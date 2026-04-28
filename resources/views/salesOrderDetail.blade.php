@extends('layouts.app')

@section('title', 'Categorías')

@section('content')
    @php
      $roleName = strtolower((string) optional(auth()->user()->role)->name);
      $currentUser = auth()->user();
      $salesOrderPlanCapabilities = \App\Support\TenantPlanCapabilities::forTenant($order->tenant ?? null);
      $isDeliveryOnlyView = ($currentUser?->hasStoreRole('delivery') ?? false)
        && !($currentUser?->hasStoreRole('owner', 'admin', 'seller', 'warehouse') ?? false);
      $edoc = $order->latest_electronic_document;
      $hasAnnulledInvoice = (bool) ($order->has_annulled_invoice ?? false);
      $canApproveSale = $currentUser?->hasStoreRole('owner', 'admin', 'seller') ?? false;
      $canApproveDelivery = $currentUser?->hasStoreRole('owner', 'admin', 'warehouse', 'delivery') ?? false;
      $canRegisterReturn = $currentUser?->hasStoreRole('owner', 'admin', 'seller') ?? false;
      $canApprovePayments = $currentUser?->hasStoreRole('owner', 'admin', 'seller') ?? false;
      $canDownloadPdfs = $currentUser?->hasStoreRole('owner', 'admin', 'seller') ?? false;
      $canManageAppointmentWorkflow = $currentUser?->hasStoreRole('owner', 'admin', 'seller') ?? false;
      $linkedAppointment = $linkedAppointment ?? null;
      $appointmentPaymentMethods = $appointmentPaymentMethods ?? collect();
      $resolveCurrencySymbol = function (?string $code) {
        $normalized = strtoupper(trim((string) $code));
        if ($normalized === 'EUR') {
          return '€';
        }

        if ($normalized === 'USD') {
          return '$';
        }

        return '';
      };

      $storePhone = preg_replace('/\D+/', '', (string) (($order->tenant->phone_code ?? '') . ($order->tenant->phone_number ?? '')));
      $customerPhone = preg_replace('/\D+/', '', (string) ($order->user->phone_number ?? ''));
      $storeWhatsappUrl = $storePhone !== ''
        ? 'https://wa.me/' . $storePhone . '?text=' . rawurlencode('Hola ' . ($order->tenant->name ?? 'tienda') . ', sobre la orden #' . $order->id . '.')
        : null;
      $customerWhatsappUrl = $customerPhone !== ''
        ? 'https://wa.me/' . $customerPhone . '?text=' . rawurlencode('Hola ' . ($order->user->name ?? 'cliente') . ', te escribimos sobre la orden #' . $order->id . '.')
        : null;
      $customerCallUrl = $customerPhone !== '' ? 'tel:' . $customerPhone : null;
      $deliveryTypeLabel = strtolower(trim((string) ($order->preference ?? '')));
      $isStoreDelivery = str_contains($deliveryTypeLabel, 'delivery');
      $isExternalShipping = str_contains($deliveryTypeLabel, 'env') || str_contains($deliveryTypeLabel, 'shipping');
      $deliveryOperationsLocked = !$salesOrderPlanCapabilities->allowsDeliveryOperations();
      $deliveryLabel = (int) $order->deliver_status === 0
        ? 'Pendiente'
        : ((int) $order->deliver_status === 1
          ? 'Entregado'
          : ((int) $order->deliver_status === 2 ? 'Cancelado' : 'En proceso'));
      $approvalLabel = $order->status == 0 ? 'En proceso' : ($order->status == 1 ? 'Aprobado' : 'Negado');
      $paymentBalance = max(0, (float) $totalOrden - (float) $totalPagado);
      $paymentStepTone = $totalPagado >= $totalOrden && $totalOrden > 0 ? 'success' : ($totalPagado > 0 ? 'pending' : 'danger');
      $assignedDeliveryName = trim((string) ($order->assignedDeliveryUser->name ?? ''));
      $assignedDeliveryPhone = trim((string) ($order->assignedDeliveryUser->phone_number ?? ''));
      $deliveryContactMeta = $assignedDeliveryName !== '' || $assignedDeliveryPhone !== ''
        ? 'Repartidor: ' . ($assignedDeliveryName !== '' ? $assignedDeliveryName : 'No identificado') . ' | Teléfono: ' . ($assignedDeliveryPhone !== '' ? $assignedDeliveryPhone : 'No registrado')
        : 'Aún no hay un repartidor asignado para esta orden.';
      $shippingProgressDescription = match ((int) $order->deliver_status) {
        1 => 'Paso 3 de 3: el envío figura como entregado correctamente.',
        2 => 'El envío fue cancelado antes de completar su despacho.',
        default => 'Paso 1 de 3: tu envío fue registrado y está en preparación para despacho.',
      };
      $shippingProgressMeta = match ((int) $order->deliver_status) {
        1 => 'Proceso: 1. Preparación  2. Despacho  3. Entregado',
        2 => 'Proceso interrumpido: 1. Preparación  2. Cancelado',
        default => 'Proceso: 1. Preparación  2. Despacho  3. Entrega final',
      };
      $timelineSteps = [
        [
          'tone' => 'success',
          'title' => 'Orden creada',
          'description' => 'La orden fue registrada el ' . ($order->date ?: 'sin fecha disponible') . '.',
          'meta' => 'Entrega: ' . ($order->preference ?: 'Sin preferencia definida'),
        ],
        [
          'tone' => $paymentStepTone,
          'title' => 'Pagos y validación',
          'description' => $totalPagado > 0
            ? 'Se registraron pagos por ' . (($orderCurrencySymbol ?? '$') . number_format($totalPagado, 2)) . '. Saldo pendiente: ' . (($orderCurrencySymbol ?? '$') . number_format($paymentBalance, 2)) . '.'
            : 'Aún no hay pagos confirmados para esta orden.',
          'meta' => $approvalLabel,
        ],
        [
          'tone' => (int) $order->deliver_status === 1 ? 'success' : ((int) $order->deliver_status === 0 ? 'pending' : 'danger'),
          'title' => $isExternalShipping ? 'Seguimiento del envío' : 'Entrega y despacho',
          'description' => $isExternalShipping
            ? $shippingProgressDescription
            : 'Estado actual del delivery: ' . $deliveryLabel . '.',
          'meta' => $isExternalShipping
            ? $shippingProgressMeta . ' | Dirección: ' . ($order->address ?: 'No registrada')
            : $deliveryContactMeta,
        ],
        [
          'tone' => $hasAnnulledInvoice ? 'danger' : ($edoc ? 'success' : 'pending'),
          'title' => 'Factura digital',
          'description' => $hasAnnulledInvoice
            ? 'La última factura electrónica fue anulada y no debe reutilizarse.'
            : ($edoc
              ? 'Factura electrónica emitida: ' . ($edoc->numero_documento ?: 'sin correlativo visible') . '.'
              : 'La factura electrónica todavía no ha sido emitida.'),
          'meta' => $edoc?->created_at ? 'Actualizada ' . $edoc->created_at->format('d/m/Y H:i') : 'Sin actualización registrada',
        ],
      ];
      $visibleTimelineSteps = $isDeliveryOnlyView
        ? array_values(array_filter($timelineSteps, fn (array $step) => in_array($step['title'], ['Orden creada', 'Entrega y despacho', 'Seguimiento del envío'], true)))
        : $timelineSteps;
      $appointmentStatusLabels = [
        'scheduled' => 'Programada',
        'confirmed' => 'Confirmada',
        'completed' => 'Completada',
        'cancelled' => 'Cancelada',
        'no_show' => 'No asistió',
      ];
      $appointmentPaymentStatusLabels = [
        'pending' => 'Pendiente',
        'partial' => 'Abono parcial',
        'paid' => 'Pagada',
        'waived' => 'Sin cobro',
      ];
    @endphp
    <div class="container-fluid">
      <input type="text" id="user-name" class="d-none" value="{{ $order->user->name }}" readonly>
      <input type="text" id="user-email" class="d-none" value="{{ $order->user->email }}" readonly>
      <input type="text" id="user-phone" class="d-none" value="{{ $order->user->phone_number ?? 'No registrado' }}" readonly>

      <div class="order-shell">
        <div class="card order-hero-panel mb-4">
          <div class="row g-4 align-items-start position-relative" style="z-index:1;">
            <div class="col-lg-7">
              <div class="order-eyebrow">Seguimiento inteligente de tu orden</div>
              <div class="order-hero-title">Orden #{{ $order->id }}</div>
              <div class="d-flex flex-wrap gap-2 mb-3">
                @unless($isDeliveryOnlyView)
                <span class="order-status-pill">{{ $approvalLabel }}</span>
                @endunless
                <span class="order-status-pill">{{ $deliveryLabel }}</span>
                @unless($isDeliveryOnlyView)
                <span class="order-status-pill">{{ $orderCurrencyCode ?? 'USD' }}</span>
                @if($hasAnnulledInvoice)
                  <span class="order-status-pill order-status-pill-danger">Factura anulada</span>
                @endif
                @endunless
              </div>
              <div class="order-meta-copy">
                @unless($isDeliveryOnlyView)
                {{ $order->user->name }} · {{ $order->user->phone_number ?? 'Sin teléfono' }}<br>
                @endunless
                {{ $order->preference ?: 'Entrega no definida' }} · {{ $order->address ?: 'Sin dirección registrada' }}
                @if($isStoreDelivery)
                  <br>{{ $deliveryContactMeta }}
                @elseif($isExternalShipping)
                  <br>{{ $shippingProgressMeta }}
                @endif
              </div>
            </div>
            <div class="col-lg-5">
              <div class="d-flex flex-wrap gap-2 justify-content-lg-end">
                <button type="button" onclick="window.history.back()" class="btn btn-outline-secondary mb-0">Volver</button>
                @unless($isDeliveryOnlyView)
                @if($canDownloadPdfs)
                <div class="d-flex align-items-center gap-2 flex-wrap justify-content-lg-end">
                  <label for="order-download-currency" class="mb-0 text-sm fw-semibold">Moneda de emisión</label>
                  <select id="order-download-currency" class="form-select form-select-sm border border-1 p-2" style="min-width: 170px; max-width: 220px;">
                    <option value="{{ $orderCurrencyCode ?? 'USD' }}">{{ $orderCurrencyCode ?? 'USD' }} (moneda de la venta)</option>
                    @if(($orderCurrencyCode ?? 'USD') !== 'VES')
                      <option value="VES">VES / Bolívares</option>
                    @endif
                  </select>
                </div>
                @if(!$hasAnnulledInvoice)
                <a id="downloadInvoiceBtn" data-base-url="{{ route('sales.orders.pdfs', ['id' => $order->id, 'type' => 'invoice']) }}" href="{{ route('sales.orders.pdfs', ['id' => $order->id, 'type' => 'invoice']) }}?currency_code={{ $orderCurrencyCode ?? 'USD' }}&disposition=inline" class="btn btn-dark mb-0">Factura PDF</a>
                @else
                <span class="btn btn-outline-danger mb-0 disabled" aria-disabled="true">Factura anulada</span>
                @endif
                <a id="downloadDeliveryBtn" data-base-url="{{ route('sales.orders.pdfs', ['id' => $order->id, 'type' => 'delivery']) }}" href="{{ route('sales.orders.pdfs', ['id' => $order->id, 'type' => 'delivery']) }}?currency_code={{ $orderCurrencyCode ?? 'USD' }}&disposition=inline" class="btn btn-outline-dark mb-0">Orden de entrega</a>
                @endif
                @if($storeWhatsappUrl)
                  <a href="{{ $storeWhatsappUrl }}" target="_blank" rel="noopener" class="btn btn-outline-success mb-0">WhatsApp tienda</a>
                @endif
                @if($canRegisterReturn)
                  <button type="button" class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#returnModal">Registrar Devolución</button>
                @endif
                @endunless
                @if($customerWhatsappUrl)
                  <a href="{{ $customerWhatsappUrl }}" target="_blank" rel="noopener" class="btn btn-success mb-0">Ir a WhatsApp del cliente</a>
                @endif
                @if($customerCallUrl)
                  <a href="{{ $customerCallUrl }}" class="btn btn-outline-primary mb-0">Llamar al cliente</a>
                @endif
                @if(!empty($deliveryMeta['map_url']))
                  <a href="{{ $deliveryMeta['map_url'] }}" target="_blank" rel="noopener" class="btn btn-outline-dark mb-0">Ver dirección</a>
                @endif
              </div>
            </div>
          </div>
        </div>

        @unless($isDeliveryOnlyView)
        <div class="row g-3 mb-4">
          <div class="col-md-3">
            <div class="card order-metric-card h-100">
              <div class="order-metric-label">Total orden</div>
              <div class="order-metric-value">{{ $orderCurrencySymbol ?? '$' }}{{ number_format($totalOrden, 2) }}</div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="card order-metric-card h-100">
              <div class="order-metric-label">Total pagado</div>
              <div class="order-metric-value">{{ $orderCurrencySymbol ?? '$' }}{{ number_format($totalPagado, 2) }}</div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="card order-metric-card h-100">
              <div class="order-metric-label">Saldo pendiente</div>
              <div class="order-metric-value">{{ $orderCurrencySymbol ?? '$' }}{{ number_format($paymentBalance, 2) }}</div>
            </div>
          </div>
          <div class="col-md-3">
            <div class="card order-metric-card h-100">
              <div class="order-metric-label">Factura</div>
              <div class="order-metric-value order-metric-value-sm">{{ $edoc?->numero_documento ?: ($hasAnnulledInvoice ? 'Anulada' : 'Pendiente') }}</div>
            </div>
          </div>
        </div>
        @endunless

        <div class="row g-4 mb-4">
          <div class="col-lg-7">
            <div class="card order-timeline-card h-100">
              <div class="d-flex justify-content-between align-items-start gap-3 mb-3 flex-wrap">
                <div>
                  <h5 class="mb-1">Estado de la orden</h5>
                  <p class="order-meta-copy mb-0">Una lectura rápida del avance real de la orden.</p>
                </div>
                <div class="d-flex flex-wrap gap-3 align-items-center">
                  <div class="d-flex align-items-center gap-2">
                    <strong>Entregado:</strong>
                    @if($order->has_returns)
                      <span class="text-danger">Devolución Registrada</span>
                    @elseif($canApproveDelivery)
                      <select id="deliver-status" class="btn btn-sm toggle-status-btn {{ $order->deliver_status == 0 ? 'btn-outline-warning' : ($order->deliver_status == 1 ? 'btn-outline-success' : 'btn-outline-danger') }}" onchange="updateDeliverStatus(this, {{ $order->id }})">
                        <option value="0" {{ $order->deliver_status == 0 ? 'selected' : '' }}>Pendiente ↓</option>
                        <option value="1" {{ $order->deliver_status == 1 ? 'selected' : '' }}>Entregado ↓</option>
                        <option value="2" {{ $order->deliver_status == 2 ? 'selected' : '' }}>Cancelado ↓</option>
                      </select>
                    @else
                      <span class="text-sm">{{ $deliveryLabel }}</span>
                    @endif
                  </div>
                  <div class="d-flex align-items-center gap-2">
                    <strong>Estado:</strong>
                    @if($isDeliveryOnlyView)
                      <span class="text-sm">{{ $approvalLabel }}</span>
                    @elseif($order->has_returns)
                      <span class="text-danger">Devolución Registrada</span>
                    @elseif($canApproveSale)
                      <select id="order-status" class="btn btn-sm toggle-status-btn {{ $order->status == 0 ? 'btn-outline-warning' : ($order->status == 1 ? 'btn-outline-success' : 'btn-outline-danger') }}" onchange="updateOrderStatus(this, {{ $order->id }})">
                        <option value="0" {{ $order->status == 0 ? 'selected' : '' }}>En Proceso ↓</option>
                        <option value="1" {{ $order->status == 1 ? 'selected' : '' }}>Aprobado ↓</option>
                        <option value="2" {{ $order->status == 2 ? 'selected' : '' }}>Negado ↓</option>
                      </select>
                    @else
                      <span class="text-sm">{{ $approvalLabel }}</span>
                    @endif
                  </div>
                </div>
              </div>

              <div class="order-timeline-steps">
                @foreach($visibleTimelineSteps as $step)
                  <div class="order-timeline-step {{ $step['tone'] }}">
                    <div class="order-timeline-title">{{ $step['title'] }}</div>
                    <div class="order-timeline-description">{{ $step['description'] }}</div>
                    <small class="text-muted d-block">{{ $step['meta'] }}</small>
                  </div>
                @endforeach
              </div>
            </div>
          </div>
          <div class="col-lg-5">
            <div class="card order-summary-card h-100">
              <div class="order-summary-label">{{ $isDeliveryOnlyView ? 'Entrega' : 'Cliente y entrega' }}</div>
              <div class="order-summary-value">{{ $isDeliveryOnlyView ? ($deliveryMeta['receiver_name'] ?: ($order->user->name ?? 'Sin nombre')) : $order->user->name }}</div>
              @unless($isDeliveryOnlyView)
              <p class="order-meta-copy mb-3">{{ $order->user->email ?? 'Sin correo' }} · {{ $order->user->phone_number ?? 'Sin teléfono' }}</p>
              @else
              <p class="order-meta-copy mb-3">{{ $deliveryMeta['receiver_phone'] ?: 'Sin teléfono registrado' }}</p>
              @endunless
              <div class="order-summary-row">
                <span>Tipo de entrega</span>
                <strong>{{ $order->preference }}</strong>
              </div>
              <div class="order-summary-row">
                <span>Fecha</span>
                <strong>{{ $order->date }}</strong>
              </div>
              <div class="order-summary-row order-summary-row-column">
                <span>Dirección</span>
                <strong>{{ $deliveryMeta['destination_label'] ?? ($order->address ?: 'Sin dirección registrada') }}</strong>
                @if(!empty($deliveryMeta['map_url']))
                  <a href="{{ $deliveryMeta['map_url'] }}" target="_blank" rel="noopener" class="text-sm">Abrir ubicación en Google Maps</a>
                @endif
              </div>
              @if(!empty($deliveryMeta['receiver_name']) || !empty($deliveryMeta['receiver_phone']))
              <div class="order-summary-row order-summary-row-column">
                <span>Quién recibe</span>
                <strong>{{ $deliveryMeta['receiver_name'] ?: 'No registrado' }}</strong>
                <small>{{ $deliveryMeta['receiver_phone'] ?: 'Sin teléfono registrado' }}</small>
              </div>
              @endif
              @if(!empty($deliveryMeta['extra_info']))
              <div class="order-summary-row order-summary-row-column">
                <span>Información adicional</span>
                <strong>{{ $deliveryMeta['extra_info'] }}</strong>
              </div>
              @endif
              @if($isStoreDelivery || $isExternalShipping)
              <div class="order-summary-row order-summary-row-column">
                <span>{{ $isStoreDelivery ? 'Gestión de delivery' : 'Gestión de envío' }}</span>
                <strong>{{ $isStoreDelivery ? $deliveryContactMeta : $shippingProgressMeta }}</strong>
              </div>
              @endif
              @unless($isDeliveryOnlyView)
              @if($isStoreDelivery && $deliveryOperationsLocked)
              <div class="order-summary-row order-summary-row-column">
                <span>Asignar repartidor</span>
                <strong>El plan actual no permite gestionar repartidores heredados.</strong>
              </div>
              @elseif($isStoreDelivery && ($currentUser?->hasStoreRole('owner', 'admin', 'warehouse') ?? false))
              <div class="order-summary-row order-summary-row-column">
                <span>Asignar repartidor</span>
                <form method="POST" action="{{ route('sales.assignDeliveryUser', $order->id) }}" class="w-100 d-flex gap-2 align-items-center flex-wrap">
                  @csrf
                  <select name="delivery_assigned_user_id" class="form-select form-select-sm border border-1 p-2" style="min-width: 220px;">
                    <option value="">Sin asignar</option>
                    @foreach($deliveryUsers as $deliveryUser)
                      <option value="{{ $deliveryUser->id }}" {{ (int) ($order->delivery_assigned_user_id ?? 0) === (int) $deliveryUser->id ? 'selected' : '' }}>
                        {{ $deliveryUser->name }}{{ !empty($deliveryUser->phone_number) ? ' · ' . $deliveryUser->phone_number : '' }}
                      </option>
                    @endforeach
                  </select>
                  <button type="submit" class="btn btn-outline-dark btn-sm mb-0">Guardar</button>
                </form>
              </div>
              @endif
              @endunless
            </div>
          </div>
        </div>
      </div>

      @unless($isDeliveryOnlyView)
      @if($linkedAppointment)
      <div class="card mt-4 order-surface-card" id="linked-appointment-workflow" data-endpoint="{{ route('appointments.workflow', $linkedAppointment->id) }}">
        <div class="card-header d-flex justify-content-between align-items-center flex-wrap gap-2">
          <h6 class="mb-0">Gestión de cita vinculada</h6>
          <span class="badge bg-gradient-info">Cita #{{ $linkedAppointment->id }}</span>
        </div>
        <div class="card-body">
          <div class="row g-3">
            <div class="col-md-6 col-xl-3">
              <small class="text-muted d-block">Servicio</small>
              <strong>{{ $linkedAppointment->service->display_name ?? $linkedAppointment->service->name ?? 'Servicio' }}</strong>
            </div>
            <div class="col-md-6 col-xl-3">
              <small class="text-muted d-block">Profesional</small>
              <strong>{{ $linkedAppointment->assignedUser->name ?? 'Sin asignar' }}</strong>
            </div>
            <div class="col-md-6 col-xl-3">
              <small class="text-muted d-block">Fecha y hora</small>
              <strong>{{ optional($linkedAppointment->starts_at)->format('d/m/Y H:i') ?? 'Sin fecha' }}</strong>
            </div>
            <div class="col-md-6 col-xl-3">
              <small class="text-muted d-block">Estado actual</small>
              <strong>{{ $appointmentStatusLabels[(string) ($linkedAppointment->status ?? 'scheduled')] ?? ucfirst((string) ($linkedAppointment->status ?? 'scheduled')) }}</strong>
              <small class="d-block text-muted mt-1">Pago: {{ $appointmentPaymentStatusLabels[(string) ($linkedAppointment->payment_status ?? 'pending')] ?? ucfirst((string) ($linkedAppointment->payment_status ?? 'pending')) }}</small>
            </div>
          </div>

          @if($canManageAppointmentWorkflow)
          <hr>
          <div class="row g-3 align-items-end">
            <div class="col-md-4">
              <label for="appointment-workflow-action" class="form-label">Acción de estatus</label>
              <select id="appointment-workflow-action" class="form-select border border-1 p-2">
                <option value="">Selecciona una acción</option>
                <option value="call_customer">Registrar llamada</option>
                <option value="confirm_attendance">Confirmar asistencia</option>
                <option value="cancel">Cancelar cita</option>
                <option value="no_show">Marcar no asistió</option>
              </select>
            </div>
            <div class="col-md-5">
              <label for="appointment-workflow-note" class="form-label">Nota (opcional)</label>
              <input type="text" id="appointment-workflow-note" class="form-control border border-1 p-2" maxlength="1000" placeholder="Detalle para el equipo o cliente">
            </div>
            <div class="col-md-3 d-grid">
              <button type="button" class="btn btn-outline-dark mb-0" onclick="submitLinkedAppointmentWorkflowAction()">Aplicar acción</button>
            </div>
          </div>

          <div class="row g-3 align-items-end mt-1">
            <div class="col-md-3">
              <label for="appointment-paid-amount" class="form-label">Monto cobrado</label>
              <input type="number" id="appointment-paid-amount" class="form-control border border-1 p-2" min="0" step="0.01" value="{{ number_format((float) ($linkedAppointment->paid_amount ?? ($linkedAppointment->service->price ?? 0)), 2, '.', '') }}">
            </div>
            <div class="col-md-3">
              <label for="appointment-payment-method" class="form-label">Método de pago</label>
              <select id="appointment-payment-method" class="form-select border border-1 p-2">
                <option value="">Sin método</option>
                @foreach($appointmentPaymentMethods as $method)
                  <option value="{{ $method->id }}" data-has-reference="{{ $method->usesReference() ? '1' : '0' }}" {{ (int) ($linkedAppointment->payment_method_id ?? 0) === (int) $method->id ? 'selected' : '' }}>
                    {{ $method->name }}{{ !empty($method->currency?->code) ? ' · ' . $method->currency->code : '' }}
                  </option>
                @endforeach
              </select>
            </div>
            <div class="col-md-4">
              <label for="appointment-payment-reference" class="form-label">Referencia</label>
              <input type="text" id="appointment-payment-reference" class="form-control border border-1 p-2" maxlength="255" value="{{ $linkedAppointment->payment_reference ?? '' }}" placeholder="Número de referencia">
            </div>
            <div class="col-md-2 d-grid">
              <button type="button" class="btn btn-dark mb-0" onclick="confirmLinkedAppointmentPayment()">Confirmar pago</button>
            </div>
          </div>
          <small class="text-muted d-block mt-2" id="linked-appointment-workflow-feedback">Los cambios se aplican en tiempo real sobre la cita vinculada.</small>
          @else
          <hr>
          <small class="text-muted d-block">No tienes permisos para cambiar el estatus de esta cita o confirmar pagos.</small>
          @endif
        </div>
      </div>
      @endif

      @php
        $documentIssueMode = (string) ($order->document_issue_mode ?? 'delivery_note');
      @endphp
      <div class="card mt-4">
        <div class="card-body py-3">
          <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2">
            <div>
              <strong>Tipo de documento de la venta:</strong>
              <span class="badge {{ $documentIssueMode === 'electronic_invoice' ? 'bg-success' : 'bg-secondary' }} ms-2">
                {{ $documentIssueMode === 'electronic_invoice' ? 'Facturación digital' : 'Orden de entrega' }}
              </span>
            </div>
            <form method="POST" action="{{ route('sales.documentMode.update', $order->id) }}" class="d-flex gap-2 align-items-center">
              @csrf
              <select name="document_issue_mode" class="form-select form-select-sm border border-1 p-2" style="min-width: 220px;">
                <option value="delivery_note" {{ $documentIssueMode === 'delivery_note' ? 'selected' : '' }}>Orden de entrega</option>
                <option value="electronic_invoice" {{ $documentIssueMode === 'electronic_invoice' ? 'selected' : '' }} {{ (bool) ($order->tenant->electronic_invoicing_enabled ?? false) ? '' : 'disabled' }}>Facturación digital</option>
              </select>
              <button type="submit" class="btn btn-outline-dark btn-sm mb-0">Guardar</button>
            </form>
          </div>
        </div>
      </div>

      @if((bool) ($order->tenant->electronic_invoicing_enabled ?? false) && $documentIssueMode === 'electronic_invoice')
      <div class="card mt-4">
        <div class="card-header pb-0">
          <h6 class="mb-0">Facturación electrónica (The Factory HKA)</h6>
        </div>
        <div class="card-body">
          <div class="row g-2">
            <div class="col-md-2">
              <form method="POST" action="{{ route('sales.electronic.emit', $order->id) }}">
                @csrf
                <button type="submit" class="btn btn-dark btn-sm w-100 mb-0">Emitir</button>
              </form>
            </div>
            <div class="col-md-2">
              <form method="POST" action="{{ route('sales.electronic.status', $order->id) }}">
                @csrf
                <button type="submit" class="btn btn-outline-dark btn-sm w-100 mb-0">Consultar estado</button>
              </form>
            </div>
            <div class="col-md-2">
              <form method="POST" action="{{ route('sales.electronic.download', $order->id) }}">
                @csrf
                <input type="hidden" name="tipo_archivo" value="pdf">
                <button type="submit" class="btn btn-outline-secondary btn-sm w-100 mb-0" {{ $hasAnnulledInvoice ? 'disabled' : '' }}>Descargar PDF</button>
              </form>
            </div>
            <div class="col-md-2">
              <a href="{{ route('sales.orders.pdfs', ['id' => $order->id, 'type' => 'invoice']) }}?disposition=inline" target="_blank" rel="noopener" class="btn btn-outline-primary btn-sm w-100 mb-0 {{ $hasAnnulledInvoice ? 'disabled' : '' }}" {{ $hasAnnulledInvoice ? 'aria-disabled=true tabindex=-1' : '' }}>Imprimir factura</a>
            </div>
            <div class="col-md-2">
              <form method="POST" action="{{ route('sales.electronic.sendEmail', $order->id) }}">
                @csrf
                <input type="hidden" name="emails" value="{{ $order->user->email ?? '' }}">
                <button type="submit" class="btn btn-outline-success btn-sm w-100 mb-0" {{ $hasAnnulledInvoice ? 'disabled' : '' }}>Enviar correo</button>
              </form>
            </div>
            <div class="col-md-2">
              <form method="POST" action="{{ route('sales.electronic.annul', $order->id) }}" data-requires-action-reason="true" data-reason-field="motivo_anulacion" data-reason-prompt="Indica el motivo de la anulación de esta factura." onsubmit="return confirm('¿Confirmas la anulación del documento electrónico?');">
                @csrf
                <button type="submit" class="btn btn-outline-danger btn-sm w-100 mb-0" {{ $edoc && $edoc->is_annulled ? 'disabled' : '' }}>Anular</button>
              </form>
            </div>
            <div class="col-md-1">
              <form method="POST" action="{{ route('sales.electronic.metadata', $order->id) }}">
                @csrf
                <button type="submit" class="btn btn-outline-primary btn-sm w-100 mb-0">Numeraciones</button>
              </form>
            </div>
          </div>

          <hr>
          @if($edoc && $edoc->is_annulled)
            <div class="alert alert-danger text-white mb-3">
              Esta factura fiscal fue anulada en la imprenta autorizada{{ $edoc->annulled_at ? ' el ' . $edoc->annulled_at->format('d/m/Y H:i') : '' }}.
            </div>
          @endif
          <div class="row">
            <div class="col-md-6">
              <p class="mb-1"><strong>Serie:</strong> {{ $edoc->serie ?? '-' }}</p>
              <p class="mb-1"><strong>Tipo doc:</strong> {{ $edoc->tipo_documento ?? '-' }}</p>
              <p class="mb-1"><strong>Número:</strong> {{ $edoc->numero_documento ?? '-' }}</p>
              <p class="mb-1"><strong>Control:</strong> <span class="badge bg-gradient-dark">{{ $edoc->numero_control ?? '-' }}</span></p>
            </div>
            <div class="col-md-6">
              <p class="mb-1"><strong>Transacción:</strong> {{ $edoc->transaccion_id ?? '-' }}</p>
              <p class="mb-1"><strong>Estado:</strong> <span class="badge {{ ($edoc && $edoc->is_annulled) ? 'bg-gradient-danger' : 'bg-gradient-success' }}">{{ ($edoc && $edoc->is_annulled) ? 'Anulada' : ($edoc->estado_documento ?? 'Activa') }}</span></p>
              <p class="mb-1"><strong>Mensaje:</strong> {{ $edoc->mensaje ?? '-' }}</p>
              <p class="mb-1"><strong>Anulado:</strong> {{ ($edoc && $edoc->is_annulled) ? 'Sí' : 'No' }}</p>
            </div>
          </div>
        </div>
      </div>
      @else
      <div class="alert alert-secondary mt-4 mb-0">
        @if(!(bool) ($order->tenant->electronic_invoicing_enabled ?? false))
          La facturación digital está desactivada para esta tienda. Un super administrador puede activarla desde Gestión de Tiendas.
        @else
          Esta orden está configurada para Orden de entrega. Si deseas factura digital, cambia el tipo de documento arriba.
        @endif
      </div>
      @endif
      @php
        $orderTaxBase = (float) $order->details->sum('amount');
        $orderTaxTotal = (float) $order->details->flatMap->taxes->sum('tax_amount');
        $orderGrossTotal = $orderTaxBase + $orderTaxTotal;
      @endphp

      <div class="row mt-4 g-4">
        <div class="col-12 col-xl-6">
          <div class="card h-100">
            <div class="card-header pb-0">
              <h6 class="mb-0">Notas de Crédito y Débito</h6>
              <p class="text-sm text-muted mb-0">Registra ajustes fiscales vinculados a esta venta.</p>
            </div>
            <div class="card-body">
              <form method="POST" action="{{ route('sales.adjustmentNotes.store', $order->id) }}" class="row g-3">
                @csrf
                <div class="col-md-4">
                  <label class="form-label">Tipo de nota</label>
                  <select name="note_type" class="form-select border border-1 p-2" required>
                    <option value="credit">Nota de crédito</option>
                    <option value="debit">Nota de débito</option>
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label">Fecha</label>
                  <input type="date" name="note_date" class="form-control border border-1 p-2" value="{{ now()->toDateString() }}" required>
                </div>
                <div class="col-md-4">
                  <label class="form-label">Monto</label>
                  <input type="number" name="amount" min="0.01" step="0.01" class="form-control border border-1 p-2" required data-decimal-friendly="true">
                </div>
                <div class="col-12">
                  <label class="form-label">Motivo</label>
                  <input type="text" name="reason" class="form-control border border-1 p-2" maxlength="255" required>
                </div>
                <div class="col-12">
                  <label class="form-label">Observaciones</label>
                  <textarea name="notes" class="form-control border border-1 p-2" rows="2"></textarea>
                </div>
                <div class="col-12 d-flex justify-content-end">
                  <button type="submit" class="btn btn-dark mb-0">Registrar nota</button>
                </div>
              </form>

              <div class="table-responsive mt-4">
                <table class="table align-items-center mb-0">
                  <thead>
                    <tr>
                      <th>Correlativo</th>
                      <th>Tipo</th>
                      <th>Fecha</th>
                      <th>Monto</th>
                      <th>Estatus</th>
                    </tr>
                  </thead>
                  <tbody>
                    @forelse($order->adjustmentNotes as $note)
                      <tr>
                        <td>{{ $note->internal_number ?? 'N/A' }}</td>
                        <td>{{ $note->note_type === 'credit' ? 'Crédito' : 'Débito' }}</td>
                        <td>{{ optional($note->note_date)->format('d/m/Y') ?? 'N/A' }}</td>
                        <td>{{ $note->currency_code }} {{ number_format((float) $note->amount, 2) }}</td>
                        <td><span class="badge bg-gradient-secondary">{{ ucfirst(str_replace('_', ' ', $note->status ?? 'registered')) }}</span></td>
                      </tr>
                    @empty
                      <tr>
                        <td colspan="5" class="text-center text-muted">No hay notas registradas.</td>
                      </tr>
                    @endforelse
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>

        <div class="col-12 col-xl-6">
          <div class="card h-100">
            <div class="card-header pb-0">
              <h6 class="mb-0">Retenciones</h6>
              <p class="text-sm text-muted mb-0">Registra retenciones asociadas a esta venta para el libro de ventas.</p>
            </div>
            <div class="card-body">
              <form method="POST" action="{{ route('sales.retentions.store', $order->id) }}" class="row g-3">
                @csrf
                <div class="col-md-4">
                  <label class="form-label">Tipo</label>
                  <select name="retention_type" class="form-select border border-1 p-2" required>
                    <option value="iva">IVA</option>
                    <option value="islr">ISLR</option>
                    <option value="municipal">Municipal</option>
                    <option value="other">Otra</option>
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label">Fecha</label>
                  <input type="date" name="retention_date" class="form-control border border-1 p-2" value="{{ now()->toDateString() }}" required>
                </div>
                <div class="col-md-4">
                  <label class="form-label">Tasa (%)</label>
                  <input type="number" name="retention_rate" min="0" max="100" step="0.01" class="form-control border border-1 p-2" required data-decimal-friendly="true">
                </div>
                <div class="col-md-4">
                  <label class="form-label">Base imponible</label>
                  <input type="number" name="taxable_base" min="0.01" step="0.01" class="form-control border border-1 p-2" value="{{ number_format($orderGrossTotal, 2, '.', '') }}" required data-decimal-friendly="true">
                </div>
                <div class="col-md-4">
                  <label class="form-label">Monto retenido</label>
                  <input type="number" name="retained_amount" min="0.01" step="0.01" class="form-control border border-1 p-2" data-decimal-friendly="true">
                </div>
                <div class="col-md-4">
                  <label class="form-label">Comprobante</label>
                  <input type="text" name="certificate_number" class="form-control border border-1 p-2" maxlength="60">
                </div>
                <div class="col-12">
                  <label class="form-label">Observaciones</label>
                  <textarea name="notes" class="form-control border border-1 p-2" rows="2"></textarea>
                </div>
                <div class="col-12 d-flex justify-content-end">
                  <button type="submit" class="btn btn-dark mb-0">Registrar retención</button>
                </div>
              </form>

              <div class="table-responsive mt-4">
                <table class="table align-items-center mb-0">
                  <thead>
                    <tr>
                      <th>Fecha</th>
                      <th>Tipo</th>
                      <th>Comprobante</th>
                      <th>Monto</th>
                      <th>Estatus</th>
                    </tr>
                  </thead>
                  <tbody>
                    @forelse($order->retentions as $retention)
                      <tr>
                        <td>{{ optional($retention->retention_date)->format('d/m/Y') ?? 'N/A' }}</td>
                        <td>{{ strtoupper($retention->retention_type ?? 'N/A') }}</td>
                        <td>{{ $retention->certificate_number ?? 'N/A' }}</td>
                        <td>{{ $retention->currency_code }} {{ number_format((float) $retention->retained_amount, 2) }}</td>
                        <td><span class="badge bg-gradient-secondary">{{ ucfirst(str_replace('_', ' ', $retention->status ?? 'registered')) }}</span></td>
                      </tr>
                    @empty
                      <tr>
                        <td colspan="5" class="text-center text-muted">No hay retenciones registradas.</td>
                      </tr>
                    @endforelse
                  </tbody>
                </table>
              </div>
            </div>
          </div>
        </div>
      </div>
      @endunless

      <!-- Tabla de Detalles de la Orden -->
      <div class="card order-surface-card">
        <div class="card-header">
          <h6 class="mb-0">{{ $isDeliveryOnlyView ? 'Lo que vas a entregar' : 'Productos en la Orden' }}</h6>
        </div>
        <div class="card-body">
          <div class="table-responsive order-table-wrapper">
          <table class="table order-detail-table align-middle mb-0">
            <thead>
              <tr>
                <th>Producto</th>
                <th>Cantidad</th>
                <th>Variante</th>
                @unless($isDeliveryOnlyView)
                <th>Precio Unitario</th>
                <th>Subtotal</th>
                @endunless
              </tr>
            </thead>
            <tbody>
              @foreach($order->details as $detalle)
              <tr>
                <td data-label="Producto">{{ $detalle->variant->product->name ?? 'Sin nombre' }}</td>
                <td data-label="Cantidad">{{ $detalle->quantity }}</td>
                <td data-label="Variante">{{ $detalle->variant->size ?? '' }}</td>
                @unless($isDeliveryOnlyView)
                <td data-label="Precio Unitario"><span class="amount-chip">{{ $orderCurrencySymbol ?? '$' }}{{ number_format($detalle->price, 2) }}</span></td>
                <td data-label="Subtotal"><span class="amount-chip amount-chip-strong">{{ $orderCurrencySymbol ?? '$' }}{{ number_format($detalle->amount, 2) }}</span></td>
                @endunless
              </tr>
              @endforeach
            </tbody>
          </table>
          </div>
          @unless($isDeliveryOnlyView)
          <div class="order-total-stack mt-3">
            <div class="order-total-line">
              <span class="order-total-label">Total Orden</span>
              <span class="amount-chip amount-chip-strong">{{ $orderCurrencySymbol ?? '$' }}{{ number_format($totalOrden, 2) }}</span>
            </div>
            @if($order->has_returns)
              <div class="order-total-line">
                <span class="order-total-label">Total Devolución</span>
                <span class="amount-chip">{{ $orderCurrencySymbol ?? '$' }}{{ number_format($order->total_devuelto, 2) }}</span>
              </div>
            @endif
          </div>
          @endunless
        </div>
      </div>

      @unless($isDeliveryOnlyView)
      <!-- Tabla de Pagos -->
      <div class="card mt-4 order-surface-card">
        <div class="card-header">
          <h6 class="mb-0">Pagos Registrados</h6>
        </div>
        <div class="card-body">
          <div class="table-responsive order-table-wrapper">
          <table class="table order-detail-table align-middle mb-0">
            <thead>
              <tr>
                <th>Moneda</th>
                <th>Método de Pago</th>
                <th>Monto</th>
                <th>Beneficiario</th>
                <th>Banco</th>
                <th>Referencia</th>
                <th>Comprobante</th>
                <th>Estado</th>
              </tr>
            </thead>
            <tbody>
              @foreach($order->payments as $payment)
              @php
                $paymentCurrencyCode = strtoupper(trim((string) ($payment->currency ?? '')));
                $paymentSymbol = $resolveCurrencySymbol($paymentCurrencyCode);
              @endphp
              <tr>
                <td data-label="Moneda">{{ $payment->currency }}</td>
                <td data-label="Método de Pago">{{ $payment->payment->name}}</td>
                <td data-label="Monto"><span class="amount-chip amount-chip-strong">{{ $paymentSymbol }}{{ number_format($payment->amount, 2) }}{{ $paymentSymbol === '' && $paymentCurrencyCode !== '' ? ' ' . $paymentCurrencyCode : '' }}</span></td>
                <td data-label="Beneficiario">{{ $payment->payment->admin_name }}</td>
                <td data-label="Banco">{{ $payment->payment->bank }}</td>
                <td data-label="Referencia">{{ $payment->reference ?? 'N/A' }}</td>
                <td id="payment-{{ $payment->id }}" data-label="Comprobante">
                    @if($payment->images->isNotEmpty())
                      <a href="{{ \App\Support\ImageStorage::url($payment->images->first()->image_path) ?? '#' }}" target="_blank" class="btn btn-sm btn-outline-dark mb-0">Ver imagen</a>
                    @else
                      <span class="text-muted">Sin imagen</span>
                    @endif
                </td>
                <td data-label="Estado">
                    @if($canApprovePayments)
                      <select class="btn btn-sm toggle-status-btn 
                        {{ $payment->status == 0 ? 'btn-outline-warning' : ($payment->status == 1 ? 'btn-outline-success' : 'btn-outline-danger') }}" 
                        onchange="updatePaymentStatus(this, {{ $payment->id }})">
                          <option value="0" {{ $payment->status == 0 ? 'selected' : '' }}>En Proceso ↓</option>
                          <option value="1" {{ $payment->status == 1 ? 'selected' : '' }}>Pagado ↓</option>
                          <option value="3" {{ $payment->status == 3 ? 'selected' : '' }}>Cancelado ↓</option>
                      </select>
                    @else
                      <span class="text-sm">{{ $payment->status == 0 ? 'En Proceso' : ($payment->status == 1 ? 'Pagado' : 'Cancelado') }}</span>
                    @endif
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
          </div>
          <div class="order-total-stack mt-3">
            <div class="order-total-line">
              <span class="order-total-label">Total Pagado</span>
              <span class="amount-chip amount-chip-strong">{{ $orderCurrencySymbol ?? '$' }}{{ number_format($totalPagado, 2) }}</span>
            </div>
            @if($order->has_returns)
              <div class="order-total-line">
                <span class="order-total-label">Total Devolución</span>
                <span class="amount-chip">{{ $orderCurrencySymbol ?? '$' }}{{ number_format($order->total_devuelto, 2) }}</span>
              </div>
            @endif
          </div>
        </div>
      </div>
      @endunless

      <!-- Modal para realizar devoluciones -->
      @if($canRegisterReturn)
      <div class="modal fade" id="returnModal" tabindex="-1" aria-labelledby="returnModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-lg">
              <div class="modal-content">
                  <div class="modal-header">
                      <h5 class="modal-title" id="returnModalLabel">Registrar Devolución</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                  </div>
                  <div class="modal-body">
                      <form id="returnForm">
                          @csrf
                          <input type="hidden" id="orderId" value="{{ $order->id }}">
                          
                          <div class="mb-3">
                              <label for="returnReason" class="form-label">Razón de la devolución</label>
                              <textarea id="returnReason" class="form-control border border-1 border-radius-lg p-2" rows="3" placeholder="Especifique la razón de la devolución" required></textarea>
                          </div>

                          <div class="mb-3">
                              <h6>Productos de la Orden</h6>
                                <div class="table-responsive order-table-wrapper">
                                <table class="table order-detail-table align-middle mb-0">
                                  <thead>
                                      <tr>
                                          <th>Producto</th>
                                          <th>Cantidad</th>
                                          <th>Devolver</th>
                                      </tr>
                                  </thead>
                                  <tbody>
                                      @foreach($order->details as $detalle)
                                          <tr>
                                            <td data-label="Producto">{{ $detalle->variant->product->name ?? 'Sin nombre' }}</td>
                                            <td data-label="Cantidad">{{ $detalle->quantity }}</td>
                                            <td data-label="Devolver">
                                                  <input type="number" class="form-control return-quantity border border-1 border-radius-lg p-2" 
                                                      data-id="{{ $detalle->variant->id }}" 
                                                      data-max="{{ $detalle->quantity }}" 
                                                      placeholder="Cantidad a devolver" 
                                                      min="0" 
                                                      max="{{ $detalle->quantity }}">
                                              </td>
                                          </tr>
                                      @endforeach
                                  </tbody>
                              </table>
                                    </div>
                          </div>

                          <div class="d-flex justify-content-end">
                              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                              <button type="submit" class="btn btn-dark ms-2">Registrar Devolución</button>
                          </div>
                      </form>
                  </div>
              </div>
          </div>
      </div>
      @endif
    </div>
    @endsection

