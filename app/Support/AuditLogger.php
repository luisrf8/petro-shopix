<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response as SymfonyResponse;

class AuditLogger
{
    public static function logRequest(Request $request, ?SymfonyResponse $response = null, ?\Throwable $exception = null, float $durationMs = 0): void
    {
        try {
            if (self::shouldSkipRequest($request)) {
                return;
            }

            $user = Auth::user();
            $route = $request->route();
            $routeName = (string) ($route?->getName() ?? 'unknown');
            $path = trim((string) $request->path(), '/');
            $method = strtoupper((string) $request->method());
            $status = (int) ($response?->getStatusCode() ?? ($exception ? 500 : 0));

            $module = self::resolveModule($routeName, $path);
            $action = self::resolveActionLabel($method, $status);

            $descriptionPayload = [
                'route_name' => $routeName,
                'path' => '/' . $path,
                'method' => $method,
                'status' => $status,
                'duration_ms' => round(max(0, $durationMs), 2),
                'module' => $module,
                'role' => optional($user?->role)->name,
                'tenant_id' => (int) ($user->tenant_id ?? 0),
                'user_name' => $user->name ?? null,
                'ip' => $request->ip(),
                'user_agent' => Str::limit((string) $request->userAgent(), 200),
            ];

            $oldModels = (array) $request->attributes->get('audit_old_models', []);
            $newModels = (array) $request->attributes->get('audit_new_models', []);

            if (in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
                $descriptionPayload['payload'] = self::sanitizeData($request->all());
            } elseif (!empty($request->query())) {
                $descriptionPayload['query'] = self::sanitizeData($request->query());
            }

            $humanDescription = self::buildHumanDescription(
                $method,
                $status,
                $module,
                $oldModels,
                $newModels,
                (array) ($descriptionPayload['payload'] ?? [])
            );

            if (!empty($humanDescription['message'])) {
                $descriptionPayload['message'] = $humanDescription['message'];
            }

            if (!empty($humanDescription['changes'])) {
                $descriptionPayload['changes'] = $humanDescription['changes'];
            }

            if ($exception) {
                $descriptionPayload['error'] = Str::limit($exception->getMessage(), 300);
            }

            self::insert([
                'table_name' => Str::limit($module, 100, ''),
                'action' => Str::limit($action, 100, ''),
                'user_id' => $user?->id,
                'tenant_id' => $user?->tenant_id,
                'event_type' => 'request',
                'description' => json_encode($descriptionPayload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'old_values' => !empty($oldModels) ? $oldModels : null,
                'new_values' => !empty($newModels) ? $newModels : null,
                'ip_address' => $request->ip(),
                'occurred_at' => now(),
            ]);
        } catch (\Throwable $loggingException) {
            Log::warning('Audit logging failed', [
                'message' => $loggingException->getMessage(),
            ]);
        }
    }

    public static function logEvent(string $module, string $action, string $message, ?int $userId = null, array $extra = []): void
    {
        try {
            $user = $userId ? null : Auth::user();
            $effectiveUserId = $userId ?: ($user?->id);
            $tenantId = $extra['tenant_id'] ?? $user?->tenant_id;

            $payload = array_merge([
                'module' => $module,
                'message' => $message,
                'role' => optional($user?->role)->name,
                'tenant_id' => $tenantId,
                'ip' => request()?->ip(),
            ], self::sanitizeData($extra));

            self::insert([
                'table_name' => Str::limit($module, 100, ''),
                'action' => Str::limit($action, 100, ''),
                'user_id' => $effectiveUserId,
                'tenant_id' => self::normalizeNullableInt($tenantId),
                'event_type' => Str::lower(trim($module)) === 'auth' ? 'auth' : 'event',
                'description' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'ip_address' => request()?->ip(),
                'occurred_at' => now(),
            ]);
        } catch (\Throwable $loggingException) {
            Log::warning('Audit event logging failed', [
                'message' => $loggingException->getMessage(),
            ]);
        }
    }

    public static function logModelChange(Model $model, string $action, string $message, ?array $oldValues = null, ?array $newValues = null): void
    {
        try {
            $user = Auth::user();
            $table = $model->getTable();

            $payload = [
                'module' => 'audit',
                'message' => $message,
                'model' => get_class($model),
                'table' => $table,
                'record_id' => (string) $model->getKey(),
                'tenant_id' => self::resolveModelTenantId($model, $oldValues, $newValues, $user?->tenant_id),
                'user_name' => $user?->name,
                'ip' => request()?->ip(),
            ];

            self::insert([
                'table_name' => Str::limit($table, 100, ''),
                'action' => Str::limit($action, 100, ''),
                'user_id' => $user?->id,
                'tenant_id' => self::resolveModelTenantId($model, $oldValues, $newValues, $user?->tenant_id),
                'event_type' => 'model',
                'record_id' => (string) $model->getKey(),
                'description' => json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                'old_values' => self::sanitizeData($oldValues ?? []),
                'new_values' => self::sanitizeData($newValues ?? []),
                'ip_address' => request()?->ip(),
                'occurred_at' => now(),
            ]);
        } catch (\Throwable $loggingException) {
            Log::warning('Audit model logging failed', [
                'message' => $loggingException->getMessage(),
                'table' => $model->getTable(),
            ]);
        }
    }

    private static function insert(array $payload): void
    {
        DB::table('audit_log')->insert([
            'table_name' => (string) ($payload['table_name'] ?? 'system'),
            'action' => (string) ($payload['action'] ?? 'UNKNOWN'),
            'user_id' => self::normalizeNullableInt($payload['user_id'] ?? null),
            'tenant_id' => self::normalizeNullableInt($payload['tenant_id'] ?? null),
            'event_type' => $payload['event_type'] ?? null,
            'record_id' => isset($payload['record_id']) ? (string) $payload['record_id'] : null,
            'description' => (string) ($payload['description'] ?? ''),
            'old_values' => isset($payload['old_values']) ? json_encode($payload['old_values'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
            'new_values' => isset($payload['new_values']) ? json_encode($payload['new_values'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) : null,
            'ip_address' => $payload['ip_address'] ?? request()?->ip(),
            'occurred_at' => $payload['occurred_at'] ?? now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private static function resolveModelTenantId(Model $model, ?array $oldValues, ?array $newValues, $fallback = null): ?int
    {
        $candidates = [
            $model->getAttribute('tenant_id'),
            $newValues['tenant_id'] ?? null,
            $oldValues['tenant_id'] ?? null,
            $fallback,
        ];

        foreach ($candidates as $candidate) {
            $normalized = self::normalizeNullableInt($candidate);
            if ($normalized !== null) {
                return $normalized;
            }
        }

        return null;
    }

    private static function normalizeNullableInt($value): ?int
    {
        $normalized = is_numeric($value) ? (int) $value : null;

        return $normalized && $normalized > 0 ? $normalized : null;
    }

    private static function resolveModule(string $routeName, string $path): string
    {
        if ($routeName !== '' && $routeName !== 'unknown') {
            return Str::before($routeName, '.');
        }

        $segment = trim((string) Str::before($path, '/'));

        return $segment !== '' ? $segment : 'system';
    }

    private static function resolveActionLabel(string $method, int $status): string
    {
        $result = $status >= 200 && $status < 400 ? 'OK' : ($status >= 400 ? 'ERROR' : 'UNKNOWN');

        return $method . ' ' . $result . ' (' . $status . ')';
    }

    private static function shouldSkipRequest(Request $request): bool
    {
        $method = strtoupper((string) $request->method());

        if (self::isImageOnlyRequest($request)) {
            return true;
        }

        return !in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true);
    }

    private static function isImageOnlyRequest(Request $request): bool
    {
        $method = strtoupper((string) $request->method());
        if (!in_array($method, ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            return false;
        }

        $path = Str::lower(trim((string) $request->path(), '/'));
        $routeName = Str::lower(trim((string) ($request->route()?->getName() ?? '')));

        $imagePathKeywords = [
            'addimage',
            'remove-image',
            'update-qr',
            'remove-qr',
            'qr-image',
        ];

        foreach ($imagePathKeywords as $keyword) {
            if (Str::contains($path, $keyword) || Str::contains($routeName, $keyword)) {
                return true;
            }
        }

        if (!$request->hasFile()) {
            return false;
        }

        $keys = array_unique(array_merge(array_keys($request->all()), array_keys($request->allFiles())));
        if (empty($keys)) {
            return false;
        }

        $allowedMetaKeys = [
            '_token',
            '_method',
            'id',
            'product_id',
            'product_variant_id',
            'tenant_id',
        ];

        foreach ($keys as $key) {
            $normalizedKey = Str::lower((string) $key);
            if (in_array($normalizedKey, $allowedMetaKeys, true)) {
                continue;
            }

            if (!self::isImageFieldKey($normalizedKey)) {
                return false;
            }
        }

        return true;
    }

    private static function buildHumanDescription(string $method, int $status, string $module, array $oldModels, array $newModels, array $payload): array
    {
        $isSuccess = $status >= 200 && $status < 400;
        $base = [
            'message' => null,
            'changes' => [],
        ];

        if (!$isSuccess) {
            $base['message'] = 'Operación fallida en ' . $module . '.';
            return $base;
        }

        if (in_array($method, ['PUT', 'PATCH'], true) || ($method === 'POST' && !empty($oldModels))) {
            $changes = self::buildModelDiffLines($oldModels, $newModels);
            if (!empty($changes)) {
                $base['message'] = 'Se actualizó ' . $module . '.';
                $base['changes'] = $changes;
                return $base;
            }

            $base['message'] = 'Se actualizó ' . $module . '.';
            return $base;
        }

        if ($method === 'DELETE') {
            $deleted = self::buildDeleteLines($oldModels);
            $base['message'] = 'Se eliminó registro en ' . $module . '.';
            $base['changes'] = $deleted;
            return $base;
        }

        if ($method === 'POST') {
            $base['message'] = 'Se creó registro en ' . $module . '.';
            $base['changes'] = self::buildCreateLines($payload);
            return $base;
        }

        $base['message'] = 'Operación registrada en ' . $module . '.';
        return $base;
    }

    private static function buildModelDiffLines(array $oldModels, array $newModels): array
    {
        $lines = [];

        foreach ($oldModels as $param => $oldModel) {
            $newModel = $newModels[$param] ?? null;
            $oldAttributes = (array) ($oldModel['attributes'] ?? []);
            $newAttributes = (array) ($newModel['attributes'] ?? []);

            foreach ($oldAttributes as $key => $oldValue) {
                if (self::isIgnoredAuditKey((string) $key)) {
                    continue;
                }

                if (!array_key_exists($key, $newAttributes)) {
                    continue;
                }

                $newValue = $newAttributes[$key];
                if (self::normalizeComparableValue($oldValue) === self::normalizeComparableValue($newValue)) {
                    continue;
                }

                $lines[] = sprintf(
                    '%s se actualizó de: %s a: %s',
                    self::humanizeKey((string) $key),
                    self::formatValue($oldValue),
                    self::formatValue($newValue)
                );
            }
        }

        return array_values(array_unique($lines));
    }

    private static function buildCreateLines(array $payload): array
    {
        $lines = [];

        foreach ($payload as $key => $value) {
            if (self::isIgnoredAuditKey((string) $key)) {
                continue;
            }

            if (is_array($value) || is_object($value)) {
                continue;
            }

            $lines[] = sprintf(
                '%s inicial: %s',
                self::humanizeKey((string) $key),
                self::formatValue($value)
            );
        }

        return array_slice($lines, 0, 20);
    }

    private static function buildDeleteLines(array $oldModels): array
    {
        $lines = [];

        foreach ($oldModels as $param => $oldModel) {
            $table = (string) ($oldModel['table'] ?? $param);
            $id = self::formatValue($oldModel['id'] ?? null);
            $lines[] = sprintf('%s eliminado con ID: %s', $table, $id);
        }

        return $lines;
    }

    private static function formatValue($value): string
    {
        if ($value === null || $value === '') {
            return 'vacío';
        }

        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_scalar($value)) {
            return Str::limit((string) $value, 120);
        }

        return '[complejo]';
    }

    private static function normalizeComparableValue($value): string
    {
        if ($value === null) {
            return 'null';
        }

        if (is_bool($value)) {
            return $value ? '1' : '0';
        }

        if (is_numeric($value)) {
            return (string) +$value;
        }

        return trim((string) $value);
    }

    private static function humanizeKey(string $key): string
    {
        $key = str_replace('_', ' ', trim($key));
        return Str::ucfirst($key);
    }

    private static function sanitizeData($data, int $depth = 0)
    {
        if ($depth > 4) {
            return '[max-depth]';
        }

        if (is_array($data)) {
            $sanitized = [];
            foreach ($data as $key => $value) {
                $normalizedKey = Str::lower((string) $key);
                if (Str::contains($normalizedKey, ['password', 'token', 'authorization', 'secret', 'refresh_token'])) {
                    $sanitized[$key] = '***';
                    continue;
                }

                if (self::isImageFieldKey($normalizedKey)) {
                    continue;
                }

                $sanitized[$key] = self::sanitizeData($value, $depth + 1);
            }

            return $sanitized;
        }

        if (is_object($data)) {
            return self::sanitizeData((array) $data, $depth + 1);
        }

        if (is_string($data)) {
            return Str::limit($data, 300);
        }

        return $data;
    }

    private static function isIgnoredAuditKey(string $key): bool
    {
        $normalized = Str::lower(trim($key));

        if ($normalized === '') {
            return true;
        }

        if (in_array($normalized, [
            '_token',
            '_method',
            'updated_at',
            'created_at',
            'deleted_at',
            'remember_token',
            'password',
            'password_confirmation',
            'token',
        ], true)) {
            return true;
        }

        return self::isImageFieldKey($normalized);
    }

    private static function isImageFieldKey(string $key): bool
    {
        return Str::contains($key, [
            'image',
            'images',
            'file',
            'proof',
            'logo',
            'background',
            'avatar',
            'qr',
            'photo',
            'picture',
        ]);
    }
}
