<?php

namespace App\Support;

use App\Models\Tenant;
use App\Models\TenantPlanPayment;

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
        $this->isFree = false;
        $this->isBasic = false;
    }

    public static function forTenant(?Tenant $tenant, bool $isSuperAdmin = false): self
    {
        $latestPaidPlan = null;

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
        return false;
    }

    public function hasBasicRestriction(): bool
    {
        return false;
    }

    public function canDashboard(): bool
    {
        return true;
    }

    public function canCategories(): bool
    {
        return true;
    }

    public function canProducts(): bool
    {
        return $this->canCategories();
    }

    public function canStoreManagement(): bool
    {
        return true;
    }

    public function canPaymentMethods(): bool
    {
        return true;
    }

    public function canSales(): bool
    {
        return true;
    }

    public function canCustomers(): bool
    {
        return true;
    }

    public function canAccountsReceivable(): bool
    {
        return true;
    }

    public function canPaidPendingDeliveries(): bool
    {
        return true;
    }

    public function canInventoryEntries(): bool
    {
        return true;
    }

    public function canProviders(): bool
    {
        return true;
    }

    public function canWarehouses(): bool
    {
        return true;
    }

    public function canMaterials(): bool
    {
        return true;
    }

    public function canPurchaseHistory(): bool
    {
        return true;
    }

    public function canPendingOrders(): bool
    {
        return true;
    }

    public function canSalesOrders(): bool
    {
        return true;
    }

    public function canElectronicDocuments(): bool
    {
        return true;
    }

    public function canReports(): bool
    {
        return true;
    }

    public function canStoreExpenses(): bool
    {
        return true;
    }

    public function canAppointments(): bool
    {
        return true;
    }

    public function canGeneratePurchase(): bool
    {
        return true;
    }

    public function canGenerateSalesReport(): bool
    {
        return true;
    }

    public function allowsOperationalDeliverySettings(): bool
    {
        return true;
    }

    public function allowsDeliveryOperations(): bool
    {
        return true;
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

        return (bool) ($targetTenant?->restrict_delivery_city_to_tenant ?? true);
    }
}