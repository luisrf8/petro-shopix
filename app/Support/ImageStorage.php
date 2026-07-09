<?php

namespace App\Support;

use Google\Client;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use Google\Service\Drive\Permission;
use Google\Service\Exception as GoogleServiceException;
use GuzzleHttp\Client as GuzzleClient;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class ImageStorage
{
    public const GOOGLE_PREFIX = 'gdrive/';
    private const GOOGLE_FILE_ID_PATTERN = '/^[a-zA-Z0-9_-]{10,}$/';

    public static function provider(): string
    {
        $provider = Str::lower(trim((string) config('services.image_storage.provider', 'local')));

        if (in_array($provider, ['google', 'google-drive', 'gdrive'], true)) {
            $provider = 'google_drive';
        }

        if ($provider === 'google_drive' && !self::hasGoogleDriveConfiguration()) {
            return 'local';
        }

        if (($provider === '' || $provider === 'local') && self::hasGoogleDriveConfiguration()) {
            return 'google_drive';
        }

        return $provider !== '' ? $provider : 'local';
    }

    public static function usesGoogleDrive(): bool
    {
        return self::provider() === 'google_drive' && self::hasGoogleDriveConfiguration();
    }

    public static function disk(): string
    {
        return (string) config('services.image_storage.local_disk', 'public');
    }

    public static function storeUploadedFile(UploadedFile $file, string $directory): string
    {
        if (self::usesGoogleDrive()) {
            $binary = file_get_contents($file->getRealPath());
            if ($binary === false) {
                throw new RuntimeException('No se pudo leer el archivo de imagen para subir a Google Drive.');
            }

            $mimeType = (string) ($file->getClientMimeType() ?: $file->getMimeType() ?: 'application/octet-stream');

            try {
                return self::uploadToGoogleDrive($binary, $mimeType, $directory, $file->getClientOriginalName());
            } catch (\Throwable $exception) {
                if (!self::shouldFallbackToLocalOnDriveError($exception)) {
                    throw $exception;
                }

                self::logGoogleDriveFallback($exception, $directory, $file->getClientOriginalName());

                return $file->store(trim($directory, '/'), self::disk());
            }
        }

        return $file->store(trim($directory, '/'), self::disk());
    }

    public static function storeUploadedImageAsWebp(UploadedFile $file, string $directory, int $quality = 82): string
    {
        $mimeType = Str::lower((string) ($file->getClientMimeType() ?: $file->getMimeType() ?: ''));
        $rasterMimeTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp', 'image/gif'];

        if (!in_array($mimeType, $rasterMimeTypes, true)) {
            return self::storeUploadedFile($file, $directory);
        }

        if (!function_exists('imagecreatefromstring') || !function_exists('imagewebp')) {
            return self::storeUploadedFile($file, $directory);
        }

        $binary = file_get_contents($file->getRealPath());
        if ($binary === false || $binary === '') {
            return self::storeUploadedFile($file, $directory);
        }

        $resource = @imagecreatefromstring($binary);
        if ($resource === false) {
            return self::storeUploadedFile($file, $directory);
        }

        if (function_exists('imagepalettetotruecolor')) {
            imagepalettetotruecolor($resource);
        }
        imagealphablending($resource, true);
        imagesavealpha($resource, true);

        ob_start();
        $encoded = @imagewebp($resource, null, max(0, min(100, $quality)));
        $webpBinary = ob_get_clean();
        imagedestroy($resource);

        if ($encoded !== true || !is_string($webpBinary) || $webpBinary === '') {
            return self::storeUploadedFile($file, $directory);
        }

        return self::storeBinary($webpBinary, $directory, 'webp', 'image/webp');
    }

    public static function storeBinary(string $binary, string $directory, string $extension = 'png', ?string $mimeType = null): string
    {
        $safeExtension = ltrim(Str::lower($extension), '.');
        $fileName = (string) Str::uuid() . '.' . ($safeExtension !== '' ? $safeExtension : 'png');

        if (self::usesGoogleDrive()) {
            $resolvedMimeType = $mimeType ?: self::mimeFromExtension($safeExtension);

            try {
                return self::uploadToGoogleDrive($binary, $resolvedMimeType, $directory, $fileName);
            } catch (\Throwable $exception) {
                if (!self::shouldFallbackToLocalOnDriveError($exception)) {
                    throw $exception;
                }

                self::logGoogleDriveFallback($exception, $directory, $fileName);
            }
        }

        $path = trim($directory, '/') . '/' . $fileName;
        Storage::disk(self::disk())->put($path, $binary);

        return $path;
    }

    public static function exists(?string $storedPath): bool
    {
        if (empty($storedPath)) {
            return false;
        }

        $fileId = self::extractGoogleFileId($storedPath);
        if ($fileId !== '') {
            if (!self::hasGoogleDriveConfiguration()) {
                return false;
            }

            try {
                $service = self::buildDriveService();
                $service->files->get($fileId, [
                    'fields' => 'id',
                    'supportsAllDrives' => true,
                ]);
                return true;
            } catch (\Throwable $exception) {
                return false;
            }
        }

        return Storage::disk(self::disk())->exists($storedPath);
    }

    public static function delete(?string $storedPath): void
    {
        if (empty($storedPath)) {
            return;
        }

        $fileId = self::extractGoogleFileId($storedPath);
        if ($fileId !== '') {
            if (!self::hasGoogleDriveConfiguration()) {
                return;
            }

            try {
                $service = self::buildDriveService();
                $service->files->delete($fileId, [
                    'supportsAllDrives' => true,
                ]);
            } catch (\Throwable $exception) {
                // Ignore delete failures to avoid blocking functional flows.
            }

            return;
        }

        Storage::disk(self::disk())->delete($storedPath);
    }

    public static function isGooglePath(?string $storedPath): bool
    {
        return self::extractGoogleFileId($storedPath) !== '';
    }

    public static function extractGoogleFileId(?string $storedPath): string
    {
        if (empty($storedPath)) {
            return '';
        }

        $cleanPath = trim((string) $storedPath);
        if ($cleanPath === '') {
            return '';
        }

        $cleanPath = ltrim($cleanPath, '/');

        if (Str::startsWith($cleanPath, self::GOOGLE_PREFIX)) {
            return trim((string) Str::after($cleanPath, self::GOOGLE_PREFIX));
        }

        if (Str::startsWith($cleanPath, 'storage/' . self::GOOGLE_PREFIX)) {
            return trim((string) Str::after($cleanPath, 'storage/' . self::GOOGLE_PREFIX));
        }

        $googleIdFromUrl = self::extractGoogleFileIdFromUrl($cleanPath);
        if ($googleIdFromUrl !== '') {
            return $googleIdFromUrl;
        }

        return preg_match(self::GOOGLE_FILE_ID_PATTERN, $cleanPath) === 1
            ? $cleanPath
            : '';
    }

    private static function extractGoogleFileIdFromUrl(string $value): string
    {
        $decodedValue = urldecode(trim($value));
        if ($decodedValue === '') {
            return '';
        }

        if (preg_match('#(?:^|/)storage/gdrive/([^/?]+)#', $decodedValue, $matches) === 1) {
            return trim((string) ($matches[1] ?? ''));
        }

        if (preg_match('#drive\.google\.com/file/d/([^/?]+)#', $decodedValue, $matches) === 1) {
            return trim((string) ($matches[1] ?? ''));
        }

        $query = parse_url($decodedValue, PHP_URL_QUERY);
        if (is_string($query) && $query !== '') {
            parse_str($query, $queryParams);
            $id = trim((string) ($queryParams['id'] ?? ''));
            if ($id !== '') {
                return $id;
            }
        }

        return '';
    }

    private static function hasGoogleDriveConfiguration(): bool
    {
        return self::hasGoogleDriveOAuthConfiguration() || self::hasGoogleDriveServiceAccountConfiguration();
    }

    private static function hasGoogleDriveServiceAccountConfiguration(): bool
    {
        $credentialsPath = self::resolveCredentialsPath((string) config('services.image_storage.google_drive_credentials', ''));

        return $credentialsPath !== '' && is_file($credentialsPath);
    }

    private static function hasGoogleDriveOAuthConfiguration(): bool
    {
        return self::googleDriveOAuthClientId() !== ''
            && self::googleDriveOAuthClientSecret() !== ''
            && self::googleDriveOAuthRefreshToken() !== '';
    }

    private static function googleDriveOAuthClientId(): string
    {
        return trim((string) config('services.image_storage.google_drive_oauth_client_id', ''));
    }

    private static function googleDriveOAuthClientSecret(): string
    {
        return trim((string) config('services.image_storage.google_drive_oauth_client_secret', ''));
    }

    private static function googleDriveOAuthRefreshToken(): string
    {
        return trim((string) config('services.image_storage.google_drive_oauth_refresh_token', ''));
    }

    private static function resolveCredentialsPath(string $credentialsPath): string
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

    private static function googleProxyUrl(string $fileId): string
    {
        try {
            return route('storage.gdrive.proxy', ['fileId' => $fileId]);
        } catch (\Throwable $exception) {
            return asset('storage/' . self::GOOGLE_PREFIX . ltrim($fileId, '/'));
        }
    }

    public static function url(?string $storedPath): ?string
    {
        if (empty($storedPath)) {
            return null;
        }

        $storedPath = trim((string) $storedPath);

        if (Str::startsWith($storedPath, ['http://', 'https://'])) {
            $googleIdFromUrl = self::extractGoogleFileIdFromUrl($storedPath);
            if ($googleIdFromUrl !== '') {
                return self::googleProxyUrl($googleIdFromUrl);
            }

            return $storedPath;
        }

        $googleId = self::extractGoogleFileId($storedPath);
        if ($googleId !== '') {
            return self::googleProxyUrl($googleId);
        }

        if (Str::startsWith($storedPath, '/storage/')) {
            return asset(ltrim($storedPath, '/'));
        }

        if (Str::startsWith($storedPath, 'storage/')) {
            return asset($storedPath);
        }

        try {
            return Storage::disk(self::disk())->url($storedPath);
        } catch (\Throwable $exception) {
            return asset('storage/' . ltrim($storedPath, '/'));
        }
    }

    public static function downloadGoogleFileById(string $fileId, ?int $timeoutSeconds = null): array
    {
        $fileId = self::extractGoogleFileId(trim($fileId));
        if ($fileId === '') {
            throw new RuntimeException('Archivo inválido de Google Drive.');
        }

        $service = self::buildDriveService();
        if (is_int($timeoutSeconds) && $timeoutSeconds > 0) {
            $safeTimeout = max(2, min(20, $timeoutSeconds));
            $service->getClient()->setHttpClient(new GuzzleClient([
                'timeout' => $safeTimeout,
                'connect_timeout' => min(5, $safeTimeout),
            ]));
        }

        $metadata = $service->files->get($fileId, [
            'fields' => 'id,name,mimeType',
            'supportsAllDrives' => true,
        ]);
        $stream = $service->files->get($fileId, [
            'alt' => 'media',
            'supportsAllDrives' => true,
        ]);
        $content = method_exists($stream, 'getBody') ? (string) $stream->getBody() : (string) $stream;

        return [
            'name' => (string) ($metadata->name ?? ('image-' . $fileId)),
            'mime_type' => (string) ($metadata->mimeType ?? 'application/octet-stream'),
            'content' => $content,
        ];
    }

    private static function uploadToGoogleDrive(string $binary, string $mimeType, string $directory, string $originalName): string
    {
        if ($binary === '') {
            throw new RuntimeException('No se recibió contenido para subir a Google Drive.');
        }

        $service = self::buildDriveService();
        $namePrefix = trim($directory, '/');
        $baseName = trim(basename($originalName));
        $safeName = trim(($namePrefix !== '' ? $namePrefix . '-' : '') . $baseName);

        $folderId = trim((string) config('services.image_storage.google_drive_folder_id', env('GOOGLE_DRIVE_FOLDER_ID', '')));
        if ($folderId === '') {
            throw new RuntimeException('Falta GOOGLE_DRIVE_FOLDER_ID. Define el ID de la carpeta destino en Google Drive.');
        }
        $metadata = [
            'name' => $safeName,
            'parents' => [$folderId],
        ];

        try {
            $created = $service->files->create(
                new DriveFile($metadata),
                [
                    'data' => $binary,
                    'mimeType' => $mimeType,
                    'uploadType' => 'multipart',
                    'fields' => 'id',
                    'supportsAllDrives' => true,
                ]
            );
        } catch (GoogleServiceException $exception) {
            if (self::extractDriveErrorReason($exception) === 'storageQuotaExceeded') {
                throw new RuntimeException(
                    'Google Drive devolvio storageQuotaExceeded. Con service account debes usar una carpeta en Shared Drive con permisos para la cuenta de servicio. Para cuentas personales, usa OAuth de usuario.',
                    0,
                    $exception
                );
            }

            throw $exception;
        }

        $fileId = (string) ($created->id ?? '');
        if ($fileId === '') {
            throw new RuntimeException('Google Drive no devolvió el ID del archivo cargado.');
        }

        try {
            $permission = new Permission([
                'type' => 'anyone',
                'role' => 'reader',
            ]);
            $service->permissions->create($fileId, $permission, [
                'supportsAllDrives' => true,
            ]);
        } catch (\Throwable $exception) {
            // If this fails, the file was created anyway. Keep reference and let admins set permissions.
        }

        return self::GOOGLE_PREFIX . $fileId;
    }

    private static function buildDriveService(): Drive
    {
        if (self::hasGoogleDriveOAuthConfiguration()) {
            $client = new Client();
            $client->setClientId(self::googleDriveOAuthClientId());
            $client->setClientSecret(self::googleDriveOAuthClientSecret());
            $client->addScope(Drive::DRIVE);
            $client->setAccessType('offline');

            $tokenData = $client->fetchAccessTokenWithRefreshToken(self::googleDriveOAuthRefreshToken());
            if (is_array($tokenData) && isset($tokenData['error'])) {
                $error = trim((string) ($tokenData['error_description'] ?? $tokenData['error'] ?? 'OAuth refresh token inválido.'));
                throw new RuntimeException('No se pudo autenticar en Google Drive por OAuth: ' . $error);
            }

            return new Drive($client);
        }

        $credentialsPath = self::resolveCredentialsPath((string) config('services.image_storage.google_drive_credentials', storage_path('app/credentials.json')));

        if (!is_file($credentialsPath)) {
            throw new RuntimeException('No existe el archivo de credenciales de Google Drive: ' . $credentialsPath);
        }

        $client = new Client();
        $client->setAuthConfig($credentialsPath);
        $client->addScope(Drive::DRIVE);
        $client->setAccessType('offline');
        $client->setPrompt('select_account consent');

        return new Drive($client);
    }

    private static function mimeFromExtension(string $extension): string
    {
        return match (Str::lower($extension)) {
            'jpg', 'jpeg' => 'image/jpeg',
            'gif' => 'image/gif',
            'webp' => 'image/webp',
            'svg' => 'image/svg+xml',
            default => 'image/png',
        };
    }

    private static function shouldFallbackToLocalOnDriveError(\Throwable $exception): bool
    {
        if ((bool) config('services.image_storage.google_drive_fallback_to_local_on_error', true) === false) {
            return false;
        }

        if ($exception instanceof GoogleServiceException) {
            return true;
        }

        return $exception instanceof RuntimeException
            && Str::contains(Str::lower($exception->getMessage()), ['google drive', 'credential', 'quota']);
    }

    private static function logGoogleDriveFallback(\Throwable $exception, string $directory, string $fileName): void
    {
        Log::warning('Google Drive upload failed. Falling back to local storage.', [
            'directory' => trim($directory, '/'),
            'file_name' => $fileName,
            'reason' => self::extractDriveErrorReason($exception),
            'message' => $exception->getMessage(),
        ]);
    }

    private static function extractDriveErrorReason(\Throwable $exception): ?string
    {
        if (!$exception instanceof GoogleServiceException) {
            return null;
        }

        $errors = $exception->getErrors();
        if (!is_array($errors)) {
            return null;
        }

        foreach ($errors as $error) {
            $reason = trim((string) ($error['reason'] ?? ''));
            if ($reason !== '') {
                return $reason;
            }
        }

        return null;
    }
}