@extends('layouts.app')

@section('title', 'Categorías')

@section('content')
    <div class="container-fluid py-2">
      <div class="row">
        <div class="col-md-12 mt-4">
        <div class="">
            <div class="pb-0 px-3">
              <h1>Detalles de la Orden Nro {{ $order->id }}</h1>
              <h4 class="mb-0">Proveedor: {{$order->provider_id}}</h4>
              <h6 class="mb-0">Fecha: {{$order->date}}</h6>
            </div>
            <div class="pt-4">
              <div class="row">
                @foreach($order->detalles as $detalle)
                      <div class="col-md-4 mb-4">
                          <div class="card p-4 d-flex flex-row">
                              <div class="d-flex flex-column mx-3">
                                  <h6 class="mb-2 text-sm">{{ $detalle->product_variant->product->name ?? 'Sin nombre' }}</h6>
                                  <span class="mb-2 text-xs">Cantidad: 
                                      <span class="text-dark font-weight-bold ms-sm-2">{{ $detalle->quantity }}</span>
                                  </span>
                                  <span class="mb-2 text-xs">Variante: 
                                      <span class="text-dark font-weight-bold ms-sm-2">{{ $detalle->product_variant->size ?? 'Sin variante' }}</span>
                                  </span>
                                  <span class="mb-2 text-xs">Precio: 
                                      <span class="text-dark font-weight-bold ms-sm-2">{{ $detalle->price ?? 'Sin precio' }}</span>
                                  </span>
                              </div>
                          </div>
                      </div>
                  @endforeach
              </div>
          </div>
        </div>
      </div>
    </div>
    @endsection

@push('scripts')
<!-- Core JS Files -->
<script src="{{ asset('assets/js/core/popper.min.js') }}"></script>
<script src="{{ asset('assets/js/core/bootstrap.min.js') }}"></script>

<!-- Github buttons -->
<script async defer src="https://buttons.github.io/buttons.js"></script>

<!-- Control Center for Material Dashboard: parallax effects, scripts for the example pages etc -->
<script>
    document.querySelectorAll('form').forEach(form => {
        form.addEventListener('submit', function (e) {
            e.preventDefault();

            let formData = new FormData(form);
            
            fetch(form.action, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': form.querySelector('input[name="_token"]').value,
                },
                body: formData,
            })
            .then(response => response.json())
            .then(data => {
                alert(data.message);
                // Opcional: actualizar la interfaz de usuario o limpiar los campos
            })
            .catch(error => {
                console.error('Error:', error);
                alert('Ocurrió un error al registrar la llegada.');
            });
        });
    });
</script>
@endpush