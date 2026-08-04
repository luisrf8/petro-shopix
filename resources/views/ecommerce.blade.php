<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Shopix - Gestión de sedes Virtuales</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@500;700;800&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    :root {
      --text-main: #0f172a;
      --text-soft: #4b5563;
      --brand-blue: #1d4ed8;
      --line-soft: #dbe4f0;
      --surface: #ffffff;
      --surface-soft: #f6f8fc;
      --shadow-soft: 0 18px 42px rgba(15, 23, 42, 0.08);
    }

    body {
      font-family: 'Manrope', sans-serif;
      background:
        radial-gradient(60rem 24rem at -10% 0%, rgba(37, 99, 235, 0.1), transparent 60%),
        radial-gradient(52rem 22rem at 110% 10%, rgba(14, 165, 233, 0.12), transparent 58%),
        linear-gradient(180deg, #f8fbff 0%, #f4f7fb 48%, #f8fafd 100%);
      color: var(--text-main);
    }

    .landing-header {
      transition: background 0.25s ease-in-out;
      z-index: 1050;
      background: transparent;
      backdrop-filter: none;
      border-bottom: 0;
      padding: 0.5rem 0;
    }

    .landing-header .navbar-toggler {
      border: 1px solid rgba(0, 0, 0, 0.2);
      background: #fff;
      padding: 0.35rem 0.55rem;
    }

    .landing-header .navbar-collapse.show {
      margin-top: 0.65rem;
      padding: 0.65rem;
      border-radius: 14px;
      border: 1px solid rgba(255, 255, 255, 0.22);
      background: rgba(15, 23, 42, 0.9);
      box-shadow: 0 14px 32px rgba(2, 6, 23, 0.32);
      backdrop-filter: blur(10px);
    }

    .landing-header .navbar-collapse.show .landing-nav-link {
      background: rgba(255, 255, 255, 0.95) !important;
      color: #0f172a !important;
      border-color: rgba(148, 163, 184, 0.45);
    }

    .landing-nav-link {
      font-weight: 600;
      padding: 0.4rem 0.75rem;
      border-radius: 12px;
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
      background: url('../../assets/img/shopixbg.png') no-repeat center center;
      background-size: cover;
      overflow: hidden;
    }

    .hero-overlay {
      position: absolute;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background: linear-gradient(140deg, rgba(2, 6, 23, 0.82), rgba(15, 23, 42, 0.72));
      z-index: 0;
    }
        
    .hero .container {
      z-index: 1;
      padding-top: 5.5rem;
      position: relative;
    }

    .hero .container::before {
      content: '';
      position: absolute;
      inset: auto -12% -28% auto;
      width: 20rem;
      height: 20rem;
      border-radius: 999px;
      background: radial-gradient(circle, rgba(59, 130, 246, 0.34), transparent 68%);
      pointer-events: none;
    }

    .filter-box {
      background: white;
      border-radius: 12px;
      box-shadow: 0 0 30px rgba(0, 0, 0, 0.05);
      padding: 20px;
    }

    .section-title {
      font-size: clamp(1.4rem, 4.5vw, 2rem);
      font-family: 'Plus Jakarta Sans', sans-serif;
      font-weight: 800;
      letter-spacing: -0.01em;
    }

    .card-product {
      border: 1px solid var(--line-soft);
      border-radius: 18px;
      overflow: hidden;
      box-shadow: var(--shadow-soft);
      background: var(--surface);
      transition: transform 0.22s ease, box-shadow 0.22s ease;
    }

    .card-product:hover {
      transform: translateY(-4px);
      box-shadow: 0 22px 48px rgba(15, 23, 42, 0.14);
    }

    .plans-grid {
      display: grid;
      grid-template-columns: repeat(4, minmax(220px, 1fr));
      gap: 1.25rem;
      align-items: stretch;
    }

    .plan-card {
      position: relative;
    }

    .plan-card.is-popular {
      background: linear-gradient(180deg, #062a5c 0%, #07214a 100%);
      border-color: #0a3d83;
      color: #f8fbff;
      box-shadow: 0 18px 44px rgba(7, 33, 74, 0.45);
    }

    .plan-popular-badge {
      position: absolute;
      top: 0.7rem;
      right: 0.7rem;
      background: #b2e549;
      color: #325200;
      border-radius: 999px;
      padding: 0.24rem 0.68rem;
      font-size: 0.7rem;
      font-weight: 800;
      letter-spacing: 0.02em;
      text-transform: uppercase;
    }

    .plan-card.is-popular .plan-title,
    .plan-card.is-popular .plan-label,
    .plan-card.is-popular .plan-price,
    .plan-card.is-popular .plan-feature-item {
      color: #f8fbff;
    }

    .plan-price {
      font-size: clamp(2rem, 2.4vw, 2.4rem);
      font-weight: 800;
      line-height: 1;
      margin-bottom: 1rem;
      color: #0f172a;
    }

    .plan-price small {
      font-size: 0.86rem;
      font-weight: 600;
      color: #5b6576;
    }

    .plan-card.is-popular .plan-price small {
      color: #dbeafe;
    }

    .plan-feature-item {
      font-size: 1.01rem;
      color: #0f172a;
    }

    .plan-feature-item .bi-check2 {
      color: #b2e549 !important;
    }

    .plan-btn-available {
      border-radius: 0.65rem;
      font-weight: 700;
      border-width: 1px;
    }

    .plan-card.is-popular .plan-btn-available {
      background: #b2e549;
      border-color: #b2e549;
      color: #325200;
    }

    .plan-card.is-popular .plan-btn-available:hover {
      background: #a6db39;
      border-color: #a6db39;
      color: #2e4d00;
    }


    .icon-box {
      text-align: center;
    }

    .feature-card,
    .benefit-card {
      background: rgba(255, 255, 255, 0.9);
      border: 1px solid var(--line-soft);
      border-radius: 16px;
      padding: 1.25rem 1rem;
      height: 100%;
      box-shadow: 0 12px 30px rgba(15, 23, 42, 0.06);
    }

    .icon-box i {
      font-size: 2rem;
      margin-bottom: 10px;
      color: var(--brand-blue);
    }

    footer {
      background: #0b172d;
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
      font-size: clamp(3rem, 11vw, 6.3rem);
      font-family: 'Plus Jakarta Sans', sans-serif;
      font-weight: 800;
      line-height: 1.1;
      color: #fff;
      text-shadow: 2px 2px 8px rgba(0, 0, 0, 1);
    }

    .hero-slogan {
      font-size: clamp(1.05rem, 3.8vw, 1.75rem);
      font-weight: 600;
      line-height: 1.25;
      color: #fff;
      text-shadow: 2px 2px 8px rgba(0, 0, 0, 1);
      margin-top: 0.5rem;
      max-width: 900px;
      margin-inline: auto;
    }

    .hero-panel {
      max-width: 620px;
      margin: 1.8rem auto 0;
      border: 1px solid rgba(255, 255, 255, 0.26);
      border-radius: 18px;
      background: rgba(255, 255, 255, 0.1);
      backdrop-filter: blur(10px);
      padding: 1rem;
      box-shadow: 0 12px 30px rgba(2, 6, 23, 0.3);
    }

    .hero-order-form {
      max-width: 520px;
      margin-inline: auto;
      display: grid;
      gap: 0.6rem;
    }

    .hero-order-caption {
      margin: 0 0 0.35rem;
      font-weight: 700;
      color: #ffffff;
      text-align: center;
    }

    .hero-order-row {
      width: 100%;
    }

    .hero-order-input {
      width: 100%;
    }

    .hero-order-btn {
      width: 100%;
      max-width: 220px;
      margin-inline: auto;
    }

    .section-soft {
      background: var(--surface-soft);
    }

    .allies-shell {
      background: #fff;
      border: 1px solid var(--line-soft);
      border-radius: 20px;
      padding: 1.25rem;
      box-shadow: var(--shadow-soft);
    }

    .allies-logo {
      max-height: 82px;
      object-fit: contain;
      filter: saturate(0.95);
      transition: transform 0.2s ease;
    }

    .allies-logo:hover {
      transform: translateY(-2px);
    }

    .contact-card {
      background: rgba(255, 255, 255, 0.9);
      border: 1px solid var(--line-soft);
      border-radius: 20px;
      padding: 1.5rem;
      box-shadow: var(--shadow-soft);
    }

    .contact-actions {
      display: flex;
      flex-wrap: wrap;
      gap: 0.65rem;
    }

    .contact-btn {
      border-radius: 999px;
      padding: 0.62rem 1.1rem;
      font-weight: 700;
      border: 1px solid transparent;
      display: inline-flex;
      align-items: center;
      gap: 0.45rem;
      transition: transform 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
      text-decoration: none;
    }

    .contact-btn:hover {
      transform: translateY(-2px);
      box-shadow: 0 12px 24px rgba(15, 23, 42, 0.14);
    }

    .contact-btn-primary {
      background: linear-gradient(135deg, #1d4ed8, #2563eb);
      color: #ffffff;
      border-color: rgba(37, 99, 235, 0.35);
    }

    .contact-btn-primary:hover {
      color: #ffffff;
      background: linear-gradient(135deg, #1e40af, #1d4ed8);
    }

    .contact-btn-secondary {
      background: #ffffff;
      color: #1f2937;
      border-color: #cbd5e1;
    }

    .contact-btn-secondary:hover {
      color: #111827;
      background: #f8fafc;
    }

    .hero-cta-row {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 0.85rem;
      margin-top: 1.5rem;
    }

    .hero-cta-pill {
      display: inline-flex;
      align-items: center;
      gap: 0.55rem;
      padding: 0.9rem 1.25rem;
      border-radius: 999px;
      font-weight: 800;
      text-decoration: none;
      border: 1px solid rgba(255, 255, 255, 0.24);
      transition: transform 0.2s ease, box-shadow 0.2s ease, background-color 0.2s ease;
    }

    .hero-cta-pill:hover {
      transform: translateY(-2px);
      box-shadow: 0 16px 34px rgba(15, 23, 42, 0.22);
    }

    .hero-cta-primary {
      background: linear-gradient(135deg, #f8fbff, #dbeafe);
      color: #0f172a;
    }

    .hero-cta-secondary {
      background: rgba(255, 255, 255, 0.12);
      color: #ffffff;
      backdrop-filter: blur(12px);
    }

    .allies-link {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      width: 100%;
      min-height: 120px;
      text-decoration: none;
      border-radius: 18px;
      padding: 0.75rem;
      border: 1px solid transparent;
      transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease, background-color 0.2s ease;
    }

    .allies-link:hover {
      transform: translateY(-3px);
      box-shadow: 0 18px 34px rgba(15, 23, 42, 0.12);
      border-color: #cbd5e1;
      background: #f8fbff;
    }

    /* También puedes aplicar un text-shadow más sutil al menú de navegación */
    .nav-link.white-shadow {
        color: white;
        /* Sombra más definida */
        text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.91); 
    }
    /* Agrega esta nueva clase a tu bloque <style> */
    @media (max-width: 991.98px) {
      .plans-grid {
        grid-template-columns: repeat(2, minmax(220px, 1fr));
      }

      .landing-header {
        background: transparent;
      }

      .landing-header .navbar-collapse {
        transition: all 0.2s ease;
      }

      .navbar-nav {
        padding-top: 0.5rem;
      }

      .navbar-nav .nav-item {
        margin-bottom: 0.5rem;
      }

      .hero-title {
        font-size: clamp(2.2rem, 8.6vw, 4.2rem);
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
      .plans-grid {
        grid-template-columns: 1fr;
      }

      .hero .container {
        padding-top: 6rem;
      }

      .hero-title {
        font-size: clamp(2rem, 10vw, 3rem);
      }

      .hero-slogan {
        font-size: clamp(0.9rem, 4.6vw, 1.25rem);
      }

      .hero-panel {
        padding: 0.85rem;
      }

      .contact-btn {
        width: 100%;
        justify-content: center;
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
              <a class="btn btn-light text-dark landing-nav-link" href="/">Por sede / servicio</a>
            </li>
            <li class="nav-item">
              <a class="btn btn-light text-dark landing-nav-link" href="/login">Acceso admin</a>
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
      <h2 class="hero-slogan">GESTIONA TU sede O SERVICIO VIRTUAL FÁCILMENTE</h2>
      <div class="hero-cta-row">
        <a href="/" class="hero-cta-pill hero-cta-primary">
          <i class="bi bi-grid-1x2-fill"></i>
          Explorar sedes / servicios
        </a>
        <a href="#sedes" class="hero-cta-pill hero-cta-secondary">
          <i class="bi bi-stars"></i>
          Ver sedes
        </a>
      </div>

    </div>
  </section>


  <!-- FUNCIONALIDADES -->
  <section id="funcionalidades" class="py-5 section-soft">
    <div class="container">
      <h2 class="section-title mb-5 text-center">Funciones Principales</h2>
      <div class="row text-center g-3">
        <div class="col-12 col-sm-6 col-lg-3 icon-box mb-4 mb-lg-0">
          <div class="feature-card">
            <i class="bi bi-shop"></i>
            <h5>Gestión de sede</h5>
            <p>Organiza todos tus productos y categorías fácilmente.</p>
          </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-3 icon-box mb-4 mb-lg-0">
          <div class="feature-card">
            <i class="bi bi-box-seam"></i>
            <h5>Inventario Inteligente</h5>
            <p>Control automático de existencias y alertas de stock bajo.</p>
          </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-3 icon-box mb-4 mb-lg-0">
          <div class="feature-card">
            <i class="bi bi-cash-stack"></i>
            <h5>Ventas y Compras</h5>
            <p>Registra tus movimientos y genera reportes detallados.</p>
          </div>
        </div>
        <div class="col-12 col-sm-6 col-lg-3 icon-box mb-4 mb-lg-0">
          <div class="feature-card">
            <i class="bi bi-bar-chart"></i>
            <h5>Reportes y Pagos</h5>
            <p>Obtén reportes en tiempo real y administra tus pagos con facilidad.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

<section id="sedes" class="p-5">
    <div class="text-center">
      <h2 class="section-title mb-4">Gestiona Tus Sedes</h2>
      <p class="text-muted mb-4">Shopix ahora trabaja con sedes, sin planes ni pagos de suscripcion por tenant.</p>
      <div class="d-flex justify-content-center">
        <div class="card p-4 card-product plan-card h-100 w-100" style="max-width: 520px;">
          <div class="card-body d-flex flex-column align-items-center text-center">
            <h6 class="text-uppercase fw-semibold mb-3 plan-title">Crea tu sede principal</h6>
            <p class="mb-3">Configura sucursales, usuarios y catalogo desde un flujo unificado.</p>
            <a href="/admin/login" class="btn btn-primary w-75 plan-btn-available">
              Acceso SuperUser
            </a>
          </div>
        </div>
      </div>

    </div>
  </section>


  <!-- BENEFICIOS -->
  <section id="beneficios" class="py-5">
    <div class="container text-center">
      <h2 class="section-title mb-5">Beneficios de Usar Shopix</h2>
      <div class="row justify-content-center">
        <div class="col-12 col-md-4 icon-box mb-4 mb-md-0">
          <div class="benefit-card">
            <i class="bi bi-speedometer"></i>
            <h5>Rápido y Eficiente</h5>
            <p>Todo lo que necesitas para tu sede en un solo panel.</p>
          </div>
        </div>
        <div class="col-12 col-md-4 icon-box mb-4 mb-md-0">
          <div class="benefit-card">
            <i class="bi bi-cloud-check"></i>
            <h5>Accesible desde Cualquier Lugar</h5>
            <p>Gestiona tu negocio desde tu computadora o teléfono.</p>
          </div>
        </div>
        <div class="col-12 col-md-4 icon-box mb-4 mb-md-0">
          <div class="benefit-card">
            <i class="bi bi-people"></i>
            <h5>Multiusuario</h5>
            <p>Agrega empleados, asigna roles y trabaja en equipo.</p>
          </div>
        </div>
      </div>
    </div>
  </section>

<!-- sedes -->
<section id="aliados" class="py-5 section-soft">
  <div class="container text-center">
    <h2 class="section-title mb-5 fw-bold">Aliados Comerciales</h2>

    <div class="allies-shell">
    <div id="carouselsedes" class="carousel slide" data-bs-ride="carousel" data-bs-interval="2000">
      <div class="carousel-inner">

        @foreach($tenants->chunk(4) as $index => $grupo)
        <div class="carousel-item @if($index === 0) active @endif">
          <div class="row justify-content-center">
            @foreach($grupo as $sede)
              <div class="col-6 col-md-3 mb-4 d-flex justify-content-center align-items-center">
                <a href="{{ route('tenant.public', $sede->slug) }}" class="allies-link" aria-label="Explorar {{ $sede->name }}">
                  @if($sede->logo)
                    <img src="{{ \App\Support\ImageStorage::url($sede->logo) ?? asset('assets/img/shopix5.png') }}" 
                         alt="{{ $sede->name }}" 
                         class="img-fluid allies-logo" 
                         style="max-height: 100px;">
                  @else
                    <img src="{{ asset('assets/img/shopix5.png') }}" 
                         alt="{{ $sede->name }}" 
                         class="img-fluid allies-logo" 
                         style="max-height: 100px;">
                  @endif
                </a>
              </div>
            @endforeach
          </div>
        </div>
        @endforeach

      </div>

      <!-- Controles (opcionales) -->
      <button class="carousel-control-prev" type="button" data-bs-target="#carouselsedes" data-bs-slide="prev">
        <span class="carousel-control-prev-icon"></span>
      </button>
      <button class="carousel-control-next" type="button" data-bs-target="#carouselsedes" data-bs-slide="next">
        <span class="carousel-control-next-icon"></span>
      </button>
    </div>
    </div>
  </div>
</section>
      <div class="hero-panel">
        <p class="hero-order-caption text-dark">Consultar estatus de compra</p>
        <div class="hero-order-form">
          <div class="hero-order-row">
            <input type="number" min="1" id="publicOrderIdInput" class="form-control bg-white hero-order-input" placeholder="Ingresa tu número de pedido">
          </div>
          <div class="hero-order-row">
            <button type="button" id="publicOrderCheckBtn" class="btn btn-light text-dark fw-semibold hero-order-btn border">Ver estatus</button>
          </div>
        </div>
      </div>

  <!-- CONTACTO / UBICACIÓN -->
  <section id="contacto" class="py-5 section-soft">
    <div class="container">
      <div class="row align-items-center">
        <div class="col-12 mb-4 mb-md-0">
          <div class="contact-card">
            <h2 class="section-title mb-3">Contáctanos</h2>
            <p class="mb-3">Obtén una demo personalizada o consulta nuestros planes.</p>
            <div class="contact-actions">
              <a href="https://api.whatsapp.com/send?phone=584148859372" target="_blank" class="contact-btn contact-btn-primary">
                <i class="bi bi-whatsapp"></i> Escríbenos por WhatsApp
              </a>
            </div>
            <div class="mt-4 d-flex gap-4">
              <a href="https://www.instagram.com/infinitycenter.ca/" target="_blank" class="text-dark fs-4"><i class="bi bi-instagram"></i></a>
              <a href="https://facebook.com" target="_blank" class="text-dark fs-4"><i class="bi bi-facebook"></i></a>
              <a href="https://t.me" target="_blank" class="text-dark fs-4"><i class="bi bi-telegram"></i></a>
            </div>
          </div>
        </div>
      </div>
    </div>
  </section>

  <!-- FOOTER -->
  <footer>
    <div class="container">
      <p>© 2025 Shopix. Sistema de Gestión de sedes Virtuales.</p>
      <a href="/login" class="btn btn-light text-dark fw-bold px-4 py-2 w-50">Soy Admin</a>
    </div>
  </footer>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
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
@include('partials.module-help-client-tour')
</html>
