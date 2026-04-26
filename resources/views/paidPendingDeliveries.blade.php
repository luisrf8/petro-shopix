@extends('layouts.app')

@section('title', 'Ventas Pagadas por Entregar')

@section('content')
@php
  $currentUser = auth()->user();
  $paidPendingTenant = ($currentUser && (int) ($currentUser->tenant_id ?? 0) > 0)
    ? \App\Models\Tenant::find((int) $currentUser->tenant_id)
    : null;
  $paidPendingCapabilities = \App\Support\TenantPlanCapabilities::forTenant($paidPendingTenant);
  $isOwner = (bool) ($currentUser?->isOwner() ?? false);
  $isAdmin = (bool) ($currentUser?->isAdmin() ?? false);
  $isSeller = (bool) ($currentUser?->hasStoreRole('seller') ?? false);
  $isWarehouse = (bool) ($currentUser?->hasStoreRole('warehouse') ?? false);
  $isDeliveryUser = (bool) ($currentUser?->hasStoreRole('delivery') ?? false);
  $deliveryOperationsLocked = !$paidPendingCapabilities->allowsDeliveryOperations();
  $canAssignDeliveryInline = !$deliveryOperationsLocked && ($isOwner || $isAdmin || $isSeller || $isWarehouse);
@endphp
<div class="container-fluid py-2">
  <div class="card my-4">
    <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
      <div class="bg-gradient-dark shadow-dark border-radius-lg pt-3 pb-3 d-flex justify-content-between align-items-center">
        <h6 class="text-white text-capitalize ps-3 mb-0">Ventas pagadas pendientes de entrega</h6>
        <span class="badge bg-light text-dark me-3">{{ $ordersCount }}</span>
      </div>
    </div>
    <div class="card-body px-0 pb-2">
      <div class="px-3 pt-3">
        <ul class="nav nav-tabs" id="paidPendingDeliveryTabs" role="tablist">
          @foreach($visibleTabs as $tab)
            <li class="nav-item" role="presentation">
              <button
                class="nav-link {{ $activeTab === $tab['key'] ? 'active' : '' }}"
                id="paid-pending-{{ $tab['key'] }}-tab"
                data-bs-toggle="tab"
                data-bs-target="#paid-pending-{{ $tab['key'] }}"
                type="button"
                role="tab"
                aria-controls="paid-pending-{{ $tab['key'] }}"
                aria-selected="{{ $activeTab === $tab['key'] ? 'true' : 'false' }}"
              >
                {{ $tab['label'] }}
                <span class="badge bg-dark ms-2">{{ $tab['orders']->count() }}</span>
              </button>
            </li>
          @endforeach
        </ul>
      </div>

      <div class="tab-content p-3" id="paidPendingDeliveryTabsContent">
        @foreach($visibleTabs as $tab)
          <div
            class="tab-pane fade {{ $activeTab === $tab['key'] ? 'show active' : '' }}"
            id="paid-pending-{{ $tab['key'] }}"
            role="tabpanel"
            aria-labelledby="paid-pending-{{ $tab['key'] }}-tab"
          >
            <div class="table-responsive">
              <table class="table align-items-center mb-0">
                <thead>
                  <tr>
                    <th>Orden</th>
                    <th>Fecha</th>
                    <th>Destino</th>
                    <th>Acción</th>
                    <th>Recibe</th>
                  </tr>
                </thead>
                <tbody>
                  @forelse($tab['orders'] as $order)
                    <tr data-delivery-order-row="1" data-order-id="{{ $order->id }}" data-assigned-user-id="{{ (int) ($order->delivery_assigned_user_id ?? 0) }}">
                      <td>#{{ $order->id }}</td>
                      <td>{{ \Carbon\Carbon::parse($order->date)->format('d/m/Y') }}</td>
                      <td>
                        <div class="d-flex flex-column">
                          @if($order->delivery_map_url)
                            <a href="{{ $order->delivery_map_url }}" target="_blank" rel="noopener" class="text-xs">Ver mapa</a>
                          @endif
                        </div>
                      </td>
                      <td>
                        @if($tab['key'] === 'delivery' && $deliveryOperationsLocked)
                          <span class="text-xs text-muted">Delivery bloqueado por el plan actual.</span>
                        @elseif($tab['key'] === 'delivery' && $isDeliveryUser)
                          @php
                            $assignedUserId = (int) ($order->delivery_assigned_user_id ?? 0);
                            $isAssignedToCurrentDelivery = $assignedUserId > 0 && $assignedUserId === (int) ($currentUser->id ?? 0);
                          @endphp

                          <div class="d-flex flex-column gap-2">
                            @if($isAssignedToCurrentDelivery)
                              <span class="badge bg-success">Asignada para ti</span>
                              <a href="{{ route('sales.showByOrder', $order->id) }}" class="btn btn-outline-dark btn-sm mb-0">Gestionar delivery</a>
                            @elseif($assignedUserId > 0)
                              <span class="text-xs text-secondary">Tomada por {{ $order->assigned_delivery_name ?: 'otro delivery' }}</span>
                            @else
                              <form method="POST" action="{{ route('sales.assignDeliveryUser', $order->id) }}" class="d-inline-block">
                                @csrf
                                <input type="hidden" name="delivery_assigned_user_id" value="{{ (int) ($currentUser->id ?? 0) }}">
                                <button type="submit" class="btn btn-dark btn-sm mb-0">Tomar gestión</button>
                              </form>
                            @endif
                          </div>
                        @elseif($tab['key'] === 'delivery' && $canAssignDeliveryInline)
                          <div class="d-flex flex-column gap-2">
                            <form method="POST" action="{{ route('sales.assignDeliveryUser', $order->id) }}" class="d-flex flex-column gap-2">
                              @csrf
                              <select name="delivery_assigned_user_id" class="form-select form-select-sm">
                                <option value="">Sin asignar</option>
                                @foreach($deliveryUsers as $deliveryUser)
                                  <option value="{{ $deliveryUser->id }}" @selected((int) ($order->delivery_assigned_user_id ?? 0) === (int) $deliveryUser->id)>
                                    {{ $deliveryUser->name }}
                                  </option>
                                @endforeach
                              </select>
                              <button type="submit" class="btn btn-outline-dark btn-sm mb-0">Guardar asignación</button>
                            </form>
                            <a href="{{ route('sales.showByOrder', $order->id) }}" class="btn btn-outline-secondary btn-sm mb-0">Gestionar delivery</a>
                          </div>
                        @else
                        <a href="{{ route('sales.showByOrder', $order->id) }}" class="btn btn-outline-dark btn-sm mb-0">Gestionar {{ strtolower($tab['label']) }}</a>
                        @endif
                      </td>
                      <td>
                        <div class="d-flex flex-column">
                          <span class="text-sm">{{ $order->delivery_receiver_name ?: 'No registrado' }}</span>
                          <span class="text-xs text-secondary">{{ $order->delivery_receiver_phone ?: ($order->delivery_extra_info ?: '-') }}</span>
                        </div>
                      </td>
                    </tr>
                  @empty
                    <tr>
                      <td colspan="5" class="text-center text-muted py-4">No hay órdenes pagadas pendientes en {{ strtolower($tab['label']) }}.</td>
                    </tr>
                  @endforelse
                </tbody>
              </table>
            </div>
          </div>
        @endforeach
      </div>
    </div>
  </div>
