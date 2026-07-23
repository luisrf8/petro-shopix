@extends('layouts.app')

@php
  $categoriesUserRole = \App\Models\User::canonicalRoleName(optional(auth()->user()->role)->name);
  $categoriesIsSellerRole = in_array($categoriesUserRole, ['vendor', 'vendedor', 'seller'], true);
@endphp

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
    <div class="container-fluid py-2">
      <!-- Modal para crear categoría -->
      <div class="modal fade" id="createCategoryModal" tabindex="-1" aria-labelledby="createCategoryModalLabel" aria-hidden="true">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="createCategoryModalLabel">Crear Categoría</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <!-- Formulario para crear el Categoría -->
              <form id="createCategoryForm" enctype="multipart/form-data">
                @csrf
                <div class="mb-3 text-center">
                    <img id="createCategoryImagePreview"
                        src=""
                        class="img-fluid rounded"
                        style="max-height:120px; display:none;">
                </div>

                <div class="mb-3">
                    <label for="createCategoryImage" class="form-label">Imagen de la categoría</label>
                    <input
                        type="file"
                        class="form-control border border-1 p-2"
                        id="createCategoryImage"
                        name="image"
                        accept=".png,.jpg,.jpeg,.svg"
                    >
                  <small class="text-muted d-block mt-1">También aplica conversión JPG/JPEG/PNG a WEBP y compresión automática por tamaño.</small>
                </div>
                <div class="mb-3">
                  <button type="button" class="btn btn-outline-dark w-100" id="openCreateCategoryAiBtn">
                    🤖 IA Gemini para imagen de categoría
                  </button>
                </div>
                <div class="mb-3">
                  <label for="categoryName" class="form-label">Nombre</label>
                  <input type="text" class="form-control border border-1 p-2" id="categoryName" name="name" required>
                </div>
                <div class="mb-3">
                  <label for="categoryDescription" class="form-label">Descripción</label>
                  <textarea class="form-control border border-1 p-2" id="categoryDescription" name="description" rows="3"></textarea>
                </div>
                <div class="d-flex flex-row-reverse">
                  <button type="submit" class="btn btn-dark">Guardar</button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
      <!-- Modal para crear categoría -->
      <!-- Tabla para mostrar categorías -->
      <div class="row">
        <div class="col-12">
          <div class="card my-4">
            <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
              <div class="bg-gradient-dark shadow-dark border-radius-lg pt-4 pb-3 d-flex justify-content-between align-items-center">
                <h6 class="text-white text-capitalize ps-3">CATEGORÍAS</h6>
                @if(!$categoriesIsSellerRole)
                  <div id="categoriesCreateTrigger" class="py-1 px-3 text-end " data-bs-toggle="modal" data-bs-target="#createCategoryModal">
                    <label class="text-white">
                      + Agregar Categoría
                    </label>
                  </div>
                @endif
              </div>
            </div>
            <div class="card-body px-0 pb-2">
              <div class="table-responsive p-0">
                <table class="table align-items-center mb-0">
                  <thead class="text-center">
                    <tr>
                      <th>Nombre</th>
                      <th>Descripción</th>
                      <th>Estado</th>
                      <th>Productos Disponibles</th>
                      @if(!$categoriesIsSellerRole)
                        <th>Agregar producto</th>
                        <th>Editar</th>
                        <th>Activar / Inactivar</th>
                      @endif
                    </tr>
                  </thead>
                  <tbody class="text-center">
                    @foreach($categories as $category)
                      <tr>
                        <td>
                          <div class="d-flex px-2 py-1">
                            <div class="d-flex flex-column justify-content-center">
                              <h6 class="mb-0 text-sm">{{ $category['name'] }}</h6>
                            </div>
                          </div>
                        </td>
                        <td>
                          <p class="text-xs text-secondary mb-0">{{ $category['description'] }}</p>
                        </td>
                        <td class="align-middle text-center text-sm">
                          <span class="badge badge-sm  {{ $category->is_active ? 'bg-gradient-success' : 'bg-gradient-secondary' }}">{{ $category->is_active ? 'Activo' : 'Inactivo' }}
                          </span>
                        </td>
                        <td>{{ $category->total_available_items ?? 0 }}</td>
                        @if(!$categoriesIsSellerRole)
                          <td class="align-middle">
                            <a
                              href="{{ route('createProductItem', ['category_id' => $category->id]) }}"
                              class="text-secondary font-weight-bold text-xs toggle-status-btn">
                              Agregar producto
                            </a>
                          </td>
                          <td class="align-middle">
                            <a href="javascript:;"
                              class="text-secondary font-weight-bold text-xs btn-edit-user d-flex align-items-center justify-content-center"
                              data-bs-toggle="modal"
                              data-bs-target="#editCategoryModal"
                              data-category-id="{{ $category->id }}"
                              data-name="{{ $category->name }}"
                              data-description="{{ $category->description }}"
                              data-image="{{ $category->image ? (\App\Support\ImageStorage::url($category->image) ?? '') : '' }}">
                              Editar
                            </a>
                          </td>
                          <td class="align-middle">
                            <a href="javascript:;"
                            class="text-secondary font-weight-bold text-xs toggle-status-btn" 
                            data-id="{{ $category->id }}" 
                            data-status="{{ $category->is_active ? 'active' : 'inactive' }}">
                              {{ $category->is_active ? 'Inactivar' : 'Activar' }}
                            </a>
                          </td>
                        @endif
                      </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
      <!-- Modal para editar categoría -->
      <div class="modal fade" id="editCategoryModal" tabindex="-1" aria-labelledby="editCategoryModal" aria-hidden="true">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="editCategoryModalLabel">Editar Categoría</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <form id="editCategoryForm" enctype="multipart/form-data">
                @csrf
                <input type="hidden" id="editCategoryId" name="id">
                <div class="mb-3 text-center">
                    <img id="currentCategoryImage"
                        src=""
                        class="img-fluid rounded"
                        style="max-height:120px; display:none;">
                </div>

                <div class="mb-3">
                    <label for="editCategoryImage" class="form-label">Imagen de la categoría</label>
                    <input
                        type="file"
                        class="form-control border border-1 p-2"
                        id="editCategoryImage"
                        name="image"
                        accept=".png,.jpg,.jpeg,.svg"
                    >
                    <small class="text-muted">
                        Dejar vacío si no deseas cambiar la imagen
                    </small>
                  <small class="text-muted d-block mt-1">También aplica conversión JPG/JPEG/PNG a WEBP y compresión automática por tamaño.</small>
                </div>
                <div class="mb-3">
                  <button type="button" class="btn btn-outline-dark w-100" id="openEditCategoryAiBtn">
                    🤖 IA Gemini para imagen de categoría
                  </button>
                </div>

                <div class="mb-3">
                  <label for="editCategoryName" class="form-label">Nombre</label>
                  <input type="text" class="form-control border border-1 p-2" id="editCategoryName" name="name" required>
                </div>
                <div class="mb-3">
                  <label for="editCategoryDescription" class="form-label">Descripción</label>
                  <textarea class="form-control border border-1 p-2" id="editCategoryDescription" name="description" rows="3" required></textarea>
                </div>
                <div class="d-flex flex-row-reverse">
                  <button type="submit" class="btn btn-info">Guardar</button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>

      <div class="modal fade" id="createCategoryAiModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Generar imagen de categoría (Crear)</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
              <div id="createCategoryAiPreviewWrapper" class="mb-3 d-none">
                <label class="form-label fw-bold mb-2">Resultado actual</label>
                <img id="createCategoryAiPreview" src="#" class="img-fluid rounded border" alt="Imagen generada IA">
              </div>

              <div id="createCategoryAiChat" class="ai-chat-box mb-3"></div>

              <div id="createCategoryAiLoading" class="mt-2 d-none">
                <div class="d-flex align-items-center">
                  <div class="spinner-border spinner-border-sm me-2 text-dark" role="status"></div>
                  <span>Generando imagen</span>
                  <span class="ai-loading-dots"><span></span><span></span><span></span></span>
                </div>
              </div>

              <div class="mt-3">
                <input type="file" id="createCategoryAiReferenceImage" class="d-none" accept=".png,.jpg,.jpeg,.webp">
                <div class="d-flex gap-2 align-items-end">
                  <button type="button" class="btn btn-outline-dark ai-attach-btn" id="createCategoryAiAttachBtn" title="Adjuntar imagen">📎</button>
                  <textarea id="createCategoryAiPrompt" class="form-control" rows="2" placeholder="Escribe tu mensaje para la IA..."></textarea>
                  <button type="button" class="btn btn-dark" id="createCategoryAiSendBtn" title="Enviar">➤</button>
                </div>
                <small class="text-muted d-block mt-1" id="createCategoryAiAttachedName">Sin imagen adjunta</small>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="createCategoryAiCloseBtn">Cerrar</button>
              <button type="button" class="btn btn-outline-dark" id="createCategoryAiDownloadBtn" disabled>Descargar</button>
              <button type="button" class="btn btn-outline-success" id="createCategoryAiUseBtn" disabled>Usar esta imagen</button>
            </div>
          </div>
        </div>
      </div>

      <div class="modal fade" id="editCategoryAiModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title">Generar imagen de categoría (Editar)</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
              <div id="editCategoryAiPreviewWrapper" class="mb-3 d-none">
                <label class="form-label fw-bold mb-2">Resultado actual</label>
                <img id="editCategoryAiPreview" src="#" class="img-fluid rounded border" alt="Imagen generada IA">
              </div>

              <div id="editCategoryAiChat" class="ai-chat-box mb-3"></div>

              <div id="editCategoryAiLoading" class="mt-2 d-none">
                <div class="d-flex align-items-center">
                  <div class="spinner-border spinner-border-sm me-2 text-dark" role="status"></div>
                  <span>Generando imagen</span>
                  <span class="ai-loading-dots"><span></span><span></span><span></span></span>
                </div>
              </div>

              <div class="mt-3">
                <input type="file" id="editCategoryAiReferenceImage" class="d-none" accept=".png,.jpg,.jpeg,.webp">
                <div class="d-flex gap-2 align-items-end">
                  <button type="button" class="btn btn-outline-dark ai-attach-btn" id="editCategoryAiAttachBtn" title="Adjuntar imagen">📎</button>
                  <textarea id="editCategoryAiPrompt" class="form-control" rows="2" placeholder="Escribe tu mensaje para la IA..."></textarea>
                  <button type="button" class="btn btn-dark" id="editCategoryAiSendBtn" title="Enviar">➤</button>
                </div>
                <small class="text-muted d-block mt-1" id="editCategoryAiAttachedName">Sin imagen adjunta</small>
              </div>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="editCategoryAiCloseBtn">Cerrar</button>
              <button type="button" class="btn btn-outline-dark" id="editCategoryAiDownloadBtn" disabled>Descargar</button>
              <button type="button" class="btn btn-outline-success" id="editCategoryAiUseBtn" disabled>Usar esta imagen</button>
            </div>
          </div>
        </div>
      </div>

    </div>
    @endsection

