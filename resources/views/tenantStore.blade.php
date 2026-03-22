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
</style>

@php
    $authUser = auth()->user();
    $canAssignStoreRoles = $authUser?->canAssignStoreRoles() ?? false;
    $isOwnerRole = $authUser?->isOwner() ?? false;
    $tenantStoreUrl = $tenant->full_url ?? (url('/').'/'.$tenant->slug);
    $tenantBusinessType = \Illuminate\Support\Str::lower((string) ($tenant->business_type ?? 'tienda'));
@endphp

<div class="p-4 ">
    <h1 class="">Gestión de Tienda</h1>

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

                        {{-- NUEVO: Usuarios de la tienda --}}
                        <li class="nav-item" role="presentation">
                            <button class="nav-link" id="users-tab" data-bs-toggle="tab" data-bs-target="#users" type="button" role="tab">
                                Usuarios
                            </button>
                        </li>
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
                                <div class="mb-3">
                                    <label class="form-label">Url de la Tienda</label>
                                    <input type="text" class="form-control p-2 border border-radius-lg" id="storeSlugInput" name="slug" value="{{ $tenant->slug ?? '' }}">
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
                                            class="btn btn-outline-dark"
                                            id="openStoreUrlBtn">
                                            Abrir tienda
                                        </a>
                                        <button
                                            type="button"
                                            class="btn btn-outline-secondary"
                                            id="copyStoreUrlBtn">
                                            Copiar enlace
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
                                    <label class="form-label">Cambiar Logo (PNG o SVG)</label>
                                    <input type="file" name="logo" id="logo" class="form-control form-control-lg border border-radius-lg" accept=".png,.svg">
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
                                    <label class="form-label">Imagen de fondo (PNG o SVG) (1920x1080)</label>
                                    <input type="file" name="background_image" id="background_image" class="form-control form-control-lg border border-radius-lg" accept=".png,.svg">
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

                            {{-- TAB 4: Usuarios --}}
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
    let aiModalInstance = null;
    let currentAiTarget = null;
        let aiChatHistory = [];
        let aiLatestResult = null;

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
        const file = new File([blob], fileName, { type: mimeType || 'image/png' });
        const dataTransfer = new DataTransfer();
        dataTransfer.items.add(file);
        input.files = dataTransfer.files;

        preview.src = URL.createObjectURL(file);
        preview.classList.remove('d-none');
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
            alert('Debes escribir un prompt para generar la imagen.');
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
            alert(error.message || 'Error al generar la imagen con Gemini.');
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

    if (businessTypeSelect) {
        businessTypeSelect.addEventListener('change', () => refreshEconomicActivities(''));
    }

    if (economicActivitySelect) {
        economicActivitySelect.addEventListener('change', () => refreshEconomicActivities(economicActivitySelect.value));
        refreshEconomicActivities(economicActivitySelect.dataset.selected || '');
    }

    if (copyStoreUrlBtn && storePublicUrlInput) {
        copyStoreUrlBtn.addEventListener('click', async () => {
            const originalText = copyStoreUrlBtn.textContent;
            const copied = await copyText(storePublicUrlInput.value || '');
            copyStoreUrlBtn.textContent = copied ? 'Copiado' : 'Error';
            setTimeout(() => {
                copyStoreUrlBtn.textContent = originalText;
            }, 1400);
        });
    }

    // Vista previa del logo
    logoInput.addEventListener("change", (event) => {
      const file = event.target.files[0];
      if (file) {
        const reader = new FileReader();
        reader.onload = e => {
          logoPreview.src = e.target.result;
          logoPreview.classList.remove("d-none");
        };
        reader.readAsDataURL(file);
      } else {
        logoPreview.src = "#";
        logoPreview.classList.add("d-none");
      }
    });

        backgroundInput.addEventListener("change", (event) => {
            const file = event.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = e => {
                    backgroundPreview.src = e.target.result;
                    backgroundPreview.classList.remove("d-none");
                };
                reader.readAsDataURL(file);
            }
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

        const formData = new FormData(form);

        const response = await fetch("{{ route('tenant.update') }}", {
            method: "POST",
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
            },
            body: formData
        });

        const data = await response.json();

        if (data.success) {
            alert(data.message);
            window.location.reload();
        } else if (data.errors) {
            // Mostrar errores de validación
            console.log(data.errors);
            alert("Errores: " + JSON.stringify(data.errors));
        } else {
            alert(data.message || "Error desconocido");
        }
    });

</script>
@endpush
