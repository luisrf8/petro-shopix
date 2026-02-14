<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    Auth\AuthenticatedSessionController,
    Auth\RegisteredUserController,
    ProductController,
    ProviderController,
    CategoryController,
    PurchaseOrderController,
    SaleController,
    PaymentMethodController,
    IndexController,
    UserController,
    TenantController,
    PlanController,
    TaxController,
    LocationController
};

// RUTAS DE INVITADOS
Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'authenticate']);
    
    Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('register', [RegisteredUserController::class, 'store']);
});

// PÁGINAS PÚBLICAS
Route::get('/', [IndexController::class, 'landing'])->name('landing');
Route::get('/index', fn() => view('index'));
Route::get('/publicOrder/{id}', [SaleController::class, 'showPublicOrder']);
Route::get('/create-tenant-user', [TenantController::class, 'createIndexUser'])->name('createTenantUser');
Route::get('/get-states/{country}', [LocationController::class, 'getStates']);
Route::get('/get-cities/{state}', [LocationController::class, 'getCities']);

// RUTAS CON AUTENTICACIÓN
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [IndexController::class, 'index'])->name('dashboard');
    Route::post('/logout', [AuthenticatedSessionController::class, 'logout'])->name('logout');

    Route::get('/categories', [ProductController::class, 'categoriesIndex'])->name('categories.index');

    Route::get('/products', [ProductController::class, 'index'])->name('products.index');
    Route::get('/products/{category}', [ProductController::class, 'showByCategory'])->name('products.byCategory');
    Route::get('/products/product/{id}', [ProductController::class, 'showByProduct'])->name('productItem');
    Route::get('/createProduct', [ProductController::class, 'indexCreateProduct'])->name('createProductItem');
    Route::post('/products/{id}/taxes', [ProductController::class, 'updateTaxes']);

    Route::get('/users', [UserController::class, 'index'])->name('users');
    Route::get('/paymentMethods', [PaymentMethodController::class, 'index'])->name('paymentMethods.index');
    Route::get('/profile', fn() => view('profile'))->name('profile');

    // Ventas
    Route::get('/sales', [SaleController::class, 'index'])->name('sales');
    Route::get('/sales-orders', [SaleController::class, 'viewOrders'])->name('sales.orders');
    Route::get('/sales/{id}', [SaleController::class, 'showByOrder'])->name('sales.showByOrder');
    Route::post('/sales/{id}/return', [SaleController::class, 'processReturn'])->name('sales.return');
    Route::post('/create-sale', [SaleController::class, 'store']);
    Route::get('/sales-orders/{id}/pdf', [SaleController::class, 'downloadPdf']);

    // Compras
    Route::get('/purchase', [PurchaseOrderController::class, 'index'])->name('purchase');
    Route::get('/purchase-orders', [PurchaseOrderController::class, 'viewOrders'])->name('purchase.orders');
    Route::get('/order/{id}', [PurchaseOrderController::class, 'showByOrder'])->name('showByOrder');

    // Tenants
    Route::get('/tenants', [TenantController::class, 'index'])->name('tenant.index');
    Route::get('/create-tenant', [TenantController::class, 'createIndex'])->name('createTenant');
    Route::get('/tenant-store', [TenantController::class, 'getTenant'])->name('tenant.store');
    Route::post('/tenant-update', [TenantController::class, 'updateTenant'])->name('tenant.update');
    Route::resource('tenants', TenantController::class);

    // Planes
    Route::get('/plans', [PlanController::class, 'index'])->name('plans.index');

    // Impuestos
    Route::get('taxes', [TaxController::class, 'index'])->name('taxes');
    Route::post('taxes/create', [TaxController::class, 'store']);
    Route::post('taxes/update/{tax}', [TaxController::class, 'update']);
    Route::post('taxes/toggle/{tax}', [TaxController::class, 'toggleStatus']);

    // Logs
    Route::get('/logs', [IndexController::class, 'indexLog'])->name('logs.index');
});

require __DIR__.'/auth.php';

// 🔹 RUTAS PÚBLICAS DEL TENANT (al final)
Route::get('/{tenant:slug}', [TenantController::class, 'publicTenantindex'])->name('tenant.public');
Route::get('/{tenant:slug}/categorias', [TenantController::class, 'publicTenantCategory'])->name('tenant.public.categories');
Route::get('/{tenant:slug}/{product:id}', [TenantController::class, 'publicTenantProduct'])->name('tenant.public.product');
Route::post('/tenants-public', [TenantController::class, 'storePublic'])->name('tenants.storePublic'); // ← fuera del grupo auth
