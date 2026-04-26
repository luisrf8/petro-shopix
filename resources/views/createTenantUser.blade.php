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
    .ai-spark {
      display: inline-flex;
      align-items: center;
      gap: 0.35rem;
    }
    .ai-loading-dots {
      display: inline-flex;
      margin-left: 0.35rem;
    }
    .ai-loading-dots span {
      width: 6px;
      height: 6px;
      margin: 0 2px;
      background: #0d6efd;
      border-radius: 50%;
      animation: aiPulse 0.9s infinite ease-in-out;
    }
    .ai-loading-dots span:nth-child(2) { animation-delay: 0.15s; }
    .ai-loading-dots span:nth-child(3) { animation-delay: 0.3s; }
    .ai-chat-box {
      border: 1px solid #dee2e6;
      border-radius: .5rem;
      padding: .75rem;
      background: #f8f9fa;
      height: 220px;
      overflow-y: auto;
    }
    .ai-attach-btn {
      width: 42px;
      height: 42px;
    font-size: 20px;
      border-radius: 50%;
    }
    @keyframes fadeIn {
      from { opacity: 0; transform: translateY(10px); }
      to { opacity: 1; transform: translateY(0); }
    }
    @keyframes aiPulse {
      0%, 100% { opacity: 0.3; transform: translateY(0); }
      50% { opacity: 1; transform: translateY(-3px); }
    }

    .shopix-toast-stack {
      position: fixed;
      top: 1rem;
      right: 1rem;
      z-index: 2060;
      display: flex;
      flex-direction: column;
      gap: 0.5rem;
      pointer-events: none;
    }

    .shopix-toast {
      min-width: 280px;
      max-width: 420px;
      background: #1f2937;
      color: #fff;
      border-radius: 10px;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
      padding: 0.75rem 1rem;
      opacity: 0;
      transform: translateY(-6px);
      transition: opacity 0.2s ease, transform 0.2s ease;
      pointer-events: auto;
    }

    .shopix-toast.show {
      opacity: 1;
      transform: translateY(0);
    }

    .shopix-toast.success {
      background: #0f5132;
    }

    .shopix-toast.warning {
      background: #7a4e00;
    }

    .shopix-toast.error {
      background: #842029;
    }

    .hex-input {
      text-transform: uppercase;
      letter-spacing: 0.04em;
    }

    .color-sync-group {
      display: flex;
      align-items: center;
      gap: 0.75rem;
    }

    .color-picker-input {
      width: 58px;
      height: 58px;
      border: 1px solid #dee2e6;
      border-radius: 0.85rem;
      padding: 0.25rem;
      background: #fff;
      flex: 0 0 auto;
      cursor: pointer;
    }

    .color-swatch-chip {
      min-width: 92px;
      height: 58px;
      border-radius: 0.85rem;
      border: 1px solid #dee2e6;
      display: flex;
      align-items: center;
      justify-content: center;
      font-weight: 700;
      box-shadow: inset 0 0 0 1px rgba(255,255,255,0.3);
    }

    .assistant-copy-card {
      border: 1px solid #dbe4f0;
      border-radius: 1rem;
      padding: 1rem;
      background: linear-gradient(180deg, #f8fbff 0%, #ffffff 100%);
      box-shadow: 0 10px 24px rgba(15, 23, 42, 0.05);
    }

    .terms-consent-card {
      border: 1px solid #dbe4f0;
      border-radius: 1rem;
      padding: 1rem;
      background: #f8fafc;
    }

    .terms-consent-list {
      margin: 0.75rem 0 0;
      padding-left: 1.1rem;
      color: #475569;
    }

    .terms-consent-list li + li {
      margin-top: 0.35rem;
    }

    .terms-modal-frame {
      width: 100%;
      min-height: 72vh;
      border: 0;
      border-radius: 0.75rem;
      background: #f8fafc;
    }
  </style>
</head>
<!-- Activa tu mejor versión. -->
 <!-- Ropa deportiva que se adapta a tu ritmo y potencia tu confianza. -->
<body>
  <div id="shopixToastContainer" class="shopix-toast-stack"></div>
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
            @if ($errors->any())
              <div class="alert alert-danger" role="alert">
                <strong>No se pudo crear la tienda.</strong>
                <ul class="mb-0 mt-2">
                  @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                  @endforeach
                </ul>
              </div>
            @endif

            @if (session('status'))
              <div class="alert alert-success" role="alert">
                <strong>Registro completado.</strong>
                <div class="mt-1">{{ session('status') }}</div>
              </div>
            @endif

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
                  <small class="text-muted">Tu empresa estará disponible en: <strong>https://shopixv.com/<span id="slug-preview">mi-empresa</span></strong></small>
                </div>

                <div class="mb-3">
                  <label for="email" class="form-label fw-bold">Correo de contacto</label>
                  <input type="email" name="email" id="email" class="form-control form-control-lg" placeholder="correo@empresa.com" required>
                </div>

                <div class="row mb-3">
                  <div class="col-md-4">
                    <label for="business_type" class="form-label fw-bold">Tipo de negocio</label>
                    <select name="business_type" id="business_type" class="form-select form-select-lg" required>
                      <option value="">Selecciona una opción</option>
                      <option value="tienda" {{ old('business_type') === 'tienda' ? 'selected' : '' }}>Tienda</option>
                      <option value="servicio" {{ old('business_type') === 'servicio' ? 'selected' : '' }}>Servicio</option>
                    </select>
                  </div>
                  <div class="col-md-8">
                    <label for="economic_activity" class="form-label fw-bold">Rubro económico</label>
                    <select name="economic_activity" id="economic_activity" class="form-select form-select-lg" data-selected="{{ old('economic_activity') }}" required>
                      <option value="">Selecciona un rubro</option>
                    </select>
                    <small id="economic_activity_help" class="text-muted d-block mt-1"></small>
                    <small id="economic_activity_examples" class="text-muted d-block mt-1"></small>
                  </div>
                </div>

                <div class="mb-4">
                  <label for="logo" class="form-label fw-bold">Logo (PNG, JPG, JPEG o SVG)</label>
                  <div class="d-flex align-items-center gap-3 flex-wrap">
                    <img id="logo-preview" src="#" class="logo-preview rounded d-none p-2 bg-white shadow-sm">
                    <input type="file" name="logo" id="logo" class="form-control form-control-lg" accept=".png,.jpg,.jpeg,.webp,.svg">
                  </div>
                </div>
                <div class="mb-3">
                  <button type="button" class="btn btn-outline-primary w-100" id="openLogoAiModalBtn">
                    <span class="ai-spark">🤖 IA Gemini</span> para generar logo
                  </button>
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

                <div class="assistant-copy-card mb-4">
                  <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                    <div>
                      <h6 class="fw-bold mb-1">Asistente IA para slogan y descripción</h6>
                    </div>
                    <button type="button" class="btn btn-outline-primary" id="generateStoreCopyBtn">
                      <span class="ai-spark">🤖 Gemini</span> sugerir copy
                    </button>
                  </div>
                  <small class="text-muted d-block mt-2" id="storeCopyAiStatus">Completa nombre de empresa y rubro económico para obtener una sugerencia más precisa.</small>
                </div>

                <div class="mb-3">
                  <label for="slogan" class="form-label fw-bold">Slogan</label>
                  <input type="text" name="slogan" id="slogan" class="form-control form-control-lg" value="{{ old('slogan') }}" placeholder="Tu estilo, tu marca...">
                </div>

                <div class="mb-3">
                  <label for="description" class="form-label fw-bold">Descripción</label>
                  <textarea name="description" id="description" class="form-control form-control-lg border border-radius-lg p-2" rows="3" placeholder="Cuéntanos sobre tu empresa...">{{ old('description') }}</textarea>
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
                    <div class="color-sync-group">
                      <input type="color" id="color_primary_picker" class="color-picker-input" value="{{ old('color_primary', '#0D6EFD') }}" aria-label="Selector visual color primario">
                      <input type="text" name="color_primary" id="color_primary" class="form-control form-control-lg hex-input" value="{{ old('color_primary', '#0D6EFD') }}" placeholder="#0D6EFD" pattern="^#(?:[A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$" maxlength="7" required>
                      <div class="color-swatch-chip" id="color_primary_swatch">Primario</div>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <label for="color_secondary" class="form-label fw-bold">Color Secundario</label>
                    <div class="color-sync-group">
                      <input type="color" id="color_secondary_picker" class="color-picker-input" value="{{ old('color_secondary', '#6C757D') }}" aria-label="Selector visual color secundario">
                      <input type="text" name="color_secondary" id="color_secondary" class="form-control form-control-lg hex-input" value="{{ old('color_secondary', '#6C757D') }}" placeholder="#6C757D" pattern="^#(?:[A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$" maxlength="7" required>
                      <div class="color-swatch-chip" id="color_secondary_swatch">Secundario</div>
                    </div>
                  </div>
                  <div class="col-md-4">
                    <label for="color_accent" class="form-label fw-bold">Color Acento</label>
                    <div class="color-sync-group">
                      <input type="color" id="color_accent_picker" class="color-picker-input" value="{{ old('color_accent', '#FFC107') }}" aria-label="Selector visual color acento">
                      <input type="text" name="color_accent" id="color_accent" class="form-control form-control-lg hex-input" value="{{ old('color_accent', '#FFC107') }}" placeholder="#FFC107" pattern="^#(?:[A-Fa-f0-9]{6}|[A-Fa-f0-9]{3})$" maxlength="7" required>
                      <div class="color-swatch-chip" id="color_accent_swatch">Acento</div>
                    </div>
                  </div>
                </div>

                <div class="row mb-3">
                    @php
                      $selectedCountryId = old('country');
                      $selectedStateId = old('state');
                      $selectedCityId = old('city');
                    @endphp
                    <div class="col-md-4">
                        <label for="country" class="form-label fw-bold">País</label>
                                                                    <select name="country" id="country" class="form-control form-control-lg border border-radius-lg p-2" required>
                                                <option value="">Selecciona un país</option>
                                                @foreach($countries as $country)
                                    <option value="{{ $country->id }}" {{ (string) $selectedCountryId === (string) $country->id ? 'selected' : '' }}>
                                                        {{ $country->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                    </div>
                  <div class="col-md-4">
                      <label for="state" class="form-label fw-bold">Estado / Provincia</label>
                      <select name="state" id="state" class="form-control form-control-lg border border-radius-lg p-2" required {{ $selectedCountryId ? '' : 'disabled' }}>
                          <option value="">Selecciona un estado</option>
                        @foreach($states->where('country_id', $selectedCountryId) as $state)
                          <option value="{{ $state->id }}" {{ (string) $selectedStateId === (string) $state->id ? 'selected' : '' }}>
                            {{ $state->name }}
                          </option>
                        @endforeach
                      </select>
                      <div id="state-loading" style="display:none;">Cargando estados...</div>
                  </div>
                  <div class="col-md-4">
                      <label for="city" class="form-label fw-bold">Ciudad</label>
                      <select name="city" id="city" class="form-control form-control-lg border border-radius-lg p-2" required {{ $selectedStateId ? '' : 'disabled' }}>
                          <option value="">Selecciona una ciudad</option>
                        @foreach($cities->where('state_id', $selectedStateId) as $city)
                          <option value="{{ $city->id }}" {{ (string) $selectedCityId === (string) $city->id ? 'selected' : '' }}>
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
                      <input type="text" name="phone_number" id="phone_number" class="form-control form-control-lg" placeholder="Ej: 4121234567" inputmode="numeric" autocomplete="tel-national" data-numeric-only="true" required>
                  </div>
                </div>

                @php
                  $weekDays = [
                    'monday' => 'Lunes',
                    'tuesday' => 'Martes',
                    'wednesday' => 'Miércoles',
                    'thursday' => 'Jueves',
                    'friday' => 'Viernes',
                    'saturday' => 'Sábado',
                    'sunday' => 'Domingo',
                  ];
                @endphp
                <div class="mb-4" id="createTenantUserScheduleFields" style="display: {{ strtolower((string) old('business_type', 'tienda')) === 'tienda' ? 'block' : 'none' }};">
                  <label class="form-label fw-bold d-block">Días laborales y horario (opcional)</label>
                  <div class="row g-2 mb-3">
                    @foreach($weekDays as $dayKey => $dayLabel)
                      <div class="col-6 col-md-3">
                        <div class="form-check">
                          <input class="form-check-input" type="checkbox" name="working_days[]" id="register_working_day_{{ $dayKey }}" value="{{ $dayKey }}">
                          <label class="form-check-label" for="register_working_day_{{ $dayKey }}">{{ $dayLabel }}</label>
                        </div>
                      </div>
                    @endforeach
                  </div>
                  <div class="row g-3">
                    <div class="col-12 col-md-6">
                      <label for="opening_time" class="form-label fw-bold">Hora de apertura</label>
                      <input type="time" name="opening_time" id="opening_time" class="form-control form-control-lg" value="{{ old('opening_time') }}">
                    </div>
                    <div class="col-12 col-md-6">
                      <label for="closing_time" class="form-label fw-bold">Hora de cierre</label>
                      <input type="time" name="closing_time" id="closing_time" class="form-control form-control-lg" value="{{ old('closing_time') }}">
                    </div>
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
                    <input type="text" name="users[owner][dni]" id="owner_dni" class="form-control form-control-lg" inputmode="numeric" autocomplete="off" data-numeric-only="true" required>
                  </div>
                  <div class="col-md-6 mb-3">
                    <label for="owner_phone" class="form-label">Teléfono</label>
                    <input type="hidden" name="users[owner][phone_number]" id="owner_phone_hidden">
                    <div class="row g-2">
                      <div class="col-4">
                        <select id="owner_phone_code" class="form-select form-select-lg" aria-label="Código de país del owner" required>
                          <option value="+58">🇻🇪 +58</option>
                          <option value="+1">🇺🇸 +1</option>
                          <option value="+34">🇪🇸 +34</option>
                          <option value="+57">🇨🇴 +57</option>
                          <option value="+55">🇧🇷 +55</option>
                          <option value="+52">🇲🇽 +52</option>
                        </select>
                      </div>
                      <div class="col-8">
                        <input type="text" id="owner_phone" class="form-control form-control-lg" placeholder="Ej: 4121234567" inputmode="numeric" autocomplete="tel-national" data-numeric-only="true" required>
                      </div>
                    </div>
                  </div>
                  <div class="col-md-6 mb-3">
                    <label for="owner_email" class="form-label">Tu correo</label>
                    <div class="d-flex gap-2 align-items-start">
                      <input type="email" name="users[owner][email]" id="owner_email" class="form-control form-control-lg" required>
                      <button type="button" class="btn btn-outline-secondary" id="reusePreviousEmailBtn">Usar el correo anterior</button>
                    </div>
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

                <div class="terms-consent-card mt-4">
                  <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                    <div>
                      <h6 class="fw-bold mb-1">Términos y condiciones del registro</h6>
                      <p class="text-muted mb-0">Revisa el contrato y confirma la aceptación antes de crear tu tienda.</p>
                    </div>
                    <div class="d-flex gap-2 flex-wrap">
                      <button type="button" class="btn btn-outline-secondary btn-sm" id="openTermsModalBtn">Leer contrato</button>
                      <a href="{{ route('legal.terms.pdf') }}" target="_blank" rel="noopener" class="btn btn-outline-dark btn-sm">Abrir PDF</a>
                    </div>
                  </div>
                  <ul class="terms-consent-list">
                    <li>Los datos registrados deben ser reales y corresponder a la empresa o servicio que se está creando.</li>
                    <li>El uso de SHOPIX queda sujeto a las capacidades, restricciones y límites operativos del plan seleccionado.</li>
                    <li>La creación de la tienda implica aceptación del contrato, políticas de uso y tratamiento operativo de la información suministrada.</li>
                  </ul>
                  <div class="form-check mt-3">
                    <input class="form-check-input" type="checkbox" value="1" id="accept_terms" name="accept_terms" {{ old('accept_terms') ? 'checked' : '' }} required>
                    <label class="form-check-label" for="accept_terms">
                      He leído y acepto los términos y condiciones del servicio de SHOPIX.
                    </label>
                  </div>
                </div>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="aiGenerateModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="aiModalTitle">Generar imagen con IA</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <p class="mb-2" id="aiModalQuestion">Habla con Gemini para generar y ajustar tu imagen.</p>
          <div id="aiPreviewWrapper" class="mb-3 d-none">
            <label class="form-label fw-bold mb-2">Resultado actual</label>
            <img id="aiGeneratedPreview" src="#" class="img-fluid rounded border" alt="Imagen generada por IA">
          </div>
          <div id="aiChatMessages" class="ai-chat-box mb-3"></div>
          <div id="aiGeneratingStatus" class="mt-3 d-none">
            <div class="d-flex align-items-center">
              <div class="spinner-border spinner-border-sm me-2 text-primary" role="status"></div>
              <span>Generando imagen</span>
              <span class="ai-loading-dots"><span></span><span></span><span></span></span>
            </div>
            <small class="text-muted d-block mt-2">Puedes seguir pidiendo ajustes hasta que te guste el resultado.</small>
          </div>
          <div class="mt-3">
            <input type="file" id="aiReferenceImage" class="d-none" accept=".png,.jpg,.jpeg,.webp">
            <div class="d-flex gap-2 align-items-end">
              <button type="button" class="btn ai-attach-btn" id="aiAttachBtn" title="Adjuntar imagen">📎</button>
              <textarea id="aiPromptInput" class="form-control" rows="2" placeholder="Escribe tu mensaje para la IA..."></textarea>
              <button type="button" class="btn btn-primary" id="aiGenerateBtn" title="Enviar mensaje">➤</button>
            </div>
            <small class="text-muted d-block mt-1" id="aiAttachedName"></small>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="aiCancelBtn">Cancelar</button>
          <button type="button" class="btn btn-outline-dark" id="aiRemoveBgBtn">Quitar fondo</button>
          <button type="button" class="btn btn-outline-primary" id="aiDownloadBtn" disabled>Descargar</button>
          <button type="button" class="btn btn-outline-success" id="aiUseImageBtn" disabled>Usar esta imagen</button>
        </div>
      </div>
    </div>
  </div>

  <div class="modal fade" id="termsPdfModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Términos y condiciones</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
        </div>
        <div class="modal-body pt-2">
          <iframe
            id="termsPdfFrame"
            class="terms-modal-frame"
            src="{{ route('legal.terms.pdf') }}"
            title="Términos y condiciones en PDF"
          ></iframe>
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
    const tenantAiImageEndpoint = @json(route('tenant.ai-image'));
    const tenantAiCopyEndpoint = @json(route('tenant.ai-copy'));
    const googleMapsApiKey = @json(env('GOOGLE_MAPS_API_KEY'));
    const TENANT_SAFE_IMAGE_BYTES = 1200 * 1024;
    const TENANT_IMAGE_MAX_DIMENSION = 2200;
    let aiModalInstance = null;
    let termsModalInstance = null;
    let currentAiTarget = null;
    let aiChatHistory = [];
    let aiLatestResult = null;

    function pickReadableTextColor(hex) {
      const normalized = normalizeTenantHexValue(hex).replace('#', '');
      if (normalized.length !== 6) {
        return '#0F172A';
      }

      const red = parseInt(normalized.slice(0, 2), 16);
      const green = parseInt(normalized.slice(2, 4), 16);
      const blue = parseInt(normalized.slice(4, 6), 16);
      const brightness = ((red * 299) + (green * 587) + (blue * 114)) / 1000;

      return brightness > 155 ? '#0F172A' : '#FFFFFF';
    }

    function normalizeTenantNumericValue(value) {
      return String(value ?? '').replace(/\D+/g, '');
    }

    function normalizeTenantHexValue(value) {
      const raw = String(value ?? '').trim().toUpperCase().replace(/[^#A-F0-9]/g, '');
      if (!raw) {
        return '';
      }

      const withHash = raw.startsWith('#') ? raw : `#${raw}`;
      return withHash.slice(0, 7);
    }

    function showTenantToast(message, type = 'info') {
      const container = document.getElementById('shopixToastContainer');
      if (!container || !message) {
        return;
      }

      const toast = document.createElement('div');
      toast.className = `shopix-toast ${type}`;
      toast.textContent = message;
      container.appendChild(toast);

      requestAnimationFrame(() => toast.classList.add('show'));

      setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => toast.remove(), 220);
      }, 3600);
    }

    function setTenantSubmitLoading(button, isLoading, loadingText = 'Creando tienda...') {
      if (!button) return;

      if (isLoading) {
        if (button.dataset.loading === '1') return;
        button.dataset.loading = '1';
        button.dataset.originalHtml = button.innerHTML;
        button.disabled = true;
        button.innerHTML = `<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>${loadingText}`;
        return;
      }

      button.disabled = false;
      button.dataset.loading = '0';
      if (button.dataset.originalHtml) {
        button.innerHTML = button.dataset.originalHtml;
      }
    }

    function formatTenantSize(bytes) {
      return `${(Number(bytes || 0) / (1024 * 1024)).toFixed(2)} MB`;
    }

    function loadTenantImageElement(file) {
      return new Promise((resolve, reject) => {
        const img = new Image();
        const objectUrl = URL.createObjectURL(file);
        img.onload = () => {
          URL.revokeObjectURL(objectUrl);
          resolve(img);
        };
        img.onerror = () => {
          URL.revokeObjectURL(objectUrl);
          reject(new Error('No se pudo procesar la imagen.'));
        };
        img.src = objectUrl;
      });
    }

    function tenantCanvasToBlob(canvas, type, quality) {
      return new Promise((resolve) => canvas.toBlob((blob) => resolve(blob), type, quality));
    }

    async function optimizeTenantImageFile(file) {
      const type = String(file?.type || '').toLowerCase();
      if (type === 'image/svg+xml') {
        return { file, changed: false, convertedToWebp: false, stillLarge: file.size > TENANT_SAFE_IMAGE_BYTES };
      }

      const rasterTypes = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
      if (!rasterTypes.includes(type)) {
        return { file, changed: false, convertedToWebp: false, stillLarge: file.size > TENANT_SAFE_IMAGE_BYTES };
      }

      const source = await loadTenantImageElement(file);
      const originalWidth = source.naturalWidth || source.width;
      const originalHeight = source.naturalHeight || source.height;

      let width = originalWidth;
      let height = originalHeight;
      if (width > TENANT_IMAGE_MAX_DIMENSION || height > TENANT_IMAGE_MAX_DIMENSION) {
        const scale = Math.min(TENANT_IMAGE_MAX_DIMENSION / width, TENANT_IMAGE_MAX_DIMENSION / height);
        width = Math.max(1, Math.round(width * scale));
        height = Math.max(1, Math.round(height * scale));
      }

      const canvas = document.createElement('canvas');
      const ctx = canvas.getContext('2d');
      canvas.width = width;
      canvas.height = height;
      ctx.drawImage(source, 0, 0, width, height);

      const targetType = 'image/webp';
      const convertedToWebp = type !== 'image/webp';
      let blob = await tenantCanvasToBlob(canvas, targetType, 0.9);

      while (blob && blob.size > TENANT_SAFE_IMAGE_BYTES && width > 640 && height > 640) {
        width = Math.max(640, Math.round(width * 0.85));
        height = Math.max(640, Math.round(height * 0.85));
        canvas.width = width;
        canvas.height = height;
        ctx.drawImage(source, 0, 0, width, height);
        blob = await tenantCanvasToBlob(canvas, targetType, 0.82);
      }

      if (!blob) {
        return { file, changed: false, convertedToWebp: false, stillLarge: file.size > TENANT_SAFE_IMAGE_BYTES };
      }

      const changed = blob.size !== file.size || width !== originalWidth || height !== originalHeight || convertedToWebp;
      if (!changed) {
        return { file, changed: false, convertedToWebp: false, stillLarge: file.size > TENANT_SAFE_IMAGE_BYTES };
      }

      const baseName = file.name.replace(/\.[^.]+$/, '');
      const optimizedFile = new File([blob], `${baseName}.webp`, { type: targetType });

      return {
        file: optimizedFile,
        changed: true,
        convertedToWebp,
        stillLarge: optimizedFile.size > TENANT_SAFE_IMAGE_BYTES,
      };
    }

    async function optimizeTenantInputFile(inputId, previewId) {
      const input = document.getElementById(inputId);
      const preview = document.getElementById(previewId);
      const selectedFile = input?.files?.[0];
      if (!input || !preview || !selectedFile) {
        return;
      }

      try {
        const originalSize = Number(selectedFile.size || 0);
        const optimized = await optimizeTenantImageFile(selectedFile);
        const optimizedSize = Number(optimized.file?.size || originalSize);
        const recommendedLimit = formatTenantSize(TENANT_SAFE_IMAGE_BYTES);
        const dt = new DataTransfer();
        dt.items.add(optimized.file);
        input.files = dt.files;

        preview.src = URL.createObjectURL(optimized.file);
        preview.classList.remove('d-none');

        if (optimized.changed) {
          let message = `Imagen optimizada automaticamente: ${formatTenantSize(originalSize)} -> ${formatTenantSize(optimizedSize)} (max recomendado ${recommendedLimit}).`;
          if (optimized.convertedToWebp) {
            message = `Imagen convertida a WEBP y optimizada: ${formatTenantSize(originalSize)} -> ${formatTenantSize(optimizedSize)} (max recomendado ${recommendedLimit}).`;
          }
          if (optimized.stillLarge) {
            message += ` Aun supera el maximo recomendado (${recommendedLimit}); baja la resolucion manualmente.`;
          }
          showTenantToast(message, optimized.stillLarge ? 'warning' : 'info');
        } else if (optimized.stillLarge) {
          showTenantToast(`La imagen pesa ${formatTenantSize(optimizedSize)}. Recomendado por imagen: ${recommendedLimit}.`, 'warning');
        }
      } catch (error) {
        preview.src = URL.createObjectURL(selectedFile);
        preview.classList.remove('d-none');
        showTenantToast('No se pudo optimizar la imagen seleccionada.', 'warning');
      }
    }

    async function setGeneratedImageInInput({ inputId, previewId, base64Data, mimeType, fileName }) {
      const input = document.getElementById(inputId);
      const preview = document.getElementById(previewId);
      if (!input || !preview || !base64Data) {
        return;
      }

      const byteChars = atob(base64Data);
      const byteNumbers = new Array(byteChars.length);
      for (let index = 0; index < byteChars.length; index += 1) {
        byteNumbers[index] = byteChars.charCodeAt(index);
      }

      const byteArray = new Uint8Array(byteNumbers);
      const blob = new Blob([byteArray], { type: mimeType || 'image/png' });
      const originalFile = new File([blob], fileName, { type: mimeType || 'image/png' });
      const optimized = await optimizeTenantImageFile(originalFile);
      const file = optimized.file;
      const dataTransfer = new DataTransfer();
      dataTransfer.items.add(file);
      input.files = dataTransfer.files;

      preview.src = URL.createObjectURL(file);
      preview.classList.remove('d-none');

      if (optimized.changed) {
        const toastMessage = optimized.convertedToWebp
          ? 'La imagen de IA se optimizo y se convirtio a WEBP.'
          : 'La imagen de IA se optimizo para subirla sin errores.';
        showTenantToast(toastMessage, optimized.stillLarge ? 'warning' : 'info');
      }
    }

    function appendAiMessage(role, content) {
      const chatBox = document.getElementById('aiChatMessages');
      const item = document.createElement('div');
      item.className = `mb-2 ${role === 'assistant' ? '' : 'text-end'}`;
      const bubble = document.createElement('div');
      bubble.className = role === 'assistant' ? 'd-inline-block p-2 rounded bg-white border' : 'd-inline-block p-2 rounded text-white bg-primary';
      bubble.style.maxWidth = '90%';
      bubble.textContent = content;
      item.appendChild(bubble);
      chatBox.appendChild(item);
      chatBox.scrollTop = chatBox.scrollHeight;
    }

    function getReferenceImageData() {
      const input = document.getElementById('aiReferenceImage');
      const file = input?.files?.[0];
      if (!file) {
        return Promise.resolve(null);
      }

      return new Promise((resolve, reject) => {
        const reader = new FileReader();
        reader.onload = () => {
          const dataUrl = String(reader.result || '');
          const base64 = dataUrl.includes(',') ? dataUrl.split(',')[1] : dataUrl;
          resolve({ data: base64, mime: file.type || 'image/png' });
        };
        reader.onerror = () => reject(new Error('No se pudo leer la imagen de referencia.'));
        reader.readAsDataURL(file);
      });
    }

    function setAiLoadingState(isLoading) {
      const status = document.getElementById('aiGeneratingStatus');
      const generateBtn = document.getElementById('aiGenerateBtn');
      const cancelBtn = document.getElementById('aiCancelBtn');
      const attachBtn = document.getElementById('aiAttachBtn');
      const removeBgBtn = document.getElementById('aiRemoveBgBtn');
      status.classList.toggle('d-none', !isLoading);
      generateBtn.disabled = isLoading;
      cancelBtn.disabled = isLoading;
      if (attachBtn) {
        attachBtn.disabled = isLoading;
      }
      if (removeBgBtn) {
        removeBgBtn.disabled = isLoading;
      }
    }

    function closeAiModal() {
      aiModalInstance?.hide();
    }

    async function getAiSourceImageData() {
      if (aiLatestResult?.base64Data) {
        return {
          data: aiLatestResult.base64Data,
          mime: aiLatestResult.mimeType || 'image/png',
        };
      }

      const input = currentAiTarget ? document.getElementById(currentAiTarget.inputId) : null;
      const currentFile = input?.files?.[0];
      if (currentFile) {
        return new Promise((resolve, reject) => {
          const reader = new FileReader();
          reader.onload = () => {
            const dataUrl = String(reader.result || '');
            resolve({
              data: dataUrl.includes(',') ? dataUrl.split(',')[1] : dataUrl,
              mime: currentFile.type || 'image/png',
            });
          };
          reader.onerror = () => reject(new Error('No se pudo leer la imagen actual.'));
          reader.readAsDataURL(currentFile);
        });
      }

      return getReferenceImageData();
    }

    async function removeAiImageBackground() {
      if (!currentAiTarget) {
        return;
      }

      const sourceImage = await getAiSourceImageData();
      if (!sourceImage?.data) {
        showTenantToast('Adjunta o genera primero una imagen para quitarle el fondo.', 'warning');
        return;
      }

      appendAiMessage('user', 'Quita el fondo de esta imagen.');
      aiChatHistory.push({ role: 'user', content: 'Quita el fondo de esta imagen.' });
      setAiLoadingState(true);

      try {
        const response = await fetch(tenantAiImageEndpoint, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
          },
          body: JSON.stringify({
            type: currentAiTarget.type,
            prompt: 'Quita el fondo y deja la imagen lista para usar como logo.',
            messages: aiChatHistory,
            reference_image_data: sourceImage.data,
            reference_image_mime: sourceImage.mime,
            image_operation: 'remove_background',
          }),
        });

        const payload = await response.json();
        if (!response.ok || !payload.success) {
          throw new Error(payload.error || payload.message || 'No se pudo quitar el fondo.');
        }

        aiLatestResult = {
          base64Data: payload.data,
          mimeType: payload.mime_type || 'image/png',
          fileName: currentAiTarget.fileName,
          inputId: currentAiTarget.inputId,
          previewId: currentAiTarget.previewId,
        };

        renderGeneratedPreview();
        appendAiMessage('assistant', 'Listo. Eliminé el fondo de la imagen actual.');
        aiChatHistory.push({ role: 'assistant', content: 'Se eliminó el fondo de la imagen.' });
      } catch (error) {
        appendAiMessage('assistant', 'No pude quitar el fondo. Intenta con otra imagen o vuelve a generarla.');
        showTenantToast(error.message || 'Error al quitar el fondo.', 'error');
      } finally {
        setAiLoadingState(false);
      }
    }

    function openAiModal(target) {
      currentAiTarget = target;
      aiLatestResult = null;
      aiChatHistory = [];
      const title = document.getElementById('aiModalTitle');
      const question = document.getElementById('aiModalQuestion');
      const prompt = document.getElementById('aiPromptInput');
      const chatBox = document.getElementById('aiChatMessages');
      const downloadBtn = document.getElementById('aiDownloadBtn');
      const useBtn = document.getElementById('aiUseImageBtn');
      const previewWrapper = document.getElementById('aiPreviewWrapper');
      const referenceInput = document.getElementById('aiReferenceImage');
      const attachedName = document.getElementById('aiAttachedName');

      title.textContent = 'Generar logo con IA';
      question.textContent = 'Chatea con Gemini hasta que el logo quede como quieres.';
      prompt.placeholder = 'Ej: logo minimalista para tienda deportiva en tonos azul y blanco, sin texto';

      prompt.value = '';
      referenceInput.value = '';
      attachedName.textContent = '';
      chatBox.innerHTML = '';
      appendAiMessage('assistant', 'Estoy listo para ayudarte. Describe la imagen que quieres generar.');
      previewWrapper.classList.add('d-none');
      downloadBtn.disabled = true;
      useBtn.disabled = true;
      setAiLoadingState(false);
      aiModalInstance.show();
    }

    function renderGeneratedPreview() {
      const previewWrapper = document.getElementById('aiPreviewWrapper');
      const preview = document.getElementById('aiGeneratedPreview');
      const downloadBtn = document.getElementById('aiDownloadBtn');
      const useBtn = document.getElementById('aiUseImageBtn');

      if (!aiLatestResult) {
        previewWrapper.classList.add('d-none');
        downloadBtn.disabled = true;
        useBtn.disabled = true;
        return;
      }

      preview.src = `data:${aiLatestResult.mimeType};base64,${aiLatestResult.base64Data}`;
      previewWrapper.classList.remove('d-none');
      downloadBtn.disabled = false;
      useBtn.disabled = false;
    }

    function downloadLatestImage() {
      if (!aiLatestResult) {
        return;
      }

      const byteChars = atob(aiLatestResult.base64Data);
      const byteNumbers = new Array(byteChars.length);
      for (let index = 0; index < byteChars.length; index += 1) {
        byteNumbers[index] = byteChars.charCodeAt(index);
      }
      const byteArray = new Uint8Array(byteNumbers);
      const blob = new Blob([byteArray], { type: aiLatestResult.mimeType || 'image/png' });
      const fileUrl = URL.createObjectURL(blob);
      const downloadLink = document.createElement('a');
      downloadLink.href = fileUrl;
      downloadLink.download = aiLatestResult.fileName;
      document.body.appendChild(downloadLink);
      downloadLink.click();
      downloadLink.remove();
      setTimeout(() => URL.revokeObjectURL(fileUrl), 2500);
    }

    async function generateImageWithGemini({ type, prompt, inputId, previewId, fileName }) {
      if (!prompt) {
        showTenantToast('Debes escribir un prompt para generar la imagen.', 'warning');
        return;
      }

      appendAiMessage('user', prompt);
      aiChatHistory.push({ role: 'user', content: prompt });
      setAiLoadingState(true);

      try {
        const referenceData = await getReferenceImageData();
        const useStoreColors = true;
        const colorPrimary = document.getElementById('color_primary')?.value || null;
        const colorSecondary = document.getElementById('color_secondary')?.value || null;
        const colorAccent = document.getElementById('color_accent')?.value || null;

        const response = await fetch(tenantAiImageEndpoint, {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
          },
          body: JSON.stringify({
            type,
            prompt,
            messages: aiChatHistory,
            reference_image_data: referenceData?.data || null,
            reference_image_mime: referenceData?.mime || null,
            shop_colors: useStoreColors ? {
              color_primary: colorPrimary,
              color_secondary: colorSecondary,
              color_accent: colorAccent,
            } : null,
          }),
        });

        const payload = await response.json();
        if (!response.ok || !payload.success) {
          throw new Error(payload.error || payload.message || 'No se pudo generar la imagen.');
        }

        aiLatestResult = {
          base64Data: payload.data,
          mimeType: payload.mime_type || 'image/png',
          fileName,
          inputId,
          previewId,
        };

        renderGeneratedPreview();
        appendAiMessage('assistant', 'Listo. Te dejé una nueva versión de la imagen. Puedes pedir cambios o usar esta versión.');
        aiChatHistory.push({ role: 'assistant', content: 'Imagen generada y mostrada al usuario.' });
        document.getElementById('aiPromptInput').value = '';

      } catch (error) {
        appendAiMessage('assistant', 'No pude generar la imagen. Ajusta el prompt e intenta nuevamente.');
        showTenantToast(error.message || 'Error al generar la imagen con Gemini.', 'error');
      } finally {
        setAiLoadingState(false);
      }
    }

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

      map.addListener('click', function(event) {
        marker.setPosition(event.latLng);
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
      const openLogoAiModalBtn = document.getElementById('openLogoAiModalBtn');
      const aiGenerateBtn = document.getElementById('aiGenerateBtn');
      const generateStoreCopyBtn = document.getElementById('generateStoreCopyBtn');
      const storeCopyAiStatus = document.getElementById('storeCopyAiStatus');
      const aiDownloadBtn = document.getElementById('aiDownloadBtn');
      const aiUseImageBtn = document.getElementById('aiUseImageBtn');
      const aiRemoveBgBtn = document.getElementById('aiRemoveBgBtn');
      const aiAttachBtn = document.getElementById('aiAttachBtn');
      const aiReferenceImage = document.getElementById('aiReferenceImage');
      const aiPromptInput = document.getElementById('aiPromptInput');
      const phoneCodeSelect = document.getElementById('phone_code');
      const phoneNumberInput = document.getElementById('phone_number');
      const ownerDniInput = document.getElementById('owner_dni');
      const ownerPhoneInput = document.getElementById('owner_phone');
      const ownerPhoneCodeSelect = document.getElementById('owner_phone_code');
      const ownerPhoneHiddenInput = document.getElementById('owner_phone_hidden');
      const ownerEmailInput = document.getElementById('owner_email');
      const contactEmailInput = document.getElementById('email');
      const reusePreviousEmailBtn = document.getElementById('reusePreviousEmailBtn');
      const openTermsModalBtn = document.getElementById('openTermsModalBtn');
      const countrySelect = document.getElementById('country');
      const stateSelect = document.getElementById('state');
      const citySelect = document.getElementById('city');
      const stateLoading = document.getElementById('state-loading');
      const cityLoading = document.getElementById('city-loading');
      const businessTypeSelect = document.getElementById('business_type');
      const economicActivitySelect = document.getElementById('economic_activity');
      const tenantForm = document.getElementById('tenantForm');
      const hexInputs = Array.from(document.querySelectorAll('.hex-input'));
      const termsCheckbox = document.getElementById('accept_terms');

      const colorBindings = [
        { hexId: 'color_primary', pickerId: 'color_primary_picker', swatchId: 'color_primary_swatch' },
        { hexId: 'color_secondary', pickerId: 'color_secondary_picker', swatchId: 'color_secondary_swatch' },
        { hexId: 'color_accent', pickerId: 'color_accent_picker', swatchId: 'color_accent_swatch' },
      ];

      const businessCatalog = {
        tienda: [
          'Supermercado y Abastos',
          'Panaderia y Pasteleria',
          'Moda y Boutique',
          'Calzado y Marroquineria',
          'Ferreteria y Construccion',
          'Hogar, Muebles y Decoracion',
          'Tecnologia y Computacion',
          'Telefonia y Accesorios',
          'Farmacia y Bienestar',
          'Mascotas y Agrotienda',
          'Papeleria, Libros y Juguetes',
          'Repuestos y Accesorios Automotrices'
        ],
        servicio: [
          'Restaurante, Cafeteria y Delivery',
          'Barberia, Salon y Spa',
          'Consultorio Medico y Odontologico',
          'Asesoria Legal, Contable y Administrativa',
          'Soporte Tecnico y Reparaciones',
          'Educacion, Cursos e Idiomas',
          'Logistica, Envios y Mensajeria',
          'Fitness, Deporte y Bienestar',
          'Eventos, Fotografia y Produccion',
          'Mantenimiento, Limpieza e Instalaciones'
        ]
      };

      const businessExamples = {
        'Supermercado y Abastos': 'Mini market, abasto vecinal, bodegon, distribuidora de viveres.',
        'Panaderia y Pasteleria': 'Panaderias, reposteria, postres por encargo, cafe bakery.',
        'Moda y Boutique': 'Ropa femenina, masculina, infantil, boutique de temporada.',
        'Calzado y Marroquineria': 'Zapaterias, bolsos, carteras, cinturones y accesorios de cuero.',
        'Ferreteria y Construccion': 'Ferreterias, herramientas, materiales de obra, pinturas y acabados.',
        'Hogar, Muebles y Decoracion': 'Mueblerias, colchones, decoracion, iluminacion y hogar.',
        'Tecnologia y Computacion': 'Computadoras, gaming, electronica, impresoras, consumibles.',
        'Telefonia y Accesorios': 'Celulares, tablets, fundas, cargadores, wearables.',
        'Farmacia y Bienestar': 'Farmacias, suplementos, cuidado personal, ortopedia ligera.',
        'Mascotas y Agrotienda': 'Pet shop, alimento para mascotas, insumos veterinarios, agroinsumos.',
        'Papeleria, Libros y Juguetes': 'Papelerias, librerias, regalos educativos, jugueterias.',
        'Repuestos y Accesorios Automotrices': 'Lubricantes, baterias, repuestos, accesorios para vehiculos.',
        'Restaurante, Cafeteria y Delivery': 'Restaurantes, lunch, cafeterias, dark kitchen, delivery.',
        'Barberia, Salon y Spa': 'Barberias, peluquerias, manicure, spa, estetica facial.',
        'Consultorio Medico y Odontologico': 'Odontologia, medicina general, pediatria, psicologia, fisioterapia.',
        'Asesoria Legal, Contable y Administrativa': 'Abogados, contadores, asesoria fiscal, outsourcing administrativo.',
        'Soporte Tecnico y Reparaciones': 'Reparacion de telefonos, laptops, electrodomesticos, redes, CCTV.',
        'Educacion, Cursos e Idiomas': 'Academias, cursos online, capacitacion tecnica, clases personalizadas.',
        'Logistica, Envios y Mensajeria': 'Courier, motomensajeria, transporte de paquetes, encomiendas.',
        'Fitness, Deporte y Bienestar': 'Entrenadores, gimnasios, yoga, pilates, nutricion deportiva.',
        'Eventos, Fotografia y Produccion': 'Fotografia, video, bodas, eventos corporativos, produccion creativa.',
        'Mantenimiento, Limpieza e Instalaciones': 'Limpieza residencial, electricidad, plomeria, aires acondicionados.'
      };

      const businessDescriptions = {
        tienda: 'Selecciona el rubro comercial que mejor representa lo que vendes para personalizar mejor la identidad inicial de tu tienda.',
        servicio: 'Elige el sector del servicio para reflejar con más precisión tu propuesta profesional desde el registro.'
      };

      const setStoreCopyButtonLoading = (isLoading) => {
        if (!generateStoreCopyBtn) {
          return;
        }

        if (isLoading) {
          if (generateStoreCopyBtn.dataset.loading === '1') {
            return;
          }

          generateStoreCopyBtn.dataset.loading = '1';
          generateStoreCopyBtn.dataset.originalHtml = generateStoreCopyBtn.innerHTML;
          generateStoreCopyBtn.disabled = true;
          generateStoreCopyBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span>Generando copy...';
          return;
        }

        generateStoreCopyBtn.disabled = false;
        generateStoreCopyBtn.dataset.loading = '0';
        if (generateStoreCopyBtn.dataset.originalHtml) {
          generateStoreCopyBtn.innerHTML = generateStoreCopyBtn.dataset.originalHtml;
        }
      };

      const syncColorBinding = ({ hexId, pickerId, swatchId }) => {
        const hexInput = document.getElementById(hexId);
        const pickerInput = document.getElementById(pickerId);
        const swatch = document.getElementById(swatchId);
        if (!hexInput || !pickerInput || !swatch) {
          return;
        }

        const normalized = normalizeTenantHexValue(hexInput.value);
        const colorValue = /^#(?:[A-F0-9]{6}|[A-F0-9]{3})$/i.test(normalized) ? normalized : '#FFFFFF';
        hexInput.value = normalized || colorValue;
        pickerInput.value = colorValue.length === 4
          ? `#${colorValue[1]}${colorValue[1]}${colorValue[2]}${colorValue[2]}${colorValue[3]}${colorValue[3]}`
          : colorValue;
        swatch.style.background = colorValue;
        swatch.style.color = pickReadableTextColor(pickerInput.value);
        swatch.textContent = colorValue;
      };

      const refreshEconomicActivities = (selectedValue = '') => {
        if (!businessTypeSelect || !economicActivitySelect) {
          return;
        }

        const businessType = String(businessTypeSelect.value || 'tienda').toLowerCase() === 'servicio' ? 'servicio' : 'tienda';
        const options = businessCatalog[businessType] || [];
        const help = document.getElementById('economic_activity_help');
        const examples = document.getElementById('economic_activity_examples');

        economicActivitySelect.innerHTML = '<option value="">Selecciona un rubro</option>';
        options.forEach((option) => {
          const selected = String(option).toLowerCase() === String(selectedValue || '').toLowerCase();
          economicActivitySelect.insertAdjacentHTML('beforeend', `<option value="${option}" ${selected ? 'selected' : ''}>${option}</option>`);
        });

        const currentValue = economicActivitySelect.value;
        help.textContent = businessDescriptions[businessType] || 'Selecciona una categoria para ver ejemplos.';
        examples.textContent = currentValue && businessExamples[currentValue]
          ? `Ejemplos específicos: ${businessExamples[currentValue]}`
          : 'Selecciona una categoria para ver ejemplos y una guía más específica del rubro.';
      };

      aiModalInstance = new bootstrap.Modal(document.getElementById('aiGenerateModal'));
      termsModalInstance = new bootstrap.Modal(document.getElementById('termsPdfModal'));

      const normalizeSlug = (value) => String(value ?? '')
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/-{2,}/g, '-')
        .replace(/^-+|-+$/g, '');

      slugInput.addEventListener('input', () => {
        const normalizedValue = normalizeSlug(slugInput.value);

        if (slugInput.value !== normalizedValue) {
          slugInput.value = normalizedValue;
        }

        slugPreview.textContent = normalizedValue || 'mi-empresa';
      });

      document.querySelectorAll('[data-numeric-only="true"]').forEach((input) => {
        input.addEventListener('input', () => {
          const nextValue = normalizeTenantNumericValue(input.value);
          if (input.value !== nextValue) {
            input.value = nextValue;
          }
        });
      });

      hexInputs.forEach((input) => {
        input.addEventListener('input', () => {
          input.value = normalizeTenantHexValue(input.value);
          input.classList.remove('is-invalid');
          const binding = colorBindings.find((item) => item.hexId === input.id);
          if (binding) {
            syncColorBinding(binding);
          }
        });

        input.addEventListener('blur', () => {
          input.value = normalizeTenantHexValue(input.value);
          const binding = colorBindings.find((item) => item.hexId === input.id);
          if (binding) {
            syncColorBinding(binding);
          }
        });
      });

      colorBindings.forEach((binding) => {
        const pickerInput = document.getElementById(binding.pickerId);
        pickerInput?.addEventListener('input', () => {
          const hexInput = document.getElementById(binding.hexId);
          hexInput.value = normalizeTenantHexValue(pickerInput.value);
          hexInput.classList.remove('is-invalid');
          syncColorBinding(binding);
        });
        syncColorBinding(binding);
      });

      const syncOwnerPhoneValue = () => {
        if (!ownerPhoneHiddenInput) {
          return;
        }

        const localNumber = normalizeTenantNumericValue(ownerPhoneInput?.value || '');
        const countryCode = String(ownerPhoneCodeSelect?.value || '').trim();
        ownerPhoneHiddenInput.value = localNumber ? `${countryCode}${localNumber}` : '';
      };

      ownerPhoneInput?.addEventListener('input', syncOwnerPhoneValue);
      ownerPhoneCodeSelect?.addEventListener('change', syncOwnerPhoneValue);
      phoneCodeSelect?.addEventListener('change', () => {
        if (ownerPhoneCodeSelect && !ownerPhoneInput?.value.trim()) {
          ownerPhoneCodeSelect.value = phoneCodeSelect.value;
          syncOwnerPhoneValue();
        }
      });

      reusePreviousEmailBtn?.addEventListener('click', () => {
        if (!contactEmailInput?.value.trim()) {
          showTenantToast('Primero escribe el correo de contacto para reutilizarlo.', 'warning');
          return;
        }

        ownerEmailInput.value = contactEmailInput.value.trim();
        ownerEmailInput.dispatchEvent(new Event('input', { bubbles: true }));
      });

      openTermsModalBtn?.addEventListener('click', () => {
        termsModalInstance?.show();
      });

      generateStoreCopyBtn?.addEventListener('click', async () => {
        const storeName = String(document.getElementById('name')?.value || '').trim();
        const businessType = String(businessTypeSelect?.value || '').trim();
        const economicActivity = String(economicActivitySelect?.value || '').trim();
        const sloganInput = document.getElementById('slogan');
        const descriptionInput = document.getElementById('description');

        if (!storeName || !businessType || !economicActivity) {
          showTenantToast('Completa nombre, tipo de negocio y rubro antes de usar Gemini.', 'warning');
          return;
        }

        setStoreCopyButtonLoading(true);
        if (storeCopyAiStatus) {
          storeCopyAiStatus.textContent = 'Gemini está redactando una propuesta inicial para tu negocio...';
        }

        try {
          const response = await fetch(tenantAiCopyEndpoint, {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
              'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
              'Accept': 'application/json',
            },
            body: JSON.stringify({
              name: storeName,
              business_type: businessType,
              economic_activity: economicActivity,
            }),
          });

          const payload = await response.json().catch(() => ({}));
          if (!response.ok || !payload.success || !payload.data) {
            throw new Error(payload.message || 'No se pudo generar el copy comercial.');
          }

          if (sloganInput && payload.data.slogan) {
            sloganInput.value = payload.data.slogan;
          }

          if (descriptionInput && payload.data.description) {
            descriptionInput.value = payload.data.description;
          }

          if (storeCopyAiStatus) {
            storeCopyAiStatus.textContent = 'Propuesta aplicada. Puedes editar el slogan o la descripción antes de continuar.';
          }
          showTenantToast('Gemini generó una propuesta inicial para tu tienda.', 'success');
        } catch (error) {
          if (storeCopyAiStatus) {
            storeCopyAiStatus.textContent = 'No se pudo generar la propuesta en este momento.';
          }
          showTenantToast(error.message || 'Error al generar slogan y descripción con Gemini.', 'error');
        } finally {
          setStoreCopyButtonLoading(false);
        }
      });

      logoInput.addEventListener('change', async (event) => {
        if (!event.target.files?.length) {
          logoPreview.src = '#';
          logoPreview.classList.add('d-none');
          return;
        }

        await optimizeTenantInputFile('logo', 'logo-preview');
      });

      if (openLogoAiModalBtn) {
        openLogoAiModalBtn.addEventListener('click', () => {
          openAiModal({
            type: 'logo',
            inputId: 'logo',
            previewId: 'logo-preview',
            fileName: 'logo-gemini.png',
          });
        });
      }

      if (aiAttachBtn) {
        aiAttachBtn.addEventListener('click', () => aiReferenceImage?.click());
      }

      if (aiReferenceImage) {
        aiReferenceImage.addEventListener('change', () => {
          const file = aiReferenceImage.files?.[0];
          const attachedName = document.getElementById('aiAttachedName');
          attachedName.textContent = file ? `Adjunto: ${file.name}` : '';
        });
      }

      if (aiGenerateBtn) {
        aiGenerateBtn.addEventListener('click', async () => {
          if (!currentAiTarget) {
            return;
          }

          await generateImageWithGemini({
            type: currentAiTarget.type,
            prompt: aiPromptInput.value.trim(),
            inputId: currentAiTarget.inputId,
            previewId: currentAiTarget.previewId,
            fileName: currentAiTarget.fileName,
          });
        });
      }

      if (aiDownloadBtn) {
        aiDownloadBtn.addEventListener('click', () => {
          downloadLatestImage();
          closeAiModal();
        });
      }

      if (aiRemoveBgBtn) {
        aiRemoveBgBtn.addEventListener('click', async () => {
          await removeAiImageBackground();
        });
      }

      if (aiUseImageBtn) {
        aiUseImageBtn.addEventListener('click', async () => {
          if (!aiLatestResult) {
            return;
          }

          await setGeneratedImageInInput({
            inputId: aiLatestResult.inputId,
            previewId: aiLatestResult.previewId,
            base64Data: aiLatestResult.base64Data,
            mimeType: aiLatestResult.mimeType,
            fileName: aiLatestResult.fileName,
          });

          appendAiMessage('assistant', 'Imagen aplicada al formulario. Cierro el modal para que sigas con el registro.');
          closeAiModal();
        });
      }

      if (aiPromptInput) {
        aiPromptInput.addEventListener('keydown', (event) => {
          if (event.key === 'Enter' && !event.shiftKey) {
            event.preventDefault();
            aiGenerateBtn?.click();
          }
        });
      }

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
        stateSelect.disabled = true;
        citySelect.disabled = true;

        if (!countryId) return;

        stateLoading.style.display = 'block';
        try {
          const response = await fetch(`/get-states/${countryId}`);
          if (!response.ok) throw new Error('No se pudieron cargar los estados');
          const data = await response.json();
          data.forEach(state => {
            stateSelect.insertAdjacentHTML('beforeend', `<option value="${state.id}">${state.name}</option>`);
          });
          stateSelect.disabled = false;
        } catch (error) {
          console.error(error);
        } finally {
          stateLoading.style.display = 'none';
        }
      });

      stateSelect.addEventListener('change', async function() {
        const stateId = this.value;
        citySelect.innerHTML = '<option value="">Selecciona una ciudad</option>';
        citySelect.disabled = true;

        if (!stateId) return;

        cityLoading.style.display = 'block';
        try {
          const response = await fetch(`/get-cities/${stateId}`);
          if (!response.ok) throw new Error('No se pudieron cargar las ciudades');
          const data = await response.json();
          data.forEach(city => {
            citySelect.insertAdjacentHTML('beforeend', `<option value="${city.id}">${city.name}</option>`);
          });
          citySelect.disabled = false;
        } catch (error) {
          console.error(error);
        } finally {
          cityLoading.style.display = 'none';
        }
      });

      if (businessTypeSelect) {
        const syncCreateTenantUserScheduleVisibility = () => {
          const scheduleBlock = document.getElementById('createTenantUserScheduleFields');
          if (!scheduleBlock) {
            return;
          }

          const isPhysicalStore = String(businessTypeSelect.value || '').toLowerCase() === 'tienda';
          scheduleBlock.style.display = isPhysicalStore ? 'block' : 'none';
        };

        businessTypeSelect.addEventListener('change', () => {
          refreshEconomicActivities('');
          syncCreateTenantUserScheduleVisibility();
        });

        syncCreateTenantUserScheduleVisibility();
      }

      if (economicActivitySelect) {
        economicActivitySelect.addEventListener('change', () => refreshEconomicActivities(economicActivitySelect.value));
        refreshEconomicActivities(economicActivitySelect.dataset.selected || '');
      }

      tenantForm?.addEventListener('submit', function (event) {
        syncOwnerPhoneValue();

        const invalidHexInput = hexInputs.find((input) => !/^#(?:[A-F0-9]{6}|[A-F0-9]{3})$/i.test(String(input.value || '').trim()));
        if (invalidHexInput) {
          event.preventDefault();
          invalidHexInput.classList.add('is-invalid');
          invalidHexInput.focus();
          showTenantToast('Ingresa colores HEX válidos, por ejemplo #0D6EFD.', 'warning');
          return;
        }

        const ownerPhoneValue = normalizeTenantNumericValue(ownerPhoneInput?.value || '');
        const tenantPhoneValue = normalizeTenantNumericValue(phoneNumberInput?.value || '');
        const ownerDniValue = normalizeTenantNumericValue(ownerDniInput?.value || '');

        if (!tenantPhoneValue || !ownerPhoneValue || !ownerDniValue) {
          event.preventDefault();
          showTenantToast('DNI y teléfonos deben contener solo números.', 'warning');
          return;
        }

        if (!termsCheckbox?.checked) {
          event.preventDefault();
          showTenantToast('Debes aceptar los términos y condiciones para crear la tienda.', 'warning');
          termsCheckbox?.focus();
          return;
        }

        const submitBtn = this.querySelector('button[type="submit"]');
        if (submitBtn?.dataset.loading === '1') {
          event.preventDefault();
          return;
        }

        setTenantSubmitLoading(submitBtn, true, 'Creando tienda...');
      });
    });
  </script>
</body>
</html>
