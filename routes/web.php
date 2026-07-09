<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\{
    AppointmentController,
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
    AccountsPayableController,
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
    ElectronicInvoicingController,
    SalesFiscalController,
    SellerCommissionController,
    WithholdingController,
    ProjectModuleController
};

// RUTAS DE INVITADOS
Route::middleware('guest')->group(function () {
    Route::get('admin/login', [AuthenticatedSessionController::class, 'createAdmin'])->name('login');
    Route::post('admin/login', [AuthenticatedSessionController::class, 'authenticateAdmin'])->name('admin.login.submit');
    Route::post('client/login', [AuthenticatedSessionController::class, 'authenticateCustomer'])->name('client.login.submit');
    Route::get('client/login/{provider}', [AuthenticatedSessionController::class, 'redirectToCustomerProvider'])
        ->where('provider', 'google')
        ->name('client.social.redirect');
    Route::get('client/login/{provider}/callback', [AuthenticatedSessionController::class, 'handleCustomerProviderCallback'])
        ->where('provider', 'google')
        ->name('client.social.callback');
    Route::get('login', function () {
        return redirect('/admin/login');
    });
    Route::post('login', [AuthenticatedSessionController::class, 'authenticateAdmin']);
    
    Route::get('register', [RegisteredUserController::class, 'create'])->name('register');
    Route::post('register', [RegisteredUserController::class, 'store']);
});

// PÁGINAS PÚBLICAS
Route::get('/', [IndexController::class, 'landing'])->name('landing');
Route::get('/landings', [IndexController::class, 'landingDirectory'])->name('landing.directory');
Route::get('/legal/terms-and-conditions.pdf', [TenantController::class, 'termsAndConditionsPdf'])->name('legal.terms.pdf');
Route::get('/index', fn() => view('index'));
Route::get('/manifest.webmanifest', function (\Illuminate\Http\Request $request) {
    $name = trim((string) $request->query('name', config('app.name', 'Shopix')));
    $startUrl = (string) $request->query('start_url', '/');
    $themeColor = '#' . ltrim((string) $request->query('theme', '0F172A'), '#');
    $iconVariant = trim((string) $request->query('icon_variant', 'client'));

    if ($name === '') {
        $name = config('app.name', 'Shopix');
    }

    if ($startUrl === '' || !str_starts_with($startUrl, '/')) {
        $startUrl = '/';
    }

    if (!preg_match('/^#[0-9A-Fa-f]{6}$/', $themeColor)) {
        $themeColor = '#0F172A';
    }

    if (!in_array($iconVariant, ['client', 'admin'], true)) {
        $iconVariant = 'client';
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
                'src' => route('pwa.icon', ['size' => 192, 'variant' => $iconVariant]),
                'sizes' => '192x192',
                'type' => 'image/png',
                'purpose' => 'any maskable',
            ],
            [
                'src' => route('pwa.icon', ['size' => 512, 'variant' => $iconVariant]),
                'sizes' => '512x512',
                'type' => 'image/png',
                'purpose' => 'any maskable',
            ],
        ],
    ], 200, ['Content-Type' => 'application/manifest+json']);
})->name('tenant.pwa.manifest');

