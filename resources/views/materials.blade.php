@extends('layouts.app')

@section('title', 'Lista de Materiales')

@section('content')
<div class="container-fluid py-2">
  <div class="row mt-4">
    <div class="col-12">
      <div class="card mb-4">
        <div class="card-header p-3">
          <h5 class="mb-1">Crear paquete / lista de materiales</h5>
          <p class="text-sm text-muted mb-0">Define cada material en modo variante fija (como antes) o modo producto flexible para escoger sabores al vender.</p>
        </div>
        <div class="card-body p-3">
          @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
          @endif

          @if($errors->any())
            <div class="alert alert-danger mb-3">
              {{ $errors->first() }}
            </div>
          @endif

          @if($isBasicPlanTenant ?? false)
            <div class="alert alert-warning mb-0">
              El plan Básico no permite crear listas de materiales.
            </div>
          @else
          <form method="POST" action="{{ route('materials.store') }}" id="materialPackageForm">
            @csrf
            <div class="row g-3">
              <div class="col-12 col-md-4">
                <label class="form-label">Nombre del paquete</label>
                <input type="text" class="form-control border border-1 p-2 bg-white" name="name" placeholder="Ej: Combo quirúrgico" required>
              </div>
              <div class="col-12 col-md-5">
                <label class="form-label">Descripción</label>
                <input type="text" class="form-control border border-1 p-2 bg-white" name="description" placeholder="Ej: Incluye guantes, mascarillas y bata.">
              </div>
              <div class="col-12 col-md-3">
                <label class="form-label">Descuento (%)</label>
                <input type="number" min="0" max="100" step="0.01" class="form-control border border-1 p-2 bg-white" name="discount_percentage" value="0" placeholder="0" data-decimal-friendly="true">
              </div>
              <div class="col-12 col-md-3">
                <label class="form-label">Precio fijo del paquete ($)</label>
                <input type="number" min="0.01" step="0.01" class="form-control border border-1 p-2 bg-white" name="package_price" placeholder="Opcional" data-decimal-friendly="true">
              </div>
            </div>

            <hr>

            <div class="d-flex justify-content-between align-items-center mb-2">
              <h6 class="mb-0">Materiales del paquete</h6>
              <button type="button" class="btn btn-outline-dark btn-sm mb-0" id="addMaterialRow">+ Agregar material</button>
            </div>

            <div id="materialsRows" class="d-flex flex-column gap-2"></div>

            <div class="mt-3">
              <button type="submit" class="btn btn-info mb-0">Guardar paquete</button>
            </div>
          </form>
          @endif
        </div>
      </div>
    </div>

    <div class="col-12">
      <div class="card">
        <div class="card-header p-3 d-flex justify-content-between align-items-center">
          <h6 class="mb-0">Paquetes creados</h6>
          <span class="text-sm text-muted">{{ $packages->count() }} registros</span>
        </div>
        <div class="card-body p-3">
          @if($packages->isEmpty())
            <p class="text-muted mb-0">No hay paquetes registrados todavía.</p>
          @else
            <div class="table-responsive">
              <table class="table align-items-center mb-0">
                <thead>
                  <tr>
                    <th>Imagen</th>
                    <th>Paquete</th>
                    <th>Estado</th>
                    <th>Materiales</th>
                    <th>Acción</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($packages as $package)
                    @php
                      $previewItem = $package->items->first();
                      $previewImage = $previewItem && $previewItem->variant && $previewItem->variant->product && isset($previewItem->variant->product->images[0])
                        ? (
                            \App\Support\ImageStorage::url($previewItem->variant->product->images[0]->path)
                            ?? asset('assets/img/shopix5.png')
                          )
                        : asset('assets/img/shopix5.png');
                    @endphp
                    <tr>
                      <td>
                        <img src="{{ $previewImage }}" alt="{{ $package->name }}" style="width:56px; height:56px; object-fit:cover; border-radius:10px; border:1px solid #e2e8f0;">
                      </td>
                      <td>
                        <div class="fw-semibold">{{ $package->name }}</div>
                        <div class="text-xs text-muted">{{ $package->description ?: 'Sin descripción' }}</div>
                        @if((float) ($package->discount_percentage ?? 0) > 0)
                          <div class="text-xs text-success fw-semibold">Descuento: {{ number_format((float) $package->discount_percentage, 2) }}%</div>
                        @endif
                        @if(!is_null($package->package_price))
                          <div class="text-xs text-dark fw-semibold">Precio combo: {{ number_format((float) $package->package_price, 2) }} $</div>
                        @endif
                      </td>
                      <td>
                        <span class="badge {{ $package->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $package->is_active ? 'Activo' : 'Inactivo' }}</span>
                      </td>
                      <td>
                        <ul class="mb-0 ps-3">
                          @foreach($package->items as $item)
                            @php
                              $itemImage = $item->variant && $item->variant->product && isset($item->variant->product->images[0])
                                ? (\App\Support\ImageStorage::url($item->variant->product->images[0]->path) ?? asset('assets/img/shopix5.png'))
                                : asset('assets/img/shopix5.png');
                            @endphp
                            <li class="text-sm d-flex align-items-center gap-2">
                              <img src="{{ $itemImage }}" alt="{{ $item->variant?->product?->name ?? 'Producto' }}" style="width:28px; height:28px; object-fit:cover; border-radius:6px; border:1px solid #e2e8f0;">
                              <span>
                                {{ $item->variant?->product?->name ?? 'Producto' }} - {{ $item->variant?->size ?? 'Variante' }} (x{{ rtrim(rtrim(number_format($item->quantity, 2, '.', ''), '0'), '.') }})
                                @if(($item->selection_mode ?? 'variant') === 'product')
                                  <span class="badge bg-info ms-1">Flexible</span>
                                @else
                                  <span class="badge bg-secondary ms-1">Fijo</span>
                                @endif
                              </span>
                            </li>
                          @endforeach
                        </ul>
                      </td>
                      <td>
                        <div class="d-flex gap-2 flex-wrap">
                          <button
                            type="button"
                            class="btn btn-sm btn-outline-info mb-0 edit-package-btn"
                            data-package-id="{{ $package->id }}">
                            Editar
                          </button>

                          <form method="POST" action="{{ route('materials.toggleStatus', $package->id) }}" @if($package->is_active) data-requires-action-reason="true" data-reason-field="action_reason" data-reason-prompt="Indica el motivo para desactivar este paquete." @endif>
                            @csrf
                            <button class="btn btn-sm btn-outline-secondary mb-0" type="submit">
                              {{ $package->is_active ? 'Desactivar' : 'Activar' }}
                            </button>
                          </form>
                        </div>
                      </td>
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

