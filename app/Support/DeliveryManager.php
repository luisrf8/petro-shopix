<?php

namespace App\Support;

use App\Models\Tenant;
use RuntimeException;

class DeliveryManager
{
    public static function settings(Tenant $tenant): array
    {
        return [
            'enabled' => (bool) ($tenant->delivery_enabled ?? false),
            'mode' => self::normalizeMode($tenant->delivery_fee_mode ?? null),
            'fixed_fee' => round((float) ($tenant->delivery_fixed_fee ?? 0), 2),
            'fee_per_km' => round((float) ($tenant->delivery_fee_per_km ?? 0), 2),
            'notifications_enabled' => (bool) ($tenant->delivery_notifications_enabled ?? true),
        ];
    }

    public static function calculate(Tenant $tenant, string $deliveryType, float $itemsSubtotal, ?float $distanceKm = null): array
    {
        $settings = self::settings($tenant);
        $normalizedType = self::normalizeType($deliveryType);
        $normalizedDistance = is_null($distanceKm) ? null : round(max(0, (float) $distanceKm), 2);

        if ($normalizedType === 'pickup') {
            return [
                'fee' => 0.0,
                'mode' => 'pickup',
                'distance_km' => null,
                'label' => 'Retiro en sede',
                'notifications_enabled' => $settings['notifications_enabled'],
            ];
        }

        if ($normalizedType === 'shipping') {
            return [
                'fee' => 0.0,
                'mode' => 'shipping',
                'distance_km' => null,
                'label' => 'Envio por tercero',
                'notifications_enabled' => false,
                'items_subtotal' => round(max(0, $itemsSubtotal), 2),
            ];
        }

        if (!$settings['enabled']) {
            throw new RuntimeException('La sede no tiene delivery activo.');
        }

        $mode = $settings['mode'];
        $fee = 0.0;

        if ($mode === 'fixed') {
            $fee = $settings['fixed_fee'];
        } elseif ($mode === 'distance') {
            if (is_null($normalizedDistance) || $normalizedDistance <= 0) {
                throw new RuntimeException('Debes indicar la distancia estimada del delivery en kilómetros.');
            }

            $fee = round($normalizedDistance * $settings['fee_per_km'], 2);
        }

        return [
            'fee' => round(max(0, $fee), 2),
            'mode' => $mode,
            'distance_km' => $mode === 'distance' ? $normalizedDistance : null,
            'label' => self::modeLabel($mode),
            'notifications_enabled' => $settings['notifications_enabled'],
            'items_subtotal' => round(max(0, $itemsSubtotal), 2),
        ];
    }

    public static function modeLabel(?string $mode): string
    {
        return match (self::normalizeMode($mode)) {
            'fixed' => 'Tarifa fija',
            'distance' => 'Tarifa por km',
            default => 'Gratis',
        };
    }

    public static function normalizeMode(?string $mode): string
    {
        $normalizedMode = strtolower(trim((string) $mode));

        return in_array($normalizedMode, ['free', 'fixed', 'distance'], true)
            ? $normalizedMode
            : 'free';
    }

    public static function normalizeType(?string $type): string
    {
        $normalizedType = strtolower(trim((string) $type));

        return in_array($normalizedType, ['pickup', 'delivery', 'shipping'], true)
            ? $normalizedType
            : 'pickup';
    }
}