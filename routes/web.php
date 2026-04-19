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
    CustomerController,
    StoreExpenseController,
    TenantController,
    PlanController,
    TaxController,
    LocationController,
    MaterialPackageController,
    WarehouseController,
    NotificationController,
    PushSubscriptionController,
    HelpPreferenceController,
    GoogleDriveController,
    ReportController,
    ElectronicInvoicingController
};

// RUTAS DE INVITADOS
Route::middleware('guest')->group(function () {
    Route::get('admin/login', [AuthenticatedSessionController::class, 'createAdmin'])->name('login');
    Route::post('admin/login', [AuthenticatedSessionController::class, 'authenticateAdmin'])->name('admin.login.submit');
    Route::post('client/login', [AuthenticatedSessionController::class, 'authenticateCustomer'])->name('client.login.submit');
    Route::get('client/login/{provider}', [AuthenticatedSessionController::class, 'redirectToCustomerProvider'])
        ->where('provider', 'google|facebook|apple')
        ->name('client.social.redirect');
    Route::get('client/login/{provider}/callback', [AuthenticatedSessionController::class, 'handleCustomerProviderCallback'])
        ->where('provider', 'google|facebook|apple')
        ->name('client.social.callback');
    Route::get('login', function () {
        return redirect()->route('login');
    });
    Route::post('login', [AuthenticatedSessionController::class, 'authenticateAdmin']);
    
    Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('register', [RegisteredUserController::class, 'store']);
});

// PÁGINAS PÚBLICAS
Route::get('/', [IndexController::class, 'landing'])->name('landing');
Route::get('/landings', [IndexController::class, 'landingDirectory'])->name('landing.directory');
Route::get('/index', fn() => view('index'));
Route::get('/manifest.webmanifest', function (\Illuminate\Http\Request $request) {
    $name = trim((string) $request->query('name', config('app.name', 'Shopix')));
    $startUrl = (string) $request->query('start_url', '/');
    $themeColor = '#' . ltrim((string) $request->query('theme', '0F172A'), '#');

    if ($name === '') {
        $name = config('app.name', 'Shopix');
    }

    if ($startUrl === '' || !str_starts_with($startUrl, '/')) {
        $startUrl = '/';
    }

    if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $themeColor)) {
        $themeColor = '#0F172A';
    }

    return response()->json([
        'name' => $name,
        'short_name' => \Illuminate\Support\Str::limit($name, 12, ''),
        'start_url' => $startUrl,
        'scope' => '/',
        'display' => 'standalone',
        'background_color' => '#FFFFFF',
        'theme_color' => $themeColor,
        'icons' => [
            [
                'src' => url('/assets/img/shopix5.png'),
                'sizes' => '192x192',
                'type' => 'image/png',
                'purpose' => 'any maskable',
            ],
            [
                'src' => url('/assets/img/shopix5.png'),
                'sizes' => '512x512',
                'type' => 'image/png',
                'purpose' => 'any maskable',
            ],
        ],
    ], 200, ['Content-Type' => 'application/manifest+json']);
})->name('tenant.pwa.manifest');
Route::get('/storage/gdrive/{fileId}', [GoogleDriveController::class, 'streamImage'])->where('fileId', '.*')->name('storage.gdrive.proxy');
Route::get('/publicOrder/{id}', [SaleController::class, 'showPublicOrder']);
Route::get('/publicOrder/{id}/pdfs/{type}', [SaleController::class, 'downloadStoredPdf'])->whereIn('type', ['invoice', 'delivery'])->name('public.order.pdf');
Route::get('/create-tenant-user', [TenantController::class, 'createIndexUser'])->name('createTenantUser');
Route::get('/get-countries', [LocationController::class, 'getCountries']);
Route::get('/get-states/{country}', [LocationController::class, 'getStates']);
Route::get('/get-cities/{state}', [LocationController::class, 'getCities']);
Route::post('/tenant-ai-image', [TenantController::class, 'generateTenantImage'])->name('tenant.ai-image');

