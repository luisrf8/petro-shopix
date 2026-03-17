<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Reporte General por Modulos</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; }
        h2 { margin: 0 0 8px; }
        h3 { margin: 14px 0 6px; }
        .meta { margin-bottom: 12px; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; }
        th, td { border: 1px solid #ccc; padding: 6px; }
        th { background: #f3f3f3; text-align: left; }
        .num { text-align: right; }
    </style>
</head>
<body>
    <h2>Reporte General por Modulos del Sistema</h2>
    <div class="meta">Rango: {{ $summary['start_date']->format('d/m/Y') }} al {{ $summary['end_date']->format('d/m/Y') }}</div>
    <div class="meta">Generado: {{ $summary['generated_at']->format('d/m/Y H:i') }}</div>

    @foreach($modules as $module)
        <h3>{{ $module['name'] }}</h3>
        <table>
            <thead>
                <tr>
                    <th>Metrica</th>
                    <th class="num">Valor</th>
                </tr>
            </thead>
            <tbody>
                @foreach($module['metrics'] as $metricName => $metricValue)
                    <tr>
                        <td>{{ $metricName }}</td>
                        <td class="num">{{ is_numeric($metricValue) ? number_format((float) $metricValue, 2) : $metricValue }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endforeach
</body>
</html>
