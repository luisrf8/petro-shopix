<?php

namespace App\Support;

use App\Models\TenantPlanPayment;
use App\Models\User;
use Illuminate\Support\Str;

class UserRedirector
{
    public static function isSuperAdmin(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        return (int) ($user->role_id ?? 0) === 4 || Str::lower((string) optional($user->role)->name) === 'super_user';
    }

    public static function isCustomer(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        $roleName = Str::lower(Str::ascii((string) optional($user->role)->name));

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

        return $user->hasStoreRole('owner', 'admin', 'seller', 'warehouse');
    }

    public static function resolveBackofficeRedirect(?User $user): string
    {
        if (!$user) {
            return '/';
        }

        if (self::isSuperAdmin($user)) {
            return '/plans';
        }

        if (self::isCustomer($user)) {
            return '/';
        }

        [$isFreePlan, $isBasicPlan] = self::resolvePlanFlags($user);

        if ($user->hasStoreRole('warehouse')) {
            return '/sales-orders/pending-delivery';
        }

        if ($user->hasStoreRole('seller')) {
            return ($isFreePlan || $isBasicPlan) ? '/products' : '/sales';
        }

        if ($user->isOwner() || $user->isAdmin()) {
            return ($isFreePlan || $isBasicPlan) ? '/products' : '/dashboard';
        }

        return ($isFreePlan || $isBasicPlan) ? '/products' : '/dashboard';
    }

    public static function resolvePlanFlags(?User $user): array
    {
        if (!$user || !(int) ($user->tenant_id ?? 0)) {
            return [false, false];
        }

        $latestPaid = TenantPlanPayment::with('plan')
            ->where('tenant_id', (int) $user->tenant_id)
            ->where('status', 'paid')
            ->orderByDesc('paid_at')
            ->orderByDesc('id')
            ->first();

        $planName = Str::lower(Str::ascii((string) ($latestPaid?->plan?->name ?? '')));
        $isFreePlan = (float) ($latestPaid?->plan?->price ?? -1) <= 0;
        $isBasicPlan = Str::contains($planName, ['basico', 'basic']);

        return [$isFreePlan, $isBasicPlan];
    }
}