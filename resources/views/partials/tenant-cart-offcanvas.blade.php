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
  $showBsPricesInStorefront = (bool) ($showBsPrices ?? ($tenantThemeModel->show_bs_prices_in_storefront ?? false));
  $storefrontBsRateValue = (float) ($storefrontBsRate ?? 0);

  [$tenantPrimaryR, $tenantPrimaryG, $tenantPrimaryB] = $toRgb($tenantColorPrimary);
  [$tenantSecondaryR, $tenantSecondaryG, $tenantSecondaryB] = $toRgb($tenantColorSecondary);
  [$tenantAccentR, $tenantAccentG, $tenantAccentB] = $toRgb($tenantColorAccent);
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

  #tenantCartOffcanvas {
    --bs-offcanvas-zindex: 2000;
    z-index: 2000;
    width: min(470px, 100vw);
    background: #f8fafc;
    border-left: 1px solid rgba(var(--tenant-accent-rgb), 0.38);
  }

  .offcanvas-backdrop {
    --bs-backdrop-zindex: 1990;
    z-index: 1990;
  }

  #tenantCartOffcanvas .offcanvas-header {
    background: #ffffff;
    border-bottom: 1px solid #e5e7eb !important;
    padding: 0.9rem 1rem;
  }

  #tenantCartOffcanvas .offcanvas-title {
    font-weight: 700;
    color: var(--tenant-primary);
    letter-spacing: 0.01em;
  }

  #tenantCartOffcanvas .offcanvas-body {
    padding: 0.95rem;
    gap: 0.75rem;
  }

  .tenant-cart-plan-alert {
    border: 1px solid rgba(var(--tenant-accent-rgb), 0.45);
    border-radius: 12px;
    background: #ffffff;
    color: var(--tenant-primary);
    margin-bottom: 0;
    font-size: 0.9rem;
  }

  #tenant-cart-items {
    display: flex;
    flex-direction: column;
    gap: 0.65rem;
    margin-bottom: 0 !important;
  }

  .tenant-cart-empty {
    border: 1px dashed #cbd5e1;
    border-radius: 12px;
    background: #ffffff;
    color: #64748b;
    text-align: center;
    padding: 0.9rem;
    margin: 0;
  }

  .tenant-cart-item-card {
    border: 1px solid #e2e8f0;
    border-radius: 14px;
    padding: 0.75rem;
    background: #ffffff;
    box-shadow: 0 8px 16px rgba(15, 23, 42, 0.06);
  }

  .tenant-cart-item-thumb {
    width: 52px;
    height: 52px;
    border-radius: 10px;
    object-fit: cover;
    border: 1px solid #e2e8f0;
    flex-shrink: 0;
  }

  .tenant-cart-item-name {
    color: var(--tenant-primary);
    font-weight: 700;
    line-height: 1.2;
  }

  .tenant-cart-item-variant {
    color: #64748b;
    font-size: 0.82rem;
  }

  .tenant-cart-item-price {
    color: #334155;
    font-size: 0.86rem;
    font-weight: 600;
  }

  .tenant-cart-remove-btn {
    border-radius: 10px;
  }

  .tenant-cart-qty-btn {
    border-radius: 10px;
    min-width: 34px;
  }

  .tenant-cart-qty {
    min-width: 18px;
    text-align: center;
  }

  .tenant-cart-section-footer {
    border-top: none !important;
    background: #ffffff;
    border: 1px solid rgba(var(--tenant-accent-rgb), 0.38);
    border-radius: 16px;
    padding: 0.9rem;
    margin-top: auto;
    box-shadow: 0 10px 20px rgba(15, 23, 42, 0.06);
  }

  .tenant-cart-subtotal-label {
    color: #475569;
    font-weight: 600;
  }

  .tenant-cart-subtotal-amount {
    color: var(--tenant-primary);
    font-size: 1.1rem;
    font-weight: 700;
  }

  .tenant-cart-subtotal-bs {
    color: #64748b;
    font-size: 0.82rem;
    font-weight: 600;
  }

  #tenant-checkout-form .form-label {
    color: #475569;
    font-size: 0.86rem;
    font-weight: 600;
  }

  #tenant-checkout-form .form-control,
  #tenant-checkout-form .form-select {
    border-radius: 12px;
    border-color: #cbd5e1;
    font-size: 0.92rem;
  }

  #tenant-checkout-form .form-check-input {
    border-color: #94a3b8;
  }

  .tenant-cart-checkout-btn {
    border-radius: 12px;
    font-weight: 600;
    padding: 0.65rem 0.95rem;
    background: linear-gradient(135deg, var(--tenant-primary), var(--tenant-secondary));
    border-color: var(--tenant-primary);
    box-shadow: 0 10px 20px rgba(var(--tenant-primary-rgb), 0.25);
  }

  .tenant-cart-checkout-btn:hover,
  .tenant-cart-checkout-btn:focus {
    background: linear-gradient(135deg, var(--tenant-secondary), var(--tenant-primary));
    border-color: var(--tenant-secondary);
  }

  #tenantProCheckoutModal .modal-dialog {
    max-width: min(980px, 95vw);
  }

  #tenantProCheckoutModal .modal-content {
    border: 1px solid #dbe3ee;
    border-radius: 18px;
    overflow: hidden;
    box-shadow: 0 24px 48px rgba(15, 23, 42, 0.2);
  }

  #tenantProCheckoutModal .modal-header {
    background: #ffffff;
    border-bottom: 1px solid #e5e7eb;
  }

  #tenantProCheckoutModal .modal-title {
    font-weight: 700;
    color: var(--tenant-primary);
  }

  #tenantProCheckoutModal .modal-body {
    background: #f8fafc;
    padding: 1rem;
  }

  #tenantProCheckoutModal .modal-footer {
    background: #ffffff;
    border-top: 1px solid #e5e7eb;
  }

  .catalog-agenda-cell.is-clickable {
    cursor: pointer;
    transition: opacity 0.18s ease, transform 0.18s ease;
  }

  .catalog-agenda-cell.is-clickable:hover {
    opacity: 0.92;
  }

  .catalog-agenda-cell.is-loading {
    position: relative;
    pointer-events: none;
  }

  .catalog-agenda-cell.is-loading .catalog-agenda-pill {
    opacity: 0;
  }

  .catalog-agenda-cell.is-loading::after {
    content: '';
    position: absolute;
    top: 50%;
    left: 50%;
    width: 14px;
    height: 14px;
    margin-top: -7px;
    margin-left: -7px;
    border-radius: 999px;
    border: 2px solid rgba(var(--tenant-primary-rgb), 0.25);
    border-top-color: rgba(var(--tenant-primary-rgb), 0.95);
    animation: catalogSlotSpin 0.75s linear infinite;
  }

  @keyframes catalogSlotSpin {
    to {
      transform: rotate(360deg);
    }
  }

  .tenant-pro-auth-wrap,
  #tenant-pro-checkout-section {
    border: 1px solid rgba(var(--tenant-accent-rgb), 0.38);
    border-radius: 16px;
    background: #ffffff;
    padding: 0.85rem;
  }

  .tenant-auth-entry-shell {
    display: block;
  }

  .tenant-auth-form-shell {
    border-radius: 18px;
    border: 1px solid #e5e7eb;
    background: linear-gradient(180deg, #ffffff, #fffaf5);
    padding: 1rem;
  }

  .tenant-auth-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 0.75rem;
    margin-bottom: 0.9rem;
  }

  .tenant-auth-head h6 {
    margin: 0;
    font-size: 1.2rem;
    font-weight: 800;
    color: #0f172a;
  }

  .tenant-auth-head p {
    margin: 0.25rem 0 0;
    color: #64748b;
    font-size: 0.9rem;
  }

  .tenant-auth-head-pill {
    border-radius: 999px;
    padding: 0.42rem 0.72rem;
    border: 1px solid #fed7aa;
    background: #fff7ed;
    color: #9a3412;
    font-size: 0.78rem;
    font-weight: 700;
    white-space: nowrap;
  }

  .tenant-auth-social-grid {
    display: grid;
    gap: 0.65rem;
    margin-bottom: 0.95rem;
  }

  .tenant-auth-social-btn {
    min-height: 48px;
    border-radius: 14px;
    border: 1px solid #e5e7eb;
    background: #fff;
    color: #111827;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 0.7rem;
    font-weight: 700;
    text-decoration: none;
    transition: transform 0.2s ease, box-shadow 0.2s ease, border-color 0.2s ease;
  }

  .tenant-auth-social-btn:hover,
  .tenant-auth-social-btn:focus {
    transform: translateY(-1px);
    box-shadow: 0 12px 24px rgba(15, 23, 42, 0.08);
    border-color: rgba(var(--tenant-accent-rgb), 0.45);
    color: #111827;
  }

  .tenant-auth-social-btn[data-provider="google"] i { color: #ea4335; }
  .tenant-auth-social-btn[data-provider="facebook"] i { color: #1877f2; }
  .tenant-auth-social-btn[data-provider="apple"] i { color: #111827; }

  .tenant-auth-social-btn.is-disabled {
    opacity: 0.55;
    pointer-events: none;
    background: #f8fafc;
  }

  .tenant-auth-divider {
    display: flex;
    align-items: center;
    gap: 0.75rem;
    color: #94a3b8;
    font-size: 0.78rem;
    letter-spacing: 0.08em;
    text-transform: uppercase;
    font-weight: 700;
    margin: 0.9rem 0;
  }

  .tenant-auth-divider::before,
  .tenant-auth-divider::after {
    content: '';
    flex: 1;
    height: 1px;
    background: #e5e7eb;
  }

  .tenant-auth-alert {
    border-radius: 14px;
    border: 1px solid #fecaca;
    background: #fef2f2;
    color: #991b1b;
    padding: 0.8rem 0.9rem;
    font-size: 0.9rem;
  }

  #tenantProCheckoutModal .nav-tabs {
    border-bottom: 1px solid #dbe3ee;
    gap: 0.35rem;
  }

  #tenantProCheckoutModal .nav-tabs .nav-link {
    border: 1px solid transparent;
    border-top-left-radius: 10px;
    border-top-right-radius: 10px;
    color: #475569;
    font-weight: 600;
    padding: 0.45rem 0.8rem;
  }

  #tenantProCheckoutModal .nav-tabs .nav-link.active {
    background: linear-gradient(135deg, var(--tenant-primary), var(--tenant-secondary));
    color: #ffffff;
    border-color: var(--tenant-primary);
  }

  #tenantProCheckoutModal .btn-dark {
    background: linear-gradient(135deg, var(--tenant-primary), var(--tenant-secondary));
    border-color: var(--tenant-primary);
  }

  #tenantProCheckoutModal .btn-dark:hover,
  #tenantProCheckoutModal .btn-dark:focus {
    background: linear-gradient(135deg, var(--tenant-secondary), var(--tenant-primary));
    border-color: var(--tenant-secondary);
  }

  #tenantProCheckoutModal .btn-outline-dark {
    color: var(--tenant-primary);
    border-color: rgba(var(--tenant-primary-rgb), 0.45);
  }

  #tenantProCheckoutModal .btn-outline-dark:hover,
  #tenantProCheckoutModal .btn-outline-dark:focus {
    color: #fff;
    background: var(--tenant-primary);
    border-color: var(--tenant-primary);
  }

  #tenantAuthTabsContent,
  #tenantPublicAuthTabsContent {
    border-color: #dbe3ee !important;
    border-radius: 0 0 12px 12px !important;
    background: #f8fafc;
  }

  #tenantProCheckoutModal .form-control,
  #tenantProCheckoutModal .form-select {
    border-radius: 12px;
    border-color: #cbd5e1;
    font-size: 0.92rem;
  }

  #tenantProCheckoutModal .form-control:focus,
  #tenantProCheckoutModal .form-select:focus,
  #tenant-checkout-form .form-control:focus,
  #tenant-checkout-form .form-select:focus {
    border-color: rgba(var(--tenant-accent-rgb), 0.75);
    box-shadow: 0 0 0 0.2rem rgba(var(--tenant-accent-rgb), 0.18);
  }

  .tenant-pro-payment-row {
    border: 1px solid rgba(var(--tenant-accent-rgb), 0.35) !important;
    border-radius: 14px !important;
    background: #f8fafc;
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.8);
  }

  .pro-payment-method-details {
    border-color: #d1d5db !important;
    border-radius: 12px !important;
    background: #ffffff !important;
    color: #334155;
  }

  .pro-remove-payment-row {
    border-radius: 10px;
  }

  .tenant-pro-summary {
    margin-top: 0.5rem;
    border: 0;
    border-radius: 0;
    background: transparent;
    padding: 0;
  }

  .tenant-pro-summary.is-compact {
    margin-top: 0;
    padding: 0;
    background: transparent;
  }

  .tenant-pro-summary-grid {
    display: flex;
    flex-wrap: nowrap;
    gap: 0.45rem;
    align-items: stretch;
  }

  .tenant-pro-summary-card {
    border: 1px solid rgba(var(--tenant-accent-rgb), 0.28);
    border-radius: 10px;
    background: #ffffff;
    padding: 0.3rem 0.45rem;
    min-width: 0;
    flex: 1 1 0;
  }

  .tenant-pro-summary-card small {
    display: block;
    color: #64748b;
    font-size: 0.74rem;
    margin-bottom: 0.05rem;
  }

  .tenant-pro-summary-card strong,
  .tenant-pro-summary-card span {
    display: block;
    color: #0f172a;
    font-weight: 700;
    line-height: 1.2;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    font-size: 0.9rem;
  }

  .tenant-pro-stepper {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    gap: 0.35rem;
    margin: 0.7rem 0 0.9rem;
  }

  .tenant-pro-step {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: flex-start;
    gap: 0.32rem;
    border: 0;
    border-radius: 0;
    background: transparent;
    padding: 0.2rem 0.25rem;
    color: #64748b;
    font-size: 0.76rem;
    font-weight: 700;
    text-align: center;
  }

  .tenant-pro-step-index {
    width: 30px;
    height: 30px;
    border-radius: 999px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #e2e8f0;
    color: #334155;
    flex-shrink: 0;
  }

  .tenant-pro-step-label {
    display: block;
    line-height: 1.15;
  }

  .tenant-pro-step.is-active {
    color: var(--tenant-primary);
  }

  .tenant-pro-step.is-active .tenant-pro-step-index,
  .tenant-pro-step.is-complete .tenant-pro-step-index {
    background: linear-gradient(135deg, var(--tenant-primary), var(--tenant-secondary));
    color: #fff;
  }

  .tenant-pro-step.is-complete {
    color: var(--tenant-primary);
  }

  .tenant-pro-step-panel + .tenant-pro-step-panel {
    margin-top: 0.2rem;
  }

  .tenant-pro-step-shell {
    border: 1px solid #e5e7eb;
    border-radius: 16px;
    background: #fff;
    padding: 0.9rem;
  }

  .tenant-pro-step-shell h6 {
    margin-bottom: 0.4rem;
    font-size: 1rem;
    font-weight: 800;
    color: #0f172a;
  }

  .tenant-pro-step-note {
    color: #64748b;
    font-size: 0.84rem;
    margin-bottom: 0.7rem;
  }

  .tenant-pro-success-card {
    border: 1px solid #bbf7d0;
    border-radius: 18px;
    background: linear-gradient(180deg, #f0fdf4, #ffffff);
    padding: 1rem;
    text-align: center;
  }

  .tenant-pro-success-icon {
    width: 56px;
    height: 56px;
    border-radius: 999px;
    margin: 0 auto 0.75rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 1.5rem;
    color: #166534;
    background: #dcfce7;
    border: 1px solid #86efac;
  }

  .tenant-pro-success-card p {
    margin: 0;
    color: #475569;
  }

  .tenant-pro-summary-row {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 0.5rem;
    margin-bottom: 0.3rem;
    color: #334155;
    font-size: 0.92rem;
  }

  .tenant-pro-summary-row:last-child {
    margin-bottom: 0;
  }

  .tenant-pro-summary .highlight {
    color: var(--tenant-primary);
    font-weight: 700;
  }

  .tenant-pro-note {
    color: #64748b;
    font-size: 0.82rem;
    margin-top: 0.45rem;
  }

  #tenant-pro-submit-order {
    border-radius: 12px;
    font-weight: 600;
    background: linear-gradient(135deg, var(--tenant-primary), var(--tenant-secondary));
    border-color: var(--tenant-primary);
  }

  #tenant-pro-submit-order:hover,
  #tenant-pro-submit-order:focus {
    background: linear-gradient(135deg, var(--tenant-secondary), var(--tenant-primary));
    border-color: var(--tenant-secondary);
  }

  @media (max-width: 575.98px) {
    #tenantCartOffcanvas .offcanvas-body {
      padding: 0.75rem;
    }

    .tenant-cart-section-footer {
      padding: 0.75rem;
    }

    #tenantProCheckoutModal .modal-body {
      padding: 0.75rem;
    }

    .tenant-pro-auth-wrap,
    #tenant-pro-checkout-section {
      padding: 0.75rem;
    }

    .tenant-pro-summary-grid {
      gap: 0.3rem;
    }

    .tenant-pro-summary-card {
      padding: 0.28rem 0.35rem;
    }

    .tenant-pro-summary-card small {
      font-size: 0.67rem;
    }

    .tenant-pro-summary-card strong,
    .tenant-pro-summary-card span {
      font-size: 0.8rem;
    }

    .tenant-pro-stepper {
      grid-template-columns: repeat(3, minmax(0, 1fr));
    }

    .tenant-pro-step {
      justify-content: flex-start;
      padding-inline: 0;
    }

    .tenant-auth-entry-shell {
      display: block;
    }

    .tenant-auth-head {
      flex-direction: column;
    }

    .tenant-auth-social-btn {
      justify-content: flex-start;
      padding-inline: 0.9rem;
    }
  }
</style>

@php
  $tenantAuthRedirect = request()->getRequestUri() ?: '/';
  $tenantSocialProviders = [
    [
      'key' => 'google',
      'label' => 'Google',
      'icon' => 'bi bi-google',
      'enabled' => filled(config('services.google.client_id')) && filled(config('services.google.client_secret')) && filled(config('services.google.redirect')),
    ],
  ];
@endphp

