<!--
=========================================================
* Material Dashboard 3 - v3.2.0
=========================================================

* Product Page: https://www.creative-tim.com/product/material-dashboard
* Copyright 2024 Creative Tim (https://www.creative-tim.com)
* Licensed under MIT (https://www.creative-tim.com/license)
* Coded by Creative Tim

=========================================================

* The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
-->
<!DOCTYPE html>
<html lang="en">

<head>
  @php($pwaIconVersion = (string) config('app.asset_version', '20260710'))
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <link rel="apple-touch-icon" sizes="180x180" href="{{ route('pwa.icon', ['size' => 180, 'variant' => 'admin', 'v' => $pwaIconVersion]) }}">
  <link rel="icon" type="image/png" href="{{ route('pwa.icon', ['size' => 192, 'variant' => 'admin', 'v' => $pwaIconVersion]) }}">
  <title>
  </title>
  <!--     Fonts and icons     -->
  <link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700,900" />
  <!-- Nucleo Icons -->
  <link href="{{ asset('assets/css/nucleo-icons.css') }}" rel="stylesheet">
  <link href="{{ asset('assets/css/nucleo-svg.css') }}" rel="stylesheet">
  <!-- Font Awesome Icons -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.2/css/all.min.css">
  <!-- Material Icons -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
  <!-- CSS Files -->
  <link href="{{ asset('assets/css/material-dashboard.css?v=3.2.0') }}" rel="stylesheet">
</head>

<body class="g-sidenav-show  bg-gray-100">
<style>
  #navbarBlur {
    position: relative;
    z-index: 2;
    background: rgba(255, 255, 255, 0.92);
    backdrop-filter: saturate(180%) blur(6px);
    -webkit-backdrop-filter: saturate(180%) blur(6px);
    border-bottom: 1px solid rgba(148, 163, 184, 0.2);
    margin-bottom: 0.35rem;
  }

  #navbarBlur .container-fluid {
    display: flex;
    align-items: center;
    gap: 0.5rem;
  }

  #navbarBlur .header-actions-wrap {
    margin-left: auto;
    flex: 1 1 auto;
    display: flex;
    justify-content: flex-end;
  }

  #navbarBlur .header-actions-wrap .navbar-nav {
    margin-left: auto;
    display: flex;
    flex-direction: row;
    flex-wrap: wrap;
    align-items: center;
    justify-content: flex-end;
    width: auto;
    row-gap: 0.4rem;
  }

  .header-session-user {
    min-width: 0;
    flex: 0 1 460px;
    max-width: min(48vw, 460px);
    display: flex;
    align-items: center;
    justify-content: center;
    position: relative;
    overflow: visible;
  }

  .header-session-user .header-session-user-inner {
    min-width: 0;
    max-width: 100%;
    white-space: nowrap;
    overflow: hidden;
    justify-content: center;
    text-align: center;
  }

  .header-session-user.has-tenant-scope {
    flex: 0 1 auto;
    max-width: none;
  }

  .header-session-user.has-tenant-scope .header-session-user-inner {
    overflow: visible;
  }

  .header-session-user .session-name {
    font-size: 0.78rem;
    font-weight: 700;
    color: #1f2937;
    line-height: 1.1;
  }

  .header-session-user .session-meta {
    font-size: 0.68rem;
    color: #6b7280;
    line-height: 1.1;
  }

  .header-session-user .session-text {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    display: block;
    min-width: 0;
  }

  .header-session-user .session-name {
    max-width: 150px;
  }

  .header-session-user .session-meta-email {
    max-width: 170px;
  }

  .superowner-tenant-scope-form {
    min-width: 240px;
    max-width: 360px;
  }

  .superowner-tenant-scope-form .form-select {
    min-height: 34px;
    font-size: 0.75rem;
    border-radius: 0.5rem;
    border-color: rgba(52, 71, 103, 0.25);
  }

  .header-session-user .session-meta-role {
    max-width: 120px;
  }

  .header-session-user .session-tenant-scope-inline {
    flex: 0 0 auto;
    margin-right: 0.4rem;
  }

  .header-session-user .session-tenant-scope-inline .superowner-tenant-scope-form {
    min-width: 220px;
    max-width: 300px;
  }

  .header-session-user .session-tenant-scope-inline .superowner-tenant-scope-form .form-select {
    width: 100%;
  }

  .header-session-user .session-sep {
    flex: 0 0 auto;
    color: #9ca3af;
    font-size: 0.68rem;
    line-height: 1;
  }

  .header-user-badge-toggle {
    border: 0;
    background: transparent;
    padding: 0;
    margin: 0;
    color: inherit;
    line-height: 1;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
  }

  .header-user-badge-toggle:focus-visible {
    outline: 2px solid rgba(59, 130, 246, 0.65);
    outline-offset: 2px;
    border-radius: 999px;
  }

  .header-user-badge {
    position: absolute;
    top: calc(100% + 0.4rem);
    right: 0;
    min-width: 220px;
    max-width: min(90vw, 320px);
    background: #ffffff;
    border: 1px solid rgba(148, 163, 184, 0.35);
    border-radius: 0.6rem;
    box-shadow: 0 10px 28px rgba(15, 23, 42, 0.16);
    padding: 0.6rem 0.75rem;
    display: none;
    z-index: 1200;
  }

  .header-user-badge.is-open {
    display: block;
  }

  .header-user-badge-title {
    font-size: 0.72rem;
    font-weight: 700;
    color: #111827;
    margin-bottom: 0.35rem;
  }

  .header-user-badge-item {
    font-size: 0.7rem;
    color: #374151;
    line-height: 1.35;
    word-break: break-word;
  }

  .header-user-badge-item + .header-user-badge-item {
    margin-top: 0.18rem;
  }

  .header-icon-item {
    flex: 0 0 auto;
  }

  .header-notification-optin {
    border: 1px solid rgba(52, 71, 103, 0.18);
    border-radius: 999px;
    padding: 0.35rem 0.7rem !important;
    font-size: 0.78rem;
    font-weight: 600;
    line-height: 1;
  }

  .header-notification-optin.is-ready {
    border-color: rgba(34, 197, 94, 0.35);
    background: rgba(34, 197, 94, 0.08);
    color: #166534 !important;
  }

  .header-pwa-install {
    border: 1px solid rgba(52, 71, 103, 0.18);
    border-radius: 999px;
    padding: 0.35rem 0.7rem !important;
    font-size: 0.78rem;
    font-weight: 600;
    line-height: 1;
  }

  .header-pwa-install.is-ready {
    border-color: rgba(59, 130, 246, 0.35);
    background: rgba(59, 130, 246, 0.08);
    color: #1d4ed8 !important;
  }

  @media (max-width: 576px) {
    #navbarBlur .container-fluid {
      align-items: flex-start;
      flex-wrap: nowrap;
    }

    #navbarBlur .header-actions-wrap {
      width: auto;
      margin-left: 0;
      margin-top: 0 !important;
      min-width: 0;
    }

    #navbarBlur .header-actions-wrap .navbar-nav {
      width: auto;
      justify-content: flex-end;
      gap: 0.35rem;
      flex-wrap: nowrap;
    }

    .header-session-user {
      order: 0;
      flex: 1 1 auto;
      max-width: none;
      margin-right: 0.15rem !important;
      justify-content: flex-end;
    }

    .header-session-user.has-tenant-scope {
      flex: 1 1 auto;
      max-width: calc(100vw - 120px);
    }

    .header-session-user.has-tenant-scope .header-session-user-inner {
      justify-content: flex-end;
      width: auto;
      max-width: 100%;
      text-align: right;
      white-space: nowrap;
      overflow: visible;
      align-items: center;
      gap: 0.3rem;
    }

    .header-session-user.has-tenant-scope .session-tenant-scope-inline {
      flex: 1 1 auto;
      min-width: 170px;
      max-width: calc(100vw - 165px);
      margin-right: 0.25rem;
    }

    .header-session-user.has-tenant-scope .session-tenant-scope-inline .superowner-tenant-scope-form {
      min-width: 100%;
      max-width: none;
      width: 100%;
    }

    .header-session-user.has-tenant-scope .session-name,
    .header-session-user.has-tenant-scope .session-meta,
    .header-session-user.has-tenant-scope .session-sep {
      display: none;
    }

    .header-session-user.has-tenant-scope .header-user-badge-toggle {
      display: inline-flex;
      align-items: center;
      justify-content: center;
      margin-right: 0.05rem;
      flex: 0 0 auto;
    }

    .header-session-user.has-tenant-scope .header-user-badge-toggle .material-symbols-rounded {
      font-size: 1.25rem;
      line-height: 1;
    }

    .header-session-user .session-name {
      font-size: 0.72rem;
    }

    .header-session-user .session-meta {
      font-size: 0.64rem;
    }

    .header-session-user .session-meta:not(:last-child) {
      display: none;
    }

    .header-notification-optin,
    .header-pwa-install {
      padding: 0.32rem 0.55rem !important;
      font-size: 0.72rem;
      white-space: nowrap;
    }
  }

  @media (max-width: 991px) {
    #navbarBlur .header-actions-wrap {
      width: auto;
      min-width: 0;
    }

    #navbarBlur .header-actions-wrap .navbar-nav {
      gap: 0.45rem;
    }

    .header-session-user {
      max-width: 150px;
      margin-right: 0.5rem !important;
      justify-content: flex-end;
    }

    .header-session-user.has-tenant-scope {
      max-width: min(72vw, 560px);
    }

    .header-session-user.has-tenant-scope .header-session-user-inner {
      overflow: visible;
    }

    .header-session-user.has-tenant-scope .session-tenant-scope-inline {
      min-width: 240px;
      max-width: min(58vw, 420px);
      margin-right: 0.45rem;
    }

    .header-session-user.has-tenant-scope .session-tenant-scope-inline .superowner-tenant-scope-form {
      min-width: 100%;
      max-width: none;
      width: 100%;
    }

    .header-session-user .session-meta {
      display: none;
    }

    .header-session-user .session-sep {
      display: none;
    }

    .header-session-user .session-name {
      font-size: 0.72rem;
      max-width: 100%;
    }
  }

  @media (max-width: 1250px) {
    .header-session-user .session-meta-role,
    .header-session-user .session-sep-role {
      display: none;
    }
  }

  @media (max-width: 1140px) {
    .header-session-user .session-meta-email,
    .header-session-user .session-sep-email {
      display: none;
    }

    .superowner-tenant-scope-form {
      min-width: 190px;
      max-width: 240px;
    }
  }

  @media (max-width: 460px) {
    .header-session-user.has-tenant-scope .session-tenant-scope-inline {
      min-width: 150px;
      max-width: calc(100vw - 160px);
    }

    .header-user-badge {
      right: -18px;
      min-width: 200px;
      max-width: min(92vw, 300px);
    }
  }
