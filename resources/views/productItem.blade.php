@extends('layouts.app')

@section('title', 'Categorías')

@section('content')
    <style>
      .ai-chat-box {
        border: 1px solid #dee2e6;
        border-radius: .5rem;
        padding: .75rem;
        background: #f8f9fa;
        height: 220px;
        overflow-y: auto;
      }
      .ai-attach-btn {
        width: 42px;
        height: 42px;
        border-radius: 50%;
      }
      .ai-loading-dots {
        display: inline-flex;
        margin-left: 0.35rem;
      }
      .ai-loading-dots span {
        width: 6px;
        height: 6px;
        margin: 0 2px;
        background: #212529;
        border-radius: 50%;
        animation: aiPulse 0.9s infinite ease-in-out;
      }
      .ai-loading-dots span:nth-child(2) { animation-delay: 0.15s; }
      .ai-loading-dots span:nth-child(3) { animation-delay: 0.3s; }
      @keyframes aiPulse {
        0%, 100% { opacity: 0.3; transform: translateY(0); }
        50% { opacity: 1; transform: translateY(-3px); }
      }
    </style>
    <div class="container-fluid py-2">
      <div class="row">
        <div class="col-md-12 mt-4">
          <div class="">
            <div class="pb-0 px-3">
              <a href="{{ route('products.index') }}" class="d-flex align-items-center">
                <i class="material-symbols-rounded opacity-10">arrow_back_ios_new</i>
                <h6 class="mb-0 mx-1">Volver</h6>
              </a>
            </div>
            <div class="pt-4">
              <div class="row">
                <div class="container">
                  <div class="row">
                    <div class="col-md-12">
              <!-- <div class="card"> -->
                      <div class="card" data-product-id="{{ $product->id }}">
                          <div class="card-body d-flex flex-row">
                            <div class="d-flex">
                              {{-- Miniaturas a la izquierda --}}
                              <div class="d-flex flex-column me-3" style="gap: 0.5rem;">
                                @foreach($product->images as $index => $image)
                                  <img 
                                    src="{{ asset('storage/' . $image->path) }}" 
                                    alt="Miniatura"
                                    class="img-thumbnail cursor-pointer m-0 p-0 border border-1 border-dark text-dark border-radius-lg "
                                    style="width: 4rem; height: 4rem; object-fit: cover;"
                                    onclick="changeMainImage('{{ asset('storage/' . $image->path) }}', {{ $image->id }})"
                                  >
                                @endforeach
                              </div>

                      {{-- Imagen principal y botones --}}
                      <div class="position-relative" style="width: 25rem; height: 25rem;">
                        <p class="text-info position-absolute top-0 end-0 m-2 d-flex flex-column align-items-end" style="gap: 0.5rem;">
                          <button class="btn btn-danger btn-sm" onclick="confirmRemoveImage(currentImageId)">Eliminar imagen</button>
                        </p>
                        <div class="icon icon-shape icon-xl shadow bg-transparent text-center border border-1 border-dark text-dark border-radius-lg w-100 h-100">
                          @if(isset($product->images) && count($product->images) > 0)
                            <img 
                              id="mainImage" 
                              src="{{ asset('storage/' . $product->images[0]->path) }}" 
                              alt="Imagen del producto" 
                              style="width: 100%; height: 100%; object-fit: cover; border-radius: inherit;"
                            >
                            <input type="hidden" id="mainImageId" value="{{ $product->images[0]->id }}">
                          @else
                            <i class="material-symbols-rounded text-dark" style="font-size: 5rem;">photo_camera</i>
                          @endif
                        </div>
                      </div>
                    </div>
                    <!-- Modal para agregar imagen -->
                    <div class="modal fade" id="addImageModal" tabindex="-1" aria-labelledby="addImageModalLabel" aria-hidden="true">
                      <div class="modal-dialog">
                        <form id="addImageForm" method="POST" action="{{ route('products.addImage', $product->id) }}" enctype="multipart/form-data">
                          @csrf
                          <div class="modal-content">
                            <div class="modal-header">
                              <h5 class="modal-title" id="addImageModalLabel">Agregar imagen</h5>
                              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                              <div class="mb-3 text-center">
                                <img id="addImagePreview" src="" class="img-fluid rounded" style="max-height:160px; display:none;">
                              </div>
                              <div class="mb-3">
                                <label for="image" class="form-label">Seleccionar imagen</label>
                                <input type="file" class="form-control" id="image" name="image" accept="image/*" required>
                              </div>
                              <div class="d-flex gap-2">
                                <button type="button" class="btn btn-outline-dark w-100" id="openProductAiBtn">🤖 Generar con IA</button>
                                <button type="button" class="btn btn-outline-secondary w-100" id="removeBgBtn">Quitar fondo IA</button>
                              </div>
                              <small class="text-muted d-block mt-2">Puedes generar una imagen nueva o subir una y quitarle el fondo.</small>
                            </div>
                            <div class="modal-footer">
                              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                              <button type="submit" class="btn btn-dark">Guardar</button>
                            </div>
                          </div>
                        </form>
                      </div>
                    </div>
                    <!-- Product details -->
                    <div class="mx-4">
                      <!-- <div class="card-header">{{ $product->name }}</div> -->
                      <h2><strong>{{ $product->name }}</strong></h2>
                      <p><strong>Categoría:</strong> {{ $product->category->name }}</p>
                      <p><strong>Descripción:</strong> {{ $product->description }}</p>
                      <p><strong>Descuento del producto:</strong> {{ number_format((float) ($product->discount_percentage ?? 0), 2) }}%</p>
                      <button type="button" class="btn btn-outline-dark btn-sm mb-3" id="generateProductCodesBtn">Generar códigos de todas las variantes</button>
                      <p><strong>Impuestos:</strong> 
                        @foreach ($product->taxes as $tax)
                          {{ $tax->name }} - {{ $tax->rate }} %
                        @endforeach
                      </p>
                      <p><strong>Variantes:</strong>
                        <ul>
                          @foreach ($product->variants as $variant)
                              @php
                                $productDiscount = (float) ($product->discount_percentage ?? 0);
                                $variantDiscount = (float) ($variant->discount_percentage ?? 0);
                                $effectivePrice = (float) $variant->price * ((100 - $productDiscount) / 100) * ((100 - $variantDiscount) / 100);
                              @endphp
                              <li class="mb-2">
                                {{ $variant->size }} - Precio: {{ number_format($effectivePrice, 2) }} $
                                @if($productDiscount > 0 || $variantDiscount > 0)
                                  <small class="text-muted">(base: {{ number_format((float) $variant->price, 2) }} $, desc: {{ number_format($productDiscount + $variantDiscount, 2) }}%)</small>
                                @endif
                                - {{$variant->stock}} unidades disponibles
                                <div class="small text-muted mt-1">
                                  QR: <span id="variantQrCode-{{ $variant->id }}">{{ $variant->qr_code ?: '—' }}</span>
                                  | Barras: <span id="variantBarcode-{{ $variant->id }}">{{ $variant->barcode ?: '—' }}</span>
                                </div>
                                <button
                                  type="button"
                                  class="btn btn-outline-secondary btn-sm mt-1 generate-variant-codes-btn"
                                  data-variant-id="{{ $variant->id }}"
                                >
                                  Generar códigos variante
                                </button>
                                <button
                                  type="button"
                                  class="btn btn-outline-secondary btn-sm mt-1 open-qr-modal-btn"
                                  data-qr-title="QR variante {{ $variant->size }}"
                                  data-qr-url="{{ route('variants.qrImage', $variant->id) }}"
                                  data-qr-filename="variante-{{ $variant->id }}-qr.png"
                                  id="showVariantQrBtn-{{ $variant->id }}"
                                  {{ empty($variant->qr_code) ? 'disabled' : '' }}
                                >
                                  Ver QR variante
                                </button>
                                <button
                                  type="button"
                                  class="btn btn-outline-secondary btn-sm mt-1 download-qr-btn"
                                  data-qr-url="{{ route('variants.qrImage', $variant->id) }}"
                                  data-qr-filename="variante-{{ $variant->id }}-qr.png"
                                  id="downloadVariantQrBtn-{{ $variant->id }}"
                                  {{ empty($variant->qr_code) ? 'disabled' : '' }}
                                >
                                  Descargar QR
                                </button>
                                <button
                                  type="button"
                                  class="btn btn-outline-secondary btn-sm mt-1 print-qr-btn"
                                  data-qr-url="{{ route('variants.qrImage', $variant->id) }}"
                                  id="printVariantQrBtn-{{ $variant->id }}"
                                  {{ empty($variant->qr_code) ? 'disabled' : '' }}
                                >
                                  Imprimir QR
                                </button>
                              </li>
                          @endforeach
                        </ul>
                      </p>
                      <!-- <p><strong>Categoría:</strong> {{ $product->category->name }}</p> -->
                         <!-- Action Buttons -->
                      <div class="mt-4">
                        <button class="btn btn-success" data-bs-toggle="modal" data-bs-target="#editProductModal" onclick="editProduct()">Editar</button>
                        <button class="btn btn-info" data-bs-toggle="modal" data-bs-target="#addImageModal">Agregar imagen +</button>
                        <button class="btn btn-dark" onclick="deleteProduct({{ $product->id }})">Eliminar</button>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>

            <div class="modal fade" id="productAiModal" tabindex="-1" aria-hidden="true">
              <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title">Generar imagen de producto con IA</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                  </div>
                  <div class="modal-body">
                    <div id="productAiPreviewWrapper" class="mb-3 d-none">
                      <label class="form-label fw-bold mb-2">Resultado actual</label>
                      <img id="productAiPreview" src="#" class="img-fluid rounded border" alt="Imagen producto IA">
                    </div>

                    <div id="productAiChat" class="ai-chat-box mb-3"></div>

                    <div id="productAiLoading" class="mt-2 d-none">
                      <div class="d-flex align-items-center">
                        <div class="spinner-border spinner-border-sm me-2 text-dark" role="status"></div>
                        <span>Generando imagen</span>
                        <span class="ai-loading-dots"><span></span><span></span><span></span></span>
                      </div>
                    </div>

                    <div class="mt-3">
                      <input type="file" id="productAiReferenceImage" class="d-none" accept=".png,.jpg,.jpeg,.webp">
                      <div class="d-flex gap-2 align-items-end">
                        <button type="button" class="btn btn-outline-dark ai-attach-btn" id="productAiAttachBtn" title="Adjuntar imagen">📎</button>
                        <textarea id="productAiPrompt" class="form-control" rows="2" placeholder="Escribe tu mensaje para la IA..."></textarea>
                        <button type="button" class="btn btn-dark" id="productAiSendBtn" title="Enviar">➤</button>
                      </div>
                      <small class="text-muted d-block mt-1" id="productAiAttachedName">Sin imagen adjunta</small>
                    </div>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="productAiCloseBtn">Cerrar</button>
                    <button type="button" class="btn btn-outline-dark" id="productAiDownloadBtn" disabled>Descargar</button>
                    <button type="button" class="btn btn-outline-success" id="productAiUseBtn" disabled>Usar esta imagen</button>
                  </div>
                </div>
              </div>
            </div>
            <!-- Modal -->
            <div class="modal fade" id="editProductModal" tabindex="-1" role="dialog" aria-labelledby="editProductModalLabel" aria-hidden="true">
              <div class="modal-dialog" role="document">
                <div class="modal-content">
                  <div class="modal-header d-flex justify-content-between">
                    <h5 class="modal-title" id="editProductModalLabel">Editar Producto</h5>
                    <span aria-hidden="true" class="btn-close" data-bs-dismiss="modal"></span>
                  </div>
                  <div class="modal-body">
                  <form id="editProductForm"enctype="multipart/form-data">
                        @csrf
                        <div class="form-group">
                            <label for="productName">Nombre</label>
                            <input type="text" class="form-control border border-1 p-2" id="productName" name="name" value="{{ old('name', $product->name) }}" required>
                        </div>
                        <div class="form-group">
                            <label for="productDescription">Descripción</label>
                            <input class="form-control border border-1 p-2" id="productDescription" name="description" rows="3" value="{{ old('description', $product->description) }}" required></input>
                        </div>
                        <div class="form-group mb-4">
                            <label for="productCategory">Categoría</label>
                            <select class="form-control border border-1 p-2" id="productCategory" name="category" required>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}" 
                                        {{ $category->id == old('category', $product->category_id) ? 'selected' : '' }}>
                                        {{ $category->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="form-group mb-4">
                          <label for="productStatus">Estado</label>
                          <select class="form-control border border-1 p-2" id="productStatus" name="is_active" required>
                            <option value="1" {{ $product->is_active ? 'selected' : '' }}>Activo</option>
                            <option value="0" {{ !$product->is_active ? 'selected' : '' }}>Inactivo</option>
                          </select>
                        </div>
                        <div class="form-group mb-4">
                          <label for="productDiscountPercentage">Descuento del producto (%)</label>
                          <input type="number" class="form-control border border-1 p-2" id="productDiscountPercentage" name="discount_percentage" min="0" max="100" step="0.01" value="{{ number_format((float) ($product->discount_percentage ?? 0), 2, '.', '') }}">
                        </div>
                        <button type="submit" class="btn btn-dark" id="saveChangesBtn">Guardar Cambios</button>
                    </form>
                    <div class="form-group">
                        <label class="fw-bold">Impuestos</label>

                        <div id="taxContainer">
                            @foreach ($taxes as $tax)
                                <div class="form-check">
                                    <input 
                                        class="form-check-input tax-checkbox" 
                                        type="checkbox" 
                                        value="{{ $tax->id }}" 
                                        id="tax{{ $tax->id }}"
                                        {{ $product->taxes->contains($tax->id) ? 'checked' : '' }}
                                    >
                                    <label class="form-check-label" for="tax{{ $tax->id }}">
                                        {{ $tax->name }} ({{ $tax->rate }}%)
                                    </label>
                                </div>
                            @endforeach
                        </div>

                        <button type="button" class="btn btn-dark mt-3" id="saveProductTaxesBtn">
                            Guardar Impuestos del Producto
                        </button>
                    </div>
                    <div class="form-group">
                      <label for="productVariants">Variedades</label>
                      <div id="variantContainer"></div>
                      <button type="button" class="btn btn-secondary mt-3" id="addVariantBtn">Agregar Variante</button>
                      <button type="button" class="btn btn-dark mt-3" id="saveVariantsBtn">Guardar Variantes Creadas</button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            <!-- End Modal -->

            <div class="modal fade" id="productQrModal" tabindex="-1" aria-hidden="true">
              <div class="modal-dialog modal-dialog-centered">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" id="productQrModalTitle">Código QR</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                  </div>
                  <div class="modal-body text-center">
                    <img id="productQrModalImage" src="" alt="Código QR" class="img-fluid border rounded" style="max-width: 320px;">
                    <div id="productQrModalError" class="alert alert-warning d-none mt-3 mb-0">
                      No se pudo cargar la imagen QR. Vuelve a generar el código o intenta de nuevo.
                    </div>
                  </div>
                  <div class="modal-footer">
                    <button type="button" class="btn btn-outline-dark" id="productQrModalDownload">Descargar</button>
                    <button type="button" class="btn btn-outline-secondary" id="productQrModalPrint">Imprimir</button>
                    <button type="button" class="btn btn-dark" data-bs-dismiss="modal">Cerrar</button>
                  </div>
                </div>
              </div>
            </div>
          </div>
      </div>
    </div>
  </div>
  </div>
  </div>
    @endsection

@push('scripts')
<script src="{{ asset('assets/js/core/popper.min.js') }}"></script>
<script src="{{ asset('assets/js/core/bootstrap.min.js') }}"></script>
<!-- Github buttons -->
<script async defer src="https://buttons.github.io/buttons.js"></script>


<script>
    const tenantAiImageEndpoint = @json(route('tenant.ai-image'));
    let productAiModalInstance = null;
    let productAiHistory = [];
    let productAiLatestResult = null;

    function productAiAppendMessage(role, content) {
      const chatBox = document.getElementById('productAiChat');
      const item = document.createElement('div');
      item.className = `mb-2 ${role === 'assistant' ? '' : 'text-end'}`;
      const bubble = document.createElement('div');
      bubble.className = role === 'assistant' ? 'd-inline-block p-2 rounded bg-white border' : 'd-inline-block p-2 rounded text-white bg-dark';
      bubble.style.maxWidth = '90%';
      bubble.textContent = content;
      item.appendChild(bubble);
      chatBox.appendChild(item);
      chatBox.scrollTop = chatBox.scrollHeight;
    }

    function productAiSetLoading(isLoading) {
      document.getElementById('productAiLoading').classList.toggle('d-none', !isLoading);
      document.getElementById('productAiSendBtn').disabled = isLoading;
      document.getElementById('productAiAttachBtn').disabled = isLoading;
      document.getElementById('productAiCloseBtn').disabled = isLoading;
    }

    function productAiRenderPreview() {
      const wrapper = document.getElementById('productAiPreviewWrapper');
      const preview = document.getElementById('productAiPreview');
      const downloadBtn = document.getElementById('productAiDownloadBtn');
      const useBtn = document.getElementById('productAiUseBtn');

      if (!productAiLatestResult) {
        wrapper.classList.add('d-none');
        downloadBtn.disabled = true;
        useBtn.disabled = true;
        return;
      }

      preview.src = `data:${productAiLatestResult.mimeType};base64,${productAiLatestResult.base64Data}`;
      wrapper.classList.remove('d-none');
      downloadBtn.disabled = false;
      useBtn.disabled = false;
    }

    function productAiGetReferenceData() {
      const input = document.getElementById('productAiReferenceImage');
      const file = input?.files?.[0];
      if (!file) {
        return Promise.resolve(null);
      }

      return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () => {
          const dataUrl = String(reader.result || '');
          const base64 = dataUrl.includes(',') ? dataUrl.split(',')[1] : dataUrl;
          resolve({ data: base64, mime: file.type || 'image/png' });
        };
        reader.onerror = () => reject(new Error('No se pudo leer la imagen adjunta.'));
        reader.readAsDataURL(file);
      });
    }

    async function productAiApplyToInput(base64Data, mimeType, fileName = 'producto-gemini.png') {
      const input = document.getElementById('image');
      const preview = document.getElementById('addImagePreview');
      if (!input || !preview || !base64Data) {
        return;
      }

      const bytes = atob(base64Data);
      const arr = new Uint8Array(bytes.length);
      for (let i = 0; i < bytes.length; i += 1) {
        arr[i] = bytes.charCodeAt(i);
      }
      const blob = new Blob([arr], { type: mimeType || 'image/png' });
      const file = new File([blob], fileName, { type: mimeType || 'image/png' });

      const dt = new DataTransfer();
      dt.items.add(file);
      input.files = dt.files;

      preview.src = URL.createObjectURL(file);
      preview.style.display = 'block';
    }

    async function productAiSend() {
      const promptInput = document.getElementById('productAiPrompt');
      const prompt = String(promptInput.value || '').trim();
      if (!prompt) {
        alert('Escribe un mensaje para generar la imagen.');
        return;
      }

      productAiAppendMessage('user', prompt);
      productAiHistory.push({ role: 'user', content: prompt });
      productAiSetLoading(true);

      try {
        const referenceData = await productAiGetReferenceData();
        const response = await fetch(tenantAiImageEndpoint, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
          },
          body: JSON.stringify({
            type: 'product',
            image_operation: 'generate',
            prompt,
            messages: productAiHistory,
            reference_image_data: referenceData?.data || null,
            reference_image_mime: referenceData?.mime || null,
          })
        });

        const payload = await response.json();
        if (!response.ok || !payload.success) {
          throw new Error(payload.error || payload.message || 'No se pudo generar la imagen.');
        }

        productAiLatestResult = {
          base64Data: payload.data,
          mimeType: payload.mime_type || 'image/png',
          fileName: 'producto-gemini.png',
        };

        productAiRenderPreview();
        productAiAppendMessage('assistant', 'Listo, aquí tienes una versión. ¿La ajustamos o la usamos?');
        productAiHistory.push({ role: 'assistant', content: 'Imagen generada y mostrada al usuario.' });
        promptInput.value = '';
      } catch (error) {
        productAiAppendMessage('assistant', 'No pude generar la imagen. Ajusta tu mensaje e intenta de nuevo.');
        alert(error.message || 'Error al generar imagen con IA.');
      } finally {
        productAiSetLoading(false);
      }
    }

    async function removeBackgroundFromUpload() {
      const input = document.getElementById('image');
      const file = input?.files?.[0];
      if (!file) {
        alert('Primero sube una imagen para quitar el fondo.');
        return;
      }

      const data = await new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () => {
          const dataUrl = String(reader.result || '');
          const base64 = dataUrl.includes(',') ? dataUrl.split(',')[1] : dataUrl;
          resolve({ base64, mime: file.type || 'image/png' });
        };
        reader.onerror = () => reject(new Error('No se pudo leer la imagen subida.'));
        reader.readAsDataURL(file);
      });

      const removeBgBtn = document.getElementById('removeBgBtn');
      const oldText = removeBgBtn.textContent;
      removeBgBtn.disabled = true;
      removeBgBtn.textContent = 'Procesando...';

      try {
        const response = await fetch(tenantAiImageEndpoint, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
          },
          body: JSON.stringify({
            type: 'product',
            image_operation: 'remove_background',
            prompt: 'Quita el fondo de este producto y déjalo con transparencia limpia.',
            reference_image_data: data.base64,
            reference_image_mime: data.mime,
          })
        });

        const payload = await response.json();
        if (!response.ok || !payload.success) {
          throw new Error(payload.error || payload.message || 'No se pudo quitar el fondo.');
        }

        await productAiApplyToInput(payload.data, payload.mime_type || 'image/png', 'producto-sin-fondo.png');
        alert('Fondo eliminado y imagen cargada en el formulario.');
      } catch (error) {
        alert(error.message || 'Error al quitar fondo con IA.');
      } finally {
        removeBgBtn.disabled = false;
        removeBgBtn.textContent = oldText;
      }
    }

    let currentImageId = {{ $product->images[0]->id ?? 'null' }};

    function changeMainImage(imagePath, imageId) {
      const mainImage = document.getElementById('mainImage');
      const mainImageId = document.getElementById('mainImageId');

      mainImage.src = imagePath;
      currentImageId = imageId;
      mainImageId.value = imageId;
    }

    function confirmRemoveImage(imageId) {
      if (confirm('¿Estás seguro de que deseas eliminar esta imagen?')) {
        // Aquí iría tu lógica para eliminar la imagen
        // Por ejemplo, una redirección o una llamada AJAX
        window.location.href = `/productos/eliminar-imagen/${imageId}`;
      }
    }
  document.querySelectorAll('.thumbnail img').forEach(img => {
    img.addEventListener('click', function() {
      document.getElementById('mainImage').src = this.src;
    });
  });
  document.getElementById('addVariantBtn').addEventListener('click', function () {
    const variantContainer = document.getElementById('variantContainer');

    // Crear un nuevo div para la variante
    const variantDiv = document.createElement('div');
    variantDiv.classList.add('col', 'mb-3');

    // Crear contenedor para inputs
    const inputContainer = document.createElement('div');
    inputContainer.classList.add('input-group', 'gap-4');

    // Input para el nombre de la variante
    const variantInput = document.createElement('input');
    variantInput.type = 'text';
    variantInput.placeholder = 'Variante';
    variantInput.classList.add('form-control', 'border', 'border-1', 'p-2');
    variantInput.name = 'size';

    // Input para el precio
    const priceInput = document.createElement('input');
    priceInput.type = 'number';
    priceInput.placeholder = 'Precio';
    priceInput.classList.add('form-control', 'border', 'border-1', 'p-2');
    priceInput.name = 'price';

    const discountInput = document.createElement('input');
    discountInput.type = 'number';
    discountInput.placeholder = 'Descuento %';
    discountInput.classList.add('form-control', 'border', 'border-1', 'p-2');
    discountInput.name = 'discount_percentage';
    discountInput.min = '0';
    discountInput.max = '100';
    discountInput.step = '0.01';
    discountInput.value = '0';

    // Input para el stock
    const stockInput = document.createElement('input');
    stockInput.type = 'number';
    stockInput.placeholder = 'Stock';
    stockInput.classList.add('form-control', 'border', 'border-1', 'p-2');
    stockInput.name = 'stock';

    // Botón para eliminar la variante
    const deleteBtn = document.createElement('button');
    deleteBtn.innerText = 'Eliminar';
    deleteBtn.classList.add('btn', 'btn-danger', 'mt-2', 'ms-auto');

    // Funcionalidad para eliminar la variante
    deleteBtn.addEventListener('click', function () {
        variantContainer.removeChild(variantDiv);
    });

    // Agregar inputs al contenedor de inputs
    inputContainer.appendChild(variantInput);
    inputContainer.appendChild(priceInput);
    inputContainer.appendChild(discountInput);
    inputContainer.appendChild(stockInput);

    // Agregar los elementos al div de variante
    variantDiv.appendChild(inputContainer);
    variantDiv.appendChild(deleteBtn);

    // Agregar la nueva variante al contenedor
    variantContainer.appendChild(variantDiv);
});

