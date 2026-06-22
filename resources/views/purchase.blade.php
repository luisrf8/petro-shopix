<style>
    .purchase-step {
        border: 1px solid #e9ecef;
        border-radius: .75rem;
        padding: .5rem .75rem;
        background: #fff;
        color: #6c757d;
        font-weight: 600;
    }

    .purchase-step.active {
        border-color: #0dcaf0;
        color: #0d6efd;
        background: #f1fbff;
    }

    .summary-pill {
        border-radius: 999px;
        border: 1px solid #e9ecef;
        padding: .4rem .8rem;
        background: #fff;
        font-weight: 600;
    }

    .purchase-product-thumb {
        width: 70px;
        height: 70px;
        border-radius: .75rem;
        overflow: hidden;
        border: 1px solid #111;
        flex-shrink: 0;
    }

    .purchase-product-thumb img {
        width: 100%;
        height: 100%;
        object-fit: cover;
    }

    .variant-row {
        border-top: 1px dashed #e5e7eb;
        padding-top: .6rem;
        margin-top: .6rem;
    }
</style>
@extends('layouts.app')

@section('title', 'Categorías')

@section('content')
        <div class="py-1 px-3 text-end">
      <a class="nav-link text-dark" href="/createProduct">
        + Agregar Producto
      </a>
    </div>
    <div class="mx-5">
                <h1>Entrada de Inventario</h1>

                <div class="card card-body mb-3">
                    <label class="form-label mb-2">Tipo de entrada</label>
                    <div class="d-flex flex-wrap gap-3" id="entryModeSwitch">
                        <label class="d-flex align-items-center gap-2 mb-0">
                            <input class="form-check-input" type="radio" name="entry_mode" value="purchase" checked>
                            <span>Compra / pedido (con proveedor)</span>
                        </label>
                        <label class="d-flex align-items-center gap-2 mb-0">
                            <input class="form-check-input" type="radio" name="entry_mode" value="production">
                            <span>Producción interna (con consumibles)</span>
                        </label>
                    </div>
                    <small class="text-muted mt-2" id="entryModeHelpText">Registra una compra normal con proveedor y costo unitario por variante.</small>
                </div>

                <div class="d-flex flex-wrap gap-2 mb-3">
                    <div class="summary-pill" id="summary-entry-mode">Modo: Compra</div>
                    <div class="summary-pill" id="summary-selected-products">Productos: 0</div>
                    <div class="summary-pill" id="summary-selected-variants">Variantes: 0</div>
                    <div class="summary-pill" id="summary-total-units">Unidades: 0</div>
                    <div class="summary-pill" id="summary-total-amount">Monto estimado: 0.00 {{ $baseCurrencyCode ?? 'USD' }}</div>
                </div>

                <div class="d-flex flex-wrap gap-2 mb-4" id="purchase-steps-indicator">
                    <div class="purchase-step active" data-step="1">1. Productos, variantes y costos</div>
                    <div class="purchase-step" data-step="2">2. Proveedor y fecha</div>
                    <div class="purchase-step" data-step="3">3. Confirmación</div>
                </div>

        <form id="purchaseForm">
            @csrf
            <div id="step1">
                <h4>Paso 1: Selecciona variantes y define cantidad/costo</h4>
                <div class="mb-3">
                    <input 
                        type="text" 
                        id="searchInput" 
                        class="form-control border border-1 p-2 bg-white" 
                        placeholder="Buscar producto..." 
                        onkeyup="filterProducts()">
                </div>
                <button type="button" class="btn btn-info mt-3 toStep2" disabled>Siguiente</button>
                <div id="itemSelector" class="row row-cols-1 row-cols-md-2 g-3">
                    @foreach ($productItems as $item)
                        @php
                            $itemImage = isset($item->images) && count($item->images) > 0
                                ? (\App\Support\ImageStorage::url($item->images[0]->path) ?? asset('assets/img/shopix5.png'))
                                : asset('assets/img/shopix5.png');
                        @endphp
                        <div class="col product-item" data-name="{{ strtolower($item->name) }}">
                            <div class="card h-100">
                                <div class="card-body">
                                                                    <div class="d-flex gap-3 align-items-center mb-2">
                                                                        <div class="purchase-product-thumb">
                                                                            <img src="{{ $itemImage }}" alt="{{ $item->name }}">
                                                                        </div>
                                                                        <div class="flex-grow-1">
                                                                            <h5 class="mb-1">{{ $item->name }}</h5>
                                                                            <p class="mb-0 text-sm text-muted">{{ $item->description }}</p>
                                                                        </div>
                                                                    </div>

                                                                    @foreach($item->variants as $variant)
                                                                        <div class="variant-row" data-variant-row="{{ $variant->id }}">
                                                                            <div class="d-flex flex-wrap align-items-center gap-2 mb-2">
                                                                                <label class="mb-0 d-flex align-items-center gap-2" for="variant_{{ $variant->id }}">
                                                                                    <input
                                                                                        type="checkbox"
                                                                                        id="variant_{{ $variant->id }}"
                                                                                        class="form-check-input purchase-variant-checkbox"
                                                                                        data-variant-id="{{ $variant->id }}"
                                                                                        data-product-id="{{ $item->id }}"
                                                                                        data-product-name="{{ $item->name }}"
                                                                                        data-variant-size="{{ $variant->size }}"
                                                                                        data-product-image="{{ $itemImage }}"
                                                                                        data-default-price="{{ $variant->price }}"
                                                                                    >
                                                                                    <span>
                                                                                        {{ $variant->size }} | Stock: {{ $variant->stock }}
                                                                                        @if(!(bool) ($item->is_active ?? true))
                                                                                            <span class="badge bg-secondary ms-1">Inactivo</span>
                                                                                        @endif
                                                                                    </span>
                                                                                </label>
                                                                            </div>

                                                                            <div class="row g-2">
                                                                                <div class="col-6">
                                                                                    <input type="number" min="1" class="form-control purchase-qty border p-2" data-variant-id="{{ $variant->id }}" placeholder="Cantidad" disabled>
                                                                                </div>
                                                                                <div class="col-6">
                                                                                    <input type="number" min="0.01" step="0.01" class="form-control purchase-price border p-2" data-variant-id="{{ $variant->id }}" placeholder="Costo {{ $baseCurrencyCode ?? 'USD' }}" value="{{ number_format($variant->price, 2, '.', '') }}" disabled>
                                                                                </div>
                                                                                <div class="col-12">
                                                                                    <select class="form-control purchase-currency border p-2" data-variant-id="{{ $variant->id }}" disabled>
                                                                                        <option value="{{ $baseCurrencyCode ?? 'USD' }}">{{ $baseCurrencyCode ?? 'USD' }} (moneda madre)</option>
                                                                                        <option value="USD">USD</option>
                                                                                        <option value="EUR">EUR</option>
                                                                                        <option value="BS">BS</option>
                                                                                    </select>
                                                                                </div>
                                                                            </div>

                                                                            <div class="card card-body mt-2 d-none production-consumables" data-production-wrapper="{{ $variant->id }}">
                                                                                <div class="d-flex justify-content-between align-items-center mb-2">
                                                                                    <strong class="text-sm">Consumibles usados para esta producción</strong>
                                                                                    <button type="button" class="btn btn-outline-dark btn-sm mb-0" data-add-consumption="{{ $variant->id }}">+ Agregar consumible</button>
                                                                                </div>
                                                                                <div data-consumption-list="{{ $variant->id }}" class="d-flex flex-column gap-2"></div>
                                                                            </div>
                                                                            <div class="small text-end text-muted mt-1" data-line-total="{{ $variant->id }}">Subtotal: 0.00 {{ $baseCurrencyCode ?? 'USD' }}</div>
                                                                        </div>
                                                                    @endforeach
                                </div>
                                                        </div>
                        </div>
                    @endforeach
                </div>
                <button type="button" class="btn btn-info mt-3 toStep2" disabled>Siguiente</button>
            </div>

                        <div id="step2" class="d-none card card-body">
                                <h4 id="step2Title">Paso 2: Proveedor y fecha de entrada</h4>
                            <div class="mb-3">
                                <label for="warehouseId" class="form-label">Almacén destino</label>
                                <select id="warehouseId" class="form-control border border-1 p-2" required>
                                    <option value="">Selecciona un almacén</option>
                                    @foreach(($warehouses ?? collect()) as $warehouse)
                                        <option value="{{ $warehouse->id }}" {{ $warehouse->is_default ? 'selected' : '' }}>
                                            {{ $warehouse->name }}{{ $warehouse->is_default ? ' (Principal)' : '' }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                <div class="mb-3" id="providerBlock">
                    <label for="providerName" class="form-label">Proveedor</label>
                    <input type="text" id="providerName" list="purchaseProviderOptions" class="form-control border border-1 p-2" placeholder="Razón social del proveedor">
                    <small class="text-muted">Selecciona o escribe el proveedor principal de la factura.</small>
                    <div class="mt-2">
                        <a href="{{ route('providers.index') }}" class="btn btn-outline-dark btn-sm mb-0">Gestionar proveedores</a>
                    </div>
                </div>
                <div class="row g-3 mb-3" id="providerTaxBlock">
                    <div class="col-md-4">
                        <label for="providerRif" class="form-label">RIF proveedor</label>
                        <input type="text" id="providerRif" class="form-control border border-1 p-2" placeholder="J-111222555">
                    </div>
                    <div class="col-md-4">
                        <label for="supplierInvoiceNumber" class="form-label">Nro. factura</label>
                        <input type="text" id="supplierInvoiceNumber" class="form-control border border-1 p-2" placeholder="20260200000019">
                    </div>
                    <div class="col-md-4">
                        <label for="supplierInvoiceControlNumber" class="form-label">Nro. control</label>
                        <input type="text" id="supplierInvoiceControlNumber" class="form-control border border-1 p-2" placeholder="00-00000019">
                    </div>
                    <div class="col-md-4">
                        <label for="supplierInvoiceDate" class="form-label">Fecha factura</label>
                        <input type="date" id="supplierInvoiceDate" class="form-control border border-1 p-2">
                    </div>
                    <div class="col-md-8">
                        <label for="supplierInvoiceFile" class="form-label">Factura soporte</label>
                        <input type="file" id="supplierInvoiceFile" class="form-control border border-1 p-2" accept=".pdf,.jpg,.jpeg,.png">
                        <small class="text-muted">Opcional. Se almacenará como respaldo de la retención.</small>
                    </div>
                </div>
                <datalist id="purchaseProviderOptions">
                    @foreach(($providers ?? collect()) as $provider)
                        <option value="{{ $provider->name }}"></option>
                    @endforeach
                </datalist>
                <div class="mb-3">
                    <label for="purchaseDate" class="form-label">Fecha de compra</label>
                    <input type="date" id="purchaseDate" class="form-control border border-1 p-2">
                </div>
                <div class="d-flex justify-content-between gap-2">
                    <button type="button" class="btn btn-secondary mt-3" id="backToStep1">Atrás</button>
                    <button type="button" class="btn btn-info mt-3" id="toStep3" disabled>Siguiente</button>
                </div>
            </div>

            <div id="step3" class="d-none">
                <h4>Paso 3: Confirmación</h4>
                <div id="providerContainer"></div>
                <div class="bg-white mt-3 mb-0" id="finalSummaryText"></div>
                <button type="button" class="btn btn-secondary mt-3" id="backToStep2">Atrás</button>
                <button type="button" class="btn btn-info mt-3" id="createOrder">Registrar entrada</button>
            </div>
        </form>
    </div>
    @endsection

@push('scripts')
<script src="{{ asset('assets/js/core/popper.min.js') }}"></script>
<script src="{{ asset('assets/js/core/bootstrap.min.js') }}"></script>
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>

<!-- Github buttons -->
<!-- <script async defer src="https://buttons.github.io/buttons.js"></script> -->
@php
    $consumableVariantsPayload = ($productItems ?? collect())->flatMap(function ($product) {
        return ($product->variants ?? collect())->map(function ($variant) use ($product) {
            $inactiveSuffix = !(bool) ($product->is_active ?? true) ? ' [INACTIVO]' : '';
            return [
                'id' => (int) $variant->id,
                'label' => trim(($product->name ?? 'Producto') . ' - ' . ($variant->size ?? 'Sin variante') . $inactiveSuffix),
                'default_price' => (float) ($variant->price ?? 0),
                'stock' => (float) ($variant->stock ?? 0),
            ];
        });
    })->values();
@endphp
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const baseCurrencyCode = @json($baseCurrencyCode ?? 'USD');
            const baseRateToBs = Number(@json($baseRateToBs ?? 0));
            const dollarRateToBs = Number(@json($dollarRateToBs ?? 0));
            const euroRateToBs = Number(@json($euroRateToBs ?? 0));
            const consumableVariants = @json($consumableVariantsPayload);

            const toBaseCurrency = (amount, inputCurrency) => {
                const currency = String(inputCurrency || baseCurrencyCode).toUpperCase();
                const value = Number(amount || 0);

                if (currency === baseCurrencyCode) {
                    return value;
                }

                if (currency === 'BS' && baseRateToBs > 0) {
                    return value / baseRateToBs;
                }

                if ((currency === 'USD' || currency === 'EUR') && currency !== baseCurrencyCode) {
                    const fromRate = currency === 'EUR' ? euroRateToBs : dollarRateToBs;
                    if (fromRate > 0 && baseRateToBs > 0) {
                        return (value * fromRate) / baseRateToBs;
                    }
                }

                return value;
            };

            let itemsSelected = [];
            let entryMode = 'purchase';
            const toStep2 = document.querySelector('.toStep2');
            const step1 = document.getElementById('step1');
            const step2 = document.getElementById('step2');
            const toStep3 = document.getElementById('toStep3');
            const step3 = document.getElementById('step3');
            const backToStep1 = document.getElementById('backToStep1');
            const backToStep2 = document.getElementById('backToStep2');
            const providerInput = document.getElementById("providerName");
            const providerRifInput = document.getElementById("providerRif");
            const providerTaxBlock = document.getElementById('providerTaxBlock');
            const supplierInvoiceNumberInput = document.getElementById('supplierInvoiceNumber');
            const supplierInvoiceControlNumberInput = document.getElementById('supplierInvoiceControlNumber');
            const supplierInvoiceDateInput = document.getElementById('supplierInvoiceDate');
            const supplierInvoiceFileInput = document.getElementById('supplierInvoiceFile');
            const purchaseDateInput = document.getElementById('purchaseDate');
            const warehouseIdInput = document.getElementById('warehouseId');
            const entryModeSwitch = document.getElementById('entryModeSwitch');
            const entryModeHelpText = document.getElementById('entryModeHelpText');
            const providerBlock = document.getElementById('providerBlock');
            const step2Title = document.getElementById('step2Title');

            const summaryEntryMode = document.getElementById('summary-entry-mode');
            const summarySelectedProducts = document.getElementById('summary-selected-products');
            const summarySelectedVariants = document.getElementById('summary-selected-variants');
            const summaryTotalUnits = document.getElementById('summary-total-units');
            const summaryTotalAmount = document.getElementById('summary-total-amount');

            const stepIndicator = document.querySelectorAll('#purchase-steps-indicator [data-step]');

            purchaseDateInput.value = new Date().toISOString().slice(0, 10);

            function setActiveStep(stepNumber) {
                stepIndicator.forEach((step) => {
                    step.classList.toggle('active', Number(step.dataset.step) === Number(stepNumber));
                });
            }

            const getModeLabel = () => entryMode === 'production' ? 'Producción interna' : 'Compra';

            function refreshModeUI() {
                const isProduction = entryMode === 'production';

                summaryEntryMode.textContent = `Modo: ${getModeLabel()}`;
                entryModeHelpText.textContent = isProduction
                    ? 'Registra una producción interna consumiendo insumos del inventario para generar productos terminados.'
                    : 'Registra una compra normal con proveedor y costo unitario por variante.';

                step2Title.textContent = isProduction
                    ? 'Paso 2: Almacén y fecha de producción'
                    : 'Paso 2: Proveedor y fecha de entrada';

                providerBlock.classList.toggle('d-none', isProduction);
                providerTaxBlock.classList.toggle('d-none', isProduction);

                document.querySelectorAll('.purchase-price, .purchase-currency').forEach((el) => {
                    const variantId = el.dataset.variantId;
                    const checkbox = document.querySelector(`.purchase-variant-checkbox[data-variant-id="${variantId}"]`);
                    const checked = !!checkbox?.checked;

                    if (isProduction) {
                        el.disabled = true;
                    } else {
                        el.disabled = !checked;
                    }
                });

                document.querySelectorAll('.production-consumables').forEach((wrapper) => {
                    const variantId = wrapper.dataset.productionWrapper;
                    const checkbox = document.querySelector(`.purchase-variant-checkbox[data-variant-id="${variantId}"]`);
                    const checked = !!checkbox?.checked;
                    wrapper.classList.toggle('d-none', !(isProduction && checked));
                });

                toStep3.disabled = isProduction
                    ? (!purchaseDateInput.value || !warehouseIdInput.value)
                    : (providerInput.value.trim() === '' || providerRifInput.value.trim() === '' || supplierInvoiceNumberInput.value.trim() === '' || supplierInvoiceControlNumberInput.value.trim() === '' || !supplierInvoiceDateInput.value || !purchaseDateInput.value || !warehouseIdInput.value);
            }

            function buildConsumableOptions(selectedId = '') {
                const options = ['<option value="">Selecciona consumible</option>'];
                consumableVariants.forEach((variant) => {
                    const isSelected = Number(selectedId) === Number(variant.id);
                    options.push(`<option value="${variant.id}" ${isSelected ? 'selected' : ''}>${variant.label} (Stock: ${Number(variant.stock || 0)})</option>`);
                });
                return options.join('');
            }

            function initConsumableSelect2(selectElement) {
                if (!selectElement || typeof window.jQuery === 'undefined' || !window.jQuery.fn || !window.jQuery.fn.select2) {
                    return;
                }

                const $select = window.jQuery(selectElement);
                if ($select.hasClass('select2-hidden-accessible')) {
                    $select.select2('destroy');
                }

                $select.select2({
                    width: '100%',
                    dropdownAutoWidth: true,
                    placeholder: 'Selecciona consumible',
                    allowClear: true,
                    language: {
                        noResults: function () {
                            return 'Sin coincidencias';
                        }
                    }
                });

                $select.on('change.select2', function () {
                    const producedVariantId = this.dataset.producedVariantId;
                    const selectedVariantId = Number(this.value || 0);
                    const selectedVariant = consumableVariants.find((item) => Number(item.id) === selectedVariantId);
                    const costInput = this.closest('[data-consumption-row]')?.querySelector('.production-consumed-cost');

                    if (costInput && selectedVariant && Number(costInput.value || 0) <= 0) {
                        costInput.value = Number(selectedVariant.default_price || 0).toFixed(2);
                    }

                    if (producedVariantId) {
                        updateLineTotal(producedVariantId);
                    }
                    refreshSelectionState();
                });
            }

            function addConsumptionRow(producedVariantId, values = {}) {
                const list = document.querySelector(`[data-consumption-list="${producedVariantId}"]`);
                if (!list) return;

                const rowId = `${Date.now()}_${Math.floor(Math.random() * 1000000)}`;
                const selectedId = Number(values.consumed_variant_id || 0);
                const selectedVariant = consumableVariants.find((item) => Number(item.id) === selectedId);
                const defaultCost = Number(values.unit_cost || selectedVariant?.default_price || 0).toFixed(2);
                const defaultQty = Number(values.quantity || 1).toFixed(2);

                const wrapper = document.createElement('div');
                wrapper.className = 'row g-2 align-items-center border rounded p-2 m-0';
                wrapper.dataset.consumptionRow = rowId;
                wrapper.innerHTML = `
                    <div class="col-12 col-md-6">
                        <label class="form-label small mb-1">Consumible</label>
                        <select class="form-control border p-2 production-consumed-variant js-consumable-select" data-produced-variant-id="${producedVariantId}">
                            ${buildConsumableOptions(selectedId)}
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label small mb-1">Cantidad</label>
                        <input type="number" min="0.01" step="0.01" class="form-control border p-2 production-consumed-qty" value="${defaultQty}" data-produced-variant-id="${producedVariantId}" placeholder="Cant.">
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label small mb-1">Costo unitario</label>
                        <input type="number" min="0.01" step="0.01" class="form-control border p-2 production-consumed-cost" value="${defaultCost}" data-produced-variant-id="${producedVariantId}" placeholder="Costo unitario ${baseCurrencyCode}">
                    </div>
                    <div class="col-12 col-md-1 text-end">
                        <button type="button" class="btn btn-outline-danger btn-sm mb-0" data-remove-consumption="${rowId}">X</button>
                    </div>
                `;

                list.appendChild(wrapper);

                const selectElement = wrapper.querySelector('.js-consumable-select');
                initConsumableSelect2(selectElement);
            }

            function updateSummaryPills() {
                const selectedProductIds = [...new Set(itemsSelected.map(item => item.product_id))];
                const totalUnits = itemsSelected.reduce((sum, item) => sum + Number(item.quantity || 0), 0);
                const totalAmount = itemsSelected.reduce((sum, item) => sum + (Number(item.quantity || 0) * Number(item.price || 0)), 0);

                summarySelectedProducts.textContent = `Productos: ${selectedProductIds.length}`;
                summarySelectedVariants.textContent = `Variantes: ${itemsSelected.length}`;
                summaryTotalUnits.textContent = `Unidades: ${totalUnits}`;
                summaryTotalAmount.textContent = `Monto estimado: ${totalAmount.toFixed(2)} ${baseCurrencyCode}`;
            }

            function collectSelectedItems() {
                const selected = [];
                document.querySelectorAll('.purchase-variant-checkbox:checked').forEach((checkbox) => {
                    const variantId = Number(checkbox.dataset.variantId || 0);
                    const productId = Number(checkbox.dataset.productId || 0);
                    const productName = checkbox.dataset.productName || '';
                    const variantSize = checkbox.dataset.variantSize || '';
                    const productImage = checkbox.dataset.productImage || '/assets/img/shopix5.png';

                    const qtyInput = document.querySelector(`.purchase-qty[data-variant-id="${variantId}"]`);
                    const priceInput = document.querySelector(`.purchase-price[data-variant-id="${variantId}"]`);
                    const currencyInput = document.querySelector(`.purchase-currency[data-variant-id="${variantId}"]`);
                    const quantity = Number(qtyInput?.value || 0);
                    const price = Number(priceInput?.value || 0);
                    const inputCurrency = (currencyInput?.value || baseCurrencyCode).toUpperCase();
                    const basePrice = toBaseCurrency(price, inputCurrency);

                    if (entryMode === 'purchase' && quantity > 0 && basePrice > 0) {
                        selected.push({
                            product_id: productId,
                            name: productName,
                            variant: {
                                id: variantId,
                                size: variantSize,
                                product_image: productImage,
                            },
                            quantity,
                            price: basePrice,
                            currency: inputCurrency,
                            providers: [],
                        });
                    }

                    if (entryMode === 'production' && quantity > 0) {
                        const rows = document.querySelectorAll(`[data-consumption-list="${variantId}"] [data-consumption-row]`);
                        const consumptions = [];
                        let lineCost = 0;

                        rows.forEach((row) => {
                            const consumedVariantInput = row.querySelector('.production-consumed-variant');
                            const consumedQtyInput = row.querySelector('.production-consumed-qty');
                            const consumedCostInput = row.querySelector('.production-consumed-cost');

                            const consumedVariantId = Number(consumedVariantInput?.value || 0);
                            const consumedQty = Number(consumedQtyInput?.value || 0);
                            const consumedUnitCost = Number(consumedCostInput?.value || 0);

                            if (consumedVariantId > 0 && consumedQty > 0 && consumedUnitCost > 0) {
                                consumptions.push({
                                    consumed_variant_id: consumedVariantId,
                                    quantity: consumedQty,
                                    unit_cost: consumedUnitCost,
                                });
                                lineCost += consumedQty * consumedUnitCost;
                            }
                        });

                        if (consumptions.length > 0 && lineCost > 0) {
                            selected.push({
                                product_id: productId,
                                name: productName,
                                variant: {
                                    id: variantId,
                                    size: variantSize,
                                    product_image: productImage,
                                },
                                quantity,
                                price: lineCost / quantity,
                                currency: baseCurrencyCode,
                                providers: [],
                                production_consumptions: consumptions,
                            });
                        }
                    }
                });

                return selected;
            }

            function updateLineTotal(variantId) {
                const qtyInput = document.querySelector(`.purchase-qty[data-variant-id="${variantId}"]`);
                const priceInput = document.querySelector(`.purchase-price[data-variant-id="${variantId}"]`);
                const currencyInput = document.querySelector(`.purchase-currency[data-variant-id="${variantId}"]`);
                const lineTotal = document.querySelector(`[data-line-total="${variantId}"]`);
                if (!qtyInput || !priceInput || !lineTotal) return;

                let subtotal = 0;
                if (entryMode === 'production') {
                    const rows = document.querySelectorAll(`[data-consumption-list="${variantId}"] [data-consumption-row]`);
                    rows.forEach((row) => {
                        const qty = Number(row.querySelector('.production-consumed-qty')?.value || 0);
                        const unitCost = Number(row.querySelector('.production-consumed-cost')?.value || 0);
                        subtotal += qty * unitCost;
                    });
                } else {
                    const baseUnit = toBaseCurrency(Number(priceInput.value || 0), currencyInput?.value || baseCurrencyCode);
                    subtotal = Number(qtyInput.value || 0) * baseUnit;
                }

                lineTotal.textContent = `Subtotal: ${subtotal.toFixed(2)} ${baseCurrencyCode}`;
            }

            function refreshSelectionState() {
                itemsSelected = collectSelectedItems();
                toStep2.disabled = itemsSelected.length === 0;
                updateSummaryPills();
            }

            document.querySelectorAll('.purchase-variant-checkbox').forEach((checkbox) => {
                checkbox.addEventListener('change', () => {
                    const variantId = checkbox.dataset.variantId;
                    const qtyInput = document.querySelector(`.purchase-qty[data-variant-id="${variantId}"]`);
                    const priceInput = document.querySelector(`.purchase-price[data-variant-id="${variantId}"]`);
                    const currencyInput = document.querySelector(`.purchase-currency[data-variant-id="${variantId}"]`);

                    const isChecked = checkbox.checked;
                    if (qtyInput) qtyInput.disabled = !isChecked;
                    if (priceInput) priceInput.disabled = entryMode === 'production' ? true : !isChecked;
                    if (currencyInput) currencyInput.disabled = entryMode === 'production' ? true : !isChecked;

                    const productionWrapper = document.querySelector(`[data-production-wrapper="${variantId}"]`);
                    if (productionWrapper) {
                        productionWrapper.classList.toggle('d-none', !(entryMode === 'production' && isChecked));
                    }

                    if (!isChecked) {
                        if (qtyInput) qtyInput.value = '';
                        updateLineTotal(variantId);
                    }

                    refreshSelectionState();
                });
            });

            document.querySelectorAll('.purchase-qty, .purchase-price, .purchase-currency').forEach((input) => {
                input.addEventListener('input', () => {
                    const variantId = input.dataset.variantId;
                    updateLineTotal(variantId);
                    refreshSelectionState();
                });
            });

            document.addEventListener('click', (event) => {
                const addButton = event.target.closest('[data-add-consumption]');
                if (addButton) {
                    const variantId = addButton.dataset.addConsumption;
                    addConsumptionRow(variantId);
                    updateLineTotal(variantId);
                    refreshSelectionState();
                    return;
                }

                const removeButton = event.target.closest('[data-remove-consumption]');
                if (removeButton) {
                    const rowId = removeButton.dataset.removeConsumption;
                    const row = document.querySelector(`[data-consumption-row="${rowId}"]`);
                    const variantId = row?.querySelector('.production-consumed-variant')?.dataset.producedVariantId;
                    row?.remove();
                    if (variantId) {
                        updateLineTotal(variantId);
                    }
                    refreshSelectionState();
                }
            });

            document.addEventListener('input', (event) => {
                if (!event.target.classList.contains('production-consumed-variant')
                    && !event.target.classList.contains('production-consumed-qty')
                    && !event.target.classList.contains('production-consumed-cost')) {
                    return;
                }

                const producedVariantId = event.target.dataset.producedVariantId;
                if (event.target.classList.contains('production-consumed-variant')) {
                    const variantId = Number(event.target.value || 0);
                    const selectedVariant = consumableVariants.find((item) => Number(item.id) === variantId);
                    const costInput = event.target.closest('[data-consumption-row]')?.querySelector('.production-consumed-cost');
                    if (costInput && selectedVariant && Number(costInput.value || 0) <= 0) {
                        costInput.value = Number(selectedVariant.default_price || 0).toFixed(2);
                    }
                }

                if (producedVariantId) {
                    updateLineTotal(producedVariantId);
                }
                refreshSelectionState();
            });

            entryModeSwitch.addEventListener('change', () => {
                const selectedMode = document.querySelector('input[name="entry_mode"]:checked');
                entryMode = selectedMode?.value || 'purchase';
                refreshModeUI();
                refreshSelectionState();
            });

            toStep2.addEventListener('click', function () {
                const checked = Array.from(document.querySelectorAll('.purchase-variant-checkbox:checked'));
                if (checked.length === 0) {
                    alert('Debes seleccionar al menos una variante.');
                    return;
                }

                for (const checkbox of checked) {
                    const variantId = checkbox.dataset.variantId;
                    const qty = Number(document.querySelector(`.purchase-qty[data-variant-id="${variantId}"]`)?.value || 0);
                    const price = Number(document.querySelector(`.purchase-price[data-variant-id="${variantId}"]`)?.value || 0);
                    if (entryMode === 'purchase' && (qty <= 0 || price <= 0)) {
                        alert('Cada variante seleccionada debe tener cantidad y costo mayor a 0.');
                        return;
                    }

                    if (entryMode === 'production' && qty <= 0) {
                        alert('Cada variante seleccionada debe tener cantidad producida mayor a 0.');
                        return;
                    }
                }

                itemsSelected = collectSelectedItems();
                if (itemsSelected.length === 0) {
                    alert(entryMode === 'production'
                        ? 'Debes cargar consumibles válidos para las variantes seleccionadas.'
                        : 'No hay variantes válidas para continuar.');
                    return;
                }

                step1.classList.add('d-none');
                step2.classList.remove('d-none');
                setActiveStep(2);
            });

            backToStep1.addEventListener('click', function () {
                step2.classList.add('d-none');
                step1.classList.remove('d-none');
                setActiveStep(1);
            });

            providerInput.addEventListener('input', function () {
                toStep3.disabled = entryMode === 'production'
                    ? (!purchaseDateInput.value || !warehouseIdInput.value)
                    : (providerInput.value.trim() === '' || providerRifInput.value.trim() === '' || supplierInvoiceNumberInput.value.trim() === '' || supplierInvoiceControlNumberInput.value.trim() === '' || !supplierInvoiceDateInput.value || !purchaseDateInput.value || !warehouseIdInput.value);
            });

            providerRifInput.addEventListener('input', function () {
                toStep3.disabled = entryMode === 'production'
                    ? (!purchaseDateInput.value || !warehouseIdInput.value)
                    : (providerInput.value.trim() === '' || providerRifInput.value.trim() === '' || supplierInvoiceNumberInput.value.trim() === '' || supplierInvoiceControlNumberInput.value.trim() === '' || !supplierInvoiceDateInput.value || !purchaseDateInput.value || !warehouseIdInput.value);
            });

            supplierInvoiceNumberInput.addEventListener('input', function () {
                toStep3.disabled = entryMode === 'production'
                    ? (!purchaseDateInput.value || !warehouseIdInput.value)
                    : (providerInput.value.trim() === '' || providerRifInput.value.trim() === '' || supplierInvoiceNumberInput.value.trim() === '' || supplierInvoiceControlNumberInput.value.trim() === '' || !supplierInvoiceDateInput.value || !purchaseDateInput.value || !warehouseIdInput.value);
            });

            supplierInvoiceControlNumberInput.addEventListener('input', function () {
                toStep3.disabled = entryMode === 'production'
                    ? (!purchaseDateInput.value || !warehouseIdInput.value)
                    : (providerInput.value.trim() === '' || providerRifInput.value.trim() === '' || supplierInvoiceNumberInput.value.trim() === '' || supplierInvoiceControlNumberInput.value.trim() === '' || !supplierInvoiceDateInput.value || !purchaseDateInput.value || !warehouseIdInput.value);
            });

            supplierInvoiceDateInput.addEventListener('change', function () {
                toStep3.disabled = entryMode === 'production'
                    ? (!purchaseDateInput.value || !warehouseIdInput.value)
                    : (providerInput.value.trim() === '' || providerRifInput.value.trim() === '' || supplierInvoiceNumberInput.value.trim() === '' || supplierInvoiceControlNumberInput.value.trim() === '' || !supplierInvoiceDateInput.value || !purchaseDateInput.value || !warehouseIdInput.value);
            });

            purchaseDateInput.addEventListener('change', function () {
                toStep3.disabled = entryMode === 'production'
                    ? (!purchaseDateInput.value || !warehouseIdInput.value)
                    : (providerInput.value.trim() === '' || !purchaseDateInput.value || !warehouseIdInput.value);
            });

            warehouseIdInput.addEventListener('change', function () {
                toStep3.disabled = entryMode === 'production'
                    ? (!purchaseDateInput.value || !warehouseIdInput.value)
                    : (providerInput.value.trim() === '' || !purchaseDateInput.value || !warehouseIdInput.value);
            });

            toStep3.addEventListener('click', function () {
                const providerName = providerInput.value.trim();
                const providerRif = providerRifInput.value.trim();
                const supplierInvoiceNumber = supplierInvoiceNumberInput.value.trim();
                const supplierInvoiceControlNumber = supplierInvoiceControlNumberInput.value.trim();
                const supplierInvoiceDate = supplierInvoiceDateInput.value;

                if (entryMode === 'purchase' && !providerName) {
                    alert('Debes indicar el proveedor principal.');
                    return;
                }

                if (entryMode === 'purchase' && !providerRif) {
                    alert('Debes indicar el RIF del proveedor.');
                    return;
                }

                if (entryMode === 'purchase' && !supplierInvoiceNumber) {
                    alert('Debes indicar el número de factura del proveedor.');
                    return;
                }

                if (entryMode === 'purchase' && !supplierInvoiceControlNumber) {
                    alert('Debes indicar el número de control del proveedor.');
                    return;
                }

                if (entryMode === 'purchase' && !supplierInvoiceDate) {
                    alert('Debes indicar la fecha de la factura del proveedor.');
                    return;
                }

                const purchaseDate = purchaseDateInput.value;
                if (!purchaseDate) {
                    alert('Debes indicar la fecha de compra.');
                    return;
                }

                if (!warehouseIdInput.value) {
                    alert('Debes seleccionar un almacén destino.');
                    return;
                }

                itemsSelected = itemsSelected.map(item => ({ ...item, providers: entryMode === 'purchase' ? [providerName] : [] }));

                const providerContainer = document.getElementById('providerContainer');
                providerContainer.innerHTML = '';

                const table = document.createElement('table');
                table.className = 'table table-bordered table-sm bg-white';
                table.innerHTML = `
                  <thead>
                    <tr>
                                            <th>Imagen</th>
                      <th>Producto</th>
                      <th>Variante</th>
                      <th class="text-end">Cantidad</th>
                      <th class="text-end">Costo</th>
                      <th class="text-end">Subtotal</th>
                    </tr>
                  </thead>
                  <tbody id="purchase-summary-body"></tbody>
                `;

                const tbody = table.querySelector('#purchase-summary-body');
                let totalUnits = 0;
                let totalAmount = 0;

                itemsSelected.forEach((data) => {
                    const subtotal = Number(data.quantity || 0) * Number(data.price || 0);
                    totalUnits += Number(data.quantity || 0);
                    totalAmount += subtotal;

                    const row = document.createElement('tr');
                                        const imageSrc = data.variant?.product_image || '/assets/img/shopix5.png';
                    row.innerHTML = `
                                            <td><img src="${imageSrc}" alt="${data.name || 'Producto'}" style="width:48px; height:48px; object-fit:cover; border-radius:8px;"></td>
                      <td>${data.name || 'Sin nombre'}</td>
                      <td>${data.variant?.size || 'Sin variante'}</td>
                      <td class="text-end">${data.quantity || 0}</td>
                      <td class="text-end">${Number(data.price || 0).toFixed(2)} ${baseCurrencyCode}</td>
                      <td class="text-end">${subtotal.toFixed(2)} ${baseCurrencyCode}</td>
                    `;
                    tbody.appendChild(row);
                });

                providerContainer.appendChild(table);

                const finalSummaryText = document.getElementById('finalSummaryText');
                const warehouseText = warehouseIdInput.options[warehouseIdInput.selectedIndex]?.text || '';
                finalSummaryText.textContent = `Almacén: ${warehouseText} | Proveedor: ${providerName} | RIF: ${providerRif} | Factura: ${supplierInvoiceNumber} | Control: ${supplierInvoiceControlNumber} | Fecha factura: ${supplierInvoiceDate} | Fecha entrada: ${purchaseDate} | Unidades: ${totalUnits} | Monto total: ${totalAmount.toFixed(2)} ${baseCurrencyCode}`;
                if (entryMode === 'production') {
                    finalSummaryText.textContent = `Almacén: ${warehouseText} | Modo: Producción interna | Fecha: ${purchaseDate} | Unidades producidas: ${totalUnits} | Costo total consumibles: ${totalAmount.toFixed(2)} ${baseCurrencyCode}`;
                }

                step3.classList.remove('d-none');
                step2.classList.add('d-none');
                setActiveStep(3);
            });

            backToStep2.addEventListener('click', function () {
                step2.classList.remove('d-none');
                step3.classList.add('d-none');
                setActiveStep(2);
            });

            const createOrderButton = document.getElementById('createOrder');
            createOrderButton.addEventListener('click', async function () {
                if (itemsSelected.length === 0) {
                    alert('No hay productos válidos para registrar la compra.');
                    return;
                }

                const providerName = providerInput.value.trim();
                const providerRif = providerRifInput.value.trim();
                const supplierInvoiceNumber = supplierInvoiceNumberInput.value.trim();
                const supplierInvoiceControlNumber = supplierInvoiceControlNumberInput.value.trim();
                const supplierInvoiceDate = supplierInvoiceDateInput.value;

                if (!purchaseDateInput.value) {
                    alert('Debes indicar la fecha de compra.');
                    return;
                }

                if (!warehouseIdInput.value) {
                    alert('Debes seleccionar un almacén destino.');
                    return;
                }

                createOrderButton.disabled = true;
                createOrderButton.textContent = 'Guardando...';

                try {
                    const formData = new FormData();
                    formData.append('_token', document.querySelector('input[name="_token"]').value);
                    formData.append('entry_mode', entryMode);
                    formData.append('purchase_date', purchaseDateInput.value);
                    formData.append('warehouse_id', Number(warehouseIdInput.value));
                    formData.append('provider_name', providerName);
                    formData.append('provider_rif', providerRif);
                    formData.append('supplier_invoice_number', supplierInvoiceNumber);
                    formData.append('supplier_invoice_control_number', supplierInvoiceControlNumber);
                    formData.append('supplier_invoice_date', supplierInvoiceDate);
                    if (supplierInvoiceFileInput.files && supplierInvoiceFileInput.files[0]) {
                        formData.append('supplier_invoice_file', supplierInvoiceFileInput.files[0]);
                    }

                    itemsSelected.forEach((item, index) => {
                        formData.append(`itemsSelected[${index}][product_id]`, item.product_id);
                        formData.append(`itemsSelected[${index}][name]`, item.name || '');
                        formData.append(`itemsSelected[${index}][quantity]`, item.quantity);
                        formData.append(`itemsSelected[${index}][price]`, item.price);
                        formData.append(`itemsSelected[${index}][currency]`, item.currency || baseCurrencyCode);
                        formData.append(`itemsSelected[${index}][variant][id]`, item.variant?.id || '');
                        formData.append(`itemsSelected[${index}][variant][size]`, item.variant?.size || '');
                        formData.append(`itemsSelected[${index}][variant][product_image]`, item.variant?.product_image || '');
                    });

                    const response = await fetch('/api/create-order', {
                        method: 'POST',
                        headers: {
                            'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                        },
                        body: formData,
                    });

                    const data = await response.json();

                    if (!response.ok) {
                        throw new Error(data.error || data.message || `Error en la respuesta: ${response.statusText}`);
                    }

                    alert(data.message || 'Compra creada correctamente');
                    window.location.href = '/purchase-orders';
                } catch (error) {
                    console.log("err", error)
                    alert(error.message || 'No se pudo registrar la compra.');
                } finally {
                    createOrderButton.disabled = false;
                    createOrderButton.textContent = 'Registrar entrada';
                }
            })

            refreshModeUI();
            updateSummaryPills();
        });

        function filterProducts() {
            const searchInput = document.getElementById('searchInput');
            const filter = searchInput.value.toLowerCase();
            const productItems = document.querySelectorAll('.product-item');

            productItems.forEach(item => {
                const name = item.getAttribute('data-name');
                if (name.includes(filter)) {
                    item.style.display = 'block'; // Mostrar si coincide
                } else {
                    item.style.display = 'none'; // Ocultar si no coincide
                }
            });
        }

    </script>
@endpush
