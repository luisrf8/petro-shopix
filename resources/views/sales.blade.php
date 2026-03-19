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
        right: 16px;
        bottom: 16px;
        z-index: 1080;
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
  </style>
  @extends('layouts.app')

    @section('title', 'Categorías')

    @section('content')
    <div class="container-fluid px-2 px-md-4">
        <button type="button"
            id="openAdminCartBtn"
            class="btn btn-dark admin-cart-fab d-xl-none"
            data-bs-toggle="offcanvas"
            data-bs-target="#cart"
            aria-controls="cart">
            <i class="material-symbols-rounded align-middle">shopping_cart</i>
            <span class="ms-1">Carrito</span>
            <span class="badge bg-light text-dark ms-2" id="adminCartCount">0</span>
        </button>

        <div class="row g-4">
        <div class="col-12 col-xl-8">
            <h1>Flujo de Venta</h1>
            <span id="dollarRate" data-rate="{{ $dollarRate}}"></span>
            <span id="customerId" data-rate="{{ $customerId}}"></span>
            <form id="purchaseForm">
                @csrf
                <!-- Paso 1: Selección del Ítem -->
                <div id="step1" class="step">
                    <!-- Input de Búsqueda -->
                    <div class="">
                        <input 
                            type="text" 
                            id="searchCategory" 
                            class="form-control border border-1 p-2 bg-white" 
                            placeholder="Buscar categoría..." 
                            onkeyup="filterCategories()">
                    </div>
                    <div id="categoriesContainer" class="d-flex overflow-auto gap-3 py-3 mb-2" style="scroll-snap-type: x mandatory;">
                        <div class="category-item flex-shrink-0" style="width: 200px; scroll-snap-align: start;" data-category="all" onclick="filterProductsByCategory('all')">
                            <a href="javascript:void(0)" class="text-decoration-none category-filter">
                                <div class="card h-100">
                                    <div class="card-header mx-3 p-3 text-center">
                                        <div class="icon icon-shape icon-lg bg-gradient-dark shadow text-center border-radius-lg">
                                            <i class="material-symbols-rounded opacity-10">all_inclusive</i>
                                        </div>
                                    </div>
                                    <div class="card-body pt-0 p-3 text-center">
                                        <h6 class="text-center mb-0 opacity-9">Todos</h6>
                                    </div>
                                </div>
                            </a>
                        </div>
                        @foreach($categories as $category)

                            <div class="category-item flex-shrink-0" style="width: 200px; scroll-snap-align: start;" data-category-name="{{ $category->name }}" data-category="{{ $category->id }}" onclick="filterProductsByCategory('{{ $category->id }}')">
                                <a href="javascript:void(0)" class="text-decoration-none category-filter">
                                    <div class="card h-100">
                                        <div class="card-header mx-3 p-3 text-center">
                                            <div class="icon icon-shape icon-lg bg-gradient-dark shadow text-center border-radius-lg">
                                                <i class="material-symbols-rounded opacity-10"></i>
                                            </div>
                                        </div>
                                        <div class="card-body pt-0 p-3 text-center">
                                            <h6 class="text-center mb-0 opacity-9">{{ $category->name }}</h6>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        @endforeach

                        @if(isset($materialPackages) && $materialPackages->count() > 0)
                            <div class="category-item flex-shrink-0" style="width: 200px; scroll-snap-align: start;" data-category-name="paquetes" data-category="packages" onclick="filterProductsByCategory('packages')">
                                <a href="javascript:void(0)" class="text-decoration-none category-filter">
                                    <div class="card h-100">
                                        <div class="card-header mx-3 p-3 text-center">
                                            <div class="icon icon-shape icon-lg bg-gradient-dark shadow text-center border-radius-lg">
                                                <i class="material-symbols-rounded opacity-10">inventory_2</i>
                                            </div>
                                        </div>
                                        <div class="card-body pt-0 p-3 text-center">
                                            <h6 class="text-center mb-0 opacity-9">Paquetes</h6>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        @endif
                    </div>
                    <div class="mb-3">
                        <input 
                            type="text" 
                            id="searchInput" 
                            class="form-control border border-1 p-2 bg-white" 
                            placeholder="Buscar producto..." 
                            onkeyup="filterProducts()">
                    </div>

                    <div class="card border mb-3">
                        <div class="card-body">
                            <h6 class="mb-2">Agregar por QR / Código de barras</h6>
                            <div class="d-flex gap-2 flex-wrap">
                                <input type="text" id="scanCodeInput" class="form-control border border-1 p-2 bg-white" placeholder="Escanea o pega el código">
                                <button type="button" class="btn btn-dark mb-0" id="scanCodeBtn">Agregar</button>
                                <button type="button" class="btn btn-outline-dark mb-0" id="openQrScannerBtn" data-bs-toggle="modal" data-bs-target="#scanQrModal">Escanear con cámara</button>
                            </div>
                        </div>
                    </div>

                                @if(isset($materialPackages) && $materialPackages->count() > 0)
                                    <div id="materialPackagesSection" class="card border mb-3 material-packages-section" data-category="packages">
                                        <div class="card-body">
                                            <h6 class="mb-3">Paquetes / Listas de materiales</h6>
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
                                                                        <p class="text-sm fw-bold mb-0">{{ number_format($packageTotal, 2) }} USD</p>
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
                                    </div>
                                @endif

                    <div id="itemSelector" class="row row-cols-1 row-cols-md-3 g-3">
                        @foreach($productItems as $item)
                            <div class="col product-item" data-category="{{ $item->category_id }}" data-name="{{ strtolower($item->name) }}">
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
                                            @endphp
                                            <div class="d-flex gap-5 justify-content-between align-items-center">
                                                <label for="variant_{{ $variant->id }}" class="d-block mt-2 variant-label" style="cursor: pointer;" data-product-name="{{ $item->name }}">
                                                    <input type="checkbox" class="form-check-input me-2 variant-checkbox" id="variant_{{ $variant->id }}" name="selectedVariants[]" value="{{ $variant->id }}"
                                                    data-price="{{ number_format($effectiveVariantPrice, 2, '.', '') }}" data-stock="{{ $variant->stock }}"
                                                    data-product-name="{{ $item->name }}"
                                                    data-size="{{ $variant->size }}"
                                                    data-taxes="{{ $item->taxes }}">
                                                    <span>
                                                        {{$variant->size}} |
                                                        @if($productDiscount > 0 || $variantDiscount > 0)
                                                            <span class="text-decoration-line-through text-muted">{{ number_format((float) $variant->price, 2) }} USD</span>
                                                            <span class="fw-semibold">{{ number_format($effectiveVariantPrice, 2) }} USD</span>
                                                            <small class="text-success">(-{{ number_format($productDiscount + $variantDiscount, 2) }}%)</small>
                                                        @else
                                                            {{ number_format((float) $variant->price, 2) }} USD
                                                        @endif
                                                        | Stock: {{ $variant->stock }}
                                                    </span>
                                                    <i class="check-icon d-none ms-2 text-success fas fa-check"></i>
                                                </label>
                                                <i class="material-symbols-rounded text-info" style="cursor: pointer"
                                                    onclick="showProductDetails('{{ $item->name }}', '{{ $item->description }}', '{{ isset($item->images) && count($item->images) > 0 ? (\App\Support\ImageStorage::url($item->images[0]->path) ?? asset('assets/img/shopix5.png')) : '' }}', '{{ number_format($effectiveVariantPrice, 2, '.', '') }}', '{{ $variant->stock }}', '{{ $variant->size }}')">
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
                <div id="step2" class="step d-none">
                    <h4>Paso 2: Selecciona Métodos de Pago</h4>
                    <div id="totalAmountDisplay" class="mt-3">
                        <strong>Total a pagar: </strong><span id="totalAmountValue">0.00</span>$
                    </div>
                    <div class="">
                        <strong>Tasa BCV: </strong><span id="dollarRate" data-rate="{{ number_format($dollarRate->rate, 2, '.', '') }}">{{ number_format($dollarRate->rate, 2) }} Bs.</span>
                    </div>
                    <div class="">
                        <strong>Total a pagar: </strong><span id="totalAmountBsValue">0.00</span>Bs 
                    </div>
                    <div class="mt-2">
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

                        @if($igtfTax)
                            <strong>
                                Si el método de pago seleccionado es dólares (USD) se aplicará el impuesto del IGTF del {{ $igtfTax->rate }}%
                            </strong>
                        @endif
                    </div>
                    <div id="paymentMethods" class="mb-3">
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
                                    <div class="d-flex justify-content-between align-items-center">
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
                                                @php
                                                    $noReference = in_array(strtolower($method->name), ['efectivo', 'punto de venta', 'pago movil']);
                                                @endphp
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

                    <div id="paymentSummary" class="mt-3">
                        <strong>Total ingresado: </strong> $ <span id="totalPaid">0.00</span><br>
                        <span class="text-danger paymentMessage"></span>
                    </div>
                    <div id="paymentsContainer" class="mt-3">
                        <!-- Aquí se mostrarán los métodos de pago seleccionados -->
                        <ul id="selectedPaymentMethods" class="list-group">
                            <!-- Los métodos de pago seleccionados se agregarán aquí dinámicamente -->
                        </ul>
                    </div>
                    <div class="d-flex justify-content-between w-100 align-items-center">
                        <button type="button" class="btn btn-secondary mt-3" id="backToStep1">Atrás</button>
                        <button type="button" class="btn btn-info mt-3" id="toStep3" disabled>Siguiente</button>
                    </div>
                </div>

                <div id="step3" class="step d-none">
                    <h4>Paso 3: Confirmación</h4>
                    <p>Resumen de la compra y confirmación.</p>

                    <div class="card p-3 mb-3">
                        <h6 class="mb-2">Cliente para esta venta</h6>
                        <div class="d-flex gap-4 flex-wrap mb-2">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="create_new_customer" id="create_customer_no" value="no" checked>
                                <label class="form-check-label" for="create_customer_no">No, usar cliente actual</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="create_new_customer" id="create_customer_yes" value="yes">
                                <label class="form-check-label" for="create_customer_yes">Sí, crear nuevo cliente</label>
                            </div>
                        </div>

                        <div id="newCustomerForm" class="row g-2 d-none">
                            <div class="col-12 col-md-6">
                                <label for="newCustomerName" class="form-label mb-1">Nombre</label>
                                <input type="text" id="newCustomerName" class="form-control border border-1 p-2 bg-white" placeholder="Nombre del cliente">
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="newCustomerEmail" class="form-label mb-1">Correo</label>
                                <input type="email" id="newCustomerEmail" class="form-control border border-1 p-2 bg-white" placeholder="correo@ejemplo.com">
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="newCustomerPhone" class="form-label mb-1">Teléfono</label>
                                <input type="text" id="newCustomerPhone" class="form-control border border-1 p-2 bg-white" placeholder="Ej: +58 412 1234567">
                            </div>
                            <div class="col-12 col-md-6">
                                <label for="newCustomerDni" class="form-label mb-1">DNI</label>
                                <input type="text" id="newCustomerDni" class="form-control border border-1 p-2 bg-white" placeholder="Documento de identidad">
                            </div>
                        </div>
                    </div>

                    <div class="card p-3 mb-3">
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
                            </div>
                        </div>
                    </div>

                    <div class="card p-3 mb-3">
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

                    <div id="summaryContainer" class="mt-3 card p-4"></div> <!-- Aquí se insertará el resumen -->
                    <span class="text-danger paymentMessage"></span>

                    <div class="d-flex justify-content-between w-100 align-items-center">
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
                        <strong>Sub Total:</strong> $<span id="cartSubTotal">0.00</span>
                    </div>
                    <div class="mt-3 igtf-class" style="display: none;">
                        <strong>Total sin IGTF:</strong> $<span id="cartTotalIGTF">0.00</span>
                    </div>
                    <div class="mt-3">
                        <strong>Total:</strong> $<span id="cartTotal">0.00</span>
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
<!-- Modal para Detalles del Producto -->
<div class="modal fade" id="productDetailModal" tabindex="-1" aria-labelledby="productDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="productDetailModalLabel">Detalles del Producto</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex gap-4">
                    <!-- Imagen del producto -->
                    <div style="width: 200px; height: 200px;">
                        <img id="modalProductImage" src="" alt="Imagen del producto" style="width: 100%; height: 100%; object-fit: cover; border-radius: 8px;">
                    </div>
                    <!-- Información del producto -->
                    <div>
                        <h5 id="modalProductName"></h5>
                        <p id="modalProductDescription"></p>
                        <p><strong>Precio:</strong> $<span id="modalProductPrice"></span></p>
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
    @endsection

