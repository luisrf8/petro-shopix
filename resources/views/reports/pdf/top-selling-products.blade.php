<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Productos Mas Vendidos</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; }
        h2 { margin: 0 0 8px; }
        .meta { margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ccc; padding: 6px; }
        th { background: #f3f3f3; text-align: left; }
        .num { text-align: right; }
        .summary { margin-top: 8px; }
    </style>
</head>
<body>
    <h2>Reporte de Productos Mas Vendidos</h2>
    <div class="meta">Rango: {{ $summary['start_date']->format('d/m/Y') }} al {{ $summary['end_date']->format('d/m/Y') }}</div>
    <div class="summary">
        Total de unidades: <strong>{{ number_format($summary['total_units']) }}</strong> |
        Total vendido: <strong>{{ number_format($summary['total_amount'], 2) }} USD</strong>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Producto</th>
                <th>Variante</th>
                <th class="num">Unidades</th>
                <th class="num">Monto (USD)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $index => $row)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $row->product_name }}</td>
                    <td>{{ $row->variant_name ?: 'N/A' }}</td>
                    <td class="num">{{ number_format($row->total_quantity) }}</td>
                    <td class="num">{{ number_format($row->total_amount, 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">No hay ventas en el rango seleccionado.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
