<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; }
    h1 { font-size: 16px; margin: 0 0 8px; }
    .muted { color: #555; }
    .mt { margin-top: 12px; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    th, td { border: 1px solid #aaa; padding: 6px; text-align: left; vertical-align: top; }
    .right { text-align: right; }
  </style>
</head>
<body>
  <h1>Comprobante de Retencion de Venta</h1>
  <div class="muted">Generado por Shopix con datos sincronizados hacia HKA</div>

  <table>
    <tr>
      <th>Comprobante</th>
      <td>{{ $retention->certificate_number ?? $retention->internal_number ?? 'N/A' }}</td>
      <th>Fecha</th>
      <td>{{ optional($retention->retention_date)->format('d/m/Y') ?? 'N/A' }}</td>
    </tr>
    <tr>
      <th>Cliente</th>
      <td>{{ $order->user->name ?? 'N/A' }}</td>
      <th>Documento fiscal</th>
      <td>{{ $referenceDocument->numero_documento ?? 'N/A' }}</td>
    </tr>
    <tr>
      <th>Control fiscal</th>
      <td>{{ $referenceDocument->numero_control ?? 'N/A' }}</td>
      <th>Tipo de retencion</th>
      <td>{{ strtoupper((string) ($retention->retention_type ?? 'N/A')) }}</td>
    </tr>
    <tr>
      <th>Orden Shopix</th>
      <td>{{ $order->id }}</td>
      <th>Estatus</th>
      <td>{{ ucfirst(str_replace('_', ' ', (string) ($retention->status ?? 'registered'))) }}</td>
    </tr>
  </table>

  <table class="mt">
    <thead>
      <tr>
        <th>Moneda de registro</th>
        <th>Base imponible</th>
        <th>Tasa (%)</th>
        <th>Monto retenido</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td>{{ strtoupper((string) ($retention->currency_code ?? 'N/A')) }}</td>
        <td class="right">{{ number_format((float) ($retention->taxable_base ?? 0), 2) }}</td>
        <td class="right">{{ number_format((float) ($retention->retention_rate ?? 0), 2) }}</td>
        <td class="right">{{ number_format((float) ($retention->retained_amount ?? 0), 2) }}</td>
      </tr>
    </tbody>
  </table>

  <table class="mt">
    <tr>
      <th>Respuesta HKA</th>
      <td>
        Codigo: {{ data_get($retention->response_payload, 'apply.codigo', 'N/A') }}<br>
        Mensaje: {{ data_get($retention->response_payload, 'apply.mensaje', 'Sin mensaje') }}
      </td>
    </tr>
    <tr>
      <th>Observaciones</th>
      <td>{{ $retention->notes ?: 'N/A' }}</td>
    </tr>
  </table>

  <p class="muted mt">Este comprobante PDF es una representacion interna de Shopix basada en la retencion registrada y la respuesta almacenada de HKA.</p>
</body>
</html>
