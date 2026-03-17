@extends('layouts.app')

@section('title', 'Almacenes')

@section('content')
<div class="container-fluid py-2">
  <div class="row mt-4 g-4">
    <div class="col-12">
      <div class="card mb-0">
        <div class="card-header p-3 d-flex justify-content-between align-items-center">
          <div>
            <h5 class="mb-1">Gestión de almacenes</h5>
            <p class="text-sm text-muted mb-0">Administra almacenes, registra movimientos y edita operaciones ya registradas.</p>
          </div>
        </div>
        <div class="card-body p-3">
          @if(session('success'))
            <div class="alert alert-success text-white mb-3">{{ session('success') }}</div>
          @endif

          @if($errors->any())
            <div class="alert alert-danger text-white mb-3">{{ $errors->first() }}</div>
          @endif

          <div class="row g-4">
            <div class="col-12 col-xl-5">
              <div class="border rounded-3 p-3 h-100 bg-white">
                <h6 class="mb-3">Registrar almacén</h6>
                <form method="POST" action="{{ route('warehouses.store') }}" class="row g-3">
                  @csrf
                  <div class="col-12">
                    <label class="form-label">Nombre del almacén</label>
                    <input type="text" class="form-control border border-dark p-2 bg-white" name="name" placeholder="Ej: Almacén Centro" required>
                  </div>
                  <div class="col-12 col-md-6">
                    <input type="hidden" name="is_default" value="0">
                    <div class="form-check border rounded-2 p-3">
                      <input class="form-check-input" type="checkbox" value="1" id="createWarehouseDefault" name="is_default">
                      <label class="form-check-label" for="createWarehouseDefault">Definir como principal</label>
                    </div>
                  </div>
                  <div class="col-12 col-md-6">
                    <input type="hidden" name="is_active" value="1">
                    <div class="form-check border rounded-2 p-3">
                      <input class="form-check-input" type="checkbox" value="1" id="createWarehouseActive" checked disabled>
                      <label class="form-check-label" for="createWarehouseActive">Activo al crear</label>
                    </div>
                  </div>
                  <div class="col-12">
                    <button type="submit" class="btn btn-dark mb-0 w-100">Crear almacén</button>
                  </div>
                </form>
              </div>
            </div>

            <div class="col-12 col-xl-7">
              <div class="border rounded-3 p-3 h-100 bg-white">
                <h6 class="mb-3">Registrar movimiento</h6>
                <form method="POST" action="{{ route('warehouses.movements.store') }}" class="row g-3">
                  @csrf
                  <div class="col-12 col-md-6">
                    <label class="form-label">Tipo de movimiento</label>
                    <select name="movement_type" class="form-control border border-dark p-2 bg-white" required>
                      @foreach($movementTypes as $movementKey => $movementLabel)
                        <option value="{{ $movementKey }}">{{ $movementLabel }}</option>
                      @endforeach
                    </select>
                  </div>
                  <div class="col-12 col-md-6">
                    <label class="form-label">Fecha y hora</label>
                    <input type="datetime-local" name="moved_at" class="form-control border border-dark p-2 bg-white" value="{{ now()->format('Y-m-d\TH:i') }}" required>
                  </div>
                  <div class="col-12">
                    <label class="form-label">Producto / variante</label>
                    <select name="product_variant_id" class="form-control border border-dark p-2 bg-white" required>
                      <option value="">Selecciona una variante</option>
                      @foreach($variants as $variant)
                        <option value="{{ $variant->id }}">{{ $variant->product->name ?? 'Producto' }} - {{ $variant->size }} | Stock general: {{ number_format((float) $variant->stock, 2) }}</option>
                      @endforeach
                    </select>
                  </div>
                  <div class="col-12 col-md-6">
                    <label class="form-label">Almacén origen</label>
                    <select name="source_warehouse_id" class="form-control border border-dark p-2 bg-white">
                      <option value="">No aplica</option>
                      @foreach($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                      @endforeach
                    </select>
                  </div>
                  <div class="col-12 col-md-6">
                    <label class="form-label">Almacén destino</label>
                    <select name="destination_warehouse_id" class="form-control border border-dark p-2 bg-white">
                      <option value="">No aplica</option>
                      @foreach($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                      @endforeach
                    </select>
                  </div>
                  <div class="col-12 col-md-4">
                    <label class="form-label">Cantidad</label>
                    <input type="number" step="0.01" min="0.01" name="quantity" class="form-control border border-dark p-2 bg-white" placeholder="0.00" required>
                  </div>
                  <div class="col-12 col-md-8">
                    <label class="form-label">Observación</label>
                    <input type="text" name="notes" class="form-control border border-dark p-2 bg-white" placeholder="Motivo o detalle del movimiento">
                  </div>
                  <div class="col-12">
                    <div class="small text-muted border rounded-2 p-2">
                      Entrada: usa destino. Salida: usa origen. Transferencia: usa ambos almacenes.
                    </div>
                  </div>
                  <div class="col-12">
                    <button type="submit" class="btn btn-dark mb-0 w-100">Registrar movimiento</button>
                  </div>
                </form>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-xl-5">
      <div class="card h-100">
        <div class="card-header p-3">
          <h6 class="mb-0">Registro y edición de almacenes</h6>
        </div>
        <div class="card-body p-3">
          <div class="table-responsive">
            <table class="table align-items-center mb-0">
              <thead>
                <tr>
                  <th>Nombre</th>
                  <th>Principal</th>
                  <th>Activo</th>
                  <th class="text-end">Acción</th>
                </tr>
              </thead>
              <tbody>
                @forelse($warehouses as $warehouse)
                  <tr>
                    <td colspan="4" class="px-0 border-0">
                      <form method="POST" action="{{ route('warehouses.update', $warehouse) }}" class="row g-2 align-items-center border rounded-3 p-2 mx-0 mb-2 bg-white">
                        @csrf
                        @method('PUT')
                        <div class="col-12 col-md-5">
                          <input type="text" name="name" class="form-control border border-dark p-2 bg-white" value="{{ $warehouse->name }}" required>
                        </div>
                        <div class="col-6 col-md-2">
                          <input type="hidden" name="is_default" value="0">
                          <div class="form-check mb-0">
                            <input class="form-check-input" type="checkbox" name="is_default" value="1" id="warehouse-default-{{ $warehouse->id }}" {{ $warehouse->is_default ? 'checked' : '' }}>
                            <label class="form-check-label" for="warehouse-default-{{ $warehouse->id }}">Sí</label>
                          </div>
                        </div>
                        <div class="col-6 col-md-2">
                          <input type="hidden" name="is_active" value="0">
                          <div class="form-check mb-0">
                            <input class="form-check-input" type="checkbox" name="is_active" value="1" id="warehouse-active-{{ $warehouse->id }}" {{ $warehouse->is_active ? 'checked' : '' }}>
                            <label class="form-check-label" for="warehouse-active-{{ $warehouse->id }}">Sí</label>
                          </div>
                        </div>
                        <div class="col-12 col-md-3 text-md-end">
                          <button type="submit" class="btn btn-outline-dark mb-0 w-100">Guardar</button>
                        </div>
                      </form>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="4" class="text-center text-muted py-4">No hay almacenes registrados.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <div class="col-12 col-xl-7">
      <div class="card h-100">
        <div class="card-header p-3">
          <h6 class="mb-0">Movimientos registrados</h6>
        </div>
        <div class="card-body p-3">
          <div class="table-responsive">
            <table class="table table-bordered align-items-center mb-0">
              <thead>
                <tr>
                  <th>Fecha</th>
                  <th>Tipo</th>
                  <th>Producto</th>
                  <th>Origen</th>
                  <th>Destino</th>
                  <th class="text-end">Cantidad</th>
                  <th>Registro</th>
                  <th class="text-end">Editar</th>
                </tr>
              </thead>
              <tbody>
                @forelse($movements as $movement)
                  <tr>
                    <td>{{ $movement->moved_at?->format('d/m/Y H:i') }}</td>
                    <td>{{ $movementTypes[$movement->movement_type] ?? $movement->movement_type }}</td>
                    <td>
                      <div class="fw-semibold">{{ $movement->variant->product->name ?? 'Producto' }}</div>
                      <div class="text-xs text-muted">{{ $movement->variant->size ?? 'Variante' }}</div>
                    </td>
                    <td>{{ $movement->sourceWarehouse->name ?? 'No aplica' }}</td>
                    <td>{{ $movement->destinationWarehouse->name ?? 'No aplica' }}</td>
                    <td class="text-end">{{ number_format((float) $movement->quantity, 2) }}</td>
                    <td>
                      <div>{{ $movement->user->name ?? 'Sistema' }}</div>
                      @if($movement->notes)
                        <div class="text-xs text-muted">{{ \Illuminate\Support\Str::limit($movement->notes, 60) }}</div>
                      @endif
                    </td>
                    <td class="text-end">
                      <button class="btn btn-outline-dark btn-sm mb-0" type="button" data-bs-toggle="collapse" data-bs-target="#movement-edit-{{ $movement->id }}" aria-expanded="false">Editar</button>
                    </td>
                  </tr>
                  <tr class="collapse" id="movement-edit-{{ $movement->id }}">
                    <td colspan="8" class="bg-light">
                      <form method="POST" action="{{ route('warehouses.movements.update', $movement) }}" class="row g-2 p-2">
                        @csrf
                        @method('PUT')
                        <div class="col-12 col-md-3">
                          <label class="form-label">Tipo</label>
                          <select name="movement_type" class="form-control border border-dark p-2 bg-white" required>
                            @foreach($movementTypes as $movementKey => $movementLabel)
                              <option value="{{ $movementKey }}" {{ $movement->movement_type === $movementKey ? 'selected' : '' }}>{{ $movementLabel }}</option>
                            @endforeach
                          </select>
                        </div>
                        <div class="col-12 col-md-5">
                          <label class="form-label">Producto / variante</label>
                          <select name="product_variant_id" class="form-control border border-dark p-2 bg-white" required>
                            @foreach($variants as $variant)
                              <option value="{{ $variant->id }}" {{ (int) $movement->product_variant_id === (int) $variant->id ? 'selected' : '' }}>{{ $variant->product->name ?? 'Producto' }} - {{ $variant->size }}</option>
                            @endforeach
                          </select>
                        </div>
                        <div class="col-12 col-md-4">
                          <label class="form-label">Fecha y hora</label>
                          <input type="datetime-local" name="moved_at" class="form-control border border-dark p-2 bg-white" value="{{ $movement->moved_at?->format('Y-m-d\TH:i') }}" required>
                        </div>
                        <div class="col-12 col-md-3">
                          <label class="form-label">Origen</label>
                          <select name="source_warehouse_id" class="form-control border border-dark p-2 bg-white">
                            <option value="">No aplica</option>
                            @foreach($warehouses as $warehouse)
                              <option value="{{ $warehouse->id }}" {{ (int) $movement->source_warehouse_id === (int) $warehouse->id ? 'selected' : '' }}>{{ $warehouse->name }}</option>
                            @endforeach
                          </select>
                        </div>
                        <div class="col-12 col-md-3">
                          <label class="form-label">Destino</label>
                          <select name="destination_warehouse_id" class="form-control border border-dark p-2 bg-white">
                            <option value="">No aplica</option>
                            @foreach($warehouses as $warehouse)
                              <option value="{{ $warehouse->id }}" {{ (int) $movement->destination_warehouse_id === (int) $warehouse->id ? 'selected' : '' }}>{{ $warehouse->name }}</option>
                            @endforeach
                          </select>
                        </div>
                        <div class="col-12 col-md-2">
                          <label class="form-label">Cantidad</label>
                          <input type="number" step="0.01" min="0.01" name="quantity" class="form-control border border-dark p-2 bg-white" value="{{ number_format((float) $movement->quantity, 2, '.', '') }}" required>
                        </div>
                        <div class="col-12 col-md-4">
                          <label class="form-label">Observación</label>
                          <input type="text" name="notes" class="form-control border border-dark p-2 bg-white" value="{{ $movement->notes }}">
                        </div>
                        <div class="col-12 text-end">
                          <button type="submit" class="btn btn-dark mb-0">Actualizar movimiento</button>
                        </div>
                      </form>
                    </td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="8" class="text-center text-muted py-4">No hay movimientos registrados.</td>
                  </tr>
                @endforelse
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
