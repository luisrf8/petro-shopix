<!DOCTYPE html>
<html lang="es">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <title>Shopix - Mi cuenta</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css" rel="stylesheet">
  <style>
    :root {
      --brand-primary: #0f172a;
      --brand-secondary: #1e293b;
      --brand-accent: #2563eb;
    }

    body {
      background:
        radial-gradient(circle at 8% 12%, rgba(37, 99, 235, 0.08), transparent 35%),
        radial-gradient(circle at 90% 88%, rgba(15, 23, 42, 0.08), transparent 30%),
        #f4f6fb;
      min-height: 100vh;
    }

    .portal-topbar {
      background: linear-gradient(135deg, var(--brand-primary), var(--brand-secondary));
      color: #fff;
      border-radius: 0 0 18px 18px;
      box-shadow: 0 16px 35px rgba(15, 23, 42, 0.25);
    }

    .portal-pill {
      border: 1px solid rgba(255, 255, 255, 0.32);
      background: rgba(255, 255, 255, 0.12);
      color: #fff;
    }

    .portal-card {
      border: 0;
      border-radius: 16px;
      box-shadow: 0 12px 30px rgba(15, 23, 42, 0.08);
    }

    .portal-section-title {
      font-size: 1.02rem;
      font-weight: 700;
      color: #0f172a;
      margin-bottom: 0.9rem;
    }

    .summary-pill {
      border-radius: 999px;
      font-size: 0.78rem;
      background: #eef2ff;
      color: #1e1b4b;
      border: 1px solid #dbe2ff;
      padding: 0.35rem 0.7rem;
    }

    .portal-empty {
      border: 1px dashed #cbd5e1;
      border-radius: 12px;
      background: #f8fafc;
      color: #475569;
      padding: 1rem;
      text-align: center;
    }

    .portal-sticky-nav {
      position: sticky;
      top: 1rem;
      z-index: 1;
    }

    .portal-anchor-btn {
      text-align: left;
    }

    .portal-anchor-btn.active {
      background: rgba(37, 99, 235, 0.09);
      border-color: rgba(37, 99, 235, 0.32);
      color: #0f172a;
    }

    #location-map {
      width: 100%;
      height: 260px;
      border: 0;
      border-radius: 12px;
      background: #e2e8f0;
    }
  </style>
</head>

