@extends('layouts.app')

@section('title', 'Lista de Materiales')

@section('content')
<div class="container-fluid py-2">
  <div class="row mt-4">
    <div class="col-12">
      <div class="card mb-4">
        <div class="card-header p-3">
          <h5 class="mb-1">Crear paquete / lista de materiales</h5>
          <p class="text-sm text-muted mb-0">Combina variantes de productos para crear combos, docenas u outfits.</p>
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
                <input type="number" min="0" max="100" step="0.01" class="form-control border border-1 p-2 bg-white" name="discount_percentage" value="0" placeholder="0">
              </div>
              <div class="col-12 col-md-3">
                <label class="form-label">Precio fijo del paquete ($)</label>
                <input type="number" min="0.01" step="0.01" class="form-control border border-1 p-2 bg-white" name="package_price" placeholder="Opcional">
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
                              <span>{{ $item->variant?->product?->name ?? 'Producto' }} - {{ $item->variant?->size ?? 'Variante' }} (x{{ rtrim(rtrim(number_format($item->quantity, 2, '.', ''), '0'), '.') }})</span>
                            </li>
                          @endforeach
                        </ul>
                      </td>
                      <td>
                        <form method="POST" action="{{ route('materials.toggleStatus', $package->id) }}">
                          @csrf
                          <button class="btn btn-sm btn-outline-secondary mb-0" type="submit">
                            {{ $package->is_active ? 'Desactivar' : 'Activar' }}
                          </button>
                        </form>
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
@endsection

@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const rowsContainer = document.getElementById('materialsRows');
    const addRowBtn = document.getElementById('addMaterialRow');
    const fallbackImage = @json(asset('assets/img/shopix5.png'));

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

    const variantOptions = `
      <option value="">Selecciona variante...</option>
      @foreach($productItems as $product)
        @foreach($product->variants as $variant)
          <option value="{{ $variant->id }}">{{ $product->name }} - {{ $variant->size }} (Stock: {{ $variant->stock }})</option>
        @endforeach
      @endforeach
    `;

    let rowIndex = 0;

    function refreshRowPreview(rowElement) {
      if (!rowElement) return;

      const select = rowElement.querySelector('.js-material-variant-select');
      const image = rowElement.querySelector('.js-material-variant-image');
      const label = rowElement.querySelector('.js-material-variant-label');

      if (!select || !image || !label) return;

      const meta = variantMeta[String(select.value)] || null;
      if (!meta) {
        image.src = fallbackImage;
        label.textContent = 'Selecciona una variante para ver su imagen';
        return;
      }

      image.src = meta.image || fallbackImage;
      label.textContent = `${meta.name || 'Producto'} - ${meta.size || 'Variante'} (Stock: ${meta.stock ?? 0})`;
    }

    function addRow() {
      rowIndex += 1;
      const row = document.createElement('div');
      row.className = 'border rounded p-2';
      row.innerHTML = `
        <div class="row g-2 align-items-end">
          <div class="col-12 col-md-2">
            <label class="form-label mb-1">Imagen</label>
            <div class="border rounded d-flex align-items-center justify-content-center bg-white" style="height: 62px; overflow: hidden;">
              <img src="${fallbackImage}" alt="Vista previa" class="js-material-variant-image" style="width: 100%; height: 100%; object-fit: cover;">
            </div>
          </div>
          <div class="col-12 col-md-8">
            <label class="form-label mb-1">Variante</label>
            <select name="items[${rowIndex}][variant_id]" class="form-select border border-1 bg-white js-material-variant-select" required>
              ${variantOptions}
            </select>
            <p class="text-xs text-muted mb-0 mt-1 js-material-variant-label">Selecciona una variante para ver su imagen</p>
          </div>
          <div class="col-8 col-md-1">
            <label class="form-label mb-1">Cantidad</label>
            <input type="number" name="items[${rowIndex}][quantity]" class="form-control border border-1 p-2 bg-white" min="0.01" step="0.01" placeholder="1" required>
          </div>
          <div class="col-4 col-md-1">
            <button type="button" class="btn btn-outline-danger w-100 remove-row">X</button>
          </div>
        </div>
      `;
      rowsContainer.appendChild(row);
      refreshRowPreview(row);
    }

    addRow();

    addRowBtn.addEventListener('click', addRow);

    rowsContainer.addEventListener('click', function (event) {
      if (!event.target.classList.contains('remove-row')) return;
      const allRows = rowsContainer.querySelectorAll('.border.rounded.p-2');
      if (allRows.length <= 1) {
        return;
      }
      event.target.closest('.border.rounded.p-2')?.remove();
    });

    rowsContainer.addEventListener('change', function (event) {
      if (!event.target.classList.contains('js-material-variant-select')) return;
      const row = event.target.closest('.border.rounded.p-2');
      refreshRowPreview(row);
    });
  });
</script>
@endpush
