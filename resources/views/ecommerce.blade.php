<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Shopix - Gestión de Tiendas Virtuales</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
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

    .shadow-soft {
      filter: drop-shadow(0 4px 10px rgba(0, 0, 0, 0.25));
    }

        .hero {
            position: relative;
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            text-align: center;
            color: white;
            /* La imagen de fondo debe ser llamativa y de alta resolución */
            background: url('../../assets/img/shopixbg.png') no-repeat center center;
            background-size: cover;
            overflow: hidden; /* Asegura que la superposición no se salga */
        }

        /* Nueva capa oscura para contraste */
        .hero-overlay {
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background-color: rgba(0, 0, 0, 0.62); /* Negro semi-transparente */
            z-index: 0;
        }
        
        /* Contenido del Hero sobre la superposición */
        .hero .container {
          z-index: 1; /* Coloca el contenido por encima del overlay */
          padding-top: 5.5rem;
        }

    .filter-box {
      background: white;
      border-radius: 12px;
      box-shadow: 0 0 30px rgba(0, 0, 0, 0.05);
      padding: 20px;
    }

    .section-title {
      font-size: clamp(1.4rem, 4.5vw, 2rem);
      font-weight: 700;
    }

    .card-product {
      border: none;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 0 15px rgba(0, 0, 0, 0.05);
    }

    .card-product:hover {
      transform: scale(1.01);
    }

    .icon-box {
      text-align: center;
    }

    .icon-box i {
      font-size: 2rem;
      margin-bottom: 10px;
    }

    footer {
      background: #001a49ff;
      color: #fff;
      padding: 40px 0;
      text-align: center;
    }

    .glass-header {
      backdrop-filter: blur(16px) saturate(180%);
      -webkit-backdrop-filter: blur(16px) saturate(180%);
      background-color: rgba(255, 255, 255, 0.1);
      border-radius: 16px;
      border: 1px solid rgba(255, 255, 255, 0.25);
      color: white;
      width: 100%;
    }

    .nav-link.white-shadow {
      color: white;
      text-shadow: 1px 1px 3px rgba(1, 0, 0, 2);
    }
    .hero-title {
      font-size: 5rem;
        font-weight: 700;
      line-height: 1.1;
        color: #fff; 
        text-shadow: 2px 2px 8px rgba(0, 0, 0, 1); 
    }

    .hero-slogan {
      font-size: 5rem;
      font-weight: 600;
      line-height: 1.25;
      color: #fff;
      text-shadow: 2px 2px 8px rgba(0, 0, 0, 1);
      margin-top: 0.5rem;
    }

    /* También puedes aplicar un text-shadow más sutil al menú de navegación */
    .nav-link.white-shadow {
        color: white;
        /* Sombra más definida */
        text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.91); 
    }
    /* Agrega esta nueva clase a tu bloque <style> */
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
    }
  </style>
</head>

