@extends('layouts.app')

@section('title', 'Proyectos')

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
      <div class="bg-gradient-dark shadow-dark border-radius-lg pt-3 pb-3">
        <h6 class="text-white text-capitalize ps-3 mb-0">Módulo de Proyectos</h6>
      </div>
    </div>
    <div class="card-body">
      <div class="card border mb-4">
        <div class="card-header pb-0"><h6 class="mb-0">Crear proyecto</h6></div>
        <div class="card-body">
          <form method="POST" action="{{ route('projects.module.projects.store') }}" class="row g-3">
            @csrf
            <div class="col-md-4"><label class="form-label">Nombre</label><input type="text" name="name" class="form-control border border-1 p-2" required></div>
            <div class="col-md-2"><label class="form-label">Fase</label><select name="phase" class="form-control border border-1 p-2" required><option value="inicio">Inicio</option><option value="desarrollo">Desarrollo</option><option value="fin">Fin</option></select></div>
            <div class="col-md-3"><label class="form-label">Inicio</label><input type="date" name="starts_at" class="form-control border border-1 p-2"></div>
            <div class="col-md-3"><label class="form-label">Fin</label><input type="date" name="ends_at" class="form-control border border-1 p-2"></div>
            <div class="col-md-3"><label class="form-label">Presupuesto</label><input type="number" name="budget_amount" min="0" step="0.01" class="form-control border border-1 p-2"></div>
            <div class="col-md-2"><label class="form-label">Moneda</label><select name="currency_code" class="form-control border border-1 p-2"><option value="USD">USD</option><option value="EUR">EUR</option><option value="BS">BS</option></select></div>
            <div class="col-md-7"><label class="form-label">Cotización origen (opcional)</label><select name="quotation_id" class="form-control border border-1 p-2"><option value="">Sin cotización asociada</option>@foreach($quotations as $quotation)<option value="{{ $quotation->id }}">#{{ $quotation->id }} - {{ $quotation->title }}</option>@endforeach</select></div>
            <div class="col-12"><label class="form-label">Descripción</label><textarea name="description" rows="2" class="form-control border border-1 p-2"></textarea></div>
            <div class="col-12"><label class="form-label">Notas</label><textarea name="notes" rows="2" class="form-control border border-1 p-2"></textarea></div>
            <div class="col-12 text-end"><button class="btn btn-dark mb-0" type="submit">Guardar proyecto</button></div>
          </form>
        </div>
      </div>

      <div class="row g-4">
        @forelse($projects as $project)
          <div class="col-12">
            <div class="card border">
              <div class="card-body">
                <div class="d-flex justify-content-between align-items-start flex-wrap gap-2 mb-3">
                  <div>
                    <h6 class="mb-1">{{ $project->name }}</h6>
                    <p class="text-sm mb-0">{{ $project->description ?: 'Sin descripción' }}</p>
                    <p class="text-xs text-muted mb-0">Cotización: {{ $project->quotation ? '#' . $project->quotation->id : 'N/A' }}</p>
                    <a href="{{ route('projects.module.projects.show', $project) }}" class="btn btn-outline-primary btn-sm mt-2 mb-0">Ver ficha del proyecto</a>
                  </div>
                  <form method="POST" action="{{ route('projects.module.projects.phase', $project) }}" class="d-flex gap-2 align-items-center">
                    @csrf
                    <select name="phase" class="form-control border border-1 p-2 form-control-sm">
                      <option value="inicio" {{ $project->phase === 'inicio' ? 'selected' : '' }}>Inicio</option>
                      <option value="desarrollo" {{ $project->phase === 'desarrollo' ? 'selected' : '' }}>Desarrollo</option>
                      <option value="fin" {{ $project->phase === 'fin' ? 'selected' : '' }}>Fin</option>
                    </select>
                    <button class="btn btn-outline-dark btn-sm mb-0" type="submit">Actualizar fase</button>
                  </form>
                </div>

                <div class="row g-3">
                  <div class="col-12 col-xl-6">
                    <h6 class="text-sm mb-2">Tareas</h6>
                    <form method="POST" action="{{ route('projects.module.projects.tasks.store', $project) }}" class="row g-2 mb-2">
                      @csrf
                      <div class="col-md-4"><input type="text" name="title" class="form-control border border-1 p-2 form-control-sm" placeholder="Título de tarea" required></div>
                      <div class="col-md-3"><select name="responsible_team_member_id" class="form-control border border-1 p-2 form-control-sm"><option value="">Sin responsable</option>@foreach($teamMembers as $member)<option value="{{ $member->id }}">{{ $member->full_name }}</option>@endforeach</select></div>
                      <div class="col-md-2"><select name="status" class="form-control border border-1 p-2 form-control-sm"><option value="todo">Pendiente</option><option value="in_progress">En progreso</option><option value="done">Finalizada</option></select></div>
                      <div class="col-md-2"><input type="date" name="due_date" class="form-control border border-1 p-2 form-control-sm"></div>
                      <div class="col-md-1"><button class="btn btn-dark btn-sm mb-0 w-100" type="submit">+</button></div>
                      <div class="col-12"><input type="text" name="description" class="form-control border border-1 p-2 form-control-sm" placeholder="Detalle (opcional)"></div>
                    </form>

                    <div class="table-responsive">
                      <table class="table table-sm mb-0">
                        <thead><tr><th>Tarea</th><th>Responsable</th><th>Estado</th><th>Vence</th><th>Acción</th></tr></thead>
                        <tbody>
                          @forelse($project->tasks as $task)
                            <tr>
                              <td>{{ $task->title }}</td>
                              <td>{{ $task->responsibleMember->full_name ?? 'Sin responsable' }}</td>
                              <td>{{ $task->status }}</td>
                              <td>{{ optional($task->due_date)->format('d/m/Y') ?: '-' }}</td>
                              <td>
                                <form method="POST" action="{{ route('projects.module.projects.tasks.status', $task) }}" class="d-flex gap-2">
                                  @csrf
                                  <select name="status" class="form-control border border-1 p-2 form-control-sm">
                                    <option value="todo" {{ $task->status === 'todo' ? 'selected' : '' }}>Pendiente</option>
                                    <option value="in_progress" {{ $task->status === 'in_progress' ? 'selected' : '' }}>En progreso</option>
                                    <option value="done" {{ $task->status === 'done' ? 'selected' : '' }}>Finalizada</option>
                                  </select>
                                  <button type="submit" class="btn btn-outline-dark btn-sm mb-0">OK</button>
                                </form>
                              </td>
                            </tr>
                          @empty
                            <tr><td colspan="5" class="text-muted text-center">Sin tareas.</td></tr>
                          @endforelse
                        </tbody>
                      </table>
                    </div>
                  </div>

                  <div class="col-12 col-xl-6">
                    <h6 class="text-sm mb-2">Equipo y comisiones</h6>
                    <form method="POST" action="{{ route('projects.module.projects.assignments.store', $project) }}" class="row g-2 mb-2">
                      @csrf
                      <div class="col-md-3">
                        <select name="team_member_id" class="form-control border border-1 p-2 form-control-sm" required>
                          <option value="">Selecciona integrante</option>
                          @foreach($teamMembers as $member)
                            <option value="{{ $member->id }}">{{ $member->full_name }}</option>
                          @endforeach
                        </select>
                      </div>
                      <div class="col-md-2"><select name="commission_type" class="form-control border border-1 p-2 form-control-sm"><option value="none">Sin comisión</option><option value="percent">% comisión</option><option value="fixed">Comisión fija</option></select></div>
                      <div class="col-md-2"><input type="number" min="0" step="0.01" name="commission_value" class="form-control border border-1 p-2 form-control-sm" placeholder="Comisión"></div>
                      <div class="col-md-2"><input type="number" min="0" step="0.01" name="pay_amount" class="form-control border border-1 p-2 form-control-sm" placeholder="Monto a pagar"></div>
                      <div class="col-md-2"><select name="pay_currency_code" class="form-control border border-1 p-2 form-control-sm"><option value="USD">USD</option><option value="EUR">EUR</option><option value="BS">BS</option></select></div>
                      <div class="col-md-2"><input type="number" min="0" max="100" step="0.01" name="project_share_percent" class="form-control border border-1 p-2 form-control-sm" placeholder="% proyecto"></div>
                      <div class="col-md-2"><select name="member_status" class="form-control border border-1 p-2 form-control-sm"><option value="active">Activo</option><option value="inactive">Inactivo</option><option value="terminated">Terminado</option><option value="paid">Pagado</option><option value="pending">Pendiente</option></select></div>
                      <div class="col-md-1"><button class="btn btn-dark btn-sm mb-0 w-100" type="submit">OK</button></div>
                      <div class="col-12"><input type="text" name="notes" class="form-control border border-1 p-2 form-control-sm" placeholder="Notas de comisión"></div>
                    </form>

                    <div class="table-responsive">
                      <table class="table table-sm mb-0">
                        <thead><tr><th>Integrante</th><th>Comisión</th><th>Monto pago</th><th>% proyecto</th><th>Estado</th></tr></thead>
                        <tbody>
                          @forelse($project->assignments as $assignment)
                            <tr>
                              <td>
                                <div>{{ $assignment->teamMember->full_name ?? '-' }}</div>
                                <small class="text-muted d-block mt-1">Estado integrante: {{ strtoupper($assignment->member_status ?? 'ACTIVE') }}</small>
                              </td>
                              <td>{{ $assignment->commission_type }} {{ number_format((float) $assignment->commission_value, 2) }}</td>
                              <td>{{ number_format((float) $assignment->pay_amount, 2) }} {{ $assignment->pay_currency_code }}</td>
                              <td>{{ number_format((float) $assignment->project_share_percent, 2) }}%</td>
                              <td>{{ $assignment->is_active ? 'Activo' : 'Inactivo' }}</td>
                            </tr>
                          @empty
                            <tr><td colspan="5" class="text-muted text-center">Sin integrantes asignados.</td></tr>
                          @endforelse
                        </tbody>
                      </table>
                    </div>
                  </div>
                </div>
              </div>
            </div>
          </div>
        @empty
          <div class="col-12"><div class="alert alert-info mb-0">No hay proyectos registrados.</div></div>
        @endforelse
      </div>
    </div>
  </div>
</div>
@endsection
