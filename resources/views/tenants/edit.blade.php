@extends('layouts.app')

@section('title', 'Editar Tienda')

@section('content')
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
<style>
  .tenant-edit-shell {
    display: grid;
    gap: 1.25rem;
  }

  .tenant-edit-summary {
    border: 1px solid rgba(15, 23, 42, 0.08);
    background:
      radial-gradient(circle at top right, rgba(59, 130, 246, 0.12), transparent 32%),
      linear-gradient(135deg, #f8fbff 0%, #ffffff 52%, #fff8ef 100%);
  }

  .tenant-edit-section {
    border: 1px solid rgba(15, 23, 42, 0.08);
    border-radius: 1rem;
    background: #ffffff;
    box-shadow: 0 18px 40px rgba(15, 23, 42, 0.05);
  }

  .tenant-edit-section .card-header {
    border-bottom: 1px solid rgba(15, 23, 42, 0.08);
    background: transparent;
  }

  .tenant-status-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    padding: 0.45rem 0.8rem;
    border-radius: 999px;
    font-size: 0.75rem;
    font-weight: 800;
    letter-spacing: 0.04em;
    text-transform: uppercase;
  }

  .tenant-status-pill.is-active {
    background: rgba(34, 197, 94, 0.12);
    color: #166534;
  }

  .tenant-status-pill.is-inactive {
    background: rgba(239, 68, 68, 0.12);
    color: #b91c1c;
  }

  .tenant-summary-stat {
    padding: 0.95rem 1rem;
    border-radius: 0.9rem;
    background: rgba(255, 255, 255, 0.8);
    border: 1px solid rgba(15, 23, 42, 0.08);
    height: 100%;
  }

  .tenant-summary-stat strong {
    display: block;
    font-size: 1.25rem;
    color: #0f172a;
  }