@push('scripts')
<!-- Core JS Files -->
<script src="{{ asset('assets/js/core/popper.min.js') }}"></script>
<script src="{{ asset('assets/js/core/bootstrap.min.js') }}"></script>
<script src="https://unpkg.com/html5-qrcode" defer></script>
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

        const dollar = @json($dollarRate);
        const dollarRate = Number(dollar.rate);
        
        const authUser = @json($authUser);
        const customerId = Number(authUser?.id || 0);
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

                        return [
                            'variant_id' => $item->product_variant_id,
                            'variant_size' => $item->variant->size ?? '',
                            'variant_price' => (float) $effectivePrice,
                            'product_name' => $item->variant->product->name ?? 'Producto',
                            'quantity' => (float) ($item->quantity ?? 0),
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

        function calculateTaxRateFromTaxes(taxes) {
            return (taxes || []).reduce((sum, tax) => sum + (parseFloat(tax.rate) || 0), 0);
        }

        function addMaterialPackageToSale(packageId) {
            const pkg = materialPackages.find(p => Number(p.id) === Number(packageId));
            if (!pkg) {
                alert('No se encontró el paquete seleccionado.');
                return;
            }

            const qtyInput = document.getElementById(`packageQty_${packageId}`);
            const packQty = Math.max(1, parseInt(qtyInput?.value || '1', 10));

            pkg.items.forEach(component => {
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

                const packageBaseTotal = pkg.items.reduce((sum, row) => {
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
                        stock: 999999,
                        quantity: componentQty,
                        line_discount_percentage: combinedLineDiscount,
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
                    quantity: 1,
                    line_discount_percentage: 0,
                    taxes,
                    taxRate: totalTaxRate,
                    taxAmount,
                    totalPrice // <--- Guardar precio con impuestos
                });

                // Sumar al total general
                totalAmount += totalPrice;
                subTotalAmount += price;
                console.log("Selected Items:", selectedItems);
            } else {
                const removedItem = selectedItems.find(item => item.id === id);
                if (removedItem) totalAmount -= removedItem.totalPrice;

                selectedItems = selectedItems.filter(item => item.id !== id);
            }

            renderCart();
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
            const totalAmountValue = document.getElementById('totalAmountValue');
            const totalAmountBsValue = document.getElementById('totalAmountBsValue');
            const toStep2Btn = document.getElementById('toStep2');

            cartList.innerHTML = '';

            selectedItems.forEach(item => {
                const li = document.createElement('li');
                li.className = 'list-group-item d-flex justify-content-between align-items-start flex-column';

                const textDiv = document.createElement('div');
                textDiv.innerHTML = `
                    <strong>${item.productName} ${item.productSize}</strong><br>
                    Subtotal: ${(item.price * item.quantity).toFixed(2)} USD
                    <br>
                    Impuestos:<br>
                    ${item.taxes.map(tax => `• ${tax.name} (${parseFloat(tax.rate)}%)`).join('<br>')}
                    <br>
                    <strong>Total con Impuestos: ${(item.totalPrice * item.quantity).toFixed(2)} USD</strong>
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
            // Obtener la tasa del dólar desde el DOM
            const dollar = @json($dollarRate);
            const dollarRate = Number(dollar.rate);

            const taxesContainer = document.getElementById('taxesContainer');

            const { totalUsd, tax } = calculateUsdTax();

            taxesContainer.innerHTML = `
                <div class="mt-3 igtf-class" style="display: none;">
                    <strong>Total Pagado en USD:</strong> $${totalUsd.toFixed(2)}
                </div>
                <div class="mt-1 text-danger igtf-class" style="display: none;">
                    <strong>Impuesto 3% por pago en USD:</strong> $${tax.toFixed(2)}
                </div>
            `;
            
            let totalItemsWithTaxes = selectedItems.reduce((acc, item) => {
                return acc + (item.totalPrice * item.quantity);
            }, 0);

            if(igtfTax && totalUsd > 0) {
                document.querySelectorAll('.igtf-class').forEach(el => el.style.display = 'block');
            } else {
                document.querySelectorAll('.igtf-class').forEach(el => el.style.display = 'none');
            }

            totalAmount = totalItemsWithTaxes + tax;
            totalSinIGTF = totalItemsWithTaxes;
            console.log("Total sin IGTF:", totalSinIGTF);
            cartTotal.textContent = totalAmount.toFixed(2); 
            cartSubTotal.textContent = subTotalAmount.toFixed(2);
            cartTotalBs.textContent = (totalAmount * dollarRate ).toFixed(2); 
            cartSubTotalBs.textContent = (subTotalAmount * dollarRate ).toFixed(2);
            cartTotalIGTF.textContent = totalSinIGTF.toFixed(2);
            totalAmountValue.textContent = totalAmount.toFixed(2); // Asegúrate de mostrar un número válido
            totalAmountBsValue.textContent = (totalAmount * dollarRate ).toFixed(2); // Asegúrate de mostrar un número válido
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
            activeCategory = categoryId;
            const productItems = document.querySelectorAll('.product-item');
            const packagesSection = document.getElementById('materialPackagesSection');

            productItems.forEach(item => {
                const itemCategory = item.getAttribute('data-category');
                if (categoryId === 'all' || (categoryId !== 'packages' && itemCategory === categoryId)) {
                    item.style.display = 'block'; // Mostrar si coincide con la categoría seleccionada
                } else {
                    item.style.display = 'none'; // Ocultar si no coincide
                }
            });

            if (packagesSection) {
                const showPackages = categoryId === 'all' || categoryId === 'packages';
                packagesSection.style.display = showPackages ? 'block' : 'none';
            }

            // Limpiar el campo de búsqueda de productos al cambiar de categoría
            document.getElementById('searchInput').value = '';
        }
        
        function filterProducts() {
            const searchInput = document.getElementById('searchInput');
            const filter = searchInput.value.toLowerCase();
            const productItems = document.querySelectorAll('.product-item');
            const packageItems = document.querySelectorAll('.package-item');
            const packagesSection = document.getElementById('materialPackagesSection');

            const isAll = activeCategory === 'all';
            const isPackages = activeCategory === 'packages';

            productItems.forEach(item => {
                const name = item.getAttribute('data-name');
                const itemCategory = item.getAttribute('data-category');
                const matchCategory = isAll || (!isPackages && itemCategory === activeCategory);

                if (matchCategory && name.includes(filter)) {
                    item.style.display = 'block'; // Mostrar si coincide
                } else {
                    item.style.display = 'none'; // Ocultar si no coincide
                }
            });

            if (packagesSection) {
                let hasVisiblePackage = false;

                packageItems.forEach(item => {
                    const name = item.getAttribute('data-name') || '';
                    const shouldShow = (isAll || isPackages) && name.includes(filter);
                    item.style.display = shouldShow ? 'block' : 'none';
                    if (shouldShow) {
                        hasVisiblePackage = true;
                    }
                });

                packagesSection.style.display = hasVisiblePackage ? 'block' : 'none';
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
        document.getElementById('toStep2').addEventListener('click', function() {
            document.getElementById('step1').classList.add('d-none');
            document.getElementById('step2').classList.remove('d-none');

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
                ? `<input type="hidden" class="payment-reference-input" data-method-id="${methodId}" data-entry-id="${entryId}" data-requires-reference="0" value="00">`
                : `<input type="text" class="form-control payment-reference-input border border-1 p-2" data-method-id="${methodId}" data-entry-id="${entryId}" data-requires-reference="1" placeholder="Referencia" oninput="updatePayment(this)">`;

            return `
                <div class="d-flex flex-row gap-2 align-items-center" data-payment-entry-row="${entryId}">
                    <label class="m-0">Monto:</label>
                    <input type="number" step="0.01" min="0" class="form-control payment-input border border-1 p-2"
                        data-method-id="${methodId}"
                        data-entry-id="${entryId}"
                        data-currency="${currency}"
                        oninput="updatePayment(this)">
                    ${referenceInput}
                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="removePaymentEntry('${methodId}', '${entryId}')">X</button>
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
                reference: noReference ? '00' : ''
            });

            validatePaymentDetails();
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

            const dollar = @json($dollarRate);
            const dollarRate = Number(dollar.rate);

            if (payment) {
                if (input.classList.contains('payment-input')) {
                    let amount = parseFloat(input.value) || 0;

                    if (currency === 'BS') {
                        amount = amount / dollarRate;
                    }

                    payment.amount = amount;
                } else if (input.classList.contains('payment-reference-input')) {
                    payment.reference = input.value;
                }
            }
            console.log("payments 2", payments);
            renderCart();
            validatePaymentDetails();
        }

        function getUsdPaymentsTotal() {
            let totalUsd = 0;

            payments.forEach(p => {
                if (p.currency === 'USD') {
                    totalUsd += Number(p.amount) || 0;
                }
            });

            return totalUsd;
        }

        function calculateUsdTax() {
            const totalUsd = getUsdPaymentsTotal();
            const tax = totalUsd * 0.03; // 3%
            return {
                totalUsd,
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
            };
        }

        function renderSummary() {
            const container = document.getElementById('summaryContainer');
            const deliveryType = document.querySelector('input[name="delivery_type"]:checked')?.value || 'pickup';
            const deliveryAddressData = buildDeliveryAddress();
            const deliveryPreferenceLabel = deliveryType === 'shipping' ? 'Envío' : 'Retiro en tienda';
            const shouldCreateNewCustomer = document.querySelector('input[name="create_new_customer"]:checked')?.value === 'yes';
            const newCustomerName = (document.getElementById('newCustomerName')?.value || '').trim();
            const newCustomerEmail = (document.getElementById('newCustomerEmail')?.value || '').trim();
            const newCustomerPhone = (document.getElementById('newCustomerPhone')?.value || '').trim();
            const newCustomerDni = (document.getElementById('newCustomerDni')?.value || '').trim();
            
            const dollar = @json($dollarRate);
            const dollarRate = Number(dollar.rate);

            container.innerHTML = ''; // Limpiar resumen anterior

            // Resumen de items
            const itemsTitle = document.createElement('h5');
            itemsTitle.innerText = 'Productos seleccionados';
            container.appendChild(itemsTitle);

            const itemList = document.createElement('ul');
            selectedItems.forEach(item => {
                const li = document.createElement('li');
                li.innerText = `${item.productName} - Variante: ${item.productSize} - Cantidad: ${item.quantity} - Subtotal: $${(item.price * item.quantity).toFixed(2)}`;
                itemList.appendChild(li);
            });
            container.appendChild(itemList);
            itemList.className = 'card p-4 gap-2';

            // Total
            const totalDiv = document.createElement('p');
            totalDiv.innerHTML = `<strong>Total a pagar:</strong> $${totalAmount.toFixed(2)}`;
            container.appendChild(totalDiv);

            // Total BS
            console.log("dollarRate", dollarRate)
            const totalDivBs = document.createElement('p');
            totalDivBs.innerHTML = `<strong>Total a pagar Bs:</strong> Bs${(totalAmount * dollarRate).toFixed(2)}`;
            container.appendChild(totalDivBs);

            const deliveryDiv = document.createElement('p');
            deliveryDiv.innerHTML = `<strong>Entrega:</strong> ${deliveryPreferenceLabel}`;
            container.appendChild(deliveryDiv);

            const addressDiv = document.createElement('p');
            addressDiv.innerHTML = `<strong>Dirección:</strong> ${deliveryType === 'shipping' ? (deliveryAddressData.address || 'No indicada') : 'Tienda'}`;
            container.appendChild(addressDiv);

            const customerDiv = document.createElement('p');
            customerDiv.innerHTML = `<strong>Cliente:</strong> ${shouldCreateNewCustomer ? 'Nuevo cliente' : 'Cliente actual'}`;
            container.appendChild(customerDiv);

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
                    li.innerText = `${payment.methodName} (${payment.currency}) - Monto: $${amount.toFixed(2)} - Referencia: ${reference}`;
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
                    <strong>Monto:</strong> $${amount.toFixed(2)} <br>
                    <strong>Referencia:</strong> ${reference} <br>
                    <hr>
                `;

                paymentsContainer.appendChild(paymentDiv);
            });
        }

        function validatePaymentDetails() {
            totalPaid = payments.reduce((sum, payment) => sum + payment.amount, 0);
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
            } else if (totalPaid < totalAmount) {
                const remaining = (totalAmount - totalPaid).toFixed(2);
                messageText = `Falta por pagar: $${remaining} / BS${(remaining * dollarRate).toFixed(2)}`;
                messageClass = 'text-danger';
                disableStep3 = true;
            } else if (totalPaid > totalAmount) {
                const change = (totalPaid - totalAmount).toFixed(2);
                messageText = `Debe entregar vuelto: $${change} / BS${(change * dollarRate).toFixed(2)}`;
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


        //Funciones para paso 3
        document.getElementById('toStep3').addEventListener('click', function() {
            closeCartOffcanvas(true);
            document.getElementById('step2').classList.add('d-none');
            renderSummary(); // Mostrar el resumen
            document.getElementById('cart').classList.add('d-none');
            document.getElementById('step3').classList.remove('d-none');
            document.getElementById('openAdminCartBtn')?.classList.add('d-none');
            console.log('Resumen:', selectedItems);
            console.log('Pagos:', payments);
        });
        document.getElementById('backToStep2').addEventListener('click', function() {
            document.getElementById('step3').classList.add('d-none');
            document.getElementById('step2').classList.remove('d-none');
            document.getElementById('cart').classList.remove('d-none');
            document.getElementById('openAdminCartBtn')?.classList.remove('d-none');

        });

        function updateDeliveryAddressVisibility() {
            const selectedType = document.querySelector('input[name="delivery_type"]:checked')?.value || 'pickup';
            const addressContainer = document.getElementById('deliveryAddressContainer');
            const countrySelect = document.getElementById('deliveryCountry');
            const stateSelect = document.getElementById('deliveryState');
            const citySelect = document.getElementById('deliveryCity');
            const addressDetailInput = document.getElementById('deliveryAddressDetail');

            if (selectedType === 'shipping') {
                addressContainer.classList.remove('d-none');
                ensureDeliveryCountriesLoaded().catch(() => {
                    alert('No se pudieron cargar los países para el envío.');
                });
            } else {
                addressContainer.classList.add('d-none');
                if (countrySelect) countrySelect.value = '';
                if (stateSelect) resetLocationSelect(stateSelect, 'Estado (parte del país)', true);
                if (citySelect) resetLocationSelect(citySelect, 'Ciudad', true);
                if (addressDetailInput) addressDetailInput.value = '';
            }

            if (!document.getElementById('step3').classList.contains('d-none')) {
                renderSummary();
            }
        }

        function updateCreateCustomerVisibility() {
            const shouldCreateNewCustomer = document.querySelector('input[name="create_new_customer"]:checked')?.value === 'yes';
            const form = document.getElementById('newCustomerForm');
            if (!form) return;

            form.classList.toggle('d-none', !shouldCreateNewCustomer);

            if (!document.getElementById('step3').classList.contains('d-none')) {
                renderSummary();
            }
        }

        document.querySelectorAll('input[name="delivery_type"]').forEach(input => {
            input.addEventListener('change', updateDeliveryAddressVisibility);
        });

        document.getElementById('deliveryAddressDetail').addEventListener('input', function () {
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
    const deliveryAddressData = buildDeliveryAddress();
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

    if (shouldCreateNewCustomer) {
        const emailPattern = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        if (!newCustomerName || !newCustomerEmail || !newCustomerPhone || !newCustomerDni) {
            alert('Para crear un cliente nuevo debes completar nombre, correo, teléfono y DNI.');
            button.disabled = false;
            button.innerHTML = originalText;
            return;
        }

        if (!emailPattern.test(newCustomerEmail)) {
            alert('El correo del nuevo cliente no es válido.');
            button.disabled = false;
            button.innerHTML = originalText;
            return;
        }
    }

    const validPayments = payments.filter(payment => Number(payment.amount || 0) > 0);

    const summary = {
        customerId: shouldCreateNewCustomer ? null : customerId,
        items: selectedItems,
        payments: validPayments,
        tenant_id: tenantId,
        dollarRate: dollarRate,
        delivery_type: deliveryType,
        delivery_address: deliveryType === 'shipping' ? deliveryAddressData.address : 'Tienda',
        create_new_customer: shouldCreateNewCustomer,
        customer_new: shouldCreateNewCustomer
            ? {
                name: newCustomerName,
                email: newCustomerEmail,
                phone_number: newCustomerPhone,
                dni: newCustomerDni,
            }
            : null,
        mark_delivered: document.getElementById('markDelivered')?.checked || false,
        mark_payments_paid: document.getElementById('markPaymentsPaid')?.checked || false,
        mark_sale_completed: document.getElementById('markSaleCompleted')?.checked || false,
    };

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    console.log('Resumen a enviar:', summary);

    fetch('/create-sale', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify(summary)
    })
        .then(response => {
            if (response.ok) {
                return response.json();
            } else {
                throw new Error('Error al confirmar la compra.');
            }
        })
        .then(data => {
            alert(data.message || 'Compra confirmada con éxito.');

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
            alert('Error al confirmar la compra.');
        })
        .finally(() => {
            // Restaurar botón en cualquier caso
            button.disabled = false;
            button.innerHTML = originalText;
        });
});


    </script>
@endpush