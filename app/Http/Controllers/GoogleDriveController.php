<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Google\Client;
use Google\Service\Drive;
use Illuminate\Support\Facades\File;
use App\Support\ImageStorage;

class GoogleDriveController extends Controller
{
    private $client;
    private $isConfigured = false;

    public function __construct()
    {
        $this->client = new Client();

        $credentialsPath = storage_path('app/credentials.json');
        if (File::exists($credentialsPath)) {
            $this->client->setAuthConfig($credentialsPath);
            $this->isConfigured = true;
        }

        $this->client->addScope(Drive::DRIVE_FILE);
        $this->client->setAccessType('offline');
        $this->client->setPrompt('select_account consent');
    }

    private function ensureConfigured()
    {
        if ($this->isConfigured) {
            return null;
        }

        return response()->json([
            'message' => 'Google Drive no está configurado. Falta el archivo storage/app/credentials.json.',
        ], 503);
    }

    public function uploadFile(Request $request)
    {
        if ($response = $this->ensureConfigured()) {
            return $response;
        }

        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif|max:2048',
        ]);

        $service = new Drive($this->client);
        
        // Authenticate user
        if ($request->has('code')) {
            $this->client->authenticate($request->get('code'));
            $request->session()->put('google_drive_token', $this->client->getAccessToken());
        }

        if ($request->session()->has('google_drive_token')) {
            $this->client->setAccessToken($request->session()->get('google_drive_token'));
        }

        // Handle token refresh
        if ($this->client->isAccessTokenExpired()) {
            $refreshToken = $this->client->getRefreshToken();
            $this->client->fetchAccessTokenWithRefreshToken($refreshToken);
            $request->session()->put('google_drive_token', $this->client->getAccessToken());
        }

        $file = $request->file('image');
        $filePath = $file->getPathName();
        $fileName = $file->getClientOriginalName();
        $fileMetadata = new Drive\DriveFile([
            'name' => $fileName,
            'parents' => ['your-folder-id']
        ]);

        $content = file_get_contents($filePath);
        $file = $service->files->create($fileMetadata, [
            'data' => $content,
            'mimeType' => $file->getMimeType(),
            'uploadType' => 'multipart',
            'fields' => 'id'
        ]);

        $fileId = $file->id;
        $fileUrl = "https://drive.google.com/uc?export=view&id={$fileId}";

        // Guardar la URL del archivo en la base de datos o donde sea necesario

        return response()->json(['message' => 'File uploaded successfully', 'url' => $fileUrl], 201);
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
