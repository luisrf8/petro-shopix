<!DOCTYPE html>
<html lang="es">

<head>
  @php($ecommercePwaIconVersion = (string) config('app.asset_version', '20260710'))
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="default">
  <meta name="apple-mobile-web-app-title" content="Shopix">
  <meta name="theme-color" content="#2563eb">
  <title>Shopix - Por tienda / servicio</title>
  <link rel="icon" type="image/png" href="{{ route('pwa.icon', ['size' => 192, 'variant' => 'client', 'v' => $ecommercePwaIconVersion]) }}">
  <link rel="manifest" href="{{ route('tenant.pwa.manifest', ['start_url' => route('landing.directory'), 'name' => 'Shopix', 'theme' => '2563eb', 'icon_variant' => 'client']) }}">
  <link rel="apple-touch-icon" sizes="180x180" href="{{ route('pwa.icon', ['size' => 180, 'variant' => 'client', 'v' => $ecommercePwaIconVersion]) }}">
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
      --brand-accent: #2563eb;
      --card-shadow: 0 20px 46px rgba(15, 23, 42, 0.08);
      --card-shadow-hover: 0 26px 54px rgba(15, 23, 42, 0.14);
    }

    * {
      box-sizing: border-box;
    }

    body {
      font-family: 'Manrope', sans-serif;
      background:
        radial-gradient(58rem 24rem at -8% 0%, rgba(37, 99, 235, 0.14), transparent 60%),
        radial-gradient(44rem 20rem at 110% 12%, rgba(14, 165, 233, 0.16), transparent 55%),
        linear-gradient(180deg, #fbfdff 0%, var(--bg-page) 54%, #f7f9fd 100%);
      color: var(--text-primary);
      min-height: 100vh;
      overflow-x: hidden;
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

    .directory-navbar-actions {
      align-items: center;
      gap: 0.45rem;
    }

    .directory-navbar-actions .nav-item {
      display: flex;
      align-items: center;
    }

    .directory-navbar-actions .landing-nav-link {
      border: 1px solid #e2e8f0;
      background: rgba(248, 250, 252, 0.92);
      color: #0f172a;
      min-height: 44px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      gap: 0.4rem;
      padding: 0.5rem 0.95rem;
      font-weight: 700;
      transition: all 0.2s ease;
    }

    .directory-navbar-actions .directory-icon-btn {
      width: 44px;
      min-width: 44px;
      padding: 0;
      font-size: 1.02rem;
    }

    .directory-user-menu {
      border-radius: 14px;
      border: 1px solid #dbe4f0;
      box-shadow: 0 16px 32px rgba(15, 23, 42, 0.12);
      padding: 0.35rem;
      min-width: 240px;
    }

    .directory-user-menu .dropdown-item {
      border-radius: 10px;
      font-weight: 600;
      display: inline-flex;
      align-items: center;
      gap: 0.55rem;
      padding: 0.52rem 0.66rem;
    }

    .directory-user-menu .dropdown-item i {
      width: 1.2rem;
      text-align: center;
    }

    .directory-user-menu-header {
      border-radius: 10px;
      border: 1px solid #dbeafe;
      background: #eff6ff;
      padding: 0.52rem 0.66rem;
      margin-bottom: 0.32rem;
      font-size: 0.88rem;
      color: #1e3a8a;
      font-weight: 700;
    }

    .directory-navbar-actions .landing-nav-link:hover {
      border-color: #c7d2fe;
      background: #ffffff;
      transform: translateY(-1px);
      box-shadow: 0 8px 18px rgba(15, 23, 42, 0.08);
    }

    .directory-navbar-actions #directory-client-login-btn {
      border-color: #0f172a;
      background: linear-gradient(135deg, #0f172a, #1e293b);
      color: #ffffff;
    }

    .directory-navbar-actions #directory-client-login-btn:hover {
      border-color: #0f172a;
      background: linear-gradient(135deg, #111827, #1f2937);
      color: #ffffff;
    }

    .hero {
      position: relative;
      padding-top: 4.2rem;
      padding-bottom: 0.35rem;
      overflow: hidden;
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
      margin-bottom: 0.35rem;
    }

    .hero-subtitle {
      font-size: clamp(0.98rem, 2vw, 1.1rem);
      line-height: 1.65;
      color: var(--text-secondary);
      max-width: 70ch;
      margin-bottom: 0;
    }

    .hero-search-shell {
      margin-top: 0.85rem;
      max-width: 46rem;
    }

    .hero-search-card {
      display: flex;
      align-items: center;
      gap: 0.7rem;
      padding: 0.82rem 0.9rem;
      border-radius: 20px;
      border: 1px solid rgba(148, 163, 184, 0.24);
      background: rgba(255, 255, 255, 0.94);
      box-shadow: 0 18px 36px rgba(15, 23, 42, 0.08);
      backdrop-filter: blur(12px);
    }

    .hero-search-icon {
      width: 2.6rem;
      height: 2.6rem;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      border-radius: 14px;
      background: linear-gradient(135deg, rgba(37, 99, 235, 0.14), rgba(14, 165, 233, 0.12));
      color: var(--brand-accent);
      font-size: 1rem;
      flex-shrink: 0;
    }

    .hero-search-copy {
      min-width: 0;
      flex: 1 1 auto;
    }

    .hero-search-label {
      display: block;
      font-size: 0.76rem;
      font-weight: 800;
      letter-spacing: 0.08em;
      text-transform: uppercase;
      color: #64748b;
      margin-bottom: 0.22rem;
    }

    .hero-search-input {
      border: 0;
      outline: 0;
      width: 100%;
      background: transparent;
      color: #0f172a;
      font-size: 1rem;
      font-weight: 700;
      padding: 0;
    }

    .hero-search-input::placeholder {
      color: #94a3b8;
      font-weight: 600;
    }

    .directory-user-toggle {
      display: inline-flex;
      align-items: center;
      gap: 0.5rem;
      border: 1px solid rgba(37, 99, 235, 0.22);
      border-radius: 999px;
      padding: 0.5rem 0.92rem;
      background: linear-gradient(135deg, rgba(219, 234, 254, 0.9), rgba(239, 246, 255, 0.96));
      color: #1e40af;
      font-weight: 800;
      box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.84);
      min-height: 44px;
      position: relative;
    }

    .directory-user-toggle i {
      font-size: 0.95rem;
    }

    .directory-user-toggle .badge {
      position: absolute;
      top: -6px;
      right: -6px;
      min-width: 18px;
      height: 18px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      font-size: 0.66rem;
      border: 2px solid #ffffff;
      border-radius: 999px;
      padding: 0 0.32rem;
      line-height: 1;
    }

    .landing-header.is-authenticated .directory-navbar-actions {
      background: rgba(255, 255, 255, 0.8);
      border: 1px solid rgba(148, 163, 184, 0.22);
      border-radius: 16px;
      padding: 0.28rem;
      box-shadow: 0 10px 22px rgba(15, 23, 42, 0.07);
    }

    .landing-header.is-authenticated .directory-client-action-btn .badge {
      top: -4px;
      right: -4px;
      border: 2px solid #ffffff;
    }

    .landing-header.is-authenticated #directory-client-logout-btn {
      border-color: #fecaca;
      color: #b91c1c;
      background: #fff5f5;
    }

    .landing-header.is-authenticated #directory-client-logout-btn:hover {
      border-color: #fca5a5;
      color: #991b1b;
      background: #ffe4e6;
    }

    .directory-auth-modal .modal-content {
      border-radius: 22px;
      border: 1px solid rgba(148, 163, 184, 0.24);
      overflow: hidden;
      box-shadow: 0 28px 60px rgba(15, 23, 42, 0.18);
    }

    .directory-auth-modal .modal-header,
    .directory-auth-modal .modal-footer {
      border-color: #e2e8f0;
      background: #ffffff;
    }

    .directory-auth-modal .modal-body {
      background: linear-gradient(180deg, #f8fbff, #f8fafc);
      padding: 1rem;
    }

    .directory-auth-shell {
      border: 1px solid #dbe4f0;
      border-radius: 18px;
      padding: 1rem;
      background: rgba(255, 255, 255, 0.96);
    }

    .directory-auth-shell .nav-link {
      border-radius: 12px 12px 0 0;
      color: #475569;
      font-weight: 700;
    }

    .directory-auth-shell .nav-link.active {
      background: linear-gradient(135deg, #1d4ed8, #0ea5e9);
      color: #ffffff;
    }

    .directory-auth-shell .form-control {
      border-radius: 12px;
      border-color: #d7dfeb;
      padding-top: 0.68rem;
      padding-bottom: 0.68rem;
    }

    .directory-auth-shell .form-control:focus {
      border-color: rgba(37, 99, 235, 0.42);
      box-shadow: 0 0 0 0.2rem rgba(37, 99, 235, 0.14);
    }

    .directory-auth-submit {
      border-radius: 12px;
      font-weight: 700;
      padding-top: 0.72rem;
      padding-bottom: 0.72rem;
      background: linear-gradient(135deg, #0f172a, #1e293b);
      border-color: #0f172a;
    }

    .directory-auth-helper {
      font-size: 0.9rem;
      color: #475569;
      margin-bottom: 0.85rem;
    }

    .directory-auth-error {
      border-radius: 12px;
      border: 1px solid #fecaca;
      background: #fff1f2;
      color: #b91c1c;
      font-size: 0.88rem;
      padding: 0.72rem 0.8rem;
    }

    .directory-client-action-btn {
      position: relative;
    }

    .directory-pwa-install.is-ready {
      background: #16a34a !important;
      border-color: #16a34a !important;
      color: #ffffff !important;
    }

    .directory-client-action-btn .badge {
      position: absolute;
      top: -6px;
      right: -6px;
      min-width: 18px;
      height: 18px;
      display: inline-flex;
      align-items: center;
      justify-content: center;
      padding: 0 0.32rem;
      font-size: 0.66rem;
      line-height: 1;
      border-radius: 999px;
      box-shadow: 0 2px 6px rgba(15, 23, 42, 0.24);
    }

    .directory-modern-modal .modal-content {
      border: 1px solid #dbe4f0;
      border-radius: 20px;
      overflow: hidden;
      box-shadow: 0 24px 48px rgba(15, 23, 42, 0.18);
    }

    .directory-modern-modal .modal-header,
    .directory-modern-modal .modal-footer {
      background: #ffffff;
      border-color: #e5e7eb;
    }

    .directory-modern-modal .modal-body {
      background: #f8fafc;
    }

    .directory-list-card {
      border: 1px solid #e2e8f0;
      border-radius: 14px;
      background: #ffffff;
      padding: 0.9rem;
    }

    .directory-list-meta {
      color: #64748b;
      font-size: 0.88rem;
      line-height: 1.4;
    }

    .directory-customer-shell {
      border: 1px solid #dbe4f0;
      border-radius: 14px;
      background: #ffffff;
      padding: 0.9rem;
    }

    .directory-customer-label {
      font-size: 0.75rem;
      color: #64748b;
      margin-bottom: 0.15rem;
    }

    .directory-customer-value {
      font-size: 0.95rem;
      color: #0f172a;
      font-weight: 700;
      margin-bottom: 0;
      word-break: break-word;
    }

    .directory-filter-card {
      border: 1px solid rgba(148, 163, 184, 0.24);
      border-radius: 24px;
      background: rgba(255, 255, 255, 0.9);
      backdrop-filter: blur(10px);
      box-shadow: var(--card-shadow);
      position: relative;
      overflow: hidden;
    }

    .directory-shell {
      display: grid;
      grid-template-columns: minmax(280px, 320px) minmax(0, 1fr);
      gap: 1.25rem;
      align-items: start;
    }

    .directory-shell > :first-child {
      margin-top: 0 !important;
      padding-top: 0 !important;
    }

    .directory-sidebar {
      position: sticky;
      top: 6.6rem;
    }

    .directory-sidebar .directory-filter-card {
      padding: 1.15rem;
    }

    .directory-sidebar-header {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      gap: 0.75rem;
      margin-bottom: 0.9rem;
    }

    .directory-sidebar-title {
      font-size: 1rem;
      font-weight: 800;
      margin: 0;
      color: #0f172a;
    }

    .directory-sidebar-subtitle {
      font-size: 0.84rem;
      line-height: 1.45;
      color: #64748b;
      margin: 0.2rem 0 0;
    }

    .directory-sidebar-actions {
      display: flex;
      flex-wrap: wrap;
      gap: 0.65rem;
      margin-top: 0.9rem;
    }

    .directory-sidebar-actions .directory-results-meta {
      width: 100%;
      font-size: 0.86rem;
    }

    .directory-content {
      min-width: 0;
    }

    .directory-content-header {
      display: flex;
      align-items: flex-start;
      justify-content: space-between;
      gap: 1rem;
    }

    .directory-content-title {
      font-size: clamp(1.35rem, 2vw, 1.75rem);
      font-weight: 800;
      margin-bottom: 0.3rem;
      color: #0f172a;
    }

    .directory-content-copy {
      font-size: 0.94rem;
      color: #64748b;
      margin: 0;
      max-width: 60ch;
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

    .mobile-directory-filter-shell {
      display: none;
    }

    .mobile-directory-filter-shell .directory-filter-card {
      margin-top: 0.4rem;
      box-shadow: none;
      border-color: rgba(148, 163, 184, 0.24);
      background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.98));
    }

    .directory-filter-group {
      border: 1px solid var(--line-soft);
      border-radius: 16px;
      padding: 1rem;
      background: linear-gradient(180deg, rgba(248, 250, 252, 0.84), rgba(255, 255, 255, 0.96));
      height: 100%;
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

    .directory-grid {
      display: grid;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 0.95rem;
      align-items: stretch;
    }

    .landing-directory-item {
      min-width: 0;
    }

    .directory-tenant-card {
      border: 1px solid rgba(148, 163, 184, 0.24);
      border-radius: 18px;
      background: linear-gradient(180deg, rgba(255, 255, 255, 0.98), rgba(248, 250, 252, 0.96));
      box-shadow: 0 12px 28px rgba(15, 23, 42, 0.08);
      transition: transform 0.24s ease, box-shadow 0.24s ease, border-color 0.24s ease;
      height: 100%;
      overflow: hidden;
    }

    .directory-tenant-card:hover {
      transform: translateY(-4px);
      box-shadow: var(--card-shadow-hover);
      border-color: rgba(37, 99, 235, 0.24);
    }

    .directory-tenant-media {
      height: 168px;
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
      max-width: calc(100% - 1.8rem);
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
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

    .directory-chip {
      display: inline-flex;
      align-items: center;
      gap: 0.34rem;
      max-width: 100%;
      background: #eff6ff;
      color: #1e40af;
      border: 1px solid #bfdbfe;
      border-radius: 999px;
      font-size: 0.74rem;
      font-weight: 700;
      padding: 0.23rem 0.62rem;
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }

    .directory-location {
      color: #52637b;
      line-height: 1.55;
      min-height: 2.7em;
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
      min-height: 2.45em;
    }

    .directory-description {
      font-size: 0.86rem;
      color: #5b6b83;
      line-height: 1.45;
      margin-bottom: 0.55rem;
      display: -webkit-box;
      -webkit-line-clamp: 3;
      -webkit-box-orient: vertical;
      overflow: hidden;
      text-overflow: ellipsis;
      min-height: 3.7em;
    }

    @media (max-width: 991.98px) {
      .hero {
        padding-top: 5.4rem;
        padding-bottom: 0.25rem;
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

      .landing-nav-link {
        width: 100%;
      }

      .directory-navbar-actions {
        align-items: stretch;
        gap: 0.5rem;
      }

      .landing-header.is-authenticated .directory-navbar-actions {
        border-radius: 14px;
        padding: 0.45rem;
      }

      .directory-user-toggle {
        width: 100%;
        justify-content: center;
      }

      .directory-user-menu {
        width: 100%;
        min-width: 0;
      }
    }

    @media (max-width: 767.98px) {
      .hero {
        padding-top: 4.7rem;
        padding-bottom: 0.15rem;
      }

      .hero .container {
        padding-left: 0.85rem;
        padding-right: 0.85rem;
      }

      .hero-eyebrow {
        margin-bottom: 0.15rem;
        padding: 0.34rem 0.72rem;
        font-size: 0.78rem;
      }

      .hero-search-shell {
      }

      .hero-search-card {
        padding: 0.72rem 0.78rem;
        border-radius: 18px;
      }

      .hero-search-icon {
        width: 2.35rem;
        height: 2.35rem;
        border-radius: 12px;
      }

      .hero-search-input {
        font-size: 0.96rem;
      }

      .hero h1 {
        max-width: none;
      }

      .mobile-directory-filter-shell {
        display: block;
      }

      .directory-shell {
        grid-template-columns: 1fr;
        gap: 1rem;
      }

      .directory-sidebar {
        position: static;
      }

      .directory-filter-card.is-desktop {
        display: none;
      }

      .directory-filter-group {
        padding: 0.8rem;
      }

      .mobile-directory-filter-shell .directory-filter-card {
        padding: 0.95rem;
      }

      .directory-content-header {
        flex-direction: column;
        margin-bottom: 0.85rem;
      }

      .directory-content-title {
        font-size: 1.2rem;
      }

      .directory-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.1rem;
      }

      .directory-tenant-media {
        height: 118px;
      }

      .directory-card-logo {
        width: 42px;
        height: 42px;
      }

      .directory-card-badge {
        left: 0.55rem;
        bottom: 0.55rem;
        font-size: 0.62rem;
        padding: 0.2rem 0.5rem;
      }

      .directory-tenant-card .p-3 {
        padding: 0.75rem !important;
      }

      .directory-tenant-card h5 {
        font-size: 0.98rem;
        line-height: 1.2;
      }

      .directory-chip {
        font-size: 0.66rem;
        padding: 0.18rem 0.45rem;
      }

      .directory-location {
        min-height: auto;
        font-size: 0.76rem;
        line-height: 1.35;
      }

      .directory-slogan {
        font-size: 0.78rem;
        min-height: auto;
        -webkit-line-clamp: 2;
      }

      .directory-description {
        font-size: 0.74rem;
        line-height: 1.35;
        min-height: auto;
        -webkit-line-clamp: 2;
      }

      .directory-tenant-card .btn {
        padding: 0.5rem 0.7rem;
        font-size: 0.8rem;
      }
    }

    @media (min-width: 768px) and (max-width: 1199.98px) {
      .directory-shell {
        grid-template-columns: minmax(250px, 300px) minmax(0, 1fr);
      }

      .directory-grid {
        grid-template-columns: repeat(2, minmax(0, 1fr));
      }
    }

    @media (min-width: 1400px) {
      .directory-tenant-media {
        height: 188px;
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
          <ul class="navbar-nav ms-auto align-items-lg-center gap-lg-2 directory-navbar-actions">
            <li class="nav-item">
              <a class="btn btn-light text-dark landing-nav-link directory-icon-btn" href="/landings" title="Inicio: tiendas y servicios" aria-label="Inicio: tiendas y servicios">
                <i class="bi bi-house"></i>
              </a>
            </li>
            <li class="nav-item" id="directory-client-login-wrap">
              <button type="button" class="btn btn-dark landing-nav-link directory-icon-btn" id="directory-client-login-btn" title="Entrar como cliente" aria-label="Entrar como cliente">
                <i class="bi bi-person"></i>
              </button>
            </li>
            <li class="nav-item dropdown d-none" id="directory-client-session-wrap">
              <button type="button"
                      class="btn directory-user-toggle dropdown-toggle"
                      id="directory-client-session-toggle"
                      data-bs-toggle="dropdown"
                      aria-expanded="false"
                      aria-label="Menú de cliente"
                      title="Menú de cliente">
                <i class="bi bi-person-circle"></i>
                <span class="d-none d-md-inline" id="directory-client-session-name">Cliente</span>
                <span class="badge rounded-pill bg-danger d-none" id="directory-client-notifications-count">0</span>
              </button>
              <ul class="dropdown-menu dropdown-menu-end directory-user-menu">
                <li>
                  <div class="directory-user-menu-header">
                    Hola, <span id="directory-client-menu-name">cliente</span>
                  </div>
                </li>
                <li id="directory-client-notifications-wrap">
                  <button type="button" class="dropdown-item directory-client-action-btn" id="directory-client-notifications-btn">
                    <i class="bi bi-bell"></i>
                    <span>Notificaciones</span>
                  </button>
                </li>
                <li id="directory-client-orders-wrap">
                  <a href="#" class="dropdown-item" id="directory-client-orders-btn">
                    <i class="bi bi-bag-check"></i>
                    <span>Mis compras</span>
                  </a>
                </li>
                <li id="directory-client-account-wrap">
                  <a href="#" class="dropdown-item" id="directory-client-account-btn">
                    <i class="bi bi-person-gear"></i>
                    <span>Mi perfil</span>
                  </a>
                </li>
                <li id="directory-install-pwa-wrap">
                  <button type="button" class="dropdown-item directory-pwa-install d-none" id="directory-install-pwa-btn">
                    <i class="bi bi-download"></i>
                    <span id="directory-install-pwa-label">Instalar app</span>
                  </button>
                </li>
                <li><hr class="dropdown-divider my-1"></li>
                <li id="directory-client-logout-wrap">
                  <button type="button" class="dropdown-item text-danger" id="directory-client-logout-btn">
                    <i class="bi bi-box-arrow-right"></i>
                    <span>Cerrar sesión</span>
                  </button>
                </li>
              </ul>
            </li>
          </ul>

          <div class="mobile-directory-filter-shell d-md-none">
            <div class="directory-filter-card p-3 mt-2">
              <div class="row g-3 align-items-end">
                <div class="col-12">
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

  <section class="hero mt-4">
    <div class="mx-1">
      <span class="hero-eyebrow"><i class="bi bi-geo-alt"></i> Explorador inteligente</span>
      <div class="hero-search-shell">
        <label class="hero-search-card" for="landingHeroSearch">
          <span class="hero-search-icon"><i class="bi bi-search"></i></span>
          <span class="hero-search-copy">
            <input id="landingHeroSearch" data-landing-filter="name" type="search" class="hero-search-input" placeholder="Buscar Tienda o Servicio...">
          </span>
        </label>
      </div>
    </div>
  </section>

  <section class="mx-1">
    <div class="">
      <div class="directory-shell">
        <aside class="directory-sidebar">
          <div class="directory-filter-card is-desktop">
            <div class="row g-3 mt-1">
              <div class="col-12">
                <div class="directory-filter-group h-100">
                  <div class="directory-filter-group-title"><i class="bi bi-geo-alt-fill me-1"></i> Localidad</div>
                  <div class="row g-2">
                    <div class="col-12">
                      <label class="form-label" for="landingStateFilterDesktop">Estado</label>
                      <select id="landingStateFilterDesktop" data-landing-filter="state" class="form-select">
                        <option value="">Todos</option>
                        @foreach(($tenantFilters['states'] ?? collect()) as $state)
                          <option value="{{ $state }}">{{ $state }}</option>
                        @endforeach
                      </select>
                    </div>
                    <div class="col-12">
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
              <div class="col-12">
                <div class="directory-filter-group h-100">
                  <div class="directory-filter-group-title"><i class="bi bi-sliders2-vertical me-1"></i> Especificaciones</div>
                  <div class="row g-2">
                    <div class="col-12">
                      <label class="form-label" for="landingTypeFilterDesktop">Tipo de tienda / servicio</label>
                      <select id="landingTypeFilterDesktop" data-landing-filter="type" class="form-select">
                        <option value="">Todos</option>
                        @foreach(($tenantFilters['types'] ?? collect()) as $type)
                          <option value="{{ $type }}">{{ $type }}</option>
                        @endforeach
                      </select>
                    </div>
                    <div class="col-12">
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

            <div class="directory-sidebar-actions">
              <button type="button" data-landing-clear class="btn btn-outline-secondary directory-clear-btn w-100">
                <i class="bi bi-arrow-counterclockwise me-1"></i> Limpiar filtros
              </button>
              <span class="directory-results-meta" data-landing-results-count>0 resultados</span>
            </div>
          </div>
        </aside>

        <div class="directory-content">
          <div class="directory-content-header">
            <span class="directory-results-meta d-none d-md-inline-flex" data-landing-results-count>0 resultados</span>
          </div>

          <div class="directory-grid" id="landingDirectoryList">
        @foreach($tenantsDirectory as $tenant)
          <div class="landing-directory-item"
               data-name="{{ $tenant->name ?? '' }}"
               data-type="{{ $tenant->directory_type ?? '' }}"
               data-activity="{{ $tenant->directory_activity ?? '' }}"
               data-state="{{ $tenant->directory_state ?? '' }}"
              data-city="{{ $tenant->directory_city ?? '' }}"
              data-search="{{ trim(implode(' ', array_filter([
                $tenant->name ?? '',
                $tenant->slug ?? '',
                $tenant->directory_type ?? '',
                $tenant->directory_activity ?? '',
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

              <div class="p-3 d-flex flex-column flex-grow-1">
                <div class="d-flex align-items-center gap-2 mb-2">
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

                <p class="mb-2 small directory-location">
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
                  @php($directoryTypeLabel = mb_strtolower(trim((string) ($tenant->directory_type ?? 'tienda'))))
                  @php($directoryActionLabel = str_contains($directoryTypeLabel, 'servicio') ? 'Ver servicio' : 'Ver tienda')
                  @php($externalUrl = trim((string) ($tenant->external_url ?? '')))
                  @php($externalUrl = $externalUrl !== '' && !\Illuminate\Support\Str::startsWith(\Illuminate\Support\Str::lower($externalUrl), ['http://', 'https://']) ? ('https://' . $externalUrl) : $externalUrl)
                  @php($shopixUrl = url('/' . $tenant->slug))
                  @if($externalUrl !== '')
                    <div class="d-flex gap-2 flex-wrap">
                      <a
                        href="{{ $shopixUrl }}"
                        class="btn btn-dark flex-fill rounded-3"
                        data-directory-tenant-link
                      >{{ $directoryActionLabel }} en Shopix</a>
                      <a
                        href="{{ $externalUrl }}"
                        class="btn btn-outline-dark flex-fill rounded-3"
                        target="_blank"
                        rel="noopener"
                      >Sitio oficial</a>
                    </div>
                  @else
                    <a
                      href="{{ $shopixUrl }}"
                      class="btn btn-dark w-100 rounded-3"
                      data-directory-tenant-link
                    >{{ $directoryActionLabel }}</a>
                  @endif
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
      </div>
    </div>
  </section>

  <div class="modal fade directory-auth-modal" id="directoryClientAuthModal" tabindex="-1" aria-labelledby="directoryClientAuthModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="directoryClientAuthModalLabel">Acceso cliente</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="directory-auth-shell">
            <p class="directory-auth-helper">Ingresa como cliente para entrar a las tiendas de Shopix. Si instalas la app desde aquí, el acceso directo abrirá este explorador.</p>
            <div id="directory-auth-error" class="directory-auth-error d-none mb-3"></div>
            <ul class="nav nav-tabs mb-3" id="directoryAuthTabs" role="tablist">
              <li class="nav-item" role="presentation">
                <button class="nav-link active" id="directory-auth-login-tab" data-bs-toggle="tab" data-bs-target="#directory-auth-login-panel" type="button" role="tab">Iniciar sesión</button>
              </li>
              <li class="nav-item" role="presentation">
                <button class="nav-link" id="directory-auth-register-tab" data-bs-toggle="tab" data-bs-target="#directory-auth-register-panel" type="button" role="tab">Crear cuenta</button>
              </li>
            </ul>
            <div class="tab-content" id="directoryAuthTabsContent">
              <div class="tab-pane fade show active" id="directory-auth-login-panel" role="tabpanel">
                <form id="directory-client-login-form" class="row g-3">
                  <div class="col-12">
                    <select class="form-select" id="directory-client-login-type" required>
                      <option value="name" selected>Ingresar por Nombre</option>
                      <option value="email">Ingresar por Correo</option>
                      <option value="dni">Ingresar por DNI</option>
                    </select>
                  </div>
                  <div class="col-12">
                    <input type="text" class="form-control" id="directory-client-login-identifier" placeholder="Nombre" required>
                  </div>
                  <div class="col-12">
                    <input type="password" class="form-control" id="directory-client-login-password" placeholder="Contraseña" required>
                  </div>
                  <div class="col-12">
                    <button type="submit" class="btn btn-dark w-100 directory-auth-submit">Entrar</button>
                  </div>
                </form>
              </div>
              <div class="tab-pane fade" id="directory-auth-register-panel" role="tabpanel">
                <form id="directory-client-register-form" class="row g-3">
                  <div class="col-12">
                    <input type="text" class="form-control" id="directory-client-register-name" placeholder="Nombre" required>
                  </div>
                  <div class="col-12">
                    <input type="email" class="form-control" id="directory-client-register-email" placeholder="Email" required>
                  </div>
                  <div class="col-12">
                    <input type="password" class="form-control" id="directory-client-register-password" placeholder="Contraseña" minlength="8" required>
                  </div>
                  <div class="col-12">
                    <input type="password" class="form-control" id="directory-client-register-password-confirmation" placeholder="Confirmar contraseña" minlength="8" required>
                  </div>
                  <div class="col-12 col-md-6">
                    <input type="text" class="form-control" id="directory-client-register-dni" placeholder="DNI opcional">
                  </div>
                  <div class="col-12 col-md-6">
                    <input type="text" class="form-control" id="directory-client-register-phone" placeholder="Teléfono opcional">
                  </div>
                  <div class="col-12">
                    <button type="submit" class="btn btn-dark w-100 directory-auth-submit">Crear cuenta</button>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade directory-modern-modal" id="directoryClientNotificationsModal" tabindex="-1" aria-labelledby="directoryClientNotificationsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="directoryClientNotificationsModalLabel">Notificaciones</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div id="directory-client-notifications-list" class="d-flex flex-column gap-2"></div>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade directory-modern-modal" id="directoryClientOrdersModal" tabindex="-1" aria-labelledby="directoryClientOrdersModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="directoryClientOrdersModalLabel">Mis compras</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div id="directory-client-orders-list" class="d-flex flex-column gap-2"></div>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade directory-modern-modal" id="directoryClientAccountModal" tabindex="-1" aria-labelledby="directoryClientAccountModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="directoryClientAccountModalLabel">Mi perfil</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body d-flex flex-column gap-3">
          <div class="directory-customer-shell">
            <div class="row g-2">
              <div class="col-12 col-md-6">
                <p class="directory-customer-label">Nombre</p>
                <p class="directory-customer-value" id="directory-client-account-name">-</p>
              </div>
              <div class="col-12 col-md-6">
                <p class="directory-customer-label">Email</p>
                <p class="directory-customer-value" id="directory-client-account-email">-</p>
              </div>
              <div class="col-12 col-md-6">
                <p class="directory-customer-label">DNI</p>
                <p class="directory-customer-value" id="directory-client-account-dni">No registrado</p>
              </div>
              <div class="col-12 col-md-6">
                <p class="directory-customer-label">Teléfono</p>
                <p class="directory-customer-value" id="directory-client-account-phone">No registrado</p>
              </div>
              <div class="col-12">
                <p class="directory-customer-label">Ubicación guardada</p>
                <p class="directory-customer-value" id="directory-client-account-location">No registrada</p>
              </div>
              <div class="col-12">
                <p class="directory-customer-label">Coordenadas</p>
                <p class="directory-customer-value" id="directory-client-account-coordinates">No registradas</p>
              </div>
              <div class="col-12">
                <form id="directory-client-profile-form" class="row g-2 align-items-end">
                  <div class="col-12 col-md-6">
                    <label for="directory-client-phone-input" class="directory-customer-label">Teléfono</label>
                    <input type="text" class="form-control" id="directory-client-phone-input" placeholder="Ej: +58 412 0000000" maxlength="50">
                  </div>
                  <div class="col-12 col-md-6">
                    <label for="directory-client-address-input" class="directory-customer-label">Dirección exacta</label>
                    <input type="text" class="form-control" id="directory-client-address-input" placeholder="Calle, edificio, referencia..." maxlength="500">
                  </div>
                  <div class="col-12 col-md-4">
                    <label for="directory-client-country" class="directory-customer-label">País</label>
                    <select id="directory-client-country" class="form-select">
                      <option value="">País</option>
                    </select>
                  </div>
                  <div class="col-12 col-md-4">
                    <label for="directory-client-state" class="directory-customer-label">Estado</label>
                    <select id="directory-client-state" class="form-select" disabled>
                      <option value="">Estado</option>
                    </select>
                  </div>
                  <div class="col-12 col-md-4">
                    <label for="directory-client-city" class="directory-customer-label">Ciudad</label>
                    <select id="directory-client-city" class="form-select" disabled>
                      <option value="">Ciudad</option>
                    </select>
                  </div>
                  <div class="col-12 d-flex flex-wrap gap-2 align-items-center">
                    <button type="button" class="btn btn-outline-dark btn-sm" id="directory-client-use-current-location">Usar ubicación actual</button>
                    <small class="text-muted" id="directory-client-location-status">Aún no se ha fijado una ubicación exacta.</small>
                    <input type="hidden" id="directory-client-latitude">
                    <input type="hidden" id="directory-client-longitude">
                  </div>
                  <div class="col-12 col-md-4">
                    <button type="submit" class="btn btn-outline-dark btn-sm w-100">Guardar perfil</button>
                  </div>
                </form>
              </div>
            </div>
          </div>

          <div class="directory-customer-shell">
            <h6 class="mb-2">Cambiar contraseña</h6>
            <form id="directory-client-password-form" class="row g-2">
              <div class="col-12">
                <input type="password" class="form-control" id="directory-client-current-password" placeholder="Contraseña actual" minlength="8" required>
              </div>
              <div class="col-12 col-md-6">
                <input type="password" class="form-control" id="directory-client-new-password" placeholder="Nueva contraseña" minlength="8" required>
              </div>
              <div class="col-12 col-md-6">
                <input type="password" class="form-control" id="directory-client-new-password-confirmation" placeholder="Confirmar nueva contraseña" minlength="8" required>
              </div>
              <div class="col-12">
                <button type="submit" class="btn btn-outline-dark btn-sm">Actualizar contraseña</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    const directoryAuthTokenKey = 'shopix_ecomm_token';
    const directoryAuthUserKey = 'shopix_ecomm_user';
    const directoryPendingUrlKey = 'shopix_directory_pending_url';
    const directoryCustomerPortalBaseUrl = @json(route('customer.portal.general'));
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
    const directoryClientLoginWrap = document.getElementById('directory-client-login-wrap');
    const directoryClientLoginBtn = document.getElementById('directory-client-login-btn');
    const directoryClientSessionWrap = document.getElementById('directory-client-session-wrap');
    const directoryClientSessionName = document.getElementById('directory-client-session-name');
    const directoryClientMenuName = document.getElementById('directory-client-menu-name');
    const directoryClientNotificationsWrap = document.getElementById('directory-client-notifications-wrap');
    const directoryClientNotificationsBtn = document.getElementById('directory-client-notifications-btn');
    const directoryClientNotificationsCount = document.getElementById('directory-client-notifications-count');
    const directoryClientNotificationsList = document.getElementById('directory-client-notifications-list');
    const directoryClientOrdersWrap = document.getElementById('directory-client-orders-wrap');
    const directoryClientOrdersBtn = document.getElementById('directory-client-orders-btn');
    const directoryClientOrdersList = document.getElementById('directory-client-orders-list');
    const directoryClientAccountWrap = document.getElementById('directory-client-account-wrap');
    const directoryClientAccountBtn = document.getElementById('directory-client-account-btn');
    const directoryInstallPwaBtn = document.getElementById('directory-install-pwa-btn');
    const directoryInstallPwaLabel = document.getElementById('directory-install-pwa-label');
    const directoryClientLogoutWrap = document.getElementById('directory-client-logout-wrap');
    const directoryClientLogoutBtn = document.getElementById('directory-client-logout-btn');
    const directoryClientAuthModalElement = document.getElementById('directoryClientAuthModal');
    const directoryClientNotificationsModalElement = document.getElementById('directoryClientNotificationsModal');
    const directoryClientOrdersModalElement = document.getElementById('directoryClientOrdersModal');
    const directoryClientAccountModalElement = document.getElementById('directoryClientAccountModal');
    const directoryAuthError = document.getElementById('directory-auth-error');
    const directoryClientLoginForm = document.getElementById('directory-client-login-form');
    const directoryClientRegisterForm = document.getElementById('directory-client-register-form');
    const directoryClientProfileForm = document.getElementById('directory-client-profile-form');
    const directoryClientPasswordForm = document.getElementById('directory-client-password-form');
    const directoryClientCountrySelect = document.getElementById('directory-client-country');
    const directoryClientStateSelect = document.getElementById('directory-client-state');
    const directoryClientCitySelect = document.getElementById('directory-client-city');
    const directoryClientAddressInput = document.getElementById('directory-client-address-input');
    const directoryClientLatitudeInput = document.getElementById('directory-client-latitude');
    const directoryClientLongitudeInput = document.getElementById('directory-client-longitude');
    const directoryClientUseCurrentLocationBtn = document.getElementById('directory-client-use-current-location');
    const directoryClientLocationStatus = document.getElementById('directory-client-location-status');
    const landingTotalItems = landingDirectoryItems.length;
    const landingStateCityMap = buildLandingStateCityMap();
    const directoryAuthModal = directoryClientAuthModalElement ? bootstrap.Modal.getOrCreateInstance(directoryClientAuthModalElement) : null;
    const directoryClientNotificationsModal = directoryClientNotificationsModalElement ? bootstrap.Modal.getOrCreateInstance(directoryClientNotificationsModalElement) : null;
    const directoryClientOrdersModal = directoryClientOrdersModalElement ? bootstrap.Modal.getOrCreateInstance(directoryClientOrdersModalElement) : null;
    const directoryClientAccountModal = directoryClientAccountModalElement ? bootstrap.Modal.getOrCreateInstance(directoryClientAccountModalElement) : null;
    const directoryServiceWorkerUrl = @json(url('/push-sw.js'));
    const directoryVapidPublicKey = @json(config('webpush.vapid.public_key'));
    const directoryDefaultNotificationIcon = @json(url('/pwa-icon/192.png'));
    let directoryServiceWorkerRegistrationPromise = null;
    let directoryNotificationAutoPrompted = false;
    let directoryCountriesCache = null;

    function buildDirectoryCustomerPortalUrl(hash = '') {
      return `${directoryCustomerPortalBaseUrl}${hash}`;
    }

    async function fetchDirectoryLocationJson(url) {
      const response = await fetch(url, { headers: { Accept: 'application/json' } });

      if (!response.ok) {
        throw new Error('No se pudo cargar la ubicación.');
      }

      return response.json();
    }

    function resetDirectorySelect(selectElement, placeholder, disabled = true) {
      if (!selectElement) {
        return;
      }

      selectElement.innerHTML = `<option value="">${placeholder}</option>`;
      selectElement.disabled = disabled;
    }

    function fillDirectorySelect(selectElement, items, placeholder, selectedValue = null) {
      if (!selectElement) {
        return;
      }

      const selectedAsString = selectedValue !== null && selectedValue !== undefined ? String(selectedValue) : null;
      selectElement.innerHTML = [
        `<option value="">${placeholder}</option>`,
        ...items.map((item) => {
          const id = String(item.id);
          const selected = selectedAsString !== null && id === selectedAsString ? ' selected' : '';
          return `<option value="${id}"${selected}>${item.name}</option>`;
        }),
      ].join('');
      selectElement.disabled = items.length === 0;
    }

    async function getDirectoryCountries() {
      if (Array.isArray(directoryCountriesCache)) {
        return directoryCountriesCache;
      }

      const countries = await fetchDirectoryLocationJson('/get-countries');
      directoryCountriesCache = Array.isArray(countries) ? countries : [];
      return directoryCountriesCache;
    }

    async function initDirectoryLocationSelectors(user = null) {
      if (!directoryClientCountrySelect || !directoryClientStateSelect || !directoryClientCitySelect) {
        return;
      }

      const countries = await getDirectoryCountries();
      fillDirectorySelect(directoryClientCountrySelect, countries, 'País', user?.country_id || null);

      if (!directoryClientCountrySelect.value) {
        resetDirectorySelect(directoryClientStateSelect, 'Estado', true);
        resetDirectorySelect(directoryClientCitySelect, 'Ciudad', true);
        return;
      }

      const states = await fetchDirectoryLocationJson(`/get-states/${directoryClientCountrySelect.value}`);
      fillDirectorySelect(directoryClientStateSelect, Array.isArray(states) ? states : [], 'Estado', user?.state_id || null);

      if (!directoryClientStateSelect.value) {
        resetDirectorySelect(directoryClientCitySelect, 'Ciudad', true);
        return;
      }

      const cities = await fetchDirectoryLocationJson(`/get-cities/${directoryClientStateSelect.value}`);
      fillDirectorySelect(directoryClientCitySelect, Array.isArray(cities) ? cities : [], 'Ciudad', user?.city_id || null);
    }

    function bindDirectoryLocationSelectors() {
      directoryClientCountrySelect?.addEventListener('change', async () => {
        resetDirectorySelect(directoryClientStateSelect, 'Estado', true);
        resetDirectorySelect(directoryClientCitySelect, 'Ciudad', true);

        if (!directoryClientCountrySelect.value) {
          return;
        }

        try {
          const states = await fetchDirectoryLocationJson(`/get-states/${directoryClientCountrySelect.value}`);
          fillDirectorySelect(directoryClientStateSelect, Array.isArray(states) ? states : [], 'Estado');
        } catch (error) {
          alert('No se pudieron cargar los estados.');
        }
      });

      directoryClientStateSelect?.addEventListener('change', async () => {
        resetDirectorySelect(directoryClientCitySelect, 'Ciudad', true);

        if (!directoryClientStateSelect.value) {
          return;
        }

        try {
          const cities = await fetchDirectoryLocationJson(`/get-cities/${directoryClientStateSelect.value}`);
          fillDirectorySelect(directoryClientCitySelect, Array.isArray(cities) ? cities : [], 'Ciudad');
        } catch (error) {
          alert('No se pudieron cargar las ciudades.');
        }
      });
    }

    function updateDirectoryLocationStatus(latitude = null, longitude = null) {
      if (!directoryClientLocationStatus) {
        return;
      }

      if (Number.isFinite(latitude) && Number.isFinite(longitude)) {
        directoryClientLocationStatus.textContent = `Ubicación exacta fijada: ${latitude.toFixed(6)}, ${longitude.toFixed(6)}`;
        return;
      }

      directoryClientLocationStatus.textContent = 'Aún no se ha fijado una ubicación exacta.';
    }

    function getDirectorySelectedText(selectElement) {
      if (!selectElement || !selectElement.value) {
        return '';
      }

      const selectedOption = selectElement?.options?.[selectElement.selectedIndex];
      return selectedOption ? selectedOption.text.trim() : '';
    }

    function requestDirectoryCurrentLocation() {
      if (!navigator.geolocation) {
        alert('Tu dispositivo no permite obtener ubicación desde el navegador.');
        return;
      }

      updateDirectoryLocationStatus(null, null);
      if (directoryClientLocationStatus) {
        directoryClientLocationStatus.textContent = 'Obteniendo ubicación actual...';
      }

      navigator.geolocation.getCurrentPosition((position) => {
        const latitude = Number(position.coords.latitude || 0);
        const longitude = Number(position.coords.longitude || 0);

        if (directoryClientLatitudeInput) {
          directoryClientLatitudeInput.value = String(latitude);
        }

        if (directoryClientLongitudeInput) {
          directoryClientLongitudeInput.value = String(longitude);
        }

        updateDirectoryLocationStatus(latitude, longitude);
      }, () => {
        updateDirectoryLocationStatus(null, null);
        alert('No se pudo obtener tu ubicación actual. Revisa los permisos de la app o del navegador.');
      }, {
        enableHighAccuracy: true,
        timeout: 12000,
        maximumAge: 0,
      });
    }

    function getDirectoryAuthToken() {
      return localStorage.getItem(directoryAuthTokenKey) || '';
    }

    function isDirectoryStandaloneMode() {
      return window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
    }

    function isDirectoryIosDevice() {
      const userAgent = window.navigator.userAgent || '';
      return /iPad|iPhone|iPod/.test(userAgent) || (userAgent.includes('Mac') && 'ontouchend' in document);
    }

    function supportsDirectoryBrowserNotifications() {
      return window.isSecureContext && 'Notification' in window && 'serviceWorker' in navigator && 'PushManager' in window;
    }

    async function ensureDirectoryServiceWorkerRegistration() {
      if (!supportsDirectoryBrowserNotifications()) {
        return null;
      }

      if (!directoryServiceWorkerRegistrationPromise) {
        directoryServiceWorkerRegistrationPromise = navigator.serviceWorker.register(directoryServiceWorkerUrl, { scope: '/' });
      }

      return directoryServiceWorkerRegistrationPromise;
    }

    function directoryUrlBase64ToUint8Array(base64String) {
      const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
      const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
      const rawData = window.atob(base64);
      const outputArray = new Uint8Array(rawData.length);

      for (let index = 0; index < rawData.length; index += 1) {
        outputArray[index] = rawData.charCodeAt(index);
      }

      return outputArray;
    }

    function resolveDirectoryPushContentEncoding(subscription) {
      const subscriptionJson = typeof subscription?.toJSON === 'function' ? subscription.toJSON() : null;
      if (subscriptionJson?.contentEncoding) {
        return subscriptionJson.contentEncoding;
      }

      const supportedEncodings = Array.isArray(window.PushManager?.supportedContentEncodings)
        ? window.PushManager.supportedContentEncodings
        : [];

      if (supportedEncodings.includes('aes128gcm')) {
        return 'aes128gcm';
      }

      if (supportedEncodings.includes('aesgcm')) {
        return 'aesgcm';
      }

      return 'aesgcm';
    }

    async function syncDirectoryBrowserPushSubscription(token, options = {}) {
      if (!supportsDirectoryBrowserNotifications() || !directoryVapidPublicKey) {
        return null;
      }

      const registration = await ensureDirectoryServiceWorkerRegistration();
      if (!registration) {
        return null;
      }

      let subscription = await registration.pushManager.getSubscription();
      if (subscription && options.forceRefresh === true) {
        await fetch('/api/push-subscriptions', {
          method: 'DELETE',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'Authorization': `Bearer ${token}`,
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
          },
          body: JSON.stringify({ endpoint: subscription.endpoint }),
        }).catch(() => {});
        await subscription.unsubscribe().catch(() => {});
        subscription = null;
      }

      if (!subscription) {
        subscription = await registration.pushManager.subscribe({
          userVisibleOnly: true,
          applicationServerKey: directoryUrlBase64ToUint8Array(directoryVapidPublicKey),
        });
      }

      await fetch('/api/push-subscriptions', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'Authorization': `Bearer ${token}`,
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
        body: JSON.stringify({
          subscription: {
            ...subscription.toJSON(),
            contentEncoding: resolveDirectoryPushContentEncoding(subscription),
          },
        }),
      });

      return subscription;
    }

    async function showDirectoryBrowserNotification(notification, options = {}) {
      if (!supportsDirectoryBrowserNotifications() || Notification.permission !== 'granted') {
        return;
      }

      const force = options.force === true;
      if (!force && document.visibilityState === 'visible' && !isDirectoryStandaloneMode()) {
        return;
      }

      const registration = await ensureDirectoryServiceWorkerRegistration().catch(() => null);
      const notificationOptions = {
        body: notification.message || notification.body || '',
        icon: directoryDefaultNotificationIcon,
        badge: directoryDefaultNotificationIcon,
        data: {
          url: notification.target_url || notification.url || window.location.href,
        },
        tag: `shopix-directory-${notification.id || Date.now()}`,
      };

      if (registration && typeof registration.showNotification === 'function') {
        await registration.showNotification(notification.title || 'Notificación', notificationOptions);
        return;
      }

      const nativeNotification = new Notification(notification.title || 'Notificación', notificationOptions);
      nativeNotification.onclick = () => {
        window.focus();
        window.location.href = notificationOptions.data.url;
        nativeNotification.close();
      };
    }

    async function requestDirectoryBrowserNotificationPermission() {
      const token = getDirectoryAuthToken();
      const user = getDirectoryAuthUser();

      if (!token || !user?.id) {
        return;
      }

      if (!supportsDirectoryBrowserNotifications()) {
        return;
      }

      if (!directoryVapidPublicKey || Notification.permission === 'denied') {
        return;
      }

      const permission = await Notification.requestPermission();
      if (permission !== 'granted') {
        return;
      }

      await syncDirectoryBrowserPushSubscription(token);
      await showDirectoryBrowserNotification({
        title: 'Alertas activadas',
        message: 'Este dispositivo ya puede recibir notificaciones de Shopix.',
        target_url: window.location.href,
      }, { force: true });
    }

    function maybeAutoRequestDirectoryBrowserNotificationPermission() {
      const token = getDirectoryAuthToken();
      const user = getDirectoryAuthUser();

      if (directoryNotificationAutoPrompted || !token || !user?.id || !isDirectoryStandaloneMode()) {
        return;
      }

      if (!supportsDirectoryBrowserNotifications() || !directoryVapidPublicKey || Notification.permission !== 'default') {
        return;
      }

      directoryNotificationAutoPrompted = true;
      requestDirectoryBrowserNotificationPermission().catch(() => {
        directoryNotificationAutoPrompted = false;
      });
    }

    function updateDirectoryInstallPwaUi() {
      if (!directoryInstallPwaBtn) {
        return;
      }

      const setInstallButtonLabel = (text) => {
        if (directoryInstallPwaLabel) {
          directoryInstallPwaLabel.textContent = text;
          return;
        }

        directoryInstallPwaBtn.textContent = text;
      };

      directoryInstallPwaBtn.classList.remove('d-none');

      if (isDirectoryStandaloneMode()) {
        setInstallButtonLabel('App instalada');
        directoryInstallPwaBtn.classList.add('is-ready');
        return;
      }

      directoryInstallPwaBtn.classList.remove('is-ready');

      if (isDirectoryIosDevice()) {
        setInstallButtonLabel('Agregar a inicio');
        return;
      }

      setInstallButtonLabel('Instalar app');
    }

    async function installDirectoryPwa() {
      if (!directoryInstallPwaBtn || isDirectoryStandaloneMode()) {
        return;
      }

      if (isDirectoryIosDevice()) {
        alert('En iPhone o iPad, abre Shopix en Safari desde /landings, toca Compartir y luego selecciona "Agregar a pantalla de inicio". El acceso instalado abrirá el inicio del directorio de Shopix.');
        return;
      }

      if (!window.__shopixDirectoryDeferredInstallPrompt) {
        alert('La instalación aún no está disponible en este navegador. Recarga la página, usa HTTPS y asegúrate de que Shopix no esté ya instalado.');
        return;
      }

      window.__shopixDirectoryDeferredInstallPrompt.prompt();
      const choice = await window.__shopixDirectoryDeferredInstallPrompt.userChoice.catch(() => null);
      window.__shopixDirectoryDeferredInstallPrompt = null;
      updateDirectoryInstallPwaUi();

      if (choice?.outcome === 'accepted') {
        alert('La instalación de Shopix se inició correctamente. La app abrirá desde /landings.');
      }
    }

    function getDirectoryAuthUser() {
      try {
        return JSON.parse(localStorage.getItem(directoryAuthUserKey) || 'null');
      } catch (error) {
        return null;
      }
    }

    function clearDirectoryAuthData() {
      localStorage.removeItem(directoryAuthTokenKey);
      localStorage.removeItem(directoryAuthUserKey);
      applyDirectoryAuthState();
      fillDirectoryAccount(null);
    }

    async function refreshDirectoryAuthSession(token) {
      const response = await fetch('/api/user', {
        method: 'GET',
        headers: {
          'Accept': 'application/json',
          'Authorization': `Bearer ${token}`,
        },
      });

      const data = await response.json().catch(() => ({}));

      if (response.ok && data?.user?.id) {
        return {
          user: data.user,
          shouldClear: false,
        };
      }

      return {
        user: null,
        shouldClear: response.status === 401 || response.status === 404,
      };
    }

    function setDirectoryAuthData(token, user) {
      localStorage.setItem(directoryAuthTokenKey, token || '');
      localStorage.setItem(directoryAuthUserKey, JSON.stringify(user || null));
      applyDirectoryAuthState();
      fillDirectoryAccount(user || null);
    }

    function clearDirectoryAuthError() {
      if (!directoryAuthError) {
        return;
      }

      directoryAuthError.textContent = '';
      directoryAuthError.classList.add('d-none');
    }

    function showDirectoryAuthError(message) {
      if (!directoryAuthError) {
        return;
      }

      directoryAuthError.textContent = message || 'No se pudo completar la autenticación.';
      directoryAuthError.classList.remove('d-none');
    }

    function openDirectoryAuthModal(targetUrl = '') {
      if (targetUrl) {
        sessionStorage.setItem(directoryPendingUrlKey, targetUrl);
      }

      clearDirectoryAuthError();
      directoryAuthModal?.show();
    }

    function consumeDirectoryPendingUrl() {
      const url = sessionStorage.getItem(directoryPendingUrlKey) || '';
      sessionStorage.removeItem(directoryPendingUrlKey);
      return url;
    }

    function applyDirectoryAuthState() {
      const token = getDirectoryAuthToken();
      const user = getDirectoryAuthUser();
      const hasSession = !!token && !!user?.id;
      const landingHeader = document.querySelector('.landing-header');

      directoryClientLoginWrap?.classList.toggle('d-none', hasSession);
      directoryClientSessionWrap?.classList.toggle('d-none', !hasSession);
      directoryClientNotificationsWrap?.classList.toggle('d-none', !hasSession);
      directoryClientOrdersWrap?.classList.toggle('d-none', !hasSession);
      directoryClientAccountWrap?.classList.toggle('d-none', !hasSession);
      directoryClientLogoutWrap?.classList.toggle('d-none', !hasSession);
      landingHeader?.classList.toggle('is-authenticated', hasSession);

      if (directoryClientSessionName) {
        directoryClientSessionName.textContent = hasSession ? (user.name || 'cliente') : 'Cliente';
      }

      if (directoryClientMenuName) {
        directoryClientMenuName.textContent = hasSession ? (user.name || 'cliente') : 'cliente';
      }

      if (!hasSession && directoryClientNotificationsCount) {
        directoryClientNotificationsCount.textContent = '0';
        directoryClientNotificationsCount.classList.add('d-none');
      }
    }

    async function initializeDirectoryAuthState() {
      applyDirectoryAuthState();
      fillDirectoryAccount(getDirectoryAuthUser());

      const token = getDirectoryAuthToken();
      if (!token) {
        return;
      }

      try {
        const session = await refreshDirectoryAuthSession(token);
        if (session.shouldClear) {
          clearDirectoryAuthData();
          return;
        }

        if (session.user?.id) {
          setDirectoryAuthData(token, session.user);
        }
      } catch (error) {
      }
    }

    function fillDirectoryAccount(user) {
      const nameElement = document.getElementById('directory-client-account-name');
      const emailElement = document.getElementById('directory-client-account-email');
      const dniElement = document.getElementById('directory-client-account-dni');
      const phoneElement = document.getElementById('directory-client-account-phone');
      const phoneInputElement = document.getElementById('directory-client-phone-input');
      const locationElement = document.getElementById('directory-client-account-location');
      const coordinatesElement = document.getElementById('directory-client-account-coordinates');
      const latitude = user?.latitude !== null && user?.latitude !== undefined ? Number(user.latitude) : null;
      const longitude = user?.longitude !== null && user?.longitude !== undefined ? Number(user.longitude) : null;
      const locationParts = [
        getDirectorySelectedText(directoryClientCountrySelect),
        getDirectorySelectedText(directoryClientStateSelect),
        getDirectorySelectedText(directoryClientCitySelect),
        user?.address || '',
      ].filter(Boolean);

      if (nameElement) nameElement.textContent = user?.name || '-';
      if (emailElement) emailElement.textContent = user?.email || '-';
      if (dniElement) dniElement.textContent = user?.dni || 'No registrado';
      if (phoneElement) phoneElement.textContent = user?.phone_number || user?.phone || 'No registrado';
      if (phoneInputElement) phoneInputElement.value = user?.phone_number || user?.phone || '';
      if (directoryClientAddressInput) directoryClientAddressInput.value = user?.address || '';
      if (directoryClientLatitudeInput) directoryClientLatitudeInput.value = user?.latitude ?? '';
      if (directoryClientLongitudeInput) directoryClientLongitudeInput.value = user?.longitude ?? '';
      if (locationElement) locationElement.textContent = locationParts.length ? locationParts.join(' · ') : 'No registrada';
      if (coordinatesElement) coordinatesElement.textContent = Number.isFinite(latitude) && Number.isFinite(longitude)
        ? `${latitude.toFixed(6)}, ${longitude.toFixed(6)}`
        : 'No registradas';
      updateDirectoryLocationStatus(latitude, longitude);

      initDirectoryLocationSelectors(user).then(() => {
        const refreshedLocationParts = [
          getDirectorySelectedText(directoryClientCountrySelect),
          getDirectorySelectedText(directoryClientStateSelect),
          getDirectorySelectedText(directoryClientCitySelect),
          user?.address || '',
        ].filter(Boolean);

        if (locationElement) {
          locationElement.textContent = refreshedLocationParts.length ? refreshedLocationParts.join(' · ') : 'No registrada';
        }
      }).catch(() => {
      });
    }

    async function fetchDirectoryNotifications(token) {
      const response = await fetch('/api/notifications', {
        method: 'GET',
        headers: {
          'Accept': 'application/json',
          'Authorization': `Bearer ${token}`,
        },
      });

      if (!response.ok) {
        throw new Error('No se pudieron cargar notificaciones.');
      }

      return response.json();
    }

    async function markDirectoryNotificationAsRead(token, id) {
      await fetch(`/api/notifications/${id}/read`, {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'Authorization': `Bearer ${token}`,
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
      });
    }

    async function fetchDirectoryOrders(token) {
      const response = await fetch('/api/user/orders', {
        method: 'GET',
        headers: {
          'Accept': 'application/json',
          'Authorization': `Bearer ${token}`,
        },
      });

      if (!response.ok) {
        throw new Error('No se pudieron cargar las compras.');
      }

      return response.json();
    }

    function renderDirectoryNotifications(payload, token) {
      const unread = Number(payload?.unread_count || 0);
      if (directoryClientNotificationsCount) {
        directoryClientNotificationsCount.textContent = String(unread);
        directoryClientNotificationsCount.classList.toggle('d-none', unread <= 0);
      }

      const rows = Array.isArray(payload?.notifications) ? payload.notifications : [];
      if (!directoryClientNotificationsList) {
        return;
      }

      if (rows.length === 0) {
        directoryClientNotificationsList.innerHTML = '<p class="text-muted mb-0">No tienes notificaciones.</p>';
        return;
      }

      directoryClientNotificationsList.innerHTML = rows.map(row => {
        const actionButton = row.is_read
          ? '<span class="badge bg-success">Leída</span>'
          : `<button type="button" class="btn btn-sm btn-outline-dark" data-directory-mark-read="${row.id}">Marcar leída</button>`;

        return `
          <div class="directory-list-card ${row.is_read ? '' : 'border-dark'}">
            <div class="d-flex justify-content-between align-items-start gap-2">
              <div>
                <div class="fw-semibold">${row.title || 'Notificación'}</div>
                <div class="small text-muted">${row.message || ''}</div>
                <div class="small text-secondary mt-1">${row.created_at || ''}</div>
              </div>
              <div class="d-flex flex-column gap-1 align-items-end">
                ${row.target_url ? `<a href="${row.target_url}" class="btn btn-sm btn-dark">Abrir</a>` : ''}
                ${actionButton}
              </div>
            </div>
          </div>
        `;
      }).join('');

      directoryClientNotificationsList.querySelectorAll('[data-directory-mark-read]').forEach(button => {
        button.addEventListener('click', async () => {
          const id = button.getAttribute('data-directory-mark-read');
          await markDirectoryNotificationAsRead(token, id);
          const updated = await fetchDirectoryNotifications(token);
          renderDirectoryNotifications(updated, token);
        });
      });
    }

    function directoryOrderStatusLabel(status) {
      if (Number(status) === 1) return 'Aprobado';
      if (Number(status) === 2) return 'Negado';
      return 'En proceso';
    }

    function directoryDeliveryStatusLabel(status) {
      if (Number(status) === 1) return 'Entregado';
      if (Number(status) === 3) return 'En despacho / En vía';
      if (Number(status) === 2) return 'Cancelado';
      return 'Pendiente';
    }

    function renderDirectoryOrders(payload) {
      const rows = Array.isArray(payload?.orders) ? payload.orders : [];
      if (!directoryClientOrdersList) {
        return;
      }

      if (rows.length === 0) {
        directoryClientOrdersList.innerHTML = '<p class="text-muted mb-0">Todavía no tienes compras registradas.</p>';
        return;
      }

      directoryClientOrdersList.innerHTML = rows.map(row => `
        <article class="directory-list-card">
          <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap mb-2">
            <div>
              <div class="fw-semibold fs-6">Pedido #${row.id}</div>
              <div class="directory-list-meta"><strong>Tienda:</strong> ${row.tenant_name || 'No disponible'}${row.date ? ` • ${row.date}` : ''}</div>
            </div>
            <a href="${row.public_url}" class="btn btn-sm btn-outline-dark">Ver detalle</a>
          </div>
          <div class="directory-list-meta mb-1"><strong>${row.items_count || 0}</strong> item(s) • <strong>${Number(row.total || 0).toFixed(2)} $</strong></div>
          <div class="directory-list-meta mb-1">Pedido: ${directoryOrderStatusLabel(row.status)}</div>
          <div class="directory-list-meta">Entrega: ${directoryDeliveryStatusLabel(row.deliver_status)}</div>
        </article>
      `).join('');
    }

    async function syncDirectoryClientData() {
      const token = getDirectoryAuthToken();
      const user = getDirectoryAuthUser();

      if (!token || !user?.id) {
        return;
      }

      try {
        const payload = await fetchDirectoryNotifications(token);
        renderDirectoryNotifications(payload, token);
      } catch (error) {
        if (directoryClientNotificationsList) {
          directoryClientNotificationsList.innerHTML = '<p class="text-danger mb-0">No se pudieron cargar notificaciones.</p>';
        }
      }
    }

    async function submitDirectoryProfileUpdate(event) {
      event.preventDefault();

      const token = getDirectoryAuthToken();
      const user = getDirectoryAuthUser();
      if (!token || !user?.id) {
        return;
      }

      const phoneNumber = (document.getElementById('directory-client-phone-input')?.value || '').trim();
      const address = (directoryClientAddressInput?.value || '').trim();
      const latitude = directoryClientLatitudeInput?.value ? Number(directoryClientLatitudeInput.value) : null;
      const longitude = directoryClientLongitudeInput?.value ? Number(directoryClientLongitudeInput.value) : null;
      const response = await fetch('/api/user/update-profile', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'Authorization': `Bearer ${token}`,
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
        body: JSON.stringify({
          phone_number: phoneNumber,
          address,
          country_id: directoryClientCountrySelect?.value || null,
          state_id: directoryClientStateSelect?.value || null,
          city_id: directoryClientCitySelect?.value || null,
          latitude: Number.isFinite(latitude) ? latitude : null,
          longitude: Number.isFinite(longitude) ? longitude : null,
        }),
      });

      const data = await response.json().catch(() => ({}));
      if (!response.ok) {
        alert(data.message || 'No se pudo actualizar el teléfono.');
        return;
      }

      const updatedUser = data?.user || {
        ...user,
        phone_number: phoneNumber || null,
        address: address || null,
        country_id: directoryClientCountrySelect?.value ? Number(directoryClientCountrySelect.value) : null,
        state_id: directoryClientStateSelect?.value ? Number(directoryClientStateSelect.value) : null,
        city_id: directoryClientCitySelect?.value ? Number(directoryClientCitySelect.value) : null,
        latitude: Number.isFinite(latitude) ? latitude : null,
        longitude: Number.isFinite(longitude) ? longitude : null,
      };
      setDirectoryAuthData(token, updatedUser);
      fillDirectoryAccount(updatedUser);
      alert(data.message || 'Perfil actualizado correctamente.');
    }

    async function submitDirectoryPasswordUpdate(event) {
      event.preventDefault();

      const token = getDirectoryAuthToken();
      const user = getDirectoryAuthUser();
      if (!token || !user?.id) {
        return;
      }

      const current_password = document.getElementById('directory-client-current-password')?.value || '';
      const new_password = document.getElementById('directory-client-new-password')?.value || '';
      const new_password_confirmation = document.getElementById('directory-client-new-password-confirmation')?.value || '';

      if (new_password !== new_password_confirmation) {
        alert('La confirmación de la nueva contraseña no coincide.');
        return;
      }

      const response = await fetch('/api/user/change-password', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'Authorization': `Bearer ${token}`,
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
        body: JSON.stringify({
          current_password,
          new_password,
          new_password_confirmation,
        }),
      });

      const data = await response.json().catch(() => ({}));
      if (!response.ok) {
        alert(data.message || 'No se pudo actualizar la contraseña.');
        return;
      }

      event.target.reset();
      alert(data.message || 'Contraseña actualizada correctamente.');
    }

    async function submitDirectoryClientLogin(event) {
      event.preventDefault();
      clearDirectoryAuthError();

      const loginType = document.getElementById('directory-client-login-type')?.value || 'name';
      const login = document.getElementById('directory-client-login-identifier')?.value.trim() || '';

      const response = await fetch('/api/loginEcomm', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
        body: JSON.stringify({
          login,
          login_type: loginType,
          password: document.getElementById('directory-client-login-password')?.value || '',
        })
      });

      const data = await response.json().catch(() => ({}));
      if (!response.ok || !data.token || !data.user) {
        showDirectoryAuthError(data.message || 'No se pudo iniciar sesión como cliente.');
        return;
      }

      setDirectoryAuthData(data.token, data.user);
      directoryAuthModal?.hide();
      directoryClientLoginForm?.reset();
      fillDirectoryAccount(data.user);
      syncDirectoryClientData();
      setTimeout(() => {
        maybeAutoRequestDirectoryBrowserNotificationPermission();
      }, 120);

      const pendingUrl = consumeDirectoryPendingUrl();
      if (pendingUrl) {
        window.location.href = pendingUrl;
      }
    }

    function syncDirectoryLoginPlaceholder() {
      const loginTypeSelect = document.getElementById('directory-client-login-type');
      const loginInput = document.getElementById('directory-client-login-identifier');

      if (!loginTypeSelect || !loginInput) {
        return;
      }

      const placeholderByType = {
        name: 'Nombre',
        email: 'Correo electrónico',
        dni: 'DNI o cédula',
      };

      const selectedType = String(loginTypeSelect.value || 'name');
      loginInput.placeholder = placeholderByType[selectedType] || 'Nombre';
    }

    async function submitDirectoryClientRegister(event) {
      event.preventDefault();
      clearDirectoryAuthError();

      const payload = {
        name: document.getElementById('directory-client-register-name')?.value.trim() || '',
        email: document.getElementById('directory-client-register-email')?.value.trim() || '',
        password: document.getElementById('directory-client-register-password')?.value || '',
        password_confirmation: document.getElementById('directory-client-register-password-confirmation')?.value || '',
        dni: document.getElementById('directory-client-register-dni')?.value.trim() || '',
        phone_number: document.getElementById('directory-client-register-phone')?.value.trim() || '',
      };

      const response = await fetch('/api/registerEcomm', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
        body: JSON.stringify(payload)
      });

      const data = await response.json().catch(() => ({}));
      if (!response.ok || !data.token || !data.user) {
        showDirectoryAuthError(data.message || 'No se pudo crear la cuenta de cliente.');
        return;
      }

      setDirectoryAuthData(data.token, data.user);
      directoryAuthModal?.hide();
      directoryClientRegisterForm?.reset();
      fillDirectoryAccount(data.user);
      syncDirectoryClientData();
      setTimeout(() => {
        maybeAutoRequestDirectoryBrowserNotificationPermission();
      }, 120);

      const pendingUrl = consumeDirectoryPendingUrl();
      if (pendingUrl) {
        window.location.href = pendingUrl;
      }
    }

    function buildLandingStateCityMap() {
      return landingDirectoryItems.reduce((map, item) => {
        const state = String(item.dataset.state || '').trim();
        const city = String(item.dataset.city || '').trim();

        if (!state || !city) {
          return map;
        }

        map[state] = map[state] || new Set();
        map[state].add(city);
        return map;
      }, {});
    }

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

    function updateLandingCityOptions(selectedState = '', preserveSelectedCity = true) {
      const normalizedState = String(selectedState || '').trim();
      const availableCities = normalizedState && landingStateCityMap[normalizedState]
        ? Array.from(landingStateCityMap[normalizedState]).sort((left, right) => left.localeCompare(right, 'es'))
        : @json((($tenantFilters['cities'] ?? collect())->values()->all()));

      (landingFilterGroups.city || []).forEach(select => {
        const currentValue = preserveSelectedCity ? select.value : '';
        const hasCurrentValue = availableCities.includes(currentValue);

        select.innerHTML = '';

        const defaultOption = document.createElement('option');
        defaultOption.value = '';
        defaultOption.textContent = normalizedState ? 'Todas en el estado' : 'Todas';
        select.appendChild(defaultOption);

        availableCities.forEach(city => {
          const option = document.createElement('option');
          option.value = city;
          option.textContent = city;
          select.appendChild(option);
        });

        select.value = hasCurrentValue ? currentValue : '';
      });

      if (normalizedState && !availableCities.includes(getLandingFilterValue('city'))) {
        syncLandingFilterValue('city', '');
      }
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
      const selectedState = normalizeText(getLandingFilterValue('state'));
      const selectedCity = normalizeText(getLandingFilterValue('city'));

      let visibleCount = 0;

      landingDirectoryItems.forEach(item => {
        const itemName = normalizeText(item.dataset.name);
        const itemSearch = normalizeText(item.dataset.search || item.textContent || '');
        const itemType = normalizeText(item.dataset.type);
        const itemActivity = normalizeText(item.dataset.activity);
        const itemState = normalizeText(item.dataset.state);
        const itemCity = normalizeText(item.dataset.city);

        const matches =
          (!selectedName || itemSearch.includes(selectedName) || itemName.includes(selectedName)) &&
          (!selectedType || itemType === selectedType) &&
          (!selectedActivity || itemActivity === selectedActivity) &&
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

          if (key === 'state') {
            updateLandingCityOptions(element.value, false);
          }

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

        updateLandingCityOptions('', false);
        applyLandingDirectoryFilters();
      });
    });

    const navbarCollapse = document.getElementById('landingNavbar');
    const navLinks = document.querySelectorAll('#landingNavbar .nav-link, #landingNavbar .btn');
    const bsCollapse = navbarCollapse ? new bootstrap.Collapse(navbarCollapse, { toggle: false }) : null;

    navLinks.forEach(link => {
      link.addEventListener('click', () => {
        if (link.getAttribute('data-bs-toggle') === 'dropdown') {
          return;
        }

        if (window.innerWidth < 992 && navbarCollapse?.classList.contains('show') && bsCollapse) {
          bsCollapse.hide();
        }
      });
    });

    directoryClientLoginBtn?.addEventListener('click', () => {
      openDirectoryAuthModal();
    });

    directoryClientNotificationsBtn?.addEventListener('click', async () => {
      const token = getDirectoryAuthToken();
      const user = getDirectoryAuthUser();
      if (!token || !user?.id) {
        openDirectoryAuthModal();
        return;
      }

      directoryClientNotificationsModal?.show();
      try {
        const payload = await fetchDirectoryNotifications(token);
        renderDirectoryNotifications(payload, token);
      } catch (error) {
        if (directoryClientNotificationsList) {
          directoryClientNotificationsList.innerHTML = '<p class="text-danger mb-0">No se pudieron cargar notificaciones.</p>';
        }
      }
    });

    directoryClientOrdersBtn?.addEventListener('click', async (event) => {
      event.preventDefault();
      const token = getDirectoryAuthToken();
      const user = getDirectoryAuthUser();
      if (!token || !user?.id) {
        openDirectoryAuthModal();
        return;
      }

      const customerPortalUrl = buildDirectoryCustomerPortalUrl('#compras');
      window.location.href = customerPortalUrl;
    });

    directoryClientAccountBtn?.addEventListener('click', (event) => {
      event.preventDefault();
      const token = getDirectoryAuthToken();
      const user = getDirectoryAuthUser();
      if (!token || !user?.id) {
        openDirectoryAuthModal();
        return;
      }

      const customerPortalUrl = buildDirectoryCustomerPortalUrl('#perfil');
      window.location.href = customerPortalUrl;
    });

    directoryClientLogoutBtn?.addEventListener('click', () => {
      clearDirectoryAuthData();
      sessionStorage.removeItem(directoryPendingUrlKey);
    });

    directoryClientLoginForm?.addEventListener('submit', submitDirectoryClientLogin);
    document.getElementById('directory-client-login-type')?.addEventListener('change', syncDirectoryLoginPlaceholder);
    directoryClientRegisterForm?.addEventListener('submit', submitDirectoryClientRegister);
    bindDirectoryLocationSelectors();
    directoryClientUseCurrentLocationBtn?.addEventListener('click', requestDirectoryCurrentLocation);
    directoryClientProfileForm?.addEventListener('submit', submitDirectoryProfileUpdate);
    directoryClientPasswordForm?.addEventListener('submit', submitDirectoryPasswordUpdate);
    directoryInstallPwaBtn?.addEventListener('click', installDirectoryPwa);

    window.addEventListener('beforeinstallprompt', event => {
      event.preventDefault();
      window.__shopixDirectoryDeferredInstallPrompt = event;
      updateDirectoryInstallPwaUi();
    });

    window.addEventListener('appinstalled', () => {
      window.__shopixDirectoryDeferredInstallPrompt = null;
      updateDirectoryInstallPwaUi();
      setTimeout(() => {
        maybeAutoRequestDirectoryBrowserNotificationPermission();
      }, 220);
    });

    window.addEventListener('storage', event => {
      if (event.key !== directoryAuthTokenKey && event.key !== directoryAuthUserKey) {
        return;
      }

      applyDirectoryAuthState();
      fillDirectoryAccount(getDirectoryAuthUser());
    });

    syncDirectoryLoginPlaceholder();

    updateLandingCityOptions(getLandingFilterValue('state'));
    initializeDirectoryAuthState().catch(() => {
      applyDirectoryAuthState();
      fillDirectoryAccount(getDirectoryAuthUser());
    });
    syncDirectoryClientData();
    updateDirectoryInstallPwaUi();
    ensureDirectoryServiceWorkerRegistration().catch(() => {});

    if (supportsDirectoryBrowserNotifications() && Notification.permission === 'granted' && getDirectoryAuthToken() && getDirectoryAuthUser()?.id) {
      syncDirectoryBrowserPushSubscription(getDirectoryAuthToken()).catch(() => {});
    }

    setTimeout(() => {
      maybeAutoRequestDirectoryBrowserNotificationPermission();
    }, 180);
    applyLandingDirectoryFilters();
  </script>
  @include('partials.module-help-client-tour')
</body>

</html>
