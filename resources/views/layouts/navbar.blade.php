<style>
#sidenav-main {
  transition: transform 0.3s ease-in-out;
  height: 100vh;
  min-height: 100vh;
  margin-top: 0 !important;
  margin-bottom: 0 !important;
  border-radius: 0 !important;
  overflow: hidden;
}

#sidenav-main #sidenav-collapse-main {
  flex: 1 1 auto;
  min-height: 0;
  overflow-y: auto;
  overflow-x: hidden;
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

/* Keep top header visible while using a single browser scroll. */
.sidenav.fixed-start + .main-content #navbarBlur {
  position: fixed;
  top: 0;
  right: 0;
  left: 15rem;
  z-index: var(--z-header, 1040);
}

.sidenav.fixed-start.closed + .main-content #navbarBlur {
  left: 0;
}

.main-content {
  padding-top: 5.5rem;
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
  .sidenav.fixed-start + .main-content #navbarBlur,
  .sidenav.fixed-start.closed + .main-content #navbarBlur {
    left: 0;
  }

  .main-content {
    padding-top: 6rem;
  }

  #sidenav-main {
    height: 100vh;
    max-height: 100vh;
  }

  #sidenav-main #sidenav-collapse-main {
    max-height: calc(100vh - 7.5rem);
    overflow-y: auto;
    overflow-x: hidden;
    -webkit-overflow-scrolling: touch;
    padding-bottom: 1rem;
  }

  #sidenav-main .sidenav-footer {
    position: static !important;
  }

  .sidenav.fixed-start + .main-content,
  .sidenav.fixed-end + .main-content {
    margin-left: 0 !important;
    margin-right: 0 !important;
  }
}

