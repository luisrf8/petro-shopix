@extends('layouts.app')

@section('title', 'Administrador de Comisiones')

@section('content')
<div class="container-fluid py-2">
  <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
    <div>
      <h5 class="mb-1">Administrador de Comisiones</h5>
      <p class="text-sm text-muted mb-0">
        {{ $summary['seller_name'] }} · Mes actual {{ $summary['month_start']->format('d/m/Y') }} al {{ $summary['month_end']->format('d/m/Y') }}
      </p>
    </div>
    <a href="{{ route('seller-commissions.progress.pdf') }}" class="btn btn-dark mb-0">Descargar PDF</a>
  </div>

  <div class="row">
    <div class="col-md-4 mb-4">
      <div class="card">
        <div class="card-body p-3">
          <p class="text-sm mb-1 text-uppercase font-weight-bold">Comisión generada</p>
          <h4 class="mb-0">{{ number_format((float) $summary['total_generated'], 2) }} {{ $summary['currency_code'] }}</h4>
        </div>
      </div>
    </div>
    <div class="col-md-4 mb-4">
      <div class="card">
        <div class="card-body p-3">
          <p class="text-sm mb-1 text-uppercase font-weight-bold">Pendiente por pagar</p>
          <h4 class="mb-0 text-warning">{{ number_format((float) $summary['total_pending'], 2) }} {{ $summary['currency_code'] }}</h4>
        </div>
      </div>
    </div>
    <div class="col-md-4 mb-4">
      <div class="card">
        <div class="card-body p-3">
          <p class="text-sm mb-1 text-uppercase font-weight-bold">Pagada en el mes</p>
          <h4 class="mb-0 text-success">{{ number_format((float) $summary['total_paid'], 2) }} {{ $summary['currency_code'] }}</h4>
        </div>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-md-3 mb-4">
      <div class="card">
        <div class="card-body p-3">
          <p class="text-sm mb-1 text-uppercase font-weight-bold">Clientes frecuentes</p>
          <h4 class="mb-0">{{ number_format((int) ($summary['frequent_customers_count'] ?? 0)) }}</h4>
        </div>
      </div>
    </div>
    <div class="col-md-3 mb-4">
      <div class="card">
        <div class="card-body p-3">
          <p class="text-sm mb-1 text-uppercase font-weight-bold">Clientes que deben</p>
          <h4 class="mb-0 text-warning">{{ number_format((int) ($summary['debt_customers_count'] ?? 0)) }}</h4>
        </div>
      </div>
    </div>
    <div class="col-md-3 mb-4">
      <div class="card">
        <div class="card-body p-3">
          <p class="text-sm mb-1 text-uppercase font-weight-bold">Ventas por cobrar</p>
          <h4 class="mb-0 text-warning">{{ number_format((int) ($summary['receivable_orders_count'] ?? 0)) }}</h4>
          <small class="text-muted">{{ number_format((float) ($summary['receivable_total'] ?? 0), 2) }} {{ $summary['currency_code'] }}</small>
        </div>
      </div>
    </div>
    <div class="col-md-3 mb-4">
      <div class="card">
        <div class="card-body p-3">
          <p class="text-sm mb-1 text-uppercase font-weight-bold">Ventas por entregar</p>
          <h4 class="mb-0 text-info">{{ number_format((int) ($summary['pending_delivery_orders_count'] ?? 0)) }}</h4>
          <small class="text-muted">{{ number_format((float) ($summary['pending_delivery_total'] ?? 0), 2) }} {{ $summary['currency_code'] }}</small>
        </div>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-lg-6 mb-4">
      <div class="card h-100">
        <div class="card-header pb-0">
          <h6 class="mb-0">Clientes frecuentes</h6>
        </div>
        <div class="card-body pt-2">
          <div class="table-responsive">
            <table class="table align-items-center mb-0">
              <thead>
                <tr>
                  <th>Cliente</th>
                  <th>Compras</th>
                  <th>Total</th>
                </tr>
              </thead>
              <tbody>
                @forelse($frequentCustomers as $customer)
                  <tr>
                    <td>{{ $customer['customer_name'] }}</td>
                    <td>{{ number_format((int) $customer['orders_count']) }}</td>
                    <td>{{ number_format((float) $customer['total_amount'], 2) }} {{ $summary['currency_code'] }}</td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="3" class="text-center text-muted py-3">Sin clientes frecuentes por ahora.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-6 mb-4">
      <div class="card h-100">
        <div class="card-header pb-0">
          <h6 class="mb-0">Clientes que deben</h6>
        </div>
        <div class="card-body pt-2">
          <div class="table-responsive">
            <table class="table align-items-center mb-0">
              <thead>
                <tr>
                  <th>Cliente</th>
                  <th>Ventas pendientes</th>
                  <th>Deuda</th>
                </tr>
              </thead>
              <tbody>
                @forelse($debtCustomers as $customer)
                  <tr>
                    <td>{{ $customer['customer_name'] }}</td>
                    <td>{{ number_format((int) $customer['orders_count']) }}</td>
                    <td class="text-warning">{{ number_format((float) $customer['pending_amount'], 2) }} {{ $summary['currency_code'] }}</td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="3" class="text-center text-muted py-3">No hay clientes con deuda pendiente.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-lg-6 mb-4">
      <div class="card h-100">
        <div class="card-header pb-0">
          <h6 class="mb-0">Ventas por cobrar</h6>
        </div>
        <div class="card-body pt-2">
          <div class="table-responsive">
            <table class="table align-items-center mb-0">
              <thead>
                <tr>
                  <th>#Venta</th>
                  <th>Cliente</th>
                  <th>Pendiente</th>
                </tr>
              </thead>
              <tbody>
                @forelse($receivableOrders as $order)
                  <tr>
                    <td><a href="/sales/{{ $order->id }}" class="text-dark">#{{ $order->id }}</a></td>
                    <td>{{ $order->user->name ?? 'Cliente' }}</td>
                    <td class="text-warning">{{ number_format((float) $order->pending_amount, 2) }} {{ $summary['currency_code'] }}</td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="3" class="text-center text-muted py-3">No hay ventas por cobrar.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>

    <div class="col-lg-6 mb-4">
      <div class="card h-100">
        <div class="card-header pb-0">
          <h6 class="mb-0">Ventas por entregar</h6>
        </div>
        <div class="card-body pt-2">
          <div class="table-responsive">
            <table class="table align-items-center mb-0">
              <thead>
                <tr>
                  <th>#Venta</th>
                  <th>Cliente</th>
                  <th>Total</th>
                </tr>
              </thead>
              <tbody>
                @forelse($pendingDeliveryOrders as $order)
                  <tr>
                    <td><a href="/sales/{{ $order->id }}" class="text-dark">#{{ $order->id }}</a></td>
                    <td>{{ $order->user->name ?? 'Cliente' }}</td>
                    <td class="text-info">{{ number_format((float) $order->order_total_amount, 2) }} {{ $summary['currency_code'] }}</td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="3" class="text-center text-muted py-3">No hay ventas por entregar.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="card my-4">
    <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
      <div class="bg-gradient-dark shadow-dark border-radius-lg pt-3 pb-3 d-flex justify-content-between align-items-center">
        <h6 class="text-white text-capitalize ps-3 mb-0">Detalle de comisiones del mes</h6>
        <span class="text-white pe-3 text-sm">Ventas con comisión: {{ number_format((int) $summary['orders_count']) }}</span>
      </div>
    </div>
    <div class="card-body px-0 pb-2">
      <div class="table-responsive p-3">
        <table class="table align-items-center mb-0">
          <thead>
            <tr>
              <th>#Venta</th>
              <th>Base</th>
              <th>Tasa</th>
              <th>Comisión</th>
              <th>Estado</th>
              <th>Calculada</th>
            </tr>
          </thead>
          <tbody>
            @forelse($commissions as $commission)
              <tr>
                <td><a href="/sales/{{ $commission->sales_order_id }}" class="text-dark">#{{ $commission->sales_order_id }}</a></td>
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
              </tr>
            @empty
              <tr>
                <td colspan="6" class="text-center text-muted py-4">No hay comisiones para el mes en curso.</td>
              </tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>
@endsection
