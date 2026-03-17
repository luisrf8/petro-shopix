<style>
  .tenant-order-card {
    border: 1px solid #e8eaed;
    border-radius: 12px;
    padding: 0.9rem;
    background: #fff;
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
</style>

<li class="nav-item">
  <button type="button"
          id="cart-toggle-button"
          class="btn btn-light text-dark landing-nav-link d-inline-flex align-items-center gap-2"
          data-bs-toggle="offcanvas"
          data-bs-target="#tenantCartOffcanvas"
          aria-controls="tenantCartOffcanvas">
    <i class="bi bi-cart3"></i>
    <span>Carrito</span>
    <span class="badge rounded-pill bg-dark" id="tenant-cart-count">0</span>
  </button>
</li>

<li class="nav-item" id="tenant-session-login-wrap">
  <button type="button"
          id="tenant-session-login"
          class="btn btn-light text-dark landing-nav-link d-inline-flex align-items-center gap-2">
    <i class="bi bi-box-arrow-in-right"></i>
    <span>Iniciar sesión</span>
  </button>
</li>

<li class="nav-item d-none" id="tenant-session-indicator-wrap">
  <span class="btn btn-light text-dark landing-nav-link d-inline-flex align-items-center gap-2">
    <i class="bi bi-person-circle"></i>
    <span id="tenant-session-indicator">Sesión iniciada</span>
  </span>
</li>

<li class="nav-item d-none" id="tenant-orders-wrap">
  <button type="button"
          id="tenant-orders-btn"
          class="btn btn-light text-dark landing-nav-link d-inline-flex align-items-center gap-2">
    <i class="bi bi-bag-check"></i>
    <span>Estado de compras</span>
  </button>
</li>

<li class="nav-item d-none" id="tenant-session-logout-wrap">
  <button type="button"
          id="tenant-session-logout"
          class="btn btn-outline-light text-dark landing-nav-link d-inline-flex align-items-center gap-2">
    <i class="bi bi-box-arrow-right"></i>
    <span>Cerrar sesión</span>
  </button>
</li>

<li class="nav-item d-none" id="tenant-notifications-wrap">
  <button type="button"
          id="tenant-notifications-btn"
          class="btn btn-light text-dark landing-nav-link d-inline-flex align-items-center gap-2"
          data-bs-toggle="modal"
          data-bs-target="#tenantNotificationsModal">
    <i class="bi bi-bell"></i>
    <span>Notificaciones</span>
    <span class="badge rounded-pill bg-danger d-none" id="tenant-notifications-count">0</span>
  </button>
</li>

<div class="modal fade" id="tenantNotificationsModal" tabindex="-1" aria-labelledby="tenantNotificationsModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="tenantNotificationsModalLabel">Notificaciones</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
        <div id="tenant-notifications-list" class="d-flex flex-column gap-2"></div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="tenantOrdersModal" tabindex="-1" aria-labelledby="tenantOrdersModalLabel" aria-hidden="true">
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

<div class="modal fade" id="tenantAuthModal" tabindex="-1" aria-labelledby="tenantAuthModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="tenantAuthModalLabel">Iniciar sesión</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body">
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
                <input type="email" class="form-control" id="tenant-public-login-email" placeholder="Email" required>
              </div>
              <div class="col-12">
                <input type="password" class="form-control" id="tenant-public-login-password" placeholder="Contraseña" required>
              </div>
              <div class="col-12">
                <button type="submit" class="btn btn-dark w-100">Entrar</button>
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
                <input type="password" class="form-control" id="tenant-public-register-password" placeholder="Contraseña" minlength="8" required>
              </div>
              <div class="col-12">
                <input type="password" class="form-control" id="tenant-public-register-password-confirmation" placeholder="Confirmar contraseña" minlength="8" required>
              </div>
              <div class="col-12">
                <input type="text" class="form-control" id="tenant-public-register-dni" placeholder="DNI (opcional)">
              </div>
              <div class="col-12">
                <button type="submit" class="btn btn-dark w-100">Crear cuenta</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<div class="toast-container position-fixed top-0 end-0 p-3" id="tenant-toast-container" style="z-index: 3000;"></div>

<script>
  (() => {
    const loginWrap = document.getElementById('tenant-session-login-wrap');
    const loginButton = document.getElementById('tenant-session-login');
    const indicatorWrap = document.getElementById('tenant-session-indicator-wrap');
    const indicatorText = document.getElementById('tenant-session-indicator');
    const logoutWrap = document.getElementById('tenant-session-logout-wrap');
    const logoutButton = document.getElementById('tenant-session-logout');
    const ordersWrap = document.getElementById('tenant-orders-wrap');
    const ordersButton = document.getElementById('tenant-orders-btn');
    const ordersList = document.getElementById('tenant-orders-list');
    const ordersModal = document.getElementById('tenantOrdersModal');
    const authModal = document.getElementById('tenantAuthModal');
    const authModalLabel = document.getElementById('tenantAuthModalLabel');
    const notificationsWrap = document.getElementById('tenant-notifications-wrap');
    const notificationsCount = document.getElementById('tenant-notifications-count');
    const notificationsList = document.getElementById('tenant-notifications-list');
    const notificationsBtn = document.getElementById('tenant-notifications-btn');
    const notificationsModal = document.getElementById('tenantNotificationsModal');
    let tenantToastContainer = document.getElementById('tenant-toast-container');

    if (notificationsModal && notificationsModal.parentElement !== document.body) {
      document.body.appendChild(notificationsModal);
    }

    if (ordersModal && ordersModal.parentElement !== document.body) {
      document.body.appendChild(ordersModal);
    }

    if (authModal && authModal.parentElement !== document.body) {
      document.body.appendChild(authModal);
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

    if (!loginWrap || !loginButton || !indicatorWrap || !indicatorText || !logoutWrap || !logoutButton || !ordersWrap || !ordersButton || !ordersList || !notificationsWrap || !notificationsCount || !notificationsList) {
      return;
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

    async function submitTenantPublicLogin(event) {
      event.preventDefault();

      const email = document.getElementById('tenant-public-login-email')?.value.trim() || '';
      const password = document.getElementById('tenant-public-login-password')?.value || '';

      const response = await fetch('/api/loginEcomm', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Accept': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
        body: JSON.stringify({ email, password })
      });

      const data = await response.json().catch(() => ({}));
      if (!response.ok || !data.token || !data.user) {
        alert(data.message || 'No se pudo iniciar sesión.');
        return;
      }

      persistTenantAuth(data.token, data.user);
      bootstrap.Modal.getInstance(authModal)?.hide();
    }

    async function submitTenantPublicRegister(event) {
      event.preventDefault();

      const payload = {
        name: document.getElementById('tenant-public-register-name')?.value.trim() || '',
        email: document.getElementById('tenant-public-register-email')?.value.trim() || '',
        password: document.getElementById('tenant-public-register-password')?.value || '',
        password_confirmation: document.getElementById('tenant-public-register-password-confirmation')?.value || '',
        dni: document.getElementById('tenant-public-register-dni')?.value.trim() || '',
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
        alert(data.message || 'No se pudo crear la cuenta.');
        return;
      }

      persistTenantAuth(data.token, data.user);
      bootstrap.Modal.getInstance(authModal)?.hide();
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
          ? `<a href="${row.target_url}" class="btn btn-sm btn-dark"${row.is_read ? '' : ` data-mark-read-link="${row.id}"`}>Abrir</a>`
          : '';

        return `
          <div class="border rounded p-2 ${unreadClass}">
            <div class="d-flex justify-content-between align-items-start gap-2">
              <div>
                <div class="fw-semibold">${row.title || 'Notificación'}</div>
                <div class="small text-muted">${row.message || ''}</div>
                <div class="small text-secondary mt-1">${row.created_at || ''}</div>
              </div>
              <div class="d-flex flex-column gap-1 align-items-end">${openButton}${actionButton}</div>
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
      if (Number(status) === 2) return 'Cancelado';
      return 'Pendiente';
    }

    function deliveryStatusClass(status) {
      if (Number(status) === 1) return 'text-bg-success';
      if (Number(status) === 2) return 'text-bg-danger';
      return 'text-bg-secondary';
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
              <div class="tenant-order-meta">${row.tenant_name || 'Tienda'} ${row.date ? `• ${row.date}` : ''}</div>
            </div>
            <a href="${row.public_url}" class="btn btn-sm btn-outline-dark">Ver detalle</a>
          </div>

          <div class="tenant-order-meta mb-1"><strong>${row.items_count || 0}</strong> item(s) • <strong>${Number(row.total || 0).toFixed(2)} $</strong></div>
          <div class="tenant-order-meta mb-2">${row.preference || 'No definida'}${row.address ? ` • ${row.address}` : ''}</div>

          <div class="tenant-order-status-group">
            <span class="badge tenant-order-badge ${orderStatusClass(row.status)}">Pedido: ${orderStatusLabel(row.status)}</span>
            <span class="badge tenant-order-badge ${deliveryStatusClass(row.deliver_status)}">Entrega: ${deliveryStatusLabel(row.deliver_status)}</span>
          </div>
        </article>
      `).join('');
    }

    function applyAuthState(user, token) {
      currentUser = user || null;
      currentToken = token || '';

      const hasSession = !!currentToken && !!currentUser?.id;

      loginWrap.classList.toggle('d-none', hasSession);
      indicatorWrap.classList.toggle('d-none', !hasSession);
      logoutWrap.classList.toggle('d-none', !hasSession);
      ordersWrap.classList.toggle('d-none', !hasSession);
      notificationsWrap.classList.toggle('d-none', !hasSession);

      if (hasSession) {
        indicatorText.textContent = `Hola, ${currentUser.name || 'Usuario'}`;
      } else {
        indicatorText.textContent = 'Sesión iniciada';
        notificationsCount.textContent = '0';
        notificationsCount.classList.add('d-none');
      }
    }

    let currentUser = null;
    let currentToken = '';

    try {
      const user = JSON.parse(localStorage.getItem('shopix_ecomm_user') || 'null');
      const token = localStorage.getItem('shopix_ecomm_token') || '';
      applyAuthState(user, token);

      if (user && user.name && token) {
        function incrementBadge() {
          const current = Number(notificationsCount.textContent || 0) + 1;
          notificationsCount.textContent = String(current);
          notificationsCount.classList.toggle('d-none', current <= 0);
        }

        function bindRealtimeChannel() {
          const pusherKey = @json(env('PUSHER_APP_KEY'));
          if (!pusherKey) {
            return;
          }

          const pusher = new Pusher(pusherKey, {
            cluster: @json(env('PUSHER_APP_CLUSTER')),
            wsHost: @json(env('PUSHER_HOST', '127.0.0.1')),
            wsPort: Number(@json(env('PUSHER_PORT', 6001))),
            wssPort: Number(@json(env('PUSHER_PORT', 6001))),
            forceTLS: @json(env('PUSHER_SCHEME', 'http')) === 'https',
            enabledTransports: ['ws', 'wss'],
            authEndpoint: '/api/broadcasting/auth',
            auth: {
              headers: {
                'Authorization': `Bearer ${token}`,
                'Accept': 'application/json',
              },
            },
          });

          const channel = pusher.subscribe(`private-App.Models.User.${user.id}`);
          const handleIncoming = async (notification) => {
            showTenantToast(notification.title || 'Notificación', notification.message || '');
            incrementBadge();

            try {
              const payload = await fetchNotifications(token);
              renderNotifications(payload, token);
            } catch (error) {
            }
          };

          channel.bind('Illuminate\\Notifications\\Events\\BroadcastNotificationCreated', handleIncoming);
          channel.bind('.Illuminate\\Notifications\\Events\\BroadcastNotificationCreated', handleIncoming);
          pusher.connection.bind('error', () => {});
        }
        fetchNotifications(token)
          .then(payload => renderNotifications(payload, token))
          .catch(() => {
            notificationsList.innerHTML = '<p class="text-danger mb-0">No se pudieron cargar notificaciones.</p>';
          });

        bindRealtimeChannel();

        notificationsBtn?.addEventListener('click', async () => {
          try {
            const payload = await fetchNotifications(token);
            renderNotifications(payload, token);
          } catch (error) {
            notificationsList.innerHTML = '<p class="text-danger mb-0">No se pudieron cargar notificaciones.</p>';
          }
        });

      }
    } catch (error) {
      applyAuthState(null, '');
    }

    window.addEventListener('shopix-auth-changed', (event) => {
      const user = event.detail?.user || null;
      const token = event.detail?.token || '';
      applyAuthState(user, token);
    });

    document.getElementById('tenant-public-login-form')?.addEventListener('submit', submitTenantPublicLogin);
    document.getElementById('tenant-public-register-form')?.addEventListener('submit', submitTenantPublicRegister);

    ordersButton?.addEventListener('click', async () => {
      const hasSession = !!currentToken && !!currentUser?.id;
      if (!hasSession) {
        if (openTenantAuthModal()) {
          return;
        }

        alert('No se pudo abrir el inicio de sesión en este momento.');
        return;
      }

      ordersList.innerHTML = '<p class="text-muted mb-0">Cargando compras...</p>';
      const ordersModalInstance = bootstrap.Modal.getOrCreateInstance(document.getElementById('tenantOrdersModal'));
      ordersModalInstance.show();

      try {
        const payload = await fetchOrders(currentToken);
        renderOrders(payload);
      } catch (error) {
        ordersList.innerHTML = '<p class="text-danger mb-0">No se pudieron cargar las compras.</p>';
      }
    });

    loginButton.addEventListener('click', () => {
      if (openTenantAuthModal()) {
        return;
      }

      alert('No se pudo abrir el inicio de sesión en este momento. Recarga la página e inténtalo nuevamente.');
    });

    logoutButton.addEventListener('click', () => {
      localStorage.removeItem('shopix_ecomm_token');
      localStorage.removeItem('shopix_ecomm_user');
      window.dispatchEvent(new CustomEvent('shopix-auth-changed', {
        detail: {
          token: '',
          user: null,
        },
      }));
      window.location.reload();
    });
  })();
</script>
