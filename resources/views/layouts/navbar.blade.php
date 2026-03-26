<style>
#sidenav-main {
  transition: transform 0.3s ease-in-out;
}

#sidenav-main.closed {
  transform: translateX(-100%);
}

.mobile-sidenav-close {
  z-index: 1055;
  opacity: 1 !important;
  font-size: 1.1rem;
}

#sidenav-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.35);
  z-index: 1040;
  display: none;
}

#sidenav-backdrop.show {
  display: block;
}

#g-sidenav-show {
  transition: margin-left 0.3s ease-in-out;
}

/* Desktop */
@media (min-width: 992px) {
  .sidenav.fixed-start + .main-content {
    margin-left: 15rem;
    transition: margin 0.3s ease-in-out;
  }

  .sidenav.fixed-start.closed + .main-content {
    margin-left: 0; /* Quita margen cuando está cerrado */
  }

  .sidenav.fixed-end + .main-content {
    margin-right: 15rem;
    transition: margin 0.3s ease-in-out;
  }

  .sidenav.fixed-end.closed + .main-content {
    margin-right: 0; /* Quita margen cuando está cerrado */
  }
}

/* Móvil y tablet */
@media (max-width: 991px) {
  .sidenav.fixed-start + .main-content,
  .sidenav.fixed-end + .main-content {
    margin-left: 0 !important;
    margin-right: 0 !important;
  }
}

