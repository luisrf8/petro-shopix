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

  .tenant-pro-auth-wrap,
  #tenant-pro-checkout-section {
    border: 1px solid rgba(var(--tenant-accent-rgb), 0.38);
    border-radius: 16px;
    background: #ffffff;
    padding: 0.85rem;
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

  #tenant-pro-logged-user {
    border-radius: 12px;
    border: 1px solid #bbf7d0;
    background: #f0fdf4;
    color: #166534;
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
    margin-top: 0.85rem;
    border: 1px solid rgba(var(--tenant-accent-rgb), 0.35);
    border-radius: 14px;
    background: rgba(var(--tenant-accent-rgb), 0.08);
    padding: 0.75rem;
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
  }
</style>

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
        <span class="fw-bold tenant-cart-subtotal-amount" id="tenant-cart-subtotal">0.00 $</span>
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
            <input class="form-check-input" type="radio" name="tenant-delivery-type" id="delivery-shipping" value="shipping">
            <label class="form-check-label" for="delivery-shipping">Envío</label>
          </div>
        </div>

        <div class="mb-3 d-none" id="tenant-shipping-address-container">
          <label class="form-label">Dirección de envío</label>
          <div class="row g-2">
            <div class="col-12 col-md-4">
              <select id="tenant-shipping-country" class="form-select">
                <option value="">País</option>
              </select>
            </div>
            <div class="col-12 col-md-4">
              <select id="tenant-shipping-state" class="form-select" disabled>
                <option value="">Estado</option>
              </select>
            </div>
            <div class="col-12 col-md-4">
              <select id="tenant-shipping-city" class="form-select" disabled>
                <option value="">Ciudad</option>
              </select>
            </div>
            <div class="col-12">
              <input type="text" id="tenant-shipping-address-detail" class="form-control" placeholder="Dirección exacta (calle, referencia, etc.)">
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
                  <input type="email" class="form-control" id="tenant-pro-login-email" placeholder="Email" required>
                </div>
                <div class="col-12 col-md-6">
                  <input type="password" class="form-control" id="tenant-pro-login-password" placeholder="Contraseña" required>
                </div>
                <div class="col-12">
                  <button type="submit" class="btn btn-dark">Entrar</button>
                </div>
              </form>
            </div>
            <div class="tab-pane fade" id="tenant-register-panel" role="tabpanel">
              <form id="tenant-pro-register-form" class="row g-2">
                <div class="col-12 col-md-4">
                  <input type="text" class="form-control" id="tenant-pro-register-name" placeholder="Nombre" required>
                </div>
                <div class="col-12 col-md-4">
                  <input type="email" class="form-control" id="tenant-pro-register-email" placeholder="Email" required>
                </div>
                <div class="col-12 col-md-4">
                  <input type="password" class="form-control" id="tenant-pro-register-password" placeholder="Contraseña" minlength="8" required>
                </div>
                <div class="col-12 col-md-4">
                  <input type="password" class="form-control" id="tenant-pro-register-password-confirmation" placeholder="Confirmar contraseña" minlength="8" required>
                </div>
                <div class="col-12 col-md-4">
                  <input type="text" class="form-control" id="tenant-pro-register-dni" placeholder="DNI (opcional)">
                </div>
                <div class="col-12">
                  <button type="submit" class="btn btn-dark">Crear cuenta</button>
                </div>
              </form>
            </div>
          </div>
        </div>

        <div id="tenant-pro-logged-user" class="alert alert-success d-none"></div>

        <div id="tenant-pro-checkout-section" class="d-none">
          <div class="mb-3">
            <label class="form-label">Tipo de entrega</label>
            <div>
              <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="tenant-pro-delivery-type" id="tenant-pro-delivery-pickup" value="pickup" checked>
                <label class="form-check-label" for="tenant-pro-delivery-pickup">Retiro en tienda</label>
              </div>
              <div class="form-check form-check-inline">
                <input class="form-check-input" type="radio" name="tenant-pro-delivery-type" id="tenant-pro-delivery-shipping" value="shipping">
                <label class="form-check-label" for="tenant-pro-delivery-shipping">Envío</label>
              </div>
            </div>
          </div>

          <div class="mb-3 d-none" id="tenant-pro-shipping-address-container">
            <label class="form-label">Dirección de envío</label>
            <div class="row g-2">
              <div class="col-12 col-md-4">
                <select id="tenant-pro-shipping-country" class="form-select">
                  <option value="">País</option>
                </select>
              </div>
              <div class="col-12 col-md-4">
                <select id="tenant-pro-shipping-state" class="form-select" disabled>
                  <option value="">Estado</option>
                </select>
              </div>
              <div class="col-12 col-md-4">
                <select id="tenant-pro-shipping-city" class="form-select" disabled>
                  <option value="">Ciudad</option>
                </select>
              </div>
              <div class="col-12">
                <input type="text" id="tenant-pro-shipping-address-detail" class="form-control" placeholder="Dirección exacta (calle, referencia, etc.)">
              </div>
            </div>
          </div>

          <hr>
          <h6>Métodos de pago</h6>
          <p class="small text-muted mb-2">Cada pago requiere referencia y comprobante de pago (imagen).</p>
          <div id="tenant-pro-payment-rows" class="d-flex flex-column gap-2"></div>
          <button type="button" id="tenant-pro-add-payment-row" class="btn btn-outline-dark btn-sm mt-2">+ Agregar pago</button>

          <div class="tenant-pro-summary mt-3">
            <div class="tenant-pro-summary-row">
              <strong>Total carrito</strong>
              <strong class="highlight" id="tenant-pro-total-amount">0.00 $</strong>
            </div>
            <div class="tenant-pro-summary-row">
              <span class="text-muted">Total carrito (Bs)</span>
              <span id="tenant-pro-total-amount-bs" class="text-muted">0.00 Bs</span>
            </div>
            <div class="tenant-pro-summary-row mt-2">
              <span class="fw-semibold">Pagado</span>
              <span id="tenant-pro-paid-amount">0.00 $</span>
            </div>
            <div class="tenant-pro-summary-row">
              <span class="text-muted">Pagado (Bs)</span>
              <span id="tenant-pro-paid-amount-bs" class="text-muted">0.00 Bs</span>
            </div>
            <div class="tenant-pro-summary-row mt-2">
              <strong>Restante</strong>
              <strong class="highlight" id="tenant-pro-remaining-amount">0.00 $</strong>
            </div>
            <div class="tenant-pro-summary-row">
              <span class="text-muted">Restante (Bs)</span>
              <span id="tenant-pro-remaining-amount-bs" class="text-muted">0.00 Bs</span>
            </div>
            <div class="tenant-pro-note">Tasa referencial: <span id="tenant-pro-dollar-rate">0.00</span> Bs por USD</div>
          </div>
        </div>
      </div>
      <div class="modal-footer tenant-pro-modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
        <button type="button" class="btn btn-success" id="tenant-pro-submit-order" disabled>Confirmar pedido</button>
      </div>
    </div>
  </div>
