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
<table width="100%" style="border-collapse: collapse; border: none;">
    <tr>
        <td style="text-align: left; padding: 0; border: none;">
        </td>
    </tr>
</table>



    <h2>Factura Nro {{ $order->id }}</h2>
    <p><strong>Cliente:</strong> {{ $order->user->name }} | <strong>Teléfono:</strong> {{ $order->user->phone_number ?? 'No registrado' }}</p>
    <p><strong>Dirección:</strong> {{ $order->address }}</p>
    <p><strong>Fecha:</strong> {{ $order->date }} | <strong>Estado:</strong> {{ $order->status == 0 ? 'En Proceso' : ($order->status == 1 ? 'Aprobado' : 'Negado') }}</p>

    <!-- Detalles de productos -->
    <h2>Detalle</h2>
    <table>
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
                <td>{{ $detalle->variant->product->name ?? 'Sin nombre' }} | {{ $detalle->taxes->count() > 0 ? '(G)' : '(E)' }}</td>
                <td>{{ $detalle->quantity }}</td>
                <td>{{ $detalle->variant->size ?? '' }}</td>
                <td>${{ number_format($detalle->price, 2) }}</td>
                <td>${{ number_format($detalle->amount, 2) }}</td>
            </tr>
            @endforeach
            <tr>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td><strong>Sub Total</strong></td>
                    <td>${{ number_format($totalOrden, 2) }}</td>
                </tr>
            @foreach($order->details as $detalle)
                @foreach($detalle->taxes as $tax)
                <tr>
                    <td></td>
                    <td></td>
                    <td>{{ $tax->tax_name }}</td>
                    <td>{{ number_format($tax->tax_rate, 2) }}%</td>
                    <td>${{ number_format($tax->tax_amount, 2) }}</td>
                </tr>
            @endforeach

            @endforeach
                <tr>
                    <td></td>
                    <td></td>
                    <td></td>
                    <td><strong>Total</strong></td>
                    <td>${{ number_format($totalGeneral, 2) }}</td>
                </tr>
        </tbody>
    </table>
    <!-- Detalles de impuestos -->
    <table>

        <tbody>

        </tbody>
    </table>


    <p>
        Equivalente a {{ number_format($totalGeneral, 2) * $dollarRate }},00 Bolívares calculados a tasa BCV 
        ( {{ number_format($dollarRate, 2) }} ) del día 
        {{ \Carbon\Carbon::parse($order->date)->format('d/m/Y') }}
    </p>


</body>
</html>
