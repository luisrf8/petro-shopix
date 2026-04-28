<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte de Gestion de Citas</title>
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
    <h2>Reporte de Gestion de Citas</h2>
    <div class="meta">Rango: {{ $summary['start_date']->format('d/m/Y') }} al {{ $summary['end_date']->format('d/m/Y') }}</div>
    <div class="meta">
        Citas: <strong>{{ number_format($summary['appointments']) }}</strong> |
        Programadas: <strong>{{ number_format($summary['scheduled']) }}</strong> |
        Confirmadas: <strong>{{ number_format($summary['confirmed']) }}</strong> |
        Completadas: <strong>{{ number_format($summary['completed']) }}</strong> |
        Canceladas: <strong>{{ number_format($summary['cancelled']) }}</strong> |
        Pago pendiente/parcial: <strong>{{ number_format($summary['pending_payment']) }}</strong>
    </div>
    <div class="meta">
        Precio servicios: <strong>{{ number_format($summary['total_service_price'], 2) }} {{ $summary['currency_code'] ?? 'USD' }}</strong> |
        Cobrado: <strong>{{ number_format($summary['total_paid'], 2) }} {{ $summary['currency_code'] ?? 'USD' }}</strong> |
        Pendiente: <strong>{{ number_format($summary['total_pending'], 2) }} {{ $summary['currency_code'] ?? 'USD' }}</strong>
    </div>

    <table>
        <thead>
            <tr>
                <th># Cita</th>
                <th>Servicio</th>
                <th>Profesional</th>
                <th>Cliente</th>
                <th>Fecha/Hora</th>
                <th>Estado</th>
                <th>Pago</th>
                <th class="num">Precio</th>
                <th class="num">Cobrado</th>
                <th class="num">Pendiente</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $appointment)
                <tr>
                    <td>{{ $appointment->id }}</td>
                    <td>{{ $appointment->service->display_name ?? $appointment->service->name ?? 'Servicio' }}</td>
                    <td>{{ $appointment->assignedUser->name ?? 'Profesional' }}</td>
                    <td>{{ $appointment->customer->name ?? 'Cliente' }}</td>
                    <td>{{ optional($appointment->starts_at)?->format('d/m/Y H:i') }}</td>
                    <td>{{ $appointment->status_label ?? ucfirst((string) ($appointment->status ?? 'scheduled')) }}</td>
                    <td>{{ $appointment->payment_status_label ?? ucfirst((string) ($appointment->payment_status ?? 'pending')) }}</td>
                    <td class="num">{{ number_format((float) ($appointment->service_price_report ?? 0), 2) }}</td>
                    <td class="num">{{ number_format((float) ($appointment->paid_amount_report ?? 0), 2) }}</td>
                    <td class="num">{{ number_format((float) ($appointment->pending_amount_report ?? 0), 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10">No hay citas en el rango seleccionado.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