</style>
<nav class="navbar navbar-main navbar-expand-lg px-0  shadow-none border-radius-xl" id="navbarBlur" data-scroll="true">
      <div class="container-fluid py-1">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb bg-transparent mb-0 pb-0 pt-1 px-0 me-sm-6 me-5">
            <!-- Botón para desktop (toggle con icono que cambia) -->
            <li>
              <button id="btnDesktopNav" class="btn btn-black top-0 start-0 m-2 z-index-3 d-none d-lg-inline-block">
                <i class="material-symbols-rounded">menu</i>
              </button>
            </li>

            <li class="breadcrumb-item text-sm d-flex align-items-center">
              <a class="opacity-5 text-dark" href="/paymentMethods">
                <!-- Tasa Actual disponible en /paymentMethods -->
              </a>
            </li>

            <!-- Botones solo para móvil -->
            <li class="d-lg-none">
              <!-- Abrir -->
              <button id="btnOpenNav" class="btn btn-black top-0 start-0 m-2 z-index-3">
                <i class="material-symbols-rounded">menu</i>
              </button>
              <!-- Cerrar -->
              <button id="btnCloseNav" class="btn btn-black top-0 start-0 m-2 z-index-3" style="display:none;">
                <i class="material-symbols-rounded">close</i>
              </button>
            </li>
          </ol>
        </nav>

        <div class="navbar-collapse mt-sm-0 mt-2 me-md-0 me-sm-4 header-actions-wrap" id="navbar">
          <ul class="navbar-nav ms-auto pe-md-3 d-flex align-items-center justify-content-end">
            @php($headerAuthUser = auth()->user())
            @php($headerIsSuperOwner = auth()->check() && \App\Support\UserRedirector::isSuperAdmin($headerAuthUser))
            @php($headerTenantScopeOptions = collect($superOwnerTenantScopeOptions ?? []))
            @if($headerIsSuperOwner && $headerTenantScopeOptions->isEmpty())
              @php($headerTenantScopeOptions = \App\Models\Tenant::query()->orderBy('name')->get(['id', 'name']))
            @endif
            <li class="nav-item d-flex align-items-center me-3 header-session-user {{ $headerIsSuperOwner ? 'has-tenant-scope' : '' }}">
              <div class="d-flex text-end align-items-center gap-1 header-session-user-inner">
                @if($headerIsSuperOwner)
                  @php($headerActiveScopeId = (int) ($superOwnerTenantScopeId ?? session('superowner_tenant_scope_id', 0)))
                  @php($headerActiveScopeOption = $headerTenantScopeOptions->firstWhere('id', $headerActiveScopeId))
                  <div class="session-tenant-scope-inline">
                    <form method="POST" action="{{ route('superowner.tenant.scope.update') }}" class="superowner-tenant-scope-form">
                      @csrf
                      <select
                        name="tenant_scope_id"
                        class="form-select form-select-sm"
                        title="Tenant activa para módulos"
                        onchange="this.form.submit()"
                      >
                        <option value="0" {{ $headerActiveScopeId === 0 ? 'selected' : '' }}>GENERAL (todas las sedes)</option>
                        @foreach($headerTenantScopeOptions as $tenantOption)
                          <option value="{{ $tenantOption->id }}" {{ $headerActiveScopeId === (int) $tenantOption->id ? 'selected' : '' }}>
                            Tenant: {{ $tenantOption->name }}
                          </option>
                        @endforeach
                      </select>
                    </form>
                  </div>
                @endif
                <button
                  type="button"
                  class="header-user-badge-toggle"
                  id="headerUserBadgeToggle"
                  aria-label="Ver datos del usuario"
                  aria-expanded="false"
                  aria-controls="headerUserBadge"
                >
                  <i class="material-symbols-rounded flex-shrink-0">account_circle</i>
                </button>
                <span class="session-name session-text">{{ $authUserName ?? (string) (auth()->user()->name ?? 'Usuario') }}</span>
                <span class="session-sep session-sep-email">/</span>
                <span class="session-meta session-text session-meta-email">{{ $authUserEmail ?? (string) (auth()->user()->email ?? 'Sin correo') }}</span>
                <span class="session-sep session-sep-role">/</span>
                <span class="session-meta session-text session-meta-role">Rol: {{ $authUserRole ?? (string) (optional(auth()->user()?->role)->name ?? 'Sin rol') }}</span>
                <div class="header-user-badge" id="headerUserBadge" role="status" aria-live="polite">
                  <div class="header-user-badge-title">Datos del usuario</div>
                  <div class="header-user-badge-item"><strong>Nombre:</strong> {{ $authUserName ?? (string) (auth()->user()->name ?? 'Usuario') }}</div>
                  <div class="header-user-badge-item"><strong>Correo:</strong> {{ $authUserEmail ?? (string) (auth()->user()->email ?? 'Sin correo') }}</div>
                  <div class="header-user-badge-item"><strong>Rol:</strong> {{ $authUserRole ?? (string) (optional(auth()->user()?->role)->name ?? 'Sin rol') }}</div>
                </div>
              </div>
            </li>
            <li class="nav-item d-flex align-items-center me-2 header-icon-item">
              <a href="{{ route('notifications.index') }}" class="nav-link text-body p-0 position-relative" aria-label="Notificaciones" title="Notificaciones">
                <i class="material-symbols-rounded">notifications</i>
                @php($headerUnreadNotificationsCount = auth()->check() ? auth()->user()->unreadNotifications()->count() : 0)
                @if($headerUnreadNotificationsCount > 0)
                  <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger backoffice-notifications-count">{{ $headerUnreadNotificationsCount }}</span>
                @else
                  <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none backoffice-notifications-count">0</span>
                @endif
              </a>
            </li>
            <li class="nav-item d-flex align-items-center header-icon-item">
              <form method="POST" action="{{ route('logout') }}" class="m-0 p-0">
                @csrf
                <button
                  type="submit"
                  class="nav-link text-body p-0 border-0 bg-transparent"
                  aria-label="Cerrar sesión"
                  title="Cerrar sesión"
                >
                  <i class="material-symbols-rounded">logout</i>
                </button>
              </form>
            </li>
          </ul>
        </div>
      </div>
    </nav>