@push('styles')
<style>
  .order-shell {
    max-width: 1240px;
  }

  .order-hero-panel {
    padding: 1.5rem;
    position: relative;
    overflow: hidden;
    border: 1px solid rgba(148, 163, 184, 0.22);
    border-radius: 1.75rem;
    box-shadow: 0 24px 70px -44px rgba(15, 23, 42, 0.5);
    background: radial-gradient(circle at top right, rgba(59, 130, 246, 0.18), transparent 28%), rgba(255, 255, 255, 0.96);
  }

  .order-hero-panel::after {
    content: '';
    position: absolute;
    inset: auto -60px -80px auto;
    width: 220px;
    height: 220px;
    border-radius: 999px;
    background: radial-gradient(circle, rgba(14, 165, 233, 0.18), transparent 68%);
  }

  .order-eyebrow {
    letter-spacing: 0.16em;
    text-transform: uppercase;
    font-size: 0.72rem;
    font-weight: 700;
    color: #1d4ed8;
    margin-bottom: 0.6rem;
  }

  .order-hero-title {
    font-size: clamp(1.8rem, 4vw, 3rem);
    line-height: 1;
    font-weight: 800;
    margin-bottom: 0.75rem;
    color: #0f172a;
  }

  .order-hero-subtitle,
  .order-meta-copy {
    color: #475569;
  }

  .order-status-pill {
    display: inline-flex;
    align-items: center;
    gap: 0.45rem;
    padding: 0.5rem 0.85rem;
    border-radius: 999px;
    background: #e2e8f0;
    color: #0f172a;
    font-size: 0.82rem;
    font-weight: 700;
  }

  .order-status-pill-danger {
    background: #fee2e2;
    color: #991b1b;
  }

  .order-metric-card,
  .order-timeline-card,
  .order-summary-card {
    border: 1px solid rgba(148, 163, 184, 0.22);
    border-radius: 1.5rem;
    box-shadow: 0 20px 40px -36px rgba(15, 23, 42, 0.45);
    background: rgba(255, 255, 255, 0.95);
  }

  .order-metric-card {
    padding: 1rem 1.1rem;
  }

  .order-metric-label {
    font-size: 0.78rem;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: #64748b;
    font-weight: 700;
  }

  .order-metric-value {
    font-size: 1.35rem;
    font-weight: 800;
    margin-top: 0.35rem;
    color: #0f172a;
  }

  .order-metric-value-sm {
    font-size: 1rem;
    line-height: 1.35;
  }

  .order-timeline-card,
  .order-summary-card {
    padding: 1.35rem;
  }

  .order-timeline-steps {
    display: grid;
    gap: 0.95rem;
  }

  .order-timeline-step {
    position: relative;
    padding: 1rem 1rem 1rem 3rem;
    margin: 0;
    border: 1px solid #e2e8f0;
    border-radius: 22px;
    background: rgba(255, 255, 255, 0.96);
    min-width: 0;
    overflow-wrap: anywhere;
  }

  .order-timeline-step::before {
    content: '';
    position: absolute;
    top: 1.1rem;
    left: 1rem;
    width: 0.9rem;
    height: 0.9rem;
    border-radius: 999px;
    background: #0f172a;
    box-shadow: 0 0 0 6px rgba(15, 23, 42, 0.08);
  }

  .order-timeline-step.pending::before {
    background: #f59e0b;
  }

  .order-timeline-step.success::before {
    background: #16a34a;
  }

  .order-timeline-step.danger::before {
    background: #dc2626;
  }

  .order-timeline-title {
    font-weight: 700;
    margin-bottom: 0.2rem;
  }

  .order-timeline-description {
    color: #475569;
    margin-bottom: 0.2rem;
  }

  .order-summary-label {
    font-size: 0.78rem;
    text-transform: uppercase;
    letter-spacing: 0.08em;
    color: #64748b;
    font-weight: 700;
    margin-bottom: 0.4rem;
  }

  .order-summary-value {
    font-size: 1.5rem;
    font-weight: 800;
    color: #0f172a;
  }

  .order-summary-row {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 1rem;
    padding: 0.75rem 0;
    border-top: 1px solid #e2e8f0;
    color: #475569;
  }

  .order-summary-row-column {
    flex-direction: column;
    align-items: flex-start;
  }

  .order-page-title {
    font-weight: 700;
    letter-spacing: -0.01em;
    margin-bottom: 0.85rem;
  }

  .order-surface-card {
    border: 1px solid #e2e8f0;
    border-radius: 1rem;
    box-shadow: 0 12px 26px rgba(15, 23, 42, 0.06);
    background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
  }

  .amount-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.2rem;
    padding: 0.2rem 0.6rem;
    border-radius: 999px;
    font-weight: 700;
    font-size: 0.85rem;
    color: #1e293b;
    border: 1px solid #dbe4f0;
    background: #ffffff;
    box-shadow: 0 6px 16px rgba(15, 23, 42, 0.08);
  }

  .amount-chip-strong {
    border-color: rgba(15, 23, 42, 0.18);
    background: linear-gradient(135deg, #ffffff 0%, #eef2ff 100%);
  }

  .order-total-stack {
    border: 1px solid #dbe4f0;
    border-radius: 0.9rem;
    padding: 0.7rem 0.85rem;
    background: rgba(255, 255, 255, 0.9);
  }

  .order-total-line {
    display: flex;
    align-items: center;
    justify-content: space-between;
    gap: 0.8rem;
    margin-bottom: 0.5rem;
  }

  .order-total-line:last-child {
    margin-bottom: 0;
  }

  .order-total-label {
    color: #475569;
    font-weight: 600;
  }

  .order-table-wrapper {
    width: 100%;
  }

  @media (max-width: 767.98px) {
    .order-hero-panel,
    .order-timeline-card,
    .order-summary-card,
    .order-metric-card {
      border-radius: 1.25rem;
    }

    .order-detail-table thead {
      display: none;
    }

    .order-detail-table,
    .order-detail-table tbody,
    .order-detail-table tr,
    .order-detail-table td {
      display: block;
      width: 100%;
    }

    .order-detail-table tr {
      border: 1px solid #dee2e6;
      border-radius: 0.75rem;
      padding: 0.75rem;
      margin-bottom: 0.75rem;
      background: #fff;
      box-shadow: 0 0.125rem 0.375rem rgba(0, 0, 0, 0.05);
    }

    .order-detail-table td {
      border: 0;
      padding: 0.45rem 0;
      text-align: left;
      white-space: normal;
    }

    .order-detail-table td::before {
      content: attr(data-label);
      display: block;
      font-weight: 700;
      color: #344767;
      margin-bottom: 0.2rem;
    }

    .order-detail-table td[data-label="Estado"] select,
    .order-detail-table td[data-label="Devolver"] input {
      width: 100%;
    }
  }
</style>
@endpush

@push('scripts')
<script src="{{ asset('assets/js/core/popper.min.js') }}"></script>
<script src="{{ asset('assets/js/core/bootstrap.min.js') }}"></script>

<!-- Github buttons -->
<script async defer src="https://buttons.github.io/buttons.js"></script>

<!-- Control Center for Material Dashboard: parallax effects, scripts for the example pages etc -->
<script>
 function showLoading(selectElement) {
    selectElement.disabled = true;
    const originalText = selectElement.options[selectElement.selectedIndex].text;
    selectElement.options[selectElement.selectedIndex].text = "Cargando...";
    return originalText;
}

function restoreText(selectElement, originalText) {
    selectElement.options[selectElement.selectedIndex].text = originalText;
    selectElement.disabled = false;
}

function updateOrderStatus(selectElement, orderId) {
    const status = selectElement.value;
  const reason = status === '2'
    ? window.shopixRequestActionReason('Indica el motivo para negar o cancelar la orden.')
    : '';
  if (status === '2' && !reason) {
    return;
  }
    const originalText = showLoading(selectElement);

    fetch(`/api/order/${orderId}/status/update`, {
        method: "POST",
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ status: status, action_reason: reason })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if(data.pdf_url) {
              const link = document.createElement("a");
              link.href = data.pdf_url;
              link.download = `orden-${orderId}.pdf`;
              document.body.appendChild(link);
              link.click();
              document.body.removeChild(link);
            }
            alert(data.message);
            location.reload();
        } else {
            restoreText(selectElement, originalText);
        }
    })
    .catch(error => {
        console.error("Error:", error);
        restoreText(selectElement, originalText);
    });
}

