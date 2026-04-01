@extends('layouts.app')

@section('title', 'Categorías')

@push('styles')
<style>
  .products-toolbar {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 0.75rem;
  }

  .products-grid {
    margin-top: 0.5rem;
  }

  .product-card-clean {
    border: 1px solid var(--bs-border-color);
    border-radius: 1rem;
    background: var(--bs-body-bg);
    padding: 1rem;
    height: 100%;
    display: flex;
    gap: 0.9rem;
    align-items: flex-start;
  }

  .product-thumb-clean {
    width: 92px;
    height: 92px;
    border-radius: 0.85rem;
    overflow: hidden;
    border: 1px solid var(--bs-border-color);
    display: inline-flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
    background: var(--bs-body-bg);
  }

  .product-thumb-clean img {
    width: 100%;
    height: 100%;
    object-fit: cover;
  }

  .product-main-clean {
    min-width: 0;
    flex: 1;
  }

  .product-title-clean {
    font-size: 0.98rem;
    font-weight: 600;
    margin: 0;
    line-height: 1.3;
  }

  .product-desc-clean {
    color: var(--bs-secondary-color);
    font-size: 0.82rem;
    margin: 0.2rem 0 0.55rem;
    line-height: 1.35;
  }

  .variant-row-clean {
    display: flex;
    justify-content: space-between;
    gap: 0.65rem;
    border-top: 1px solid var(--bs-border-color-translucent);
    padding-top: 0.45rem;
    margin-top: 0.45rem;
    font-size: 0.84rem;
  }

  .variant-price-clean {
    font-weight: 600;
  }

  .product-actions-clean {
    align-self: flex-start;
    margin-left: auto;
  }

  .edit-link-clean {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    white-space: nowrap;
    font-size: 0.84rem;
    font-weight: 500;
    color: inherit;
    text-decoration: none;
  }

  .edit-link-clean:hover {
    text-decoration: underline;
    color: inherit;
  }

  .products-categories-track .category-item,
  .products-categories-track > .flex-shrink-0 {
    width: 200px;
    scroll-snap-align: start;
  }

  @media (max-width: 768px) {
    .products-toolbar {
      flex-direction: column;
      align-items: stretch;
    }

    .products-categories-track .category-item,
    .products-categories-track > .flex-shrink-0 {
      width: 170px;
    }

    .product-card-clean {
      padding: 0.85rem;
      gap: 0.65rem;
    }

    .product-thumb-clean {
      width: 82px;
      height: 82px;
    }

    .product-title-clean {
      font-size: 0.92rem;
    }

    .variant-row-clean {
      font-size: 0.8rem;
    }
  }
</style>
@endpush