// Función para guardar variantes
document.getElementById('saveVariantsBtn').addEventListener('click', function () {
    const variantContainer = document.getElementById('variantContainer');
    // Obtener el id del producto desde la tarjeta
    const productId = document.querySelector('.card').getAttribute('data-product-id');
    const variants = [];

    // Recorrer todas las variantes creadas
    variantContainer.querySelectorAll('.input-group').forEach(inputGroup => {
        const size = inputGroup.querySelector('input[name="size"]').value;
        const price = inputGroup.querySelector('input[name="price"]').value;
        const discount_percentage = inputGroup.querySelector('input[name="discount_percentage"]').value;
        const stock = inputGroup.querySelector('input[name="stock"]').value;

        // Validar que los campos no estén vacíos
        if (size && price && stock) {
          variants.push({ size, price, discount_percentage: discount_percentage || 0, stock });
        }
    });

    // Enviar las variantes al servidor mediante AJAX
    if (variants.length > 0) {
        fetch('/api/variants/store', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
            },
            body: JSON.stringify({ product_id: productId, variants }),
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    alert('Variantes guardadas exitosamente.');
                    location.reload();
                } else {
                    alert('Error al guardar variantes.');
                }
            })
            .catch(error => console.error('Error:', error));
    } else {
        alert('Por favor, completa todos los campos antes de guardar.');
    }
});
// ==========================
//  GUARDAR IMPUESTOS ASOCIADOS AL PRODUCTO
// ==========================