@push('scripts')
  <script>
    const authUser = @json($authUser);
    const tenantId = Number(authUser.tenant_id);
    const tenantAiImageEndpoint = @json(route('tenant.ai-image'));
    const CATEGORY_SAFE_IMAGE_BYTES = 1.2 * 1024 * 1024;

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

    function notifyCategory(message) {
      showShopixToast(message, 'info');
    }

    function formatCategorySize(bytes) {
      return `${(Number(bytes || 0) / (1024 * 1024)).toFixed(2)} MB`;
    }

    function setCategorySubmitLoading(button, isLoading, loadingText = 'Guardando...') {
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

    function loadImageForCategory(file) {
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

    function categoryCanvasBlob(canvas, type, quality) {
      return new Promise((resolve) => canvas.toBlob(resolve, type, quality));
    }

    async function optimizeCategoryFile(file) {
      const rasterTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
      const type = String(file.type || '').toLowerCase();
      if (!rasterTypes.includes(type)) {
        return { file, changed: false, convertedToWebp: false, stillLarge: file.size > CATEGORY_SAFE_IMAGE_BYTES };
      }

      const img = await loadImageForCategory(file);
      const sourceWidth = img.naturalWidth || img.width;
      const sourceHeight = img.naturalHeight || img.height;
      let width = sourceWidth;
      let height = sourceHeight;
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
      ctx.drawImage(img, 0, 0, width, height);

      const targetType = 'image/webp';
      const convertedToWebp = type !== 'image/webp';
      let blob = await categoryCanvasBlob(canvas, targetType, 0.9);

      while (blob && blob.size > CATEGORY_SAFE_IMAGE_BYTES && width > 640 && height > 640) {
        width = Math.max(640, Math.round(width * 0.85));
        height = Math.max(640, Math.round(height * 0.85));
        canvas.width = width;
        canvas.height = height;
        ctx.drawImage(img, 0, 0, width, height);
        blob = await categoryCanvasBlob(canvas, targetType, 0.82);
      }

      if (!blob) {
        return { file, changed: false, convertedToWebp: false, stillLarge: file.size > CATEGORY_SAFE_IMAGE_BYTES };
      }

      const changed = convertedToWebp || blob.size !== file.size || width !== sourceWidth || height !== sourceHeight;
      if (!changed) {
        return { file, changed: false, convertedToWebp: false, stillLarge: blob.size > CATEGORY_SAFE_IMAGE_BYTES };
      }

      const baseName = file.name.replace(/\.[^.]+$/, '');
      const optimized = new File([blob], `${baseName}.webp`, { type: targetType });

      return {
        file: optimized,
        changed: true,
        convertedToWebp,
        stillLarge: optimized.size > CATEGORY_SAFE_IMAGE_BYTES,
      };
    }

    async function optimizeCategoryInput(inputId) {
      const input = document.getElementById(inputId);
      const file = input?.files?.[0];
      if (!file) return { changed: false };

      const originalSize = Number(file.size || 0);
      const optimized = await optimizeCategoryFile(file);
      const optimizedSize = Number(optimized.file?.size || originalSize);
      const recommendedLimit = formatCategorySize(CATEGORY_SAFE_IMAGE_BYTES);
      if (optimized.changed) {
        const dt = new DataTransfer();
        dt.items.add(optimized.file);
        input.files = dt.files;

        let message = `Imagen optimizada automaticamente: ${formatCategorySize(originalSize)} -> ${formatCategorySize(optimizedSize)} (max recomendado ${recommendedLimit}).`;
        if (optimized.convertedToWebp) {
          message = `Imagen convertida a WEBP y optimizada: ${formatCategorySize(originalSize)} -> ${formatCategorySize(optimizedSize)} (max recomendado ${recommendedLimit}).`;
        }
        if (optimized.stillLarge) {
          message += ` Aun supera el maximo recomendado (${recommendedLimit}); baja la resolucion manualmente.`;
        }
        showShopixToast(message, optimized.stillLarge ? 'warning' : 'info');
      } else if (optimized.stillLarge) {
        showShopixToast(`La imagen pesa ${formatCategorySize(optimizedSize)}. Recomendado por imagen: ${recommendedLimit}.`, 'warning');
      }

      return optimized;
    }

    function initCategoryAiFlow(config) {
      const modalEl = document.getElementById(config.modalId);
      const sourceModalEl = document.getElementById(config.sourceModalId);
      const openButton = document.getElementById(config.openButtonId);
      const modalInstance = new bootstrap.Modal(modalEl);
      let history = [];
      let latestResult = null;

      function appendMessage(role, content) {
        const chatBox = document.getElementById(config.chatId);
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

      function setLoading(isLoading) {
        document.getElementById(config.loadingId).classList.toggle('d-none', !isLoading);
        document.getElementById(config.sendButtonId).disabled = isLoading;
        document.getElementById(config.attachButtonId).disabled = isLoading;
        document.getElementById(config.closeButtonId).disabled = isLoading;
      }

      function renderPreview() {
        const wrapper = document.getElementById(config.previewWrapperId);
        const preview = document.getElementById(config.previewId);
        const downloadBtn = document.getElementById(config.downloadButtonId);
        const useBtn = document.getElementById(config.useButtonId);

        if (!latestResult) {
          wrapper.classList.add('d-none');
          downloadBtn.disabled = true;
          useBtn.disabled = true;
          return;
        }

        preview.src = `data:${latestResult.mimeType};base64,${latestResult.base64Data}`;
        wrapper.classList.remove('d-none');
        downloadBtn.disabled = false;
        useBtn.disabled = false;
      }

      function getReferenceData() {
        const input = document.getElementById(config.referenceInputId);
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

      function downloadLatest() {
        if (!latestResult) return;

        const bytes = atob(latestResult.base64Data);
        const arr = new Uint8Array(bytes.length);
        for (let i = 0; i < bytes.length; i += 1) {
          arr[i] = bytes.charCodeAt(i);
        }
        const blob = new Blob([arr], { type: latestResult.mimeType || 'image/png' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = config.fileName;
        document.body.appendChild(link);
        link.click();
        link.remove();
        setTimeout(() => URL.revokeObjectURL(url), 2500);
      }

      async function applyToTarget() {
        if (!latestResult) return;

        const input = document.getElementById(config.targetInputId);
        const preview = document.getElementById(config.targetPreviewId);
        if (!input || !preview) return;

        const bytes = atob(latestResult.base64Data);
        const arr = new Uint8Array(bytes.length);
        for (let i = 0; i < bytes.length; i += 1) {
          arr[i] = bytes.charCodeAt(i);
        }
        const blob = new Blob([arr], { type: latestResult.mimeType || 'image/png' });
        const file = new File([blob], config.fileName, { type: latestResult.mimeType || 'image/png' });

        const dt = new DataTransfer();
        dt.items.add(file);
        input.files = dt.files;

        await optimizeCategoryInput(config.targetInputId);

        const appliedFile = input.files?.[0] || file;
        preview.src = URL.createObjectURL(appliedFile);
        preview.style.display = 'block';
        appendMessage('assistant', 'Imagen aplicada al formulario. Puedes seguir ajustando o cerrar con la X.');
      }

      async function send() {
        const promptInput = document.getElementById(config.promptId);
        const prompt = String(promptInput.value || '').trim();
        if (!prompt) {
          showShopixToast('Escribe un mensaje para generar la imagen.', 'warning');
          return;
        }

        appendMessage('user', prompt);
        history.push({ role: 'user', content: prompt });
        setLoading(true);

        try {
          const referenceData = await getReferenceData();
          const response = await fetch(tenantAiImageEndpoint, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
            },
            body: JSON.stringify({
              type: 'category',
              prompt,
              messages: history,
              reference_image_data: referenceData?.data || null,
              reference_image_mime: referenceData?.mime || null,
            })
          });

          const payload = await response.json();
          if (!response.ok || !payload.success) {
            throw new Error(payload.error || payload.message || 'No se pudo generar la imagen.');
          }

          latestResult = {
            base64Data: payload.data,
            mimeType: payload.mime_type || 'image/png',
          };

          renderPreview();
          appendMessage('assistant', 'Listo, aquí tienes una versión. ¿La ajustamos o la usamos?');
          history.push({ role: 'assistant', content: 'Imagen generada y mostrada al usuario.' });
          promptInput.value = '';
        } catch (error) {
          appendMessage('assistant', 'No pude generar la imagen. Intenta ajustar tu mensaje.');
          showShopixToast(error.message || 'Error al generar imagen con IA.', 'error');
        } finally {
          setLoading(false);
        }
      }

      function openFlow() {
        history = [];
        latestResult = null;
        document.getElementById(config.promptId).value = '';
        document.getElementById(config.referenceInputId).value = '';
        document.getElementById(config.attachedNameId).textContent = 'Sin imagen adjunta';
        document.getElementById(config.chatId).innerHTML = '';
        appendMessage('assistant', 'Hola, te ayudo a crear la imagen de tu categoría. ¿Cómo la quieres?');
        renderPreview();
        setLoading(false);

        const sourceInstance = bootstrap.Modal.getOrCreateInstance(sourceModalEl);
        sourceModalEl.addEventListener('hidden.bs.modal', () => modalInstance.show(), { once: true });
        sourceInstance.hide();
      }

      modalEl.addEventListener('hidden.bs.modal', () => {
        const sourceInstance = bootstrap.Modal.getOrCreateInstance(sourceModalEl);
        sourceInstance.show();
      });

      openButton.addEventListener('click', openFlow);
      document.getElementById(config.attachButtonId).addEventListener('click', () => {
        document.getElementById(config.referenceInputId).click();
      });
      document.getElementById(config.referenceInputId).addEventListener('change', function () {
        const file = this.files?.[0];
        document.getElementById(config.attachedNameId).textContent = file ? `Adjunto: ${file.name}` : 'Sin imagen adjunta';
      });
      document.getElementById(config.sendButtonId).addEventListener('click', send);
      document.getElementById(config.promptId).addEventListener('keydown', function (event) {
        if (event.key === 'Enter' && !event.shiftKey) {
          event.preventDefault();
          send();
        }
      });
      document.getElementById(config.downloadButtonId).addEventListener('click', downloadLatest);
      document.getElementById(config.useButtonId).addEventListener('click', applyToTarget);
    }

    initCategoryAiFlow({
      modalId: 'createCategoryAiModal',
      sourceModalId: 'createCategoryModal',
      openButtonId: 'openCreateCategoryAiBtn',
      chatId: 'createCategoryAiChat',
      loadingId: 'createCategoryAiLoading',
      previewWrapperId: 'createCategoryAiPreviewWrapper',
      previewId: 'createCategoryAiPreview',
      referenceInputId: 'createCategoryAiReferenceImage',
      attachedNameId: 'createCategoryAiAttachedName',
      promptId: 'createCategoryAiPrompt',
      attachButtonId: 'createCategoryAiAttachBtn',
      sendButtonId: 'createCategoryAiSendBtn',
      closeButtonId: 'createCategoryAiCloseBtn',
      downloadButtonId: 'createCategoryAiDownloadBtn',
      useButtonId: 'createCategoryAiUseBtn',
      targetInputId: 'createCategoryImage',
      targetPreviewId: 'createCategoryImagePreview',
      fileName: 'categoria-gemini.png',
    });

    initCategoryAiFlow({
      modalId: 'editCategoryAiModal',
      sourceModalId: 'editCategoryModal',
      openButtonId: 'openEditCategoryAiBtn',
      chatId: 'editCategoryAiChat',
      loadingId: 'editCategoryAiLoading',
      previewWrapperId: 'editCategoryAiPreviewWrapper',
      previewId: 'editCategoryAiPreview',
      referenceInputId: 'editCategoryAiReferenceImage',
      attachedNameId: 'editCategoryAiAttachedName',
      promptId: 'editCategoryAiPrompt',
      attachButtonId: 'editCategoryAiAttachBtn',
      sendButtonId: 'editCategoryAiSendBtn',
      closeButtonId: 'editCategoryAiCloseBtn',
      downloadButtonId: 'editCategoryAiDownloadBtn',
      useButtonId: 'editCategoryAiUseBtn',
      targetInputId: 'editCategoryImage',
      targetPreviewId: 'currentCategoryImage',
      fileName: 'categoria-gemini-editar.png',
    });

    document.getElementById('createCategoryImage').addEventListener('change', async function () {
      await optimizeCategoryInput('createCategoryImage');
      const file = this.files?.[0];
      const preview = document.getElementById('createCategoryImagePreview');
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

    document.getElementById('createCategoryForm').addEventListener('submit', async function(event) {
      event.preventDefault();

      const submitBtn = this.querySelector('button[type="submit"]');
      if (submitBtn?.dataset.loading === '1') {
        return;
      }
      setCategorySubmitLoading(submitBtn, true, 'Guardando...');

      await optimizeCategoryInput('createCategoryImage');

      let formData = new FormData(this);
      formData.append('tenant_id', tenantId); // 👈 Agregas el tenant_id
      fetch('api/create-category', {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
          'Accept': 'application/json'
        },
        body: formData
      })
      .then(async response => {
        const payload = await response.json().catch(() => ({}));

        if (response.status === 201 && payload.success) {
          showShopixToast(payload.message || 'Categoría creada correctamente', 'success');
          window.location.reload();
          return;
        }

        if (response.status === 413) {
          const selectedBytes = Number(document.getElementById('createCategoryImage')?.files?.[0]?.size || 0);
          const selectedSize = selectedBytes > 0 ? formatCategorySize(selectedBytes) : 'no detectado';
          throw new Error(`Error 413: solicitud demasiado grande. Peso detectado: ${selectedSize}. Recomendado por imagen: ${formatCategorySize(CATEGORY_SAFE_IMAGE_BYTES)}.`);
        }

        const validationMessage = payload?.errors
          ? Object.values(payload.errors).flat().join('\n')
          : null;

        throw new Error(validationMessage || payload.message || 'Error al crear la categoría');
      })
      .catch(error => {
        console.error('Error:', error);
        showShopixToast(error.message || 'Ocurrió un error al crear la categoría', 'error');
      })
      .finally(() => {
        setCategorySubmitLoading(submitBtn, false);
      });
    });
    // Evento para llenar el modal con los datos de la categoría seleccionada
    document.querySelectorAll('.btn-edit-user').forEach(button => {
      button.addEventListener('click', function () {

        const categoryId = this.getAttribute('data-category-id');
        const categoryName = this.getAttribute('data-name');
        const categoryDescription = this.getAttribute('data-description');
        const categoryImage = this.getAttribute('data-image');

        document.getElementById('editCategoryId').value = categoryId;
        document.getElementById('editCategoryName').value = categoryName;
        document.getElementById('editCategoryDescription').value = categoryDescription;

        const imgPreview = document.getElementById('currentCategoryImage');

        if (categoryImage) {
            imgPreview.src = categoryImage;
            imgPreview.style.display = 'block';
        } else {
            imgPreview.style.display = 'none';
        }

        // Limpiar input file
        document.getElementById('editCategoryImage').value = '';
      });
    });

    document.getElementById('editCategoryImage').addEventListener('change', async function () {
      await optimizeCategoryInput('editCategoryImage');
        const file = this.files[0];
        if (!file) {
            const img = document.getElementById('currentCategoryImage');
            img.style.display = 'none';
            return;
        }

        const reader = new FileReader();
        reader.onload = e => {
            const img = document.getElementById('currentCategoryImage');
            img.src = e.target.result;
            img.style.display = 'block';
        };
        reader.readAsDataURL(file);
    });

    // Enviar la actualización al servidor
    document.getElementById('editCategoryForm').addEventListener('submit', async function (event) {
      event.preventDefault(); // Evita el envío normal del formulario

      const submitBtn = this.querySelector('button[type="submit"]');
      if (submitBtn?.dataset.loading === '1') {
        return;
      }
      setCategorySubmitLoading(submitBtn, true, 'Guardando...');

      await optimizeCategoryInput('editCategoryImage');

      const formData = new FormData(this);
      const categoryId = formData.get('id');
      formData.append('tenant_id', tenantId);

      fetch(`api/categories/${categoryId}`, {
        method: 'POST', // Usa 'PUT' si tu API lo requiere
        headers: {
          'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
          'Accept': 'application/json'
        },
        body: formData
      })
      .then(async response => {
        const payload = await response.json().catch(() => ({}));

        if (response.status === 200 && payload.success) {
          showShopixToast(payload.message || 'Categoría actualizada correctamente', 'success');
          window.location.reload();
          return;
        }

        if (response.status === 413) {
          const selectedBytes = Number(document.getElementById('editCategoryImage')?.files?.[0]?.size || 0);
          const selectedSize = selectedBytes > 0 ? formatCategorySize(selectedBytes) : 'no detectado';
          throw new Error(`Error 413: solicitud demasiado grande. Peso detectado: ${selectedSize}. Recomendado por imagen: ${formatCategorySize(CATEGORY_SAFE_IMAGE_BYTES)}.`);
        }

        const validationMessage = payload?.errors
          ? Object.values(payload.errors).flat().join('\n')
          : null;

        throw new Error(validationMessage || payload.message || 'Error al actualizar la categoría');
      })
      .catch(error => {
        console.error('Error:', error);
        showShopixToast(error.message || 'Ocurrió un error al actualizar la categoría', 'error');
      })
      .finally(() => {
        setCategorySubmitLoading(submitBtn, false);
      });
    });
    document.querySelectorAll('.toggle-status-btn').forEach(button => {
      button.addEventListener('click', function () {
        const categoryId = this.getAttribute('data-id');
        const currentStatus = this.getAttribute('data-status');
        // Alternar el estado
        const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
        const reason = newStatus === 'inactive' ? window.shopixRequestActionReason('Indica el motivo para inactivar esta categoría.') : '';
        if (newStatus === 'inactive' && !reason) {
          return;
        }

        // Hacer la petición AJAX para cambiar el estado
        fetch(`api/categories/${categoryId}/toggle-status`, {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
            'Content-Type': 'application/json',
            'Accept': 'application/json',
          },
            body: JSON.stringify({
              is_active: newStatus === 'active' ? 1 : 0,
              tenant_id: tenantId,
              action_reason: reason,
            })
        })
          .then(response => {
            if (response.status === 200) { // Valida el código de estado HTTP
              alert('Categoría actualizada correctamente');
              window.location.reload();
            } else {
              throw new Error('Error al actualizar la categoría');
            }
          })
          .catch(error => {
            console.error('Error:', error);
            alert('Ocurrió un error al actualizar la Categoría');
          });
        })
      });

  </script>
@endpush