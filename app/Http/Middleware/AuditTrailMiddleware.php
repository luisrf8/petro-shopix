<?php

namespace App\Http\Middleware;

use App\Support\AuditLogger;
use Closure;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class AuditTrailMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $start = microtime(true);
        $response = null;
        $exception = null;
        $oldModels = $this->captureRouteModelSnapshots($request, false);

        try {
            $response = $next($request);

            return $response;
        } catch (\Throwable $th) {
            $exception = $th;
            throw $th;
        } finally {
            $newModels = $this->captureRouteModelSnapshots($request, true);
            $request->attributes->set('audit_old_models', $oldModels);
            $request->attributes->set('audit_new_models', $newModels);

            $durationMs = (microtime(true) - $start) * 1000;
            AuditLogger::logRequest($request, $response, $exception, $durationMs);
        }
    }

    private function captureRouteModelSnapshots(Request $request, bool $refresh): array
    {
        $route = $request->route();
        if (!$route) {
            return [];
        }

        $snapshots = [];

        foreach ((array) $route->parameters() as $param => $value) {
            if (!$value instanceof Model) {
                continue;
            }

            $model = $refresh ? $value->fresh() : $value;
            if (!$model instanceof Model) {
                continue;
            }

            $snapshots[$param] = [
                'class' => $model->getMorphClass(),
                'table' => $model->getTable(),
                'id' => $model->getKey(),
                'attributes' => $this->normalizeAttributes((array) $model->getAttributes()),
            ];
        }

        foreach ($this->captureFallbackSnapshots($request) as $param => $snapshot) {
            if (!array_key_exists($param, $snapshots)) {
                $snapshots[$param] = $snapshot;
            }
        }

        return $snapshots;
    }

    private function captureFallbackSnapshots(Request $request): array
    {
        $route = $request->route();
        if (!$route) {
            return [];
        }

        $routeParameters = (array) $route->parameters();
        $scalarId = null;

        foreach ($routeParameters as $value) {
            if (is_scalar($value) && is_numeric($value)) {
                $scalarId = (int) $value;
                break;
            }
        }

        if (!$scalarId) {
            return [];
        }

        $tables = $this->resolveCandidateTables($request);
        foreach ($tables as $table) {
            try {
                if (!Schema::hasTable($table)) {
                    continue;
                }

                $row = DB::table($table)->where('id', $scalarId)->first();
                if (!$row) {
                    continue;
                }

                return [
                    $table . '_fallback' => [
                        'class' => $table,
                        'table' => $table,
                        'id' => $scalarId,
                        'attributes' => $this->normalizeAttributes((array) $row),
                    ],
                ];
            } catch (\Throwable $th) {
                continue;
            }
        }

        return [];
    }

    private function resolveCandidateTables(Request $request): array
    {
        $candidates = [];

        $routeName = (string) ($request->route()?->getName() ?? '');
        if ($routeName !== '') {
            $module = (string) Str::before($routeName, '.');
            if ($module !== '') {
                $candidates[] = Str::snake(str_replace('-', '_', $module));
                $candidates[] = Str::plural(Str::snake(str_replace('-', '_', $module)));
            }
        }

        $segments = array_values(array_filter(explode('/', trim((string) $request->path(), '/'))));
        foreach ($segments as $segment) {
            $segment = trim(Str::lower($segment));
            if ($segment === '' || $segment === 'api' || is_numeric($segment)) {
                continue;
            }

            if (in_array($segment, ['update', 'edit', 'store', 'create', 'toggle-status', 'status', 'generate-codes', 'barcode', 'import-catalog'], true)) {
                continue;
            }

            $candidates[] = Str::snake(str_replace('-', '_', $segment));
            $candidates[] = Str::plural(Str::snake(str_replace('-', '_', $segment)));
        }

        return array_values(array_unique(array_filter($candidates)));
    }

    private function normalizeAttributes(array $attributes): array
    {
        $normalized = [];

        foreach ($attributes as $key => $value) {
            if (is_scalar($value) || $value === null) {
                $normalized[$key] = $value;
            }
        }

        return $normalized;
    }
}
