@extends('layouts.app')

@section('title', 'Categorías')

@php
  $productItemUserRole = \App\Models\User::canonicalRoleName(optional(auth()->user()->role)->name);
  $productItemIsSellerRole = in_array($productItemUserRole, ['vendor', 'vendedor', 'seller'], true);
  $productVariantUnitOptions = \App\Models\ProductVariant::UNIT_TYPE_OPTIONS;
  $productVariantQuantityModeOptions = \App\Models\ProductVariant::QUANTITY_INPUT_MODE_OPTIONS;
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

      @media (max-width: 991.98px) {
        .product-detail-body {
          flex-direction: column !important;
          gap: 1rem;
        }

        .product-thumbs {
          flex-direction: row !important;
          flex-wrap: wrap;
          margin-right: 0 !important;
        }

        .product-main-image {
          width: 100% !important;
          height: auto !important;
          max-width: 340px;
          aspect-ratio: 1 / 1;
          margin: 0 auto;
        }

        .product-meta {
          margin-left: 0 !important;
          margin-right: 0 !important;
        }

      }

      #addImageForm .form-control,
      #addImageForm .form-select,
      #editProductForm .form-control,
      #variantEditorList .form-control,
      #newVariantContainer .form-control,
      #productAiPrompt {
        border: 1px solid #d2d6da !important;
        padding: 0.5rem !important;
      }

      .tax-readonly-chip {
        display: inline-flex;
        align-items: center;
        border: 1px solid #d1d5db;
        border-radius: 999px;
        padding: 0.35rem 0.75rem;
        background: #f8fafc;
        font-size: 0.85rem;
        font-weight: 600;
        color: #334155;
      }

      .variant-preview-trigger {
        cursor: zoom-in;
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
                      <div class="card product-detail-card" data-product-id="{{ $product->id }}">
                          <div class="card-body d-flex flex-row product-detail-body">
                            <div class="d-flex">
                              {{-- Miniaturas a la izquierda --}}
                              <div class="d-flex flex-column me-3 product-thumbs" style="gap: 0.5rem;">
                                @foreach($product->images as $index => $image)
                                  <img 
                                    src="{{ \App\Support\ImageStorage::url($image->path) ?? asset('assets/img/shopix5.png') }}" 
                                    alt="Miniatura"
                                    class="img-thumbnail cursor-pointer m-0 p-0 border border-1 border-dark text-dark border-radius-lg "
                                    style="width: 4rem; height: 4rem; object-fit: cover;"
                                    onclick="changeMainImage('{{ \App\Support\ImageStorage::url($image->path) ?? asset('assets/img/shopix5.png') }}', {{ $image->id }})"
                                  >
                                @endforeach
                              </div>

                      {{-- Imagen principal y botones --}}
                      <div class="position-relative product-main-image" style="width: 25rem; height: 25rem;">
                        @if(!$productItemIsSellerRole)
                          <p class="text-info position-absolute top-0 end-0 m-2 d-flex flex-column align-items-end" style="gap: 0.5rem;">
                            <button class="btn btn-danger btn-sm" onclick="confirmRemoveImage(currentImageId)">Eliminar imagen</button>
                          </p>
                        @endif
                        <div class="icon icon-shape icon-xl shadow bg-transparent text-center border border-1 border-dark text-dark border-radius-lg w-100 h-100">
                          @if(isset($product->images) && count($product->images) > 0)
                            <img 
                              id="mainImage" 
                              src="{{ \App\Support\ImageStorage::url($product->images[0]->path) ?? asset('assets/img/shopix5.png') }}" 
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
                                <label for="variantImageSelector" class="form-label">Asignar a variante (opcional)</label>
                                <select class="form-select" id="variantImageSelector" name="variant_id">
                                  <option value="">Imagen general del producto</option>
                                  @foreach ($product->variants as $variant)
                                    <option value="{{ $variant->id }}">{{ $variant->size }} ({{ number_format((float) $variant->price, 2) }} $)</option>
                                  @endforeach
                                </select>
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
                    <div class="mx-4 product-meta">
                      <h2><strong>{{ $product->name }}</strong></h2>
                      <p class="mb-1"><strong>Categoría:</strong> {{ $product->category->name }}</p>
                      <p class="mb-1"><strong>Descripción:</strong> {{ $product->description }}</p>
                      <p class="mb-2"><strong>Descuento del producto:</strong> {{ number_format((float) ($product->discount_percentage ?? 0), 2) }}%</p>
                      <p class="mb-2"><strong>Tipo:</strong> {{ $product->is_consumable ? 'Consumible interno' : 'Producto vendible' }}</p>
                      <div class="d-flex flex-wrap gap-2 mb-3">
                        @if(!$productItemIsSellerRole)
                          <button type="button" class="btn btn-outline-dark btn-sm" id="generateProductCodesBtn">Generar códigos de todas las variantes</button>
                          <button class="btn btn-info btn-sm" data-bs-toggle="modal" data-bs-target="#addImageModal">Agregar imagen +</button>
                          <button class="btn btn-dark btn-sm" onclick="deleteProduct({{ $product->id }})">Eliminar</button>
                        @endif
                      </div>
                    </div>
                  </div>
                </div>
              </div>

              @if(!$productItemIsSellerRole)
                <div class="card mt-3">
                  <div class="card-body">
                    <h5 class="mb-3">Editar producto</h5>
                    <form id="editProductForm" enctype="multipart/form-data" class="row g-3">
                      @csrf
                      <div class="col-md-6">
                        <label for="editProductName" class="form-label">Nombre</label>
                        <input type="text" class="form-control border" id="editProductName" name="name" value="{{ old('name', $product->name) }}" required>
                      </div>
                      <div class="col-md-6">
                        <label for="productCategory" class="form-label">Categoría</label>
                        <select class="form-control border" id="productCategory" name="category" required>
                          @foreach($categories as $category)
                            <option value="{{ $category->id }}" {{ $category->id == old('category', $product->category_id) ? 'selected' : '' }}>
                              {{ $category->name }}
                            </option>
                          @endforeach
                        </select>
                      </div>
                      <div class="col-12">
                        <label for="editProductDescription" class="form-label">Descripción</label>
                        <textarea class="form-control border" id="editProductDescription" name="description" rows="2">{{ old('description', $product->description) }}</textarea>
                      </div>
                      <div class="col-md-4">
                        <label for="productStatus" class="form-label">Estado</label>
                        <select class="form-control border" id="productStatus" name="is_active" required>
                          <option value="1" {{ $product->is_active ? 'selected' : '' }}>Activo</option>
                          <option value="0" {{ !$product->is_active ? 'selected' : '' }}>Inactivo</option>
                        </select>
                      </div>
                      <div class="col-md-4">
                        <label for="productDiscountPercentage" class="form-label">Descuento (%)</label>
                        <input type="number" class="form-control border" id="productDiscountPercentage" name="discount_percentage" min="0" max="100" step="0.01" value="{{ number_format((float) ($product->discount_percentage ?? 0), 2, '.', '') }}" data-decimal-friendly="true">
                      </div>
                      <div class="col-md-4">
                        <label for="productConsumableToggle" class="form-label">Disponibilidad</label>
                        <select class="form-control border" id="productConsumableToggle" name="is_consumable" required>
                          <option value="0" {{ !$product->is_consumable ? 'selected' : '' }}>Disponible para venta</option>
                          <option value="1" {{ $product->is_consumable ? 'selected' : '' }}>Consumible interno</option>
                        </select>
                      </div>
                      <div class="col-md-12 d-flex align-items-end">
                        <button type="submit" class="btn btn-dark w-100" id="saveChangesBtn">Guardar Cambios</button>
                      </div>
                    </form>
                  </div>
                </div>
              @endif

              <div class="card mt-3">
                <div class="card-body">
                  <h5 class="mb-3">Impuesto asignado</h5>
                  <p class="text-sm text-muted mb-3">La edición de producto muestra únicamente el impuesto originalmente asociado.</p>
                  <div class="d-flex flex-wrap gap-2">
                    @forelse ($product->taxes as $tax)
                      <span class="tax-readonly-chip">{{ $tax->name }} ({{ $tax->rate }}%)</span>
                    @empty
                      <span class="text-muted">Este producto no tiene impuestos asignados.</span>
                    @endforelse
                  </div>
                </div>
              </div>

              <div class="card mt-3">
                <div class="card-header pb-0 border-0 bg-transparent">
                  <ul class="nav nav-tabs card-header-tabs" id="productInventoryTabs" role="tablist">
                    <li class="nav-item" role="presentation">
                      <button class="nav-link active" id="variants-tab" data-bs-toggle="tab" data-bs-target="#variants-tab-pane" type="button" role="tab" aria-controls="variants-tab-pane" aria-selected="true">Variantes</button>
                    </li>
                    <li class="nav-item" role="presentation">
                      <button class="nav-link" id="warehouse-stock-tab" data-bs-toggle="tab" data-bs-target="#warehouse-stock-tab-pane" type="button" role="tab" aria-controls="warehouse-stock-tab-pane" aria-selected="false">Stock por Almacén</button>
                    </li>
                  </ul>
                </div>
                <div class="card-body">
                  <div class="tab-content" id="productInventoryTabContent">
                    <div class="tab-pane fade show active" id="variants-tab-pane" role="tabpanel" aria-labelledby="variants-tab" tabindex="0">
                      <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Variantes</h5>
                        @if(!$productItemIsSellerRole)
                          <button type="button" class="btn btn-outline-dark btn-sm" id="addVariantBtn">Agregar Variante</button>
                        @endif
                      </div>

                      <div id="variantEditorList" class="d-flex flex-column gap-3">
                    @foreach ($product->variants as $variant)
                      @php
                        $productDiscount = (float) ($product->discount_percentage ?? 0);
                        $variantDiscount = (float) ($variant->discount_percentage ?? 0);
                        $effectivePrice = (float) $variant->price * ((100 - $productDiscount) / 100) * ((100 - $variantDiscount) / 100);
                        $variantImage = $variant->images->first();
                      @endphp
                      <div class="border rounded p-3" data-existing-variant-row="{{ $variant->id }}">
                        <div class="row g-2 align-items-end">
                          <div class="col-lg-3 col-md-6">
                            <label class="form-label">Variante</label>
                            <input type="text" class="form-control border" data-existing-size value="{{ $variant->size }}" {{ $productItemIsSellerRole ? 'readonly' : '' }}>
                          </div>
                          <div class="col-lg-2 col-md-6">
                            <label class="form-label">Precio base</label>
                            <input type="number" class="form-control border" data-existing-price min="0.01" step="0.01" value="{{ number_format((float) $variant->price, 2, '.', '') }}" data-decimal-friendly="true" {{ $productItemIsSellerRole ? 'readonly' : '' }}>
                          </div>
                          <div class="col-lg-2 col-md-6">
                            <label class="form-label">Desc. variante %</label>
                            <input type="number" class="form-control border" data-existing-discount min="0" max="100" step="0.01" value="{{ number_format((float) ($variant->discount_percentage ?? 0), 2, '.', '') }}" data-decimal-friendly="true" {{ $productItemIsSellerRole ? 'readonly' : '' }}>
                          </div>
                          <div class="col-lg-2 col-md-6">
                            <label class="form-label">Unidad</label>
                            <select class="form-control border" data-existing-unit-type {{ $productItemIsSellerRole ? 'disabled' : '' }}>
                              @foreach($productVariantUnitOptions as $unitValue => $unitLabel)
                                <option value="{{ $unitValue }}" {{ ($variant->unit_type ?? 'unidad') === $unitValue ? 'selected' : '' }}>{{ $unitLabel }}</option>
                              @endforeach
                            </select>
                          </div>
                          <div class="col-lg-2 col-md-6">
                            <label class="form-label">Cantidad</label>
                            <select class="form-control border" data-existing-quantity-mode {{ $productItemIsSellerRole ? 'disabled' : '' }}>
                              @foreach($productVariantQuantityModeOptions as $modeValue => $modeLabel)
                                <option value="{{ $modeValue }}" {{ ($variant->quantity_input_mode ?? 'integer') === $modeValue ? 'selected' : '' }}>{{ $modeLabel }}</option>
                              @endforeach
                            </select>
                          </div>
                          <div class="col-lg-2 col-md-6">
                            <label class="form-label">Venta mínima</label>
                            <input type="number" class="form-control border" data-existing-min-sale-quantity min="0.01" step="0.01" value="{{ number_format((float) ($variant->min_sale_quantity ?? 1), 2, '.', '') }}" data-decimal-friendly="true" {{ $productItemIsSellerRole ? 'readonly' : '' }}>
                          </div>
                          <div class="col-lg-1 col-md-6">
                            <label class="form-label">Stock</label>
                            <input type="number" class="form-control border" data-existing-stock min="0" step="0.01" value="{{ number_format((float) $variant->stock, 2, '.', '') }}" data-decimal-friendly="true" {{ $productItemIsSellerRole ? 'readonly' : '' }}>
                          </div>
                          <div class="col-lg-2 col-md-6">
                            <label class="form-label">Código barras</label>
                            <input type="text" class="form-control border" data-existing-barcode value="{{ $variant->barcode ?: '' }}" data-variant-barcode-input="{{ $variant->id }}" {{ $productItemIsSellerRole ? 'readonly' : '' }}>
                          </div>
                          <div class="col-lg-2 col-md-6">
                            <label class="form-label">Imagen variante</label>
                            @if(!$productItemIsSellerRole)
                              <input type="file" class="form-control border existing-variant-image-input" data-existing-image="{{ $variant->id }}" accept="image/*">
                            @endif
                          </div>
                        </div>

                        <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mt-2">
                          <div class="d-flex align-items-center gap-2">
                            <img
                              src="{{ $variantImage ? (\App\Support\ImageStorage::url($variantImage->path) ?? asset('assets/img/shopix5.png')) : asset('assets/img/shopix5.png') }}"
                              alt="Imagen variante"
                              id="variantPreview-{{ $variant->id }}"
                              class="variant-preview-trigger"
                              data-variant-image-preview="{{ $variant->id }}"
                              data-image-label="{{ $product->name }} · {{ $variant->size }}"
                              style="width:52px;height:52px;object-fit:cover;border-radius:8px;border:1px solid #d1d5db;"
                            >
                            <small class="text-muted">Precio final: {{ number_format($effectivePrice, 2) }} $</small>
                          </div>
                          @if(!$productItemIsSellerRole)
                            <div class="d-flex flex-wrap gap-2">
                              <button type="button" class="btn btn-outline-dark btn-sm open-existing-variant-ai-btn" data-variant-id="{{ $variant->id }}">IA imagen</button>
                              <button type="button" class="btn btn-dark btn-sm save-existing-variant-btn" data-variant-id="{{ $variant->id }}">Guardar variante</button>
                              <button type="button" class="btn btn-outline-secondary btn-sm generate-variant-codes-btn" data-variant-id="{{ $variant->id }}">Generar códigos</button>
                              <button type="button" class="btn btn-outline-secondary btn-sm open-qr-modal-btn" data-qr-title="QR variante {{ $variant->size }}" data-qr-url="{{ route('variants.qrImage', $variant->id) }}" data-qr-filename="variante-{{ $variant->id }}-qr.png" id="showVariantQrBtn-{{ $variant->id }}" {{ empty($variant->qr_code) ? 'disabled' : '' }}>Ver QR</button>
                              <button type="button" class="btn btn-outline-secondary btn-sm download-qr-btn" data-qr-url="{{ route('variants.qrImage', $variant->id) }}" data-qr-filename="variante-{{ $variant->id }}-qr.png" id="downloadVariantQrBtn-{{ $variant->id }}" {{ empty($variant->qr_code) ? 'disabled' : '' }}>Descargar QR</button>
                              <button type="button" class="btn btn-outline-secondary btn-sm print-qr-btn" data-qr-url="{{ route('variants.qrImage', $variant->id) }}" id="printVariantQrBtn-{{ $variant->id }}" {{ empty($variant->qr_code) ? 'disabled' : '' }}>Imprimir QR</button>
                            </div>
                          @endif
                        </div>

                        <div class="small text-muted mt-2">
                          QR: <span id="variantQrCode-{{ $variant->id }}">{{ $variant->qr_code ?: '—' }}</span>
                          | Barras: <span id="variantBarcode-{{ $variant->id }}">{{ $variant->barcode ?: '—' }}</span>
                        </div>
                      </div>
                    @endforeach
                      </div>

                      @if(!$productItemIsSellerRole)
                        <div id="newVariantContainer" class="d-flex flex-column gap-3 mt-3"></div>

                        <button type="button" class="btn btn-dark mt-3" id="saveVariantsBtn">Guardar variantes nuevas</button>
                      @endif
                    </div>

                    <div class="tab-pane fade" id="warehouse-stock-tab-pane" role="tabpanel" aria-labelledby="warehouse-stock-tab" tabindex="0">
                      <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                        <div>
                          <h5 class="mb-1">Stock por Almacén</h5>
                          <p class="text-sm text-muted mb-0">Consulta la disponibilidad de cada variante en las distintas sedes activas.</p>
                        </div>
                        <a href="{{ route('warehouses.index') }}" class="btn btn-outline-dark btn-sm">Gestionar almacenes</a>
                      </div>

                      @if(($warehouses ?? collect())->isEmpty())
                        <div class="alert alert-light border text-dark mb-0">No hay almacenes activos configurados para esta tienda.</div>
                      @else
                        <div class="table-responsive">
                          <table class="table align-middle">
                            <thead>
                              <tr>
                                <th>Variante</th>
                                @foreach($warehouses as $warehouse)
                                  <th>{{ $warehouse->name }}</th>
                                @endforeach
                                <th>Total</th>
                              </tr>
                            </thead>
                            <tbody>
                              @foreach($product->variants as $variant)
                                @php $variantWarehouseTotal = 0; @endphp
                                <tr>
                                  <td>
                                    <strong>{{ $variant->size }}</strong>
                                    <div class="text-muted small">Stock global: {{ $variant->stock }}</div>
                                  </td>
                                  @foreach($warehouses as $warehouse)
                                    @php
                                      $stockItem = $warehouseStocks[$warehouse->id . '_' . $variant->id] ?? null;
                                      $quantity = (int) ($stockItem->stock ?? 0);
                                      $variantWarehouseTotal += $quantity;
                                    @endphp
                                    <td>{{ $quantity }}</td>
                                  @endforeach
                                  <td><strong>{{ $variantWarehouseTotal }}</strong></td>
                                </tr>
                              @endforeach
                            </tbody>
                          </table>
                        </div>
                      @endif
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
                      <div class="alert alert-light border py-2 px-3 small mb-3" id="productAiBaseHint">Sin imagen base seleccionada.</div>
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
            <div class="modal fade" id="variantImagePreviewModal" tabindex="-1" aria-hidden="true">
              <div class="modal-dialog modal-xl modal-dialog-centered">
                <div class="modal-content">
                  <div class="modal-header">
                    <h5 class="modal-title" id="variantImagePreviewModalTitle">Imagen de variante</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                  </div>
                  <div class="modal-body text-center">
                    <img id="variantImagePreviewModalImg" src="" alt="Imagen variante completa" class="img-fluid rounded border">
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
    const productVariantUnitOptions = @json($productVariantUnitOptions);
    const productVariantQuantityModeOptions = @json($productVariantQuantityModeOptions);

    function buildVariantOptionTags(options, selectedValue = '') {
      return Object.entries(options || {}).map(([value, label]) => {
        const selected = String(selectedValue || '') === String(value) ? 'selected' : '';
        return `<option value="${value}" ${selected}>${label}</option>`;
      }).join('');
    }

    const tenantAiImageEndpoint = @json(route('tenant.ai-image'));
    const PRODUCT_ITEM_SAFE_IMAGE_BYTES = 1.2 * 1024 * 1024;
    let productAiModalInstance = null;
    let variantImagePreviewModalInstance = null;
    let productAiHistory = [];
    let productAiLatestResult = null;
    let productAiTarget = null;
    let productAiShouldReturnToAddImageModal = false;

    function showProductToast(message, type = 'info') {
      let container = document.getElementById('shopixToastContainer');
      if (!container) {
        container = document.createElement('div');
        container.id = 'shopixToastContainer';
        container.style.position = 'fixed';
        container.style.top = '1rem';
        container.style.right = '1rem';
        container.style.zIndex = '1080';
        container.style.display = 'flex';
        container.style.flexDirection = 'column';
        container.style.gap = '0.5rem';
        document.body.appendChild(container);
      }

      const colors = {
        info: '#1d4ed8',
        warning: '#b45309',
        error: '#b91c1c',
        success: '#166534',
      };

      const toast = document.createElement('div');
      toast.textContent = message;
      toast.style.minWidth = '260px';
      toast.style.maxWidth = '420px';
      toast.style.background = colors[type] || colors.info;
      toast.style.color = '#fff';
      toast.style.borderRadius = '10px';
      toast.style.padding = '0.7rem 0.9rem';
      toast.style.boxShadow = '0 8px 20px rgba(0, 0, 0, 0.2)';
      toast.style.opacity = '0';
      toast.style.transform = 'translateY(-8px)';
      toast.style.transition = 'opacity .2s ease, transform .2s ease';
      toast.style.fontSize = '0.92rem';
      container.appendChild(toast);

      requestAnimationFrame(() => {
        toast.style.opacity = '1';
        toast.style.transform = 'translateY(0)';
      });

      setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transform = 'translateY(-8px)';
        setTimeout(() => toast.remove(), 220);
      }, 3600);
    }

    function formatProductItemSize(bytes) {
      return `${(Number(bytes || 0) / (1024 * 1024)).toFixed(2)} MB`;
    }

    function parsePositiveProductAmount(value) {
      const amount = window.shopixParseDecimalInput
        ? window.shopixParseDecimalInput(value)
        : Number(value);
      return Number.isFinite(amount) && amount > 0 ? amount : null;
    }

    function parseProductInteger(value, minimum = 0) {
      const amount = Number(value);
      return Number.isInteger(amount) && amount >= minimum ? amount : null;
    }
    function parseProductStockAmount(value, minimum = 0) {
      const amount = window.shopixParseDecimalInput
        ? window.shopixParseDecimalInput(value)
        : Number(String(value ?? '').replace(',', '.'));
      return Number.isFinite(amount) && amount >= minimum ? Math.round(amount * 100) / 100 : null;
    }

    function loadProductImageElement(file) {
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

    function productCanvasToBlob(canvas, type, quality) {
      return new Promise((resolve) => canvas.toBlob(resolve, type, quality));
    }

    async function optimizeProductSingleImage(file) {
      const rasterTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
      const type = String(file.type || '').toLowerCase();
      if (!rasterTypes.includes(type)) {
        return { file, changed: false, convertedToWebp: false, stillLarge: file.size > PRODUCT_ITEM_SAFE_IMAGE_BYTES };
      }

      const img = await loadProductImageElement(file);
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
      let blob = await productCanvasToBlob(canvas, targetType, 0.9);

      while (blob && blob.size > PRODUCT_ITEM_SAFE_IMAGE_BYTES && width > 640 && height > 640) {
        width = Math.max(640, Math.round(width * 0.85));
        height = Math.max(640, Math.round(height * 0.85));
        canvas.width = width;
        canvas.height = height;
        ctx.drawImage(img, 0, 0, width, height);
        blob = await productCanvasToBlob(canvas, targetType, 0.82);
      }

      if (!blob) {
        return { file, changed: false, convertedToWebp: false, stillLarge: file.size > PRODUCT_ITEM_SAFE_IMAGE_BYTES };
      }

      const changed = convertedToWebp || blob.size !== file.size || width !== sourceWidth || height !== sourceHeight;
      if (!changed) {
        return { file, changed: false, convertedToWebp: false, stillLarge: blob.size > PRODUCT_ITEM_SAFE_IMAGE_BYTES };
      }

      const baseName = file.name.replace(/\.[^.]+$/, '');
      const optimized = new File([blob], `${baseName}.webp`, { type: targetType });

      return {
        file: optimized,
        changed: true,
        convertedToWebp,
        stillLarge: optimized.size > PRODUCT_ITEM_SAFE_IMAGE_BYTES,
      };
    }

    async function optimizeProductImageInput(inputId) {
      const input = document.getElementById(inputId);
      const file = input?.files?.[0];
      if (!file) {
        return { changed: false };
      }

      const originalSize = Number(file.size || 0);
      const optimized = await optimizeProductSingleImage(file);
      const optimizedSize = Number(optimized.file?.size || originalSize);
      const recommendedLimit = formatProductItemSize(PRODUCT_ITEM_SAFE_IMAGE_BYTES);
      if (optimized.changed) {
        const dt = new DataTransfer();
        dt.items.add(optimized.file);
        input.files = dt.files;

        let message = `Imagen optimizada automaticamente: ${formatProductItemSize(originalSize)} -> ${formatProductItemSize(optimizedSize)} (max recomendado ${recommendedLimit}).`;
        if (optimized.convertedToWebp) {
          message = `Imagen convertida a WEBP y optimizada: ${formatProductItemSize(originalSize)} -> ${formatProductItemSize(optimizedSize)} (max recomendado ${recommendedLimit}).`;
        }
        if (optimized.stillLarge) {
          message += ` Aun supera el maximo recomendado (${recommendedLimit}); baja la resolucion manualmente.`;
        }
        showProductToast(message, optimized.stillLarge ? 'warning' : 'info');
      } else if (optimized.stillLarge) {
        showProductToast(`La imagen pesa ${formatProductItemSize(optimizedSize)}. Recomendado por imagen: ${recommendedLimit}.`, 'warning');
      }

      return optimized;
    }

    function productAiCreateFileFromBase64(base64Data, mimeType, fileName = 'producto-gemini.png') {
      const bytes = atob(base64Data);
      const arr = new Uint8Array(bytes.length);
      for (let i = 0; i < bytes.length; i += 1) {
        arr[i] = bytes.charCodeAt(i);
      }
      const blob = new Blob([arr], { type: mimeType || 'image/png' });
      return new File([blob], fileName, { type: mimeType || 'image/png' });
    }

    function productAiSetTarget(target = {}) {
      productAiTarget = {
        input: target.input || null,
        inputId: target.inputId || null,
        preview: target.preview || null,
        previewId: target.previewId || null,
        existingImageUrl: target.existingImageUrl || '',
        title: target.title || 'imagen de producto',
      };
      productAiShouldReturnToAddImageModal = Boolean(target.returnToAddImageModal);
      document.getElementById('productAiBaseHint').textContent = productAiTarget.existingImageUrl || productAiTarget.input?.files?.[0]
        ? `Se usará como base la ${productAiTarget.title} actual si no adjuntas otra.`
        : 'Sin imagen base seleccionada.';
    }

    function productAiGetTargetInput() {
      return productAiTarget?.input || (productAiTarget?.inputId ? document.getElementById(productAiTarget.inputId) : null);
    }

    function productAiGetTargetPreview() {
      return productAiTarget?.preview || (productAiTarget?.previewId ? document.getElementById(productAiTarget.previewId) : null);
    }

    function productAiReadFileAsBase64(file) {
      return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () => {
          const dataUrl = String(reader.result || '');
          const base64 = dataUrl.includes(',') ? dataUrl.split(',')[1] : dataUrl;
          resolve({ data: base64, mime: file.type || 'image/png' });
        };
        reader.onerror = () => reject(new Error('No se pudo leer la imagen base.'));
        reader.readAsDataURL(file);
      });
    }

    async function productAiReadRemoteImage(url) {
      if (!url) return null;
      const response = await fetch(url);
      if (!response.ok) return null;
      const blob = await response.blob();
      const file = new File([blob], 'imagen-base', { type: blob.type || 'image/png' });
      return productAiReadFileAsBase64(file);
    }

    function openProductAiForTarget(target) {
      productAiHistory = [];
      productAiLatestResult = null;
      document.getElementById('productAiPrompt').value = '';
      document.getElementById('productAiReferenceImage').value = '';
      document.getElementById('productAiAttachedName').textContent = 'Sin imagen adjunta';
      document.getElementById('productAiChat').innerHTML = '';
      productAiSetTarget(target);
      productAiAppendMessage('assistant', `Describe cómo debe verse la ${productAiTarget?.title || 'imagen'} y generaré una versión.`);
      productAiRenderPreview();
      productAiSetLoading(false);

      if (productAiShouldReturnToAddImageModal) {
        const addImageModalEl = document.getElementById('addImageModal');
        const addImageModalInstance = bootstrap.Modal.getOrCreateInstance(addImageModalEl);
        addImageModalEl.addEventListener('hidden.bs.modal', () => productAiModalInstance.show(), { once: true });
        addImageModalInstance.hide();
        return;
      }

      productAiModalInstance.show();
    }

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

    async function productAiGetReferenceData() {
      const attachedFile = document.getElementById('productAiReferenceImage')?.files?.[0] || null;
      if (attachedFile) {
        return productAiReadFileAsBase64(attachedFile);
      }

      const targetInput = productAiGetTargetInput();
      const targetFile = targetInput?.files?.[0] || null;
      if (targetFile) {
        return productAiReadFileAsBase64(targetFile);
      }

      if (productAiTarget?.existingImageUrl) {
        return productAiReadRemoteImage(productAiTarget.existingImageUrl);
      }

      return null;
    }

    async function productAiApplyToInput(base64Data, mimeType, fileName = 'producto-gemini.png') {
      const input = productAiGetTargetInput();
      const preview = productAiGetTargetPreview();
      if (!input || !base64Data) {
        return;
      }

      const file = productAiCreateFileFromBase64(base64Data, mimeType, fileName);
      const dt = new DataTransfer();
      dt.items.add(file);
      input.files = dt.files;

      if (input.id) {
        await optimizeProductImageInput(input.id);
      } else {
        await optimizeProductImageInputForElement(input);
      }

      const optimizedFile = input.files?.[0] || file;
      if (preview) {
        preview.src = URL.createObjectURL(optimizedFile);
        preview.style.display = 'block';
      }
    }

    async function productAiSend() {
      const promptInput = document.getElementById('productAiPrompt');
      const prompt = String(promptInput.value || '').trim();
      if (!prompt) {
        showProductToast('Escribe un mensaje para generar la imagen.', 'warning');
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
        showProductToast(error.message || 'Error al generar imagen con IA.', 'error');
      } finally {
        productAiSetLoading(false);
      }
    }

    async function removeBackgroundFromUpload() {
      const input = document.getElementById('image');
      const file = input?.files?.[0];
      if (!file) {
        showProductToast('Primero sube una imagen para quitar el fondo.', 'warning');
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
        showProductToast('Fondo eliminado y imagen cargada en el formulario.', 'success');
      } catch (error) {
        showProductToast(error.message || 'Error al quitar fondo con IA.', 'error');
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

  document.querySelectorAll('.thumbnail img').forEach(img => {
    img.addEventListener('click', function() {
      document.getElementById('mainImage').src = this.src;
    });
  });
  const productCardForActions = document.querySelector('.card[data-product-id]');
  const inlineProductId = productCardForActions?.getAttribute('data-product-id');
  const productUpdateEndpointTemplate = "{{ route('products.updateWeb', ['id' => '__ID__']) }}";

  function createNewVariantRow() {
    const container = document.getElementById('newVariantContainer');
    if (!container) return;

    const row = document.createElement('div');
    row.className = 'border rounded p-3 new-variant-row';
    row.innerHTML = `
      <div class="row g-2 align-items-end">
        <div class="col-lg-2 col-md-6">
          <label class="form-label">Variante</label>
          <input type="text" class="form-control" data-new-size>
        </div>
        <div class="col-lg-2 col-md-6">
          <label class="form-label">Precio</label>
          <input type="number" class="form-control" data-new-price min="0.01" step="0.01" data-decimal-friendly="true">
        </div>
        <div class="col-lg-2 col-md-6">
          <label class="form-label">Desc. %</label>
          <input type="number" class="form-control" data-new-discount min="0" max="100" step="0.01" value="0" data-decimal-friendly="true">
        </div>
        <div class="col-lg-2 col-md-6">
          <label class="form-label">Unidad</label>
          <select class="form-control" data-new-unit-type>
            ${buildVariantOptionTags(productVariantUnitOptions, 'unidad')}
          </select>
        </div>
        <div class="col-lg-2 col-md-6">
          <label class="form-label">Venta mínima</label>
          <input type="number" class="form-control" data-new-min-sale-quantity min="0.01" step="0.01" value="1.00" data-decimal-friendly="true">
        </div>
        <div class="col-lg-2 col-md-6">
          <label class="form-label">Cantidad</label>
          <select class="form-control" data-new-quantity-mode>
            ${buildVariantOptionTags(productVariantQuantityModeOptions, 'integer')}
          </select>
        </div>
        <div class="col-lg-1 col-md-6">
          <label class="form-label">Stock</label>
          <input type="number" class="form-control" data-new-stock min="0" step="1">
        </div>
        <div class="col-lg-2 col-md-6">
          <label class="form-label">Código barras</label>
          <input type="text" class="form-control" data-new-barcode>
        </div>
        <div class="col-lg-2 col-md-6">
          <label class="form-label">Imagen</label>
          <input type="file" class="form-control new-variant-image" accept="image/*">
        </div>
      </div>
      <div class="d-flex justify-content-end gap-2 mt-2">
        <button type="button" class="btn btn-outline-dark btn-sm open-new-variant-ai-btn">IA imagen</button>
        <button type="button" class="btn btn-outline-danger btn-sm remove-new-variant-btn">Eliminar</button>
      </div>
    `;

    row.querySelector('.remove-new-variant-btn')?.addEventListener('click', () => row.remove());
    row.querySelector('.new-variant-image')?.addEventListener('change', async function () {
      await optimizeProductImageInputForElement(this);
    });
    row.querySelector('.open-new-variant-ai-btn')?.addEventListener('click', () => {
      const imageInput = row.querySelector('.new-variant-image');
      openProductAiForTarget({
        input: imageInput,
        title: 'imagen de variante',
      });
    });

    container.appendChild(row);
  }

  async function optimizeProductImageInputForElement(inputEl) {
    const file = inputEl?.files?.[0];
    if (!file) return;

    const optimized = await optimizeProductSingleImage(file);
    if (optimized.changed) {
      const dt = new DataTransfer();
      dt.items.add(optimized.file);
      inputEl.files = dt.files;
    }
  }

  document.getElementById('addVariantBtn')?.addEventListener('click', createNewVariantRow);

  document.querySelectorAll('.existing-variant-image-input').forEach((input) => {
    input.addEventListener('change', async function () {
      await optimizeProductImageInputForElement(this);
      const variantId = this.getAttribute('data-existing-image');
      const preview = document.getElementById(`variantPreview-${variantId}`);
      const file = this.files?.[0];
      if (preview && file) {
        preview.src = URL.createObjectURL(file);
      }
    });
  });

  function openVariantImagePreview(src, label) {
    const imageEl = document.getElementById('variantImagePreviewModalImg');
    const titleEl = document.getElementById('variantImagePreviewModalTitle');
    if (!imageEl || !variantImagePreviewModalInstance || !src) {
      return;
    }

    imageEl.src = src;
    titleEl.textContent = label || 'Imagen de variante';
    variantImagePreviewModalInstance.show();
  }

  document.querySelectorAll('[data-variant-image-preview]').forEach((preview) => {
    preview.addEventListener('click', function () {
      openVariantImagePreview(this.getAttribute('src') || '', this.dataset.imageLabel || 'Imagen de variante');
    });
  });

  document.querySelectorAll('.open-existing-variant-ai-btn').forEach((button) => {
    button.addEventListener('click', function () {
      const variantId = this.getAttribute('data-variant-id');
      const row = document.querySelector(`[data-existing-variant-row="${variantId}"]`);
      const input = row?.querySelector('.existing-variant-image-input');
      const preview = document.getElementById(`variantPreview-${variantId}`);
      if (!row || !input) return;

      openProductAiForTarget({
        input,
        preview,
        title: 'imagen de variante',
        existingImageUrl: preview?.getAttribute('src') || '',
      });
    });
  });

  document.getElementById('saveVariantsBtn')?.addEventListener('click', async function () {
    if (!inlineProductId) return;

    const variants = [];
    const formData = new FormData();
    formData.append('product_id', inlineProductId);

    const rows = Array.from(document.querySelectorAll('#newVariantContainer .new-variant-row'));
    rows.forEach((row, index) => {
      const size = row.querySelector('[data-new-size]')?.value?.trim();
      const price = parsePositiveProductAmount(row.querySelector('[data-new-price]')?.value);
      const discount_percentage = row.querySelector('[data-new-discount]')?.value;
      const stock = parseProductStockAmount(row.querySelector('[data-new-stock]')?.value, 0);
      const barcode = row.querySelector('[data-new-barcode]')?.value?.trim();
      const unit_type = row.querySelector('[data-new-unit-type]')?.value || 'unidad';
      const quantity_input_mode = row.querySelector('[data-new-quantity-mode]')?.value || 'integer';
      const min_sale_quantity = parseProductStockAmount(row.querySelector('[data-new-min-sale-quantity]')?.value, 0.01);

      if (size && price !== null && stock !== null && min_sale_quantity !== null) {
        variants.push({ size, price, discount_percentage: discount_percentage || 0, stock, barcode, unit_type, quantity_input_mode, min_sale_quantity });
        const imageInput = row.querySelector('.new-variant-image');
        if (imageInput?.files?.[0]) {
          formData.append(`variant_images[${index}]`, imageInput.files[0]);
        }
      }
    });

    if (!variants.length) {
      alert('Agrega al menos una variante nueva válida.');
      return;
    }

    formData.append('variants', JSON.stringify(variants));

    try {
      const response = await fetch('/api/variants/store', {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
          'Accept': 'application/json',
        },
        body: formData,
      });

      const payload = await response.json().catch(() => ({}));
      if (!response.ok || !payload.success) {
        const validationMessage = payload?.errors
          ? Object.values(payload.errors).flat().join('\n')
          : null;
        throw new Error(validationMessage || payload.message || 'No se pudieron guardar las variantes nuevas.');
      }

      alert(payload.message || 'Variantes guardadas exitosamente.');
      window.location.reload();
    } catch (error) {
      alert(error.message || 'Error al guardar variantes.');
    }
  });

  variantImagePreviewModalInstance = new bootstrap.Modal(document.getElementById('variantImagePreviewModal'));

  document.getElementById('saveProductTaxesBtn')?.addEventListener('click', function () {
    if (!inlineProductId) return;

    if (!@json((bool) ($canEditProductTaxes ?? false))) {
      alert('Las alícuotas del producto están bloqueadas hasta contar con habilitación de imprenta.');
      return;
    }

    const selectedTaxIds = [...document.querySelectorAll('.tax-checkbox:checked')].map(cb => cb.value);

    fetch(`/products/${inlineProductId}/taxes`, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
      },
      body: JSON.stringify({ taxes: selectedTaxIds }),
    })
      .then(res => res.json())
      .then(data => {
        if (data.success) {
          alert('Impuestos del producto actualizados.');
          location.reload();
        } else {
          alert(data.message || 'Hubo un error al actualizar impuestos.');
        }
      })
      .catch(() => alert('Hubo un error al actualizar impuestos.'));
  });

  document.querySelectorAll('.save-existing-variant-btn').forEach((button) => {
    button.addEventListener('click', async function () {
      const variantId = this.getAttribute('data-variant-id');
      const row = document.querySelector(`[data-existing-variant-row="${variantId}"]`);
      if (!variantId || !row) return;

      const formData = new FormData();
      const size = row.querySelector('[data-existing-size]')?.value?.trim() || '';
      const price = parsePositiveProductAmount(row.querySelector('[data-existing-price]')?.value);
      const stock = parseProductStockAmount(row.querySelector('[data-existing-stock]')?.value, 0);

      if (!size) {
        alert('La variante debe tener un nombre.');
        return;
      }

      if (price === null) {
        alert('El precio de la variante debe ser mayor a cero.');
        return;
      }

      if (stock === null) {
        alert('El stock de la variante no puede ser negativo.');
        return;
      }

      formData.append('size', size);
      formData.append('price', String(price));
      formData.append('discount_percentage', row.querySelector('[data-existing-discount]')?.value || '0');
      formData.append('stock', String(stock));
      formData.append('barcode', row.querySelector('[data-existing-barcode]')?.value || '');
      formData.append('unit_type', row.querySelector('[data-existing-unit-type]')?.value || 'unidad');
      formData.append('quantity_input_mode', row.querySelector('[data-existing-quantity-mode]')?.value || 'integer');
      formData.append('min_sale_quantity', row.querySelector('[data-existing-min-sale-quantity]')?.value || '1');

      const imageInput = row.querySelector('.existing-variant-image-input');
      if (imageInput?.files?.[0]) {
        formData.append('image', imageInput.files[0]);
      }

      const originalText = this.textContent;
      this.disabled = true;
      this.textContent = 'Guardando...';

      try {
        const response = await fetch(`/api/variants/${variantId}`, {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            'Accept': 'application/json',
          },
          body: (() => {
            formData.append('_method', 'PUT');
            return formData;
          })(),
        });

        const payload = await response.json().catch(() => ({}));
        if (!response.ok || !payload.success) {
          const validationMessage = payload?.errors
            ? Object.values(payload.errors).flat().join('\n')
            : null;
          throw new Error(validationMessage || payload.message || 'No se pudo actualizar la variante.');
        }

        const displayBarcode = document.getElementById(`variantBarcode-${variantId}`);
        const barcodeInput = row.querySelector('[data-existing-barcode]');
        if (displayBarcode) displayBarcode.textContent = payload?.variant?.barcode || barcodeInput?.value || '—';

        alert(payload.message || 'Variante actualizada correctamente.');
      } catch (error) {
        alert(error.message || 'No se pudo actualizar la variante.');
      } finally {
        this.disabled = false;
        this.textContent = originalText;
      }
    });
  });
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

