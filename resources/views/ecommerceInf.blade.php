<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <link rel="icon" type="image/png" href="{{ \App\Support\ImageStorage::url($tenant->logo) ?? asset('assets/img/shopix5.png') }}" />
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

    $countryName = $tenant->country_name ?? '';
    $stateName = $tenant->state_name ?? '';
    $cityName = $tenant->city_name ?? '';
    $locationSummary = implode(' - ', array_filter([$countryName, $stateName, $cityName]));
    $weekDayLabels = [
      'monday' => 'Lunes',
      'tuesday' => 'Martes',
      'wednesday' => 'Miércoles',
      'thursday' => 'Jueves',
      'friday' => 'Viernes',
      'saturday' => 'Sábado',
      'sunday' => 'Domingo',
    ];
    $workingDaysText = collect($tenant->working_days ?? [])
      ->map(fn ($day) => $weekDayLabels[strtolower((string) $day)] ?? null)
      ->filter()
      ->values()
      ->implode(', ');
    $openingTimeLabel = !empty($tenant->opening_time) ? \Illuminate\Support\Str::substr((string) $tenant->opening_time, 0, 5) : '';
    $closingTimeLabel = !empty($tenant->closing_time) ? \Illuminate\Support\Str::substr((string) $tenant->closing_time, 0, 5) : '';
    $workingHoursLabel = ($openingTimeLabel !== '' || $closingTimeLabel !== '')
      ? trim(($openingTimeLabel !== '' ? $openingTimeLabel : '--:--') . ' - ' . ($closingTimeLabel !== '' ? $closingTimeLabel : '--:--'))
      : '';
    $whatsapp = preg_replace('/\D/', '', (string) ($tenant->phone_code . $tenant->phone_number));
    $whatsappUrl = !empty($whatsapp) ? 'https://api.whatsapp.com/send?phone=' . $whatsapp : null;
    $serviceLabel = !empty($tenant->business_type) && \Illuminate\Support\Str::lower((string) $tenant->business_type) === 'servicio'
      ? 'Servicio'
      : 'Tienda';
    $heroSummary = !empty($tenant->description)
      ? \Illuminate\Support\Str::limit((string) $tenant->description, 96)
      : ($serviceLabel === 'Servicio' ? 'Agenda, consulta y compra desde tu móvil.' : 'Explora, elige y compra desde tu móvil.');
    $daysShortLabel = $workingDaysText !== ''
      ? \Illuminate\Support\Str::limit($workingDaysText, 36)
      : ($serviceLabel === 'Servicio' ? 'Atención por agenda' : 'Horario no publicado');
    $mapsUrl = null;
    if (!empty($tenant->latitude) && !empty($tenant->longitude)) {
      $mapsUrl = 'https://www.google.com/maps?q=' . $tenant->latitude . ',' . $tenant->longitude;
    } else {
      $addressParts = array_filter([
        $tenant->address ?? '',
        $cityName,
        $stateName,
        $countryName,
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

    .landing-header.is-scrolled .tenant-main-nav-btn {
      background: #f8fafc;
      border-color: #d6e0ef;
      color: #1e293b !important;
    }

    .landing-header.is-scrolled .tenant-main-nav-btn:hover,
    .landing-header.is-scrolled .tenant-main-nav-btn:focus {
      background: #eef2ff;
      border-color: rgba(var(--tenant-accent-rgb), 0.45);
      color: #0f172a !important;
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

    .hero {
      position: relative;
      min-height: 92vh;
      display: flex;
      align-items: center;
      justify-content: center;
      text-align: center;
      color: white;
      background-size: cover;
      background-position: center;
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

    .hero-overlay.hero-overlay-image {
      background: rgba(2, 6, 23, 0.42);
    }

    .hero-overlay.hero-overlay-color {
      background: rgba(var(--tenant-primary-rgb), 0.62);
    }

    .hero .container {
      z-index: 1;
      padding-top: 5.5rem;
    }

    .hero-copy-shell {
      max-width: 980px;
      margin-inline: auto;
      border: 0;
      border-radius: 0;
      background: transparent;
      padding: 0;
      box-shadow: none;
    }

    .hero-title {
      font-size: clamp(2rem, 5vw, 4rem);
      font-weight: 700;
      line-height: 1.1;
      color: #fff;
      text-shadow: 2px 2px 8px rgba(0, 0, 0, 1);
    }

    .hero-slogan {
      font-size: clamp(1.2rem, 3.8vw, 2.4rem);
      font-weight: 600;
      line-height: 1.25;
      color: #fff;
      text-shadow: 2px 2px 8px rgba(0, 0, 0, 1);
      margin-top: 0.5rem;
    }

    .hero-description {
      font-size: clamp(0.95rem, 2.8vw, 1.2rem);
      color: #fff;
      text-shadow: 1px 1px 6px rgba(0, 0, 0, 0.8);
      max-width: 850px;
      margin: 0.75rem auto 0;
    }

    .hero-badges {
      display: flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 0.5rem;
      margin-top: 1rem;
    }

    .hero-badge {
      border: 1px solid rgba(226, 232, 240, 0.42);
      background: rgba(30, 41, 59, 0.82);
      color: #fff;
      border-radius: 999px;
      padding: 0.35rem 0.8rem;
      font-size: 0.88rem;
      font-weight: 600;
    }

    .hero-actions {
      margin-top: 1.2rem;
      display: flex;
      justify-content: center;
      gap: 0.6rem;
      flex-wrap: wrap;
    }

    .hero-action-secondary {
      border-color: rgba(255, 255, 255, 0.78);
      color: #ffffff;
      background: rgba(15, 23, 42, 0.56);
    }

    .hero-action-secondary:hover,
    .hero-action-secondary:focus {
      color: #ffffff;
      background: rgba(15, 23, 42, 0.35);
      border-color: #ffffff;
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

    .section-soft {
      background: #f6f8fc;
    }

    .section-muted {
      background: #eef2f7;
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

    footer.bg-dark p {
      margin-bottom: 0;
    }

    .card-product {
      border: 1px solid #e5e7eb;
      border-radius: 16px;
      overflow: hidden;
      box-shadow: 0 12px 24px rgba(15, 23, 42, 0.08);
      transition: transform 0.2s ease, box-shadow 0.2s ease;
      background-color: #fff;
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

    .discovery-map-btn {
      border-radius: 999px;
      font-weight: 700;
      border: 1px solid rgba(var(--tenant-primary-rgb), 0.45);
      color: var(--tenant-primary);
      background: #ffffff;
      padding: 0.38rem 0.9rem;
      text-decoration: none;
      display: inline-flex;
      align-items: center;
      gap: 0.35rem;
    }

    .discovery-map-btn:hover,
    .discovery-map-btn:focus {
      color: #ffffff;
      background: linear-gradient(135deg, var(--tenant-primary), var(--tenant-secondary));
      border-color: transparent;
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

    .contact-card {
      background: rgba(255, 255, 255, 0.9);
      border: 1px solid #dbe4f0;
      border-radius: 20px;
      padding: 1.5rem;
      box-shadow: 0 18px 42px rgba(15, 23, 42, 0.08);
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
      background: linear-gradient(135deg, var(--tenant-primary), var(--tenant-secondary));
      color: #ffffff;
      border-color: rgba(var(--tenant-primary-rgb), 0.45);
    }

    .contact-btn-primary:hover {
      color: #ffffff;
      background: linear-gradient(135deg, var(--tenant-secondary), var(--tenant-primary));
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

    .contact-stat-grid {
      display: grid;
      grid-template-columns: repeat(2, minmax(0, 1fr));
      gap: 0.75rem;
      margin: 1rem 0 1.25rem;
    }

    .contact-stat {
      border: 1px solid #e5e7eb;
      border-radius: 16px;
      padding: 0.9rem;
      background: #fff;
    }

    .contact-stat small {
      display: block;
      color: #64748b;
      text-transform: uppercase;
      font-size: 0.72rem;
      letter-spacing: 0.06em;
      margin-bottom: 0.35rem;
      font-weight: 700;
    }

    .contact-stat strong {
      display: block;
      color: #0f172a;
      font-size: 0.96rem;
      line-height: 1.3;
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

      .filter-chip-card {
        min-width: 170px;
      }

      .hero {
        min-height: 85vh;
      }

      .category-card {
        height: 220px;
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
        text-align: left;
      }

      #landingNavbar.show {
        margin-top: 0.6rem;
        padding: 0.75rem;
        border: 1px solid #dbe4f0;
        border-radius: 14px;
        background: rgba(255, 255, 255, 0.97);
        box-shadow: 0 12px 26px rgba(15, 23, 42, 0.1);
      }

      #landingNavbar.show .tenant-main-nav-btn,
      #landingNavbar.show .tenant-nav-action-btn {
        background: #f8fafc;
        border-color: #d6e0ef;
        color: #1e293b !important;
      }

      #landingNavbar.show .tenant-nav-action-btn i {
        background: #e8eef9;
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
        height: 220px !important;
      }

      .contact-btn {
        width: 100%;
        justify-content: center;
      }

      .contact-stat {
        padding: 0.85rem;
      }

      .contact-stat-grid {
        grid-template-columns: 1fr;
      }
    }
  </style>
</head>

<body>

  <!-- HEADER -->
  <header class="landing-header position-fixed top-0 start-0 w-100">
    <div class="container">
      <nav class="navbar navbar-expand-lg navbar-dark p-0">
        <a class="navbar-brand d-flex align-items-center" href="#top">
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
              <a class="btn landing-nav-link tenant-main-nav-btn" href="#categorias"><i class="bi bi-grid"></i> Categorías</a>
            </li>
            <li class="nav-item">
              <a class="btn landing-nav-link tenant-main-nav-btn" href="#productos"><i class="bi bi-bag"></i> Productos</a>
            </li>
            <li class="nav-item">
              <a class="btn landing-nav-link tenant-main-nav-btn" href="#contacto"><i class="bi bi-chat-dots"></i> Contacto</a>
            </li>
            <li class="nav-item">
              <a class="btn landing-nav-link tenant-main-nav-btn" href="#" data-shopix-open-auth><i class="bi bi-person-circle"></i> Entrar</a>
            </li>
            @include('partials.tenant-cart-nav')
          </ul>
        </div>
      </nav>
    </div>
  </header>

  <!-- HERO -->
  <section id="top" class="hero" style="
      @if(isset($tenant->background_image) && $tenant->background_image)
        background-image: url('{{ \App\Support\ImageStorage::url($tenant->background_image) ?? asset('assets/img/shopix5.png') }}');
      @else
        background-image: linear-gradient(135deg, {{ $tenantColorPrimary }}, {{ $tenantColorSecondary }}, {{ $tenantColorAccent }});
      @endif
      background-position: center;
      background-repeat: no-repeat;
      background-size: cover;
      overflow: hidden;
  ">

    <div class="hero-overlay {{ isset($tenant->background_image) && $tenant->background_image ? 'hero-overlay-image' : 'hero-overlay-color' }}"></div>
    <div class="container text-center">
      @php
          $businessTypeLabel = !empty($tenant->business_type)
            ? \Illuminate\Support\Str::title(str_replace('_', ' ', (string) $tenant->business_type))
            : null;
          $economicActivityLabel = !empty($tenant->economic_activity)
            ? \Illuminate\Support\Str::title((string) $tenant->economic_activity)
            : null;
          $locationLabel = implode(' · ', array_filter([
            $tenant->city_name ?? null,
            $tenant->state_name ?? null,
            $tenant->country_name ?? null,
          ]));
      @endphp
      <div class="hero-copy-shell">
        <h1 class="hero-title">{{ strtoupper($tenant->name) }}</h1>
        <h2 class="hero-slogan">{{ $tenant->slogan ?: ($serviceLabel === 'Servicio' ? 'Tu servicio, sin vueltas.' : 'Compra fácil, rápido y directo.') }}</h2>
        <p class="hero-description">{{ $heroSummary }}</p>
        <div class="hero-badges">
          @if(!empty($locationLabel))
            <span class="hero-badge">{{ $locationLabel }}</span>
          @endif
          <span class="hero-badge">{{ $serviceLabel }}</span>
          @if(!empty($economicActivityLabel))
            <span class="hero-badge">{{ $economicActivityLabel }}</span>
          @endif
        </div>
        <div class="hero-actions">
          <a href="{{ route('tenant.public.categories', ['tenant' => $tenant->slug]) }}" class="btn btn-outline-light px-4">Explorar</a>
          @if(!empty($whatsappUrl))
            <a href="{{ $whatsappUrl }}" target="_blank" rel="noopener noreferrer" class="btn hero-action-secondary px-4">WhatsApp</a>
          @endif
          @if(!empty($mapsUrl))
            <a href="{{ $mapsUrl }}" target="_blank" rel="noopener noreferrer" class="btn hero-action-secondary px-4">Ubicación</a>
          @endif
        </div>
      </div>
    </div>
  </section>

  <!-- PRODUCTOS -->
  <section id="productos" class="py-5 section-muted">
    <div class="container">

      <div class="products-layout">
        <aside class="filters-panel">
          <div class="filter-panel-card">
            <h3 class="h6 fw-bold mb-3">Filtrar catálogo</h3>

            <label for="product-search" class="small text-muted mb-1">Buscar por nombre</label>
            <div class="catalog-search mb-3">
              <i class="bi bi-search"></i>
              <input type="text" id="product-search" class="form-control border-0 shadow-none" placeholder="Ej. termo, camiseta, paquete...">
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
          <div class="products-summary">
            <span id="products-counter">Mostrando {{ $productItems->count() }} resultado{{ $productItems->count() == 1 ? '' : 's' }}</span>
            <a href="{{ route('tenant.public.categories', ['tenant' => $tenant->slug]) }}" class="btn">Ver todos los productos</a>
          </div>

          <div class="row" id="products-container">
            @foreach($productItems as $product)
              <div class="col-12 col-sm-6 col-lg-4 mb-4 product-item" data-category="{{ $product->category_id }}" data-name="{{ strtolower($product->name) }}">
                <a href="{{ route('tenant.public.categories', ['tenant' => $tenant->slug]) }}" class="text-decoration-none d-block h-100">
                  <div class="card card-product h-100">
                    @if(isset($product->images[0]))
                      <img src="{{ \App\Support\ImageStorage::url($product->images[0]->path) ?? asset('assets/img/shopix5.png') }}" class="card-img-top landing-media-image" style="height: 300px; object-fit: cover;">
                    @else
                      <div class="d-flex align-items-center justify-content-center" style="height: 300px; background-color: #eee;">
                        <i class="bi bi-image text-muted fs-1"></i>
                      </div>
                    @endif
                    <div class="card-body text-start">
                      <h5 class="fw-bold text-dark mb-1">{{ $product->name }}</h5>
                      <p class="text-muted small mb-0">{{ \Illuminate\Support\Str::limit($product->description ?? 'Producto destacado en esta tienda.', 72) }}</p>
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
                <div class="col-12 col-sm-6 col-lg-4 mb-4 package-item" data-name="{{ strtolower($package->name) }}">
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
                      <p class="text-muted mb-2">{{ $package->description ?: 'Paquete personalizado de productos.' }}</p>
                      <p class="small mb-2">Incluye {{ $package->items->count() }} material(es)</p>
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
  <!-- CONTACTO / UBICACIÓN -->
  <section id="contacto" class="py-5 section-soft">
    <div class="container">
      <div class="row align-items-center">
        <div class="col-12 col-md-6 mb-4 mb-md-0">
          <div class="contact-card">
          <h2 class="section-title text-start mb-3">Contacto</h2>
          <div class="contact-stat-grid">
            <div class="contact-stat">
              <small>Horario</small>
              <strong>{{ $workingHoursLabel !== '' ? $workingHoursLabel : 'Sin horario publicado' }}</strong>
            </div>
            <div class="contact-stat">
              <small>Días</small>
              <strong>{{ $workingDaysText !== '' ? $workingDaysText : 'Consulta disponibilidad' }}</strong>
            </div>
            <div class="contact-stat">
              <small>Zona</small>
              <strong>{{ $locationSummary !== '' ? $locationSummary : 'Ubicación por confirmar' }}</strong>
            </div>
            <div class="contact-stat">
              <small>Dirección</small>
              <strong>{{ !empty($tenant->address) ? $tenant->address : 'Disponible por mapa o contacto directo' }}</strong>
            </div>
          </div>
          <div class="contact-actions">
            @if(!empty($whatsappUrl))
              <a href="{{ $whatsappUrl }}" target="_blank" class="contact-btn contact-btn-primary">
                <i class="bi bi-whatsapp"></i> Escríbenos por WhatsApp
              </a>
            @endif
            @if(!empty($mapsUrl))
              <a href="{{ $mapsUrl }}" target="_blank" rel="noopener noreferrer" class="contact-btn contact-btn-secondary">
                <i class="bi bi-geo-alt"></i> Ver dirección
              </a>
            @endif
          </div>
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
        </div>
        <div class="col-12 col-md-6">
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

  @include('partials.tenant-cart-offcanvas')

  @php
    $tenantPackagesPayload = ($materialPackages ?? collect())->map(function ($package) {
      return [
        'id' => $package->id,
        'name' => $package->name,
        'discount_percentage' => (float) ($package->discount_percentage ?? 0),
        'package_price' => !is_null($package->package_price) ? (float) $package->package_price : null,
        'items' => $package->items->map(function ($item) {
          $basePrice = (float) ($item->variant->price ?? 0);
          $productDiscount = (float) ($item->variant->product->discount_percentage ?? 0);
          $variantDiscount = (float) ($item->variant->discount_percentage ?? 0);
          $effectivePrice = $basePrice * ((100 - $productDiscount) / 100) * ((100 - $variantDiscount) / 100);

          return [
            'variant_id' => $item->product_variant_id,
            'variant_size' => $item->variant->size ?? '',
            'variant_price' => (float) $effectivePrice,
            'product_name' => $item->variant->product->name ?? 'Producto',
            'image_src' => isset($item->variant->product->images[0])
              ? (\App\Support\ImageStorage::url($item->variant->product->images[0]->path) ?? asset('assets/img/shopix5.png'))
              : asset('assets/img/shopix5.png'),
            'quantity' => (float) ($item->quantity ?? 0),
          ];
        })->values()->toArray(),
      ];
    })->values()->toArray();
  @endphp

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

  <script>
    const landingHeader = document.querySelector('.landing-header');
    const navLinks = document.querySelectorAll('#landingNavbar .nav-link, #landingNavbar .btn');
    const navbarCollapse = document.getElementById('landingNavbar');
    const bsCollapse = navbarCollapse ? new bootstrap.Collapse(navbarCollapse, { toggle: false }) : null;

    function syncLandingHeaderState() {
      if (!landingHeader) {
        return;
      }

      const isScrolled = window.scrollY > 14;
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

    navLinks.forEach(link => {
      link.addEventListener('click', () => {
        if (window.innerWidth < 992 && navbarCollapse.classList.contains('show') && bsCollapse) {
          bsCollapse.hide();
        }
      });
    });

    // Filtrado de productos por categoría
    const categoryLinks = document.querySelectorAll('.category-link[data-id]');
    const products = document.querySelectorAll('.product-item');
    const packageItems = document.querySelectorAll('.package-item');
    const searchInput = document.getElementById('product-search');
    const productsCounter = document.getElementById('products-counter');
    const productsEmpty = document.getElementById('products-empty');
    const packagesEmpty = document.getElementById('packages-empty');
    const tenantPackages = @json($tenantPackagesPayload);
    const tenantSlug = @json($tenant->slug);
    let activeCategory = 'all';

    function sendCartCommand(type, detail = {}) {
      document.dispatchEvent(new CustomEvent('shopix-cart-command', {
        detail: { type, ...detail }
      }));
    }

    function addTenantPackageToCart(packageId) {
      const pkg = tenantPackages.find(p => Number(p.id) === Number(packageId));
      if (!pkg) {
        alert('No se encontró el paquete.');
        return;
      }

      const qtyInput = document.getElementById(`tenant-pack-qty-${packageId}`);
      const packQty = Math.max(1, parseInt(qtyInput?.value || '1', 10));

      pkg.items.forEach(component => {
        const quantity = (Number(component.quantity || 0) * packQty);
        if (quantity <= 0) return;
        const packageDiscount = Math.max(0, Math.min(100, Number(pkg.discount_percentage || 0)));
        const packageBaseTotal = pkg.items.reduce((sum, row) => {
          const rowQty = Number(row.quantity || 0);
          const rowBasePrice = Number(row.variant_price || 0);
          return sum + (rowBasePrice * ((100 - packageDiscount) / 100) * rowQty);
        }, 0);

        const targetPackageTotal = (pkg.package_price !== null && pkg.package_price !== undefined)
          ? (Number(pkg.package_price) || 0)
          : packageBaseTotal;

        const priceScale = packageBaseTotal > 0 ? (targetPackageTotal / packageBaseTotal) : 1;

        const componentPrice = Number(component.variant_price || 0)
          * ((100 - packageDiscount) / 100)
          * priceScale;

          sendCartCommand('add-item', {
            item: {
          variantId: Number(component.variant_id),
          productId: Number(component.variant_id),
          productName: `${component.product_name} [${pkg.name}]`,
          variantSize: component.variant_size,
              imageSrc: component.image_src || null,
          price: componentPrice,
          qty: quantity,
            }
          });
      });

        sendCartCommand('open-cart');

      alert(`Paquete "${pkg.name}" agregado al carrito.`);
    }

      document.querySelectorAll('.js-add-tenant-package').forEach(button => {
        button.addEventListener('click', () => {
          addTenantPackageToCart(button.dataset.packageId);
        });
      });

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

    function applyCatalogFilters() {
      const searchText = (searchInput?.value || '').trim().toLowerCase();
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

        const targetSection = document.getElementById('productos');

        if (targetSection && (link.classList.contains('category-card') || link.classList.contains('filter-chip-card') || link.dataset.id === 'packages')) {
          targetSection.scrollIntoView({ behavior: 'smooth', block: 'start' });
        }
      });
    });

    if (searchInput) {
      searchInput.addEventListener('input', applyCatalogFilters);
    }

    setActiveCategory('all');
    applyCatalogFilters();
  </script>
</body>
</html>
