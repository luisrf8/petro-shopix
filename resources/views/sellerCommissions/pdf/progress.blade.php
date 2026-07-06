<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Avance mensual de comisiones</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 11px; color: #111; }
        h2 { margin: 0 0 8px; }
        .meta { margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ccc; padding: 6px; }
        th { background: #f3f3f3; text-align: left; }
        .num { text-align: right; }
    </style>
</head>
<body>
    <h2>Avance mensual de comisiones</h2>

    <div class="meta">
        Vendedor: <strong>{{ $summary['seller_name'] }}</strong><br>
        Rango: {{ $summary['month_start']->format('d/m/Y') }} al {{ $summary['month_end']->format('d/m/Y') }}
    </div>

    <div class="meta">
        Generado: <strong>{{ number_format((float) $summary['total_generated'], 2) }} {{ $summary['currency_code'] }}</strong> |
        Pendiente: <strong>{{ number_format((float) $summary['total_pending'], 2) }} {{ $summary['currency_code'] }}</strong> |
        Pagado: <strong>{{ number_format((float) $summary['total_paid'], 2) }} {{ $summary['currency_code'] }}</strong>
    </div>

    <table>
        <thead>
            <tr>
                <th># Venta</th>
                <th class="num">Base</th>
                <th class="num">Tasa</th>
                <th class="num">Comisión</th>
                <th>Estado</th>
                <th>Calculada</th>
            </tr>
        </thead>
        <tbody>
            @forelse($commissions as $commission)
                <tr>
                    <td>{{ $commission->sales_order_id }}</td>
                    <td class="num">{{ number_format((float) $commission->commission_base_amount, 2) }} {{ $commission->currency_code }}</td>
                    <td class="num">{{ number_format((float) $commission->commission_rate, 2) }}%</td>
                    <td class="num">{{ number_format((float) $commission->commission_amount, 2) }} {{ $commission->currency_code }}</td>
                    <td>{{ $commission->status === 'paid' ? 'Pagada' : 'Pendiente' }}</td>
                    <td>{{ optional($commission->calculated_at)->format('d/m/Y H:i') ?: optional($commission->created_at)->format('d/m/Y H:i') }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="6">No hay comisiones para el mes en curso.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