function updateDeliverStatus(selectElement, paymentId) {
    const status = selectElement.value;
  const reason = status === '2'
    ? window.shopixRequestActionReason('Indica el motivo para cancelar o revertir la entrega.')
    : '';
  if (status === '2' && !reason) {
    return;
  }
    const originalText = showLoading(selectElement);

    fetch(`/api/deliver/${paymentId}/status/update`, {
        method: "POST",
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ status: status, action_reason: reason })
    })
    .then(response => response.json())
    .then(data => {
        alert(data.message);
        location.reload();
    })
    .catch(error => {
        console.error("Error:", error);
        restoreText(selectElement, originalText);
    });
}

function updatePaymentStatus(selectElement, paymentId) {
    const status = selectElement.value;
  const reason = status === '3'
    ? window.shopixRequestActionReason('Indica el motivo para cancelar este pago.')
    : '';
  if (status === '3' && !reason) {
    return;
  }
    const originalText = showLoading(selectElement);

    fetch(`/api/payment/${paymentId}/status/update`, {
        method: "POST",
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ status: status, action_reason: reason })
    })
    .then(response => response.json())
    .then(data => {
        alert(data.message);
        location.reload();
    })
    .catch(error => {
        console.error("Error:", error);
        restoreText(selectElement, originalText);
    });
}

