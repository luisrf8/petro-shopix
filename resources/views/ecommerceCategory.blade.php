<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <link rel="icon" type="image/png" href="{{ route('pwa.icon', ['size' => 192, 'variant' => 'client']) }}" />
  <title>{{ $tenant->name }} - Tienda Virtual</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css" rel="stylesheet">

  @php
    $normalizeTenantHex = function ($value, $fallback) {
      $candidate = strtoupper(trim((string) $value));
      if (preg_match('/^#[0-9A-F]{6}$/', $candidate)) {
        return $candidate;
      }

      return strtoupper($fallback);
    };

    $toRgb = function ($hex) {
      $clean = ltrim($hex, '#');
      return [
        hexdec(substr($clean, 0, 2)),
        hexdec(substr($clean, 2, 2)),
        hexdec(substr($clean, 4, 2)),
      ];
    };

    $tenantColorPrimary = $normalizeTenantHex($tenant->color_primary ?? null, '#0F172A');
    $tenantColorSecondary = $normalizeTenantHex($tenant->color_secondary ?? null, '#334155');
    $tenantColorAccent = $normalizeTenantHex($tenant->color_accent ?? null, '#38BDF8');
    $baseCurrencyCode = strtoupper((string) ($baseCurrencyCode ?? ($tenant->base_currency ?? 'USD')));
    $baseCurrencyCode = in_array($baseCurrencyCode, ['USD', 'EUR'], true) ? $baseCurrencyCode : 'USD';
    $baseCurrencySymbol = (string) ($baseCurrencySymbol ?? ($baseCurrencyCode === 'EUR' ? '€' : '$'));

    $mapsUrl = null;
    if (!empty($tenant->latitude) && !empty($tenant->longitude)) {
      $mapsUrl = 'https://www.google.com/maps?q=' . $tenant->latitude . ',' . $tenant->longitude;
    } else {
      $addressParts = array_filter([
        $tenant->address ?? '',
        $tenant->city_name ?? '',
        $tenant->state_name ?? '',
        $tenant->country_name ?? '',
      ]);
      if (!empty($addressParts)) {
        $mapsUrl = 'https://www.google.com/maps/search/?api=1&query=' . urlencode(implode(', ', $addressParts));
      }
    }

    [$tenantPrimaryR, $tenantPrimaryG, $tenantPrimaryB] = $toRgb($tenantColorPrimary);
    [$tenantSecondaryR, $tenantSecondaryG, $tenantSecondaryB] = $toRgb($tenantColorSecondary);
    [$tenantAccentR, $tenantAccentG, $tenantAccentB] = $toRgb($tenantColorAccent);
  @endphp

  @include('partials.tenant-pwa-head', ['tenant' => $tenant, 'tenantColorPrimary' => $tenantColorPrimary])

  <style>
    :root {
      --tenant-primary: {{ $tenantColorPrimary }};
      --tenant-secondary: {{ $tenantColorSecondary }};
      --tenant-accent: {{ $tenantColorAccent }};
      --tenant-primary-rgb: {{ $tenantPrimaryR }}, {{ $tenantPrimaryG }}, {{ $tenantPrimaryB }};
      --tenant-secondary-rgb: {{ $tenantSecondaryR }}, {{ $tenantSecondaryG }}, {{ $tenantSecondaryB }};
      --tenant-accent-rgb: {{ $tenantAccentR }}, {{ $tenantAccentG }}, {{ $tenantAccentB }};
    }

    body {
      font-family: 'Inter', sans-serif;
      background-color: #f3f4f6;
      color: #111827;
      min-height: 100vh;
      display: flex;
      flex-direction: column;
    }

    .landing-header {
      transition: background 0.25s ease, border-color 0.25s ease, box-shadow 0.25s ease;
      z-index: 1050;
      background: transparent;
      backdrop-filter: none;
      border-bottom: 1px solid transparent;
      padding: 0.5rem 0;
    }

    .landing-header.is-scrolled {
      background: rgba(255, 255, 255, 0.92);
      backdrop-filter: blur(10px);
      border-bottom-color: rgba(var(--tenant-primary-rgb), 0.16);
      box-shadow: none;
    }

    .landing-header .navbar-toggler {
      border: 1px solid rgba(255, 255, 255, 0.55);
      background: rgba(255, 255, 255, 0.16);
      padding: 0.35rem 0.55rem;
    }

    .landing-header.is-scrolled .navbar-toggler {
      border-color: rgba(var(--tenant-primary-rgb), 0.35);
      background: #ffffff;
    }

    .landing-header.is-scrolled .navbar-toggler-icon {
      filter: invert(1) grayscale(1);
    }

    .landing-nav-link {
      font-weight: 600;
      padding: 0.46rem 0.88rem;
      border-radius: 999px;
      font-size: 0.96rem;
      display: inline-flex;
      align-items: center;
      gap: 0.42rem;
      border: 1px solid transparent;
    }

    .tenant-main-nav-btn {
      background: rgba(255, 255, 255, 0.14);
      border-color: rgba(255, 255, 255, 0.44);
      color: #ffffff !important;
    }

    .tenant-main-nav-btn:hover,
    .tenant-main-nav-btn:focus {
      background: rgba(255, 255, 255, 0.22);
      border-color: rgba(255, 255, 255, 0.66);
      color: #ffffff !important;
    }

    .landing-header.is-scrolled .tenant-main-nav-btn,
    #landingNavbar.show .tenant-main-nav-btn {
      background: #f8fafc;
      border-color: #d6e0ef;
      color: #1e293b !important;
    }

    .landing-header.is-scrolled .tenant-main-nav-btn:hover,
    .landing-header.is-scrolled .tenant-main-nav-btn:focus,
    #landingNavbar.show .tenant-main-nav-btn:hover,
    #landingNavbar.show .tenant-main-nav-btn:focus {
      background: #eef2ff;
      border-color: rgba(var(--tenant-accent-rgb), 0.45);
      color: #0f172a !important;
    }

    .tenant-logo-chip {
      border: 1px solid rgba(255, 255, 255, 0.75) !important;
      background: linear-gradient(135deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.96)) !important;
      border-radius: 12px !important;
      transition: background 0.25s ease, border-color 0.25s ease;
      box-shadow: none;
    }

    .landing-header.is-scrolled .tenant-logo-chip {
      border-color: #d6e0ef !important;
      background: #ffffff !important;
    }

    .tenant-logo-image {
      width: 100px;
      height: 50px;
      object-fit: contain;
      filter: none;
    }

    .landing-nav-link.category-link.active {
      background: rgba(var(--tenant-accent-rgb), 0.2);
      color: #fff !important;
      border-color: rgba(var(--tenant-accent-rgb), 0.6);
    }

    .section-title {
      font-size: clamp(1.4rem, 4.5vw, 2rem);
      font-weight: 700;
      margin-bottom: 2rem;
      text-align: center;
      color: #111827;
    }

    .section-shell {
      background: #ffffff;
    }

    .section-muted {
      background: #eef2f7;
    }

    .btn-primary {
      background: linear-gradient(135deg, var(--tenant-primary), var(--tenant-secondary));
      border-color: var(--tenant-primary);
    }

    .btn-primary:hover,
    .btn-primary:focus {
      background: linear-gradient(135deg, var(--tenant-secondary), var(--tenant-primary));
      border-color: var(--tenant-secondary);
    }

    .btn-dark {
      background: linear-gradient(135deg, var(--tenant-primary), var(--tenant-secondary));
      border-color: var(--tenant-primary);
    }

    .btn-dark:hover,
    .btn-dark:focus {
      background: linear-gradient(135deg, var(--tenant-secondary), var(--tenant-primary));
      border-color: var(--tenant-secondary);
    }

    .btn-outline-dark {
      color: var(--tenant-primary);
      border-color: rgba(var(--tenant-primary-rgb), 0.45);
    }

    .btn-outline-dark:hover,
    .btn-outline-dark:focus {
      color: #fff;
      background: var(--tenant-primary);
      border-color: var(--tenant-primary);
    }

    .form-check-input:checked {
      background-color: var(--tenant-primary);
      border-color: var(--tenant-primary);
    }

    .badge.text-bg-light.border {
      background: rgba(var(--tenant-accent-rgb), 0.14) !important;
      color: var(--tenant-primary);
      border-color: rgba(var(--tenant-accent-rgb), 0.48) !important;
    }

    footer.bg-dark {
      background: linear-gradient(135deg, var(--tenant-primary), var(--tenant-secondary)) !important;
      margin-top: auto;
    }

    .page-shell {
      padding-top: 6.2rem;
    }

    .card-product {
      border: 1px solid #e5e7eb;
      border-radius: 16px;
      overflow: hidden;
      box-shadow: 0 12px 24px rgba(15, 23, 42, 0.08);
      transition: transform 0.2s ease, box-shadow 0.2s ease;
      background-color: #fff;
    }

    .product-card-link {
      color: inherit;
    }

    .product-card-media {
      position: relative;
    }

    .product-card-body {
      padding: 0.95rem 0.95rem 0.9rem;
      display: grid;
      gap: 0.72rem;
    }

    .product-card-copy {
      display: grid;
      gap: 0.26rem;
    }

    .product-card-title {
      font-size: 1rem;
      line-height: 1.2;
      margin: 0;
      color: #111827;
    }

    .product-card-description {
      color: #6b7280;
      font-size: 0.84rem;
      line-height: 1.35;
      margin: 0;
    }

    .product-variant-strip {
      display: flex;
      gap: 0.5rem;
      overflow-x: auto;
      padding-bottom: 0.1rem;
      margin: 0 -0.15rem;
      scroll-snap-type: x proximity;
      scrollbar-width: thin;
    }

    .product-variant-strip::-webkit-scrollbar {
      height: 6px;
    }

    .product-variant-strip::-webkit-scrollbar-thumb {
      background: #cbd5e1;
      border-radius: 999px;
    }

    .product-variant-chip {
      min-width: 122px;
      border: 1px solid #cfd8e3;
      background: linear-gradient(180deg, #ffffff, #f8fafc);
      border-radius: 14px;
      padding: 0.58rem 0.72rem;
      display: grid;
      gap: 0.3rem;
      box-shadow: 0 8px 18px rgba(15, 23, 42, 0.06);
      scroll-snap-align: start;
      flex: 0 0 auto;
    }

    .product-variant-chip.product-variant-more {
      place-content: center;
      text-align: center;
      color: var(--tenant-primary);
      background: linear-gradient(180deg, rgba(var(--tenant-accent-rgb), 0.12), rgba(var(--tenant-primary-rgb), 0.08));
      border-style: dashed;
    }

    .product-variant-chip.product-variant-more strong {
      font-size: 1rem;
      line-height: 1;
    }

    .product-variant-chip.product-variant-more small {
      color: #475569;
      font-size: 0.72rem;
      font-weight: 700;
    }

    .product-variant-size {
      display: inline-flex;
      align-items: center;
      gap: 0.35rem;
      font-size: 0.82rem;
      font-weight: 800;
      color: var(--tenant-primary);
      letter-spacing: 0.02em;
      text-transform: uppercase;
    }

    .product-variant-price {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 0.45rem;
      font-size: 0.84rem;
      color: #111827;
    }

    .product-variant-price strong {
      font-size: 0.98rem;
      color: #0f172a;
    }

    .product-card-footer {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 0.65rem;
      color: #475569;
      font-size: 0.8rem;
    }

    .product-card-cta {
      display: inline-flex;
      align-items: center;
      gap: 0.35rem;
      font-weight: 700;
      color: var(--tenant-primary);
    }

    .landing-media-image {
      border-radius: 14px !important;
    }

    .card-product:hover {
      transform: translateY(-3px);
      box-shadow: 0 16px 28px rgba(15, 23, 42, 0.12);
    }

    .discovery-shell {
      background: #ffffff;
      border-top: 1px solid #e5e7eb;
      border-bottom: 1px solid #e5e7eb;
    }

    .discovery-head p {
      color: #6b7280;
      margin-bottom: 0;
    }

    .trust-pills {
      display: flex;
      flex-wrap: wrap;
      gap: 0.5rem;
      justify-content: flex-start;
    }

    .trust-pill {
      border: 1px solid rgba(var(--tenant-accent-rgb), 0.45);
      background: white;
      color: var(--tenant-primary);
      border-radius: 999px;
      font-size: 0.84rem;
      font-weight: 600;
      padding: 0.35rem 0.75rem;
    }

    .catalog-filter-rail {
      display: flex;
      gap: 0.75rem;
      overflow-x: auto;
      padding-bottom: 0.25rem;
      scroll-snap-type: x proximity;
    }

    .catalog-filter-rail::-webkit-scrollbar {
      height: 7px;
    }

    .catalog-filter-rail::-webkit-scrollbar-thumb {
      background: #cbd5e1;
      border-radius: 999px;
    }

    .filter-chip-card {
      min-width: 200px;
      border: 1px solid #dbe3ee;
      background: #f8fafc;
      border-radius: 16px;
      display: flex;
      align-items: center;
      gap: 0.7rem;
      padding: 0.7rem;
      text-align: left;
      transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
      scroll-snap-align: start;
      color: #111827;
      text-decoration: none;
    }

    .filter-chip-card:hover {
      transform: translateY(-2px);
      box-shadow: 0 10px 20px rgba(15, 23, 42, 0.08);
      border-color: rgba(var(--tenant-accent-rgb), 0.75);
    }

    .filter-chip-thumb {
      width: 44px;
      height: 44px;
      border-radius: 12px;
      background: #e5e7eb;
      background-size: cover;
      background-position: center;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      color: var(--tenant-primary);
      flex-shrink: 0;
      border: 1px solid rgba(var(--tenant-accent-rgb), 0.35);
    }

    .filter-chip-meta {
      display: flex;
      flex-direction: column;
      line-height: 1.2;
      min-width: 0;
    }

    .filter-chip-meta small {
      color: #64748b;
      font-size: 0.72rem;
    }

    .filter-chip-meta strong {
      font-size: 0.92rem;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
      color: #111827;
    }

    .category-card {
    position: relative;
    display: block;
    width: 100%;
    height: 260px;
    background-size: cover;
    background-position: center;
    border-radius: 14px;
    border: 1px solid #d1d5db;
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
    font-size: clamp(1rem, 3vw, 1.5rem);
    font-weight: 700;
    text-align: center;
}

    .category-link.active.category-card,
    .category-card.active {
      box-shadow: 0 0 0 3px rgba(var(--tenant-accent-rgb), 0.55);
      transform: translateY(-3px);
    }

    .category-link.active.filter-chip-card {
      border-color: var(--tenant-primary);
      background: linear-gradient(135deg, var(--tenant-primary), var(--tenant-secondary));
    }

    .category-link.active.filter-chip-card .filter-chip-meta small,
    .category-link.active.filter-chip-card .filter-chip-meta strong,
    .category-link.active.filter-chip-card .filter-chip-thumb {
      color: #fff;
      border-color: rgba(255, 255, 255, 0.35);
    }

    .products-layout {
      display: grid;
      grid-template-columns: minmax(230px, 280px) 1fr;
      gap: 1rem;
    }

    .filters-panel {
      position: sticky;
      top: 92px;
      align-self: flex-start;
    }

    .filter-panel-card {
      background: #fff;
      border: 1px solid #e5e7eb;
      border-radius: 16px;
      box-shadow: 0 12px 24px rgba(15, 23, 42, 0.08);
      padding: 1rem;
    }

    .mobile-filter-shell {
      display: none;
    }

    .mobile-filter-shell .filter-panel-card {
      margin-top: 0.35rem;
      box-shadow: none;
      border-color: #dbe4f0;
      background: linear-gradient(180deg, #ffffff, #f8fafc);
    }

    .catalog-search {
      display: flex;
      align-items: center;
      gap: 0.55rem;
      border: 1px solid #d1d5db;
      border-radius: 12px;
      padding: 0.3rem 0.65rem;
      background: #fff;
    }

    .catalog-search i {
      color: #64748b;
    }

    .catalog-search .form-control {
      font-size: 0.92rem;
      padding: 0.35rem 0;
      color: #111827;
    }

    .filter-card-btn {
      border: 1px solid #d1d5db;
      background: #f8fafc;
      border-radius: 12px;
      color: #111827;
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 0.45rem;
      width: 100%;
      padding: 0.55rem 0.7rem;
      transition: all 0.2s ease;
      font-size: 0.92rem;
      text-align: left;
      text-decoration: none;
    }

    .filter-card-btn:hover {
      border-color: rgba(var(--tenant-accent-rgb), 0.7);
      background: #f1f5f9;
    }

    .filter-card-btn.active {
      border-color: var(--tenant-primary);
      background: linear-gradient(135deg, var(--tenant-primary), var(--tenant-secondary));
      color: #fff;
    }

    .filter-card-btn.active small {
      color: rgba(255, 255, 255, 0.85) !important;
    }

    .products-summary {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 0.75rem;
      margin-bottom: 0.75rem;
      color: #334155;
    }

    .price-neo-chip {
      display: inline-flex;
      align-items: center;
      gap: 0.25rem;
      border: 1px solid rgba(var(--tenant-accent-rgb), 0.38);
      background: linear-gradient(135deg, #ffffff, #f8fafc);
      color: #0f172a;
      border-radius: 999px;
      padding: 0.22rem 0.62rem;
      font-size: 0.84rem;
      font-weight: 700;
      box-shadow: 0 8px 16px rgba(15, 23, 42, 0.08);
    }

    .price-neo-chip strong {
      color: var(--tenant-primary);
      font-weight: 800;
    }

    .empty-state {
      border: 1px dashed #cbd5e1;
      border-radius: 14px;
      background: #f8fafc;
      color: #64748b;
      padding: 0.9rem;
      text-align: center;
      font-size: 0.92rem;
    }

    @media (max-width: 991.98px) {
      .landing-header {
        background: transparent;
      }

      .landing-header.is-scrolled {
        background: rgba(255, 255, 255, 0.95);
      }

      .products-layout {
        grid-template-columns: 1fr;
      }

      .filters-panel {
        display: none;
      }

      .mobile-filter-shell {
        display: block;
      }

      .filter-chip-card {
        min-width: 170px;
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
        text-align: left;
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

      .category-card {
        height: 220px;
      }

      .product-card-body {
        padding: 0.85rem 0.85rem 0.82rem;
        gap: 0.65rem;
      }
    }

    @media (max-width: 575.98px) {
      .page-shell {
        padding-top: 5.7rem;
      }

      #productos.py-3 {
        padding-top: 0.85rem !important;
        padding-bottom: 1.35rem !important;
      }

      .category-card {
        height: 190px;
      }

      .filter-chip-card {
        min-width: 150px;
      }

      .filter-chip-thumb {
        width: 38px;
        height: 38px;
      }

      .card-product img,
      .card-product .d-flex.align-items-center.justify-content-center {
        height: 210px !important;
      }

      .card-product {
        border-radius: 18px;
      }

      .product-card-title {
        font-size: 0.98rem;
      }

      .product-card-description {
        font-size: 0.81rem;
      }

      .product-variant-strip {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.42rem;
        margin-inline: 0;
        overflow-x: visible;
        padding-bottom: 0;
        scroll-snap-type: none;
      }

      .product-variant-chip {
        min-width: 0;
        width: 100%;
        padding: 0.42rem 0.3rem;
        border-radius: 11px;
        flex: initial;
        gap: 0.18rem;
      }

      .product-variant-size {
        justify-content: center;
        font-size: 0.66rem;
        gap: 0.18rem;
      }

      .product-variant-price {
        justify-content: center;
        font-size: 0.68rem;
        gap: 0.18rem;
      }

      .product-variant-price strong {
        font-size: 0.72rem;
      }

      .product-variant-chip.product-variant-more strong {
        font-size: 0.82rem;
      }

      .product-variant-chip.product-variant-more small {
        font-size: 0.62rem;
      }

      .product-card-footer {
        font-size: 0.76rem;
      }

      .products-summary {
        margin-bottom: 0.55rem;
      }

      .product-item,
      .package-item {
        margin-bottom: 0.9rem !important;
      }

      #products-container {
        --bs-gutter-x: 0.7rem;
      }
    }

  </style>
</head>

<body>

  <!-- HEADER -->
  <header class="landing-header position-fixed top-0 start-0 w-100">
    <div class="container">
      <nav class="navbar navbar-expand-lg navbar-dark p-0">
        <a class="navbar-brand d-flex align-items-center" href="{{ route('tenant.public', ['tenant' => $tenant->slug]) }}">
          @if($tenant->logo)
            <span class="btn p-1 px-3 m-0 tenant-logo-chip">
              <img src="{{ \App\Support\ImageStorage::url($tenant->logo) ?? asset('assets/img/shopix5.png') }}" alt="Logo {{ $tenant->name }}" class="img-fluid tenant-logo-image">
            </span>
          @else
            <span class="fw-bold text-white">{{ $tenant->name }}</span>
          @endif
        </a>

        <button type="button"
                class="btn tenant-nav-action-btn landing-nav-link d-inline-flex align-items-center tenant-icon-btn d-lg-none ms-auto me-2"
                aria-label="Carrito"
                title="Carrito"
                data-bs-toggle="offcanvas"
                data-bs-target="#tenantCartOffcanvas"
                aria-controls="tenantCartOffcanvas">
          <i class="bi bi-cart3"></i>
          <span class="badge rounded-pill bg-dark tenant-cart-count">0</span>
        </button>

        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#landingNavbar" aria-controls="landingNavbar" aria-expanded="false" aria-label="Toggle navigation">
          <span class="navbar-toggler-icon"></span>
        </button>

        <div class="collapse navbar-collapse" id="landingNavbar">
          <ul class="navbar-nav w-100 align-items-lg-center gap-lg-2">
            <li class="nav-item">
              <a class="btn landing-nav-link tenant-main-nav-btn" href="{{ route('tenant.public.categories', ['tenant' => $tenant->slug]) }}"><i class="bi bi-grid"></i> Categorías</a>
            </li>
            <li class="nav-item">
              <a class="btn landing-nav-link tenant-main-nav-btn" href="{{ route('tenant.public.categories', ['tenant' => $tenant->slug]) }}#productos"><i class="bi bi-bag"></i> Productos</a>
            </li>
            <li class="nav-item">
              <a class="btn landing-nav-link tenant-main-nav-btn" href="{{ route('tenant.public', ['tenant' => $tenant->slug]) }}#contacto"><i class="bi bi-chat-dots"></i> Contacto</a>
            </li>
            <li class="nav-item">
              <a class="btn landing-nav-link tenant-main-nav-btn" href="#" data-shopix-open-auth><i class="bi bi-person-circle"></i> Entrar</a>
            </li>
            @if(!empty($mapsUrl))
              <li class="nav-item">
                <a class="btn landing-nav-link tenant-main-nav-btn" href="{{ $mapsUrl }}" target="_blank" rel="noopener noreferrer"><i class="bi bi-geo-alt"></i> Ver dirección</a>
              </li>
            @endif
            @include('partials.tenant-cart-nav')
            <li class="nav-item">
              <a class="btn landing-nav-link tenant-main-nav-btn" href="{{ route('tenant.public', ['tenant' => $tenant->slug]) }}"><i class="bi bi-arrow-left"></i> Volver</a>
            </li>
          </ul>

          <div class="mobile-filter-shell d-lg-none">
            <div class="filter-panel-card">
              <h3 class="h6 fw-bold mb-3">Filtrar catálogo</h3>

              <label for="product-search-mobile" class="small text-muted mb-1">Buscar por nombre</label>
              <div class="catalog-search mb-3">
                <i class="bi bi-search"></i>
                <input type="text" id="product-search-mobile" data-catalog-search class="form-control border-0 shadow-none" placeholder="Ej. termo, camiseta, paquete...">
              </div>

              <div class="d-grid gap-2">
                <button type="button" class="filter-card-btn category-link active" data-id="all" aria-pressed="true">
                  <span class="fw-semibold">Todos</span>
                  <small class="text-muted">Mostrar todo</small>
                </button>

                @foreach($categories as $category)
                  <button type="button" class="filter-card-btn category-link" data-id="{{ $category->id }}" aria-pressed="false">
                    <span class="fw-semibold">{{ $category->name }}</span>
                    <small class="text-muted">Filtrar</small>
                  </button>
                @endforeach

                @if(isset($materialPackages) && $materialPackages->count() > 0)
                  <button type="button" class="filter-card-btn category-link" data-id="packages" aria-pressed="false">
                    <span class="fw-semibold">Paquetes</span>
                    <small class="text-muted">Combos</small>
                  </button>
                @endif
              </div>

              <hr class="my-3">
              <div class="small text-muted d-grid gap-2">
                <div><i class="bi bi-shield-lock me-1"></i> Compra protegida</div>
                <div><i class="bi bi-lock me-1"></i> Datos protegidos</div>
                <div><i class="bi bi-whatsapp me-1"></i> Soporte directo</div>
              </div>
            </div>
          </div>
        </div>
      </nav>
    </div>
  </header>


  <!-- PRODUCTOS -->
  <section id="productos" class="py-3 section-muted mt-10">
    <div class="container">
      <div class="d-flex justify-content-between align-items-center mb-3 gap-2 flex-wrap">
        <div>
          <h2 class="section-title text-start mb-0">Catálogo de productos</h2>
          <p class="text-muted mb-0">Vista organizada por categorías para navegar más rápido.</p>
        </div>
      </div>

      <div class="products-layout">
        <aside class="filters-panel">
          <div class="filter-panel-card">
            <h3 class="h6 fw-bold mb-3">Filtrar catálogo</h3>

            <label for="product-search-desktop" class="small text-muted mb-1">Buscar por nombre</label>
            <div class="catalog-search mb-3">
              <i class="bi bi-search"></i>
              <input type="text" id="product-search-desktop" data-catalog-search class="form-control border-0 shadow-none" placeholder="Ej. termo, camiseta, paquete...">
            </div>

            <div class="d-grid gap-2">
              <button type="button" class="filter-card-btn category-link active" data-id="all" aria-pressed="true">
                <span class="fw-semibold">Todos</span>
                <small class="text-muted">Mostrar todo</small>
              </button>

              @foreach($categories as $category)
                <button type="button" class="filter-card-btn category-link" data-id="{{ $category->id }}" aria-pressed="false">
                  <span class="fw-semibold">{{ $category->name }}</span>
                  <small class="text-muted">Filtrar</small>
                </button>
              @endforeach

              @if(isset($materialPackages) && $materialPackages->count() > 0)
                <button type="button" class="filter-card-btn category-link" data-id="packages" aria-pressed="false">
                  <span class="fw-semibold">Paquetes</span>
                  <small class="text-muted">Combos</small>
                </button>
              @endif
            </div>

            <hr class="my-3">
            <div class="small text-muted d-grid gap-2">
              <div><i class="bi bi-shield-lock me-1"></i> Compra protegida</div>
              <div><i class="bi bi-lock me-1"></i> Datos protegidos</div>
              <div><i class="bi bi-whatsapp me-1"></i> Soporte directo</div>
            </div>
          </div>
        </aside>

        <div>
          <div class="row" id="products-container">
            @foreach($products as $product)
              <div class="col-6 col-sm-6 col-md-4 col-lg-4 mb-4 product-item" data-category="{{ $product->category_id }}" data-name="{{ strtolower($product->name) }}">
                <a href="{{ route('tenant.public.product', [
                    'tenant' => $tenant->slug,
                    'product' => $product->slug
                  ]) }}" class="product-card-link text-decoration-none d-block h-100">
                  <div class="card card-product h-100">
                    <div class="product-card-media">
                      @if(isset($product->images[0]))
                        <img src="{{ \App\Support\ImageStorage::url($product->images[0]->path) ?? asset('assets/img/shopix5.png') }}" class="card-img-top landing-media-image" style="height: 300px; object-fit: cover;">
                      @else
                        <div class="d-flex align-items-center justify-content-center" style="height: 300px; background-color: #eee;">
                          <i class="bi bi-image text-muted fs-1"></i>
                        </div>
                      @endif
                    </div>
                    <div class="card-body product-card-body text-start">
                      <div class="product-card-copy">
                        <h5 class="product-card-title fw-bold">{{ $product->name }}</h5>
                        <p class="product-card-description">{{ \Illuminate\Support\Str::limit($product->description ?? 'Producto destacado en esta tienda.', 84) }}</p>
                      </div>
                      <div class="product-variant-strip" aria-label="Variantes disponibles de {{ $product->name }}">
                        @foreach ($product->variants->take(2) as $variant)
                          @php
                            $productDiscount = (float) ($product->discount_percentage ?? 0);
                            $variantDiscount = (float) ($variant->discount_percentage ?? 0);
                            $effectiveVariantPrice = (float) $variant->price * ((100 - $productDiscount) / 100) * ((100 - $variantDiscount) / 100);
                          @endphp
                          <div class="product-variant-chip">
                            <span class="product-variant-size">
                              <i class="bi bi-tag"></i>
                              {{ $variant->size }}
                            </span>
                            <span class="product-variant-price">
                              <small>{{ $baseCurrencySymbol }}</small>
                              <strong>{{ number_format($effectiveVariantPrice, 2) }}</strong>
                            </span>
                          </div>
                        @endforeach
                        @if ($product->variants->count() > 2)
                          <div class="product-variant-chip product-variant-more">
                            <strong>+{{ $product->variants->count() - 2 }}</strong>
                            <small>ver más</small>
                          </div>
                        @endif
                      </div>
                      <div class="product-card-footer">
                        <span>{{ $product->variants->count() }} variante{{ $product->variants->count() === 1 ? '' : 's' }}</span>
                        <span class="product-card-cta">Ver producto <i class="bi bi-arrow-right-short"></i></span>
                      </div>
                    </div>
                  </div>
                </a>
              </div>
            @endforeach

            @if(isset($materialPackages) && $materialPackages->count() > 0)
              @foreach($materialPackages as $package)
                @php
                  $firstItem = $package->items->first();
                  $firstImage = $firstItem && $firstItem->variant && $firstItem->variant->product && isset($firstItem->variant->product->images[0])
                    ? (\App\Support\ImageStorage::url($firstItem->variant->product->images[0]->path) ?? asset('assets/img/shopix5.png'))
                    : null;
                  $packageTotalBeforeDiscount = $package->items->sum(function($it) {
                    $basePrice = (float) ($it->variant->price ?? 0);
                    $productDiscount = (float) ($it->variant->product->discount_percentage ?? 0);
                    $variantDiscount = (float) ($it->variant->discount_percentage ?? 0);
                    $price = $basePrice
                      * ((100 - $productDiscount) / 100)
                      * ((100 - $variantDiscount) / 100);
                    $qty = (float) ($it->quantity ?? 0);
                    return $price * $qty;
                  });
                  $packageDiscount = (float) ($package->discount_percentage ?? 0);
                  $packageTotalCalculated = $packageTotalBeforeDiscount * ((100 - $packageDiscount) / 100);
                  $packageTotal = !is_null($package->package_price)
                    ? (float) $package->package_price
                    : $packageTotalCalculated;
                @endphp
                <div class="col-12 col-sm-6 col-md-4 col-lg-4 mb-4 package-item" data-name="{{ strtolower($package->name) }}">
                  <div class="card card-product h-100">
                    @if($firstImage)
                      <img src="{{ $firstImage }}" class="card-img-top landing-media-image" style="height: 300px; object-fit: cover;">
                    @else
                      <div class="d-flex align-items-center justify-content-center" style="height: 300px; background-color: #eee;">
                        <i class="bi bi-box-seam text-muted fs-1"></i>
                      </div>
                    @endif
                    <div class="card-body text-start">
                      <h5 class="fw-bold text-dark">{{ $package->name }}</h5>
                      <p class="text-muted mb-1">{{ $package->description ?: 'Paquete personalizado.' }}</p>
                      <p class="small mb-1">{{ $package->items->count() }} material(es)</p>
                      <p class="mb-2">
                        <span class="price-neo-chip">
                          <strong>{{ number_format($packageTotal, 2) }}</strong>
                          <span>{{ $baseCurrencySymbol }}</span>
                        </span>
                      </p>
                      @if(!is_null($package->package_price))
                        <p class="text-dark small mb-2">Precio fijo combo</p>
                      @endif
                      @if($packageDiscount > 0)
                        <p class="text-success small mb-2">Descuento del paquete: {{ number_format($packageDiscount, 2) }}%</p>
                      @endif
                      <div class="d-flex gap-2 align-items-center">
                        <input type="number" min="1" value="1" class="form-control form-control-sm" id="tenant-pack-qty-{{ $package->id }}" style="max-width: 90px;">
                        <button type="button" class="btn btn-dark btn-sm js-add-tenant-package" data-package-id="{{ $package->id }}">Agregar paquete</button>
                      </div>
                    </div>
                  </div>
                </div>
              @endforeach
            @endif
          </div>

          <div id="products-empty" class="empty-state" style="display: none;">
            No encontramos productos con los filtros seleccionados.
          </div>

          <div id="packages-empty" class="empty-state" style="display: none;">
            No encontramos paquetes con los filtros seleccionados.
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
  const landingHeader = document.querySelector('.landing-header');
  const navLinksCollapse = document.querySelectorAll('#landingNavbar .nav-link, #landingNavbar .btn');
  const navbarCollapse = document.getElementById('landingNavbar');
  const bsCollapse = navbarCollapse ? new bootstrap.Collapse(navbarCollapse, { toggle: false }) : null;

  const hasHeroSection = !!document.querySelector('.hero');

  function syncLandingHeaderState() {
    if (!landingHeader) {
      return;
    }

    const isScrolled = !hasHeroSection || window.scrollY > 14;
    landingHeader.classList.toggle('is-scrolled', isScrolled);
  }

  window.addEventListener('scroll', syncLandingHeaderState, { passive: true });

  if (navbarCollapse) {
    navbarCollapse.addEventListener('shown.bs.collapse', () => {
      landingHeader?.classList.add('is-scrolled');
    });

    navbarCollapse.addEventListener('hidden.bs.collapse', () => {
      syncLandingHeaderState();
    });
  }

  syncLandingHeaderState();

  navLinksCollapse.forEach(link => {
    link.addEventListener('click', () => {
      if (window.innerWidth < 992 && navbarCollapse.classList.contains('show') && bsCollapse) {
        bsCollapse.hide();
      }
    });
  });

  const categoryLinks = document.querySelectorAll('.category-link[data-id]');
  const products = document.querySelectorAll('.product-item');
  const packageItems = document.querySelectorAll('.package-item');
  const searchInputs = document.querySelectorAll('[data-catalog-search]');
  const productsCounter = document.getElementById('products-counter');
  const productsEmpty = document.getElementById('products-empty');
  const packagesEmpty = document.getElementById('packages-empty');
  function sendCartCommand(type, detail = {}) {
    document.dispatchEvent(new CustomEvent('shopix-cart-command', {
      detail: { type, ...detail }
    }));
  }

  function addTenantPackageToCart(packageId) {
    const qtyInput = document.getElementById(`tenant-pack-qty-${packageId}`);
    const packQty = Math.max(1, parseInt(qtyInput?.value || '1', 10));

    sendCartCommand('add-package', {
      packageId: Number(packageId),
      packageQty: packQty,
    });
  }

  document.querySelectorAll('.js-add-tenant-package').forEach(button => {
    button.addEventListener('click', () => {
      addTenantPackageToCart(button.dataset.packageId);
    });
  });

  let activeCategory = 'all';
  let searchText = '';

  function setActiveCategory(categoryId) {
    activeCategory = categoryId;

    categoryLinks.forEach(link => {
      const isActive = link.dataset.id === categoryId;
      link.classList.toggle('active', isActive);

      if (link.tagName === 'BUTTON') {
        link.setAttribute('aria-pressed', isActive ? 'true' : 'false');
      }
    });
  }

  function syncSearchInputs(value) {
    searchInputs.forEach(input => {
      if (input.value !== value) {
        input.value = value;
      }
    });
  }

  function applyCatalogFilters() {
    let visibleProducts = 0;
    let visiblePackages = 0;

    products.forEach(product => {
      const productName = (product.dataset.name || '').toLowerCase();
      const matchesCategory = activeCategory === 'all' || (activeCategory !== 'packages' && product.dataset.category === activeCategory);
      const matchesSearch = !searchText || productName.includes(searchText);
      const isVisible = matchesCategory && matchesSearch;

      product.style.display = isVisible ? 'block' : 'none';
      if (isVisible) {
        visibleProducts += 1;
      }
    });

    packageItems.forEach(item => {
      const packageName = (item.dataset.name || '').toLowerCase();
      const matchesSearch = !searchText || packageName.includes(searchText);
      const isVisible = (activeCategory === 'all' || activeCategory === 'packages') && matchesSearch;

      item.style.display = isVisible ? 'block' : 'none';
      if (isVisible) {
        visiblePackages += 1;
      }
    });

    const totalVisible = visibleProducts + visiblePackages;

    if (productsCounter) {
      productsCounter.textContent = `Mostrando ${totalVisible} resultado${totalVisible === 1 ? '' : 's'}`;
    }

    if (productsEmpty) {
      productsEmpty.style.display = totalVisible > 0 ? 'none' : 'block';
    }

    if (packagesEmpty) {
      packagesEmpty.style.display = activeCategory === 'packages' && visiblePackages === 0 ? 'block' : 'none';
    }
  }

  categoryLinks.forEach(link => {
    link.addEventListener('click', e => {
      e.preventDefault();

      setActiveCategory(link.dataset.id);
      applyCatalogFilters();

      if (window.innerWidth < 992 && navbarCollapse?.classList.contains('show') && link.closest('.mobile-filter-shell') && bsCollapse) {
        bsCollapse.hide();
      }

      const targetSection = document.getElementById('productos');

      if (targetSection && (link.classList.contains('category-card') || link.classList.contains('filter-chip-card') || link.dataset.id === 'packages')) {
        targetSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
      }
    });
  });

  searchInputs.forEach(input => {
    input.addEventListener('input', () => {
      searchText = (input.value || '').trim().toLowerCase();
      syncSearchInputs(input.value || '');
      applyCatalogFilters();
    });
  });

  setActiveCategory('all');
  syncSearchInputs('');
  applyCatalogFilters();
</script>
</body>

</html>