// RUTAS CON AUTENTICACIÓN
Route::middleware(['auth', 'backoffice.access', 'free.plan.access', 'basic.plan.access', 'inactive.tenant.restrict'])->group(function () {
    Route::get('/settings/google-drive/oauth', [GoogleDriveController::class, 'oauthStatus'])->name('google-drive.oauth.status');
    Route::get('/settings/google-drive/connect', [GoogleDriveController::class, 'redirectToGoogle'])->name('google-drive.connect');
    Route::get('/settings/google-drive/callback', [GoogleDriveController::class, 'handleGoogleCallback'])->name('google-drive.callback');

    Route::get('/dashboard', [IndexController::class, 'index'])->name('dashboard');
    Route::post('/logout', [AuthenticatedSessionController::class, 'logout'])->name('logout');
    Route::get('/notifications', [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/feed', [NotificationController::class, 'webFeed'])->name('notifications.feed');
    Route::post('/notifications/{id}/read', [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/push-subscriptions', [PushSubscriptionController::class, 'store'])->name('push-subscriptions.store');
    Route::delete('/push-subscriptions', [PushSubscriptionController::class, 'destroy'])->name('push-subscriptions.destroy');
    Route::get('/help-preferences', [HelpPreferenceController::class, 'show'])->name('help.preferences.show');
    Route::post('/help-preferences/global', [HelpPreferenceController::class, 'updateGlobal'])->name('help.preferences.global');
    Route::post('/help-preferences/route', [HelpPreferenceController::class, 'updateRoute'])->name('help.preferences.route');

    Route::get('/categories', [ProductController::class, 'categoriesIndex'])->middleware('role.name:owner,admin,administrador,vendor,vendedor,seller,almacen,almacenista,warehouse')->name('categories.index');

    Route::get('/products', [ProductController::class, 'index'])->middleware('role.name:owner,admin,administrador,vendor,vendedor,seller,almacen,almacenista,warehouse')->name('products.index');
    Route::post('/create-product', [ProductController::class, 'create'])->middleware('role.name:owner,admin,administrador,vendor,vendedor,seller,almacen,almacenista,warehouse')->name('products.createWeb');
    Route::post('/products/import-catalog', [ProductController::class, 'importCatalog'])->middleware('role.name:owner,admin,administrador')->name('products.importCatalogWeb');
    Route::post('/products/{product}/generate-codes', [ProductController::class, 'generateCodes'])->middleware('role.name:owner,admin,administrador,vendor,vendedor,seller,almacen,almacenista,warehouse')->name('products.generateCodesWeb');
    Route::get('/products/{category}', [ProductController::class, 'showByCategory'])->middleware('role.name:owner,admin,administrador,vendor,vendedor,seller,almacen,almacenista,warehouse')->name('products.byCategory');
    Route::get('/products/product/{id}', [ProductController::class, 'showByProduct'])->middleware('role.name:owner,admin,administrador,vendor,vendedor,seller,almacen,almacenista,warehouse')->name('productItem');
    Route::get('/createProduct', [ProductController::class, 'indexCreateProduct'])->middleware('role.name:owner,admin,administrador,vendor,vendedor,seller,almacen,almacenista,warehouse')->name('createProductItem');
    Route::post('/products/{id}/taxes', [ProductController::class, 'updateTaxes'])->middleware('role.name:owner,admin,administrador');
    Route::post('/variants/{productVariant}/generate-codes', [ProductVariantController::class, 'generateCodes'])->middleware('role.name:owner,admin,administrador,vendor,vendedor,seller,almacen,almacenista,warehouse')->name('variants.generateCodesWeb');
    Route::get('/variants/{productVariant}/qr-image', [ProductVariantController::class, 'qrImage'])->middleware('role.name:owner,admin,administrador,vendor,vendedor,seller,almacen,almacenista,warehouse')->name('variants.qrImage');

    Route::get('/users', [UserController::class, 'index'])->middleware('role.name:4')->name('users');
    Route::get('/paymentMethods', [PaymentMethodController::class, 'index'])->middleware('role.name:owner,admin,administrador')->name('paymentMethods.index');
    Route::get('/profile', fn() => view('profile'))->name('profile');

    // Ventas
    Route::get('/sales', [SaleController::class, 'index'])->middleware('role.name:owner,admin,administrador,vendor,vendedor,seller')->name('sales');
    Route::get('/customers', [CustomerController::class, 'index'])->middleware('role.name:owner,admin,administrador,vendor,vendedor,seller')->name('customers.index');
    Route::post('/customers', [CustomerController::class, 'store'])->middleware('role.name:owner,admin,administrador,vendor,vendedor,seller')->name('customers.store');
    Route::put('/customers/{customer}', [CustomerController::class, 'update'])->middleware('role.name:owner,admin,administrador,vendor,vendedor,seller')->name('customers.update');
    Route::post('/customers/{customer}/toggle-status', [CustomerController::class, 'toggleStatus'])->middleware('role.name:owner,admin,administrador,vendor,vendedor,seller')->name('customers.toggleStatus');
    Route::get('/accounts-receivable', [SaleController::class, 'viewReceivables'])->middleware('role.name:owner,admin,administrador,vendor,vendedor,seller')->name('accounts.receivable.index');
    Route::get('/paid-pending-deliveries', [SaleController::class, 'viewPaidPendingDelivery'])->middleware('role.name:owner,admin,administrador,vendor,vendedor,seller,almacen,almacenista,warehouse')->name('sales.paidPendingDeliveries.index');
    Route::get('/sales-orders', [SaleController::class, 'viewOrders'])->middleware('role.name:owner,admin,administrador,vendor,vendedor,seller')->name('sales.orders');
    Route::get('/sales-orders/pending-delivery', [SaleController::class, 'viewPendingDeliveryOrders'])->middleware('role.name:owner,admin,administrador,almacen,almacenista,warehouse')->name('sales.orders.pendingDelivery');
    Route::get('/sales/{id}', [SaleController::class, 'showByOrder'])->middleware('role.name:owner,admin,administrador,vendor,vendedor,seller,almacen,almacenista,warehouse')->name('sales.showByOrder');
    Route::post('/sales-orders/{order}/electronic/emit', [ElectronicInvoicingController::class, 'emit'])->middleware('role.name:owner,admin,administrador,vendor,vendedor,seller')->name('sales.electronic.emit');
    Route::post('/sales-orders/{order}/electronic/status', [ElectronicInvoicingController::class, 'status'])->middleware('role.name:owner,admin,administrador,vendor,vendedor,seller')->name('sales.electronic.status');
    Route::post('/sales-orders/{order}/electronic/download', [ElectronicInvoicingController::class, 'download'])->middleware('role.name:owner,admin,administrador,vendor,vendedor,seller')->name('sales.electronic.download');
    Route::post('/sales-orders/{order}/electronic/send-email', [ElectronicInvoicingController::class, 'sendEmail'])->middleware('role.name:owner,admin,administrador,vendor,vendedor,seller')->name('sales.electronic.sendEmail');
    Route::post('/sales-orders/{order}/electronic/annul', [ElectronicInvoicingController::class, 'annul'])->middleware('role.name:owner,admin,administrador')->name('sales.electronic.annul');
    Route::post('/sales-orders/{order}/electronic/metadata', [ElectronicInvoicingController::class, 'metadata'])->middleware('role.name:owner,admin,administrador,vendor,vendedor,seller')->name('sales.electronic.metadata');
    Route::post('/sales-orders/{order}/document-mode', [SaleController::class, 'updateDocumentMode'])->middleware('role.name:owner,admin,administrador,vendor,vendedor,seller')->name('sales.documentMode.update');
    Route::get('/my-electronic-documents', [ElectronicInvoicingController::class, 'tenantIndex'])->middleware('role.name:owner,admin,administrador,vendor,vendedor,seller')->name('sales.electronic.documents.tenant');
    Route::get('/electronic-documents', [ElectronicInvoicingController::class, 'index'])->middleware('role.name:4')->name('electronic.documents.index');
    Route::post('/electronic-documents/{electronicDocument}/retry', [ElectronicInvoicingController::class, 'retry'])->middleware('role.name:4')->name('electronic.documents.retry');
    Route::post('/sales/{id}/return', [SaleController::class, 'processReturn'])->middleware('role.name:owner,admin,administrador,vendor,vendedor,seller')->name('sales.return');
    Route::post('/create-sale', [SaleController::class, 'store'])->middleware('role.name:owner,admin,administrador,vendor,vendedor,seller');
    Route::post('/sales/scan-code', [SaleController::class, 'resolveScanCode'])->middleware('role.name:owner,admin,administrador,vendor,vendedor,seller')->name('sales.resolveScanCode');
    Route::get('/sales-orders/{id}/pdf', [SaleController::class, 'downloadPdf'])->middleware('role.name:owner,admin,administrador,vendor,vendedor,seller');
    Route::get('/sales-orders/{id}/pdfs/{type}', [SaleController::class, 'downloadStoredPdf'])->whereIn('type', ['invoice', 'delivery'])->middleware('role.name:owner,admin,administrador,vendor,vendedor,seller')->name('sales.orders.pdfs');

    // Reportes PDF
    Route::get('/reports', [ReportController::class, 'index'])->middleware('role.name:owner,admin,administrador')->name('reports.index');
    Route::get('/reports/products/top-selling/pdf', [ReportController::class, 'topSellingProductsPdf'])->middleware('role.name:owner,admin,administrador')->name('reports.products.topSelling.pdf');
    Route::get('/reports/products/top-selling/excel', [ReportController::class, 'topSellingProductsExcel'])->middleware('role.name:owner,admin,administrador')->name('reports.products.topSelling.excel');
    Route::get('/reports/inventory/entries/pdf', [ReportController::class, 'inventoryEntriesPdf'])->middleware('role.name:owner,admin,administrador')->name('reports.inventory.entries.pdf');
    Route::get('/reports/inventory/entries/excel', [ReportController::class, 'inventoryEntriesExcel'])->middleware('role.name:owner,admin,administrador')->name('reports.inventory.entries.excel');
    Route::get('/reports/sales/management/pdf', [ReportController::class, 'salesManagementPdf'])->middleware('role.name:owner,admin,administrador')->name('reports.sales.management.pdf');
    Route::get('/reports/sales/management/excel', [ReportController::class, 'salesManagementExcel'])->middleware('role.name:owner,admin,administrador')->name('reports.sales.management.excel');
    Route::get('/reports/inventory/total/pdf', [ReportController::class, 'inventoryTotalPdf'])->middleware('role.name:owner,admin,administrador')->name('reports.inventory.total.pdf');
    Route::get('/reports/inventory/total/excel', [ReportController::class, 'inventoryTotalExcel'])->middleware('role.name:owner,admin,administrador')->name('reports.inventory.total.excel');
    Route::get('/reports/system/modules/pdf', [ReportController::class, 'systemModulesPdf'])->middleware('role.name:owner,admin,administrador')->name('reports.system.modules.pdf');
    Route::get('/reports/system/modules/excel', [ReportController::class, 'systemModulesExcel'])->middleware('role.name:owner,admin,administrador')->name('reports.system.modules.excel');
    Route::get('/reports/customers/pdf', [ReportController::class, 'customersPdf'])->middleware('role.name:owner,admin,administrador')->name('reports.customers.pdf');
    Route::get('/reports/customers/excel', [ReportController::class, 'customersExcel'])->middleware('role.name:owner,admin,administrador')->name('reports.customers.excel');
    Route::get('/reports/accounts-receivable/pdf', [ReportController::class, 'receivablesPdf'])->middleware('role.name:owner,admin,administrador')->name('reports.accountsReceivable.pdf');
    Route::get('/reports/accounts-receivable/excel', [ReportController::class, 'receivablesExcel'])->middleware('role.name:owner,admin,administrador')->name('reports.accountsReceivable.excel');
    Route::get('/reports/store-expenses/pdf', [ReportController::class, 'storeExpensesPdf'])->middleware('role.name:owner,admin,administrador')->name('reports.storeExpenses.pdf');
    Route::get('/reports/store-expenses/excel', [ReportController::class, 'storeExpensesExcel'])->middleware('role.name:owner,admin,administrador')->name('reports.storeExpenses.excel');

    // Compras
    Route::get('/purchase', [PurchaseOrderController::class, 'index'])->middleware('role.name:owner,admin,administrador,almacen,almacenista,warehouse')->name('purchase');
    Route::get('/providers', [ProviderController::class, 'index'])->middleware('role.name:owner,admin,administrador,almacen,almacenista,warehouse')->name('providers.index');
    Route::post('/providers', [ProviderController::class, 'store'])->middleware('role.name:owner,admin,administrador,almacen,almacenista,warehouse')->name('providers.store');
    Route::put('/providers/{provider}', [ProviderController::class, 'update'])->middleware('role.name:owner,admin,administrador,almacen,almacenista,warehouse')->name('providers.update');
    Route::post('/providers/{provider}/toggle-status', [ProviderController::class, 'toggleStatus'])->middleware('role.name:owner,admin,administrador,almacen,almacenista,warehouse')->name('providers.toggleStatus');
    Route::get('/store-expenses', [StoreExpenseController::class, 'index'])->middleware('role.name:owner,admin,administrador')->name('store-expenses.index');
    Route::post('/store-expenses', [StoreExpenseController::class, 'store'])->middleware('role.name:owner,admin,administrador')->name('store-expenses.store');
    Route::put('/store-expenses/{expense}', [StoreExpenseController::class, 'update'])->middleware('role.name:owner,admin,administrador')->name('store-expenses.update');
    Route::get('/purchase-orders', [PurchaseOrderController::class, 'viewOrders'])->middleware('role.name:owner,admin,administrador,almacen,almacenista,warehouse')->name('purchase.orders');
    Route::get('/order/{id}', [PurchaseOrderController::class, 'showByOrder'])->middleware('role.name:owner,admin,administrador,almacen,almacenista,warehouse')->name('showByOrder');

    // Almacenes
    Route::get('/warehouses', [WarehouseController::class, 'index'])->middleware('role.name:owner,admin,administrador,vendor,vendedor,seller,almacen,almacenista,warehouse')->name('warehouses.index');
    Route::post('/warehouses', [WarehouseController::class, 'store'])->middleware('role.name:owner,admin,administrador')->name('warehouses.store');
    Route::put('/warehouses/{warehouse}', [WarehouseController::class, 'update'])->middleware('role.name:owner,admin,administrador')->name('warehouses.update');
    Route::post('/warehouses/movements', [WarehouseController::class, 'storeMovement'])->middleware('role.name:owner,admin,administrador,vendor,vendedor,seller,almacen,almacenista,warehouse')->name('warehouses.movements.store');
    Route::put('/warehouses/movements/{movement}', [WarehouseController::class, 'updateMovement'])->middleware('role.name:owner,admin,administrador,vendor,vendedor,seller,almacen,almacenista,warehouse')->name('warehouses.movements.update');

    // Lista de materiales / paquetes
    Route::get('/materials', [MaterialPackageController::class, 'index'])->middleware('role.name:owner,admin,administrador,vendor,vendedor,seller,almacen,almacenista,warehouse')->name('materials.index');
    Route::post('/materials', [MaterialPackageController::class, 'store'])->middleware('role.name:owner,admin,administrador,vendor,vendedor,seller,almacen,almacenista,warehouse')->name('materials.store');
    Route::put('/materials/{id}', [MaterialPackageController::class, 'update'])->middleware('role.name:owner,admin,administrador,vendor,vendedor,seller,almacen,almacenista,warehouse')->name('materials.update');
    Route::post('/materials/{id}/toggle-status', [MaterialPackageController::class, 'toggleStatus'])->middleware('role.name:owner,admin,administrador,vendor,vendedor,seller,almacen,almacenista,warehouse')->name('materials.toggleStatus');
    Route::post('/materials/{id}/generate-codes', [MaterialPackageController::class, 'generateCodes'])->middleware('role.name:owner,admin,administrador,vendor,vendedor,seller,almacen,almacenista,warehouse')->name('materials.generateCodes');

    // Tenants
    Route::get('/tenants', [TenantController::class, 'index'])->middleware('role.name:4')->name('tenant.index');
    Route::get('/tenant-payments', [TenantController::class, 'paymentsIndex'])->middleware('role.name:4')->name('tenant.payments.index');
    Route::get('/create-tenant', [TenantController::class, 'createIndex'])->middleware('role.name:4')->name('createTenant');
    Route::get('/tenant-store', [TenantController::class, 'getTenant'])->middleware('role.name:owner,admin,administrador')->name('tenant.store');
    Route::post('/tenant-update', [TenantController::class, 'updateTenant'])->middleware('role.name:owner,admin,administrador')->name('tenant.update');
    Route::post('/tenant-store/plan-payment-request', [TenantController::class, 'submitPlanPaymentRequest'])->middleware('role.name:owner')->name('tenant.planPayment.request');
    Route::post('/tenants/{tenant}/plan-payments/{payment}/approve', [TenantController::class, 'approvePlanPayment'])->middleware('role.name:4')->name('tenant.planPayment.approve');
    Route::post('/tenants/{tenant}/plan-payments/{payment}/cutoff', [TenantController::class, 'updatePlanPaymentCutoffDate'])->middleware('role.name:4')->name('tenant.planPayment.cutoff.update');
    Route::post('/tenants/{tenant}/plan-payments/{payment}/reject', [TenantController::class, 'rejectPlanPayment'])->middleware('role.name:4')->name('tenant.planPayment.reject');
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
