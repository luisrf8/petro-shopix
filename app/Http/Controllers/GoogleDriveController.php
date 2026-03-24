<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Google\Client;
use Google\Service\Drive;
use App\Support\ImageStorage;
use RuntimeException;

class GoogleDriveController extends Controller
{
    private $client;
    private $isConfigured = false;
    private $configurationError = '';

    public function __construct()
    {
        $this->client = new Client();
        $this->client->addScope(Drive::DRIVE);
        $this->client->setAccessType('offline');
        $this->client->setIncludeGrantedScopes(true);
        $this->client->setPrompt('select_account consent');

        $clientId = trim((string) config('services.image_storage.google_drive_oauth_client_id', ''));
        $clientSecret = trim((string) config('services.image_storage.google_drive_oauth_client_secret', ''));

        if ($clientId === '' || $clientSecret === '') {
            $this->configurationError = 'Faltan GOOGLE_DRIVE_OAUTH_CLIENT_ID o GOOGLE_DRIVE_OAUTH_CLIENT_SECRET en el archivo .env.';
            return;
        }

        $this->client->setClientId($clientId);
        $this->client->setClientSecret($clientSecret);
        $this->client->setRedirectUri(route('google-drive.callback'));
        $this->isConfigured = true;
    }

    private function ensureConfigured()
    {
        if ($this->isConfigured) {
            return null;
        }

        return response()->json([
            'message' => $this->configurationError !== ''
                ? $this->configurationError
                : 'Google Drive OAuth no esta configurado.',
        ], 503);
    }

    public function uploadFile(Request $request)
    {
        if ($response = $this->ensureConfigured()) {
            return $response;
        }

        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        try {
            $storedPath = ImageStorage::storeUploadedFile($request->file('image'), 'google_drive_uploads');

            return response()->json([
                'message' => 'File uploaded successfully',
                'path' => $storedPath,
                'url' => ImageStorage::url($storedPath),
            ], 201);
        } catch (RuntimeException $exception) {
            return response()->json([
                'message' => $exception->getMessage(),
            ], 422);
        }
    }

    public function redirectToGoogle()
    {
        if ($response = $this->ensureConfigured()) {
            return $response;
        }

        return redirect()->away($this->client->createAuthUrl());
    }

    public function handleGoogleCallback(Request $request)
    {
        if ($response = $this->ensureConfigured()) {
            return $response;
        }

        $oauthError = trim((string) $request->query('error', ''));
        if ($oauthError !== '') {
            $errorDescription = trim((string) $request->query('error_description', ''));

            return response()->json([
                'message' => 'Google OAuth devolvio un error: ' . $oauthError,
                'error_description' => $errorDescription !== '' ? $errorDescription : null,
                'connect_url' => route('google-drive.connect'),
            ], 422);
        }

        $code = trim((string) $request->query('code', ''));
        if ($code === '') {
            return response()->json([
                'message' => 'No se recibio el parametro code desde Google OAuth. No abras callback manualmente; inicia desde connect_url.',
                'connect_url' => route('google-drive.connect'),
            ], 422);
        }

        $token = $this->client->fetchAccessTokenWithAuthCode($code);
        if (is_array($token) && isset($token['error'])) {
            $error = trim((string) ($token['error_description'] ?? $token['error'] ?? 'Error desconocido en OAuth.'));

            return response()->json([
                'message' => 'No se pudo obtener el access token de Google: ' . $error,
            ], 422);
        }

        $refreshToken = trim((string) ($token['refresh_token'] ?? ''));
        if ($refreshToken === '') {
            $existingRefreshToken = trim((string) config('services.image_storage.google_drive_oauth_refresh_token', ''));
            if ($existingRefreshToken !== '') {
                $refreshToken = $existingRefreshToken;
            }
        }

        if ($refreshToken === '') {
            return response()->json([
                'message' => 'Google no devolvio refresh_token. Revoca acceso de la app en tu cuenta Google y vuelve a autorizar para forzar consentimiento offline.',
            ], 422);
        }

        $request->session()->put('google_drive_oauth_refresh_token', $refreshToken);

        return response()->json([
            'message' => 'OAuth completado. Guarda este valor en GOOGLE_DRIVE_OAUTH_REFRESH_TOKEN en tu .env.',
            'refresh_token' => $refreshToken,
            'env_example' => 'GOOGLE_DRIVE_OAUTH_REFRESH_TOKEN=' . $refreshToken,
        ]);
    }

    public function oauthStatus()
    {
        $oauthClientId = trim((string) config('services.image_storage.google_drive_oauth_client_id', ''));
        $oauthClientSecret = trim((string) config('services.image_storage.google_drive_oauth_client_secret', ''));
        $oauthRefreshToken = trim((string) config('services.image_storage.google_drive_oauth_refresh_token', ''));

        return response()->json([
            'oauth_client_id_configured' => $oauthClientId !== '',
            'oauth_client_secret_configured' => $oauthClientSecret !== '',
            'oauth_refresh_token_configured' => $oauthRefreshToken !== '',
            'connect_url' => route('google-drive.connect'),
            'callback_url' => route('google-drive.callback'),
        ]);
    }

    public function streamImage(string $fileId)
    {
        try {
            $file = ImageStorage::downloadGoogleFileById(trim($fileId));

            return response($file['content'], 200, [
                'Content-Type' => $file['mime_type'],
                'Cache-Control' => 'public, max-age=2592000',
            ]);
        } catch (\Throwable $exception) {
            abort(404);
        }
    }

}
