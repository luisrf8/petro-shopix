<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8" />
    <title>Orden de Venta</title>
    <style>
        body {
            font-family: Arial, sans-serif;
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
@php
    $storeData = $order->tenant;
    $storeName = optional($storeData)->name ?? '-';
    $storeEmail = optional($storeData)->email ?? 'No registrado';
    $storeRif = optional($storeData)->rif ?? '-';
    $orderCurrencyCode = strtoupper(trim((string) ($orderCurrencyCode ?? $order->sale_currency_code ?? (optional($storeData)->base_currency ?? 'USD'))));
    $orderCurrencyCode = in_array($orderCurrencyCode, ['USD', 'EUR', 'VES'], true) ? $orderCurrencyCode : 'USD';
    $emissionCurrencyCode = strtoupper(trim((string) ($emissionCurrencyCode ?? $orderCurrencyCode)));
    $emissionCurrencyCode = in_array($emissionCurrencyCode, ['USD', 'EUR', 'VES'], true) ? $emissionCurrencyCode : $orderCurrencyCode;
    $emissionCurrencySymbol = (string) ($emissionCurrencySymbol ?? ($emissionCurrencyCode === 'EUR' ? '€' : ($emissionCurrencyCode === 'VES' ? 'Bs' : '$')));
    $emissionConversionFactor = (float) ($emissionConversionFactor ?? 1);
    $emissionRateToBs = (float) ($emissionRateToBs ?? 0);
    $itemsSubtotal = (float) ($itemsSubtotal ?? $order->items_subtotal);
    $deliveryFee = (float) ($deliveryFee ?? $order->delivery_fee_amount);
    $taxTotalAmount = (float) ($totalTaxes ?? $order->details->flatMap->taxes->sum('tax_amount'));
    $invoiceGrandTotal = (float) ($totalGeneral ?? ($itemsSubtotal + $deliveryFee + $taxTotalAmount));
    $totalDiscount = (float) ($order->total_discount ?? $order->details->sum(function ($detail) {
        $lineSubtotal = (float) ($detail->amount ?? 0);
        $lineBase = (float) ($detail->line_subtotal_before_discount ?? 0);

        if ($lineBase <= 0) {
            $lineBase = $lineSubtotal + (float) ($detail->line_discount_amount ?? 0);
        }

        return max(0, $lineBase - $lineSubtotal);
    }));
    $displayAmount = function ($value) use ($emissionConversionFactor) {
        return (float) $value * $emissionConversionFactor;
    };
@endphp
<table width="100%" style="border-collapse: collapse; border: none;">
    <tr>
        <td style="text-align: left; padding: 0; border: none;">
        </td>
    </tr>
</table>



    <h2>Factura Nro {{ $order->id }}</h2>
    <p><strong>Tienda:</strong> {{ $storeName }} | <strong>Email:</strong> {{ $storeEmail }}</p>
    <p><strong>RIF:</strong> {{ $storeRif }}</p>
    <p><strong>Cliente:</strong> {{ $order->user->name }} | <strong>Teléfono:</strong> {{ $order->user->phone_number ?? 'No registrado' }}</p>
    <p><strong>Dirección:</strong> {{ $order->address }}</p>
    <p><strong>Moneda de la venta:</strong> {{ $orderCurrencyCode }} | <strong>Moneda de emisión:</strong> {{ $emissionCurrencyCode }}</p>
    <p><strong>Fecha:</strong> {{ $order->date }} | <strong>Estado:</strong> {{ $order->status == 0 ? 'En Proceso' : ($order->status == 1 ? 'Aprobado' : 'Negado') }}</p>

    <!-- Detalles de productos -->
    <h2>Detalle</h2>
    <table>
        <thead>
            <tr>
                <th>Producto</th>
                <th>Cantidad</th>
                <th>Variante</th>
                <th>Precio antes desc.</th>
                <th>Precio Unitario</th>
                <th>Descuento</th>
                <th>Subtotal neto</th>
            </tr>
        </thead>
        <tbody>
            @foreach($order->details as $detalle)
            @php
                $lineSubtotal = (float) ($detalle->amount ?? 0);
                $lineBase = (float) ($detalle->line_subtotal_before_discount ?? 0);
                if ($lineBase <= 0) {
                    $lineBase = $lineSubtotal + (float) ($detalle->line_discount_amount ?? 0);
                }

                if ($lineBase <= 0) {
                    $lineBase = $lineSubtotal;
                }

                $lineDiscount = max(0, $lineBase - $lineSubtotal);
            @endphp
            <tr>
                <td>{{ $detalle->variant->product->display_name ?? 'Sin nombre' }} | {{ $detalle->taxes->count() > 0 ? '(G)' : '(E)' }}</td>
                <td>{{ $detalle->quantity }}</td>
                <td>{{ $detalle->variant->size ?? '' }}</td>
                <td>{{ $emissionCurrencySymbol }}{{ number_format($displayAmount($lineBase), 2) }}</td>
                <td>{{ $emissionCurrencySymbol }}{{ number_format($displayAmount($detalle->price), 2) }}</td>
                <td>{{ $emissionCurrencySymbol }}{{ number_format($displayAmount($lineDiscount), 2) }}</td>
                <td>{{ $emissionCurrencySymbol }}{{ number_format($displayAmount($lineSubtotal), 2) }}</td>
            </tr>
            @endforeach
            @if($totalDiscount > 0)
                <tr>
                    <td colspan="6"><strong>Total descuentos</strong></td>
                    <td>{{ $emissionCurrencySymbol }}{{ number_format($displayAmount($totalDiscount), 2) }}</td>
                </tr>
            @endif
            <tr>
                    <td colspan="6"><strong>Sub Total</strong></td>
                    <td>{{ $emissionCurrencySymbol }}{{ number_format($displayAmount($itemsSubtotal), 2) }}</td>
                </tr>
            @if($deliveryFee > 0)
                <tr>
                    <td colspan="6"><strong>Delivery</strong></td>
                    <td>{{ $emissionCurrencySymbol }}{{ number_format($displayAmount($deliveryFee), 2) }}</td>
                </tr>
            @endif
            @foreach($order->details as $detalle)
                @foreach($detalle->taxes as $tax)
                <tr>
                    <td></td>
                    <td></td>
                    <td>{{ $tax->tax_name }}</td>
                    <td>{{ number_format($tax->tax_rate, 2) }}%</td>
                    <td></td>
                    <td></td>
                    <td>{{ $emissionCurrencySymbol }}{{ number_format($displayAmount($tax->tax_amount), 2) }}</td>
                </tr>
            @endforeach

            @endforeach
                <tr>
                    <td colspan="6"><strong>Total</strong></td>
                    <td>{{ $emissionCurrencySymbol }}{{ number_format($displayAmount($invoiceGrandTotal), 2) }}</td>
                </tr>
        </tbody>
    </table>
    <!-- Detalles de impuestos -->
    <table>

        <tbody>

        </tbody>
    </table>


    @php
        $displayTotalGeneral = $displayAmount($invoiceGrandTotal);
    @endphp
    @if($emissionCurrencyCode === 'VES')
        <p>Monto expresado en bolívares (VES).</p>
    @elseif($emissionRateToBs > 0)
        @php
            $totalBs = $displayTotalGeneral * $emissionRateToBs;
        @endphp
        <p>
            Equivalente a {{ number_format($totalBs, 2, ',', '.') }} Bolívares calculados a tasa BCV
            ( {{ number_format($emissionRateToBs, 2, ',', '.') }} ) del día
            {{ \Carbon\Carbon::parse($order->date)->format('d/m/Y') }}
        </p>
    @endif


</body>
</html>
