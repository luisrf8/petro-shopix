<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8" />
    <title>Orden de Entrega - Almacén</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 13px;
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
@php
    $orderCurrencyCode = strtoupper(trim((string) ($orderCurrencyCode ?? $order->sale_currency_code ?? ($tienda->base_currency ?? 'USD'))));
    $orderCurrencyCode = in_array($orderCurrencyCode, ['USD', 'EUR', 'VES'], true) ? $orderCurrencyCode : 'USD';
    $emissionCurrencyCode = strtoupper(trim((string) ($emissionCurrencyCode ?? $orderCurrencyCode)));
    $emissionCurrencyCode = in_array($emissionCurrencyCode, ['USD', 'EUR', 'VES'], true) ? $emissionCurrencyCode : $orderCurrencyCode;
    $itemsSubtotal = (float) ($itemsSubtotal ?? $order->items_subtotal);
    $deliveryFee = (float) ($deliveryFee ?? $order->delivery_fee_amount);
    $orderTotal = (float) ($totalOrden ?? ($itemsSubtotal + $deliveryFee));
    $displayAmount = $displayAmount ?? static fn ($amount) => (float) $amount;
@endphp

<table class="header-table">
    <tr>
        <td style="width: 70%; vertical-align: top;">
            <h2 style="margin: 0;">{{ $tienda->name }}</h2>
            <div>RIF: {{ $tienda->rif ?? 'No registrado' }}</div>
            <div>Dirección: {{ trim(($tienda->country ?? '') . ' ' . ($tienda->state ?? '') . ' ' . ($tienda->city ?? '')) }}</div>
            <div>Documento interno de almacén (sin derecho a crédito fiscal)</div>
        </td>
        <td style="width: 30%; text-align: right; vertical-align: top;">
            @if(!empty($imageBase64))
                <img src="{{ $imageBase64 }}" alt="Logo" style="width: 120px; height: 120px;">
            @endif
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
        </tr>
    </thead>
    <tbody>
        @foreach($order->details as $detalle)
            <tr>
                <td>{{ $detalle->variant->product->name ?? 'Sin nombre' }}</td>
                <td>{{ $detalle->quantity }}</td>
                <td>{{ $detalle->variant->size ?? '' }}</td>
            </tr>
        @endforeach
    </tbody>
</table>

<table style="margin-top: 12px; width: 55%; margin-left: auto;">
    <tbody>
        <tr>
            <td><strong>Subtotal productos</strong></td>
            <td style="text-align: right;">{{ number_format($displayAmount($itemsSubtotal), 2) }} {{ $emissionCurrencyCode }}</td>
        </tr>
        @if($deliveryFee > 0)
            <tr>
                <td><strong>Delivery</strong></td>
                <td style="text-align: right;">{{ number_format($displayAmount($deliveryFee), 2) }} {{ $emissionCurrencyCode }}</td>
            </tr>
        @endif
        <tr>
            <td><strong>Total orden</strong></td>
            <td style="text-align: right;">{{ number_format($displayAmount($orderTotal), 2) }} {{ $emissionCurrencyCode }}</td>
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
