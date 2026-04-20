@extends('layouts.app')

@section('title', 'Tiendas')

@section('content')
<style>
  .pending-payments-focus {
    box-shadow: 0 0 0 2px rgba(255, 193, 7, 0.55) !important;
    transition: box-shadow .25s ease;
  }

  .tenant-table-wrapper {
    overflow-x: auto;
  }

  .tenant-admin-table {
    min-width: 1650px;
  }

  .tenant-admin-table th,
  .tenant-admin-table td {
    vertical-align: top;
    white-space: normal;
  }

  .tenant-plan-cell {
    min-width: 360px;
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
  @endphp

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
                          <form method="POST" action="{{ route('tenant.planPayment.reject', ['tenant' => $pending->tenant_id, 'payment' => $pending->id]) }}">
                            @csrf
                            <input type="hidden" name="review_notes" value="Pago rechazado por administración.">
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
                  <th>URL</th>
                  <th>Email</th>
                  <th>Tipo</th>
                  <th>Rubro</th>
                  <th>Facturación digital</th>
                  <th>Contribuyente especial</th>
                  <th>Envío solo ciudad tienda</th>
                  <th>Estado</th>
                  <th class="tenant-plan-cell">Plan</th>
                  <th>Editar</th>
                  <th>Eliminar</th>
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
                    <td>{{ $tenant->slug }}</td>
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
                        $owner = $tenant->users->first(function($user) {
                          return optional($user->role)->name === 'owner';
                        }) ?? $tenant->users->first();

                        $latestPayment = $tenant->tenantPlanPayments
                          ->where('status', 'paid')
                          ->sortBy(function ($payment) {
                            return optional($payment->paid_at)->timestamp ?? 0;
                          })
                          ->last();

                        $latestPendingPayment = $tenant->tenantPlanPayments
                          ->where('status', 'pending')
                          ->sortBy(function ($payment) {
                            return optional($payment->created_at)->timestamp ?? 0;
                          })
                          ->last();
                      @endphp
                      <p class="mb-1">Dueño: {{ $owner?->name ?? 'Sin dueño' }}</p>
                      <p class="mb-1">Usuarios: {{ $tenant->users->count() }}</p>
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
                          <form method="POST" action="{{ route('tenant.planPayment.reject', ['tenant' => $tenant->id, 'payment' => $latestPendingPayment->id]) }}">
                            @csrf
                            <input type="hidden" name="review_notes" value="Pago rechazado por administración.">
                            <button type="submit" class="btn btn-outline-danger btn-sm mb-0 w-100">Rechazar pago</button>
                          </form>
                        </div>
                      @endif
                      {{-- O solo plan activo --}}
                      {{-- <p>Plan activo: {{ $tenant->activePlanPayment->plan->name ?? 'Sin plan' }}</p> --}}
                    </td>

                    <td>
                      <a href="javascript:;" 
                        class="text-secondary font-weight-bold text-xs btn-edit-tenant"
                        data-bs-toggle="modal" 
                        data-bs-target="#editTenantModal" 
                        data-id="{{ $tenant->id }}"
                        data-name="{{ $tenant->name }}"
                        data-slug="{{ $tenant->slug }}"
                        data-email="{{ $tenant->email }}"
                        data-logo="{{ $tenant->logo }}"
                        data-logo-url="{{ $tenant->logo ? (\App\Support\ImageStorage::url($tenant->logo) ?? asset('assets/img/shopix5.png')) : asset('assets/img/shopix5.png') }}"
                        data-business-type="{{ \Illuminate\Support\Str::lower((string) ($tenant->business_type ?? 'tienda')) }}"
                        data-economic-activity="{{ $tenant->economic_activity ?? '' }}"
                        data-owner-name="{{ $owner?->name }}"
                        data-owner-email="{{ $owner?->email }}"
                        data-plan-id="{{ $latestPayment?->plan_id }}"
                        data-electronic-invoicing-enabled="{{ (int) (($tenant->electronic_invoicing_enabled ?? false) ? 1 : 0) }}"
                        data-special-taxpayer="{{ (int) (($tenant->special_taxpayer ?? false) ? 1 : 0) }}"
                        data-printer-tax-change-enabled="{{ (int) (($tenant->printer_tax_change_enabled ?? false) ? 1 : 0) }}"
                        data-printer-tax-change-reference="{{ $tenant->printer_tax_change_reference ?? '' }}"
                        data-restrict-delivery-city-to-tenant="{{ (int) (($tenant->restrict_delivery_city_to_tenant ?? true) ? 1 : 0) }}"
                        data-working-days='@json($tenant->working_days ?? [])'
                        data-opening-time="{{ !empty($tenant->opening_time) ? \Illuminate\Support\Str::substr((string) $tenant->opening_time, 0, 5) : '' }}"
                        data-closing-time="{{ !empty($tenant->closing_time) ? \Illuminate\Support\Str::substr((string) $tenant->closing_time, 0, 5) : '' }}"
                        data-active="{{ $tenant->is_active }}">
                        Editar
                      </a>
                    </td>
                    <td>
                      <a href="javascript:;" 
                         class="text-danger font-weight-bold text-xs btn-delete-tenant"
                         data-id="{{ $tenant->id }}">
                        Eliminar
                      </a>
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

  <!-- Modal para editar tenant -->
  <div class="modal fade" id="editTenantModal" tabindex="-1" aria-labelledby="editTenantModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="editTenantModalLabel">Editar Tienda</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="editTenantForm" enctype="multipart/form-data">
            @csrf
            <input type="hidden" id="editTenantId" name="id">
            <div class="mb-3">
              <label for="editTenantLogo" class="form-label">Logo</label>
              <div class="text-center mb-2">
                <img id="editTenantLogoPreview" 
                    src="{{ asset('assets/img/shopix5.png') }}" 
                    alt="Logo"
                    width="100"
                    height="100"
                    style="object-fit: contain; border: 1px solid #ddd; border-radius: 8px;">
              </div>
              <input type="text" class="form-control border border-1 p-2" id="editTenantLogo" name="logo" type="hidden" style="display: none;">
            </div>
            <div class="mb-3">
              <label for="editTenantName" class="form-label">Nombre</label>
              <input type="text" class="form-control border border-1 p-2" id="editTenantName" name="name" required>
            </div>
            <div class="mb-3">
              <label for="editTenantSlug" class="form-label">Slug</label>
              <input type="text" class="form-control border border-1 p-2" id="editTenantSlug" name="slug" required>
            </div>
            <div class="mb-3">
              <label for="editTenantEmail" class="form-label">Email</label>
              <input type="email" class="form-control border border-1 p-2" id="editTenantEmail" name="email">
            </div>

            <div class="mb-3">
              <label for="editTenantBusinessType" class="form-label">Tipo de negocio</label>
              <select class="form-select border border-1 p-2" id="editTenantBusinessType" name="business_type" required>
                <option value="">Selecciona una opción</option>
                <option value="tienda">Tienda</option>
                <option value="servicio">Servicio</option>
              </select>
            </div>

            <div class="mb-3">
              <label for="editTenantEconomicActivity" class="form-label">Rubro económico</label>
              <select class="form-select border border-1 p-2" id="editTenantEconomicActivity" name="economic_activity" required>
                <option value="">Selecciona un rubro</option>
              </select>
              <small id="editTenantEconomicActivityHelp" class="text-muted d-block mt-1"></small>
            </div>

            <div class="mb-3">
              <label for="editOwnerName" class="form-label">Nombre dueño</label>
              <input type="text" class="form-control border border-1 p-2" id="editOwnerName" name="owner_name">
            </div>

            <div class="mb-3">
              <label for="editOwnerEmail" class="form-label">Email dueño</label>
              <input type="email" class="form-control border border-1 p-2" id="editOwnerEmail" name="owner_email">
            </div>

            <div class="mb-3">
              <label for="editOwnerPassword" class="form-label">Nueva contraseña dueño (opcional)</label>
              <input type="password" class="form-control border border-1 p-2" id="editOwnerPassword" name="owner_password" autocomplete="new-password">
            </div>

            <div class="mb-3">
              <label for="editTenantPlan" class="form-label">Plan</label>
              <select class="form-select border border-1 p-2" id="editTenantPlan" name="plan_id" required>
                <option value="">Selecciona un plan</option>
                @foreach($plans as $plan)
                  <option value="{{ $plan->id }}">{{ $plan->name }} - ${{ $plan->price }}</option>
                @endforeach
              </select>
            </div>

            <div class="mb-3">
              <label for="editTenantStatus" class="form-label">Estado</label>
              <select class="form-select border border-1 p-2" id="editTenantStatus" name="is_active" required>
                <option value="1">Activo</option>
                <option value="0">Inactivo</option>
              </select>
            </div>

            <div class="mb-3">
              <label for="editTenantElectronicInvoicingEnabled" class="form-label">Facturación digital</label>
              <select class="form-select border border-1 p-2" id="editTenantElectronicInvoicingEnabled" name="electronic_invoicing_enabled" required>
                <option value="1">Activa</option>
                <option value="0">Inactiva</option>
              </select>
              <small class="text-muted">Controla si la tienda usa integración de facturación electrónica.</small>
            </div>

            <div class="mb-3">
              <label for="editTenantSpecialTaxpayer" class="form-label">Contribuyente especial</label>
              <select class="form-select border border-1 p-2" id="editTenantSpecialTaxpayer" name="special_taxpayer" required>
                <option value="1">Activo</option>
                <option value="0">Inactivo</option>
              </select>
              <small class="text-muted">Si está activo, la tienda no aplicará IGTF.</small>
            </div>

            <div class="mb-3">
              <label for="editTenantPrinterTaxChangeEnabled" class="form-label">Habilitación imprenta para alícuotas</label>
              <select class="form-select border border-1 p-2" id="editTenantPrinterTaxChangeEnabled" name="printer_tax_change_enabled" required>
                <option value="1">Activa</option>
                <option value="0">Inactiva</option>
              </select>
              <small class="text-muted">Solo debe activarse cuando la imprenta autorice cambios de alícuotas.</small>
            </div>

            <div class="mb-3">
              <label for="editTenantPrinterTaxChangeReference" class="form-label">Referencia imprenta</label>
              <input type="text" class="form-control border border-1 p-2" id="editTenantPrinterTaxChangeReference" name="printer_tax_change_reference" placeholder="Providencia o referencia de aprobación">
            </div>

            <div class="mb-3">
              <label for="editTenantRestrictDeliveryCityToTenant" class="form-label">Envío solo en ciudad de la tienda</label>
              <select class="form-select border border-1 p-2" id="editTenantRestrictDeliveryCityToTenant" name="restrict_delivery_city_to_tenant" required>
                <option value="1">Activa</option>
                <option value="0">Inactiva</option>
              </select>
              <small class="text-muted">Si está activa, solo permite envíos a la ciudad configurada de la tienda.</small>
            </div>

            <div class="mb-3">
              <label class="form-label">Días laborales (opcional)</label>
              <div class="row g-2">
                @php
                  $weekDays = [
                    'monday' => 'Lunes',
                    'tuesday' => 'Martes',
                    'wednesday' => 'Miércoles',
                    'thursday' => 'Jueves',
                    'friday' => 'Viernes',
                    'saturday' => 'Sábado',
                    'sunday' => 'Domingo',
                  ];
                @endphp
                @foreach($weekDays as $dayKey => $dayLabel)
                  <div class="col-6 col-md-4">
                    <div class="form-check">
                      <input class="form-check-input edit-tenant-working-day" type="checkbox" id="edit_working_day_{{ $dayKey }}" name="working_days[]" value="{{ $dayKey }}">
                      <label class="form-check-label" for="edit_working_day_{{ $dayKey }}">{{ $dayLabel }}</label>
                    </div>
                  </div>
                @endforeach
              </div>
            </div>

            <div class="row g-3 mb-3">
              <div class="col-12 col-md-6">
                <label for="editTenantOpeningTime" class="form-label">Hora de apertura</label>
                <input type="time" class="form-control border border-1 p-2" id="editTenantOpeningTime" name="opening_time">
              </div>
              <div class="col-12 col-md-6">
                <label for="editTenantClosingTime" class="form-label">Hora de cierre</label>
                <input type="time" class="form-control border border-1 p-2" id="editTenantClosingTime" name="closing_time">
              </div>
            </div>
            <div class="d-flex flex-row-reverse">
              <button type="submit" class="btn btn-info">Guardar</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  const tenantBusinessCatalog = {
    tienda: [
      'Alimentos y Bebidas',
      'Moda y Accesorios',
      'Hogar y Construccion',
      'Tecnologia',
      'Salud y Belleza',
      'Otros'
    ],
    servicio: [
      'Gastronomia',
      'Cuidado Personal',
      'Servicios Tecnicos',
      'Profesionales',
      'Logistica y Educacion'
    ]
  };

  const tenantBusinessExamples = {
    'Alimentos y Bebidas': 'Supermercados, Panaderias, Licorerias, Carnicerias.',
    'Moda y Accesorios': 'Ropa, Calzado, Joyeria, Opticas.',
    'Hogar y Construccion': 'Ferreterias, Mueblerias, Decoracion, Pinturerias.',
    'Tecnologia': 'Electronica, Computacion, Telefonia Movil.',
    'Salud y Belleza': 'Farmacias, Perfumerias, Cosmetica.',
    'Otros': 'Jugueterias, Librerias, Pet Shops (Mascotas).',
    'Gastronomia': 'Restaurantes, Cafeterias, Fast Food, Caterings.',
    'Cuidado Personal': 'Peluquerias, Centros de Estetica, Spas, Gimnasios.',
    'Servicios Tecnicos': 'Talleres mecanicos, Reparacion de electrodomesticos, Soporte IT.',
    'Profesionales': 'Consultorios medicos, Estudios contables/legales, Arquitectura.',
    'Logistica y Educacion': 'Mensajeria, Institutos de idiomas, Jardines de infantes.'
  };

  function populateTenantEconomicActivities(typeValue, selectedValue = '') {
    const businessType = String(typeValue || 'tienda').toLowerCase() === 'servicio' ? 'servicio' : 'tienda';
    const select = document.getElementById('editTenantEconomicActivity');
    const help = document.getElementById('editTenantEconomicActivityHelp');
    if (!select) {
      return;
    }

    const options = tenantBusinessCatalog[businessType] || [];
    select.innerHTML = '<option value="">Selecciona un rubro</option>';
    options.forEach((option) => {
      const selected = String(option).toLowerCase() === String(selectedValue || '').toLowerCase();
      select.insertAdjacentHTML('beforeend', `<option value="${option}" ${selected ? 'selected' : ''}>${option}</option>`);
    });

    const selectedOption = select.value;
    help.textContent = selectedOption && tenantBusinessExamples[selectedOption]
      ? `Ejemplos: ${tenantBusinessExamples[selectedOption]}`
      : 'Selecciona una categoria para ver ejemplos.';
  }

  document.getElementById('editTenantBusinessType')?.addEventListener('change', function () {
    populateTenantEconomicActivities(this.value, '');
  });

  document.getElementById('editTenantEconomicActivity')?.addEventListener('change', function () {
    const help = document.getElementById('editTenantEconomicActivityHelp');
    const selectedOption = this.value;
    help.textContent = selectedOption && tenantBusinessExamples[selectedOption]
      ? `Ejemplos: ${tenantBusinessExamples[selectedOption]}`
      : 'Selecciona una categoria para ver ejemplos.';
  });

  // Llenar modal para editar Tenant
  document.querySelectorAll('.btn-edit-tenant').forEach(button => {
    button.addEventListener('click', function () {
      document.getElementById('editTenantId').value = this.dataset.id;
      document.getElementById('editTenantName').value = this.dataset.name;
      document.getElementById('editTenantSlug').value = this.dataset.slug;
      document.getElementById('editTenantEmail').value = this.dataset.email;
      document.getElementById('editTenantBusinessType').value = this.dataset.businessType || 'tienda';
      populateTenantEconomicActivities(
        this.dataset.businessType || 'tienda',
        this.dataset.economicActivity || ''
      );
      document.getElementById('editTenantLogo').value = this.dataset.logo;
      document.getElementById('editOwnerName').value = this.dataset.ownerName || '';
      document.getElementById('editOwnerEmail').value = this.dataset.ownerEmail || '';
      document.getElementById('editOwnerPassword').value = '';
      document.getElementById('editTenantPlan').value = this.dataset.planId || '';
      document.getElementById('editTenantStatus').value = this.dataset.active || '1';
      document.getElementById('editTenantElectronicInvoicingEnabled').value = this.dataset.electronicInvoicingEnabled || '0';
      document.getElementById('editTenantSpecialTaxpayer').value = this.dataset.specialTaxpayer || '0';
      document.getElementById('editTenantPrinterTaxChangeEnabled').value = this.dataset.printerTaxChangeEnabled || '0';
      document.getElementById('editTenantPrinterTaxChangeReference').value = this.dataset.printerTaxChangeReference || '';
      document.getElementById('editTenantRestrictDeliveryCityToTenant').value = this.dataset.restrictDeliveryCityToTenant || '1';
      document.getElementById('editTenantOpeningTime').value = this.dataset.openingTime || '';
      document.getElementById('editTenantClosingTime').value = this.dataset.closingTime || '';

      const incomingWorkingDays = (() => {
        try {
          const parsed = JSON.parse(this.dataset.workingDays || '[]');
          return Array.isArray(parsed) ? parsed.map(day => String(day).toLowerCase()) : [];
        } catch (error) {
          return [];
        }
      })();

      document.querySelectorAll('.edit-tenant-working-day').forEach((checkbox) => {
        checkbox.checked = incomingWorkingDays.includes(String(checkbox.value || '').toLowerCase());
      });

      // 👇 Aquí actualizamos la vista previa del logo dinámicamente
      const logoPreview = document.getElementById('editTenantLogoPreview');
      const logoPath = this.dataset.logoUrl || '/assets/img/shopix5.png';
      logoPreview.src = logoPath;
    });
  });
  
  document.getElementById('editTenantLogo').addEventListener('input', function() {
    const logoPreview = document.getElementById('editTenantLogoPreview');
    const logoPath = this.value ? this.value : '/assets/img/shopix5.png';
    logoPreview.src = logoPath;
  });

  populateTenantEconomicActivities('tienda', '');


  // Actualizar Tenant
  document.getElementById('editTenantForm').addEventListener('submit', function(event) {
    event.preventDefault();
    const formData = new FormData(this);
    const tenantId = formData.get('id');
    fetch(`/api/tenants/${tenantId}`, {
      method: 'POST',
      headers: {
        'Accept': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
      },
      body: formData
    })
    .then(async response => {
      const data = await response.json();
      if (!response.ok) {
        throw new Error(data.message || 'Ocurrió un error al actualizar la tienda');
      }
      return data;
    })
    .then(data => {
      alert(data.message || 'Tienda actualizada correctamente');
      window.location.reload();
    })
    .catch(error => {
      console.error('Error:', error);
      alert(error.message || 'Ocurrió un error al actualizar la tienda');
    });
  });

  // Eliminar Tenant
  document.querySelectorAll('.btn-delete-tenant').forEach(button => {
    button.addEventListener('click', function () {
      const tenantId = this.dataset.id;
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