@section('content')
    <div class="container-fluid py-2">
      <div class="row">
        <div class="col-lg-12">
          <!-- Buscador -->
          <div class="mb-3 px-2">
            <input type="text" id="searchCategory" class="admin-mobile-search form-control border border-1 p-2 bg-white" placeholder="Buscar categoría...">
          </div>
          <!-- Carrusel scrollable -->
          <div id="categoriesContainer" class="products-categories-track d-flex overflow-auto gap-3 px-2 py-3" style="scroll-snap-type: x mandatory;">
            <div class="flex-shrink-0">
              <a  href="/products" class="text-decoration-none">
                <div class="card h-100">
                  <div class="card-header mx-3 p-3 text-center">
                    <div class="icon icon-shape icon-lg bg-gradient-dark shadow text-center border-radius-lg">
                      <i class="material-symbols-rounded opacity-10">all_inclusive</i>
                    </div>
                  </div>
                  <div class="card-body pt-0 p-3 text-center">
                    <h6 class="text-center mb-0 opacity-9">Todos</h6>
                    <span class="text-xs"></span>
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
              <div class="category-item flex-shrink-0" data-name="{{ strtolower($category['name']) }}">
                <a href="{{ route('products.byCategory', $category->id) }}" class="text-decoration-none">
                  <div class="card h-100">
                    <div class="card-header mx-3 p-3 text-center">
                      <div class="icon icon-shape icon-lg bg-gradient-dark shadow text-center border-radius-lg">
                        <i class="material-symbols-rounded opacity-10">{{ $icon }}</i>
                      </div>
                    </div>
                    <div class="card-body pt-0 p-3 text-center">
                      <h6 class="text-center mb-0 opacity-9">{{ $category['name'] }}</h6>
                      <span class="text-xs">{{ $category['description'] }}</span>
                    </div>
                  </div>
                </a>
              </div>
            @endforeach
          </div>
        </div>
      </div>
      <div class="row">
      <div class="col-md-12 mt-4">
        <div class="">
          <div class="products-toolbar">
            <div class="px-3 w-100" style="max-width: 420px;">
              <input type="text" id="searchProduct" class="w-100 form-control border border-1 p-2 bg-white" placeholder="Buscar producto...">
            </div>
            <div class="px-3 admin-mobile-actions justify-content-end align-items-center">
              <a class="nav-link text-black mb-0 admin-mobile-action-trigger" href="/createProduct">
                + Agregar Producto
              </a>
              <a class="nav-link text-black mb-0 admin-mobile-action-trigger" href="javascript:;" data-bs-toggle="modal" data-bs-target="#importCatalogModal">
                + Importar Catálogo
              </a>
              <a class="nav-link text-black mb-0 admin-mobile-action-trigger" href="/purchase">
                + Generar Compra
              </a>
              <button id="generateReport" class="btn btn-dark mb-0 admin-mobile-action-trigger" onclick="getReport()">
                Generar Reporte
              </button>
            </div>
          </div>
    <!-- Modal para crear producto -->
    <div class="modal fade" id="createProductModal" tabindex="-1" aria-labelledby="createProductModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="createProductModalLabel">Crear Producto</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <!-- Formulario para crear el producto -->
            <form id="createProductForm" enctype="multipart/form-data">
              @csrf
              <div class="mb-3">
                  <label for="categorySelector" class="form-label">Categoría (Sucursal)</label>
                  <select id="categorySelector" name="category_id" class="form-control border border-1 p-2" required>
                      <option value="">Selecciona una categoría</option>
                  </select>
              </div>
              <div class="mb-3">
                <label for="productName" class="form-label">Nombre del producto</label>
                <input type="text" class="form-control border border-1 p-2" id="productName" name="name" required>
              </div>
              <div class="mb-3">
                <label for="productDescription" class="form-label">Descripción</label>
                <textarea class="form-control border border-1 p-2" id="productDescription" name="description" rows="3"></textarea>
              </div>
              <div class="mb-3">
                <label for="productPrice" class="form-label">Precio</label>
                <input type="number" class="form-control border border-1 p-2" id="productPrice" name="price" step="0.01" required>
              </div>
              <div class="mb-3">
                <label for="productImages" class="form-label">Imágenes</label>
                <div class="form-control border border-1 p-2">
                  <input type="file" class="" id="productImages" name="images[]" multiple>
                </div>
              </div>
              <div class="d-flex flex-row-reverse">
                <button type="submit" class="btn btn-info">Guardar</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
    <!-- Fin Modal para crear producto -->

    <div class="modal fade" id="importCatalogModal" tabindex="-1" aria-labelledby="importCatalogModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="importCatalogModalLabel">Importar catálogo</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body">
            <form id="importCatalogForm" enctype="multipart/form-data">
              @csrf
              <div class="mb-3">
                <label for="catalogFile" class="form-label">Archivo (CSV, JSON, SQL)</label>
                <input type="file" class="form-control border border-1 p-2" id="catalogFile" name="file" accept=".csv,.json,.sql,.txt">
              </div>

              <div class="mb-3">
                <label for="googleSheetUrl" class="form-label">Google Sheets (URL pública opcional)</label>
                <input type="url" class="form-control border border-1 p-2" id="googleSheetUrl" name="google_sheet_url" placeholder="https://docs.google.com/spreadsheets/d/...">
              </div>

              <div class="form-check mb-3">
                <input class="form-check-input" type="checkbox" value="1" id="useGeminiMapping" name="use_gemini_mapping" checked>
                <label class="form-check-label" for="useGeminiMapping">
                  Usar Gemini para mapear columnas automáticamente
                </label>
              </div>

              <small class="text-muted d-block mb-3">
                Estructura recomendada: category_name, category_description, product_name, product_description, variant_size, variant_price, variant_stock, variant_unit_type.
              </small>

              <div class="d-flex flex-row-reverse">
                <button type="submit" class="btn btn-dark" id="importCatalogSubmitBtn">Importar</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

    <div class="px-3">
      <div class="row products-grid">
      @foreach($productItems as $product)
        @php
          $variantImage = $product->variants->first()?->images->first();
          $productCoverImage = (isset($product->images) && count($product->images) > 0)
            ? $product->images[0]
            : $variantImage;
        @endphp
        <div class="product-item col-12 col-md-6 col-xl-4 mb-3" data-name="{{ strtolower($product->name) }}">
          <div class="product-card-clean">
              <a href="{{ route('productItem', $product->id) }}" class="product-thumb-clean" aria-label="Abrir producto {{ $product->name }}">
                @if($productCoverImage)
                  <img src="{{ \App\Support\ImageStorage::url($productCoverImage->path) ?? asset('assets/img/shopix5.png') }}" alt="Imagen del producto">
                @else
                  <i class="material-symbols-rounded text-dark">photo_camera</i>
                @endif
              </a>

              <div class="product-main-clean">
                <h6 class="product-title-clean text-truncate">{{ $product->name }}</h6>
                <p class="product-desc-clean">{{ $product->description ?: 'Sin descripción' }}</p>

                @foreach ($product->variants as $variant)
                  <div class="variant-row-clean">
                    <span>
                      <strong>{{ $variant->size }}</strong>
                    </span>
                    <span class="variant-price-clean">
                      {{ number_format((float) $variant->price, 2) }} {{ $baseCurrencySymbol ?? '$' }}
                    </span>
                    <span class="{{ $variant->stock < 1 ? 'text-danger' : ($variant->stock < 5 ? 'text-warning' : 'text-success') }}">
                      {{ $variant->stock }} unidades
                    </span>
                  </div>
                @endforeach
              </div>

              <div class="product-actions-clean">
                <a href="{{ route('productItem', $product->id) }}" class="edit-link-clean">
                  <i class="material-symbols-rounded text-sm">edit</i>Editar
                </a>
              </div>
          </div>
        </div>
      @endforeach

      @if(($productItems ?? collect())->isEmpty())
        <div class="col-12">
          <div class="card border-0 shadow-sm p-4 text-center">
            <h6 class="mb-1">No hay productos para mostrar</h6>
            <p class="text-sm text-muted mb-0">Agrega un producto o cambia el filtro de categoría.</p>
          </div>
        </div>
      @endif
    </div>
  </div>
