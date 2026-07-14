<?php

namespace App\Http\Controllers;

use App\Models\Currency;
use App\Models\ElectronicDocument;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\SalesAdjustmentNote;
use App\Models\SalesOrder;
use App\Models\SalesRetention;
use App\Services\FiscalCorrelativeService;
use App\Services\TheFactoryHkaService;
use App\Support\PdfDownload;
use App\Support\TenantCurrency;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Database\QueryException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class SalesFiscalController extends Controller
{
    public function __construct(
        private readonly FiscalCorrelativeService $fiscalCorrelativeService,
        private readonly TheFactoryHkaService $hkaService
    ) {}

    public function storeAdjustmentNote(Request $request, SalesOrder $order): RedirectResponse
    {
        $this->authorizeOrderAccess($order);

        $validated = $request->validate([
            'note_type' => 'required|in:credit,debit',
            'note_date' => 'required|date',
            'amount' => 'nullable|numeric|gt:0',
            'adjustment_mode' => 'nullable|in:manual,exchange_rate_diff,price_error',
            'taxable_base' => 'nullable|numeric|gt:0',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'affected_igtf_amount' => 'nullable|numeric|min:0',
            'reason' => 'required|string|max:255',
            'notes' => 'nullable|string|max:2000',
        ]);

            $latestElectronicDocument = $this->resolveLatestInvoiceDocument($order);
        $noteType = (string) $validated['note_type'];
        $adjustmentMode = (string) ($validated['adjustment_mode'] ?? 'manual');

        if (!$latestElectronicDocument || empty($latestElectronicDocument->numero_documento) || empty($latestElectronicDocument->numero_control)) {
            return back()->withErrors([
                'note_type' => 'No puedes registrar una nota fiscal sin factura fiscal original con numero y control.',
            ])->withInput();
        }

        if ($noteType === 'credit') {
            $issuedAt = $latestElectronicDocument->issued_at
                ?? $latestElectronicDocument->created_at
                ?? Carbon::parse((string) ($order->date ?? now()->toDateString()));
            $maxAgeDays = max(1, (int) ($order->tenant?->credit_note_max_age_days ?? 30));
            $noteDate = Carbon::parse((string) $validated['note_date']);
            $ageDays = $issuedAt ? $issuedAt->startOfDay()->diffInDays($noteDate->startOfDay()) : 0;

            if ($ageDays > $maxAgeDays) {
                return back()->withErrors([
                    'note_date' => 'No puedes emitir una nota de credito para facturas con antiguedad mayor a ' . $maxAgeDays . ' dias.',
                ])->withInput();
            }
        }

        $affectedIgtfAmount = round((float) ($validated['affected_igtf_amount'] ?? 0), 2);
        $amount = isset($validated['amount']) ? round((float) $validated['amount'], 2) : null;
        $taxableBase = isset($validated['taxable_base']) ? round((float) $validated['taxable_base'], 2) : null;
        $taxRate = isset($validated['tax_rate']) ? round((float) $validated['tax_rate'], 4) : null;
        $taxAmount = null;
        if (!is_null($taxableBase) && !is_null($taxRate)) {
            $taxAmount = round($taxableBase * ($taxRate / 100), 2);
        }

        if ($noteType === 'debit' && in_array($adjustmentMode, ['exchange_rate_diff', 'price_error'], true)) {
            if (is_null($taxableBase) || is_null($taxRate)) {
                return back()->withErrors([
                    'taxable_base' => 'Para notas de debito por diferencial debes indicar base imponible y alicuota de IVA.',
                ])->withInput();
            }

            $amount = round($taxableBase + (float) ($taxAmount ?? 0) + max(0, $affectedIgtfAmount), 2);
        }

        if (is_null($amount) || $amount <= 0) {
            return back()->withErrors([
                'amount' => 'Debes indicar un monto valido para registrar la nota fiscal.',
            ])->withInput();
        }

        if ($adjustmentMode === 'manual') {
            [$taxableBase, $taxAmount] = $this->resolveManualAdjustmentBreakdown($amount, $taxRate, $affectedIgtfAmount);
        }

        $prefix = $validated['note_type'] === 'credit' ? 'NC' : 'ND';
        $internalNumber = $this->fiscalCorrelativeService->next(
            (int) $order->tenant_id,
            $validated['note_type'] === 'credit' ? 'credit_note' : 'debit_note',
            $prefix
        );

        $note = SalesAdjustmentNote::create([
            'tenant_id' => $order->tenant_id,
            'sales_order_id' => $order->id,
            'electronic_document_id' => $latestElectronicDocument->id,
            'created_by' => auth()->id(),
            'note_type' => $validated['note_type'],
            'adjustment_mode' => $adjustmentMode,
            'status' => 'registered',
            'note_date' => $validated['note_date'],
            'internal_number' => $internalNumber,
            'document_code' => $validated['note_type'] === 'credit' ? '02' : '03',
            'reference_document_number' => $latestElectronicDocument->numero_documento,
            'reference_control_number' => $latestElectronicDocument->numero_control,
            'amount' => $amount,
            'taxable_base' => $taxableBase,
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'affected_igtf_amount' => $affectedIgtfAmount > 0 ? $affectedIgtfAmount : 0,
            'currency_code' => $this->resolveOrderCurrencyCode($order),
            'reason' => trim((string) $validated['reason']),
            'notes' => $validated['notes'] ?? null,
        ]);

        $payload = $this->hkaService->buildAdjustmentNotePayloadFromOrder($order, $note, $latestElectronicDocument);
        $response = $noteType === 'credit'
            ? $this->hkaService->emitDebitNote($payload)
            : $this->hkaService->emitCreditNote($payload);

        $responseData = is_array($response['data'] ?? null) ? $response['data'] : [];
        $hkaCodigo = (string) ($responseData['codigo'] ?? $responseData['Codigo'] ?? '');
        $hkaMensaje = (string) ($responseData['mensaje'] ?? $responseData['Mensaje'] ?? '');
        $responseMessage = trim((string) ($response['message'] ?? ''));

        if (!($response['ok'] ?? false)) {
            $note->update([
                'status' => 'failed',
                'request_payload' => $payload,
                'response_payload' => is_array($responseData) ? $responseData : ['raw' => (string) $hkaMensaje],
            ]);

            $message = $responseMessage !== '' && strcasecmp($responseMessage, 'Error de integración') !== 0
                ? $responseMessage
                : ($hkaMensaje !== '' ? $hkaMensaje : 'No fue posible emitir la nota fiscal en HKA.');
            if ($hkaCodigo !== '') {
                $message .= ' Código HKA: ' . $hkaCodigo . '.';
            }
            if (isset($response['status']) && (int) $response['status'] > 0) {
                $message .= ' HTTP: ' . (int) $response['status'] . '.';
            }

            return back()->withErrors([
                'note_type' => $message,
            ])->withInput();
        }

        $note->update([
            'status' => 'issued',
            'request_payload' => $payload,
            'response_payload' => $responseData,
            'issued_at' => now(),
            'related_at' => now(),
            'document_code' => $noteType === 'credit' ? '02' : '03',
        ]);

        $successMessage = 'La nota fiscal fue emitida correctamente.';
        if ($hkaCodigo !== '') {
            $successMessage .= ' Codigo HKA: ' . $hkaCodigo . '.';
        }

        return back()->with('success', $successMessage);
    }

    public function storeRetention(Request $request, SalesOrder $order): RedirectResponse
    {
        $this->authorizeOrderAccess($order);

        $validated = $request->validate([
            'retention_type' => 'required|in:iva,islr,municipal,other',
            'retention_date' => 'required|date',
            'retention_rate' => 'required|numeric|min:0|max:100',
            'taxable_base' => 'required|numeric|gt:0',
            'retained_amount' => 'nullable|numeric|gt:0',
            'certificate_number' => 'nullable|string|max:60',
            'notes' => 'nullable|string|max:2000',
        ]);

        if ((string) $validated['retention_type'] === 'iva') {
            $normalizedRate = round((float) $validated['retention_rate'], 4);
            if (!in_array($normalizedRate, [75.0, 100.0], true)) {
                return back()->withErrors([
                    'retention_rate' => 'Para retenciones de IVA solo se permite 75% o 100%.',
                ])->withInput();
            }

            $certificate = trim((string) ($validated['certificate_number'] ?? ''));
            if (!$this->isValidIvaCertificate($certificate)) {
                return back()->withErrors([
                    'certificate_number' => 'El comprobante de IVA debe tener formato YYYYMM + 8 digitos (14 digitos en total).',
                ])->withInput();
            }
        }

        $latestElectronicDocument = $this->resolveLatestInvoiceDocument($order);
        $taxableBase = (float) $validated['taxable_base'];
        $retentionRate = (float) $validated['retention_rate'];
        $retainedAmount = isset($validated['retained_amount'])
            ? (float) $validated['retained_amount']
            : round($taxableBase * ($retentionRate / 100), 2);
        $internalNumber = $this->fiscalCorrelativeService->next(
            (int) $order->tenant_id,
            'retention',
            'RET'
        );

        try {
            $retention = DB::transaction(function () use ($order, $latestElectronicDocument, $validated, $internalNumber, $retentionRate, $taxableBase, $retainedAmount) {
                $retention = SalesRetention::create([
                    'tenant_id' => $order->tenant_id,
                    'sales_order_id' => $order->id,
                    'electronic_document_id' => $latestElectronicDocument?->id,
                    'created_by' => auth()->id(),
                    'retention_type' => $validated['retention_type'],
                    'status' => 'registered',
                    'retention_date' => $validated['retention_date'],
                    'internal_number' => $internalNumber,
                    'certificate_number' => $validated['certificate_number'] ?? null,
                    'retention_rate' => $retentionRate,
                    'taxable_base' => $taxableBase,
                    'retained_amount' => $retainedAmount,
                    'currency_code' => $this->resolveOrderCurrencyCode($order),
                    'notes' => $validated['notes'] ?? null,
                ]);

                $this->applyRetentionAsInternalPayment($order, $retention);

                return $retention;
            });
        } catch (\RuntimeException|QueryException $exception) {
            return back()->withErrors([
                'retention_type' => $exception->getMessage() !== ''
                    ? $exception->getMessage()
                    : 'No fue posible aplicar la retención al saldo interno de la orden.',
            ])->withInput();
        }

        $messages = ['La retención fue registrada y aplicada al saldo pendiente. Ref: ' . ($retention->internal_number ?? 'N/A')];

        if ($latestElectronicDocument && !$latestElectronicDocument->is_annulled) {
            $syncResult = $this->syncRetentionToHka($order, $retention, $latestElectronicDocument);
            $messages[] = $syncResult['message'];
        } else {
            $messages[] = 'HKA: no se sincronizó porque la factura fiscal relacionada no está activa.';
        }

        return back()->with('success', implode(' ', array_filter($messages)));
    }

    public function syncRetentionHka(SalesRetention $retention): RedirectResponse
    {
        $order = $this->authorizeRetentionAccess($retention);
        $referenceDocument = $this->resolveRetentionReferenceDocument($retention, $order);

        if (!$referenceDocument) {
            return back()->withErrors([
                'retention_type' => 'No existe una factura fiscal activa para sincronizar esta retención con HKA.',
            ]);
        }

        $result = $this->syncRetentionToHka($order, $retention, $referenceDocument);

        if (!($result['ok'] ?? false)) {
            return back()->withErrors([
                'retention_type' => $result['message'] ?? 'No fue posible sincronizar la retención con HKA.',
            ]);
        }

        return back()->with('success', $result['message'] ?? 'Retención sincronizada con HKA correctamente.');
    }

    public function refreshRetentionHkaStatus(SalesRetention $retention): RedirectResponse
    {
        $order = $this->authorizeRetentionAccess($retention);
        $referenceDocument = $this->resolveRetentionReferenceDocument($retention, $order);

        if (!$referenceDocument) {
            return back()->withErrors([
                'retention_type' => 'No existe una factura fiscal activa para consultar esta retención en HKA.',
            ]);
        }

        $queryPayload = $this->hkaService->buildRetentionLookupPayload($referenceDocument);
        $response = $this->hkaService->getRetention($queryPayload);
        $responseData = is_array($response['data'] ?? null) ? $response['data'] : [];

        $retention->update([
            'status' => ($response['ok'] ?? false) ? 'applied_hka' : ($retention->status === 'applied_hka' ? 'applied_hka' : 'applied_hka_error'),
            'request_payload' => $this->mergeRetentionPayloadSegment($retention->request_payload, 'status', $queryPayload),
            'response_payload' => $this->mergeRetentionPayloadSegment($retention->response_payload, 'status', $responseData ?: [
                'message' => $response['message'] ?? null,
                'status' => $response['status'] ?? null,
            ]),
        ]);

        if (!($response['ok'] ?? false)) {
            return back()->withErrors([
                'retention_type' => ($response['message'] ?? 'No fue posible consultar la retención en HKA.') .
                    ((string) Arr::get($responseData, 'codigo', '') !== '' ? ' Código HKA: ' . Arr::get($responseData, 'codigo') . '.' : ''),
            ]);
        }

        return back()->with('success', 'Consulta HKA actualizada para la retención ' . ($retention->internal_number ?? $retention->id) . '.');
    }

    public function downloadAdjustmentNote(Request $request, SalesAdjustmentNote $note)
    {
        $this->authorizeAdjustmentNoteAccess($note);

        $validated = $request->validate([
            'tipo_archivo' => 'nullable|in:pdf,PDF,xml,XML,json,JSON',
            'disposition' => 'nullable|in:inline,attachment',
        ]);

        if (($note->status ?? '') !== 'issued') {
            abort(422, 'Solo puedes descargar notas fiscales emitidas correctamente en HKA.');
        }

        if (!$this->hkaService->isConfigured()) {
            abort(422, 'La integración HKA no está configurada para descargar notas fiscales.');
        }

        $fileType = strtolower((string) ($validated['tipo_archivo'] ?? 'pdf'));
        $downloadPayload = $this->buildAdjustmentNoteDownloadPayload($note, $fileType);

        if (trim((string) ($downloadPayload['numeroDocumento'] ?? '')) === '') {
            abort(422, 'No existe numeración suficiente para descargar esta nota desde HKA.');
        }

        $response = $this->hkaService->downloadDocumentFile($downloadPayload);
        if (!($response['ok'] ?? false) || empty($response['content'])) {
            abort(422, 'No fue posible descargar la nota fiscal desde HKA. ' . ($response['message'] ?? 'Error desconocido.'));
        }

        $responsePayload = is_array($note->response_payload) ? $note->response_payload : [];
        $downloads = is_array($responsePayload['downloads'] ?? null) ? $responsePayload['downloads'] : [];
        $downloads[$fileType] = array_filter([
            'codigo' => Arr::get($response, 'data.codigo'),
            'mensaje' => Arr::get($response, 'data.mensaje', $response['message'] ?? null),
            'downloaded_at' => now()->toDateTimeString(),
        ], static fn ($value) => !is_null($value) && $value !== '');
        $responsePayload['downloads'] = $downloads;

        $note->update([
            'response_payload' => $responsePayload,
        ]);

        $documentLabel = $note->note_type === 'credit' ? 'nota-credito' : 'nota-debito';
        $fileName = implode('-', array_filter([
            $documentLabel,
            trim((string) ($downloadPayload['serie'] ?? '')) !== '' ? trim((string) ($downloadPayload['serie'] ?? '')) : null,
            trim((string) ($downloadPayload['numeroDocumento'] ?? '')),
        ])) . '.' . strtolower((string) ($response['extension'] ?? $fileType));

        return response((string) $response['content'], 200, [
            'Content-Type' => (string) ($response['mime_type'] ?? 'application/pdf'),
            'Content-Disposition' => PdfDownload::buildDispositionHeader($request, $fileName, (string) ($validated['disposition'] ?? 'attachment')),
        ]);
    }

    public function downloadRetentionHkaSnapshot(Request $request, SalesRetention $retention)
    {
        $order = $this->authorizeRetentionAccess($retention);

        $validated = $request->validate([
            'disposition' => 'nullable|in:inline,attachment',
        ]);

        $referenceDocument = $this->resolveRetentionReferenceDocument($retention, $order);
        $hkaCurrency = strtoupper(trim((string) (
            data_get($retention->request_payload, 'apply.totalRetencion.moneda')
            ?: data_get($referenceDocument?->request_payload ?? [], 'encabezado.identificacionDocumento.moneda')
            ?: ''
        )));

        $payload = [
            'retention' => [
                'id' => $retention->id,
                'internal_number' => $retention->internal_number,
                'retention_type' => $retention->retention_type,
                'status' => $retention->status,
                'retention_date' => optional($retention->retention_date)->format('Y-m-d'),
                'currency_code' => $retention->currency_code,
                'hka_currency' => $hkaCurrency !== '' ? $hkaCurrency : null,
                'certificate_number' => $retention->certificate_number,
                'retention_rate' => $retention->retention_rate,
                'taxable_base' => $retention->taxable_base,
                'retained_amount' => $retention->retained_amount,
                'notes' => $retention->notes,
            ],
            'related_document' => $referenceDocument ? [
                'serie' => $referenceDocument->serie,
                'tipo_documento' => $referenceDocument->tipo_documento,
                'numero_documento' => $referenceDocument->numero_documento,
                'numero_control' => $referenceDocument->numero_control,
                'url_consulta' => $referenceDocument->url_consulta,
            ] : null,
            'hka' => [
                'apply_request' => data_get($retention->request_payload, 'apply'),
                'apply_response' => data_get($retention->response_payload, 'apply'),
                'status_request' => data_get($retention->request_payload, 'status'),
                'status_response' => data_get($retention->response_payload, 'status'),
            ],
            'generated_at' => now()->toDateTimeString(),
        ];

        $content = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($content === false) {
            abort(500, 'No fue posible generar el JSON de la retención.');
        }

        $fileName = implode('-', array_filter([
            'retencion-hka',
            trim((string) ($retention->internal_number ?? '')) !== '' ? trim((string) ($retention->internal_number ?? '')) : null,
            preg_replace('/[^A-Za-z0-9_-]/', '', (string) ($retention->certificate_number ?? $retention->id)),
        ])) . '.json';

        return response($content, 200, [
            'Content-Type' => 'application/json; charset=UTF-8',
            'Content-Disposition' => PdfDownload::buildDispositionHeader($request, $fileName, (string) ($validated['disposition'] ?? 'attachment')),
        ]);
    }

    public function downloadRetentionCertificate(Request $request, SalesRetention $retention)
    {
        $order = $this->authorizeRetentionAccess($retention);
        $referenceDocument = $this->resolveRetentionReferenceDocument($retention, $order);

        $validated = $request->validate([
            'disposition' => 'nullable|in:inline,attachment',
        ]);

        $retention->loadMissing(['salesOrder.user', 'salesOrder.tenant', 'electronicDocument']);

        $html = view('sales.retentions.certificate-pdf', [
            'retention' => $retention,
            'order' => $order,
            'referenceDocument' => $referenceDocument,
        ])->render();

        $pdf = $this->renderPdf($html);
        $fileName = 'comprobante_retencion_venta_' . ($retention->internal_number ?? $retention->id) . '.pdf';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => PdfDownload::buildDispositionHeader($request, $fileName, (string) ($validated['disposition'] ?? 'inline')),
        ]);
    }

    private function authorizeOrderAccess(SalesOrder $order): void
    {
        $authUser = auth()->user();

        abort_if((int) ($order->tenant_id ?? 0) !== (int) ($authUser->tenant_id ?? 0), 404);

        $isSeller = (bool) ($authUser?->hasStoreRole('seller') ?? false);
        if ($isSeller && (int) ($order->sales_rep_user_id ?? 0) !== (int) ($authUser->id ?? 0)) {
            abort(404);
        }
    }

    private function resolveManualAdjustmentBreakdown(float $amount, ?float $taxRate, float $affectedIgtfAmount): array
    {
        $netAmount = round($amount - max(0, $affectedIgtfAmount), 2);
        if ($netAmount <= 0) {
            return [0.0, 0.0];
        }

        $rate = max(0, (float) ($taxRate ?? 0));
        if ($rate <= 0) {
            return [$netAmount, 0.0];
        }

        $taxableBase = round($netAmount / (1 + ($rate / 100)), 2);
        $taxAmount = round($netAmount - $taxableBase, 2);

        return [$taxableBase, $taxAmount];
    }

    private function authorizeAdjustmentNoteAccess(SalesAdjustmentNote $note): SalesOrder
    {
        $note->loadMissing(['salesOrder.tenant', 'electronicDocument']);
        $order = $note->salesOrder;
        abort_if(!$order, 404);
        $this->authorizeOrderAccess($order);

        return $order;
    }

    private function authorizeRetentionAccess(SalesRetention $retention): SalesOrder
    {
        $retention->loadMissing(['salesOrder.tenant', 'electronicDocument']);
        $order = $retention->salesOrder;
        abort_if(!$order, 404);
        $this->authorizeOrderAccess($order);

        return $order;
    }

    private function resolveOrderCurrencyCode(SalesOrder $order): string
    {
        $code = strtoupper(trim((string) ($order->sale_currency_code ?? $order->tenant?->base_currency ?? 'USD')));
        return in_array($code, ['USD', 'EUR', 'VES'], true) ? $code : 'USD';
    }

    private function isValidIvaCertificate(string $certificateNumber): bool
    {
        if (!preg_match('/^\d{14}$/', $certificateNumber)) {
            return false;
        }

        $period = substr($certificateNumber, 0, 6);
        $year = (int) substr($period, 0, 4);
        $month = (int) substr($period, 4, 2);

        return $year >= 2000 && $year <= 2100 && $month >= 1 && $month <= 12;
    }

    private function resolveLatestInvoiceDocument(SalesOrder $order): ?ElectronicDocument
    {
        $order->loadMissing('electronicDocuments');

        $document = $order->electronicDocuments
            ->where('tipo_documento', '01')
            ->sortByDesc('id')
            ->first();

        return $document instanceof ElectronicDocument ? $document : null;
    }

    private function applyRetentionAsInternalPayment(SalesOrder $order, SalesRetention $retention): void
    {
        $order->loadMissing('tenant');

        $retentionCurrency = TenantCurrency::normalizeCurrencyCode((string) ($retention->currency_code ?? $this->resolveOrderCurrencyCode($order)));
        $baseCurrency = TenantCurrency::resolveBaseCurrencyCode($order->tenant);

        $amountOriginal = round((float) $retention->retained_amount, 2);
        $amountBase = round((float) TenantCurrency::convertAmount(
            $amountOriginal,
            $retentionCurrency,
            $baseCurrency,
            (int) $order->tenant_id
        ), 2);

        $exchangeRateToBase = null;
        if ($amountOriginal > 0) {
            $exchangeRateToBase = round($amountBase / $amountOriginal, 6);
        }

        $paymentMethod = $this->resolveRetentionPaymentMethod((int) $order->tenant_id, $retentionCurrency);
        $reference = 'RET:' . (string) ($retention->internal_number ?? $retention->id);

        Payment::updateOrCreate(
            [
                'sales_order_id' => (int) $order->id,
                'reference' => $reference,
            ],
            [
                'payment_method' => (string) $paymentMethod->id,
                'amount' => $amountBase,
                'amount_original' => $amountOriginal,
                'amount_base' => $amountBase,
                'exchange_rate_to_base' => $exchangeRateToBase,
                'applies_igtf' => false,
                'currency' => $retentionCurrency,
                'status' => 1,
            ]
        );

        $totalPaidBase = (float) $order->payments()->where('status', 1)->sum(DB::raw('COALESCE(amount_base, amount, 0)'));
        $order->update([
            'total_paid_base' => round($totalPaidBase, 2),
        ]);

        $retention->update([
            'status' => 'applied',
            'applied_at' => now(),
        ]);
    }

    private function syncRetentionToHka(SalesOrder $order, SalesRetention $retention, $referenceDocument): array
    {
        $certificate = preg_replace('/[^A-Za-z0-9]/', '', (string) ($retention->certificate_number ?? ''));
        if ($certificate === '') {
            return [
                'ok' => false,
                'message' => 'HKA: falta el número de comprobante para sincronizar la retención.',
            ];
        }

        if (!$this->hkaService->isConfigured()) {
            return [
                'ok' => false,
                'message' => 'HKA: la integración no está configurada para sincronizar retenciones.',
            ];
        }

        $payload = $this->hkaService->buildRetentionPayloadFromOrder($order, $retention, $referenceDocument);
        $response = $this->hkaService->applyRetention($payload);
        $responseData = is_array($response['data'] ?? null) ? $response['data'] : [];
        $hkaCode = (string) Arr::get($responseData, 'codigo', '');
        $message = (string) ($response['message'] ?? '');

        $statusPayload = $this->hkaService->buildRetentionLookupPayload($referenceDocument);
        $statusResponseData = [];
        $statusOk = false;
        $statusCode = '';
        $statusMessage = '';

        if ($response['ok'] ?? false) {
            $statusResponse = $this->hkaService->getRetention($statusPayload);
            $statusResponseData = is_array($statusResponse['data'] ?? null) ? $statusResponse['data'] : [];
            $statusOk = (bool) ($statusResponse['ok'] ?? false);
            $statusCode = (string) Arr::get($statusResponseData, 'codigo', '');
            $statusMessage = (string) ($statusResponse['message'] ?? '');
        }

        $retention->update([
            'status' => ($response['ok'] ?? false) && $statusOk ? 'applied_hka' : (($response['ok'] ?? false) ? 'applied' : 'applied_hka_error'),
            'request_payload' => $this->mergeRetentionPayloadSegment(
                $this->mergeRetentionPayloadSegment($retention->request_payload, 'apply', $payload),
                'status',
                $statusPayload
            ),
            'response_payload' => $this->mergeRetentionPayloadSegment(
                $this->mergeRetentionPayloadSegment($retention->response_payload, 'apply', $responseData ?: [
                    'message' => $message,
                    'status' => $response['status'] ?? null,
                ]),
                'status',
                !empty($statusResponseData) ? $statusResponseData : [
                    'message' => $statusMessage !== '' ? $statusMessage : null,
                    'status' => $response['status'] ?? null,
                ]
            ),
        ]);

        if (!($response['ok'] ?? false)) {
            return [
                'ok' => false,
                'message' => 'HKA: ' . ($message !== '' ? $message : 'no fue posible sincronizar la retención.')
                    . ($hkaCode !== '' ? ' Código HKA: ' . $hkaCode . '.' : ''),
            ];
        }

        if (!$statusOk) {
            return [
                'ok' => true,
                'message' => 'HKA: retención sincronizada correctamente' . ($hkaCode !== '' ? ' (código ' . $hkaCode . ').' : '.')
                    . ' La confirmación inmediata en HKA quedó pendiente; usa Consultar HKA para refrescarla.',
            ];
        }

        return [
            'ok' => true,
            'message' => 'HKA: retención sincronizada y confirmada correctamente'
                . ($hkaCode !== '' ? ' (aplicar ' . $hkaCode . ')' : '')
                . ($statusCode !== '' ? ' (consulta ' . $statusCode . ').' : '.'),
        ];
    }

    private function resolveRetentionReferenceDocument(SalesRetention $retention, SalesOrder $order)
    {
        $document = $retention->electronicDocument;
        if ($document && !$document->is_annulled && $document->numero_documento && $document->numero_control) {
            return $document;
        }

        return $order->electronicDocuments()
            ->where('is_annulled', false)
            ->whereNotNull('numero_documento')
            ->whereNotNull('numero_control')
            ->latest('id')
            ->first();
    }

    private function buildAdjustmentNoteDownloadPayload(SalesAdjustmentNote $note, string $fileType): array
    {
        $requestPayload = is_array($note->request_payload) ? $note->request_payload : [];
        $responsePayload = is_array($note->response_payload) ? $note->response_payload : [];

        return array_filter([
            'serie' => (string) (
                data_get($responsePayload, 'resultado.serie')
                ?: data_get($requestPayload, 'encabezado.identificacionDocumento.serie')
                ?: optional($note->electronicDocument)->serie
                ?: ''
            ),
            'tipoDocumento' => (string) (
                $note->document_code
                ?: data_get($responsePayload, 'resultado.tipoDocumento')
                ?: data_get($requestPayload, 'encabezado.identificacionDocumento.tipoDocumento')
                ?: ($note->note_type === 'credit' ? '02' : '03')
            ),
            'numeroDocumento' => (string) (
                data_get($responsePayload, 'resultado.numeroDocumento')
                ?: data_get($requestPayload, 'encabezado.identificacionDocumento.numeroDocumento')
                ?: preg_replace('/\D+/', '', (string) ($note->internal_number ?? $note->id))
            ),
            'tipoArchivo' => $fileType,
        ], static fn ($value) => !is_null($value) && $value !== '');
    }

    private function mergeRetentionPayloadSegment($currentPayload, string $segment, array $payload): array
    {
        $data = is_array($currentPayload) ? $currentPayload : [];
        $data[$segment] = $payload;

        return $data;
    }

    private function resolveRetentionPaymentMethod(int $tenantId, string $retentionCurrency): PaymentMethod
    {
        $normalizedCurrency = TenantCurrency::normalizeCurrencyCode($retentionCurrency);
        $currencyCode = $normalizedCurrency === 'BS' ? 'BS' : $normalizedCurrency;

        $currency = Currency::query()
            ->where('code', $currencyCode)
            ->orderByRaw('CASE WHEN tenant_id = ? THEN 0 WHEN tenant_id IS NULL THEN 1 ELSE 2 END', [$tenantId])
            ->first();

        if (!$currency) {
            $currency = Currency::query()
                ->whereIn('code', [$currencyCode, 'USD', 'BS'])
                ->orderByRaw('CASE WHEN code = ? THEN 0 WHEN code = ? THEN 1 ELSE 2 END', [$currencyCode, 'USD'])
                ->first();
        }

        if (!$currency) {
            $existingTenantMethod = PaymentMethod::query()
                ->where('tenant_id', $tenantId)
                ->whereNotNull('currency_id')
                ->orderBy('id')
                ->first();

            if ($existingTenantMethod) {
                $currency = Currency::query()->find($existingTenantMethod->currency_id);
            }
        }

        if (!$currency) {
            throw new \RuntimeException('No existe una moneda configurada para registrar la retención como pago interno.');
        }

        return PaymentMethod::query()->firstOrCreate(
            [
                'tenant_id' => $tenantId,
                'name' => 'Retención Fiscal',
            ],
            [
                'currency_id' => $currency?->id,
                'status' => 1,
                'has_reference' => true,
                'applies_igtf_base' => false,
                'description' => 'Metodo interno para aplicar retenciones de ventas al saldo pendiente.',
            ]
        );
    }

    private function renderPdf(string $html): string
    {
        @ini_set('max_execution_time', '180');
        @set_time_limit(180);
        @ini_set('memory_limit', '512M');

        try {
            $options = new Options();
            $options->set('isHtml5ParserEnabled', true);
            $options->set('isPhpEnabled', true);

            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($html, 'UTF-8');
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            return $dompdf->output();
        } catch (\Throwable $exception) {
            throw new \RuntimeException('[PDF] No se pudo generar el documento fiscal en este momento. Intenta nuevamente.', 0, $exception);
        }
    }
}