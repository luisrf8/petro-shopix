<?php

namespace App\Http\Controllers;

use App\Models\SalesAdjustmentNote;
use App\Models\SalesOrder;
use App\Models\SalesRetention;
use App\Services\FiscalCorrelativeService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class SalesFiscalController extends Controller
{
    public function __construct(private readonly FiscalCorrelativeService $fiscalCorrelativeService)
    {
    }

    public function storeAdjustmentNote(Request $request, SalesOrder $order): RedirectResponse
    {
        $this->authorizeOrderAccess($order);

        $validated = $request->validate([
            'note_type' => 'required|in:credit,debit',
            'note_date' => 'required|date',
            'amount' => 'required|numeric|gt:0',
            'reason' => 'required|string|max:255',
            'notes' => 'nullable|string|max:2000',
        ]);

        $latestElectronicDocument = $order->electronicDocuments()->latest('id')->first();
        $prefix = $validated['note_type'] === 'credit' ? 'NC' : 'ND';
        $internalNumber = $this->fiscalCorrelativeService->next(
            (int) $order->tenant_id,
            $validated['note_type'] === 'credit' ? 'credit_note' : 'debit_note',
            $prefix
        );

        SalesAdjustmentNote::create([
            'tenant_id' => $order->tenant_id,
            'sales_order_id' => $order->id,
            'electronic_document_id' => $latestElectronicDocument?->id,
            'created_by' => auth()->id(),
            'note_type' => $validated['note_type'],
            'status' => 'registered',
            'note_date' => $validated['note_date'],
            'internal_number' => $internalNumber,
            'document_code' => $validated['note_type'] === 'credit' ? '03' : '02',
            'reference_document_number' => $latestElectronicDocument?->numero_documento,
            'reference_control_number' => $latestElectronicDocument?->numero_control,
            'amount' => (float) $validated['amount'],
            'currency_code' => $this->resolveOrderCurrencyCode($order),
            'reason' => trim((string) $validated['reason']),
            'notes' => $validated['notes'] ?? null,
        ]);

        return back()->with('success', 'La nota fiscal fue registrada correctamente.');
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

        $latestElectronicDocument = $order->electronicDocuments()->latest('id')->first();
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

        SalesRetention::create([
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

        return back()->with('success', 'La retención fue registrada correctamente.');
    }

    private function authorizeOrderAccess(SalesOrder $order): void
    {
        abort_if((int) ($order->tenant_id ?? 0) !== (int) (auth()->user()->tenant_id ?? 0), 404);
    }

    private function resolveOrderCurrencyCode(SalesOrder $order): string
    {
        $code = strtoupper(trim((string) ($order->sale_currency_code ?? $order->tenant?->base_currency ?? 'USD')));
        return in_array($code, ['USD', 'EUR', 'VES'], true) ? $code : 'USD';
    }
}