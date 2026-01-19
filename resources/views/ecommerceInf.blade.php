<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>{{ $tenant->name }} - Tienda Virtual</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    body {
      font-family: 'Inter', sans-serif;
      background-color: #fff;
      color: #000;
    }

    .w-100.position-fixed.top-0.px-4 {
      transition: box-shadow 0.3s ease-in-out, background 0.3s ease-in-out;
      z-index: 1050;
    }
    .hero {
      position: relative;
      height: 100vh;
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
    }

    .glass-text {
      font-size: 4rem;
      font-weight: 700;
      color: #fff;
      text-shadow: 2px 2px 8px rgba(0, 0, 0, 1);
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
.header-button {
  
}
  </style>
</head>

<body>

  <!-- HEADER -->
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
              <a class="btn btn-light text-dark fw-bold p-1 px-3 m-0" href="#funcionalidades">Funciones</a>
              <a class="btn btn-light text-dark fw-bold p-1 px-3 m-0" href="#beneficios">Beneficios</a>
              <a class="btn btn-light text-dark fw-bold p-1 px-3 m-0" href="#contacto">Contacto</a>
            </ul>
      </div>
    </div>
  </div>

  <!-- HERO -->
  <section class="hero" style="
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
      <h1 class="glass-text">{{ strtoupper($tenant->name) }}</h1>
      <h2 class="glass-text">{{ $tenant->slogan ?? '' }}</h2>
      <label class="">{{ $tenant->description ?? '' }}</label>
    </div>
  </section>

  <!-- CATEGORIAS -->
<section id="categorias" class="py-5 bg-white">
    <div class="container">
        <h2 class="section-title mb-5 text-center">Categorías Principales</h2>
        <div class="row g-4 justify-content-center"> 
            @foreach($categories as $category)
                <div class="col-md-3">
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
        </div>
    </div>
</section>
  <!-- PRODUCTOS -->
  <section class="py-5 bg-light">
    <div class="container">
      <h2 class="section-title">Productos Destacados</h2>
      <div class="row" id="products-container">
        @foreach($productItems as $product)
          <div class="col-md-4 mb-4 product-item" data-category="{{ $product->category_id }}">
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
          </div>
        @endforeach
      </div>
      <div class="text-center">
        <a href="{{ route('tenant.public.categories', ['tenant' => $tenant->slug]) }}" class="btn btn-outline-primary">Ver todos los productos</a>
      </div>
    </div>
  </section>
  <!-- CONTACTO / UBICACIÓN -->
  <section id="contacto" class="py-5 bg-white">
    <div class="container">
      <div class="row align-items-center">
        <div class="col-md-6 mb-4 mb-md-0">
          <h2 class="section-title text-start mb-3">Contáctanos</h2>
          <p class="mb-3">{{ $tenant->name ?? '' }} - {{ $tenant->description ?? '' }}.</p>
          <p class="">Somos una empresa de {{ $tenant->country ?? '' }} - {{ $tenant->state ?? '' }} - {{ $tenant->city ?? '' }}.</p>
          <p class="">Ubicada en {{ $tenant->address ?? '' }}.</p>
          @php
              $whatsapp = preg_replace('/\D/', '', $tenant->phone_code . $tenant->phone_number);
          @endphp
          <a href="https://api.whatsapp.com/send?phone={{ $whatsapp }}" target="_blank" class="btn btn-primary px-4 py-2">
            Escríbenos por WhatsApp
          </a>
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
        <div class="col-md-6">
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

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    // Shadow effect on scroll
    const header = document.querySelector('.w-100.position-fixed.top-0.px-4');
    window.addEventListener('scroll', () => {
      if(window.scrollY > 0){
        header.classList.add('scrolled-shadow');
      } else {
        header.classList.remove('scrolled-shadow');
      }
    });

    // Filtrado de productos por categoría
    const categoryLinks = document.querySelectorAll('.category-link');
    const products = document.querySelectorAll('.product-item');

    categoryLinks.forEach(link => {
      link.addEventListener('click', e => {
        e.preventDefault();

        categoryLinks.forEach(l => l.classList.remove('active'));
        link.classList.add('active');

        const categoryId = link.dataset.id;
        products.forEach(product => {
          if(categoryId === 'all' || product.dataset.category === categoryId){
            product.style.display = 'block';
          } else {
            product.style.display = 'none';
          }
        });
      });
    });
  </script>
</body>
</html>
