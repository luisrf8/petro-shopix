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

                <div class="d-flex flex-wrap gap-2 mb-3">
                    <div class="summary-pill" id="summary-selected-products">Productos: 0</div>
                    <div class="summary-pill" id="summary-selected-variants">Variantes: 0</div>
                    <div class="summary-pill" id="summary-total-units">Unidades: 0</div>
                    <div class="summary-pill" id="summary-total-amount">Monto estimado: 0.00 USD</div>
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
                                <div id="itemSelector" class="row row-cols-1 row-cols-md-2 g-3">
                    @foreach ($productItems as $item)
                                                @php
                                                    $itemImage = isset($item->images) && count($item->images) > 0
                                                            ? asset('storage/' . $item->images[0]->path)
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
                                                                                    <span>{{ $variant->size }} | Stock: {{ $variant->stock }}</span>
                                                                                </label>
                                                                            </div>

                                                                            <div class="row g-2">
                                                                                <div class="col-6">
                                                                                    <input type="number" min="1" class="form-control purchase-qty" data-variant-id="{{ $variant->id }}" placeholder="Cantidad" disabled>
                                                                                </div>
                                                                                <div class="col-6">
                                                                                    <input type="number" min="0.01" step="0.01" class="form-control purchase-price" data-variant-id="{{ $variant->id }}" placeholder="Costo USD" value="{{ number_format($variant->price, 2, '.', '') }}" disabled>
                                                                                </div>
                                                                            </div>
                                                                            <div class="small text-end text-muted mt-1" data-line-total="{{ $variant->id }}">Subtotal: 0.00 USD</div>
                                                                        </div>
                                                                    @endforeach
                                </div>
                                                        </div>
                        </div>
                    @endforeach
                </div>
                <button type="button" class="btn btn-info mt-3" id="toStep2" disabled>Siguiente</button>
            </div>

                        <div id="step2" class="d-none">
                                <h4>Paso 2: Proveedor y fecha de entrada</h4>
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
                <div class="mb-3">
                    <label for="providerName" class="form-label">Proveedor</label>
                    <input type="text" id="providerName" class="form-control border border-1 p-2" placeholder="Ej: Distribuidora ABC">
                    <small class="text-muted">Si deseas separar órdenes por proveedor, escribe varios nombres separados por coma.</small>
                </div>
                <div class="mb-3">
                    <label for="purchaseDate" class="form-label">Fecha de compra</label>
                    <input type="date" id="purchaseDate" class="form-control border border-1 p-2">
                </div>
                <button type="button" class="btn btn-secondary mt-3" id="backToStep1">Atrás</button>
                <button type="button" class="btn btn-info mt-3" id="toStep3" disabled>Siguiente</button>
            </div>

            <div id="step3" class="d-none">
                <h4>Paso 3: Confirmación</h4>
                <div id="providerContainer"></div>
                <div class="alert alert-info mt-3 mb-0" id="finalSummaryText"></div>
                <button type="button" class="btn btn-secondary mt-3" id="backToStep2">Atrás</button>
                <button type="button" class="btn btn-info mt-3" id="createOrder">Registrar entrada</button>
            </div>
        </form>
    </div>
    @endsection

@push('scripts')
<script src="{{ asset('assets/js/core/popper.min.js') }}"></script>
<script src="{{ asset('assets/js/core/bootstrap.min.js') }}"></script>

