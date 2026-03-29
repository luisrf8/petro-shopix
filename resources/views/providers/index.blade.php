@extends('layouts.app')

@section('title', 'Proveedores')

@section('content')
<div class="container-fluid py-2">
  <div class="card my-4">
    <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
      <div class="bg-gradient-dark shadow-dark border-radius-lg pt-3 pb-3 d-flex justify-content-between align-items-center">
        <h6 class="text-white text-capitalize ps-3 mb-0">Registro de proveedores</h6>
        <button type="button" class="btn btn-sm btn-light mb-0 me-3" data-bs-toggle="modal" data-bs-target="#createProviderModal">+ Crear proveedor</button>
      </div>
    </div>
    <div class="card-body px-0 pb-2">
      <div class="px-3 pt-3">
        <form method="GET" class="row g-2 align-items-end">
          <div class="col-md-6">
            <label class="form-label">Buscar</label>
            <input type="text" name="search" value="{{ $search }}" class="form-control border border-1 p-2" placeholder="Nombre, contacto, correo o teléfono">
          </div>
          <div class="col-md-3">
            <label class="form-label">Estado</label>
            <select name="status" class="form-control border border-1 p-2">
              <option value="active" {{ $status === 'active' ? 'selected' : '' }}>Activos</option>
              <option value="inactive" {{ $status === 'inactive' ? 'selected' : '' }}>Inactivos</option>
              <option value="all" {{ $status === 'all' ? 'selected' : '' }}>Todos</option>
            </select>
          </div>
          <div class="col-auto">
            <button type="submit" class="btn btn-dark mb-0">Filtrar</button>
          </div>
        </form>
      </div>

      <div class="table-responsive p-3">
        <table class="table align-items-center mb-0">
          <thead>
            <tr>
              <th>Proveedor</th>
              <th>Contacto</th>
              <th>Moneda pago</th>
              <th>Notas</th>
              <th>Estado</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            @forelse($providers as $provider)
              <tr>
                <td>{{ $provider->name }}</td>
                <td>
                  <div class="d-flex flex-column">
                    <span>{{ $provider->contact_name ?: '-' }}</span>
                    <span class="text-xs text-secondary">{{ $provider->email ?: '-' }} | {{ $provider->phone_number ?: '-' }}</span>
                  </div>
                </td>
                <td>{{ strtoupper((string) ($provider->payment_currency_code ?: ($baseCurrencyCode ?? 'USD'))) }}</td>
                <td>{{ $provider->notes ?: '-' }}</td>
                <td><span class="badge {{ $provider->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $provider->is_active ? 'Activo' : 'Inactivo' }}</span></td>
                <td>
                  <div class="d-flex gap-2 flex-wrap">
                    <button type="button" class="btn btn-outline-dark btn-sm mb-0" data-bs-toggle="modal" data-bs-target="#editProviderModal"
                      data-provider-id="{{ $provider->id }}"
                      data-provider-name="{{ $provider->name }}"
                      data-provider-contact="{{ $provider->contact_name }}"
                      data-provider-email="{{ $provider->email }}"
                      data-provider-phone="{{ $provider->phone_number }}"
                      data-provider-payment-currency="{{ strtoupper((string) ($provider->payment_currency_code ?: ($baseCurrencyCode ?? 'USD'))) }}"
                      data-provider-notes="{{ $provider->notes }}"
                      data-provider-active="{{ (int) $provider->is_active }}">Editar</button>
                    <form method="POST" action="{{ route('providers.toggleStatus', $provider) }}">
                      @csrf
                      <button type="submit" class="btn btn-outline-secondary btn-sm mb-0">{{ $provider->is_active ? 'Inactivar' : 'Activar' }}</button>
                    </form>
                  </div>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="6" class="text-center text-muted py-4">No hay proveedores registrados.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
      <div class="px-3 pb-3 d-flex justify-content-center">{{ $providers->links() }}</div>
    </div>
  </div>
</div>