<body>

  <!-- HEADER -->
  <header class="landing-header position-fixed top-0 start-0 w-100">
    <div class="container">
      <nav class="navbar navbar-expand-lg navbar-light p-0">
        <a class="navbar-brand d-flex align-items-center" href="#top">
          <span class="btn btn-light p-1 px-3 m-0">
            <img src="../../assets/img/shopix5.png" alt="Logo Shopix" class="img-fluid" style="width: 100px; object-fit: contain;">
          </span>
        </a>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#landingNavbar" aria-controls="landingNavbar" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="landingNavbar">
          <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2">
            <li class="nav-item">
              <a class="btn btn-light text-dark landing-nav-link" href="#funcionalidades">Funciones</a>
            </li>
            <li class="nav-item">
              <a class="btn btn-light text-dark landing-nav-link" href="#beneficios">Beneficios</a>
            </li>
            <li class="nav-item">
              <a class="btn btn-light text-dark landing-nav-link" href="#contacto">Contacto</a>
            </li>
            <li class="nav-item">
              <a class="btn btn-light text-dark landing-nav-link" href="/landings">Por tienda / servicio</a>
            </li>
            <li class="nav-item">
              <a class="btn btn-light text-dark landing-nav-link" href="/login">Iniciar sesión</a>
            </li>
          </ul>
        </div>
      </nav>
    </div>
  </header>

  <!-- HERO -->
  <section id="top" class="hero">
    <!-- <video autoplay muted loop playsinline>
      <source src="../../assets/img/Street.mp4" type="video/mp4" />
    </video> -->
    <div class="hero-overlay"></div> 
    <div class="container text-center">
      <h1 class="hero-title">SHOPIX</h1>
      <h2 class="hero-slogan">GESTIONA TU TIENDA O SERVICIO VIRTUAL FÁCILMENTE</h2>
      <div class="mt-4 mx-auto" style="max-width: 560px;">
        <label for="publicOrderIdInput" class="form-label text-white fw-semibold">Consultar estatus de compra</label>
        <div class="d-flex gap-2 flex-wrap justify-content-center">
          <input type="number" min="1" id="publicOrderIdInput" class="form-control bg-white" placeholder="Ingresa tu número de pedido">
          <button type="button" id="publicOrderCheckBtn" class="btn btn-light text-dark fw-semibold">Ver estatus</button>
        </div>
      </div>
    </div>
  </section>


  <!-- FUNCIONALIDADES -->
  <section id="funcionalidades" class="py-5 bg-light">
    <div class="container">
      <h2 class="section-title mb-5 text-center">Funciones Principales</h2>
      <div class="row text-center">
        <div class="col-12 col-sm-6 col-lg-3 icon-box mb-4 mb-lg-0">
          <i class="bi bi-shop"></i>
          <h5>Gestión de Tienda</h5>
          <p>Organiza todos tus productos y categorías fácilmente.</p>
        </div>
        <div class="col-12 col-sm-6 col-lg-3 icon-box mb-4 mb-lg-0">
          <i class="bi bi-box-seam"></i>
          <h5>Inventario Inteligente</h5>
          <p>Control automático de existencias y alertas de stock bajo.</p>
        </div>
        <div class="col-12 col-sm-6 col-lg-3 icon-box mb-4 mb-lg-0">
          <i class="bi bi-cash-stack"></i>
          <h5>Ventas y Compras</h5>
          <p>Registra tus movimientos y genera reportes detallados.</p>
        </div>
        <div class="col-12 col-sm-6 col-lg-3 icon-box mb-4 mb-lg-0">
          <i class="bi bi-bar-chart"></i>
          <h5>Reportes y Pagos</h5>
          <p>Obtén reportes en tiempo real y administra tus pagos con facilidad.</p>
        </div>
      </div>
    </div>
  </section>

<section class="p-5">
    <div class="text-center">
      <h2 class="section-title mb-5">Planes Disponibles</h2>
      <div class="d-flex flex-wrap justify-content-center gap-4">
        @foreach($plans as $plan)
        <div class="mb-4 d-flex justify-content-center">
          <div class="card p-4 card-product h-100 d-flex flex-column justify-content-between" style="width: 25rem;">
            <div class="card-body d-flex flex-column align-items-center">
              <h6 class="text-uppercase fw-semibold mb-3">{{ $plan->name }}</h6>
                <img src="{{ $plan->image }}" 
                    alt="{{ $plan->name }}" 
                    class="img-fluid mb-3 rounded shadow-soft" 
                    style="max-width: 220px;">

              
              <p class="fw-semibold mb-1">Monto a pagar</p>
              <h4 class="fw-bold mb-4">${{ number_format($plan->price, 2) }} / Mes</h4>

              <h6 class="fw-bold mb-2">Beneficios</h6>
              <ul class="list-unstyled text-start w-100">
                @foreach($plan->features as $feature)
                <li class="mb-1 d-flex align-items-center gap-2">
                  <i class="bi bi-check2 me-1" style="color: #0d6efd; font-size: 1.5rem; font-weight: 800"></i>{{ $feature }}
                </li>
                @endforeach
              </ul>
            </div>
            
            <div class="card-footer bg-transparent border-0 text-center">
              @if ($plan->status == 1)
                <a href="/create-tenant-user"
                  target="_blank"
                  class="btn btn-primary w-75 fw-semibold rounded-pill">
                  Seleccionar Plan
                </a>
              @else
                <button
                  class="btn btn-secondary w-75 fw-semibold rounded-pill"
                  disabled
                  style="cursor: default;"
                >
                  Próximamente...
                </button>
              @endif
            </div>
            </div>
        </div>
        @endforeach
      </div>
    </div>
  </section>


  <!-- BENEFICIOS -->
  <section id="beneficios" class="py-5">
    <div class="container text-center">
      <h2 class="section-title mb-5">Beneficios de Usar Shopix</h2>
      <div class="row justify-content-center">
        <div class="col-12 col-md-4 icon-box mb-4 mb-md-0">
          <i class="bi bi-speedometer"></i>
          <h5>Rápido y Eficiente</h5>
          <p>Todo lo que necesitas para tu tienda en un solo panel.</p>
        </div>
        <div class="col-12 col-md-4 icon-box mb-4 mb-md-0">
          <i class="bi bi-cloud-check"></i>
          <h5>Accesible desde Cualquier Lugar</h5>
          <p>Gestiona tu negocio desde tu computadora o teléfono.</p>
        </div>
        <div class="col-12 col-md-4 icon-box mb-4 mb-md-0">
          <i class="bi bi-people"></i>
          <h5>Multiusuario</h5>
          <p>Agrega empleados, asigna roles y trabaja en equipo.</p>
        </div>
      </div>
    </div>
  </section>

