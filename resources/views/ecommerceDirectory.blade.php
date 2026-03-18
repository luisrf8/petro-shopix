<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Shopix - Por tienda / servicio</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">
  <style>
    body {
      font-family: 'Inter', sans-serif;
      background: #f8f9fb;
      color: #151515;
    }

    .landing-header {
      background: #ffffff;
      border-bottom: 1px solid #e9ecef;
      z-index: 1050;
    }

    .landing-nav-link {
      font-weight: 600;
      padding: 0.4rem 0.75rem;
    }

    .hero {
      background: linear-gradient(135deg, #0f172a, #1e293b);
      color: #fff;
      padding: 7rem 0 3rem;
    }

    .hero h1 {
      font-size: clamp(1.6rem, 4.2vw, 2.8rem);
      font-weight: 700;
    }

    .directory-filter-card {
      border: 1px solid #e9ecef;
      border-radius: 14px;
      background: #fff;
      box-shadow: 0 8px 28px rgba(0, 0, 0, 0.06);
    }

    .directory-tenant-card {
      border: 1px solid #e9ecef;
      border-radius: 14px;
      background: #fff;
      box-shadow: 0 8px 20px rgba(0, 0, 0, 0.06);
      transition: transform 0.2s ease, box-shadow 0.2s ease;
      height: 100%;
    }

    .directory-tenant-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 12px 25px rgba(0, 0, 0, 0.1);
    }

    .directory-tenant-logo {
      width: 62px;
      height: 62px;
      border-radius: 999px;
      object-fit: cover;
      border: 1px solid #e9ecef;
      background: #fff;
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
              <a class="btn btn-light text-dark landing-nav-link" href="/login">Iniciar sesión</a>
            </li>
          </ul>
        </div>
      </nav>
    </div>
  </header>

  <section class="hero">
    <div class="container">
      <h1>Por tienda / servicio y localidad</h1>
      <p class="mb-0 opacity-75">Encuentra tiendas y servicios por actividad económica y ubicación dentro de Venezuela.</p>
    </div>
  </section>

  <section class="py-4">
    <div class="container">
      <div class="directory-filter-card p-3 p-md-4 mb-4">
        <div class="row g-2">
          <div class="col-12 col-md-6 col-lg-4">
            <label class="form-label mb-1" for="landingTypeFilter">Tipo de tienda/servicio</label>
            <select id="landingTypeFilter" class="form-select">
              <option value="">Todos</option>
              @foreach(($tenantFilters['types'] ?? collect()) as $type)
                <option value="{{ $type }}">{{ $type }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-12 col-md-6 col-lg-4">
            <label class="form-label mb-1" for="landingActivityFilter">Actividad económica</label>
            <select id="landingActivityFilter" class="form-select">
              <option value="">Todas</option>
              @foreach(($tenantFilters['activities'] ?? collect()) as $activity)
                <option value="{{ $activity }}">{{ $activity }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-12 col-md-6 col-lg-4">
            <label class="form-label mb-1" for="landingRegionFilter">Parte de Venezuela</label>
            <select id="landingRegionFilter" class="form-select">
              <option value="">Todas</option>
              @foreach(($tenantFilters['regions'] ?? collect()) as $region)
                <option value="{{ $region }}">{{ $region }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-12 col-md-6 col-lg-6">
            <label class="form-label mb-1" for="landingStateFilter">Estado</label>
            <select id="landingStateFilter" class="form-select">
              <option value="">Todos</option>
              @foreach(($tenantFilters['states'] ?? collect()) as $state)
                <option value="{{ $state }}">{{ $state }}</option>
              @endforeach
            </select>
          </div>
          <div class="col-12 col-md-6 col-lg-6">
            <label class="form-label mb-1" for="landingCityFilter">Ciudad</label>
            <select id="landingCityFilter" class="form-select">
              <option value="">Todas</option>
              @foreach(($tenantFilters['cities'] ?? collect()) as $city)
                <option value="{{ $city }}">{{ $city }}</option>
              @endforeach
            </select>
          </div>
        </div>
      </div>

      <div class="row g-3" id="landingDirectoryList">
        @foreach($tenantsDirectory as $tenant)
          <div class="col-12 col-md-6 col-lg-4 landing-directory-item"
               data-type="{{ $tenant->directory_type ?? '' }}"
               data-activity="{{ $tenant->directory_activity ?? '' }}"
               data-region="{{ $tenant->directory_region ?? '' }}"
               data-state="{{ $tenant->directory_state ?? '' }}"
               data-city="{{ $tenant->directory_city ?? '' }}">
            <div class="directory-tenant-card p-3 d-flex flex-column">
              <div class="d-flex align-items-center gap-3 mb-2">
                @if(!empty($tenant->logo))
                  <img src="{{ asset('storage/' . $tenant->logo) }}" alt="{{ $tenant->name }}" class="directory-tenant-logo">
                @else
                  <img src="{{ asset('assets/img/shopix5.png') }}" alt="{{ $tenant->name }}" class="directory-tenant-logo">
                @endif
                <div>
                  <h5 class="mb-0">{{ $tenant->name }}</h5>
                  <small class="text-muted">{{ $tenant->directory_type ?? 'Tienda' }}</small>
                </div>
              </div>
              <p class="mb-1 small"><strong>Actividad:</strong> {{ $tenant->directory_activity ?? 'General' }}</p>
              <p class="mb-3 small text-muted">
                {{ $tenant->directory_region ?? 'Sin región' }}
                @if(!empty($tenant->directory_state)) | {{ $tenant->directory_state }} @endif
                @if(!empty($tenant->directory_city)) | {{ $tenant->directory_city }} @endif
              </p>
              <div class="mt-auto">
                <a href="/{{ $tenant->slug }}" class="btn btn-dark w-100">Ver tienda / servicio</a>
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
    const landingFilters = {
      type: document.getElementById('landingTypeFilter'),
      activity: document.getElementById('landingActivityFilter'),
      region: document.getElementById('landingRegionFilter'),
      state: document.getElementById('landingStateFilter'),
      city: document.getElementById('landingCityFilter'),
    };

    const landingDirectoryItems = Array.from(document.querySelectorAll('.landing-directory-item'));
    const landingDirectoryEmpty = document.getElementById('landingDirectoryEmpty');

    function normalizeText(value) {
      return String(value || '').trim().toLowerCase();
    }

    function applyLandingDirectoryFilters() {
      const selectedType = normalizeText(landingFilters.type?.value);
      const selectedActivity = normalizeText(landingFilters.activity?.value);
      const selectedRegion = normalizeText(landingFilters.region?.value);
      const selectedState = normalizeText(landingFilters.state?.value);
      const selectedCity = normalizeText(landingFilters.city?.value);

      let visibleCount = 0;

      landingDirectoryItems.forEach(item => {
        const itemType = normalizeText(item.dataset.type);
        const itemActivity = normalizeText(item.dataset.activity);
        const itemRegion = normalizeText(item.dataset.region);
        const itemState = normalizeText(item.dataset.state);
        const itemCity = normalizeText(item.dataset.city);

        const matches =
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
    }

    Object.values(landingFilters).forEach(filterEl => {
      filterEl?.addEventListener('change', applyLandingDirectoryFilters);
    });

    applyLandingDirectoryFilters();
  </script>
</body>

</html>
