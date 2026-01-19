<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>{{ $tenant->name }} - Detalle de Producto</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    body {
      font-family: 'Inter', sans-serif;
      background-color: #f8f9fa; /* Fondo más claro para la página de detalle */
      color: #000;
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
  </style>
</head>

<body>
  <div class="w-100 position-fixed top-0 px-4" style="z-index: 1050;">
    <div class="row align-items-center mt-2">
      <div class="col-md-4 d-flex justify-content-start">
        @if($tenant->logo)
          <div class="btn btn-light text-dark fw-bold p-0 m-0">
            <img src="{{ asset('storage/' . $tenant->logo) }}" alt="Logo {{ $tenant->name }}" class="img-fluid" style="width: 100px; height: 50px; filter: drop-shadow(0 4px 6px rgba(0, 0, 0, 0));">
          </div>
        @endif
      </div>
      <div class="col-md-8 d-flex justify-content-end">
            <ul class="nav gap-2">
              <a class="btn btn-light text-dark fw-bold p-1 px-3 m-0 category-link"
                href="{{ route('tenant.public.categories', ['tenant' => $tenant->slug]) }}"
                data-id="">
                Volver
                </a>
            </ul>
      </div>
    </div>
  </div>
  <section class="py-5">
    <div class="">
      <div class="product-detail-card">
        <div class="row">
          <div class="col-md-5 d-flex flex-md-row flex-column mb-4 mb-md-0 gap-4 align-items-start">
            <div class="me-3">
              <div class="d-flex flex-column gap-2" id="thumbnail-gallery">
                @if(count($product->images) > 0)
                  @foreach($product->images as $index => $image)
                    <img 
                      src="{{ asset('storage/' . $image->path) }}" 
                      alt="Miniatura {{ $index + 1 }}" 
                      class="thumbnail-image {{ $index === 0 ? 'active' : '' }}" 
                      data-main-src="{{ asset('storage/' . $image->path) }}"
                    >
                  @endforeach
                @else
                   <div class="d-flex align-items-center justify-content-center border rounded-3" style="width: 80px; height: 80px; background-color: #eee;">
                    <i class="bi bi-image text-muted fs-3"></i>
                  </div>
                @endif
              </div>
            </div>
            
            <div class="flex-grow-1">
              @if(isset($product->images[0]))
                <img 
                  src="{{ asset('storage/' . $product->images[0]->path) }}" 
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
                    <div 
                        class="variant-button"
                        data-size="{{ $variant->size }}"
                        data-price="{{ number_format($variant->price, 2) }}"
                        data-stock="{{ $variant->stock }}"
                        data-product-name="{{ $product->name }}"
                        {{ $variant->stock <= 0 ? 'disabled' : '' }}
                    >
                        <span class="fw-semibold">{{ $variant->size }}</span>
                        <span class="text-muted small">/ {{ number_format($variant->price, 2) }} $</span>
                        @if ($variant->stock <= 0)
                            <span class="badge bg-danger ms-1">Agotado</span>
                        @endif
                    </div>
                @empty
                    <p class="text-muted">No hay variantes disponibles.</p>
                @endforelse
            </div>

            <div class="mt-4 pt-2 border-top d-flex justify-content-center">
                <button 
                    id="whatsapp-button"
                    class="btn btn-success btn-lg w-60"
                    disabled
                >
                    <i class="bi bi-whatsapp me-2"></i> Comunicarme por WhatsApp por este producto
                </button>
                
                </div>
                      </div>
                    </div>
                  </div>
                </div>
  </section>

  <footer class="py-4 text-center bg-dark text-white">
    <p>© 2025 {{ $tenant->name }} - SHOPIX. Todos los derechos reservados.</p>
  </footer>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    document.addEventListener('DOMContentLoaded', () => {
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
        const whatsappButton = document.getElementById('whatsapp-button');
        let selectedVariant = null;
        
        // ** Inyectar las variables del tenant desde Blade a JavaScript **
        // Usamos JSON.stringify para manejar correctamente los valores, especialmente si son strings.
        const tenantPhoneCode = '{{ $tenant->phone_code }}';
        const tenantPhoneNumber = '{{ $tenant->phone_number }}';

        // --- Lógica de selección de variantes ---
        variantButtons.forEach(button => {
            button.addEventListener('click', () => {
                // 1. Desactivar todos los botones
                variantButtons.forEach(btn => btn.classList.remove('selected'));

                // 2. Activar el botón seleccionado
                button.classList.add('selected');
                
                // 3. Almacenar la variante seleccionada
                selectedVariant = {
                    size: button.dataset.size,
                    price: button.dataset.price,
                    productName: button.dataset.productName
                };
                
                // 4. Habilitar el botón de WhatsApp
                whatsappButton.disabled = false;
            });
        });

        // --- Lógica del botón de WhatsApp ---
        whatsappButton.addEventListener('click', () => {
            if (!selectedVariant) {
                alert('Por favor, selecciona una variante primero.');
                return;
            }

            // 1. Construir el número de teléfono
            // Asegúrate de que el código de país se combine correctamente con el número.
            // Los números de WhatsApp deben ser numéricos, sin '+' inicial ni guiones.
            const fullPhoneNumber = tenantPhoneCode.replace(/\+/g, '') + tenantPhoneNumber;
            
            // 2. Construir el mensaje
            const message = `Hola, estoy interesado en el producto *${selectedVariant.productName}* ` +
                            `en la variante **${selectedVariant.size}** con precio de *${selectedVariant.price} $*. ` +
                            `¿Podrían darme más información?`;

            // 3. Codificar el mensaje para la URL
            const encodedMessage = encodeURIComponent(message);
            
            // 4. Crear el enlace
            const whatsappLink = `https://wa.me/${fullPhoneNumber}?text=${encodedMessage}`;

            // 5. Redireccionar
            window.open(whatsappLink, '_blank');
        });
    });
  </script>
</body>

</html>