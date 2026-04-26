<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        'http://192.168.1.119:8000',
        'http://192.168.1.119:8000/api/*',
        'api/*', // Excluir todas las rutas dentro del grupo 'api' de la verificación CSRF
        'login', // Excluir la ruta 'login' de la verificación CSRF
        'logout', // Excluir la ruta 'logout' de la verificación CSRF
    ];

    protected function tokensMatch($request)
    {
        $matches = parent::tokensMatch($request);

        if (!$matches && $request->is('tenant-update')) {
            $sessionToken = $request->session()->token();
            $inputToken = $request->input('_token');
            $headerToken = $request->header('X-CSRF-TOKEN');
            $xsrfHeader = $request->header('X-XSRF-TOKEN');

            Log::warning('CSRF mismatch detected for tenant-update.', [
                'method' => $request->method(),
                'full_url' => $request->fullUrl(),
                'session_id' => $request->session()->getId(),
                'has_session_cookie' => $request->cookies->has(config('session.cookie')),
                'has_xsrf_cookie' => $request->cookies->has('XSRF-TOKEN'),
                'session_cookie_name' => config('session.cookie'),
                'session_token_prefix' => $sessionToken ? Str::limit($sessionToken, 16, '...') : null,
                'input_token_prefix' => $inputToken ? Str::limit($inputToken, 16, '...') : null,
                'header_token_prefix' => $headerToken ? Str::limit($headerToken, 16, '...') : null,
                'xsrf_header_prefix' => $xsrfHeader ? Str::limit($xsrfHeader, 16, '...') : null,
                'referer' => $request->headers->get('referer'),
                'origin' => $request->headers->get('origin'),
                'host' => $request->getHost(),
            ]);
        }

        return $matches;
    }
}
