<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Comprobante de Pago #{{ $payroll->id }}</title>
  <style>
    @page {
      margin: 5mm;
      padding: 0;
    }

    * { box-sizing: border-box; }
    body {
      margin: 0;
      padding: 0;
      font-family: Arial, Helvetica, sans-serif;
      color: #111;
      background: #fff;
    }
    .receipt-sheet {
      display: flex;
      flex-direction: column;
      gap: 0;
    }
    .receipt-copy {
      border: 1px solid #2f2f2f;
      padding: 0;
      margin: 0;
      min-height: 100vh;
      page-break-after: always;
      position: relative;
    }
    .receipt-copy-inner {
      padding: 5mm;
      min-height: calc(100vh - 10mm);
      display: flex;
      flex-direction: column;
    }
    .receipt-copy:last-child {
      page-break-after: auto;
    }
    .receipt-header {
      text-align: center;
      font-weight: 700;
      letter-spacing: 0.4px;
      margin: 0 0 4mm 0;
      padding-top: 4mm;
    }
    .meta {
      display: grid;
      grid-template-columns: 1fr 1fr;
      gap: 3mm 8mm;
      margin-bottom: 6mm;
      font-size: 12px;
    }
    .meta strong { display: inline-block; min-width: 110px; }
    .table {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 6mm;
      font-size: 12px;
    }
    .table th,
    .table td {
      border-bottom: 1px solid #d8d8d8;
      padding: 5px 4px;
      vertical-align: top;
    }
    .table th {
      text-align: left;
      width: 45%;
      font-weight: 700;
    }
    .items-table {
      width: 100%;
      border-collapse: collapse;
      margin: 4mm 0;
      font-size: 12px;
    }
    .items-table th,
    .items-table td {
      border: 1px solid #dcdcdc;
      padding: 4px;
      text-align: left;
    }
    .items-table th {
      background: #f7f7f7;
      font-weight: 700;
    }
    .reason {
      margin-top: 3mm;
      font-size: 12px;
      white-space: pre-wrap;
      min-height: 12mm;
      border: 1px solid #dfdfdf;
      padding: 3mm;
      border-radius: 4px;
    }
    .signatures {
      margin-top: auto;
      padding-top: 10mm;
      width: 100%;
      border-collapse: separate;
      border-spacing: 12mm 0;
      table-layout: fixed;
      font-size: 12px;
    }
    .sign-box {
      width: 50%;
      text-align: center;
      vertical-align: bottom;
    }
    .sign-line {
      border-top: 1px solid #333;
      margin-top: 10mm;
      padding-top: 2mm;
      width: 100%;
    }
    .copy-label {
      position: absolute;
      top: 3mm;
      right: 5mm;
      font-size: 10px;
      color: #444;
    }
    .print-actions {
      margin-bottom: 8mm;
      display: flex;
      justify-content: flex-end;
      gap: 8px;
    }
    .btn {
      border: 1px solid #222;
      background: #fff;
      color: #111;
      border-radius: 4px;
      padding: 6px 10px;
      font-size: 12px;
      cursor: pointer;
    }
    @media print {
      .print-actions { display: none; }
      body { padding: 0; margin: 0; }
      .receipt-sheet { gap: 0; }
      .receipt-copy {
        border: 1px solid #2f2f2f;
        min-height: 100vh;
        page-break-inside: avoid;
        page-break-after: always;
      }
      .receipt-copy-inner {
        padding: 5mm;
        min-height: calc(100vh - 10mm);
      }
      .receipt-copy:last-child {
        page-break-after: auto;
      }
    }
  </style>
