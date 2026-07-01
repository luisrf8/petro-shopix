@extends('layouts.app')

@section('title', 'Cotizaciones')

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet">
<style>
  :root {
    --q-surface: #ffffff;
    --q-border: #dbe3f0;
    --q-ink: #0f172a;
    --q-muted: #64748b;
    --q-accent: #2563eb;
    --q-soft: #f8fbff;
  }

  .quotation-page-shell .card {
    border-color: var(--q-border);
  }

  .quotation-item-select2-wrap .select2-container {
    width: 100% !important;
  }

  .quotation-item-select2-wrap .select2-selection--single {
    min-height: 38px;
    border: 1px solid #d2d6da;
    border-radius: 0.375rem;
    display: flex;
    align-items: center;
  }

  .quotation-item-select2-wrap .select2-selection__rendered {
    line-height: 36px !important;
    font-size: 0.875rem;
  }

  .quotation-item-select2-wrap .select2-selection__arrow {
    height: 36px !important;
  }

  .quotation-item-select2-wrap .select2-container--default .select2-selection--single:focus,
  .quotation-item-select2-wrap .select2-container--default.select2-container--open .select2-selection--single {
    border-color: #596cff;
    box-shadow: 0 0 0 2px rgba(89, 108, 255, 0.15);
  }

  .quotation-form-shell .form-control,
  .quotation-form-shell .form-select {
    border-radius: 12px;
  }

  .quotation-form-shell label.form-label {
    font-weight: 700;
    color: #334155;
  }

  @media (max-width: 991.98px) {
    .quotation-page-shell {
      padding-left: 0.35rem;
      padding-right: 0.35rem;
    }

    .quotation-page-shell .card {
      border-radius: 20px;
      box-shadow: 0 20px 38px -32px rgba(15, 23, 42, 0.38);
      overflow: hidden;
    }

    .quotation-page-shell .card-header h6 {
      font-size: 1.1rem;
    }

    .quotation-form-shell {
      border: 1px solid var(--q-border);
      border-radius: 18px;
      background: linear-gradient(180deg, var(--q-surface) 0%, var(--q-soft) 100%);
      padding: 0.85rem;
    }

    .quotation-form-shell .col-md-3,
    .quotation-form-shell .col-md-4,
    .quotation-form-shell .col-md-6,
    .quotation-form-shell .col-md-8 {
      width: 100%;
    }

    .quotation-form-shell .table-responsive {
      border: 0 !important;
      background: transparent;
    }

    .quotation-items-table {
      min-width: 0 !important;
      width: 100%;
      border-collapse: separate;
      border-spacing: 0;
    }

    .quotation-items-table thead {
      display: none;
    }

    .quotation-items-table tbody tr {
      display: block;
      border: 1px solid var(--q-border);
      border-radius: 16px;
      background: #fff;
      box-shadow: 0 16px 28px -30px rgba(15, 23, 42, 0.45);
      padding: 0.7rem;
      margin-bottom: 0.65rem;
    }

    .quotation-items-table tbody td {
      display: block;
      width: 100%;
      border: 0;
      padding: 0 0 0.55rem 0;
    }

    .quotation-items-table tbody td:last-child {
      padding-bottom: 0;
    }

    .quotation-items-table tbody td:nth-child(1)::before { content: 'Tipo ítem'; }
    .quotation-items-table tbody td:nth-child(2)::before { content: 'Producto / Variante'; }
    .quotation-items-table tbody td:nth-child(3)::before { content: 'Servicio'; }
    .quotation-items-table tbody td:nth-child(4)::before { content: 'Descripción'; }
    .quotation-items-table tbody td:nth-child(5)::before { content: 'Cantidad'; }
    .quotation-items-table tbody td:nth-child(6)::before { content: 'Precio unit.'; }
    .quotation-items-table tbody td:nth-child(7)::before { content: 'Desc. %'; }
    .quotation-items-table tbody td:nth-child(8)::before { content: 'Acción'; }

    .quotation-items-table tbody td::before {
      display: block;
      font-size: 0.74rem;
      font-weight: 800;
      letter-spacing: 0.03em;
      text-transform: uppercase;
      color: var(--q-muted);
      margin-bottom: 0.28rem;
    }

    .quotation-submit-wrap {
      position: sticky;
      bottom: 10px;
      z-index: 5;
      background: linear-gradient(180deg, rgba(248, 251, 255, 0) 0%, rgba(248, 251, 255, 0.96) 32%);
      padding-top: 0.5rem;
    }

    .quotation-submit-wrap .btn {
      width: 100%;
      border-radius: 14px;
      font-weight: 700;
      min-height: 48px;
      box-shadow: 0 14px 24px -18px rgba(15, 23, 42, 0.5);
    }

    .quotations-history-table thead {
      display: none;
    }

    .quotations-history-table tbody tr {
      display: block;
      border: 1px solid var(--q-border);
      border-radius: 16px;
      background: #fff;
      padding: 0.8rem;
      margin-bottom: 0.75rem;
    }

    .quotations-history-table tbody td {
      display: flex;
      justify-content: space-between;
      gap: 0.75rem;
      border: 0;
      padding: 0.35rem 0;
      text-align: right;
    }

    .quotations-history-table tbody td::before {
      content: attr(data-label);
      font-weight: 700;
      color: #475569;
      text-align: left;
      flex: 0 0 auto;
    }

    .quotations-history-table tbody td:last-child {
      display: block;
      text-align: left;
      padding-top: 0.6rem;
    }

    .quotations-history-table tbody td:last-child::before {
      display: none;
    }

    .quotations-history-table tbody td:last-child .btn,
    .quotations-history-table tbody td:last-child form {
      width: 100%;
      margin-bottom: 0.45rem;
    }

    .quotations-history-table tbody td:last-child .btn {
      justify-content: center;
    }
  }
