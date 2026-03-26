<?php

namespace App\Http\Middleware;

use App\Models\TenantPlanPayment;
use App\Providers\RouteServiceProvider;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class RedirectIfAuthenticated
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @param  string|null  ...$guards
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next, ...$guards)
    {
        $guards = empty($guards) ? [null] : $guards;

        foreach ($guards as $guard) {
            if (Auth::guard($guard)->check()) {
                $user = Auth::guard($guard)->user();

                if ($user && (int) ($user->role_id ?? 0) !== 4) {
                    $latestPaid = TenantPlanPayment::with('plan')
                        ->where('tenant_id', (int) ($user->tenant_id ?? 0))
                        ->where('status', 'paid')
                        ->orderByDesc('paid_at')
                        ->orderByDesc('id')
                        ->first();

                    $planName = Str::lower(Str::ascii((string) ($latestPaid?->plan?->name ?? '')));
                    $isFreePlan = (float) ($latestPaid?->plan?->price ?? 0) <= 0;
                    $isBasicPlan = Str::contains($planName, ['basico', 'basic']);

                    if ($isFreePlan || $isBasicPlan) {
                        return redirect('/products');
                    }
                }

                return redirect(RouteServiceProvider::HOME);
            }
        }

        return $next($request);
    }
}
