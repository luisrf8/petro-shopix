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

    .landing-nav-link {
      font-weight: 600;
      padding: 0.4rem 0.75rem;
    }

    .landing-header .navbar-toggler {
      border: 1px solid rgba(0, 0, 0, 0.2);
      background: #fff;
      padding: 0.35rem 0.55rem;
    }

    .hero {
      position: relative;
      min-height: 100vh;
      display: flex;
      align-items: center;
      justify-content: center;
      text-align: center;
      color: white;
      background-size: cover;
      overflow: hidden;
    }

    .hero-overlay {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      z-index: 0;
    }

    .hero .container {
      z-index: 1;
      padding-top: 5.5rem;
    }

    .hero-title {
      font-size: 4rem;
      font-weight: 700;
      line-height: 1.1;
      color: #fff;
      text-shadow: 2px 2px 8px rgba(0, 0, 0, 1);
    }

    .hero-slogan {
      font-size: 4rem;
      font-weight: 600;
      line-height: 1.25;
      color: #fff;
      text-shadow: 2px 2px 8px rgba(0, 0, 0, 1);
      margin-top: 0.5rem;
    }

    .hero-description {
      font-size: clamp(0.95rem, 2.8vw, 1.2rem);
      color: #fff;
      text-shadow: 1px 1px 6px rgba(0, 0, 0, 0.8);
      max-width: 850px;
      margin: 0.75rem auto 0;
    }

    .section-title {
      font-size: clamp(1.4rem, 4.5vw, 2rem);
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
    font-size: clamp(1rem, 3vw, 1.5rem);
    font-weight: 700;
    text-align: center;
}

    @media (max-width: 991.98px) {
      .landing-header {
        background: rgba(255, 255, 255, 0.96);
      }

      .hero {
        min-height: 85vh;
      }

      .category-card {
        height: 220px;
      }

      .navbar-nav {
        padding-top: 0.5rem;
      }

      .navbar-nav .nav-item {
        margin-bottom: 0.5rem;
      }

      .hero-title {
        font-size: clamp(1.6rem, 5.6vw, 3rem);
      }

      .hero-slogan {
        font-size: clamp(1rem, 3.2vw, 1.6rem);
      }

      .landing-nav-link {
        display: block;
        width: 100%;
        text-align: center;
      }
    }

    @media (max-width: 575.98px) {
      .hero .container {
        padding-top: 6rem;
      }

      .hero-title {
        font-size: clamp(1.35rem, 8vw, 2rem);
      }

      .hero-slogan {
        font-size: clamp(0.9rem, 4.6vw, 1.25rem);
      }

      .category-card {
        height: 190px;
      }

      .card-product img,
      .card-product .d-flex.align-items-center.justify-content-center {
        height: 220px !important;
      }
    }
  </style>
</head>

<body>

  <!-- HEADER -->
  <header class="landing-header position-fixed top-0 start-0 w-100">
    <div class="container">
      <nav class="navbar navbar-expand-lg navbar-light p-0">
        <a class="navbar-brand d-flex align-items-center" href="#top">
          @if($tenant->logo)
            <span class="btn btn-light p-1 px-3 m-0">
              <img src="{{ asset('storage/' . $tenant->logo) }}" alt="Logo {{ $tenant->name }}" class="img-fluid" style="width: 100px; height: 50px; object-fit: contain;">
            </span>
          @else
            <span class="fw-bold">{{ $tenant->name }}</span>
          @endif
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#landingNavbar" aria-controls="landingNavbar" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="landingNavbar">
          <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
            <li class="nav-item">
              <a class="btn btn-light text-dark landing-nav-link" href="#categorias">Categorías</a>
            </li>
            <li class="nav-item">
              <a class="btn btn-light text-dark landing-nav-link" href="#productos">Productos</a>
            </li>
            <li class="nav-item">
              <a class="btn btn-light text-dark landing-nav-link" href="#contacto">Contacto</a>
            </li>
            @include('partials.tenant-cart-nav')
          </ul>
        </div>
      </nav>
    </div>
  </header>

  <!-- HERO -->
  <section id="top" class="hero" style="
      @if(isset($tenant->background_image) && $tenant->background_image)
        background-image: url('{{ asset('storage/' . $tenant->background_image) }}');
      @else
        background-color: {{ $tenant->color_primary ?? '#fdfaf6' }};
      @endif
      background-position: center;
      background-repeat: no-repeat;
      background-size: cover;
      overflow: hidden;
  ">

    <div class="category-overlay"></div>
    <div class="container text-center">
      <h1 class="hero-title">{{ strtoupper($tenant->name) }}</h1>
      <h2 class="hero-slogan">{{ $tenant->slogan ?? '' }}</h2>
      <p class="hero-description">{{ $tenant->description ?? '' }}</p>
    </div>
  </section>

  <!-- CATEGORIAS -->
