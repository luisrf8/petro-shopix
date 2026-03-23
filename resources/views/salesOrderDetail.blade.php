@extends('layouts.app')

@section('title', 'Categorías')

@section('content')
    @php
      $roleName = strtolower((string) optional(auth()->user()->role)->name);
      $isWarehouseRole = $roleName === 'almacen';

      $storePhone = preg_replace('/\D+/', '', (string) (($order->tenant->phone_code ?? '') . ($order->tenant->phone_number ?? '')));
      $customerPhone = preg_replace('/\D+/', '', (string) ($order->user->phone_number ?? ''));
      $storeWhatsappUrl = $storePhone !== ''
        ? 'https://wa.me/' . $storePhone . '?text=' . rawurlencode('Hola ' . ($order->tenant->name ?? 'tienda') . ', sobre la orden #' . $order->id . '.')
        : null;
      $customerWhatsappUrl = $customerPhone !== ''
        ? 'https://wa.me/' . $customerPhone . '?text=' . rawurlencode('Hola ' . ($order->user->name ?? 'cliente') . ', te escribimos sobre la orden #' . $order->id . '.')
        : null;
    @endphp
    <div class="container-fluid">
      <h1>Detalles de la Orden Nro {{ $order->id }}</h1>
      <input type="text" id="user-name" class="d-none" value="{{ $order->user->name }}" readonly>
      <input type="text" id="user-email" class="d-none" value="{{ $order->user->email }}" readonly>
      <input type="text" id="user-phone" class="d-none" value="{{ $order->user->phone_number ?? 'No registrado' }}" readonly>
      <p><strong>Cliente:</strong> {{ $order->user->name }} | <strong>Teléfono:</strong> {{ $order->user->phone_number ?? 'No registrado' }}</p>
      <p><strong>Entrega:</strong> {{ $order->preference }} | <strong>Dirección:</strong> {{ $order->address }}</p>
      <div class="d-flex align-items-center gap-2">
        <strong>Entregado:</strong>
        @if($order->has_returns)
          <span class="text-danger">Devolución Registrada</span>
        @else
          <select id="deliver-status" class="btn btn-sm toggle-status-btn 
            {{ $order->deliver_status == 0 ? 'btn-outline-warning' : ($order->deliver_status == 1 ? 'btn-outline-success' : 'btn-outline-danger') }}" 
            onchange="updateDeliverStatus(this, {{ $order->id }})">
              <option value="0" {{ $order->deliver_status == 0 ? 'selected' : '' }}>Pendiente ↓</option>
              <option value="1" {{ $order->deliver_status == 1 ? 'selected' : '' }}>Entregado ↓</option>
              <option value="2" {{ $order->deliver_status == 2 ? 'selected' : '' }}>Cancelado ↓</option>
          </select>
        @endif
      </div>

      <div class="d-flex gap-2">
        <div>
          <p><strong>Fecha:</strong> {{ $order->date }} |
        </div> 
          <div class="d-flex gap-2">
              <strong>Estado:</strong>
              @if($order->has_returns)
                <span class="text-danger">Devolución Registrada</span>
              @else
              @if(!$isWarehouseRole)
              <select id="order-status" class="btn btn-sm toggle-status-btn 
                {{ $order->status == 0 ? 'btn-outline-warning' : ($order->status == 1 ? 'btn-outline-success' : 'btn-outline-danger') }}" 
                onchange="updateOrderStatus(this, {{ $order->id }})">
                  <option value="0" {{ $order->status == 0 ? 'selected' : '' }}>En Proceso ↓</option>
                  <option value="1" {{ $order->status == 1 ? 'selected' : '' }}>Aprobado ↓</option>
                  <option value="2" {{ $order->status == 2 ? 'selected' : '' }}>Negado ↓</option>
              </select>
              @else
                <span class="text-sm">{{ $order->status == 0 ? 'En Proceso' : ($order->status == 1 ? 'Aprobado' : 'Negado') }}</span>
              @endif
              @endif
          </div>
      </div>

      <div class="w-100 d-flex justify-content-between mt-3 gap-4">
        <div class="d-flex flex-wrap gap-2">
          <a href="{{ route('sales.orders.pdfs', ['id' => $order->id, 'type' => 'invoice']) }}" class="btn btn-dark mb-0">Descargar factura PDF</a>
          <a href="{{ route('sales.orders.pdfs', ['id' => $order->id, 'type' => 'delivery']) }}" class="btn btn-outline-dark mb-0">Descargar nota de entrega</a>
          @if($storeWhatsappUrl)
            <a href="{{ $storeWhatsappUrl }}" target="_blank" rel="noopener" class="btn btn-outline-success mb-0">WhatsApp tienda</a>
          @endif
          @if($customerWhatsappUrl)
            <a href="{{ $customerWhatsappUrl }}" target="_blank" rel="noopener" class="btn btn-success mb-0">WhatsApp cliente</a>
          @endif
        </div>
        @if(!$isWarehouseRole)
          <button type="button" class="btn btn-dark" data-bs-toggle="modal" data-bs-target="#returnModal">
              Registrar Devolución
          </button>
        @endif
      </div>
      <!-- Tabla de Detalles de la Orden -->
      <div class="card">
        <div class="card-header">
          <h6 class="mb-0">Productos en la Orden</h6>
        </div>
        <div class="card-body">
          <div class="table-responsive order-table-wrapper">
          <table class="table order-detail-table align-middle mb-0">
            <thead>
              <tr>
                <th>Producto</th>
                <th>Cantidad</th>
                <th>Variante</th>
                <th>Precio Unitario</th>
                <th>Subtotal</th>
              </tr>
            </thead>
            <tbody>
              @foreach($order->details as $detalle)
              <tr>
                <td data-label="Producto">{{ $detalle->variant->product->name ?? 'Sin nombre' }}</td>
                <td data-label="Cantidad">{{ $detalle->quantity }}</td>
                <td data-label="Variante">{{ $detalle->variant->size ?? '' }}</td>
                <td data-label="Precio Unitario">${{ number_format($detalle->price, 2) }}</td>
                <td data-label="Subtotal">${{ number_format($detalle->amount, 2) }}</td>
              </tr>
              @endforeach
            </tbody>
          </table>
          </div>
          <p><strong>Total Orden:</strong> ${{ number_format($totalOrden, 2) }}</p>
          <p><strong>{{ $order->has_returns ? 'Total Devolucion' : ''}} </strong> ${{ $order->has_returns ? number_format($order->total_devuelto, 2) : '' }}</p>
        </div>
      </div>

      <!-- Tabla de Pagos -->
      <div class="card mt-4">
        <div class="card-header">
          <h6 class="mb-0">Pagos Registrados</h6>
        </div>
        <div class="card-body">
          <div class="table-responsive order-table-wrapper">
          <table class="table order-detail-table align-middle mb-0">
            <thead>
              <tr>
                <th>Moneda</th>
                <th>Método de Pago</th>
                <th>Monto</th>
                <th>Beneficiario</th>
                <th>Banco</th>
                <th>Referencia</th>
                <th>Comprobante</th>
                <th>Estado</th>
              </tr>
            </thead>
            <tbody>
              @foreach($order->payments as $payment)
              <tr>
                <td data-label="Moneda">{{ $payment->currency }}</td>
                <td data-label="Método de Pago">{{ $payment->payment->name}}</td>
                <td data-label="Monto">${{ number_format($payment->amount, 2) }}</td>
                <td data-label="Beneficiario">{{ $payment->payment->admin_name }}</td>
                <td data-label="Banco">{{ $payment->payment->bank }}</td>
                <td data-label="Referencia">{{ $payment->reference ?? 'N/A' }}</td>
                <td id="payment-{{ $payment->id }}" data-label="Comprobante">
                    @if($payment->images->isNotEmpty())
                      <a href="{{ \App\Support\ImageStorage::url($payment->images->first()->image_path) ?? '#' }}" target="_blank" class="btn btn-sm btn-outline-dark mb-0">Ver imagen</a>
                    @else
                      <span class="text-muted">Sin imagen</span>
                    @endif
                </td>
                <td data-label="Estado">
                    @if(!$isWarehouseRole)
                      <select class="btn btn-sm toggle-status-btn 
                        {{ $payment->status == 0 ? 'btn-outline-warning' : ($payment->status == 1 ? 'btn-outline-success' : 'btn-outline-danger') }}" 
                        onchange="updatePaymentStatus(this, {{ $payment->id }})">
                          <option value="0" {{ $payment->status == 0 ? 'selected' : '' }}>En Proceso ↓</option>
                          <option value="1" {{ $payment->status == 1 ? 'selected' : '' }}>Pagado ↓</option>
                          <option value="3" {{ $payment->status == 3 ? 'selected' : '' }}>Cancelado ↓</option>
                      </select>
                    @else
                      <span class="text-sm">{{ $payment->status == 0 ? 'En Proceso' : ($payment->status == 1 ? 'Pagado' : 'Cancelado') }}</span>
                    @endif
                </td>
              </tr>
              @endforeach
            </tbody>
          </table>
          </div>
          <p><strong>Total Pagado:</strong> ${{ number_format($totalPagado, 2) }}</p>
          <p><strong>{{ $order->has_returns ? 'Total Devolucion' : ''}} </strong> ${{ number_format($order->total_devuelto, 2) }}</p>
        </div>
      </div>

      <!-- Modal para realizar devoluciones -->
      @if(!$isWarehouseRole)
      <div class="modal fade" id="returnModal" tabindex="-1" aria-labelledby="returnModalLabel" aria-hidden="true">
          <div class="modal-dialog modal-lg">
              <div class="modal-content">
                  <div class="modal-header">
                      <h5 class="modal-title" id="returnModalLabel">Registrar Devolución</h5>
                      <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                  </div>
                  <div class="modal-body">
                      <form id="returnForm">
                          @csrf
                          <input type="hidden" id="orderId" value="{{ $order->id }}">
                          
                          <div class="mb-3">
                              <label for="returnReason" class="form-label">Razón de la devolución</label>
                              <textarea id="returnReason" class="form-control border border-1 border-radius-lg p-2" rows="3" placeholder="Especifique la razón de la devolución" required></textarea>
                          </div>

                          <div class="mb-3">
                              <h6>Productos de la Orden</h6>
                                <div class="table-responsive order-table-wrapper">
                                <table class="table order-detail-table align-middle mb-0">
                                  <thead>
                                      <tr>
                                          <th>Producto</th>
                                          <th>Cantidad</th>
                                          <th>Devolver</th>
                                      </tr>
                                  </thead>
                                  <tbody>
                                      @foreach($order->details as $detalle)
                                          <tr>
                                            <td data-label="Producto">{{ $detalle->variant->product->name ?? 'Sin nombre' }}</td>
                                            <td data-label="Cantidad">{{ $detalle->quantity }}</td>
                                            <td data-label="Devolver">
                                                  <input type="number" class="form-control return-quantity border border-1 border-radius-lg p-2" 
                                                      data-id="{{ $detalle->variant->id }}" 
                                                      data-max="{{ $detalle->quantity }}" 
                                                      placeholder="Cantidad a devolver" 
                                                      min="0" 
                                                      max="{{ $detalle->quantity }}">
                                              </td>
                                          </tr>
                                      @endforeach
                                  </tbody>
                              </table>
                                    </div>
                          </div>

                          <div class="d-flex justify-content-end">
                              <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                              <button type="submit" class="btn btn-dark ms-2">Registrar Devolución</button>
                          </div>
                      </form>
                  </div>
              </div>
          </div>
      </div>
      @endif
    </div>
    @endsection

