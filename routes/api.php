<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\ProductVariantController;
use App\Http\Controllers\PurchaseOrderController;
use App\Http\Controllers\PaymentMethodController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\UserController;
use App\Http\Controllers\PlanController;
use App\Http\Controllers\TenantController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PushSubscriptionController;

// ------------------------ RUTAS PÚBLICAS ------------------------

Route::post('logout', [AuthenticatedSessionController::class, 'destroy']);
Route::post('loginEcomm', [AuthenticatedSessionController::class, 'store']);
Route::post('/registerEcomm', [AuthenticatedSessionController::class, 'registerEcomm']);

// Endpoint para obtener el token CSRF
Route::post('/create-user', [UserController::class, 'store']);
Route::post('/user/{id}', [UserController::class, 'update'])->whereNumber('id');
Route::post('users/{id}/toggle-status', [UserController::class, 'toggleStatus'])->name('users.toggleStatus');

// Rutas de productos y categorías
Route::get('/products', [ProductController::class, 'index'])->name('api.products.index');
Route::get('/get-products', [ProductController::class, 'getProducts'])->name('products.getProducts');
Route::get('/categories', [CategoryController::class, 'getCategories'])->name('categories.getCategories');
Route::get('/products/{id}', [ProductController::class, 'showByCategoryEcomm']);
Route::get('/products/all', [ProductController::class, 'showByCategoryEcommAll']);
Route::get('/getProduct/{id}', [ProductController::class, 'show']);
Route::get('/payment-methods/ecomm', [SaleController::class, 'getPaymentMethodsEcomm']);

// ---------------------- RUTAS AUTENTICADAS ------------------------

Route::middleware(['auth.jwt'])->group(function () {
    Route::get('/user', [AuthenticatedSessionController::class, 'getUserFromToken']);
    Route::post('/user/update-profile', [AuthenticatedSessionController::class, 'updateEcommProfile']);
    Route::post('/user/change-password', [AuthenticatedSessionController::class, 'changeEcommPassword']);
    Route::get('/user/orders', [SaleController::class, 'viewMyOrders']);
    Route::post('/create-sale/ecomm', [SaleController::class, 'storeEcommerceSale']);
    Route::get('/notifications', [NotificationController::class, 'apiIndex']);
    Route::post('/notifications/{id}/read', [NotificationController::class, 'apiMarkAsRead']);
    Route::post('/push-subscriptions', [PushSubscriptionController::class, 'store']);
    Route::delete('/push-subscriptions', [PushSubscriptionController::class, 'destroy']);
});

// ------------------------ CATEGORÍAS ------------------------

Route::post('/create-category', [CategoryController::class, 'store'])->name('categories.store');
Route::post('/categories/{category}', [CategoryController::class, 'update'])->name('categories.update');
Route::post('categories/{id}/toggle-status', [CategoryController::class, 'toggleStatus'])->name('categories.toggleStatus');

// ------------------------ PRODUCTOS ------------------------

Route::post('/products/{id}', [ProductController::class, 'update'])->name('products.update');
Route::delete('/products/{id}', [ProductController::class, 'destroy'])->name('products.destroy');
Route::post('/products/{product}/generate-codes', [ProductController::class, 'generateCodes'])->name('products.generateCodes');
Route::post('/create-product', [ProductController::class, 'create']);
// Route::post('/create-product', [ProductController::class, 'store'])->name('products.store');
Route::post('/products/import-catalog', [ProductController::class, 'importCatalog'])->name('products.importCatalog');
Route::get('/products/import-template', [ProductController::class, 'downloadImportTemplate'])->name('products.importTemplate');
Route::post('/addImage/{productId}', [ProductController::class, 'addImage'])->name('products.addImage');
Route::delete('/product/remove-image/{imageId}', [ProductController::class, 'removeImage'])->name('products.removeImage');
Route::get('/products/report', [ProductController::class, 'generateReport']);

// ------------------------ VARIANTES DE PRODUCTOS ------------------------

