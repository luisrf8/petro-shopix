<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Shopix - Por tienda / servicio</title>
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Manrope:wght@400;500;600;700;800&family=Plus+Jakarta+Sans:wght@500;600;700&display=swap" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    :root {
      --bg-page: #f3f5fb;
      --bg-soft: #eef2ff;
      --text-primary: #0f172a;
      --text-secondary: #475569;
      --line-soft: #dbe4f0;
      --card-shadow: 0 20px 46px rgba(15, 23, 42, 0.08);
    const landingFilterElements = Array.from(document.querySelectorAll('[data-landing-filter]'));
    const landingFilterGroups = landingFilterElements.reduce((groups, element) => {
      const key = element.dataset.landingFilter;
      groups[key] = groups[key] || [];
      groups[key].push(element);
      return groups;
    }, {});

    const landingClearFiltersButtons = document.querySelectorAll('[data-landing-clear]');
    const landingResultsCounters = document.querySelectorAll('[data-landing-results-count]');
      --brand-accent: #2563eb;
    }

    body {
      font-family: 'Manrope', sans-serif;
      background:
        radial-gradient(58rem 24rem at -8% 0%, rgba(37, 99, 235, 0.14), transparent 60%),
        radial-gradient(44rem 20rem at 110% 12%, rgba(14, 165, 233, 0.16), transparent 55%),
        linear-gradient(180deg, #fbfdff 0%, var(--bg-page) 54%, #f7f9fd 100%);
      color: var(--text-primary);
      min-height: 100vh;
    }

    .landing-header {
      background: rgba(255, 255, 255, 0.86);
      backdrop-filter: blur(14px);
      border-bottom: 1px solid rgba(148, 163, 184, 0.2);
      z-index: 1050;
    }

    .landing-nav-link {
      font-weight: 600;
      border-radius: 12px;
      padding: 0.42rem 0.9rem;
    }
      const selectedName = normalizeText(getLandingFilterValue('name'));
      const selectedType = normalizeText(getLandingFilterValue('type'));
      const selectedActivity = normalizeText(getLandingFilterValue('activity'));
      const selectedRegion = normalizeText(getLandingFilterValue('region'));
      const selectedState = normalizeText(getLandingFilterValue('state'));
      const selectedCity = normalizeText(getLandingFilterValue('city'));

    .hero::before {
      content: '';
      position: absolute;
      inset: 0;
      background:
        linear-gradient(145deg, rgba(255, 255, 255, 0.95), rgba(239, 246, 255, 0.86)),
        radial-gradient(26rem 14rem at 80% 18%, rgba(37, 99, 235, 0.14), transparent 65%);
      z-index: 0;
    }

    .hero .container {
      position: relative;
      z-index: 1;
    }

    .hero-eyebrow {
      display: inline-flex;
      align-items: center;
      gap: 0.4rem;
      padding: 0.4rem 0.8rem;
      font-size: 0.82rem;
      font-weight: 700;
      color: #1d4ed8;
      letter-spacing: 0.02em;
      border-radius: 999px;
      background: rgba(37, 99, 235, 0.1);
      border: 1px solid rgba(37, 99, 235, 0.18);
    }

      landingResultsCounters.forEach(counter => {
        counter.textContent = `${visibleCount} de ${landingTotalItems} resultados`;
      font-size: clamp(1.9rem, 4.2vw, 3.1rem);
      font-weight: 800;
      letter-spacing: -0.02em;
    Object.entries(landingFilterGroups).forEach(([key, elements]) => {
      const eventName = key === 'name' ? 'input' : 'change';
      elements.forEach(element => {
        element.addEventListener(eventName, () => {
          syncLandingFilterValue(key, element.value, element);
          applyLandingDirectoryFilters();
        });
      });
      font-size: clamp(0.98rem, 2.2vw, 1.13rem);
      margin-bottom: 0;
    landingClearFiltersButtons.forEach(button => {
      button.addEventListener('click', () => {
        Object.values(landingFilterGroups).forEach(elements => {
          elements.forEach(element => {
            element.value = '';
          });
        });

        applyLandingDirectoryFilters();
      });
    });

    const navbarCollapse = document.getElementById('landingNavbar');
    const navLinks = document.querySelectorAll('#landingNavbar .nav-link, #landingNavbar .btn');
    const bsCollapse = navbarCollapse ? new bootstrap.Collapse(navbarCollapse, { toggle: false }) : null;

    navLinks.forEach(link => {
      link.addEventListener('click', () => {
        if (window.innerWidth < 992 && navbarCollapse?.classList.contains('show') && bsCollapse) {
          bsCollapse.hide();
      background: rgba(255, 255, 255, 0.9);
      backdrop-filter: blur(10px);
      box-shadow: var(--card-shadow);
      position: relative;
      overflow: hidden;
    }

    .mobile-directory-filter-shell {
      display: none;
    }

    .mobile-directory-filter-shell .directory-filter-card {
      margin-top: 0.4rem;
      box-shadow: none;
      border-color: rgba(148, 163, 184, 0.24);
      background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.98));
    }

    .directory-grid {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 1rem;
    }

    .directory-filter-card::after {
      content: '';
      position: absolute;
      width: 14rem;
      height: 14rem;
      border-radius: 999px;
      top: -5.6rem;
      right: -4.6rem;
      background: radial-gradient(circle, rgba(37, 99, 235, 0.14), transparent 68%);
      pointer-events: none;
    }

    .directory-filter-group {
      border: 1px solid var(--line-soft);
      border-radius: 16px;
      padding: 1rem;
      background: linear-gradient(180deg, rgba(248, 250, 252, 0.84), rgba(255, 255, 255, 0.96));
    }

    .directory-filter-group-title {
      font-size: 0.87rem;
      text-transform: uppercase;
      letter-spacing: 0.07em;
      font-weight: 700;
      color: #334155;
      margin-bottom: 0.75rem;
    }

    .directory-filter-card .form-label {
      color: #1e293b;
      font-size: 0.86rem;
      font-weight: 700;
      margin-bottom: 0.44rem;
    }

    .directory-filter-card .form-select,
    .directory-filter-card .form-control {
      border-radius: 12px;
      border-color: #d8e0ed;
      padding-top: 0.62rem;
      padding-bottom: 0.62rem;
      font-size: 0.95rem;
    }

    .directory-filter-card .form-select:focus,
    .directory-filter-card .form-control:focus {
      border-color: rgba(37, 99, 235, 0.4);
      box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.14);
    }

    .directory-results-meta {
      color: #334155;
      font-size: 0.93rem;
      font-weight: 600;
    }

    .directory-clear-btn {
      border-radius: 12px;
      font-weight: 700;
      padding-inline: 1rem;
    }

    .directory-tenant-card {
      border: 1px solid rgba(148, 163, 184, 0.24);
      border-radius: 18px;
      background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.96));
      box-shadow: 0 14px 34px rgba(15, 23, 42, 0.08);
      transition: transform 0.24s ease, box-shadow 0.24s ease, border-color 0.24s ease;
      height: 100%;
    }

    .directory-tenant-card:hover {
      transform: translateY(-4px);
      box-shadow: var(--card-shadow-hover);
      border-color: rgba(37, 99, 235, 0.24);
    }

    .directory-tenant-media {
      height: 180px;
      border-bottom: 1px solid rgba(148, 163, 184, 0.2);
      background: linear-gradient(120deg, #dbeafe, #eff6ff);
      position: relative;
      overflow: hidden;
      padding: 0.55rem;
    }

    .directory-tenant-media img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
      border-radius: 14px;
    }

    .directory-tenant-media::after {
      content: '';
      position: absolute;
      inset: 0;
      background: linear-gradient(180deg, transparent 46%, rgba(15, 23, 42, 0.3) 100%);
    }

    .directory-card-badge {
      position: absolute;
      left: 0.9rem;
      bottom: 0.8rem;
      z-index: 2;
      background: rgba(255, 255, 255, 0.9);
      border: 1px solid rgba(191, 219, 254, 0.9);
      color: #1e40af;
      border-radius: 999px;
      font-size: 0.72rem;
      font-weight: 800;
      padding: 0.24rem 0.64rem;
      letter-spacing: 0.03em;
      text-transform: uppercase;
    }

    .directory-card-logo {
      width: 56px;
      height: 56px;
      border-radius: 16px;
      object-fit: cover;
      border: 1px solid #d6dce8;
      background: #fff;
      flex-shrink: 0;
    }

    .directory-tenant-logo {
      width: 62px;
      height: 62px;
      border-radius: 999px;
      object-fit: cover;
      border: 1px solid #e9ecef;
      background: #fff;
    }

    .directory-chip {
      display: inline-flex;
      align-items: center;
      gap: 0.34rem;
      background: #eff6ff;
      color: #1e40af;
      border: 1px solid #bfdbfe;
      border-radius: 999px;
      font-size: 0.74rem;
      font-weight: 700;
      padding: 0.23rem 0.62rem;
    }

    .directory-location {
      color: #52637b;
    }

    .directory-slogan {
      font-size: 0.9rem;
      font-weight: 700;
      color: #1e293b;
      margin-bottom: 0.3rem;
      line-height: 1.35;
      display: -webkit-box;
      -webkit-line-clamp: 2;
      -webkit-box-orient: vertical;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .directory-description {
      font-size: 0.86rem;
      color: #5b6b83;
      line-height: 1.45;
      margin-bottom: 0.75rem;
      display: -webkit-box;
      -webkit-line-clamp: 3;
      -webkit-box-orient: vertical;
      overflow: hidden;
      text-overflow: ellipsis;
      min-height: 3.7em;
    }

    @media (max-width: 767.98px) {
      .hero {
        padding-top: 6.4rem;
      }

      #landingNavbar.show {
        margin-top: 0.6rem;
        padding: 0.75rem;
        max-height: calc(100vh - 110px);
        overflow-y: auto;
        overscroll-behavior: contain;
        border: 1px solid #dbe4f0;
        border-radius: 14px;
        background: rgba(255, 255, 255, 0.97);
        box-shadow: 0 12px 26px rgba(15, 23, 42, 0.1);
      }

      .mobile-directory-filter-shell {
        display: block;
      }

      .directory-filter-card.is-desktop {
        display: none;
      }

      .directory-filter-group {
        padding: 0.85rem;
      }

      .directory-grid {
        grid-template-columns: 1fr;
      }
    }

    @media (min-width: 768px) and (max-width: 1199.98px) {
      .directory-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
      }
    }

    @media (min-width: 1400px) {
      .directory-tenant-media {
        height: 200px;
      }
    }
  </style>
</head>

<body>
  <header class="landing-header position-fixed top-0 start-0 w-100">
    <div class="container py-2">
      <nav class="navbar navbar-expand-lg p-0">
        <a class="navbar-brand" href="/">
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
              <a class="btn btn-light text-dark landing-nav-link" href="/">Inicio</a>
            </li>
            <li class="nav-item">
              <a class="btn btn-light text-dark landing-nav-link" href="/landings">Por tienda / servicio</a>
            </li>
            <li class="nav-item">
              <a class="btn btn-light text-dark landing-nav-link" href="/login">Acceso admin</a>
            </li>
          </ul>

          <div class="mobile-directory-filter-shell d-md-none">
            <div class="directory-filter-card p-3 mt-2">
              <div class="row g-3 align-items-end">
                <div class="col-12">
                  <label class="form-label" for="landingNameFilterMobile">Buscar por nombre de tienda o servicio</label>
                  <input id="landingNameFilterMobile" data-landing-filter="name" type="search" class="form-control" placeholder="Ej: panaderia artesanal, taller automotriz, farmacia central">
                </div>
                <div class="col-12 d-flex flex-wrap gap-2">
                  <button type="button" data-landing-clear class="btn btn-outline-secondary directory-clear-btn w-100">
                    <i class="bi bi-arrow-counterclockwise me-1"></i> Limpiar filtros
                  </button>
                  <span class="directory-results-meta" data-landing-results-count>0 resultados</span>
                </div>
              </div>

              <div class="row g-3 mt-1">
                <div class="col-12">
                  <div class="directory-filter-group h-100">
                    <div class="directory-filter-group-title"><i class="bi bi-geo-alt-fill me-1"></i> Localidad</div>
                    <div class="row g-2">
                      <div class="col-12">
                        <label class="form-label" for="landingRegionFilterMobile">Region</label>
                        <select id="landingRegionFilterMobile" data-landing-filter="region" class="form-select">
                          <option value="">Todas</option>
                          @foreach(($tenantFilters['regions'] ?? collect()) as $region)
                            <option value="{{ $region }}">{{ $region }}</option>
                          @endforeach
                        </select>
                      </div>
                      <div class="col-12">
                        <label class="form-label" for="landingStateFilterMobile">Estado</label>
                        <select id="landingStateFilterMobile" data-landing-filter="state" class="form-select">
                          <option value="">Todos</option>
                          @foreach(($tenantFilters['states'] ?? collect()) as $state)
                            <option value="{{ $state }}">{{ $state }}</option>
                          @endforeach
                        </select>
                      </div>
                      <div class="col-12">
                        <label class="form-label" for="landingCityFilterMobile">Ciudad</label>
                        <select id="landingCityFilterMobile" data-landing-filter="city" class="form-select">
                          <option value="">Todas</option>
                          @foreach(($tenantFilters['cities'] ?? collect()) as $city)
                            <option value="{{ $city }}">{{ $city }}</option>
                          @endforeach
                        </select>
                      </div>
                    </div>
                  </div>
                </div>
                <div class="col-12">
                  <div class="directory-filter-group h-100">
                    <div class="directory-filter-group-title"><i class="bi bi-sliders2-vertical me-1"></i> Especificaciones</div>
                    <div class="row g-2">
                      <div class="col-12">
                        <label class="form-label" for="landingTypeFilterMobile">Tipo de tienda / servicio</label>
                        <select id="landingTypeFilterMobile" data-landing-filter="type" class="form-select">
                          <option value="">Todos</option>
                          @foreach(($tenantFilters['types'] ?? collect()) as $type)
                            <option value="{{ $type }}">{{ $type }}</option>
                          @endforeach
                        </select>
                      </div>
                      <div class="col-12">
                        <label class="form-label" for="landingActivityFilterMobile">Actividad economica</label>
                        <select id="landingActivityFilterMobile" data-landing-filter="activity" class="form-select">
                          <option value="">Todas</option>
                          @foreach(($tenantFilters['activities'] ?? collect()) as $activity)
                            <option value="{{ $activity }}">{{ $activity }}</option>
                          @endforeach
                        </select>
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </nav>
    </div>
  </header>

  <section class="hero">
    <div class="container">
      <span class="hero-eyebrow"><i class="bi bi-geo-alt"></i> Explorador inteligente</span>
      <h1>Por tienda / servicio y localidad</h1>
      <p class="hero-subtitle">Encuentra comercios y servicios con filtros guiados por localidad y especificaciones del negocio, en una experiencia mas clara y organizada.</p>
    </div>
  </section>

  <section class="py-4">
    <div class="container">
      <div class="directory-filter-card is-desktop p-3 p-md-4 mb-4">
        <div class="row g-3 align-items-end">
          <div class="col-12 col-lg-7">
            <label class="form-label" for="landingNameFilterDesktop">Buscar por nombre de tienda o servicio</label>
            <input id="landingNameFilterDesktop" data-landing-filter="name" type="search" class="form-control" placeholder="Ej: panaderia artesanal, taller automotriz, farmacia central">
          </div>
          <div class="col-12 col-lg-5 d-flex flex-wrap justify-content-lg-end gap-2">
            <button type="button" data-landing-clear class="btn btn-outline-secondary directory-clear-btn">
              <i class="bi bi-arrow-counterclockwise me-1"></i> Limpiar filtros
            </button>
            <span class="directory-results-meta align-self-center" data-landing-results-count>0 resultados</span>
          </div>
        </div>

        <div class="row g-3 mt-1">
          <div class="col-12 col-xl-6">
            <div class="directory-filter-group h-100">
              <div class="directory-filter-group-title"><i class="bi bi-geo-alt-fill me-1"></i> Localidad</div>
              <div class="row g-2">
                <div class="col-12 col-md-4">
                  <label class="form-label" for="landingRegionFilterDesktop">Region</label>
                  <select id="landingRegionFilterDesktop" data-landing-filter="region" class="form-select">
                    <option value="">Todas</option>
                    @foreach(($tenantFilters['regions'] ?? collect()) as $region)
                      <option value="{{ $region }}">{{ $region }}</option>
                    @endforeach
                  </select>
                </div>
                <div class="col-12 col-md-4">
                  <label class="form-label" for="landingStateFilterDesktop">Estado</label>
                  <select id="landingStateFilterDesktop" data-landing-filter="state" class="form-select">
                    <option value="">Todos</option>
                    @foreach(($tenantFilters['states'] ?? collect()) as $state)
                      <option value="{{ $state }}">{{ $state }}</option>
                    @endforeach
                  </select>
                </div>
                <div class="col-12 col-md-4">
                  <label class="form-label" for="landingCityFilterDesktop">Ciudad</label>
                  <select id="landingCityFilterDesktop" data-landing-filter="city" class="form-select">
                    <option value="">Todas</option>
                    @foreach(($tenantFilters['cities'] ?? collect()) as $city)
                      <option value="{{ $city }}">{{ $city }}</option>
                    @endforeach
                  </select>
                </div>
              </div>
            </div>
          </div>
          <div class="col-12 col-xl-6">
            <div class="directory-filter-group h-100">
              <div class="directory-filter-group-title"><i class="bi bi-sliders2-vertical me-1"></i> Especificaciones</div>
              <div class="row g-2">
                <div class="col-12 col-md-6">
                  <label class="form-label" for="landingTypeFilterDesktop">Tipo de tienda / servicio</label>
                  <select id="landingTypeFilterDesktop" data-landing-filter="type" class="form-select">
                    <option value="">Todos</option>
                    @foreach(($tenantFilters['types'] ?? collect()) as $type)
                      <option value="{{ $type }}">{{ $type }}</option>
                    @endforeach
                  </select>
                </div>
                <div class="col-12 col-md-6">
                  <label class="form-label" for="landingActivityFilterDesktop">Actividad economica</label>
                  <select id="landingActivityFilterDesktop" data-landing-filter="activity" class="form-select">
                    <option value="">Todas</option>
                    @foreach(($tenantFilters['activities'] ?? collect()) as $activity)
                      <option value="{{ $activity }}">{{ $activity }}</option>
                    @endforeach
                  </select>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
        <span class="directory-results-meta" id="landingResultsCountSummary">Tiendas y servicios disponibles por localidad</span>
      </div>

      <div class="directory-grid" id="landingDirectoryList">
        @foreach($tenantsDirectory as $tenant)
          <div class="landing-directory-item"
               data-name="{{ $tenant->name ?? '' }}"
               data-type="{{ $tenant->directory_type ?? '' }}"
               data-activity="{{ $tenant->directory_activity ?? '' }}"
               data-region="{{ $tenant->directory_region ?? '' }}"
               data-state="{{ $tenant->directory_state ?? '' }}"
              data-city="{{ $tenant->directory_city ?? '' }}"
              data-search="{{ trim(implode(' ', array_filter([
                $tenant->name ?? '',
                $tenant->slug ?? '',
                $tenant->directory_type ?? '',
                $tenant->directory_activity ?? '',
                $tenant->directory_region ?? '',
                $tenant->directory_state ?? '',
                $tenant->directory_city ?? '',
                $tenant->directory_country ?? '',
                $tenant->slogan ?? '',
                $tenant->description ?? '',
                $tenant->address ?? '',
              ]))) }}">
            <div class="directory-tenant-card d-flex flex-column">
              <div class="directory-tenant-media">
                @if(!empty($tenant->background_image))
                  <img src="{{ \App\Support\ImageStorage::url($tenant->background_image) ?? asset('assets/img/shopix5.png') }}" alt="Imagen principal de {{ $tenant->name }}">
                @else
                  <img src="{{ asset('assets/img/shopix5.png') }}" alt="Imagen principal de {{ $tenant->name }}">
                @endif
                <span class="directory-card-badge">{{ $tenant->directory_type ?? 'Tienda' }}</span>
              </div>

              <div class="p-3 p-xl-4 d-flex flex-column flex-grow-1">
                <div class="d-flex align-items-center gap-3 mb-2">
                  @if(!empty($tenant->logo))
                    <img src="{{ \App\Support\ImageStorage::url($tenant->logo) ?? asset('assets/img/shopix5.png') }}" alt="Logo de {{ $tenant->name }}" class="directory-card-logo">
                  @else
                    <img src="{{ asset('assets/img/shopix5.png') }}" alt="Logo de {{ $tenant->name }}" class="directory-card-logo">
                  @endif

                  <div>
                    <h5 class="mb-1">{{ $tenant->name }}</h5>
                    <small class="directory-chip">{{ $tenant->directory_activity ?? 'General' }}</small>
                  </div>
                </div>

                <p class="mb-3 small directory-location">
                  {{ $tenant->directory_region ?? 'Sin region' }}
                  @if(!empty($tenant->directory_state)) | {{ $tenant->directory_state }} @endif
                  @if(!empty($tenant->directory_city)) | {{ $tenant->directory_city }} @endif
                </p>

                <p class="directory-slogan">
                  {{ trim((string) ($tenant->slogan ?? '')) !== '' ? $tenant->slogan : 'Tu tienda de confianza' }}
                </p>

                <p class="directory-description">
                  {{ trim((string) ($tenant->description ?? '')) !== '' ? $tenant->description : 'Explora productos y servicios disponibles en esta tienda.' }}
                </p>

                <div class="mt-auto">
                  <a href="/{{ $tenant->slug }}" class="btn btn-dark w-100 rounded-3">Ver tienda / servicio</a>
                </div>
              </div>
            </div>
          </div>
        @endforeach
      </div>

      <div id="landingDirectoryEmpty" class="text-center text-muted mt-3 d-none">
        No hay tiendas o servicios que coincidan con los filtros seleccionados.
      </div>
    </div>
  </section>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    const landingFilterElements = Array.from(document.querySelectorAll('[data-landing-filter]'));
    const landingFilterGroups = landingFilterElements.reduce((groups, element) => {
      const key = element.dataset.landingFilter;
      groups[key] = groups[key] || [];
      groups[key].push(element);
      return groups;
    }, {});

    const landingClearFiltersButtons = document.querySelectorAll('[data-landing-clear]');
    const landingResultsCounters = document.querySelectorAll('[data-landing-results-count]');
    const landingDirectoryItems = Array.from(document.querySelectorAll('.landing-directory-item'));
    const landingDirectoryEmpty = document.getElementById('landingDirectoryEmpty');
    const landingTotalItems = landingDirectoryItems.length;

    function getLandingFilterValue(key) {
      return landingFilterGroups[key]?.[0]?.value || '';
    }

    function syncLandingFilterValue(key, value, sourceElement = null) {
      (landingFilterGroups[key] || []).forEach(element => {
        if (element !== sourceElement && element.value !== value) {
          element.value = value;
        }
      });
    }

    function normalizeText(value) {
      return String(value || '')
        .trim()
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '');
    }

    function applyLandingDirectoryFilters() {
      const selectedName = normalizeText(getLandingFilterValue('name'));
      const selectedType = normalizeText(getLandingFilterValue('type'));
      const selectedActivity = normalizeText(getLandingFilterValue('activity'));
      const selectedRegion = normalizeText(getLandingFilterValue('region'));
      const selectedState = normalizeText(getLandingFilterValue('state'));
      const selectedCity = normalizeText(getLandingFilterValue('city'));

      let visibleCount = 0;

      landingDirectoryItems.forEach(item => {
        const itemName = normalizeText(item.dataset.name);
        const itemSearch = normalizeText(item.dataset.search || item.textContent || '');
        const itemType = normalizeText(item.dataset.type);
        const itemActivity = normalizeText(item.dataset.activity);
        const itemRegion = normalizeText(item.dataset.region);
        const itemState = normalizeText(item.dataset.state);
        const itemCity = normalizeText(item.dataset.city);

        const matches =
          (!selectedName || itemSearch.includes(selectedName) || itemName.includes(selectedName)) &&
          (!selectedType || itemType === selectedType) &&
          (!selectedActivity || itemActivity === selectedActivity) &&
          (!selectedRegion || itemRegion === selectedRegion) &&
          (!selectedState || itemState === selectedState) &&
          (!selectedCity || itemCity === selectedCity);

        item.classList.toggle('d-none', !matches);
        if (matches) {
          visibleCount += 1;
        }
      });

      if (landingDirectoryEmpty) {
        landingDirectoryEmpty.classList.toggle('d-none', visibleCount > 0);
      }

      landingResultsCounters.forEach(counter => {
        counter.textContent = `${visibleCount} de ${landingTotalItems} resultados`;
      });
    }

    Object.entries(landingFilterGroups).forEach(([key, elements]) => {
      const eventName = key === 'name' ? 'input' : 'change';
      elements.forEach(element => {
        element.addEventListener(eventName, () => {
          syncLandingFilterValue(key, element.value, element);
          applyLandingDirectoryFilters();
        });
      });
    });

    landingClearFiltersButtons.forEach(button => {
      button.addEventListener('click', () => {
        Object.values(landingFilterGroups).forEach(elements => {
          elements.forEach(element => {
            element.value = '';
          });
        });

        applyLandingDirectoryFilters();
      });
    });

    const navbarCollapse = document.getElementById('landingNavbar');
    const navLinks = document.querySelectorAll('#landingNavbar .nav-link, #landingNavbar .btn');
    const bsCollapse = navbarCollapse ? new bootstrap.Collapse(navbarCollapse, { toggle: false }) : null;

    navLinks.forEach(link => {
      link.addEventListener('click', () => {
        if (window.innerWidth < 992 && navbarCollapse?.classList.contains('show') && bsCollapse) {
          bsCollapse.hide();
        }
      });
    });

    applyLandingDirectoryFilters();
  </script>
</body>

</html>
