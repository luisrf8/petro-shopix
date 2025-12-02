<style>
/* Indicador de selección (viñeta) */
.indicator {
    width: 20px;
    height: 20px;
    background-color: transparent;
    border: 2px solid #ccc;
    border-radius: 50%;
    transition: background-color 0.3s, border-color 0.3s;
}

/* Cambiar el estado de la viñeta si está seleccionado */
input[type="checkbox"]:checked + .position-absolute {
    background-color: #26a69a; /* Verde */
    border-color: #26a69a;
}

</style>
@extends('layouts.app')

@section('title', 'Categorías')

@section('content')
    <div class="py-1 px-3 text-end" onclick="getSucursales()">
      <a class="nav-link text-dark" href="/createProduct">
        + Agregar Producto
      </a>
    </div>
    <div class="mx-5">
        <h1>Flujo de Compra</h1>
        <form id="purchaseForm">
            @csrf
            <!-- Paso 1: Selección del Ítem -->
            <div id="step1">
                <h4>Paso 1: Selecciona uno o más productos.</h4>
                <!-- Input de Búsqueda -->
                <div class="mb-3">
                    <input 
                        type="text" 
                        id="searchInput" 
                        class="form-control border border-1 p-2 bg-white" 
                        placeholder="Buscar producto..." 
                        onkeyup="filterProducts()">
                </div>
                <div id="itemSelector" class="row row-cols-1 row-cols-md-3 g-3">
                    @foreach ($productItems as $item)
                        <div class="col position-relative product-item" data-name="{{ strtolower($item->name) }}">
                            <label class="card h-100" for="item_{{ $item->id }}" style="cursor: pointer;">
                                <input type="checkbox" class="form-check-input d-none" id="item_{{ $item->id }}" value="{{ $item->id }}" name="selectedItems[]">
                                <div class="position-absolute top-0 end-0 m-2 indicator" id="indicator_{{ $item->id }}"></div>
                                <div class="card-body">
                                    <h5 class="card-title">{{ $item->name }}</h5>
                                    <p class="card-text">{{ $item->description }}</p>
                                </div>
                            </label>
                        </div>
                    @endforeach
                </div>
                <button type="button" class="btn btn-info mt-3" id="toStep2" disabled>Siguiente</button>
            </div>
            <!-- Paso 2: Selección de Variante y Cantidad -->
            <div id="step2" class="d-none">
                <h4>Paso 2: Selecciona Variante y Cantidad</h4>
                <div id="variantContainer"></div>
                <button type="button" class="btn btn-secondary mt-3" id="toStep1">Atrás</button>
                <button type="button" class="btn btn-info mt-3" id="toStep3" disabled>Siguiente</button>
            </div>

            <!-- Paso 3: Selección de Proveedores -->
            <div id="step3" class="d-none">
                <h4>Paso 3: Selecciona Proveedor</h4>
                <!-- Input para el nombre del proveedor -->
                <div class="mb-3">
                    <label for="providerName" class="form-label">Nombre del Proveedor</label>
                    <input type="text" id="providerName" class="form-control border border-1 p-2" placeholder="Escribe el nombre del proveedor">
                </div>
                <button type="button" class="btn btn-secondary mt-3" id="backToStep2">Atrás</button>
                <button type="button" class="btn btn-info mt-3" id="toStep4" disabled>Siguiente</button>
            </div>
            <!-- Paso 4: Confirmación -->
            <div id="step4" class="d-none">
                <h4>Paso 4: Confirmación</h4>
                <div id="providerContainer"></div>
                <button type="button" class="btn btn-secondary mt-3" id="backToStep3">Atrás</button>
                <button type="button" class="btn btn-info mt-3" id="createOrder">Continuar</button>
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
            var itemsSelected= [];
            const itemSelector = document.getElementById('itemSelector');
            const toStep2 = document.getElementById('toStep2');
            const step1 = document.getElementById('step1');
            const step2 = document.getElementById('step2');
            const toStep1 = document.getElementById('toStep1');
            const backToStep2 = document.getElementById('backToStep2');
            const toStep3 = document.getElementById('toStep3');
            const backToStep3 = document.getElementById('backToStep3');
            const step3 = document.getElementById('step3');
            const supplierSelector = document.getElementById('supplierSelector');
            const step4 = document.getElementById('step4');
            const providerInput = document.getElementById("providerName");

            // Activar botón siguiente en el Paso 1
            document.querySelectorAll('#itemSelector input[type="checkbox"]').forEach((checkbox) => {
                checkbox.addEventListener('change', function () {
                    const checkedBoxes = document.querySelectorAll('#itemSelector input[type="checkbox"]:checked');
                    console.log("hola")
                    console.log("checkboxes", checkedBoxes.length)
                    document.getElementById('toStep2').disabled = checkedBoxes.length === 0; // Habilitar si hay al menos uno seleccionado
                });
            });
            // Transición de pasos
            toStep2.addEventListener('click', function () {
                const selectedItems = Array.from(document.querySelectorAll('#itemSelector input[type="checkbox"]:checked')).map(
                    (checkbox) => checkbox.value
                );
                console.log("selectedItems", selectedItems)
                if (selectedItems.length === 0) {
                    alert('Por favor selecciona al menos un producto');
                    return;
                }
                fetch('api/sales/get-variants', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                    },
                    body: JSON.stringify({ item_ids: selectedItems }),
                })
                .then((response) => {
                    // Verificar si la respuesta tiene un código de estado exitoso
                    if (!response.ok) {
                        throw new Error(`Error en la respuesta: ${response.statusText}`);
                    }
                    return response.json();
                })
                // Loop through variants
                .then((data) => {
                console.log("data", data)
                    const variantContainer = document.getElementById('variantContainer');
                    variantContainer.innerHTML = ''; // Limpiar contenido previo
                    variantContainer.className = 'row row-cols-1 row-cols-md-3 g-3 gap-4';

                    // Mostrar variantes agrupadas por producto
                    data.forEach((product) => {
                        // Crear una tarjeta para cada producto
                        const productCard = document.createElement('div');
                        productCard.classList.add('card', 'mb-4', 'shadow', 'col-4', 'p-0', 'w-20', 'h-100');

                        const productHeader = document.createElement('div');
                        productHeader.classList.add('card-header', 'bg-info', 'text-white', 'fw-bold');
                        productHeader.textContent = `Producto: ${product.product_name}`;
                        productCard.appendChild(productHeader);

                        const productBody = document.createElement('div');
                        productBody.classList.add('card-body');

                        // Iterar a través de las variantes del producto
                        product.variants.forEach((variant) => {
                            const variantRow = document.createElement('div');
                            variantRow.classList.add('mb-2', 'border-bottom', 'pb-2');

                            const variantLabel = document.createElement('div');
                            variantLabel.textContent = `Variante: ${variant.size || 'Sin nombre'}`;
                            variantLabel.classList.add('me-3', 'fw-bold');

                            const sizeLabel = document.createElement('div');
                            sizeLabel.textContent = `Stock: ${variant.stock || 'Sin nombre'}`;
                            sizeLabel.classList.add('me-3');

                        // Crear el campo de precio como un input (similar a la cantidad)
                            const priceInput = document.createElement('input');
                            priceInput.type = 'number'; // Usamos number para que el usuario pueda ingresar un valor numérico
                            priceInput.placeholder = 'Precio';
                            priceInput.min = 1; // El precio no puede ser negativo
                            priceInput.id = `inputPrice_${product.product_id}_${variant.id}`;
                            priceInput.classList.add('form-control', 'w-auto', 'border', 'border-1', 'p-2', 'bg-white', 'input-price', 'mb-2'); // Estilos

                            const quantityInput = document.createElement('input');
                            quantityInput.type = 'number';
                            quantityInput.placeholder = 'Cantidad';
                            quantityInput.min = 1;
                            quantityInput.id = `inputQuantity_${product.product_id}_${variant.id}`;
                            quantityInput.classList.add('form-control', 'w-auto', 'border', 'border-1', 'p-2', 'bg-white', 'input-cantidad');

                            quantityInput.addEventListener('input', function () {
                                const quantity = parseInt(quantityInput.value) || 0; // Usar parseInt para asegurar que la cantidad sea un número
                                const price = parseFloat(priceInput.value) || 0; // Asegurar que el valor sea numérico

                                if (quantity > 0 && price > 0) {
                                    const selectedProduct = {
                                        product_id: product.product_id,
                                        name: product.product_name,
                                        variant: variant, // Guardamos la variante específica
                                        quantity: quantity,
                                        price: price
                                    };

                                    const existingProductIndex = itemsSelected.findIndex(
                                        (item) => item.product_id === selectedProduct.product_id && item.variant.id === selectedProduct.variant.id
                                    );

                                    if (existingProductIndex > -1) {
                                        // Si ya existe, actualizamos la cantidad
                                        itemsSelected[existingProductIndex].quantity = quantity;
                                    } else {
                                        // Si no existe, lo agregamos a la lista
                                        itemsSelected.push(selectedProduct);
                                    }
                                }

                                console.log("itemsSelected", itemsSelected);
                                document.getElementById('toStep3').disabled = itemsSelected.length === 0; // Desactivar el paso 3 si no hay selección
                            });

                            // Agregar la variante a la tarjeta del producto
                            variantRow.appendChild(variantLabel);
                            variantRow.appendChild(sizeLabel);
                            variantRow.appendChild(priceInput); 
                            variantRow.appendChild(quantityInput);

                            productBody.appendChild(variantRow);
                        });

                        productCard.appendChild(productBody);
                        variantContainer.appendChild(productCard);
                    });
                    // Transición a Step 2
                    step1.classList.add('d-none');
                    step2.classList.remove('d-none');
                })

                .catch((error) => {
                    console.error('Error al obtener variantes:', error);
                    alert('Hubo un error al obtener las variantes. Por favor, intenta nuevamente.');
                });
            
            });
        
            toStep1.addEventListener('click', function () {
                step2.classList.add('d-none');
                step1.classList.remove('d-none');
            });
            
            toStep3.addEventListener('click', function () {
                step3.classList.remove('d-none');
                step2.classList.add('d-none');
            });

            backToStep2.addEventListener('click', function () {
                step2.classList.remove('d-none');
                step3.classList.add('d-none');
            })

            providerInput.addEventListener("input", function () {
                // Verificar si el input tiene texto
                const providerName = providerInput.value.trim();
                document.getElementById('toStep4').disabled = providerName === "";;

                if (providerName !== "") {
                    // Asociar el nombre del proveedor con los productos seleccionados
                    itemsSelected.forEach(item => {
                        item.providers = [providerName]; // Agregar el proveedor al producto (como lista)
                    });

                    console.log("itemsSelected con providerName:", itemsSelected);
                } else {
                    // Si el input está vacío, limpiar los proveedores en itemsSelected
                    itemsSelected.forEach(item => {
                        item.providers = [];
                    });
                }
            });

            toStep4.addEventListener('click', function () {
                const providerContainer = document.getElementById('providerContainer');
                providerContainer.innerHTML = ''; // Limpiar contenido previo, si es necesario

                itemsSelected.forEach((data) => {
                    const dataRow = document.createElement('div');
                    dataRow.classList.add('d-flex', 'align-items-center', 'mb-2');

                    const productLabel = document.createElement('span');
                    productLabel.textContent = `Producto: ${data.name || 'Sin nombre'}`;
                    productLabel.classList.add('me-3');

                    const variantLabel = document.createElement('span');
                    variantLabel.textContent = `Variante: ${data.variant.size || 'Sin nombre'}`;
                    variantLabel.classList.add('me-3');

                    const quantityLabel = document.createElement('span');
                    quantityLabel.textContent = `Cantidad: ${data.quantity || 'Sin nombre'}`;
                    quantityLabel.classList.add('me-3');

                    const priceLabel = document.createElement('span');
                    priceLabel.textContent = `Precio: ${data.price || 'Sin nombre'} USD`;
                    priceLabel.classList.add('me-3');

                    if (Array.isArray(data.providers) && data.providers.length > 0) {
                        data.providers.forEach(provider => {
                            const providerLabel = document.createElement('span');
                            providerLabel.textContent = `Proveedor: ${provider}`;
                            providerLabel.classList.add('me-3');
                            dataRow.appendChild(providerLabel);
                        });
                    } else {
                        console.error('No se encontraron proveedores válidos para este producto.', data.providers);
                    }

                    dataRow.appendChild(productLabel);
                    dataRow.appendChild(variantLabel);
                    dataRow.appendChild(quantityLabel);
                    dataRow.appendChild(priceLabel);

                    // Agregar la fila al contenedor de proveedores
                    providerContainer.appendChild(dataRow);
                });

                // Mostrar el paso 4 y ocultar el paso 3
                step4.classList.remove('d-none');
                step3.classList.add('d-none');
            });

            backToStep3.addEventListener('click', function () {
                step3.classList.remove('d-none');
                step4.classList.add('d-none');
            })

            // Similares transiciones para Step 2 -> Step 3 y Step 3 -> Step 4

            // Finalizar compra
            const purchaseForm = document.getElementById('purchaseForm');
            purchaseForm.addEventListener('submit', function (event) {
                event.preventDefault();
                alert('Compra confirmada');
                // Realiza la solicitud al servidor aquí
            });
            const createOrderButton = document.getElementById('createOrder');
            createOrderButton.addEventListener('click', function () {

                fetch('api/create-order', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                    },
                    body: JSON.stringify({ itemsSelected }),
                })
                .then((response) => {
                    if (response.status === 200) { // Valida el código de estado HTTP
                        alert('Compra creada correctamente');
                        window.location.href = '/products';
                    }
                    // Verificar si la respuesta tiene un código de estado exitoso
                    if (!response.ok) {
                        throw new Error(`Error en la respuesta: ${response.statusText}`);
                    }
                    return response.json();
                })
                .catch((error) => {
                    console.log("err", error)
                })
            })
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
