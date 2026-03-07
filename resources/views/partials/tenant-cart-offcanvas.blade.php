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
    <div id="tenant-cart-disabled-alert" class="alert alert-warning d-none" role="alert">
      El carrito está disponible para tiendas con plan Pro.
      @if(!empty($cartPlanName))
        Plan actual: <strong>{{ $cartPlanName }}</strong>.
      @endif
    </div>

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
          <i class="bi bi-whatsapp me-2"></i>Realizar pedido
        </button>
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

    function renderCart() {
      const cart = getCart();
      const totalQty = getTotalQty(cart);
      const subtotal = getSubtotal(cart);

      cartCountElement.textContent = totalQty;
      cartSubtotalElement.textContent = `${subtotal.toFixed(2)} $`;

      if (!cartEnabled) {
        cartDisabledAlert.classList.remove('d-none');
        checkoutButton.disabled = true;
        checkoutForm.classList.add('opacity-50');
      } else {
        cartDisabledAlert.classList.add('d-none');
        checkoutButton.disabled = cart.length === 0;
        checkoutForm.classList.remove('opacity-50');
      }

      if (cart.length === 0) {
        cartItemsElement.innerHTML = '<p class="text-muted">No hay productos en el carrito.</p>';
        if (cartEnabled) {
          checkoutButton.disabled = true;
        }
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
      if (!cartEnabled) {
        alert('El carrito solo está disponible para tiendas con plan Pro.');
        return;
      }

      const cart = getCart();
      const existingIndex = cart.findIndex(cartItem => (
        Number(cartItem.productId) === Number(item.productId) && cartItem.variantSize === item.variantSize
      ));

      if (existingIndex >= 0) {
        cart[existingIndex].qty += Number(item.qty || 1);
      } else {
        cart.push({
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
      if (!cartEnabled) {
        alert('El carrito solo está disponible para tiendas con plan Pro.');
        return;
      }

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

    checkoutButton.addEventListener('click', checkoutByWhatsApp);

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
