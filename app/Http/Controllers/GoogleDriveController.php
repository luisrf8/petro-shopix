<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Google\Client;
use Google\Service\Drive;
use Illuminate\Support\Facades\File;
use App\Support\ImageStorage;
use RuntimeException;

class GoogleDriveController extends Controller
{
    private $client;
    private $isConfigured = false;
    private $credentialsPath;

    public function __construct()
    {
        $this->client = new Client();

        $configuredPath = (string) config('services.image_storage.google_drive_credentials', storage_path('app/credentials.json'));
        $this->credentialsPath = $this->resolveCredentialsPath($configuredPath);

        if (File::exists($this->credentialsPath)) {
            $this->client->setAuthConfig($this->credentialsPath);
            $this->isConfigured = true;
        }

        $this->client->addScope(Drive::DRIVE);
        $this->client->setAccessType('offline');
        $this->client->setPrompt('select_account consent');
    }

    private function ensureConfigured()
    {
        if ($this->isConfigured) {
            return null;
        }

        return response()->json([
            'message' => 'Google Drive no está configurado. Falta el archivo de credenciales en: ' . $this->credentialsPath,
        ], 503);
    }

    private function resolveCredentialsPath(string $credentialsPath): string
    {
        $credentialsPath = trim($credentialsPath);
        if ($credentialsPath === '') {
            return storage_path('app/credentials.json');
        }

        if (preg_match('#^(?:[A-Za-z]:[\\/]|\\\\|/)#', $credentialsPath) === 1) {
            return $credentialsPath;
        }

        return base_path($credentialsPath);
    }

    public function uploadFile(Request $request)
    {
        if ($response = $this->ensureConfigured()) {
            return $response;
        }

        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
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
        if (!$this->isConfigured) {
            return redirect()->back()->with('error', 'Google Drive no está configurado.');
        }

        return redirect()->away($this->client->createAuthUrl());
    }

    public function handleGoogleCallback(Request $request)
    {
        if (!$this->isConfigured) {
            return redirect()->back()->with('error', 'Google Drive no está configurado.');
        }

        $this->client->authenticate($request->get('code'));
        $request->session()->put('google_drive_token', $this->client->getAccessToken());

        return redirect('/'); // Redirige a la página principal o donde desees
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