<div class="offcanvas offcanvas-end" tabindex="-1" id="tenantCartOffcanvas" aria-labelledby="tenantCartOffcanvasLabel">
  <div class="offcanvas-header border-bottom tenant-cart-header">
    <h5 class="offcanvas-title" id="tenantCartOffcanvasLabel">Tu carrito</h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>

  <div class="offcanvas-body d-flex flex-column tenant-cart-body">
    @if(!$cartEnabled)
      <div id="tenant-cart-disabled-alert" class="alert alert-warning tenant-cart-plan-alert" role="alert">
        Tu tienda está en plan básico. Puedes enviar tu pedido por WhatsApp.
      </div>
    @else
      <div id="tenant-cart-disabled-alert" class="alert alert-info tenant-cart-plan-alert" role="alert">
        Plan Pro activo: puedes completar el checkout con métodos de pago y tipo de entrega.
      </div>
    @endif

    <div id="tenant-cart-items" class="mb-3"></div>

    <div class="border-top pt-3 mt-auto tenant-cart-section-footer">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <span class="fw-semibold tenant-cart-subtotal-label">Subtotal</span>
        <div class="text-end">
          <span class="fw-bold tenant-cart-subtotal-amount" id="tenant-cart-subtotal">0.00 $</span>
          <div class="tenant-cart-subtotal-bs d-none" id="tenant-cart-subtotal-bs">Bs 0.00</div>
        </div>
      </div>

      <div id="tenant-checkout-form">
        @if(!$cartEnabled)
        <div class="mb-3">
          <label class="form-label d-block">Tipo de entrega</label>
          <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="tenant-delivery-type" id="delivery-pickup" value="pickup" checked>
            <label class="form-check-label" for="delivery-pickup">Retiro en tienda</label>
          </div>
          <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="tenant-delivery-type" id="delivery-store" value="delivery" {{ (bool) ($tenant->delivery_enabled ?? false) ? '' : 'disabled' }}>
            <label class="form-check-label" for="delivery-store">Delivery</label>
          </div>
          <div class="form-check form-check-inline">
            <input class="form-check-input" type="radio" name="tenant-delivery-type" id="delivery-shipping" value="shipping" {{ (bool) ($tenant->delivery_enabled ?? false) ? '' : 'disabled' }}>
            <label class="form-check-label" for="delivery-shipping">Envío</label>
          </div>
          <small class="text-muted d-block mt-2">
            @if((bool) ($tenant->delivery_enabled ?? false))
              Delivery tienda: {{ \App\Support\DeliveryManager::modeLabel($tenant->delivery_fee_mode ?? 'free') }}.
            @else
              El delivery y los envíos están desactivados. Solo está disponible retiro en tienda.
            @endif
          </small>
        </div>

        <div class="mb-3 d-none" id="tenant-shipping-address-container">
          <label class="form-label" id="tenant-shipping-address-label">Dirección para delivery</label>
          <div class="row g-2">
            <div class="col-12 d-none" id="tenant-delivery-recipient-fields">
              <div class="row g-2">
                <div class="col-12 d-flex flex-wrap gap-2">
                  <button type="button" class="btn btn-outline-dark btn-sm" id="tenant-delivery-use-customer-data">Usar mis datos como receptor</button>
                </div>
                <div class="col-12 col-md-6">
                  <input type="text" id="tenant-delivery-receiver-name" class="form-control" placeholder="Nombre de quien recibe">
                </div>
                <div class="col-12 col-md-6">
                  <div class="input-group">
                    <select id="tenant-delivery-receiver-phone-code" class="form-select" style="max-width: 120px;">
                      <option value="+58" selected>+58</option>
                      <option value="+1">+1</option>
                      <option value="+52">+52</option>
                      <option value="+57">+57</option>
                      <option value="+51">+51</option>
                      <option value="+54">+54</option>
                      <option value="+34">+34</option>
                    </select>
                    <input type="text" id="tenant-delivery-receiver-phone" class="form-control" placeholder="Teléfono de quien recibe">
                  </div>
                </div>
                <div class="col-12">
                  <textarea id="tenant-delivery-extra-info" class="form-control" rows="2" placeholder="Información adicional para el delivery (opcional)"></textarea>
                </div>
              </div>
            </div>
            <div class="col-12 col-md-4" id="tenant-shipping-location-selects-country-wrap">
              <select id="tenant-shipping-country" class="form-select">
                <option value="">País</option>
              </select>
            </div>
            <div class="col-12 col-md-4" id="tenant-shipping-location-selects-state-wrap">
              <select id="tenant-shipping-state" class="form-select" disabled>
                <option value="">Estado</option>
              </select>
            </div>
            <div class="col-12 col-md-4" id="tenant-shipping-location-selects-city-wrap">
              <select id="tenant-shipping-city" class="form-select" disabled>
                <option value="">Ciudad</option>
              </select>
            </div>
            <div class="col-12" id="tenant-shipping-detail-wrap">
              <input type="text" id="tenant-shipping-address-detail" class="form-control" placeholder="Dirección exacta (calle, referencia, etc.)">
              <small class="text-muted d-block mt-2 d-none" id="tenant-shipping-address-hint">Indica también por cuál agencia de envío deseas trabajar.</small>
            </div>
            <div class="col-12 d-flex flex-wrap gap-2" id="tenant-shipping-location-actions">
              <button type="button" class="btn btn-outline-dark btn-sm" id="tenant-shipping-use-profile-location">Usar ubicación guardada</button>
              <button type="button" class="btn btn-outline-dark btn-sm" id="tenant-shipping-use-current-location">Usar ubicación actual</button>
              <button type="button" class="btn btn-outline-dark btn-sm" id="tenant-delivery-open-map">Marcar ubicación en mapa</button>
              <small class="text-muted w-100" id="tenant-shipping-location-status">Aún no se ha fijado una ubicación exacta.</small>
              <small class="text-muted w-100" id="tenant-delivery-price-info">Precio delivery: se calcula al fijar ubicación.</small>
              <input type="hidden" id="tenant-shipping-latitude">
              <input type="hidden" id="tenant-shipping-longitude">
            </div>
            <div class="col-12 col-md-4 d-none" id="tenant-shipping-distance-wrap">
              <input type="number" min="0" step="0.01" id="tenant-shipping-distance" class="form-control" placeholder="Distancia estimada (km)" readonly>
            </div>
          </div>
        </div>
        @endif

        <button id="tenant-cart-checkout" type="button" class="btn btn-success w-100 tenant-cart-checkout-btn">
          @if($cartEnabled)
            <i class="bi bi-bag-check me-2"></i>Continuar checkout
          @else
            <i class="bi bi-whatsapp me-2"></i>Realizar pedido
          @endif
        </button>
        <button id="tenant-cart-whatsapp-consult" type="button" class="btn btn-outline-success w-100 mt-2">
          <i class="bi bi-whatsapp me-2"></i>Consultar disponibilidad por WhatsApp
        </button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="tenantProCheckoutModal" tabindex="-1" aria-labelledby="tenantProCheckoutModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content tenant-pro-modal-content">
      <div class="modal-header tenant-pro-modal-header">
        <h5 class="modal-title" id="tenantProCheckoutModalLabel">Checkout Pro</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body tenant-pro-modal-body">
        <div id="tenant-pro-auth-section" class="mb-4 tenant-pro-auth-wrap">
          <div class="tenant-auth-entry-shell">
            <div class="tenant-auth-form-shell">
              <div class="tenant-auth-head">
                <div>
                  <h6>Iniciar sesión o registrarte</h6>
                  <p>Usa una red social o tu correo para continuar.</p>
                </div>
              </div>

              <div id="tenant-pro-auth-alert" class="tenant-auth-alert d-none" role="alert"></div>

              <div class="tenant-auth-social-grid">
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

              <div class="tenant-auth-divider">o con correo</div>

              <ul class="nav nav-tabs" id="tenantAuthTabs" role="tablist">
                <li class="nav-item" role="presentation">
                  <button class="nav-link active" id="tenant-login-tab" data-bs-toggle="tab" data-bs-target="#tenant-login-panel" type="button" role="tab">Iniciar sesión</button>
                </li>
                <li class="nav-item" role="presentation">
                  <button class="nav-link" id="tenant-register-tab" data-bs-toggle="tab" data-bs-target="#tenant-register-panel" type="button" role="tab">Crear cuenta</button>
                </li>
              </ul>
              <div class="tab-content border border-top-0 rounded-bottom p-3" id="tenantAuthTabsContent">
                <div class="tab-pane fade show active" id="tenant-login-panel" role="tabpanel">
                  <form id="tenant-pro-login-form" class="row g-2">
                    <div class="col-12 col-md-6">
                      <input type="text" class="form-control" id="tenant-pro-login-email" placeholder="Correo, teléfono o usuario" required>
                    </div>
                    <div class="col-12 col-md-6">
                      <div class="input-group">
                        <input type="password" class="form-control" id="tenant-pro-login-password" placeholder="Contraseña" required>
                        <button type="button" class="btn btn-outline-secondary" data-password-toggle="tenant-pro-login-password" aria-label="Mostrar u ocultar contraseña"><i class="bi bi-eye"></i></button>
                      </div>
                    </div>
                    <div class="col-12">
                      <button type="submit" class="btn btn-dark">Entrar</button>
                    </div>
                  </form>
                </div>
                <div class="tab-pane fade" id="tenant-register-panel" role="tabpanel">
                  <form id="tenant-pro-register-form" class="row g-2">
                    <div class="col-12 col-md-6">
                      <input type="text" class="form-control" id="tenant-pro-register-name" placeholder="Nombre" required>
                    </div>
                    <div class="col-12 col-md-6">
                      <input type="email" class="form-control" id="tenant-pro-register-email" placeholder="Email" required>
                    </div>
                    <div class="col-12 col-md-6">
                      <div class="input-group">
                        <input type="password" class="form-control" id="tenant-pro-register-password" placeholder="Contraseña" minlength="8" required>
                        <button type="button" class="btn btn-outline-secondary" data-password-toggle="tenant-pro-register-password" aria-label="Mostrar u ocultar contraseña"><i class="bi bi-eye"></i></button>
                      </div>
                    </div>
                    <div class="col-12 col-md-6">
                      <div class="input-group">
                        <input type="password" class="form-control" id="tenant-pro-register-password-confirmation" placeholder="Confirmar contraseña" minlength="8" required>
                        <button type="button" class="btn btn-outline-secondary" data-password-toggle="tenant-pro-register-password-confirmation" aria-label="Mostrar u ocultar contraseña"><i class="bi bi-eye"></i></button>
                      </div>
                    </div>
                    <div class="col-12 col-md-6">
                      <input type="text" class="form-control" id="tenant-pro-register-dni" placeholder="DNI" required>
                    </div>
                    <div class="col-4 col-md-2">
                      <select class="form-select" id="tenant-pro-register-phone-code" aria-label="Código de país teléfono" required>
                        <option value="+58" selected>+58</option>
                        <option value="+1">+1</option>
                        <option value="+52">+52</option>
                        <option value="+57">+57</option>
                        <option value="+51">+51</option>
                        <option value="+54">+54</option>
                        <option value="+34">+34</option>
                      </select>
                    </div>
                    <div class="col-8 col-md-4">
                      <input type="text" class="form-control" id="tenant-pro-register-phone" placeholder="Teléfono" required>
                    </div>
                    <div class="col-12">
                      <button type="submit" class="btn btn-dark">Crear cuenta</button>
                    </div>
                  </form>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div id="tenant-pro-checkout-section" class="d-none">
          <div class="tenant-pro-summary is-compact mb-3">
            <div class="tenant-pro-summary-grid">
              <div class="tenant-pro-summary-card">
                <small>Total</small>
                <strong class="highlight" id="tenant-pro-total-amount">0.00 $</strong>
                <span class="text-muted small" id="tenant-pro-total-amount-bs">0.00 Bs</span>
              </div>
              <div class="tenant-pro-summary-card">
                <small>Restante</small>
                <strong class="highlight" id="tenant-pro-remaining-amount">0.00 $</strong>
                <span class="text-muted small" id="tenant-pro-remaining-amount-bs">0.00 Bs</span>
              </div>
              <div class="tenant-pro-summary-card">
                <small>Pagado</small>
                <strong id="tenant-pro-paid-amount">0.00 $</strong>
                <span class="text-muted small" id="tenant-pro-paid-amount-bs">0.00 Bs</span>
              </div>
              <div class="tenant-pro-summary-card">
                <small>Tasa referencial</small>
                <strong><span id="tenant-pro-dollar-rate">0.00</span> Bs</strong>
                <span class="text-muted small">Base: <span id="tenant-pro-base-currency">USD</span></span>
              </div>
            </div>
            <div class="tenant-pro-summary-row mt-2" id="tenant-pro-delivery-fee-row">
              <span class="text-muted">Delivery estimado</span>
              <span id="tenant-pro-delivery-fee" class="text-muted">0.00 $</span>
            </div>
            <div class="tenant-pro-summary-row mt-2 d-none" id="tenant-pro-igtf-base-payments-row">
              <span class="text-muted">Pagado en <span id="tenant-pro-igtf-base-code">USD</span> (base IGTF)</span>
              <span id="tenant-pro-igtf-base-payments" class="text-muted">0.00 $</span>
            </div>
            <div class="tenant-pro-summary-row d-none" id="tenant-pro-igtf-row">
              <span class="text-danger fw-semibold">IGTF</span>
              <span id="tenant-pro-igtf-amount" class="text-danger fw-semibold">0.00 $</span>
            </div>
          </div>

          <div class="tenant-pro-stepper" id="tenant-pro-stepper">
            <div class="tenant-pro-step is-active" data-step="1">
              <span class="tenant-pro-step-index">1</span>
              <span class="tenant-pro-step-label">Entrega</span>
            </div>
            <div class="tenant-pro-step" data-step="2">
              <span class="tenant-pro-step-index">2</span>
              <span class="tenant-pro-step-label">Pago</span>
            </div>
            <div class="tenant-pro-step" data-step="3">
              <span class="tenant-pro-step-index">3</span>
              <span class="tenant-pro-step-label">Confirmación</span>
            </div>
          </div>

          <div class="tenant-pro-step-panel" data-checkout-step-panel="1">
            <div class="tenant-pro-step-shell" id="tenant-pro-delivery-step-shell">
              <h6>Tipo de entrega</h6>
              <p class="tenant-pro-step-note">Primero define cómo recibirás tu compra.</p>
              <div id="tenant-pro-delivery-type-wrap">
                <div class="form-check form-check-inline">
                  <input class="form-check-input" type="radio" name="tenant-pro-delivery-type" id="tenant-pro-delivery-pickup" value="pickup" checked>
                  <label class="form-check-label" for="tenant-pro-delivery-pickup">Retiro en tienda</label>
                </div>
                <div class="form-check form-check-inline">
                  <input class="form-check-input" type="radio" name="tenant-pro-delivery-type" id="tenant-pro-delivery-store" value="delivery" {{ (bool) ($tenant->delivery_enabled ?? false) ? '' : 'disabled' }}>
                  <label class="form-check-label" for="tenant-pro-delivery-store">Delivery</label>
                </div>
                <div class="form-check form-check-inline">
                  <input class="form-check-input" type="radio" name="tenant-pro-delivery-type" id="tenant-pro-delivery-shipping" value="shipping" {{ (bool) ($tenant->delivery_enabled ?? false) ? '' : 'disabled' }}>
                  <label class="form-check-label" for="tenant-pro-delivery-shipping">Envío</label>
                </div>
                <small class="text-muted d-block mt-2">
                  @if((bool) ($tenant->delivery_enabled ?? false))
                    Delivery tienda: {{ \App\Support\DeliveryManager::modeLabel($tenant->delivery_fee_mode ?? 'free') }}.
                  @else
                    El delivery y los envíos están desactivados. Solo está disponible retiro en tienda.
                  @endif
                </small>
              </div>

              <div class="mt-3 d-none" id="tenant-pro-shipping-address-container">
                <label class="form-label" id="tenant-pro-shipping-address-label">Dirección para delivery</label>
                <div class="row g-2">
                  <div class="col-12 d-none" id="tenant-pro-delivery-recipient-fields">
                    <div class="row g-2">
                      <div class="col-12 d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-outline-dark btn-sm" id="tenant-pro-delivery-use-customer-data">Usar mis datos como receptor</button>
                      </div>
                      <div class="col-12 col-md-6">
                        <input type="text" id="tenant-pro-delivery-receiver-name" class="form-control" placeholder="Nombre de quien recibe">
                      </div>
                      <div class="col-12 col-md-6">
                        <div class="input-group">
                          <select id="tenant-pro-delivery-receiver-phone-code" class="form-select" style="max-width: 120px;">
                            <option value="+58" selected>+58</option>
                            <option value="+1">+1</option>
                            <option value="+52">+52</option>
                            <option value="+57">+57</option>
                            <option value="+51">+51</option>
                            <option value="+54">+54</option>
                            <option value="+34">+34</option>
                          </select>
                          <input type="text" id="tenant-pro-delivery-receiver-phone" class="form-control" placeholder="Teléfono de quien recibe">
                        </div>
                      </div>
                      <div class="col-12">
                        <textarea id="tenant-pro-delivery-extra-info" class="form-control" rows="2" placeholder="Información adicional para el delivery (opcional)"></textarea>
                      </div>
                    </div>
                  </div>
                  <div class="col-12 col-md-4" id="tenant-pro-shipping-location-selects-country-wrap">
                    <select id="tenant-pro-shipping-country" class="form-select">
                      <option value="">País</option>
                    </select>
                  </div>
                  <div class="col-12 col-md-4" id="tenant-pro-shipping-location-selects-state-wrap">
                    <select id="tenant-pro-shipping-state" class="form-select" disabled>
                      <option value="">Estado</option>
                    </select>
                  </div>
                  <div class="col-12 col-md-4" id="tenant-pro-shipping-location-selects-city-wrap">
                    <select id="tenant-pro-shipping-city" class="form-select" disabled>
                      <option value="">Ciudad</option>
                    </select>
                  </div>
                  <div class="col-12" id="tenant-pro-shipping-detail-wrap">
                    <input type="text" id="tenant-pro-shipping-address-detail" class="form-control" placeholder="Dirección exacta (calle, referencia, etc.)">
                    <small class="text-muted d-block mt-2 d-none" id="tenant-pro-shipping-address-hint">Indica también por cuál agencia de envío deseas trabajar.</small>
                  </div>
                  <div class="col-12 d-flex flex-wrap gap-2" id="tenant-pro-shipping-location-actions">
                    <button type="button" class="btn btn-outline-dark btn-sm" id="tenant-pro-shipping-use-profile-location">Usar ubicación guardada</button>
                    <button type="button" class="btn btn-outline-dark btn-sm" id="tenant-pro-shipping-use-current-location">Usar ubicación actual</button>
                    <button type="button" class="btn btn-outline-dark btn-sm" id="tenant-pro-delivery-open-map">Marcar ubicación en mapa</button>
                    <small class="text-muted w-100" id="tenant-pro-shipping-location-status">Aún no se ha fijado una ubicación exacta.</small>
                    <small class="text-muted w-100" id="tenant-pro-delivery-price-info">Precio delivery: se calcula al fijar ubicación.</small>
                    <input type="hidden" id="tenant-pro-shipping-latitude">
                    <input type="hidden" id="tenant-pro-shipping-longitude">
                  </div>
                  <div class="col-12 col-md-4 d-none" id="tenant-pro-shipping-distance-wrap">
                    <input type="number" min="0" step="0.01" id="tenant-pro-shipping-distance" class="form-control" placeholder="Distancia estimada (km)" readonly>
                  </div>
                </div>
              </div>

              <div class="mt-3 d-none" id="tenant-pro-appointment-section">
                <div class="row g-2">
                  <div class="col-12" id="tenant-pro-appointment-service-wrap">
                    <label class="form-label">Servicio</label>
                    <select id="tenant-pro-appointment-service" class="form-select">
                      <option value="">Selecciona un servicio</option>
                    </select>
                  </div>
                  <div class="col-12 d-none" id="tenant-pro-appointment-service-selected-wrap">
                    <label class="form-label">Servicio seleccionado</label>
                    <input type="text" id="tenant-pro-appointment-service-selected" class="form-control" readonly>
                  </div>
                  <div class="col-12">
                    <label class="form-label">Profesional</label>
                    <select id="tenant-pro-appointment-user" class="form-select">
                      <option value="">Selecciona un profesional</option>
                    </select>
                  </div>
                  <input type="hidden" id="tenant-pro-appointment-date">
                  <div class="col-12">
                    <label class="form-label">Calendario de disponibilidad</label>
                    <div class="border rounded p-2">
                      <div class="d-flex align-items-center justify-content-between mb-2">
                        <button type="button" class="btn btn-outline-dark btn-sm" id="tenant-pro-appointment-calendar-prev">Mes anterior</button>
                        <strong id="tenant-pro-appointment-calendar-label">-</strong>
                        <button type="button" class="btn btn-outline-dark btn-sm" id="tenant-pro-appointment-calendar-next">Mes siguiente</button>
                      </div>
                      <div class="d-grid" style="grid-template-columns: repeat(7, minmax(0, 1fr)); gap: 6px;" id="tenant-pro-appointment-calendar-grid"></div>
                    </div>
                    <small class="text-muted d-block mt-1" id="tenant-pro-appointment-calendar-note">Selecciona servicio y profesional para visualizar disponibilidad por día.</small>
                  </div>
                  <div class="col-12 d-none">
                    <label class="form-label">Fecha seleccionada</label>
                    <input type="text" id="tenant-pro-appointment-date-display" class="form-control" placeholder="Haz click en un día del calendario" readonly>
                  </div>
                  <div class="col-12">
                    <label class="form-label">Hora disponible</label>
                    <select id="tenant-pro-appointment-slot" class="form-select">
                      <option value="">Selecciona profesional y un día del calendario</option>
                    </select>
                    <small class="text-muted d-block mt-1" id="tenant-pro-appointment-slot-note">Te mostraremos los horarios disponibles en tiempo real.</small>
                  </div>
                  <div class="col-12 d-none" id="tenant-pro-appointment-summary">
                    <div class="alert alert-light border mb-0 py-2 px-3" id="tenant-pro-appointment-summary-text">Aún no has completado los datos de tu cita.</div>
                  </div>
                  <div class="col-12">
                    <label class="form-label">Forma de pago de la cita</label>
                    <select id="tenant-pro-appointment-payment-mode" class="form-select">
                      <option value="online">Pagar ahora</option>
                      <option value="on_site">Pagar en el lugar</option>
                    </select>
                  </div>
                </div>
              </div>
            </div>
          </div>

          <div class="tenant-pro-step-panel d-none" data-checkout-step-panel="2">
            <div class="tenant-pro-step-shell">
              <h6>Métodos de pago</h6>
              <p class="tenant-pro-step-note" id="tenant-pro-payment-step-note">Agrega tu pago con referencia y comprobante.</p>
              <div class="alert alert-info py-2 px-3 mb-2 d-none" id="tenant-pro-on-site-payment-note">
                Elegiste pagar en el lugar. Puedes confirmar el pedido sin registrar pago en línea.
              </div>
              <div id="tenant-pro-payment-rows" class="d-flex flex-column gap-2"></div>
              <button type="button" id="tenant-pro-add-payment-row" class="btn btn-outline-dark btn-sm mt-2">+ Agregar pago</button>
            </div>
          </div>

          <div class="tenant-pro-step-panel d-none" data-checkout-step-panel="3">
            <div class="tenant-pro-success-card">
              <div class="tenant-pro-success-icon">
                <i class="bi bi-check2-circle"></i>
              </div>
              <h6 class="mb-2">Solicitud enviada con éxito</h6>
              <p id="tenant-pro-success-message">Recibirás una notificación con el seguimiento de tu compra o cita.</p>
              <div class="d-none" id="tenant-pro-appointment-status-wrap">
                <span class="badge bg-secondary" id="tenant-pro-appointment-status-badge">Estado de cita</span>
                <small class="d-block text-muted mt-1" id="tenant-pro-appointment-status-note">Validando estado actual de la cita...</small>
              </div>
              <a href="#" id="tenant-pro-success-link" class="btn btn-outline-dark btn-sm mt-3 d-none">Ver seguimiento</a>
            </div>
          </div>
        </div>
      </div>
      <div class="modal-footer tenant-pro-modal-footer" id="tenantProCheckoutModalFooter">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
        <button type="button" class="btn btn-outline-dark d-none" id="tenant-pro-prev-step">Atrás</button>
        <button type="button" class="btn btn-dark d-none" id="tenant-pro-next-step">Continuar</button>
        <button type="button" class="btn btn-success d-none" id="tenant-pro-submit-order" disabled>Confirmar pedido</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="tenantPackageFlavorModal" tabindex="-1" aria-labelledby="tenantPackageFlavorModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="tenantPackageFlavorModalLabel">Seleccionar sabores del combo</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <div id="tenant-package-flavor-summary" class="mb-3"></div>
        <div id="tenant-package-flavor-rows" class="d-flex flex-column gap-3"></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-dark" id="tenant-confirm-package-flavor-btn">Agregar al carrito</button>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="tenantDeliveryMapModal" tabindex="-1" aria-labelledby="tenantDeliveryMapModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="tenantDeliveryMapModalLabel">Seleccionar ubicación de delivery</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <div class="modal-body">
        <div class="mb-3">
          <input type="text" id="tenant-delivery-map-search" class="form-control" placeholder="Buscar ubicación en Google Maps">
        </div>
        <div id="tenant-delivery-map-canvas" style="width: 100%; height: 360px; border-radius: 12px; background: #f5f5f5;"></div>
        <small class="text-muted d-block mt-3" id="tenant-delivery-map-status">Haz clic en el mapa o mueve el marcador para fijar la ubicación exacta.</small>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
        <button type="button" class="btn btn-dark" id="tenant-delivery-map-confirm">Usar esta ubicación</button>
      </div>
    </div>
  </div>
</div>

@php
  $tenantPublicPackagePayload = ($materialPackages ?? collect())->map(function ($package) {
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

        $selectableVariants = collect($item->variant->product->variants ?? [])
          ->where('stock', '>', 0)
          ->map(function ($variant) {
            $variantBasePrice = (float) ($variant->price ?? 0);
            $variantProductDiscount = (float) ($variant->product->discount_percentage ?? 0);
            $variantOwnDiscount = (float) ($variant->discount_percentage ?? 0);
            $variantImagePath = optional($variant->images->first())->path;
            $productImagePath = optional($variant->product->images->first())->path;

            return [
              'variant_id' => (int) $variant->id,
              'variant_size' => (string) ($variant->size ?? ''),
              'variant_stock' => (float) ($variant->stock ?? 0),
              'variant_price' => $variantBasePrice * ((100 - $variantProductDiscount) / 100) * ((100 - $variantOwnDiscount) / 100),
              'product_name' => $variant->product->name ?? 'Producto',
              'image_src' => $variantImagePath
                ? (\App\Support\ImageStorage::url($variantImagePath) ?? asset('assets/img/shopix5.png'))
                : ($productImagePath
                    ? (\App\Support\ImageStorage::url($productImagePath) ?? asset('assets/img/shopix5.png'))
                    : asset('assets/img/shopix5.png')),
              'taxes' => ($variant->product && $variant->product->taxes)
                ? $variant->product->taxes->map(function ($tax) {
                    return [
                      'name' => $tax->name,
                      'rate' => (float) $tax->rate,
                    ];
                  })->values()->toArray()
                : [],
            ];
          })
          ->values()
          ->toArray();

        $itemVariantImagePath = optional($item->variant->images->first())->path;
        $itemProductImagePath = optional($item->variant->product->images->first())->path;

        return [
          'variant_id' => (int) $item->product_variant_id,
          'selection_mode' => (string) ($item->selection_mode ?? 'variant'),
          'variant_size' => (string) ($item->variant->size ?? ''),
          'variant_stock' => (float) ($item->variant->stock ?? 0),
          'variant_price' => (float) $effectivePrice,
          'product_name' => $item->variant->product->name ?? 'Producto',
          'image_src' => $itemVariantImagePath
            ? (\App\Support\ImageStorage::url($itemVariantImagePath) ?? asset('assets/img/shopix5.png'))
            : ($itemProductImagePath
                ? (\App\Support\ImageStorage::url($itemProductImagePath) ?? asset('assets/img/shopix5.png'))
                : asset('assets/img/shopix5.png')),
          'quantity' => (float) ($item->quantity ?? 0),
          'selectable_variants' => (($item->selection_mode ?? 'variant') === 'product')
            ? $selectableVariants
            : [[
                'variant_id' => (int) $item->product_variant_id,
                'variant_size' => (string) ($item->variant->size ?? ''),
                'variant_stock' => (float) ($item->variant->stock ?? 0),
                'variant_price' => (float) $effectivePrice,
                'product_name' => $item->variant->product->name ?? 'Producto',
                'image_src' => $itemVariantImagePath
                  ? (\App\Support\ImageStorage::url($itemVariantImagePath) ?? asset('assets/img/shopix5.png'))
                  : ($itemProductImagePath
                      ? (\App\Support\ImageStorage::url($itemProductImagePath) ?? asset('assets/img/shopix5.png'))
                      : asset('assets/img/shopix5.png')),
                'taxes' => ($item->variant && $item->variant->product && $item->variant->product->taxes)
                  ? $item->variant->product->taxes->map(function ($tax) {
                      return [
                        'name' => $tax->name,
                        'rate' => (float) $tax->rate,
                      ];
                    })->values()->toArray()
                  : [],
              ]],
          'taxes' => ($item->variant && $item->variant->product && $item->variant->product->taxes)
            ? $item->variant->product->taxes->map(function ($tax) {
                return [
                  'name' => $tax->name,
                  'rate' => (float) $tax->rate,
                ];
              })->values()->toArray()
            : [],
        ];
      })->values()->toArray(),
    ];
  })->values()->toArray();
@endphp

