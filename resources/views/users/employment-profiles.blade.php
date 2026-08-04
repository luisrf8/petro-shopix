@extends('layouts.app')

@section('title', 'Ficha laboral de usuarios')

@section('content')
<div class="container-fluid py-2">
  @if(session('success'))
    <div class="alert alert-success text-white bg-gradient-success" role="alert">{{ session('success') }}</div>
  @endif

  @if($errors->any())
    <div class="alert alert-danger text-white bg-gradient-danger" role="alert">
      <ul class="mb-0">
        @foreach($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="card my-4">
    <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
      <div class="bg-gradient-dark shadow-dark border-radius-lg pt-3 pb-3 d-flex justify-content-between align-items-center">
        <h6 class="text-white text-capitalize ps-3 mb-0">Módulo de ficha laboral</h6>
        <a href="{{ route('users') }}" class="btn btn-outline-light btn-sm mb-0 me-3">Volver a usuarios</a>
      </div>
    </div>
    <div class="card-body">
      <p class="text-sm text-muted mb-3">Gestiona contrato, carga familiar, fecha de contratación, edad y fecha de nacimiento por usuario.</p>

      <div class="table-responsive">
        <table class="table table-sm align-items-center mb-0">
          <thead>
            <tr>
              <th>Usuario</th>
              <th>sede</th>
              <th>Rol</th>
              <th>Contratación</th>
              <th>Nacimiento / Edad</th>
              <th>Carga familiar</th>
              <th>Contrato</th>
              <th>Acción</th>
            </tr>
          </thead>
          <tbody>
            @forelse($users as $user)
              @php
                $profile = $user->employmentProfile;
                $effectiveAge = $profile?->age;
                if (!$effectiveAge && $profile?->birth_date) {
                  $effectiveAge = \Carbon\Carbon::parse((string) $profile->birth_date)->age;
                }
              @endphp
              <tr>
                <td>{{ $user->name }}<br><small class="text-muted">{{ $user->email }}</small></td>
                <td>{{ $user->tenant->name ?? 'Sin sede' }}</td>
                <td>{{ $user->role->name ?? '-' }}</td>
                <td>{{ $profile?->hired_at?->format('d/m/Y') ?? '-' }}</td>
                <td>
                  {{ $profile?->birth_date?->format('d/m/Y') ?? '-' }}
                  @if($effectiveAge)
                    <br><small class="text-muted">{{ $effectiveAge }} años</small>
                  @endif
                </td>
                <td>{{ (int) ($profile?->family_dependents ?? 0) }}</td>
                <td>
                  @if(!empty($profile?->contract_file_path))
                    <a href="{{ asset('storage/' . $profile->contract_file_path) }}" target="_blank" class="btn btn-outline-dark btn-sm mb-0">Ver archivo</a>
                  @else
                    <span class="text-muted">Sin archivo</span>
                  @endif
                </td>
                <td>
                  <button
                    type="button"
                    class="btn btn-dark btn-sm mb-0 open-profile-modal"
                    data-bs-toggle="modal"
                    data-bs-target="#employmentProfileModal"
                    data-user-id="{{ $user->id }}"
                    data-user-name="{{ $user->name }}"
                    data-employment-type="{{ $profile?->employment_type ?? '' }}"
                    data-family-dependents="{{ (int) ($profile?->family_dependents ?? 0) }}"
                    data-hired-at="{{ $profile?->hired_at?->format('Y-m-d') ?? '' }}"
                    data-birth-date="{{ $profile?->birth_date?->format('Y-m-d') ?? '' }}"
                    data-age="{{ $profile?->age ?? '' }}"
                    data-notes="{{ $profile?->notes ?? '' }}">
                    Editar ficha
                  </button>
                </td>
              </tr>
            @empty
              <tr><td colspan="8" class="text-center text-muted">No hay usuarios para gestionar.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>

      <div class="mt-3 d-flex justify-content-center">
        {{ $users->links() }}
      </div>
    </div>
  </div>
</div>

<div class="modal fade" id="employmentProfileModal" tabindex="-1" aria-labelledby="employmentProfileModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-lg modal-dialog-scrollable">
    <div class="modal-content">
      <div class="modal-header">
        <h5 class="modal-title" id="employmentProfileModalLabel">Editar ficha laboral</h5>
        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
      </div>
      <form id="employmentProfileForm" method="POST" enctype="multipart/form-data">
        @csrf
        <div class="modal-body">
          <p class="mb-3" id="employmentProfileUserLabel"></p>

          <div class="row g-3">
            <div class="col-md-4">
              <label class="form-label">Tipo de vínculo</label>
              <select name="employment_type" id="employmentType" class="form-control border border-1 p-2">
                <option value="">No definido</option>
                <option value="fixed_term">Plazo fijo</option>
                <option value="indefinite">Indefinido</option>
                <option value="service">Prestación de servicio</option>
                <option value="internship">Pasantía</option>
              </select>
            </div>
            <div class="col-md-4">
              <label class="form-label">Fecha de contratación</label>
              <input type="date" name="hired_at" id="hiredAt" class="form-control border border-1 p-2">
            </div>
            <div class="col-md-4">
              <label class="form-label">Carga familiar</label>
              <input type="number" min="0" max="50" name="family_dependents" id="familyDependents" class="form-control border border-1 p-2">
            </div>
            <div class="col-md-6">
              <label class="form-label">Fecha de nacimiento</label>
              <input type="date" name="birth_date" id="birthDate" class="form-control border border-1 p-2">
            </div>
            <div class="col-md-6">
              <label class="form-label">Edad (opcional)</label>
              <input type="number" min="0" max="120" name="age" id="age" class="form-control border border-1 p-2" placeholder="Se calcula automáticamente si dejas vacío">
            </div>
            <div class="col-12">
              <label class="form-label">Contrato (archivo)</label>
              <input type="file" name="contract_file" class="form-control border border-1 p-2" accept=".pdf,.doc,.docx,.png,.jpg,.jpeg,.webp">
            </div>
            <div class="col-12">
              <label class="form-label">Notas</label>
              <textarea name="notes" id="notes" rows="3" class="form-control border border-1 p-2"></textarea>
            </div>
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-outline-secondary mb-0" data-bs-dismiss="modal">Cancelar</button>
          <button type="submit" class="btn btn-dark mb-0">Guardar ficha</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  (() => {
    const form = document.getElementById('employmentProfileForm');
    const userLabel = document.getElementById('employmentProfileUserLabel');
    const employmentType = document.getElementById('employmentType');
    const familyDependents = document.getElementById('familyDependents');
    const hiredAt = document.getElementById('hiredAt');
    const birthDate = document.getElementById('birthDate');
    const age = document.getElementById('age');
    const notes = document.getElementById('notes');

    document.querySelectorAll('.open-profile-modal').forEach((button) => {
      button.addEventListener('click', () => {
        const userId = button.getAttribute('data-user-id') || '';
        const userName = button.getAttribute('data-user-name') || '';

        form.setAttribute('action', `/users/${userId}/employment-profile`);
        userLabel.textContent = `Usuario: ${userName}`;

        employmentType.value = button.getAttribute('data-employment-type') || '';
        familyDependents.value = button.getAttribute('data-family-dependents') || 0;
        hiredAt.value = button.getAttribute('data-hired-at') || '';
        birthDate.value = button.getAttribute('data-birth-date') || '';
        age.value = button.getAttribute('data-age') || '';
        notes.value = button.getAttribute('data-notes') || '';
      });
    });
  })();
</script>
@endpush
