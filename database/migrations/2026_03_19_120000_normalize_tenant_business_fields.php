<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        if (!Schema::hasTable('tenants')) {
            return;
        }

        if (!Schema::hasColumn('tenants', 'business_type') || !Schema::hasColumn('tenants', 'economic_activity')) {
            return;
        }

        $catalogByType = [
            'Tienda' => [
                'Alimentos y Bebidas',
                'Moda y Accesorios',
                'Hogar y Construccion',
                'Tecnologia',
                'Salud y Belleza',
                'Otros',
            ],
            'Servicio' => [
                'Gastronomia',
                'Cuidado Personal',
                'Servicios Tecnicos',
                'Profesionales',
                'Logistica y Educacion',
            ],
        ];

        $activityAliases = [
            'alimentosybebidas' => 'Alimentos y Bebidas',
            'alimentos' => 'Alimentos y Bebidas',
            'bebidas' => 'Alimentos y Bebidas',
            'modayaccesorios' => 'Moda y Accesorios',
            'moda' => 'Moda y Accesorios',
            'accesorios' => 'Moda y Accesorios',
            'hogaryconstruccion' => 'Hogar y Construccion',
            'hogar' => 'Hogar y Construccion',
            'construccion' => 'Hogar y Construccion',
            'ferreteria' => 'Hogar y Construccion',
            'tecnologia' => 'Tecnologia',
            'electronica' => 'Tecnologia',
            'computacion' => 'Tecnologia',
            'telefonia' => 'Tecnologia',
            'saludybelleza' => 'Salud y Belleza',
            'salud' => 'Salud y Belleza',
            'belleza' => 'Salud y Belleza',
            'farmacia' => 'Salud y Belleza',
            'cosmetica' => 'Salud y Belleza',
            'gastronomia' => 'Gastronomia',
            'restaurante' => 'Gastronomia',
            'cafeteria' => 'Gastronomia',
            'fastfood' => 'Gastronomia',
            'catering' => 'Gastronomia',
            'cuidadopersonal' => 'Cuidado Personal',
            'peluqueria' => 'Cuidado Personal',
            'estetica' => 'Cuidado Personal',
            'spa' => 'Cuidado Personal',
            'gimnasio' => 'Cuidado Personal',
            'serviciostecnicos' => 'Servicios Tecnicos',
            'serviciotecnico' => 'Servicios Tecnicos',
            'taller' => 'Servicios Tecnicos',
            'reparacion' => 'Servicios Tecnicos',
            'soporteit' => 'Servicios Tecnicos',
            'profesional' => 'Profesionales',
            'profesionales' => 'Profesionales',
            'consultorio' => 'Profesionales',
            'consultoria' => 'Profesionales',
            'arquitectura' => 'Profesionales',
            'logisticayeducacion' => 'Logistica y Educacion',
            'logistica' => 'Logistica y Educacion',
            'educacion' => 'Logistica y Educacion',
            'mensajeria' => 'Logistica y Educacion',
            'instituto' => 'Logistica y Educacion',
            'otros' => 'Otros',
            'general' => 'Otros',
        ];

        $tenants = DB::table('tenants')->select('id', 'business_type', 'economic_activity')->get();

        foreach ($tenants as $tenant) {
            $currentTypeRaw = trim((string) ($tenant->business_type ?? ''));
            $currentActivityRaw = trim((string) ($tenant->economic_activity ?? ''));

            $typeKey = $this->normalizeKey($currentTypeRaw);
            $normalizedType = match (true) {
                in_array($typeKey, ['servicio', 'servicios', 'service', 'services'], true) => 'Servicio',
                in_array($typeKey, ['tienda', 'tiendas', 'store', 'stores', 'comercio', 'comercios'], true) => 'Tienda',
                default => null,
            };

            $activityKey = $this->normalizeKey($currentActivityRaw);
            $normalizedActivity = $activityAliases[$activityKey] ?? null;

            if (!$normalizedType) {
                if ($normalizedActivity && in_array($normalizedActivity, $catalogByType['Servicio'], true)) {
                    $normalizedType = 'Servicio';
                } else {
                    $normalizedType = 'Tienda';
                }
            }

            if (!$normalizedActivity) {
                $normalizedActivity = $catalogByType[$normalizedType][0] ?? 'Otros';
            }

            if (!in_array($normalizedActivity, $catalogByType[$normalizedType], true)) {
                $normalizedActivity = $normalizedType === 'Servicio' ? 'Profesionales' : 'Otros';
            }

            if ($currentTypeRaw !== $normalizedType || $currentActivityRaw !== $normalizedActivity) {
                DB::table('tenants')
                    ->where('id', $tenant->id)
                    ->update([
                        'business_type' => $normalizedType,
                        'economic_activity' => $normalizedActivity,
                    ]);
            }
        }
    }

    public function down(): void
    {
        // Irreversible data normalization.
    }

    private function normalizeKey(?string $value): string
    {
        $normalized = mb_strtolower(trim((string) $value));
        if ($normalized === '') {
            return '';
        }

        $normalized = strtr($normalized, [
            'á' => 'a',
            'é' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'ú' => 'u',
            'ü' => 'u',
            'ñ' => 'n',
        ]);

        return preg_replace('/[^a-z0-9]/', '', $normalized) ?? '';
    }
};
