@extends('layouts.app')

@section('title', 'Categorías')

@php
  $productsUserRole = \App\Models\User::canonicalRoleName(optional(auth()->user()->role)->name);
  $productsIsSellerRole = in_array($productsUserRole, ['vendor', 'vendedor', 'seller'], true);
  $productsToolbarTenant = ($productsToolbarTenantId = (int) (auth()->user()->tenant_id ?? 0)) > 0
    ? \App\Models\Tenant::find($productsToolbarTenantId)
    : null;
  $productsToolbarCapabilities = \App\Support\TenantPlanCapabilities::forTenant($productsToolbarTenant);
  $productsToolbarFreePlan = !$productsToolbarCapabilities->canGeneratePurchase();
@endphp

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

  .page-loading-skeleton {
    position: fixed;
    inset: 0;
    z-index: 2000;
    display: none;
    padding: 1rem;
    background: rgba(248, 250, 252, 0.82);
    backdrop-filter: blur(4px);
  }

  .page-loading-skeleton.is-visible {
    display: block;
  }

  .skeleton-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(230px, 1fr));
    gap: 1rem;
    margin-top: 2rem;
  }

  .skeleton-card {
    min-height: 148px;
    border-radius: 1rem;
    border: 1px solid rgba(148, 163, 184, 0.25);
    background: linear-gradient(90deg, #eef2f7 25%, #f8fafc 37%, #eef2f7 63%);
    background-size: 400% 100%;
    animation: skeletonShimmer 1.3s ease infinite;
    padding: 0.95rem;
  }

  .skeleton-line {
    height: 10px;
    border-radius: 999px;
    background: rgba(148, 163, 184, 0.35);
    margin-bottom: 0.55rem;
  }

  .skeleton-line.short {
    width: 42%;
  }

  .skeleton-line.medium {
    width: 68%;
  }

  .skeleton-chip {
    display: inline-block;
    width: 72px;
    height: 20px;
    border-radius: 999px;
    background: rgba(148, 163, 184, 0.25);
    margin-right: 0.45rem;
  }

  @keyframes skeletonShimmer {
    0% { background-position: 100% 0; }
    100% { background-position: 0 0; }
  }

  .products-primary-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.6rem;
    justify-content: flex-end;
  }

  .products-primary-actions .btn {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    min-height: 40px;
    border-radius: 0.7rem;
    white-space: nowrap;
  }

  .product-card-clean {
    border: 1px solid var(--bs-border-color);
    border-radius: 0.9rem;
    background: var(--bs-body-bg);
    padding: 0.62rem 0.7rem;
    height: 100%;
    display: flex;
    gap: 0.6rem;
    align-items: flex-start;
  }

  .product-thumb-clean {
    width: 62px;
    height: 62px;
    border-radius: 0.72rem;
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

  .product-head-clean {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.45rem;
  }

  .product-title-clean {
    font-size: 0.9rem;
    font-weight: 600;
    margin: 0;
    line-height: 1.2;
  }

  .product-desc-clean {
    color: var(--bs-secondary-color);
    font-size: 0.74rem;
    margin: 0.08rem 0 0.25rem;
    line-height: 1.25;
    display: -webkit-box;
    -webkit-line-clamp: 1;
    -webkit-box-orient: vertical;
    overflow: hidden;
  }

  .variants-list-clean {
    display: grid;
    gap: 0.26rem;
  }

  .variant-row-clean {
    display: grid;
    grid-template-columns: minmax(0, 1fr) auto auto;
    column-gap: 0.45rem;
    align-items: center;
    padding: 0.2rem 0.35rem;
    border-radius: 0.5rem;
    background: rgba(17, 24, 39, 0.04);
    font-size: 0.75rem;
    line-height: 1.1;
  }

  .variant-size-clean {
    min-width: 0;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
  }

  .variant-price-clean {
    font-weight: 600;
    white-space: nowrap;
    text-align: right;
    display: inline-flex;
    flex-direction: column;
    align-items: flex-end;
    gap: 0.08rem;
  }

  .variant-price-bs-clean {
    font-size: 0.68rem;
    font-weight: 500;
    color: var(--bs-secondary-color);
  }

  .variant-stock-clean {
    white-space: nowrap;
    text-align: right;
  }

  .variant-more-clean {
    background: transparent;
    padding: 0.05rem 0.15rem;
    color: var(--bs-secondary-color);
    font-size: 0.72rem;
    justify-content: flex-start;
  }

  .edit-link-clean {
    display: inline-flex;
    align-items: center;
    gap: 0.25rem;
    white-space: nowrap;
    font-size: 0.74rem;
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
    width: auto;
    scroll-snap-align: start;
  }

  .category-filter-link {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    border: 1px solid var(--bs-border-color);
    border-radius: 0.7rem;
    padding: 0.42rem 0.72rem;
    font-size: 0.83rem;
    font-weight: 600;
    line-height: 1;
    color: var(--bs-dark);
    background: var(--bs-body-bg);
    white-space: nowrap;
    text-decoration: none;
    transition: all 0.16s ease;
  }

  .category-filter-link:hover {
    color: var(--bs-dark);
    border-color: var(--bs-dark);
    text-decoration: none;
    transform: translateY(-1px);
  }

  .category-filter-link.is-active {
    background: linear-gradient(195deg, #42424a, #191919);
    color: #fff;
    border-color: transparent;
    box-shadow: 0 0.2rem 0.6rem rgba(0, 0, 0, 0.2);
  }

  .category-filter-icon {
    font-size: 1rem;
    line-height: 1;
  }

  @media (max-width: 768px) {
    .products-toolbar {
      flex-direction: column;
      align-items: stretch;
    }

    .products-primary-actions {
      justify-content: stretch;
    }

    .products-primary-actions .btn {
      flex: 1 1 calc(50% - 0.6rem);
      justify-content: center;
    }

    .products-categories-track .category-item,
    .products-categories-track > .flex-shrink-0 {
      width: auto;
    }

    .category-filter-link {
      font-size: 0.78rem;
      padding: 0.4rem 0.64rem;
    }

    .product-card-clean {
      padding: 0.68rem;
      gap: 0.52rem;
    }

    .product-thumb-clean {
      width: 60px;
      height: 60px;
    }

    .product-title-clean {
      font-size: 0.85rem;
    }

    .variant-row-clean {
      font-size: 0.72rem;
    }

    .variant-row-clean {
      grid-template-columns: minmax(0, 1fr) auto;
      row-gap: 0.15rem;
    }

    .variant-stock-clean {
      grid-column: 1 / -1;
      text-align: left;
    }
  }
</style>
@endpush

@section('content')
    <div class="container-fluid py-2">
      <div id="productsPageSkeleton" class="page-loading-skeleton" aria-hidden="true">
        <div class="skeleton-grid">
          @for ($i = 0; $i < 8; $i++)
            <div class="skeleton-card">
              <div class="d-flex gap-3">
                <div style="width:62px;height:62px;border-radius:0.72rem;background:rgba(148,163,184,.25);"></div>
                <div class="flex-grow-1">
                  <div class="skeleton-line medium"></div>
                  <div class="skeleton-line short"></div>
                  <div class="skeleton-chip"></div><div class="skeleton-chip"></div>
                  <div class="skeleton-line"></div>
                </div>
              </div>
            </div>
          @endfor
        </div>
      </div>
      <div class="row">
        <div class="col-lg-12">
          <!-- Buscador -->
          <div class="mb-3 px-2">
            <input type="text" id="searchCategory" class="admin-mobile-search form-control border border-1 p-2 bg-white" placeholder="Buscar categoría...">
          </div>
          <!-- Carrusel scrollable -->
          <div id="categoriesContainer" class="products-categories-track d-flex overflow-auto gap-2 px-2 py-2" style="scroll-snap-type: x mandatory;">
            <div class="flex-shrink-0">
              <a href="/products" class="category-filter-link {{ isset($category) ? '' : 'is-active' }}" aria-label="Filtrar por todas las categorias">
                <i class="material-symbols-rounded category-filter-icon">all_inclusive</i>
                <span>Todos</span>
              </a>
            </div>
            @foreach($categories as $categoryItem)
              @php
                switch ($categoryItem->name) {
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
              <div class="category-item flex-shrink-0" data-search="{{ \Illuminate\Support\Str::lower((string) $categoryItem['name']) }}">
                <a href="{{ route('products.byCategory', $categoryItem->id) }}" class="category-filter-link {{ (isset($category) && (int) $category->id === (int) $categoryItem->id) ? 'is-active' : '' }}" aria-label="Filtrar por categoria {{ $categoryItem['name'] }}">
                  <i class="material-symbols-rounded category-filter-icon">{{ $icon }}</i>
                  <span>{{ $categoryItem['name'] }}</span>
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
              <form method="GET" action="{{ isset($category) ? route('products.byCategory', $category->id) : route('products.index') }}" id="productsSearchForm" class="d-flex gap-2 align-items-center">
                <input type="text" id="searchProduct" name="q" value="{{ request('q', $search ?? '') }}" class="w-100 form-control border border-1 p-2 bg-white" placeholder="Buscar producto...">
                <button type="submit" class="btn btn-dark mb-0">Buscar</button>
              </form>
            </div>
            <div class="px-3 products-primary-actions align-items-center">
              @if(!$productsIsSellerRole)
                <a class="btn btn-dark mb-0 admin-mobile-action-trigger" href="/createProduct">
                  <i class="material-symbols-rounded">add_box</i>
                  <span>Agregar Producto</span>
                </a>
                <a class="btn btn-outline-dark mb-0 admin-mobile-action-trigger" href="javascript:;" data-bs-toggle="modal" data-bs-target="#importCatalogModal">
                  <i class="material-symbols-rounded">upload_file</i>
                  <span>Importar Catálogo</span>
                </a>
                @unless($productsToolbarFreePlan)
                  <a class="btn btn-outline-dark mb-0 admin-mobile-action-trigger" href="/purchase">
                    <i class="material-symbols-rounded">shopping_bag</i>
                    <span>Generar Compra</span>
                  </a>
                  <button type="button" id="generateReport" class="btn btn-dark mb-0 admin-mobile-action-trigger" data-bs-toggle="modal" data-bs-target="#productsReportModal">
                    <i class="material-symbols-rounded">assessment</i>
                    Generar Reporte
                  </button>
                @endunless
              @endif
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
                <input type="number" class="form-control border border-1 p-2" id="productPrice" name="price" min="0.01" step="0.01" required data-decimal-friendly="true">
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

    <div class="modal fade" id="productsReportModal" tabindex="-1" aria-labelledby="productsReportModalLabel" aria-hidden="true">
      <div class="modal-dialog">
        <div class="modal-content">
          <div class="modal-header">
            <h5 class="modal-title" id="productsReportModalLabel">Generar Reporte de Productos</h5>
            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
          </div>
          <div class="modal-body">
            <form id="productsReportForm" action="{{ url('/api/products/report') }}" method="GET" target="_blank">
              <div class="mb-3">
                <label for="reportCategoryId" class="form-label">Categoría</label>
                <select id="reportCategoryId" name="category_id" class="form-control border border-1 p-2">
                  <option value="">Todas las categorías</option>
                  @foreach($categories as $category)
                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                  @endforeach
                </select>
              </div>
              <div class="mb-3">
                <label for="reportStatus" class="form-label">Estado</label>
                <select id="reportStatus" name="status" class="form-control border border-1 p-2" required>
                  <option value="all" selected>Todos</option>
                  <option value="active">Activos</option>
                  <option value="inactive">Inactivos</option>
                </select>
              </div>
              <div class="mb-3">
                <label for="reportFormat" class="form-label">Formato</label>
                <select id="reportFormat" name="format" class="form-control border border-1 p-2" required>
                  <option value="csv" selected>CSV</option>
                </select>
              </div>
              <div class="d-flex justify-content-end gap-2">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-dark">Descargar reporte</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>

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
        @php
          $productSearchTerms = collect([
            $product->name,
            $product->description,
            optional($product->category)->name,
          ])->merge($product->variants->map(function ($variant) {
            return collect([
              $variant->size,
              $variant->barcode,
              $variant->sku,
              $variant->code,
            ])->filter()->implode(' ');
          }))->filter()->implode(' ');
        @endphp
        <div class="product-item col-12 col-md-6 col-xl-3 mb-2" data-search="{{ \Illuminate\Support\Str::lower($productSearchTerms) }}">
          <div class="product-card-clean">
              <a href="{{ route('productItem', $product->id) }}" class="product-thumb-clean" aria-label="Abrir producto {{ $product->name }}">
                @if($productCoverImage)
                  <img src="{{ \App\Support\ImageStorage::url($productCoverImage->path) ?? asset('assets/img/shopix5.png') }}" alt="Imagen del producto">
                @else
                  <i class="material-symbols-rounded text-dark">photo_camera</i>
                @endif
              </a>

              <div class="product-main-clean">
                <div class="product-head-clean">
                  <h6 class="product-title-clean text-truncate mb-0">{{ $product->name }}</h6>
                  @if(!$productsIsSellerRole)
                    <a href="{{ route('productItem', $product->id) }}" class="edit-link-clean">
                      <i class="material-symbols-rounded text-sm">edit</i>Editar
                    </a>
                  @endif
                </div>
                <p class="product-desc-clean">{{ $product->description ?: 'Sin descripción' }}</p>

                <div class="variants-list-clean">
                  @foreach ($product->variants->take(4) as $variant)
                    @php
                      $variantPriceBase = (float) ($variant->price ?? 0);
                      $variantPriceBs = ($baseRateToBs ?? 0) > 0
                        ? ($variantPriceBase * (float) $baseRateToBs)
                        : null;
                    @endphp
                    <div class="variant-row-clean">
                      <span class="variant-size-clean">
                        <strong>{{ $variant->size }}</strong>
                      </span>
                      <span class="variant-price-clean">
                        <span>{{ number_format($variantPriceBase, 2) }} {{ $baseCurrencyCode ?? 'USD' }}</span>
                        @if(!is_null($variantPriceBs))
                          <span class="variant-price-bs-clean">Bs {{ number_format((float) $variantPriceBs, 2) }}</span>
                        @endif
                      </span>
                      <span class="variant-stock-clean {{ $variant->stock < 1 ? 'text-danger' : ($variant->stock < 5 ? 'text-warning' : 'text-success') }}">
                        {{ $variant->stock }} unidades
                      </span>
                    </div>
                  @endforeach
                  @if($product->variants->count() > 4)
                    <div class="variant-row-clean variant-more-clean">
                      <span><strong>+{{ $product->variants->count() - 4 }}</strong> variantes más</span>
                    </div>
                  @endif
                </div>
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
    @if(method_exists($productItems, 'hasPages') && $productItems->hasPages())
      <div class="d-flex justify-content-center mt-4">
        {{ $productItems->links('pagination::bootstrap-5') }}
      </div>
    @endif
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

    const createProductForm = document.getElementById('createProductForm');
    if (createProductForm) {
      createProductForm.addEventListener('submit', function(event) {
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
    }

    const createCategoryForm = document.getElementById('createCategoryForm');
    if (createCategoryForm) {
      createCategoryForm.addEventListener('submit', function(event) {
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
    }

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
  const normalizeSearchText = (value) => String(value || '')
    .toLowerCase()
    .normalize('NFD')
    .replace(/[\u0300-\u036f]/g, '')
    .trim();

  const productsPageSkeleton = document.getElementById('productsPageSkeleton');
  const productsPaginationKey = 'shopix-products-loading';

  function setProductsSkeletonVisible(isVisible) {
    if (!productsPageSkeleton) {
      return;
    }

    productsPageSkeleton.classList.toggle('is-visible', isVisible);
    productsPageSkeleton.setAttribute('aria-hidden', isVisible ? 'false' : 'true');
  }

  document.addEventListener('click', (event) => {
    const paginationLink = event.target.closest('.pagination a');
    if (!paginationLink || paginationLink.classList.contains('disabled')) {
      return;
    }

    sessionStorage.setItem(productsPaginationKey, '1');
    setProductsSkeletonVisible(true);
  });

  document.addEventListener('DOMContentLoaded', () => {
    if (sessionStorage.getItem(productsPaginationKey) === '1') {
      setProductsSkeletonVisible(true);
    }
  });

  window.addEventListener('load', () => {
    sessionStorage.removeItem(productsPaginationKey);
    setProductsSkeletonVisible(false);
  });

  const applySearchFilter = (inputId, itemSelector) => {
    const input = document.getElementById(inputId);
    if (!input) {
      return;
    }

    const searchValue = normalizeSearchText(input.value);
    document.querySelectorAll(itemSelector).forEach((item) => {
      const searchableText = normalizeSearchText(
        item.getAttribute('data-search') || item.textContent || ''
      );
      const isVisible = searchValue === '' || searchableText.includes(searchValue);
      item.classList.toggle('d-none', !isVisible);
      item.style.display = isVisible ? '' : 'none';
    });
  };

  document.addEventListener('input', (event) => {
    const target = event.target;
    if (!(target instanceof HTMLInputElement)) {
      return;
    }

    if (target.id === 'searchCategory') {
      applySearchFilter('searchCategory', '.category-item');
    }

  });

  window.addEventListener('load', () => {
    applySearchFilter('searchCategory', '.category-item');
  });
  </script>
@endpush