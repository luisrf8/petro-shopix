<?php

namespace App\Services;

use App\Models\ElectronicDocument;
use App\Models\AccountPayable;
use App\Models\IslrWithholding;
use App\Models\PurchaseVatRetention;
use App\Models\SalesAdjustmentNote;
use App\Models\SalesOrder;
use App\Models\SalesRetention;
use App\Models\Tax;
use App\Support\TenantCurrency;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class TheFactoryHkaService
{
    private string $baseUrl;
    private string $username;
    private string $password;
    private string $signatureSecret;
    private bool $enforceSignatureValidation;
    private int $timeout;
    private bool $verifySsl;

    public function __construct()
    {
        $config = config('services.thefactory_hka', []);

        $this->baseUrl = rtrim((string) ($config['base_url'] ?? ''), '/');
        $this->username = (string) ($config['username'] ?? '');
        $this->password = (string) ($config['password'] ?? '');
        $this->signatureSecret = (string) ($config['signature_secret'] ?? $this->password);
        $this->enforceSignatureValidation = (bool) ($config['enforce_signature_validation'] ?? true);
        $this->timeout = (int) ($config['timeout'] ?? 25);
        $this->verifySsl = (bool) ($config['verify_ssl'] ?? true);
    }

    public function validateDocumentPayloadCompliance(array $documentoElectronico): array
    {
        $errors = [];

        $requiredPaths = [
            'encabezado.identificacionDocumento.tipoDocumento',
            'encabezado.identificacionDocumento.fechaEmision',
            'encabezado.identificacionDocumento.transaccionId',
            'encabezado.comprador.tipoIdentificacion',
            'encabezado.comprador.numeroIdentificacion',
            'encabezado.comprador.razonSocial',
            'encabezado.totales.montoGravadoTotal',
            'encabezado.totales.totalIVA',
            'encabezado.totales.totalAPagar',
            'detallesItems',
        ];

        foreach ($requiredPaths as $path) {
            $value = Arr::get($documentoElectronico, $path);
            if (is_null($value) || $value === '' || (is_array($value) && empty($value))) {
                $errors[] = 'Campo obligatorio faltante: ' . $path;
            }
        }

        if (!Arr::has($documentoElectronico, 'encabezado.identificacionDocumento.serie')) {
            $errors[] = 'Campo obligatorio faltante: encabezado.identificacionDocumento.serie';
        }

        $totalIva = (float) str_replace(',', '.', (string) Arr::get($documentoElectronico, 'encabezado.totales.totalIVA', '0'));
        $totalPagar = (float) str_replace(',', '.', (string) Arr::get($documentoElectronico, 'encabezado.totales.totalAPagar', '0'));
        if ($totalIva < 0) {
            $errors[] = 'El total de IVA no puede ser negativo.';
        }
        if ($totalPagar <= 0) {
            $errors[] = 'El total a pagar debe ser mayor a cero.';
        }

        $items = Arr::get($documentoElectronico, 'detallesItems', []);
        if (!is_array($items) || count($items) === 0) {
            $errors[] = 'Debe existir al menos un item en detallesItems.';
        }

        if ($this->enforceSignatureValidation && trim($this->signatureSecret) === '') {
            $errors[] = 'No existe secreto de firma configurado para validar integridad del payload.';
        }

        $tokenAvailable = !is_null($this->getAuthToken());
        if (!$tokenAvailable) {
            $errors[] = 'No fue posible obtener token de seguridad para HKA.';
        }

        return [
            'ok' => empty($errors),
            'errors' => $errors,
        ];
    }

    public function isConfigured(): bool
    {
        return $this->baseUrl !== '' && $this->username !== '' && $this->password !== '';
    }

    public function emitDocument(array $documentoElectronico): array
    {
        return $this->request('post', '/api/Emision', [
            'documentoElectronico' => $documentoElectronico,
        ]);
    }

    public function emitCreditNote(array $documentoElectronico): array
    {
        $v2Response = $this->request('post', '/api/v2/NotaCredito', [
            'documentoElectronico' => $documentoElectronico,
        ]);

        if ($v2Response['ok'] ?? false) {
            return $v2Response;
        }

        return $this->emitDocument($documentoElectronico);
    }

    public function emitDebitNote(array $documentoElectronico): array
    {
        $v2Response = $this->request('post', '/api/v2/NotaDebito', [
            'documentoElectronico' => $documentoElectronico,
        ]);

        if ($v2Response['ok'] ?? false) {
            return $v2Response;
        }

        return $this->emitDocument($documentoElectronico);
    }

    public function emitArc(array $documentoElectronico): array
    {
        return $this->request('post', '/api/EmisionARC', [
            'documentoElectronico' => $documentoElectronico,
        ]);
    }

    public function buildAdjustmentNotePayloadFromOrder(
        SalesOrder $order,
        SalesAdjustmentNote $note,
        ElectronicDocument $referenceDocument,
        array $options = []
    ): array {
        $order->loadMissing(['user', 'tenant', 'salesRepresentative']);

        $documentCurrency = $this->normalizeCurrency((string) (
            Arr::get($referenceDocument->request_payload ?? [], 'encabezado.identificacionDocumento.moneda')
            ?? config('services.thefactory_hka.default_currency', 'BSD')
        ));
        $exchangeRate = (float) (
            Arr::get($referenceDocument->request_payload ?? [], 'encabezado.totalesOtraMoneda.tipoCambio')
            ?? TenantCurrency::resolveRateToBs((int) $order->tenant_id, TenantCurrency::resolveBaseCurrencyCode($order->tenant))
            ?? 0
        );

        if ($exchangeRate <= 0) {
            $exchangeRate = max((float) config('services.thefactory_hka.default_exchange_rate', 1), 1);
        }

        $emitOtherCurrency = $this->isBolivarCurrency($documentCurrency);
        $otherCurrency = $emitOtherCurrency
            ? TenantCurrency::resolveBaseCurrencyCode($order->tenant)
            : 'BSD';
        $multiplier = $emitOtherCurrency ? $exchangeRate : 1;

        $taxableBase = round((float) ($note->taxable_base ?? 0), 2);
        $taxRate = round((float) ($note->tax_rate ?? 0), 2);
        $taxAmount = round((float) ($note->tax_amount ?? 0), 2);
        $igtfAmount = round(max(0, (float) ($note->affected_igtf_amount ?? 0)), 2);
        $igtfRate = $this->resolveAdjustmentNoteIgtfRate($note, $order, $referenceDocument);
        $igtfBaseAmount = $this->resolveAdjustmentNoteIgtfBaseAmount($note, $order, $referenceDocument, $igtfAmount, $igtfRate);
        $documentIgtfBaseAmount = $emitOtherCurrency ? round($igtfBaseAmount * $exchangeRate, 2) : $igtfBaseAmount;

        if ($taxableBase <= 0) {
            $taxableBase = round(max(0, (float) ($note->amount ?? 0) - $taxAmount - $igtfAmount), 2);
        }

        $exemptBase = $taxAmount <= 0 ? $taxableBase : 0.0;
        $gravableBase = $taxAmount > 0 ? $taxableBase : 0.0;
        $subtotal = round($taxableBase, 2);
        $montoTotalConIva = round($subtotal + $taxAmount, 2);
        $totalAPagar = round($montoTotalConIva + $igtfAmount, 2);

        $impuestosSubtotal = [[
            'codigoTotalImp' => $taxAmount > 0 ? $this->resolveInvoiceTaxCodeByRate($taxRate) : 'E',
            'alicuotaImp' => $taxAmount > 0 ? $this->formatPlainNumber($taxRate, 2) : null,
            'baseImponibleImp' => $this->formatAmount($taxableBase * $multiplier, 2),
            'valorTotalImp' => $this->formatAmount($taxAmount * $multiplier, 2),
        ]];

        $impuestosSubtotalOtraMoneda = [[
            'codigoTotalImp' => $taxAmount > 0 ? $this->resolveInvoiceTaxCodeByRate($taxRate) : 'E',
            'alicuotaImp' => $taxAmount > 0 ? $this->formatPlainNumber($taxRate, 2) : null,
            'baseImponibleImp' => $this->formatAmount($taxableBase, 2),
            'valorTotalImp' => $this->formatAmount($taxAmount, 2),
        ]];

        if ($igtfAmount > 0) {
            $impuestosSubtotal[] = [
                'codigoTotalImp' => 'IGTF',
                'alicuotaImp' => $this->formatPlainNumber($igtfRate, 2),
                'baseImponibleImp' => $this->formatAmount($documentIgtfBaseAmount, 2),
                'valorTotalImp' => $this->formatAmount($igtfAmount * $multiplier, 2),
            ];
            $impuestosSubtotalOtraMoneda[] = [
                'codigoTotalImp' => 'IGTF',
                'alicuotaImp' => $this->formatPlainNumber($igtfRate, 2),
                'baseImponibleImp' => $this->formatAmount($igtfBaseAmount, 2),
                'valorTotalImp' => $this->formatAmount($igtfAmount, 2),
            ];
        }

        $noteItem = [
            'numeroLinea' => '1',
            'codigoPLU' => 'AJUSTE-' . ($note->internal_number ?? $note->id ?? '1'),
            'indicadorBienoServicio' => '2',
            'descripcion' => trim((string) ($note->reason ?? 'Ajuste fiscal de venta')),
            'cantidad' => '1',
            'unidadMedida' => 'UND',
            'precioUnitario' => $this->formatAmount($taxableBase * $multiplier, 4),
            'descuentoMonto' => $this->formatAmount(0, 2),
            'recargoMonto' => $this->formatAmount(0, 2),
            'precioItem' => $this->formatAmount($taxableBase * $multiplier, 2),
            'codigoImpuesto' => $taxAmount > 0 ? $this->resolveInvoiceTaxCodeByRate($taxRate) : 'E',
            'tasaIVA' => $taxAmount > 0 ? $this->formatPlainNumber($taxRate, 2) : null,
            'valorIVA' => $taxAmount > 0 ? $this->formatAmount($taxAmount * $multiplier, 2) : null,
            'valorTotalItem' => $this->formatAmount($montoTotalConIva * $multiplier, 2),
            'infoAdicionalItem' => [],
            'listaItemOTI' => null,
        ];

        $identification = $this->buildCustomerIdentification($order);
        $tipoDocumento = $note->note_type === 'credit' ? '02' : '03';
        $serie = trim((string) ($referenceDocument->serie ?? config('services.thefactory_hka.default_serie', '')));
        $numeroDocumento = preg_replace('/\D+/', '', trim((string) ($note->internal_number ?: $note->id)));
        if ($numeroDocumento === '') {
            $numeroDocumento = (string) ($note->id ?? $order->id);
        }
        $montoFacturaAfectada = $this->resolveAffectedInvoiceAmount($order, $referenceDocument, $documentCurrency);
        $sellerName = trim((string) ($order->salesRepresentative->name ?? $order->tenant->name ?? 'Shopix'));
        $sellerCode = trim((string) ($order->sales_rep_user_id ?? auth()->id() ?? ''));
        $transactionId = preg_replace('/[^A-Za-z0-9]/', '', 'SHOPIXN' . ($note->id ?? $order->id) . Str::upper(Str::random(6)));

        $customPayload = [
            'encabezado' => [
                'identificacionDocumento' => [
                    'tipoDocumento' => $tipoDocumento,
                    'numeroDocumento' => $numeroDocumento,
                    'fechaEmision' => Carbon::parse((string) ($note->note_date ?? now()->toDateString()))->format('d/m/Y'),
                    'horaEmision' => strtolower(now()->format('h:i:s a')),
                    'serie' => $serie,
                    'sucursal' => (string) config('services.thefactory_hka.default_branch', '0001'),
                    'tipoDePago' => 'Inmediato',
                    'tipoDeVenta' => 'Interna',
                    'moneda' => $documentCurrency,
                    'transaccionId' => $transactionId,
                    'serieFacturaAfectada' => (string) ($referenceDocument->serie ?? ''),
                    'numeroFacturaAfectada' => (string) ($referenceDocument->numero_documento ?? $note->reference_document_number ?? ''),
                    'numeroControlFacturaAfectada' => (string) ($referenceDocument->numero_control ?? $note->reference_control_number ?? ''),
                    'fechaFacturaAfectada' => Carbon::parse((string) ($referenceDocument->issued_at ?? $referenceDocument->created_at ?? now()->toDateString()))->format('d/m/Y'),
                    'montoFacturaAfectada' => $montoFacturaAfectada,
                    'comentarioFacturaAfectada' => trim((string) ($note->reason ?? 'Ajuste fiscal de venta')),
                ],
                'comprador' => [
                    'tipoIdentificacion' => $identification['tipo_identificacion'],
                    'numeroIdentificacion' => $identification['numero_identificacion'],
                    'razonSocial' => (string) ($order->user->name ?? 'Consumidor final'),
                    'direccion' => (string) (($order->address && trim((string) $order->address) !== '') ? $order->address : ($order->tenant->address ?? 'N/A')),
                    'ubigeo' => null,
                    'pais' => (string) ($options['pais'] ?? 'VE'),
                    'notificar' => null,
                    'telefono' => !empty($identification['telefono']) ? [$identification['telefono']] : null,
                    'correo' => !empty($order->user?->email) ? [(string) $order->user->email] : null,
                    'otrosEnvios' => null,
                ],
                'vendedor' => [
                    'codigo' => $sellerCode,
                    'nombre' => $sellerName,
                    'numCajero' => $sellerCode,
                ],
                'totales' => [
                    'nroItems' => '1',
                    'montoGravadoTotal' => $this->formatAmount($gravableBase * $multiplier, 2),
                    'montoExentoTotal' => $this->formatAmount($exemptBase * $multiplier, 2),
                    'montoPercibidoTotal' => $this->formatAmount(0, 2),
                    'subtotal' => $this->formatAmount($subtotal * $multiplier, 2),
                    'totalIVA' => $this->formatAmount($taxAmount * $multiplier, 2),
                    'montoTotalConIVA' => $this->formatAmount($montoTotalConIva * $multiplier, 2),
                    'totalAPagar' => $this->formatAmount($totalAPagar * $multiplier, 2),
                    'impuestosSubtotal' => $impuestosSubtotal,
                    'formasPago' => [[
                        'descripcion' => 'Ajuste fiscal asociado',
                        'fecha' => Carbon::parse((string) ($note->note_date ?? now()->toDateString()))->format('d/m/Y'),
                        'forma' => '99',
                        'monto' => $this->formatAmount($totalAPagar * $multiplier, 2),
                        'moneda' => $documentCurrency,
                    ]],
                    'totalIGTF' => $igtfAmount > 0 ? $this->formatAmount($igtfAmount * $multiplier, 2) : null,
                    'totalIGTF_VES' => $igtfAmount > 0 ? $this->formatAmount($emitOtherCurrency ? $igtfAmount * $exchangeRate : $igtfAmount, 2) : null,
                ],
                'totalesOtraMoneda' => $emitOtherCurrency ? [
                    'moneda' => $otherCurrency,
                    'tipoCambio' => $this->formatAmount($exchangeRate, 4),
                    'montoGravadoTotal' => $this->formatAmount($gravableBase, 2),
                    'montoPercibidoTotal' => $this->formatAmount(0, 2),
                    'montoExentoTotal' => $this->formatAmount($exemptBase, 2),
                    'subtotal' => $this->formatAmount($subtotal, 2),
                    'totalIVA' => $this->formatAmount($taxAmount, 2),
                    'montoTotalConIVA' => $this->formatAmount($montoTotalConIva, 2),
                    'totalAPagar' => $this->formatAmount($totalAPagar, 2),
                    'impuestosSubtotal' => $impuestosSubtotalOtraMoneda,
                    'totalIGTF' => $igtfAmount > 0 ? $this->formatAmount($igtfAmount, 2) : null,
                    'totalIGTF_VES' => $igtfAmount > 0 ? $this->formatAmount($igtfAmount * $exchangeRate, 2) : null,
                ] : null,
                'orden' => null,
            ],
            'detallesItems' => [$noteItem],
            'infoAdicional' => [
                [
                    'campo' => 'SHOPIX_AJUSTE_MODO',
                    'valor' => (string) ($note->adjustment_mode ?? 'manual'),
                ],
                [
                    'campo' => 'SHOPIX_AJUSTE_INTERNO',
                    'valor' => (string) ($note->internal_number ?? $note->id),
                ],
            ],
        ];

        $payload = $this->mergeWithBaseInvoicePayload($customPayload);

        return $this->removeNulls($payload);
    }

    private function resolveAffectedInvoiceAmount(SalesOrder $order, ElectronicDocument $referenceDocument, string $targetCurrency): string
    {
        $requestPayload = is_array($referenceDocument->request_payload) ? $referenceDocument->request_payload : [];
        $referenceDocumentCurrency = $this->normalizeCurrency((string) (
            Arr::get($requestPayload, 'encabezado.identificacionDocumento.moneda')
            ?? config('services.thefactory_hka.default_currency', 'BSD')
        ));
        $otherCurrency = $this->normalizeCurrency((string) Arr::get($requestPayload, 'encabezado.totalesOtraMoneda.moneda', ''));

        $documentTotal = $this->toFloatAmount(Arr::get($requestPayload, 'encabezado.totales.totalAPagar'));
        if ($documentTotal > 0 && $referenceDocumentCurrency === $targetCurrency) {
            return $this->formatAmount($documentTotal, 2);
        }

        $otherCurrencyTotal = $this->toFloatAmount(Arr::get($requestPayload, 'encabezado.totalesOtraMoneda.totalAPagar'));
        if ($otherCurrencyTotal > 0 && $otherCurrency === $targetCurrency) {
            return $this->formatAmount($otherCurrencyTotal, 2);
        }

        $order->loadMissing(['tenant', 'details.taxes']);
        $sourceCurrency = TenantCurrency::normalizeCurrencyCode((string) ($order->sale_currency_code ?: TenantCurrency::resolveBaseCurrencyCode($order->tenant)));
        $itemsSubtotal = (float) $order->details->sum('amount');
        $taxTotal = (float) $order->details->flatMap->taxes->sum('tax_amount');
        if ($taxTotal <= 0) {
            $taxTotal = (float) $order->details->sum(fn ($item) => (float) ($item->tax_amount ?? 0));
        }
        $orderTotal = round($itemsSubtotal + (float) ($order->delivery_fee_amount ?? 0) + $taxTotal + (float) ($order->igtf_amount ?? 0), 2);
        $convertedTotal = (float) TenantCurrency::convertAmount($orderTotal, $sourceCurrency, $targetCurrency, (int) $order->tenant_id);

        return $this->formatAmount($convertedTotal, 2);
    }

    private function toFloatAmount($value): float
    {
        $text = trim((string) $value);
        if ($text === '') {
            return 0.0;
        }

        $sanitized = preg_replace('/[^0-9,.-]/', '', $text);
        if ($sanitized === null || $sanitized === '') {
            return 0.0;
        }

        $normalized = str_contains($sanitized, ',') && !str_contains($sanitized, '.')
            ? str_replace(',', '.', $sanitized)
            : str_replace(',', '', $sanitized);

        return is_numeric($normalized) ? (float) $normalized : 0.0;
    }

    public function buildRetentionPayloadFromOrder(
        SalesOrder $order,
        SalesRetention $retention,
        ElectronicDocument $referenceDocument,
        array $options = []
    ): array {
        $order->loadMissing(['tenant', 'user']);

        $documentCurrency = $this->normalizeCurrency((string) (
            Arr::get($referenceDocument->request_payload ?? [], 'encabezado.identificacionDocumento.moneda')
            ?? config('services.thefactory_hka.default_currency', 'BSD')
        ));
        $targetCurrency = $this->isBolivarCurrency($documentCurrency) ? 'BS' : $documentCurrency;
        $sourceCurrency = TenantCurrency::normalizeCurrencyCode((string) ($retention->currency_code ?: $order->sale_currency_code ?: TenantCurrency::resolveBaseCurrencyCode($order->tenant)));

        $baseAmount = (float) TenantCurrency::convertAmount(
            (float) $retention->taxable_base,
            $sourceCurrency,
            $targetCurrency,
            (int) $order->tenant_id
        );
        $retainedAmount = (float) TenantCurrency::convertAmount(
            (float) $retention->retained_amount,
            $sourceCurrency,
            $targetCurrency,
            (int) $order->tenant_id
        );

        $igtfFromOptions = max(0, (float) ($options['total_igtf'] ?? 0));
        $igtfFromReferenceDocument = max(0, (float) str_replace(',', '.', (string) Arr::get(
            $referenceDocument->request_payload ?? [],
            'encabezado.totales.totalIGTF',
            0
        )));
        $orderIgtfSourceCurrency = TenantCurrency::normalizeCurrencyCode((string) ($order->sale_currency_code ?: TenantCurrency::resolveBaseCurrencyCode($order->tenant)));
        $igtfFromOrder = max(0, (float) TenantCurrency::convertAmount(
            (float) ($order->igtf_amount ?? 0),
            $orderIgtfSourceCurrency,
            $targetCurrency,
            (int) $order->tenant_id
        ));

        $igtfToDeclare = $igtfFromOptions > 0
            ? $igtfFromOptions
            : ($igtfFromReferenceDocument > 0 ? $igtfFromReferenceDocument : $igtfFromOrder);

        $certificateNumber = preg_replace('/[^A-Za-z0-9]/', '', (string) ($retention->certificate_number ?? ''));
        if ($certificateNumber === '') {
            $certificateNumber = preg_replace('/[^A-Za-z0-9]/', '', (string) ($retention->internal_number ?? $retention->id));
        }

        $totalRetencion = [
            'totalBaseImponible' => $this->formatAmount($baseAmount, 2),
            'numeroCompRetencion' => $certificateNumber,
            'fechaEmisionCR' => Carbon::parse((string) ($retention->retention_date ?? now()->toDateString()))->format('d/m/Y'),
            'totalRetenido' => $this->formatAmount($retainedAmount, 2),
        ];

        $retentionType = Str::lower(trim((string) $retention->retention_type));
        if ($retentionType === 'iva') {
            $totalRetencion['totalIVA'] = $this->formatAmount($baseAmount, 2);
        } elseif ($retentionType === 'islr') {
            $totalRetencion['totalISRL'] = $this->formatAmount($retainedAmount, 2);
        }

        if ($igtfToDeclare > 0) {
            $totalRetencion['totalIGTF'] = $this->formatAmount($igtfToDeclare, 2);
        }

        return $this->removeNulls([
            'serie' => (string) ($referenceDocument->serie ?? ''),
            'tipoDocumento' => (string) ($referenceDocument->tipo_documento ?: '01'),
            'numeroDocumento' => preg_replace('/\D+/', '', (string) ($referenceDocument->numero_documento ?? '')),
            'numeroControl' => (string) ($referenceDocument->numero_control ?? ''),
            'totalRetencion' => $totalRetencion,
        ]);
    }

    public function buildRetentionLookupPayload(ElectronicDocument $referenceDocument): array
    {
        return $this->removeNulls([
            'serie' => (string) ($referenceDocument->serie ?? ''),
            'tipoDocumento' => (string) ($referenceDocument->tipo_documento ?: '01'),
            'numeroDocumento' => preg_replace('/\D+/', '', (string) ($referenceDocument->numero_documento ?? '')),
            'numeroControl' => (string) ($referenceDocument->numero_control ?? ''),
        ]);
    }

    public function buildRetentionPayloadFromReference(array $referenceDocument, array $retentionData, int $tenantId, array $options = []): array
    {
        $documentCurrency = $this->normalizeCurrency((string) ($referenceDocument['currency_code'] ?? config('services.thefactory_hka.default_currency', 'BSD')));
        $targetCurrency = $this->isBolivarCurrency($documentCurrency) ? 'BS' : $documentCurrency;
        $sourceCurrency = TenantCurrency::normalizeCurrencyCode((string) ($retentionData['currency_code'] ?? $targetCurrency));

        $baseAmount = (float) TenantCurrency::convertAmount(
            (float) ($retentionData['taxable_base'] ?? 0),
            $sourceCurrency,
            $targetCurrency,
            $tenantId
        );
        $retainedAmount = (float) TenantCurrency::convertAmount(
            (float) ($retentionData['retained_amount'] ?? 0),
            $sourceCurrency,
            $targetCurrency,
            $tenantId
        );

        $totalIva = max(0, (float) ($options['total_iva'] ?? 0));
        if ($totalIva > 0) {
            $totalIva = (float) TenantCurrency::convertAmount($totalIva, $sourceCurrency, $targetCurrency, $tenantId);
        }

        $totalIgtf = max(0, (float) ($options['total_igtf'] ?? 0));
        if ($totalIgtf > 0) {
            $totalIgtf = (float) TenantCurrency::convertAmount($totalIgtf, $sourceCurrency, $targetCurrency, $tenantId);
        }

        $certificateNumber = preg_replace('/[^A-Za-z0-9]/', '', (string) ($retentionData['certificate_number'] ?? ''));
        if ($certificateNumber === '') {
            $certificateNumber = preg_replace('/[^A-Za-z0-9]/', '', (string) ($retentionData['internal_number'] ?? 'RETENCION'));
        }

        $retentionType = Str::lower(trim((string) ($retentionData['retention_type'] ?? 'iva')));
        $totalRetencion = [
            'totalBaseImponible' => $this->formatAmount($baseAmount, 2),
            'numeroCompRetencion' => $certificateNumber,
            'fechaEmisionCR' => Carbon::parse((string) ($retentionData['retention_date'] ?? now()->toDateString()))->format('d/m/Y'),
            'totalRetenido' => $this->formatAmount($retainedAmount, 2),
        ];

        if ($retentionType === 'iva') {
            $totalRetencion['totalIVA'] = $this->formatAmount($totalIva > 0 ? $totalIva : $baseAmount, 2);
        } elseif ($retentionType === 'islr') {
            $totalRetencion['totalISRL'] = $this->formatAmount($retainedAmount, 2);
        }

        if ($totalIgtf > 0) {
            $totalRetencion['totalIGTF'] = $this->formatAmount($totalIgtf, 2);
        }

        return $this->removeNulls([
            'serie' => (string) ($referenceDocument['serie'] ?? ''),
            'tipoDocumento' => (string) ($referenceDocument['tipo_documento'] ?? '01'),
            'numeroDocumento' => preg_replace('/\D+/', '', (string) ($referenceDocument['numero_documento'] ?? '')),
            'numeroControl' => (string) ($referenceDocument['numero_control'] ?? ''),
            'totalRetencion' => $totalRetencion,
        ]);
    }

    public function buildRetentionLookupPayloadFromReference(array $referenceDocument): array
    {
        return $this->removeNulls([
            'serie' => (string) ($referenceDocument['serie'] ?? ''),
            'tipoDocumento' => (string) ($referenceDocument['tipo_documento'] ?? '01'),
            'numeroDocumento' => preg_replace('/\D+/', '', (string) ($referenceDocument['numero_documento'] ?? '')),
            'numeroControl' => (string) ($referenceDocument['numero_control'] ?? ''),
        ]);
    }

    public function buildPurchaseVatRetentionDocumentPayload(PurchaseVatRetention $retention, AccountPayable $accountPayable): array
    {
        $retention->loadMissing(['provider', 'tenant']);
        $accountPayable->loadMissing(['provider', 'tenant']);

        $provider = $retention->provider ?: $accountPayable->provider;
        $tenant = $retention->tenant ?: $accountPayable->tenant;
        $sourceCurrency = TenantCurrency::normalizeCurrencyCode((string) ($retention->currency_code ?: $accountPayable->currency_code ?: 'BSD'));
        $currencyContext = $this->buildPurchaseRetentionCurrencyContext(
            $tenant,
            [
                'amount_total' => (float) $accountPayable->amount_total,
                'taxable_base' => (float) $retention->taxable_base,
                'tax_amount' => (float) $retention->tax_amount,
                'retained_amount' => (float) $retention->retained_amount,
            ],
            $sourceCurrency
        );
        $documentNumber = $this->resolvePurchaseRetentionDocumentNumber((string) ($retention->certificate_number ?? $retention->id), $retention->retention_date ?? now());
        $certificateNumber = $this->resolveRetentionCertificateToken((string) ($retention->certificate_number ?? $retention->id));
        $transactionId = preg_replace('/[^A-Za-z0-9]/', '', 'SHOPIXRETIVA' . $retention->id . Str::upper(Str::random(6)));

        $line = [
            'numeroLinea' => '1',
            'fechaDocumento' => Carbon::parse((string) ($accountPayable->invoice_date ?? $accountPayable->issued_at ?? $retention->retention_date ?? now()))->format('d/m/Y'),
            'serieDocumento' => null,
            'tipoDocumento' => '01',
            'numeroDocumento' => preg_replace('/\D+/', '', (string) ($retention->invoice_number ?: $accountPayable->invoice_number ?: $accountPayable->document_number ?: '')),
            'numeroControl' => (string) ($retention->control_number ?: $accountPayable->control_number ?: ''),
            'tipoTransaccion' => null,
            'montoTotal' => $this->formatAmount($currencyContext['document']['amount_total'], 2),
            'montoExento' => $this->formatAmount(max(0, $currencyContext['document']['amount_total'] - $currencyContext['document']['taxable_base'] - $currencyContext['document']['tax_amount']), 2),
            'baseImponible' => $this->formatAmount($currencyContext['document']['taxable_base'], 2),
            'porcentaje' => $this->formatRetentionFraction((float) $retention->tax_amount, (float) $retention->taxable_base, (float) ($accountPayable->tax_rate ?? 0)),
            'porcentajeRetencion' => $this->formatFraction((float) $retention->retention_rate),
            'sustraendo' => null,
            'montoIVA' => $this->formatAmount($currencyContext['document']['tax_amount'], 2),
            'retenido' => $this->formatAmount($currencyContext['document']['retained_amount'], 2),
            'percibido' => $this->formatAmount(0, 2),
            'codigoConcepto' => null,
            'moneda' => $currencyContext['document_currency'],
            'infoAdicionalItem' => [[
                'campo' => 'Fafectada',
                'valor' => '',
            ]],
        ];

        $customPayload = [
            'encabezado' => [
                'identificacionDocumento' => [
                    'tipoDocumento' => '05',
                    'numeroDocumento' => $documentNumber,
                    'fechaEmision' => Carbon::parse((string) ($retention->retention_date ?? now()))->format('d/m/Y'),
                    'fechaVencimiento' => Carbon::parse((string) ($retention->retention_date ?? now()))->format('d/m/Y'),
                    'horaEmision' => strtolower(now()->format('h:i:s a')),
                    'anulado' => false,
                    'tipoDePago' => 'Contado',
                    'serie' => (string) config('services.thefactory_hka.default_serie', ''),
                    'sucursal' => (string) config('services.thefactory_hka.default_branch', '0001'),
                    'tipoDeVenta' => 'Interna',
                    'moneda' => $currencyContext['document_currency'],
                    'transaccionId' => $transactionId,
                ],
                'vendedor' => null,
                'comprador' => null,
                'sujetoRetenido' => $this->buildRetainedSubject($provider, $tenant),
                'tercero' => null,
                'totales' => [
                    'nroItems' => '1',
                    'montoGravadoTotal' => '0',
                    'montoExentoTotal' => '0',
                    'montoPercibidoTotal' => null,
                    'subtotalAntesDescuento' => null,
                    'totalDescuento' => '0.00',
                    'totalRecargos' => null,
                    'subtotal' => '0.00',
                    'totalIVA' => '0.00',
                    'montoTotalConIVA' => $this->formatAmount($currencyContext['document']['amount_total'], 2),
                    'totalAPagar' => '0.00',
                    'montoEnLetras' => 'N/A',
                    'listaRecargo' => null,
                    'listaDescBonificacion' => null,
                    'impuestosSubtotal' => null,
                    'otrosImpuestosSubtotal' => null,
                    'formasPago' => null,
                    'totalIGTF' => null,
                    'totalIGTF_VES' => null,
                    'montoTotalOTI' => null,
                    'montoTotalIVAyOTI' => null,
                ],
                'totalesRetencion' => [
                    'totalBaseImponible' => $this->formatAmount($currencyContext['document']['taxable_base'], 2),
                    'numeroCompRetencion' => $certificateNumber,
                    'fechaEmisionCR' => Carbon::parse((string) ($retention->retention_date ?? now()))->format('d/m/Y'),
                    'totalIVA' => $this->formatAmount($currencyContext['document']['tax_amount'], 2),
                    'totalRetenido' => $this->formatAmount($currencyContext['document']['retained_amount'], 2),
                    'totalISRL' => null,
                    'totalIGTF' => null,
                    'tipoComprobante' => null,
                ],
                'totalesOtraMoneda' => $currencyContext['totales_otra_moneda'],
                'orden' => null,
            ],
            'detallesItems' => null,
            'detallesRetencion' => [$line],
            'viajes' => null,
            'infoAdicional' => null,
            'guiaDespacho' => null,
            'transporte' => null,
            'esLote' => false,
            'esMinimo' => false,
        ];

        return $this->removeNulls($this->mergeWithBaseInvoicePayload($customPayload));
    }

    public function buildPurchaseIslrRetentionDocumentPayload(IslrWithholding $withholding, AccountPayable $accountPayable): array
    {
        $withholding->loadMissing(['provider', 'tenant', 'concept']);
        $accountPayable->loadMissing(['provider', 'tenant']);

        $provider = $withholding->provider ?: $accountPayable->provider;
        $tenant = $withholding->tenant ?: $accountPayable->tenant;
        $sourceCurrency = TenantCurrency::normalizeCurrencyCode((string) ($withholding->currency_code ?: $accountPayable->currency_code ?: 'BSD'));
        $currencyContext = $this->buildPurchaseRetentionCurrencyContext(
            $tenant,
            [
                'amount_total' => (float) $accountPayable->amount_total,
                'taxable_base' => (float) $withholding->base_amount,
                'tax_amount' => 0.0,
                'retained_amount' => (float) $withholding->retained_amount,
            ],
            $sourceCurrency
        );
        $documentNumber = $this->resolvePurchaseRetentionDocumentNumber((string) ($withholding->certificate_number ?? $withholding->id), $withholding->retention_date ?? now());
        $certificateNumber = $this->resolveRetentionCertificateToken((string) ($withholding->certificate_number ?? $withholding->id));
        $transactionId = preg_replace('/[^A-Za-z0-9]/', '', 'SHOPIXRETISLR' . $withholding->id . Str::upper(Str::random(6)));

        $line = [
            'numeroLinea' => '1',
            'fechaDocumento' => Carbon::parse((string) ($accountPayable->invoice_date ?? $accountPayable->issued_at ?? $withholding->payment_date ?? now()))->format('d/m/Y'),
            'serieDocumento' => null,
            'tipoDocumento' => '01',
            'numeroDocumento' => preg_replace('/\D+/', '', (string) ($withholding->invoice_number ?: $accountPayable->invoice_number ?: $accountPayable->document_number ?: '')),
            'numeroControl' => (string) ($withholding->control_number ?: $accountPayable->control_number ?: ''),
            'tipoTransaccion' => null,
            'montoTotal' => $this->formatAmount($currencyContext['document']['amount_total'], 2),
            'montoExento' => $this->formatAmount(max(0, $currencyContext['document']['amount_total'] - $currencyContext['document']['taxable_base']), 2),
            'baseImponible' => $this->formatAmount($currencyContext['document']['taxable_base'], 2),
            'porcentaje' => $this->formatFraction((float) $withholding->rate_percent),
            'porcentajeRetencion' => $this->formatFraction((float) $withholding->rate_percent),
            'sustraendo' => (float) ($withholding->sustraendo_amount ?? 0) > 0 ? $this->formatAmount((float) $withholding->sustraendo_amount, 2) : null,
            'montoIVA' => null,
            'retenido' => $this->formatAmount($currencyContext['document']['retained_amount'], 2),
            'percibido' => $this->formatAmount(0, 2),
            'codigoConcepto' => (string) ($withholding->concept->code ?? ''),
            'moneda' => $currencyContext['document_currency'],
            'infoAdicionalItem' => [[
                'campo' => 'Fafectada',
                'valor' => '',
            ]],
        ];

        $customPayload = [
            'encabezado' => [
                'identificacionDocumento' => [
                    'tipoDocumento' => '05',
                    'numeroDocumento' => $documentNumber,
                    'fechaEmision' => Carbon::parse((string) ($withholding->retention_date ?? now()))->format('d/m/Y'),
                    'fechaVencimiento' => Carbon::parse((string) ($withholding->retention_date ?? now()))->format('d/m/Y'),
                    'horaEmision' => strtolower(now()->format('h:i:s a')),
                    'anulado' => false,
                    'tipoDePago' => 'Contado',
                    'serie' => (string) config('services.thefactory_hka.default_serie', ''),
                    'sucursal' => (string) config('services.thefactory_hka.default_branch', '0001'),
                    'tipoDeVenta' => 'Interna',
                    'moneda' => $currencyContext['document_currency'],
                    'transaccionId' => $transactionId,
                ],
                'vendedor' => null,
                'comprador' => null,
                'sujetoRetenido' => $this->buildRetainedSubject($provider, $tenant),
                'tercero' => null,
                'totales' => [
                    'nroItems' => '1',
                    'montoGravadoTotal' => '0',
                    'montoExentoTotal' => '0',
                    'montoPercibidoTotal' => null,
                    'subtotalAntesDescuento' => null,
                    'totalDescuento' => '0.00',
                    'totalRecargos' => null,
                    'subtotal' => '0.00',
                    'totalIVA' => '0.00',
                    'montoTotalConIVA' => $this->formatAmount($currencyContext['document']['amount_total'], 2),
                    'totalAPagar' => '0.00',
                    'montoEnLetras' => 'N/A',
                    'listaRecargo' => null,
                    'listaDescBonificacion' => null,
                    'impuestosSubtotal' => null,
                    'otrosImpuestosSubtotal' => null,
                    'formasPago' => null,
                    'totalIGTF' => null,
                    'totalIGTF_VES' => null,
                    'montoTotalOTI' => null,
                    'montoTotalIVAyOTI' => null,
                ],
                'totalesRetencion' => [
                    'totalBaseImponible' => $this->formatAmount($currencyContext['document']['taxable_base'], 2),
                    'numeroCompRetencion' => $certificateNumber,
                    'fechaEmisionCR' => Carbon::parse((string) ($withholding->retention_date ?? now()))->format('d/m/Y'),
                    'totalIVA' => null,
                    'totalRetenido' => $this->formatAmount($currencyContext['document']['retained_amount'], 2),
                    'totalISRL' => $this->formatAmount($currencyContext['document']['retained_amount'], 2),
                    'totalIGTF' => null,
                    'tipoComprobante' => null,
                ],
                'totalesOtraMoneda' => $currencyContext['totales_otra_moneda'],
                'orden' => null,
            ],
            'detallesItems' => null,
            'detallesRetencion' => [$line],
            'viajes' => null,
            'infoAdicional' => null,
            'guiaDespacho' => null,
            'transporte' => null,
            'esLote' => false,
            'esMinimo' => false,
        ];

        return $this->removeNulls($this->mergeWithBaseInvoicePayload($customPayload));
    }

    public function getDocumentStatus(array $payload): array
    {
        return $this->request('post', '/api/EstadoDocumento', $payload);
    }

    public function getBatchStatus(array $payload): array
    {
        return $this->request('post', '/api/EstadoLote', $payload);
    }

    public function downloadDocument(array $payload): array
    {
        return $this->request('post', '/api/DescargaArchivo', $payload);
    }

    public function downloadDocumentFile(array $payload): array
    {
        $response = $this->downloadDocument($payload);
        $fileType = strtolower((string) ($payload['tipoArchivo'] ?? 'pdf'));
        $base64 = trim((string) Arr::get($response, 'data.archivo', ''));
        $providerCode = trim((string) Arr::get($response, 'data.codigo', Arr::get($response, 'data.Codigo', '')));
        $providerMessage = trim((string) Arr::get($response, 'data.mensaje', Arr::get($response, 'data.Mensaje', '')));

        if (!($response['ok'] ?? false)) {
            return $response + [
                'content' => null,
                'extension' => $fileType,
                'mime_type' => $this->resolveDownloadedMimeType($fileType),
            ];
        }

        if ($base64 === '') {
            $message = 'The Factory HKA no devolvió contenido para el archivo solicitado.';
            if ($providerMessage !== '') {
                $message = $providerMessage;
            }
            if ($providerCode !== '') {
                $message .= ' Código HKA: ' . $providerCode . '.';
            }

            return [
                'ok' => false,
                'status' => $response['status'] ?? 422,
                'message' => $message,
                'data' => $response['data'] ?? null,
                'raw' => $response['raw'] ?? null,
                'content' => null,
                'extension' => $fileType,
                'mime_type' => $this->resolveDownloadedMimeType($fileType),
            ];
        }

        $binary = base64_decode($base64, true);
        if ($binary === false) {
            return [
                'ok' => false,
                'status' => $response['status'] ?? 422,
                'message' => 'The Factory HKA devolvió un archivo inválido.',
                'data' => $response['data'] ?? null,
                'raw' => $response['raw'] ?? null,
                'content' => null,
                'extension' => $fileType,
                'mime_type' => $this->resolveDownloadedMimeType($fileType),
            ];
        }

        return $response + [
            'content' => $binary,
            'extension' => $fileType,
            'mime_type' => $this->resolveDownloadedMimeType($fileType),
        ];
    }

    public function sendDocumentByEmail(array $payload): array
    {
        return $this->request('post', '/api/Correo/Enviar', $payload);
    }

    public function trackEmail(array $payload): array
    {
        return $this->request('post', '/api/Correo/Rastreo', $payload);
    }

    public function sendOrderByEmail(array $payload): array
    {
        return $this->request('post', '/api/Correo/EnviaOrden', $payload);
    }

    public function trackOrderEmail(array $payload): array
    {
        return $this->request('post', '/api/Correo/RastreoOrden', $payload);
    }

    public function annulDocument(array $payload): array
    {
        return $this->request('post', '/api/Anular', $payload);
    }

    public function listNumerations(array $payload = []): array
    {
        return $this->request('post', '/api/ConsultaNumeraciones', $payload);
    }

    public function assignNumerations(array $payload): array
    {
        return $this->request('post', '/api/AsignarNumeraciones', $payload);
    }

    public function getLastDocument(array $payload): array
    {
        return $this->request('post', '/api/UltimoDocumento', $payload);
    }

    public function listAnnulledDocuments(array $payload): array
    {
        return $this->request('post', '/api/ListadoDocumentos', $payload);
    }

    public function listDocuments(array $payload): array
    {
        return $this->request('post', '/api/ListadoDocumentos', $payload);
    }

    public function listAssignments(array $payload): array
    {
        return $this->request('post', '/api/ListadoAsignaciones', $payload);
    }

    public function applyRetention(array $payload): array
    {
        return $this->request('post', '/api/AplicarRetencion', $payload);
    }

    public function getRetention(array $payload): array
    {
        return $this->request('get', '/api/AplicarRetencion', $payload);
    }

    public function deleteRetention(array $payload): array
    {
        return $this->request('delete', '/api/AplicarRetencion', $payload);
    }

    public function applyRelation(array $payload): array
    {
        return $this->request('post', '/api/Documentos/Relacionar', $payload);
    }

    public function getRelation(array $query): array
    {
        return $this->request('get', '/api/Documentos/Relacionar', $query);
    }

    public function deleteRelation(array $payload): array
    {
        return $this->request('delete', '/api/Documentos/Relacionar', $payload);
    }

    public function buildInvoicePayloadFromOrder(SalesOrder $order, array $options = []): array
    {
        $order->loadMissing(['user', 'tenant', 'details.variant.product.category', 'details.taxes.tax', 'payments.payment']);

        $requestedSerie = trim((string) ($options['serie'] ?? ''));
        $defaultSerie = trim((string) config('services.thefactory_hka.default_serie', ''));
        $serie = $requestedSerie !== '' ? $requestedSerie : $defaultSerie;
        $serieUpper = Str::upper($serie);
        if (in_array($serieUpper, ['TODOS', 'TODO', 'ALL'], true)) {
            $serie = '';
        }
        $tipoDocumento = trim((string) ($options['tipo_documento'] ?? config('services.thefactory_hka.default_document_type', '01')));
        $numeroDocumento = $this->resolveInvoiceDocumentNumber($order, $serie, $tipoDocumento, $options);

        $identification = $this->buildCustomerIdentification($order);
        $subtotal = (float) $order->details->sum('amount');
        $taxTotal = (float) $order->details->flatMap->taxes->sum('tax_amount');
        if ($taxTotal <= 0) {
            $taxTotal = (float) $order->details->sum(function ($item) {
                return (float) ($item->tax_amount ?? 0);
            });
        }

        $tenantBaseCurrency = $this->normalizeCurrency(TenantCurrency::resolveBaseCurrencyCode($order->tenant));
        $documentCurrency = $this->normalizeCurrency((string) ($options['moneda'] ?? config('services.thefactory_hka.default_currency', 'BSD')));
        $foreignCurrency = $this->normalizeCurrency((string) ($options['otra_moneda'] ?? $tenantBaseCurrency));
        $exchangeRate = $this->resolveExchangeRate($order, $options);
        $saleType = (string) ($options['tipo_de_venta'] ?? config('services.thefactory_hka.default_sale_type', 'Interna'));
        $paymentType = (string) ($options['tipo_de_pago'] ?? config('services.thefactory_hka.default_payment_type', 'Inmediato'));
        $branch = (string) ($options['sucursal'] ?? config('services.thefactory_hka.default_branch', '0001'));
        $transactionId = preg_replace('/[^A-Za-z0-9]/', '', (string) ($options['transaccion_id'] ?? ('SHOPIX' . $order->id . Str::upper(Str::random(8)))));
        if (!is_string($transactionId) || $transactionId === '') {
            $transactionId = 'SHOPIX' . $order->id . Str::upper(Str::random(6));
        }

        $emitOtherCurrency = $this->isBolivarCurrency($documentCurrency) && !$this->isBolivarCurrency($foreignCurrency);

        $customerIsFiscalExempt = $this->isGovernmentCustomer($identification);

        $detailItems = $order->details->values()->map(function ($item, $index) use ($emitOtherCurrency, $exchangeRate, $order, $customerIsFiscalExempt) {
            $lineTaxAmount = (float) $item->taxes->sum('tax_amount');
            if ($lineTaxAmount <= 0) {
                $lineTaxAmount = (float) ($item->tax_amount ?? 0);
            }

            if ($this->shouldForceExemptItem($order, $item, $customerIsFiscalExempt)) {
                $lineTaxAmount = 0.0;
            }

            $lineTaxRate = $this->resolveInvoiceTaxRateForDetail($item, $lineTaxAmount);
            if ($lineTaxAmount <= 0) {
                $lineTaxRate = 0.0;
            }
            $lineSubtotal = (float) $item->amount;
            $lineTotal = round($lineSubtotal + $lineTaxAmount, 2);
            $multiplier = $emitOtherCurrency ? $exchangeRate : 1;
            $isExempt = $lineTaxAmount <= 0;
            $lineBaseAmount = (float) ($item->line_subtotal_before_discount ?? 0);
            if ($lineBaseAmount <= 0) {
                $lineBaseAmount = $lineSubtotal + (float) ($item->line_discount_amount ?? 0);
            }

            if ($lineBaseAmount <= 0) {
                $lineBaseAmount = $lineSubtotal;
            }

            $lineDiscountAmount = round(max(0, $lineBaseAmount - $lineSubtotal), 2);
            $lineUnitDiscount = (float) ($item->quantity ?? 0) > 0
                ? round($lineDiscountAmount / max(1, (float) $item->quantity), 4)
                : 0.0;
            $lineUnitPriceStored = round(max(0, (float) ($item->price ?? 0)), 4);

            if ($lineUnitPriceStored <= 0 && (float) ($item->quantity ?? 0) > 0) {
                $lineUnitPriceStored = round($lineSubtotal / max(1, (float) $item->quantity), 4);
            }

            return [
                'numeroLinea' => (string) ($index + 1),
                'codigoPLU' => (string) ($item->variant->sku ?? $item->variant->barcode ?? ('ITEM-' . ($item->id ?? $index + 1))),
                'indicadorBienoServicio' => '1',
                'descripcion' => (string) ($item->variant->product->name ?? 'Producto'),
                'cantidad' => $this->formatPlainNumber($item->quantity, 4),
                'unidadMedida' => 'UND',
                'precioUnitario' => $this->formatAmount($lineUnitPriceStored * $multiplier, 4),
                'precioUnitarioDescuento' => $lineUnitDiscount > 0 ? $this->formatAmount($lineUnitDiscount * $multiplier, 2) : null,
                'montoBonificacion' => null,
                'descripcionBonificacion' => null,
                'descuentoMonto' => $this->formatAmount($lineDiscountAmount * $multiplier, 2),
                'recargoMonto' => $this->formatAmount(0, 2),
                'precioItem' => $this->formatAmount($lineSubtotal * $multiplier, 2),
                'precioAntesDescuento' => $this->formatAmount($lineBaseAmount * $multiplier, 2),
                'codigoImpuesto' => $isExempt ? 'E' : $this->resolveInvoiceTaxCodeByRate($lineTaxRate),
                'tasaIVA' => $lineTaxAmount > 0 && $lineTaxRate > 0 ? $this->formatPlainNumber($lineTaxRate, 2) : null,
                'valorIVA' => $lineTaxAmount > 0 ? $this->formatAmount($lineTaxAmount * $multiplier, 2) : null,
                'valorTotalItem' => $this->formatAmount($lineTotal * $multiplier, 2),
                'infoAdicionalItem' => [],
                'listaItemOTI' => null,
            ];
        })->all();

        $subtotalBeforeDiscount = (float) ($order->subtotal_before_discount ?? 0);
        $totalDiscount = (float) ($order->total_discount ?? 0);

        if ($subtotalBeforeDiscount <= 0) {
            $subtotalBeforeDiscount = $subtotal + max(0, $totalDiscount);
        }

        if ($totalDiscount <= 0) {
            $totalDiscount = round(max(0, $subtotalBeforeDiscount - $subtotal), 2);
        }

        $taxSummary = $this->summarizeInvoiceTaxes($order->details, function ($item) use ($order, $customerIsFiscalExempt) {
            return $this->shouldForceExemptItem($order, $item, $customerIsFiscalExempt);
        });
        $taxableBaseTotal = (float) $taxSummary['taxable_base_total'];
        $exemptTotal = (float) $taxSummary['exempt_total'];
        $taxTotal = round((float) collect($taxSummary['groups'])->sum('amount'), 2);
        $montoTotalConIva = round($subtotal + $taxTotal, 2);
        $igtfBaseAmount = $this->resolveInvoiceIgtfBaseAmount($order, $tenantBaseCurrency);
        $igtfTotal = $this->resolveInvoiceIgtfTotal($order, $tenantBaseCurrency);
        $documentIgtfBaseAmount = $emitOtherCurrency ? round($igtfBaseAmount * $exchangeRate, 2) : $igtfBaseAmount;
        $documentIgtfTotal = $emitOtherCurrency ? round($igtfTotal * $exchangeRate, 2) : $igtfTotal;
        $igtfTotalVes = $this->isBolivarCurrency($documentCurrency)
            ? $documentIgtfTotal
            : ($exchangeRate > 0 ? round($igtfTotal * $exchangeRate, 2) : null);
        $totalPagar = round($montoTotalConIva + $igtfTotal, 2);
        $documentTaxableBaseTotal = $emitOtherCurrency ? round($taxableBaseTotal * $exchangeRate, 2) : $taxableBaseTotal;
        $documentExemptTotal = $emitOtherCurrency ? round($exemptTotal * $exchangeRate, 2) : $exemptTotal;
        $documentSubtotal = $emitOtherCurrency ? round($subtotal * $exchangeRate, 2) : $subtotal;
        $documentSubtotalBeforeDiscount = $emitOtherCurrency ? round($subtotalBeforeDiscount * $exchangeRate, 2) : $subtotalBeforeDiscount;
        $documentTotalDiscount = $emitOtherCurrency ? round($totalDiscount * $exchangeRate, 2) : $totalDiscount;
        $documentTaxTotal = $emitOtherCurrency ? round($taxTotal * $exchangeRate, 2) : $taxTotal;
        $documentMontoTotalConIva = $emitOtherCurrency ? round($montoTotalConIva * $exchangeRate, 2) : $montoTotalConIva;
        $documentTotalPagar = round($documentMontoTotalConIva + $documentIgtfTotal, 2);
        $taxesSubtotal = $this->buildInvoiceTaxSubtotalRows($taxSummary, $emitOtherCurrency ? $exchangeRate : 1);
        $foreignTaxesSubtotal = $this->buildInvoiceTaxSubtotalRows($taxSummary, 1);

        if ($documentIgtfTotal > 0) {
            $igtfRate = round(max((float) ($order->igtf_rate ?? 0), 3.0), 2);
            $taxesSubtotal[] = [
                'codigoTotalImp' => 'IGTF',
                'alicuotaImp' => $this->formatPlainNumber($igtfRate, 2),
                'baseImponibleImp' => $this->formatAmount($documentIgtfBaseAmount, 2),
                'valorTotalImp' => $this->formatAmount($documentIgtfTotal, 2),
            ];
            $foreignTaxesSubtotal[] = [
                'codigoTotalImp' => 'IGTF',
                'alicuotaImp' => $this->formatPlainNumber($igtfRate, 2),
                'baseImponibleImp' => $this->formatAmount($igtfBaseAmount, 2),
                'valorTotalImp' => $this->formatAmount($igtfTotal, 2),
            ];
        }

        $payments = $order->payments
            ->filter(function ($payment) {
                $reference = Str::upper(trim((string) ($payment->reference ?? '')));
                $paymentName = Str::lower(trim((string) ($payment->payment->name ?? '')));

                if (str_starts_with($reference, 'RET:')) {
                    return false;
                }

                if ($paymentName === 'retencion fiscal') {
                    return false;
                }

                return (int) ($payment->status ?? 0) === 1;
            })
            ->map(function ($payment) use ($documentCurrency, $exchangeRate, $emitOtherCurrency, $order) {
            $paymentCurrency = $this->normalizeCurrency((string) ($documentCurrency));
            $paymentAmount = $emitOtherCurrency ? ((float) $payment->amount * $exchangeRate) : (float) $payment->amount;
            $sourceCurrency = $this->normalizeCurrency((string) ($payment->currency ?? $documentCurrency));
            $sourceAmount = round((float) ($payment->amount_original ?? 0), 2);
            if ($sourceAmount <= 0) {
                $sourceAmount = round((float) ($payment->amount ?? 0), 2);
            }
            $paymentDescription = (string) ($payment->payment->name ?? 'Pago tienda');

            if (
                $emitOtherCurrency
                && $sourceCurrency !== ''
                && !$this->isBolivarCurrency($sourceCurrency)
                && $sourceAmount > 0
            ) {
                $paymentCurrency = $sourceCurrency;
                $paymentAmount = $sourceAmount;
            }

            if ($sourceCurrency !== $paymentCurrency && $sourceAmount > 0) {
                $paymentDescription .= sprintf(
                    ' (pagado en %s %s, TC %s)',
                    $sourceCurrency,
                    $this->formatAmount($sourceAmount, 2),
                    $this->formatAmount(max($exchangeRate, 0.00000001), 4)
                );
            }

            $payment = [
                'descripcion' => $paymentDescription,
                'fecha' => Carbon::parse((string) ($order->date ?? now()->toDateString()))->format('d/m/Y'),
                'forma' => $this->mapPaymentFormCode((string) ($payment->payment->name ?? '')),
                'monto' => $this->formatAmount($paymentAmount, 2),
                'moneda' => $paymentCurrency,
            ];

            if (!$this->isBsExactCurrency($paymentCurrency)) {
                $payment['tipoCambio'] = $this->formatAmount(max($exchangeRate, 0.00000001), 4);
            }

                return $payment;
            })->values()->all();

        if (empty($payments)) {
            $payments = [[
                'descripcion' => 'Pago tienda',
                'fecha' => Carbon::parse((string) ($order->date ?? now()->toDateString()))->format('d/m/Y'),
                'forma' => '99',
                'monto' => $this->formatAmount($documentTotalPagar, 2),
                'moneda' => $documentCurrency,
            ]];

            if (!$this->isBsExactCurrency($documentCurrency)) {
                $payments[0]['tipoCambio'] = $this->formatAmount($emitOtherCurrency ? 0 : max($exchangeRate, 0.00000001), 4);
            }
        }

        $payments = $this->rebalancePayments($payments, $documentTotalPagar, $documentCurrency);

        $infoAdicional = [];
        if ($subtotalBeforeDiscount > 0 || $totalDiscount > 0) {
            $infoAdicional = [
                [
                    'campo' => 'SHOPIX_SUBTOTAL_ANTES_DESC',
                    'valor' => $this->formatAmount($documentSubtotalBeforeDiscount, 2) . ' ' . $documentCurrency,
                ],
                [
                    'campo' => 'SHOPIX_TOTAL_DESCUENTO',
                    'valor' => $this->formatAmount($documentTotalDiscount, 2) . ' ' . $documentCurrency,
                ],
                [
                    'campo' => 'SHOPIX_SUBTOTAL_NETO',
                    'valor' => $this->formatAmount($documentSubtotal, 2) . ' ' . $documentCurrency,
                ],
            ];
        }

        $isDeliveryOrder = Str::lower(trim((string) ($order->preference ?? ''))) === 'delivery';
        $isDispatchGuideDocument = in_array($tipoDocumento, ['04', '06'], true);
        $customPayload = [
            'encabezado' => [
                'identificacionDocumento' => [
                    'tipoDocumento' => $tipoDocumento,
                    'numeroDocumento' => $numeroDocumento !== '' ? $numeroDocumento : null,
                    'fechaEmision' => now()->format('d/m/Y'),
                    'fechaVencimiento' => Carbon::parse((string) ($order->date ?? now()->toDateString()))->format('d/m/Y'),
                    'horaEmision' => strtolower(now()->format('h:i:s a')),
                    'anulado' => false,
                    'serie' => $serie,
                    'sucursal' => $branch,
                    'tipoDePago' => $paymentType,
                    'tipoDeVenta' => $saleType,
                    'moneda' => $documentCurrency,
                    'transaccionId' => $transactionId,
                    'urlPdf' => null,
                ],
                'vendedor' => null,
                'comprador' => [
                    'tipoIdentificacion' => $identification['tipo_identificacion'],
                    'numeroIdentificacion' => $identification['numero_identificacion'],
                    'razonSocial' => (string) ($order->user->name ?? 'Consumidor final'),
                    'direccion' => (string) (($order->address && trim((string) $order->address) !== '') ? $order->address : ($order->tenant->address ?? 'N/A')),
                    'ubigeo' => null,
                    'pais' => (string) ($options['pais'] ?? 'VE'),
                    'notificar' => null,
                    'telefono' => !empty($identification['telefono']) ? [$identification['telefono']] : null,
                    'correo' => !empty($order->user->email) ? [$order->user->email] : null,
                    'otrosEnvios' => null,
                ],
                'totales' => [
                    'nroItems' => (string) count($detailItems),
                    'montoGravadoTotal' => $this->formatAmount($documentTaxableBaseTotal, 2),
                    'montoExentoTotal' => $this->formatAmount($documentExemptTotal, 2),
                    'montoPercibidoTotal' => $this->formatAmount(0, 2),
                    'subtotalAntesDescuento' => $documentTotalDiscount > 0 ? null : $this->formatAmount($documentSubtotalBeforeDiscount, 2),
                    'totalDescuento' => null,
                    'totalRecargos' => null,
                    'subtotal' => $this->formatAmount($documentSubtotal, 2),
                    'totalIVA' => $this->formatAmount($documentTaxTotal, 2),
                    'montoTotalConIVA' => $this->formatAmount($documentMontoTotalConIva, 2),
                    'totalAPagar' => $this->formatAmount($documentTotalPagar, 2),
                    'montoEnLetras' => null,
                    'listaRecargo' => null,
                    'listaDescBonificacion' => $documentTotalDiscount > 0 ? [[
                        'descDescuento' => 'Descuento promocional Shopix',
                        'montoDescuento' => $this->formatAmount($documentTotalDiscount, 2),
                    ]] : null,
                    'impuestosSubtotal' => $taxesSubtotal,
                    'otrosImpuestosSubtotal' => null,
                    'formasPago' => $payments,
                    'totalIGTF' => $documentIgtfTotal > 0 ? $this->formatAmount($documentIgtfTotal, 2) : null,
                    'totalIGTF_VES' => $igtfTotalVes !== null && $igtfTotalVes > 0 ? $this->formatAmount($igtfTotalVes, 2) : null,
                    'montoTotalOTI' => null,
                    'montoTotalIVAyOTI' => null,
                ],
                'totalesRetencion' => null,
                'totalesOtraMoneda' => $emitOtherCurrency ? [
                    'moneda' => $foreignCurrency,
                    'tipoCambio' => $this->formatAmount(max($exchangeRate, 0.00000001), 4),
                    'montoGravadoTotal' => $this->formatAmount($taxableBaseTotal, 2),
                    'montoPercibidoTotal' => $this->formatAmount(0, 2),
                    'montoExentoTotal' => $this->formatAmount($exemptTotal, 2),
                    'subtotal' => $this->formatAmount($subtotal, 2),
                    'totalIVA' => $this->formatAmount($taxTotal, 2),
                    'montoTotalConIVA' => $this->formatAmount($montoTotalConIva, 2),
                    'totalAPagar' => $this->formatAmount($totalPagar, 2),
                    'montoEnLetras' => null,
                    'subtotalAntesDescuento' => $totalDiscount > 0 ? null : $this->formatAmount($subtotalBeforeDiscount, 2),
                    'totalDescuento' => null,
                    'totalRecargos' => null,
                    'listaRecargo' => null,
                    'listaDescBonificacion' => $totalDiscount > 0 ? [[
                        'descDescuento' => 'Descuento promocional Shopix',
                        'montoDescuento' => $this->formatAmount($totalDiscount, 2),
                    ]] : null,
                    'impuestosSubtotal' => $foreignTaxesSubtotal,
                    'otrosImpuestosSubtotal' => null,
                    'totalIGTF' => $igtfTotal > 0 ? $this->formatAmount($igtfTotal, 2) : null,
                    'totalIGTF_VES' => $igtfTotalVes !== null && $igtfTotalVes > 0 ? $this->formatAmount($igtfTotalVes, 2) : null,
                    'montoTotalOTI' => null,
                    'montoTotalIVAyOTI' => null,
                ] : null,
                'orden' => null,
            ],
            'detallesItems' => $detailItems,
            'detallesRetencion' => null,
            'viajes' => null,
            'infoAdicional' => $infoAdicional,
            'guiaDespacho' => ($isDispatchGuideDocument || $isDeliveryOrder) ? [
                'esGuiaDespacho' => '1',
                'motivoTraslado' => 'Despacho de mercancia',
                'descripcionServicio' => 'Entrega de venta Shopix',
                'destinoProducto' => (string) ($order->address ?? 'N/A'),
            ] : null,
            'transporte' => null,
            'esLote' => null,
            'esMinimo' => null,
        ];

        $payload = $this->mergeWithBaseInvoicePayload($customPayload);

        return $this->removeNulls($payload);
    }

    private function summarizeInvoiceTaxes(Collection $details, ?callable $forceExemptResolver = null): array
    {
        $summary = [
            'taxable_base_total' => 0.0,
            'exempt_total' => 0.0,
            'groups' => [],
        ];

        foreach ($details as $item) {
            $lineBaseAmount = round((float) ($item->amount ?? 0), 2);
            $forceExempt = $forceExemptResolver ? (bool) $forceExemptResolver($item) : false;

            if ($forceExempt) {
                $summary['exempt_total'] += $lineBaseAmount;
                continue;
            }

            $lineTaxes = $item->taxes ?? collect();
            $lineTaxTotal = (float) $lineTaxes->sum('tax_amount');

            if ($lineTaxTotal <= 0) {
                $lineTaxTotal = (float) ($item->tax_amount ?? 0);
            }

            if ($lineTaxTotal <= 0) {
                $summary['exempt_total'] += $lineBaseAmount;
                continue;
            }

            $summary['taxable_base_total'] += $lineBaseAmount;

            if ($lineTaxes->isNotEmpty()) {
                foreach ($lineTaxes as $tax) {
                    $taxAmount = round((float) ($tax->tax_amount ?? 0), 2);
                    if ($taxAmount <= 0) {
                        continue;
                    }

                    $rate = $this->resolveInvoiceTaxRateValue((float) ($tax->tax_rate ?? 0), $lineBaseAmount, $taxAmount);
                    $key = number_format($rate, 2, '.', '');

                    if (!isset($summary['groups'][$key])) {
                        $summary['groups'][$key] = [
                            'rate' => $rate,
                            'base' => 0.0,
                            'amount' => 0.0,
                        ];
                    }

                    $summary['groups'][$key]['base'] += $lineBaseAmount;
                    $summary['groups'][$key]['amount'] += $taxAmount;
                }

                continue;
            }

            $rate = $this->resolveInvoiceTaxRateForDetail($item, $lineTaxTotal);
            $key = number_format($rate, 2, '.', '');

            if (!isset($summary['groups'][$key])) {
                $summary['groups'][$key] = [
                    'rate' => $rate,
                    'base' => 0.0,
                    'amount' => 0.0,
                ];
            }

            $summary['groups'][$key]['base'] += $lineBaseAmount;
            $summary['groups'][$key]['amount'] += $lineTaxTotal;
        }

        $summary['taxable_base_total'] = round((float) $summary['taxable_base_total'], 2);
        $summary['exempt_total'] = round((float) $summary['exempt_total'], 2);
        $summary['groups'] = collect($summary['groups'])
            ->sortBy('rate')
            ->map(function (array $group) {
                $group['base'] = round((float) $group['base'], 2);
                $group['amount'] = round((float) $group['amount'], 2);

                return $group;
            })
            ->values()
            ->all();

        return $summary;
    }

    private function buildInvoiceTaxSubtotalRows(array $summary, float $multiplier = 1): array
    {
        $rows = collect($summary['groups'] ?? [])->map(function (array $group) use ($multiplier) {
            $rate = (float) ($group['rate'] ?? 0);

            return [
                'codigoTotalImp' => $this->resolveInvoiceTaxCodeByRate($rate),
                'alicuotaImp' => $this->formatPlainNumber($rate, 2),
                'baseImponibleImp' => $this->formatAmount((float) $group['base'] * $multiplier, 2),
                'valorTotalImp' => $this->formatAmount((float) $group['amount'] * $multiplier, 2),
            ];
        })->values();

        if ((float) ($summary['exempt_total'] ?? 0) > 0) {
            $rows->push([
                'codigoTotalImp' => 'E',
                'baseImponibleImp' => $this->formatAmount((float) $summary['exempt_total'] * $multiplier, 2),
                'valorTotalImp' => $this->formatAmount(0, 2),
            ]);
        }

        return $rows->all();
    }

    private function resolveInvoiceTaxRateForDetail($item, float $lineTaxAmount): float
    {
        $detailRate = (float) ($item->taxes->first()->tax_rate ?? ($item->tax_rate ?? 0));
        $lineBaseAmount = (float) ($item->amount ?? 0);

        return $this->resolveInvoiceTaxRateValue($detailRate, $lineBaseAmount, $lineTaxAmount);
    }

    private function resolveInvoiceTaxRateValue(float $rate, float $baseAmount, float $taxAmount): float
    {
        if ($rate > 0) {
            return round($rate, 2);
        }

        if ($baseAmount > 0 && $taxAmount > 0) {
            return round(($taxAmount / $baseAmount) * 100, 2);
        }

        return 0.0;
    }

    private function resolveInvoiceTaxCodeByRate(float $rate): string
    {
        $normalizedRate = round($rate, 2);

        if ($normalizedRate <= 0) {
            return 'E';
        }

        if (abs($normalizedRate - 8.0) < 0.01) {
            return 'R';
        }

        if (abs($normalizedRate - 16.0) < 0.01) {
            return 'G';
        }

        if ($normalizedRate >= 24.0) {
            return 'A';
        }

        return 'G';
    }

    private function resolveInvoiceIgtfTotal(SalesOrder $order, string $tenantBaseCurrency): float
    {
        if (!(bool) ($order->tenant->electronic_invoicing_enabled ?? false) || !(bool) ($order->tenant->special_taxpayer ?? false)) {
            return 0.0;
        }

        $persistedIgtfAmount = (float) ($order->igtf_amount ?? 0);
        if ($persistedIgtfAmount > 0) {
            return round($persistedIgtfAmount, 2);
        }

        $igtfRate = (float) (Tax::query()
            ->whereRaw('(LOWER(name) = ? OR LOWER(name) LIKE ?)', ['igtf', '%igtf%'])
            ->where(function ($query) {
                $query->whereNull('is_active')->orWhere('is_active', 1);
            })
            ->value('rate') ?? 0);

        if ($igtfRate <= 0) {
            $igtfRate = 3.0;
        }

        if ($igtfRate <= 0) {
            return 0.0;
        }

        $orderTaxTotal = (float) $order->details->flatMap->taxes->sum('tax_amount');
        $totalWithoutIgtf = round((float) $order->gross_total + $orderTaxTotal, 2);

        $directForeignCurrencyPayments = (float) $order->payments
            ->where('status', 1)
            ->filter(function ($payment) {
                $isForeignCurrency = $this->isForeignCurrencyForIgtf((string) ($payment->currency ?? ''));
                if (!$isForeignCurrency) {
                    return false;
                }

                return true;
            })
            ->sum(function ($payment) {
                return (float) ($payment->amount_base ?? $payment->amount ?? 0);
            });

        if ($directForeignCurrencyPayments <= 0) {
            return 0.0;
        }

        $igtfBaseAmount = round(min(max(0, $directForeignCurrencyPayments), max(0, $totalWithoutIgtf)), 2);

        return round($igtfBaseAmount * ($igtfRate / 100), 2);
    }

    private function resolveInvoiceIgtfBaseAmount(SalesOrder $order, string $tenantBaseCurrency): float
    {
        if (!(bool) ($order->tenant->electronic_invoicing_enabled ?? false) || !(bool) ($order->tenant->special_taxpayer ?? false)) {
            return 0.0;
        }

        $persistedBaseAmount = (float) ($order->igtf_base_amount ?? 0);
        if ($persistedBaseAmount > 0) {
            return round($persistedBaseAmount, 2);
        }

        $orderTaxTotal = (float) $order->details->flatMap->taxes->sum('tax_amount');
        $totalWithoutIgtf = round((float) $order->gross_total + $orderTaxTotal, 2);

        $directForeignCurrencyPayments = (float) $order->payments
            ->where('status', 1)
            ->filter(function ($payment) {
                return $this->isForeignCurrencyForIgtf((string) ($payment->currency ?? ''));
            })
            ->sum(function ($payment) {
                return (float) ($payment->amount_base ?? $payment->amount ?? 0);
            });

        if ($directForeignCurrencyPayments <= 0) {
            return 0.0;
        }

        return round(min(max(0, $directForeignCurrencyPayments), max(0, $totalWithoutIgtf)), 2);
    }

    private function resolveAdjustmentNoteIgtfRate(
        SalesAdjustmentNote $note,
        SalesOrder $order,
        ElectronicDocument $referenceDocument
    ): float {
        $referenceRate = (float) str_replace(',', '.', (string) Arr::get(
            $referenceDocument->request_payload ?? [],
            'encabezado.totales.impuestosSubtotal.1.alicuotaImp',
            0
        ));

        if ($referenceRate > 0) {
            return round($referenceRate, 2);
        }

        $orderIgtfAmount = round((float) ($order->igtf_amount ?? 0), 2);
        $orderIgtfBaseAmount = round((float) ($order->igtf_base_amount ?? 0), 2);
        if ($orderIgtfAmount > 0 && $orderIgtfBaseAmount > 0) {
            return round(($orderIgtfAmount / $orderIgtfBaseAmount) * 100, 2);
        }

        return 3.0;
    }

    private function resolveAdjustmentNoteIgtfBaseAmount(
        SalesAdjustmentNote $note,
        SalesOrder $order,
        ElectronicDocument $referenceDocument,
        float $igtfAmount,
        float $igtfRate
    ): float {
        if ($igtfAmount <= 0 || $igtfRate <= 0) {
            return 0.0;
        }

        $referenceIgtfAmount = (float) str_replace(',', '.', (string) Arr::get(
            $referenceDocument->request_payload ?? [],
            'encabezado.totales.totalIGTF',
            0
        ));
        $referenceIgtfBaseAmount = (float) str_replace(',', '.', (string) Arr::get(
            $referenceDocument->request_payload ?? [],
            'encabezado.totales.impuestosSubtotal.1.baseImponibleImp',
            0
        ));

        if ($referenceIgtfAmount > 0 && $referenceIgtfBaseAmount > 0 && abs($igtfAmount - $referenceIgtfAmount) < 0.01) {
            return round($referenceIgtfBaseAmount, 2);
        }

        $orderIgtfAmount = round((float) ($order->igtf_amount ?? 0), 2);
        $orderIgtfBaseAmount = round((float) ($order->igtf_base_amount ?? 0), 2);
        if ($orderIgtfAmount > 0 && $orderIgtfBaseAmount > 0) {
            $ratioBase = $igtfAmount / $orderIgtfAmount;
            return round($orderIgtfBaseAmount * $ratioBase, 2);
        }

        return round($igtfAmount / ($igtfRate / 100), 2);
    }

    private function paymentNameSuggestsIgtfEligibility(string $paymentName): bool
    {
        $name = Str::lower(trim($paymentName));
        if ($name === '') {
            return false;
        }

        $blocked = [
            'pago movil', 'pago móvil', 'punto de venta', 'tarjeta', 'credito', 'crédito',
            'debito', 'débito', 'transferencia nacional', 'nacional', 'bs', 'bolivar', 'bolívar',
        ];

        if (Str::contains($name, $blocked)) {
            return false;
        }

        $allowed = [
            'efectivo', 'cash', 'zelle', 'wire', 'swift', 'international', 'internacional',
            'transferencia extranjera', 'transferencia internacional', 'foreign',
        ];

        return Str::contains($name, $allowed);
    }

    private function normalizeCheckoutCurrencyCode(?string $currencyCode): string
    {
        $code = strtoupper(trim((string) $currencyCode));

        if (in_array($code, ['BS', 'VES', 'VED', 'VEF', 'BOLIVAR', 'BOLIVARES'], true)) {
            return 'BS';
        }

        return $code;
    }

    private function isGovernmentCustomer(array $identification): bool
    {
        return strtoupper((string) ($identification['tipo_identificacion'] ?? '')) === 'G';
    }

    private function shouldForceExemptItem(SalesOrder $order, $item, bool $customerIsFiscalExempt): bool
    {
        if ($customerIsFiscalExempt) {
            return true;
        }

        $product = $item->variant->product ?? null;
        $category = $product->category ?? null;

        foreach ([$item, $product, $category] as $entity) {
            if (!$entity) {
                continue;
            }

            if ($this->hasTruthyAttribute($entity, ['is_exempt', 'tax_exempt', 'vat_exempt', 'is_tax_exempt'])) {
                return true;
            }
        }

        return false;
    }

    private function hasTruthyAttribute($model, array $keys): bool
    {
        if (!method_exists($model, 'getAttributes')) {
            return false;
        }

        $attributes = $model->getAttributes();
        foreach ($keys as $key) {
            if (array_key_exists($key, $attributes) && (bool) $attributes[$key]) {
                return true;
            }
        }

        return false;
    }

    private function isForeignCurrencyForIgtf(?string $currencyCode): bool
    {
        $code = $this->normalizeCheckoutCurrencyCode($currencyCode);

        return $code !== '' && $code !== 'BS';
    }

    private function request(string $method, string $path, array $payload = []): array
    {
        if (!$this->isConfigured()) {
            return [
                'ok' => false,
                'message' => 'La integración The Factory HKA no está configurada en variables de entorno.',
                'data' => null,
                'raw' => null,
            ];
        }

        try {
            $http = Http::timeout($this->timeout)
                ->acceptJson()
                ->asJson()
                ->withOptions(['verify' => $this->verifySsl]);

            if (!str_contains($path, '/api/Autenticacion')) {
                $token = $this->getAuthToken();

                if (!$token) {
                    return [
                        'ok' => false,
                        'message' => 'No fue posible autenticarse en The Factory HKA.',
                        'data' => null,
                        'raw' => null,
                    ];
                }

                $http = $http->withToken($token);

                if (!empty($payload)) {
                    $http = $http->withHeaders([
                        'X-Shopix-Signature' => $this->buildPayloadSignature($payload),
                        'X-Shopix-Signature-Alg' => 'HMAC-SHA256',
                    ]);
                }
            }

            $url = $this->baseUrl . $path;
            $method = strtolower($method);

            if ($method === 'get') {
                if (!empty($payload) && $this->shouldSendGetRequestBody($path)) {
                    $response = $http->withBody(json_encode($payload), 'application/json')->send('GET', $url);
                } else {
                    $response = $http->get($url, $payload);
                }
            } elseif ($method === 'delete') {
                $response = $http->withBody(json_encode($payload), 'application/json')->delete($url);
            } else {
                $response = $http->{$method}($url, $payload);
            }

            $json = $response->json();
            $ok = $response->successful() && $this->isApiResponseSuccessful($json);
            $apiMessage = is_array($json) ? trim((string) Arr::get($json, 'mensaje', '')) : '';
            $validationMessages = is_array($json)
                ? collect(Arr::get($json, 'validaciones', []))
                    ->filter(fn ($message) => is_scalar($message) && trim((string) $message) !== '')
                    ->map(fn ($message) => trim((string) $message))
                    ->values()
                    ->all()
                : [];

            $messageParts = [];
            if ($apiMessage !== '') {
                $messageParts[] = $apiMessage;
            }
            if (!empty($validationMessages)) {
                $messageParts[] = implode(' | ', $validationMessages);
            }

            $message = implode(': ', $messageParts);
            if ($message === '') {
                if ($ok) {
                    $message = 'OK';
                } else {
                    $status = (int) $response->status();
                    $rawBody = trim((string) $response->body());
                    $message = 'Error de integración';

                    if ($status > 0) {
                        $message .= ' HTTP ' . $status . '.';
                    }

                    if ($rawBody !== '') {
                        $message .= ' Respuesta: ' . Str::limit($rawBody, 300);
                    }
                }
            }

            return [
                'ok' => $ok,
                'status' => $response->status(),
                'message' => $message,
                'data' => is_array($json) ? $json : [],
                'raw' => $response->body(),
            ];
        } catch (\Throwable $e) {
            return [
                'ok' => false,
                'message' => 'Error al conectar con The Factory HKA: ' . $e->getMessage(),
                'data' => null,
                'raw' => null,
            ];
        }
    }

    private function resolveDownloadedMimeType(string $fileType): string
    {
        return match (strtolower($fileType)) {
            'xml' => 'application/xml',
            'json' => 'application/json',
            default => 'application/pdf',
        };
    }

    private function shouldSendGetRequestBody(string $path): bool
    {
        return in_array($path, [
            '/api/AplicarRetencion',
        ], true);
    }

    private function getAuthToken(): ?string
    {
        $cacheKey = 'tfhka.auth.token';
        $cachedToken = Cache::get($cacheKey);

        if (is_string($cachedToken) && $cachedToken !== '') {
            return $cachedToken;
        }

        try {
            $authPayloads = [
                [
                    'usuario' => $this->username,
                    'clave' => $this->password,
                ],
                [
                    'tokenUsuario' => $this->username,
                    'tokenPassword' => $this->password,
                ],
                [
                    'TokenUsuario' => $this->username,
                    'TokenPassword' => $this->password,
                ],
            ];

            foreach ($authPayloads as $payload) {
                $response = Http::timeout($this->timeout)
                    ->acceptJson()
                    ->asJson()
                    ->withOptions(['verify' => $this->verifySsl])
                    ->post($this->baseUrl . '/api/Autenticacion', $payload);

                $json = $response->json();
                $token = (string) Arr::get($json, 'token', '');

                if ($response->successful() && $token !== '') {
                    $expiration = Arr::get($json, 'expiracion');
                    $minutes = 30;

                    if ($expiration) {
                        try {
                            $expiresAt = Carbon::parse((string) $expiration);
                            $minutes = max(1, now()->diffInMinutes($expiresAt, false) - 1);
                        } catch (\Throwable $e) {
                            $minutes = 30;
                        }
                    }

                    Cache::put($cacheKey, $token, now()->addMinutes($minutes));
                    return $token;
                }
            }
        } catch (\Throwable $e) {
            return null;
        }

        return null;
    }

    private function isApiResponseSuccessful($json): bool
    {
        if (!is_array($json)) {
            return false;
        }

        $mensaje = Str::lower(trim((string) Arr::get($json, 'mensaje', '')));
        if ($mensaje !== '' && str_contains($mensaje, 'no procesado')) {
            return false;
        }

        $codigo = Arr::get($json, 'codigo');
        if (is_int($codigo)) {
            return $codigo >= 200 && $codigo < 300;
        }

        $codigoString = trim((string) $codigo);
        if ($codigoString === '') {
            return true;
        }

        if (preg_match('/^2\d\d$/', $codigoString)) {
            return true;
        }

        return in_array(Str::lower($codigoString), ['ok', '0', '00', 'success', 'exito'], true);
    }

    private function buildCustomerIdentification(SalesOrder $order): array
    {
        $rawDni = trim((string) ($order->user->dni ?? ''));
        $phone = preg_replace('/\D+/', '', (string) ($order->user->phone_number ?? ''));

        $tipo = 'V';
        if ($rawDni !== '' && preg_match('/^([VJEPGC])/i', $rawDni, $matches)) {
            $tipo = strtoupper($matches[1]);
        }

        $numero = preg_replace('/\D+/', '', $rawDni);
        if ($numero === '') {
            $numero = (string) ($order->user_id ?? $order->id);
        }

        return [
            'tipo_identificacion' => $tipo,
            'numero_identificacion' => $numero,
            'telefono' => $phone,
        ];
    }

    private function formatAmount($value, int $decimals = 2): string
    {
        return number_format((float) $value, $decimals, '.', '');
    }

    private function formatPlainNumber($value, int $decimals = 2): string
    {
        return rtrim(rtrim(number_format((float) $value, $decimals, '.', ''), '0'), '.');
    }

    private function resolveInvoiceDocumentNumber(SalesOrder $order, string $serie, string $tipoDocumento, array $options): string
    {
        $numeroDocumento = trim((string) ($options['numero_documento'] ?? ''));
        if ($numeroDocumento !== '') {
            return preg_replace('/\D+/', '', $numeroDocumento) ?: (string) $order->id;
        }

        // For dispatch guides, let HKA resolve/validate numbering from assigned numerations.
        if ($tipoDocumento === '06') {
            return '';
        }

        if ((bool) config('services.thefactory_hka.auto_next_number', true)) {
            $lastResponse = $this->getLastDocument([
                'serie' => $serie,
                'tipoDocumento' => $tipoDocumento,
            ]);

            $lastNumber = (int) Arr::get($lastResponse, 'data.numeroDocumento', 0);
            if ($lastNumber > 0) {
                return (string) ($lastNumber + 1);
            }
        }

        return (string) $order->id;
    }

    private function resolveExchangeRate(SalesOrder $order, array $options): float
    {
        $explicitRate = (float) ($options['tipo_cambio'] ?? 0);
        if ($explicitRate > 0) {
            return $explicitRate;
        }

        $targetCurrency = $this->normalizeCurrency((string) ($options['otra_moneda'] ?? TenantCurrency::resolveBaseCurrencyCode($order->tenant)));

        if ($this->isBolivarCurrency($targetCurrency)) {
            return 1;
        }

        $tenantRate = TenantCurrency::resolveRateToBs((int) $order->tenant_id, $targetCurrency);
        if ($tenantRate > 0) {
            return (float) $tenantRate;
        }

        return max((float) config('services.thefactory_hka.default_exchange_rate', 1), 1);
    }

    private function buildPayloadSignature(array $payload): string
    {
        $normalized = $this->normalizeForSignature($payload);
        $json = json_encode($normalized, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $secret = trim($this->signatureSecret) !== '' ? $this->signatureSecret : 'shopix-default-signature';

        return hash_hmac('sha256', (string) $json, $secret);
    }

    private function normalizeForSignature($value)
    {
        if (!is_array($value)) {
            return $value;
        }

        if (array_is_list($value)) {
            return array_map(fn ($item) => $this->normalizeForSignature($item), $value);
        }

        ksort($value);
        foreach ($value as $key => $item) {
            $value[$key] = $this->normalizeForSignature($item);
        }

        return $value;
    }

    private function normalizeCurrency(string $currency): string
    {
        $currency = Str::upper(trim($currency));

        if ($currency === '') {
            return 'BSD';
        }

        if (in_array($currency, ['BS', 'VES', 'VED', 'VEF', 'BOLIVAR', 'BOLIVARES'], true)) {
            return 'BSD';
        }

        return $currency;
    }

    private function isBolivarCurrency(string $currency): bool
    {
        return in_array($this->normalizeCurrency($currency), ['BS', 'BSD', 'VES', 'VED', 'VEF'], true);
    }

    private function isBsExactCurrency(string $currency): bool
    {
        return in_array(Str::upper(trim($currency)), ['BS', 'BSD', 'VES', 'VED', 'VEF'], true);
    }

    private function mapPaymentFormCode(string $paymentName): string
    {
        $normalized = Str::lower(Str::ascii($paymentName));

        if (str_contains($normalized, 'movil')) {
            return '02';
        }

        if (str_contains($normalized, 'transfer')) {
            return '03';
        }

        if (str_contains($normalized, 'efectivo')) {
            return '01';
        }

        if (str_contains($normalized, 'tarjeta')) {
            return '04';
        }

        return '99';
    }

    private function rebalancePayments(array $payments, float $totalPagar, string $documentCurrency): array
    {
        if (empty($payments)) {
            return $payments;
        }

        $toFloat = static function ($value): float {
            return (float) str_replace(',', '.', (string) $value);
        };

        $documentCurrency = $this->normalizeCurrency($documentCurrency);
        $sum = 0.0;

        foreach ($payments as $payment) {
            $amount = $toFloat($payment['monto'] ?? 0);
            $paymentCurrency = $this->normalizeCurrency((string) ($payment['moneda'] ?? ''));

            if ($paymentCurrency === $documentCurrency) {
                $sum += $amount;
                continue;
            }

            $rate = $toFloat($payment['tipoCambio'] ?? 0);
            if ($rate <= 0) {
                return $payments;
            }

            if ($this->isBolivarCurrency($documentCurrency) && !$this->isBolivarCurrency($paymentCurrency)) {
                $sum += $amount * $rate;
                continue;
            }

            if (!$this->isBolivarCurrency($documentCurrency) && $this->isBolivarCurrency($paymentCurrency)) {
                $sum += $amount / $rate;
                continue;
            }

            return $payments;
        }

        $difference = round($totalPagar - $sum, 2);
        if (abs($difference) < 0.01) {
            return $payments;
        }

        $lastIndex = count($payments) - 1;
        $lastAmount = $toFloat($payments[$lastIndex]['monto'] ?? 0);
        $lastCurrency = $this->normalizeCurrency((string) ($payments[$lastIndex]['moneda'] ?? ''));
        $lastRate = $toFloat($payments[$lastIndex]['tipoCambio'] ?? 0);

        $adjustment = $difference;
        if ($lastCurrency !== $documentCurrency) {
            if ($lastRate <= 0) {
                return $payments;
            }

            if ($this->isBolivarCurrency($documentCurrency) && !$this->isBolivarCurrency($lastCurrency)) {
                $adjustment = $difference / $lastRate;
            } elseif (!$this->isBolivarCurrency($documentCurrency) && $this->isBolivarCurrency($lastCurrency)) {
                $adjustment = $difference * $lastRate;
            } else {
                return $payments;
            }
        }

        $payments[$lastIndex]['monto'] = $this->formatAmount(max(0, $lastAmount + $adjustment), 2);

        return $payments;
    }

    private function mergeWithBaseInvoicePayload(array $customPayload): array
    {
        return $this->mergeAssocRecursive($this->invoiceBasePayload(), $customPayload);
    }

    private function buildRetainedSubject($provider, $tenant): array
    {
        [$type, $number] = $this->splitFiscalIdentification((string) ($provider->rif ?? ''));

        return $this->removeNulls([
            'tipoPerceptor' => null,
            'tipoIdentificacion' => $type,
            'numeroIdentificacion' => $number,
            'razonSocial' => (string) ($provider->name ?? 'Proveedor'),
            'direccion' => trim((string) ($provider->notes ?: ($tenant->address ?? 'Dirección no suministrada'))),
            'ubigeo' => null,
            'pais' => 'VE',
            'notificar' => 'No',
            'telefono' => !empty($provider->phone_number) ? [(string) $provider->phone_number] : null,
            'correo' => !empty($provider->email) ? [(string) $provider->email] : null,
            'otrosEnvios' => null,
        ]);
    }

    private function buildPurchaseRetentionCurrencyContext($tenant, array $amounts, string $sourceCurrency): array
    {
        $sourceCurrency = TenantCurrency::normalizeCurrencyCode($sourceCurrency);
        $documentCurrency = 'BSD';
        $tenantId = (int) ($tenant->id ?? 0);

        $normalizedAmounts = [
            'amount_total' => round((float) ($amounts['amount_total'] ?? 0), 2),
            'taxable_base' => round((float) ($amounts['taxable_base'] ?? 0), 2),
            'tax_amount' => round((float) ($amounts['tax_amount'] ?? 0), 2),
            'retained_amount' => round((float) ($amounts['retained_amount'] ?? 0), 2),
        ];

        if ($sourceCurrency === 'BS') {
            return [
                'document_currency' => $documentCurrency,
                'document' => $normalizedAmounts,
                'totales_otra_moneda' => null,
            ];
        }

        $exchangeRate = TenantCurrency::resolveRateToBs($tenantId, $sourceCurrency);
        if ($exchangeRate <= 0) {
            $exchangeRate = max((float) config('services.thefactory_hka.default_exchange_rate', 1), 1);
        }

        $documentAmounts = [
            'amount_total' => round($normalizedAmounts['amount_total'] * $exchangeRate, 2),
            'taxable_base' => round($normalizedAmounts['taxable_base'] * $exchangeRate, 2),
            'tax_amount' => round($normalizedAmounts['tax_amount'] * $exchangeRate, 2),
            'retained_amount' => round($normalizedAmounts['retained_amount'] * $exchangeRate, 2),
        ];

        return [
            'document_currency' => $documentCurrency,
            'document' => $documentAmounts,
            'totales_otra_moneda' => [
                'moneda' => $sourceCurrency,
                'tipoCambio' => $this->formatAmount($exchangeRate, 4),
                'montoGravadoTotal' => '0.00',
                'montoExentoTotal' => '0.00',
                'montoPercibidoTotal' => null,
                'subtotal' => '0.00',
                'totalIVA' => '0.00',
                'montoTotalConIVA' => $this->formatAmount($normalizedAmounts['amount_total'], 2),
                'totalAPagar' => '0.00',
                'impuestosSubtotal' => null,
                'totalIGTF' => null,
                'totalIGTF_VES' => null,
            ],
        ];
    }

    private function splitFiscalIdentification(string $rif): array
    {
        $normalized = strtoupper(trim($rif));
        $type = preg_match('/^[A-Z]/', $normalized) ? substr($normalized, 0, 1) : 'J';
        $number = preg_replace('/\D+/', '', $normalized);

        return [$type !== '' ? $type : 'J', $number !== '' ? $number : '0'];
    }

    private function resolvePurchaseRetentionDocumentNumber(string $seed, $date): string
    {
        $digits = preg_replace('/\D+/', '', $seed);
        $digits = str_pad(substr($digits !== '' ? $digits : '0', -8), 8, '0', STR_PAD_LEFT);
        $prefix = Carbon::parse((string) $date)->format('Ym');

        return $prefix . $digits;
    }

    private function resolveRetentionCertificateToken(string $seed): string
    {
        $digits = preg_replace('/\D+/', '', $seed);

        return $digits !== '' ? ltrim($digits, '0') !== '' ? ltrim($digits, '0') : '0' : '0';
    }

    private function formatFraction(float $percent): string
    {
        $fraction = $percent > 1 ? ($percent / 100) : $percent;

        return $this->formatPlainNumber($fraction, 4);
    }

    private function formatRetentionFraction(float $taxAmount, float $baseAmount, float $fallbackPercent): string
    {
        if ($baseAmount > 0 && $taxAmount > 0) {
            return $this->formatPlainNumber($taxAmount / $baseAmount, 4);
        }

        return $this->formatFraction($fallbackPercent);
    }

    private function invoiceBasePayload(): array
    {
        return [
            'encabezado' => [
                'identificacionDocumento' => [
                    'tipoDocumento' => '',
                    'serie' => '',
                    'numeroDocumento' => null,
                    'fechaEmision' => null,
                    'horaEmision' => null,
                    'sucursal' => '',
                    'tipoDePago' => null,
                    'tipoDeVenta' => null,
                    'moneda' => null,
                    'transaccionId' => null,
                ],
                'vendedor' => [
                    'codigo' => '',
                    'nombre' => '',
                    'numCajero' => '',
                ],
                'comprador' => [
                    'tipoIdentificacion' => '',
                    'numeroIdentificacion' => '',
                    'razonSocial' => '',
                    'direccion' => '',
                    'ubigeo' => '',
                    'pais' => 'VE',
                    'notificar' => 'No',
                    'telefono' => [],
                    'correo' => [],
                    'otrosEnvios' => [],
                ],
                'totales' => [
                    'nroItems' => '0',
                    'montoGravadoTotal' => '0.00',
                    'montoExentoTotal' => '0.00',
                    'subtotalAntesDescuento' => '0.00',
                    'totalDescuento' => '0.00',
                    'totalRecargos' => '0.00',
                    'subtotal' => '0.00',
                    'totalIVA' => '0.00',
                    'montoTotalConIVA' => '0.00',
                    'totalAPagar' => '0.00',
                    'impuestosSubtotal' => [],
                    'formasPago' => [],
                ],
                'totalesOtraMoneda' => null,
                'orden' => [
                    'numero' => '',
                    'correo' => [],
                ],
            ],
            'detallesItems' => [],
            'infoAdicional' => [],
            'esMinimo' => false,
        ];
    }

    private function mergeAssocRecursive(array $base, array $custom): array
    {
        foreach ($custom as $key => $value) {
            if (
                array_key_exists($key, $base)
                && is_array($base[$key])
                && is_array($value)
                && $this->isAssoc($base[$key])
                && $this->isAssoc($value)
            ) {
                $base[$key] = $this->mergeAssocRecursive($base[$key], $value);
                continue;
            }

            $base[$key] = $value;
        }

        return $base;
    }

    private function isAssoc(array $array): bool
    {
        if ($array === []) {
            return false;
        }

        return array_keys($array) !== range(0, count($array) - 1);
    }

    private function removeNulls(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $data[$key] = $this->removeNulls($value);
                continue;
            }

            if ($value === null) {
                unset($data[$key]);
            }
        }

        return $data;
    }
}
