<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>{{ $tenant->name }} - Tienda Virtual</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    body {
      font-family: 'Inter', sans-serif;
      background-color: #fff;
      color: #000;
    }

    .landing-header {
      transition: background 0.3s ease-in-out;
      z-index: 1050;
      background: transparent;
      padding: 0.5rem 0;
    }

    .landing-header .navbar-toggler {
      border: 1px solid rgba(0, 0, 0, 0.2);
      background: #fff;
      padding: 0.35rem 0.55rem;
    }

    .landing-nav-link {
      font-weight: 600;
      padding: 0.4rem 0.75rem;
    }

    .section-title {
      font-size: 2rem;
      font-weight: 700;
      margin-bottom: 2rem;
      text-align: center;
    }

    .card-product {
      border: none;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 0 15px rgba(0, 0, 0, 0.05);
      transition: transform 0.2s ease;
      background-color: #fff;
    }

    .card-product:hover {
      transform: scale(1.02);
    }

    .nav-link.category-link {
      font-weight: 500;
      margin-left: 1rem;
      cursor: pointer;
      transition: color 0.2s;
    }

    .nav-link.category-link.active {
    }
    .category-card {
    position: relative;
    display: block;
    width: 100%;
    height: 260px;
    background-size: cover;
    background-position: center;
    border-radius: 14px;
    overflow: hidden;
    text-decoration: none;
    transition: transform 0.3s ease;
}

.category-card:hover {
    transform: scale(1.03);
}

/* Oscurece la imagen para que el texto se lea */
.category-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(
        to top,
        rgba(0,0,0,0.65),
        rgba(0,0,0,0.15)
    );
    display: flex;
    align-items: flex-end;
    justify-content: center;
    padding-bottom: 25px;
}

.category-title {
    color: #fff;
    font-size: 1.5rem;
    font-weight: 700;
    text-align: center;
}

    @media (max-width: 991.98px) {
      .landing-header {
        background: rgba(255, 255, 255, 0.96);
      }

      .navbar-nav {
        padding-top: 0.5rem;
      }

      .navbar-nav .nav-item {
        margin-bottom: 0.5rem;
      }

      .landing-nav-link {
        display: block;
        width: 100%;
        text-align: center;
      }
    }

  </style>
</head>