<body>
  <header class="portal-topbar py-3 py-md-4 mb-4">
    <div class="container">
      <div class="d-flex flex-column flex-lg-row align-items-lg-center justify-content-between gap-3">
        <div>
          <p class="mb-1 text-uppercase small" style="letter-spacing: .08em; opacity: .86;">Portal de cliente</p>
          <h1 class="h3 mb-0">Mi cuenta Shopix</h1>
        </div>
        <div class="d-flex flex-wrap gap-2">
          <a href="{{ url('/landings') }}" class="btn portal-pill btn-sm">Inicio tiendas / servicios</a>
          <button type="button" class="btn portal-pill btn-sm" id="logout-btn"><i class="bi bi-box-arrow-right me-1"></i>Cerrar sesión</button>
        </div>
      </div>
    </div>
  </header>

  <main class="container pb-5">
    <div class="alert d-none" id="portal-alert" role="alert"></div>

    <div id="guest-panel" class="card portal-card d-none">
      <div class="card-body p-4 text-center">
        <h2 class="h5 mb-2">Necesitas iniciar sesión</h2>
        <p class="text-muted mb-3">Para ver tu cuenta, compras y citas debes iniciar sesión como cliente.</p>
        <a href="{{ url('/landings') }}" class="btn btn-dark">Ir al directorio</a>
      </div>
    </div>

    <div id="portal-panel" class="d-none">
      <div class="row g-3 g-lg-4 mb-3">
        <div class="col-12 col-lg-3">
          <div class="card portal-card portal-sticky-nav">
            <div class="card-body">
              <p class="text-muted small mb-2">Navegación rápida</p>
              <div class="d-grid gap-2">
                <button type="button" class="btn btn-outline-secondary portal-anchor-btn" data-target="compras">Compras</button>
                <button type="button" class="btn btn-outline-secondary portal-anchor-btn" data-target="citas">Citas</button>
                <button type="button" class="btn btn-outline-secondary portal-anchor-btn" data-target="perfil">Perfil</button>
                <button type="button" class="btn btn-outline-secondary portal-anchor-btn" data-target="seguridad">Contraseña</button>
              </div>
            </div>
          </div>
        </div>

        <div class="col-12 col-lg-9">
          <div class="row g-3 mb-3">
            <div class="col-12 col-md-4">
              <div class="card portal-card h-100">
                <div class="card-body">
                  <p class="text-muted mb-1">Compras registradas</p>
                  <p class="h4 mb-0" id="summary-orders">0</p>
                </div>
              </div>
            </div>
            <div class="col-12 col-md-4">
              <div class="card portal-card h-100">
                <div class="card-body">
                  <p class="text-muted mb-1">Citas registradas</p>
                  <p class="h4 mb-0" id="summary-appointments">0</p>
                </div>
              </div>
            </div>
            <div class="col-12 col-md-4">
              <div class="card portal-card h-100">
                <div class="card-body">
                  <p class="text-muted mb-1">Sesión</p>
                  <span class="summary-pill" id="summary-user">Cliente</span>
                </div>
              </div>
            </div>
          </div>

          <section id="compras" class="card portal-card mb-3">
            <div class="card-body p-3 p-md-4">
              <h2 class="portal-section-title">Mis compras</h2>
              <div id="orders-list" class="portal-empty">Cargando compras...</div>
            </div>
          </section>

          <section id="citas" class="card portal-card mb-3">
            <div class="card-body p-3 p-md-4">
              <h2 class="portal-section-title">Mis citas</h2>
              <div id="appointments-list" class="portal-empty">Cargando citas...</div>
            </div>
          </section>

          <section id="perfil" class="card portal-card mb-3">
            <div class="card-body p-3 p-md-4">
              <h2 class="portal-section-title">Mi perfil y dirección</h2>
              <form id="profile-form" class="row g-3">
                <div class="col-12 col-md-6">
                  <label for="profile-name" class="form-label">Nombre</label>
                  <input type="text" class="form-control" id="profile-name" maxlength="255">
                </div>
                <div class="col-12 col-md-6">
                  <label for="profile-email" class="form-label">Correo</label>
                  <input type="email" class="form-control" id="profile-email" maxlength="255" placeholder="Opcional">
                </div>
                <div class="col-12 col-md-6">
                  <label for="profile-dni" class="form-label">DNI</label>
                  <input type="text" class="form-control" id="profile-dni" maxlength="100" placeholder="Opcional">
                </div>
                <div class="col-4 col-md-2">
                  <label for="profile-phone-code" class="form-label">Código</label>
                  <input type="text" class="form-control" id="profile-phone-code" maxlength="10" placeholder="+58">
                </div>
                <div class="col-8 col-md-4">
                  <label for="profile-phone-number" class="form-label">Teléfono</label>
                  <input type="text" class="form-control" id="profile-phone-number" maxlength="50">
                </div>
                <div class="col-12 col-md-4">
                  <label for="profile-country" class="form-label">País</label>
                  <select id="profile-country" class="form-select">
                    <option value="">Selecciona un país</option>
                  </select>
                </div>
                <div class="col-12 col-md-4">
                  <label for="profile-state" class="form-label">Estado</label>
                  <select id="profile-state" class="form-select" disabled>
                    <option value="">Selecciona un estado</option>
                  </select>
                </div>
                <div class="col-12 col-md-4">
                  <label for="profile-city" class="form-label">Ciudad</label>
                  <select id="profile-city" class="form-select" disabled>
                    <option value="">Selecciona una ciudad</option>
                  </select>
                </div>
                <div class="col-12">
                  <label for="profile-address" class="form-label">Dirección</label>
                  <textarea id="profile-address" class="form-control" rows="2" maxlength="500"></textarea>
                </div>
                <div class="col-6 col-md-3">
                  <label for="profile-latitude" class="form-label">Latitud</label>
                  <input type="number" step="0.000001" id="profile-latitude" class="form-control">
                </div>
                <div class="col-6 col-md-3">
                  <label for="profile-longitude" class="form-label">Longitud</label>
                  <input type="number" step="0.000001" id="profile-longitude" class="form-control">
                </div>
                <div class="col-12 col-md-6 d-flex align-items-end">
                  <button type="button" class="btn btn-outline-secondary me-2" id="detect-location-btn"><i class="bi bi-geo-alt"></i> Usar ubicación actual</button>
                  <a href="#" target="_blank" rel="noopener noreferrer" id="open-map-link" class="btn btn-outline-dark">Abrir en Maps</a>
                </div>
                <div class="col-12">
                  <iframe id="location-map" loading="lazy" referrerpolicy="no-referrer-when-downgrade"></iframe>
                </div>
                <div class="col-12">
                  <button type="submit" class="btn btn-dark">Guardar perfil</button>
                </div>
              </form>
            </div>
          </section>

          <section id="seguridad" class="card portal-card">
            <div class="card-body p-3 p-md-4">
              <h2 class="portal-section-title">Cambiar contraseña</h2>
              <form id="password-form" class="row g-3">
                <div class="col-12">
                  <label for="current-password" class="form-label">Contraseña actual</label>
                  <input type="password" class="form-control" id="current-password" minlength="8" required>
                </div>
                <div class="col-12 col-md-6">
                  <label for="new-password" class="form-label">Nueva contraseña</label>
                  <input type="password" class="form-control" id="new-password" minlength="8" required>
                </div>
                <div class="col-12 col-md-6">
                  <label for="new-password-confirmation" class="form-label">Confirmar contraseña</label>
                  <input type="password" class="form-control" id="new-password-confirmation" minlength="8" required>
                </div>
                <div class="col-12">
                  <button type="submit" class="btn btn-dark">Actualizar contraseña</button>
                </div>
              </form>
            </div>
          </section>
        </div>
      </div>
    </div>
  </main>

  <script>
    (() => {
      const TOKEN_KEY = 'shopix_ecomm_token';
      const USER_KEY = 'shopix_ecomm_user';
      const profileEndpoint = '/api/user/update-profile';
      const passwordEndpoint = '/api/user/change-password';

      const guestPanel = document.getElementById('guest-panel');
      const portalPanel = document.getElementById('portal-panel');
      const alertBox = document.getElementById('portal-alert');

      const ordersList = document.getElementById('orders-list');
      const appointmentsList = document.getElementById('appointments-list');
      const summaryOrders = document.getElementById('summary-orders');
      const summaryAppointments = document.getElementById('summary-appointments');
      const summaryUser = document.getElementById('summary-user');

      const profileForm = document.getElementById('profile-form');
      const passwordForm = document.getElementById('password-form');

      const profileName = document.getElementById('profile-name');
      const profileEmail = document.getElementById('profile-email');
      const profileDni = document.getElementById('profile-dni');
      const profilePhoneCode = document.getElementById('profile-phone-code');
      const profilePhoneNumber = document.getElementById('profile-phone-number');
      const profileCountry = document.getElementById('profile-country');
      const profileState = document.getElementById('profile-state');
      const profileCity = document.getElementById('profile-city');
      const profileAddress = document.getElementById('profile-address');
      const profileLatitude = document.getElementById('profile-latitude');
      const profileLongitude = document.getElementById('profile-longitude');
      const detectLocationBtn = document.getElementById('detect-location-btn');
      const openMapLink = document.getElementById('open-map-link');
      const locationMap = document.getElementById('location-map');

      const logoutBtn = document.getElementById('logout-btn');

      const sections = {
        compras: document.getElementById('compras'),
        citas: document.getElementById('citas'),
        perfil: document.getElementById('perfil'),
        seguridad: document.getElementById('seguridad')
      };

      let currentToken = localStorage.getItem(TOKEN_KEY) || '';
      let currentUser = null;
      let profile = null;

      try {
        currentUser = JSON.parse(localStorage.getItem(USER_KEY) || 'null');
      } catch (error) {
        currentUser = null;
      }

      const showAlert = (message, type = 'success') => {
        alertBox.className = `alert alert-${type}`;
        alertBox.textContent = message;
        alertBox.classList.remove('d-none');
      };

      const hideAlert = () => {
        alertBox.classList.add('d-none');
      };

      const clearSession = () => {
        localStorage.removeItem(TOKEN_KEY);
        localStorage.removeItem(USER_KEY);
        currentToken = '';
        currentUser = null;
      };

      const authFetch = async (url, options = {}) => {
        const headers = {
          'Accept': 'application/json',
          'Authorization': `Bearer ${currentToken}`,
          ...options.headers,
        };

        const response = await fetch(url, { ...options, headers });

        if (response.status === 401) {
          clearSession();
          throw new Error('Tu sesión expiró.');
        }

        if (!response.ok) {
          const payload = await response.json().catch(() => ({}));
          throw new Error(payload.message || 'No se pudo completar la solicitud.');
        }

        return response.json();
      };

      const parsePhone = (value) => {
        const raw = String(value || '').trim();
        if (!raw) {
          return { code: '+58', number: '' };
        }

        if (!raw.startsWith('+')) {
          return { code: '+58', number: raw.replace(/\D+/g, '') };
        }

        const normalized = raw.replace(/\s+/g, '');
        const body = normalized.slice(1);
        if (!body) {
          return { code: '+58', number: '' };
        }

        if (body.length <= 4) {
          return { code: `+${body}`, number: '' };
        }

        return {
          code: `+${body.slice(0, 2)}`,
          number: body.slice(2),
        };
      };

      const fillSelect = (select, items, placeholder) => {
        select.innerHTML = `<option value="">${placeholder}</option>`;
        items.forEach(item => {
          const option = document.createElement('option');
          option.value = String(item.id);
          option.textContent = item.name;
          select.appendChild(option);
        });
      };

      const updateMapPreview = () => {
        const lat = parseFloat(profileLatitude.value);
        const lng = parseFloat(profileLongitude.value);

        if (!Number.isFinite(lat) || !Number.isFinite(lng)) {
          locationMap.removeAttribute('src');
          openMapLink.setAttribute('href', '#');
          openMapLink.setAttribute('aria-disabled', 'true');
          return;
        }

        const mapUrl = `https://www.google.com/maps?q=${lat},${lng}`;
        locationMap.src = `${mapUrl}&z=15&output=embed`;
        openMapLink.setAttribute('href', mapUrl);
        openMapLink.removeAttribute('aria-disabled');
      };

      const loadCountries = async (countryId, stateId, cityId) => {
        const countries = await fetch('/get-countries').then(r => r.json());
        fillSelect(profileCountry, countries, 'Selecciona un país');
        profileCountry.disabled = false;

        if (countryId) {
          profileCountry.value = String(countryId);
          await loadStates(countryId, stateId, cityId);
        }
      };

      const loadStates = async (countryId, stateId = null, cityId = null) => {
        if (!countryId) {
          fillSelect(profileState, [], 'Selecciona un estado');
          fillSelect(profileCity, [], 'Selecciona una ciudad');
          profileState.disabled = true;
          profileCity.disabled = true;
          return;
        }

        const states = await fetch(`/get-states/${countryId}`).then(r => r.json());
        fillSelect(profileState, states, 'Selecciona un estado');
        profileState.disabled = false;

        if (stateId) {
          profileState.value = String(stateId);
          await loadCities(stateId, cityId);
        } else {
          fillSelect(profileCity, [], 'Selecciona una ciudad');
          profileCity.disabled = true;
        }
      };

      const loadCities = async (stateId, cityId = null) => {
        if (!stateId) {
          fillSelect(profileCity, [], 'Selecciona una ciudad');
          profileCity.disabled = true;
          return;
        }

        const cities = await fetch(`/get-cities/${stateId}`).then(r => r.json());
        fillSelect(profileCity, cities, 'Selecciona una ciudad');
        profileCity.disabled = false;

        if (cityId) {
          profileCity.value = String(cityId);
        }
      };

      const renderOrders = (orders) => {
        const list = Array.isArray(orders) ? orders : [];
        summaryOrders.textContent = String(list.length);

        if (!list.length) {
          ordersList.className = 'portal-empty';
          ordersList.textContent = 'Todavía no tienes compras registradas.';
          return;
        }

        ordersList.className = 'd-grid gap-2';
        ordersList.innerHTML = list.map(order => {
          const storeName = order?.tenant_name || order?.tenant?.name || 'Tienda no disponible';
          return `
            <div class="border rounded-3 p-3">
              <div class="d-flex justify-content-between flex-wrap gap-2">
                <strong>Pedido #${order.order_number ?? order.id}</strong>
                <span class="badge text-bg-light">${order.status || 'Pendiente'}</span>
              </div>
              <p class="mb-1 mt-2"><strong>Tienda:</strong> ${storeName}</p>
              <p class="mb-0 text-muted">Fecha: ${order.created_at || order.date || 'N/A'}</p>
            </div>
          `;
        }).join('');
      };

      const renderAppointments = (appointments) => {
        const list = Array.isArray(appointments) ? appointments : [];
        summaryAppointments.textContent = String(list.length);

        if (!list.length) {
          appointmentsList.className = 'portal-empty';
          appointmentsList.textContent = 'Todavía no tienes citas registradas.';
          return;
        }

        appointmentsList.className = 'd-grid gap-2';
        appointmentsList.innerHTML = list.map(appointment => {
          return `
            <div class="border rounded-3 p-3">
              <div class="d-flex justify-content-between flex-wrap gap-2">
                <strong>${appointment.service_name || 'Servicio'}</strong>
                <span class="badge text-bg-light">${appointment.status || 'Pendiente'}</span>
              </div>
              <p class="mb-1 mt-2">Fecha: ${appointment.scheduled_for || appointment.appointment_date || 'N/A'}</p>
              <p class="mb-0 text-muted">Profesional: ${appointment.employee_name || 'Por asignar'}</p>
            </div>
          `;
        }).join('');
      };

      const loadProfile = async () => {
        const payload = await authFetch('/api/user');
        profile = payload.user || payload;

        profileName.value = profile.name || '';
        profileEmail.value = profile.email || '';
        profileDni.value = profile.dni || '';

        const phone = parsePhone(profile.phone_number || '');
        profilePhoneCode.value = phone.code || '+58';
        profilePhoneNumber.value = phone.number || '';

        profileAddress.value = profile.address || '';
        profileLatitude.value = profile.latitude ?? '';
        profileLongitude.value = profile.longitude ?? '';

        summaryUser.textContent = profile.name || 'Cliente';

        await loadCountries(profile.country_id, profile.state_id, profile.city_id);
        updateMapPreview();
      };

      const loadOrders = async () => {
        const payload = await authFetch('/api/user/orders');
        renderOrders(payload.orders || payload.data || []);
      };

      const loadAppointments = async () => {
        const payload = await authFetch('/api/user/appointments');
        renderAppointments(payload.appointments || payload.data || []);
      };

      profileCountry.addEventListener('change', async () => {
        await loadStates(profileCountry.value || null);
      });

      profileState.addEventListener('change', async () => {
        await loadCities(profileState.value || null);
      });

      profileLatitude.addEventListener('input', updateMapPreview);
      profileLongitude.addEventListener('input', updateMapPreview);

      detectLocationBtn.addEventListener('click', () => {
        if (!navigator.geolocation) {
          showAlert('Tu navegador no soporta geolocalización.', 'warning');
          return;
        }

        navigator.geolocation.getCurrentPosition((position) => {
          profileLatitude.value = position.coords.latitude.toFixed(6);
          profileLongitude.value = position.coords.longitude.toFixed(6);
          updateMapPreview();
        }, () => {
          showAlert('No se pudo obtener tu ubicación.', 'warning');
        });
      });

      profileForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        hideAlert();

        const payload = {
          name: profileName.value.trim() || null,
          email: profileEmail.value.trim() || null,
          dni: profileDni.value.trim() || null,
          phone_code: profilePhoneCode.value.trim() || null,
          phone_number: profilePhoneNumber.value.trim() || null,
          country_id: profileCountry.value || null,
          state_id: profileState.value || null,
          city_id: profileCity.value || null,
          address: profileAddress.value.trim() || null,
          latitude: profileLatitude.value ? Number(profileLatitude.value) : null,
          longitude: profileLongitude.value ? Number(profileLongitude.value) : null,
        };

        try {
          const response = await authFetch(profileEndpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload),
          });

          showAlert(response.message || 'Perfil actualizado correctamente.');
          await loadProfile();
        } catch (error) {
          showAlert(error.message || 'No se pudo guardar el perfil.', 'danger');
        }
      });

      passwordForm.addEventListener('submit', async (event) => {
        event.preventDefault();
        hideAlert();

        const currentPassword = document.getElementById('current-password').value;
        const newPassword = document.getElementById('new-password').value;
        const newPasswordConfirmation = document.getElementById('new-password-confirmation').value;

        try {
          const response = await authFetch(passwordEndpoint, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
              current_password: currentPassword,
              new_password: newPassword,
              new_password_confirmation: newPasswordConfirmation,
            }),
          });

          passwordForm.reset();
          showAlert(response.message || 'Contraseña actualizada correctamente.');
        } catch (error) {
          showAlert(error.message || 'No se pudo actualizar la contraseña.', 'danger');
        }
      });

      logoutBtn.addEventListener('click', () => {
        clearSession();
        window.location.href = '{{ url('/landings') }}';
      });

      document.querySelectorAll('.portal-anchor-btn').forEach(btn => {
        btn.addEventListener('click', () => {
          const target = btn.getAttribute('data-target');
          if (!target || !sections[target]) {
            return;
          }

          sections[target].scrollIntoView({ behavior: 'smooth', block: 'start' });
          window.history.replaceState(null, '', `#${target}`);

          document.querySelectorAll('.portal-anchor-btn').forEach(item => item.classList.remove('active'));
          btn.classList.add('active');
        });
      });

      const activateHashSection = () => {
        const hash = window.location.hash.replace('#', '');
        const target = sections[hash] ? hash : 'compras';
        const button = document.querySelector(`.portal-anchor-btn[data-target="${target}"]`);
        button?.click();
      };

      const initialize = async () => {
        if (!currentToken) {
          guestPanel.classList.remove('d-none');
          return;
        }

        portalPanel.classList.remove('d-none');

        try {
          await loadProfile();
          await Promise.all([loadOrders(), loadAppointments()]);
          activateHashSection();
        } catch (error) {
          clearSession();
          portalPanel.classList.add('d-none');
          guestPanel.classList.remove('d-none');
          showAlert(error.message || 'Tu sesión expiró. Inicia sesión nuevamente.', 'warning');
        }
      };

      initialize();
    })();
  </script>
</body>

</html>
