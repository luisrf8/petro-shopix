@extends('layouts.app')

@section('title', 'Categorías')

@php
  $salesOrdersTenant = ($salesOrdersTenantId = (int) (auth()->user()->tenant_id ?? 0)) > 0
    ? \App\Models\Tenant::find($salesOrdersTenantId)
    : null;
  $salesOrdersCapabilities = \App\Support\TenantPlanCapabilities::forTenant($salesOrdersTenant);
  $salesOrdersFreePlan = !$salesOrdersCapabilities->canGenerateSalesReport();
@endphp

@push('styles')
<style>
  .sales-orders-filters {
    border: 1px solid #e2e8f0;
    border-radius: 0.85rem;
    padding: 0.75rem;
    background: #f8fafc;
    margin-bottom: 0.85rem;
  }

  .sales-orders-table-wrap {
    overflow-x: auto;
    overflow-y: hidden;
    -webkit-overflow-scrolling: touch;
    touch-action: pan-x;
    max-width: 100%;
  }

  #salesOrdersTable {
    min-width: 1080px;
  }

  #salesOrdersTable tbody tr:nth-child(odd) {
    background-color: #f3f4f6;
  }

  #salesOrdersTable tbody tr:nth-child(even) {
    background-color: #ffffff;
  }

  @media (max-width: 991.98px) {
    .sales-orders-table-wrap {
      overflow-x: scroll !important;
      scrollbar-width: thin;
    }

    .sales-orders-table-wrap::-webkit-scrollbar {
      height: 6px;
    }

    .sales-orders-table-wrap::-webkit-scrollbar-thumb {
      background: #9ca3af;
      border-radius: 999px;
    }
  }
</style>
@endpush