<!-- Core JS Files -->
<script src="{{ asset('assets/js/core/popper.min.js') }}"></script>
<script src="{{ asset('assets/js/core/bootstrap.min.js') }}"></script>

<!-- Github buttons -->
<script async defer src="https://buttons.github.io/buttons.js"></script>

<!-- Control Center for Material Dashboard: parallax effects, scripts for the example pages etc -->

<script>
document.addEventListener("DOMContentLoaded", function () {
    const sidenav = document.getElementById('sidenav-main');
  const userBadgeToggle = document.getElementById('headerUserBadgeToggle');
  const userBadge = document.getElementById('headerUserBadge');

    // Botones
    const btnDesktop = document.getElementById('btnDesktopNav');
    const btnOpen = document.getElementById('btnOpenNav');
    const btnClose = document.getElementById('btnCloseNav');

    // ==== Desktop ====
    if (btnDesktop) {
        btnDesktop.addEventListener('click', function () {
            sidenav.classList.toggle('closed');
            const icon = btnDesktop.querySelector('i');
            if (sidenav.classList.contains('closed')) {
                icon.textContent = 'menu_open';
            } else {
                icon.textContent = 'menu';
            }
        });
    }

    // ==== Móvil ====
    function initMobileState() {
        if (window.innerWidth < 992) {
            sidenav.classList.add('closed');
            btnOpen.style.display = "inline-block";
            btnClose.style.display = "none";
        }
    }

    btnOpen.addEventListener('click', function () {
        sidenav.classList.remove('closed');
        btnOpen.style.display = "none";
        btnClose.style.display = "inline-block";
    });

    btnClose.addEventListener('click', function () {
        sidenav.classList.add('closed');
        btnClose.style.display = "none";
        btnOpen.style.display = "inline-block";
    });

    // Al cargar y al redimensionar
    initMobileState();
    window.addEventListener('resize', initMobileState);

    if (userBadgeToggle && userBadge) {
      userBadgeToggle.addEventListener('click', function (event) {
        event.stopPropagation();
        const willOpen = !userBadge.classList.contains('is-open');
        userBadge.classList.toggle('is-open', willOpen);
        userBadgeToggle.setAttribute('aria-expanded', willOpen ? 'true' : 'false');
      });

      document.addEventListener('click', function (event) {
        if (!userBadge.contains(event.target) && !userBadgeToggle.contains(event.target)) {
          userBadge.classList.remove('is-open');
          userBadgeToggle.setAttribute('aria-expanded', 'false');
        }
      });

      document.addEventListener('keydown', function (event) {
        if (event.key === 'Escape') {
          userBadge.classList.remove('is-open');
          userBadgeToggle.setAttribute('aria-expanded', 'false');
        }
      });
    }
});
</script>

  <!-- Github buttons -->
  <script async defer src="https://buttons.github.io/buttons.js"></script>
  <!-- Control Center for Material Dashboard: parallax effects, scripts for the example pages etc -->
</body>

</html>