</div>

<script>
  (() => {
    const tenantSlug = @json($tenant->slug);
    const cartEnabled = @json((bool) ($cartEnabled ?? false));
    const tenantName = @json($tenant->name);
    const tenantPhoneCode = @json($tenant->phone_code ?? '');
    const tenantPhoneNumber = @json($tenant->phone_number ?? '');
    const tenantCountryId = @json($tenant->country ?? null);
    const tenantStateId = @json($tenant->state ?? null);
    const tenantCityId = @json($tenant->city ?? null);
    const shopixDebug = true;

    function cartDebug(...args) {
      if (!shopixDebug) return;
      console.log('[ShopixCart Debug][Offcanvas]', ...args);
    }

    const storageKey = `shopix_cart_${tenantSlug}`;
    const cartCountElement = document.getElementById('tenant-cart-count');
    const cartItemsElement = document.getElementById('tenant-cart-items');
    const cartSubtotalElement = document.getElementById('tenant-cart-subtotal');
    const cartDisabledAlert = document.getElementById('tenant-cart-disabled-alert');
    const checkoutButton = document.getElementById('tenant-cart-checkout');
    const checkoutForm = document.getElementById('tenant-checkout-form');

    const shippingAddressContainer = document.getElementById('tenant-shipping-address-container');
    const shippingCountrySelect = document.getElementById('tenant-shipping-country');
    const shippingStateSelect = document.getElementById('tenant-shipping-state');
    const shippingCitySelect = document.getElementById('tenant-shipping-city');
    const shippingAddressDetailInput = document.getElementById('tenant-shipping-address-detail');
    const deliveryTypeInputs = document.querySelectorAll('input[name="tenant-delivery-type"]');

    const proShippingCountrySelect = document.getElementById('tenant-pro-shipping-country');
    const proShippingStateSelect = document.getElementById('tenant-pro-shipping-state');
    const proShippingCitySelect = document.getElementById('tenant-pro-shipping-city');
    const proShippingAddressDetailInput = document.getElementById('tenant-pro-shipping-address-detail');

    let countriesCache = null;

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

    function buildShippingAddress(countrySelect, stateSelect, citySelect, detailInput) {
      const countryId = countrySelect?.value || '';
      const stateId = stateSelect?.value || '';
      const cityId = citySelect?.value || '';
      const detail = (detailInput?.value || '').trim();

      if (!countryId || !stateId || !cityId) {
        return { valid: false, message: 'Selecciona país, estado y ciudad para el envío.' };
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
        address: parts.join(', '),
      };
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

    function getTotalQty(cart) {
      return cart.reduce((sum, item) => sum + Number(item.qty), 0);
    }

    function updateDeliveryAddressVisibility() {
      const selectedDeliveryType = document.querySelector('input[name="tenant-delivery-type"]:checked')?.value;
      const isShipping = selectedDeliveryType === 'shipping';
      if (shippingAddressContainer) {
        shippingAddressContainer.classList.toggle('d-none', !isShipping);
      }

      if (isShipping && shippingCountrySelect && !shippingCountrySelect.options.length) {
        initLocationSelectors(shippingCountrySelect, shippingStateSelect, shippingCitySelect, {
          countryId: tenantCountryId,
          stateId: tenantStateId,
          cityId: tenantCityId,
        }).catch(() => {
          alert('No se pudieron cargar los selectores de ubicación de envío.');
        });
      }
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    function renderCart() {
      const cart = getCart();
      const totalQty = getTotalQty(cart);
      const subtotal = getSubtotal(cart);

      cartDebug('renderCart', {
        totalItems: cart.length,
        totalQty,
        subtotal,
      });

      cartCountElement.textContent = totalQty;
      cartSubtotalElement.textContent = `${subtotal.toFixed(2)} $`;

      checkoutButton.disabled = cart.length === 0;

      if (cart.length === 0) {
        cartItemsElement.innerHTML = '<p class="tenant-cart-empty">No hay productos en el carrito.</p>';
        checkoutButton.disabled = true;
        return;
      }

      cartItemsElement.innerHTML = cart.map((item, index) => {
        return `
          <div class="tenant-cart-item-card">
            <div class="d-flex justify-content-between gap-2 align-items-start">
              <div>
                <div class="tenant-cart-item-name">${item.productName}</div>
                <div class="tenant-cart-item-variant">Variante: ${item.variantSize}</div>
                <div class="tenant-cart-item-price">${Number(item.price).toFixed(2)} $ c/u</div>
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
          price: Number(item.price),
          qty: Number(item.qty || 1)
        });
      }

      cartDebug('addItem:next-cart', cart);
      saveCart(cart);
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

    function checkoutByWhatsApp() {
      const cart = getCart();
      if (cart.length === 0) {
        alert('Tu carrito está vacío.');
        return;
      }

      const deliveryType = document.querySelector('input[name="tenant-delivery-type"]:checked')?.value || 'pickup';
      const isShipping = deliveryType === 'shipping';
      const shippingAddressResult = buildShippingAddress(
        shippingCountrySelect,
        shippingStateSelect,
        shippingCitySelect,
        shippingAddressDetailInput
      );
      const authUser = getAuthUser();
      const customerName = (authUser?.name || '').trim();

      if (isShipping && !shippingAddressResult.valid) {
        alert(shippingAddressResult.message);
        return;
      }

      const phone = `${String(tenantPhoneCode).replace(/\D/g, '')}${String(tenantPhoneNumber).replace(/\D/g, '')}`;
      if (!phone) {
        alert('La tienda no tiene un número de WhatsApp configurado.');
        return;
      }

      const lines = [];
      lines.push(`Hola ${tenantName}, quiero realizar este pedido:`);
      if (customerName) {
        lines.push(`Cliente: ${customerName}`);
      }
      lines.push('');

      cart.forEach((item, idx) => {
        const lineTotal = Number(item.qty) * Number(item.price);
        lines.push(`${idx + 1}. ${item.productName} (${item.variantSize}) x${item.qty} - ${lineTotal.toFixed(2)} $`);
      });

      lines.push('');
      lines.push(`Subtotal: ${getSubtotal(cart).toFixed(2)} $`);
      lines.push(`Entrega: ${isShipping ? 'Envío' : 'Retiro en tienda'}`);
      if (isShipping) {
        lines.push(`Dirección de envío: ${shippingAddressResult.address}`);
      }

      const message = encodeURIComponent(lines.join('\n'));
      const link = `https://wa.me/${phone}?text=${message}`;
      window.open(link, '_blank');
    }

    const authTokenKey = 'shopix_ecomm_token';
    const authUserKey = 'shopix_ecomm_user';
    let proPaymentMethods = [];
    let proDollarRate = 0;

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
        <option value="${method.id}" data-currency-code="${method.currency?.code || ''}" data-currency-name="${method.currency?.name || ''}">${method.name} (${method.currency?.code || method.currency?.name || 'USD'})</option>
      `).join('');

      return `
        <div class="border rounded p-2 tenant-pro-payment-row" data-pro-payment-row="${rowId}">
          <div class="row g-2">
            <div class="col-12 col-md-4">
              <label class="form-label small mb-1">Método</label>
              <select class="form-select pro-payment-method">${options}</select>
            </div>
            <div class="col-12 col-md-4">
              <label class="form-label small mb-1">Monto</label>
              <input type="number" step="0.01" min="0" class="form-control pro-payment-amount" placeholder="0.00">
            </div>
            <div class="col-10 col-md-3">
              <label class="form-label small mb-1">Referencia *</label>
              <input type="text" class="form-control pro-payment-reference" placeholder="Obligatoria" required>
            </div>
            <div class="col-2 col-md-1 d-flex align-items-end">
              <button type="button" class="btn btn-outline-danger btn-sm w-100 pro-remove-payment-row" aria-label="Eliminar pago"><i class="bi bi-x-lg"></i></button>
            </div>
            <div class="col-12">
              <div class="small border rounded p-2 bg-light pro-payment-method-details"></div>
            </div>
            <div class="col-12 col-md-6">
              <label class="form-label small mb-1">Imagen de comprobante *</label>
              <input type="file" class="form-control pro-payment-reference-image" accept="image/png,image/jpeg,image/jpg,image/webp" required>
            </div>
            <div class="col-12 col-md-6">
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

    function toUsdFromMethodAmount(method, amount) {
      if (!amount || amount <= 0) return 0;
      if (isBsCurrency(method)) {
        if (!proDollarRate || proDollarRate <= 0) return 0;
        return amount / proDollarRate;
      }
      return amount;
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
        submitButton.disabled = cart.length === 0;
      }
    }

    function populatePaymentRowDetails(row) {
      const methodId = Number(row.querySelector('.pro-payment-method')?.value || 0);
      const method = getMethodById(methodId);
      const detailsBox = row.querySelector('.pro-payment-method-details');
      if (detailsBox) {
        detailsBox.innerHTML = renderMethodDetailsHtml(method);
      }
    }

    function fileToDataUrl(file) {
      return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () => resolve(String(reader.result || ''));
        reader.onerror = () => reject(new Error('No se pudo leer la imagen de referencia.'));
        reader.readAsDataURL(file);
      });
    }

    function updateProPaymentSummary() {
      const totalUsd = getSubtotal(getCart());
      const totalBs = totalUsd * proDollarRate;

      const paymentRows = Array.from(document.querySelectorAll('[data-pro-payment-row]'));
      const paidUsd = paymentRows.reduce((sum, row) => {
        const methodId = Number(row.querySelector('.pro-payment-method')?.value || 0);
        const amount = Number(row.querySelector('.pro-payment-amount')?.value || 0);
        const method = getMethodById(methodId);
        return sum + toUsdFromMethodAmount(method, amount);
      }, 0);

      const remainingUsd = Math.max(totalUsd - paidUsd, 0);
      const paidBs = paidUsd * proDollarRate;
      const remainingBs = remainingUsd * proDollarRate;

      document.getElementById('tenant-pro-total-amount').textContent = `${totalUsd.toFixed(2)} $`;
      document.getElementById('tenant-pro-total-amount-bs').textContent = `${totalBs.toFixed(2)} Bs`;
      document.getElementById('tenant-pro-paid-amount').textContent = `${paidUsd.toFixed(2)} $`;
      document.getElementById('tenant-pro-paid-amount-bs').textContent = `${paidBs.toFixed(2)} Bs`;
      document.getElementById('tenant-pro-remaining-amount').textContent = `${remainingUsd.toFixed(2)} $`;
      document.getElementById('tenant-pro-remaining-amount-bs').textContent = `${remainingBs.toFixed(2)} Bs`;
      document.getElementById('tenant-pro-dollar-rate').textContent = `${(proDollarRate || 0).toFixed(2)}`;
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
      const cart = getCart();
      const authOnly = !!options.authOnly;
      if (!authOnly && cart.length === 0) {
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

      const token = getAuthToken();
      const user = getAuthUser();
      const isLogged = !!token && !!user?.id;

      if (authOnly && cart.length === 0) {
        if (modalTitle) {
          modalTitle.textContent = isLogged ? 'Mi cuenta' : 'Iniciar sesión';
        }

        if (isLogged) {
          authSection.classList.add('d-none');
          checkoutSection.classList.add('d-none');
        } else {
          authSection.classList.remove('d-none');
          checkoutSection.classList.add('d-none');
          const loginTab = document.getElementById('tenant-login-tab');
          if (loginTab) {
            bootstrap.Tab.getOrCreateInstance(loginTab).show();
          }
        }

        paymentRowsContainer.innerHTML = '<p class="tenant-cart-empty">Inicia sesión para consultar tu cuenta o agrega productos al carrito para continuar con el checkout.</p>';
        totalAmountElement.textContent = '0.00 $';
        submitOrderButton.disabled = true;

        const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
        modal.show();
        return;
      }

      if (modalTitle) {
        modalTitle.textContent = 'Checkout Pro';
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

      if (methods.length === 0) {
        alert('Esta tienda no tiene métodos de pago activos para checkout.');
        return;
      }

      if (!proDollarRate || proDollarRate <= 0) {
        alert('La tienda no tiene tasa de dólar configurada. Contacta al comercio.');
        return;
      }

      paymentRowsContainer.innerHTML = '';
      let rowCounter = 0;
      const addPaymentRow = () => {
        rowCounter += 1;
        paymentRowsContainer.insertAdjacentHTML('beforeend', createPaymentRowHtml(methods, `row_${rowCounter}`));
        const row = paymentRowsContainer.lastElementChild;
        if (row) {
          populatePaymentRowDetails(row);
        }
        updateProPaymentSummary();
      };

      addPaymentRow();
      addPaymentRowButton.onclick = addPaymentRow;

      paymentRowsContainer.onclick = (event) => {
        const removeBtn = event.target.closest('.pro-remove-payment-row');
        if (!removeBtn) return;
        const row = event.target.closest('[data-pro-payment-row]');
        if (row) {
          row.remove();
          updateProPaymentSummary();
        }
      };

      paymentRowsContainer.oninput = (event) => {
        const imageInput = event.target.closest('.pro-payment-reference-image');
        if (imageInput) {
          const row = imageInput.closest('[data-pro-payment-row]');
          const file = imageInput.files?.[0] || null;
          const nameElement = row ? row.querySelector('.pro-payment-reference-image-name') : null;
          if (nameElement) {
            nameElement.textContent = file ? file.name : 'Sin imagen cargada';
          }
        }
        updateProPaymentSummary();
      };

      paymentRowsContainer.onchange = (event) => {
        const row = event.target.closest('[data-pro-payment-row]');
        if (row) {
          populatePaymentRowDetails(row);
        }
        updateProPaymentSummary();
      };

      totalAmountElement.textContent = `${getSubtotal(cart).toFixed(2)} $`;
      updateProPaymentSummary();

      if (isLogged) {
        authSection.classList.add('d-none');
        checkoutSection.classList.remove('d-none');
        submitOrderButton.disabled = cart.length === 0;
      } else {
        authSection.classList.remove('d-none');
        checkoutSection.classList.add('d-none');
        submitOrderButton.disabled = true;
      }

      if (authOnly && !isLogged) {
        const loginTab = document.getElementById('tenant-login-tab');
        if (loginTab) {
          bootstrap.Tab.getOrCreateInstance(loginTab).show();
        }
      }

      const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
      modal.show();
    }

    async function loginProCustomer(event) {
      event.preventDefault();
      const email = document.getElementById('tenant-pro-login-email').value.trim();
      const password = document.getElementById('tenant-pro-login-password').value;

      const response = await fetch('/api/loginEcomm', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': csrfToken,
        },
        body: JSON.stringify({ email, password })
      });

      const data = await response.json();
      if (!response.ok || !data.token || !data.user) {
        alert(data.message || 'No se pudo iniciar sesión.');
        return;
      }

      setAuthData(data.token, data.user);
      alert('Sesión iniciada correctamente.');
      openProCheckout({ authOnly: !cartEnabled || getCart().length === 0 });
    }

    async function registerProCustomer(event) {
      event.preventDefault();
      const name = document.getElementById('tenant-pro-register-name').value.trim();
      const email = document.getElementById('tenant-pro-register-email').value.trim();
      const password = document.getElementById('tenant-pro-register-password').value;
      const password_confirmation = document.getElementById('tenant-pro-register-password-confirmation').value;
      const dni = document.getElementById('tenant-pro-register-dni').value.trim();

      const response = await fetch('/api/registerEcomm', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': csrfToken,
        },
        body: JSON.stringify({ name, email, password, password_confirmation, dni })
      });

      const data = await response.json();
      if (!response.ok || !data.token || !data.user) {
        alert(data.message || 'No se pudo crear la cuenta.');
        return;
      }

      setAuthData(data.token, data.user);
      alert('Cuenta creada correctamente.');
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
      if (cart.length === 0) {
        alert('Tu carrito está vacío.');
        return;
      }

      const missingVariant = cart.some(item => !item.variantId);
      if (missingVariant) {
        alert('Hay productos antiguos en tu carrito sin variante válida. Elimínalos y vuelve a agregarlos.');
        return;
      }

      const deliveryType = document.querySelector('input[name="tenant-pro-delivery-type"]:checked')?.value || 'pickup';
      const deliveryAddressResult = buildShippingAddress(
        proShippingCountrySelect,
        proShippingStateSelect,
        proShippingCitySelect,
        proShippingAddressDetailInput
      );

      if (deliveryType === 'shipping' && !deliveryAddressResult.valid) {
        alert(deliveryAddressResult.message);
        return;
      }

      const paymentRows = Array.from(document.querySelectorAll('[data-pro-payment-row]'));
      const payments = (await Promise.all(paymentRows.map(async row => {
        const methodId = Number(row.querySelector('.pro-payment-method')?.value || 0);
        const amountRaw = Number(row.querySelector('.pro-payment-amount')?.value || 0);
        const method = getMethodById(methodId);
        const amount = toUsdFromMethodAmount(method, amountRaw);
        const reference = (row.querySelector('.pro-payment-reference')?.value || '').trim();
        const imageFile = row.querySelector('.pro-payment-reference-image')?.files?.[0] || null;
        const referenceImageData = imageFile ? await fileToDataUrl(imageFile) : null;

        return {
          method_id: methodId,
          amount,
          reference,
          reference_image_data: referenceImageData,
          reference_image_mime: imageFile?.type || null,
        };
      }))).filter(payment => payment.method_id > 0 && payment.amount > 0);

      if (payments.length === 0) {
        alert('Debes agregar al menos un pago válido.');
        return;
      }

      const hasMissingReference = payments.some(payment => !String(payment.reference || '').trim());
      if (hasMissingReference) {
        alert('Cada pago debe incluir una referencia.');
        return;
      }

      const hasMissingProofImage = payments.some(payment => !String(payment.reference_image_data || '').trim());
      if (hasMissingProofImage) {
        alert('Cada pago debe incluir una imagen de comprobante.');
        return;
      }

      const totalPaidUsd = payments.reduce((sum, payment) => sum + Number(payment.amount || 0), 0);
      const totalOrderUsd = getSubtotal(cart);
      if (totalPaidUsd + 0.0001 < totalOrderUsd) {
        const remainingUsd = totalOrderUsd - totalPaidUsd;
        alert(`Falta por pagar: ${remainingUsd.toFixed(2)} $ / ${(remainingUsd * proDollarRate).toFixed(2)} Bs`);
        return;
      }

      const items = cart.map(item => ({
        variant_id: Number(item.variantId),
        quantity: Number(item.qty),
        unit_price: Number(item.price),
      }));

      let response;
      setProSubmitLoading(true);
      try {
        response = await fetch(`/${tenantSlug}/checkout/pro`, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'Authorization': `Bearer ${token}`,
            'X-CSRF-TOKEN': csrfToken,
          },
          body: JSON.stringify({
            customer_id: Number(user.id),
            delivery_type: deliveryType,
            delivery_address: deliveryType === 'shipping' ? deliveryAddressResult.address : 'Tienda',
            items,
            payments,
            mark_delivered: false,
            mark_payments_paid: false,
            mark_sale_completed: false,
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
        alert(data.message || data.error || 'No se pudo completar el pedido.');
        setProSubmitLoading(false);
        return;
      }

      alert(data.message || 'Pedido realizado correctamente.');
      saveCart([]);

      const modalElement = document.getElementById('tenantProCheckoutModal');
      const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
      modal.hide();

      if (data.order_id) {
        window.location.href = `/publicOrder/${data.order_id}`;
      }

      setProSubmitLoading(false);
    }

    document.addEventListener('click', event => {
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

    deliveryTypeInputs.forEach(input => {
      input.addEventListener('change', updateDeliveryAddressVisibility);
    });

    checkoutButton.addEventListener('click', () => {
      closeTenantCartOffcanvas();

      if (cartEnabled) {
        openProCheckout();
      } else {
        checkoutByWhatsApp();
      }
    });

    document.getElementById('tenant-pro-login-form')?.addEventListener('submit', loginProCustomer);
    document.getElementById('tenant-pro-register-form')?.addEventListener('submit', registerProCustomer);

    if (cartEnabled) {
      document.querySelectorAll('input[name="tenant-pro-delivery-type"]').forEach(input => {
        input.addEventListener('change', () => {
          const isShipping = document.querySelector('input[name="tenant-pro-delivery-type"]:checked')?.value === 'shipping';
          document.getElementById('tenant-pro-shipping-address-container').classList.toggle('d-none', !isShipping);

          if (isShipping && proShippingCountrySelect && !proShippingCountrySelect.options.length) {
            initLocationSelectors(proShippingCountrySelect, proShippingStateSelect, proShippingCitySelect, {
              countryId: tenantCountryId,
              stateId: tenantStateId,
              cityId: tenantCityId,
            }).catch(() => {
              alert('No se pudieron cargar los selectores de ubicación de envío.');
            });
          }
        });
      });

      document.getElementById('tenant-pro-submit-order')?.addEventListener('click', submitProOrder);
    }

    updateDeliveryAddressVisibility();
    bindLocationSelectorEvents(shippingCountrySelect, shippingStateSelect, shippingCitySelect);
    bindLocationSelectorEvents(proShippingCountrySelect, proShippingStateSelect, proShippingCitySelect);

    if (shippingCountrySelect) {
      initLocationSelectors(shippingCountrySelect, shippingStateSelect, shippingCitySelect, {
        countryId: tenantCountryId,
        stateId: tenantStateId,
        cityId: tenantCityId,
      }).catch(() => {
      });
    }

    if (proShippingCountrySelect) {
      initLocationSelectors(proShippingCountrySelect, proShippingStateSelect, proShippingCitySelect, {
        countryId: tenantCountryId,
        stateId: tenantStateId,
        cityId: tenantCityId,
      }).catch(() => {
      });
    }

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
