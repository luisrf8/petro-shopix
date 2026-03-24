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

    .shopix-toast-container {
        position: fixed;
        top: 1rem;
        right: 1rem;
        z-index: 1080;
        display: flex;
        flex-direction: column;
        gap: 0.5rem;
    }

    .shopix-toast {
        min-width: 260px;
        max-width: 420px;
        background: #111827;
        color: #fff;
        border-radius: 10px;
        padding: 0.7rem 0.9rem;
        box-shadow: 0 8px 20px rgba(0, 0, 0, 0.2);
        opacity: 0;
        transform: translateY(-8px);
        transition: opacity .2s ease, transform .2s ease;
        font-size: 0.92rem;
    }

    .shopix-toast.show {
        opacity: 1;
        transform: translateY(0);
    }

    .shopix-toast.info { background: #1d4ed8; }
    .shopix-toast.warning { background: #b45309; }
    .shopix-toast.error { background: #b91c1c; }
    .shopix-toast.success { background: #166534; }
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

                    <div class="mb-3">
                        <label for="productDiscount" class="form-label">Descuento del producto (%)</label>
                        <input type="number" id="productDiscount" name="productDiscount" min="0" max="100" step="0.01" value="0" class="form-control border border-radius-lg p-2" placeholder="0">
                    </div>

                    <!-- Product Images -->
                    <div class="mb-3">
                        <label for="productImages" class="form-label">Imagenes</label>
                        <input type="file" id="productImages" name="images[]" class="form-control border border-radius-lg p-2" multiple accept="image/*">
                        <small class="text-muted d-block mt-1">Si subes JPG/JPEG se convertirá automáticamente a PNG. Si pesa mucho, se reducirá la resolución para evitar errores.</small>
                        <div id="productImageNotice" class="alert alert-warning mt-2 d-none" role="alert"></div>
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
                        <small class="text-muted d-block mb-2">Puedes escribir el código de barras manualmente. Si lo dejas vacío, se genera automáticamente.</small>
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
        const PRODUCT_SAFE_IMAGE_BYTES = 1.8 * 1024 * 1024;
        const PRODUCT_SAFE_TOTAL_UPLOAD_BYTES = 18 * 1024 * 1024;

        function showShopixToast(message, type = 'info') {
            let container = document.getElementById('shopixToastContainer');
            if (!container) {
                container = document.createElement('div');
                container.id = 'shopixToastContainer';
                container.className = 'shopix-toast-container';
                document.body.appendChild(container);
            }

            const toast = document.createElement('div');
            toast.className = `shopix-toast ${type}`;
            toast.textContent = message;
            container.appendChild(toast);

            requestAnimationFrame(() => toast.classList.add('show'));

            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 220);
            }, 3600);
        }

        function showProductImageNotice(message, type = 'warning') {
            showShopixToast(message, type === 'danger' ? 'error' : type);
        }

        function hideProductImageNotice() {
            const notice = document.getElementById('productImageNotice');
            if (!notice) return;
            notice.classList.add('d-none');
            notice.textContent = '';
        }

        function getSelectedImagesTotalBytes(inputEl) {
            if (!inputEl?.files?.length) {
                return 0;
            }

            return Array.from(inputEl.files).reduce((acc, file) => acc + (file.size || 0), 0);
        }

        function setSubmitButtonLoading(button, isLoading, loadingText = 'Guardando...') {
            if (!button) return;

            if (isLoading) {
                if (button.dataset.loading === '1') return;
                button.dataset.loading = '1';
                button.dataset.originalHtml = button.innerHTML;
                button.disabled = true;
                button.innerHTML = `<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>${loadingText}`;
                return;
            }

            button.disabled = false;
            button.dataset.loading = '0';
            if (button.dataset.originalHtml) {
                button.innerHTML = button.dataset.originalHtml;
            }
        }

        function loadImageElement(file) {
            return new Promise((resolve, reject) => {
                const img = new Image();
                const objectUrl = URL.createObjectURL(file);
                img.onload = () => {
                    URL.revokeObjectURL(objectUrl);
                    resolve(img);
                };
                img.onerror = () => {
                    URL.revokeObjectURL(objectUrl);
                    reject(new Error('No se pudo procesar la imagen.'));
                };
                img.src = objectUrl;
            });
        }

        function canvasToBlob(canvas, type, quality) {
            return new Promise((resolve) => {
                canvas.toBlob((blob) => resolve(blob), type, quality);
            });
        }

        async function optimizeProductImage(file) {
            const rasterTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
            if (!rasterTypes.includes((file.type || '').toLowerCase())) {
                return { file, changed: false, convertedToPng: false };
            }

            const source = await loadImageElement(file);
            const originalWidth = source.naturalWidth || source.width;
            const originalHeight = source.naturalHeight || source.height;

            let width = originalWidth;
            let height = originalHeight;
            const maxDimension = 2200;

            if (width > maxDimension || height > maxDimension) {
                const scale = Math.min(maxDimension / width, maxDimension / height);
                width = Math.max(1, Math.round(width * scale));
                height = Math.max(1, Math.round(height * scale));
            }

            const canvas = document.createElement('canvas');
            const ctx = canvas.getContext('2d');
            canvas.width = width;
            canvas.height = height;
            ctx.drawImage(source, 0, 0, width, height);

            const forcePng = ['image/jpeg', 'image/jpg'].includes((file.type || '').toLowerCase());
            const targetType = forcePng ? 'image/png' : (file.type || 'image/png');
            let blob = await canvasToBlob(canvas, targetType, targetType === 'image/webp' ? 0.9 : undefined);

            while (blob && blob.size > PRODUCT_SAFE_IMAGE_BYTES && width > 640 && height > 640) {
                width = Math.max(640, Math.round(width * 0.85));
                height = Math.max(640, Math.round(height * 0.85));
                canvas.width = width;
                canvas.height = height;
                ctx.drawImage(source, 0, 0, width, height);
                blob = await canvasToBlob(canvas, targetType, targetType === 'image/webp' ? 0.82 : undefined);
            }

            if (!blob) {
                return { file, changed: false, convertedToPng: false };
            }

            const changed = blob.size !== file.size || width !== originalWidth || height !== originalHeight || forcePng;
            if (!changed) {
                return { file, changed: false, convertedToPng: false };
            }

            const baseName = file.name.replace(/\.[^.]+$/, '');
            const extension = targetType === 'image/png' ? 'png' : (targetType === 'image/webp' ? 'webp' : 'png');
            const optimizedFile = new File([blob], `${baseName}.${extension}`, { type: targetType });

            return {
                file: optimizedFile,
                changed: true,
                convertedToPng: forcePng,
                stillLarge: optimizedFile.size > PRODUCT_SAFE_IMAGE_BYTES,
            };
        }

        async function optimizeProductInputFiles(inputEl) {
            if (!inputEl?.files?.length) {
                return;
            }

            const dt = new DataTransfer();
            let changedCount = 0;
            let convertedCount = 0;
            let stillLargeCount = 0;

            for (const file of Array.from(inputEl.files)) {
                try {
                    const optimized = await optimizeProductImage(file);
                    dt.items.add(optimized.file);
                    if (optimized.changed) changedCount += 1;
                    if (optimized.convertedToPng) convertedCount += 1;
                    if (optimized.stillLarge) stillLargeCount += 1;
                } catch (error) {
                    dt.items.add(file);
                }
            }

            inputEl.files = dt.files;

            if (changedCount > 0) {
                let msg = `Se optimizaron ${changedCount} imagen(es)`;
                if (convertedCount > 0) {
                    msg += ` y ${convertedCount} JPG/JPEG se convirtieron a PNG`;
                }
                msg += ' para evitar errores por tamaño.';
                if (stillLargeCount > 0) {
                    msg += ' Aún hay archivos grandes: baja la resolución manualmente.';
                }
                showProductImageNotice(msg, stillLargeCount > 0 ? 'warning' : 'info');
            } else {
                hideProductImageNotice();
            }
        }

        // Handle image preview
        document.getElementById('productImages').addEventListener('change', async function(event) {
            await optimizeProductInputFiles(event.target);
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
                    <input type="number" name="variantDiscount[]" class="form-control border border-radius-lg p-2 h-100" placeholder="Discount %" min="0" max="100" step="0.01" value="0" required>
                    <input type="number" name="variantStock[]" class="form-control border border-radius-lg p-2 h-100" placeholder="Variant stock" required>
                    <input type="text" name="variantBarcode[]" class="form-control border border-radius-lg p-2 h-100" placeholder="Código de barras (opcional)">
                    <button type="button" class="btn btn-danger remove-variant-btn">Remove</button>
                </div>
            `;
            container.appendChild(variantDiv);

            // Handle removing variants
            variantDiv.querySelector('.remove-variant-btn').addEventListener('click', function() {
                container.removeChild(variantDiv);
            });
        });

        document.getElementById('createProductForm').addEventListener('submit', async function (event) {
            const authUser = @json($authUser);
            const tenantId = Number(authUser.tenant_id);
            event.preventDefault();

            const submitBtn = this.querySelector('button[type="submit"]');
            if (submitBtn?.dataset.loading === '1') {
                return;
            }
            setSubmitButtonLoading(submitBtn, true, 'Creando...');

            const productImagesInput = document.getElementById('productImages');
            await optimizeProductInputFiles(productImagesInput);

            const totalImageBytes = getSelectedImagesTotalBytes(productImagesInput);
            if (totalImageBytes > PRODUCT_SAFE_TOTAL_UPLOAD_BYTES) {
                const totalMb = (totalImageBytes / (1024 * 1024)).toFixed(1);
                const safeMb = (PRODUCT_SAFE_TOTAL_UPLOAD_BYTES / (1024 * 1024)).toFixed(0);
                showShopixToast(`Las imagenes seleccionadas pesan ${totalMb} MB en total. Reduce el total por debajo de ${safeMb} MB para evitar el error 413.`, 'error');
                setSubmitButtonLoading(submitBtn, false);
                return;
            }

            let formData = new FormData(this);
            formData.append('tenant_id', tenantId); // 👈 Agregas el tenant_id

            // Agrega variantes al FormData
            const variants = [];
            document.querySelectorAll('#variantContainer .variant-row').forEach((row) => {
                const name = row.querySelector('input[name="variantName[]"]').value;
                const price = row.querySelector('input[name="variantPrice[]"]').value;
                const discount = row.querySelector('input[name="variantDiscount[]"]').value;
                const stock = row.querySelector('input[name="variantStock[]"]').value;
                const barcode = row.querySelector('input[name="variantBarcode[]"]').value;
                if (name && price && stock) {
                    variants.push({ name, price, discount_percentage: discount || 0, stock, barcode });
                }
            });

            formData.append('variants', JSON.stringify(variants));

            fetch('api/create-product', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                    'Accept': 'application/json'
                },
                body: formData,
            })
                .then(async (response) => {
                    const payload = await response.json().catch(() => ({}));

                    if (!response.ok || !payload.success) {
                        if (response.status === 413) {
                            throw new Error('La solicitud es demasiado grande para el servidor (413). Reduce el peso total de las imagenes o subelas en menos cantidad.');
                        }

                        if (response.status === 403) {
                            throw new Error('El servidor rechazo la subida (403). Verifica permisos o politicas de tamaño en el servidor.');
                        }

                        const validationMessage = payload?.errors
                            ? Object.values(payload.errors).flat().join('\n')
                            : null;

                        throw new Error(validationMessage || payload.message || 'Error creating product.');
                    }

                    window.location.href = "{{ route('products.index') }}";
                })
                .catch((error) => {
                    console.error('Error:', error);
                    showShopixToast(error.message || 'Error creating product. Please check console for details.', 'error');
                })
                .finally(() => {
                    setSubmitButtonLoading(submitBtn, false);
                });
        });


    </script>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha3/dist/js/bootstrap.bundle.min.js"></script>
@endpush