<section id="categorias" class="py-5 bg-white">
    <div class="container">
        <h2 class="section-title mb-5 text-center">Categorías Principales</h2>
        <div class="row g-4 justify-content-center"> 
            @foreach($categories as $category)
                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                    <a href="#"
                       class="category-card category-link"
                       data-id="{{ $category->id }}"
                       style="background-image: url('{{ asset('storage/'.$category->image) }}')">
                        <div class="category-overlay">
                            <h5 class="category-title text-center">{{ $category->name }}</h5>
                        </div>
                    </a>
                </div>
            @endforeach

              @if(isset($materialPackages) && $materialPackages->count() > 0)
                <div class="col-12 col-sm-6 col-md-4 col-lg-3">
                  <a href="#"
                     class="category-card category-link"
                     data-id="packages"
                     style="background-image: linear-gradient(135deg, rgba(0,0,0,.75), rgba(0,0,0,.35));">
                    <div class="category-overlay">
                      <h5 class="category-title text-center">Paquetes</h5>
                    </div>
                  </a>
                </div>
              @endif
        </div>
    </div>
</section>
  <!-- PRODUCTOS -->
  <section id="productos" class="py-5 bg-light">
    <div class="container">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="section-title text-start mb-0">Productos Destacados</h2>
        <a href="{{ route('tenant.public.categories', ['tenant' => $tenant->slug]) }}" class="btn btn-outline-primary btn-sm">Ver más</a>
      </div>
      <div class="row" id="products-container">
        @foreach($productItems as $product)
          <div class="col-12 col-sm-6 col-lg-4 mb-4 product-item" data-category="{{ $product->category_id }}">
            <a href="{{ route('tenant.public.categories', ['tenant' => $tenant->slug]) }}" class="text-decoration-none d-block h-100">
              <div class="card card-product h-100">
                @if(isset($product->images[0]))
                  <img src="{{ asset('storage/' . $product->images[0]->path) }}" class="card-img-top" style="height: 300px; object-fit: cover;">
                @else
                  <div class="d-flex align-items-center justify-content-center" style="height: 300px; background-color: #eee;">
                    <i class="bi bi-image text-muted fs-1"></i>
                  </div>
                @endif
                <div class="card-body text-center">
                  <h5 class="fw-bold text-dark">{{ $product->name }}</h5>
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
    <div class="container">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <h2 class="section-title text-start mb-0">Paquetes y Combos</h2>
      </div>
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
          <div class="col-12 col-sm-6 col-lg-4 mb-4 package-item" data-name="{{ strtolower($package->name) }}">
            <div class="card card-product h-100">
              @if($firstImage)
                <img src="{{ $firstImage }}" class="card-img-top" style="height: 260px; object-fit: cover;">
              @else
                <div class="d-flex align-items-center justify-content-center" style="height: 260px; background-color: #eee;">
                  <i class="bi bi-box-seam text-muted fs-1"></i>
                </div>
              @endif
              <div class="card-body text-start">
                <h5 class="fw-bold text-dark">{{ $package->name }}</h5>
                <p class="text-muted mb-2">{{ $package->description ?: 'Paquete personalizado de productos.' }}</p>
                <p class="small mb-2">Incluye {{ $package->items->count() }} material(es)</p>
                <p class="fw-semibold mb-2">{{ number_format($packageTotal, 2) }} $</p>
                @if(!is_null($package->package_price))
                  <p class="text-dark small mb-2">Precio fijo combo</p>
                @endif
                @if($packageDiscount > 0)
                  <p class="text-success small mb-2">Descuento del paquete: {{ number_format($packageDiscount, 2) }}%</p>
                @endif
                <div class="d-flex gap-2 align-items-center">
                  <input type="number" min="1" value="1" class="form-control form-control-sm" id="tenant-pack-qty-{{ $package->id }}" style="max-width: 90px;">
                  <button type="button" class="btn btn-dark btn-sm" onclick="addTenantPackageToCart({{ $package->id }})">Agregar paquete</button>
                </div>
              </div>
            </div>
          </div>
        @endforeach
      </div>
    </div>
  </section>
  @endif
  <!-- CONTACTO / UBICACIÓN -->
  <section id="contacto" class="py-5 bg-white">
    <div class="container">
      <div class="row align-items-center">
        <div class="col-12 col-md-6 mb-4 mb-md-0">
          <h2 class="section-title text-start mb-3">Contáctanos</h2>
          <p class="mb-3">{{ $tenant->name ?? '' }} - {{ $tenant->description ?? '' }}.</p>
          <p class="">Somos una empresa de {{ $tenant->country ?? '' }} - {{ $tenant->state ?? '' }} - {{ $tenant->city ?? '' }}.</p>
          <p class="">Ubicada en {{ $tenant->address ?? '' }}.</p>
          @php
              $whatsapp = preg_replace('/\D/', '', $tenant->phone_code . $tenant->phone_number);
              $mapsUrl = null;
              if (!empty($tenant->latitude) && !empty($tenant->longitude)) {
                  $mapsUrl = 'https://www.google.com/maps?q=' . $tenant->latitude . ',' . $tenant->longitude;
              } else {
                  $addressParts = array_filter([
                      $tenant->address ?? '',
                      $tenant->city ?? '',
                      $tenant->state ?? '',
                      $tenant->country ?? '',
                  ]);
                  if (!empty($addressParts)) {
                      $mapsUrl = 'https://www.google.com/maps/search/?api=1&query=' . urlencode(implode(', ', $addressParts));
                  }
              }
          @endphp
          <div class="d-flex flex-wrap gap-2">
            <a href="https://api.whatsapp.com/send?phone={{ $whatsapp }}" target="_blank" class="btn btn-primary px-4 py-2">
              Escríbenos por WhatsApp
            </a>
            @if(!empty($mapsUrl))
              <a href="{{ $mapsUrl }}" target="_blank" rel="noopener noreferrer" class="btn btn-outline-dark px-4 py-2">
                Ver ubicación en Google Maps
              </a>
            @endif
          </div>
          <div class="mt-4 d-flex gap-4">

              @if(!empty($tenant->instagram))
                  <a href="https://www.instagram.com/{{ ltrim($tenant->instagram, '@') }}"
                    target="_blank"
                    class="text-dark fs-4">
                      <i class="bi bi-instagram"></i>
                  </a>
              @endif

              @if(!empty($tenant->facebook))
                  <a href="{{ $tenant->facebook }}"
                    target="_blank"
                    class="text-dark fs-4">
                      <i class="bi bi-facebook"></i>
                  </a>
              @endif

              @if(!empty($tenant->telegram))
                  <a href="https://t.me/{{ ltrim($tenant->telegram, '@') }}"
                    target="_blank"
                    class="text-dark fs-4">
                      <i class="bi bi-telegram"></i>
                  </a>
              @endif

          </div>
        </div>
        <div class="col-12 col-md-6">
            <div class="rounded-4 overflow-hidden shadow" style="height: 300px;">
              @if($tenant->latitude && $tenant->longitude)
                <a href="https://www.google.com/maps?q={{ $tenant->latitude }},{{ $tenant->longitude }}"
                  target="_blank"
                  class="d-block h-100">
                    <iframe
                        src="https://www.google.com/maps?q={{ $tenant->latitude }},{{ $tenant->longitude }}&output=embed"
                        width="100%" height="100%" style="border:0;">
                    </iframe>
                </a>
              @else
                  <div class="d-flex align-items-center justify-content-center h-100 text-muted">
                      Ubicación no disponible
                  </div>
              @endif
            </div>
        </div>
      </div>
    </div>
  </section>
  <footer class="py-4 text-center bg-dark text-white">
    <p>© 2025 {{ $tenant->name }} - SHOPIX. Todos los derechos reservados.</p>
  </footer>

  @include('partials.tenant-cart-offcanvas')

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    const navLinks = document.querySelectorAll('#landingNavbar .nav-link, #landingNavbar .btn');
    const navbarCollapse = document.getElementById('landingNavbar');
    const bsCollapse = navbarCollapse ? new bootstrap.Collapse(navbarCollapse, { toggle: false }) : null;

    navLinks.forEach(link => {
      link.addEventListener('click', () => {
        if (window.innerWidth < 992 && navbarCollapse.classList.contains('show') && bsCollapse) {
          bsCollapse.hide();
        }
      });
    });

    // Filtrado de productos por categoría
    const categoryLinks = document.querySelectorAll('.category-link');
    const products = document.querySelectorAll('.product-item');
    const tenantPackages = @json(($materialPackages ?? collect())->map(function($package) {
      return [
        'id' => $package->id,
        'name' => $package->name,
        'discount_percentage' => (float) ($package->discount_percentage ?? 0),
        'package_price' => !is_null($package->package_price) ? (float) $package->package_price : null,
        'items' => $package->items->map(function($item) {
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
    })->values());

    window.addTenantPackageToCart = function (packageId) {
      const pkg = tenantPackages.find(p => Number(p.id) === Number(packageId));
      if (!pkg) {
        alert('No se encontró el paquete.');
        return;
      }

      const qtyInput = document.getElementById(`tenant-pack-qty-${packageId}`);
      const packQty = Math.max(1, parseInt(qtyInput?.value || '1', 10));

      if (!window.ShopixCart || typeof window.ShopixCart.addItem !== 'function') {
        alert('No se pudo abrir el carrito.');
        return;
      }

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

        window.ShopixCart.addItem({
          variantId: Number(component.variant_id),
          productId: Number(component.variant_id),
          productName: `${component.product_name} [${pkg.name}]`,
          variantSize: component.variant_size,
          price: componentPrice,
          qty: quantity,
        });
      });

      alert(`Paquete "${pkg.name}" agregado al carrito.`);
    }

    const packagesSection = document.getElementById('paquetes');

    categoryLinks.forEach(link => {
      link.addEventListener('click', e => {
        e.preventDefault();

        categoryLinks.forEach(l => l.classList.remove('active'));
        link.classList.add('active');

        const categoryId = link.dataset.id;
        products.forEach(product => {
          if(categoryId === 'all' || (categoryId !== 'packages' && product.dataset.category === categoryId)){
            product.style.display = 'block';
          } else {
            product.style.display = 'none';
          }
        });

        if (packagesSection) {
          const showPackages = categoryId === 'all' || categoryId === 'packages';
          packagesSection.style.display = showPackages ? 'block' : 'none';
        }
      });
    });
  </script>
</body>
</html>
