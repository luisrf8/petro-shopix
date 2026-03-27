@extends('layouts.app')

@section('title', 'Categorías')

@section('content')
    <div class="container-fluid py-2">
      <div class="row mt-4">
        <div class="col-12">
            <div class="card">
              <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                <div class="bg-gradient-dark shadow-dark border-radius-lg pt-4 pb-3 d-flex justify-content-between align-items-center">
                  <h6 class="text-white text-capitalize ps-3">{{ $pageTitle ?? 'VENTAS REALIZADAS' }}</h6>
                  <div class="py-1 px-3 text-end admin-mobile-actions">
                    @if(!($isPendingDeliveryView ?? false) && ($canApprovePayments ?? true))
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
                <div class="table-responsive">
                  <table class="table align-items-center mb-0">
                    <thead class="text-center">
                      <tr>
                        <th># Orden</th>
                        <th>Fecha</th>
                        <th>Usuario</th>
                        <th>Entrega</th>
                        <th># Productos</th>
                        <th>Documento</th>
                        <th>Estado</th>
                        <th>Devolución</th>
                        <th>Acciones</th>
                      </tr>
                    </thead>
                    <tbody class="text-center">
                      @foreach($salesOrders as $order)
                        <tr>
                          <td>{{ $order->id }}</td>
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
                              {{ $mode === 'electronic_invoice' ? 'Factura digital' : 'Nota de entrega' }}
                            </span>
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

@if(!($isPendingDeliveryView ?? false) && ($canApprovePayments ?? true))
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