<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>{{ $tenant->name }} - Detalle de Producto</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    body {
      font-family: 'Inter', sans-serif;
      background-color: #f8f9fa; /* Fondo más claro para la página de detalle */
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

    .product-detail-card {
      max-width: 90vw;
      min-height: 70vh;
      margin: 50px auto;
      padding: 30px;
      border-radius: 12px;
      box-shadow: 0 4px 25px rgba(0, 0, 0, 0.1);
      background-color: #fff;
    }

    .main-image {
      max-height: 500px;
      width: 100%;
      object-fit: cover;
      border-radius: 8px;
    }

    .thumbnail-image {
      width: 80px;
      height: 80px;
      object-fit: cover;
      border: 2px solid transparent;
      border-radius: 4px;
      cursor: pointer;
      transition: border-color 0.2s;
    }

    .thumbnail-image:hover,
    .thumbnail-image.active {
      border-color: #007bff; /* Color de realce para la miniatura activa */
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

    /* Estilos para simular los botones de la imagen (Edit/Add Image/Delete) */
    .btn-action {
        margin-right: 10px;
    }
    .variant-button {
        cursor: pointer;
        transition: background-color 0.2s, border-color 0.2s, color 0.2s;
        padding: 8px 15px;
        margin-right: 10px;
        margin-bottom: 10px;
        border: 1px solid #ccc;
        border-radius: 6px;
        background-color: #fff;
        color: #333;
        font-weight: 500;
        display: inline-block;
    }

    .variant-button.selected {
        background-color: #d4d4d4ff; /* Color primario de Bootstrap para selección */
        border-color: #aaaaaaff;
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

      .product-detail-card {
        max-width: 95vw;
        margin: 35px auto;
        padding: 20px;
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
      <nav class="navbar navbar-expand-lg navbar-light p-0">
        <a class="navbar-brand d-flex align-items-center" href="{{ route('tenant.public', ['tenant' => $tenant->slug]) }}">
          @if($tenant->logo)
            <span class="btn btn-light p-1 px-3 m-0">
              <img src="{{ \App\Support\ImageStorage::url($tenant->logo) ?? asset('assets/img/shopix5.png') }}" alt="Logo {{ $tenant->name }}" class="img-fluid" style="width: 100px; height: 50px; object-fit: contain;">
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
            @include('partials.tenant-cart-nav')
            <li class="nav-item">
              <a class="btn btn-light text-dark landing-nav-link" href="{{ route('tenant.public.categories', ['tenant' => $tenant->slug]) }}">Volver</a>
            </li>
          </ul>
        </div>
      </nav>
    </div>
  </header>
  <section class="py-5">
    <div class="">
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
            <h1 class="fw-bold mb-3">{{ $product->name }}</h1>
            <p><strong>Descripción:</strong> {{ $product->description }}</p>

            <h5 class="fw-semibold mt-4">Variantes:</h5>
            <div id="variants-container" class="d-flex flex-wrap gap-2 mb-4">
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

            @if($cartEnabled)
              <div class="mt-4 pt-2 border-top d-flex flex-column flex-sm-row justify-content-center gap-2">
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
              <div class="mt-4 pt-2 border-top d-flex justify-content-center">
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