@php
  $tenantThemeModel = $tenant ?? null;

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

  $tenantColorPrimary = $normalizeTenantHex($tenantThemeModel->color_primary ?? null, '#0F172A');
  $tenantColorSecondary = $normalizeTenantHex($tenantThemeModel->color_secondary ?? null, '#334155');
  $tenantColorAccent = $normalizeTenantHex($tenantThemeModel->color_accent ?? null, '#38BDF8');

  [$tenantPrimaryR, $tenantPrimaryG, $tenantPrimaryB] = $toRgb($tenantColorPrimary);
  [$tenantSecondaryR, $tenantSecondaryG, $tenantSecondaryB] = $toRgb($tenantColorSecondary);
  [$tenantAccentR, $tenantAccentG, $tenantAccentB] = $toRgb($tenantColorAccent);
  $tenantAuthRedirect = request()->getRequestUri() ?: '/';
  $tenantCustomerPortalUrl = route('customer.portal.general');
  $tenantSocialProviders = [
    [
      'key' => 'google',
      'label' => 'Google',
      'icon' => 'bi bi-google',
      'enabled' => filled(config('services.google.client_id')) && filled(config('services.google.client_secret')) && filled(config('services.google.redirect')),
    ],
  ];
@endphp

<style>
  :root {
    --tenant-primary: {{ $tenantColorPrimary }};
    --tenant-secondary: {{ $tenantColorSecondary }};
    --tenant-accent: {{ $tenantColorAccent }};
    --tenant-primary-rgb: {{ $tenantPrimaryR }}, {{ $tenantPrimaryG }}, {{ $tenantPrimaryB }};
    --tenant-secondary-rgb: {{ $tenantSecondaryR }}, {{ $tenantSecondaryG }}, {{ $tenantSecondaryB }};
    --tenant-accent-rgb: {{ $tenantAccentR }}, {{ $tenantAccentG }}, {{ $tenantAccentB }};
  }

  .tenant-nav-action-btn {
    border: 1px solid rgba(255, 255, 255, 0.44);
    background: rgba(255, 255, 255, 0.14);
    color: #fff !important;
    min-height: 42px;
    padding-inline: 0.9rem;
    box-shadow: none;
  }

  .tenant-nav-action-btn:hover,
  .tenant-nav-action-btn:focus {
    border-color: rgba(255, 255, 255, 0.68);
    background: rgba(255, 255, 255, 0.24);
    color: #fff !important;
  }

  .landing-header.is-scrolled .tenant-nav-action-btn,
  #landingNavbar.show .tenant-nav-action-btn {
    background: #f8fafc;
    border-color: #d6e0ef;
    color: #1e293b !important;
  }

  .landing-header.is-scrolled .tenant-nav-action-btn:hover,
  .landing-header.is-scrolled .tenant-nav-action-btn:focus,
  #landingNavbar.show .tenant-nav-action-btn:hover,
  #landingNavbar.show .tenant-nav-action-btn:focus {
    background: #eef2ff;
    border-color: rgba(var(--tenant-accent-rgb), 0.46);
    color: #0f172a !important;
  }

  .tenant-nav-action-btn i {
    width: 1.65rem;
    height: 1.65rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 999px;
    font-size: 0.92rem;
    background: rgba(255, 255, 255, 0.22);
  }

  .landing-header.is-scrolled .tenant-nav-action-btn i,
  #landingNavbar.show .tenant-nav-action-btn i {
    background: #e8eef9;
  }

  .tenant-nav-action-btn .badge {
    border: 1px solid rgba(var(--tenant-accent-rgb), 0.4);
    background: var(--tenant-primary) !important;
    font-weight: 700;
  }
  
  .tenant-icon-btn .badge {
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
    z-index: 2;
  }

  .tenant-user-dropdown-btn {
    min-height: 42px;
    padding-right: 0.75rem;
    max-width: 230px;
    white-space: nowrap;
  }

  .tenant-user-name {
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 140px;
    display: inline-block;
    vertical-align: middle;
  }

  .tenant-icon-btn {
    width: 42px;
    height: 42px;
    min-width: 42px;
    padding: 0;
    justify-content: center;
    position: relative;
    overflow: visible;
  }

  .tenant-icon-btn i {
    margin: 0;
  }

  .tenant-user-menu {
    border: 1px solid #dbe3ee;
    border-radius: 14px;
    box-shadow: 0 16px 34px rgba(15, 23, 42, 0.14);
    padding: 0.35rem;
    min-width: 220px;
  }

  .tenant-user-menu .dropdown-item {
    border-radius: 10px;
    font-weight: 600;
    color: #1f2937;
    padding: 0.58rem 0.7rem;
  }

  .tenant-user-menu .dropdown-item:hover,
  .tenant-user-menu .dropdown-item:focus {
    background: #eef2ff;
    color: #111827;
  }

  .tenant-user-menu .dropdown-item.text-danger:hover,
  .tenant-user-menu .dropdown-item.text-danger:focus {
    background: #fef2f2;
    color: #b91c1c;
  }

  .tenant-order-card {
    border: 1px solid #e8eaed;
    border-radius: 12px;
    padding: 0.9rem;
    background: #fff;
  }

  .tenant-notification-permission-card {
    border: 1px solid #dbe3ee;
    border-radius: 12px;
    background: #f8fafc;
    padding: 0.9rem;
  }

  .tenant-notification-permission-card.is-ready {
    background: #ecfdf5;
    border-color: #a7f3d0;
  }

  .tenant-notification-permission-copy {
    font-size: 0.86rem;
    color: #475569;
    margin-bottom: 0;
  }

  .tenant-notification-card {
    border-radius: 12px;
  }

  .tenant-notification-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.5rem;
    align-items: center;
    justify-content: flex-end;
  }

  .tenant-notification-actions .btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    line-height: 1;
  }

  @media (max-width: 575.98px) {
    .tenant-notification-meta {
      width: 100%;
    }

    .tenant-notification-actions {
      width: 100%;
      justify-content: stretch;
    }

    .tenant-notification-actions .btn,
    .tenant-notification-actions .badge {
      width: 100%;
      justify-content: center;
      text-align: center;
    }
  }

  .tenant-order-meta {
    color: #5f6368;
    font-size: 0.9rem;
    line-height: 1.35;
  }

  .tenant-order-status-group {
    display: flex;
    flex-wrap: wrap;
    gap: 0.4rem;
  }

  .tenant-order-badge {
    font-size: 0.75rem;
    padding: 0.35rem 0.55rem;
    border-radius: 999px;
  }

  .tenant-appointment-state-group {
    display: flex;
    flex-wrap: wrap;
    gap: 0.35rem;
  }

  #tenantAppointmentsModal .modal-dialog {
    max-width: 620px;
  }

  #tenant-appointments-list {
    gap: 0.75rem !important;
  }

  .tenant-appointment-card {
    border-radius: 16px;
    border: 1px solid #dde5f0;
    background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    padding: 0.95rem;
  }

  .tenant-appointment-card.is-payment-pending {
    border-color: rgba(var(--tenant-accent-rgb), 0.35);
    box-shadow: inset 0 0 0 1px rgba(var(--tenant-accent-rgb), 0.1);
  }

  .tenant-appointment-title {
    font-size: 1.14rem;
    line-height: 1.2;
    font-weight: 700;
    letter-spacing: -0.01em;
    color: #111827;
  }

  .tenant-appointment-meta {
    color: #475569;
    font-size: 0.92rem;
    line-height: 1.35;
  }

  .tenant-appointment-paid {
    color: #334155;
    font-size: 1rem;
    font-weight: 600;
  }

  .tenant-appointment-state-chip {
    padding: 0.36rem 0.62rem;
    font-size: 0.76rem;
    font-weight: 700;
    border-color: #d7e2ef;
    background: #fff;
  }

  .tenant-appointment-actions {
    display: flex;
    flex-wrap: wrap;
    gap: 0.45rem;
  }

  .tenant-appointment-action {
    border-radius: 10px;
    font-weight: 600;
    letter-spacing: 0.01em;
    padding: 0.42rem 0.72rem;
  }

  .tenant-appointment-action-main {
    background: linear-gradient(135deg, var(--tenant-primary), var(--tenant-secondary));
    border-color: transparent;
    color: #fff;
  }

  .tenant-appointment-action-main:hover,
  .tenant-appointment-action-main:focus {
    color: #fff;
    filter: brightness(0.98);
  }

  .tenant-appointment-action-soft {
    border-color: rgba(var(--tenant-primary-rgb), 0.22);
    color: #1f2937;
    background: #ffffff;
  }

  .tenant-appointment-action-soft:hover,
  .tenant-appointment-action-soft:focus {
    border-color: rgba(var(--tenant-accent-rgb), 0.48);
    background: #f8fbff;
    color: #0f172a;
  }

  @media (max-width: 575.98px) {
    .tenant-appointment-actions .tenant-appointment-action {
      flex: 1 1 calc(50% - 0.45rem);
      text-align: center;
    }
  }

  #tenantAppointmentPaymentModal .modal-dialog,
  #tenantAppointmentRescheduleModal .modal-dialog {
    max-width: 560px;
  }

  .tenant-appointment-modal-shell {
    border: 1px solid #dde6f2;
    border-radius: 14px;
    background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
    padding: 0.85rem;
  }

  .tenant-appointment-modal-shell.is-context {
    border-left: 4px solid rgba(var(--tenant-accent-rgb), 0.75);
    padding-left: 0.75rem;
  }

  #tenantAppointmentPaymentModal .form-control,
  #tenantAppointmentPaymentModal .form-select,
  #tenantAppointmentRescheduleModal .form-control,
  #tenantAppointmentRescheduleModal .form-select {
    border-radius: 12px;
    border-color: #cdd8e6;
  }

  #tenantAppointmentPaymentModal .form-control:focus,
  #tenantAppointmentPaymentModal .form-select:focus,
  #tenantAppointmentRescheduleModal .form-control:focus,
  #tenantAppointmentRescheduleModal .form-select:focus {
    border-color: rgba(var(--tenant-accent-rgb), 0.75);
    box-shadow: 0 0 0 0.18rem rgba(var(--tenant-accent-rgb), 0.16);
  }

  .tenant-appointment-modal-footer {
    border-top: 1px solid #e4eaf3;
    background: #ffffff;
  }

  .tenant-appointment-modal-cancel {
    border-radius: 12px;
    border-color: #cfd8e5;
    color: #334155;
    font-weight: 600;
  }

  .tenant-appointment-modal-primary {
    border-radius: 12px;
    border: 0;
    font-weight: 700;
    background: linear-gradient(135deg, var(--tenant-primary), var(--tenant-secondary));
    color: #fff;
  }

  .tenant-appointment-modal-primary:hover,
  .tenant-appointment-modal-primary:focus {
    color: #fff;
    filter: brightness(0.98);
  }

  .tenant-reschedule-calendar-shell {
    border: 1px solid #dbe4ef;
    border-radius: 14px;
    background: #ffffff;
    padding: 0.65rem;
  }

  .tenant-reschedule-calendar-label {
    font-size: 0.96rem;
    font-weight: 700;
    color: #0f172a;
    text-transform: capitalize;
  }

  .tenant-reschedule-weekday {
    font-size: 0.72rem;
    color: #64748b;
    text-transform: uppercase;
    font-weight: 600;
    letter-spacing: 0.02em;
    text-align: center;
  }

  .tenant-reschedule-day-btn {
    border-radius: 10px;
    min-height: 34px;
    font-weight: 600;
  }

  .tenant-reschedule-day-btn.is-selected {
    background: linear-gradient(135deg, var(--tenant-primary), var(--tenant-secondary));
    border-color: transparent;
    color: #fff;
  }

  .tenant-appointment-state-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.34rem;
    border-radius: 999px;
    border: 1px solid #dbe3ee;
    background: #f8fafc;
    color: #1f2937;
    padding: 0.3rem 0.55rem;
    font-size: 0.74rem;
    font-weight: 600;
    line-height: 1;
  }

  .tenant-appointment-state-dot {
    width: 8px;
    height: 8px;
    border-radius: 999px;
    display: inline-block;
    box-shadow: 0 0 0 1px rgba(15, 23, 42, 0.15);
    flex-shrink: 0;
  }

  .tenant-customer-info-shell {
    border: 1px solid #dbe3ee;
    border-radius: 12px;
    background: #ffffff;
    padding: 0.8rem;
  }

  .tenant-customer-info-label {
    font-size: 0.75rem;
    color: #64748b;
    margin-bottom: 0.15rem;
  }

  .tenant-customer-info-value {
    font-size: 0.95rem;
    color: #0f172a;
    font-weight: 600;
    margin-bottom: 0;
    word-break: break-word;
  }

  #tenantCustomerModal .form-control {
    border-radius: 12px;
    border-color: #cbd5e1;
  }

  #tenantCustomerModal .form-control:focus {
    border-color: rgba(var(--tenant-accent-rgb), 0.75);
    box-shadow: 0 0 0 0.2rem rgba(var(--tenant-accent-rgb), 0.18);
  }

  .tenant-modern-modal .modal-content {
    border: 1px solid #dbe3ee;
    border-radius: 18px;
    overflow: hidden;
    box-shadow: 0 24px 48px rgba(15, 23, 42, 0.2);
  }

  .tenant-modern-modal .modal-header {
    background: #ffffff;
    border-bottom: 1px solid #e5e7eb;
  }

  .tenant-modern-modal .modal-title {
    color: var(--tenant-primary);
    font-weight: 700;
  }

  .tenant-modern-modal .modal-body {
    background: #f8fafc;
  }

  .tenant-auth-shell {
    border: 1px solid #dbe3ee;
    border-radius: 14px;
    background: #ffffff;
    padding: 0.75rem;
  }

  #tenantPublicAuthTabs {
    border-bottom: 1px solid #dbe3ee;
    gap: 0.35rem;
  }

  #tenantPublicAuthTabs .nav-link {
    border: 1px solid transparent;
    border-top-left-radius: 10px;
    border-top-right-radius: 10px;
    color: #475569;
    font-weight: 600;
    padding: 0.45rem 0.8rem;
  }

  #tenantPublicAuthTabs .nav-link.active {
    background: linear-gradient(135deg, var(--tenant-primary), var(--tenant-secondary));
    color: #ffffff;
    border-color: var(--tenant-primary);
  }

  #tenantPublicAuthTabsContent {
    border-color: #dbe3ee !important;
    border-radius: 0 0 12px 12px !important;
    background: #f8fafc;
  }

  #tenantAuthModal .form-control {
    border-radius: 12px;
    border-color: #cbd5e1;
  }

  #tenantAuthModal .form-control:focus {
    border-color: rgba(var(--tenant-accent-rgb), 0.75);
    box-shadow: 0 0 0 0.2rem rgba(var(--tenant-accent-rgb), 0.18);
  }

  .tenant-auth-primary-btn {
    border-radius: 12px;
    font-weight: 600;
    background: linear-gradient(135deg, var(--tenant-primary), var(--tenant-secondary));
    border-color: var(--tenant-primary);
  }

  .tenant-auth-primary-btn:hover,
  .tenant-auth-primary-btn:focus {
    background: linear-gradient(135deg, var(--tenant-secondary), var(--tenant-primary));
    border-color: var(--tenant-secondary);
  }

  @media (max-width: 991.98px) {
    .tenant-user-name {
      max-width: 180px;
    }

    #tenant-session-indicator-wrap .dropdown-toggle::after {
      display: none;
    }

    #tenant-session-indicator-wrap .tenant-user-menu {
      display: none !important;
    }

    #landingNavbar.show .tenant-icon-btn {
      width: 100%;
      min-width: 0;
      justify-content: flex-start;
      padding: 0 0.85rem;
      gap: 0.45rem;
    }

    #landingNavbar.show .tenant-icon-btn .badge {
      top: 8px;
      right: 10px;
    }

    #landingNavbar.show .tenant-mobile-only-menu-label {
      display: inline;
      font-weight: 600;
      font-size: 0.95rem;
    }
  }

  .tenant-mobile-only-menu-label {
    display: none;
  }
