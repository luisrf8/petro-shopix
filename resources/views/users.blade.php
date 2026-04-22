<style>
.bg-lighter {
    background-color: #f6f6f6; /* Elige un color más claro */
}
</style>
@extends('layouts.app')

@section('title', 'Categorías')

@section('content')
    <div class="container-fluid py-2">
      <!-- Buscador -->
      <!-- Tabla de usuarios -->
      <div class="row">
        <div class="col-12">
          <div class="card my-4">
            <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
              <div class="bg-gradient-dark shadow-dark border-radius-lg pt-4 pb-3 d-flex justify-content-between align-items-center">
                <h6 class="text-white text-capitalize ps-3">USUARIOS</h6>
                <div class="py-1 px-3 text-end " data-bs-toggle="modal" data-bs-target="#createCategoryModal">
                  <label class="text-white admin-mobile-action-trigger">
                    + Crear Usuario
                  </label>
                </div>
              </div>
            </div>
            <div class="card-body px-0 pb-2">
              <div class="mx-3">
                <input type="text" id="searchUser" class="form-control border border-1 p-2" placeholder="Buscar usuario...">
              </div>
              <div class="table-responsive m-3">
                <table class="table table-striped text-center">
                  <thead>
                    <tr>
                      <th>Nombre</th>
                      <th>Correo Electrónico</th>
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
                        <td>{{ $user->role->name }}</td>
                        <td class="text-center">
                          <span class="badge badge-sm  {{ $user->is_active ? 'bg-gradient-success' : 'bg-gradient-secondary' }}">{{ $user->is_active ? 'Activo' : 'Inactivo' }}
                          </span>
                        </td>
                        <td>
                          <a class="text-secondary font-weight-bold text-xs btn-edit-user d-flex align-items-center justify-content-center admin-mobile-action-trigger" 
                            data-bs-toggle="modal" 
                            data-bs-target="#editUserModal" 
                            data-user-id="{{ $user->id }}"
                            data-name="{{ $user->name }}"
                            data-email="{{ $user->email }}"
                            data-role="{{ $user->role->id }}">
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
                                            <label for="editUserRoleSelector" class="form-label">Rol</label>
                                            <select id="editUserRoleSelector" name="role_id" class="form-control border border-1 p-2" required>
                                              <option value="">Seleccione un rol</option>
                                              <!-- Aquí se cargarán los roles dinámicamente -->
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
                    @endforeach
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
      <!-- Paginador -->
      <div class="d-flex justify-content-center">
        {{ $users->links() }}
      </div>

      <!-- Modal para crear usuario -->
      <div class="modal fade" id="createCategoryModal" tabindex="-1" aria-labelledby="createCategoryModalLabel" aria-hidden="true">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="createCategoryModalLabel">Crear Usuario</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
              <!-- Formulario para crear el Usuario -->
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
              <!-- Aquí se cargarán los roles dinámicamente -->
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
    document.getElementById('createUserForm').addEventListener('submit', function(event) {
      event.preventDefault(); // Evita el envío normal del formulario

      let formData = new FormData(this); // Crear un FormData con los datos del formulario

      fetch('api/create-user', {
        method: 'POST',
        headers: {
          'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
        },
        body: formData
      })
      .then(response => {
            if (response.status === 201) { // Valida el código de estado HTTP
              alert('Usuario creado correctamente');
              window.location.reload();
            } else {
              throw new Error('Error al crear');
            }
          })
      .catch(error => console.error('Error:', error));
    });
    document.querySelectorAll('.btn-edit-user').forEach(button => {
      button.addEventListener('click', function () {
        const userId = this.getAttribute('data-user-id');
        const userName = this.getAttribute('data-name');
        const userEmail = this.getAttribute('data-email');
        const userRoleId = this.getAttribute('data-role');

        document.getElementById('editUserId').value = userId;
        document.getElementById('editUserName').value = userName;
        document.getElementById('editUserEmail').value = userEmail;
        document.getElementById('editUserRoleSelector').value = userRoleId;
      });
    });
        // Enviar la actualización al servidor
    document.getElementById('editUserForm').addEventListener('submit', function (event) {
      event.preventDefault(); // Evita el envío normal del formulario

      const formData = new FormData(this);
      const userId = formData.get('id');

      fetch(`api/user/${userId}`, {
        method: 'POST', // Usa 'PUT' si tu API lo requiere
        headers: {
          'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
        },
        body: formData
      })
      .then(response => {
        if (response.status === 200) { // Valida el código de estado HTTP
          alert('Usuario actualizado correctamente');
          window.location.reload();
        } else {
          throw new Error('Error al actualizar Usuario');
        }
      })
      .catch(error => {
        console.error('Error:', error);
        alert('Ocurrió un error al actualizar Usuario');
      });
    });
    document.querySelectorAll('.toggle-status-btn').forEach(button => {
      button.addEventListener('click', function () {
        console.log("hola")
        const userId = this.getAttribute('data-id');
        const currentStatus = this.getAttribute('data-status');
        // Alternar el estado
        const newStatus = currentStatus === 'active' ? 'inactive' : 'active';
        const reason = newStatus === 'inactive' ? window.shopixRequestActionReason('Indica el motivo para inactivar este usuario.') : '';
        if (newStatus === 'inactive' && !reason) {
          return;
        }

        // Hacer la petición AJAX para cambiar el estado
        fetch(`api/users/${userId}/toggle-status`, {
          method: 'POST',
          headers: {
            'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value,
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({ is_active: newStatus === 'active' ? 1 : 0, action_reason: reason })
        })
        .then(response => {
          if (response.status === 200) { // Valida el código de estado HTTP
            alert('Usuario actualizado correctamente');
            window.location.reload();
          } else {
            throw new Error('Error al actualizar Usuario');
          }
        })
        .catch(error => {
          console.error('Error:', error);
          alert('Ocurrió un error al actualizar Usuario');
        });
        })
      });
    document.getElementById('searchUser').addEventListener('input', function () {
      const searchValue = this.value.toLowerCase();
      const rows = document.querySelectorAll('#userTableBody tr');

      rows.forEach(row => {
          const name = row.cells[0].textContent.toLowerCase();
          const email = row.cells[1].textContent.toLowerCase();
          if (name.includes(searchValue) || email.includes(searchValue)) {
              row.style.display = ''; // Mostrar fila
          } else {
              row.style.display = 'none'; // Ocultar fila
          }
      });
    });
  </script>
@endpush