<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Cotizacion #{{ $quotation->id }}</title>
  <style>
    body {
      font-family: DejaVu Sans, sans-serif;
      font-size: 11px;
      color: #1f2937;
    }

    .sheet {
      width: 100%;
    }

    .muted {
      color: #6b7280;
    }

    .header {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 12px;
    }

    .header td {
      border: none;
      padding: 0;
      vertical-align: top;
    }

    .header-logo-row td {
      padding-bottom: 4px;
    }

    .logo-row-cell {
      text-align: left;
    }

    .header-title-row td {
      padding-top: 2px;
      padding-bottom: 2px;
    }

    .header-info-row td {
      padding-top: 2px;
    }

    .store-title-cell {
      width: 70%;
      text-align: left;
      padding-right: 8px;
    }

    .quote-title-cell {
      width: 30%;
      text-align: right;
    }

    .logo-box {
      width: 145px;
      height: 80px;
      text-align: center;
      vertical-align: middle;
      overflow: hidden;
      display: inline-block;
      background: #fff;
    }

    .logo-box img {
      max-width: 100%;
      max-height: 100%;
      width: auto;
      height: auto;
      object-fit: contain;
      margin-top: 4px;
    }

    .company-name {
      font-size: 18px;
      font-weight: 700;
      margin: 0;
      text-transform: uppercase;
    }

    .company-line {
      margin: 1px 0;
      font-size: 10px;
    }

    .doc-title {
      font-size: 16px;
      font-weight: 700;
      letter-spacing: 0.8px;
      margin: 0;
      text-transform: uppercase;
    }

    .meta {
      width: 100%;
      border-collapse: collapse;
      margin-bottom: 10px;
    }

    .meta td {
      border: 1px solid #d1d5db;
      padding: 6px 8px;
      font-size: 10px;
    }

    .meta-label {
      width: 20%;
      background: #f9fafb;
    }

    .meta-value {
      width: 30%;
    }
    .items-table {
      width: 100%;
      border-collapse: collapse;
      margin-top: 2px;
    }

    .items-table th,
    .items-table td {
      border: 1px solid #1f2937;
      padding: 6px 6px;
      vertical-align: middle;
      font-size: 10px;
    }

    .items-table th {
      background: #88a9cf;
      color: #111827;
      text-transform: uppercase;
      font-size: 9px;
      letter-spacing: 0.3px;
      text-align: center;
    }

    .items-table th.currency-head {
      white-space: nowrap;
    }

    .items-table .description-cell {
      text-align: left;
    }

    .items-table .qty-cell,
    .items-table .amount-cell,
    .items-table .tax-cell,
    .items-table .total-cell {
      text-align: right;
      white-space: nowrap;
    }

    .items-table .tax-cell {
      font-size: 9px;
    }

    .totals {
      margin-top: 8px;
      width: 100%;
      border-collapse: collapse;
    }

    .totals td {
      border: 1px solid #1f2937;
      padding: 6px 8px;
      font-size: 10px;
    }

    .totals-label {
      text-align: right;
      font-weight: 700;
      background: #e5ecf6;
    }

    .totals-amount-usd,
    .totals-amount-bs {
      text-align: right;
    }

    .totals-amount-usd {
      width: 16%;
    }

    .totals-amount-bs {
      width: 18%;
      font-size: 9px !important;
    }

    .right {
      text-align: right;
    }

    .notes {
      margin-top: 12px;
      border: 1px solid #d1d5db;
      padding: 8px;
      font-size: 10px;
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
    $logoUrl = (string) ($billingLogoDataUri ?? '');
    $tenantRif = trim((string) ($tenant->rif ?? ''));
    $foreignCurrencyCode = in_array($quotationCurrencyCode, ['USD', 'EUR'], true) ? $quotationCurrencyCode : 'USD';
    $foreignCurrencySymbol = $foreignCurrencyCode === 'EUR' ? '€' : '$';

    $tenantPhoneCode = trim((string) ($tenant->phone_code ?? ''));
    $tenantPhoneNumber = trim((string) ($tenant->phone_number ?? ''));
    $tenantPhone = trim($tenantPhoneCode . ' ' . $tenantPhoneNumber);
    if ($tenantPhone === '') {
      $tenantPhone = '-';
    }

    $toBsAmount = function (float $amount) use ($quotationCurrencyCode, $quotationRateToBs) {
      if ($quotationCurrencyCode === 'BS') {
        return $amount;
      }

      if ($quotationRateToBs <= 0) {
        return null;
      }

      return $amount * $quotationRateToBs;
    };

    $toForeignAmount = function (float $amount) use ($quotationCurrencyCode, $foreignCurrencyCode, $quotationRateToBs, $usdRateToBs, $toBsAmount) {
      if ($quotationCurrencyCode === $foreignCurrencyCode) {
        return $amount;
      }

      $amountBs = $toBsAmount($amount);
      if (is_null($amountBs)) {
        return null;
      }

      if ($foreignCurrencyCode === 'USD') {
        return $usdRateToBs > 0 ? ($amountBs / $usdRateToBs) : null;
      }

      return $quotationRateToBs > 0 ? ($amountBs / $quotationRateToBs) : null;
    };

    $resolveItemTaxSummary = function ($item): string {
      $taxes = optional(optional($item)->product)->taxes;
      if (!$taxes || $taxes->isEmpty()) {
        return 'Exento';
      }

      return $taxes
        ->map(function ($tax) {
          $name = trim((string) ($tax->name ?? $tax->code ?? 'Impuesto'));
          $rate = is_null($tax->rate) ? null : (float) $tax->rate;
          if (is_null($rate)) {
            return $name;
          }

          return $name . ' ' . rtrim(rtrim(number_format($rate, 2, '.', ''), '0'), '.') . '%';
        })
        ->implode(', ');
    };

    $resolveItemTaxRatePercent = function ($item): float {
      $taxes = optional(optional($item)->product)->taxes;
      if (!$taxes || $taxes->isEmpty()) {
        return 0.0;
      }

      return (float) $taxes->sum(function ($tax) {
        return (float) ($tax->rate ?? 0);
      });
    };

    $estimatedTaxTotalQuote = 0.0;
  @endphp

  <div class="sheet">
    <table class="header">
      <tr class="header-logo-row">
        <td class="logo-row-cell" colspan="2">
          <div class="logo-box">
            <img src="{{ $logoUrl }}" alt="Logo de facturación">
          </div>
        </td>
      </tr>
      <tr class="header-title-row">
        <td class="store-title-cell">
          <p class="company-name">{{ strtoupper((string) ($tenant->name ?? 'TIENDA')) }}</p>
        </td>
        <td class="quote-title-cell">
          <p class="doc-title">COTIZACIÓN #{{ $quotation->id }}</p>
        </td>
      </tr>
      <tr class="header-info-row">
        <td>
          @if($tenantRif !== '')
            <p class="company-line"><strong>RIF:</strong> {{ strtoupper($tenantRif) }}</p>
          @endif
          <p class="company-line"><strong>Dirección:</strong> {{ (string) ($tenant->address ?? '-') }}</p>
          <p class="company-line"><strong>Teléfono:</strong> {{ $tenantPhone }}</p>
          <p class="company-line"><strong>Email:</strong> {{ (string) ($tenant->email ?? '-') }}</p>
        </td>
        <td class="quote-title-cell">
          <p class="company-line"><strong>Fecha:</strong> {{ optional($quotation->created_at)->format('d/m/Y') ?: now()->format('d/m/Y') }}</p>
          <p class="company-line"><strong>Validez:</strong> {{ optional($quotation->valid_until)->format('d/m/Y') ?: '-' }}</p>
        </td>
      </tr>
    </table>

    <table class="meta">
      <tr>
        <td class="meta-label">Título</td>
        <td class="meta-value">{{ (string) ($quotation->title ?? '-') }}</td>
        <td class="meta-label">Tipo</td>
        <td class="meta-value">{{ $quotation->type === 'supplier_request' ? 'Solicitud a proveedor' : 'Cotización a cliente' }}</td>
      </tr>
      <tr>
        <td class="meta-label">Cliente</td>
        <td class="meta-value">{{ $quotation->customer_name ?: '-' }}</td>
        <td class="meta-label">Proveedor</td>
        <td class="meta-value">{{ $quotation->provider_name ?: optional($quotation->provider)->name ?: '-' }}</td>
      </tr>
      <tr>
        <td class="meta-label">Moneda</td>
        <td class="meta-value">{{ $quotationCurrencyCode }}</td>
        <td class="meta-label">Tasa USD-Bs</td>
        <td class="meta-value">{{ $usdRateToBs > 0 ? number_format($usdRateToBs, 4) : 'N/D' }}</td>
      </tr>
    </table>

    <table class="items-table">
      <colgroup>
        <col style="width:4%;">
        <col style="width:7%;">
        <col style="width:27%;">
        <col style="width:9%;">
        <col style="width:6%;">
        <col style="width:9%;">
        <col style="width:9%;">
        <col style="width:16%;">
        <col style="width:18%;">
      </colgroup>
      <thead>
        <tr>
          <th>Item</th>
          <th>Cant.</th>
          <th>Descripción</th>
          <th>Impuesto</th>
          <th>Imp. monto</th>
          <th class="currency-head">Precio unit&nbsp;{{ $foreignCurrencySymbol }}</th>
          <th class="currency-head">Precio unit&nbsp;Bs</th>
          <th class="currency-head">Total&nbsp;{{ $foreignCurrencySymbol }}</th>
          <th class="currency-head">Total&nbsp;Bs</th>
        </tr>
      </thead>
      <tbody>
        @forelse($quotation->items as $index => $item)
          @php
            $lineTotalQuote = (float) ($item->total ?? 0);
            $lineTaxRatePercent = $resolveItemTaxRatePercent($item);
            $lineTaxQuote = round($lineTotalQuote * ($lineTaxRatePercent / 100), 4);
            $estimatedTaxTotalQuote += $lineTaxQuote;

            $lineUnitForeign = $toForeignAmount((float) $item->unit_price);
            $lineUnitBs = $toBsAmount((float) $item->unit_price);
            $lineTotalForeign = $toForeignAmount($lineTotalQuote);
            $lineTotalBs = $toBsAmount($lineTotalQuote);
          @endphp
          <tr>
            <td class="right">{{ $index + 1 }}</td>
            <td class="qty-cell">{{ number_format((float) $item->quantity, 2) }}</td>
            <td class="description-cell">
              @php
                $descriptionText = trim((string) ($item->description ?? ''));
                $productLine = trim((string) ((optional($item->product)->name ?: '')) . (optional($item->variant)->size ? (' - ' . optional($item->variant)->size) : ''));
              @endphp
              {{ $descriptionText !== '' ? $descriptionText : ($productLine !== '' ? $productLine : '-') }}
              @if($descriptionText !== '' && $productLine !== '' && strcasecmp($descriptionText, $productLine) !== 0)
                <div class="muted">{{ $productLine }}</div>
              @endif
              @if((float) ($item->discount_percent ?? 0) > 0)
                <div class="muted">Desc: {{ number_format((float) $item->discount_percent, 2) }}%</div>
              @endif
            </td>
            <td class="tax-cell">{{ $resolveItemTaxSummary($item) }}</td>
            <td class="amount-cell">{{ number_format($lineTaxQuote, 2) }}</td>
            <td class="amount-cell">{{ is_null($lineUnitForeign) ? 'N/D' : ($foreignCurrencySymbol . ' ' . number_format((float) $lineUnitForeign, 2)) }}</td>
            <td class="amount-cell">{{ is_null($lineUnitBs) ? 'N/D' : ('Bs ' . number_format((float) $lineUnitBs, 2)) }}</td>
            <td class="total-cell">{{ is_null($lineTotalForeign) ? 'N/D' : ($foreignCurrencySymbol . ' ' . number_format((float) $lineTotalForeign, 2)) }}</td>
            <td class="total-cell">{{ is_null($lineTotalBs) ? 'N/D' : ('Bs ' . number_format((float) $lineTotalBs, 2)) }}</td>
          </tr>
        @empty
          <tr>
            <td colspan="9" class="right">Sin items</td>
          </tr>
        @endforelse

        <tr>
          <td colspan="7" class="totals-label">SUBTOTAL</td>
          <td class="totals-amount-usd">{{ is_null($toForeignAmount((float) $quotation->subtotal)) ? 'N/D' : ($foreignCurrencySymbol . ' ' . number_format((float) $toForeignAmount((float) $quotation->subtotal), 2)) }}</td>
          <td class="totals-amount-bs">{{ is_null($toBsAmount((float) $quotation->subtotal)) ? 'N/D' : ('Bs ' . number_format((float) $toBsAmount((float) $quotation->subtotal), 2)) }}</td>
        </tr>
        <tr>
          <td colspan="7" class="totals-label">DESCUENTOS</td>
          <td class="totals-amount-usd">{{ is_null($toForeignAmount((float) $quotation->discount_amount)) ? 'N/D' : ($foreignCurrencySymbol . ' ' . number_format((float) $toForeignAmount((float) $quotation->discount_amount), 2)) }}</td>
          <td class="totals-amount-bs">{{ is_null($toBsAmount((float) $quotation->discount_amount)) ? 'N/D' : ('Bs ' . number_format((float) $toBsAmount((float) $quotation->discount_amount), 2)) }}</td>
        </tr>
        <tr>
          <td colspan="7" class="totals-label">IMPUESTOS (EST.)</td>
          <td class="totals-amount-usd">{{ is_null($toForeignAmount((float) $estimatedTaxTotalQuote)) ? 'N/D' : ($foreignCurrencySymbol . ' ' . number_format((float) $toForeignAmount((float) $estimatedTaxTotalQuote), 2)) }}</td>
          <td class="totals-amount-bs">{{ is_null($toBsAmount((float) $estimatedTaxTotalQuote)) ? 'N/D' : ('Bs ' . number_format((float) $toBsAmount((float) $estimatedTaxTotalQuote), 2)) }}</td>
        </tr>
        <tr>
          <td colspan="7" class="totals-label">TOTAL</td>
          <td class="totals-amount-usd">{{ is_null($toForeignAmount((float) $quotation->total_amount)) ? 'N/D' : ($foreignCurrencySymbol . ' ' . number_format((float) $toForeignAmount((float) $quotation->total_amount), 2)) }}</td>
          <td class="totals-amount-bs">{{ is_null($toBsAmount((float) $quotation->total_amount)) ? 'N/D' : ('Bs ' . number_format((float) $toBsAmount((float) $quotation->total_amount), 2)) }}</td>
        </tr>
      </tbody>
    </table>

    @if($quotation->notes)
      <div class="notes">
        <strong>Notas:</strong>
        <div>{{ $quotation->notes }}</div>
      </div>
    @endif
  </div>
</body>
</html>
