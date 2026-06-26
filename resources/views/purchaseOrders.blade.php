@extends('layouts.app')

@section('title', 'Categorías')

@php
  $purchaseOrdersTenant = ($purchaseOrdersTenantId = (int) (auth()->user()->tenant_id ?? 0)) > 0
    ? \App\Models\Tenant::find($purchaseOrdersTenantId)
    : null;
  $purchaseOrdersCapabilities = \App\Support\TenantPlanCapabilities::forTenant($purchaseOrdersTenant);
  $purchaseOrdersFreePlan = !$purchaseOrdersCapabilities->canGeneratePurchase();
@endphp

@section('content')
    <div class="container-fluid py-2">
      <div class="row mt-4">
        <div class="col-12">
            <div class="card">
              <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                <div class="bg-gradient-dark shadow-dark border-radius-lg pt-4 pb-3 d-flex justify-content-between align-items-center">
                  <h6 class="text-white text-capitalize ps-3">ORDENES DE COMPRA REALIZADAS</h6>
                  <div class="py-1 px-3 text-end admin-mobile-actions">
                    @unless($purchaseOrdersFreePlan)
                      <label class="text-white admin-mobile-action-trigger" data-bs-toggle="modal" data-bs-target="#reportModal">
                        + Generar Reporte
                      </label>
                      <a class="text-white admin-mobile-action-trigger" href="/purchase">
                        + Generar Compra
                      </a>
                    @endunless
                  </div>
                </div>
              </div> 
              <div class="card-body">
                <div class="mb-3">
                  <input
                    type="text"
                    id="purchaseOrdersSearch"
                    class="form-control border border-1 p-2 bg-white"
                    placeholder="Buscar por orden, proveedor, almacén o tipo..."
                  >
                </div>
                <div class="table-responsive">
                  <table class="table align-items-center mb-0">
                    <thead class="text-center">
                      <tr>
                        <th>Vista</th>
                        <th># Orden</th>
                        <th>Fecha</th>
                        <th>Almacén</th>
                        <th>Tipo</th>
                        <th>Proveedor</th>
                        <th># Variantes</th>
                        <th># Productos</th>
                        <th>Total ({{ $baseCurrencyCode ?? 'USD' }})</th>
                        <th>Acciones</th>
                      </tr>
                    </thead>
                    <tbody class="text-center" id="purchaseOrdersTableBody">
                      @foreach($purchaseOrders as $order)
                        <tr data-search="{{ \Illuminate\Support\Str::lower(collect([
                          $order->id,
                          $order->date,
                          optional($order->warehouse)->name,
                          $order->entry_mode_label,
                          $order->provider_display_name,
                        ])->filter()->implode(' ')) }}">
                          <td>
                            <img src="{{ $order->preview_image }}" alt="preview" style="width:48px;height:48px;object-fit:cover;border-radius:8px;">
                          </td>
                          <td>{{ $order->id }}</td>
                          <td>{{ $order->date }}</td>
                          <td>{{ $order->warehouse->name ?? 'N/A' }}</td>
                          <td>{{ $order->entry_mode_label ?? 'Compra' }}</td>
                          <td>{{ $order->provider_display_name }}</td>
                          <td>{{ $order->total_variants }}</td>
                          <td>{{ $order->total_items }}</td>
                          <td>{{ number_format($order->total_amount, 2) }}</td>
                          <td>
                            <a href="/order/{{ $order->id }}" class="text-secondary font-weight-bold text-xs toggle-status-btn admin-mobile-action-trigger">Ver Detalles</a>
                          </td>
                        </tr>
                      @endforeach
                    </tbody>
                  </table>
                </div>
              </div>
            </div>
        </div>
      </div>
      </div>
    </div>
    @endsection

@push('scripts')
<script src="{{ asset('assets/js/core/popper.min.js') }}"></script>
<script src="{{ asset('assets/js/core/bootstrap.min.js') }}"></script>

<!-- Github buttons -->
<!-- <script async defer src="https://buttons.github.io/buttons.js"></script> -->

<!-- Control Center for Material Dashboard: parallax effects, scripts for the example pages etc -->
  <script>
    const normalizeSearchText = (value) => String(value || '')
      .toLowerCase()
      .normalize('NFD')
      .replace(/[\u0300-\u036f]/g, '')
      .trim();

    const purchaseOrdersSearchInput = document.getElementById('purchaseOrdersSearch');
    const purchaseOrdersRows = document.querySelectorAll('#purchaseOrdersTableBody tr');

    if (purchaseOrdersSearchInput) {
      purchaseOrdersSearchInput.addEventListener('input', function () {
        const searchValue = normalizeSearchText(this.value);

        purchaseOrdersRows.forEach((row) => {
          const searchableText = normalizeSearchText(row.getAttribute('data-search'));
          row.style.display = searchableText.includes(searchValue) ? '' : 'none';
        });
      });
    }
  </script>
@endpush