<!-- Github buttons -->
<!-- <script async defer src="https://buttons.github.io/buttons.js"></script> -->
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            let itemsSelected = [];
            const toStep2 = document.getElementById('toStep2');
            const step1 = document.getElementById('step1');
            const step2 = document.getElementById('step2');
            const toStep3 = document.getElementById('toStep3');
            const step3 = document.getElementById('step3');
            const backToStep1 = document.getElementById('backToStep1');
            const backToStep2 = document.getElementById('backToStep2');
            const providerInput = document.getElementById("providerName");
            const purchaseDateInput = document.getElementById('purchaseDate');
            const warehouseIdInput = document.getElementById('warehouseId');

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

            function updateSummaryPills() {
                const selectedProductIds = [...new Set(itemsSelected.map(item => item.product_id))];
                const totalUnits = itemsSelected.reduce((sum, item) => sum + Number(item.quantity || 0), 0);
                const totalAmount = itemsSelected.reduce((sum, item) => sum + (Number(item.quantity || 0) * Number(item.price || 0)), 0);

                summarySelectedProducts.textContent = `Productos: ${selectedProductIds.length}`;
                summarySelectedVariants.textContent = `Variantes: ${itemsSelected.length}`;
                summaryTotalUnits.textContent = `Unidades: ${totalUnits}`;
                summaryTotalAmount.textContent = `Monto estimado: ${totalAmount.toFixed(2)} USD`;
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
                    const quantity = Number(qtyInput?.value || 0);
                    const price = Number(priceInput?.value || 0);

                    if (quantity > 0 && price > 0) {
                        selected.push({
                            product_id: productId,
                            name: productName,
                            variant: {
                                id: variantId,
                                size: variantSize,
                                product_image: productImage,
                            },
                            quantity,
                            price,
                            providers: [],
                        });
                    }
                });

                return selected;
            }

            function updateLineTotal(variantId) {
                const qtyInput = document.querySelector(`.purchase-qty[data-variant-id="${variantId}"]`);
                const priceInput = document.querySelector(`.purchase-price[data-variant-id="${variantId}"]`);
                const lineTotal = document.querySelector(`[data-line-total="${variantId}"]`);
                if (!qtyInput || !priceInput || !lineTotal) return;

                const subtotal = Number(qtyInput.value || 0) * Number(priceInput.value || 0);
                lineTotal.textContent = `Subtotal: ${subtotal.toFixed(2)} USD`;
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

                    const isChecked = checkbox.checked;
                    if (qtyInput) qtyInput.disabled = !isChecked;
                    if (priceInput) priceInput.disabled = !isChecked;

                    if (!isChecked) {
                        if (qtyInput) qtyInput.value = '';
                        updateLineTotal(variantId);
                    }

                    refreshSelectionState();
                });
            });

            document.querySelectorAll('.purchase-qty, .purchase-price').forEach((input) => {
                input.addEventListener('input', () => {
                    const variantId = input.dataset.variantId;
                    updateLineTotal(variantId);
                    refreshSelectionState();
                });
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
                    if (qty <= 0 || price <= 0) {
                        alert('Cada variante seleccionada debe tener cantidad y costo mayor a 0.');
                        return;
                    }
                }

                itemsSelected = collectSelectedItems();
                if (itemsSelected.length === 0) {
                    alert('No hay variantes válidas para continuar.');
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
                const providerName = providerInput.value.trim();
                toStep3.disabled = providerName === '' || !purchaseDateInput.value || !warehouseIdInput.value;
            });

            purchaseDateInput.addEventListener('change', function () {
                toStep3.disabled = providerInput.value.trim() === '' || !purchaseDateInput.value || !warehouseIdInput.value;
            });

            warehouseIdInput.addEventListener('change', function () {
                toStep3.disabled = providerInput.value.trim() === '' || !purchaseDateInput.value || !warehouseIdInput.value;
            });

            toStep3.addEventListener('click', function () {
                const providers = providerInput.value
                    .split(',')
                    .map(name => name.trim())
                    .filter(Boolean);

                if (providers.length === 0) {
                    alert('Debes indicar al menos un proveedor.');
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

                itemsSelected = itemsSelected.map(item => ({ ...item, providers }));

                const providerContainer = document.getElementById('providerContainer');
                providerContainer.innerHTML = '';

                const table = document.createElement('table');
                table.className = 'table table-bordered table-sm';
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
                      <td class="text-end">${Number(data.price || 0).toFixed(2)} USD</td>
                      <td class="text-end">${subtotal.toFixed(2)} USD</td>
                    `;
                    tbody.appendChild(row);
                });

                providerContainer.appendChild(table);

                const finalSummaryText = document.getElementById('finalSummaryText');
                const warehouseText = warehouseIdInput.options[warehouseIdInput.selectedIndex]?.text || '';
                finalSummaryText.textContent = `Almacén: ${warehouseText} | Proveedor(es): ${providers.join(', ')} | Fecha: ${purchaseDate} | Unidades: ${totalUnits} | Monto total: ${totalAmount.toFixed(2)} USD`;

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
                if (!providerName) {
                    alert('Debes indicar un proveedor válido.');
                    return;
                }

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
                    const response = await fetch('/api/create-order', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                        },
                        body: JSON.stringify({
                            itemsSelected,
                            purchase_date: purchaseDateInput.value,
                            warehouse_id: Number(warehouseIdInput.value)
                        }),
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
