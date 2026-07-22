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
    $showBsPrices = (bool) ($showBsPrices ?? ($tenant->show_bs_prices_in_storefront ?? false));
    $storefrontBsRate = (float) ($storefrontBsRate ?? 0);

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
    $projectWhatsappBaseUrl = !empty($whatsapp) ? 'https://api.whatsapp.com/send?phone=' . $whatsapp : null;
    $serviceLabel = !empty($tenant->business_type) && \Illuminate\Support\Str::lower((string) $tenant->business_type) === 'servicio'
      ? 'Servicio'
      : 'Tienda';
    $tenantOffersProjects = (bool) ($tenant->offers_projects ?? true);
    $heroSummary = !empty($tenant->description)
      ? \Illuminate\Support\Str::limit((string) $tenant->description, 96)
      : ($serviceLabel === 'Servicio' ? 'Agenda, consulta y compra desde tu móvil.' : 'Explora, elige y compra desde tu móvil.');
    $daysShortLabel = $workingDaysText !== ''
      ? \Illuminate\Support\Str::limit($workingDaysText, 36)
      : ($serviceLabel === 'Servicio' ? 'Atención por agenda' : 'Horario no publicado');
    $mapsUrl = null;
    $defaultWhatsappMessage = 'Hola, vengo de tu tienda virtual de Shopix';
    $projectWhatsappMessage = 'Hola, vi la landing de proyectos en Shopix y quiero solicitar una cotización personalizada.';
    $tenantExternalUrl = trim((string) ($tenant->external_url ?? ''));
    if ($tenantExternalUrl !== '' && !\Illuminate\Support\Str::startsWith(\Illuminate\Support\Str::lower($tenantExternalUrl), ['http://', 'https://'])) {
      $tenantExternalUrl = 'https://' . $tenantExternalUrl;
    }
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
    if (!empty($whatsappUrl)) {
      $whatsappUrl .= '&text=' . urlencode($defaultWhatsappMessage);
    }
    $projectWhatsappUrl = !empty($projectWhatsappBaseUrl)
      ? $projectWhatsappBaseUrl . '&text=' . urlencode($projectWhatsappMessage)
      : null;
    $tenantBackgroundUrl = !empty($tenant->background_image)
      ? (
        \App\Support\ImageStorage::url($tenant->background_image)
        ?? asset('assets/img/shopix5.png')
      )
      : null;
    $tenantBackgroundExt = strtolower(pathinfo((string) ($tenant->background_image ?? ''), PATHINFO_EXTENSION));
    $tenantBackgroundMediaType = strtolower(trim((string) ($tenant->background_media_type ?? 'image')));
    $heroHasVideoBackground = !empty($tenantBackgroundUrl)
      && ($tenantBackgroundMediaType === 'video' || in_array($tenantBackgroundExt, ['mp4', 'webm', 'mov'], true));
    $heroHasImageBackground = !empty($tenantBackgroundUrl) && !$heroHasVideoBackground;

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
      z-index: 1;
    }

    .hero-background-video {
      position: absolute;
      inset: 0;
      width: 100%;
      height: 100%;
      object-fit: cover;
      z-index: 0;
    }

    .hero-overlay.hero-overlay-image {
      background: rgba(2, 6, 23, 0.42);
    }

    .hero-overlay.hero-overlay-color {
      background: rgba(var(--tenant-primary-rgb), 0.62);
    }

    .hero .container {
      position: relative;
      z-index: 2;
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

    .products-main-grid {
      display: block;
    }

    .catalog-appointments-section {
      margin-top: 1.15rem;
      grid-column: 1 / -1;
      width: 100%;
      min-width: 0;
    }

    .catalog-appointments-section .filter-panel-card {
      width: 100%;
      max-width: none;
    }

    .catalog-appointments-toolbar {
      display: grid;
      grid-template-columns: minmax(180px, 260px) minmax(220px, 1fr) auto;
      gap: 0.65rem;
      align-items: end;
      margin-bottom: 0.7rem;
    }

    .catalog-appointments-view-group {
      display: inline-flex;
      border: 1px solid #d1d5db;
      border-radius: 8px;
      overflow: hidden;
    }

    .catalog-appointments-view-group .btn {
      border-radius: 0;
      border: 0;
      min-width: 74px;
    }

    .catalog-appointments-nav {
      display: inline-flex;
      align-items: center;
      gap: 0.45rem;
      justify-self: end;
    }

    .catalog-appointments-nav .btn {
      min-width: 98px;
    }

    .catalog-appointments-range {
      font-size: 2rem;
      font-weight: 700;
      color: #1f2937;
      margin-bottom: 0.85rem;
    }

    .catalog-agenda-board {
      overflow-x: auto;
      border: 1px solid #dbe4f0;
      border-radius: 16px;
      background: #ffffff;
      padding: 0.7rem;
    }

    .catalog-agenda-grid {
      display: grid;
      grid-template-columns: 78px repeat(7, minmax(140px, 1fr));
      gap: 0.5rem;
      min-width: 1040px;
    }

    .catalog-agenda-grid.day-view {
      grid-template-columns: 78px minmax(280px, 1fr);
      min-width: 0;
    }

    .catalog-agenda-hours-title {
      font-size: 0.72rem;
      font-weight: 700;
      color: #94a3b8;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      align-self: end;
      padding-bottom: 0.35rem;
    }

    .catalog-agenda-day-head {
      border: 1px solid #dbe4f0;
      background: #f8fafc;
      border-radius: 14px;
      padding: 0.55rem;
      text-align: center;
      min-height: 64px;
    }

    .catalog-agenda-day-head.active {
      background: #0f172a;
      color: #ffffff;
      border-color: #0f172a;
    }

    .catalog-agenda-day-head.available {
      background: #16a34a;
      color: #ffffff;
      border-color: #15803d;
    }

    .catalog-agenda-day-head.occupied {
      background: #dc2626;
      color: #ffffff;
      border-color: #b91c1c;
    }

    .catalog-agenda-day-head.closed,
    .catalog-agenda-day-head.past {
      background: #6b7280;
      color: #ffffff;
      border-color: #4b5563;
    }

    .catalog-agenda-day-weekday {
      display: block;
      text-transform: uppercase;
      font-size: 0.72rem;
      letter-spacing: 0.06em;
      font-weight: 700;
      opacity: 0.85;
    }

    .catalog-agenda-day-date {
      display: block;
      font-weight: 700;
      font-size: 1.05rem;
      margin-top: 0.15rem;
      line-height: 1.15;
    }

    .catalog-agenda-hour {
      border-top: 1px solid #edf2f7;
      color: #94a3b8;
      font-size: 0.8rem;
      height: 58px;
      display: flex;
      align-items: center;
      padding-right: 0.35rem;
    }

    .catalog-agenda-cell {
      border: 1px solid #e2e8f0;
      border-radius: 12px;
      background: #f8fafc;
      min-height: 58px;
      padding: 0.25rem;
      display: flex;
      align-items: flex-start;
      justify-content: center;
    }

    .catalog-agenda-pill {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border-radius: 999px;
      padding: 0.15rem 0.45rem;
      font-size: 0.72rem;
      font-weight: 700;
      line-height: 1;
      margin-top: 0.15rem;
    }

    .catalog-agenda-pill.available {
      background: rgba(22, 163, 74, 0.12);
      color: #166534;
      border: 1px solid rgba(22, 163, 74, 0.32);
    }

    .catalog-agenda-pill.occupied {
      background: rgba(239, 68, 68, 0.1);
      color: #b91c1c;
      border: 1px solid rgba(239, 68, 68, 0.3);
    }

    .catalog-agenda-pill.past {
      background: rgba(107, 114, 128, 0.15);
      color: #374151;
      border: 1px solid rgba(107, 114, 128, 0.4);
    }

    .catalog-agenda-cell.past {
      background: #e5e7eb;
      border-color: #cbd5e1;
    }

    .catalog-appointments-note {
      margin-top: 0.6rem;
      color: #64748b;
    }

    .catalog-day-status-card {
      width: 100%;
      border: 1px solid #dbe4f0;
      border-radius: 14px;
      padding: 0.75rem;
      color: #fff;
      text-align: left;
      min-height: 96px;
      display: flex;
      flex-direction: column;
      justify-content: center;
      gap: 0.2rem;
    }

    .catalog-day-status-card.available {
      background: #16a34a;
      border-color: #15803d;
    }

    .catalog-day-status-card.occupied {
      background: #dc2626;
      border-color: #b91c1c;
    }

    .catalog-day-status-card.closed {
      background: #6b7280;
      border-color: #4b5563;
    }

    .catalog-day-status-card.past {
      background: #9ca3af;
      border-color: #6b7280;
    }

    .catalog-day-status-card .weekday {
      font-size: 0.78rem;
      text-transform: uppercase;
      letter-spacing: 0.06em;
      opacity: 0.9;
    }

    .catalog-day-status-card .date {
      font-size: 1.2rem;
      font-weight: 800;
      line-height: 1.1;
    }

    .catalog-day-status-card .state {
      font-size: 0.85rem;
      font-weight: 700;
      opacity: 0.95;
    }

    .catalog-day-selected {
      box-shadow: 0 0 0 2px #0f172a inset;
    }

    .catalog-appointments-legend {
      display: flex;
      gap: 0.75rem;
      flex-wrap: wrap;
      font-size: 0.78rem;
      color: #475569;
    }

    .catalog-appointments-legend span {
      display: inline-flex;
      align-items: center;
      gap: 0.35rem;
    }

    .catalog-appointments-legend i {
      width: 10px;
      height: 10px;
      border-radius: 999px;
      display: inline-block;
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

    .project-solutions-section,
    .project-live-section {
      background: #f8fafc;
    }

    .project-section-head {
      display: flex;
      justify-content: space-between;
      align-items: center;
      gap: 1rem;
      flex-wrap: wrap;
      margin-bottom: 1.75rem;
    }

    .project-section-kicker {
      font-size: 0.73rem;
      letter-spacing: 0.12em;
      text-transform: uppercase;
      color: var(--tenant-primary);
      font-weight: 800;
      margin-bottom: 0.45rem;
    }

    .project-pill-tabs {
      display: flex;
      gap: 0.45rem;
      flex-wrap: wrap;
    }

    .project-pill-tab {
      appearance: none;
      border: 1px solid rgba(15, 23, 42, 0.1);
      border-radius: 999px;
      padding: 0.45rem 0.85rem;
      background: #ffffff;
      color: #334155;
      font-size: 0.78rem;
      font-weight: 700;
      line-height: 1;
    }

    .project-pill-tab.is-active {
      background: var(--tenant-primary);
      color: #ffffff;
      border-color: var(--tenant-primary);
    }

    .project-showcase-card,
    .project-progress-card {
      border: 1px solid rgba(15, 23, 42, 0.08);
      border-radius: 18px;
      overflow: hidden;
      background: #ffffff;
      box-shadow: 0 18px 42px rgba(15, 23, 42, 0.08);
      height: 100%;
      transition: transform 0.25s ease, box-shadow 0.25s ease;
    }

    .project-showcase-card:hover,
    .project-progress-card:hover {
      transform: translateY(-4px);
      box-shadow: 0 24px 50px rgba(15, 23, 42, 0.12);
    }

    .project-showcase-media,
    .project-progress-media {
      width: 100%;
      height: 220px;
      object-fit: cover;
      display: block;
      background: linear-gradient(135deg, rgba(var(--tenant-primary-rgb), 0.15), rgba(var(--tenant-accent-rgb), 0.15));
    }

    .project-card-body {
      padding: 1.15rem;
    }

    .project-card-title {
      color: #1e293b;
      font-weight: 800;
      font-size: 1.28rem;
      margin-bottom: 0.55rem;
    }

    .project-card-text {
      color: #64748b;
      margin-bottom: 0.85rem;
    }

    .project-card-link {
      appearance: none;
      border: none;
      background: transparent;
      padding: 0;
      color: var(--tenant-primary);
      font-weight: 700;
      text-decoration: none;
    }

    .project-phase-badge {
      position: absolute;
      right: 0.9rem;
      top: 0.9rem;
      background: rgba(255, 214, 109, 0.96);
      color: #7c5800;
      border-radius: 999px;
      padding: 0.28rem 0.7rem;
      font-size: 0.7rem;
      font-weight: 800;
      letter-spacing: 0.04em;
      text-transform: uppercase;
    }

    .project-progress-shell {
      margin-top: 1rem;
    }

    .project-progress-meta {
      display: flex;
      justify-content: space-between;
      gap: 0.75rem;
      font-size: 0.82rem;
      font-weight: 700;
      color: #1e3a8a;
      margin-bottom: 0.45rem;
    }

    .project-progress-bar {
      width: 100%;
      height: 8px;
      border-radius: 999px;
      background: #e2e8f0;
      overflow: hidden;
      margin-bottom: 0.55rem;
    }

    .project-progress-bar > span {
      display: block;
      height: 100%;
      border-radius: 999px;
      background: linear-gradient(90deg, var(--tenant-primary), var(--tenant-accent));
    }

    .project-progress-steps {
      display: flex;
      justify-content: space-between;
      gap: 0.75rem;
      color: #64748b;
      font-size: 0.72rem;
    }

    .project-cta-box {
      max-width: 820px;
      margin: 2.5rem auto 0;
      background: #ffffff;
      border: 1px solid rgba(15, 23, 42, 0.12);
      border-radius: 22px;
      padding: 2.1rem 1.6rem;
      text-align: center;
      box-shadow: 0 22px 50px rgba(15, 23, 42, 0.08);
    }

    .project-cta-actions {
      display: inline-flex;
      flex-wrap: wrap;
      justify-content: center;
      gap: 0.75rem;
      margin-top: 1.2rem;
    }

    .project-cta-actions .btn {
      min-width: 220px;
      border-radius: 12px;
      font-weight: 700;
      padding: 0.75rem 1rem;
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

      .products-main-grid {
        grid-template-columns: 1fr;
      }

      .filters-panel {
        display: none;
      }

      .catalog-appointments-toolbar {
        grid-template-columns: 1fr;
        align-items: stretch;
      }

      .catalog-appointments-nav {
        justify-self: start;
      }

      .project-section-head {
        flex-direction: column;
        align-items: flex-start;
      }

      .project-cta-actions {
        display: flex;
      }

      .project-cta-actions .btn {
        min-width: 0;
        width: 100%;
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
      @if($heroHasImageBackground)
        background-image: url('{{ $tenantBackgroundUrl }}');
      @else
        background-image: linear-gradient(135deg, {{ $tenantColorPrimary }}, {{ $tenantColorSecondary }}, {{ $tenantColorAccent }});
      @endif
      background-position: center;
      background-repeat: no-repeat;
      background-size: cover;
      overflow: hidden;
  ">

    @if($heroHasVideoBackground)
      <video class="hero-background-video" autoplay muted loop playsinline preload="metadata" aria-hidden="true">
        <source src="{{ $tenantBackgroundUrl }}">
      </video>
    @endif

    <div class="hero-overlay {{ ($heroHasImageBackground || $heroHasVideoBackground) ? 'hero-overlay-image' : 'hero-overlay-color' }}"></div>
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
          @if($tenantOffersProjects && !empty($projectWhatsappUrl))
            <a href="{{ $projectWhatsappUrl }}" target="_blank" rel="noopener noreferrer" class="btn btn-outline-light px-4">Solicitar cotización</a>
          @else
            <a href="{{ route('tenant.public.categories', ['tenant' => $tenant->slug]) }}" class="btn btn-outline-light px-4">Explorar</a>
          @endif
          <a href="{{ route('tenant.public.categories', ['tenant' => $tenant->slug]) }}" class="btn hero-action-secondary px-4">Ver catálogo</a>
          @if(!empty($tenantExternalUrl))
            <a href="{{ $tenantExternalUrl }}" target="_blank" rel="noopener noreferrer" class="btn hero-action-secondary px-4">Sitio oficial</a>
          @endif
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

  @if($tenantOffersProjects)
  <section class="py-5 project-solutions-section">
    <div class="container">
      <div class="project-section-head">
        <div>
          <div class="project-section-kicker">Soluciones a medida</div>
          <h2 class="section-title text-start mb-2">Categorías y especialidades que ofrece {{ $tenant->name }}</h2>
          <p class="text-muted mb-0">Explora el tipo de solución que maneja la tienda y luego solicita tu cotización por WhatsApp.</p>
        </div>
        <div class="project-pill-tabs">
          <button type="button" class="project-pill-tab is-active" data-project-category="all">Todos</button>
          @foreach($categories->take(4) as $category)
            <button type="button" class="project-pill-tab" data-project-category="{{ $category->id }}">{{ $category->name }}</button>
          @endforeach
        </div>
      </div>

      <div class="row g-4">
        @foreach($categories as $category)
          @php
            $featuredCategoryProduct = $category->products->first();
            $featuredCategoryImage = $featuredCategoryProduct && isset($featuredCategoryProduct->images[0])
              ? (\App\Support\ImageStorage::url($featuredCategoryProduct->images[0]->path) ?? asset('assets/img/shopix5.png'))
              : null;
          @endphp
          <div class="col-12 col-md-6 col-xl-4 project-showcase-item" data-project-category-card="{{ $category->id }}">
            <article class="project-showcase-card">
              @if($featuredCategoryImage)
                <img src="{{ $featuredCategoryImage }}" alt="{{ $category->name }}" class="project-showcase-media">
              @else
                <div class="project-showcase-media d-flex align-items-center justify-content-center">
                  <i class="bi bi-grid text-muted fs-1"></i>
                </div>
              @endif
              <div class="project-card-body">
                <h3 class="project-card-title">{{ $category->name }}</h3>
                <p class="project-card-text">{{ \Illuminate\Support\Str::limit($category->description ?: 'Especialidad disponible dentro de la oferta comercial de esta tienda.', 120) }}</p>
                <button type="button" class="project-card-link project-category-trigger" data-project-category-trigger="{{ $category->id }}">Filtrar en catalogo <i class="bi bi-arrow-right"></i></button>
              </div>
            </article>
          </div>
        @endforeach
      </div>
    </div>
  </section>
  @endif

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

        <div class="products-main-grid">
          <div>
            <div class="products-summary">
              @php
                $servicesCatalogCount = collect($appointmentCatalogServices ?? [])->count();
                $initialCatalogCount = $productItems->count() + $servicesCatalogCount;
              @endphp
              <span id="products-counter">Mostrando {{ $initialCatalogCount }} resultado{{ $initialCatalogCount == 1 ? '' : 's' }}</span>
              <a href="{{ route('tenant.public.categories', ['tenant' => $tenant->slug]) }}" class="btn btn-outline-dark btn-sm px-3">
                <i class="bi bi-grid me-1"></i>Ver todos los productos
              </a>
            </div>

            <div class="row" id="products-container">
            @foreach($productItems as $product)
              <div class="col-12 col-sm-6 col-lg-4 mb-4 product-item" data-category="{{ $product->category_id }}" data-name="{{ strtolower($product->name) }}">
                <a href="{{ route('tenant.public.product', ['tenant' => $tenant->slug, 'product' => $product->slug]) }}" class="text-decoration-none d-block h-100">
                  <div class="card card-product h-100">
                    @php
                      $firstVariant = $product->variants->first();
                      $productDiscount = (float) ($product->discount_percentage ?? 0);
                      $variantDiscount = (float) ($firstVariant?->discount_percentage ?? 0);
                      $displayPrice = $firstVariant
                        ? (float) $firstVariant->price * ((100 - $productDiscount) / 100) * ((100 - $variantDiscount) / 100)
                        : null;
                      $displayPriceBs = !is_null($displayPrice) && $showBsPrices
                        ? $displayPrice * $storefrontBsRate
                        : null;
                    @endphp
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
                      @if(!is_null($displayPrice))
                        <p class="mb-0 mt-2">
                          <span class="price-neo-chip">
                            <strong>{{ number_format($displayPrice, 2) }}</strong>
                            <span>{{ $baseCurrencySymbol }}</span>
                          </span>
                        </p>
                        @if(!is_null($displayPriceBs))
                          <p class="text-muted small mb-0">Bs {{ number_format($displayPriceBs, 2) }}</p>
                        @endif
                      @endif
                    </div>
                  </div>
                </a>
              </div>
            @endforeach

            @foreach(($appointmentCatalogServices ?? []) as $appointmentService)
              @php
                $serviceVariant = $appointmentService->productVariant;
                $serviceProduct = $serviceVariant?->product;
                $serviceCategoryId = $serviceProduct?->category_id;
                $serviceImage = isset($serviceProduct->images[0])
                  ? (
                    \App\Support\ImageStorage::url($serviceProduct->images[0]->path)
                    ?? asset('assets/img/shopix5.png')
                  )
                  : null;
                $serviceAssignedUsers = $appointmentService->assignedUsers;
                $serviceAssignedUserNames = $serviceAssignedUsers->pluck('name')->filter()->values();
                if ($serviceAssignedUserNames->isEmpty() && $appointmentService->assignedUser) {
                  $serviceAssignedUserNames = collect([$appointmentService->assignedUser->name]);
                }
                $serviceDisplayPrice = (float) ($appointmentService->price ?? 0);
                if ($serviceDisplayPrice <= 0 && $serviceVariant) {
                  $serviceDisplayPrice = (float) ($serviceVariant->effective_price ?? $serviceVariant->price ?? 0);
                }
                $serviceDisplayPriceBs = $showBsPrices && $serviceDisplayPrice > 0
                  ? $serviceDisplayPrice * $storefrontBsRate
                  : null;
                $serviceWhatsappUrl = !empty($projectWhatsappBaseUrl)
                  ? $projectWhatsappBaseUrl . '&text=' . urlencode('Hola, quiero información para reservar el servicio "' . ($appointmentService->display_name ?? $appointmentService->name ?? 'Servicio') . '" que vi en la landing de Shopix.')
                  : null;
                $serviceActionLabel = ($isFreePlanTenant ?? false)
                  ? 'Consultar por WhatsApp'
                  : (($appointmentsEnabledForStorefront ?? false) ? 'Reservar cita' : 'Agenda no disponible');
                $serviceActionDisabled = !($isFreePlanTenant ?? false) && !($appointmentsEnabledForStorefront ?? false);
              @endphp
              <div class="col-12 col-sm-6 col-lg-4 mb-4 product-item product-item-service"
                   data-category="{{ $serviceCategoryId }}"
                   data-name="{{ strtolower((string) ($appointmentService->display_name ?? $appointmentService->name ?? 'servicio')) }}">
                <div class="card card-product h-100">
                  @if($serviceImage)
                    <img src="{{ $serviceImage }}" class="card-img-top landing-media-image" style="height: 300px; object-fit: cover;" alt="{{ $appointmentService->display_name ?? $appointmentService->name ?? 'Servicio' }}">
                  @else
                    <div class="d-flex align-items-center justify-content-center" style="height: 300px; background-color: #eee;">
                      <i class="bi bi-scissors text-muted fs-1"></i>
                    </div>
                  @endif
                  <div class="card-body text-start d-flex flex-column">
                    <h5 class="fw-bold text-dark mb-1">{{ $appointmentService->display_name ?? $appointmentService->name ?? 'Servicio' }}</h5>
                    <p class="text-muted small mb-2">{{ \Illuminate\Support\Str::limit((string) ($appointmentService->description ?? 'Servicio disponible por agenda.'), 72) }}</p>
                    <p class="small mb-1">Duración: {{ (int) ($appointmentService->duration_minutes ?? 60) }} min</p>
                    <p class="small text-muted mb-2">{{ $serviceAssignedUserNames->isNotEmpty() ? 'Profesional(es): ' . $serviceAssignedUserNames->join(', ') : 'Disponible con cualquier profesional activo' }}</p>
                    @if($serviceDisplayPrice > 0)
                      <p class="mb-0 mt-auto">
                        <span class="price-neo-chip">
                          <strong>{{ number_format($serviceDisplayPrice, 2) }}</strong>
                          <span>{{ $baseCurrencySymbol }}</span>
                        </span>
                      </p>
                      @if(!is_null($serviceDisplayPriceBs))
                        <p class="text-muted small mb-0">Bs {{ number_format($serviceDisplayPriceBs, 2) }}</p>
                      @endif
                    @endif

                    <button
                      type="button"
                      class="btn btn-outline-dark btn-sm mt-3 js-open-tenant-service"
                      {{ $serviceActionDisabled ? 'disabled' : '' }}
                      data-service-id="{{ (int) $appointmentService->id }}"
                      data-service-name="{{ $appointmentService->display_name ?? $appointmentService->name ?? 'Servicio' }}"
                      data-service-whatsapp-url="{{ $serviceWhatsappUrl ?? '' }}"
                      data-service-free-plan="{{ ($isFreePlanTenant ?? false) ? '1' : '0' }}"
                      data-service-appointments-enabled="{{ ($appointmentsEnabledForStorefront ?? false) ? '1' : '0' }}">
                      {{ $serviceActionLabel }}
                    </button>
                  </div>
                </div>
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
                  $packageTotalBs = $showBsPrices ? $packageTotal * $storefrontBsRate : null;
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
                      @if(!is_null($packageTotalBs))
                        <p class="text-muted small mb-2">Bs {{ number_format($packageTotalBs, 2) }}</p>
                      @endif
                      @if(!is_null($package->package_price))
                        <p class="text-dark small mb-2">Precio fijo combo</p>
                      @endif
                      @if($packageDiscount > 0)
                        <p class="text-success small mb-2">Descuento del paquete: {{ number_format($packageDiscount, 2) }}%</p>
                      @endif
                      <div class="d-flex gap-2 align-items-center">
                        <input type="number" min="1" value="1" class="form-control form-control-sm" id="tenant-pack-qty-{{ $package->id }}" style="max-width: 90px;">
                        @if($tenantOffersProjects && !empty($projectWhatsappBaseUrl))
                          <a href="{{ $projectWhatsappBaseUrl . '&text=' . urlencode('Hola, quiero cotizar el paquete ' . $package->name . ' que vi en la landing de Shopix.') }}" target="_blank" rel="noopener noreferrer" class="btn btn-dark btn-sm">Cotizar paquete</a>
                        @else
                          <button type="button" class="btn btn-dark btn-sm js-add-tenant-package" data-package-id="{{ $package->id }}">Agregar paquete</button>
                        @endif
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

        @if($appointmentsEnabledForStorefront ?? false)
        <section class="catalog-appointments-section" data-shopix-catalog-appointment>
          <div class="filter-panel-card">
            <h3 class="h5 fw-bold mb-2">Calendario de citas</h3>
            <p class="small text-muted mb-3">Visual informativa de ocupación/disponibilidad.</p>

            <div class="catalog-appointments-toolbar">
              <div>
                <label class="small text-muted mb-1 d-block">Profesional</label>
                <select class="form-select form-select-sm" data-catalog-appointment-professional>
                  <option value="">Cargando profesionales...</option>
                </select>
              </div>

              <div class="catalog-appointments-view-group" role="group" aria-label="Vista disponibilidad">
                <button type="button" class="btn btn-outline-dark btn-sm" data-catalog-appointment-view="day">Día</button>
                <button type="button" class="btn btn-outline-dark btn-sm active" data-catalog-appointment-view="week">Semana</button>
                <button type="button" class="btn btn-outline-dark btn-sm" data-catalog-appointment-view="month">Mes</button>
              </div>

              <div class="catalog-appointments-nav">
                <button type="button" class="btn btn-outline-dark btn-sm" data-catalog-appointment-prev-week>Anterior</button>
                <button type="button" class="btn btn-outline-dark btn-sm" data-catalog-appointment-today>Hoy</button>
                <button type="button" class="btn btn-outline-dark btn-sm" data-catalog-appointment-next-week>Siguiente</button>
            </div>
            </div>

            <div class="catalog-appointments-range" data-catalog-appointment-range>-</div>
            <div class="catalog-agenda-board" data-catalog-appointment-days></div>
            <small class="catalog-appointments-note d-block" data-catalog-appointment-note>Consultando disponibilidad...</small>

            <div class="catalog-appointments-legend mt-2">
              <span><i style="background:#16a34a;"></i>Disponible</span>
              <span><i style="background:#ef4444;"></i>Ocupada</span>
          </div>
          </div>
        </section>
        @endif
        </div>
      </div>
    </div>
  </section>

  @if($tenantOffersProjects)
  <section class="py-5 project-live-section">
    <div class="container">
      <div class="project-section-head">
        <div>
          <div class="project-section-kicker">Proyectos en curso</div>
          <h2 class="section-title text-start mb-2">Proyectos que se están realizando</h2>
          <p class="text-muted mb-0">Visibilidad del avance por fases para que los clientes entiendan cómo se ejecuta cada solución.</p>
        </div>
      </div>

      <div class="row g-4">
        @forelse($activeProjects as $project)
          @php
            $projectAssets = $project->assets ?? collect();
            $projectPreviewAsset = $projectAssets->first(function ($asset) {
              $ext = strtolower(pathinfo((string) ($asset->file_path ?? ''), PATHINFO_EXTENSION));
              return in_array($ext, ['jpg', 'jpeg', 'png', 'webp'], true);
            });
            $projectPreviewImage = $projectPreviewAsset && !empty($projectPreviewAsset->file_path)
              ? asset('storage/' . ltrim((string) $projectPreviewAsset->file_path, '/'))
              : null;
            $projectProgress = (int) (($project->tasks_total_count ?? 0) > 0 ? round((($project->tasks_done_count ?? 0) / max(1, $project->tasks_total_count)) * 100) : 0);
            $phaseMap = ['inicio' => 'Inicio', 'desarrollo' => 'Desarrollo', 'fin' => 'Final'];
            $projectPhaseLabel = $phaseMap[strtolower((string) $project->phase)] ?? strtoupper((string) $project->phase);
          @endphp
          <div class="col-12 col-md-6 col-xl-4">
            <article class="project-progress-card position-relative">
              <span class="project-phase-badge">{{ $projectPhaseLabel }}</span>
              @if($projectPreviewImage)
                <img src="{{ $projectPreviewImage }}" alt="{{ $project->name }}" class="project-progress-media">
              @else
                <div class="project-progress-media d-flex align-items-center justify-content-center">
                  <i class="bi bi-building text-muted fs-1"></i>
                </div>
              @endif
              <div class="project-card-body">
                <h3 class="project-card-title">{{ $project->name }}</h3>
                <p class="project-card-text">{{ \Illuminate\Support\Str::limit($project->description ?: 'Proyecto activo en ejecución dentro del portafolio de la tienda.', 120) }}</p>

                <div class="project-progress-shell">
                  <div class="project-progress-meta">
                    <span>Progreso del proyecto</span>
                    <span>{{ $projectProgress }}% completado</span>
                  </div>
                  <div class="project-progress-bar"><span style="width: {{ $projectProgress }}%;"></span></div>
                  <div class="project-progress-steps">
                    <span>Inicio</span>
                    <span>Desarrollo</span>
                    <span>Final</span>
                  </div>
                </div>
              </div>
            </article>
          </div>
        @empty
          <div class="col-12">
            <div class="empty-state">Todavía no hay proyectos públicos en ejecución para mostrar.</div>
          </div>
        @endforelse
      </div>

      <div class="project-cta-box">
        <div class="project-section-kicker">Siguiente paso</div>
        <h3 class="mb-3">¿Tiene un proyecto en mente?</h3>
        <p class="text-muted mb-0">Nuestros asesores técnicos están listos para analizar sus requerimientos. La solicitud inicial se gestiona por WhatsApp y luego el equipo administrativo prepara la cotización formal y el proyecto asociado.</p>
        @if(!empty($projectWhatsappBaseUrl))
          <div class="project-cta-actions">
            <a href="{{ $projectWhatsappBaseUrl . '&text=' . urlencode('Hola, quiero solicitar un presupuesto personalizado para un proyecto.') }}" target="_blank" rel="noopener noreferrer" class="btn btn-dark">Solicitar Presupuesto Personalizado</a>
            <a href="{{ $projectWhatsappBaseUrl . '&text=' . urlencode('Hola, quiero hablar con ventas sobre un proyecto.') }}" target="_blank" rel="noopener noreferrer" class="btn btn-outline-secondary">Contactar con Ventas</a>
          </div>
        @endif
      </div>
    </div>
  </section>
  @endif
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
            @if(!empty($whatsappUrl) || !empty($projectWhatsappUrl))
              <a href="{{ $tenantOffersProjects && !empty($projectWhatsappUrl) ? $projectWhatsappUrl : $whatsappUrl }}" target="_blank" class="contact-btn contact-btn-primary">
                <i class="bi bi-whatsapp"></i> {{ $tenantOffersProjects ? 'Solicitar cotización por WhatsApp' : 'Escríbenos por WhatsApp' }}
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
                    rel="noopener noreferrer"
                    class="text-dark fs-4">
                      <i class="bi bi-facebook"></i>
                  </a>
              @endif

              @if(!empty($tenant->tiktok))
                  <a href="{{ \Illuminate\Support\Str::startsWith((string) $tenant->tiktok, ['http://', 'https://']) ? $tenant->tiktok : 'https://www.tiktok.com/@' . ltrim((string) $tenant->tiktok, '@') }}"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="text-dark fs-4"
                    aria-label="TikTok">
                      <i class="bi bi-tiktok"></i>
                  </a>
              @endif

              @if(!empty($tenant->telegram))
                  <a href="https://t.me/{{ ltrim($tenant->telegram, '@') }}"
                    target="_blank"
                    rel="noopener noreferrer"
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
    const projectCategoryTabs = document.querySelectorAll('[data-project-category]');
    const projectCategoryCards = document.querySelectorAll('[data-project-category-card]');
    const projectCategoryTriggers = document.querySelectorAll('[data-project-category-trigger]');
    const products = document.querySelectorAll('.product-item');
    const packageItems = document.querySelectorAll('.package-item');
    const searchInput = document.getElementById('product-search');
    const productsCounter = document.getElementById('products-counter');
    const productsEmpty = document.getElementById('products-empty');
    const packagesEmpty = document.getElementById('packages-empty');
    let activeCategory = 'all';

    function sendCartCommand(type, detail = {}) {
      document.dispatchEvent(new CustomEvent('shopix-cart-command', {
        detail: { type, ...detail }
      }));
    }

    function openServiceFlow(button) {
      if (!button) {
        return;
      }

      const isFreePlan = String(button.dataset.serviceFreePlan || '0') === '1';
      const appointmentsEnabled = String(button.dataset.serviceAppointmentsEnabled || '0') === '1';
      const whatsappUrl = String(button.dataset.serviceWhatsappUrl || '').trim();
      const serviceId = Number(button.dataset.serviceId || 0);
      const serviceName = String(button.dataset.serviceName || 'Servicio').trim();

      if (isFreePlan) {
        if (!whatsappUrl) {
          alert('Esta tienda no tiene WhatsApp configurado para consultas de servicios.');
          return;
        }

        window.open(whatsappUrl, '_blank', 'noopener');
        return;
      }

      if (!appointmentsEnabled) {
        alert('La agenda de citas no está disponible en este momento para este servicio.');
        return;
      }

      if (serviceId <= 0) {
        alert('No se pudo abrir el flujo de cita para este servicio.');
        return;
      }

      sendCartCommand('open-appointment-service', {
        serviceId,
        serviceName,
      });
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

    function setActiveCategory(categoryId) {
      activeCategory = categoryId;

      categoryLinks.forEach(link => {
        const isActive = link.dataset.id === categoryId;
        link.classList.toggle('active', isActive);

        if (link.tagName === 'BUTTON') {
          link.setAttribute('aria-pressed', isActive ? 'true' : 'false');
        }
      });

      projectCategoryTabs.forEach(tab => {
        tab.classList.toggle('is-active', tab.dataset.projectCategory === categoryId);
      });
    }

    function filterProjectCategoryCards(categoryId) {
      projectCategoryCards.forEach(card => {
        const shouldShow = categoryId === 'all' || card.dataset.projectCategoryCard === categoryId;
        card.style.display = shouldShow ? '' : 'none';
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

    projectCategoryTabs.forEach(tab => {
      tab.addEventListener('click', () => {
        const categoryId = tab.dataset.projectCategory || 'all';
        setActiveCategory(categoryId);
        filterProjectCategoryCards(categoryId);
        applyCatalogFilters();
      });
    });

    projectCategoryTriggers.forEach(trigger => {
      trigger.addEventListener('click', () => {
        const categoryId = trigger.dataset.projectCategoryTrigger || 'all';
        setActiveCategory(categoryId);
        filterProjectCategoryCards(categoryId);
        applyCatalogFilters();

        const targetSection = document.getElementById('productos');
        targetSection?.scrollIntoView({ behavior: 'smooth', block: 'start' });
      });
    });

    if (searchInput) {
      searchInput.addEventListener('input', applyCatalogFilters);
    }

    document.querySelectorAll('.js-open-tenant-service').forEach((button) => {
      button.addEventListener('click', () => {
        openServiceFlow(button);
      });
    });

    setActiveCategory('all');
    filterProjectCategoryCards('all');
    applyCatalogFilters();
  </script>
  @include('partials.module-help-client-tour')
</body>
</html>