<body  style="min-height: 100vh;">

  <!-- HEADER -->
  <header class="landing-header position-fixed top-0 start-0 w-100">
    <div class="container">
      <nav class="navbar navbar-expand-lg navbar-light p-0">
        <a class="navbar-brand d-flex align-items-center" href="{{ route('tenant.public', ['tenant' => $tenant->slug]) }}">
          @if($tenant->logo)
            <span class="btn btn-light p-1 px-3 m-0">
              <img src="{{ asset('storage/' . $tenant->logo) }}" alt="Logo {{ $tenant->name }}" class="img-fluid" style="width: 100px; height: 50px; object-fit: contain;">
            </span>
          @else
            <span class="btn btn-light text-dark fw-bold">{{ $tenant->name }}</span>
          @endif
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#landingNavbar" aria-controls="landingNavbar" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="landingNavbar">
          <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
            @foreach($categories as $category)
              <li class="nav-item">
                <a class="btn btn-light text-dark landing-nav-link category-link" href="#" data-id="{{ $category->id }}">{{ $category->name }}</a>
              </li>
            @endforeach
            @if(isset($materialPackages) && $materialPackages->count() > 0)
              <li class="nav-item">
                <a class="btn btn-light text-dark landing-nav-link category-link" href="#" data-id="packages">Paquetes</a>
              </li>
            @endif
            <li class="nav-item">
              <a class="btn btn-light text-dark landing-nav-link category-link" href="#" data-id="all">Todos</a>
            </li>
            @include('partials.tenant-cart-nav')
            <li class="nav-item">
              <a class="btn btn-light text-dark landing-nav-link" href="{{ route('tenant.public', ['tenant' => $tenant->slug]) }}">Volver</a>
            </li>
          </ul>
        </div>
      </nav>
    </div>
  </header>

  <!-- PRODUCTOS -->
  <section class="py-5 bg-light h-100">
    <div class="mt-5 px-3 px-md-4">
      <div class="row mb-4">
        <div class="col-md-6 mx-auto">
            <div class="input-group input-group-lg shadow-sm">
            <span class="input-group-text bg-white border-end-0">
                <i class="bi bi-search"></i>
            </span>
            <input
                type="text"
                id="product-search"
                class="form-control border-start-0"
                placeholder="Buscar productos..."
                autocomplete="off"
            >
            </div>
        </div>
        </div>

      <div class="row" id="products-container">
        @foreach($products as $product)
            <div class="col-12 col-sm-6 col-md-4 col-lg-3 mb-4 product-item" data-category="{{ $product->category_id }}" data-name="{{ strtolower($product->name) }}">
                    <a href="{{ route('tenant.public.product', [
    'tenant' => $tenant->slug,
    'product' => $product->id
]) }}" class="text-decoration-none">

                    <div class="card card-product h-100">
                        @if(isset($product->images[0]))
                            <img src="{{ asset('storage/' . $product->images[0]->path) }}" class="card-img-top" style="height: 300px; object-fit: cover;">
                        @else
                            <div class="d-flex align-items-center justify-content-center" style="height: 300px; background-color: #eee;">
                                <i class="bi bi-image text-muted fs-1"></i>
                            </div>
                        @endif
                        <div class="card-body text-start">
                            <h5 class="fw-bold text-dark">{{ $product->name }}</h5>
                            <p class="text-muted">{{ $product->description }}</p>
                            <div class="d-flex flex-row gap-1 m-0">
                                @foreach ($product->variants as $variant)
                                @php
                                  $productDiscount = (float) ($product->discount_percentage ?? 0);
                                  $variantDiscount = (float) ($variant->discount_percentage ?? 0);
                                  $effectiveVariantPrice = (float) $variant->price * ((100 - $productDiscount) / 100) * ((100 - $variantDiscount) / 100);
                                @endphp
                                    <div class="small btn btn-outline-secondary p-2 rounded-3">
                                        <span class="fw-semibold">{{ $variant->size }}</span>
                                        / 
                                  <span class="fw-semibold">{{ number_format($effectiveVariantPrice, 2) }} $</span>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </a>
            </div>
        @endforeach
      </div>
    </div>
  </section>

  @if(isset($materialPackages) && $materialPackages->count() > 0)
  <section id="paquetes" class="py-5 bg-white border-top" data-category="packages">
    <div class="mt-3 px-3 px-md-4">
      <h2 class="section-title text-start">Paquetes y Combos</h2>
      <div class="row" id="packages-container">
        @foreach($materialPackages as $package)
          @php
            $firstItem = $package->items->first();
            $firstImage = $firstItem && $firstItem->variant && $firstItem->variant->product && isset($firstItem->variant->product->images[0])
              ? asset('storage/' . $firstItem->variant->product->images[0]->path)
              : null;
            $packageTotalBeforeDiscount = $package->items->sum(function($it) {
              $basePrice = (float) ($it->variant->price ?? 0);
              $productDiscount = (float) ($it->variant->product->discount_percentage ?? 0);
              $variantDiscount = (float) ($it->variant->discount_percentage ?? 0);
              $price = $basePrice
                * ((100 - $productDiscount) / 100)
                * ((100 - $variantDiscount) / 100);
              $qty = (float) ($it->quantity ?? 0);
              return $price * $qty;
            });
            $packageDiscount = (float) ($package->discount_percentage ?? 0);
            $packageTotalCalculated = $packageTotalBeforeDiscount * ((100 - $packageDiscount) / 100);
            $packageTotal = !is_null($package->package_price)
              ? (float) $package->package_price
              : $packageTotalCalculated;
          @endphp
          <div class="col-12 col-sm-6 col-md-4 col-lg-3 mb-4 package-item" data-name="{{ strtolower($package->name) }}">
            <div class="card card-product h-100">
              @if($firstImage)
                <img src="{{ $firstImage }}" class="card-img-top" style="height: 300px; object-fit: cover;">
              @else
                <div class="d-flex align-items-center justify-content-center" style="height: 300px; background-color: #eee;">
                  <i class="bi bi-box-seam text-muted fs-1"></i>
                </div>
              @endif
              <div class="card-body text-start">
                <h5 class="fw-bold text-dark">{{ $package->name }}</h5>
                <p class="text-muted mb-1">{{ $package->description ?: 'Paquete personalizado.' }}</p>
                <p class="small mb-1">{{ $package->items->count() }} material(es)</p>
                <p class="fw-semibold mb-2">{{ number_format($packageTotal, 2) }} $</p>
                @if(!is_null($package->package_price))
                  <p class="text-dark small mb-2">Precio fijo combo</p>
                @endif
                @if($packageDiscount > 0)
                  <p class="text-success small mb-2">Descuento del paquete: {{ number_format($packageDiscount, 2) }}%</p>
                @endif
                <div class="d-flex gap-2 align-items-center">
                  <input type="number" min="1" value="1" class="form-control form-control-sm" id="tenant-pack-qty-{{ $package->id }}" style="max-width: 90px;">
                  <button type="button" class="btn btn-dark btn-sm js-add-tenant-package" data-package-id="{{ $package->id }}">Agregar paquete</button>
                </div>
              </div>
            </div>
          </div>
        @endforeach
      </div>
    </div>
  </section>
  @endif

  <footer class="py-4 text-center bg-dark text-white">
    <p>© 2025 {{ $tenant->name }} - SHOPIX. Todos los derechos reservados.</p>
  </footer>

  @include('partials.tenant-cart-offcanvas')

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

