<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;
use PharData;
use RuntimeException;
use ZipArchive;
use DOMDocument;
use DOMXPath;

class ShopixSetupDocumentService
{
    private const KEY_VALUE_TABLE_HEADERS = [
        'campo|respuesta',
        'campo|valor',
    ];

    private const LIST_TABLE_HEADERS = [
        'rol|nombre|correo|contrasena|telefono|dni rif' => 'users',
        'nombre|banco|titular|dni rif|descripcion|requiere referencia' => 'payment_methods',
        'categoria|producto|variante|precio|stock|descripcion|consumible|activo' => 'store_catalog',
        'servicio|categoria|precio|duracion min|buffer min|profesional|descripcion|color|activo' => 'service_catalog',
        'profesional|dia|hora inicio|hora fin|intervalo min|activo' => 'schedule_rules',
    ];

    private const FIELD_MAP = [
        'nombre comercial' => 'name',
        'slug sugerido' => 'slug',
        'correo principal' => 'email',
        'tipo de negocio tienda servicio' => 'business_type',
        'rubro economico' => 'economic_activity',
        'eslogan' => 'slogan',
        'descripcion comercial' => 'description',
        'codigo pais' => 'phone_code',
        'telefono principal' => 'phone_number',
        'pais' => 'country_name',
        'estado' => 'state_name',
        'ciudad' => 'city_name',
        'direccion' => 'address',
        'dias operativos' => 'working_days',
        'hora de apertura' => 'opening_time',
        'hora de cierre' => 'closing_time',
        'modo citas por orden de llegada si no' => 'appointments_first_come_enabled',
        'contribuyente especial si no' => 'special_taxpayer',
        'delivery habilitado si no' => 'delivery_enabled',
        'modo tarifa delivery free fixed distance' => 'delivery_fee_mode',
        'tarifa fija delivery' => 'delivery_fixed_fee',
        'tarifa por km delivery' => 'delivery_fee_per_km',
        'restringir delivery a ciudad de la tienda si no' => 'restrict_delivery_city_to_tenant',
        'notificaciones de delivery si no' => 'delivery_notifications_enabled',
        'tiktok' => 'tiktok',
        'instagram' => 'instagram',
        'facebook' => 'facebook',
        'color primario' => 'color_primary',
        'color secundario' => 'color_secondary',
        'color acento' => 'color_accent',
    ];

    public function parseUploadedFile(UploadedFile $file): array
    {
        return $this->parseDocxFile($file->getRealPath() ?: $file->path());
    }

    public function parseDocxFile(string $path): array
    {
        $documentXml = $this->readDocumentXml($path);
        $tables = $this->extractTables($documentXml);

        $payload = [
            'tenant' => [],
            'users' => [],
            'payment_methods' => [],
            'store_catalog' => [],
            'service_catalog' => [],
            'schedule_rules' => [],
        ];

        foreach ($tables as $table) {
            if (empty($table)) {
                continue;
            }

            $headerSignature = $this->buildHeaderSignature($table[0]);

            if (in_array($headerSignature, self::KEY_VALUE_TABLE_HEADERS, true)) {
                $payload['tenant'] = array_merge($payload['tenant'], $this->parseKeyValueTable($table));
                continue;
            }

            $listKey = self::LIST_TABLE_HEADERS[$headerSignature] ?? null;
            if (!$listKey) {
                continue;
            }

            $payload[$listKey] = array_merge($payload[$listKey], $this->parseListTable($listKey, $table));
        }

        $payload['tenant'] = $this->normalizeTenantPayload($payload['tenant']);
        $payload['users'] = $this->normalizeRows($payload['users']);
        $payload['payment_methods'] = $this->normalizeRows($payload['payment_methods']);
        $payload['store_catalog'] = $this->normalizeRows($payload['store_catalog']);
        $payload['service_catalog'] = $this->normalizeRows($payload['service_catalog']);
        $payload['schedule_rules'] = $this->normalizeRows($payload['schedule_rules']);

        return [
            'payload' => $payload,
            'summary' => [
                'users' => count($payload['users']),
                'payment_methods' => count($payload['payment_methods']),
                'store_catalog' => count($payload['store_catalog']),
                'service_catalog' => count($payload['service_catalog']),
                'schedule_rules' => count($payload['schedule_rules']),
            ],
        ];
    }

    private function readDocumentXml(string $path): string
    {
        $documentXml = null;

        if (class_exists(ZipArchive::class)) {
            $zip = new ZipArchive();
            $opened = $zip->open($path);

            if ($opened === true) {
                $documentXml = $zip->getFromName('word/document.xml');
                $zip->close();
            }
        }

        if (!is_string($documentXml) || trim($documentXml) === '') {
            $documentXml = $this->readDocumentXmlWithPhar($path);
        }

        if (!is_string($documentXml) || trim($documentXml) === '') {
            throw new RuntimeException('El DOCX no contiene un documento principal válido.');
        }

        return $documentXml;
    }

