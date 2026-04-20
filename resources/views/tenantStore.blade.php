@extends('layouts.app')

@section('title', 'Tienda')

@section('content')
<style>
    .text-black-all * {
        color: #000 !important;
    }

    /* Transición para el panel del iframe */
    #iframeContainer {
        transition: all 0.3s ease-in-out;
    }
    
    .form-control-color {
    width: 3rem;
    height: unset;
    padding: 0.5rem;
    }

    .logo-preview {
      max-height: 90px;
      border: 1px solid #dee2e6;
    }
    .no-border {
    border: none !important;
    padding: 0;
    background: transparent;
}

/* Chrome, Edge, Safari */
.no-border::-webkit-color-swatch-wrapper {
    padding: 0;
}

.no-border::-webkit-color-swatch {
    border: none;
    border-radius: 6px; /* opcional */
}

/* Firefox */
.no-border::-moz-color-swatch {
    border: none;
    border-radius: 6px; /* opcional */
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
    background: #212529;
    border-radius: 50%;
    animation: aiPulse 0.9s infinite ease-in-out;
}

.ai-loading-dots span:nth-child(2) {
    animation-delay: 0.15s;
}

.ai-loading-dots span:nth-child(3) {
    animation-delay: 0.3s;
}

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

@keyframes aiPulse {
    0%, 100% { opacity: 0.3; transform: translateY(0); }
    50% { opacity: 1; transform: translateY(-3px); }
}

