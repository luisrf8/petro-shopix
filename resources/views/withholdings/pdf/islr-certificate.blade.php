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
  <h1>Comprobante de Retencion de ISLR</h1>
  <div class="muted">Emitido por Shopix - Decreto N 1.808</div>

  <table>
    <tr>
      <th>Comprobante</th>
      <td>{{ $withholding->certificate_number ?? 'N/A' }}</td>
      <th>Fecha</th>
      <td>{{ optional($withholding->retention_date)->format('d/m/Y') }}</td>
    </tr>
    <tr>
      <th>Proveedor</th>
      <td>{{ $withholding->provider->name ?? 'N/A' }}</td>
      <th>RIF</th>
      <td>{{ $withholding->provider->rif ?? 'N/A' }}</td>
    </tr>
    <tr>
      <th>Concepto</th>
      <td>{{ $withholding->concept->code ?? 'N/A' }} - {{ $withholding->concept->name ?? 'N/A' }}</td>
      <th>Pago</th>
      <td>{{ optional($withholding->payment_date)->format('d/m/Y') }}</td>
    </tr>
    <tr>
      <th>Factura</th>
      <td>{{ $withholding->invoice_number ?? ($withholding->accountPayable->invoice_number ?? 'N/A') }}</td>
      <th>Control</th>
      <td>{{ $withholding->control_number ?? ($withholding->accountPayable->control_number ?? 'N/A') }}</td>
    </tr>
  </table>

  <table>
    <thead>
      <tr>
        <th>Base</th>
        <th>Porcentaje (%)</th>
        <th>Sustraendo (UT)</th>
        <th>Sustraendo (Monto)</th>
        <th>Monto Retenido</th>
      </tr>
    </thead>
    <tbody>
      <tr>
        <td class="right">{{ number_format((float) $withholding->base_amount, 2) }} {{ $withholding->currency_code }}</td>
        <td class="right">{{ number_format((float) $withholding->rate_percent, 4) }}</td>
        <td class="right">{{ number_format((float) $withholding->sustraendo_ut, 4) }}</td>
        <td class="right">{{ number_format((float) $withholding->sustraendo_amount, 2) }} {{ $withholding->currency_code }}</td>
        <td class="right">{{ number_format((float) $withholding->retained_amount, 2) }} {{ $withholding->currency_code }}</td>
      </tr>
    </tbody>
  </table>

  <p class="muted">Comprobante emitido para soporte fiscal de retenciones de ISLR por pagos de servicios.</p>
</body>
</html>
