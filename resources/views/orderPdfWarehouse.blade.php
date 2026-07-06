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
            width: 34%;
            padding-right: 10px;
        }

        .order-header-right {
            width: 66%;
            text-align: left;
        }

        .order-title {
            margin: 0 0 1px;
            font-size: 22px;
            font-weight: 800;
            line-height: 1.05;
        }

        .order-company-name {
            margin: 0 0 2px;
            font-size: 16px;
            font-weight: 700;
            line-height: 1.05;
        }

        .order-company-info {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 10px;
        }

        .order-company-info td {
            border: none;
            padding: 1px 0;
            width: 50%;
            vertical-align: top;
            font-size: 12px;
        }

        .order-company-info .label {
            font-weight: 700;
        }

        .order-logo-line {
            font-size: 11px;
            font-weight: 700;
            margin: 0 0 1px;
            line-height: 1.15;
        }

        .order-logo-box {
            display: inline-block;
            width: 155px;
            text-align: left;
        }

        .order-logo-box img {
            display: block;
            margin-left: 0;
            margin-right: auto;
            max-width: 155px;
            max-height: 72px;
        }

        .order-summary-grid {
            width: 100%;
            border-collapse: collapse;
            margin: 4px 0 10px;
        }

        .order-summary-grid td {
            border: none;
            padding: 0;
            vertical-align: top;
            width: 50%;
        }

        .order-summary-block {
            font-size: 13px;
            line-height: 1.45;
        }

        .order-summary-block p {
            margin: 0 0 2px;
        }

        .order-summary-block .label {
            font-weight: 700;
        }

        .products-table th,
        .products-table td {
            font-size: 11px;
            padding: 6px 5px;
        }

        .products-table th {
            white-space: nowrap;
        }

        .products-table .qty-cell {
            text-align: center;
        }

        .products-table .amount-cell {
            text-align: right;
            white-space: nowrap;
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

        return number_format((float) ($usdAmount ?? 0), 2, '.', ',');
    };

    $formatBsOrderAmount = function (float $amount) use ($toBsOrderAmount): string {
        $bsAmount = $toBsOrderAmount($amount);

        return number_format((float) ($bsAmount ?? 0), 2, '.', ',');
    };
@endphp

<div class="order-header">
    <table class="order-header-top">
        <tr>
            <td class="order-header-left">
                @if(!empty($imageBase64))
                    <div class="order-logo-box">
                        <img src="{{ $imageBase64 }}" alt="Logo">
                    </div>
                @endif
            </td>
            <td class="order-header-right">
                <p class="order-logo-line">ESTE DOCUMENTO NO SUSTITUYE LA FACTURA FISCAL. NO VALIDO COMO DOCUMENTO FISCAL</p>
                <p class="order-title">ORDEN DE DESPACHO</p>
                <p class="order-company-name">{{ $tienda->name }}</p>
                <table class="order-company-info">
                    <tr>
                        <td><span class="label">RIF:</span> {{ $tienda->rif ?: '-' }}</td>
                        <td><span class="label">Dirección:</span> {{ trim(($tienda->country ?? '') . ' ' . ($tienda->state ?? '') . ' ' . ($tienda->city ?? '')) }}</td>
                    </tr>
                    <tr>
                        <td><span class="label">Teléfono:</span> {{ $tienda->phone_number ?? 'No registrado' }}</td>
                        <td><span class="label">Email:</span> {{ $tienda->email ?? 'No registrado' }}</td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</div>

<table class="order-summary-grid">
    <tr>
        <td>
            <div class="order-summary-block">
                <p><span class="label">Cliente:</span> {{ $order->user->name }} | <span class="label">Teléfono:</span> {{ $order->user->phone_number ?? 'No registrado' }}</p>
                <p><span class="label">Dirección:</span> {{ $order->address }}</p>
                <p><span class="label">Moneda de la venta:</span> {{ $orderCurrencyCode }} | <span class="label">Moneda de emisión:</span> {{ $emissionCurrencyCode }}</p>
            </div>
        </td>
        <td>
            <div class="order-summary-block">
                <p><span class="label">Detalles de la Orden Nro {{ $order->id }}</span></p>
                <p><span class="label">Entrega:</span> {{ $order->preference }} | <span class="label">Dirección:</span> {{ $order->address }}</p>
                <p><span class="label">Fecha:</span> {{ $order->date }} | <span class="label">Estado:</span> {{ $order->status == 0 ? 'En Proceso' : ($order->status == 1 ? 'Aprobado' : 'Negado') }}</p>
            </div>
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
<table class="products-table">
    <colgroup>
        <col style="width: 19%;">
        <col style="width: 8%;">
        <col style="width: 17%;">
        <col style="width: 14%;">
        <col style="width: 14%;">
        <col style="width: 14%;">
        <col style="width: 14%;">
    </colgroup>
    <thead>
        <tr>
            <th>Producto</th>
            <th>Cantidad</th>
            <th>Variante</th>
            <th>Sub total USD</th>
            <th>Sub total Bs</th>
            <th>Total USD</th>
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
                <td class="qty-cell">{{ (int) round((float) $detalle->quantity) }}</td>
                <td>{{ $detalle->variant->size ?? '' }}</td>
                <td class="amount-cell">{{ $formatUsdOrderAmount($lineSubtotal) }}</td>
                <td class="amount-cell">{{ $formatBsOrderAmount($lineSubtotal) }}</td>
                <td class="amount-cell">{{ $formatUsdOrderAmount($lineTotal) }}</td>
                <td class="amount-cell">{{ $formatBsOrderAmount($lineTotal) }}</td>
            </tr>
        @endforeach
        @if($deliveryFee > 0)
            <tr>
                <td><strong>Delivery</strong></td>
                <td class="qty-cell">1</td>
                <td>-</td>
                <td class="amount-cell">{{ $formatUsdOrderAmount($deliveryFee) }}</td>
                <td class="amount-cell">{{ $formatBsOrderAmount($deliveryFee) }}</td>
                <td class="amount-cell">{{ $formatUsdOrderAmount($deliveryFee) }}</td>
                <td class="amount-cell">{{ $formatBsOrderAmount($deliveryFee) }}</td>
            </tr>
        @endif
        <tr>
            <td colspan="3" style="text-align: right;"><strong>Total orden</strong></td>
            <td class="amount-cell"><strong>{{ $formatUsdOrderAmount($orderTotal) }}</strong></td>
            <td class="amount-cell"><strong>{{ $formatBsOrderAmount($orderTotal) }}</strong></td>
            <td class="amount-cell"><strong>{{ $formatUsdOrderAmount($orderTotal) }}</strong></td>
            <td class="amount-cell"><strong>{{ $formatBsOrderAmount($orderTotal) }}</strong></td>
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