</style>

<li class="nav-item d-none dropdown ms-lg-auto" id="tenant-session-indicator-wrap">
  <button type="button"
          class="btn tenant-nav-action-btn landing-nav-link d-inline-flex align-items-center gap-2 dropdown-toggle tenant-user-dropdown-btn"
          data-bs-toggle="dropdown"
          aria-expanded="false"
          aria-label="Menú de usuario"
          title="Menú de usuario">
    <i class="bi bi-person-circle"></i>
    <span id="tenant-session-indicator" class="tenant-user-name">Sesión iniciada</span>
  </button>
  <ul class="dropdown-menu dropdown-menu-end tenant-user-menu">
    <li id="tenant-orders-wrap">
      <a href="{{ $tenantCustomerPortalUrl }}#compras" id="tenant-orders-btn" class="dropdown-item d-inline-flex align-items-center gap-2">
        <i class="bi bi-bag-check"></i>
        <span>Listado de compras</span>
      </a>
    </li>
    <li id="tenant-appointments-wrap">
      <a href="{{ $tenantCustomerPortalUrl }}#citas" id="tenant-appointments-btn" class="dropdown-item d-inline-flex align-items-center gap-2">
        <i class="bi bi-calendar-check"></i>
        <span>Mis citas</span>
      </a>
    </li>
    <li id="tenant-account-wrap">
      <a href="{{ $tenantCustomerPortalUrl }}#perfil" id="tenant-account-btn" class="dropdown-item d-inline-flex align-items-center gap-2">
        <i class="bi bi-person-gear"></i>
        <span>Mi perfil</span>
      </a>
    </li>
    <li><hr class="dropdown-divider my-1"></li>
    <li id="tenant-session-logout-wrap">
      <button type="button" id="tenant-session-logout" class="dropdown-item text-danger d-inline-flex align-items-center gap-2">
        <i class="bi bi-box-arrow-right"></i>
        <span>Cerrar sesión</span>
      </button>
    </li>
  </ul>
</li>

<li class="nav-item d-none d-lg-none" id="tenant-orders-mobile-wrap">
  <a href="{{ $tenantCustomerPortalUrl }}#compras"
          id="tenant-orders-mobile-btn"
          class="btn tenant-nav-action-btn landing-nav-link d-inline-flex align-items-center gap-2">
    <i class="bi bi-bag-check"></i>
    <span>Listado de compras</span>
  </a>
</li>

<li class="nav-item d-none d-lg-none" id="tenant-appointments-mobile-wrap">
  <a href="{{ $tenantCustomerPortalUrl }}#citas"
          id="tenant-appointments-mobile-btn"
          class="btn tenant-nav-action-btn landing-nav-link d-inline-flex align-items-center gap-2">
    <i class="bi bi-calendar-check"></i>
    <span>Mis citas</span>
  </a>
</li>

<li class="nav-item d-none d-lg-none" id="tenant-account-mobile-wrap">
  <a href="{{ $tenantCustomerPortalUrl }}#perfil"
          id="tenant-account-mobile-btn"
          class="btn tenant-nav-action-btn landing-nav-link d-inline-flex align-items-center gap-2">
    <i class="bi bi-person-gear"></i>
    <span>Mi perfil</span>
  </a>
</li>

<li class="nav-item d-none d-lg-none" id="tenant-session-logout-mobile-wrap">
  <button type="button"
          id="tenant-session-logout-mobile-btn"
          class="btn tenant-nav-action-btn landing-nav-link d-inline-flex align-items-center gap-2 text-danger">
    <i class="bi bi-box-arrow-right"></i>
    <span>Cerrar sesión</span>
  </button>
</li>

<li class="nav-item d-none" id="tenant-notifications-wrap">
  <button type="button"
          id="tenant-notifications-btn"
          class="btn tenant-nav-action-btn landing-nav-link d-inline-flex align-items-center tenant-icon-btn"
          aria-label="Notificaciones"
          title="Notificaciones"
          data-bs-toggle="modal"
          data-bs-target="#tenantNotificationsModal">
    <i class="bi bi-bell"></i>
    <span class="tenant-mobile-only-menu-label">Notificaciones</span>
    <span class="badge rounded-pill bg-danger d-none" id="tenant-notifications-count">0</span>
  </button>
</li>

<li class="nav-item d-none d-lg-block">
  <button type="button"
          id="cart-toggle-button"
          class="btn tenant-nav-action-btn landing-nav-link d-inline-flex align-items-center tenant-icon-btn"
          aria-label="Carrito"
          title="Carrito"
          data-bs-toggle="offcanvas"
          data-bs-target="#tenantCartOffcanvas"
          aria-controls="tenantCartOffcanvas">
    <i class="bi bi-cart3"></i>
    <span class="badge rounded-pill bg-dark tenant-cart-count">0</span>
  </button>
</li>

