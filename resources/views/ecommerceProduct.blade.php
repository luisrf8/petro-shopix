<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>{{ $tenant->name }} - Detalle de Producto</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

  @php
    $normalizeTenantHex = function ($value, $fallback) {
      $candidate = strtoupper(trim((string) $value));
      if (preg_match('/^#[0-9A-F]{6}$/', $candidate)) {
        return $candidate;
      }

      return strtoupper($fallback);
    };

    $toRgb = function ($hex) {
      $clean = ltrim($hex, '#');
      return [
        hexdec(substr($clean, 0, 2)),
        hexdec(substr($clean, 2, 2)),
        hexdec(substr($clean, 4, 2)),
      ];
    };

    $tenantColorPrimary = $normalizeTenantHex($tenant->color_primary ?? null, '#0F172A');
    $tenantColorSecondary = $normalizeTenantHex($tenant->color_secondary ?? null, '#334155');
    $tenantColorAccent = $normalizeTenantHex($tenant->color_accent ?? null, '#38BDF8');

    [$tenantPrimaryR, $tenantPrimaryG, $tenantPrimaryB] = $toRgb($tenantColorPrimary);
    [$tenantSecondaryR, $tenantSecondaryG, $tenantSecondaryB] = $toRgb($tenantColorSecondary);
    [$tenantAccentR, $tenantAccentG, $tenantAccentB] = $toRgb($tenantColorAccent);
  @endphp

  <style>
    :root {
      --tenant-primary: {{ $tenantColorPrimary }};
      --tenant-secondary: {{ $tenantColorSecondary }};
      --tenant-accent: {{ $tenantColorAccent }};
      --tenant-primary-rgb: {{ $tenantPrimaryR }}, {{ $tenantPrimaryG }}, {{ $tenantPrimaryB }};
      --tenant-secondary-rgb: {{ $tenantSecondaryR }}, {{ $tenantSecondaryG }}, {{ $tenantSecondaryB }};
      --tenant-accent-rgb: {{ $tenantAccentR }}, {{ $tenantAccentG }}, {{ $tenantAccentB }};
    }

    body {
      font-family: 'Inter', sans-serif;
      background-color: #f3f4f6;
      color: #111827;
    }

    .landing-header {
      transition: background 0.3s ease-in-out;
      z-index: 1050;
      background: linear-gradient(135deg, rgba(var(--tenant-primary-rgb), 0.82), rgba(var(--tenant-secondary-rgb), 0.72));
      backdrop-filter: blur(8px);
      border-bottom: 1px solid rgba(var(--tenant-accent-rgb), 0.28);
      padding: 0.5rem 0;
    }

    .landing-header .navbar-toggler {
      border: 1px solid rgba(var(--tenant-accent-rgb), 0.55);
      background: rgba(var(--tenant-primary-rgb), 0.25);
      padding: 0.35rem 0.55rem;
    }

    .landing-nav-link {
      font-weight: 600;
      padding: 0.4rem 0.75rem;
      border-radius: 999px;
    }

    .section-muted {
      background: #eef2f7;
    }

    .btn-primary {
      background: linear-gradient(135deg, var(--tenant-primary), var(--tenant-secondary));
      border-color: var(--tenant-primary);
    }

    .btn-primary:hover,
    .btn-primary:focus {
      background: linear-gradient(135deg, var(--tenant-secondary), var(--tenant-primary));
      border-color: var(--tenant-secondary);
    }

    .btn-dark {
      background: linear-gradient(135deg, var(--tenant-primary), var(--tenant-secondary));
      border-color: var(--tenant-primary);
    }

    .btn-dark:hover,
    .btn-dark:focus {
      background: linear-gradient(135deg, var(--tenant-secondary), var(--tenant-primary));
      border-color: var(--tenant-secondary);
    }

    .btn-outline-dark {
      color: var(--tenant-primary);
      border-color: rgba(var(--tenant-primary-rgb), 0.45);
    }

    .btn-outline-dark:hover,
    .btn-outline-dark:focus {
      color: #fff;
      background: var(--tenant-primary);
      border-color: var(--tenant-primary);
    }

    .form-check-input:checked {
      background-color: var(--tenant-primary);
      border-color: var(--tenant-primary);
    }

    footer.bg-dark {
      background: linear-gradient(135deg, var(--tenant-primary), var(--tenant-secondary)) !important;
    }

    .page-shell {
      padding-top: 6.2rem;
    }

    .spotlight-card {
      background: #ffffff;
      border: 1px solid #e5e7eb;
      border-radius: 18px;
      padding: 1.25rem;
      box-shadow: 0 12px 24px rgba(15, 23, 42, 0.08);
    }

    .spotlight-kicker {
      font-size: 0.78rem;
      font-weight: 700;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      color: #64748b;
      margin-bottom: 0.35rem;
    }

    .spotlight-title {
      font-size: clamp(1.5rem, 4vw, 2.25rem);
      line-height: 1.12;
      margin-bottom: 0.35rem;
      color: var(--tenant-primary);
      font-weight: 700;
    }

    .spotlight-desc {
      color: #475569;
      margin-bottom: 0.75rem;
      font-size: 0.96rem;
    }

    .trust-pills {
      display: flex;
      flex-wrap: wrap;
      gap: 0.5rem;
    }

    .trust-pill {
      border: 1px solid rgba(var(--tenant-accent-rgb), 0.45);
      background: rgba(var(--tenant-accent-rgb), 0.12);
      color: var(--tenant-primary);
      border-radius: 999px;
      font-size: 0.8rem;
      font-weight: 600;
      padding: 0.35rem 0.75rem;
    }

    .product-detail-card {
      max-width: 1120px;
      min-height: 66vh;
      margin: 16px auto 28px;
      padding: 30px;
      border-radius: 16px;
      border: 1px solid #e5e7eb;
      box-shadow: 0 12px 24px rgba(15, 23, 42, 0.08);
      background-color: #fff;
    }

    .main-image {
      max-height: 500px;
      width: 100%;
      object-fit: cover;
      border-radius: 12px;
      border: 1px solid #e5e7eb;
    }

    .product-gallery-main .d-flex.align-items-center.justify-content-center.rounded-3 {
      border: 1px solid #e5e7eb;
      border-radius: 12px;
      background-color: #f8fafc !important;
    }

    .product-meta-card {
      border: 1px solid #e2e8f0;
      border-radius: 14px;
      background: #f8fafc;
      padding: 0.95rem;
      margin-bottom: 1rem;
    }

    .variant-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 0.5rem;
      margin-bottom: 0.5rem;
    }

    .secure-box {
      border: 1px solid #dbe3ee;
      border-radius: 12px;
      background: #f8fafc;
      padding: 0.8rem;
      margin-top: 0.85rem;
      color: #334155;
      font-size: 0.9rem;
    }

    .secure-box .item {
      display: flex;
      align-items: center;
      gap: 0.45rem;
      margin-bottom: 0.45rem;
    }

    .secure-box .item:last-child {
      margin-bottom: 0;
    }

    .thumbnail-image {
      width: 80px;
      height: 80px;
      object-fit: cover;
      border: 2px solid #d1d5db;
      border-radius: 8px;
      cursor: pointer;
      transition: border-color 0.2s;
    }

    .thumbnail-image:hover,
    .thumbnail-image.active {
      border-color: var(--tenant-primary);
    }

    .variant-item {
      list-style-type: disc;
      margin-left: 20px;
      padding: 5px 0;
      color: #333;
    }
    
    .variant-price {
        font-weight: 700;
        color: #1a1a1a;
    }

    .variant-button {
        cursor: pointer;
        transition: background-color 0.2s, border-color 0.2s, color 0.2s;
        padding: 8px 12px;
        margin-right: 8px;
        margin-bottom: 8px;
      border: 1px solid #d1d5db;
      border-radius: 10px;
        background-color: #fff;
      color: #111827;
        font-weight: 500;
        display: inline-block;
        min-width: 120px;
    }

    .variant-button.selected {
      background: linear-gradient(135deg, var(--tenant-primary), var(--tenant-secondary));
      border-color: var(--tenant-primary);
      color: #fff;
    }

    .variant-button.selected .text-muted {
      color: rgba(255, 255, 255, 0.85) !important;
    }

    .variant-button:disabled {
        background-color: #f8f9fa;
        border-color: #eee;
        color: #999;
        cursor: not-allowed;
    }

    .product-gallery-thumbs {
      order: 1;
      margin-right: 1rem;
    }

    .product-gallery-main {
      order: 2;
    }

    @media (max-width: 991.98px) {
      .landing-header {
        background: linear-gradient(135deg, rgba(var(--tenant-primary-rgb), 0.92), rgba(var(--tenant-secondary-rgb), 0.84));
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

      .product-detail-card {
        max-width: 95vw;
        margin: 25px auto;
        padding: 20px;
      }

      .spotlight-card {
        padding: 1rem;
      }

      .spotlight-title {
        font-size: clamp(1.25rem, 6vw, 1.8rem);
      }

      .product-gallery-main {
        order: 1;
        width: 100%;
      }

      .product-gallery-thumbs {
        order: 2;
        width: 100%;
        margin-right: 0;
      }

      #thumbnail-gallery {
        display: flex !important;
        flex-direction: row !important;
        gap: 0.5rem;
        overflow-x: auto;
        padding-bottom: 0.25rem;
      }

      #thumbnail-gallery .thumbnail-image,
      #thumbnail-gallery .border.rounded-3 {
        flex: 0 0 auto;
      }
    }
  </style>