    private function readDocumentXmlWithPhar(string $path): ?string
    {
        try {
            $archive = new PharData($path);
            if (!isset($archive['word/document.xml'])) {
                return null;
            }

            return $archive['word/document.xml']->getContent();
        } catch (\Throwable) {
            return null;
        }
    }

    private function extractTables(string $documentXml): array
    {
        $document = new DOMDocument();
        $loaded = @$document->loadXML($documentXml);

        if (!$loaded) {
            throw new RuntimeException('No se pudo leer el contenido XML del documento.');
        }

        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('w', 'http://schemas.openxmlformats.org/wordprocessingml/2006/main');

        $tables = [];
        foreach ($xpath->query('//w:tbl') ?: [] as $tableNode) {
            $rows = [];
            foreach ($xpath->query('./w:tr', $tableNode) ?: [] as $rowNode) {
                $cells = [];
                foreach ($xpath->query('./w:tc', $rowNode) ?: [] as $cellNode) {
                    $paragraphs = [];
                    foreach ($xpath->query('.//w:p', $cellNode) ?: [] as $paragraphNode) {
                        $parts = [];
                        foreach ($xpath->query('.//w:t|.//w:tab|.//w:br', $paragraphNode) ?: [] as $textNode) {
                            if ($textNode->localName === 'tab') {
                                $parts[] = "\t";
                                continue;
                            }

                            if ($textNode->localName === 'br') {
                                $parts[] = "\n";
                                continue;
                            }

                            $parts[] = $textNode->textContent;
                        }

                        $paragraphText = trim(preg_replace('/[ \t]+/', ' ', implode('', $parts)) ?? '');
                        if ($paragraphText !== '') {
                            $paragraphs[] = $paragraphText;
                        }
                    }

                    $cells[] = trim(implode("\n", $paragraphs));
                }

                if (!empty(array_filter($cells, fn ($value) => trim((string) $value) !== ''))) {
                    $rows[] = $cells;
                }
            }

            if (!empty($rows)) {
                $tables[] = $rows;
            }
        }

        return $tables;
    }

    private function buildHeaderSignature(array $headerRow): string
    {
        return implode('|', array_map(fn ($value) => $this->normalizeHeader((string) $value), $headerRow));
    }

    private function parseKeyValueTable(array $table): array
    {
        $tenant = [];

        foreach (array_slice($table, 1) as $row) {
            $label = $row[0] ?? '';
            $value = $row[1] ?? '';

            $field = self::FIELD_MAP[$this->normalizeHeader($label)] ?? null;
            if (!$field) {
                continue;
            }

            $tenant[$field] = trim((string) $value);
        }

        return $tenant;
    }

    private function parseListTable(string $listKey, array $table): array
    {
        $headers = array_map(fn ($value) => $this->normalizeHeader((string) $value), $table[0]);
        $rows = [];

        foreach (array_slice($table, 1) as $row) {
            $mapped = [];
            foreach ($headers as $index => $header) {
                $mapped[$header] = trim((string) ($row[$index] ?? ''));
            }

            if ($this->isEmptyRow($mapped)) {
                continue;
            }

            $rows[] = match ($listKey) {
                'users' => [
                    'role' => $mapped['rol'] ?? '',
                    'name' => $mapped['nombre'] ?? '',
                    'email' => $mapped['correo'] ?? '',
                    'password' => $mapped['contrasena'] ?? '',
                    'phone_number' => $mapped['telefono'] ?? '',
                    'dni' => $mapped['dni rif'] ?? '',
                ],
                'payment_methods' => [
                    'name' => $mapped['nombre'] ?? '',
                    'bank' => $mapped['banco'] ?? '',
                    'admin_name' => $mapped['titular'] ?? '',
                    'dni' => $mapped['dni rif'] ?? '',
                    'description' => $mapped['descripcion'] ?? '',
                    'has_reference' => $mapped['requiere referencia'] ?? '',
                ],
                'store_catalog' => [
                    'category' => $mapped['categoria'] ?? '',
                    'product_name' => $mapped['producto'] ?? '',
                    'variant_name' => $mapped['variante'] ?? '',
                    'price' => $mapped['precio'] ?? '',
                    'stock' => $mapped['stock'] ?? '',
                    'description' => $mapped['descripcion'] ?? '',
                    'is_consumable' => $mapped['consumible'] ?? '',
                    'is_active' => $mapped['activo'] ?? '',
                ],
                'service_catalog' => [
                    'name' => $mapped['servicio'] ?? '',
                    'category' => $mapped['categoria'] ?? '',
                    'price' => $mapped['precio'] ?? '',
                    'duration_minutes' => $mapped['duracion min'] ?? '',
                    'buffer_minutes' => $mapped['buffer min'] ?? '',
                    'professional' => $mapped['profesional'] ?? '',
                    'description' => $mapped['descripcion'] ?? '',
                    'color_hex' => $mapped['color'] ?? '',
                    'is_active' => $mapped['activo'] ?? '',
                ],
                'schedule_rules' => [
                    'professional' => $mapped['profesional'] ?? '',
                    'day' => $mapped['dia'] ?? '',
                    'start_time' => $mapped['hora inicio'] ?? '',
                    'end_time' => $mapped['hora fin'] ?? '',
                    'slot_interval_minutes' => $mapped['intervalo min'] ?? '',
                    'is_active' => $mapped['activo'] ?? '',
                ],
                default => $mapped,
            };
        }

        return $rows;
    }