function submitLinkedAppointmentWorkflowAction() {
  const workflowCard = document.getElementById('linked-appointment-workflow');
  const actionSelect = document.getElementById('appointment-workflow-action');
  const noteInput = document.getElementById('appointment-workflow-note');

  if (!workflowCard || !actionSelect) {
    return;
  }

  const action = String(actionSelect.value || '').trim();
  if (!action) {
    alert('Selecciona una acción de estatus para la cita.');
    return;
  }

  runLinkedAppointmentWorkflow({
    action,
    note: String(noteInput?.value || '').trim(),
    create_sale: false,
  });
}

function confirmLinkedAppointmentPayment() {
  const workflowCard = document.getElementById('linked-appointment-workflow');
  const amountInput = document.getElementById('appointment-paid-amount');
  const methodSelect = document.getElementById('appointment-payment-method');
  const referenceInput = document.getElementById('appointment-payment-reference');

  if (!workflowCard || !amountInput) {
    return;
  }

  const amount = Number(amountInput.value || 0);
  const paymentMethodId = Number(methodSelect?.value || 0);
  const reference = String(referenceInput?.value || '').trim();
  const selectedMethod = methodSelect?.selectedOptions?.[0] || null;
  const requiresReference = String(selectedMethod?.dataset?.hasReference || '0') === '1';

  if (amount <= 0) {
    alert('Indica un monto mayor a 0 para confirmar el pago de la cita.');
    return;
  }

  if (requiresReference && !reference) {
    alert('Este método de pago requiere referencia.');
    return;
  }

  runLinkedAppointmentWorkflow({
    action: 'confirm_payment',
    paid_amount: amount,
    payment_method_id: paymentMethodId > 0 ? paymentMethodId : null,
    payment_reference: reference,
    create_sale: false,
    note: 'Pago confirmado desde detalle de orden.',
  });
}

