<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
  <link rel="icon" type="image/png" href="{{ asset('assets/img/favicon.png') }}">
  <title>Orden de Venta</title>
  <link href="{{ asset('assets/css/nucleo-icons.css') }}" rel="stylesheet">
  <link href="{{ asset('assets/css/material-dashboard.css?v=3.2.0') }}" rel="stylesheet">
  <meta name="csrf-token" content="{{ csrf_token() }}">
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
        $storeWhatsappUrl = $storePhone !== ''
            ? 'https://wa.me/' . $storePhone . '?text=' . rawurlencode('Hola ' . ($order->tenant->name ?? 'tienda') . ', sobre la orden #' . $order->id . '.')
            : null;
        $customerWhatsappUrl = $customerPhone !== ''
            ? 'https://wa.me/' . $customerPhone . '?text=' . rawurlencode('Hola ' . ($order->user->name ?? 'cliente') . ', sobre la orden #' . $order->id . '.')
            : null;
    @endphp
    <div class="container-fluid">
        <div class="d-block d-md-none text-center">
        </div>
        <div class="d-flex flex-wrap justify-content-between align-items-center">
            <h1 class="text-center">Orden Nro {{ $order->id }}</h1>
        </div>
        
        <p><strong>Cliente:</strong> {{ $order->user->name }} | <strong>Teléfono:</strong> {{ $order->user->phone_number ?? 'No registrado' }}</p>
        <p><strong>Moneda de la venta:</strong> {{ $orderCurrencyCode ?? 'USD' }}</p>
        <p><strong>Entrega:</strong> {{ $order->preference }} | <strong>Dirección:</strong> {{ $order->address }}</p>
        
        <div class="d-flex flex-wrap align-items-center gap-2">
            <strong>Entregado:</strong>
            <span class="btn btn-sm {{ $order->status == 0 ? 'btn-outline-warning' : ($order->status == 1 ? 'btn-outline-success' : 'btn-outline-danger') }}">
                {{ $order->status == 0 ? 'Pendiente' : ($order->status == 1 ? 'Entregado' : ($order->status == 2 ? 'Cancelado' : 'Devolución')) }}
            </span>
        </div>
        
        <div class="d-flex flex-wrap gap-2">
            <p><strong>Fecha:</strong> {{ $order->date }}</p>
            <div class="d-flex align-items-center gap-2">
                <strong>Estado:</strong>
                <span class="btn btn-sm {{ $order->status == 0 ? 'btn-outline-warning' : ($order->status == 1 ? 'btn-outline-success' : 'btn-outline-danger') }}">
                    {{ $order->status == 0 ? 'En Proceso' : ($order->status == 1 ? 'Aprobado' : 'Negado') }}
                </span>
            </div>
        </div>

        <div class="d-flex flex-wrap gap-2 mt-3">
            <button type="button" id="public-order-back" class="btn btn-outline-secondary mb-0">Volver</button>
            <div class="d-flex align-items-center gap-2">
                <label for="public-order-download-currency" class="mb-0 text-sm fw-semibold">Moneda de emisión</label>
                <select id="public-order-download-currency" class="form-select form-select-sm border border-1 p-2" style="min-width: 170px;">
                    <option value="{{ $orderCurrencyCode ?? 'USD' }}">{{ $orderCurrencyCode ?? 'USD' }} (moneda de la venta)</option>
                    @if(($orderCurrencyCode ?? 'USD') !== 'VES')
                        <option value="VES">VES / Bolívares</option>
                    @endif
                </select>
            </div>
            <a id="publicDownloadInvoiceBtn" data-base-url="{{ route('public.order.pdf', ['id' => $order->id, 'type' => 'invoice']) }}" href="{{ route('public.order.pdf', ['id' => $order->id, 'type' => 'invoice']) }}?currency_code={{ $orderCurrencyCode ?? 'USD' }}" class="btn btn-dark mb-0">Descargar factura PDF</a>
            <a id="publicDownloadDeliveryBtn" data-base-url="{{ route('public.order.pdf', ['id' => $order->id, 'type' => 'delivery']) }}" href="{{ route('public.order.pdf', ['id' => $order->id, 'type' => 'delivery']) }}?currency_code={{ $orderCurrencyCode ?? 'USD' }}" class="btn btn-outline-dark mb-0">Descargar nota de entrega</a>
            @if($storeWhatsappUrl)
                <a href="{{ $storeWhatsappUrl }}" target="_blank" rel="noopener" class="btn btn-outline-success mb-0">WhatsApp tienda</a>
            @endif
            @if($customerWhatsappUrl)
                <a href="{{ $customerWhatsappUrl }}" target="_blank" rel="noopener" class="btn btn-success mb-0">WhatsApp cliente</a>
            @endif
        </div>
        
        <!-- Tabla de Detalles de la Orden -->
        <div class="card mt-4">
            <div class="card-header">
                <h6 class="mb-0">Productos en la Orden</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Producto</th>
                                <th>Cantidad</th>
                                <th>Variante</th>
                                <th>Precio Unitario</th>
                                <th>Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($order->details as $detalle)
                            <tr>
                                <td>{{ $detalle->variant->product->name ?? 'Sin nombre' }}</td>
                                <td>{{ $detalle->quantity }}</td>
                                <td>{{ $detalle->variant->size ?? '' }}</td>
                                <td>{{ $orderCurrencySymbol ?? '$' }}{{ number_format($detalle->price, 2) }}</td>
                                <td>{{ $orderCurrencySymbol ?? '$' }}{{ number_format($detalle->amount, 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p><strong>Total Orden:</strong> {{ $orderCurrencySymbol ?? '$' }}{{ number_format($totalOrden, 2) }}</p>
            </div>
        </div>
        
        <!-- Tabla de Pagos -->
        <div class="card mt-4">
            <div class="card-header">
                <h6 class="mb-0">Pagos Registrados</h6>
            </div>
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table">
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
                                <td>{{ $payment->currency }}</td>
                                <td>{{ $payment->payment->name}}</td>
                                <td>{{ $paymentSymbol }}{{ number_format($payment->amount, 2) }}{{ $paymentSymbol === '' && $paymentCurrencyCode !== '' ? ' ' . $paymentCurrencyCode : '' }}</td>
                                <td>{{ $payment->payment->admin_name }}</td>
                                <td>{{ $payment->payment->bank }}</td>
                                <td>{{ $payment->reference ?? 'N/A' }}</td>
                                                                <td>
                                                                        @if($payment->images->isNotEmpty())
                                                                            <a href="{{ \App\Support\ImageStorage::url($payment->images->first()->image_path) ?? '#' }}" target="_blank" class="btn btn-sm btn-outline-dark mb-0">Ver imagen</a>
                                                                        @else
                                                                            <span class="text-muted">Sin imagen</span>
                                                                        @endif
                                                                </td>
                                <td>
                                    <span class="btn btn-sm {{ $payment->status == 0 ? 'btn-outline-warning' : ($payment->status == 1 ? 'btn-outline-success' : 'btn-outline-danger') }}">
                                        {{ $payment->status == 0 ? 'En Proceso' : ($payment->status == 1 ? 'Pagado' : 'Cancelado') }}
                                    </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <p><strong>Total Pagado:</strong> {{ $orderCurrencySymbol ?? '$' }}{{ number_format($totalPagado, 2) }}</p>
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
                const currencySelect = document.getElementById('public-order-download-currency');
                const invoiceBtn = document.getElementById('publicDownloadInvoiceBtn');
                const deliveryBtn = document.getElementById('publicDownloadDeliveryBtn');

                if (!currencySelect || !invoiceBtn || !deliveryBtn) {
                    return;
                }

                const syncDownloadUrls = () => {
                    const currencyCode = encodeURIComponent(currencySelect.value || '{{ $orderCurrencyCode ?? 'USD' }}');
                    const invoiceBase = invoiceBtn.dataset.baseUrl || invoiceBtn.getAttribute('href') || '';
                    const deliveryBase = deliveryBtn.dataset.baseUrl || deliveryBtn.getAttribute('href') || '';

                    invoiceBtn.href = `${invoiceBase}?currency_code=${currencyCode}`;
                    deliveryBtn.href = `${deliveryBase}?currency_code=${currencyCode}`;
                };

                currencySelect.addEventListener('change', syncDownloadUrls);
                syncDownloadUrls();
            })();
        </script>
</body>

</html>
