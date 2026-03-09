<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    Auth\AuthenticatedSessionController,
    Auth\RegisteredUserController,
    ProductController,
    ProductVariantController,
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
    LocationController,
    MaterialPackageController,
    WarehouseController,
    NotificationController
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
Route::post('/tenant-ai-image', [TenantController::class, 'generateTenantImage'])->name('tenant.ai-image');

// RUTAS CON AUTENTICACIÓN
Route::middleware('auth')->group(function () {
    Route::get('/dashboard', [IndexController::class, 'index'])->name('dashboard');
    Route::post('/logout', [AuthenticatedSessionController::class, 'logout'])->name('logout');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/feed', [NotificationController::class, 'webFeed'])->name('notifications.feed');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');

    Route::get('/categories', [ProductController::class, 'categoriesIndex'])->middleware('role.name:1,2,5')->name('categories.index');

    Route::get('/products', [ProductController::class, 'index'])->middleware('role.name:1,2,5')->name('products.index');
    Route::post('/products/import-catalog', [ProductController::class, 'importCatalog'])->middleware('role.name:1,2,5')->name('products.importCatalogWeb');
    Route::post('/products/{product}/generate-codes', [ProductController::class, 'generateCodes'])->middleware('role.name:1,2,5')->name('products.generateCodesWeb');
    Route::get('/products/{category}', [ProductController::class, 'showByCategory'])->middleware('role.name:1,2,5')->name('products.byCategory');
    Route::get('/products/product/{id}', [ProductController::class, 'showByProduct'])->middleware('role.name:1,2,5')->name('productItem');
    Route::get('/createProduct', [ProductController::class, 'indexCreateProduct'])->middleware('role.name:1,2,5')->name('createProductItem');
    Route::post('/products/{id}/taxes', [ProductController::class, 'updateTaxes'])->middleware('role.name:1,2,5');
    Route::post('/variants/{productVariant}/generate-codes', [ProductVariantController::class, 'generateCodes'])->middleware('role.name:1,2,5')->name('variants.generateCodesWeb');
    Route::get('/variants/{productVariant}/qr-image', [ProductVariantController::class, 'qrImage'])->middleware('role.name:1,2,5')->name('variants.qrImage');

    Route::get('/users', [UserController::class, 'index'])->middleware('role.name:4')->name('users');
    Route::get('/paymentMethods', [PaymentMethodController::class, 'index'])->middleware('role.name:1,5')->name('paymentMethods.index');
    Route::get('/profile', fn() => view('profile'))->name('profile');

    // Ventas
    Route::get('/sales', [SaleController::class, 'index'])->middleware('role.name:1,2,5')->name('sales');
    Route::get('/sales-orders', [SaleController::class, 'viewOrders'])->middleware('role.name:1,2,5,almacen')->name('sales.orders');
    Route::get('/sales/{id}', [SaleController::class, 'showByOrder'])->middleware('role.name:1,2,5,almacen')->name('sales.showByOrder');
    Route::post('/sales/{id}/return', [SaleController::class, 'processReturn'])->middleware('role.name:1,2,5')->name('sales.return');
    Route::post('/create-sale', [SaleController::class, 'store'])->middleware('role.name:1,2,5');
    Route::post('/sales/scan-code', [SaleController::class, 'resolveScanCode'])->middleware('role.name:1,2,5')->name('sales.resolveScanCode');
    Route::get('/sales-orders/{id}/pdf', [SaleController::class, 'downloadPdf']);

    // Compras
    Route::get('/purchase', [PurchaseOrderController::class, 'index'])->middleware('role.name:1,2,5,almacen')->name('purchase');
    Route::get('/purchase-orders', [PurchaseOrderController::class, 'viewOrders'])->middleware('role.name:1,2,5,almacen')->name('purchase.orders');
    Route::get('/order/{id}', [PurchaseOrderController::class, 'showByOrder'])->middleware('role.name:1,2,5,almacen')->name('showByOrder');

    // Almacenes
    Route::get('/warehouses', [WarehouseController::class, 'index'])->middleware('role.name:1,2,5')->name('warehouses.index');
    Route::post('/warehouses', [WarehouseController::class, 'store'])->middleware('role.name:1,2,5')->name('warehouses.store');

    // Lista de materiales / paquetes
    Route::get('/materials', [MaterialPackageController::class, 'index'])->middleware('role.name:1,2,5')->name('materials.index');
    Route::post('/materials', [MaterialPackageController::class, 'store'])->middleware('role.name:1,2,5')->name('materials.store');
    Route::post('/materials/{id}/toggle-status', [MaterialPackageController::class, 'toggleStatus'])->middleware('role.name:1,2,5')->name('materials.toggleStatus');
    Route::post('/materials/{id}/generate-codes', [MaterialPackageController::class, 'generateCodes'])->middleware('role.name:1,2,5')->name('materials.generateCodes');

    // Tenants
    Route::get('/tenants', [TenantController::class, 'index'])->middleware('role.name:4')->name('tenant.index');
    Route::get('/create-tenant', [TenantController::class, 'createIndex'])->middleware('role.name:4')->name('createTenant');
    Route::get('/tenant-store', [TenantController::class, 'getTenant'])->middleware('role.name:1,5')->name('tenant.store');
    Route::post('/tenant-update', [TenantController::class, 'updateTenant'])->middleware('role.name:1,5')->name('tenant.update');
    Route::resource('tenants', TenantController::class)->middleware('role.name:4');

    // Planes
    Route::get('/plans', [PlanController::class, 'index'])->middleware('role.name:4')->name('plans.index');

    // Impuestos
    Route::get('taxes', [TaxController::class, 'index'])->middleware('role.name:4')->name('taxes');
    Route::post('taxes/create', [TaxController::class, 'store'])->middleware('role.name:4');
    Route::post('taxes/update/{tax}', [TaxController::class, 'update'])->middleware('role.name:4');
    Route::post('taxes/toggle/{tax}', [TaxController::class, 'toggleStatus'])->middleware('role.name:4');

    // Logs
    Route::get('/logs', [IndexController::class, 'indexLog'])->middleware('role.name:4')->name('logs.index');
});

require __DIR__.'/auth.php';

// 🔹 RUTAS PÚBLICAS DEL TENANT (al final)
Route::get('/{tenant:slug}', [TenantController::class, 'publicTenantindex'])->name('tenant.public');
Route::get('/{tenant:slug}/categorias', [TenantController::class, 'publicTenantCategory'])->name('tenant.public.categories');
Route::get('/{tenant:slug}/payment-methods', [TenantController::class, 'publicTenantPaymentMethods'])->name('tenant.public.paymentMethods');
Route::post('/{tenant:slug}/checkout/pro', [TenantController::class, 'publicTenantProCheckout'])->name('tenant.public.proCheckout');
Route::post('/{tenant:slug}/scan-code', [TenantController::class, 'publicTenantResolveScanCode'])->name('tenant.public.scanCode');
Route::get('/{tenant:slug}/{product:id}', [TenantController::class, 'publicTenantProduct'])->whereNumber('product')->name('tenant.public.product');
Route::post('/tenants-public', [TenantController::class, 'storePublic'])->name('tenants.storePublic'); // ← fuera del grupo auth
