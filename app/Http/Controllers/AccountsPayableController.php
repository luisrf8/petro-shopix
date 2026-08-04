<?php

namespace App\Http\Controllers;

use App\Models\AccountPayable;
use App\Models\AccountPayablePayment;
use App\Models\IslrWithholding;
use App\Models\IslrWithholdingConcept;
use App\Models\Tenant;
use App\Models\Provider;
use App\Models\PurchaseOrder;
use Carbon\Carbon;
use App\Services\FiscalCorrelativeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccountsPayableController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = $this->tenantScopeId($request);

        if ($tenantId > 0) {
            $this->ensureDefaultIslrConcepts($tenantId);
            $this->syncOverdueStatuses($tenantId);
        }

        $search = trim((string) $request->query('search', ''));
        $status = trim((string) $request->query('status', ''));
        $providerId = (int) $request->query('provider_id', 0);
        $dateFrom = trim((string) $request->query('date_from', ''));
        $dateTo = trim((string) $request->query('date_to', ''));

        $baseQuery = AccountPayable::query()
            ->with(['provider', 'purchaseOrder', 'payments', 'purchaseVatRetentions', 'islrWithholdings'])
            ->when($tenantId > 0, fn ($query) => $query->where('tenant_id', $tenantId))
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
            ->when($tenantId > 0, fn ($query) => $query->where('tenant_id', $tenantId))
            ->whereBetween('paid_at', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
            ->sum('amount');

        $providers = Provider::query()
            ->when($tenantId > 0, fn ($query) => $query->where('tenant_id', $tenantId))
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $purchaseOrders = PurchaseOrder::query()
            ->when($tenantId > 0, fn ($query) => $query->where('tenant_id', $tenantId))
            ->where('entry_mode', 'purchase')
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->limit(150)
            ->get();

        $islrConcepts = IslrWithholdingConcept::query()
            ->when($tenantId > 0, function ($query) use ($tenantId) {
                $query->where(function ($innerQuery) use ($tenantId) {
                    $innerQuery->whereNull('tenant_id')->orWhere('tenant_id', $tenantId);
                });
            })
            ->where('is_active', true)
            ->orderBy('code')
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
            'purchaseOrders',
            'islrConcepts'
        ));
    }

    public function store(Request $request)
    {
        $tenantId = $this->tenantWriteId($request);

        $validated = $request->validate([
            'provider_id' => 'nullable|integer|exists:providers,id',
            'purchase_order_id' => 'nullable|integer|exists:purchase_orders,id',
            'document_number' => 'nullable|string|max:120',
            'invoice_number' => 'nullable|string|max:120',
            'control_number' => 'nullable|string|max:120',
            'invoice_date' => 'nullable|date',
            'issued_at' => 'required|date',
            'due_at' => 'nullable|date|after_or_equal:issued_at',
            'amount_total' => 'required|numeric|min:0.01',
            'currency_code' => 'required|string|size:3',
            'is_service' => 'nullable|boolean',
            'taxable_base' => 'nullable|numeric|min:0',
            'tax_rate' => 'nullable|numeric|min:0|max:100',
            'tax_amount' => 'nullable|numeric|min:0',
            'islr_concept_code' => 'nullable|string|max:40',
            'notes' => 'nullable|string|max:2000',
        ]);

        $providerId = (int) ($validated['provider_id'] ?? 0);
        $purchaseOrderId = (int) ($validated['purchase_order_id'] ?? 0);
        $provider = null;

        if ($providerId > 0) {
            $provider = Provider::query()
                ->where('tenant_id', $tenantId)
                ->whereKey($providerId)
                ->first();

            if (!$provider) {
                return back()->with('error', 'El proveedor no pertenece a tu sede.');
            }
        }

        if ($purchaseOrderId > 0) {
            $purchaseOrder = PurchaseOrder::query()
                ->where('tenant_id', $tenantId)
                ->whereKey($purchaseOrderId)
                ->first();

            if (!$purchaseOrder) {
                return back()->with('error', 'La orden de compra no pertenece a tu sede.');
            }

            $alreadyLinked = AccountPayable::query()
                ->where('tenant_id', $tenantId)
                ->where('purchase_order_id', $purchaseOrderId)
                ->exists();

            if ($alreadyLinked) {
                return back()->with('error', 'Esa orden de compra ya tiene una cuenta por pagar asociada.');
            }
        }

        $isService = (bool) ($validated['is_service'] ?? true);
        $forcedIslrConceptCode = trim((string) ($validated['islr_concept_code'] ?? ''));
        $tenant = Tenant::query()->find($tenantId);
        $canApplyIslr = $this->canApplyIslrWithholding($tenant, $provider);

        if (!$isService && $forcedIslrConceptCode !== '') {
            return back()->withErrors([
                'islr_concept_code' => 'El concepto ISLR solo puede configurarse cuando la cuenta por pagar corresponde a servicios.',
            ])->withInput();
        }

        if ($isService && $forcedIslrConceptCode !== '' && !$canApplyIslr) {
            return back()->withErrors([
                'islr_concept_code' => 'No puedes aplicar ISLR porque ni la empresa ni el proveedor están marcados como agente/sujeto fiscal para retención.',
            ])->withInput();
        }

        $amountTotal = round((float) $validated['amount_total'], 4);
        $taxableBase = isset($validated['taxable_base'])
            ? round((float) $validated['taxable_base'], 4)
            : $amountTotal;
        $taxRate = isset($validated['tax_rate']) ? round((float) $validated['tax_rate'], 4) : 0.0;
        $taxAmount = isset($validated['tax_amount'])
            ? round((float) $validated['tax_amount'], 4)
            : round(max(0, $amountTotal - $taxableBase), 4);
        $status = $this->resolveStatus($amountTotal, 0, $validated['due_at'] ?? null);

        AccountPayable::create([
            'tenant_id' => $tenantId,
            'provider_id' => $providerId > 0 ? $providerId : null,
            'purchase_order_id' => $purchaseOrderId > 0 ? $purchaseOrderId : null,
            'document_number' => trim((string) ($validated['document_number'] ?? '')) ?: null,
            'invoice_number' => trim((string) ($validated['invoice_number'] ?? '')) ?: null,
            'control_number' => trim((string) ($validated['control_number'] ?? '')) ?: null,
            'invoice_date' => $validated['invoice_date'] ?? null,
            'issued_at' => $validated['issued_at'],
            'due_at' => $validated['due_at'] ?? null,
            'amount_total' => $amountTotal,
            'amount_paid' => 0,
            'amount_pending' => $amountTotal,
            'currency_code' => strtoupper((string) $validated['currency_code']),
            'is_service' => $isService,
            'taxable_base' => $taxableBase,
            'tax_rate' => $taxRate,
            'tax_amount' => $taxAmount,
            'islr_concept_code' => $forcedIslrConceptCode !== '' ? $forcedIslrConceptCode : null,
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

            $createdPayment = AccountPayablePayment::create([
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

            $this->createAutomaticIslrWithholding($locked, $createdPayment, $paymentAmount);

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

    private function createAutomaticIslrWithholding(AccountPayable $accountPayable, AccountPayablePayment $payment, float $paymentAmount): void
    {
        if (!(bool) ($accountPayable->is_service ?? true)) {
            return;
        }

        $provider = $accountPayable->provider;
        if (!$provider) {
            return;
        }

        $tenantId = (int) $accountPayable->tenant_id;
        $tenant = Tenant::query()->find($tenantId);

        if (!$this->canApplyIslrWithholding($tenant, $provider)) {
            return;
        }

        $this->ensureDefaultIslrConcepts($tenantId);

        $concept = $this->resolveApplicableIslrConcept($accountPayable);
        if (!$concept) {
            return;
        }

        $exists = IslrWithholding::query()
            ->where('tenant_id', $tenantId)
            ->where('account_payable_payment_id', (int) $payment->id)
            ->exists();
        if ($exists) {
            return;
        }

        $amountTotal = (float) ($accountPayable->amount_total ?? 0);
        if ($amountTotal <= 0) {
            return;
        }

        $taxableBaseTotal = (float) ($accountPayable->taxable_base ?? $amountTotal);
        $proportion = max(0, min(1, $paymentAmount / $amountTotal));
        $baseAmount = round($taxableBaseTotal * $proportion, 4);

        $taxUnitValue = (float) ($tenant->tax_unit_value ?? 0);
        $sustraendoAmount = round(max(0, (float) ($concept->sustraendo_ut ?? 0) * $taxUnitValue), 4);
        $grossRetention = round($baseAmount * (((float) $concept->rate_percent) / 100), 4);
        $retainedAmount = round(max(0, $grossRetention - $sustraendoAmount), 4);

        if ($retainedAmount <= 0) {
            return;
        }

        $correlative = app(FiscalCorrelativeService::class)->next($tenantId, 'islr_retention', 'RET-ISLR');

        IslrWithholding::create([
            'tenant_id' => $tenantId,
            'account_payable_id' => (int) $accountPayable->id,
            'account_payable_payment_id' => (int) $payment->id,
            'provider_id' => (int) ($accountPayable->provider_id ?? 0) ?: null,
            'concept_id' => (int) $concept->id,
            'created_by' => auth()->id(),
            'retention_date' => now()->toDateString(),
            'payment_date' => $payment->paid_at?->toDateString() ?? now()->toDateString(),
            'certificate_number' => $correlative,
            'invoice_number' => (string) ($accountPayable->invoice_number ?? $accountPayable->document_number ?? ''),
            'control_number' => (string) ($accountPayable->control_number ?? ''),
            'base_amount' => $baseAmount,
            'rate_percent' => (float) $concept->rate_percent,
            'sustraendo_ut' => (float) ($concept->sustraendo_ut ?? 0),
            'sustraendo_amount' => $sustraendoAmount,
            'retained_amount' => $retainedAmount,
            'currency_code' => (string) ($accountPayable->currency_code ?? 'USD'),
            'status' => 'issued',
            'notes' => 'Retención ISLR generada automáticamente al registrar pago de servicio.',
        ]);
    }

    private function resolveApplicableIslrConcept(AccountPayable $accountPayable): ?IslrWithholdingConcept
    {
        $tenantId = (int) $accountPayable->tenant_id;
        $provider = $accountPayable->provider;
        $personType = strtolower(trim((string) ($provider->fiscal_person_type ?? 'pj')));
        $residencyType = strtolower(trim((string) ($provider->fiscal_residency_type ?? 'domiciliado')));
        $forcedCode = strtoupper(trim((string) ($accountPayable->islr_concept_code ?? '')));

        $query = IslrWithholdingConcept::query()
            ->where(function ($scope) use ($tenantId) {
                $scope->whereNull('tenant_id')->orWhere('tenant_id', $tenantId);
            })
            ->where('is_active', true);

        if ($forcedCode !== '') {
            return (clone $query)->whereRaw('UPPER(code) = ?', [$forcedCode])->first();
        }

        return (clone $query)
            ->whereIn('applicable_person_type', ['any', $personType])
            ->whereIn('applicable_residency_type', ['any', $residencyType])
            ->orderByDesc('tenant_id')
            ->orderBy('code')
            ->first();
    }

    private function canApplyIslrWithholding(?Tenant $tenant, ?Provider $provider): bool
    {
        return (bool) ($tenant?->special_taxpayer ?? false) || (bool) ($provider?->is_special_taxpayer ?? false);
    }

    private function ensureDefaultIslrConcepts(int $tenantId): void
    {
        $defaults = [
            ['code' => '053', 'name' => 'Contratistas/Subcontratistas PN domiciliado', 'rate_percent' => 3.0, 'sustraendo_ut' => 0, 'applicable_person_type' => 'pn', 'applicable_residency_type' => 'domiciliado'],
            ['code' => '054', 'name' => 'Contratistas/Subcontratistas PN no domiciliado', 'rate_percent' => 34.0, 'sustraendo_ut' => 0, 'applicable_person_type' => 'pn', 'applicable_residency_type' => 'no_domiciliado'],
            ['code' => '055', 'name' => 'Contratistas/Subcontratistas PJ domiciliado', 'rate_percent' => 2.0, 'sustraendo_ut' => 0, 'applicable_person_type' => 'pj', 'applicable_residency_type' => 'domiciliado'],
            ['code' => '056', 'name' => 'Contratistas/Subcontratistas PJ no domiciliado', 'rate_percent' => 34.0, 'sustraendo_ut' => 0, 'applicable_person_type' => 'pj', 'applicable_residency_type' => 'no_domiciliado'],
        ];

        foreach ($defaults as $default) {
            IslrWithholdingConcept::query()->updateOrCreate(
                [
                    'tenant_id' => null,
                    'code' => $default['code'],
                ],
                $default + ['is_active' => true]
            );
        }
    }
}
