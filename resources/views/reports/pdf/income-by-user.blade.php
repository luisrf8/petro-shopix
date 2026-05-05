<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Ingresos por Usuario</title>
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
    <h2>Reporte de Ingresos por Usuario</h2>
    <div class="meta">Rango: {{ $summary['start_date']->format('d/m/Y') }} al {{ $summary['end_date']->format('d/m/Y') }}</div>
    <div class="meta">
        Usuarios: <strong>{{ number_format($summary['rows_count'] ?? 0) }}</strong> |
        Ordenes: <strong>{{ number_format($summary['orders_count'] ?? 0) }}</strong> |
        Clientes: <strong>{{ number_format($summary['customers_count'] ?? 0) }}</strong> |
        Total vendido: <strong>{{ number_format($summary['total_amount'] ?? 0, 2) }} {{ $summary['currency_code'] ?? 'USD' }}</strong> |
        Total cobrado: <strong>{{ number_format($summary['total_paid'] ?? 0, 2) }} {{ $summary['currency_code'] ?? 'USD' }}</strong>
    </div>

    <table>
        <thead>
            <tr>
                <th>Usuario</th>
                <th class="num">Ordenes</th>
                <th class="num">Clientes</th>
                <th class="num">Vendido ({{ $summary['currency_code'] ?? 'USD' }})</th>
                <th class="num">Cobrado ({{ $summary['currency_code'] ?? 'USD' }})</th>
                <th class="num">Cobranza (%)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ $row['seller_name'] ?? 'Sin vendedor asignado' }}</td>
                    <td class="num">{{ number_format((int) ($row['orders_count'] ?? 0)) }}</td>
                    <td class="num">{{ number_format((int) ($row['customers_count'] ?? 0)) }}</td>
                    <td class="num">{{ number_format((float) ($row['total_amount'] ?? 0), 2) }}</td>
                    <td class="num">{{ number_format((float) ($row['total_paid'] ?? 0), 2) }}</td>
                    <td class="num">{{ number_format((float) ($row['collection_rate'] ?? 0), 2) }}%</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">No hay movimientos para el filtro seleccionado.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
