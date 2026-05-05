@extends('layouts.app')

@section('title', 'Cuentas por Pagar')

@section('content')
<div class="container-fluid py-2">
  <div class="row">
    <div class="col-md-6 col-xl-4 mb-4">
      <div class="card"><div class="card-body p-3"><p class="text-sm mb-1 text-uppercase font-weight-bold">Saldo pendiente</p><h4 class="mb-0">${{ number_format($totalPending, 2) }}</h4></div></div>
    </div>
    <div class="col-md-6 col-xl-4 mb-4">
      <div class="card"><div class="card-body p-3"><p class="text-sm mb-1 text-uppercase font-weight-bold">Vencido</p><h4 class="mb-0 text-danger">${{ number_format($overdueTotal, 2) }}</h4></div></div>
    </div>
    <div class="col-md-6 col-xl-4 mb-4">
      <div class="card"><div class="card-body p-3"><p class="text-sm mb-1 text-uppercase font-weight-bold">Pagado este mes</p><h4 class="mb-0 text-success">${{ number_format($monthPaid, 2) }}</h4></div></div>
    </div>
  </div>

  <div class="card my-4">
    <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
      <div class="bg-gradient-dark shadow-dark border-radius-lg pt-3 pb-3 d-flex justify-content-between align-items-center">
        <h6 class="text-white text-capitalize ps-3 mb-0">Gestión de cuentas por pagar</h6>
        <button type="button" class="btn btn-sm btn-light mb-0 me-3" data-bs-toggle="modal" data-bs-target="#createPayableModal">+ Nueva cuenta</button>
      </div>
    </div>
    <div class="card-body px-0 pb-2">
      <div class="px-3 pt-3">
        <form method="GET" class="row g-2 align-items-end">
          <div class="col-md-3"><label class="form-label">Buscar</label><input type="text" name="search" value="{{ $search }}" class="form-control border border-1 p-2" placeholder="Documento, proveedor, nota"></div>
          <div class="col-md-2"><label class="form-label">Estado</label><select name="status" class="form-control border border-1 p-2"><option value="">Todos</option><option value="pending" {{ $status === 'pending' ? 'selected' : '' }}>Pendiente</option><option value="partial" {{ $status === 'partial' ? 'selected' : '' }}>Parcial</option><option value="overdue" {{ $status === 'overdue' ? 'selected' : '' }}>Vencida</option><option value="paid" {{ $status === 'paid' ? 'selected' : '' }}>Pagada</option></select></div>
          <div class="col-md-3"><label class="form-label">Proveedor</label><select name="provider_id" class="form-control border border-1 p-2"><option value="">Todos</option>@foreach($providers as $provider)<option value="{{ $provider->id }}" {{ (int) $providerId === (int) $provider->id ? 'selected' : '' }}>{{ $provider->name }}</option>@endforeach</select></div>
          <div class="col-md-2"><label class="form-label">Desde</label><input type="date" name="date_from" value="{{ $dateFrom }}" class="form-control border border-1 p-2"></div>
          <div class="col-md-2"><label class="form-label">Hasta</label><input type="date" name="date_to" value="{{ $dateTo }}" class="form-control border border-1 p-2"></div>
          <div class="col-auto"><button type="submit" class="btn btn-dark mb-0">Filtrar</button></div>
        </form>
      </div>

      <div class="table-responsive p-3">
        <table class="table align-items-center mb-0">
          <thead><tr><th>#</th><th>Proveedor</th><th>Documento</th><th>Emisión</th><th>Vence</th><th>Total</th><th>Pagado</th><th>Pendiente</th><th>Estado</th><th>Acciones</th></tr></thead>
          <tbody>
            @forelse($accountsPayable as $payable)
              @php
                $statusMap = [
                  'pending' => ['label' => 'Pendiente', 'class' => 'bg-warning text-dark'],
                  'partial' => ['label' => 'Parcial', 'class' => 'bg-info text-white'],
                  'overdue' => ['label' => 'Vencida', 'class' => 'bg-danger text-white'],
                  'paid' => ['label' => 'Pagada', 'class' => 'bg-success text-white'],
                ];
                $statusMeta = $statusMap[$payable->status] ?? ['label' => ucfirst((string) $payable->status), 'class' => 'bg-secondary text-white'];
              @endphp
              <tr>
                <td>{{ $payable->id }}</td>
                <td>
                  <div class="d-flex flex-column">
                    <span class="font-weight-bold text-sm">{{ $payable->provider->name ?? 'Sin proveedor' }}</span>
                    <span class="text-xs text-secondary">OC: {{ $payable->purchase_order_id ? ('#' . $payable->purchase_order_id) : 'Manual' }}</span>
                  </div>
                </td>
                <td>{{ $payable->document_number ?: '-' }}</td>
                <td>{{ optional($payable->issued_at)->format('d/m/Y') }}</td>
                <td>{{ optional($payable->due_at)->format('d/m/Y') ?: '-' }}</td>
                <td>{{ number_format((float) $payable->amount_total, 2) }} {{ $payable->currency_code }}</td>
                <td>{{ number_format((float) $payable->amount_paid, 2) }} {{ $payable->currency_code }}</td>
                <td>{{ number_format((float) $payable->amount_pending, 2) }} {{ $payable->currency_code }}</td>
                <td><span class="badge {{ $statusMeta['class'] }}">{{ $statusMeta['label'] }}</span></td>
                <td>
                  @if((float) $payable->amount_pending > 0.0001)
                    <button type="button" class="btn btn-outline-dark btn-sm mb-0" data-bs-toggle="modal" data-bs-target="#registerPaymentModal"
                      data-payable-id="{{ $payable->id }}"
                      data-payable-pending="{{ number_format((float) $payable->amount_pending, 4, '.', '') }}"
                      data-payable-currency="{{ $payable->currency_code }}"
                      data-payable-provider="{{ $payable->provider->name ?? 'Sin proveedor' }}"
                    >Abonar</button>
                  @else
                    <span class="text-success text-xs fw-bold">Saldada</span>
                  @endif
                </td>
              </tr>
            @empty
              <tr><td colspan="10" class="text-center text-muted py-4">No hay cuentas por pagar registradas.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
      <div class="px-3 pb-3 d-flex justify-content-center">{{ $accountsPayable->links() }}</div>
    </div>
  </div>
