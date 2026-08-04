<?php

namespace App\Http\Controllers;

use App\Support\UserRedirector;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Foundation\Bus\DispatchesJobs;
use Illuminate\Foundation\Validation\ValidatesRequests;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller as BaseController;

class Controller extends BaseController
{
    use AuthorizesRequests, DispatchesJobs, ValidatesRequests;

    protected function tenantScopeId(?Request $request = null): int
    {
        $user = auth()->user();

        if (!$user) {
            return 0;
        }

        if (!UserRedirector::isSuperAdmin($user)) {
            return (int) ($user->tenant_id ?? 0);
        }

        $sessionTenantId = (int) session('superowner_tenant_scope_id', 0);

        if (!$request) {
            return max(0, $sessionTenantId);
        }

        return max(0, (int) $request->input('tenant_id', $request->query('tenant_id', $sessionTenantId)));
    }

    protected function tenantWriteId(?Request $request = null): int
    {
        $request = $request ?? request();
        $tenantId = $this->tenantScopeId($request);

        if ($tenantId <= 0) {
            $message = 'Este modulo requiere una tenant especifica o un tenant destino.';

            if ($request->expectsJson() || $request->is('api/*')) {
                abort(422, $message);
            }

            throw new HttpResponseException(
                redirect()->route('dashboard')->with('warning', $message)
            );
        }

        return $tenantId;
    }
}