function runLinkedAppointmentWorkflow(payload) {
  const workflowCard = document.getElementById('linked-appointment-workflow');
  const feedback = document.getElementById('linked-appointment-workflow-feedback');
  const endpoint = String(workflowCard?.dataset?.endpoint || '').trim();

  if (!endpoint) {
    return;
  }

  if (feedback) {
    feedback.textContent = 'Aplicando cambios en la cita...';
    feedback.classList.remove('text-danger');
  }

  fetch(endpoint, {
    method: 'POST',
    headers: {
      'X-CSRF-TOKEN': '{{ csrf_token() }}',
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
    },
    body: JSON.stringify(payload),
  })
    .then(async response => {
      const data = await response.json().catch(() => ({}));

      if (!response.ok || !data.success) {
        throw new Error(data.message || 'No se pudo actualizar la cita.');
      }

      if (feedback) {
        feedback.textContent = data.message || 'Cita actualizada correctamente.';
        feedback.classList.remove('text-danger');
      }

      alert(data.message || 'Cita actualizada correctamente.');
      location.reload();
    })
    .catch(error => {
      const message = String(error?.message || 'No se pudo actualizar la cita.');
      if (feedback) {
        feedback.textContent = message;
        feedback.classList.add('text-danger');
      }
      alert(message);
    });
}