@push('styles')
<style>
  .order-table-wrapper {
    width: 100%;
  }

  @media (max-width: 767.98px) {
    .order-detail-table thead {
      display: none;
    }

    .order-detail-table,
    .order-detail-table tbody,
    .order-detail-table tr,
    .order-detail-table td {
      display: block;
      width: 100%;
    }

    .order-detail-table tr {
      border: 1px solid #dee2e6;
      border-radius: 0.75rem;
      padding: 0.75rem;
      margin-bottom: 0.75rem;
      background: #fff;
      box-shadow: 0 0.125rem 0.375rem rgba(0, 0, 0, 0.05);
    }

    .order-detail-table td {
      border: 0;
      padding: 0.45rem 0;
      text-align: left;
      white-space: normal;
    }

    .order-detail-table td::before {
      content: attr(data-label);
      display: block;
      font-weight: 700;
      color: #344767;
      margin-bottom: 0.2rem;
    }

    .order-detail-table td[data-label="Estado"] select,
    .order-detail-table td[data-label="Devolver"] input {
      width: 100%;
    }
  }
</style>
@endpush

@push('scripts')
<script src="{{ asset('assets/js/core/popper.min.js') }}"></script>
<script src="{{ asset('assets/js/core/bootstrap.min.js') }}"></script>

