<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>{{ $tenant->name }}</title>

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

  <style>
    :root {
      --color-primary: {{ $tenant->color_primary ?? '#0043a8' }};
      --color-secondary: {{ $tenant->color_secondary ?? '#2d88d7' }};
      --color-accent: {{ $tenant->color_accent ?? '#ffffff' }};
    }

    body {
      font-family: 'Poppins', sans-serif;
      color: var(--color-accent);
      background-color: #f8f9fa;
    }

    .hero {
      position: relative;
      height: 90vh;
      overflow: hidden;
    }

    .hero video {
      position: absolute;
      width: 100%;
      height: 100%;
      object-fit: cover;
      top: 0;
      left: 0;
      z-index: -1;
    }

    .hero .container {
      position: relative;
      top: 50%;
      transform: translateY(-50%);
      color: var(--color-accent);
      text-shadow: 0 2px 8px rgba(0,0,0,0.6);
    }

    .btn-custom {
      background-color: var(--color-primary);
      color: var(--color-accent);
      border: none;
      transition: all 0.3s ease;
    }

    .btn-custom:hover {
      background-color: var(--color-secondary);
      color: var(--color-accent);
    }

    .section-title {
      color: var(--color-primary);
      font-weight: bold;
    }

    .card-product {
      border: none;
      transition: transform 0.2s ease, box-shadow 0.2s ease;
      background-color: #fff;
    }

    .card-product:hover {
      transform: translateY(-5px);
      box-shadow: 0 6px 15px rgba(0, 67, 168, 0.2);
    }

    footer {
      background-color: var(--color-primary);
      color: var(--color-accent);
    }

    footer a {
      background-color: var(--color-secondary);
      color: var(--color-accent);
      border: none;
    }

    footer a:hover {
      background-color: var(--color-accent);
      color: var(--color-primary);
    }

    .logo {
      max-width: 150px;
      margin-bottom: 1rem;
      filter: drop-shadow(0 2px 5px rgba(0,0,0,0.3));
    }
  </style>
</head>

<body>

  <!-- HERO -->
  <section class="hero">
    <video autoplay muted loop playsinline>
      <source src="{{ asset('storage/' . ($tenant->video ?? 'tenants/default.mp4')) }}" type="video/mp4" />
    </video>

    <div class="container text-center">
      @if($tenant->logo)
        <img src="{{ asset('storage/' . $tenant->logo) }}" alt="Logo {{ $tenant->name }}" class="logo">
      @endif

      <h1 class="fw-bold">{{ strtoupper($tenant->name) }}</h1>
      <h4>{{ $tenant->slogan ?? 'MODA PARA HOY Y SIEMPRE' }}</h4>
      <p class="lead">{{ $tenant->description ?? 'Chemises, franelas y jeans que definen tu estilo' }}</p>

      <a href="{{ $tenant->store_url ?? '#' }}" class="btn btn-custom fw-bold px-4 py-2 mt-3">Comprar ahora</a>
    </div>
  </section>

  <!-- CATEGORÍAS -->
  <section class="py-5">
    <div class="container">
      <h2 class="section-title mb-4 text-center">Nuestra Colección</h2>
      <div class="row g-4">
        @foreach($categories as $category)
          <div class="col-md-4">
            <div class="card card-product text-center">
              <div class="mt-4">
                <i class="{{ $category->icon }} fs-2" style="color: var(--color-primary);"></i>
              </div>
              <div class="card-body">
                <h6 class="fw-bold">{{ $category->name }}</h6>
                <span class="text-muted">{{ $category->description }}</span>
              </div>
            </div>
          </div>
        @endforeach
      </div>
    </div>
  </section>

  <!-- PRODUCTOS -->
  <section class="py-5 bg-light">
    <div class="container text-center">
      <h2 class="section-title mb-5">{{ isset($category) ? $category->name : 'Productos Destacados' }}</h2>
      <div class="row justify-content-center">
        @foreach($productItems as $product)
          <div class="col-md-4 mb-4">
            <div class="card h-100 shadow-sm border-0">
              @if(isset($product->images[0]))
                <img src="{{ asset('storage/' . $product->images[0]->path) }}" class="card-img-top" style="height: 300px; object-fit: cover;">
              @else
                <div class="d-flex align-items-center justify-content-center" style="height: 300px; background-color: #eee;">
                  <i class="bi bi-image text-muted fs-1"></i>
                </div>
              @endif
              <div class="card-body">
                <h5 class="fw-bold text-dark">{{ $product->name }}</h5>
              </div>
            </div>
          </div>
        @endforeach
      </div>
    </div>
  </section>

  <!-- FOOTER -->
  <footer class="py-4 text-center">
    <p>© 2025 {{ $tenant->name }}. Todos los derechos reservados.</p>
    <a href="{{ url('/login') }}" class="btn fw-bold px-4 py-2">Soy Admin</a>
  </footer>

</body>
</html>
