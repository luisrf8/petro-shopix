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
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Carbon\Carbon;


class TenantController extends Controller
{
    public function generateTenantImage(Request $request)
    {
        $validated = $request->validate([
            'type' => 'required|in:logo,background,category,product',
            'prompt' => 'nullable|string|max:2000',
            'messages' => 'nullable|array',
            'messages.*.role' => 'required_with:messages|in:user,assistant',
            'messages.*.content' => 'required_with:messages|string|max:2000',
            'reference_image' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:4096',
            'reference_image_data' => 'nullable|string',
            'reference_image_mime' => 'nullable|string|max:100',
            'shop_colors' => 'nullable|array',
            'shop_colors.color_primary' => 'nullable|string|max:20',
            'shop_colors.color_secondary' => 'nullable|string|max:20',
            'shop_colors.color_accent' => 'nullable|string|max:20',
            'background_ratio' => 'nullable|string|max:20',
            'image_operation' => 'nullable|in:generate,remove_background',
        ]);

        $apiKey = config('services.gemini.api_key');
        $preferredModel = config('services.gemini.model', 'gemini-2.0-flash-exp-image-generation');

        if (empty($apiKey)) {
            return response()->json([
                'success' => false,
                'message' => 'No está configurada la clave de Gemini API.',
            ], 500);
        }

        $operation = $validated['image_operation'] ?? 'generate';

        if ($validated['type'] === 'logo') {
            $typePrompt = 'Genera un logo profesional, limpio, sin texto, con fondo transparente.';
        } elseif ($validated['type'] === 'background') {
            $typePrompt = 'Genera una imagen de fondo profesional para ecommerce en formato horizontal 1920x1080.';
        } elseif ($validated['type'] === 'product') {
            if ($operation === 'remove_background') {
                $typePrompt = 'Elimina completamente el fondo de la imagen de producto adjunta. Mantén solo el producto principal recortado con bordes limpios, sin sombras de fondo y con fondo transparente.';
            } else {
                $typePrompt = 'Genera una imagen de producto para ecommerce, centrada, con iluminación de estudio, alta calidad y fondo limpio o transparente.';
            }
        } else {
            $typePrompt = 'Genera una imagen para categoría de productos ecommerce, clara, atractiva y centrada en el objeto principal.';
        }

        $messages = collect($validated['messages'] ?? [])
            ->filter(fn ($item) => !empty($item['content']))
            ->values();

        $prompt = trim((string) ($validated['prompt'] ?? ''));

        if ($messages->isEmpty() && $prompt === '') {
            return response()->json([
                'success' => false,
                'message' => 'Debes enviar un prompt para generar la imagen.',
            ], 422);
        }

        $conversationText = $messages->map(function ($item) {
            return ($item['role'] === 'assistant' ? 'Asistente' : 'Usuario') . ': ' . $item['content'];
        })->implode("\n");

        $colorContext = '';
        if (!empty($validated['shop_colors']) && is_array($validated['shop_colors'])) {
            $primary = $validated['shop_colors']['color_primary'] ?? null;
            $secondary = $validated['shop_colors']['color_secondary'] ?? null;
            $accent = $validated['shop_colors']['color_accent'] ?? null;
            $parts = array_filter([
                $primary ? "primario {$primary}" : null,
                $secondary ? "secundario {$secondary}" : null,
                $accent ? "acento {$accent}" : null,
            ]);

            if (!empty($parts)) {
                $colorContext = "Usa esta paleta de colores de la tienda: " . implode(', ', $parts) . ".\n";
            }
        }

        $ratioContext = '';
        if (!empty($validated['background_ratio'])) {
            $ratioContext = "Mantén una composición compatible con proporción aproximada {$validated['background_ratio']} (ancho/alto).\n";
        }

        $fullPrompt = trim($typePrompt . "\n\n" . $colorContext . $ratioContext . ($conversationText !== '' ? "Contexto del chat:\n{$conversationText}\n\n" : '') . ($prompt !== '' ? "Solicitud actual: {$prompt}\n\n" : '') . 'Devuelve únicamente la mejor versión de la imagen final.');

        $referenceImagePartCamel = null;
        $referenceImagePartSnake = null;
        if ($request->hasFile('reference_image')) {
            $referenceFile = $request->file('reference_image');
            $referenceBase64 = base64_encode(file_get_contents($referenceFile->getRealPath()));
            $referenceMime = $referenceFile->getMimeType() ?: 'image/png';

            $referenceImagePartCamel = [
                'inlineData' => [
                    'mimeType' => $referenceMime,
                    'data' => $referenceBase64,
                ],
            ];

            $referenceImagePartSnake = [
                'inline_data' => [
                    'mime_type' => $referenceMime,
                    'data' => $referenceBase64,
                ],
            ];
        } elseif (!empty($validated['reference_image_data'])) {
            $referenceBase64 = preg_replace('/^data:image\/[a-zA-Z0-9.+-]+;base64,/', '', (string) $validated['reference_image_data']);
            $referenceMime = $validated['reference_image_mime'] ?? 'image/png';

            $referenceImagePartCamel = [
                'inlineData' => [
                    'mimeType' => $referenceMime,
                    'data' => $referenceBase64,
                ],
            ];

            $referenceImagePartSnake = [
                'inline_data' => [
                    'mime_type' => $referenceMime,
                    'data' => $referenceBase64,
                ],
            ];
        }

        $candidateModels = array_values(array_unique(array_filter([
            $preferredModel,
            'gemini-2.0-flash-exp-image-generation',
            'gemini-2.0-flash-preview-image-generation',
            'gemini-2.5-flash-image-preview',
            'gemini-2.0-flash',
        ])));

        $apiVersions = ['v1beta', 'v1'];
        $lastError = null;

        $basePartsCamel = [['text' => $fullPrompt]];
        $basePartsSnake = [['text' => $fullPrompt]];
        if (!is_null($referenceImagePartCamel)) {
            $basePartsCamel[] = $referenceImagePartCamel;
        }
        if (!is_null($referenceImagePartSnake)) {
            $basePartsSnake[] = $referenceImagePartSnake;
        }

        $basePayloadCamel = [
            'contents' => [
                [
                    'parts' => $basePartsCamel,
                ],
            ],
        ];

        $basePayloadSnake = [
            'contents' => [
                [
                    'parts' => $basePartsSnake,
                ],
            ],
        ];

        $payloadVariants = [
            array_merge($basePayloadCamel, [
                'generationConfig' => [
                    'responseModalities' => ['TEXT', 'IMAGE'],
                ],
            ]),
            array_merge($basePayloadSnake, [
                'generation_config' => [
                    'response_modalities' => ['TEXT', 'IMAGE'],
                ],
            ]),
            $basePayloadCamel,
            $basePayloadSnake,
        ];

        foreach ($candidateModels as $model) {
            foreach ($apiVersions as $version) {
                foreach ($payloadVariants as $payload) {
                    $response = Http::timeout(90)->post(
                        "https://generativelanguage.googleapis.com/{$version}/models/{$model}:generateContent?key={$apiKey}",
                        $payload
                    );

                    if (!$response->successful()) {
                        $errorMessage = $response->json('error.message') ?? $response->body();
                        $lastError = $errorMessage;

                        $normalizedError = Str::lower((string) $errorMessage);
                        if (Str::contains($normalizedError, ['quota exceeded', 'rate limit', 'billing', 'limit: 0'])) {
                            return response()->json([
                                'success' => false,
                                'message' => 'Tu clave de Gemini no tiene cuota disponible en este proyecto.',
                                'error' => $errorMessage,
                                'tried_models' => $candidateModels,
                            ], 429);
                        }

                        if (Str::contains($normalizedError, ['api key not valid', 'permission denied', 'unauthenticated', 'forbidden'])) {
                            return response()->json([
                                'success' => false,
                                'message' => 'La clave de Gemini no es válida o no tiene permisos para generar imágenes.',
                                'error' => $errorMessage,
                                'tried_models' => $candidateModels,
                            ], 403);
                        }

                        continue;
                    }

                    $parts = data_get($response->json(), 'candidates.0.content.parts', []);
                    $inlinePart = collect($parts)->first(function ($part) {
                        return isset($part['inlineData']['data']) || isset($part['inline_data']['data']);
                    });

                    $base64 = data_get($inlinePart, 'inlineData.data') ?? data_get($inlinePart, 'inline_data.data');
                    $mimeType = data_get($inlinePart, 'inlineData.mimeType') ?? data_get($inlinePart, 'inline_data.mime_type') ?? 'image/png';

                    if (!empty($base64)) {
                        return response()->json([
                            'success' => true,
                            'data' => $base64,
                            'mime_type' => $mimeType,
                        ]);
                    }

                    $textReply = data_get($response->json(), 'candidates.0.content.parts.0.text');
                    if (!empty($textReply)) {
                        $lastError = 'El modelo respondió solo texto y no imagen.';
                    } else {
                        $lastError = 'El modelo respondió, pero no devolvió imagen inline.';
                    }
                }
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'No se pudo generar la imagen con Gemini.',
            'error' => $lastError ?: 'No se encontró un modelo compatible para generación de imagen.',
            'tried_models' => $candidateModels,
        ], 422);
    }

