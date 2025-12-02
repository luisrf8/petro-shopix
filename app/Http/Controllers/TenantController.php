<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Models\User;
use App\Models\Plan;
use Illuminate\Support\Facades\Hash;
use App\Models\TenantPlanPayment;
use App\Models\Category;
use App\Models\Product;
use App\Models\Role;

class TenantController extends Controller
{
public function index()
{
    // Trae todos los tenants con todos sus planes asociados
    $tenants = Tenant::with(['tenantPlanPayments.plan'])->get();

    // O solo el plan activo de cada tenant
    // $tenants = Tenant::with(['activePlanPayment.plan'])->get();

    $plans = Plan::all();

    return view('tenant', compact('tenants', 'plans'));
}


    public function createIndex()
    {
        
        $tenants = Tenant::all();
        $plans = Plan::all();
        return view('createTenant', compact('tenants', 'plans'));

    }

    public function createIndexUser()
    {
        $tenants = Tenant::all();
        $plans = Plan::all();
        return view('createTenantUser', compact('tenants', 'plans'));

    }

    public function publicTenantindex(Tenant $tenant)
    {
        // Cargar categorías y productos del tenant
        $categories = Category::where('tenant_id', $tenant->id)->get();
        $productItems = Product::where('tenant_id', $tenant->id)
            ->with('images')
            ->get();

        return view('ecommerceInf', compact('tenant', 'categories', 'productItems'));
    }

    // Página filtrada por categoría
    public function publicTenantCategory(Tenant $tenant, Category $category)
    {
        $categories = Category::where('tenant_id', $tenant->id)->get();
        $productItems = ProductItem::where('tenant_id', $tenant->id)
            ->where('category_id', $category->id)
            ->with('images')
            ->get();

        return view('ecommerceInf', compact('tenant', 'categories', 'productItems', 'category'));
    }

    public function store(Request $request)
    {
        DB::raw("SET @user_id = " . auth()->id());

        $request->validate([
            'name'            => 'required|string|max:255',
            'slug'            => 'required|string|max:255|unique:tenants,slug',
            'email'           => 'required|email|unique:tenants,email',
            'logo'            => 'nullable|image|mimes:png,svg|max:2048',
            'color_primary'   => 'required|string|max:7',
            'color_secondary' => 'required|string|max:7',
            'color_accent'    => 'required|string|max:7',
            'country'         => 'required|string|max:255',
            'state'           => 'required|string|max:255',
            'city'            => 'required|string|max:255',
            'phone_code'      => 'required|string|max:5',
            'phone_number'    => 'required|string|max:20',
            'users'           => 'array',
            'plan_id'         => 'required|exists:plans,id',
        ]);

        // 📂 Subir logo si existe
        $logoPath = null;
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('tenants/logos', 'public');
        }

        $tenant = Tenant::create([
            'name'            => $request->name,
            'slug'            => Str::slug($request->slug),
            'email'           => $request->email,
            'logo'            => $logoPath,
            'color_primary'   => $request->color_primary,
            'color_secondary' => $request->color_secondary,
            'color_accent'    => $request->color_accent,
            'country'         => $request->country,
            'state'           => $request->state,
            'city'            => $request->city,
            'phone_code'      => $request->phone_code,
            'phone_number'    => $request->phone_number,
        ]);

        // 💳 Crear relación TenantPayment
        $plan = Plan::findOrFail($request->plan_id);

        TenantPlanPayment::create([
            'tenant_id' => $tenant->id,
            'plan_id'   => $plan->id,
            'amount'    => $plan->price,
            'status'    => 'paid', // o pending si quieres validar pago
            'paid_at'   => now(),
        ]);

        // 🎭 Obtener roles existentes
        $roles = Role::whereIn('name', ['owner', 'admin', 'vendor'])->get()->keyBy('name');

        // 👥 Crear usuarios enviados en el formulario
        if ($request->has('users')) {
            foreach ($request->users as $roleName => $userData) {
                if (!empty($userData['email'])) {
                    $user = User::create([
                        'name'      => $userData['name'] ?? ucfirst($roleName),
                        'email'     => $userData['email'],
                        'password'  => Hash::make($userData['password'] ?? 'password123'),
                        'tenant_id' => $tenant->id,
                        'is_active' => 1,
                    ]);

                    // Asignar rol automáticamente según el nombre
                    if (isset($roles[$roleName])) {
                        $user->assignRole($roles[$roleName]->name);
                    } elseif ($roleName === 'owner') {
                        $user->assignRole('admin');
                    }
                }
            }
        }

