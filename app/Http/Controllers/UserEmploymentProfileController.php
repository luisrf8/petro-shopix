<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use App\Models\User;
use App\Models\UserEmploymentProfile;
use App\Support\UserRedirector;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class UserEmploymentProfileController extends Controller
{
    public function index()
    {
        $authUser = auth()->user();
        $isSuperOwner = UserRedirector::isSuperAdmin($authUser);
        $scopeTenantId = $this->tenantScopeId();

        $users = User::query()
            ->with(['role:id,name', 'tenant:id,name', 'employmentProfile'])
            ->when(!$isSuperOwner, fn ($query) => $query->where('tenant_id', (int) ($authUser->tenant_id ?? 0)))
            ->when($isSuperOwner && $scopeTenantId > 0, fn ($query) => $query->where('tenant_id', $scopeTenantId))
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        $tenantFilterOptions = $isSuperOwner
            ? Tenant::query()->orderBy('name')->get(['id', 'name'])
            : collect();

        return view('users.employment-profiles', [
            'users' => $users,
            'isSuperOwner' => $isSuperOwner,
            'selectedTenantId' => $scopeTenantId,
            'tenantFilterOptions' => $tenantFilterOptions,
        ]);
    }

    public function update(Request $request, User $user)
    {
        $authUser = auth()->user();
        $isSuperOwner = UserRedirector::isSuperAdmin($authUser);
        $scopeTenantId = $this->tenantScopeId();

        if (!$isSuperOwner && (int) ($user->tenant_id ?? 0) !== (int) ($authUser->tenant_id ?? 0)) {
            abort(404);
        }

        if ($isSuperOwner && $scopeTenantId > 0 && (int) ($user->tenant_id ?? 0) !== $scopeTenantId) {
            abort(404);
        }

        $validated = $request->validate([
            'employment_type' => 'nullable|string|max:50',
            'contract_file' => 'nullable|file|mimes:pdf,doc,docx,png,jpg,jpeg,webp|max:10240',
            'family_dependents' => 'nullable|integer|min:0|max:50',
            'hired_at' => 'nullable|date',
            'birth_date' => 'nullable|date',
            'age' => 'nullable|integer|min:0|max:120',
            'notes' => 'nullable|string|max:4000',
        ]);

        $profile = UserEmploymentProfile::query()->firstOrNew(['user_id' => (int) $user->id]);
        $profile->tenant_id = (int) ($user->tenant_id ?? 0) ?: null;
        $profile->employment_type = $validated['employment_type'] ?? null;
        $profile->family_dependents = (int) ($validated['family_dependents'] ?? 0);
        $profile->hired_at = $validated['hired_at'] ?? null;
        $profile->birth_date = $validated['birth_date'] ?? null;

        $calculatedAge = null;
        if (!empty($profile->birth_date)) {
            $calculatedAge = Carbon::parse((string) $profile->birth_date)->age;
        }

        $profile->age = isset($validated['age']) && $validated['age'] !== null
            ? (int) $validated['age']
            : $calculatedAge;
        $profile->notes = $validated['notes'] ?? null;

        if ($request->hasFile('contract_file')) {
            if (!empty($profile->contract_file_path) && Storage::disk('public')->exists($profile->contract_file_path)) {
                Storage::disk('public')->delete($profile->contract_file_path);
            }

            $profile->contract_file_path = $request->file('contract_file')->store('user_contracts', 'public');
        }

        if (!$profile->exists) {
            $profile->created_by = auth()->id();
        }
        $profile->updated_by = auth()->id();
        $profile->save();

        return back()->with('success', 'Ficha laboral actualizada correctamente.');
    }
}
