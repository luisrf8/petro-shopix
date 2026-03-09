<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class EnsureRoleName
{
    public function handle(Request $request, Closure $next, ...$allowedRoleNames)
    {
        $user = auth()->user();

        if (!$user || !$user->role) {
            abort(403);
        }

        $roleName = strtolower((string) $user->role->name);
        $allowedNames = collect($allowedRoleNames)
            ->filter(fn($value) => !is_numeric($value))
            ->map(fn($name) => strtolower((string) $name))
            ->all();

        $allowedIds = collect($allowedRoleNames)
            ->filter(fn($value) => is_numeric($value))
            ->map(fn($id) => (int) $id)
            ->all();

        $nameAllowed = in_array($roleName, $allowedNames, true);
        $idAllowed = in_array((int) $user->role_id, $allowedIds, true);

        if (!$nameAllowed && !$idAllowed) {
            abort(403);
        }

        return $next($request);
    }
}