    public function index()
    {
        // Trae todos los tenants con todos sus planes asociados
        $tenants = Tenant::with(['tenantPlanPayments.plan', 'users.role'])->get();


        // O solo el plan activo de cada tenant
        // $tenants = Tenant::with(['activePlanPayment.plan'])->get();

        $plans = Plan::all();

        return view('tenant', compact('tenants', 'plans'));
    }
    
    public function getTenant()
    {
        $user = auth()->user();
        $tenant = Tenant::with(['users.role'])->where('id', $user->tenant_id)->first();
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

        $cartEnabled = $this->tenantHasProPlan($tenant);
        $cartPlanName = $this->getTenantCurrentPlanName($tenant);

        return view('ecommerceInf', compact('tenant', 'categories', 'productItems', 'cartEnabled', 'cartPlanName'));
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

        $tenantPlanPaymentData = [
            'tenant_id' => $tenant->id,
            'plan_id'   => $plan->id,
            'amount'    => $plan->price,
            'status'    => 'paid', // o pending si quieres validar pago
            'paid_at'   => now(),
        ];

        if (Schema::hasColumn('tenant_plan_payments', 'expires_at')) {
            $tenantPlanPaymentData['expires_at'] = now()->addDays((int) ($plan->duration_days ?? 0));
        }

        TenantPlanPayment::create($tenantPlanPaymentData);

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
        $validated = $request->validate([
            'name'                  => 'required|string|max:255',
            'slug'                  => 'required|string|max:255|unique:tenants,slug',
            'email'                 => 'required|email|unique:tenants,email',
            'logo'                  => 'nullable|image|mimes:png,svg|max:2048',
            'background_image'      => 'nullable|image|mimes:png,jpg,jpeg|max:4096',
            'color_primary'         => 'required|string|max:7',
            'color_secondary'       => 'required|string|max:7',
            'color_accent'          => 'required|string|max:7',
            'country'               => 'required|exists:countries,id',
            'state'                 => 'required|exists:states,id',
            'city'                  => 'required|exists:cities,id',
            'phone_code'            => 'required|string|max:5',
            'phone_number'          => 'required|string|max:20',
            'plan_id'               => 'required|exists:plans,id',
            'address'               => 'nullable|string|max:255',
            'latitude'              => 'nullable|numeric',
            'longitude'             => 'nullable|numeric',
            'slogan'                => 'nullable|string|max:255',
            'description'           => 'nullable|string',
            'users.owner.name'      => 'required|string|max:255',
            'users.owner.email'     => 'required|email|unique:users,email',
            'users.owner.password'  => 'required|string|min:8',
            'users.owner.phone_number' => 'nullable|string|max:20',
            'users.owner.dni'       => 'nullable|string|max:50',
        ]);

        DB::beginTransaction();

        try {
            $logoPath = null;
            if ($request->hasFile('logo')) {
                $logoPath = $request->file('logo')->store('tenants/logos', 'public');
            }

            $backgroundPath = null;
            if ($request->hasFile('background_image')) {
                $backgroundPath = $request->file('background_image')->store('tenants/backgrounds', 'public');
            }

            $tenant = Tenant::create([
                'name'            => $validated['name'],
                'slug'            => Str::slug($validated['slug']),
                'email'           => $validated['email'],
                'logo'            => $logoPath,
                'color_primary'   => $validated['color_primary'],
                'color_secondary' => $validated['color_secondary'],
                'color_accent'    => $validated['color_accent'],
                'country'         => $validated['country'],
                'state'           => $validated['state'],
                'city'            => $validated['city'],
                'phone_code'      => $validated['phone_code'],
                'phone_number'    => $validated['phone_number'],
                'slogan'          => $validated['slogan'] ?? null,
                'description'     => $validated['description'] ?? null,
                'address'         => $validated['address'] ?? null,
                'latitude'        => $validated['latitude'] ?? null,
                'longitude'       => $validated['longitude'] ?? null,
                'background_image'=> $backgroundPath,
            ]);

            $plan = Plan::findOrFail($validated['plan_id']);
            $tenantPlanPaymentData = [
                'tenant_id' => $tenant->id,
                'plan_id'   => $plan->id,
                'amount'    => $plan->price,
                'status'    => 'paid',
                'paid_at'   => now(),
            ];

            if (Schema::hasColumn('tenant_plan_payments', 'expires_at')) {
                $tenantPlanPaymentData['expires_at'] = now()->addDays((int) ($plan->duration_days ?? 0));
            }

            TenantPlanPayment::create($tenantPlanPaymentData);

            $ownerRole = Role::where('name', 'owner')->first();
            User::create([
                'name'        => $validated['users']['owner']['name'],
                'email'       => $validated['users']['owner']['email'],
                'phone_number'=> $validated['users']['owner']['phone_number'] ?? null,
                'dni'         => $validated['users']['owner']['dni'] ?? null,
                'password'    => Hash::make($validated['users']['owner']['password']),
                'tenant_id'   => $tenant->id,
                'role_id'     => $ownerRole?->id,
                'is_active'   => 1,
            ]);

            DB::commit();

            return redirect()
                ->route('login')
                ->with('status', 'Tu tienda fue creada exitosamente. Ahora inicia sesión con tu cuenta.')
                ->withInput([
                    'email' => $validated['users']['owner']['email'],
                ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()
                ->withErrors(['create_tenant' => 'No se pudo crear la tienda. Intenta nuevamente.'])
                ->withInput();
        }
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
            'email' => 'nullable|email|unique:tenants,email,' . $tenant->id,
            'logo'  => 'nullable|string',
            'color_primary'   => 'nullable|string|max:7',
            'color_secondary' => 'nullable|string|max:7',
            'color_accent'    => 'nullable|string|max:7',
            'country'         => 'nullable|string|max:255',
            'state'           => 'nullable|string|max:255',
            'city'            => 'nullable|string|max:255',
            'phone_code'      => 'nullable|string|max:5',
            'phone_number'    => 'nullable|string|max:20',
            'slogan'          => 'nullable|string|max:255',
            'description'     => 'nullable|string',
            'address'         => 'nullable|string|max:255',
            'latitude'        => 'nullable|numeric',
            'longitude'       => 'nullable|numeric',
            'background_image'=> 'nullable|string',
            'tiktok'          => 'nullable|string|max:255',
            'instagram'       => 'nullable|string|max:255',
            'facebook'        => 'nullable|string|max:255',
            'owner_name'      => 'nullable|string|max:255',
            'owner_email'     => 'nullable|email|max:255',
            'owner_phone_number' => 'nullable|string|max:20',
            'owner_dni'       => 'nullable|string|max:50',
            'owner_password'  => 'nullable|string|min:8',
            'plan_id' => 'nullable|exists:plans,id',
            'is_active' => 'nullable|boolean',
        ]);

        $latestPaidPlanPayment = $tenant->tenantPlanPayments()
            ->where('status', 'paid')
            ->orderByDesc('paid_at')
            ->orderByDesc('id')
            ->first();

        $currentPlanId = $latestPaidPlanPayment?->plan_id;
        $incomingPlanId = isset($validated['plan_id']) ? (int) $validated['plan_id'] : null;
        $planChanged = !is_null($incomingPlanId) && ((int) $currentPlanId !== $incomingPlanId);

        $ownerRole = Role::where('name', 'owner')->first();
        $owner = $tenant->users()
            ->when($ownerRole, function ($query) use ($ownerRole) {
                $query->where('role_id', $ownerRole->id);
            })
            ->first();

        if (!$owner) {
            $owner = $tenant->users()->orderBy('id')->first();
        }

        if (!empty($validated['owner_email'])) {
            $existingUserWithEmail = User::where('email', $validated['owner_email'])->first();

            if ($existingUserWithEmail) {
                $sameOwner = $owner && ((int) $existingUserWithEmail->id === (int) $owner->id);
                $belongsToSameTenant = (int) $existingUserWithEmail->tenant_id === (int) $tenant->id;

                if (!$sameOwner && !$belongsToSameTenant) {
                    return response()->json([
                        'message' => 'El correo del dueño ya está en uso por otro usuario',
                    ], 422);
                }

                if (!$owner && $belongsToSameTenant) {
                    $owner = $existingUserWithEmail;
                }
            }

        }

        $ownerDataProvided =
            array_key_exists('owner_name', $validated) ||
            array_key_exists('owner_email', $validated) ||
            array_key_exists('owner_phone_number', $validated) ||
            array_key_exists('owner_dni', $validated) ||
            array_key_exists('owner_password', $validated);

        $ownerHasChanges = false;

        if ($ownerDataProvided) {
            if (!$owner) {
                $ownerHasChanges =
                    !empty($validated['owner_name']) ||
                    !empty($validated['owner_email']) ||
                    !empty($validated['owner_phone_number']) ||
                    !empty($validated['owner_dni']) ||
                    !empty($validated['owner_password']);
            } else {
                if (array_key_exists('owner_name', $validated) && (string) ($validated['owner_name'] ?? '') !== (string) $owner->name) {
                    $ownerHasChanges = true;
                }

                if (array_key_exists('owner_email', $validated) && (string) ($validated['owner_email'] ?? '') !== (string) $owner->email) {
                    $ownerHasChanges = true;
                }

                if (array_key_exists('owner_phone_number', $validated) && (string) ($validated['owner_phone_number'] ?? '') !== (string) ($owner->phone_number ?? '')) {
                    $ownerHasChanges = true;
                }

                if (array_key_exists('owner_dni', $validated) && (string) ($validated['owner_dni'] ?? '') !== (string) ($owner->dni ?? '')) {
                    $ownerHasChanges = true;
                }

                if (!empty($validated['owner_password'])) {
                    $ownerHasChanges = true;
                }
            }
        }

        $tenantData = [
            'name' => $validated['name'] ?? $tenant->name,
            'slug' => array_key_exists('slug', $validated) ? Str::slug((string) $validated['slug']) : $tenant->slug,
            'email' => $validated['email'] ?? $tenant->email,
            'logo' => $validated['logo'] ?? $tenant->logo,
            'color_primary' => $validated['color_primary'] ?? $tenant->color_primary,
            'color_secondary' => $validated['color_secondary'] ?? $tenant->color_secondary,
            'color_accent' => $validated['color_accent'] ?? $tenant->color_accent,
            'country' => $validated['country'] ?? $tenant->country,
            'state' => $validated['state'] ?? $tenant->state,
            'city' => $validated['city'] ?? $tenant->city,
            'phone_code' => $validated['phone_code'] ?? $tenant->phone_code,
            'phone_number' => $validated['phone_number'] ?? $tenant->phone_number,
            'slogan' => $validated['slogan'] ?? $tenant->slogan,
            'description' => $validated['description'] ?? $tenant->description,
            'address' => $validated['address'] ?? $tenant->address,
            'latitude' => $validated['latitude'] ?? $tenant->latitude,
            'longitude' => $validated['longitude'] ?? $tenant->longitude,
            'background_image' => $validated['background_image'] ?? $tenant->background_image,
            'tiktok' => $validated['tiktok'] ?? $tenant->tiktok,
            'instagram' => $validated['instagram'] ?? $tenant->instagram,
            'facebook' => $validated['facebook'] ?? $tenant->facebook,
            'is_active' => $validated['is_active'] ?? $tenant->is_active,
        ];

        $tenant->fill($tenantData);
        $tenantHasChanges = $tenant->isDirty();

        if (!$tenantHasChanges && !$ownerHasChanges && !$planChanged) {
            return response()->json([
                'message' => 'No se detectaron cambios para actualizar',
                'tenant'  => $tenant->load(['tenantPlanPayments.plan', 'users.role']),
            ]);
        }

        if ($ownerHasChanges) {
            if (!$owner) {
                $owner = new User();
                $owner->tenant_id = $tenant->id;
                if ($ownerRole) {
                    $owner->role_id = $ownerRole->id;
                }
                $owner->is_active = 1;
            }

            $owner->name = $validated['owner_name'] ?? $owner->name ?? 'Owner';
            $owner->email = $validated['owner_email'] ?? $owner->email;
            $owner->phone_number = $validated['owner_phone_number'] ?? $owner->phone_number;
            $owner->dni = $validated['owner_dni'] ?? $owner->dni;

            if (!empty($validated['owner_password'])) {
                $owner->password = Hash::make($validated['owner_password']);
            } elseif (!$owner->exists) {
                $owner->password = Hash::make('password123');
            }

            $owner->save();
        }

        if ($tenantHasChanges) {
            $tenant->save();
        }

        // Si cambia el plan
        if ($planChanged) {
            $plan = Plan::findOrFail($incomingPlanId);
            $paidAt = Carbon::now();
            $expiresAt = (clone $paidAt)->addDays((int) ($plan->duration_days ?? 0));

            $tenantPlanPaymentData = [
                'tenant_id' => $tenant->id,
                'plan_id' => $plan->id,
                'amount' => $plan->price,
                'status' => 'paid',
                'paid_at' => $paidAt,
            ];

            if (Schema::hasColumn('tenant_plan_payments', 'expires_at')) {
                $tenantPlanPaymentData['expires_at'] = $expiresAt;
            }

            TenantPlanPayment::create($tenantPlanPaymentData);
        }

        return response()->json([
            'message' => 'Tenant actualizado correctamente',
            'tenant'  => $tenant->load(['tenantPlanPayments.plan', 'users.role']),
        ]);
    }

