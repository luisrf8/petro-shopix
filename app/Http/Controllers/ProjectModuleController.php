<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\AppointmentService;
use App\Models\ProjectAssignment;
use App\Models\Project;
use App\Models\ProjectPayroll;
use App\Models\ProjectPayrollItem;
use App\Models\ProjectQuotation;
use App\Models\ProjectQuotationItem;
use App\Models\ProjectAsset;
use App\Models\ProjectTask;
use App\Models\ProjectTeamMember;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderDetail;
use App\Models\Provider;
use App\Models\Role;
use App\Models\SalesOrder;
use App\Models\SalesOrderDetail;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\ProductVariantWarehouseStock;
use App\Support\ImageStorage;
use App\Support\WorkflowNotifier;
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
        $dollarRateToBs = TenantCurrency::resolveRateToBs($tenantId, 'USD');
        $euroRateToBs = TenantCurrency::resolveRateToBs($tenantId, 'EUR');

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
        $dollarRateToBs = TenantCurrency::resolveRateToBs($tenantId, 'USD');
        $euroRateToBs = TenantCurrency::resolveRateToBs($tenantId, 'EUR');

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
            ->with('product:id,name,tenant_id,barcode,qr_code')
            ->whereHas('product', function ($query) use ($tenantId) {
                $query->where('tenant_id', $tenantId);
            })
            ->orderBy('id', 'desc')
            ->take(500)
            ->get();

        $appointmentServices = AppointmentService::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->take(200)
            ->get(['id', 'name', 'description', 'price']);

        $projects = Project::query()
            ->where('tenant_id', $tenantId)
            ->orderByDesc('id')
            ->take(100)
            ->get(['id', 'name', 'budget_amount']);

        return view('quotations.index', compact(
            'quotations',
            'editingQuotation',
            'providers',
            'customers',
            'warehouses',
            'productVariants',
            'appointmentServices',
            'projects',
            'baseCurrencyCode',
            'dollarRateToBs',
            'euroRateToBs'
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
            'currency_code' => 'required|string|max:10',
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

    public function invalidateQuotation(Request $request, ProjectQuotation $quotation)
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        abort_if((int) $quotation->tenant_id !== $tenantId, 404);

        if ($this->isQuotationLockedStatus((string) $quotation->status)) {
            return back()->with('warning', 'La cotización ya fue cerrada y no se puede invalidar nuevamente.');
        }

        $reason = trim((string) $request->input('reason', ''));
        $notePrefix = '[INVALIDADA ' . now()->format('d/m/Y H:i') . ']';
        $noteLine = $reason !== '' ? ($notePrefix . ' Motivo: ' . $reason) : ($notePrefix . ' Motivo no especificado.');

        $quotation->update([
            'status' => 'invalidated',
            'notes' => $this->appendLifecycleNote((string) ($quotation->notes ?? ''), $noteLine),
        ]);

        return back()->with('success', 'Cotización invalidada correctamente.');
    }

    public function annulQuotation(Request $request, ProjectQuotation $quotation)
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        abort_if((int) $quotation->tenant_id !== $tenantId, 404);

        if ($this->isQuotationLockedStatus((string) $quotation->status)) {
            return back()->with('warning', 'La cotización ya fue cerrada y no se puede anular nuevamente.');
        }

        $reason = trim((string) $request->input('reason', ''));
        $notePrefix = '[ANULADA ' . now()->format('d/m/Y H:i') . ']';
        $noteLine = $reason !== '' ? ($notePrefix . ' Motivo: ' . $reason) : ($notePrefix . ' Motivo no especificado.');

        $quotation->update([
            'status' => 'annulled',
            'notes' => $this->appendLifecycleNote((string) ($quotation->notes ?? ''), $noteLine),
        ]);

        return back()->with('success', 'Cotización anulada correctamente.');
    }

    public function replaceQuotation(Request $request, ProjectQuotation $quotation)
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        abort_if((int) $quotation->tenant_id !== $tenantId, 404);

        if ($this->isQuotationLockedStatus((string) $quotation->status)) {
            return back()->with('warning', 'La cotización ya fue cerrada y no se puede reemplazar nuevamente.');
        }

        $validated = $request->validate([
            'replacement_title' => 'nullable|string|max:255',
            'reason' => 'nullable|string|max:1000',
        ]);

        $quotation->loadMissing('items');

        $replacementTitle = trim((string) ($validated['replacement_title'] ?? ''));
        if ($replacementTitle === '') {
            $replacementTitle = trim((string) $quotation->title) . ' (Reemplazo)';
        }

        $newQuotation = null;

        DB::transaction(function () use ($quotation, $replacementTitle, $validated, &$newQuotation) {
            $newQuotation = ProjectQuotation::query()->create([
                'tenant_id' => (int) $quotation->tenant_id,
                'type' => (string) $quotation->type,
                'quotation_kind' => (string) $quotation->quotation_kind,
                'status' => 'draft',
                'title' => $replacementTitle,
                'customer_id' => $quotation->customer_id,
                'customer_name' => $quotation->customer_name,
                'customer_email' => $quotation->customer_email,
                'provider_id' => $quotation->provider_id,
                'provider_name' => $quotation->provider_name,
                'discount_percent' => (float) ($quotation->discount_percent ?? 0),
                'subtotal' => (float) ($quotation->subtotal ?? 0),
                'discount_amount' => (float) ($quotation->discount_amount ?? 0),
                'total_amount' => (float) ($quotation->total_amount ?? 0),
                'currency_code' => (string) ($quotation->currency_code ?? 'USD'),
                'valid_until' => $quotation->valid_until,
                'notes' => $this->appendLifecycleNote(
                    (string) ($quotation->notes ?? ''),
                    '[REEMPLAZO] Generada desde cotización #' . (int) $quotation->id
                ),
                'created_by' => auth()->id(),
            ]);

            foreach ($quotation->items as $item) {
                ProjectQuotationItem::query()->create([
                    'quotation_id' => (int) $newQuotation->id,
                    'tenant_id' => (int) $item->tenant_id,
                    'product_id' => $item->product_id,
                    'product_variant_id' => $item->product_variant_id,
                    'item_type' => (string) ($item->item_type ?? 'product'),
                    'service_name' => $item->service_name,
                    'description' => (string) $item->description,
                    'quantity' => (float) $item->quantity,
                    'unit_price' => (float) $item->unit_price,
                    'discount_percent' => (float) ($item->discount_percent ?? 0),
                    'total' => (float) $item->total,
                ]);
            }

            $reason = trim((string) ($validated['reason'] ?? ''));
            $line = '[REEMPLAZADA ' . now()->format('d/m/Y H:i') . '] Nueva cotización #' . (int) $newQuotation->id;
            if ($reason !== '') {
                $line .= ' | Motivo: ' . $reason;
            }

            $quotation->update([
                'status' => 'replaced',
                'notes' => $this->appendLifecycleNote((string) ($quotation->notes ?? ''), $line),
            ]);
        });

        return redirect()
            ->route('projects.module.quotations.index', ['edit' => (int) $newQuotation->id])
            ->with('success', 'Cotización reemplazada. Se creó la nueva cotización #' . (int) $newQuotation->id . '.');
    }

    private function persistQuotation(Request $request, int $tenantId, ?ProjectQuotation $quotation = null): ProjectQuotation
    {
        $validated = $request->validate([
            'type' => 'required|in:customer,supplier_request',
            'quotation_kind' => 'required|in:products,services,materials,project,mixed',
            'title' => 'required|string|max:255',
            'status' => 'nullable|in:draft,sent,approved,rejected,invalidated,annulled,replaced',
            'customer_id' => 'nullable|exists:users,id',
            'create_customer' => 'nullable|boolean',
            'customer_name' => 'nullable|string|max:255',
            'customer_email' => 'nullable|email|max:255',
            'customer_phone' => 'nullable|string|max:30',
            'customer_dni' => 'nullable|string|max:100',
            'is_retention_agent' => 'nullable|boolean',
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
                foreach (['customer_name', 'customer_email', 'customer_phone', 'customer_dni'] as $fieldName) {
                    if (trim((string) ($validated[$fieldName] ?? '')) === '') {
                        throw ValidationException::withMessages([
                            $fieldName => ['Debes completar todos los datos del cliente para crearlo.'],
                        ]);
                    }
                }

                $customer = User::query()->create([
                    'name' => trim((string) $validated['customer_name']),
                    'email' => trim((string) $validated['customer_email']),
                    'phone_number' => trim((string) $validated['customer_phone']),
                    'dni' => trim((string) $validated['customer_dni']),
                    'tenant_id' => $tenantId,
                    'role_id' => $this->resolveCustomerRoleId(),
                    'password' => Hash::make(Str::random(12)),
                    'is_active' => 1,
                    'is_retention_agent' => (bool) ($validated['is_retention_agent'] ?? false),
                ]);

                $customerId = (int) $customer->id;
                $customerName = (string) $customer->name;
                $customerEmail = (string) ($customer->email ?? '');
            } else {
                $customerName = trim((string) ($validated['customer_name'] ?? ''));
                $customerEmail = trim((string) ($validated['customer_email'] ?? ''));
                $customerPhone = trim((string) ($validated['customer_phone'] ?? ''));
                $customerDni = trim((string) ($validated['customer_dni'] ?? ''));

                if ($customerName === '' || $customerEmail === '' || $customerPhone === '' || $customerDni === '') {
                    throw ValidationException::withMessages([
                        'customer_name' => ['Debes completar todos los datos del cliente para la cotización.'],
                    ]);
                }
            }
        }

        if ($validated['type'] === 'supplier_request' && empty($validated['provider_id']) && empty($providerName)) {
            throw ValidationException::withMessages([
                'provider_name' => ['Debes indicar el proveedor para la solicitud de cotización.'],
            ]);
        }

        $tenant = Tenant::query()->find($tenantId);
        $normalizedCurrencyCode = $this->normalizeProjectCurrencyCode($validated['currency_code'] ?? null, $tenant);

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
            'currency_code' => $normalizedCurrencyCode,
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

    private function normalizeProjectCurrencyCode(?string $currencyCode, ?Tenant $tenant = null): string
    {
        $normalized = strtoupper(trim((string) $currencyCode));

        if (in_array($normalized, ['USD', 'EUR'], true)) {
            return $normalized;
        }

        if (in_array($normalized, ['BS', 'VES', 'VED', 'VEF', 'BSD', 'BOLIVAR', 'BOLIVARES'], true)) {
            return 'BS';
        }

        return TenantCurrency::resolveBaseCurrencyCode($tenant);
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

        if ($this->isQuotationLockedStatus((string) $quotation->status)) {
            return back()->with('warning', 'La cotización está cerrada y no puede convertirse a proyecto.');
        }

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

        if ($this->isQuotationLockedStatus((string) $quotation->status)) {
            return back()->with('warning', 'La cotización está cerrada y no puede convertirse a venta.');
        }

        $validated = $request->validate([
            'sale_reference' => 'nullable|string|max:120',
        ]);

        $quotation->loadMissing('items', 'customer');

        $saleItems = $quotation->items
            ->filter(function (ProjectQuotationItem $item) {
                return (int) ($item->product_variant_id ?? 0) > 0 && (float) ($item->quantity ?? 0) > 0;
            })
            ->values();

        if ($saleItems->isEmpty()) {
            throw ValidationException::withMessages([
                'items' => ['La cotización no tiene ítems de producto válidos para crear una venta.'],
            ]);
        }

        foreach ($saleItems as $item) {
            $rawQty = (float) ($item->quantity ?? 0);
            if (abs($rawQty - round($rawQty)) > 0.00001) {
                throw ValidationException::withMessages([
                    'items' => ['La venta solo permite cantidades enteras por producto. Ajusta la cotización antes de convertir.'],
                ]);
            }
        }

        $customer = null;
        if (!empty($quotation->customer_id)) {
            $customer = User::query()->find((int) $quotation->customer_id);
            if ($customer && (int) $customer->tenant_id !== $tenantId) {
                $customer = null;
            }
        }

        if (!$customer) {
            $customerName = trim((string) ($quotation->customer_name ?? ''));
            if ($customerName === '') {
                throw ValidationException::withMessages([
                    'customer_name' => ['Debes definir un cliente en la cotización para convertirla en venta.'],
                ]);
            }

            $customerEmail = trim((string) ($quotation->customer_email ?? ''));
            if ($customerEmail !== '') {
                $customer = User::query()
                    ->where('tenant_id', $tenantId)
                    ->whereRaw('LOWER(email) = ?', [Str::lower($customerEmail)])
                    ->first();
            }

            if (!$customer) {
                $customer = User::query()->create([
                    'name' => $customerName,
                    'email' => $customerEmail !== '' ? $customerEmail : null,
                    'phone_number' => null,
                    'tenant_id' => $tenantId,
                    'role_id' => $this->resolveCustomerRoleId(),
                    'password' => Hash::make(Str::random(16)),
                    'is_active' => 1,
                ]);
            }

            $quotation->customer_id = (int) $customer->id;
            $quotation->customer_name = (string) $customer->name;
            $quotation->customer_email = (string) ($customer->email ?? $customerEmail);
        }

        $salesOrder = null;

        DB::transaction(function () use ($tenantId, $quotation, $saleItems, $validated, $customer, &$salesOrder) {
            $salesOrder = SalesOrder::query()->create([
                'user_id' => (int) $customer->id,
                'sales_rep_user_id' => null,
                'date' => now()->toDateString(),
                'address' => 'Venta generada desde cotización #' . (int) $quotation->id,
                'status' => 0,
                'preference' => 'Retiro en tienda',
                'deliver_status' => 0,
                'tenant_id' => $tenantId,
                'document_issue_mode' => 'delivery_note',
                'sale_currency_code' => (string) ($quotation->currency_code ?: 'USD'),
                'delivery_fee' => 0,
                'delivery_fee_mode' => 'free',
                'subtotal_before_discount' => round((float) ($quotation->subtotal ?? 0), 2),
                'total_discount' => round((float) ($quotation->discount_amount ?? 0), 2),
                'total_paid_base' => 0,
                'igtf_base_amount' => 0,
                'igtf_amount' => 0,
                'change_due_base' => 0,
                'change_paid_in_bs' => false,
                'change_rate_to_bs' => null,
                'change_due_bs' => 0,
            ]);

            foreach ($saleItems as $item) {
                $variant = ProductVariant::query()->with('product:id,tenant_id')->findOrFail((int) $item->product_variant_id);
                abort_if((int) ($variant->product->tenant_id ?? 0) !== $tenantId, 404);

                $quantity = (int) round((float) $item->quantity);
                if ($quantity <= 0) {
                    throw ValidationException::withMessages([
                        'items' => ['La cantidad de los ítems a convertir debe ser mayor a cero.'],
                    ]);
                }

                if ((float) ($variant->stock ?? 0) < $quantity) {
                    throw ValidationException::withMessages([
                        'items' => ['Stock insuficiente para convertir la cotización a venta. Revisa inventario.'],
                    ]);
                }

                $lineSubtotalBeforeDiscount = round((float) $item->unit_price * $quantity, 2);
                $lineTotal = round((float) ($item->total ?? 0), 2);
                if ($lineTotal <= 0) {
                    $lineTotal = $lineSubtotalBeforeDiscount;
                }
                $lineDiscountAmount = round(max(0, $lineSubtotalBeforeDiscount - $lineTotal), 2);
                $lineUnitPrice = $quantity > 0 ? round($lineTotal / $quantity, 2) : 0;

                SalesOrderDetail::query()->create([
                    'sales_order_id' => (int) $salesOrder->id,
                    'product_variant_id' => (int) $variant->id,
                    'quantity' => $quantity,
                    'price' => $lineUnitPrice,
                    'amount' => $lineTotal,
                    'line_subtotal_before_discount' => $lineSubtotalBeforeDiscount,
                    'line_discount_amount' => $lineDiscountAmount,
                ]);

                $variant->stock = max(0, (float) ($variant->stock ?? 0) - $quantity);
                $variant->save();
            }

            $saleReferenceInput = trim((string) ($validated['sale_reference'] ?? ''));
            $saleReference = $saleReferenceInput !== ''
                ? $saleReferenceInput . ' · VENTA #' . (int) $salesOrder->id
                : 'VENTA #' . (int) $salesOrder->id;

            $quotation->update([
                'customer_id' => (int) $customer->id,
                'customer_name' => (string) ($customer->name ?? $quotation->customer_name),
                'customer_email' => (string) ($customer->email ?? $quotation->customer_email),
                'status' => 'approved',
                'conversion_target' => 'sale',
                'converted_sale_reference' => $saleReference,
                'converted_to_sale_at' => now(),
            ]);
        });

        WorkflowNotifier::notifyTenantRoles($tenantId, ['owner', 'administrador', 'admin', 'vendedor'], [
            'title' => 'Nueva venta creada',
            'message' => 'La cotización #' . (int) $quotation->id . ' se convirtió en la venta #' . (int) ($salesOrder->id ?? 0) . '.',
            'type' => 'new-order',
            'tenant_id' => $tenantId,
            'order_id' => $salesOrder ? (int) $salesOrder->id : null,
            'action' => 'review_sale',
        ]);

        if ($customer) {
            WorkflowNotifier::notifyUser($customer, [
                'title' => 'Tu venta fue creada',
                'message' => 'Se registró tu venta #' . (int) ($salesOrder->id ?? 0) . '. Puedes revisarla desde tu pedido.',
                'type' => 'new-order',
                'tenant_id' => $tenantId,
                'order_id' => $salesOrder ? (int) $salesOrder->id : null,
                'action' => 'review_my_order',
            ]);
        }

        return back()->with('success', 'Cotización convertida a venta correctamente. Orden #' . (int) ($salesOrder->id ?? 0) . ' creada.');
    }

    public function convertQuotationToInventoryEntry(Request $request, ProjectQuotation $quotation)
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        abort_if((int) $quotation->tenant_id !== $tenantId, 404);

        if ($this->isQuotationLockedStatus((string) $quotation->status)) {
            return back()->with('warning', 'La cotización está cerrada y no puede convertirse a inventario.');
        }

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

        $quotation->load(['items.product.taxes', 'items.variant', 'provider']);
        $tenant = Tenant::query()->find($tenantId);
        $billingLogoDataUri = $this->resolveTenantBillingLogoDataUri($tenant);

        $quotationCurrencyCode = TenantCurrency::normalizeCurrencyCode((string) ($quotation->currency_code ?? 'USD'));
        $quotationRateToBs = TenantCurrency::resolveRateToBs($tenantId, $quotationCurrencyCode);
        $usdRateToBs = TenantCurrency::resolveRateToBs($tenantId, 'USD');

        $html = view('projects.quotation_pdf', compact(
            'quotation',
            'tenant',
            'billingLogoDataUri',
            'quotationCurrencyCode',
            'quotationRateToBs',
            'usdRateToBs'
        ))->render();

        $options = new Options();
        $options->set('isRemoteEnabled', false);
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

    private function resolveTenantBillingLogoDataUri(?Tenant $tenant): ?string
    {
        $fallbackPath = public_path('assets/img/shopix5.png');
        $fallbackDataUri = $this->buildDataUriFromPath($fallbackPath);

        if (!$tenant || (empty($tenant->billing_logo) && empty($tenant->logo))) {
            return $fallbackDataUri;
        }

        $logoPath = trim((string) ($tenant->billing_logo ?: $tenant->logo));
        if ($logoPath === '') {
            return $fallbackDataUri;
        }

        try {
            if (ImageStorage::isGooglePath($logoPath)) {
                $googleFileId = ImageStorage::extractGoogleFileId($logoPath);
                if ($googleFileId !== '') {
                    $file = ImageStorage::downloadGoogleFileById($googleFileId);
                    $content = (string) ($file['content'] ?? '');
                    $mime = trim((string) ($file['mime_type'] ?? 'image/png'));

                    if ($content !== '') {
                        return 'data:' . ($mime !== '' ? $mime : 'image/png') . ';base64,' . base64_encode($content);
                    }
                }
            }

            if (Storage::disk('public')->exists($logoPath)) {
                $content = Storage::disk('public')->get($logoPath);
                $mime = (string) (Storage::disk('public')->mimeType($logoPath) ?: 'image/png');

                if ($content !== '') {
                    return 'data:' . $mime . ';base64,' . base64_encode($content);
                }
            }

            $publicPath = public_path(ltrim($logoPath, '/'));
            if (is_file($publicPath)) {
                return $this->buildDataUriFromPath($publicPath) ?: $fallbackDataUri;
            }

            $storagePublicPath = public_path('storage/' . ltrim($logoPath, '/'));
            if (is_file($storagePublicPath)) {
                return $this->buildDataUriFromPath($storagePublicPath) ?: $fallbackDataUri;
            }
        } catch (\Throwable $exception) {
            // Silently fallback to default logo for PDF rendering stability.
        }

        return $fallbackDataUri;
    }

    private function buildDataUriFromPath(string $path): ?string
    {
        if (!is_file($path)) {
            return null;
        }

        $content = @file_get_contents($path);
        if ($content === false || $content === '') {
            return null;
        }

        $mime = @mime_content_type($path) ?: 'image/png';

        return 'data:' . $mime . ';base64,' . base64_encode($content);
    }

    private function isQuotationLockedStatus(string $status): bool
    {
        return in_array(Str::lower(trim($status)), ['invalidated', 'annulled', 'replaced'], true);
    }

    private function appendLifecycleNote(string $existingNotes, string $newLine): string
    {
        $existing = trim($existingNotes);
        $line = trim($newLine);

        if ($existing === '') {
            return $line;
        }

        return $existing . "\n" . $line;
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
            'payment_frequency' => 'required|in:daily,weekly,fortnightly,package,monthly',
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

        $statusAction = (string) ($validated['action'] ?? '');
        $terminationReason = trim((string) ($validated['termination_reason'] ?? ''));

        if (in_array($statusAction, ['inactive', 'terminate'], true) && $terminationReason === '') {
            throw ValidationException::withMessages([
                'termination_reason' => [
                    $statusAction === 'terminate'
                        ? 'Debes indicar el motivo del despido.'
                        : 'Debes indicar el motivo de la inactivación.',
                ],
            ]);
        }

        if ($statusAction === 'inactive') {
            $teamMember->update([
                'is_active' => false,
                'terminated_at' => null,
                'termination_reason' => $terminationReason,
            ]);

            return back()->with('success', 'Integrante inactivado correctamente.');
        }

        if ($statusAction === 'terminate') {
            $teamMember->update([
                'is_active' => false,
                'terminated_at' => now(),
                'termination_reason' => $terminationReason,
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
            'payment_type' => 'required|in:daily,weekly,fortnightly,monthly,package,contract',
            'amount' => 'nullable|numeric|min:0.01',
            'currency_code' => 'nullable|string|in:USD,EUR,BS,VES',
            'paid_at' => 'required|date',
            'payment_reason' => 'nullable|string|max:2000',
            'deduction_reason' => 'nullable|string|max:2000',
            'total_to_pay' => 'nullable|numeric|min:0',
            'payroll_items_json' => 'nullable|string',
            'notes' => 'nullable|string|max:2000',
        ]);

        $rawItems = trim((string) ($validated['payroll_items_json'] ?? ''));
        $payrollItems = [];
        $paymentsTotal = 0.0;
        $deductionsTotal = 0.0;

        if ($rawItems !== '') {
            $decodedItems = json_decode($rawItems, true);
            if (!is_array($decodedItems) || empty($decodedItems)) {
                throw ValidationException::withMessages([
                    'payroll_items_json' => ['Debes agregar al menos un item de pago o descuento.'],
                ]);
            }

            foreach ($decodedItems as $index => $row) {
                $normalizedType = strtolower(trim((string) data_get($row, 'type', '')));
                $type = match ($normalizedType) {
                    'pago', 'payment' => 'payment',
                    'descuento', 'deduction' => 'deduction',
                    default => '',
                };

                $amount = (float) data_get($row, 'amount', 0);
                $description = trim((string) data_get($row, 'description', ''));

                if ($type === '' || $amount <= 0 || $description === '') {
                    throw ValidationException::withMessages([
                        'payroll_items_json' => ['Hay items inválidos. Verifica tipo, monto y descripción en cada fila.'],
                    ]);
                }

                if (mb_strlen($description) > 255) {
                    throw ValidationException::withMessages([
                        'payroll_items_json' => ['La descripción de cada item debe tener máximo 255 caracteres.'],
                    ]);
                }

                if ($type === 'payment') {
                    $paymentsTotal += $amount;
                } else {
                    $deductionsTotal += $amount;
                }

                $payrollItems[] = [
                    'item_type' => $type,
                    'amount' => $amount,
                    'description' => $description,
                    'sort_order' => $index,
                ];
            }

            if (empty($validated['team_member_id'])) {
                throw ValidationException::withMessages([
                    'team_member_id' => ['Debes seleccionar un integrante para registrar el pago de nómina por items.'],
                ]);
            }
        }

        if (!empty($validated['team_member_id'])) {
            $teamMember = ProjectTeamMember::query()->findOrFail((int) $validated['team_member_id']);
            abort_if((int) $teamMember->tenant_id !== $tenantId, 404);
        }

        if (!empty($validated['project_id'])) {
            $project = Project::query()->findOrFail((int) $validated['project_id']);
            abort_if((int) $project->tenant_id !== $tenantId, 404);
        }

        $computedTotalToPay = !empty($payrollItems)
            ? max(0, $paymentsTotal - $deductionsTotal)
            : (float) ($validated['total_to_pay'] ?? $validated['amount'] ?? 0);

        if ($computedTotalToPay <= 0) {
            throw ValidationException::withMessages([
                'total_to_pay' => ['El total a pagar debe ser mayor que 0. Revisa los montos de pagos y descuentos.'],
            ]);
        }

        $amountBase = !empty($payrollItems)
            ? $paymentsTotal
            : (float) ($validated['amount'] ?? 0);

        if ($amountBase <= 0) {
            throw ValidationException::withMessages([
                'amount' => ['Debes indicar un monto válido o agregar items de pago.'],
            ]);
        }

        $paymentReason = trim((string) ($validated['payment_reason'] ?? ''));
        $deductionReason = trim((string) ($validated['deduction_reason'] ?? ''));

        if (!empty($payrollItems)) {
            $paymentReason = collect($payrollItems)
                ->where('item_type', 'payment')
                ->pluck('description')
                ->implode(' | ');

            $deductionReason = collect($payrollItems)
                ->where('item_type', 'deduction')
                ->pluck('description')
                ->implode(' | ');
        }

        DB::transaction(function () use ($tenantId, $validated, $amountBase, $computedTotalToPay, $paymentReason, $deductionReason, $payrollItems) {
            $entry = ProjectPayroll::query()->create([
                'tenant_id' => $tenantId,
                'team_member_id' => $validated['team_member_id'] ?? null,
                'project_id' => $validated['project_id'] ?? null,
                'payment_type' => $validated['payment_type'],
                'amount' => $amountBase,
                'currency_code' => strtoupper((string) ($validated['currency_code'] ?? 'USD')),
                'paid_at' => $validated['paid_at'],
                'notes' => $validated['notes'] ?? null,
                'payment_reason' => $paymentReason !== '' ? $paymentReason : null,
                'deduction_reason' => $deductionReason !== '' ? $deductionReason : null,
                'total_to_pay' => $computedTotalToPay,
                'created_by' => auth()->id(),
            ]);

            if (empty($payrollItems)) {
                ProjectPayrollItem::query()->create([
                    'tenant_id' => $tenantId,
                    'payroll_entry_id' => $entry->id,
                    'item_type' => 'payment',
                    'amount' => $amountBase,
                    'description' => $paymentReason !== '' ? $paymentReason : 'Pago base de nómina',
                    'sort_order' => 0,
                ]);
                return;
            }

            foreach ($payrollItems as $row) {
                ProjectPayrollItem::query()->create([
                    'tenant_id' => $tenantId,
                    'payroll_entry_id' => $entry->id,
                    'item_type' => $row['item_type'],
                    'amount' => $row['amount'],
                    'description' => $row['description'],
                    'sort_order' => $row['sort_order'],
                ]);
            }
        });

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
            'fortnightly' => $base->addDays(15),
            'monthly' => $base->addMonth(),
            'package', 'contract' => null,
            default => null,
        };
    }

    public function payrollReceipt(ProjectPayroll $payroll)
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        abort_if($tenantId <= 0 || (int) $payroll->tenant_id !== $tenantId, 404);

        $payroll->loadMissing(['teamMember', 'project', 'items']);

        $html = view('payroll.receipt', [
            'payroll' => $payroll,
            'forPdf' => true,
        ])->render();

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $options->set('defaultFont', 'DejaVu Sans');

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $filename = 'comprobante-nomina-' . (int) $payroll->id . '.pdf';
        $disposition = request()->boolean('download') ? 'attachment' : 'inline';

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $disposition . '; filename="' . $filename . '"',
        ]);
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
