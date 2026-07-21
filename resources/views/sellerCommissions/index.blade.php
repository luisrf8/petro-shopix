@extends('layouts.app')

@section('title', 'Comisiones de Ventas')

@section('content')
<div class="container-fluid py-2">
  <div class="row">
    <div class="col-md-4 mb-4"><div class="card"><div class="card-body p-3"><p class="text-sm mb-1 text-uppercase font-weight-bold">Comisiones pendientes</p><h4 class="mb-0 text-warning">${{ number_format($totalPending, 2) }}</h4></div></div></div>
    <div class="col-md-4 mb-4"><div class="card"><div class="card-body p-3"><p class="text-sm mb-1 text-uppercase font-weight-bold">Comisiones pagadas</p><h4 class="mb-0 text-success">${{ number_format($totalPaid, 2) }}</h4></div></div></div>
    <div class="col-md-4 mb-4"><div class="card"><div class="card-body p-3"><p class="text-sm mb-1 text-uppercase font-weight-bold">Generadas este mes</p><h4 class="mb-0">${{ number_format($monthGenerated, 2) }}</h4></div></div></div>
  </div>

  @if($isAdminView)
  <div class="card mb-4">
    <div class="card-header p-3 pb-0"><h6 class="mb-0">Porcentaje por usuario de ventas</h6></div>
    <div class="card-body pt-2">
      <div class="table-responsive">
        <table class="table align-items-center mb-0">
          <thead><tr><th>Usuario</th><th>Comisión (%)</th><th>Acción</th></tr></thead>
          <tbody>
            @forelse($sellers as $seller)
              <tr>
                <td>{{ $seller->name }}</td>
                <td style="max-width:140px;">
                  <form method="POST" action="{{ route('seller-commissions.rate.update', $seller->id) }}" class="d-flex gap-2 align-items-center">
                    @csrf
                    @method('PUT')
                    <input type="number" step="0.01" min="0" max="100" name="commission_percentage" value="{{ number_format((float) ($seller->commission_percentage ?? 0), 2, '.', '') }}" class="form-control border border-1 p-2" required data-decimal-friendly="true">
                </td>
                <td>
                    <button type="submit" class="btn btn-sm btn-dark mb-0">Guardar</button>
                  </form>
                </td>
              </tr>
            @empty
              <tr><td colspan="3" class="text-muted">No hay usuarios elegibles para comisión.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
  @endif

  <div class="card my-4">
    <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
      <div class="bg-gradient-dark shadow-dark border-radius-lg pt-3 pb-3 d-flex justify-content-between align-items-center">
        <h6 class="text-white text-capitalize ps-3 mb-0">Comisiones por venta</h6>
      </div>
    </div>
    <div class="card-body px-0 pb-2">
      <div class="px-3 pt-3">
        <form method="GET" class="row g-2 align-items-end">
          @if(!$isSellerOnlyView)
          <div class="col-md-3"><label class="form-label">Usuario</label><select name="seller_id" class="form-control border border-1 p-2"><option value="">Todos</option>@foreach($sellers as $seller)<option value="{{ $seller->id }}" {{ (int) $sellerId === (int) $seller->id ? 'selected' : '' }}>{{ $seller->name }}</option>@endforeach</select></div>
          @endif
          <div class="col-md-2"><label class="form-label">Estado</label><select name="status" class="form-control border border-1 p-2"><option value="">Todos</option><option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>Pendiente</option><option value="paid" {{ $status === 'paid' ? 'selected' : '' }}>Pagada</option></select></div>
          <div class="col-md-2"><label class="form-label">Desde</label><input type="date" name="date_from" value="{{ $dateFrom }}" class="form-control border border-1 p-2"></div>
          <div class="col-md-2"><label class="form-label">Hasta</label><input type="date" name="date_to" value="{{ $dateTo }}" class="form-control border border-1 p-2"></div>
          <div class="col-auto"><button type="submit" class="btn btn-dark mb-0">Filtrar</button></div>
        </form>
      </div>

      <div class="table-responsive p-3">
        <table class="table align-items-center mb-0">
          <thead><tr><th>#Venta</th><th>Vendedor</th><th>Base</th><th>Tasa</th><th>Comisión</th><th>Estado</th><th>Calculada</th><th>Acción</th></tr></thead>
          <tbody>
            @forelse($commissions as $commission)
              <tr>
                <td><a href="/sales/{{ $commission->sales_order_id }}" class="text-dark">#{{ $commission->sales_order_id }}</a></td>
                <td>{{ $commission->seller->name ?? 'N/A' }}</td>
                <td>{{ number_format((float) $commission->commission_base_amount, 2) }} {{ $commission->currency_code }}</td>
                <td>{{ number_format((float) $commission->commission_rate, 2) }}%</td>
                <td>{{ number_format((float) $commission->commission_amount, 2) }} {{ $commission->currency_code }}</td>
                <td>
                  @if($commission->status === 'paid')
                    <span class="badge bg-success">Pagada</span>
                  @else
                    <span class="badge bg-warning text-dark">Pendiente</span>
                  @endif
                </td>
                <td>{{ optional($commission->calculated_at)->format('d/m/Y H:i') ?: optional($commission->created_at)->format('d/m/Y H:i') }}</td>
                <td>
                  @if($isAdminView && $commission->status !== 'paid')
                    <form method="POST" action="{{ route('seller-commissions.mark-paid', $commission->id) }}">
                      @csrf
                      <button type="submit" class="btn btn-outline-dark btn-sm mb-0">Marcar pagada</button>
                    </form>
                  @else
                    <span class="text-muted text-xs">-</span>
                  @endif
                </td>
              </tr>
            @empty
              <tr><td colspan="8" class="text-center text-muted py-4">No hay comisiones registradas.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
      <div class="px-3 pb-3 d-flex justify-content-center">{{ $commissions->links() }}</div>
    </div>
  </div>
</div>
@endsection
