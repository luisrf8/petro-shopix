@extends('layouts.app')

@section('title', 'Conceptos ISLR')

@section('content')
<div class="container-fluid py-2">
  <div class="card my-4">
    <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
      <div class="bg-gradient-dark shadow-dark border-radius-lg pt-3 pb-3 d-flex justify-content-between align-items-center">
        <h6 class="text-white text-capitalize ps-3 mb-0">Matriz de conceptos ISLR</h6>
      </div>
    </div>
    <div class="card-body">
      <form method="POST" action="{{ route('withholdings.islr.concepts.store') }}" class="row g-2 mb-4">
        @csrf
        <div class="col-md-2">
          <label class="form-label">Codigo</label>
          <input type="text" name="code" class="form-control border border-1 p-2" required>
        </div>
        <div class="col-md-3">
          <label class="form-label">Nombre</label>
          <input type="text" name="name" class="form-control border border-1 p-2" required>
        </div>
        <div class="col-md-2">
          <label class="form-label">Porcentaje</label>
          <input type="number" name="rate_percent" min="0" max="100" step="0.0001" class="form-control border border-1 p-2" required data-decimal-friendly="true">
        </div>
        <div class="col-md-2">
          <label class="form-label">Sustraendo UT</label>
          <input type="number" name="sustraendo_ut" min="0" step="0.0001" class="form-control border border-1 p-2" data-decimal-friendly="true">
        </div>
        <div class="col-md-1">
          <label class="form-label">Persona</label>
          <select name="applicable_person_type" class="form-control border border-1 p-2" required>
            <option value="any">Todas</option>
            <option value="pn">PN</option>
            <option value="pj">PJ</option>
          </select>
        </div>
        <div class="col-md-2">
          <label class="form-label">Residencia</label>
          <select name="applicable_residency_type" class="form-control border border-1 p-2" required>
            <option value="any">Todas</option>
            <option value="domiciliado">Domiciliado</option>
            <option value="no_domiciliado">No domiciliado</option>
          </select>
        </div>
        <div class="col-12 d-flex justify-content-end">
          <button type="submit" class="btn btn-dark mb-0">Guardar concepto</button>
        </div>
      </form>

      <div class="table-responsive">
        <table class="table align-items-center mb-0">
          <thead>
            <tr>
              <th>Codigo</th>
              <th>Nombre</th>
              <th>%</th>
              <th>Sustraendo UT</th>
              <th>Persona</th>
              <th>Residencia</th>
              <th>Origen</th>
              <th>Activo</th>
            </tr>
          </thead>
          <tbody>
          @forelse($concepts as $concept)
            <tr>
              <td>{{ $concept->code }}</td>
              <td>{{ $concept->name }}</td>
              <td>{{ number_format((float) $concept->rate_percent, 4) }}</td>
              <td>{{ number_format((float) $concept->sustraendo_ut, 4) }}</td>
              <td>{{ strtoupper($concept->applicable_person_type) }}</td>
              <td>{{ $concept->applicable_residency_type }}</td>
              <td>{{ $concept->tenant_id ? 'Tienda' : 'Global' }}</td>
              <td>{{ $concept->is_active ? 'Si' : 'No' }}</td>
            </tr>
          @empty
            <tr><td colspan="8" class="text-center text-muted">No hay conceptos cargados.</td></tr>
          @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
@endsection
