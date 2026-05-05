  <style>
    .step {
        display: none;
    }
    .step:not(.d-none) {
        display: block;
    }
    /* quitar si no se necesita */
    #cartList {
        max-height: 450px;
        overflow-y: auto;
        overflow-x: hidden;
        border: 1px solid #e3e6ea;
        border-radius: .5rem;
        background-color: #f8f9fa;
        padding-right: 5px;
    }

    .admin-cart-fab {
        position: fixed;
        right: max(16px, env(safe-area-inset-right));
        bottom: max(16px, env(safe-area-inset-bottom));
        left: auto !important;
        z-index: 1080;
        box-shadow: 0 14px 28px rgba(15, 23, 42, 0.22);
    }

    .sale-flow-shell {
        display: flex;
        flex-direction: column;
        gap: 1rem;
    }

    .sale-flow-hero {
        padding: 0.95rem 1.1rem;
        border: 1px solid #dbe4f0;
        border-radius: 24px;
        background: radial-gradient(circle at top right, rgba(96, 165, 250, 0.18), transparent 24%), linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        box-shadow: 0 24px 50px -38px rgba(15, 23, 42, 0.45);
    }

    .sale-flow-eyebrow {
        font-size: 0.74rem;
        letter-spacing: 0.14em;
        text-transform: uppercase;
        font-weight: 800;
        color: #2563eb;
        margin-bottom: 0.4rem;
    }

    .sale-flow-hero h1 {
        margin-bottom: 0.15rem;
    }

    .sale-step-panel {
        border: 1px solid #dbe4f0;
        border-radius: 24px;
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        box-shadow: 0 22px 45px -40px rgba(15, 23, 42, 0.45);
        padding: 1.2rem;
    }

    .sale-step-title {
        font-size: clamp(1.35rem, 2vw, 1.8rem);
        font-weight: 800;
        margin-bottom: 0.2rem;
        color: #0f172a;
    }

    .sale-step-copy {
        color: #64748b;
        margin-bottom: 1rem;
    }

    .sale-info-strip {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(180px, 1fr));
        gap: 0.8rem;
        margin-bottom: 1rem;
    }

    .sale-info-pill {
        border: 1px solid #dbe4f0;
        border-radius: 18px;
        padding: 0.85rem 1rem;
        background: #fff;
    }

    .sale-info-pill small {
        display: block;
        color: #64748b;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        font-weight: 700;
        margin-bottom: 0.25rem;
    }

    .sale-section-card {
        border: 1px solid #e2e8f0;
        border-radius: 20px;
        background: #fff;
        padding: 1rem;
        box-shadow: 0 18px 35px -34px rgba(15, 23, 42, 0.32);
        margin-bottom: 0.9rem;
    }

    .sale-section-card h6,
    .sale-section-card h5 {
        font-weight: 800;
    }

    .sale-section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 0.75rem;
        flex-wrap: wrap;
        margin-bottom: 0.85rem;
    }

    .sale-section-header p {
        margin: 0;
        color: #64748b;
    }

    .sale-catalog-grid {
        display: grid;
        gap: 1rem;
    }

    .sale-products-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(260px, 1fr));
        gap: 0.6rem;
    }

    .sale-products-grid .product-item,
    .sale-products-grid .package-item {
        width: 100%;
    }

    .sale-step-actions {
        display: flex;
        justify-content: space-between;
        gap: 0.75rem;
        flex-wrap: wrap;
    }

    .sale-step-actions .btn {
        min-width: 120px;
    }

    .sale-flow-stepper {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 0.55rem;
    }

    .sale-flow-step {
        position: relative;
        padding: 0.65rem 0.8rem;
        border: 1px solid #dbe4f0;
        border-radius: 20px;
        background: rgba(255, 255, 255, 0.82);
        box-shadow: 0 16px 32px -28px rgba(15, 23, 42, 0.28);
        transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease, background 0.2s ease;
    }

    .sale-flow-step.is-active {
        border-color: rgba(37, 99, 235, 0.45);
        background: linear-gradient(180deg, rgba(255,255,255,0.98) 0%, rgba(239,246,255,0.98) 100%);
        box-shadow: 0 22px 38px -30px rgba(37, 99, 235, 0.45);
        transform: translateY(-1px);
    }

    .sale-flow-step.is-complete {
        border-color: rgba(15, 118, 110, 0.28);
        background: linear-gradient(180deg, rgba(255,255,255,0.98) 0%, rgba(240,253,250,0.96) 100%);
    }

    .sale-flow-step-number {
        width: 30px;
        height: 30px;
        border-radius: 999px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 0.78rem;
        font-weight: 800;
        background: #e2e8f0;
        color: #0f172a;
        margin-bottom: 0.38rem;
    }

    .sale-flow-step.is-active .sale-flow-step-number {
        background: #2563eb;
        color: #fff;
    }

    .sale-flow-step.is-complete .sale-flow-step-number {
        background: #0f766e;
        color: #fff;
    }

    .sale-flow-step-title {
        font-size: 0.92rem;
        font-weight: 800;
        color: #0f172a;
        margin-bottom: 0;
    }

    @media (min-width: 992px) {
        .sale-flow-hero {
            padding: 0.72rem 0.9rem;
        }

        .sale-flow-hero h1 {
            font-size: 2.15rem;
            line-height: 1.08;
            margin-bottom: 0.05rem;
        }

        .sale-flow-stepper {
            gap: 0.45rem;
        }

        .sale-flow-step {
            padding: 0.46rem 0.62rem;
            border-radius: 16px;
        }

        .sale-flow-step-number {
            width: 26px;
            height: 26px;
            font-size: 0.72rem;
            margin-bottom: 0.24rem;
        }

        .sale-flow-step-title {
            font-size: 0.82rem;
        }
    }

    .sale-flow-step-copy-small {
        color: #64748b;
        font-size: 0.88rem;
        line-height: 1.35;
    }

    .sale-step-panel-step1 {
        border: 1px solid #dbe4f0;
        border-radius: 24px;
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        box-shadow: 0 22px 45px -40px rgba(15, 23, 42, 0.45);
        padding: 1.2rem;
    }

    .sale-toolbar-grid {
        display: grid;
        grid-template-columns: 1fr;
        gap: 0.9rem;
        margin-bottom: 1rem;
    }

    .sale-toolbar-card {
        border: 1px solid #e2e8f0;
        border-radius: 20px;
        background: rgba(255, 255, 255, 0.96);
        box-shadow: 0 18px 35px -34px rgba(15, 23, 42, 0.32);
        padding: 1rem;
    }

    .sale-toolbar-card h6 {
        font-weight: 800;
        margin-bottom: 0.3rem;
    }

    .sale-toolbar-card p {
        color: #64748b;
        margin-bottom: 0.8rem;
    }

    #categoriesContainer {
        padding-bottom: 0.1rem !important;
        gap: 0.5rem !important;
    }

    .sale-category-pill {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.3rem;
        border: 1px solid #dbe4f0;
        border-radius: 999px;
        background: #fff;
        color: #0f172a;
        padding: 0.4rem 0.8rem;
        font-size: 0.86rem;
        font-weight: 700;
        line-height: 1;
        white-space: nowrap;
        transition: all 0.18s ease;
    }

    .sale-category-pill:hover,
    .sale-category-pill.is-active {
        border-color: rgba(37, 99, 235, 0.45);
        color: #1d4ed8;
        background: #eff6ff;
    }

    .category-item .card,
    .product-item .card,
    .package-item .card {
        border: 1px solid #dbe4f0;
        border-radius: 22px;
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        box-shadow: 0 20px 40px -36px rgba(15, 23, 42, 0.38);
        overflow: hidden;
        transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
    }

    .category-item .card:hover,
    .product-item .card:hover,
    .package-item .card:hover {
        transform: translateY(-2px);
        border-color: rgba(37, 99, 235, 0.26);
        box-shadow: 0 26px 45px -34px rgba(37, 99, 235, 0.24);
    }

    .category-item .card-header {
        border-bottom: 0;
        background: transparent;
    }

    .category-item .icon-shape,
    .package-item .icon-shape,
    .product-item .icon-shape {
        border: 1px solid rgba(15, 23, 42, 0.08) !important;
        box-shadow: 0 18px 34px -28px rgba(15, 23, 42, 0.38) !important;
    }

    .product-item .card-body,
    .package-item .card-body {
        padding: 1rem;
    }

    #itemSelector.sale-products-grid-compact {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
        gap: 0.85rem;
    }

    .product-card-header {
        cursor: pointer;
    }

    .product-card-header h5 {
        font-size: 1.06rem;
        margin-bottom: 0.12rem;
    }

    .product-card-header p {
        margin-bottom: 0;
        font-size: 0.82rem;
        color: #64748b;
    }

    .product-variants-panel {
        display: none;
        margin-top: 0.7rem;
        border-top: 1px solid #e5e7eb;
        padding-top: 0.65rem;
    }

    .product-item.is-expanded .product-variants-panel {
        display: block;
    }

    .variant-row {
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        padding: 0.56rem 0.7rem;
        background: #fff;
        margin-top: 0.45rem;
    }

    .variant-row .variant-label {
        margin: 0 !important;
    }

    .variant-row .variant-copy {
        font-size: 0.85rem;
        color: #334155;
    }

    .variant-qty-input {
        max-width: 78px;
    }

    #cart.offcanvas-admin-desktop {
        border: 1px solid #dbe4f0;
        border-radius: 24px;
        box-shadow: 0 22px 45px -40px rgba(15, 23, 42, 0.45);
    }

    #cart.offcanvas-admin-desktop .offcanvas-header {
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    }

    #cart {
        --bs-offcanvas-zindex: 2000;
    }

    @media (min-width: 1200px) {
        #cart.offcanvas-admin-desktop {
            position: sticky;
            top: 90px;
            height: calc(100vh - 120px);
            border: 1px solid #e3e6ea;
            border-radius: .75rem;
            background: #fff;
            transform: none !important;
            visibility: visible !important;
        }

        #cart.offcanvas-admin-desktop .offcanvas-body {
            display: flex;
            flex-direction: column;
        }

        #cart.offcanvas-admin-desktop #cartList {
            max-height: 48vh;
        }

        #cart.offcanvas-admin-desktop .offcanvas-header {
            border-bottom: 1px solid #e3e6ea !important;
        }
    }

    @media (max-width: 1199.98px) {
        #cart.offcanvas-admin-desktop {
            width: min(96vw, 460px);
        }

        .sale-category-pill {
            font-size: 0.8rem;
            padding: 0.36rem 0.68rem;
        }

        #itemSelector.sale-products-grid-compact {
            grid-template-columns: 1fr;
        }

        .variant-row {
            gap: .75rem !important;
            flex-wrap: wrap;
            align-items: flex-start !important;
        }

        .variant-row .variant-label {
            flex: 1 1 85%;
        }

        .payment-method-row {
            align-items: flex-start !important;
            gap: .75rem;
        }

        .product-detail-layout {
            flex-direction: column;
            gap: 1rem !important;
        }

        .product-detail-image {
            width: 100% !important;
            max-width: 240px;
            margin: 0 auto;
            height: auto !important;
            aspect-ratio: 1 / 1;
        }

        .sale-flow-hero,
        .sale-step-panel,
        .sale-section-card {
            border-radius: 18px;
        }

        .sale-step-actions .btn {
            flex: 1 1 150px;
        }

        .sale-flow-stepper {
            grid-template-columns: 1fr;
        }

        .sale-step-panel-step1,
        .sale-toolbar-card {
            border-radius: 18px;
        }

        .sale-products-grid {
            grid-template-columns: 1fr;
        }
    }
  </style>
@extends('layouts.app')

@php
    $salesPlanCapabilities = \App\Support\TenantPlanCapabilities::forTenant($tenant ?? null);
    $salesFreePlanLock = !$salesPlanCapabilities->allowsDeliveryOperations();
    $salesDeliveryEnabled = $salesPlanCapabilities->effectiveDeliveryEnabled($tenant ?? null);
    $salesSpecialTaxpayer = $salesPlanCapabilities->effectiveSpecialTaxpayer($tenant ?? null);
@endphp

    @section('title', 'Categorías')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<style>
    .select2-container--default .select2-selection--single {
        height: calc(2.875rem + 2px);
        border: 1px solid #d2d6da;
        border-radius: 0.5rem;
        padding: 0.55rem 0.75rem;
    }

    .select2-container--default .select2-selection--single .select2-selection__rendered {
        line-height: 1.5rem;
        padding-left: 0;
        color: #344767;
    }

    .select2-container--default .select2-selection--single .select2-selection__arrow {
        height: 100%;
        right: 0.6rem;
    }