    public function updateTenant(Request $request)
    {
        $user = auth()->user();
        $tenant = Tenant::findOrFail($user->tenant_id);

        try {
            $validated = $request->validate([
                'name'            => 'nullable|string|max:255',
                'slug'            => 'nullable|string|max:255|unique:tenants,slug,' . $tenant->id,
                'slogan'          => 'nullable|string|max:255',
                'description'     => 'nullable|string',
                'logo'            => 'nullable|image|mimes:png,svg|max:2048',
                'color_primary'   => 'nullable|string|max:7',
                'color_secondary' => 'nullable|string|max:7',
                'color_accent'    => 'nullable|string|max:7',
                'country'         => 'nullable|exists:countries,id',
                'state'           => 'nullable|exists:states,id',
                'city'            => 'nullable|exists:cities,id',
                'phone_code'      => 'nullable|string|max:5',
                'phone_number'    => 'nullable|string|max:20',
                'address'         => 'nullable|string|max:255',
                'latitude'        => 'nullable|numeric',
                'longitude'       => 'nullable|numeric',
                'background_image'       => 'nullable|image|mimes:png,jpg,jpeg|max:2048',
                'tiktok'         => 'nullable|string|max:255',
                'instagram'         => 'nullable|string|max:255',
                'facebook'         => 'nullable|string|max:255',
            ]);

            $newUserInput = $request->input('new_user', []);
            $shouldCreateNewUser = false;
            if (is_array($newUserInput)) {
                foreach ($newUserInput as $value) {
                    if (!is_null($value) && trim((string)$value) !== '') {
                        $shouldCreateNewUser = true;
                        break;
                    }
                }
            }

            if ($shouldCreateNewUser) {
                $newUserValidated = $request->validate([
                    'new_user.name'         => 'required|string|max:255',
                    'new_user.email'        => 'required|email|unique:users,email',
                    'new_user.password'     => 'required|string|min:8',
                    'new_user.role_id'      => 'required|exists:roles,id',
                    'new_user.phone_number' => 'nullable|string|max:20',
                    'new_user.dni'          => 'nullable|string|max:50',
                ]);
            }

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
                'name'            => $validated['name'] ?? $tenant->name,
                'slug'            => isset($validated['slug']) ? Str::slug($validated['slug']) : $tenant->slug,
                'slogan'          => $validated['slogan'] ?? $tenant->slogan,
                'description'     => $validated['description'] ?? $tenant->description,
                'color_primary'   => $validated['color_primary'] ?? $tenant->color_primary,
                'color_secondary' => $validated['color_secondary'] ?? $tenant->color_secondary,
                'color_accent'    => $validated['color_accent'] ?? $tenant->color_accent,
                'country'         => $validated['country'] ?? $tenant->country,
                'state'           => $validated['state'] ?? $tenant->state,
                'city'            => $validated['city'] ?? $tenant->city,
                'phone_code'      => $validated['phone_code'] ?? $tenant->phone_code,
                'phone_number'    => $validated['phone_number'] ?? $tenant->phone_number,
                'address'         => $validated['address'] ?? $tenant->address,
                'latitude'        => $validated['latitude'] ?? $tenant->latitude,
                'longitude'       => $validated['longitude'] ?? $tenant->longitude,
                'tiktok'          => $validated['tiktok'] ?? $tenant->tiktok,
                'instagram'      => $validated['instagram'] ?? $tenant->instagram,
                'facebook'       => $validated['facebook'] ?? $tenant->facebook,
                'background_image'=> $tenant->background_image, // 👈 clave
            ]);

