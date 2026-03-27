<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Clientes</title>
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
    <h2>Reporte de Clientes</h2>
    <div class="meta">Rango: {{ $summary['start_date']->format('d/m/Y') }} al {{ $summary['end_date']->format('d/m/Y') }}</div>
    <div class="meta">Estado de cliente: <strong>{{ $summary['customer_status'] === 'active' ? 'Activos' : ($summary['customer_status'] === 'inactive' ? 'Inactivos' : 'Todos') }}</strong></div>
    <div class="meta">
        Clientes: <strong>{{ number_format($summary['customers']) }}</strong> |
        Compras: <strong>{{ number_format($summary['orders']) }}</strong> |
        Cobrado: <strong>{{ number_format($summary['total_paid'], 2) }} USD</strong>
    </div>
    <table>
        <thead>
            <tr>
                <th>Cliente</th>
                <th>Correo</th>
                <th>Telefono</th>
                <th class="num">Compras</th>
                <th class="num">Cobrado</th>
                <th>Ultima compra</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $customer)
                <tr>
                    <td>{{ $customer->name }}</td>
                    <td>{{ $customer->email }}</td>
                    <td>{{ $customer->phone_number ?? 'N/A' }}</td>
                    <td class="num">{{ number_format($customer->orders_count) }}</td>
                    <td class="num">{{ number_format((float) ($customer->total_paid_amount ?? 0), 2) }}</td>
                    <td>{{ $customer->last_purchase_at ? \Carbon\Carbon::parse($customer->last_purchase_at)->format('d/m/Y') : 'N/A' }}</td>
                    <td>{{ $customer->is_active ? 'Activo' : 'Inactivo' }}</td>
                </tr>
            @empty
                <tr><td colspan="7">No hay clientes en el rango seleccionado.</td></tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>