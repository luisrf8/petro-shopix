<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use Illuminate\Http\Request;
use App\Models\ProductInventory;
use App\Models\Category;
use App\Models\Product;
use App\Models\SalesOrder;
use App\Models\User;
use App\Models\PurchaseOrder;
use App\Models\DollarRate;
use App\Models\Tenant;
use App\Models\Plan;
use App\Models\Country;
use App\Models\State;
use App\Models\City;
use Carbon\Carbon;
use App\Models\Log;
use App\Models\SalesOrderDetail;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class IndexController extends Controller
{
    public function landing()
    {
        $categories = Category::all()->take(3);
        $tenants = Tenant::all();
        $plans = Plan::all();
        
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
    
        $productItems = Product::with(['category', 'images', 'variants'])
            ->orderBy('created_at', 'desc')
            ->take(3)
            ->get();
    
        return view('ecommerce', compact('categories', 'productItems', 'tenants', 'plans'));
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
        $tenants = Tenant::with('categories:id,name,tenant_id')->get();
        $countryMap = Country::pluck('name', 'id')->mapWithKeys(fn ($name, $id) => [(string) $id => (string) $name]);
        $stateMap = State::pluck('name', 'id')->mapWithKeys(fn ($name, $id) => [(string) $id => (string) $name]);
        $cityMap = City::pluck('name', 'id')->mapWithKeys(fn ($name, $id) => [(string) $id => (string) $name]);

        $hasBusinessType = Schema::hasColumn('tenants', 'business_type');
        $hasEconomicActivity = Schema::hasColumn('tenants', 'economic_activity');

        $tenantsDirectory = $tenants->map(function (Tenant $tenant) use ($countryMap, $stateMap, $cityMap, $hasBusinessType, $hasEconomicActivity) {
            $countryName = $this->resolveLocationName($tenant->country, $countryMap);
            $stateName = $this->resolveLocationName($tenant->state, $stateMap);
            $cityName = $this->resolveLocationName($tenant->city, $cityMap);

            $businessType = $hasBusinessType ? trim((string) ($tenant->business_type ?? '')) : '';
            $economicActivity = $hasEconomicActivity ? trim((string) ($tenant->economic_activity ?? '')) : '';

            if ($businessType === '') {
                $businessType = $this->inferTenantType($tenant);
            }

            if ($economicActivity === '') {
                $economicActivity = $this->inferTenantActivity($tenant);
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
            return 'Tienda';
        }

        if (preg_match('/servicio|consultor|agencia|taller|reparaci[oó]n|sal[oó]n|spa|barber|estudio/', $haystack) === 1) {
            return 'Servicio';
        }

        return 'Tienda';
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
    public function indexLog()
    {
        $logs = Log::latest()->take(100)->get();        
        return view('logs', compact('logs'));
    }

    public function index()
    {
        $now = Carbon::now();
        $startOfWeek = $now->copy()->startOfWeek();
        $startOfNineMonthsAgo = $now->copy()->subMonths(8)->startOfMonth(); // incluye mes actual

        $user = auth()->user();
        $tenantId = $user->tenant_id;
        $tenant = Tenant::find($tenantId);
        $tenantPublicUrl = $tenant?->slug ? url('/').'/'.$tenant->slug : null;

        // Cargas (filtradas por tenant)
        $users = User::with('role')->where('tenant_id', $tenantId)->get();
        $productItems = Product::with(['category', 'images', 'variants'])
            ->where('tenant_id', $tenantId)
            ->get();

        $salesOrders = SalesOrder::with(['user', 'details', 'details.variant'])
            ->where('tenant_id', $tenantId)
            ->latest('date')->take(3)->get();

        $purchaseOrders = PurchaseOrder::with(['detalles'])
            ->where('tenant_id', $tenantId)
            ->latest('date')->take(3)->get();

        // Cantidad de ventas en la última semana (filtrado)
        $weeklySalesCount = SalesOrder::where('date', '>=', $startOfWeek)
            ->where('tenant_id', $tenantId)
            ->count();

        // Meses (últimos 9, incluyendo actual)
        $months = collect(range(8, 0))->map(function ($i) use ($now) {
            return $now->copy()->subMonths($i)->format('M');
        });

        // Productos con bajo stock (menos de 10 unidades)
        $lowStockProducts = Product::select('products.id', 'products.name', DB::raw('SUM(product_variants.stock) as total_stock'))
            ->join('product_variants', 'products.id', '=', 'product_variants.product_id')
            ->where('products.tenant_id', $tenantId)
            ->groupBy('products.id', 'products.name')
            ->orderBy('total_stock', 'asc')
            ->limit(4)
            ->get();

        // Ventas por mes (últimos 9 meses)
        $monthlySales = SalesOrder::selectRaw('DATE_FORMAT(date, "%b") as month, COUNT(*) as total')
            ->where('date', '>=', $startOfNineMonthsAgo)
            ->where('tenant_id', $tenantId)
            ->groupBy(DB::raw('YEAR(date), MONTH(date), DATE_FORMAT(date, "%b")'))
            ->orderByRaw('YEAR(date), MONTH(date)')
            ->get()
            ->pluck('total', 'month')
            ->toArray();

        $monthlySalesFormatted = $months->map(function ($month) use ($monthlySales) {
            return $monthlySales[$month] ?? 0;
        });

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

        return view('dashboard', compact(
            'stats',
            'purchaseOrders',
            'salesOrders',
            'weeklySalesCount',
            'monthlySalesFormatted',
            'topProductNames',
            'topProductSales',
            'months',
            'lowStockProducts',
            'user',
            'tenantPublicUrl'
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
        $productItems = Product::all();
        $providers = Provider::all();
        $warehouses = Warehouse::all();
        return view('productWarehouse', compact('warehouses', 'productInventories', 'productItems', 'providers', 'warehouses')); // Asegúrate de tener una vista para mostrar las almacens.
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