<div class="modal fade tenant-modern-modal" id="tenantNotificationsModal" tabindex="-1" aria-labelledby="tenantNotificationsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="tenantNotificationsModalLabel">Notificaciones</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="tenant-notification-permission-panel" class="tenant-notification-permission-card d-none mb-3">
          <div class="d-flex flex-column gap-2">
            <div>
              <div class="fw-semibold" id="tenant-notification-permission-title">Activa alertas del navegador</div>
              <p class="tenant-notification-permission-copy" id="tenant-notification-permission-copy">Permite notificaciones para recibir avisos nativos en este dispositivo.</p>
            </div>
            <div class="d-flex flex-wrap gap-2">
              <button type="button" class="btn btn-sm btn-dark mb-0" id="tenant-enable-browser-notifications">Activar alertas</button>
            </div>
          </div>
        </div>
        <div id="tenant-notifications-list" class="d-flex flex-column gap-2"></div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade tenant-modern-modal" id="tenantOrdersModal" tabindex="-1" aria-labelledby="tenantOrdersModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="tenantOrdersModalLabel">Mis compras</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="tenant-orders-list" class="d-flex flex-column gap-2"></div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade tenant-modern-modal" id="tenantAppointmentsModal" tabindex="-1" aria-labelledby="tenantAppointmentsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="tenantAppointmentsModalLabel">Mis citas</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="tenant-appointments-list" class="d-flex flex-column gap-2"></div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade tenant-modern-modal" id="tenantAppointmentPaymentModal" tabindex="-1" aria-labelledby="tenantAppointmentPaymentModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="tenantAppointmentPaymentModalLabel">Registrar pago de cita</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body d-flex flex-column gap-3">
        <div class="tenant-customer-info-shell tenant-appointment-modal-shell is-context">
          <p class="tenant-customer-info-label mb-1">Cita seleccionada</p>
          <p class="tenant-customer-info-value mb-0" id="tenant-appointment-payment-context">-</p>
        </div>

        <div class="tenant-customer-info-shell tenant-appointment-modal-shell">
          <div class="row g-2 align-items-end">
            <div class="col-12">
              <label for="tenant-appointment-payment-method" class="tenant-customer-info-label">Método de pago</label>
              <select id="tenant-appointment-payment-method" class="form-select">
                <option value="">Selecciona un método</option>
              </select>
            </div>
            <div class="col-12 col-md-6">
              <label for="tenant-appointment-payment-amount" class="tenant-customer-info-label">Monto pagado</label>
              <input type="number" min="0" step="0.01" id="tenant-appointment-payment-amount" class="form-control" placeholder="0.00">
            </div>
            <div class="col-12 col-md-6">
              <label for="tenant-appointment-payment-currency" class="tenant-customer-info-label">Moneda</label>
              <input type="text" id="tenant-appointment-payment-currency" class="form-control" value="USD" readonly>
            </div>
            <div class="col-12" id="tenant-appointment-payment-reference-wrap">
              <label for="tenant-appointment-payment-reference" class="tenant-customer-info-label">Referencia de pago</label>
              <input type="text" id="tenant-appointment-payment-reference" class="form-control" maxlength="255" placeholder="Opcional">
            </div>
            <div class="col-12">
              <small class="text-muted d-block" id="tenant-appointment-payment-note">Completa los datos para confirmar el pago.</small>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer tenant-appointment-modal-footer">
        <button type="button" class="btn tenant-appointment-modal-cancel mb-0" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn tenant-appointment-modal-primary mb-0" id="tenant-appointment-payment-submit">Confirmar pago</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade tenant-modern-modal" id="tenantAppointmentRescheduleModal" tabindex="-1" aria-labelledby="tenantAppointmentRescheduleModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="tenantAppointmentRescheduleModalLabel">Reprogramar cita</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body d-flex flex-column gap-3">
        <div class="tenant-customer-info-shell tenant-appointment-modal-shell is-context">
          <p class="tenant-customer-info-label mb-1">Cita seleccionada</p>
          <p class="tenant-customer-info-value mb-0" id="tenant-appointment-reschedule-context">-</p>
        </div>

        <div class="tenant-customer-info-shell tenant-appointment-modal-shell">
          <div class="row g-2 align-items-end">
            <div class="col-12">
              <label class="tenant-customer-info-label d-block mb-1">Calendario de disponibilidad</label>
              <div class="tenant-reschedule-calendar-shell">
                <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                  <button type="button" class="btn btn-sm tenant-appointment-action tenant-appointment-action-soft mb-0" id="tenant-appointment-reschedule-calendar-prev">Mes anterior</button>
                  <strong id="tenant-appointment-reschedule-calendar-label" class="tenant-reschedule-calendar-label">-</strong>
                  <button type="button" class="btn btn-sm tenant-appointment-action tenant-appointment-action-soft mb-0" id="tenant-appointment-reschedule-calendar-next">Mes siguiente</button>
                </div>
                <div class="d-grid" style="grid-template-columns: repeat(7, minmax(0, 1fr)); gap: 6px;" id="tenant-appointment-reschedule-calendar-grid"></div>
              </div>
              <small class="text-muted d-block mt-1" id="tenant-appointment-reschedule-calendar-note">Selecciona un día disponible para ver horarios.</small>
              <input type="hidden" id="tenant-appointment-reschedule-date">
            </div>
            <div class="col-12 d-none">
              <label for="tenant-appointment-reschedule-date-display" class="tenant-customer-info-label">Fecha seleccionada</label>
              <input type="text" id="tenant-appointment-reschedule-date-display" class="form-control" readonly>
            </div>
            <div class="col-12">
              <label for="tenant-appointment-reschedule-slot" class="tenant-customer-info-label">Hora disponible</label>
              <select id="tenant-appointment-reschedule-slot" class="form-select">
                <option value="">Selecciona una hora</option>
              </select>
            </div>
            <div class="col-12">
              <small class="text-muted d-block" id="tenant-appointment-reschedule-note">Selecciona un día para consultar horarios disponibles.</small>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer tenant-appointment-modal-footer">
        <button type="button" class="btn tenant-appointment-modal-cancel mb-0" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn tenant-appointment-modal-primary mb-0" id="tenant-appointment-reschedule-submit">Guardar cambio</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade tenant-modern-modal" id="tenantAuthModal" tabindex="-1" aria-labelledby="tenantAuthModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="tenantAuthModalLabel">Iniciar sesión</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div class="tenant-auth-shell">
        <div class="tenant-auth-social-grid mb-3">
          @foreach($tenantSocialProviders as $provider)
            <a href="{{ route('client.social.redirect', ['provider' => $provider['key'], 'redirect' => $tenantAuthRedirect]) }}"
               class="tenant-auth-social-btn {{ $provider['enabled'] ? '' : 'is-disabled' }}"
               data-provider="{{ $provider['key'] }}"
               aria-disabled="{{ $provider['enabled'] ? 'false' : 'true' }}">
              <i class="{{ $provider['icon'] }} fs-5"></i>
              <span>Continuar con {{ $provider['label'] }}</span>
            </a>
          @endforeach
        </div>

        <div class="tenant-auth-divider">o con credenciales</div>

        <ul class="nav nav-tabs" id="tenantPublicAuthTabs" role="tablist">
          <li class="nav-item" role="presentation">
            <button class="nav-link active" id="tenant-public-login-tab" data-bs-toggle="tab" data-bs-target="#tenant-public-login-panel" type="button" role="tab">Iniciar sesión</button>
          </li>
          <li class="nav-item" role="presentation">
            <button class="nav-link" id="tenant-public-register-tab" data-bs-toggle="tab" data-bs-target="#tenant-public-register-panel" type="button" role="tab">Crear cuenta</button>
          </li>
        </ul>
        <div class="tab-content border border-top-0 rounded-bottom p-3" id="tenantPublicAuthTabsContent">
          <div class="tab-pane fade show active" id="tenant-public-login-panel" role="tabpanel">
            <form id="tenant-public-login-form" class="row g-2">
              <div class="col-12">
                <select class="form-select" id="tenant-public-login-type" required>
                  <option value="name" selected>Ingresar por Nombre</option>
                  <option value="email">Ingresar por Correo</option>
                  <option value="dni">Ingresar por DNI</option>
                </select>
              </div>
              <div class="col-12">
                <input type="text" class="form-control" id="tenant-public-login-identifier" placeholder="Nombre" required>
              </div>
              <div class="col-12">
                <div class="input-group">
                  <input type="password" class="form-control" id="tenant-public-login-password" placeholder="Contraseña" required>
                  <button type="button" class="btn btn-outline-secondary" data-password-toggle="tenant-public-login-password" aria-label="Mostrar u ocultar contraseña"><i class="bi bi-eye"></i></button>
                </div>
              </div>
              <div class="col-12">
                <button type="submit" class="btn btn-dark w-100 tenant-auth-primary-btn">Entrar</button>
              </div>
            </form>
          </div>
          <div class="tab-pane fade" id="tenant-public-register-panel" role="tabpanel">
            <form id="tenant-public-register-form" class="row g-2">
              <div class="col-12">
                <input type="text" class="form-control" id="tenant-public-register-name" placeholder="Nombre" required>
              </div>
              <div class="col-12">
                <input type="email" class="form-control" id="tenant-public-register-email" placeholder="Email" required>
              </div>
              <div class="col-12">
                <div class="input-group">
                  <input type="password" class="form-control" id="tenant-public-register-password" placeholder="Contraseña" minlength="8" required>
                  <button type="button" class="btn btn-outline-secondary" data-password-toggle="tenant-public-register-password" aria-label="Mostrar u ocultar contraseña"><i class="bi bi-eye"></i></button>
                </div>
              </div>
              <div class="col-12">
                <div class="input-group">
                  <input type="password" class="form-control" id="tenant-public-register-password-confirmation" placeholder="Confirmar contraseña" minlength="8" required>
                  <button type="button" class="btn btn-outline-secondary" data-password-toggle="tenant-public-register-password-confirmation" aria-label="Mostrar u ocultar contraseña"><i class="bi bi-eye"></i></button>
                </div>
              </div>
              <div class="col-12">
                <input type="text" class="form-control" id="tenant-public-register-dni" placeholder="DNI" required>
              </div>
              <div class="col-4 col-md-3">
                <select class="form-select" id="tenant-public-register-phone-code" aria-label="Código de país teléfono" required>
                  <option value="+58" selected>+58</option>
                  <option value="+1">+1</option>
                  <option value="+52">+52</option>
                  <option value="+57">+57</option>
                  <option value="+51">+51</option>
                  <option value="+54">+54</option>
                  <option value="+34">+34</option>
                </select>
              </div>
              <div class="col-8 col-md-9">
                <input type="text" class="form-control" id="tenant-public-register-phone" placeholder="Teléfono" required>
              </div>
              <div class="col-12">
                <button type="submit" class="btn btn-dark w-100 tenant-auth-primary-btn">Crear cuenta</button>
              </div>
            </form>
          </div>
        </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade tenant-modern-modal" id="tenantCustomerModal" tabindex="-1" aria-labelledby="tenantCustomerModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="tenantCustomerModalLabel">Mi perfil</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body d-flex flex-column gap-3">
        <div class="tenant-customer-info-shell">
          <div class="row g-2">
            <div class="col-12 col-md-6">
              <p class="tenant-customer-info-label">Nombre</p>
              <p class="tenant-customer-info-value" id="tenant-customer-name">-</p>
            </div>
            <div class="col-12 col-md-6">
              <p class="tenant-customer-info-label">Email</p>
              <p class="tenant-customer-info-value" id="tenant-customer-email">-</p>
            </div>
            <div class="col-12 col-md-6">
              <p class="tenant-customer-info-label">DNI</p>
              <p class="tenant-customer-info-value" id="tenant-customer-dni">No registrado</p>
            </div>
            <div class="col-12 col-md-6">
              <p class="tenant-customer-info-label">Teléfono</p>
              <p class="tenant-customer-info-value" id="tenant-customer-phone">No registrado</p>
            </div>
            <div class="col-12">
              <form id="tenant-customer-phone-form" class="row g-2 align-items-end">
                <div class="col-4 col-md-3">
                  <label for="tenant-customer-phone-code" class="tenant-customer-info-label">Código país</label>
                  <select class="form-select" id="tenant-customer-phone-code">
                    <option value="+58" selected>+58</option>
                    <option value="+1">+1</option>
                    <option value="+52">+52</option>
                    <option value="+57">+57</option>
                    <option value="+51">+51</option>
                    <option value="+54">+54</option>
                    <option value="+34">+34</option>
                  </select>
                </div>
                <div class="col-8 col-md-5">
                  <label for="tenant-customer-phone-input" class="tenant-customer-info-label">Agregar / actualizar teléfono</label>
                  <input type="text" class="form-control" id="tenant-customer-phone-input" placeholder="Ej: 4120000000" maxlength="50">
                </div>
                <div class="col-12 col-md-4">
                  <button type="submit" class="btn btn-outline-dark btn-sm w-100">Guardar teléfono</button>
                </div>
              </form>
            </div>
          </div>
        </div>

        <div class="tenant-customer-info-shell">
          <h6 class="mb-2">Cambiar contraseña</h6>
          <form id="tenant-customer-change-password-form" class="row g-2">
            <div class="col-12">
              <input type="password" class="form-control" id="tenant-customer-current-password" placeholder="Contraseña actual" minlength="8" required>
            </div>
            <div class="col-12 col-md-6">
              <input type="password" class="form-control" id="tenant-customer-new-password" placeholder="Nueva contraseña" minlength="8" required>
            </div>
            <div class="col-12 col-md-6">
              <input type="password" class="form-control" id="tenant-customer-new-password-confirmation" placeholder="Confirmar nueva contraseña" minlength="8" required>
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

<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<div class="toast-container position-fixed top-0 end-0 p-3" id="tenant-toast-container" style="z-index: 3000;"></div>

