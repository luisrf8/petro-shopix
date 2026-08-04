<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <link rel="icon" type="image/png" href="{{ route('pwa.icon', ['size' => 192, 'variant' => 'client']) }}">
  <title>Orden de Venta</title>
  <link href="{{ asset('assets/css/nucleo-icons.css') }}" rel="stylesheet">
  <link href="{{ asset('assets/css/material-dashboard.css?v=3.2.0') }}" rel="stylesheet">
  <meta name="csrf-token" content="{{ csrf_token() }}">
  <style>
    :root {
        --order-bg: linear-gradient(180deg, #f8fafc 0%, #e2e8f0 100%);
        --order-card: rgba(255, 255, 255, 0.88);
        --order-border: rgba(148, 163, 184, 0.22);
        --order-text: #0f172a;
        --order-muted: #475569;
        --order-shadow: 0 24px 70px -44px rgba(15, 23, 42, 0.5);
    }

    body {
        min-height: 100vh;
        background: var(--order-bg);
        color: var(--order-text);
    }

    .order-shell {
        max-width: 1240px;
        margin: 0 auto;
    }

    .glass-card {
        border: 1px solid var(--order-border);
        background: var(--order-card);
        backdrop-filter: blur(18px);
        border-radius: 28px;
        box-shadow: var(--order-shadow);
    }

    .hero-panel {
        padding: 1.5rem;
        position: relative;
        overflow: hidden;
        background: radial-gradient(circle at top right, rgba(59, 130, 246, 0.18), transparent 28%), rgba(255, 255, 255, 0.92);
    }

    .hero-panel::after {
        content: '';
        position: absolute;
        inset: auto -60px -80px auto;
        width: 220px;
        height: 220px;
        border-radius: 999px;
        background: radial-gradient(circle, rgba(14, 165, 233, 0.18), transparent 68%);
    }

    .eyebrow {
        letter-spacing: 0.16em;
        text-transform: uppercase;
        font-size: 0.72rem;
        font-weight: 700;
        color: #1d4ed8;
        margin-bottom: 0.6rem;
    }

    .hero-title {
        font-size: clamp(1.8rem, 4vw, 3rem);
        line-height: 1;
        font-weight: 800;
        margin-bottom: 0.75rem;
    }

    .hero-subtitle,
    .meta-copy {
        color: var(--order-muted);
    }

    .status-pill {
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

    .metric-card {
        padding: 1rem 1.1rem;
        height: 100%;
    }

    .metric-label {
        font-size: 0.78rem;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #64748b;
        font-weight: 700;
    }

    .metric-value {
        font-size: 1.35rem;
        font-weight: 800;
        margin-top: 0.35rem;
    }

    .timeline-card,
    .section-card {
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
        color: var(--order-muted);
        margin-bottom: 0.2rem;
    }

    .soft-table thead th {
        border-bottom: 1px solid #e2e8f0;
        color: #64748b;
        font-size: 0.76rem;
        text-transform: uppercase;
        letter-spacing: 0.08em;
    }

    .soft-table tbody td {
        border-bottom: 1px solid #eef2f7;
        vertical-align: middle;
    }

    .soft-table tbody tr:last-child td {
        border-bottom: none;
    }

    .quick-actions {
        display: flex;
        flex-wrap: wrap;
        gap: 0.75rem;
    }

    @media (max-width: 768px) {
        body {
            padding: 1rem !important;
        }

        .hero-panel,
        .timeline-card,
        .section-card,
        .metric-card {
            border-radius: 22px;
        }
    }
  </style>
</head>

<body class="g-sidenav-show bg-gray-100 p-4">
    @php
        $storePhone = preg_replace('/\D+/', '', (string) (($order->tenant->phone_code ?? '') . ($order->tenant->phone_number ?? '')));
        $customerPhone = preg_replace('/\D+/', '', (string) ($order->user->phone_number ?? ''));
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
        $normalizeCurrencyCode = function (?string $code) {
            $normalized = strtoupper(trim((string) $code));

            if (in_array($normalized, ['BS', 'VES'], true)) {
                return 'VES';
            }

            return $normalized;
        };
        $currencyToBsRate = function (?string $code) use ($normalizeCurrencyCode, $dollarRateToBs, $euroRateToBs) {
            $normalized = $normalizeCurrencyCode($code);

            if ($normalized === 'VES') {
                return 1.0;
            }

            if ($normalized === 'USD') {
                return (float) $dollarRateToBs;
            }

            if ($normalized === 'EUR') {
                return (float) $euroRateToBs;
            }

            return 0.0;
        };
        $formatEquivalentUsdBs = function (float $amount, ?string $amountCurrencyCode) use ($normalizeCurrencyCode, $currencyToBsRate, $dollarRateToBs) {
            $normalized = $normalizeCurrencyCode($amountCurrencyCode);
            $rateToBs = $currencyToBsRate($normalized);

            if ($rateToBs <= 0) {
                return null;
            }

            $amountBs = $amount * $rateToBs;
            $parts = ['Bs ' . number_format($amountBs, 2)];

            if ((float) $dollarRateToBs > 0) {
                $parts[] = '$' . number_format($amountBs / (float) $dollarRateToBs, 2);
            }

            return implode(' | ', $parts);
        };
        $storeWhatsappUrl = $storePhone !== ''
            ? 'https://wa.me/' . $storePhone . '?text=' . rawurlencode('Hola ' . ($order->tenant->name ?? 'sede') . ', sobre la orden #' . $order->id . '.')
            : null;
        $customerWhatsappUrl = $customerPhone !== ''
            ? 'https://wa.me/' . $customerPhone . '?text=' . rawurlencode('Hola ' . ($order->user->name ?? 'cliente') . ', sobre la orden #' . $order->id . '.')
            : null;
        $deliveryTypeLabel = strtolower(trim((string) ($order->preference ?? '')));
        $isStoreDelivery = str_contains($deliveryTypeLabel, 'delivery');
        $isExternalShipping = str_contains($deliveryTypeLabel, 'env') || str_contains($deliveryTypeLabel, 'shipping');
        $deliveryLabel = (int) $order->deliver_status === 0
            ? 'Pendiente'
            : ((int) $order->deliver_status === 3
                ? ($isExternalShipping ? 'En vía' : 'En despacho')
            : ((int) $order->deliver_status === 1
                ? 'Entregado'
                : ((int) $order->deliver_status === 2 ? 'Cancelado' : 'En proceso')));
        $approvalLabel = $order->status == 0 ? 'En proceso' : ($order->status == 1 ? 'Aprobado' : 'Negado');
        $paymentBalance = max(0, (float) $totalOrden - (float) $totalPagado);
        $paymentStepTone = $totalPagado >= $totalOrden && $totalOrden > 0 ? 'success' : ($totalPagado > 0 ? 'pending' : 'danger');
        $deliveryFeeAmount = round((float) ($order->delivery_fee ?? 0), 2);
        $deliveryFeeEquivalent = $formatEquivalentUsdBs($deliveryFeeAmount, $orderCurrencyCode ?? 'USD');
        $invoiceDocument = $order->latest_electronic_document;
        $assignedDeliveryName = trim((string) ($order->assignedDeliveryUser->name ?? ''));
        $assignedDeliveryPhone = trim((string) ($order->assignedDeliveryUser->phone_number ?? ''));
        $deliveryContactMeta = $assignedDeliveryName !== '' || $assignedDeliveryPhone !== ''
            ? 'Repartidor: ' . ($assignedDeliveryName !== '' ? $assignedDeliveryName : 'No identificado') . ' | Teléfono: ' . ($assignedDeliveryPhone !== '' ? $assignedDeliveryPhone : 'No registrado')
            : 'Aún no hay un repartidor asignado para esta orden.';
        $shippingProgressDescription = match ((int) $order->deliver_status) {
            1 => 'Paso 3 de 3: el envío figura como entregado correctamente.',
            3 => 'Paso 2 de 3: el pedido está actualmente en ruta para su entrega.',
            2 => 'El envío fue cancelado antes de completar su despacho.',
            default => 'Paso 1 de 3: tu envío fue registrado y está en preparación para despacho.',
        };
        $shippingProgressMeta = match ((int) $order->deliver_status) {
            1 => 'Proceso: 1. Preparación  2. Despacho  3. Entregado',
            3 => 'Proceso: 1. Preparación  2. En despacho / En vía  3. Entregado',
            2 => 'Proceso interrumpido: 1. Preparación  2. Cancelado',
            default => 'Proceso: 1. Preparación  2. En despacho / En vía  3. Entrega final',
        };
        $timelineSteps = [
            [
                'tone' => 'success',
                'title' => 'Orden creada',
                'description' => 'La orden fue registrada el ' . ($order->date ?: 'sin fecha disponible') . '.',
                'meta' => 'Entrega: ' . ($order->preference ?: 'Sin preferencia definida') . ' | Costo delivery: ' . (($orderCurrencySymbol ?? '$') . number_format($deliveryFeeAmount, 2)),
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
                'tone' => (int) $order->deliver_status === 1 ? 'success' : ((int) $order->deliver_status === 2 ? 'danger' : 'pending'),
                'title' => $isExternalShipping ? 'Seguimiento del envío' : 'Entrega y despacho',
                'description' => $isExternalShipping
                    ? $shippingProgressDescription
                    : 'Estado actual del delivery: ' . $deliveryLabel . '.',
                'meta' => $isExternalShipping
                    ? $shippingProgressMeta . ' | Dirección: ' . ($order->address ?: 'No registrada')
                    : $deliveryContactMeta,
            ],
            [
                'tone' => $order->has_annulled_invoice ? 'danger' : ($invoiceDocument ? 'success' : 'pending'),
                'title' => 'Factura digital',
                'description' => $order->has_annulled_invoice
                    ? 'La última factura electrónica fue anulada y no debe reutilizarse.'
                    : ($invoiceDocument
                        ? 'Factura electrónica emitida: ' . ($invoiceDocument->numero_documento ?: 'sin correlativo visible') . '.'
                        : 'La factura electrónica todavía no ha sido emitida.'),
                'meta' => $invoiceDocument?->created_at ? 'Actualizada ' . $invoiceDocument->created_at->format('d/m/Y H:i') : 'Sin actualización registrada',
            ],
        ];

        if ($order->has_returns) {
            $timelineSteps[] = [
                'tone' => 'danger',
                'title' => 'Devoluciones registradas',
                'description' => 'Esta orden tiene devoluciones por ' . (($orderCurrencySymbol ?? '$') . number_format($order->total_devuelto ?? 0, 2)) . '.',
                'meta' => 'Revisa el detalle inferior para validar cantidades devueltas.',
            ];
        }
    @endphp
    <div class="order-shell">
        <div class="glass-card hero-panel mb-4">
            <div class="row g-4 align-items-start position-relative" style="z-index:1;">
                <div class="col-lg-7">
                    <div class="eyebrow">Seguimiento inteligente de tu orden</div>
                    <div class="hero-title">Orden #{{ $order->id }}</div>
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <span class="status-pill">{{ $approvalLabel }}</span>
                        <span class="status-pill">{{ $deliveryLabel }}</span>
                        <span class="status-pill">{{ $orderCurrencyCode ?? 'USD' }}</span>
                        @if($order->has_annulled_invoice)
                            <span class="status-pill" style="background:#fee2e2; color:#991b1b;">Factura anulada</span>
                        @endif
                    </div>
                    <div class="meta-copy">
                        {{ $order->user->name }} · {{ $order->user->phone_number ?? 'Sin teléfono' }}<br>
                        {{ $order->preference ?: 'Entrega no definida' }} · {{ $order->address ?: 'Sin dirección registrada' }}
                        @if($isStoreDelivery)
                            <br>{{ $deliveryContactMeta }}
                        @elseif($isExternalShipping)
                            <br>{{ $shippingProgressMeta }}
                        @endif
                    </div>
                </div>
                <div class="col-lg-5">
                    <div class="quick-actions justify-content-lg-end">
                        <button type="button" id="public-order-back" class="btn btn-outline-secondary mb-0">Volver</button>
                        @if((bool) ($order->tenant->electronic_invoicing_enabled ?? false))
                            <a id="publicDownloadInvoiceBtn" href="{{ route('public.order.pdf', ['id' => $order->id, 'type' => 'invoice']) }}" class="btn btn-dark mb-0" target="_blank" rel="noopener">Factura PDF</a>
                        @else
                            <button type="button" class="btn btn-dark mb-0" disabled title="La facturación digital no está activa en esta sede">Factura PDF</button>
                        @endif
                        <a id="publicDownloadDeliveryBtn" href="{{ route('public.order.pdf', ['id' => $order->id, 'type' => 'delivery']) }}" class="btn btn-outline-dark mb-0" target="_blank" rel="noopener">Orden de entrega</a>
                        @if($storeWhatsappUrl)
                            <a href="{{ $storeWhatsappUrl }}" target="_blank" rel="noopener" class="btn btn-outline-success mb-0">WhatsApp sede</a>
                        @endif
                        @if($customerWhatsappUrl)
                            <a id="public-order-customer-whatsapp" href="{{ $customerWhatsappUrl }}" target="_blank" rel="noopener" class="btn btn-success mb-0" data-order-user-id="{{ $order->user->id }}">WhatsApp cliente</a>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-md-3">
                <div class="glass-card metric-card">
                    <div class="metric-label">Total orden</div>
                    <div class="metric-value">{{ $orderCurrencySymbol ?? '$' }}{{ number_format($totalOrden, 2) }}</div>
                    @if(($totalEquivalent = $formatEquivalentUsdBs((float) $totalOrden, $orderCurrencyCode ?? 'USD')))
                        <small class="text-muted d-block mt-1">{{ $totalEquivalent }}</small>
                    @endif
                </div>
            </div>
            <div class="col-md-3">
                <div class="glass-card metric-card">
                    <div class="metric-label">Total pagado</div>
                    <div class="metric-value">{{ $orderCurrencySymbol ?? '$' }}{{ number_format($totalPagado, 2) }}</div>
                    @if(($paidEquivalent = $formatEquivalentUsdBs((float) $totalPagado, $orderCurrencyCode ?? 'USD')))
                        <small class="text-muted d-block mt-1">{{ $paidEquivalent }}</small>
                    @endif
                </div>
            </div>
            <div class="col-md-3">
                <div class="glass-card metric-card">
                    <div class="metric-label">Saldo pendiente</div>
                    <div class="metric-value">{{ $orderCurrencySymbol ?? '$' }}{{ number_format($paymentBalance, 2) }}</div>
                    @if(($balanceEquivalent = $formatEquivalentUsdBs((float) $paymentBalance, $orderCurrencyCode ?? 'USD')))
                        <small class="text-muted d-block mt-1">{{ $balanceEquivalent }}</small>
                    @endif
                </div>
            </div>
            <div class="col-md-3">
                <div class="glass-card metric-card">
                    <div class="metric-label">Costo delivery</div>
                    <div class="metric-value" style="font-size:1rem;">{{ $orderCurrencySymbol ?? '$' }}{{ number_format($deliveryFeeAmount, 2) }}</div>
                    @if($deliveryFeeEquivalent)
                        <small class="text-muted d-block mt-1">{{ $deliveryFeeEquivalent }}</small>
                    @endif
                </div>
            </div>
        </div>

        <div class="row g-4">
            <div class="col-lg-5">
                <div class="glass-card timeline-card h-100">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h5 class="mb-1">Estado de la orden</h5>
                            <p class="meta-copy mb-0">Una lectura rápida del avance real de tu compra.</p>
                        </div>
                    </div>

                    <div class="order-timeline-steps">
                        @foreach($timelineSteps as $step)
                            <div class="order-timeline-step {{ $step['tone'] }}">
                                <div class="order-timeline-title">{{ $step['title'] }}</div>
                                <div class="order-timeline-description">{{ $step['description'] }}</div>
                                <small class="text-muted d-block">{{ $step['meta'] }}</small>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <div class="col-lg-7">
                <div class="glass-card section-card mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h5 class="mb-1">Productos de la orden</h5>
                            <p class="meta-copy mb-0">Detalle de artículos, cantidades y subtotales.</p>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table soft-table mb-0">
                            <thead>
                                <tr>
                                    <th>Producto</th>
                                    <th>Cantidad</th>
                                    <th>Variante</th>
                                    <th>Precio</th>
                                    <th>Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($order->details as $detalle)
                                    @php
                                        $detailProductName = $detalle->custom_product_name ?? ($detalle->variant->product->display_name ?? 'Sin nombre');
                                        $detailVariantLabel = $detalle->custom_variant_code ?? ($detalle->variant->size ?? 'General');
                                    @endphp
                                    <tr>
                                        <td>{{ $detailProductName }}</td>
                                        <td>{{ $detalle->quantity }}</td>
                                        <td>{{ $detailVariantLabel }}</td>
                                        <td>{{ $orderCurrencySymbol ?? '$' }}{{ number_format($detalle->price, 2) }}</td>
                                        <td>{{ $orderCurrencySymbol ?? '$' }}{{ number_format($detalle->amount, 2) }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <div class="glass-card section-card mb-4">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h5 class="mb-1">Pagos registrados</h5>
                            <p class="meta-copy mb-0">Comprobantes, método y estado de conciliación.</p>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="table soft-table mb-0">
                            <thead>
                                <tr>
                                    <th>Moneda</th>
                                    <th>Método</th>
                                    <th>Monto</th>
                                    <th>Beneficiario</th>
                                    <th>Referencia</th>
                                    <th>Comprobante</th>
                                    <th>Estado</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($order->payments as $payment)
                                    @php
                                        $paymentCurrencyCode = strtoupper(trim((string) ($payment->currency ?? '')));
                                        if (in_array($paymentCurrencyCode, ['BS', 'VES'], true)) {
                                            $paymentCurrencyCode = 'VES';
                                        }
                                        $paymentSymbol = $resolveCurrencySymbol($paymentCurrencyCode);
                                        $paymentEquivalent = $formatEquivalentUsdBs((float) $payment->amount, $paymentCurrencyCode);
                                    @endphp
                                    <tr>
                                        <td>{{ $payment->currency }}</td>
                                        <td>
                                            {{ $payment->payment->name }}<br>
                                            <small class="text-muted">{{ $payment->payment->bank }}</small>
                                        </td>
                                        <td>
                                            {{ $paymentSymbol }}{{ number_format($payment->amount, 2) }}{{ $paymentSymbol === '' && $paymentCurrencyCode !== '' ? ' ' . $paymentCurrencyCode : '' }}
                                            @if($paymentEquivalent)
                                                <small class="text-muted d-block">Equivalente: {{ $paymentEquivalent }}</small>
                                            @endif
                                        </td>
                                        <td>{{ $payment->payment->admin_name }}</td>
                                        <td>{{ $payment->reference ?? 'N/A' }}</td>
                                        <td>
                                            @if($payment->images->isNotEmpty())
                                                <a href="{{ \App\Support\ImageStorage::url($payment->images->first()->image_path) ?? '#' }}" target="_blank" class="btn btn-sm btn-outline-dark mb-0">Ver imagen</a>
                                            @else
                                                <span class="text-muted">Sin imagen</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="status-pill" style="background:{{ $payment->status == 1 ? '#dcfce7' : ($payment->status == 0 ? '#fef3c7' : '#fee2e2') }}; color:{{ $payment->status == 1 ? '#166534' : ($payment->status == 0 ? '#92400e' : '#991b1b') }};">
                                                {{ $payment->status == 0 ? 'En proceso' : ($payment->status == 1 ? 'Pagado' : 'Cancelado') }}
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">Aún no hay pagos registrados para esta orden.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>

                @if($order->has_returns)
                    <div class="glass-card section-card">
                        <div class="d-flex justify-content-between align-items-center mb-3">
                            <div>
                                <h5 class="mb-1">Devoluciones</h5>
                                <p class="meta-copy mb-0">Monto acumulado devuelto: {{ $orderCurrencySymbol ?? '$' }}{{ number_format($order->total_devuelto ?? 0, 2) }}</p>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table soft-table mb-0">
                                <thead>
                                    <tr>
                                        <th>Fecha</th>
                                        <th>Motivo</th>
                                        <th>Items</th>
                                        <th>Total</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($order->returns as $return)
                                        <tr>
                                            <td>{{ optional($return->created_at)->format('d/m/Y H:i') ?? 'Sin fecha' }}</td>
                                            <td>{{ $return->return_reason ?? 'Sin motivo registrado' }}</td>
                                            <td>{{ $return->items->sum('quantity') }}</td>
                                            <td>{{ $orderCurrencySymbol ?? '$' }}{{ number_format($return->total_refund ?? 0, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Core JS Files -->
    <script src="{{ asset('assets/js/core/popper.min.js') }}"></script>
    <script src="{{ asset('assets/js/core/bootstrap.min.js') }}"></script>
    <script async defer src="https://buttons.github.io/buttons.js"></script>
        <script>
            document.getElementById('public-order-back')?.addEventListener('click', function () {
                if (window.history.length > 1) {
                    window.history.back();
                    return;
                }

                window.location.href = '/';
            });

            (() => {
                const customerWhatsappBtn = document.getElementById('public-order-customer-whatsapp');

                if (!customerWhatsappBtn) {
                    return;
                }

                try {
                    const rawUser = localStorage.getItem('shopix_ecomm_user');
                    const customerUser = rawUser ? JSON.parse(rawUser) : null;
                    const orderUserId = String(customerWhatsappBtn.dataset.orderUserId || '');
                    const currentUserId = String(customerUser?.id ?? '');

                    if (orderUserId !== '' && currentUserId !== '' && orderUserId === currentUserId) {
                        customerWhatsappBtn.classList.add('d-none');
                    }
                } catch (error) {
                    console.warn('No se pudo resolver el usuario autenticado del storefront.', error);
                }
            })();
        </script>
</body>

</html>
