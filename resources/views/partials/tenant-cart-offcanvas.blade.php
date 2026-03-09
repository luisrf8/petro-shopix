<style>
  #tenantCartOffcanvas {
    --bs-offcanvas-zindex: 2000;
    z-index: 2000;
  }

  .offcanvas-backdrop {
    --bs-backdrop-zindex: 1990;
    z-index: 1990;
  }
</style>

<div class="offcanvas offcanvas-end" tabindex="-1" id="tenantCartOffcanvas" aria-labelledby="tenantCartOffcanvasLabel">
  <div class="offcanvas-header border-bottom">
    <h5 class="offcanvas-title" id="tenantCartOffcanvasLabel">Tu carrito</h5>
    <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
  </div>

  <div class="offcanvas-body d-flex flex-column">
    @if(!$cartEnabled)
      <div id="tenant-cart-disabled-alert" class="alert alert-warning" role="alert">
        Tu tienda está en plan básico. Puedes enviar tu pedido por WhatsApp.
      </div>
    @else
      <div id="tenant-cart-disabled-alert" class="alert alert-info" role="alert">
        Plan Pro activo: puedes completar el checkout con métodos de pago y tipo de entrega.
      </div>
    @endif

    <div id="tenant-cart-items" class="mb-3"></div>

    <div class="border-top pt-3 mt-auto">
      <div class="d-flex justify-content-between align-items-center mb-3">
        <span class="fw-semibold">Subtotal</span>
        <span class="fw-bold" id="tenant-cart-subtotal">0.00 $</span>
      </div>

      <div id="tenant-checkout-form">
        <div class="mb-3">
          <label for="tenant-customer-name" class="form-label">Nombre del cliente</label>
          <input type="text" class="form-control" id="tenant-customer-name" placeholder="Ej: María Pérez">
        </div>

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
          <label for="tenant-shipping-address" class="form-label">Dirección de envío</label>
          <textarea id="tenant-shipping-address" class="form-control" rows="2" placeholder="Escribe tu dirección completa"></textarea>
        </div>

        <button id="tenant-cart-checkout" type="button" class="btn btn-success w-100">
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

@if($cartEnabled)
<div class="modal fade" id="tenantProCheckoutModal" tabindex="-1" aria-labelledby="tenantProCheckoutModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="tenantProCheckoutModalLabel">Checkout Pro</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="tenant-pro-auth-section" class="mb-4">
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
            <label for="tenant-pro-shipping-address" class="form-label">Dirección de envío</label>
            <textarea id="tenant-pro-shipping-address" class="form-control" rows="2" placeholder="Dirección completa"></textarea>
          </div>

          <hr>
          <h6>Métodos de pago</h6>
          <div id="tenant-pro-payment-rows" class="d-flex flex-column gap-2"></div>
          <button type="button" id="tenant-pro-add-payment-row" class="btn btn-outline-dark btn-sm mt-2">+ Agregar pago</button>

          <div class="mt-3 d-flex justify-content-between">
            <strong>Total carrito</strong>
            <strong id="tenant-pro-total-amount">0.00 $</strong>
          </div>
          <div class="mt-1 d-flex justify-content-between">
            <span class="text-muted">Total carrito (Bs)</span>
            <span id="tenant-pro-total-amount-bs" class="text-muted">0.00 Bs</span>
          </div>
          <div class="mt-3 d-flex justify-content-between">
            <span class="fw-semibold">Pagado</span>
            <span id="tenant-pro-paid-amount">0.00 $</span>
          </div>
          <div class="mt-1 d-flex justify-content-between">
            <span class="text-muted">Pagado (Bs)</span>
            <span id="tenant-pro-paid-amount-bs" class="text-muted">0.00 Bs</span>
          </div>
          <div class="mt-3 d-flex justify-content-between">
            <strong>Restante</strong>
            <strong id="tenant-pro-remaining-amount">0.00 $</strong>
          </div>
          <div class="mt-1 d-flex justify-content-between">
            <span class="text-muted">Restante (Bs)</span>
            <span id="tenant-pro-remaining-amount-bs" class="text-muted">0.00 Bs</span>
          </div>
          <div class="small text-muted mt-1">Tasa referencial: <span id="tenant-pro-dollar-rate">0.00</span> Bs por USD</div>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cerrar</button>
        <button type="button" class="btn btn-success" id="tenant-pro-submit-order" disabled>Confirmar pedido</button>
      </div>
    </div>
  </div>
