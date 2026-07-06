<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Orden de Venta</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            position: relative;
        }

        .non-fiscal-watermark {
            position: fixed;
            top: 38%;
            left: 8%;
            right: 8%;
            transform: rotate(-28deg);
            font-size: 34px;
            font-weight: 800;
            text-align: center;
            color: rgba(160, 0, 0, 0.18);
            letter-spacing: 2px;
            z-index: 0;
            pointer-events: none;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th, td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }

        th {
            background-color: #f2f2f2;
        }

        .order-header {
            margin-bottom: 10px;
        }

        .order-header-top {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 6px;
        }

        .order-header-top td {
            border: none;
            padding: 0;
            vertical-align: top;
        }

        .order-header-left {
            width: 64%;
            padding-right: 8px;
        }

        .order-header-right {
            width: 36%;
            text-align: right;
        }

        .order-title {
            margin: 0 0 6px;
            font-size: 28px;
            font-weight: 800;
        }

        .order-company-name {
            margin: 0 0 4px;
            font-size: 18px;
            font-weight: 700;
        }

        .order-company-info {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .order-company-info td {
            border: none;
            padding: 2px 0;
            width: 50%;
            vertical-align: top;
            font-size: 13px;
        }

        .order-company-info .label {
            font-weight: 700;
        }

        .order-logo-line {
            font-size: 12px;
            font-weight: 700;
            margin: 0 0 6px;
        }

        .order-logo-box {
            display: inline-block;
            width: 150px;
            text-align: right;
        }

        .order-logo-box img {
            display: block;
            margin-left: auto;
            margin-right: 0;
            max-width: 150px;
            max-height: 70px;
        }
    </style>
</head>
<body>
<div class="non-fiscal-watermark">DOCUMENTO INTERNO - NO FISCAL - SIN VALIDEZ TRIBUTARIA</div>
@php
    $orderCurrencyCode = strtoupper(trim((string) ($orderCurrencyCode ?? $order->sale_currency_code ?? ($tienda->base_currency ?? 'USD'))));
    $orderCurrencyCode = in_array($orderCurrencyCode, ['USD', 'EUR', 'VES'], true) ? $orderCurrencyCode : 'USD';
    $emissionCurrencyCode = strtoupper(trim((string) ($emissionCurrencyCode ?? $orderCurrencyCode)));
    $emissionCurrencyCode = in_array($emissionCurrencyCode, ['USD', 'EUR', 'VES'], true) ? $emissionCurrencyCode : $orderCurrencyCode;
    $itemsSubtotal = (float) ($itemsSubtotal ?? $order->items_subtotal);
    $deliveryFee = (float) ($deliveryFee ?? $order->delivery_fee_amount);
    $orderTotal = (float) ($totalOrden ?? ($itemsSubtotal + $deliveryFee));
    $displayAmount = $displayAmount ?? static fn ($amount) => (float) $amount;
    $orderRateToBsSnapshot = (float) ($order->sale_rate_to_bs ?? $order->change_rate_to_bs ?? 0);
    if (in_array($orderCurrencyCode, ['VES', 'BS'], true)) {
        $orderRateToBsSnapshot = $orderRateToBsSnapshot > 0 ? $orderRateToBsSnapshot : 1.0;
    }

    $toBsOrderAmount = function (float $amount) use ($orderCurrencyCode, $orderRateToBsSnapshot): ?float {
        if (in_array($orderCurrencyCode, ['VES', 'BS'], true)) {
            return $amount;
        }

        if ($orderRateToBsSnapshot <= 0) {
            return null;
        }

        return $amount * $orderRateToBsSnapshot;
    };
    $toUsdOrderAmount = function (float $amount) use ($orderCurrencyCode, $toBsOrderAmount, $orderRateToBsSnapshot): ?float {
        if ($orderCurrencyCode === 'USD') {
            return $amount;
        }

        if (in_array($orderCurrencyCode, ['VES', 'BS'], true)) {
            return $orderRateToBsSnapshot > 0 ? ($amount / $orderRateToBsSnapshot) : null;
        }

        $amountBs = $toBsOrderAmount($amount);
        if (is_null($amountBs) || $orderRateToBsSnapshot <= 0) {
            return null;
        }

        return $amountBs / $orderRateToBsSnapshot;
    };
    $formatUsdOrderAmount = function (float $amount) use ($toUsdOrderAmount): string {
        $usdAmount = $toUsdOrderAmount($amount);

        return is_null($usdAmount) ? 'USD N/D' : ('USD ' . number_format($usdAmount, 2));
    };

    $formatBsOrderAmount = function (float $amount) use ($toBsOrderAmount): string {
        $bsAmount = $toBsOrderAmount($amount);

        return is_null($bsAmount) ? 'Bs N/D' : ('Bs ' . number_format($bsAmount, 2));
    };
@endphp
<div class="order-header">
    <table class="order-header-top">
        <tr>
            <td class="order-header-left">
                <p class="order-title">ORDEN DE DESPACHO</p>
                <p class="order-company-name">{{ $tienda->name }}</p>
                <table class="order-company-info">
                    <tr>
                        <td><span class="label">RIF:</span> {{ $tienda->rif ?: '-' }}</td>
                        <td><span class="label">Dirección:</span> {{ $tienda->country_name ?? $tienda->countryName() ?? $tienda->country }} {{ $tienda->state_name ?? $tienda->stateName() ?? $tienda->state }} {{ $tienda->city_name ?? $tienda->cityName() ?? $tienda->city }}</td>
                    </tr>
                    <tr>
                        <td><span class="label">Teléfono:</span> {{ $tienda->phone_number ?? 'No registrado' }}</td>
                        <td><span class="label">Email:</span> {{ $tienda->email ?? 'No registrado' }}</td>
                    </tr>
                </table>
            </td>
            <td class="order-header-right">
                <p class="order-logo-line">ESTE DOCUMENTO NO SUSTITUYE LA FACTURA FISCAL. NO VALIDO COMO DOCUMENTO FISCAL</p>
                @if(!empty($imageBase64))
                    <div class="order-logo-box">
                        <img src="{{ $imageBase64 }}" alt="main_logo">
                    </div>
                @endif
            </td>
        </tr>
    </table>
</div>

    <p><strong>Cliente:</strong> {{ $order->user->name }} | <strong>Teléfono:</strong> {{ $order->user->phone_number ?? 'No registrado' }}</p>
    <p><strong>Dirección:</strong> {{ $order->address }}</p>
    <p><strong>Moneda de la venta:</strong> {{ $orderCurrencyCode }} | <strong>Moneda de emisión:</strong> {{ $emissionCurrencyCode }}</p>
    <p><strong>Fecha:</strong> {{ $order->date }} | <strong>Estado:</strong> {{ $order->status == 0 ? 'En Proceso' : ($order->status == 1 ? 'Aprobado' : 'Negado') }}</p>


    <h2>Detalles de la Orden Nro {{ $order->id }}</h2>
    <p><strong>Cliente:</strong> {{ $order->user->name }} | <strong>Teléfono:</strong> {{ $order->user->phone_number ?? 'No registrado' }}</p>
    <p><strong>Entrega:</strong> {{ $order->preference }} | <strong>Dirección:</strong> {{ $order->address }}</p>
    <p><strong>Fecha:</strong> {{ $order->date }} | <strong>Estado:</strong> {{ $order->status == 0 ? 'En Proceso' : ($order->status == 1 ? 'Aprobado' : 'Negado') }}</p>

    <!-- Detalles de productos -->
    <h2>Productos en la Orden</h2>
    <table>
        <thead>
            <tr>
                <th>Producto</th>
                <th>Cantidad</th>
                <th>Variante</th>
                <th>Sub total $</th>
                <th>Sub total Bs</th>
                <th>Total $</th>
                <th>Total Bs</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->details as $detalle)
            @php
                $lineSubtotal = (float) ($detalle->line_subtotal_before_discount ?? ($detalle->price * $detalle->quantity));
                $lineTotal = (float) ($detalle->amount ?? 0);
            @endphp
            <tr>
                <td>{{ $detalle->variant->product->name ?? 'Sin nombre' }}</td>
                <td>{{ $detalle->quantity }}</td>
                <td>{{ $detalle->variant->size ?? '' }}</td>
                <td style="text-align: right;">{{ $formatUsdOrderAmount($lineSubtotal) }}</td>
                <td style="text-align: right;">{{ $formatBsOrderAmount($lineSubtotal) }}</td>
                <td style="text-align: right;">{{ $formatUsdOrderAmount($lineTotal) }}</td>
                <td style="text-align: right;">{{ $formatBsOrderAmount($lineTotal) }}</td>
            </tr>
            @endforeach
            @if($deliveryFee > 0)
            <tr>
                <td><strong>Delivery</strong></td>
                <td>1</td>
                <td>-</td>
                <td style="text-align: right;">{{ $formatUsdOrderAmount($deliveryFee) }}</td>
                <td style="text-align: right;">{{ $formatBsOrderAmount($deliveryFee) }}</td>
                <td style="text-align: right;">{{ $formatUsdOrderAmount($deliveryFee) }}</td>
                <td style="text-align: right;">{{ $formatBsOrderAmount($deliveryFee) }}</td>
            </tr>
            @endif
            <tr>
                <td colspan="3" style="text-align: right;"><strong>Total orden</strong></td>
                <td style="text-align: right;"><strong>{{ $formatUsdOrderAmount($orderTotal) }}</strong></td>
                <td style="text-align: right;"><strong>{{ $formatBsOrderAmount($orderTotal) }}</strong></td>
                <td style="text-align: right;"><strong>{{ $formatUsdOrderAmount($orderTotal) }}</strong></td>
                <td style="text-align: right;"><strong>{{ $formatBsOrderAmount($orderTotal) }}</strong></td>
            </tr>
        </tbody>
    </table>


    <!-- Detalles de pagos -->
    {{--
    <h2>Pagos Registrados</h2>
    <table>
        <thead>
            <tr>
                <th>Moneda</th>
                <th>Método de Pago</th>
                <th>Monto</th>
                <th>Beneficiario</th>
                <th>Banco</th>
                <th>Referencia</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->payments as $payment)
            <tr>
                <td>{{ $payment->currency }}</td>
                <td>{{ $payment->payment->name }}</td>
                <td>${{ number_format($payment->amount, 2) }}</td>
                <td>{{ $payment->payment->admin_name }}</td>
                <td>{{ $payment->payment->bank }}</td>
                <td>{{ $payment->reference ?? 'N/A' }}</td>
                <td>{{ $payment->status == 0 ? 'En Proceso' : ($payment->status == 1 ? 'Pagado' : 'Cancelado') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>
    <p><strong>Total Pagado:</strong> ${{ number_format($totalPagado, 2) }}</p>
     --}}

    <img src="{{ $qrCodeBase64 }}" alt="Código QR">

</body>
</html>
