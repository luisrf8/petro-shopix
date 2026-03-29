@extends('layouts.app')

@section('content')
<div class="container-fluid py-2">

  <div class="card my-4">
    <div class="card-header p-3">
      <h6 class="mb-0">Filtros de auditoría</h6>
    </div>
    <div class="card-body p-3">
      <form method="GET" action="{{ route('logs.index') }}" class="row g-2">
        <div class="col-12 col-md-2">
          <input type="text" class="form-control border border-1 p-2" name="user_id" placeholder="Usuario ID" value="{{ $filters['user_id'] ?? '' }}">
        </div>
        <div class="col-12 col-md-2">
          <input type="text" class="form-control border border-1 p-2" name="tenant_id" placeholder="Tienda ID" value="{{ $filters['tenant_id'] ?? '' }}">
        </div>
        <div class="col-12 col-md-2">
          <select class="form-select border border-1 p-2" name="role">
            <option value="">Rol (todos)</option>
            @foreach(($filterOptions['roles'] ?? collect()) as $role)
              <option value="{{ $role }}" {{ ($filters['role'] ?? '') === $role ? 'selected' : '' }}>{{ $role }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-12 col-md-2">
          <select class="form-select border border-1 p-2" name="module">
            <option value="">Funcionalidad (todas)</option>
            @foreach(($filterOptions['modules'] ?? collect()) as $module)
              <option value="{{ $module }}" {{ ($filters['module'] ?? '') === $module ? 'selected' : '' }}>{{ $module }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-12 col-md-2">
          <select class="form-select border border-1 p-2" name="action">
            <option value="">Acción (todas)</option>
            @foreach(($filterOptions['actions'] ?? collect()) as $action)
              <option value="{{ $action }}" {{ ($filters['action'] ?? '') === $action ? 'selected' : '' }}>{{ $action }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-12 col-md-2">
          <select class="form-select border border-1 p-2" name="status">
            <option value="">Estado (todos)</option>
            @foreach(($filterOptions['statuses'] ?? collect()) as $status)
              <option value="{{ $status }}" {{ (string) ($filters['status'] ?? '') === (string) $status ? 'selected' : '' }}>{{ $status }}</option>
            @endforeach
          </select>
        </div>
        <div class="col-12 col-md-2">
          <input type="date" class="form-control border border-1 p-2" name="date_from" value="{{ $filters['date_from'] ?? '' }}">
        </div>
        <div class="col-12 col-md-2">
          <input type="date" class="form-control border border-1 p-2" name="date_to" value="{{ $filters['date_to'] ?? '' }}">
        </div>
        <div class="col-12 col-md-6">
          <input type="text" class="form-control border border-1 p-2" name="q" placeholder="Buscar por texto libre (ruta, mensaje, payload, etc.)" value="{{ $filters['q'] ?? '' }}">
        </div>
        <div class="col-12 col-md-2 d-flex gap-2">
          <button type="submit" class="btn btn-dark mb-0 w-100">Filtrar</button>
        </div>
        <div class="col-12 col-md-2 d-flex gap-2">
          <a href="{{ route('logs.index') }}" class="btn btn-outline-secondary mb-0 w-100">Limpiar</a>
        </div>
      </form>
    </div>
  </div>

  {{-- ================ TABLA ================ --}}
  <div class="card my-4">
    <div class="card-header bg-gradient-dark text-white d-flex justify-content-between align-items-center p-3">
      <h6 class="m-0 text-white">LOGS</h6>
    </div>

    <div class="card-body px-0 pb-2">
      <div class="table-responsive">
        <table class="table text-center">
          <thead>
            <tr>
              <th class="border border-1 p-2">ID</th>
              <th class="border border-1 p-2">Fecha</th>
              <th class="border border-1 p-2">Usuario</th>
              <th class="border border-1 p-2">Tienda</th>
              <th class="border border-1 p-2">Rol</th>
              <th class="border border-1 p-2">Funcionalidad</th>
              <th class="border border-1 p-2">Acción</th>
              <th class="border border-1 p-2">Ruta</th>
              <th class="border border-1 p-2">Estado</th>
              <th class="border border-1 p-2">Descripción</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($logs as $log)
              @php
                $payload = null;
                if (is_string($log->description) && \Illuminate\Support\Str::startsWith(trim($log->description), ['{', '['])) {
                  $decoded = json_decode($log->description, true);
                  if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                    $payload = $decoded;
                  }
                }
              @endphp
              <tr>
                <td class="border border-1 p-2">{{ $log->id }}</td>
                <td class="border border-1 p-2">{{ optional($log->created_at)->format('Y-m-d H:i:s') }}</td>
                <td class="border border-1 p-2">{{ $log->user_id }}</td>
                <td class="border border-1 p-2">{{ $payload['tenant_id'] ?? '-' }}</td>
                <td class="border border-1 p-2">{{ $payload['role'] ?? '-' }}</td>
                <td class="border border-1 p-2">{{ $payload['module'] ?? $log->table_name }}</td>
                <td class="border border-1 p-2">{{ $log->action }}</td>
                <td class="border border-1 p-2">{{ $payload['path'] ?? '-' }}</td>
                <td class="border border-1 p-2">{{ $payload['status'] ?? '-' }}</td>
                <td class="border border-1 p-2 text-start" style="max-width: 560px; white-space: normal; word-break: break-word;">
                  @if($payload)
                    @if(!empty($payload['message']))
                      <div><strong>{{ $payload['message'] }}</strong></div>
                    @else
                      <div><strong>Operación registrada.</strong></div>
                    @endif
                    @if(!empty($payload['error']))
                      <div class="text-danger">{{ $payload['error'] }}</div>
                    @endif
                    @if(!empty($payload['changes']) && is_array($payload['changes']))
                      <ul class="mb-2 ps-3 text-xs">
                        @foreach($payload['changes'] as $changeLine)
                          <li>{{ $changeLine }}</li>
                        @endforeach
                      </ul>
                    @endif
                  @else
                    {{ $log->description }}
                  @endif
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
      <div class="px-3 pb-3 d-flex justify-content-center">
        {{ $logs->links() }}
      </div>
    </div>
  </div>

</div>
@endsection