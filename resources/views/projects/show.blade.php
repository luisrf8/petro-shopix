@extends('layouts.app')

@section('title', 'Ficha de Proyecto')

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
      <div class="bg-gradient-dark shadow-dark border-radius-lg pt-3 pb-3 d-flex justify-content-between align-items-center pe-3">
        <h6 class="text-white text-capitalize ps-3 mb-0">Ficha del Proyecto: {{ $project->name }}</h6>
        <a href="{{ route('projects.module.projects.index') }}" class="btn btn-outline-light btn-sm mb-0">Volver</a>
      </div>
    </div>

    <div class="card-body">
      <div class="row g-3 mb-4">
        <div class="col-md-3"><strong>Fase:</strong> {{ strtoupper($project->phase) }}</div>
        <div class="col-md-3"><strong>Inicio:</strong> {{ optional($project->starts_at)->format('d/m/Y') ?: '-' }}</div>
        <div class="col-md-3"><strong>Fin:</strong> {{ optional($project->ends_at)->format('d/m/Y') ?: '-' }}</div>
        <div class="col-md-3"><strong>Presupuesto:</strong> {{ number_format((float) $project->budget_amount, 2) }} {{ $project->currency_code }}</div>
        <div class="col-12"><strong>Descripción:</strong> {{ $project->description ?: 'Sin descripción' }}</div>
        <div class="col-12 text-end">
          @if($project->phase !== 'fin')
            <form method="POST" action="{{ route('projects.module.projects.complete', $project) }}" class="d-inline-block">
              @csrf
              <button type="submit" class="btn btn-success mb-0">Marcar proyecto como terminado</button>
            </form>
          @else
            <span class="badge bg-success">Proyecto terminado</span>
          @endif
        </div>
      </div>

      <div class="row g-3 mb-4">
        <div class="col-12 col-md-4">
          <div class="card border h-100">
            <div class="card-body py-3">
              <p class="text-sm text-muted mb-1">Total Pagado (Ingresos)</p>
              <h5 class="mb-0 text-success">{{ number_format((float) $totalPaid, 2) }} {{ $project->currency_code }}</h5>
            </div>
          </div>
        </div>
        <div class="col-12 col-md-4">
          <div class="card border h-100">
            <div class="card-body py-3">
              <p class="text-sm text-muted mb-1">Total Gastado (Gastos + Nómina)</p>
              <h5 class="mb-0 text-danger">{{ number_format((float) $totalSpent, 2) }} {{ $project->currency_code }}</h5>
              <p class="text-xs text-muted mb-0 mt-1">Gastos: {{ number_format((float) $totalExpenses, 2) }} | Nómina: {{ number_format((float) $totalPayrollPaid, 2) }}</p>
            </div>
          </div>
        </div>
        <div class="col-12 col-md-4">
          <div class="card border h-100">
            <div class="card-body py-3">
              <p class="text-sm text-muted mb-1">Rentabilidad</p>
              <h5 class="mb-0 {{ $profitabilityAmount >= 0 ? 'text-success' : 'text-danger' }}">{{ number_format((float) $profitabilityAmount, 2) }} {{ $project->currency_code }}</h5>
              <p class="text-xs text-muted mb-0 mt-1">{{ number_format((float) $profitabilityPercent, 2) }}%</p>
            </div>
          </div>
        </div>
      </div>

      <div class="card border mb-4">
        <div class="card-header pb-0"><h6 class="mb-0">Tareas del proyecto</h6></div>
        <div class="card-body">
          <form method="POST" action="{{ route('projects.module.projects.tasks.store', $project) }}" class="row g-3 mb-3">
            @csrf
            <div class="col-md-3"><label class="form-label">Título</label><input type="text" name="title" class="form-control border border-1 p-2" required></div>
            <div class="col-md-3"><label class="form-label">Responsable (opcional)</label><select name="responsible_team_member_id" class="form-control border border-1 p-2"><option value="">Sin responsable</option>@foreach($teamMembers as $member)<option value="{{ $member->id }}">{{ $member->full_name }}</option>@endforeach</select></div>
            <div class="col-md-2"><label class="form-label">Estado</label><select name="status" class="form-control border border-1 p-2"><option value="todo">Pendiente</option><option value="in_progress">En progreso</option><option value="done">Finalizada</option></select></div>
            <div class="col-md-2"><label class="form-label">Vence</label><input type="date" name="due_date" class="form-control border border-1 p-2"></div>
            <div class="col-md-2 d-flex align-items-end"><button type="submit" class="btn btn-dark mb-0 w-100">Agregar tarea</button></div>
            <div class="col-12"><label class="form-label">Detalle (opcional)</label><input type="text" name="description" class="form-control border border-1 p-2"></div>
          </form>

          <div class="table-responsive">
            <table class="table table-sm align-items-center mb-0">
              <thead><tr><th>Tarea</th><th>Responsable</th><th>Estado</th><th>Vence</th><th>Acción</th></tr></thead>
              <tbody>
                @forelse($project->tasks as $task)
                  <tr>
                    <td>{{ $task->title }}</td>
                    <td>{{ $task->responsibleMember->full_name ?? 'Sin responsable' }}</td>
                    <td>{{ strtoupper($task->status) }}</td>
                    <td>{{ optional($task->due_date)->format('d/m/Y') ?: '-' }}</td>
                    <td>
                      <form method="POST" action="{{ route('projects.module.projects.tasks.status', $task) }}" class="d-flex gap-2">
                        @csrf
                        <select name="status" class="form-control border border-1 p-2 form-control-sm">
                          <option value="todo" {{ $task->status === 'todo' ? 'selected' : '' }}>Pendiente</option>
                          <option value="in_progress" {{ $task->status === 'in_progress' ? 'selected' : '' }}>En progreso</option>
                          <option value="done" {{ $task->status === 'done' ? 'selected' : '' }}>Finalizada</option>
                        </select>
                        <button class="btn btn-outline-dark btn-sm mb-0" type="submit">Actualizar</button>
                      </form>
                    </td>
                  </tr>
                @empty
                  <tr><td colspan="5" class="text-center text-muted">Sin tareas registradas para este proyecto.</td></tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>

      <div class="card border mb-4">
        <div class="card-header pb-0"><h6 class="mb-0">Agregar información adicional del proyecto</h6></div>
        <div class="card-body">
          <form method="POST" action="{{ route('projects.module.projects.assets.store', $project) }}" enctype="multipart/form-data" class="row g-3">
            @csrf
            <div class="col-md-3">
              <label class="form-label">Tipo de registro</label>
              <select name="asset_type" class="form-control border border-1 p-2" required>
                <option value="reference_image">Imagen de referencia</option>
                <option value="process_image">Imagen del proceso</option>
                <option value="task_image">Imagen de tarea</option>
                <option value="final_image">Imagen finalizada</option>
                <option value="documentation">Documentación</option>
                <option value="expense">Gasto</option>
                <option value="payment">Pago</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Tarea relacionada (opcional)</label>
              <select name="task_id" class="form-control border border-1 p-2">
                <option value="">Sin tarea específica</option>
                @foreach($project->tasks as $task)
                  <option value="{{ $task->id }}">{{ $task->title }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-3"><label class="form-label">Monto (si aplica)</label><input type="number" min="0" step="0.01" name="amount" class="form-control border border-1 p-2"></div>
            <div class="col-md-3"><label class="form-label">Moneda (si aplica)</label><select name="currency_code" class="form-control border border-1 p-2"><option value="">--</option><option value="USD">USD</option><option value="EUR">EUR</option><option value="BS">BS</option></select></div>
            <div class="col-md-4"><label class="form-label">Título</label><input type="text" name="title" class="form-control border border-1 p-2" placeholder="Título/etiqueta"></div>
            <div class="col-md-4"><label class="form-label">Fecha</label><input type="date" name="happened_at" class="form-control border border-1 p-2"></div>
            <div class="col-md-4"><label class="form-label">Archivo (imagen/documento)</label><input type="file" name="asset_file" class="form-control border border-1 p-2"></div>
            <div class="col-12"><label class="form-label">Notas</label><textarea name="notes" rows="2" class="form-control border border-1 p-2"></textarea></div>
            <div class="col-12 text-end"><button type="submit" class="btn btn-dark mb-0">Guardar registro</button></div>
          </form>
        </div>
      </div>

      <div class="row g-4">
        @foreach([
          'reference_image' => 'Imágenes de referencia',
          'process_image' => 'Imágenes del proceso',
          'task_image' => 'Imágenes de tareas',
          'final_image' => 'Imágenes finalizadas',
          'documentation' => 'Documentación',
          'expense' => 'Gastos',
          'payment' => 'Pagos',
        ] as $key => $label)
          <div class="col-12 col-xl-6">
            <div class="card border h-100">
              <div class="card-header pb-0"><h6 class="mb-0">{{ $label }}</h6></div>
              <div class="card-body">
                @php $records = $assetsByType->get($key, collect()); @endphp
                @if($records->isEmpty())
                  <p class="text-muted mb-0">Sin registros.</p>
                @else
                  <div class="table-responsive">
                    <table class="table table-sm mb-0">
                      <thead><tr><th>Fecha</th><th>Título</th><th>Detalle</th><th>Archivo</th></tr></thead>
                      <tbody>
                        @foreach($records as $record)
                          <tr>
                            <td>{{ optional($record->happened_at)->format('d/m/Y') ?: '-' }}</td>
                            <td>{{ $record->title ?: '-' }}</td>
                            <td>
                              @if(!is_null($record->amount))
                                {{ number_format((float) $record->amount, 2) }} {{ $record->currency_code ?: '' }}
                              @endif
                              @if($record->task)
                                <div class="text-xs text-muted">Tarea: {{ $record->task->title }}</div>
                              @endif
                              @if($record->notes)
                                <div class="text-xs text-muted">{{ $record->notes }}</div>
                              @endif
                            </td>
                            <td>
                              @if($record->file_path)
                                @php $fileExt = strtolower(pathinfo((string) $record->file_path, PATHINFO_EXTENSION)); @endphp
                                @if(in_array($fileExt, ['jpg', 'jpeg', 'png', 'webp'], true))
                                  <div class="mb-1">
                                    <img src="{{ route('projects.module.projects.assets.file', $record) }}" alt="{{ $record->title ?: 'imagen' }}" style="max-width: 120px; max-height: 80px; object-fit: cover; border-radius: 6px;">
                                  </div>
                                @endif
                                <a href="{{ route('projects.module.projects.assets.file', $record) }}" target="_blank">Abrir</a>
                              @else
                                -
                              @endif
                            </td>
                          </tr>
                        @endforeach
                      </tbody>
                    </table>
                  </div>
                @endif
              </div>
            </div>
          </div>
        @endforeach
      </div>
    </div>
  </div>
</div>
@endsection
