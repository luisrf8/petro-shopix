<?php

namespace App\Http\Controllers;

use App\Models\ElectronicDocument;
use App\Models\SalesOrder;
use App\Models\Tenant;
use App\Services\TheFactoryHkaService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Arr;

class ElectronicInvoicingController extends Controller
{
    public function __construct(private readonly TheFactoryHkaService $service)
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
            ->with(['tenant:id,name', 'salesOrder:id,date', 'creator:id,name'])
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
            $rows = $query->get();
            return $this->exportCsv($rows);
        }

        $rows = $query->paginate(30)->withQueryString();
        $tenants = $isSuperAdmin
            ? Tenant::query()->orderBy('name')->get(['id', 'name'])
            : Tenant::query()->where('id', $authTenantId)->orderBy('name')->get(['id', 'name']);

        $canRetry = $isSuperAdmin;

        return view('electronicDocuments.index', compact('rows', 'tenants', 'tenantId', 'status', 'serie', 'code', 'errorOnly', 'fromDate', 'toDate', 'isSuperAdmin', 'canRetry'));
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

        $response = $this->service->emitDocument($payload);

        $electronicDocument->update([
            'codigo' => (string) Arr::get($response, 'data.codigo', $electronicDocument->codigo),
            'mensaje' => (string) ($response['message'] ?? $electronicDocument->mensaje),
            'numero_documento' => (string) Arr::get($response, 'data.resultado.numeroDocumento', $electronicDocument->numero_documento),
            'numero_control' => (string) Arr::get($response, 'data.resultado.numeroControl', $electronicDocument->numero_control),
            'transaccion_id' => (string) Arr::get($response, 'data.resultado.transaccionId', $electronicDocument->transaccion_id),
            'estado_documento' => (string) Arr::get($response, 'data.resultado.autorizado', Arr::get($response, 'data.resultado.imprentaDigital', $electronicDocument->estado_documento)),
            'url_consulta' => (string) Arr::get($response, 'data.resultado.urlConsulta', $electronicDocument->url_consulta),
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

        $response = $this->service->emitDocument($payload);

        $document = ElectronicDocument::create([
            'tenant_id' => $order->tenant_id,
            'sales_order_id' => $order->id,
            'created_by' => auth()->id(),
            'provider' => 'thefactoryhka',
            'tipo_documento' => (string) Arr::get($payload, 'encabezado.identificacionDocumento.tipoDocumento', '01'),
            'serie' => (string) Arr::get($payload, 'encabezado.identificacionDocumento.serie', ''),
            'numero_documento' => (string) Arr::get($response, 'data.resultado.numeroDocumento', Arr::get($payload, 'encabezado.identificacionDocumento.numeroDocumento')),
            'numero_control' => (string) Arr::get($response, 'data.resultado.numeroControl', ''),
            'transaccion_id' => (string) Arr::get($response, 'data.resultado.transaccionId', Arr::get($payload, 'encabezado.identificacionDocumento.transaccionId')),
            'estado_documento' => (string) Arr::get($response, 'data.resultado.autorizado', Arr::get($response, 'data.resultado.imprentaDigital', '')),
            'codigo' => (string) Arr::get($response, 'data.codigo', ''),
            'mensaje' => (string) ($response['message'] ?? ''),
            'url_consulta' => (string) Arr::get($response, 'data.resultado.urlConsulta', ''),
            'request_payload' => $payload,
            'response_payload' => $response['data'] ?? null,
            'issued_at' => ($response['ok'] ?? false) ? now() : null,
        ]);

        if (!($response['ok'] ?? false)) {
            return back()->with('error', 'No fue posible emitir el documento electrónico: ' . ($response['message'] ?? 'Error desconocido.'));
        }

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

        $document->update([
            'codigo' => (string) Arr::get($response, 'data.codigo', $document->codigo),
            'mensaje' => (string) ($response['message'] ?? $document->mensaje),
            'estado_documento' => (string) Arr::get($response, 'data.estado.estadoDocumento', $document->estado_documento),
            'numero_control' => (string) Arr::get($response, 'data.estado.numeroControl', $document->numero_control),
            'url_consulta' => (string) Arr::get($response, 'data.estado.urlConsulta', $document->url_consulta),
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

    public function download(Request $request, SalesOrder $order): RedirectResponse
    {
        $this->authorizeOrderAccess($order);

        $validated = $request->validate([
            'tipo_archivo' => 'nullable|in:pdf,PDF,xml,XML,json,JSON',
        ]);

        $document = $order->electronicDocuments()->latest('id')->first();
        if (!$document) {
            return back()->with('error', 'No existe documento electrónico para descargar.');
        }

        $response = $this->service->downloadDocument([
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
            'response_payload' => $response['data'] ?? $document->response_payload,
        ]);

        return back()->with('success', 'Archivo solicitado correctamente al proveedor electrónico.');
    }

    public function annul(Request $request, SalesOrder $order): RedirectResponse
    {
        $this->authorizeOrderAccess($order);

        $validated = $request->validate([
            'motivo_anulacion' => 'required|string|max:255',
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
            abort(422, 'Esta venta está configurada como nota de entrega. Cambia el tipo de documento para operar facturación digital.');
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
                'ID', 'Tienda', 'Orden', 'Tipo', 'Serie', 'Numero', 'Control', 'Estado', 'Codigo', 'Mensaje', 'Emitido', 'Anulado', 'Fecha'
            ]);

            foreach ($rows as $row) {
                fputcsv($output, [
                    (string) $row->id,
                    (string) ($row->tenant->name ?? 'N/A'),
                    (string) ($row->sales_order_id ?? ''),
                    (string) ($row->tipo_documento ?? ''),
                    (string) ($row->serie ?? ''),
                    (string) ($row->numero_documento ?? ''),
                    (string) ($row->numero_control ?? ''),
                    (string) ($row->estado_documento ?? ''),
                    (string) ($row->codigo ?? ''),
                    (string) ($row->mensaje ?? ''),
                    $row->issued_at ? $row->issued_at->format('d/m/Y H:i') : 'No',
                    $row->is_annulled ? 'Si' : 'No',
                    optional($row->created_at)->format('d/m/Y H:i') ?? '',
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
}