const returnForm = document.getElementById('returnForm');
if (returnForm) {
  returnForm.addEventListener('submit', function (event) {
    event.preventDefault();

    const orderId = document.getElementById('orderId').value;
    const reason = document.getElementById('returnReason').value.trim();
    const items = [];

    document.querySelectorAll('.return-quantity').forEach(input => {
      const quantity = parseInt(input.value);
      const maxQuantity = parseInt(input.getAttribute('data-max'));
      const id = input.getAttribute('data-id');

      if (quantity > 0 && quantity <= maxQuantity) {
        items.push({ id, quantity });
      }
    });

    if (items.length === 0) {
      alert('Debe especificar al menos un producto para devolver.');
      return;
    }

    if (!reason) {
      alert('Debes indicar el motivo de la devolución.');
      return;
    }

    fetch(`/sales/${orderId}/return`, {
      method: 'POST',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
        'Content-Type': 'application/json',
      },
      body: JSON.stringify({ items, reason }),
    })
    .then(response => {
      if (response.ok) {
        return response.json();
      } else {
        throw new Error('Error al registrar la devolución.');
      }
    })
    .then(data => {
      alert(data.message);
      location.reload();
    })
    .catch(error => {
      console.error('Error:', error);
      alert('Ocurrió un error al registrar la devolución.');
    });
  });
}

(() => {
  const currencySelect = document.getElementById('order-download-currency');
  const invoiceBtn = document.getElementById('downloadInvoiceBtn');
  const deliveryBtn = document.getElementById('downloadDeliveryBtn');

  if (!currencySelect || !invoiceBtn || !deliveryBtn) {
    return;
  }

  const syncDownloadUrls = () => {
    const currencyCode = encodeURIComponent(currencySelect.value || '{{ $orderCurrencyCode ?? 'USD' }}');
    const invoiceBase = invoiceBtn.dataset.baseUrl || invoiceBtn.getAttribute('href') || '';
    const deliveryBase = deliveryBtn.dataset.baseUrl || deliveryBtn.getAttribute('href') || '';

    invoiceBtn.href = `${invoiceBase}?currency_code=${currencyCode}&disposition=inline`;
    deliveryBtn.href = `${deliveryBase}?currency_code=${currencyCode}&disposition=inline`;
  };

  currencySelect.addEventListener('change', syncDownloadUrls);
  syncDownloadUrls();
})();
</script>
@endpush