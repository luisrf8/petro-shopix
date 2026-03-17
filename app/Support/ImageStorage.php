<?php

namespace App\Support;

use Google\Client;
use Google\Service\Drive;
use Google\Service\Drive\DriveFile;
use Google\Service\Drive\Permission;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use RuntimeException;

class ImageStorage
{
    public const GOOGLE_PREFIX = 'gdrive/';

    public static function provider(): string
    {
        return Str::lower((string) config('services.image_storage.provider', 'local'));
    }

    public static function usesGoogleDrive(): bool
    {
        return self::provider() === 'google_drive';
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
            return self::uploadToGoogleDrive($binary, $mimeType, $directory, $file->getClientOriginalName());
        }

        return $file->store(trim($directory, '/'), self::disk());
    }

    public static function storeBinary(string $binary, string $directory, string $extension = 'png', ?string $mimeType = null): string
    {
        $safeExtension = ltrim(Str::lower($extension), '.');
        $fileName = (string) Str::uuid() . '.' . ($safeExtension !== '' ? $safeExtension : 'png');

        if (self::usesGoogleDrive()) {
            $resolvedMimeType = $mimeType ?: self::mimeFromExtension($safeExtension);
            return self::uploadToGoogleDrive($binary, $resolvedMimeType, $directory, $fileName);
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

        if (self::isGooglePath($storedPath)) {
            $fileId = self::extractGoogleFileId($storedPath);
            if ($fileId === '') {
                return false;
            }

            try {
                $service = self::buildDriveService();
                $service->files->get($fileId, ['fields' => 'id']);
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

        if (self::isGooglePath($storedPath)) {
            $fileId = self::extractGoogleFileId($storedPath);
            if ($fileId === '') {
                return;
            }

            try {
                $service = self::buildDriveService();
                $service->files->delete($fileId);
            } catch (\Throwable $exception) {
                // Ignore delete failures to avoid blocking functional flows.
            }

            return;
        }

        Storage::disk(self::disk())->delete($storedPath);
    }

    public static function isGooglePath(?string $storedPath): bool
    {
        if (empty($storedPath)) {
            return false;
        }

        $cleanPath = ltrim((string) $storedPath, '/');

        return Str::startsWith($cleanPath, self::GOOGLE_PREFIX);
    }

    public static function extractGoogleFileId(?string $storedPath): string
    {
        if (!self::isGooglePath($storedPath)) {
            return '';
        }

        $cleanPath = ltrim((string) $storedPath, '/');

        return trim((string) Str::after($cleanPath, self::GOOGLE_PREFIX));
    }

    public static function url(?string $storedPath): ?string
    {
        if (empty($storedPath)) {
            return null;
        }

        $storedPath = trim((string) $storedPath);

        if (Str::startsWith($storedPath, ['http://', 'https://'])) {
            return $storedPath;
        }

        if (self::isGooglePath($storedPath)) {
            return asset('storage/' . ltrim($storedPath, '/'));
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

    public static function downloadGoogleFileById(string $fileId): array
    {
        $fileId = trim($fileId);
        if ($fileId === '') {
            throw new RuntimeException('Archivo inválido de Google Drive.');
        }

        $service = self::buildDriveService();
        $metadata = $service->files->get($fileId, ['fields' => 'id,name,mimeType']);
        $stream = $service->files->get($fileId, ['alt' => 'media']);
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
        $metadata = ['name' => $safeName];

        $folderId = trim((string) config('services.image_storage.google_drive_folder_id', ''));
        if ($folderId !== '') {
            $metadata['parents'] = [$folderId];
        }

        $created = $service->files->create(
            new DriveFile($metadata),
            [
                'data' => $binary,
                'mimeType' => $mimeType,
                'uploadType' => 'multipart',
                'fields' => 'id',
            ]
        );

        $fileId = (string) ($created->id ?? '');
        if ($fileId === '') {
            throw new RuntimeException('Google Drive no devolvió el ID del archivo cargado.');
        }

        try {
            $permission = new Permission([
                'type' => 'anyone',
                'role' => 'reader',
            ]);
            $service->permissions->create($fileId, $permission);
        } catch (\Throwable $exception) {
            // If this fails, the file was created anyway. Keep reference and let admins set permissions.
        }

        return self::GOOGLE_PREFIX . $fileId;
    }

    private static function buildDriveService(): Drive
    {
        $credentialsPath = (string) config('services.image_storage.google_drive_credentials', storage_path('app/credentials.json'));

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
}