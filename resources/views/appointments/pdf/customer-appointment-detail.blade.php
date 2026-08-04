<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Detalle de cita</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111827; }
        h1, h2, h3 { margin: 0 0 8px 0; }
        .muted { color: #6b7280; }
        .row { margin-bottom: 10px; }
        .box { border: 1px solid #d1d5db; border-radius: 8px; padding: 10px; margin-bottom: 10px; }
        table { width: 100%; border-collapse: collapse; }
        th, td { border: 1px solid #d1d5db; padding: 6px; vertical-align: top; }
        th { background: #f3f4f6; font-size: 11px; text-transform: uppercase; }
    </style>
</head>
<body>
    <h2>Control de cita #{{ (int) $appointment->id }}</h2>
    <div class="muted row">sede: {{ $tenant->name ?? 'Shopix' }}</div>

    <div class="box">
        <div><strong>Cliente:</strong> {{ $appointment->customer->name ?? $appointment->contact_name ?? 'Cliente' }}</div>
        <div><strong>Teléfono:</strong> {{ $appointment->customer->phone_number ?? $appointment->contact_phone ?? 'N/A' }}</div>
        <div><strong>Profesional:</strong> {{ $appointment->assignedUser->name ?? 'Profesional' }}</div>
        <div><strong>Fecha y hora:</strong> {{ optional($appointment->starts_at)->format('d/m/Y H:i') }} - {{ optional($appointment->ends_at)->format('H:i') }}</div>
        <div><strong>Estado:</strong> {{ $appointment->status_label }} | <strong>Pago:</strong> {{ $appointment->payment_status_label }}</div>
        <div><strong>Total cita:</strong> {{ number_format((float) $appointmentTotal, 2) }} USD {{ $bsRate > 0 ? '| Bs ' . number_format((float) $appointmentTotal * (float) $bsRate, 2) : '' }}</div>
    </div>

    <div class="box">
        <h3>Servicios realizados</h3>
        <table>
            <thead>
                <tr>
                    <th>Servicio</th>
                    <th>Precio USD</th>
                </tr>
            </thead>
            <tbody>
                @php
                    $services = $appointment->serviceItems->pluck('service')->filter()->values();
                    if ($services->isEmpty() && $appointment->service) {
                        $services = collect([$appointment->service]);
                    }
                @endphp
                @forelse($services as $service)
                    <tr>
                        <td>{{ $service->display_name ?? $service->name ?? 'Servicio' }}</td>
                        <td>{{ number_format((float) ($service->price ?? 0), 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="2">Sin servicios registrados</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="box">
        <h3>Items / consumos</h3>
        <table>
            <thead>
                <tr>
                    <th>Item</th>
                    <th>Cantidad</th>
                    <th>Monto USD</th>
                </tr>
            </thead>
            <tbody>
                @forelse($appointment->consumptions as $consumption)
                    <tr>
                        <td>{{ $consumption->variant->product->display_name ?? 'Item' }}{{ !empty($consumption->variant->size) ? ' · ' . $consumption->variant->size : '' }}</td>
                        <td>{{ number_format((float) ($consumption->quantity ?? 0), 2) }}</td>
                        <td>{{ number_format((float) ($consumption->amount ?? 0), 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="3">Sin consumos registrados</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="box">
        <h3>Anotaciones</h3>
        @php($cleanNotes = trim((string) preg_replace('/^\[APPOINTMENT_PAYMENT_META\].*$/m', '', (string) ($appointment->notes ?? ''))))
        <div>{{ !empty($cleanNotes) ? $cleanNotes : 'Sin anotaciones.' }}</div>
    </div>

    <div class="box">
        <h3>Evidencias cargadas</h3>
        <div>Total imágenes: {{ $appointment->images->count() }}</div>
        @if($appointment->images->isNotEmpty())
            @foreach($appointment->images as $image)
                <div style="margin-top:6px;">
                    • {{ $image->caption ?: 'Sin etiqueta' }}
                    ({{ optional($image->created_at)->format('d/m/Y H:i') }})
                    {{ $image->uploadedBy->name ? ' - ' . $image->uploadedBy->name : '' }}
                </div>
            @endforeach
            <div class="muted" style="margin-top:8px;">Nota: las miniaturas se consultan desde el módulo de control en pantalla.</div>
        @endif
    </div>
</body>
</html>
