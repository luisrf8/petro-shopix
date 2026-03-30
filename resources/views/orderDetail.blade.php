@extends('layouts.app')

@section('title', 'Categorías')

@section('content')
<div class="container-fluid py-2">
    <div class="row mt-4">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div>
                    <h3 class="mb-1">Detalle de entrada #{{ $order->id }}</h3>
                    <p class="text-sm text-muted mb-0">Información completa del ingreso al inventario.</p>
        </div>
                <a href="/purchase-orders" class="btn btn-outline-secondary btn-sm mb-0">Volver al historial</a>
      </div>

            <div class="row g-3 mb-3">
                <div class="col-12 col-md-3">
                    <div class="card h-100">
                        <div class="card-body p-3">
                            <p class="text-xs text-secondary mb-1">Origen</p>
                            <h6 class="mb-0">{{ $order->provider_display_name }}</h6>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-3">
                    <div class="card h-100">
                        <div class="card-body p-3">
                            <p class="text-xs text-secondary mb-1">Tipo de entrada</p>
                            <h6 class="mb-0">{{ $order->entry_mode_label ?? 'Compra' }}</h6>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-3">
                    <div class="card h-100">
                        <div class="card-body p-3">
                            <p class="text-xs text-secondary mb-1">Almacén</p>
                            <h6 class="mb-0">{{ $order->warehouse->name ?? 'No asignado' }}</h6>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-2">
                    <div class="card h-100">
                        <div class="card-body p-3">
                            <p class="text-xs text-secondary mb-1">Fecha de compra</p>
                            <h6 class="mb-0">{{ $order->date }}</h6>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-2">
                    <div class="card h-100">
                        <div class="card-body p-3">
                            <p class="text-xs text-secondary mb-1">Variantes</p>
                            <h6 class="mb-0">{{ $order->total_variants }}</h6>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-2">
                    <div class="card h-100">
                        <div class="card-body p-3">
                            <p class="text-xs text-secondary mb-1">Unidades</p>
                            <h6 class="mb-0">{{ $order->total_items }}</h6>
                        </div>
                    </div>
                </div>
                <div class="col-12 col-md-2">
                    <div class="card h-100">
                        <div class="card-body p-3">
                            <p class="text-xs text-secondary mb-1">Monto total</p>
                            <h6 class="mb-0">{{ number_format($order->total_amount, 2) }} {{ $baseCurrencyCode ?? 'USD' }}</h6>
                        </div>
                    </div>
                </div>
            </div>

            @if(($order->entry_mode ?? 'purchase') === 'production')
            <div class="card mb-3">
                <div class="card-header p-3 pb-0">
                    <h6 class="mb-0">Consumibles usados en producción</h6>
                </div>
                <div class="card-body p-3">
                    <div class="table-responsive">
                        <table class="table align-items-center mb-0">
                            <thead>
                                <tr>
                                    <th>Producto terminado</th>
                                    <th>Consumible</th>
                                    <th class="text-end">Cantidad consumida</th>
                                    <th class="text-end">Costo unitario</th>
                                    <th class="text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($order->consumptions as $consumption)
                                    <tr>
                                        <td>{{ $consumption->producedVariant?->product?->name ?? 'N/A' }} {{ $consumption->producedVariant?->size ? ('(' . $consumption->producedVariant?->size . ')') : '' }}</td>
                                        <td>{{ $consumption->consumedVariant?->product?->name ?? 'N/A' }} {{ $consumption->consumedVariant?->size ? ('(' . $consumption->consumedVariant?->size . ')') : '' }}</td>
                                        <td class="text-end">{{ number_format((float) $consumption->quantity, 2) }}</td>
                                        <td class="text-end">{{ number_format((float) $consumption->unit_cost, 2) }} {{ $baseCurrencyCode ?? 'USD' }}</td>
                                        <td class="text-end">{{ number_format((float) $consumption->amount, 2) }} {{ $baseCurrencyCode ?? 'USD' }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5">No hay consumibles registrados para esta entrada.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="4" class="text-end">Costo total consumibles:</th>
                                    <th class="text-end">{{ number_format((float) ($order->consumption_total ?? 0), 2) }} {{ $baseCurrencyCode ?? 'USD' }}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
            @endif

            <div class="card">
                <div class="card-header p-3 pb-0">
                    <h6 class="mb-0">Productos ingresados</h6>
                </div>
                <div class="card-body p-3">
                    <div class="table-responsive">
                        <table class="table align-items-center mb-0">
                            <thead>
                                <tr>
                                    <th>Imagen</th>
                                    <th>Producto</th>
                                    <th>Variante</th>
                                    <th class="text-end">Cantidad</th>
                                    <th class="text-end">Costo unitario</th>
                                    <th class="text-end">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($order->detalles as $detalle)
                                    @php
                                        $product = $detalle->productVariant?->product;
                                        $image = $product && $product->images->first()
                                                ? (\App\Support\ImageStorage::url($product->images->first()->path) ?? asset('assets/img/shopix5.png'))
                                                : asset('assets/img/shopix5.png');
                                    @endphp
                                    <tr>
                                        <td>
                                            <img src="{{ $image }}" alt="{{ $product?->name ?? 'Producto' }}" style="width:56px;height:56px;object-fit:cover;border-radius:8px;">
                                        </td>
                                        <td>{{ $product?->name ?? 'Sin nombre' }}</td>
                                        <td>{{ $detalle->productVariant?->size ?? 'Sin variante' }}</td>
                                        <td class="text-end">{{ $detalle->quantity }}</td>
                                        <td class="text-end">{{ number_format($detalle->price, 2) }} {{ $baseCurrencyCode ?? 'USD' }}</td>
                                        <td class="text-end">{{ number_format($detalle->amount, 2) }} {{ $baseCurrencyCode ?? 'USD' }}</td>
                                    </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr>
                                    <th colspan="3" class="text-end">Totales:</th>
                                    <th class="text-end">{{ $order->total_items }}</th>
                                    <th></th>
                                    <th class="text-end">{{ number_format($order->total_amount, 2) }} {{ $baseCurrencyCode ?? 'USD' }}</th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
    </div>
    </div>
</div>
@endsection