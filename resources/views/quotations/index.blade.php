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

  .quotations-history-table th,
  .quotations-history-table td {
    vertical-align: middle;
    text-align: center;
  }

  .quotations-history-table td[data-label="Acciones"] {
    justify-content: center;
    align-items: center;
  }

  .quotation-status-chip {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    min-width: 110px;
    padding: 0.25rem 0.55rem;
    border-radius: 999px;
    font-size: 0.72rem;
    font-weight: 700;
    letter-spacing: 0.02em;
    text-transform: uppercase;
    border: 1px solid #cbd5e1;
    color: #334155;
    background: #f8fafc;
  }

  .quotation-status-chip[data-status="invalidated"],
  .quotation-status-chip[data-status="annulled"],
  .quotation-status-chip[data-status="replaced"] {
    color: #991b1b;
    border-color: #fecaca;
    background: #fef2f2;
  }

  .quotations-history-filters {
    border: 1px solid var(--q-border);
    border-radius: 14px;
    background: var(--q-soft);
    padding: 0.75rem;
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

    .quotations-history-wrap {
      overflow-x: hidden !important;
    }

    .quotations-history-table {
      width: 100%;
      table-layout: fixed;
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
      display: block;
      border: 0;
      padding: 0.35rem 0;
      text-align: left;
      overflow: visible;
      white-space: normal;
      word-break: break-word;
    }

    .quotations-history-table tbody td::before {
      content: attr(data-label);
      display: block;
      font-weight: 700;
      color: #475569;
      text-align: left;
      margin-bottom: 0.18rem;
    }

    .quotations-history-table tbody td:last-child {
      display: block;
      text-align: left;
      padding-top: 0.45rem;
      overflow: visible;
    }

    .quotations-history-table tbody td:last-child::before {
      display: none;
    }

    .quotations-history-table tbody td:last-child .d-flex {
      display: grid !important;
      grid-template-columns: repeat(3, minmax(0, 1fr));
      gap: 0.4rem !important;
      align-items: stretch;
      width: 100%;
      max-width: 100%;
    }

    .quotations-history-table tbody td:last-child .d-flex > * {
      min-width: 0;
    }

    .quotations-history-table tbody td:last-child .btn {
      justify-content: center;
      min-height: 36px;
      padding: 0.3rem 0.45rem;
      width: 100%;
      margin-bottom: 0;
      border-radius: 10px;
      font-size: 0.78rem;
      line-height: 1;
    }

    .quotations-history-table tbody td:last-child .btn span {
      display: none;
    }

    .quotations-history-table tbody td:last-child .btn .material-symbols-rounded {
      margin: 0 !important;
      font-size: 18px;
    }

    .quotations-history-table tbody td:last-child .btn.d-inline-flex {
      gap: 0 !important;
    }

    .quotations-history-table tbody td:last-child .d-flex > form {
      grid-column: 1 / -1;
      display: grid !important;
      grid-template-columns: 1fr auto;
      gap: 0.35rem !important;
      width: 100%;
      margin-bottom: 0;
    }

    .quotations-history-table tbody td:last-child .d-flex > form .form-control {
      min-width: 0;
      font-size: 0.76rem;
      padding: 0.3rem 0.45rem;
      height: 36px;
    }

    .quotations-history-table tbody td:last-child .d-flex > form .btn {
      width: 42px;
      min-width: 42px;
      padding: 0;
    }

    .quotations-history-filters .row > [class*="col-"] {
      width: 100%;
    }

    .quotations-history-filters .btn {
      width: 100%;
    }
  }

  @media (max-width: 575.98px) {
    .quotations-history-table tbody td:last-child .d-flex {
      grid-template-columns: repeat(2, minmax(0, 1fr));
    }
  }
</style>
@endpush

