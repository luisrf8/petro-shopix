@extends('layouts.app')

@section('title', 'Crear Producto')

@php
    $createProductVariantUnitOptions = \App\Models\ProductVariant::UNIT_TYPE_OPTIONS;
    $createProductVariantQuantityModeOptions = \App\Models\ProductVariant::QUANTITY_INPUT_MODE_OPTIONS;
@endphp

@section('content')
<style>
    .product-builder {
        --surface: #f5f7fb;
        --card-bg: rgba(255, 255, 255, 0.92);
        --border-soft: #d8dee9;
        --text-primary: #0f172a;
        --text-secondary: #475569;
        --accent: #111827;
        --accent-soft: #e5e7eb;
        --success: #166534;
        background: radial-gradient(circle at 0% 0%, #e2ecff 0%, #f8fafc 38%), radial-gradient(circle at 100% 100%, #fef3c7 0%, #f8fafc 30%);
        border-radius: 24px;
        padding: 1.5rem;
    }

    .builder-hero {
        border-radius: 20px;
        border: 1px solid var(--border-soft);
        background: linear-gradient(135deg, rgba(255, 255, 255, 0.92), rgba(250, 250, 252, 0.84));
        padding: 1.3rem 1.4rem;
        margin-bottom: 1rem;
    }

    .builder-hero h1 {
        margin: 0;
        color: var(--text-primary);
        font-weight: 700;
        font-size: clamp(1.3rem, 2.4vw, 2rem);
        letter-spacing: -0.02em;
    }

    .builder-hero p {
        margin: 0.35rem 0 0;
        color: var(--text-secondary);
    }

    .builder-grid {
        display: grid;
        grid-template-columns: 1.1fr 1fr;
        gap: 1rem;
    }

    .builder-card {
        border: 1px solid var(--border-soft);
        border-radius: 20px;
        background: var(--card-bg);
        box-shadow: 0 20px 45px -34px rgba(15, 23, 42, 0.55);
        padding: 1rem;
    }

    .builder-card h4 {
        margin: 0;
        color: var(--text-primary);
        font-size: 1rem;
        font-weight: 700;
    }

    .builder-card small {
        color: var(--text-secondary);
    }

    .tax-chip {
        border: 1px solid var(--border-soft);
        border-radius: 999px;
        padding: 0.45rem 0.8rem;
        background: #fff;
        cursor: pointer;
        transition: all 0.18s ease;
        user-select: none;
    }

    .tax-chip.selected {
        background: #0f172a;
        color: #fff;
        border-color: #0f172a;
    }

    .variant-row {
        border: 1px solid var(--border-soft);
        border-radius: 16px;
        padding: 0.8rem;
        background: #fff;
    }

    .variant-preview {
        width: 54px;
        height: 54px;
        border-radius: 12px;
        border: 1px dashed var(--border-soft);
        object-fit: cover;
        display: none;
    }

    .hero-actions {
        display: flex;
        justify-content: flex-end;
        gap: 0.75rem;
        margin-top: 1rem;
    }

    .hero-actions .btn {
        border-radius: 12px;
        padding-inline: 1rem;
    }

    #createProductForm .form-control,
    #createProductForm .form-select {
        border: 1px solid #d2d6da !important;
        padding: 0.5rem !important;
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
    .shopix-toast.success { background: var(--success); }

    @media (max-width: 992px) {
        .product-builder {
            padding: 1rem;
            border-radius: 16px;
        }

        .builder-grid {
            grid-template-columns: 1fr;
        }

        .hero-actions {
            flex-direction: column;
        }

        .hero-actions .btn {
            width: 100%;
        }
    }
</style>

<div class="container-fluid py-3">
    <div class="product-builder">
        <form id="createProductForm" enctype="multipart/form-data">
            @csrf
            <div class="builder-hero">
                <h1>Nuevo producto con variantes visuales</h1>
                <p>Configura datos generales, impuestos y una imagen propia para cada variante.</p>
            </div>

            <div class="builder-grid">
                <div class="builder-card">
                    <div class="d-flex justify-content-between align-items-end mb-2">
                        <div>
                            <h4>Información general</h4>
                            <small>Datos base del producto y galería principal.</small>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label for="productName" class="form-label">Nombre del producto</label>
                        <input type="text" id="productName" name="productName" class="form-control" placeholder="Ej: Camisa Oxford" required>
                    </div>

                    <div class="mb-3">
                        <label for="categorySelector" class="form-label">Categoría</label>
                        @php $selectedCategoryId = (string) request('category_id', old('category_id', '')); @endphp
                        <select id="categorySelector" name="category_id" class="form-select" required>
                            <option value="">Seleccione una categoría</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->id }}" {{ $selectedCategoryId === (string) $category->id ? 'selected' : '' }}>{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="mb-3">
                        <label for="productDescription" class="form-label">Descripción</label>
                        <textarea id="productDescription" name="productDescription" class="form-control" rows="3" placeholder="Describe el producto"></textarea>
                    </div>

                    <div class="mb-3">
                        <label for="productDiscount" class="form-label">Descuento del producto (%)</label>
                        <input type="number" id="productDiscount" name="productDiscount" min="0" max="100" step="0.01" value="0" class="form-control" data-decimal-friendly="true">
                    </div>

                    <div class="mb-3">
                        <div class="form-check form-switch ps-0 d-flex align-items-center justify-content-between border rounded-3 px-3 py-2 bg-white">
                            <div>
                                <label class="form-check-label fw-semibold" for="productIsConsumable">Producto consumible</label>
                                <small class="d-block text-muted">Los consumibles se usan para inventario interno y no estarán disponibles para venta directa.</small>
                            </div>
                            <input class="form-check-input ms-3" type="checkbox" role="switch" id="productIsConsumable" name="is_consumable" value="1">
                        </div>
                    </div>

                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center gap-2 mb-1">
                            <label for="productImages" class="form-label mb-0">Galería principal del producto</label>
                            <button type="button" class="btn btn-outline-dark btn-sm" id="openCreateProductAiBtn">Generar con IA</button>
                        </div>
                        <input type="file" id="productImages" name="images[]" class="form-control" multiple accept="image/*">
                        <small class="text-muted d-block mt-1">Estas imágenes son del producto general. Cada variante puede tener su propia imagen abajo.</small>
                    </div>
                    <div id="imagePreview" class="d-flex flex-wrap gap-2"></div>
                </div>

                <div class="builder-card">
                    <div class="d-flex justify-content-between align-items-end mb-2">
                        <div>
                            <h4>Impuestos</h4>
                            <small>Selecciona los impuestos que aplican a este producto.</small>
                        </div>
                    </div>
                    @php
                        $productIntrinsicTaxes = collect($taxes ?? [])->filter(function ($tax) {
                            $raw = trim((string) (($tax->name ?? '') . ' ' . ($tax->code ?? '')));
                            $normalized = function_exists('iconv')
                                ? strtolower((string) iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $raw))
                                : strtolower($raw);

                            if (str_contains($normalized, 'igtf') || str_contains($normalized, 'islr') || str_contains($normalized, 'retencion')) {
                                return false;
                            }

                            return true;
                        })->values();
                    @endphp
                    <div id="taxCardsContainer" class="d-flex flex-wrap gap-2 mb-2">
                        @forelse($productIntrinsicTaxes as $tax)
                            <button type="button" class="tax-chip selectable-tax" data-id="{{ $tax->id }}">
                                {{ $tax->name }} ({{ $tax->rate }}%)
                            </button>
                        @empty
                            <span class="text-muted">No hay alícuotas IVA disponibles para productos.</span>
                        @endforelse
                    </div>
                    <div id="taxInputs"></div>
                </div>
            </div>

            <div class="builder-card mt-3">
                <div class="d-flex justify-content-between align-items-end mb-3">
                    <div>
                        <h4>Variantes</h4>
                        <small>Cada variante puede incluir su imagen. Si no subes código de barras, se genera automáticamente.</small>
                    </div>
                    <button type="button" id="addVariantBtn" class="btn btn-outline-dark btn-sm">Agregar variante</button>
                </div>

                <div id="variantContainer" class="d-flex flex-column gap-2"></div>
            </div>

            <div class="hero-actions">
                <a href="{{ route('products.index') }}" class="btn btn-light border">Cancelar</a>
                <button type="submit" class="btn btn-dark">Crear producto</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="createProductAiModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Generar imagen con IA</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div id="createProductAiPreviewWrapper" class="mb-3 d-none">
                    <label class="form-label fw-bold mb-2">Resultado actual</label>
                    <img id="createProductAiPreview" src="#" class="img-fluid rounded border" alt="Imagen generada por IA">
                </div>
                <div id="createProductAiChat" class="border rounded p-3 bg-white mb-3" style="min-height:180px; max-height:280px; overflow:auto;"></div>
                <div id="createProductAiLoading" class="mt-2 d-none">
                    <div class="d-flex align-items-center">
                        <div class="spinner-border spinner-border-sm me-2 text-dark" role="status"></div>
                        <span>Generando imagen...</span>
                    </div>
                </div>
                <div class="alert alert-light border py-2 px-3 small mb-3" id="createProductAiBaseHint">Sin imagen base seleccionada.</div>
                <input type="file" id="createProductAiReferenceImage" class="d-none" accept=".png,.jpg,.jpeg,.webp">
                <div class="d-flex gap-2 align-items-end">
                    <button type="button" class="btn btn-outline-dark" id="createProductAiAttachBtn" title="Adjuntar imagen">📎</button>
                    <button type="button" class="btn btn-outline-secondary d-none" id="createProductAiImportMainBtn" title="Importar imagen del producto principal">Importar imagen del producto principal</button>
                    <textarea id="createProductAiPrompt" class="form-control" rows="2" placeholder="Escribe tu mensaje para la IA..."></textarea>
                    <button type="button" class="btn btn-dark" id="createProductAiSendBtn" title="Enviar">➤</button>
                </div>
                <small class="text-muted d-block mt-1" id="createProductAiAttachedName">Sin imagen adjunta</small>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
                <button type="button" class="btn btn-outline-dark" id="createProductAiDownloadBtn" disabled>Descargar</button>
                <button type="button" class="btn btn-outline-success" id="createProductAiUseBtn" disabled>Usar esta imagen</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    const PRODUCT_SAFE_IMAGE_BYTES = 1.2 * 1024 * 1024;
    const PRODUCT_SAFE_TOTAL_UPLOAD_BYTES = 8 * 1024 * 1024;
    const createProductEndpoint = @json(route('products.createWeb'));
    const tenantAiImageEndpoint = @json(route('tenant.ai-image'));
    const createProductVariantUnitOptions = @json($createProductVariantUnitOptions);
    const createProductVariantQuantityModeOptions = @json($createProductVariantQuantityModeOptions);
    let createProductAiHistory = [];
    let createProductAiLatestResult = null;
    let createProductAiTarget = null;
    let createProductAiImportedReference = null;

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
        }, 3200);
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

    function getSelectedImagesTotalBytes(inputEl) {
        if (!inputEl?.files?.length) return 0;
        return Array.from(inputEl.files).reduce((acc, file) => acc + (file.size || 0), 0);
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

    function parsePositiveCreateProductAmount(value) {
        const amount = window.shopixParseDecimalInput
            ? window.shopixParseDecimalInput(value)
            : Number(value);
        return Number.isFinite(amount) && amount > 0 ? amount : null;
    }

    function parseCreateProductInteger(value, minimum = 0) {
        const amount = Number(value);
        return Number.isInteger(amount) && amount >= minimum ? amount : null;
    }

    function parseCreateProductStockAmount(value, minimum = 0) {
        const amount = window.shopixParseDecimalInput
            ? window.shopixParseDecimalInput(value)
            : Number(String(value ?? '').replace(',', '.'));
        return Number.isFinite(amount) && amount >= minimum ? Math.round(amount * 100) / 100 : null;
    }

    async function optimizeProductImage(file) {
        const rasterTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
        if (!rasterTypes.includes((file.type || '').toLowerCase())) {
            return { file, changed: false };
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

        let blob = await canvasToBlob(canvas, 'image/webp', 0.9);
        while (blob && blob.size > PRODUCT_SAFE_IMAGE_BYTES && width > 640 && height > 640) {
            width = Math.max(640, Math.round(width * 0.85));
            height = Math.max(640, Math.round(height * 0.85));
            canvas.width = width;
            canvas.height = height;
            ctx.drawImage(source, 0, 0, width, height);
            blob = await canvasToBlob(canvas, 'image/webp', 0.82);
        }

        if (!blob) {
            return { file, changed: false };
        }

        const baseName = file.name.replace(/\.[^.]+$/, '');
        const optimizedFile = new File([blob], `${baseName}.webp`, { type: 'image/webp' });
        return {
            file: optimizedFile,
            changed: optimizedFile.size !== file.size || optimizedFile.name !== file.name,
        };
    }

    async function optimizeInputFiles(inputEl) {
        if (!inputEl?.files?.length) return;

        const dt = new DataTransfer();
        for (const file of Array.from(inputEl.files)) {
            try {
                const optimized = await optimizeProductImage(file);
                dt.items.add(optimized.file);
            } catch (error) {
                dt.items.add(file);
            }
        }
        inputEl.files = dt.files;
    }

    function createDataTransferWithFiles(files = []) {
        const dt = new DataTransfer();
        files.forEach((file) => dt.items.add(file));
        return dt;
    }

    function appendFilesToInput(inputEl, files = []) {
        const dt = new DataTransfer();
        Array.from(inputEl?.files || []).forEach((file) => dt.items.add(file));
        files.forEach((file) => dt.items.add(file));
        inputEl.files = dt.files;
    }

    function fileFromBase64(base64Data, mimeType, fileName) {
        const bytes = atob(base64Data);
        const buffer = new Uint8Array(bytes.length);
        for (let i = 0; i < bytes.length; i += 1) {
            buffer[i] = bytes.charCodeAt(i);
        }
        return new File([new Blob([buffer], { type: mimeType || 'image/png' })], fileName, {
            type: mimeType || 'image/png',
        });
    }

    function appendCreateProductAiMessage(role, content) {
        const chatBox = document.getElementById('createProductAiChat');
        if (!chatBox) return;

        const item = document.createElement('div');
        item.className = `mb-2 p-2 rounded ${role === 'assistant' ? 'bg-light border' : 'bg-dark text-white'}`;
        item.textContent = content;
        chatBox.appendChild(item);
        chatBox.scrollTop = chatBox.scrollHeight;
    }

    function setCreateProductAiLoading(isLoading) {
        document.getElementById('createProductAiLoading')?.classList.toggle('d-none', !isLoading);
        document.getElementById('createProductAiSendBtn').disabled = isLoading;
        document.getElementById('createProductAiAttachBtn').disabled = isLoading;
    }

    function renderCreateProductAiPreview() {
        const wrapper = document.getElementById('createProductAiPreviewWrapper');
        const preview = document.getElementById('createProductAiPreview');
        const downloadBtn = document.getElementById('createProductAiDownloadBtn');
        const useBtn = document.getElementById('createProductAiUseBtn');

        if (!createProductAiLatestResult) {
            wrapper.classList.add('d-none');
            downloadBtn.disabled = true;
            useBtn.disabled = true;
            return;
        }

        preview.src = `data:${createProductAiLatestResult.mimeType};base64,${createProductAiLatestResult.base64Data}`;
        wrapper.classList.remove('d-none');
        downloadBtn.disabled = false;
        useBtn.disabled = false;
    }

    function buildCreateProductOptionTags(options, selectedValue = '') {
        return Object.entries(options || {}).map(([value, label]) => {
            const selected = String(selectedValue || '') === String(value) ? 'selected' : '';
            return `<option value="${value}" ${selected}>${label}</option>`;
        }).join('');
    }

    async function dataUrlFromRemoteImage(url) {
        if (!url) return null;
        const response = await fetch(url);
        if (!response.ok) return null;
        const blob = await response.blob();

        return new Promise((resolve, reject) => {
            const reader = new FileReader();
            reader.onload = () => resolve(String(reader.result || ''));
            reader.onerror = () => reject(new Error('No se pudo leer la imagen actual.'));
            reader.readAsDataURL(blob);
        });
    }

    async function getCreateProductAiReferenceData() {
        const attachedFile = document.getElementById('createProductAiReferenceImage')?.files?.[0] || null;
        const targetFile = createProductAiTarget?.input?.files?.[0] || null;
        const sourceFile = attachedFile;

        if (sourceFile) {
            return new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.onload = () => {
                    const dataUrl = String(reader.result || '');
                    const base64 = dataUrl.includes(',') ? dataUrl.split(',')[1] : dataUrl;
                    resolve({ data: base64, mime: sourceFile.type || 'image/png' });
                };
                reader.onerror = () => reject(new Error('No se pudo leer la imagen base.'));
                reader.readAsDataURL(sourceFile);
            });
        }

        if (createProductAiImportedReference?.data) {
            return {
                data: createProductAiImportedReference.data,
                mime: createProductAiImportedReference.mime || 'image/png',
            };
        }

        if (targetFile) {
            return new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.onload = () => {
                    const dataUrl = String(reader.result || '');
                    resolve({
                        data: dataUrl.includes(',') ? dataUrl.split(',')[1] : dataUrl,
                        mime: targetFile.type || 'image/png',
                    });
                };
                reader.onerror = () => reject(new Error('No se pudo leer la imagen seleccionada.'));
                reader.readAsDataURL(targetFile);
            });
        }

        const productSeedFile = createProductAiTarget?.productSeedInput?.files?.[0] || null;
        if (productSeedFile) {
            return new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.onload = () => {
                    const dataUrl = String(reader.result || '');
                    resolve({
                        data: dataUrl.includes(',') ? dataUrl.split(',')[1] : dataUrl,
                        mime: productSeedFile.type || 'image/png',
                    });
                };
                reader.onerror = () => reject(new Error('No se pudo leer la imagen principal del producto.'));
                reader.readAsDataURL(productSeedFile);
            });
        }

        if (createProductAiTarget?.productSeedUrl) {
            const dataUrl = await dataUrlFromRemoteImage(createProductAiTarget.productSeedUrl);
            if (dataUrl) {
                const mime = dataUrl.match(/^data:(image\/[a-zA-Z0-9.+-]+);base64,/)?.[1] || 'image/png';
                return {
                    data: dataUrl.includes(',') ? dataUrl.split(',')[1] : dataUrl,
                    mime,
                };
            }
        }

        if (createProductAiTarget?.existingImageUrl) {
            const dataUrl = await dataUrlFromRemoteImage(createProductAiTarget.existingImageUrl);
            if (!dataUrl) return null;
            const mime = dataUrl.match(/^data:(image\/[a-zA-Z0-9.+-]+);base64,/)?.[1] || 'image/png';
            return {
                data: dataUrl.includes(',') ? dataUrl.split(',')[1] : dataUrl,
                mime,
            };
        }

        return null;
    }

    function openCreateProductAiModal(target) {
        createProductAiTarget = target;
        createProductAiHistory = [];
        createProductAiLatestResult = null;
        createProductAiImportedReference = null;
        document.getElementById('createProductAiPrompt').value = '';
        document.getElementById('createProductAiReferenceImage').value = '';
        document.getElementById('createProductAiAttachedName').textContent = 'Sin imagen adjunta';
        document.getElementById('createProductAiChat').innerHTML = '';
        document.getElementById('createProductAiImportMainBtn')?.classList.toggle('d-none', !(target?.productSeedInput?.files?.[0] || target?.productSeedUrl));
        document.getElementById('createProductAiBaseHint').textContent = target?.existingImageUrl || target?.input?.files?.[0]
            ? `Se usará como base la ${target.label || 'imagen actual'} si no adjuntas otra.`
            : 'Sin imagen base seleccionada.';
        appendCreateProductAiMessage('assistant', `Describe cómo debe verse la ${target?.label || 'imagen'} y generaré una versión inicial.`);
        renderCreateProductAiPreview();
        setCreateProductAiLoading(false);
        bootstrap.Modal.getOrCreateInstance(document.getElementById('createProductAiModal')).show();
    }

    async function sendCreateProductAiPrompt() {
        const promptInput = document.getElementById('createProductAiPrompt');
        const prompt = String(promptInput.value || '').trim();
        if (!prompt) {
            showShopixToast('Escribe un mensaje para generar la imagen.', 'warning');
            return;
        }

        appendCreateProductAiMessage('user', prompt);
        createProductAiHistory.push({ role: 'user', content: prompt });
        setCreateProductAiLoading(true);

        try {
            const referenceData = await getCreateProductAiReferenceData();
            const response = await fetch(tenantAiImageEndpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || document.querySelector('input[name="_token"]')?.value || '',
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({
                    type: 'product',
                    image_operation: 'generate',
                    prompt,
                    messages: createProductAiHistory,
                    reference_image_data: referenceData?.data || null,
                    reference_image_mime: referenceData?.mime || null,
                }),
            });

            const payload = await response.json().catch(() => ({}));
            if (!response.ok || !payload.success || !payload.data) {
                throw new Error(payload.error || payload.message || 'No se pudo generar la imagen.');
            }

            createProductAiLatestResult = {
                base64Data: payload.data,
                mimeType: payload.mime_type || 'image/png',
                fileName: 'producto-generado-ia.png',
            };
            renderCreateProductAiPreview();
            appendCreateProductAiMessage('assistant', 'Listo. Puedes usar esta imagen o seguir refinando el resultado.');
            createProductAiHistory.push({ role: 'assistant', content: 'Imagen generada correctamente.' });
            promptInput.value = '';
        } catch (error) {
            appendCreateProductAiMessage('assistant', 'No pude generar esa imagen. Ajusta el mensaje o cambia la base.');
            showShopixToast(error.message || 'No se pudo generar la imagen.', 'error');
        } finally {
            setCreateProductAiLoading(false);
        }
    }

    async function applyCreateProductAiResult() {
        if (!createProductAiLatestResult || !createProductAiTarget?.input) return;

        const file = fileFromBase64(
            createProductAiLatestResult.base64Data,
            createProductAiLatestResult.mimeType,
            createProductAiLatestResult.fileName || 'producto-generado-ia.png'
        );

        if (createProductAiTarget.multiple) {
            appendFilesToInput(createProductAiTarget.input, [file]);
        } else {
            createProductAiTarget.input.files = createDataTransferWithFiles([file]).files;
        }

        await optimizeInputFiles(createProductAiTarget.input);
        createProductAiTarget.input.dispatchEvent(new Event('change', { bubbles: true }));
        appendCreateProductAiMessage('assistant', 'La imagen se aplicó al formulario.');
    }

    async function importMainProductImageAsSeed() {
        if (!createProductAiTarget) return;

        const seedFile = createProductAiTarget.productSeedInput?.files?.[0] || null;
        if (seedFile) {
            createProductAiImportedReference = await new Promise((resolve, reject) => {
                const reader = new FileReader();
                reader.onload = () => {
                    const dataUrl = String(reader.result || '');
                    resolve({
                        data: dataUrl.includes(',') ? dataUrl.split(',')[1] : dataUrl,
                        mime: seedFile.type || 'image/png',
                        name: seedFile.name,
                    });
                };
                reader.onerror = () => reject(new Error('No se pudo leer la imagen principal del producto.'));
                reader.readAsDataURL(seedFile);
            });
            document.getElementById('createProductAiAttachedName').textContent = `Base importada: ${seedFile.name}`;
            showShopixToast('La imagen principal del producto quedó lista como base para la variante.', 'success');
            return;
        }

        if (createProductAiTarget.productSeedUrl) {
            const dataUrl = await dataUrlFromRemoteImage(createProductAiTarget.productSeedUrl);
            if (!dataUrl) {
                showShopixToast('No se pudo importar la imagen principal del producto.', 'warning');
                return;
            }
            createProductAiImportedReference = {
                data: dataUrl.includes(',') ? dataUrl.split(',')[1] : dataUrl,
                mime: dataUrl.match(/^data:(image\/[a-zA-Z0-9.+-]+);base64,/)?.[1] || 'image/png',
                name: 'imagen-principal-producto',
            };
            document.getElementById('createProductAiAttachedName').textContent = 'Base importada: imagen principal del producto';
            showShopixToast('La imagen principal del producto quedó lista como base para la variante.', 'success');
            return;
        }

        showShopixToast('Primero agrega una imagen principal del producto para reutilizarla como base.', 'warning');
    }

    function createVariantRow() {
        const container = document.getElementById('variantContainer');
        const row = document.createElement('div');
        row.className = 'variant-row';
        row.innerHTML = `
            <div class="row g-2 align-items-end">
                <div class="col-lg-2 col-md-6">
                    <label class="form-label mb-1">Variante</label>
                    <input type="text" class="form-control" name="variantName[]" placeholder="Ej: M" required>
                </div>
                <div class="col-lg-2 col-md-6">
                    <label class="form-label mb-1">Precio</label>
                    <input type="number" class="form-control" name="variantPrice[]" min="0.01" step="0.01" required data-decimal-friendly="true">
                </div>
                <div class="col-lg-2 col-md-6">
                    <label class="form-label mb-1">Desc. %</label>
                    <input type="number" class="form-control" name="variantDiscount[]" min="0" max="100" step="0.01" value="0" data-decimal-friendly="true">
                </div>
                <div class="col-lg-2 col-md-6">
                    <label class="form-label mb-1">Unidad</label>
                    <select class="form-select" name="variantUnitType[]">
                        ${buildCreateProductOptionTags(createProductVariantUnitOptions, 'unidad')}
                    </select>
                </div>
                <div class="col-lg-2 col-md-6">
                    <label class="form-label mb-1">Cantidad</label>
                    <select class="form-select" name="variantQuantityMode[]">
                        ${buildCreateProductOptionTags(createProductVariantQuantityModeOptions, 'integer')}
                    </select>
                </div>
                <div class="col-lg-2 col-md-6">
                    <label class="form-label mb-1">Venta mínima</label>
                    <input type="number" class="form-control" name="variantMinSaleQuantity[]" min="0.01" step="0.01" value="1.00" data-decimal-friendly="true">
                </div>
                <div class="col-lg-1 col-md-6">
                    <label class="form-label mb-1">Stock</label>
                    <input type="number" class="form-control" name="variantStock[]" min="0" step="0.01" required data-decimal-friendly="true">
                </div>
                <div class="col-lg-2 col-md-6">
                    <label class="form-label mb-1">Código de barras</label>
                    <input type="text" class="form-control" name="variantBarcode[]" placeholder="Opcional">
                </div>
                <div class="col-lg-2 col-md-6">
                    <label class="form-label mb-1">Imagen variante</label>
                    <input type="file" class="form-control variant-image-input" accept="image/*">
                </div>
            </div>
            <div class="d-flex justify-content-between align-items-center mt-2">
                <img class="variant-preview" alt="Preview variante">
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-outline-dark btn-sm open-create-variant-ai-btn">IA imagen</button>
                    <button type="button" class="btn btn-outline-danger btn-sm remove-variant-btn">Eliminar</button>
                </div>
            </div>
        `;

        const removeBtn = row.querySelector('.remove-variant-btn');
        removeBtn.addEventListener('click', () => row.remove());

        const imageInput = row.querySelector('.variant-image-input');
        const preview = row.querySelector('.variant-preview');
        row.querySelector('.open-create-variant-ai-btn')?.addEventListener('click', () => {
            const firstProductPreview = document.querySelector('#imagePreview img');
            openCreateProductAiModal({
                input: imageInput,
                preview,
                multiple: false,
                label: 'imagen de variante',
                existingImageUrl: preview.getAttribute('src') || '',
                productSeedInput: document.getElementById('productImages'),
                productSeedUrl: firstProductPreview?.getAttribute('src') || '',
            });
        });
        imageInput.addEventListener('change', async (event) => {
            await optimizeInputFiles(event.target);
            const file = event.target.files?.[0];
            if (!file) {
                preview.style.display = 'none';
                preview.removeAttribute('src');
                return;
            }
            preview.src = URL.createObjectURL(file);
            preview.style.display = 'block';
        });

        container.appendChild(row);
    }

    document.querySelectorAll('.selectable-tax').forEach((chip) => {
        chip.addEventListener('click', () => {
            const id = chip.getAttribute('data-id');
            const taxInputs = document.getElementById('taxInputs');

            if (chip.classList.contains('selected')) {
                chip.classList.remove('selected');
                document.getElementById(`tax_input_${id}`)?.remove();
                return;
            }

            // Selection is exclusive: keep only one tax active at a time.
            document.querySelectorAll('.selectable-tax.selected').forEach((selectedChip) => {
                selectedChip.classList.remove('selected');
            });
            taxInputs.innerHTML = '';

            chip.classList.add('selected');
            const input = document.createElement('input');
            input.type = 'hidden';
            input.name = 'tax_ids[]';
            input.id = `tax_input_${id}`;
            input.value = id;
            taxInputs.appendChild(input);
        });
    });

    document.getElementById('productImages').addEventListener('change', async function(event) {
        await optimizeInputFiles(event.target);
        const preview = document.getElementById('imagePreview');
        preview.innerHTML = '';
        Array.from(event.target.files).forEach(file => {
            const img = document.createElement('img');
            img.src = URL.createObjectURL(file);
            img.style.width = '74px';
            img.style.height = '74px';
            img.style.objectFit = 'cover';
            img.style.borderRadius = '10px';
            preview.appendChild(img);
        });
    });

    document.getElementById('openCreateProductAiBtn').addEventListener('click', () => {
        const firstPreview = document.querySelector('#imagePreview img');
        openCreateProductAiModal({
            input: document.getElementById('productImages'),
            multiple: true,
            label: 'imagen del producto',
            existingImageUrl: firstPreview?.getAttribute('src') || '',
        });
    });

    document.getElementById('createProductAiAttachBtn').addEventListener('click', () => {
        document.getElementById('createProductAiReferenceImage').click();
    });

    document.getElementById('createProductAiImportMainBtn').addEventListener('click', importMainProductImageAsSeed);

    document.getElementById('createProductAiReferenceImage').addEventListener('change', function() {
        const file = this.files?.[0];
        document.getElementById('createProductAiAttachedName').textContent = file ? `Adjunto: ${file.name}` : 'Sin imagen adjunta';
    });

    document.getElementById('createProductAiSendBtn').addEventListener('click', sendCreateProductAiPrompt);
    document.getElementById('createProductAiPrompt').addEventListener('keydown', function(event) {
        if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            sendCreateProductAiPrompt();
        }
    });

    document.getElementById('createProductAiUseBtn').addEventListener('click', applyCreateProductAiResult);
    document.getElementById('createProductAiDownloadBtn').addEventListener('click', () => {
        if (!createProductAiLatestResult) return;
        const file = fileFromBase64(
            createProductAiLatestResult.base64Data,
            createProductAiLatestResult.mimeType,
            createProductAiLatestResult.fileName || 'producto-generado-ia.png'
        );
        const url = URL.createObjectURL(file);
        const link = document.createElement('a');
        link.href = url;
        link.download = file.name;
        document.body.appendChild(link);
        link.click();
        link.remove();
        setTimeout(() => URL.revokeObjectURL(url), 2000);
    });

    document.getElementById('addVariantBtn').addEventListener('click', createVariantRow);
    createVariantRow();

    document.getElementById('createProductForm').addEventListener('submit', async function(event) {
        event.preventDefault();

        const tenantId = Number(@json(auth()->user()->tenant_id));
        const submitBtn = this.querySelector('button[type="submit"]');
        if (submitBtn?.dataset.loading === '1') return;

        setSubmitButtonLoading(submitBtn, true, 'Creando...');

        const productImagesInput = document.getElementById('productImages');
        await optimizeInputFiles(productImagesInput);

        const totalImageBytes = getSelectedImagesTotalBytes(productImagesInput);
        if (totalImageBytes > PRODUCT_SAFE_TOTAL_UPLOAD_BYTES) {
            const totalMb = (totalImageBytes / (1024 * 1024)).toFixed(1);
            showShopixToast(`Las imagenes del producto pesan ${totalMb} MB. Reduce la carga para evitar error 413.`, 'error');
            setSubmitButtonLoading(submitBtn, false);
            return;
        }

        const formData = new FormData(this);
        formData.append('tenant_id', tenantId);

        const variants = [];
        const rows = Array.from(document.querySelectorAll('#variantContainer .variant-row'));

        rows.forEach((row, index) => {
            const name = row.querySelector('input[name="variantName[]"]').value.trim();
            const price = parsePositiveCreateProductAmount(row.querySelector('input[name="variantPrice[]"]').value);
            const discount = row.querySelector('input[name="variantDiscount[]"]').value;
            const stock = parseCreateProductStockAmount(row.querySelector('input[name="variantStock[]"]').value, 0);
            const barcode = row.querySelector('input[name="variantBarcode[]"]').value;
            const unit_type = row.querySelector('select[name="variantUnitType[]"]')?.value || 'unidad';
            const quantity_input_mode = row.querySelector('select[name="variantQuantityMode[]"]')?.value || 'integer';
            const min_sale_quantity = parseCreateProductStockAmount(row.querySelector('input[name="variantMinSaleQuantity[]"]')?.value, 0.01);
            if (name && price !== null && stock !== null && min_sale_quantity !== null) {
                variants.push({
                    name,
                    price,
                    discount_percentage: discount || 0,
                    stock,
                    barcode,
                    unit_type,
                    quantity_input_mode,
                    min_sale_quantity,
                });

                const imageInput = row.querySelector('.variant-image-input');
                if (imageInput?.files?.[0]) {
                    formData.append(`variant_images[${index}]`, imageInput.files[0]);
                }
            }
        });

        if (!variants.length) {
            showShopixToast('Debes agregar al menos una variante válida.', 'warning');
            setSubmitButtonLoading(submitBtn, false);
            return;
        }

        formData.append('variants', JSON.stringify(variants));

        try {
            const response = await fetch(createProductEndpoint, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
                credentials: 'same-origin',
                body: formData,
            });

            const payload = await response.json().catch(() => ({}));

            if (!response.ok || !payload.success) {
                const validationMessage = payload?.errors
                    ? Object.values(payload.errors).flat().join('\n')
                    : null;
                throw new Error(validationMessage || payload.message || 'No se pudo crear el producto.');
            }

            showShopixToast(payload.message || 'Producto creado correctamente', 'success');
            window.location.href = "{{ route('products.index') }}";
        } catch (error) {
            showShopixToast(error.message || 'No se pudo crear el producto.', 'error');
        } finally {
            setSubmitButtonLoading(submitBtn, false);
        }
    });
</script>
@endpush