</div>

<div class="modal fade" id="createPayableModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Registrar cuenta por pagar</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <form method="POST" action="{{ route('accounts.payable.store') }}">@csrf<div class="modal-body">
    <div class="mb-3"><label class="form-label">Proveedor</label><select name="provider_id" class="form-control border border-1 p-2"><option value="">Sin proveedor</option>@foreach($providers as $provider)<option value="{{ $provider->id }}">{{ $provider->name }}</option>@endforeach</select></div>
    <div class="mb-3"><label class="form-label">Orden de compra (opcional)</label><select name="purchase_order_id" class="form-control border border-1 p-2"><option value="">Ninguna</option>@foreach($purchaseOrders as $order)<option value="{{ $order->id }}">#{{ $order->id }} - {{ $order->provider_display_name }} - {{ $order->date }}</option>@endforeach</select></div>
    <div class="mb-3"><label class="form-label">N° Documento</label><input type="text" name="document_number" class="form-control border border-1 p-2" placeholder="Factura o referencia"></div>
    <div class="row"><div class="col-md-6 mb-3"><label class="form-label">Fecha emisión</label><input type="date" name="issued_at" value="{{ now()->toDateString() }}" class="form-control border border-1 p-2" required></div><div class="col-md-6 mb-3"><label class="form-label">Fecha vencimiento</label><input type="date" name="due_at" class="form-control border border-1 p-2"></div></div>
    <div class="row"><div class="col-md-6 mb-3"><label class="form-label">Monto total</label><input type="number" step="0.01" min="0.01" name="amount_total" class="form-control border border-1 p-2" required data-decimal-friendly="true"></div><div class="col-md-6 mb-3"><label class="form-label">Moneda</label><select name="currency_code" class="form-control border border-1 p-2"><option value="USD">USD</option><option value="EUR">EUR</option><option value="VES">VES</option></select></div></div>
    <div class="mb-3"><label class="form-label">Notas</label><textarea name="notes" class="form-control border border-1 p-2" rows="3"></textarea></div>
  </div><div class="modal-footer"><button type="button" class="btn btn-outline-secondary mb-0" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-dark mb-0">Guardar</button></div></form>
</div></div></div>

<div class="modal fade" id="registerPaymentModal" tabindex="-1" aria-hidden="true"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h5 class="modal-title">Registrar abono</h5><button type="button" class="btn-close" data-bs-dismiss="modal"></button></div>
  <form method="POST" id="registerPaymentForm">@csrf<div class="modal-body">
    <div class="alert alert-light border mb-3" id="registerPaymentSummary">Saldo pendiente</div>
    <div class="row"><div class="col-md-6 mb-3"><label class="form-label">Fecha de pago</label><input type="date" name="paid_at" value="{{ now()->toDateString() }}" class="form-control border border-1 p-2" required></div><div class="col-md-6 mb-3"><label class="form-label">Monto</label><input type="number" step="0.01" min="0.01" name="amount" id="registerPaymentAmount" class="form-control border border-1 p-2" required data-decimal-friendly="true"></div></div>
    <div class="row"><div class="col-md-6 mb-3"><label class="form-label">Método</label><input type="text" name="payment_method" class="form-control border border-1 p-2" placeholder="Transferencia, efectivo..."></div><div class="col-md-6 mb-3"><label class="form-label">Referencia</label><input type="text" name="reference" class="form-control border border-1 p-2"></div></div>
    <div class="mb-3"><label class="form-label">Notas</label><textarea name="notes" class="form-control border border-1 p-2" rows="3"></textarea></div>
  </div><div class="modal-footer"><button type="button" class="btn btn-outline-secondary mb-0" data-bs-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-dark mb-0">Registrar abono</button></div></form>
</div></div></div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const paymentModal = document.getElementById('registerPaymentModal');
  const paymentForm = document.getElementById('registerPaymentForm');
  const paymentSummary = document.getElementById('registerPaymentSummary');
  const paymentAmount = document.getElementById('registerPaymentAmount');

  paymentModal?.addEventListener('show.bs.modal', function (event) {
    const button = event.relatedTarget;
    if (!button || !paymentForm) {
      return;
    }

    const payableId = button.getAttribute('data-payable-id');
    const pending = button.getAttribute('data-payable-pending') || '0';
    const currency = button.getAttribute('data-payable-currency') || 'USD';
    const provider = button.getAttribute('data-payable-provider') || 'Sin proveedor';

    paymentForm.action = `/accounts-payable/${payableId}/payments`;
    paymentAmount.value = pending;
    paymentAmount.max = pending;
    paymentSummary.textContent = `Proveedor: ${provider} | Saldo pendiente: ${Number(pending).toFixed(2)} ${currency}`;
  });
});
</script>
@endpush
