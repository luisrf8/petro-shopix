<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Support\UserRedirector;
use Closure;
use Illuminate\Http\Request;

class ApplySuperOwnerTenantScope
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        if (!$user || !UserRedirector::isSuperAdmin($user)) {
            return $next($request);
        }

        $selectedTenantId = session()->has('superowner_tenant_scope_id')
            ? (int) session('superowner_tenant_scope_id', 0)
            : null;

        if ($selectedTenantId === null) {
            $selectedTenantId = (int) Tenant::query()->orderBy('id')->value('id');
            if ($selectedTenantId > 0) {
                session(['superowner_tenant_scope_id' => $selectedTenantId]);
            }
        }

        if ($selectedTenantId > 0) {
            // Apply selected tenant globally so existing module queries keep working.
            $user->tenant_id = $selectedTenantId;

            if (!$request->query->has('tenant_id') && !$request->request->has('tenant_id')) {
                $request->merge(['tenant_id' => $selectedTenantId]);
            }
        }

        view()->share('superOwnerTenantScopeId', $selectedTenantId);
        view()->share('superOwnerTenantScopeOptions', Tenant::query()->orderBy('name')->get(['id', 'name']));

        return $next($request);
    }
}
