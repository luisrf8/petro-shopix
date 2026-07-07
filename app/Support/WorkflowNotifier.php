<?php

namespace App\Support;

use App\Models\Payment;
use App\Models\SalesOrder;
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
        if (!self::shouldDispatchToUser($user, $payload)) {
            return;
        }

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

    private static function shouldDispatchToUser(User $user, array $payload): bool
    {
        // Restricción: un vendedor solo recibe notificaciones asociadas a su propio contexto.
        if (!(bool) $user->hasStoreRole('seller')) {
            return true;
        }

        $sellerId = (int) ($user->id ?? 0);
        if ($sellerId <= 0) {
            return false;
        }

        $relatedOrderId = self::extractRelatedOrderId($payload);
        if ($relatedOrderId > 0) {
            return self::orderIsRelatedToSeller($relatedOrderId, $sellerId);
        }

        // Si no existe forma de vincular la notificación al vendedor autenticado, no se envía.
        return self::payloadContainsSellerReference($payload, $sellerId);
    }

    private static function extractRelatedOrderId(array $payload): int
    {
        $orderId = (int) ($payload['order_id'] ?? 0);
        if ($orderId > 0) {
            return $orderId;
        }

        $meta = is_array($payload['meta'] ?? null) ? $payload['meta'] : [];
        $metaOrderId = (int) ($meta['order_id'] ?? 0);
        if ($metaOrderId > 0) {
            return $metaOrderId;
        }

        $paymentId = (int) ($payload['payment_id'] ?? 0);
        if ($paymentId <= 0) {
            $paymentId = (int) ($meta['payment_id'] ?? 0);
        }

        if ($paymentId > 0) {
            return (int) Payment::query()->whereKey($paymentId)->value('sales_order_id');
        }

        return 0;
    }

    private static function orderIsRelatedToSeller(int $orderId, int $sellerId): bool
    {
        $order = SalesOrder::query()
            ->select(['id', 'sales_rep_user_id'])
            ->whereKey($orderId)
            ->first();

        if (!$order) {
            return false;
        }

        return (int) ($order->sales_rep_user_id ?? 0) === $sellerId;
    }

    private static function payloadContainsSellerReference(array $payload, int $sellerId): bool
    {
        $directKeys = [
            'user_id',
            'target_user_id',
            'sales_rep_user_id',
            'assigned_user_id',
            'assigned_by_user_id',
            'actor_user_id',
            'delivery_user_id',
            'customer_user_id',
        ];

        foreach ($directKeys as $key) {
            if ((int) ($payload[$key] ?? 0) === $sellerId) {
                return true;
            }
        }

        $meta = is_array($payload['meta'] ?? null) ? $payload['meta'] : [];
        foreach ($directKeys as $key) {
            if ((int) ($meta[$key] ?? 0) === $sellerId) {
                return true;
            }
        }

        $relatedIds = $meta['related_user_ids'] ?? null;
        if (is_array($relatedIds)) {
            return collect($relatedIds)->map(fn ($id) => (int) $id)->contains($sellerId);
        }

        return false;
    }
}