</style>
    @php
      use App\Models\Tenant;
      use App\Models\User as UserModel;
      use App\Support\ImageStorage;
      use App\Support\TenantPlanCapabilities;

      $user = auth()->user();
      $roleName = strtolower((string) optional($user?->role)->name);
      $canonicalRole = UserModel::canonicalRoleName(optional($user?->role)->name);

      $isSuperAdmin = ((int) ($user->role_id ?? 0) === 4) || $canonicalRole === 'super_user';
      $isOwner = (bool) ($user?->isOwner() ?? false);
      $isAdmin = (bool) ($user?->isAdmin() ?? false);
      $isSeller = (bool) ($user?->hasStoreRole('seller') ?? false);
      $isWarehouse = (bool) ($user?->hasStoreRole('warehouse') ?? false);
      $isDelivery = (bool) ($user?->hasStoreRole('delivery') ?? false);

      $canSeeCategories = $isOwner || $isAdmin || $isSeller || $isWarehouse;
      $canSeeProducts = $isOwner || $isAdmin || $isSeller || $isWarehouse;
      $canSell = $isOwner || $isAdmin || $isSeller;
      $canSeeSalesOrders = $isOwner || $isAdmin || $isSeller;
      $canSeePendingOrders = $isOwner || $isAdmin || $isWarehouse;
      $canSeePaidPendingDeliveries = $isOwner || $isAdmin || $isSeller || $isWarehouse || $isDelivery;
      $canSeeTenantElectronicDocuments = $isOwner || $isAdmin || $isSeller;
      $canSeeCustomers = $isOwner || $isAdmin || $isSeller;
      $canSeeSellerCommissions = $isOwner || $isAdmin;
      $canSeeSellerCommissionProgress = $isSeller;
      $canSeeAccountsReceivable = $isOwner || $isAdmin;
      $canInventoryEntries = $isOwner || $isAdmin || $isWarehouse;
      $canSeeWarehouses = $isOwner || $isAdmin || $isSeller || $isWarehouse;
      $canSeeMaterials = $isOwner || $isAdmin || $isSeller || $isWarehouse;
      $canSeeAccountsPayable = $isOwner || $isAdmin || $isWarehouse;
      $canManageStore = $isOwner || $isAdmin;
      $canSeeReports = $isOwner || $isAdmin;
      $canSeeStoreExpenses = $isOwner || $isAdmin;

      $tenantLogo = null;
      $tenant = null;
      if ($user && $user->tenant_id) {
          $tenant = Tenant::find($user->tenant_id);

          if ($tenant && $tenant->logo) {
          $tenantLogo = ImageStorage::url($tenant->logo);
          }
      }

      $unreadNotificationsCount = $user ? $user->unreadNotifications()->count() : 0;

        $planCapabilities = TenantPlanCapabilities::forTenant($tenant, $isSuperAdmin);
        $hasFreePlanRestriction = $planCapabilities->hasFreeRestriction();
        $hasBasicPlanRestriction = $planCapabilities->hasBasicRestriction();

        $planCanDashboard = $planCapabilities->canDashboard();
        $planCanCategories = $planCapabilities->canCategories();
        $planCanProducts = $planCanCategories;
        $planCanStoreManagement = $planCapabilities->canStoreManagement();
        $planCanPaymentMethods = $planCapabilities->canPaymentMethods();
        $planCanSales = $planCapabilities->canSales();
        $planCanAppointments = $planCapabilities->canAppointments();
        $planCanCustomers = $planCapabilities->canCustomers();
        $planCanAccountsReceivable = $planCapabilities->canAccountsReceivable();
        $planCanPaidPendingDeliveries = $planCapabilities->canPaidPendingDeliveries();
        $planCanInventoryEntries = $planCapabilities->canInventoryEntries();
        $planCanProviders = $planCapabilities->canProviders();
        $planCanWarehouses = $planCapabilities->canWarehouses();
        $planCanMaterials = $planCapabilities->canMaterials();
        $planCanPurchaseHistory = $planCapabilities->canPurchaseHistory();
        $planCanPendingOrders = $planCapabilities->canPendingOrders();
        $planCanSalesOrders = $planCapabilities->canSalesOrders();
        $planCanElectronicDocuments = $planCapabilities->canElectronicDocuments();
        $planCanReports = $planCapabilities->canReports();
        $planCanStoreExpenses = $planCapabilities->canStoreExpenses();

        $tenantBusinessType = strtolower((string) ($tenant->business_type ?? ''));
        $isServiceStore = in_array($tenantBusinessType, ['servicio', 'service', 'services'], true);
        $tenantOffersProjects = (bool) ($tenant->offers_projects ?? true);
        $canSeeAppointments = $isOwner || $isAdmin || $isSeller;

        $showCatalogSection = ($planCanDashboard)
          || ($canSeeCategories && $planCanCategories)
          || ($canSeeProducts && $planCanProducts)
          || (($isOwner || $isAdmin) && $planCanPaymentMethods)
          || ($canManageStore && $planCanStoreManagement);

        $showSalesSection = ($canSell && $planCanSales)
          || ($canSeeAppointments && $planCanAppointments && $isServiceStore)
          || ($canSeeCustomers && $planCanCustomers)
          || ($canSeeSellerCommissionProgress && $planCanSales)
          || ($canSeeSellerCommissions && $planCanSales)
          || ($canSeeAccountsReceivable && $planCanAccountsReceivable)
          || ($canSeePaidPendingDeliveries && $planCanPaidPendingDeliveries)
          || ($canSeePendingOrders && $planCanPendingOrders)
          || ($canSeeSalesOrders && $planCanSalesOrders);

        $showInventorySection = ($canInventoryEntries && $planCanInventoryEntries)
          || ($canInventoryEntries && $planCanProviders)
          || ($canSeeWarehouses && $planCanWarehouses)
          || ($canSeeMaterials && $planCanMaterials)
          || ($canSeeAccountsPayable && $planCanPurchaseHistory)
          || ($canInventoryEntries && $planCanPurchaseHistory);

        $showManagementSection = ($canSeeTenantElectronicDocuments && $planCanElectronicDocuments)
          || ($canSeeReports && $planCanReports)
          || ($canSeeStoreExpenses && $planCanStoreExpenses)
          || $isSuperAdmin;
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
      @if($showCatalogSection)
        <li class="nav-item px-3 mt-1 mb-1">
          <span class="sidebar-section-title">Catálogo y Tienda</span>
        </li>
      @endif
      @if($canSeeCategories || $canSeeProducts || $canManageStore)
        @if($planCanDashboard && !$isSeller)
          <li class="nav-item">
            <a class="nav-link text-dark" href="/dashboard">
              <i class="material-symbols-rounded opacity-5">dashboard</i>
              <span class="nav-link-text ms-1">Administrador</span>
            </a>
          </li>
        @endif
        @if($canSeeCategories && $planCanCategories)
          <li class="nav-item">
            <a class="nav-link text-dark" href="/categories">
              <i class="material-symbols-rounded opacity-5">view_in_ar</i>
              <span class="nav-link-text ms-1">Categorías</span>
            </a>
          </li>
        @endif
        @if($canSeeProducts && $planCanProducts)
          <li class="nav-item">
            <a class="nav-link text-dark" href="/products">
              <i class="material-symbols-rounded opacity-5">table_view</i>
              <span class="nav-link-text ms-1">Productos</span>
            </a>
          </li>
        @endif
        @if(($isOwner || $isAdmin) && $planCanPaymentMethods)
          <li class="nav-item">
            <a class="nav-link text-dark" href="/paymentMethods">
              <i class="material-symbols-rounded opacity-5">view_in_ar</i>
              <span class="nav-link-text ms-1">Métodos de Pago</span>
            </a>
          </li>
        @endif
        @if($canManageStore && $planCanStoreManagement)
          <li class="nav-item">
            <a class="nav-link text-dark" href="/tenant-store">
              <i class="material-symbols-rounded opacity-5">view_in_ar</i>
              <span class="nav-link-text ms-1">Gestión de Tienda</span>
            </a>
          </li>
        @endif
      @endif

      @if($showSalesSection)
        <li class="nav-item px-3 mt-2 mb-1">
          <span class="sidebar-section-title">Ventas y Clientes</span>
        </li>
      @endif

        @if($canSell && $planCanSales)
          <li class="nav-item">
            <a class="nav-link text-dark" href="/sales">
              <i class="material-symbols-rounded opacity-5">receipt_long</i>
              <span class="nav-link-text ms-1">Realizar Venta</span>
            </a>
          </li>
        @endif

        @if($canSeeAppointments && $planCanAppointments && $isServiceStore)
          <li class="nav-item">
            <a class="nav-link text-dark" href="/appointments">
              <i class="material-symbols-rounded opacity-5">calendar_month</i>
              <span class="nav-link-text ms-1">Citas y Servicios</span>
            </a>
          </li>
        @endif

        @if($canSeeCustomers && $planCanCustomers)
          <li class="nav-item">
            <a class="nav-link text-dark" href="/customers">
              <i class="material-symbols-rounded opacity-5">groups</i>
              <span class="nav-link-text ms-1">Clientes</span>
            </a>
          </li>
        @endif

        @if($canSell && $planCanSales && !$isSeller && $tenantOffersProjects)
          <li class="nav-item">
            <a class="nav-link text-dark" href="/proyectos">
              <i class="material-symbols-rounded opacity-5">assignment</i>
              <span class="nav-link-text ms-1">Proyectos</span>
            </a>
          </li>
        @endif

        @if($canSell && $planCanSales)
          <li class="nav-item">
            <a class="nav-link text-dark" href="/cotizaciones">
              <i class="material-symbols-rounded opacity-5">description</i>
              <span class="nav-link-text ms-1">Cotizaciones</span>
            </a>
          </li>
        @endif

        @if($canSell && $planCanSales && !$isSeller)
          <li class="nav-item">
            <a class="nav-link text-dark" href="/nomina">
              <i class="material-symbols-rounded opacity-5">badge</i>
              <span class="nav-link-text ms-1">Nómina</span>
            </a>
          </li>
        @endif

        @if($canSeeSellerCommissionProgress && $planCanSales)
          <li class="nav-item">
            <a class="nav-link text-dark" href="{{ route('seller-commissions.progress') }}">
              <i class="material-symbols-rounded opacity-5">monitoring</i>
              <span class="nav-link-text ms-1">Administrador ventas</span>
            </a>
          </li>
        @endif

        @if($canSeeSellerCommissions && $planCanSales)
          <li class="nav-item">
            <a class="nav-link text-dark" href="/seller-commissions">
              <i class="material-symbols-rounded opacity-5">payments</i>
              <span class="nav-link-text ms-1">Comisiones</span>
            </a>
          </li>
        @endif

        @if($canSeeAccountsReceivable && $planCanAccountsReceivable)
          <li class="nav-item">
            <a class="nav-link text-dark" href="/accounts-receivable">
              <i class="material-symbols-rounded opacity-5">request_quote</i>
              <span class="nav-link-text ms-1">Cuentas por Cobrar</span>
            </a>
          </li>
        @endif

      @if($canSeePaidPendingDeliveries && $planCanPaidPendingDeliveries)
        <li class="nav-item">
          <a class="nav-link text-dark" href="/paid-pending-deliveries">
            <i class="material-symbols-rounded opacity-5">inventory</i>
            <span class="nav-link-text ms-1">Pagadas por Entregar</span>
          </a>
        </li>
      @endif

      @if($showInventorySection)
        <li class="nav-item px-3 mt-2 mb-1">
          <span class="sidebar-section-title">Inventario y Compras</span>
        </li>
      @endif

        @if($canInventoryEntries && $planCanInventoryEntries)
          <li class="nav-item">
            <a class="nav-link text-dark" href="/purchase">
              <i class="material-symbols-rounded opacity-5">view_in_ar</i>
              <span class="nav-link-text ms-1">Entrada de Inventario</span>
            </a>
          </li>
        @endif
        @if($canInventoryEntries && $planCanProviders)
          <li class="nav-item">
            <a class="nav-link text-dark" href="/providers">
              <i class="material-symbols-rounded opacity-5">local_shipping</i>
              <span class="nav-link-text ms-1">Proveedores</span>
            </a>
          </li>
        @endif

        @if($canSeeWarehouses && $planCanWarehouses)
          <li class="nav-item">
            <a class="nav-link text-dark" href="/warehouses">
              <i class="material-symbols-rounded opacity-5">warehouse</i>
              <span class="nav-link-text ms-1">Almacenes</span>
            </a>
          </li>
        @endif
        @if($canSeeMaterials && $planCanMaterials)
          <li class="nav-item">
            <a class="nav-link text-dark" href="/materials">
              <i class="material-symbols-rounded opacity-5">inventory_2</i>
              <span class="nav-link-text ms-1">Lista de Materiales</span>
            </a>
          </li>
        @endif

        @if($canInventoryEntries && $planCanPurchaseHistory)
          <li class="nav-item">
            <a class="nav-link text-dark" href="/purchase-orders">
              <i class="material-symbols-rounded opacity-5">format_textdirection_r_to_l</i>
              <span class="nav-link-text ms-1">Historial de Entradas</span>
            </a>
          </li>
        @endif

        @if($canSeeAccountsPayable && $planCanPurchaseHistory)
          <li class="nav-item">
            <a class="nav-link text-dark" href="/accounts-payable">
              <i class="material-symbols-rounded opacity-5">account_balance_wallet</i>
              <span class="nav-link-text ms-1">Cuentas por Pagar</span>
            </a>
          </li>
        @endif

        @if($canSeePendingOrders && $planCanPendingOrders)
          <li class="nav-item">
            <a class="nav-link text-dark" href="/sales-orders/pending-delivery">
              <i class="material-symbols-rounded opacity-5">local_shipping</i>
              <span class="nav-link-text ms-1">Pedidos Pendientes</span>
            </a>
          </li>
        @endif

        @if($canSeeSalesOrders && $planCanSalesOrders)
          <li class="nav-item">
            <a class="nav-link text-dark" href="/sales-orders">
              <i class="material-symbols-rounded opacity-5">format_textdirection_r_to_l</i>
              <span class="nav-link-text ms-1">Ventas Realizadas</span>
            </a>
          </li>
        @endif

      @if($showManagementSection)
        <li class="nav-item px-3 mt-2 mb-1">
          <span class="sidebar-section-title">Administración</span>
        </li>
      @endif

        @if($canSeeTenantElectronicDocuments && $planCanElectronicDocuments)
          <li class="nav-item">
            <a class="nav-link text-dark" href="/my-electronic-documents">
              <i class="material-symbols-rounded opacity-5">receipt_long</i>
              <span class="nav-link-text ms-1">Facturación Digital</span>
            </a>
          </li>
        @endif

        @if($canSeeReports && $planCanReports)
          <li class="nav-item">
            <a class="nav-link text-dark" href="/reports">
              <i class="material-symbols-rounded opacity-5">summarize</i>
              <span class="nav-link-text ms-1">Reportes PDF</span>
            </a>
          </li>
        @endif

        @if($canSeeStoreExpenses && $planCanStoreExpenses)
          <li class="nav-item">
            <a class="nav-link text-dark" href="/store-expenses">
              <i class="material-symbols-rounded opacity-5">account_balance_wallet</i>
              <span class="nav-link-text ms-1">Gastos de Tienda</span>
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
            <a class="nav-link text-dark" href="/electronic-documents">
              <i class="material-symbols-rounded opacity-5">receipt_long</i>
              <span class="nav-link-text ms-1">Facturación Digital</span>
            </a>
          </li>
                    <li class="nav-item">
            <a class="nav-link text-dark" href="/logs">
              <i class="material-symbols-rounded opacity-5">format_textdirection_r_to_l</i>
              <span class="nav-link-text ms-1">Logs</span>
            </a>
          </li>
                    <li class="nav-item">
                      <a class="nav-link text-dark" href="/documentation">
                        <i class="material-symbols-rounded opacity-5">description</i>
                        <span class="nav-link-text ms-1">Documentación</span>
                      </a>
                    </li>
          <li class="nav-item">
            <a class="nav-link text-dark" href="/users">
              <i class="material-symbols-rounded opacity-5">person</i>
              <span class="nav-link-text ms-1">Gestión de usuarios</span>
            </a>
          </li>
        @endif
      </ul>
    </div>
    <div class="sidenav-footer position-absolute w-100 bottom-0 ">
      <div class="mx-3">
      </div>
    </div>
