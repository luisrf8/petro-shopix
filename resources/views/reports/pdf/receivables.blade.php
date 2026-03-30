<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Cuentas por Cobrar</title>
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
    <h2>Reporte de Cuentas por Cobrar</h2>
    <div class="meta">Rango: {{ $summary['start_date']->format('d/m/Y') }} al {{ $summary['end_date']->format('d/m/Y') }}</div>
    <div class="meta">Saldo pendiente mínimo: <strong>{{ number_format((float) ($summary['min_pending_balance'] ?? 0), 2) }} {{ $summary['currency_code'] ?? 'USD' }}</strong></div>
    <div class="meta">
        Ordenes: <strong>{{ number_format($summary['orders']) }}</strong> |
        Total: <strong>{{ number_format($summary['total_amount'], 2) }} {{ $summary['currency_code'] ?? 'USD' }}</strong> |
        Cobrado: <strong>{{ number_format($summary['total_paid'], 2) }} {{ $summary['currency_code'] ?? 'USD' }}</strong> |
        Pendiente: <strong>{{ number_format($summary['total_pending'], 2) }} {{ $summary['currency_code'] ?? 'USD' }}</strong>
    </div>
    <table>
        <thead>
            <tr>
                <th># Orden</th>
                <th>Fecha</th>
                <th>Cliente</th>
                <th class="num">Items</th>
                <th class="num">Total</th>
                <th class="num">Cobrado</th>
                <th class="num">Saldo</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $order)
                <tr>
                    <td>{{ $order->id }}</td>
                    <td>{{ \Carbon\Carbon::parse($order->date)->format('d/m/Y') }}</td>
                    <td>{{ $order->user->name ?? 'N/A' }}</td>
                    <td class="num">{{ number_format($order->details->sum('quantity')) }}</td>
                    <td class="num">{{ number_format($order->order_total_amount, 2) }}</td>
                    <td class="num">{{ number_format($order->approved_paid_amount, 2) }}</td>
                    <td class="num">{{ number_format($order->pending_amount, 2) }}</td>
                </tr>
            @empty
                <tr><td colspan="7">No hay cuentas por cobrar en el rango seleccionado.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>