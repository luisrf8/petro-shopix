<!--
=========================================================
* Material Dashboard 3 - v3.2.0
=========================================================

* Product Page: https://www.creative-tim.com/product/material-dashboard
* Copyright 2024 Creative Tim (https://www.creative-tim.com)
* Licensed under MIT (https://www.creative-tim.com/license)
* Coded by Creative Tim

=========================================================

* The above copyright notice and this permission notice shall be included in all copies or substantial portions of the Software.
-->
<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <link rel="apple-touch-icon" sizes="76x76" href="../assets/img/apple-icon.png">
  <link rel="icon" type="image/png" href="../assets/img/favicon.png">
  <title>
  </title>
  <!--     Fonts and icons     -->
  <link rel="stylesheet" type="text/css" href="https://fonts.googleapis.com/css?family=Inter:300,400,500,600,700,900" />
  <!-- Nucleo Icons -->
  <link href="{{ asset('assets/css/nucleo-icons.css') }}" rel="stylesheet">
  <link href="{{ asset('assets/css/nucleo-svg.css') }}" rel="stylesheet">
  <!-- Font Awesome Icons -->
  <script src="https://kit.fontawesome.com/42d5adcbca.js" crossorigin="anonymous"></script>
  <!-- Material Icons -->
  <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Rounded:opsz,wght,FILL,GRAD@24,400,0,0" />
  <!-- CSS Files -->
  <link href="{{ asset('assets/css/material-dashboard.css?v=3.2.0') }}" rel="stylesheet">
</head>

<body class="g-sidenav-show  bg-gray-100">
@php
  $authUser = auth()->user();
  $authUserName = (string) ($authUser->name ?? 'Usuario');
  $authUserEmail = (string) ($authUser->email ?? 'Sin correo');
  $authUserRole = (string) (optional($authUser?->role)->name ?? 'Sin rol');
@endphp
<style>
  .header-session-user {
    min-width: 0;
    max-width: 320px;
  }

  .header-session-user .session-name {
    font-size: 0.78rem;
    font-weight: 700;
    color: #1f2937;
    line-height: 1.1;
  }

  .header-session-user .session-meta {
    font-size: 0.68rem;
    color: #6b7280;
    line-height: 1.1;
  }

  .header-session-user .session-text {
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    display: block;
  }

  @media (max-width: 576px) {
    .header-session-user {
      max-width: 200px;
    }

    .header-session-user .session-name {
      font-size: 0.72rem;
    }

    .header-session-user .session-meta {
      font-size: 0.64rem;
    }
  }
</style>
<nav class="navbar navbar-main navbar-expand-lg px-0  shadow-none border-radius-xl" id="navbarBlur" data-scroll="true">
      <div class="container-fluid py-1">
        <nav aria-label="breadcrumb">
          <ol class="breadcrumb bg-transparent mb-0 pb-0 pt-1 px-0 me-sm-6 me-5">
            <!-- Botón para desktop (toggle con icono que cambia) -->
            <li>
              <button id="btnDesktopNav" class="btn btn-black top-0 start-0 m-2 z-index-3 d-none d-lg-inline-block">
                <i class="material-symbols-rounded">menu</i>
              </button>
            </li>

            <li class="breadcrumb-item text-sm d-flex align-items-center">
              <a class="opacity-5 text-dark" href="/paymentMethods">
                <!-- Tasa Actual: {{ $dollarRate ? number_format($dollarRate->rate, 2) : 'No disponible' }} VES / USD -->
              </a>
            </li>

            <!-- Botones solo para móvil -->
            <li class="d-lg-none">
              <!-- Abrir -->
              <button id="btnOpenNav" class="btn btn-black top-0 start-0 m-2 z-index-3">
                <i class="material-symbols-rounded">menu</i>
              </button>
              <!-- Cerrar -->
              <button id="btnCloseNav" class="btn btn-black top-0 start-0 m-2 z-index-3" style="display:none;">
                <i class="material-symbols-rounded">close</i>
              </button>
            </li>
          </ol>
        </nav>

        <div class="collapse navbar-collapse mt-sm-0 mt-2 me-md-0 me-sm-4" id="navbar">
          <!-- <div class="ms-md-auto pe-md-3 d-flex align-items-center">
            <div class="input-group input-group-outline">
              <label class="form-label">Type here...</label>
              <input type="text" class="form-control">
            </div>
          </div> -->
          <ul class="navbar-nav ms-md-auto pe-md-3 d-flex align-items-center  justify-content-end">
            <li class="nav-item d-flex align-items-center me-3 header-session-user">
              <div class="d-flex text-end align-items-center gap-1">
                <span class="session-name session-text">{{ $authUserName }}</span> / 
                <span class="session-meta session-text">{{ $authUserEmail }}</span> / 
                <span class="session-meta session-text">Rol: {{ $authUserRole }}</span>
              </div>
            </li>
            <li class="nav-item d-flex align-items-center me-2">
              <a href="{{ route('notifications.index') }}" class="nav-link text-body p-0 position-relative" aria-label="Notificaciones" title="Notificaciones">
                <i class="material-symbols-rounded">notifications</i>
                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none" id="backoffice-notifications-count">0</span>
              </a>
            </li>
            <li class="nav-item d-flex align-items-center">
              <form method="POST" action="{{ route('logout') }}" class="m-0 p-0">
                @csrf
                <button
                  type="submit"
                  class="nav-link text-body p-0 border-0 bg-transparent"
                  aria-label="Cerrar sesión"
                  title="Cerrar sesión"
                >
                  <i class="material-symbols-rounded">logout</i>
                </button>
              </form>
            </li>
            <li class="nav-item d-lg-none d-flex align-items-center me-2">
              <a class="nav-link text-body p-0 position-relative" href="{{ route('notifications.index') }}" aria-label="Notificaciones">
                <i class="material-symbols-rounded">notifications</i>
                @if($headerUnreadNotificationsCount > 0)
                  <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger backoffice-notifications-count">{{ $headerUnreadNotificationsCount }}</span>
                @else
                  <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger d-none backoffice-notifications-count">0</span>
                @endif
              </a>
            </li>
            <li class="nav-item d-lg-none d-flex align-items-center me-2">
              <a class="nav-link text-body p-0" href="/users" aria-label="Usuario">
                <i class="material-symbols-rounded">account_circle</i>
              </a>
            </li>
            <li class="nav-item d-lg-none d-flex align-items-center">
              <button type="button" class="nav-link text-body p-0 border-0 bg-transparent" onclick="logOut()" aria-label="Cerrar sesión">
                <i class="material-symbols-rounded">logout</i>
              </button>
            </li>
            <!-- <li class="nav-item px-3 d-flex align-items-center">
              <a href="javascript:;" class="nav-link text-body p-0">
                <i class="material-symbols-rounded fixed-plugin-button-nav">settings</i>
              </a>
            </li> -->
            <!-- <li class="nav-item dropdown pe-3 d-flex align-items-center">
              <a href="javascript:;" class="nav-link text-body p-0" id="dropdownMenuButton" data-bs-toggle="dropdown" aria-expanded="false">
                <i class="material-symbols-rounded">notifications</i>
              </a>
              <ul class="dropdown-menu  dropdown-menu-end  px-2 py-3 me-sm-n4" aria-labelledby="dropdownMenuButton">
                <li class="mb-2">
                  <a class="dropdown-item border-radius-md" href="javascript:;">
                    <div class="d-flex py-1">
                      <div class="my-auto">
                        <img src="../assets/img/team-2.jpg" class="avatar avatar-sm  me-3 ">
                      </div>
                      <div class="d-flex flex-column justify-content-center">
                        <h6 class="text-sm font-weight-normal mb-1">
                          <span class="font-weight-bold">New message</span> from Laur
                        </h6>
                        <p class="text-xs text-secondary mb-0">
                          <i class="fa fa-clock me-1"></i>
                          13 minutes ago
                        </p>
                      </div>
                    </div>
                  </a>
                </li>
                <li class="mb-2">
                  <a class="dropdown-item border-radius-md" href="javascript:;">
                    <div class="d-flex py-1">
                      <div class="my-auto">
                        <img src="../assets/img/small-logos/logo-spotify.svg" class="avatar avatar-sm bg-gradient-dark  me-3 ">
                      </div>
                      <div class="d-flex flex-column justify-content-center">
                        <h6 class="text-sm font-weight-normal mb-1">
                          <span class="font-weight-bold">New album</span> by Travis Scott
                        </h6>
                        <p class="text-xs text-secondary mb-0">
                          <i class="fa fa-clock me-1"></i>
                          1 day
                        </p>
                      </div>
                    </div>
                  </a>
                </li>
                <li>
                  <a class="dropdown-item border-radius-md" href="javascript:;">
                    <div class="d-flex py-1">
                      <div class="avatar avatar-sm bg-gradient-secondary  me-3  my-auto">
                        <svg width="12px" height="12px" viewBox="0 0 43 36" version="1.1" xmlns="http://www.w3.org/2000/svg" xmlns:xlink="http://www.w3.org/1999/xlink">
                          <title>credit-card</title>
                          <g stroke="none" stroke-width="1" fill="none" fill-rule="evenodd">
                            <g transform="translate(-2169.000000, -745.000000)" fill="#FFFFFF" fill-rule="nonzero">
                              <g transform="translate(1716.000000, 291.000000)">
                                <g transform="translate(453.000000, 454.000000)">
                                  <path class="color-background" d="M43,10.7482083 L43,3.58333333 C43,1.60354167 41.3964583,0 39.4166667,0 L3.58333333,0 C1.60354167,0 0,1.60354167 0,3.58333333 L0,10.7482083 L43,10.7482083 Z" opacity="0.593633743"></path>
                                  <path class="color-background" d="M0,16.125 L0,32.25 C0,34.2297917 1.60354167,35.8333333 3.58333333,35.8333333 L39.4166667,35.8333333 C41.3964583,35.8333333 43,34.2297917 43,32.25 L43,16.125 L0,16.125 Z M19.7083333,26.875 L7.16666667,26.875 L7.16666667,23.2916667 L19.7083333,23.2916667 L19.7083333,26.875 Z M35.8333333,26.875 L28.6666667,26.875 L28.6666667,23.2916667 L35.8333333,23.2916667 L35.8333333,26.875 Z"></path>
                                </g>
                              </g>
                            </g>
                          </g>
                        </svg>
                      </div>
                      <div class="d-flex flex-column justify-content-center">
                        <h6 class="text-sm font-weight-normal mb-1">
                          Payment successfully completed
                        </h6>
                        <p class="text-xs text-secondary mb-0">
                          <i class="fa fa-clock me-1"></i>
                          2 days
                        </p>
                      </div>
                    </div>
                  </a>
                </li>
              </ul>
            </li> -->
            <!-- <li class="nav-item d-flex align-items-center">
              <a href="../pages/sign-in.html" class="nav-link text-body font-weight-bold px-0">
                <i class="material-symbols-rounded">account_circle</i>
              </a>
            </li> -->
          </ul>
        </div>
      </div>
    </nav>
<!-- Core JS Files -->
<script src="{{ asset('assets/js/core/popper.min.js') }}"></script>
<script src="{{ asset('assets/js/core/bootstrap.min.js') }}"></script>

<!-- Github buttons -->
<script async defer src="https://buttons.github.io/buttons.js"></script>

<!-- Control Center for Material Dashboard: parallax effects, scripts for the example pages etc -->

<script>
document.addEventListener("DOMContentLoaded", function () {
    const sidenav = document.getElementById('sidenav-main');

    // Botones
    const btnDesktop = document.getElementById('btnDesktopNav');
    const btnOpen = document.getElementById('btnOpenNav');
    const btnClose = document.getElementById('btnCloseNav');

    // ==== Desktop ====
    if (btnDesktop) {
        btnDesktop.addEventListener('click', function () {
            sidenav.classList.toggle('closed');
            const icon = btnDesktop.querySelector('i');
            if (sidenav.classList.contains('closed')) {
                icon.textContent = 'menu_open';
            } else {
                icon.textContent = 'menu';
            }
        });
    }

    // ==== Móvil ====
    function initMobileState() {
        if (window.innerWidth < 992) {
            sidenav.classList.add('closed');
            btnOpen.style.display = "inline-block";
            btnClose.style.display = "none";
        }
    }

    btnOpen.addEventListener('click', function () {
        sidenav.classList.remove('closed');
        btnOpen.style.display = "none";
        btnClose.style.display = "inline-block";
    });

    btnClose.addEventListener('click', function () {
        sidenav.classList.add('closed');
        btnClose.style.display = "none";
        btnOpen.style.display = "inline-block";
    });

    // Al cargar y al redimensionar
    initMobileState();
    window.addEventListener('resize', initMobileState);
});
</script>

  <!-- Github buttons -->
  <script async defer src="https://buttons.github.io/buttons.js"></script>
  <!-- Control Center for Material Dashboard: parallax effects, scripts for the example pages etc -->
</body>

</html>