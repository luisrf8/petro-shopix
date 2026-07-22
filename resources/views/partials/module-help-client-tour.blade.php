@php
  $clientModuleHelpEnabled = (bool) config('module_help.enabled', true);
  $clientCurrentRouteName = optional(request()->route())->getName() ?? 'client_route';
  $clientModuleHelpConfig = config('module_help', []);
  $clientModuleHelpAudience = $clientModuleHelpConfig['audience'] ?? [];
  $clientCsvToList = static function ($value) {
    if (is_array($value)) {
      return array_values(array_filter(array_map(static fn ($item) => strtolower(trim((string) $item)), $value), static fn ($item) => $item !== ''));
    }

    $raw = trim((string) $value);
    if ($raw === '') {
      return [];
    }

    return array_values(array_filter(array_map(static fn ($item) => strtolower(trim((string) $item)), explode(',', $raw)), static fn ($item) => $item !== ''));
  };
  $clientCsvToIntegerList = static function ($value) {
    if (is_array($value)) {
      return array_values(array_filter(array_map(static fn ($item) => (int) $item, $value), static fn ($item) => $item > 0));
    }

    $raw = trim((string) $value);
    if ($raw === '') {
      return [];
    }

    return array_values(array_filter(array_map(static fn ($item) => (int) trim((string) $item), explode(',', $raw)), static fn ($item) => $item > 0));
  };
  $clientAuthUser = auth()->user();
  $clientIsGuest = !$clientAuthUser;
  $clientRoleName = strtolower(trim((string) optional(optional($clientAuthUser)->role)->name));
  if ($clientRoleName === '' && $clientAuthUser && isset($clientAuthUser->role_id)) {
    $clientRoleName = 'role:' . (int) $clientAuthUser->role_id;
  }
  $clientTenantId = (int) (($tenant->id ?? null) ?: ($clientAuthUser->tenant_id ?? 0));
  $clientAllowGuests = (bool) ($clientModuleHelpAudience['allow_guests'] ?? true);
  $clientRoleAllowList = $clientCsvToList($clientModuleHelpAudience['role_allow_list'] ?? []);
  $clientRoleBlockList = $clientCsvToList($clientModuleHelpAudience['role_block_list'] ?? []);
  $clientTenantAllowList = $clientCsvToIntegerList($clientModuleHelpAudience['tenant_allow_list'] ?? []);
  $clientTenantBlockList = $clientCsvToIntegerList($clientModuleHelpAudience['tenant_block_list'] ?? []);
  $clientAudienceAllowed = true;

  if ($clientIsGuest && !$clientAllowGuests) {
    $clientAudienceAllowed = false;
  }

  if ($clientAudienceAllowed && !$clientIsGuest && !empty($clientRoleAllowList) && !in_array($clientRoleName, $clientRoleAllowList, true)) {
    $clientAudienceAllowed = false;
  }

  if ($clientAudienceAllowed && !$clientIsGuest && !empty($clientRoleBlockList) && in_array($clientRoleName, $clientRoleBlockList, true)) {
    $clientAudienceAllowed = false;
  }

  if ($clientAudienceAllowed && $clientTenantId > 0 && !empty($clientTenantAllowList) && !in_array($clientTenantId, $clientTenantAllowList, true)) {
    $clientAudienceAllowed = false;
  }

  if ($clientAudienceAllowed && $clientTenantId > 0 && !empty($clientTenantBlockList) && in_array($clientTenantId, $clientTenantBlockList, true)) {
    $clientAudienceAllowed = false;
  }

  $clientModuleHelpEnabled = $clientModuleHelpEnabled && $clientAudienceAllowed;
  $clientModuleHelpFallback = $clientModuleHelpConfig['fallback'] ?? [];
  $clientModuleHelpRoute = $clientModuleHelpConfig['routes'][$clientCurrentRouteName] ?? [];
  $clientModuleHelp = $clientModuleHelpEnabled
    ? array_replace_recursive($clientModuleHelpFallback, $clientModuleHelpRoute)
    : [];
@endphp

@if(!empty($clientModuleHelp))
  <style>
    .shopix-client-tour-launcher {
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

    .shopix-client-tour-launcher:hover,
    .shopix-client-tour-launcher:focus {
      background: linear-gradient(135deg, #111827, #334155);
      color: #f8fafc;
    }

    @media (max-width: 576px) {
      .shopix-client-tour-launcher {
        right: 0.75rem;
        left: 0.75rem;
        bottom: 0.75rem;
        justify-content: center;
      }
    }
  </style>

  <button type="button" class="shopix-client-tour-launcher" id="shopixStartClientModuleTourBtn" aria-label="Iniciar recorrido guiado">
    <i class="bi bi-compass"></i>
    Recorrido guiado
  </button>

  <script>
    document.addEventListener('DOMContentLoaded', function () {
      const moduleHelp = @json($clientModuleHelp);
      const routeKey = @json($clientCurrentRouteName);
      const launchButton = document.getElementById('shopixStartClientModuleTourBtn');

      if (!launchButton) {
        return;
      }

      const seenKey = 'shopix_client_tour_seen_' + routeKey;

      function getDriverFactory() {
        return window.driver?.js?.driver || window.driver;
      }

      function ensureDriverAssets() {
        return new Promise(function (resolve) {
          const cssId = 'shopix-driver-css';
          if (!document.getElementById(cssId)) {
            const link = document.createElement('link');
            link.id = cssId;
            link.rel = 'stylesheet';
            link.href = 'https://cdn.jsdelivr.net/npm/driver.js@1.0.1/dist/driver.css';
            document.head.appendChild(link);
          }

          if (getDriverFactory()) {
            resolve(true);
            return;
          }

          const scriptId = 'shopix-driver-js';
          const existingScript = document.getElementById(scriptId);
          if (existingScript) {
            existingScript.addEventListener('load', function () {
              resolve(Boolean(getDriverFactory()));
            }, { once: true });
            resolve(Boolean(getDriverFactory()));
            return;
          }

          const script = document.createElement('script');
          script.id = scriptId;
          script.src = 'https://cdn.jsdelivr.net/npm/driver.js@1.0.1/dist/driver.js.iife.js';
          script.onload = function () {
            resolve(Boolean(getDriverFactory()));
          };
          script.onerror = function () {
            resolve(false);
          };
          document.body.appendChild(script);
        });
      }

      function normalizeSteps(mode) {
        const wizard = Array.isArray(moduleHelp.wizard) ? moduleHelp.wizard : [];
        const tour = Array.isArray(moduleHelp.tour) ? moduleHelp.tour : [];
        const source = mode === 'wizard'
          ? (wizard.length ? wizard : tour)
          : (tour.length ? tour : wizard);

        return source
          .filter(function (step) {
            if (!step || !step.selector) {
              return false;
            }

            try {
              return Boolean(document.querySelector(step.selector));
            } catch (error) {
              return false;
            }
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
              },
            };
          });
      }

      function startClientTour(mode) {
        ensureDriverAssets().then(function (ready) {
          const driverFactory = getDriverFactory();
          if (!ready || typeof driverFactory !== 'function') {
            return;
          }

          const steps = normalizeSteps(mode);
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
          localStorage.setItem(seenKey, '1');
        });
      }

      launchButton.addEventListener('click', function () {
        startClientTour('tour');
      });
    });
  </script>
@endif