<div class="modal fade" id="editMaterialPackageModal" tabindex="-1" aria-labelledby="editMaterialPackageModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="editMaterialPackageModalLabel">Editar paquete / lista de materiales</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <form method="POST" id="editMaterialPackageForm" data-update-template="{{ route('materials.update', ['id' => '__ID__']) }}">
        @csrf
        @method('PUT')
        <div class="modal-body">
          <div class="row g-3">
            <div class="col-12 col-md-4">
              <label class="form-label">Nombre del paquete</label>
              <input type="text" class="form-control border border-1 p-2 bg-white" name="name" id="editPackageName" required>
            </div>
            <div class="col-12 col-md-5">
              <label class="form-label">Descripción</label>
              <input type="text" class="form-control border border-1 p-2 bg-white" name="description" id="editPackageDescription">
            </div>
            <div class="col-12 col-md-3">
              <label class="form-label">Descuento (%)</label>
              <input type="number" min="0" max="100" step="0.01" class="form-control border border-1 p-2 bg-white" name="discount_percentage" id="editPackageDiscount" value="0" data-decimal-friendly="true">
            </div>
            <div class="col-12 col-md-4">
              <label class="form-label">Precio fijo del paquete ($)</label>
              <input type="number" min="0.01" step="0.01" class="form-control border border-1 p-2 bg-white" name="package_price" id="editPackagePrice" placeholder="Opcional" data-decimal-friendly="true">
            </div>
          </div>

          <hr>

          <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="mb-0">Materiales del paquete</h6>
            <button type="button" class="btn btn-outline-dark btn-sm mb-0" id="addEditMaterialRow">+ Agregar material</button>
          </div>

          <div id="editMaterialsRows" class="d-flex flex-column gap-2"></div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-info" id="editPackageSubmitBtn" disabled>Guardar cambios</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<style>
  .select2-container--default .select2-selection--single {
    min-height: 42px;
    border: 1px solid #d2d6da;
    border-radius: 0.5rem;
    padding: 0.45rem 0.75rem;
  }

  .select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 1.5rem;
    padding-left: 0;
  }

  .select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 40px;
    right: 8px;
  }

  .select2-container {
    width: 100% !important;
  }
</style>
@endpush

