<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Productos de Entrada</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; }
        h2 { margin: 0 0 8px; }
        .meta { margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ccc; padding: 6px; }
        th { background: #f3f3f3; text-align: left; }
        .num { text-align: right; }
    </style>
</head>
<body>
    <h2>Reporte de Productos de Entrada (Compras)</h2>
    <div class="meta">Rango: {{ $summary['start_date']->format('d/m/Y') }} al {{ $summary['end_date']->format('d/m/Y') }}</div>
    <div class="meta">
        Ordenes: <strong>{{ number_format($summary['orders']) }}</strong> |
        Items: <strong>{{ number_format($summary['total_items']) }}</strong> |
        Monto: <strong>{{ number_format($summary['total_amount'], 2) }} {{ $summary['currency_code'] ?? 'USD' }}</strong>
    </div>

    <table>
        <thead>
            <tr>
                <th># Orden</th>
                <th>Fecha</th>
                <th>Almacen</th>
                <th>Proveedor</th>
                <th class="num">Items</th>
                <th class="num">Monto ({{ $summary['currency_code'] ?? 'USD' }})</th>
            </tr>
        </thead>
        <tbody>
            @forelse($entries as $order)
                <tr>
                    <td>{{ $order->id }}</td>
                    <td>{{ \Carbon\Carbon::parse($order->date)->format('d/m/Y') }}</td>
                    <td>{{ $order->warehouse->name ?? 'N/A' }}</td>
                    <td>{{ $order->provider_display_name }}</td>
                    <td class="num">{{ number_format($order->detalles->sum('quantity')) }}</td>
                    <td class="num">{{ number_format((float) ($order->report_total_amount ?? 0), 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">No hay entradas de inventario en el rango seleccionado.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
