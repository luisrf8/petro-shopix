<?php

namespace App\Http\Middleware;

use App\Support\UserRedirector;
use Closure;
use Illuminate\Http\Request;

class EnsureBackofficeAccess
{
    public function handle(Request $request, Closure $next)
    {
        $user = auth()->user();

        if ($user && !UserRedirector::canAccessBackoffice($user)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Este acceso está reservado para el panel administrativo.',
                    'redirect_to' => '/',
                ], 403);
            }

            return redirect('/');
        }

        return $next($request);
    }
}