@section('content')
    <div class="container-fluid py-2">
      <div class="row mt-4">
        <div class="col-12">
            <div class="card">
              <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                <div class="bg-gradient-dark shadow-dark border-radius-lg pt-4 pb-3 d-flex justify-content-between align-items-center">
                  <h6 class="text-white text-capitalize ps-3">{{ $pageTitle ?? 'VENTAS REALIZADAS' }}</h6>
                  <div class="py-1 px-3 text-end admin-mobile-actions">
                    @if(!($isPendingDeliveryView ?? false) && ($canApprovePayments ?? true) && !$salesOrdersFreePlan)
                      <label class="text-white admin-mobile-action-trigger"  data-bs-toggle="modal" data-bs-target="#reportModal">
                        + Generar Reporte
                      </label>
                    @endif
                    @if(!($isPendingDeliveryView ?? false) && (($canApprovePayments ?? false) || ($canDeliverOrders ?? false)))
                      <a class="text-white ms-6 admin-mobile-action-trigger" href="/sales">
                        + Generar Venta
                      </a>
                    @endif
                  </div>
                </div>
              </div> 
              <div class="card-body">
                <div class="sales-orders-filters">
                  <div class="row g-2">
                    <div class="col-12 col-md-4">
                      <input type="text" id="salesOrdersSearchInput" class="form-control border border-1 p-2" placeholder="Buscar por orden, factura, usuario o fecha...">
                    </div>
                    <div class="col-6 col-md-2">
                      <select id="salesOrdersStatusFilter" class="form-control border border-1 p-2">
                        <option value="">Estado (todos)</option>
                        <option value="En Proceso">En Proceso</option>
                        <option value="Aprobado">Aprobado</option>
                        <option value="Negado">Negado</option>
                      </select>
                    </div>
                    <div class="col-6 col-md-2">
                      <select id="salesOrdersDeliveryFilter" class="form-control border border-1 p-2">
                        <option value="">Entrega (todas)</option>
                        <option value="Tienda">Tienda</option>
                        <option value="delivery">delivery</option>
                      </select>
                    </div>
                    <div class="col-6 col-md-2">
                      <select id="salesOrdersDocumentFilter" class="form-control border border-1 p-2">
                        <option value="">Documento (todos)</option>
                        <option value="Factura digital">Factura digital</option>
                        <option value="Orden de entrega">Orden de entrega</option>
                      </select>
                    </div>
                    <div class="col-6 col-md-2">
                      <button type="button" id="salesOrdersClearFilters" class="btn btn-outline-dark w-100 mb-0">Limpiar</button>
                    </div>
                  </div>
                </div>

                <div class="table-responsive sales-orders-table-wrap">
                  <table class="table align-items-center mb-0" id="salesOrdersTable">
                    <thead class="text-center">
                      <tr>
                        <th># Orden</th>
                        <th># Factura</th>
                        <th>Fecha</th>
                        <th>Usuario</th>
                        <th>Entrega</th>
                        <th># Productos</th>
                        <th>Documento</th>
                        <th>Factura fiscal</th>
                        <th>Estado</th>
                        <th>Devolución</th>
                        <th>Acciones</th>
                      </tr>
                    </thead>
                    <tbody class="text-center" id="salesOrdersTableBody">
                      @foreach($salesOrders as $order)
                        @php
                          $edoc = $order->latest_electronic_document;
                          $invoiceNumber = $edoc ? (string) ($edoc->numero_documento ?: '-') : '-';
                          $documentMode = (string) ($order->document_issue_mode ?? 'delivery_note');
                          $documentLabel = $documentMode === 'electronic_invoice' ? 'Factura digital' : 'Orden de entrega';
                          $statusLabel = $order->status == 0 ? 'En Proceso' : ($order->status == 1 ? 'Aprobado' : ($order->status == 2 ? 'Negado' : ''));
                          $userLabel = $order->user ? $order->user->name : 'Usuario no asignado';
                          $deliveryLabel = (string) ($order->preference ?? '');
                        @endphp
                        <tr>
                          <td>
                            <div class="fw-semibold">#{{ $order->id }}</div>
                            @if($order->has_annulled_invoice ?? false)
                              <span class="badge bg-gradient-danger mt-1">Orden con factura anulada</span>
                            @endif
                          </td>
                          <td>
                            @if($edoc)
                              <div class="fw-semibold">{{ $edoc->numero_documento ?: '-' }}</div>
                              @if($edoc->is_annulled)
                                <span class="badge bg-gradient-danger mt-1">Anulada</span>
                              @else
                                <span class="badge bg-gradient-success mt-1">Activa</span>
                              @endif
                            @else
                              <span class="text-muted">-</span>
                            @endif
                          </td>
                          <td>{{ $order->date }}</td>
                          <td>{{ $order->user ? $order->user->name : 'Usuario no asignado' }}</td>
                          <td class="text-center">
                            <span class="badge badge-sm  {{ $order->preference == 'Tienda' ? 'bg-gradient-secondary' : 'bg-gradient-info' }}">{{ $order->preference }}
                            </span>
                          </td>
                          <td>{{ $order->total_items }}</td>
                          <td>
                            @php
                              $mode = (string) ($order->document_issue_mode ?? 'delivery_note');
                            @endphp
                            <span class="badge badge-sm {{ $mode === 'electronic_invoice' ? 'bg-gradient-success' : 'bg-gradient-secondary' }}">
                              {{ $mode === 'electronic_invoice' ? 'Factura digital' : 'Orden de entrega' }}
                            </span>
                          </td>
                          <td>
                            @if($edoc)
                              <span class="badge badge-sm {{ $edoc->is_annulled ? 'bg-gradient-danger' : 'bg-gradient-success' }}">
                                {{ $edoc->is_annulled ? 'Anulada' : 'Vigente' }}
                              </span>
                            @else
                              <span class="badge badge-sm bg-gradient-secondary">Sin emitir</span>
                            @endif
                          </td>
                          <td class="text-center">
                            <span class="badge badge-sm
                              {{ $order->status == '0' ? 'bg-gradient-warning' :
                                ($order->status == '1' ? 'bg-gradient-success' :
                                ($order->status == '2' ? 'bg-gradient-danger' : '') ) }}">
                              {{ $order->status == 0 ? 'En Proceso' :
                                ($order->status == 1 ? 'Aprobado' :
                                ($order->status == 2 ? 'Negado' : '')) }}
                            </span>
                          </td>
                          <td>
                            @if($order->has_returns)
                              <span class="text-danger">Con Devolución</span>
                            @else
                              <span class=""></span>
                            @endif
                          </td>
                          <td>
                            <a href="/sales/{{ $order->id }}" class="text-secondary font-weight-bold text-xs toggle-status-btn admin-mobile-action-trigger">Ver Detalles</a>
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