Route::get('/pwa-icon/{size}.png', function (int $size) {
    abort_unless(in_array($size, [180, 192, 512], true), 404);

    $variant = request()->query('variant', 'client');
    $sourceFile = $variant === 'admin' ? 'shopix8.png' : 'shopix7.png';
    $sourcePath = public_path('assets/img/' . $sourceFile);
    abort_unless(is_file($sourcePath), 404);

    if (!function_exists('imagecreatetruecolor') || !function_exists('imagecreatefrompng')) {
        return response()->file($sourcePath, ['Content-Type' => 'image/png']);
    }

    $sourceImage = @imagecreatefrompng($sourcePath);
    abort_unless($sourceImage !== false, 404);

    $sourceWidth = imagesx($sourceImage);
    $sourceHeight = imagesy($sourceImage);
    $icon = imagecreatetruecolor($size, $size);

    imagealphablending($icon, true);
    imagesavealpha($icon, false);
    $background = imagecolorallocate($icon, 255, 255, 255);
    imagefilledrectangle($icon, 0, 0, $size, $size, $background);

    $padding = (int) floor($size * 0.12);
    $sourceAspectRatio = $sourceWidth / max($sourceHeight, 1);

    if ($sourceAspectRatio >= 2.2) {
        $targetWidth = max((int) round($size * 0.84), 1);
        $targetHeight = max((int) round($size * 0.34), 1);
    } else {
        $availableSize = max($size - ($padding * 2), 1);
        $scale = min($availableSize / max($sourceWidth, 1), $availableSize / max($sourceHeight, 1));
        $targetWidth = max((int) round($sourceWidth * $scale), 1);
        $targetHeight = max((int) round($sourceHeight * $scale), 1);
    }

    $destinationX = (int) floor(($size - $targetWidth) / 2);
    $destinationY = (int) floor(($size - $targetHeight) / 2);

    imagecopyresampled(
        $icon,
        $sourceImage,
        $destinationX,
        $destinationY,
        0,
        0,
        $targetWidth,
        $targetHeight,
        $sourceWidth,
        $sourceHeight
    );

    ob_start();
    imagepng($icon);
    $binary = ob_get_clean();

    imagedestroy($icon);
    imagedestroy($sourceImage);

    return response($binary, 200, [
        'Content-Type' => 'image/png',
        'Cache-Control' => 'public, max-age=604800',
    ]);
})->name('pwa.icon');
Route::get('/storage/gdrive/{fileId}', [GoogleDriveController::class, 'streamImage'])->where('fileId', '.*')->name('storage.gdrive.proxy');
Route::get('/publicOrder/{id}', [SaleController::class, 'showPublicOrder']);
Route::get('/publicOrder/{id}/pdfs/{type}', [SaleController::class, 'downloadStoredPdf'])->whereIn('type', ['invoice', 'delivery'])->name('public.order.pdf');
Route::get('/create-tenant-user', [TenantController::class, 'createIndexUser'])->name('createTenantUser');
Route::get('/get-countries', [LocationController::class, 'getCountries']);
Route::get('/get-states/{country}', [LocationController::class, 'getStates']);
Route::get('/get-cities/{state}', [LocationController::class, 'getCities']);
Route::post('/tenant-ai-image', [TenantController::class, 'generateTenantImage'])->name('tenant.ai-image');
Route::post('/tenant-ai-copy', [TenantController::class, 'generateTenantCopy'])->name('tenant.ai-copy');
Route::post('/tenant-ai-setup', [TenantController::class, 'generateTenantSetup'])->name('tenant.ai-setup');

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
    Route::post('/products/{id}/update', [ProductController::class, 'update'])->middleware('role.name:owner,admin,administrador,vendor,vendedor,seller,almacen,almacenista,warehouse')->name('products.updateWeb');
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
    Route::get('/paymentMethods/rates/export/{format}', [PaymentMethodController::class, 'exportRateHistory'])->where('format', 'csv|excel|pdf')->middleware('role.name:owner,admin,administrador')->name('paymentMethods.rates.export');
    Route::get('/profile', fn() => view('profile'))->name('profile');

    // Ventas
    Route::get('/sales', [SaleController::class, 'index'])->middleware('role.name:owner,admin,administrador,vendor,vendedor,seller')->name('sales');
    Route::get('/customers', [CustomerController::class, 'index'])->middleware('role.name:owner,admin,administrador,vendor,vendedor,seller')->name('customers.index');
    Route::post('/customers', [CustomerController::class, 'store'])->middleware('role.name:owner,admin,administrador,vendor,vendedor,seller')->name('customers.store');
    Route::put('/customers/{customer}', [CustomerController::class, 'update'])->middleware('role.name:owner,admin,administrador,vendor,vendedor,seller')->name('customers.update');
    Route::post('/customers/{customer}/toggle-status', [CustomerController::class, 'toggleStatus'])->middleware('role.name:owner,admin,administrador,vendor,vendedor,seller')->name('customers.toggleStatus');
    Route::get('/accounts-receivable', [SaleController::class, 'viewReceivables'])->middleware('role.name:owner,admin,administrador,vendor,vendedor,seller')->name('accounts.receivable.index');
    Route::get('/paid-pending-deliveries', [SaleController::class, 'viewPaidPendingDelivery'])->middleware('role.name:owner,admin,administrador,vendor,vendedor,seller,almacen,almacenista,warehouse,delivery,repartidor')->name('sales.paidPendingDeliveries.index');
    Route::get('/sales-orders', [SaleController::class, 'viewOrders'])->middleware('role.name:owner,admin,administrador,vendor,vendedor,seller')->name('sales.orders');
    Route::post('/sales-orders/pending-dispatch-guides/email', [SaleController::class, 'sendPendingDispatchGuidesReport'])->middleware('role.name:owner,admin,administrador,vendor,vendedor,seller')->name('sales.orders.pendingDispatchGuides.email');
    Route::get('/sales-orders/pending-delivery', [SaleController::class, 'viewPendingDeliveryOrders'])->middleware('role.name:owner,admin,administrador,almacen,almacenista,warehouse,delivery,repartidor')->name('sales.orders.pendingDelivery');
    Route::get('/sales/{id}', [SaleController::class, 'showByOrder'])->middleware('role.name:owner,admin,administrador,vendor,vendedor,seller,almacen,almacenista,warehouse,delivery,repartidor')->name('sales.showByOrder');
    Route::post('/sales-orders/{order}/assign-delivery-user', [SaleController::class, 'assignDeliveryUser'])->middleware('role.name:owner,admin,administrador,vendor,vendedor,seller,almacen,almacenista,warehouse,delivery,repartidor')->name('sales.assignDeliveryUser');
    Route::post('/sales-orders/{order}/electronic/emit', [ElectronicInvoicingController::class, 'emit'])->middleware('role.name:owner,admin,administrador,vendor,vendedor,seller')->name('sales.electronic.emit');
    Route::post('/sales-orders/{order}/electronic/status', [ElectronicInvoicingController::class, 'status'])->middleware('role.name:owner,admin,administrador,vendor,vendedor,seller')->name('sales.electronic.status');
    Route::post('/sales-orders/{order}/electronic/download', [ElectronicInvoicingController::class, 'download'])->middleware('role.name:owner,admin,administrador,vendor,vendedor,seller')->name('sales.electronic.download');
    Route::post('/sales-orders/{order}/dispatch-guide/emit', [SaleController::class, 'emitHkaDispatchGuide'])->middleware('role.name:owner,admin,administrador,vendor,vendedor,seller')->name('sales.dispatchGuide.emit');
    Route::match(['get', 'post'], '/sales-orders/{order}/dispatch-guide/download', [SaleController::class, 'downloadHkaDispatchGuide'])->middleware('role.name:owner,admin,administrador,vendor,vendedor,seller,almacen,almacenista,warehouse')->name('sales.dispatchGuide.download');
    Route::post('/sales-orders/{order}/electronic/send-email', [ElectronicInvoicingController::class, 'sendEmail'])->middleware('role.name:owner,admin,administrador,vendor,vendedor,seller')->name('sales.electronic.sendEmail');
    Route::post('/sales-orders/{order}/electronic/annul', [ElectronicInvoicingController::class, 'annul'])->middleware('role.name:owner,admin,administrador')->name('sales.electronic.annul');
    Route::post('/sales-orders/{order}/electronic/metadata', [ElectronicInvoicingController::class, 'metadata'])->middleware('role.name:owner,admin,administrador,vendor,vendedor,seller')->name('sales.electronic.metadata');
    Route::post('/sales-orders/{order}/document-mode', [SaleController::class, 'updateDocumentMode'])->middleware('role.name:owner,admin,administrador,vendor,vendedor,seller')->name('sales.documentMode.update');
    Route::post('/sales-orders/{order}/adjustment-notes', [SalesFiscalController::class, 'storeAdjustmentNote'])->middleware('role.name:owner,admin,administrador,vendor,vendedor,seller')->name('sales.adjustmentNotes.store');
    Route::get('/sales-adjustment-notes/{note}/download', [SalesFiscalController::class, 'downloadAdjustmentNote'])->middleware('role.name:owner,admin,administrador,vendor,vendedor,seller')->name('sales.adjustmentNotes.download');
    Route::post('/sales-orders/{order}/retentions', [SalesFiscalController::class, 'storeRetention'])->middleware('role.name:owner,admin,administrador,vendor,vendedor,seller')->name('sales.retentions.store');
    Route::get('/sales-retentions/{retention}/certificate', [SalesFiscalController::class, 'downloadRetentionCertificate'])->middleware('role.name:owner,admin,administrador,vendor,vendedor,seller')->name('sales.retentions.certificate');
    Route::get('/sales-retentions/{retention}/download', [SalesFiscalController::class, 'downloadRetentionHkaSnapshot'])->middleware('role.name:owner,admin,administrador,vendor,vendedor,seller')->name('sales.retentions.download');
    Route::post('/sales-retentions/{retention}/sync-hka', [SalesFiscalController::class, 'syncRetentionHka'])->middleware('role.name:owner,admin,administrador,vendor,vendedor,seller')->name('sales.retentions.syncHka');
    Route::post('/sales-retentions/{retention}/status-hka', [SalesFiscalController::class, 'refreshRetentionHkaStatus'])->middleware('role.name:owner,admin,administrador,vendor,vendedor,seller')->name('sales.retentions.statusHka');
    Route::get('/my-electronic-documents', [ElectronicInvoicingController::class, 'tenantIndex'])->middleware('role.name:owner,admin,administrador,vendor,vendedor,seller')->name('sales.electronic.documents.tenant');
    Route::get('/electronic-documents', [ElectronicInvoicingController::class, 'index'])->middleware('role.name:4')->name('electronic.documents.index');
    Route::post('/electronic-documents/{electronicDocument}/retry', [ElectronicInvoicingController::class, 'retry'])->middleware('role.name:4')->name('electronic.documents.retry');
    Route::post('/sales/{id}/return', [SaleController::class, 'processReturn'])->middleware('role.name:owner,admin,administrador,vendor,vendedor,seller')->name('sales.return');
    Route::post('/create-sale', [SaleController::class, 'store'])->middleware('role.name:owner,admin,administrador,vendor,vendedor,seller');
    Route::post('/sales/scan-code', [SaleController::class, 'resolveScanCode'])->middleware('role.name:owner,admin,administrador,vendor,vendedor,seller')->name('sales.resolveScanCode');
    Route::get('/sales-orders/{id}/pdf', [SaleController::class, 'downloadPdf'])->middleware('role.name:owner,admin,administrador,vendor,vendedor,seller');
    Route::get('/sales-orders/{id}/pdfs/{type}', [SaleController::class, 'downloadStoredPdf'])->whereIn('type', ['invoice', 'delivery'])->middleware('role.name:owner,admin,administrador,vendor,vendedor,seller,almacen,almacenista,warehouse')->name('sales.orders.pdfs');
    Route::post('/sales-orders/{order}/whatsapp/send-delivery-pdf', [SaleController::class, 'sendDeliveryPdfToCustomerWhatsapp'])->middleware('role.name:owner,admin,administrador,vendor,vendedor,seller')->name('sales.orders.whatsapp.sendDeliveryPdf');
    Route::get('/seller-commissions', [SellerCommissionController::class, 'index'])->middleware('role.name:owner,admin,administrador')->name('seller-commissions.index');
    Route::get('/seller-commissions/progress', [SellerCommissionController::class, 'sellerProgress'])->middleware('role.name:vendor,vendedor,seller')->name('seller-commissions.progress');
    Route::get('/seller-commissions/progress/pdf', [SellerCommissionController::class, 'sellerProgressPdf'])->middleware('role.name:vendor,vendedor,seller')->name('seller-commissions.progress.pdf');
    Route::put('/seller-commissions/rate/{seller}', [SellerCommissionController::class, 'updateSellerRate'])->middleware('role.name:owner,admin,administrador')->name('seller-commissions.rate.update');
    Route::post('/seller-commissions/{commission}/mark-paid', [SellerCommissionController::class, 'markAsPaid'])->middleware('role.name:owner,admin,administrador')->name('seller-commissions.mark-paid');
    Route::get('/appointments', [AppointmentController::class, 'index'])->middleware('role.name:owner,admin,administrador,vendor,vendedor,seller')->name('appointments.index');
    Route::post('/appointments', [AppointmentController::class, 'store'])->middleware('role.name:owner,admin,administrador,vendor,vendedor,seller')->name('appointments.store');
    Route::post('/appointments/{appointment}/workflow', [AppointmentController::class, 'workflowAction'])->middleware('role.name:owner,admin,administrador,vendor,vendedor,seller')->name('appointments.workflow');
    Route::post('/appointments/services', [AppointmentController::class, 'storeService'])->middleware('role.name:owner,admin,administrador,vendor,vendedor,seller')->name('appointments.services.store');
    Route::post('/appointments/schedules', [AppointmentController::class, 'storeSchedule'])->middleware('role.name:owner,admin,administrador,vendor,vendedor,seller')->name('appointments.schedules.store');
    Route::post('/appointments/packages', [AppointmentController::class, 'storePackage'])->middleware('role.name:owner,admin,administrador,vendor,vendedor,seller')->name('appointments.packages.store');
    Route::get('/appointments/available-slots', [AppointmentController::class, 'availableSlots'])->middleware('role.name:owner,admin,administrador,vendor,vendedor,seller')->name('appointments.availableSlots');

    // Módulos separados: nómina, proyectos y cotizaciones
    Route::get('/projects-module', [ProjectModuleController::class, 'index'])->middleware('role.name:owner,admin,administrador,almacen,almacenista,warehouse')->name('projects.module.index');

    Route::get('/nomina', [ProjectModuleController::class, 'payrollIndex'])->middleware('role.name:owner,admin,administrador,almacen,almacenista,warehouse')->name('projects.module.payroll.index');
    Route::post('/nomina/team-members', [ProjectModuleController::class, 'storeTeamMember'])->middleware('role.name:owner,admin,administrador,almacen,almacenista,warehouse')->name('projects.module.team.store');
    Route::post('/nomina/team-members/{teamMember}/status', [ProjectModuleController::class, 'updateTeamMemberStatus'])->middleware('role.name:owner,admin,administrador,almacen,almacenista,warehouse')->name('projects.module.team.status');
    Route::post('/nomina/payrolls', [ProjectModuleController::class, 'storePayroll'])->middleware('role.name:owner,admin,administrador,almacen,almacenista,warehouse')->name('projects.module.payrolls.store');
    Route::get('/nomina/payrolls/{payroll}/comprobante', [ProjectModuleController::class, 'payrollReceipt'])->middleware('role.name:owner,admin,administrador,almacen,almacenista,warehouse')->name('projects.module.payrolls.receipt');

    Route::get('/proyectos', [ProjectModuleController::class, 'projectsIndex'])->middleware('role.name:owner,admin,administrador,almacen,almacenista,warehouse')->name('projects.module.projects.index');
    Route::post('/proyectos', [ProjectModuleController::class, 'storeProject'])->middleware('role.name:owner,admin,administrador,almacen,almacenista,warehouse')->name('projects.module.projects.store');
    Route::get('/proyectos/{project}', [ProjectModuleController::class, 'projectShow'])->middleware('role.name:owner,admin,administrador,almacen,almacenista,warehouse')->name('projects.module.projects.show');
    Route::post('/proyectos/{project}/assets', [ProjectModuleController::class, 'storeProjectAsset'])->middleware('role.name:owner,admin,administrador,almacen,almacenista,warehouse')->name('projects.module.projects.assets.store');
    Route::get('/proyectos/assets/{asset}/file', [ProjectModuleController::class, 'projectAssetFile'])->middleware('role.name:owner,admin,administrador,almacen,almacenista,warehouse')->name('projects.module.projects.assets.file');
    Route::post('/proyectos/{project}/phase', [ProjectModuleController::class, 'updateProjectPhase'])->middleware('role.name:owner,admin,administrador,almacen,almacenista,warehouse')->name('projects.module.projects.phase');
    Route::post('/proyectos/{project}/complete', [ProjectModuleController::class, 'completeProject'])->middleware('role.name:owner,admin,administrador,almacen,almacenista,warehouse')->name('projects.module.projects.complete');
    Route::post('/proyectos/{project}/visibility', [ProjectModuleController::class, 'updateProjectVisibility'])->middleware('role.name:owner,admin,administrador,almacen,almacenista,warehouse')->name('projects.module.projects.visibility');
    Route::post('/proyectos/{project}/tasks', [ProjectModuleController::class, 'storeProjectTask'])->middleware('role.name:owner,admin,administrador,almacen,almacenista,warehouse')->name('projects.module.projects.tasks.store');
    Route::post('/proyectos/tasks/{task}/status', [ProjectModuleController::class, 'updateProjectTaskStatus'])->middleware('role.name:owner,admin,administrador,almacen,almacenista,warehouse')->name('projects.module.projects.tasks.status');
    Route::post('/proyectos/{project}/assignments', [ProjectModuleController::class, 'storeProjectAssignment'])->middleware('role.name:owner,admin,administrador,almacen,almacenista,warehouse')->name('projects.module.projects.assignments.store');

    Route::get('/cotizaciones', [ProjectModuleController::class, 'quotationsIndex'])->middleware('role.name:owner,admin,administrador,vendor,vendedor,seller,almacen,almacenista,warehouse')->name('projects.module.quotations.index');
    Route::post('/cotizaciones', [ProjectModuleController::class, 'storeQuotation'])->middleware('role.name:owner,admin,administrador,vendor,vendedor,seller,almacen,almacenista,warehouse')->name('projects.module.quotations.store');
    Route::put('/cotizaciones/{quotation}', [ProjectModuleController::class, 'updateQuotation'])->middleware('role.name:owner,admin,administrador,vendor,vendedor,seller,almacen,almacenista,warehouse')->name('projects.module.quotations.update');
    Route::get('/cotizaciones/{quotation}/pdf', [ProjectModuleController::class, 'quotationPdf'])->middleware('role.name:owner,admin,administrador,vendor,vendedor,seller,almacen,almacenista,warehouse')->name('projects.module.quotations.pdf');
    Route::post('/cotizaciones/{quotation}/invalidate', [ProjectModuleController::class, 'invalidateQuotation'])->middleware('role.name:owner,admin,administrador,vendor,vendedor,seller,almacen,almacenista,warehouse')->name('projects.module.quotations.invalidate');
    Route::post('/cotizaciones/{quotation}/annul', [ProjectModuleController::class, 'annulQuotation'])->middleware('role.name:owner,admin,administrador,vendor,vendedor,seller,almacen,almacenista,warehouse')->name('projects.module.quotations.annul');
    Route::post('/cotizaciones/{quotation}/replace', [ProjectModuleController::class, 'replaceQuotation'])->middleware('role.name:owner,admin,administrador,vendor,vendedor,seller,almacen,almacenista,warehouse')->name('projects.module.quotations.replace');
    Route::post('/cotizaciones/{quotation}/to-project', [ProjectModuleController::class, 'convertQuotationToProject'])->middleware('role.name:owner,admin,administrador,vendor,vendedor,seller,almacen,almacenista,warehouse')->name('projects.module.quotations.toProject');
    Route::post('/cotizaciones/{quotation}/to-sale', [ProjectModuleController::class, 'convertQuotationToSale'])->middleware('role.name:owner,admin,administrador,vendor,vendedor,seller,almacen,almacenista,warehouse')->name('projects.module.quotations.toSale');
    Route::post('/cotizaciones/{quotation}/to-inventory-entry', [ProjectModuleController::class, 'convertQuotationToInventoryEntry'])->middleware('role.name:owner,admin,administrador,vendor,vendedor,seller,almacen,almacenista,warehouse')->name('projects.module.quotations.toInventory');

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
    Route::get('/reports/appointments/workflow/pdf', [ReportController::class, 'appointmentsWorkflowPdf'])->middleware('role.name:owner,admin,administrador')->name('reports.appointments.workflow.pdf');
    Route::get('/reports/appointments/workflow/excel', [ReportController::class, 'appointmentsWorkflowExcel'])->middleware('role.name:owner,admin,administrador')->name('reports.appointments.workflow.excel');
    Route::get('/reports/accounts-receivable/pdf', [ReportController::class, 'receivablesPdf'])->middleware('role.name:owner,admin,administrador')->name('reports.accountsReceivable.pdf');
    Route::get('/reports/accounts-receivable/excel', [ReportController::class, 'receivablesExcel'])->middleware('role.name:owner,admin,administrador')->name('reports.accountsReceivable.excel');
    Route::get('/reports/income/by-user/pdf', [ReportController::class, 'incomeByUserPdf'])->middleware('role.name:owner,admin,administrador')->name('reports.income.byUser.pdf');
    Route::get('/reports/income/by-user/excel', [ReportController::class, 'incomeByUserExcel'])->middleware('role.name:owner,admin,administrador')->name('reports.income.byUser.excel');
    Route::get('/reports/sales/book/pdf', [ReportController::class, 'salesBookPdf'])->middleware('role.name:owner,admin,administrador')->name('reports.sales.book.pdf');
    Route::get('/reports/sales/book/excel', [ReportController::class, 'salesBookExcel'])->middleware('role.name:owner,admin,administrador')->name('reports.sales.book.excel');
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
    Route::get('/accounts-payable', [AccountsPayableController::class, 'index'])->middleware('role.name:owner,admin,administrador,almacen,almacenista,warehouse')->name('accounts.payable.index');
    Route::post('/accounts-payable', [AccountsPayableController::class, 'store'])->middleware('role.name:owner,admin,administrador,almacen,almacenista,warehouse')->name('accounts.payable.store');
    Route::post('/accounts-payable/{accountPayable}/payments', [AccountsPayableController::class, 'registerPayment'])->middleware('role.name:owner,admin,administrador,almacen,almacenista,warehouse')->name('accounts.payable.payments.store');
    Route::get('/withholdings/islr/concepts', [WithholdingController::class, 'islrConceptsIndex'])->middleware('role.name:owner,admin,administrador')->name('withholdings.islr.concepts.index');
    Route::post('/withholdings/islr/concepts', [WithholdingController::class, 'islrConceptsStore'])->middleware('role.name:owner,admin,administrador')->name('withholdings.islr.concepts.store');
    Route::put('/withholdings/islr/concepts/{concept}', [WithholdingController::class, 'islrConceptsUpdate'])->middleware('role.name:owner,admin,administrador')->name('withholdings.islr.concepts.update');
    Route::get('/withholdings/iva/export/txt', [WithholdingController::class, 'exportVatTxt'])->middleware('role.name:owner,admin,administrador')->name('withholdings.iva.export.txt');
    Route::get('/withholdings/islr/export/xml', [WithholdingController::class, 'exportIslrXml'])->middleware('role.name:owner,admin,administrador')->name('withholdings.islr.export.xml');
    Route::get('/withholdings/iva/{retention}/certificate', [WithholdingController::class, 'purchaseVatCertificatePdf'])->middleware('role.name:owner,admin,administrador,almacen,almacenista,warehouse')->name('withholdings.iva.certificate.pdf');
    Route::get('/withholdings/iva/{retention}/download-hka-pdf', [WithholdingController::class, 'purchaseVatDownloadHkaPdf'])->middleware('role.name:owner,admin,administrador,almacen,almacenista,warehouse')->name('withholdings.iva.downloadHkaPdf');
    Route::post('/withholdings/iva/{retention}/sync-hka', [WithholdingController::class, 'purchaseVatSyncHka'])->middleware('role.name:owner,admin,administrador,almacen,almacenista,warehouse')->name('withholdings.iva.syncHka');
    Route::post('/withholdings/iva/{retention}/status-hka', [WithholdingController::class, 'purchaseVatStatusHka'])->middleware('role.name:owner,admin,administrador,almacen,almacenista,warehouse')->name('withholdings.iva.statusHka');
    Route::get('/withholdings/iva/{retention}/download-hka', [WithholdingController::class, 'purchaseVatDownloadHkaSnapshot'])->middleware('role.name:owner,admin,administrador,almacen,almacenista,warehouse')->name('withholdings.iva.downloadHka');
    Route::get('/withholdings/islr/{withholding}/certificate', [WithholdingController::class, 'islrCertificatePdf'])->middleware('role.name:owner,admin,administrador,almacen,almacenista,warehouse')->name('withholdings.islr.certificate.pdf');
    Route::get('/withholdings/islr/{withholding}/download-hka-pdf', [WithholdingController::class, 'islrDownloadHkaPdf'])->middleware('role.name:owner,admin,administrador,almacen,almacenista,warehouse')->name('withholdings.islr.downloadHkaPdf');
    Route::post('/withholdings/islr/{withholding}/sync-hka', [WithholdingController::class, 'islrSyncHka'])->middleware('role.name:owner,admin,administrador,almacen,almacenista,warehouse')->name('withholdings.islr.syncHka');
    Route::post('/withholdings/islr/{withholding}/status-hka', [WithholdingController::class, 'islrStatusHka'])->middleware('role.name:owner,admin,administrador,almacen,almacenista,warehouse')->name('withholdings.islr.statusHka');
    Route::get('/withholdings/islr/{withholding}/download-hka', [WithholdingController::class, 'islrDownloadHkaSnapshot'])->middleware('role.name:owner,admin,administrador,almacen,almacenista,warehouse')->name('withholdings.islr.downloadHka');
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
    Route::post('/materials', [MaterialPackageController::class, 'store'])->middleware('role.name:owner,admin,administrador,almacen,almacenista,warehouse')->name('materials.store');
    Route::put('/materials/{id}', [MaterialPackageController::class, 'update'])->middleware('role.name:owner,admin,administrador,almacen,almacenista,warehouse')->name('materials.update');
    Route::post('/materials/{id}/toggle-status', [MaterialPackageController::class, 'toggleStatus'])->middleware('role.name:owner,admin,administrador,almacen,almacenista,warehouse')->name('materials.toggleStatus');
    Route::post('/materials/{id}/generate-codes', [MaterialPackageController::class, 'generateCodes'])->middleware('role.name:owner,admin,administrador,vendor,vendedor,seller,almacen,almacenista,warehouse')->name('materials.generateCodes');

    // Tenants
    Route::get('/tenants', [TenantController::class, 'index'])->middleware('role.name:4')->name('tenant.index');
    Route::get('/tenant-payments', [TenantController::class, 'paymentsIndex'])->middleware('role.name:4')->name('tenant.payments.index');
    Route::get('/create-tenant', [TenantController::class, 'createIndex'])->middleware('role.name:4')->name('createTenant');
    Route::get('/tenant-store', [TenantController::class, 'getTenant'])->middleware('role.name:owner,admin,administrador')->name('tenant.store');
    Route::post('/tenant-update', [TenantController::class, 'updateTenant'])->middleware('role.name:owner,admin,administrador')->name('tenant.update');
    Route::post('/tenant-store/users/{id}/update', [UserController::class, 'update'])->middleware('role.name:owner,admin,administrador')->name('tenant.users.update');
    Route::post('/tenant-store/users/{id}/toggle-status', [UserController::class, 'toggleStatus'])->middleware('role.name:owner,admin,administrador')->name('tenant.users.toggleStatus');
    Route::post('/tenant-import-setup-docx', [TenantController::class, 'importSetupDocument'])->middleware('role.name:4')->name('tenant.importSetupDocx');
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
    Route::get('/documentation', [IndexController::class, 'documentationIndex'])->middleware('role.name:4')->name('documentation.index');
    Route::get('/documentation/download/{document}', [IndexController::class, 'documentationDownload'])->middleware('role.name:4')->name('documentation.download');
});

