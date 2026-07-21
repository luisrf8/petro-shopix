<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Reporte de citas por cliente</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111827; }
        h1, h2, h3 { margin: 0 0 8px 0; }
        .muted { color: #6b7280; }
        .mb-8 { margin-bottom: 8px; }
        .mb-12 { margin-bottom: 12px; }
        .mb-16 { margin-bottom: 16px; }
        .summary-box { border: 1px solid #d1d5db; padding: 10px; border-radius: 8px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 6px; vertical-align: top; }
        th { background: #f3f4f6; font-size: 11px; text-transform: uppercase; }
        .small { font-size: 11px; }
    </style>
</head>
<body>
    <h2>Control de clientes / citas</h2>
    <div class="mb-8 muted">Tienda: {{ $tenant->name ?? 'Shopix' }}</div>
    <div class="mb-12"><strong>Cliente:</strong> {{ $customer->name ?? 'Cliente' }}{{ !empty($customer->phone_number) ? ' · ' . $customer->phone_number : '' }}</div>

    <div class="summary-box mb-16">
        <div><strong>Citas registradas:</strong> {{ (int) ($summary['appointments_count'] ?? 0) }}</div>
        <div><strong>Total citas USD:</strong> {{ number_format((float) ($summary['appointments_total_usd'] ?? 0), 2) }}</div>
        <div><strong>Total citas Bs:</strong> {{ $bsRate > 0 ? number_format((float) ($summary['appointments_total_usd'] ?? 0) * (float) $bsRate, 2) : 'N/A' }}</div>
        <div><strong>Evidencias cargadas:</strong> {{ (int) ($summary['evidences_count'] ?? 0) }}</div>
        <div class="small muted">Generado: {{ now()->format('d/m/Y H:i') }}</div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 14%;">Fecha</th>
                <th style="width: 18%;">Profesional</th>
                <th style="width: 25%;">Servicios</th>
                <th style="width: 17%;">Items</th>
                <th style="width: 10%;">Total USD</th>
                <th style="width: 8%;">Estado</th>
                <th style="width: 8%;">Evidencias</th>
            </tr>
        </thead>
        <tbody>
            @forelse($appointments as $appointment)
                @php
                    $services = $appointment->serviceItems->pluck('service')->filter()->values();
                    if ($services->isEmpty() && $appointment->service) {
                        $services = collect([$appointment->service]);
                    }
                    $serviceSubtotal = round((float) $services->sum(fn($s) => (float) ($s->price ?? 0)), 2);
                    $itemsSubtotal = round((float) $appointment->consumptions->sum('amount'), 2);
                    $total = $serviceSubtotal + $itemsSubtotal;
                @endphp
                <tr>
                    <td>{{ optional($appointment->starts_at)->format('d/m/Y H:i') }}</td>
                    <td>{{ $appointment->assignedUser->name ?? 'Profesional' }}</td>
                    <td>
                        @foreach($services as $service)
                            • {{ $service->display_name ?? $service->name ?? 'Servicio' }}<br>
                        @endforeach
                    </td>
                    <td>
                        @forelse($appointment->consumptions as $consumption)
                            • {{ $consumption->variant->product->name ?? 'Item' }} x{{ number_format((float) ($consumption->quantity ?? 0), 2) }}<br>
                        @empty
                            Sin items
                        @endforelse
                    </td>
                    <td>{{ number_format($total, 2) }}</td>
                    <td>{{ $appointment->status_label }}</td>
                    <td>{{ $appointment->images->count() }}</td>
                </tr>
                @php($cleanNotes = trim((string) preg_replace('/^\[APPOINTMENT_PAYMENT_META\].*$/m', '', (string) ($appointment->notes ?? ''))))
                @if(!empty($cleanNotes))
                    <tr>
                        <td colspan="7" class="small"><strong>Anotaciones:</strong> {{ $cleanNotes }}</td>
                    </tr>
                @endif
            @empty
                <tr>
                    <td colspan="7">Sin citas registradas para este cliente.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