</head>

<body>
  <header class="landing-header position-fixed top-0 start-0 w-100">
    <div class="container">
      <nav class="navbar navbar-expand-lg navbar-dark p-0">
        <a class="navbar-brand d-flex align-items-center" href="{{ route('tenant.public', ['tenant' => $tenant->slug]) }}">
          @if($tenant->logo)
            <span class="btn btn-light p-1 px-3 m-0">
              <img src="{{ \App\Support\ImageStorage::url($tenant->logo) ?? asset('assets/img/shopix5.png') }}" alt="Logo {{ $tenant->name }}" class="img-fluid" style="width: 100px; height: 50px; object-fit: contain;">
            </span>
          @else
            <span class="fw-bold text-white">{{ $tenant->name }}</span>
          @endif
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#landingNavbar" aria-controls="landingNavbar" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="landingNavbar">
          <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
            @include('partials.tenant-cart-nav')
            <li class="nav-item">
              <a class="btn btn-outline-light landing-nav-link" href="{{ route('tenant.public.categories', ['tenant' => $tenant->slug]) }}">Volver</a>
            </li>
          </ul>
        </div>
      </nav>
    </div>
  </header>
  <section class="py-10 section-muted page-shell">
    <div class="container">
      <div class="spotlight-card">
        <div class="d-flex flex-column flex-lg-row justify-content-between align-items-lg-center gap-3">
          <div>
            <div class="spotlight-kicker">Producto seleccionado</div>
            <h1 class="spotlight-title">{{ $product->name }}</h1>
            <p class="spotlight-desc">{{ \Illuminate\Support\Str::limit($product->description ?? 'Producto destacado en esta tienda.', 170) }}</p>
            <div class="trust-pills">
              <span class="trust-pill"><i class="bi bi-shield-check me-1"></i> Compra segura</span>
              <span class="trust-pill"><i class="bi bi-lock me-1"></i> Datos protegidos</span>
              <span class="trust-pill"><i class="bi bi-headset me-1"></i> Soporte directo</span>
            </div>
          </div>
          <div class="d-flex gap-2 flex-wrap">
            <a class="btn btn-outline-dark" href="{{ route('tenant.public.categories', ['tenant' => $tenant->slug]) }}">
              <i class="bi bi-arrow-left me-1"></i> Volver al catálogo
            </a>
          </div>
        </div>
      </div>

      <div class="product-detail-card">
        <div class="row">
          <div class="col-md-5 d-flex flex-column flex-md-row mb-4 mb-md-0 gap-3 align-items-start">
            <div class="product-gallery-thumbs">
              <div class="d-flex flex-column gap-2" id="thumbnail-gallery">
                @if(count($product->images) > 0)
                  @foreach($product->images as $index => $image)
                    <img 
                      src="{{ \App\Support\ImageStorage::url($image->path) ?? asset('assets/img/shopix5.png') }}" 
                      alt="Miniatura {{ $index + 1 }}" 
                      class="thumbnail-image {{ $index === 0 ? 'active' : '' }}" 
                      data-main-src="{{ \App\Support\ImageStorage::url($image->path) ?? asset('assets/img/shopix5.png') }}"
                    >
                  @endforeach
                @else
                   <div class="d-flex align-items-center justify-content-center border rounded-3" style="width: 80px; height: 80px; background-color: #eee;">
                    <i class="bi bi-image text-muted fs-3"></i>
                  </div>
                @endif
              </div>
            </div>
            
            <div class="product-gallery-main flex-grow-1">
              @if(isset($product->images[0]))
                <img 
                  src="{{ \App\Support\ImageStorage::url($product->images[0]->path) ?? asset('assets/img/shopix5.png') }}" 
                  alt="Imagen Principal de {{ $product->name }}" 
                  class="main-image" 
                  id="main-product-image"
                >
              @else
                <div class="d-flex align-items-center justify-content-center rounded-3" style="height: 500px; background-color: #eee;">
                  <i class="bi bi-image text-muted fs-1"></i>
                </div>
              @endif
            </div>
          </div>

          <div class="col-md-7 ps-md-5">
            <div class="product-meta-card">
              <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap mb-2">
                <h2 class="h4 fw-bold mb-0">{{ $product->name }}</h2>
                <span class="badge text-bg-light border">{{ $product->variants->count() }} variante{{ $product->variants->count() == 1 ? '' : 's' }}</span>
              </div>
              <p class="text-muted mb-0">{{ $product->description }}</p>
            </div>

            <div class="variant-header">
              <h5 class="fw-semibold mb-0">Selecciona una variante</h5>
              <small class="text-muted">Elige talla o presentación</small>
            </div>
            <div id="variants-container" class="d-flex flex-wrap gap-2 mb-2">
                @forelse ($product->variants as $variant)
                @php
                  $productDiscount = (float) ($product->discount_percentage ?? 0);
                  $variantDiscount = (float) ($variant->discount_percentage ?? 0);
                  $effectiveVariantPrice = (float) $variant->price * ((100 - $productDiscount) / 100) * ((100 - $variantDiscount) / 100);
                @endphp
                    <div 
                        class="variant-button"
                      data-variant-id="{{ $variant->id }}"
                        data-size="{{ $variant->size }}"
                  data-price="{{ number_format($effectiveVariantPrice, 2, '.', '') }}"
                        data-stock="{{ $variant->stock }}"
                        data-product-name="{{ $product->name }}"
                        {{ $variant->stock <= 0 ? 'disabled' : '' }}
                    >
                        <span class="fw-semibold">{{ $variant->size }}</span>
                  <span class="text-muted small">/ {{ number_format($effectiveVariantPrice, 2) }} $</span>
                  @if($productDiscount > 0 || $variantDiscount > 0)
                    <small class="text-success d-block">Desc: {{ number_format($productDiscount + $variantDiscount, 2) }}%</small>
                  @endif
                        @if ($variant->stock <= 0)
                            <span class="badge bg-danger ms-1">Agotado</span>
                        @endif
                    </div>
                @empty
                    <p class="text-muted">No hay variantes disponibles.</p>
                @endforelse
            </div>

            <div class="secure-box">
              <div class="item"><i class="bi bi-shield-lock"></i><span>Proceso de compra protegido para el usuario.</span></div>
              <div class="item"><i class="bi bi-credit-card"></i><span>Información clara de precio por variante seleccionada.</span></div>
              <div class="item"><i class="bi bi-chat-dots"></i><span>Soporte directo por carrito o WhatsApp según configuración.</span></div>
            </div>

            @if($cartEnabled)
              <div class="mt-4 pt-3 border-top d-flex flex-column flex-sm-row justify-content-center gap-2">
                <button
                  id="add-to-cart-button"
                  class="btn btn-primary btn-lg"
                  disabled
                >
                  <i class="bi bi-cart-plus me-2"></i> Agregar al carrito
                </button>
                <button
                  id="open-cart-button"
                  class="btn btn-outline-dark btn-lg"
                  type="button"
                >
                  <i class="bi bi-cart3 me-2"></i> Ver carrito
                </button>
              </div>
            @else
              <div class="mt-4 pt-3 border-top d-flex justify-content-center">
                <button
                  id="whatsapp-button"
                  class="btn btn-success btn-lg"
                  disabled
                >
                  <i class="bi bi-whatsapp me-2"></i> Comunicarme por WhatsApp por este producto
                </button>
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
    document.addEventListener('DOMContentLoaded', () => {
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

        const mainImage = document.getElementById('main-product-image');
        const thumbnails = document.querySelectorAll('.thumbnail-image');

        if (mainImage) {
            thumbnails.forEach(thumbnail => {
                thumbnail.addEventListener('click', () => {
                    // Cambiar la imagen principal
                    mainImage.src = thumbnail.dataset.mainSrc;

                    // Actualizar el estado activo de las miniaturas
                    thumbnails.forEach(t => t.classList.remove('active'));
                    thumbnail.classList.add('active');
                });
            });
        }
        const variantButtons = document.querySelectorAll('.variant-button:not([disabled])');
        const cartEnabled = @json((bool) ($cartEnabled ?? false));
        const addToCartButton = document.getElementById('add-to-cart-button');
        const openCartButton = document.getElementById('open-cart-button');
        const whatsappButton = document.getElementById('whatsapp-button');
        let selectedVariant = null;
        const tenantSlug = @json($tenant->slug);

        const tenantPhoneCode = @json($tenant->phone_code ?? '');
        const tenantPhoneNumber = @json($tenant->phone_number ?? '');
        const shopixDebug = true;

        function cartDebug(...args) {
          if (!shopixDebug) return;
          console.log('[ShopixCart Debug][Product]', ...args);
        }

        function sendCartCommand(type, detail = {}) {
          cartDebug('sendCartCommand', { type, detail });
          document.dispatchEvent(new CustomEvent('shopix-cart-command', {
            detail: { type, ...detail }
          }));
        }

        function openCartSimple() {
          sendCartCommand('open-cart');
        }

        // --- Lógica de selección de variantes ---
        variantButtons.forEach(button => {
            button.addEventListener('click', () => {
                // 1. Desactivar todos los botones
                variantButtons.forEach(btn => btn.classList.remove('selected'));

                // 2. Activar el botón seleccionado
                button.classList.add('selected');
                
                // 3. Almacenar la variante seleccionada
                selectedVariant = {
                    variantId: button.dataset.variantId,
                    size: button.dataset.size,
                    price: button.dataset.price,
                  productName: button.dataset.productName,
                  productId: @json($product->id)
                };

                cartDebug('variant:selected', selectedVariant);

                if (cartEnabled && addToCartButton) {
                  addToCartButton.disabled = false;
                }

                if (!cartEnabled && whatsappButton) {
                  whatsappButton.disabled = false;
                }
            });
        });

        if (cartEnabled && addToCartButton) {
          addToCartButton.addEventListener('click', () => {
              cartDebug('add-click:triggered', {
                selectedVariant,
                buttonDisabled: addToCartButton.disabled,
              });

              if (!selectedVariant) {
                  cartDebug('add-click:aborted-no-variant');
                  alert('Por favor, selecciona una variante primero.');
                  return;
              }

              const payload = {
                variantId: selectedVariant.variantId,
                productId: selectedVariant.productId,
                productName: selectedVariant.productName,
                variantSize: selectedVariant.size,
                price: Number(selectedVariant.price),
                qty: 1
              };

              cartDebug('add-click:payload', payload);
              sendCartCommand('add-item', { item: payload });

              const storageKey = `shopix_cart_${tenantSlug}`;
              try {
                const persisted = JSON.parse(localStorage.getItem(storageKey) || '[]');
                cartDebug('add-click:post-persist-cart', persisted);
              } catch (error) {
                console.error('[ShopixCart Debug][Product] add-click:post-persist-parse-error', error);
              }

              openCartSimple();
          });
        }

        if (cartEnabled && openCartButton) {
          openCartButton.addEventListener('click', () => {
              openCartSimple();
          });
        }

        if (!cartEnabled && whatsappButton) {
          whatsappButton.addEventListener('click', () => {
            if (!selectedVariant) {
              alert('Por favor, selecciona una variante primero.');
              return;
            }

            const fullPhoneNumber = String(tenantPhoneCode).replace(/\D/g, '') + String(tenantPhoneNumber).replace(/\D/g, '');
            if (!fullPhoneNumber) {
              alert('La tienda no tiene un número de WhatsApp configurado.');
              return;
            }

            const message = `Hola, estoy interesado en el producto *${selectedVariant.productName}* en la variante *${selectedVariant.size}* con precio de *${selectedVariant.price} $*. ¿Podrían darme más información?`;
            const whatsappLink = `https://wa.me/${fullPhoneNumber}?text=${encodeURIComponent(message)}`;
            window.open(whatsappLink, '_blank');
          });
        }
    });
  </script>
</body>

</html>