            if ($shouldCreateNewUser) {
                User::create([
                    'name'        => $newUserValidated['new_user']['name'],
                    'email'       => $newUserValidated['new_user']['email'],
                    'password'    => Hash::make($newUserValidated['new_user']['password']),
                    'tenant_id'   => $tenant->id,
                    'role_id'     => $newUserValidated['new_user']['role_id'],
                    'phone_number'=> $newUserValidated['new_user']['phone_number'] ?? null,
                    'dni'         => $newUserValidated['new_user']['dni'] ?? null,
                    'is_active'   => 1,
                ]);
            }

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

        $cartEnabled = $this->tenantHasProPlan($tenant);
        $cartPlanName = $this->getTenantCurrentPlanName($tenant);

        return view('ecommerceCategory', compact(
            'tenant',
            'categories',
            'products',
            'cartEnabled',
            'cartPlanName'
        ));
    }
    public function publicTenantProduct(Tenant $tenant, Product $product)
    {
        // $tenant y $product son inyectados automáticamente por el model binding de Laravel
        // gracias a la ruta '/{tenant:slug}/{product:slug}'
        
        if ((int) $product->tenant_id !== (int) $tenant->id) {
            abort(404);
        }

        // Cargar cualquier relación necesaria (ej: category, variants, images)
        $product->load(['category', 'variants', 'images']);

        $cartEnabled = $this->tenantHasProPlan($tenant);
        $cartPlanName = $this->getTenantCurrentPlanName($tenant);

        return view('ecommerceProduct', compact('tenant', 'product', 'cartEnabled', 'cartPlanName'));
    }

    private function getTenantCurrentPlanName(Tenant $tenant): ?string
    {
        $latestPaidPlanPayment = $tenant->tenantPlanPayments()
            ->with('plan')
            ->where('status', 'paid')
            ->orderByDesc('paid_at')
            ->orderByDesc('id')
            ->first();

        return $latestPaidPlanPayment?->plan?->name;
    }

    private function tenantHasProPlan(Tenant $tenant): bool
    {
        $planName = Str::lower((string) $this->getTenantCurrentPlanName($tenant));

        return Str::contains($planName, 'pro');
    }

    public function destroy(Tenant $tenant)
    {
        DB::raw("SET @user_id = " . auth()->id());

        $tenant->delete();

        return response()->json(['message' => 'Tenant eliminado correctamente']);
    }
}
