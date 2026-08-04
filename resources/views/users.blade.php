<style>
  .bg-lighter {
    background-color: #f6f6f6;
  }

  .users-table-wrap {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
  }

  .users-table {
    min-width: 920px;
  }

  .tenant-chip {
    display: inline-flex;
    align-items: center;
    padding: 0.3rem 0.7rem;
    border-radius: 999px;
    background: rgba(15, 23, 42, 0.06);
    color: #334155;
    font-size: 0.75rem;
    font-weight: 700;
  }
</style>
@extends('layouts.app')

@section('title', 'Usuarios')

@section('content')
<div class="container-fluid py-2">
  <div class="row">
    <div class="col-12">
      <div class="card my-4">
        <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
          <div class="bg-gradient-dark shadow-dark border-radius-lg pt-4 pb-3 d-flex justify-content-between align-items-center">
            <h6 class="text-white text-capitalize ps-3">USUARIOS</h6>
            <div class="py-1 px-3 text-end d-flex align-items-center gap-2">
              <a href="{{ route('users.employmentProfiles.index') }}" class="btn btn-outline-light btn-sm mb-0">Ficha laboral</a>
              <label class="text-white admin-mobile-action-trigger" data-bs-toggle="modal" data-bs-target="#createUserModal">+ Crear Usuario</label>
            </div>
          </div>
        </div>
        <div class="card-body px-0 pb-2">
          <div class="mx-3 mb-3">
            <input type="text" id="searchUser" class="form-control border border-1 p-2" placeholder="Buscar usuario, correo o sede...">
          </div>
          <div class="users-table-wrap m-3 mt-0">
            <table class="table table-striped text-center users-table mb-0">
              <thead>
                <tr>
                  <th>Nombre</th>
                  <th>Correo Electrónico</th>
                  <th>sede</th>
                  <th>Rol</th>
                  <th>Estado</th>
                  <th>Editar</th>
                  <th>Activar / Inactivar</th>
                </tr>
              </thead>
              <tbody id="userTableBody">
                @foreach($users as $user)
                  <tr>
                    <td>{{ $user->name }}</td>
                    <td>{{ $user->email }}</td>
                    <td>
                      <span class="tenant-chip">{{ $user->tenant->name ?? 'Sin sede' }}</span>
                    </td>
                    <td>{{ $user->role->name }}</td>
                    <td>
                      <span class="badge badge-sm {{ $user->is_active ? 'bg-gradient-success' : 'bg-gradient-secondary' }}">{{ $user->is_active ? 'Activo' : 'Inactivo' }}</span>
                    </td>
                    <td>
                      <a class="text-secondary font-weight-bold text-xs btn-edit-user d-flex align-items-center justify-content-center admin-mobile-action-trigger"
                        data-bs-toggle="modal"
                        data-bs-target="#editUserModal"
                        data-user-id="{{ $user->id }}"
                        data-name="{{ $user->name }}"
                        data-email="{{ $user->email }}"
                        data-role="{{ $user->role->id }}"
                        data-tenant-id="{{ $user->tenant_id }}">
                        Editar
                      </a>
                    </td>
                    <td>
                      <a class="text-secondary font-weight-bold text-xs toggle-status-btn admin-mobile-action-trigger"
                        data-id="{{ $user->id }}"
                        data-status="{{ $user->is_active ? 'active' : 'inactive' }}">
                        {{ $user->is_active ? 'Inactivar' : 'Activar' }}
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

  <div class="d-flex justify-content-center">
    {{ $users->links() }}
  </div>

  <div class="modal fade" id="createUserModal" tabindex="-1" aria-labelledby="createUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="createUserModalLabel">Crear Usuario</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="createUserForm" enctype="multipart/form-data">
            @csrf
            <div class="mb-3">
              <label for="userName" class="form-label">Nombre</label>
              <input type="text" class="form-control border border-1 p-2" id="userName" name="name" placeholder="Ingrese el nombre del usuario" required>
            </div>
            <div class="mb-3">
              <label for="userEmail" class="form-label">Correo Electrónico</label>
              <input type="email" class="form-control border border-1 p-2" id="userEmail" name="email" placeholder="Ingrese el correo electrónico" required>
            </div>
            <div class="mb-3">
              <label for="userTenantSelector" class="form-label">sede asignada</label>
              <select id="userTenantSelector" name="tenant_id" class="form-control border border-1 p-2">
                <option value="">Sin sede</option>
                @foreach($tenants as $tenant)
                  <option value="{{ $tenant->id }}">{{ $tenant->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="mb-3">
              <label for="userPhoneNumber" class="form-label">Teléfono</label>
              <input type="text" class="form-control border border-1 p-2" id="userPhoneNumber" name="phone_number" placeholder="Ingrese el teléfono" required>
            </div>
            <div class="mb-3">
              <label for="userDni" class="form-label">DNI</label>
              <input type="text" class="form-control border border-1 p-2" id="userDni" name="dni" placeholder="Ingrese el DNI" required>
            </div>
            <div class="mb-3">
              <label for="userPassword" class="form-label">Contraseña</label>
              <input type="password" class="form-control border border-1 p-2" id="userPassword" name="password" placeholder="Ingrese la contraseña" required>
            </div>
            <div class="mb-3">
              <label for="roleSelector" class="form-label">Rol</label>
              <select id="roleSelector" name="role_id" class="form-control border border-1 p-2" required>
                <option value="">Seleccione un rol</option>
                @foreach($roles as $role)
                  <option value="{{ $role->id }}">{{ $role->name }}</option>
                @endforeach
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

  <div class="modal fade" id="editUserModal" tabindex="-1" aria-labelledby="editUserModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="editUserModalLabel">Editar Usuario</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="editUserForm" class="text-start" enctype="multipart/form-data">
            @csrf
            <input type="hidden" id="editUserId" name="id">
            <div class="mb-3">
              <label for="editUserName" class="form-label">Nombre</label>
              <input type="text" class="form-control border border-1 p-2" id="editUserName" name="name" required>
            </div>
            <div class="mb-3">
              <label for="editUserEmail" class="form-label">Correo Electrónico</label>
              <input type="email" class="form-control border border-1 p-2" id="editUserEmail" name="email" placeholder="Ingrese el correo electrónico" required>
            </div>
            <div class="mb-3">
              <label for="editUserTenantSelector" class="form-label">sede asignada</label>
              <select id="editUserTenantSelector" name="tenant_id" class="form-control border border-1 p-2">
                <option value="">Sin sede</option>
                @foreach($tenants as $tenant)
                  <option value="{{ $tenant->id }}">{{ $tenant->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="mb-3">
              <label for="editUserRoleSelector" class="form-label">Rol</label>
              <select id="editUserRoleSelector" name="role_id" class="form-control border border-1 p-2" required>
                <option value="">Seleccione un rol</option>
                @foreach($roles as $role)
                  <option value="{{ $role->id }}">{{ $role->name }}</option>
                @endforeach
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
<script src="{{ asset('assets/js/core/popper.min.js') }}"></script>
<script src="{{ asset('assets/js/core/bootstrap.min.js') }}"></script>

<!-- Github buttons -->
<!-- <script async defer src="https://buttons.github.io/buttons.js"></script> -->

<!-- Control Center for Material Dashboard: parallax effects, scripts for the example pages etc -->
  <script>
    function parseJsonResponse(response) {
      return response.text().then(text => {
        let data = {};

        if (text) {
          try {
            data = JSON.parse(text);
          } catch (error) {
            data = { message: text };
          }
        }

        if (!response.ok) {
          const validationMessage = data.errors
            ? Object.values(data.errors).flat().join('\n')
            : null;
          throw new Error(validationMessage || data.message || 'La solicitud no pudo procesarse.');
        }

        return data;
      });
    }

    document.getElementById('createUserForm').addEventListener('submit', function(event) {
      event.preventDefault();

      let formData = new FormData(this);

      fetch('/api/create-user', {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
        },
        body: formData
      })
      .then(parseJsonResponse)
      .then(() => {
        alert('Usuario creado correctamente');
        window.location.reload();
      })
      .catch(error => {
        console.error('Error:', error);
        alert(error.message || 'Ocurrió un error al crear el usuario');
      });
    });

    document.querySelectorAll('.btn-edit-user').forEach(button => {
      button.addEventListener('click', function () {
        const userId = this.getAttribute('data-user-id');
        const userName = this.getAttribute('data-name');
        const userEmail = this.getAttribute('data-email');
        const userRoleId = this.getAttribute('data-role');
        const userTenantId = this.getAttribute('data-tenant-id');

        document.getElementById('editUserId').value = userId;
        document.getElementById('editUserName').value = userName;
        document.getElementById('editUserEmail').value = userEmail;
        document.getElementById('editUserRoleSelector').value = userRoleId;
        document.getElementById('editUserTenantSelector').value = userTenantId || '';
      });
    });

    document.getElementById('editUserForm').addEventListener('submit', function (event) {
      event.preventDefault();

      const formData = new FormData(this);
      const userId = formData.get('id');

      fetch(`/api/user/${userId}`, {
        method: 'POST',
        headers: {
          'Accept': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
        },
        body: formData
      })
      .then(parseJsonResponse)
      .then(() => {
        alert('Usuario actualizado correctamente');
        window.location.reload();
      })
      .catch(error => {
        console.error('Error:', error);
        alert(error.message || 'Ocurrió un error al actualizar Usuario');
      });
    });

    document.querySelectorAll('.toggle-status-btn').forEach(button => {
      button.addEventListener('click', function () {
        const userId = this.getAttribute('data-id');
        const currentStatus = this.getAttribute('data-status');
        const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
        const reason = newStatus === 'inactive' ? window.shopixRequestActionReason('Indica el motivo para inactivar este usuario.') : '';
        if (newStatus === 'inactive' && !reason) {
          return;
        }

        fetch(`/api/users/${userId}/toggle-status`, {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
            'Accept': 'application/json',
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({ is_active: newStatus === 'active' ? 1 : 0, action_reason: reason })
        })
        .then(parseJsonResponse)
        .then(() => {
          alert('Usuario actualizado correctamente');
          window.location.reload();
        })
        .catch(error => {
          console.error('Error:', error);
          alert(error.message || 'Ocurrió un error al actualizar Usuario');
        });
      })
      });

    document.getElementById('searchUser').addEventListener('input', function () {
      const searchValue = this.value.toLowerCase();
      const rows = document.querySelectorAll('#userTableBody tr');

      rows.forEach(row => {
          const name = row.cells[0].textContent.toLowerCase();
          const email = row.cells[1].textContent.toLowerCase();
          const tenant = row.cells[2].textContent.toLowerCase();
          if (name.includes(searchValue) || email.includes(searchValue) || tenant.includes(searchValue)) {
              row.style.display = '';
          } else {
              row.style.display = 'none';
          }
      });
    });
  </script>
@endpush