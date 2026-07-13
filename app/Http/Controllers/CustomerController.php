<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use App\Support\ActionReason;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $authUser = auth()->user();
        $tenantId = (int) ($authUser->tenant_id ?? 0);

        abort_if($tenantId <= 0, 403);

        $search = trim((string) $request->query('search', ''));
        $status = trim((string) $request->query('status', 'all'));
        $lastPurchaseFrom = trim((string) $request->query('last_purchase_from', ''));
        $lastPurchaseTo = trim((string) $request->query('last_purchase_to', ''));
        $ranking = trim((string) $request->query('ranking', 'all'));
        $customerRoleId = $this->resolveCustomerRoleId();

        $baseQuery = $this->buildCustomerQuery($tenantId, $search, $status, $lastPurchaseFrom, $lastPurchaseTo);

        $summaryQuery = clone $baseQuery;

        $totalCustomers = (clone $summaryQuery)->count();
        $activeCustomers = (clone $summaryQuery)->where('is_active', 1)->count();
        $customersWithPurchases = (clone $summaryQuery)
            ->whereHas('salesOrders', function ($salesOrdersQuery) use ($tenantId) {
                $salesOrdersQuery->where('tenant_id', $tenantId);
            })
            ->count();
        $totalApprovedRevenue = (float) ((clone $summaryQuery)
            ->withSum([
                'payments as tenant_paid_total' => function ($paymentsQuery) use ($tenantId) {
                    $paymentsQuery
                        ->whereRaw('payments.status = ?', [1])
                        ->whereHas('salesOrder', function ($salesOrderQuery) use ($tenantId) {
                            $salesOrderQuery->where('tenant_id', $tenantId);
                        });
                },
            ], 'amount')
            ->get()
            ->sum(fn (User $customer) => (float) ($customer->tenant_paid_total ?? 0)));

        $customers = (clone $baseQuery)
            ->withCount([
                'salesOrders as orders_count' => function ($salesOrdersQuery) use ($tenantId) {
                    $salesOrdersQuery->where('tenant_id', $tenantId);
                },
            ])
            ->withMax([
                'salesOrders as last_purchase_at' => function ($salesOrdersQuery) use ($tenantId) {
                    $salesOrdersQuery->where('tenant_id', $tenantId);
                },
            ], 'date')
            ->withSum([
                'payments as total_paid_amount' => function ($paymentsQuery) use ($tenantId) {
                    $paymentsQuery
                        ->whereRaw('payments.status = ?', [1])
                        ->whereHas('salesOrder', function ($salesOrderQuery) use ($tenantId) {
                            $salesOrderQuery->where('tenant_id', $tenantId);
                        });
                },
            ], 'amount')
            ->when($ranking === 'top', function ($query) {
                $query->orderByDesc('total_paid_amount')->orderByDesc('orders_count');
            }, function ($query) {
                $query->orderByDesc('orders_count')->orderBy('name');
            })
            ->paginate(15)
            ->withQueryString();

        return view('customers.index', [
            'customers' => $customers,
            'search' => $search,
            'status' => $status,
            'lastPurchaseFrom' => $lastPurchaseFrom,
            'lastPurchaseTo' => $lastPurchaseTo,
            'ranking' => $ranking,
            'totalCustomers' => $totalCustomers,
            'activeCustomers' => $activeCustomers,
            'customersWithPurchases' => $customersWithPurchases,
            'totalApprovedRevenue' => $totalApprovedRevenue,
            'customerRoleId' => $customerRoleId,
        ]);
    }

    public function store(Request $request)
    {
        $authUser = auth()->user();
        $tenantId = (int) ($authUser->tenant_id ?? 0);

        abort_if($tenantId <= 0, 403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'phone_number' => 'required|string|max:20',
            'dni' => 'required|string|max:100',
            'is_active' => 'nullable|boolean',
            'is_retention_agent' => 'nullable|boolean',
        ]);

        $temporaryPassword = strtoupper(Str::random(8));

        User::create([
            'name' => trim((string) $validated['name']),
            'email' => trim((string) $validated['email']),
            'phone_number' => trim((string) $validated['phone_number']),
            'dni' => trim((string) $validated['dni']),
            'tenant_id' => $tenantId,
            'role_id' => $this->resolveCustomerRoleId(),
            'password' => Hash::make($temporaryPassword),
            'is_active' => (int) ($validated['is_active'] ?? 1),
            'is_retention_agent' => (bool) ($validated['is_retention_agent'] ?? false),
        ]);

        return back()->with('success', 'Cliente creado correctamente. Contraseña temporal: ' . $temporaryPassword);
    }

    public function update(Request $request, User $customer)
    {
        $authUser = auth()->user();
        $tenantId = (int) ($authUser->tenant_id ?? 0);

        abort_if((int) $customer->tenant_id !== $tenantId, 404);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,' . $customer->id,
            'phone_number' => 'required|string|max:20',
            'dni' => 'required|string|max:100',
            'is_active' => 'nullable|boolean',
            'is_retention_agent' => 'nullable|boolean',
        ]);

        $customer->update([
            'name' => trim((string) $validated['name']),
            'email' => trim((string) $validated['email']),
            'phone_number' => trim((string) $validated['phone_number']),
            'dni' => trim((string) $validated['dni']),
            'is_active' => (int) ($validated['is_active'] ?? 0),
            'is_retention_agent' => (bool) ($validated['is_retention_agent'] ?? false),
        ]);

        return back()->with('success', 'Cliente actualizado correctamente.');
    }

    public function toggleStatus(Request $request, User $customer)
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);

        abort_if((int) $customer->tenant_id !== $tenantId, 404);

        $reason = null;
        if ((bool) $customer->is_active) {
            $reason = ActionReason::require($request, 'action_reason', 'Debes indicar el motivo para inactivar el cliente.');
        }

        $customer->is_active = !$customer->is_active;
        $customer->save();

        if (!(bool) $customer->is_active) {
            ActionReason::log('customers', 'CUSTOMER_DEACTIVATED', (string) $reason, [
                'customer_id' => $customer->id,
                'tenant_id' => $customer->tenant_id,
            ]);
        }

        return back()->with('success', 'Estado del cliente actualizado correctamente.');
    }

    private function buildCustomerQuery(int $tenantId, string $search, string $status, string $lastPurchaseFrom, string $lastPurchaseTo)
    {
        $roles = Role::query()->get();

        $customerRoleIds = $roles
            ->filter(function (Role $role) {
                $normalizedName = strtolower(trim((string) $role->name));

                return in_array($normalizedName, ['user', 'cliente', 'customer'], true);
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values();

        $excludedRoleIds = $roles
            ->filter(function (Role $role) {
                $canonicalRole = User::canonicalRoleName($role->name);
                $normalizedName = strtolower(trim((string) $role->name));

                return in_array($canonicalRole, ['owner', 'admin', 'seller', 'warehouse'], true)
                    || in_array($normalizedName, ['super_user', 'super user'], true);
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values();

        return User::query()
            ->with('role')
            ->where('tenant_id', $tenantId)
            ->when($excludedRoleIds->isNotEmpty(), function ($query) use ($excludedRoleIds) {
                $query->whereNotIn('role_id', $excludedRoleIds->all());
            })
            ->where(function ($query) use ($customerRoleIds, $tenantId) {
                if ($customerRoleIds->isNotEmpty()) {
                    $query->whereIn('role_id', $customerRoleIds->all());
                }

                $query->orWhereHas('salesOrders', function ($salesOrdersQuery) use ($tenantId) {
                    $salesOrdersQuery->where('tenant_id', $tenantId);
                });
            })
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($searchQuery) use ($search) {
                    $searchQuery
                        ->where('name', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%')
                        ->orWhere('phone_number', 'like', '%' . $search . '%')
                        ->orWhere('dni', 'like', '%' . $search . '%');
                });
            })
            ->when(in_array($status, ['active', 'inactive'], true), function ($query) use ($status) {
                $query->where('is_active', $status === 'active' ? 1 : 0);
            })
            ->when($lastPurchaseFrom !== '' || $lastPurchaseTo !== '', function ($query) use ($tenantId, $lastPurchaseFrom, $lastPurchaseTo) {
                $query->whereHas('salesOrders', function ($salesOrdersQuery) use ($tenantId, $lastPurchaseFrom, $lastPurchaseTo) {
                    $salesOrdersQuery->where('tenant_id', $tenantId);

                    if ($lastPurchaseFrom !== '') {
                        $salesOrdersQuery->whereDate('date', '>=', Carbon::parse($lastPurchaseFrom)->toDateString());
                    }

                    if ($lastPurchaseTo !== '') {
                        $salesOrdersQuery->whereDate('date', '<=', Carbon::parse($lastPurchaseTo)->toDateString());
                    }
                });
            });
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