.store-role-card {
    border: 1px solid #dee2e6;
    border-radius: .75rem;
    background: #fff;
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
</style>

@php
    $authUser = auth()->user();
    $canAssignStoreRoles = ($authUser?->canAssignStoreRoles() ?? false) && !($isFreePlanTenant ?? false);
    $isOwnerRole = $authUser?->isOwner() ?? false;
    $tenantStoreUrl = $tenant->full_url ?? (url('/').'/'.$tenant->slug);
    $tenantBusinessType = \Illuminate\Support\Str::lower((string) ($tenant->business_type ?? 'tienda'));
    $currentPlanName = $currentPlanPayment?->plan?->name ?? 'Sin plan activo';
    $currentPlanAmount = $currentPlanPayment?->amount;
    $currentCutoffDate = optional($currentPlanCutoffDate)->format('d/m/Y H:i') ?? 'Sin fecha de corte';
    $currentPlanDaysRemainingLabel = is_null($currentPlanDaysRemaining)
        ? 'Sin vigencia registrada'
        : ($currentPlanDaysRemaining < 0
            ? 'Vencido hace '.abs((int) $currentPlanDaysRemaining).' días'
            : ($currentPlanDaysRemaining === 0
                ? 'Vence hoy'
                : 'Faltan '.$currentPlanDaysRemaining.' días'));
@endphp

<div class="p-4 ">
    <div id="shopixToastContainer" class="shopix-toast-stack"></div>
    <h1 class="">Gestión de Tienda</h1>

    @if(!is_null($currentPlanDaysRemaining))
        @if($currentPlanDaysRemaining < 0)
            <div class="alert alert-danger border" role="alert">
                Tu plan está vencido ({{ $currentPlanDaysRemainingLabel }}). Registra y envía tu pago en la pestaña <strong>Plan y Pagos</strong> para reactivar el servicio.
            </div>
        @elseif($currentPlanDaysRemaining <= 7)
            <div class="alert alert-warning border" role="alert">
                Tu plan está próximo a vencer ({{ $currentPlanDaysRemainingLabel }}). Puedes cargar el pago desde <strong>Plan y Pagos</strong>.
            </div>
        @endif
    @endif

    @if($pendingPlanPayment)
        <div class="alert alert-info border" role="alert">
            Tienes una solicitud de pago pendiente de aprobación para el plan <strong>{{ $pendingPlanPayment->plan->name ?? 'N/A' }}</strong>.
        </div>
    @endif

    @if($isBasicPlanTenant ?? false)
        <div class="alert alert-warning border" role="alert">
            <strong>Limitaciones del plan Básico:</strong>
            <ul class="mb-0 mt-2 ps-3">
                <li>Solo puedes tener un usuario administrador.</li>
                <li>No puedes crear almacenes adicionales (solo el almacén central).</li>
                <li>No puedes crear listas de materiales.</li>
                <li>No tienes acceso al módulo de reportes.</li>
            </ul>
            <div class="mt-3">
                <button type="button" class="btn btn-dark btn-sm mb-0" id="goToPlanPaymentsBtn">
                    Subir de plan
                </button>
            </div>
        </div>
    @endif

    <div class="row">
        {{-- Columna izquierda --}}
        <div class="col-md-6" id="leftColumn">
            <div class="card h-100">
                <div class="card-body">

                    {{-- Tabs --}}
                    <ul class="nav nav-tabs mb-3 text-black-all" id="tenantTabs" role="tablist">
                        <li class="nav-item" role="presentation">
                            <button class="nav-link active" id="info-tab" data-bs-toggle="tab" data-bs-target="#info" type="button" role="tab">
                                Info de la Empresa
                            </button>
                        </li>

                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="address-tab" data-bs-toggle="tab" data-bs-target="#addressTab" type="button" role="tab">
                                Dirección
                            </button>
                        </li>

                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="design-tab" data-bs-toggle="tab" data-bs-target="#design" type="button" role="tab">
                                Identidad
                            </button>
                        </li>

                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="plan-tab" data-bs-toggle="tab" data-bs-target="#plan" type="button" role="tab">
                                Plan y Pagos
                            </button>
                        </li>

                        {{-- NUEVO: Usuarios de la tienda --}}
                        @if(!($isFreePlanTenant ?? false))
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="users-tab" data-bs-toggle="tab" data-bs-target="#users" type="button" role="tab">
                                    Usuarios
                                </button>
                            </li>
                        @endif
                    </ul>

                    {{-- Formulario principal --}}
                    <form id="tenantForm" enctype="multipart/form-data" novalidate>
                        @csrf

                        <div class="tab-content" id="tenantTabsContent">

                            {{-- TAB 1 --}}
                            <div class="tab-pane fade show active" id="info" role="tabpanel">
                                <div class="mb-3">
                                    <label class="form-label">Nombre de la Tienda</label>
                                    <input type="text" class="form-control p-2 border border-radius-lg" name="name" value="{{ $tenant->name ?? '' }}">
                                </div>

                                <div class="row mb-3">
                                    <div class="col-md-4">
                                        <label for="business_type" class="form-label fw-bold">Tipo de negocio</label>
                                        <select name="business_type" id="business_type" class="form-select form-select-lg" required>
                                            <option value="">Selecciona una opción</option>
                                            <option value="tienda" {{ $tenantBusinessType === 'tienda' ? 'selected' : '' }}>Tienda</option>
                                            <option value="servicio" {{ $tenantBusinessType === 'servicio' ? 'selected' : '' }}>Servicio</option>
                                        </select>
                                    </div>
                                    <div class="col-md-8">
                                        <label for="economic_activity" class="form-label fw-bold">Rubro económico</label>
                                        <select name="economic_activity" id="economic_activity" class="form-select form-select-lg border border-radius-lg" data-selected="{{ $tenant->economic_activity ?? '' }}" required>
                                            <option value="">Selecciona un rubro</option>
                                        </select>
                                        <small id="economic_activity_help" class="text-muted d-block mt-1"></small>
                                    </div>
                                </div>

                                <div class="row mb-4">
                                    <div class="col-md-4">
                                        <label for="phone_code" class="form-label fw-bold">Código del país</label>
                                        <select name="phone_code" id="phone_code" class="form-select form-select-lg">
                                        <option value="+58" {{ ($tenant->phone_code ?? '') == '+58' ? 'selected' : '' }}>🇻🇪 +58</option>
                                        <option value="+1" {{ ($tenant->phone_code ?? '') == '+1' ? 'selected' : '' }}>🇺🇸 +1</option>
                                        <option value="+34" {{ ($tenant->phone_code ?? '') == '+34' ? 'selected' : '' }}>🇪🇸 +34</option>
                                        <option value="+57" {{ ($tenant->phone_code ?? '') == '+57' ? 'selected' : '' }}>🇨🇴 +57</option>
                                        <option value="+55" {{ ($tenant->phone_code ?? '') == '+55' ? 'selected' : '' }}>🇧🇷 +55</option>
                                        <option value="+52" {{ ($tenant->phone_code ?? '') == '+52' ? 'selected' : '' }}>🇲🇽 +52</option>
                                        </select>
                                    </div>
                                    <div class="col-md-8">
                                        <label for="phone_number" class="form-label fw-bold">Número de teléfono</label>
                                        <input type="text" name="phone_number" id="phone_number" class="form-control form-control-lg border border-radius-lg" placeholder="Ej: 4121234567" value="{{ $tenant->phone_number ?? '' }}">
                                    </div>
                                </div>

                                @php
                                    $workingDays = collect($tenant->working_days ?? [])->map(fn ($day) => strtolower((string) $day))->all();
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

                                <div class="mb-4" id="physicalStoreScheduleFields" style="display: {{ $tenantBusinessType === 'tienda' ? 'block' : 'none' }};">
                                    <label class="form-label fw-bold d-block">Días laborales y horario (tienda física)</label>
                                    <div class="row g-2 mb-3">
                                        @foreach($weekDays as $dayKey => $dayLabel)
                                            <div class="col-6 col-md-3">
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="working_days[]" id="working_day_{{ $dayKey }}" value="{{ $dayKey }}" {{ in_array($dayKey, $workingDays, true) ? 'checked' : '' }}>
                                                    <label class="form-check-label" for="working_day_{{ $dayKey }}">{{ $dayLabel }}</label>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-12 col-md-6">
                                            <label for="opening_time" class="form-label">Hora de apertura</label>
                                            <input type="time" class="form-control border border-1 p-2" id="opening_time" name="opening_time" value="{{ !empty($tenant->opening_time) ? \Illuminate\Support\Str::substr((string) $tenant->opening_time, 0, 5) : '' }}">
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <label for="closing_time" class="form-label">Hora de cierre</label>
                                            <input type="time" class="form-control border border-1 p-2" id="closing_time" name="closing_time" value="{{ !empty($tenant->closing_time) ? \Illuminate\Support\Str::substr((string) $tenant->closing_time, 0, 5) : '' }}">
                                        </div>
                                    </div>
                                    <small class="text-muted d-block mt-2">Estos campos son opcionales y solo se muestran en la landing si tienen datos.</small>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Url de la Tienda</label>
                                    <div class="input-group mt-2">
                                        <input
                                            type="text"
                                            class="form-control p-2 border border-radius-lg"
                                            id="storePublicUrlInput"
                                            value="{{ $tenantStoreUrl }}"
                                            readonly>
                                        <a
                                            href="{{ $tenantStoreUrl }}"
                                            target="_blank"
                                            rel="noopener"
                                            class="btn btn-outline-dark url-icon-action-btn"
                                            id="openStoreUrlBtn"
                                            aria-label="Abrir tienda"
                                            title="Abrir tienda">
                                            <i class="material-symbols-rounded">open_in_new</i>
                                        </a>
                                        <button
                                            type="button"
                                            class="btn btn-outline-secondary url-icon-action-btn"
                                            id="copyStoreUrlBtn"
                                            aria-label="Copiar enlace"
                                            title="Copiar enlace"
                                            data-icon-default="content_copy">
                                            <i class="material-symbols-rounded">content_copy</i>
                                        </button>
                                    </div>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Eslogan</label>
                                    <input type="text" class="form-control p-2 border border-radius-lg" name="slogan" value="{{ $tenant->slogan ?? '' }}">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Descripción</label>
                                    <textarea class="form-control p-2 border border-radius-lg" name="description" rows="3">{{ $tenant->description ?? '' }}</textarea>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold d-block">Contribuyente especial</label>
                                    <input type="hidden" name="special_taxpayer" value="0">
                                    <div class="form-check form-switch">
                                        <input
                                            class="form-check-input"
                                            type="checkbox"
                                            role="switch"
                                            id="special_taxpayer"
                                            name="special_taxpayer"
                                            value="1"
                                            {{ (bool) ($tenant->special_taxpayer ?? false) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="special_taxpayer">
                                            La tienda es contribuyente especial
                                        </label>
                                    </div>
                                    <small class="text-muted">Si está activo, el sistema no aplicará IGTF en los cobros.</small>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold d-block">Habilitación de imprenta para cambio de alícuotas</label>
                                    <input type="hidden" name="printer_tax_change_enabled" value="0">
                                    <div class="form-check form-switch">
                                        <input
                                            class="form-check-input"
                                            type="checkbox"
                                            role="switch"
                                            id="printer_tax_change_enabled"
                                            name="printer_tax_change_enabled"
                                            value="1"
                                            {{ (bool) ($tenant->printer_tax_change_enabled ?? false) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="printer_tax_change_enabled">
                                            Permitir cambios de alícuotas en productos existentes
                                        </label>
                                    </div>
                                    <small class="text-muted">Úsalo solo cuando la imprenta autorice el cambio fiscal.</small>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label">Referencia de habilitación de imprenta</label>
                                    <input type="text" class="form-control p-2 border border-radius-lg" name="printer_tax_change_reference" value="{{ $tenant->printer_tax_change_reference ?? '' }}" placeholder="Providencia, ticket o referencia de aprobación">
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold d-block">Restricción de envíos por ciudad</label>
                                    <input type="hidden" name="restrict_delivery_city_to_tenant" value="0">
                                    <div class="form-check form-switch">
                                        <input
                                            class="form-check-input"
                                            type="checkbox"
                                            role="switch"
                                            id="restrict_delivery_city_to_tenant"
                                            name="restrict_delivery_city_to_tenant"
                                            value="1"
                                            {{ (bool) ($tenant->restrict_delivery_city_to_tenant ?? true) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="restrict_delivery_city_to_tenant">
                                            Permitir envíos solo en la ciudad de la tienda
                                        </label>
                                    </div>
                                    <small class="text-muted">Si se desactiva, la tienda puede registrar envíos a cualquier ciudad.</small>
                                </div>
                            </div>

                            {{-- TAB 2 --}}
                            <div class="tab-pane fade" id="addressTab" role="tabpanel">
                                <div class="mb-3">
                                    <div class="row mb-3">
                                        <div class="col-md-4">
                                            <label for="country" class="form-label ">País</label>
                                            <select name="country" id="country" class="form-control form-control-lg border border-radius-lg p-2">
                                                <option value="">Selecciona un país</option>
                                                @foreach($countries as $country)
                                                    <option value="{{ $country->id }}" {{ isset($tenant->country) && $tenant->country == $country->id ? 'selected' : '' }}>
                                                        {{ $country->name }}
                                                    </option>
                                                @endforeach
                                            </select>
                                        </div>

                                        <div class="col-md-4">
                                            <label for="state" class="form-label fw-bold">Estado / Provincia</label>
                                            <select name="state" id="state" class="form-control form-control-lg border border-radius-lg p-2">
                                                <option value="">Selecciona un estado</option>
                                                @if(isset($tenant->state))
                                                    @foreach($states->where('country_id', $tenant->country) as $state)
                                                        <option value="{{ $state->id }}" {{ $tenant->state == $state->id ? 'selected' : '' }}>
                                                            {{ $state->name }}
                                                        </option>
                                                    @endforeach
                                                @endif
                                            </select>
                                            <div id="state-loading" style="display:none;">Cargando estados...</div>
                                        </div>

                                        <div class="col-md-4">
                                            <label for="city" class="form-label fw-bold">Ciudad</label>
                                            <select name="city" id="city" class="form-control form-control-lg border border-radius-lg p-2">
                                                <option value="">Selecciona una ciudad</option>
                                                @if(isset($tenant->city))
                                                    @foreach($cities->where('state_id', $tenant->state) as $city)
                                                        <option value="{{ $city->id }}" {{ $tenant->city == $city->id ? 'selected' : '' }}>
                                                            {{ $city->name }}
                                                        </option>
                                                    @endforeach
                                                @endif
                                            </select>
                                            <div id="city-loading" style="display:none;">Cargando ciudades...</div>
                                        </div>
                                    </div>

                                    <label class="form-label">Dirección Exacta</label>
                                    <input type="text" id="address" class="form-control p-2 border border-radius-lg" name="address" value="{{ $tenant->address ?? '' }}">
                                    <!-- 🗺️ MAPA GOOGLE -->
                                    <div class="mb-4">
                                        <label class="form-label fw-bold">Ubicación en el mapa</label>
                                        <div id="map" style="height: 350px; border-radius: 0.5rem;"></div>

                                        <!-- Campos ocultos para latitud y longitud -->
                                        <input type="hidden" name="latitude" id="latitude" value="{{ $tenant->latitude ?? '' }}">
                                        <input type="hidden" name="longitude" id="longitude" value="{{ $tenant->longitude ?? '' }}">
                                    </div>
                                </div>
                            </div>

                            {{-- TAB 3 --}}
                            <div class="tab-pane fade" id="design" role="tabpanel">
                                <div class="mb-4">
                                    <div class="d-flex align-items-center gap-3 flex-wrap">
                                        <img id="logo-preview" src="{{ \App\Support\ImageStorage::url($tenant->logo) ?? asset('assets/img/shopix5.png') }}" class="logo-preview rounded p-2 bg-white shadow-sm">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Cambiar Logo (PNG, JPG, JPEG o SVG)</label>
                                    <input type="file" name="logo" id="logo" class="form-control form-control-lg border border-radius-lg" accept=".png,.jpg,.jpeg,.webp,.svg">
                                </div>
                                <div class="mb-3">
                                    <button type="button" class="btn btn-outline-dark w-100" id="openLogoAiModalBtn">
                                        <span class="ai-spark">🤖 IA Gemini</span> para generar logo
                                    </button>
                                </div>
                                <div class="mb-4">
                                    <div class="d-flex align-items-center gap-3 flex-wrap">
                                        <img id="bg-preview" src="{{ \App\Support\ImageStorage::url($tenant->background_image) ?? asset('assets/img/shopix5.png') }}" class="logo-preview rounded p-2 bg-white shadow-sm">
                                    </div>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Imagen de fondo (PNG, JPG, JPEG o SVG) (1920x1080)</label>
                                    <input type="file" name="background_image" id="background_image" class="form-control form-control-lg border border-radius-lg" accept=".png,.jpg,.jpeg,.webp,.svg">
                                </div>
                                <div class="mb-3">
                                    <button type="button" class="btn btn-outline-dark w-100" id="openBackgroundAiModalBtn">
                                        <span class="ai-spark">🤖 IA Gemini</span> para generar imagen de fondo
                                    </button>
                                </div>
                                <div class="row mb-4">
                                    <div class="col-md-4">
                                        <label for="color_primary" class="form-label fw-bold">Color Primario</label>
                                        <input type="color"
                                        name="color_primary"
                                        id="color_primary"
                                        class="form-control-color w-100 bg-transparent no-border"
                                        style="height: 45px;"
                                        value="{{ $tenant->color_primary ?? '#0d6efd' }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="color_secondary" class="form-label fw-bold">Color Secundario</label>
                                        <input type="color"
                                        name="color_secondary"
                                        id="color_secondary"
                                        style="height: 45px;"
                                        class="form-control-color w-100 bg-transparent no-border"
                                        value="{{ $tenant->color_secondary ?? '#6c757d' }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="color_accent" class="form-label fw-bold">Color Acento (letras y detalles)</label>
                                        <input type="color"
                                            name="color_accent"
                                            id="color_accent"
                                            style="height: 45px;"
                                            class="form-control-color w-100 bg-transparent no-border"
                                            value="{{ $tenant->color_accent ?? '#ffc107' }}">
                                    </div>
                                </div>
                                <div class="row mb-4">
                                    <div class="col-md-4">
                                        <label for="tiktok" class="form-label fw-bold">TikTok</label>
                                        <input type="text"
                                        name="tiktok"
                                        id="tiktok"
                                        class="form-control p-2 border border-radius-lg"
                                        value="{{ $tenant->tiktok ?? '' }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="instagram" class="form-label fw-bold">Instagram</label>
                                        <input type="text"
                                        name="instagram"
                                        id="instagram"
                                        class="form-control p-2 border border-radius-lg"
                                        value="{{ $tenant->instagram ?? '' }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label for="facebook" class="form-label fw-bold">Facebook</label>
                                        <input type="text"
                                        name="facebook"
                                        id="facebook"
                                        class="form-control p-2 border border-radius-lg"
                                        value="{{ $tenant->facebook ?? '' }}">
                                    </div>
                                </div>
                            </div>

                            {{-- TAB 4: Plan y pagos --}}
                            <div class="tab-pane fade" id="plan" role="tabpanel">
                                <h5 class="mt-2">Plan actual y fecha de corte</h5>
                                <div class="alert alert-light border mb-3">
                                    <p class="mb-1"><strong>Plan actual:</strong> {{ $currentPlanName }}</p>
                                    <p class="mb-1"><strong>Monto:</strong> {{ is_null($currentPlanAmount) ? 'N/A' : '$'.number_format((float) $currentPlanAmount, 2) }}</p>
                                    <p class="mb-0"><strong>Fecha de corte:</strong> {{ $currentCutoffDate }}</p>
                                </div>

                                @if($pendingPlanPayment)
                                    <div class="alert alert-warning border mb-3">
                                        Tienes una solicitud pendiente para el plan <strong>{{ $pendingPlanPayment->plan->name ?? 'N/A' }}</strong>.
                                        Estado: pendiente de aprobación administrativa.
                                    </div>
                                @endif

                                <h6 class="mb-3">Solicitar cambio/renovación de plan</h6>
                                <div class="mb-3">
                                    <label for="plan_request_plan_id" class="form-label">Plan a solicitar</label>
                                    <select id="plan_request_plan_id" class="form-control form-control-lg border border-radius-lg p-2">
                                        <option value="">Selecciona un plan</option>
                                        @foreach($plans as $plan)
                                            <option value="{{ $plan->id }}" data-price="{{ (float) ($plan->price ?? 0) }}">
                                                {{ $plan->name }} - ${{ number_format((float) ($plan->price ?? 0), 2) }} - {{ (int) ($plan->duration_days ?? 0) }} días
                                            </option>
                                        @endforeach
                                    </select>
                                    <small class="text-muted d-block mt-1">Si el plan no es gratuito, debes cargar referencia y comprobante.</small>
                                </div>

                                <div class="mb-3">
                                    <label for="plan_request_reference" class="form-label">Referencia de pago</label>
                                    <input type="text" id="plan_request_reference" class="form-control p-2 border border-radius-lg" placeholder="Ej: TRX-123456789">
                                </div>

                                <div class="mb-3">
                                    <label for="plan_request_proof" class="form-label">Comprobante de pago</label>
                                    <input type="file" id="plan_request_proof" class="form-control form-control-lg border border-radius-lg" accept=".png,.jpg,.jpeg,.webp">
                                </div>

                                <div class="mb-3">
                                    <label for="plan_request_notes" class="form-label">Notas (opcional)</label>
                                    <textarea id="plan_request_notes" rows="3" class="form-control p-2 border border-radius-lg" placeholder="Comentario para administración"></textarea>
                                </div>

                                <button
                                    type="button"
                                    id="submitPlanPaymentBtn"
                                    class="btn btn-sm btn-dark text-white"
                                    @if($pendingPlanPayment) disabled @endif>
                                    Enviar solicitud de pago
                                </button>
                            </div>

                            {{-- TAB 5: Usuarios --}}
                            @if(!($isFreePlanTenant ?? false))
                            <div class="tab-pane fade" id="users" role="tabpanel">
                                <h5 class="mt-2">Usuarios de la tienda</h5>
                                <div class="accordion mb-4" id="rolesAccordion">
                                    @foreach(($roleDefinitions ?? []) as $roleKey => $roleDefinition)
                                        <div class="accordion-item border rounded-3 mb-2 overflow-hidden">
                                            <h2 class="accordion-header" id="heading-{{ $roleKey }}">
                                                <button class="accordion-button collapsed fw-semibold" type="button" data-bs-toggle="collapse" data-bs-target="#collapse-{{ $roleKey }}" aria-expanded="false" aria-controls="collapse-{{ $roleKey }}">
                                                    <span>{{ $roleDefinition['name'] }}</span>
                                                    <span class="badge bg-dark text-white ms-2">{{ strtoupper($roleKey) }}</span>
                                                </button>
                                            </h2>
                                            <div id="collapse-{{ $roleKey }}" class="accordion-collapse collapse" aria-labelledby="heading-{{ $roleKey }}" data-bs-parent="#rolesAccordion">
                                                <div class="accordion-body">
                                                    <p class="text-sm text-muted mb-2">{{ $roleDefinition['description'] }}</p>
                                                    <ul class="text-sm mb-0 ps-3">
                                                        @foreach(($roleDefinition['permissions'] ?? []) as $permission)
                                                            <li>{{ $permission }}</li>
                                                        @endforeach
                                                    </ul>
                                                </div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>

                                @if($canAssignStoreRoles)
                                    <div class="alert alert-info border mb-4">
                                        @if($isOwnerRole)
                                            Como owner puedes crear usuarios y asignar roles de admin, vendedor y almacenista.
                                        @else
                                            Como admin puedes crear usuarios operativos y asignar roles de vendedor y almacenista. La asignacion de admin queda reservada al owner.
                                        @endif
                                    </div>
                                    @if($isBasicPlanTenant ?? false)
                                        <div class="alert alert-warning border mb-4">
                                            En plan Básico solo puedes tener un owner y un admin. Solo se permite crear un usuario admin si aún no existe.
                                        </div>
                                    @endif
                                @else
                                    <div class="alert alert-warning border mb-4">
                                        Tu rol no tiene permisos para crear usuarios ni asignar roles desde esta pantalla.
                                    </div>
                                @endif

                                <ul class="list-group mb-4">
                                    @forelse($tenant->users as $user)
                                        <li class="list-group-item d-flex justify-content-between align-items-center">
                                            <div>
                                                <strong>{{ $user->name }}</strong>
                                                <small class="d-block text-muted">{{ $user->email }}</small>
                                                <small class="d-block text-muted">{{ ($roleDefinitions[\App\Models\User::canonicalRoleName(optional($user->role)->name)]['description'] ?? 'Usuario operativo de la tienda.') }}</small>
                                            </div>
                                            <span class="badge bg-dark text-white">{{ \App\Models\User::displayRoleName(optional($user->role)->name) }}</span>
                                        </li>
                                    @empty
                                        <li class="list-group-item text-center text-muted">No hay usuarios registrados.</li>
                                    @endforelse
                                </ul>

                                @if($canAssignStoreRoles)
                                    <h6 class="mb-3">Agregar nuevo usuario</h6>
                                    <div class="row">
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Nombre</label>
                                            <input type="text" name="new_user[name]" class="form-control p-2 border border-radius-lg">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Correo</label>
                                            <input type="email" name="new_user[email]" class="form-control p-2 border border-radius-lg">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Teléfono</label>
                                            <input type="text" name="new_user[phone_number]" class="form-control p-2 border border-radius-lg">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">DNI</label>
                                            <input type="text" name="new_user[dni]" class="form-control p-2 border border-radius-lg">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Contraseña</label>
                                            <input type="password" name="new_user[password]" class="form-control p-2 border border-radius-lg" autocomplete="new-password">
                                        </div>
                                        <div class="col-md-6 mb-3">
                                            <label class="form-label">Rol</label>
                                            <select name="new_user[role_id]" class="form-control form-control-lg border border-radius-lg p-2">
                                                <option value="">Selecciona un rol</option>
                                                @foreach($roles as $role)
                                                    <option value="{{ $role->id }}">{{ \App\Models\User::displayRoleName($role->name) }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                    </div>
                                @endif
                            </div>
                            @endif
                        </div>

                        <button type="submit" class="btn btn-sm btn-dark text-white w-100 mt-3">Guardar Cambios</button>
                    </form>

                </div>
            </div>
        </div>

        {{-- Columna derecha (iframe) --}}
        <div class="col-md-6" id="iframeContainer">
            <div class="card h-100">

                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Vista previa</h5>

                    {{-- Botón para minimizar --}}
                    <button id="toggleIframe" class="btn btn-outline-dark btn-sm">
                        Minimizar
                    </button>
                </div>

                <div class="card-body p-0" id="iframeContent" style="height: 600px;">
                    <iframe
                        id="previewFrame"
                        src="{{ $tenant->full_url ?? (url('/').'/'.$tenant->slug) }}"
                        style="width: 100%; height: 100%; border: none;">
                    </iframe>
                </div>

            </div>
        </div>

    </div>
</div>
<!-- MODAL EDITAR USUARIO -->
<div class="modal fade" id="editUserModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">

            <div class="modal-header">
                <h5 class="modal-title">Editar Usuario</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>

            <form id="editUserForm">
                @csrf
                <input type="hidden" name="user_id" id="edit_user_id">

                <div class="modal-body">

                    <!-- Nombre -->
                    <div class="mb-3">
                        <label class="form-label">Nombre</label>
                        <input type="text" name="name" id="edit_user_name" class="form-control" required>
                    </div>

                    <!-- Correo -->
                    <div class="mb-3">
                        <label class="form-label">Correo</label>
                        <input type="email" name="email" id="edit_user_email" class="form-control" required>
                    </div>

                    <!-- Contraseña -->
                    <div class="mb-3">
                        <label class="form-label">
                            Contraseña <small class="text-muted">(dejar vacío para no cambiar)</small>
                        </label>
                        <input type="password" name="password" id="edit_user_password" class="form-control">
                    </div>

                    <!-- Rol -->
                    <div class="mb-3">
                        <label class="form-label">Rol</label>
                        <select name="role" id="edit_user_role" class="form-select" required>
                            <option value="">Seleccione un rol</option>
                            @foreach($roles as $role)
                                <option value="{{ $role->name }}">{{ \App\Models\User::displayRoleName($role->name) }}</option>
                            @endforeach
                        </select>
                    </div>

                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>

            </form>

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
                <div class="d-flex gap-3 align-items-center mb-2">
                    <div class="form-check m-0">
                        <input class="" type="checkbox" id="aiUseStoreColors" checked>
                        <label class="form-check-label" for="aiUseStoreColors">Usar colores de la tienda</label>
                    </div>
                    <div class="form-check m-0">
                        <input class="" type="checkbox" id="aiUseBackgroundRatio" checked>
                        <label class="form-check-label" for="aiUseBackgroundRatio">Usar proporción del fondo</label>
                    </div>
                </div>
                <div id="aiChatMessages" class="ai-chat-box mb-3"></div>
                <div id="aiGeneratingStatus" class="mt-3 d-none">
                    <div class="d-flex align-items-center">
                        <div class="spinner-border spinner-border-sm me-2 text-dark" role="status"></div>
                        <span>Generando imagen</span>
                        <span class="ai-loading-dots"><span></span><span></span><span></span></span>
                    </div>
                    <small class="text-muted d-block mt-2">Puedes seguir pidiendo ajustes hasta que te guste el resultado.</small>
                </div>
                <div class="mt-3">
                    <input type="file" id="aiReferenceImage" class="d-none" accept=".png,.jpg,.jpeg,.webp">
                    <div class="d-flex gap-2 align-items-end">
                        <button type="button" class="btn ai-attach-btn" id="aiAttachBtn" title="Adjuntar imagen">📎</button>
                        <textarea id="aiPromptInput" class="form-control border border-radius-lg p-2" rows="2" placeholder="Escribe tu mensaje para la IA..."></textarea>
                        <button type="button" class="btn btn-dark" id="aiGenerateBtn" title="Enviar mensaje">➤</button>
                    </div>
                    <small class="text-muted d-block mt-1" id="aiAttachedName"></small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="aiCancelBtn">Cancelar</button>
                <button type="button" class="btn btn-outline-dark" id="aiDownloadBtn" disabled>Descargar</button>
                <button type="button" class="btn btn-outline-success" id="aiUseImageBtn" disabled>Usar esta imagen</button>
            </div>
        </div>
    </div>
</div>

</div>

@endsection


@push('scripts')
    <script async
    src="https://maps.googleapis.com/maps/api/js?key=AIzaSyA5zzN0-ht0NYbOOUeCRP2RRJyWrEDZsRI&libraries=places&callback=initMap">
    </script>
<script>
  let map, marker;
    const tenantAiImageEndpoint = "{{ route('tenant.ai-image') }}";
    const TENANT_SAFE_IMAGE_BYTES = 1200 * 1024;
    const TENANT_IMAGE_MAX_DIMENSION = 2200;
    let aiModalInstance = null;
    let currentAiTarget = null;
        let aiChatHistory = [];
        let aiLatestResult = null;

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

    function formatTenantSize(bytes) {
        return `${(Number(bytes || 0) / (1024 * 1024)).toFixed(2)} MB`;
    }

    function setTenantSubmitLoading(button, isLoading, loadingText = 'Guardando...') {
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
        bubble.className = role === 'assistant' ? 'd-inline-block p-2 rounded bg-white border' : 'd-inline-block p-2 rounded text-white bg-dark';
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
        status.classList.toggle('d-none', !isLoading);
        generateBtn.disabled = isLoading;
        cancelBtn.disabled = isLoading;
        if (attachBtn) {
            attachBtn.disabled = isLoading;
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

        if (target.type === 'logo') {
            title.textContent = 'Generar logo con IA';
            question.textContent = '';
            prompt.placeholder = 'Ej: logo minimalista deportivo en azul y dorado, sin texto';
        } else {
            title.textContent = 'Generar imagen de fondo con IA';
            question.textContent = 'Chatea con Gemini y ajusta la imagen de fondo por iteraciones.';
            prompt.placeholder = 'Ej: fondo ecommerce moderno 1920x1080 con tonos oscuros y luces suaves';
        }

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
            const useStoreColors = document.getElementById('aiUseStoreColors')?.checked;
            const useBackgroundRatio = document.getElementById('aiUseBackgroundRatio')?.checked;
            const colorPrimary = document.getElementById('color_primary')?.value || null;
            const colorSecondary = document.getElementById('color_secondary')?.value || null;
            const colorAccent = document.getElementById('color_accent')?.value || null;
            const ratioImage = document.getElementById(currentAiTarget?.backgroundPreviewId || 'bg-preview');
            let backgroundRatio = null;
            if (useBackgroundRatio && ratioImage && ratioImage.naturalWidth && ratioImage.naturalHeight) {
                const ratio = ratioImage.naturalWidth / ratioImage.naturalHeight;
                backgroundRatio = ratio.toFixed(3);
            }

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
                    background_ratio: backgroundRatio,
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

function initMap() {
    const latitudeField = document.getElementById("latitude");
    const longitudeField = document.getElementById("longitude");
    const savedLat = parseFloat(latitudeField.value);
    const savedLng = parseFloat(longitudeField.value);
    const hasSavedPosition = !Number.isNaN(savedLat) && !Number.isNaN(savedLng);
    const defaultPos = hasSavedPosition ? { lat: savedLat, lng: savedLng } : { lat: 9.7457, lng: -63.1832 }; // Maturín, Monagas, Venezuela por defecto

  map = new google.maps.Map(document.getElementById("map"), {
    center: defaultPos,
    zoom: 13,
  });

  marker = new google.maps.Marker({
    position: defaultPos,
    map: map,
    draggable: true,
  });

    latitudeField.value = defaultPos.lat;
    longitudeField.value = defaultPos.lng;

  // Actualizar campos ocultos cuando se mueva el marcador
  google.maps.event.addListener(marker, "dragend", function(event) {
        latitudeField.value = event.latLng.lat();
        longitudeField.value = event.latLng.lng();
    });

    // Permitir fijar punto haciendo clic en el mapa
    map.addListener("click", function(event) {
        marker.setPosition(event.latLng);
        latitudeField.value = event.latLng.lat();
        longitudeField.value = event.latLng.lng();
  });

  // Buscar dirección con el input de texto
  const input = document.getElementById("address");
    if (input) {
        const autocomplete = new google.maps.places.Autocomplete(input);
        autocomplete.bindTo("bounds", map);

        autocomplete.addListener("place_changed", function() {
            const place = autocomplete.getPlace();
            if (!place.geometry) return;
            map.setCenter(place.geometry.location);
            map.setZoom(15);
            marker.setPosition(place.geometry.location);
            latitudeField.value = place.geometry.location.lat();
            longitudeField.value = place.geometry.location.lng();
        });
    }
}
    const logoInput = document.getElementById("logo");
    const logoPreview = document.getElementById("logo-preview");
    const backgroundInput = document.getElementById("background_image");
    const backgroundPreview = document.getElementById("bg-preview");
    const openLogoAiModalBtn = document.getElementById('openLogoAiModalBtn');
    const openBackgroundAiModalBtn = document.getElementById('openBackgroundAiModalBtn');
    const storeSlugInput = document.getElementById('storeSlugInput');
    const storePublicUrlInput = document.getElementById('storePublicUrlInput');
    const openStoreUrlBtn = document.getElementById('openStoreUrlBtn');
    const copyStoreUrlBtn = document.getElementById('copyStoreUrlBtn');
    const aiGenerateBtn = document.getElementById('aiGenerateBtn');
    const aiDownloadBtn = document.getElementById('aiDownloadBtn');
    const aiUseImageBtn = document.getElementById('aiUseImageBtn');
    const aiAttachBtn = document.getElementById('aiAttachBtn');
    const aiReferenceImage = document.getElementById('aiReferenceImage');
    const aiPromptInput = document.getElementById('aiPromptInput');
    const baseStoreUrl = "{{ rtrim(url('/'), '/') }}";
    const businessTypeSelect = document.getElementById('business_type');
    const economicActivitySelect = document.getElementById('economic_activity');
    const submitPlanPaymentBtn = document.getElementById('submitPlanPaymentBtn');
    const goToPlanPaymentsBtn = document.getElementById('goToPlanPaymentsBtn');
    const planRequestPlanInput = document.getElementById('plan_request_plan_id');
    const planRequestReferenceInput = document.getElementById('plan_request_reference');
    const planRequestProofInput = document.getElementById('plan_request_proof');
    const planRequestNotesInput = document.getElementById('plan_request_notes');
    const tenantPlanPaymentRequestEndpoint = "{{ route('tenant.planPayment.request') }}";
    const tenantPlanDaysRemaining = {{ is_null($currentPlanDaysRemaining) ? 'null' : (int) $currentPlanDaysRemaining }};
    const tenantHasPendingPlanPayment = {{ $pendingPlanPayment ? 'true' : 'false' }};

    const businessCatalog = {
        tienda: [
            'Alimentos y Bebidas',
            'Moda y Accesorios',
            'Hogar y Construccion',
            'Tecnologia',
            'Salud y Belleza',
            'Otros'
        ],
        servicio: [
            'Gastronomia',
            'Cuidado Personal',
            'Servicios Tecnicos',
            'Profesionales',
            'Logistica y Educacion'
        ]
    };

    const businessExamples = {
        'Alimentos y Bebidas': 'Supermercados, Panaderias, Licorerias, Carnicerias.',
        'Moda y Accesorios': 'Ropa, Calzado, Joyeria, Opticas.',
        'Hogar y Construccion': 'Ferreterias, Mueblerias, Decoracion, Pinturerias.',
        'Tecnologia': 'Electronica, Computacion, Telefonia Movil.',
        'Salud y Belleza': 'Farmacias, Perfumerias, Cosmetica.',
        'Otros': 'Jugueterias, Librerias, Pet Shops (Mascotas).',
        'Gastronomia': 'Restaurantes, Cafeterias, Fast Food, Caterings.',
        'Cuidado Personal': 'Peluquerias, Centros de Estetica, Spas, Gimnasios.',
        'Servicios Tecnicos': 'Talleres mecanicos, Reparacion de electrodomesticos, Soporte IT.',
        'Profesionales': 'Consultorios medicos, Estudios contables/legales, Arquitectura.',
        'Logistica y Educacion': 'Mensajeria, Institutos de idiomas, Jardines de infantes.'
    };

    const refreshEconomicActivities = (selectedValue = '') => {
        if (!businessTypeSelect || !economicActivitySelect) {
            return;
        }

        const businessType = String(businessTypeSelect.value || 'tienda').toLowerCase() === 'servicio' ? 'servicio' : 'tienda';
        const options = businessCatalog[businessType] || [];
        const help = document.getElementById('economic_activity_help');

        economicActivitySelect.innerHTML = '<option value="">Selecciona un rubro</option>';
        options.forEach((option) => {
            const selected = String(option).toLowerCase() === String(selectedValue || '').toLowerCase();
            economicActivitySelect.insertAdjacentHTML('beforeend', `<option value="${option}" ${selected ? 'selected' : ''}>${option}</option>`);
        });

        const currentValue = economicActivitySelect.value;
        help.textContent = currentValue && businessExamples[currentValue]
            ? `Ejemplos: ${businessExamples[currentValue]}`
            : 'Selecciona una categoria para ver ejemplos.';
    };

    const normalizeSlug = (value) => String(value ?? '')
        .toLowerCase()
        .normalize('NFD')
        .replace(/[\u0300-\u036f]/g, '')
        .replace(/[^a-z0-9]+/g, '-')
        .replace(/-{2,}/g, '-')
        .replace(/^-+|-+$/g, '');

    const updateStorePublicUrl = () => {
        if (!storeSlugInput || !storePublicUrlInput || !openStoreUrlBtn) {
            return;
        }

        const normalizedSlug = normalizeSlug(storeSlugInput.value);
        if (storeSlugInput.value !== normalizedSlug) {
            storeSlugInput.value = normalizedSlug;
        }

        const fullUrl = normalizedSlug ? `${baseStoreUrl}/${normalizedSlug}` : baseStoreUrl;
        storePublicUrlInput.value = fullUrl;
        openStoreUrlBtn.href = fullUrl;
    };

    const copyText = async (text) => {
        if (navigator.clipboard?.writeText) {
            await navigator.clipboard.writeText(text);
            return true;
        }

        const textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.opacity = '0';
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        const copied = document.execCommand('copy');
        document.body.removeChild(textArea);

        return copied;
    };

    aiModalInstance = new bootstrap.Modal(document.getElementById('aiGenerateModal'));

    if (storeSlugInput) {
        storeSlugInput.addEventListener('input', updateStorePublicUrl);
        updateStorePublicUrl();
    }

    const syncPhysicalStoreScheduleVisibility = () => {
        const scheduleBlock = document.getElementById('physicalStoreScheduleFields');
        if (!scheduleBlock || !businessTypeSelect) {
            return;
        }

        const isPhysicalStore = String(businessTypeSelect.value || '').toLowerCase() === 'tienda';
        scheduleBlock.style.display = isPhysicalStore ? 'block' : 'none';
    };

    if (businessTypeSelect) {
        businessTypeSelect.addEventListener('change', () => {
            refreshEconomicActivities('');
            syncPhysicalStoreScheduleVisibility();
        });
    }

    if (economicActivitySelect) {
        economicActivitySelect.addEventListener('change', () => refreshEconomicActivities(economicActivitySelect.value));
        refreshEconomicActivities(economicActivitySelect.dataset.selected || '');
    }

    syncPhysicalStoreScheduleVisibility();

    if (copyStoreUrlBtn && storePublicUrlInput) {
        copyStoreUrlBtn.addEventListener('click', async () => {
            const icon = copyStoreUrlBtn.querySelector('.material-symbols-rounded');
            const defaultIcon = copyStoreUrlBtn.dataset.iconDefault || 'content_copy';
            const copied = await copyText(storePublicUrlInput.value || '');
            if (icon) {
                icon.textContent = copied ? 'check' : 'error';
            }
            setTimeout(() => {
                if (icon) {
                    icon.textContent = defaultIcon;
                }
            }, 1400);
        });
    }

    if (submitPlanPaymentBtn) {
        submitPlanPaymentBtn.addEventListener('click', async () => {
            const selectedPlanOption = planRequestPlanInput?.selectedOptions?.[0] || null;
            const selectedPlanId = planRequestPlanInput?.value || '';
            const selectedPlanPrice = Number(selectedPlanOption?.dataset?.price || 0);
            const isFreePlan = selectedPlanPrice <= 0;

            if (!selectedPlanId) {
                showTenantToast('Selecciona un plan antes de enviar la solicitud.', 'warning');
                return;
            }

            if (!isFreePlan && !(planRequestReferenceInput?.value || '').trim()) {
                showTenantToast('Debes ingresar una referencia de pago para ese plan.', 'warning');
                return;
            }

            if (!isFreePlan && !planRequestProofInput?.files?.length) {
                showTenantToast('Debes adjuntar un comprobante de pago para ese plan.', 'warning');
                return;
            }

            setTenantSubmitLoading(submitPlanPaymentBtn, true, 'Enviando...');

            try {
                const requestData = new FormData();
                requestData.append('plan_id', selectedPlanId);
                requestData.append('payment_reference', (planRequestReferenceInput?.value || '').trim());
                requestData.append('notes', (planRequestNotesInput?.value || '').trim());

                if (planRequestProofInput?.files?.[0]) {
                    requestData.append('payment_proof', planRequestProofInput.files[0]);
                }

                const response = await fetch(tenantPlanPaymentRequestEndpoint, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
                        'Accept': 'application/json'
                    },
                    body: requestData,
                });

                const data = await response.json().catch(() => ({}));

                if (response.ok) {
                    showTenantToast(data.message || 'Solicitud enviada correctamente.', 'success');
                    setTimeout(() => window.location.reload(), 800);
                    return;
                }

                const firstError = Object.values(data.errors || {}).flat()?.[0];
                showTenantToast(firstError || data.message || 'No se pudo enviar la solicitud.', 'error');
            } catch (error) {
                showTenantToast('No se pudo conectar para enviar la solicitud.', 'error');
            } finally {
                setTenantSubmitLoading(submitPlanPaymentBtn, false);
            }
        });
    }

    if (goToPlanPaymentsBtn) {
        goToPlanPaymentsBtn.addEventListener('click', () => {
            const planTabTrigger = document.getElementById('plan-tab');
            if (!planTabTrigger) {
                return;
            }

            const tabInstance = bootstrap.Tab.getOrCreateInstance(planTabTrigger);
            tabInstance.show();

            setTimeout(() => {
                document.getElementById('plan_request_plan_id')?.focus();
            }, 220);
        });
    }

    setTimeout(() => {
        if (tenantHasPendingPlanPayment) {
            showTenantToast('Tienes un pago de plan pendiente de aprobación por administración.', 'info');
            return;
        }

        if (tenantPlanDaysRemaining === null) {
            return;
        }

        if (tenantPlanDaysRemaining < 0) {
            showTenantToast(`Tu plan está vencido desde hace ${Math.abs(tenantPlanDaysRemaining)} días.`, 'error');
            return;
        }

        if (tenantPlanDaysRemaining <= 7) {
            const dayLabel = tenantPlanDaysRemaining === 1 ? 'día' : 'días';
            showTenantToast(`Tu plan vence en ${tenantPlanDaysRemaining} ${dayLabel}. Registra el pago para evitar cortes.`, 'warning');
        }
    }, 380);

    // Vista previa del logo
    logoInput.addEventListener('change', async (event) => {
        if (!event.target.files?.length) {
            logoPreview.src = '#';
            logoPreview.classList.add('d-none');
            return;
        }

        await optimizeTenantInputFile('logo', 'logo-preview');
    });

        backgroundInput.addEventListener('change', async (event) => {
            if (!event.target.files?.length) {
                backgroundPreview.src = '#';
                backgroundPreview.classList.add('d-none');
                return;
            }

            await optimizeTenantInputFile('background_image', 'bg-preview');
        });

        if (openLogoAiModalBtn) {
            openLogoAiModalBtn.addEventListener('click', () => {
                openAiModal({
                    type: 'logo',
                    inputId: 'logo',
                    previewId: 'logo-preview',
                    backgroundPreviewId: 'bg-preview',
                    fileName: 'logo-gemini.png',
                });
            });
        }

        if (openBackgroundAiModalBtn) {
            openBackgroundAiModalBtn.addEventListener('click', () => {
                openAiModal({
                    type: 'background',
                    inputId: 'background_image',
                    previewId: 'bg-preview',
                    backgroundPreviewId: 'bg-preview',
                    fileName: 'background-gemini.png',
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

                appendAiMessage('assistant', 'Imagen aplicada al formulario. Puedes seguir iterando o cerrar el modal cuando quieras.');
            });
        }

        if (aiPromptInput) {
            aiPromptInput.addEventListener('keydown', async (event) => {
                if (event.key === 'Enter' && !event.shiftKey) {
                    event.preventDefault();
                    aiGenerateBtn?.click();
                }
            });
        }
/* Botón para ocultar/mostrar el iframe */
document.getElementById('toggleIframe').addEventListener('click', function () {
    const content = document.getElementById('iframeContent');
    const container = document.getElementById('iframeContainer');
    const leftColumn = document.getElementById('leftColumn');

    if (content.style.display === "none") {
        // Mostrar de nuevo
        content.style.display = "block";
        container.classList.remove('col-md-12');
        container.classList.add('col-md-6');
        leftColumn.classList.remove('col-md-12');
        leftColumn.classList.add('col-md-6');
        this.textContent = "Minimizar";
    } else {
        // Ocultar iframe
        content.style.display = "none";
        container.classList.remove('col-md-6');
        container.classList.add('col-md-12');
        leftColumn.classList.remove('col-md-6');
        leftColumn.classList.add('col-md-12');
        this.textContent = "Mostrar Vista Previa";
    }
});
// Abrir modal y cargar datos del usuario
document.querySelectorAll('.editUserBtn').forEach(btn => {
    btn.addEventListener('click', () => {
        const id = btn.dataset.id;
        const name = btn.dataset.name;
        const email = btn.dataset.email;

        document.getElementById('edit_user_id').value = id;
        document.getElementById('edit_user_name').value = name;
        document.getElementById('edit_user_email').value = email;

        let modal = new bootstrap.Modal(document.getElementById('editUserModal'));
        modal.show();
    });
});
    // Al cambiar país
    document.getElementById('country').addEventListener('change', function(){
        let country_id = this.value;
        document.getElementById('state').innerHTML = '<option value="">Selecciona un estado</option>';
        document.getElementById('city').innerHTML = '<option value="">Selecciona una ciudad</option>';
        if(country_id){
            document.getElementById('state-loading').style.display = 'block';
            fetch('/get-states/' + country_id)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('state-loading').style.display = 'none';
                    data.forEach(state => {
                        document.getElementById('state').insertAdjacentHTML('beforeend', '<option value="'+state.id+'">'+state.name+'</option>');
                    });
                });
        }
    });

    // Al cambiar estado
    document.getElementById('state').addEventListener('change', function(){
        let state_id = this.value;
        document.getElementById('city').innerHTML = '<option value="">Selecciona una ciudad</option>';
        if(state_id){
            document.getElementById('city-loading').style.display = 'block';
            fetch('/get-cities/' + state_id)
                .then(response => response.json())
                .then(data => {
                    document.getElementById('city-loading').style.display = 'none';
                    data.forEach(city => {
                        document.getElementById('city').insertAdjacentHTML('beforeend', '<option value="'+city.id+'">'+city.name+'</option>');
                    });
            });
        }
    });
    const form = document.getElementById('tenantForm');
    form.addEventListener('submit', async (e) => {
        e.preventDefault();

        const submitBtn = form.querySelector('button[type="submit"]');
        if (submitBtn?.dataset.loading === '1') {
            return;
        }
        setTenantSubmitLoading(submitBtn, true, 'Guardando...');

        const formData = new FormData(form);

        try {
            const response = await fetch("{{ route('tenant.update') }}", {
                method: "POST",
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    'Accept': 'application/json'
                },
                body: formData
            });

            const data = await response.json().catch(() => ({}));

            if (response.ok && data.success) {
                showTenantToast(data.message || 'Tienda actualizada correctamente.', 'success');
                setTimeout(() => window.location.reload(), 700);
                return;
            }

            if (data.errors) {
                const firstError = Object.values(data.errors || {}).flat()?.[0] || 'Revisa los datos del formulario.';
                showTenantToast(firstError, 'warning');
                return;
            }

            const defaultError = response.status === 413
                ? `La solicitud es demasiado grande (413). Recomendado por imagen: ${formatTenantSize(TENANT_SAFE_IMAGE_BYTES)}.`
                : 'Error desconocido';
            showTenantToast(data.message || defaultError, 'error');
        } catch (error) {
            showTenantToast('No se pudo conectar con el servidor. Intenta nuevamente.', 'error');
        } finally {
            setTenantSubmitLoading(submitBtn, false);
        }
    });

</script>
@endpush
