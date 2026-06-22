@extends('layouts.app')

@section('title', 'Crear Tenant')

@section('content')
        <div class="container">
            <div class="card shadow-sm my-4">
                <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                    <div class="bg-gradient-dark text-white shadow-dark border-radius-lg pt-4 pb-3 d-flex justify-content-center align-items-center">
                        <h1 class="text-white">Crear Nuevo Tenant</h1>
                    </div>
                </div>
                <div class="card-body">
                    @if (session('status'))
                        <div class="alert alert-success text-white" role="alert">
                            {{ session('status') }}
                        </div>
                    @endif

                    @if ($errors->any())
                        <div class="alert alert-danger text-white" role="alert">
                            <strong>No se pudo crear la tienda.</strong>
                            <ul class="mb-0 mt-2">
                                @foreach ($errors->all() as $error)
                                    <li>{{ $error }}</li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    <form action="{{ route('tenants.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <input type="hidden" name="import_payload" id="createTenantImportPayload" value="{{ old('import_payload') }}">

                        <div class="alert alert-light border mb-4">
                            <div class="row g-3 align-items-end">
                                <div class="col-12 col-lg-7">
                                    <label for="createTenantSetupDocx" class="form-label fw-bold">Importar formulario Shopix (.docx)</label>
                                    <input type="file" id="createTenantSetupDocx" class="form-control border border-radius-lg p-2" accept=".docx">
                                    <small class="text-muted d-block mt-1">Importa el documento formal de levantamiento para precargar la tienda, usuarios y el payload de catálogo/servicios.</small>
                                </div>
                                <div class="col-12 col-lg-5">
                                    <button type="button" class="btn btn-outline-dark w-100" id="createTenantImportBtn">Importar documento</button>
                                </div>
                            </div>
                            <div id="createTenantImportStatus" class="small text-muted mt-3"></div>
                            <div id="createTenantImportSummary" class="small mt-2"></div>
                        </div>

                        {{-- Nombre --}}
                        <div class="mb-3">
                            <label for="name" class="form-label">Nombre del Tenant</label>
                            <input type="text" name="name" id="name" class="form-control border border-radius-lg p-2" placeholder="Ej: Mi Empresa" value="{{ old('name') }}" required>
                        </div>

                        {{-- Slug --}}
                        <div class="mb-3">
                            <label for="slug" class="form-label">Slug / URL de Landing</label>
                            <input type="text" name="slug" id="slug" class="form-control border border-radius-lg p-2" placeholder="/ejemplo-mi-empresa" value="{{ old('slug') }}" required>
                        </div>

                        <div class="mb-3">
                            <label for="external_url" class="form-label">URL propia (opcional)</label>
                            <input type="text" name="external_url" id="external_url" class="form-control border border-radius-lg p-2" placeholder="https://mitienda.com" value="{{ old('external_url') }}">
                            <small class="text-muted">Si la indicas, el directorio de Shopix llevara a esta URL externa.</small>
                        </div>

                        {{-- Email --}}
                        <div class="mb-3">
                            <label for="email" class="form-label">Correo de contacto</label>
                            <input type="email" name="email" id="email" class="form-control border border-radius-lg p-2" placeholder="correo@empresa.com" value="{{ old('email') }}" required>
                        </div>

                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="business_type" class="form-label">Tipo de negocio</label>
                                <select name="business_type" id="business_type" class="form-control border border-radius-lg p-2" required>
                                    <option value="">Selecciona una opción</option>
                                    <option value="tienda" {{ old('business_type') === 'tienda' ? 'selected' : '' }}>Tienda</option>
                                    <option value="servicio" {{ old('business_type') === 'servicio' ? 'selected' : '' }}>Servicio</option>
                                </select>
                            </div>
                            <div class="col-md-8">
                                <label for="economic_activity" class="form-label">Rubro económico</label>
                                <select name="economic_activity" id="economic_activity" class="form-control border border-radius-lg p-2" data-selected="{{ old('economic_activity') }}">
                                    <option value="">Selecciona un rubro</option>
                                </select>
                                <small id="economic_activity_help" class="text-muted d-block mt-1"></small>
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
                            $oldWorkingDays = collect(old('working_days', []))->map(fn ($day) => strtolower((string) $day))->all();
                        @endphp

                        <div class="mb-3" id="createTenantScheduleFields" style="display: {{ strtolower((string) old('business_type', 'tienda')) === 'tienda' ? 'block' : 'none' }};">
                            <label class="form-label">Días laborales y horario (opcional)</label>
                            <div class="row g-2 mb-3">
                                @foreach($weekDays as $dayKey => $dayLabel)
                                    <div class="col-6 col-md-3">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" name="working_days[]" id="create_working_day_{{ $dayKey }}" value="{{ $dayKey }}" {{ in_array($dayKey, $oldWorkingDays, true) ? 'checked' : '' }}>
                                            <label class="form-check-label" for="create_working_day_{{ $dayKey }}">{{ $dayLabel }}</label>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                            <div class="row g-3">
                                <div class="col-12 col-md-6">
                                    <label for="opening_time" class="form-label">Hora de apertura</label>
                                    <input type="time" name="opening_time" id="opening_time" class="form-control border border-radius-lg p-2" value="{{ old('opening_time') }}">
                                </div>
                                <div class="col-12 col-md-6">
                                    <label for="closing_time" class="form-label">Hora de cierre</label>
                                    <input type="time" name="closing_time" id="closing_time" class="form-control border border-radius-lg p-2" value="{{ old('closing_time') }}">
                                </div>
                            </div>
                        </div>
                        
                        <div class="row mb-3">
                            {{-- Logo --}}
                            <div class="col-12 col-md-6">
                                <label for="logo" class="form-label">Logo (PNG o SVG)</label>
                                <div class="d-flex align-items-center gap-3">
                                    <img id="logo-preview" src="#"
                                        alt="Vista previa del logo"
                                        class="img-fluid rounded shadow-sm border d-none p-2"
                                        style="max-height: 100px; max-width: 100px;">
                                    <input type="file" name="logo" id="logo"
                                        class="form-control border border-radius-lg p-2"
                                        accept=".png,.svg">
                                </div>
                            </div>
                            {{-- Colores --}}
                            <div class="row col-12 col-md-6">
                                <div class="col-md-4">
                                    <label for="color_primary" class="form-label">Color Primario</label>
                                    <input type="color" name="color_primary" id="color_primary" class="form-control border border-radius-lg p-2 border border-radius-lg p-2 form-control border border-radius-lg p-2 border border-radius-lg p-2-color h-50" value="#0d6efd">
                                </div>
                                <div class="col-md-4">
                                    <label for="color_secondary" class="form-label">Color Secundario</label>
                                    <input type="color" name="color_secondary" id="color_secondary" class="form-control border border-radius-lg p-2 form-control border border-radius-lg p-2-color h-50" value="#6c757d">
                                </div>
                                <div class="col-md-4">
                                    <label for="color_accent" class="form-label">Color Acento</label>
                                    <input type="color" name="color_accent" id="color_accent" class="form-control border border-radius-lg p-2 form-control border border-radius-lg p-2-color h-50" value="#ffc107">
                                </div>
                            </div>

                        </div>
                        
                        {{-- Plan --}}
                        <div class="mb-3">
                            <label for="plan_id" class="form-label">Plan</label>
                            <select name="plan_id" id="plan_id" class="form-control border border-radius-lg p-2" required>
                                @foreach($plans as $plan)
                                    <option value="{{ $plan->id }}" {{ (string) old('plan_id') === (string) $plan->id ? 'selected' : '' }}>{{ $plan->name }} - ${{ $plan->price }}</option>
                                @endforeach
                            </select>
                        </div>



                        {{-- Roles y Usuarios iniciales --}}
                        <div class="mb-3">
                            <label class="form-label">Usuarios iniciales</label>
                            <div class="accordion" id="accordionRoles">

                                {{-- Owner --}}
                                <div class="accordion-item border border-radius-lg p-2">
                                    <h2 class="accordion-header" id="headingOwner">
                                        <button class="accordion-button" type="button" data-bs-toggle="collapse" data-bs-target="#collapseOwner" aria-controls="collapseOwner">
                                            👑 Owner
                                        </button>
                                    </h2>
                                    <div id="collapseOwner" class="accordion-collapse collapse show" aria-labelledby="headingOwner" data-bs-parent="#accordionRoles">
                                        <div class="accordion-body">
                                            <div class="mb-2">
                                                <label for="owner_name" class="form-label">Nombre</label>
                                                <input type="text" name="users[owner][name]" id="owner_name" class="form-control border border-radius-lg p-2" placeholder="Nombre del Owner">
                                            </div>
                                            <div class="mb-2">
                                                <label for="owner_email" class="form-label">Correo</label>
                                                <input type="email" name="users[owner][email]" id="owner_email" class="form-control border border-radius-lg p-2" placeholder="owner@empresa.com">
                                            </div>
                                            <div class="mb-2">
                                                <label for="owner_password" class="form-label">Contraseña</label>
                                                <div class="input-group gap-2">
                                                    <input type="password" name="users[owner][password]" id="owner_password" class="form-control border border-radius-lg p-2" placeholder="********">
                                                        <button type="button" class="input-group-text toggle-password mx-5" data-target="owner_password">
                                                            👁️
                                                        </button>
                                                        <button type="button" class="input-group-text copy-password mx-3" data-target="owner_password">
                                                            📋
                                                        </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Admin --}}
                                <div class="accordion-item border border-radius-lg p-2 mt-2">
                                    <h2 class="accordion-header" id="headingAdmin">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseAdmin" aria-expanded="false" aria-controls="collapseAdmin">
                                            🛠️ Admin
                                        </button>
                                    </h2>
                                    <div id="collapseAdmin" class="accordion-collapse collapse" aria-labelledby="headingAdmin" data-bs-parent="#accordionRoles">
                                        <div class="accordion-body">
                                            <div class="mb-2">
                                                <label for="admin_name" class="form-label">Nombre</label>
                                                <input type="text" name="users[admin][name]" id="admin_name" class="form-control border border-radius-lg p-2" placeholder="Nombre del Admin">
                                            </div>
                                            <div class="mb-2">
                                                <label for="admin_email" class="form-label">Correo</label>
                                                <input type="email" name="users[admin][email]" id="admin_email" class="form-control border border-radius-lg p-2" placeholder="admin@empresa.com">
                                            </div>
                                            <div class="mb-2">
                                                <label for="admin_password" class="form-label">Contraseña</label>
                                                <div class="input-group">
                                                    <input type="password" name="users[admin][password]" id="admin_password" class="form-control border border-radius-lg p-2" placeholder="********">
                                                        <button type="button" class="input-group-text toggle-password mx-5" data-target="admin_password">
                                                            👁️
                                                        </button>
                                                        <button type="button" class="input-group-text copy-password mx-3" data-target="admin_password">
                                                            📋
                                                        </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                {{-- Vendor --}}
                                <div class="accordion-item border border-radius-lg p-2 mt-2">
                                    <h2 class="accordion-header" id="headingVendor">
                                        <button class="accordion-button collapsed" type="button" data-bs-toggle="collapse" data-bs-target="#collapseVendor" aria-expanded="false" aria-controls="collapseVendor">
                                            🛒 Vendor
                                        </button>
                                    </h2>
                                    <div id="collapseVendor" class="accordion-collapse collapse" aria-labelledby="headingVendor" data-bs-parent="#accordionRoles">
                                        <div class="accordion-body">
                                            <div class="mb-2">
                                                <label for="vendor_name" class="form-label">Nombre</label>
                                                <input type="text" name="users[vendor][name]" id="vendor_name" class="form-control border border-radius-lg p-2" placeholder="Nombre del Vendor">
                                            </div>
                                            <div class="mb-2">
                                                <label for="vendor_email" class="form-label">Correo</label>
                                                <input type="email" name="users[vendor][email]" id="vendor_email" class="form-control border border-radius-lg p-2" placeholder="vendor@empresa.com">
                                            </div>

                                            <div class="mb-2">
                                                <label for="vendor_password" class="form-label">Contraseña</label>
                                                <div class="input-group">
                                                    <input type="password" name="users[vendor][password]" id="vendor_password" class="form-control border border-radius-lg p-2" placeholder="********">
                                                        <button type="button" class="input-group-text toggle-password mx-5" data-target="vendor_password">
                                                            <i class="material-symbols-rounded opacity-5">hide_source</i>
                                                            <i class="material-symbols-rounded opacity-5">remove_red_eye</i>
                                                        </button>
                                                        <button type="button" class="input-group-text copy-password mx-3" data-target="vendor_password">
                                                            📋
                                                        </button>
                                                </div>
                                            </div>

                                        </div>
                                    </div>
                                </div>

                            </div>
                            <small class="text-muted">Recomendamos anotar en un lugar seguro las credenciales asociadas.</small>
                            <small class="text-muted">Los usuarios ingresados serán creados y vinculados al tenant automáticamente.</small>
                        </div>


                        <div class="d-flex justify-content-end">
                            <button type="submit" class="btn btn-success px-4">Crear Tenant</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