</head>
<body>
  @if(empty($forPdf))
    <div class="print-actions">
      <button class="btn" onclick="window.print()">Imprimir</button>
    </div>
  @endif

  @php
    $paymentTypeLabels = [
      'daily' => 'Diario',
      'weekly' => 'Semanal',
      'fortnightly' => 'Quincenal',
      'monthly' => 'Mensual',
      'package' => 'Paquete',
      'contract' => 'Contrato',
    ];
    $paymentTypeLabel = $paymentTypeLabels[$payroll->payment_type] ?? strtoupper((string) $payroll->payment_type);
    $memberName = $payroll->teamMember->full_name ?? 'No especificado';
    $projectName = $payroll->project->name ?? 'No especificado';
    $currency = $payroll->currency_code ?: 'USD';
    $amountBase = (float) ($payroll->amount ?? 0);
    $totalToPay = (float) ($payroll->total_to_pay ?? $payroll->amount ?? 0);
    $exchangeRateToBs = (float) ($payroll->exchange_rate_to_bs ?? 0);
    $amountBs = (float) ($payroll->amount_bs ?? 0);
    $totalToPayBs = (float) ($payroll->total_to_pay_bs ?? 0);
    if ($amountBs <= 0 && strtoupper((string) $currency) === 'BS') {
      $amountBs = $amountBase;
    } elseif ($amountBs <= 0 && $exchangeRateToBs > 0) {
      $amountBs = $amountBase * $exchangeRateToBs;
    }
    if ($totalToPayBs <= 0 && strtoupper((string) $currency) === 'BS') {
      $totalToPayBs = $totalToPay;
    } elseif ($totalToPayBs <= 0 && $exchangeRateToBs > 0) {
      $totalToPayBs = $totalToPay * $exchangeRateToBs;
    }
    $paidAt = optional($payroll->paid_at)->format('d/m/Y');
    $items = collect($payroll->items ?? []);
    $paymentsTotal = (float) $items->where('item_type', 'payment')->sum('amount');
    $deductionsTotal = (float) $items->where('item_type', 'deduction')->sum('amount');
  @endphp

  <div class="receipt-sheet">
    @for($copy = 1; $copy <= 2; $copy++)
      <section class="receipt-copy">
        <div class="copy-label">COPIA {{ $copy }}</div>
        <div class="receipt-copy-inner">
        <div class="receipt-header">COMPROBANTE DE PAGO</div>

        <div class="meta">
          <div><strong>Comprobante #:</strong> {{ $payroll->id }}</div>
          <div><strong>Fecha:</strong> {{ $paidAt }}</div>
          <div><strong>Nombre y apellido:</strong> {{ $memberName }}</div>
          <div><strong>Proyecto:</strong> {{ $projectName }}</div>
          <div><strong>Tipo de pago:</strong> {{ $paymentTypeLabel }}</div>
          <div><strong>Moneda:</strong> {{ $currency }}</div>
        </div>

        <table class="table" aria-label="Detalle de comprobante de pago">
          <tbody>
            <tr>
              <th>Total pagos</th>
              <td>
                {{ number_format($paymentsTotal > 0 ? $paymentsTotal : $amountBase, 2) }} {{ $currency }}
                @if($amountBs > 0)
                  <br><small>Bs {{ number_format($amountBs, 2) }}</small>
                @endif
              </td>
            </tr>
            <tr>
              <th>Total descuentos</th>
              <td>{{ number_format($deductionsTotal, 2) }} {{ $currency }}</td>
            </tr>
            <tr>
              <th>Total a pagar</th>
              <td>
                <strong>{{ number_format($totalToPay, 2) }} {{ $currency }}</strong>
                @if($totalToPayBs > 0)
                  <br><small><strong>Bs {{ number_format($totalToPayBs, 2) }}</strong></small>
                @endif
              </td>
            </tr>
          </tbody>
        </table>

        <table class="items-table" aria-label="Items del comprobante">
          <thead>
            <tr>
              <th>Tipo</th>
              <th>Razón</th>
              <th>Monto</th>
            </tr>
          </thead>
          <tbody>
            @forelse($items as $item)
              <tr>
                <td>{{ $item->item_type === 'deduction' ? 'Descuento' : 'Pago' }}</td>
                <td>{{ $item->description }}</td>
                <td>{{ number_format((float) $item->amount, 2) }} {{ $currency }}</td>
              </tr>
            @empty
              <tr>
                <td>Pago</td>
                <td>{{ trim((string) ($payroll->payment_reason ?? '')) !== '' ? $payroll->payment_reason : 'Pago base de nómina' }}</td>
                <td>{{ number_format($amountBase, 2) }} {{ $currency }}</td>
              </tr>
            @endforelse
          </tbody>
        </table>

        <table class="signatures" aria-label="Firmas del comprobante">
          <tr>
            <td cl ass="sign-box">
              <div class="sign-line">Firma empresa</div>
            </td>
            <td class="sign-box">
              <div class="sign-line">Firma trabajador</div>
            </td>
          </tr>
        </table>
        </div>
      </section>
    @endfor
  </div>
</body>
</html>