@push('scripts')
@php
  $packageCatalogData = ($packages ?? collect())->map(function ($package) {
    return [
      'id' => (int) $package->id,
      'name' => (string) $package->name,
      'description' => (string) ($package->description ?? ''),
      'discount_percentage' => (float) ($package->discount_percentage ?? 0),
      'package_price' => !is_null($package->package_price) ? (float) $package->package_price : null,
      'items' => ($package->items ?? collect())->map(function ($item) {
        return [
          'selection_mode' => (string) ($item->selection_mode ?? 'variant'),
          'variant_id' => (int) ($item->product_variant_id ?? 0),
          'product_id' => (int) ($item->variant->product_id ?? 0),
          'quantity' => (float) ($item->quantity ?? 0),
        ];
      })->values()->toArray(),
    ];
  })->values()->toArray();
@endphp
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const rowsContainer = document.getElementById('materialsRows');
    const addRowBtn = document.getElementById('addMaterialRow');
    const editRowsContainer = document.getElementById('editMaterialsRows');
    const addEditRowBtn = document.getElementById('addEditMaterialRow');
    const editPackageButtons = document.querySelectorAll('.edit-package-btn');
    const editForm = document.getElementById('editMaterialPackageForm');
    const editSubmitBtn = document.getElementById('editPackageSubmitBtn');
    const editModalElement = document.getElementById('editMaterialPackageModal');
    const editModal = editModalElement ? bootstrap.Modal.getOrCreateInstance(editModalElement) : null;
    let editFormInitialState = '';
    const fallbackImage = @json(asset('assets/img/shopix5.png'));

    const packageCatalog = @json($packageCatalogData);

    const variantMeta = {
      @foreach($productItems as $product)
        @foreach($product->variants as $variant)
          "{{ $variant->id }}": {
            name: @json($product->name),
            size: @json($variant->size),
            stock: @json($variant->stock),
            image: @json((isset($product->images[0]) ? (\App\Support\ImageStorage::url($product->images[0]->path) ?? asset('assets/img/shopix5.png')) : asset('assets/img/shopix5.png'))),
          },
        @endforeach
      @endforeach
    };

    const productMeta = {
      @foreach($productItems as $product)
        "{{ $product->id }}": {
          name: @json($product->name),
          stock: @json((float) $product->variants->sum('stock')),
          image: @json((isset($product->images[0]) ? (\App\Support\ImageStorage::url($product->images[0]->path) ?? asset('assets/img/shopix5.png')) : asset('assets/img/shopix5.png'))),
        },
      @endforeach
    };

    const variantOptions = `
      <option value="">Selecciona variante...</option>
      @foreach($productItems as $product)
        @foreach($product->variants as $variant)
          <option value="{{ $variant->id }}">{{ $product->name }} - {{ $variant->size }} (Stock: {{ $variant->stock }})</option>
        @endforeach
      @endforeach
    `;

    const productOptions = `
      <option value="">Selecciona producto...</option>
      @foreach($productItems as $product)
          <option value="{{ $product->id }}">{{ $product->name }} (Stock total: {{ number_format((float) $product->variants->sum('stock'), 2, '.', '') }})</option>
      @endforeach
    `;

    let rowIndex = 0;
    let editRowIndex = 0;

    function initializeMaterialSelect2(rowElement) {
      if (!rowElement || !window.jQuery || !window.jQuery.fn.select2) {
        return;
      }

      const $row = window.jQuery(rowElement);
      const $selects = $row.find('.js-material-variant-select, .js-material-product-select');
      const $dropdownParent = $row.closest('.modal-content').length ? $row.closest('.modal-content') : window.jQuery(document.body);

      $selects.each(function () {
        const $select = window.jQuery(this);
        if ($select.hasClass('select2-hidden-accessible')) {
          $select.select2('destroy');
        }

        const placeholder = $select.hasClass('js-material-product-select')
          ? 'Escribe para buscar un producto...'
          : 'Escribe para buscar una variante...';

        $select.select2({
          width: '100%',
          placeholder,
          allowClear: true,
          dropdownParent: $dropdownParent,
        });
      });
    }

    function refreshRowPreview(rowElement) {
      if (!rowElement) return;

      const modeSelect = rowElement.querySelector('.js-material-selection-mode');
      const variantSelect = rowElement.querySelector('.js-material-variant-select');
      const productSelect = rowElement.querySelector('.js-material-product-select');
      const image = rowElement.querySelector('.js-material-item-image');
      const label = rowElement.querySelector('.js-material-item-label');

      if (!modeSelect || !variantSelect || !productSelect || !image || !label) return;

      const mode = modeSelect.value || 'variant';
      const variantWrap = rowElement.querySelector('.js-variant-select-wrap');
      const productWrap = rowElement.querySelector('.js-product-select-wrap');

      if (mode === 'variant') {
        variantWrap?.classList.remove('d-none');
        productWrap?.classList.add('d-none');
        variantSelect.required = true;
        variantSelect.disabled = false;
        productSelect.required = false;
        productSelect.disabled = true;
        productSelect.value = '';

        const meta = variantMeta[String(variantSelect.value)] || null;
        if (!meta) {
          image.src = fallbackImage;
          label.textContent = 'Selecciona una variante fija para este material';
          return;
        }

        image.src = meta.image || fallbackImage;
        label.textContent = `${meta.name || 'Producto'} - ${meta.size || 'Variante'} (Stock: ${meta.stock ?? 0})`;
        return;
      }

      variantWrap?.classList.add('d-none');
      productWrap?.classList.remove('d-none');
      variantSelect.required = false;
      variantSelect.disabled = true;
      variantSelect.value = '';
      productSelect.required = true;
      productSelect.disabled = false;

      const meta = productMeta[String(productSelect.value)] || null;
      if (!meta) {
        image.src = fallbackImage;
        label.textContent = 'Selecciona un producto flexible para definir sabores en la venta';
        return;
      }

      image.src = meta.image || fallbackImage;
      label.textContent = `${meta.name || 'Producto'} (Stock total: ${meta.stock ?? 0})`;
    }

    function buildRowHtml(index, seed = {}) {
      const selectedMode = String(seed.selection_mode || 'variant');
      const selectedVariantId = seed.variant_id ? String(seed.variant_id) : '';
      const selectedProductId = seed.product_id ? String(seed.product_id) : '';
      const selectedQuantity = String(seed.quantity ?? 1);

      const scopedVariantOptions = variantOptions.replace(
        `value="${selectedVariantId}"`,
        `value="${selectedVariantId}" selected`
      );

      const scopedProductOptions = productOptions.replace(
        `value="${selectedProductId}"`,
        `value="${selectedProductId}" selected`
      );

      return `
        <div class="row g-2 align-items-end">
          <div class="col-12 col-md-2">
            <label class="form-label mb-1">Imagen</label>
            <div class="border rounded d-flex align-items-center justify-content-center bg-white" style="height: 62px; overflow: hidden;">
              <img src="${fallbackImage}" alt="Vista previa" class="js-material-item-image" style="width: 100%; height: 100%; object-fit: cover;">
            </div>
          </div>
          <div class="col-12 col-md-6">
            <label class="form-label mb-1">Modo</label>
            <select name="items[${index}][selection_mode]" class="form-select border border-1 bg-white js-material-selection-mode" required>
              <option value="variant" ${selectedMode === 'variant' ? 'selected' : ''}>Variante fija (modo clásico)</option>
              <option value="product" ${selectedMode === 'product' ? 'selected' : ''}>Producto flexible (sabores en venta)</option>
            </select>
            <div class="js-variant-select-wrap mt-2 ${selectedMode === 'product' ? 'd-none' : ''}">
              <select name="items[${index}][variant_id]" class="form-select border border-1 bg-white js-material-variant-select" ${selectedMode === 'variant' ? 'required' : ''}>
                ${scopedVariantOptions}
              </select>
            </div>
            <div class="js-product-select-wrap mt-2 ${selectedMode === 'product' ? '' : 'd-none'}">
              <select name="items[${index}][product_id]" class="form-select border border-1 bg-white js-material-product-select" ${selectedMode === 'product' ? 'required' : ''}>
                ${scopedProductOptions}
              </select>
            </div>
            <p class="text-xs text-muted mb-0 mt-1 js-material-item-label">Selecciona una variante fija para este material</p>
          </div>
          <div class="col-8 col-md-3">
            <label class="form-label mb-1">Cantidad</label>
            <input type="text" name="items[${index}][quantity]" class="form-control border border-1 p-2 bg-white" inputmode="decimal" pattern="^[0-9]+(\.[0-9]+)?$" placeholder="1" value="${selectedQuantity}" required>
          </div>
          <div class="col-4 col-md-1">
            <button type="button" class="btn btn-outline-danger w-100 remove-row">X</button>
          </div>
        </div>
      `;
    }

    function appendCreateRow(seed = {}) {
      if (!rowsContainer) {
        return;
      }

      rowIndex += 1;
      const row = document.createElement('div');
      row.className = 'border rounded p-2';
      row.innerHTML = buildRowHtml(rowIndex, seed);
      rowsContainer.appendChild(row);
      initializeMaterialSelect2(row);
      refreshRowPreview(row);
    }

    function appendEditRow(seed = {}) {
      editRowIndex += 1;
      const row = document.createElement('div');
      row.className = 'border rounded p-2';
      row.innerHTML = buildRowHtml(editRowIndex, seed);
      editRowsContainer?.appendChild(row);
      initializeMaterialSelect2(row);
      refreshRowPreview(row);
    }

    function serializeEditFormState() {
      if (!editForm) {
        return '';
      }

      const fields = Array.from(editForm.querySelectorAll('input, select, textarea'));
      const pairs = fields
        .filter((field) => field.name && field.type !== 'hidden')
        .map((field) => {
          if (field.type === 'checkbox') {
            return `${field.name}:${field.checked ? '1' : '0'}`;
          }

          return `${field.name}:${String(field.value ?? '').trim()}`;
        })
        .sort();

      return pairs.join('|');
    }

    function refreshEditSubmitState() {
      if (!editSubmitBtn) {
        return;
      }

      const currentState = serializeEditFormState();
      editSubmitBtn.disabled = currentState === editFormInitialState;
    }

    if (rowsContainer) {
      appendCreateRow();
    }

    addRowBtn?.addEventListener('click', () => appendCreateRow());
    addEditRowBtn?.addEventListener('click', () => appendEditRow());

    rowsContainer?.addEventListener('click', function (event) {
      if (!event.target.classList.contains('remove-row')) return;
      const allRows = rowsContainer.querySelectorAll('.border.rounded.p-2');
      if (allRows.length <= 1) {
        return;
      }
      event.target.closest('.border.rounded.p-2')?.remove();
    });

    rowsContainer?.addEventListener('change', function (event) {
      if (!event.target.classList.contains('js-material-selection-mode')
          && !event.target.classList.contains('js-material-product-select')
          && !event.target.classList.contains('js-material-variant-select')) {
        return;
      }
      const row = event.target.closest('.border.rounded.p-2');
      refreshRowPreview(row);
    });

    editRowsContainer?.addEventListener('click', function (event) {
      if (!event.target.classList.contains('remove-row')) return;
      const allRows = editRowsContainer.querySelectorAll('.border.rounded.p-2');
      if (allRows.length <= 1) {
        return;
      }
      event.target.closest('.border.rounded.p-2')?.remove();
    });

    editRowsContainer?.addEventListener('change', function (event) {
      if (!event.target.classList.contains('js-material-selection-mode')
          && !event.target.classList.contains('js-material-product-select')
          && !event.target.classList.contains('js-material-variant-select')) {
        return;
      }
      const row = event.target.closest('.border.rounded.p-2');
      refreshRowPreview(row);
      refreshEditSubmitState();
    });

    editRowsContainer?.addEventListener('input', refreshEditSubmitState);
    editForm?.addEventListener('input', refreshEditSubmitState);
    editForm?.addEventListener('change', refreshEditSubmitState);

    editForm?.addEventListener('submit', function (event) {
      const currentState = serializeEditFormState();
      if (currentState === editFormInitialState) {
        event.preventDefault();
        return;
      }

      const confirmed = window.confirm('¿Deseas guardar los cambios de esta lista de materiales?');
      if (!confirmed) {
        event.preventDefault();
      }
    });

    editPackageButtons.forEach((button) => {
      button.addEventListener('click', function () {
        const packageId = Number(this.dataset.packageId || 0);
        const pkg = packageCatalog.find((item) => Number(item.id) === packageId);
        if (!pkg || !editForm) {
          return;
        }

        const updateTemplate = editForm.dataset.updateTemplate || '';
        editForm.action = updateTemplate.replace('__ID__', String(packageId));
        document.getElementById('editPackageName').value = pkg.name || '';
        document.getElementById('editPackageDescription').value = pkg.description || '';
        document.getElementById('editPackageDiscount').value = Number(pkg.discount_percentage || 0);
        document.getElementById('editPackagePrice').value = (pkg.package_price === null || pkg.package_price === undefined) ? '' : Number(pkg.package_price || 0);

        if (editRowsContainer) {
          editRowsContainer.innerHTML = '';
        }
        editRowIndex = 0;

        const items = Array.isArray(pkg.items) ? pkg.items : [];
        if (items.length === 0) {
          appendEditRow();
        } else {
          items.forEach((item) => appendEditRow(item));
        }

        editFormInitialState = serializeEditFormState();
        refreshEditSubmitState();

        editModal?.show();
      });
    });
  });
</script>
@endpush