@endsection
@push('scripts')
<script>
document.addEventListener("DOMContentLoaded", () => {
    const createTenantImportEndpoint = "{{ route('tenant.importSetupDocx', [], false) }}";
    // Mostrar / ocultar contraseña
    document.querySelectorAll(".toggle-password").forEach(btn => {
        btn.addEventListener("click", () => {
            const targetId = btn.getAttribute("data-target");
            const input = document.getElementById(targetId);
            if (input.type === "password") {
                input.type = "text";
                btn.textContent = "🙈";
            } else {
                input.type = "password";
                btn.textContent = "👁️";
            }
        });
    });

    // Copiar contraseña al portapapeles
    document.querySelectorAll(".copy-password").forEach(btn => {
        btn.addEventListener("click", async () => {
            const targetId = btn.getAttribute("data-target");
            const input = document.getElementById(targetId);
            try {
                await navigator.clipboard.writeText(input.value);
                btn.textContent = "✅";
                setTimeout(() => btn.textContent = "📋", 1500);
            } catch (err) {
                alert("No se pudo copiar la contraseña");
            }
        });
    });

    // Vista previa del logo
    const slugInput = document.getElementById("slug");
    const logoInput = document.getElementById("logo");
    const logoPreview = document.getElementById("logo-preview");
    const businessTypeSelect = document.getElementById("business_type");
    const economicActivitySelect = document.getElementById("economic_activity");
    const importInput = document.getElementById('createTenantSetupDocx');
    const importButton = document.getElementById('createTenantImportBtn');
    const importPayloadInput = document.getElementById('createTenantImportPayload');
    const importStatus = document.getElementById('createTenantImportStatus');
    const importSummary = document.getElementById('createTenantImportSummary');

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

    if (slugInput) {
        const normalizeSlug = (value) => String(value ?? "")
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .replace(/[^a-z0-9]+/g, '-')
            .replace(/-{2,}/g, '-')
            .replace(/^-+|-+$/g, '');

        slugInput.addEventListener("input", () => {
            const normalizedValue = normalizeSlug(slugInput.value);
            if (slugInput.value !== normalizedValue) {
                slugInput.value = normalizedValue;
            }
        });
    }

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

    if (businessTypeSelect) {
        const syncCreateTenantScheduleVisibility = () => {
            const scheduleBlock = document.getElementById('createTenantScheduleFields');
            if (!scheduleBlock) {
                return;
            }

            const isPhysicalStore = String(businessTypeSelect.value || '').toLowerCase() === 'tienda';
            scheduleBlock.style.display = isPhysicalStore ? 'block' : 'none';
        };

        businessTypeSelect.addEventListener('change', () => {
            refreshEconomicActivities('');
            syncCreateTenantScheduleVisibility();
        });

        syncCreateTenantScheduleVisibility();
    }

    if (economicActivitySelect) {
        economicActivitySelect.addEventListener('change', () => refreshEconomicActivities(economicActivitySelect.value));
        refreshEconomicActivities(economicActivitySelect.dataset.selected || '');
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content')
        || document.querySelector('input[name="_token"]')?.value
        || '';

    const setCreateImportStatus = (message, type = 'muted') => {
        if (!importStatus) {
            return;
        }

        importStatus.className = `small mt-3 text-${type}`;
        importStatus.textContent = message || '';
    };

    const renderCreateImportSummary = (summary = {}) => {
        if (!importSummary) {
            return;
        }

        const parts = [
            ['users', 'usuarios'],
            ['payment_methods', 'métodos de pago'],
            ['store_catalog', 'items de tienda'],
            ['service_catalog', 'servicios'],
            ['schedule_rules', 'horarios'],
        ]
            .map(([key, label]) => {
                const count = Number(summary?.[key] || 0);
                return count > 0 ? `${count} ${label}` : null;
            })
            .filter(Boolean);

        importSummary.textContent = parts.length
            ? `Detectado: ${parts.join(', ')}.`
            : 'Documento leído sin bloques repetibles detectados.';
    };

    const setCheckboxValues = (selector, values = []) => {
        document.querySelectorAll(selector).forEach((checkbox) => {
            checkbox.checked = values.includes(String(checkbox.value || '').toLowerCase());
        });
    };

    const canonicalRoleKey = (role) => {
        const normalized = String(role || '').trim().toLowerCase();
        if (["owner"].includes(normalized)) return 'owner';
        if (["admin", "administrador"].includes(normalized)) return 'admin';
        if (["seller", "vendor", "vendedor"].includes(normalized)) return 'vendor';
        return normalized;
    };

    const applyImportedCreateTenantData = (payload = {}) => {
        const tenant = payload.tenant || {};
        const users = Array.isArray(payload.users) ? payload.users : [];

        if (tenant.name) document.getElementById('name').value = tenant.name;
        if (tenant.slug) document.getElementById('slug').value = tenant.slug;
        if (tenant.email) document.getElementById('email').value = tenant.email;
        if (tenant.business_type && businessTypeSelect) {
            businessTypeSelect.value = String(tenant.business_type).toLowerCase() === 'servicio' ? 'servicio' : 'tienda';
        }
        refreshEconomicActivities(tenant.economic_activity || '');
        if (tenant.economic_activity && economicActivitySelect) {
            economicActivitySelect.value = tenant.economic_activity;
            refreshEconomicActivities(tenant.economic_activity);
        }

        if (tenant.opening_time) document.getElementById('opening_time').value = String(tenant.opening_time).slice(0, 5);
        if (tenant.closing_time) document.getElementById('closing_time').value = String(tenant.closing_time).slice(0, 5);
        setCheckboxValues('input[name="working_days[]"]', Array.isArray(tenant.working_days) ? tenant.working_days : []);

        users.forEach((user) => {
            const roleKey = canonicalRoleKey(user.role);
            if (!roleKey) {
                return;
            }

            const prefix = roleKey;
            const resolvedPrefix = prefix === 'vendor' ? 'vendor' : prefix;
            const nameInput = document.getElementById(`${resolvedPrefix}_name`);
            const emailInput = document.getElementById(`${resolvedPrefix}_email`);
            const passwordInput = document.getElementById(`${resolvedPrefix}_password`);

            if (nameInput && user.name) nameInput.value = user.name;
            if (emailInput && user.email) emailInput.value = user.email;
            if (passwordInput && user.password) passwordInput.value = user.password;
        });

        if (businessTypeSelect) {
            businessTypeSelect.dispatchEvent(new Event('change'));
            if (tenant.economic_activity && economicActivitySelect) {
                economicActivitySelect.value = tenant.economic_activity;
                refreshEconomicActivities(tenant.economic_activity);
            }
        }
    };

    if (importButton && importInput && importPayloadInput) {
        importButton.addEventListener('click', async () => {
            const file = importInput.files?.[0];
            if (!file) {
                setCreateImportStatus('Selecciona un archivo .docx para importar.', 'warning');
                return;
            }

            setCreateImportStatus('Importando documento...', 'muted');
            importButton.disabled = true;

            try {
                const formData = new FormData();
                formData.append('setup_docx', file);
                formData.append('_token', csrfToken);

                const response = await fetch(createTenantImportEndpoint, {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    credentials: 'same-origin',
                    body: formData,
                });

                const data = await response.json().catch(() => ({}));
                if (!response.ok || !data.success) {
                    const errorMessage = data?.errors
                        ? Object.values(data.errors).flat()[0]
                        : (data.message || 'No se pudo importar el documento.');
                    setCreateImportStatus(errorMessage, 'danger');
                    return;
                }

                importPayloadInput.value = JSON.stringify(data.payload || {});
                applyImportedCreateTenantData(data.payload || {});
                renderCreateImportSummary(data.summary || {});
                setCreateImportStatus(data.message || 'Documento importado correctamente.', 'success');
            } catch (error) {
                setCreateImportStatus('No se pudo conectar con el servidor para importar el documento.', 'danger');
            } finally {
                importButton.disabled = false;
            }
        });
    }
});
</script>
@endpush


