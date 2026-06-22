<?php

namespace App\Http\Controllers;

use App\Models\IslrWithholding;
use App\Models\IslrWithholdingConcept;
use App\Models\PurchaseVatRetention;
use App\Services\TheFactoryHkaService;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class WithholdingController extends Controller
{
    public function __construct(
        private readonly TheFactoryHkaService $hkaService
    ) {}

    public function islrConceptsIndex()
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);

        $concepts = IslrWithholdingConcept::query()
            ->where(function ($query) use ($tenantId) {
                $query->whereNull('tenant_id')->orWhere('tenant_id', $tenantId);
            })
            ->orderByRaw('CASE WHEN tenant_id IS NULL THEN 0 ELSE 1 END DESC')
            ->orderBy('code')
            ->get();

        return view('withholdings.islr-concepts', compact('concepts'));
    }

    public function islrConceptsStore(Request $request)
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);

        $validated = $request->validate([
            'code' => 'required|string|max:40',
            'name' => 'required|string|max:160',
            'rate_percent' => 'required|numeric|min:0|max:100',
            'sustraendo_ut' => 'nullable|numeric|min:0',
            'applicable_person_type' => 'required|in:any,pn,pj',
            'applicable_residency_type' => 'required|in:any,domiciliado,no_domiciliado',
            'is_active' => 'nullable|boolean',
        ]);

        IslrWithholdingConcept::query()->updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'code' => strtoupper(trim((string) $validated['code'])),
            ],
            [
                'name' => trim((string) $validated['name']),
                'rate_percent' => (float) $validated['rate_percent'],
                'sustraendo_ut' => (float) ($validated['sustraendo_ut'] ?? 0),
                'applicable_person_type' => (string) $validated['applicable_person_type'],
                'applicable_residency_type' => (string) $validated['applicable_residency_type'],
                'is_active' => (bool) ($validated['is_active'] ?? true),
            ]
        );

        return back()->with('success', 'Concepto ISLR guardado correctamente.');
    }

    public function islrConceptsUpdate(Request $request, IslrWithholdingConcept $concept)
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        abort_if((int) ($concept->tenant_id ?? 0) !== $tenantId, 403);

        $validated = $request->validate([
            'name' => 'required|string|max:160',
            'rate_percent' => 'required|numeric|min:0|max:100',
            'sustraendo_ut' => 'nullable|numeric|min:0',
            'applicable_person_type' => 'required|in:any,pn,pj',
            'applicable_residency_type' => 'required|in:any,domiciliado,no_domiciliado',
            'is_active' => 'nullable|boolean',
        ]);

        $concept->update([
            'name' => trim((string) $validated['name']),
            'rate_percent' => (float) $validated['rate_percent'],
            'sustraendo_ut' => (float) ($validated['sustraendo_ut'] ?? 0),
            'applicable_person_type' => (string) $validated['applicable_person_type'],
            'applicable_residency_type' => (string) $validated['applicable_residency_type'],
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]);

        return back()->with('success', 'Concepto ISLR actualizado correctamente.');
    }

    public function exportVatTxt(Request $request)
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        [$startDate, $endDate] = $this->resolveFortnightRange($request);

        $retentions = PurchaseVatRetention::query()
            ->with(['provider', 'purchaseOrder'])
            ->where('tenant_id', $tenantId)
            ->whereDate('retention_date', '>=', $startDate->toDateString())
            ->whereDate('retention_date', '<=', $endDate->toDateString())
            ->orderBy('retention_date')
            ->orderBy('id')
            ->get();

        $lines = $retentions->map(function (PurchaseVatRetention $retention) {
            return implode('|', [
                preg_replace('/[^A-Z0-9]/', '', strtoupper((string) ($retention->provider->rif ?? ''))),
                (string) ($retention->invoice_number ?? ''),
                (string) ($retention->control_number ?? ''),
                'C',
                number_format((float) $retention->taxable_base, 2, '.', ''),
                number_format((float) $retention->tax_amount, 2, '.', ''),
                number_format((float) $retention->retained_amount, 2, '.', ''),
                Carbon::parse($retention->retention_date)->format('d/m/Y'),
                (string) ($retention->certificate_number ?? ''),
            ]);
        })->all();

        $content = implode("\n", $lines) . (count($lines) > 0 ? "\n" : '');
        $filename = 'retenciones_iva_seniat_' . $startDate->format('Ymd') . '_' . $endDate->format('Ymd') . '.txt';

        return response($content, 200, [
            'Content-Type' => 'text/plain; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function exportIslrXml(Request $request)
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        $month = trim((string) $request->query('month', now()->format('Y-m')));
        $start = Carbon::parse($month . '-01')->startOfMonth();
        $end = Carbon::parse($month . '-01')->endOfMonth();

        $rows = IslrWithholding::query()
            ->with(['provider', 'concept'])
            ->where('tenant_id', $tenantId)
            ->whereDate('retention_date', '>=', $start->toDateString())
            ->whereDate('retention_date', '<=', $end->toDateString())
            ->orderBy('retention_date')
            ->orderBy('id')
            ->get();

        $xml = new \SimpleXMLElement('<?xml version="1.0" encoding="UTF-8"?><RetencionesISLR/>');
        $xml->addChild('Periodo', $start->format('m/Y'));
        $itemsNode = $xml->addChild('Comprobantes');

        foreach ($rows as $row) {
            $item = $itemsNode->addChild('Comprobante');
            $item->addChild('Numero', (string) ($row->certificate_number ?? ''));
            $item->addChild('Fecha', Carbon::parse($row->retention_date)->format('d/m/Y'));
            $item->addChild('RIF', preg_replace('/[^A-Z0-9]/', '', strtoupper((string) ($row->provider->rif ?? ''))));
            $item->addChild('Concepto', (string) ($row->concept->code ?? ''));
            $item->addChild('Base', number_format((float) $row->base_amount, 2, '.', ''));
            $item->addChild('Porcentaje', number_format((float) $row->rate_percent, 4, '.', ''));
            $item->addChild('SustraendoUT', number_format((float) $row->sustraendo_ut, 4, '.', ''));
            $item->addChild('SustraendoMonto', number_format((float) $row->sustraendo_amount, 2, '.', ''));
            $item->addChild('Retenido', number_format((float) $row->retained_amount, 2, '.', ''));
        }

        $filename = 'retenciones_islr_' . $start->format('Ym') . '.xml';

        return response($xml->asXML(), 200, [
            'Content-Type' => 'application/xml; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function purchaseVatCertificatePdf(PurchaseVatRetention $retention)
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        abort_if((int) $retention->tenant_id !== $tenantId, 404);

        $retention->loadMissing(['provider', 'purchaseOrder']);

        $html = view('withholdings.pdf.purchase-vat-certificate', compact('retention'))->render();
        $pdf = $this->renderPdf($html);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="comprobante_retencion_iva_' . $retention->id . '.pdf"',
        ]);
    }

    public function purchaseVatSyncHka(PurchaseVatRetention $retention): RedirectResponse
    {
        $retention = $this->authorizePurchaseVatRetention($retention);
        $result = $this->emitPurchaseRetentionDocumentToHka(
            $retention,
            $retention->accountPayable,
            $this->hkaService->buildPurchaseVatRetentionDocumentPayload($retention, $retention->accountPayable),
            'retención IVA de compra'
        );

        if (!($result['ok'] ?? false)) {
            return back()->with('error', $result['message'] ?? 'No fue posible sincronizar la retención de IVA con HKA.');
        }

        return back()->with('success', $result['message']);
    }

    public function purchaseVatStatusHka(PurchaseVatRetention $retention): RedirectResponse
    {
        $retention = $this->authorizePurchaseVatRetention($retention);
        $result = $this->refreshPurchaseRetentionDocumentStatus($retention, 'retención IVA de compra');

        if (!($result['ok'] ?? false)) {
            return back()->with('error', $result['message'] ?? 'No fue posible consultar la retención de IVA en HKA.');
        }

        return back()->with('success', $result['message']);
    }

    public function purchaseVatDownloadHkaSnapshot(Request $request, PurchaseVatRetention $retention)
    {
        $retention = $this->authorizePurchaseVatRetention($retention);

        return $this->downloadRetentionSnapshot(
            $request,
            $retention,
            'retencion-compra-iva-hka',
            [
                'invoice_number' => $retention->invoice_number ?: $retention->accountPayable?->invoice_number ?: $retention->accountPayable?->document_number,
                'control_number' => $retention->control_number ?: $retention->accountPayable?->control_number,
                'provider' => [
                    'name' => $retention->provider->name ?? null,
                    'rif' => $retention->provider->rif ?? null,
                ],
            ]
        );
    }

    public function purchaseVatDownloadHkaPdf(Request $request, PurchaseVatRetention $retention)
    {
        $retention = $this->authorizePurchaseVatRetention($retention);

        return $this->downloadRetentionPdfFromHka(
            $request,
            $retention,
            'retencion-iva-compra'
        );
    }

    public function islrCertificatePdf(IslrWithholding $withholding)
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        abort_if((int) $withholding->tenant_id !== $tenantId, 404);

        $withholding->loadMissing(['provider', 'concept', 'accountPayable']);

        $html = view('withholdings.pdf.islr-certificate', compact('withholding'))->render();
        $pdf = $this->renderPdf($html);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="comprobante_retencion_islr_' . $withholding->id . '.pdf"',
        ]);
    }

    public function islrSyncHka(IslrWithholding $withholding): RedirectResponse
    {
        $withholding = $this->authorizeIslrWithholding($withholding);
        $result = $this->emitPurchaseRetentionDocumentToHka(
            $withholding,
            $withholding->accountPayable,
            $this->hkaService->buildPurchaseIslrRetentionDocumentPayload($withholding, $withholding->accountPayable),
            'retención ISLR de compra'
        );

        if (!($result['ok'] ?? false)) {
            return back()->with('error', $result['message'] ?? 'No fue posible sincronizar la retención de ISLR con HKA.');
        }

        return back()->with('success', $result['message']);
    }

    public function islrStatusHka(IslrWithholding $withholding): RedirectResponse
    {
        $withholding = $this->authorizeIslrWithholding($withholding);
        $result = $this->refreshPurchaseRetentionDocumentStatus($withholding, 'retención ISLR de compra');

        if (!($result['ok'] ?? false)) {
            return back()->with('error', $result['message'] ?? 'No fue posible consultar la retención de ISLR en HKA.');
        }

        return back()->with('success', $result['message']);
    }

    public function islrDownloadHkaSnapshot(Request $request, IslrWithholding $withholding)
    {
        $withholding = $this->authorizeIslrWithholding($withholding);

        return $this->downloadRetentionSnapshot(
            $request,
            $withholding,
            'retencion-compra-islr-hka',
            [
                'invoice_number' => $withholding->invoice_number ?: $withholding->accountPayable?->invoice_number ?: $withholding->accountPayable?->document_number,
                'control_number' => $withholding->control_number ?: $withholding->accountPayable?->control_number,
                'provider' => [
                    'name' => $withholding->provider->name ?? null,
                    'rif' => $withholding->provider->rif ?? null,
                ],
                'concept' => [
                    'code' => $withholding->concept->code ?? null,
                    'name' => $withholding->concept->name ?? null,
                ],
            ]
        );
    }

    public function islrDownloadHkaPdf(Request $request, IslrWithholding $withholding)
    {
        $withholding = $this->authorizeIslrWithholding($withholding);

        return $this->downloadRetentionPdfFromHka(
            $request,
            $withholding,
            'retencion-islr-compra'
        );
    }

    private function authorizePurchaseVatRetention(PurchaseVatRetention $retention): PurchaseVatRetention
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        abort_if((int) $retention->tenant_id !== $tenantId, 404);

        $retention->loadMissing(['provider', 'purchaseOrder', 'accountPayable']);

        return $retention;
    }

    private function authorizeIslrWithholding(IslrWithholding $withholding): IslrWithholding
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        abort_if((int) $withholding->tenant_id !== $tenantId, 404);

        $withholding->loadMissing(['provider', 'concept', 'accountPayable']);

        return $withholding;
    }

    private function resolvePurchaseReferenceDocument(string $invoiceNumber, string $controlNumber, string $currencyCode): array
    {
        return [
            'serie' => (string) config('services.thefactory_hka.default_serie', ''),
            'tipo_documento' => '01',
            'numero_documento' => $invoiceNumber,
            'numero_control' => $controlNumber,
            'currency_code' => $currencyCode !== '' ? $currencyCode : 'USD',
        ];
    }

    private function emitPurchaseRetentionDocumentToHka($retention, ?\App\Models\AccountPayable $accountPayable, array $payload, string $label): array
    {
        if (!$accountPayable) {
            return ['ok' => false, 'message' => 'HKA: la retención no tiene cuenta por pagar asociada.'];
        }

        if (!$this->hkaService->isConfigured()) {
            return ['ok' => false, 'message' => 'HKA: la integración no está configurada para emitir retenciones.'];
        }

        $response = $this->hkaService->emitDocument($payload);
        $responseData = is_array($response['data'] ?? null) ? $response['data'] : [];
        $emitAccepted = ($response['ok'] ?? false) && $this->isAcceptedHkaDocumentResponse($responseData, (string) ($response['message'] ?? ''));
        $statusPayload = [];
        $statusResponseData = [];
        $statusOk = false;
        $statusMessage = '';

        if ($emitAccepted) {
            $statusPayload = $this->buildPurchaseRetentionStatusPayload($retention, $responseData, $payload);
            if (!empty($statusPayload)) {
                $statusResponse = $this->hkaService->getDocumentStatus($statusPayload);
                $statusResponseData = is_array($statusResponse['data'] ?? null) ? $statusResponse['data'] : [];
                $statusMessage = (string) ($statusResponse['message'] ?? '');
                $statusOk = ($statusResponse['ok'] ?? false) && $this->isAcceptedHkaDocumentResponse($statusResponseData, $statusMessage);
            }
        }

        $retention->update([
            'status' => $emitAccepted && $statusOk ? 'applied_hka' : ($emitAccepted ? 'issued' : 'applied_hka_error'),
            'request_payload' => $this->mergePayloadSegment(
                $this->mergePayloadSegment($retention->request_payload, 'emit', $payload),
                'status',
                $statusPayload
            ),
            'response_payload' => $this->mergePayloadSegment(
                $this->mergePayloadSegment($retention->response_payload, 'emit', $responseData ?: [
                    'message' => $response['message'] ?? null,
                    'status' => $response['status'] ?? null,
                ]),
                'status',
                !empty($statusResponseData) ? $statusResponseData : [
                    'message' => $statusMessage !== '' ? $statusMessage : null,
                ]
            ),
        ]);

        if (!$emitAccepted) {
            return [
                'ok' => false,
                'message' => 'HKA: no fue posible emitir la ' . $label . '. ' . ((string) ($response['message'] ?? '') !== '' ? (string) $response['message'] : 'Error desconocido.'),
            ];
        }

        if (!$statusOk) {
            return [
                'ok' => true,
                'message' => 'HKA: ' . ucfirst($label) . ' emitida correctamente. La confirmación inmediata quedó pendiente; usa Consultar HKA para refrescarla.',
            ];
        }

        return [
            'ok' => true,
            'message' => 'HKA: ' . ucfirst($label) . ' emitida y confirmada correctamente.',
        ];
    }

    private function refreshPurchaseRetentionDocumentStatus($retention, string $label): array
    {
        $payload = $this->buildPurchaseRetentionStatusPayload($retention);
        if (empty($payload)) {
            return ['ok' => false, 'message' => 'HKA: la retención aún no tiene numeración emitida para consultar estado.'];
        }

        $response = $this->hkaService->getDocumentStatus($payload);
        $responseData = is_array($response['data'] ?? null) ? $response['data'] : [];
        $accepted = ($response['ok'] ?? false) && $this->isAcceptedHkaDocumentResponse($responseData, (string) ($response['message'] ?? ''));

        $retention->update([
            'status' => $accepted ? 'applied_hka' : 'applied_hka_error',
            'request_payload' => $this->mergePayloadSegment($retention->request_payload, 'status', $payload),
            'response_payload' => $this->mergePayloadSegment($retention->response_payload, 'status', $responseData ?: [
                'message' => $response['message'] ?? null,
                'status' => $response['status'] ?? null,
            ]),
        ]);

        if (!$accepted) {
            return ['ok' => false, 'message' => 'HKA: no fue posible consultar la ' . $label . '. ' . ((string) ($response['message'] ?? '') !== '' ? (string) $response['message'] : 'Error desconocido.')];
        }

        return ['ok' => true, 'message' => 'Consulta HKA actualizada para la ' . $label . '.'];
    }

    private function buildPurchaseRetentionStatusPayload($retention, array $emitResponse = [], array $emitPayload = []): array
    {
        $responsePayload = is_array($retention->response_payload) ? $retention->response_payload : [];
        $requestPayload = is_array($retention->request_payload) ? $retention->request_payload : [];

        return array_filter([
            'serie' => (string) (
                data_get($emitResponse, 'resultado.serie')
                ?: data_get($responsePayload, 'emit.resultado.serie')
                ?: data_get($emitPayload, 'encabezado.identificacionDocumento.serie')
                ?: data_get($requestPayload, 'emit.encabezado.identificacionDocumento.serie')
                ?: config('services.thefactory_hka.default_serie', '')
            ),
            'tipoDocumento' => '05',
            'numeroDocumento' => (string) (
                data_get($emitResponse, 'resultado.numeroDocumento')
                ?: data_get($responsePayload, 'emit.resultado.numeroDocumento')
                ?: data_get($emitPayload, 'encabezado.identificacionDocumento.numeroDocumento')
                ?: data_get($requestPayload, 'emit.encabezado.identificacionDocumento.numeroDocumento')
                ?: ''
            ),
            'transaccionId' => (string) (
                data_get($emitResponse, 'resultado.transaccionId')
                ?: data_get($responsePayload, 'emit.resultado.transaccionId')
                ?: data_get($emitPayload, 'encabezado.identificacionDocumento.transaccionId')
                ?: data_get($requestPayload, 'emit.encabezado.identificacionDocumento.transaccionId')
                ?: ''
            ),
        ], static fn ($value) => !is_null($value) && $value !== '');
    }

    private function isAcceptedHkaDocumentResponse(array $responseData, string $fallbackMessage = ''): bool
    {
        $validations = Arr::get($responseData, 'validaciones', []);
        if (is_array($validations) && !empty($validations)) {
            return false;
        }

        $code = trim((string) Arr::get($responseData, 'codigo', ''));
        if ($code !== '' && !in_array($code, ['200', '201'], true)) {
            return false;
        }

        $message = trim((string) (Arr::get($responseData, 'mensaje', '') ?: $fallbackMessage));
        $messageLower = mb_strtolower($message);
        foreach (['no procesado', 'error', 'rechazado'] as $needle) {
            if ($messageLower !== '' && str_contains($messageLower, $needle)) {
                return false;
            }
        }

        return true;
    }

    private function syncRetentionToHka($retention, array $referenceDocument, array $retentionData, array $options, string $label): array
    {
        $invoiceNumber = trim((string) ($referenceDocument['numero_documento'] ?? ''));
        $controlNumber = trim((string) ($referenceDocument['numero_control'] ?? ''));
        $certificate = preg_replace('/[^A-Za-z0-9]/', '', (string) ($retentionData['certificate_number'] ?? ''));

        if ($certificate === '') {
            return ['ok' => false, 'message' => 'HKA: falta el número de comprobante para sincronizar la ' . $label . '.'];
        }

        if ($invoiceNumber === '' || $controlNumber === '') {
            return ['ok' => false, 'message' => 'HKA: faltan número de factura o control del proveedor para sincronizar la ' . $label . '.'];
        }

        if (!$this->hkaService->isConfigured()) {
            return ['ok' => false, 'message' => 'HKA: la integración no está configurada para sincronizar retenciones.'];
        }

        $payload = $this->hkaService->buildRetentionPayloadFromReference($referenceDocument, $retentionData, (int) $retention->tenant_id, $options);
        $response = $this->hkaService->applyRetention($payload);
        $responseData = is_array($response['data'] ?? null) ? $response['data'] : [];
        $hkaCode = (string) Arr::get($responseData, 'codigo', '');
        $message = (string) ($response['message'] ?? '');

        $statusPayload = $this->hkaService->buildRetentionLookupPayloadFromReference($referenceDocument);
        $statusResponseData = [];
        $statusOk = false;
        $statusCode = '';
        $statusMessage = '';

        if (($response['ok'] ?? false) && $this->isAcceptedHkaRetentionResponse($responseData, $message)) {
            $statusResponse = $this->hkaService->getRetention($statusPayload);
            $statusResponseData = is_array($statusResponse['data'] ?? null) ? $statusResponse['data'] : [];
            $statusOk = (bool) ($statusResponse['ok'] ?? false) && $this->isAcceptedHkaRetentionResponse($statusResponseData, (string) ($statusResponse['message'] ?? ''));
            $statusCode = (string) Arr::get($statusResponseData, 'codigo', '');
            $statusMessage = (string) ($statusResponse['message'] ?? '');
        }

        $applyAccepted = ($response['ok'] ?? false) && $this->isAcceptedHkaRetentionResponse($responseData, $message);

        $retention->update([
            'status' => $applyAccepted && $statusOk ? 'applied_hka' : ($applyAccepted ? 'issued' : 'applied_hka_error'),
            'request_payload' => $this->mergePayloadSegment(
                $this->mergePayloadSegment($retention->request_payload, 'apply', $payload),
                'status',
                $statusPayload
            ),
            'response_payload' => $this->mergePayloadSegment(
                $this->mergePayloadSegment($retention->response_payload, 'apply', $responseData ?: [
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

        if (!$applyAccepted) {
            return [
                'ok' => false,
                'message' => 'HKA: ' . ($message !== '' ? $message : 'no fue posible sincronizar la ' . $label . '.')
                    . ($hkaCode !== '' ? ' Código HKA: ' . $hkaCode . '.' : ''),
            ];
        }

        if (!$statusOk) {
            return [
                'ok' => true,
                'message' => 'HKA: ' . ucfirst($label) . ' sincronizada correctamente'
                    . ($hkaCode !== '' ? ' (código ' . $hkaCode . ').' : '.')
                    . ' La confirmación inmediata quedó pendiente; usa Consultar HKA para refrescarla.',
            ];
        }

        return [
            'ok' => true,
            'message' => 'HKA: ' . ucfirst($label) . ' sincronizada y confirmada correctamente'
                . ($hkaCode !== '' ? ' (aplicar ' . $hkaCode . ')' : '')
                . ($statusCode !== '' ? ' (consulta ' . $statusCode . ').' : '.'),
        ];
    }

    private function refreshRetentionHkaStatus($retention, array $referenceDocument): array
    {
        $invoiceNumber = trim((string) ($referenceDocument['numero_documento'] ?? ''));
        $controlNumber = trim((string) ($referenceDocument['numero_control'] ?? ''));

        if ($invoiceNumber === '' || $controlNumber === '') {
            return ['ok' => false, 'message' => 'HKA: faltan número de factura o control del proveedor para consultar esta retención.'];
        }

        $queryPayload = $this->hkaService->buildRetentionLookupPayloadFromReference($referenceDocument);
        $response = $this->hkaService->getRetention($queryPayload);
        $responseData = is_array($response['data'] ?? null) ? $response['data'] : [];

        $accepted = ($response['ok'] ?? false) && $this->isAcceptedHkaRetentionResponse($responseData, (string) ($response['message'] ?? ''));

        $retention->update([
            'status' => $accepted ? 'applied_hka' : (($retention->status ?? '') === 'applied_hka' ? 'applied_hka_error' : 'applied_hka_error'),
            'request_payload' => $this->mergePayloadSegment($retention->request_payload, 'status', $queryPayload),
            'response_payload' => $this->mergePayloadSegment($retention->response_payload, 'status', $responseData ?: [
                'message' => $response['message'] ?? null,
                'status' => $response['status'] ?? null,
            ]),
        ]);

        if (!$accepted) {
            return [
                'ok' => false,
                'message' => ($response['message'] ?? 'No fue posible consultar la retención en HKA.')
                    . ((string) Arr::get($responseData, 'codigo', '') !== '' ? ' Código HKA: ' . Arr::get($responseData, 'codigo') . '.' : ''),
            ];
        }

        return [
            'ok' => true,
            'message' => 'Consulta HKA actualizada para la retención ' . ($retention->certificate_number ?? $retention->id) . '.',
        ];
    }

    private function downloadRetentionSnapshot(Request $request, $retention, string $prefix, array $extraPayload = [])
    {
        $validated = $request->validate([
            'disposition' => 'nullable|in:inline,attachment',
        ]);

        $payload = [
            'retention' => [
                'id' => $retention->id,
                'certificate_number' => $retention->certificate_number ?? null,
                'status' => $retention->status ?? null,
                'retention_date' => optional($retention->retention_date)->format('Y-m-d'),
                'currency_code' => $retention->currency_code ?? null,
            ],
            'context' => $extraPayload,
            'hka' => [
                'emit_request' => data_get($retention->request_payload, 'emit'),
                'emit_response' => data_get($retention->response_payload, 'emit'),
                'status_request' => data_get($retention->request_payload, 'status'),
                'status_response' => data_get($retention->response_payload, 'status'),
            ],
            'generated_at' => now()->toDateTimeString(),
        ];

        $content = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        if ($content === false) {
            abort(500, 'No fue posible generar el JSON de la retención.');
        }

        $fileName = $prefix . '-' . preg_replace('/[^A-Za-z0-9\-]/', '', (string) ($retention->certificate_number ?? $retention->id)) . '.json';

        return response($content, 200, [
            'Content-Type' => 'application/json; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    private function downloadRetentionPdfFromHka(Request $request, $retention, string $filePrefix)
    {
        if (!$this->hkaService->isConfigured()) {
            return back()->with('error', 'HKA: la integración no está configurada para descargar retenciones.');
        }

        $validated = $request->validate([
            'disposition' => 'nullable|in:inline,attachment',
        ]);

        $payload = $this->buildPurchaseRetentionDocumentDownloadPayload($retention);
        if (trim((string) ($payload['numeroDocumento'] ?? '')) === '') {
            return back()->with('error', 'HKA: la retención no tiene numeración suficiente para descargar el PDF emitido por HKA.');
        }

        $response = $this->hkaService->downloadDocumentFile($payload);
        if (!($response['ok'] ?? false) || empty($response['content'])) {
            return back()->with('error', 'HKA: no fue posible descargar el PDF de la retención. ' . ($response['message'] ?? 'Error desconocido.'));
        }

        $responsePayload = is_array($retention->response_payload) ? $retention->response_payload : [];
        $downloads = is_array($responsePayload['downloads'] ?? null) ? $responsePayload['downloads'] : [];
        $downloads['pdf'] = array_filter([
            'codigo' => Arr::get($response, 'data.codigo'),
            'mensaje' => Arr::get($response, 'data.mensaje', $response['message'] ?? null),
            'downloaded_at' => now()->toDateTimeString(),
        ], static fn ($value) => !is_null($value) && $value !== '');
        $responsePayload['downloads'] = $downloads;
        $retention->update(['response_payload' => $responsePayload]);

        $fileName = $filePrefix . '-' . trim((string) ($payload['numeroDocumento'] ?? ($retention->certificate_number ?? $retention->id))) . '.pdf';

        return response((string) $response['content'], 200, [
            'Content-Type' => (string) ($response['mime_type'] ?? 'application/pdf'),
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
        ]);
    }

    private function buildRetentionDocumentDownloadPayload($retention): array
    {
        $responsePayload = is_array($retention->response_payload) ? $retention->response_payload : [];
        $requestPayload = is_array($retention->request_payload) ? $retention->request_payload : [];

        return array_filter([
            'serie' => (string) (
                data_get($responsePayload, 'apply.resultado.serie')
                ?: data_get($responsePayload, 'apply.serie')
                ?: config('services.thefactory_hka.default_serie', '')
            ),
            'tipoDocumento' => (string) (
                data_get($responsePayload, 'apply.resultado.tipoDocumento')
                ?: data_get($responsePayload, 'apply.tipoDocumento')
                ?: '05'
            ),
            'numeroDocumento' => (string) (
                data_get($responsePayload, 'apply.resultado.numeroDocumento')
                ?: data_get($responsePayload, 'apply.numeroDocumento')
                ?: preg_replace('/\D+/', '', (string) ($retention->certificate_number ?? $retention->id))
            ),
            'tipoArchivo' => 'pdf',
        ], static fn ($value) => !is_null($value) && $value !== '');
    }

    private function buildPurchaseRetentionDocumentDownloadPayload($retention): array
    {
        $responsePayload = is_array($retention->response_payload) ? $retention->response_payload : [];
        $requestPayload = is_array($retention->request_payload) ? $retention->request_payload : [];

        return array_filter([
            'serie' => (string) (
                data_get($responsePayload, 'status.resultado.serie')
                ?: data_get($responsePayload, 'status.serie')
                ?: data_get($responsePayload, 'emit.resultado.serie')
                ?: data_get($responsePayload, 'status.estado.serie')
                ?: data_get($responsePayload, 'emit.serie')
                ?: data_get($requestPayload, 'emit.encabezado.identificacionDocumento.serie')
                ?: config('services.thefactory_hka.default_serie', '')
            ),
            'tipoDocumento' => '05',
            'numeroDocumento' => (string) (
                data_get($responsePayload, 'status.resultado.numeroDocumento')
                ?: data_get($responsePayload, 'status.numeroDocumento')
                ?: data_get($responsePayload, 'emit.resultado.numeroDocumento')
                ?: data_get($responsePayload, 'status.estado.numeroDocumento')
                ?: data_get($responsePayload, 'emit.numeroDocumento')
                ?: data_get($requestPayload, 'emit.encabezado.identificacionDocumento.numeroDocumento')
                ?: ''
            ),
            'tipoArchivo' => 'pdf',
        ], static fn ($value) => !is_null($value) && $value !== '');
    }

    private function isAcceptedHkaRetentionResponse(array $responseData, string $fallbackMessage = ''): bool
    {
        $code = trim((string) Arr::get($responseData, 'codigo', Arr::get($responseData, 'Codigo', '')));
        $message = trim((string) (Arr::get($responseData, 'mensaje', Arr::get($responseData, 'Mensaje', '')) ?: $fallbackMessage));
        $validations = Arr::get($responseData, 'validaciones', []);

        if (is_array($validations) && !empty($validations)) {
            return false;
        }

        $messageLower = mb_strtolower($message);
        foreach (['no aplicada', 'no encontrada', 'no existe', 'error'] as $needle) {
            if ($messageLower !== '' && str_contains($messageLower, $needle)) {
                return false;
            }
        }

        if ($code !== '' && in_array($code, ['203', '400', '401', '404', '422', '500'], true)) {
            return false;
        }

        return true;
    }

    private function mergePayloadSegment($currentPayload, string $segment, array $payload): array
    {
        $data = is_array($currentPayload) ? $currentPayload : [];
        $data[$segment] = $payload;

        return $data;
    }

    private function resolveFortnightRange(Request $request): array
    {
        $period = trim((string) $request->query('period', now()->format('Y-m')));
        $fortnight = (int) $request->query('fortnight', now()->day <= 15 ? 1 : 2);

        $base = Carbon::parse($period . '-01');
        $start = $fortnight === 2 ? $base->copy()->day(16) : $base->copy()->startOfMonth();
        $end = $fortnight === 2 ? $base->copy()->endOfMonth() : $base->copy()->day(15);

        return [$start->startOfDay(), $end->endOfDay()];
    }

    private function renderPdf(string $html): string
    {
        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        return $dompdf->output();
    }
}