@if(!($isPendingDeliveryView ?? false) && ($canApprovePayments ?? true) && !$salesOrdersFreePlan)
<!-- Modal para generar reporte -->
<div class="modal fade" id="reportModal" tabindex="-1" aria-labelledby="reportModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="reportModalLabel">Generar Reporte de Ventas</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <form id="reportForm">
                    @csrf
                    <div class="mb-3">
                        <label for="range" class="form-label">Seleccionar Rango de Fechas</label>
                        <select id="range" name="range" class="form-control border border-radius-lg p-2">
                            <option value="weekly">Semanal</option>
                            <option value="monthly" selected>Mensual</option>
                            <option value="quarterly">Trimestral</option>
                            <option value="yearly">Anual</option>
                        </select>
                    </div>
                    <div class="d-flex justify-content-end">
                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                        <button onclick="getReport()" class="btn btn-dark ms-2">Generar Reporte</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endif
    @endsection

@push('scripts')
<script src="{{ asset('assets/js/core/popper.min.js') }}"></script>
<script src="{{ asset('assets/js/core/bootstrap.min.js') }}"></script>

<!-- Github buttons -->
<!-- <script async defer src="https://buttons.github.io/buttons.js"></script> -->

<!-- Control Center for Material Dashboard: parallax effects, scripts for the example pages etc -->
  <script>
    function setupSalesOrdersFilters() {
      const tableBody = document.getElementById('salesOrdersTableBody');
      const searchInput = document.getElementById('salesOrdersSearchInput');
      const statusFilter = document.getElementById('salesOrdersStatusFilter');
      const deliveryFilter = document.getElementById('salesOrdersDeliveryFilter');
      const documentFilter = document.getElementById('salesOrdersDocumentFilter');
      const clearBtn = document.getElementById('salesOrdersClearFilters');

      if (!tableBody || !searchInput || !statusFilter || !deliveryFilter || !documentFilter || !clearBtn) {
        return;
      }

      const rows = Array.from(tableBody.querySelectorAll('tr'));

      const normalize = (value) => String(value || '').toLowerCase().trim();

      const applyFilters = () => {
        const query = normalize(searchInput.value);
        const statusValue = normalize(statusFilter.value);
        const deliveryValue = normalize(deliveryFilter.value);
        const documentValue = normalize(documentFilter.value);

        rows.forEach((row) => {
          const rowText = normalize(row.textContent);
          const cells = row.querySelectorAll('td');

          const statusText = normalize(cells[8]?.textContent || '');
          const deliveryText = normalize(cells[4]?.textContent || '');
          const documentText = normalize(cells[6]?.textContent || '');

          const matchesQuery = !query || rowText.includes(query);
          const matchesStatus = !statusValue || statusText.includes(statusValue);
          const matchesDelivery = !deliveryValue || deliveryText.includes(deliveryValue);
          const matchesDocument = !documentValue || documentText.includes(documentValue);

          row.style.display = matchesQuery && matchesStatus && matchesDelivery && matchesDocument ? '' : 'none';
        });
      };

      const clearFilters = () => {
        searchInput.value = '';
        statusFilter.value = '';
        deliveryFilter.value = '';
        documentFilter.value = '';
        applyFilters();
      };

      searchInput.addEventListener('input', applyFilters);
      statusFilter.addEventListener('change', applyFilters);
      deliveryFilter.addEventListener('change', applyFilters);
      documentFilter.addEventListener('change', applyFilters);
      clearBtn.addEventListener('click', clearFilters);
    }

    document.addEventListener('DOMContentLoaded', setupSalesOrdersFilters);

    document.getElementById('createProductForm')?.addEventListener('submit', function(event) {
      event.preventDefault(); // Evita el envío normal del formulario

      let formData = new FormData(this); // Crear un FormData con los datos del formulario
      const token = localStorage.getItem('authToken');
      fetch('api/create-product', {
        method: 'POST',
        headers: {
          // 'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
          'Authorization': `Bearer ${token}`
          
        },
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.message === 'Product created successfully') {
          alert('Producto creado correctamente');
          // Cierra el modal y refresca o actualiza el contenido
          $('#createProductModal').modal('hide');
          // Aquí puedes añadir lógica para actualizar la lista de productos si existe
        } else {
          alert('Ocurrió un error al crear el producto');
        }
      })
      .catch(error => console.error('Error:', error));
    });
    
    function getReport() {
        if (!document.getElementById('reportModal')) {
          return;
        }
        event.preventDefault();
        const range = document.getElementById('range').value;
        fetch('api/sales-orders-report', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({ range: range })
        })
        .then(response => response.json()) // <--- AQUI conviertes la respuesta a JSON
        .then(data => {
            // Aquí puedes manejar la respuesta del servidor
            console.log('Reporte generado:', data.response);
            if(data.pdf_url) {
              const link = document.createElement("a");
              link.href = data.pdf_url;
              console.log('PDF URL:', data.pdf_url);
              link.download = `reporte_ordenes_ventas_${data.fecha}.pdf`;
              document.body.appendChild(link);
              link.click();
              document.body.removeChild(link);
            }
            console.log('PDF URL:', data.pdf_url);
            alert('Reporte generado con éxito');
        })
        .catch(error => {
            console.error('Error al generar el reporte:', error);
            alert('Ocurrió un error al generar el reporte');
        });
    }

    document.getElementById('createCategoryForm')?.addEventListener('submit', function(event) {
      event.preventDefault(); // Evita el envío normal del formulario

      let formData = new FormData(this); // Crear un FormData con los datos del formulario
      const token = localStorage.getItem('authToken');
      fetch('api/create-category', {
        method: 'POST',
        headers: {
          // 'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
          'Authorization': `Bearer ${token}`
        },
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.status === 201) {
          alert('Categoría creado correctamente');
          // Cierra el modal y refresca o actualiza el contenido
          $('#createCategoryModal').modal('hide');
          // Aquí puedes añadir lógica para actualizar la lista de Categoría si existe
        } else {
          alert('Ocurrió un error al crear la Categoría');
        }
      })
      .catch(error => console.error('Error:', error));
    });
    function getSucursales() {
      const token = localStorage.getItem('authToken');
      fetch('api/categories', {
          method: 'post',
          headers: {
              // 'X-CSRF-TOKEN': document.querySelector('input[name="_token"]').value
              'Authorization': `Bearer ${token}`
          }
      })
      .then(response => response.json())
      .then(data => {
          const categorySelector = document.getElementById('categorySelector');
          
          // Limpiamos las opciones actuales
          categorySelector.innerHTML = '<option value="">Selecciona una categoría</option>';
          
          // Agregamos cada categoría al selector
          data.forEach(category => {
              const option = document.createElement('option');
              option.value = category.id;
              option.textContent = category.name;
              categorySelector.appendChild(option);
          });
      })
      .catch(error => console.error('Error:', error));
  }
  </script>
@endpush