<script>
  const navLinksCollapse = document.querySelectorAll('#landingNavbar .nav-link, #landingNavbar .btn');
  const navbarCollapse = document.getElementById('landingNavbar');
  const bsCollapse = navbarCollapse ? new bootstrap.Collapse(navbarCollapse, { toggle: false }) : null;

  navLinksCollapse.forEach(link => {
    link.addEventListener('click', () => {
      if (window.innerWidth < 992 && navbarCollapse.classList.contains('show') && bsCollapse) {
        bsCollapse.hide();
      }
    });
  });

  const categoryLinks = document.querySelectorAll('.category-link');
  const products = document.querySelectorAll('.product-item');
  const packageItems = document.querySelectorAll('.package-item');
  const packagesSection = document.getElementById('paquetes');
  const searchInput = document.getElementById('product-search');
  @php
    $tenantPackagesPayload = ($materialPackages ?? collect())->map(function ($package) {
      return [
        'id' => $package->id,
        'name' => $package->name,
        'discount_percentage' => (float) ($package->discount_percentage ?? 0),
        'package_price' => !is_null($package->package_price) ? (float) $package->package_price : null,
        'items' => $package->items->map(function ($item) {
          $basePrice = (float) ($item->variant->price ?? 0);
          $productDiscount = (float) ($item->variant->product->discount_percentage ?? 0);
          $variantDiscount = (float) ($item->variant->discount_percentage ?? 0);
          $effectivePrice = $basePrice * ((100 - $productDiscount) / 100) * ((100 - $variantDiscount) / 100);

          return [
            'variant_id' => $item->product_variant_id,
            'variant_size' => $item->variant->size ?? '',
            'variant_price' => (float) $effectivePrice,
            'product_name' => $item->variant->product->name ?? 'Producto',
            'quantity' => (float) ($item->quantity ?? 0),
          ];
        })->values()->toArray(),
      ];
    })->values();
  @endphp
  const tenantPackages = @json($tenantPackagesPayload);
  const tenantSlug = @json($tenant->slug);

  function sendCartCommand(type, detail = {}) {
    document.dispatchEvent(new CustomEvent('shopix-cart-command', {
      detail: { type, ...detail }
    }));
  }

  function addTenantPackageToCart(packageId) {
    const pkg = tenantPackages.find(p => Number(p.id) === Number(packageId));
    if (!pkg) {
      alert('No se encontró el paquete.');
      return;
    }

    const qtyInput = document.getElementById(`tenant-pack-qty-${packageId}`);
    const packQty = Math.max(1, parseInt(qtyInput?.value || '1', 10));

    pkg.items.forEach(component => {
      const quantity = (Number(component.quantity || 0) * packQty);
      if (quantity <= 0) return;
      const packageDiscount = Math.max(0, Math.min(100, Number(pkg.discount_percentage || 0)));
      const packageBaseTotal = pkg.items.reduce((sum, row) => {
        const rowQty = Number(row.quantity || 0);
        const rowBasePrice = Number(row.variant_price || 0);
        return sum + (rowBasePrice * ((100 - packageDiscount) / 100) * rowQty);
      }, 0);

      const targetPackageTotal = (pkg.package_price !== null && pkg.package_price !== undefined)
        ? (Number(pkg.package_price) || 0)
        : packageBaseTotal;

      const priceScale = packageBaseTotal > 0 ? (targetPackageTotal / packageBaseTotal) : 1;

      const componentPrice = Number(component.variant_price || 0)
        * ((100 - packageDiscount) / 100)
        * priceScale;

      sendCartCommand('add-item', {
        item: {
          variantId: Number(component.variant_id),
          productId: Number(component.variant_id),
          productName: `${component.product_name} [${pkg.name}]`,
          variantSize: component.variant_size,
          price: componentPrice,
          qty: quantity,
        }
      });
    });

    sendCartCommand('open-cart');

    alert(`Paquete "${pkg.name}" agregado al carrito.`);
  }

  document.querySelectorAll('.js-add-tenant-package').forEach(button => {
    button.addEventListener('click', () => {
      addTenantPackageToCart(button.dataset.packageId);
    });
  });

  let activeCategory = 'all';

  function filterProducts() {
    const searchText = searchInput.value.toLowerCase();
    const isAll = activeCategory === 'all';
    const isPackages = activeCategory === 'packages';

    products.forEach(product => {
      const matchesCategory =
        isAll || (!isPackages && product.dataset.category === activeCategory);

      const matchesSearch =
        product.dataset.name.includes(searchText);

      product.style.display =
        matchesCategory && matchesSearch ? 'block' : 'none';
    });

    let hasVisiblePackage = false;
    packageItems.forEach(item => {
      const matchesSearch = (item.dataset.name || '').includes(searchText);
      const visible = (isAll || isPackages) && matchesSearch;
      item.style.display = visible ? 'block' : 'none';
      if (visible) {
        hasVisiblePackage = true;
      }
    });

    if (packagesSection) {
      packagesSection.style.display = hasVisiblePackage ? 'block' : 'none';
    }
  }

  categoryLinks.forEach(link => {
    link.addEventListener('click', e => {
      e.preventDefault();

      categoryLinks.forEach(l => l.classList.remove('active'));
      link.classList.add('active');

      activeCategory = link.dataset.id;
      filterProducts();
    });
  });

  searchInput.addEventListener('input', filterProducts);
  filterProducts();
</script>
</body>

</html>
