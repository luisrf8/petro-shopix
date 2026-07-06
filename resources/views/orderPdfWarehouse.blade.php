<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <title>Orden de Entrega - Almacén</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 13px;
            position: relative;
        }

        .non-fiscal-watermark {
            position: fixed;
            top: 38%;
            left: 8%;
            right: 8%;
            transform: rotate(-28deg);
            font-size: 30px;
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

        .header-table td,
        .meta-table td,
        .sign-table td {
            border: none;
            padding: 4px 0;
        }

        .meta-box {
            border: 1px solid #000;
            padding: 10px;
            margin: 12px 0;
        }

        .sign-line {
            height: 42px;
            border-bottom: 1px solid #000;
        }

        .text-right {
            text-align: right;
        }

        .section-title {
            margin: 12px 0 8px;
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

<table class="header-table" style="margin-bottom: 8px;">
    <tr>
        <td style="text-align: center;">
            @if(!empty($imageBase64))
                <img src="{{ $imageBase64 }}" alt="Logo" style="width: 120px; height: 120px;">
            @endif
        </td>
    </tr>
</table>

<table class="header-table" style="margin-bottom: 10px;">
    <tr>
        <td style="width: 100%; vertical-align: top;">
            <h2 style="margin: 0 0 4px;">ORDEN DE DESPACHO</h2>
            <div><strong>{{ $tienda->name }}</strong></div>
            @if(!empty($tienda->rif))
                <div>RIF: {{ $tienda->rif }}</div>
            @endif
            <div>Direccion: {{ trim(($tienda->country ?? '') . ' ' . ($tienda->state ?? '') . ' ' . ($tienda->city ?? '')) }}</div>
            <div><strong>ESTE DOCUMENTO NO SUSTITUYE LA FACTURA FISCAL. NO VALIDO COMO DOCUMENTO FISCAL</strong></div>
        </td>
    </tr>
</table>

<div class="meta-box">
    <table class="meta-table">
        <tr>
            <td><strong>Orden de entrega #{{ $order->id }}</strong></td>
            <td class="text-right"><strong>Fecha:</strong> {{ $order->date }}</td>
        </tr>
        <tr>
            <td><strong>Cliente:</strong> {{ $order->user->name }}</td>
            <td class="text-right"><strong>Teléfono:</strong> {{ $order->user->phone_number ?? 'No registrado' }}</td>
        </tr>
        <tr>
            <td><strong>Tipo de entrega:</strong> {{ $order->preference ?: 'No definido' }}</td>
            <td class="text-right"><strong>Estado:</strong> {{ $order->status == 0 ? 'En proceso' : ($order->status == 1 ? 'Aprobado' : 'Negado') }}</td>
        </tr>
        <tr>
            <td colspan="2"><strong>Dirección de entrega:</strong> {{ $order->address ?: 'No registrada' }}</td>
        </tr>
        <tr>
            <td><strong>Moneda venta:</strong> {{ $orderCurrencyCode }}</td>
            <td class="text-right"><strong>Moneda emisión:</strong> {{ $emissionCurrencyCode }}</td>
        </tr>
    </table>
</div>

<h3 class="section-title">Detalle de productos</h3>
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

<table class="sign-table" style="margin-top: 24px;">
    <tr>
        <td style="width: 48%;">
            <div class="sign-line"></div>
            <div style="margin-top: 6px;"><strong>Entrega almacén</strong></div>
        </td>
        <td style="width: 4%;"></td>
        <td style="width: 48%;">
            <div class="sign-line"></div>
            <div style="margin-top: 6px;"><strong>Recibe cliente</strong></div>
        </td>
    </tr>
</table>

@if(!empty($qrCodeBase64))
    <div style="margin-top: 18px; text-align: center;">
        <img src="{{ $qrCodeBase64 }}" alt="Código QR" style="width: 110px; height: 110px;">
    </div>
@endif

</body>
</html>
