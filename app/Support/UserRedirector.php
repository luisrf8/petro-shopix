<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Support\Str;

class UserRedirector
{
    public static function isSuperAdmin(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        $roleName = Str::lower(Str::ascii(trim((string) optional($user->role)->name)));
        $roleName = str_replace([' ', '-'], '_', $roleName);

        return (int) ($user->role_id ?? 0) === 4
            || in_array($roleName, ['super_user', 'superuser', 'superowner', 'super-owner'], true);
    }

    public static function isCustomer(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        $roleName = Str::lower(Str::ascii(trim((string) optional($user->role)->name)));

        return in_array($roleName, ['user', 'cliente', 'customer'], true);
    }

    public static function canAccessBackoffice(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        if (self::isSuperAdmin($user)) {
            return true;
        }

        if (self::isCustomer($user)) {
            return false;
        }

        return $user->hasStoreRole('owner', 'admin', 'seller', 'warehouse', 'delivery');
    }

    public static function resolveBackofficeRedirect(?User $user): string
    {
        if (!$user) {
            return '/';
        }

        if (self::isSuperAdmin($user)) {
            return '/tenants';
        }

        if (self::isCustomer($user)) {
            return '/';
        }

        if ($user->hasStoreRole('warehouse', 'delivery')) {
            return '/sales-orders/pending-delivery';
        }

        if ($user->hasStoreRole('seller')) {
            return '/sales';
        }

        if ($user->isOwner() || $user->isAdmin()) {
            return '/dashboard';
        }

        return '/dashboard';
    }

    public static function resolvePlanFlags(?User $user): array
    {
        return [false, false];
    }
}