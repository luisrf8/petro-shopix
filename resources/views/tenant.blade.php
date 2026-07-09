@extends('layouts.app')

@section('title', 'Tiendas')

@section('content')
<style>
  .tenant-hero-card {
    overflow: hidden;
    border: 1px solid rgba(15, 23, 42, 0.08);
    background:
      radial-gradient(circle at top right, rgba(248, 113, 113, 0.12), transparent 32%),
      linear-gradient(135deg, #fff9f3 0%, #ffffff 58%, #eef6ff 100%);
  }

  .tenant-stat-card {
    height: 100%;
    border: 1px solid rgba(15, 23, 42, 0.08);
    border-radius: 1rem;
    background: #ffffff;
    box-shadow: 0 16px 34px rgba(15, 23, 42, 0.05);
  }

  .tenant-stat-eyebrow {
    display: inline-flex;
    align-items: center;
    gap: 0.4rem;
    padding: 0.35rem 0.65rem;
    border-radius: 999px;
    background: rgba(15, 23, 42, 0.06);
    color: #334155;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.04em;
    text-transform: uppercase;
  }

  .tenant-stat-number {
    font-size: clamp(1.9rem, 2.8vw, 2.6rem);
    font-weight: 800;
    color: #0f172a;
    line-height: 1;
  }

  .pending-payments-focus {
    box-shadow: 0 0 0 2px rgba(255, 193, 7, 0.55) !important;
    transition: box-shadow .25s ease;
  }

  .tenant-delete-overlay {
    position: fixed;
    inset: 0;
    z-index: 2000;
    display: none;
    align-items: center;
    justify-content: center;
    background: rgba(15, 23, 42, 0.45);
    backdrop-filter: blur(3px);
  }

  .tenant-delete-overlay.is-visible {
    display: flex;
  }

  .tenant-delete-overlay-card {
    min-width: min(92vw, 360px);
    padding: 1.25rem 1.35rem;
    border-radius: 1rem;
    background: #ffffff;
    box-shadow: 0 24px 60px rgba(15, 23, 42, 0.2);
    text-align: center;
  }

  .tenant-delete-overlay-spinner {
    width: 2.75rem;
    height: 2.75rem;
    margin: 0 auto 0.8rem;
    border-radius: 50%;
    border: 4px solid rgba(15, 23, 42, 0.12);
    border-top-color: #0f172a;
    animation: tenantDeleteSpin 0.8s linear infinite;
  }

  @keyframes tenantDeleteSpin {
    to {
      transform: rotate(360deg);
    }
  }

  .tenant-table-wrapper {
    overflow-x: auto;
    padding-bottom: 0.35rem;
    -webkit-overflow-scrolling: touch;
  }

  .tenant-admin-table {
    min-width: 1380px;
  }

  .tenant-admin-table th,
  .tenant-admin-table td {
    vertical-align: top;
    white-space: normal;
  }

  .tenant-plan-cell {
    min-width: 320px;
  }

  .tenant-url-cell {
    min-width: 220px;
    max-width: 260px;
  }

  .tenant-url-link {
    display: inline-block;
    max-width: 100%;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    vertical-align: top;
  }

  .tenant-alert-list {
    display: grid;
    gap: 0.85rem;
  }

  .tenant-alert-item {
    display: flex;
    justify-content: space-between;
    gap: 1rem;
    padding: 0.8rem 0.95rem;
    border-radius: 0.9rem;
    border: 1px solid rgba(15, 23, 42, 0.08);
    background: rgba(255, 255, 255, 0.82);
  }

  .tenant-alert-item strong {
    color: #0f172a;
  }

  .tenant-actions-cell {
    min-width: 120px;
    white-space: nowrap;
    vertical-align: middle !important;
    text-align: center;
    background: #fff;
  }

  .tenant-actions-cell--edit,
  .tenant-actions-cell--delete,
  .tenant-actions-head--edit,
  .tenant-actions-head--delete {
    position: sticky;
    z-index: 2;
    background: #fff;
  }

  .tenant-actions-cell--delete,
  .tenant-actions-head--delete {
    right: 0;
    min-width: 120px;
  }

  .tenant-actions-cell--edit,
  .tenant-actions-head--edit {
    right: 120px;
    min-width: 120px;
  }

  .tenant-actions-head--edit,
  .tenant-actions-head--delete {
    background: #f8f9fa;
    z-index: 3;
  }

  @media (max-width: 991.98px) {
    .tenant-hero-card .card-body {
      padding: 1.2rem;
    }
  }
</style>
<div class="container-fluid py-2">
  @php
    $pendingPayments = $tenants
      ->flatMap(function ($tenant) {
        return $tenant->tenantPlanPayments
          ->where('status', 'pending')
          ->map(function ($payment) use ($tenant) {
            $payment->tenant_name = $tenant->name;
            $payment->tenant_slug = $tenant->slug;
            return $payment;
          });
      })
      ->sortByDesc('created_at')
      ->values();

    $activeTenantsCount = $tenants->where('is_active', 1)->count();
    $inactiveTenantsCount = $tenants->count() - $activeTenantsCount;
    $digitalBillingCount = $tenants->filter(fn ($tenant) => (bool) ($tenant->electronic_invoicing_enabled ?? false))->count();
    $specialTaxpayerCount = $tenants->filter(fn ($tenant) => (bool) ($tenant->special_taxpayer ?? false))->count();
  @endphp

  <div class="row mb-4">
    <div class="col-12">
      <div class="card tenant-hero-card">
        <div class="card-body p-4">
          <div class="row g-3 align-items-stretch">
            <div class="col-xl-4">
              <div class="h-100 d-flex flex-column justify-content-between">
                <div>
                  <span class="tenant-stat-eyebrow">Admin Superior</span>
                  <h3 class="mt-3 mb-2">Dashboard operativo de tiendas</h3>
                  <p class="text-sm text-muted mb-0">
                    Vista rápida para seguimiento comercial, cobros pendientes y estado de facturación digital en todo SHOPIX.
                  </p>
                </div>
                <div class="d-flex flex-wrap gap-2 mt-3">
                  <a href="{{ route('tenant.payments.index') }}#pending-payments-section" class="btn btn-dark btn-sm mb-0">Ver pagos pendientes</a>
                  <a href="{{ route('electronic.documents.index') }}" class="btn btn-outline-dark btn-sm mb-0">Ver facturación digital</a>
                </div>
              </div>
            </div>
            <div class="col-sm-6 col-xl-2">
              <div class="tenant-stat-card p-3">
                <span class="tenant-stat-eyebrow">Tiendas</span>
                <div class="tenant-stat-number mt-3">{{ $tenants->count() }}</div>
                <p class="text-sm text-muted mb-0 mt-2">{{ $activeTenantsCount }} activas y {{ $inactiveTenantsCount }} inactivas.</p>
              </div>
            </div>
            <div class="col-sm-6 col-xl-2">
              <div class="tenant-stat-card p-3">
                <span class="tenant-stat-eyebrow">Cobranza</span>
                <div class="tenant-stat-number mt-3">{{ $pendingPayments->count() }}</div>
                <p class="text-sm text-muted mb-0 mt-2">Pagos de planes esperando revisión.</p>
              </div>
            </div>
            <div class="col-sm-6 col-xl-2">
              <div class="tenant-stat-card p-3">
                <span class="tenant-stat-eyebrow">Vencimientos</span>
                <div class="tenant-stat-number mt-3">{{ $nearDueTenants->count() + $overdueTenants->count() }}</div>
                <p class="text-sm text-muted mb-0 mt-2">{{ $nearDueTenants->count() }} por vencer y {{ $overdueTenants->count() }} vencidas.</p>
              </div>
            </div>
            <div class="col-sm-6 col-xl-2">
              <div class="tenant-stat-card p-3">
                <span class="tenant-stat-eyebrow">Fiscal</span>
                <div class="tenant-stat-number mt-3">{{ $digitalBillingCount }}</div>
                <p class="text-sm text-muted mb-0 mt-2">Con facturación digital y {{ $specialTaxpayerCount }} contribuyentes especiales.</p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div class="row mb-3" id="pending-payments-section">
    <div class="col-12">
      <div class="card border" id="pending-payments-card">
        <div class="card-body py-3">
          <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="mb-0">Pagos pendientes por aprobación</h6>
            <span class="badge bg-warning text-dark">{{ $pendingPayments->count() }}</span>
          </div>

          @if(($pendingPayments->count() ?? 0) > 0)
            <div class="table-responsive">
              <table class="table align-items-center mb-0">
                <thead>
                  <tr>
                    <th>Tienda</th>
                    <th>Plan</th>
                    <th>Monto</th>
                    <th>Referencia</th>
                    <th>Enviado</th>
                    <th>Acción</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($pendingPayments as $pending)
                    <tr>
                      <td>{{ $pending->tenant_name }}</td>
                      <td>{{ $pending->plan->name ?? 'N/A' }}</td>
                      <td>${{ number_format((float) ($pending->amount ?? 0), 2) }}</td>
                      <td>{{ $pending->payment_reference ?? 'Sin referencia' }}</td>
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

  <div class="row mb-3" id="billing-overview-section">
    <div class="col-lg-6 mb-3 mb-lg-0">
      <div class="card border">
        <div class="card-body py-3">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0">Alertas de cobro próximas (7 días)</h6>
            <span class="badge bg-warning text-dark">{{ $nearDueTenants->count() }}</span>
          </div>
          @if(($nearDueTenants->count() ?? 0) > 0)
            <div class="tenant-alert-list">
              @foreach($nearDueTenants as $nearTenant)
                <div class="tenant-alert-item">
                  <div>
                    <strong>{{ $nearTenant->name }}</strong>
                    <div class="text-sm text-muted">{{ $nearTenant->email ?? 'Sin correo registrado' }}</div>
                  </div>
                  <span class="badge bg-warning text-dark align-self-start">{{ (int) $nearTenant->plan_days_remaining }} días</span>
                </div>
              @endforeach
            </div>
          @else
            <p class="text-sm text-muted mb-0">No hay tiendas próximas de pago dentro de los próximos 7 días.</p>
          @endif
        </div>
      </div>
    </div>

    <div class="col-lg-6">
      <div class="card border">
        <div class="card-body py-3">
          <div class="d-flex justify-content-between align-items-center mb-3">
            <h6 class="mb-0">Alertas vencidas</h6>
            <span class="badge bg-danger">{{ $overdueTenants->count() }}</span>
          </div>
          @if(($overdueTenants->count() ?? 0) > 0)
            <div class="tenant-alert-list">
              @foreach($overdueTenants as $overTenant)
                <div class="tenant-alert-item">
                  <div>
                    <strong>{{ $overTenant->name }}</strong>
                    <div class="text-sm text-muted">Requiere contacto y regularización del plan.</div>
                  </div>
                  <span class="badge bg-danger align-self-start">{{ abs((int) $overTenant->plan_days_remaining) }} días</span>
                </div>
              @endforeach
            </div>
          @else
            <p class="text-sm text-muted mb-0">No hay tiendas vencidas actualmente.</p>
          @endif
        </div>
      </div>
    </div>
  </div>

  <!-- Tabla para mostrar tenants -->
  <div class="row">
    <div class="col-12">
      <div class="card my-4">
        <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
          <div class="bg-gradient-dark shadow-dark border-radius-lg pt-4 pb-3 d-flex justify-content-between align-items-center">
            <h6 class="text-white text-capitalize ps-3">TIENDAS</h6>
            <a href="/create-tenant" blank="_blank">
              <div class="py-1 px-3 text-end">
                <label class="text-white">+ Agregar Tienda</label>
              </div>
            </a>
          </div>
        </div>
        <div class="card-body px-0 pb-2">
          <div class="table-responsive p-0 tenant-table-wrapper">
            <table class="table align-items-center mb-0 tenant-admin-table">
              <thead class="text-center">
                <tr>
                  <th>Logo</th>
                  <th>Nombre</th>
                  <th class="tenant-url-cell">URL</th>
                  <th>Email</th>
                  <th>Tipo</th>
                  <th>Rubro</th>
                  <th>Facturación digital</th>
                  <th>Contribuyente especial</th>
                  <th>Envío solo ciudad tienda</th>
                  <th>Estado</th>
                  <th class="tenant-plan-cell">Plan</th>
                  <th class="tenant-actions-head--edit">Editar</th>
                  <th class="tenant-actions-head--delete">Eliminar</th>
                </tr>
              </thead>
              <tbody class="text-center">
                @foreach($tenants as $tenant)
                  <tr>
                    <td>
                      <img src="{{ $tenant->logo ? (\App\Support\ImageStorage::url($tenant->logo) ?? asset('assets/img/shopix5.png')) : asset('assets/img/shopix5.png') }}" alt="Logo"
                      class="navbar-brand-img"
                      width="64"
                      height="64"
                      alt="main_logo"
                      style="object-fit: contain;">
                    </td>
                    <td>{{ $tenant->name }}</td>
                    <td class="tenant-url-cell">
                      <a class="tenant-url-link" href="{{ route('tenant.public', $tenant) }}" target="_blank" rel="noopener">
                        {{ route('tenant.public', $tenant) }}
                      </a>
                    </td>
                    <td>{{ $tenant->email }}</td>
                    <td>{{ $tenant->business_type ?? 'No definido' }}</td>
                    <td>{{ $tenant->economic_activity ?? 'No definido' }}</td>
                    <td>
                      @if((bool) ($tenant->electronic_invoicing_enabled ?? false))
                        <span class="badge bg-success">Activa</span>
                      @else
                        <span class="badge bg-secondary">Inactiva</span>
                      @endif
                    </td>
                    <td>
                      @if((bool) ($tenant->special_taxpayer ?? false))
                        <span class="badge bg-warning text-dark">Activo</span>
                      @else
                        <span class="badge bg-secondary">Inactivo</span>
                      @endif
                    </td>
                    <td>
                      @if((bool) ($tenant->restrict_delivery_city_to_tenant ?? true))
                        <span class="badge bg-success">Activa</span>
                      @else
                        <span class="badge bg-secondary">Inactiva</span>
                      @endif
                    </td>

                    <td>
                      @if((int) ($tenant->is_active ?? 1) === 1)
                        <span class="badge bg-success">Activa</span>
                      @else
                        <span class="badge bg-danger">Inactiva</span>
                      @endif
                    </td>

                    <td class="tenant-plan-cell">
                      @php
                        $owner = $tenant->owner_user;
                        $latestPayment = $tenant->latest_paid_plan_payment;
                        $latestPendingPayment = $tenant->latest_pending_plan_payment;
                      @endphp
                      <p class="mb-1">Dueño: {{ $owner?->name ?? 'Sin dueño' }}</p>
                      <p class="mb-1">Usuarios: {{ $tenant->users_count_snapshot }}</p>
                      @if($latestPayment)
                        @php
                          $daysRemaining = null;
                          $resolvedCutoffDate = null;
                          if (!is_null($latestPayment->expires_at)) {
                              $resolvedCutoffDate = \Carbon\Carbon::parse($latestPayment->expires_at);
                          } elseif (!is_null($latestPayment->paid_at)) {
                              $resolvedCutoffDate = \Carbon\Carbon::parse($latestPayment->paid_at)->addDays((int) ($latestPayment->plan->duration_days ?? 0));
                          }

                          if (!is_null($resolvedCutoffDate)) {
                              $expires = $resolvedCutoffDate;
                              $now = now();
                              $daysRemaining = $expires->greaterThanOrEqualTo($now)
                                  ? $now->diffInDays($expires)
                                  : (-1 * $expires->diffInDays($now));
                          }
                        @endphp
                        <p class="mb-1">Plan actual: {{ $latestPayment->plan->name }} - ${{ $latestPayment->amount }} - Estado: {{ $latestPayment->status }}</p>
                        <p class="mb-1">Vence: {{ optional($resolvedCutoffDate)->format('d/m/Y H:i') ?? 'Sin fecha' }}</p>
                        <p class="mb-1">
                          Días restantes:
                          @if(is_null($daysRemaining))
                            Sin vigencia
                          @elseif($daysRemaining < 0)
                            Vencido hace {{ abs($daysRemaining) }} días
                          @else
                            {{ $daysRemaining }} días
                          @endif
                        </p>
                      @else
                        <p class="mb-1">Plan actual: Sin plan</p>
                        <p class="mb-1">Vence: Sin fecha</p>
                      @endif

                      @if($latestPendingPayment)
                        <hr class="my-2">
                        <p class="mb-1"><strong>Solicitud pendiente:</strong> {{ $latestPendingPayment->plan->name ?? 'N/A' }} - ${{ number_format((float) ($latestPendingPayment->amount ?? 0), 2) }}</p>
                        <p class="mb-1"><strong>Referencia:</strong> {{ $latestPendingPayment->payment_reference ?? 'Sin referencia' }}</p>
                        <p class="mb-2"><strong>Enviada:</strong> {{ optional($latestPendingPayment->created_at)->format('d/m/Y H:i') ?? 'Sin fecha' }}</p>

                        @if(!empty($latestPendingPayment->payment_proof))
                          <p class="mb-2">
                            <a href="{{ \App\Support\ImageStorage::url($latestPendingPayment->payment_proof) }}" target="_blank" rel="noopener" class="btn btn-outline-dark btn-sm mb-0">
                              Ver comprobante
                            </a>
                          </p>
                        @endif

                        <div class="d-flex flex-column gap-2">
                          <form method="POST" action="{{ route('tenant.planPayment.approve', ['tenant' => $tenant->id, 'payment' => $latestPendingPayment->id]) }}">
                            @csrf
                            <button type="submit" class="btn btn-success btn-sm mb-0 w-100">Aprobar pago</button>
                          </form>
                          <form method="POST" action="{{ route('tenant.planPayment.reject', ['tenant' => $tenant->id, 'payment' => $latestPendingPayment->id]) }}" data-requires-action-reason="true" data-reason-field="review_notes" data-reason-prompt="Indica el motivo para rechazar este pago de plan.">
                            @csrf
                            <button type="submit" class="btn btn-outline-danger btn-sm mb-0 w-100">Rechazar pago</button>
                          </form>
                        </div>
                      @endif
                      {{-- O solo plan activo --}}
                      {{-- <p>Plan activo: {{ $tenant->activePlanPayment->plan->name ?? 'Sin plan' }}</p> --}}
                    </td>

                    <td class="tenant-actions-cell tenant-actions-cell--edit">
                      <a href="{{ route('tenants.edit', $tenant) }}" class="btn btn-outline-dark btn-sm mb-0">Editar</a>
                    </td>
                    <td class="tenant-actions-cell tenant-actions-cell--delete">
                      <button type="button"
                         class="btn btn-link text-danger font-weight-bold text-xs p-0 btn-delete-tenant"
                         data-id="{{ $tenant->id }}">
                        Eliminar
                      </button>
                    </td>
                  </tr>
                @endforeach
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  </div>

  <div id="tenantDeleteOverlay" class="tenant-delete-overlay" aria-hidden="true">
    <div class="tenant-delete-overlay-card">
      <div class="tenant-delete-overlay-spinner" role="status" aria-label="Eliminando tienda"></div>
      <h6 class="mb-2">Eliminando tienda</h6>
      <p class="mb-0 text-muted">Procesando dependencias y limpieza de registros. No cierres esta pantalla.</p>
    </div>
  </div>

</div>
@endsection

@push('scripts')
<script>
  const tenantDeleteOverlay = document.getElementById('tenantDeleteOverlay');
  const tenantDeleteButtons = Array.from(document.querySelectorAll('.btn-delete-tenant'));

  function setTenantDeleteLoading(isLoading, sourceButton = null) {
    if (tenantDeleteOverlay) {
      tenantDeleteOverlay.classList.toggle('is-visible', isLoading);
      tenantDeleteOverlay.setAttribute('aria-hidden', isLoading ? 'false' : 'true');
    }

    tenantDeleteButtons.forEach((button) => {
      button.disabled = isLoading;
      if (!sourceButton || button !== sourceButton) {
        button.style.pointerEvents = isLoading ? 'none' : '';
        button.style.opacity = isLoading ? '0.6' : '';
      }
    });
  }

  // Eliminar Tenant
  tenantDeleteButtons.forEach(button => {
    button.addEventListener('click', function () {
      const tenantId = this.dataset.id;
      if (!tenantId) return;

      if (!window.confirm('¿Eliminar esta tienda? Si tiene datos asociados, el proceso puede tardar unos segundos.')) {
        return;
      }

      const originalText = this.textContent;
      this.textContent = 'Eliminando...';
      setTenantDeleteLoading(true, this);

      fetch(`/api/tenants/${tenantId}`, {
        method: 'DELETE',
        headers: {
          'Accept': 'application/json',
          'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
        }
      })
      .then(async response => {
        const data = await response.json();
        if (!response.ok) {
          throw new Error(data.message || 'Ocurrió un error al eliminar la tienda');
        }
        return data;
      })
      .then(data => {
        alert('Tienda eliminada correctamente');
        window.location.reload();
      })
      .catch(error => {
        console.error('Error:', error);
        alert(error.message || 'Ocurrió un error al eliminar la tienda');
      })
      .finally(() => {
        setTenantDeleteLoading(false);
        this.textContent = originalText;
      });
    });
  });

  if (window.location.hash === '#pending-payments-section') {
    const pendingCard = document.getElementById('pending-payments-card');
    if (pendingCard) {
      pendingCard.classList.add('pending-payments-focus');
      setTimeout(() => pendingCard.classList.remove('pending-payments-focus'), 2400);
    }
  }
</script>
@endpush