</style>
<body class="bg-gray-100">
    @php
      use App\Models\Tenant;
      use App\Models\TenantPlanPayment;
      use App\Models\User as UserModel;
      use App\Support\ImageStorage;

      $user = auth()->user();
      $roleName = strtolower((string) optional($user?->role)->name);
      $canonicalRole = UserModel::canonicalRoleName(optional($user?->role)->name);

      $isSuperAdmin = ((int) ($user->role_id ?? 0) === 4) || $canonicalRole === 'super_user';
      $isOwner = (bool) ($user?->isOwner() ?? false);
      $isAdmin = (bool) ($user?->isAdmin() ?? false);
      $isSeller = (bool) ($user?->hasStoreRole('seller') ?? false);
      $isWarehouse = (bool) ($user?->hasStoreRole('warehouse') ?? false);

      $canSeeCategories = $isOwner || $isAdmin || $isSeller;
      $canSeeProducts = $isOwner || $isAdmin || $isSeller || $isWarehouse;
      $canSell = $isOwner || $isAdmin || $isSeller;
      $canSeeSalesOrders = $isOwner || $isAdmin || $isSeller || $isWarehouse;
      $canInventoryEntries = $isOwner || $isAdmin || $isWarehouse;
      $canSeeWarehouses = $isOwner || $isAdmin || $isSeller || $isWarehouse;
      $canSeeMaterials = $isOwner || $isAdmin || $isWarehouse;
      $canManageStore = $isOwner || $isAdmin;

      $tenantLogo = null;
      $tenant = null;
      if ($user && $user->tenant_id) {
          $tenant = Tenant::find($user->tenant_id);

          if ($tenant && $tenant->logo) {
          $tenantLogo = ImageStorage::url($tenant->logo);
          }
      }

      $unreadNotificationsCount = $user ? $user->unreadNotifications()->count() : 0;

        $isFreePlanTenant = false;
          $isBasicPlanTenant = false;
        if (!$isSuperAdmin && $tenant) {
          $latestPaidPlan = TenantPlanPayment::with('plan')
            ->where('tenant_id', (int) $tenant->id)
            ->where('status', 'paid')
            ->orderByDesc('paid_at')
            ->orderByDesc('id')
            ->first();

          $isFreePlanTenant = (float) ($latestPaidPlan?->plan?->price ?? -1) <= 0;
            $planName = strtolower((string) \Illuminate\Support\Str::ascii((string) ($latestPaidPlan?->plan?->name ?? '')));
            $isBasicPlanTenant = strpos($planName, 'basico') !== false || strpos($planName, 'basic') !== false;
        }

        $hasFreePlanRestriction = !$isSuperAdmin && $isFreePlanTenant;
          $hasBasicPlanRestriction = !$isSuperAdmin && $isBasicPlanTenant;
    @endphp
    <div class="sidenav-header m-0 p-0 h-15 d-flex align-items-center justify-content-between px-2">
      <a class="navbar-brand d-flex justify-content-center align-items-center m-0" href="/dashboard">
        <img src="{{ $tenantLogo ?? asset('assets/img/shopix5.png') }}"
            class="navbar-brand-img"
            width="100"
            height="100"
            alt="main_logo"
            style="object-fit: contain;">
      </a>
      <button type="button" class="btn d-xl-none m-0" id="iconSidenav" aria-label="Cerrar menú">
        <i class="fas fa-times"></i>X
      </button>
    </div>
    <hr class="horizontal dark mt-0 mb-2">
    <style>
      .sidebar-section-title {
        display: flex;
        align-items: center;
        gap: 0.35rem;
        margin: 0.5rem 0 0.2rem;
        padding: 0.35rem 0.65rem;
        border-radius: 0.5rem;
        background: rgba(33, 37, 41, 0.08);
        font-size: 0.68rem;
        font-weight: 700;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        color: #212529;
      }
    </style>
    <div class="collapse navbar-collapse w-auto" id="sidenav-collapse-main">
      <ul class="navbar-nav">
      @if($canSeeCategories || $canSeeProducts || $canManageStore)
        @if(!$hasFreePlanRestriction && !$hasBasicPlanRestriction)
          <li class="nav-item">
            <a class="nav-link text-dark" href="/dashboard">
              <i class="material-symbols-rounded opacity-5">dashboard</i>
              <span class="nav-link-text ms-1">Administrador</span>
            </a>
          </li>
        @endif
        @if($canSeeCategories)
          <li class="nav-item">
            <a class="nav-link text-dark" href="/categories">
              <i class="material-symbols-rounded opacity-5">view_in_ar</i>
              <span class="nav-link-text ms-1">Categorías</span>
            </a>
          </li>
        @endif
        @if($canSeeProducts)
          <li class="nav-item">
            <a class="nav-link text-dark" href="/products">
              <i class="material-symbols-rounded opacity-5">table_view</i>
              <span class="nav-link-text ms-1">Productos</span>
            </a>
          </li>
        @endif
        @if($isOwner || $isAdmin)
          <li class="nav-item">
            <a class="nav-link text-dark" href="/paymentMethods">
              <i class="material-symbols-rounded opacity-5">view_in_ar</i>
              <span class="nav-link-text ms-1">Métodos de Pago</span>
            </a>
          </li>
        @endif
        @if($canManageStore)
          <li class="nav-item">
            <a class="nav-link text-dark" href="/tenant-store">
              <i class="material-symbols-rounded opacity-5">view_in_ar</i>
              <span class="nav-link-text ms-1">Gestión de Tienda</span>
            </a>
          </li>
        @endif
      @endif
        @if($canSell && !$hasFreePlanRestriction)

        <li class="nav-item">
          <a class="nav-link text-dark" href="/sales">
            <i class="material-symbols-rounded opacity-5">receipt_long</i>
            <span class="nav-link-text ms-1">Realizar Venta</span>
          </a>
        </li>
      @endif
        @if($canInventoryEntries && !$hasFreePlanRestriction)
          <li class="nav-item">
            <a class="nav-link text-dark" href="/purchase">
              <i class="material-symbols-rounded opacity-5">view_in_ar</i>
              <span class="nav-link-text ms-1">Entrada de Inventario</span>
            </a>
          </li>
        @endif

        @if($canSeeWarehouses && !$hasFreePlanRestriction)
          <li class="nav-item">
            <a class="nav-link text-dark" href="/warehouses">
              <i class="material-symbols-rounded opacity-5">warehouse</i>
              <span class="nav-link-text ms-1">Almacenes</span>
            </a>
          </li>
        @endif
        @if($canSeeMaterials && !$hasFreePlanRestriction)
          <li class="nav-item">
            <a class="nav-link text-dark" href="/materials">
              <i class="material-symbols-rounded opacity-5">inventory_2</i>
              <span class="nav-link-text ms-1">Lista de Materiales</span>
            </a>
          </li>
        @endif

        @if($canInventoryEntries && !$hasFreePlanRestriction)
          <li class="nav-item">
            <a class="nav-link text-dark" href="/purchase-orders">
              <i class="material-symbols-rounded opacity-5">format_textdirection_r_to_l</i>
              <span class="nav-link-text ms-1">Historial de Entradas</span>
            </a>
          </li>
        @endif

        @if($isWarehouse && !$hasFreePlanRestriction)
          <li class="nav-item">
            <a class="nav-link text-dark" href="/sales-orders/pending-delivery">
              <i class="material-symbols-rounded opacity-5">local_shipping</i>
              <span class="nav-link-text ms-1">Pedidos Pendientes</span>
            </a>
          </li>
        @endif

        @if($canSeeSalesOrders && !$isWarehouse && !$hasFreePlanRestriction)
          <li class="nav-item">
            <a class="nav-link text-dark" href="/sales-orders">
              <i class="material-symbols-rounded opacity-5">format_textdirection_r_to_l</i>
              <span class="nav-link-text ms-1">Ventas Realizadas</span>
            </a>
          </li>
        @endif

        @if(($canSeeSalesOrders || $canInventoryEntries) && !$hasFreePlanRestriction && !$hasBasicPlanRestriction)
          <li class="nav-item">
            <a class="nav-link text-dark" href="/reports">
              <i class="material-symbols-rounded opacity-5">summarize</i>
              <span class="nav-link-text ms-1">Reportes PDF</span>
            </a>
          </li>
        @endif

        @if($isSuperAdmin)
          <li class="nav-item">
            <a class="nav-link text-dark" href="/plans">
              <i class="material-symbols-rounded opacity-5">sell</i>
              <span class="nav-link-text ms-1">Planes</span>
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link text-dark" href="/tenant-payments#billing-overview-section">
              <i class="material-symbols-rounded opacity-5">event_upcoming</i>
              <span class="nav-link-text ms-1">Próximas de Pago</span>
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link text-dark" href="/tenant-payments#pending-payments-section">
              <i class="material-symbols-rounded opacity-5">request_quote</i>
              <span class="nav-link-text ms-1">Pagos de Tiendas</span>
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link text-dark" href="/taxes">
              <i class="material-symbols-rounded opacity-5">view_in_ar</i>
              <!-- <i class="bi bi-bag"></i> -->
              <span class="nav-link-text ms-1">Impuestos</span>
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link text-dark" href="/tenants">
              <i class="material-symbols-rounded opacity-5">format_textdirection_r_to_l</i>
              <span class="nav-link-text ms-1">Tiendas</span>
            </a>
          </li>
                    <li class="nav-item">
            <a class="nav-link text-dark" href="/logs">
              <i class="material-symbols-rounded opacity-5">format_textdirection_r_to_l</i>
              <span class="nav-link-text ms-1">Logs</span>
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link text-dark" href="/users">
              <i class="material-symbols-rounded opacity-5">person</i>
              <span class="nav-link-text ms-1">Gestión de usuarios</span>
            </a>
          </li>
        @endif
        <li class="nav-item d-flex" onclick="logOut()">
          <a class="nav-link text-dark">
            <!-- <i class="bi bi-person-circle"></i> -->
            <i class="material-symbols-rounded opacity-5">supervised_user_circle</i>
            <span class="nav-link-text ms-1">Cerrar Sesión</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-dark d-flex justify-content-between align-items-center" href="{{ route('notifications.index') }}">
            <span>
              <i class="material-symbols-rounded opacity-5">notifications</i>
              <span class="nav-link-text ms-1">Notificaciones</span>
            </span>
            @if($unreadNotificationsCount > 0)
              <span class="badge bg-danger" id="backoffice-notifications-count">{{ $unreadNotificationsCount }}</span>
            @else
              <span class="badge bg-danger d-none" id="backoffice-notifications-count">0</span>
            @endif
          </a>
        </li>
      </ul>
    </div>
    <div class="sidenav-footer position-absolute w-100 bottom-0 ">
      <div class="mx-3">
      </div>
    </div>
