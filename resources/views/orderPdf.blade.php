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

        img {
            display: block;
            margin: 10px auto;
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
    $formatDualOrderAmount = function (float $amount) use ($toUsdOrderAmount, $toBsOrderAmount): string {
        $usdAmount = $toUsdOrderAmount($amount);
        $bsAmount = $toBsOrderAmount($amount);

        $usdText = is_null($usdAmount) ? 'USD N/D' : ('USD ' . number_format($usdAmount, 2));
        $bsText = is_null($bsAmount) ? 'Bs N/D' : ('Bs ' . number_format($bsAmount, 2));

        return $usdText . ' / ' . $bsText;
    };
@endphp
<table width="100%" style="border-collapse: collapse; border: none; margin-bottom: 6px;">
    <tr>
        <td style="text-align: center; padding: 0; border: none;">
            @if(!empty($imageBase64))
                <img src="{{ $imageBase64 }}" alt="main_logo" style="width: 130px; height: 130px">
            @endif
        </td>
    </tr>
</table>

<table width="100%" style="border-collapse: collapse; border: none; margin-bottom: 10px;">
    <tr>
        <td style="text-align: left; padding: 0; border: none;">
            <p><strong>ESTE DOCUMENTO NO SUSTITUYE LA FACTURA FISCAL. NO VALIDO COMO DOCUMENTO FISCAL</strong></p>
            <h1>ORDEN DE DESPACHO</h1>
            <p><strong>{{ $tienda->name }}</strong></p>
            @if(!empty($tienda->rif))
                <p>RIF: {{ $tienda->rif }}</p>
            @endif
            <p>Direccion de la empresa: {{ $tienda->country_name ?? $tienda->countryName() ?? $tienda->country }} {{ $tienda->state_name ?? $tienda->stateName() ?? $tienda->state }} {{ $tienda->city_name ?? $tienda->cityName() ?? $tienda->city }}</p>
        </td>
    </tr>
</table>

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
                <th>Sub total $ / Bs</th>
                <th>Total $ / Bs</th>
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
                <td style="text-align: right;">{{ $formatDualOrderAmount($lineSubtotal) }}</td>
                <td style="text-align: right;">{{ $formatDualOrderAmount($lineTotal) }}</td>
            </tr>
            @endforeach
            @if($deliveryFee > 0)
            <tr>
                <td><strong>Delivery</strong></td>
                <td>1</td>
                <td>-</td>
                <td style="text-align: right;">{{ $formatDualOrderAmount($deliveryFee) }}</td>
                <td style="text-align: right;">{{ $formatDualOrderAmount($deliveryFee) }}</td>
            </tr>
            @endif
            <tr>
                <td colspan="3" style="text-align: right;"><strong>Total orden</strong></td>
                <td style="text-align: right;"><strong>{{ $formatDualOrderAmount($orderTotal) }}</strong></td>
                <td style="text-align: right;"><strong>{{ $formatDualOrderAmount($orderTotal) }}</strong></td>
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
