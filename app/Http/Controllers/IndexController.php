<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use App\Models\Provider;
use Illuminate\Http\Request;
use App\Models\ProductInventory;
use App\Models\Category;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\StoreExpense;
use App\Models\User;
use App\Models\PurchaseOrder;
use App\Models\DollarRate;
use App\Models\Tenant;
use App\Models\Country;
use App\Models\State;
use App\Models\City;
use Carbon\Carbon;
use App\Models\Log;
use App\Models\SalesOrderDetail;
use App\Support\TenantPlanCapabilities;
use App\Support\UserRedirector;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class IndexController extends Controller
{
    public function landing()
    {
        $categories = Category::all()->take(3);
        $tenantsQuery = Tenant::query();
        if (Schema::hasColumn('tenants', 'is_active')) {
            $tenantsQuery->where(function ($query) {
                $query->whereNull('is_active')->orWhere('is_active', 1);
            });
        }

        $tenants = $tenantsQuery->get();
        
        // Asocia íconos por nombre o ID
        $icons = [
            'Ropa' => 'bi bi-shirt',
            'Calzado' => 'bi bi-boot',
            'Accesorios' => 'bi bi-sunglasses',
            // Agrega más según tus categorías
        ];
    
        // Añadir icono a cada categoría
        foreach ($categories as $category) {
            $category->icon = $icons[$category->name] ?? 'bi bi-tag'; // icono por defecto
        }
    
        $variantColumns = [
            'id',
            'product_id',
            'size',
            'price',
            'stock',
            'discount_percentage',
            'qr_code',
            'barcode',
        ];
        if (Schema::hasColumn('product_variants', 'is_active')) {
            $variantColumns[] = 'is_active';
        }

        $productItems = Product::with([
                'category:id,name',
                'images:id,product_id,path',
                'variants:' . implode(',', $variantColumns),
            ])
            ->orderBy('created_at', 'desc')
            ->take(3)
            ->get();
    
        return view('ecommerce', compact('categories', 'productItems', 'tenants'));
    }

    public function landingDirectory()
    {
        $directoryData = $this->buildTenantDirectoryData();

        return view('ecommerceDirectory', [
            'tenantsDirectory' => $directoryData['tenantsDirectory'],
            'tenantFilters' => $directoryData['tenantFilters'],
        ]);
    }

    private function buildTenantDirectoryData(): array
    {
        $tenantsQuery = Tenant::with('categories:id,name,tenant_id');
        if (Schema::hasColumn('tenants', 'is_active')) {
            $tenantsQuery->where(function ($query) {
                $query->whereNull('is_active')->orWhere('is_active', 1);
            });
        }

        $tenants = $tenantsQuery->get();
        $countryMap = Country::pluck('name', 'id')->mapWithKeys(fn ($name, $id) => [(string) $id => (string) $name]);
        $stateMap = State::pluck('name', 'id')->mapWithKeys(fn ($name, $id) => [(string) $id => (string) $name]);
        $cityMap = City::pluck('name', 'id')->mapWithKeys(fn ($name, $id) => [(string) $id => (string) $name]);

        $hasBusinessType = Schema::hasColumn('tenants', 'business_type');
        $hasEconomicActivity = Schema::hasColumn('tenants', 'economic_activity');

        $tenantsDirectory = $tenants->map(function (Tenant $tenant) use ($countryMap, $stateMap, $cityMap, $hasBusinessType, $hasEconomicActivity) {
            $countryName = $this->resolveLocationName($tenant->country, $countryMap);
            $stateName = $this->resolveLocationName($tenant->state, $stateMap);
            $cityName = $this->resolveLocationName($tenant->city, $cityMap);

            $businessType = $hasBusinessType ? $this->normalizeDirectoryBusinessType((string) ($tenant->business_type ?? '')) : '';
            $economicActivity = $hasEconomicActivity ? $this->normalizeDirectoryEconomicActivity((string) ($tenant->economic_activity ?? '')) : '';

            if ($businessType === '') {
                $businessType = $this->normalizeDirectoryBusinessType($this->inferTenantType($tenant));
            }

            if ($economicActivity === '') {
                $economicActivity = $this->normalizeDirectoryEconomicActivity($this->inferTenantActivity($tenant));
            }

            $tenant->directory_country = $countryName;
            $tenant->directory_state = $stateName;
            $tenant->directory_city = $cityName;
            $tenant->directory_region = $this->resolveVenezuelaRegion($stateName, $countryName);
            $tenant->directory_type = $businessType;
            $tenant->directory_activity = $economicActivity;

            return $tenant;
        });

        $tenantFilters = [
            'types' => $tenantsDirectory->pluck('directory_type')->filter()->unique()->sort()->values(),
            'activities' => $tenantsDirectory->pluck('directory_activity')->filter()->unique()->sort()->values(),
            'regions' => $tenantsDirectory->pluck('directory_region')->filter()->unique()->sort()->values(),
            'states' => $tenantsDirectory->pluck('directory_state')->filter()->unique()->sort()->values(),
            'cities' => $tenantsDirectory->pluck('directory_city')->filter()->unique()->sort()->values(),
        ];

        return [
            'tenantsDirectory' => $tenantsDirectory,
            'tenantFilters' => $tenantFilters,
        ];
    }

    private function normalizeDirectoryBusinessType(?string $value): string
    {
        $key = $this->normalizeLookupKey($value);
        if ($key === '') {
            return '';
        }

        if (in_array($key, ['servicio', 'servicios', 'service', 'services'], true)) {
            return 'Servicio';
        }

        if (in_array($key, ['sede', 'sedes', 'store', 'stores', 'comercio', 'comercios'], true)) {
            return 'sede';
        }

        return '';
    }

    private function normalizeDirectoryEconomicActivity(?string $value): string
    {
        $normalized = trim((string) $value);
        if ($normalized === '') {
            return '';
        }

        $normalized = preg_replace('/\s+/', ' ', $normalized) ?? $normalized;

        $canonical = $this->matchDirectoryActivityToCatalog($normalized);
        if ($canonical !== '') {
            return $canonical;
        }

        return Str::title(Str::lower($normalized));
    }

    private function matchDirectoryActivityToCatalog(string $value): string
    {
        $needle = $this->normalizeLookupKey($value);
        if ($needle === '') {
            return '';
        }

        foreach ($this->directoryBusinessActivityCatalog() as $activity) {
            if ($this->normalizeLookupKey($activity) === $needle) {
                return $activity;
            }
        }

        $aliases = [
            'alimentosybebidas' => 'Alimentos y Bebidas',
            'alimentos' => 'Alimentos y Bebidas',
            'bebidas' => 'Alimentos y Bebidas',
            'modayaccesorios' => 'Moda y Accesorios',
            'moda' => 'Moda y Accesorios',
            'accesorios' => 'Moda y Accesorios',
            'hogaryconstruccion' => 'Hogar y Construccion',
            'hogar' => 'Hogar y Construccion',
            'construccion' => 'Hogar y Construccion',
            'ferreteria' => 'Hogar y Construccion',
            'tecnologia' => 'Tecnologia',
            'tecnologico' => 'Tecnologia',
            'electronica' => 'Tecnologia',
            'computacion' => 'Tecnologia',
            'telefonia' => 'Tecnologia',
            'saludybelleza' => 'Salud y Belleza',
            'salud' => 'Salud y Belleza',
            'belleza' => 'Salud y Belleza',
            'farmacia' => 'Salud y Belleza',
            'cosmetica' => 'Salud y Belleza',
            'gastronomia' => 'Gastronomia',
            'restaurante' => 'Gastronomia',
            'cafeteria' => 'Gastronomia',
            'caterings' => 'Gastronomia',
            'cuidadopersonal' => 'Cuidado Personal',
            'peluqueria' => 'Cuidado Personal',
            'estetica' => 'Cuidado Personal',
            'spa' => 'Cuidado Personal',
            'gimnasio' => 'Cuidado Personal',
            'serviciostecnicos' => 'Servicios Tecnicos',
            'serviciotecnico' => 'Servicios Tecnicos',
            'reparacion' => 'Servicios Tecnicos',
            'soporteit' => 'Servicios Tecnicos',
            'profesionales' => 'Profesionales',
            'profesional' => 'Profesionales',
            'consultorio' => 'Profesionales',
            'consultoria' => 'Profesionales',
            'arquitectura' => 'Profesionales',
            'logisticayeducacion' => 'Logistica y Educacion',
            'logistica' => 'Logistica y Educacion',
            'educacion' => 'Logistica y Educacion',
            'mensajeria' => 'Logistica y Educacion',
            'instituto' => 'Logistica y Educacion',
            'otros' => 'Otros',
            'general' => 'Otros',
        ];

        return $aliases[$needle] ?? '';
    }

    private function directoryBusinessActivityCatalog(): array
    {
        return [
            'Alimentos y Bebidas',
            'Moda y Accesorios',
            'Hogar y Construccion',
            'Tecnologia',
            'Salud y Belleza',
            'Otros',
            'Gastronomia',
            'Cuidado Personal',
            'Servicios Tecnicos',
            'Profesionales',
            'Logistica y Educacion',
        ];
    }

    private function normalizeLookupKey(?string $value): string
    {
        $normalized = Str::lower(trim((string) $value));
        if ($normalized === '') {
            return '';
        }

        $normalized = strtr($normalized, [
            'á' => 'a',
            'é' => 'e',
            'í' => 'i',
            'ó' => 'o',
            'ú' => 'u',
            'ü' => 'u',
            'ñ' => 'n',
        ]);

        return preg_replace('/[^a-z0-9]/', '', $normalized) ?? '';
    }

    private function resolveLocationName($value, $lookup)
    {
        if (is_null($value)) {
            return '';
        }

        $raw = trim((string) $value);
        if ($raw === '') {
            return '';
        }

        if (preg_match('/^\d+$/', $raw) === 1 && isset($lookup[$raw])) {
            return trim((string) $lookup[$raw]);
        }

        return $raw;
    }

    private function inferTenantType(Tenant $tenant): string
    {
        $name = strtolower((string) $tenant->name);
        $description = strtolower((string) ($tenant->description ?? ''));
        $categoryNames = strtolower((string) $tenant->categories->pluck('name')->implode(' '));
        $haystack = trim($name . ' ' . $description . ' ' . $categoryNames);

        if ($haystack === '') {
            return 'sede';
        }

        if (preg_match('/servicio|consultor|agencia|taller|reparaci[oó]n|sal[oó]n|spa|barber|estudio/', $haystack) === 1) {
            return 'Servicio';
        }

        return 'sede';
    }

    private function inferTenantActivity(Tenant $tenant): string
    {
        $firstCategory = trim((string) optional($tenant->categories->first())->name);
        if ($firstCategory !== '') {
            return $firstCategory;
        }

        return 'General';
    }

    private function resolveVenezuelaRegion(string $stateName, string $countryName): string
    {
        $country = strtolower(trim($countryName));
        $state = strtolower(trim($stateName));

        if ($state === '') {
            return $country === 'venezuela' ? 'Sin región' : 'Internacional';
        }

        $regions = [
            'Capital' => ['distrito capital', 'miranda', 'la guaira', 'vargas'],
            'Central' => ['aragua', 'carabobo', 'cojedes'],
            'Centro-Occidente' => ['lara', 'falcón', 'falcon', 'yaracuy'],
            'Occidente' => ['zulia', 'trujillo', 'mérida', 'merida', 'táchira', 'tachira'],
            'Los Llanos' => ['barinas', 'portuguesa', 'guárico', 'guarico', 'apure'],
            'Oriente' => ['anzoátegui', 'anzoategui', 'monagas', 'sucre', 'nueva esparta'],
            'Guayana' => ['bolívar', 'bolivar', 'amazonas', 'delta amacuro'],
            'Insular' => ['nueva esparta'],
        ];

        foreach ($regions as $region => $states) {
            if (in_array($state, $states, true)) {
                return $region;
            }
        }

        return $country === 'venezuela' ? 'Otras zonas' : 'Internacional';
    }
    public function indexLog(Request $request)
    {
        $filters = [
            'user_id' => trim((string) $request->query('user_id', '')),
            'tenant_id' => trim((string) $request->query('tenant_id', '')),
            'role' => trim((string) $request->query('role', '')),
            'module' => trim((string) $request->query('module', '')),
            'action' => trim((string) $request->query('action', '')),
            'status' => trim((string) $request->query('status', '')),
            'date_from' => trim((string) $request->query('date_from', '')),
            'date_to' => trim((string) $request->query('date_to', '')),
            'q' => trim((string) $request->query('q', '')),
        ];

        $query = Log::query()->orderByDesc('id');

        if ($filters['user_id'] !== '' && ctype_digit($filters['user_id'])) {
            $query->where('user_id', (int) $filters['user_id']);
        }

        if ($filters['action'] !== '') {
            $query->where('action', 'like', '%' . $filters['action'] . '%');
        }

        if ($filters['date_from'] !== '') {
            $query->whereDate('created_at', '>=', $filters['date_from']);
        }

        if ($filters['date_to'] !== '') {
            $query->whereDate('created_at', '<=', $filters['date_to']);
        }

        $rawLogs = $query->limit(4000)->get();

        $logsCollection = $rawLogs->map(function (Log $log) {
            $payload = $this->decodeAuditPayload($log->description);
            $log->audit_payload = $payload;
            $log->audit_tenant_id = (string) ($payload['tenant_id'] ?? '');
            $log->audit_role = (string) ($payload['role'] ?? '');
            $log->audit_module = (string) ($payload['module'] ?? $log->table_name);
            $log->audit_event_type = (string) ($log->event_type ?? '');
            $log->audit_record_id = (string) ($log->record_id ?? '');
            $log->audit_status = (string) ($payload['status'] ?? '');
            $log->audit_route_name = (string) ($payload['route_name'] ?? '');
            $log->audit_path = (string) ($payload['path'] ?? '');
            $log->audit_message = (string) ($payload['message'] ?? '');
            $log->audit_old_values = is_array($log->old_values) ? $log->old_values : [];
            $log->audit_new_values = is_array($log->new_values) ? $log->new_values : [];

            return $log;
        });

        if ($filters['tenant_id'] !== '') {
            $logsCollection = $logsCollection->filter(function (Log $log) use ($filters) {
                return (string) $log->audit_tenant_id === (string) $filters['tenant_id'];
            });
        }

        if ($filters['role'] !== '') {
            $needle = Str::lower($filters['role']);
            $logsCollection = $logsCollection->filter(function (Log $log) use ($needle) {
                return Str::contains(Str::lower((string) $log->audit_role), $needle);
            });
        }

        if ($filters['module'] !== '') {
            $needle = Str::lower($filters['module']);
            $logsCollection = $logsCollection->filter(function (Log $log) use ($needle) {
                return Str::contains(Str::lower((string) $log->audit_module), $needle)
                    || Str::contains(Str::lower((string) $log->table_name), $needle);
            });
        }

        if ($filters['status'] !== '') {
            $logsCollection = $logsCollection->filter(function (Log $log) use ($filters) {
                return (string) $log->audit_status === (string) $filters['status'];
            });
        }

        if ($filters['q'] !== '') {
            $needle = Str::lower($filters['q']);
            $logsCollection = $logsCollection->filter(function (Log $log) use ($needle) {
                return Str::contains(Str::lower((string) $log->description), $needle)
                    || Str::contains(Str::lower((string) $log->action), $needle)
                    || Str::contains(Str::lower((string) $log->table_name), $needle)
                    || Str::contains(Str::lower((string) $log->audit_route_name), $needle)
                    || Str::contains(Str::lower((string) $log->audit_path), $needle)
                    || Str::contains(Str::lower((string) $log->audit_message), $needle);
            });
        }

        $logsCollection = $logsCollection->values();

        $perPage = 100;
        $currentPage = max(1, (int) $request->query('page', 1));
        $total = $logsCollection->count();
        $items = $logsCollection->slice(($currentPage - 1) * $perPage, $perPage)->values();

        $logs = new LengthAwarePaginator(
            $items,
            $total,
            $perPage,
            $currentPage,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        $filterOptions = [
            'roles' => $rawLogs
                ->map(fn (Log $log) => (string) ($this->decodeAuditPayload($log->description)['role'] ?? ''))
                ->filter()
                ->unique()
                ->sort()
                ->values(),
            'modules' => $rawLogs
                ->map(function (Log $log) {
                    $payload = $this->decodeAuditPayload($log->description);
                    return (string) ($payload['module'] ?? $log->table_name);
                })
                ->filter()
                ->unique()
                ->sort()
                ->values(),
            'actions' => $rawLogs
                ->pluck('action')
                ->filter()
                ->unique()
                ->sort()
                ->values(),
            'statuses' => $rawLogs
                ->map(fn (Log $log) => (string) ($this->decodeAuditPayload($log->description)['status'] ?? ''))
                ->filter()
                ->unique()
                ->sort()
                ->values(),
        ];

        return view('logs', compact('logs', 'filters', 'filterOptions'));
    }

    public function documentationIndex()
    {
        $documents = collect($this->documentationCatalog())
            ->map(function (array $document, string $key) {
                $path = base_path($document['path']);
                $exists = File::exists($path);

                return [
                    'key' => $key,
                    'title' => $document['title'],
                    'description' => $document['description'],
                    'filename' => basename($document['path']),
                    'exists' => $exists,
                    'size' => $exists ? File::size($path) : null,
                    'updated_at' => $exists ? date('Y-m-d H:i:s', File::lastModified($path)) : null,
                ];
            })
            ->values();

        return view('documentation.index', compact('documents'));
    }

    public function documentationDownload(string $document)
    {
        $catalog = $this->documentationCatalog();
        abort_unless(isset($catalog[$document]), 404);

        $path = base_path($catalog[$document]['path']);
        abort_unless(File::exists($path), 404);

        return response()->download($path, basename($path));
    }

    public function updateSuperOwnerTenantScope(Request $request)
    {
        $user = auth()->user();

        abort_unless($user && UserRedirector::isSuperAdmin($user), 403);

        $validated = $request->validate([
            'tenant_scope_id' => 'required|integer|min:0',
        ]);

        $tenantScopeId = (int) $validated['tenant_scope_id'];

        abort_if($tenantScopeId > 0 && !Tenant::query()->whereKey($tenantScopeId)->exists(), 422);

        session(['superowner_tenant_scope_id' => $tenantScopeId]);

        return back()->with('success', 'Filtro de tenant actualizado.');
    }

    private function documentationCatalog(): array
    {
        return [
            'brochure-comercial' => [
                'title' => 'Brochure Comercial',
                'description' => 'Documento de presentacion comercial de la plataforma.',
                'path' => 'docs/shopix_brochure_comercial.docx',
            ],
            'documentacion-general' => [
                'title' => 'Documentacion General',
                'description' => 'Documento funcional general de Shopix.',
                'path' => 'docs/shopix_documentacion_general.docx',
            ],
            'documento-tecnico-interno' => [
                'title' => 'Documento Tecnico Interno',
                'description' => 'Documento tecnico de referencia para equipos internos.',
                'path' => 'docs/shopix_documento_tecnico_interno.docx',
            ],
            'formulario-alta-servicio' => [
                'title' => 'Formulario Alta Servicio Cliente',
                'description' => 'Plantilla de levantamiento orientada al alta de servicios.',
                'path' => 'docs/shopix_formulario_alta_servicio_cliente.docx',
            ],
            'formulario-levantamiento-operativo' => [
                'title' => 'Formulario Levantamiento Operativo Importable',
                'description' => 'Plantilla importable para levantamiento operativo.',
                'path' => 'docs/shopix_formulario_levantamiento_operativo_importable.docx',
            ],
            'manual-tecnico-operativo-unificado' => [
                'title' => 'Manual Tecnico Operativo Unificado',
                'description' => 'Version unificada de brochure y documentacion tecnico-operativa.',
                'path' => 'docs/shopix_manual_tecnico_operativo_unificado.md',
            ],
        ];
    }

    private function decodeAuditPayload(?string $description): array
    {
        $value = trim((string) $description);
        if ($value === '' || !Str::startsWith($value, ['{', '['])) {
            return [];
        }

        $decoded = json_decode($value, true);

        return json_last_error() === JSON_ERROR_NONE && is_array($decoded)
            ? $decoded
            : [];
    }

    private function isAuthAuditLog(Log $log): bool
    {
        $payload = $this->decodeAuditPayload($log->description);

        $module = Str::lower(trim((string) ($payload['module'] ?? $log->table_name ?? '')));
        $action = Str::lower(trim((string) ($log->action ?? '')));

        if ($module === 'auth') {
            return true;
        }

        return Str::contains($action, ['login', 'logout', 'auth']);
    }

    public function index()
    {
        $now = Carbon::now();
        $startOfWeek = $now->copy()->startOfWeek();
        $startOfNineMonthsAgo = $now->copy()->subMonths(8)->startOfMonth(); // incluye mes actual

        $user = auth()->user();
        $isSuperOwner = UserRedirector::isSuperAdmin($user);
        $selectedTenantId = (int) request()->query('tenant_id', 0);
        $tenantId = $isSuperOwner ? $selectedTenantId : (int) ($user->tenant_id ?? 0);
        $isDeliveryUser = (bool) ($user?->hasStoreRole('delivery') ?? false);
        $tenant = $tenantId > 0 ? Tenant::find($tenantId) : null;
        $tenantPlanCapabilities = TenantPlanCapabilities::forTenant($tenant);
        $tenantPublicUrl = $tenant?->slug ? url('/').'/'.$tenant->slug : null;
        $currentPlanPayment = $tenantPlanCapabilities->latestPaidPlan();
        $currentPlanDaysRemaining = null;

        if ($currentPlanPayment && !is_null($currentPlanPayment->expires_at)) {
            $expiresAt = Carbon::parse($currentPlanPayment->expires_at);
            $currentPlanDaysRemaining = $expiresAt->greaterThanOrEqualTo($now)
                ? $now->diffInDays($expiresAt)
                : (-1 * $expiresAt->diffInDays($now));
        }

        // Cargas (filtradas por tenant)
        $users = User::with('role')
            ->when($tenantId > 0, fn ($query) => $query->where('tenant_id', $tenantId))
            ->get();
        $productItems = Product::with(['category', 'images', 'variants'])
            ->when($tenantId > 0, fn ($query) => $query->where('tenant_id', $tenantId))
            ->get();

        $salesOrders = SalesOrder::with(['user', 'details', 'details.variant'])
            ->when($tenantId > 0, fn ($query) => $query->where('tenant_id', $tenantId))
            ->latest('date')->take(3)->get();

        $purchaseOrders = PurchaseOrder::with(['detalles'])
            ->when($tenantId > 0, fn ($query) => $query->where('tenant_id', $tenantId))
            ->latest('date')->take(3)->get();

        // Cantidad de ventas en la última semana (filtrado)
        $weeklySalesCount = SalesOrder::where('date', '>=', $startOfWeek)
            ->when($tenantId > 0, fn ($query) => $query->where('tenant_id', $tenantId))
            ->count();

        // Meses (últimos 9, incluyendo actual)
        $months = collect(range(8, 0))->map(function ($i) use ($now) {
            return $now->copy()->subMonths($i)->format('M');
        });

        // Productos con bajo stock (menos de 10 unidades)
        $lowStockProducts = Product::select('products.id', 'products.name', DB::raw('SUM(product_variants.stock) as total_stock'))
            ->join('product_variants', 'products.id', '=', 'product_variants.product_id')
            ->when($tenantId > 0, fn ($query) => $query->where('products.tenant_id', $tenantId))
            ->groupBy('products.id', 'products.name')
            ->orderBy('total_stock', 'asc')
            ->limit(4)
            ->get();

        // Ventas por mes (últimos 9 meses)
        $monthlySales = SalesOrder::selectRaw('DATE_FORMAT(date, "%b") as month, COUNT(*) as total')
            ->where('date', '>=', $startOfNineMonthsAgo)
            ->when($tenantId > 0, fn ($query) => $query->where('tenant_id', $tenantId))
            ->groupBy(DB::raw('YEAR(date), MONTH(date), DATE_FORMAT(date, "%b")'))
            ->orderByRaw('YEAR(date), MONTH(date)')
            ->get()
            ->pluck('total', 'month')
            ->toArray();

        $monthlySalesFormatted = $months->map(function ($month) use ($monthlySales) {
            return $monthlySales[$month] ?? 0;
        });

        $monthlyExpenseRows = Schema::hasTable('store_expenses')
            ? StoreExpense::selectRaw('DATE_FORMAT(spent_at, "%b") as month, SUM(COALESCE(amount_bs, amount)) as total')
                ->when($tenantId > 0, fn ($query) => $query->where('tenant_id', $tenantId))
                ->where('spent_at', '>=', $startOfNineMonthsAgo)
                ->groupBy(DB::raw('YEAR(spent_at), MONTH(spent_at), DATE_FORMAT(spent_at, "%b")'))
                ->orderByRaw('YEAR(spent_at), MONTH(spent_at)')
                ->get()
                ->pluck('total', 'month')
                ->toArray()
            : [];

        $monthlySalesAmountRows = SalesOrderDetail::query()
            ->join('sales_orders', 'sales_orders.id', '=', 'sales_order_details.sales_order_id')
            ->selectRaw('DATE_FORMAT(sales_orders.date, "%b") as month, SUM(sales_order_details.amount) as total')
            ->when($tenantId > 0, fn ($query) => $query->where('sales_orders.tenant_id', $tenantId))
            ->where('sales_orders.date', '>=', $startOfNineMonthsAgo)
            ->groupBy(DB::raw('YEAR(sales_orders.date), MONTH(sales_orders.date), DATE_FORMAT(sales_orders.date, "%b")'))
            ->orderByRaw('YEAR(sales_orders.date), MONTH(sales_orders.date)')
            ->get()
            ->pluck('total', 'month')
            ->toArray();

        $paymentsHasStatusColumn = Schema::hasColumn('payments', 'status');
        $salesOrdersHasStatusColumn = Schema::hasColumn('sales_orders', 'status');
        $monthlyCollectedRows = SalesOrder::query()
            ->join('payments', 'payments.sales_order_id', '=', 'sales_orders.id')
            ->selectRaw('DATE_FORMAT(sales_orders.date, "%b") as month, SUM(payments.amount) as total')
            ->when($tenantId > 0, fn ($query) => $query->where('sales_orders.tenant_id', $tenantId))
            ->where('sales_orders.date', '>=', $startOfNineMonthsAgo)
            ->when($paymentsHasStatusColumn, fn ($query) => $query->where('payments.status', 1))
            ->groupBy(DB::raw('YEAR(sales_orders.date), MONTH(sales_orders.date), DATE_FORMAT(sales_orders.date, "%b")'))
            ->orderByRaw('YEAR(sales_orders.date), MONTH(sales_orders.date)')
            ->get()
            ->pluck('total', 'month')
            ->toArray();

        $monthlyExpensesFormatted = $months->map(function ($month) use ($monthlyExpenseRows) {
            return round((float) ($monthlyExpenseRows[$month] ?? 0), 2);
        });

        $monthlySalesAmountFormatted = $months->map(function ($month) use ($monthlySalesAmountRows) {
            return round((float) ($monthlySalesAmountRows[$month] ?? 0), 2);
        });

        $monthlyCollectedFormatted = $months->map(function ($month) use ($monthlyCollectedRows) {
            return round((float) ($monthlyCollectedRows[$month] ?? 0), 2);
        });

        $monthlyProfitTrendFormatted = $months->map(function ($month) use ($monthlyCollectedRows, $monthlyExpenseRows) {
            $collected = (float) ($monthlyCollectedRows[$month] ?? 0);
            $expenses = (float) ($monthlyExpenseRows[$month] ?? 0);

            return round($collected - $expenses, 2);
        });

        $salesAmountCurrentMonth = (float) SalesOrder::query()
            ->when($tenantId > 0, fn ($query) => $query->where('tenant_id', $tenantId))
            ->whereBetween('date', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
            ->with('details')
            ->get()
            ->sum(fn ($order) => (float) $order->details->sum('amount'));

        $collectedAmountCurrentMonth = (float) SalesOrder::query()
            ->when($tenantId > 0, fn ($query) => $query->where('tenant_id', $tenantId))
            ->whereBetween('date', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
            ->with('payments')
            ->get()
            ->sum(function ($order) use ($paymentsHasStatusColumn) {
                return (float) ($paymentsHasStatusColumn
                    ? $order->payments->where('status', 1)->sum('amount')
                    : $order->payments->sum('amount'));
            });

        $expensesAmountCurrentMonth = Schema::hasTable('store_expenses')
            ? (float) StoreExpense::query()
                ->when($tenantId > 0, fn ($query) => $query->where('tenant_id', $tenantId))
                ->whereBetween('spent_at', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
                ->sum(DB::raw('COALESCE(amount_bs, amount)'))
            : 0.0;

        $receivablesAmount = (float) SalesOrder::with(['details', 'payments'])
            ->when($tenantId > 0, fn ($query) => $query->where('tenant_id', $tenantId))
            ->when($salesOrdersHasStatusColumn, fn ($query) => $query->where('status', '!=', 2))
            ->get()
            ->sum(function ($order) use ($paymentsHasStatusColumn) {
                $total = (float) $order->details->sum('amount');
                $paid = (float) ($paymentsHasStatusColumn
                    ? $order->payments->where('status', 1)->sum('amount')
                    : $order->payments->sum('amount'));

                return max(0, round($total - $paid, 2));
            });

        $financialSummary = [
            'sales' => round($salesAmountCurrentMonth, 2),
            'collected' => round($collectedAmountCurrentMonth, 2),
            'expenses' => round($expensesAmountCurrentMonth, 2),
            'receivables' => round($receivablesAmount, 2),
            'net' => round($collectedAmountCurrentMonth - $expensesAmountCurrentMonth, 2),
            'estimated_profit' => round($salesAmountCurrentMonth - $expensesAmountCurrentMonth, 2),
            'estimated_margin' => $salesAmountCurrentMonth > 0
                ? round((($salesAmountCurrentMonth - $expensesAmountCurrentMonth) / $salesAmountCurrentMonth) * 100, 2)
                : 0,
        ];

        $monthlyTrend = [
            'current' => (float) ($monthlyProfitTrendFormatted->last() ?? 0),
            'previous' => (float) ($monthlyProfitTrendFormatted->slice(-2, 1)->first() ?? 0),
        ];
        $monthlyTrend['delta'] = round($monthlyTrend['current'] - $monthlyTrend['previous'], 2);
        $monthlyTrend['delta_percent'] = $monthlyTrend['previous'] != 0.0
            ? round(($monthlyTrend['delta'] / abs($monthlyTrend['previous'])) * 100, 2)
            : null;

        $topExpenseCategoryRows = Schema::hasTable('store_expenses')
            ? StoreExpense::query()
                ->selectRaw("COALESCE(NULLIF(category, ''), 'Sin categoria') as category_name, SUM(COALESCE(amount_bs, amount)) as total_amount")
                ->where('tenant_id', $tenantId)
                ->groupBy('category_name')
                ->orderByDesc('total_amount')
                ->limit(5)
                ->get()
            : collect();

        $topExpenseCategoryLabels = $topExpenseCategoryRows->pluck('category_name')->toArray();
        $topExpenseCategoryTotals = $topExpenseCategoryRows
            ->map(fn ($row) => round((float) $row->total_amount, 2))
            ->toArray();

        // Top products (asegurando que el producto pertenezca al tenant)
        $topProducts = SalesOrderDetail::select('products.id', 'products.name', DB::raw('SUM(quantity) as total_sales'))
            ->join('product_variants', 'sales_order_details.product_variant_id', '=', 'product_variants.id')
            ->join('products', 'product_variants.product_id', '=', 'products.id')
            ->where('sales_order_details.tenant_id', $tenantId)
            ->where('products.tenant_id', $tenantId)
            ->groupBy('products.id', 'products.name')
            ->orderByDesc('total_sales')
            ->limit(5)
            ->get();

        $topProductNames = $topProducts->pluck('name')->toArray();
        $topProductSales = $topProducts->pluck('total_sales')->toArray();

        // Stats: usar counts filtrados por tenant (antes usabas Model::count() global)
        $stats = [
            ['name' => 'Usuarios', 'count' => User::where('tenant_id', $tenantId)->count(), 'link' => '/users'],
            ['name' => 'Productos', 'count' => Product::where('tenant_id', $tenantId)->count(), 'link' => '/products'],
            ['name' => 'Órdenes de Venta', 'count' => SalesOrder::where('tenant_id', $tenantId)->count(), 'link' => '/sales-orders'],
            ['name' => 'Órdenes de Compra', 'count' => PurchaseOrder::where('tenant_id', $tenantId)->count(), 'link' => '/purchase-orders'],
        ];

        $deliveryDashboardOrders = collect();
        $deliveryDashboardAmount = 0.0;

        if ($isDeliveryUser && $tenantPlanCapabilities->allowsDeliveryOperations()) {
            $deliveryDashboardOrders = SalesOrder::with(['user', 'details', 'payments'])
                ->where('tenant_id', $tenantId)
                ->where('deliver_status', 0)
                ->when($salesOrdersHasStatusColumn, fn ($query) => $query->where('status', '!=', 2))
                ->whereRaw('LOWER(COALESCE(preference, "")) LIKE ?', ['%delivery%'])
                ->orderByDesc('id')
                ->get()
                ->map(function (SalesOrder $order) {
                    $order->total_items = (int) $order->details->sum('quantity');
                    $order->order_total_amount = (float) $order->gross_total;
                    $order->approved_paid_amount = (float) $order->payments->where('status', 1)->sum('amount');
                    $order->registered_paid_amount = (float) $order->payments->where('status', '!=', 3)->sum('amount');
                    $order->effective_paid_amount = max($order->approved_paid_amount, $order->registered_paid_amount);
                    $order->pending_amount = max(0, round($order->order_total_amount - $order->effective_paid_amount, 2));

                    return $order;
                })
                ->filter(fn (SalesOrder $order) => $order->pending_amount <= 0.0001)
                ->values();

            $deliveryDashboardAmount = (float) $deliveryDashboardOrders->sum('effective_paid_amount');
        }

        return view('dashboard', compact(
            'stats',
            'purchaseOrders',
            'salesOrders',
            'weeklySalesCount',
            'monthlySalesFormatted',
            'monthlyExpensesFormatted',
            'monthlySalesAmountFormatted',
            'monthlyCollectedFormatted',
            'monthlyProfitTrendFormatted',
            'topProductNames',
            'topProductSales',
            'topExpenseCategoryLabels',
            'topExpenseCategoryTotals',
            'months',
            'lowStockProducts',
            'user',
            'tenantPublicUrl',
            'currentPlanPayment',
            'currentPlanDaysRemaining',
            'financialSummary',
            'monthlyTrend',
            'isDeliveryUser',
            'deliveryDashboardOrders',
            'deliveryDashboardAmount'
        ));
    }
    
    public function head()
    {
        $dollarRate = DollarRate::latest('created_at')->first();
        return view('layout.head', compact('dollarRate'));
    }

    public function addToWarehouseindex()
    {
        $warehouses = Warehouse::all();
        $productInventories = ProductInventory::all();
        $productItems = Product::query()->select(['id', 'name', 'tenant_id', 'is_active'])->get();
        $providers = Provider::query()->select(['id', 'name'])->get();
        return view('productWarehouse', compact('warehouses', 'productInventories', 'productItems', 'providers')); // Asegúrate de tener una vista para mostrar las almacens.
    }
    public function getWarehouses()
    {
        $warehouses = Warehouse::all();
        return response()->json($warehouses);
    }
    public function create()
    {
        return view('warehouses.create'); // Vista para crear una nueva almacen.
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:warehouses',
            'description' => 'nullable|string',
        ]);

        $warehouse = Warehouse::create([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        // return redirect()->route('warehouses.index')->with('success', 'almacen creada con éxito.');
        return response()->json(['message' => 'Warehouse created successfully', 'Warehouse' => $warehouse], 201);

    }
    public function storeInitialInventory(Request $request)
    {
        // Validar los datos recibidos
        $request->validate([
            'itemsSelected' => 'required|array',
            'itemsSelected.*.product_id' => 'required|integer',
            'itemsSelected.*.quantity' => 'required|integer|min:1',
            'itemsSelected.*.variant.id' => 'required|integer',
            'itemsSelected.*.warehouse.id' => 'required|integer',
        ]);

        $itemsSelected = $request->input('itemsSelected');

        // Obtener la fecha actual
        $currentDate = Carbon::now();

        // Preparar los datos para insertar
        $dataToInsert = [];
        foreach ($itemsSelected as $item) {
            $dataToInsert[] = [
                'product_variant_id' => $item['variant']['id'],
                'warehouse_id' => $item['warehouse']['id'],
                'quantity' => $item['quantity'],
                'arrival_date' => $currentDate,
                'expiration_date' => $currentDate,
                'created_at' => $currentDate,
                'updated_at' => $currentDate,
            ];
        }

        // Insertar en la base de datos
        ProductInventory::insert($dataToInsert);

        return response()->json([
            'message' => 'Inventario inicial registrado exitosamente.',
            'data' => $dataToInsert
        ], 201);
    }
    public function updateProductInventory(Request $request, $id)
    {   
        $productInventory = ProductInventory::findOrFail($id);
    
        // Actualizar la información
        $productInventory->warehouse_id = $request->warehouse_id;
        $productInventory->arrival_date = $request->arrival_date;
        $productInventory->expiration_date = $request->expiration_date;
    
        // Guardar los cambios
        $productInventory->save();
    
        return response()->json([
            'status' => 200,
            'message' => 'Producto actualizado con éxito',
            'productInventory' => $productInventory,
        ]);
    }
    public function show(Warehouse $warehouse)
    {
        return view('warehouses.show', compact('warehouse')); // Vista para mostrar una almacen específica.
    }

    public function edit(Warehouse $warehouse)
    {
        return view('warehouses.edit', compact('warehouse')); // Vista para editar una almacen.
    }

    public function update(Request $request, Warehouse $warehouse)
    {
        $request->validate([
            'name' => 'required|string|max:255|unique:warehouses,name,' . $warehouse->id,
            'description' => 'nullable|string',
        ]);

        $warehouse->update([
            'name' => $request->name,
            'description' => $request->description,
        ]);

        return redirect()->route('warehouses.index')->with('success', 'Almacen actualizada con éxito.');
    }

    public function destroy(Warehouse $warehouse)
    {
        $warehouse->delete();
        return redirect()->route('warehouses.index')->with('success', 'Almacen eliminada con éxito.');
    }
}