async function deleteProduct(productId) {
  if (!confirm('¿Estás seguro de que deseas eliminar este producto?')) {
    return;
  }

  try {
    const response = await fetch(`/api/products/${productId}`, {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        'Accept': 'application/json',
      },
    });

    const payload = await response.json().catch(() => ({}));

    if (response.ok && payload.success) {
      alert(payload.message || 'Producto eliminado correctamente');
      window.location.href = "{{ route('products.index') }}";
      return;
    }

    throw new Error(payload.message || 'Error al eliminar el producto');
  } catch (error) {
    console.error('Error:', error);
    alert(error.message || 'Error al eliminar el producto');
  }
}

document.getElementById('editProductForm')?.addEventListener('submit', function(event) {
  event.preventDefault(); // Evitar que se recargue la página
    let formData = new FormData(this);
    const normalizedName = (document.getElementById('editProductName')?.value || '').trim();
    formData.set('name', normalizedName);

    const productId = inlineProductId;
    if (!productId) {
      alert('No se pudo identificar el producto a actualizar.');
      return;
    }

    const updateUrl = productUpdateEndpointTemplate.replace('__ID__', encodeURIComponent(String(productId)));

    fetch(updateUrl, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
        },
        credentials: 'same-origin',
        body: formData,
    })
    .then(async response => {
        const payload = await response.json().catch(() => ({}));

        if (response.ok && payload.success) {
          alert(payload.message || 'Producto actualizado correctamente');
          window.location.reload();
          return;
        }

        const validationMessage = payload?.errors
          ? Object.values(payload.errors).flat().join('\n')
          : null;

        throw new Error(validationMessage || payload.message || 'Error al actualizar el producto');
      })
    .catch(error => {
        console.error('Error:', error);
        alert(error.message || 'Error al actualizar el producto');
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
  const variantImageSelector = document.getElementById('variantImageSelector');

  const resolveAddImageBaseUrl = () => {
    const selectedVariantId = variantImageSelector?.value || '';
    if (selectedVariantId) {
      return document.getElementById(`variantPreview-${selectedVariantId}`)?.getAttribute('src') || '';
    }

    return document.getElementById('mainImage')?.getAttribute('src') || '';
  };

  document.getElementById('image').addEventListener('change', async function () {
    await optimizeProductImageInput('image');
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

  document.getElementById('addImageForm')?.addEventListener('submit', async function (event) {
    event.preventDefault();
    const optimized = await optimizeProductImageInput('image');
    if (optimized?.stillLarge) {
      showProductToast('La imagen sigue demasiado pesada. Baja más la resolución antes de guardar.', 'warning');
      return;
    }
    this.submit();
  });

  document.getElementById('openProductAiBtn').addEventListener('click', () => {
    openProductAiForTarget({
      inputId: 'image',
      previewId: 'addImagePreview',
      existingImageUrl: resolveAddImageBaseUrl(),
      title: variantImageSelector?.value ? 'imagen de la variante seleccionada' : 'imagen principal del producto',
      returnToAddImageModal: true,
    });
  });

  productAiModalEl.addEventListener('hidden.bs.modal', () => {
    if (productAiShouldReturnToAddImageModal) {
      addImageModalInstance.show();
    }
    productAiShouldReturnToAddImageModal = false;
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
    const file = productAiCreateFileFromBase64(
      productAiLatestResult.base64Data,
      productAiLatestResult.mimeType,
      productAiLatestResult.fileName || 'producto-gemini.png'
    );
    const blob = new Blob([file], { type: productAiLatestResult.mimeType || 'image/png' });
    const url = URL.createObjectURL(blob);
    const link = document.createElement('a');
    link.href = url;
    link.download = file.name;
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
