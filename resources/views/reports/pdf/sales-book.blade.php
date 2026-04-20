<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Libro de Ventas</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 10px; color: #111; }
        h2 { margin: 0 0 8px; }
        .meta { margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ccc; padding: 5px; }
        th { background: #f3f3f3; text-align: left; }
        .num { text-align: right; }
    </style>
</head>
<body>
    <h2>Libro de Ventas</h2>
    <div class="meta">Rango: {{ $summary['start_date']->format('d/m/Y') }} al {{ $summary['end_date']->format('d/m/Y') }}</div>
    <div class="meta">Fuente fiscal: {{ ($summary['source'] ?? 'shopix') === 'hka' ? 'Sesion HKA' : 'Shopix' }}</div>
    <div class="meta">
        Registros: <strong>{{ number_format($summary['rows_count']) }}</strong> |
        Base gravable: <strong>{{ number_format($summary['taxable_base'], 2) }} {{ $summary['currency_code'] }}</strong> |
        IVA: <strong>{{ number_format($summary['tax_total'], 2) }} {{ $summary['currency_code'] }}</strong> |
        Neto: <strong>{{ number_format($summary['net_total'], 2) }} {{ $summary['currency_code'] }}</strong>
    </div>

    <table>
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Orden</th>
                <th>Cliente</th>
                <th>Documento</th>
                <th>Número</th>
                <th>Control</th>
                <th class="num">Base</th>
                <th class="num">IVA</th>
                <th class="num">Total</th>
                <th class="num">N/C</th>
                <th class="num">N/D</th>
                <th class="num">Ret.</th>
                <th class="num">Neto</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ $row['sale_date'] }}</td>
                    <td>{{ $row['order_id'] }}</td>
                    <td>{{ $row['customer_name'] }}</td>
                    <td>{{ $row['document_label'] }}</td>
                    <td>{{ $row['document_number'] }}</td>
                    <td>{{ $row['control_number'] }}</td>
                    <td class="num">{{ number_format($row['taxable_base'], 2) }}</td>
                    <td class="num">{{ number_format($row['tax_total'], 2) }}</td>
                    <td class="num">{{ number_format($row['total_amount'], 2) }}</td>
                    <td class="num">{{ number_format($row['credit_notes_total'], 2) }}</td>
                    <td class="num">{{ number_format($row['debit_notes_total'], 2) }}</td>
                    <td class="num">{{ number_format($row['retentions_total'], 2) }}</td>
                    <td class="num">{{ number_format($row['net_total'], 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="13">No hay ventas en el rango seleccionado.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>