require __DIR__.'/auth.php';

Route::get('/csrf-token', function () {
    request()->session()->regenerateToken();

    return response()->json([
        'csrf_token' => csrf_token(),
    ]);
})->name('csrf.token');

// 🔹 RUTAS PÚBLICAS DEL TENANT (al final)
Route::get('/{tenant:slug}', [TenantController::class, 'publicTenantindex'])->name('tenant.public');
Route::get('/{tenant:slug}/categorias', [TenantController::class, 'publicTenantCategory'])->name('tenant.public.categories');
Route::get('/{tenant:slug}/payment-methods', [TenantController::class, 'publicTenantPaymentMethods'])->name('tenant.public.paymentMethods');
Route::get('/{tenant:slug}/appointments/public-availability', [TenantController::class, 'publicTenantAppointmentAvailability'])->name('tenant.public.appointments.availability');
Route::post('/{tenant:slug}/checkout/pro', [TenantController::class, 'publicTenantProCheckout'])->name('tenant.public.proCheckout');
Route::post('/{tenant:slug}/scan-code', [TenantController::class, 'publicTenantResolveScanCode'])->name('tenant.public.scanCode');
Route::get('/{tenant:slug}/{product}', [TenantController::class, 'publicTenantProduct'])->name('tenant.public.product');
Route::post('/tenants-public', [TenantController::class, 'storePublic'])->name('tenants.storePublic'); // ← fuera del grupo auth
