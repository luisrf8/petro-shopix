<?php

namespace App\Services;

use Google\Client as GoogleClient;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class GeminiImageService
{
    public function generateImage(array $payload): array
    {
        $preferredModel = $this->normalizeModel(config('services.gemini.model', 'gemini-2.5-flash'));
        $projectNumber = trim((string) config('services.gemini.project_number', ''));
        $location = trim((string) config('services.gemini.location', 'us-central1'));
        $apiKey = trim((string) config('services.gemini.api_key', ''));

        $prompt = trim((string) ($payload['prompt'] ?? ''));
        $operation = trim((string) ($payload['image_operation'] ?? 'generate'));
        $referenceImageData = $payload['reference_image_data'] ?? null;
        $referenceImageMime = trim((string) ($payload['reference_image_mime'] ?? 'image/png')) ?: 'image/png';

        if ($prompt === '') {
            return [
                'success' => false,
                'status' => 422,
                'message' => 'El prompt para imagen está vacío.',
            ];
        }

        $triedModels = [];
        $lastFailure = null;
        $vertexFailure = null;
        $discoveredImageModels = [];

        if (Str::startsWith($preferredModel, 'imagen')) {
            $triedModels[] = $preferredModel;
            $vertexResult = $this->generateWithVertexImagen(
                model: $preferredModel,
                prompt: $prompt,
                projectNumber: $projectNumber,
                location: $location,
                sampleCount: (int) ($payload['sample_count'] ?? 1),
                aspectRatio: (string) ($payload['aspect_ratio'] ?? '1:1')
            );

            if ($vertexResult['success'] === true) {
                $vertexResult['tried_models'] = $triedModels;
                return $vertexResult;
            }

            $vertexFailure = $vertexResult;
        }

        $discoveredImageModels = $this->discoverGeminiImageCapableModels($apiKey);
        $fallbackModels = $this->resolveFallbackGeminiImageModels($preferredModel, $discoveredImageModels);

        foreach ($fallbackModels as $model) {
            $triedModels[] = $model;
            $fallback = $this->generateWithGeminiApi(
                model: $model,
                prompt: $prompt,
                apiKey: $apiKey,
                operation: $operation,
                referenceImageData: $referenceImageData,
                referenceImageMime: $referenceImageMime
            );

            if ($fallback['success'] === true) {
                $fallback['tried_models'] = array_values(array_unique($triedModels));
                return $fallback;
            }

            $lastFailure = $fallback;
        }

        if ($lastFailure === null && $vertexFailure !== null) {
            return [
                'success' => false,
                'status' => (int) ($vertexFailure['status'] ?? 500),
                'message' => $vertexFailure['message'] ?? 'No se pudo generar imagen con Vertex AI.',
                'error' => $vertexFailure['error'] ?? null,
                'tried_models' => array_values(array_unique($triedModels)),
            ];
        }

        if (($lastFailure['status'] ?? null) === 422 && $vertexFailure !== null) {
            return [
                'success' => false,
                'status' => (int) ($vertexFailure['status'] ?? 500),
                'message' => 'No se pudo usar Imagen en Vertex AI y los modelos fallback no devolvieron imagen.',
                'error' => $vertexFailure['error'] ?? ($lastFailure['error'] ?? null),
                'tried_models' => array_values(array_unique($triedModels)),
                'available_fallback_models' => $discoveredImageModels,
            ];
        }

        return [
            'success' => false,
            'status' => $lastFailure['status'] ?? 422,
            'message' => $lastFailure['message'] ?? 'No se pudo generar la imagen con los modelos configurados.',
            'error' => $lastFailure['error'] ?? null,
            'tried_models' => array_values(array_unique($triedModels)),
            'available_fallback_models' => $discoveredImageModels,
        ];
    }

    private function generateWithVertexImagen(
        string $model,
        string $prompt,
        string $projectNumber,
        string $location,
        int $sampleCount = 1,
        string $aspectRatio = '1:1'
    ): array {
        if ($projectNumber === '') {
            return [
                'success' => false,
                'status' => 500,
                'message' => 'Falta GEMINI_PROJECT_NUMBER para usar Imagen en Vertex AI.',
            ];
        }

        $accessTokenResult = $this->resolveGoogleAccessToken();
        if ($accessTokenResult['success'] !== true) {
            return [
                'success' => false,
                'status' => 500,
                'message' => 'No se pudo autenticar con Google Cloud para usar Imagen.',
                'error' => $accessTokenResult['error'] ?? 'Credenciales inválidas o no configuradas.',
            ];
        }

        $url = sprintf(
            'https://%s-aiplatform.googleapis.com/v1/projects/%s/locations/%s/publishers/google/models/%s:predict',
            $location,
            $projectNumber,
            $location,
            $model
        );

        $response = Http::timeout(120)
            ->withToken($accessTokenResult['access_token'])
            ->post($url, [
                'instances' => [
                    ['prompt' => $prompt],
                ],
                'parameters' => [
                    'sampleCount' => max(1, min($sampleCount, 4)),
                    'aspectRatio' => $aspectRatio,
                ],
            ]);

        if (!$response->successful()) {
            $errorMessage = $response->json('error.message') ?? $response->body();
            $status = $response->status();

            return [
                'success' => false,
                'status' => $status,
                'message' => $status === 403
                    ? 'El proyecto no tiene permisos para usar Imagen en Vertex AI.'
                    : 'Vertex AI rechazó la generación de imagen.',
                'error' => $errorMessage,
            ];
        }

        $prediction = data_get($response->json(), 'predictions.0', []);
        $base64 = (string) (data_get($prediction, 'bytesBase64Encoded') ?? '');

        if ($base64 === '') {
            return [
                'success' => false,
                'status' => 422,
                'message' => 'Vertex AI respondió sin imagen base64.',
                'error' => $response->json(),
            ];
        }

        return [
            'success' => true,
            'status' => 200,
            'provider' => 'vertex-imagen',
            'data' => $base64,
            'mime_type' => 'image/png',
        ];
    }

    private function generateWithGeminiApi(
        string $model,
        string $prompt,
        string $apiKey,
        string $operation,
        ?string $referenceImageData,
        string $referenceImageMime
    ): array {
        if ($apiKey === '') {
            return [
                'success' => false,
                'status' => 500,
                'message' => 'No está configurada GEMINI_API_KEY para fallback Gemini.',
            ];
        }

        $parts = [['text' => $prompt]];
        if (!empty($referenceImageData)) {
            $parts[] = [
                'inlineData' => [
                    'mimeType' => $referenceImageMime,
                    'data' => preg_replace('/^data:image\/[a-zA-Z0-9.+-]+;base64,/', '', (string) $referenceImageData),
                ],
            ];
        }

        $payload = [
            'contents' => [
                ['parts' => $parts],
            ],
            'generationConfig' => [
                'responseModalities' => ['IMAGE'],
            ],
        ];

        // For image edits from a user-provided picture, allow mixed response modalities.
        if ($operation === 'remove_background') {
            $payload['generationConfig']['responseModalities'] = ['TEXT', 'IMAGE'];
        }

        $normalizedModel = $this->normalizeModelForApiPath($model);
        $versions = ['v1beta', 'v1'];
        $response = null;

        foreach ($versions as $version) {
            $attempt = Http::timeout(90)->post(
                "https://generativelanguage.googleapis.com/{$version}/models/{$normalizedModel}:generateContent?key={$apiKey}",
                $payload
            );

            if ($attempt->successful()) {
                $response = $attempt;
                break;
            }

            $errorMessage = Str::lower((string) ($attempt->json('error.message') ?? $attempt->body()));
            $isNotFound = $attempt->status() === 404 || Str::contains($errorMessage, ['not found for api version', 'is not found']);
            if ($isNotFound) {
                $response = $attempt;
                continue;
            }

            $response = $attempt;
            break;
        }

        if (is_null($response)) {
            return [
                'success' => false,
                'status' => 500,
                'message' => 'No se pudo contactar Gemini API para generar imagen.',
                'error' => 'Sin respuesta HTTP de Gemini.',
            ];
        }

        if (!$response->successful()) {
            $errorMessage = $response->json('error.message') ?? $response->body();
            $status = $response->status();
            $normalizedError = Str::lower((string) $errorMessage);

            if (Str::contains($normalizedError, ['only supports text output', 'text output only'])) {
                return [
                    'success' => false,
                    'status' => 422,
                    'message' => "El modelo {$normalizedModel} no soporta salida de imagen.",
                    'error' => $errorMessage,
                ];
            }

            if (Str::contains($normalizedError, ['not found for api version', 'is not found'])) {
                return [
                    'success' => false,
                    'status' => 404,
                    'message' => "El modelo {$normalizedModel} no está disponible para generateContent en esta API.",
                    'error' => $errorMessage,
                ];
            }

            if (Str::contains($normalizedError, ['quota exceeded', 'rate limit', 'billing', 'limit: 0'])) {
                return [
                    'success' => false,
                    'status' => 429,
                    'message' => 'Tu clave de Gemini no tiene cuota disponible en este proyecto.',
                    'error' => $errorMessage,
                ];
            }

            if (Str::contains($normalizedError, ['api key not valid', 'permission denied', 'unauthenticated', 'forbidden'])) {
                return [
                    'success' => false,
                    'status' => 403,
                    'message' => 'La clave de Gemini no es válida o no tiene permisos para generar imágenes.',
                    'error' => $errorMessage,
                ];
            }

            return [
                'success' => false,
                'status' => $status,
                'message' => 'Gemini API rechazó la generación de imagen.',
                'error' => $errorMessage,
            ];
        }

        $candidates = collect(data_get($response->json(), 'candidates', []));
        $inlinePart = $candidates
            ->flatMap(function ($candidate) {
                return collect(data_get($candidate, 'content.parts', []));
            })
            ->first(function ($part) {
                return !empty(data_get($part, 'inlineData.data')) || !empty(data_get($part, 'inline_data.data'));
            });

        $base64 = data_get($inlinePart, 'inlineData.data') ?? data_get($inlinePart, 'inline_data.data');
        $mimeType = data_get($inlinePart, 'inlineData.mimeType') ?? data_get($inlinePart, 'inline_data.mime_type') ?? 'image/png';

        if (!empty($base64)) {
            return [
                'success' => true,
                'status' => 200,
                'provider' => 'gemini-api',
                'data' => $base64,
                'mime_type' => $mimeType,
            ];
        }

        return [
            'success' => false,
            'status' => 422,
            'message' => 'El modelo respondió, pero no devolvió imagen inline.',
            'error' => $response->json(),
        ];
    }

    private function resolveFallbackGeminiImageModels(string $preferredModel, array $discoveredModels = []): array
    {
        $configured = trim((string) config('services.gemini.image_fallback_models', ''));

        $fromConfig = collect(explode(',', $configured))
            ->map(fn ($value) => $this->normalizeModel($value))
            ->filter()
            ->values()
            ->all();

        $defaultModels = [
            'gemini-2.0-flash-preview-image-generation',
            'gemini-2.0-flash-exp-image-generation',
        ];

        $candidates = array_values(array_unique(array_filter(array_merge(
            [Str::startsWith($preferredModel, 'imagen') ? null : $preferredModel],
            $fromConfig,
            $discoveredModels,
            $defaultModels
        ))));

        return array_values(array_filter($candidates, function ($model) {
            return !in_array($model, ['gemini-2.5-flash', 'gemini-1.5-flash', 'gemini-1.5-flash-latest'], true);
        }));
    }

    private function discoverGeminiImageCapableModels(string $apiKey): array
    {
        if ($apiKey === '') {
            return [];
        }

        $versions = ['v1beta', 'v1'];
        $models = [];

        foreach ($versions as $version) {
            $response = Http::timeout(30)
                ->get("https://generativelanguage.googleapis.com/{$version}/models", [
                    'key' => $apiKey,
                ]);

            if (!$response->successful()) {
                continue;
            }

            $items = collect($response->json('models', []));
            $fromVersion = $items
                ->filter(function ($item) {
                    $supported = collect((array) data_get($item, 'supportedGenerationMethods', []))
                        ->map(fn ($value) => Str::lower((string) $value));
                    if (!$supported->contains('generatecontent')) {
                        return false;
                    }

                    $name = Str::lower((string) data_get($item, 'name', ''));
                    return Str::contains($name, ['image', 'imagen']);
                })
                ->map(function ($item) {
                    return $this->normalizeModel((string) data_get($item, 'name', ''));
                })
                ->filter()
                ->values()
                ->all();

            $models = array_values(array_unique(array_merge($models, $fromVersion)));
        }

        return $models;
    }

    private function normalizeModelForApiPath(string $model): string
    {
        $normalized = $this->normalizeModel($model);
        return Str::startsWith($normalized, 'models/') ? Str::after($normalized, 'models/') : $normalized;
    }

    private function resolveGoogleAccessToken(): array
    {
        $credentialsPath = trim((string) config('services.gemini.credentials_path', ''));
        if ($credentialsPath === '') {
            $credentialsPath = trim((string) env('GOOGLE_APPLICATION_CREDENTIALS', ''));
        }

        if ($credentialsPath === '' || !is_file($credentialsPath)) {
            return [
                'success' => false,
                'error' => 'No se encontró el archivo de credenciales de Google Cloud (JSON).',
            ];
        }

        try {
            $client = new GoogleClient();
            $client->setAuthConfig($credentialsPath);
            $client->setScopes(['https://www.googleapis.com/auth/cloud-platform']);
            $tokenData = $client->fetchAccessTokenWithAssertion();
            $accessToken = $tokenData['access_token'] ?? null;

            if (!$accessToken) {
                return [
                    'success' => false,
                    'error' => $tokenData['error_description'] ?? 'No se pudo obtener access_token de Google.',
                ];
            }

            return [
                'success' => true,
                'access_token' => $accessToken,
            ];
        } catch (\Throwable $exception) {
            return [
                'success' => false,
                'error' => $exception->getMessage(),
            ];
        }
    }

    private function normalizeModel(?string $model): string
    {
        $value = Str::lower(trim((string) $model));

        if ($value === '') {
            return 'gemini-2.5-flash';
        }

        if (in_array($value, ['gemini-1.5-flash-latest', 'gemini-1.5-flash'], true)) {
            return 'gemini-2.5-flash';
        }

        if (Str::startsWith($value, 'models/')) {
            return Str::after($value, 'models/');
        }

        return $value;
    }
}
