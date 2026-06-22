<?php

namespace App\Http\Controllers;

use App\Models\ElectronicDocument;
use App\Models\DollarRate;
use App\Models\EuroRate;
use App\Models\SalesAdjustmentNote;
use App\Models\SalesOrder;
use App\Models\Tenant;
use App\Services\FiscalCorrelativeService;
use App\Services\TheFactoryHkaService;
use App\Support\ActionReason;
use App\Support\PdfDownload;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class ElectronicInvoicingController extends Controller
{
    public function __construct(
        private readonly TheFactoryHkaService $service,
        private readonly FiscalCorrelativeService $fiscalCorrelativeService
    )
    {
    }

    public function index(Request $request)
    {
        abort_unless($this->isSuperAdmin(), 403);

        return $this->renderIndex($request, true);
    }

    public function tenantIndex(Request $request)
    {
        abort_if(!$this->isStoreRoleAllowed(), 403);

        return $this->renderIndex($request, false);
    }

    private function renderIndex(Request $request, bool $isSuperAdmin)
    {
        $authTenantId = (int) (auth()->user()->tenant_id ?? 0);

        $tenantId = (int) $request->query('tenant_id', 0);
        if (!$isSuperAdmin) {
            $tenantId = $authTenantId;
        }

        $status = trim((string) $request->query('status', 'all'));
        $serie = trim((string) $request->query('serie', ''));
        $code = trim((string) $request->query('code', ''));
        $errorOnly = (int) $request->query('error_only', 0) === 1;
        $fromDate = trim((string) $request->query('from_date', ''));
        $toDate = trim((string) $request->query('to_date', ''));

        $query = ElectronicDocument::query()
            ->with([
                'tenant:id,name',
                'salesOrder:id,date,sale_currency_code,change_rate_to_bs,tenant_id',
                'salesOrder.payments:id,sales_order_id,exchange_rate_to_base,status,currency,created_at',
                'creator:id,name',
            ])
            ->when($tenantId > 0, fn ($q) => $q->where('tenant_id', $tenantId))
            ->when(!$isSuperAdmin, fn ($q) => $q->where('tenant_id', $authTenantId))
            ->when($serie !== '', fn ($q) => $q->where('serie', 'like', '%' . $serie . '%'))
            ->when($code !== '', fn ($q) => $q->where('codigo', 'like', '%' . $code . '%'))
            ->when($fromDate !== '', fn ($q) => $q->whereDate('created_at', '>=', $fromDate))
            ->when($toDate !== '', fn ($q) => $q->whereDate('created_at', '<=', $toDate))
            ->when($status === 'issued', fn ($q) => $q->whereNotNull('issued_at')->where('is_annulled', false))
            ->when($status === 'failed', fn ($q) => $q->whereNull('issued_at')->where('is_annulled', false))
            ->when($status === 'annulled', fn ($q) => $q->where('is_annulled', true))
            ->when($errorOnly, function ($q) {
                $q->where(function ($sq) {
                    $sq->whereNull('issued_at')
                        ->orWhere('mensaje', 'like', '%error%')
                        ->orWhere('mensaje', 'like', '%fall%')
                        ->orWhere('codigo', 'like', '4%')
                        ->orWhere('codigo', 'like', '5%');
                });
            })
            ->orderByDesc('id');

        if ($request->query('export') === 'csv') {
            $rows = $query->get()->map(fn (ElectronicDocument $row) => $this->decorateElectronicDocumentRow($row));
            return $this->exportCsv($rows);
        }

        $rows = $query->paginate(30)->withQueryString();
        $rows->setCollection(
            $rows->getCollection()->map(fn (ElectronicDocument $row) => $this->decorateElectronicDocumentRow($row))
        );

        $adjustmentRows = SalesAdjustmentNote::query()
            ->with([
                'tenant:id,name',
                'salesOrder:id,date,sale_currency_code,change_rate_to_bs,tenant_id',
                'salesOrder.payments:id,sales_order_id,exchange_rate_to_base,status,currency,created_at',
                'creator:id,name',
                'electronicDocument:id,serie,numero_documento,numero_control',
            ])
            ->when($tenantId > 0, fn ($q) => $q->where('tenant_id', $tenantId))
            ->when(!$isSuperAdmin, fn ($q) => $q->where('tenant_id', $authTenantId))
            ->when($fromDate !== '', fn ($q) => $q->whereDate('created_at', '>=', $fromDate))
            ->when($toDate !== '', fn ($q) => $q->whereDate('created_at', '<=', $toDate))
            ->orderByDesc('id')
            ->limit(30)
            ->get()
            ->map(fn (SalesAdjustmentNote $note) => $this->decorateAdjustmentNoteRow($note));

        $tenants = $isSuperAdmin
            ? Tenant::query()->orderBy('name')->get(['id', 'name'])
            : Tenant::query()->where('id', $authTenantId)->orderBy('name')->get(['id', 'name']);

        $canRetry = $isSuperAdmin;

        return view('electronicDocuments.index', compact('rows', 'adjustmentRows', 'tenants', 'tenantId', 'status', 'serie', 'code', 'errorOnly', 'fromDate', 'toDate', 'isSuperAdmin', 'canRetry'));
    }

    public function retry(ElectronicDocument $electronicDocument): RedirectResponse
    {
        abort_unless($this->isSuperAdmin(), 403);

        $order = $electronicDocument->salesOrder()->with(['tenant', 'user', 'details.variant.product', 'details.taxes', 'payments.payment'])->first();
        if (!$order) {
            return back()->with('error', 'No se encontró la orden asociada al documento electrónico.');
        }

        if (!(bool) ($order->tenant?->electronic_invoicing_enabled ?? false)) {
            return back()->with('error', 'La tienda asociada tiene desactivada la facturación digital.');
        }

        if (!$this->service->isConfigured()) {
            return back()->with('error', 'La integración The Factory HKA no está configurada en el servidor.');
        }

        $payload = $this->service->buildInvoicePayloadFromOrder($order, [
            'serie' => $electronicDocument->serie,
            'tipo_documento' => $electronicDocument->tipo_documento ?: '01',
            'numero_documento' => $electronicDocument->numero_documento,
            'transaccion_id' => $electronicDocument->transaccion_id,
        ]);

        $compliance = $this->service->validateDocumentPayloadCompliance($payload);
        if (!($compliance['ok'] ?? false)) {
            return back()->with('error', 'Reintento bloqueado por validación fiscal: ' . implode(' | ', $compliance['errors'] ?? ['Error de cumplimiento.']));
        }

        $response = $this->service->emitDocument($payload);
        $fiscalIds = $this->extractFiscalIdentifiers((array) ($response['data'] ?? []));

        $electronicDocument->update([
            'codigo' => (string) Arr::get($response, 'data.codigo', $electronicDocument->codigo),
            'mensaje' => (string) ($response['message'] ?? $electronicDocument->mensaje),
            'numero_documento' => (string) Arr::get($response, 'data.resultado.numeroDocumento', $electronicDocument->numero_documento),
            'numero_control' => (string) Arr::get($response, 'data.resultado.numeroControl', $electronicDocument->numero_control),
            'transaccion_id' => (string) Arr::get($response, 'data.resultado.transaccionId', $electronicDocument->transaccion_id),
            'estado_documento' => (string) Arr::get($response, 'data.resultado.autorizado', Arr::get($response, 'data.resultado.imprentaDigital', $electronicDocument->estado_documento)),
            'url_consulta' => (string) Arr::get($response, 'data.resultado.urlConsulta', $electronicDocument->url_consulta),
            'cufe' => $fiscalIds['cufe'] ?? $electronicDocument->cufe,
            'qr_string' => $fiscalIds['qr_string'] ?? $electronicDocument->qr_string,
            'request_payload' => $payload,
            'response_payload' => $response['data'] ?? $electronicDocument->response_payload,
            'issued_at' => ($response['ok'] ?? false) ? Carbon::now() : $electronicDocument->issued_at,
        ]);

        if (!($response['ok'] ?? false)) {
            return back()->with('error', 'Reintento falló: ' . ($response['message'] ?? 'Error desconocido.'));
        }

        return back()->with('success', 'Documento reprocesado correctamente.');
    }

    public function emit(Request $request, SalesOrder $order): RedirectResponse
    {
        $this->authorizeOrderAccess($order);

        $validated = $request->validate([
            'serie' => 'nullable|string|max:20',
            'tipo_documento' => 'nullable|string|max:5',
            'numero_documento' => 'nullable|string|max:19',
        ]);

        $payload = $this->service->buildInvoicePayloadFromOrder($order, [
            'serie' => trim((string) ($validated['serie'] ?? '')),
            'tipo_documento' => trim((string) ($validated['tipo_documento'] ?? '01')),
            'numero_documento' => trim((string) ($validated['numero_documento'] ?? '')),
        ]);

        $compliance = $this->service->validateDocumentPayloadCompliance($payload);
        if (!($compliance['ok'] ?? false)) {
            return back()->with('error', 'Emisión bloqueada por validación fiscal: ' . implode(' | ', $compliance['errors'] ?? ['Error de cumplimiento.']));
        }

        $response = $this->service->emitDocument($payload);
        $fiscalIds = $this->extractFiscalIdentifiers((array) ($response['data'] ?? []));
        $internalNumber = $this->fiscalCorrelativeService->next((int) $order->tenant_id, 'invoice', 'FAC');

        $document = ElectronicDocument::create([
            'tenant_id' => $order->tenant_id,
            'sales_order_id' => $order->id,
            'created_by' => auth()->id(),
            'provider' => 'thefactoryhka',
            'tipo_documento' => (string) Arr::get($payload, 'encabezado.identificacionDocumento.tipoDocumento', '01'),
            'serie' => (string) Arr::get($payload, 'encabezado.identificacionDocumento.serie', ''),
            'numero_documento' => (string) Arr::get($response, 'data.resultado.numeroDocumento', Arr::get($payload, 'encabezado.identificacionDocumento.numeroDocumento')),
            'internal_number' => $internalNumber,
            'numero_control' => (string) Arr::get($response, 'data.resultado.numeroControl', ''),
            'transaccion_id' => (string) Arr::get($response, 'data.resultado.transaccionId', Arr::get($payload, 'encabezado.identificacionDocumento.transaccionId')),
            'estado_documento' => (string) Arr::get($response, 'data.resultado.autorizado', Arr::get($response, 'data.resultado.imprentaDigital', '')),
            'codigo' => (string) Arr::get($response, 'data.codigo', ''),
            'mensaje' => (string) ($response['message'] ?? ''),
            'url_consulta' => (string) Arr::get($response, 'data.resultado.urlConsulta', ''),
            'cufe' => $fiscalIds['cufe'] ?? null,
            'qr_string' => $fiscalIds['qr_string'] ?? null,
            'request_payload' => $payload,
            'response_payload' => $response['data'] ?? null,
            'issued_at' => ($response['ok'] ?? false) ? now() : null,
        ]);

        if (!($response['ok'] ?? false)) {
            return back()->with('error', 'No fue posible emitir el documento electrónico: ' . ($response['message'] ?? 'Error desconocido.'));
        }

        ActionReason::log('electronic_invoices', 'INVOICE_CREATED', 'Factura electrónica emitida correctamente', [
            'sales_order_id' => $order->id,
            'tenant_id' => $order->tenant_id,
            'electronic_document_id' => $document->id,
            'numero_documento' => $document->numero_documento,
            'numero_control' => $document->numero_control,
        ]);

        Log::info('Factura electrónica creada', [
            'sales_order_id' => $order->id,
            'tenant_id' => $order->tenant_id,
            'electronic_document_id' => $document->id,
            'numero_documento' => $document->numero_documento,
            'numero_control' => $document->numero_control,
            'transaccion_id' => $document->transaccion_id,
            'created_by' => auth()->id(),
            'source' => 'manual_emit',
        ]);

        return back()->with('success', 'Documento electrónico emitido. Transacción: ' . ($document->transaccion_id ?: 'N/A'));
    }

    public function status(SalesOrder $order): RedirectResponse
    {
        $this->authorizeOrderAccess($order);

        $document = $order->electronicDocuments()->latest('id')->first();
        if (!$document) {
            return back()->with('error', 'No existe documento electrónico registrado para esta venta.');
        }

        $response = $this->service->getDocumentStatus([
            'serie' => $document->serie,
            'tipoDocumento' => $document->tipo_documento ?: '01',
            'numeroDocumento' => $document->numero_documento,
            'transaccionId' => $document->transaccion_id,
        ]);
        $fiscalIds = $this->extractFiscalIdentifiers((array) ($response['data'] ?? []));

        $document->update([
            'codigo' => (string) Arr::get($response, 'data.codigo', $document->codigo),
            'mensaje' => (string) ($response['message'] ?? $document->mensaje),
            'estado_documento' => (string) Arr::get($response, 'data.estado.estadoDocumento', $document->estado_documento),
            'numero_control' => (string) Arr::get($response, 'data.estado.numeroControl', $document->numero_control),
            'url_consulta' => (string) Arr::get($response, 'data.estado.urlConsulta', $document->url_consulta),
            'cufe' => $fiscalIds['cufe'] ?? $document->cufe,
            'qr_string' => $fiscalIds['qr_string'] ?? $document->qr_string,
            'response_payload' => $response['data'] ?? $document->response_payload,
        ]);

        if (!($response['ok'] ?? false)) {
            return back()->with('error', 'No fue posible consultar estado: ' . ($response['message'] ?? 'Error desconocido.'));
        }

        return back()->with('success', 'Estado actualizado: ' . ($document->estado_documento ?: 'N/A'));
    }

    public function sendEmail(Request $request, SalesOrder $order): RedirectResponse
    {
        $this->authorizeOrderAccess($order);

        $validated = $request->validate([
            'emails' => 'nullable|string|max:500',
        ]);

        $document = $order->electronicDocuments()->latest('id')->first();
        if (!$document) {
            return back()->with('error', 'No existe documento electrónico para enviar por correo.');
        }

        if ((bool) $document->is_annulled) {
            return back()->with('error', 'La factura electrónica fue anulada y ya no puede reenviarse.');
        }

        $emails = collect(explode(',', (string) ($validated['emails'] ?? '')))
            ->map(fn ($email) => trim($email))
            ->filter(fn ($email) => $email !== '')
            ->values();

        if ($emails->isEmpty() && !empty($order->user?->email)) {
            $emails = collect([$order->user->email]);
        }

        if ($emails->isEmpty()) {
            return back()->with('error', 'No hay correos válidos para el envío.');
        }

        $response = $this->service->sendDocumentByEmail([
            'serie' => $document->serie,
            'tipoDocumento' => $document->tipo_documento ?: '01',
            'numeroDocumento' => $document->numero_documento,
            'correos' => $emails->all(),
        ]);

        if (!($response['ok'] ?? false)) {
            return back()->with('error', 'No fue posible enviar el documento por correo: ' . ($response['message'] ?? 'Error desconocido.'));
        }

        $document->update([
            'emailed_at' => now(),
            'mensaje' => (string) ($response['message'] ?? $document->mensaje),
            'response_payload' => $response['data'] ?? $document->response_payload,
        ]);

        return back()->with('success', 'Documento enviado por correo electrónico.');
    }

    public function download(Request $request, SalesOrder $order)
    {
        $this->authorizeOrderAccess($order);

        $validated = $request->validate([
            'tipo_archivo' => 'nullable|in:pdf,PDF,xml,XML,json,JSON',
            'disposition' => 'nullable|in:inline,attachment',
        ]);

        $document = $order->electronicDocuments()->latest('id')->first();
        if (!$document) {
            return back()->with('error', 'No existe documento electrónico para descargar.');
        }

        if ((bool) $document->is_annulled) {
            return back()->with('error', 'La factura electrónica fue anulada y ya no puede visualizarse ni descargarse.');
        }

        $response = $this->service->downloadDocumentFile([
            'serie' => $document->serie,
            'tipoDocumento' => $document->tipo_documento ?: '01',
            'numeroDocumento' => $document->numero_documento,
            'tipoArchivo' => $validated['tipo_archivo'] ?? 'pdf',
        ]);

        if (!($response['ok'] ?? false)) {
            return back()->with('error', 'No fue posible descargar el archivo electrónico: ' . ($response['message'] ?? 'Error desconocido.'));
        }

        $document->update([
            'mensaje' => (string) ($response['message'] ?? $document->mensaje),
            'response_payload' => Arr::except((array) ($response['data'] ?? []), ['archivo']) ?: $document->response_payload,
        ]);

        return $this->buildDownloadedFileResponse(
            $document,
            (string) ($response['content'] ?? ''),
            (string) ($response['mime_type'] ?? 'application/pdf'),
            (string) ($response['extension'] ?? strtolower((string) ($validated['tipo_archivo'] ?? 'pdf'))),
            (string) ($validated['disposition'] ?? 'attachment')
        );
    }

    public function annul(Request $request, SalesOrder $order): RedirectResponse
    {
        $this->authorizeOrderAccess($order);

        $validated = $request->validate([
            'motivo_anulacion' => 'required|string|max:255',
        ]);

        ActionReason::log('electronic_invoices', 'INVOICE_ANNULMENT_REQUESTED', trim((string) $validated['motivo_anulacion']), [
            'sales_order_id' => $order->id,
            'tenant_id' => $order->tenant_id,
        ]);

        $document = $order->electronicDocuments()->latest('id')->first();
        if (!$document) {
            return back()->with('error', 'No existe documento electrónico para anular.');
        }

        $response = $this->service->annulDocument([
            'serie' => $document->serie,
            'tipoDocumento' => $document->tipo_documento ?: '01',
            'numeroDocumento' => $document->numero_documento,
            'motivoAnulacion' => trim((string) $validated['motivo_anulacion']),
            'fechaAnulacion' => now()->format('d/m/Y'),
            'horaAnulacion' => strtolower(now()->format('h:i:s a')),
        ]);

        if (!($response['ok'] ?? false)) {
            return back()->with('error', 'No fue posible anular el documento: ' . ($response['message'] ?? 'Error desconocido.'));
        }

        $document->update([
            'is_annulled' => true,
            'annulled_at' => now(),
            'mensaje' => (string) ($response['message'] ?? $document->mensaje),
            'response_payload' => $response['data'] ?? $document->response_payload,
        ]);

        return back()->with('success', 'Documento electrónico anulado correctamente.');
    }

    public function metadata(SalesOrder $order): RedirectResponse
    {
        $this->authorizeOrderAccess($order);

        $lastDocumentResponse = $this->service->getLastDocument([
            'serie' => config('services.thefactory_hka.default_serie', ''),
            'tipoDocumento' => config('services.thefactory_hka.default_document_type', '01'),
        ]);

        $numerationsResponse = $this->service->listNumerations([
            'serie' => config('services.thefactory_hka.default_serie', ''),
            'tipoDocumento' => config('services.thefactory_hka.default_document_type', '01'),
        ]);

        $parts = [];

        if ($lastDocumentResponse['ok'] ?? false) {
            $parts[] = 'Último documento: ' . (Arr::get($lastDocumentResponse, 'data.numeroDocumento', 'N/A'));
        } else {
            $parts[] = 'No se pudo consultar el último documento.';
        }

        if ($numerationsResponse['ok'] ?? false) {
            $parts[] = 'Numeraciones consultadas: OK';
        } else {
            $parts[] = 'No se pudieron consultar numeraciones.';
        }

        return back()->with('success', implode(' | ', $parts));
    }

    private function authorizeOrderAccess(SalesOrder $order): void
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        abort_if((int) $order->tenant_id !== $tenantId, 404);

        if (($order->document_issue_mode ?? 'delivery_note') !== 'electronic_invoice') {
            abort(422, 'Esta venta está configurada como orden de entrega. Cambia el tipo de documento para operar facturación digital.');
        }

        if (!(bool) ($order->tenant?->electronic_invoicing_enabled ?? false)) {
            abort(403, 'La facturación digital está desactivada para esta tienda.');
        }

        if (!$this->service->isConfigured()) {
            abort(422, 'La integración de facturación digital no está configurada en el servidor.');
        }
    }


    private function exportCsv($rows)
    {
        $fileName = 'monitor_facturacion_digital_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($rows) {
            $output = fopen('php://output', 'w');
            fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($output, [
                'Fecha', 'Hora', 'Tipo de Doc', 'Serie', 'Nro. Documento', 'Control', 'Doc. Afectado', 'Tasa', 'Usuario', 'Total', 'Estado', 'Anulado', 'Orden', 'Tienda'
            ]);

            foreach ($rows as $row) {
                fputcsv($output, [
                    (string) ($row->display_date ?? ''),
                    (string) ($row->display_time ?? ''),
                    (string) ($row->display_document_type ?? ''),
                    (string) ($row->serie ?? ''),
                    (string) ($row->numero_documento ?? ''),
                    (string) ($row->display_control_number ?? ''),
                    (string) ($row->affected_document ?? '-'),
                    (string) ($row->display_tax_rate ?? '-'),
                    (string) ($row->display_user ?? 'N/A'),
                    number_format((float) ($row->display_total_amount ?? 0), 2, '.', ''),
                    (string) ($row->estado_documento ?? ''),
                    $row->is_annulled ? 'Si' : 'No',
                    (string) ($row->sales_order_id ?? ''),
                    (string) ($row->tenant->name ?? 'N/A'),
                ]);
            }

            fclose($output);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function isSuperAdmin(): bool
    {
        $user = auth()->user();
        $roleName = strtolower((string) optional($user->role)->name);

        return (int) ($user->role_id ?? 0) === 4 || $roleName === 'super_user';
    }

    private function isStoreRoleAllowed(): bool
    {
        $user = auth()->user();
        $roleName = strtolower((string) optional($user->role)->name);

        return in_array($roleName, ['owner', 'admin', 'administrador', 'vendor', 'vendedor', 'seller', 'almacen', 'almacenista', 'warehouse'], true);
    }

    private function decorateElectronicDocumentRow(ElectronicDocument $row): ElectronicDocument
    {
        $issuedAt = $row->issued_at ?? $row->created_at;
        $requestPayload = is_array($row->request_payload) ? $row->request_payload : [];

        $row->setAttribute('display_date', optional($issuedAt)->format('d/m/Y') ?? '');
        $row->setAttribute('display_time', optional($issuedAt)->format('H:i:s') ?? '');
        $row->setAttribute('display_document_type', $this->mapDocumentTypeLabel((string) ($row->tipo_documento ?? '')));
        $row->setAttribute('display_control_number', $this->formatControlNumber((string) ($row->numero_control ?? '')));
        $row->setAttribute('affected_document', $this->extractAffectedDocument($requestPayload));
        $row->setAttribute('display_tax_rate', $this->extractExchangeRate($requestPayload, $row->salesOrder));
        $row->setAttribute('display_total_amount', $this->extractTotalAmount($requestPayload));
        $row->setAttribute('display_user', (string) ($row->creator->name ?? 'N/A'));

        return $row;
    }

    private function decorateAdjustmentNoteRow(SalesAdjustmentNote $note): object
    {
        $issuedAt = $note->issued_at ?? $note->related_at ?? $note->created_at;
        $referenceDocument = $note->electronicDocument;
        $hkaDocumentType = (string) (
            data_get($note->response_payload, 'resultado.tipoDocumento')
            ?: data_get($note->response_payload, 'tipoDocumento')
            ?: data_get($note->request_payload, 'encabezado.identificacionDocumento.tipoDocumento')
            ?: $note->document_code
            ?: ''
        );
        $displayDocumentType = match ($hkaDocumentType) {
            '02' => 'Nota de crédito',
            '03' => 'Nota de débito',
            default => ($note->note_type === 'credit' ? 'Nota de crédito' : 'Nota de débito'),
        };
        $exchangeRate = $this->extractExchangeRate(is_array($referenceDocument?->request_payload) ? $referenceDocument->request_payload : [], $note->salesOrder);
        $noteDocumentNumber = (string) (
            data_get($note->response_payload, 'resultado.numeroDocumento')
            ?: data_get($note->response_payload, 'numeroDocumento')
            ?: data_get($note->request_payload, 'encabezado.identificacionDocumento.numeroDocumento')
            ?: preg_replace('/\D+/', '', (string) ($note->internal_number ?? $note->id))
        );
        $noteControlNumber = (string) (
            data_get($note->response_payload, 'resultado.numeroControl')
            ?: data_get($note->response_payload, 'estado.numeroControl')
            ?: data_get($note->response_payload, 'numeroControl')
            ?: '-'
        );

        return (object) [
            'id' => (int) $note->id,
            'sales_order_id' => (int) $note->sales_order_id,
            'tenant' => $note->tenant,
            'creator' => $note->creator,
            'is_annulled' => false,
            'serie' => $referenceDocument?->serie ?? '-',
            'numero_documento' => $noteDocumentNumber !== '' ? $noteDocumentNumber : '-',
            'numero_control' => $noteControlNumber !== '' ? $noteControlNumber : '-',
            'display_date' => optional($issuedAt)->format('d/m/Y') ?? '',
            'display_time' => optional($issuedAt)->format('H:i:s') ?? '',
            'display_document_type' => $displayDocumentType,
            'display_control_number' => $noteControlNumber !== '' ? $this->formatControlNumber($noteControlNumber) : '-',
            'affected_document' => $note->reference_document_number ?: '-',
            'display_tax_rate' => $exchangeRate,
            'display_user' => (string) ($note->creator->name ?? 'N/A'),
            'display_total_amount' => (float) ($note->amount ?? 0),
            'estado_documento' => match ((string) ($note->status ?? 'registered')) {
                'issued' => 'Emitida',
                'failed' => 'Fallida',
                'registered' => 'Registrada',
                default => ucfirst(str_replace('_', ' ', (string) ($note->status ?? 'registered'))),
            },
            'mensaje' => $note->reason ?: ($note->notes ?? ''),
            'note_type' => $note->note_type,
            'download_url' => route('sales.adjustmentNotes.download', ['note' => $note->id, 'tipo_archivo' => 'pdf']),
        ];
    }

    private function buildDownloadedFileResponse(ElectronicDocument $document, string $content, string $mimeType, string $extension, string $disposition)
    {
        $fileName = implode('-', array_filter([
            'factura',
            trim((string) ($document->serie ?? '')) !== '' ? trim((string) $document->serie) : null,
            trim((string) ($document->numero_documento ?? '')),
        ])) . '.' . strtolower($extension ?: 'pdf');

        return response($content, 200, [
            'Content-Type' => $mimeType,
            'Content-Disposition' => PdfDownload::buildDispositionHeader(request(), $fileName, $disposition),
        ]);
    }

    private function mapDocumentTypeLabel(string $documentType): string
    {
        return match ($documentType) {
            '01' => 'Factura',
            '02' => 'Nota de crédito',
            '03' => 'Nota de débito',
            '04' => 'Orden de entrega',
            '05' => 'Comprobante de retención',
            '06' => 'Guía de despacho',
            '07' => 'ARC',
            default => $documentType !== '' ? $documentType : '-',
        };
    }

    private function formatControlNumber(string $controlNumber): string
    {
        $trimmed = trim($controlNumber);
        if ($trimmed === '') {
            return '-';
        }

        if (str_contains($trimmed, '-')) {
            return $trimmed;
        }

        $digits = preg_replace('/\D+/', '', $trimmed) ?: $trimmed;
        if (strlen($digits) > 2) {
            return substr($digits, 0, 2) . '-' . substr($digits, 2);
        }

        return $digits;
    }

    private function extractAffectedDocument(array $requestPayload): string
    {
        $serie = trim((string) Arr::get($requestPayload, 'encabezado.identificacionDocumento.serieFacturaAfectada', ''));
        $number = trim((string) Arr::get($requestPayload, 'encabezado.identificacionDocumento.numeroFacturaAfectada', ''));

        if ($number === '') {
            return '-';
        }

        return $serie !== '' ? $serie . '-' . $number : $number;
    }

    private function extractFiscalIdentifiers(array $responseData): array
    {
        $cufePaths = [
            'resultado.cufe',
            'resultado.CUFE',
            'resultado.codigoUnicoFacturaElectronica',
            'estado.cufe',
            'estado.CUFE',
            'cufe',
            'CUFE',
        ];

        $qrPaths = [
            'resultado.qrString',
            'resultado.cadenaQR',
            'resultado.qr',
            'estado.qrString',
            'estado.cadenaQR',
            'estado.qr',
            'qrString',
            'cadenaQR',
            'qr',
        ];

        return [
            'cufe' => $this->firstNonEmptyByPaths($responseData, $cufePaths),
            'qr_string' => $this->firstNonEmptyByPaths($responseData, $qrPaths),
        ];
    }

    private function firstNonEmptyByPaths(array $source, array $paths): ?string
    {
        foreach ($paths as $path) {
            $value = Arr::get($source, $path);
            $text = trim((string) $value);
            if ($text !== '') {
                return $text;
            }
        }

        return null;
    }

    private function extractExchangeRate(array $requestPayload, ?SalesOrder $order = null): string
    {
        $payloadRate = (float) Arr::get($requestPayload, 'encabezado.totalesOtraMoneda.tipoCambio', 0);
        if ($payloadRate > 0) {
            return number_format($payloadRate, 4, '.', '');
        }

        if (!$order) {
            return '-';
        }

        $paymentRate = (float) collect($order->payments ?? [])
            ->pluck('exchange_rate_to_base')
            ->filter(fn ($rate) => (float) $rate > 0)
            ->first();

        if ($paymentRate > 0) {
            return number_format($paymentRate, 4, '.', '');
        }

        $changeRate = (float) ($order->change_rate_to_bs ?? 0);
        if ($changeRate > 0) {
            return number_format($changeRate, 4, '.', '');
        }

        $currencyCode = strtoupper(trim((string) ($order->sale_currency_code ?? '')));
        $orderDate = trim((string) ($order->date ?? ''));
        $tenantId = (int) ($order->tenant_id ?? 0);

        if ($tenantId > 0 && $orderDate !== '' && in_array($currencyCode, ['USD', 'EUR'], true)) {
            $rate = $currencyCode === 'EUR'
                ? $this->resolveHistoricalEuroRate($tenantId, $orderDate)
                : $this->resolveHistoricalDollarRate($tenantId, $orderDate);

            if ($rate > 0) {
                return number_format($rate, 4, '.', '');
            }
        }

        return '-';
    }

    private function resolveHistoricalDollarRate(int $tenantId, string $orderDate): float
    {
        $targetDate = Carbon::parse($orderDate)->endOfDay();

        return (float) (DollarRate::query()
            ->where('tenant_id', $tenantId)
            ->where('created_at', '<=', $targetDate)
            ->latest('created_at')
            ->value('rate')
            ?? DollarRate::query()->where('tenant_id', $tenantId)->latest('created_at')->value('rate')
            ?? 0);
    }

    private function resolveHistoricalEuroRate(int $tenantId, string $orderDate): float
    {
        $targetDate = Carbon::parse($orderDate)->endOfDay();

        return (float) (EuroRate::query()
            ->where('tenant_id', $tenantId)
            ->where('created_at', '<=', $targetDate)
            ->latest('created_at')
            ->value('rate')
            ?? EuroRate::query()->where('tenant_id', $tenantId)->latest('created_at')->value('rate')
            ?? 0);
    }

    private function extractTotalAmount(array $requestPayload): float
    {
        return (float) Arr::get($requestPayload, 'encabezado.totales.totalAPagar', 0);
    }
}