</style>
@endpush

@section('content')
<div class="container-fluid py-2 quotation-page-shell">
  @if(session('success'))
    <div class="alert alert-success text-white bg-gradient-success" role="alert">{{ session('success') }}</div>
  @endif

  @if($errors->any())
    <div class="alert alert-danger text-white bg-gradient-danger" role="alert">
      <ul class="mb-0">
        @foreach($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  @php
    $isEditing = (bool) $editingQuotation;
    $editingQuotationItems = $editingQuotation
      ? $editingQuotation->items->map(function ($item) {
          return [
            'product_id' => $item->product_id,
            'product_variant_id' => $item->product_variant_id,
            'item_type' => $item->item_type,
            'service_name' => $item->service_name,
            'description' => $item->description,
            'quantity' => $item->quantity,
            'unit_price' => $item->unit_price,
            'discount_percent' => $item->discount_percent,
          ];
        })->values()->all()
      : [];
  @endphp

  <div class="card my-4">
    <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
      <div class="bg-gradient-dark shadow-dark border-radius-lg pt-3 pb-3">
        <h6 class="text-white text-capitalize ps-3 mb-0">Módulo de Cotizaciones</h6>
      </div>
    </div>

    <div class="card-body">
      <div class="card border mb-4">
        <div class="card-header pb-0 d-flex justify-content-between align-items-center">
          <h6 class="mb-0">{{ $isEditing ? 'Editar cotización #' . $editingQuotation->id : 'Crear cotización PDF' }}</h6>
          @if($isEditing)
            <a href="{{ route('projects.module.quotations.index') }}" class="btn btn-outline-secondary btn-sm mb-0">Cancelar edición</a>
          @endif
        </div>
        <div class="card-body">
          <form method="POST" action="{{ $isEditing ? route('projects.module.quotations.update', $editingQuotation) : route('projects.module.quotations.store') }}" id="quotationForm" class="row g-3 quotation-form-shell">
            @csrf
            @if($isEditing)
              @method('PUT')
            @endif

            <div class="col-md-3">
              <label class="form-label">Tipo cliente/proveedor</label>
              <select name="type" id="quotationType" class="form-control border border-1 p-2" required>
                <option value="customer" {{ old('type', $editingQuotation->type ?? 'customer') === 'customer' ? 'selected' : '' }}>Cliente</option>
                <option value="supplier_request" {{ old('type', $editingQuotation->type ?? '') === 'supplier_request' ? 'selected' : '' }}>Proveedor</option>
              </select>
            </div>
            <div class="col-md-3">
              <label class="form-label">Tipo de cotización</label>
              <select name="quotation_kind" class="form-control border border-1 p-2" required>
                <option value="products" {{ old('quotation_kind', $editingQuotation->quotation_kind ?? 'mixed') === 'products' ? 'selected' : '' }}>Productos</option>
                <option value="services" {{ old('quotation_kind', $editingQuotation->quotation_kind ?? 'mixed') === 'services' ? 'selected' : '' }}>Servicios</option>
                <option value="materials" {{ old('quotation_kind', $editingQuotation->quotation_kind ?? 'mixed') === 'materials' ? 'selected' : '' }}>Lista de materiales</option>
                <option value="project" {{ old('quotation_kind', $editingQuotation->quotation_kind ?? 'mixed') === 'project' ? 'selected' : '' }}>Proyecto</option>
                <option value="mixed" {{ old('quotation_kind', $editingQuotation->quotation_kind ?? 'mixed') === 'mixed' ? 'selected' : '' }}>Mixta</option>
              </select>
            </div>
            <div class="col-md-6">
              <label class="form-label">Título</label>
              <input type="text" name="title" class="form-control border border-1 p-2" value="{{ old('title', $editingQuotation->title ?? '') }}" required>
            </div>

            <div class="col-md-4" id="customerSelectWrap">
              <label class="form-label">Cliente registrado (opcional)</label>
              <select name="customer_id" id="customerId" class="form-control border border-1 p-2">
                <option value="">Seleccionar cliente existente</option>
                @foreach($customers as $customer)
                  <option value="{{ $customer->id }}" {{ (string) old('customer_id', $editingQuotation->customer_id ?? '') === (string) $customer->id ? 'selected' : '' }}>{{ $customer->name }}{{ $customer->email ? ' (' . $customer->email . ')' : '' }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-4" id="customerNameWrap">
              <label class="form-label">Cliente nuevo / manual</label>
              <input type="text" name="customer_name" class="form-control border border-1 p-2" value="{{ old('customer_name', $editingQuotation->customer_name ?? '') }}">
            </div>
            <div class="col-md-4" id="customerEmailWrap">
              <label class="form-label">Correo cliente</label>
              <input type="email" name="customer_email" class="form-control border border-1 p-2" value="{{ old('customer_email', $editingQuotation->customer_email ?? '') }}">
            </div>
            <div class="col-md-4" id="customerPhoneWrap">
              <label class="form-label">Teléfono cliente (opcional)</label>
              <input type="text" name="customer_phone" class="form-control border border-1 p-2" value="{{ old('customer_phone') }}">
            </div>
            <div class="col-md-8" id="createCustomerWrap">
              <div class="form-check mt-4 pt-1">
                <input class="form-check-input" type="checkbox" value="1" name="create_customer" id="createCustomer" {{ old('create_customer') ? 'checked' : '' }}>
                <label class="form-check-label" for="createCustomer">Crear cliente automáticamente con los datos ingresados</label>
              </div>
            </div>

            <div class="col-md-6 d-none" id="providerSelectWrap">
              <label class="form-label">Proveedor registrado</label>
              <select name="provider_id" class="form-control border border-1 p-2">
                <option value="">Selecciona proveedor</option>
                @foreach($providers as $provider)
                  <option value="{{ $provider->id }}" {{ (string) old('provider_id', $editingQuotation->provider_id ?? '') === (string) $provider->id ? 'selected' : '' }}>{{ $provider->name }}</option>
                @endforeach
              </select>
            </div>
            <div class="col-md-6 d-none" id="providerNameWrap">
              <label class="form-label">Proveedor externo</label>
              <input type="text" name="provider_name" class="form-control border border-1 p-2" value="{{ old('provider_name', $editingQuotation->provider_name ?? '') }}">
            </div>

            <div class="col-md-3"><label class="form-label">Descuento global %</label><input type="number" name="discount_percent" min="0" max="100" step="0.01" value="{{ old('discount_percent', $editingQuotation->discount_percent ?? 0) }}" class="form-control border border-1 p-2"></div>
            @php
              $selectedQuotationCurrency = strtoupper((string) old('currency_code', $editingQuotation->currency_code ?? ($baseCurrencyCode ?? 'USD')));
              if (in_array($selectedQuotationCurrency, ['VES', 'VED', 'VEF', 'BSD'], true)) {
                $selectedQuotationCurrency = 'BS';
              }
            @endphp
            <div class="col-md-3">
              <label class="form-label">Moneda</label>
              <select name="currency_code" id="quotationCurrencyCode" class="form-control border border-1 p-2" required>
                <option value="USD" {{ $selectedQuotationCurrency === 'USD' ? 'selected' : '' }}>USD</option>
                <option value="EUR" {{ $selectedQuotationCurrency === 'EUR' ? 'selected' : '' }}>EUR</option>
                <option value="BS" {{ $selectedQuotationCurrency === 'BS' ? 'selected' : '' }}>BS (Bolívares)</option>
              </select>
            </div>
            <div class="col-md-3"><label class="form-label">Estado</label><select name="status" class="form-control border border-1 p-2"><option value="draft" {{ old('status', $editingQuotation->status ?? 'draft') === 'draft' ? 'selected' : '' }}>Borrador</option><option value="sent" {{ old('status', $editingQuotation->status ?? '') === 'sent' ? 'selected' : '' }}>Enviada</option><option value="approved" {{ old('status', $editingQuotation->status ?? '') === 'approved' ? 'selected' : '' }}>Aprobada</option><option value="rejected" {{ old('status', $editingQuotation->status ?? '') === 'rejected' ? 'selected' : '' }}>Rechazada</option></select></div>
            <div class="col-md-3"><label class="form-label">Válida hasta</label><input type="date" name="valid_until" class="form-control border border-1 p-2" value="{{ old('valid_until', optional($editingQuotation->valid_until ?? null)->format('Y-m-d')) }}"></div>

            <div class="col-12">
              <div class="table-responsive border rounded">
                <table class="table table-sm mb-0 quotation-items-table">
                  <thead><tr><th style="min-width:170px;">Tipo ítem</th><th style="min-width:280px;">Producto / Variante</th><th style="min-width:180px;">Servicio</th><th style="min-width:200px;">Descripción</th><th style="min-width:120px;">Cantidad</th><th style="min-width:140px;">Precio unit.</th><th style="min-width:120px;">Desc. %</th><th style="min-width:60px;"></th></tr></thead>
                  <tbody id="quotationItemsBody"></tbody>
                </table>
              </div>
              <button type="button" id="addQuotationItemBtn" class="btn btn-outline-dark btn-sm mt-2 mb-0">+ Agregar ítem</button>
            </div>

            <div class="col-12"><label class="form-label">Notas</label><textarea name="notes" rows="2" class="form-control border border-1 p-2">{{ old('notes', $editingQuotation->notes ?? '') }}</textarea></div>
            <div class="col-12 text-end quotation-submit-wrap"><button type="submit" class="btn btn-dark mb-0">{{ $isEditing ? 'Actualizar cotización' : 'Guardar cotización' }}</button></div>
          </form>
        </div>
      </div>

      <div class="table-responsive">
        <table class="table table-sm align-items-center mb-0 quotations-history-table">
          <thead><tr><th>#</th><th>Tipo</th><th>Categoría</th><th>Título</th><th>Total</th><th>Control</th><th>Acciones</th></tr></thead>
          <tbody>
            @forelse($quotations as $quotation)
              <tr>
                <td data-label="#">{{ $quotation->id }}</td>
                <td data-label="Tipo">{{ $quotation->type === 'supplier_request' ? 'Proveedor' : 'Cliente' }}</td>
                <td data-label="Categoría">{{ strtoupper($quotation->quotation_kind ?? 'MIXED') }}</td>
                <td data-label="Título">{{ $quotation->title }}</td>
                <td data-label="Total">{{ number_format((float) $quotation->total_amount, 2) }} {{ $quotation->currency_code }}</td>
                <td data-label="Control">
                  @if($quotation->conversion_target === 'project')
                    Proyecto #{{ $quotation->converted_project_id }}
                  @elseif($quotation->conversion_target === 'sale')
                    Venta {{ $quotation->converted_sale_reference }}
                  @elseif($quotation->conversion_target === 'inventory_entry')
                    Entrada #{{ $quotation->converted_purchase_order_id }}
                  @else
                    Pendiente
                  @endif
                </td>
                <td data-label="Acciones" class="d-flex flex-wrap gap-2">
                  <a href="{{ route('projects.module.quotations.index', ['edit' => $quotation->id]) }}" class="btn btn-outline-primary btn-sm mb-0">Editar</a>
                  <a href="{{ route('projects.module.quotations.pdf', $quotation) }}" class="btn btn-outline-dark btn-sm mb-0" target="_blank">PDF</a>
                  <form method="POST" action="{{ route('projects.module.quotations.toProject', $quotation) }}" class="d-flex gap-1">@csrf<input type="text" name="project_name" class="form-control form-control-sm border border-1 p-2" placeholder="Nombre proyecto"><button class="btn btn-outline-primary btn-sm mb-0" type="submit">A proyecto</button></form>
                  <form method="POST" action="{{ route('projects.module.quotations.toSale', $quotation) }}" class="d-flex gap-1">@csrf<input type="text" name="sale_reference" class="form-control form-control-sm border border-1 p-2" placeholder="Ref venta" required><button class="btn btn-outline-success btn-sm mb-0" type="submit">A venta</button></form>
                  @if($quotation->type === 'supplier_request')
                    <form method="POST" action="{{ route('projects.module.quotations.toInventory', $quotation) }}" class="d-flex gap-1">
                      @csrf
                      <select name="warehouse_id" class="form-control form-control-sm border border-1 p-2">
                        <option value="">Almacén automático</option>
                        @foreach($warehouses as $warehouse)
                          <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                        @endforeach
                      </select>
                      <button class="btn btn-outline-warning btn-sm mb-0" type="submit">A inventario</button>
                    </form>
                  @endif
                </td>
              </tr>
            @empty
              <tr><td colspan="7" class="text-center text-muted">Sin cotizaciones registradas.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>
    </div>
  </div>
</div>

<template id="quotationItemTemplate">
  <tr>
    <td>
      <select class="form-control border border-1 p-2" data-name="item_type" required>
        <option value="product">Producto</option>
        <option value="materials">Lista de materiales</option>
        <option value="service">Servicio</option>
        <option value="project">Proyecto</option>
        <option value="free">Libre</option>
      </select>
    </td>
    <td class="quotation-item-select2-wrap">
      <input type="hidden" class="js-item-product-id" value="">
      <select class="form-control border border-1 p-2 js-item-variant" data-name="product_variant_id">
        <option value="">Ítem libre</option>
        @foreach($productVariants as $variant)
          @php
            $productName = $variant->product->name ?? 'Producto';
            $variantName = $variant->size ?? 'Variante';
            $variantBarcode = trim((string) ($variant->barcode ?? ''));
            $variantQrCode = trim((string) ($variant->qr_code ?? ''));
            $productBarcode = trim((string) ($variant->product->barcode ?? ''));
            $productQrCode = trim((string) ($variant->product->qr_code ?? ''));
            $productCode = 'P' . (int) $variant->product_id;
            $variantCode = 'V' . (int) $variant->id;
            $searchTokens = collect([
              $productName,
              $variantName,
              $variantBarcode,
              $variantQrCode,
              $productBarcode,
              $productQrCode,
              $productCode,
              $variantCode,
              (string) ((int) $variant->id),
              (string) ((int) $variant->product_id),
            ])->filter()->implode(' | ');
          @endphp
          <option
            value="{{ $variant->id }}"
            data-product-id="{{ $variant->product_id }}"
            data-product-name="{{ $productName }}"
            data-variant-name="{{ $variantName }}"
            data-price="{{ number_format((float) $variant->price, 2, '.', '') }}"
            data-search="{{ $searchTokens }}">
            {{ $productName }} - {{ $variantName }}
          </option>
        @endforeach
      </select>
    </td>
    <td><input type="text" class="form-control border border-1 p-2" data-name="service_name" placeholder="Servicio"></td>
    <td><input type="text" class="form-control border border-1 p-2 js-item-description" data-name="description" required></td>
    <td><input type="number" min="0.01" step="0.01" class="form-control border border-1 p-2" data-name="quantity" value="1" required></td>
    <td><input type="number" min="0" step="0.01" class="form-control border border-1 p-2 js-item-unit-price" data-name="unit_price" value="0" required></td>
    <td><input type="number" min="0" max="100" step="0.01" class="form-control border border-1 p-2" data-name="discount_percent" value="0"></td>
    <td><button type="button" class="btn btn-outline-danger btn-sm mb-0 js-remove-item">X</button></td>
  </tr>
</template>
@endsection

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  const quotationCurrencySelect = document.getElementById('quotationCurrencyCode');
  const baseCurrencyCode = @json(strtoupper((string) ($baseCurrencyCode ?? 'USD')));
  const dollarRateToBs = Number(@json((float) ($dollarRateToBs ?? 0)) || 0);
  const euroRateToBs = Number(@json((float) ($euroRateToBs ?? 0)) || 0);

  const normalizeCurrencyCode = (value) => {
    const code = String(value || '').trim().toUpperCase();
    if (['BS', 'VES', 'VED', 'VEF', 'BSD'].includes(code)) {
      return 'BS';
    }
    if (['USD', 'EUR'].includes(code)) {
      return code;
    }
    return 'USD';
  };

  const resolveRateToBs = (currencyCode) => {
    const code = normalizeCurrencyCode(currencyCode);
    if (code === 'BS') return 1;
    if (code === 'EUR') return euroRateToBs > 0 ? euroRateToBs : 0;
    return dollarRateToBs > 0 ? dollarRateToBs : 0;
  };

  const convertCurrencyAmount = (amount, fromCurrency, toCurrency) => {
    const numericAmount = Number(amount || 0);
    if (!Number.isFinite(numericAmount)) {
      return 0;
    }

    const from = normalizeCurrencyCode(fromCurrency);
    const to = normalizeCurrencyCode(toCurrency);

    if (from === to) {
      return numericAmount;
    }

    const fromRate = resolveRateToBs(from);
    const toRate = resolveRateToBs(to);

    if ((from !== 'BS' && fromRate <= 0) || (to !== 'BS' && toRate <= 0)) {
      return numericAmount;
    }

    const amountInBs = from === 'BS' ? numericAmount : (numericAmount * fromRate);
    return to === 'BS' ? amountInBs : (amountInBs / toRate);
  };

  const quotationItemTypeStorageKey = 'shopix.quotations.last_item_type';

  const resolveLastSelectedItemType = () => {
    try {
      const saved = String(window.localStorage.getItem(quotationItemTypeStorageKey) || '').trim();
      if (['product', 'materials', 'service', 'project', 'free'].includes(saved)) {
        return saved;
      }
    } catch (error) {
      // Ignore storage access errors and fallback to default value.
    }

    return 'product';
  };

  const persistLastSelectedItemType = (value) => {
    const normalized = String(value || '').trim();
    if (!['product', 'materials', 'service', 'project', 'free'].includes(normalized)) {
      return;
    }

    try {
      window.localStorage.setItem(quotationItemTypeStorageKey, normalized);
    } catch (error) {
      // Ignore storage access errors.
    }
  };

  let lastSelectedItemType = resolveLastSelectedItemType();

  const initVariantSelect2 = (row) => {
    const variantSelect = row ? row.querySelector('.js-item-variant') : null;
    if (!variantSelect || !window.jQuery || !window.jQuery.fn?.select2) {
      return;
    }

    const $select = window.jQuery(variantSelect);
    if ($select.hasClass('select2-hidden-accessible')) {
      $select.select2('destroy');
    }

    $select.select2({
      width: '100%',
      placeholder: 'Buscar por nombre, codigo o barcode',
      allowClear: true,
      matcher: function (params, data) {
        const term = String(params?.term || '').trim().toLowerCase();
        if (!term) {
          return data;
        }

        const text = String(data?.text || '').toLowerCase();
        const search = String(data?.element?.dataset?.search || '').toLowerCase();
        const haystack = `${text} ${search}`.trim();

        return haystack.includes(term) ? data : null;
      }
    });

    $select.off('change.select2-sync').on('change.select2-sync', function () {
      variantSelect.dispatchEvent(new Event('change', { bubbles: true }));
    });
  };

  const quotationType = document.getElementById('quotationType');
  const customerSelectWrap = document.getElementById('customerSelectWrap');
  const customerNameWrap = document.getElementById('customerNameWrap');
  const customerEmailWrap = document.getElementById('customerEmailWrap');
  const customerPhoneWrap = document.getElementById('customerPhoneWrap');
  const createCustomerWrap = document.getElementById('createCustomerWrap');
  const providerSelectWrap = document.getElementById('providerSelectWrap');
  const providerNameWrap = document.getElementById('providerNameWrap');

  const toggleQuotationTypeFields = () => {
    const isSupplier = quotationType && quotationType.value === 'supplier_request';

    if (customerSelectWrap) customerSelectWrap.classList.toggle('d-none', isSupplier);
    if (customerNameWrap) customerNameWrap.classList.toggle('d-none', isSupplier);
    if (customerEmailWrap) customerEmailWrap.classList.toggle('d-none', isSupplier);
    if (customerPhoneWrap) customerPhoneWrap.classList.toggle('d-none', isSupplier);
    if (createCustomerWrap) createCustomerWrap.classList.toggle('d-none', isSupplier);

    if (providerSelectWrap) providerSelectWrap.classList.toggle('d-none', !isSupplier);
    if (providerNameWrap) providerNameWrap.classList.toggle('d-none', !isSupplier);
  };

  if (quotationType) {
    quotationType.addEventListener('change', toggleQuotationTypeFields);
    toggleQuotationTypeFields();
  }

  const itemsBody = document.getElementById('quotationItemsBody');
  const template = document.getElementById('quotationItemTemplate');
  const addItemBtn = document.getElementById('addQuotationItemBtn');

  const initialItems = {{ \Illuminate\Support\Js::from($editingQuotationItems) }};

  const assignRowNames = (row, index) => {
    row.querySelectorAll('[data-name]').forEach((field) => {
      const key = field.getAttribute('data-name');
      field.name = `items[${index}][${key}]`;
    });

    const productIdInput = row.querySelector('.js-item-product-id');
    if (productIdInput) {
      productIdInput.name = `items[${index}][product_id]`;
    }
  };

  const syncVariantToFields = (row, forceWrite = false) => {
    const variantSelect = row.querySelector('.js-item-variant');
    const descriptionInput = row.querySelector('.js-item-description');
    const unitPriceInput = row.querySelector('.js-item-unit-price');
    const productIdInput = row.querySelector('.js-item-product-id');

    const selected = variantSelect ? variantSelect.options[variantSelect.selectedIndex] : null;
    if (!selected || !String(variantSelect.value || '').trim()) {
      if (productIdInput) productIdInput.value = '';
      return;
    }

    if (productIdInput) productIdInput.value = selected.getAttribute('data-product-id') || '';

    if (descriptionInput && (forceWrite || !String(descriptionInput.value || '').trim())) {
      const productName = selected.getAttribute('data-product-name') || 'Producto';
      const variantName = selected.getAttribute('data-variant-name') || 'Variante';
      descriptionInput.value = `${productName} - ${variantName}`;
    }

    if (unitPriceInput && (forceWrite || Number(unitPriceInput.value || 0) <= 0)) {
      const price = Number(selected.getAttribute('data-price') || 0);
      const selectedCurrency = normalizeCurrencyCode(quotationCurrencySelect?.value || baseCurrencyCode);
      const convertedPrice = convertCurrencyAmount(price, baseCurrencyCode, selectedCurrency);
      unitPriceInput.value = convertedPrice.toFixed(2);
    }
  };

  const addItemRow = (defaults) => {
    if (!template || !itemsBody) return;

    const row = template.content.firstElementChild.cloneNode(true);
    const index = itemsBody.querySelectorAll('tr').length;
    assignRowNames(row, index);

    if (defaults) {
      const variantSelect = row.querySelector('.js-item-variant');
      if (variantSelect) variantSelect.value = String(defaults.product_variant_id || '');

      const productIdInput = row.querySelector('.js-item-product-id');
      if (productIdInput) productIdInput.value = String(defaults.product_id || '');

      const serviceInput = row.querySelector('[data-name="service_name"]');
      if (serviceInput) serviceInput.value = defaults.service_name || '';

      const typeInput = row.querySelector('[data-name="item_type"]');
      if (typeInput) {
        typeInput.value = defaults.item_type || lastSelectedItemType;
        lastSelectedItemType = typeInput.value || lastSelectedItemType;
      }

      const descriptionInput = row.querySelector('.js-item-description');
      if (descriptionInput) descriptionInput.value = defaults.description || '';

      const quantityInput = row.querySelector('[data-name="quantity"]');
      if (quantityInput) quantityInput.value = Number(defaults.quantity || 1).toFixed(2);

      const unitPriceInput = row.querySelector('.js-item-unit-price');
      if (unitPriceInput) unitPriceInput.value = Number(defaults.unit_price || 0).toFixed(2);

      const discountInput = row.querySelector('[data-name="discount_percent"]');
      if (discountInput) discountInput.value = Number(defaults.discount_percent || 0).toFixed(2);
    }

    if (!defaults) {
      const typeInput = row.querySelector('[data-name="item_type"]');
      if (typeInput) {
        typeInput.value = lastSelectedItemType;
      }
    }

    itemsBody.appendChild(row);

    const typeInput = row.querySelector('[data-name="item_type"]');
    if (typeInput) {
      typeInput.addEventListener('change', function () {
        lastSelectedItemType = String(typeInput.value || 'product');
        persistLastSelectedItemType(lastSelectedItemType);
      });
      persistLastSelectedItemType(typeInput.value || lastSelectedItemType);
    }

    const variantSelect = row.querySelector('.js-item-variant');
    if (variantSelect) {
      variantSelect.addEventListener('change', function () { syncVariantToFields(row); });
      initVariantSelect2(row);
      if (defaults && defaults.product_variant_id) {
        syncVariantToFields(row, false);
      }
    }

    const removeBtn = row.querySelector('.js-remove-item');
    if (removeBtn) removeBtn.addEventListener('click', function () { row.remove(); });
  };

  if (addItemBtn) addItemBtn.addEventListener('click', function () { addItemRow(null); });

  if (Array.isArray(initialItems) && initialItems.length > 0) {
    initialItems.forEach(function (item) { addItemRow(item); });
  } else {
    addItemRow(null);
  }

  if (quotationCurrencySelect) {
    let previousCurrencyCode = normalizeCurrencyCode(quotationCurrencySelect.value || baseCurrencyCode);

    quotationCurrencySelect.addEventListener('change', function () {
      const nextCurrencyCode = normalizeCurrencyCode(quotationCurrencySelect.value || baseCurrencyCode);
      if (nextCurrencyCode === previousCurrencyCode) {
        return;
      }

      document.querySelectorAll('#quotationItemsBody .js-item-unit-price').forEach((priceInput) => {
        const currentPrice = Number(priceInput.value || 0);
        const convertedPrice = convertCurrencyAmount(currentPrice, previousCurrencyCode, nextCurrencyCode);
        priceInput.value = convertedPrice.toFixed(2);
      });

      previousCurrencyCode = nextCurrencyCode;
    });
  }
});
</script>
@endpush
