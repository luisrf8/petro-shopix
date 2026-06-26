@extends('layouts.app')

@section('title', 'Proyectos, Cotizaciones, Equipo y Nómina')

@section('content')
<div class="container-fluid py-2">
  @if(session('success'))
    <div class="alert alert-success text-white bg-gradient-success" role="alert">{{ session('success') }}</div>
  @endif

  @if($errors->any())
    <div class="alert alert-danger text-white bg-gradient-danger" role="alert">
      <strong>Se detectaron errores:</strong>
      <ul class="mb-0">
        @foreach($errors->all() as $error)
          <li>{{ $error }}</li>
        @endforeach
      </ul>
    </div>
  @endif

  <div class="card my-4">
    <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
      <div class="bg-gradient-dark shadow-dark border-radius-lg pt-3 pb-3 d-flex justify-content-between align-items-center">
        <h6 class="text-white text-capitalize ps-3 mb-0">Módulo de Proyectos</h6>
      </div>
    </div>

    <div class="card-body">
      <div class="row g-4">
        <div class="col-12 col-xl-6">
          <div class="card border">
            <div class="card-header pb-0"><h6 class="mb-0">Crear Proyecto</h6></div>
            <div class="card-body">
              <form method="POST" action="{{ route('projects.module.projects.store') }}" class="row g-3">
                @csrf
                <div class="col-12">
                  <label class="form-label">Nombre del proyecto</label>
                  <input type="text" name="name" class="form-control border border-1 p-2" required>
                </div>
                <div class="col-md-4">
                  <label class="form-label">Fase</label>
                  <select name="phase" class="form-control border border-1 p-2" required>
                    <option value="inicio">Inicio</option>
                    <option value="desarrollo">Desarrollo</option>
                    <option value="fin">Fin</option>
                  </select>
                </div>
                <div class="col-md-4"><label class="form-label">Fecha inicio</label><input type="date" name="starts_at" class="form-control border border-1 p-2"></div>
                <div class="col-md-4"><label class="form-label">Fecha desarrollo</label><input type="date" name="development_at" class="form-control border border-1 p-2"></div>
                <div class="col-md-4"><label class="form-label">Fecha fin</label><input type="date" name="ends_at" class="form-control border border-1 p-2"></div>
                <div class="col-md-4"><label class="form-label">Presupuesto</label><input type="number" name="budget_amount" min="0" step="0.01" class="form-control border border-1 p-2" placeholder="0.00"></div>
                <div class="col-md-4">
                  <label class="form-label">Moneda</label>
                  <select name="currency_code" class="form-control border border-1 p-2">
                    <option value="{{ $baseCurrencyCode ?? 'USD' }}">{{ $baseCurrencyCode ?? 'USD' }}</option>
                    <option value="USD">USD</option>
                    <option value="EUR">EUR</option>
                    <option value="BS">BS</option>
                  </select>
                </div>
                <div class="col-12">
                  <label class="form-label">Asociar cotización (opcional)</label>
                  <select name="quotation_id" class="form-control border border-1 p-2">
                    <option value="">Sin cotización</option>
                    @foreach($quotations as $quotation)
                      <option value="{{ $quotation->id }}">#{{ $quotation->id }} - {{ $quotation->title }}</option>
                    @endforeach
                  </select>
                </div>
                <div class="col-12"><label class="form-label">Descripción / notas</label><textarea name="description" rows="2" class="form-control border border-1 p-2"></textarea></div>
                <div class="col-12 text-end"><button type="submit" class="btn btn-dark mb-0">Guardar proyecto</button></div>
              </form>
            </div>
          </div>
        </div>

        <div class="col-12 col-xl-6">
          <div class="card border">
            <div class="card-header pb-0"><h6 class="mb-0">Crear Cotización</h6></div>
            <div class="card-body">
              <form method="POST" action="{{ route('projects.module.quotations.store') }}" id="quotationForm" class="row g-3">
                @csrf
                <div class="col-md-4">
                  <label class="form-label">Tipo</label>
                  <select name="type" id="quotationType" class="form-control border border-1 p-2" required>
                    <option value="customer">Cotización cliente</option>
                    <option value="supplier_request">Solicitud a proveedor</option>
                  </select>
                </div>
                <div class="col-md-8"><label class="form-label">Título</label><input type="text" name="title" class="form-control border border-1 p-2" required></div>

                <div class="col-md-6" id="customerNameWrap"><label class="form-label">Cliente</label><input type="text" name="customer_name" class="form-control border border-1 p-2"></div>
                <div class="col-md-6" id="customerEmailWrap"><label class="form-label">Correo cliente</label><input type="email" name="customer_email" class="form-control border border-1 p-2"></div>
                <div class="col-md-6 d-none" id="providerSelectWrap">
                  <label class="form-label">Proveedor registrado</label>
                  <select name="provider_id" class="form-control border border-1 p-2">
                    <option value="">Selecciona un proveedor</option>
                    @foreach($providers as $provider)
                      <option value="{{ $provider->id }}">{{ $provider->name }}</option>
                    @endforeach
                  </select>
                </div>
                <div class="col-md-6 d-none" id="providerNameWrap"><label class="form-label">Proveedor externo</label><input type="text" name="provider_name" class="form-control border border-1 p-2"></div>

                <div class="col-md-3"><label class="form-label">Desc. global %</label><input type="number" name="discount_percent" min="0" max="100" step="0.01" value="0" class="form-control border border-1 p-2"></div>
                <div class="col-md-3">
                  <label class="form-label">Moneda</label>
                  <select name="currency_code" class="form-control border border-1 p-2">
                    <option value="{{ $baseCurrencyCode ?? 'USD' }}">{{ $baseCurrencyCode ?? 'USD' }}</option>
                    <option value="USD">USD</option>
                    <option value="EUR">EUR</option>
                    <option value="BS">BS</option>
                  </select>
                </div>
                <div class="col-md-3"><label class="form-label">Estado</label><select name="status" class="form-control border border-1 p-2"><option value="draft">Borrador</option><option value="sent">Enviada</option><option value="approved">Aprobada</option><option value="rejected">Rechazada</option></select></div>
                <div class="col-md-3"><label class="form-label">Válida hasta</label><input type="date" name="valid_until" class="form-control border border-1 p-2"></div>

                <div class="col-12">
                  <div class="table-responsive border rounded">
                    <table class="table table-sm mb-0">
                      <thead>
                        <tr>
                          <th style="min-width: 280px;">Producto / Variante</th>
                          <th style="min-width: 180px;">Servicio</th>
                          <th style="min-width: 200px;">Descripción</th>
                          <th style="min-width: 120px;">Cantidad</th>
                          <th style="min-width: 140px;">Precio unitario</th>
                          <th style="min-width: 120px;">Desc. %</th>
                          <th style="min-width: 60px;"></th>
                        </tr>
                      </thead>
                      <tbody id="quotationItemsBody"></tbody>
                    </table>
                  </div>
                  <button type="button" class="btn btn-outline-dark btn-sm mt-2 mb-0" id="addQuotationItemBtn">+ Agregar ítem</button>
                </div>

                <div class="col-12"><label class="form-label">Notas</label><textarea name="notes" rows="2" class="form-control border border-1 p-2"></textarea></div>
                <div class="col-12 text-end"><button type="submit" class="btn btn-dark mb-0">Guardar cotización</button></div>
              </form>
            </div>
          </div>
        </div>

        <div class="col-12">
          <div class="card border">
            <div class="card-header pb-0"><h6 class="mb-0">Resumen</h6></div>
            <div class="card-body">
              <div class="table-responsive mb-4">
                <h6 class="text-sm">Proyectos recientes</h6>
                <table class="table table-sm align-items-center mb-0">
                  <thead><tr><th>Proyecto</th><th>Fase</th><th>Fechas</th><th>Presupuesto</th><th>Actualizar fase</th></tr></thead>
                  <tbody>
                    @forelse($projects as $project)
                      <tr>
                        <td>{{ $project->name }}</td>
                        <td><span class="badge bg-dark">{{ strtoupper($project->phase) }}</span></td>
                        <td>I: {{ optional($project->starts_at)->format('d/m/Y') ?: '-' }} | D: {{ optional($project->development_at)->format('d/m/Y') ?: '-' }} | F: {{ optional($project->ends_at)->format('d/m/Y') ?: '-' }}</td>
                        <td>{{ number_format((float) $project->budget_amount, 2) }} {{ $project->currency_code }}</td>
                        <td>
                          <form method="POST" action="{{ route('projects.module.projects.phase', $project) }}" class="d-flex gap-2">
                            @csrf
                            <select name="phase" class="form-control border border-1 p-2 form-control-sm"><option value="inicio" {{ $project->phase === 'inicio' ? 'selected' : '' }}>Inicio</option><option value="desarrollo" {{ $project->phase === 'desarrollo' ? 'selected' : '' }}>Desarrollo</option><option value="fin" {{ $project->phase === 'fin' ? 'selected' : '' }}>Fin</option></select>
                            <button type="submit" class="btn btn-outline-dark btn-sm mb-0">Guardar</button>
                          </form>
                        </td>
                      </tr>
                    @empty
                      <tr><td colspan="5" class="text-center text-muted">Sin proyectos registrados.</td></tr>
                    @endforelse
                  </tbody>
                </table>
              </div>

              <div class="table-responsive mb-4">
                <h6 class="text-sm">Cotizaciones recientes</h6>
                <table class="table table-sm align-items-center mb-0">
                  <thead><tr><th>#</th><th>Tipo</th><th>Título</th><th>Cliente / Proveedor</th><th>Total</th><th>PDF</th></tr></thead>
                  <tbody>
                    @forelse($quotations as $quotation)
                      <tr>
                        <td>{{ $quotation->id }}</td>
                        <td>{{ $quotation->type === 'supplier_request' ? 'Solicitud proveedor' : 'Cliente' }}</td>
                        <td>{{ $quotation->title }}</td>
                        <td>{{ $quotation->customer_name ?: ($quotation->provider_name ?: optional($quotation->provider)->name ?: '-') }}</td>
                        <td>{{ number_format((float) $quotation->total_amount, 2) }} {{ $quotation->currency_code }}</td>
                        <td><a href="{{ route('projects.module.quotations.pdf', $quotation) }}" target="_blank" class="btn btn-outline-dark btn-sm mb-0">Ver PDF</a></td>
                      </tr>
                    @empty
                      <tr><td colspan="6" class="text-center text-muted">Sin cotizaciones registradas.</td></tr>
                    @endforelse
                  </tbody>
                </table>
              </div>

              <div class="row g-4">
                <div class="col-12 col-xl-6">
                  <h6 class="text-sm">Equipo de trabajo</h6>
                  <form method="POST" action="{{ route('projects.module.team.store') }}" class="row g-3 mb-3">@csrf
                    <div class="col-md-6"><label class="form-label">Usuario existente (opcional)</label><select name="user_id" class="form-control border border-1 p-2"><option value="">Sin usuario asociado</option>@foreach($users as $user)<option value="{{ $user->id }}">{{ $user->name }} {{ $user->email ? '(' . $user->email . ')' : '' }}</option>@endforeach</select></div>
                    <div class="col-md-6"><label class="form-label">Nombre (si no tiene usuario)</label><input type="text" name="full_name" class="form-control border border-1 p-2"></div>
                    <div class="col-md-4"><label class="form-label">Rol</label><input type="text" name="role" class="form-control border border-1 p-2"></div>
                    <div class="col-md-4"><label class="form-label">Correo</label><input type="email" name="email" class="form-control border border-1 p-2"></div>
                    <div class="col-md-4"><label class="form-label">Teléfono</label><input type="text" name="phone" class="form-control border border-1 p-2"></div>
                    <div class="col-12"><label class="form-label">Notas</label><textarea name="notes" rows="2" class="form-control border border-1 p-2"></textarea></div>
                    <div class="col-12 text-end"><button type="submit" class="btn btn-dark mb-0">Agregar integrante</button></div>
                  </form>
                  <div class="table-responsive"><table class="table table-sm align-items-center mb-0"><thead><tr><th>Nombre</th><th>Rol</th><th>Contacto</th><th>Estado</th></tr></thead><tbody>@forelse($teamMembers as $member)<tr><td>{{ $member->full_name }}</td><td>{{ $member->role ?: '-' }}</td><td>{{ $member->email ?: '-' }} {{ $member->phone ? '| ' . $member->phone : '' }}</td><td><span class="badge {{ $member->is_active ? 'bg-success' : 'bg-secondary' }}">{{ $member->is_active ? 'Activo' : 'Inactivo' }}</span></td></tr>@empty<tr><td colspan="4" class="text-center text-muted">Sin integrantes registrados.</td></tr>@endforelse</tbody></table></div>
                </div>

                <div class="col-12 col-xl-6">
                  <h6 class="text-sm">Nómina simple</h6>
                  <form method="POST" action="{{ route('projects.module.payrolls.store') }}" class="row g-3 mb-3">@csrf
                    <div class="col-md-4"><label class="form-label">Tipo pago</label><select name="payment_type" class="form-control border border-1 p-2" required><option value="daily">Diario</option><option value="weekly">Semanal</option><option value="monthly">Mensual</option><option value="contract">Por contrato</option></select></div>
                    <div class="col-md-4"><label class="form-label">Monto</label><input type="number" name="amount" min="0.01" step="0.01" class="form-control border border-1 p-2" required></div>
                    <div class="col-md-4"><label class="form-label">Moneda</label><select name="currency_code" class="form-control border border-1 p-2"><option value="{{ $baseCurrencyCode ?? 'USD' }}">{{ $baseCurrencyCode ?? 'USD' }}</option><option value="USD">USD</option><option value="EUR">EUR</option><option value="BS">BS</option></select></div>
                    <div class="col-md-6"><label class="form-label">Integrante (opcional)</label><select name="team_member_id" class="form-control border border-1 p-2"><option value="">Sin integrante asociado</option>@foreach($teamMembers as $member)<option value="{{ $member->id }}">{{ $member->full_name }}</option>@endforeach</select></div>
                    <div class="col-md-6"><label class="form-label">Proyecto (opcional)</label><select name="project_id" class="form-control border border-1 p-2"><option value="">Sin proyecto asociado</option>@foreach($projects as $project)<option value="{{ $project->id }}">{{ $project->name }}</option>@endforeach</select></div>
                    <div class="col-md-4"><label class="form-label">Fecha pago</label><input type="date" name="paid_at" class="form-control border border-1 p-2" required></div>
                    <div class="col-md-8"><label class="form-label">Notas</label><input type="text" name="notes" class="form-control border border-1 p-2"></div>
                    <div class="col-12 text-end"><button type="submit" class="btn btn-dark mb-0">Registrar pago</button></div>
                  </form>
                  <div class="table-responsive"><table class="table table-sm align-items-center mb-0"><thead><tr><th>Fecha</th><th>Tipo</th><th>Integrante</th><th>Proyecto</th><th>Monto</th></tr></thead><tbody>@forelse($payrollEntries as $payroll)<tr><td>{{ optional($payroll->paid_at)->format('d/m/Y') }}</td><td>{{ strtoupper($payroll->payment_type) }}</td><td>{{ $payroll->teamMember->full_name ?? '-' }}</td><td>{{ $payroll->project->name ?? '-' }}</td><td>{{ number_format((float) $payroll->amount, 2) }} {{ $payroll->currency_code }}</td></tr>@empty<tr><td colspan="5" class="text-center text-muted">Sin pagos registrados.</td></tr>@endforelse</tbody></table></div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</div>

