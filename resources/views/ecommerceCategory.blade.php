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
            <li class="nav-item">
              <a class="btn btn-light text-dark landing-nav-link category-link" href="#" data-id="">Todos</a>
            </li>
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
                                    <div class="small btn btn-outline-secondary p-2 rounded-3">
                                        <span class="fw-semibold">{{ $variant->size }}</span>
                                        / 
                                        <span class="fw-semibold">{{ number_format($variant->price, 2) }} $</span>
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

  <footer class="py-4 text-center bg-dark text-white">
    <p>© 2025 {{ $tenant->name }} - SHOPIX. Todos los derechos reservados.</p>
  </footer>

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
  const searchInput = document.getElementById('product-search');

  let activeCategory = null;

  function filterProducts() {
    const searchText = searchInput.value.toLowerCase();

    products.forEach(product => {
      const matchesCategory =
        !activeCategory || product.dataset.category === activeCategory;

      const matchesSearch =
        product.dataset.name.includes(searchText);

      product.style.display =
        matchesCategory && matchesSearch ? 'block' : 'none';
    });
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
</script>
</body>

</html>
