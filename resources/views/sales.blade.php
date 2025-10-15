  <style>
    .step {
        display: none;
    }
    .step:not(.d-none) {
        display: block;
    }
  </style>
  @extends('layouts.app')

    @section('title', 'Categorías')

    @section('content')
    <div class="mx-5 d-flex justify-content-between gap-4">
        <div class="w-75">
            <h1>Flujo de Venta</h1>
            {{$dollarRate}}
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
                            @php
                                switch ($category->name) {
                                    case 'Chemises':
                                        $icon = 'accessibility_new';
                                        break;
                                    case 'Pantalones':
                                        $icon = 'vignette';
                                        break;
                                    case 'Camisas':
                                        $icon = 'hiking';
                                        break;
                                    case 'Franelas':
                                        $icon = 'view_stream';
                                        break;
                                    default:
                                        $icon = 'category'; // ícono por defecto
                                }
                            @endphp

                            <div class="category-item flex-shrink-0" style="width: 200px; scroll-snap-align: start;" data-category-name="{{ $category->name }}" data-category="{{ $category->id }}" onclick="filterProductsByCategory('{{ $category->id }}')">
                                <a href="javascript:void(0)" class="text-decoration-none category-filter">
                                    <div class="card h-100">
                                        <div class="card-header mx-3 p-3 text-center">
                                            <div class="icon icon-shape icon-lg bg-gradient-dark shadow text-center border-radius-lg">
                                                <i class="material-symbols-rounded opacity-10">{{ $icon }}</i>
                                            </div>
                                        </div>
                                        <div class="card-body pt-0 p-3 text-center">
                                            <h6 class="text-center mb-0 opacity-9">{{ $category->name }}</h6>
                                        </div>
                                    </div>
                                </a>
                            </div>
                        @endforeach
                    </div>
                    <div class="mb-3">
                        <input 
                            type="text" 
                            id="searchInput" 
                            class="form-control border border-1 p-2 bg-white" 
                            placeholder="Buscar producto..." 
                            onkeyup="filterProducts()">
                    </div>
                    <div id="itemSelector" class="row row-cols-1 row-cols-md-3 g-3">
                        @foreach($productItems as $item)
                            <div class="col product-item" data-category="{{ $item->category_id }}" data-name="{{ strtolower($item->name) }}">
                                <div class="card h-100">
                                    <div class="card-body">
                                        <div class="d-flex gap-4 align-items-center">
                                            <!-- Contenedor de la imagen -->
                                            <a href="{{ route('productItem', $item->id) }}" class="icon icon-shape icon-xl shadow bg-transparent text-center border border-1 border-black text-info border-radius-lg flex-shrink-0" style="width: 70px; height: 70px;">
                                                @if(isset($item->images) && count($item->images) > 0)
                                                    <img src="{{ asset('storage/' . $item->images[0]->path) }}" alt="Imagen del producto" style="width: 100%; height: 100%; object-fit: cover; border-radius: inherit;">
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
                                            <div class="d-flex gap-5 justify-content-between align-items-center">
                                                <label for="variant_{{ $variant->id }}" class="d-block mt-2 variant-label" style="cursor: pointer;" data-product-name="{{ $item->name }}">
                                                    <input type="checkbox" class="form-check-input me-2 variant-checkbox" id="variant_{{ $variant->id }}" name="selectedVariants[]" value="{{ $variant->id }}"
                                                    data-price="{{ $variant->price }}" data-stock="{{ $variant->stock }}"
                                                    data-product-name="{{ $item->name }}"
                                                    data-size="{{ $variant->size }}">
                                                    <span>Talla: {{$variant->size}} | {{ $variant->price }} USD | Stock: {{ $variant->stock }}</span>
                                                    <i class="check-icon d-none ms-2 text-success fas fa-check"></i>
                                                </label>
                                                <i class="material-symbols-rounded text-info" style="cursor: pointer"
                                                    onclick="showProductDetails('{{ $item->name }}', '{{ $item->description }}', '{{ isset($item->images) && count($item->images) > 0 ? asset('storage/' . $item->images[0]->path) : '' }}', '{{ $variant->price }}', '{{ $variant->stock }}', '{{ $variant->size }}')">
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
                        <strong>Total a pagar: </strong><span id="totalAmountBsValue">0.00</span>Bs 
                    </div>
                    <div class="mb-3">
                        {{-- <strong>Tasa BCV: </strong><span id="dollarRate" data-rate="{{ number_format($dollarRate->rate, 2, '.', '') }}">{{ number_format($dollarRate->rate, 2) }} Bs.</span> --}}
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
                                                    <img src="{{ asset('storage/' . $qr) }}" alt="QR" style="max-width: 70px; max-height: 70px; cursor: pointer;"
                                                        onclick="showQrModal('{{ asset('storage/' . $qr) }}')">
                                                @endif
                                            @endif
                                            <div>
                                                <strong>{{ $method->name }}</strong>
                                                @if ($method->admin_name) - {{ $method->admin_name }} @endif
                                                @if ($method->bank) ({{ $method->bank }}) @endif
                                                @if ($method->dni)
                                                    <div><small>DNI/Correo: {{ $method->dni }}</small></div>
                                                @endif
                                                <div id="paymentFields_{{ $method->id }}" class="d-none d-flex flex-row gap-2 align-items-center">
                                                    <label for="amount_{{ $method->id }}">Monto:</label>
                                                    <input type="number" step="0.01" min="0" class="form-control payment-input border border-1 p-2" 
                                                        id="amount_{{ $method->id }}" 
                                                        data-method-id="{{ $method->id }}" 
                                                        data-currency="{{ $currencyCode }}" 
                                                        oninput="updatePayment(this)">

                                                    @php
                                                        $noReference = in_array(strtolower($method->name), ['efectivo', 'punto de venta', 'pago movil']);
                                                    @endphp

                                                    @if (!$noReference)
                                                        <label for="reference_{{ $method->id }}">Referencia:</label>
                                                        <input type="text" class="form-control payment-reference-input border border-1 p-2" 
                                                            id="reference_{{ $method->id }}" 
                                                            data-method-id="{{ $method->id }}" 
                                                            oninput="updatePayment(this)">
                                                    @else
                                                        <input type="hidden" id="reference_{{ $method->id }}" value="00">
                                                    @endif
                                                </div>
                                            </div>
                                        </div>
                                        <div class="mt-2">
                                            <input type="checkbox" class="form-check-input payment-method-checkbox" id="method_{{ $method->id }}" 
                                                data-method-id="{{ $method->id }}" data-currency="{{ $currencyCode }}" 
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
                    <div class="d-flex justify-content-between w-100 align-items-center">
                        <button type="button" class="btn btn-secondary mt-3" id="backToStep1">Atrás</button>
                        <button type="button" class="btn btn-info mt-3" id="toStep3" disabled>Siguiente</button>
                    </div>
                </div>

                <div id="step3" class="step d-none">
                    <h4>Paso 3: Confirmación</h4>
                    <p>Resumen de la compra y confirmación.</p>

                    <div id="summaryContainer" class="mt-3 card p-4"></div> <!-- Aquí se insertará el resumen -->
                    <span class="text-danger paymentMessage"></span>

                    <div class="d-flex justify-content-between w-100 align-items-center">
                        <button type="button" class="btn btn-secondary mt-3" id="backToStep2">Atrás</button>
                        <button type="button" class="btn btn-success mt-3" id="confirmPurchase">Confirmar</button>
                    </div>
                </div>

            </form>
        </div>
        <div class="w-25 card p-4 h-100" id="cart">
            <h1>Carrito</h1>
            <ul id="cartList" class="list-group"></ul>
            <div class="mt-3">
                <strong>Total:</strong> $<span id="cartTotal">0.00</span>
            </div>
            <div class="mt-3">
                <strong>Total Bs:</strong>Bs<span id="cartTotalBs">0.00</span>
            </div>
            <div class="d-flex justify-content-end">
                <button type="button" class="btn btn-dark mt-3" id="toStep2" disabled>Siguiente</button>
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
                        <p><strong>Talla:</strong> <span id="modalProductSize"></span></p>
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
    @endsection