<template id="quotationItemTemplate">
  <tr>
    <td>
      <input type="hidden" class="js-item-product-id" value="">
      <select class="form-control border border-1 p-2 js-item-variant" data-name="product_variant_id">
        <option value="">Ítem libre (sin producto)</option>
        @foreach($productVariants as $variant)
          <option value="{{ $variant->id }}" data-product-id="{{ $variant->product_id }}" data-product-name="{{ $variant->product->name ?? 'Producto' }}" data-variant-name="{{ $variant->size }}" data-price="{{ number_format((float) $variant->price, 2, '.', '') }}">{{ $variant->product->name ?? 'Producto' }} - {{ $variant->size }}</option>
        @endforeach
      </select>
    </td>
    <td><input type="text" class="form-control border border-1 p-2" data-name="service_name" placeholder="Servicio opcional"></td>
    <td><input type="text" class="form-control border border-1 p-2 js-item-description" data-name="description" placeholder="Detalle del ítem" required></td>
    <td><input type="number" min="0.0001" step="0.01" class="form-control border border-1 p-2" data-name="quantity" value="1" required></td>
    <td><input type="number" min="0" step="0.01" class="form-control border border-1 p-2 js-item-unit-price" data-name="unit_price" value="0" required></td>
    <td><input type="number" min="0" max="100" step="0.01" class="form-control border border-1 p-2" data-name="discount_percent" value="0"></td>
    <td><button type="button" class="btn btn-outline-danger btn-sm mb-0 js-remove-item">X</button></td>
  </tr>
