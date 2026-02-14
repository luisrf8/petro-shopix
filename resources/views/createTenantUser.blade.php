<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title>Registrar Empresa</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">

  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" />

  <style>
    body {
      background: linear-gradient(135deg, #f0f4f8, #e4e7ebff);
      font-family: "Inter", sans-serif;
      min-height: 100vh;
    }
    .card {
      border-radius: 1rem;
    }
    .form-control:focus {
      border-color: #113264ff;
      box-shadow: 0 0 0 0.2rem rgba(13,110,253,0.25);
    }
    .logo-preview {
      max-height: 90px;
      border: 1px solid #dee2e6;
    }
    .step {
      display: none;
      animation: fadeIn 0.4s ease-in-out;
    }
    .step.active {
      display: block;
    }
    .is-invalid {
        border-color: #dc3545;
        box-shadow: 0 0 0 0.2rem rgba(220,53,69,.25);
    }
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }
  </style>
</head>
<!-- Activa tu mejor versión. -->
 <!-- Ropa deportiva que se adapta a tu ritmo y potencia tu confianza. -->
<body>
  <!-- HEADER -->
  <div class="w-100 position-fixed top-0 px-4" style="z-index: 1050;">
    <div class="py-3 w-100">
      <div class="row align-items-center">
        <div class="col-md-4 d-flex justify-content-start">
          <a href="/" class="btn btn-light text-dark fw-bold px-4 py-2">
            <img src="../../assets/img/shopix5.png" alt="Logo Shopix" class="img-fluid" style="width: 100px;">
          </a>
        </div>
      </div>
    </div>
  </div>

  <div class="container my-5 pt-5">
    <div class="row justify-content-center">
      <div class="col-lg-8">
        <div class="card shadow border-0">
          <div class="card-header text-center py-4">
            <img src="../../assets/img/shopix5.png" alt="Logo Shopix" class="img-fluid mb-2" style="width: 150px;">
            <h2 class="mb-0">Crea tu espacio empresarial</h2>
          </div>

          <div class="card-body p-4">
            <form id="tenantForm" action="{{ route('tenants.storePublic') }}" method="POST" enctype="multipart/form-data">
              @csrf

              <!-- PASO 1 -->
              <div class="step active" id="step1">
                <h5 class="fw-bold mb-4 text-center">Información básica</h5>

                <div class="mb-3">
                  <label for="name" class="form-label fw-bold">Nombre de la Empresa</label>
                  <input type="text" name="name" id="name" class="form-control form-control-lg" placeholder="Ej: Mi Empresa SRL" required>
                </div>

                <div class="mb-3">
                  <label for="slug" class="form-label fw-bold">Slug / URL de acceso</label>
                  <input type="text" name="slug" id="slug" class="form-control form-control-lg" placeholder="mi-empresa" required>
                  <small class="text-muted">Tu empresa estará disponible en: <strong>https://shopix.com/<span id="slug-preview">mi-empresa</span></strong></small>
                </div>

                <div class="mb-3">
                  <label for="email" class="form-label fw-bold">Correo de contacto</label>
                  <input type="email" name="email" id="email" class="form-control form-control-lg" placeholder="correo@empresa.com" required>
                </div>

                <div class="mb-4">
                  <label for="logo" class="form-label fw-bold">Logo (PNG o SVG)</label>
                  <div class="d-flex align-items-center gap-3 flex-wrap">
                    <img id="logo-preview" src="#" class="logo-preview rounded d-none p-2 bg-white shadow-sm">
                    <input type="file" name="logo" id="logo" class="form-control form-control-lg" accept=".png,.svg">
                  </div>
                </div>

                <div class="mb-4">
                    <label for="plan_id" class="form-label fw-bold">Selecciona tu plan</label>
                    <select name="plan_id" id="plan_id" class="form-select form-select-lg" required>
                        <option value="">-- Elige un plan --</option>
                        @foreach($plans as $plan)
                            @if ($plan->status == 1)
                                <option value="{{ $plan->id }}">
                                    {{ $plan->name }} - ${{ $plan->price }}
                                </option>
                            @endif
                            @endforeach
                    </select>
                </div>
                                <div class="d-flex justify-content-end">
                  <button type="button" class="btn btn-primary btn-lg" id="nextBtn">Siguiente ➜</button>
                </div>
              </div>

              <!-- PASO 2 -->
              <div class="step" id="step2">
                <h5 class="fw-bold mb-4 text-center">Detalles de tu tienda</h5>

                <div class="mb-3">
                  <label for="slogan" class="form-label fw-bold">Slogan</label>
                  <input type="text" name="slogan" id="slogan" class="form-control form-control-lg" placeholder="Tu estilo, tu marca...">
                </div>

                <div class="mb-3">
                  <label for="description" class="form-label fw-bold">Descripción</label>
                  <textarea name="description" id="description" class="form-control form-control-lg" rows="3" placeholder="Cuéntanos sobre tu empresa..."></textarea>
                </div>

                <!-- 🏠 DIRECCIÓN EXACTA -->
                <div class="mb-3">
                    <label for="address" class="form-label fw-bold">Dirección exacta</label>
                    <input type="text" name="address" id="address" class="form-control form-control-lg" placeholder="Ej: Av. Libertador local 22, Maturin Monagas" >
                </div>

                <!-- 🗺️ MAPA GOOGLE -->
                <div class="mb-4">
                    <label class="form-label fw-bold">Ubicación en el mapa</label>
                    <div id="map" style="height: 350px; border-radius: 0.5rem;"></div>

                    <!-- Campos ocultos para latitud y longitud -->
                    <input type="hidden" name="latitude" id="latitude">
                    <input type="hidden" name="longitude" id="longitude">
                </div>

                <div class="row mb-4">
                  <div class="col-md-4">
                    <label for="color_primary" class="form-label fw-bold">Color Primario</label>
                    <input type="color" name="color_primary" id="color_primary" class="form-control form-control-color w-100" value="#0d6efd">
                  </div>
                  <div class="col-md-4">
                    <label for="color_secondary" class="form-label fw-bold">Color Secundario</label>
                    <input type="color" name="color_secondary" id="color_secondary" class="form-control form-control-color w-100" value="#6c757d">
                  </div>
                  <div class="col-md-4">
                    <label for="color_accent" class="form-label fw-bold">Color Acento</label>
                    <input type="color" name="color_accent" id="color_accent" class="form-control form-control-color w-100" value="#ffc107">
                  </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-4">
                        <label for="country" class="form-label fw-bold">País</label>
                                                                    <select name="country" id="country" class="form-control form-control-lg border border-radius-lg p-2" required>
                                                <option value="">Selecciona un país</option>
                                                @foreach($countries as $country)
                                                    <option value="{{ $country->id }}">
                                                        {{ $country->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                    </div>
                  <div class="col-md-4">
                      <label for="state" class="form-label fw-bold">Estado / Provincia</label>
                      <select name="state" id="state" class="form-control form-control-lg border border-radius-lg p-2" required>
                          <option value="">Selecciona un estado</option>
                          @foreach($states as $state)
                              <option value="{{ $state->id }}">
                                  {{ $state->name }}
                              </option>
                          @endforeach
                      </select>
                      <div id="state-loading" style="display:none;">Cargando estados...</div>
                  </div>
                  <div class="col-md-4">
                      <label for="city" class="form-label fw-bold">Ciudad</label>
                      <select name="city" id="city" class="form-control form-control-lg border border-radius-lg p-2" required>
                          <option value="">Selecciona una ciudad</option>
                          @foreach($cities as $city)
                              <option value="{{ $city->id }}">
                                  {{ $city->name }}
                              </option>
                          @endforeach
                      </select>
                      <div id="city-loading" style="display:none;">Cargando ciudades...</div>
                  </div>
                </div>

                <div class="row mb-4">
                  <div class="col-md-4">
                      <label for="phone_code" class="form-label fw-bold">Código del país</label>
                      <select name="phone_code" id="phone_code" class="form-select form-select-lg" required>
                      <option value="+58">🇻🇪 +58</option>
                      <option value="+1">🇺🇸 +1</option>
                      <option value="+34">🇪🇸 +34</option>
                      <option value="+57">🇨🇴 +57</option>
                      <option value="+55">🇧🇷 +55</option>
                      <option value="+52">🇲🇽 +52</option>
                      </select>
                  </div>
                  <div class="col-md-8">
                      <label for="phone_number" class="form-label fw-bold">Número de teléfono</label>
                      <input type="text" name="phone_number" id="phone_number" class="form-control form-control-lg" placeholder="Ej: 4121234567" required>
                  </div>
                </div>
                <hr>

                <h5 class="fw-bold text-center mb-3">Crea tu cuenta de acceso</h5>
                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label for="owner_name" class="form-label">Tu nombre</label>
                    <input type="text" name="users[owner][name]" id="owner_name" class="form-control form-control-lg" required>
                  </div>
                  <div class="col-md-6 mb-3">
                    <label for="owner_dni" class="form-label">DNI</label>
                    <input type="text" name="users[owner][dni]" id="owner_dni" class="form-control form-control-lg" required>
                  </div>
                  <div class="col-md-6 mb-3">
                    <label for="owner_phone" class="form-label">Teléfono</label>
                    <input type="text" name="users[owner][phone_number]" id="owner_phone" class="form-control form-control-lg" required>
                  </div>
                  <div class="col-md-6 mb-3">
                    <label for="owner_email" class="form-label">Tu correo</label>
                    <input type="email" name="users[owner][email]" id="owner_email" class="form-control form-control-lg" required>
                  </div>
                  <div class="col-12 mb-3">
                    <label for="owner_password" class="form-label">Contraseña</label>
                    <div class="input-group">
                      <input type="password" name="users[owner][password]" id="owner_password" class="form-control form-control-lg" placeholder="********" required>
                      <button type="button" class="input-group-text toggle-password" data-target="owner_password">👁️</button>
                    </div>
                  </div>
                </div>

                <div class="d-flex justify-content-between mt-3">
                  <button type="button" class="btn btn-secondary btn-lg" id="prevBtn">⬅ Volver</button>
                  <button type="submit" class="btn btn-success btn-lg">🚀 Crear mi tienda</button>
                </div>

                <p class="text-center text-muted mt-3 mb-0 small">
                  Al continuar, aceptas nuestros <a href="#" class="text-decoration-none">términos y condiciones</a>.
                </p>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>

  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    let map = null;
    let marker = null;
    let googleScriptLoaded = false;
    let googleScriptLoading = false;
    const googleMapsApiKey = @json(env('GOOGLE_MAPS_API_KEY'));

    function initializeMap() {
      if (!window.google || !window.google.maps || map) {
        return;
      }

      const defaultPos = { lat: 9.7457, lng: -63.1832 };
      map = new google.maps.Map(document.getElementById('map'), {
        center: defaultPos,
        zoom: 13,
      });

      marker = new google.maps.Marker({
        position: defaultPos,
        map,
        draggable: true,
      });

      document.getElementById('latitude').value = defaultPos.lat;
      document.getElementById('longitude').value = defaultPos.lng;

      google.maps.event.addListener(marker, 'dragend', function(event) {
        document.getElementById('latitude').value = event.latLng.lat();
        document.getElementById('longitude').value = event.latLng.lng();
      });

      const input = document.getElementById('address');
      const autocomplete = new google.maps.places.Autocomplete(input);
      autocomplete.bindTo('bounds', map);

      autocomplete.addListener('place_changed', function() {
        const place = autocomplete.getPlace();
        if (!place.geometry) return;
        map.setCenter(place.geometry.location);
        map.setZoom(15);
        marker.setPosition(place.geometry.location);
        document.getElementById('latitude').value = place.geometry.location.lat();
        document.getElementById('longitude').value = place.geometry.location.lng();
      });
    }

    function loadGoogleMapsScript() {
      if (googleScriptLoaded) {
        initializeMap();
        return;
      }

      if (googleScriptLoading) {
        return;
      }

      if (!googleMapsApiKey) {
        console.error('Falta GOOGLE_MAPS_API_KEY en .env');
        return;
      }

      googleScriptLoading = true;
      window.initMap = function() {
        googleScriptLoaded = true;
        googleScriptLoading = false;
        initializeMap();
      };

      const script = document.createElement('script');
      script.src = `https://maps.googleapis.com/maps/api/js?key=${googleMapsApiKey}&libraries=places&callback=initMap`;
      script.async = true;
      script.defer = true;
      script.onerror = function() {
        googleScriptLoading = false;
        console.error('No se pudo cargar Google Maps. Verifica la API key y restricciones de dominio.');
      };
      document.head.appendChild(script);
    }

    document.addEventListener('DOMContentLoaded', () => {
      const step1 = document.getElementById('step1');
      const step2 = document.getElementById('step2');
      const nextBtn = document.getElementById('nextBtn');
      const prevBtn = document.getElementById('prevBtn');
      const slugInput = document.getElementById('slug');
      const slugPreview = document.getElementById('slug-preview');
      const logoInput = document.getElementById('logo');
      const logoPreview = document.getElementById('logo-preview');
      const countrySelect = document.getElementById('country');
      const stateSelect = document.getElementById('state');
      const citySelect = document.getElementById('city');
      const stateLoading = document.getElementById('state-loading');
      const cityLoading = document.getElementById('city-loading');

      slugInput.addEventListener('input', () => {
        slugPreview.textContent = slugInput.value || 'mi-empresa';
      });

      logoInput.addEventListener('change', (event) => {
        const file = event.target.files[0];
        if (file) {
          const reader = new FileReader();
          reader.onload = e => {
            logoPreview.src = e.target.result;
            logoPreview.classList.remove('d-none');
          };
          reader.readAsDataURL(file);
        } else {
          logoPreview.src = '#';
          logoPreview.classList.add('d-none');
        }
      });

      nextBtn.addEventListener('click', () => {
        const inputsStep1 = step1.querySelectorAll('input[required], select[required]');
        let valid = true;

        inputsStep1.forEach(input => {
          if (!String(input.value).trim()) {
            input.classList.add('is-invalid');
            valid = false;
          } else {
            input.classList.remove('is-invalid');
          }
        });

        if (!valid) {
          return;
        }

        step1.classList.remove('active');
        step2.classList.add('active');
        window.scrollTo({ top: 0, behavior: 'smooth' });
        loadGoogleMapsScript();
      });

      prevBtn.addEventListener('click', () => {
        step2.classList.remove('active');
        step1.classList.add('active');
        window.scrollTo({ top: 0, behavior: 'smooth' });
      });

      document.querySelectorAll('.toggle-password').forEach(btn => {
        btn.addEventListener('click', () => {
          const input = document.getElementById(btn.dataset.target);
          input.type = input.type === 'password' ? 'text' : 'password';
        });
      });

      countrySelect.addEventListener('change', async function() {
        const countryId = this.value;
        stateSelect.innerHTML = '<option value="">Selecciona un estado</option>';
        citySelect.innerHTML = '<option value="">Selecciona una ciudad</option>';

        if (!countryId) return;

        stateLoading.style.display = 'block';
        try {
          const response = await fetch(`/get-states/${countryId}`);
          if (!response.ok) throw new Error('No se pudieron cargar los estados');
          const data = await response.json();
          data.forEach(state => {
            stateSelect.insertAdjacentHTML('beforeend', `<option value="${state.id}">${state.name}</option>`);
          });
        } catch (error) {
          console.error(error);
        } finally {
          stateLoading.style.display = 'none';
        }
      });

      stateSelect.addEventListener('change', async function() {
        const stateId = this.value;
        citySelect.innerHTML = '<option value="">Selecciona una ciudad</option>';

        if (!stateId) return;

        cityLoading.style.display = 'block';
        try {
          const response = await fetch(`/get-cities/${stateId}`);
          if (!response.ok) throw new Error('No se pudieron cargar las ciudades');
          const data = await response.json();
          data.forEach(city => {
            citySelect.insertAdjacentHTML('beforeend', `<option value="${city.id}">${city.name}</option>`);
          });
        } catch (error) {
          console.error(error);
        } finally {
          cityLoading.style.display = 'none';
        }
      });
    });
  </script>
</body>
</html>
