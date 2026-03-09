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

<li class="nav-item d-none" id="tenant-session-indicator-wrap">
  <span class="btn btn-light text-dark landing-nav-link d-inline-flex align-items-center gap-2">
    <i class="bi bi-person-circle"></i>
    <span id="tenant-session-indicator">Sesión iniciada</span>
  </span>
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

<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<div class="toast-container position-fixed top-0 end-0 p-3" id="tenant-toast-container" style="z-index: 3000;"></div>

<script>
  (() => {
    const indicatorWrap = document.getElementById('tenant-session-indicator-wrap');
    const indicatorText = document.getElementById('tenant-session-indicator');
    const logoutWrap = document.getElementById('tenant-session-logout-wrap');
    const logoutButton = document.getElementById('tenant-session-logout');
    const notificationsWrap = document.getElementById('tenant-notifications-wrap');
    const notificationsCount = document.getElementById('tenant-notifications-count');
    const notificationsList = document.getElementById('tenant-notifications-list');
    const notificationsBtn = document.getElementById('tenant-notifications-btn');
    const notificationsModal = document.getElementById('tenantNotificationsModal');
    let tenantToastContainer = document.getElementById('tenant-toast-container');

    if (notificationsModal && notificationsModal.parentElement !== document.body) {
      document.body.appendChild(notificationsModal);
    }

    if (!tenantToastContainer) {
      tenantToastContainer = document.createElement('div');
      tenantToastContainer.id = 'tenant-toast-container';
      tenantToastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
      tenantToastContainer.style.zIndex = '5000';
      document.body.appendChild(tenantToastContainer);
    } else {
      tenantToastContainer.className = 'toast-container position-fixed top-0 end-0 p-3';
      tenantToastContainer.style.zIndex = '5000';
      if (tenantToastContainer.parentElement !== document.body) {
        document.body.appendChild(tenantToastContainer);
      }
    }

    if (!indicatorWrap || !indicatorText || !logoutWrap || !logoutButton || !notificationsWrap || !notificationsCount || !notificationsList) {
      return;
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

        return `
          <div class="border rounded p-2 ${unreadClass}">
            <div class="d-flex justify-content-between align-items-start gap-2">
              <div>
                <div class="fw-semibold">${row.title || 'Notificación'}</div>
                <div class="small text-muted">${row.message || ''}</div>
                <div class="small text-secondary mt-1">${row.created_at || ''}</div>
              </div>
              <div>${actionButton}</div>
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
    }

    try {
      const user = JSON.parse(localStorage.getItem('shopix_ecomm_user') || 'null');
      const token = localStorage.getItem('shopix_ecomm_token') || '';

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

          pusher.connection.bind('error', () => {
          });
        }

        indicatorText.textContent = `Hola, ${user.name}`;
        indicatorWrap.classList.remove('d-none');
        logoutWrap.classList.remove('d-none');
        notificationsWrap.classList.remove('d-none');

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
      indicatorWrap.classList.add('d-none');
      logoutWrap.classList.add('d-none');
      notificationsWrap.classList.add('d-none');
    }

    logoutButton.addEventListener('click', () => {
      localStorage.removeItem('shopix_ecomm_token');
      localStorage.removeItem('shopix_ecomm_user');
      window.location.reload();
    });
  })();
</script>