</style>
@endpush

    @section('content')
    <div class="container-fluid px-2 px-md-4">
        <button type="button"
            id="openAdminCartBtn"
            class="btn btn-dark admin-cart-fab d-xl-none"
            style="position: fixed; right: max(16px, env(safe-area-inset-right)); bottom: max(16px, env(safe-area-inset-bottom)); left: auto; z-index: 1080; box-shadow: 0 14px 28px rgba(15, 23, 42, 0.22);"
            data-bs-toggle="offcanvas"
            data-bs-target="#cart"
            aria-controls="cart">
            <i class="material-symbols-rounded align-middle">shopping_cart</i>
            <span class="ms-1">Carrito</span>
            <span class="badge bg-light text-dark ms-2" id="adminCartCount">0</span>
        </button>

        <div class="sale-flow-shell">
        <div class="sale-flow-hero">
            <h1>Flujo de Venta</h1>
            <div class="sale-flow-stepper" id="saleFlowStepper">
                <div class="sale-flow-step is-active" data-sale-step="1">
                    <div class="sale-flow-step-number">1</div>
                    <div class="sale-flow-step-title">Selección</div>
                </div>
                <div class="sale-flow-step" data-sale-step="2">
                    <div class="sale-flow-step-number">2</div>
                    <div class="sale-flow-step-title">Pago</div>
                </div>
                <div class="sale-flow-step" data-sale-step="3">
                    <div class="sale-flow-step-number">3</div>
                    <div class="sale-flow-step-title">Confirmación</div>
                </div>
            </div>
        </div>
        <div class="row g-4">
        <div class="col-12 col-xl-8">
            <span id="baseRate" data-rate="{{ number_format($baseRateToBs ?? 0, 2, '.', '') }}"></span>
            <span id="customerId" data-rate="{{ $customerId}}"></span>
            <form id="purchaseForm">
                @csrf
                <!-- Paso 1: Selección del Ítem -->
                <div id="step1" class="step sale-step-panel-step1">
                    <div class="sale-step-title">Paso 1: Selección de productos</div>

                    <div class="sale-toolbar-grid">

                    </div>
                    <div class="sale-section-card">
                    <div class="sale-section-header">
                        <div>
                            <input 
                            type="text" 
                            id="searchCategory" 
                            class="form-control border border-1 p-2 bg-white" 
                            placeholder="Buscar categoría..." 
                            onkeyup="filterCategories()">
                        </div>
                    </div>
                    <div id="categoriesContainer" class="d-flex overflow-auto py-2 mb-1" style="scroll-snap-type: x mandatory;">
                        <div class="category-item flex-shrink-0" style="scroll-snap-align: start;" data-category="all" onclick="filterProductsByCategory('all')">
                            <a href="javascript:void(0)" class="text-decoration-none category-filter sale-category-pill is-active">
                                <i class="material-symbols-rounded" style="font-size: 1rem;">all_inclusive</i>
                                <span>Todos</span>
                            </a>
                        </div>
                        @foreach($categories as $category)

                            <div class="category-item flex-shrink-0" style="scroll-snap-align: start;" data-category-name="{{ $category->name }}" data-category="{{ $category->id }}" onclick="filterProductsByCategory('{{ $category->id }}')">
                                <a href="javascript:void(0)" class="text-decoration-none category-filter sale-category-pill">
                                    <span>{{ $category->name }}</span>
                                </a>
                            </div>
                        @endforeach

                        @if(isset($materialPackages) && $materialPackages->count() > 0)
                            <div class="category-item flex-shrink-0" style="scroll-snap-align: start;" data-category-name="paquetes" data-category="packages" onclick="filterProductsByCategory('packages')">
                                <a href="javascript:void(0)" class="text-decoration-none category-filter sale-category-pill">
                                    <i class="material-symbols-rounded" style="font-size: 1rem;">inventory_2</i>
                                    <span>Paquetes</span>
                                </a>
                            </div>
                        @endif
                    </div>
                    </div>

                                @if(isset($materialPackages) && $materialPackages->count() > 0)
                                    <div id="materialPackagesSection" class="sale-section-card mb-3 material-packages-section" data-category="packages">
                                        <div class="sale-section-header">
                                            <div>
                                                <h6 class="mb-1">Paquetes / Listas de materiales</h6>
                                                <p>Combos rápidos con precio fijo o composición flexible.</p>
                                            </div>
                                        </div>
                                            <div class="row g-3">
                                                @foreach($materialPackages as $package)
                                                    @php
                                                        $firstItem = $package->items->first();
                                                        $firstImage = $firstItem && $firstItem->variant && $firstItem->variant->product && isset($firstItem->variant->product->images[0])
                                                            ? (\App\Support\ImageStorage::url($firstItem->variant->product->images[0]->path) ?? asset('assets/img/shopix5.png'))
                                                            : null;
                                                        $packageTotalBeforeDiscount = $package->items->sum(function($it) {
                                                            $basePrice = (float) ($it->variant->price ?? 0);
                                                            $productDiscount = (float) ($it->variant->product->discount_percentage ?? 0);
                                                            $variantDiscount = (float) ($it->variant->discount_percentage ?? 0);
                                                            $price = $basePrice
                                                                * ((100 - $productDiscount) / 100)
                                                                * ((100 - $variantDiscount) / 100);
                                                            $qty = (float) ($it->quantity ?? 0);
                                                            return $price * $qty;
                                                        });
                                                        $packageDiscount = (float) ($package->discount_percentage ?? 0);
                                                        $packageTotalCalculated = $packageTotalBeforeDiscount * ((100 - $packageDiscount) / 100);
                                                        $packageTotal = !is_null($package->package_price)
                                                            ? (float) $package->package_price
                                                            : $packageTotalCalculated;
                                                    @endphp
                                                    <div class="col-12 col-md-6 col-lg-4 package-item" data-name="{{ strtolower($package->name) }}">
                                                        <div class="card h-100 shadow-sm">
                                                            <div class="card-body">
                                                                <div class="d-flex gap-3 align-items-center mb-2">
                                                                    <div class="icon icon-shape icon-xl shadow bg-transparent text-center border border-1 border-black text-info border-radius-lg flex-shrink-0" style="width: 70px; height: 70px;">
                                                                        @if($firstImage)
                                                                            <img src="{{ $firstImage }}" alt="{{ $package->name }}" style="width: 100%; height: 100%; object-fit: cover; border-radius: inherit;">
                                                                        @else
                                                                            <i class="material-symbols-rounded text-dark">inventory_2</i>
                                                                        @endif
                                                                    </div>
                                                                    <div class="flex-grow-1">
                                                                        <h6 class="mb-1">{{ $package->name }}</h6>
                                                                        <p class="text-sm text-muted mb-0">{{ $package->items->count() }} materiales</p>
                                                                        <p class="text-sm fw-bold mb-0">{{ number_format($packageTotal, 2) }} {{ $baseCurrencyCode ?? 'USD' }}</p>
                                                                        @if(!is_null($package->package_price))
                                                                            <p class="text-xs text-dark mb-0">Precio fijo combo</p>
                                                                        @endif
                                                                        @if($packageDiscount > 0)
                                                                            <p class="text-xs text-success mb-0">Descuento paquete: {{ number_format($packageDiscount, 2) }}%</p>
                                                                        @endif
                                                                    </div>
                                                                </div>
                                                                <div class="d-flex gap-2 align-items-center mt-2">
                                                                    <input type="number" min="1" value="1" class="form-control form-control-sm" id="packageQty_{{ $package->id }}" style="max-width: 90px;">
                                                                    <button
                                                                        type="button"
                                                                        class="btn btn-sm btn-outline-dark mb-0"
                                                                        onclick="addMaterialPackageToSale({{ $package->id }})"
                                                                    >Agregar paquete</button>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    </div>
                                                @endforeach
                                            </div>
                                    </div>
                                @endif

                    <div class="sale-section-card">
                    <div class="sale-section-header">
                        <div>
                            <h6 class="mb-1">Productos del catálogo</h6>
                        </div>
                                                <input 
                            type="text" 
                            id="searchInput" 
                            class="form-control border border-1 p-2 bg-white" 
                            placeholder="Buscar producto..." 
                            onkeyup="filterProducts()">
                    </div>
                    <div class="sale-toolbar-card mb-2">
                        <h6 class="mb-2">Agregar por QR / Código de barras</h6>
                        <div class="d-flex gap-2 flex-wrap">
                            <input type="text" id="scanCodeInput" class="form-control border border-1 p-2 bg-white" placeholder="Escanea o pega el código">
                            <button type="button" class="btn btn-dark mb-0" id="scanCodeBtn">Agregar</button>
                            <button type="button" class="btn btn-outline-dark mb-0" id="openQrScannerBtn" data-bs-toggle="modal" data-bs-target="#scanQrModal">Escanear con cámara</button>
                        </div>
                    </div>
                    <div id="itemSelector" class="sale-products-grid">
                        @foreach($productItems as $item)
                            <div class="product-item" data-category="{{ $item->category_id }}" data-name="{{ strtolower($item->name) }}">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <div class="d-flex gap-4 align-items-center">
                                            <!-- Contenedor de la imagen -->
                                            <a href="{{ route('productItem', $item->id) }}" class="icon icon-shape icon-xl shadow bg-transparent text-center border border-1 border-black text-info border-radius-lg flex-shrink-0" style="width: 70px; height: 70px;">
                                                @if(isset($item->images) && count($item->images) > 0)
                                                    <img src="{{ \App\Support\ImageStorage::url($item->images[0]->path) ?? asset('assets/img/shopix5.png') }}" alt="Imagen del producto" style="width: 100%; height: 100%; object-fit: cover; border-radius: inherit;">
                                                @else
                                                    <i class="material-symbols-rounded text-dark">photo_camera</i>
                                                @endif
                                            </a>
                                            <!-- Contenedor del texto -->
                                            <div class="flex-grow-1">
                                                <h5 class="text-truncate" style="max-width: calc(100% - 80px); overflow: hidden; white-space: nowrap;">{{ $item->name }}</h5>
                                                <p class="text-truncate" style="max-width: calc(100% - 80px); overflow: hidden; white-space: nowrap;">{{ $item->description }}</p>
                                            </div>
                                        </div>
                                        @foreach($item->variants as $variant)
                                            @if($variant->stock > 0)
                                            @php
                                                $productDiscount = (float) ($item->discount_percentage ?? 0);
                                                $variantDiscount = (float) ($variant->discount_percentage ?? 0);
                                                $effectiveVariantPrice = (float) $variant->price * ((100 - $productDiscount) / 100) * ((100 - $variantDiscount) / 100);
                                                $variantCardImagePath = optional($variant->images->first())->path;
                                                $productCardImagePath = optional($item->images->first())->path;
                                                $variantCardImage = $variantCardImagePath
                                                    ? (\App\Support\ImageStorage::url($variantCardImagePath) ?? asset('assets/img/shopix5.png'))
                                                    : ($productCardImagePath
                                                        ? (\App\Support\ImageStorage::url($productCardImagePath) ?? asset('assets/img/shopix5.png'))
                                                        : asset('assets/img/shopix5.png'));
                                            @endphp
                                            <div class="d-flex gap-5 justify-content-between align-items-center variant-row">
                                                <label for="variant_{{ $variant->id }}" class="d-block mt-2 variant-label" style="cursor: pointer;" data-product-name="{{ $item->name }}">
                                                    <input type="checkbox" class="form-check-input me-2 variant-checkbox" id="variant_{{ $variant->id }}" name="selectedVariants[]" value="{{ $variant->id }}"
                                                    data-price="{{ number_format($effectiveVariantPrice, 2, '.', '') }}" data-stock="{{ $variant->stock }}"
                                                    data-product-name="{{ $item->name }}"
                                                    data-size="{{ $variant->size }}"
                                                    data-image-src="{{ $variantCardImage }}"
                                                    data-taxes="{{ $item->taxes }}">
                                                    <span>
                                                        {{$variant->size}} |
                                                        @if($productDiscount > 0 || $variantDiscount > 0)
                                                            <span class="text-decoration-line-through text-muted">{{ number_format((float) $variant->price, 2) }} {{ $baseCurrencyCode ?? 'USD' }}</span>
                                                            <span class="fw-semibold">{{ number_format($effectiveVariantPrice, 2) }} {{ $baseCurrencyCode ?? 'USD' }}</span>
                                                            <small class="text-success">(-{{ number_format($productDiscount + $variantDiscount, 2) }}%)</small>
                                                        @else
                                                            {{ number_format((float) $variant->price, 2) }} {{ $baseCurrencyCode ?? 'USD' }}
                                                        @endif
                                                        | Stock: {{ $variant->stock }}
                                                    </span>
                                                    <i class="check-icon d-none ms-2 text-success fas fa-check"></i>
                                                </label>
                                                <i class="material-symbols-rounded text-info" style="cursor: pointer"
                                                    onclick="showProductDetails('{{ $item->name }}', '{{ $item->description }}', '{{ $variantCardImage }}', '{{ number_format($effectiveVariantPrice, 2, '.', '') }}', '{{ $variant->stock }}', '{{ $variant->size }}')">
                                                    info
                                                </i>
                                            </div>
                                            @endif
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                    </div>
                </div>
                <div id="step2" class="step d-none sale-step-panel">
                    <div class="sale-step-title">Paso 2: Métodos de pago</div>
                    <p class="sale-step-copy">Registra montos, referencias y comprobantes con una lectura más clara del total pendiente.</p>
                    <div class="sale-info-strip">
                        <div class="sale-info-pill">
                            <small>Total</small>
                            <div><span id="totalAmountValue">0.00</span>{{ $baseCurrencySymbol ?? '$' }}</div>
                        </div>
                        <div class="sale-info-pill">
                            <small>Tasa BCV</small>
                            <div><span id="baseRateDisplay" data-rate="{{ number_format($baseRateToBs ?? 0, 2, '.', '') }}">{{ number_format($baseRateToBs ?? 0, 2) }}</span> Bs.</div>
                        </div>
                        <div class="sale-info-pill">
                            <small>Total Bs</small>
                            <div><span id="totalAmountBsValue">0.00</span> Bs</div>
                        </div>
                    </div>
                    <div class="sale-section-card mt-2">
                        @php
                            // Buscar el impuesto IGTF dentro de $taxes
                            $igtfTax = null;
                            foreach($taxes as $tax) {
                                if($tax->name === 'IGTF') {
                                    $igtfTax = $tax;
                                    break;
                                }
                            }
                        @endphp

                        @if($igtfTax && (bool) ($tenant->electronic_invoicing_enabled ?? false) && !$salesSpecialTaxpayer)
                            <strong>
                                Si el método de pago seleccionado es en {{ $baseCurrencyCode ?? 'USD' }} se aplicará el impuesto del IGTF del {{ $igtfTax->rate }}%
                            </strong>
                        @elseif($igtfTax && $salesSpecialTaxpayer)
                            <strong>
                                La tienda está marcada como contribuyente especial, por lo tanto no se aplica IGTF.
                            </strong>
                        @elseif($igtfTax)
                            <strong>
                                La facturación digital está desactivada en la tienda, por lo tanto no se aplica IGTF.
                            </strong>
                        @endif
                    </div>
                    <div id="paymentMethods" class="mb-3 sale-section-card">
                        @php
                            $groupedMethods = $paymentMethods->groupBy(fn($m) => $m->currency->code);
                        @endphp

                        <!-- Botones de monedas -->
                        <div class="btn-group mb-1" role="group">
                            @foreach ($groupedMethods as $currencyCode => $methods)
                                <button type="button" class="btn btn-outline-dark currency-tab" data-currency="{{ $currencyCode }}">
                                    {{ $currencyCode }}
                                </button>
                            @endforeach
                        </div>

                        <!-- Contenedor de métodos de pago por moneda -->
                        @foreach ($groupedMethods as $currencyCode => $methods)
                            <div class="currency-section d-none" data-currency="{{ $currencyCode }}">

                            @foreach ($methods as $method)
                                <div class="card mb-2 p-2">
                                    <div class="d-flex justify-content-between align-items-center payment-method-row">
                                        <div class="d-flex gap-2 align-items-center">
                                            @if ($method->qr_image)
                                                @php $qr = json_decode($method->qr_image)[0] ?? null; @endphp
                                                @if ($qr)
                                                    <img src="{{ \App\Support\ImageStorage::url($qr) ?? asset('assets/img/shopix5.png') }}" alt="QR" style="max-width: 70px; max-height: 70px; cursor: pointer;"
                                                        onclick="showQrModal('{{ \App\Support\ImageStorage::url($qr) ?? asset('assets/img/shopix5.png') }}')">
                                                @endif
                                            @endif
                                            <div>
                                                <strong>{{ $method->name }}</strong>
                                                @if ($method->admin_name) - {{ $method->admin_name }} @endif
                                                @if ($method->bank) ({{ $method->bank }}) @endif
                                                @if ($method->dni)
                                                    <div><small>DNI/Correo: {{ $method->dni }}</small></div>
                                                @endif
                                                @php $noReference = !$method->usesReference(); @endphp
                                                <div id="paymentFields_{{ $method->id }}" class="d-none mt-2" data-currency="{{ $currencyCode }}" data-no-reference="{{ $noReference ? '1' : '0' }}">
                                                    <div id="paymentRows_{{ $method->id }}" class="d-flex flex-column gap-2"></div>
                                                    <button type="button" class="btn btn-outline-dark btn-sm mt-2" onclick="addPaymentEntry('{{ $method->id }}', '{{ $currencyCode }}', {{ $noReference ? 'true' : 'false' }})">
                                                        + Agregar otro pago
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mt-2">
                                            <input type="checkbox" class="form-check-input payment-method-checkbox" id="method_{{ $method->id }}" 
                                                data-method-id="{{ $method->id }}" data-method-name="{{ $method->name }}" data-currency="{{ $currencyCode }}" 
                                                onchange="togglePaymentFields(this)">
                                        </div>
                                    </div>

                                </div>
                            @endforeach
                            </div>
                        @endforeach
                    </div>

                    <div id="paymentSummary" class="mt-3 sale-section-card">
                        <strong>Total ingresado: </strong> {{ $baseCurrencySymbol ?? '$' }} <span id="totalPaid">0.00</span><br>
                        <span class="text-danger paymentMessage"></span>
                    </div>
                    <div id="paymentsContainer" class="mt-3">
                        <!-- Aquí se mostrarán los métodos de pago seleccionados -->
                        <ul id="selectedPaymentMethods" class="list-group">
                            <!-- Los métodos de pago seleccionados se agregarán aquí dinámicamente -->
                        </ul>
                    </div>
                    <div class="sale-step-actions w-100 align-items-center">
                        <button type="button" class="btn btn-secondary mt-3" id="backToStep1">Atrás</button>
                        <button type="button" class="btn btn-info mt-3" id="toStep3" disabled>Siguiente</button>
                    </div>
                </div>

                <div id="step3" class="step d-none sale-step-panel">
                    <div class="sale-step-title">Paso 3: Confirmación</div>
                    <p class="sale-step-copy">Revisa cliente, entrega, documento y estado inicial antes de registrar la venta.</p>

                    <div class="sale-section-card">
                        <h6 class="mb-2">Cliente para esta venta</h6>
                        <div class="d-flex gap-4 flex-wrap mb-2">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="create_new_customer" id="create_customer_no" value="no" checked>
                                <label class="form-check-label" for="create_customer_no">No, usar cliente existente</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="create_new_customer" id="create_customer_yes" value="yes">
                                <label class="form-check-label" for="create_customer_yes">Sí, crear nuevo cliente</label>
                            </div>
                        </div>

                        <div id="existingCustomerForm" class="row g-2">
                            <div class="col-12">
                                <label for="existingCustomerSelect" class="form-label mb-1">Selecciona el cliente existente</label>
                                <select id="existingCustomerSelect" class="form-control border border-1 p-2 bg-white">
                                    <option value="">Selecciona un cliente</option>
                                    @foreach(($existingCustomersForSale ?? collect()) as $customerOption)
                                        <option value="{{ $customerOption->id }}">
                                            {{ $customerOption->name }}{{ $customerOption->email ? ' - ' . $customerOption->email : '' }}{{ $customerOption->phone_number ? ' - ' . $customerOption->phone_number : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <div id="newCustomerForm" class="row g-2 d-none">
                            <div class="col-12 col-md-6">
                                <label for="newCustomerName" class="form-label mb-1">Nombre</label>
                                <input type="text" id="newCustomerName" class="form-control border border-1 p-2 bg-white" placeholder="Nombre del cliente">
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="newCustomerEmail" class="form-label mb-1">Correo</label>
                                <input type="email" id="newCustomerEmail" class="form-control border border-1 p-2 bg-white" placeholder="correo@ejemplo.com (opcional)">
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="newCustomerPhone" class="form-label mb-1">Teléfono</label>
                                <div class="row g-2">
                                    <div class="col-4">
                                        <select id="newCustomerPhoneCode" class="form-control border border-1 p-2 bg-white">
                                            <option value="+58" selected>+58</option>
                                            <option value="+1">+1</option>
                                            <option value="+52">+52</option>
                                            <option value="+57">+57</option>
                                            <option value="+51">+51</option>
                                            <option value="+54">+54</option>
                                            <option value="+34">+34</option>
                                        </select>
                                    </div>
                                    <div class="col-8">
                                        <input type="text" id="newCustomerPhone" class="form-control border border-1 p-2 bg-white" placeholder="4121234567">
                                    </div>
                                </div>
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="newCustomerDni" class="form-label mb-1">DNI</label>
                                <input type="text" id="newCustomerDni" class="form-control border border-1 p-2 bg-white" placeholder="Documento de identidad">
                            </div>
                            <div class="col-12">
                                <small class="text-muted">Al crear cliente nuevo se asigna contraseña temporal <strong>12345678</strong> para que el cliente la cambie luego desde su cuenta en la landing.</small>
                            </div>
                        </div>
                    </div>

                    <div class="sale-section-card">
                        <h6 class="mb-3">Tipo de entrega</h6>
                        <div class="d-flex gap-4 flex-wrap">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="delivery_type" id="delivery_pickup" value="pickup" checked>
                                <label class="form-check-label" for="delivery_pickup">Retiro en tienda</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="delivery_type" id="delivery_shipping" value="shipping">
                                <label class="form-check-label" for="delivery_shipping">Envío</label>
                            </div>
                        </div>
                        <small class="text-muted d-block mt-2" id="deliveryModeHelper">
                            @if($salesFreePlanLock)
                                Delivery habilitado para esta venta administrativa.
                            @elseif($salesDeliveryEnabled)
                                Modelo activo: {{ \App\Support\DeliveryManager::modeLabel($tenant->delivery_fee_mode ?? 'free') }}.
                            @else
                                Delivery disponible (sin configuración de tarifa, se tomará costo 0).
                            @endif
                        </small>

                        <div class="mt-3 d-none" id="deliveryAddressContainer">
                            <label class="form-label">Ubicación de envío</label>
                            <div class="row g-2">
                                <div class="col-12 col-md-4">
                                    <select id="deliveryCountry" class="form-control border border-1 p-2 bg-white">
                                        <option value="">País</option>
                                    </select>
                                </div>
                                <div class="col-12 col-md-4">
                                    <select id="deliveryState" class="form-control border border-1 p-2 bg-white" disabled>
                                        <option value="">Estado (parte del país)</option>
                                    </select>
                                </div>
                                <div class="col-12 col-md-4">
                                    <select id="deliveryCity" class="form-control border border-1 p-2 bg-white" disabled>
                                        <option value="">Ciudad</option>
                                    </select>
                                </div>
                                <div class="col-12">
                                    <input type="text" id="deliveryAddressDetail" class="form-control border border-1 p-2 bg-white" placeholder="Dirección exacta (calle, referencia, etc.)">
                                </div>
                                <div class="col-12 col-md-4 d-none" id="deliveryDistanceContainer">
                                    <label class="form-label mb-1" for="deliveryDistanceKm">Distancia estimada (km)</label>
                                    <input type="number" min="0" step="0.01" id="deliveryDistanceKm" class="form-control border border-1 p-2 bg-white" placeholder="Ej: 6.5">
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="sale-section-card">
                        <h6 class="mb-3">Documento de la venta</h6>
                        <div class="d-flex gap-4 flex-wrap">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="sale_document_mode" id="sale_document_delivery" value="delivery_note" {{ (bool) ($tenant->electronic_invoicing_enabled ?? false) ? '' : 'checked' }}>
                                <label class="form-check-label" for="sale_document_delivery">Orden de entrega</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="sale_document_mode" id="sale_document_electronic" value="electronic_invoice" {{ (bool) ($tenant->electronic_invoicing_enabled ?? false) ? 'checked' : '' }} {{ (bool) ($tenant->electronic_invoicing_enabled ?? false) ? '' : 'disabled' }}>
                                <label class="form-check-label" for="sale_document_electronic">Facturación digital</label>
                            </div>
                        </div>
                        @if(!(bool) ($tenant->electronic_invoicing_enabled ?? false))
                            <small class="text-muted d-block mt-2">La facturación digital está desactivada para esta tienda. Solo se permite orden de entrega.</small>
                        @endif
                    </div>

                    <div class="sale-section-card">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="mb-0">Estado inicial de la venta</h6>
                            <div class="form-check m-0">
                                <input class="form-check-input" type="checkbox" id="saleStatusSelectAll">
                                <label class="form-check-label" for="saleStatusSelectAll">Seleccionar todo</label>
                            </div>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input sale-status-check" type="checkbox" id="markSaleCompleted" checked>
                            <label class="form-check-label" for="markSaleCompleted">Marcar venta como completa</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input sale-status-check" type="checkbox" id="markPaymentsPaid" checked>
                            <label class="form-check-label" for="markPaymentsPaid">Marcar pagos como pagados</label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input sale-status-check" type="checkbox" id="markDelivered">
                            <label class="form-check-label" for="markDelivered">Marcar orden como entregada</label>
                        </div>
                    </div>

                    <div id="summaryContainer" class="mt-3 sale-section-card"></div>
                    <span class="text-danger paymentMessage"></span>

                    <div class="sale-step-actions w-100 align-items-center">
                        <button type="button" class="btn btn-secondary mt-3" id="backToStep2">Atrás</button>
                        <button type="button" class="btn btn-success mt-3" id="confirmPurchase">Confirmar</button>
                    </div>
                </div>

            </form>
        </div>
        <div class="col-12 col-xl-4">
            <div class="offcanvas offcanvas-end offcanvas-admin-desktop" tabindex="-1" id="cart" aria-labelledby="cartOffcanvasLabel" data-bs-scroll="true" data-bs-backdrop="true">
                <div class="offcanvas-header border-bottom">
                    <h4 class="offcanvas-title m-0" id="cartOffcanvasLabel">Carrito</h4>
                    <div class="d-flex align-items-center gap-2">
                        <button type="button" class="btn btn-outline-secondary btn-sm d-xl-none" data-bs-dismiss="offcanvas">Cerrar</button>
                        <button type="button" class="btn-close d-xl-none" data-bs-dismiss="offcanvas" aria-label="Close"></button>
                    </div>
                </div>
                <div class="offcanvas-body p-3 p-xl-4">
                    <ul id="cartList" class="list-group gap-1"></ul>
                    <div class="mt-3">
                        <strong>Sub Total:</strong> {{ $baseCurrencySymbol ?? '$' }}<span id="cartSubTotal">0.00</span>
                    </div>
                    <div class="mt-3">
                        <strong>Delivery:</strong> {{ $baseCurrencySymbol ?? '$' }}<span id="cartDeliveryFee">0.00</span>
                        <small class="text-muted d-block" id="cartDeliveryMode">Retiro en tienda</small>
                    </div>
                    <div class="mt-3 igtf-class" style="display: none;">
                        <strong>Total sin IGTF:</strong> {{ $baseCurrencySymbol ?? '$' }}<span id="cartTotalIGTF">0.00</span>
                    </div>
                    <div class="mt-3">
                        <strong>Total:</strong> {{ $baseCurrencySymbol ?? '$' }}<span id="cartTotal">0.00</span>
                    </div>
                    <div class="mt-3">
                        <strong>Sub Total Bs:</strong>Bs<span id="cartSubTotalBs">0.00</span>
                    </div>
                    <div class="mt-3">
                        <strong>Total Bs:</strong>Bs<span id="cartTotalBs">0.00</span>
                    </div>
                    <div class="mt-3" id="taxesContainer">
                        <!-- Aquí se mostrarán los impuestos aplicados -->
                    </div>
                    <div class="d-flex justify-content-end mt-auto">
                        <button type="button" class="btn btn-dark mt-3" id="toStep2" disabled>Siguiente</button>
                    </div>
                </div>
            </div>
        </div>
        </div>
    </div>
    </div>
<!-- Modal para Detalles del Producto -->
<div class="modal fade" id="productDetailModal" tabindex="-1" aria-labelledby="productDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="productDetailModalLabel">Detalles del Producto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex gap-4 product-detail-layout">
                    <!-- Imagen del producto -->
                    <div class="product-detail-image" style="width: 200px; height: 200px;">
                        <img id="modalProductImage" src="" alt="Imagen del producto" style="width: 100%; height: 100%; object-fit: cover; border-radius: 8px;">
                    </div>
                    <!-- Información del producto -->
                    <div>
                        <h5 id="modalProductName"></h5>
                        <p id="modalProductDescription"></p>
                        <p><strong>Precio:</strong> {{ $baseCurrencySymbol ?? '$' }}<span id="modalProductPrice"></span></p>
                        <p><strong>Stock:</strong> <span id="modalProductStock"></span></p>
                        <p><strong>Variante:</strong> <span id="modalProductSize"></span></p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
<!-- Modal para mostrar el QR -->
<div class="modal fade" id="qrModal" tabindex="-1" aria-labelledby="qrModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-body text-center">
                <img id="qrModalImage" src="" alt="QR Code" style="max-width: 100%; height: auto; border-radius: 8px;">
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="scanQrModal" tabindex="-1" aria-labelledby="scanQrModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="scanQrModalLabel">Escanear QR del producto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div id="qrScannerReader" style="width:100%; min-height: 280px;"></div>
                <small class="text-muted d-block mt-2">Apunta la cámara al QR o código de barras del producto para agregarlo automáticamente.</small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="packageFlavorModal" tabindex="-1" aria-labelledby="packageFlavorModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="packageFlavorModalLabel">Seleccionar sabores del combo</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div id="packageFlavorSummary" class="mb-3"></div>
                <div id="packageFlavorRows" class="d-flex flex-column gap-3"></div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-dark" id="confirmPackageFlavorBtn">Agregar al carrito</button>
            </div>
        </div>
    </div>
</div>
    @endsection

@push('scripts')
<!-- Core JS Files -->
<script src="{{ asset('assets/js/core/popper.min.js') }}"></script>
<script src="{{ asset('assets/js/core/bootstrap.min.js') }}"></script>
<script src="https://unpkg.com/html5-qrcode" defer></script>
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <script>
        var selectedItems = [];
        var totalAmount = 0;
        var subTotalAmount = 0;
        let payments = []; 
        let totalPaid = 0; 
        let scanCodeDebounceTimer = null;
        let scanCodeRequestInFlight = false;
        let qrScannerInstance = null;
        let qrScannerRunning = false;
        let qrScannerLock = false;
        let qrScannerStartInFlight = false;
        let qrScannerPermissionGranted = false;
        let qrScannerCameraId = null;
        const igtfTax = @json($taxes->firstWhere('name', 'IGTF'));

        const baseCurrencyCode = @json($baseCurrencyCode ?? 'USD');
        const normalizedBaseCurrencyCode = String(baseCurrencyCode || 'USD').toUpperCase();
        const baseCurrencySymbol = @json($baseCurrencySymbol ?? '$');
        const baseRateToBs = Number(@json($baseRateToBs ?? 0));
        const dollarRateToBs = Number(@json($dollarRate->rate ?? 0));
        const euroRateToBs = Number(@json($euroRate->rate ?? 0));
        const tenantElectronicInvoicingEnabled = @json((bool) ($tenant->electronic_invoicing_enabled ?? false));
        const tenantSpecialTaxpayer = @json((bool) ($tenant->special_taxpayer ?? false));
        const tenantDeliveryConfig = @json(\App\Support\DeliveryManager::settings($tenant));
        const existingCustomersForSale = @json(($existingCustomersForSale ?? collect())->values());
        
        const authUser = @json($authUser);
        let selectedExistingCustomerId = Number(existingCustomersForSale?.[0]?.id || 0);
        let currentDeliveryFee = 0;

        function initializeExistingCustomerSelect() {
            const selectElement = document.getElementById('existingCustomerSelect');
            if (!selectElement || !window.jQuery || !window.jQuery.fn?.select2) {
                return;
            }

            const $select = window.jQuery(selectElement);
            if ($select.hasClass('select2-hidden-accessible')) {
                return;
            }

            $select.select2({
                width: '100%',
                placeholder: 'Busca por nombre, correo o teléfono',
                allowClear: true,
            });

            $select.on('change', function () {
                selectedExistingCustomerId = Number(this.value || 0);
                if (!document.getElementById('step3').classList.contains('d-none')) {
                    renderSummary();
                }
            });
        }

        function appendFormDataValue(formData, key, value) {
            if (value === undefined || value === null) {
                return;
            }

            if (value instanceof File) {
                formData.append(key, value);
                return;
            }

            if (Array.isArray(value)) {
                value.forEach((item, index) => appendFormDataValue(formData, `${key}[${index}]`, item));
                return;
            }

            if (typeof value === 'object') {
                Object.entries(value).forEach(([childKey, childValue]) => {
                    appendFormDataValue(formData, `${key}[${childKey}]`, childValue);
                });
                return;
            }

            if (typeof value === 'boolean') {
                formData.append(key, value ? '1' : '0');
                return;
            }

            formData.append(key, String(value));
        }
        @php
            $materialPackagesPayload = ($materialPackages ?? collect())->map(function ($package) {
                return [
                    'id' => $package->id,
                    'name' => $package->name,
                    'discount_percentage' => (float) ($package->discount_percentage ?? 0),
                    'package_price' => !is_null($package->package_price) ? (float) $package->package_price : null,
                    'items' => $package->items->map(function ($item) {
                        $basePrice = (float) ($item->variant->price ?? 0);
                        $productDiscount = (float) ($item->variant->product->discount_percentage ?? 0);
                        $variantDiscount = (float) ($item->variant->discount_percentage ?? 0);
                        $effectivePrice = $basePrice * ((100 - $productDiscount) / 100) * ((100 - $variantDiscount) / 100);

                        $selectableVariants = collect($item->variant->product->variants ?? [])
                            ->where('stock', '>', 0)
                            ->map(function ($variant) {
                                $variantBasePrice = (float) ($variant->price ?? 0);
                                $variantProductDiscount = (float) ($variant->product->discount_percentage ?? 0);
                                $variantOwnDiscount = (float) ($variant->discount_percentage ?? 0);
                                $variantImagePath = optional($variant->images->first())->path;
                                $productImagePath = optional($variant->product->images->first())->path;

                                return [
                                    'variant_id' => (int) $variant->id,
                                    'variant_size' => (string) ($variant->size ?? ''),
                                    'variant_stock' => (float) ($variant->stock ?? 0),
                                    'variant_price' => $variantBasePrice * ((100 - $variantProductDiscount) / 100) * ((100 - $variantOwnDiscount) / 100),
                                    'product_name' => $variant->product->name ?? 'Producto',
                                    'image_src' => $variantImagePath
                                        ? (\App\Support\ImageStorage::url($variantImagePath) ?? asset('assets/img/shopix5.png'))
                                        : ($productImagePath
                                            ? (\App\Support\ImageStorage::url($productImagePath) ?? asset('assets/img/shopix5.png'))
                                            : asset('assets/img/shopix5.png')),
                                    'taxes' => ($variant->product && $variant->product->taxes)
                                        ? $variant->product->taxes->map(function ($tax) {
                                            return [
                                                'name' => $tax->name,
                                                'rate' => (float) $tax->rate,
                                            ];
                                        })->values()->toArray()
                                        : [],
                                ];
                            })
                            ->values()
                            ->toArray();

                        $itemVariantImagePath = optional($item->variant->images->first())->path;
                        $itemProductImagePath = optional($item->variant->product->images->first())->path;

                        return [
                            'variant_id' => $item->product_variant_id,
                            'selection_mode' => $item->selection_mode ?? 'variant',
                            'variant_size' => $item->variant->size ?? '',
                            'variant_stock' => (float) ($item->variant->stock ?? 0),
                            'variant_price' => (float) $effectivePrice,
                            'product_name' => $item->variant->product->name ?? 'Producto',
                            'image_src' => $itemVariantImagePath
                                ? (\App\Support\ImageStorage::url($itemVariantImagePath) ?? asset('assets/img/shopix5.png'))
                                : ($itemProductImagePath
                                    ? (\App\Support\ImageStorage::url($itemProductImagePath) ?? asset('assets/img/shopix5.png'))
                                    : asset('assets/img/shopix5.png')),
                            'quantity' => (float) ($item->quantity ?? 0),
                            'selectable_variants' => (($item->selection_mode ?? 'variant') === 'product')
                                ? $selectableVariants
                                : [[
                                    'variant_id' => (int) $item->product_variant_id,
                                    'variant_size' => (string) ($item->variant->size ?? ''),
                                    'variant_stock' => (float) ($item->variant->stock ?? 0),
                                    'variant_price' => (float) $effectivePrice,
                                    'product_name' => $item->variant->product->name ?? 'Producto',
                                    'image_src' => $itemVariantImagePath
                                        ? (\App\Support\ImageStorage::url($itemVariantImagePath) ?? asset('assets/img/shopix5.png'))
                                        : ($itemProductImagePath
                                            ? (\App\Support\ImageStorage::url($itemProductImagePath) ?? asset('assets/img/shopix5.png'))
                                            : asset('assets/img/shopix5.png')),
                                    'taxes' => ($item->variant && $item->variant->product && $item->variant->product->taxes)
                                        ? $item->variant->product->taxes->map(function ($tax) {
                                            return [
                                                'name' => $tax->name,
                                                'rate' => (float) $tax->rate,
                                            ];
                                        })->values()->toArray()
                                        : [],
                                ]],
                            'taxes' => ($item->variant && $item->variant->product && $item->variant->product->taxes)
                                ? $item->variant->product->taxes->map(function ($tax) {
                                    return [
                                        'name' => $tax->name,
                                        'rate' => (float) $tax->rate,
                                    ];
                                })->values()->toArray()
                                : [],
                        ];
                    })->values()->toArray(),
                ];
            })->values();
        @endphp
        const materialPackages = @json($materialPackagesPayload);
        let pendingPackageSelection = null;

        function calculateTaxRateFromTaxes(taxes) {
            return (taxes || []).reduce((sum, tax) => sum + (parseFloat(tax.rate) || 0), 0);
        }

        function isBolivarCurrencyCode(currencyCode) {
            const normalized = String(currencyCode || '').toUpperCase().trim();
            return ['BS', 'VES', 'VED', 'VEF', 'BOLIVAR', 'BOLIVARES'].includes(normalized);
        }

        function roundMoney(value) {
            const numeric = Number(value || 0);

            if (!Number.isFinite(numeric)) {
                return 0;
            }

            return Math.round((numeric + Number.EPSILON) * 100) / 100;
        }

        function convertAmountToBaseCurrency(amount, currency) {
            const value = Number(amount || 0);
            const sourceCurrency = String(currency || '').toUpperCase().trim();

            if (!Number.isFinite(value) || value <= 0) {
                return 0;
            }

            if (sourceCurrency === normalizedBaseCurrencyCode) {
                return roundMoney(value);
            }

            if (isBolivarCurrencyCode(sourceCurrency)) {
                return baseRateToBs > 0 ? roundMoney(value / baseRateToBs) : 0;
            }

            if (sourceCurrency === 'USD' && normalizedBaseCurrencyCode === 'EUR') {
                return (dollarRateToBs > 0 && euroRateToBs > 0) ? roundMoney((value * dollarRateToBs) / euroRateToBs) : 0;
            }

            if (sourceCurrency === 'EUR' && normalizedBaseCurrencyCode === 'USD') {
                return (euroRateToBs > 0 && dollarRateToBs > 0) ? roundMoney((value * euroRateToBs) / dollarRateToBs) : 0;
            }

            return roundMoney(value);
        }

        function getSelectableVariantsForComponent(component) {
            const selectableVariants = Array.isArray(component.selectable_variants) ? component.selectable_variants : [];
            if (selectableVariants.length > 0) {
                return selectableVariants;
            }

            return [{
                variant_id: Number(component.variant_id),
                variant_size: component.variant_size || '',
                variant_stock: Number(component.variant_stock || 0),
                variant_price: Number(component.variant_price || 0),
                product_name: component.product_name || 'Producto',
                taxes: Array.isArray(component.taxes) ? component.taxes : [],
            }];
        }

        function openPackageFlavorModal(packageId) {
            const pkg = materialPackages.find(p => Number(p.id) === Number(packageId));
            if (!pkg) {
                alert('No se encontró el paquete seleccionado.');
                return;
            }

            const qtyInput = document.getElementById(`packageQty_${packageId}`);
            const packQty = Math.max(1, parseInt(qtyInput?.value || '1', 10));

            const hasFlexibleComponents = (pkg.items || []).some(component => String(component.selection_mode || 'variant') === 'product');
            if (!hasFlexibleComponents) {
                addFixedOnlyMaterialPackageToSale(pkg, packQty);
                return;
            }

            const components = (pkg.items || []).map((component, index) => {
                const requiredQty = (parseFloat(component.quantity) || 0) * packQty;
                const choices = getSelectableVariantsForComponent(component)
                    .filter(choice => Number(choice.variant_stock || 0) > 0)
                    .map(choice => ({
                        variant_id: Number(choice.variant_id),
                        variant_size: String(choice.variant_size || ''),
                        variant_stock: Number(choice.variant_stock || 0),
                        variant_price: Number(choice.variant_price || 0),
                        product_name: choice.product_name || component.product_name || 'Producto',
                        image_src: choice.image_src || component.image_src || null,
                        taxes: Array.isArray(choice.taxes) ? choice.taxes : [],
                        quantity: 0,
                    }));

                if (choices.length > 0 && requiredQty > 0) {
                    const preferredIndex = choices.findIndex(choice => Number(choice.variant_id) === Number(component.variant_id));
                    if (preferredIndex >= 0) {
                        choices[preferredIndex].quantity = requiredQty;
                    } else {
                        choices[0].quantity = requiredQty;
                    }
                }

                return {
                    component_id: `${pkg.id}_${index}`,
                    product_name: component.product_name || 'Producto',
                    required_qty: requiredQty,
                    choices,
                };
            }).filter(component => component.required_qty > 0 && component.choices.length > 0);

            if (components.length === 0) {
                alert('Este paquete no tiene variantes disponibles con stock.');
                return;
            }

            pendingPackageSelection = {
                package: pkg,
                packageQty: packQty,
                components,
            };

            renderPackageFlavorModal();
            const modalElement = document.getElementById('packageFlavorModal');
            bootstrap.Modal.getOrCreateInstance(modalElement).show();
        }

        function renderPackageFlavorModal() {
            if (!pendingPackageSelection) {
                return;
            }

            const summary = document.getElementById('packageFlavorSummary');
            const rows = document.getElementById('packageFlavorRows');
            if (!summary || !rows) {
                return;
            }

            const pkg = pendingPackageSelection.package;
            summary.innerHTML = `
                <div class="alert alert-light border mb-0">
                    <strong>${pkg.name}</strong><br>
                    Cantidad de paquetes: ${pendingPackageSelection.packageQty}
                </div>
            `;

            rows.innerHTML = '';
            pendingPackageSelection.components.forEach((component, componentIndex) => {
                const choicesHtml = component.choices.map((choice, choiceIndex) => `
                    <div class="row g-2 align-items-center mb-2">
                        <div class="col-12 col-md-6">
                            <div class="d-flex align-items-center gap-2">
                                <img src="${choice.image_src || '/assets/img/shopix5.png'}" alt="${choice.product_name || 'Producto'}" style="width:56px;height:56px;object-fit:cover;border-radius:12px;border:1px solid #e5e7eb;flex-shrink:0;" onerror="this.onerror=null;this.src='/assets/img/shopix5.png';">
                                <div>
                                    <small class="text-muted d-block">Variante</small>
                                    <strong>${choice.product_name} ${choice.variant_size || ''}</strong>
                                </div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <small class="text-muted d-block">Stock</small>
                            <span>${choice.variant_stock}</span>
                        </div>
                        <div class="col-6 col-md-3">
                            <small class="text-muted d-block">Cantidad</small>
                            <input
                                type="number"
                                min="0"
                                max="${choice.variant_stock}"
                                step="0.01"
                                class="form-control form-control-sm"
                                value="${choice.quantity}"
                                data-package-component-index="${componentIndex}"
                                data-package-choice-index="${choiceIndex}"
                            >
                        </div>
                    </div>
                `).join('');

                rows.insertAdjacentHTML('beforeend', `
                    <div class="card border mb-2">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <h6 class="mb-0">${component.product_name}</h6>
                                <span class="badge bg-dark">Requerido: ${component.required_qty}</span>
                            </div>
                            ${choicesHtml}
                        </div>
                    </div>
                `);
            });
        }

        function confirmPackageFlavorSelection() {
            if (!pendingPackageSelection) {
                return;
            }

            const pkg = pendingPackageSelection.package;
            const packageDiscount = Math.max(0, Math.min(100, parseFloat(pkg.discount_percentage || 0)));
            const selectedRows = [];

            for (let componentIndex = 0; componentIndex < pendingPackageSelection.components.length; componentIndex += 1) {
                const component = pendingPackageSelection.components[componentIndex];

                for (let choiceIndex = 0; choiceIndex < component.choices.length; choiceIndex += 1) {
                    const choice = component.choices[choiceIndex];
                    const qtyInput = document.querySelector(`[data-package-component-index="${componentIndex}"][data-package-choice-index="${choiceIndex}"]`);
                    const qty = parseFloat(qtyInput?.value || '0') || 0;

                    if (qty <= 0) {
                        continue;
                    }

                    if (qty > Number(choice.variant_stock || 0)) {
                        alert(`La cantidad para ${choice.product_name} ${choice.variant_size || ''} supera el stock.`);
                        return;
                    }

                    selectedRows.push({
                        variant_id: Number(choice.variant_id),
                        qty,
                        stock: Number(choice.variant_stock || 0),
                        product_name: choice.product_name,
                        variant_size: choice.variant_size,
                        variant_price: Number(choice.variant_price || 0),
                        image_src: choice.image_src || null,
                        taxes: Array.isArray(choice.taxes) ? choice.taxes : [],
                    });
                }
            }

            if (selectedRows.length === 0) {
                alert('Debes seleccionar al menos una variante para continuar.');
                return;
            }

            const packageBaseTotal = selectedRows.reduce((sum, row) => {
                return sum + (row.variant_price * ((100 - packageDiscount) / 100) * row.qty);
            }, 0);

            const targetPackageTotal = (pkg.package_price !== null && pkg.package_price !== undefined)
                ? (Number(pkg.package_price) || 0)
                : packageBaseTotal;

            const priceScale = packageBaseTotal > 0 ? (targetPackageTotal / packageBaseTotal) : 1;
            const combinedLineMultiplier = ((100 - packageDiscount) / 100) * priceScale;
            const combinedLineDiscount = (1 - combinedLineMultiplier) * 100;

            for (const row of selectedRows) {
                const variantId = String(row.variant_id);
                const price = row.variant_price * combinedLineMultiplier;
                const taxRate = calculateTaxRateFromTaxes(row.taxes || []);
                const taxAmount = price * (taxRate / 100);
                const totalPrice = price + taxAmount;

                const existing = selectedItems.find(item => String(item.id) === variantId);
                if (existing) {
                    const nextQty = Number(existing.quantity || 0) + row.qty;
                    if (nextQty > Number(row.stock || existing.stock || 0)) {
                        alert(`Stock insuficiente para ${row.product_name} ${row.variant_size || ''}.`);
                        return;
                    }
                    existing.quantity = nextQty;
                } else {
                    selectedItems.push({
                        id: variantId,
                        productName: `${row.product_name} [${pkg.name}]`,
                        productSize: row.variant_size,
                        price,
                        stock: Number(row.stock || 0),
                        quantity: row.qty,
                        line_discount_percentage: combinedLineDiscount,
                        imageSrc: row.image_src || null,
                        taxes: row.taxes || [],
                        taxRate,
                        taxAmount,
                        totalPrice,
                    });
                }

                const checkbox = document.getElementById(`variant_${variantId}`);
                if (checkbox) {
                    checkbox.checked = true;
                }
            }

            recalcSubtotals();
            renderCart();
            bootstrap.Modal.getOrCreateInstance(document.getElementById('packageFlavorModal')).hide();
            alert(`Paquete "${pkg.name}" agregado al carrito.`);
            pendingPackageSelection = null;
        }

        function addMaterialPackageToSale(packageId) {
            openPackageFlavorModal(packageId);
        }

        function addFixedOnlyMaterialPackageToSale(pkg, packQty) {
            (pkg.items || []).forEach(component => {
                const variantId = String(component.variant_id);
                const componentQty = (parseFloat(component.quantity) || 0) * packQty;
                if (componentQty <= 0) {
                    return;
                }

                const taxes = component.taxes || [];
                const taxRate = calculateTaxRateFromTaxes(taxes);
                const packageDiscount = Math.max(0, Math.min(100, parseFloat(pkg.discount_percentage || 0)));
                const priceBeforePackageDiscount = parseFloat(component.variant_price) || 0;
                const baseLineMultiplier = ((100 - packageDiscount) / 100);

                const packageBaseTotal = (pkg.items || []).reduce((sum, row) => {
                    const rowQty = parseFloat(row.quantity) || 0;
                    const rowBasePrice = parseFloat(row.variant_price) || 0;
                    return sum + (rowBasePrice * ((100 - packageDiscount) / 100) * rowQty);
                }, 0);

                const targetPackageTotal = (pkg.package_price !== null && pkg.package_price !== undefined)
                    ? (parseFloat(pkg.package_price) || 0)
                    : packageBaseTotal;

                const priceScale = packageBaseTotal > 0 ? (targetPackageTotal / packageBaseTotal) : 1;
                const combinedLineMultiplier = baseLineMultiplier * priceScale;
                const price = priceBeforePackageDiscount * combinedLineMultiplier;
                const taxAmount = price * (taxRate / 100);
                const totalPrice = price + taxAmount;
                const combinedLineDiscount = (1 - combinedLineMultiplier) * 100;

                const existing = selectedItems.find(item => String(item.id) === variantId);
                if (existing) {
                    existing.quantity = Number(existing.quantity || 0) + componentQty;
                } else {
                    selectedItems.push({
                        id: variantId,
                        productName: `${component.product_name} [${pkg.name}]`,
                        productSize: component.variant_size,
                        price,
                        stock: Number(component.variant_stock || 999999),
                        quantity: componentQty,
                        line_discount_percentage: combinedLineDiscount,
                        imageSrc: component.image_src || null,
                        taxes,
                        taxRate,
                        taxAmount,
                        totalPrice,
                    });
                }

                const checkbox = document.getElementById(`variant_${variantId}`);
                if (checkbox) {
                    checkbox.checked = true;
                }
            });

            recalcSubtotals();
            renderCart();
            alert(`Paquete "${pkg.name}" agregado al carrito.`);
        }

        document.addEventListener('DOMContentLoaded', function () {
            // Escuchar todos los checkboxes
            const checkboxes = document.querySelectorAll('input[name="selectedVariants[]"]');
            initializeExistingCustomerSelect();
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', handleCheckboxChange);
            });

            const cartElement = document.getElementById('cart');
            if (cartElement) {
                cartElement.addEventListener('hidden.bs.offcanvas', function () {
                    document.body.classList.remove('modal-open');
                    document.body.style.removeProperty('overflow');
                    document.body.style.removeProperty('padding-right');
                    document.querySelectorAll('.offcanvas-backdrop').forEach(el => el.remove());
                });
            }
        });

        function closeCartOffcanvas(forceCleanup = false) {
            const cartElement = document.getElementById('cart');
            if (!cartElement) return;

            const isMobileOffcanvas = window.innerWidth < 1200;

            if (isMobileOffcanvas) {
                const offcanvas = bootstrap.Offcanvas.getInstance(cartElement);
                if (offcanvas) {
                    offcanvas.hide();
                }
            }

            if (forceCleanup) {
                setTimeout(() => {
                    document.body.classList.remove('modal-open');
                    document.body.style.removeProperty('overflow');
                    document.body.style.removeProperty('padding-right');
                    document.querySelectorAll('.offcanvas-backdrop').forEach(el => el.remove());
                }, 120);
            }
        }
        function handleCheckboxChange(e) {
            const checkbox = e.target;
            const id = checkbox.value;
            const productName = checkbox.getAttribute('data-product-name');
            const productSize = checkbox.getAttribute('data-size');
            const stock = parseInt(checkbox.getAttribute('data-stock')) || 0;
            const price = parseFloat(checkbox.getAttribute('data-price')) || 0;
            const taxesString = checkbox.getAttribute('data-taxes');
            const imageSrc = checkbox.getAttribute('data-image-src') || '';
            const selectedQty = Math.max(1, parseInt(checkbox.dataset.selectedQty || '1', 10) || 1);
            delete checkbox.dataset.selectedQty;

            // Convertir a JSON
            const taxes = taxesString ? JSON.parse(taxesString) : [];

            // Sumar tasas de impuestos
            const totalTaxRate = taxes.reduce((sum, tax) => sum + parseFloat(tax.rate), 0);

            // Cálculo del impuesto
            const taxAmount = price * (totalTaxRate / 100);

            // Precio final = precio base + impuestos
            const totalPrice = price + taxAmount;

            console.log(`Tasas acumuladas (${id}): ${totalTaxRate}%`);
            console.log(`Precio base: ${price} => Total con impuestos: ${totalPrice}`);

            if (checkbox.checked) {
                selectedItems.push({
                    id,
                    productName,
                    productSize,
                    price,
                    stock,
                    quantity: selectedQty,
                    line_discount_percentage: 0,
                    imageSrc,
                    taxes,
                    taxRate: totalTaxRate,
                    taxAmount,
                    totalPrice // <--- Guardar precio con impuestos
                });
            } else {
                selectedItems = selectedItems.filter(item => item.id !== id);
            }

            recalcSubtotals();
            renderCart();
        }

        function getAdminDeliveryModeLabel(mode, distanceKm = null) {
            if (mode === 'distance') {
                return distanceKm && distanceKm > 0
                    ? `Delivery por km (${distanceKm.toFixed(2)} km)`
                    : 'Delivery por km';
            }

            if (mode === 'fixed') {
                return 'Delivery con tarifa fija';
            }

            if (mode === 'free') {
                return 'Delivery gratis';
            }

            return 'Retiro en tienda';
        }

        function getAdminDeliveryChargeContext(strict = false) {
            const selectedType = document.querySelector('input[name="delivery_type"]:checked')?.value || 'pickup';
            const distanceInput = document.getElementById('deliveryDistanceKm');
            const distanceValue = Number(distanceInput?.value || 0);

            if (selectedType !== 'shipping') {
                return {
                    valid: true,
                    fee: 0,
                    mode: 'pickup',
                    distanceKm: null,
                    label: 'Retiro en tienda',
                };
            }

            if (!tenantDeliveryConfig?.enabled) {
                return {
                    valid: true,
                    fee: 0,
                    mode: 'free',
                    distanceKm: null,
                    label: 'Delivery gratis',
                };
            }

            const normalizedMode = tenantDeliveryConfig.mode || 'free';
            if (normalizedMode === 'fixed') {
                return {
                    valid: true,
                    fee: Number(tenantDeliveryConfig.fixed_fee || 0),
                    mode: normalizedMode,
                    distanceKm: null,
                    label: getAdminDeliveryModeLabel(normalizedMode),
                };
            }

            if (normalizedMode === 'distance') {
                if (distanceValue <= 0) {
                    return {
                        valid: !strict,
                        fee: 0,
                        mode: normalizedMode,
                        distanceKm: null,
                        label: getAdminDeliveryModeLabel(normalizedMode),
                        message: 'Debes indicar la distancia estimada del delivery en kilómetros.',
                    };
                }

                return {
                    valid: true,
                    fee: Number(tenantDeliveryConfig.fee_per_km || 0) * distanceValue,
                    mode: normalizedMode,
                    distanceKm: distanceValue,
                    label: getAdminDeliveryModeLabel(normalizedMode, distanceValue),
                };
            }

            return {
                valid: true,
                fee: 0,
                mode: 'free',
                distanceKm: null,
                label: getAdminDeliveryModeLabel('free'),
            };
        }

