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
            <p>"Sin derecho a crédito fiscal."</p>
            <h1>{{ $tienda->name }}</h1>
            <p>RIF: {{ $tienda->rif }} J-00000005 </p>
            <p>Direccion de la empresa: {{ $tienda->country }} {{ $tienda->state }} {{ $tienda->city  }}</p>
        </td>
        <td style="text-align: right; padding: 0; border: none;">
            <img src="{{ $imageBase64 }}" alt="main_logo" style="width: 150px; height: 150px">
        </td>
    </tr>
</table>

    <p><strong>Cliente:</strong> {{ $order->user->name }} | <strong>Teléfono:</strong> {{ $order->user->phone_number ?? 'No registrado' }}</p>
    <p><strong>Dirección:</strong> {{ $order->address }}</p>
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
                    {{--

                <th>Precio Unitario</th>
                <th>Subtotal</th>
                    --}}
            </tr>
        </thead>
        <tbody>
            @foreach($order->details as $detalle)
            <tr>
                <td>{{ $detalle->variant->product->name ?? 'Sin nombre' }}</td>
                <td>{{ $detalle->quantity }}</td>
                <td>{{ $detalle->variant->size ?? '' }}</td>
                    {{--

                <td>${{ number_format($detalle->price, 2) }}</td>
                <td>${{ number_format($detalle->amount, 2) }}</td>
                    --}}
            </tr>
            @endforeach
        </tbody>
    </table>

    {{--
    <p><strong>Total Orden:</strong> ${{ number_format($totalOrden, 2) }}</p>
     --}}


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
