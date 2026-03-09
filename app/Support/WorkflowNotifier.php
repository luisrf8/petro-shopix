<?php

namespace App\Support;

use App\Models\User;
use App\Notifications\WorkflowStatusNotification;
use Illuminate\Support\Collection;

class WorkflowNotifier
{
    public static function notifyTenantRoles(int $tenantId, array $roleNames, array $payload): void
    {
        $normalizedRoleNames = collect($roleNames)
            ->map(fn ($name) => strtolower((string) $name))
            ->filter()
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

        $user->notify(new WorkflowStatusNotification($payload));
    }

    public static function notifyUsers(iterable $users, array $payload): void
    {
        collect($users)
            ->filter(fn ($user) => $user instanceof User)
            ->each(function (User $user) use ($payload) {
                if ((bool) $user->is_active) {
                    $user->notify(new WorkflowStatusNotification($payload));
                }
            });
    }
}
