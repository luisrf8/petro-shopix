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

  </style>
</head>

<body  style="min-height: 100vh;">

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
              @foreach($categories as $category)
                <a class="btn btn-light text-dark fw-bold p-1 px-3 m-0 category-link" href="#"
                  data-id="{{ $category->id }}">{{ $category->name }}</a>
              @endforeach
              <a class="btn btn-light text-dark fw-bold p-1 px-3 m-0 category-link"
                href="#"
                data-id="">
                Todos
                </a>
                <a class="btn btn-light text-dark fw-bold p-1 px-3 m-0"
                href="{{ route('tenant.public', ['tenant' => $tenant->slug]) }}"
                data-id="">
                Volver
                </a>
            </ul>
      </div>
    </div>
  </div>

  <!-- PRODUCTOS -->
  <section class="py-5 bg-light h-100">
    <div class="mt-5 px-4">
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
            <div class="col-md-3 mb-4 product-item" data-category="{{ $product->category_id }}" data-name="{{ strtolower($product->name) }}">
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
