<?php

namespace App\Http\Controllers;

use App\Models\AccountPayable;
use App\Models\AccountPayablePayment;
use App\Models\Provider;
use App\Models\PurchaseOrder;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccountsPayableController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        abort_if($tenantId <= 0, 403);

        $this->syncOverdueStatuses($tenantId);

        $search = trim((string) $request->query('search', ''));
        $status = trim((string) $request->query('status', ''));
        $providerId = (int) $request->query('provider_id', 0);
        $dateFrom = trim((string) $request->query('date_from', ''));
        $dateTo = trim((string) $request->query('date_to', ''));

        $baseQuery = AccountPayable::query()
            ->with(['provider', 'purchaseOrder', 'payments'])
            ->where('tenant_id', $tenantId)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery
                        ->where('document_number', 'like', '%' . $search . '%')
                        ->orWhere('notes', 'like', '%' . $search . '%')
                        ->orWhereHas('provider', function ($providerQuery) use ($search) {
                            $providerQuery->where('name', 'like', '%' . $search . '%');
                        });
                });
            })
            ->when($status !== '', function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when($providerId > 0, function ($query) use ($providerId) {
                $query->where('provider_id', $providerId);
            })
            ->when($dateFrom !== '', function ($query) use ($dateFrom) {
                $query->whereDate('issued_at', '>=', $dateFrom);
            })
            ->when($dateTo !== '', function ($query) use ($dateTo) {
                $query->whereDate('issued_at', '<=', $dateTo);
            });

        $accountsPayable = (clone $baseQuery)
            ->orderByRaw("FIELD(status, 'overdue', 'pending', 'partial', 'paid')")
            ->orderBy('due_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $totalPending = (float) (clone $baseQuery)
            ->whereIn('status', ['pending', 'partial', 'overdue'])
            ->sum('amount_pending');

        $overdueTotal = (float) (clone $baseQuery)
            ->where('status', 'overdue')
            ->sum('amount_pending');

        $monthPaid = (float) AccountPayablePayment::query()
            ->where('tenant_id', $tenantId)
            ->whereBetween('paid_at', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
            ->sum('amount');

        $providers = Provider::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $purchaseOrders = PurchaseOrder::query()
            ->where('tenant_id', $tenantId)
            ->where('entry_mode', 'purchase')
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->limit(150)
            ->get();

        return view('accountsPayable.index', compact(
            'accountsPayable',
            'search',
            'status',
            'providerId',
            'dateFrom',
            'dateTo',
            'totalPending',
            'overdueTotal',
            'monthPaid',
            'providers',
            'purchaseOrders'
        ));
    }

    public function store(Request $request)
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        abort_if($tenantId <= 0, 403);

        $validated = $request->validate([
            'provider_id' => 'nullable|integer|exists:providers,id',
            'purchase_order_id' => 'nullable|integer|exists:purchase_orders,id',
            'document_number' => 'nullable|string|max:120',
            'issued_at' => 'required|date',
            'due_at' => 'nullable|date|after_or_equal:issued_at',
            'amount_total' => 'required|numeric|min:0.01',
            'currency_code' => 'required|string|size:3',
            'notes' => 'nullable|string|max:2000',
        ]);

        $providerId = (int) ($validated['provider_id'] ?? 0);
        $purchaseOrderId = (int) ($validated['purchase_order_id'] ?? 0);

        if ($providerId > 0) {
            $provider = Provider::query()
                ->where('tenant_id', $tenantId)
                ->whereKey($providerId)
                ->first();

            if (!$provider) {
                return back()->with('error', 'El proveedor no pertenece a tu tienda.');
            }
        }

        if ($purchaseOrderId > 0) {
            $purchaseOrder = PurchaseOrder::query()
                ->where('tenant_id', $tenantId)
                ->whereKey($purchaseOrderId)
                ->first();

            if (!$purchaseOrder) {
                return back()->with('error', 'La orden de compra no pertenece a tu tienda.');
            }

            $alreadyLinked = AccountPayable::query()
                ->where('tenant_id', $tenantId)
                ->where('purchase_order_id', $purchaseOrderId)
                ->exists();

            if ($alreadyLinked) {
                return back()->with('error', 'Esa orden de compra ya tiene una cuenta por pagar asociada.');
            }
        }

        $amountTotal = round((float) $validated['amount_total'], 4);
        $status = $this->resolveStatus($amountTotal, 0, $validated['due_at'] ?? null);

        AccountPayable::create([
            'tenant_id' => $tenantId,
            'provider_id' => $providerId > 0 ? $providerId : null,
            'purchase_order_id' => $purchaseOrderId > 0 ? $purchaseOrderId : null,
            'document_number' => trim((string) ($validated['document_number'] ?? '')) ?: null,
            'issued_at' => $validated['issued_at'],
            'due_at' => $validated['due_at'] ?? null,
            'amount_total' => $amountTotal,
            'amount_paid' => 0,
            'amount_pending' => $amountTotal,
            'currency_code' => strtoupper((string) $validated['currency_code']),
            'status' => $status,
            'notes' => $validated['notes'] ?? null,
            'created_by' => auth()->id(),
        ]);

        return back()->with('success', 'Cuenta por pagar registrada correctamente.');
    }

    public function registerPayment(Request $request, AccountPayable $accountPayable)
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        abort_if((int) $accountPayable->tenant_id !== $tenantId, 404);

        $validated = $request->validate([
            'paid_at' => 'required|date',
            'amount' => 'required|numeric|min:0.01',
            'payment_method' => 'nullable|string|max:100',
            'reference' => 'nullable|string|max:120',
            'notes' => 'nullable|string|max:2000',
        ]);

        DB::transaction(function () use ($validated, $tenantId, $accountPayable) {
            /** @var AccountPayable $locked */
            $locked = AccountPayable::query()
                ->where('tenant_id', $tenantId)
                ->whereKey($accountPayable->id)
                ->lockForUpdate()
                ->firstOrFail();

            $paymentAmount = round((float) $validated['amount'], 4);
            $pendingBefore = round((float) $locked->amount_pending, 4);

            if ($pendingBefore <= 0) {
                throw new \RuntimeException('Esta cuenta ya está saldada.');
            }

            if ($paymentAmount - $pendingBefore > 0.0001) {
                throw new \RuntimeException('El abono no puede ser mayor al saldo pendiente.');
            }

            AccountPayablePayment::create([
                'account_payable_id' => $locked->id,
                'tenant_id' => $tenantId,
                'paid_at' => $validated['paid_at'],
                'amount' => $paymentAmount,
                'currency_code' => (string) ($locked->currency_code ?? 'USD'),
                'payment_method' => trim((string) ($validated['payment_method'] ?? '')) ?: null,
                'reference' => trim((string) ($validated['reference'] ?? '')) ?: null,
                'notes' => $validated['notes'] ?? null,
                'created_by' => auth()->id(),
            ]);

            $newPaid = round(((float) $locked->amount_paid) + $paymentAmount, 4);
            $newPending = round(max(0, ((float) $locked->amount_total) - $newPaid), 4);

            $locked->amount_paid = $newPaid;
            $locked->amount_pending = $newPending;
            $locked->status = $this->resolveStatus((float) $locked->amount_total, $newPaid, $locked->due_at?->toDateString());
            $locked->save();
        });

        return back()->with('success', 'Abono registrado correctamente.');
    }

    private function syncOverdueStatuses(int $tenantId): void
    {
        AccountPayable::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('status', ['pending', 'partial'])
            ->where('amount_pending', '>', 0)
            ->whereNotNull('due_at')
            ->whereDate('due_at', '<', now()->toDateString())
            ->update(['status' => 'overdue']);

        AccountPayable::query()
            ->where('tenant_id', $tenantId)
            ->where('status', 'overdue')
            ->where('amount_pending', '<=', 0)
            ->update(['status' => 'paid']);
    }

    private function resolveStatus(float $amountTotal, float $amountPaid, ?string $dueAt): string
    {
        $pending = round(max(0, $amountTotal - $amountPaid), 4);

        if ($pending <= 0.0001) {
            return 'paid';
        }

        if ($amountPaid > 0.0001) {
            return 'partial';
        }

        if ($dueAt && now()->gt(Carbon::parse($dueAt)->endOfDay())) {
            return 'overdue';
        }

        return 'pending';
    }
}