<script>
  (() => {
    if (window.__shopixTenantNavBootstrapped) {
      return;
    }

    window.__shopixTenantNavBootstrapped = true;

    const indicatorWrap = document.getElementById('tenant-session-indicator-wrap');
    const indicatorText = document.getElementById('tenant-session-indicator');
    const logoutWrap = document.getElementById('tenant-session-logout-wrap');
    const logoutButton = document.getElementById('tenant-session-logout');
    const logoutMobileWrap = document.getElementById('tenant-session-logout-mobile-wrap');
    const logoutMobileButton = document.getElementById('tenant-session-logout-mobile-btn');
    const ordersWrap = document.getElementById('tenant-orders-wrap');
    const ordersButton = document.getElementById('tenant-orders-btn');
    const ordersMobileWrap = document.getElementById('tenant-orders-mobile-wrap');
    const ordersMobileButton = document.getElementById('tenant-orders-mobile-btn');
    const appointmentsWrap = document.getElementById('tenant-appointments-wrap');
    const appointmentsButton = document.getElementById('tenant-appointments-btn');
    const appointmentsMobileWrap = document.getElementById('tenant-appointments-mobile-wrap');
    const appointmentsMobileButton = document.getElementById('tenant-appointments-mobile-btn');
    const accountWrap = document.getElementById('tenant-account-wrap');
    const accountButton = document.getElementById('tenant-account-btn');
    const accountMobileWrap = document.getElementById('tenant-account-mobile-wrap');
    const accountMobileButton = document.getElementById('tenant-account-mobile-btn');
    const ordersList = document.getElementById('tenant-orders-list');
    const ordersModal = document.getElementById('tenantOrdersModal');
    const appointmentsList = document.getElementById('tenant-appointments-list');
    const appointmentsModal = document.getElementById('tenantAppointmentsModal');
    const appointmentPaymentModal = document.getElementById('tenantAppointmentPaymentModal');
    const appointmentPaymentContext = document.getElementById('tenant-appointment-payment-context');
    const appointmentPaymentMethodSelect = document.getElementById('tenant-appointment-payment-method');
    const appointmentPaymentAmountInput = document.getElementById('tenant-appointment-payment-amount');
    const appointmentPaymentCurrencyInput = document.getElementById('tenant-appointment-payment-currency');
    const appointmentPaymentReferenceWrap = document.getElementById('tenant-appointment-payment-reference-wrap');
    const appointmentPaymentReferenceInput = document.getElementById('tenant-appointment-payment-reference');
    const appointmentPaymentNote = document.getElementById('tenant-appointment-payment-note');
    const appointmentPaymentSubmitBtn = document.getElementById('tenant-appointment-payment-submit');
    const appointmentRescheduleModal = document.getElementById('tenantAppointmentRescheduleModal');
    const appointmentRescheduleContext = document.getElementById('tenant-appointment-reschedule-context');
    const appointmentRescheduleDateInput = document.getElementById('tenant-appointment-reschedule-date');
    const appointmentRescheduleDateDisplayInput = document.getElementById('tenant-appointment-reschedule-date-display');
    const appointmentRescheduleCalendarGrid = document.getElementById('tenant-appointment-reschedule-calendar-grid');
    const appointmentRescheduleCalendarLabel = document.getElementById('tenant-appointment-reschedule-calendar-label');
    const appointmentRescheduleCalendarPrevBtn = document.getElementById('tenant-appointment-reschedule-calendar-prev');
    const appointmentRescheduleCalendarNextBtn = document.getElementById('tenant-appointment-reschedule-calendar-next');
    const appointmentRescheduleCalendarNote = document.getElementById('tenant-appointment-reschedule-calendar-note');
    const appointmentRescheduleSlotSelect = document.getElementById('tenant-appointment-reschedule-slot');
    const appointmentRescheduleNote = document.getElementById('tenant-appointment-reschedule-note');
    const appointmentRescheduleSubmitBtn = document.getElementById('tenant-appointment-reschedule-submit');
    const authModal = document.getElementById('tenantAuthModal');
    const authModalLabel = document.getElementById('tenantAuthModalLabel');
    const customerModal = document.getElementById('tenantCustomerModal');
    const notificationsWrap = document.getElementById('tenant-notifications-wrap');
    const notificationsCount = document.getElementById('tenant-notifications-count');
    const notificationsList = document.getElementById('tenant-notifications-list');
    const notificationsBtn = document.getElementById('tenant-notifications-btn');
    const notificationsModal = document.getElementById('tenantNotificationsModal');
    const notificationPermissionPanel = document.getElementById('tenant-notification-permission-panel');
    const notificationPermissionTitle = document.getElementById('tenant-notification-permission-title');
    const notificationPermissionCopy = document.getElementById('tenant-notification-permission-copy');
    const enableBrowserNotificationsBtn = document.getElementById('tenant-enable-browser-notifications');
    const authTriggers = Array.from(document.querySelectorAll('[data-shopix-open-auth]'));
    const tenantCustomerPortalUrl = @json($tenantCustomerPortalUrl);
    let tenantToastContainer = document.getElementById('tenant-toast-container');
    let serviceWorkerRegistrationPromise = null;
    let tenantAppointmentsById = new Map();
    let tenantAppointmentPaymentMethods = [];
    let paymentAppointmentId = 0;
    let rescheduleAppointmentId = 0;
    let rescheduleCalendarMonth = '';
    let rescheduleCalendarDays = [];

    if (notificationsModal && notificationsModal.parentElement !== document.body) {
      document.body.appendChild(notificationsModal);
    }

    if (ordersModal && ordersModal.parentElement !== document.body) {
      document.body.appendChild(ordersModal);
    }

    if (appointmentsModal && appointmentsModal.parentElement !== document.body) {
      document.body.appendChild(appointmentsModal);
    }

    if (appointmentPaymentModal && appointmentPaymentModal.parentElement !== document.body) {
      document.body.appendChild(appointmentPaymentModal);
    }

    if (appointmentRescheduleModal && appointmentRescheduleModal.parentElement !== document.body) {
      document.body.appendChild(appointmentRescheduleModal);
    }

    if (authModal && authModal.parentElement !== document.body) {
      document.body.appendChild(authModal);
    }

    if (customerModal && customerModal.parentElement !== document.body) {
      document.body.appendChild(customerModal);
    }

    if (!tenantToastContainer) {
      tenantToastContainer = document.createElement('div');
      tenantToastContainer.id = 'tenant-toast-container';
      tenantToastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
      tenantToastContainer.style.zIndex = '5000';
      document.body.appendChild(tenantToastContainer);
    } else if (tenantToastContainer.parentElement !== document.body) {
      document.body.appendChild(tenantToastContainer);
    }

    if (!indicatorWrap || !indicatorText || !logoutWrap || !logoutButton || !ordersWrap || !ordersButton || !ordersList || !appointmentsWrap || !appointmentsButton || !appointmentsList || !notificationsWrap || !notificationsCount || !notificationsList || !accountWrap || !accountButton) {
      return;
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const tenantWhatsappNumber = `${@json($tenantThemeModel->phone_code ?? '')}${@json($tenantThemeModel->phone_number ?? '')}`.replace(/\D/g, '');
    const serviceWorkerUrl = @json(url('/push-sw.js'));
    const vapidPublicKey = @json(config('webpush.vapid.public_key'));
    const defaultNotificationIcon = @json(\App\Support\ImageStorage::url($tenantThemeModel->logo ?? null) ?? asset('assets/img/shopix6.png'));
    let storefrontNotificationAutoPrompted = false;

    function resolveTenantApiErrorMessage(payload, fallbackMessage) {
      if (payload?.errors && typeof payload.errors === 'object') {
        const firstError = Object.values(payload.errors).flat()?.[0];
        if (firstError) {
          return String(firstError);
        }
      }

      if (payload?.error) {
        return String(payload.error);
      }

      if (payload?.message) {
        return String(payload.message);
      }

      return fallbackMessage;
    }

    function isExpiredTokenMessage(message) {
      const normalized = String(message || '').toLowerCase();
      return normalized.includes('token has expired')
        || normalized.includes('token inválido')
        || normalized.includes('token invalido')
        || normalized.includes('token expirado')
        || normalized.includes('token vencido');
    }

    function supportsBrowserNotifications() {
      return window.isSecureContext && 'Notification' in window && 'serviceWorker' in navigator && 'PushManager' in window;
    }

    function isStandaloneMode() {
      return window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
    }

    async function ensureServiceWorkerRegistration() {
      if (!supportsBrowserNotifications()) {
        return null;
      }

      if (!serviceWorkerRegistrationPromise) {
        serviceWorkerRegistrationPromise = navigator.serviceWorker.register(serviceWorkerUrl, { scope: '/' });
      }

      return serviceWorkerRegistrationPromise;
    }

    function urlBase64ToUint8Array(base64String) {
      const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
      const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
      const rawData = window.atob(base64);
      const outputArray = new Uint8Array(rawData.length);

      for (let index = 0; index < rawData.length; index += 1) {
        outputArray[index] = rawData.charCodeAt(index);
      }

      return outputArray;
    }

    function resolvePushContentEncoding(subscription) {
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

    async function deleteStoredPushSubscription(token, endpoint) {
      if (!token || !endpoint) {
        return;
      }

      await fetch('/api/push-subscriptions', {
        method: 'DELETE',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'Authorization': `Bearer ${token}`,
          'X-CSRF-TOKEN': csrfToken,
        },
        body: JSON.stringify({ endpoint }),
      }).catch(() => {});
    }

    function shouldForceIosPushRefresh() {
      return isIosDevice() && isStandaloneMode() && Notification.permission === 'granted';
    }

    async function syncBrowserPushSubscription(token, options = {}) {
      if (!supportsBrowserNotifications() || !vapidPublicKey) {
        return null;
      }

      const registration = await ensureServiceWorkerRegistration();
      if (!registration) {
        return null;
      }

      let subscription = await registration.pushManager.getSubscription();
      if (subscription && options.forceRefresh === true) {
        await deleteStoredPushSubscription(token, subscription.endpoint);
        await subscription.unsubscribe().catch(() => {});
        subscription = null;
      }

      if (!subscription) {
        subscription = await registration.pushManager.subscribe({
          userVisibleOnly: true,
          applicationServerKey: urlBase64ToUint8Array(vapidPublicKey),
        });
      }

      await fetch('/api/push-subscriptions', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'Authorization': `Bearer ${token}`,
          'X-CSRF-TOKEN': csrfToken,
        },
        body: JSON.stringify({
          subscription: {
            ...subscription.toJSON(),
            contentEncoding: resolvePushContentEncoding(subscription),
          },
        }),
      });

      return subscription;
    }

    async function removeBrowserPushSubscription(token) {
      if (!supportsBrowserNotifications()) {
        return;
      }

      const registration = await ensureServiceWorkerRegistration().catch(() => null);
      const subscription = await registration?.pushManager.getSubscription();

      if (!subscription) {
        return;
      }

      await fetch('/api/push-subscriptions', {
        method: 'DELETE',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'Authorization': `Bearer ${token}`,
          'X-CSRF-TOKEN': csrfToken,
        },
        body: JSON.stringify({
          endpoint: subscription.endpoint,
        }),
      }).catch(() => {});
    }

    async function showBrowserNotification(notification, options = {}) {
      if (!supportsBrowserNotifications() || Notification.permission !== 'granted') {
        return;
      }

      const force = options.force === true;
      if (!force && document.visibilityState === 'visible' && !isStandaloneMode()) {
        return;
      }

      const registration = await ensureServiceWorkerRegistration().catch(() => null);
      const title = notification.title || 'Notificación';
      const body = notification.message || notification.body || '';
      const targetUrl = notification.target_url || notification.url || window.location.href;
      const notificationOptions = {
        body,
        icon: defaultNotificationIcon,
        badge: defaultNotificationIcon,
        data: {
          url: targetUrl,
        },
        tag: `shopix-${notification.id || Date.now()}`,
      };

      if (registration && typeof registration.showNotification === 'function') {
        await registration.showNotification(title, notificationOptions);
        return;
      }

      const nativeNotification = new Notification(title, notificationOptions);
      nativeNotification.onclick = () => {
        window.focus();
        window.location.href = targetUrl;
        nativeNotification.close();
      };
    }

    function updateNotificationPermissionUi() {
      const hasSession = !!currentToken && !!currentUser?.id;

      if (!notificationPermissionPanel) {
        return;
      }

      if (!hasSession) {
        notificationPermissionPanel.classList.add('d-none');
        notificationPermissionPanel.classList.remove('is-ready');
        return;
      }

      if (!supportsBrowserNotifications()) {
        notificationPermissionPanel.classList.remove('d-none', 'is-ready');
        notificationPermissionTitle.textContent = 'Este navegador aún no admite alertas web aquí';
        notificationPermissionCopy.textContent = 'Necesitas HTTPS y un navegador compatible. En iPhone, las alertas web funcionan mejor al instalar la tienda en pantalla de inicio.';
        if (enableBrowserNotificationsBtn) {
          enableBrowserNotificationsBtn.textContent = 'Entendido';
        }
        return;
      }

      const permission = Notification.permission;
      notificationPermissionPanel.classList.remove('d-none');

      if (permission === 'granted') {
        notificationPermissionPanel.classList.add('is-ready');
        notificationPermissionTitle.textContent = 'Alertas del navegador activas';
        notificationPermissionCopy.textContent = 'Este dispositivo ya puede recibir notificaciones web de la tienda.';
        if (enableBrowserNotificationsBtn) {
          enableBrowserNotificationsBtn.textContent = 'Revisar';
        }
        return;
      }

      notificationPermissionPanel.classList.remove('is-ready');

      if (permission === 'denied') {
        notificationPermissionTitle.textContent = 'El permiso fue bloqueado';
        notificationPermissionCopy.textContent = 'Debes reactivar las notificaciones desde la configuración del navegador o del sistema para este sitio.';
        if (enableBrowserNotificationsBtn) {
          enableBrowserNotificationsBtn.textContent = 'Ver ayuda';
        }
        return;
      }

      notificationPermissionTitle.textContent = 'Activa alertas del navegador';
      notificationPermissionCopy.textContent = 'Permite notificaciones para recibir avisos nativos en este dispositivo. En iPhone, instala la tienda en pantalla de inicio para mejor compatibilidad.';
      if (enableBrowserNotificationsBtn) {
        enableBrowserNotificationsBtn.textContent = 'Activar alertas';
      }
    }

    async function requestBrowserNotificationPermission() {
      if (!currentToken || !currentUser?.id) {
        openTenantAuthModal();
        return;
      }

      if (!supportsBrowserNotifications()) {
        alert('Este navegador necesita HTTPS, Service Worker y soporte Push API para activar alertas web. En iPhone, instala la tienda en pantalla de inicio.');
        return;
      }

      if (!vapidPublicKey) {
        alert('Las notificaciones push aún no están configuradas en el servidor.');
        return;
      }

      if (Notification.permission === 'denied') {
        alert('El permiso de notificaciones está bloqueado. Debes habilitarlo manualmente en la configuración del navegador o del sistema.');
        return;
      }

      const permission = await Notification.requestPermission();
      updateNotificationPermissionUi();

      if (permission !== 'granted') {
        return;
      }

      await syncBrowserPushSubscription(currentToken);
      await showBrowserNotification({
        title: 'Alertas activadas',
        message: 'Desde ahora recibirás notificaciones nativas de esta tienda en este dispositivo.',
        target_url: window.location.href,
      }, { force: true });
      showTenantToast('Alertas activadas', 'Este dispositivo ya puede recibir notificaciones del navegador.');
    }

    function maybeAutoRequestBrowserNotificationPermission() {
      if (storefrontNotificationAutoPrompted) {
        return;
      }

      if (!currentToken || !currentUser?.id || !isStandaloneMode()) {
        return;
      }

      if (!supportsBrowserNotifications() || !vapidPublicKey || Notification.permission !== 'default') {
        return;
      }

      storefrontNotificationAutoPrompted = true;
      requestBrowserNotificationPermission().catch(() => {
        storefrontNotificationAutoPrompted = false;
      });
    }

    function requestNotificationsFromFlow(detail = {}) {
      const standaloneOnly = detail?.standaloneOnly !== false;

      if (standaloneOnly && !isStandaloneMode()) {
        return;
      }

      if (!currentToken || !currentUser?.id) {
        return;
      }

      if (!supportsBrowserNotifications() || !vapidPublicKey || Notification.permission !== 'default') {
        return;
      }

      requestBrowserNotificationPermission().catch(() => {});
    }

    function openTenantAuthModal() {
      if (authModal && typeof bootstrap !== 'undefined' && bootstrap?.Modal) {
        const offcanvasElement = document.getElementById('tenantCartOffcanvas');
        if (offcanvasElement && bootstrap?.Offcanvas) {
          const offcanvasInstance = bootstrap.Offcanvas.getInstance(offcanvasElement);
          offcanvasInstance?.hide();
        }

        const loginTab = document.getElementById('tenant-public-login-tab');
        if (loginTab && bootstrap?.Tab) {
          bootstrap.Tab.getOrCreateInstance(loginTab).show();
        }

        if (authModalLabel) {
          authModalLabel.textContent = 'Iniciar sesión';
        }

        bootstrap.Modal.getOrCreateInstance(authModal).show();
        return true;
      }

      return false;
    }

    function persistTenantAuth(token, user) {
      localStorage.setItem('shopix_ecomm_token', token || '');
      localStorage.setItem('shopix_ecomm_user', JSON.stringify(user || null));
      window.dispatchEvent(new CustomEvent('shopix-auth-changed', {
        detail: {
          token: token || '',
          user: user || null,
        },
      }));
    }

    function clearPersistedTenantAuth(shouldDispatch = true) {
      localStorage.removeItem('shopix_ecomm_token');
      localStorage.removeItem('shopix_ecomm_user');

      if (!shouldDispatch) {
        return;
      }

      window.dispatchEvent(new CustomEvent('shopix-auth-changed', {
        detail: {
          token: '',
          user: null,
        },
      }));
    }

    async function resolveTenantAuthUser(token) {
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
        shouldClear: !response.ok,
      };
    }

    function fillCustomerModalData(user) {
      const nameEl = document.getElementById('tenant-customer-name');
      const emailEl = document.getElementById('tenant-customer-email');
      const dniEl = document.getElementById('tenant-customer-dni');
      const phoneEl = document.getElementById('tenant-customer-phone');
      const phoneInputEl = document.getElementById('tenant-customer-phone-input');
      const phoneCodeEl = document.getElementById('tenant-customer-phone-code');
      const parsedPhone = splitPhoneNumber(user?.phone || user?.phone_number || '');

      if (nameEl) nameEl.textContent = user?.name || '-';
      if (emailEl) emailEl.textContent = user?.email || '-';
      if (dniEl) dniEl.textContent = user?.dni || 'No registrado';
      if (phoneEl) phoneEl.textContent = user?.phone || user?.phone_number || 'No registrado';
      if (phoneInputEl) phoneInputEl.value = parsedPhone.number;
      if (phoneCodeEl) phoneCodeEl.value = parsedPhone.code;
    }

    function normalizePhoneDigits(value) {
      return String(value || '').replace(/\D+/g, '');
    }

    function splitPhoneNumber(value) {
      const normalized = String(value || '').trim();
      const fallback = { code: '+58', number: '' };

      if (!normalized) {
        return fallback;
      }

      const normalizedWithoutSpaces = normalized.replace(/\s+/g, '');
      const matchedCode = normalizedWithoutSpaces.match(/^\+(\d{1,4})(\d+)$/);

      if (!matchedCode) {
        return {
          code: '+58',
          number: normalizePhoneDigits(normalizedWithoutSpaces),
        };
      }

      const codeDigits = matchedCode[1];
      const localNumber = matchedCode[2];
      const knownCodes = ['58', '1', '52', '57', '51', '54', '34'];
      const resolvedCode = knownCodes.includes(codeDigits) ? `+${codeDigits}` : '+58';
      const number = knownCodes.includes(codeDigits)
        ? localNumber
        : normalizePhoneDigits(normalizedWithoutSpaces);

      return {
        code: resolvedCode,
        number,
      };
    }

    function buildPhoneNumber(phoneCodeId, phoneInputId) {
      const phoneCode = document.getElementById(phoneCodeId)?.value || '+58';
      const rawNumber = document.getElementById(phoneInputId)?.value || '';
      const number = normalizePhoneDigits(rawNumber);

      if (!number) {
        return '';
      }

      const code = `+${normalizePhoneDigits(phoneCode) || '58'}`;
      return `${code}${number}`;
    }

    function openTenantCustomerModal() {
      const hasSession = !!currentToken && !!currentUser?.id;
      if (!hasSession) {
        return openTenantAuthModal();
      }

      fillCustomerModalData(currentUser);
      document.getElementById('tenant-customer-change-password-form')?.reset();

      if (customerModal && typeof bootstrap !== 'undefined' && bootstrap?.Modal) {
        bootstrap.Modal.getOrCreateInstance(customerModal).show();
        return true;
      }

      return false;
    }

    async function submitTenantCustomerPasswordChange(event) {
      event.preventDefault();

      if (!currentToken || !currentUser?.id) {
        alert('Debes iniciar sesión para cambiar tu contraseña.');
        return;
      }

      const current_password = document.getElementById('tenant-customer-current-password')?.value || '';
      const new_password = document.getElementById('tenant-customer-new-password')?.value || '';
      const new_password_confirmation = document.getElementById('tenant-customer-new-password-confirmation')?.value || '';

      if (!current_password || !new_password || !new_password_confirmation) {
        alert('Completa los tres campos para actualizar la contraseña.');
        return;
      }

      if (new_password.length < 8) {
        alert('La nueva contraseña debe tener al menos 8 caracteres.');
        return;
      }

      if (new_password !== new_password_confirmation) {
        alert('La confirmación de la nueva contraseña no coincide.');
        return;
      }

      const response = await fetch('/api/user/change-password', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'Authorization': `Bearer ${currentToken}`,
          'X-CSRF-TOKEN': csrfToken,
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

    async function submitTenantCustomerPhoneUpdate(event) {
      event.preventDefault();

      if (!currentToken || !currentUser?.id) {
        alert('Debes iniciar sesión para actualizar tu perfil.');
        return;
      }

      const phoneInput = document.getElementById('tenant-customer-phone-input');
      const phoneCode = document.getElementById('tenant-customer-phone-code');
      const phoneNumber = buildPhoneNumber('tenant-customer-phone-code', 'tenant-customer-phone-input');

      const response = await fetch('/api/user/update-profile', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'Authorization': `Bearer ${currentToken}`,
          'X-CSRF-TOKEN': csrfToken,
        },
        body: JSON.stringify({
          phone_number: phoneNumber,
        }),
      });

      const data = await response.json().catch(() => ({}));
      if (!response.ok) {
        alert(resolveTenantApiErrorMessage(data, 'No se pudo actualizar el teléfono.'));
        return;
      }

      const updatedUser = data?.user || { ...currentUser, phone_number: phoneNumber || null };
      persistTenantAuth(currentToken, updatedUser);
      fillCustomerModalData(updatedUser);
      if (phoneInput) {
        const parsedPhone = splitPhoneNumber(updatedUser?.phone_number || updatedUser?.phone || '');
        phoneInput.value = parsedPhone.number;
      }
      if (phoneCode) {
        const parsedPhone = splitPhoneNumber(updatedUser?.phone_number || updatedUser?.phone || '');
        phoneCode.value = parsedPhone.code;
      }

      alert(data.message || 'Teléfono actualizado correctamente.');
    }

    async function submitTenantPublicLogin(event) {
      event.preventDefault();

      const loginType = document.getElementById('tenant-public-login-type')?.value || 'name';
      const login = document.getElementById('tenant-public-login-identifier')?.value.trim() || '';
      const password = document.getElementById('tenant-public-login-password')?.value || '';

      const response = await fetch('/api/loginEcomm', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
        body: JSON.stringify({ login, login_type: loginType, password })
      });

      const data = await response.json().catch(() => ({}));
      if (!response.ok || !data.token || !data.user) {
        const message = resolveTenantApiErrorMessage(data, 'No se pudo iniciar sesión.');
        if (isExpiredTokenMessage(message)) {
          clearPersistedTenantAuth(false);
          applyAuthState(null, '');
        }

        alert(message);
        return;
      }

      persistTenantAuth(data.token, data.user);
      bootstrap.Modal.getInstance(authModal)?.hide();
      setTimeout(() => {
        maybeAutoRequestBrowserNotificationPermission();
      }, 120);
    }

    function syncTenantPublicLoginPlaceholder() {
      const loginTypeSelect = document.getElementById('tenant-public-login-type');
      const loginInput = document.getElementById('tenant-public-login-identifier');

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

    async function submitTenantPublicRegister(event) {
      event.preventDefault();

      const payload = {
        name: document.getElementById('tenant-public-register-name')?.value.trim() || '',
        email: document.getElementById('tenant-public-register-email')?.value.trim() || '',
        password: document.getElementById('tenant-public-register-password')?.value || '',
        password_confirmation: document.getElementById('tenant-public-register-password-confirmation')?.value || '',
        dni: document.getElementById('tenant-public-register-dni')?.value.trim() || '',
        phone_number: buildPhoneNumber('tenant-public-register-phone-code', 'tenant-public-register-phone'),
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
        alert(resolveTenantApiErrorMessage(data, 'No se pudo crear la cuenta.'));
        return;
      }

      persistTenantAuth(data.token, data.user);
      bootstrap.Modal.getInstance(authModal)?.hide();
      setTimeout(() => {
        maybeAutoRequestBrowserNotificationPermission();
      }, 120);
    }

    async function fetchNotifications(token) {
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

    async function markNotificationAsRead(token, id) {
      await fetch(`/api/notifications/${id}/read`, {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'Authorization': `Bearer ${token}`,
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
      });
    }

    async function fetchOrders(token) {
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

    async function fetchAppointments(token) {
      const response = await fetch('/api/user/appointments', {
        method: 'GET',
        headers: {
          'Accept': 'application/json',
          'Authorization': `Bearer ${token}`,
        },
      });

      const payload = await response.json().catch(() => ({}));

      if (!response.ok) {
        if (response.status === 401) {
          clearPersistedTenantAuth(false);
          applyAuthState(null, '');
        }

        throw new Error(payload?.message || 'No se pudieron cargar las citas.');
      }

      return payload;
    }

    async function runAppointmentAction(token, appointmentId, payload) {
      const response = await fetch(`/api/user/appointments/${appointmentId}/action`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'Authorization': `Bearer ${token}`,
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
        body: JSON.stringify(payload || {}),
      });

      const data = await response.json().catch(() => ({}));
      if (!response.ok || data?.success === false) {
        throw new Error(data?.message || 'No se pudo ejecutar la acción de la cita.');
      }

      return data;
    }

    function getTodayLocalDateValue() {
      const now = new Date();
      return `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
    }

    async function fetchAppointmentAvailableSlots(token, appointmentId, dateValue, monthValue = '') {
      const params = new URLSearchParams();
      if (dateValue) {
        params.set('date', String(dateValue || '').trim());
      }
      if (monthValue) {
        params.set('month', String(monthValue || '').trim());
      }
      const response = await fetch(`/api/user/appointments/${appointmentId}/available-slots?${params.toString()}`, {
        method: 'GET',
        headers: {
          'Accept': 'application/json',
          'Authorization': `Bearer ${token}`,
        },
      });

      const data = await response.json().catch(() => ({}));
      if (!response.ok || data?.success === false) {
        throw new Error(data?.message || 'No se pudo cargar la disponibilidad de la cita.');
      }

      return data;
    }

    function parseRescheduleMonth(value) {
      const normalized = String(value || '').trim();
      if (!/^\d{4}-\d{2}$/.test(normalized)) {
        return null;
      }

      const [yearRaw, monthRaw] = normalized.split('-');
      const year = Number(yearRaw);
      const month = Number(monthRaw);
      if (!Number.isInteger(year) || !Number.isInteger(month) || month < 1 || month > 12) {
        return null;
      }

      return { year, month };
    }

    function getMonthFromDateValue(dateValue) {
      const normalized = String(dateValue || '').trim();
      return /^\d{4}-\d{2}-\d{2}$/.test(normalized) ? normalized.slice(0, 7) : '';
    }

    function shiftRescheduleMonthValue(monthValue, step) {
      const parsed = parseRescheduleMonth(monthValue);
      const base = parsed
        ? new Date(parsed.year, parsed.month - 1, 1)
        : new Date();

      base.setMonth(base.getMonth() + Number(step || 0));
      return `${base.getFullYear()}-${String(base.getMonth() + 1).padStart(2, '0')}`;
    }

    function formatRescheduleCalendarLabel(monthValue) {
      const parsed = parseRescheduleMonth(monthValue);
      if (!parsed) {
        return '-';
      }

      const date = new Date(parsed.year, parsed.month - 1, 1);
      return date.toLocaleDateString('es-ES', { month: 'long', year: 'numeric' });
    }

    function formatRescheduleSelectedDateLabel(dateValue) {
      const normalized = String(dateValue || '').trim();
      if (!/^\d{4}-\d{2}-\d{2}$/.test(normalized)) {
        return '';
      }

      const [yearRaw, monthRaw, dayRaw] = normalized.split('-');
      const date = new Date(Number(yearRaw), Number(monthRaw) - 1, Number(dayRaw));
      if (Number.isNaN(date.getTime())) {
        return '';
      }

      return date.toLocaleDateString('es-ES', {
        weekday: 'long',
        day: '2-digit',
        month: 'long',
        year: 'numeric',
      });
    }

    function syncRescheduleSelectedDateDisplay() {
      if (!appointmentRescheduleDateDisplayInput) {
        return;
      }

      const selectedDate = String(appointmentRescheduleDateInput?.value || '').trim();
      appointmentRescheduleDateDisplayInput.value = formatRescheduleSelectedDateLabel(selectedDate);
    }

    function renderRescheduleCalendar() {
      if (!appointmentRescheduleCalendarGrid) {
        return;
      }

      const parsed = parseRescheduleMonth(rescheduleCalendarMonth || getMonthFromDateValue(appointmentRescheduleDateInput?.value || ''));
      if (!parsed) {
        appointmentRescheduleCalendarGrid.innerHTML = '';
        if (appointmentRescheduleCalendarLabel) {
          appointmentRescheduleCalendarLabel.textContent = '-';
        }
        return;
      }

      const monthStart = new Date(parsed.year, parsed.month - 1, 1);
      const monthEnd = new Date(parsed.year, parsed.month, 0);
      const selectedDate = String(appointmentRescheduleDateInput?.value || '').trim();
      const todayIso = getTodayLocalDateValue();
      const weekdayLabels = ['L', 'M', 'X', 'J', 'V', 'S', 'D'];
      const startWeekday = (monthStart.getDay() + 6) % 7;
      const totalDays = monthEnd.getDate();

      if (appointmentRescheduleCalendarLabel) {
        appointmentRescheduleCalendarLabel.textContent = formatRescheduleCalendarLabel(rescheduleCalendarMonth);
      }

      const cells = [];
      weekdayLabels.forEach(label => {
        cells.push(`<div class="tenant-reschedule-weekday">${label}</div>`);
      });

      for (let index = 0; index < startWeekday; index += 1) {
        cells.push('<div></div>');
      }

      const calendarByDate = new Map((rescheduleCalendarDays || []).map(row => [String(row.date || ''), row]));

      for (let day = 1; day <= totalDays; day += 1) {
        const dateValue = `${parsed.year}-${String(parsed.month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        const row = calendarByDate.get(dateValue);
        const hasSlots = !!row?.has_slots;
        const slotsCount = Number(row?.slots_count || 0);
        const isPastDate = dateValue < todayIso;
        const isEnabled = hasSlots && !isPastDate;
        const isSelected = selectedDate === dateValue;
        const buttonClass = isSelected
          ? 'btn btn-dark btn-sm w-100 tenant-reschedule-day-btn is-selected'
          : (hasSlots
            ? 'btn btn-outline-dark btn-sm w-100 tenant-reschedule-day-btn'
            : 'btn btn-outline-secondary btn-sm w-100 tenant-reschedule-day-btn');

        const title = hasSlots
          ? `${slotsCount} horario(s)`
          : (isPastDate ? 'Fecha pasada' : 'Sin horarios disponibles');

        cells.push(`<button type="button" class="${buttonClass}" data-reschedule-calendar-date="${dateValue}" ${isEnabled ? '' : 'disabled'} title="${title}"><span>${day}</span></button>`);
      }

      appointmentRescheduleCalendarGrid.innerHTML = cells.join('');
    }

    function findFirstRescheduleAvailableDate(calendarDays) {
      const todayIso = getTodayLocalDateValue();
      const row = Array.isArray(calendarDays)
        ? calendarDays.find(item => !!item?.has_slots && String(item?.date || '') >= todayIso)
        : null;

      return String(row?.date || '').trim();
    }

    async function refreshRescheduleAvailability() {
      if (!currentToken || rescheduleAppointmentId <= 0 || !appointmentRescheduleDateInput || !appointmentRescheduleSlotSelect) {
        return;
      }

      const selectedDate = String(appointmentRescheduleDateInput.value || '').trim();
      const selectedMonth = getMonthFromDateValue(selectedDate);
      if (!rescheduleCalendarMonth) {
        rescheduleCalendarMonth = selectedMonth || getTodayLocalDateValue().slice(0, 7);
      }

      const queryMonth = selectedMonth || rescheduleCalendarMonth;
      appointmentRescheduleSlotSelect.innerHTML = '<option value="">Cargando horarios...</option>';
      if (appointmentRescheduleNote) {
        appointmentRescheduleNote.textContent = selectedDate
          ? 'Consultando horarios disponibles...'
          : 'Cargando disponibilidad del calendario...';
      }

      try {
        const payload = await fetchAppointmentAvailableSlots(currentToken, rescheduleAppointmentId, selectedDate, queryMonth);
        const slots = selectedDate && Array.isArray(payload?.slots) ? payload.slots : [];
        rescheduleCalendarDays = Array.isArray(payload?.calendar) ? payload.calendar : [];
        if (payload?.calendar_month && /^\d{4}-\d{2}$/.test(String(payload.calendar_month))) {
          rescheduleCalendarMonth = String(payload.calendar_month);
        }

        if (!selectedDate) {
          const firstAvailableDate = findFirstRescheduleAvailableDate(rescheduleCalendarDays);
          if (firstAvailableDate) {
            appointmentRescheduleDateInput.value = firstAvailableDate;
            syncRescheduleSelectedDateDisplay();
            await refreshRescheduleAvailability();
            return;
          }
        }

        appointmentRescheduleSlotSelect.innerHTML = selectedDate
          ? [
            '<option value="">Selecciona una hora</option>',
            ...slots.map(slot => `<option value="${slot.start || ''}">${slot.label || `${slot.start || ''} - ${slot.end || ''}`}</option>`),
          ].join('')
          : '<option value="">Selecciona un día del calendario</option>';

        if (selectedDate && slots.length > 0) {
          appointmentRescheduleSlotSelect.value = String(slots[0]?.start || '');
        }

        renderRescheduleCalendar();

        if (appointmentRescheduleCalendarNote) {
          const availableDays = rescheduleCalendarDays.filter(row => !!row?.has_slots).length;
          appointmentRescheduleCalendarNote.textContent = availableDays > 0
            ? `${availableDays} día(s) con disponibilidad en ${formatRescheduleCalendarLabel(rescheduleCalendarMonth)}.`
            : 'No se detectaron días disponibles en este mes.';
        }

        if (appointmentRescheduleNote) {
          appointmentRescheduleNote.textContent = !selectedDate
            ? 'Selecciona un día del calendario para ver horas disponibles.'
            : (slots.length > 0
              ? `${slots.length} horario(s) disponible(s).`
              : 'No hay horarios para ese día. Selecciona otra fecha.');
        }
      } catch (error) {
        appointmentRescheduleSlotSelect.innerHTML = '<option value="">No se pudo cargar disponibilidad</option>';
        if (appointmentRescheduleNote) {
          appointmentRescheduleNote.textContent = error?.message || 'No se pudo cargar disponibilidad.';
        }
        rescheduleCalendarDays = [];
        renderRescheduleCalendar();
      }
    }

    function openAppointmentRescheduleModal(row) {
      if (!appointmentRescheduleModal || !appointmentRescheduleDateInput || !appointmentRescheduleSlotSelect) {
        return;
      }

      const appointmentId = Number(row?.id || 0);
      if (appointmentId <= 0) {
        return;
      }

      rescheduleAppointmentId = appointmentId;
      const startsAtRaw = String(row?.starts_at || '').trim();
      const startsAtDate = startsAtRaw.length >= 10 ? startsAtRaw.slice(0, 10) : '';
      const today = getTodayLocalDateValue();
      const initialDate = startsAtDate && startsAtDate >= today ? startsAtDate : today;

      appointmentRescheduleDateInput.value = initialDate;
      rescheduleCalendarMonth = getMonthFromDateValue(initialDate) || today.slice(0, 7);
      rescheduleCalendarDays = [];
      appointmentRescheduleSlotSelect.innerHTML = '<option value="">Selecciona una hora</option>';
      syncRescheduleSelectedDateDisplay();

      if (appointmentRescheduleContext) {
        appointmentRescheduleContext.textContent = `${row?.service || 'Servicio'} · ${row?.professional || 'Profesional'}`;
      }

      if (appointmentRescheduleNote) {
        appointmentRescheduleNote.textContent = 'Cargando disponibilidad...';
      }

      if (appointmentRescheduleCalendarNote) {
        appointmentRescheduleCalendarNote.textContent = 'Cargando calendario...';
      }

      renderRescheduleCalendar();

      const modalInstance = bootstrap.Modal.getOrCreateInstance(appointmentRescheduleModal);
      modalInstance.show();
      refreshRescheduleAvailability().catch(() => {});
    }

    function syncAppointmentPaymentMethodUi() {
      if (!appointmentPaymentMethodSelect) {
        return;
      }

      const selectedId = Number(appointmentPaymentMethodSelect.value || 0);
      const selectedMethod = tenantAppointmentPaymentMethods.find(method => Number(method?.id || 0) === selectedId) || null;
      const currencyCode = selectedMethod?.currency_code ? String(selectedMethod.currency_code) : 'USD';
      const requiresReference = !!selectedMethod?.uses_reference;

      if (appointmentPaymentCurrencyInput) {
        appointmentPaymentCurrencyInput.value = currencyCode;
      }

      if (appointmentPaymentReferenceWrap) {
        appointmentPaymentReferenceWrap.classList.toggle('d-none', !requiresReference);
      }

      if (appointmentPaymentReferenceInput && !requiresReference) {
        appointmentPaymentReferenceInput.value = '';
      }

      if (appointmentPaymentNote) {
        appointmentPaymentNote.textContent = requiresReference
          ? 'Este método requiere referencia de pago.'
          : 'Completa los datos para confirmar el pago.';
      }
    }

    function openAppointmentPaymentModal(row) {
      if (!appointmentPaymentModal || !appointmentPaymentMethodSelect || !appointmentPaymentAmountInput) {
        return;
      }

      const appointmentId = Number(row?.id || 0);
      if (appointmentId <= 0) {
        return;
      }

      paymentAppointmentId = appointmentId;
      const servicePrice = Number(row?.service_price || 0);
      const pendingAmount = Number(row?.pending_amount || 0);
      const defaultAmount = pendingAmount > 0 ? pendingAmount : servicePrice;

      if (appointmentPaymentContext) {
        appointmentPaymentContext.textContent = `${row?.service || 'Servicio'} · ${row?.professional || 'Profesional'}`;
      }

      appointmentPaymentAmountInput.value = defaultAmount > 0 ? defaultAmount.toFixed(2) : '';

      const options = ['<option value="">Selecciona un método</option>'];
      tenantAppointmentPaymentMethods.forEach((method) => {
        const currencyCode = String(method?.currency_code || 'USD').trim();
        const methodLabel = `${method?.name || 'Método'}${currencyCode ? ` · ${currencyCode}` : ''}`;
        options.push(`<option value="${Number(method?.id || 0)}">${methodLabel}</option>`);
      });
      appointmentPaymentMethodSelect.innerHTML = options.join('');

      const preferredMethodId = Number(row?.payment_method_id || 0);
      if (preferredMethodId > 0 && tenantAppointmentPaymentMethods.some(method => Number(method?.id || 0) === preferredMethodId)) {
        appointmentPaymentMethodSelect.value = String(preferredMethodId);
      }

      if (appointmentPaymentReferenceInput) {
        appointmentPaymentReferenceInput.value = '';
      }

      syncAppointmentPaymentMethodUi();

      const modalInstance = bootstrap.Modal.getOrCreateInstance(appointmentPaymentModal);
      modalInstance.show();
    }

    function showTenantToast(title, message) {
      if (!tenantToastContainer) return;

      const toastEl = document.createElement('div');
      toastEl.className = 'toast';
      toastEl.setAttribute('role', 'alert');
      toastEl.setAttribute('aria-live', 'assertive');
      toastEl.setAttribute('aria-atomic', 'true');
      toastEl.innerHTML = `
        <div class="toast-header">
          <strong class="me-auto">${title || 'Notificación'}</strong>
          <small>ahora</small>
          <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body">${message || ''}</div>
      `;

      tenantToastContainer.appendChild(toastEl);
      const toast = new bootstrap.Toast(toastEl, { delay: 5000 });
      toast.show();
      toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
    }

    function renderNotifications(payload, token) {
      const unread = Number(payload?.unread_count || 0);
      notificationsCount.textContent = String(unread);
      notificationsCount.classList.toggle('d-none', unread <= 0);

      const rows = Array.isArray(payload?.notifications) ? payload.notifications : [];
      if (rows.length === 0) {
        notificationsList.innerHTML = '<p class="text-muted mb-0">No tienes notificaciones.</p>';
        return;
      }

      notificationsList.innerHTML = rows.map(row => {
        const unreadClass = row.is_read ? '' : 'border-dark';
        const actionButton = row.is_read
          ? '<span class="badge bg-success">Leída</span>'
          : `<button type="button" class="btn btn-sm btn-outline-dark" data-mark-read="${row.id}">Marcar leída</button>`;
        const openButton = row.target_url
          ? `<a href="${row.target_url}" class="btn btn-sm btn-dark" aria-label="Abrir notificación" title="Abrir"${row.is_read ? '' : ` data-mark-read-link="${row.id}"`}><i class="bi bi-box-arrow-up-right me-1"></i>Abrir</a>`
          : '';
        const statusBadge = row.is_read
          ? '<span class="badge text-bg-light border">Leída</span>'
          : '<span class="badge text-bg-dark">Nueva</span>';

        return `
          <div class="border rounded p-3 tenant-notification-card ${unreadClass}">
            <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
              <div class="fw-semibold">${row.title || 'Notificación'}</div>
              ${statusBadge}
            </div>
            <div class="small text-muted">${row.message || ''}</div>
            <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mt-2">
              <div class="small text-secondary tenant-notification-meta">${row.created_at || ''}</div>
              <div class="tenant-notification-actions">
                ${openButton}
                ${row.is_read ? '' : actionButton}
              </div>
            </div>
          </div>
        `;
      }).join('');

      notificationsList.querySelectorAll('[data-mark-read]').forEach(button => {
        button.addEventListener('click', async () => {
          const id = button.getAttribute('data-mark-read');
          await markNotificationAsRead(token, id);
          const updated = await fetchNotifications(token);
          renderNotifications(updated, token);
        });
      });

      notificationsList.querySelectorAll('[data-mark-read-link]').forEach(link => {
        link.addEventListener('click', async () => {
          const id = link.getAttribute('data-mark-read-link');
          if (id) {
            try {
              await markNotificationAsRead(token, id);
            } catch (error) {
            }
          }
        });
      });
    }

    function isAppointmentRealtimeNotification(notification) {
      const action = String(notification?.action || '').toLowerCase();
      const type = String(notification?.type || '').toLowerCase();
      const appointmentId = Number(notification?.appointment_id || notification?.meta?.appointment_id || 0);

      return action.startsWith('appointment_') || type.includes('appointment') || appointmentId > 0;
    }

    async function refreshTenantAppointmentsRealtime() {
      if (!currentToken || !currentUser?.id) {
        return;
      }

      try {
        const payload = await fetchAppointments(currentToken);
        renderAppointments(payload);

        if (appointmentRescheduleModal?.classList.contains('show') && rescheduleAppointmentId > 0) {
          await refreshRescheduleAvailability();
        }
      } catch (error) {
      }
    }

    function orderStatusLabel(status) {
      if (Number(status) === 1) return 'Aprobado';
      if (Number(status) === 2) return 'Negado';
      return 'En proceso';
    }

    function orderStatusClass(status) {
      if (Number(status) === 1) return 'text-bg-success';
      if (Number(status) === 2) return 'text-bg-danger';
      return 'text-bg-dark';
    }

    function deliveryStatusLabel(status) {
      if (Number(status) === 1) return 'Entregado';
      if (Number(status) === 3) return 'En despacho / En vía';
      if (Number(status) === 2) return 'Cancelado';
      return 'Pendiente';
    }

    function deliveryStatusClass(status) {
      if (Number(status) === 1) return 'text-bg-success';
      if (Number(status) === 3) return 'text-bg-primary';
      if (Number(status) === 2) return 'text-bg-danger';
      return 'text-bg-secondary';
    }

    function appointmentStatusColor(status) {
      const normalized = String(status || '').toLowerCase();
      if (normalized === 'confirmed') return '#22c55e';
      if (normalized === 'completed') return '#14b8a6';
      if (normalized === 'cancelled') return '#ef4444';
      if (normalized === 'no_show') return '#f59e0b';
      return '#3b82f6';
    }

    function appointmentPaymentColor(status) {
      const normalized = String(status || '').toLowerCase();
      if (normalized === 'paid') return '#22c55e';
      if (normalized === 'partial') return '#f59e0b';
      if (normalized === 'waived') return '#60a5fa';
      return '#94a3b8';
    }

    function buildAppointmentWhatsappUrl(row) {
      if (!tenantWhatsappNumber) {
        return '';
      }

      const startsAt = row?.starts_at ? new Date(row.starts_at).toLocaleString() : 'sin fecha';
      const message = encodeURIComponent(`Hola, te escribo por mi cita de ${row?.service || 'servicio'} con ${row?.professional || 'profesional'} (${startsAt}). ¿Me ayudas con la confirmación?`);
      return `https://wa.me/${tenantWhatsappNumber}?text=${message}`;
    }

    function renderOrders(payload) {
      const rows = Array.isArray(payload?.orders) ? payload.orders : [];
      if (rows.length === 0) {
        ordersList.innerHTML = '<p class="text-muted mb-0">Todavía no tienes compras registradas.</p>';
        return;
      }

      ordersList.innerHTML = rows.map(row => `
        <article class="tenant-order-card">
          <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap mb-2">
            <div>
              <div class="fw-semibold fs-6">Pedido #${row.id}</div>
              <div class="tenant-order-meta"><strong>Tienda:</strong> ${row.tenant_name || 'No disponible'}${row.date ? ` • ${row.date}` : ''}</div>
            </div>
            <a href="${row.public_url}" class="btn btn-sm btn-outline-dark">Ver detalle</a>
          </div>

          <div class="tenant-order-meta mb-1"><strong>${row.items_count || 0}</strong> item(s) • <strong>${Number(row.total || 0).toFixed(2)} $</strong></div>
          <div class="tenant-order-meta mb-2">${String(row.preference || '').trim() && String(row.address || '').trim() && String(row.preference).trim().toLowerCase() === String(row.address).trim().toLowerCase()
            ? `${row.preference}`
            : `${row.preference || 'No definida'}${row.address ? ` • ${row.address}` : ''}`}</div>

          <div class="tenant-order-status-group">
            <span class="badge tenant-order-badge ${orderStatusClass(row.status)}">Pedido: ${orderStatusLabel(row.status)}</span>
            <span class="badge tenant-order-badge ${deliveryStatusClass(row.deliver_status)}">Entrega: ${deliveryStatusLabel(row.deliver_status)}</span>
          </div>
        </article>
      `).join('');
    }

    function renderAppointments(payload) {
      const rows = Array.isArray(payload?.appointments) ? payload.appointments : [];
      tenantAppointmentPaymentMethods = Array.isArray(payload?.payment_methods) ? payload.payment_methods : [];
      tenantAppointmentsById = new Map(rows.map(row => [Number(row?.id || 0), row]));
      if (rows.length === 0) {
        appointmentsList.innerHTML = '<p class="text-muted mb-0">Todavía no tienes citas registradas.</p>';
        return;
      }

      appointmentsList.innerHTML = rows.map(row => {
        const startsAt = row.starts_at
          ? new Date(row.starts_at).toLocaleString(undefined, {
              day: 'numeric',
              month: 'short',
              year: 'numeric',
              hour: '2-digit',
              minute: '2-digit',
            })
          : 'Sin fecha';
        const statusColor = appointmentStatusColor(row.status);
        const paymentColor = appointmentPaymentColor(row.payment_status);
        const whatsappUrl = buildAppointmentWhatsappUrl(row);
        const isPaymentPending = ['pending', 'partial'].includes(String(row.payment_status || '').toLowerCase());
        const publicOrderButton = row.public_order_url
          ? `<a href="${row.public_order_url}" class="btn btn-sm tenant-appointment-action tenant-appointment-action-soft">Ver pago</a>`
          : '';
        const whatsappButton = whatsappUrl
          ? `<a href="${whatsappUrl}" target="_blank" rel="noopener" class="btn btn-sm tenant-appointment-action tenant-appointment-action-soft">WhatsApp admin</a>`
          : '';

        return `
          <article class="tenant-appointment-card ${isPaymentPending ? 'is-payment-pending' : ''}" data-appointment-id="${row.id}">
            <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap mb-2">
              <div>
                <div class="tenant-appointment-title">${row.service || 'Servicio'}</div>
                <div class="tenant-appointment-meta">${row.professional || 'Profesional'} · ${startsAt}</div>
              </div>
              <div class="tenant-appointment-state-group">
                <span class="tenant-appointment-state-chip"><span class="tenant-appointment-state-dot" style="background:${statusColor};"></span>${row.status_label || row.status || 'Programada'}</span>
                <span class="tenant-appointment-state-chip"><span class="tenant-appointment-state-dot" style="background:${paymentColor};"></span>${row.payment_status_label || row.payment_status || 'Pendiente'}</span>
              </div>
            </div>
            <div class="tenant-appointment-paid mb-2">Pagado: ${Number(row.paid_amount || 0).toFixed(2)} ${row.payment_currency || 'USD'}</div>
            <div class="tenant-appointment-actions">
              ${row.can_confirm ? '<button type="button" class="btn btn-sm tenant-appointment-action tenant-appointment-action-main" data-appointment-action="confirm_attendance">Confirmar asistencia</button>' : ''}
              ${row.can_reschedule ? '<button type="button" class="btn btn-sm tenant-appointment-action tenant-appointment-action-soft" data-appointment-action="reschedule">Cambiar fecha</button>' : ''}
              ${row.can_confirm_payment ? '<button type="button" class="btn btn-sm tenant-appointment-action tenant-appointment-action-main" data-appointment-action="confirm_payment">Pagar y confirmar</button>' : ''}
              ${row.can_cancel ? '<button type="button" class="btn btn-sm tenant-appointment-action tenant-appointment-action-soft" data-appointment-action="cancel">Cancelar</button>' : ''}
              ${whatsappButton}
              ${publicOrderButton}
            </div>
          </article>
        `;
      }).join('');

      appointmentsList.querySelectorAll('[data-appointment-action]').forEach(button => {
        button.addEventListener('click', async () => {
          const card = button.closest('[data-appointment-id]');
          const appointmentId = Number(card?.getAttribute('data-appointment-id') || 0);
          const appointmentRow = tenantAppointmentsById.get(appointmentId) || null;
          const action = button.getAttribute('data-appointment-action');

          if (!appointmentId || !action || !currentToken) {
            return;
          }

          if (action === 'reschedule') {
            openAppointmentRescheduleModal(appointmentRow || { id: appointmentId });
            return;
          }

          if (action === 'confirm_payment') {
            openAppointmentPaymentModal(appointmentRow || { id: appointmentId });
            return;
          }

          const payload = { action };

          button.disabled = true;
          try {
            const result = await runAppointmentAction(currentToken, appointmentId, payload);
            showTenantToast('Citas', result?.message || 'Cita actualizada correctamente.');
            const updated = await fetchAppointments(currentToken);
            renderAppointments(updated);
          } catch (error) {
            showTenantToast('Citas', error?.message || 'No se pudo actualizar la cita.');
          } finally {
            button.disabled = false;
          }
        });
      });
    }

    appointmentRescheduleCalendarGrid?.addEventListener('click', (event) => {
      const target = event.target.closest('[data-reschedule-calendar-date]');
      if (!target || !appointmentRescheduleDateInput) {
        return;
      }

      const dateValue = String(target.getAttribute('data-reschedule-calendar-date') || '').trim();
      if (!dateValue) {
        return;
      }

      appointmentRescheduleDateInput.value = dateValue;
      const dateMonth = getMonthFromDateValue(dateValue);
      if (dateMonth) {
        rescheduleCalendarMonth = dateMonth;
      }

      syncRescheduleSelectedDateDisplay();
      refreshRescheduleAvailability().catch(() => {});
    });

    appointmentRescheduleCalendarPrevBtn?.addEventListener('click', () => {
      rescheduleCalendarMonth = shiftRescheduleMonthValue(rescheduleCalendarMonth, -1);
      if (appointmentRescheduleDateInput) {
        appointmentRescheduleDateInput.value = '';
      }
      syncRescheduleSelectedDateDisplay();
      refreshRescheduleAvailability().catch(() => {});
    });

    appointmentRescheduleCalendarNextBtn?.addEventListener('click', () => {
      rescheduleCalendarMonth = shiftRescheduleMonthValue(rescheduleCalendarMonth, 1);
      if (appointmentRescheduleDateInput) {
        appointmentRescheduleDateInput.value = '';
      }
      syncRescheduleSelectedDateDisplay();
      refreshRescheduleAvailability().catch(() => {});
    });

    appointmentRescheduleSubmitBtn?.addEventListener('click', async () => {
      if (!currentToken || rescheduleAppointmentId <= 0 || !appointmentRescheduleDateInput || !appointmentRescheduleSlotSelect) {
        return;
      }

      const scheduledDate = String(appointmentRescheduleDateInput.value || '').trim();
      const startTime = String(appointmentRescheduleSlotSelect.value || '').trim();

      if (!scheduledDate || !startTime) {
        alert('Debes seleccionar un día y una hora disponible.');
        return;
      }

      appointmentRescheduleSubmitBtn.disabled = true;
      try {
        const result = await runAppointmentAction(currentToken, rescheduleAppointmentId, {
          action: 'reschedule',
          scheduled_date: scheduledDate,
          start_time: startTime,
        });

        alert(result?.message || 'Cita reprogramada correctamente.');

        const modalInstance = bootstrap.Modal.getOrCreateInstance(appointmentRescheduleModal);
        modalInstance.hide();

        const updated = await fetchAppointments(currentToken);
        renderAppointments(updated);
      } catch (error) {
        alert(error?.message || 'No se pudo reprogramar la cita.');
      } finally {
        appointmentRescheduleSubmitBtn.disabled = false;
      }
    });

    appointmentPaymentMethodSelect?.addEventListener('change', syncAppointmentPaymentMethodUi);

    appointmentPaymentSubmitBtn?.addEventListener('click', async () => {
      if (!currentToken || paymentAppointmentId <= 0 || !appointmentPaymentMethodSelect || !appointmentPaymentAmountInput) {
        return;
      }

      const paymentMethodId = Number(appointmentPaymentMethodSelect.value || 0);
      const paidAmount = Number(appointmentPaymentAmountInput.value || 0);
      const paymentReference = String(appointmentPaymentReferenceInput?.value || '').trim();
      const selectedMethod = tenantAppointmentPaymentMethods.find(method => Number(method?.id || 0) === paymentMethodId) || null;

      if (paymentMethodId <= 0) {
        showTenantToast('Pago de cita', 'Debes seleccionar un método de pago.');
        return;
      }

      if (!Number.isFinite(paidAmount) || paidAmount <= 0) {
        showTenantToast('Pago de cita', 'Indica un monto pagado mayor a 0.');
        return;
      }

      if (selectedMethod?.uses_reference && paymentReference === '') {
        showTenantToast('Pago de cita', 'Este método requiere referencia de pago.');
        return;
      }

      appointmentPaymentSubmitBtn.disabled = true;

      try {
        const result = await runAppointmentAction(currentToken, paymentAppointmentId, {
          action: 'confirm_payment',
          payment_method_id: paymentMethodId,
          paid_amount: paidAmount,
          payment_reference: paymentReference,
          create_sale: true,
        });

        showTenantToast('Pago de cita', result?.message || 'Pago registrado correctamente.');

        const modalInstance = bootstrap.Modal.getOrCreateInstance(appointmentPaymentModal);
        modalInstance.hide();

        const updated = await fetchAppointments(currentToken);
        renderAppointments(updated);
      } catch (error) {
        showTenantToast('Pago de cita', error?.message || 'No se pudo registrar el pago.');
      } finally {
        appointmentPaymentSubmitBtn.disabled = false;
      }
    });

    appointmentPaymentModal?.addEventListener('hidden.bs.modal', () => {
      paymentAppointmentId = 0;
      if (appointmentPaymentMethodSelect) {
        appointmentPaymentMethodSelect.value = '';
      }
      if (appointmentPaymentAmountInput) {
        appointmentPaymentAmountInput.value = '';
      }
      if (appointmentPaymentReferenceInput) {
        appointmentPaymentReferenceInput.value = '';
      }
      syncAppointmentPaymentMethodUi();
    });

    function applyAuthState(user, token) {
      currentUser = user || null;
      currentToken = token || '';

      const hasSession = !!currentToken && !!currentUser?.id;

      indicatorWrap.classList.toggle('d-none', !hasSession);
      logoutWrap.classList.toggle('d-none', !hasSession);
      ordersWrap.classList.toggle('d-none', !hasSession);
      appointmentsWrap.classList.toggle('d-none', !hasSession);
      accountWrap.classList.toggle('d-none', !hasSession);
      logoutMobileWrap?.classList.toggle('d-none', !hasSession);
      ordersMobileWrap?.classList.toggle('d-none', !hasSession);
      appointmentsMobileWrap?.classList.toggle('d-none', !hasSession);
      accountMobileWrap?.classList.toggle('d-none', !hasSession);
      notificationsWrap.classList.toggle('d-none', !hasSession);
      authTriggers.forEach(trigger => {
        const navItem = trigger.closest('.nav-item');
        if (navItem) {
          navItem.classList.toggle('d-none', hasSession);
          return;
        }

        trigger.classList.toggle('d-none', hasSession);
      });

      if (hasSession) {
        indicatorText.textContent = `Hola, ${currentUser.name || 'Usuario'}`;
      } else {
        indicatorText.textContent = 'Sesión iniciada';
        notificationsCount.textContent = '0';
        notificationsCount.classList.add('d-none');
      }

      updateNotificationPermissionUi();
    }

    let tenantRealtimeSessionKey = '';
    let tenantRealtimePusher = null;

    function bindRealtimeChannel(user, token) {
      const sessionKey = `${user.id}:${token}`;
      if (tenantRealtimeSessionKey === sessionKey) {
        return;
      }

      tenantRealtimeSessionKey = sessionKey;

      const pusherKey = @json(config('broadcasting.connections.reverb.key'));
      if (!pusherKey) {
        return;
      }

      const configuredHost = @json(config('broadcasting.connections.reverb.options.host'));
      const configuredPort = Number(@json(config('broadcasting.connections.reverb.options.port')));
      const configuredScheme = @json(config('broadcasting.connections.reverb.options.scheme'));
      const configuredCluster = @json(config('broadcasting.connections.pusher.options.cluster'));

      const browserHost = window.location.hostname;
      const wsHost = !configuredHost || configuredHost === '127.0.0.1' || configuredHost === '0.0.0.0'
        ? browserHost
        : configuredHost;

      const forceTLS = configuredScheme
        ? configuredScheme === 'https'
        : window.location.protocol === 'https:';

      const wsPort = configuredPort || (forceTLS ? 443 : 80);

      const pusherOptions = {
        wsHost,
        wsPort,
        wssPort: wsPort,
        forceTLS,
        enabledTransports: ['ws', 'wss'],
        disableStats: true,
        authEndpoint: '/api/broadcasting/auth',
        auth: {
          headers: {
            'Authorization': `Bearer ${token}`,
            'Accept': 'application/json',
          },
        },
      };

      if (configuredCluster) {
        pusherOptions.cluster = configuredCluster;
      }

      if (tenantRealtimePusher && typeof tenantRealtimePusher.disconnect === 'function') {
        tenantRealtimePusher.disconnect();
      }

      const pusher = new Pusher(pusherKey, pusherOptions);
      tenantRealtimePusher = pusher;

      const incrementBadge = () => {
        const current = Number(notificationsCount.textContent || 0) + 1;
        notificationsCount.textContent = String(current);
        notificationsCount.classList.toggle('d-none', current <= 0);
      };

      const channel = pusher.subscribe(`private-App.Models.User.${user.id}`);
      const handleIncoming = async (notification) => {
        showTenantToast(notification.title || 'Notificación', notification.message || '');
        incrementBadge();
        showBrowserNotification(notification).catch(() => {});

        try {
          const payload = await fetchNotifications(token);
          renderNotifications(payload, token);
        } catch (error) {
        }

        if (isAppointmentRealtimeNotification(notification)) {
          refreshTenantAppointmentsRealtime().catch(() => {});
        }
      };

      channel.bind('Illuminate\\Notifications\\Events\\BroadcastNotificationCreated', handleIncoming);
      channel.bind('.Illuminate\\Notifications\\Events\\BroadcastNotificationCreated', handleIncoming);
      pusher.connection.bind('error', () => {});
    }

    function bootstrapTenantSession(user, token) {
      if (!token || !user?.id) {
        if (tenantRealtimePusher && typeof tenantRealtimePusher.disconnect === 'function') {
          tenantRealtimePusher.disconnect();
          tenantRealtimePusher = null;
        }

        tenantRealtimeSessionKey = '';
        return;
      }

      ensureServiceWorkerRegistration().catch(() => {});

      fetchNotifications(token)
        .then(payload => renderNotifications(payload, token))
        .catch(() => {
          notificationsList.innerHTML = '<p class="text-danger mb-0">No se pudieron cargar notificaciones.</p>';
        });

      if (supportsBrowserNotifications() && Notification.permission === 'granted') {
        syncBrowserPushSubscription(token, { forceRefresh: shouldForceIosPushRefresh() }).catch(() => {});
      }

      setTimeout(() => {
        maybeAutoRequestBrowserNotificationPermission();
      }, 180);

      bindRealtimeChannel(user, token);
    }

    async function initializeTenantAuthState() {
      let storedUser = null;
      let storedToken = '';

      try {
        storedUser = JSON.parse(localStorage.getItem('shopix_ecomm_user') || 'null');
        storedToken = localStorage.getItem('shopix_ecomm_token') || '';
      } catch (error) {
        storedUser = null;
        storedToken = '';
      }

      applyAuthState(storedUser, storedToken);
      ensureServiceWorkerRegistration().catch(() => {});

      if (!storedToken) {
        return;
      }

      try {
        const resolvedSession = await resolveTenantAuthUser(storedToken);
        if (resolvedSession.shouldClear) {
          clearPersistedTenantAuth(false);
          applyAuthState(null, '');
          return;
        }

        if (resolvedSession.user?.id) {
          persistTenantAuth(storedToken, resolvedSession.user);
          return;
        }
      } catch (error) {
      }

      if (storedUser?.id) {
        bootstrapTenantSession(storedUser, storedToken);
      }
    }

    let currentUser = null;
    let currentToken = '';

    const handleTenantPageShow = () => {
      if (currentToken && shouldForceIosPushRefresh()) {
        syncBrowserPushSubscription(currentToken, { forceRefresh: true }).catch(() => {});
      }
    };

    const handleTenantVisibilityChange = () => {
      if (document.visibilityState === 'visible' && currentToken && shouldForceIosPushRefresh()) {
        syncBrowserPushSubscription(currentToken, { forceRefresh: true }).catch(() => {});
      }
    };

    initializeTenantAuthState().catch(() => {
      applyAuthState(null, '');
    });

    window.addEventListener('pageshow', handleTenantPageShow);
    document.addEventListener('visibilitychange', handleTenantVisibilityChange);

    notificationsBtn?.addEventListener('click', async () => {
      if (!currentToken || !currentUser?.id) {
        notificationsList.innerHTML = '<p class="text-muted mb-0">Inicia sesión para ver tus notificaciones.</p>';
        return;
      }

      try {
        const payload = await fetchNotifications(currentToken);
        renderNotifications(payload, currentToken);
      } catch (error) {
        notificationsList.innerHTML = '<p class="text-danger mb-0">No se pudieron cargar notificaciones.</p>';
      }
    });

    window.addEventListener('shopix-auth-changed', (event) => {
      const user = event.detail?.user || null;
      const token = event.detail?.token || '';
      applyAuthState(user, token);
      bootstrapTenantSession(user, token);

      if (token && user?.id && supportsBrowserNotifications() && Notification.permission === 'granted') {
        syncBrowserPushSubscription(token, { forceRefresh: shouldForceIosPushRefresh() }).catch(() => {});
      }

      setTimeout(() => {
        maybeAutoRequestBrowserNotificationPermission();
      }, 180);
    });

    window.addEventListener('storage', (event) => {
      if (event.key !== 'shopix_ecomm_token' && event.key !== 'shopix_ecomm_user') {
        return;
      }

      applyAuthState(getAuthUser(), getAuthToken());
      bootstrapTenantSession(getAuthUser(), getAuthToken());
    });

    window.addEventListener('appinstalled', () => {
      setTimeout(() => {
        maybeAutoRequestBrowserNotificationPermission();
      }, 220);
    });

    document.getElementById('tenant-public-login-form')?.addEventListener('submit', submitTenantPublicLogin);
    document.getElementById('tenant-public-login-type')?.addEventListener('change', syncTenantPublicLoginPlaceholder);
    document.getElementById('tenant-public-register-form')?.addEventListener('submit', submitTenantPublicRegister);
    document.querySelectorAll('[data-password-toggle]').forEach((button) => {
      if (button.dataset.shopixPasswordToggleBound === '1') {
        return;
      }

      button.dataset.shopixPasswordToggleBound = '1';
      button.addEventListener('click', () => {
        const inputId = button.getAttribute('data-password-toggle');
        const input = inputId ? document.getElementById(inputId) : null;
        const icon = button.querySelector('i');
        if (!input) return;

        const isHidden = input.type === 'password';
        input.type = isHidden ? 'text' : 'password';
        icon?.classList.toggle('bi-eye', !isHidden);
        icon?.classList.toggle('bi-eye-slash', isHidden);
      });
    });

    syncTenantPublicLoginPlaceholder();

    const goToCustomerPortal = (hash = '') => {
      const destination = `${tenantCustomerPortalUrl}${hash}`;
      window.location.assign(destination);
    };

    ordersButton?.addEventListener('click', (event) => {
      event.preventDefault();
      if (currentToken && currentUser?.id) {
        goToCustomerPortal('#compras');
        return;
      }

      openTenantAuthModal();
    });

    ordersMobileButton?.addEventListener('click', (event) => {
      event.preventDefault();
      ordersButton?.click();
    });

    appointmentsButton?.addEventListener('click', (event) => {
      event.preventDefault();
      if (currentToken && currentUser?.id) {
        goToCustomerPortal('#citas');
        return;
      }

      openTenantAuthModal();
    });

    appointmentsMobileButton?.addEventListener('click', (event) => {
      event.preventDefault();
      appointmentsButton?.click();
    });

    accountButton?.addEventListener('click', (event) => {
      event.preventDefault();
      if (currentToken && currentUser?.id) {
        goToCustomerPortal('#perfil');
        return;
      }

      openTenantAuthModal();
    });

    accountMobileButton?.addEventListener('click', (event) => {
      event.preventDefault();
      accountButton?.click();
    });

    authTriggers.forEach(trigger => {
      trigger.addEventListener('click', event => {
        event.preventDefault();

        if (currentToken && currentUser?.id) {
          goToCustomerPortal('#perfil');
          return;
        }

        if (openTenantAuthModal()) {
          return;
        }

        alert('No se pudo abrir el inicio de sesión en este momento. Recarga la página e inténtalo nuevamente.');
      });
    });

    window.addEventListener('shopix-open-auth-requested', () => {
      if (currentToken && currentUser?.id) {
        goToCustomerPortal('#perfil');
        return;
      }

      openTenantAuthModal();
    });

    window.addEventListener('shopix:notifications-optin-requested', (event) => {
      requestNotificationsFromFlow(event?.detail || {});
    });

    logoutButton.addEventListener('click', () => {
      if (currentToken) {
        removeBrowserPushSubscription(currentToken).catch(() => {});
      }

      clearPersistedTenantAuth();
      window.location.reload();
    });

    logoutMobileButton?.addEventListener('click', () => {
      logoutButton?.click();
    });

    document.getElementById('tenant-customer-change-password-form')?.addEventListener('submit', submitTenantCustomerPasswordChange);
    document.getElementById('tenant-customer-phone-form')?.addEventListener('submit', submitTenantCustomerPhoneUpdate);
    enableBrowserNotificationsBtn?.addEventListener('click', requestBrowserNotificationPermission);

    window.addEventListener('pagehide', () => {
      if (tenantRealtimePusher && typeof tenantRealtimePusher.disconnect === 'function') {
        tenantRealtimePusher.disconnect();
        tenantRealtimePusher = null;
      }

      window.removeEventListener('pageshow', handleTenantPageShow);
      document.removeEventListener('visibilitychange', handleTenantVisibilityChange);
      window.__shopixTenantNavBootstrapped = false;
    }, { once: true });
  })();
</script>
