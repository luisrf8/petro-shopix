<?php

namespace App\Services;

use App\Models\SalesOrder;
use App\Support\TenantCurrency;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

class TheFactoryHkaService
{
    private string $baseUrl;
    private string $username;
    private string $password;
    private int $timeout;
    private bool $verifySsl;

    public function __construct()
    {
        $config = config('services.thefactory_hka', []);

        $this->baseUrl = rtrim((string) ($config['base_url'] ?? ''), '/');
        $this->username = (string) ($config['username'] ?? '');
        $this->password = (string) ($config['password'] ?? '');
        $this->timeout = (int) ($config['timeout'] ?? 25);
        $this->verifySsl = (bool) ($config['verify_ssl'] ?? true);
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

    public function emitArc(array $documentoElectronico): array
    {
        return $this->request('post', '/api/EmisionARC', [
            'documentoElectronico' => $documentoElectronico,
        ]);
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
        $order->loadMissing(['user', 'tenant', 'details.variant.product', 'details.taxes.tax', 'payments.payment']);

        $serie = trim((string) ($options['serie'] ?? config('services.thefactory_hka.default_serie', '')));
        $serieUpper = Str::upper($serie);
        if (in_array($serieUpper, ['TODOS', 'TODO', 'ALL'], true)) {
            $serie = '00';
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

        $taxRate = (float) ($order->details->flatMap->taxes->first()->tax_rate ?? ($order->details->first()->tax_rate ?? 0));
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

        $detailItems = $order->details->values()->map(function ($item, $index) use ($emitOtherCurrency, $exchangeRate) {
            $lineTaxAmount = (float) $item->taxes->sum('tax_amount');
            if ($lineTaxAmount <= 0) {
                $lineTaxAmount = (float) ($item->tax_amount ?? 0);
            }

            $lineTaxRate = (float) ($item->taxes->first()->tax_rate ?? ($item->tax_rate ?? 0));
            $lineSubtotal = (float) $item->amount;
            $lineTotal = round($lineSubtotal + $lineTaxAmount, 2);
            $multiplier = $emitOtherCurrency ? $exchangeRate : 1;
            $isExempt = $lineTaxAmount <= 0;
            $lineBaseAmount = $lineSubtotal;

            return [
                'numeroLinea' => (string) ($index + 1),
                'codigoPLU' => (string) ($item->variant->sku ?? $item->variant->barcode ?? ('ITEM-' . ($item->id ?? $index + 1))),
                'indicadorBienoServicio' => '1',
                'descripcion' => (string) ($item->variant->product->name ?? 'Producto'),
                'cantidad' => $this->formatPlainNumber($item->quantity, 4),
                'unidadMedida' => 'UND',
                'precioUnitario' => $this->formatAmount((float) $item->price * $multiplier, 4),
                'precioUnitarioDescuento' => null,
                'montoBonificacion' => null,
                'descripcionBonificacion' => null,
                'descuentoMonto' => $this->formatAmount(0, 2),
                'recargoMonto' => $this->formatAmount(0, 2),
                'precioItem' => $this->formatAmount($lineBaseAmount * $multiplier, 2),
                'precioAntesDescuento' => $this->formatAmount($lineBaseAmount * $multiplier, 2),
                'codigoImpuesto' => $isExempt ? 'E' : 'G',
                'tasaIVA' => $lineTaxAmount > 0 ? $this->formatPlainNumber($lineTaxRate > 0 ? $lineTaxRate : 16, 2) : null,
                'valorIVA' => $lineTaxAmount > 0 ? $this->formatAmount($lineTaxAmount * $multiplier, 2) : null,
                'valorTotalItem' => $this->formatAmount($lineTotal * $multiplier, 2),
                'infoAdicionalItem' => [],
                'listaItemOTI' => null,
            ];
        })->all();

        $taxableBaseTotal = (float) collect($order->details)->sum(function ($item) {
            $lineTaxAmount = (float) $item->taxes->sum('tax_amount');
            if ($lineTaxAmount <= 0) {
                $lineTaxAmount = (float) ($item->tax_amount ?? 0);
            }

            return $lineTaxAmount > 0 ? (float) ($item->amount ?? 0) : 0.0;
        });
        $exemptTotal = max(0, round($subtotal - $taxableBaseTotal, 2));
        $montoTotalConIva = round($subtotal + $taxTotal, 2);
        $totalPagar = $montoTotalConIva;
        $documentTaxableBaseTotal = $emitOtherCurrency ? round($taxableBaseTotal * $exchangeRate, 2) : $taxableBaseTotal;
        $documentExemptTotal = $emitOtherCurrency ? round($exemptTotal * $exchangeRate, 2) : $exemptTotal;
        $documentSubtotal = $emitOtherCurrency ? round($subtotal * $exchangeRate, 2) : $subtotal;
        $documentTaxTotal = $emitOtherCurrency ? round($taxTotal * $exchangeRate, 2) : $taxTotal;
        $documentMontoTotalConIva = $emitOtherCurrency ? round($montoTotalConIva * $exchangeRate, 2) : $montoTotalConIva;
        $documentTotalPagar = $emitOtherCurrency ? round($totalPagar * $exchangeRate, 2) : $totalPagar;
        $taxesSubtotal = [];
        if ($taxTotal > 0) {
            $taxesSubtotal[] = [
                'codigoTotalImp' => 'G',
                'alicuotaImp' => $this->formatPlainNumber($taxRate > 0 ? $taxRate : 16, 2),
                'baseImponibleImp' => $this->formatAmount($documentTaxableBaseTotal, 2),
                'valorTotalImp' => $this->formatAmount($documentTaxTotal, 2),
            ];
        }
        if ($exemptTotal > 0) {
            $taxesSubtotal[] = [
                'codigoTotalImp' => 'E',
                'baseImponibleImp' => $this->formatAmount($documentExemptTotal, 2),
                'valorTotalImp' => $this->formatAmount(0, 2),
            ];
        }

        $foreignTaxesSubtotal = [];
        if ($taxTotal > 0) {
            $foreignTaxesSubtotal[] = [
                'codigoTotalImp' => 'G',
                'alicuotaImp' => $this->formatPlainNumber($taxRate > 0 ? $taxRate : 16, 2),
                'baseImponibleImp' => $this->formatAmount($taxableBaseTotal, 2),
                'valorTotalImp' => $this->formatAmount($taxTotal, 2),
            ];
        }
        if ($exemptTotal > 0) {
            $foreignTaxesSubtotal[] = [
                'codigoTotalImp' => 'E',
                'baseImponibleImp' => $this->formatAmount($exemptTotal, 2),
                'valorTotalImp' => $this->formatAmount(0, 2),
            ];
        }

        $payments = $order->payments->map(function ($payment) use ($documentCurrency, $documentTotalPagar, $exchangeRate, $emitOtherCurrency, $order) {
            $paymentCurrency = $this->normalizeCurrency((string) ($documentCurrency));
            $paymentAmount = $emitOtherCurrency ? ((float) $payment->amount * $exchangeRate) : (float) $payment->amount;

            $payment = [
                'descripcion' => (string) ($payment->payment->name ?? 'Pago tienda'),
                'fecha' => Carbon::parse((string) ($order->date ?? now()->toDateString()))->format('d/m/Y'),
                'forma' => $this->mapPaymentFormCode((string) ($payment->payment->name ?? '')),
                'monto' => $this->formatAmount($paymentAmount, 2),
                'moneda' => $paymentCurrency,
            ];

            if (!$this->isBsExactCurrency($paymentCurrency)) {
                $payment['tipoCambio'] = $this->formatAmount($emitOtherCurrency ? 0 : max($exchangeRate, 0.00000001), 4);
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

        $payments = $this->rebalancePayments($payments, $documentTotalPagar);

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
                    'subtotalAntesDescuento' => $this->formatAmount($documentSubtotal, 2),
                    'totalDescuento' => null,
                    'totalRecargos' => null,
                    'subtotal' => $this->formatAmount($documentSubtotal, 2),
                    'totalIVA' => $this->formatAmount($documentTaxTotal, 2),
                    'montoTotalConIVA' => $this->formatAmount($documentMontoTotalConIva, 2),
                    'totalAPagar' => $this->formatAmount($documentTotalPagar, 2),
                    'montoEnLetras' => null,
                    'listaRecargo' => null,
                    'listaDescBonificacion' => null,
                    'impuestosSubtotal' => $taxesSubtotal,
                    'otrosImpuestosSubtotal' => null,
                    'formasPago' => $payments,
                    'totalIGTF' => null,
                    'totalIGTF_VES' => null,
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
                    'subtotalAntesDescuento' => $this->formatAmount($subtotal, 2),
                    'totalDescuento' => null,
                    'totalRecargos' => null,
                    'listaRecargo' => null,
                    'listaDescBonificacion' => null,
                    'impuestosSubtotal' => $foreignTaxesSubtotal,
                    'otrosImpuestosSubtotal' => null,
                    'montoTotalOTI' => null,
                    'montoTotalIVAyOTI' => null,
                ] : null,
                'orden' => null,
            ],
            'detallesItems' => $detailItems,
            'detallesRetencion' => null,
            'viajes' => null,
            'infoAdicional' => [],
            'guiaDespacho' => null,
            'transporte' => null,
            'esLote' => null,
            'esMinimo' => null,
        ];

        $payload = $this->mergeWithBaseInvoicePayload($customPayload);

        return $this->removeNulls($payload);
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
            }

            $url = $this->baseUrl . $path;
            $method = strtolower($method);

            if ($method === 'get') {
                $response = $http->get($url, $payload);
            } elseif ($method === 'delete') {
                $response = $http->withBody(json_encode($payload), 'application/json')->delete($url);
            } else {
                $response = $http->{$method}($url, $payload);
            }

            $json = $response->json();
            $ok = $response->successful() && $this->isApiResponseSuccessful($json);

            return [
                'ok' => $ok,
                'status' => $response->status(),
                'message' => (string) (Arr::get($json, 'mensaje') ?? ($ok ? 'OK' : 'Error de integración')),
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

    private function rebalancePayments(array $payments, float $totalPagar): array
    {
        if (empty($payments)) {
            return $payments;
        }

        $sum = (float) collect($payments)->sum(function ($payment) {
            return (float) ($payment['monto'] ?? 0);
        });

        $difference = round($totalPagar - $sum, 2);
        if (abs($difference) < 0.01) {
            return $payments;
        }

        $lastIndex = count($payments) - 1;
        $lastAmount = (float) ($payments[$lastIndex]['monto'] ?? 0);
        $payments[$lastIndex]['monto'] = $this->formatAmount(max(0, $lastAmount + $difference), 2);

        return $payments;
    }

    private function mergeWithBaseInvoicePayload(array $customPayload): array
    {
        return $this->mergeAssocRecursive($this->invoiceBasePayload(), $customPayload);
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
