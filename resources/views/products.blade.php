@extends('layouts.app')

@section('title', 'Categorías')

@section('content')
    <div class="container-fluid py-2">
      <div class="py-1 px-3 text-end" data-bs-toggle="modal" data-bs-target="#createCategoryModal">
        <label>
          + Agregar Categoría
        </label>
      </div>
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
                <div class="mb-3">
                  <label for="categoryName" class="form-label">Nombre</label>
                  <input type="text" class="form-control border border-1 p-2" id="categoryName" name="name" required>
                </div>
                <div class="mb-3">
                  <label for="categoryDescription" class="form-label">Descripción</label>
                  <textarea class="form-control border border-1 p-2" id="categoryDescription" name="description" rows="3" required></textarea>
                </div>
                <div class="d-flex flex-row-reverse">
                  <button type="submit" class="btn btn-info">Guardar</button>
                </div>
              </form>
            </div>
          </div>
        </div>
      </div>
      <!-- Modal para crear categoría -->
      <div class="row">
        <div class="col-lg-12">
          <!-- Buscador -->
          <div class="mb-3 px-2">
            <input type="text" id="searchCategory" class="w-25 form-control border border-1 p-2 bg-white" placeholder="Buscar categoría...">
          </div>
          <!-- Carrusel scrollable -->
          <div id="categoriesContainer" class="d-flex overflow-auto gap-3 px-2 py-3" style="scroll-snap-type: x mandatory;">
            <div class=" flex-shrink-0" style="width: 200px; scroll-snap-align: start;">
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
              <div class="category-item flex-shrink-0" style="width: 200px; scroll-snap-align: start;" data-name="{{ strtolower($category['name']) }}">
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
          <div class="d-flex justify-content-between align-items-center">
            <div class="px-3 w-30">
              <input type="text" id="searchProduct" class="w-100 form-control border border-1 p-2 bg-white" placeholder="Buscar producto...">
            </div>
            <div class="px-3 d-flex justify-content-end align-items-center gap-5">
              <a class="nav-link text-black" href="/createProduct">
                + Agregar Producto
              </a>
              <a class="nav-link text-black" href="javascript:;" data-bs-toggle="modal" data-bs-target="#importCatalogModal">
                + Importar Catálogo
              </a>
              <a class="nav-link text-black" href="/purchase">
                + Generar Compra
              </a>
              <button id="generateReport" class="btn btn-dark mt-3" onclick="getReport()">
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
                <textarea class="form-control border border-1 p-2" id="productDescription" name="description" rows="3" required></textarea>
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
      <div class="row">
        <!-- Buscador -->
      @foreach($productItems as $product)
        <div class="product-item col-md-4 mb-4" data-name="{{ strtolower($product->name) }}">
          <div class="card p-4 d-flex flex-row" style="min-height: 10rem;">
              <a href="{{ route('productItem', $product->id) }}" class="icon icon-shape icon-xl shadow bg-transparent text-center border border-1 border-black text-info border-radius-lg" style="width: 100px; height: 100px;">
                  @if(isset($product->images) && count($product->images) > 0)
                      <img src="{{ asset('storage/' . $product->images[0]->path) }}" alt="Imagen del producto" style="width: 100%; height: 100%; object-fit: cover; border-radius: inherit;">
                  @else
                      <i class="material-symbols-rounded text-dark">photo_camera</i>
                  @endif
              </a>
              <div class="d-flex flex-column mx-3">
                <h6 class="text-sm">{{ $product->name }}</h6>
                <div class="text-sm d-flex flex-column">
                  <span>
                    {{ $product->description }}
                  </span>
                  @foreach ($product->variants as $variant)
                    <span class="text-sm">
                      {{ $variant->size }} - {{ $variant->price }} $ - 
                    <span class="{{ $variant->stock < 1 ? 'text-danger' : ($variant->stock < 5 ? 'text-warning' : 'text-success') }}">{{ $variant->stock }} unidades</span>
                    </span>
                  @endforeach
                </div>
                    <!-- Mostrar las tallas -->
              </div>
              <div class="ms-auto text-end">
                <a href="{{ route('productItem', $product->id) }}" class="btn btn-link text-dark" ><i class="material-symbols-rounded text-sm">edit</i>Editar</a>
              </div>
          </div>
        </div>
      @endforeach
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

      fetch('api/create-product', {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
        },
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.message === 'Product created successfully') {
          alert('Producto creado correctamente');
          // Cierra el modal y refresca o actualiza el contenido
          $('#createProductModal').modal('hide');
          // Aquí puedes añadir lógica para actualizar la lista de productos si existe
        } else {
          alert('Ocurrió un error al crear el producto');
        }
      })
      .catch(error => console.error('Error:', error));
    });

    document.getElementById('createCategoryForm').addEventListener('submit', function(event) {
      event.preventDefault(); // Evita el envío normal del formulario

      let formData = new FormData(this); // Crear un FormData con los datos del formulario

      fetch('api/create-category', {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
        },
        body: formData
      })
      .then(response => {
        if (response.status === 201) { // Valida el código de estado HTTP
          alert('Categoría creada correctamente');
          window.location.reload();
        } else {
          throw new Error('Error al crear la categoría');
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('Ocurrió un error al crear la Categoría');
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