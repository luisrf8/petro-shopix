@extends('layouts.app')

@section('title', 'Clientes')

@section('content')
<div class="container-fluid py-2">
  <div class="row">
    <div class="col-xl-3 col-sm-6 mb-4">
      <div class="card">
        <div class="card-body p-3">
          <p class="text-sm mb-1 text-uppercase font-weight-bold">Clientes</p>
          <h4 class="mb-0">{{ number_format((int) $totalCustomers) }}</h4>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-sm-6 mb-4">
      <div class="card">
        <div class="card-body p-3">
          <p class="text-sm mb-1 text-uppercase font-weight-bold">Activos</p>
          <h4 class="mb-0">{{ number_format((int) $activeCustomers) }}</h4>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-sm-6 mb-4">
      <div class="card">
        <div class="card-body p-3">
          <p class="text-sm mb-1 text-uppercase font-weight-bold">Con compras</p>
          <h4 class="mb-0">{{ number_format((int) $customersWithPurchases) }}</h4>
        </div>
      </div>
    </div>
    <div class="col-xl-3 col-sm-6 mb-4">
      <div class="card">
        <div class="card-body p-3">
          <p class="text-sm mb-1 text-uppercase font-weight-bold">Cobrado</p>
          <h4 class="mb-0">${{ number_format((float) $totalApprovedRevenue, 2) }}</h4>
        </div>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-12">
      <div class="card my-4">
        <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
          <div class="bg-gradient-dark shadow-dark border-radius-lg pt-3 pb-3 d-flex justify-content-between align-items-center">
            <h6 class="text-white text-capitalize ps-3 mb-0">Gestión de clientes</h6>
            <div class="d-flex align-items-center gap-3 me-3">
              <span class="badge bg-light text-dark">{{ $customers->total() }}</span>
              <button type="button" class="btn btn-sm btn-light mb-0" data-bs-toggle="modal" data-bs-target="#createCustomerModal">+ Crear cliente</button>
            </div>
          </div>
        </div>
        <div class="card-body px-0 pb-2">
          <div class="px-3 pt-3">
            <form method="GET" action="{{ route('customers.index') }}" class="row g-2 align-items-end">
              <div class="col-md-4">
                <label for="customer-search" class="form-label">Buscar cliente</label>
                <input
                  type="text"
                  id="customer-search"
                  name="search"
                  value="{{ $search }}"
                  class="form-control border border-1 p-2"
                  placeholder="Nombre, correo, teléfono o DNI"
                >
              </div>
              <div class="col-md-2">
                <label for="customer-status" class="form-label">Estado</label>
                <select id="customer-status" name="status" class="form-control border border-1 p-2">
                  <option value="all" {{ $status === 'all' ? 'selected' : '' }}>Todos</option>
                  <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Activos</option>
                  <option value="inactive" {{ $status === 'inactive' ? 'selected' : '' }}>Inactivos</option>
                </select>
              </div>
              <div class="col-md-2">
                <label for="last_purchase_from" class="form-label">Última compra desde</label>
                <input type="date" id="last_purchase_from" name="last_purchase_from" value="{{ $lastPurchaseFrom }}" class="form-control border border-1 p-2">
              </div>
              <div class="col-md-2">
                <label for="last_purchase_to" class="form-label">Hasta</label>
                <input type="date" id="last_purchase_to" name="last_purchase_to" value="{{ $lastPurchaseTo }}" class="form-control border border-1 p-2">
              </div>
              <div class="col-md-2">
                <label for="ranking" class="form-label">Ranking</label>
                <select id="ranking" name="ranking" class="form-control border border-1 p-2">
                  <option value="all" {{ $ranking === 'all' ? 'selected' : '' }}>General</option>
                  <option value="top" {{ $ranking === 'top' ? 'selected' : '' }}>Top compradores</option>
                </select>
              </div>
              <div class="col-auto">
                <button type="submit" class="btn btn-dark mb-0">Buscar</button>
              </div>
              @if($search !== '' || $status !== 'all' || $lastPurchaseFrom !== '' || $lastPurchaseTo !== '' || $ranking !== 'all')
                <div class="col-auto">
                  <a href="{{ route('customers.index') }}" class="btn btn-outline-secondary mb-0">Limpiar</a>
                </div>
              @endif
            </form>
          </div>

          <div class="table-responsive p-3">
            <table class="table align-items-center mb-0">
              <thead>
                <tr>
                  <th>Cliente</th>
                  <th>Contacto</th>
                  <th>Documento</th>
                  <th>Compras</th>
                  <th>Cobrado</th>
                  <th>Última compra</th>
                  <th>Estado</th>
                  <th>Acción</th>
                </tr>
              </thead>
              <tbody>
                @forelse($customers as $customer)
                  @php
                    $phoneDigits = preg_replace('/\D+/', '', (string) ($customer->phone_number ?? ''));
                    $whatsAppUrl = $phoneDigits !== '' ? 'https://wa.me/' . $phoneDigits : null;
                  @endphp
                  <tr>
                    <td>
                      <div class="d-flex flex-column">
                        <span class="font-weight-bold text-sm">{{ $customer->name }}</span>
                        <span class="text-xs text-secondary">{{ $customer->email }}</span>
                      </div>
                    </td>
                    <td>{{ $customer->phone_number ?: '-' }}</td>
                    <td>{{ $customer->dni ?: '-' }}</td>
                    <td>{{ (int) ($customer->orders_count ?? 0) }}</td>
                    <td>${{ number_format((float) ($customer->total_paid_amount ?? 0), 2) }}</td>
                    <td>{{ $customer->last_purchase_at ? \Carbon\Carbon::parse($customer->last_purchase_at)->format('d/m/Y') : '-' }}</td>
                    <td>
                      <span class="badge {{ $customer->is_active ? 'bg-success' : 'bg-secondary' }}">
                        {{ $customer->is_active ? 'Activo' : 'Inactivo' }}
                      </span>
                    </td>
                    <td>
                      <div class="d-flex flex-wrap gap-2">
                        <button
                          type="button"
                          class="btn btn-outline-dark btn-sm mb-0"
                          data-bs-toggle="modal"
                          data-bs-target="#editCustomerModal"
                          data-customer-id="{{ $customer->id }}"
                          data-customer-name="{{ $customer->name }}"
                          data-customer-email="{{ $customer->email }}"
                          data-customer-phone="{{ $customer->phone_number }}"
                          data-customer-dni="{{ $customer->dni }}"
                          data-customer-active="{{ (int) $customer->is_active }}"
                        >Editar</button>
                        <form method="POST" action="{{ route('customers.toggleStatus', $customer) }}" @if($customer->is_active) data-requires-action-reason="true" data-reason-field="action_reason" data-reason-prompt="Indica el motivo para inactivar este cliente." @endif>
                          @csrf
                          <button type="submit" class="btn btn-outline-secondary btn-sm mb-0">{{ $customer->is_active ? 'Inactivar' : 'Activar' }}</button>
                        </form>
                        @if($whatsAppUrl)
                          <a href="{{ $whatsAppUrl }}" target="_blank" rel="noopener" class="btn btn-outline-success btn-sm mb-0">WhatsApp</a>
                        @endif
                      </div>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="8" class="text-center text-muted py-4">No hay clientes registrados para esta tienda.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>

          <div class="px-3 pb-3 d-flex justify-content-center">
            {{ $customers->links() }}
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="createCustomerModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Crear cliente</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <form method="POST" action="{{ route('customers.store') }}">
        @csrf
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Nombre</label>
            <input type="text" name="name" class="form-control border border-1 p-2" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Correo</label>
            <input type="email" name="email" class="form-control border border-1 p-2" placeholder="Opcional">
          </div>
          <div class="mb-3">
            <label class="form-label">Teléfono</label>
            <input type="text" name="phone_number" class="form-control border border-1 p-2">
          </div>
          <div class="mb-3">
            <label class="form-label">DNI</label>
            <input type="text" name="dni" class="form-control border border-1 p-2">
          </div>
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" role="switch" id="createCustomerActive" name="is_active" value="1" checked>
            <label class="form-check-label" for="createCustomerActive">Activo</label>
          </div>
          <div class="mt-2">
            <small class="text-muted">Se generará automáticamente una contraseña temporal para el cliente.</small>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary mb-0" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-dark mb-0">Guardar</button>
        </div>
      </form>
    </div>
  </div>