        return response()->json([
            'message' => 'Creado Exitosamente',
            'tenant'  => $tenant,
        ]);
    }
    public function storePublic(Request $request)
    {
        DB::raw("SET @user_id = " . auth()->id());

        $request->validate([
            'name'            => 'required|string|max:255',
            'slug'            => 'required|string|max:255|unique:tenants,slug',
            'email'           => 'required|email|unique:tenants,email',
            'logo'            => 'nullable|image|mimes:png,svg|max:2048',
            'color_primary'   => 'required|string|max:7',
            'color_secondary' => 'required|string|max:7',
            'color_accent'    => 'required|string|max:7',
            'country'         => 'required|string|max:255',
            'state'           => 'required|string|max:255',
            'city'            => 'required|string|max:255',
            'phone_code'      => 'required|string|max:5',
            'phone_number'    => 'required|string|max:20',
            'users'           => 'array',
            'plan_id'         => 'required|exists:plans,id',
        ]);

        // 📂 Subir logo si existe
        $logoPath = null;
        if ($request->hasFile('logo')) {
            $logoPath = $request->file('logo')->store('tenants/logos', 'public');
        }

        $tenant = Tenant::create([
            'name'            => $request->name,
            'slug'            => Str::slug($request->slug),
            'email'           => $request->email,
            'logo'            => $logoPath,
            'color_primary'   => $request->color_primary,
            'color_secondary' => $request->color_secondary,
            'color_accent'    => $request->color_accent,
            'country'         => $request->country,
            'state'           => $request->state,
            'city'            => $request->city,
            'phone_code'      => $request->phone_code,
            'phone_number'    => $request->phone_number,
        ]);

        // 💳 Crear relación TenantPayment
        $plan = Plan::findOrFail($request->plan_id);

        TenantPlanPayment::create([
            'tenant_id' => $tenant->id,
            'plan_id'   => $plan->id,
            'amount'    => $plan->price,
            'status'    => 'paid', // o pending si quieres validar pago
            'paid_at'   => now(),
        ]);

        // 🎭 Obtener roles existentes
        $roles = Role::whereIn('name', ['owner', 'admin', 'vendor'])->get()->keyBy('name');

        // 👥 Crear usuarios enviados en el formulario
        if ($request->has('users')) {
            foreach ($request->users as $roleName => $userData) {
                if (!empty($userData['email'])) {
                    $user = User::create([
                        'name'      => $userData['name'] ?? ucfirst($roleName),
                        'email'     => $userData['email'],
                        'password'  => Hash::make($userData['password'] ?? 'password123'),
                        'tenant_id' => $tenant->id,
                        'is_active' => 1,
                    ]);

                    // Asignar rol automáticamente según el nombre
                    if (isset($roles[$roleName])) {
                        $user->assignRole($roles[$roleName]->name);
                    } elseif ($roleName === 'owner') {
                        $user->assignRole('admin');
                    }
                }
            }
        }

        return response()->json([
            'message' => 'Creado Exitosamente',
            'tenant'  => $tenant,
        ]);
    }

    public function show(Tenant $tenant)
    {
        return $tenant;
    }

    public function update(Request $request, Tenant $tenant)
    {
        DB::raw("SET @user_id = " . auth()->id());

        $validated = $request->validate([
            'name'  => 'sometimes|string|max:255',
            'slug'  => 'sometimes|string|max:255|unique:tenants,slug,' . $tenant->id,
            'email' => 'nullable|email',
            'logo'  => 'nullable|string',
            'plan_id' => 'nullable|exists:plans,id',
            'is_active' => 'nullable|boolean',
        ]);
        // Actualizar datos del tenant
        $tenant->update([
            'name' => $validated['name'] ?? $tenant->name,
            'slug' => $validated['slug'] ?? $tenant->slug,
            'email' => $validated['email'] ?? $tenant->email,
            'logo' => $validated['logo'] ?? $tenant->logo,
            'is_active' => $validated['is_active'] ?? $tenant->is_active,
        ]);

        // Si cambia el plan
        if (!empty($validated['plan_id'])) {
            $plan = Plan::find($validated['plan_id']);

            TenantPlanPayment::create([
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'amount' => $plan->price,
                'status' => 'paid',
                'paid_at' => now(),
            ]);
        }

        return response()->json([
            'message' => 'Tenant actualizado correctamente',
            'tenant'  => $tenant,
        ]);
    }

    public function destroy(Tenant $tenant)
    {
        DB::raw("SET @user_id = " . auth()->id());

        $tenant->delete();

        return response()->json(['message' => 'Tenant eliminado correctamente']);
    }
}
