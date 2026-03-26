<?php

namespace App\Http\Middleware;

use App\Models\TenantPlanPayment;
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
            $message = 'El plan Básico no permite esta acción (reportes, crear almacenes o crear lista de materiales).';

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
        $latestPaid = TenantPlanPayment::with('plan')
            ->where('tenant_id', $tenantId)
            ->where('status', 'paid')
            ->orderByDesc('paid_at')
            ->orderByDesc('id')
            ->first();

        if (!$latestPaid || !$latestPaid->plan) {
            return false;
        }

        $planName = Str::lower(Str::ascii((string) ($latestPaid->plan->name ?? '')));

        return Str::contains($planName, ['basico', 'basic']);
    }

    private function isBlockedRouteForBasicPlan(Request $request): bool
    {
        $routeName = (string) ($request->route()?->getName() ?? '');

        $blockedExact = [
            'dashboard',
            'warehouses.store',
            'materials.store',
            'reports.index',
        ];

        if (in_array($routeName, $blockedExact, true)) {
            return true;
        }

        if (Str::startsWith($routeName, 'reports.')) {
            return true;
        }

        $path = trim((string) $request->path(), '/');

        return $path === 'dashboard'
            || $path === 'reports'
            || str_starts_with($path, 'reports/');
    }
}
