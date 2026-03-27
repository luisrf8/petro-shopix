<?php

namespace App\Http\Controllers;

use App\Models\Provider;
use Illuminate\Http\Request;

class ProviderController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        abort_if($tenantId <= 0, 403);

        $search = trim((string) $request->query('search', ''));
        $status = trim((string) $request->query('status', 'active'));

        $providers = Provider::query()
            ->where('tenant_id', $tenantId)
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

        return view('providers.index', compact('providers', 'search', 'status'));
    }

    public function store(Request $request)
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        abort_if($tenantId <= 0, 403);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'contact_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone_number' => 'nullable|string|max:30',
            'notes' => 'nullable|string|max:1000',
            'is_active' => 'nullable|boolean',
        ]);

        Provider::updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'name' => trim((string) $validated['name']),
            ],
            [
                'contact_name' => $validated['contact_name'] ?? null,
                'email' => $validated['email'] ?? null,
                'phone_number' => $validated['phone_number'] ?? null,
                'notes' => $validated['notes'] ?? null,
                'is_active' => (bool) ($validated['is_active'] ?? true),
            ]
        );

        return back()->with('success', 'Proveedor guardado correctamente.');
    }

    public function update(Request $request, Provider $provider)
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        abort_if((int) $provider->tenant_id !== $tenantId, 404);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'contact_name' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'phone_number' => 'nullable|string|max:30',
            'notes' => 'nullable|string|max:1000',
            'is_active' => 'nullable|boolean',
        ]);

        $provider->update([
            'name' => trim((string) $validated['name']),
            'contact_name' => $validated['contact_name'] ?? null,
            'email' => $validated['email'] ?? null,
            'phone_number' => $validated['phone_number'] ?? null,
            'notes' => $validated['notes'] ?? null,
            'is_active' => (bool) ($validated['is_active'] ?? false),
        ]);

        return back()->with('success', 'Proveedor actualizado correctamente.');
    }

    public function toggleStatus(Provider $provider)
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        abort_if((int) $provider->tenant_id !== $tenantId, 404);

        $provider->is_active = !$provider->is_active;
        $provider->save();

        return back()->with('success', 'Estado del proveedor actualizado correctamente.');
    }
}