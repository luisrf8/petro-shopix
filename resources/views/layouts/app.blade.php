<!DOCTYPE html>
<html lang="es">
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
  <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Mi App')</title>
    <link rel="stylesheet" href="{{ asset('assets/css/material-dashboard.min.css') }}">
    <style>
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
          font-size: 0.72rem;
          padding: 0.55rem 0.45rem;
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
    </style>
    @stack('styles')
</head>
<body class="bg-gray-100" id="d-body">
    @php
      $moduleHelpEnabled = (bool) config('module_help.enabled', false);
        $currentRouteName = optional(request()->route())->getName();
        $moduleHelpConfig = config('module_help', []);
        $moduleHelpFallback = $moduleHelpConfig['fallback'] ?? [];
      $moduleHelp = $moduleHelpEnabled
        ? ($moduleHelpConfig['routes'][$currentRouteName] ?? $moduleHelpFallback)
        : [];
        $moduleWizard = $moduleHelp['wizard'] ?? [];
        $moduleTour = $moduleHelp['tour'] ?? [];
        $helpPreferenceUrls = [
            'show' => route('help.preferences.show'),
            'global' => route('help.preferences.global'),
            'route' => route('help.preferences.route'),
        ];
    @endphp
      <aside class="sidenav navbar navbar-vertical navbar-expand-xs fixed-start ms-0 d-lg-block bg-white sidebar-full-height" id="sidenav-main">
        @include('layouts.navbar')
      </aside>
    <main class="main-content position-relative max-height-vh-100 h-100 border-radius-lg ">
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

  @if(!empty($moduleHelp))
    {{--
    <button
      class="btn btn-dark rounded-circle p-0 d-flex align-items-center justify-content-center"
      type="button"
      data-bs-toggle="offcanvas"
      data-bs-target="#moduleHelpPanel"
      aria-controls="moduleHelpPanel"
      style="position: fixed; right: 1rem; bottom: 1rem; width: 52px; height: 52px; z-index: 1052;"
      title="Ayuda del modulo"
    >
      <i class="material-symbols-rounded text-white">help</i>
    </button>
    --}}

    <div class="offcanvas offcanvas-end" tabindex="-1" id="moduleHelpPanel" aria-labelledby="moduleHelpPanelLabel">
      <div class="offcanvas-header border-bottom">
        <h5 class="offcanvas-title" id="moduleHelpPanelLabel">{{ $moduleHelp['title'] ?? 'Ayuda del modulo' }}</h5>
        <button type="button" class="btn-close text-reset" data-bs-dismiss="offcanvas" aria-label="Close"></button>
      </div>
      <div class="offcanvas-body">
        @if(!empty($moduleHelp['intro']))
          <p class="text-sm text-dark mb-4">{{ $moduleHelp['intro'] }}</p>
        @endif

        <div class="mb-4 d-grid gap-2">
          <button type="button" class="btn btn-dark mb-0" id="startModuleWizard" @disabled(empty($moduleWizard))>
            Iniciar Wizard con Stepper
          </button>
          <button type="button" class="btn btn-outline-dark mb-0" id="startModuleTour" @disabled(empty($moduleTour))>
            Marcar Pasos en Pantalla
          </button>
          <div class="form-check mt-2">
            <input class="form-check-input" type="checkbox" id="disableAutoHelpRoute">
            <label class="form-check-label text-sm" for="disableAutoHelpRoute">
              No volver a mostrar ayuda automatica en este modulo
            </label>
          </div>
          <div class="form-check">
            <input class="form-check-input" type="checkbox" id="disableAutoHelpGlobal">
            <label class="form-check-label text-sm" for="disableAutoHelpGlobal">
              No volver a mostrar ayuda automatica en todo el sistema
            </label>
          </div>
          @if(empty($moduleWizard) && empty($moduleTour))
            <small class="text-muted">Este modulo aun no tiene pasos guiados configurados.</small>
          @endif
        </div>

        @foreach(($moduleHelp['sections'] ?? []) as $section)
          <div class="mb-4">
            <h6 class="text-uppercase text-xs text-dark font-weight-bolder mb-2">{{ $section['heading'] ?? 'Detalle' }}</h6>
            <ul class="ps-3 mb-0">
              @foreach(($section['items'] ?? []) as $item)
                <li class="text-sm text-secondary mb-2">{{ $item }}</li>
              @endforeach
            </ul>
          </div>
        @endforeach
      </div>
    </div>
  @endif

  <div class="module-wizard-backdrop" id="moduleWizardBackdrop"></div>
  <div class="module-wizard-focus" id="moduleWizardFocus"></div>
  <div class="card shadow module-wizard-tooltip" id="moduleWizardTooltip">
    <div class="card-body p-3">
      <div class="d-flex justify-content-between align-items-start mb-2">
        <div>
          <div class="text-xs text-muted" id="wizardInlineProgress"></div>
          <h6 class="mb-0" id="wizardTooltipTitle">Paso guiado</h6>
        </div>
        <button type="button" class="btn btn-sm btn-outline-secondary mb-0" id="closeModuleWizard">Cerrar</button>
      </div>
      <div id="wizardInlineSteps" class="d-flex flex-wrap gap-2 mb-2"></div>
      <p class="text-sm mb-2" id="wizardInlineDescription"></p>
      <div class="alert alert-light border text-sm py-2 px-3 mb-2" id="wizardInlineAction" role="status"></div>
      <div class="d-flex justify-content-between">
        <button type="button" class="btn btn-outline-secondary btn-sm mb-0" id="wizardPrevStep">Anterior</button>
        <button type="button" class="btn btn-dark btn-sm mb-0" id="wizardNextStep">Siguiente</button>
      </div>
    </div>
  </div>

    <!-- Scripts -->
    <script src="{{ asset('assets/js/core/popper.min.js') }}"></script>
    <script src="{{ asset('assets/js/core/bootstrap.min.js') }}"></script>
    <script src="{{ asset('assets/js/material-dashboard.min.js?v=3.2.0') }}"></script>
    <script src="{{ asset('assets/js/navbar.js') }}"></script>
    @if(!empty($moduleHelp))
      <script>
        document.addEventListener('DOMContentLoaded', function () {
          const moduleHelp = @json($moduleHelp);
          const currentRouteName = @json($currentRouteName);
          const wizardSteps = Array.isArray(moduleHelp.wizard) ? moduleHelp.wizard : [];
          const tourSteps = Array.isArray(moduleHelp.tour) ? moduleHelp.tour : [];
          const canGuide = wizardSteps.length > 0 || tourSteps.length > 0;

          const routeKey = currentRouteName || 'unknown_route';
          const routeDisableKey = 'shopix_help_disable_route_' + routeKey;
          const globalDisableKey = 'shopix_help_disable_global';
          const promptedSessionKey = 'shopix_help_prompted_' + routeKey;
          const preferenceUrls = @json($helpPreferenceUrls);
          const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';

          const disableRouteCheckbox = document.getElementById('disableAutoHelpRoute');
          const disableGlobalCheckbox = document.getElementById('disableAutoHelpGlobal');

          const wizardBtn = document.getElementById('startModuleWizard');
          const tourBtn = document.getElementById('startModuleTour');
          const offcanvasEl = document.getElementById('moduleHelpPanel');
          const offcanvasInstance = offcanvasEl ? bootstrap.Offcanvas.getOrCreateInstance(offcanvasEl) : null;

          const wizardBackdrop = document.getElementById('moduleWizardBackdrop');
          const wizardFocus = document.getElementById('moduleWizardFocus');
          const wizardTooltip = document.getElementById('moduleWizardTooltip');
          const wizardProgress = document.getElementById('wizardInlineProgress');
          const wizardTitle = document.getElementById('wizardTooltipTitle');
          const wizardDescription = document.getElementById('wizardInlineDescription');
          const wizardAction = document.getElementById('wizardInlineAction');
          const wizardStepChips = document.getElementById('wizardInlineSteps');
          const wizardPrev = document.getElementById('wizardPrevStep');
          const wizardNext = document.getElementById('wizardNextStep');
          const wizardClose = document.getElementById('closeModuleWizard');

          let wizardIndex = 0;
          let wizardActive = false;
          let activeSteps = [];
          let currentTarget = null;
          let currentInlineNote = null;

          function isRouteAutoHelpDisabled() {
            return localStorage.getItem(routeDisableKey) === '1';
          }

          function isGlobalAutoHelpDisabled() {
            return localStorage.getItem(globalDisableKey) === '1';
          }

          function setRouteDisabledLocal(disabled) {
            if (disabled) {
              localStorage.setItem(routeDisableKey, '1');
            } else {
              localStorage.removeItem(routeDisableKey);
            }
          }

          function setGlobalDisabledLocal(disabled) {
            if (disabled) {
              localStorage.setItem(globalDisableKey, '1');
            } else {
              localStorage.removeItem(globalDisableKey);
            }
          }

          async function saveGlobalPreference(disabled) {
            try {
              await fetch(preferenceUrls.global, {
                method: 'POST',
                headers: {
                  'Content-Type': 'application/json',
                  'X-CSRF-TOKEN': csrfToken,
                  'Accept': 'application/json',
                  'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ disabled: disabled ? 1 : 0 }),
              });
            } catch (error) {
              console.warn('No se pudo guardar preferencia global en servidor.', error);
            }
          }

          async function saveRoutePreference(disabled) {
            try {
              await fetch(preferenceUrls.route, {
                method: 'POST',
                headers: {
                  'Content-Type': 'application/json',
                  'X-CSRF-TOKEN': csrfToken,
                  'Accept': 'application/json',
                  'X-Requested-With': 'XMLHttpRequest',
                },
                body: JSON.stringify({ route: routeKey, disabled: disabled ? 1 : 0 }),
              });
            } catch (error) {
              console.warn('No se pudo guardar preferencia de modulo en servidor.', error);
            }
          }

          async function hydratePreferencesFromServer() {
            try {
              const response = await fetch(preferenceUrls.show, {
                method: 'GET',
                headers: {
                  'Accept': 'application/json',
                  'X-Requested-With': 'XMLHttpRequest',
                },
              });

              if (!response.ok) {
                return;
              }

              const data = await response.json();
              const disableGlobal = Boolean(data.disable_global);
              const disabledRoutes = data.disabled_routes && typeof data.disabled_routes === 'object'
                ? data.disabled_routes
                : {};
              const disableRoute = Boolean(disabledRoutes[routeKey]);

              setGlobalDisabledLocal(disableGlobal);
              setRouteDisabledLocal(disableRoute);

              if (disableGlobalCheckbox) {
                disableGlobalCheckbox.checked = disableGlobal;
              }

              if (disableRouteCheckbox) {
                disableRouteCheckbox.checked = disableRoute;
              }
            } catch (error) {
              console.warn('No se pudieron cargar preferencias de ayuda desde servidor.', error);
            }
          }

          if (disableRouteCheckbox) {
            disableRouteCheckbox.checked = isRouteAutoHelpDisabled();
            disableRouteCheckbox.addEventListener('change', async function () {
              const disabled = disableRouteCheckbox.checked;
              setRouteDisabledLocal(disabled);
              await saveRoutePreference(disabled);
            });
          }

          if (disableGlobalCheckbox) {
            disableGlobalCheckbox.checked = isGlobalAutoHelpDisabled();
            disableGlobalCheckbox.addEventListener('change', async function () {
              const disabled = disableGlobalCheckbox.checked;
              setGlobalDisabledLocal(disabled);
              await saveGlobalPreference(disabled);
            });
          }

          function findTarget(selector) {
            if (!selector) return null;
            try {
              return document.querySelector(selector);
            } catch (error) {
              return null;
            }
          }

          function clearCurrentVisuals() {
            if (currentTarget) {
              currentTarget.classList.remove('module-step-current');
            }

            if (currentInlineNote && currentInlineNote.parentNode) {
              currentInlineNote.remove();
            }

            currentInlineNote = null;
            currentTarget = null;
          }

          function buildStepInfo(step, index) {
            const info = document.createElement('div');
            info.className = 'module-step-inline-note';
            info.innerHTML = `
              <div class="fw-bold text-sm mb-1">Paso ${index + 1}: ${step.title || 'Paso guiado'}</div>
              <p class="mb-1 text-sm">${step.description || ''}</p>
              <p class="mb-0 text-sm"><strong>Accion:</strong> ${step.action || 'Completa este paso para continuar.'}</p>
            `;
            return info;
          }

          function getAvailableStep(index) {
            for (let i = index; i < activeSteps.length; i += 1) {
              const target = findTarget(activeSteps[i].selector || '');
              if (target) {
                return { index: i, step: activeSteps[i], target: target };
              }
            }

            for (let i = index - 1; i >= 0; i -= 1) {
              const target = findTarget(activeSteps[i].selector || '');
              if (target) {
                return { index: i, step: activeSteps[i], target: target };
              }
            }

            return null;
          }

          function positionTooltip(targetRect) {
            if (!wizardTooltip) return;

            // const tooltipWidth = Math.min(
            //   wizardTooltip.offsetWidth || 390,
            //   Math.max(260, window.innerWidth - 16)
            // );
            // const tooltipHeight = wizardTooltip.offsetHeight || 240;
            let top = targetRect.top;
            let left = targetRect.right + 8;

            // Prefer right side of highlight, then left side, then below as fallback.
            // if (left + tooltipWidth > window.innerWidth - 8) {
            //   left = targetRect.left - tooltipWidth - 8;
            // }

            // if (left < 8) {
            //   left = targetRect.left;
            //   top = targetRect.bottom;
            // }

            // if (left + tooltipWidth > window.innerWidth - 8) {
            //   left = window.innerWidth - tooltipWidth - 8;
            // }

            // if (top + tooltipHeight > window.innerHeight - 8) {
            //   top = Math.max(8, window.innerHeight - tooltipHeight - 8);
            // }

            // if (top < 8) {
            //   top = 8;
            // }

            // wizardTooltip.style.top = top + 'px';
            // wizardTooltip.style.left = left + 'px';
          }

          function syncSpotlightPosition() {
            if (!wizardActive || !currentTarget) return;

            const rect = currentTarget.getBoundingClientRect();
            const padding = 8;

            // if (wizardFocus) {
            //   wizardFocus.style.top = Math.max(0, rect.top - padding) + 'px';
            //   wizardFocus.style.left = Math.max(0, rect.left - padding) + 'px';
            //   wizardFocus.style.width = Math.max(0, rect.width + (padding * 2)) + 'px';
            //   wizardFocus.style.height = Math.max(0, rect.height + (padding * 2)) + 'px';
            // }

            if (wizardTooltip) {
              positionTooltip(rect);
            }
          }

          function renderStepChips() {
            if (!wizardStepChips) return;
            wizardStepChips.innerHTML = '';

            activeSteps.forEach(function (step, idx) {
              const target = findTarget(step.selector || '');
              const chip = document.createElement('button');
              chip.type = 'button';
              chip.className = 'module-step-chip' + (idx === wizardIndex ? ' active' : '');
              chip.textContent = String(idx + 1);
              chip.title = step.title || ('Paso ' + (idx + 1));
              chip.disabled = !target;
              chip.addEventListener('click', function () {
                wizardIndex = idx;
                renderWizardOverlay(true);
              });
              wizardStepChips.appendChild(chip);
            });
          }

          function renderWizardOverlay(scrollToCurrent) {
            if (!activeSteps.length) return;

            clearCurrentVisuals();

            const available = getAvailableStep(wizardIndex);
            if (!available) {
              closeWizardOverlay();
              return;
            }

            wizardIndex = available.index;
            const currentStep = available.step;
            const target = available.target;

            wizardProgress.textContent = 'Paso ' + (wizardIndex + 1) + ' de ' + activeSteps.length;
            wizardTitle.textContent = currentStep.title || 'Paso guiado';
            wizardDescription.textContent = currentStep.description || '';
            wizardAction.textContent = currentStep.action || 'Completa este paso para continuar.';
            wizardPrev.disabled = wizardIndex === 0;
            wizardNext.textContent = wizardIndex === activeSteps.length - 1 ? 'Finalizar' : 'Siguiente';

            if (scrollToCurrent) {
              target.scrollIntoView({ behavior: 'smooth', block: 'center' });
            }

            currentTarget = target;
            currentTarget.classList.add('module-step-current');

            const inlineNote = buildStepInfo(currentStep, wizardIndex);
            currentTarget.insertAdjacentElement('afterend', inlineNote);
            currentInlineNote = inlineNote;

            if (wizardBackdrop) wizardBackdrop.style.display = 'block';
            if (wizardFocus) {
              wizardFocus.style.display = 'block';
            }
            if (wizardTooltip) {
              wizardTooltip.style.display = 'block';
            }
            syncSpotlightPosition();

            if (scrollToCurrent) {
              setTimeout(syncSpotlightPosition, 260);
              setTimeout(syncSpotlightPosition, 520);
            }

            renderStepChips();
          }

          function startWizardOverlay(mode) {
            activeSteps = mode === 'tour'
              ? (tourSteps.length ? tourSteps : wizardSteps)
              : (wizardSteps.length ? wizardSteps : tourSteps);

            if (!activeSteps.length) return;

            wizardActive = true;
            wizardIndex = 0;

            if (offcanvasInstance) {
              offcanvasInstance.hide();
            }

            renderWizardOverlay(true);
          }

          function closeWizardOverlay() {
            wizardActive = false;
            clearCurrentVisuals();
            if (wizardBackdrop) wizardBackdrop.style.display = 'none';
            if (wizardFocus) wizardFocus.style.display = 'none';
            if (wizardTooltip) {
              wizardTooltip.style.display = 'none';
              wizardTooltip.style.top = '-9999px';
              wizardTooltip.style.left = '-9999px';
            }
          }

          if (wizardBtn) {
            wizardBtn.addEventListener('click', function () {
              startWizardOverlay('wizard');
            });
          }

          if (tourBtn) {
            tourBtn.addEventListener('click', function () {
              startWizardOverlay('tour');
            });
          }

          if (wizardPrev) {
            wizardPrev.addEventListener('click', function () {
              if (wizardIndex > 0) {
                wizardIndex -= 1;
                renderWizardOverlay(true);
              }
            });
          }

          if (wizardNext) {
            wizardNext.addEventListener('click', function () {
              if (wizardIndex < activeSteps.length - 1) {
                wizardIndex += 1;
                renderWizardOverlay(true);
                return;
              }
              closeWizardOverlay();
            });
          }

          if (wizardClose) {
            wizardClose.addEventListener('click', closeWizardOverlay);
          }

          if (wizardBackdrop) {
            wizardBackdrop.addEventListener('click', closeWizardOverlay);
          }

          hydratePreferencesFromServer().finally(function () {
            if (
              canGuide &&
              offcanvasInstance &&
              !isRouteAutoHelpDisabled() &&
              !isGlobalAutoHelpDisabled() &&
              !sessionStorage.getItem(promptedSessionKey)
            ) {
              sessionStorage.setItem(promptedSessionKey, '1');
              setTimeout(function () {
                offcanvasInstance.show();
              }, 900);
            }
          });

          window.addEventListener('resize', function () {
            if (!wizardActive) return;
            syncSpotlightPosition();
          });

          window.addEventListener('scroll', function () {
            if (!wizardActive) return;
            syncSpotlightPosition();
          }, true);
        });
      </script>
    @endif
    @stack('scripts')
</body>
</html>
