@extends('layouts.app')

@section('title', 'Pagos de Tiendas')

@section('content')
<div class="container-fluid py-2">
  <div class="row mb-3" id="billing-overview-section">
    <div class="col-lg-6 mb-3 mb-lg-0">
      <div class="card border">
        <div class="card-body py-3">
          <h6 class="mb-2">Tiendas próximas de pago (7 días)</h6>
          @if(($nearDueTenants->count() ?? 0) > 0)
            @foreach($nearDueTenants as $nearTenant)
              <div class="d-flex justify-content-between align-items-center py-1 border-bottom">
                <span>{{ $nearTenant->name }}</span>
                <span class="badge bg-warning text-dark">{{ (int) $nearTenant->plan_days_remaining }} días</span>
              </div>
            @endforeach
          @else
            <p class="text-sm text-muted mb-0">No hay tiendas próximas de pago dentro de los próximos 7 días.</p>
          @endif
        </div>
      </div>
    </div>

    <div class="col-lg-6">
      <div class="card border">
        <div class="card-body py-3">
          <h6 class="mb-2">Tiendas vencidas</h6>
          @if(($overdueTenants->count() ?? 0) > 0)
            @foreach($overdueTenants as $overTenant)
              <div class="d-flex justify-content-between align-items-center py-1 border-bottom">
                <span>{{ $overTenant->name }}</span>
                <span class="badge bg-danger">{{ abs((int) $overTenant->plan_days_remaining) }} días vencido</span>
              </div>
            @endforeach
          @else
            <p class="text-sm text-muted mb-0">No hay tiendas vencidas actualmente.</p>
          @endif
        </div>
      </div>
    </div>
  </div>

  <div class="row mt-5 mb-3" id="pending-payments-section">
    <div class="col-12">
      <div class="card border">
        <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
          <div class="bg-gradient-dark shadow-dark border-radius-lg pt-3 pb-3 d-flex justify-content-between align-items-center">
            <h6 class="text-white text-capitalize ps-3 mb-0">Pagos pendientes por aprobación</h6>
            <span class="badge bg-warning text-dark me-3">{{ $pendingPayments->count() }}</span>
          </div>
        </div>
        <div class="card-body py-3">
          @if(($pendingPayments->count() ?? 0) > 0)
            <div class="table-responsive">
              <table class="table align-items-center mb-0">
                <thead>
                  <tr>
                    <th>Tienda</th>
                    <th>Plan</th>
                    <th>Monto</th>
                    <th>Referencia</th>
                    <th>Archivo</th>
                    <th>Enviado</th>
                    <th>Acción</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($pendingPayments as $pending)
                    <tr>
                      <td>{{ $pending->tenant->name ?? 'N/A' }}</td>
                      <td>{{ $pending->plan->name ?? 'N/A' }}</td>
                      <td>${{ number_format((float) ($pending->amount ?? 0), 2) }}</td>
                      <td>{{ $pending->payment_reference ?? 'Sin referencia' }}</td>
                      <td>
                        @if(!empty($pending->payment_proof))
                          @if(\App\Support\ImageStorage::isGooglePath($pending->payment_proof))
                            <span class="badge bg-success">Drive</span>
                          @else
                            <span class="badge bg-secondary">Local</span>
                          @endif
                        @else
                          -
                        @endif
                      </td>
                      <td>{{ optional($pending->created_at)->format('d/m/Y H:i') ?? '-' }}</td>
                      <td>
                        <div class="d-flex gap-2 flex-wrap">
                          @if(!empty($pending->payment_proof))
                            <a href="{{ \App\Support\ImageStorage::url($pending->payment_proof) }}" target="_blank" rel="noopener" class="btn btn-outline-dark btn-sm mb-0">
                              Comprobante
                            </a>
                          @endif
                          <form method="POST" action="{{ route('tenant.planPayment.approve', ['tenant' => $pending->tenant_id, 'payment' => $pending->id]) }}">
                            @csrf
                            <label class="form-label text-xs mb-1">Fecha corte</label>
                            <input
                              type="datetime-local"
                              name="expires_at"
                              value="{{ now()->addDays((int) ($pending->plan->duration_days ?? 0))->format('Y-m-d\\TH:i') }}"
                              class="form-control form-control-sm mb-2"
                            >
                            <button type="submit" class="btn btn-success btn-sm mb-0">Aprobar</button>
                          </form>
                          <form method="POST" action="{{ route('tenant.planPayment.reject', ['tenant' => $pending->tenant_id, 'payment' => $pending->id]) }}" data-requires-action-reason="true" data-reason-field="review_notes" data-reason-prompt="Indica el motivo para rechazar este pago de plan.">
                            @csrf
                            <button type="submit" class="btn btn-outline-danger btn-sm mb-0">Rechazar</button>
                          </form>
                        </div>
                      </td>
                    </tr>
                  @endforeach
                </tbody>
              </table>
            </div>
          @else
            <p class="text-sm text-muted mb-0">No hay pagos pendientes por aprobación en este momento.</p>
          @endif
        </div>
      </div>
    </div>
  </div>

  <div class="row">
    <div class="col-12">
      <div class="card my-4">
        <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
          <div class="bg-gradient-dark shadow-dark border-radius-lg pt-3 pb-3 d-flex justify-content-between align-items-center">
            <h6 class="text-white text-capitalize ps-3 mb-0">Histórico de pagos de tiendas</h6>
            <span class="badge bg-light text-dark me-3">{{ $payments->count() }}</span>
          </div>
        </div>
        <div class="card-body px-0 pb-2">
          <div class="table-responsive p-0">
            <table class="table align-items-center mb-0">
              <thead>
                <tr>
                  <th>Tienda</th>
                  <th>Plan</th>
                  <th>Monto</th>
                  <th>Estado</th>
                  <th>Fecha pago</th>
                  <th>Fecha corte</th>
                  <th>Referencia</th>
                  <th>Archivo</th>
                  <th>Revisado por</th>
                </tr>
              </thead>
              <tbody>
                @forelse($payments as $payment)
                  <tr>
                    @php
                      $resolvedCutoffDate = null;
                      if (!is_null($payment->expires_at)) {
                        $resolvedCutoffDate = \Carbon\Carbon::parse($payment->expires_at);
                      } elseif (!is_null($payment->paid_at)) {
                        $resolvedCutoffDate = \Carbon\Carbon::parse($payment->paid_at)->addDays((int) ($payment->plan->duration_days ?? 0));
                      }
                    @endphp
                    <td>{{ $payment->tenant->name ?? 'N/A' }}</td>
                    <td>{{ $payment->plan->name ?? 'N/A' }}</td>
                    <td>${{ number_format((float) ($payment->amount ?? 0), 2) }}</td>
                    <td>
                      @php
                        $statusClass = $payment->status === 'paid' ? 'bg-success' : ($payment->status === 'pending' ? 'bg-warning text-dark' : 'bg-danger');
                        $statusLabel = $payment->status === 'paid' ? 'Aprobado' : ($payment->status === 'pending' ? 'Pendiente' : 'Rechazado');
                      @endphp
                      <span class="badge {{ $statusClass }}">{{ $statusLabel }}</span>
                    </td>
                    <td>{{ optional($payment->paid_at)->format('d/m/Y H:i') ?? '-' }}</td>
                    <td>
                      @if($payment->status === 'paid')
                        <form method="POST" action="{{ route('tenant.planPayment.cutoff.update', ['tenant' => $payment->tenant_id, 'payment' => $payment->id]) }}" class="d-flex align-items-center gap-2">
                          @csrf
                          <input
                            type="datetime-local"
                            name="expires_at"
                            value="{{ optional($resolvedCutoffDate)->format('Y-m-d\\TH:i') }}"
                            class="form-control form-control-sm"
                            required
                          >
                          <button type="submit" class="btn btn-outline-dark btn-sm mb-0">Guardar</button>
                        </form>
                      @else
                        {{ optional($resolvedCutoffDate)->format('d/m/Y H:i') ?? '-' }}
                      @endif
                    </td>
                    <td>{{ $payment->payment_reference ?? 'Sin referencia' }}</td>
                    <td>
                      @if(!empty($payment->payment_proof))
                        @if(\App\Support\ImageStorage::isGooglePath($payment->payment_proof))
                          <span class="badge bg-success">Drive</span>
                        @else
                          <span class="badge bg-secondary">Local</span>
                        @endif
                      @else
                        -
                      @endif
                    </td>
                    <td>{{ $payment->reviewer->name ?? '-' }}</td>
                  </tr>
                @empty
                  <tr>
                    <td colspan="9" class="text-center text-muted py-3">No hay pagos registrados.</td>
                  </tr>
                @endforelse
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection
