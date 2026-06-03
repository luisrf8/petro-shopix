<?php

namespace App\Http\Middleware;

use App\Support\TenantPlanResolver;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class RestrictBasicPlanAccess
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
        if ($tenantId <= 0 || !$this->isBasicPlanTenant($tenantId)) {
            return $next($request);
        }

        if ($this->isBlockedRouteForBasicPlan($request)) {
            $message = 'El plan Básico solo permite: Categorías, Productos, Gestión de Tienda, Realizar Venta, Entrada de Inventario, Proveedores, Lista de Materiales, Ventas Realizadas e Historial de Entradas.';

            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 403);
            }

            return back()->with('warning', $message);
        }

        return $next($request);
    }

    private function isBasicPlanTenant(int $tenantId): bool
    {
        $latestPaid = TenantPlanResolver::latestPaidForTenant($tenantId);

        if (!$latestPaid || !$latestPaid->plan) {
            return false;
        }

        $planName = Str::lower(Str::ascii((string) ($latestPaid->plan->name ?? '')));

        return Str::contains($planName, ['basico', 'basic']);
    }

    private function isBlockedRouteForBasicPlan(Request $request): bool
    {
        $routeName = (string) ($request->route()?->getName() ?? '');

        $blockedByName = [
            'dashboard',
            'paymentMethods.index',
            'customers.index',
            'customers.store',
            'customers.update',
            'customers.toggleStatus',
            'accounts.receivable.index',
            'sales.paidPendingDeliveries.index',
            'sales.orders.pendingDelivery',
            'sales.electronic.documents.tenant',
            'store-expenses.index',
            'store-expenses.store',
            'store-expenses.update',
        ];

        if (in_array($routeName, $blockedByName, true)) {
            return true;
        }

        if (Str::startsWith($routeName, 'reports.')
            || Str::startsWith($routeName, 'sales.electronic.')
            || Str::startsWith($routeName, 'electronic.documents.')
        ) {
            return true;
        }

        $path = trim((string) $request->path(), '/');

        $blockedPrefixes = [
            'dashboard',
            'paymentMethods',
            'customers',
            'accounts-receivable',
            'paid-pending-deliveries',
            'my-electronic-documents',
            'electronic-documents',
            'store-expenses',
            'reports',
        ];

        foreach ($blockedPrefixes as $prefix) {
            if ($path === $prefix || str_starts_with($path, $prefix . '/')) {
                return true;
            }
        }

        if ($path === 'sales-orders/pending-delivery' || str_starts_with($path, 'sales-orders/pending-delivery/')) {
            return true;
        }

        return str_contains($path, '/electronic/');
    }
}