@push('scripts')
<!-- Core JS Files -->
<script src="{{ asset('assets/js/core/popper.min.js') }}"></script>
<script src="{{ asset('assets/js/core/bootstrap.min.js') }}"></script>
    <script>
        var selectedItems = [];
        var totalAmount = 0;
        let payments = []; 
        let totalPaid = 0; 

        const dollar = @json($dollarRate);
        const dollarRate = Number(dollar.rate);
        
        const customerId = document.getElementById('customerId').dataset.rate; // Asegúrate de que esta variable esté definida correctamente
        document.addEventListener('DOMContentLoaded', function () {
            // Escuchar todos los checkboxes
            const checkboxes = document.querySelectorAll('input[name="selectedVariants[]"]');
            checkboxes.forEach(checkbox => {
                checkbox.addEventListener('change', handleCheckboxChange);
            });
        });
        function handleCheckboxChange(e) {
            const checkbox = e.target;
            const id = checkbox.value;
            const productName = checkbox.getAttribute('data-product-name');
            const productSize = checkbox.getAttribute('data-size');
            const stock = parseInt(checkbox.getAttribute('data-stock')) || 0; // Asegúrate de que sea un número
            const price = parseFloat(checkbox.getAttribute('data-price')) || 0; // Asegúrate de que sea un número

            if (checkbox.checked) {
                selectedItems.push({
                    id,
                    productName,
                    productSize,
                    price,
                    quantity: 1,
                    stock
                });
                totalAmount += price;
            } else {
                const removedItem = selectedItems.find(item => item.id === id);
                if (removedItem) totalAmount -= removedItem.price * removedItem.quantity;

                selectedItems = selectedItems.filter(item => item.id !== id);
            }

            renderCart();
        }

        function updateQuantity(id, newQty) {
            const item = selectedItems.find(item => item.id === id);
            if (item) {
                newQty = parseInt(newQty) || 1; // Asegúrate de que sea un número válido
                if (newQty < 1) newQty = 1;
                if (newQty > item.stock) newQty = item.stock;

                totalAmount -= item.price * item.quantity; // Quita el anterior
                item.quantity = newQty;
                totalAmount += item.price * newQty;        // Agrega el nuevo
                renderCart();
            }
        }

        function renderCart() {
            const cartList = document.getElementById('cartList');
            const cartTotal = document.getElementById('cartTotal');
            const cartTotalBs = document.getElementById('cartTotalBs');
            const totalAmountValue = document.getElementById('totalAmountValue');
            const totalAmountBsValue = document.getElementById('totalAmountBsValue');
            const toStep2Btn = document.getElementById('toStep2');
            cartList.innerHTML = '';

            selectedItems.forEach(item => {
                const li = document.createElement('li');
                li.className = 'list-group-item d-flex justify-content-between align-items-start flex-column mt-2';

                const textDiv = document.createElement('div');
                textDiv.innerHTML = `<strong>${item.productName}</strong><br>
                <strong>Talla: ${item.productSize}</strong><br>
                Precio: ${item.price.toFixed(2)} USD | Stock: ${item.stock}`;

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
            // Obtener la tasa del dólar desde el DOM

            const dollar = @json($dollarRate);
            const dollarRate = Number(dollar.rate);

            cartTotal.textContent = totalAmount.toFixed(2); 
            cartTotalBs.textContent = (totalAmount * dollarRate ).toFixed(2); 
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

            renderCart();
        }
        function filterProductsByCategory(categoryId) {
            const productItems = document.querySelectorAll('.product-item');

            productItems.forEach(item => {
                const itemCategory = item.getAttribute('data-category');
                if (categoryId === 'all' || itemCategory === categoryId) {
                    item.style.display = 'block'; // Mostrar si coincide con la categoría seleccionada
                } else {
                    item.style.display = 'none'; // Ocultar si no coincide
                }
            });

            // Limpiar el campo de búsqueda de productos al cambiar de categoría
            document.getElementById('searchInput').value = '';
        }
        
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
        
        function togglePaymentFields(checkbox) {
            const methodId = checkbox.dataset.methodId;
            const paymentFields = document.getElementById(`paymentFields_${methodId}`);

            if (checkbox.checked) {
                paymentFields.classList.remove('d-none'); // Mostrar los campos de monto y referencia
                payments.push({
                    methodId: methodId,
                    currency: checkbox.dataset.currency,
                    amount: 0,
                    reference: ''
                });
            } else {
                // Limpiar los valores de los inputs
                const amountInput = document.getElementById(`amount_${methodId}`);
                const referenceInput = document.getElementById(`reference_${methodId}`);

                if (amountInput) amountInput.value = ''; // Limpiar el campo de monto
                if (referenceInput) referenceInput.value = ''; // Limpiar el campo de referencia

                paymentFields.classList.add('d-none'); // Ocultar los campos
                payments = payments.filter(payment => payment.methodId !== methodId); // Eliminar el pago del arreglo
            }

            console.log(payments); // Verificar el arreglo de pagos en la consola
            validatePaymentDetails(); // Validar los detalles de pago
        }

        function updatePayment(input) {
            const methodId = input.dataset.methodId;
            const currency = input.dataset.currency;
            const payment = payments.find(payment => payment.methodId === methodId);

            const dollar = @json($dollarRate);
            const dollarRate = Number(dollar.rate);

            if (payment) {
                if (input.classList.contains('payment-input')) {
                    let amount = parseFloat(input.value) || 0;

                    // Si la moneda es bolívares, convertir a dólares
                    if (currency === 'BS') {
                        amount = amount / dollarRate;
                    }

                    payment.amount = amount;
                } else if (input.classList.contains('payment-reference-input')) {
                    payment.reference = input.value;
                }
            }

            console.log(payments);
            validatePaymentDetails();
        }
        function validatePaymentDetails() {
            totalPaid = payments.reduce((sum, payment) => sum + payment.amount, 0);
            const totalPaidSpan = document.getElementById('totalPaid');
            const paymentMessages = document.querySelectorAll('.paymentMessage');
            const toStep3Button = document.getElementById('toStep3');

            // Mostrar el total ingresado
            totalPaidSpan.textContent = totalPaid.toFixed(2);

            let messageText = '';
            let messageClass = '';
            let disableStep3 = false;

            // Verificar si hay referencias vacías (solo si el método requiere referencia)
            const hasEmptyReference = payments.some(payment => {
                const methodElement = document.querySelector(`[data-method-id="${payment.methodId}"]`);
                if (methodElement && methodElement.dataset.currency !== undefined) {
                    const inputReference = document.getElementById(`reference_${payment.methodId}`);
                    return inputReference && inputReference.type !== 'hidden' && (!payment.reference || payment.reference.trim() === '');
                }
                return false;
            });

            if (hasEmptyReference) {
                messageText = `Todos los métodos de pago deben tener una referencia válida.`;
                messageClass = 'text-danger';
                disableStep3 = true;
            } else if (totalPaid < totalAmount) {
                const remaining = (totalAmount - totalPaid).toFixed(2);
                messageText = `Falta por pagar: $${remaining}`;
                messageClass = 'text-danger';
                disableStep3 = true;
            } else if (totalPaid > totalAmount) {
                const change = (totalPaid - totalAmount).toFixed(2);
                messageText = `Debe entregar vuelto: $${change}`;
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
            document.getElementById('step2').classList.add('d-none');
            renderSummary(); // Mostrar el resumen
            document.getElementById('cart').classList.add('d-none');
            document.getElementById('step3').classList.remove('d-none');
            console.log('Resumen:', selectedItems);
            console.log('Pagos:', payments);
        });
        document.getElementById('backToStep2').addEventListener('click', function() {
            document.getElementById('step3').classList.add('d-none');
            document.getElementById('step2').classList.remove('d-none');
            document.getElementById('cart').classList.remove('d-none');

        });
        function renderSummary() {
            const container = document.getElementById('summaryContainer');
            
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
                li.innerText = `${item.productName} - Talla: ${item.productSize} - Cantidad: ${item.quantity} - Subtotal: $${(item.price * item.quantity).toFixed(2)}`;
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
                    const amountInput = document.getElementById(`amount_${payment.methodId}`);
                    const referenceInput = document.getElementById(`reference_${payment.methodId}`);
                    const amount = amountInput?.value || 0;
                    const reference = referenceInput?.value || '';

                    const li = document.createElement('li');
                    li.innerText = `Método: ${payment.currency} - Monto: $${parseFloat(amount).toFixed(2)} - Referencia: ${reference}`;
                    paymentList.appendChild(li);
                });
                container.appendChild(paymentList);
                paymentList.className = 'card p-4 gap-2';
            }
        }
        
        document.getElementById('confirmPurchase').addEventListener('click', function() {
            const authUser = @json($authUser);
            const tenantId = Number(authUser.tenant_id);

            const summary = {
                customerId: customerId,
                items: selectedItems,
                payments: payments,
                tenant_id: tenantId
            };

            // Obtener el token CSRF desde el meta tag o un input hidden
            const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            console.log('Resumen a enviar:', summary);
            return;
            // Enviar la solicitud al servidor
            fetch('/create-sale', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken // Incluir el token CSRF
                },
                body: JSON.stringify(summary) // Convertir el resumen a JSON
            })
            .then(response => {
                if (response.ok) {
                    return response.json(); // Parsear la respuesta como JSON
                } else {
                    throw new Error('Error al confirmar la compra.');
                }
            })
            .then(data => {
                // Manejar la respuesta exitosa
                alert('Compra confirmada con éxito.');
                const link = document.createElement('a');
                link.href = data.pdf_url;
                link.download = ''; // Puedes darle un nombre: 'orden-venta.pdf'
                document.body.appendChild(link);
                link.click();
                document.body.removeChild(link);
                // Redirigir o limpiar el formulario
                window.location.href = '/sales-orders'; // Cambia la ruta según sea necesario
            })
            .catch(error => {
                // Manejar errores
                console.error('Error:', error);
                alert('Error al confirmar la compra.');
            });
        });
    </script>
@endpush