<?php

namespace App\Support;

use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ActionReason
{
    public const DEFAULT_FIELD = 'action_reason';

    public static function require(Request $request, string $field = self::DEFAULT_FIELD, ?string $message = null): string
    {
        $reason = self::normalize($request->input($field, ''));

        if ($reason === '') {
            throw ValidationException::withMessages([
                $field => $message ?: 'Debes indicar el motivo de esta accion.',
            ]);
        }

        return $reason;
    }

    public static function normalize($value): string
    {
        $normalized = trim((string) $value);
        $normalized = preg_replace('/\s+/', ' ', $normalized) ?? $normalized;

        return Str::limit($normalized, 1000, '');
    }

    public static function log(string $module, string $action, string $reason, array $extra = []): void
    {
        AuditLogger::logEvent($module, $action, 'Accion negativa registrada con motivo.', null, array_merge($extra, [
            'reason' => self::normalize($reason),
        ]));
    }
}