<!-- Github buttons -->
<script async defer src="https://buttons.github.io/buttons.js"></script>

<!-- Control Center for Material Dashboard: parallax effects, scripts for the example pages etc -->
<script>
 function showLoading(selectElement) {
    selectElement.disabled = true;
    const originalText = selectElement.options[selectElement.selectedIndex].text;
    selectElement.options[selectElement.selectedIndex].text = "Cargando...";
    return originalText;
}

function restoreText(selectElement, originalText) {
    selectElement.options[selectElement.selectedIndex].text = originalText;
    selectElement.disabled = false;
}

function updateOrderStatus(selectElement, orderId) {
    const status = selectElement.value;
    const originalText = showLoading(selectElement);

    fetch(`/api/order/${orderId}/status/update`, {
        method: "POST",
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ status: status })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            if(data.pdf_url) {
              const link = document.createElement("a");
              link.href = data.pdf_url;
              link.download = `orden-${orderId}.pdf`;
              document.body.appendChild(link);
              link.click();
              document.body.removeChild(link);
            }
            alert(data.message);
            location.reload();
        } else {
            restoreText(selectElement, originalText);
        }
    })
    .catch(error => {
        console.error("Error:", error);
        restoreText(selectElement, originalText);
    });
}

function updateDeliverStatus(selectElement, paymentId) {
    const status = selectElement.value;
    const originalText = showLoading(selectElement);

    fetch(`/api/deliver/${paymentId}/status/update`, {
        method: "POST",
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ status: status })
    })
    .then(response => response.json())
    .then(data => {
        alert(data.message);
        location.reload();
    })
    .catch(error => {
        console.error("Error:", error);
        restoreText(selectElement, originalText);
    });
}

