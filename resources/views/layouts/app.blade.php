<!DOCTYPE html>
<html lang="es">
<head>
    @php
      $backofficePwaName = trim((string) config('app.name', 'Shopix'));
      $backofficePwaName = $backofficePwaName !== '' ? $backofficePwaName . ' Admin' : 'Shopix Admin';
      $backofficePwaTheme = '0F172A';
      $backofficePwaStartUrl = request()->getRequestUri() ?: '/dashboard';
      $backofficePwaIconVersion = (string) config('app.asset_version', '20260710');
    @endphp
    <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <link rel="apple-touch-icon" sizes="180x180" href="{{ route('pwa.icon', ['size' => 180, 'variant' => 'admin', 'v' => $backofficePwaIconVersion]) }}">
  <link rel="icon" type="image/png" href="{{ route('pwa.icon', ['size' => 192, 'variant' => 'admin', 'v' => $backofficePwaIconVersion]) }}">
  <meta name="theme-color" content="#{{ $backofficePwaTheme }}">
  <meta name="mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-capable" content="yes">
  <meta name="apple-mobile-web-app-status-bar-style" content="default">
  <meta name="apple-mobile-web-app-title" content="{{ \Illuminate\Support\Str::limit($backofficePwaName, 24, '') }}">
  <link rel="manifest" href="{{ route('tenant.pwa.manifest', ['start_url' => $backofficePwaStartUrl, 'name' => $backofficePwaName, 'theme' => $backofficePwaTheme, 'icon_variant' => 'admin']) }}">
  <title>
  </title>
  <!-- Nucleo Icons -->
  <link href="{{ asset('assets/css/nucleo-icons.css') }}" rel="stylesheet">
  <link href="{{ asset('assets/css/nucleo-svg.css') }}" rel="stylesheet">
  <!-- CSS Files -->
  <link href="{{ asset('assets/css/material-dashboard.css?v=3.2.0') }}" rel="stylesheet">
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/driver.js@1.0.1/dist/driver.css" />
  <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Mi App')</title>
    <link rel="stylesheet" href="{{ asset('assets/css/material-dashboard.min.css') }}">
    <style>
      :root {
        --z-cart: 1030;
        --z-header: 1040;
        --z-modal-backdrop: 1050;
        --z-modal: 1055;
      }

      .module-wizard-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(22, 26, 35, 0.58);
        z-index: 2147482000;
        display: none;
      }

      .module-wizard-focus {
        position: fixed;
        border: 2px solid #8fb2ff;
        border-radius: 10px;
        box-shadow: 0 0 0 9999px rgba(16, 20, 29, 0.45);
        z-index: 2147482001;
        pointer-events: none;
        display: none;
        transition: all 0.2s ease;
      }

      .module-wizard-tooltip {
        position: fixed;
        z-index: 2147482002;
        width: min(360px, calc(100vw - 1rem));
        max-height: calc(100vh - 1rem);
        overflow-y: auto;
        top: -9999px;
        left: -9999px;
        display: none;
      }

      .module-step-inline-note {
        margin-top: 0.5rem;
        margin-bottom: 0.9rem;
        border-left: 4px solid #5e72e4;
        background: #f4f7ff;
        border-radius: 0.45rem;
        padding: 0.6rem 0.75rem;
        color: #344767;
      }

      .module-step-chip {
        border: 1px solid #d0d7e2;
        border-radius: 999px;
        background: #fff;
        color: #2f3d56;
        padding: 0.2rem 0.7rem;
        font-size: 0.75rem;
        cursor: pointer;
      }

      .module-step-chip.active {
        background: #344767;
        color: #fff;
        border-color: #344767;
      }

      .module-step-current {
        box-shadow: 0 0 0 3px rgba(95, 124, 234, 0.35);
        border-radius: 8px;
      }

      .admin-mobile-search {
        width: 100%;
        max-width: 320px;
      }

      .admin-mobile-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.6rem;
      }

      .admin-mobile-action-trigger {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 1px solid rgba(52, 71, 103, 0.25);
        border-radius: 0.5rem;
        min-height: 38px;
        padding: 0.4rem 0.75rem;
        line-height: 1.2;
        text-align: center;
        white-space: normal;
      }

      .admin-mobile-action-trigger.text-white {
        border-color: rgba(255, 255, 255, 0.45);
      }

      .url-icon-action-btn {
        width: 42px;
        min-width: 42px;
        height: 42px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        padding: 0;
      }

      .url-icon-action-btn .material-symbols-rounded {
        font-size: 20px;
        line-height: 1;
      }

      .url-icon-action-btn.url-icon-action-btn-sm {
        width: 32px;
        min-width: 32px;
        height: 32px;
      }

      .url-icon-action-btn.url-icon-action-btn-sm .material-symbols-rounded {
        font-size: 18px;
      }

      input[type="checkbox"],
      input[type="radio"] {
        accent-color: #6c757d;
      }

      .form-check-input:checked {
        background-color: #6c757d !important;
        border-color: #6c757d !important;
      }

      .form-check-input:focus {
        border-color: #6c757d !important;
        box-shadow: 0 0 0 0.15rem rgba(108, 117, 125, 0.25) !important;
      }

      @media (max-width: 768px) {
        .module-wizard-tooltip {
          width: calc(100vw - 1rem);
        }

        .main-content .card-header .bg-gradient-dark {
          flex-wrap: wrap;
          gap: 0.5rem;
          align-items: flex-start !important;
        }

        .main-content .card-header .text-end {
          width: 100%;
          display: flex;
          flex-direction: column;
          align-items: stretch !important;
          gap: 0.45rem;
          padding-top: 0.25rem;
        }

        .main-content .card-header .text-end .ms-6 {
          margin-left: 0 !important;
        }

        .admin-mobile-actions {
          width: 100%;
          justify-content: stretch;
        }

        .admin-mobile-actions > * {
          flex: 1 1 calc(50% - 0.6rem);
          min-width: 140px;
        }

        .admin-mobile-search {
          max-width: 100%;
        }

        .admin-mobile-action-trigger {
          width: 100%;
          min-height: 40px;
        }

        .main-content .card-header .text-end > label.text-white,
        .main-content .card-header .text-end > a.text-white,
        .main-content .card-header [data-bs-toggle="modal"] > label.text-white,
        .main-content .card-header .py-1.px-3.text-end[data-bs-toggle="modal"] > label.text-white {
          display: inline-flex;
          width: 100%;
          justify-content: center;
          align-items: center;
          text-align: center;
          white-space: normal;
          line-height: 1.25;
          padding: 0.45rem 0.7rem;
          border: 1px solid rgba(255, 255, 255, 0.45);
          border-radius: 0.5rem;
          cursor: pointer;
        }

        .main-content .card .table-responsive {
          overflow-x: auto;
          -webkit-overflow-scrolling: touch;
        }

        .main-content .card .table-responsive table {
          min-width: 640px;
        }

        .main-content .card .table th,
        .main-content .card .table td {
          font-size: 0.7rem;
          padding: 0.4rem 0.4rem;
          line-height: 1.2;
          vertical-align: middle;
        }

        .main-content a.btn-edit-user,
        .main-content a.toggle-status-btn,
        .main-content button.toggle-status-btn,
        .main-content .toggle-status-btn {
          display: inline-flex !important;
          align-items: center;
          justify-content: center;
          white-space: nowrap;
          line-height: 1.2;
          padding: 0.35rem 0.55rem;
          border: 1px solid currentColor;
          border-radius: 0.45rem;
          min-height: 30px;
        }
      }

      .sidebar-full-height {
        height: 100vh;
        min-height: 100vh;
        margin-top: 0 !important;
        margin-bottom: 0 !important;
        border-radius: 0 !important;
      }

      /* Keep a single browser scroll so top navbar sticky works consistently. */
      .main-content {
        height: auto !important;
        max-height: none !important;
        overflow: visible !important;
      }

      .shopix-drag-scroll {
        cursor: grab;
      }

      .shopix-drag-scroll.is-dragging {
        cursor: grabbing;
        user-select: none;
      }

      .shopix-drag-table-wrap {
        overflow-x: auto;
        -webkit-overflow-scrolling: touch;
        width: 100%;
      }

      .shopix-admin-tour-launcher {
        position: fixed;
        right: 1rem;
        bottom: 1rem;
        z-index: 1090;
        display: inline-flex;
        align-items: center;
        gap: 0.45rem;
        border: 0;
        border-radius: 999px;
        padding: 0.55rem 0.95rem;
        font-weight: 700;
        background: linear-gradient(135deg, #0f172a, #1e293b);
        color: #f8fafc;
        box-shadow: 0 14px 26px rgba(15, 23, 42, 0.28);
      }

      .shopix-admin-tour-launcher:hover,
      .shopix-admin-tour-launcher:focus {
        background: linear-gradient(135deg, #111827, #334155);
        color: #f8fafc;
      }

      @media (max-width: 576px) {
        .shopix-admin-tour-launcher {
          left: 0.75rem;
          right: auto;
          bottom: 0.75rem;
          justify-content: center;
          width: 52px;
          height: 52px;
          min-width: 52px;
          padding: 0;
          gap: 0;
          border-radius: 999px;
        }

        .shopix-admin-tour-launcher .shopix-admin-tour-launcher-label {
          display: none;
        }

        .shopix-admin-tour-launcher .material-symbols-rounded {
          font-size: 22px !important;
        }
      }
    </style>
    @stack('styles')
</head>
<body class="bg-gray-100" id="d-body">
    @php
    $moduleHelpEnabled = (bool) config('module_help.enabled', false);
        $currentRouteName = optional(request()->route())->getName();
        $moduleHelpConfig = config('module_help', []);
    $moduleHelpAudience = $moduleHelpConfig['audience'] ?? [];
    $csvToList = static function ($value) {
      if (is_array($value)) {
        return array_values(array_filter(array_map(static fn ($item) => strtolower(trim((string) $item)), $value), static fn ($item) => $item !== ''));
      }

      $raw = trim((string) $value);
      if ($raw === '') {
        return [];
      }

      return array_values(array_filter(array_map(static fn ($item) => strtolower(trim((string) $item)), explode(',', $raw)), static fn ($item) => $item !== ''));
    };
    $csvToIntegerList = static function ($value) {
      if (is_array($value)) {
        return array_values(array_filter(array_map(static fn ($item) => (int) $item, $value), static fn ($item) => $item > 0));
      }

      $raw = trim((string) $value);
      if ($raw === '') {
        return [];
      }

      return array_values(array_filter(array_map(static fn ($item) => (int) trim((string) $item), explode(',', $raw)), static fn ($item) => $item > 0));
    };
    $authUser = auth()->user();
    $isGuestUser = !$authUser;
    $currentRoleName = strtolower(trim((string) optional(optional($authUser)->role)->name));
    if ($currentRoleName === '' && $authUser && isset($authUser->role_id)) {
      $currentRoleName = 'role:' . (int) $authUser->role_id;
    }
    $currentTenantId = (int) ($authUser->tenant_id ?? 0);
    $allowGuests = (bool) ($moduleHelpAudience['allow_guests'] ?? true);
    $roleAllowList = $csvToList($moduleHelpAudience['role_allow_list'] ?? []);
    $roleBlockList = $csvToList($moduleHelpAudience['role_block_list'] ?? []);
    $tenantAllowList = $csvToIntegerList($moduleHelpAudience['tenant_allow_list'] ?? []);
    $tenantBlockList = $csvToIntegerList($moduleHelpAudience['tenant_block_list'] ?? []);
    $moduleHelpAudienceAllowed = true;

    if ($isGuestUser && !$allowGuests) {
      $moduleHelpAudienceAllowed = false;
    }

    if ($moduleHelpAudienceAllowed && !$isGuestUser && !empty($roleAllowList) && !in_array($currentRoleName, $roleAllowList, true)) {
      $moduleHelpAudienceAllowed = false;
    }

    if ($moduleHelpAudienceAllowed && !$isGuestUser && !empty($roleBlockList) && in_array($currentRoleName, $roleBlockList, true)) {
      $moduleHelpAudienceAllowed = false;
    }

    if ($moduleHelpAudienceAllowed && !$isGuestUser && !empty($tenantAllowList) && !in_array($currentTenantId, $tenantAllowList, true)) {
      $moduleHelpAudienceAllowed = false;
    }

    if ($moduleHelpAudienceAllowed && !$isGuestUser && !empty($tenantBlockList) && in_array($currentTenantId, $tenantBlockList, true)) {
      $moduleHelpAudienceAllowed = false;
    }

    $moduleHelpEnabled = $moduleHelpEnabled && $moduleHelpAudienceAllowed;
        $moduleHelpRoute = $moduleHelpConfig['routes'][$currentRouteName] ?? [];
      $moduleHelp = $moduleHelpEnabled ? $moduleHelpRoute : [];
      if ($moduleHelpEnabled && empty($moduleHelp)) {
        $moduleHelp = [
          'title' => 'Ayuda: ' . str_replace('.', ' / ', (string) ($currentRouteName ?: 'Modulo')),
          'intro' => 'Recorrido contextual del modulo actual.',
        ];
      }
        $moduleWizard = $moduleHelp['wizard'] ?? [];
        $moduleTour = $moduleHelp['tour'] ?? [];
    @endphp
      <aside class="sidenav navbar navbar-vertical navbar-expand-xs fixed-start ms-0 d-lg-block bg-white sidebar-full-height" id="sidenav-main">
        @include('layouts.navbar')
      </aside>
    <main class="main-content position-relative border-radius-lg">
    @include('layouts.head')

        <div class="container-fluid pt-2">
          @if(session('warning'))
            <div class="alert alert-warning text-dark" role="alert">{{ session('warning') }}</div>
          @endif
          @if(session('success'))
            <div class="alert alert-success text-white" role="alert">{{ session('success') }}</div>
          @endif
          @if(session('error'))
            <div class="alert alert-danger text-white" role="alert">{{ session('error') }}</div>
          @endif
        </div>

        @yield('content')
    </main>

  @if($moduleHelpEnabled)
    <button type="button" class="shopix-admin-tour-launcher" id="shopixStartAdminModuleTourBtn" aria-label="Iniciar recorrido guiado">
      <i class="material-symbols-rounded" style="font-size:18px;">explore</i>
      <span class="shopix-admin-tour-launcher-label">Recorrido rapido</span>
    </button>
  @endif

    <!-- Scripts -->
    <script src="{{ asset('assets/js/core/popper.min.js') }}"></script>
    <script src="{{ asset('assets/js/core/bootstrap.min.js') }}"></script>
    <script src="{{ asset('assets/js/plugins/perfect-scrollbar.min.js') }}"></script>
    <script src="{{ asset('assets/js/material-dashboard.min.js?v=3.2.0') }}"></script>
    <script src="{{ asset('assets/js/navbar.js') }}"></script>
    @if($moduleHelpEnabled)
      <script>
        document.addEventListener('DOMContentLoaded', function () {
          const moduleHelp = @json($moduleHelp ?? []);
          const currentRouteName = @json($currentRouteName);
          const launchButton = document.getElementById('shopixStartAdminModuleTourBtn');
          const routeKey = currentRouteName || 'unknown_route';
          const seenKey = 'shopix_admin_tour_seen_' + routeKey;

          const adminTourBlueprints = {
            'dashboard': {
              tour: [
                { title: 'Resumen principal', description: 'Visualiza el estado general del negocio.', selector: '.dashboard-headline, .container-fluid' },
                { title: 'Indicadores', description: 'Revisa las metricas clave del periodo.', selector: '.card h4, .card h6' },
                { title: 'Accesos rapidos', description: 'Entra rapido a modulos operativos.', selector: '.card a[href]'}
              ]
            },
            'notifications.index': {
              tour: [
                { title: 'Listado de notificaciones', description: 'Consulta avisos y eventos recientes.', selector: 'table.table, .list-group, .card' },
                { title: 'Marcar como leida', description: 'Gestiona el estado de cada notificacion.', selector: 'form[action*="/notifications/"] button[type="submit"], button[data-action="read"]' }
              ]
            },
            'categories.index': {
              tour: [
                { title: 'Crear categoria', description: 'Registra una nueva categoria para catalogo.', selector: '#categoriesCreateTrigger, [data-bs-target="#createCategoryModal"]' },
                { title: 'Tabla de categorias', description: 'Edita o activa/inactiva categorias.', selector: 'table.table' }
              ]
            },
            'products.index': {
              tour: [
                { title: 'Filtro de categorias', description: 'Segmenta productos por categoria.', selector: '#categoriesContainer, .category-link' },
                { title: 'Buscador', description: 'Ubica productos rapidamente.', selector: '#searchProduct, #searchInput, input[type="search"]' },
                { title: 'Acciones de catalogo', description: 'Crea o importa productos del modulo.', selector: 'a[href="/createProduct"], [data-bs-target*="import"], #importCatalogForm' }
              ]
            },
            'createProductItem': {
              tour: [
                { title: 'Formulario de creacion', description: 'Aqui se construye el producto completo con datos generales e inventario.', selector: '#createProductForm, .product-builder, .builder-hero' },
                { title: 'Informacion general', description: 'Completa nombre, categoria, descripcion y descuento del producto.', selector: '#productName, #categorySelector, #productDescription, #productDiscount' },
                { title: 'Imagen principal del producto', description: 'Carga fotos generales del producto o usa IA para generarlas.', selector: '#productImages, #imagePreview, #openCreateProductAiBtn' },
                { title: 'Impuesto aplicable', description: 'Selecciona la alicuota de impuesto para este producto.', selector: '#taxCardsContainer, .selectable-tax, #taxInputs' },
                { title: 'Variantes del producto', description: 'Agrega y completa variantes con precio, stock, unidad e imagen propia.', selector: '#addVariantBtn, #variantContainer, #variantContainer .variant-row, #variantContainer input[name="variantName[]"], #variantContainer input[name="variantPrice[]"], #variantContainer input[name="variantStock[]"]', action: 'Haz clic en Agregar variante y completa al menos una variante valida.' },
                { title: 'Crear producto', description: 'Cuando todo este completo, finaliza el registro del producto.', selector: '#createProductForm button[type="submit"]', action: 'Haz clic en Crear producto para guardar.' }
              ]
            },
            'productItem': {
              tour: [
                { title: 'Detalle de producto', description: 'Revisa el producto antes de crear su nueva variante.', selector: '.product-detail-card, #mainImage' },
                { title: 'Pestana de variantes', description: 'Aqui se administran las presentaciones y stock del producto.', selector: '#productInventoryTabs, #variants-tab, #variants-tab-pane', action: 'Confirma que estas en la pestana Variantes.' },
                { title: 'Agregar variante', description: 'Inicia una variante nueva para este producto.', selector: '#addVariantBtn', action: 'Haz clic en Agregar Variante.' },
                { title: 'Completar datos', description: 'Completa los campos de la nueva variante: nombre, precio, stock y codigo de barras.', selector: '#newVariantContainer, #newVariantContainer .new-variant-row, [data-new-size], [data-new-price], [data-new-stock], [data-new-barcode]' },
                { title: 'Guardar variante nueva', description: 'Guarda todas las variantes nuevas cargadas en esta seccion.', selector: '#saveVariantsBtn', action: 'Haz clic en Guardar variantes nuevas para finalizar.' }
              ]
            },
            'users': {
              tour: [
                { title: 'Busqueda de usuarios', description: 'Filtra por nombre o correo.', selector: '#searchUser, #searchInput, input[type="search"]' },
                { title: 'Gestion de usuarios', description: 'Crea, edita y cambia estado de cuentas.', selector: '#userTableBody, table.table, .btn-edit-user, .toggle-status-btn' }
              ]
            },
            'paymentMethods.index': {
              tour: [
                { title: 'Monedas y tasas', description: 'Controla tasas de cambio activas.', selector: '#currentDollarRate, [data-bs-target*="Rate"], [data-bs-target*="rate"]' },
                { title: 'Metodos de pago', description: 'Registra y administra canales de cobro.', selector: '#createPaymentMethodForm, .btn-edit-method, table.table' }
              ]
            },
            'sales': {
              tour: [
                { title: 'Construccion de venta', description: 'Selecciona productos y cantidades.', selector: '#itemSelector, .product-item, .category-link' },
                { title: 'Cobro y cierre', description: 'Define metodos de pago y confirma la orden.', selector: '#paymentMethods, #purchaseForm, button[type="submit"]' }
              ]
            },
            'sales.orders': {
              tour: [
                { title: 'Historial de ordenes', description: 'Consulta ventas realizadas y su estatus.', selector: 'table.table' },
                { title: 'Acciones de orden', description: 'Abre detalle, reportes y documentos.', selector: 'a[href*="/sales/"], [data-bs-target*="report"], form[action*="sales-orders"]' }
              ]
            },
            'sales.showByOrder': {
              tour: [
                { title: 'Detalle de orden', description: 'Visualiza items, pagos y estatus de despacho.', selector: '.card, table.table' },
                { title: 'Funciones operativas', description: 'Ejecuta devoluciones, emision o cambios de estado.', selector: 'form[action*="/sales"], form[action*="/electronic"], button[type="submit"]' }
              ]
            },
            'purchase': {
              tour: [
                { title: 'Selección de variantes', description: 'Arma la entrada con cantidades y costos.', selector: '#itemSelector, table.table' },
                { title: 'Confirmacion de compra', description: 'Define proveedor, almacen y registra la entrada.', selector: '#warehouseId, #createOrder, #finalSummaryText' }
              ]
            },
            'purchase.orders': {
              tour: [
                { title: 'Ordenes de compra', description: 'Consulta entradas por fecha, proveedor y total.', selector: 'table.table' },
                { title: 'Detalle de compra', description: 'Abre la orden para auditoria completa.', selector: 'a[href*="/order/"]' }
              ]
            },
            'warehouses.index': {
              tour: [
                { title: 'Alta de almacen', description: 'Crea almacenes y estructura operativa.', selector: 'form[action*="warehouses"], input[name="name"]' },
                { title: 'Control de existencias', description: 'Revisa stock por almacen y variantes.', selector: 'table.table' }
              ]
            },
            'materials.index': {
              tour: [
                { title: 'Paquetes y materiales', description: 'Configura combos y listas de materiales.', selector: '#materialPackageForm, #materialsRows, table.table' },
                { title: 'Estado y codigos', description: 'Gestiona disponibilidad y generacion de codigos.', selector: 'form[action*="toggle-status"], form[action*="generate-codes"]' }
              ]
            },
            'providers.index': {
              tour: [
                { title: 'Registro de proveedor', description: 'Crea y actualiza proveedores del negocio.', selector: 'form, [data-bs-target*="Provider"], [data-bs-target*="provider"]' },
                { title: 'Listado de proveedores', description: 'Controla estado y acciones por fila.', selector: 'table.table, .toggle-status-btn' }
              ]
            },
            'accounts.payable.index': {
              tour: [
                { title: 'Deudas por pagar', description: 'Monitorea cuentas abiertas por proveedor.', selector: 'table.table, .card' },
                { title: 'Registro de pagos', description: 'Carga abonos o pagos totales.', selector: 'form[action*="/accounts-payable"], [data-bs-target*="payment"]' }
              ]
            },
            'accounts.receivable.index': {
              tour: [
                { title: 'Cartera por cobrar', description: 'Supervisa saldos pendientes de clientes.', selector: 'table.table, .card' },
                { title: 'Seguimiento de cobro', description: 'Gestiona estado y trazabilidad de pagos.', selector: 'a[href*="/sales/"], form[action*="receivable"]' }
              ]
            },
            'store-expenses.index': {
              tour: [
                { title: 'Registro de egresos', description: 'Documenta gastos operativos de sede.', selector: 'form' },
                { title: 'Historial de gastos', description: 'Revisa y ajusta gastos registrados.', selector: 'table.table' }
              ]
            },
            'reports.index': {
              tour: [
                { title: 'Centro de reportes', description: 'Selecciona reporte por area funcional.', selector: '.card, a[href*="/reports/"]' },
                { title: 'Exportaciones', description: 'Descarga reportes en formatos disponibles.', selector: 'a[href$="/pdf"], a[href$="/excel"]' }
              ]
            },
            'seller-commissions.index': {
              tour: [
                { title: 'Comisiones', description: 'Revisa montos por vendedor y estado de pago.', selector: 'table.table, .card' },
                { title: 'Acciones de comision', description: 'Marca pagos o ajusta porcentaje por vendedor.', selector: 'form[action*="mark-paid"], form[action*="/rate/"]' }
              ]
            },
            'appointments.index': {
              tour: [
                { title: 'Agenda de citas', description: 'Visualiza citas y su avance operativo.', selector: 'table.table, .card, .timeline' },
                { title: 'Workflow de atencion', description: 'Ejecuta acciones de confirmacion y cierre.', selector: 'form[action*="/workflow"], button[type="submit"]' }
              ]
            },
            'appointments.services.index': {
              tour: [
                { title: 'Catalogo de servicios', description: 'Crea y administra servicios de agenda.', selector: 'form, table.table' },
                { title: 'Estado de servicios', description: 'Activa, edita o desactiva servicios.', selector: 'form[action*="/appointments/services"], .toggle-status-btn' }
              ]
            },
            'projects.module.index': {
              tour: [
                { title: 'Modulos del area', description: 'Accede a nomina, proyectos y cotizaciones.', selector: 'a[href*="/nomina"], a[href*="/proyectos"], a[href*="/cotizaciones"], .card' }
              ]
            },
            'projects.module.payroll.index': {
              tour: [
                { title: 'Gestion de nomina', description: 'Administra equipo y pagos del personal.', selector: 'table.table, form[action*="/payrolls"], form[action*="/team-members"]' },
                { title: 'Comprobantes', description: 'Genera y consulta comprobantes de pago.', selector: 'a[href*="/comprobante"]' }
              ]
            },
            'projects.module.projects.index': {
              tour: [
                { title: 'Listado de proyectos', description: 'Supervisa proyectos activos y fases.', selector: 'table.table, .card' },
                { title: 'Alta de proyecto', description: 'Crea nuevos proyectos y parametros iniciales.', selector: 'form[action*="/proyectos"], [data-bs-target*="project"]' }
              ]
            },
            'projects.module.projects.show': {
              tour: [
                { title: 'Detalle del proyecto', description: 'Gestiona avances, fases y visibilidad.', selector: '.card, .progress, .badge' },
                { title: 'Tareas y activos', description: 'Registra tareas y evidencia del proyecto.', selector: 'form[action*="/tasks"], form[action*="/assets"], table.table' }
              ]
            },
            'projects.module.quotations.index': {
              tour: [
                { title: 'Cotizaciones', description: 'Crea propuestas comerciales y realiza conversiones.', selector: 'form[action*="/cotizaciones"], table.table' },
                { title: 'Conversion de cotizacion', description: 'Convierte a proyecto, venta o inventario.', selector: 'form[action*="to-project"], form[action*="to-sale"], form[action*="to-inventory-entry"]' }
              ]
            },
            'electronic.documents.index': {
              tour: [
                { title: 'Bandeja electronica', description: 'Monitorea estado de documentos fiscales.', selector: 'table.table, .card' },
                { title: 'Acciones de recuperacion', description: 'Reintenta documentos con error cuando aplique.', selector: 'form[action*="/retry"], button[type="submit"]' }
              ]
            },
            'documentation.index': {
              tour: [
                { title: 'Documentacion tecnica', description: 'Consulta manuales y guias del sistema.', selector: 'table.table, .list-group, a[href*="/documentation/download/"]' }
              ]
            }
          };

          function resolveDynamicRouteHelp(routeName) {
            const exact = adminTourBlueprints[routeName] || null;
            if (exact) {
              return exact;
            }

            if (routeName.startsWith('reports.')) {
              return {
                tour: [
                  { title: 'Reporte solicitado', description: 'Aqui ejecutas la exportacion del reporte especifico.', selector: 'form, button[type="submit"], a[href$="/pdf"], a[href$="/excel"], .card' }
                ]
              };
            }

            if (routeName.startsWith('withholdings.')) {
              return {
                tour: [
                  { title: 'Retenciones', description: 'Gestiona certificados, exportaciones y sincronizacion fiscal.', selector: 'table.table, form, button[type="submit"], a[href*="withholdings"]' }
                ]
              };
            }

            if (routeName.startsWith('sales.')) {
              return {
                tour: [
                  { title: 'Operacion de ventas', description: 'Ejecuta acciones de venta segun el estado del documento.', selector: 'table.table, .card, form[action*="sales"], a[href*="sales"]' }
                ]
              };
            }

            if (routeName.startsWith('tenant.')) {
              return {
                tour: [
                  { title: 'Gestion de tenant', description: 'Administra configuracion y operacion del tenant actual.', selector: 'form, table.table, .card, button[type="submit"]' }
                ]
              };
            }

            return {
              tour: [
                { title: 'Flujo del modulo', description: 'Revisa las acciones principales disponibles en esta pantalla.', selector: 'main, form, table.table, button[type="submit"], a[href]' }
              ]
            };
          }

          const dynamicHelp = resolveDynamicRouteHelp(routeKey);
          const wizardSteps = Array.isArray(moduleHelp.wizard) && moduleHelp.wizard.length
            ? moduleHelp.wizard
            : (Array.isArray(dynamicHelp.wizard) ? dynamicHelp.wizard : []);
          const tourSteps = Array.isArray(moduleHelp.tour) && moduleHelp.tour.length
            ? moduleHelp.tour
            : (Array.isArray(dynamicHelp.tour) ? dynamicHelp.tour : []);

          function findTarget(selector) {
            if (!selector) {
              return null;
            }

            try {
              return document.querySelector(selector);
            } catch (error) {
              return null;
            }
          }

          function buildDriverSteps(mode) {
            const source = mode === 'wizard'
              ? (wizardSteps.length ? wizardSteps : tourSteps)
              : (tourSteps.length ? tourSteps : wizardSteps);

            return source
              .filter(function (step) {
                return Boolean(step && step.selector && findTarget(step.selector));
              })
              .map(function (step) {
                const actionText = step.action ? '<br><br><strong>Accion:</strong> ' + step.action : '';
                return {
                  element: step.selector,
                  popover: {
                    title: step.title || 'Paso guiado',
                    description: (step.description || 'Sigue este paso para continuar.') + actionText,
                    side: 'bottom',
                    align: 'start',
                  }
                };
              });
          }

          function startDriverTour(mode, persistSeen) {
            const driverFactory = window.driver?.js?.driver || window.driver;
            if (typeof driverFactory !== 'function') {
              return;
            }

            const steps = buildDriverSteps(mode);
            if (!steps.length) {
              return;
            }

            const tour = driverFactory({
              showProgress: true,
              allowClose: true,
              animate: true,
              overlayColor: 'rgba(15, 23, 42, 0.72)',
              nextBtnText: 'Siguiente',
              prevBtnText: 'Anterior',
              doneBtnText: 'Listo',
              showButtons: ['next', 'previous', 'close'],
              steps: steps,
            });

            tour.drive();
            if (persistSeen) {
              localStorage.setItem(seenKey, '1');
            }
          }

          if (launchButton) {
            launchButton.addEventListener('click', function () {
              startDriverTour('tour', true);
            });
          }
        });
      </script>
    @endif
    <script>
      window.shopixRequestActionReason = function (message) {
        const promptMessage = message || 'Indica el motivo de esta accion:';
        const value = window.prompt(promptMessage, '');
        if (value === null) {
          return null;
        }

        const trimmed = value.trim();
        if (!trimmed) {
          window.alert('Debes indicar un motivo para continuar.');
          return null;
        }

        return trimmed;
      };

      window.shopixBindReasonFormPrompts = function () {
        document.querySelectorAll('form[data-requires-action-reason="true"]').forEach((form) => {
          if (form.dataset.reasonBound === 'true') {
            return;
          }

          form.dataset.reasonBound = 'true';
          form.addEventListener('submit', (event) => {
            const hiddenFieldName = form.dataset.reasonField || 'action_reason';
            let hiddenField = form.querySelector(`input[name="${hiddenFieldName}"]`);
            if (!hiddenField) {
              hiddenField = document.createElement('input');
              hiddenField.type = 'hidden';
              hiddenField.name = hiddenFieldName;
              form.appendChild(hiddenField);
            }

            if (hiddenField.value && hiddenField.value.trim() !== '') {
              return;
            }

            const reason = window.shopixRequestActionReason(form.dataset.reasonPrompt || 'Indica el motivo de esta accion:');
            if (!reason) {
              event.preventDefault();
              return;
            }

            hiddenField.value = reason;
          });
        });
      };

      document.addEventListener('DOMContentLoaded', () => {
        window.shopixBindReasonFormPrompts();
      });

      window.shopixNormalizeEditableDecimalValue = function (value) {
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
      };

      window.shopixParseDecimalInput = function (value) {
        const normalized = window.shopixNormalizeEditableDecimalValue(value).numeric;
        const parsed = Number.parseFloat(normalized);
        return Number.isFinite(parsed) ? parsed : null;
      };

      window.shopixFormatDecimalInputValue = function (value, places = 2) {
        const numeric = Number(value);
        const fractionDigits = Number.isInteger(places) && places >= 0 ? places : 2;
        return new Intl.NumberFormat('en-US', {
          minimumFractionDigits: fractionDigits,
          maximumFractionDigits: fractionDigits,
        }).format(Number.isFinite(numeric) ? numeric : 0);
      };

      window.shopixActivateDecimalInputs = function (root = document) {
        root.querySelectorAll('input[data-decimal-friendly="true"]').forEach((input) => {
          if (input.dataset.decimalFriendlyReady === '1') {
            return;
          }

          input.dataset.decimalFriendlyReady = '1';
          const step = String(input.getAttribute('step') || '0.01');
          if (!input.dataset.decimalPlaces) {
            input.dataset.decimalPlaces = step.includes('.') ? String(step.split('.')[1].length) : '2';
          }

          input.type = 'text';
          input.setAttribute('inputmode', 'decimal');
          input.setAttribute('autocomplete', 'off');

          const parsed = window.shopixParseDecimalInput(input.value);
          if (parsed !== null) {
            input.value = window.shopixFormatDecimalInputValue(parsed, Number(input.dataset.decimalPlaces || 2));
          }
        });
      };

      document.addEventListener('DOMContentLoaded', () => {
        window.shopixActivateDecimalInputs();
      });

      document.addEventListener('focusin', (event) => {
        const input = event.target.closest('input[data-decimal-friendly="true"]');
        if (!input) {
          return;
        }

        const normalized = window.shopixNormalizeEditableDecimalValue(input.value).text;
        if (normalized && input.value !== normalized) {
          input.value = normalized;
        }
      });

      document.addEventListener('input', (event) => {
        const input = event.target.closest('input[data-decimal-friendly="true"]');
        if (!input) {
          return;
        }

        const selectionStart = input.selectionStart ?? String(input.value || '').length;
        const beforeCursor = String(input.value || '').slice(0, selectionStart);
        const normalizedValue = window.shopixNormalizeEditableDecimalValue(input.value);
        const normalizedBeforeCursor = window.shopixNormalizeEditableDecimalValue(beforeCursor);

        if (!normalizedValue.text) {
          input.value = '';
          return;
        }

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
      });

      document.addEventListener('blur', (event) => {
        const input = event.target.closest('input[data-decimal-friendly="true"]');
        if (!input) {
          return;
        }

        if (!String(input.value || '').trim()) {
          input.value = '';
          return;
        }

        const parsed = window.shopixParseDecimalInput(input.value);
        if (parsed === null) {
          input.value = '';
          return;
        }

        input.value = window.shopixFormatDecimalInputValue(parsed, Number(input.dataset.decimalPlaces || 2));
      }, true);

      document.addEventListener('submit', (event) => {
        const form = event.target;
        if (!(form instanceof HTMLFormElement)) {
          return;
        }

        form.querySelectorAll('input[data-decimal-friendly="true"]').forEach((input) => {
          const normalized = window.shopixNormalizeEditableDecimalValue(input.value).numeric;
          input.value = normalized;
        });
      }, true);

      (function () {
        const nativeFetch = window.fetch ? window.fetch.bind(window) : null;
        if (!nativeFetch) {
          return;
        }

        const csrfMeta = document.querySelector('meta[name="csrf-token"]');
        const loginUrl = @json(route('login'));

        window.fetch = async function (input, init) {
          const response = await nativeFetch(input, init);
          if (response.status !== 419) {
            return response;
          }

          let payload = {};
          try {
            payload = await response.clone().json();
          } catch (error) {
            payload = {};
          }

          if (csrfMeta && payload.csrf_token) {
            csrfMeta.setAttribute('content', payload.csrf_token);
          }

          const targetUrl = payload.login_url || loginUrl;
          const message = payload.message || 'Tu sesion vencio. Inicia sesion nuevamente.';

          window.dispatchEvent(new CustomEvent('shopix:session-expired', {
            detail: {
              message,
              targetUrl,
              payload,
            },
          }));

          window.alert(message);
          window.location.assign(targetUrl);

          return response;
        };
      })();

      (function () {
        const DRAG_ATTR = 'data-shopix-drag-scroll-bound';

        function hasHorizontalOverflow(element) {
          return (element.scrollWidth - element.clientWidth) > 2;
        }

        function findScrollableContainer(table) {
          let current = table.parentElement;

          while (current && current !== document.body) {
            const style = window.getComputedStyle(current);
            const overflowX = (style.overflowX || '').toLowerCase();

            if ((overflowX === 'auto' || overflowX === 'scroll') && hasHorizontalOverflow(current)) {
              return current;
            }

            current = current.parentElement;
          }

          return null;
        }

        function ensureWrapper(table) {
          const existingContainer = findScrollableContainer(table);
          if (existingContainer) {
            return existingContainer;
          }

          const parent = table.parentElement;
          if (!parent) {
            return null;
          }

          const wrap = document.createElement('div');
          wrap.className = 'shopix-drag-table-wrap';
          parent.insertBefore(wrap, table);
          wrap.appendChild(table);

          return wrap;
        }

        function bindDragToContainer(container) {
          if (!container || container.getAttribute(DRAG_ATTR) === '1') {
            return;
          }

          container.setAttribute(DRAG_ATTR, '1');
          container.classList.add('shopix-drag-scroll');

          let dragging = false;
          let startX = 0;
          let startScrollLeft = 0;

          container.addEventListener('mousedown', function (event) {
            if (event.button !== 0) {
              return;
            }

            if (!hasHorizontalOverflow(container)) {
              return;
            }

            dragging = true;
            startX = event.pageX;
            startScrollLeft = container.scrollLeft;
            container.classList.add('is-dragging');
          });

          container.addEventListener('mouseleave', function () {
            dragging = false;
            container.classList.remove('is-dragging');
          });

          container.addEventListener('mouseup', function () {
            dragging = false;
            container.classList.remove('is-dragging');
          });

          container.addEventListener('mousemove', function (event) {
            if (!dragging) {
              return;
            }

            event.preventDefault();
            const delta = event.pageX - startX;
            container.scrollLeft = startScrollLeft - delta;
          });
        }

        function bindTables(root) {
          const scope = root || document;
          const tables = scope.querySelectorAll('table');

          tables.forEach(function (table) {
            const container = ensureWrapper(table);
            bindDragToContainer(container);
          });
        }

        document.addEventListener('DOMContentLoaded', function () {
          bindTables(document);

          const observer = new MutationObserver(function (mutations) {
            mutations.forEach(function (mutation) {
              mutation.addedNodes.forEach(function (node) {
                if (!(node instanceof HTMLElement)) {
                  return;
                }

                if (node.matches('table')) {
                  bindTables(node.parentElement || document);
                  return;
                }

                if (node.querySelector('table')) {
                  bindTables(node);
                }
              });
            });
          });

          observer.observe(document.body, { childList: true, subtree: true });
        });
      })();
    </script>
    <script>
      (function () {
        const PDF_NOTICE_ID = 'shopix-pdf-notice';
        let hideTimer = null;

        function ensureNotice() {
          let notice = document.getElementById(PDF_NOTICE_ID);
          if (notice) {
            return notice;
          }

          notice = document.createElement('div');
          notice.id = PDF_NOTICE_ID;
          notice.setAttribute('role', 'status');
          notice.setAttribute('aria-live', 'polite');
          notice.style.position = 'fixed';
          notice.style.right = '16px';
          notice.style.bottom = '16px';
          notice.style.zIndex = '1085';
          notice.style.minWidth = '280px';
          notice.style.maxWidth = '420px';
          notice.style.padding = '12px 14px';
          notice.style.borderRadius = '10px';
          notice.style.boxShadow = '0 10px 30px rgba(15, 23, 42, 0.22)';
          notice.style.fontSize = '14px';
          notice.style.fontWeight = '600';
          notice.style.display = 'none';
          notice.style.background = '#0f172a';
          notice.style.color = '#f8fafc';

          document.body.appendChild(notice);
          return notice;
        }

        function showNotice(message, tone) {
          const notice = ensureNotice();
          clearTimeout(hideTimer);

          const tones = {
            loading: { bg: '#0f172a', color: '#f8fafc' },
            success: { bg: '#14532d', color: '#ecfdf5' },
            error: { bg: '#7f1d1d', color: '#fef2f2' }
          };

          const selected = tones[tone] || tones.loading;
          notice.style.background = selected.bg;
          notice.style.color = selected.color;
          notice.textContent = message;
          notice.style.display = 'block';

          if (tone !== 'loading') {
            hideTimer = window.setTimeout(function () {
              notice.style.display = 'none';
            }, 5000);
          }
        }

        function looksLikePdfUrl(url) {
          if (!url || typeof url !== 'string') {
            return false;
          }

          const normalized = url.toLowerCase();
          return normalized.includes('/pdf') || normalized.includes('/pdfs/');
        }

        function launchPdfRequest(url) {
          showNotice('Generando PDF. Estamos optimizando la carga...', 'loading');

          const opened = window.open(url, '_blank', 'noopener,noreferrer');
          if (!opened) {
            showNotice('No se pudo abrir una nueva pestaña. Habilita popups para este sitio e intenta nuevamente.', 'error');
            return;
          }

          window.setTimeout(function () {
            showNotice('Solicitud PDF enviada. Revisa la nueva pestaña o la descarga del navegador.', 'success');
          }, 800);
        }

        function formToUrl(form) {
          const action = form.getAttribute('action') || window.location.href;
          const method = (form.getAttribute('method') || 'GET').toUpperCase();
          if (method !== 'GET') {
            return null;
          }

          const data = new FormData(form);
          const params = new URLSearchParams();
          data.forEach(function (value, key) {
            if (typeof value === 'string') {
              params.append(key, value);
            }
          });

          const query = params.toString();
          if (!query) {
            return action;
          }

          return action + (action.includes('?') ? '&' : '?') + query;
        }

        document.addEventListener('click', function (event) {
          const link = event.target.closest('a[href]');
          if (!link) {
            return;
          }

          if (event.button !== 0 || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) {
            return;
          }

          const href = link.getAttribute('href') || '';
          if (!looksLikePdfUrl(href)) {
            return;
          }

          if (link.hasAttribute('download')) {
            return;
          }

          event.preventDefault();
          event.stopPropagation();
          launchPdfRequest(href);
        }, true);

        document.addEventListener('submit', function (event) {
          const form = event.target;
          if (!(form instanceof HTMLFormElement)) {
            return;
          }

          const action = form.getAttribute('action') || '';
          if (!looksLikePdfUrl(action)) {
            return;
          }

          const url = formToUrl(form);
          if (!url) {
            return;
          }

          event.preventDefault();
          event.stopPropagation();
          launchPdfRequest(url);
        }, true);
      })();
    </script>
    <script src="https://cdn.jsdelivr.net/npm/driver.js@1.0.1/dist/driver.js.iife.js"></script>
    @stack('scripts')
</body>
</html>
