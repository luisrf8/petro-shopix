<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class PushSubscriptionController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        $user = $this->resolveUser();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado.',
            ], 401);
        }

        $validated = $request->validate([
            'subscription' => ['required', 'array'],
            'subscription.endpoint' => ['required', 'string', 'max:500'],
            'subscription.keys' => ['nullable', 'array'],
            'subscription.keys.p256dh' => ['nullable', 'string'],
            'subscription.keys.auth' => ['nullable', 'string'],
            'subscription.contentEncoding' => ['nullable', 'string', 'in:aesgcm,aes128gcm'],
        ]);

        $subscription = $validated['subscription'];

        $user->updatePushSubscription(
            (string) $subscription['endpoint'],
            data_get($subscription, 'keys.p256dh'),
            data_get($subscription, 'keys.auth'),
            data_get($subscription, 'contentEncoding')
        );

        return response()->json([
            'success' => true,
        ]);
    }

    public function destroy(Request $request): JsonResponse
    {
        $user = $this->resolveUser();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado.',
            ], 401);
        }

        $validated = $request->validate([
            'endpoint' => ['required', 'string', 'max:500'],
        ]);

        $user->deletePushSubscription((string) $validated['endpoint']);

        return response()->json([
            'success' => true,
        ]);
    }

    private function resolveUser(): mixed
    {
        if (auth()->check()) {
            return auth()->user();
        }

        try {
            return JWTAuth::parseToken()->authenticate();
        } catch (\Throwable) {
            return null;
        }
    }
}