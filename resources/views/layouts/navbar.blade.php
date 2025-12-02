<style>
#sidenav-main {
  transition: transform 0.3s ease-in-out;
}

#sidenav-main.closed {
  transform: translateX(-100%);
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

      $user = auth()->user();
      $tenantLogo = null;
      $tenant = null;
      if ($user && $user->tenant_id) {
          $tenant = Tenant::find($user->tenant_id);

          if ($tenant && $tenant->logo) {
              // Si el logo está almacenado en storage
              $tenantLogo = asset('storage/' . $tenant->logo);
          }
      }
    @endphp
    <div class="sidenav-header m-0 p-0 h-15">
      <i class="fas fa-times p-3 cursor-pointer text-dark opacity-5 position-absolute end-0 top-0 d-none d-xl-none"
        aria-hidden="true" id="iconSidenav"></i>
      <a class="navbar-brand d-flex justify-content-center align-items-center" href="/dashboard">
        <img src="{{ $tenantLogo ?? asset('assets/img/shopix5.png') }}"
            class="navbar-brand-img"
            width="100"
            height="100"
            alt="main_logo"
            style="object-fit: contain;">
      </a>
    </div>
    <hr class="horizontal dark mt-0 mb-2">
    <div class="collapse navbar-collapse w-auto" id="sidenav-collapse-main">
      <ul class="navbar-nav">
      @if($user->role_id === 1)
        <li class="nav-item">
          <a class="nav-link text-dark" href="/dashboard">
            <i class="material-symbols-rounded opacity-5">dashboard</i>
            <span class="nav-link-text ms-1">Administrador</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-dark" href="/categories">
            <i class="material-symbols-rounded opacity-5">view_in_ar</i>
            <span class="nav-link-text ms-1">Categorías</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-dark" href="/products">
            <i class="material-symbols-rounded opacity-5">table_view</i>
            <span class="nav-link-text ms-1">Productos</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-dark" href="/paymentMethods">
            <i class="material-symbols-rounded opacity-5">view_in_ar</i>
            <span class="nav-link-text ms-1">Métodos de Pago</span>
          </a>
        </li>
      @endif
        @if($user->role_id === 2 || $user->role_id === 1)

        <li class="nav-item">
          <a class="nav-link text-dark" href="/sales">
            <i class="material-symbols-rounded opacity-5">receipt_long</i>
            <span class="nav-link-text ms-1">Realizar Venta</span>
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link text-dark" href="/sales-orders">
            <i class="material-symbols-rounded opacity-5">format_textdirection_r_to_l</i>
            <span class="nav-link-text ms-1">Ventas Realizadas</span>
          </a>
        </li>
      @endif
        @if($user->role_id === 2)
          <li class="nav-item">
            <a class="nav-link text-dark" href="/purchase">
              <i class="material-symbols-rounded opacity-5">view_in_ar</i>
              <!-- <i class="bi bi-bag"></i> -->
              <span class="nav-link-text ms-1">Realizar Compra</span>
            </a>
          </li>
          <li class="nav-item">
            <a class="nav-link text-dark" href="/purchase-orders">
              <i class="material-symbols-rounded opacity-5">format_textdirection_r_to_l</i>
              <span class="nav-link-text ms-1">Compras Realizadas</span>
            </a>
          </li>
        @endif
        @if($user->role_id === 4)
          <li class="nav-item">
            <a class="nav-link text-dark" href="/plans">
              <i class="material-symbols-rounded opacity-5">view_in_ar</i>
              <!-- <i class="bi bi-bag"></i> -->
              <span class="nav-link-text ms-1">Planes</span>
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
      </ul>
    </div>
    <div class="sidenav-footer position-absolute w-100 bottom-0 ">
      <div class="mx-3">
      </div>
    </div>
<!-- Core JS Files -->
<script src="{{ asset('assets/js/core/popper.min.js') }}"></script>
<script src="{{ asset('assets/js/core/bootstrap.min.js') }}"></script>
<!-- Github buttons -->
<script async defer src="https://buttons.github.io/buttons.js"></script>

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
    document.addEventListener("DOMContentLoaded", function () {
        const currentUrl = window.location.pathname; // Obtén la ruta actual sin el dominio
        const navLinks = document.querySelectorAll('.navbar-nav .nav-link'); // Selecciona los enlaces

        navLinks.forEach(link => {
            const linkHref = link.getAttribute('href');
            if (currentUrl === linkHref) { // Compara la ruta actual con el href
                link.classList.add("bg-gray-900", "text-white");
            } else {
                link.classList.remove("bg-gray-900", "text-white");
            }
        });
        const toggleNavbarButton = document.getElementById('toggleNavbar');
        const sidenav = document.getElementById('sidenav-main');
        const body = document.getElementById('d-body');

    });

  function logOut() {
    fetch("/logout", { 
        method: 'POST',
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