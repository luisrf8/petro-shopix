@extends('layouts.app')

@section('content')
<div class="container-fluid py-2">
  {{-- ================ MODAL CREAR ================ --}}
  <div class="modal fade" id="createTaxModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Crear Impuesto</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <form id="createTaxForm">
            @csrf
            <div class="mb-3">
              <label class="form-label">Nombre del impuesto</label>
              <input type="text" class="form-control" name="name" required>
            </div>
            <div class="mb-3">
              <label class="form-label">Porcentaje (%)</label>
              <input type="number" step="0.01" class="form-control" name="rate" required>
            </div>
            <button class="btn btn-dark">Guardar</button>
          </form>
        </div>
      </div>
    </div>
  </div>

  {{-- ================ TABLA ================ --}}
  <div class="card my-4">
    <div class="card-header bg-gradient-dark text-white d-flex justify-content-between align-items-center p-3">
      <h6 class="m-0">IMPUESTOS</h6>
      <label class="text-white" data-bs-toggle="modal" data-bs-target="#createTaxModal">
        + Agregar Impuesto
      </label>
    </div>

    <div class="card-body px-0 pb-2">
      <div class="table-responsive">
        <table class="table text-center">
          <thead>
            <tr>
              <th>Nombre</th>
              <th>Porcentaje</th>
              <th>Estado</th>
              <th>Editar</th>
              <th>Activar / Inactivar</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($taxes as $tax)
              <tr>
                <td>{{ $tax->name }}</td>
                <td>{{ $tax->rate }} %</td>
                <td>
                  <span class="badge {{ $tax->is_active ? 'bg-success':'bg-secondary' }}">
                    {{ $tax->is_active ? 'Activo':'Inactivo' }}
                  </span>
                </td>
                <td>
                  <a href="javascript:;" class="btn-edit-tax" 
                    data-bs-toggle="modal" data-bs-target="#editTaxModal"
                    data-tax-id="{{ $tax->id }}"
                    data-name="{{ $tax->name }}"
                    data-rate="{{ $tax->rate }}">
                    Editar
                  </a>
                </td>
                <td>
                  <a href="javascript:;" class="toggle-status-tax"
                    data-id="{{ $tax->id }}"
                    data-status="{{ $tax->is_active ? 'active':'inactive' }}">
                    {{ $tax->is_active ? 'Inactivar':'Activar' }}
                  </a>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>

  {{-- ================ MODAL EDITAR ================ --}}
  <div class="modal fade" id="editTaxModal" tabindex="-1">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title">Editar Impuesto</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
        </div>
        <div class="modal-body">
          <form id="editTaxForm">
            @csrf
            <input type="hidden" name="id" id="editTaxId">
            <div class="mb-3">
              <label class="form-label">Nombre</label>
              <input type="text" class="form-control" id="editTaxName" name="name">
            </div>
            <div class="mb-3">
              <label class="form-label">Porcentaje (%)</label>
              <input type="number" step="0.01" class="form-control" id="editTaxRate" name="rate">
            </div>
            <button class="btn btn-info">Guardar</button>
          </form>
        </div>
      </div>
    </div>
  </div>

</div>
@endsection
@push('scripts')
<script>
  const authUser = @json($authUser);
  const tenantId = Number(authUser.tenant_id);

  // CREATE
  document.getElementById('createTaxForm').addEventListener('submit', e => {
    e.preventDefault();
    let formData = new FormData(e.target);
    formData.append('tenant_id', tenantId);

    fetch('/taxes/create', {
      method: 'POST',
      headers: {'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value},
      body: formData
    }).then(r => r.status===201 ? location.reload() : alert('Error'));
  });

  // FILL EDIT FORM
  document.querySelectorAll('.btn-edit-tax').forEach(btn => {
    btn.addEventListener('click', () => {
      document.getElementById('editTaxId').value = btn.dataset.taxId;
      document.getElementById('editTaxName').value = btn.dataset.name;
      document.getElementById('editTaxRate').value = btn.dataset.rate;
    });
  });

  // UPDATE
  document.getElementById('editTaxForm').addEventListener('submit', e => {
    e.preventDefault();
    let formData = new FormData(e.target);
    let id = formData.get('id');

    fetch(`/taxes/update/${id}`, {
      method: 'POST',
      headers: {'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value},
      body: formData
    }).then(r => r.status===200 ? location.reload() : alert('Error'));
  });

  // TOGGLE STATE
  document.querySelectorAll('.toggle-status-tax').forEach(btn => {
    btn.addEventListener('click', () => {
      let id = btn.dataset.id;
      let newStatus = btn.dataset.status === 'active' ? 0 : 1;

      fetch(`/taxes/toggle/${id}`, {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
        },
        body: JSON.stringify({is_active: newStatus})
      }).then(r => r.status===200 ? location.reload() : alert('Error'));
    });
  });
</script>
@endpush
