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
                $tabCreateActive = ($activeTab ?? 'create') === 'create';
            @endphp
            <ul class="nav nav-tabs" role="tablist">
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ $tabCreateActive ? 'active' : '' }}" id="service-tab-create" data-bs-toggle="tab" data-bs-target="#service-pane-create" type="button" role="tab">Crear servicio</button>
                </li>
                <li class="nav-item" role="presentation">
                    <button class="nav-link {{ !$tabCreateActive ? 'active' : '' }}" id="service-tab-created" data-bs-toggle="tab" data-bs-target="#service-pane-created" type="button" role="tab">Servicios creados</button>
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
                                    <option value="{{ $variant->id }}">{{ $variant->product->name ?? 'Servicio' }} · {{ $variant->size ?? 'Variante' }}</option>
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

                <div class="tab-pane fade {{ !$tabCreateActive ? 'show active' : '' }}" id="service-pane-created" role="tabpanel" aria-labelledby="service-tab-created">
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
                                     data-search="{{ strtolower(trim(($service->display_name ?? $service->name ?? '') . ' ' . ($service->description ?? '') . ' ' . ($service->productVariant->product->name ?? '') . ' ' . ($service->productVariant->size ?? ''))) }}">
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
                                                    <option value="{{ $variant->id }}" {{ (int) $variant->id === (int) ($service->product_variant_id ?? 0) ? 'selected' : '' }}>{{ $variant->product->name ?? 'Servicio' }} · {{ $variant->size ?? 'Variante' }}</option>
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
