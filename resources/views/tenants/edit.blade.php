@extends('layouts.app')

@section('title', 'Editar sede')

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
                </div>
                <h3 class="mb-1">{{ $tenant->name }}</h3>
                <p class="text-sm text-muted mb-0">{{ $tenant->email ?: 'Sin correo registrado' }}</p>
              </div>
            </div>
          </div>
          <div class="col-sm-6 col-xl-3">
            <div class="tenant-summary-stat">
              <span class="text-xs text-uppercase text-muted fw-bold">Usuarios</span>
              <strong>{{ $tenant->users->count() }}</strong>
              <span class="text-sm text-muted">Gestión posterior por roles</span>
            </div>
          </div>
          <div class="col-sm-6 col-xl-4">
            <div class="tenant-summary-stat">
              <span class="text-xs text-uppercase text-muted fw-bold">Portal público</span>
              <strong>Vista pública</strong>
              <a href="{{ route('tenant.public', $tenant) }}" target="_blank" rel="noopener" class="text-sm">Abrir sede pública</a>
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
        <div class="col-12">
          <div class="tenant-edit-shell">
            <div class="card tenant-edit-section">
              <div class="card-header p-3 pb-2">
                <h5 class="mb-1">Datos de la sede</h5>
                <p class="text-sm text-muted mb-0">Datos base editables de la sede.</p>
              </div>
              <div class="card-body p-3">
                <div class="row g-3">
                  <div class="col-md-6">
                    <label class="form-label">Nombre</label>
                    <input type="text" name="name" class="form-control border border-1 p-2" value="{{ old('name', $tenant->name) }}" required>
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Email</label>
                    <input type="email" name="email" class="form-control border border-1 p-2" value="{{ old('email', $tenant->email) }}">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">RIF de la sede (opcional)</label>
                    <input type="text" name="rif" class="form-control border border-1 p-2" value="{{ old('rif', $tenant->rif) }}" placeholder="J-12345678-9">
                  </div>
                  <div class="col-md-6">
                    <label class="form-label">Logo</label>
                    <input type="text" name="logo" class="form-control border border-1 p-2" value="{{ old('logo', $tenant->logo) }}" placeholder="Ruta o identificador del logo">
                  </div>
                  <div class="col-12">
                    <label class="form-label">Descripción</label>
                    <textarea name="description" rows="4" class="form-control border border-1 p-2">{{ old('description', $tenant->description) }}</textarea>
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
