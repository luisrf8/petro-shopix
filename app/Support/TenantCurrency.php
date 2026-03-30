<?php

namespace App\Support;

use App\Models\DollarRate;
use App\Models\EuroRate;
use App\Models\Tenant;
use Illuminate\Support\Str;

class TenantCurrency
{
    public static function resolveBaseCurrencyCode(?Tenant $tenant): string
    {
        $code = Str::upper(trim((string) ($tenant?->base_currency ?? 'USD')));
        return in_array($code, ['USD', 'EUR'], true) ? $code : 'USD';
    }

    public static function resolveCurrencySymbol(string $currencyCode): string
    {
        return Str::upper(trim($currencyCode)) === 'EUR' ? '€' : '$';
    }

    public static function normalizeCurrencyCode(?string $currencyCode): string
    {
        $code = Str::upper(trim((string) $currencyCode));

        if (in_array($code, ['BS', 'VES', 'VED', 'VEF', 'BSD'], true)) {
            return 'BS';
        }

        if (in_array($code, ['USD', 'EUR'], true)) {
            return $code;
        }

        return 'USD';
    }

    public static function resolveRateToBs(int $tenantId, string $currencyCode): float
    {
        $code = self::normalizeCurrencyCode($currencyCode);

        if ($code === 'BS') {
            return 1.0;
        }

        if ($code === 'EUR') {
            return (float) (EuroRate::query()->where('tenant_id', $tenantId)->latest('created_at')->value('rate') ?: 0);
        }

        return (float) (DollarRate::query()->where('tenant_id', $tenantId)->latest('created_at')->value('rate') ?: 0);
    }

    public static function convertAmount(float $amount, string $fromCurrencyCode, string $toCurrencyCode, int $tenantId): float
    {
        $from = self::normalizeCurrencyCode($fromCurrencyCode);
        $to = self::normalizeCurrencyCode($toCurrencyCode);

        if ($from === $to) {
            return round($amount, 4);
        }

        $amountBs = $from === 'BS'
            ? $amount
            : $amount * self::resolveRateToBs($tenantId, $from);

        if ($to === 'BS') {
            return round($amountBs, 4);
        }

        $toRate = self::resolveRateToBs($tenantId, $to);
        if ($toRate <= 0) {
            return round($amount, 4);
        }

        return round($amountBs / $toRate, 4);
    }
}