</div>
@endsection

@push('scripts')
<script>
  document.addEventListener('DOMContentLoaded', function () {
    const tenantId = @json((int) ($currentUser->tenant_id ?? 0));
    const pusherKey = @json(config('broadcasting.connections.reverb.key'));

    if (!tenantId || !pusherKey || typeof Pusher === 'undefined') {
      return;
    }

    const csrfToken = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
    const configuredHost = @json(config('broadcasting.connections.reverb.options.host'));
    const configuredPort = Number(@json(config('broadcasting.connections.reverb.options.port')));
    const configuredScheme = @json(config('broadcasting.connections.reverb.options.scheme'));
    const configuredCluster = @json(config('broadcasting.connections.pusher.options.cluster'));

    const browserHost = window.location.hostname;
    const wsHost = !configuredHost || configuredHost === '127.0.0.1' || configuredHost === '0.0.0.0'
      ? browserHost
      : configuredHost;

    const forceTLS = configuredScheme
      ? configuredScheme === 'https'
      : window.location.protocol === 'https:';

    const wsPort = configuredPort || (forceTLS ? 443 : 80);
    const pusherOptions = {
      wsHost,
      wsPort,
      wssPort: wsPort,
      forceTLS,
      enabledTransports: ['ws', 'wss'],
      disableStats: true,
      authEndpoint: '/broadcasting/auth',
      auth: {
        headers: {
          'X-CSRF-TOKEN': csrfToken,
        },
      },
    };

    if (configuredCluster) {
      pusherOptions.cluster = configuredCluster;
    }

    const pusher = new Pusher(pusherKey, pusherOptions);
    const channel = pusher.subscribe(`private-tenant.delivery-ops.${tenantId}`);
    const handleAssignmentUpdate = function () {
      window.location.reload();
    };

    channel.bind('delivery.assignment.updated', handleAssignmentUpdate);
    channel.bind('.delivery.assignment.updated', handleAssignmentUpdate);
    pusher.connection.bind('error', function () {});
  });
</script>
@endpush
