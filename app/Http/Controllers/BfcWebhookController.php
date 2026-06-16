<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class BfcWebhookController extends Controller
{
    public function store(Request $request): JsonResponse
    {
        if (!$request->isJson()) {
            return response()->json([
                'status' => 'error',
                'message' => 'Content-Type must be application/json',
            ], 415);
        }

        $payload = $request->json()->all();
        if (!is_array($payload) || $payload === []) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid or empty JSON payload',
            ], 400);
        }

        if (!$this->isSignatureValid($request)) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid signature',
            ], 401);
        }

        $transactionRef = (string) (
            $payload['transaction_id']
            ?? $payload['reference']
            ?? $payload['id']
            ?? ''
        );

        Log::info('BFC webhook received', [
            'path' => $request->path(),
            'ip' => $request->ip(),
            'api_key' => $request->header('api-key'),
            'transaction_ref' => $transactionRef,
            'payload' => $payload,
        ]);

        return response()->json([
            'status' => 'ok',
            'message' => 'Notification received',
            'transaction_ref' => $transactionRef,
            'received_at' => now()->toIso8601String(),
        ], 200);
    }

    private function isSignatureValid(Request $request): bool
    {
        $mustValidate = filter_var(env('BFC_WEBHOOK_VALIDATE_SIGNATURE', false), FILTER_VALIDATE_BOOL);
        if (!$mustValidate) {
            return true;
        }

        $expectedApiKey = (string) env('BFC_API_KEY', '');
        $expectedSecret = (string) env('BFC_API_KEY_SECRET', '');

        $apiKey = (string) $request->header('api-key', '');
        $signature = (string) ($request->header('secret', $request->header('x-signature', '')));

        if ($expectedApiKey === '' || $expectedSecret === '' || $apiKey === '' || $signature === '') {
            return false;
        }

        if (!hash_equals($expectedApiKey, $apiKey)) {
            return false;
        }

        $body = (string) $request->getContent();
        $computed = hash_hmac('sha384', $body, $expectedSecret);

        return hash_equals($computed, $signature);
    }
}
