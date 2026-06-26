<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProjectAssignment;
use App\Models\Project;
use App\Models\ProjectPayroll;
use App\Models\ProjectQuotation;
use App\Models\ProjectQuotationItem;
use App\Models\ProjectAsset;
use App\Models\ProjectTask;
use App\Models\ProjectTeamMember;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderDetail;
use App\Models\Provider;
use App\Models\Role;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\ProductVariantWarehouseStock;
use App\Support\TenantCurrency;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProjectModuleController extends Controller
{
    public function index()
    {
        return redirect()->route('projects.module.projects.index');
    }

    public function payrollIndex()
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        abort_if($tenantId <= 0, 403);

        $period = (string) request()->query('period', 'all');
        $paymentTypeFilter = (string) request()->query('payment_type', 'all');

        $tenant = Tenant::query()->find($tenantId);
        $baseCurrencyCode = TenantCurrency::resolveBaseCurrencyCode($tenant);

        $teamMembers = ProjectTeamMember::query()
            ->where('tenant_id', $tenantId)
            ->with('user')
            ->latest('id')
            ->take(100)
            ->get();

        $payrollQuery = ProjectPayroll::query()
            ->where('tenant_id', $tenantId)
            ->with(['project', 'teamMember'])
            ->when($paymentTypeFilter !== 'all', function ($query) use ($paymentTypeFilter) {
                $query->where('payment_type', $paymentTypeFilter);
            })
            ->when($period === 'week', function ($query) {
                $query->whereBetween('paid_at', [now()->startOfWeek()->toDateString(), now()->endOfWeek()->toDateString()]);
            })
            ->when($period === 'month', function ($query) {
                $query->whereYear('paid_at', now()->year)->whereMonth('paid_at', now()->month);
            })
            ->when($period === 'package', function ($query) {
                $query->where('payment_type', 'package');
            });

        $payrollEntries = $payrollQuery
            ->latest('id')
            ->take(120)
            ->get();

        $payrollEntries->each(function (ProjectPayroll $entry) {
            $entry->next_payment_at = $this->estimateNextPaymentDate($entry->paid_at, (string) $entry->payment_type);
        });

        $latestTeamPayments = ProjectPayroll::query()
            ->where('tenant_id', $tenantId)
            ->whereNotNull('team_member_id')
            ->select('team_member_id', DB::raw('MAX(paid_at) as last_paid_at'))
            ->groupBy('team_member_id')
            ->pluck('last_paid_at', 'team_member_id');

        $upcomingPayments = $teamMembers
            ->filter(fn (ProjectTeamMember $member) => (bool) $member->is_active)
            ->map(function (ProjectTeamMember $member) use ($latestTeamPayments) {
                $frequency = (string) ($member->payment_frequency ?? 'monthly');
                if ($frequency === 'package') {
                    return null;
                }

                $lastPaidAt = $latestTeamPayments->get($member->id);
                $baseDate = $lastPaidAt ? Carbon::parse((string) $lastPaidAt) : now();
                $nextPaymentAt = $this->estimateNextPaymentDate($baseDate, $frequency);

                if (!$nextPaymentAt) {
                    return null;
                }

                $daysLeft = now()->startOfDay()->diffInDays($nextPaymentAt->copy()->startOfDay(), false);
                if ($daysLeft > 3) {
                    return null;
                }

                return [
                    'member_name' => $member->full_name,
                    'frequency' => $frequency,
                    'next_payment_at' => $nextPaymentAt,
                    'days_left' => $daysLeft,
                ];
            })
            ->filter()
            ->values();

        $projects = Project::query()
            ->where('tenant_id', $tenantId)
            ->latest('id')
            ->take(50)
            ->get(['id', 'name']);

        $users = User::query()
            ->where('tenant_id', $tenantId)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'phone_number']);

        return view('payroll.index', compact(
            'teamMembers',
            'payrollEntries',
            'projects',
            'users',
            'baseCurrencyCode',
            'period',
            'paymentTypeFilter',
            'upcomingPayments'
        ));
    }

    public function projectsIndex()
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        abort_if($tenantId <= 0, 403);

        $projects = Project::query()
            ->where('tenant_id', $tenantId)
            ->with([
                'quotation',
                'tasks' => fn ($query) => $query->with('responsibleMember')->latest('id')->take(20),
                'assignments.teamMember',
            ])
            ->latest('id')
            ->take(30)
            ->get();

        $quotations = ProjectQuotation::query()
            ->where('tenant_id', $tenantId)
            ->latest('id')
            ->take(50)
            ->get(['id', 'title', 'total_amount', 'currency_code']);

        $teamMembers = ProjectTeamMember::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('full_name')
            ->get(['id', 'full_name', 'role']);

        return view('projects.index', compact(
            'projects',
            'quotations',
            'teamMembers'
        ));
    }

    public function projectShow(Project $project)
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        abort_if((int) $project->tenant_id !== $tenantId, 404);

        $project->load([
            'quotation',
            'tasks.responsibleMember',
            'assignments.teamMember',
            'assets.task',
            'payrollEntries',
        ]);

        $teamMembers = ProjectTeamMember::query()
            ->where('tenant_id', $tenantId)
            ->orderBy('full_name')
            ->get(['id', 'full_name']);

        $assetsByType = $project->assets->groupBy('asset_type');

        $totalPaid = (float) $project->assets
            ->where('asset_type', 'payment')
            ->sum(function (ProjectAsset $asset) {
                return (float) ($asset->amount ?? 0);
            });

        $totalExpenses = (float) $project->assets
            ->where('asset_type', 'expense')
            ->sum(function (ProjectAsset $asset) {
                return (float) ($asset->amount ?? 0);
            });

        $totalPayrollPaid = (float) $project->payrollEntries
            ->sum(function (ProjectPayroll $payroll) {
                return (float) ($payroll->amount ?? 0);
            });

        $totalSpent = $totalExpenses + $totalPayrollPaid;
        $profitabilityAmount = $totalPaid - $totalSpent;
        $profitabilityPercent = $totalPaid > 0
            ? (($profitabilityAmount / $totalPaid) * 100)
            : 0;

        return view('projects.show', [
            'project' => $project,
            'teamMembers' => $teamMembers,
            'assetsByType' => $assetsByType,
            'totalPaid' => $totalPaid,
            'totalExpenses' => $totalExpenses,
            'totalPayrollPaid' => $totalPayrollPaid,
            'totalSpent' => $totalSpent,
            'profitabilityAmount' => $profitabilityAmount,
            'profitabilityPercent' => $profitabilityPercent,
        ]);
    }

    public function storeProjectAsset(Request $request, Project $project)
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        abort_if((int) $project->tenant_id !== $tenantId, 404);

        $validated = $request->validate([
            'asset_type' => 'required|in:reference_image,process_image,task_image,final_image,documentation,expense,payment',
            'task_id' => 'nullable|exists:pm_project_tasks,id',
            'title' => 'nullable|string|max:255',
            'notes' => 'nullable|string|max:4000',
            'amount' => 'nullable|numeric|min:0',
            'currency_code' => 'nullable|string|in:USD,EUR,BS,VES',
            'happened_at' => 'nullable|date',
            'asset_file' => 'nullable|file|mimes:jpg,jpeg,png,webp,pdf,doc,docx,xls,xlsx|max:10240',
        ]);

        if (!empty($validated['task_id'])) {
            $task = ProjectTask::query()->findOrFail((int) $validated['task_id']);
            abort_if((int) $task->tenant_id !== $tenantId || (int) $task->project_id !== (int) $project->id, 404);
        }

        $filePath = null;
        if ($request->hasFile('asset_file')) {
            $filePath = $request->file('asset_file')->store('project_assets/' . $tenantId . '/' . $project->id, 'public');
        }

        ProjectAsset::query()->create([
            'tenant_id' => $tenantId,
            'project_id' => $project->id,
            'task_id' => $validated['task_id'] ?? null,
            'asset_type' => $validated['asset_type'],
            'title' => $validated['title'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'file_path' => $filePath,
            'amount' => isset($validated['amount']) ? (float) $validated['amount'] : null,
            'currency_code' => !empty($validated['currency_code']) ? strtoupper((string) $validated['currency_code']) : null,
            'happened_at' => $validated['happened_at'] ?? null,
            'created_by' => auth()->id(),
        ]);

        return back()->with('success', 'Registro del proyecto guardado correctamente.');
    }

    public function projectAssetFile(ProjectAsset $asset)
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        abort_if((int) $asset->tenant_id !== $tenantId, 404);

        $path = (string) ($asset->file_path ?? '');
        abort_if($path === '' || !Storage::disk('public')->exists($path), 404);

        return response()->file(Storage::disk('public')->path($path));
    }

    public function quotationsIndex()
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        abort_if($tenantId <= 0, 403);
        $editingQuotationId = (int) request()->query('edit', 0);

        $tenant = Tenant::query()->find($tenantId);
        $baseCurrencyCode = TenantCurrency::resolveBaseCurrencyCode($tenant);

        $quotations = ProjectQuotation::query()
            ->where('tenant_id', $tenantId)
            ->with(['items', 'provider', 'convertedProject', 'customer', 'convertedPurchaseOrder'])
            ->latest('id')
            ->take(40)
            ->get();

        $editingQuotation = $editingQuotationId > 0
            ? ProjectQuotation::query()
                ->where('tenant_id', $tenantId)
                ->with('items')
                ->find($editingQuotationId)
            : null;

        $providers = Provider::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        $customerRoleIds = $this->resolveCustomerRoleIds();
        $customers = User::query()
            ->where('tenant_id', $tenantId)
            ->when(!empty($customerRoleIds), function ($query) use ($customerRoleIds) {
                $query->whereIn('role_id', $customerRoleIds);
            })
            ->where('is_active', 1)
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'phone_number']);

        $warehouses = Warehouse::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get(['id', 'name']);

        $productVariants = ProductVariant::query()
            ->with('product:id,name,tenant_id')
            ->whereHas('product', function ($query) use ($tenantId) {
                $query->where('tenant_id', $tenantId);
            })
            ->orderBy('id', 'desc')
            ->take(500)
            ->get();

        return view('quotations.index', compact(
            'quotations',
            'editingQuotation',
            'providers',
            'customers',
            'warehouses',
            'productVariants',
            'baseCurrencyCode'
        ));
    }

    public function storeProject(Request $request)
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        abort_if($tenantId <= 0, 403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:4000',
            'phase' => 'required|in:inicio,desarrollo,fin',
            'starts_at' => 'nullable|date',
            'ends_at' => 'nullable|date',
            'budget_amount' => 'nullable|numeric|min:0',
            'currency_code' => 'nullable|string|in:USD,EUR,BS,VES',
            'quotation_id' => 'nullable|exists:pm_quotations,id',
            'notes' => 'nullable|string|max:4000',
        ]);

        if (!empty($validated['quotation_id'])) {
            $quotation = ProjectQuotation::query()->findOrFail((int) $validated['quotation_id']);
            abort_if((int) $quotation->tenant_id !== $tenantId, 404);
        }

        Project::query()->create([
            'tenant_id' => $tenantId,
            'name' => trim((string) $validated['name']),
            'description' => $validated['description'] ?? null,
            'phase' => $validated['phase'],
            'starts_at' => $validated['starts_at'] ?? null,
            'ends_at' => $validated['ends_at'] ?? null,
            'budget_amount' => (float) ($validated['budget_amount'] ?? 0),
            'currency_code' => strtoupper((string) ($validated['currency_code'] ?? 'USD')),
            'quotation_id' => $validated['quotation_id'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'created_by' => auth()->id(),
        ]);

        return back()->with('success', 'Proyecto creado correctamente.');
    }

    public function storeProjectTask(Request $request, Project $project)
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        abort_if((int) $project->tenant_id !== $tenantId, 404);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:4000',
            'status' => 'required|in:todo,in_progress,done',
            'responsible_team_member_id' => 'nullable|exists:pm_team_members,id',
            'due_date' => 'nullable|date',
        ]);

        if (!empty($validated['responsible_team_member_id'])) {
            $responsible = ProjectTeamMember::query()->findOrFail((int) $validated['responsible_team_member_id']);
            abort_if((int) $responsible->tenant_id !== $tenantId, 404);
        }

        ProjectTask::query()->create([
            'tenant_id' => $tenantId,
            'project_id' => $project->id,
            'responsible_team_member_id' => $validated['responsible_team_member_id'] ?? null,
            'title' => trim((string) $validated['title']),
            'description' => $validated['description'] ?? null,
            'status' => $validated['status'],
            'due_date' => $validated['due_date'] ?? null,
            'completed_at' => $validated['status'] === 'done' ? now() : null,
            'created_by' => auth()->id(),
        ]);

        return back()->with('success', 'Tarea registrada correctamente.');
    }

    public function updateProjectTaskStatus(Request $request, ProjectTask $task)
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        abort_if((int) $task->tenant_id !== $tenantId, 404);

        $validated = $request->validate([
            'status' => 'required|in:todo,in_progress,done',
        ]);

        $task->update([
            'status' => $validated['status'],
            'completed_at' => $validated['status'] === 'done' ? now() : null,
        ]);

        return back()->with('success', 'Estado de tarea actualizado.');
    }

    public function storeProjectAssignment(Request $request, Project $project)
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        abort_if((int) $project->tenant_id !== $tenantId, 404);

        $validated = $request->validate([
            'team_member_id' => 'required|exists:pm_team_members,id',
            'commission_type' => 'required|in:none,percent,fixed',
            'commission_value' => 'nullable|numeric|min:0',
            'pay_amount' => 'nullable|numeric|min:0',
            'pay_currency_code' => 'nullable|string|in:USD,EUR,BS,VES',
            'project_share_percent' => 'nullable|numeric|min:0|max:100',
            'member_status' => 'nullable|in:active,inactive,terminated,paid,pending',
            'notes' => 'nullable|string|max:2000',
            'is_active' => 'nullable|boolean',
        ]);

        $teamMember = ProjectTeamMember::query()->findOrFail((int) $validated['team_member_id']);
        abort_if((int) $teamMember->tenant_id !== $tenantId, 404);

        $commissionValue = (float) ($validated['commission_value'] ?? 0);
        if ($validated['commission_type'] === 'percent' && $commissionValue > 100) {
            throw ValidationException::withMessages([
                'commission_value' => ['La comisión porcentual no puede ser mayor a 100.'],
            ]);
        }

        $projectSharePercent = (float) ($validated['project_share_percent'] ?? 0);

        ProjectAssignment::query()->updateOrCreate(
            [
                'project_id' => $project->id,
                'team_member_id' => $teamMember->id,
            ],
            [
                'tenant_id' => $tenantId,
                'commission_type' => $validated['commission_type'],
                'commission_value' => $commissionValue,
                'pay_amount' => (float) ($validated['pay_amount'] ?? 0),
                'pay_currency_code' => strtoupper((string) ($validated['pay_currency_code'] ?? 'USD')),
                'project_share_percent' => $projectSharePercent,
                'member_status' => (string) ($validated['member_status'] ?? 'active'),
                'notes' => $validated['notes'] ?? null,
                'is_active' => (bool) ($validated['is_active'] ?? true),
            ]
        );

        return back()->with('success', 'Equipo asignado al proyecto correctamente.');
    }

    public function updateProjectPhase(Request $request, Project $project)
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        abort_if((int) $project->tenant_id !== $tenantId, 404);

        $validated = $request->validate([
            'phase' => 'required|in:inicio,desarrollo,fin',
        ]);

        $project->update([
            'phase' => $validated['phase'],
        ]);

        return back()->with('success', 'Fase del proyecto actualizada correctamente.');
    }

    public function completeProject(Project $project)
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        abort_if((int) $project->tenant_id !== $tenantId, 404);

        $project->update([
            'phase' => 'fin',
            'ends_at' => $project->ends_at ?: now()->toDateString(),
        ]);

        return back()->with('success', 'Proyecto marcado como terminado.');
    }

    public function storeQuotation(Request $request)
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        abort_if($tenantId <= 0, 403);

        $this->persistQuotation($request, $tenantId);

        return back()->with('success', 'Cotización creada correctamente.');
    }

    public function updateQuotation(Request $request, ProjectQuotation $quotation)
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        abort_if((int) $quotation->tenant_id !== $tenantId, 404);

        $this->persistQuotation($request, $tenantId, $quotation);

        return redirect()->route('projects.module.quotations.index')->with('success', 'Cotización actualizada correctamente.');
    }

    private function persistQuotation(Request $request, int $tenantId, ?ProjectQuotation $quotation = null): ProjectQuotation
    {
        $validated = $request->validate([
            'type' => 'required|in:customer,supplier_request',
            'quotation_kind' => 'required|in:products,services,materials,project,mixed',
            'title' => 'required|string|max:255',
            'status' => 'nullable|in:draft,sent,approved,rejected',
            'customer_id' => 'nullable|exists:users,id',
            'create_customer' => 'nullable|boolean',
            'customer_name' => 'nullable|string|max:255',
            'customer_email' => 'nullable|email|max:255',
            'customer_phone' => 'nullable|string|max:30',
            'provider_id' => 'nullable|exists:providers,id',
            'provider_name' => 'nullable|string|max:255',
            'discount_percent' => 'nullable|numeric|min:0|max:100',
            'currency_code' => 'nullable|string|in:USD,EUR,BS,VES',
            'valid_until' => 'nullable|date',
            'notes' => 'nullable|string|max:4000',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'nullable|exists:products,id',
            'items.*.product_variant_id' => 'nullable|exists:product_variants,id',
            'items.*.item_type' => 'required|in:product,materials,service,project,free',
            'items.*.service_name' => 'nullable|string|max:255',
            'items.*.description' => 'required|string|max:255',
            'items.*.quantity' => 'required|numeric|min:0.01',
            'items.*.unit_price' => 'required|numeric|min:0',
            'items.*.discount_percent' => 'nullable|numeric|min:0|max:100',
        ]);

        $providerName = null;
        if (!empty($validated['provider_id'])) {
            $provider = Provider::query()->findOrFail((int) $validated['provider_id']);
            abort_if((int) $provider->tenant_id !== $tenantId, 404);
            $providerName = (string) $provider->name;
        } elseif (!empty($validated['provider_name'])) {
            $providerName = trim((string) $validated['provider_name']);
        }

        $customerId = null;
        $customerName = null;
        $customerEmail = null;

        if ($validated['type'] === 'customer') {
            if (!empty($validated['customer_id'])) {
                $customer = User::query()->findOrFail((int) $validated['customer_id']);
                abort_if((int) $customer->tenant_id !== $tenantId, 404);

                $customerId = (int) $customer->id;
                $customerName = (string) $customer->name;
                $customerEmail = (string) ($customer->email ?? '');
            } elseif ((bool) ($validated['create_customer'] ?? false)) {
                if (empty($validated['customer_name'])) {
                    throw ValidationException::withMessages([
                        'customer_name' => ['Debes indicar el nombre del cliente para crearlo.'],
                    ]);
                }

                $customer = User::query()->create([
                    'name' => trim((string) $validated['customer_name']),
                    'email' => !empty($validated['customer_email']) ? trim((string) $validated['customer_email']) : null,
                    'phone_number' => !empty($validated['customer_phone']) ? trim((string) $validated['customer_phone']) : null,
                    'tenant_id' => $tenantId,
                    'role_id' => $this->resolveCustomerRoleId(),
                    'password' => Hash::make(Str::random(12)),
                    'is_active' => 1,
                ]);

                $customerId = (int) $customer->id;
                $customerName = (string) $customer->name;
                $customerEmail = (string) ($customer->email ?? '');
            } else {
                $customerName = trim((string) ($validated['customer_name'] ?? ''));
                $customerEmail = trim((string) ($validated['customer_email'] ?? ''));

                if ($customerName === '') {
                    throw ValidationException::withMessages([
                        'customer_name' => ['Debes indicar el cliente para la cotización.'],
                    ]);
                }
            }
        }

        if ($validated['type'] === 'supplier_request' && empty($validated['provider_id']) && empty($providerName)) {
            throw ValidationException::withMessages([
                'provider_name' => ['Debes indicar el proveedor para la solicitud de cotización.'],
            ]);
        }

        $payload = [
            'tenant_id' => $tenantId,
            'type' => $validated['type'],
            'quotation_kind' => $validated['quotation_kind'],
            'status' => (string) ($validated['status'] ?? 'draft'),
            'title' => trim((string) $validated['title']),
            'customer_id' => $customerId,
            'customer_name' => $customerName !== '' ? $customerName : null,
            'customer_email' => $customerEmail !== '' ? $customerEmail : null,
            'provider_id' => $validated['provider_id'] ?? null,
            'provider_name' => $providerName,
            'discount_percent' => (float) ($validated['discount_percent'] ?? 0),
            'currency_code' => strtoupper((string) ($validated['currency_code'] ?? 'USD')),
            'valid_until' => $validated['valid_until'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'subtotal' => 0,
            'discount_amount' => 0,
            'total_amount' => 0,
            'created_by' => auth()->id(),
        ];

        if ($quotation) {
            $quotation->update($payload);
            ProjectQuotationItem::query()->where('quotation_id', $quotation->id)->delete();
        } else {
            $quotation = ProjectQuotation::query()->create($payload);
        }

        $totals = $this->syncQuotationItems($quotation, $validated['items'], $tenantId, (float) ($validated['discount_percent'] ?? 0));

        $quotation->update($totals);

        return $quotation;
    }

    private function syncQuotationItems(ProjectQuotation $quotation, array $items, int $tenantId, float $globalDiscountPercent): array
    {
        $subtotal = 0.0;
        $discountAmount = 0.0;
        $netAmount = 0.0;

        foreach ($items as $item) {
            $productId = !empty($item['product_id']) ? (int) $item['product_id'] : null;
            $variantId = !empty($item['product_variant_id']) ? (int) $item['product_variant_id'] : null;

            if ($productId) {
                $product = Product::query()->findOrFail($productId);
                abort_if((int) $product->tenant_id !== $tenantId, 404);
            }

            if ($variantId) {
                $variant = ProductVariant::query()->with('product:id,tenant_id')->findOrFail($variantId);
                abort_if((int) ($variant->product->tenant_id ?? 0) !== $tenantId, 404);

                if ($productId && (int) ($variant->product_id ?? 0) !== $productId) {
                    throw ValidationException::withMessages([
                        'items' => ['La variante seleccionada no pertenece al producto indicado.'],
                    ]);
                }

                $productId = (int) $variant->product_id;
            }

            $quantity = (float) ($item['quantity'] ?? 0);
            $unitPrice = (float) ($item['unit_price'] ?? 0);
            $lineDiscountPercent = (float) ($item['discount_percent'] ?? 0);

            $lineSubtotal = $quantity * $unitPrice;
            $lineDiscount = $lineSubtotal * ($lineDiscountPercent / 100);
            $lineTotal = max($lineSubtotal - $lineDiscount, 0);

            ProjectQuotationItem::query()->create([
                'quotation_id' => $quotation->id,
                'tenant_id' => $tenantId,
                'product_id' => $productId,
                'product_variant_id' => $variantId,
                'item_type' => (string) ($item['item_type'] ?? 'product'),
                'service_name' => $item['service_name'] ?? null,
                'description' => trim((string) $item['description']),
                'quantity' => $quantity,
                'unit_price' => $unitPrice,
                'discount_percent' => $lineDiscountPercent,
                'total' => $lineTotal,
            ]);

            $subtotal += $lineSubtotal;
            $discountAmount += $lineDiscount;
            $netAmount += $lineTotal;
        }

        $globalDiscountAmount = $netAmount * ($globalDiscountPercent / 100);
        $finalTotal = max($netAmount - $globalDiscountAmount, 0);

        return [
            'subtotal' => round($subtotal, 4),
            'discount_amount' => round($discountAmount + $globalDiscountAmount, 4),
            'total_amount' => round($finalTotal, 4),
        ];
    }

    public function convertQuotationToProject(Request $request, ProjectQuotation $quotation)
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        abort_if((int) $quotation->tenant_id !== $tenantId, 404);

        if ($quotation->converted_project_id) {
            return back()->with('success', 'La cotización ya estaba convertida en proyecto.');
        }

        $validated = $request->validate([
            'project_name' => 'nullable|string|max:255',
            'starts_at' => 'nullable|date',
            'notes' => 'nullable|string|max:2000',
        ]);

        $project = Project::query()->create([
            'tenant_id' => $tenantId,
            'name' => trim((string) ($validated['project_name'] ?? ('Proyecto desde cotización #' . $quotation->id))),
            'description' => 'Proyecto iniciado desde cotización #' . $quotation->id,
            'phase' => 'inicio',
            'starts_at' => $validated['starts_at'] ?? now()->toDateString(),
            'budget_amount' => (float) $quotation->total_amount,
            'currency_code' => (string) $quotation->currency_code,
            'quotation_id' => $quotation->id,
            'notes' => $validated['notes'] ?? $quotation->notes,
            'created_by' => auth()->id(),
        ]);

        $quotation->update([
            'status' => 'approved',
            'conversion_target' => 'project',
            'converted_project_id' => $project->id,
            'converted_to_project_at' => now(),
        ]);

        return back()->with('success', 'Cotización convertida a proyecto correctamente.');
    }

    public function convertQuotationToSale(Request $request, ProjectQuotation $quotation)
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        abort_if((int) $quotation->tenant_id !== $tenantId, 404);

        $validated = $request->validate([
            'sale_reference' => 'required|string|max:120',
        ]);

        $quotation->update([
            'status' => 'approved',
            'conversion_target' => 'sale',
            'converted_sale_reference' => trim((string) $validated['sale_reference']),
            'converted_to_sale_at' => now(),
        ]);

        return back()->with('success', 'Cotización marcada como convertida en venta.');
    }

    public function convertQuotationToInventoryEntry(Request $request, ProjectQuotation $quotation)
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        abort_if((int) $quotation->tenant_id !== $tenantId, 404);

        if ($quotation->type !== 'supplier_request') {
            throw ValidationException::withMessages([
                'quotation' => ['Solo las cotizaciones a proveedor pueden convertirse en entrada de inventario.'],
            ]);
        }

        $validated = $request->validate([
            'warehouse_id' => 'nullable|exists:warehouses,id',
        ]);

        $quotation->loadMissing('items');

        $warehouse = null;
        if (!empty($validated['warehouse_id'])) {
            $warehouse = Warehouse::query()
                ->where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->find((int) $validated['warehouse_id']);
        }

        if (!$warehouse) {
            $warehouse = Warehouse::query()
                ->where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->orderByDesc('is_default')
                ->orderBy('id')
                ->first();
        }

        if (!$warehouse) {
            throw ValidationException::withMessages([
                'warehouse_id' => ['No tienes almacenes activos para registrar la entrada de inventario.'],
            ]);
        }

        $inventoryItems = $quotation->items->filter(function (ProjectQuotationItem $item) {
            return (int) ($item->product_variant_id ?? 0) > 0 && (float) ($item->quantity ?? 0) > 0;
        })->values();

        if ($inventoryItems->isEmpty()) {
            throw ValidationException::withMessages([
                'items' => ['La cotización no tiene variantes de producto válidas para entrada de inventario.'],
            ]);
        }

        $providerId = $quotation->provider_id;
        if (!$providerId && !empty($quotation->provider_name)) {
            $provider = Provider::query()->updateOrCreate(
                [
                    'tenant_id' => $tenantId,
                    'name' => trim((string) $quotation->provider_name),
                ],
                [
                    'is_active' => true,
                ]
            );

            $providerId = $provider->id;
        }

        if (!$providerId) {
            throw ValidationException::withMessages([
                'provider_id' => ['Debes indicar un proveedor válido para convertir esta cotización en entrada de inventario.'],
            ]);
        }

        DB::transaction(function () use ($tenantId, $quotation, $warehouse, $inventoryItems, $providerId) {
            $purchaseOrder = PurchaseOrder::query()->create([
                'provider_id' => $providerId,
                'provider_name' => $quotation->provider_name,
                'provider_rif' => null,
                'warehouse_id' => $warehouse->id,
                'date' => now()->toDateString(),
                'tenant_id' => $tenantId,
                'entry_mode' => 'purchase',
                'supplier_invoice_number' => 'Q-' . $quotation->id,
                'supplier_invoice_control_number' => 'Q-' . $quotation->id,
                'supplier_invoice_date' => now()->toDateString(),
                'supplier_invoice_file_path' => null,
            ]);

            foreach ($inventoryItems as $item) {
                $variant = ProductVariant::query()->with('product:id,tenant_id')->findOrFail((int) $item->product_variant_id);
                abort_if((int) ($variant->product->tenant_id ?? 0) !== $tenantId, 404);

                $quantity = (int) max(round((float) $item->quantity), 1);
                $unitPrice = (float) $item->unit_price;

                PurchaseOrderDetail::query()->create([
                    'purchase_order_id' => $purchaseOrder->id,
                    'product_variant_id' => $variant->id,
                    'quantity' => $quantity,
                    'price' => $unitPrice,
                    'amount' => round($quantity * $unitPrice, 4),
                    'input_currency_code' => $quotation->currency_code,
                    'input_exchange_rate' => null,
                    'tenant_id' => $tenantId,
                ]);

                $variant->stock = (float) ($variant->stock ?? 0) + $quantity;
                $variant->save();

                $warehouseStock = ProductVariantWarehouseStock::query()->firstOrNew([
                    'tenant_id' => $tenantId,
                    'warehouse_id' => $warehouse->id,
                    'product_variant_id' => $variant->id,
                ]);

                $warehouseStock->quantity = (float) ($warehouseStock->quantity ?? 0) + $quantity;
                $warehouseStock->save();
            }

            $quotation->update([
                'status' => 'approved',
                'provider_id' => $providerId,
                'conversion_target' => 'inventory_entry',
                'converted_purchase_order_id' => $purchaseOrder->id,
                'converted_to_inventory_at' => now(),
            ]);
        });

        return back()->with('success', 'Cotización convertida en entrada de inventario correctamente.');
    }

    public function quotationPdf(ProjectQuotation $quotation)
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        abort_if((int) $quotation->tenant_id !== $tenantId, 404);

        $quotation->load(['items.product', 'items.variant', 'provider']);

        $html = view('projects.quotation_pdf', compact('quotation'))->render();

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = 'cotizacion-' . (int) $quotation->id . '.pdf';

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="' . $filename . '"',
        ]);
    }

    public function storeTeamMember(Request $request)
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        abort_if($tenantId <= 0, 403);

        $validated = $request->validate([
            'user_id' => 'nullable|exists:users,id',
            'full_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone' => ['nullable', 'string', 'max:60', 'regex:/^\+[1-9]\d{6,14}$/'],
            'role' => 'nullable|string|max:120',
            'payment_frequency' => 'required|in:daily,weekly,package,monthly',
            'is_active' => 'nullable|boolean',
            'notes' => 'nullable|string|max:2000',
        ]);

        $selectedUser = null;
        if (!empty($validated['user_id'])) {
            $selectedUser = User::query()->findOrFail((int) $validated['user_id']);
            abort_if((int) $selectedUser->tenant_id !== $tenantId, 404);
        }

        $fullName = trim((string) ($validated['full_name'] ?? ($selectedUser->name ?? '')));
        if ($fullName === '') {
            throw ValidationException::withMessages([
                'full_name' => ['Debes indicar el nombre del integrante.'],
            ]);
        }

        ProjectTeamMember::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => $selectedUser?->id,
            'full_name' => $fullName,
            'email' => $validated['email'] ?? $selectedUser?->email,
            'phone' => $validated['phone'] ?? $selectedUser?->phone_number,
            'role' => $validated['role'] ?? null,
            'payment_frequency' => $validated['payment_frequency'],
            'is_active' => (bool) ($validated['is_active'] ?? true),
            'terminated_at' => null,
            'termination_reason' => null,
            'notes' => $validated['notes'] ?? null,
        ]);

        return back()->with('success', 'Integrante agregado al equipo correctamente.');
    }

    public function updateTeamMemberStatus(Request $request, ProjectTeamMember $teamMember)
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        abort_if((int) $teamMember->tenant_id !== $tenantId, 404);

        $validated = $request->validate([
            'action' => 'required|in:inactive,terminate,reactivate',
            'termination_reason' => 'nullable|string|max:255',
        ]);

        if ($validated['action'] === 'inactive') {
            $teamMember->update([
                'is_active' => false,
                'terminated_at' => null,
                'termination_reason' => null,
            ]);

            return back()->with('success', 'Integrante inactivado correctamente.');
        }

        if ($validated['action'] === 'terminate') {
            $teamMember->update([
                'is_active' => false,
                'terminated_at' => now(),
                'termination_reason' => trim((string) ($validated['termination_reason'] ?? 'Despido registrado.')),
            ]);

            return back()->with('success', 'Integrante marcado como despedido.');
        }

        $teamMember->update([
            'is_active' => true,
            'terminated_at' => null,
            'termination_reason' => null,
        ]);

        return back()->with('success', 'Integrante reactivado correctamente.');
    }

    public function storePayroll(Request $request)
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        abort_if($tenantId <= 0, 403);

        $validated = $request->validate([
            'team_member_id' => 'nullable|exists:pm_team_members,id',
            'project_id' => 'nullable|exists:pm_projects,id',
            'payment_type' => 'required|in:daily,weekly,monthly,package,contract',
            'amount' => 'required|numeric|min:0.01',
            'currency_code' => 'nullable|string|in:USD,EUR,BS,VES',
            'paid_at' => 'required|date',
            'notes' => 'nullable|string|max:2000',
        ]);

        if (!empty($validated['team_member_id'])) {
            $teamMember = ProjectTeamMember::query()->findOrFail((int) $validated['team_member_id']);
            abort_if((int) $teamMember->tenant_id !== $tenantId, 404);
        }

        if (!empty($validated['project_id'])) {
            $project = Project::query()->findOrFail((int) $validated['project_id']);
            abort_if((int) $project->tenant_id !== $tenantId, 404);
        }

        ProjectPayroll::query()->create([
            'tenant_id' => $tenantId,
            'team_member_id' => $validated['team_member_id'] ?? null,
            'project_id' => $validated['project_id'] ?? null,
            'payment_type' => $validated['payment_type'],
            'amount' => (float) $validated['amount'],
            'currency_code' => strtoupper((string) ($validated['currency_code'] ?? 'USD')),
            'paid_at' => $validated['paid_at'],
            'notes' => $validated['notes'] ?? null,
            'created_by' => auth()->id(),
        ]);

        return back()->with('success', 'Pago de nómina registrado correctamente.');
    }

    private function estimateNextPaymentDate($paidAt, string $paymentType): ?Carbon
    {
        if (!$paidAt) {
            return null;
        }

        $base = $paidAt instanceof Carbon ? $paidAt->copy() : Carbon::parse((string) $paidAt);
        $normalized = strtolower(trim($paymentType));

        return match ($normalized) {
            'daily' => $base->addDay(),
            'weekly' => $base->addWeek(),
            'monthly' => $base->addMonth(),
            'package', 'contract' => null,
            default => null,
        };
    }

    private function resolveCustomerRoleIds(): array
    {
        return Role::query()
            ->whereRaw('LOWER(name) IN (?, ?, ?)', ['user', 'cliente', 'customer'])
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    private function resolveCustomerRoleId(): int
    {
        $roleId = Role::query()
            ->whereRaw('LOWER(name) IN (?, ?, ?)', ['user', 'cliente', 'customer'])
            ->value('id');

        if ($roleId) {
            return (int) $roleId;
        }

        return (int) Role::query()->firstOrCreate(['name' => 'user'])->id;
    }
}
