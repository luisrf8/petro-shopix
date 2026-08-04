<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Inventario Total</title>
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
    <h2>Reporte de Inventario Total</h2>
    <div class="meta">Generado: {{ $summary['generated_at']->format('d/m/Y H:i') }}</div>
    @if(isset($summary['start_date'], $summary['end_date']))
        <div class="meta">Rango: {{ optional($summary['start_date'])->format('d/m/Y') }} - {{ optional($summary['end_date'])->format('d/m/Y') }}</div>
    @endif
    <div class="meta">
        Variantes: <strong>{{ number_format($summary['variants']) }}</strong> |
        Stock total: <strong>{{ number_format($summary['total_stock']) }}</strong> |
        Valor inventario: <strong>{{ number_format($summary['inventory_value'], 2) }} {{ $summary['currency_code'] ?? 'USD' }}</strong>
    </div>

    <table>
        <thead>
            <tr>
                <th>Producto</th>
                <th>Categoria</th>
                <th>Variante</th>
                <th class="num">Stock</th>
                <th class="num">Precio ({{ $summary['currency_code'] ?? 'USD' }})</th>
                <th class="num">Valor ({{ $summary['currency_code'] ?? 'USD' }})</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ $row->product->display_name ?? 'N/A' }}</td>
                    <td>{{ $row->product->category->name ?? 'N/A' }}</td>
                    <td>{{ $row->size ?: 'N/A' }}</td>
                    <td class="num">{{ number_format($row->stock) }}</td>
                    <td class="num">{{ number_format((float) ($row->report_price ?? 0), 2) }}</td>
                    <td class="num">{{ number_format((float) ($row->report_value ?? 0), 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">No hay variantes registradas para esta sede.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