<!-- Core JS Files -->
<script src="{{ asset('assets/js/core/popper.min.js') }}"></script>
<script src="{{ asset('assets/js/core/bootstrap.min.js') }}"></script>
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<!-- Github buttons -->
<script async defer src="https://buttons.github.io/buttons.js"></script>
<div class="toast-container position-fixed top-0 end-0 p-3" id="backoffice-toast-container" style="z-index: 3000;"></div>

<!-- Control Center for Material Dashboard: parallax effects, scripts for the example pages etc -->
<script src="{{ asset('assets/js/material-dashboard.min.js?v=3.2.0') }}"></script>

  <script>
  </script>
  <script>
    var win = navigator.platform.indexOf('Win') > -1;
    if (win && document.querySelector('#sidenav-scrollbar')) {
      var options = {
        damping: '0.5'
      }
      Scrollbar.init(document.querySelector('#sidenav-scrollbar'), options);
    }
  </script>
  <script>
    (() => {
      const userId = @json(optional(auth()->user())->id);
      if (!userId) return;

      const badge = document.getElementById('backoffice-notifications-count');
      const toastContainer = document.getElementById('backoffice-toast-container');
      function updateBadge(unread) {
        if (!badge) return;
        const current = Number(badge.textContent || 0);
        const count = typeof unread === 'number' ? unread : current + 1;
        badge.textContent = String(count);
        badge.classList.toggle('d-none', count <= 0);
      }

      function showToast(title, message) {
        if (!toastContainer) return;
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

        toastContainer.appendChild(toastEl);
        const toast = new bootstrap.Toast(toastEl, { delay: 5000 });
        toast.show();
        toastEl.addEventListener('hidden.bs.toast', () => toastEl.remove());
      }

      function bindNotificationChannel() {
        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
        const pusherKey = @json(env('PUSHER_APP_KEY'));
        if (!pusherKey) return;

        const pusher = new Pusher(pusherKey, {
          cluster: @json(env('PUSHER_APP_CLUSTER')),
          wsHost: @json(env('PUSHER_HOST', '127.0.0.1')),
          wsPort: Number(@json(env('PUSHER_PORT', 6001))),
          wssPort: Number(@json(env('PUSHER_PORT', 6001))),
          forceTLS: @json(env('PUSHER_SCHEME', 'http')) === 'https',
          enabledTransports: ['ws', 'wss'],
          authEndpoint: '/broadcasting/auth',
          auth: {
            headers: {
              'X-CSRF-TOKEN': csrf,
            },
          },
        });

        const channel = pusher.subscribe(`private-App.Models.User.${userId}`);
        const handleIncoming = (notification) => {
          const title = notification.title || 'Notificación';
          const message = notification.message || '';
          updateBadge();
          showToast(title, message);
        };

        channel.bind('Illuminate\\Notifications\\Events\\BroadcastNotificationCreated', handleIncoming);
        channel.bind('.Illuminate\\Notifications\\Events\\BroadcastNotificationCreated', handleIncoming);
      }

      async function loadInitialUnreadCount() {
        try {
          const response = await fetch('/notifications/feed', { headers: { 'Accept': 'application/json' } });
          if (!response.ok) return;

          const payload = await response.json();
          if (!payload.success) return;

          updateBadge(payload.unread_count || 0);
        } catch (error) {
        }
      }

      loadInitialUnreadCount();
      bindNotificationChannel();
    })();

    document.addEventListener("DOMContentLoaded", function () {
        const currentUrl = window.location.pathname; // Obtén la ruta actual sin el dominio
        const navLinks = document.querySelectorAll('.navbar-nav .nav-link'); // Selecciona los enlaces
      const sidenav = document.getElementById('sidenav-main');
      const iconSidenav = document.getElementById('iconSidenav');
      const btnOpenNav = document.getElementById('btnOpenNav');
      const btnCloseNav = document.getElementById('btnCloseNav');

        let backdrop = document.getElementById('sidenav-backdrop');
        if (!backdrop) {
          backdrop = document.createElement('div');
          backdrop.id = 'sidenav-backdrop';
          document.body.appendChild(backdrop);
        }

        function closeMobileSidenav() {
          if (!sidenav) return;
          sidenav.classList.add('closed');
          backdrop.classList.remove('show');

          if (btnOpenNav) {
            btnOpenNav.style.display = 'inline-block';
          }
          if (btnCloseNav) {
            btnCloseNav.style.display = 'none';
          }
        }

        function openMobileSidenav() {
          if (!sidenav) return;
          sidenav.classList.remove('closed');
          backdrop.classList.add('show');

          if (btnOpenNav) {
            btnOpenNav.style.display = 'none';
          }
          if (btnCloseNav) {
            btnCloseNav.style.display = 'inline-block';
          }
        }

        navLinks.forEach(link => {
            const linkHref = link.getAttribute('href');
            if (currentUrl === linkHref) { // Compara la ruta actual con el href
                link.classList.add("bg-gray-900", "text-white");
            } else {
                link.classList.remove("bg-gray-900", "text-white");
            }
        });
        if (iconSidenav && sidenav) {
          iconSidenav.addEventListener('click', function () {
            closeMobileSidenav();
          });
        }

        if (btnOpenNav) {
          btnOpenNav.addEventListener('click', function () {
            if (window.innerWidth < 992) {
              openMobileSidenav();
            }
          });
        }

        if (btnCloseNav) {
          btnCloseNav.addEventListener('click', function () {
            closeMobileSidenav();
          });
        }

        if (backdrop) {
          backdrop.addEventListener('click', function () {
            closeMobileSidenav();
          });
        }

        window.addEventListener('resize', function () {
          if (window.innerWidth >= 992) {
            backdrop.classList.remove('show');
          } else if (!sidenav.classList.contains('closed')) {
            backdrop.classList.add('show');
          }
        });

    });

  function logOut() {
    fetch("/api/logout", {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
        },
    })
    .then(response => {
        if (response.ok) {
            return response.json();
        } else {
            throw new Error('Logout failed');
        }
    })
    .then(data => {
        window.location.href = '/login';
    })
    .catch(error => {
        console.error("Error during logout:", error);
        alert("Ocurrió un error al cerrar sesión.");
    });
}
</script>
  <script async defer src="https://buttons.github.io/buttons.js"></script>
  <script src="{{ asset('assets/js/navbar.js') }}"></script>