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
    <p><strong>Moneda:</strong> {{ $quotation->currency_code }}</p>
  </div>

  <table>
    <thead>
      <tr>
        <th>Descripcion</th>
        <th>Servicio</th>
        <th class="right">Cantidad</th>
        <th class="right">Precio Unit.</th>
        <th class="right">Desc. %</th>
        <th class="right">Total</th>
      </tr>
    </thead>
    <tbody>
      @forelse($quotation->items as $item)
        <tr>
          <td>
            {{ $item->description }}
            @if($item->product || $item->variant)
              <div class="muted">
                {{ optional($item->product)->name ?: 'Producto' }} {{ optional($item->variant)->size ? ('- ' . $item->variant->size) : '' }}
              </div>
            @endif
          </td>
          <td>{{ $item->service_name ?: '-' }}</td>
          <td class="right">{{ number_format((float) $item->quantity, 2) }}</td>
          <td class="right">{{ number_format((float) $item->unit_price, 2) }}</td>
          <td class="right">{{ number_format((float) $item->discount_percent, 2) }}</td>
          <td class="right">{{ number_format((float) $item->total, 2) }}</td>
        </tr>
      @empty
        <tr>
          <td colspan="6" class="right">Sin items</td>
        </tr>
      @endforelse
    </tbody>
  </table>

  <table class="totals">
    <tr>
      <th>Subtotal</th>
      <td class="right">{{ number_format((float) $quotation->subtotal, 2) }} {{ $quotation->currency_code }}</td>
    </tr>
    <tr>
      <th>Descuentos</th>
      <td class="right">{{ number_format((float) $quotation->discount_amount, 2) }} {{ $quotation->currency_code }}</td>
    </tr>
    <tr>
      <th>Total</th>
      <td class="right"><strong>{{ number_format((float) $quotation->total_amount, 2) }} {{ $quotation->currency_code }}</strong></td>
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