</div>

<div class="modal fade" id="editCustomerModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title">Editar cliente</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <form method="POST" id="editCustomerForm">
        @csrf
        @method('PUT')
        <div class="modal-body">
          <div class="mb-3">
            <label class="form-label">Nombre</label>
            <input type="text" name="name" id="editCustomerName" class="form-control border border-1 p-2" required>
          </div>
          <div class="mb-3">
            <label class="form-label">Correo</label>
            <input type="email" name="email" id="editCustomerEmail" class="form-control border border-1 p-2" placeholder="Opcional">
          </div>
          <div class="mb-3">
            <label class="form-label">Teléfono</label>
            <input type="text" name="phone_number" id="editCustomerPhone" class="form-control border border-1 p-2">
          </div>
          <div class="mb-3">
            <label class="form-label">DNI</label>
            <input type="text" name="dni" id="editCustomerDni" class="form-control border border-1 p-2">
          </div>
          <div class="form-check form-switch">
            <input class="form-check-input" type="checkbox" role="switch" id="editCustomerActive" name="is_active" value="1">
            <label class="form-check-label" for="editCustomerActive">Activo</label>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary mb-0" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-dark mb-0">Guardar cambios</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const editModal = document.getElementById('editCustomerModal');
  const editForm = document.getElementById('editCustomerForm');

  editModal?.addEventListener('show.bs.modal', function (event) {
    const button = event.relatedTarget;
    if (!button || !editForm) {
      return;
    }

    const customerId = button.getAttribute('data-customer-id');
    editForm.action = `/customers/${customerId}`;
    document.getElementById('editCustomerName').value = button.getAttribute('data-customer-name') || '';
    document.getElementById('editCustomerEmail').value = button.getAttribute('data-customer-email') || '';
    document.getElementById('editCustomerPhone').value = button.getAttribute('data-customer-phone') || '';
    document.getElementById('editCustomerDni').value = button.getAttribute('data-customer-dni') || '';
    document.getElementById('editCustomerActive').checked = button.getAttribute('data-customer-active') === '1';
  });
});
</script>
@endpush