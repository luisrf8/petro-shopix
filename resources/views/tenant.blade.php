@extends('layouts.app')

@section('title', 'Tiendas')

@section('content')
<div class="container-fluid py-2">
  <!-- Tabla para mostrar tenants -->
  <div class="row">
    <div class="col-12">
      <div class="card my-4">
        <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
          <div class="bg-gradient-dark shadow-dark border-radius-lg pt-4 pb-3 d-flex justify-content-between align-items-center">
            <h6 class="text-white text-capitalize ps-3">TIENDAS</h6>
            <a href="/create-tenant" blank="_blank">
              <div class="py-1 px-3 text-end">
                <label class="text-white">+ Agregar Tienda</label>
              </div>
            </a>
          </div>
        </div>
        <div class="card-body px-0 pb-2">
          <div class="table-responsive p-0">
            <table class="table align-items-center mb-0">
              <thead class="text-center">
                <tr>
                  <th>Logo</th>
                  <th>Nombre</th>
                  <th>URL</th>
                  <th>Email</th>
                  <th>Plan</th>
                  <th>Editar</th>
                  <th>Eliminar</th>
                </tr>
              </thead>
              <tbody class="text-center">
                @foreach($tenants as $tenant)
                  <tr>
                    <td>
                      <img src="{{ $tenant->logo ? asset('storage/' . $tenant->logo) : asset('assets/img/shopix5.png') }}" alt="Logo"
                      class="navbar-brand-img"
                      width="100"
                      height="100"
                      alt="main_logo"
                      style="object-fit: contain;">
                    </td>
                    <td>{{ $tenant->name }}</td>
                    <td>{{ $tenant->slug }}</td>
                    <td>{{ $tenant->email }}</td>

                    <td>
                      @php
                        $owner = $tenant->users->first(function($user) {
                          return optional($user->role)->name === 'owner';
                        }) ?? $tenant->users->first();

                        $latestPayment = $tenant->tenantPlanPayments
                          ->where('status', 'paid')
                          ->sortBy(function ($payment) {
                            return optional($payment->paid_at)->timestamp ?? 0;
                          })
                          ->last();
                      @endphp
                      <p>Dueño: {{ $owner?->name ?? 'Sin dueño' }}</p>
                      <p>Usuarios: {{ $tenant->users->count() }}</p>
                      @if($latestPayment)
                        <p>Plan actual: {{ $latestPayment->plan->name }} - ${{ $latestPayment->amount }} - Estado: {{ $latestPayment->status }}</p>
                        <p>Vence: {{ optional($latestPayment->expires_at)->format('d/m/Y H:i') ?? 'Sin fecha' }}</p>
                      @else
                        <p>Plan actual: Sin plan</p>
                        <p>Vence: Sin fecha</p>
                      @endif
                      {{-- O solo plan activo --}}
                      {{-- <p>Plan activo: {{ $tenant->activePlanPayment->plan->name ?? 'Sin plan' }}</p> --}}
                    </td>

                    <td>
                      <a href="javascript:;" 
                        class="text-secondary font-weight-bold text-xs btn-edit-tenant"
                        data-bs-toggle="modal" 
                        data-bs-target="#editTenantModal" 
                        data-id="{{ $tenant->id }}"
                        data-name="{{ $tenant->name }}"
                        data-slug="{{ $tenant->slug }}"
                        data-email="{{ $tenant->email }}"
                        data-logo="{{ $tenant->logo }}"
                        data-owner-name="{{ $owner?->name }}"
                        data-owner-email="{{ $owner?->email }}"
                        data-plan-id="{{ $latestPayment?->plan_id }}"
                        data-active="{{ $tenant->is_active }}">
                        Editar
                      </a>
                    </td>
                    <td>
                      <a href="javascript:;" 
                         class="text-danger font-weight-bold text-xs btn-delete-tenant"
                         data-id="{{ $tenant->id }}">
                        Eliminar
                      </a>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal para editar tenant -->
  <div class="modal fade" id="editTenantModal" tabindex="-1" aria-labelledby="editTenantModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="editTenantModalLabel">Editar Tienda</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="editTenantForm" enctype="multipart/form-data">
            @csrf
            <input type="hidden" id="editTenantId" name="id">
            <div class="mb-3">
              <label for="editTenantLogo" class="form-label">Logo</label>
              <div class="text-center mb-2">
                <img id="editTenantLogoPreview" 
                    src="{{ asset('assets/img/shopix5.png') }}" 
                    alt="Logo"
                    width="100"
                    height="100"
                    style="object-fit: contain; border: 1px solid #ddd; border-radius: 8px;">
              </div>
              <input type="text" class="form-control border border-1 p-2" id="editTenantLogo" name="logo" type="hidden" style="display: none;">
            </div>
            <div class="mb-3">
              <label for="editTenantName" class="form-label">Nombre</label>
              <input type="text" class="form-control border border-1 p-2" id="editTenantName" name="name" required>
            </div>
            <div class="mb-3">
              <label for="editTenantSlug" class="form-label">Slug</label>
              <input type="text" class="form-control border border-1 p-2" id="editTenantSlug" name="slug" required>
            </div>
            <div class="mb-3">
              <label for="editTenantEmail" class="form-label">Email</label>
              <input type="email" class="form-control border border-1 p-2" id="editTenantEmail" name="email">
            </div>

            <div class="mb-3">
              <label for="editOwnerName" class="form-label">Nombre dueño</label>
              <input type="text" class="form-control border border-1 p-2" id="editOwnerName" name="owner_name">
            </div>

            <div class="mb-3">
              <label for="editOwnerEmail" class="form-label">Email dueño</label>
              <input type="email" class="form-control border border-1 p-2" id="editOwnerEmail" name="owner_email">
            </div>

            <div class="mb-3">
              <label for="editOwnerPassword" class="form-label">Nueva contraseña dueño (opcional)</label>
              <input type="password" class="form-control border border-1 p-2" id="editOwnerPassword" name="owner_password" autocomplete="new-password">
            </div>

            <div class="mb-3">
              <label for="editTenantPlan" class="form-label">Plan</label>
              <select class="form-select border border-1 p-2" id="editTenantPlan" name="plan_id" required>
                <option value="">Selecciona un plan</option>
                @foreach($plans as $plan)
                  <option value="{{ $plan->id }}">{{ $plan->name }} - ${{ $plan->price }}</option>
                @endforeach
              </select>
            </div>

            <div class="mb-3">
              <label for="editTenantStatus" class="form-label">Estado</label>
              <select class="form-select border border-1 p-2" id="editTenantStatus" name="is_active" required>
                <option value="1">Activo</option>
                <option value="0">Inactivo</option>
              </select>
            </div>
            <div class="d-flex flex-row-reverse">
              <button type="submit" class="btn btn-info">Guardar</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  // Llenar modal para editar Tenant
  document.querySelectorAll('.btn-edit-tenant').forEach(button => {
    button.addEventListener('click', function () {
      document.getElementById('editTenantId').value = this.dataset.id;
      document.getElementById('editTenantName').value = this.dataset.name;
      document.getElementById('editTenantSlug').value = this.dataset.slug;
      document.getElementById('editTenantEmail').value = this.dataset.email;
      document.getElementById('editTenantLogo').value = this.dataset.logo;
      document.getElementById('editOwnerName').value = this.dataset.ownerName || '';
      document.getElementById('editOwnerEmail').value = this.dataset.ownerEmail || '';
      document.getElementById('editOwnerPassword').value = '';
      document.getElementById('editTenantPlan').value = this.dataset.planId || '';
      document.getElementById('editTenantStatus').value = this.dataset.active || '1';

      // 👇 Aquí actualizamos la vista previa del logo dinámicamente
      const logoPreview = document.getElementById('editTenantLogoPreview');
      const logoPath = this.dataset.logo ? `/storage/${this.dataset.logo}` : '/assets/img/shopix5.png';
      logoPreview.src = logoPath;
    });
  });
  
  document.getElementById('editTenantLogo').addEventListener('input', function() {
    const logoPreview = document.getElementById('editTenantLogoPreview');
    const logoPath = this.value ? `/storage/${this.value}` : '/assets/img/shopix5.png';
    logoPreview.src = logoPath;
  });


  // Actualizar Tenant
  document.getElementById('editTenantForm').addEventListener('submit', function(event) {
    event.preventDefault();
    const formData = new FormData(this);
    const tenantId = formData.get('id');
    fetch(`/api/tenants/${tenantId}`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
      },
      body: formData
    })
    .then(async response => {
      const data = await response.json();
      if (!response.ok) {
        throw new Error(data.message || 'Ocurrió un error al actualizar la tienda');
      }
      return data;
    })
    .then(data => {
      alert(data.message || 'Tienda actualizada correctamente');
      window.location.reload();
    })
    .catch(error => {
      console.error('Error:', error);
      alert(error.message || 'Ocurrió un error al actualizar la tienda');
    });
  });

  // Eliminar Tenant
  document.querySelectorAll('.btn-delete-tenant').forEach(button => {
    button.addEventListener('click', function () {
      const tenantId = this.dataset.id;
      fetch(`/api/tenants/${tenantId}`, {
        method: 'DELETE',
        headers: {
          'Accept': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
        }
      })
      .then(async response => {
        const data = await response.json();
        if (!response.ok) {
          throw new Error(data.message || 'Ocurrió un error al eliminar la tienda');
        }
        return data;
      })
      .then(data => {
        alert('Tienda eliminada correctamente');
        window.location.reload();
      })
      .catch(error => {
        console.error('Error:', error);
        alert(error.message || 'Ocurrió un error al eliminar la tienda');
      });
    });
  });
</script>
@endpush