document.getElementById('saveProductTaxesBtn').addEventListener('click', function () {
    
    const productId = document.querySelector('.card').getAttribute('data-product-id');

    // IDs seleccionados
    const selectedTaxIds = [...document.querySelectorAll('.tax-checkbox:checked')]
        .map(cb => cb.value);

    fetch(`/products/${productId}/taxes`, {
        method: 'POST',
        headers: {
            "Content-Type": "application/json",
            "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content")
        },
        body: JSON.stringify({
            taxes: selectedTaxIds
        })
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            alert("Impuestos del producto actualizados");
            location.reload();
        } else {
            alert("Hubo un error al actualizar");
        }
    })
    .catch(err => console.error(err));
});

function editProduct() {
  // Obtener los datos del producto desde el DOM o una llamada AJAX
  const productData = {
    variants: @json($product->variants) // Convertir a JSON los datos de las variantes
  };

  // Precargar las variantes
  const variantContainer = document.getElementById('variantContainer');
  variantContainer.innerHTML = ''; // Limpiar variantes previas
  productData.variants.forEach(variant => {
    const variantDiv = document.createElement('div');
    variantDiv.classList.add('row', 'mb-3');
    variantDiv.innerHTML = `
      <div class="col">
        <label for="Nombre">Variante</label>
        <input type="text" class="form-control border border-1 p-2" value="${variant.size}" placeholder="Nombre" name="variantName[]">
      </div>
      <div class="col">
        <label for="Precio">Precio USD</label>
        <input type="number" class="form-control border border-1 p-2" value="${variant.price}" placeholder="Precio" name="variantPrice[]">
      </div>
      <div class="col">
        <label for="Descuento">Desc. %</label>
        <input type="number" class="form-control border border-1 p-2" value="${variant.discount_percentage || 0}" placeholder="Descuento %" name="variantDiscount[]" min="0" max="100" step="0.01">
      </div>
      <div class="col">
        <label for="Stock">Stock</label>
        <input type="number" class="form-control border border-1 p-2" value="${variant.stock}" placeholder="Stock" name="variantStock[]">
      </div>
      <div class="col pt-2">
        <button type="button" class="btn btn-dark mt-4 editVariantBtn" data-id="${variant.id}">Editar</button>
      </div>
    `;
    variantContainer.appendChild(variantDiv);
  });

  // Agregar evento al botón "Editar"
  document.querySelectorAll('.editVariantBtn').forEach(button => {
    button.addEventListener('click', function () {
      const variantId = this.getAttribute('data-id');
      const variantRow = this.closest('.row');
      const size = variantRow.querySelector('input[name="variantName[]"]').value;
      const price = variantRow.querySelector('input[name="variantPrice[]"]').value;
      const discount_percentage = variantRow.querySelector('input[name="variantDiscount[]"]').value;
      const stock = variantRow.querySelector('input[name="variantStock[]"]').value;

      // Realizar una solicitud AJAX para actualizar la variante
      fetch(`/api/variants/${variantId}`, {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
        },
        body: JSON.stringify({ size, price, discount_percentage, stock })
      })
        .then(response => {
          if (response.ok) {
            alert('Variante actualizada exitosamente.');
            window.location.reload()
          } else {
            throw new Error('Error al actualizar la variante.');
          }
        })
        .catch(error => {
          console.error(error);
          alert('Hubo un problema al actualizar la variante.');
        });
    });
  });
}
function confirmRemoveImage(imageId) {
    if (confirm('¿Estás seguro de que deseas eliminar esta imagen?')) {
      fetch(`/api/product/remove-image/${imageId}`, {
        method: 'DELETE',
        headers: {
          'X-CSRF-TOKEN': '{{ csrf_token() }}',
          'Content-Type': 'application/json',
        }
      })
      .then(response => response.json())
      .then(data => {
        if (data.success) {
          alert('Imagen eliminada correctamente');
          location.reload();
        } else {
          alert('Error al eliminar la imagen');
        }
      })
      .catch(error => console.error('Error:', error));
    }
  }
