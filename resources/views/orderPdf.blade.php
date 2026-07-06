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
            position: relative;
            transform: rotate(-28deg);
            font-size: 34px;
            font-weight: 800;
            text-align: center;
            color: rgba(160, 0, 0, 0.30);
            letter-spacing: 2px;
            z-index: 0;
            pointer-events: none;
            line-height: 1.1;
            margin: 34px 0 -72px;
        }

        .products-watermark-wrap {
            position: relative;
            margin-top: 0;
        }

        .products-watermark-wrap > h2,
        .products-watermark-wrap > .products-table {
            position: relative;
            z-index: 1;
        }

        .products-watermark-wrap > h2 {
            margin: 0 0 3px;
            line-height: 1.05;
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
            margin-bottom: 4px;
        }

        .order-header-top {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 2px;
        }

        .order-header-top td {
            border: none;
            padding: 0;
            vertical-align: top;
        }

        .order-header-logo {
            width: 18%;
            padding-right: 4px;
        }

        .order-header-title {
            text-align: center;
            vertical-align: middle;
        }

        .order-header-qr {
            width: 112px;
            text-align: right;
            vertical-align: middle;
        }

        .order-title {
            margin: 0;
            font-size: 24px;
            font-weight: 800;
            line-height: 1.05;
        }

        .order-header-qr img {
            width: 100px;
            height: 100px;
            display: inline-block;
        }

        .order-company-name {
            margin: 0 0 1px;
            font-size: 16px;
            font-weight: 700;
            line-height: 1.05;
        }

        .order-company-info {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 4px;
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
            margin: 0 0 2px;
            line-height: 1.15;
        }

        .order-logo-box {
            display: inline-block;
            width: 102px;
            text-align: left;
        }

        .order-logo-box img {
            display: block;
            margin-left: 0;
            margin-right: auto;
            max-width: 102px;
            max-height: 48px;
        }

        .order-summary-grid {
            width: 100%;
            border-collapse: collapse;
            margin: 0 0 4px;
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
@php
    $storeData = $tienda ?? $order->tenant;
    $storeName = optional($storeData)->name ?? '-';
    $storeRif = optional($storeData)->rif ?: '-';
    $storePhone = optional($storeData)->phone_number ?? 'No registrado';
    $storeEmail = optional($storeData)->email ?? 'No registrado';
    $storeCountry = optional($storeData)->country_name ?? (method_exists($storeData, 'countryName') ? $storeData->countryName() : optional($storeData)->country);
    $storeState = optional($storeData)->state_name ?? (method_exists($storeData, 'stateName') ? $storeData->stateName() : optional($storeData)->state);
    $storeCity = optional($storeData)->city_name ?? (method_exists($storeData, 'cityName') ? $storeData->cityName() : optional($storeData)->city);
    $orderCurrencyCode = strtoupper(trim((string) ($orderCurrencyCode ?? $order->sale_currency_code ?? (optional($storeData)->base_currency ?? 'USD'))));
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
            <td class="order-header-logo">
                @if(!empty($imageBase64))
                    <div class="order-logo-box">
                        <img src="{{ $imageBase64 }}" alt="main_logo">
                    </div>
                @endif
            </td>
            <td class="order-header-title">
                <p class="order-title">ORDEN DE DESPACHO</p>
            </td>
            <td class="order-header-qr">
                @if(!empty($qrCodeBase64))
                    <img src="{{ $qrCodeBase64 }}" alt="Código QR">
                @endif
            </td>
        </tr>
        <tr>
            <td colspan="3">
                <p class="order-logo-line">ESTE DOCUMENTO NO SUSTITUYE LA FACTURA FISCAL. NO VALIDO COMO DOCUMENTO FISCAL</p>
                <p class="order-company-name">{{ $storeName }}</p>
                <table class="order-company-info">
                    <tr>
                        <td><span class="label">RIF:</span> {{ $storeRif }}</td>
                        <td><span class="label">Dirección:</span> {{ trim(($storeCountry ?? '') . ' ' . ($storeState ?? '') . ' ' . ($storeCity ?? '')) }}</td>
                    </tr>
                    <tr>
                        <td><span class="label">Teléfono:</span> {{ $storePhone }}</td>
                        <td><span class="label">Email:</span> {{ $storeEmail }}</td>
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

    <div class="products-watermark-wrap">
    <div class="non-fiscal-watermark">DOCUMENTO INTERNO - NO FISCAL - SIN VALIDEZ TRIBUTARIA</div>
    <!-- Detalles de productos -->
    <h2>Productos en la Orden</h2>
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
    </div>


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

</body>
</html>