<script src="https://js.pusher.com/8.2.0/pusher.min.js"></script>
<!-- Github buttons -->
<script async defer src="https://buttons.github.io/buttons.js"></script>
<div class="toast-container position-fixed top-0 end-0 p-3" id="backoffice-toast-container" style="z-index: 3000;"></div>

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

      const badges = Array.from(document.querySelectorAll('.backoffice-notifications-count'));
      const toastContainer = document.getElementById('backoffice-toast-container');
      const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
      const serviceWorkerUrl = @json(url('/push-sw.js'));
      const vapidPublicKey = @json(config('webpush.vapid.public_key'));
      const defaultNotificationIcon = @json($tenantLogo ?? asset('assets/img/shopix5.png'));
      let serviceWorkerRegistrationPromise = null;
      const processedNotificationIds = new Set();
      let feedPrimed = false;
      let notificationPollIntervalId = null;

      function isAlreadyProcessedNotification(notification) {
        const id = notification?.id;
        return !!(id && processedNotificationIds.has(String(id)));
      }

      function markNotificationAsProcessed(notification) {
        const id = notification?.id;
        if (!id) {
          return;
        }

        processedNotificationIds.add(String(id));

        if (processedNotificationIds.size > 400) {
          const iterator = processedNotificationIds.values();
          const firstValue = iterator.next();
          if (!firstValue.done) {
            processedNotificationIds.delete(firstValue.value);
          }
        }
      }

      function updateBadge(unread) {
        if (!badges.length) return;
        const current = Number(badges[0].textContent || 0);
        const count = typeof unread === 'number' ? unread : current + 1;
        badges.forEach((badgeEl) => {
          badgeEl.textContent = String(count);
          badgeEl.classList.toggle('d-none', count <= 0);
        });
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

      function supportsBrowserNotifications() {
        return window.isSecureContext && 'Notification' in window && 'serviceWorker' in navigator && 'PushManager' in window;
      }

      function isIosDevice() {
        return /iphone|ipad|ipod/i.test(window.navigator.userAgent);
      }

      function isStandaloneMode() {
        return window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true;
      }

      function browserNotificationSupportState() {
        return {
          secureContext: window.isSecureContext,
          notificationApi: 'Notification' in window,
          serviceWorkerApi: 'serviceWorker' in navigator,
          pushManagerApi: 'PushManager' in window,
          vapidConfigured: !!vapidPublicKey,
        };
      }

      function updateInstallPwaUi() {
        const installPwaBtn = document.getElementById('backoffice-install-pwa');
        if (!installPwaBtn) {
          return;
        }

        installPwaBtn.classList.remove('d-none');

        if (isStandaloneMode()) {
          installPwaBtn.textContent = 'App instalada';
          installPwaBtn.classList.add('is-ready');
          return;
        }

        installPwaBtn.classList.remove('is-ready');

        if (isIosDevice()) {
          installPwaBtn.textContent = 'Agregar a inicio';
          return;
        }

        installPwaBtn.textContent = 'Instalar app';
      }

      async function installBackofficePwa() {
        const installPwaBtn = document.getElementById('backoffice-install-pwa');
        if (!installPwaBtn) {
          return;
        }

        if (isStandaloneMode()) {
          return;
        }

        if (isIosDevice()) {
          alert('En iPhone o iPad, abre este sitio en Safari, toca Compartir y luego selecciona "Agregar a pantalla de inicio". Después abre Shopix desde el icono instalado.');
          return;
        }

        if (!window.__shopixDeferredInstallPrompt) {
          alert('La instalación aún no está disponible en este navegador. Recarga la página, usa HTTPS y asegúrate de que el sitio no esté ya instalado.');
          return;
        }

        window.__shopixDeferredInstallPrompt.prompt();
        const choice = await window.__shopixDeferredInstallPrompt.userChoice.catch(() => null);
        window.__shopixDeferredInstallPrompt = null;
        updateInstallPwaUi();

        if (choice?.outcome === 'accepted') {
          showToast('Instalación iniciada', 'Shopix se agregará como app en este dispositivo.');
        }
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

      async function deleteStoredPushSubscription(endpoint) {
        if (!endpoint) {
          return;
        }

        await fetch('/push-subscriptions', {
          method: 'DELETE',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            'X-CSRF-TOKEN': csrfToken,
          },
          body: JSON.stringify({ endpoint }),
        }).catch(() => {});
      }

      function shouldForceIosPushRefresh() {
        return isIosDevice() && isStandaloneMode() && Notification.permission === 'granted';
      }

      async function syncBrowserPushSubscription(options = {}) {
        if (!supportsBrowserNotifications() || !vapidPublicKey) {
          return null;
        }

        const registration = await ensureServiceWorkerRegistration();
        if (!registration) {
          return null;
        }

        let subscription = await registration.pushManager.getSubscription();
        if (subscription && options.forceRefresh === true) {
          await deleteStoredPushSubscription(subscription.endpoint);
          await subscription.unsubscribe().catch(() => {});
          subscription = null;
        }

        if (!subscription) {
          subscription = await registration.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToUint8Array(vapidPublicKey),
          });
        }

        await fetch('/push-subscriptions', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
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

      async function showBrowserNotification(notification, options = {}) {
        if (!supportsBrowserNotifications() || Notification.permission !== 'granted') {
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
          tag: `shopix-backoffice-${notification.id || Date.now()}`,
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

      function updateBrowserNotificationUi() {
        const enableBrowserNotificationsBtn = document.getElementById('backoffice-enable-browser-notifications');
        if (!enableBrowserNotificationsBtn) {
          return;
        }

        const support = browserNotificationSupportState();

        enableBrowserNotificationsBtn.classList.remove('d-none');

        if (!supportsBrowserNotifications()) {
          enableBrowserNotificationsBtn.textContent = 'Alertas no disponibles';
          enableBrowserNotificationsBtn.classList.remove('is-ready');
          return;
        }

        if (!support.vapidConfigured) {
          enableBrowserNotificationsBtn.textContent = 'Alertas no configuradas';
          enableBrowserNotificationsBtn.classList.remove('is-ready');
          return;
        }

        const permission = Notification.permission;

        if (permission === 'granted') {
          enableBrowserNotificationsBtn.textContent = 'Alertas activas';
          enableBrowserNotificationsBtn.classList.add('is-ready');
          return;
        }

        enableBrowserNotificationsBtn.classList.remove('is-ready');

        if (permission === 'denied') {
          enableBrowserNotificationsBtn.textContent = 'Alertas bloqueadas';
          return;
        }

        enableBrowserNotificationsBtn.textContent = 'Activar alertas';
      }

      async function requestBrowserNotificationPermission() {
        const support = browserNotificationSupportState();

        if (!supportsBrowserNotifications()) {
          const missing = [];
          if (!support.secureContext) missing.push('HTTPS');
          if (!support.notificationApi) missing.push('Notification API');
          if (!support.serviceWorkerApi) missing.push('Service Worker');
          if (!support.pushManagerApi) missing.push('Push API');

          alert(`Este navegador todavía no puede activar alertas web aquí. Falta: ${missing.join(', ')}. En iPhone, abre Shopix desde Safari y agrega el sitio a pantalla de inicio.`);
          return;
        }

        if (!support.vapidConfigured) {
          alert('Las notificaciones push aún no están configuradas en el servidor.');
          return;
        }

        if (Notification.permission === 'denied') {
          alert('El permiso de notificaciones está bloqueado. Debes habilitarlo manualmente en la configuración del navegador o del sistema.');
          return;
        }

        const permission = await Notification.requestPermission();
        updateBrowserNotificationUi();

        if (permission !== 'granted') {
          return;
        }

        await syncBrowserPushSubscription();
        await showBrowserNotification({
          title: 'Alertas activadas',
          message: 'Este dispositivo ya puede recibir notificaciones del panel de Shopix.',
          target_url: window.location.href,
        }, { force: true });
        showToast('Alertas activadas', 'Este dispositivo ya puede recibir notificaciones del panel.');
      }

      function bindNotificationChannel() {
        const pusherKey = @json(config('broadcasting.connections.reverb.key'));
        if (!pusherKey) return;

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
          authEndpoint: '/broadcasting/auth',
          auth: {
            headers: {
              'X-CSRF-TOKEN': csrfToken,
            },
          },
        };

        if (configuredCluster) {
          pusherOptions.cluster = configuredCluster;
        }

        const pusher = new Pusher(pusherKey, pusherOptions);

        const channel = pusher.subscribe(`private-App.Models.User.${userId}`);
        const handleIncoming = (notification) => {
          if (isAlreadyProcessedNotification(notification)) {
            return;
          }

          markNotificationAsProcessed(notification);
          const title = notification.title || 'Notificación';
          const message = notification.message || '';
          updateBadge();
          showToast(title, message);
          window.dispatchEvent(new CustomEvent('shopix:backoffice-notification', {
            detail: notification,
          }));
          showBrowserNotification(notification).catch(() => {});
        };

        channel.bind('Illuminate\\Notifications\\Events\\BroadcastNotificationCreated', handleIncoming);
        channel.bind('.Illuminate\\Notifications\\Events\\BroadcastNotificationCreated', handleIncoming);
        pusher.connection.bind('error', () => {});
      }

      async function syncNotificationsFromFeed(showNewToasts = false) {
        try {
          const response = await fetch('/notifications/feed', { headers: { 'Accept': 'application/json' } });
          if (!response.ok) {
            return;
          }

          const payload = await response.json();
          if (!payload.success) {
            return;
          }

          updateBadge(payload.unread_count || 0);

          const notifications = Array.isArray(payload.notifications) ? payload.notifications : [];

          if (!feedPrimed) {
            notifications.forEach((notification) => markNotificationAsProcessed(notification));
            feedPrimed = true;
            return;
          }

          const newNotifications = notifications
            .filter((notification) => !isAlreadyProcessedNotification(notification))
            .reverse();

          newNotifications.forEach((notification) => {
            markNotificationAsProcessed(notification);
            window.dispatchEvent(new CustomEvent('shopix:backoffice-notification', {
              detail: notification,
            }));

            if (showNewToasts) {
              const title = notification.title || 'Notificación';
              const message = notification.message || '';
              showToast(title, message);
              showBrowserNotification(notification).catch(() => {});
            }
          });
        } catch (error) {
        }
      }

      async function loadInitialUnreadCount() {
        await syncNotificationsFromFeed(false);
      }

      window.addEventListener('beforeinstallprompt', (event) => {
        event.preventDefault();
        window.__shopixDeferredInstallPrompt = event;
        updateInstallPwaUi();
      });

      window.addEventListener('appinstalled', () => {
        window.__shopixDeferredInstallPrompt = null;
        updateInstallPwaUi();
      });

      loadInitialUnreadCount();
      bindNotificationChannel();

      if (!notificationPollIntervalId) {
        notificationPollIntervalId = window.setInterval(() => {
          syncNotificationsFromFeed(true);
        }, 30000);
      }

      try {
        ensureServiceWorkerRegistration().catch(() => {});
        updateBrowserNotificationUi();
        updateInstallPwaUi();
        if (supportsBrowserNotifications() && Notification.permission === 'granted') {
          syncBrowserPushSubscription({ forceRefresh: shouldForceIosPushRefresh() }).catch(() => {});
        }

        document.getElementById('backoffice-enable-browser-notifications')?.addEventListener('click', requestBrowserNotificationPermission);
        document.getElementById('backoffice-install-pwa')?.addEventListener('click', installBackofficePwa);
      } catch (error) {
      }

      window.addEventListener('pageshow', () => {
        if (shouldForceIosPushRefresh()) {
          syncBrowserPushSubscription({ forceRefresh: true }).catch(() => {});
        }
      });

      document.addEventListener('visibilitychange', () => {
        if (document.visibilityState === 'visible' && shouldForceIosPushRefresh()) {
          syncBrowserPushSubscription({ forceRefresh: true }).catch(() => {});
        }

        if (document.visibilityState === 'visible') {
          syncNotificationsFromFeed(true);
        }
      });
    })();

    document.addEventListener("DOMContentLoaded", function () {
        const currentUrl = window.location.pathname; // Obtén la ruta actual sin el dominio
        const navLinks = document.querySelectorAll('.navbar-nav .nav-link'); // Selecciona los enlaces
      const sidenav = document.getElementById('sidenav-main');
      const iconSidenav = document.getElementById('iconSidenav');
      const btnOpenNav = document.getElementById('btnOpenNav');
      const btnCloseNav = document.getElementById('btnCloseNav');

      // Defensive cleanup for stale overlays after login/redirects.
      document.querySelectorAll('.modal-backdrop, .offcanvas-backdrop').forEach((el) => el.remove());
      document.body.classList.remove('modal-open');
      document.body.style.overflow = '';
      document.body.style.paddingRight = '';

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

</script>
  <script async defer src="https://buttons.github.io/buttons.js"></script>
  <script src="{{ asset('assets/js/navbar.js') }}"></script>