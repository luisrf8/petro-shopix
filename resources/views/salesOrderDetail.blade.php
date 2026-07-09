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
      $dispatchGuideEdoc = $order->latest_dispatch_guide_document ?? null;
      $dispatchGuideIssued = (bool) optional($dispatchGuideEdoc)->issued_at;
      $hasAnnulledInvoice = (bool) ($order->has_annulled_invoice ?? false);
      $canApproveSale = $currentUser?->hasStoreRole('owner', 'admin', 'seller') ?? false;
      $canApproveDelivery = $currentUser?->hasStoreRole('owner', 'admin', 'seller', 'warehouse', 'delivery') ?? false;
      $canRegisterReturn = $currentUser?->hasStoreRole('owner', 'admin', 'seller') ?? false;
      $canApprovePayments = $currentUser?->hasStoreRole('owner', 'admin', 'seller') ?? false;
      $canDownloadPdfs = $currentUser?->hasStoreRole('owner', 'admin', 'seller', 'warehouse') ?? false;
      $canDownloadInvoicePdf = $currentUser?->hasStoreRole('owner', 'admin', 'seller') ?? false;
      $isDigitalInvoicingEnabled = (bool) ($order->tenant->electronic_invoicing_enabled ?? false);
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
      $orderBaseCurrencyCode = \App\Support\TenantCurrency::normalizeCurrencyCode($orderCurrencyCode ?? ($order->sale_currency_code ?? 'USD'));
      $orderRateToBsSnapshot = (float) ($order->sale_rate_to_bs ?? $order->change_rate_to_bs ?? 0);
      if ($orderBaseCurrencyCode === 'BS') {
        $orderRateToBsSnapshot = 1.0;
      }

      $toBsFromBaseAmount = function (float $amountBase) use ($orderBaseCurrencyCode, $orderRateToBsSnapshot): ?float {
        if ($orderBaseCurrencyCode === 'BS') {
          return $amountBase;
        }

        if ($orderRateToBsSnapshot <= 0) {
          return null;
        }

        return $amountBase * $orderRateToBsSnapshot;
      };
      $toBaseFromBsAmount = function (float $amountBs) use ($orderBaseCurrencyCode, $orderRateToBsSnapshot): ?float {
        if ($orderBaseCurrencyCode === 'BS') {
          return $amountBs;
        }

        if ($orderRateToBsSnapshot <= 0) {
          return null;
        }

        return $amountBs / $orderRateToBsSnapshot;
      };
      $toUsdFromBaseAmount = function (float $amountBase) use ($orderBaseCurrencyCode, $toBaseFromBsAmount): ?float {
        if ($orderBaseCurrencyCode === 'USD') {
          return $amountBase;
        }

        if ($orderBaseCurrencyCode === 'BS') {
          return $toBaseFromBsAmount($amountBase);
        }

        return null;
      };
      $formatDualAmount = function (float $amount, ?string $currencyCode) use ($orderBaseCurrencyCode, $toBaseFromBsAmount, $toUsdFromBaseAmount, $toBsFromBaseAmount, $orderRateToBsSnapshot): string {
        $normalizedCurrency = \App\Support\TenantCurrency::normalizeCurrencyCode($currencyCode);
        $baseAmount = null;

        if ($normalizedCurrency === $orderBaseCurrencyCode) {
          $baseAmount = $amount;
        } elseif ($normalizedCurrency === 'BS') {
          $baseAmount = $toBaseFromBsAmount($amount);
        } elseif ($normalizedCurrency === 'USD' && $orderBaseCurrencyCode === 'BS' && $orderRateToBsSnapshot > 0) {
          $baseAmount = $amount * $orderRateToBsSnapshot;
        }

        $usdAmount = is_null($baseAmount) ? null : $toUsdFromBaseAmount($baseAmount);
        $bsAmount = is_null($baseAmount) ? null : $toBsFromBaseAmount($baseAmount);

        $usdText = is_null($usdAmount) ? 'N/D' : ('$' . number_format($usdAmount, 2));
        $bsText = is_null($bsAmount) ? 'N/D' : ('Bs ' . number_format($bsAmount, 2));

        return $usdText . ' / ' . $bsText;
      };

      $storePhone = preg_replace('/\D+/', '', (string) (($order->tenant->phone_code ?? '') . ($order->tenant->phone_number ?? '')));
      $customerPhone = preg_replace('/\D+/', '', (string) ($order->user->phone_number ?? ''));
      $storeWhatsappUrl = $storePhone !== ''
        ? 'https://wa.me/' . $storePhone . '?text=' . rawurlencode('Hola ' . ($order->tenant->name ?? 'tienda') . ', sobre la orden #' . $order->id . '.')
        : null;
      $publicDeliveryPdfUrl = route('public.order.pdf', ['id' => $order->id, 'type' => 'delivery']) . '?disposition=inline';
      $orderProductsSummary = $order->details
        ->map(function ($detail) {
          $productName = trim((string) ($detail->variant->product->name ?? 'Producto'));
          $quantity = (float) ($detail->quantity ?? 0);
          if ($quantity <= 0) {
            $quantity = 1;
          }

          return $productName . ' x' . rtrim(rtrim(number_format($quantity, 2, '.', ''), '0'), '.');
        })
        ->filter(fn ($line) => $line !== '')
        ->implode(', ');
      if ($orderProductsSummary === '') {
        $orderProductsSummary = 'Productos no especificados';
      }
      $orderDateText = trim((string) ($order->date ?? 'sin fecha registrada'));
      $tenantNameForMessage = trim((string) ($order->tenant->name ?? 'la tienda'));
      $customerWhatsappUrl = $customerPhone !== ''
        ? 'https://wa.me/' . $customerPhone . '?text=' . rawurlencode('Hola ' . ($order->user->name ?? 'cliente') . ', te saludamos de parte de ' . $tenantNameForMessage . '. Te compartimos la orden de entrega número #' . $order->id . '. Productos comprados: ' . $orderProductsSummary . '. Fecha de compra: ' . $orderDateText . '. PDF de la orden: ' . $publicDeliveryPdfUrl)
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
      $availablePaymentCurrencies = collect($paymentMethods ?? collect())
        ->map(fn ($method) => strtoupper(trim((string) ($method->currency->code ?? ''))))
        ->filter(fn ($code) => $code !== '')
        ->unique()
        ->values();
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
      $linkedAppointmentServiceTotal = round((float) ($linkedAppointment?->service?->price ?? 0), 2);
      $linkedAppointmentPaidTotal = round((float) ($linkedAppointment?->paid_amount ?? 0), 2);
      $linkedAppointmentPendingTotal = max(0, round($linkedAppointmentServiceTotal - $linkedAppointmentPaidTotal, 2));
      $linkedAppointmentDollarRate = (float) (\App\Models\DollarRate::query()
        ->where('tenant_id', (int) ($order->tenant_id ?? 0))
        ->latest('created_at')
        ->value('rate') ?? 0);
      $linkedAppointmentProofUrl = null;
      if ($linkedAppointment) {
        $linkedAppointmentMetaPrefix = '[APPOINTMENT_PAYMENT_META]';
        $linkedAppointmentNotes = (string) ($linkedAppointment->notes ?? '');
        $linkedAppointmentNoteLines = preg_split('/\r\n|\r|\n/', $linkedAppointmentNotes) ?: [];

        foreach (array_reverse($linkedAppointmentNoteLines) as $noteLine) {
          $noteLine = trim((string) $noteLine);
          if ($noteLine === '' || !str_starts_with($noteLine, $linkedAppointmentMetaPrefix)) {
            continue;
          }

          $decodedMeta = json_decode(substr($noteLine, strlen($linkedAppointmentMetaPrefix)), true);
          if (!is_array($decodedMeta)) {
            continue;
          }

          $proofUrlFromMeta = trim((string) ($decodedMeta['proof_url'] ?? ''));
          $proofPathFromMeta = trim((string) ($decodedMeta['proof_path'] ?? ''));

          if ($proofUrlFromMeta !== '') {
            $linkedAppointmentProofUrl = $proofUrlFromMeta;
            break;
          }

          if ($proofPathFromMeta !== '') {
            $linkedAppointmentProofUrl = \App\Support\ImageStorage::url($proofPathFromMeta);
            break;
          }
        }
      }
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
                Venta realizada por: {{ $order->salesRepresentative->name ?? 'No registrado' }}<br>
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
                @if($canDownloadInvoicePdf && $isDigitalInvoicingEnabled)
                  @if(!$hasAnnulledInvoice)
                  <a id="downloadInvoiceBtn" data-base-url="{{ route('sales.orders.pdfs', ['id' => $order->id, 'type' => 'invoice']) }}" href="{{ route('sales.orders.pdfs', ['id' => $order->id, 'type' => 'invoice']) }}?currency_code={{ $orderCurrencyCode ?? 'USD' }}&disposition=inline" class="btn btn-dark mb-0" target="_blank" rel="noopener">Factura PDF</a>
                  @else
                  <a id="downloadInvoiceBtn" data-base-url="{{ route('sales.orders.pdfs', ['id' => $order->id, 'type' => 'invoice']) }}" href="{{ route('sales.orders.pdfs', ['id' => $order->id, 'type' => 'invoice']) }}?currency_code={{ $orderCurrencyCode ?? 'USD' }}&disposition=inline" class="btn btn-outline-danger mb-0" target="_blank" rel="noopener">Factura anulada</a>
                  @endif
                @endif
                <a id="downloadDeliveryBtn" data-base-url="{{ route('sales.orders.pdfs', ['id' => $order->id, 'type' => 'delivery']) }}" href="{{ route('sales.orders.pdfs', ['id' => $order->id, 'type' => 'delivery']) }}?disposition=inline" class="btn btn-outline-dark mb-0" target="_blank" rel="noopener">Orden de entrega</a>
                @endif
                @if($canRegisterReturn)
                  <button type="button" class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#returnModal">Registrar Devolución</button>
                @endif
                @endunless
                @if($customerWhatsappUrl)
                  <a href="{{ $customerWhatsappUrl }}" target="_blank" rel="noopener" class="btn btn-success mb-0">Enviar URL PDF por WhatsApp</a>
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

        <!-- Tabla de Detalles de la Orden -->
        <details class="order-detail-disclosure mb-4" open>
          <summary>Productos de la orden</summary>
          <div class="card order-surface-card">
            <div class="card-header">
              <h6 class="mb-0">{{ $isDeliveryOnlyView ? 'Lo que vas a entregar' : 'Productos en la Orden' }}</h6>
            </div>
            <div class="card-body">
              @php
                $orderSubtotalBeforeDiscount = (float) ($order->subtotal_before_discount ?? 0);
                if ($orderSubtotalBeforeDiscount <= 0) {
                  $orderSubtotalBeforeDiscount = (float) $order->details->sum(function ($detail) {
                    return (float) ($detail->line_subtotal_before_discount ?? $detail->amount ?? 0);
                  });
                }

                $orderSubtotalNet = (float) $order->details->sum('amount');
                $orderDiscountTotal = (float) ($order->total_discount ?? 0);
                if ($orderDiscountTotal <= 0) {
                  $orderDiscountTotal = max(0, round($orderSubtotalBeforeDiscount - $orderSubtotalNet, 2));
                }

                $orderTaxTotalDetail = (float) $order->details->flatMap->taxes->sum('tax_amount');
                if ($orderTaxTotalDetail <= 0) {
                  $orderTaxTotalDetail = (float) $order->details->sum(function ($detail) {
                    return (float) ($detail->tax_amount ?? 0);
                  });
                }

                $orderTotalWithTaxes = round($orderSubtotalNet + $orderTaxTotalDetail, 2);
              @endphp
              <div class="table-responsive order-table-wrapper">
                <table class="table order-detail-table align-middle mb-0">
                  <thead>
                    <tr>
                      <th>Producto</th>
                      <th>Cantidad</th>
                      <th>Variante</th>
                      @unless($isDeliveryOnlyView)
                      <th>Precio Inicial</th>
                      <th>Descuento</th>
                      <th>Precio Neto</th>
                      <th>Subtotal Neto</th>
                      @endunless
                    </tr>
                  </thead>
                  <tbody>
                    @foreach($order->details as $detalle)
                    @php
                      $detailQty = max(1, (float) ($detalle->quantity ?? 0));
                      $detailSubtotalBeforeDiscount = (float) ($detalle->line_subtotal_before_discount ?? 0);
                      if ($detailSubtotalBeforeDiscount <= 0) {
                        $detailSubtotalBeforeDiscount = (float) ($detalle->amount ?? 0);
                      }
                      $detailDiscountAmount = (float) ($detalle->line_discount_amount ?? 0);
                      if ($detailDiscountAmount <= 0) {
                        $detailDiscountAmount = max(0, round($detailSubtotalBeforeDiscount - (float) ($detalle->amount ?? 0), 2));
                      }
                      $detailOriginalUnitPrice = $detailQty > 0 ? round($detailSubtotalBeforeDiscount / $detailQty, 2) : (float) ($detalle->price ?? 0);
                      $detailDiscountUnitPrice = $detailQty > 0 ? round($detailDiscountAmount / $detailQty, 2) : 0.0;
                    @endphp
                    <tr>
                      <td data-label="Producto">{{ $detalle->variant->product->name ?? 'Sin nombre' }}</td>
                      <td data-label="Cantidad">{{ $detalle->quantity }}</td>
                      <td data-label="Variante">{{ $detalle->variant->size ?? '' }}</td>
                      @unless($isDeliveryOnlyView)
                      <td data-label="Precio Inicial"><span class="amount-chip">{{ $formatDualAmount((float) $detailOriginalUnitPrice, $orderCurrencyCode ?? ($order->sale_currency_code ?? 'USD')) }}</span></td>
                      <td data-label="Descuento"><span class="amount-chip">{{ $formatDualAmount((float) $detailDiscountUnitPrice, $orderCurrencyCode ?? ($order->sale_currency_code ?? 'USD')) }}</span></td>
                      <td data-label="Precio Neto"><span class="amount-chip">{{ $formatDualAmount((float) $detalle->price, $orderCurrencyCode ?? ($order->sale_currency_code ?? 'USD')) }}</span></td>
                      <td data-label="Subtotal Neto"><span class="amount-chip amount-chip-strong">{{ $formatDualAmount((float) $detalle->amount, $orderCurrencyCode ?? ($order->sale_currency_code ?? 'USD')) }}</span></td>
                      @endunless
                    </tr>
                    @endforeach
                  </tbody>
                </table>
              </div>
              @unless($isDeliveryOnlyView)
              <div class="order-total-stack mt-3">
                <div class="order-total-line">
                  <span class="order-total-label">Subtotal Antes De Descuento</span>
                  <span class="amount-chip">{{ $formatDualAmount((float) $orderSubtotalBeforeDiscount, $orderCurrencyCode ?? ($order->sale_currency_code ?? 'USD')) }}</span>
                </div>
                <div class="order-total-line">
                  <span class="order-total-label">Total Descuento</span>
                  <span class="amount-chip">{{ $formatDualAmount((float) $orderDiscountTotal, $orderCurrencyCode ?? ($order->sale_currency_code ?? 'USD')) }}</span>
                </div>
                <div class="order-total-line">
                  <span class="order-total-label">Subtotal Neto</span>
                  <span class="amount-chip">{{ $formatDualAmount((float) $orderSubtotalNet, $orderCurrencyCode ?? ($order->sale_currency_code ?? 'USD')) }}</span>
                </div>
                <div class="order-total-line">
                  <span class="order-total-label">Total Impuestos</span>
                  <span class="amount-chip">{{ $formatDualAmount((float) $orderTaxTotalDetail, $orderCurrencyCode ?? ($order->sale_currency_code ?? 'USD')) }}</span>
                </div>
                <div class="order-total-line">
                  <span class="order-total-label">Total A Pagar</span>
                  <span class="amount-chip amount-chip-strong">{{ $formatDualAmount((float) $orderTotalWithTaxes, $orderCurrencyCode ?? ($order->sale_currency_code ?? 'USD')) }}</span>
                </div>
                @if($order->has_returns)
                  <div class="order-total-line">
                    <span class="order-total-label">Total Devolución</span>
                    <span class="amount-chip">{{ $formatDualAmount((float) $order->total_devuelto, $orderCurrencyCode ?? ($order->sale_currency_code ?? 'USD')) }}</span>
                  </div>
                @endif
              </div>
              @endunless
            </div>
          </div>
        </details>

        @unless($isDeliveryOnlyView)
        <!-- Tabla de Pagos -->
        <details class="order-detail-disclosure mb-4" open>
          <summary>Pagos registrados</summary>
          <div class="card order-surface-card">
            <div class="card-header">
              <div class="d-flex flex-wrap justify-content-between align-items-center gap-2">
                <h6 class="mb-0">Pagos Registrados</h6>
                @if($canApprovePayments && (float) $paymentBalance > 0)
                  <button type="button" class="btn btn-sm btn-dark mb-0" id="openCreatePaymentEntryModalBtn">Agregar pago</button>
                @endif
              </div>
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
                      <th>Acciones</th>
                    </tr>
                  </thead>
                  <tbody>
                    @forelse($order->payments as $payment)
                    @php
                      $paymentCurrencyCode = strtoupper(trim((string) ($payment->currency ?? '')));
                      $paymentSymbol = $resolveCurrencySymbol($paymentCurrencyCode);
                      $paymentBaseAmount = (float) ($payment->amount_base ?? $payment->amount ?? 0);
                      $paymentOriginalAmount = (float) ($payment->amount_original ?? 0);

                      $paymentUsdAmount = $toUsdFromBaseAmount($paymentBaseAmount);
                      $paymentBsAmount = $toBsFromBaseAmount($paymentBaseAmount);

                      if (\App\Support\TenantCurrency::normalizeCurrencyCode($paymentCurrencyCode) === 'BS' && $paymentOriginalAmount > 0) {
                        $paymentBsAmount = $paymentOriginalAmount;
                      }

                      $paymentDualText = (is_null($paymentUsdAmount) ? 'N/D' : ('$' . number_format($paymentUsdAmount, 2)))
                        . ' / '
                        . (is_null($paymentBsAmount) ? 'N/D' : ('Bs ' . number_format($paymentBsAmount, 2)));
                      $paymentProofUrl = $payment->images->isNotEmpty()
                        ? (\App\Support\ImageStorage::url($payment->images->first()->image_path) ?? null)
                        : null;
                    @endphp
                    <tr>
                      <td data-label="Moneda">{{ $payment->currency }}</td>
                      <td data-label="Método de Pago">{{ $payment->payment->name}}</td>
                      <td data-label="Monto"><span class="amount-chip amount-chip-strong">{{ $paymentDualText }}</span></td>
                      <td data-label="Beneficiario">{{ $payment->payment->admin_name }}</td>
                      <td data-label="Banco">{{ $payment->payment->bank }}</td>
                      <td data-label="Referencia">{{ $payment->reference ?? 'N/A' }}</td>
                      <td id="payment-{{ $payment->id }}" data-label="Comprobante">
                          @if($paymentProofUrl)
                            <a href="{{ $paymentProofUrl }}" target="_blank" class="btn btn-sm btn-outline-dark mb-0">Ver imagen</a>
                          @else
                            <span class="text-muted">Sin imagen</span>
                          @endif
                      </td>
                      <td data-label="Estado">
                          @if($canApprovePayments)
                            <select data-payment-id="{{ $payment->id }}" class="btn btn-sm toggle-status-btn js-payment-status 
                              {{ $payment->status == 0 ? 'btn-outline-warning' : ($payment->status == 1 ? 'btn-outline-success' : 'btn-outline-danger') }}">
                                <option value="0" {{ $payment->status == 0 ? 'selected' : '' }}>En Proceso ↓</option>
                                <option value="1" {{ $payment->status == 1 ? 'selected' : '' }}>Pagado ↓</option>
                                <option value="3" {{ $payment->status == 3 ? 'selected' : '' }}>Cancelado ↓</option>
                            </select>
                          @else
                            <span class="text-sm">{{ $payment->status == 0 ? 'En Proceso' : ($payment->status == 1 ? 'Pagado' : 'Cancelado') }}</span>
                          @endif
                      </td>
                      <td data-label="Acciones">
                        @if($canApprovePayments && (int) ($payment->status ?? 0) !== 1)
                          <div class="d-flex flex-column gap-2">
                            <button
                              type="button"
                              class="btn btn-sm btn-outline-dark mb-0 js-edit-payment-btn"
                              data-payment-id="{{ $payment->id }}"
                              data-payment-amount="{{ number_format((float) $payment->amount, 2, '.', '') }}"
                              data-payment-reference="{{ $payment->reference ?? '' }}"
                              data-payment-proof-url="{{ $paymentProofUrl ?? '' }}"
                            >
                              Editar
                            </button>
                            <button
                              type="button"
                              class="btn btn-sm btn-outline-danger mb-0 js-delete-payment-btn"
                              data-payment-id="{{ $payment->id }}"
                            >
                              Eliminar
                            </button>
                          </div>
                        @else
                          <span class="text-sm text-muted">{{ (int) ($payment->status ?? 0) === 1 ? 'Pago aprobado' : 'Sin acciones' }}</span>
                        @endif
                      </td>
                    </tr>
                    @empty
                    <tr>
                      <td colspan="9" class="text-center text-muted py-4">Aún no hay pagos registrados para esta orden.</td>
                    </tr>
                    @endforelse
                  </tbody>
                </table>
              </div>
              <div class="order-total-stack mt-3">
                <div class="order-total-line">
                  <span class="order-total-label">Total Pagado</span>
                  <span class="amount-chip amount-chip-strong">{{ $formatDualAmount((float) $totalPagado, $orderCurrencyCode ?? ($order->sale_currency_code ?? 'USD')) }}</span>
                </div>
                <div class="order-total-line">
                  <span class="order-total-label">Monto pendiente</span>
                  <span class="amount-chip amount-chip-strong">{{ $formatDualAmount((float) $paymentBalance, $orderCurrencyCode ?? ($order->sale_currency_code ?? 'USD')) }}</span>
                </div>
                @if($order->has_returns)
                  <div class="order-total-line">
                    <span class="order-total-label">Total Devolución</span>
                    <span class="amount-chip">{{ $formatDualAmount((float) $order->total_devuelto, $orderCurrencyCode ?? ($order->sale_currency_code ?? 'USD')) }}</span>
                  </div>
                @endif
              </div>
            </div>
          </div>
        </details>
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
                      <select id="deliver-status" data-deliver-id="{{ $order->id }}" class="btn btn-sm toggle-status-btn js-deliver-status {{ $order->deliver_status == 0 ? 'btn-outline-warning' : ($order->deliver_status == 1 ? 'btn-outline-success' : 'btn-outline-danger') }}">
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
                      <select id="order-status" data-order-id="{{ $order->id }}" class="btn btn-sm toggle-status-btn js-order-status {{ $order->status == 0 ? 'btn-outline-warning' : ($order->status == 1 ? 'btn-outline-success' : 'btn-outline-danger') }}">
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
              <p class="order-meta-copy mb-3"><strong>Venta realizada por:</strong> {{ $order->salesRepresentative->name ?? 'No registrado' }}</p>
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
      <div
        class="card mt-4 order-surface-card"
        id="linked-appointment-workflow"
        data-endpoint="{{ route('appointments.workflow', $linkedAppointment->id) }}"
        data-service-total="{{ number_format($linkedAppointmentServiceTotal, 2, '.', '') }}"
        data-paid-total="{{ number_format($linkedAppointmentPaidTotal, 2, '.', '') }}"
        data-pending-total="{{ number_format($linkedAppointmentPendingTotal, 2, '.', '') }}"
      >
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
              @if(!empty($linkedAppointmentProofUrl))
              <a href="{{ $linkedAppointmentProofUrl }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-dark mt-2 mb-0">
                <i class="bi bi-image me-1"></i>Ver comprobante
              </a>
              @endif
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

          <div class="row g-3 mb-1">
            <div class="col-md-4">
              <div class="linked-appointment-payment-metric">
                <small class="text-muted d-block">Total del servicio (USD)</small>
                <strong id="appointment-service-total">{{ number_format($linkedAppointmentServiceTotal, 2, ',', '.') }}</strong>
              </div>
            </div>
            <div class="col-md-4">
              <div class="linked-appointment-payment-metric">
                <small class="text-muted d-block">Lleva pagado (USD)</small>
                <strong id="appointment-paid-total">{{ number_format($linkedAppointmentPaidTotal, 2, ',', '.') }}</strong>
              </div>
            </div>
            <div class="col-md-4">
              <div class="linked-appointment-payment-metric">
                <small class="text-muted d-block">Saldo pendiente (USD)</small>
                <strong id="appointment-pending-total">{{ number_format($linkedAppointmentPendingTotal, 2, ',', '.') }}</strong>
              </div>
            </div>
          </div>

          <div class="row g-3 align-items-end mt-1">
            <div class="col-md-3">
              <label for="appointment-paid-amount" class="form-label">Abono a registrar</label>
              <input type="number" id="appointment-paid-amount" class="form-control border border-1 p-2" min="0" step="0.01" value="0.00">
            </div>
            <div class="col-md-3">
              <label for="appointment-payment-method" class="form-label">Método de pago</label>
              <select id="appointment-payment-method" class="form-select border border-1 p-2">
                <option value="">Sin método</option>
                @foreach($appointmentPaymentMethods as $method)
                  @php
                    $methodCurrencyCode = strtoupper((string) ($method->currency?->code ?? 'USD'));
                  @endphp
                  <option
                    value="{{ $method->id }}"
                    data-has-reference="{{ $method->usesReference() ? '1' : '0' }}"
                    data-currency-code="{{ $methodCurrencyCode }}"
                    data-currency-symbol="{{ $resolveCurrencySymbol($methodCurrencyCode) }}"
                    {{ (int) ($linkedAppointment->payment_method_id ?? 0) === (int) $method->id ? 'selected' : '' }}
                  >
                    {{ $method->name }}{{ !empty($method->currency?->code) ? ' · ' . $method->currency->code : '' }}
                  </option>
                @endforeach
              </select>
            </div>
            <div class="col-md-3">
              <label for="appointment-payment-rate" class="form-label">Tasa del día (VES/USD)</label>
              <input type="number" id="appointment-payment-rate" class="form-control border border-1 p-2" min="0" step="0.0001" value="{{ number_format(max($linkedAppointmentDollarRate, 0), 4, '.', '') }}">
            </div>
            <div class="col-md-3">
              <label for="appointment-paid-amount-usd" class="form-label">Equivalente en USD</label>
              <input type="text" id="appointment-paid-amount-usd" class="form-control border border-1 p-2" value="0.00" readonly>
            </div>
            <div class="col-md-4">
              <label for="appointment-payment-reference" class="form-label">Referencia</label>
              <input type="text" id="appointment-payment-reference" class="form-control border border-1 p-2" maxlength="255" value="{{ $linkedAppointment->payment_reference ?? '' }}" placeholder="Número de referencia">
            </div>
            <div class="col-md-4">
              <label for="appointment-payment-proof" class="form-label">Comprobante</label>
              <input type="file" id="appointment-payment-proof" class="form-control border border-1 p-2" accept="image/jpeg,image/png,image/jpg,image/webp">
              <small class="text-muted d-block mt-1" id="appointment-payment-proof-hint">Si el método lo requiere, adjunta una imagen del comprobante.</small>
            </div>
            <div class="col-md-2 d-grid">
              <button type="button" class="btn btn-dark mb-0" onclick="confirmLinkedAppointmentPayment()">Confirmar pago</button>
            </div>
          </div>
          <small class="text-muted d-block mt-2" id="appointment-payment-calc-summary">Ingresa un abono para calcular cuánto lleva pagado y el saldo restante.</small>
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
      @if($isDigitalInvoicingEnabled)
      <details class="order-detail-disclosure mt-4">
        <summary>Guía de despacho y Facturación electrónica</summary>
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

      @if($isDigitalInvoicingEnabled)
      <div class="card mt-3">
        <div class="card-body py-3">
          <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2">
            <div>
              <strong>Guía de despacho fiscal (HKA):</strong>
              @php
                $dispatchGuideStatus = $dispatchGuideEdoc
                  ? ((bool) $dispatchGuideEdoc->is_annulled
                    ? 'Anulada'
                    : ((string) ($dispatchGuideEdoc->issued_at
                      ? 'Emitida'
                      : ((trim((string) ($dispatchGuideEdoc->mensaje ?? '')) !== '' && str_contains(strtolower((string) ($dispatchGuideEdoc->mensaje ?? '')), 'error')) ? 'Fallida' : 'Pendiente'))))
                  : 'Pendiente';
                $dispatchGuideBadgeClass = $dispatchGuideEdoc
                  ? ((bool) $dispatchGuideEdoc->is_annulled
                    ? 'bg-danger'
                    : ($dispatchGuideEdoc->issued_at
                      ? 'bg-success'
                      : ((trim((string) ($dispatchGuideEdoc->mensaje ?? '')) !== '' && str_contains(strtolower((string) ($dispatchGuideEdoc->mensaje ?? '')), 'error')) ? 'bg-danger' : 'bg-warning')))
                  : 'bg-secondary';
              @endphp
              <span class="badge {{ $dispatchGuideBadgeClass }} ms-2">{{ $dispatchGuideStatus }}</span>
              @if($dispatchGuideEdoc)
                <small class="d-block text-muted mt-1">
                  Doc: {{ $dispatchGuideEdoc->numero_documento ?: 'N/A' }} | Ctrl: {{ $dispatchGuideEdoc->numero_control ?: 'N/A' }}
                </small>
                @if(!$dispatchGuideIssued && !empty($dispatchGuideEdoc->mensaje))
                  <small class="d-block text-danger mt-1">{{ $dispatchGuideEdoc->mensaje }}</small>
                @endif
              @endif
            </div>
            <div class="d-flex gap-2 align-items-center">
              <form method="POST" action="{{ route('sales.dispatchGuide.emit', $order->id) }}">
                @csrf
                <button type="submit" class="btn btn-outline-secondary btn-sm mb-0" {{ (bool) optional($dispatchGuideEdoc)->is_annulled ? 'disabled' : '' }}>
                  Emitir guía HKA
                </button>
              </form>
              <form method="POST" action="{{ route('sales.dispatchGuide.download', $order->id) }}">
                @csrf
                <input type="hidden" name="tipo_archivo" value="pdf">
                <button type="submit" class="btn btn-outline-dark btn-sm mb-0" {{ !$dispatchGuideIssued || (bool) optional($dispatchGuideEdoc)->is_annulled ? 'disabled' : '' }}>
                  Descargar guía HKA
                </button>
              </form>
            </div>
          </div>
        </div>
      </div>
      @endif

      @if($isDigitalInvoicingEnabled && $documentIssueMode === 'electronic_invoice')
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
                  <button type="submit" class="btn btn-outline-secondary btn-sm w-100 mb-0">Descargar PDF</button>
              </form>
            </div>
            <div class="col-md-2">
                <a href="{{ route('sales.orders.pdfs', ['id' => $order->id, 'type' => 'invoice']) }}?disposition=inline" target="_blank" rel="noopener" class="btn btn-outline-primary btn-sm w-100 mb-0">Imprimir factura</a>
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
      @endif
      </details>
      @endif
      @php
        $orderTaxBase = (float) $order->details->sum('amount');
        $orderTaxTotal = (float) $order->details->flatMap->taxes->sum('tax_amount');
        $orderGrossTotal = $orderTaxBase + $orderTaxTotal;
        $orderIgtfTotal = round((float) ($order->igtf_amount ?? 0), 2);
        $suggestedVatRate = $orderTaxBase > 0 && $orderTaxTotal > 0
          ? round(($orderTaxTotal / $orderTaxBase) * 100, 2)
          : 16.00;
        $fiscalDocumentAvailable = $edoc && !$hasAnnulledInvoice && !empty($edoc->numero_documento) && !empty($edoc->numero_control);
        $creditNoteDeadline = $edoc?->issued_at
          ? $edoc->issued_at->copy()->addDays((int) ($order->tenant?->credit_note_max_age_days ?? 30))->format('d/m/Y')
          : null;
        $suggestedRetentionIvaBase = round(max(0, $orderTaxTotal), 2);
        $suggestedRetentionIva75 = round($suggestedRetentionIvaBase * 0.75, 2);
        $suggestedRetentionIva100 = round($suggestedRetentionIvaBase, 2);
        $suggestedRetentionIncomeBase = round(max(0, $orderTaxBase), 2);
      @endphp

      @if($isDigitalInvoicingEnabled)
      <div class="row mt-4 g-4">
        <div class="col-12 col-xl-6">
          <details class="order-detail-disclosure">
            <summary>Notas de crédito y débito</summary>
          <div class="card h-100">
            <div class="card-header pb-0">
              <h6 class="mb-0">Notas de Crédito y Débito</h6>
              <p class="text-sm text-muted mb-0">Usa esta sección solo cuando ya exista una factura fiscal válida y necesites corregirla ante el SENIAT/HKA.</p>
            </div>
            <div class="card-body">
              @if(!$fiscalDocumentAvailable)
                <div class="alert alert-warning text-white bg-warning" role="alert">
                  <strong>Antes de registrar una nota:</strong> esta orden todavía no tiene una factura fiscal activa reutilizable. Primero debes emitir la factura; si fue anulada, no se puede usar como referencia.
                </div>
              @else
                <div class="alert alert-light border" role="alert">
                  <div class="row g-2">
                    <div class="col-md-6">
                      <strong>Factura que se va a afectar</strong><br>
                      Nro: {{ $edoc->numero_documento }}<br>
                      Control: {{ $edoc->numero_control }}<br>
                      Fecha: {{ optional($edoc->issued_at ?? $edoc->created_at)->format('d/m/Y') ?? 'N/A' }}
                    </div>
                    <div class="col-md-6">
                      <strong>Referencia útil para llenar la nota</strong><br>
                      Base imponible sugerida: {{ $orderCurrencyCode ?? ($order->sale_currency_code ?? 'USD') }} {{ number_format($orderTaxBase, 2) }}<br>
                      IVA facturado sugerido: {{ number_format($suggestedVatRate, 2) }}% ({{ $orderCurrencyCode ?? ($order->sale_currency_code ?? 'USD') }} {{ number_format($orderTaxTotal, 2) }})<br>
                      @if($creditNoteDeadline)
                        Último día recomendado para NC: {{ $creditNoteDeadline }}
                      @endif
                    </div>
                  </div>
                </div>
              @endif

              <div class="alert alert-info text-white bg-info" role="alert">
                <strong>Mapa rapido del flujo fiscal:</strong> en Shopix, la factura, las notas y las retenciones se intentan enviar por API a HKA. El portal de HKA se usa luego para consultar, validar rastreo y confirmar lo que ya fue procesado.
              </div>

              <div class="row g-3 mb-3">
                <div class="col-md-4">
                  <div class="border rounded p-3 h-100 bg-light">
                    <strong>Se emite por API</strong>
                    <p class="text-sm text-muted mb-0 mt-2">Factura fiscal, nota de crédito, nota de débito y retención. Si HKA responde con aceptación, Shopix guarda el payload y la respuesta para auditoría.</p>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="border rounded p-3 h-100 bg-light">
                    <strong>Se consulta luego</strong>
                    <p class="text-sm text-muted mb-0 mt-2">El portal HKA y los botones de estado sirven para verificar cómo quedó el documento, confirmar su trazabilidad y revisar códigos o mensajes de negocio.</p>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="border rounded p-3 h-100 bg-light">
                    <strong>Cómo leer el estatus</strong>
                    <p class="text-sm text-muted mb-0 mt-2"><strong>Registered:</strong> creado internamente. <strong>Issued:</strong> aceptado por HKA y sí afecta fiscalmente. <strong>Failed:</strong> HKA lo rechazó o falló la integración; no debe considerarse aplicado.</p>
                  </div>
                </div>
              </div>

              <div class="row g-3 mb-3">
                <div class="col-md-4">
                  <div class="border rounded p-3 h-100 bg-light">
                    <strong>Paso 1</strong>
                    <p class="text-sm text-muted mb-0">Elige <strong>crédito</strong> si vas a rebajar o revertir monto. Elige <strong>débito</strong> si vas a aumentar el monto facturado.</p>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="border rounded p-3 h-100 bg-light">
                    <strong>Paso 2</strong>
                    <p class="text-sm text-muted mb-0">Si el ajuste es por diferencial cambiario o error de precio, llena Base, IVA e IGTF. El sistema calculará el monto final.</p>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="border rounded p-3 h-100 bg-light">
                    <strong>Paso 3</strong>
                    <p class="text-sm text-muted mb-0">Describe el motivo como aparecería en soporte fiscal. La nota se emitirá en HKA al guardar.</p>
                  </div>
                </div>
              </div>

              @if($errors->has('note_type') || $errors->has('note_date') || $errors->has('amount') || $errors->has('taxable_base') || $errors->has('tax_rate') || $errors->has('affected_igtf_amount') || $errors->has('reason'))
                <div class="alert alert-danger" role="alert">
                  <strong>No se pudo registrar la nota.</strong>
                  <ul class="mb-0 mt-2 ps-3">
                    @foreach (['note_type', 'note_date', 'amount', 'taxable_base', 'tax_rate', 'affected_igtf_amount', 'reason'] as $noteErrorField)
                      @error($noteErrorField)
                        <li>{{ $message }}</li>
                      @enderror
                    @endforeach
                  </ul>
                </div>
              @endif

              <form method="POST" action="{{ route('sales.adjustmentNotes.store', $order->id) }}" class="row g-3">
                @csrf
                <div class="col-md-4">
                  <label class="form-label">Tipo de nota</label>
                  <select name="note_type" id="adjustmentNoteType" class="form-select border border-1 p-2" required>
                    <option value="credit" {{ old('note_type') === 'credit' || !old('note_type') ? 'selected' : '' }}>Nota de crédito</option>
                    <option value="debit" {{ old('note_type') === 'debit' ? 'selected' : '' }}>Nota de débito</option>
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label">Modo de ajuste</label>
                  <select name="adjustment_mode" id="adjustmentMode" class="form-select border border-1 p-2">
                    <option value="manual" {{ old('adjustment_mode') === 'manual' || !old('adjustment_mode') ? 'selected' : '' }}>Manual</option>
                    <option value="exchange_rate_diff" {{ old('adjustment_mode') === 'exchange_rate_diff' ? 'selected' : '' }}>Diferencial cambiario</option>
                    <option value="price_error" {{ old('adjustment_mode') === 'price_error' ? 'selected' : '' }}>Error de precio</option>
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label">Fecha</label>
                  <input type="date" name="note_date" class="form-control border border-1 p-2" value="{{ old('note_date', now()->toDateString()) }}" required>
                </div>
                <div class="col-md-3">
                  <label class="form-label">Monto final de la nota</label>
                  <input type="number" id="adjustmentAmount" name="amount" min="0.01" step="0.01" class="form-control border border-1 p-2" value="{{ old('amount') }}" required data-decimal-friendly="true" placeholder="Ej. 25.00">
                  <small class="text-muted">En modo manual este es el valor que manda. Base e IVA solo se usan para distribuir fiscalmente ese monto.</small>
                </div>
                <div class="col-md-3">
                  <label class="form-label">Base imponible</label>
                  <input type="number" id="adjustmentTaxableBase" name="taxable_base" min="0.01" step="0.01" class="form-control border border-1 p-2" value="{{ old('taxable_base', number_format($orderTaxBase, 2, '.', '')) }}" data-decimal-friendly="true">
                  <small class="text-muted">Sugerido según la factura: {{ number_format($orderTaxBase, 2) }}.</small>
                </div>
                <div class="col-md-3">
                  <label class="form-label">IVA (%)</label>
                  <input type="number" id="adjustmentTaxRate" name="tax_rate" min="0" max="100" step="0.01" class="form-control border border-1 p-2" value="{{ old('tax_rate', number_format($suggestedVatRate, 2, '.', '')) }}" data-decimal-friendly="true">
                  <small class="text-muted">IVA sugerido según esta venta: {{ number_format($suggestedVatRate, 2) }}%.</small>
                </div>
                <div class="col-md-3">
                  <label class="form-label">IGTF ajuste</label>
                  <input type="number" id="adjustmentIgtfAmount" name="affected_igtf_amount" min="0" step="0.01" class="form-control border border-1 p-2" value="{{ old('affected_igtf_amount', $orderIgtfTotal > 0 ? number_format($orderIgtfTotal, 2, '.', '') : '') }}" data-decimal-friendly="true">
                  <small class="text-muted">Solo úsalo si la corrección también afecta el IGTF.</small>
                </div>
                <div class="col-12">
                  <div id="adjustmentNoteHelper" class="alert alert-secondary mb-0 py-2 px-3">
                    En manual, HKA recibirá el monto final que escribes arriba. Para débito por diferencial cambiario o error de precio, el sistema calcula automáticamente el monto como Base + IVA + IGTF.
                  </div>
                </div>
                <div class="col-12">
                  <div class="border rounded p-3 bg-light">
                    <strong>Vista previa de cálculo</strong>
                    <div class="text-sm text-muted mt-1" id="adjustmentNotePreview">Completa los campos para ver el cálculo estimado antes de emitir la nota.</div>
                  </div>
                </div>
                <div class="col-12">
                  <label class="form-label">Motivo</label>
                  <input type="text" id="adjustmentReason" name="reason" class="form-control border border-1 p-2" maxlength="255" value="{{ old('reason') }}" required placeholder="Ej. Devolución parcial, error de precio, diferencial cambiario">
                </div>
                <div class="col-12">
                  <label class="form-label">Observaciones</label>
                  <textarea name="notes" class="form-control border border-1 p-2" rows="2" placeholder="Aquí puedes dejar el soporte interno del ajuste.">{{ old('notes') }}</textarea>
                </div>
                <div class="col-12 d-flex justify-content-end">
                  <button type="submit" class="btn btn-dark mb-0" {{ !$fiscalDocumentAvailable ? 'disabled' : '' }}>Emitir nota en HKA</button>
                </div>
              </form>

              <div class="alert alert-light border mt-4 mb-3" role="alert">
                <strong>Como leer el impacto:</strong> la factura original no se reescribe. Cada nota aprobada en HKA queda asociada a esa factura y aqui ves el monto original, el ajuste aplicado y el neto fiscal estimado despues del cambio.
              </div>

              <div class="table-responsive mt-4">
                <table class="table align-items-center mb-0">
                  <thead>
                    <tr>
                      <th>Correlativo</th>
                      <th>Tipo</th>
                      <th>Factura afectada</th>
                      <th>Moneda</th>
                      <th>Fecha</th>
                      <th>Monto original</th>
                      <th>Ajuste aplicado</th>
                      <th>Neto estimado</th>
                      <th>Estatus</th>
                      <th></th>
                    </tr>
                  </thead>
                  <tbody>
                    @forelse($order->adjustmentNotes as $note)
                      @php
                        $noteCurrency = strtoupper((string) ($note->currency_code ?: ($orderCurrencyCode ?? ($order->sale_currency_code ?? 'USD'))));
                        $noteHkaCurrency = strtoupper((string) (data_get($note->request_payload, 'encabezado.identificacionDocumento.moneda') ?: data_get(optional($note->electronicDocument)->request_payload, 'encabezado.identificacionDocumento.moneda') ?: $noteCurrency));
                        $displayCurrency = $noteHkaCurrency !== '' ? $noteHkaCurrency : $noteCurrency;
                        $referenceNumber = $note->reference_document_number ?: optional($note->electronicDocument)->numero_documento;
                        $referenceControl = $note->reference_control_number ?: optional($note->electronicDocument)->numero_control;
                        $hkaDocumentType = (string) (
                          data_get($note->response_payload, 'resultado.tipoDocumento')
                          ?: data_get($note->response_payload, 'tipoDocumento')
                          ?: data_get($note->request_payload, 'encabezado.identificacionDocumento.tipoDocumento')
                          ?: $note->document_code
                          ?: ''
                        );
                        $effectiveNoteType = match ($hkaDocumentType) {
                          '02' => 'credit',
                          '03' => 'debit',
                          default => (string) $note->note_type,
                        };
                        $noteDocumentNumber = (string) (
                          data_get($note->response_payload, 'resultado.numeroDocumento')
                          ?: data_get($note->response_payload, 'numeroDocumento')
                          ?: data_get($note->request_payload, 'encabezado.identificacionDocumento.numeroDocumento')
                          ?: preg_replace('/\D+/', '', (string) ($note->internal_number ?? $note->id))
                        );
                        $noteControlNumber = (string) (
                          data_get($note->response_payload, 'resultado.numeroControl')
                          ?: data_get($note->response_payload, 'estado.numeroControl')
                          ?: data_get($note->response_payload, 'numeroControl')
                          ?: ''
                        );
                        $originalAmountRaw = data_get($note->request_payload, 'encabezado.identificacionDocumento.montoFacturaAfectada', data_get(optional($note->electronicDocument)->request_payload, 'encabezado.totales.totalAPagar'));
                        $originalAmountSanitized = preg_replace('/[^0-9,.-]/', '', (string) $originalAmountRaw);
                        $originalAmountNormalized = str_contains($originalAmountSanitized, ',') && !str_contains($originalAmountSanitized, '.')
                          ? str_replace(',', '.', $originalAmountSanitized)
                          : str_replace(',', '', $originalAmountSanitized);
                        $originalAmount = is_numeric($originalAmountNormalized) ? (float) $originalAmountNormalized : null;
                        $noteAmount = round((float) ($note->amount ?? 0), 2);
                        $adjustmentAmountRaw = data_get($note->request_payload, 'encabezado.totales.totalAPagar', $noteAmount);
                        $adjustmentAmountSanitized = preg_replace('/[^0-9,.-]/', '', (string) $adjustmentAmountRaw);
                        $adjustmentAmountNormalized = str_contains($adjustmentAmountSanitized, ',') && !str_contains($adjustmentAmountSanitized, '.')
                          ? str_replace(',', '.', $adjustmentAmountSanitized)
                          : str_replace(',', '', $adjustmentAmountSanitized);
                        $adjustmentAmountDisplay = is_numeric($adjustmentAmountNormalized) ? (float) $adjustmentAmountNormalized : $noteAmount;
                        $impactApplied = ($note->status ?? '') === 'issued';
                        $estimatedNetAmount = null;
                        if (!is_null($originalAmount) && $impactApplied) {
                          $estimatedNetAmount = $effectiveNoteType === 'credit'
                            ? max(0, round($originalAmount - $adjustmentAmountDisplay, 2))
                            : round($originalAmount + $adjustmentAmountDisplay, 2);
                        }
                        $hkaCode = data_get($note->response_payload, 'codigo', data_get($note->response_payload, 'Codigo'));
                        $hkaMessage = data_get($note->response_payload, 'mensaje', data_get($note->response_payload, 'Mensaje'));
                        $noteConsultUrl = data_get($note->response_payload, 'resultado.urlConsulta', data_get($note->response_payload, 'estado.urlConsulta'));
                        $detailId = 'adjustmentNoteDetail' . $note->id;
                        $statusClass = $impactApplied
                          ? 'bg-gradient-success'
                          : (($note->status ?? '') === 'failed' ? 'bg-gradient-danger' : 'bg-gradient-secondary');
                        $statusLabel = match ((string) ($note->status ?? 'registered')) {
                          'issued' => 'Emitida',
                          'failed' => 'Fallida',
                          'registered' => 'Registrada',
                          default => ucfirst(str_replace('_', ' ', (string) ($note->status ?? 'registered'))),
                        };
                      @endphp
                      <tr>
                        <td>
                          <div>{{ $note->internal_number ?? 'N/A' }}</div>
                          @if($noteControlNumber !== '')
                            <small class="text-muted">Ctrl. HKA: {{ $noteControlNumber }}</small>
                          @endif
                        </td>
                        <td>{{ $effectiveNoteType === 'credit' ? 'Crédito' : 'Débito' }}</td>
                        <td>
                          <div>{{ $referenceNumber ?: 'N/A' }}</div>
                          <small class="text-muted">Ctrl: {{ $referenceControl ?: 'N/A' }}</small>
                        </td>
                        <td>
                          <div>{{ $displayCurrency ?: 'N/D' }}</div>
                          @if($noteCurrency !== '' && $noteCurrency !== $displayCurrency)
                            <small class="text-muted">Registro: {{ $noteCurrency }}</small>
                          @endif
                        </td>
                        <td>{{ optional($note->note_date)->format('d/m/Y') ?? 'N/A' }}</td>
                        <td>
                          @if(!is_null($originalAmount))
                            {{ $displayCurrency }} {{ number_format($originalAmount, 2) }}
                          @else
                            <span class="text-muted">N/D</span>
                          @endif
                        </td>
                        <td>{{ $displayCurrency }} {{ number_format($adjustmentAmountDisplay, 2) }}</td>
                        <td>
                          @if($impactApplied && !is_null($estimatedNetAmount))
                            <span class="fw-semibold">{{ $displayCurrency }} {{ number_format($estimatedNetAmount, 2) }}</span>
                          @elseif($impactApplied)
                            <span class="text-muted">Aplicada sin base visible</span>
                          @else
                            <span class="text-muted">No aplicado</span>
                          @endif
                        </td>
                        <td><span class="badge {{ $statusClass }}">{{ $statusLabel }}</span></td>
                        <td class="text-end">
                          <button
                            class="btn btn-sm btn-outline-dark mb-0"
                            type="button"
                            data-bs-toggle="collapse"
                            data-bs-target="#{{ $detailId }}"
                            aria-expanded="false"
                            aria-controls="{{ $detailId }}"
                          >
                            Ver detalle fiscal
                          </button>
                        </td>
                      </tr>
                      <tr class="collapse" id="{{ $detailId }}">
                        <td colspan="10" class="bg-light">
                          <div class="p-3">
                            <div class="row g-3">
                              <div class="col-md-4">
                                <strong>Resultado esperado</strong>
                                <div class="text-sm text-muted mt-1">
                                  @if($impactApplied && !is_null($estimatedNetAmount) && !is_null($originalAmount))
                                    Factura {{ $referenceNumber ?: 'N/A' }}: {{ $displayCurrency }} {{ number_format($originalAmount, 2) }}
                                    {{ $effectiveNoteType === 'credit' ? '-' : '+' }}
                                    {{ $displayCurrency }} {{ number_format($adjustmentAmountDisplay, 2) }}
                                    = {{ $displayCurrency }} {{ number_format($estimatedNetAmount, 2) }}.
                                  @elseif($impactApplied)
                                    La nota fue emitida, pero no hay monto original suficiente en el payload para calcular el neto aqui.
                                  @else
                                    Esta nota no altero la factura porque no quedo emitida en HKA.
                                  @endif
                                </div>
                                <div class="text-sm text-muted mt-2">Relación fiscal: factura {{ $referenceNumber ?: 'N/A' }} / control {{ $referenceControl ?: 'N/A' }}.</div>
                              </div>
                              <div class="col-md-4">
                                <strong>Datos del ajuste</strong>
                                <div class="text-sm text-muted mt-1">Moneda HKA: {{ $displayCurrency ?: 'N/D' }}</div>
                                <div class="text-sm text-muted">Moneda de registro: {{ $noteCurrency ?: 'N/D' }}</div>
                                <div class="text-sm text-muted mt-1">Motivo: {{ $note->reason ?: 'N/A' }}</div>
                                <div class="text-sm text-muted">Base: {{ $noteCurrency }} {{ number_format((float) ($note->taxable_base ?? 0), 2) }}</div>
                                <div class="text-sm text-muted">IVA: {{ $noteCurrency }} {{ number_format((float) ($note->tax_amount ?? 0), 2) }} @if(!is_null($note->tax_rate))({{ number_format((float) $note->tax_rate, 2) }}%)@endif</div>
                                <div class="text-sm text-muted">IGTF: {{ $noteCurrency }} {{ number_format((float) ($note->affected_igtf_amount ?? 0), 2) }}</div>
                              </div>
                              <div class="col-md-4">
                                <strong>Traza HKA</strong>
                                <div class="text-sm text-muted mt-1">Documento HKA: {{ $noteDocumentNumber !== '' ? $noteDocumentNumber : 'N/A' }}</div>
                                <div class="text-sm text-muted">Control HKA: {{ $noteControlNumber !== '' ? $noteControlNumber : 'N/A' }}</div>
                                <div class="text-sm text-muted mt-1">Codigo: {{ $hkaCode ?: 'N/A' }}</div>
                                <div class="text-sm text-muted">Mensaje: {{ $hkaMessage ?: 'Sin mensaje almacenado.' }}</div>
                                <div class="text-sm text-muted">Relacionada el: {{ optional($note->related_at)->format('d/m/Y H:i') ?? 'N/A' }}</div>
                                @if($noteConsultUrl)
                                  <div class="text-sm mt-2"><a href="{{ $noteConsultUrl }}" target="_blank" rel="noopener">Abrir consulta HKA</a></div>
                                @endif
                              </div>
                              <div class="col-12 d-flex flex-wrap gap-2">
                                @if($impactApplied)
                                  <a href="{{ route('sales.adjustmentNotes.download', ['note' => $note->id, 'tipo_archivo' => 'pdf', 'disposition' => 'attachment']) }}" class="btn btn-sm btn-outline-dark mb-0">Descargar PDF HKA</a>
                                  <a href="{{ route('sales.adjustmentNotes.download', ['note' => $note->id, 'tipo_archivo' => 'xml', 'disposition' => 'attachment']) }}" class="btn btn-sm btn-outline-secondary mb-0">Descargar XML HKA</a>
                                  <a href="{{ route('sales.adjustmentNotes.download', ['note' => $note->id, 'tipo_archivo' => 'json', 'disposition' => 'attachment']) }}" class="btn btn-sm btn-outline-secondary mb-0">Descargar JSON HKA</a>
                                @else
                                  <span class="text-sm text-muted">La descarga HKA se habilita cuando la nota queda emitida.</span>
                                @endif
                              </div>
                              @if(!empty($note->notes))
                                <div class="col-12">
                                  <strong>Observaciones internas</strong>
                                  <div class="text-sm text-muted mt-1">{{ $note->notes }}</div>
                                </div>
                              @endif
                            </div>
                          </div>
                        </td>
                      </tr>
                    @empty
                      <tr>
                        <td colspan="10" class="text-center text-muted">No hay notas registradas.</td>
                      </tr>
                    @endforelse
                  </tbody>
                </table>
              </div>
            </div>
          </div>
          </details>
        </div>

        <div class="col-12 col-xl-6">
          <details class="order-detail-disclosure">
            <summary>Retenciones</summary>
          <div class="card h-100">
            <div class="card-header pb-0">
              <h6 class="mb-0">Retenciones</h6>
              <p class="text-sm text-muted mb-0">Registra aquí retenciones aplicadas por el cliente. Shopix las aplica al saldo interno y puede sincronizarlas con HKA para rastreo fiscal.</p>
            </div>
            <div class="card-body">
              <div class="alert alert-info text-white bg-info" role="alert">
                Al registrar la retención, Shopix la aplica al saldo interno y también intenta sincronizarla con HKA si existe una factura fiscal activa y un comprobante válido. Luego puedes usar los botones de cada fila para reenviar o consultar su estado en HKA.
              </div>
              <div class="alert alert-light border" role="alert">
                <div class="row g-2">
                  <div class="col-md-4">
                    <strong>IVA facturado</strong><br>
                    {{ $orderCurrencyCode ?? ($order->sale_currency_code ?? 'USD') }} {{ number_format($suggestedRetentionIvaBase, 2) }}
                  </div>
                  <div class="col-md-4">
                    <strong>Base de venta</strong><br>
                    {{ $orderCurrencyCode ?? ($order->sale_currency_code ?? 'USD') }} {{ number_format($suggestedRetentionIncomeBase, 2) }}
                  </div>
                  <div class="col-md-4">
                    <strong>Saldo pendiente</strong><br>
                    {{ $orderCurrencySymbol ?? '$' }}{{ number_format($paymentBalance, 2) }}
                  </div>
                </div>
              </div>

              <div class="row g-3 mb-3">
                <div class="col-md-4">
                  <div class="border rounded p-3 h-100 bg-light">
                    <strong>Se sincroniza por API</strong>
                    <p class="text-sm text-muted mb-0 mt-2">HKA recibe la retención por <strong>AplicarRetencion</strong> usando la factura relacionada. Shopix conserva solicitud y respuesta para auditoría.</p>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="border rounded p-3 h-100 bg-light">
                    <strong>Moneda en HKA</strong>
                    <p class="text-sm text-muted mb-0 mt-2">El API de retenciones no expone un campo de moneda en la descarga. Shopix toma la moneda registrada y convierte los montos a la moneda fiscal de la factura antes de sincronizar.</p>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="border rounded p-3 h-100 bg-light">
                    <strong>Descarga permitida</strong>
                    <p class="text-sm text-muted mb-0 mt-2">El comprobante PDF se genera en Shopix con la retención registrada. Además puedes descargar el JSON técnico con la solicitud y respuesta HKA como evidencia de integración.</p>
                  </div>
                </div>
              </div>

              <div class="row g-3 mb-3">
                <div class="col-md-4">
                  <div class="border rounded p-3 h-100 bg-light">
                    <strong>Paso 1</strong>
                    <p class="text-sm text-muted mb-0">Selecciona el tipo de retención. Para IVA, la base sugerida es el IVA facturado, no el total completo de la venta.</p>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="border rounded p-3 h-100 bg-light">
                    <strong>Paso 2</strong>
                    <p class="text-sm text-muted mb-0">Ingresa tasa y comprobante. Si es IVA, el comprobante debe tener formato SENIAT: período `YYYYMM` + correlativo de 8 dígitos.</p>
                  </div>
                </div>
                <div class="col-md-4">
                  <div class="border rounded p-3 h-100 bg-light">
                    <strong>Paso 3</strong>
                    <p class="text-sm text-muted mb-0">Al guardar, la retención se aplica como pago interno y reduce el saldo pendiente de la orden.</p>
                  </div>
                </div>
              </div>

              @if($errors->has('retention_type') || $errors->has('retention_date') || $errors->has('retention_rate') || $errors->has('taxable_base') || $errors->has('retained_amount') || $errors->has('certificate_number'))
                <div class="alert alert-danger" role="alert">
                  <strong>No se pudo registrar la retención.</strong>
                  <ul class="mb-0 mt-2 ps-3">
                    @foreach (['retention_type', 'retention_date', 'retention_rate', 'taxable_base', 'retained_amount', 'certificate_number'] as $retentionErrorField)
                      @error($retentionErrorField)
                        <li>{{ $message }}</li>
                      @enderror
                    @endforeach
                  </ul>
                </div>
              @endif

              <form method="POST" action="{{ route('sales.retentions.store', $order->id) }}" class="row g-3">
                @csrf
                <div class="col-md-4">
                  <label class="form-label">Tipo</label>
                  <select name="retention_type" id="retentionType" class="form-select border border-1 p-2" required>
                    <option value="iva" {{ old('retention_type') === 'iva' || !old('retention_type') ? 'selected' : '' }}>IVA</option>
                    <option value="islr" {{ old('retention_type') === 'islr' ? 'selected' : '' }}>ISLR</option>
                    <option value="municipal" {{ old('retention_type') === 'municipal' ? 'selected' : '' }}>Municipal</option>
                    <option value="other" {{ old('retention_type') === 'other' ? 'selected' : '' }}>Otra</option>
                  </select>
                </div>
                <div class="col-md-4">
                  <label class="form-label">Fecha</label>
                  <input type="date" name="retention_date" class="form-control border border-1 p-2" value="{{ old('retention_date', now()->toDateString()) }}" required>
                </div>
                <div class="col-md-4">
                  <label class="form-label">Tasa (%)</label>
                  <input type="number" id="retentionRate" name="retention_rate" min="0" max="100" step="0.01" class="form-control border border-1 p-2" value="{{ old('retention_rate', '75.00') }}" required data-decimal-friendly="true">
                </div>
                <div class="col-md-4">
                  <label class="form-label">Base imponible</label>
                  <input type="number" id="retentionTaxableBase" name="taxable_base" min="0.01" step="0.01" class="form-control border border-1 p-2" value="{{ old('taxable_base', number_format($suggestedRetentionIvaBase > 0 ? $suggestedRetentionIvaBase : $suggestedRetentionIncomeBase, 2, '.', '')) }}" required data-decimal-friendly="true">
                  <small class="text-muted" id="retentionBaseHint">Para IVA se sugiere usar el IVA facturado: {{ number_format($suggestedRetentionIvaBase, 2) }}.</small>
                </div>
                <div class="col-md-4">
                  <label class="form-label">Monto retenido</label>
                  <input type="number" id="retentionRetainedAmount" name="retained_amount" min="0.01" step="0.01" class="form-control border border-1 p-2" value="{{ old('retained_amount', $suggestedRetentionIva75 > 0 ? number_format($suggestedRetentionIva75, 2, '.', '') : '') }}" data-decimal-friendly="true">
                  <small class="text-muted">Si no lo modificas, el sistema puede sugerirlo según Base x Tasa.</small>
                </div>
                <div class="col-md-4">
                  <label class="form-label">Comprobante</label>
                  <input type="text" id="retentionCertificateNumber" name="certificate_number" class="form-control border border-1 p-2" value="{{ old('certificate_number') }}" maxlength="14" inputmode="numeric" pattern="\d{14}" placeholder="YYYYMM########">
                  <small class="text-muted" id="retentionCertificateHint">Para IVA usa formato SENIAT: YYYYMM + 8 digitos.</small>
                </div>
                <div class="col-12">
                  <div class="border rounded p-3 bg-light">
                    <strong>Vista previa de aplicación</strong>
                    <div class="text-sm text-muted mt-1" id="retentionPreview">La retención registrada reducirá el saldo pendiente de esta orden cuando sea guardada.</div>
                  </div>
                </div>
                <div class="col-12">
                  <label class="form-label">Observaciones</label>
                  <textarea name="notes" class="form-control border border-1 p-2" rows="2" placeholder="Ej. Retención practicada por el cliente según comprobante entregado.">{{ old('notes') }}</textarea>
                </div>
                <div class="col-12 d-flex justify-content-end">
                  <button type="submit" class="btn btn-dark mb-0">Registrar retención</button>
                </div>
              </form>

              <div class="table-responsive mt-4">
                <table class="table align-items-center mb-0">
                  <thead>
                    <tr>
                      <th>Correlativo</th>
                      <th>Fecha</th>
                      <th>Tipo</th>
                      <th>Documento relacionado</th>
                      <th>Moneda</th>
                      <th>Comprobante</th>
                      <th>Monto</th>
                      <th>Interno</th>
                      <th>HKA</th>
                      <th>Acciones</th>
                    </tr>
                  </thead>
                  <tbody>
                    @forelse($order->retentions as $retention)
                      @php
                        $retentionApplyPayload = data_get($retention->response_payload, 'apply', []);
                        $retentionStatusPayload = data_get($retention->response_payload, 'status', []);
                        $retentionReferenceNumber = data_get($retention->request_payload, 'apply.numeroDocumento', optional($retention->electronicDocument)->numero_documento ?: ($edoc->numero_documento ?? null));
                        $retentionReferenceControl = data_get($retention->request_payload, 'apply.numeroControl', optional($retention->electronicDocument)->numero_control ?: ($edoc->numero_control ?? null));
                        $retentionCurrency = strtoupper((string) ($retention->currency_code ?: ($orderCurrencyCode ?? ($order->sale_currency_code ?? 'USD'))));
                        $retentionHkaCurrency = strtoupper((string) (data_get(optional($retention->electronicDocument)->request_payload, 'encabezado.identificacionDocumento.moneda') ?: data_get($edoc?->request_payload ?? [], 'encabezado.identificacionDocumento.moneda') ?: $retentionCurrency));
                        $retentionHkaCode = (string) (data_get($retentionApplyPayload, 'codigo') ?: data_get($retentionStatusPayload, 'codigo') ?: '');
                        $retentionHkaMessage = (string) (data_get($retentionApplyPayload, 'mensaje') ?: data_get($retentionStatusPayload, 'mensaje') ?: '');
                        $retentionHkaBadge = str_contains((string) $retention->status, 'hka_error')
                          ? 'bg-gradient-danger'
                          : (str_contains((string) $retention->status, 'hka') ? 'bg-gradient-success' : 'bg-gradient-secondary');
                        $retentionHkaLabel = str_contains((string) $retention->status, 'hka_error')
                          ? 'Con error'
                          : (str_contains((string) $retention->status, 'hka') ? 'Sincronizada' : 'Pendiente');
                        $retentionStatusClass = match ((string) ($retention->status ?? 'registered')) {
                          'registered' => 'bg-gradient-secondary',
                          'applied' => 'bg-gradient-info',
                          'applied_hka' => 'bg-gradient-success',
                          'applied_hka_error' => 'bg-gradient-warning',
                          default => 'bg-gradient-secondary',
                        };
                        $retentionStatusLabel = match ((string) ($retention->status ?? 'registered')) {
                          'registered' => 'Registrada',
                          'applied' => 'Aplicada',
                          'applied_hka' => 'Aplicada + HKA',
                          'applied_hka_error' => 'Aplicada con error HKA',
                          default => ucfirst(str_replace('_', ' ', (string) ($retention->status ?? 'registered'))),
                        };
                        $retentionDetailId = 'retentionDetail' . $retention->id;
                      @endphp
                      <tr>
                        <td>{{ $retention->internal_number ?? 'N/A' }}</td>
                        <td>{{ optional($retention->retention_date)->format('d/m/Y') ?? 'N/A' }}</td>
                        <td>{{ strtoupper($retention->retention_type ?? 'N/A') }}</td>
                        <td>
                          <div>{{ $retentionReferenceNumber ?: 'N/A' }}</div>
                          <small class="text-muted">Ctrl: {{ $retentionReferenceControl ?: 'N/A' }}</small>
                        </td>
                        <td>
                          <div>{{ $retentionCurrency ?: 'N/D' }}</div>
                          @if($retentionHkaCurrency !== '' && $retentionHkaCurrency !== $retentionCurrency)
                            <small class="text-muted">HKA: {{ $retentionHkaCurrency }}</small>
                          @endif
                        </td>
                        <td>{{ $retention->certificate_number ?? 'N/A' }}</td>
                        <td>{{ $retentionCurrency }} {{ number_format((float) $retention->retained_amount, 2) }}</td>
                        <td><span class="badge {{ $retentionStatusClass }}">{{ $retentionStatusLabel }}</span></td>
                        <td>
                          <span class="badge {{ $retentionHkaBadge }}">{{ $retentionHkaLabel }}</span>
                          @if($retentionHkaCode !== '')
                            <div class="text-xs text-muted mt-1">Código: {{ $retentionHkaCode }}</div>
                          @endif
                          @if($retentionHkaMessage !== '')
                            <div class="text-xs text-muted">{{ \Illuminate\Support\Str::limit($retentionHkaMessage, 90) }}</div>
                          @endif
                        </td>
                        <td>
                          <div class="d-flex flex-column gap-2">
                            <button
                              class="btn btn-outline-dark btn-sm mb-0 w-100"
                              type="button"
                              data-bs-toggle="collapse"
                              data-bs-target="#{{ $retentionDetailId }}"
                              aria-expanded="false"
                              aria-controls="{{ $retentionDetailId }}"
                            >
                              Ver detalle
                            </button>
                            <form method="POST" action="{{ route('sales.retentions.syncHka', $retention->id) }}">
                              @csrf
                              <button type="submit" class="btn btn-outline-dark btn-sm mb-0 w-100">Enviar/Actualizar HKA</button>
                            </form>
                            <form method="POST" action="{{ route('sales.retentions.statusHka', $retention->id) }}">
                              @csrf
                              <button type="submit" class="btn btn-outline-secondary btn-sm mb-0 w-100">Consultar HKA</button>
                            </form>
                            <a href="{{ route('sales.retentions.certificate', ['retention' => $retention->id, 'disposition' => 'inline']) }}" class="btn btn-outline-secondary btn-sm mb-0 w-100">Ver comprobante PDF</a>
                            <a href="{{ route('sales.retentions.download', ['retention' => $retention->id, 'disposition' => 'attachment']) }}" class="btn btn-outline-secondary btn-sm mb-0 w-100">Descargar JSON HKA</a>
                          </div>
                        </td>
                      </tr>
                      <tr class="collapse" id="{{ $retentionDetailId }}">
                        <td colspan="10" class="bg-light">
                          <div class="p-3">
                            <div class="row g-3">
                              <div class="col-md-4">
                                <strong>Relación fiscal</strong>
                                <div class="text-sm text-muted mt-1">Factura relacionada: {{ $retentionReferenceNumber ?: 'N/A' }}</div>
                                <div class="text-sm text-muted">Control: {{ $retentionReferenceControl ?: 'N/A' }}</div>
                                <div class="text-sm text-muted">Comprobante: {{ $retention->certificate_number ?: 'N/A' }}</div>
                              </div>
                              <div class="col-md-4">
                                <strong>Montos y moneda</strong>
                                <div class="text-sm text-muted mt-1">Moneda de registro: {{ $retentionCurrency ?: 'N/D' }}</div>
                                <div class="text-sm text-muted">Moneda fiscal HKA: {{ $retentionHkaCurrency ?: 'N/D' }}</div>
                                <div class="text-sm text-muted">Base: {{ $retentionCurrency }} {{ number_format((float) ($retention->taxable_base ?? 0), 2) }}</div>
                                <div class="text-sm text-muted">Retenido: {{ $retentionCurrency }} {{ number_format((float) ($retention->retained_amount ?? 0), 2) }}</div>
                                <div class="text-sm text-muted">Tasa: {{ number_format((float) ($retention->retention_rate ?? 0), 2) }}%</div>
                              </div>
                              <div class="col-md-4">
                                <strong>Traza HKA</strong>
                                <div class="text-sm text-muted mt-1">Código: {{ $retentionHkaCode ?: 'N/A' }}</div>
                                <div class="text-sm text-muted">Mensaje: {{ $retentionHkaMessage ?: 'Sin mensaje almacenado.' }}</div>
                                <div class="text-sm text-muted">Aplicada el: {{ optional($retention->applied_at)->format('d/m/Y H:i') ?? 'N/A' }}</div>
                              </div>
                              @if(!empty($retention->notes))
                                <div class="col-12">
                                  <strong>Observaciones internas</strong>
                                  <div class="text-sm text-muted mt-1">{{ $retention->notes }}</div>
                                </div>
                              @endif
                            </div>
                          </div>
                        </td>
                      </tr>
                    @empty
                      <tr>
                        <td colspan="10" class="text-center text-muted">No hay retenciones registradas.</td>
                      </tr>
                    @endforelse
                  </tbody>
                </table>
              </div>
            </div>
          </div>
          </details>
        </div>
      </div>
      @endif
      @endunless

      <div class="modal fade" id="editPaymentEntryModal" tabindex="-1" aria-labelledby="editPaymentEntryModalLabel" aria-hidden="true">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="editPaymentEntryModalLabel">Editar pago registrado</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
              <form id="editPaymentEntryForm">
                <input type="hidden" id="editPaymentEntryId">
                <div class="mb-3">
                  <label for="editPaymentEntryAmount" class="form-label">Monto</label>
                  <input type="number" id="editPaymentEntryAmount" class="form-control border border-1 p-2" min="0.01" step="0.01" required>
                </div>
                <div class="mb-3">
                  <label for="editPaymentEntryReference" class="form-label">Referencia</label>
                  <input type="text" id="editPaymentEntryReference" class="form-control border border-1 p-2" maxlength="255" placeholder="Opcional">
                </div>
                <div class="mb-3">
                  <label for="editPaymentEntryProof" class="form-label">Comprobante (imagen)</label>
                  <input type="file" id="editPaymentEntryProof" class="form-control border border-1 p-2" accept="image/jpeg,image/png,image/jpg,image/webp">
                  <small id="editPaymentEntryProofCurrent" class="text-muted d-block mt-1">Sin imagen actual.</small>
                </div>
              </form>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-outline-secondary mb-0" data-bs-dismiss="modal">Cancelar</button>
              <button type="button" class="btn btn-dark mb-0" id="savePaymentEntryChangesBtn">Guardar cambios</button>
            </div>
          </div>
        </div>
      </div>

      <div class="modal fade" id="createPaymentEntryModal" tabindex="-1" aria-labelledby="createPaymentEntryModalLabel" aria-hidden="true">
        <div class="modal-dialog">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="createPaymentEntryModalLabel">Agregar pago</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
              <form id="createPaymentEntryForm">
                <div class="mb-3 border rounded p-2 bg-light">
                  <div class="d-flex justify-content-between align-items-center">
                    <span class="text-sm text-muted">Cuánto debo</span>
                    <strong id="createPaymentDebtLabel">{{ number_format((float) $paymentBalance, 2, '.', '') }} {{ $orderCurrencyCode ?? 'USD' }}</strong>
                  </div>
                  <div class="d-flex justify-content-between align-items-center mt-1">
                    <span class="text-sm text-muted">Cuánto estoy pagando</span>
                    <strong id="createPaymentPayingLabel">0.00 {{ $orderCurrencyCode ?? 'USD' }}</strong>
                  </div>
                  <div class="d-flex justify-content-between align-items-center mt-1">
                    <span class="text-sm text-muted">Monto restante</span>
                    <strong id="createPaymentRemainingLabel">{{ number_format((float) $paymentBalance, 2, '.', '') }} {{ $orderCurrencyCode ?? 'USD' }}</strong>
                  </div>
                  <small id="createPaymentDualHint" class="text-muted d-block mt-1">{{ $formatDualAmount((float) $paymentBalance, $orderCurrencyCode ?? ($order->sale_currency_code ?? 'USD')) }}</small>
                </div>
                <div class="mb-3">
                  <label for="createPaymentEntryCurrency" class="form-label">Moneda para pagar</label>
                  <select id="createPaymentEntryCurrency" class="form-control border border-1 p-2" required>
                    @foreach($availablePaymentCurrencies as $currencyCode)
                      <option value="{{ $currencyCode }}" {{ $loop->first ? 'selected' : '' }}>{{ $currencyCode }}</option>
                    @endforeach
                  </select>
                </div>
                <div class="mb-3">
                  <label for="createPaymentEntryMethod" class="form-label">Método de pago</label>
                  <select id="createPaymentEntryMethod" class="form-control border border-1 p-2" required>
                    <option value="">Selecciona un método</option>
                    @foreach(($paymentMethods ?? collect()) as $method)
                      <option
                        value="{{ $method->id }}"
                        data-currency-code="{{ strtoupper(trim((string) ($method->currency->code ?? ''))) }}"
                        data-has-reference="{{ $method->usesReference() ? '1' : '0' }}"
                        data-requires-proof="{{ $method->requiresProofImage() ? '1' : '0' }}"
                      >
                        {{ $method->name }}{{ !empty($method->currency?->code) ? ' · ' . strtoupper((string) $method->currency->code) : '' }}
                      </option>
                    @endforeach
                  </select>
                </div>
                <div class="mb-3">
                  <label for="createPaymentEntryAmount" class="form-label">Monto</label>
                  <input type="number" id="createPaymentEntryAmount" class="form-control border border-1 p-2" min="0.01" step="0.01" required>
                  <div class="d-flex justify-content-end mt-2">
                    <button type="button" class="btn btn-sm btn-outline-dark mb-0" id="fillRemainingPaymentAmountBtn">Colocar monto restante</button>
                  </div>
                </div>
                <div class="mb-3">
                  <label for="createPaymentEntryReference" class="form-label">Referencia</label>
                  <input type="text" id="createPaymentEntryReference" class="form-control border border-1 p-2" maxlength="255" placeholder="Opcional">
                </div>
                <div class="mb-3">
                  <label for="createPaymentEntryProof" class="form-label">Comprobante (imagen)</label>
                  <input type="file" id="createPaymentEntryProof" class="form-control border border-1 p-2" accept="image/jpeg,image/png,image/jpg,image/webp">
                  <small id="createPaymentEntryHint" class="text-muted d-block mt-1">Si el método lo requiere, adjunta referencia y comprobante.</small>
                </div>
              </form>
            </div>
            <div class="modal-footer">
              <button type="button" class="btn btn-outline-secondary mb-0" data-bs-dismiss="modal">Cancelar</button>
              <button type="button" class="btn btn-dark mb-0" id="saveCreatePaymentEntryBtn">Guardar pago</button>
            </div>
          </div>
        </div>
      </div>

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
                                          <th>Destino</th>
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
                                            <td data-label="Destino">
                                              <select class="form-select border border-1 border-radius-lg p-2 return-disposition" data-id="{{ $detalle->variant->id }}">
                                                <option value="resalable">Apto para venta</option>
                                                <option value="damaged">Merma / dañado</option>
                                                <option value="no_physical_return">No retorna físicamente</option>
                                              </select>
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

  .order-detail-disclosure {
    border: 1px solid rgba(148, 163, 184, 0.22);
    border-radius: 1rem;
    background: rgba(255, 255, 255, 0.95);
    padding: 0.5rem;
    box-shadow: 0 14px 30px -28px rgba(15, 23, 42, 0.45);
  }

  .order-detail-disclosure > summary {
    list-style: none;
    cursor: pointer;
    font-weight: 700;
    color: #0f172a;
    padding: 0.55rem 0.75rem;
    border-radius: 0.7rem;
    background: #f1f5f9;
    margin-bottom: 0.5rem;
    position: relative;
    user-select: none;
  }

  .order-detail-disclosure > summary::after {
    content: '+';
    position: absolute;
    right: 0.75rem;
    top: 50%;
    transform: translateY(-50%);
    font-size: 1rem;
    font-weight: 800;
    color: #334155;
  }

  .order-detail-disclosure[open] > summary::after {
    content: '-';
  }

  .order-detail-disclosure > :not(summary) {
    overflow: hidden;
    max-height: 0;
    opacity: 0;
    transform: translateY(-6px);
    transition: max-height 0.35s ease, opacity 0.28s ease, transform 0.28s ease;
    pointer-events: none;
  }

  .order-detail-disclosure[open] > :not(summary) {
    max-height: 400rem;
    opacity: 1;
    transform: translateY(0);
    pointer-events: auto;
  }

  .order-detail-disclosure > summary::-webkit-details-marker {
    display: none;
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

  .linked-appointment-payment-metric {
    border: 1px solid rgba(148, 163, 184, 0.22);
    border-radius: 0.9rem;
    padding: 0.65rem 0.75rem;
    background: #f8fafc;
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

function openEditPaymentEntryModal(triggerButton) {
  const paymentIdInput = document.getElementById('editPaymentEntryId');
  const amountInput = document.getElementById('editPaymentEntryAmount');
  const referenceInput = document.getElementById('editPaymentEntryReference');
  const proofInput = document.getElementById('editPaymentEntryProof');
  const proofCurrent = document.getElementById('editPaymentEntryProofCurrent');
  const modalElement = document.getElementById('editPaymentEntryModal');

  if (!paymentIdInput || !amountInput || !referenceInput || !modalElement) {
    return;
  }

  paymentIdInput.value = String(triggerButton.dataset.paymentId || '');
  amountInput.value = String(triggerButton.dataset.paymentAmount || '0.00');
  referenceInput.value = String(triggerButton.dataset.paymentReference || '');

  if (proofInput) {
    proofInput.value = '';
  }

  const currentProofUrl = String(triggerButton.dataset.paymentProofUrl || '').trim();
  if (proofCurrent) {
    proofCurrent.innerHTML = currentProofUrl !== ''
      ? `<a href="${currentProofUrl}" target="_blank" rel="noopener">Ver comprobante actual</a>`
      : 'Sin imagen actual.';
  }

  const modalInstance = bootstrap.Modal.getOrCreateInstance(modalElement);
  modalInstance.show();
}

function openCreatePaymentEntryModal() {
  const modalElement = document.getElementById('createPaymentEntryModal');
  const pendingInOrderCurrency = Number({{ \Illuminate\Support\Js::from((float) $paymentBalance) }} || 0);
  const currencySelect = document.getElementById('createPaymentEntryCurrency');
  const methodSelect = document.getElementById('createPaymentEntryMethod');
  const amountInput = document.getElementById('createPaymentEntryAmount');
  const referenceInput = document.getElementById('createPaymentEntryReference');
  const proofInput = document.getElementById('createPaymentEntryProof');

  if (!modalElement || pendingInOrderCurrency <= 0) {
    if (pendingInOrderCurrency <= 0) {
      alert('Esta orden ya está completamente pagada.');
    }
    return;
  }

  if (currencySelect && currencySelect.options.length > 0) {
    currencySelect.selectedIndex = 0;
  }
  if (methodSelect) {
    methodSelect.value = '';
  }
  if (amountInput) {
    amountInput.value = '';
  }
  if (referenceInput) {
    referenceInput.value = '';
  }
  if (proofInput) {
    proofInput.value = '';
  }

  syncCreatePaymentMethodFilter();
  syncCreatePaymentSummary();
  syncCreatePaymentEntryHint();
  bootstrap.Modal.getOrCreateInstance(modalElement).show();
}

function getCreatePaymentCurrencyRateSnapshot() {
  const orderCurrencyCode = String({{ \Illuminate\Support\Js::from($orderCurrencyCode ?? 'USD') }} || 'USD').toUpperCase();
  const rateToBs = Number({{ \Illuminate\Support\Js::from((float) ($orderRateToBsSnapshot ?? 1)) }} || 0);

  return {
    orderCurrencyCode,
    rateToBs: Number.isFinite(rateToBs) && rateToBs > 0 ? rateToBs : 1,
  };
}

function convertCreatePaymentAmountToOrderCurrency(amount, fromCurrency) {
  const { orderCurrencyCode, rateToBs } = getCreatePaymentCurrencyRateSnapshot();
  const source = String(fromCurrency || '').toUpperCase();
  if (!Number.isFinite(amount)) {
    return 0;
  }

  if (source === orderCurrencyCode) {
    return amount;
  }

  if (source === 'BS' && orderCurrencyCode === 'USD') {
    return amount / rateToBs;
  }

  if (source === 'USD' && orderCurrencyCode === 'BS') {
    return amount * rateToBs;
  }

  return amount;
}

function convertCreatePaymentAmountFromOrderCurrency(amount, toCurrency) {
  const { orderCurrencyCode, rateToBs } = getCreatePaymentCurrencyRateSnapshot();
  const target = String(toCurrency || '').toUpperCase();
  if (!Number.isFinite(amount)) {
    return 0;
  }

  if (target === orderCurrencyCode) {
    return amount;
  }

  if (target === 'BS' && orderCurrencyCode === 'USD') {
    return amount * rateToBs;
  }

  if (target === 'USD' && orderCurrencyCode === 'BS') {
    return amount / rateToBs;
  }

  return amount;
}

function syncCreatePaymentMethodFilter() {
  const currencySelect = document.getElementById('createPaymentEntryCurrency');
  const methodSelect = document.getElementById('createPaymentEntryMethod');
  if (!currencySelect || !methodSelect) {
    return;
  }

  const selectedCurrency = String(currencySelect.value || '').toUpperCase();
  const currentValue = String(methodSelect.value || '');
  let hasCurrentOption = false;

  Array.from(methodSelect.options).forEach((option) => {
    if (!option.value) {
      option.hidden = false;
      option.disabled = false;
      return;
    }

    const optionCurrency = String(option.dataset.currencyCode || '').toUpperCase();
    const visible = optionCurrency === selectedCurrency;
    option.hidden = !visible;
    option.disabled = !visible;

    if (visible && option.value === currentValue) {
      hasCurrentOption = true;
    }
  });

  if (!hasCurrentOption) {
    methodSelect.value = '';
  }

  syncCreatePaymentEntryHint();
}

function syncCreatePaymentSummary() {
  const currencySelect = document.getElementById('createPaymentEntryCurrency');
  const amountInput = document.getElementById('createPaymentEntryAmount');
  const debtLabel = document.getElementById('createPaymentDebtLabel');
  const payingLabel = document.getElementById('createPaymentPayingLabel');
  const remainingLabel = document.getElementById('createPaymentRemainingLabel');
  const dualHint = document.getElementById('createPaymentDualHint');

  if (!currencySelect || !debtLabel || !payingLabel || !remainingLabel) {
    return;
  }

  const selectedCurrency = String(currencySelect.value || '').toUpperCase();
  const pendingInOrderCurrency = Number({{ \Illuminate\Support\Js::from((float) $paymentBalance) }} || 0);
  const payingInSelected = Number(amountInput?.value || 0);
  const payingInOrder = convertCreatePaymentAmountToOrderCurrency(payingInSelected, selectedCurrency);
  const debtInSelected = convertCreatePaymentAmountFromOrderCurrency(pendingInOrderCurrency, selectedCurrency);
  const remainingInOrder = Math.max(0, pendingInOrderCurrency - Math.max(0, payingInOrder));
  const remainingInSelected = convertCreatePaymentAmountFromOrderCurrency(remainingInOrder, selectedCurrency);

  debtLabel.textContent = `${debtInSelected.toFixed(2)} ${selectedCurrency}`;
  payingLabel.textContent = `${Math.max(0, payingInSelected).toFixed(2)} ${selectedCurrency}`;
  remainingLabel.textContent = `${Math.max(0, remainingInSelected).toFixed(2)} ${selectedCurrency}`;

  if (dualHint) {
    dualHint.textContent = `Pendiente en moneda de la orden: ${pendingInOrderCurrency.toFixed(2)} ${getCreatePaymentCurrencyRateSnapshot().orderCurrencyCode} | Restante: ${remainingInOrder.toFixed(2)} ${getCreatePaymentCurrencyRateSnapshot().orderCurrencyCode}`;
  }
}

function fillCreatePaymentRemainingAmount() {
  const currencySelect = document.getElementById('createPaymentEntryCurrency');
  const amountInput = document.getElementById('createPaymentEntryAmount');
  if (!currencySelect || !amountInput) {
    return;
  }

  const selectedCurrency = String(currencySelect.value || '').toUpperCase();
  const pendingInOrderCurrency = Number({{ \Illuminate\Support\Js::from((float) $paymentBalance) }} || 0);
  const amountToFill = convertCreatePaymentAmountFromOrderCurrency(pendingInOrderCurrency, selectedCurrency);
  amountInput.value = amountToFill > 0 ? amountToFill.toFixed(2) : '0.00';
  syncCreatePaymentSummary();
}

function syncCreatePaymentEntryHint() {
  const methodSelect = document.getElementById('createPaymentEntryMethod');
  const hint = document.getElementById('createPaymentEntryHint');
  if (!methodSelect || !hint) {
    return;
  }

  const selectedOption = methodSelect.selectedOptions?.[0] || null;
  const requiresReference = String(selectedOption?.dataset?.hasReference || '0') === '1';
  const requiresProof = String(selectedOption?.dataset?.requiresProof || '0') === '1';

  if (!selectedOption || !selectedOption.value) {
    hint.textContent = 'Si el método lo requiere, adjunta referencia y comprobante.';
    return;
  }

  if (requiresReference && requiresProof) {
    hint.textContent = 'Este método requiere referencia y comprobante.';
    return;
  }

  if (requiresReference) {
    hint.textContent = 'Este método requiere referencia.';
    return;
  }

  if (requiresProof) {
    hint.textContent = 'Este método requiere comprobante.';
    return;
  }

  hint.textContent = 'Puedes adjuntar comprobante opcionalmente.';
}

function saveCreatePaymentEntry() {
  const currencySelect = document.getElementById('createPaymentEntryCurrency');
  const methodSelect = document.getElementById('createPaymentEntryMethod');
  const amountInput = document.getElementById('createPaymentEntryAmount');
  const referenceInput = document.getElementById('createPaymentEntryReference');
  const proofInput = document.getElementById('createPaymentEntryProof');
  const saveButton = document.getElementById('saveCreatePaymentEntryBtn');

  const paymentMethodId = Number(methodSelect?.value || 0);
  const selectedCurrency = String(currencySelect?.value || '').toUpperCase();
  const amount = Number(amountInput?.value || 0);
  const reference = String(referenceInput?.value || '').trim();
  const proofFile = proofInput?.files?.[0] || null;

  const selectedOption = methodSelect?.selectedOptions?.[0] || null;
  const requiresReference = String(selectedOption?.dataset?.hasReference || '0') === '1';
  const requiresProof = String(selectedOption?.dataset?.requiresProof || '0') === '1';

  if (paymentMethodId <= 0) {
    alert('Selecciona un método de pago.');
    return;
  }

  if (!Number.isFinite(amount) || amount <= 0) {
    alert('Ingresa un monto válido mayor a 0.');
    return;
  }

  const pendingInOrderCurrency = Number({{ \Illuminate\Support\Js::from((float) $paymentBalance) }} || 0);
  const payingInOrderCurrency = convertCreatePaymentAmountToOrderCurrency(amount, selectedCurrency);
  if (payingInOrderCurrency - pendingInOrderCurrency > 0.00001) {
    alert('El monto a pagar no puede superar el saldo pendiente.');
    return;
  }

  if (requiresReference && reference === '') {
    alert('Este método de pago requiere referencia.');
    return;
  }

  if (requiresProof && !proofFile) {
    alert('Este método de pago requiere comprobante.');
    return;
  }

  const payload = new FormData();
  payload.append('payment_method_id', String(paymentMethodId));
  payload.append('amount', amount.toFixed(2));
  payload.append('currency', selectedCurrency);
  payload.append('reference', reference);
  if (proofFile) {
    payload.append('proof_image', proofFile);
  }

  if (saveButton) {
    saveButton.disabled = true;
    saveButton.textContent = 'Guardando...';
  }

  fetch(`/api/payment/order/{{ $order->id }}/create`, {
    method: 'POST',
    headers: {
      'X-CSRF-TOKEN': '{{ csrf_token() }}',
      'Accept': 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
    },
    body: payload,
  })
    .then(async response => {
      const data = await response.json().catch(() => ({}));
      if (!response.ok || !data.success) {
        throw new Error(data.message || 'No se pudo registrar el pago.');
      }

      alert(data.message || 'Pago registrado correctamente.');
      location.reload();
    })
    .catch(error => {
      alert(String(error?.message || 'No se pudo registrar el pago.'));
    })
    .finally(() => {
      if (saveButton) {
        saveButton.disabled = false;
        saveButton.textContent = 'Guardar pago';
      }
    });
}

function savePaymentEntryChanges() {
  const paymentId = Number(document.getElementById('editPaymentEntryId')?.value || 0);
  const amount = Number(document.getElementById('editPaymentEntryAmount')?.value || 0);
  const reference = String(document.getElementById('editPaymentEntryReference')?.value || '');
  const proofInput = document.getElementById('editPaymentEntryProof');
  const saveButton = document.getElementById('savePaymentEntryChangesBtn');

  if (!paymentId) {
    alert('No se pudo identificar el pago a editar.');
    return;
  }

  if (!Number.isFinite(amount) || amount <= 0) {
    alert('Ingresa un monto válido mayor a 0.');
    return;
  }

  const payload = new FormData();
  payload.append('amount', amount.toFixed(2));
  payload.append('reference', reference.trim());
  if (proofInput?.files?.[0]) {
    payload.append('proof_image', proofInput.files[0]);
  }

  if (saveButton) {
    saveButton.disabled = true;
    saveButton.textContent = 'Guardando...';
  }

  fetch(`/api/payment/${paymentId}/update`, {
    method: 'POST',
    headers: {
      'X-CSRF-TOKEN': '{{ csrf_token() }}',
      'Accept': 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
    },
    body: payload,
  })
    .then(async response => {
      const data = await response.json().catch(() => ({}));
      if (!response.ok || !data.success) {
        throw new Error(data.message || 'No se pudo actualizar el pago.');
      }

      alert(data.message || 'Pago actualizado correctamente.');
      location.reload();
    })
    .catch(error => {
      alert(String(error?.message || 'No se pudo actualizar el pago.'));
    })
    .finally(() => {
      if (saveButton) {
        saveButton.disabled = false;
        saveButton.textContent = 'Guardar cambios';
      }
    });
}

function deletePaymentEntry(paymentId) {
  if (!paymentId) {
    return;
  }

  const reason = window.shopixRequestActionReason('Indica el motivo para eliminar este pago no aprobado.');
  if (!reason) {
    return;
  }

  if (!window.confirm('¿Eliminar este pago? Esta acción no se puede deshacer.')) {
    return;
  }

  fetch(`/api/payment/${paymentId}`, {
    method: 'DELETE',
    headers: {
      'X-CSRF-TOKEN': '{{ csrf_token() }}',
      'Content-Type': 'application/json',
      'Accept': 'application/json',
      'X-Requested-With': 'XMLHttpRequest',
    },
    body: JSON.stringify({ action_reason: reason }),
  })
    .then(async response => {
      const data = await response.json().catch(() => ({}));
      if (!response.ok || !data.success) {
        throw new Error(data.message || 'No se pudo eliminar el pago.');
      }

      alert(data.message || 'Pago eliminado correctamente.');
      location.reload();
    })
    .catch(error => {
      alert(String(error?.message || 'No se pudo eliminar el pago.'));
    });
}

window.updateOrderStatus = updateOrderStatus;
window.updateDeliverStatus = updateDeliverStatus;
window.updatePaymentStatus = updatePaymentStatus;
window.openEditPaymentEntryModal = openEditPaymentEntryModal;
window.savePaymentEntryChanges = savePaymentEntryChanges;
window.deletePaymentEntry = deletePaymentEntry;
window.openCreatePaymentEntryModal = openCreatePaymentEntryModal;
window.saveCreatePaymentEntry = saveCreatePaymentEntry;

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

function formatLinkedAppointmentMoney(value) {
  return Number(value || 0).toLocaleString('es-VE', {
    minimumFractionDigits: 2,
    maximumFractionDigits: 2,
  });
}

function getLinkedAppointmentPaymentContext() {
  const workflowCard = document.getElementById('linked-appointment-workflow');
  const amountInput = document.getElementById('appointment-paid-amount');
  const methodSelect = document.getElementById('appointment-payment-method');
  const referenceInput = document.getElementById('appointment-payment-reference');
  const rateInput = document.getElementById('appointment-payment-rate');
  const usdInput = document.getElementById('appointment-paid-amount-usd');
  const proofInput = document.getElementById('appointment-payment-proof');
  const proofHint = document.getElementById('appointment-payment-proof-hint');
  const summary = document.getElementById('appointment-payment-calc-summary');

  if (!workflowCard || !amountInput || !methodSelect || !rateInput) {
    return null;
  }

  const serviceTotal = Number(workflowCard.dataset.serviceTotal || 0);
  const paidTotal = Number(workflowCard.dataset.paidTotal || 0);
  const amountOriginal = Number(amountInput.value || 0);
  const selectedMethod = methodSelect.selectedOptions?.[0] || null;
  const selectedCurrency = String(selectedMethod?.dataset?.currencyCode || 'USD').toUpperCase();
  const requiresReference = String(selectedMethod?.dataset?.hasReference || '0') === '1';
  const exchangeRate = Number(rateInput.value || 0);

  let amountInUsd = amountOriginal;
  if (selectedCurrency === 'VES') {
    amountInUsd = exchangeRate > 0 ? (amountOriginal / exchangeRate) : 0;
  }

  amountInUsd = Number.isFinite(amountInUsd) ? Math.max(0, amountInUsd) : 0;
  const projectedPaidTotal = paidTotal + amountInUsd;
  const projectedPending = Math.max(0, serviceTotal - projectedPaidTotal);

  if (usdInput) {
    usdInput.value = amountInUsd.toFixed(2);
  }

  if (summary) {
    const currencySuffix = selectedCurrency === 'VES' ? ` (equivalente con tasa ${exchangeRate > 0 ? exchangeRate.toFixed(4) : '0.0000'})` : '';
    summary.textContent = `Abono registrado: ${formatLinkedAppointmentMoney(amountOriginal)} ${selectedCurrency}${currencySuffix}. Pagado acumulado: ${formatLinkedAppointmentMoney(projectedPaidTotal)} USD. Saldo pendiente: ${formatLinkedAppointmentMoney(projectedPending)} USD.`;
  }

  if (proofHint) {
    proofHint.textContent = requiresReference
      ? 'Este método requiere referencia y comprobante.'
      : 'Adjunta un comprobante si aplica para auditoría interna.';
  }

  return {
    workflowCard,
    amountInput,
    methodSelect,
    referenceInput,
    rateInput,
    proofInput,
    amountOriginal,
    amountInUsd,
    selectedCurrency,
    selectedMethod,
    requiresReference,
    exchangeRate,
  };
}

function syncLinkedAppointmentPaymentUi() {
  const context = getLinkedAppointmentPaymentContext();
  if (!context) {
    return;
  }
}

function confirmLinkedAppointmentPayment() {
  const context = getLinkedAppointmentPaymentContext();
  if (!context) {
    return;
  }

  const {
    amountOriginal,
    amountInUsd,
    methodSelect,
    referenceInput,
    proofInput,
    selectedCurrency,
    requiresReference,
    exchangeRate,
  } = context;

  const paymentMethodId = Number(methodSelect?.value || 0);
  const reference = String(referenceInput?.value || '').trim();
  const proofFile = proofInput?.files?.[0] || null;

  if (amountOriginal <= 0) {
    alert('Indica un monto mayor a 0 para confirmar el pago de la cita.');
    return;
  }

  if (amountInUsd <= 0) {
    alert('No se pudo calcular el equivalente en USD. Revisa la tasa del día.');
    return;
  }

  if (requiresReference && !reference) {
    alert('Este método de pago requiere referencia.');
    return;
  }

  if (requiresReference && !proofFile) {
    alert('Este método de pago requiere comprobante.');
    return;
  }

  const payload = new FormData();
  payload.append('action', 'confirm_payment');
  payload.append('paid_amount', amountInUsd.toFixed(2));
  payload.append('paid_amount_mode', 'increment');
  payload.append('payment_amount_original', amountOriginal.toFixed(2));
  payload.append('payment_currency_original', selectedCurrency);
  payload.append('exchange_rate', String(exchangeRate > 0 ? exchangeRate.toFixed(4) : '0'));
  payload.append('payment_currency', 'USD');
  payload.append('create_sale', '0');
  payload.append('require_payment_proof', requiresReference ? '1' : '0');
  payload.append('note', 'Pago confirmado desde detalle de orden.');

  if (paymentMethodId > 0) {
    payload.append('payment_method_id', String(paymentMethodId));
  }

  if (reference) {
    payload.append('payment_reference', reference);
  }

  if (proofFile) {
    payload.append('payment_proof_image', proofFile);
  }

  runLinkedAppointmentWorkflow(payload);
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

  const isFormDataPayload = typeof FormData !== 'undefined' && payload instanceof FormData;
  const headers = {
    'X-CSRF-TOKEN': '{{ csrf_token() }}',
    'Accept': 'application/json',
    'X-Requested-With': 'XMLHttpRequest',
  };

  if (!isFormDataPayload) {
    headers['Content-Type'] = 'application/json';
  }

  fetch(endpoint, {
    method: 'POST',
    headers,
    body: isFormDataPayload ? payload : JSON.stringify(payload),
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

document.addEventListener('DOMContentLoaded', () => {
  document.querySelectorAll('.js-order-status[data-order-id]').forEach((selectElement) => {
    selectElement.addEventListener('change', () => {
      const orderId = Number(selectElement.dataset.orderId || 0);
      if (!orderId) {
        return;
      }

      updateOrderStatus(selectElement, orderId);
    });
  });

  document.querySelectorAll('.js-deliver-status[data-deliver-id]').forEach((selectElement) => {
    selectElement.addEventListener('change', () => {
      const deliverId = Number(selectElement.dataset.deliverId || 0);
      if (!deliverId) {
        return;
      }

      updateDeliverStatus(selectElement, deliverId);
    });
  });

  document.querySelectorAll('.js-payment-status[data-payment-id]').forEach((selectElement) => {
    selectElement.addEventListener('change', () => {
      const paymentId = Number(selectElement.dataset.paymentId || 0);
      if (!paymentId) {
        return;
      }

      updatePaymentStatus(selectElement, paymentId);
    });
  });

  document.querySelectorAll('.js-edit-payment-btn[data-payment-id]').forEach((button) => {
    button.addEventListener('click', () => {
      openEditPaymentEntryModal(button);
    });
  });

  document.querySelectorAll('.js-delete-payment-btn[data-payment-id]').forEach((button) => {
    button.addEventListener('click', () => {
      const paymentId = Number(button.dataset.paymentId || 0);
      if (!paymentId) {
        return;
      }

      deletePaymentEntry(paymentId);
    });
  });

  const savePaymentEntryChangesBtn = document.getElementById('savePaymentEntryChangesBtn');
  if (savePaymentEntryChangesBtn) {
    savePaymentEntryChangesBtn.addEventListener('click', savePaymentEntryChanges);
  }

  const openCreatePaymentEntryModalBtn = document.getElementById('openCreatePaymentEntryModalBtn');
  if (openCreatePaymentEntryModalBtn) {
    openCreatePaymentEntryModalBtn.addEventListener('click', openCreatePaymentEntryModal);
  }

  const saveCreatePaymentEntryBtn = document.getElementById('saveCreatePaymentEntryBtn');
  if (saveCreatePaymentEntryBtn) {
    saveCreatePaymentEntryBtn.addEventListener('click', saveCreatePaymentEntry);
  }

  const createPaymentEntryMethod = document.getElementById('createPaymentEntryMethod');
  if (createPaymentEntryMethod) {
    createPaymentEntryMethod.addEventListener('change', syncCreatePaymentEntryHint);
  }

  const createPaymentEntryCurrency = document.getElementById('createPaymentEntryCurrency');
  if (createPaymentEntryCurrency) {
    createPaymentEntryCurrency.addEventListener('change', () => {
      syncCreatePaymentMethodFilter();
      syncCreatePaymentSummary();
    });
  }

  const createPaymentEntryAmount = document.getElementById('createPaymentEntryAmount');
  if (createPaymentEntryAmount) {
    createPaymentEntryAmount.addEventListener('input', syncCreatePaymentSummary);
    createPaymentEntryAmount.addEventListener('change', syncCreatePaymentSummary);
  }

  const fillRemainingPaymentAmountBtn = document.getElementById('fillRemainingPaymentAmountBtn');
  if (fillRemainingPaymentAmountBtn) {
    fillRemainingPaymentAmountBtn.addEventListener('click', fillCreatePaymentRemainingAmount);
  }

  const appointmentAmountInput = document.getElementById('appointment-paid-amount');
  const appointmentMethodSelect = document.getElementById('appointment-payment-method');
  const appointmentRateInput = document.getElementById('appointment-payment-rate');
  const appointmentReferenceInput = document.getElementById('appointment-payment-reference');

  [appointmentAmountInput, appointmentMethodSelect, appointmentRateInput, appointmentReferenceInput].forEach(element => {
    if (!element) {
      return;
    }

    element.addEventListener('input', syncLinkedAppointmentPaymentUi);
    element.addEventListener('change', syncLinkedAppointmentPaymentUi);
  });

  syncLinkedAppointmentPaymentUi();

  const noteTypeSelect = document.getElementById('adjustmentNoteType');
  const adjustmentModeSelect = document.getElementById('adjustmentMode');
  const adjustmentAmountInput = document.getElementById('adjustmentAmount');
  const taxableBaseInput = document.getElementById('adjustmentTaxableBase');
  const taxRateInput = document.getElementById('adjustmentTaxRate');
  const igtfInput = document.getElementById('adjustmentIgtfAmount');
  const adjustmentReasonInput = document.getElementById('adjustmentReason');
  const adjustmentNoteHelper = document.getElementById('adjustmentNoteHelper');
  const adjustmentNotePreview = document.getElementById('adjustmentNotePreview');

  const retentionTypeSelect = document.getElementById('retentionType');
  const retentionRateInput = document.getElementById('retentionRate');
  const retentionTaxableBaseInput = document.getElementById('retentionTaxableBase');
  const retentionRetainedAmountInput = document.getElementById('retentionRetainedAmount');
  const retentionCertificateInput = document.getElementById('retentionCertificateNumber');
  const retentionBaseHint = document.getElementById('retentionBaseHint');
  const retentionCertificateHint = document.getElementById('retentionCertificateHint');
  const retentionPreview = document.getElementById('retentionPreview');

  const suggestedOrderTaxBase = {{ \Illuminate\Support\Js::from(number_format($orderTaxBase, 2, '.', '')) }};
  const suggestedOrderTaxTotal = {{ \Illuminate\Support\Js::from(number_format($suggestedRetentionIvaBase, 2, '.', '')) }};
  const suggestedVatRate = {{ \Illuminate\Support\Js::from(number_format($suggestedVatRate, 2, '.', '')) }};
  const suggestedIgtfAmount = {{ \Illuminate\Support\Js::from($orderIgtfTotal > 0 ? number_format($orderIgtfTotal, 2, '.', '') : '0.00') }};
  const pendingBalanceAmount = {{ \Illuminate\Support\Js::from(number_format($paymentBalance, 2, '.', '')) }};
  let retentionAmountTouched = Boolean(retentionRetainedAmountInput?.value);

  const toNumber = (value) => {
    const parsed = Number.parseFloat(String(value || '').replace(',', '.'));
    return Number.isFinite(parsed) ? parsed : 0;
  };

  const formatMoney = (value) => {
    return toNumber(value).toLocaleString('es-VE', {
      minimumFractionDigits: 2,
      maximumFractionDigits: 2,
    });
  };

  const syncAdjustmentNoteUi = () => {
    const type = noteTypeSelect?.value || 'credit';
    const mode = adjustmentModeSelect?.value || 'manual';
    const autoCalculatedDebit = type === 'debit' && ['exchange_rate_diff', 'price_error'].includes(mode);
    const taxableBase = toNumber(taxableBaseInput?.value);
    const taxRate = toNumber(taxRateInput?.value);
    const igtfAmount = toNumber(igtfInput?.value);
    const taxAmount = taxableBase > 0 && taxRate > 0 ? (taxableBase * taxRate) / 100 : 0;
    const calculatedAmount = taxableBase + taxAmount + Math.max(0, igtfAmount);

    if (adjustmentModeSelect) {
      adjustmentModeSelect.disabled = type === 'credit';
      if (type === 'credit') {
        adjustmentModeSelect.value = 'manual';
      }
    }

    if (adjustmentAmountInput) {
      adjustmentAmountInput.required = !autoCalculatedDebit;
      adjustmentAmountInput.disabled = autoCalculatedDebit;
      if (autoCalculatedDebit) {
        adjustmentAmountInput.value = calculatedAmount > 0 ? calculatedAmount.toFixed(2) : '';
      }
    }

    if (taxableBaseInput) {
      taxableBaseInput.required = autoCalculatedDebit;
    }

    if (taxRateInput) {
      taxRateInput.required = autoCalculatedDebit;
    }

    if (igtfInput && type !== 'debit') {
      igtfInput.value = '';
    }

    if (adjustmentReasonInput && !adjustmentReasonInput.value.trim()) {
      if (type === 'credit') {
        adjustmentReasonInput.placeholder = 'Ej. Devolución parcial, anulación de renglón, descuento posterior.';
      } else if (mode === 'exchange_rate_diff') {
        adjustmentReasonInput.placeholder = 'Ej. Ajuste por diferencial cambiario de factura emitida.';
      } else if (mode === 'price_error') {
        adjustmentReasonInput.placeholder = 'Ej. Ajuste por error de precio detectado luego de facturar.';
      } else {
        adjustmentReasonInput.placeholder = 'Ej. Ajuste fiscal posterior a la emisión.';
      }
    }

    if (adjustmentNoteHelper) {
      adjustmentNoteHelper.textContent = type === 'credit'
        ? 'La nota de crédito rebaja o revierte una porción de la factura original. Usa el monto exacto que deseas descontar y documenta claramente el motivo.'
        : (autoCalculatedDebit
          ? 'Esta nota de débito aumentará la factura. El monto final se calculará automáticamente con Base + IVA + IGTF.'
          : 'Esta nota de débito aumentará la factura original. Puedes indicar manualmente el monto final o usar una causa estructurada.');
    }

    if (adjustmentNotePreview) {
      const manualAmount = toNumber(adjustmentAmountInput?.value);
      if (autoCalculatedDebit) {
        adjustmentNotePreview.textContent = `Base ${formatMoney(taxableBase)} + IVA ${formatMoney(taxAmount)} + IGTF ${formatMoney(igtfAmount)} = ${formatMoney(calculatedAmount)}.`;
      } else if (manualAmount > 0) {
        adjustmentNotePreview.textContent = `Se enviará una ${type === 'credit' ? 'nota de crédito' : 'nota de débito'} por ${formatMoney(manualAmount)} con motivo fiscal documentado.`;
      } else {
        adjustmentNotePreview.textContent = 'Completa los campos para ver el cálculo estimado antes de emitir la nota.';
      }
    }
  };

  const syncRetentionUi = (forceSuggestedAmount = false) => {
    const type = retentionTypeSelect?.value || 'iva';
    const rate = toNumber(retentionRateInput?.value);
    const base = toNumber(retentionTaxableBaseInput?.value);
    const pendingBalance = toNumber(pendingBalanceAmount);

    if (type === 'iva') {
      if (retentionRateInput && (!retentionRateInput.value || forceSuggestedAmount)) {
        retentionRateInput.value = '75.00';
      }

      if (retentionTaxableBaseInput && (!retentionTaxableBaseInput.value || forceSuggestedAmount)) {
        retentionTaxableBaseInput.value = suggestedOrderTaxTotal;
      }

      if (retentionCertificateInput) {
        retentionCertificateInput.maxLength = 14;
        retentionCertificateInput.setAttribute('pattern', '\\d{14}');
        retentionCertificateInput.setAttribute('placeholder', 'YYYYMM########');
      }

      if (retentionBaseHint) {
        retentionBaseHint.textContent = `Para IVA se sugiere usar el IVA facturado: ${formatMoney(suggestedOrderTaxTotal)}.`;
      }

      if (retentionCertificateHint) {
        retentionCertificateHint.textContent = 'Para IVA usa formato SENIAT: YYYYMM + 8 digitos.';
      }
    } else {
      if (retentionTaxableBaseInput && (!retentionTaxableBaseInput.value || forceSuggestedAmount)) {
        retentionTaxableBaseInput.value = suggestedOrderTaxBase;
      }

      if (retentionBaseHint) {
        retentionBaseHint.textContent = 'Para ISLR, municipal u otras retenciones, revisa la base y tasa según el comprobante entregado por el cliente.';
      }

      if (retentionCertificateHint) {
        retentionCertificateHint.textContent = 'El comprobante es recomendado para auditoría interna y obligatorio si tu proceso fiscal así lo exige.';
      }
    }

    const effectiveRate = toNumber(retentionRateInput?.value);
    const effectiveBase = toNumber(retentionTaxableBaseInput?.value);
    const calculatedRetention = effectiveBase > 0 && effectiveRate > 0 ? (effectiveBase * effectiveRate) / 100 : 0;

    if (retentionRetainedAmountInput && (forceSuggestedAmount || !retentionAmountTouched || !retentionRetainedAmountInput.value)) {
      retentionRetainedAmountInput.value = calculatedRetention > 0 ? calculatedRetention.toFixed(2) : '';
    }

    const effectiveRetention = toNumber(retentionRetainedAmountInput?.value);
    const projectedBalance = Math.max(0, pendingBalance - effectiveRetention);

    if (retentionPreview) {
      retentionPreview.textContent = effectiveRetention > 0
        ? `Se registrará una retención por ${formatMoney(effectiveRetention)}. El saldo pendiente estimado bajará de ${formatMoney(pendingBalance)} a ${formatMoney(projectedBalance)}.`
        : 'La retención registrada reducirá el saldo pendiente de esta orden cuando sea guardada.';
    }
  };

  noteTypeSelect?.addEventListener('change', syncAdjustmentNoteUi);
  adjustmentModeSelect?.addEventListener('change', syncAdjustmentNoteUi);
  [adjustmentAmountInput, taxableBaseInput, taxRateInput, igtfInput].forEach(element => {
    element?.addEventListener('input', syncAdjustmentNoteUi);
    element?.addEventListener('change', syncAdjustmentNoteUi);
  });

  retentionRetainedAmountInput?.addEventListener('input', () => {
    retentionAmountTouched = true;
    syncRetentionUi(false);
  });

  [retentionTypeSelect, retentionRateInput, retentionTaxableBaseInput, retentionCertificateInput].forEach(element => {
    element?.addEventListener('input', () => syncRetentionUi(false));
    element?.addEventListener('change', () => syncRetentionUi(element === retentionTypeSelect));
  });

  syncAdjustmentNoteUi();
  syncRetentionUi(false);
});

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
      const dispositionInput = document.querySelector(`.return-disposition[data-id="${id}"]`);
      const disposition = dispositionInput?.value || 'resalable';

      if (quantity > 0 && quantity <= maxQuantity) {
        items.push({ id, quantity, disposition });
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

  if (!currencySelect || !deliveryBtn) {
    return;
  }

  const syncDownloadUrls = () => {
    const currencyCode = encodeURIComponent(currencySelect.value || '{{ $orderCurrencyCode ?? 'USD' }}');
    const invoiceBase = invoiceBtn ? (invoiceBtn.dataset.baseUrl || invoiceBtn.getAttribute('href') || '') : '';
    const deliveryBase = deliveryBtn.dataset.baseUrl || deliveryBtn.getAttribute('href') || '';

    if (invoiceBtn && invoiceBase !== '') {
      invoiceBtn.href = `${invoiceBase}?currency_code=${currencyCode}&disposition=inline`;
    }
    deliveryBtn.href = `${deliveryBase}?disposition=inline`;
  };

  currencySelect.addEventListener('change', syncDownloadUrls);
  syncDownloadUrls();
})();
</script>
@endpush