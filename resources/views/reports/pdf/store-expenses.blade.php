<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Gastos de Tienda</title>
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
    <h2>Reporte de Gastos de Tienda</h2>
    <div class="meta">Rango: {{ $summary['start_date']->format('d/m/Y') }} al {{ $summary['end_date']->format('d/m/Y') }}</div>
    <div class="meta">Categoría: <strong>{{ $summary['expense_category'] !== '' ? $summary['expense_category'] : 'Todas' }}</strong></div>
    <div class="meta">
        Gastos: <strong>{{ number_format($summary['expenses']) }}</strong> |
        Total egresado: <strong>{{ number_format($summary['total_amount'], 2) }} {{ $summary['currency_code'] ?? 'USD' }}</strong> |
        Total Bs: <strong>{{ number_format($summary['total_amount_bs'] ?? 0, 2) }} Bs</strong>
    </div>
    <table>
        <thead>
            <tr>
                <th>Fecha</th>
                <th>Concepto</th>
                <th>Categoria</th>
                <th>Proveedor</th>
                <th>Metodo</th>
                <th>Moneda</th>
                <th class="num">Monto original</th>
                <th class="num">Tasa Bs</th>
                <th class="num">Monto Bs</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $expense)
                <tr>
                    <td>{{ \Carbon\Carbon::parse($expense->spent_at)->format('d/m/Y') }}</td>
                    <td>{{ $expense->title }}</td>
                    <td>{{ $expense->category ?? 'N/A' }}</td>
                    <td>{{ $expense->provider_name ?? 'N/A' }}</td>
                    <td>{{ $expense->payment_method ?? 'N/A' }}</td>
                    <td>{{ strtoupper((string) ($expense->currency_code ?? 'USD')) }}</td>
                    <td class="num">{{ number_format((float) ($expense->amount_original ?? $expense->amount ?? 0), 4) }}</td>
                    <td class="num">{{ number_format((float) ($expense->exchange_rate_to_bs ?? 1), 4) }}</td>
                    <td class="num">{{ number_format((float) ($expense->amount_bs ?? $expense->amount ?? 0), 2) }}</td>
                    <td>{{ ucfirst((string) ($expense->status ?? 'paid')) }}</td>
                </tr>
            @empty
                <tr><td colspan="10">No hay gastos en el rango seleccionado.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>