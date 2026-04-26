<?php

namespace App\Support;

use App\Models\Tenant;
use App\Models\TenantPlanPayment;
use Illuminate\Support\Str;

class TenantPlanCapabilities
{
    private ?Tenant $tenant;
    private bool $isSuperAdmin;
    private ?TenantPlanPayment $latestPaidPlan;
    private bool $isFree;
    private bool $isBasic;

    private function __construct(?Tenant $tenant, bool $isSuperAdmin, ?TenantPlanPayment $latestPaidPlan)
    {
        $this->tenant = $tenant;
        $this->isSuperAdmin = $isSuperAdmin;
        $this->latestPaidPlan = $latestPaidPlan;

        $this->isFree = !$isSuperAdmin && (float) ($latestPaidPlan?->plan?->price ?? -1) <= 0;

        $planName = Str::lower(Str::ascii((string) ($latestPaidPlan?->plan?->name ?? '')));
        $this->isBasic = !$isSuperAdmin && !$this->isFree && Str::contains($planName, ['basico', 'basic']);
    }

    public static function forTenant(?Tenant $tenant, bool $isSuperAdmin = false): self
    {
        $latestPaidPlan = null;

        if (!$isSuperAdmin && $tenant?->id) {
            $latestPaidPlan = TenantPlanPayment::with('plan')
                ->where('tenant_id', (int) $tenant->id)
                ->where('status', 'paid')
                ->orderByDesc('paid_at')
                ->orderByDesc('id')
                ->first();
        }

        return new self($tenant, $isSuperAdmin, $latestPaidPlan);
    }

    public function latestPaidPlan(): ?TenantPlanPayment
    {
        return $this->latestPaidPlan;
    }

    public function isFree(): bool
    {
        return $this->isFree;
    }

    public function isBasic(): bool
    {
        return $this->isBasic;
    }

    public function isPro(): bool
    {
        return $this->isSuperAdmin || (!$this->isFree && !$this->isBasic);
    }

    public function hasFreeRestriction(): bool
    {
        return !$this->isSuperAdmin && $this->isFree;
    }

    public function hasBasicRestriction(): bool
    {
        return !$this->isSuperAdmin && $this->isBasic;
    }

    public function canDashboard(): bool
    {
        return $this->isSuperAdmin || $this->isPro();
    }

    public function canCategories(): bool
    {
        return $this->isSuperAdmin || $this->isFree || $this->isBasic || $this->isPro();
    }

    public function canProducts(): bool
    {
        return $this->canCategories();
    }

    public function canStoreManagement(): bool
    {
        return $this->isSuperAdmin || $this->isFree || $this->isBasic || $this->isPro();
    }

    public function canPaymentMethods(): bool
    {
        return $this->isSuperAdmin || $this->isPro();
    }

    public function canSales(): bool
    {
        return $this->isSuperAdmin || $this->isBasic || $this->isPro();
    }

    public function canCustomers(): bool
    {
        return $this->isSuperAdmin || $this->isPro();
    }

    public function canAccountsReceivable(): bool
    {
        return $this->isSuperAdmin || $this->isPro();
    }

    public function canPaidPendingDeliveries(): bool
    {
        return $this->isSuperAdmin || $this->isPro();
    }

    public function canInventoryEntries(): bool
    {
        return $this->isSuperAdmin || $this->isBasic || $this->isPro();
    }

    public function canProviders(): bool
    {
        return $this->isSuperAdmin || $this->isBasic || $this->isPro();
    }

    public function canWarehouses(): bool
    {
        return $this->isSuperAdmin || $this->isBasic || $this->isPro();
    }

    public function canMaterials(): bool
    {
        return $this->isSuperAdmin || $this->isBasic || $this->isPro();
    }

    public function canPurchaseHistory(): bool
    {
        return $this->isSuperAdmin || $this->isBasic || $this->isPro();
    }

    public function canPendingOrders(): bool
    {
        return $this->isSuperAdmin || $this->isPro();
    }

    public function canSalesOrders(): bool
    {
        return $this->isSuperAdmin || $this->isBasic || $this->isPro();
    }

    public function canElectronicDocuments(): bool
    {
        return $this->isSuperAdmin || $this->isPro();
    }

    public function canReports(): bool
    {
        return $this->isSuperAdmin || $this->isPro();
    }

    public function canStoreExpenses(): bool
    {
        return $this->isSuperAdmin || $this->isPro();
    }

    public function canAppointments(): bool
    {
        return $this->isSuperAdmin || $this->isPro();
    }

    public function canGeneratePurchase(): bool
    {
        return !$this->isFree();
    }

    public function canGenerateSalesReport(): bool
    {
        return !$this->isFree();
    }

    public function allowsOperationalDeliverySettings(): bool
    {
        return !$this->isFree();
    }

    public function allowsDeliveryOperations(): bool
    {
        return !$this->isFree();
    }

    public function effectiveDeliveryEnabled(?Tenant $tenant = null): bool
    {
        $targetTenant = $tenant ?? $this->tenant;

        return $this->allowsDeliveryOperations() && (bool) ($targetTenant?->delivery_enabled ?? false);
    }

    public function effectiveDeliveryNotificationsEnabled(?Tenant $tenant = null): bool
    {
        $targetTenant = $tenant ?? $this->tenant;

        return $this->allowsDeliveryOperations() && (bool) ($targetTenant?->delivery_notifications_enabled ?? true);
    }

    public function effectiveSpecialTaxpayer(?Tenant $tenant = null): bool
    {
        $targetTenant = $tenant ?? $this->tenant;

        return $this->allowsOperationalDeliverySettings() && (bool) ($targetTenant?->special_taxpayer ?? false);
    }

    public function effectiveRestrictDeliveryCity(?Tenant $tenant = null): bool
    {
        $targetTenant = $tenant ?? $this->tenant;

        return $this->isFree()
            ? true
            : (bool) ($targetTenant?->restrict_delivery_city_to_tenant ?? true);
    }
}