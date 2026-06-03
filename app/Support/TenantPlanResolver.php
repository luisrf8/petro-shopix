<?php

namespace App\Support;

use App\Models\TenantPlanPayment;

class TenantPlanResolver
{
    /**
     * @var array<int, TenantPlanPayment|null>
     */
    private static array $latestPaidByTenant = [];

    public static function latestPaidForTenant(int $tenantId): ?TenantPlanPayment
    {
        if ($tenantId <= 0) {
            return null;
        }

        if (array_key_exists($tenantId, self::$latestPaidByTenant)) {
            return self::$latestPaidByTenant[$tenantId];
        }

        self::$latestPaidByTenant[$tenantId] = TenantPlanPayment::with('plan')
            ->where('tenant_id', $tenantId)
            ->where('status', 'paid')
            ->orderByDesc('paid_at')
            ->orderByDesc('id')
            ->first();

        return self::$latestPaidByTenant[$tenantId];
    }
}