</style>
<div class="container-fluid py-3">
  <div class="tenant-edit-shell">
    <div class="card tenant-edit-summary">
      <div class="card-body p-4">
        <div class="row g-3 align-items-center">
          <div class="col-xl-5">
            <div class="d-flex align-items-center gap-3">
              <img
                src="{{ $tenant->logo ? (\App\Support\ImageStorage::url($tenant->logo) ?? asset('assets/img/shopix5.png')) : asset('assets/img/shopix5.png') }}"
                alt="Logo de {{ $tenant->name }}"
                width="72"
                height="72"
                style="object-fit: contain; border-radius: 1rem; border: 1px solid rgba(15, 23, 42, 0.08); background: #fff;"
              >
              <div>
                <div class="d-flex flex-wrap gap-2 align-items-center mb-2">
                  <span class="tenant-status-pill {{ (int) ($tenant->is_active ?? 1) === 1 ? 'is-active' : 'is-inactive' }}">
                    {{ (int) ($tenant->is_active ?? 1) === 1 ? 'Activa' : 'Inactiva' }}
                  </span>
                  <span class="badge bg-dark">{{ $tenant->business_type ?? 'Sin tipo' }}</span>
                </div>
                <h3 class="mb-1">{{ $tenant->name }}</h3>
                <p class="text-sm text-muted mb-0">{{ $tenant->email ?: 'Sin correo registrado' }} · /{{ $tenant->slug }}</p>
              </div>
            </div>
          </div>
          <div class="col-sm-6 col-xl-2">
            <div class="tenant-summary-stat">
              <span class="text-xs text-uppercase text-muted fw-bold">Plan actual</span>
              <strong>{{ $latestPlanPayment?->plan?->name ?? 'Sin plan' }}</strong>
              <span class="text-sm text-muted">{{ $resolvedPlanCutoffDate ? 'Vence ' . $resolvedPlanCutoffDate->format('d/m/Y') : 'Sin fecha de corte' }}</span>
            </div>
          </div>
          <div class="col-sm-6 col-xl-2">
            <div class="tenant-summary-stat">
              <span class="text-xs text-uppercase text-muted fw-bold">Usuarios</span>
              <strong>{{ $tenant->users->count() }}</strong>
              <span class="text-sm text-muted">Owner: {{ $owner?->name ?? 'Sin asignar' }}</span>
            </div>
          </div>
          <div class="col-sm-6 col-xl-3">
            <div class="tenant-summary-stat">
              <span class="text-xs text-uppercase text-muted fw-bold">Estado operativo</span>
              <strong>
                @if(is_null($planDaysRemaining))
                  Sin vigencia
                @elseif($planDaysRemaining < 0)
                  {{ abs($planDaysRemaining) }} días vencida
                @else
                  {{ $planDaysRemaining }} días restantes
                @endif
              </strong>
              <a href="{{ route('tenant.public', $tenant) }}" target="_blank" rel="noopener" class="text-sm">Abrir tienda pública</a>
            </div>
          </div>
        </div>
      </div>
    </div>

    @if(session('warning'))
      <div class="alert alert-warning text-white mb-0">{{ session('warning') }}</div>
    @endif

    @if($errors->any())
      <div class="alert alert-danger text-white mb-0">
        <ul class="mb-0 ps-3">
          @foreach($errors->all() as $error)
            <li>{{ $error }}</li>
          @endforeach
        </ul>
      </div>
    @endif

    <form method="POST" action="{{ route('tenants.update', $tenant) }}">
      @csrf
      @method('PUT')

      <div class="row g-4">
        <div class="col-12 col-xl-8">
          <div class="tenant-edit-shell">
            <div class="card tenant-edit-section">
              <div class="card-header p-3 pb-2">
                <h5 class="mb-1">Datos de la tienda</h5>
                <p class="text-sm text-muted mb-0">Identidad comercial, tipo de negocio y presencia pública.</p>
              </div>
              <div class="card-body p-3">
                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label">Nombre</label>
                    <input type="text" name="name" class="form-control border border-1 p-2" value="{{ old('name', $tenant->name) }}" required>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Slug</label>
                    <input type="text" name="slug" class="form-control border border-1 p-2" value="{{ old('slug', $tenant->slug) }}" required>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control border border-1 p-2" value="{{ old('email', $tenant->email) }}">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">RIF de la tienda (opcional)</label>
                    <input type="text" name="rif" class="form-control border border-1 p-2" value="{{ old('rif', $tenant->rif) }}" placeholder="J-12345678-9">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">URL propia (opcional)</label>
                    <input type="text" name="external_url" class="form-control border border-1 p-2" value="{{ old('external_url', $tenant->external_url) }}" placeholder="https://mitienda.com">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Logo</label>
                    <input type="text" name="logo" class="form-control border border-1 p-2" value="{{ old('logo', $tenant->logo) }}" placeholder="Ruta o identificador del logo">
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Tipo de negocio</label>
                    <select id="tenantBusinessType" name="business_type" class="form-select border border-1 p-2" required>
                      <option value="tienda" {{ old('business_type', \Illuminate\Support\Str::lower((string) ($tenant->business_type ?? 'tienda'))) === 'tienda' ? 'selected' : '' }}>Tienda</option>
                      <option value="servicio" {{ old('business_type', \Illuminate\Support\Str::lower((string) ($tenant->business_type ?? 'tienda'))) === 'servicio' ? 'selected' : '' }}>Servicio</option>
                    </select>
                  </div>
                  <div class="col-md-8">
                    <label class="form-label">Rubro económico</label>
                    <select id="tenantEconomicActivity" name="economic_activity" class="form-select border border-1 p-2" data-selected="{{ old('economic_activity', $tenant->economic_activity) }}">
                      <option value="">Selecciona un rubro</option>
                    </select>
                    <small id="tenantEconomicActivityHelp" class="text-muted d-block mt-1"></small>
                  </div>
                  <div class="col-12">
                    <label class="form-label">Slogan</label>
                    <input type="text" name="slogan" class="form-control border border-1 p-2" value="{{ old('slogan', $tenant->slogan) }}">
                  </div>
                  <div class="col-12">
                    <label class="form-label">Descripción</label>
                    <textarea name="description" rows="4" class="form-control border border-1 p-2">{{ old('description', $tenant->description) }}</textarea>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Color primario</label>
                    <input type="text" name="color_primary" class="form-control border border-1 p-2" value="{{ old('color_primary', $tenant->color_primary) }}" placeholder="#000000">
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Color secundario</label>
                    <input type="text" name="color_secondary" class="form-control border border-1 p-2" value="{{ old('color_secondary', $tenant->color_secondary) }}" placeholder="#000000">
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Color acento</label>
                    <input type="text" name="color_accent" class="form-control border border-1 p-2" value="{{ old('color_accent', $tenant->color_accent) }}" placeholder="#000000">
                  </div>
                </div>
              </div>
            </div>

            <div class="card tenant-edit-section">
              <div class="card-header p-3 pb-2">
                <h5 class="mb-1">Ubicación y horarios</h5>
                <p class="text-sm text-muted mb-0">Datos operativos que afectan la publicación y la logística.</p>
              </div>
              <div class="card-body p-3">
                <div class="row g-3">
                  <div class="col-md-4">
                    <label class="form-label">País</label>
                    <select name="country" class="form-select border border-1 p-2">
                      <option value="">Seleccione</option>
                      @foreach($countries as $country)
                        @php
                          $selectedCountry = old('country', $tenant->country);
                          $isSelectedCountry = (string) $selectedCountry === (string) $country->id || (string) $selectedCountry === (string) $country->name;
                        @endphp
                        <option value="{{ $country->id }}" {{ $isSelectedCountry ? 'selected' : '' }}>{{ $country->name }}</option>
                      @endforeach
                    </select>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Estado</label>
                    <select name="state" class="form-select border border-1 p-2">
                      <option value="">Seleccione</option>
                      @foreach($states as $state)
                        @php
                          $selectedState = old('state', $tenant->state);
                          $isSelectedState = (string) $selectedState === (string) $state->id || (string) $selectedState === (string) $state->name;
                        @endphp
                        <option value="{{ $state->id }}" {{ $isSelectedState ? 'selected' : '' }}>{{ $state->name }}</option>
                      @endforeach
                    </select>
                  </div>
                  <div class="col-md-4">
                    <label class="form-label">Ciudad</label>
                    <select name="city" class="form-select border border-1 p-2">
                      <option value="">Seleccione</option>
                      @foreach($cities as $city)
                        @php
                          $selectedCity = old('city', $tenant->city);
                          $isSelectedCity = (string) $selectedCity === (string) $city->id || (string) $selectedCity === (string) $city->name;
                        @endphp
                        <option value="{{ $city->id }}" {{ $isSelectedCity ? 'selected' : '' }}>{{ $city->name }}</option>
                      @endforeach
                    </select>
                  </div>
                  <div class="col-md-3">
                    <label class="form-label">Código país</label>
                    <input type="text" name="phone_code" class="form-control border border-1 p-2" value="{{ old('phone_code', $tenant->phone_code) }}">
                  </div>
                  <div class="col-md-3">
                    <label class="form-label">Teléfono</label>
                    <input type="text" name="phone_number" class="form-control border border-1 p-2" value="{{ old('phone_number', $tenant->phone_number) }}">
                  </div>
                  <div class="col-md-3">
                    <label class="form-label">Latitud</label>
                    <input type="text" name="latitude" class="form-control border border-1 p-2" value="{{ old('latitude', $tenant->latitude) }}">
                  </div>
                  <div class="col-md-3">
                    <label class="form-label">Longitud</label>
                    <input type="text" name="longitude" class="form-control border border-1 p-2" value="{{ old('longitude', $tenant->longitude) }}">
                  </div>
                  <div class="col-12">
                    <label class="form-label">Dirección</label>
                    <input type="text" name="address" class="form-control border border-1 p-2" value="{{ old('address', $tenant->address) }}">
                  </div>
                  <div class="col-12">
                    <label class="form-label d-block">Días laborales</label>
                    <div class="row g-2">
                      @foreach($weekDays as $dayKey => $dayLabel)
                        <div class="col-6 col-md-4 col-lg-3">
                          <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="working_days[]" id="working_day_{{ $dayKey }}" value="{{ $dayKey }}"
                              {{ in_array($dayKey, old('working_days', $tenant->working_days ?? []), true) ? 'checked' : '' }}>
                            <label class="form-check-label" for="working_day_{{ $dayKey }}">{{ $dayLabel }}</label>
                          </div>
                        </div>
                      @endforeach
                    </div>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Hora de apertura</label>
                    <input type="time" name="opening_time" class="form-control border border-1 p-2" value="{{ old('opening_time', !empty($tenant->opening_time) ? \Illuminate\Support\Str::substr((string) $tenant->opening_time, 0, 5) : '') }}">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Hora de cierre</label>
                    <input type="time" name="closing_time" class="form-control border border-1 p-2" value="{{ old('closing_time', !empty($tenant->closing_time) ? \Illuminate\Support\Str::substr((string) $tenant->closing_time, 0, 5) : '') }}">
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <div class="col-12 col-xl-4">
          <div class="tenant-edit-shell">
            <div class="card tenant-edit-section">
              <div class="card-header p-3 pb-2">
                <h5 class="mb-1">Owner y plan</h5>
                <p class="text-sm text-muted mb-0">Responsable principal de la tienda y vigencia comercial.</p>
              </div>
              <div class="card-body p-3">
                <div class="mb-3">
                  <label class="form-label">Nombre owner</label>
                  <input type="text" name="owner_name" class="form-control border border-1 p-2" value="{{ old('owner_name', $owner?->name) }}">
                </div>
                <div class="mb-3">
                  <label class="form-label">Correo owner</label>
                  <input type="email" name="owner_email" class="form-control border border-1 p-2" value="{{ old('owner_email', $owner?->email) }}">
                </div>
                <div class="mb-3">
                  <label class="form-label">Teléfono owner</label>
                  <input type="text" name="owner_phone_number" class="form-control border border-1 p-2" value="{{ old('owner_phone_number', $owner?->phone_number) }}">
                </div>
                <div class="mb-3">
                  <label class="form-label">DNI owner</label>
                  <input type="text" name="owner_dni" class="form-control border border-1 p-2" value="{{ old('owner_dni', $owner?->dni) }}">
                </div>
                <div class="mb-3">
                  <label class="form-label">Nueva contraseña owner</label>
                  <input type="password" name="owner_password" class="form-control border border-1 p-2" autocomplete="new-password" placeholder="Solo si vas a resetearla">
                </div>
                <div class="mb-0">
                  <label class="form-label">Plan</label>
                  @php
                    $currentPlanId = (int) ($latestPlanPayment?->plan_id ?? 0);
                  @endphp
                  <select name="plan_id" class="form-select border border-1 p-2">
                    <option value="" {{ old('plan_id') === null || old('plan_id') === '' ? 'selected' : '' }}>Mantener plan actual (sin renovar)</option>
                    @foreach($upgradePlans as $plan)
                      <option value="{{ $plan->id }}" {{ (string) old('plan_id') === (string) $plan->id ? 'selected' : '' }}>
                        {{ $plan->name }}{{ (int) $plan->id === $currentPlanId ? ' (actual)' : '' }} - ${{ number_format((float) ($plan->price ?? 0), 2) }}
                      </option>
                    @endforeach
                  </select>
                  <small class="text-muted d-block mt-1">Selecciona cualquier plan para cambiarlo. Si eliges el plan actual, se renovará la vigencia.</small>
                  @if($upgradePlans->isEmpty())
                    <small class="text-muted d-block mt-1">No hay planes disponibles para esta tienda.</small>
                  @endif
                </div>
              </div>
            </div>

            <div class="card tenant-edit-section">
              <div class="card-header p-3 pb-2">
                <h5 class="mb-1">Controles operativos</h5>
                <p class="text-sm text-muted mb-0">Activación, fiscalización y restricciones de delivery.</p>
              </div>
              <div class="card-body p-3">
                <div class="mb-3">
                  <label class="form-label">Estado</label>
                  <select name="is_active" class="form-select border border-1 p-2">
                    <option value="1" {{ (string) old('is_active', $tenant->is_active) === '1' ? 'selected' : '' }}>Activa</option>
                    <option value="0" {{ (string) old('is_active', $tenant->is_active) === '0' ? 'selected' : '' }}>Inactiva</option>
                  </select>
                </div>
                <div class="mb-3">
                  <label class="form-label">Facturación digital</label>
                  <select name="electronic_invoicing_enabled" class="form-select border border-1 p-2">
                    <option value="1" {{ (string) old('electronic_invoicing_enabled', (int) ($tenant->electronic_invoicing_enabled ?? false)) === '1' ? 'selected' : '' }}>Activa</option>
                    <option value="0" {{ (string) old('electronic_invoicing_enabled', (int) ($tenant->electronic_invoicing_enabled ?? false)) === '0' ? 'selected' : '' }}>Inactiva</option>
                  </select>
                </div>
                <div class="mb-3">
                  <label class="form-label">Módulo de proyectos</label>
                  <select name="offers_projects" class="form-select border border-1 p-2">
                    <option value="1" {{ (string) old('offers_projects', (int) ($tenant->offers_projects ?? true)) === '1' ? 'selected' : '' }}>Activo</option>
                    <option value="0" {{ (string) old('offers_projects', (int) ($tenant->offers_projects ?? true)) === '0' ? 'selected' : '' }}>Inactivo</option>
                  </select>
                </div>
                <div class="mb-3">
                  <label class="form-label">Contribuyente especial</label>
                  <select name="special_taxpayer" class="form-select border border-1 p-2">
                    <option value="1" {{ (string) old('special_taxpayer', (int) ($tenant->special_taxpayer ?? false)) === '1' ? 'selected' : '' }}>Activo</option>
                    <option value="0" {{ (string) old('special_taxpayer', (int) ($tenant->special_taxpayer ?? false)) === '0' ? 'selected' : '' }}>Inactivo</option>
                  </select>
                </div>
                <div class="mb-3">
                  <label class="form-label">Cambio de alícuotas por imprenta</label>
                  <select name="printer_tax_change_enabled" class="form-select border border-1 p-2">
                    <option value="1" {{ (string) old('printer_tax_change_enabled', (int) ($tenant->printer_tax_change_enabled ?? false)) === '1' ? 'selected' : '' }}>Activo</option>
                    <option value="0" {{ (string) old('printer_tax_change_enabled', (int) ($tenant->printer_tax_change_enabled ?? false)) === '0' ? 'selected' : '' }}>Inactivo</option>
                  </select>
                </div>
                <div class="mb-3">
                  <label class="form-label">Referencia imprenta</label>
                  <input type="text" name="printer_tax_change_reference" class="form-control border border-1 p-2" value="{{ old('printer_tax_change_reference', $tenant->printer_tax_change_reference) }}">
                </div>
                <div class="mb-0">
                  <label class="form-label">Delivery solo ciudad de la tienda</label>
                  <select name="restrict_delivery_city_to_tenant" class="form-select border border-1 p-2">
                    <option value="1" {{ (string) old('restrict_delivery_city_to_tenant', (int) ($tenant->restrict_delivery_city_to_tenant ?? true)) === '1' ? 'selected' : '' }}>Activo</option>
                    <option value="0" {{ (string) old('restrict_delivery_city_to_tenant', (int) ($tenant->restrict_delivery_city_to_tenant ?? true)) === '0' ? 'selected' : '' }}>Inactivo</option>
                  </select>
                </div>
              </div>
            </div>

            <div class="d-flex justify-content-between gap-2">
              <a href="{{ route('tenants.index') }}" class="btn btn-outline-dark mb-0">Volver</a>
              <button type="submit" class="btn btn-dark mb-0">Guardar cambios</button>
            </div>
          </div>
        </div>
      </div>
    </form>
  </div>
