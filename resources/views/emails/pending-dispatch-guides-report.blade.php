<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>Reporte de guías pendientes por facturar</title>
</head>
<body>
    <h2>Reporte {{ ucfirst((string) ($report['label'] ?? '')) }} de guías de despacho pendientes por facturar</h2>

    <p><strong>Tienda:</strong> {{ $tenant->name ?? 'N/A' }}</p>
    <p><strong>Periodo:</strong> {{ $report['start_date'] ?? 'N/A' }} al {{ $report['end_date'] ?? 'N/A' }}</p>
    <p><strong>Total pendiente:</strong> {{ $report['count'] ?? 0 }}</p>

    <table width="100%" cellpadding="6" cellspacing="0" border="1" style="border-collapse: collapse;">
        <thead>
            <tr>
                <th># Orden</th>
                <th>Cliente</th>
                <th>Fecha venta</th>
                <th>Entrega</th>
                <th>Guía</th>
                <th>Control</th>
                <th>Emitida</th>
            </tr>
        </thead>
        <tbody>
            @forelse(($report['orders'] ?? []) as $order)
                <tr>
                    <td>{{ $order['order_id'] ?? '' }}</td>
                    <td>{{ $order['customer_name'] ?? '' }}</td>
                    <td>{{ $order['sale_date'] ?? '' }}</td>
                    <td>{{ $order['delivery_type'] ?? '' }}</td>
                    <td>{{ $order['guide_number'] ?? '' }}</td>
                    <td>{{ $order['guide_control'] ?? '' }}</td>
                    <td>{{ $order['guide_issued_at_display'] ?? '' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="7">No hay guías pendientes por facturar en el periodo seleccionado.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>