</div>
      </div>
    </div>
    @endsection

@push('scripts')
<script src="{{ asset('assets/js/core/popper.min.js') }}"></script>
<script src="{{ asset('assets/js/core/bootstrap.min.js') }}"></script>

  <script>
    const createProductEndpoint = @json(route('products.createWeb'));
    const importCatalogEndpoint = @json(route('products.importCatalogWeb'));
    const importCatalogForm = document.getElementById('importCatalogForm');
    if (importCatalogForm) {
      importCatalogForm.addEventListener('submit', async function (event) {
        event.preventDefault();

        const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
          || document.querySelector('input[name="_token"]')?.value;

        const submitBtn = document.getElementById('importCatalogSubmitBtn');
        const originalText = submitBtn.textContent;
        submitBtn.disabled = true;
        submitBtn.textContent = 'Importando...';

        try {
          const formData = new FormData(this);
          if (!formData.get('file') && !String(formData.get('google_sheet_url') || '').trim()) {
            alert('Debes subir un archivo o colocar una URL de Google Sheets.');
            return;
          }

          const response = await fetch(importCatalogEndpoint, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
              'Accept': 'application/json',
              'X-Requested-With': 'XMLHttpRequest',
              'X-CSRF-TOKEN': csrfToken || '',
            },
            body: formData,
          });

          const payload = await response.json();
          if (!response.ok || !payload.success) {
            throw new Error(payload.error || payload.message || 'No se pudo importar el catálogo.');
          }

          const summary = payload.summary || {};
          alert(
            `Importación completada.\n` +
            `Categorías creadas: ${summary.created_categories ?? 0}\n` +
            `Productos creados: ${summary.created_products ?? 0}\n` +
            `Variantes procesadas: ${summary.processed_variants ?? 0}\n` +
            `Filas omitidas: ${summary.skipped_rows ?? 0}`
          );

          window.location.reload();
        } catch (error) {
          console.error(error);
          alert(error.message || 'Error al importar catálogo.');
        } finally {
          submitBtn.disabled = false;
          submitBtn.textContent = originalText;
        }
      });
    }

    document.getElementById('createProductForm').addEventListener('submit', function(event) {
      event.preventDefault(); // Evita el envío normal del formulario

      let formData = new FormData(this); // Crear un FormData con los datos del formulario

      fetch(createProductEndpoint, {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest'
        },
        credentials: 'same-origin',
        body: formData
      })
      .then(async response => {
        const data = await response.json().catch(() => ({}));

        if (!response.ok) {
          const validationMessage = data?.errors
            ? Object.values(data.errors).flat().join('\n')
            : null;
          throw new Error(validationMessage || data.message || data.error || 'No se pudo crear el producto.');
        }

        return data;
      })
      .then(data => {
        if (data.success || data.message === 'Product created successfully') {
          alert('Producto creado correctamente');
          // Cierra el modal y refresca o actualiza el contenido
          $('#createProductModal').modal('hide');
          window.location.reload();
        } else {
          throw new Error(data.message || 'No se pudo crear el producto.');
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert(error.message || 'No se pudo crear el producto.');
      });
    });

    document.getElementById('createCategoryForm').addEventListener('submit', function(event) {
      event.preventDefault(); // Evita el envío normal del formulario

      let formData = new FormData(this); // Crear un FormData con los datos del formulario

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

        if (response.status === 201 || payload?.category) {
          alert('Categoría creada correctamente');
          window.location.reload();
        } else {
          const validationMessage = payload?.errors
            ? Object.values(payload.errors).flat().join('\n')
            : null;
          throw new Error(validationMessage || payload.message || payload.error || 'No se pudo crear la categoría.');
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert(error.message || 'No se pudo crear la categoría.');
      });
    });

    function getSucursales() {
      fetch('api/categories', {
          method: 'GET',
          headers: {
              'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
          }
      })
      .then(response => response.json())
      .then(data => {
          const categorySelector = document.getElementById('categorySelector');
          
          // Limpiamos las opciones actuales
          categorySelector.innerHTML = '<option value="">Selecciona una categoría</option>';
          
          // Agregamos cada categoría al selector
          data.forEach(category => {
              const option = document.createElement('option');
              option.value = category.id;
              option.textContent = category.name;
              categorySelector.appendChild(option);
          });
      })
      .catch(error => console.error('Error:', error));
  }
  document.getElementById('searchCategory').addEventListener('input', function () {
    const searchValue = this.value.toLowerCase();
    const items = document.querySelectorAll('.category-item');

    items.forEach(item => {
      const name = item.getAttribute('data-name');
      if (name.includes(searchValue)) {
        item.style.display = 'block';
      } else {
        item.style.display = 'none';
      }
    });
  });
  document.getElementById('searchProduct').addEventListener('input', function () {
    const searchValue = this.value.toLowerCase();
    const items = document.querySelectorAll('.product-item');

    items.forEach(item => {
      const name = item.getAttribute('data-name');
      if (name.includes(searchValue)) {
        item.style.display = 'block';
      } else {
        item.style.display = 'none';
      }
    });
  });
  function getReport() {
    fetch('api/products/report', {
      method: 'GET',
          headers: {
              'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
          }
    })
    .then(response => response.json())
    .then(data => {
      if (data.message === 'Report generated successfully') {
        alert('Reporte generado correctamente');
        // Aquí puedes añadir lógica para manejar el reporte, como descargarlo o mostrarlo
      } else {
        alert('Ocurrió un error al generar el reporte');
      }
    })
    .catch(error => console.error('Error:', error));
  }
  </script>
@endpush