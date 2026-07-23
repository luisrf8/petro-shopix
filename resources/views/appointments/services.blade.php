@extends('layouts.app')

@section('title', 'Servicios de Citas')

@push('styles')
<style>
    .services-shell .card {
        border-radius: 1rem;
        border: 1px solid rgba(15, 23, 42, 0.08);
        box-shadow: 0 12px 32px rgba(15, 23, 42, 0.06);
    }

    .service-item {
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 0.85rem;
        padding: 0.9rem;
        background: #fff;
    }

    .service-item summary {
        list-style: none;
    }

    .service-item summary::-webkit-details-marker {
        display: none;
    }

    .service-item .service-note {
        font-size: 0.86rem;
        color: #64748b;
    }
</style>
@endpush

@section('content')
<div class="container py-4 services-shell">
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
        <div>
            <h4 class="mb-1">Servicios de citas</h4>
            <p class="text-muted mb-0">Gestiona creación, edición, activación e inactivación de servicios.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('appointments.index') }}" class="btn btn-outline-secondary btn-sm">Volver a citas</a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger">
            <ul class="mb-0">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <div class="card">
        <div class="card-body p-3 p-lg-4">
            @php
                $resolvedTab = $activeTab ?? 'create';
                if ($errors->any()) {
                    if (old('sessions_count') || old('day_of_weeks') || old('preferred_day_of_week')) {
                        $resolvedTab = 'packages';
                    } elseif (old('name') || old('duration_minutes') || old('product_variant_id')) {
                        $resolvedTab = 'create';
                    }
                }
                $tabCreateActive = $resolvedTab === 'create';
                $tabCreatedActive = $resolvedTab === 'created';
                $tabPackagesActive = $resolvedTab === 'packages';
            @endphp
            <ul class="nav nav-tabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $tabCreateActive ? 'active' : '' }}" id="service-tab-create" data-bs-toggle="tab" data-bs-target="#service-pane-create" type="button" role="tab">Crear servicio</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $tabCreatedActive ? 'active' : '' }}" id="service-tab-created" data-bs-toggle="tab" data-bs-target="#service-pane-created" type="button" role="tab">Servicios creados</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $tabPackagesActive ? 'active' : '' }}" id="service-tab-packages" data-bs-toggle="tab" data-bs-target="#service-pane-packages" type="button" role="tab">Paquetes de sesiones</button>
                </li>
            </ul>

            <div class="tab-content border border-top-0 rounded-bottom p-3">
                <div class="tab-pane fade {{ $tabCreateActive ? 'show active' : '' }}" id="service-pane-create" role="tabpanel" aria-labelledby="service-tab-create">
                    <form method="POST" action="{{ route('appointments.services.store') }}" class="row g-2" id="appointmentServiceCreateForm">
                        @csrf
                        <div class="col-12">
                            <label class="form-label">Producto de servicio</label>
                            <select name="product_variant_id" class="form-control border border-1 p-2" required>
                                <option value="">Selecciona un producto/variante</option>
                                @foreach($serviceVariants as $variant)
                                    <option value="{{ $variant->id }}">{{ $variant->product->display_name ?? 'Servicio' }} · {{ $variant->size ?? 'Variante' }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Nombre comercial</label>
                            <input type="text" name="name" class="form-control border border-1 p-2" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label">Descripción</label>
                            <textarea name="description" class="form-control border border-1 p-2" rows="2"></textarea>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Duración</label>
                            <input type="number" name="duration_minutes" class="form-control border border-1 p-2" min="15" step="15" value="60" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Buffer</label>
                            <input type="number" name="buffer_minutes" class="form-control border border-1 p-2" min="0" step="5" value="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Precio</label>
                            <input type="number" name="price" class="form-control border border-1 p-2" min="0" step="0.01" value="0">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label">Profesionales asignados</label>
                            <select name="user_ids[]" class="form-control border border-1 p-2" multiple size="5">
                                @foreach($professionals as $professional)
                                    <option value="{{ $professional->id }}">{{ $professional->name }}</option>
                                @endforeach
                            </select>
                            <small class="text-muted d-block mt-1">Puedes seleccionar varios profesionales. Si no seleccionas ninguno, el servicio queda disponible para cualquiera.</small>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label">Color</label>
                            <input type="color" name="color_hex" class="form-control form-control-color w-100" value="#0f172a">
                        </div>
                        <div class="col-12">
                            <button class="btn btn-dark w-100 mb-0" type="submit">Guardar servicio</button>
                        </div>
                    </form>
                </div>

                <div class="tab-pane fade {{ $tabCreatedActive ? 'show active' : '' }}" id="service-pane-created" role="tabpanel" aria-labelledby="service-tab-created">
                    <div class="row g-2 mb-3">
                        <div class="col-12 col-md-4">
                            <label class="form-label">Buscar</label>
                            <input type="search" class="form-control border border-1 p-2" id="servicesFilterSearch" placeholder="Nombre, producto o descripción">
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">Estado</label>
                            <select class="form-control border border-1 p-2" id="servicesFilterStatus">
                                <option value="all">Todos</option>
                                <option value="active">Activos</option>
                                <option value="inactive">Inactivos</option>
                            </select>
                        </div>
                        <div class="col-12 col-md-4">
                            <label class="form-label">Profesional</label>
                            <select class="form-control border border-1 p-2" id="servicesFilterProfessional">
                                <option value="all">Todos</option>
                                <option value="unassigned">Sin asignación</option>
                                @foreach($professionals as $professional)
                                    <option value="{{ (int) $professional->id }}">{{ $professional->name }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div id="servicesCreatedList" class="d-flex flex-column gap-2" style="max-height: 65vh; overflow:auto;">
                        @forelse($services as $service)
                            @php
                                $serviceAssignedIds = $service->assignedUsers->pluck('id')->map(fn($id) => (int) $id)->all();
                                if (empty($serviceAssignedIds) && !empty($service->user_id)) {
                                    $serviceAssignedIds = [(int) $service->user_id];
                                }
                                $serviceAssignedNames = $service->assignedUsers->pluck('name')->filter()->values();
                                if ($serviceAssignedNames->isEmpty() && $service->assignedUser) {
                                    $serviceAssignedNames = collect([$service->assignedUser->name]);
                                }
                            @endphp
                            <details class="service-item"
                                     data-service-item="1"
                                     data-status="{{ (bool) ($service->is_active ?? true) ? 'active' : 'inactive' }}"
                                     data-assigned-ids="{{ implode(',', $serviceAssignedIds) }}"
                                     data-search="{{ strtolower(trim(($service->display_name ?? $service->name ?? '') . ' ' . ($service->description ?? '') . ' ' . ($service->productVariant->product->display_name ?? '') . ' ' . ($service->productVariant->size ?? ''))) }}">
                                <summary class="d-flex justify-content-between align-items-center" style="cursor:pointer;">
                                    <div>
                                        <div class="fw-semibold">{{ $service->display_name }}</div>
                                        <div class="service-note">{{ (int) ($service->duration_minutes ?? 60) }} min · Buffer {{ (int) ($service->buffer_minutes ?? 0) }} min · {{ number_format((float) ($service->price ?? 0), 2) }} $</div>
                                    </div>
                                    <span class="badge {{ (bool) ($service->is_active ?? true) ? 'bg-success' : 'bg-secondary' }}">{{ (bool) ($service->is_active ?? true) ? 'Activo' : 'Inactivo' }}</span>
                                </summary>

                                <div class="mt-2">
                                    <form method="POST" action="{{ route('appointments.services.update', ['service' => $service->id]) }}" class="row g-2">
                                        @csrf
                                        @method('PUT')
                                        <div class="col-12">
                                            <label class="form-label">Producto de servicio</label>
                                            <select name="product_variant_id" class="form-control border border-1 p-2" required>
                                                @foreach($serviceVariants as $variant)
                                                    <option value="{{ $variant->id }}" {{ (int) $variant->id === (int) ($service->product_variant_id ?? 0) ? 'selected' : '' }}>{{ $variant->product->display_name ?? 'Servicio' }} · {{ $variant->size ?? 'Variante' }}</option>
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Nombre comercial</label>
                                            <input type="text" name="name" class="form-control border border-1 p-2" value="{{ $service->name }}" required>
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Descripción</label>
                                            <textarea name="description" class="form-control border border-1 p-2" rows="2">{{ $service->description }}</textarea>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Duración</label>
                                            <input type="number" name="duration_minutes" class="form-control border border-1 p-2" min="15" step="15" value="{{ (int) ($service->duration_minutes ?? 60) }}" required>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Buffer</label>
                                            <input type="number" name="buffer_minutes" class="form-control border border-1 p-2" min="0" step="5" value="{{ (int) ($service->buffer_minutes ?? 0) }}">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Precio</label>
                                            <input type="number" name="price" class="form-control border border-1 p-2" min="0" step="0.01" value="{{ number_format((float) ($service->price ?? 0), 2, '.', '') }}">
                                        </div>
                                        <div class="col-md-8">
                                            <label class="form-label">Profesionales asignados</label>
                                            <select name="user_ids[]" class="form-control border border-1 p-2" multiple size="5">
                                                @foreach($professionals as $professional)
                                                    <option value="{{ $professional->id }}" {{ in_array((int) $professional->id, $serviceAssignedIds, true) ? 'selected' : '' }}>{{ $professional->name }}</option>
                                                @endforeach
                                            </select>
                                            <small class="text-muted d-block mt-1">{{ $serviceAssignedNames->isNotEmpty() ? 'Asignados: ' . $serviceAssignedNames->join(', ') : 'Sin asignación específica (cualquiera).' }}</small>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Color</label>
                                            <input type="color" name="color_hex" class="form-control form-control-color w-100" value="{{ $service->color_hex ?: '#0f172a' }}">
                                        </div>
                                        <div class="col-12">
                                            <label class="form-label">Estado</label>
                                            <select name="is_active" class="form-control border border-1 p-2">
                                                <option value="1" {{ (bool) ($service->is_active ?? true) ? 'selected' : '' }}>Activo</option>
                                                <option value="0" {{ !(bool) ($service->is_active ?? true) ? 'selected' : '' }}>Inactivo</option>
                                            </select>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <button class="btn btn-dark w-100 mb-0" type="submit">Guardar cambios</button>
                                        </div>
                                    </form>

                                    <div class="row g-2 mt-1">
                                        <div class="col-12 col-md-6">
                                            <form method="POST" action="{{ route('appointments.services.toggleStatus', ['service' => $service->id]) }}">
                                                @csrf
                                                <button class="btn btn-outline-secondary w-100 mb-0" type="submit">{{ (bool) ($service->is_active ?? true) ? 'Inactivar servicio' : 'Activar servicio' }}</button>
                                            </form>
                                        </div>
                                        <div class="col-12 col-md-6">
                                            <form method="POST" action="{{ route('appointments.services.destroy', ['service' => $service->id]) }}" onsubmit="return confirm('¿Eliminar este servicio? Solo se puede si no tiene citas asociadas.');">
                                                @csrf
                                                @method('DELETE')
                                                <button class="btn btn-outline-danger w-100 mb-0" type="submit">Eliminar servicio</button>
                                            </form>
                                        </div>
                                    </div>
                                </div>
                            </details>
                        @empty
                            <div class="text-muted">Todavía no hay servicios configurados.</div>
                        @endforelse
                    </div>

                    <div id="servicesCreatedEmpty" class="alert alert-light border mt-3 d-none mb-0">No hay servicios que coincidan con los filtros.</div>
                </div>

                <div class="tab-pane fade {{ $tabPackagesActive ? 'show active' : '' }}" id="service-pane-packages" role="tabpanel" aria-labelledby="service-tab-packages">
                    <div class="row g-3">
                        <div class="col-12 col-xl-6">
                            <div class="border rounded p-3 h-100">
                                <h6 class="mb-2">Crear paquete y agendar sesiones</h6>
                                <p class="text-muted mb-3">Define cuantas sesiones tendrá el paquete y en qué días de la semana asistirá el cliente.</p>
                                <form method="POST" action="{{ route('appointments.packages.store') }}" class="row g-2">
                                    @csrf
                                    <div class="col-12">
                                        <label class="form-label">Nombre del paquete</label>
                                        <input type="text" name="name" class="form-control border border-1 p-2" value="{{ old('name') }}" placeholder="Ej: 10 sesiones de corte" required>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Servicio base</label>
                                        <select name="appointment_service_id" class="form-control border border-1 p-2" required>
                                            <option value="">Selecciona un servicio</option>
                                            @foreach($activeServices as $service)
                                                <option value="{{ $service->id }}" {{ (string) old('appointment_service_id') === (string) $service->id ? 'selected' : '' }}>{{ $service->display_name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label">N° de sesiones</label>
                                        <input type="number" name="sessions_count" min="1" max="60" value="{{ old('sessions_count', 10) }}" class="form-control border border-1 p-2" required>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label">Cada (semanas)</label>
                                        <input type="number" name="repeat_every_weeks" min="1" max="12" value="{{ old('repeat_every_weeks', 1) }}" class="form-control border border-1 p-2" required>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Días de asistencia</label>
                                        @php
                                            $oldDays = collect(old('day_of_weeks', old('preferred_day_of_week') !== null ? [old('preferred_day_of_week')] : []))
                                                ->map(fn ($value) => (int) $value)
                                                ->unique()
                                                ->values()
                                                ->all();
                                        @endphp
                                        <div class="d-flex flex-wrap gap-2 border border-1 rounded p-2">
                                            @foreach(\App\Models\UserScheduleRule::WEEK_DAYS as $dayIndex => $dayLabel)
                                                <label class="form-check form-check-inline mb-0 me-0 px-2 py-1 border rounded">
                                                    <input
                                                        class="form-check-input me-1"
                                                        type="checkbox"
                                                        name="day_of_weeks[]"
                                                        value="{{ $dayIndex }}"
                                                        {{ in_array((int) $dayIndex, $oldDays, true) ? 'checked' : '' }}
                                                    >
                                                    <span class="form-check-label">{{ $dayLabel }}</span>
                                                </label>
                                            @endforeach
                                        </div>
                                        <small class="text-muted d-block mt-1">Puedes seleccionar uno o varios días. El sistema distribuirá las sesiones en esos días respetando la frecuencia semanal.</small>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label">Hora</label>
                                        <input type="time" name="preferred_time" class="form-control border border-1 p-2" value="{{ old('preferred_time', '09:00') }}" required>
                                    </div>
                                    <div class="col-6">
                                        <label class="form-label">Inicio</label>
                                        <input type="date" name="start_date" class="form-control border border-1 p-2" value="{{ old('start_date', now()->toDateString()) }}" required>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label">Profesional</label>
                                        <select name="user_id" class="form-control border border-1 p-2" required>
                                            <option value="">Selecciona</option>
                                            @foreach($professionals as $professional)
                                                <option value="{{ $professional->id }}" {{ (string) old('user_id') === (string) $professional->id ? 'selected' : '' }}>{{ $professional->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <label class="form-label">Cliente (opcional)</label>
                                        <select name="customer_id" class="form-control border border-1 p-2">
                                            <option value="">Sin asignar</option>
                                            @foreach($customers as $customer)
                                                <option value="{{ $customer->id }}" {{ (string) old('customer_id') === (string) $customer->id ? 'selected' : '' }}>{{ $customer->name }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Precio</label>
                                        <input type="number" name="price" min="0" step="0.01" value="{{ old('price', 0) }}" class="form-control border border-1 p-2">
                                    </div>
                                    <div class="col-12">
                                        <button class="btn btn-dark w-100 mb-0" type="submit">Crear paquete y agendar sesiones</button>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <div class="col-12 col-xl-6">
                            <div class="border rounded p-3 h-100">
                                <h6 class="mb-2">Paquetes creados recientemente</h6>
                                <div class="d-flex flex-column gap-2" style="max-height: 70vh; overflow:auto;">
                                    @forelse($packages as $package)
                                        @php
                                            $sessionTotal = (int) $package->sessions->count();
                                            $sessionDone = (int) $package->sessions->where('status', 'completed')->count();
                                            $sessionPending = (int) $package->sessions->where('status', 'scheduled')->count();
                                            $sessionsOrdered = $package->sessions
                                                ->sortBy(function ($session) {
                                                    return optional($session->scheduled_for)->timestamp ?? 0;
                                                })
                                                ->values();
                                            if ($sessionsOrdered->isEmpty()) {
                                                $sessionsOrdered = $package->sessions->sortBy('session_number')->values();
                                            }
                                            $firstSession = optional($sessionsOrdered->first())->scheduled_for;
                                            $lastSession = optional($sessionsOrdered->last())->scheduled_for;
                                        @endphp
                                        <div class="service-item">
                                            <div class="d-flex justify-content-between align-items-start gap-2">
                                                <div>
                                                    <div class="fw-semibold">{{ $package->name }}</div>
                                                    <div class="service-note">
                                                        {{ $package->service->display_name ?? $package->service->name ?? 'Servicio' }}
                                                        · {{ (int) ($package->sessions_count ?? $sessionTotal) }} sesiones
                                                        · cada {{ (int) ($package->repeat_every_weeks ?? 1) }} semana(s)
                                                    </div>
                                                    <div class="service-note">Precio: {{ number_format((float) ($package->price ?? 0), 2) }} $</div>
                                                </div>
                                                <span class="badge bg-secondary">{{ $sessionDone }}/{{ $sessionTotal }} completadas</span>
                                            </div>
                                            <div class="service-note mt-1">
                                                Pendientes: {{ $sessionPending }}
                                                @if($firstSession)
                                                    · Primera: {{ \Carbon\Carbon::parse($firstSession)->format('d/m/Y H:i') }}
                                                @endif
                                                @if($lastSession)
                                                    · Última: {{ \Carbon\Carbon::parse($lastSession)->format('d/m/Y H:i') }}
                                                @endif
                                            </div>
                                        </div>
                                    @empty
                                        <div class="text-muted">Aún no hay paquetes creados.</div>
                                    @endforelse
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('servicesFilterSearch');
    const statusSelect = document.getElementById('servicesFilterStatus');
    const professionalSelect = document.getElementById('servicesFilterProfessional');
    const serviceItems = Array.from(document.querySelectorAll('[data-service-item="1"]'));
    const emptyState = document.getElementById('servicesCreatedEmpty');

    function applyServiceFilters() {
        const search = String(searchInput?.value || '').trim().toLowerCase();
        const status = String(statusSelect?.value || 'all').trim();
        const professional = String(professionalSelect?.value || 'all').trim();
        let visibleCount = 0;

        serviceItems.forEach((item) => {
            const itemSearch = String(item.dataset.search || '').toLowerCase();
            const itemStatus = String(item.dataset.status || 'inactive').trim();
            const assignedIds = String(item.dataset.assignedIds || '')
                .split(',')
                .map((value) => Number(value || 0))
                .filter((value) => value > 0);

            const searchOk = search === '' || itemSearch.includes(search);
            const statusOk = status === 'all' || itemStatus === status;
            const professionalOk = professional === 'all'
                ? true
                : (professional === 'unassigned'
                    ? assignedIds.length === 0
                    : assignedIds.includes(Number(professional || 0)));

            const visible = searchOk && statusOk && professionalOk;
            item.style.display = visible ? '' : 'none';
            if (visible) {
                visibleCount += 1;
            }
        });

        if (emptyState) {
            emptyState.classList.toggle('d-none', visibleCount > 0);
        }
    }

    searchInput?.addEventListener('input', applyServiceFilters);
    statusSelect?.addEventListener('change', applyServiceFilters);
    professionalSelect?.addEventListener('change', applyServiceFilters);

    applyServiceFilters();
});
</script>
@endpush