</div>
@endif

<script>
  (() => {
    const tenantSlug = @json($tenant->slug);
    const cartEnabled = @json((bool) ($cartEnabled ?? false));
    const tenantName = @json($tenant->name);
    const tenantPhoneCode = @json($tenant->phone_code ?? '');
    const tenantPhoneNumber = @json($tenant->phone_number ?? '');

    const storageKey = `shopix_cart_${tenantSlug}`;
    const cartCountElement = document.getElementById('tenant-cart-count');
    const cartItemsElement = document.getElementById('tenant-cart-items');
    const cartSubtotalElement = document.getElementById('tenant-cart-subtotal');
    const cartDisabledAlert = document.getElementById('tenant-cart-disabled-alert');
    const checkoutButton = document.getElementById('tenant-cart-checkout');
    const checkoutForm = document.getElementById('tenant-checkout-form');

    const shippingAddressContainer = document.getElementById('tenant-shipping-address-container');
    const shippingAddressInput = document.getElementById('tenant-shipping-address');
    const deliveryTypeInputs = document.querySelectorAll('input[name="tenant-delivery-type"]');
    const customerNameInput = document.getElementById('tenant-customer-name');

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

    function getCart() {
      try {
        const parsed = JSON.parse(localStorage.getItem(storageKey));
        return Array.isArray(parsed) ? parsed : [];
      } catch (error) {
        return [];
      }
    }

    function saveCart(cart) {
      localStorage.setItem(storageKey, JSON.stringify(cart));
      renderCart();
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
      shippingAddressContainer.classList.toggle('d-none', !isShipping);
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

    function renderCart() {
      const cart = getCart();
      const totalQty = getTotalQty(cart);
      const subtotal = getSubtotal(cart);

      cartCountElement.textContent = totalQty;
      cartSubtotalElement.textContent = `${subtotal.toFixed(2)} $`;

      checkoutButton.disabled = cart.length === 0;

      if (cart.length === 0) {
        cartItemsElement.innerHTML = '<p class="text-muted">No hay productos en el carrito.</p>';
        checkoutButton.disabled = true;
        return;
      }

      cartItemsElement.innerHTML = cart.map((item, index) => {
        return `
          <div class="border rounded-3 p-2 mb-2">
            <div class="d-flex justify-content-between gap-2 align-items-start">
              <div>
                <div class="fw-semibold">${item.productName}</div>
                <div class="small text-muted">Variante: ${item.variantSize}</div>
                <div class="small">${Number(item.price).toFixed(2)} $ c/u</div>
              </div>
              <button type="button" class="btn btn-sm btn-outline-danger" data-remove-index="${index}">
                <i class="bi bi-trash"></i>
              </button>
            </div>
            <div class="d-flex align-items-center justify-content-end gap-2 mt-2">
              <button type="button" class="btn btn-sm btn-outline-secondary" data-decrease-index="${index}">-</button>
              <span class="fw-semibold">${item.qty}</span>
              <button type="button" class="btn btn-sm btn-outline-secondary" data-increase-index="${index}">+</button>
            </div>
          </div>
        `;
      }).join('');
    }

    function addItem(item) {
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
      const shippingAddress = (shippingAddressInput.value || '').trim();
      const customerName = (customerNameInput.value || '').trim();

      if (isShipping && !shippingAddress) {
        alert('Indica la dirección de envío para completar el pedido.');
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
        lines.push(`Dirección de envío: ${shippingAddress}`);
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
    }

    function createPaymentRowHtml(methods, rowId) {
      const options = methods.map(method => `
        <option value="${method.id}" data-currency-code="${method.currency?.code || ''}" data-currency-name="${method.currency?.name || ''}">${method.name} (${method.currency?.code || method.currency?.name || 'USD'})</option>
      `).join('');

      return `
        <div class="border rounded p-2" data-pro-payment-row="${rowId}">
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
              <label class="form-label small mb-1">Referencia</label>
              <input type="text" class="form-control pro-payment-reference" placeholder="Opcional">
            </div>
            <div class="col-2 col-md-1 d-flex align-items-end">
              <button type="button" class="btn btn-outline-danger btn-sm w-100 pro-remove-payment-row">X</button>
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

    async function openProCheckout() {
      const cart = getCart();
      if (cart.length === 0) {
        alert('Tu carrito está vacío.');
        return;
      }

      const modalElement = document.getElementById('tenantProCheckoutModal');
      if (!modalElement) {
        alert('No se pudo abrir el checkout.');
        return;
      }

      const loggedUserAlert = document.getElementById('tenant-pro-logged-user');
      const authSection = document.getElementById('tenant-pro-auth-section');
      const checkoutSection = document.getElementById('tenant-pro-checkout-section');
      const submitOrderButton = document.getElementById('tenant-pro-submit-order');
      const totalAmountElement = document.getElementById('tenant-pro-total-amount');
      const paymentRowsContainer = document.getElementById('tenant-pro-payment-rows');
      const addPaymentRowButton = document.getElementById('tenant-pro-add-payment-row');

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

      paymentRowsContainer.oninput = () => {
        updateProPaymentSummary();
      };

      paymentRowsContainer.onchange = () => {
        updateProPaymentSummary();
      };

      totalAmountElement.textContent = `${getSubtotal(cart).toFixed(2)} $`;
      updateProPaymentSummary();

      const token = getAuthToken();
      const user = getAuthUser();
      const isLogged = !!token && !!user?.id;

      if (isLogged) {
        loggedUserAlert.classList.remove('d-none');
        loggedUserAlert.textContent = `Sesión activa: ${user.name} (${user.email})`;
        authSection.classList.add('d-none');
        checkoutSection.classList.remove('d-none');
        submitOrderButton.disabled = false;
      } else {
        loggedUserAlert.classList.add('d-none');
        authSection.classList.remove('d-none');
        checkoutSection.classList.add('d-none');
        submitOrderButton.disabled = true;
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
      openProCheckout();
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
      openProCheckout();
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
      const deliveryAddress = (document.getElementById('tenant-pro-shipping-address').value || '').trim();

      if (deliveryType === 'shipping' && !deliveryAddress) {
        alert('Indica la dirección de envío.');
        return;
      }

      const paymentRows = Array.from(document.querySelectorAll('[data-pro-payment-row]'));
      const payments = paymentRows.map(row => {
        const methodId = Number(row.querySelector('.pro-payment-method')?.value || 0);
        const amountRaw = Number(row.querySelector('.pro-payment-amount')?.value || 0);
        const method = getMethodById(methodId);
        const amount = toUsdFromMethodAmount(method, amountRaw);
        const reference = (row.querySelector('.pro-payment-reference')?.value || '').trim();

        return { method_id: methodId, amount, reference };
      }).filter(payment => payment.method_id > 0 && payment.amount > 0);

      if (payments.length === 0) {
        alert('Debes agregar al menos un pago válido.');
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

      const response = await fetch(`/${tenantSlug}/checkout/pro`, {
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
          delivery_address: deliveryType === 'shipping' ? deliveryAddress : 'Tienda',
          items,
          payments,
        })
      });

      const data = await response.json();
      if (!response.ok) {
        alert(data.message || data.error || 'No se pudo completar el pedido.');
        return;
      }

      alert(data.message || 'Pedido realizado correctamente.');
      saveCart([]);

      const modalElement = document.getElementById('tenantProCheckoutModal');
      const modal = bootstrap.Modal.getOrCreateInstance(modalElement);
      modal.hide();
    }

    document.addEventListener('click', event => {
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

    if (cartEnabled) {
      document.getElementById('tenant-pro-login-form')?.addEventListener('submit', loginProCustomer);
      document.getElementById('tenant-pro-register-form')?.addEventListener('submit', registerProCustomer);

      document.querySelectorAll('input[name="tenant-pro-delivery-type"]').forEach(input => {
        input.addEventListener('change', () => {
          const isShipping = document.querySelector('input[name="tenant-pro-delivery-type"]:checked')?.value === 'shipping';
          document.getElementById('tenant-pro-shipping-address-container').classList.toggle('d-none', !isShipping);
        });
      });

      document.getElementById('tenant-pro-submit-order')?.addEventListener('click', submitProOrder);
    }

    window.ShopixCart = {
      addItem,
      open: () => {
        const canvasElement = document.getElementById('tenantCartOffcanvas');
        if (!canvasElement) {
          return;
        }

        const offcanvas = bootstrap.Offcanvas.getOrCreateInstance(canvasElement);
        offcanvas.show();
      }
    };

    updateDeliveryAddressVisibility();
    renderCart();
  })();
</script>
