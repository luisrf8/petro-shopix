<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class HelpPreferenceController extends Controller
{
    public function show(Request $request): JsonResponse
    {
        $user = $request->user();

        return response()->json([
            'disable_global' => (bool) $user->help_disable_global,
            'disabled_routes' => is_array($user->help_disabled_routes) ? $user->help_disabled_routes : [],
        ]);
    }

    public function updateGlobal(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'disabled' => ['required', 'boolean'],
        ]);

        $user = $request->user();
        $user->help_disable_global = (bool) $validated['disabled'];
        $user->save();

        return response()->json([
            'message' => 'Preferencia global actualizada.',
            'disable_global' => (bool) $user->help_disable_global,
        ]);
    }

    public function updateRoute(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'route' => ['required', 'string', 'max:191'],
            'disabled' => ['required', 'boolean'],
        ]);

        $user = $request->user();
        $disabledRoutes = is_array($user->help_disabled_routes) ? $user->help_disabled_routes : [];

        if ((bool) $validated['disabled']) {
            $disabledRoutes[$validated['route']] = true;
        } else {
            unset($disabledRoutes[$validated['route']]);
        }

        $user->help_disabled_routes = $disabledRoutes;
        $user->save();

        return response()->json([
            'message' => 'Preferencia por modulo actualizada.',
            'disabled_routes' => $user->help_disabled_routes,
        ]);
    }
}