document.getElementById('editProductForm').addEventListener('submit', function(event) {
  event.preventDefault(); // Evitar que se recargue la página
  console.log("Formulario enviado");
    // Crear un objeto con los datos del formulario
    let formData = new FormData(this);

    const productId = document.querySelector('.card').getAttribute('data-product-id');
    console.log("productId", productId);

    // Realizar la solicitud fetch con el body en formato JSON
    fetch(`/api/products/${productId}`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').value,
        },
        body: formData, // Convertir el objeto a JSON
    })
    .then(response => {
        if (response.status === 201) { // Valida el código de estado HTTP
          alert('Producto actualizado correctamente');
          window.location.reload();
        } else {
          throw new Error('Error al crear la categoría');
        }
      })
    .catch(error => {
        console.error('Error:', error);
    });
});

document.addEventListener('DOMContentLoaded', () => {
  const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
  const productCard = document.querySelector('.card[data-product-id]');
  const productId = productCard?.getAttribute('data-product-id');
  const productQrModalEl = document.getElementById('productQrModal');
  const productQrModal = productQrModalEl ? new bootstrap.Modal(productQrModalEl) : null;
  const productQrModalTitle = document.getElementById('productQrModalTitle');
  const productQrModalImage = document.getElementById('productQrModalImage');
  const productQrModalError = document.getElementById('productQrModalError');
  const productQrModalDownload = document.getElementById('productQrModalDownload');
  const productQrModalPrint = document.getElementById('productQrModalPrint');
  let productQrCurrentUrl = '';
  let productQrCurrentFileName = 'qr.png';

  function downloadQrImage(url, fileName) {
    if (!url) return;
    const link = document.createElement('a');
    link.href = url;
    link.download = fileName || 'qr.png';
    document.body.appendChild(link);
    link.click();
    link.remove();
  }

  function printQrImage(url) {
    if (!url) return;
    const printWindow = window.open('', '_blank', 'width=600,height=700');
    if (!printWindow) {
      alert('Debes permitir ventanas emergentes para imprimir el QR.');
      return;
    }

    printWindow.document.write(`
      <html>
        <head><title>Imprimir QR</title></head>
        <body style="display:flex;align-items:center;justify-content:center;min-height:100vh;margin:0;">
          <img src="${url}" style="max-width:420px;width:100%;" onload="window.print();window.close();" />
        </body>
      </html>
    `);
    printWindow.document.close();
  }

  document.querySelectorAll('.open-qr-modal-btn').forEach((button) => {
    button.addEventListener('click', function () {
      productQrCurrentUrl = this.getAttribute('data-qr-url') || '';
      productQrCurrentFileName = this.getAttribute('data-qr-filename') || 'qr.png';
      if (productQrModalTitle) {
        productQrModalTitle.textContent = this.getAttribute('data-qr-title') || 'Código QR';
      }
      if (productQrModalImage) {
        const separator = productQrCurrentUrl.includes('?') ? '&' : '?';
        const freshUrl = `${productQrCurrentUrl}${separator}t=${Date.now()}`;
        productQrModalImage.classList.remove('d-none');
        productQrModalError?.classList.add('d-none');
        productQrModalImage.onerror = function () {
          productQrModalImage.classList.add('d-none');
          productQrModalError?.classList.remove('d-none');
        };
        productQrModalImage.onload = function () {
          productQrModalImage.classList.remove('d-none');
          productQrModalError?.classList.add('d-none');
        };
        productQrModalImage.src = freshUrl;
      }
      productQrModal?.show();
    });
  });

  document.querySelectorAll('.download-qr-btn').forEach((button) => {
    button.addEventListener('click', function () {
      downloadQrImage(this.getAttribute('data-qr-url') || '', this.getAttribute('data-qr-filename') || 'qr.png');
    });
  });

  document.querySelectorAll('.print-qr-btn').forEach((button) => {
    button.addEventListener('click', function () {
      printQrImage(this.getAttribute('data-qr-url') || '');
    });
  });

  productQrModalDownload?.addEventListener('click', function () {
    downloadQrImage(productQrCurrentUrl, productQrCurrentFileName);
  });

  productQrModalPrint?.addEventListener('click', function () {
    printQrImage(productQrCurrentUrl);
  });

  const generateProductCodesBtn = document.getElementById('generateProductCodesBtn');
  generateProductCodesBtn?.addEventListener('click', async function () {
    if (!productId) {
      return;
    }

    const originalText = this.textContent;
    this.disabled = true;
    this.textContent = 'Generando...';

    try {
      const response = await fetch(`/products/${productId}/generate-codes`, {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': csrfToken,
          'Accept': 'application/json',
        },
      });

      const payload = await response.json();
      if (!response.ok || !payload.success) {
        throw new Error(payload.message || 'No se pudieron generar los códigos de variantes.');
      }

      (payload.variants || []).forEach((variant) => {
        const variantId = String(variant.id || '');
        if (!variantId) return;
        const qrElement = document.getElementById(`variantQrCode-${variantId}`);
        const barcodeElement = document.getElementById(`variantBarcode-${variantId}`);
        if (qrElement) qrElement.textContent = variant.qr_code || '—';
        if (barcodeElement) barcodeElement.textContent = variant.barcode || '—';
        document.getElementById(`showVariantQrBtn-${variantId}`)?.removeAttribute('disabled');
        document.getElementById(`downloadVariantQrBtn-${variantId}`)?.removeAttribute('disabled');
        document.getElementById(`printVariantQrBtn-${variantId}`)?.removeAttribute('disabled');
      });

      alert(`Códigos de variantes actualizados. Generados: ${payload.generated || 0} de ${payload.total_variants || 0}.`);
    } catch (error) {
      alert(error.message || 'Error al generar códigos de variantes.');
    } finally {
      this.disabled = false;
      this.textContent = originalText;
    }
  });

  document.querySelectorAll('.generate-variant-codes-btn').forEach((button) => {
    button.addEventListener('click', async function () {
      const variantId = this.getAttribute('data-variant-id');
      if (!variantId) {
        return;
      }

      const originalText = this.textContent;
      this.disabled = true;
      this.textContent = 'Generando...';

      try {
        const response = await fetch(`/variants/${variantId}/generate-codes`, {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': csrfToken,
            'Accept': 'application/json',
          },
        });

        const payload = await response.json();
        if (!response.ok || !payload.success) {
          throw new Error(payload.message || 'No se pudieron generar los códigos de la variante.');
        }

        const qrElement = document.getElementById(`variantQrCode-${variantId}`);
        const barcodeElement = document.getElementById(`variantBarcode-${variantId}`);
        if (qrElement) qrElement.textContent = payload.qr_code || '—';
        if (barcodeElement) barcodeElement.textContent = payload.barcode || '—';
        document.getElementById(`showVariantQrBtn-${variantId}`)?.removeAttribute('disabled');
        document.getElementById(`downloadVariantQrBtn-${variantId}`)?.removeAttribute('disabled');
        document.getElementById(`printVariantQrBtn-${variantId}`)?.removeAttribute('disabled');
        alert('Códigos de variante generados correctamente.');
      } catch (error) {
        alert(error.message || 'Error al generar códigos de variante.');
      } finally {
        this.disabled = false;
        this.textContent = originalText;
      }
    });
  });

  const addImageModalEl = document.getElementById('addImageModal');
  const productAiModalEl = document.getElementById('productAiModal');
  const addImageModalInstance = bootstrap.Modal.getOrCreateInstance(addImageModalEl);
  productAiModalInstance = bootstrap.Modal.getOrCreateInstance(productAiModalEl);

  document.getElementById('image').addEventListener('change', function () {
    const file = this.files?.[0];
    const preview = document.getElementById('addImagePreview');
    if (!file) {
      preview.style.display = 'none';
      return;
    }

    const reader = new FileReader();
    reader.onload = (e) => {
      preview.src = e.target.result;
      preview.style.display = 'block';
    };
    reader.readAsDataURL(file);
  });

  document.getElementById('openProductAiBtn').addEventListener('click', () => {
    productAiHistory = [];
    productAiLatestResult = null;
    document.getElementById('productAiPrompt').value = '';
    document.getElementById('productAiReferenceImage').value = '';
    document.getElementById('productAiAttachedName').textContent = 'Sin imagen adjunta';
    document.getElementById('productAiChat').innerHTML = '';
    productAiAppendMessage('assistant', 'Hola, te ayudo a generar la imagen de tu producto. ¿Qué deseas crear?');
    productAiRenderPreview();
    productAiSetLoading(false);

    addImageModalEl.addEventListener('hidden.bs.modal', () => productAiModalInstance.show(), { once: true });
    addImageModalInstance.hide();
  });

  productAiModalEl.addEventListener('hidden.bs.modal', () => {
    addImageModalInstance.show();
  });

  document.getElementById('removeBgBtn').addEventListener('click', removeBackgroundFromUpload);
  document.getElementById('productAiAttachBtn').addEventListener('click', () => document.getElementById('productAiReferenceImage').click());
  document.getElementById('productAiReferenceImage').addEventListener('change', function () {
    const file = this.files?.[0];
    document.getElementById('productAiAttachedName').textContent = file ? `Adjunto: ${file.name}` : 'Sin imagen adjunta';
  });
  document.getElementById('productAiSendBtn').addEventListener('click', productAiSend);
  document.getElementById('productAiPrompt').addEventListener('keydown', function (event) {
    if (event.key === 'Enter' && !event.shiftKey) {
      event.preventDefault();
      productAiSend();
    }
  });
  document.getElementById('productAiDownloadBtn').addEventListener('click', () => {
    if (!productAiLatestResult) return;
    const bytes = atob(productAiLatestResult.base64Data);
    const arr = new Uint8Array(bytes.length);
    for (let i = 0; i < bytes.length; i += 1) {
      arr[i] = bytes.charCodeAt(i);
    }
    const blob = new Blob([arr], { type: productAiLatestResult.mimeType || 'image/png' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = productAiLatestResult.fileName || 'producto-gemini.png';
    document.body.appendChild(link);
    link.click();
    link.remove();
    setTimeout(() => URL.revokeObjectURL(url), 2500);
  });
  document.getElementById('productAiUseBtn').addEventListener('click', async () => {
    if (!productAiLatestResult) return;
    await productAiApplyToInput(productAiLatestResult.base64Data, productAiLatestResult.mimeType, productAiLatestResult.fileName || 'producto-gemini.png');
    productAiAppendMessage('assistant', 'Imagen aplicada al formulario. Puedes seguir ajustando o cerrar con la X.');
  });
});

</script>
@endpush