@section('content')
<div class="container-fluid py-2 quotation-page-shell">
  @if(session('success'))
    <div class="alert alert-success text-white bg-gradient-success" role="alert">{{ session('success') }}</div>
  @endif

  @if(session('warning'))
    <div class="alert alert-warning text-white bg-gradient-warning" role="alert">{{ session('warning') }}</div>
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

    $productVariantItems = $productVariants->map(function ($variant) {
      $productName = $variant->product->name ?? 'Producto';
      $variantName = $variant->size ?? 'Variante';
      $variantBarcode = trim((string) ($variant->barcode ?? ''));
      $variantQrCode = trim((string) ($variant->qr_code ?? ''));
      $productBarcode = trim((string) ($variant->product->barcode ?? ''));
      $productQrCode = trim((string) ($variant->product->qr_code ?? ''));
      $productCode = 'P' . (int) $variant->product_id;
      $variantCode = 'V' . (int) $variant->id;

      return [
        'id' => (int) $variant->id,
        'product_id' => (int) $variant->product_id,
        'label' => $productName . ' - ' . $variantName,
        'product_name' => $productName,
        'variant_name' => $variantName,
        'price' => number_format((float) $variant->price, 2, '.', ''),
        'search' => collect([
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
        ])->filter()->implode(' | '),
      ];
    })->values()->all();

    $appointmentServiceItems = collect($appointmentServices ?? [])->map(function ($service) {
      $serviceName = trim((string) ($service->name ?? 'Servicio'));
      $serviceDescription = trim((string) ($service->description ?? ''));

      return [
        'id' => (int) $service->id,
        'name' => $serviceName,
        'label' => $serviceDescription !== '' ? ($serviceName . ' - ' . $serviceDescription) : $serviceName,
        'description' => $serviceDescription,
        'price' => number_format((float) ($service->price ?? 0), 2, '.', ''),
        'search' => collect([
          $serviceName,
          $serviceDescription,
          (string) ((int) $service->id),
        ])->filter()->implode(' | '),
      ];
    })->values()->all();

    $projectItems = collect($projects ?? [])->map(function ($project) {
      $projectName = trim((string) ($project->name ?? 'Proyecto'));
      $projectBudget = (float) ($project->budget_amount ?? 0);

      return [
        'id' => (int) $project->id,
        'name' => $projectName,
        'label' => $projectName,
        'description' => $projectName,
        'price' => number_format($projectBudget, 2, '.', ''),
        'search' => collect([
          $projectName,
          (string) ((int) $project->id),
        ])->filter()->implode(' | '),
      ];
    })->values()->all();
  @endphp

  @php
    $activeQuotationTab = in_array(($activeQuotationTab ?? 'create'), ['create', 'history'], true)
      ? $activeQuotationTab
      : 'create';
  @endphp

  <div class="card my-4">
    <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
      <div class="bg-gradient-dark shadow-dark border-radius-lg pt-3 pb-3">
        <h6 class="text-white text-capitalize ps-3 mb-0">Módulo de Cotizaciones</h6>
      </div>
    </div>

    <div class="card-body">
      <ul class="nav nav-pills bg-gray-100 p-1 border-radius-lg mb-4" role="tablist">
        <li class="nav-item" role="presentation">
          <a
            class="nav-link mb-0 px-3 py-2 {{ $activeQuotationTab === 'create' ? 'active' : '' }}"
            href="{{ route('projects.module.quotations.index', array_filter(['tab' => 'create', 'edit' => $isEditing ? $editingQuotation->id : null])) }}"
            role="tab"
            aria-selected="{{ $activeQuotationTab === 'create' ? 'true' : 'false' }}">
            Crear cotización
          </a>
        </li>
        <li class="nav-item" role="presentation">
          <a
            class="nav-link mb-0 px-3 py-2 {{ $activeQuotationTab === 'history' ? 'active' : '' }}"
            href="{{ route('projects.module.quotations.index', ['tab' => 'history']) }}"
            role="tab"
            aria-selected="{{ $activeQuotationTab === 'history' ? 'true' : 'false' }}">
            Historial
          </a>
        </li>
      </ul>

      <div class="tab-content">
      <div class="tab-pane fade {{ $activeQuotationTab === 'create' ? 'show active' : '' }}" id="quotations-create-pane" role="tabpanel">
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

            <input type="hidden" name="tab" value="create">

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
              <input type="text" name="customer_name" id="customerNameInput" class="form-control border border-1 p-2" value="{{ old('customer_name', $editingQuotation->customer_name ?? '') }}">
            </div>
            <div class="col-md-4" id="customerEmailWrap">
              <label class="form-label">Correo cliente</label>
              <input type="email" name="customer_email" id="customerEmailInput" class="form-control border border-1 p-2" value="{{ old('customer_email', $editingQuotation->customer_email ?? '') }}">
            </div>
            <div class="col-md-4" id="customerPhoneWrap">
              <label class="form-label">Teléfono cliente</label>
              <input type="text" name="customer_phone" id="customerPhoneInput" class="form-control border border-1 p-2" value="{{ old('customer_phone', $editingQuotation->customer_phone ?? '') }}">
            </div>
            <div class="col-md-4" id="customerDniWrap">
              <label class="form-label">DNI cliente</label>
              <input type="text" name="customer_dni" id="customerDniInput" class="form-control border border-1 p-2" value="{{ old('customer_dni') }}">
            </div>
            <div class="col-md-8" id="createCustomerWrap">
              <div class="form-check mt-4 pt-1">
                <input class="form-check-input" type="checkbox" value="1" name="create_customer" id="createCustomer" {{ old('create_customer') ? 'checked' : '' }}>
                <label class="form-check-label" for="createCustomer">Crear cliente automáticamente con los datos ingresados</label>
              </div>
              <div class="form-check mt-2 pt-1">
                <input class="form-check-input" type="checkbox" value="1" name="is_retention_agent" id="quotationRetentionAgent">
                <label class="form-check-label" for="quotationRetentionAgent">Agente de retención</label>
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
                  <thead><tr><th style="min-width:170px;">Tipo ítem</th><th style="min-width:280px;">Buscar ítem</th><th style="min-width:180px;">Nombre / referencia</th><th style="min-width:200px;">Descripción</th><th style="min-width:120px;">Cantidad</th><th style="min-width:140px;">Precio unit.</th><th style="min-width:120px;">Desc. %</th><th style="min-width:60px;"></th></tr></thead>
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

      </div>

      <div class="tab-pane fade {{ $activeQuotationTab === 'history' ? 'show active' : '' }}" id="quotations-history-pane" role="tabpanel">

      @php
        $quotationFilters = $quotationFilters ?? [
          'q' => '',
          'type' => 'all',
          'status' => 'all',
          'conversion' => 'all',
        ];
      @endphp

      <form method="GET" action="{{ route('projects.module.quotations.index') }}" class="quotations-history-filters mb-3">
        <input type="hidden" name="tab" value="history">
        <div class="row g-2 align-items-end">
          <div class="col-md-4">
            <label class="form-label mb-1">Buscar</label>
            <input type="text" name="q" class="form-control border border-1 p-2" placeholder="ID, título, cliente o referencia" value="{{ $quotationFilters['q'] }}">
          </div>
          <div class="col-md-2">
            <label class="form-label mb-1">Tipo</label>
            <select name="filter_type" class="form-control border border-1 p-2">
              <option value="all" {{ $quotationFilters['type'] === 'all' ? 'selected' : '' }}>Todos</option>
              <option value="customer" {{ $quotationFilters['type'] === 'customer' ? 'selected' : '' }}>Cliente</option>
              <option value="supplier_request" {{ $quotationFilters['type'] === 'supplier_request' ? 'selected' : '' }}>Proveedor</option>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label mb-1">Estado</label>
            <select name="filter_status" class="form-control border border-1 p-2">
              <option value="all" {{ $quotationFilters['status'] === 'all' ? 'selected' : '' }}>Todos</option>
              <option value="draft" {{ $quotationFilters['status'] === 'draft' ? 'selected' : '' }}>Borrador</option>
              <option value="sent" {{ $quotationFilters['status'] === 'sent' ? 'selected' : '' }}>Enviada</option>
              <option value="approved" {{ $quotationFilters['status'] === 'approved' ? 'selected' : '' }}>Aprobada</option>
              <option value="rejected" {{ $quotationFilters['status'] === 'rejected' ? 'selected' : '' }}>Rechazada</option>
              <option value="invalidated" {{ $quotationFilters['status'] === 'invalidated' ? 'selected' : '' }}>Invalidada</option>
              <option value="annulled" {{ $quotationFilters['status'] === 'annulled' ? 'selected' : '' }}>Anulada</option>
              <option value="replaced" {{ $quotationFilters['status'] === 'replaced' ? 'selected' : '' }}>Reemplazada</option>
            </select>
          </div>
          <div class="col-md-2">
            <label class="form-label mb-1">Control</label>
            <select name="filter_conversion" class="form-control border border-1 p-2">
              <option value="all" {{ $quotationFilters['conversion'] === 'all' ? 'selected' : '' }}>Todos</option>
              <option value="pending" {{ $quotationFilters['conversion'] === 'pending' ? 'selected' : '' }}>Pendiente</option>
              <option value="project" {{ $quotationFilters['conversion'] === 'project' ? 'selected' : '' }}>Proyecto</option>
              <option value="sale" {{ $quotationFilters['conversion'] === 'sale' ? 'selected' : '' }}>Venta</option>
              <option value="inventory_entry" {{ $quotationFilters['conversion'] === 'inventory_entry' ? 'selected' : '' }}>Entrada</option>
            </select>
          </div>
          <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-dark mb-0">Filtrar</button>
            <a href="{{ route('projects.module.quotations.index', ['tab' => 'history']) }}" class="btn btn-outline-secondary mb-0">Limpiar</a>
          </div>
        </div>
      </form>

      <div class="table-responsive quotations-history-wrap">
        <table class="table table-sm align-items-center mb-0 quotations-history-table">
          <thead><tr><th>#</th><th>Tipo</th><th>Categoría</th><th>Título</th><th>Total</th><th>Control</th><th>Estado</th><th>Acciones</th></tr></thead>
          <tbody>
            @forelse($quotations as $quotation)
              @php
                $isClosedQuotation = in_array(strtolower((string) ($quotation->status ?? '')), ['invalidated', 'annulled', 'replaced'], true);
              @endphp
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
                <td data-label="Estado">
                  <span class="quotation-status-chip" data-status="{{ strtolower((string) ($quotation->status ?? 'draft')) }}">
                    {{ strtoupper((string) ($quotation->status ?? 'draft')) }}
                  </span>
                </td>
                <td data-label="Acciones">
                  <div class="d-flex flex-wrap gap-2 align-items-center">
                    <a href="{{ route('projects.module.quotations.index', ['edit' => $quotation->id]) }}" class="btn btn-outline-primary btn-sm mb-0 d-inline-flex align-items-center gap-1">
                      <i class="material-symbols-rounded text-sm">edit</i>
                      <span>Editar</span>
                    </a>
                    <a href="{{ route('projects.module.quotations.pdf', $quotation) }}" class="btn btn-outline-dark btn-sm mb-0 d-inline-flex align-items-center gap-1" target="_blank">
                      <i class="material-symbols-rounded text-sm">picture_as_pdf</i>
                      <span>PDF</span>
                    </a>
                    @if(!($isSeller ?? false))
                      <button
                        type="button"
                        class="btn btn-outline-info btn-sm mb-0 d-inline-flex align-items-center gap-1"
                        data-bs-toggle="modal"
                        data-bs-target="#quotationActionModal"
                        data-action-url="{{ route('projects.module.quotations.toProject', $quotation) }}"
                        data-action-title="Pasar a proyecto"
                        data-action-message="Esta cotización se convertirá en proyecto."
                        data-action-submit-label="Confirmar proyecto"
                        data-action-submit-class="btn-info"
                        data-input-name="project_name"
                        data-input-label="Nombre del proyecto"
                        data-input-placeholder="Ej: Proyecto Oficina Central"
                        data-input-required="false"
                        {{ $isClosedQuotation ? 'disabled' : '' }}>
                        <i class="material-symbols-rounded text-sm">construction</i>
                        <span>A proyecto</span>
                      </button>
                    @endif
                    <button
                      type="button"
                      class="btn btn-outline-success btn-sm mb-0 d-inline-flex align-items-center gap-1"
                      data-bs-toggle="modal"
                      data-bs-target="#quotationActionModal"
                      data-action-url="{{ route('projects.module.quotations.toSale', $quotation) }}"
                      data-action-title="Pasar a venta"
                      data-action-message="Se registrará la conversión de esta cotización a venta."
                      data-action-submit-label="Confirmar venta"
                      data-action-submit-class="btn-success"
                      data-input-name="sale_reference"
                      data-input-label="Referencia de venta"
                      data-input-placeholder="Ej: VTA-2026-001"
                      data-input-required="true"
                      {{ $isClosedQuotation ? 'disabled' : '' }}>
                      <i class="material-symbols-rounded text-sm">point_of_sale</i>
                      <span>A venta</span>
                    </button>
                    @if($quotation->type === 'supplier_request')
                      <form method="POST" action="{{ route('projects.module.quotations.toInventory', $quotation) }}" class="d-flex gap-1">
                        @csrf
                        <select name="warehouse_id" class="form-control form-control-sm border border-1 p-2" {{ $isClosedQuotation ? 'disabled' : '' }}>
                          <option value="">Almacén automático</option>
                          @foreach($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}">{{ $warehouse->name }}</option>
                          @endforeach
                        </select>
                        <button class="btn btn-outline-warning btn-sm mb-0 d-inline-flex align-items-center gap-1" type="submit" {{ $isClosedQuotation ? 'disabled' : '' }}>
                          <i class="material-symbols-rounded text-sm">inventory_2</i>
                          <span>A inventario</span>
                        </button>
                      </form>
                    @endif
                    <button
                      type="button"
                      class="btn btn-outline-secondary btn-sm mb-0 d-inline-flex align-items-center gap-1"
                      data-bs-toggle="modal"
                      data-bs-target="#quotationActionModal"
                      data-action-url="{{ route('projects.module.quotations.invalidate', $quotation) }}"
                      data-action-title="Invalidar cotización"
                      data-action-message="La cotización quedará inválida y no podrá convertirse."
                      data-action-submit-label="Sí, invalidar"
                      data-action-submit-class="btn-secondary"
                      {{ $isClosedQuotation ? 'disabled' : '' }}>
                      <i class="material-symbols-rounded text-sm">block</i>
                      <span>Invalidar</span>
                    </button>
                    <button
                      type="button"
                      class="btn btn-outline-danger btn-sm mb-0 d-inline-flex align-items-center gap-1"
                      data-bs-toggle="modal"
                      data-bs-target="#quotationActionModal"
                      data-action-url="{{ route('projects.module.quotations.annul', $quotation) }}"
                      data-action-title="Anular cotización"
                      data-action-message="Esta acción anula la cotización actual."
                      data-action-submit-label="Sí, anular"
                      data-action-submit-class="btn-danger"
                      {{ $isClosedQuotation ? 'disabled' : '' }}>
                      <i class="material-symbols-rounded text-sm">cancel</i>
                      <span>Anular</span>
                    </button>
                    <button
                      type="button"
                      class="btn btn-outline-primary btn-sm mb-0 d-inline-flex align-items-center gap-1"
                      data-bs-toggle="modal"
                      data-bs-target="#quotationActionModal"
                      data-action-url="{{ route('projects.module.quotations.replace', $quotation) }}"
                      data-action-title="Anular y reemplazar"
                      data-action-message="Se anulará esta cotización y se creará una nueva versión."
                      data-action-submit-label="Anular y reemplazar"
                      data-action-submit-class="btn-primary"
                      data-input-name="replacement_title"
                      data-input-label="Título de la nueva versión"
                      data-input-placeholder="Ej: Cotización revisada v2"
                      data-input-required="false"
                      {{ $isClosedQuotation ? 'disabled' : '' }}>
                      <i class="material-symbols-rounded text-sm">autorenew</i>
                      <span>Anular y reemplazar</span>
                    </button>
                  </div>
                </td>
              </tr>
            @empty
              <tr><td colspan="8" class="text-center text-muted">Sin cotizaciones registradas.</td></tr>
            @endforelse
          </tbody>
        </table>
      </div>

      </div>
      </div>

      <div class="modal fade" id="quotationActionModal" tabindex="-1" aria-labelledby="quotationActionModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
          <div class="modal-content">
            <div class="modal-header">
              <h5 class="modal-title" id="quotationActionModalLabel">Confirmar acción</h5>
              <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <form method="POST" id="quotationActionForm">
              @csrf
              <div class="modal-body">
                <p class="text-sm mb-3" id="quotationActionModalMessage">Confirma esta acción sobre la cotización.</p>
                <div class="mb-2 d-none" id="quotationActionInputWrap">
                  <label for="quotationActionInput" class="form-label" id="quotationActionInputLabel">Dato requerido</label>
                  <input type="text" class="form-control border border-1 p-2" id="quotationActionInput" value="">
                </div>
              </div>
              <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary mb-0" data-bs-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn mb-0" id="quotationActionSubmitBtn">Confirmar</button>
              </div>
            </form>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<template id="quotationItemTemplate">
  <tr>
    <td>
      <select class="form-control border border-1 p-2 js-item-type-select" data-name="item_type" required>
        <option value="product">Producto</option>
        <option value="materials">Lista de materiales</option>
        <option value="service">Servicio</option>
        <option value="project">Proyecto</option>
        <option value="free">Libre</option>
      </select>
    </td>
    <td class="quotation-item-select2-wrap">
      <input type="hidden" class="js-item-product-id" value="">
      <div class="quotation-item-source-field" data-source-type="product">
        <select class="form-control border border-1 p-2 js-item-source-select" data-source-type="product">
          <option value="">Buscar producto o variante</option>
          @foreach($productVariantItems as $variant)
            <option
              value="{{ $variant['id'] }}"
              data-product-id="{{ $variant['product_id'] }}"
              data-product-name="{{ $variant['product_name'] }}"
              data-variant-name="{{ $variant['variant_name'] }}"
              data-price="{{ $variant['price'] }}"
              data-search="{{ $variant['search'] }}">
              {{ $variant['label'] }}
            </option>
          @endforeach
        </select>
      </div>
      <div class="quotation-item-source-field d-none" data-source-type="service">
        <select class="form-control border border-1 p-2 js-item-source-select" data-source-type="service">
          <option value="">Buscar servicio</option>
          @foreach($appointmentServiceItems as $service)
            <option
              value="{{ $service['id'] }}"
              data-service-name="{{ $service['name'] }}"
              data-description="{{ $service['description'] }}"
              data-price="{{ $service['price'] }}"
              data-search="{{ $service['search'] }}">
              {{ $service['label'] }}
            </option>
          @endforeach
        </select>
      </div>
      <div class="quotation-item-source-field d-none" data-source-type="project">
        <select class="form-control border border-1 p-2 js-item-source-select" data-source-type="project">
          <option value="">Buscar proyecto</option>
          @foreach($projectItems as $project)
            <option
              value="{{ $project['id'] }}"
              data-project-name="{{ $project['name'] }}"
              data-description="{{ $project['description'] }}"
              data-price="{{ $project['price'] }}"
              data-search="{{ $project['search'] }}">
              {{ $project['label'] }}
            </option>
          @endforeach
        </select>
      </div>
    </td>
    <td><input type="text" class="form-control border border-1 p-2 js-item-source-name" data-name="service_name" placeholder="Nombre del ítem"></td>
    <td><input type="text" class="form-control border border-1 p-2 js-item-description" data-name="description" required></td>
    <td><input type="text" inputmode="decimal" pattern="^[0-9]+(\.[0-9]+)?$" class="form-control border border-1 p-2" data-name="quantity" value="1" required></td>
    <td><input type="number" min="0" step="0.01" class="form-control border border-1 p-2 js-item-unit-price" data-name="unit_price" value="0" required></td>
    <td><input type="number" min="0" max="100" step="0.01" class="form-control border border-1 p-2" data-name="discount_percent" value="0"></td>
    <td><button type="button" class="btn btn-outline-danger btn-sm mb-0 js-remove-item">X</button></td>
  </tr>
