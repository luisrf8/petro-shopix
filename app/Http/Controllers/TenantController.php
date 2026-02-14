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
use App\Models\Country;
use App\Models\State;
use App\Models\City;
use Illuminate\Support\Facades\Storage;


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
    
    public function getTenant()
    {
        $user = auth()->user();
        $tenant = Tenant::where('id', $user->tenant_id)->first();
        $roles = Role::whereNotIn('name', ['owner', 'user', 'super_user'])->get();
        $countries = Country::all();
        $states = State::all();
        $cities = City::all();
        return view('tenantStore', compact('tenant', 'roles', 'countries', 'states', 'cities'));
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
        $countries = Country::all();
        $states = State::all();
        $cities = City::all();
        return view('createTenantUser', compact('tenants', 'plans', 'countries', 'states', 'cities'));

    }

    public function publicTenantindex(Tenant $tenant)
    {
        // Cargar categorías y productos del tenant
        $categories = Category::where('tenant_id', $tenant->id)->get();
        $productItems = Product::where('tenant_id', $tenant->id)
            ->with('images')
            ->limit(9)
            ->get();

        return view('ecommerceInf', compact('tenant', 'categories', 'productItems'));
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
            'country'        => 'required|exists:countries,id',
            'state'          => 'required|exists:states,id',
            'city'           => 'required|exists:cities,id',
            'latitude'      => 'required|numeric',
            'longitude'     => 'required|numeric',
            'address'       => 'required|string|max:255',
            'slogan'       => 'nullable|string|max:255',
            'description'  => 'nullable|string',

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
                        'phone_number' => $userData['phone_number'] ?? null,
                        'dni'       => $userData['dni'] ?? null,
                        'password'  => Hash::make($userData['password'] ?? 'password123'),
                        'tenant_id' => $tenant->id,
                        'is_active' => 1,
                    ]);

                    // Asignar rol automáticamente según el nombre
                    if (isset($roles[$roleName])) {
                        $user->role_id = $roles[$roleName]->id; // Asignamos el ID directamente
                        $user->save();
                    } elseif ($roleName === 'owner') {
                        $adminRole = Role::where('name', 'owner')->first();
                        if ($adminRole) {
                            $user->role_id = $adminRole->id;
                            $user->save();
                        }
                    }
                }
            }
        }
        // return view('createTenantUser', compact('tenants', 'plans', 'countries', 'states', 'cities'));
        return redirect()->route('login')->with('success', 'Tenant creado exitosamente. Por favor, inicie sesión.');
        // return response()->json([
        //     'message' => 'Creado Exitosamente',
        //     'tenant'  => $tenant,
        // ]);
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

    public function updateTenant(Request $request)
    {
        $user = auth()->user();
        $tenant = Tenant::findOrFail($user->tenant_id);

        try {
            $validated = $request->validate([
                'name'            => 'required|string|max:255',
                'slug'            => 'required|string|max:255|unique:tenants,slug,' . $tenant->id,
                'slogan'          => 'nullable|string|max:255',
                'description'     => 'nullable|string',
                'logo'            => 'nullable|image|mimes:png,svg|max:2048',
                'color_primary'   => 'required|string|max:7',
                'color_secondary' => 'required|string|max:7',
                'color_accent'    => 'required|string|max:7',
                'country'         => 'required',
                'state'           => 'required',
                'city'            => 'required',
                'phone_code'      => 'required|string|max:5',
                'phone_number'    => 'required|string|max:20',
                'address'         => 'nullable|string|max:255',
                'latitude'        => 'nullable|numeric',
                'longitude'       => 'nullable|numeric',
                'background_image'       => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
                'tiktok'         => 'nullable|string|max:255',
                'instagram'         => 'nullable|string|max:255',
                'facebook'         => 'nullable|string|max:255',
            ]);

            // Manejar subida de logo
            if ($request->hasFile('logo')) {
                $logoPath = $request->file('logo')->store('tenants/logos', 'public');
                $tenant->logo = $logoPath;
            }
            // Manejar imagen de fondo
            if ($request->hasFile('background_image')) {

                // Eliminar imagen anterior si existe
                if ($tenant->background_image && Storage::disk('public')->exists($tenant->background_image)) {
                    Storage::disk('public')->delete($tenant->background_image);
                }

                // Guardar nueva imagen
                $backgroundPath = $request->file('background_image')
                    ->store('tenants/backgrounds', 'public');

                $tenant->background_image = $backgroundPath;
            }
            // Actualizar campos
            $tenant->update([
                'name'            => $validated['name'],
                'slug'            => Str::slug($validated['slug']),
                'slogan'          => $validated['slogan'] ?? $tenant->slogan,
                'description'     => $validated['description'] ?? $tenant->description,
                'color_primary'   => $validated['color_primary'],
                'color_secondary' => $validated['color_secondary'],
                'color_accent'    => $validated['color_accent'],
                'country'         => $validated['country'],
                'state'           => $validated['state'],
                'city'            => $validated['city'],
                'city'            => $validated['city'],
                'phone_code'            => $validated['phone_code'],
                'phone_number'            => $validated['phone_number'],
                'address'         => $validated['address'] ?? $tenant->address,
                'latitude'        => $validated['latitude'] ?? $tenant->latitude,
                'longitude'       => $validated['longitude'] ?? $tenant->longitude,
                'tiktok'          => $validated['tiktok'] ?? $tenant->tiktok,
                'instagram'      => $validated['instagram'] ?? $tenant->instagram,
                'facebook'       => $validated['facebook'] ?? $tenant->facebook,
                'background_image'=> $tenant->background_image, // 👈 clave
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Tenant actualizado correctamente',
                'tenant'  => $tenant,
            ]);

        } catch (\Illuminate\Validation\ValidationException $e) {
            // Devolver errores de validación en JSON
            return response()->json([
                'success' => false,
                'message' => 'Errores de validación',
                'errors'  => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            // Devolver errores generales
            return response()->json([
                'success' => false,
                'message' => 'Ocurrió un error al actualizar el tenant',
                'error'   => $e->getMessage(),
            ], 500);
        }
    }
    public function publicTenantCategory(Tenant $tenant)
    {
        // Asegurarse que la categoría pertenece al tenant
        // if ($category->tenant_id !== $tenant->id) {
        //     abort(404);
        // }

        $categories = Category::where('tenant_id', $tenant->id)
            // ->where('status', 1)
            ->get();

        $products = Product::where('tenant_id', $tenant->id)
            // ->where('status', 1)
            ->with('images')
            ->get();

        return view('ecommerceCategory', compact(
            'tenant',
            'categories',
            'products'
        ));
    }
    public function publicTenantProduct(Tenant $tenant, Product $product)
    {
        // $tenant y $product son inyectados automáticamente por el model binding de Laravel
        // gracias a la ruta '/{tenant:slug}/{product:slug}'
        
        // Cargar cualquier relación necesaria (ej: category, variants, images)
        $product->load(['category', 'variants', 'images']);

        return view('ecommerceProduct', compact('tenant', 'product'));
    }

    public function destroy(Tenant $tenant)
    {
        DB::raw("SET @user_id = " . auth()->id());

        $tenant->delete();

        return response()->json(['message' => 'Tenant eliminado correctamente']);
    }
}
