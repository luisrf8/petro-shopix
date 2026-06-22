<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8">
  <style>
    body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #111; }
    h1 { font-size: 16px; margin: 0 0 8px; }
    .muted { color: #555; }
    table { width: 100%; border-collapse: collapse; margin-top: 10px; }
    th, td { border: 1px solid #aaa; padding: 6px; text-align: left; }
    .right { text-align: right; }
  </style>
</head>
<body>
  <h1>Comprobante de Retencion de IVA</h1>
  <div class="muted">Emitido por Shopix - Cumplimiento SENIAT</div>

  <table>
    <tr>
      <th>Comprobante</th>
      <td>{{ $retention->certificate_number ?? 'N/A' }}</td>
      <th>Fecha</th>
      <td>{{ optional($retention->retention_date)->format('d/m/Y') }}</td>
    </tr>
    <tr>
      <th>Proveedor</th>
      <td>{{ $retention->provider->name ?? 'N/A' }}</td>
      <th>RIF</th>
      <td>{{ $retention->provider->rif ?? 'N/A' }}</td>
    </tr>
    <tr>
      <th>Factura</th>
      <td>{{ $retention->invoice_number ?? 'N/A' }}</td>
      <th>Control</th>
      <td>{{ $retention->control_number ?? 'N/A' }}</td>
    </tr>
  </table>

  <table>
    <thead>
      <tr>
        <th>Base Imponible</th>
        <th>Alicuota Retencion (%)</th>
        <th>IVA Causado</th>
        <th>Monto Retenido</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td class="right">{{ number_format((float) $retention->taxable_base, 2) }} {{ $retention->currency_code }}</td>
        <td class="right">{{ number_format((float) $retention->retention_rate, 2) }}</td>
        <td class="right">{{ number_format((float) $retention->tax_amount, 2) }} {{ $retention->currency_code }}</td>
        <td class="right">{{ number_format((float) $retention->retained_amount, 2) }} {{ $retention->currency_code }}</td>
      </tr>
    </tbody>
  </table>

  <p class="muted">Este comprobante se genera para fines fiscales conforme a la Providencia Administrativa SNAT/2015/0049.</p>
</body>
</html>