<script>
  (() => {
    const tenantSlug = @json($tenant->slug);
    const tenantPackages = @json($tenantPublicPackagePayload);
    const cartEnabled = @json((bool) ($cartEnabled ?? false));
    const tenantName = @json($tenant->name);
    const tenantPhoneCode = @json($tenant->phone_code ?? '');
    const tenantPhoneNumber = @json($tenant->phone_number ?? '');
    const tenantLatitude = @json($tenant->latitude ?? null);
    const tenantLongitude = @json($tenant->longitude ?? null);
    const tenantCountryId = @json($tenant->country ?? null);
    const tenantStateId = @json($tenant->state ?? null);
    const tenantCityId = @json($tenant->city ?? null);
    const tenantDeliveryConfig = @json(\App\Support\DeliveryManager::settings($tenant));
    const showBsPricesInStorefront = @json((bool) ($showBsPricesInStorefront ?? false));
    const storefrontBsRateValue = @json((float) ($storefrontBsRateValue ?? 0));
    const tenantAppointmentAvailabilityEndpoint = `/${tenantSlug}/appointments/public-availability`;
    const initialCsrfToken = @json(csrf_token());
    const googleMapsApiKey = @json(env('GOOGLE_MAPS_API_KEY'));
    const shopixDebug = true;

    function cartDebug(...args) {
      if (!shopixDebug) return;
      console.log('[ShopixCart Debug][Offcanvas]', ...args);
    }

    const storageKey = `shopix_cart_${tenantSlug}`;
    const cartCountElements = Array.from(document.querySelectorAll('.tenant-cart-count'));
    const cartItemsElement = document.getElementById('tenant-cart-items');
    const cartSubtotalElement = document.getElementById('tenant-cart-subtotal');
    const cartSubtotalBsElement = document.getElementById('tenant-cart-subtotal-bs');
    const cartDisabledAlert = document.getElementById('tenant-cart-disabled-alert');
    const checkoutButton = document.getElementById('tenant-cart-checkout');
    const whatsappConsultButton = document.getElementById('tenant-cart-whatsapp-consult');
    const checkoutForm = document.getElementById('tenant-checkout-form');

    const shippingAddressContainer = document.getElementById('tenant-shipping-address-container');
    const shippingAddressLabel = document.getElementById('tenant-shipping-address-label');
    const shippingCountrySelect = document.getElementById('tenant-shipping-country');
    const shippingStateSelect = document.getElementById('tenant-shipping-state');
    const shippingCitySelect = document.getElementById('tenant-shipping-city');
    const shippingDetailWrap = document.getElementById('tenant-shipping-detail-wrap');
    const shippingAddressDetailInput = document.getElementById('tenant-shipping-address-detail');
    const shippingAddressHint = document.getElementById('tenant-shipping-address-hint');
    const deliveryRecipientFields = document.getElementById('tenant-delivery-recipient-fields');
    const deliveryReceiverNameInput = document.getElementById('tenant-delivery-receiver-name');
    const deliveryReceiverPhoneCodeInput = document.getElementById('tenant-delivery-receiver-phone-code');
    const deliveryReceiverPhoneInput = document.getElementById('tenant-delivery-receiver-phone');
    const deliveryExtraInfoInput = document.getElementById('tenant-delivery-extra-info');
    const deliveryPriceInfo = document.getElementById('tenant-delivery-price-info');
    const deliveryUseCustomerDataBtn = document.getElementById('tenant-delivery-use-customer-data');
    const deliveryOpenMapBtn = document.getElementById('tenant-delivery-open-map');
    const shippingDistanceInput = document.getElementById('tenant-shipping-distance');
    const shippingDistanceWrap = document.getElementById('tenant-shipping-distance-wrap');
    const shippingLatitudeInput = document.getElementById('tenant-shipping-latitude');
    const shippingLongitudeInput = document.getElementById('tenant-shipping-longitude');
    const shippingLocationStatus = document.getElementById('tenant-shipping-location-status');
    const shippingLocationSelectCountryWrap = document.getElementById('tenant-shipping-location-selects-country-wrap');
    const shippingLocationSelectStateWrap = document.getElementById('tenant-shipping-location-selects-state-wrap');
    const shippingLocationSelectCityWrap = document.getElementById('tenant-shipping-location-selects-city-wrap');
    const shippingLocationActions = document.getElementById('tenant-shipping-location-actions');
    const shippingUseProfileLocationBtn = document.getElementById('tenant-shipping-use-profile-location');
    const shippingUseCurrentLocationBtn = document.getElementById('tenant-shipping-use-current-location');
    const deliveryTypeInputs = document.querySelectorAll('input[name="tenant-delivery-type"]');
    const tenantPackageFlavorModalElement = document.getElementById('tenantPackageFlavorModal');
    const tenantPackageFlavorSummary = document.getElementById('tenant-package-flavor-summary');
    const tenantPackageFlavorRows = document.getElementById('tenant-package-flavor-rows');
    const tenantConfirmPackageFlavorBtn = document.getElementById('tenant-confirm-package-flavor-btn');

    const proShippingCountrySelect = document.getElementById('tenant-pro-shipping-country');
    const proShippingStateSelect = document.getElementById('tenant-pro-shipping-state');
    const proShippingCitySelect = document.getElementById('tenant-pro-shipping-city');
    const proShippingDetailWrap = document.getElementById('tenant-pro-shipping-detail-wrap');
    const proShippingAddressDetailInput = document.getElementById('tenant-pro-shipping-address-detail');
    const proShippingAddressLabel = document.getElementById('tenant-pro-shipping-address-label');
    const proShippingAddressHint = document.getElementById('tenant-pro-shipping-address-hint');
    const proDeliveryRecipientFields = document.getElementById('tenant-pro-delivery-recipient-fields');
    const proDeliveryReceiverNameInput = document.getElementById('tenant-pro-delivery-receiver-name');
    const proDeliveryReceiverPhoneCodeInput = document.getElementById('tenant-pro-delivery-receiver-phone-code');
    const proDeliveryReceiverPhoneInput = document.getElementById('tenant-pro-delivery-receiver-phone');
    const proDeliveryExtraInfoInput = document.getElementById('tenant-pro-delivery-extra-info');
    const proDeliveryPriceInfo = document.getElementById('tenant-pro-delivery-price-info');
    const proDeliveryFeeSummary = document.getElementById('tenant-pro-delivery-fee');
    const proDeliveryUseCustomerDataBtn = document.getElementById('tenant-pro-delivery-use-customer-data');
    const proDeliveryOpenMapBtn = document.getElementById('tenant-pro-delivery-open-map');
    const proShippingDistanceInput = document.getElementById('tenant-pro-shipping-distance');
    const proShippingDistanceWrap = document.getElementById('tenant-pro-shipping-distance-wrap');
    const proShippingLatitudeInput = document.getElementById('tenant-pro-shipping-latitude');
    const proShippingLongitudeInput = document.getElementById('tenant-pro-shipping-longitude');
    const proShippingLocationStatus = document.getElementById('tenant-pro-shipping-location-status');
    const proShippingLocationSelectCountryWrap = document.getElementById('tenant-pro-shipping-location-selects-country-wrap');
    const proShippingLocationSelectStateWrap = document.getElementById('tenant-pro-shipping-location-selects-state-wrap');
    const proShippingLocationSelectCityWrap = document.getElementById('tenant-pro-shipping-location-selects-city-wrap');
    const proShippingLocationActions = document.getElementById('tenant-pro-shipping-location-actions');
    const proDeliveryStepShell = document.getElementById('tenant-pro-delivery-step-shell');
    const proDeliveryTypeWrap = document.getElementById('tenant-pro-delivery-type-wrap');
    const proAppointmentSection = document.getElementById('tenant-pro-appointment-section');
    const proAppointmentServiceWrap = document.getElementById('tenant-pro-appointment-service-wrap');
    const proAppointmentServiceSelectedWrap = document.getElementById('tenant-pro-appointment-service-selected-wrap');
    const proAppointmentServiceSelectedInput = document.getElementById('tenant-pro-appointment-service-selected');
    const proAppointmentServiceSelect = document.getElementById('tenant-pro-appointment-service');
    const proAppointmentUserSelect = document.getElementById('tenant-pro-appointment-user');
    const proAppointmentDateInput = document.getElementById('tenant-pro-appointment-date');
    const proAppointmentDateDisplayInput = document.getElementById('tenant-pro-appointment-date-display');
    const proAppointmentSlotSelect = document.getElementById('tenant-pro-appointment-slot');
    const proAppointmentSlotNote = document.getElementById('tenant-pro-appointment-slot-note');
    const proAppointmentCalendarGrid = document.getElementById('tenant-pro-appointment-calendar-grid');
    const proAppointmentCalendarLabel = document.getElementById('tenant-pro-appointment-calendar-label');
    const proAppointmentCalendarPrevBtn = document.getElementById('tenant-pro-appointment-calendar-prev');
    const proAppointmentCalendarNextBtn = document.getElementById('tenant-pro-appointment-calendar-next');
    const proAppointmentCalendarNote = document.getElementById('tenant-pro-appointment-calendar-note');
    const proAppointmentPaymentModeSelect = document.getElementById('tenant-pro-appointment-payment-mode');
    const proAppointmentSummaryWrap = document.getElementById('tenant-pro-appointment-summary');
    const proAppointmentSummaryText = document.getElementById('tenant-pro-appointment-summary-text');
    const proPaymentStepNote = document.getElementById('tenant-pro-payment-step-note');
    const proOnSitePaymentNote = document.getElementById('tenant-pro-on-site-payment-note');
    const proSuccessMessage = document.getElementById('tenant-pro-success-message');
    const proAppointmentStatusWrap = document.getElementById('tenant-pro-appointment-status-wrap');
    const proAppointmentStatusBadge = document.getElementById('tenant-pro-appointment-status-badge');
    const proAppointmentStatusNote = document.getElementById('tenant-pro-appointment-status-note');
    const catalogAppointmentSections = Array.from(document.querySelectorAll('[data-shopix-catalog-appointment]'));
    const tenantProCheckoutModalElement = document.getElementById('tenantProCheckoutModal');
    const proShippingUseProfileLocationBtn = document.getElementById('tenant-pro-shipping-use-profile-location');
    const proShippingUseCurrentLocationBtn = document.getElementById('tenant-pro-shipping-use-current-location');
    const deliveryMapModalElement = document.getElementById('tenantDeliveryMapModal');
    const deliveryMapSearchInput = document.getElementById('tenant-delivery-map-search');
    const deliveryMapStatus = document.getElementById('tenant-delivery-map-status');
    const deliveryMapConfirmBtn = document.getElementById('tenant-delivery-map-confirm');

    let countriesCache = null;
    let pendingPackageSelection = null;
    let googleMapsScriptLoaded = false;
    let googleMapsScriptLoading = false;
    let deliveryMap = null;
    let deliveryMapMarker = null;
    let deliveryMapAutocomplete = null;
    let activeDeliveryMapContext = null;
    let pendingDeliveryMapPosition = null;

    async function fetchJson(url) {
      const response = await fetch(url, {
        headers: {
          Accept: 'application/json',
        },
      });

      if (!response.ok) {
        throw new Error('No se pudo cargar información de ubicación.');
      }

      return response.json();
    }

    function resetSelect(selectElement, placeholder, disabled = true) {
      if (!selectElement) return;
      selectElement.innerHTML = `<option value="">${placeholder}</option>`;
      selectElement.disabled = disabled;
    }

    function fillSelect(selectElement, items, placeholder, selectedValue = null) {
      if (!selectElement) return;

      const selectedAsString = selectedValue !== null && selectedValue !== undefined
        ? String(selectedValue)
        : null;

      selectElement.innerHTML = [
        `<option value="">${placeholder}</option>`,
        ...items.map(item => {
          const id = String(item.id);
          const selected = selectedAsString !== null && id === selectedAsString ? ' selected' : '';
          return `<option value="${id}"${selected}>${escapeHtml(item.name)}</option>`;
        })
      ].join('');

      selectElement.disabled = items.length === 0;
    }

    async function getCountries() {
      if (Array.isArray(countriesCache)) {
        return countriesCache;
      }

      const countries = await fetchJson('/get-countries');
      countriesCache = Array.isArray(countries) ? countries : [];
      return countriesCache;
    }

    function getSelectedText(selectElement) {
      if (!selectElement) return '';
      const selectedOption = selectElement.options[selectElement.selectedIndex];
      return selectedOption ? selectedOption.text.trim() : '';
    }

    function normalizePhoneValue(value) {
      return String(value || '').replace(/\D+/g, '').trim();
    }

    function normalizeDialCode(value, fallback = '+58') {
      const digits = String(value || '').replace(/\D+/g, '');
      const fallbackDigits = String(fallback || '+58').replace(/\D+/g, '') || '58';
      return `+${digits || fallbackDigits}`;
    }

    function splitPhoneWithCode(value, fallbackCode = '+58') {
      const cleaned = String(value || '').trim();
      if (!cleaned) {
        return { code: normalizeDialCode(fallbackCode), local: '' };
      }

      const normalized = cleaned.replace(/\s+/g, '');
      if (normalized.startsWith('+')) {
        const digits = normalized.replace(/\D+/g, '');
        const knownCodes = ['58', '1', '52', '57', '51', '54', '34'];
        const matchedCode = knownCodes.find(code => digits.startsWith(code));
        if (matchedCode) {
          return {
            code: `+${matchedCode}`,
            local: digits.slice(matchedCode.length),
          };
        }

        return {
          code: normalizeDialCode(fallbackCode),
          local: digits,
        };
      }

      return {
        code: normalizeDialCode(fallbackCode),
        local: normalizePhoneValue(normalized),
      };
    }

    function composePhoneWithCountryCode(localPhone, dialCode) {
      const localDigits = normalizePhoneValue(localPhone);
      const normalizedCode = normalizeDialCode(dialCode);
      return localDigits ? `${normalizedCode}${localDigits}` : '';
    }

    function getUserPhone(user) {
      return String(user?.phone_number || user?.phone || '').trim();
    }

    function fillReceiverFieldsFromUser(user, nameInput, phoneInput, phoneCodeInput = null) {
      if (!user) {
        alert('Debes iniciar sesión para usar tus datos como receptor.');
        return false;
      }

      if (nameInput) {
        nameInput.value = String(user.name || '').trim();
      }

      if (phoneInput) {
        const phoneParts = splitPhoneWithCode(getUserPhone(user), phoneCodeInput?.value || '+58');
        if (phoneCodeInput) {
          phoneCodeInput.value = phoneParts.code;
        }
        phoneInput.value = phoneParts.local;
      }

      return true;
    }

    function buildDeliveryAddress(receiverNameInput, receiverPhoneInput, extraInfoInput, latitudeInput = null, longitudeInput = null, receiverPhoneCodeInput = null) {
      const receiverName = (receiverNameInput?.value || '').trim();
      const receiverPhone = composePhoneWithCountryCode(
        receiverPhoneInput?.value || '',
        receiverPhoneCodeInput?.value || '+58'
      );
      const extraInfo = (extraInfoInput?.value || '').trim();
      const latitude = latitudeInput?.value ? Number(latitudeInput.value) : null;
      const longitude = longitudeInput?.value ? Number(longitudeInput.value) : null;

      if (!receiverName) {
        return { valid: false, message: 'Indica el nombre de quien recibe el delivery.' };
      }

      if (!receiverPhone) {
        return { valid: false, message: 'Indica el teléfono de quien recibe el delivery.' };
      }

      if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) {
        return { valid: false, message: 'Debes fijar la ubicación exacta del delivery desde ubicación guardada, ubicación actual o Google Maps.' };
      }

      const parts = [`Recibe: ${receiverName}`, `Teléfono: ${receiverPhone}`];

      if (extraInfo) {
        parts.push(`Información adicional: ${extraInfo}`);
      }

      return {
        valid: true,
        cityId: tenantCityId ? Number(tenantCityId) : null,
        address: parts.join(' | '),
        latitude,
        longitude,
        receiverName,
        receiverPhone,
        extraInfo,
      };
    }

    function buildShippingAddress(countrySelect, stateSelect, citySelect, detailInput, latitudeInput = null, longitudeInput = null) {
      const countryId = countrySelect?.value || '';
      const stateId = stateSelect?.value || '';
      const cityId = citySelect?.value || '';
      const detail = (detailInput?.value || '').trim();
      const latitude = latitudeInput?.value ? Number(latitudeInput.value) : null;
      const longitude = longitudeInput?.value ? Number(longitudeInput.value) : null;

      if (!countryId || !stateId || !cityId) {
        return { valid: false, message: 'Selecciona país, estado y ciudad para el envío.' };
      }

      if (!detail) {
        return { valid: false, message: 'Indica la dirección exacta y la agencia para el envío.' };
      }

      const countryName = getSelectedText(countrySelect);
      const stateName = getSelectedText(stateSelect);
      const cityName = getSelectedText(citySelect);

      const parts = [countryName, stateName, cityName].filter(Boolean);
      if (detail) {
        parts.push(detail);
      }

      return {
        valid: true,
        cityId: Number(cityId),
        address: parts.join(', '),
        latitude: Number.isFinite(latitude) ? latitude : null,
        longitude: Number.isFinite(longitude) ? longitude : null,
      };
    }

    function buildAddressForDeliveryType(deliveryType, options = {}) {
      if (deliveryType === 'delivery') {
        return buildDeliveryAddress(
          options.receiverNameInput,
          options.receiverPhoneInput,
          options.extraInfoInput,
          options.latitudeInput,
          options.longitudeInput,
          options.receiverPhoneCodeInput
        );
      }

      if (deliveryType === 'shipping') {
        return buildShippingAddress(
          options.countrySelect,
          options.stateSelect,
          options.citySelect,
          options.detailInput,
          options.latitudeInput,
          options.longitudeInput
        );
      }

      return {
        valid: true,
        cityId: null,
        address: 'Tienda',
        latitude: null,
        longitude: null,
      };
    }

    function calculateDistanceKm(originLat, originLng, destinationLat, destinationLng) {
      const toRad = (value) => (Number(value) * Math.PI) / 180;
      const earthRadiusKm = 6371;
      const dLat = toRad(destinationLat - originLat);
      const dLng = toRad(destinationLng - originLng);
      const a = Math.sin(dLat / 2) * Math.sin(dLat / 2)
        + Math.cos(toRad(originLat)) * Math.cos(toRad(destinationLat))
        * Math.sin(dLng / 2) * Math.sin(dLng / 2);
      const c = 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1 - a));
      return earthRadiusKm * c;
    }

    function estimateDistanceFromCoordinates(latitudeInput, longitudeInput, distanceInput) {
      if (!distanceInput || !Number.isFinite(Number(tenantLatitude)) || !Number.isFinite(Number(tenantLongitude))) {
        return null;
      }

      const latitude = latitudeInput?.value ? Number(latitudeInput.value) : null;
      const longitude = longitudeInput?.value ? Number(longitudeInput.value) : null;
      if (!Number.isFinite(latitude) || !Number.isFinite(longitude)) {
        distanceInput.value = '';
        return null;
      }

      const distanceKm = calculateDistanceKm(Number(tenantLatitude), Number(tenantLongitude), latitude, longitude);
      const normalizedDistanceKm = Number.isFinite(distanceKm) && distanceKm > 0 ? Number(distanceKm.toFixed(2)) : null;
      distanceInput.value = normalizedDistanceKm ? String(normalizedDistanceKm) : '';
      return normalizedDistanceKm;
    }

    function formatDeliveryFeeText(deliveryType, distanceInput) {
      const deliveryContext = getTenantDeliveryContext(deliveryType, distanceInput, false);
      if (deliveryType !== 'delivery') {
        return 'Precio delivery: no aplica para este tipo de entrega.';
      }

      if (!tenantDeliveryConfig?.enabled) {
        return 'Precio delivery: delivery de tienda no disponible.';
      }

      if (!deliveryContext.valid && deliveryContext.message) {
        return `Precio delivery: ${deliveryContext.message}`;
      }

      const distanceLabel = deliveryContext.distanceKm
        ? ` | Distancia: ${deliveryContext.distanceKm.toFixed(2)} km`
        : '';

      return `Precio delivery estimado: ${Number(deliveryContext.fee || 0).toFixed(2)} ${getBaseCurrencySymbol()}${distanceLabel}`;
    }

    function refreshDeliveryUiInfo() {
      const deliveryType = document.querySelector('input[name="tenant-delivery-type"]:checked')?.value || 'pickup';
      const proDeliveryType = document.querySelector('input[name="tenant-pro-delivery-type"]:checked')?.value || 'pickup';

      if (deliveryPriceInfo) {
        deliveryPriceInfo.textContent = formatDeliveryFeeText(deliveryType, shippingDistanceInput);
      }

      if (proDeliveryPriceInfo) {
        proDeliveryPriceInfo.textContent = formatDeliveryFeeText(proDeliveryType, proShippingDistanceInput);
      }

      if (proDeliveryFeeSummary) {
        const proDeliveryContext = getTenantDeliveryContext(proDeliveryType, proShippingDistanceInput, false);
        proDeliveryFeeSummary.textContent = `${Number(proDeliveryContext.fee || 0).toFixed(2)} ${getBaseCurrencySymbol()}`;
      }
    }

    function renderShippingLocationStatus(statusElement, latitudeInput, longitudeInput, distanceInput = null) {
      if (!statusElement) {
        return;
      }

      const latitude = latitudeInput?.value ? Number(latitudeInput.value) : null;
      const longitude = longitudeInput?.value ? Number(longitudeInput.value) : null;

      if (Number.isFinite(latitude) && Number.isFinite(longitude)) {
        const estimatedDistanceKm = estimateDistanceFromCoordinates(latitudeInput, longitudeInput, distanceInput);
        statusElement.textContent = estimatedDistanceKm
          ? `Ubicación exacta fijada: ${latitude.toFixed(6)}, ${longitude.toFixed(6)} | Distancia tienda-cliente: ${estimatedDistanceKm.toFixed(2)} km`
          : `Ubicación exacta fijada: ${latitude.toFixed(6)}, ${longitude.toFixed(6)}`;
        refreshDeliveryUiInfo();
        return;
      }

      statusElement.textContent = 'Aún no se ha fijado una ubicación exacta.';
      estimateDistanceFromCoordinates(latitudeInput, longitudeInput, distanceInput);
      refreshDeliveryUiInfo();
    }

    function applyUserLocationCoordinates(user, latitudeInput, longitudeInput, statusElement, distanceInput = null) {
      if (!user) {
        renderShippingLocationStatus(statusElement, latitudeInput, longitudeInput, distanceInput);
        alert('Debes iniciar sesión para usar tu ubicación guardada.');
        return;
      }

      if (latitudeInput) {
        latitudeInput.value = user.latitude ?? '';
      }

      if (longitudeInput) {
        longitudeInput.value = user.longitude ?? '';
      }

      renderShippingLocationStatus(statusElement, latitudeInput, longitudeInput, distanceInput);
    }

    function applyUserLocationToShippingForm(user, countrySelect, stateSelect, citySelect, detailInput, latitudeInput, longitudeInput, statusElement, distanceInput = null) {
      if (!user) {
        renderShippingLocationStatus(statusElement, latitudeInput, longitudeInput, distanceInput);
        return;
      }

      if (detailInput && typeof user.address === 'string' && user.address.trim() !== '') {
        detailInput.value = user.address.trim();
      }

      if (latitudeInput) {
        latitudeInput.value = user.latitude ?? '';
      }

      if (longitudeInput) {
        longitudeInput.value = user.longitude ?? '';
      }

      renderShippingLocationStatus(statusElement, latitudeInput, longitudeInput, distanceInput);

      initLocationSelectors(countrySelect, stateSelect, citySelect, {
        countryId: user.country_id || tenantCountryId,
        stateId: user.state_id || tenantStateId,
        cityId: user.city_id || tenantCityId,
      }).catch(() => {
      });
    }

    function requestCurrentUserLocation(latitudeInput, longitudeInput, statusElement, distanceInput = null) {
      if (!navigator.geolocation) {
        alert('Tu dispositivo no permite obtener ubicación desde el navegador.');
        return;
      }

      if (statusElement) {
        statusElement.textContent = 'Obteniendo ubicación actual...';
      }

      navigator.geolocation.getCurrentPosition((position) => {
        if (latitudeInput) {
          latitudeInput.value = String(position.coords.latitude || '');
        }

        if (longitudeInput) {
          longitudeInput.value = String(position.coords.longitude || '');
        }

        renderShippingLocationStatus(statusElement, latitudeInput, longitudeInput, distanceInput);
      }, () => {
        renderShippingLocationStatus(statusElement, latitudeInput, longitudeInput, distanceInput);
        alert('No se pudo obtener tu ubicación actual. Revisa los permisos de la app o del navegador.');
      }, {
        enableHighAccuracy: true,
        timeout: 12000,
        maximumAge: 0,
      });
    }

    async function initLocationSelectors(countrySelect, stateSelect, citySelect, defaults = {}) {
      if (!countrySelect || !stateSelect || !citySelect) {
        return;
      }

      const countries = await getCountries();
      fillSelect(countrySelect, countries, 'País', defaults.countryId ?? null);

      const selectedCountryId = countrySelect.value || '';
      if (!selectedCountryId) {
        resetSelect(stateSelect, 'Estado', true);
        resetSelect(citySelect, 'Ciudad', true);
        return;
      }

      const states = await fetchJson(`/get-states/${selectedCountryId}`);
      fillSelect(stateSelect, Array.isArray(states) ? states : [], 'Estado', defaults.stateId ?? null);

      const selectedStateId = stateSelect.value || '';
      if (!selectedStateId) {
        resetSelect(citySelect, 'Ciudad', true);
        return;
      }

      const cities = await fetchJson(`/get-cities/${selectedStateId}`);
      fillSelect(citySelect, Array.isArray(cities) ? cities : [], 'Ciudad', defaults.cityId ?? null);
    }

    function bindLocationSelectorEvents(countrySelect, stateSelect, citySelect) {
      if (!countrySelect || !stateSelect || !citySelect) {
        return;
      }

      countrySelect.addEventListener('change', async () => {
        const countryId = countrySelect.value || '';
        resetSelect(stateSelect, 'Estado', true);
        resetSelect(citySelect, 'Ciudad', true);

        if (!countryId) {
          return;
        }

        try {
          const states = await fetchJson(`/get-states/${countryId}`);
          fillSelect(stateSelect, Array.isArray(states) ? states : [], 'Estado');
        } catch (error) {
          alert('No se pudieron cargar los estados del país seleccionado.');
        }
      });

      stateSelect.addEventListener('change', async () => {
        const stateId = stateSelect.value || '';
        resetSelect(citySelect, 'Ciudad', true);

        if (!stateId) {
          return;
        }

        try {
          const cities = await fetchJson(`/get-cities/${stateId}`);
          fillSelect(citySelect, Array.isArray(cities) ? cities : [], 'Ciudad');
        } catch (error) {
          alert('No se pudieron cargar las ciudades del estado seleccionado.');
        }
      });
    }

    function openTenantCartOffcanvas() {
      const canvasElement = document.getElementById('tenantCartOffcanvas');
      if (!canvasElement || typeof bootstrap === 'undefined' || !bootstrap?.Offcanvas) {
        return;
      }

      const offcanvas = bootstrap.Offcanvas.getOrCreateInstance(canvasElement);
      offcanvas.show();
    }

    function closeTenantCartOffcanvas() {
      const canvasElement = document.getElementById('tenantCartOffcanvas');
      if (!canvasElement) {
        return;
      }

      const offcanvas = bootstrap.Offcanvas.getInstance(canvasElement);
      if (offcanvas) {
        offcanvas.hide();
      }
    }

    document.addEventListener('shopix-cart-command', (event) => {
      const type = event?.detail?.type;

      if (type === 'add-item') {
        addItem(event.detail?.item || {});
        return;
      }

      if (type === 'add-package') {
        openTenantPackageSelector(event.detail?.packageId, event.detail?.packageQty);
        return;
      }

      if (type === 'open-cart') {
        openTenantCartOffcanvas();
        return;
      }

      if (type === 'open-auth') {
        openProCheckout({ authOnly: true });
      }
    });

    function getCart() {
      try {
        const parsed = JSON.parse(localStorage.getItem(storageKey));
        const cart = Array.isArray(parsed) ? parsed : [];
        cartDebug('getCart:ok', cart);
        return cart;
      } catch (error) {
        console.error('[ShopixCart Debug][Offcanvas] getCart:parse-error', error);
        return [];
      }
    }

    function saveCart(cart) {
      localStorage.setItem(storageKey, JSON.stringify(cart));
      cartDebug('saveCart:stored', cart);
      renderCart();
    }

    function dumpCartDebugState(source = 'manual') {
      try {
        const raw = localStorage.getItem(storageKey);
        const parsed = JSON.parse(raw || '[]');
        console.log('[ShopixCart Debug][Dump]', {
          source,
          storageKey,
          raw,
          parsed,
          isArray: Array.isArray(parsed),
          count: Array.isArray(parsed) ? parsed.length : 0,
        });
      } catch (error) {
        console.error('[ShopixCart Debug][Dump] parse-error', error);
      }
    }

    function getSubtotal(cart) {
      return cart.reduce((sum, item) => sum + (Number(item.price) * Number(item.qty)), 0);
    }

    function getBsAmount(baseAmount) {
      return Number(baseAmount || 0) * Number(storefrontBsRateValue || 0);
    }

    function getTenantDeliveryModeLabel(mode, distanceKm = null) {
      if (!tenantDeliveryConfig?.enabled) {
        return 'Retiro en tienda';
      }

      if (mode === 'distance') {
        return distanceKm && distanceKm > 0
          ? `Delivery por km (${distanceKm.toFixed(2)} km)`
          : 'Delivery por km';
      }

      if (mode === 'fixed') {
        return 'Delivery con tarifa fija';
      }

      if (mode === 'free') {
        return 'Delivery gratis';
      }

      return 'Retiro en tienda';
    }

    function getTenantDeliveryContext(deliveryType, distanceInput, strict = false) {
      const normalizedType = ['delivery', 'shipping'].includes(deliveryType) ? deliveryType : 'pickup';
      const distanceKm = Number(distanceInput?.value || 0);

      if (normalizedType === 'pickup') {
        return {
          valid: true,
          fee: 0,
          mode: 'pickup',
          distanceKm: null,
          label: 'Retiro en tienda',
        };
      }

      if (normalizedType === 'shipping') {
        return {
          valid: true,
          fee: 0,
          mode: 'shipping',
          distanceKm: null,
          label: 'Envio por tercero',
        };
      }

      if (!tenantDeliveryConfig?.enabled) {
        return {
          valid: false,
          fee: 0,
          mode: 'pickup',
          distanceKm: null,
          label: 'Retiro en tienda',
          message: 'La tienda no tiene delivery activo.',
        };
      }

      if (tenantDeliveryConfig.mode === 'fixed') {
        return {
          valid: true,
          fee: Number(tenantDeliveryConfig.fixed_fee || 0),
          mode: 'fixed',
          distanceKm: null,
          label: getTenantDeliveryModeLabel('fixed'),
        };
      }

      if (tenantDeliveryConfig.mode === 'distance') {
        if (distanceKm <= 0) {
          return {
            valid: !strict,
            fee: 0,
            mode: 'distance',
            distanceKm: null,
            label: getTenantDeliveryModeLabel('distance'),
            message: 'Debes indicar la distancia estimada del delivery en kilómetros.',
          };
        }

        return {
          valid: true,
          fee: Number(tenantDeliveryConfig.fee_per_km || 0) * distanceKm,
          mode: 'distance',
          distanceKm,
          label: getTenantDeliveryModeLabel('distance', distanceKm),
        };
      }

      return {
        valid: true,
        fee: 0,
        mode: 'free',
        distanceKm: null,
        label: getTenantDeliveryModeLabel('free'),
      };
    }

    function getTotalQty(cart) {
      return cart.reduce((sum, item) => sum + Number(item.qty), 0);
    }

    function updateAddressSectionUi(deliveryType, config) {
      const {
        addressLabelElement,
        detailInput,
        detailWrap,
        hintElement,
        countryWraps,
        locationActionsElement,
        locationStatusElement,
        latitudeInput,
        longitudeInput,
        receiverFieldsElement,
        distanceInput,
      } = config;
      const isStoreDelivery = deliveryType === 'delivery';
      const isThirdPartyShipping = deliveryType === 'shipping';

      if (addressLabelElement) {
        addressLabelElement.textContent = isThirdPartyShipping ? 'Dirección para envío' : 'Dirección para delivery';
      }

      if (detailInput) {
        detailInput.placeholder = isThirdPartyShipping
          ? 'Dirección exacta y agencia de envío preferida'
          : 'Dirección exacta (calle, referencia, etc.)';
      }

      if (detailWrap) {
        detailWrap.classList.toggle('d-none', !isThirdPartyShipping);
      }

      if (hintElement) {
        hintElement.classList.toggle('d-none', !isThirdPartyShipping);
      }

      if (receiverFieldsElement) {
        receiverFieldsElement.classList.toggle('d-none', !isStoreDelivery);
      }

      countryWraps.forEach(wrap => {
        wrap?.classList.toggle('d-none', !isThirdPartyShipping);
      });

      if (locationActionsElement) {
        locationActionsElement.classList.toggle('d-none', !isStoreDelivery);
      }

      if (!isStoreDelivery) {
        if (latitudeInput) {
          latitudeInput.value = '';
        }
        if (longitudeInput) {
          longitudeInput.value = '';
        }
        renderShippingLocationStatus(locationStatusElement, latitudeInput, longitudeInput, distanceInput);
      }

      refreshDeliveryUiInfo();
    }

    function updateDeliveryAddressVisibility() {
      const selectedDeliveryType = document.querySelector('input[name="tenant-delivery-type"]:checked')?.value;
      const isAddressRequired = ['delivery', 'shipping'].includes(selectedDeliveryType);
      const isStoreDelivery = selectedDeliveryType === 'delivery';
      const isThirdPartyShipping = selectedDeliveryType === 'shipping';
      if (shippingAddressContainer) {
        shippingAddressContainer.classList.toggle('d-none', !isAddressRequired);
      }
      updateAddressSectionUi(
        selectedDeliveryType,
        {
          addressLabelElement: shippingAddressLabel,
          detailInput: shippingAddressDetailInput,
          detailWrap: shippingDetailWrap,
          hintElement: shippingAddressHint,
          countryWraps: [shippingLocationSelectCountryWrap, shippingLocationSelectStateWrap, shippingLocationSelectCityWrap],
          locationActionsElement: shippingLocationActions,
          locationStatusElement: shippingLocationStatus,
          latitudeInput: shippingLatitudeInput,
          longitudeInput: shippingLongitudeInput,
          receiverFieldsElement: deliveryRecipientFields,
          distanceInput: shippingDistanceInput,
        }
      );
      if (shippingDistanceWrap) {
        shippingDistanceWrap.classList.toggle('d-none', !(isStoreDelivery && tenantDeliveryConfig?.enabled && tenantDeliveryConfig.mode === 'distance'));
      }

      if (!isStoreDelivery && shippingDistanceInput) {
        shippingDistanceInput.value = '';
      }

      if (isThirdPartyShipping && shippingCountrySelect && !shippingCountrySelect.options.length) {
        initLocationSelectors(shippingCountrySelect, shippingStateSelect, shippingCitySelect, {
          countryId: tenantCountryId,
          stateId: tenantStateId,
          cityId: tenantCityId,
        }).catch(() => {
          alert('No se pudieron cargar los selectores de ubicación de envío.');
        });
      }
    }

    function getCsrfToken() {
      const metaToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
      if (metaToken) {
        return metaToken;
      }

      const cookieMatch = document.cookie.match(/(?:^|; )XSRF-TOKEN=([^;]+)/);
      if (cookieMatch?.[1]) {
        try {
          return decodeURIComponent(cookieMatch[1]);
        } catch (error) {
          return cookieMatch[1];
        }
      }

      return initialCsrfToken || '';
    }

    function renderCart() {
      const cart = getCart();
      const totalQty = getTotalQty(cart);
      const subtotal = getSubtotal(cart);

      cartDebug('renderCart', {
        totalItems: cart.length,
        totalQty,
        subtotal,
      });

      cartCountElements.forEach(el => {
        el.textContent = totalQty;
      });
      cartSubtotalElement.textContent = `${subtotal.toFixed(2)} ${getBaseCurrencySymbol()}`;
      if (cartSubtotalBsElement) {
        if (showBsPricesInStorefront) {
          cartSubtotalBsElement.classList.remove('d-none');
          cartSubtotalBsElement.textContent = `Bs ${getBsAmount(subtotal).toFixed(2)}`;
        } else {
          cartSubtotalBsElement.classList.add('d-none');
        }
      }

      checkoutButton.disabled = cart.length === 0;
      if (whatsappConsultButton) {
        whatsappConsultButton.disabled = cart.length === 0;
      }

      if (cart.length === 0) {
        cartItemsElement.innerHTML = '<p class="tenant-cart-empty">No hay productos en el carrito.</p>';
        checkoutButton.disabled = true;
        if (whatsappConsultButton) {
          whatsappConsultButton.disabled = true;
        }
        return;
      }

      cartItemsElement.innerHTML = cart.map((item, index) => {
        const imageSrc = item.imageSrc || '/assets/img/shopix5.png';
        return `
          <div class="tenant-cart-item-card">
            <div class="d-flex justify-content-between gap-2 align-items-start">
              <div class="d-flex gap-2">
                <img src="${imageSrc}" alt="${escapeHtml(item.productName || 'Producto')}" class="tenant-cart-item-thumb" onerror="this.onerror=null;this.src='/assets/img/shopix5.png';">
                <div>
                <div class="tenant-cart-item-name">${item.productName}</div>
                <div class="tenant-cart-item-variant">Variante: ${item.variantSize}</div>
                <div class="tenant-cart-item-price">${Number(item.price).toFixed(2)} ${getBaseCurrencySymbol()} c/u</div>
                ${showBsPricesInStorefront ? `<div class="tenant-cart-item-variant">Bs ${getBsAmount(Number(item.price)).toFixed(2)} c/u</div>` : ''}
                </div>
              </div>
              <button type="button" class="btn btn-sm btn-outline-danger tenant-cart-remove-btn" data-remove-index="${index}">
                <i class="bi bi-trash"></i>
              </button>
            </div>
            <div class="d-flex align-items-center justify-content-end gap-2 mt-2">
              <button type="button" class="btn btn-sm btn-outline-secondary tenant-cart-qty-btn" data-decrease-index="${index}">-</button>
              <span class="fw-semibold tenant-cart-qty">${item.qty}</span>
              <button type="button" class="btn btn-sm btn-outline-secondary tenant-cart-qty-btn" data-increase-index="${index}">+</button>
            </div>
          </div>
        `;
      }).join('');
    }

    function addItem(item) {
      cartDebug('addItem:input', item);
      const cart = getCart();
      const existingIndex = cart.findIndex(cartItem => (
        Number(cartItem.variantId) === Number(item.variantId)
          && Number(cartItem.price) === Number(item.price)
      ));

      if (existingIndex >= 0) {
        cart[existingIndex].qty += Number(item.qty || 1);
      } else {
        cart.push({
          variantId: Number(item.variantId),
          productId: Number(item.productId),
          productName: item.productName,
          variantSize: item.variantSize,
          imageSrc: item.imageSrc || null,
          price: Number(item.price),
          qty: Number(item.qty || 1)
        });
      }

      cartDebug('addItem:next-cart', cart);
      saveCart(cart);
    }

    function getSelectableVariantsForPackageComponent(component) {
      const selectableVariants = Array.isArray(component.selectable_variants) ? component.selectable_variants : [];
      if (selectableVariants.length > 0) {
        return selectableVariants;
      }

      return [{
        variant_id: Number(component.variant_id),
        variant_size: component.variant_size || '',
        variant_stock: Number(component.variant_stock || 0),
        variant_price: Number(component.variant_price || 0),
        product_name: component.product_name || 'Producto',
        image_src: component.image_src || '/assets/img/shopix5.png',
        taxes: Array.isArray(component.taxes) ? component.taxes : [],
      }];
    }

    function buildPackagePriceScale(pkg) {
      const packageDiscount = Math.max(0, Math.min(100, Number(pkg.discount_percentage || 0)));
      const packageBaseTotal = (pkg.items || []).reduce((sum, row) => {
        const rowQty = Number(row.quantity || 0);
        const rowBasePrice = Number(row.variant_price || 0);
        return sum + (rowBasePrice * ((100 - packageDiscount) / 100) * rowQty);
      }, 0);

      const targetPackageTotal = (pkg.package_price !== null && pkg.package_price !== undefined)
        ? (Number(pkg.package_price) || 0)
        : packageBaseTotal;

      const priceScale = packageBaseTotal > 0 ? (targetPackageTotal / packageBaseTotal) : 1;

      return {
        packageDiscount,
        combinedLineMultiplier: ((100 - packageDiscount) / 100) * priceScale,
      };
    }

    function addFixedTenantPackageToCart(pkg, packQty) {
      const priceConfig = buildPackagePriceScale(pkg);

      (pkg.items || []).forEach(component => {
        const quantity = Number(component.quantity || 0) * packQty;
        if (quantity <= 0) {
          return;
        }

        addItem({
          variantId: Number(component.variant_id),
          productId: Number(component.variant_id),
          productName: `${component.product_name} [${pkg.name}]`,
          variantSize: component.variant_size,
          imageSrc: component.image_src || null,
          price: Number(component.variant_price || 0) * priceConfig.combinedLineMultiplier,
          qty: quantity,
        });
      });

      openTenantCartOffcanvas();
      alert(`Paquete "${pkg.name}" agregado al carrito.`);
    }

    function renderTenantPackageFlavorModal() {
      if (!pendingPackageSelection || !tenantPackageFlavorSummary || !tenantPackageFlavorRows) {
        return;
      }

      const pkg = pendingPackageSelection.package;
      tenantPackageFlavorSummary.innerHTML = `
        <div class="alert alert-light border mb-0">
          <strong>${escapeHtml(pkg.name || 'Paquete')}</strong><br>
          Cantidad de paquetes: ${pendingPackageSelection.packageQty}
        </div>
      `;

      tenantPackageFlavorRows.innerHTML = '';
      pendingPackageSelection.components.forEach((component, componentIndex) => {
        const choicesHtml = component.choices.map((choice, choiceIndex) => `
          <div class="row g-2 align-items-center mb-2">
            <div class="col-12 col-md-6">
              <div class="d-flex align-items-center gap-2">
                <img src="${escapeHtml(choice.image_src || '/assets/img/shopix5.png')}" alt="${escapeHtml(choice.product_name || 'Producto')}" style="width:56px;height:56px;object-fit:cover;border-radius:12px;border:1px solid #e5e7eb;flex-shrink:0;" onerror="this.onerror=null;this.src='/assets/img/shopix5.png';">
                <div>
                  <small class="text-muted d-block">Variante</small>
                  <strong>${escapeHtml(choice.product_name || 'Producto')} ${escapeHtml(choice.variant_size || '')}</strong>
                </div>
              </div>
            </div>
            <div class="col-6 col-md-3">
              <small class="text-muted d-block">Stock</small>
              <span>${choice.variant_stock}</span>
            </div>
            <div class="col-6 col-md-3">
              <small class="text-muted d-block">Cantidad</small>
              <input
                type="number"
                min="0"
                max="${choice.variant_stock}"
                step="0.01"
                class="form-control form-control-sm"
                value="${choice.quantity}"
                data-tenant-package-component-index="${componentIndex}"
                data-tenant-package-choice-index="${choiceIndex}">
            </div>
          </div>
        `).join('');

        tenantPackageFlavorRows.insertAdjacentHTML('beforeend', `
          <div class="card border">
            <div class="card-body">
              <div class="d-flex justify-content-between align-items-center mb-2 gap-2 flex-wrap">
                <h6 class="mb-0">${escapeHtml(component.product_name || 'Producto')}</h6>
                <span class="badge bg-dark">Requerido: ${component.required_qty}</span>
              </div>
              ${choicesHtml}
            </div>
          </div>
        `);
      });
    }

    function openTenantPackageSelector(packageId, packageQty) {
      const pkg = tenantPackages.find(row => Number(row.id) === Number(packageId));
      if (!pkg) {
        alert('No se encontró el paquete.');
        return;
      }

      const packQty = Math.max(1, parseInt(packageQty || '1', 10));
      const hasFlexibleComponents = (pkg.items || []).some(component => String(component.selection_mode || 'variant') === 'product');

      if (!hasFlexibleComponents) {
        addFixedTenantPackageToCart(pkg, packQty);
        return;
      }

      const components = (pkg.items || []).map((component, index) => {
        const requiredQty = (parseFloat(component.quantity) || 0) * packQty;
        const choices = getSelectableVariantsForPackageComponent(component)
          .filter(choice => Number(choice.variant_stock || 0) > 0)
          .map(choice => ({
            variant_id: Number(choice.variant_id),
            variant_size: String(choice.variant_size || ''),
            variant_stock: Number(choice.variant_stock || 0),
            variant_price: Number(choice.variant_price || 0),
            product_name: choice.product_name || component.product_name || 'Producto',
            image_src: choice.image_src || component.image_src || null,
            taxes: Array.isArray(choice.taxes) ? choice.taxes : [],
            quantity: 0,
          }));

        if (choices.length > 0 && requiredQty > 0) {
          const preferredIndex = choices.findIndex(choice => Number(choice.variant_id) === Number(component.variant_id));
          if (preferredIndex >= 0) {
            choices[preferredIndex].quantity = requiredQty;
          } else {
            choices[0].quantity = requiredQty;
          }
        }

        return {
          component_id: `${pkg.id}_${index}`,
          product_name: component.product_name || 'Producto',
          required_qty: requiredQty,
          choices,
        };
      }).filter(component => component.required_qty > 0 && component.choices.length > 0);

      if (components.length === 0) {
        alert('Este paquete no tiene variantes disponibles con stock.');
        return;
      }

      pendingPackageSelection = {
        package: pkg,
        packageQty: packQty,
        components,
      };

      renderTenantPackageFlavorModal();
      if (tenantPackageFlavorModalElement && typeof bootstrap !== 'undefined' && bootstrap?.Modal) {
        bootstrap.Modal.getOrCreateInstance(tenantPackageFlavorModalElement).show();
      }
    }

    function confirmTenantPackageFlavorSelection() {
      if (!pendingPackageSelection) {
        return;
      }

      const pkg = pendingPackageSelection.package;
      const selectedRows = [];
      const priceConfig = buildPackagePriceScale(pkg);

      for (const component of pendingPackageSelection.components) {
        const totalSelectedQty = component.choices.reduce((sum, choice) => sum + Number(choice.quantity || 0), 0);
        if (Math.round(totalSelectedQty * 1000) !== Math.round(Number(component.required_qty || 0) * 1000)) {
          alert(`Debes completar exactamente ${component.required_qty} unidades para ${component.product_name}.`);
          return;
        }

        for (const choice of component.choices) {
          const qty = Number(choice.quantity || 0);
          if (qty <= 0) {
            continue;
          }

          selectedRows.push({
            variant_id: Number(choice.variant_id),
            qty,
            stock: Number(choice.variant_stock || 0),
            product_name: choice.product_name,
            variant_size: choice.variant_size,
            variant_price: Number(choice.variant_price || 0),
            image_src: choice.image_src || null,
          });
        }
      }

      const cart = getCart();
      for (const row of selectedRows) {
        const price = row.variant_price * priceConfig.combinedLineMultiplier;
        const existing = cart.find(item => Number(item.variantId) === Number(row.variant_id) && Number(item.price) === Number(price));
        const nextQty = Number(existing?.qty || 0) + row.qty;
        if (nextQty > Number(row.stock || existing?.stock || 0)) {
          alert(`Stock insuficiente para ${row.product_name} ${row.variant_size || ''}.`);
          return;
        }
      }

      selectedRows.forEach(row => {
        addItem({
          variantId: Number(row.variant_id),
          productId: Number(row.variant_id),
          productName: `${row.product_name} [${pkg.name}]`,
          variantSize: row.variant_size,
          imageSrc: row.image_src || null,
          price: row.variant_price * priceConfig.combinedLineMultiplier,
          qty: row.qty,
        });
      });

      if (tenantPackageFlavorModalElement && typeof bootstrap !== 'undefined' && bootstrap?.Modal) {
        bootstrap.Modal.getOrCreateInstance(tenantPackageFlavorModalElement).hide();
      }

      pendingPackageSelection = null;
      openTenantCartOffcanvas();
      alert(`Paquete "${pkg.name}" agregado al carrito.`);
    }

    function changeQty(index, nextQty) {
      const cart = getCart();
      if (!cart[index]) {
        return;
      }

      if (nextQty <= 0) {
        cart.splice(index, 1);
      } else {
        cart[index].qty = nextQty;
      }

      saveCart(cart);
    }

    function removeItem(index) {
      const cart = getCart();
      if (!cart[index]) {
        return;
      }

      cart.splice(index, 1);
      saveCart(cart);
    }

    function checkoutByWhatsApp(options = {}) {
      const consultOnly = Boolean(options?.consultOnly);
      const cart = getCart();
      if (cart.length === 0) {
        alert('Tu carrito está vacío.');
        return;
      }

      const deliveryType = document.querySelector('input[name="tenant-delivery-type"]:checked')?.value || 'pickup';
      const isStoreDelivery = deliveryType === 'delivery';
      const isThirdPartyShipping = deliveryType === 'shipping';
      const requiresAddress = isStoreDelivery || isThirdPartyShipping;
      const shippingAddressResult = buildAddressForDeliveryType(deliveryType, {
        countrySelect: shippingCountrySelect,
        stateSelect: shippingStateSelect,
        citySelect: shippingCitySelect,
        detailInput: shippingAddressDetailInput,
        latitudeInput: shippingLatitudeInput,
        longitudeInput: shippingLongitudeInput,
        receiverNameInput: deliveryReceiverNameInput,
        receiverPhoneInput: deliveryReceiverPhoneInput,
        receiverPhoneCodeInput: deliveryReceiverPhoneCodeInput,
        extraInfoInput: deliveryExtraInfoInput,
      });
      const authUser = getAuthUser();
      const customerName = (authUser?.name || '').trim();

      if (!consultOnly && requiresAddress && !shippingAddressResult.valid) {
        alert(shippingAddressResult.message);
        return;
      }

      const deliveryContext = getTenantDeliveryContext(deliveryType, shippingDistanceInput, true);
      if (!consultOnly && !deliveryContext.valid) {
        alert(deliveryContext.message || 'Debes completar la información del delivery.');
        return;
      }

      const phone = `${String(tenantPhoneCode).replace(/\D/g, '')}${String(tenantPhoneNumber).replace(/\D/g, '')}`;
      if (!phone) {
        alert('La tienda no tiene un número de WhatsApp configurado.');
        return;
      }

      const lines = [];
      if (consultOnly) {
        lines.push(`Hola ${tenantName}, quiero consultar disponibilidad de este carrito:`);
      } else {
        lines.push(`Hola ${tenantName}, quiero realizar este pedido:`);
      }
      if (customerName) {
        lines.push(`Cliente: ${customerName}`);
      }
      lines.push('');

      cart.forEach((item, idx) => {
        const lineTotal = Number(item.qty) * Number(item.price);
        lines.push(`${idx + 1}. ${item.productName} (${item.variantSize}) x${item.qty} - ${lineTotal.toFixed(2)} ${getBaseCurrencySymbol()}`);
      });

      lines.push('');
      lines.push(`Subtotal: ${getSubtotal(cart).toFixed(2)} ${getBaseCurrencySymbol()}`);
      if (consultOnly) {
        lines.push('Solicitud: Consulta de existencia/disponibilidad antes de confirmar pedido.');
      } else {
        lines.push(`Entrega: ${isStoreDelivery ? 'Delivery tienda' : (isThirdPartyShipping ? 'Envío por tercero' : 'Retiro en tienda')}`);
        if (isStoreDelivery) {
          lines.push(`Costo delivery: ${Number(deliveryContext.fee || 0).toFixed(2)} ${getBaseCurrencySymbol()} (${deliveryContext.label || 'Retiro en tienda'})`);
        }
        if (requiresAddress) {
          if (isStoreDelivery) {
            lines.push(`Recibe: ${shippingAddressResult.receiverName}`);
            lines.push(`Teléfono receptor: ${shippingAddressResult.receiverPhone}`);
            if (shippingAddressResult.extraInfo) {
              lines.push(`Información adicional: ${shippingAddressResult.extraInfo}`);
            }
          } else {
            lines.push(`Dirección: ${shippingAddressResult.address}`);
          }
          if (deliveryContext.distanceKm) {
            lines.push(`Distancia estimada: ${deliveryContext.distanceKm.toFixed(2)} km`);
          }
          if (shippingAddressResult.latitude !== null && shippingAddressResult.longitude !== null) {
            lines.push(`Ubicación exacta: https://www.google.com/maps?q=${shippingAddressResult.latitude},${shippingAddressResult.longitude}`);
          }
        }
      }

      const message = encodeURIComponent(lines.join('\n'));
      const link = `https://wa.me/${phone}?text=${message}`;
      window.open(link, '_blank');
    }

    const authTokenKey = 'shopix_ecomm_token';
    const authUserKey = 'shopix_ecomm_user';
    const authResumeKey = `shopix_resume_checkout_${tenantSlug}`;
    const catalogAppointmentSelectionKey = `shopix_catalog_appointment_${tenantSlug}`;
    const tenantAuthAlert = document.getElementById('tenant-pro-auth-alert');
    let proPaymentMethods = [];
    let proDollarRate = 0;
    let proEuroRate = 0;
    let proBaseRate = 0;
    let proBaseCurrency = 'USD';
    let proIgtfRate = 0;
    let proElectronicInvoicingEnabled = false;
    let proSpecialTaxpayer = false;
    let currentCheckoutStep = 1;
    let appointmentCheckoutEnabled = false;
    let appointmentCheckoutServices = [];
    let appointmentCheckoutProfessionals = [];
    let appointmentCalendarMonth = '';
    let appointmentCalendarDays = [];
    let catalogAppointmentOccupiedSlots = [];
    let catalogTenantOpeningTime = '';
    let catalogTenantClosingTime = '';
    let appointmentLockedServiceId = 0;
    let appointmentStatusPollingInterval = null;
    let trackedAppointmentId = 0;
    let catalogAppointmentWeekStart = '';
    let catalogAppointmentSelectedDate = '';
    let catalogAppointmentSelectedTime = '';
    let catalogAppointmentView = 'week';
    let pendingCatalogAppointmentCheckout = false;

    function clearAppointmentStatusPolling() {
      if (appointmentStatusPollingInterval) {
        clearInterval(appointmentStatusPollingInterval);
        appointmentStatusPollingInterval = null;
      }
    }

    function mapAppointmentStatusToBadge(status) {
      const normalized = String(status || 'scheduled').toLowerCase();

      if (normalized === 'confirmed') {
        return { label: 'Confirmada', className: 'bg-success' };
      }

      if (normalized === 'cancelled') {
        return { label: 'Cancelada', className: 'bg-danger' };
      }

      if (normalized === 'completed') {
        return { label: 'Completada', className: 'bg-primary' };
      }

      if (normalized === 'no_show') {
        return { label: 'No asistió', className: 'bg-dark' };
      }

      return { label: 'Pendiente', className: 'bg-warning text-dark' };
    }

    function renderTrackedAppointmentStatus(appointment) {
      if (!proAppointmentStatusWrap || !proAppointmentStatusBadge || !proAppointmentStatusNote) {
        return;
      }

      if (!appointment || Number(appointment.id || 0) <= 0) {
        proAppointmentStatusWrap.classList.add('d-none');
        return;
      }

      const statusData = mapAppointmentStatusToBadge(appointment.status || appointment.status_label || 'scheduled');
      proAppointmentStatusWrap.classList.remove('d-none');
      proAppointmentStatusBadge.className = `badge ${statusData.className}`;
      proAppointmentStatusBadge.textContent = `Cita ${statusData.label}`;

      const startsAtRaw = String(appointment.starts_at || '').trim();
      const startsAtLabel = startsAtRaw
        ? formatAppointmentSelectedDateLabel(startsAtRaw.slice(0, 10)) + (startsAtRaw.length >= 16 ? ` · ${startsAtRaw.slice(11, 16)}` : '')
        : '';

      proAppointmentStatusNote.textContent = startsAtLabel
        ? `Agenda: ${startsAtLabel}. Actualizamos este estado automáticamente.`
        : 'Actualizamos este estado automáticamente.';
    }

    async function pollTrackedAppointmentStatus() {
      if (trackedAppointmentId <= 0) {
        return;
      }

      const token = getAuthToken();
      if (!token) {
        clearAppointmentStatusPolling();
        return;
      }

      try {
        const response = await fetch('/api/user/appointments?view=all&limit=120', {
          headers: {
            Accept: 'application/json',
            Authorization: `Bearer ${token}`,
          },
        });

        if (!response.ok) {
          return;
        }

        const payload = await response.json().catch(() => ({}));
        const appointments = Array.isArray(payload?.appointments) ? payload.appointments : [];
        const tracked = appointments.find(item => Number(item?.id || 0) === trackedAppointmentId);

        if (!tracked) {
          return;
        }

        renderTrackedAppointmentStatus(tracked);

        const trackedStatus = String(tracked.status || '').toLowerCase();
        if (['cancelled', 'completed', 'no_show'].includes(trackedStatus)) {
          clearAppointmentStatusPolling();
        }
      } catch (error) {
      }
    }

    function startAppointmentStatusPolling(appointment) {
      const appointmentId = Number(appointment?.id || 0);
      trackedAppointmentId = appointmentId;
      clearAppointmentStatusPolling();

      if (appointmentId <= 0) {
        if (proAppointmentStatusWrap) {
          proAppointmentStatusWrap.classList.add('d-none');
        }
        return;
      }

      renderTrackedAppointmentStatus(appointment);
      pollTrackedAppointmentStatus().catch(() => {});
      appointmentStatusPollingInterval = setInterval(() => {
        pollTrackedAppointmentStatus().catch(() => {});
      }, 15000);
    }

    function getSelectedAppointmentService() {
      const selectedId = Number(proAppointmentServiceSelect?.value || 0);
      if (selectedId <= 0) {
        return null;
      }

      return appointmentCheckoutServices.find(service => Number(service?.id || 0) === selectedId) || null;
    }

    function getSelectedAppointmentProfessional() {
      const selectedId = Number(proAppointmentUserSelect?.value || 0);
      if (selectedId <= 0) {
        return null;
      }

      return appointmentCheckoutProfessionals.find(user => Number(user?.id || 0) === selectedId) || null;
    }

    function resolveAppointmentServicePrice(service) {
      const candidates = [
        Number(service?.price || 0),
        Number(service?.effective_price || 0),
        Number(service?.variant_price || 0),
        Number(service?.configured_price || 0),
      ];

      const price = candidates.find(value => Number.isFinite(value) && value > 0);
      return Number.isFinite(price) ? Number(price) : 0;
    }

    function getAppointmentSelectedServiceBaseAmount() {
      if (!isAppointmentCheckoutActive()) {
        return 0;
      }

      const service = getSelectedAppointmentService();
      const servicePrice = resolveAppointmentServicePrice(service);
      return Number.isFinite(servicePrice) && servicePrice > 0 ? servicePrice : 0;
    }

    function getCheckoutProItemsSubtotalBase(cart = []) {
      const baseCartSubtotal = getSubtotal(Array.isArray(cart) ? cart : []);
      if (!isAppointmentCheckoutActive()) {
        return baseCartSubtotal;
      }

      return baseCartSubtotal + getAppointmentSelectedServiceBaseAmount();
    }

    function buildCheckoutProItemsPayload(cart = []) {
      const rows = Array.isArray(cart) ? cart : [];
      const payload = rows.map(item => ({
        variant_id: Number(item?.variantId || 0),
        quantity: Number(item?.qty || 0),
        unit_price: Number(item?.price || 0),
      })).filter(item => item.variant_id > 0 && item.quantity > 0 && item.unit_price > 0);

      if (!isAppointmentCheckoutActive()) {
        return payload;
      }

      const service = getSelectedAppointmentService();
      const serviceVariantId = Number(service?.product_variant_id || 0);
      if (serviceVariantId > 0 && !payload.some(item => Number(item?.variant_id || 0) === serviceVariantId)) {
        const servicePrice = resolveAppointmentServicePrice(service);
        payload.push({
          variant_id: serviceVariantId,
          quantity: 1,
          unit_price: Number.isFinite(servicePrice) && servicePrice > 0 ? servicePrice : null,
        });
      }

      return payload;
    }

    function renderAppointmentSelectionSummary() {
      if (!proAppointmentSummaryWrap || !proAppointmentSummaryText) {
        return;
      }

      if (!isAppointmentCheckoutActive()) {
        proAppointmentSummaryWrap.classList.add('d-none');
        return;
      }

      const service = getSelectedAppointmentService();
      const professional = getSelectedAppointmentProfessional();
      const selectedDate = String(proAppointmentDateInput?.value || '').trim();
      const selectedTime = String(proAppointmentSlotSelect?.value || '').trim();
      const paymentMode = String(proAppointmentPaymentModeSelect?.value || 'online');

      const summaryLines = [];
      if (service) {
        const durationText = Number(service?.duration_minutes || 0) > 0 ? ` (${Number(service.duration_minutes)} min)` : '';
        summaryLines.push(`Servicio: ${String(service.name || 'Servicio')}${durationText}`);
      }
      if (professional) {
        summaryLines.push(`Profesional: ${String(professional.name || 'Profesional')}`);
      }
      if (selectedDate && selectedTime) {
        summaryLines.push(`Agenda: ${formatAppointmentSelectedDateLabel(selectedDate)} a las ${selectedTime}`);
      }
      if (selectedTime) {
        summaryLines.push(`Pago: ${paymentMode === 'on_site' ? 'en el lugar' : 'en línea'}`);
      }

      if (summaryLines.length === 0) {
        proAppointmentSummaryWrap.classList.add('d-none');
        proAppointmentSummaryText.textContent = 'Aún no has completado los datos de tu cita.';
        return;
      }

      proAppointmentSummaryWrap.classList.remove('d-none');
      proAppointmentSummaryText.textContent = summaryLines.join(' | ');
    }

    function parseAppointmentMonth(value) {
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

    function getCurrentLocalDateValue() {
      const nowLocal = new Date();
      return `${nowLocal.getFullYear()}-${String(nowLocal.getMonth() + 1).padStart(2, '0')}-${String(nowLocal.getDate()).padStart(2, '0')}`;
    }

    function getCurrentLocalMonthValue() {
      return getCurrentLocalDateValue().slice(0, 7);
    }

    function shiftMonthValue(monthValue, step) {
      const parsed = parseAppointmentMonth(monthValue);
      const base = parsed
        ? new Date(parsed.year, parsed.month - 1, 1)
        : new Date();

      base.setMonth(base.getMonth() + Number(step || 0));
      return `${base.getFullYear()}-${String(base.getMonth() + 1).padStart(2, '0')}`;
    }

    function findFirstAvailableCalendarDate(calendarDays) {
      const todayIso = getCurrentLocalDateValue();
      return String((Array.isArray(calendarDays)
        ? calendarDays.find(row => !!row?.has_slots && String(row?.date || '') >= todayIso)
        : null)?.date || '').trim();
    }

    function shiftAppointmentCalendarMonth(step) {
      const next = shiftMonthValue(appointmentCalendarMonth, step);
      appointmentCalendarMonth = next;
      renderAppointmentCalendar();
      refreshAppointmentSlots();
    }

    function formatAppointmentCalendarLabel(monthValue) {
      const parsed = parseAppointmentMonth(monthValue);
      if (!parsed) {
        return '-';
      }

      const date = new Date(parsed.year, parsed.month - 1, 1);
      return date.toLocaleDateString('es-ES', { month: 'long', year: 'numeric' });
    }

    function formatAppointmentSelectedDateLabel(dateValue) {
      const normalized = String(dateValue || '').trim();
      if (!/^\d{4}-\d{2}-\d{2}$/.test(normalized)) {
        return '';
      }

      const [yearRaw, monthRaw, dayRaw] = normalized.split('-');
      const parsedDate = new Date(Number(yearRaw), Number(monthRaw) - 1, Number(dayRaw));
      if (Number.isNaN(parsedDate.getTime())) {
        return '';
      }

      return parsedDate.toLocaleDateString('es-ES', {
        weekday: 'long',
        day: '2-digit',
        month: 'long',
        year: 'numeric',
      });
    }

    function readCatalogAppointmentSelection() {
      try {
        const raw = localStorage.getItem(catalogAppointmentSelectionKey);
        const parsed = raw ? JSON.parse(raw) : null;
        return parsed && typeof parsed === 'object' ? parsed : null;
      } catch (error) {
        return null;
      }
    }

    function persistCatalogAppointmentSelection(selection) {
      if (!selection) {
        localStorage.removeItem(catalogAppointmentSelectionKey);
        return;
      }

      localStorage.setItem(catalogAppointmentSelectionKey, JSON.stringify(selection));
    }

    function getCatalogAppointmentPanelControls(section) {
      if (!section) {
        return null;
      }

      return {
        section,
        professionalSelect: section.querySelector('[data-catalog-appointment-professional]'),
        prevWeekButton: section.querySelector('[data-catalog-appointment-prev-week]'),
        todayButton: section.querySelector('[data-catalog-appointment-today]'),
        nextWeekButton: section.querySelector('[data-catalog-appointment-next-week]'),
        daysWrap: section.querySelector('[data-catalog-appointment-days]'),
        note: section.querySelector('[data-catalog-appointment-note]'),
        range: section.querySelector('[data-catalog-appointment-range]'),
        viewButtons: Array.from(section.querySelectorAll('[data-catalog-appointment-view]')),
      };
    }

    function forEachCatalogAppointmentPanel(callback) {
      catalogAppointmentSections.forEach((section) => {
        const controls = getCatalogAppointmentPanelControls(section);
        if (controls) {
          callback(controls);
        }
      });
    }

    function shiftIsoDate(dateValue, days) {
      const normalized = String(dateValue || '').trim();
      if (!/^\d{4}-\d{2}-\d{2}$/.test(normalized)) {
        return '';
      }

      const [yearRaw, monthRaw, dayRaw] = normalized.split('-');
      const date = new Date(Number(yearRaw), Number(monthRaw) - 1, Number(dayRaw));
      if (Number.isNaN(date.getTime())) {
        return '';
      }

      date.setDate(date.getDate() + Number(days || 0));
      return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
    }

    function getStartOfWeekIso(dateValue) {
      const normalized = /^\d{4}-\d{2}-\d{2}$/.test(String(dateValue || '').trim())
        ? String(dateValue).trim()
        : getCurrentLocalDateValue();
      const [yearRaw, monthRaw, dayRaw] = normalized.split('-');
      const date = new Date(Number(yearRaw), Number(monthRaw) - 1, Number(dayRaw));
      if (Number.isNaN(date.getTime())) {
        return getCurrentLocalDateValue();
      }

      const day = date.getDay();
      const diffToMonday = day === 0 ? -6 : 1 - day;
      date.setDate(date.getDate() + diffToMonday);
      return `${date.getFullYear()}-${String(date.getMonth() + 1).padStart(2, '0')}-${String(date.getDate()).padStart(2, '0')}`;
    }

    function formatCatalogAppointmentDayLabel(dateValue) {
      const normalized = String(dateValue || '').trim();
      if (!/^\d{4}-\d{2}-\d{2}$/.test(normalized)) {
        return normalized;
      }

      const [yearRaw, monthRaw, dayRaw] = normalized.split('-');
      const parsedDate = new Date(Number(yearRaw), Number(monthRaw) - 1, Number(dayRaw));
      if (Number.isNaN(parsedDate.getTime())) {
        return normalized;
      }

      return parsedDate.toLocaleDateString('es-ES', {
        weekday: 'short',
        day: '2-digit',
        month: '2-digit',
      });
    }

    function getCatalogSelectedProfessionalId() {
      for (const section of catalogAppointmentSections) {
        const select = section.querySelector('[data-catalog-appointment-professional]');
        const selectedId = Number(select?.value || 0);
        if (selectedId > 0) {
          return selectedId;
        }
      }

      return 0;
    }

    function syncCatalogProfessionalSelection(professionalId) {
      forEachCatalogAppointmentPanel(({ professionalSelect }) => {
        if (!professionalSelect) {
          return;
        }

        professionalSelect.value = professionalId > 0 ? String(professionalId) : '';
      });
    }

    function resolveCatalogAppointmentServiceId(professionalId) {
      const byProfessional = appointmentCheckoutServices.find((service) => {
        const assignedUserId = Number(service?.assigned_user_id || 0);
        return assignedUserId <= 0 || assignedUserId === professionalId;
      });

      return Number(byProfessional?.id || appointmentCheckoutServices[0]?.id || 0);
    }

    function markPendingCatalogAppointmentCheckout(value = true) {
      pendingCatalogAppointmentCheckout = !!value;
    }

    function hasPendingCatalogAppointmentCheckout() {
      return pendingCatalogAppointmentCheckout;
    }

    function normalizeAppointmentStartTimeValue(timeValue) {
      const normalized = String(timeValue || '').trim();
      const match = normalized.match(/^(\d{2}):(\d{2})/);
      if (!match) {
        return normalized;
      }

      return `${match[1]}:${match[2]}`;
    }

    function parseHourMinuteToMinutes(value) {
      const normalized = normalizeAppointmentStartTimeValue(value);
      const match = normalized.match(/^(\d{2}):(\d{2})$/);
      if (!match) {
        return null;
      }

      const hours = Number(match[1]);
      const minutes = Number(match[2]);
      if (!Number.isFinite(hours) || !Number.isFinite(minutes) || hours < 0 || hours > 23 || minutes < 0 || minutes > 59) {
        return null;
      }

      return (hours * 60) + minutes;
    }

    function isOutsideTenantBusinessWindow(hourValue) {
      const openingMinutes = parseHourMinuteToMinutes(catalogTenantOpeningTime);
      const closingMinutes = parseHourMinuteToMinutes(catalogTenantClosingTime);
      const hourMinutes = Number(hourValue) * 60;

      if (!Number.isFinite(hourMinutes)) {
        return false;
      }

      if (openingMinutes !== null && hourMinutes < openingMinutes) {
        return true;
      }

      if (closingMinutes !== null && hourMinutes >= closingMinutes) {
        return true;
      }

      return false;
    }

    function persistCatalogAppointmentSlotSelection(selection = {}) {
      const hasStartTime = Object.prototype.hasOwnProperty.call(selection, 'start_time');
      const selectedProfessionalId = Number(selection.user_id || getCatalogSelectedProfessionalId() || 0);
      const selectedDate = String(selection.date || catalogAppointmentSelectedDate || getCurrentLocalDateValue()).trim();
      const selectedStartTime = hasStartTime
        ? normalizeAppointmentStartTimeValue(selection.start_time || '')
        : normalizeAppointmentStartTimeValue(catalogAppointmentSelectedTime || '');
      const serviceId = Number(selection.service_id || resolveCatalogAppointmentServiceId(selectedProfessionalId) || 0);

      catalogAppointmentSelectedDate = selectedDate;
      catalogAppointmentSelectedTime = selectedStartTime;

      persistCatalogAppointmentSelection({
        user_id: selectedProfessionalId,
        service_id: serviceId > 0 ? serviceId : null,
        date: selectedDate,
        start_time: selectedStartTime,
        view: catalogAppointmentView,
      });
    }

    function findSlotStartTimeByHour(slots = [], hourValue) {
      if (!Array.isArray(slots)) {
        return '';
      }

      const hourNumber = Number(hourValue);
      const slot = slots.find((item) => toHourNumber(item?.start_time || item?.start || '') === hourNumber);
      return normalizeAppointmentStartTimeValue(slot?.start || slot?.start_time || '');
    }

    function buildOccupiedHourSet(occupiedSlots = []) {
      const hours = new Set();
      if (!Array.isArray(occupiedSlots)) {
        return hours;
      }

      occupiedSlots.forEach((slot) => {
        const startHour = toHourNumber(slot?.start || slot?.start_time || '');
        const endHour = toHourNumber(slot?.end || slot?.end_time || '');
        if (!Number.isFinite(startHour)) {
          return;
        }

        const normalizedEnd = Number.isFinite(endHour) && endHour > startHour
          ? endHour
          : startHour + 1;

        for (let hour = startHour; hour < normalizedEnd; hour += 1) {
          hours.add(hour);
        }
      });

      return hours;
    }

    async function openCatalogAppointmentCheckoutFromSlot(dateValue, startTimeValue) {
      const selectedProfessionalId = getCatalogSelectedProfessionalId();
      if (selectedProfessionalId <= 0) {
        return;
      }

      const selectedDate = String(dateValue || '').trim();
      const selectedStartTime = String(startTimeValue || '').trim();
      if (!selectedDate || !selectedStartTime) {
        return;
      }

      persistCatalogAppointmentSlotSelection({
        user_id: selectedProfessionalId,
        service_id: resolveCatalogAppointmentServiceId(selectedProfessionalId),
        date: selectedDate,
        start_time: selectedStartTime,
      });

      markPendingCatalogAppointmentCheckout(true);
      await openProCheckout({ forceAppointment: true });
    }

    function syncCatalogAppointmentViewButtons() {
      forEachCatalogAppointmentPanel(({ viewButtons }) => {
        viewButtons.forEach((button) => {
          const view = String(button.getAttribute('data-catalog-appointment-view') || 'week');
          const active = view === catalogAppointmentView;
          button.classList.toggle('active', active);
          button.classList.toggle('btn-dark', active);
          button.classList.toggle('btn-outline-dark', !active);
        });
      });
    }

    function formatCatalogAgendaHeader(dateValue) {
      const normalized = String(dateValue || '').trim();
      if (!/^\d{4}-\d{2}-\d{2}$/.test(normalized)) {
        return { weekday: '', dateLabel: normalized };
      }

      const [yearRaw, monthRaw, dayRaw] = normalized.split('-');
      const parsedDate = new Date(Number(yearRaw), Number(monthRaw) - 1, Number(dayRaw));
      if (Number.isNaN(parsedDate.getTime())) {
        return { weekday: '', dateLabel: normalized };
      }

      return {
        weekday: parsedDate.toLocaleDateString('en-US', { weekday: 'long' }),
        dateLabel: parsedDate.toLocaleDateString('en-US', { day: '2-digit', month: 'short' }),
      };
    }

    function toHourNumber(timeValue) {
      const normalized = String(timeValue || '').trim();
      const match = normalized.match(/^(\d{2}):(\d{2})/);
      if (!match) {
        return null;
      }

      const hour = Number(match[1]);
      if (!Number.isFinite(hour) || hour < 0 || hour > 23) {
        return null;
      }

      return hour;
    }

    function isPastIsoDate(dateValue) {
      const normalized = String(dateValue || '').trim();
      if (!/^\d{4}-\d{2}-\d{2}$/.test(normalized)) {
        return false;
      }

      return normalized < getCurrentLocalDateValue();
    }

    function isPastDateHour(dateValue, hourValue) {
      const normalized = String(dateValue || '').trim();
      if (!/^\d{4}-\d{2}-\d{2}$/.test(normalized) || !Number.isFinite(Number(hourValue))) {
        return false;
      }

      const [yearRaw, monthRaw, dayRaw] = normalized.split('-');
      const slotDate = new Date(Number(yearRaw), Number(monthRaw) - 1, Number(dayRaw), Number(hourValue), 0, 0, 0);
      if (Number.isNaN(slotDate.getTime())) {
        return false;
      }

      return slotDate.getTime() < Date.now();
    }

    function resolveCatalogCalendarDayState(row, dateValue = '') {
      if (isPastIsoDate(dateValue)) {
        return 'past';
      }

      if (!row || typeof row !== 'object') {
        return 'closed';
      }

      if (row.has_slots === true) {
        return 'available';
      }

      if (row.is_working_day === false) {
        return 'closed';
      }

      return 'occupied';
    }

    function getCatalogDayStateLabel(state) {
      if (state === 'available') {
        return 'Disponible';
      }
      if (state === 'occupied') {
        return 'Ocupada';
      }
      if (state === 'past') {
        return 'Pasado';
      }
      return 'No laborable';
    }

    function buildCatalogDayAgendaHtml(dateValue, row, slots = [], occupiedSlots = []) {
      const header = formatCatalogAgendaHeader(dateValue);
      const state = resolveCatalogCalendarDayState(row, dateValue);
      const hours = Array.from({ length: 16 }).map((_, idx) => idx + 6);
      const slotHours = new Set((Array.isArray(slots) ? slots : []).map(slot => toHourNumber(slot?.start_time)).filter(hour => Number.isFinite(hour)));
      const occupiedHours = buildOccupiedHourSet(occupiedSlots);

      const cells = ['<div class="catalog-agenda-hours-title">HORAS</div>'];
      cells.push(`
        <button type="button" class="catalog-agenda-day-head ${state}" data-catalog-appointment-day="${dateValue}">
          <span class="catalog-agenda-day-weekday">${escapeHtml(header.weekday)}</span>
          <span class="catalog-agenda-day-date">${escapeHtml(header.dateLabel)}</span>
        </button>
      `);

      hours.forEach((hourValue) => {
        const hourLabel = `${String(hourValue).padStart(2, '0')}:00`;
        const isPastHour = isPastDateHour(dateValue, hourValue);
        const isOutsideBusinessHours = isOutsideTenantBusinessWindow(hourValue);
        let badgeHtml = '';

        if (isPastHour) {
          badgeHtml = '';
        } else if (isOutsideBusinessHours) {
          badgeHtml = '<span class="catalog-agenda-pill occupied">Cerrado</span>';
        } else if (state === 'closed') {
          badgeHtml = '<span class="catalog-agenda-pill occupied">Cerrado</span>';
        } else if (state === 'occupied') {
          badgeHtml = '<span class="catalog-agenda-pill occupied">Ocupado</span>';
        } else if (state === 'available' && slotHours.has(hourValue)) {
          badgeHtml = '<span class="catalog-agenda-pill available">Disponible</span>';
        } else if (occupiedHours.has(hourValue)) {
          badgeHtml = '<span class="catalog-agenda-pill occupied">Ocupado</span>';
        }

        cells.push(`<div class="catalog-agenda-hour">${hourLabel}</div>`);
        const slotStartTime = state === 'available' && !isPastHour ? findSlotStartTimeByHour(slots, hourValue) : '';
        cells.push(`<div class="catalog-agenda-cell ${isPastHour ? 'past' : ''} ${slotStartTime ? 'is-clickable' : ''}" ${slotStartTime ? `data-catalog-appointment-slot="${escapeHtml(slotStartTime)}" data-catalog-appointment-date="${dateValue}" data-catalog-appointment-time="${escapeHtml(slotStartTime)}" role="button" tabindex="0" aria-label="Solicitar cita para ${hourLabel}"` : ''}>${badgeHtml}</div>`);
      });

      return `<div class="catalog-agenda-grid day-view">${cells.join('')}</div>`;
    }

    function buildCatalogWeekAgendaHtml(calendarByDate, selectedDate, slots = [], occupiedSlots = []) {
      const weekStart = getStartOfWeekIso(selectedDate);
      const weekDays = Array.from({ length: 7 }).map((_, index) => shiftIsoDate(weekStart, index));
      const hours = Array.from({ length: 16 }).map((_, idx) => idx + 6);
      const selectedDaySlotHours = new Set((Array.isArray(slots) ? slots : []).map(slot => toHourNumber(slot?.start_time)).filter(hour => Number.isFinite(hour)));
      const selectedDayOccupiedHours = buildOccupiedHourSet(occupiedSlots);

      const cells = ['<div class="catalog-agenda-hours-title">HORAS</div>'];
      weekDays.forEach((dateValue) => {
        const header = formatCatalogAgendaHeader(dateValue);
        const isSelected = dateValue === selectedDate;
        cells.push(`
          <button type="button" class="catalog-agenda-day-head ${isSelected ? 'active' : ''}" data-catalog-appointment-day="${dateValue}">
            <span class="catalog-agenda-day-weekday">${escapeHtml(header.weekday)}</span>
            <span class="catalog-agenda-day-date">${escapeHtml(header.dateLabel)}</span>
          </button>
        `);
      });

      hours.forEach((hourValue) => {
        const hourLabel = `${String(hourValue).padStart(2, '0')}:00`;
        cells.push(`<div class="catalog-agenda-hour">${hourLabel}</div>`);

        weekDays.forEach((dateValue) => {
          const dayRow = calendarByDate.get(dateValue);
          const dayState = resolveCatalogCalendarDayState(dayRow, dateValue);
          const hasSlots = dayState === 'available';
          const isSelectedDay = dateValue === selectedDate;
          const isPastHour = isPastDateHour(dateValue, hourValue);
          const isOutsideBusinessHours = isOutsideTenantBusinessWindow(hourValue);
          let badgeHtml = '';

          if (isPastHour) {
            badgeHtml = '';
          } else if (isOutsideBusinessHours) {
            badgeHtml = '<span class="catalog-agenda-pill occupied">Cerrado</span>';
          } else if (dayState === 'past' && hourValue === 13) {
            badgeHtml = '<span class="catalog-agenda-pill past">Pasado</span>';
          } else if (dayState === 'closed') {
            badgeHtml = '<span class="catalog-agenda-pill occupied">Cerrado</span>';
          } else if (dayState === 'occupied') {
            badgeHtml = '<span class="catalog-agenda-pill occupied">Sin citas</span>';
          } else if (isSelectedDay && selectedDaySlotHours.has(hourValue)) {
            badgeHtml = '<span class="catalog-agenda-pill available">Disponible</span>';
          } else if (isSelectedDay && selectedDayOccupiedHours.has(hourValue)) {
            badgeHtml = '<span class="catalog-agenda-pill occupied">Ocupado</span>';
          } else if (!isSelectedDay && hasSlots && hourValue === 9) {
            badgeHtml = '<span class="catalog-agenda-pill available">Con cupos</span>';
          }

          const slotStartTime = isSelectedDay && dayState === 'available' && !isPastHour
            ? findSlotStartTimeByHour(slots, hourValue)
            : '';
          cells.push(`<div class="catalog-agenda-cell ${isPastHour ? 'past' : ''} ${slotStartTime ? 'is-clickable' : ''}" ${slotStartTime ? `data-catalog-appointment-slot="${escapeHtml(slotStartTime)}" data-catalog-appointment-date="${dateValue}" data-catalog-appointment-time="${escapeHtml(slotStartTime)}" role="button" tabindex="0" aria-label="Solicitar cita para ${hourLabel}"` : ''}>${badgeHtml}</div>`);
        });
      });

      return {
        html: `<div class="catalog-agenda-grid">${cells.join('')}</div>`,
        rangeLabel: `${formatCatalogAppointmentDayLabel(weekStart)} - ${formatCatalogAppointmentDayLabel(weekDays[6] || selectedDate)}`,
      };
    }

    function renderCatalogAppointmentCalendar(calendarDays, slots = [], occupiedSlots = []) {
      const calendarByDate = new Map((Array.isArray(calendarDays) ? calendarDays : []).map(row => [String(row?.date || ''), {
        has_slots: !!row?.has_slots,
        is_working_day: row?.is_working_day !== false,
      }]));
      const selectedDate = String(catalogAppointmentSelectedDate || getCurrentLocalDateValue()).trim();
      const selectedMonth = getMonthFromDateValue(selectedDate) || getCurrentLocalMonthValue();

      forEachCatalogAppointmentPanel(({ daysWrap, range }) => {
        if (!daysWrap) {
          return;
        }

        if (catalogAppointmentView === 'day') {
          const selectedRow = calendarByDate.get(selectedDate) || {
            has_slots: slots.length > 0,
            is_working_day: true,
          };
          daysWrap.innerHTML = buildCatalogDayAgendaHtml(selectedDate, selectedRow, slots, occupiedSlots);
          if (range) {
            range.textContent = formatAppointmentSelectedDateLabel(selectedDate);
          }
          return;
        }

        if (catalogAppointmentView === 'week') {
          const weekAgenda = buildCatalogWeekAgendaHtml(calendarByDate, selectedDate, slots, occupiedSlots);
          daysWrap.innerHTML = weekAgenda.html;
          if (range) {
            range.textContent = weekAgenda.rangeLabel;
          }
          return;
        }

        const parsedMonth = parseAppointmentMonth(selectedMonth);
        if (!parsedMonth) {
          daysWrap.innerHTML = '';
          if (range) {
            range.textContent = '-';
          }
          return;
        }

        const monthDate = new Date(parsedMonth.year, parsedMonth.month - 1, 1);
        const monthEnd = new Date(parsedMonth.year, parsedMonth.month, 0);
        const startWeekday = (monthDate.getDay() + 6) % 7;
        const totalDays = monthEnd.getDate();
        const weekdayLabels = ['L', 'M', 'X', 'J', 'V', 'S', 'D'];

        const cells = [];
        weekdayLabels.forEach(label => {
          cells.push(`<div class="small text-muted text-center">${label}</div>`);
        });
        for (let i = 0; i < startWeekday; i += 1) {
          cells.push('<div></div>');
        }
        for (let day = 1; day <= totalDays; day += 1) {
          const dateValue = `${parsedMonth.year}-${String(parsedMonth.month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
          const dayRow = calendarByDate.get(dateValue);
          const state = resolveCatalogCalendarDayState(dayRow, dateValue);
          const isSelected = selectedDate === dateValue;
          const toneClass = state === 'available'
            ? 'btn-success'
            : ((state === 'closed' || state === 'past') ? 'btn-secondary' : 'btn-danger');
          cells.push(`<button type="button" class="btn btn-sm w-100 ${toneClass} ${isSelected ? 'catalog-day-selected' : ''}" data-catalog-appointment-day="${dateValue}">${String(day).padStart(2, '0')}</button>`);
        }

        daysWrap.innerHTML = `<div class="d-grid gap-1" style="grid-template-columns: repeat(7, minmax(0, 1fr));">${cells.join('')}</div>`;
        if (range) {
          range.textContent = formatAppointmentCalendarLabel(selectedMonth);
        }
      });
    }

    async function refreshCatalogAppointmentSlots() {
      const selectedProfessionalId = getCatalogSelectedProfessionalId();
      const selectedDate = String(catalogAppointmentSelectedDate || getCurrentLocalDateValue()).trim();

      if (selectedProfessionalId <= 0) {
        forEachCatalogAppointmentPanel(({ daysWrap, note, range }) => {
          if (daysWrap) {
            daysWrap.innerHTML = '';
          }
          if (range) {
            range.textContent = '-';
          }
          if (note) {
            note.textContent = 'Selecciona un profesional para visualizar ocupación.';
          }
        });
        return;
      }

      const serviceId = resolveCatalogAppointmentServiceId(selectedProfessionalId);
      if (serviceId <= 0) {
        forEachCatalogAppointmentPanel(({ daysWrap, note, range }) => {
          if (daysWrap) {
            daysWrap.innerHTML = '';
          }
          if (range) {
            range.textContent = '-';
          }
          if (note) {
            note.textContent = 'No hay servicios de citas configurados para mostrar agenda.';
          }
        });
        return;
      }

      const month = getMonthFromDateValue(selectedDate) || getCurrentLocalMonthValue();
      const params = new URLSearchParams({
        service_id: String(serviceId),
        user_id: String(selectedProfessionalId),
        date: selectedDate,
        month,
      });

      let payload = {};
      try {
        const response = await fetch(`${tenantAppointmentAvailabilityEndpoint}?${params.toString()}`, {
          headers: { Accept: 'application/json' },
        });

        if (!response.ok) {
          throw new Error('catalog_slots_failed');
        }

        payload = await response.json().catch(() => ({}));
      } catch (error) {
        forEachCatalogAppointmentPanel(({ daysWrap, note }) => {
          if (daysWrap) {
            daysWrap.innerHTML = '';
          }
          if (note) {
            note.textContent = 'No se pudo consultar disponibilidad en este momento.';
          }
        });
        return;
      }

      const calendar = Array.isArray(payload?.calendar) ? payload.calendar : [];
      const slots = Array.isArray(payload?.slots) ? payload.slots : [];
      catalogAppointmentOccupiedSlots = Array.isArray(payload?.occupied_slots) ? payload.occupied_slots : [];
      catalogTenantOpeningTime = normalizeAppointmentStartTimeValue(payload?.tenant_opening_time || '');
      catalogTenantClosingTime = normalizeAppointmentStartTimeValue(payload?.tenant_closing_time || '');
      renderCatalogAppointmentCalendar(calendar, slots, catalogAppointmentOccupiedSlots);

      forEachCatalogAppointmentPanel(({ note }) => {
        if (!note) {
          return;
        }

        if (catalogAppointmentView === 'day') {
          const selectedRow = calendar.find(row => String(row?.date || '') === selectedDate) || null;
          const state = resolveCatalogCalendarDayState(selectedRow, selectedDate);
          note.textContent = state === 'available'
            ? `Día disponible (${slots.length} horario(s)).`
            : (state === 'closed'
              ? 'No laboran este día en la tienda.'
              : (state === 'past' ? 'Este día ya pasó.' : 'Día ocupado o sin horarios disponibles.'));
          return;
        }

        const availableDays = calendar.filter(row => !!row?.has_slots).length;
        note.textContent = availableDays > 0
          ? `${availableDays} día(s) disponibles en ${formatAppointmentCalendarLabel(month)}.`
          : `No hay días disponibles en ${formatAppointmentCalendarLabel(month)}.`;
      });

      persistCatalogAppointmentSlotSelection({
        user_id: selectedProfessionalId,
        date: selectedDate,
      });
    }

    function syncCatalogAppointmentProfessionals() {
      const options = ['<option value="">Selecciona un profesional</option>', ...appointmentCheckoutProfessionals.map((professional) => `<option value="${Number(professional?.id || 0)}">${escapeHtml(String(professional?.name || 'Profesional'))}</option>`)];

      forEachCatalogAppointmentPanel(({ professionalSelect }) => {
        if (!professionalSelect) {
          return;
        }

        const previousValue = professionalSelect.value;
        professionalSelect.innerHTML = options.join('');
        if (previousValue && appointmentCheckoutProfessionals.some(professional => Number(professional?.id || 0) === Number(previousValue))) {
          professionalSelect.value = previousValue;
        }
      });

      let selectedProfessionalId = getCatalogSelectedProfessionalId();
      if (selectedProfessionalId <= 0 && appointmentCheckoutProfessionals.length > 0) {
        selectedProfessionalId = Number(appointmentCheckoutProfessionals[0]?.id || 0);
        syncCatalogProfessionalSelection(selectedProfessionalId);
      }
    }

    async function refreshCatalogAppointmentSection() {
      if (catalogAppointmentSections.length === 0) {
        return;
      }

      if (!appointmentCheckoutEnabled || appointmentCheckoutServices.length === 0 || appointmentCheckoutProfessionals.length === 0) {
        forEachCatalogAppointmentPanel(({ section }) => {
          section.classList.add('d-none');
        });
        return;
      }

      forEachCatalogAppointmentPanel(({ section }) => {
        section.classList.remove('d-none');
      });

      const persisted = readCatalogAppointmentSelection();
      if (persisted) {
        catalogAppointmentSelectedDate = String(persisted?.date || '').trim() || catalogAppointmentSelectedDate;
        catalogAppointmentSelectedTime = normalizeAppointmentStartTimeValue(persisted?.start_time || '');
        catalogAppointmentView = ['day', 'week', 'month'].includes(String(persisted?.view || '').trim())
          ? String(persisted.view).trim()
          : catalogAppointmentView;
      }

      if (!catalogAppointmentSelectedDate) {
        catalogAppointmentSelectedDate = getCurrentLocalDateValue();
      }

      syncCatalogAppointmentProfessionals();
      syncCatalogAppointmentViewButtons();
      await refreshCatalogAppointmentSlots();
    }

    function bindCatalogAppointmentSectionEvents() {
      if (catalogAppointmentSections.length === 0) {
        return;
      }

      forEachCatalogAppointmentPanel((controls) => {
        const {
          section,
          professionalSelect,
          prevWeekButton,
          todayButton,
          nextWeekButton,
          daysWrap,
          viewButtons,
        } = controls;

        if (!section || section.dataset.catalogAppointmentBound === '1') {
          return;
        }

        section.dataset.catalogAppointmentBound = '1';

        professionalSelect?.addEventListener('change', async () => {
          const professionalId = Number(professionalSelect.value || 0);
          syncCatalogProfessionalSelection(professionalId);
          catalogAppointmentSelectedTime = '';
          await refreshCatalogAppointmentSlots();
        });

        viewButtons.forEach((button) => {
          button.addEventListener('click', async () => {
            const nextView = String(button.getAttribute('data-catalog-appointment-view') || 'week');
            if (!['day', 'week', 'month'].includes(nextView)) {
              return;
            }

            catalogAppointmentView = nextView;
            catalogAppointmentSelectedTime = '';
            syncCatalogAppointmentViewButtons();
            await refreshCatalogAppointmentSlots();
          });
        });

        prevWeekButton?.addEventListener('click', async () => {
          if (catalogAppointmentView === 'month') {
            const currentMonth = getMonthFromDateValue(catalogAppointmentSelectedDate || getCurrentLocalDateValue()) || getCurrentLocalMonthValue();
            const prevMonth = shiftMonthValue(currentMonth, -1);
            catalogAppointmentSelectedDate = `${prevMonth}-01`;
          } else {
            const step = catalogAppointmentView === 'day' ? -1 : -7;
            catalogAppointmentSelectedDate = shiftIsoDate(catalogAppointmentSelectedDate || getCurrentLocalDateValue(), step);
          }
          catalogAppointmentSelectedTime = '';
          await refreshCatalogAppointmentSlots();
        });

        nextWeekButton?.addEventListener('click', async () => {
          if (catalogAppointmentView === 'month') {
            const currentMonth = getMonthFromDateValue(catalogAppointmentSelectedDate || getCurrentLocalDateValue()) || getCurrentLocalMonthValue();
            const nextMonth = shiftMonthValue(currentMonth, 1);
            catalogAppointmentSelectedDate = `${nextMonth}-01`;
          } else {
            const step = catalogAppointmentView === 'day' ? 1 : 7;
            catalogAppointmentSelectedDate = shiftIsoDate(catalogAppointmentSelectedDate || getCurrentLocalDateValue(), step);
          }
          catalogAppointmentSelectedTime = '';
          await refreshCatalogAppointmentSlots();
        });

        todayButton?.addEventListener('click', async () => {
          catalogAppointmentSelectedDate = getCurrentLocalDateValue();
          catalogAppointmentSelectedTime = '';
          await refreshCatalogAppointmentSlots();
        });

        daysWrap?.addEventListener('click', async (event) => {
          const slotTrigger = event.target.closest('[data-catalog-appointment-slot]');
          if (slotTrigger) {
            if (slotTrigger.dataset.loading === '1') {
              return;
            }

            const slotDate = String(slotTrigger.getAttribute('data-catalog-appointment-date') || '').trim();
            const slotStartTime = String(slotTrigger.getAttribute('data-catalog-appointment-time') || '').trim();
            if (slotDate && slotStartTime) {
              slotTrigger.dataset.loading = '1';
              slotTrigger.classList.add('is-loading');
              try {
                await openCatalogAppointmentCheckoutFromSlot(slotDate, slotStartTime);
              } finally {
                slotTrigger.dataset.loading = '0';
                slotTrigger.classList.remove('is-loading');
              }
            }
            return;
          }

          const button = event.target.closest('[data-catalog-appointment-day]');
          if (!button || button.hasAttribute('disabled')) {
            return;
          }

          const selectedDate = String(button.getAttribute('data-catalog-appointment-day') || '').trim();
          if (!selectedDate) {
            return;
          }

          catalogAppointmentSelectedDate = selectedDate;
          catalogAppointmentSelectedTime = '';
          await refreshCatalogAppointmentSlots();
        });
      });
    }

    async function applyCatalogAppointmentSelectionToCheckout() {
      const selection = readCatalogAppointmentSelection();
      if (!selection || !isAppointmentCheckoutActive()) {
        markPendingCatalogAppointmentCheckout(false);
        return;
      }

      const serviceId = Number(selection?.service_id || 0);
      const userId = Number(selection?.user_id || 0);
      const date = String(selection?.date || '').trim();
      const startTime = normalizeAppointmentStartTimeValue(selection?.start_time || '');

      if (serviceId > 0 && proAppointmentServiceSelect && appointmentLockedServiceId <= 0 && appointmentCheckoutServices.some(service => Number(service?.id || 0) === serviceId)) {
        proAppointmentServiceSelect.value = String(serviceId);
      }

      syncAppointmentProfessionalByService();

      if (userId > 0 && proAppointmentUserSelect && appointmentCheckoutProfessionals.some(user => Number(user?.id || 0) === userId)) {
        proAppointmentUserSelect.value = String(userId);
      }

      if (date && proAppointmentDateInput) {
        proAppointmentDateInput.value = date;
        appointmentCalendarMonth = getMonthFromDateValue(date) || appointmentCalendarMonth;
        syncSelectedAppointmentDateDisplay();
      }

      await refreshAppointmentSlots();

      if (startTime && proAppointmentSlotSelect) {
        const matchingOption = Array.from(proAppointmentSlotSelect.options || []).find((option) => normalizeAppointmentStartTimeValue(option.value || '') === startTime);
        if (matchingOption) {
          proAppointmentSlotSelect.value = String(matchingOption.value || '');
        }
      }

      syncAppointmentPaymentModeUi();
      renderAppointmentSelectionSummary();
      markPendingCatalogAppointmentCheckout(false);
    }

    function syncSelectedAppointmentDateDisplay() {
      if (!proAppointmentDateDisplayInput) {
        return;
      }

      const selectedDate = String(proAppointmentDateInput?.value || '').trim();
      proAppointmentDateDisplayInput.value = formatAppointmentSelectedDateLabel(selectedDate);
      renderAppointmentSelectionSummary();
    }

    function resolveLockedAppointmentServiceId() {
      const cart = getCart();
      const variantIds = Array.isArray(cart)
        ? cart
          .map(item => Number(item?.variantId || 0))
          .filter(variantId => variantId > 0)
        : [];

      if (variantIds.length === 0) {
        return 0;
      }

      const service = appointmentCheckoutServices.find(serviceItem => {
        const variantId = Number(serviceItem?.product_variant_id || 0);
        return variantId > 0 && variantIds.includes(variantId);
      });

      return service ? Number(service.id || 0) : 0;
    }

    function syncAppointmentServiceSelectionFromCart() {
      appointmentLockedServiceId = resolveLockedAppointmentServiceId();

      if (proAppointmentServiceSelect) {
        if (appointmentLockedServiceId > 0) {
          proAppointmentServiceSelect.value = String(appointmentLockedServiceId);
          proAppointmentServiceSelect.disabled = true;
        } else {
          proAppointmentServiceSelect.disabled = false;
        }
      }

      const selectedServiceOption = proAppointmentServiceSelect?.selectedOptions?.[0] || null;
      const selectedServiceName = String(selectedServiceOption?.textContent || '').trim();

      if (proAppointmentServiceSelectedInput) {
        proAppointmentServiceSelectedInput.value = selectedServiceName;
      }

      proAppointmentServiceWrap?.classList.toggle('d-none', appointmentLockedServiceId > 0);
      proAppointmentServiceSelectedWrap?.classList.toggle('d-none', appointmentLockedServiceId <= 0);
      renderAppointmentSelectionSummary();
    }

    function renderAppointmentCalendar() {
      if (!proAppointmentCalendarGrid) {
        return;
      }

      const parsed = parseAppointmentMonth(appointmentCalendarMonth || getMonthFromDateValue(proAppointmentDateInput?.value || ''));
      if (!parsed) {
        proAppointmentCalendarGrid.innerHTML = '';
        if (proAppointmentCalendarLabel) {
          proAppointmentCalendarLabel.textContent = '-';
        }
        return;
      }

      const monthStart = new Date(parsed.year, parsed.month - 1, 1);
      const monthEnd = new Date(parsed.year, parsed.month, 0);
      const selectedDate = String(proAppointmentDateInput?.value || '').trim();
      const todayIso = getCurrentLocalDateValue();
      const weekdayLabels = ['L', 'M', 'X', 'J', 'V', 'S', 'D'];
      const startWeekday = (monthStart.getDay() + 6) % 7;
      const totalDays = monthEnd.getDate();

      if (proAppointmentCalendarLabel) {
        proAppointmentCalendarLabel.textContent = formatAppointmentCalendarLabel(appointmentCalendarMonth);
      }

      const cells = [];
      weekdayLabels.forEach(label => {
        cells.push(`<div class="small text-muted text-center">${label}</div>`);
      });

      for (let index = 0; index < startWeekday; index += 1) {
        cells.push('<div></div>');
      }

      const calendarByDate = new Map((appointmentCalendarDays || []).map(row => [String(row.date || ''), row]));

      for (let day = 1; day <= totalDays; day += 1) {
        const dateValue = `${parsed.year}-${String(parsed.month).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
        const row = calendarByDate.get(dateValue);
        const slotsCount = Number(row?.slots_count || 0);
        const hasSlots = !!row?.has_slots;
        const isSelected = selectedDate === dateValue;
        const isToday = !!row?.is_today;
        const isPastDate = dateValue < todayIso;
        const isEnabled = hasSlots && !isPastDate;
        const buttonClass = isSelected
          ? 'btn btn-dark btn-sm w-100'
          : (hasSlots ? 'btn btn-outline-dark btn-sm w-100' : 'btn btn-outline-secondary btn-sm w-100');
        const title = hasSlots
          ? `${slotsCount} horario(s)`
          : (isPastDate ? 'Fecha pasada' : 'Sin horarios disponibles');

        cells.push(`
          <button type="button" class="${buttonClass}" data-appointment-calendar-date="${dateValue}" ${isEnabled ? '' : 'disabled'} title="${title}">
            <span>${day}</span>${isToday ? '<span class="d-block" style="font-size:10px;line-height:1;">Hoy</span>' : ''}
          </button>
        `);
      }

      proAppointmentCalendarGrid.innerHTML = cells.join('');
    }

    function getCheckoutSummaryElement() {
      return document.querySelector('#tenant-pro-checkout-section .tenant-pro-summary.is-compact');
    }

    function getCheckoutStepperElement() {
      return document.getElementById('tenant-pro-stepper');
    }

    function getCheckoutStepPanels() {
      return Array.from(document.querySelectorAll('[data-checkout-step-panel]'));
    }

    function resetCheckoutSuccessState() {
      const successLink = document.getElementById('tenant-pro-success-link');
      if (successLink) {
        successLink.classList.add('d-none');
        successLink.setAttribute('href', '#');
      }
    }

    function setCheckoutStep(step) {
      currentCheckoutStep = step;

      const stepper = getCheckoutStepperElement();
      const panels = getCheckoutStepPanels();
      const summary = getCheckoutSummaryElement();
      const prevButton = document.getElementById('tenant-pro-prev-step');
      const nextButton = document.getElementById('tenant-pro-next-step');
      const submitButton = document.getElementById('tenant-pro-submit-order');
      const modalTitle = document.getElementById('tenantProCheckoutModalLabel');
      const modalFooter = document.getElementById('tenantProCheckoutModalFooter');

      stepper?.querySelectorAll('.tenant-pro-step').forEach(stepElement => {
        const stepNumber = Number(stepElement.dataset.step || 0);
        stepElement.classList.toggle('is-active', stepNumber === currentCheckoutStep);
        stepElement.classList.toggle('is-complete', stepNumber < currentCheckoutStep);
      });

      panels.forEach(panel => {
        const panelStep = Number(panel.dataset.checkoutStepPanel || 0);
        panel.classList.toggle('d-none', panelStep !== currentCheckoutStep);
      });

      if (summary) {
        summary.classList.toggle('d-none', currentCheckoutStep === 3);
      }

      if (stepper) {
        stepper.classList.toggle('d-none', currentCheckoutStep === 3);
      }

      if (prevButton) {
        prevButton.classList.toggle('d-none', currentCheckoutStep !== 2);
      }

      if (nextButton) {
        nextButton.classList.toggle('d-none', currentCheckoutStep !== 1);
      }

      if (submitButton) {
        submitButton.classList.toggle('d-none', currentCheckoutStep !== 2);
      }

      if (modalTitle) {
        modalTitle.textContent = currentCheckoutStep === 3 ? 'Pedido enviado' : 'Checkout';
      }
    }

    function isAppointmentCheckoutActive() {
      return !!appointmentCheckoutEnabled;
    }

    function isAppointmentOnSitePayment() {
      if (!isAppointmentCheckoutActive()) {
        return false;
      }

      return String(proAppointmentPaymentModeSelect?.value || 'online') === 'on_site';
    }

    function applyAppointmentCheckoutUiState() {
      const isAppointment = isAppointmentCheckoutActive();
      const stepOneLabel = document.querySelector('.tenant-pro-step[data-step="1"] .tenant-pro-step-label');
      const stepOneTitle = proDeliveryStepShell?.querySelector('h6');
      const stepOneNote = proDeliveryStepShell?.querySelector('.tenant-pro-step-note');

      if (stepOneLabel) {
        stepOneLabel.textContent = isAppointment ? 'Agenda' : 'Entrega';
      }

      if (stepOneTitle) {
        stepOneTitle.textContent = isAppointment ? 'Agenda tu cita' : 'Tipo de entrega';
      }

      if (stepOneNote) {
        stepOneNote.textContent = isAppointment
          ? 'Selecciona profesional, fecha/hora y luego confirma cómo pagar tu cita.'
          : 'Primero define cómo recibirás tu compra.';
      }

      document.querySelectorAll('input[name="tenant-pro-delivery-type"]').forEach(input => {
        input.checked = input.value === 'pickup';
      });

      proDeliveryTypeWrap?.classList.toggle('d-none', isAppointment);

      if (isAppointment) {
        document.getElementById('tenant-pro-shipping-address-container')?.classList.add('d-none');
      }

      proAppointmentSection?.classList.toggle('d-none', !isAppointment);
      renderAppointmentSelectionSummary();
      syncAppointmentPaymentModeUi();
    }

    function populateAppointmentSelectors() {
      if (!isAppointmentCheckoutActive()) {
        return;
      }

      if (proAppointmentServiceSelect) {
        proAppointmentServiceSelect.innerHTML = [
          '<option value="">Selecciona un servicio</option>',
          ...appointmentCheckoutServices.map(service => `<option value="${Number(service.id)}" data-assigned-user-id="${service.assigned_user_id ? Number(service.assigned_user_id) : ''}" data-product-variant-id="${service.product_variant_id ? Number(service.product_variant_id) : ''}">${escapeHtml(service.name || 'Servicio')}</option>`),
        ].join('');
      }

      syncAppointmentServiceSelectionFromCart();

      if (proAppointmentUserSelect) {
        proAppointmentUserSelect.innerHTML = [
          '<option value="">Selecciona un profesional</option>',
          ...appointmentCheckoutProfessionals.map(professional => `<option value="${Number(professional.id)}">${escapeHtml(professional.name || 'Profesional')}</option>`),
        ].join('');

        if (appointmentCheckoutProfessionals.length === 1) {
          proAppointmentUserSelect.value = String(Number(appointmentCheckoutProfessionals[0].id || 0));
        }
      }

      syncSelectedAppointmentDateDisplay();

      const dateMonth = getMonthFromDateValue(proAppointmentDateInput?.value || '');
      if (!appointmentCalendarMonth) {
        appointmentCalendarMonth = dateMonth || getCurrentLocalMonthValue();
      }

      renderAppointmentCalendar();
      renderAppointmentSelectionSummary();
    }

    async function loadAppointmentCheckoutAvailability() {
      appointmentCheckoutEnabled = false;
      appointmentCheckoutServices = [];
      appointmentCheckoutProfessionals = [];

      let response;
      try {
        response = await fetch(tenantAppointmentAvailabilityEndpoint, {
          headers: {
            Accept: 'application/json',
          },
        });
      } catch (error) {
        applyAppointmentCheckoutUiState();
        return;
      }

      if (!response.ok) {
        applyAppointmentCheckoutUiState();
        return;
      }

      let payload = {};
      try {
        payload = await response.json();
      } catch (error) {
        payload = {};
      }

      appointmentCheckoutEnabled = !!payload.enabled;
      appointmentCheckoutServices = Array.isArray(payload.services) ? payload.services : [];
      appointmentCheckoutProfessionals = Array.isArray(payload.professionals) ? payload.professionals : [];

      populateAppointmentSelectors();
      applyAppointmentCheckoutUiState();
      await refreshCatalogAppointmentSection();
      await refreshAppointmentSlots();
    }

    async function refreshAppointmentSlots() {
      if (!isAppointmentCheckoutActive() || !proAppointmentSlotSelect) {
        return;
      }

      const serviceId = Number(proAppointmentServiceSelect?.value || 0);
      const userId = Number(proAppointmentUserSelect?.value || 0);
      const date = String(proAppointmentDateInput?.value || '').trim();
      const hasLockedService = appointmentLockedServiceId > 0;

      if (!appointmentCalendarMonth) {
        appointmentCalendarMonth = getMonthFromDateValue(date) || getCurrentLocalMonthValue();
      }

      if (serviceId <= 0 || userId <= 0) {
        proAppointmentSlotSelect.innerHTML = `<option value="">${hasLockedService ? 'Selecciona profesional y un día del calendario' : 'Selecciona servicio, profesional y un día del calendario'}</option>`;
        if (proAppointmentSlotNote) {
          proAppointmentSlotNote.textContent = hasLockedService
            ? 'Selecciona profesional y un día del calendario para consultar horarios.'
            : 'Selecciona servicio, profesional y un día del calendario para consultar horarios.';
        }
        if (proAppointmentCalendarNote) {
          proAppointmentCalendarNote.textContent = hasLockedService
            ? 'Selecciona profesional para visualizar disponibilidad por día.'
            : 'Selecciona servicio y profesional para visualizar disponibilidad por día.';
        }
        appointmentCalendarDays = [];
        renderAppointmentCalendar();
        renderAppointmentSelectionSummary();
        syncAppointmentPaymentModeUi();
        return;
      }

      proAppointmentSlotSelect.innerHTML = '<option value="">Selecciona un día del calendario</option>';
      if (proAppointmentSlotNote) {
        proAppointmentSlotNote.textContent = date
          ? 'Buscando horarios disponibles...'
          : 'Cargando disponibilidad del calendario...';
      }

      async function fetchAppointmentAvailability(monthValue, dateValue = '') {
        const params = new URLSearchParams({
          service_id: String(serviceId),
          user_id: String(userId),
          month: monthValue,
        });

        if (dateValue) {
          params.set('date', dateValue);
        }

        const response = await fetch(`${tenantAppointmentAvailabilityEndpoint}?${params.toString()}`, {
          headers: {
            Accept: 'application/json',
          },
        });

        if (!response.ok) {
          throw new Error('availability_request_failed');
        }

        return response.json().catch(() => ({}));
      }

      let payload = {};
      try {
        payload = await fetchAppointmentAvailability(appointmentCalendarMonth, date);
      } catch (error) {
        proAppointmentSlotSelect.innerHTML = '<option value="">No se pudo consultar la disponibilidad</option>';
        if (proAppointmentSlotNote) {
          proAppointmentSlotNote.textContent = 'Verifica tu conexión e intenta nuevamente.';
        }
        if (proAppointmentCalendarNote) {
          proAppointmentCalendarNote.textContent = 'No se pudo cargar el calendario en este momento.';
        }
        appointmentCalendarDays = [];
        renderAppointmentCalendar();
        renderAppointmentSelectionSummary();
        syncAppointmentPaymentModeUi();
        return;
      }

      if (!date) {
        let scannedMonths = 0;
        let availableDateInMonth = findFirstAvailableCalendarDate(payload?.calendar || []);

        while (!availableDateInMonth && scannedMonths < 5) {
          const nextMonth = shiftMonthValue(String(payload?.calendar_month || appointmentCalendarMonth), 1);

          try {
            payload = await fetchAppointmentAvailability(nextMonth, '');
          } catch (error) {
            break;
          }

          scannedMonths += 1;
          availableDateInMonth = findFirstAvailableCalendarDate(payload?.calendar || []);
        }
      }

      const slots = date && Array.isArray(payload.slots) ? payload.slots : [];
      appointmentCalendarDays = Array.isArray(payload.calendar) ? payload.calendar : [];
      if (payload.calendar_month && /^\d{4}-\d{2}$/.test(String(payload.calendar_month))) {
        appointmentCalendarMonth = String(payload.calendar_month);
      }

      if (!date) {
        const firstAvailableDate = findFirstAvailableCalendarDate(appointmentCalendarDays);
        if (firstAvailableDate && proAppointmentDateInput && proAppointmentDateInput.value !== firstAvailableDate) {
          proAppointmentDateInput.value = firstAvailableDate;
          syncSelectedAppointmentDateDisplay();
          renderAppointmentCalendar();
          await refreshAppointmentSlots();
          return;
        }

        if (!firstAvailableDate) {
          proAppointmentSlotSelect.innerHTML = '<option value="">Sin horarios disponibles</option>';
          if (proAppointmentCalendarNote) {
            proAppointmentCalendarNote.textContent = 'No encontramos días disponibles para este profesional en los próximos meses.';
          }
          if (proAppointmentSlotNote) {
            proAppointmentSlotNote.textContent = 'Prueba con otro profesional o servicio.';
          }
          renderAppointmentCalendar();
          renderAppointmentSelectionSummary();
          syncAppointmentPaymentModeUi();
          return;
        }
      }

      proAppointmentSlotSelect.innerHTML = date
        ? [
          '<option value="">Selecciona una hora</option>',
          ...slots.map(slot => `<option value="${escapeHtml(slot.start || '')}">${escapeHtml(slot.label || `${slot.start || ''} - ${slot.end || ''}`)}</option>`),
        ].join('')
        : '<option value="">Selecciona un día del calendario</option>';

      if (date && slots.length > 0 && !String(proAppointmentSlotSelect?.value || '').trim()) {
        proAppointmentSlotSelect.value = String(slots[0]?.start || '');
      }

      renderAppointmentCalendar();
      if (proAppointmentCalendarNote) {
        const availableDays = appointmentCalendarDays.filter(row => !!row?.has_slots).length;
        proAppointmentCalendarNote.textContent = availableDays > 0
          ? `${availableDays} día(s) con disponibilidad en ${formatAppointmentCalendarLabel(appointmentCalendarMonth)}.`
          : 'No se detectaron días con disponibilidad en este mes para el profesional seleccionado.';
      }

      syncSelectedAppointmentDateDisplay();

      if (proAppointmentSlotNote) {
        proAppointmentSlotNote.textContent = !date
          ? 'Selecciona un día del calendario para ver horas disponibles.'
          : (slots.length > 0
            ? `${slots.length} horario(s) disponible(s).`
            : 'No hay horarios disponibles para los datos seleccionados.');
      }

      renderAppointmentSelectionSummary();
      syncAppointmentPaymentModeUi();
    }

    function syncAppointmentProfessionalByService() {
      if (!isAppointmentCheckoutActive()) {
        return;
      }

      const selectedOption = proAppointmentServiceSelect?.selectedOptions?.[0] || null;
      const assignedUserId = Number(selectedOption?.dataset?.assignedUserId || 0);

      if (!proAppointmentUserSelect) {
        return;
      }

      if (assignedUserId > 0) {
        proAppointmentUserSelect.value = String(assignedUserId);
        proAppointmentUserSelect.disabled = true;
      } else {
        proAppointmentUserSelect.disabled = false;
        const selectedUserId = Number(proAppointmentUserSelect.value || 0);
        if (selectedUserId <= 0 && appointmentCheckoutProfessionals.length === 1) {
          proAppointmentUserSelect.value = String(Number(appointmentCheckoutProfessionals[0]?.id || 0));
        }
      }

      renderAppointmentSelectionSummary();
    }

    function syncAppointmentPaymentModeUi() {
      if (!isAppointmentCheckoutActive()) {
        if (proAppointmentPaymentModeSelect) {
          proAppointmentPaymentModeSelect.disabled = true;
        }

        if (proPaymentStepNote) {
          proPaymentStepNote.textContent = 'Agrega tu pago con referencia y comprobante.';
        }

        proOnSitePaymentNote?.classList.add('d-none');
        document.getElementById('tenant-pro-payment-rows')?.classList.remove('d-none');
        document.getElementById('tenant-pro-add-payment-row')?.classList.remove('d-none');
        updateProPaymentSummary();
        return;
      }

      const onSite = isAppointmentOnSitePayment();
      const hasSelectedSlot = String(proAppointmentSlotSelect?.value || '').trim() !== '';

      if (proAppointmentPaymentModeSelect) {
        proAppointmentPaymentModeSelect.disabled = !hasSelectedSlot;
      }

      if (proPaymentStepNote) {
        proPaymentStepNote.textContent = !hasSelectedSlot
          ? 'Primero selecciona una hora de la cita para habilitar la forma de pago.'
          : (onSite
            ? 'Confirmarás el pedido sin pago en línea.'
            : 'Agrega tu pago con referencia y comprobante.');
      }

      proOnSitePaymentNote?.classList.toggle('d-none', !hasSelectedSlot || !onSite);
      document.getElementById('tenant-pro-payment-rows')?.classList.toggle('d-none', !hasSelectedSlot || onSite);
      document.getElementById('tenant-pro-add-payment-row')?.classList.toggle('d-none', !hasSelectedSlot || onSite);
      renderAppointmentSelectionSummary();
      updateProPaymentSummary();
    }

    function validateCheckoutStepOne() {
      if (isAppointmentCheckoutActive()) {
        const serviceId = Number(proAppointmentServiceSelect?.value || 0);
        const userId = Number(proAppointmentUserSelect?.value || 0);
        const date = String(proAppointmentDateInput?.value || '').trim();
        const startTime = String(proAppointmentSlotSelect?.value || '').trim();

        if (serviceId <= 0) {
          alert('Debes seleccionar un servicio para la cita.');
          return false;
        }

        if (userId <= 0) {
          alert('Debes seleccionar un profesional para la cita.');
          return false;
        }

        if (!date) {
          alert('Debes seleccionar una fecha para la cita.');
          return false;
        }

        if (!startTime) {
          alert('Debes seleccionar una hora disponible para la cita.');
          return false;
        }

        return true;
      }

      const deliveryType = document.querySelector('input[name="tenant-pro-delivery-type"]:checked')?.value || 'pickup';
      if (!['delivery', 'shipping'].includes(deliveryType)) {
        return true;
      }

      const deliveryAddressResult = buildAddressForDeliveryType(deliveryType, {
        countrySelect: proShippingCountrySelect,
        stateSelect: proShippingStateSelect,
        citySelect: proShippingCitySelect,
        detailInput: proShippingAddressDetailInput,
        latitudeInput: proShippingLatitudeInput,
        longitudeInput: proShippingLongitudeInput,
        receiverNameInput: proDeliveryReceiverNameInput,
        receiverPhoneInput: proDeliveryReceiverPhoneInput,
        receiverPhoneCodeInput: proDeliveryReceiverPhoneCodeInput,
        extraInfoInput: proDeliveryExtraInfoInput,
      });

      if (!deliveryAddressResult.valid) {
        alert(deliveryAddressResult.message);
        return false;
      }

      const deliveryContext = getTenantDeliveryContext(deliveryType, proShippingDistanceInput, true);
      if (!deliveryContext.valid) {
        alert(deliveryContext.message || 'Debes completar la información del delivery.');
        return false;
      }

      return true;
    }

    function showCheckoutSuccess(orderId = null, options = {}) {
      const successLink = document.getElementById('tenant-pro-success-link');
      if (successLink && orderId) {
        successLink.href = `/publicOrder/${orderId}`;
        successLink.classList.remove('d-none');
      }

      if (proSuccessMessage) {
        proSuccessMessage.textContent = options.message || 'Recibirás una notificación con el seguimiento de tu compra o cita.';
      }

      startAppointmentStatusPolling(options.appointment || null);

      if (options.promptNotifications === true) {
        window.dispatchEvent(new CustomEvent('shopix:notifications-optin-requested', {
          detail: {
            source: 'checkout-success',
            standaloneOnly: true,
          },
        }));
      }

      setCheckoutStep(3);
    }

    function getBaseCurrencySymbol() {
      return String(proBaseCurrency).toUpperCase() === 'EUR' ? '€' : '$';
    }

    function getAuthToken() {
      return localStorage.getItem(authTokenKey) || '';
    }

    function getAuthUser() {
      try {
        return JSON.parse(localStorage.getItem(authUserKey) || 'null');
      } catch (error) {
        return null;
      }
    }

    function getDefaultDeliveryMapPosition() {
      if (Number.isFinite(Number(tenantLatitude)) && Number.isFinite(Number(tenantLongitude))) {
        return { lat: Number(tenantLatitude), lng: Number(tenantLongitude) };
      }

      return { lat: 9.7457, lng: -63.1832 };
    }

    function getContextCoordinates(context) {
      const latitude = context?.latitudeInput?.value ? Number(context.latitudeInput.value) : null;
      const longitude = context?.longitudeInput?.value ? Number(context.longitudeInput.value) : null;

      if (Number.isFinite(latitude) && Number.isFinite(longitude)) {
        return { lat: latitude, lng: longitude };
      }

      return getDefaultDeliveryMapPosition();
    }

    function updateDeliveryMapStatus(position) {
      if (!deliveryMapStatus || !position) {
        return;
      }

      deliveryMapStatus.textContent = `Ubicación seleccionada: ${position.lat.toFixed(6)}, ${position.lng.toFixed(6)}`;
    }

    function setPendingDeliveryMapPosition(position, updateMap = true) {
      if (!position) {
        return;
      }

      pendingDeliveryMapPosition = {
        lat: Number(position.lat),
        lng: Number(position.lng),
      };

      updateDeliveryMapStatus(pendingDeliveryMapPosition);

      if (updateMap && deliveryMap && deliveryMapMarker) {
        deliveryMapMarker.setPosition(pendingDeliveryMapPosition);
        deliveryMap.panTo(pendingDeliveryMapPosition);
      }
    }

    function initializeDeliveryMap() {
      if (!window.google?.maps || deliveryMap) {
        return;
      }

      const initialPosition = getDefaultDeliveryMapPosition();
      deliveryMap = new google.maps.Map(document.getElementById('tenant-delivery-map-canvas'), {
        center: initialPosition,
        zoom: 14,
      });

      deliveryMapMarker = new google.maps.Marker({
        position: initialPosition,
        map: deliveryMap,
        draggable: true,
      });

      setPendingDeliveryMapPosition(initialPosition, false);

      deliveryMap.addListener('click', (event) => {
        setPendingDeliveryMapPosition({
          lat: event.latLng.lat(),
          lng: event.latLng.lng(),
        });
      });

      deliveryMapMarker.addListener('dragend', (event) => {
        setPendingDeliveryMapPosition({
          lat: event.latLng.lat(),
          lng: event.latLng.lng(),
        }, false);
      });

      if (deliveryMapSearchInput) {
        deliveryMapAutocomplete = new google.maps.places.Autocomplete(deliveryMapSearchInput);
        deliveryMapAutocomplete.bindTo('bounds', deliveryMap);
        deliveryMapAutocomplete.addListener('place_changed', () => {
          const place = deliveryMapAutocomplete.getPlace();
          if (!place.geometry?.location) {
            return;
          }

          const position = {
            lat: place.geometry.location.lat(),
            lng: place.geometry.location.lng(),
          };

          deliveryMap.setCenter(position);
          deliveryMap.setZoom(16);
          setPendingDeliveryMapPosition(position);
        });
      }
    }

    function loadDeliveryMapScript() {
      if (googleMapsScriptLoaded) {
        initializeDeliveryMap();
        return;
      }

      if (googleMapsScriptLoading) {
        return;
      }

      if (!googleMapsApiKey) {
        alert('Falta configurar GOOGLE_MAPS_API_KEY para usar el mapa.');
        return;
      }

      googleMapsScriptLoading = true;
      window.initShopixDeliveryMap = function() {
        googleMapsScriptLoaded = true;
        googleMapsScriptLoading = false;
        initializeDeliveryMap();
      };

      const script = document.createElement('script');
      script.src = `https://maps.googleapis.com/maps/api/js?key=${googleMapsApiKey}&libraries=places&callback=initShopixDeliveryMap`;
      script.async = true;
      script.defer = true;
      script.onerror = function() {
        googleMapsScriptLoading = false;
        alert('No se pudo cargar Google Maps para seleccionar la ubicación.');
      };
      document.head.appendChild(script);
    }

    function openDeliveryMapPicker(context) {
      if (!deliveryMapModalElement || typeof bootstrap === 'undefined' || !bootstrap?.Modal) {
        alert('No se pudo abrir el selector de mapa.');
        return;
      }

      activeDeliveryMapContext = context;
      pendingDeliveryMapPosition = getContextCoordinates(context);

      const modal = bootstrap.Modal.getOrCreateInstance(deliveryMapModalElement);
      modal.show();
      loadDeliveryMapScript();
    }

    function setAuthData(token, user) {
      localStorage.setItem(authTokenKey, token || '');
      localStorage.setItem(authUserKey, JSON.stringify(user || null));

      window.dispatchEvent(new CustomEvent('shopix-auth-changed', {
        detail: {
          token: token || '',
          user: user || null,
        },
      }));
    }

    function showTenantAuthAlert(message) {
      if (!tenantAuthAlert) return;
      tenantAuthAlert.textContent = message || '';
      tenantAuthAlert.classList.toggle('d-none', !message);
    }

    function clearTenantAuthAlert() {
      showTenantAuthAlert('');
    }

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

    function showTenantToast(title, message = '') {
      if (typeof bootstrap === 'undefined' || !bootstrap?.Toast) {
        return;
      }

      let toastContainer = document.getElementById('tenant-shopix-toast-container');
      if (!toastContainer) {
        toastContainer = document.createElement('div');
        toastContainer.id = 'tenant-shopix-toast-container';
        toastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
        toastContainer.style.zIndex = '3000';
        document.body.appendChild(toastContainer);
      }

      const toastElement = document.createElement('div');
      toastElement.className = 'toast align-items-center border-0';
      toastElement.setAttribute('role', 'status');
      toastElement.setAttribute('aria-live', 'polite');
      toastElement.setAttribute('aria-atomic', 'true');
      toastElement.innerHTML = `
        <div class="toast-header">
          <strong class="me-auto">${escapeHtml(title || 'Shopix')}</strong>
          <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
        <div class="toast-body">${escapeHtml(message || '')}</div>
      `;

      toastContainer.appendChild(toastElement);
      const toastInstance = bootstrap.Toast.getOrCreateInstance(toastElement, { delay: 2600 });
      toastElement.addEventListener('hidden.bs.toast', () => toastElement.remove(), { once: true });
      toastInstance.show();
    }

    function setCheckoutResumeState(shouldResumeCheckout) {
      sessionStorage.setItem(authResumeKey, JSON.stringify({
        checkout: !!shouldResumeCheckout,
      }));
    }

    function consumeCheckoutResumeState() {
      const raw = sessionStorage.getItem(authResumeKey);
      if (!raw) {
        return null;
      }

      sessionStorage.removeItem(authResumeKey);

      try {
        return JSON.parse(raw);
      } catch (error) {
        return null;
      }
    }

    function escapeHtml(value) {
      return String(value || '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
    }

    function createPaymentRowHtml(methods, rowId) {
      const options = methods.map(method => `
        <option value="${method.id}" data-currency-code="${method.currency?.code || ''}" data-currency-name="${method.currency?.name || ''}" data-has-reference="${method.has_reference ? '1' : '0'}">${method.name} (${method.currency?.code || method.currency?.name || proBaseCurrency})</option>
      `).join('');

      const firstMethod = Array.isArray(methods) && methods.length > 0 ? methods[0] : null;
      const requiresReference = !!firstMethod?.has_reference;
      const referenceColumnClass = requiresReference ? 'col-10 col-md-3' : 'd-none';
      const proofColumnClass = requiresReference ? 'col-12 col-md-6' : 'd-none';
      const proofNameColumnClass = requiresReference ? 'col-12 col-md-6' : 'd-none';

      return `
        <div class="border rounded p-2 tenant-pro-payment-row" data-pro-payment-row="${rowId}">
          <div class="row g-2">
            <div class="col-12 col-md-4">
              <label class="form-label small mb-1">Método</label>
              <select class="form-select pro-payment-method">${options}</select>
            </div>
            <div class="col-12 col-md-4">
              <label class="form-label small mb-1">Monto</label>
              <input type="text" inputmode="decimal" autocomplete="off" class="form-control pro-payment-amount" placeholder="0.00">
              <div class="d-flex justify-content-end mt-1">
                <button type="button" class="btn btn-outline-primary btn-sm py-0 px-2 pro-fill-remaining-amount">Llenar restante</button>
              </div>
            </div>
            <div class="${referenceColumnClass} pro-payment-reference-group">
              <label class="form-label small mb-1">Referencia *</label>
              <input type="text" class="form-control pro-payment-reference" placeholder="Obligatoria" ${requiresReference ? 'required' : ''}>
            </div>
            <div class="${proofColumnClass} pro-payment-proof-group">
              <label class="form-label small mb-1">Imagen de comprobante *</label>
              <input type="file" class="form-control pro-payment-reference-image" accept="image/png,image/jpeg,image/jpg,image/webp" ${requiresReference ? 'required' : ''}>
            </div>
            <div class="col-2 col-md-1 d-flex align-items-end">
              <button type="button" class="btn btn-outline-danger btn-sm w-100 pro-remove-payment-row" aria-label="Eliminar pago"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="col-12">
              <div class="small border rounded p-2 bg-light pro-payment-method-details"></div>
            </div>
            <div class="${proofNameColumnClass} pro-payment-proof-name-group">
              <div class="small text-muted pro-payment-reference-image-name pt-md-4">Sin imagen cargada</div>
            </div>
          </div>
        </div>
      `;
    }

    function getMethodById(methodId) {
      return proPaymentMethods.find(method => Number(method.id) === Number(methodId)) || null;
    }

    function isBsCurrency(method) {
      if (!method) return false;
      const code = String(method.currency?.code || '').toUpperCase();
      const name = String(method.currency?.name || '').toUpperCase();
      return code === 'BS' || code === 'VES' || name.includes('BOL') || name === 'BS' || name === 'VES';
    }

    function normalizePaymentCurrencyCode(currencyCode) {
      const normalized = String(currencyCode || '').toUpperCase().trim();
      if (['BS', 'VES', 'VED', 'VEF', 'BOLIVAR', 'BOLIVARES'].includes(normalized)) {
        return 'BS';
      }
      return normalized;
    }

    function roundProMoney(value) {
      const numeric = Number(value || 0);

      if (!Number.isFinite(numeric)) {
        return 0;
      }

      return Math.round((numeric + Number.EPSILON) * 100) / 100;
    }

    function parseProPaymentAmountValue(value) {
      const normalized = normalizeEditableProPaymentValue(value).numeric;
      const parsed = Number.parseFloat(normalized);
      return Number.isFinite(parsed) ? parsed : 0;
    }

    function normalizeEditableProPaymentValue(value) {
      const source = String(value || '')
        .replace(/\s+/g, '')
        .replace(/[^\d.,]/g, '');

      if (!source) {
        return { text: '', numeric: '' };
      }

      const lastDot = source.lastIndexOf('.');
      const lastComma = source.lastIndexOf(',');
      let decimalIndex = -1;
      let decimalSeparator = '';

      if (lastDot !== -1 && lastComma !== -1) {
        decimalIndex = Math.max(lastDot, lastComma);
        decimalSeparator = source[decimalIndex];
      } else if (lastComma !== -1) {
        const fraction = source.slice(lastComma + 1).replace(/[^\d]/g, '');
        if (fraction.length <= 2 || source.endsWith(',')) {
          decimalIndex = lastComma;
          decimalSeparator = ',';
        }
      } else if (lastDot !== -1) {
        const fraction = source.slice(lastDot + 1).replace(/[^\d]/g, '');
        if (fraction.length <= 2 || source.endsWith('.')) {
          decimalIndex = lastDot;
          decimalSeparator = '.';
        }
      }

      let integerPart = '';
      let decimalPart = '';
      let hasTrailingDecimal = false;

      if (decimalIndex !== -1) {
        integerPart = source.slice(0, decimalIndex).replace(/[^\d]/g, '');
        decimalPart = source.slice(decimalIndex + 1).replace(/[^\d]/g, '').slice(0, 2);
        hasTrailingDecimal = source.endsWith(decimalSeparator) && decimalPart.length === 0;
      } else {
        integerPart = source.replace(/[^\d]/g, '');
      }

      integerPart = integerPart.replace(/^0+(?=\d)/, '');

      if (!integerPart && (decimalPart || hasTrailingDecimal)) {
        integerPart = '0';
      }

      const text = decimalIndex !== -1
        ? `${integerPart || '0'}${(decimalPart || hasTrailingDecimal) ? '.' : ''}${decimalPart}`
        : integerPart;

      const numeric = decimalIndex !== -1
        ? `${integerPart || '0'}${decimalPart ? `.${decimalPart}` : ''}`
        : integerPart;

      return { text, numeric };
    }

    function formatProPaymentAmountValue(value) {
      const numeric = Number(value || 0);
      return new Intl.NumberFormat('en-US', {
        useGrouping: false,
        minimumFractionDigits: 2,
        maximumFractionDigits: 2,
      }).format(Number.isFinite(numeric) ? numeric : 0);
    }

    function syncProPaymentAmountInput(input, applyFormatting = false) {
      if (!input || !input.classList.contains('pro-payment-amount')) {
        return 0;
      }

      const numericValue = parseProPaymentAmountValue(input.value);
      input.dataset.rawValue = String(numericValue);

      if (applyFormatting) {
        input.value = numericValue > 0 ? formatProPaymentAmountValue(numericValue) : '';
      }

      return numericValue;
    }

    function sanitizeLiveMoneyInput(input, parser, normalizer) {
      if (!input) {
        return 0;
      }

      const selectionStart = input.selectionStart ?? String(input.value || '').length;
      const beforeCursor = String(input.value || '').slice(0, selectionStart);
      const normalizedValue = normalizer(input.value);
      const normalizedBeforeCursor = normalizer(beforeCursor);

      if (!normalizedValue.text) {
        input.dataset.rawValue = '0';
        input.value = '';
        return 0;
      }

      const numericValue = parser(normalizedValue.text);
      input.dataset.rawValue = String(numericValue);

      if (input.value !== normalizedValue.text) {
        input.value = normalizedValue.text;
        const nextCaret = normalizedBeforeCursor.text.length;
        requestAnimationFrame(() => {
          try {
            input.setSelectionRange(nextCaret, nextCaret);
          } catch (error) {
          }
        });
      }

      return numericValue;
    }

    function toBaseFromMethodAmount(method, amount) {
      if (!amount || amount <= 0) return 0;

      const methodCurrencyCode = normalizePaymentCurrencyCode(method?.currency?.code || method?.currency?.name || '');
      const normalizedBaseCurrency = normalizePaymentCurrencyCode(proBaseCurrency || 'USD');

      if (methodCurrencyCode === normalizedBaseCurrency) {
        return roundProMoney(amount);
      }

      if (isBsCurrency(method)) {
        if (!proBaseRate || proBaseRate <= 0) return 0;
        return roundProMoney(amount / proBaseRate);
      }

      if (methodCurrencyCode === 'USD' && normalizedBaseCurrency === 'EUR') {
        if (proDollarRate <= 0 || proEuroRate <= 0) return 0;
        return roundProMoney((amount * proDollarRate) / proEuroRate);
      }

      if (methodCurrencyCode === 'EUR' && normalizedBaseCurrency === 'USD') {
        if (proEuroRate <= 0 || proDollarRate <= 0) return 0;
        return roundProMoney((amount * proEuroRate) / proDollarRate);
      }

      return roundProMoney(amount);
    }

    function fromBaseToMethodAmount(method, amountBase) {
      if (!amountBase || amountBase <= 0) return 0;

      const methodCurrencyCode = normalizePaymentCurrencyCode(method?.currency?.code || method?.currency?.name || '');
      const normalizedBaseCurrency = normalizePaymentCurrencyCode(proBaseCurrency || 'USD');

      if (methodCurrencyCode === normalizedBaseCurrency) {
        return roundProMoney(amountBase);
      }

      if (isBsCurrency(method)) {
        if (!proBaseRate || proBaseRate <= 0) return 0;
        return roundProMoney(amountBase * proBaseRate);
      }

      if (methodCurrencyCode === 'USD' && normalizedBaseCurrency === 'EUR') {
        if (proDollarRate <= 0 || proEuroRate <= 0) return 0;
        return roundProMoney((amountBase * proEuroRate) / proDollarRate);
      }

      if (methodCurrencyCode === 'EUR' && normalizedBaseCurrency === 'USD') {
        if (proEuroRate <= 0 || proDollarRate <= 0) return 0;
        return roundProMoney((amountBase * proDollarRate) / proEuroRate);
      }

      return roundProMoney(amountBase);
    }

    function renderMethodDetailsHtml(method) {
      if (!method) {
        return 'Selecciona un método de pago para ver sus datos.';
      }

      const details = [
        method.admin_name ? { label: 'Beneficiario', value: String(method.admin_name) } : null,
        method.bank ? { label: 'Banco', value: String(method.bank) } : null,
        method.dni ? { label: 'DNI', value: String(method.dni) } : null,
        method.description ? { label: 'Descripción', value: String(method.description) } : null,
      ].filter(Boolean);

      const copyAllText = details
        .map(detail => `${detail.label}: ${detail.value}`)
        .join('\n');

      const qr = method.qr_image_url
        ? `<div class="mt-2"><img src="${method.qr_image_url}" alt="QR ${escapeHtml(method.name || 'método')}" style="max-width:120px; max-height:120px; object-fit:contain; border:1px solid #ddd; border-radius:8px;"></div>`
        : '';

      const detailsHtml = details.map(detail => {
        const label = escapeHtml(detail.label || 'Dato');
        const value = String(detail.value || '');
        const safeValue = escapeHtml(value);
        const encodedValue = encodeURIComponent(value);

        return `
          <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
            <div><strong>${label}:</strong> ${safeValue}</div>
            <button type="button" class="btn btn-outline-secondary btn-sm py-0 px-2 pro-copy-field" data-copy-value="${encodedValue}">Copiar</button>
          </div>
        `;
      }).join('');

      const copyAllButton = copyAllText
        ? `<div class="d-flex justify-content-end mb-2"><button type="button" class="btn btn-dark btn-sm pro-copy-all" data-copy-all-value="${encodeURIComponent(copyAllText)}">Copiar todo</button></div>`
        : '';

      return `${copyAllButton}${detailsHtml}${qr}`;
    }

    async function copyToClipboard(value) {
      try {
        if (navigator.clipboard && typeof navigator.clipboard.writeText === 'function') {
          await navigator.clipboard.writeText(value);
          return true;
        }
      } catch (error) {
      }

      const tempInput = document.createElement('textarea');
      tempInput.value = value;
      tempInput.setAttribute('readonly', 'readonly');
      tempInput.style.position = 'fixed';
      tempInput.style.left = '-9999px';
      document.body.appendChild(tempInput);
      tempInput.select();
      const copied = document.execCommand('copy');
      document.body.removeChild(tempInput);
      return copied;
    }

    function setProSubmitLoading(isLoading) {
      const submitButton = document.getElementById('tenant-pro-submit-order');
      if (!submitButton) {
        return;
      }

      if (!submitButton.dataset.defaultLabel) {
        submitButton.dataset.defaultLabel = submitButton.innerHTML;
      }

      if (isLoading) {
        submitButton.disabled = true;
        submitButton.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Confirmando...';
      } else {
        submitButton.innerHTML = submitButton.dataset.defaultLabel;
        const cart = getCart();
        submitButton.disabled = !isAppointmentCheckoutActive() && cart.length === 0;
      }
    }

    function populatePaymentRowDetails(row) {
      const methodId = Number(row.querySelector('.pro-payment-method')?.value || 0);
      const method = getMethodById(methodId);
      const detailsBox = row.querySelector('.pro-payment-method-details');
      if (detailsBox) {
        detailsBox.innerHTML = renderMethodDetailsHtml(method);
      }

      const requiresReference = !!method?.has_reference;
      const referenceGroup = row.querySelector('.pro-payment-reference-group');
      const proofGroup = row.querySelector('.pro-payment-proof-group');
      const proofNameGroup = row.querySelector('.pro-payment-proof-name-group');
      const referenceInput = row.querySelector('.pro-payment-reference');
      const proofInput = row.querySelector('.pro-payment-reference-image');

      referenceGroup?.classList.toggle('d-none', !requiresReference);
      proofGroup?.classList.toggle('d-none', !requiresReference);
      proofNameGroup?.classList.toggle('d-none', !requiresReference);

      if (referenceInput) {
        referenceInput.required = requiresReference;
        if (!requiresReference) {
          referenceInput.value = '';
        }
      }

      if (proofInput) {
        proofInput.required = requiresReference;
        if (!requiresReference) {
          proofInput.value = '';
        }
      }
    }

    const PRO_PAYMENT_SAFE_IMAGE_BYTES = 1.2 * 1024 * 1024;
    const PRO_PAYMENT_SAFE_TOTAL_BYTES = 6 * 1024 * 1024;

    function fileToDataUrl(file) {
      return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () => resolve(String(reader.result || ''));
        reader.onerror = () => reject(new Error('No se pudo leer la imagen de referencia.'));
        reader.readAsDataURL(file);
      });
    }

    function formatBytesToMb(bytes) {
      return `${(Number(bytes || 0) / (1024 * 1024)).toFixed(1)} MB`;
    }

    function calculateProIgtfTotals(paymentRows, totalBaseWithoutIgtf) {
      const normalizedBaseCurrency = normalizePaymentCurrencyCode(proBaseCurrency || 'USD');
      const directBasePaymentsTotal = roundProMoney(paymentRows.reduce((sum, row) => {
        const methodId = Number(row.querySelector('.pro-payment-method')?.value || 0);
        const method = getMethodById(methodId);
        const amountRaw = parseProPaymentAmountValue(row.querySelector('.pro-payment-amount')?.value || 0);
        const methodCurrencyCode = normalizePaymentCurrencyCode(method?.currency?.code || method?.currency?.name || '');

        if (amountRaw <= 0 || methodCurrencyCode !== normalizedBaseCurrency) {
          return sum;
        }

        return sum + amountRaw;
      }, 0));

      const shouldApplyIgtf = proElectronicInvoicingEnabled && !proSpecialTaxpayer && Number(proIgtfRate || 0) > 0;
      const igtfAmount = shouldApplyIgtf ? roundProMoney(directBasePaymentsTotal * (Number(proIgtfRate || 0) / 100)) : 0;

      return {
        shouldApplyIgtf,
        directBasePaymentsTotal,
        igtfAmount,
        totalWithIgtf: roundProMoney(totalBaseWithoutIgtf + igtfAmount),
      };
    }

    function updateProPaymentSummary() {
      const baseSymbol = getBaseCurrencySymbol();
      const proDeliveryType = document.querySelector('input[name="tenant-pro-delivery-type"]:checked')?.value || 'pickup';
      const proDeliveryContext = getTenantDeliveryContext(proDeliveryType, proShippingDistanceInput, false);
      const totalBaseWithoutIgtf = getCheckoutProItemsSubtotalBase(getCart()) + Number(proDeliveryContext.fee || 0);

      const paymentRows = Array.from(document.querySelectorAll('[data-pro-payment-row]'));
      const paidBase = roundProMoney(paymentRows.reduce((sum, row) => {
        const methodId = Number(row.querySelector('.pro-payment-method')?.value || 0);
        const amount = parseProPaymentAmountValue(row.querySelector('.pro-payment-amount')?.value || 0);
        const method = getMethodById(methodId);
        return sum + toBaseFromMethodAmount(method, amount);
      }, 0));

      const igtfTotals = calculateProIgtfTotals(paymentRows, totalBaseWithoutIgtf);
      const totalBase = roundProMoney(igtfTotals.totalWithIgtf);
      const totalBs = roundProMoney(totalBase * proBaseRate);

      const remainingBase = roundProMoney(Math.max(totalBase - paidBase, 0));
      const paidBs = roundProMoney(paidBase * proBaseRate);
      const remainingBs = roundProMoney(remainingBase * proBaseRate);

      document.getElementById('tenant-pro-total-amount').textContent = `${totalBase.toFixed(2)} ${baseSymbol}`;
      document.getElementById('tenant-pro-total-amount-bs').textContent = `${totalBs.toFixed(2)} Bs`;
      document.getElementById('tenant-pro-paid-amount').textContent = `${paidBase.toFixed(2)} ${baseSymbol}`;
      document.getElementById('tenant-pro-paid-amount-bs').textContent = `${paidBs.toFixed(2)} Bs`;
      document.getElementById('tenant-pro-remaining-amount').textContent = `${remainingBase.toFixed(2)} ${baseSymbol}`;
      document.getElementById('tenant-pro-remaining-amount-bs').textContent = `${remainingBs.toFixed(2)} Bs`;
      document.getElementById('tenant-pro-dollar-rate').textContent = `${(proBaseRate || 0).toFixed(2)}`;
      document.getElementById('tenant-pro-base-currency').textContent = String(proBaseCurrency || 'USD').toUpperCase();
      if (proDeliveryFeeSummary) {
        proDeliveryFeeSummary.textContent = `${Number(proDeliveryContext.fee || 0).toFixed(2)} ${baseSymbol}`;
      }

      const igtfRow = document.getElementById('tenant-pro-igtf-row');
      const igtfBasePaymentsRow = document.getElementById('tenant-pro-igtf-base-payments-row');
      const igtfBaseCode = document.getElementById('tenant-pro-igtf-base-code');
      const igtfBasePayments = document.getElementById('tenant-pro-igtf-base-payments');
      const igtfAmount = document.getElementById('tenant-pro-igtf-amount');

      if (igtfBaseCode) {
        igtfBaseCode.textContent = String(proBaseCurrency || 'USD').toUpperCase();
      }

      if (igtfBasePayments) {
        igtfBasePayments.textContent = `${igtfTotals.directBasePaymentsTotal.toFixed(2)} ${baseSymbol}`;
      }

      if (igtfAmount) {
        igtfAmount.textContent = `${igtfTotals.igtfAmount.toFixed(2)} ${baseSymbol}`;
      }

      if (igtfRow && igtfBasePaymentsRow) {
        if (igtfTotals.shouldApplyIgtf && igtfTotals.directBasePaymentsTotal > 0) {
          igtfRow.classList.remove('d-none');
          igtfBasePaymentsRow.classList.remove('d-none');
        } else {
          igtfRow.classList.add('d-none');
          igtfBasePaymentsRow.classList.add('d-none');
        }
      }

      refreshDeliveryUiInfo();
    }

    function syncTenantProStatusAll() {
      const checks = Array.from(document.querySelectorAll('.tenant-pro-status-check'));
      const selectAll = document.getElementById('tenant-pro-status-all');
      if (!selectAll || checks.length === 0) {
        return;
      }

      selectAll.checked = checks.every(check => check.checked);
    }

    async function openProCheckout(options = {}) {
      clearTenantAuthAlert();
      const cart = getCart();
      const authOnly = !!options.authOnly;
      const forceAppointmentCheckout = !!options.forceAppointment || hasPendingCatalogAppointmentCheckout();
      if (!authOnly && cart.length === 0 && !forceAppointmentCheckout) {
        alert('Tu carrito está vacío.');
        return;
      }

      const modalElement = document.getElementById('tenantProCheckoutModal');
      if (!modalElement) {
        alert('No se pudo abrir el checkout.');
        return;
      }

      const authSection = document.getElementById('tenant-pro-auth-section');
      const checkoutSection = document.getElementById('tenant-pro-checkout-section');
      const submitOrderButton = document.getElementById('tenant-pro-submit-order');
      const totalAmountElement = document.getElementById('tenant-pro-total-amount');
      const paymentRowsContainer = document.getElementById('tenant-pro-payment-rows');
      const addPaymentRowButton = document.getElementById('tenant-pro-add-payment-row');
      const modalTitle = document.getElementById('tenantProCheckoutModalLabel');
      const modalFooter = document.getElementById('tenantProCheckoutModalFooter');

      const token = getAuthToken();
      const user = getAuthUser();
      const isLogged = !!token && !!user?.id;

      const showAuthOnlyState = (title) => {
        if (modalTitle) {
          modalTitle.textContent = title;
        }

        authSection.classList.remove('d-none');
        checkoutSection.classList.add('d-none');
        modalFooter?.classList.add('d-none');
        submitOrderButton.disabled = true;
        paymentRowsContainer.innerHTML = '';
        const deliveryContext = getTenantDeliveryContext('pickup', proShippingDistanceInput, false);
        totalAmountElement.textContent = `${(getCheckoutProItemsSubtotalBase(cart) + Number(deliveryContext.fee || 0)).toFixed(2)} ${getBaseCurrencySymbol()}`;

        const loginTab = document.getElementById('tenant-login-tab');
        if (loginTab) {
          bootstrap.Tab.getOrCreateInstance(loginTab).show();
        }
      };

      const showCheckoutState = () => {
        authSection.classList.add('d-none');
        checkoutSection.classList.remove('d-none');
        modalFooter?.classList.remove('d-none');
        submitOrderButton.disabled = !isAppointmentCheckoutActive() && cart.length === 0;
        resetCheckoutSuccessState();
        setCheckoutStep(1);
      };

      const showLoggedInIdleState = () => {
        if (modalTitle) {
          modalTitle.textContent = 'Cuenta lista';
        }

        authSection.classList.add('d-none');
        checkoutSection.classList.add('d-none');
        modalFooter?.classList.add('d-none');
        submitOrderButton.disabled = true;
        paymentRowsContainer.innerHTML = '<p class="tenant-cart-empty">Tu sesión ya está activa. Agrega productos al carrito para continuar con el checkout.</p>';
      };

      if (!isLogged) {
        showAuthOnlyState(cart.length > 0 && !authOnly ? 'Inicia sesión para continuar' : 'Iniciar sesión');
        const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
        modal.show();
        return;
      }

      if (authOnly && cart.length === 0 && !forceAppointmentCheckout) {
        showLoggedInIdleState();

        const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
        modal.show();
        return;
      }

      let methodsResponse;
      try {
        methodsResponse = await fetch(`/${tenantSlug}/payment-methods`, {
          headers: { 'Accept': 'application/json' }
        });
      } catch (error) {
        alert('No se pudieron obtener métodos de pago.');
        return;
      }

      if (!methodsResponse.ok) {
        alert('No se pudieron obtener métodos de pago.');
        return;
      }

      const methodsData = await methodsResponse.json();
      const methods = Array.isArray(methodsData.methods) ? methodsData.methods : [];
      proPaymentMethods = methods;
      proDollarRate = Number(methodsData.dollar_rate || 0);
      proEuroRate = Number(methodsData.euro_rate || 0);
      proBaseCurrency = String(methodsData.base_currency || 'USD').toUpperCase();
      proBaseRate = Number(methodsData.base_rate || 0);
      proIgtfRate = Number(methodsData.igtf_rate || 0);
      proElectronicInvoicingEnabled = !!methodsData.electronic_invoicing_enabled;
      proSpecialTaxpayer = !!methodsData.special_taxpayer;

      await loadAppointmentCheckoutAvailability();
      if (isAppointmentCheckoutActive()) {
        await applyCatalogAppointmentSelectionToCheckout();
      }

      if (methods.length === 0 && !isAppointmentCheckoutActive()) {
        alert('Esta tienda no tiene métodos de pago activos para checkout.');
        return;
      }

      if ((!proBaseRate || proBaseRate <= 0) && !isAppointmentCheckoutActive()) {
        alert(`La tienda no tiene tasa configurada para ${proBaseCurrency}. Contacta al comercio.`);
        return;
      }

      if (isAppointmentCheckoutActive() && proAppointmentPaymentModeSelect && methods.length === 0) {
        proAppointmentPaymentModeSelect.value = 'on_site';
        const onlineOption = proAppointmentPaymentModeSelect.querySelector('option[value="online"]');
        if (onlineOption) {
          onlineOption.disabled = true;
        }
      }

      applyUserLocationToShippingForm(user, proShippingCountrySelect, proShippingStateSelect, proShippingCitySelect, proShippingAddressDetailInput, proShippingLatitudeInput, proShippingLongitudeInput, proShippingLocationStatus, proShippingDistanceInput);

      paymentRowsContainer.innerHTML = '';
      let rowCounter = 0;
      const addPaymentRow = () => {
        if (!Array.isArray(methods) || methods.length === 0) {
          return;
        }

        rowCounter += 1;
        paymentRowsContainer.insertAdjacentHTML('beforeend', createPaymentRowHtml(methods, `row_${rowCounter}`));
        const row = paymentRowsContainer.lastElementChild;
        if (row) {
          populatePaymentRowDetails(row);
        }
        updateProPaymentSummary();
      };

      if (methods.length > 0) {
        addPaymentRow();
        addPaymentRowButton.onclick = addPaymentRow;
      } else {
        addPaymentRowButton.onclick = null;
      }

      paymentRowsContainer.onclick = (event) => {
        const fillRemainingButton = event.target.closest('.pro-fill-remaining-amount');
        if (fillRemainingButton) {
          const row = event.target.closest('[data-pro-payment-row]');
          if (!row) {
            return;
          }

          const amountInput = row.querySelector('.pro-payment-amount');
          const methodId = Number(row.querySelector('.pro-payment-method')?.value || 0);
          const method = getMethodById(methodId);

          if (!amountInput || !method) {
            showTenantToast('Pago', 'Selecciona un método de pago válido para completar el monto.');
            return;
          }

          const paymentRows = Array.from(document.querySelectorAll('[data-pro-payment-row]'));
          const proDeliveryType = document.querySelector('input[name="tenant-pro-delivery-type"]:checked')?.value || 'pickup';
          const proDeliveryContext = getTenantDeliveryContext(proDeliveryType, proShippingDistanceInput, false);
          const totalBaseWithoutIgtf = getCheckoutProItemsSubtotalBase(getCart()) + Number(proDeliveryContext.fee || 0);
          const igtfTotals = calculateProIgtfTotals(paymentRows, totalBaseWithoutIgtf);
          const totalOrderBase = roundProMoney(igtfTotals.totalWithIgtf);

          const paidBaseWithoutCurrent = roundProMoney(paymentRows.reduce((sum, currentRow) => {
            if (currentRow === row) {
              return sum;
            }

            const currentMethodId = Number(currentRow.querySelector('.pro-payment-method')?.value || 0);
            const currentMethod = getMethodById(currentMethodId);
            const currentAmountRaw = parseProPaymentAmountValue(currentRow.querySelector('.pro-payment-amount')?.value || 0);

            return sum + toBaseFromMethodAmount(currentMethod, currentAmountRaw);
          }, 0));

          const remainingBase = roundProMoney(Math.max(totalOrderBase - paidBaseWithoutCurrent, 0));
          if (remainingBase <= 0) {
            amountInput.value = '';
            syncProPaymentAmountInput(amountInput, false);
            updateProPaymentSummary();
            return;
          }

          const amountForMethod = fromBaseToMethodAmount(method, remainingBase);
          if (amountForMethod <= 0) {
            showTenantToast('Pago', 'No se pudo calcular el restante para este método con las tasas actuales.');
            return;
          }

          amountInput.value = formatProPaymentAmountValue(amountForMethod);
          syncProPaymentAmountInput(amountInput, false);
          updateProPaymentSummary();
          return;
        }

        const removeBtn = event.target.closest('.pro-remove-payment-row');
        if (!removeBtn) return;
        const row = event.target.closest('[data-pro-payment-row]');
        if (row) {
          row.remove();
          updateProPaymentSummary();
        }
      };

      paymentRowsContainer.oninput = (event) => {
        const amountInput = event.target.closest('.pro-payment-amount');
        if (amountInput) {
          sanitizeLiveMoneyInput(amountInput, parseProPaymentAmountValue, normalizeEditableProPaymentValue);
        }

        const imageInput = event.target.closest('.pro-payment-reference-image');
        if (imageInput) {
          const row = imageInput.closest('[data-pro-payment-row]');
          const file = imageInput.files?.[0] || null;

          if (file && file.size > PRO_PAYMENT_SAFE_IMAGE_BYTES) {
            imageInput.value = '';
            alert(`La imagen de comprobante supera ${formatBytesToMb(PRO_PAYMENT_SAFE_IMAGE_BYTES)}. Reduce su tamaño para evitar error 413.`);
          }

          const effectiveFile = imageInput.files?.[0] || null;
          const nameElement = row ? row.querySelector('.pro-payment-reference-image-name') : null;
          if (nameElement) {
            nameElement.textContent = effectiveFile ? effectiveFile.name : 'Sin imagen cargada';
          }
        }
        updateProPaymentSummary();
      };

      paymentRowsContainer.onfocusin = (event) => {
        const amountInput = event.target.closest('.pro-payment-amount');
        if (!amountInput) {
          return;
        }

        const normalizedValue = normalizeEditableProPaymentValue(amountInput.value).text;
        if (normalizedValue && amountInput.value !== normalizedValue) {
          amountInput.value = normalizedValue;
        }
      };

      paymentRowsContainer.onfocusout = (event) => {
        const amountInput = event.target.closest('.pro-payment-amount');
        if (!amountInput) {
          return;
        }

        syncProPaymentAmountInput(amountInput, true);
        updateProPaymentSummary();
      };

      paymentRowsContainer.onchange = (event) => {
        const row = event.target.closest('[data-pro-payment-row]');
        if (row) {
          populatePaymentRowDetails(row);
        }
        updateProPaymentSummary();
      };

      const initialDeliveryContext = getTenantDeliveryContext(document.querySelector('input[name="tenant-pro-delivery-type"]:checked')?.value || 'pickup', proShippingDistanceInput, false);
      totalAmountElement.textContent = `${(getCheckoutProItemsSubtotalBase(cart) + Number(initialDeliveryContext.fee || 0)).toFixed(2)} ${getBaseCurrencySymbol()}`;
      updateProPaymentSummary();
      syncAppointmentPaymentModeUi();

      showCheckoutState();

      const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
      modal.show();
    }

    async function loginProCustomer(event) {
      event.preventDefault();
      clearTenantAuthAlert();
      const login = document.getElementById('tenant-pro-login-email').value.trim();
      const password = document.getElementById('tenant-pro-login-password').value;

      const response = await fetch('/api/loginEcomm', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': getCsrfToken(),
        },
        body: JSON.stringify({ login, password })
      });

      const data = await response.json().catch(() => ({}));
      if (!response.ok || !data.token || !data.user) {
        const message = resolveTenantApiErrorMessage(data, 'No se pudo iniciar sesión.');
        if (isExpiredTokenMessage(message)) {
          clearAuthData();
        }

        showTenantAuthAlert(message);
        return;
      }

      setAuthData(data.token, data.user);
      showTenantToast('Acceso correcto', `Inicio de sesión correcto, ${data.user?.name || 'cliente'}.`);
      openProCheckout({ authOnly: !cartEnabled || getCart().length === 0 });
    }

    async function registerProCustomer(event) {
      event.preventDefault();
      clearTenantAuthAlert();
      const name = document.getElementById('tenant-pro-register-name').value.trim();
      const email = document.getElementById('tenant-pro-register-email').value.trim();
      const password = document.getElementById('tenant-pro-register-password').value;
      const password_confirmation = document.getElementById('tenant-pro-register-password-confirmation').value;
      const dni = document.getElementById('tenant-pro-register-dni').value.trim();
      const phoneCode = document.getElementById('tenant-pro-register-phone-code')?.value || '+58';
      const rawPhone = document.getElementById('tenant-pro-register-phone').value;
      const normalizedPhone = String(rawPhone || '').replace(/\D+/g, '');
      const normalizedCode = String(phoneCode || '').replace(/\D+/g, '') || '58';
      const phone_number = normalizedPhone ? `+${normalizedCode}${normalizedPhone}` : '';

      const response = await fetch('/api/registerEcomm', {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-Requested-With': 'XMLHttpRequest',
          'X-CSRF-TOKEN': getCsrfToken(),
        },
        body: JSON.stringify({ name, email, password, password_confirmation, dni, phone_number })
      });

      const data = await response.json().catch(() => ({}));
      if (!response.ok || !data.token || !data.user) {
        const message = resolveTenantApiErrorMessage(data, 'No se pudo crear la cuenta.');
        if (isExpiredTokenMessage(message)) {
          clearAuthData();
        }

        showTenantAuthAlert(message);
        return;
      }

      setAuthData(data.token, data.user);
      showTenantToast('Cuenta creada', `Sesión iniciada correctamente, ${data.user?.name || 'cliente'}.`);
      openProCheckout({ authOnly: !cartEnabled || getCart().length === 0 });
    }

    async function submitProOrder() {
      const token = getAuthToken();
      const user = getAuthUser();
      if (!token || !user?.id) {
        alert('Debes iniciar sesión para completar el checkout.');
        return;
      }

      const cart = getCart();
      const appointmentModeActive = isAppointmentCheckoutActive();
      const checkoutItems = buildCheckoutProItemsPayload(cart);

      if (!appointmentModeActive && cart.length === 0) {
        alert('Tu carrito está vacío.');
        return;
      }

      if (appointmentModeActive && checkoutItems.length === 0) {
        alert('El servicio seleccionado no tiene un producto asociado para facturar.');
        return;
      }

      const missingVariant = checkoutItems.some(item => Number(item?.variant_id || 0) <= 0);
      if (missingVariant) {
        alert('Hay productos antiguos en tu carrito sin variante válida. Elimínalos y vuelve a agregarlos.');
        return;
      }

      const appointmentPaymentMode = String(proAppointmentPaymentModeSelect?.value || 'online');
      const requiresOnlinePayment = !appointmentModeActive || appointmentPaymentMode === 'online';

      let selectedAppointmentServiceId = null;
      let selectedAppointmentUserId = null;
      let selectedAppointmentDate = '';
      let selectedAppointmentStartTime = '';

      if (appointmentModeActive) {
        selectedAppointmentServiceId = Number(proAppointmentServiceSelect?.value || 0);
        selectedAppointmentUserId = Number(proAppointmentUserSelect?.value || 0);
        selectedAppointmentDate = String(proAppointmentDateInput?.value || '').trim();
        selectedAppointmentStartTime = String(proAppointmentSlotSelect?.value || '').trim();

        if (selectedAppointmentServiceId <= 0 || selectedAppointmentUserId <= 0 || !selectedAppointmentDate || !selectedAppointmentStartTime) {
          alert('Debes completar servicio, profesional, fecha y hora de la cita antes de confirmar.');
          return;
        }

        try {
          const slotParams = new URLSearchParams({
            service_id: String(selectedAppointmentServiceId),
            user_id: String(selectedAppointmentUserId),
            date: selectedAppointmentDate,
            month: getMonthFromDateValue(selectedAppointmentDate) || appointmentCalendarMonth || getCurrentLocalMonthValue(),
          });

          const slotValidationResponse = await fetch(`${tenantAppointmentAvailabilityEndpoint}?${slotParams.toString()}`, {
            headers: {
              Accept: 'application/json',
            },
          });

          if (!slotValidationResponse.ok) {
            alert('No se pudo validar la disponibilidad de la cita. Intenta nuevamente.');
            return;
          }

          const slotValidationPayload = await slotValidationResponse.json().catch(() => ({}));
          const latestSlots = Array.isArray(slotValidationPayload?.slots) ? slotValidationPayload.slots : [];
          const slotStillAvailable = latestSlots.some(slot => String(slot?.start || '') === selectedAppointmentStartTime);

          if (!slotStillAvailable) {
            alert('Ese horario ya no está disponible. Te mostramos la disponibilidad más reciente para que selecciones otro horario.');
            appointmentCalendarDays = Array.isArray(slotValidationPayload?.calendar) ? slotValidationPayload.calendar : appointmentCalendarDays;
            proAppointmentSlotSelect.value = '';
            await refreshAppointmentSlots();
            return;
          }
        } catch (error) {
          alert('No se pudo validar la disponibilidad de la cita. Revisa tu conexión e intenta otra vez.');
          return;
        }
      }

      const deliveryType = appointmentModeActive
        ? 'pickup'
        : (document.querySelector('input[name="tenant-pro-delivery-type"]:checked')?.value || 'pickup');

      const deliveryAddressResult = appointmentModeActive
        ? { valid: true, cityId: null, address: 'Tienda', latitude: null, longitude: null }
        : buildAddressForDeliveryType(deliveryType, {
        countrySelect: proShippingCountrySelect,
        stateSelect: proShippingStateSelect,
        citySelect: proShippingCitySelect,
        detailInput: proShippingAddressDetailInput,
        latitudeInput: proShippingLatitudeInput,
        longitudeInput: proShippingLongitudeInput,
        receiverNameInput: proDeliveryReceiverNameInput,
        receiverPhoneInput: proDeliveryReceiverPhoneInput,
        receiverPhoneCodeInput: proDeliveryReceiverPhoneCodeInput,
        extraInfoInput: proDeliveryExtraInfoInput,
      });

      if (!appointmentModeActive && ['delivery', 'shipping'].includes(deliveryType) && !deliveryAddressResult.valid) {
        alert(deliveryAddressResult.message);
        return;
      }

      const proDeliveryContext = appointmentModeActive
        ? getTenantDeliveryContext('pickup', proShippingDistanceInput, false)
        : getTenantDeliveryContext(deliveryType, proShippingDistanceInput, true);

      if (!proDeliveryContext.valid) {
        alert(proDeliveryContext.message || 'Debes completar la información del delivery.');
        return;
      }

      const paymentRows = Array.from(document.querySelectorAll('[data-pro-payment-row]'));

      const totalProofBytes = requiresOnlinePayment
        ? paymentRows.reduce((sum, row) => {
            const imageFile = row.querySelector('.pro-payment-reference-image')?.files?.[0] || null;
            return sum + Number(imageFile?.size || 0);
          }, 0)
        : 0;

      if (requiresOnlinePayment && totalProofBytes > PRO_PAYMENT_SAFE_TOTAL_BYTES) {
        alert(`Las imágenes de comprobante pesan ${formatBytesToMb(totalProofBytes)} en total. Reduce el total por debajo de ${formatBytesToMb(PRO_PAYMENT_SAFE_TOTAL_BYTES)} para evitar error 413.`);
        return;
      }

      const payments = requiresOnlinePayment ? (await Promise.all(paymentRows.map(async row => {
        const methodId = Number(row.querySelector('.pro-payment-method')?.value || 0);
        const amountRaw = parseProPaymentAmountValue(row.querySelector('.pro-payment-amount')?.value || 0);
        const method = getMethodById(methodId);
        const amount = toBaseFromMethodAmount(method, amountRaw);
        const requiresReference = !!method?.has_reference;
        const reference = requiresReference ? (row.querySelector('.pro-payment-reference')?.value || '').trim() : '';
        const imageFile = requiresReference ? (row.querySelector('.pro-payment-reference-image')?.files?.[0] || null) : null;
        const referenceImageData = imageFile ? await fileToDataUrl(imageFile) : null;

        return {
          method_id: methodId,
          amount,
          reference,
          reference_image_data: referenceImageData,
          reference_image_mime: imageFile?.type || null,
        };
      }))).filter(payment => payment.method_id > 0 && payment.amount > 0) : [];

      if (requiresOnlinePayment && payments.length === 0) {
        alert('Debes agregar al menos un pago válido.');
        return;
      }

      const hasMissingReference = requiresOnlinePayment && payments.some(payment => {
        const method = getMethodById(payment.method_id);
        return !!method?.has_reference && !String(payment.reference || '').trim();
      });
      if (hasMissingReference) {
        alert('Cada pago debe incluir una referencia.');
        return;
      }

      const hasMissingProofImage = requiresOnlinePayment && payments.some(payment => {
        const method = getMethodById(payment.method_id);
        return !!method?.has_reference && !String(payment.reference_image_data || '').trim();
      });
      if (hasMissingProofImage) {
        alert('Cada pago debe incluir una imagen de comprobante.');
        return;
      }

      if (requiresOnlinePayment) {
        const totalPaidBase = roundProMoney(payments.reduce((sum, payment) => sum + Number(payment.amount || 0), 0));
        const totalOrderBaseWithoutIgtf = getCheckoutProItemsSubtotalBase(cart) + Number(proDeliveryContext.fee || 0);
        const igtfTotals = calculateProIgtfTotals(paymentRows, totalOrderBaseWithoutIgtf);
        const totalOrderBase = roundProMoney(igtfTotals.totalWithIgtf);
        if (totalPaidBase + 0.0001 < totalOrderBase) {
          const remainingBase = roundProMoney(totalOrderBase - totalPaidBase);
          alert(`Falta por pagar: ${remainingBase.toFixed(2)} ${getBaseCurrencySymbol()} / ${(remainingBase * proBaseRate).toFixed(2)} Bs`);
          return;
        }
      }

      const items = checkoutItems;

      let response;
      setProSubmitLoading(true);
      try {
        response = await fetch(`/${tenantSlug}/checkout/pro`, {
          method: 'POST',
          credentials: 'same-origin',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'Authorization': `Bearer ${token}`,
            'X-Requested-With': 'XMLHttpRequest',
            'X-CSRF-TOKEN': getCsrfToken(),
          },
          body: JSON.stringify({
            customer_id: Number(user.id),
            delivery_type: deliveryType,
            delivery_address: ['delivery', 'shipping'].includes(deliveryType) ? deliveryAddressResult.address : 'Tienda',
            delivery_city_id: ['delivery', 'shipping'].includes(deliveryType) ? Number(deliveryAddressResult.cityId || 0) : null,
            delivery_distance_km: deliveryType === 'delivery' ? proDeliveryContext.distanceKm : null,
            delivery_latitude: ['delivery', 'shipping'].includes(deliveryType) ? deliveryAddressResult.latitude : null,
            delivery_longitude: ['delivery', 'shipping'].includes(deliveryType) ? deliveryAddressResult.longitude : null,
            items,
            payments,
            mark_delivered: false,
            mark_payments_paid: false,
            mark_sale_completed: false,
            appointment_mode: appointmentModeActive,
            appointment_service_id: appointmentModeActive ? selectedAppointmentServiceId : null,
            appointment_user_id: appointmentModeActive ? selectedAppointmentUserId : null,
            appointment_date: appointmentModeActive ? selectedAppointmentDate : null,
            appointment_start_time: appointmentModeActive ? selectedAppointmentStartTime : null,
            appointment_payment_mode: appointmentModeActive ? appointmentPaymentMode : null,
          })
        });
      } catch (error) {
        alert('No se pudo conectar con la tienda para registrar el pedido.');
        setProSubmitLoading(false);
        return;
      }

      let data = {};
      try {
        data = await response.json();
      } catch (error) {
      }

      if (!response.ok) {
        if (response.status === 413) {
          alert('La solicitud es demasiado grande (413). Reduce el peso total de los comprobantes e intenta de nuevo.');
          setProSubmitLoading(false);
          return;
        }

        alert(data.message || data.error || 'No se pudo completar el pedido.');
        setProSubmitLoading(false);
        return;
      }

      setProSubmitLoading(false);
      const successMessage = appointmentModeActive
        ? `Tu cita fue apartada correctamente para ${formatAppointmentSelectedDateLabel(selectedAppointmentDate)} a las ${selectedAppointmentStartTime}. Te notificaremos cuando el equipo confirme.`
        : 'Tu compra fue enviada correctamente. Te llegará una notificación con el seguimiento.';

      showCheckoutSuccess(data.order_id || null, {
        message: successMessage,
        appointment: data?.appointment || null,
        promptNotifications: appointmentModeActive,
      });
      saveCart([]);
    }

    document.addEventListener('click', event => {
      const authTrigger = event.target.closest('[data-shopix-open-auth]');
      if (authTrigger) {
        event.preventDefault();
        closeTenantCartOffcanvas();
        window.dispatchEvent(new CustomEvent('shopix-open-auth-requested'));
        return;
      }

      const socialTrigger = event.target.closest('.tenant-auth-social-btn:not(.is-disabled)');
      if (socialTrigger) {
        setCheckoutResumeState(cartEnabled && getCart().length > 0);
      }

      const copyAllButton = event.target.closest('.pro-copy-all');
      if (copyAllButton) {
        const copyAllValue = decodeURIComponent(copyAllButton.dataset.copyAllValue || '');
        copyToClipboard(copyAllValue).then((copied) => {
          const originalText = copyAllButton.textContent;
          copyAllButton.textContent = copied ? 'Copiado' : 'Error';
          setTimeout(() => {
            copyAllButton.textContent = originalText;
          }, 900);
        });
        return;
      }

      const copyButton = event.target.closest('.pro-copy-field');
      if (copyButton) {
        const copyValue = decodeURIComponent(copyButton.dataset.copyValue || '');
        copyToClipboard(copyValue).then((copied) => {
          const originalText = copyButton.textContent;
          copyButton.textContent = copied ? 'Copiado' : 'Error';
          setTimeout(() => {
            copyButton.textContent = originalText;
          }, 900);
        });
      }

      const removeButton = event.target.closest('[data-remove-index]');
      if (removeButton) {
        removeItem(Number(removeButton.dataset.removeIndex));
      }

      const increaseButton = event.target.closest('[data-increase-index]');
      if (increaseButton) {
        const index = Number(increaseButton.dataset.increaseIndex);
        const cart = getCart();
        if (cart[index]) {
          changeQty(index, Number(cart[index].qty) + 1);
        }
      }

      const decreaseButton = event.target.closest('[data-decrease-index]');
      if (decreaseButton) {
        const index = Number(decreaseButton.dataset.decreaseIndex);
        const cart = getCart();
        if (cart[index]) {
          changeQty(index, Number(cart[index].qty) - 1);
        }
      }
    });

    tenantPackageFlavorRows?.addEventListener('input', event => {
      const input = event.target.closest('[data-tenant-package-component-index]');
      if (!input || !pendingPackageSelection) {
        return;
      }

      const componentIndex = Number(input.dataset.tenantPackageComponentIndex);
      const choiceIndex = Number(input.dataset.tenantPackageChoiceIndex);
      const component = pendingPackageSelection.components[componentIndex];
      const choice = component?.choices?.[choiceIndex];
      if (!component || !choice) {
        return;
      }

      const parsed = Math.max(0, Math.min(Number(choice.variant_stock || 0), Number.parseFloat(input.value || '0') || 0));
      choice.quantity = parsed;
      input.value = String(parsed);
    });

    tenantConfirmPackageFlavorBtn?.addEventListener('click', confirmTenantPackageFlavorSelection);

    tenantPackageFlavorModalElement?.addEventListener('hidden.bs.modal', () => {
      pendingPackageSelection = null;
      if (tenantPackageFlavorSummary) {
        tenantPackageFlavorSummary.innerHTML = '';
      }
      if (tenantPackageFlavorRows) {
        tenantPackageFlavorRows.innerHTML = '';
      }
    });

    deliveryTypeInputs.forEach(input => {
      input.addEventListener('change', updateDeliveryAddressVisibility);
    });

    shippingDistanceInput?.addEventListener('input', updateDeliveryAddressVisibility);

    checkoutButton.addEventListener('click', () => {
      closeTenantCartOffcanvas();

      if (cartEnabled) {
        openProCheckout();
      } else {
        checkoutByWhatsApp();
      }
    });

    whatsappConsultButton?.addEventListener('click', () => {
      closeTenantCartOffcanvas();
      checkoutByWhatsApp({ consultOnly: true });
    });

    document.getElementById('tenant-pro-login-form')?.addEventListener('submit', loginProCustomer);
    document.getElementById('tenant-pro-register-form')?.addEventListener('submit', registerProCustomer);
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

    const resumedCheckoutState = consumeCheckoutResumeState();
    if (resumedCheckoutState) {
      const resumedUser = getAuthUser();
      if (resumedUser?.id) {
        showTenantToast('Acceso correcto', `Inicio de sesión correcto, ${resumedUser.name || 'cliente'}.`);
      }
      const shouldResumeCheckout = !!resumedCheckoutState.checkout && cartEnabled && getCart().length > 0 && !!getAuthToken();
      openProCheckout({ authOnly: !shouldResumeCheckout });
    }

    const urlAuthError = new URLSearchParams(window.location.search).get('shopix_auth_error');
    if (urlAuthError) {
      showTenantAuthAlert(urlAuthError);
      openProCheckout({ authOnly: true });

      const sanitizedUrl = new URL(window.location.href);
      sanitizedUrl.searchParams.delete('shopix_auth_error');
      window.history.replaceState({}, document.title, sanitizedUrl.pathname + sanitizedUrl.search + sanitizedUrl.hash);
    }

    if (cartEnabled) {
      bindCatalogAppointmentSectionEvents();
      if (catalogAppointmentSections.length > 0) {
        loadAppointmentCheckoutAvailability().catch(() => {
          forEachCatalogAppointmentPanel(({ note }) => {
            if (note) {
              note.textContent = 'No se pudo cargar la agenda semanal en este momento.';
            }
          });
        });
      }

      proAppointmentServiceSelect?.addEventListener('change', async () => {
        syncAppointmentProfessionalByService();
        proAppointmentSlotSelect.value = '';
        syncAppointmentPaymentModeUi();
        updateProPaymentSummary();
        await refreshAppointmentSlots();
      });

      proAppointmentUserSelect?.addEventListener('change', async () => {
        proAppointmentSlotSelect.value = '';
        syncAppointmentPaymentModeUi();
        await refreshAppointmentSlots();
      });

      proAppointmentDateInput?.addEventListener('change', async () => {
        const selectedDate = String(proAppointmentDateInput.value || '').trim();
        const selectedMonth = getMonthFromDateValue(selectedDate);
        if (selectedMonth) {
          appointmentCalendarMonth = selectedMonth;
        }

        proAppointmentSlotSelect.value = '';
        syncSelectedAppointmentDateDisplay();
        syncAppointmentPaymentModeUi();
        await refreshAppointmentSlots();
      });

      proAppointmentCalendarGrid?.addEventListener('click', async (event) => {
        const targetButton = event.target.closest('[data-appointment-calendar-date]');
        if (!targetButton || !proAppointmentDateInput) {
          return;
        }

        const dateValue = String(targetButton.getAttribute('data-appointment-calendar-date') || '').trim();
        if (!dateValue) {
          return;
        }

        proAppointmentDateInput.value = dateValue;
        const selectedMonth = getMonthFromDateValue(dateValue);
        if (selectedMonth) {
          appointmentCalendarMonth = selectedMonth;
        }

        proAppointmentSlotSelect.value = '';
        syncSelectedAppointmentDateDisplay();
        syncAppointmentPaymentModeUi();
        await refreshAppointmentSlots();
      });

      proAppointmentCalendarPrevBtn?.addEventListener('click', () => {
        shiftAppointmentCalendarMonth(-1);
      });

      proAppointmentCalendarNextBtn?.addEventListener('click', () => {
        shiftAppointmentCalendarMonth(1);
      });

      proAppointmentSlotSelect?.addEventListener('change', () => {
        syncAppointmentPaymentModeUi();
        updateProPaymentSummary();
      });

      proAppointmentPaymentModeSelect?.addEventListener('change', () => {
        syncAppointmentPaymentModeUi();
        updateProPaymentSummary();
      });

      document.querySelectorAll('input[name="tenant-pro-delivery-type"]').forEach(input => {
        input.addEventListener('change', () => {
          if (isAppointmentCheckoutActive()) {
            updateProPaymentSummary();
            return;
          }

          const currentType = document.querySelector('input[name="tenant-pro-delivery-type"]:checked')?.value || 'pickup';
          const isAddressRequired = ['delivery', 'shipping'].includes(currentType);
          const isStoreDelivery = currentType === 'delivery';
          const isThirdPartyShipping = currentType === 'shipping';
          document.getElementById('tenant-pro-shipping-address-container').classList.toggle('d-none', !isAddressRequired);
          updateAddressSectionUi(
            currentType,
            {
              addressLabelElement: proShippingAddressLabel,
              detailInput: proShippingAddressDetailInput,
              detailWrap: proShippingDetailWrap,
              hintElement: proShippingAddressHint,
              countryWraps: [proShippingLocationSelectCountryWrap, proShippingLocationSelectStateWrap, proShippingLocationSelectCityWrap],
              locationActionsElement: proShippingLocationActions,
              locationStatusElement: proShippingLocationStatus,
              latitudeInput: proShippingLatitudeInput,
              longitudeInput: proShippingLongitudeInput,
              receiverFieldsElement: proDeliveryRecipientFields,
              distanceInput: proShippingDistanceInput,
            }
          );
          proShippingDistanceWrap?.classList.toggle('d-none', !(isStoreDelivery && tenantDeliveryConfig?.enabled && tenantDeliveryConfig.mode === 'distance'));

          if (isThirdPartyShipping && proShippingCountrySelect && !proShippingCountrySelect.options.length) {
            initLocationSelectors(proShippingCountrySelect, proShippingStateSelect, proShippingCitySelect, {
              countryId: tenantCountryId,
              stateId: tenantStateId,
              cityId: tenantCityId,
            }).catch(() => {
              alert('No se pudieron cargar los selectores de ubicación de envío.');
            });
          }

          updateProPaymentSummary();
        });
      });

      proShippingDistanceInput?.addEventListener('input', updateProPaymentSummary);

      proShippingUseProfileLocationBtn?.addEventListener('click', () => {
        const currentType = document.querySelector('input[name="tenant-pro-delivery-type"]:checked')?.value || 'pickup';
        if (currentType === 'delivery') {
          applyUserLocationCoordinates(getAuthUser(), proShippingLatitudeInput, proShippingLongitudeInput, proShippingLocationStatus, proShippingDistanceInput);
          return;
        }

        applyUserLocationToShippingForm(getAuthUser(), proShippingCountrySelect, proShippingStateSelect, proShippingCitySelect, proShippingAddressDetailInput, proShippingLatitudeInput, proShippingLongitudeInput, proShippingLocationStatus, proShippingDistanceInput);
      });

      proShippingUseCurrentLocationBtn?.addEventListener('click', () => {
        requestCurrentUserLocation(proShippingLatitudeInput, proShippingLongitudeInput, proShippingLocationStatus, proShippingDistanceInput);
      });

      proDeliveryUseCustomerDataBtn?.addEventListener('click', () => {
        fillReceiverFieldsFromUser(getAuthUser(), proDeliveryReceiverNameInput, proDeliveryReceiverPhoneInput, proDeliveryReceiverPhoneCodeInput);
      });

      proDeliveryOpenMapBtn?.addEventListener('click', () => {
        openDeliveryMapPicker({
          latitudeInput: proShippingLatitudeInput,
          longitudeInput: proShippingLongitudeInput,
          statusElement: proShippingLocationStatus,
          distanceInput: proShippingDistanceInput,
        });
      });

      document.getElementById('tenant-pro-next-step')?.addEventListener('click', () => {
        if (!validateCheckoutStepOne()) {
          return;
        }

        setCheckoutStep(2);
      });

      document.getElementById('tenant-pro-prev-step')?.addEventListener('click', () => {
        setCheckoutStep(1);
      });

      document.getElementById('tenant-pro-submit-order')?.addEventListener('click', submitProOrder);
    }

    tenantProCheckoutModalElement?.addEventListener('hidden.bs.modal', () => {
      clearAppointmentStatusPolling();
      trackedAppointmentId = 0;
      if (proAppointmentStatusWrap) {
        proAppointmentStatusWrap.classList.add('d-none');
      }
    });

    updateDeliveryAddressVisibility();
    bindLocationSelectorEvents(shippingCountrySelect, shippingStateSelect, shippingCitySelect);
    bindLocationSelectorEvents(proShippingCountrySelect, proShippingStateSelect, proShippingCitySelect);

    shippingUseProfileLocationBtn?.addEventListener('click', () => {
      const currentType = document.querySelector('input[name="tenant-delivery-type"]:checked')?.value || 'pickup';
      if (currentType === 'delivery') {
        applyUserLocationCoordinates(getAuthUser(), shippingLatitudeInput, shippingLongitudeInput, shippingLocationStatus, shippingDistanceInput);
        return;
      }

      applyUserLocationToShippingForm(getAuthUser(), shippingCountrySelect, shippingStateSelect, shippingCitySelect, shippingAddressDetailInput, shippingLatitudeInput, shippingLongitudeInput, shippingLocationStatus, shippingDistanceInput);
    });

    shippingUseCurrentLocationBtn?.addEventListener('click', () => {
      requestCurrentUserLocation(shippingLatitudeInput, shippingLongitudeInput, shippingLocationStatus, shippingDistanceInput);
    });

    deliveryUseCustomerDataBtn?.addEventListener('click', () => {
      fillReceiverFieldsFromUser(getAuthUser(), deliveryReceiverNameInput, deliveryReceiverPhoneInput, deliveryReceiverPhoneCodeInput);
    });

    deliveryOpenMapBtn?.addEventListener('click', () => {
      openDeliveryMapPicker({
        latitudeInput: shippingLatitudeInput,
        longitudeInput: shippingLongitudeInput,
        statusElement: shippingLocationStatus,
        distanceInput: shippingDistanceInput,
      });
    });

    deliveryMapModalElement?.addEventListener('shown.bs.modal', () => {
      initializeDeliveryMap();
      if (!pendingDeliveryMapPosition) {
        pendingDeliveryMapPosition = getContextCoordinates(activeDeliveryMapContext);
      }

      if (window.google?.maps && deliveryMap) {
        google.maps.event.trigger(deliveryMap, 'resize');
        deliveryMap.setCenter(pendingDeliveryMapPosition);
        deliveryMap.setZoom(16);
      }

      if (deliveryMapMarker) {
        deliveryMapMarker.setPosition(pendingDeliveryMapPosition);
      }

      updateDeliveryMapStatus(pendingDeliveryMapPosition);
    });

    deliveryMapConfirmBtn?.addEventListener('click', () => {
      if (!activeDeliveryMapContext || !pendingDeliveryMapPosition) {
        return;
      }

      if (activeDeliveryMapContext.latitudeInput) {
        activeDeliveryMapContext.latitudeInput.value = pendingDeliveryMapPosition.lat;
      }

      if (activeDeliveryMapContext.longitudeInput) {
        activeDeliveryMapContext.longitudeInput.value = pendingDeliveryMapPosition.lng;
      }

      renderShippingLocationStatus(
        activeDeliveryMapContext.statusElement,
        activeDeliveryMapContext.latitudeInput,
        activeDeliveryMapContext.longitudeInput,
        activeDeliveryMapContext.distanceInput
      );

      if (typeof bootstrap !== 'undefined' && bootstrap?.Modal && deliveryMapModalElement) {
        bootstrap.Modal.getInstance(deliveryMapModalElement)?.hide();
      }
    });

    if (shippingCountrySelect) {
      initLocationSelectors(shippingCountrySelect, shippingStateSelect, shippingCitySelect, {
        countryId: tenantCountryId,
        stateId: tenantStateId,
        cityId: tenantCityId,
      }).catch(() => {
      });
    }

    applyUserLocationToShippingForm(getAuthUser(), shippingCountrySelect, shippingStateSelect, shippingCitySelect, shippingAddressDetailInput, shippingLatitudeInput, shippingLongitudeInput, shippingLocationStatus, shippingDistanceInput);

    if (proShippingCountrySelect) {
      initLocationSelectors(proShippingCountrySelect, proShippingStateSelect, proShippingCitySelect, {
        countryId: tenantCountryId,
        stateId: tenantStateId,
        cityId: tenantCityId,
      }).catch(() => {
      });
    }

    applyUserLocationToShippingForm(getAuthUser(), proShippingCountrySelect, proShippingStateSelect, proShippingCitySelect, proShippingAddressDetailInput, proShippingLatitudeInput, proShippingLongitudeInput, proShippingLocationStatus, proShippingDistanceInput);

    setProSubmitLoading(false);
    renderCart();

    const tenantCartOffcanvasElement = document.getElementById('tenantCartOffcanvas');
    if (tenantCartOffcanvasElement) {
      tenantCartOffcanvasElement.addEventListener('show.bs.offcanvas', () => {
        cartDebug('offcanvas:show-event');
        renderCart();
        dumpCartDebugState('offcanvas-show');
      });
    }

    window.addEventListener('shopix-cart-updated', () => {
      cartDebug('event:shopix-cart-updated');
      renderCart();
      dumpCartDebugState('cart-updated-event');
    });

    document.addEventListener('shopix-cart-debug-dump', (event) => {
      dumpCartDebugState(event?.detail?.source || 'document-event');
    });

    window.dispatchEvent(new CustomEvent('shopix-cart-ready'));
  })();
</script>