</div>
@endsection

@push('scripts')
<script>
  const tenantBusinessCatalog = {
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

  const tenantBusinessExamples = {
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

  function populateTenantEconomicActivities(typeValue, selectedValue = '') {
    const businessType = String(typeValue || 'tienda').toLowerCase() === 'servicio' ? 'servicio' : 'tienda';
    const select = document.getElementById('tenantEconomicActivity');
    const help = document.getElementById('tenantEconomicActivityHelp');

    if (!select) {
      return;
    }

    const options = tenantBusinessCatalog[businessType] || [];
    select.innerHTML = '<option value="">Selecciona un rubro</option>';

    options.forEach((option) => {
      const normalizedSelected = String(selectedValue || '').toLowerCase();
      const isSelected = String(option).toLowerCase() === normalizedSelected;
      select.insertAdjacentHTML('beforeend', `<option value="${option}" ${isSelected ? 'selected' : ''}>${option}</option>`);
    });

    const selectedOption = select.value;
    help.textContent = selectedOption && tenantBusinessExamples[selectedOption]
      ? `Ejemplos: ${tenantBusinessExamples[selectedOption]}`
      : 'Selecciona una categoria para ver ejemplos.';
  }

  const tenantBusinessType = document.getElementById('tenantBusinessType');
  const tenantEconomicActivity = document.getElementById('tenantEconomicActivity');

  if (tenantBusinessType) {
    const initialSelected = tenantEconomicActivity?.dataset.selected || '';
    populateTenantEconomicActivities(tenantBusinessType.value, initialSelected);

    tenantBusinessType.addEventListener('change', function () {
      populateTenantEconomicActivities(this.value, '');
    });
  }

  tenantEconomicActivity?.addEventListener('change', function () {
    const help = document.getElementById('tenantEconomicActivityHelp');
    const selectedOption = this.value;
    help.textContent = selectedOption && tenantBusinessExamples[selectedOption]
      ? `Ejemplos: ${tenantBusinessExamples[selectedOption]}`
      : 'Selecciona una categoria para ver ejemplos.';
  });
</script>
@endpush