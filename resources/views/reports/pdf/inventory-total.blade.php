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
    <div class="meta">
        Variantes: <strong>{{ number_format($summary['variants']) }}</strong> |
        Stock total: <strong>{{ number_format($summary['total_stock']) }}</strong> |
        Valor inventario: <strong>{{ number_format($summary['inventory_value'], 2) }} USD</strong>
    </div>

    <table>
        <thead>
            <tr>
                <th>Producto</th>
                <th>Categoria</th>
                <th>Variante</th>
                <th class="num">Stock</th>
                <th class="num">Precio (USD)</th>
                <th class="num">Valor (USD)</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ $row->product->name ?? 'N/A' }}</td>
                    <td>{{ $row->product->category->name ?? 'N/A' }}</td>
                    <td>{{ $row->size ?: 'N/A' }}</td>
                    <td class="num">{{ number_format($row->stock) }}</td>
                    <td class="num">{{ number_format($row->price, 2) }}</td>
                    <td class="num">{{ number_format(((float) $row->stock * (float) $row->price), 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">No hay variantes registradas para esta tienda.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
