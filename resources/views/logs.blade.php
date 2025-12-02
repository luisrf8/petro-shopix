@extends('layouts.app')

@section('content')
<div class="container-fluid py-2">

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
              <th>ID</th>
              <th>Usuario</th>
              <th>Tabla</th>
              <th>Accion</th>
              <th>Descripcion</th>
            </tr>
          </thead>
          <tbody>
            @foreach ($logs as $log)
              <tr>
                <td>{{ $log->id }}</td>
                <td>{{ $log->user_id }}</td>
                <td>{{ $log->table_name }}</td>
                <td>{{ $log->action }}</td>
                <td>{{ $log->description }}</td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>
    </div>
  </div>

</div>
@endsection