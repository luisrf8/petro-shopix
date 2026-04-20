<?php

namespace App\Http\Controllers;

use App\Models\Tenant;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use App\Support\WorkflowNotifier;
use App\Models\User;
use App\Models\Plan;
use Illuminate\Support\Facades\Hash;
use App\Models\TenantPlanPayment;
use App\Models\Category;
use App\Models\Product;
use App\Models\MaterialPackage;
use App\Models\Role;
use App\Models\Country;
use App\Models\State;
use App\Models\City;
use App\Models\PaymentMethod;
use App\Models\Currency;
use App\Models\PaymentImage;
use App\Models\ProductVariant;
use App\Models\SalesOrder;
use App\Models\SalesOrderDetail;
use App\Models\Payment;
use App\Models\DollarRate;
use App\Models\EuroRate;
use App\Models\Tax;
use App\Support\ImageStorage;
use App\Services\GeminiImageService;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;


class TenantController extends Controller
{
    public function generateTenantImage(Request $request, GeminiImageService $imageService)
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

        $referenceBase64 = null;
        $referenceMime = 'image/png';
        if ($request->hasFile('reference_image')) {
            $referenceFile = $request->file('reference_image');
            $referenceBase64 = base64_encode(file_get_contents($referenceFile->getRealPath()));
            $referenceMime = $referenceFile->getMimeType() ?: 'image/png';
        } elseif (!empty($validated['reference_image_data'])) {
            $referenceBase64 = preg_replace('/^data:image\/[a-zA-Z0-9.+-]+;base64,/', '', (string) $validated['reference_image_data']);
            $referenceMime = $validated['reference_image_mime'] ?? 'image/png';
        }

        $result = $imageService->generateImage([
            'prompt' => $fullPrompt,
            'image_operation' => $operation,
            'reference_image_data' => $referenceBase64,
            'reference_image_mime' => $referenceMime,
            'aspect_ratio' => (string) ($validated['background_ratio'] ?? '1:1'),
            'sample_count' => 1,
        ]);

        if (($result['success'] ?? false) === true) {
            return response()->json([
                'success' => true,
                'data' => $result['data'],
                'mime_type' => $result['mime_type'] ?? 'image/png',
                'provider' => $result['provider'] ?? null,
                'tried_models' => $result['tried_models'] ?? null,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => $result['message'] ?? 'No se pudo generar la imagen con Gemini.',
            'error' => $result['error'] ?? null,
            'tried_models' => $result['tried_models'] ?? null,
        ], (int) ($result['status'] ?? 422));
    }

    public function index()
    {
        // Trae todos los tenants con todos sus planes asociados
        $tenants = Tenant::with(['tenantPlanPayments.plan', 'tenantPlanPayments.reviewer', 'users.role'])->get();

        $tenantsWithDueData = $tenants->map(function (Tenant $tenant) {
            $latestPaid = $this->getTenantLatestPaidPlanPayment($tenant);
            $daysRemaining = $this->calculatePlanDaysRemaining($latestPaid);
            $tenant->latest_paid_plan_payment = $latestPaid;
            $tenant->plan_days_remaining = $daysRemaining;

            return $tenant;
        });

        $nearDueTenants = $tenantsWithDueData
            ->filter(fn (Tenant $tenant) => is_int($tenant->plan_days_remaining) && $tenant->plan_days_remaining >= 0 && $tenant->plan_days_remaining <= 7)
            ->sortBy('plan_days_remaining')
            ->values();

        $overdueTenants = $tenantsWithDueData
            ->filter(fn (Tenant $tenant) => is_int($tenant->plan_days_remaining) && $tenant->plan_days_remaining < 0)
            ->sortBy('plan_days_remaining')
            ->values();


        // O solo el plan activo de cada tenant
        // $tenants = Tenant::with(['activePlanPayment.plan'])->get();

        $plans = Plan::all();

        return view('tenant', compact('tenants', 'plans', 'nearDueTenants', 'overdueTenants'));
    }

    public function paymentsIndex()
    {
        $tenants = Tenant::with(['tenantPlanPayments.plan', 'tenantPlanPayments.reviewer', 'users.role'])->get();

        $payments = TenantPlanPayment::with(['tenant', 'plan', 'reviewer'])
            ->orderByDesc('created_at')
            ->get();

        $pendingPayments = $payments->where('status', 'pending')->values();

        $tenantsWithDueData = $tenants->map(function (Tenant $tenant) {
            $latestPaid = $this->getTenantLatestPaidPlanPayment($tenant);
            $daysRemaining = $this->calculatePlanDaysRemaining($latestPaid);
            $tenant->latest_paid_plan_payment = $latestPaid;
            $tenant->plan_days_remaining = $daysRemaining;

            return $tenant;
        });

        $nearDueTenants = $tenantsWithDueData
            ->filter(fn (Tenant $tenant) => is_int($tenant->plan_days_remaining) && $tenant->plan_days_remaining >= 0 && $tenant->plan_days_remaining <= 7)
            ->sortBy('plan_days_remaining')
            ->values();

        $overdueTenants = $tenantsWithDueData
            ->filter(fn (Tenant $tenant) => is_int($tenant->plan_days_remaining) && $tenant->plan_days_remaining < 0)
            ->sortBy('plan_days_remaining')
            ->values();

        return view('tenantPayments', compact('payments', 'pendingPayments', 'nearDueTenants', 'overdueTenants'));
    }
    
    public function getTenant()
    {
        $user = auth()->user();
        $tenant = Tenant::with(['users.role', 'tenantPlanPayments.plan'])
            ->where('id', $user->tenant_id)
            ->first();
        $currentPlanPayment = $this->getTenantLatestPaidPlanPayment($tenant);
        $isBasicPlanTenant = $this->isBasicPlanTenant($tenant);
        $isFreePlanTenant = (float) ($currentPlanPayment?->plan?->price ?? -1) <= 0;
        $assignableRoleKeys = $user?->assignableStoreRoleKeys() ?? [];
        $roles = Role::whereNotIn('name', ['owner', 'user', 'super_user'])
            ->get()
            ->filter(function (Role $role) use ($assignableRoleKeys) {
                return in_array(User::canonicalRoleName($role->name), $assignableRoleKeys, true);
            })
            ->values();

        $ownerRoleIds = $this->resolveOwnerRoleIds();
        $adminRoleIds = $this->resolveAdminRoleIds();
        $ownerCount = $tenant->users->whereIn('role_id', $ownerRoleIds)->count();
        $adminCount = $tenant->users->whereIn('role_id', $adminRoleIds)->count();

        if ($isBasicPlanTenant) {
            $roles = $roles->filter(function (Role $role) use ($adminRoleIds, $adminCount) {
                if (!in_array((int) $role->id, $adminRoleIds, true)) {
                    return false;
                }

                return $adminCount < 1;
            })->values();
        }
        $plans = Plan::query()->where('status', 0)->orderBy('price')->get();
        $currentPlanCutoffDate = $this->resolvePaymentCutoffDate($currentPlanPayment);
        $currentPlanDaysRemaining = $this->calculatePlanDaysRemaining($currentPlanPayment);
        $pendingPlanPayment = $tenant->tenantPlanPayments()
            ->with('plan')
            ->where('status', 'pending')
            ->latest('created_at')
            ->first();
        $roleDefinitions = User::storeRoleDefinitions();
        $countries = Country::all();
        $states = State::all();
        $cities = City::all();
        return view('tenantStore', compact(
            'tenant',
            'roles',
            'countries',
            'states',
            'cities',
            'roleDefinitions',
            'assignableRoleKeys',
            'plans',
            'currentPlanPayment',
            'currentPlanCutoffDate',
            'currentPlanDaysRemaining',
            'pendingPlanPayment',
            'isFreePlanTenant',
            'isBasicPlanTenant',
            'ownerCount',
            'adminCount'
        ));
    }