function updateQuantity(id, newQty) {
    const item = selectedItems.find(item => item.id === id);
    if (!item) return;

    newQty = parseInt(newQty) || 1;
    if (newQty < 1) newQty = 1;
    if (newQty > item.stock) newQty = item.stock;

    // Restar el subtotal y los impuestos anteriores
    subTotalAmount -= (item.price * item.quantity);

    // Actualizar cantidad
    item.quantity = newQty;

    // Sumar precio base nuevo
    subTotalAmount += (item.price * item.quantity);

    renderCart();
}


        function renderCart() {
            const cartList = document.getElementById('cartList');
            const cartTotal = document.getElementById('cartTotal');
            const cartSubTotal = document.getElementById('cartSubTotal');
            const adminCartCount = document.getElementById('adminCartCount');
            const cartTotalBs = document.getElementById('cartTotalBs');
            const cartSubTotalBs = document.getElementById('cartSubTotalBs');
            const cartTotalIGTF = document.getElementById('cartTotalIGTF');
            const cartDeliveryFee = document.getElementById('cartDeliveryFee');
            const cartDeliveryMode = document.getElementById('cartDeliveryMode');
            const totalAmountValue = document.getElementById('totalAmountValue');
            const totalAmountBsValue = document.getElementById('totalAmountBsValue');
            const toStep2Btn = document.getElementById('toStep2');

            cartList.innerHTML = '';

            selectedItems.forEach(item => {
                const li = document.createElement('li');
                li.className = 'list-group-item d-flex justify-content-between align-items-start flex-column';

                const textDiv = document.createElement('div');
                textDiv.className = 'd-flex gap-2 w-100';
                textDiv.innerHTML = `
                    <img src="${item.imageSrc || '/assets/img/shopix5.png'}" alt="${item.productName}" style="width:64px;height:64px;object-fit:cover;border-radius:12px;border:1px solid #e5e7eb;flex-shrink:0;" onerror="this.onerror=null;this.src='/assets/img/shopix5.png';">
                    <div>
                        <strong>${item.productName} ${item.productSize}</strong><br>
                        Subtotal: ${(item.price * item.quantity).toFixed(2)} ${baseCurrencyCode}
                        <br>
                        Impuestos:<br>
                        ${item.taxes.map(tax => `• ${tax.name} (${parseFloat(tax.rate)}%)`).join('<br>')}
                        <br>
                        <strong>Total con Impuestos: ${(item.totalPrice * item.quantity).toFixed(2)} ${baseCurrencyCode}</strong>
                    </div>
                `;


                const controlsDiv = document.createElement('div');
                controlsDiv.className = 'd-flex align-items-center justify-content-between w-100 mt-2';

                // Div para cantidad y el input con gap-2
                const quantityDiv = document.createElement('div');
                quantityDiv.className = 'd-flex align-items-center gap-2';

                const quantityLabel = document.createElement('label');
                quantityLabel.className = 'd-flex align-items-center mt-2';
                quantityLabel.textContent = 'Cantidad: ';
                // quantityLabel.className = 'me-2';

                const quantityInput = document.createElement('input');
                quantityInput.type = 'number';
                quantityInput.min = '1';
                quantityInput.max = item.stock;
                quantityInput.value = item.quantity;
                quantityInput.className = 'form-control qty-edit';
                quantityInput.style.width = '80px';
                quantityInput.style.height = 'fit-content';
                quantityInput.style.padding = '0.25rem 0.5rem';
                quantityInput.style.border = '1px solid #ced4da';
                quantityInput.oninput = () => updateQuantity(item.id, parseInt(quantityInput.value));

                quantityDiv.appendChild(quantityLabel);
                quantityDiv.appendChild(quantityInput);

                const removeBtn = document.createElement('button');
                removeBtn.className = 'btn btn-sm btn-danger mt-3 delete-button';
                removeBtn.innerText = 'X';
                removeBtn.onclick = () => removeFromCart(item.id);

                controlsDiv.appendChild(quantityDiv); // Agregar el div de cantidad e input
                controlsDiv.appendChild(removeBtn);  // Botón de eliminar al extremo derecho

                li.appendChild(textDiv);
                li.appendChild(controlsDiv);
                cartList.appendChild(li);
            });

            const cartTotalQty = selectedItems.reduce((sum, item) => sum + Number(item.quantity || 0), 0);
            if (adminCartCount) {
                adminCartCount.textContent = cartTotalQty;
            }
            const taxesContainer = document.getElementById('taxesContainer');

            const { totalBaseCurrency, tax } = calculateIgtfTax();

            taxesContainer.innerHTML = `
                <div class="mt-3 igtf-class" style="display: none;">
                    <strong>Total Pagado en ${baseCurrencyCode}:</strong> ${baseCurrencySymbol}${totalBaseCurrency.toFixed(2)}
                </div>
                <div class="mt-1 text-danger igtf-class" style="display: none;">
                    <strong>Impuesto IGTF:</strong> ${baseCurrencySymbol}${tax.toFixed(2)}
                </div>
            `;
            
            let totalItemsWithTaxes = selectedItems.reduce((acc, item) => {
                return acc + (item.totalPrice * item.quantity);
            }, 0);

            if(isIgtfEnabledForSale() && totalBaseCurrency > 0) {
                document.querySelectorAll('.igtf-class').forEach(el => el.style.display = 'block');
            } else {
                document.querySelectorAll('.igtf-class').forEach(el => el.style.display = 'none');
            }

            const deliveryContext = getAdminDeliveryChargeContext(false);
            currentDeliveryFee = Number(deliveryContext.fee || 0);

            totalAmount = totalItemsWithTaxes + currentDeliveryFee + tax;
            totalSinIGTF = totalItemsWithTaxes + currentDeliveryFee;
            console.log("Total sin IGTF:", totalSinIGTF);
            cartTotal.textContent = totalAmount.toFixed(2); 
            cartSubTotal.textContent = subTotalAmount.toFixed(2);
            cartTotalBs.textContent = (totalAmount * baseRateToBs ).toFixed(2); 
            cartSubTotalBs.textContent = (subTotalAmount * baseRateToBs ).toFixed(2);
            cartTotalIGTF.textContent = totalSinIGTF.toFixed(2);
            if (cartDeliveryFee) {
                cartDeliveryFee.textContent = currentDeliveryFee.toFixed(2);
            }
            if (cartDeliveryMode) {
                cartDeliveryMode.textContent = deliveryContext.label || 'Retiro en tienda';
            }
            totalAmountValue.textContent = totalAmount.toFixed(2); // Asegúrate de mostrar un número válido
            totalAmountBsValue.textContent = (totalAmount * baseRateToBs ).toFixed(2); // Asegúrate de mostrar un número válido
            toStep2Btn.disabled = selectedItems.length === 0;
        }

        function removeFromCart(id) {
            const item = selectedItems.find(item => item.id === id);
            if (item) {
                totalAmount -= item.price * item.quantity;
                selectedItems = selectedItems.filter(i => i.id !== id);
            }

            const checkbox = document.getElementById(`variant_${id}`);
            if (checkbox) checkbox.checked = false;
            
            recalcSubtotals();
            renderCart();
        }
        function recalcSubtotals() {
            subTotalAmount = selectedItems.reduce((acc, item) => {
                return acc + (item.price * item.quantity);
            }, 0);

            totalAmount = selectedItems.reduce((acc, item) => {
                return acc + (item.totalPrice * item.quantity);
            }, 0);
        }

        let activeCategory = 'all';

        function filterProductsByCategory(categoryId) {
            activeCategory = String(categoryId || 'all').trim();
            const productItems = document.querySelectorAll('.product-item');
            const packagesSection = document.getElementById('materialPackagesSection');
            const categoryButtons = document.querySelectorAll('.category-filter.sale-category-pill');

            categoryButtons.forEach(button => button.classList.remove('is-active'));
            const activeCategoryItem = document.querySelector(`.category-item[data-category="${activeCategory}"] .category-filter.sale-category-pill`);
            if (activeCategoryItem) {
                activeCategoryItem.classList.add('is-active');
            }

            const isAll = activeCategory === 'all';
            const isPackages = activeCategory === 'packages';

            productItems.forEach(item => {
                const itemCategory = String(item.getAttribute('data-category') || '').trim();
                const shouldShow = isAll || (!isPackages && itemCategory === activeCategory);
                item.classList.toggle('d-none', !shouldShow);
            });

            if (packagesSection) {
                const showPackages = isAll || isPackages;
                packagesSection.classList.toggle('d-none', !showPackages);
            }

            // Limpiar el campo de búsqueda de productos al cambiar de categoría
            const searchInput = document.getElementById('searchInput');
            if (searchInput) {
                searchInput.value = '';
            }
        }

        function toggleProductVariantPanel(productId) {
            const card = document.getElementById(`productCard_${productId}`);
            if (!card) {
                return;
            }

            card.classList.toggle('is-expanded');
        }

        function addVariantFromProductCard(variantId, qtyInputId) {
            const checkbox = document.getElementById(`variant_${variantId}`);
            const qtyInput = document.getElementById(qtyInputId);
            const qty = Math.max(1, parseInt(qtyInput?.value || '1', 10) || 1);

            if (!checkbox) {
                return;
            }

            if (checkbox.checked) {
                const existing = selectedItems.find(item => String(item.id) === String(variantId));
                if (existing) {
                    existing.quantity = Number(existing.quantity || 0) + qty;
                    recalcSubtotals();
                    renderCart();
                }
                return;
            }

            checkbox.dataset.selectedQty = String(qty);
            checkbox.checked = true;
            checkbox.dispatchEvent(new Event('change'));
        }
        
        function filterProducts() {
            const searchInput = document.getElementById('searchInput');
            const filter = String(searchInput?.value || '').toLowerCase().trim();
            const productItems = document.querySelectorAll('.product-item');
            const packageItems = document.querySelectorAll('.package-item');
            const packagesSection = document.getElementById('materialPackagesSection');

            const isAll = activeCategory === 'all';
            const isPackages = activeCategory === 'packages';

            productItems.forEach(item => {
                const name = String(item.getAttribute('data-name') || '');
                const itemCategory = String(item.getAttribute('data-category') || '').trim();
                const matchCategory = isAll || (!isPackages && itemCategory === activeCategory);
                const shouldShow = matchCategory && name.includes(filter);

                item.classList.toggle('d-none', !shouldShow);
            });

            if (packagesSection) {
                let hasVisiblePackage = false;

                packageItems.forEach(item => {
                    const name = item.getAttribute('data-name') || '';
                    const shouldShow = (isAll || isPackages) && name.includes(filter);
                    item.classList.toggle('d-none', !shouldShow);
                    if (shouldShow) {
                        hasVisiblePackage = true;
                    }
                });

                packagesSection.classList.toggle('d-none', !hasVisiblePackage);
            }
        }

        function showProductDetails(name, description, imageUrl, price, stock, size) {
            // Rellenar los datos del modal
            document.getElementById('modalProductName').textContent = name;
            document.getElementById('modalProductDescription').textContent = description;
            document.getElementById('modalProductPrice').textContent = price;
            document.getElementById('modalProductStock').textContent = stock;
            document.getElementById('modalProductSize').textContent = size;

            const productImage = document.getElementById('modalProductImage');
            if (imageUrl) {
                productImage.src = imageUrl;
                productImage.style.display = 'block';
            } else {
                productImage.style.display = 'none';
            }

            // Mostrar el modal
            const modal = new bootstrap.Modal(document.getElementById('productDetailModal'));
            modal.show();
        }
        function showQrModal(imageUrl) {
            const qrModalImage = document.getElementById('qrModalImage');
            qrModalImage.src = imageUrl; // Establecer la imagen en el modal
            const qrModal = new bootstrap.Modal(document.getElementById('qrModal'));
            qrModal.show(); // Mostrar el modal
        }

        async function stopQrScanner() {
            if (!qrScannerInstance || !qrScannerRunning) {
                return;
            }

            try {
                await qrScannerInstance.stop();
            } catch (error) {
            }

            qrScannerRunning = false;
        }

        function getPreferredRearCameraId(cameras) {
            if (!Array.isArray(cameras) || cameras.length === 0) {
                return null;
            }

            const rearCamera = cameras.find(camera => {
                const label = String(camera.label || '').toLowerCase();
                return label.includes('back') || label.includes('rear') || label.includes('trasera') || label.includes('posterior');
            });

            return rearCamera?.id || cameras[cameras.length - 1]?.id || null;
        }

        async function ensureCameraPermission() {
            if (qrScannerPermissionGranted) {
                return true;
            }

            if (!navigator.mediaDevices || typeof navigator.mediaDevices.getUserMedia !== 'function') {
                alert('Tu navegador no soporta acceso a cámara en este contexto.');
                return false;
            }

            try {
                const stream = await navigator.mediaDevices.getUserMedia({ video: true, audio: false });
                stream.getTracks().forEach(track => track.stop());
                qrScannerPermissionGranted = true;
                return true;
            } catch (error) {
                alert('No se pudo acceder a la cámara. Revisa permisos del navegador y vuelve a intentar.');
                return false;
            }
        }

        async function resolveQrCameraId() {
            if (qrScannerCameraId) {
                return qrScannerCameraId;
            }

            try {
                const cameras = await Html5Qrcode.getCameras();
                qrScannerCameraId = getPreferredRearCameraId(cameras);
            } catch (error) {
                qrScannerCameraId = null;
            }

            return qrScannerCameraId;
        }

        function cleanupModalVisualState() {
            const hasVisibleModal = document.querySelectorAll('.modal.show').length > 0;
            if (hasVisibleModal) {
                return;
            }

            document.body.classList.remove('modal-open');
            document.body.style.removeProperty('overflow');
            document.body.style.removeProperty('padding-right');
            document.querySelectorAll('.modal-backdrop').forEach(el => el.remove());
        }

        async function startQrScanner() {
            if (qrScannerStartInFlight) {
                return;
            }

            const readerElement = document.getElementById('qrScannerReader');
            if (!readerElement) {
                return;
            }

            if (typeof Html5Qrcode === 'undefined') {
                alert('No se pudo cargar el escáner QR. Intenta recargar la página.');
                return;
            }

            if (!qrScannerInstance) {
                qrScannerInstance = new Html5Qrcode('qrScannerReader');
            }

            if (qrScannerRunning) {
                return;
            }

            qrScannerStartInFlight = true;
            qrScannerLock = false;

            try {
                const hasPermission = await ensureCameraPermission();
                if (!hasPermission) {
                    return;
                }

                const cameraId = await resolveQrCameraId();
                const cameraConfig = cameraId || { facingMode: { ideal: 'environment' } };

                await qrScannerInstance.start(
                    cameraConfig,
                    { fps: 10, qrbox: { width: 240, height: 240 } },
                    async (decodedText) => {
                        if (qrScannerLock) {
                            return;
                        }

                        qrScannerLock = true;
                        const input = document.getElementById('scanCodeInput');
                        if (input) {
                            input.value = String(decodedText || '').trim();
                        }

                        await addByScanCode();
                        const modalEl = document.getElementById('scanQrModal');
                        const modalInstance = bootstrap.Modal.getInstance(modalEl);
                        modalInstance?.hide();
                    },
                    () => {
                    }
                );

                qrScannerRunning = true;
            } catch (error) {
                alert('No se pudo iniciar la cámara para escanear. Verifica permisos del navegador.');
            } finally {
                qrScannerStartInFlight = false;
            }
        }

        async function addByScanCode() {
            if (scanCodeRequestInFlight) {
                return;
            }

            const input = document.getElementById('scanCodeInput');
            const code = String(input?.value || '').trim();
            if (!code) {
                return;
            }

            scanCodeRequestInFlight = true;

            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            try {
                const response = await fetch('/sales/scan-code', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({ code }),
                });

                const payload = await response.json();
                if (!response.ok || !payload.success) {
                    alert(payload.message || 'Código no encontrado.');
                    return;
                }

                if (payload.type === 'package') {
                    addMaterialPackageToSale(payload.package_id);
                    input.value = '';
                    return;
                }

                if (payload.type === 'variant' && payload.variant) {
                    const variant = payload.variant;
                    const checkbox = document.getElementById(`variant_${variant.id}`);
                    if (checkbox) {
                        if (!checkbox.checked) {
                            checkbox.checked = true;
                            checkbox.dispatchEvent(new Event('change'));
                        } else {
                            const existing = selectedItems.find(item => String(item.id) === String(variant.id));
                            if (existing) {
                                existing.quantity = Number(existing.quantity || 0) + 1;
                                recalcSubtotals();
                                renderCart();
                            }
                        }
                        input.value = '';
                        return;
                    }

                    const taxes = variant.taxes || [];
                    const totalTaxRate = taxes.reduce((sum, tax) => sum + parseFloat(tax.rate || 0), 0);
                    const price = parseFloat(variant.price || 0);
                    const taxAmount = price * (totalTaxRate / 100);
                    const totalPrice = price + taxAmount;

                    const existing = selectedItems.find(item => String(item.id) === String(variant.id));
                    if (existing) {
                        existing.quantity = Number(existing.quantity || 0) + 1;
                    } else {
                        selectedItems.push({
                            id: String(variant.id),
                            productName: variant.product_name,
                            productSize: variant.size,
                            price,
                            stock: parseFloat(variant.stock || 0),
                            quantity: 1,
                            line_discount_percentage: 0,
                            taxes,
                            taxRate: totalTaxRate,
                            taxAmount,
                            totalPrice,
                        });
                    }

                    recalcSubtotals();
                    renderCart();
                    input.value = '';
                }
            } finally {
                scanCodeRequestInFlight = false;
            }
        }
        function filterCategories() {
            const searchValue = document.getElementById('searchCategory').value.toLowerCase();
            const categories = document.querySelectorAll('.category-item');

            categories.forEach(category => {
                const name = category.getAttribute('data-category-name')?.toLowerCase() || '';
                if (name.includes(searchValue) || category.getAttribute('data-category') === 'all') {
                    category.style.display = 'block';
                } else {
                    category.style.display = 'none';
                }
            });
        }

        document.getElementById('scanCodeBtn')?.addEventListener('click', addByScanCode);
        document.getElementById('confirmPackageFlavorBtn')?.addEventListener('click', confirmPackageFlavorSelection);
        document.getElementById('packageFlavorModal')?.addEventListener('hidden.bs.modal', () => {
            pendingPackageSelection = null;
            const rows = document.getElementById('packageFlavorRows');
            const summary = document.getElementById('packageFlavorSummary');
            if (rows) rows.innerHTML = '';
            if (summary) summary.innerHTML = '';
        });
        document.getElementById('openQrScannerBtn')?.addEventListener('click', () => {
            // Warm up permission on a direct user gesture to avoid flaky prompts on mobile.
            ensureCameraPermission();
        });
        document.getElementById('scanQrModal')?.addEventListener('shown.bs.modal', startQrScanner);
        document.getElementById('scanQrModal')?.addEventListener('hidden.bs.modal', async () => {
            await stopQrScanner();

            // On some mobile browsers the backdrop can remain mounted after quick close.
            setTimeout(() => {
                cleanupModalVisualState();
            }, 60);
        });
        document.getElementById('scanCodeInput')?.addEventListener('keydown', function (event) {
            if (event.key === 'Enter') {
                event.preventDefault();
                if (scanCodeDebounceTimer) {
                    clearTimeout(scanCodeDebounceTimer);
                    scanCodeDebounceTimer = null;
                }
                addByScanCode();
            }
        });
        document.getElementById('scanCodeInput')?.addEventListener('input', function () {
            if (scanCodeDebounceTimer) {
                clearTimeout(scanCodeDebounceTimer);
            }

            const currentValue = String(this.value || '').trim();
            if (currentValue.length < 3) {
                return;
            }

            scanCodeDebounceTimer = setTimeout(() => {
                addByScanCode();
            }, 160);
        });
        document.getElementById('scanCodeInput')?.addEventListener('paste', function () {
            if (scanCodeDebounceTimer) {
                clearTimeout(scanCodeDebounceTimer);
            }

            scanCodeDebounceTimer = setTimeout(() => {
                addByScanCode();
            }, 30);
        });

        function syncSaleStatusSelectAll() {
            const checks = Array.from(document.querySelectorAll('.sale-status-check'));
            const selectAll = document.getElementById('saleStatusSelectAll');
            if (!selectAll || checks.length === 0) {
                return;
            }

            selectAll.checked = checks.every(check => check.checked);
        }

        document.getElementById('saleStatusSelectAll')?.addEventListener('change', function () {
            document.querySelectorAll('.sale-status-check').forEach(check => {
                check.checked = this.checked;
            });
            renderSummary();
        });

        document.querySelectorAll('.sale-status-check').forEach(check => {
            check.addEventListener('change', function () {
                syncSaleStatusSelectAll();
                renderSummary();
            });
        });

        syncSaleStatusSelectAll();
        setSaleFlowStep(1);
        document.getElementById('toStep2').addEventListener('click', function() {
            document.getElementById('step1').classList.add('d-none');
            document.getElementById('step2').classList.remove('d-none');
            setSaleFlowStep(2);

            // Deshabilitar inputs y ocultar botones de eliminar
            document.querySelectorAll('.qty-edit').forEach(input => {
                input.disabled = true;
            });

            document.getElementById('toStep2').classList.add('d-none');

            document.querySelectorAll('.delete-button').forEach(btn => {
                btn.classList.add('d-none');
            });

            closeCartOffcanvas(true);
        });

        document.getElementById('backToStep1').addEventListener('click', function() {
            document.getElementById('step2').classList.add('d-none');
            document.getElementById('step1').classList.remove('d-none');
            setSaleFlowStep(1);
            document.getElementById('toStep2').classList.remove('d-none');

            // Habilitar inputs y mostrar botones de eliminar
            document.querySelectorAll('.qty-edit').forEach(input => {
                input.disabled = false;
            });

            document.querySelectorAll('.delete-button').forEach(btn => {
                btn.classList.remove('d-none');
            });
        });


        
        document.addEventListener('DOMContentLoaded', function () {
            const currencyButtons = document.querySelectorAll('.currency-tab');
            const sections = document.querySelectorAll('.currency-section');

            currencyButtons.forEach(btn => {
                btn.addEventListener('click', () => {
                    const selectedCurrency = btn.dataset.currency;
                    console.log("Moneda seleccionada:", selectedCurrency);
                    // Ocultar todas las secciones
                    sections.forEach(section => {
                        section.classList.add('d-none');
                    });

                    // Mostrar solo la sección de la moneda seleccionada
                    document.querySelector(`.currency-section[data-currency="${selectedCurrency}"]`)?.classList.remove('d-none');

                    // Resaltar botón activo
                    currencyButtons.forEach(b => b.classList.remove('btn-dark'));
                    currencyButtons.forEach(b => b.classList.add('btn-outline-dark'));
                    btn.classList.remove('btn-outline-dark');
                    btn.classList.add('btn-dark');
                });
            });
            
        });
        
        function generatePaymentEntryId() {
            return `${Date.now()}_${Math.floor(Math.random() * 1000000)}`;
        }

        function createPaymentRowHtml(methodId, currency, noReference, entryId) {
            const referenceInput = noReference
                ? `<input type="hidden" class="payment-reference-input" data-method-id="${methodId}" data-entry-id="${entryId}" data-requires-reference="0" value="">`
                : `<input type="text" class="form-control payment-reference-input border border-1 p-2" data-method-id="${methodId}" data-entry-id="${entryId}" data-requires-reference="1" placeholder="Referencia" oninput="updatePayment(this)">`;

            const proofInput = noReference
                ? `<div class="small text-muted py-2">No requiere comprobante para este método.</div>`
                : `<input type="file" class="form-control border border-1 p-2 payment-proof-input" accept="image/*" data-method-id="${methodId}" data-entry-id="${entryId}" onchange="updatePaymentProof(this)">`;

            return `
                <div class="border rounded-3 p-2 bg-white" data-payment-entry-row="${entryId}">
                    <div class="d-flex flex-column flex-lg-row gap-2 align-items-lg-center">
                        <div class="flex-grow-1">
                            <label class="form-label mb-1">Monto</label>
                            <input type="text" inputmode="decimal" autocomplete="off" class="form-control payment-input border border-1 p-2"
                                data-method-id="${methodId}"
                                data-entry-id="${entryId}"
                                data-currency="${currency}"
                                oninput="updatePayment(this)">
                        </div>
                        <div class="flex-grow-1">
                            <label class="form-label mb-1">Referencia</label>
                            ${referenceInput}
                        </div>
                        <div class="flex-grow-1">
                            <label class="form-label mb-1">Comprobante</label>
                            ${proofInput}
                        </div>
                        <div class="align-self-end">
                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="removePaymentEntry('${methodId}', '${entryId}')">X</button>
                        </div>
                    </div>
                </div>
            `;
        }

        function addPaymentEntry(methodId, currency, noReference = false, methodName = '') {
            const rowsContainer = document.getElementById(`paymentRows_${methodId}`);
            if (!rowsContainer) return;

            const entryId = generatePaymentEntryId();
            rowsContainer.insertAdjacentHTML('beforeend', createPaymentRowHtml(methodId, currency, noReference, entryId));

            payments.push({
                entryId,
                methodId: String(methodId),
                methodName: methodName || `Método ${methodId}`,
                currency,
                amount: 0,
                reference: '',
                hasProofImage: false,
                requiresReference: !noReference,
            });

            validatePaymentDetails();
        }

        function parseAmountInputValue(value) {
            const normalized = normalizeEditableAmountValue(value).numeric;
            const parsed = Number.parseFloat(normalized);
            return Number.isFinite(parsed) ? parsed : 0;
        }

        function normalizeEditableAmountValue(value) {
            const source = String(value || '')
                .replace(/\s+/g, '')
                .replace(/[^\d.,]/g, '');

            if (!source) {
                return { text: '', numeric: '' };
            }

            const lastDot = source.lastIndexOf('.');
            const lastComma = source.lastIndexOf(',');
            let decimalIndex = -1;
            let decimalSeparator = '';

            if (lastDot !== -1 && lastComma !== -1) {
                decimalIndex = Math.max(lastDot, lastComma);
                decimalSeparator = source[decimalIndex];
            } else if (lastComma !== -1) {
                const fraction = source.slice(lastComma + 1).replace(/[^\d]/g, '');
                if (fraction.length <= 2) {
                    decimalIndex = lastComma;
                    decimalSeparator = ',';
                }
            } else if (lastDot !== -1) {
                const fraction = source.slice(lastDot + 1).replace(/[^\d]/g, '');
                if (fraction.length <= 2 || source.endsWith('.')) {
                    decimalIndex = lastDot;
                    decimalSeparator = '.';
                }
            }

            let integerPart = '';
            let decimalPart = '';
            let hasTrailingDecimal = false;

            if (decimalIndex !== -1) {
                integerPart = source.slice(0, decimalIndex).replace(/[^\d]/g, '');
                decimalPart = source.slice(decimalIndex + 1).replace(/[^\d]/g, '').slice(0, 2);
                hasTrailingDecimal = source.endsWith(decimalSeparator) && decimalPart.length === 0;
            } else {
                integerPart = source.replace(/[^\d]/g, '');
            }

            integerPart = integerPart.replace(/^0+(?=\d)/, '');

            if (!integerPart && (decimalPart || hasTrailingDecimal)) {
                integerPart = '0';
            }

            const text = decimalIndex !== -1
                ? `${integerPart || '0'}${(decimalPart || hasTrailingDecimal) ? '.' : ''}${decimalPart}`
                : integerPart;

            const numeric = decimalIndex !== -1
                ? `${integerPart || '0'}${decimalPart ? `.${decimalPart}` : ''}`
                : integerPart;

            return { text, numeric };
        }

        function formatAmountDisplay(value) {
            const numeric = Number(value || 0);
            return new Intl.NumberFormat('en-US', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2,
            }).format(Number.isFinite(numeric) ? numeric : 0);
        }

        function syncFormattedPaymentInput(input, applyFormatting = false) {
            if (!input || !input.classList.contains('payment-input')) {
                return 0;
            }

            const numericValue = parseAmountInputValue(input.value);
            input.dataset.rawValue = String(numericValue);

            if (applyFormatting) {
                input.value = numericValue > 0 ? formatAmountDisplay(numericValue) : '';
            }

            return numericValue;
        }

        function sanitizeLiveAdminMoneyInput(input) {
            if (!input || !input.classList.contains('payment-input')) {
                return 0;
            }

            const selectionStart = input.selectionStart ?? String(input.value || '').length;
            const beforeCursor = String(input.value || '').slice(0, selectionStart);
            const normalizedValue = normalizeEditableAmountValue(input.value);
            const normalizedBeforeCursor = normalizeEditableAmountValue(beforeCursor);

            if (!normalizedValue.text) {
                input.dataset.rawValue = '0';
                input.value = '';
                return 0;
            }

            const numericValue = parseAmountInputValue(normalizedValue.text);
            input.dataset.rawValue = String(numericValue);

            if (input.value !== normalizedValue.text) {
                input.value = normalizedValue.text;
                const nextCaret = normalizedBeforeCursor.text.length;
                requestAnimationFrame(() => {
                    try {
                        input.setSelectionRange(nextCaret, nextCaret);
                    } catch (error) {
                    }
                });
            }

            return numericValue;
        }

        function removePaymentEntry(methodId, entryId) {
            const row = document.querySelector(`[data-payment-entry-row="${entryId}"]`);
            if (row) row.remove();

            payments = payments.filter(payment => payment.entryId !== entryId);

            const hasEntriesForMethod = payments.some(payment => payment.methodId === String(methodId));
            if (!hasEntriesForMethod) {
                const checkbox = document.getElementById(`method_${methodId}`);
                const paymentFields = document.getElementById(`paymentFields_${methodId}`);
                const rowsContainer = document.getElementById(`paymentRows_${methodId}`);
                if (rowsContainer) rowsContainer.innerHTML = '';
                if (checkbox) checkbox.checked = false;
                if (paymentFields) paymentFields.classList.add('d-none');
            }

            renderCart();
            validatePaymentDetails();
        }

        function togglePaymentFields(checkbox) {
            const methodId = checkbox.dataset.methodId;
            const methodName = checkbox.dataset.methodName || `Método ${methodId}`;
            const paymentFields = document.getElementById(`paymentFields_${methodId}`);
            const currency = paymentFields?.dataset.currency || checkbox.dataset.currency;
            const noReference = paymentFields?.dataset.noReference === '1';

            if (checkbox.checked) {
                paymentFields.classList.remove('d-none');
                const hasEntriesForMethod = payments.some(payment => payment.methodId === String(methodId));
                if (!hasEntriesForMethod) {
                    addPaymentEntry(methodId, currency, noReference, methodName);
                }
            } else {
                const rowsContainer = document.getElementById(`paymentRows_${methodId}`);
                if (rowsContainer) rowsContainer.innerHTML = '';
                paymentFields.classList.add('d-none');
                payments = payments.filter(payment => payment.methodId !== String(methodId));
            }

            console.log("payments", payments);
            validatePaymentDetails();
        }

        function updatePayment(input) {
            const methodId = input.dataset.methodId;
            const entryId = input.dataset.entryId;
            const currency = input.dataset.currency;
            const payment = payments.find(payment => payment.methodId === String(methodId) && payment.entryId === entryId);

            if (payment) {
                if (input.classList.contains('payment-input')) {
                    const enteredAmount = sanitizeLiveAdminMoneyInput(input);
                    payment.amount = convertAmountToBaseCurrency(enteredAmount, currency);
                } else if (input.classList.contains('payment-reference-input')) {
                    payment.reference = input.value;
                }
            }
            console.log("payments 2", payments);
            renderCart();
            validatePaymentDetails();
        }

        function updatePaymentProof(input) {
            const methodId = input.dataset.methodId;
            const entryId = input.dataset.entryId;
            const payment = payments.find(payment => payment.methodId === String(methodId) && payment.entryId === entryId);

            if (payment) {
                payment.hasProofImage = Boolean(input.files?.[0]);
            }
        }

        function getBaseCurrencyPaymentsTotal() {
            let totalBaseCurrency = 0;

            payments.forEach(p => {
                if (String(p.currency || '').toUpperCase().trim() === normalizedBaseCurrencyCode) {
                    totalBaseCurrency += Number(p.amount) || 0;
                }
            });

            return roundMoney(totalBaseCurrency);
        }

        function isIgtfEnabledForSale() {
            return tenantElectronicInvoicingEnabled && !tenantSpecialTaxpayer && Number(igtfTax?.rate || 0) > 0;
        }

        function calculateIgtfTax() {
            const totalBaseCurrency = getBaseCurrencyPaymentsTotal();
            if (!isIgtfEnabledForSale()) {
                return {
                    totalBaseCurrency,
                    tax: 0,
                };
            }
            const igtfRate = Number(igtfTax?.rate || 0) / 100;
            const tax = roundMoney(totalBaseCurrency * igtfRate);
            return {
                totalBaseCurrency,
                tax
            };
        }

        let deliveryCountriesLoaded = false;

        function getSelectedOptionText(selectElement) {
            const option = selectElement?.options?.[selectElement.selectedIndex];
            return option ? String(option.text || '').trim() : '';
        }

        function resetLocationSelect(selectElement, placeholder, disabled = true) {
            if (!selectElement) return;
            selectElement.innerHTML = `<option value="">${placeholder}</option>`;
            selectElement.disabled = disabled;
        }

        function fillLocationSelect(selectElement, items, placeholder) {
            if (!selectElement) return;
            selectElement.innerHTML = `<option value="">${placeholder}</option>`;
            (Array.isArray(items) ? items : []).forEach(item => {
                const option = document.createElement('option');
                option.value = String(item.id);
                option.textContent = item.name;
                selectElement.appendChild(option);
            });
            selectElement.disabled = !items || items.length === 0;
        }

        async function fetchLocationJson(url) {
            const response = await fetch(url, {
                headers: { Accept: 'application/json' },
            });

            if (!response.ok) {
                throw new Error('No se pudo cargar información de ubicación.');
            }

            return response.json();
        }

        async function ensureDeliveryCountriesLoaded() {
            const countrySelect = document.getElementById('deliveryCountry');
            if (!countrySelect || deliveryCountriesLoaded) {
                return;
            }

            const countries = await fetchLocationJson('/get-countries');
            fillLocationSelect(countrySelect, countries, 'País');
            deliveryCountriesLoaded = true;
        }

        function setSaleFlowStep(step) {
            document.querySelectorAll('[data-sale-step]').forEach((item) => {
                const itemStep = Number(item.dataset.saleStep || 0);
                item.classList.toggle('is-active', itemStep === step);
                item.classList.toggle('is-complete', itemStep < step);
            });
        }

        function buildDeliveryAddress() {
            const countrySelect = document.getElementById('deliveryCountry');
            const stateSelect = document.getElementById('deliveryState');
            const citySelect = document.getElementById('deliveryCity');
            const addressDetailInput = document.getElementById('deliveryAddressDetail');

            const countryId = countrySelect?.value || '';
            const stateId = stateSelect?.value || '';
            const cityId = citySelect?.value || '';

            if (!countryId || !stateId || !cityId) {
                return {
                    valid: false,
                    message: 'Debes seleccionar país, estado y ciudad para el envío.',
                    address: '',
                };
            }

            const parts = [
                getSelectedOptionText(countrySelect),
                getSelectedOptionText(stateSelect),
                getSelectedOptionText(citySelect),
                (addressDetailInput?.value || '').trim(),
            ].filter(Boolean);

            return {
                valid: true,
                message: '',
                address: parts.join(', '),
                cityId: Number(cityId),
            };
        }

        function renderSummary() {
            const container = document.getElementById('summaryContainer');
            const deliveryType = document.querySelector('input[name="delivery_type"]:checked')?.value || 'pickup';
            const deliveryAddressData = buildDeliveryAddress();
            const deliveryContext = getAdminDeliveryChargeContext(false);
            const deliveryPreferenceLabel = deliveryType === 'shipping' ? 'Envío' : 'Retiro en tienda';
            const saleDocumentMode = document.querySelector('input[name="sale_document_mode"]:checked')?.value || 'delivery_note';
            const saleDocumentModeLabel = saleDocumentMode === 'electronic_invoice' ? 'Facturación digital' : 'Orden de entrega';
            const shouldCreateNewCustomer = document.querySelector('input[name="create_new_customer"]:checked')?.value === 'yes';
            const existingCustomerSelect = document.getElementById('existingCustomerSelect');
            const selectedCustomerOption = existingCustomerSelect?.options?.[existingCustomerSelect.selectedIndex] || null;
            const selectedCustomerLabel = (selectedCustomerOption?.textContent || '').trim();
            const newCustomerName = (document.getElementById('newCustomerName')?.value || '').trim();
            const newCustomerEmail = (document.getElementById('newCustomerEmail')?.value || '').trim();
            const newCustomerPhone = (document.getElementById('newCustomerPhone')?.value || '').trim();
            const newCustomerDni = (document.getElementById('newCustomerDni')?.value || '').trim();
            
            container.innerHTML = ''; // Limpiar resumen anterior

            // Resumen de items
            const itemsTitle = document.createElement('h5');
            itemsTitle.innerText = 'Productos seleccionados';
            container.appendChild(itemsTitle);

            const itemList = document.createElement('ul');
            selectedItems.forEach(item => {
                const li = document.createElement('li');
                li.innerText = `${item.productName} - Variante: ${item.productSize} - Cantidad: ${item.quantity} - Subtotal: ${baseCurrencySymbol}${(item.price * item.quantity).toFixed(2)}`;
                itemList.appendChild(li);
            });
            container.appendChild(itemList);
            itemList.className = 'card p-4 gap-2';

            // Total
            const totalDiv = document.createElement('p');
            totalDiv.innerHTML = `<strong>Total a pagar:</strong> ${baseCurrencySymbol}${totalAmount.toFixed(2)}`;
            container.appendChild(totalDiv);

            // Total BS
            console.log("baseRateToBs", baseRateToBs)
            const totalDivBs = document.createElement('p');
            totalDivBs.innerHTML = `<strong>Total a pagar Bs:</strong> Bs${(totalAmount * baseRateToBs).toFixed(2)}`;
            container.appendChild(totalDivBs);

            const deliveryFeeDiv = document.createElement('p');
            deliveryFeeDiv.innerHTML = `<strong>Costo delivery:</strong> ${baseCurrencySymbol}${Number(deliveryContext.fee || 0).toFixed(2)} <span class="text-muted">(${deliveryContext.label || 'Retiro en tienda'})</span>`;
            container.appendChild(deliveryFeeDiv);

            const deliveryDiv = document.createElement('p');
            deliveryDiv.innerHTML = `<strong>Entrega:</strong> ${deliveryPreferenceLabel}`;
            container.appendChild(deliveryDiv);

            const addressDiv = document.createElement('p');
            addressDiv.innerHTML = `<strong>Dirección:</strong> ${deliveryType === 'shipping' ? (deliveryAddressData.address || 'No indicada') : 'Tienda'}`;
            container.appendChild(addressDiv);

            const customerDiv = document.createElement('p');
            customerDiv.innerHTML = `<strong>Cliente:</strong> ${shouldCreateNewCustomer ? 'Nuevo cliente' : (selectedCustomerLabel || 'Cliente existente no seleccionado')}`;
            container.appendChild(customerDiv);

            const documentModeDiv = document.createElement('p');
            documentModeDiv.innerHTML = `<strong>Documento:</strong> ${saleDocumentModeLabel}`;
            container.appendChild(documentModeDiv);

            if (shouldCreateNewCustomer) {
                const customerDataDiv = document.createElement('p');
                customerDataDiv.innerHTML = `
                    <strong>Datos cliente nuevo:</strong><br>
                    Nombre: ${newCustomerName || 'No indicado'}<br>
                    Correo: ${newCustomerEmail || 'No indicado'}<br>
                    Teléfono: ${newCustomerPhone || 'No indicado'}<br>
                    DNI: ${newCustomerDni || 'No indicado'}
                `;
                container.appendChild(customerDataDiv);
            }

            const statusDiv = document.createElement('div');
            const markSaleCompleted = document.getElementById('markSaleCompleted')?.checked;
            const markPaymentsPaid = document.getElementById('markPaymentsPaid')?.checked;
            const markDelivered = document.getElementById('markDelivered')?.checked;
            statusDiv.innerHTML = `
                <strong>Estado inicial:</strong>
                <ul class="mb-3 mt-2">
                    <li>Venta completa: ${markSaleCompleted ? 'Sí' : 'No'}</li>
                    <li>Pagos pagados: ${markPaymentsPaid ? 'Sí' : 'No'}</li>
                    <li>Entregada: ${markDelivered ? 'Sí' : 'No'}</li>
                </ul>
            `;
            container.appendChild(statusDiv);

            // Resumen de métodos de pago
            const paymentsTitle = document.createElement('h5');
            paymentsTitle.innerText = 'Métodos de pago';
            container.appendChild(paymentsTitle);

            if (payments.length === 0) {
                const noPayment = document.createElement('p');
                noPayment.innerText = 'No se ha seleccionado ningún método de pago.';
                container.appendChild(noPayment);
            } else {
                const paymentList = document.createElement('ul');
                payments.forEach(payment => {
                    const amount = Number(payment.amount || 0);
                    const reference = payment.reference || 'N/A';

                    if (amount <= 0) {
                        return;
                    }

                    const li = document.createElement('li');
                    li.innerText = `${payment.methodName} (${payment.currency}) - Monto: ${baseCurrencySymbol}${amount.toFixed(2)} - Referencia: ${reference}`;
                    paymentList.appendChild(li);
                });
                if (paymentList.children.length > 0) {
                    container.appendChild(paymentList);
                    paymentList.className = 'card p-4 gap-2';
                } else {
                    const noPaymentAmount = document.createElement('p');
                    noPaymentAmount.innerText = 'No hay montos cargados en los métodos seleccionados.';
                    container.appendChild(noPaymentAmount);
                }
            }
        }

        function addPayments() {
            const paymentsContainer = document.getElementById('paymentsContainer');
            paymentsContainer.innerHTML = ''; // Limpiar contenido previo

            payments.forEach(payment => {
                const amount = Number(payment.amount || 0);
                if (amount <= 0) {
                    return;
                }

                const paymentDiv = document.createElement('div');
                paymentDiv.className = 'payment-method-summary';
                const reference = payment.reference || 'N/A';

                paymentDiv.innerHTML = `
                    <strong>Método:</strong> ${payment.methodName} (${payment.currency})<br>
                    <strong>Monto:</strong> ${baseCurrencySymbol}${amount.toFixed(2)} <br>
                    <strong>Referencia:</strong> ${reference} <br>
                    <hr>
                `;

                paymentsContainer.appendChild(paymentDiv);
            });
        }

        function validatePaymentDetails() {
            totalPaid = roundMoney(payments.reduce((sum, payment) => sum + (Number(payment.amount) || 0), 0));
            console.log("Total pagado:", payments);
            const totalPaidSpan = document.getElementById('totalPaid');
            const paymentMessages = document.querySelectorAll('.paymentMessage');
            const toStep3Button = document.getElementById('toStep3');

            // Mostrar el total ingresado
            totalPaidSpan.textContent = totalPaid.toFixed(2);

            let messageText = '';
            let messageClass = '';
            let disableStep3 = false;

            // Verificar si hay referencias vacías (solo si el método requiere referencia y monto > 0)
            const hasEmptyReference = payments.some(payment => {
                const amount = Number(payment.amount) || 0;
                if (amount <= 0) return false;

                const inputReference = document.querySelector(`.payment-reference-input[data-entry-id="${payment.entryId}"]`);
                const requiresReference = inputReference ? inputReference.dataset.requiresReference === '1' : true;

                if (!requiresReference) return false;
                return !payment.reference || payment.reference.trim() === '';
            });

            if (hasEmptyReference) {
                messageText = `Todos los métodos de pago deben tener una referencia válida.`;
                messageClass = 'text-danger';
                disableStep3 = true;
            } else if (totalPaid + 0.0001 < roundMoney(totalAmount)) {
                const remaining = roundMoney(totalAmount - totalPaid).toFixed(2);
                messageText = `Falta por pagar: ${baseCurrencySymbol}${remaining} / BS${(remaining * baseRateToBs).toFixed(2)}`;
                messageClass = 'text-danger';
                disableStep3 = true;
            } else if (totalPaid - 0.0001 > roundMoney(totalAmount)) {
                const change = roundMoney(totalPaid - totalAmount).toFixed(2);
                messageText = `Debe entregar vuelto: ${baseCurrencySymbol}${change} / BS${(change * baseRateToBs).toFixed(2)}`;
                messageClass = 'text-warning';
                disableStep3 = false;
            } else {
                messageText = `Pago exacto.`;
                messageClass = 'text-success';
                disableStep3 = false;
            }

            // Actualizar todos los mensajes en pantalla
            paymentMessages.forEach(el => {
                el.textContent = messageText;
                el.className = `paymentMessage ${messageClass}`; // Mantener la clase base más el color
            });

            toStep3Button.disabled = disableStep3;
        }

        document.addEventListener('focusin', function (event) {
            const input = event.target.closest('.payment-input');
            if (!input) {
                return;
            }

            const normalizedValue = normalizeEditableAmountValue(input.value).text;
            if (normalizedValue && input.value !== normalizedValue) {
                input.value = normalizedValue;
            }
        });

        document.addEventListener('focusout', function (event) {
            const input = event.target.closest('.payment-input');
            if (!input) {
                return;
            }

            syncFormattedPaymentInput(input, true);
        });


        //Funciones para paso 3
        document.getElementById('toStep3').addEventListener('click', function() {
            closeCartOffcanvas(true);
            document.getElementById('step2').classList.add('d-none');
            renderSummary(); // Mostrar el resumen
            document.getElementById('cart').classList.add('d-none');
            document.getElementById('step3').classList.remove('d-none');
            setSaleFlowStep(3);
            document.getElementById('openAdminCartBtn')?.classList.add('d-none');
            console.log('Resumen:', selectedItems);
            console.log('Pagos:', payments);
        });
        document.getElementById('backToStep2').addEventListener('click', function() {
            document.getElementById('step3').classList.add('d-none');
            document.getElementById('step2').classList.remove('d-none');
            setSaleFlowStep(2);
            document.getElementById('cart').classList.remove('d-none');
            document.getElementById('openAdminCartBtn')?.classList.remove('d-none');

        });

        function updateDeliveryAddressVisibility() {
            const selectedType = document.querySelector('input[name="delivery_type"]:checked')?.value || 'pickup';
            const addressContainer = document.getElementById('deliveryAddressContainer');
            const distanceContainer = document.getElementById('deliveryDistanceContainer');
            const distanceInput = document.getElementById('deliveryDistanceKm');
            const countrySelect = document.getElementById('deliveryCountry');
            const stateSelect = document.getElementById('deliveryState');
            const citySelect = document.getElementById('deliveryCity');
            const addressDetailInput = document.getElementById('deliveryAddressDetail');

            if (selectedType === 'shipping') {
                addressContainer.classList.remove('d-none');
                const shouldShowDistance = tenantDeliveryConfig?.enabled && (tenantDeliveryConfig.mode === 'distance');
                distanceContainer?.classList.toggle('d-none', !shouldShowDistance);
                ensureDeliveryCountriesLoaded().catch(() => {
                    alert('No se pudieron cargar los países para el envío.');
                });
            } else {
                addressContainer.classList.add('d-none');
                distanceContainer?.classList.add('d-none');
                if (countrySelect) countrySelect.value = '';
                if (stateSelect) resetLocationSelect(stateSelect, 'Estado (parte del país)', true);
                if (citySelect) resetLocationSelect(citySelect, 'Ciudad', true);
                if (addressDetailInput) addressDetailInput.value = '';
                if (distanceInput) distanceInput.value = '';
            }

            renderCart();

            if (!document.getElementById('step3').classList.contains('d-none')) {
                renderSummary();
            }
        }

        function updateCreateCustomerVisibility() {
            const shouldCreateNewCustomer = document.querySelector('input[name="create_new_customer"]:checked')?.value === 'yes';
            const newCustomerForm = document.getElementById('newCustomerForm');
            const existingCustomerForm = document.getElementById('existingCustomerForm');
            if (!newCustomerForm || !existingCustomerForm) return;

            newCustomerForm.classList.toggle('d-none', !shouldCreateNewCustomer);
            existingCustomerForm.classList.toggle('d-none', shouldCreateNewCustomer);

            if (!shouldCreateNewCustomer) {
                const existingCustomerSelect = document.getElementById('existingCustomerSelect');
                selectedExistingCustomerId = Number(existingCustomerSelect?.value || 0);
            }

            if (!document.getElementById('step3').classList.contains('d-none')) {
                renderSummary();
            }
        }

        document.querySelectorAll('input[name="delivery_type"]').forEach(input => {
            input.addEventListener('change', updateDeliveryAddressVisibility);
        });

        document.querySelectorAll('input[name="sale_document_mode"]').forEach(input => {
            input.addEventListener('change', function () {
                if (!document.getElementById('step3').classList.contains('d-none')) {
                    renderSummary();
                }
            });
        });

        document.getElementById('deliveryAddressDetail').addEventListener('input', function () {
            if (!document.getElementById('step3').classList.contains('d-none')) {
                renderSummary();
            }
        });

        document.getElementById('deliveryDistanceKm')?.addEventListener('input', function () {
            renderCart();
            if (!document.getElementById('step3').classList.contains('d-none')) {
                renderSummary();
            }
        });

        document.getElementById('deliveryCountry')?.addEventListener('change', async function () {
            const stateSelect = document.getElementById('deliveryState');
            const citySelect = document.getElementById('deliveryCity');
            const countryId = this.value;

            resetLocationSelect(stateSelect, 'Estado (parte del país)', true);
            resetLocationSelect(citySelect, 'Ciudad', true);

            if (!countryId) {
                renderSummary();
                return;
            }

            try {
                const states = await fetchLocationJson(`/get-states/${countryId}`);
                fillLocationSelect(stateSelect, states, 'Estado (parte del país)');
            } catch (error) {
                alert('No se pudieron cargar los estados del país seleccionado.');
            }

            renderSummary();
        });

        document.getElementById('deliveryState')?.addEventListener('change', async function () {
            const citySelect = document.getElementById('deliveryCity');
            const stateId = this.value;

            resetLocationSelect(citySelect, 'Ciudad', true);

            if (!stateId) {
                renderSummary();
                return;
            }

            try {
                const cities = await fetchLocationJson(`/get-cities/${stateId}`);
                fillLocationSelect(citySelect, cities, 'Ciudad');
            } catch (error) {
                alert('No se pudieron cargar las ciudades del estado seleccionado.');
            }

            renderSummary();
        });

        document.getElementById('deliveryCity')?.addEventListener('change', function () {
            renderSummary();
        });

        document.querySelectorAll('input[name="create_new_customer"]').forEach(input => {
            input.addEventListener('change', updateCreateCustomerVisibility);
        });

        ['newCustomerName', 'newCustomerEmail', 'newCustomerPhone', 'newCustomerDni'].forEach(fieldId => {
            document.getElementById(fieldId)?.addEventListener('input', function () {
                if (!document.getElementById('step3').classList.contains('d-none')) {
                    renderSummary();
                }
            });
        });

        if (document.getElementById('existingCustomerSelect') && existingCustomersForSale.length > 0) {
            const existingCustomerSelect = document.getElementById('existingCustomerSelect');
            if (existingCustomerSelect && !existingCustomerSelect.value) {
                existingCustomerSelect.value = String(existingCustomersForSale[0].id);
                selectedExistingCustomerId = Number(existingCustomersForSale[0].id || 0);
            }
        }

        updateCreateCustomerVisibility();
        
        document.getElementById('confirmPurchase').addEventListener('click', function () {
    const button = this;

    // Activar loading
    button.disabled = true;
    const originalText = button.innerHTML;
    button.innerHTML = `
        <span class="spinner-border spinner-border-sm me-2"></span>
        Procesando...
    `;

    const tenantId = Number(authUser.tenant_id);
    const deliveryType = document.querySelector('input[name="delivery_type"]:checked')?.value || 'pickup';
    const saleDocumentMode = document.querySelector('input[name="sale_document_mode"]:checked')?.value || 'delivery_note';
    const deliveryAddressData = buildDeliveryAddress();
    const deliveryContext = getAdminDeliveryChargeContext(true);
    const shouldCreateNewCustomer = document.querySelector('input[name="create_new_customer"]:checked')?.value === 'yes';
    const newCustomerName = (document.getElementById('newCustomerName')?.value || '').trim();
    const newCustomerEmail = (document.getElementById('newCustomerEmail')?.value || '').trim();
    const newCustomerPhone = (document.getElementById('newCustomerPhone')?.value || '').trim();
    const newCustomerDni = (document.getElementById('newCustomerDni')?.value || '').trim();

    if (deliveryType === 'shipping' && !deliveryAddressData.valid) {
        alert(deliveryAddressData.message || 'Debes indicar la dirección cuando la entrega es por envío.');
        button.disabled = false;
        button.innerHTML = originalText;
        return;
    }

    if (!deliveryContext.valid) {
        alert(deliveryContext.message || 'Debes completar la información del delivery.');
        button.disabled = false;
        button.innerHTML = originalText;
        return;
    }

    if (saleDocumentMode === 'electronic_invoice' && !tenantElectronicInvoicingEnabled) {
        alert('La facturación digital está desactivada para esta tienda.');
        button.disabled = false;
        button.innerHTML = originalText;
        return;
    }

    if (shouldCreateNewCustomer) {
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!newCustomerName || !newCustomerPhone || !newCustomerDni) {
            alert('Para crear un cliente nuevo debes completar nombre, teléfono y DNI.');
            button.disabled = false;
            button.innerHTML = originalText;
            return;
        }

        if (newCustomerEmail && !emailPattern.test(newCustomerEmail)) {
            alert('El correo del nuevo cliente no es válido.');
            button.disabled = false;
            button.innerHTML = originalText;
            return;
        }
    } else if (!selectedExistingCustomerId) {
        alert('Debes seleccionar un cliente existente para registrar la venta.');
        button.disabled = false;
        button.innerHTML = originalText;
        return;
    }

    const validPayments = payments.filter(payment => Number(payment.amount || 0) > 0);

    const summary = {
        customerId: shouldCreateNewCustomer ? null : { id: selectedExistingCustomerId },
        customer_existing_id: shouldCreateNewCustomer ? null : selectedExistingCustomerId,
        items: selectedItems,
        tenant_id: tenantId,
        dollarRate: baseRateToBs,
        delivery_type: deliveryType,
        delivery_address: deliveryType === 'shipping' ? deliveryAddressData.address : 'Tienda',
        delivery_city_id: deliveryType === 'shipping' ? Number(deliveryAddressData.cityId || 0) : null,
        delivery_distance_km: deliveryType === 'shipping' ? deliveryContext.distanceKm : null,
        create_new_customer: shouldCreateNewCustomer,
        customer_new: shouldCreateNewCustomer
            ? {
                name: newCustomerName,
                email: newCustomerEmail || null,
                phone_code: document.getElementById('newCustomerPhoneCode')?.value || '+58',
                phone_number: newCustomerPhone,
                dni: newCustomerDni,
            }
            : null,
        mark_delivered: document.getElementById('markDelivered')?.checked || false,
        mark_payments_paid: document.getElementById('markPaymentsPaid')?.checked || false,
        mark_sale_completed: document.getElementById('markSaleCompleted')?.checked || false,
        sale_document_mode: saleDocumentMode,
    };

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const formData = new FormData();
    appendFormDataValue(formData, 'customer_existing_id', summary.customer_existing_id);
    appendFormDataValue(formData, 'items', summary.items);
    appendFormDataValue(formData, 'tenant_id', summary.tenant_id);
    appendFormDataValue(formData, 'dollarRate', summary.dollarRate);
    appendFormDataValue(formData, 'delivery_type', summary.delivery_type);
    appendFormDataValue(formData, 'delivery_address', summary.delivery_address);
    appendFormDataValue(formData, 'delivery_city_id', summary.delivery_city_id);
    appendFormDataValue(formData, 'delivery_distance_km', summary.delivery_distance_km);
    appendFormDataValue(formData, 'create_new_customer', summary.create_new_customer);
    appendFormDataValue(formData, 'customer_new', summary.customer_new);
    appendFormDataValue(formData, 'mark_delivered', summary.mark_delivered);
    appendFormDataValue(formData, 'mark_payments_paid', summary.mark_payments_paid);
    appendFormDataValue(formData, 'mark_sale_completed', summary.mark_sale_completed);
    appendFormDataValue(formData, 'sale_document_mode', summary.sale_document_mode);

    validPayments.forEach((payment, index) => {
        appendFormDataValue(formData, `payments[${index}][methodId]`, payment.methodId);
        appendFormDataValue(formData, `payments[${index}][amount]`, payment.amount);
        appendFormDataValue(formData, `payments[${index}][currency]`, payment.currency);
        appendFormDataValue(formData, `payments[${index}][reference]`, payment.reference);

        const proofInput = document.querySelector(`.payment-proof-input[data-entry-id="${payment.entryId}"]`);
        if (proofInput?.files?.[0]) {
            formData.append(`payments[${index}][proof_image]`, proofInput.files[0]);
        }
    });

    fetch('/create-sale', {
        method: 'POST',
        headers: {
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: formData
    })
        .then(async response => {
            const payload = await response.json().catch(() => ({}));

            if (response.ok) {
                return payload;
            }

            throw new Error(payload.message || payload.error || 'Error al confirmar la compra.');
        })
        .then(data => {
            let successMessage = data.message || 'Compra confirmada con éxito.';
            if (data.created_customer_temporary_password) {
                successMessage += `\n\nCliente creado con contraseña temporal: ${data.created_customer_temporary_password}.`;
                successMessage += '\nDebe iniciar sesión en la landing y cambiarla en Mi perfil.';
            }
            alert(successMessage);

            if (data.pdf_url) {
                const link = document.createElement('a');
                link.href = data.pdf_url;
                link.download = '';
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
            }

            if (data.nota_entrega_pdf_url) {
                const linkNota = document.createElement('a');
                linkNota.href = data.nota_entrega_pdf_url;
                linkNota.download = '';
                document.body.appendChild(linkNota);
                linkNota.click();
                document.body.removeChild(linkNota);
            }

            window.location.href = '/sales-orders';
        })
        .catch(error => {
            console.error('Error:', error);
            alert(error.message || 'Error al confirmar la compra.');
        })
        .finally(() => {
            // Restaurar botón en cualquier caso
            button.disabled = false;
            button.innerHTML = originalText;
        });
});


    </script>
@endpush