Route::post('/variants/store', [ProductVariantController::class, 'store'])->name('variants.store');
Route::put('/variants/{productVariant}', [ProductVariantController::class, 'update'])->name('variants.update');
Route::put('/variants/{productVariant}/barcode', [ProductVariantController::class, 'updateBarcode'])->name('variants.updateBarcode');
Route::post('/variants/{productVariant}/generate-codes', [ProductVariantController::class, 'generateCodes'])->name('variants.generateCodes');

// ------------------------ MÉTODOS DE PAGO ------------------------

Route::prefix('payment-methods')->group(function () {
    Route::post('/create', [PaymentMethodController::class, 'create'])->name('paymentMethods.create');
    Route::post('/{id}/edit', [PaymentMethodController::class, 'edit'])->name('paymentMethods.edit');
    Route::post('/{id}/toggleStatus', [PaymentMethodController::class, 'toggleStatus'])->name('paymentMethods.toggleStatus');
});
Route::post('/payment-methods/update-qr/{id}', [PaymentMethodController::class, 'updateQrImage'])->name('payment-methods.update-qr');
Route::post('/payment-methods/remove-qr/{id}', [PaymentMethodController::class, 'removeQrImage'])->name('payment-methods.remove-qr');

// ------------------------ CURRÉNCIES ------------------------

Route::post('currencies/create', [PaymentMethodController::class, 'currencyCreate'])->name('paymentMethods.currencyCreate');
Route::prefix('currencies')->group(function () {
    Route::post('/{id}/update', [PaymentMethodController::class, 'updateCurrency']);
    Route::post('/{id}/currencyToggleStatus', [PaymentMethodController::class, 'currencyToggleStatus']);
});

// ------------------------ DÓLAR Y TARIFA ------------------------

Route::prefix('dollar-rate')->group(function () {
    Route::post('/update', [PaymentMethodController::class, 'updateDollarRate'])->name('paymentMethods.updateDollarRate');
});
Route::prefix('euro-rate')->group(function () {
    Route::post('/update', [PaymentMethodController::class, 'updateEuroRate'])->name('paymentMethods.updateEuroRate');
});
Route::post('/tenant-base-currency/update', [PaymentMethodController::class, 'updateTenantBaseCurrency'])->name('paymentMethods.updateTenantBaseCurrency');
Route::get('/dollarRate', [PaymentMethodController::class, 'getDollarRate']);

// ------------------------ VENTAS ------------------------

Route::get('/payment-methods', [SaleController::class, 'getPaymentMethods']);
Route::post('/sales/get-variants', [SaleController::class, 'getVariants']);
Route::post('/create-sale', [SaleController::class, 'store']);
Route::post('/payment/{id}/status/update', [SaleController::class, 'paymentToggleStatus']);
Route::post('/deliver/{id}/status/update', [SaleController::class, 'orderDeliverToggleStatus']);
Route::post('/order/{id}/status/update', [SaleController::class, 'orderToggleStatus']);
Route::get('/orders/{id}', [SaleController::class, 'viewUserOrders']);
Route::post('/sales-orders-report', [SaleController::class, 'viewOrdersReport'])->name('sales.orders.report');

// ------------------------ ÓRDENES DE COMPRA ------------------------

Route::post('/create-order', [PurchaseOrderController::class, 'store']);
Route::post('/get-variants', [PurchaseOrderController::class, 'getVariants']);

// ------------------------ Planes ------------------------

Route::post('/plans', [PlanController::class, 'store'])->name('plans.store');
Route::post('/plans/{id}', [PlanController::class, 'update']);
Route::delete('/plans/{id}', [PlanController::class, 'destroy'])->name('plans.destroy');

// ------------------------ TENANTS ------------------------

Route::post('/tenants', [TenantController::class, 'store'])->name('tenants.store');
Route::post('/tenants/{tenant}', [TenantController::class, 'update']);
Route::delete('/tenants/{tenant}', [TenantController::class, 'destroy']);