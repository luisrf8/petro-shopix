<?php

namespace App\Http\Controllers;

use App\Models\Provider;
use Illuminate\Http\Request;
use App\Models\Tenant;
use App\Support\TenantCurrency;
use App\Support\ActionReason;

class ProviderController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = $this->tenantScopeId($request);
        $tenant = $tenantId > 0 ? Tenant::query()->find($tenantId) : null;
        $baseCurrencyCode = TenantCurrency::resolveBaseCurrencyCode($tenant);

        $search = trim((string) $request->query('search', ''));
        $status = trim((string) $request->query('status', 'active'));

        $providers = Provider::query()
            ->when($tenantId > 0, fn ($query) => $query->where('tenant_id', $tenantId))
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery
                        ->where('name', 'like', '%' . $search . '%')
                        ->orWhere('contact_name', 'like', '%' . $search . '%')
                        ->orWhere('email', 'like', '%' . $search . '%')
                        ->orWhere('phone_number', 'like', '%' . $search . '%');
                });
            })
            ->when(in_array($status, ['active', 'inactive'], true), function ($query) use ($status) {
                $query->where('is_active', $status === 'active');
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('providers.index', compact('providers', 'search', 'status', 'baseCurrencyCode'));
    }

    public function store(Request $request)
    {
        $tenantId = $this->tenantWriteId($request);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'rif' => 'nullable|string|max:20',
            'fiscal_person_type' => 'nullable|in:pn,pj',
            'fiscal_residency_type' => 'nullable|in:domiciliado,no_domiciliado',
            'is_special_taxpayer' => 'nullable|boolean',
            'contact_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone_number' => 'nullable|string|max:30',
            'payment_currency_code' => 'nullable|string|in:USD,EUR,BS,VES',
            'notes' => 'nullable|string|max:1000',
            'is_active' => 'nullable|boolean',
        ]);

        Provider::updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'name' => trim((string) $validated['name']),
            ],
            [
                'rif' => strtoupper(trim((string) ($validated['rif'] ?? ''))) ?: null,
                'fiscal_person_type' => (string) ($validated['fiscal_person_type'] ?? 'pj'),
                'fiscal_residency_type' => (string) ($validated['fiscal_residency_type'] ?? 'domiciliado'),
                'is_special_taxpayer' => (bool) ($validated['is_special_taxpayer'] ?? false),
                'contact_name' => $validated['contact_name'] ?? null,
                'email' => $validated['email'] ?? null,
                'phone_number' => $validated['phone_number'] ?? null,
                'payment_currency_code' => TenantCurrency::normalizeCurrencyCode((string) ($validated['payment_currency_code'] ?? 'USD')),
                'notes' => $validated['notes'] ?? null,
                'is_active' => (bool) ($validated['is_active'] ?? true),
            ]
        );

        return back()->with('success', 'Proveedor guardado correctamente.');
    }

    public function update(Request $request, Provider $provider)
    {
        $tenantId = $this->tenantWriteId($request);
        abort_if((int) $provider->tenant_id !== $tenantId, 404);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'rif' => 'nullable|string|max:20',
            'fiscal_person_type' => 'nullable|in:pn,pj',
            'fiscal_residency_type' => 'nullable|in:domiciliado,no_domiciliado',
            'is_special_taxpayer' => 'nullable|boolean',
            'contact_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone_number' => 'nullable|string|max:30',
            'payment_currency_code' => 'nullable|string|in:USD,EUR,BS,VES',
            'notes' => 'nullable|string|max:1000',
            'is_active' => 'nullable|boolean',
        ]);

        $provider->update([
            'name' => trim((string) $validated['name']),
            'rif' => strtoupper(trim((string) ($validated['rif'] ?? ''))) ?: null,
            'fiscal_person_type' => (string) ($validated['fiscal_person_type'] ?? $provider->fiscal_person_type ?? 'pj'),
            'fiscal_residency_type' => (string) ($validated['fiscal_residency_type'] ?? $provider->fiscal_residency_type ?? 'domiciliado'),
            'is_special_taxpayer' => (bool) ($validated['is_special_taxpayer'] ?? false),
            'contact_name' => $validated['contact_name'] ?? null,
            'email' => $validated['email'] ?? null,
            'phone_number' => $validated['phone_number'] ?? null,
            'payment_currency_code' => TenantCurrency::normalizeCurrencyCode((string) ($validated['payment_currency_code'] ?? ($provider->payment_currency_code ?? 'USD'))),
            'notes' => $validated['notes'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]);

        return back()->with('success', 'Proveedor actualizado correctamente.');
    }

    public function toggleStatus(Request $request, Provider $provider)
    {
        $tenantId = $this->tenantWriteId($request);
        abort_if((int) $provider->tenant_id !== $tenantId, 404);

        $reason = null;
        if ((bool) $provider->is_active) {
            $reason = ActionReason::require($request, 'action_reason', 'Debes indicar el motivo para inactivar el proveedor.');
        }

        $provider->is_active = !$provider->is_active;
        $provider->save();

        if (!(bool) $provider->is_active) {
            ActionReason::log('providers', 'PROVIDER_DEACTIVATED', (string) $reason, [
                'provider_id' => $provider->id,
                'tenant_id' => $provider->tenant_id,
            ]);
        }

        return back()->with('success', 'Estado del proveedor actualizado correctamente.');
    }
}