</template>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function () {
  const quotationType = document.getElementById('quotationType');
  const customerNameWrap = document.getElementById('customerNameWrap');
  const customerEmailWrap = document.getElementById('customerEmailWrap');
  const providerSelectWrap = document.getElementById('providerSelectWrap');
  const providerNameWrap = document.getElementById('providerNameWrap');

  const toggleQuotationTypeFields = () => {
    const isSupplierRequest = quotationType?.value === 'supplier_request';
    customerNameWrap?.classList.toggle('d-none', isSupplierRequest);
    customerEmailWrap?.classList.toggle('d-none', isSupplierRequest);
    providerSelectWrap?.classList.toggle('d-none', !isSupplierRequest);
    providerNameWrap?.classList.toggle('d-none', !isSupplierRequest);
  };

  quotationType?.addEventListener('change', toggleQuotationTypeFields);
  toggleQuotationTypeFields();

  const itemsBody = document.getElementById('quotationItemsBody');
  const template = document.getElementById('quotationItemTemplate');
  const addItemBtn = document.getElementById('addQuotationItemBtn');

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

  const syncVariantToFields = (row) => {
    const variantSelect = row.querySelector('.js-item-variant');
    const descriptionInput = row.querySelector('.js-item-description');
    const unitPriceInput = row.querySelector('.js-item-unit-price');
    const productIdInput = row.querySelector('.js-item-product-id');

    const selected = variantSelect?.options[variantSelect.selectedIndex];
    if (!selected || !String(variantSelect?.value || '').trim()) {
      if (productIdInput) {
        productIdInput.value = '';
      }
      return;
    }

    const productId = selected.getAttribute('data-product-id') || '';
    const productName = selected.getAttribute('data-product-name') || 'Producto';
    const variantName = selected.getAttribute('data-variant-name') || 'Variante';
    const price = selected.getAttribute('data-price') || '0';

    if (productIdInput) {
      productIdInput.value = productId;
    }

    if (descriptionInput && !String(descriptionInput.value || '').trim()) {
      descriptionInput.value = `${productName} - ${variantName}`;
    }

    if (unitPriceInput && Number(unitPriceInput.value || 0) <= 0) {
      unitPriceInput.value = Number(price).toFixed(2);
    }
  };

  const addItemRow = () => {
    if (!template || !itemsBody) {
      return;
    }

    const row = template.content.firstElementChild.cloneNode(true);
    const index = itemsBody.querySelectorAll('tr').length;
    assignRowNames(row, index);
    itemsBody.appendChild(row);

    row.querySelector('.js-item-variant')?.addEventListener('change', () => syncVariantToFields(row));
    row.querySelector('.js-remove-item')?.addEventListener('click', () => row.remove());
  };

  addItemBtn?.addEventListener('click', addItemRow);

  if ((itemsBody?.querySelectorAll('tr').length || 0) === 0) {
    addItemRow();
  }
});
</script>
@endpush
