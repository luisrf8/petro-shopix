<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <link rel="icon" type="image/png" href="{{ route('pwa.icon', ['size' => 192, 'variant' => 'client']) }}" />
  <title>{{ $tenant->name }} - Detalle de Producto</title>
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
    $tenantExternalUrl = trim((string) ($tenant->external_url ?? ''));
    if ($tenantExternalUrl !== '' && !\Illuminate\Support\Str::startsWith(\Illuminate\Support\Str::lower($tenantExternalUrl), ['http://', 'https://'])) {
      $tenantExternalUrl = 'https://' . $tenantExternalUrl;
    }

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
      font-family: 'SF Pro Text', 'Google Sans', 'Inter', sans-serif;
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
      box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
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
      box-shadow: 0 8px 22px rgba(15, 23, 42, 0.22);
    }

    .landing-header.is-scrolled .tenant-logo-chip {
      border-color: #d6e0ef !important;
      background: #ffffff !important;
    }

    .tenant-logo-image {
      width: 100px;
      height: 50px;
      object-fit: contain;
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

    footer.bg-dark {
      background: linear-gradient(135deg, var(--tenant-primary), var(--tenant-secondary)) !important;
      margin-top: auto;
    }

    .page-shell {
      padding-top: 6.2rem;
      flex: 1 0 auto;
    }

    .spotlight-card {
      background: #ffffff;
      border: 1px solid #e5e7eb;
      border-radius: 18px;
      padding: 1.25rem;
      box-shadow: 0 12px 24px rgba(15, 23, 42, 0.08);
    }

    .page-toolbar {
      max-width: 1180px;
      margin: 0 auto 1rem;
      display: flex;
      justify-content: flex-end;
      align-items: center;
      gap: 0.75rem;
    }

    .page-toolbar .btn {
      border-radius: 12px;
      padding-inline: 1rem;
    }

    .spotlight-kicker {
      font-size: 0.78rem;
      font-weight: 700;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      color: #64748b;
      margin-bottom: 0.35rem;
    }

    .spotlight-title {
      font-size: clamp(1.5rem, 4vw, 2.25rem);
      line-height: 1.12;
      margin-bottom: 0.35rem;
      color: var(--tenant-primary);
      font-weight: 700;
    }

    .spotlight-desc {
      color: #475569;
      margin-bottom: 0.75rem;
      font-size: 0.96rem;
    }

    .trust-pills {
      display: flex;
      flex-wrap: wrap;
      gap: 0.5rem;
    }

    .trust-pill {
      border: 1px solid rgba(var(--tenant-accent-rgb), 0.45);
      background: white;
      color: var(--tenant-primary);
      border-radius: 999px;
      font-size: 0.8rem;
      font-weight: 600;
      padding: 0.35rem 0.75rem;
    }

    .product-detail-card {
      max-width: min(1360px, calc(100vw - 32px));
      min-height: auto;
      margin: 12px auto 20px;
      padding: 20px;
      border-radius: 24px;
      border: 1px solid #e5e7eb;
      box-shadow: 0 20px 40px rgba(15, 23, 42, 0.1);
      background-color: #fff;
    }

    .product-detail-layout {
      display: grid;
      grid-template-columns: minmax(0, 1fr) minmax(320px, 420px);
      grid-template-areas:
        "gallery meta"
        "gallery content";
      gap: 1.1rem 1.2rem;
      align-items: start;
    }

    .product-gallery-shell {
      grid-area: gallery;
      display: grid;
      grid-template-columns: 78px minmax(0, 1fr);
      grid-template-areas: "status main";
      gap: 0.9rem;
      min-width: 0;
      align-items: start;
    }

    .product-meta-card {
      grid-area: meta;
    }

    .product-content-shell {
      grid-area: content;
      display: flex;
      flex-direction: column;
      gap: 0.75rem;
      min-width: 0;
    }

    .product-gallery-main {
      grid-area: main;
      position: relative;
      min-width: 0;
      width: 100%;
      max-width: 680px;
      justify-self: start;
    }

    .product-gallery-track {
      display: flex;
      gap: 0.75rem;
      overflow-x: auto;
      scroll-snap-type: x mandatory;
      scroll-behavior: smooth;
      scrollbar-width: none;
      -ms-overflow-style: none;
      padding-bottom: 0;
    }

    .product-gallery-track::-webkit-scrollbar {
      display: none;
    }

    .product-gallery-slide {
      flex: 0 0 100%;
      scroll-snap-align: center;
      border: 1px solid #e5e7eb;
      border-radius: 18px;
      overflow: hidden;
      background: #f8fafc;
      aspect-ratio: 1 / 1;
      display: flex;
      align-items: center;
      justify-content: center;
      position: relative;
      max-height: min(68vh, 640px);
    }

    .product-gallery-slide img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }

    .product-gallery-arrow {
      position: absolute;
      top: 50%;
      transform: translateY(-50%);
      width: 42px;
      height: 42px;
      border-radius: 999px;
      border: 1px solid rgba(255, 255, 255, 0.78);
      background: rgba(15, 23, 42, 0.58);
      color: #fff;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      z-index: 2;
      backdrop-filter: blur(8px);
    }

    .product-gallery-arrow:hover,
    .product-gallery-arrow:focus {
      background: rgba(15, 23, 42, 0.78);
      color: #fff;
    }

    .product-gallery-arrow.prev {
      left: 0.75rem;
    }

    .product-gallery-arrow.next {
      right: 0.75rem;
    }

    .product-gallery-fullscreen-btn {
      position: absolute;
      top: 0.85rem;
      right: 0.85rem;
      width: 42px;
      height: 42px;
      border-radius: 999px;
      border: 1px solid rgba(255, 255, 255, 0.82);
      background: rgba(15, 23, 42, 0.62);
      color: #fff;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      z-index: 2;
      backdrop-filter: blur(8px);
    }

    .product-gallery-fullscreen-btn:hover,
    .product-gallery-fullscreen-btn:focus {
      background: rgba(15, 23, 42, 0.82);
      color: #fff;
    }

    .product-gallery-status {
      grid-area: status;
      display: flex;
      flex-direction: column;
      align-items: stretch;
      justify-content: flex-start;
      gap: 0.55rem;
      width: 78px;
    }

    .product-gallery-counter {
      border-radius: 999px;
      background: #111827;
      color: #fff;
      font-size: 0.78rem;
      font-weight: 700;
      padding: 0.28rem 0.68rem;
      line-height: 1;
      white-space: nowrap;
    }

    .product-gallery-thumbs {
      display: flex;
      flex-direction: column;
      gap: 0.55rem;
      overflow-y: auto;
      overflow-x: hidden;
      scrollbar-width: none;
      -ms-overflow-style: none;
      padding-bottom: 0;
      max-height: min(68vh, 640px);
    }

    .product-gallery-thumbs::-webkit-scrollbar {
      display: none;
    }

    .product-gallery-fullscreen-image {
      width: 100%;
      max-height: 82vh;
      object-fit: contain;
      border-radius: 18px;
      background: #fff;
    }

    .product-gallery-fullscreen-stage {
      position: relative;
      width: min(100%, 1180px);
      margin: 0 auto;
      display: flex;
      align-items: center;
      justify-content: center;
      min-height: calc(100vh - 170px);
      padding: 1rem 4.5rem 0;
    }

    .product-gallery-fullscreen-arrow {
      position: absolute;
      top: 50%;
      transform: translateY(-50%);
      width: 52px;
      height: 52px;
      border: 0;
      border-radius: 999px;
      background: rgba(15, 23, 42, 0.9);
      color: #fff;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      box-shadow: 0 18px 30px -18px rgba(15, 23, 42, 0.7);
      z-index: 2;
    }

    .product-gallery-fullscreen-arrow.prev {
      left: 0.75rem;
    }

    .product-gallery-fullscreen-arrow.next {
      right: 0.75rem;
    }

    .product-gallery-fullscreen-arrow:hover,
    .product-gallery-fullscreen-arrow:focus {
      background: #0f172a;
      color: #fff;
    }

    .product-gallery-fullscreen-meta {
      display: flex;
      align-items: center;
      justify-content: center;
      gap: 0.75rem;
      margin-top: 0.85rem;
      flex-wrap: wrap;
    }

    .product-gallery-fullscreen-counter {
      border-radius: 999px;
      background: #111827;
      color: #fff;
      font-size: 0.84rem;
      font-weight: 700;
      padding: 0.38rem 0.82rem;
      line-height: 1;
    }

    .product-gallery-fullscreen-thumbs {
      display: flex;
      gap: 0.7rem;
      overflow-x: auto;
      padding: 0.25rem 0.1rem 0.1rem;
      scrollbar-width: none;
      max-width: min(100%, 760px);
    }

    .product-gallery-fullscreen-thumbs::-webkit-scrollbar {
      display: none;
    }

    .product-gallery-fullscreen-thumb {
      width: 72px;
      height: 72px;
      border-radius: 16px;
      object-fit: cover;
      border: 2px solid transparent;
      cursor: pointer;
      flex: 0 0 auto;
      background: #fff;
      transition: transform 0.2s ease, border-color 0.2s ease, box-shadow 0.2s ease;
    }

    .product-gallery-fullscreen-thumb.active {
      border-color: #111827;
      box-shadow: 0 18px 28px -20px rgba(15, 23, 42, 0.7);
      transform: translateY(-2px);
    }

    .product-meta-card {
      border: 1px solid #e2e8f0;
      border-radius: 18px;
      background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
      padding: 0.9rem 1rem;
      margin-bottom: 0;
      box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.7);
    }

    .product-meta-subtitle {
      color: #64748b;
      margin-bottom: 0;
      line-height: 1.55;
    }

    .variant-section-card {
      border: 1px solid #e2e8f0;
      border-radius: 18px;
      background: #ffffff;
      padding: 0.8rem 0.9rem;
    }

    .variant-header {
      display: flex;
      justify-content: space-between;
      align-items: center;
      margin-bottom: 0.45rem;
    }

    .secure-box {
      border: 1px solid #dbe3ee;
      border-radius: 16px;
      background: linear-gradient(180deg, #f8fafc 0%, #f1f5f9 100%);
      padding: 0.95rem 1rem;
      margin-top: 0;
      color: #334155;
      font-size: 0.9rem;
    }

    .secure-box .item {
      display: flex;
      align-items: center;
      gap: 0.45rem;
      margin-bottom: 0.45rem;
    }

    .secure-box .item:last-child {
      margin-bottom: 0;
    }

    .thumbnail-image {
      width: 74px;
      height: 74px;
      object-fit: cover;
      border: 2px solid #d1d5db;
      border-radius: 12px;
      cursor: pointer;
      transition: border-color 0.2s, transform 0.2s ease;
      flex: 0 0 auto;
    }

    .thumbnail-image:hover,
    .thumbnail-image.active {
      border-color: var(--tenant-primary);
      transform: translateY(-1px);
    }

    .variant-item {
      list-style-type: disc;
      margin-left: 20px;
      color: #333;
    }
    
    .variant-price {
        font-weight: 700;
        color: #1a1a1a;
    }

    .variant-button {
        cursor: pointer;
        transition: background-color 0.2s, border-color 0.2s, color 0.2s;
        padding: 8px 12px;
        margin-right: 8px;
        margin-bottom: 8px;
      border: 1px solid #d1d5db;
      border-radius: 10px;
        background-color: #fff;
      color: #111827;
        font-weight: 500;
        display: inline-block;
        min-width: 120px;
    }

    .variant-button.selected {
      background: linear-gradient(135deg, var(--tenant-primary), var(--tenant-secondary));
      border-color: var(--tenant-primary);
      color: #fff;
    }

    .variant-button.selected .text-muted {
      color: rgba(255, 255, 255, 0.85) !important;
    }

    .variant-button:disabled {
        background-color: #f8f9fa;
        border-color: #eee;
        color: #999;
        cursor: not-allowed;
    }

    .variant-grid {
      display: grid;
      grid-template-columns: repeat(auto-fit, minmax(110px, 1fr));
      gap: 0.5rem;
      margin-bottom: 0.2rem;
    }

    .variant-button {
      position: relative;
      min-width: 0;
      margin-right: 0;
      margin-bottom: 0;
      border: 1px solid #d8e1ee;
      border-radius: 14px;
      padding: 0.55rem;
      background: linear-gradient(165deg, #ffffff 0%, #f8fbff 100%);
      box-shadow: 0 6px 14px rgba(15, 23, 42, 0.06);
    }

    .variant-button:hover {
      transform: translateY(-1px);
      border-color: rgba(var(--tenant-accent-rgb), 0.48);
      box-shadow: 0 12px 22px rgba(15, 23, 42, 0.1);
    }

    .variant-chip-top {
      display: flex;
      align-items: center;
      justify-content: space-between;
      gap: 0.5rem;
      margin-bottom: 0.4rem;
    }

    .variant-media {
      width: 54px;
      height: 54px;
      border-radius: 10px;
      border: 1px solid #d5dbe5;
      background: #fff;
      overflow: hidden;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      flex-shrink: 0;
    }

    .variant-media img {
      width: 100%;
      height: 100%;
      object-fit: cover;
      display: block;
    }

    .variant-meta {
      display: flex;
      flex-direction: column;
      align-items: flex-start;
      gap: 0.1rem;
      line-height: 1.2;
    }

    .variant-price-row {
      margin-top: 0.18rem;
      display: flex;
      align-items: baseline;
      gap: 0.25rem;
      font-size: 0.84rem;
    }

    .variant-button.selected {
      border-color: rgba(var(--tenant-primary-rgb), 0.68);
      background: linear-gradient(145deg, rgba(var(--tenant-primary-rgb), 0.95), rgba(var(--tenant-secondary-rgb), 0.95));
      box-shadow: 0 14px 22px rgba(var(--tenant-primary-rgb), 0.28);
      color: #fff;
    }

    .variant-button.selected .variant-media {
      border-color: rgba(255, 255, 255, 0.7);
      box-shadow: 0 0 0 2px rgba(255, 255, 255, 0.2);
    }

    .variant-button.selected .text-muted,
    .variant-button.selected .variant-discount {
      color: rgba(255, 255, 255, 0.85) !important;
    }

    .variant-discount {
      color: #0f8a49;
      font-size: 0.72rem;
      font-weight: 700;
    }

    .variant-preview-pill {
      border: 1px solid #dbe4f0;
      border-radius: 999px;
      background: #fff;
      color: #334155;
      font-size: 0.8rem;
      font-weight: 600;
      padding: 0.35rem 0.72rem;
      display: inline-flex;
      align-items: center;
      gap: 0.35rem;
      margin-bottom: 0.65rem;
    }

    .variant-preview-pill strong {
      color: var(--tenant-primary);
    }

    .product-gallery-thumbs {
      order: 1;
      margin-right: 0;
    }

    .product-gallery-main {
      order: 2;
      min-width: 0;
    }

    .detail-actions {
      display: flex;
      flex-wrap: wrap;
      justify-content: flex-start;
      gap: 0.65rem;
      padding-top: 0.7rem;
      border-top: 1px solid #e5e7eb;
    }

    .detail-actions .btn {
      min-width: 0;
      flex: 1 1 180px;
    }

    .variant-badge {
      border-radius: 999px;
      padding: 0.45rem 0.8rem;
    }

    @media (max-width: 991.98px) {
      .landing-header {
        background: transparent;
      }

      .product-gallery-fullscreen-stage {
        min-height: calc(100vh - 150px);
        padding: 0.25rem 2.8rem 0;
      }

      .product-gallery-fullscreen-arrow {
        width: 42px;
        height: 42px;
      }

      .product-gallery-fullscreen-arrow.prev {
        left: 0.25rem;
      }

      .product-gallery-fullscreen-arrow.next {
        right: 0.25rem;
      }

      .product-gallery-fullscreen-image {
        max-height: 68vh;
      }

      .product-gallery-fullscreen-thumb {
        width: 60px;
        height: 60px;
        border-radius: 14px;
      }

      .landing-header.is-scrolled {
        background: rgba(255, 255, 255, 0.95);
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
        border: 1px solid #dbe4f0;
        border-radius: 14px;
        background: rgba(255, 255, 255, 0.97);
        box-shadow: 0 12px 26px rgba(15, 23, 42, 0.1);
      }

      .product-detail-card {
        margin-top: 25px auto;
      }

      .page-toolbar {
        max-width: 95vw;
      }

      .product-detail-layout {
        grid-template-columns: 1fr;
        grid-template-areas:
          "meta"
          "gallery"
          "content";
        gap: 0rem;
      }

      .product-gallery-shell {
        gap: 0.45rem;
      }

      .product-content-shell {
        display: flex;
        flex-direction: column;
        gap: 0.85rem;
      }

      .spotlight-card {
        padding: 1rem;
      }

      .spotlight-title {
        font-size: clamp(1.25rem, 6vw, 1.8rem);
      }

      .page-toolbar {
        justify-content: stretch;
      }

      .page-toolbar .btn {
        width: 100%;
      }

      .product-detail-card {
        max-width: 100%;
        margin: 0;
        padding: 12px 12px 18px;
        border-radius: 0;
        min-height: auto;
        border-left: 0;
        border-right: 0;
      }

      .variant-section-card,
      .secure-box {
        padding: 0;
        margin: 0;
      }

      .product-meta-card {
        padding: 0.85rem 0.95rem;
        margin-bottom: 0;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        box-shadow: none;
      }

      .product-meta-card .h4 {
        font-size: 1rem;
        line-height: 1.15;
      }

      .product-meta-subtitle {
        font-size: 0.78rem;
        line-height: 1.3;
      }

      .product-gallery-shell {
        grid-template-columns: minmax(0, 1fr);
        grid-template-areas:
          "main"
          "status";
        gap: 0.55rem;
      }

      .product-gallery-main {
        max-width: none;
        width: 100%;
      }

      .product-gallery-status {
        width: 100%;
        justify-content: center;
        align-items: center;
        min-height: 0;
      }

      .product-gallery-thumbs {
        display: none !important;
      }

      .product-gallery-slide {
        border-radius: 16px;
      }

      .product-gallery-counter {
        position: static;
        padding: 0.34rem 0.62rem;
        box-shadow: none;
      }

      .product-gallery-arrow {
        width: 38px;
        height: 38px;
      }

      .product-gallery-arrow.prev {
        left: 0.5rem;
      }

      .product-gallery-arrow.next {
        right: 0.5rem;
      }

      .thumbnail-image {
        width: 54px;
        height: 54px;
        border-radius: 10px;
      }

      .product-gallery-thumbs {
        gap: 0.45rem;
        padding-right: 0.1rem;
      }

      .variant-grid {
        display: flex;
        gap: 0.55rem;
        overflow-x: auto;
        scroll-snap-type: x proximity;
        padding-bottom: 0.25rem;
        margin-inline: -0.1rem;
      }

      .variant-button {
        flex: 0 0 132px;
        scroll-snap-align: start;
      }

      .variant-chip-top {
        margin-bottom: 0.32rem;
      }

      .variant-media {
        width: 48px;
        height: 48px;
      }

      .variant-header {
        margin-bottom: 0.55rem;
      }

      .variant-section-card {
        margin-top: 0;
      }

      .variant-preview-pill {
        margin-bottom: 0.5rem;
        padding: 0.32rem 0.62rem;
      }

      .detail-actions {
        position: sticky;
        bottom: 0;
        background: rgba(255, 255, 255, 0.96);
        backdrop-filter: blur(10px);
        padding: 0.75rem 0 0;
      }

      .detail-actions .btn {
        width: 100%;
      }
    }
  </style>
</head>

<body>
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
            @if(!empty($tenantExternalUrl))
              <li class="nav-item">
                <a class="btn landing-nav-link tenant-main-nav-btn" href="{{ $tenantExternalUrl }}" target="_blank" rel="noopener noreferrer"><i class="bi bi-box-arrow-up-right"></i> Sitio oficial</a>
              </li>
            @endif
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
              <a class="btn landing-nav-link tenant-main-nav-btn" href="{{ route('tenant.public.categories', ['tenant' => $tenant->slug]) }}"><i class="bi bi-arrow-left"></i> Volver</a>
            </li>
          </ul>
        </div>
      </nav>
    </div>
  </header>
  <section class="py-10 section-muted page-shell">
      <div class="product-detail-card">
        <div class="product-detail-layout">
          <div class="product-meta-card">
            <div class="d-flex align-items-center justify-content-between gap-2 flex-wrap mb-2">
              <h2 class="h4 fw-bold mb-0">{{ $product->name }}</h2>
            </div>
            <p class="product-meta-subtitle">{{ $product->description }}</p>
          </div>

          <div class="product-gallery-shell">
            <div class="product-gallery-main flex-grow-1">
              <button type="button" class="btn product-gallery-fullscreen-btn" id="product-gallery-fullscreen" aria-label="Ver imagen en pantalla completa">
                <i class="bi bi-arrows-fullscreen"></i>
              </button>
              <button type="button" class="btn product-gallery-arrow prev" id="product-gallery-prev" aria-label="Imagen anterior">
                <i class="bi bi-chevron-left"></i>
              </button>
              <div class="product-gallery-track" id="product-gallery-track">
                @if(count($product->images) > 0)
                  @foreach($product->images as $index => $image)
                    <div class="product-gallery-slide" data-gallery-slide data-index="{{ $index }}" data-image-src="{{ \App\Support\ImageStorage::url($image->path) ?? asset('assets/img/shopix5.png') }}">
                      <img
                        src="{{ \App\Support\ImageStorage::url($image->path) ?? asset('assets/img/shopix5.png') }}"
                        alt="Imagen {{ $index + 1 }} de {{ $product->name }}"
                      >
                    </div>
                  @endforeach
                @else
                  <div class="product-gallery-slide" data-gallery-slide data-index="0" data-image-src="{{ asset('assets/img/shopix5.png') }}">
                    <div class="d-flex align-items-center justify-content-center w-100 h-100 bg-light">
                      <i class="bi bi-image text-muted fs-1"></i>
                    </div>
                  </div>
                @endif
              </div>
              <button type="button" class="btn product-gallery-arrow next" id="product-gallery-next" aria-label="Imagen siguiente">
                <i class="bi bi-chevron-right"></i>
              </button>
            </div>

            <div class="product-gallery-status d-none d-md-flex">
              <div class="product-gallery-thumbs" id="thumbnail-gallery">
                @if(count($product->images) > 0)
                  @foreach($product->images as $index => $image)
                    <img
                      src="{{ \App\Support\ImageStorage::url($image->path) ?? asset('assets/img/shopix5.png') }}"
                      alt="Miniatura {{ $index + 1 }}"
                      class="thumbnail-image {{ $index === 0 ? 'active' : '' }}"
                      data-main-src="{{ \App\Support\ImageStorage::url($image->path) ?? asset('assets/img/shopix5.png') }}"
                      data-gallery-index="{{ $index }}"
                    >
                  @endforeach
                @endif
              </div>
            </div>

            <div class="modal fade" id="productGalleryFullscreenModal" tabindex="-1" aria-labelledby="productGalleryFullscreenModalLabel" aria-hidden="true">
              <div class="modal-dialog modal-fullscreen modal-dialog-centered">
                <div class="modal-content border-0 bg-white">
                  <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title" id="productGalleryFullscreenModalLabel">Vista completa</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                  </div>
                  <div class="modal-body pt-2">
                    <div class="product-gallery-fullscreen-stage" id="product-gallery-fullscreen-stage">
                      <button type="button" class="product-gallery-fullscreen-arrow prev" id="product-gallery-fullscreen-prev" aria-label="Imagen anterior">
                        <i class="bi bi-chevron-left"></i>
                      </button>
                      <img id="product-gallery-fullscreen-image" src="" alt="Imagen ampliada del producto" class="product-gallery-fullscreen-image">
                      <button type="button" class="product-gallery-fullscreen-arrow next" id="product-gallery-fullscreen-next" aria-label="Imagen siguiente">
                        <i class="bi bi-chevron-right"></i>
                      </button>
                    </div>
                    <div class="product-gallery-fullscreen-meta">
                      <div class="product-gallery-fullscreen-counter" id="product-gallery-fullscreen-counter">1/1</div>
                      <div class="product-gallery-fullscreen-thumbs" id="product-gallery-fullscreen-thumbs">
                        @if(count($product->images) > 0)
                          @foreach($product->images as $index => $image)
                            <img
                              src="{{ \App\Support\ImageStorage::url($image->path) ?? asset('assets/img/shopix5.png') }}"
                              alt="Miniatura ampliada {{ $index + 1 }}"
                              class="product-gallery-fullscreen-thumb {{ $index === 0 ? 'active' : '' }}"
                              data-fullscreen-gallery-index="{{ $index }}"
                            >
                          @endforeach
                        @else
                          <img
                            src="{{ asset('assets/img/shopix5.png') }}"
                            alt="Miniatura ampliada"
                            class="product-gallery-fullscreen-thumb active"
                            data-fullscreen-gallery-index="0"
                          >
                        @endif
                      </div>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="product-content-shell">
            <div class="variant-section-card">
              <div class="variant-header">
                <h5 class="fw-semibold mb-0 d-none d-md-block">Selecciona una variante</h5>
              </div>
              <div id="selected-variant-indicator" class="variant-preview-pill d-none">
                <i class="bi bi-stars"></i>
                <span>Seleccionado: <strong id="selected-variant-label">-</strong></span>
              </div>

              <div id="variants-container" class="variant-grid">
                  @forelse ($product->variants as $variant)
                  @php
                    $productDiscount = (float) ($product->discount_percentage ?? 0);
                    $variantDiscount = (float) ($variant->discount_percentage ?? 0);
                    $effectiveVariantPrice = (float) $variant->price * ((100 - $productDiscount) / 100) * ((100 - $variantDiscount) / 100);
                    $effectiveVariantPriceBs = $showBsPrices ? $effectiveVariantPrice * $storefrontBsRate : null;
                    $variantImage = optional($variant->images->first())->path;
                    $variantImageUrl = $variantImage ? (\App\Support\ImageStorage::url($variantImage) ?? asset('assets/img/shopix5.png')) : (isset($product->images[0]) ? (\App\Support\ImageStorage::url($product->images[0]->path) ?? asset('assets/img/shopix5.png')) : asset('assets/img/shopix5.png'));
                  @endphp
                      <div 
                          class="variant-button"
                          data-variant-id="{{ $variant->id }}"
                          data-size="{{ $variant->size }}"
                          data-price="{{ number_format($effectiveVariantPrice, 2, '.', '') }}"
                          data-price-bs="{{ !is_null($effectiveVariantPriceBs) ? number_format($effectiveVariantPriceBs, 2, '.', '') : '' }}"
                          data-stock="{{ $variant->stock }}"
                          data-product-name="{{ $product->name }}"
                          data-image-src="{{ $variantImageUrl }}"
                          {{ $variant->stock <= 0 ? 'disabled' : '' }}
                      >
                          <div class="variant-chip-top">
                            <div class="variant-media">
                              <img src="{{ $variantImageUrl }}" alt="Variante {{ $variant->size }}">
                            </div>
                            @if ($variant->stock <= 0)
                              <span class="badge bg-danger">Agotado</span>
                            @else
                              <span class="badge text-bg-light border">{{ $variant->stock }} disp.</span>
                            @endif
                          </div>
                          <div class="variant-meta">
                            <span class="fw-semibold">{{ $variant->size }}</span>
                            <div class="variant-price-row">
                              <span>{{ number_format($effectiveVariantPrice, 2) }}</span>
                              <span class="text-muted small">{{ $baseCurrencySymbol }}</span>
                            </div>
                            @if(!is_null($effectiveVariantPriceBs))
                              <small class="text-muted d-block">Bs {{ number_format($effectiveVariantPriceBs, 2) }}</small>
                            @endif
                          </div>
                    @if($productDiscount > 0 || $variantDiscount > 0)
                      <small class="variant-discount d-block mt-1">Desc: {{ number_format($productDiscount + $variantDiscount, 2) }}%</small>
                    @endif
                      </div>
                  @empty
                      <p class="text-muted">No hay variantes disponibles.</p>
                  @endforelse
              </div>
            </div>

            @if($cartEnabled)
              <div class="detail-actions">
                <button
                  id="add-to-cart-button"
                  class="btn btn-primary btn-lg"
                  disabled
                >
                  <i class="bi bi-cart-plus me-2"></i> Agregar al carrito
                </button>
              </div>
            @else
              <div class="detail-actions">
                <button
                  id="whatsapp-button"
                  class="btn btn-success btn-lg"
                  disabled
                >
                  <i class="bi bi-whatsapp me-2"></i> Comunicarme por WhatsApp por este producto
                </button>
              </div>
            @endif
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
    document.addEventListener('DOMContentLoaded', () => {
        const landingHeader = document.querySelector('.landing-header');
        const navLinks = document.querySelectorAll('#landingNavbar .nav-link, #landingNavbar .btn');
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

        navLinks.forEach(link => {
          link.addEventListener('click', () => {
            if (window.innerWidth < 992 && navbarCollapse.classList.contains('show') && bsCollapse) {
              bsCollapse.hide();
            }
          });
        });

        const galleryTrack = document.getElementById('product-gallery-track');
        const gallerySlides = Array.from(document.querySelectorAll('[data-gallery-slide]'));
        const galleryPrevButton = document.getElementById('product-gallery-prev');
        const galleryNextButton = document.getElementById('product-gallery-next');
        const galleryCounter = document.getElementById('product-gallery-counter');
        const thumbnailGallery = document.getElementById('thumbnail-gallery');
        const thumbnails = document.querySelectorAll('.thumbnail-image');
        const fullscreenButton = document.getElementById('product-gallery-fullscreen');
        const fullscreenModalElement = document.getElementById('productGalleryFullscreenModal');
        const fullscreenImage = document.getElementById('product-gallery-fullscreen-image');
        const fullscreenPrevButton = document.getElementById('product-gallery-fullscreen-prev');
        const fullscreenNextButton = document.getElementById('product-gallery-fullscreen-next');
        const fullscreenCounter = document.getElementById('product-gallery-fullscreen-counter');
        const fullscreenThumbsContainer = document.getElementById('product-gallery-fullscreen-thumbs');
        const fullscreenThumbs = Array.from(document.querySelectorAll('[data-fullscreen-gallery-index]'));
        const fullscreenStage = document.getElementById('product-gallery-fullscreen-stage');
        let currentGalleryIndex = 0;
        let fullscreenTouchStartX = null;

        function syncVariantPrimaryImage(imageSrc, altText) {
          if (!imageSrc || !gallerySlides.length) {
            return;
          }

          const firstSlide = gallerySlides[0];
          const firstSlideImage = firstSlide?.querySelector('img');
          if (firstSlide) {
            firstSlide.dataset.imageSrc = imageSrc;
          }
          if (firstSlideImage) {
            firstSlideImage.src = imageSrc;
            firstSlideImage.alt = altText || firstSlideImage.alt;
          }

          const firstThumb = thumbnailGallery?.querySelector('.thumbnail-image');
          if (firstThumb) {
            firstThumb.src = imageSrc;
            firstThumb.dataset.mainSrc = imageSrc;
            firstThumb.alt = altText || firstThumb.alt;
          }

          const firstFullscreenThumb = fullscreenThumbsContainer?.querySelector('[data-fullscreen-gallery-index="0"]');
          if (firstFullscreenThumb) {
            firstFullscreenThumb.src = imageSrc;
            firstFullscreenThumb.alt = altText || firstFullscreenThumb.alt;
          }

          if (fullscreenImage && currentGalleryIndex === 0) {
            fullscreenImage.src = imageSrc;
            fullscreenImage.alt = altText || fullscreenImage.alt;
          }

          scrollToGalleryIndex(0);
        }

        function updateGalleryUi(index) {
          currentGalleryIndex = Math.max(0, Math.min(index, Math.max(gallerySlides.length - 1, 0)));

          thumbnails.forEach(thumbnail => {
            thumbnail.classList.toggle('active', Number(thumbnail.dataset.galleryIndex) === currentGalleryIndex);
          });

          if (galleryCounter) {
            galleryCounter.textContent = `${currentGalleryIndex + 1}/${Math.max(gallerySlides.length, 1)}`;
          }
        }

        function scrollToGalleryIndex(index) {
          const target = gallerySlides[index];
          if (!target || !galleryTrack) {
            return;
          }

          target.scrollIntoView({ behavior: 'smooth', inline: 'start', block: 'nearest' });
          updateGalleryUi(index);
        }

        function openGalleryFullscreen(index = currentGalleryIndex) {
          const target = gallerySlides[index];
          const targetImage = target?.querySelector('img');
          const imageSrc = targetImage?.getAttribute('src') || target?.dataset.imageSrc || '';

          if (!fullscreenModalElement || !fullscreenImage || !imageSrc || typeof bootstrap === 'undefined' || !bootstrap?.Modal) {
            return;
          }

          currentGalleryIndex = Math.max(0, Math.min(index, Math.max(gallerySlides.length - 1, 0)));
          fullscreenImage.src = imageSrc;
          fullscreenImage.alt = targetImage?.getAttribute('alt') || 'Imagen ampliada del producto';
          if (fullscreenCounter) {
            fullscreenCounter.textContent = `${currentGalleryIndex + 1}/${Math.max(gallerySlides.length, 1)}`;
          }
          fullscreenThumbs.forEach((thumb) => {
            const thumbIndex = Number(thumb.dataset.fullscreenGalleryIndex || 0);
            const isActive = thumbIndex === currentGalleryIndex;
            thumb.classList.toggle('active', isActive);
            if (isActive) {
              thumb.scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
            }
          });
          if (fullscreenPrevButton) {
            fullscreenPrevButton.disabled = currentGalleryIndex <= 0;
          }
          if (fullscreenNextButton) {
            fullscreenNextButton.disabled = currentGalleryIndex >= gallerySlides.length - 1;
          }
          bootstrap.Modal.getOrCreateInstance(fullscreenModalElement).show();
        }

        function moveFullscreenGallery(step) {
          const nextIndex = Math.max(0, Math.min(currentGalleryIndex + step, gallerySlides.length - 1));
          if (nextIndex === currentGalleryIndex) {
            return;
          }

          openGalleryFullscreen(nextIndex);
        }

        if (galleryTrack && gallerySlides.length > 0) {
            thumbnails.forEach(thumbnail => {
                thumbnail.addEventListener('click', () => {
                    scrollToGalleryIndex(Number(thumbnail.dataset.galleryIndex || 0));
                });
            });

            galleryPrevButton?.addEventListener('click', () => {
              scrollToGalleryIndex(Math.max(currentGalleryIndex - 1, 0));
            });

            galleryNextButton?.addEventListener('click', () => {
              scrollToGalleryIndex(Math.min(currentGalleryIndex + 1, gallerySlides.length - 1));
            });

            fullscreenButton?.addEventListener('click', () => {
              openGalleryFullscreen(currentGalleryIndex);
            });

            fullscreenPrevButton?.addEventListener('click', () => {
              moveFullscreenGallery(-1);
            });

            fullscreenNextButton?.addEventListener('click', () => {
              moveFullscreenGallery(1);
            });

            fullscreenThumbs.forEach((thumb) => {
              thumb.addEventListener('click', () => {
                openGalleryFullscreen(Number(thumb.dataset.fullscreenGalleryIndex || 0));
              });
            });

            gallerySlides.forEach((slide, index) => {
              slide.addEventListener('click', () => {
                openGalleryFullscreen(index);
              });
            });

            fullscreenModalElement?.addEventListener('keydown', (event) => {
              if (event.key === 'ArrowLeft') {
                event.preventDefault();
                moveFullscreenGallery(-1);
              } else if (event.key === 'ArrowRight') {
                event.preventDefault();
                moveFullscreenGallery(1);
              }
            });

            fullscreenStage?.addEventListener('touchstart', (event) => {
              fullscreenTouchStartX = event.changedTouches?.[0]?.clientX ?? null;
            }, { passive: true });

            fullscreenStage?.addEventListener('touchend', (event) => {
              const endX = event.changedTouches?.[0]?.clientX ?? null;
              if (fullscreenTouchStartX === null || endX === null) {
                fullscreenTouchStartX = null;
                return;
              }

              const deltaX = endX - fullscreenTouchStartX;
              fullscreenTouchStartX = null;

              if (Math.abs(deltaX) < 35) {
                return;
              }

              if (deltaX > 0) {
                moveFullscreenGallery(-1);
              } else {
                moveFullscreenGallery(1);
              }
            }, { passive: true });

            galleryTrack.addEventListener('scroll', () => {
              const trackCenter = galleryTrack.scrollLeft + (galleryTrack.clientWidth / 2);
              let nearestIndex = 0;
              let nearestDistance = Number.POSITIVE_INFINITY;

              gallerySlides.forEach((slide, index) => {
                const slideCenter = slide.offsetLeft + (slide.clientWidth / 2);
                const distance = Math.abs(slideCenter - trackCenter);
                if (distance < nearestDistance) {
                  nearestDistance = distance;
                  nearestIndex = index;
                }
              });

              updateGalleryUi(nearestIndex);
            }, { passive: true });

            updateGalleryUi(0);
        }
        const variantButtons = document.querySelectorAll('.variant-button:not([disabled])');
        const cartEnabled = @json((bool) ($cartEnabled ?? false));
        const addToCartButton = document.getElementById('add-to-cart-button');
        const openCartButton = document.getElementById('open-cart-button');
        const whatsappButton = document.getElementById('whatsapp-button');
        const selectedVariantIndicator = document.getElementById('selected-variant-indicator');
        const selectedVariantLabel = document.getElementById('selected-variant-label');
        let selectedVariant = null;
        const tenantSlug = @json($tenant->slug);
        const baseCurrencySymbol = @json($baseCurrencySymbol ?? '$');
        const showBsPrices = @json((bool) ($showBsPrices ?? false));

        const tenantPhoneCode = @json($tenant->phone_code ?? '');
        const tenantPhoneNumber = @json($tenant->phone_number ?? '');
        const shopixDebug = true;

        function cartDebug(...args) {
          if (!shopixDebug) return;
          console.log('[ShopixCart Debug][Product]', ...args);
        }

        function sendCartCommand(type, detail = {}) {
          cartDebug('sendCartCommand', { type, detail });
          document.dispatchEvent(new CustomEvent('shopix-cart-command', {
            detail: { type, ...detail }
          }));
        }

        function openCartSimple() {
          sendCartCommand('open-cart');
        }

        // --- Lógica de selección de variantes ---
        variantButtons.forEach(button => {
            button.addEventListener('click', () => {
                // 1. Desactivar todos los botones
                variantButtons.forEach(btn => btn.classList.remove('selected'));

                // 2. Activar el botón seleccionado
                button.classList.add('selected');
                
                // 3. Almacenar la variante seleccionada
                selectedVariant = {
                    variantId: button.dataset.variantId,
                    size: button.dataset.size,
                    price: button.dataset.price,
                  priceBs: button.dataset.priceBs || '',
                  productName: button.dataset.productName,
                  productId: @json($product->id),
                  imageSrc: button.dataset.imageSrc || null,
                };

                if (selectedVariant.imageSrc) {
                  const matchingThumb = Array.from(thumbnails).find(t => t.dataset.mainSrc === selectedVariant.imageSrc);
                  if (matchingThumb) {
                    scrollToGalleryIndex(Number(matchingThumb.dataset.galleryIndex || 0));
                  } else {
                    syncVariantPrimaryImage(selectedVariant.imageSrc, `Imagen de la variante ${selectedVariant.size} de ${selectedVariant.productName}`);
                  }
                }

                if (selectedVariantIndicator && selectedVariantLabel) {
                  const bsSuffix = showBsPrices && selectedVariant.priceBs ? ` · Bs ${selectedVariant.priceBs}` : '';
                  selectedVariantLabel.textContent = `${selectedVariant.size} (${selectedVariant.price} ${baseCurrencySymbol}${bsSuffix})`;
                  selectedVariantIndicator.classList.remove('d-none');
                }

                cartDebug('variant:selected', selectedVariant);

                if (cartEnabled && addToCartButton) {
                  addToCartButton.disabled = false;
                }

                if (!cartEnabled && whatsappButton) {
                  whatsappButton.disabled = false;
                }
            });
        });

        if (cartEnabled && addToCartButton) {
          addToCartButton.addEventListener('click', () => {
              cartDebug('add-click:triggered', {
                selectedVariant,
                buttonDisabled: addToCartButton.disabled,
              });

              if (!selectedVariant) {
                  cartDebug('add-click:aborted-no-variant');
                  alert('Por favor, selecciona una variante primero.');
                  return;
              }

              const payload = {
                variantId: selectedVariant.variantId,
                productId: selectedVariant.productId,
                productName: selectedVariant.productName,
                variantSize: selectedVariant.size,
                imageSrc: selectedVariant.imageSrc || null,
                price: Number(selectedVariant.price),
                qty: 1
              };

              cartDebug('add-click:payload', payload);
              sendCartCommand('add-item', { item: payload });

              const storageKey = `shopix_cart_${tenantSlug}`;
              try {
                const persisted = JSON.parse(localStorage.getItem(storageKey) || '[]');
                cartDebug('add-click:post-persist-cart', persisted);
              } catch (error) {
                console.error('[ShopixCart Debug][Product] add-click:post-persist-parse-error', error);
              }

              openCartSimple();
          });
        }

        if (cartEnabled && openCartButton) {
          openCartButton.addEventListener('click', () => {
              openCartSimple();
          });
        }

        if (!cartEnabled && whatsappButton) {
          whatsappButton.addEventListener('click', () => {
            if (!selectedVariant) {
              alert('Por favor, selecciona una variante primero.');
              return;
            }

            const fullPhoneNumber = String(tenantPhoneCode).replace(/\D/g, '') + String(tenantPhoneNumber).replace(/\D/g, '');
            if (!fullPhoneNumber) {
              alert('La tienda no tiene un número de WhatsApp configurado.');
              return;
            }

            const message = `Hola, vengo de tu tienda virtual de Shopix y estoy interesado en el producto *${selectedVariant.productName}* en la variante *${selectedVariant.size}* con precio de *${selectedVariant.price} ${baseCurrencySymbol}*. ¿Podrían darme más información?`;
            const whatsappLink = `https://wa.me/${fullPhoneNumber}?text=${encodeURIComponent(message)}`;
            window.open(whatsappLink, '_blank');
          });
        }
    });
  </script>
</body>

</html>