<!-- Tiendas -->
<section id="beneficios" class="py-5 bg-light">
  <div class="container text-center">
    <h2 class="section-title mb-5 fw-bold">Aliados Comerciales</h2>

    <div id="carouselTiendas" class="carousel slide" data-bs-ride="carousel" data-bs-interval="2000">
      <div class="carousel-inner">

        @foreach($tenants->chunk(4) as $index => $grupo)
        <div class="carousel-item @if($index === 0) active @endif">
          <div class="row justify-content-center">
            @foreach($grupo as $tienda)
              <div class="col-6 col-md-3 mb-4 d-flex justify-content-center align-items-center">
                @if($tienda->logo)
                  <img src="{{ asset('storage/' . $tienda->logo) }}" 
                       alt="{{ $tienda->name }}" 
                       class="img-fluid" 
                       style="max-height: 100px;">
                @else
                  <img src="{{ asset('assets/img/shopix5.png') }}" 
                       alt="{{ $tienda->name }}" 
                       class="img-fluid" 
                       style="max-height: 100px;">
                @endif
              </div>
            @endforeach
          </div>
        </div>
        @endforeach

      </div>

      <!-- Controles (opcionales) -->
      <button class="carousel-control-prev" type="button" data-bs-target="#carouselTiendas" data-bs-slide="prev">
        <span class="carousel-control-prev-icon"></span>
      </button>
      <button class="carousel-control-next" type="button" data-bs-target="#carouselTiendas" data-bs-slide="next">
        <span class="carousel-control-next-icon"></span>
      </button>
    </div>
  </div>
</section>


  <!-- CONTACTO / UBICACIÓN -->
  <section id="contacto" class="py-5 bg-light">
    <div class="container">
      <div class="row align-items-center">
        <div class="col-12 col-md-6 mb-4 mb-md-0">
          <h2 class="section-title mb-3">Contáctanos</h2>
          <p class="mb-3">Obtén una demo personalizada o consulta nuestros planes.</p>
          <div class="d-flex flex-wrap gap-2">
            <a href="https://api.whatsapp.com/send?phone=584148859372" target="_blank" class="btn btn-primary px-4 py-2">
              Escríbenos por WhatsApp
            </a>
            <a href="https://www.google.com/maps?q=9.7527562,-63.1679763" target="_blank" rel="noopener noreferrer" class="btn btn-outline-dark px-4 py-2">
              Ver ubicación en Google Maps
            </a>
          </div>
          <div class="mt-4 d-flex gap-4">
            <a href="https://www.instagram.com/infinitycenter.ca/" target="_blank" class="text-dark fs-4"><i class="bi bi-instagram"></i></a>
            <a href="https://facebook.com" target="_blank" class="text-dark fs-4"><i class="bi bi-facebook"></i></a>
            <a href="https://t.me" target="_blank" class="text-dark fs-4"><i class="bi bi-telegram"></i></a>
          </div>
        </div>
        <div class="col-12 col-md-6">
          <div class="rounded-4 overflow-hidden shadow" style="height: 300px;">
            <iframe
              src="https://www.google.com/maps/embed?pb=!1m14!1m12!1m3!1d62734.306809791316!2d-63.1679763!3d9.7527562!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!5e0!3m2!1ses!2sve!4v1700000000000"
              width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy"
              referrerpolicy="no-referrer-when-downgrade"></iframe>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- FOOTER -->
  <footer>
    <div class="container">
      <p>© 2025 Shopix. Sistema de Gestión de Tiendas Virtuales.</p>
      <a href="/login" class="btn btn-light text-dark fw-bold px-4 py-2 w-50">Soy Admin</a>
    </div>
  </footer>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
</body>
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

    document.getElementById('publicOrderCheckBtn')?.addEventListener('click', function () {
      const input = document.getElementById('publicOrderIdInput');
      const orderId = String(input?.value || '').trim();

      if (!orderId || Number(orderId) <= 0) {
        alert('Debes ingresar un número de pedido válido.');
        return;
      }

      window.location.href = `/publicOrder/${orderId}`;
    });

    document.getElementById('publicOrderIdInput')?.addEventListener('keydown', function (event) {
      if (event.key === 'Enter') {
        event.preventDefault();
        document.getElementById('publicOrderCheckBtn')?.click();
      }
    });

</script>
</html>
