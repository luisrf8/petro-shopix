@extends('layouts.app')

@section('title', 'Ventas Pagadas por Entregar')

@section('content')
<div class="container-fluid py-2">
  <div class="row">
    <div class="col-md-6 col-xl-3 mb-4">
      <div class="card">
        <div class="card-body p-3">
          <p class="text-sm mb-1 text-uppercase font-weight-bold">Órdenes pagadas</p>
          <h4 class="mb-0">{{ number_format($ordersCount) }}</h4>
        </div>
      </div>
    </div>
    <div class="col-md-6 col-xl-3 mb-4">
      <div class="card">
        <div class="card-body p-3">
          <p class="text-sm mb-1 text-uppercase font-weight-bold">Monto ya cobrado</p>
          <h4 class="mb-0">${{ number_format($totalPaidOrdersAmount, 2) }}</h4>
        </div>
      </div>
    </div>
  </div>

  <div class="card my-4">
    <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
      <div class="bg-gradient-dark shadow-dark border-radius-lg pt-3 pb-3 d-flex justify-content-between align-items-center">
        <h6 class="text-white text-capitalize ps-3 mb-0">Ventas pagadas pendientes de entrega</h6>
        <span class="badge bg-light text-dark me-3">{{ $ordersCount }}</span>
      </div>
    </div>
    <div class="card-body px-0 pb-2">
      <div class="table-responsive p-3">
        <table class="table align-items-center mb-0">
          <thead>
            <tr>
              <th>Orden</th>
              <th>Fecha</th>
              <th>Cliente</th>
              <th>Items</th>
              <th>Total orden</th>
              <th>Pagado</th>
              <th>Saldo</th>
              <th>Entrega</th>
              <th>Acción</th>
            </tr>
          </thead>
          <tbody>
            @forelse($salesOrders as $order)
              <tr>
                <td>#{{ $order->id }}</td>
                <td>{{ \Carbon\Carbon::parse($order->date)->format('d/m/Y') }}</td>
                <td>
                  <div class="d-flex flex-column">
                    <span class="font-weight-bold text-sm">{{ $order->user->name ?? 'Cliente no asignado' }}</span>
                    <span class="text-xs text-secondary">{{ $order->user->email ?? '-' }}</span>
                  </div>
                </td>
                <td>{{ $order->total_items }}</td>
                <td>${{ number_format($order->order_total_amount, 2) }}</td>
                <td><span class="badge bg-success">${{ number_format($order->approved_paid_amount, 2) }}</span></td>
                <td>${{ number_format($order->pending_amount, 2) }}</td>
                <td>{{ $order->preference }}</td>
                <td>
                  <a href="{{ route('sales.showByOrder', $order->id) }}" class="btn btn-outline-dark btn-sm mb-0">Gestionar entrega</a>
                </td>
              </tr>
            @empty
              <tr>
                <td colspan="9" class="text-center text-muted py-4">No hay ventas pagadas pendientes de entrega.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
@endsection