function updatePaymentStatus(selectElement, paymentId) {
    const status = selectElement.value;
    const originalText = showLoading(selectElement);

    fetch(`/api/payment/${paymentId}/status/update`, {
        method: "POST",
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ status: status })
    })
    .then(response => response.json())
    .then(data => {
        alert(data.message);
        location.reload();
    })
    .catch(error => {
        console.error("Error:", error);
        restoreText(selectElement, originalText);
    });
}

document.getElementById('returnForm').addEventListener('submit', function (event) {
    event.preventDefault();

    const orderId = document.getElementById('orderId').value;
    const reason = document.getElementById('returnReason').value;
    const items = [];

    document.querySelectorAll('.return-quantity').forEach(input => {
        const quantity = parseInt(input.value);
        const maxQuantity = parseInt(input.getAttribute('data-max'));
        const id = input.getAttribute('data-id');

        if (quantity > 0 && quantity <= maxQuantity) {
            items.push({ id, quantity });
        }
    });

    if (items.length === 0) {
        alert('Debe especificar al menos un producto para devolver.');
        return;
    }

    fetch(`/sales/${orderId}/return`, {
        method: 'POST',
        headers: {
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
            'Content-Type': 'application/json',
        },
        body: JSON.stringify({ items, reason }),
    })
    .then(response => {
        if (response.ok) {
            return response.json();
        } else {
            throw new Error('Error al registrar la devolución.');
        }
    })
    .then(data => {
        alert(data.message);
        location.reload(); // Recargar la página para reflejar los cambios
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Ocurrió un error al registrar la devolución.');
    });
});
</script>
@endpush