</template>
@endsection

@push('scripts')
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
  const quotationCurrencySelect = document.getElementById('quotationCurrencyCode');
  const baseCurrencyCode = @json(strtoupper((string) ($baseCurrencyCode ?? 'USD')));
  const dollarRateToBs = Number(@json((float) ($dollarRateToBs ?? 0)) || 0);
  const euroRateToBs = Number(@json((float) ($euroRateToBs ?? 0)) || 0);
  const itemSourceGroups = {
    product: 'product',
    materials: 'product',
    service: 'service',
    project: 'project',
    free: null,
  };

  const quotationActionModalEl = document.getElementById('quotationActionModal');
  const quotationActionForm = document.getElementById('quotationActionForm');
  const quotationActionModalLabel = document.getElementById('quotationActionModalLabel');
  const quotationActionModalMessage = document.getElementById('quotationActionModalMessage');
  const quotationActionInputWrap = document.getElementById('quotationActionInputWrap');
  const quotationActionInputLabel = document.getElementById('quotationActionInputLabel');
  const quotationActionInput = document.getElementById('quotationActionInput');
  const quotationActionSubmitBtn = document.getElementById('quotationActionSubmitBtn');

  if (quotationActionModalEl && quotationActionForm) {
    quotationActionModalEl.addEventListener('show.bs.modal', function (event) {
      const trigger = event.relatedTarget;
      if (!trigger) {
        return;
      }

      const actionUrl = trigger.getAttribute('data-action-url') || '';
      const title = trigger.getAttribute('data-action-title') || 'Confirmar acción';
      const message = trigger.getAttribute('data-action-message') || 'Confirma esta acción sobre la cotización.';
      const submitLabel = trigger.getAttribute('data-action-submit-label') || 'Confirmar';
      const submitClass = trigger.getAttribute('data-action-submit-class') || 'btn-dark';
      const inputName = trigger.getAttribute('data-input-name') || '';
      const inputLabel = trigger.getAttribute('data-input-label') || 'Dato requerido';
      const inputPlaceholder = trigger.getAttribute('data-input-placeholder') || '';
      const inputRequired = String(trigger.getAttribute('data-input-required') || '').toLowerCase() === 'true';

      quotationActionForm.setAttribute('action', actionUrl);
      quotationActionModalLabel.textContent = title;
      quotationActionModalMessage.textContent = message;
      quotationActionSubmitBtn.textContent = submitLabel;
      quotationActionSubmitBtn.className = `btn mb-0 ${submitClass}`;

      quotationActionInput.value = '';
      quotationActionInput.removeAttribute('name');
      quotationActionInput.required = false;

      if (inputName) {
        quotationActionInputWrap.classList.remove('d-none');
        quotationActionInputLabel.textContent = inputLabel;
        quotationActionInput.placeholder = inputPlaceholder;
        quotationActionInput.name = inputName;
        quotationActionInput.required = inputRequired;
      } else {
        quotationActionInputWrap.classList.add('d-none');
        quotationActionInputLabel.textContent = 'Dato requerido';
        quotationActionInput.placeholder = '';
      }
    });
  }

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

  const getItemSourceGroup = (itemType) => itemSourceGroups[String(itemType || '').trim()] || 'product';

  const getActiveSourceSelect = (row, itemType) => {
    const sourceGroup = getItemSourceGroup(itemType);
    if (!sourceGroup) {
      return null;
    }

    return row ? row.querySelector(`.js-item-source-select[data-source-type="${sourceGroup}"]`) : null;
  };

  const initSourceSelect2 = (select, sourceGroup) => {
    if (!select || !window.jQuery || !window.jQuery.fn?.select2) {
      return;
    }

    const $select = window.jQuery(select);
    if ($select.hasClass('select2-hidden-accessible')) {
      $select.select2('destroy');
    }

    const placeholders = {
      product: 'Buscar producto, variante, barcode o código',
      service: 'Buscar servicio',
      project: 'Buscar proyecto',
    };

    $select.select2({
      width: '100%',
      placeholder: placeholders[sourceGroup] || 'Buscar ítem',
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
      select.dispatchEvent(new Event('change', { bubbles: true }));
      const row = select.closest('tr');
      if (row) {
        syncItemSourceToFields(row, true);
      }
    });
  };

  const initItemTypeSelect2 = (select) => {
    if (!select || !window.jQuery || !window.jQuery.fn?.select2) {
      return;
    }

    const $select = window.jQuery(select);
    if ($select.hasClass('select2-hidden-accessible')) {
      $select.select2('destroy');
    }

    $select.select2({
      width: '100%',
      minimumResultsForSearch: Infinity,
    });
  };

  const syncRowFieldNames = (row) => {
    const rows = Array.from(itemsBody ? itemsBody.querySelectorAll('tr') : []);
    const index = rows.indexOf(row);
    if (index < 0) {
      return;
    }

    row.querySelectorAll('[data-name]').forEach((field) => {
      const key = field.getAttribute('data-name');
      field.name = `items[${index}][${key}]`;
    });

    const productIdInput = row.querySelector('.js-item-product-id');
    if (productIdInput) {
      productIdInput.name = `items[${index}][product_id]`;
    }

    row.querySelectorAll('.js-item-source-select').forEach((select) => {
      select.removeAttribute('name');
    });

    const itemType = row.querySelector('[data-name="item_type"]')?.value || 'product';
    const activeSelect = getActiveSourceSelect(row, itemType);
    if (activeSelect && ['product', 'materials'].includes(itemType)) {
      activeSelect.name = `items[${index}][product_variant_id]`;
    }
  };

  const syncItemSourceToFields = (row, forceWrite = false) => {
    const typeInput = row.querySelector('[data-name="item_type"]');
    const itemType = String(typeInput?.value || 'product');
    const sourceGroup = getItemSourceGroup(itemType);
    const sourceSelect = getActiveSourceSelect(row, itemType);
    const productIdInput = row.querySelector('.js-item-product-id');
    const nameInput = row.querySelector('.js-item-source-name');
    const descriptionInput = row.querySelector('.js-item-description');
    const unitPriceInput = row.querySelector('.js-item-unit-price');

    if (!sourceGroup) {
      if (productIdInput) productIdInput.value = '';
      return;
    }

    const selected = sourceSelect ? sourceSelect.options[sourceSelect.selectedIndex] : null;
    if (!selected || !String(sourceSelect?.value || '').trim()) {
      if (sourceGroup === 'product' && productIdInput) {
        productIdInput.value = '';
      }
      return;
    }

    const readText = (value) => String(value || '').trim();

    if (sourceGroup === 'product') {
      if (productIdInput) productIdInput.value = selected.getAttribute('data-product-id') || '';

      const productName = selected.getAttribute('data-product-name') || 'Producto';
      const variantName = selected.getAttribute('data-variant-name') || 'Variante';
      const label = `${productName} - ${variantName}`;
      const price = Number(selected.getAttribute('data-price') || 0);

      if (nameInput && (forceWrite || !readText(nameInput.value))) {
        nameInput.value = label;
      }

      if (descriptionInput && (forceWrite || !readText(descriptionInput.value))) {
        descriptionInput.value = label;
      }

      if (unitPriceInput && (forceWrite || Number(unitPriceInput.value || 0) <= 0)) {
        const selectedCurrency = normalizeCurrencyCode(quotationCurrencySelect?.value || baseCurrencyCode);
        unitPriceInput.value = convertCurrencyAmount(price, baseCurrencyCode, selectedCurrency).toFixed(2);
      }

      return;
    }

    if (productIdInput) productIdInput.value = '';

    if (sourceGroup === 'service') {
      const serviceName = selected.getAttribute('data-service-name') || selected.textContent || 'Servicio';
      const serviceDescription = selected.getAttribute('data-description') || serviceName;
      const price = Number(selected.getAttribute('data-price') || 0);

      if (nameInput && (forceWrite || !readText(nameInput.value))) {
        nameInput.value = serviceName;
      }

      if (descriptionInput && (forceWrite || !readText(descriptionInput.value))) {
        descriptionInput.value = serviceDescription;
      }

      if (unitPriceInput && (forceWrite || Number(unitPriceInput.value || 0) <= 0)) {
        unitPriceInput.value = price.toFixed(2);
      }

      return;
    }

    if (sourceGroup === 'project') {
      const projectName = selected.getAttribute('data-project-name') || selected.textContent || 'Proyecto';
      const projectDescription = selected.getAttribute('data-description') || projectName;
      const price = Number(selected.getAttribute('data-price') || 0);

      if (nameInput && (forceWrite || !readText(nameInput.value))) {
        nameInput.value = projectName;
      }

      if (descriptionInput && (forceWrite || !readText(descriptionInput.value))) {
        descriptionInput.value = projectDescription;
      }

      if (unitPriceInput && (forceWrite || Number(unitPriceInput.value || 0) <= 0)) {
        unitPriceInput.value = price.toFixed(2);
      }
    }
  };

  const applyItemTypeState = (row, itemType, forceWrite = false) => {
    const sourceGroup = getItemSourceGroup(itemType);

    row.querySelectorAll('.quotation-item-source-field').forEach((field) => {
      field.classList.toggle('d-none', field.dataset.sourceType !== sourceGroup);
    });

    syncRowFieldNames(row);

    const activeSelect = getActiveSourceSelect(row, itemType);
    if (activeSelect && !activeSelect.dataset.select2Ready) {
      initSourceSelect2(activeSelect, sourceGroup);
      activeSelect.dataset.select2Ready = '1';
    }

    syncItemSourceToFields(row, forceWrite);
  };

  const setInitialSourceSelection = (row, defaults) => {
    const typeInput = row.querySelector('[data-name="item_type"]');
    const itemType = String(typeInput?.value || 'product');
    const sourceGroup = getItemSourceGroup(itemType);
    const sourceSelect = getActiveSourceSelect(row, itemType);
    if (!sourceSelect || !defaults) {
      return;
    }

    if (sourceGroup === 'product' && defaults.product_variant_id) {
      sourceSelect.value = String(defaults.product_variant_id);
      sourceSelect.dispatchEvent(new Event('change', { bubbles: true }));
      return;
    }

    const targetName = String(defaults.service_name || '').trim().toLowerCase();
    if (!targetName) {
      return;
    }

    const matchingOption = Array.from(sourceSelect.options).find((option) => {
      const optionText = String(option.textContent || '').trim().toLowerCase();
      const searchText = String(option.dataset.search || '').trim().toLowerCase();
      return optionText === targetName || optionText.includes(targetName) || searchText.includes(targetName);
    });

    if (matchingOption) {
      sourceSelect.value = matchingOption.value;
      sourceSelect.dispatchEvent(new Event('change', { bubbles: true }));
    }
  };

  const quotationType = document.getElementById('quotationType');
  const customerSelectWrap = document.getElementById('customerSelectWrap');
  const customerIdInput = document.getElementById('customerId');
  const customerNameWrap = document.getElementById('customerNameWrap');
  const customerEmailWrap = document.getElementById('customerEmailWrap');
  const customerPhoneWrap = document.getElementById('customerPhoneWrap');
  const customerDniWrap = document.getElementById('customerDniWrap');
  const createCustomerWrap = document.getElementById('createCustomerWrap');
  const createCustomerCheckbox = document.getElementById('createCustomer');
  const customerNameInput = document.getElementById('customerNameInput');
  const customerEmailInput = document.getElementById('customerEmailInput');
  const customerPhoneInput = document.getElementById('customerPhoneInput');
  const customerDniInput = document.getElementById('customerDniInput');
  const providerSelectWrap = document.getElementById('providerSelectWrap');
  const providerNameWrap = document.getElementById('providerNameWrap');

  const toggleQuotationTypeFields = () => {
    const isSupplier = quotationType && quotationType.value === 'supplier_request';
    const shouldRequireCustomerData = !isSupplier && ((!customerIdInput || !customerIdInput.value) || (createCustomerCheckbox && createCustomerCheckbox.checked));

    if (customerSelectWrap) customerSelectWrap.classList.toggle('d-none', isSupplier);
    if (customerNameWrap) customerNameWrap.classList.toggle('d-none', isSupplier);
    if (customerEmailWrap) customerEmailWrap.classList.toggle('d-none', isSupplier);
    if (customerPhoneWrap) customerPhoneWrap.classList.toggle('d-none', isSupplier);
    if (customerDniWrap) customerDniWrap.classList.toggle('d-none', isSupplier);
    if (createCustomerWrap) createCustomerWrap.classList.toggle('d-none', isSupplier);

    if (providerSelectWrap) providerSelectWrap.classList.toggle('d-none', !isSupplier);
    if (providerNameWrap) providerNameWrap.classList.toggle('d-none', !isSupplier);

    if (customerNameInput) customerNameInput.required = shouldRequireCustomerData;
    if (customerEmailInput) customerEmailInput.required = shouldRequireCustomerData;
    if (customerPhoneInput) customerPhoneInput.required = shouldRequireCustomerData;
    if (customerDniInput) customerDniInput.required = shouldRequireCustomerData;
  };

  if (quotationType) {
    quotationType.addEventListener('change', toggleQuotationTypeFields);
    toggleQuotationTypeFields();
  }

  createCustomerCheckbox?.addEventListener('change', toggleQuotationTypeFields);
  customerIdInput?.addEventListener('change', toggleQuotationTypeFields);

  const itemsBody = document.getElementById('quotationItemsBody');
  const template = document.getElementById('quotationItemTemplate');
  const addItemBtn = document.getElementById('addQuotationItemBtn');

  const initialItems = {{ \Illuminate\Support\Js::from($editingQuotationItems) }};

  const assignRowNames = (row) => {
    syncRowFieldNames(row);
  };

  const addItemRow = (defaults) => {
    if (!template || !itemsBody) return;

    const row = template.content.firstElementChild.cloneNode(true);
    assignRowNames(row);

    if (defaults) {
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
      if (quantityInput) quantityInput.value = String(defaults.quantity || 1);

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
    initItemTypeSelect2(typeInput);

    if (typeInput) {
      const onTypeChanged = function () {
        lastSelectedItemType = String(typeInput.value || 'product');
        persistLastSelectedItemType(lastSelectedItemType);
        syncRowFieldNames(row);
        applyItemTypeState(row, lastSelectedItemType, true);
      };

      typeInput.addEventListener('change', onTypeChanged);
      if (window.jQuery) {
        const $typeInput = window.jQuery(typeInput);
        $typeInput.off('change.shopixType select2:select.shopixType');
        $typeInput.on('change.shopixType select2:select.shopixType', onTypeChanged);
      }

      persistLastSelectedItemType(typeInput.value || lastSelectedItemType);
    }

    const sourceSelects = row.querySelectorAll('.js-item-source-select');
    sourceSelects.forEach((select) => {
      select.addEventListener('change', function () {
        syncItemSourceToFields(row, false);
      });
    });

    if (typeInput) {
      syncRowFieldNames(row);
      applyItemTypeState(row, typeInput.value || lastSelectedItemType, false);

      // Keep Select2 source in sync with the currently selected item type from first render.
      if (window.jQuery) {
        window.jQuery(typeInput).trigger('change.shopixType');
      }
    }

    if (defaults) {
      setInitialSourceSelection(row, defaults);
      syncItemSourceToFields(row, false);
    }

    const removeBtn = row.querySelector('.js-remove-item');
    if (removeBtn) removeBtn.addEventListener('click', function () {
      row.remove();
      if (itemsBody) {
        Array.from(itemsBody.querySelectorAll('tr')).forEach((remainingRow) => {
          syncRowFieldNames(remainingRow);
        });
      }
    });
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
