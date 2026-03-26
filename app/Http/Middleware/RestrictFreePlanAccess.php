<?php

namespace App\Http\Middleware;

use App\Models\TenantPlanPayment;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RestrictFreePlanAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (!$user) {
            return $next($request);
        }

        if ((int) ($user->role_id ?? 0) === 4) {
            return $next($request);
        }

        $tenantId = (int) ($user->tenant_id ?? 0);
        if ($tenantId <= 0) {
            return $next($request);
        }

        if (!$this->isFreePlanTenant($tenantId)) {
            return $next($request);
        }

        if ($this->isRouteAllowedForFreePlan($request)) {
            return $next($request);
        }

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'success' => false,
                'message' => 'Tu plan Free solo permite Categorías, Productos y Gestión de Tienda.',
            ], 403);
        }

        return redirect()->route('categories.index')
            ->with('warning', 'Tu plan Free solo permite Categorías, Productos y Gestión de Tienda.');
    }

    private function isFreePlanTenant(int $tenantId): bool
    {
        $latestPaid = TenantPlanPayment::with('plan')
            ->where('tenant_id', $tenantId)
            ->where('status', 'paid')
            ->orderByDesc('paid_at')
            ->orderByDesc('id')
            ->first();

        if (!$latestPaid || !$latestPaid->plan) {
            return false;
        }

        return (float) ($latestPaid->plan->price ?? 0) <= 0;
    }

    private function isRouteAllowedForFreePlan(Request $request): bool
    {
        $routeName = (string) ($request->route()?->getName() ?? '');

        $allowedByName = [
            'categories.index',
            'products.index',
            'products.importCatalogWeb',
            'products.generateCodesWeb',
            'products.byCategory',
            'productItem',
            'createProductItem',
            'variants.generateCodesWeb',
            'variants.qrImage',
            'tenant.store',
            'tenant.update',
            'tenant.planPayment.request',
            'logout',
        ];

        foreach ($allowedByName as $allowedName) {
            if ($routeName === $allowedName) {
                return true;
            }
        }

        $path = trim((string) $request->path(), '/');
        $allowedPrefixes = [
            'categories',
            'products',
            'createProduct',
            'variants',
            'tenant-store',
            'tenant-update',
            'logout',
        ];

        foreach ($allowedPrefixes as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
                return true;
            }
        }

        return false;
    }
}
