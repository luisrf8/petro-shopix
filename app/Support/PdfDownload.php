<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Str;

class PdfDownload
{
    public static function resolveDisposition(Request $request, string $requested = 'attachment'): string
    {
        $safeDisposition = Str::lower(trim($requested)) === 'inline' ? 'inline' : 'attachment';
        if ($safeDisposition === 'inline') {
            return 'inline';
        }

        return self::shouldForceInlineForApple($request) ? 'inline' : 'attachment';
    }

    public static function buildDispositionHeader(Request $request, string $fileName, string $requested = 'attachment'): string
    {
        $disposition = self::resolveDisposition($request, $requested);

        return $disposition . '; filename="' . str_replace('"', '', $fileName) . '"';
    }

    public static function shouldForceInlineForApple(Request $request): bool
    {
        $userAgent = Str::lower((string) $request->userAgent());

        return Str::contains($userAgent, ['iphone', 'ipad', 'ipod']);
    }
}