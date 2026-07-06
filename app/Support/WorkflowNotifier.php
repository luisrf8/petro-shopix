<?php

namespace App\Support;

use App\Models\User;
use App\Notifications\WorkflowStatusNotification;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;

class WorkflowNotifier
{
    public static function notifyTenantRoles(int $tenantId, array $roleNames, array $payload): void
    {
        $definitions = User::storeRoleDefinitions();

        $normalizedRoleNames = collect($roleNames)
            ->flatMap(function ($name) use ($definitions) {
                $normalized = strtolower(trim((string) $name));
                if ($normalized === '') {
                    return [];
                }

                $canonical = User::canonicalRoleName($normalized) ?? $normalized;
                $aliases = $definitions[$canonical]['aliases'] ?? [];

                return array_merge([$normalized, $canonical], array_map(
                    fn ($alias) => strtolower((string) $alias),
                    $aliases
                ));
            })
            ->filter()
            ->unique()
            ->values();

        if ($normalizedRoleNames->isEmpty()) {
            return;
        }

        $users = User::query()
            ->with('role')
            ->where('tenant_id', $tenantId)
            ->where('is_active', 1)
            ->get()
            ->filter(function (User $user) use ($normalizedRoleNames) {
                return $normalizedRoleNames->contains(strtolower((string) optional($user->role)->name));
            });

        self::notifyUsers($users, $payload);
    }

    public static function notifyUser(?User $user, array $payload): void
    {
        if (!$user || !(bool) $user->is_active) {
            return;
        }

        self::dispatchNotification($user, $payload);
    }

    public static function notifyUsers(iterable $users, array $payload): void
    {
        collect($users)
            ->filter(fn ($user) => $user instanceof User)
            ->each(function (User $user) use ($payload) {
                if ((bool) $user->is_active) {
                    self::dispatchNotification($user, $payload);
                }
            });
    }

    private static function dispatchNotification(User $user, array $payload): void
    {
        try {
            $user->notify(new WorkflowStatusNotification($payload));
        } catch (\Throwable $exception) {
            $storedFallback = self::storeDatabaseFallbackNotification($user, $payload);

            Log::warning('No se pudo despachar notificación de workflow.', [
                'user_id' => $user->id,
                'tenant_id' => $payload['tenant_id'] ?? null,
                'order_id' => $payload['order_id'] ?? null,
                'payment_id' => $payload['payment_id'] ?? null,
                'action' => $payload['action'] ?? null,
                'database_fallback_stored' => $storedFallback,
                'error' => $exception->getMessage(),
            ]);
        }
    }

    private static function storeDatabaseFallbackNotification(User $user, array $payload): bool
    {
        try {
            $action = (string) ($payload['action'] ?? '');
            $orderId = $payload['order_id'] ?? null;
            $paymentId = $payload['payment_id'] ?? null;
            $title = (string) ($payload['title'] ?? 'Notificación');
            $message = (string) ($payload['message'] ?? '');

            $recent = $user->notifications()
                ->latest()
                ->take(5)
                ->get()
                ->first(function ($notification) use ($action, $orderId, $paymentId, $title, $message) {
                    $data = is_array($notification->data ?? null) ? $notification->data : [];

                    return (string) ($data['action'] ?? '') === $action
                        && (string) ($data['order_id'] ?? '') === (string) ($orderId ?? '')
                        && (string) ($data['payment_id'] ?? '') === (string) ($paymentId ?? '')
                        && (string) ($data['title'] ?? '') === $title
                        && (string) ($data['message'] ?? '') === $message
                        && $notification->created_at instanceof Carbon
                        && $notification->created_at->greaterThanOrEqualTo(now()->subSeconds(15));
                });

            if ($recent) {
                return false;
            }

            $notificationData = (new WorkflowStatusNotification($payload))->toArray($user);

            $user->notifications()->create([
                'id' => (string) \Illuminate\Support\Str::uuid(),
                'type' => WorkflowStatusNotification::class,
                'data' => $notificationData,
                'read_at' => null,
            ]);

            return true;
        } catch (\Throwable $fallbackException) {
            Log::warning('No se pudo guardar fallback de notificación en base de datos.', [
                'user_id' => $user->id,
                'tenant_id' => $payload['tenant_id'] ?? null,
                'order_id' => $payload['order_id'] ?? null,
                'action' => $payload['action'] ?? null,
                'error' => $fallbackException->getMessage(),
            ]);

            return false;
        }
    }
}