    private function normalizeTenantPayload(array $tenant): array
    {
        if (array_key_exists('business_type', $tenant)) {
            $value = Str::lower(trim((string) $tenant['business_type']));
            $tenant['business_type'] = $value === 'servicio' ? 'servicio' : 'tienda';
        }

        foreach (['appointments_first_come_enabled', 'special_taxpayer', 'delivery_enabled', 'restrict_delivery_city_to_tenant', 'delivery_notifications_enabled'] as $booleanField) {
            if (array_key_exists($booleanField, $tenant)) {
                $tenant[$booleanField] = $this->normalizeBoolean($tenant[$booleanField]);
            }
        }

        foreach (['delivery_fixed_fee', 'delivery_fee_per_km'] as $numericField) {
            if (array_key_exists($numericField, $tenant)) {
                $tenant[$numericField] = $this->normalizeNumber($tenant[$numericField]);
            }
        }

        if (array_key_exists('working_days', $tenant)) {
            $tenant['working_days'] = $this->normalizeWorkingDays($tenant['working_days']);
        }

        return array_filter(
            $tenant,
            fn ($value) => !is_null($value) && (!(is_string($value)) || trim($value) !== '') && (!(is_array($value)) || !empty($value))
        );
    }

    private function normalizeRows(array $rows): array
    {
        $normalized = [];

        foreach ($rows as $row) {
            $clean = [];
            foreach ($row as $key => $value) {
                if (in_array($key, ['has_reference', 'is_consumable', 'is_active'], true)) {
                    $clean[$key] = $this->normalizeBoolean($value);
                    continue;
                }

                if (in_array($key, ['price', 'stock', 'duration_minutes', 'buffer_minutes', 'slot_interval_minutes'], true)) {
                    $clean[$key] = $this->normalizeNumber($value);
                    continue;
                }

                $clean[$key] = trim((string) $value);
            }

            if (!$this->isEmptyRow($clean)) {
                $normalized[] = $clean;
            }
        }

        return $normalized;
    }

    private function normalizeWorkingDays(string $value): array
    {
        $parts = preg_split('/[,;\n]+/', $value) ?: [];
        $map = [
            'lunes' => 'monday',
            'martes' => 'tuesday',
            'miercoles' => 'wednesday',
            'miércoles' => 'wednesday',
            'jueves' => 'thursday',
            'viernes' => 'friday',
            'sabado' => 'saturday',
            'sábado' => 'saturday',
            'domingo' => 'sunday',
            'monday' => 'monday',
            'tuesday' => 'tuesday',
            'wednesday' => 'wednesday',
            'thursday' => 'thursday',
            'friday' => 'friday',
            'saturday' => 'saturday',
            'sunday' => 'sunday',
        ];

        $normalized = [];
        foreach ($parts as $part) {
            $key = $this->normalizeHeader($part);
            if (isset($map[$key])) {
                $normalized[] = $map[$key];
            }
        }

        return array_values(array_unique($normalized));
    }

    private function normalizeBoolean(mixed $value): ?bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $normalized = Str::lower(trim((string) $value));
        if ($normalized === '') {
            return null;
        }

        if (in_array($normalized, ['si', 'sí', 'yes', 'true', '1', 'activo', 'activa'], true)) {
            return true;
        }

        if (in_array($normalized, ['no', 'false', '0', 'inactivo', 'inactiva'], true)) {
            return false;
        }

        return null;
    }

    private function normalizeNumber(mixed $value): float|int|null
    {
        $normalized = trim((string) $value);
        if ($normalized === '') {
            return null;
        }

        $normalized = str_replace(['$', 'Bs', 'USD', ' '], '', $normalized);
        $normalized = str_replace(',', '.', $normalized);

        if (!is_numeric($normalized)) {
            return null;
        }

        $number = (float) $normalized;
        if (floor($number) === $number) {
            return (int) $number;
        }

        return $number;
    }

    private function normalizeHeader(string $value): string
    {
        $value = Str::lower(trim($value));
        $value = Str::ascii($value);
        $value = preg_replace('/[^a-z0-9#\s]+/', ' ', $value) ?? $value;
        $value = preg_replace('/\s+/', ' ', $value) ?? $value;

        return trim($value);
    }

    private function isEmptyRow(array $row): bool
    {
        foreach ($row as $value) {
            if (is_bool($value) || is_numeric($value)) {
                return false;
            }

            if (trim((string) $value) !== '') {
                return false;
            }
        }

        return true;
    }
}