<div class="modal fade" id="createProviderModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Crear proveedor</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <form method="POST" action="{{ route('providers.store') }}">@csrf<div class="modal-body">
      <div class="mb-3"><label class="form-label">Nombre</label><input type="text" name="name" class="form-control border border-1 p-2" required></div>
      <div class="mb-3"><label class="form-label">Contacto</label><input type="text" name="contact_name" class="form-control border border-1 p-2"></div>
      <div class="mb-3"><label class="form-label">Correo</label><input type="email" name="email" class="form-control border border-1 p-2"></div>
      <div class="mb-3"><label class="form-label">Teléfono</label><input type="text" name="phone_number" class="form-control border border-1 p-2"></div>
      <div class="mb-3">
        <label class="form-label">Moneda de pago preferida</label>
        <select name="payment_currency_code" class="form-control border border-1 p-2">
          <option value="{{ $baseCurrencyCode ?? 'USD' }}">{{ $baseCurrencyCode ?? 'USD' }} (moneda madre)</option>
          <option value="USD">USD</option>
          <option value="EUR">EUR</option>
          <option value="BS">BS</option>
        </select>
      </div>
      <div class="mb-3"><label class="form-label">Notas</label><textarea name="notes" class="form-control border border-1 p-2" rows="3"></textarea></div>
      <div class="form-check form-switch"><input class="form-check-input" type="checkbox" id="providerActive" name="is_active" value="1" checked><label class="form-check-label" for="providerActive">Activo</label></div>
    </div><div class="modal-footer"><button type="button" class="btn btn-outline-secondary mb-0" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-dark mb-0">Guardar</button></div></form>
  </div></div>
</div>

<div class="modal fade" id="editProviderModal" tabindex="-1" aria-hidden="true">
  <div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Editar proveedor</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
    <form method="POST" id="editProviderForm">@csrf @method('PUT')<div class="modal-body">
      <div class="mb-3"><label class="form-label">Nombre</label><input type="text" name="name" id="editProviderName" class="form-control border border-1 p-2" required></div>
      <div class="mb-3"><label class="form-label">Contacto</label><input type="text" name="contact_name" id="editProviderContact" class="form-control border border-1 p-2"></div>
      <div class="mb-3"><label class="form-label">Correo</label><input type="email" name="email" id="editProviderEmail" class="form-control border border-1 p-2"></div>
      <div class="mb-3"><label class="form-label">Teléfono</label><input type="text" name="phone_number" id="editProviderPhone" class="form-control border border-1 p-2"></div>
      <div class="mb-3">
        <label class="form-label">Moneda de pago preferida</label>
        <select name="payment_currency_code" id="editProviderPaymentCurrency" class="form-control border border-1 p-2">
          <option value="USD">USD</option>
          <option value="EUR">EUR</option>
          <option value="BS">BS</option>
        </select>
      </div>
      <div class="mb-3"><label class="form-label">Notas</label><textarea name="notes" id="editProviderNotes" class="form-control border border-1 p-2" rows="3"></textarea></div>
      <div class="form-check form-switch"><input class="form-check-input" type="checkbox" id="editProviderActive" name="is_active" value="1"><label class="form-check-label" for="editProviderActive">Activo</label></div>
    </div><div class="modal-footer"><button type="button" class="btn btn-outline-secondary mb-0" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-dark mb-0">Guardar cambios</button></div></form>
  </div></div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const editModal = document.getElementById('editProviderModal');
  const editForm = document.getElementById('editProviderForm');

  editModal?.addEventListener('show.bs.modal', function (event) {
    const button = event.relatedTarget;
    if (!button || !editForm) {
      return;
    }

    const providerId = button.getAttribute('data-provider-id');
    editForm.action = `/providers/${providerId}`;
    document.getElementById('editProviderName').value = button.getAttribute('data-provider-name') || '';
    document.getElementById('editProviderContact').value = button.getAttribute('data-provider-contact') || '';
    document.getElementById('editProviderEmail').value = button.getAttribute('data-provider-email') || '';
    document.getElementById('editProviderPhone').value = button.getAttribute('data-provider-phone') || '';
    document.getElementById('editProviderPaymentCurrency').value = button.getAttribute('data-provider-payment-currency') || 'USD';
    document.getElementById('editProviderNotes').value = button.getAttribute('data-provider-notes') || '';
    document.getElementById('editProviderActive').checked = button.getAttribute('data-provider-active') === '1';
  });
});
</script>
@endpush