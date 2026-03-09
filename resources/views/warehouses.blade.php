@extends('layouts.app')

@section('title', 'Almacenes')

@section('content')
<div class="container-fluid py-2">
  <div class="row mt-4">
    <div class="col-12">
      <div class="card mb-4">
        <div class="card-header p-3 d-flex justify-content-between align-items-center">
          <div>
            <h5 class="mb-1">Gestión de almacenes</h5>
            <p class="text-sm text-muted mb-0">Crea múltiples almacenes y visualiza existencias por producto/variante.</p>
          </div>
        </div>
        <div class="card-body p-3">
          @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
          @endif

          @if($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
          @endif

          <form method="POST" action="{{ route('warehouses.store') }}" class="row g-2 align-items-end">
            @csrf
            <div class="col-12 col-md-8">
              <label class="form-label">Nombre del almacén</label>
              <input type="text" class="form-control border border-1 p-2 bg-white" name="name" placeholder="Ej: Almacén Centro" required>
            </div>
            <div class="col-12 col-md-4">
              <button type="submit" class="btn btn-info mb-0 w-100">Crear almacén</button>
            </div>
          </form>

          <hr>

          <div class="table-responsive">
            <table class="table align-items-center mb-0">
              <thead>
                <tr>
                  <th>Almacén</th>
                  <th>Tipo</th>
                  <th>Estado</th>
                </tr>
              </thead>
              <tbody>
                @foreach($warehouses as $warehouse)
                  <tr>
                    <td>{{ $warehouse->name }}</td>
                    <td>{{ $warehouse->is_default ? 'Principal' : 'Secundario' }}</td>
                    <td>{{ $warehouse->is_active ? 'Activo' : 'Inactivo' }}</td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12">
      <div class="card">
        <div class="card-header p-3">
          <h6 class="mb-0">Existencia entre almacenes</h6>
        </div>
        <div class="card-body p-3">
          <div class="table-responsive">
            <table class="table table-bordered align-items-center mb-0">
              <thead>
                <tr>
                  <th>Producto</th>
                  <th>Variante</th>
                  @foreach($warehouses as $warehouse)
                    <th class="text-end">{{ $warehouse->name }}</th>
                  @endforeach
                  <th class="text-end">Total</th>
                </tr>
              </thead>
              <tbody>
                @foreach($products as $product)
                  @foreach($product->variants as $variant)
                    @php $rowTotal = 0; @endphp
                    <tr>
                      <td>{{ $product->name }}</td>
                      <td>{{ $variant->size }}</td>
                      @foreach($warehouses as $warehouse)
                        @php
                          $key = $warehouse->id . '_' . $variant->id;
                          $qty = (float) ($stocks[$key]->quantity ?? 0);
                          $rowTotal += $qty;
                        @endphp
                        <td class="text-end">{{ number_format($qty, 2) }}</td>
                      @endforeach
                      <td class="text-end fw-bold">{{ number_format($rowTotal, 2) }}</td>
                    </tr>
                  @endforeach
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
