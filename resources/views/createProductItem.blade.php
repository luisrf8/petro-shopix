@extends('layouts.app')

@section('title', 'Crear Producto')

@section('content')
<style>
    .tax-card {
        transition: all .2s ease-in-out;
        border: 2px solid #ccc !important;
    }
    .tax-card.selected {
        border: 2px solid #000 !important;
        background-color: #f1f1f1;
    }
</style>
    <div class="container">
        <div class="card my-4">
            <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                <div class="bg-gradient-dark text-white shadow-dark border-radius-lg pt-4 pb-3 d-flex justify-content-center align-items-center">
                    <h1 class="text-white">Crear Producto</h1>
                </div>
            </div>
            <div class="card-body p-4">
                <form id="createProductForm" enctype="multipart/form-data">
                    @csrf
                    <!-- Product Name -->
                    <div class="mb-3">
                        <label for="productName" class="form-label">Nombre del Producto</label>
                        <input type="text" id="productName" name="productName" class="form-control border border-radius-lg p-2" placeholder="Ingrese el nombre del producto" required>
                    </div>

                    <!-- Product Category -->
                    <div class="mb-3">
                        <label for="categorySelector" class="form-label">Categoría</label>
                        <select id="categorySelector" name="category_id" class="form-select border border-radius-lg p-2" required>
                            <option value="">Seleccione una categoría</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Product Description -->
                    <div class="mb-3">
                        <label for="productDescription" class="form-label">Descripcion</label>
                        <textarea id="productDescription" name="productDescription" class="form-control border border-radius-lg p-2" rows="3" placeholder="Ingrese la descripcion del producto"></textarea>
                    </div>

                    <!-- Product Images -->
                    <div class="mb-3">
                        <label for="productImages" class="form-label">Imagenes</label>
                        <input type="file" id="productImages" name="images[]" class="form-control border border-radius-lg p-2" multiple accept="image/*">
                        <div id="imagePreview" class="mt-3 d-flex flex-wrap"></div>
                    </div>

                    <!-- Product Taxes -->
                    <div class="mb-3">
                        <label class="form-label">Impuestos</label>
                        <div id="taxCardsContainer" class="d-flex flex-wrap gap-2">
                            @foreach($taxes as $tax)
                                <div class="tax-card border rounded p-2 text-center selectable-tax" 
                                    data-id="{{ $tax->id }}">
                                    <strong>{{ $tax->name }}</strong><br>
                                    <small>{{ $tax->description }}</small>
                                    <small>{{ $tax->rate }}%</small>
                                </div>
                            @endforeach
                        </div>
                    </div>

                    <!-- Input oculto donde se guardarán los IDs seleccionados -->
                    <div id="taxInputs"></div>
                    <!-- Product Variants -->
                    <div class="mb-3">
                        <label class="form-label">Variantes</label>
                        <div id="variantContainer"></div>
                        <button type="button" id="addVariantBtn" class="btn btn-secondary mt-2">Agregar Variante +</button>
                    </div>

                    <!-- Submit Button -->
                    <div class="text-end">
                        <button type="submit" class="btn btn-dark">Crear Producto</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

@endsection
@push('scripts')

    <script>
        // Handle image preview
        document.getElementById('productImages').addEventListener('change', function(event) {
            const preview = document.getElementById('imagePreview');
            preview.innerHTML = '';
            Array.from(event.target.files).forEach(file => {
                const img = document.createElement('img');
                img.src = URL.createObjectURL(file);
                img.style.width = '100px';
                img.style.margin = '5px';
                img.style.objectFit = 'cover';
                preview.appendChild(img);
            });
        });

        // Selección visual + agregar input hidden
        document.querySelectorAll(".selectable-tax").forEach(card => {
            card.addEventListener("click", () => {
                const id = card.getAttribute("data-id");
                const taxInputs = document.getElementById("taxInputs");

                if (card.classList.contains("selected")) {
                    card.classList.remove("selected");
                    document.getElementById("tax_input_" + id)?.remove();
                } else {
                    card.classList.add("selected");
                    let input = document.createElement("input");
                    input.type = "hidden";
                    input.name = "tax_ids[]";
                    input.id = "tax_input_" + id;
                    input.value = id;
                    taxInputs.appendChild(input);
                }
            });
        });

        // Handle adding variants dynamically
        document.getElementById('addVariantBtn').addEventListener('click', function() {
            const container = document.getElementById('variantContainer');
            const variantDiv = document.createElement('div');
            variantDiv.classList.add('mb-3', 'variant-row');
            variantDiv.innerHTML = `
                <div class="input-group">
                    <input type="text" name="variantName[]" class="form-control border border-radius-lg p-2 h-100" placeholder="Variant name" required>
                    <input type="number" name="variantPrice[]" class="form-control border border-radius-lg p-2 h-100" placeholder="Variant price" required>
                    <input type="number" name="variantStock[]" class="form-control border border-radius-lg p-2 h-100" placeholder="Variant stock" required>
                    <button type="button" class="btn btn-danger remove-variant-btn">Remove</button>
                </div>
            `;
            container.appendChild(variantDiv);

            // Handle removing variants
            variantDiv.querySelector('.remove-variant-btn').addEventListener('click', function() {
                container.removeChild(variantDiv);
            });
        });

        document.getElementById('createProductForm').addEventListener('submit', function (event) {
            const authUser = @json($authUser);
            const tenantId = Number(authUser.tenant_id);
            event.preventDefault();

            let formData = new FormData(this);
            formData.append('tenant_id', tenantId); // 👈 Agregas el tenant_id

            // Agrega variantes al FormData
            const variants = [];
            document.querySelectorAll('#variantContainer .variant-row').forEach((row) => {
                const name = row.querySelector('input[name="variantName[]"]').value;
                const price = row.querySelector('input[name="variantPrice[]"]').value;
                const stock = row.querySelector('input[name="variantStock[]"]').value;
                if (name && price && stock) {
                    variants.push({ name, price, stock });
                }
            });

            formData.append('variants', JSON.stringify(variants));

            fetch('api/create-product', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
                },
                body: formData,
            })
                .then((response) => {
                    console.log("response", response)
                    window.history.back();
                })
                // .then((data) => {
                //     if (data.success) {
                //         alert('Product created successfully!');
                //         // this.reset();
                //         document.getElementById('imagePreview').innerHTML = '';
                //         document.getElementById('variantContainer').innerHTML = '';
                //     } else {
                //         alert('Error creating product. Please try again.');
                //     }
                // })
                .catch((error) => {
                    console.error('Error:', error);
                    alert('Error creating product. Please check console for details.');
                });
        });


    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
@endpush