    public function submitPlanPaymentRequest(Request $request)
    {
        $user = auth()->user();
        $tenant = Tenant::query()->findOrFail($user->tenant_id);

        $validated = $request->validate([
            'plan_id' => 'required|exists:plans,id',
            'payment_reference' => 'nullable|string|max:255',
            'payment_proof' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:4096',
            'notes' => 'nullable|string|max:1000',
        ]);

        $plan = Plan::query()->findOrFail((int) $validated['plan_id']);
        $hasPendingRequest = $tenant->tenantPlanPayments()->where('status', 'pending')->exists();

        if ($hasPendingRequest) {
            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Ya tienes una solicitud de pago pendiente por revisar.',
                ], 422);
            }

            return back()->with('warning', 'Ya tienes una solicitud de pago pendiente por revisar.');
        }

        $isFreePlan = $this->isFreePlan($plan);

        if (!$isFreePlan && empty(trim((string) ($validated['payment_reference'] ?? '')))) {
            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'errors' => [
                        'payment_reference' => ['Debes ingresar una referencia de pago para planes de pago.'],
                    ],
                ], 422);
            }

            return back()->withErrors([
                'payment_reference' => 'Debes ingresar una referencia de pago para planes de pago.',
            ])->withInput();
        }

        if (!$isFreePlan && !$request->hasFile('payment_proof')) {
            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'errors' => [
                        'payment_proof' => ['Debes subir un comprobante de pago para planes de pago.'],
                    ],
                ], 422);
            }

            return back()->withErrors([
                'payment_proof' => 'Debes subir un comprobante de pago para planes de pago.',
            ])->withInput();
        }

        if (!$isFreePlan && !ImageStorage::usesGoogleDrive()) {
            $message = 'No se pudo registrar el pago porque Google Drive no está configurado para comprobantes. Configura Drive e intenta nuevamente.';

            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 422);
            }

            return back()->withErrors([
                'payment_proof' => $message,
            ])->withInput();
        }

        $paymentProofPath = null;
        if ($request->hasFile('payment_proof')) {
            $paymentProofPath = ImageStorage::storeUploadedImageAsWebp($request->file('payment_proof'), 'tenant/plan-payments');

            if (!ImageStorage::isGooglePath($paymentProofPath)) {
                ImageStorage::delete($paymentProofPath);

                $message = 'El comprobante no se subió correctamente a Google Drive. Intenta nuevamente.';

                if ($request->expectsJson() || $request->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $message,
                    ], 422);
                }

                return back()->withErrors([
                    'payment_proof' => $message,
                ])->withInput();
            }
        }

        $status = $isFreePlan ? 'paid' : 'pending';
        $paidAt = $isFreePlan ? now() : null;
        $expiresAt = $isFreePlan ? $this->resolvePlanExpirationDate($tenant, $plan, now()) : null;

        $paymentData = [
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'amount' => (float) ($plan->price ?? 0),
            'status' => $status,
            'paid_at' => $paidAt,
            'payment_reference' => $validated['payment_reference'] ?? null,
            'payment_proof' => $paymentProofPath,
            'review_notes' => $validated['notes'] ?? null,
        ];

        if (Schema::hasColumn('tenant_plan_payments', 'expires_at')) {
            $paymentData['expires_at'] = $expiresAt;
        }

        TenantPlanPayment::create($paymentData);

        if ($isFreePlan) {
            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => 'Plan gratuito activado correctamente.',
                ]);
            }

            return back()->with('success', 'Plan gratuito activado correctamente.');
        }

        if ($request->expectsJson() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Solicitud de pago enviada correctamente. Queda pendiente de aprobación.',
            ]);
        }

        return back()->with('success', 'Solicitud de pago enviada correctamente. Queda pendiente de aprobación.');
    }

    public function approvePlanPayment(Request $request, Tenant $tenant, TenantPlanPayment $payment)
    {
        if ((int) $payment->tenant_id !== (int) $tenant->id) {
            abort(404);
        }

        if ($payment->status !== 'pending') {
            return back()->with('warning', 'Solo se pueden aprobar pagos pendientes.');
        }

        $validated = $request->validate([
            'review_notes' => 'nullable|string|max:1000',
            'expires_at' => 'nullable|date',
        ]);

        $plan = Plan::query()->findOrFail((int) $payment->plan_id);
        $approvedAt = now();
        $expiresAt = !empty($validated['expires_at'])
            ? Carbon::parse($validated['expires_at'])
            : $this->resolvePlanExpirationDate($tenant, $plan, $approvedAt);

        $payment->status = 'paid';
        $payment->paid_at = $approvedAt;
        $payment->review_notes = $validated['review_notes'] ?? $payment->review_notes;

        if (Schema::hasColumn('tenant_plan_payments', 'expires_at')) {
            $payment->expires_at = $expiresAt;
        }

        if (Schema::hasColumn('tenant_plan_payments', 'reviewed_at')) {
            $payment->reviewed_at = now();
        }

        if (Schema::hasColumn('tenant_plan_payments', 'reviewed_by')) {
            $payment->reviewed_by = auth()->id();
        }

        $payment->save();

        return back()->with('success', 'Pago de plan aprobado correctamente.');
    }

    public function updatePlanPaymentCutoffDate(Request $request, Tenant $tenant, TenantPlanPayment $payment)
    {
        if ((int) $payment->tenant_id !== (int) $tenant->id) {
            abort(404);
        }

        if (!Schema::hasColumn('tenant_plan_payments', 'expires_at')) {
            return back()->with('warning', 'No existe la columna de fecha de corte para este entorno.');
        }

        if ($payment->status !== 'paid') {
            return back()->with('warning', 'Solo puedes editar la fecha de corte de pagos aprobados.');
        }

        $validated = $request->validate([
            'expires_at' => 'required|date',
        ]);

        $payment->expires_at = Carbon::parse($validated['expires_at']);

        if (Schema::hasColumn('tenant_plan_payments', 'reviewed_at')) {
            $payment->reviewed_at = now();
        }

        if (Schema::hasColumn('tenant_plan_payments', 'reviewed_by')) {
            $payment->reviewed_by = auth()->id();
        }

        $payment->save();

        return back()->with('success', 'Fecha de corte actualizada correctamente.');
    }

    public function rejectPlanPayment(Request $request, Tenant $tenant, TenantPlanPayment $payment)
    {
        if ((int) $payment->tenant_id !== (int) $tenant->id) {
            abort(404);
        }

        if ($payment->status !== 'pending') {
            return back()->with('warning', 'Solo se pueden rechazar pagos pendientes.');
        }

        $request->validate([
            'review_notes' => 'nullable|string|max:1000',
        ]);

        $payment->status = 'failed';
        $payment->review_notes = $request->input('review_notes') ?: 'Pago rechazado por administración.';

        if (Schema::hasColumn('tenant_plan_payments', 'reviewed_at')) {
            $payment->reviewed_at = now();
        }

        if (Schema::hasColumn('tenant_plan_payments', 'reviewed_by')) {
            $payment->reviewed_by = auth()->id();
        }

        $payment->save();

        return back()->with('warning', 'Pago de plan rechazado correctamente.');
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
        $this->abortIfTenantInactiveForPublic($tenant);

        // Cargar categorías y productos del tenant
        $categories = Category::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->get();
        $productItems = Product::where('tenant_id', $tenant->id)
            ->with('images')
            ->limit(9)
            ->get();

        $materialPackages = MaterialPackage::with(['items', 'items.variant', 'items.variant.product', 'items.variant.product.images'])
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->limit(6)
            ->get();

        $cartEnabled = $this->tenantHasProPlan($tenant);
        $cartPlanName = $this->getTenantCurrentPlanName($tenant);
        $baseCurrencyCode = $this->resolveTenantBaseCurrencyCode($tenant);
        $baseCurrencySymbol = $this->resolveCurrencySymbol($baseCurrencyCode);

        return view('ecommerceInf', compact('tenant', 'categories', 'productItems', 'materialPackages', 'cartEnabled', 'cartPlanName', 'baseCurrencyCode', 'baseCurrencySymbol'));
    }

    public function store(Request $request)
    {
        DB::raw("SET @user_id = " . auth()->id());

        $validated = $request->validate([
            'name'            => 'required|string|max:255',
            'slug'            => 'required|string|max:255',
            'email'           => 'required|email|unique:tenants,email',
            'logo'            => 'nullable|image|mimes:png,svg,webp|max:2048',
            'color_primary'   => 'required|string|max:7',
            'color_secondary' => 'required|string|max:7',
            'color_accent'    => 'required|string|max:7',
            'business_type'   => ['required', 'string', Rule::in(['tienda', 'servicio', 'Tienda', 'Servicio'])],
            'economic_activity' => 'required|string|max:150|regex:/.*\S.*/',
            'country'         => 'nullable|string|max:255',
            'state'           => 'nullable|string|max:255',
            'city'            => 'nullable|string|max:255',
            'phone_code'      => 'nullable|string|max:5',
            'phone_number'    => 'nullable|string|max:20',
            'working_days'    => 'nullable|array',
            'working_days.*'  => ['string', Rule::in(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'])],
            'opening_time'    => 'nullable|date_format:H:i',
            'closing_time'    => 'nullable|date_format:H:i',
            'users'           => 'nullable|array',
            'users.*.name'    => 'nullable|string|max:255',
            'users.*.email'   => 'nullable|email|unique:users,email',
            'users.*.password'=> 'nullable|string|min:8',
            'plan_id'         => 'required|exists:plans,id',
        ]);

        $this->assertEconomicActivityAllowed(
            $validated['business_type'] ?? null,
            $validated['economic_activity'] ?? null
        );

        $this->assertLocationHierarchy(
            $validated['country'] ?? null,
            $validated['state'] ?? null,
            $validated['city'] ?? null
        );

        $normalizedSlug = Str::slug((string) $validated['slug']);

        if ($normalizedSlug === '') {
            return back()
                ->withErrors(['slug' => 'El slug ingresado no es válido.'])
                ->withInput();
        }

        if (Tenant::where('slug', $normalizedSlug)->exists()) {
            return back()
                ->withErrors(['slug' => 'El slug ingresado ya está en uso.'])
                ->withInput();
        }

        DB::beginTransaction();

        try {
            // 📂 Subir logo si existe
            $logoPath = null;
            if ($request->hasFile('logo')) {
                $logoPath = ImageStorage::storeUploadedImageAsWebp($request->file('logo'), 'tenants/logos');
            }

            $tenantData = [
                'name'            => $validated['name'],
                'slug'            => $normalizedSlug,
                'email'           => $validated['email'],
                'logo'            => $logoPath,
                'color_primary'   => $validated['color_primary'],
                'color_secondary' => $validated['color_secondary'],
                'color_accent'    => $validated['color_accent'],
                'business_type'   => $this->normalizeBusinessType($validated['business_type']),
                'economic_activity' => $this->normalizeEconomicActivity($validated['economic_activity'], $validated['business_type']),
                'country'         => $validated['country'] ?? null,
                'state'           => $validated['state'] ?? null,
                'city'            => $validated['city'] ?? null,
                'phone_code'      => $validated['phone_code'] ?? null,
                'phone_number'    => $validated['phone_number'] ?? null,
                'working_days'    => $this->normalizeWorkingDays($validated['working_days'] ?? null),
                'opening_time'    => $validated['opening_time'] ?? null,
                'closing_time'    => $validated['closing_time'] ?? null,
            ];

            $tenant = Tenant::create($this->filterDataByExistingColumns('tenants', $tenantData));

            // 💳 Crear relación TenantPayment
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

            $roles = Role::whereIn('name', ['owner', 'admin', 'vendor'])
                ->get()
                ->keyBy(fn (Role $role) => Str::lower((string) $role->name));

            // 👥 Crear usuarios enviados en el formulario
            if (!empty($validated['users']) && is_array($validated['users'])) {
                foreach ($validated['users'] as $roleName => $userData) {
                    if (empty($userData['email'])) {
                        continue;
                    }

                    $normalizedRoleName = Str::lower((string) $roleName);
                    $role = $roles->get($normalizedRoleName);

                    if (!$role && $normalizedRoleName === 'owner') {
                        $role = $roles->get('admin');
                    }

                    $userRecord = [
                        'name'      => $userData['name'] ?? ucfirst($normalizedRoleName),
                        'email'     => $userData['email'],
                        'password'  => Hash::make($userData['password'] ?? 'password123'),
                        'tenant_id' => $tenant->id,
                        'role_id'   => $role?->id,
                        'is_active' => 1,
                    ];

                    $user = User::create($this->filterDataByExistingColumns('users', $userRecord));

                    if ($normalizedRoleName === 'owner' && Schema::hasColumn('tenants', 'owner_id')) {
                        $tenant->owner_id = $user->id;
                    }
                }
            }

            if ($tenant->isDirty()) {
                $tenant->save();
            }

            DB::commit();

            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json([
                    'message' => 'Creado Exitosamente',
                    'tenant'  => $tenant,
                ]);
            }

            return redirect()
                ->route('tenant.index')
                ->with('status', 'Tienda creada correctamente.');
        } catch (QueryException $e) {
            DB::rollBack();

            Log::error('Error SQL al crear tienda desde admin', [
                'slug_input' => $validated['slug'] ?? null,
                'slug_normalized' => $normalizedSlug,
                'email' => $validated['email'] ?? null,
                'error' => $e->getMessage(),
            ]);

            $message = $this->resolveTenantCreationDbErrorMessage($e);

            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json(['message' => $message], 422);
            }

            return back()->withErrors(['create_tenant' => $message])->withInput();
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Error al crear tienda desde admin', [
                'slug_input' => $validated['slug'] ?? null,
                'slug_normalized' => $normalizedSlug,
                'email' => $validated['email'] ?? null,
                'error' => $e->getMessage(),
            ]);

            if ($request->expectsJson() || $request->wantsJson()) {
                return response()->json(['message' => 'No se pudo crear la tienda. Intenta nuevamente.'], 500);
            }

            return back()->withErrors(['create_tenant' => 'No se pudo crear la tienda. Intenta nuevamente.'])->withInput();
        }
    }
    public function storePublic(Request $request)
    {
        $validated = $request->validate([
            'name'                  => 'required|string|max:255',
            'slug'                  => 'required|string|max:255',
            'email'                 => 'required|email|unique:tenants,email',
            'logo'                  => 'nullable|image|mimes:png,svg,webp|max:2048',
            'background_image'      => 'nullable|image|mimes:png,jpg,jpeg,webp|max:4096',
            'color_primary'         => 'required|string|max:7',
            'color_secondary'       => 'required|string|max:7',
            'color_accent'          => 'required|string|max:7',
            'business_type'         => ['required', 'string', Rule::in(['tienda', 'servicio', 'Tienda', 'Servicio'])],
            'economic_activity'     => 'required|string|max:150|regex:/.*\S.*/',
            'country'               => 'required|exists:countries,id',
            'state'                 => 'required|exists:states,id',
            'city'                  => 'required|exists:cities,id',
            'phone_code'            => 'required|string|max:5',
            'phone_number'          => 'required|string|max:20',
            'working_days'          => 'nullable|array',
            'working_days.*'        => ['string', Rule::in(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'])],
            'opening_time'          => 'nullable|date_format:H:i',
            'closing_time'          => 'nullable|date_format:H:i',
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

        $this->assertEconomicActivityAllowed(
            $validated['business_type'] ?? null,
            $validated['economic_activity'] ?? null
        );

        $normalizedSlug = Str::slug((string) $validated['slug']);

        if ($normalizedSlug === '') {
            return back()
                ->withErrors(['slug' => 'El slug ingresado no es válido.'])
                ->withInput();
        }

        if (Tenant::where('slug', $normalizedSlug)->exists()) {
            return back()
                ->withErrors(['slug' => 'El slug ingresado ya está en uso.'])
                ->withInput();
        }

        DB::beginTransaction();

        try {
            $logoPath = null;
            if ($request->hasFile('logo')) {
                $logoPath = ImageStorage::storeUploadedImageAsWebp($request->file('logo'), 'tenants/logos');
            }

            $backgroundPath = null;
            if ($request->hasFile('background_image')) {
                $backgroundPath = ImageStorage::storeUploadedImageAsWebp($request->file('background_image'), 'tenants/backgrounds');
            }

            $tenantData = [
                'name'            => $validated['name'],
                'slug'            => $normalizedSlug,
                'email'           => $validated['email'],
                'logo'            => $logoPath,
                'color_primary'   => $validated['color_primary'],
                'color_secondary' => $validated['color_secondary'],
                'color_accent'    => $validated['color_accent'],
                'business_type'   => $this->normalizeBusinessType($validated['business_type']),
                'economic_activity' => $this->normalizeEconomicActivity($validated['economic_activity'], $validated['business_type']),
                'country'         => $validated['country'],
                'state'           => $validated['state'],
                'city'            => $validated['city'],
                'phone_code'      => $validated['phone_code'],
                'phone_number'    => $validated['phone_number'],
                'working_days'    => $this->normalizeWorkingDays($validated['working_days'] ?? null),
                'opening_time'    => $validated['opening_time'] ?? null,
                'closing_time'    => $validated['closing_time'] ?? null,
                'slogan'          => $validated['slogan'] ?? null,
                'description'     => $validated['description'] ?? null,
                'address'         => $validated['address'] ?? null,
                'latitude'        => $validated['latitude'] ?? null,
                'longitude'       => $validated['longitude'] ?? null,
                'background_image'=> $backgroundPath,
            ];

            $tenant = Tenant::create($this->filterDataByExistingColumns('tenants', $tenantData));

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

            $ownerData = [
                'name'        => $validated['users']['owner']['name'],
                'email'       => $validated['users']['owner']['email'],
                'phone_number'=> $validated['users']['owner']['phone_number'] ?? null,
                'dni'         => $validated['users']['owner']['dni'] ?? null,
                'password'    => Hash::make($validated['users']['owner']['password']),
                'tenant_id'   => $tenant->id,
                'role_id'     => $ownerRole?->id,
                'is_active'   => 1,
            ];

            $owner = User::create($this->filterDataByExistingColumns('users', $ownerData));

            if (Schema::hasColumn('tenants', 'owner_id')) {
                $tenant->owner_id = $owner->id;
                $tenant->save();
            }

            DB::commit();

            return redirect()
                ->route('login')
                ->with('status', 'Tu tienda fue creada exitosamente. Ahora inicia sesión con tu cuenta.')
                ->withInput([
                    'email' => $validated['users']['owner']['email'],
                ]);
        } catch (QueryException $e) {
            DB::rollBack();

            Log::error('Error SQL al crear tienda pública', [
                'slug_input' => $validated['slug'] ?? null,
                'slug_normalized' => $normalizedSlug,
                'email' => $validated['email'] ?? null,
                'error' => $e->getMessage(),
            ]);

            return back()
                ->withErrors(['create_tenant' => $this->resolveTenantCreationDbErrorMessage($e)])
                ->withInput();
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Error al crear tienda pública', [
                'slug_input' => $validated['slug'] ?? null,
                'slug_normalized' => $normalizedSlug,
                'email' => $validated['email'] ?? null,
                'error' => $e->getMessage(),
            ]);

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
            'business_type'   => ['sometimes', 'required', 'string', Rule::in(['tienda', 'servicio', 'Tienda', 'Servicio'])],
            'economic_activity' => 'sometimes|required|string|max:150|regex:/.*\S.*/',
            'country'         => 'nullable|string|max:255',
            'state'           => 'nullable|string|max:255',
            'city'            => 'nullable|string|max:255',
            'phone_code'      => 'nullable|string|max:5',
            'phone_number'    => 'nullable|string|max:20',
            'working_days'    => 'nullable|array',
            'working_days.*'  => ['string', Rule::in(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'])],
            'opening_time'    => 'nullable|date_format:H:i',
            'closing_time'    => 'nullable|date_format:H:i',
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
            'electronic_invoicing_enabled' => 'nullable|boolean',
            'special_taxpayer' => 'nullable|boolean',
            'printer_tax_change_enabled' => 'nullable|boolean',
            'printer_tax_change_reference' => 'nullable|string|max:255',
            'restrict_delivery_city_to_tenant' => 'nullable|boolean',
        ]);

        if (array_key_exists('economic_activity', $validated)) {
            $this->assertEconomicActivityAllowed(
                $validated['business_type'] ?? $tenant->business_type,
                $validated['economic_activity'] ?? null
            );
        }

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
            'business_type' => array_key_exists('business_type', $validated)
                ? $this->normalizeBusinessType($validated['business_type'])
                : $tenant->business_type,
            'economic_activity' => array_key_exists('economic_activity', $validated)
                ? $this->normalizeEconomicActivity(
                    $validated['economic_activity'],
                    $validated['business_type'] ?? $tenant->business_type
                )
                : $tenant->economic_activity,
            'country' => $validated['country'] ?? $tenant->country,
            'state' => $validated['state'] ?? $tenant->state,
            'city' => $validated['city'] ?? $tenant->city,
            'phone_code' => $validated['phone_code'] ?? $tenant->phone_code,
            'phone_number' => $validated['phone_number'] ?? $tenant->phone_number,
            'working_days' => array_key_exists('working_days', $validated)
                ? $this->normalizeWorkingDays($validated['working_days'] ?? null)
                : $tenant->working_days,
            'opening_time' => $validated['opening_time'] ?? $tenant->opening_time,
            'closing_time' => $validated['closing_time'] ?? $tenant->closing_time,
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
            'electronic_invoicing_enabled' => $validated['electronic_invoicing_enabled'] ?? $tenant->electronic_invoicing_enabled,
            'special_taxpayer' => $validated['special_taxpayer'] ?? $tenant->special_taxpayer,
            'printer_tax_change_enabled' => $validated['printer_tax_change_enabled'] ?? $tenant->printer_tax_change_enabled,
            'printer_tax_change_reference' => $validated['printer_tax_change_reference'] ?? $tenant->printer_tax_change_reference,
            'restrict_delivery_city_to_tenant' => $validated['restrict_delivery_city_to_tenant'] ?? $tenant->restrict_delivery_city_to_tenant,
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
        $latestPaidPlan = $this->getTenantLatestPaidPlanPayment($tenant);
        $isFreePlanTenant = (float) ($latestPaidPlan?->plan?->price ?? -1) <= 0;

        try {
            $assignableRoleKeys = $user?->assignableStoreRoleKeys() ?? [];
            $assignableRoleIds = Role::query()
                ->get()
                ->filter(function (Role $role) use ($assignableRoleKeys) {
                    return in_array(User::canonicalRoleName($role->name), $assignableRoleKeys, true);
                })
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->values()
                ->all();

            $validated = $request->validate([
                'name'            => 'nullable|string|max:255',
                'slug'            => 'nullable|string|max:255|unique:tenants,slug,' . $tenant->id,
                'slogan'          => 'nullable|string|max:255',
                'description'     => 'nullable|string',
                'business_type'   => ['required', 'string', Rule::in(['tienda', 'servicio', 'Tienda', 'Servicio'])],
                'economic_activity' => 'required|string|max:150|regex:/.*\S.*/',
                'logo'            => 'nullable|image|mimes:png,svg,webp|max:2048',
                'color_primary'   => 'nullable|string|max:7',
                'color_secondary' => 'nullable|string|max:7',
                'color_accent'    => 'nullable|string|max:7',
                'country'         => 'nullable|exists:countries,id',
                'state'           => 'nullable|exists:states,id',
                'city'            => 'nullable|exists:cities,id',
                'phone_code'      => 'nullable|string|max:5',
                'phone_number'    => 'nullable|string|max:20',
                'working_days'    => 'nullable|array',
                'working_days.*'  => ['string', Rule::in(['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'])],
                'opening_time'    => 'nullable|date_format:H:i',
                'closing_time'    => 'nullable|date_format:H:i',
                'address'         => 'nullable|string|max:255',
                'latitude'        => 'nullable|numeric',
                'longitude'       => 'nullable|numeric',
                'background_image'       => 'nullable|image|mimes:png,jpg,jpeg,webp|max:2048',
                'tiktok'         => 'nullable|string|max:255',
                'instagram'         => 'nullable|string|max:255',
                'facebook'         => 'nullable|string|max:255',
                'special_taxpayer' => 'nullable|boolean',
                'printer_tax_change_enabled' => 'nullable|boolean',
                'printer_tax_change_reference' => 'nullable|string|max:255',
                'restrict_delivery_city_to_tenant' => 'nullable|boolean',
            ]);

            $this->assertEconomicActivityAllowed(
                $validated['business_type'] ?? null,
                $validated['economic_activity'] ?? null
            );

            $this->assertLocationHierarchy(
                $validated['country'] ?? null,
                $validated['state'] ?? null,
                $validated['city'] ?? null
            );

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
                if ($isFreePlanTenant) {
                    return response()->json([
                        'success' => false,
                        'message' => 'El plan Free no permite crear usuarios adicionales.',
                    ], 403);
                }

                $newUserRoleId = (int) ($request->input('new_user.role_id') ?? 0);
                if ($this->isBasicPlanTenant($tenant) && $newUserRoleId > 0) {
                    $ownerRoleIds = $this->resolveOwnerRoleIds();
                    $adminRoleIds = $this->resolveAdminRoleIds();
                    $selectedRoleIsOwner = in_array($newUserRoleId, $ownerRoleIds, true);
                    $selectedRoleIsAdmin = in_array($newUserRoleId, $adminRoleIds, true);

                    if (!$selectedRoleIsAdmin) {
                        return response()->json([
                            'success' => false,
                            'message' => 'En plan Básico solo se permite crear un usuario administrador.',
                        ], 403);
                    }

                    $currentOwnerCount = $tenant->users()
                        ->whereIn('role_id', $ownerRoleIds)
                        ->count();

                    if ($currentOwnerCount > 1) {
                        return response()->json([
                            'success' => false,
                            'message' => 'La tienda tiene más de un owner. Debes regularizarlo antes de crear usuarios.',
                        ], 403);
                    }

                    if ($selectedRoleIsOwner) {
                        return response()->json([
                            'success' => false,
                            'message' => 'El plan Básico no permite crear más usuarios owner.',
                        ], 403);
                    }

                    if ($selectedRoleIsAdmin) {
                        $currentAdminCount = $tenant->users()
                            ->whereIn('role_id', $adminRoleIds)
                            ->count();

                        if ($currentAdminCount >= 1) {
                            return response()->json([
                                'success' => false,
                                'message' => 'El plan Básico solo permite un usuario con rol administrador.',
                            ], 403);
                        }
                    }
                }

                if (!$user || !$user->canAssignStoreRoles() || empty($assignableRoleIds)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'No tienes permisos para asignar roles a nuevos usuarios.',
                    ], 403);
                }

                $newUserValidated = $request->validate([
                    'new_user.name'         => 'required|string|max:255',
                    'new_user.email'        => 'required|email|unique:users,email',
                    'new_user.password'     => 'required|string|min:8',
                    'new_user.role_id'      => ['required', 'integer', Rule::in($assignableRoleIds)],
                    'new_user.phone_number' => 'required|string|max:20',
                    'new_user.dni'          => 'required|string|max:50',
                ]);
            }

            // Manejar subida de logo
            if ($request->hasFile('logo')) {
                $logoPath = ImageStorage::storeUploadedImageAsWebp($request->file('logo'), 'tenants/logos');
                $tenant->logo = $logoPath;
            }
            // Manejar imagen de fondo
            if ($request->hasFile('background_image')) {

                // Eliminar imagen anterior si existe
                if ($tenant->background_image && ImageStorage::exists($tenant->background_image)) {
                    ImageStorage::delete($tenant->background_image);
                }

                // Guardar nueva imagen
                $backgroundPath = ImageStorage::storeUploadedImageAsWebp($request->file('background_image'), 'tenants/backgrounds');

                $tenant->background_image = $backgroundPath;
            }
            // Actualizar campos
            $tenant->update([
                'name'            => $validated['name'] ?? $tenant->name,
                'slug'            => isset($validated['slug']) ? Str::slug($validated['slug']) : $tenant->slug,
                'slogan'          => $validated['slogan'] ?? $tenant->slogan,
                'description'     => $validated['description'] ?? $tenant->description,
                'business_type'   => $this->normalizeBusinessType($validated['business_type']),
                'economic_activity'=> $this->normalizeEconomicActivity($validated['economic_activity'], $validated['business_type']),
                'color_primary'   => $validated['color_primary'] ?? $tenant->color_primary,
                'color_secondary' => $validated['color_secondary'] ?? $tenant->color_secondary,
                'color_accent'    => $validated['color_accent'] ?? $tenant->color_accent,
                'country'         => $validated['country'] ?? $tenant->country,
                'state'           => $validated['state'] ?? $tenant->state,
                'city'            => $validated['city'] ?? $tenant->city,
                'phone_code'      => $validated['phone_code'] ?? $tenant->phone_code,
                'phone_number'    => $validated['phone_number'] ?? $tenant->phone_number,
                'working_days'    => array_key_exists('working_days', $validated)
                    ? $this->normalizeWorkingDays($validated['working_days'] ?? null)
                    : $tenant->working_days,
                'opening_time'    => $validated['opening_time'] ?? $tenant->opening_time,
                'closing_time'    => $validated['closing_time'] ?? $tenant->closing_time,
                'address'         => $validated['address'] ?? $tenant->address,
                'latitude'        => $validated['latitude'] ?? $tenant->latitude,
                'longitude'       => $validated['longitude'] ?? $tenant->longitude,
                'tiktok'          => $validated['tiktok'] ?? $tenant->tiktok,
                'instagram'      => $validated['instagram'] ?? $tenant->instagram,
                'facebook'       => $validated['facebook'] ?? $tenant->facebook,
                'special_taxpayer' => $validated['special_taxpayer'] ?? $tenant->special_taxpayer,
                'printer_tax_change_enabled' => $validated['printer_tax_change_enabled'] ?? $tenant->printer_tax_change_enabled,
                'printer_tax_change_reference' => $validated['printer_tax_change_reference'] ?? $tenant->printer_tax_change_reference,
                'restrict_delivery_city_to_tenant' => $validated['restrict_delivery_city_to_tenant'] ?? $tenant->restrict_delivery_city_to_tenant,
                'background_image'=> $tenant->background_image, // 👈 clave
            ]);

            if ($shouldCreateNewUser) {
                User::create([
                    'name'        => $newUserValidated['new_user']['name'],
                    'email'       => $newUserValidated['new_user']['email'],
                    'password'    => Hash::make($newUserValidated['new_user']['password']),
                    'tenant_id'   => $tenant->id,
                    'role_id'     => $newUserValidated['new_user']['role_id'],
                    'phone_number'=> $newUserValidated['new_user']['phone_number'],
                    'dni'         => $newUserValidated['new_user']['dni'],
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
        $this->abortIfTenantInactiveForPublic($tenant);

        // Asegurarse que la categoría pertenece al tenant
        // if ($category->tenant_id !== $tenant->id) {
        //     abort(404);
        // }

        $categories = Category::where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->get();

        $products = Product::where('tenant_id', $tenant->id)
            // ->where('status', 1)
            ->with('images')
            ->get();

        $materialPackages = MaterialPackage::with(['items', 'items.variant', 'items.variant.product', 'items.variant.product.images'])
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->orderBy('created_at', 'desc')
            ->get();

        $cartEnabled = $this->tenantHasProPlan($tenant);
        $cartPlanName = $this->getTenantCurrentPlanName($tenant);
        $baseCurrencyCode = $this->resolveTenantBaseCurrencyCode($tenant);
        $baseCurrencySymbol = $this->resolveCurrencySymbol($baseCurrencyCode);

        return view('ecommerceCategory', compact(
            'tenant',
            'categories',
            'products',
            'materialPackages',
            'cartEnabled',
            'cartPlanName',
            'baseCurrencyCode',
            'baseCurrencySymbol'
        ));
    }
    public function publicTenantProduct(Tenant $tenant, Product $product)
    {
        $this->abortIfTenantInactiveForPublic($tenant);

        // $tenant y $product son inyectados automáticamente por el model binding de Laravel
        // gracias a la ruta '/{tenant:slug}/{product:slug}'
        
        if ((int) $product->tenant_id !== (int) $tenant->id) {
            abort(404);
        }

        // Cargar cualquier relación necesaria (ej: category, variants, images)
        $product->load(['category', 'variants.images', 'images']);

        $cartEnabled = $this->tenantHasProPlan($tenant);
        $cartPlanName = $this->getTenantCurrentPlanName($tenant);
        $baseCurrencyCode = $this->resolveTenantBaseCurrencyCode($tenant);
        $baseCurrencySymbol = $this->resolveCurrencySymbol($baseCurrencyCode);

        return view('ecommerceProduct', compact('tenant', 'product', 'cartEnabled', 'cartPlanName', 'baseCurrencyCode', 'baseCurrencySymbol'));
    }

    public function publicTenantPaymentMethods(Tenant $tenant)
    {
        $this->abortIfTenantInactiveForPublic($tenant);

        $paymentMethods = PaymentMethod::with('currency')
            ->where('tenant_id', $tenant->id)
            ->where('status', 1)
            ->get()
            ->filter(function ($paymentMethod) {
                return !in_array(Str::lower((string) $paymentMethod->name), ['efectivo', 'punto de venta']);
            })
            ->map(function ($paymentMethod) {
                $qrPath = null;

                if (!empty($paymentMethod->qr_image)) {
                    $decodedQr = json_decode((string) $paymentMethod->qr_image, true);
                    if (is_array($decodedQr) && !empty($decodedQr[0])) {
                        $qrPath = $decodedQr[0];
                    } elseif (is_string($paymentMethod->qr_image)) {
                        $qrPath = $paymentMethod->qr_image;
                    }
                }

                $paymentMethod->qr_image_url = ImageStorage::url($qrPath);
                return $paymentMethod;
            })
            ->values();

        $dollarRate = DollarRate::where('tenant_id', $tenant->id)
            ->latest('created_at')
            ->value('rate');

        $euroRate = EuroRate::where('tenant_id', $tenant->id)
            ->latest('created_at')
            ->value('rate');

        $baseCurrency = $this->resolveTenantBaseCurrencyCode($tenant);
        $baseRate = $baseCurrency === 'EUR'
            ? (float) ($euroRate ?: 0)
            : (float) ($dollarRate ?: 0);
        $tenantElectronicInvoicingEnabled = (bool) ($tenant->electronic_invoicing_enabled ?? false);
        $specialTaxpayer = $this->isSpecialTaxpayer($tenant);
        $igtfRate = $this->shouldApplyIgtfForTenant($tenant) ? $this->resolveIgtfRate() : 0;

        return response()->json([
            'success' => true,
            'methods' => $paymentMethods,
            'dollar_rate' => $dollarRate ? (float) $dollarRate : 0,
            'euro_rate' => $euroRate ? (float) $euroRate : 0,
            'base_currency' => $baseCurrency,
            'base_rate' => $baseRate,
            'electronic_invoicing_enabled' => $tenantElectronicInvoicingEnabled,
            'special_taxpayer' => $specialTaxpayer,
            'igtf_rate' => (float) $igtfRate,
        ]);
    }

    private function resolveTenantBaseCurrencyCode(Tenant $tenant): string
    {
        $code = strtoupper(trim((string) ($tenant->base_currency ?? 'USD')));
        return in_array($code, ['USD', 'EUR'], true) ? $code : 'USD';
    }

    private function resolveCurrencySymbol(string $code): string
    {
        return strtoupper(trim($code)) === 'EUR' ? '€' : '$';
    }

    private function resolveIgtfRate(): float
    {
        return (float) (Tax::query()
            ->whereRaw('LOWER(name) = ?', ['igtf'])
            ->where(function ($query) {
                $query->whereNull('is_active')->orWhere('is_active', 1);
            })
            ->value('rate') ?? 0);
    }

    private function isSpecialTaxpayer(Tenant $tenant): bool
    {
        return (bool) ($tenant->special_taxpayer ?? false);
    }

    private function shouldApplyIgtfForTenant(Tenant $tenant): bool
    {
        return (bool) ($tenant->electronic_invoicing_enabled ?? false) && !$this->isSpecialTaxpayer($tenant);
    }

    private function normalizeCheckoutCurrencyCode(?string $currencyCode): string
    {
        $code = strtoupper(trim((string) $currencyCode));

        if (in_array($code, ['BS', 'VES', 'VED', 'VEF', 'BOLIVAR', 'BOLIVARES'], true)) {
            return 'BS';
        }

        return $code;
    }

    public function publicTenantResolveScanCode(Request $request, Tenant $tenant)
    {
        $this->abortIfTenantInactiveForPublic($tenant);

        $request->validate([
            'code' => 'required|string|max:150',
        ]);

        $code = trim((string) $request->input('code'));

        $variant = ProductVariant::with(['product'])
            ->where(function ($query) use ($code) {
                $query->where('qr_code', $code)->orWhere('barcode', $code);
            })
            ->whereHas('product', function ($query) use ($tenant) {
                $query->where('tenant_id', $tenant->id)->where('is_active', true);
            })
            ->first();

        if ($variant) {
            $price = $this->getVariantDiscountedUnitPrice($variant);

            return response()->json([
                'success' => true,
                'type' => 'variant',
                'variant' => [
                    'id' => $variant->id,
                    'product_name' => $variant->product->name ?? 'Producto',
                    'size' => $variant->size,
                    'price' => $price,
                ],
            ]);
        }

        $package = MaterialPackage::query()
            ->where('tenant_id', $tenant->id)
            ->where('is_active', true)
            ->where(function ($query) use ($code) {
                $query->where('qr_code', $code)->orWhere('barcode', $code);
            })
            ->first();

        if ($package) {
            return response()->json([
                'success' => true,
                'type' => 'package',
                'package_id' => $package->id,
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Código no encontrado.',
        ], 404);
    }

    public function publicTenantProCheckout(Request $request, Tenant $tenant)
    {
        $this->abortIfTenantInactiveForPublic($tenant);

        if (!$this->tenantHasProPlan($tenant)) {
            return response()->json([
                'success' => false,
                'message' => 'El checkout completo solo está disponible para planes Pro.',
            ], 403);
        }

        try {
            $validated = $request->validate([
                'customer_id' => 'required|exists:users,id',
                'delivery_type' => 'required|in:pickup,shipping',
                'delivery_address' => 'nullable|string|max:500',
                'delivery_city_id' => 'nullable|integer|exists:cities,id',
                'mark_delivered' => 'nullable|boolean',
                'mark_payments_paid' => 'nullable|boolean',
                'mark_sale_completed' => 'nullable|boolean',
                'items' => 'required|array|min:1',
                'items.*.variant_id' => 'required|integer|exists:product_variants,id',
                'items.*.quantity' => 'required|integer|min:1',
                'items.*.unit_price' => 'nullable|numeric|min:0.01',
                'payments' => 'required|array|min:1',
                'payments.*.method_id' => 'required|integer|exists:payment_methods,id',
                'payments.*.amount' => 'required|numeric|min:0.01',
                'payments.*.reference' => 'required|string|max:255',
                'payments.*.reference_image_data' => 'required|string',
                'payments.*.reference_image_mime' => 'nullable|string|max:100',
            ]);

            if ($validated['delivery_type'] === 'shipping' && empty(trim((string) ($validated['delivery_address'] ?? '')))) {
                return response()->json([
                    'success' => false,
                    'message' => 'La dirección es obligatoria para envío.',
                ], 422);
            }

            if ($validated['delivery_type'] === 'shipping' && (bool) ($tenant->restrict_delivery_city_to_tenant ?? true)) {
                $deliveryCityId = (int) ($validated['delivery_city_id'] ?? 0);
                $shippingCityValidation = $this->validateShippingCityAgainstTenant($tenant, $deliveryCityId);

                if (!($shippingCityValidation['ok'] ?? false)) {
                    return response()->json([
                        'success' => false,
                        'message' => (string) ($shippingCityValidation['message'] ?? 'Solo se permiten envíos a la ciudad de la tienda.'),
                    ], 422);
                }
            }

            foreach ($validated['payments'] as $paymentIndex => $paymentData) {
                $reference = trim((string) ($paymentData['reference'] ?? ''));
                $referenceImageData = trim((string) ($paymentData['reference_image_data'] ?? ''));

                if ($reference === '') {
                    throw new \RuntimeException('La referencia del pago #' . ($paymentIndex + 1) . ' es obligatoria.');
                }

                if ($referenceImageData === '') {
                    throw new \RuntimeException('La imagen de comprobante del pago #' . ($paymentIndex + 1) . ' es obligatoria.');
                }

                if (preg_match('/^data:image\/[a-zA-Z0-9.+-]+;base64,/', $referenceImageData) !== 1) {
                    throw new \RuntimeException('El comprobante del pago #' . ($paymentIndex + 1) . ' debe ser una imagen valida.');
                }

                $validated['payments'][$paymentIndex]['reference'] = $reference;
                $validated['payments'][$paymentIndex]['reference_image_data'] = $referenceImageData;
            }

            $address = $validated['delivery_type'] === 'shipping'
                ? trim((string) $validated['delivery_address'])
                : 'Tienda';

            $markDelivered = (bool) ($validated['mark_delivered'] ?? false);
            $markPaymentsPaid = (bool) ($validated['mark_payments_paid'] ?? false);
            $markSaleCompleted = (bool) ($validated['mark_sale_completed'] ?? false);
            $baseCurrencyCode = $this->resolveTenantBaseCurrencyCode($tenant);
            $tenantElectronicInvoicingEnabled = (bool) ($tenant->electronic_invoicing_enabled ?? false);
            $igtfRate = $this->shouldApplyIgtfForTenant($tenant) ? $this->resolveIgtfRate() : 0;

            $preference = $validated['delivery_type'] === 'shipping'
                ? 'Envío'
                : 'Retiro en tienda';

            $salesOrder = DB::transaction(function () use ($validated, $tenant, $address, $preference, $markDelivered, $markPaymentsPaid, $markSaleCompleted, $baseCurrencyCode, $tenantElectronicInvoicingEnabled, $igtfRate) {
                $salesOrder = SalesOrder::create([
                    'user_id' => $validated['customer_id'],
                    'date' => now()->toDateString(),
                    'status' => $markSaleCompleted ? 1 : 0,
                    'address' => $address,
                    'preference' => $preference,
                    'deliver_status' => $markDelivered ? 1 : 0,
                    'tenant_id' => $tenant->id,
                    'sale_currency_code' => $baseCurrencyCode,
                ]);

                $orderTotal = 0;

                foreach ($validated['items'] as $item) {
                    $variant = ProductVariant::with('product')->findOrFail($item['variant_id']);

                if ((int) $variant->product->tenant_id !== (int) $tenant->id) {
                    throw new \RuntimeException('Uno de los productos no pertenece a esta tienda.');
                }

                if ((int) $variant->stock < (int) $item['quantity']) {
                    throw new \RuntimeException('Stock insuficiente para una de las variantes seleccionadas.');
                }

                $variantEffectivePrice = $this->getVariantDiscountedUnitPrice($variant);
                $providedUnitPrice = isset($item['unit_price']) ? (float) $item['unit_price'] : $variantEffectivePrice;
                $unitPrice = min($variantEffectivePrice, $providedUnitPrice);

                $lineAmount = $unitPrice * (int) $item['quantity'];
                $orderTotal += $lineAmount;

                    SalesOrderDetail::create([
                        'sales_order_id' => $salesOrder->id,
                        'product_variant_id' => $variant->id,
                        'quantity' => (int) $item['quantity'],
                        'price' => $unitPrice,
                        'amount' => $lineAmount,
                    ]);

                    $variant->stock -= (int) $item['quantity'];
                    $variant->save();
                }

                $totalPaid = 0;
                $directBaseCurrencyPayments = 0;

                foreach ($validated['payments'] as $paymentData) {
                    $method = PaymentMethod::with('currency')
                        ->active()
                        ->findOrFail($paymentData['method_id']);

                if ((int) $method->tenant_id !== (int) $tenant->id) {
                    throw new \RuntimeException('Uno de los métodos de pago no pertenece a esta tienda.');
                }

                    $amount = (float) $paymentData['amount'];
                    $totalPaid += $amount;

                    $methodCurrencyCode = $this->normalizeCheckoutCurrencyCode(
                        (string) ($method->currency->code ?? $method->currency->name ?? '')
                    );

                    if ($methodCurrencyCode === $baseCurrencyCode) {
                        $directBaseCurrencyPayments += $amount;
                    }

                    $payment = Payment::create([
                        'sales_order_id' => $salesOrder->id,
                        'payment_method' => $method->id,
                        'amount' => $amount,
                        'currency' => $method->currency->code ?? $method->currency->name ?? 'USD',
                        'reference' => $paymentData['reference'] ?? null,
                        'status' => $markPaymentsPaid ? 1 : 0,
                    ]);

                    $this->storePaymentReferenceImageFromPayload($payment, $paymentData['reference_image_data'] ?? null, $paymentData['reference_image_mime'] ?? null);
                }

                $igtfAmount = ($igtfRate > 0)
                    ? ($directBaseCurrencyPayments * ($igtfRate / 100))
                    : 0;

                $requiredTotal = $orderTotal + $igtfAmount;

                if ($totalPaid + 0.0001 < $requiredTotal) {
                    throw new \RuntimeException('El total pagado es menor al total del pedido.');
                }

                return $salesOrder;
            });

            WorkflowNotifier::notifyTenantRoles((int) $tenant->id, ['owner', 'administrador', 'admin', 'vendedor'], [
                'title' => 'Nueva compra de cliente',
                'message' => 'Se creó el pedido #' . $salesOrder->id . '. Revisa venta y métodos de pago.',
                'type' => 'new-order',
                'tenant_id' => $tenant->id,
                'order_id' => $salesOrder->id,
                'action' => 'review_order_and_payments',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Pedido creado correctamente. Tu pedido fue enviado para validación.',
                'order_id' => $salesOrder->id,
            ], 201);
        } catch (\RuntimeException $exception) {
            return response()->json([
                'success' => false,
                'message' => $exception->getMessage(),
            ], 422);
        } catch (\Throwable $exception) {
            report($exception);

            return response()->json([
                'success' => false,
                'message' => 'No se pudo completar el pedido en este momento.',
            ], 500);
        }
    }

    private function storePaymentReferenceImageFromPayload(Payment $payment, ?string $base64Image, ?string $mimeType = null): void
    {
        $payload = trim((string) $base64Image);
        if ($payload === '') {
            return;
        }

        if (preg_match('/^data:(image\/[a-zA-Z0-9.+-]+);base64,/', $payload, $matches) === 1) {
            $mimeType = $matches[1];
            $payload = preg_replace('/^data:image\/[a-zA-Z0-9.+-]+;base64,/', '', $payload) ?? $payload;
        }

        $binary = base64_decode($payload, true);
        if ($binary === false) {
            return;
        }

        $extension = match (Str::lower((string) $mimeType)) {
            'image/jpeg', 'image/jpg' => 'jpg',
            'image/webp' => 'webp',
            default => 'png',
        };

        $path = ImageStorage::storeBinary($binary, 'payment_images', $extension, $mimeType);

        PaymentImage::create([
            'payment_id' => $payment->id,
            'image_path' => $path,
        ]);
    }

    private function abortIfTenantInactiveForPublic(Tenant $tenant): void
    {
        if ((int) ($tenant->is_active ?? 1) === 0) {
            abort(404);
        }
    }

    private function normalizeWorkingDays($days): ?array
    {
        if (!is_array($days)) {
            return null;
        }

        $normalized = collect($days)
            ->map(fn ($day) => strtolower(trim((string) $day)))
            ->filter(fn ($day) => in_array($day, ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'], true))
            ->unique()
            ->values()
            ->all();

        return empty($normalized) ? null : $normalized;
    }

    private function getVariantDiscountedUnitPrice(ProductVariant $variant): float
    {
        $basePrice = (float) ($variant->price ?? 0);
        $productDiscount = max(0, min(100, (float) ($variant->product->discount_percentage ?? 0)));
        $variantDiscount = max(0, min(100, (float) ($variant->discount_percentage ?? 0)));

        $afterProductDiscount = $basePrice * ((100 - $productDiscount) / 100);

        return round($afterProductDiscount * ((100 - $variantDiscount) / 100), 2);
    }

    private function getTenantCurrentPlanName(Tenant $tenant): ?string
    {
        $latestPaidPlanPayment = $this->getTenantLatestPaidPlanPayment($tenant);

        return $latestPaidPlanPayment?->plan?->name;
    }

    private function getTenantLatestPaidPlanPayment(Tenant $tenant): ?TenantPlanPayment
    {
        return $tenant->tenantPlanPayments()
            ->with('plan')
            ->where('status', 'paid')
            ->orderByDesc('paid_at')
            ->orderByDesc('id')
            ->first();
    }

    private function isFreePlan(Plan $plan): bool
    {
        return (float) ($plan->price ?? 0) <= 0;
    }

    private function resolvePlanExpirationDate(Tenant $tenant, Plan $plan, Carbon $baseDate): Carbon
    {
        $durationDays = max(0, (int) ($plan->duration_days ?? 0));
        $startAt = clone $baseDate;

        $latestPaid = $this->getTenantLatestPaidPlanPayment($tenant);
        if ($latestPaid && $latestPaid->expires_at instanceof Carbon && $latestPaid->expires_at->greaterThan($baseDate)) {
            $startAt = $latestPaid->expires_at->copy();
        }

        return $startAt->addDays($durationDays);
    }

    private function calculatePlanDaysRemaining(?TenantPlanPayment $payment): ?int
    {
        $cutoffDate = $this->resolvePaymentCutoffDate($payment);
        if (!$cutoffDate) {
            return null;
        }

        $expiresAt = $cutoffDate->copy();

        $now = now();

        if ($expiresAt->greaterThanOrEqualTo($now)) {
            return $now->diffInDays($expiresAt);
        }

        return -1 * $expiresAt->diffInDays($now);
    }

    private function resolvePaymentCutoffDate(?TenantPlanPayment $payment): ?Carbon
    {
        if (!$payment) {
            return null;
        }

        if (!empty($payment->expires_at)) {
            return $payment->expires_at instanceof Carbon
                ? $payment->expires_at->copy()
                : Carbon::parse($payment->expires_at);
        }

        if (empty($payment->paid_at)) {
            return null;
        }

        $durationDays = max(0, (int) ($payment->plan->duration_days ?? 0));

        $paidAt = $payment->paid_at instanceof Carbon
            ? $payment->paid_at->copy()
            : Carbon::parse($payment->paid_at);

        return $paidAt->addDays($durationDays);
    }

    private function isBasicPlanTenant(?Tenant $tenant): bool
    {
        if (!$tenant) {
            return false;
        }

        $latestPaidPlan = $this->getTenantLatestPaidPlanPayment($tenant);
        $planName = Str::lower(Str::ascii((string) ($latestPaidPlan?->plan?->name ?? '')));

        return Str::contains($planName, ['basico', 'basic']);
    }

    private function resolveAdminRoleIds(): array
    {
        return Role::query()
            ->get()
            ->filter(function (Role $role) {
                return User::canonicalRoleName((string) $role->name) === 'admin';
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    private function resolveOwnerRoleIds(): array
    {
        return Role::query()
            ->get()
            ->filter(function (Role $role) {
                return User::canonicalRoleName((string) $role->name) === 'owner';
            })
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();
    }

    private function tenantHasProPlan(Tenant $tenant): bool
    {
        $planName = Str::lower((string) $this->getTenantCurrentPlanName($tenant));

        return Str::contains($planName, 'pro');
    }

    private function normalizeBusinessType(?string $value): ?string
    {
        $normalized = Str::lower(trim((string) $value));
        if ($normalized === '') {
            return null;
        }

        return $normalized === 'servicio' ? 'Servicio' : 'Tienda';
    }

    private function normalizeEconomicActivity(?string $value, ?string $businessType = null): ?string
    {
        $normalized = trim((string) $value);
        if ($normalized === '') {
            return null;
        }

        $normalized = preg_replace('/\s+/', ' ', $normalized) ?? $normalized;

        $businessTypeKey = $this->resolveBusinessTypeKey($businessType);
        $catalog = $this->getBusinessActivityCatalog();
        if ($businessTypeKey && isset($catalog[$businessTypeKey])) {
            foreach ($catalog[$businessTypeKey] as $option) {
                if (Str::lower($option) === Str::lower($normalized)) {
                    return $option;
                }
            }
        }

        return Str::title(Str::lower($normalized));
    }

    private function assertEconomicActivityAllowed(?string $businessType, ?string $economicActivity): void
    {
        $businessTypeKey = $this->resolveBusinessTypeKey($businessType);
        $normalizedActivity = trim((string) $economicActivity);

        if (!$businessTypeKey || $normalizedActivity === '') {
            return;
        }

        $catalog = $this->getBusinessActivityCatalog();
        $options = $catalog[$businessTypeKey] ?? [];
        $normalizedActivity = preg_replace('/\s+/', ' ', $normalizedActivity) ?? $normalizedActivity;

        foreach ($options as $option) {
            if (Str::lower($option) === Str::lower($normalizedActivity)) {
                return;
            }
        }

        $businessTypeLabel = $businessTypeKey === 'servicio' ? 'Servicio' : 'Tienda';
        throw ValidationException::withMessages([
            'economic_activity' => [
                'El rubro economico no corresponde al tipo de negocio seleccionado (' . $businessTypeLabel . '). Opciones validas: ' . implode(', ', $options) . '.',
            ],
        ]);
    }

    private function assertLocationHierarchy($countryId, $stateId, $cityId): void
    {
        if ($stateId) {
            $state = State::query()->find($stateId);
            if (!$state) {
                throw ValidationException::withMessages([
                    'state' => ['El estado seleccionado no existe.'],
                ]);
            }

            if ($countryId && (int) $state->country_id !== (int) $countryId) {
                throw ValidationException::withMessages([
                    'state' => ['El estado seleccionado no pertenece al país indicado.'],
                ]);
            }
        }

        if ($cityId) {
            $city = City::query()->find($cityId);
            if (!$city) {
                throw ValidationException::withMessages([
                    'city' => ['La ciudad seleccionada no existe.'],
                ]);
            }

            if ($stateId && (int) $city->state_id !== (int) $stateId) {
                throw ValidationException::withMessages([
                    'city' => ['La ciudad seleccionada no pertenece al estado indicado.'],
                ]);
            }
        }
    }

    private function resolveBusinessTypeKey(?string $value): ?string
    {
        $normalized = Str::lower(trim((string) $value));
        if ($normalized === '') {
            return null;
        }

        if ($normalized === 'tienda') {
            return 'tienda';
        }

        if ($normalized === 'servicio') {
            return 'servicio';
        }

        return null;
    }

    private function validateShippingCityAgainstTenant(Tenant $tenant, int $deliveryCityId): array
    {
        if ($deliveryCityId <= 0) {
            return [
                'ok' => false,
                'message' => 'Debes seleccionar la ciudad de entrega.',
            ];
        }

        $tenantCityId = $this->resolveTenantCityId($tenant);
        if ($tenantCityId <= 0) {
            return [
                'ok' => false,
                'message' => 'La tienda no tiene una ciudad configurada para envíos.',
            ];
        }

        if ($tenantCityId !== $deliveryCityId) {
            $tenantCityName = City::query()->whereKey($tenantCityId)->value('name');

            return [
                'ok' => false,
                'message' => 'Solo se permiten envíos para la ciudad de la tienda' . (!empty($tenantCityName) ? ': ' . $tenantCityName : '.'),
            ];
        }

        return ['ok' => true];
    }

    private function resolveTenantCityId(Tenant $tenant): int
    {
        $rawCity = trim((string) ($tenant->city ?? ''));
        if ($rawCity === '') {
            return 0;
        }

        if (ctype_digit($rawCity)) {
            return (int) $rawCity;
        }

        return (int) (City::query()
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($rawCity)])
            ->value('id') ?? 0);
    }

    private function getBusinessActivityCatalog(): array
    {
        return [
            'tienda' => [
                'Alimentos y Bebidas',
                'Moda y Accesorios',
                'Hogar y Construccion',
                'Tecnologia',
                'Salud y Belleza',
                'Otros',
            ],
            'servicio' => [
                'Gastronomia',
                'Cuidado Personal',
                'Servicios Tecnicos',
                'Profesionales',
                'Logistica y Educacion',
            ],
        ];
    }

    private function filterDataByExistingColumns(string $tableName, array $data): array
    {
        if (!Schema::hasTable($tableName)) {
            return $data;
        }

        $existingColumns = array_flip(Schema::getColumnListing($tableName));

        return array_filter(
            $data,
            fn ($value, $columnName) => array_key_exists($columnName, $existingColumns),
            ARRAY_FILTER_USE_BOTH
        );
    }

    private function resolveTenantCreationDbErrorMessage(QueryException $exception): string
    {
        $message = Str::lower((string) $exception->getMessage());

        if (Str::contains($message, ['tenants_slug_unique', 'duplicate entry']) && Str::contains($message, 'slug')) {
            return 'El slug ingresado ya está en uso. Prueba con otro.';
        }

        if (Str::contains($message, ['unknown column', 'column not found'])) {
            return 'La plataforma requiere una actualización de base de datos. Contacta al administrador.';
        }

        return 'No se pudo crear la tienda. Intenta nuevamente.';
    }

    public function destroy(Tenant $tenant)
    {
        DB::raw("SET @user_id = " . auth()->id());

        $tenant->delete();

        return response()->json(['message' => 'Tenant eliminado correctamente']);
    }
}
