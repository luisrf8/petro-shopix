<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Support\UserRedirector;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

class RestrictInactiveTenantAccess
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        if (!$user || UserRedirector::isSuperAdmin($user) || UserRedirector::isCustomer($user)) {
            return $next($request);
        }

        $tenantId = (int) ($user->tenant_id ?? 0);
        if ($tenantId <= 0) {
            return $next($request);
        }

        if (!Schema::hasColumn('tenants', 'is_active')) {
            return $next($request);
        }

        $isTenantInactive = Tenant::query()
            ->whereKey($tenantId)
            ->where('is_active', 0)
            ->exists();

        if (!$isTenantInactive) {
            return $next($request);
        }

        if ($request->routeIs('tenant.store', 'tenant.update', 'tenant.planPayment.request', 'logout')) {
            return $next($request);
        }

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'La tienda está inactiva. Solo puedes acceder a Gestión de tienda y pagos.',
                'redirect_to' => route('tenant.store'),
            ], 403);
        }

        return redirect()
            ->route('tenant.store')
            ->with('warning', 'La tienda está inactiva. Solo puedes acceder a Gestión de tienda y pagos.');
    }
}
