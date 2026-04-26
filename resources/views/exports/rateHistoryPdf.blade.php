<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title>{{ $title ?? 'Historial de tasas' }}</title>
    <style>
        body { font-family: Arial, sans-serif; color: #111827; font-size: 12px; }
        h1 { font-size: 18px; margin-bottom: 4px; }
        p { margin: 0 0 10px; color: #4b5563; }
        table { width: 100%; border-collapse: collapse; margin-top: 16px; }
        th, td { border: 1px solid #d1d5db; padding: 8px; text-align: left; }
        th { background: #f3f4f6; font-size: 11px; text-transform: uppercase; }
    </style>
</head>
<body>
    <h1>{{ $title ?? 'Historial de tasas' }}</h1>
    <p>Exportado: {{ optional($exportedAt ?? now())->format('d/m/Y H:i') }}</p>

    <table>
        <thead>
            <tr>
                <th>Moneda</th>
                <th>Codigo</th>
                <th>Fecha BCV</th>
                <th>Tasa VES</th>
                <th>Registrada</th>
            </tr>
        </thead>
        <tbody>
            @forelse($entries as $entry)
                <tr>
                    <td>{{ $entry['currency_name'] }}</td>
                    <td>{{ $entry['currency_code'] }}</td>
                    <td>{{ $entry['rate_date']->format('d/m/Y') }}</td>
                    <td>{{ number_format((float) $entry['rate'], 4) }}</td>
                    <td>{{ optional($entry['created_at'])->format('d/m/Y H:i') ?? 'Sin registro' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="5">Sin historico registrado.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>