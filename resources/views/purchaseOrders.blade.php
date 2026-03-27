@extends('layouts.app')

@section('title', 'Categorías')

@section('content')
    <div class="container-fluid py-2">
      <div class="row mt-4">
        <div class="col-12">
            <div class="card">
              <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                <div class="bg-gradient-dark shadow-dark border-radius-lg pt-4 pb-3 d-flex justify-content-between align-items-center">
                  <h6 class="text-white text-capitalize ps-3">ORDENES DE COMPRA REALIZADAS</h6>
                  <div class="py-1 px-3 text-end admin-mobile-actions" data-bs-toggle="modal" data-bs-target="#reportModal">
                    <label class="text-white admin-mobile-action-trigger">
                      + Generar Reporte
                    </label>
                    <a class="text-white ms-6 admin-mobile-action-trigger" href="/purchase">
                      + Generar Compra
                    </a>
                  </div>
                </div>
              </div> 
              <div class="card-body">
                <div class="table-responsive">
                  <table class="table align-items-center mb-0">
                    <thead class="text-center">
                      <tr>
                        <th>Vista</th>
                        <th># Orden</th>
                        <th>Fecha</th>
                        <th>Almacén</th>
                        <th>Proveedor</th>
                        <th># Variantes</th>
                        <th># Productos</th>
                        <th>Total (USD)</th>
                        <th>Acciones</th>
                      </tr>
                    </thead>
                    <tbody class="text-center">
                      @foreach($purchaseOrders as $order)
                        <tr>
                          <td>
                            <img src="{{ $order->preview_image }}" alt="preview" style="width:48px;height:48px;object-fit:cover;border-radius:8px;">
                          </td>
                          <td>{{ $order->id }}</td>
                          <td>{{ $order->date }}</td>
                          <td>{{ $order->warehouse->name ?? 'N/A' }}</td>
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
    document.getElementById('createProductForm').addEventListener('submit', function(event) {
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

    document.getElementById('createCategoryForm').addEventListener('submit', function(event) {
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