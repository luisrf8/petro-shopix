<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Cotizacion #{{ $quotation->id }}</title>
  <style>
    body {
      font-family: DejaVu Sans, sans-serif;
      font-size: 12px;
      color: #222;
    }
    h1 {
      font-size: 20px;
      margin: 0 0 6px;
    }
    .muted {
      color: #555;
    }
    .header {
      margin-bottom: 18px;
      border-bottom: 1px solid #ddd;
      padding-bottom: 12px;
    }
    .meta {
      margin-bottom: 14px;
    }
    .meta p {
      margin: 2px 0;
    }
    table {
      width: 100%;
      border-collapse: collapse;
    }
    th, td {
      border: 1px solid #ddd;
      padding: 7px;
      vertical-align: top;
    }
    th {
      background: #f5f5f5;
      text-align: left;
    }
    .right {
      text-align: right;
    }
    .totals {
      margin-top: 12px;
      width: 44%;
      margin-left: auto;
    }
  </style>
</head>
<body>
  @php
    $quotationCurrencyCode = strtoupper(trim((string) ($quotationCurrencyCode ?? ($quotation->currency_code ?? 'USD'))));
    if (in_array($quotationCurrencyCode, ['VES', 'VED', 'VEF', 'BSD'], true)) {
      $quotationCurrencyCode = 'BS';
    }

    $quotationRateToBs = (float) ($quotationRateToBs ?? 0);
    $usdRateToBs = (float) ($usdRateToBs ?? 0);

    $toBsAmount = function (float $amount) use ($quotationCurrencyCode, $quotationRateToBs) {
      if ($quotationCurrencyCode === 'BS') {
        return $amount;
      }

      if ($quotationRateToBs <= 0) {
        return null;
      }

      return $amount * $quotationRateToBs;
    };

    $toUsdAmount = function (float $amount) use ($quotationCurrencyCode, $usdRateToBs, $toBsAmount) {
      if ($quotationCurrencyCode === 'USD') {
        return $amount;
      }

      $amountBs = $toBsAmount($amount);
      if (is_null($amountBs) || $usdRateToBs <= 0) {
        return null;
      }

      return $amountBs / $usdRateToBs;
    };

    $formatDualAmount = function (float $amount) use ($toUsdAmount, $toBsAmount) {
      $usdAmount = $toUsdAmount($amount);
      $bsAmount = $toBsAmount($amount);

      $usdText = is_null($usdAmount) ? 'N/D' : (number_format($usdAmount, 2) . ' $');
      $bsText = is_null($bsAmount) ? 'N/D' : ('Bs ' . number_format($bsAmount, 2));

      return $usdText . ' / ' . $bsText;
    };
  @endphp
  <div class="header">
    <h1>Cotizacion #{{ $quotation->id }}</h1>
    <div class="muted">{{ $quotation->title }}</div>
  </div>

  <div class="meta">
    <p><strong>Tipo:</strong> {{ $quotation->type === 'supplier_request' ? 'Solicitud a proveedor' : 'Cotizacion a cliente' }}</p>
    <p><strong>Estado:</strong> {{ strtoupper((string) $quotation->status) }}</p>
    <p><strong>Cliente:</strong> {{ $quotation->customer_name ?: '-' }}</p>
    <p><strong>Proveedor:</strong> {{ $quotation->provider_name ?: optional($quotation->provider)->name ?: '-' }}</p>
    <p><strong>Valida hasta:</strong> {{ optional($quotation->valid_until)->format('d/m/Y') ?: '-' }}</p>
    <p><strong>Moneda base de la cotización:</strong> {{ $quotationCurrencyCode }}</p>
    <p><strong>Tasa USD - Bs:</strong> {{ $usdRateToBs > 0 ? number_format($usdRateToBs, 4) : 'N/D' }}</p>
  </div>

  <table>
    <thead>
      <tr>
        <th>Descripcion</th>
        <th class="right">Cantidad</th>
        <th class="right">Precio Unit. (USD / Bs)</th>
        <th class="right">Desc. %</th>
        <th class="right">Total (USD / Bs)</th>
      </tr>
    </thead>
    <tbody>
      @forelse($quotation->items as $item)
        <tr>
          <td>
            @php
              $descriptionText = trim((string) ($item->description ?? ''));
              $productLine = trim((string) ((optional($item->product)->name ?: '')) . (optional($item->variant)->size ? (' - ' . optional($item->variant)->size) : ''));
            @endphp

            {{ $descriptionText !== '' ? $descriptionText : ($productLine !== '' ? $productLine : '-') }}

            @if($descriptionText !== '' && $productLine !== '' && strcasecmp($descriptionText, $productLine) !== 0)
              <div class="muted">
                {{ $productLine }}
              </div>
            @endif
          </td>
          <td class="right">{{ number_format((float) $item->quantity, 2) }}</td>
          <td class="right">{{ $formatDualAmount((float) $item->unit_price) }}</td>
          <td class="right">{{ number_format((float) $item->discount_percent, 2) }}</td>
          <td class="right">{{ $formatDualAmount((float) $item->total) }}</td>
        </tr>
      @empty
        <tr>
          <td colspan="5" class="right">Sin items</td>
        </tr>
      @endforelse
    </tbody>
  </table>

  <table class="totals">
    <tr>
      <th></th>
      <th class="right">USD</th>
      <th class="right">Bs</th>
    </tr>
    <tr>
      <th>Subtotal</th>
      <td class="right">{{ is_null($toUsdAmount((float) $quotation->subtotal)) ? 'N/D' : number_format((float) $toUsdAmount((float) $quotation->subtotal), 2) }}</td>
      <td class="right">{{ is_null($toBsAmount((float) $quotation->subtotal)) ? 'N/D' : number_format((float) $toBsAmount((float) $quotation->subtotal), 2) }}</td>
    </tr>
    <tr>
      <th>Descuentos</th>
      <td class="right">{{ is_null($toUsdAmount((float) $quotation->discount_amount)) ? 'N/D' : number_format((float) $toUsdAmount((float) $quotation->discount_amount), 2) }}</td>
      <td class="right">{{ is_null($toBsAmount((float) $quotation->discount_amount)) ? 'N/D' : number_format((float) $toBsAmount((float) $quotation->discount_amount), 2) }}</td>
    </tr>
    <tr>
      <th>Total</th>
      <td class="right"><strong>{{ is_null($toUsdAmount((float) $quotation->total_amount)) ? 'N/D' : number_format((float) $toUsdAmount((float) $quotation->total_amount), 2) }}</strong></td>
      <td class="right"><strong>{{ is_null($toBsAmount((float) $quotation->total_amount)) ? 'N/D' : number_format((float) $toBsAmount((float) $quotation->total_amount), 2) }}</strong></td>
    </tr>
  </table>

  @if($quotation->notes)
    <div style="margin-top: 14px;">
      <strong>Notas:</strong>
      <p>{{ $quotation->notes }}</p>
    </div>
  @endif
</body>
</html>
