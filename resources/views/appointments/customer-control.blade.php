@extends('layouts.app')

@section('title', 'Control de clientes / citas')

@section('content')
<div class="container-fluid py-3">
    @if(session('success'))
        <div class="alert alert-success text-white">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger text-white">{{ $errors->first() }}</div>
    @endif

    <div class="card mb-3">
        <div class="card-body">
            <div class="d-flex flex-wrap gap-2 justify-content-between align-items-center">
                <div>
                    <h5 class="mb-1">Control de clientes / citas</h5>
                    <small class="text-muted">Historial de citas, servicios asociados, consumos, compras y pagos por cliente.</small>
                </div>
                <div class="d-flex flex-wrap gap-2">
                    @if($selectedCustomer)
                        <a href="{{ route('appointments.customerControl.report.pdf', ['customer_id' => (int) $selectedCustomer->id]) }}" target="_blank" class="btn btn-dark btn-sm mb-0">Ver reporte PDF</a>
                    @endif
                    <a href="{{ route('appointments.index') }}" class="btn btn-outline-secondary btn-sm mb-0">Volver a citas</a>
                </div>
            </div>

            <form method="GET" action="{{ route('appointments.customerControl.index') }}" class="row g-2 mt-2">
                <div class="col-12 col-md-8 col-lg-6">
                    <label class="form-label mb-1">Cliente</label>
                    <select name="customer_id" class="form-control border border-1 p-2" required>
                        <option value="">Selecciona un cliente</option>
                        @foreach($customers as $customer)
                            <option value="{{ $customer->id }}" {{ (int) $selectedCustomerId === (int) $customer->id ? 'selected' : '' }}>
                                {{ $customer->name }}{{ !empty($customer->phone_number) ? ' · ' . $customer->phone_number : '' }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12 col-md-4 col-lg-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-dark w-100 mb-0">Ver historial</button>
                </div>
            </form>
        </div>
    </div>

    @if($selectedCustomer)
        <div class="row g-3 mb-3">
            <div class="col-12 col-md-6 col-lg-3">
                <div class="card h-100"><div class="card-body">
                    <div class="text-uppercase text-xs text-muted">Citas registradas</div>
                    <div class="h4 mb-0">{{ $summary['appointments_count'] }}</div>
                </div></div>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
                <div class="card h-100"><div class="card-body">
                    <div class="text-uppercase text-xs text-muted">Monto citas (USD)</div>
                    <div class="h4 mb-0">{{ number_format((float) $summary['appointments_total_usd'], 2) }}</div>
                    <small class="text-muted">{{ $bsRate > 0 ? number_format((float) $summary['appointments_total_usd'] * (float) $bsRate, 2) . ' Bs' : 'Sin tasa Bs' }}</small>
                </div></div>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
                <div class="card h-100"><div class="card-body">
                    <div class="text-uppercase text-xs text-muted">Compras registradas</div>
                    <div class="h4 mb-0">{{ $summary['sales_count'] }}</div>
                </div></div>
            </div>
            <div class="col-12 col-md-6 col-lg-3">
                <div class="card h-100"><div class="card-body">
                    <div class="text-uppercase text-xs text-muted">Pagos registrados (USD)</div>
                    <div class="h4 mb-0">{{ number_format((float) $summary['payments_total_usd'], 2) }}</div>
                    <small class="text-muted">Total compras: {{ number_format((float) $summary['sales_total_usd'], 2) }} USD</small>
                </div></div>
            </div>
        </div>

        <div class="card mb-3">
            <div class="card-header pb-0">
                <h6 class="mb-0">Historial de citas del cliente</h6>
            </div>
            <div class="card-body px-0 pt-2 pb-2">
                <div class="table-responsive px-3">
                    <table class="table align-items-center mb-0">
                        <thead>
                            <tr>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Fecha/Hora</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Servicios tomados</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Items/consumos</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Pago cita</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Compra asociada</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Estado</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Anotaciones</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Control</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($appointments as $appointment)
                                @php
                                    $services = $appointment->serviceItems->pluck('service')->filter()->values();
                                    if ($services->isEmpty() && $appointment->service) {
                                        $services = collect([$appointment->service]);
                                    }
                                    $servicesTotal = round((float) $services->sum(fn($s) => (float) ($s->price ?? 0)), 2);
                                    $itemsTotal = round((float) $appointment->consumptions->sum('amount'), 2);
                                    $appointmentTotal = $servicesTotal + $itemsTotal;
                                @endphp
                                <tr>
                                    <td>
                                        <div class="text-sm fw-semibold">{{ optional($appointment->starts_at)->format('d/m/Y') }}</div>
                                        <div class="text-xs text-muted">{{ optional($appointment->starts_at)->format('H:i') }} - {{ optional($appointment->ends_at)->format('H:i') }}</div>
                                    </td>
                                    <td>
                                        @if($services->isEmpty())
                                            <span class="badge bg-gradient-secondary">Sin servicios</span>
                                        @else
                                            @foreach($services as $service)
                                                <div class="text-xs">{{ $service->display_name ?? $service->name ?? 'Servicio' }}</div>
                                            @endforeach
                                        @endif
                                        <div class="text-xs text-muted mt-1">Subtotal: {{ number_format($servicesTotal, 2) }} USD</div>
                                    </td>
                                    <td>
                                        @if($appointment->consumptions->isEmpty())
                                            <span class="text-xs text-muted">Sin items</span>
                                        @else
                                            @foreach($appointment->consumptions as $consumption)
                                                <div class="text-xs">
                                                    {{ $consumption->variant->product->display_name ?? 'Item' }}{{ !empty($consumption->variant->size) ? ' · ' . $consumption->variant->size : '' }}
                                                    · x{{ number_format((float) ($consumption->quantity ?? 0), 2) }}
                                                </div>
                                            @endforeach
                                        @endif
                                        <div class="text-xs text-muted mt-1">Subtotal: {{ number_format($itemsTotal, 2) }} USD</div>
                                    </td>
                                    <td>
                                        <div class="text-xs">Pagado: {{ number_format((float) ($appointment->paid_amount ?? 0), 2) }} {{ $appointment->payment_currency ?: 'USD' }}</div>
                                        <div class="text-xs text-muted">Total cita: {{ number_format($appointmentTotal, 2) }} USD</div>
                                        <div class="text-xs text-muted">Bs: {{ $bsRate > 0 ? number_format($appointmentTotal * (float) $bsRate, 2) : 'N/A' }}</div>
                                        <div class="text-xs mt-1">{{ $appointment->payment_status_label }}</div>
                                    </td>
                                    <td>
                                        @if((int) ($appointment->sales_order_id ?? 0) > 0)
                                            <a href="{{ url('/publicOrder/' . (int) $appointment->sales_order_id) }}" target="_blank" class="text-xs">Pedido #{{ (int) $appointment->sales_order_id }}</a>
                                            <div class="text-xs text-muted">{{ $appointment->salesOrder?->payments?->count() ?? 0 }} pago(s)</div>
                                        @else
                                            <span class="text-xs text-muted">Sin compra asociada</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="text-xs">{{ $appointment->status_label }}</div>
                                        <div class="text-xs text-muted">{{ $appointment->assignedUser->name ?? 'Profesional' }}</div>
                                    </td>
                                    <td>
                                        @php($cleanNotes = trim((string) preg_replace('/^\[APPOINTMENT_PAYMENT_META\].*$/m', '', (string) ($appointment->notes ?? ''))))
                                        <div class="text-xs">{{ $cleanNotes !== '' ? $cleanNotes : 'Sin anotaciones' }}</div>
                                    </td>
                                    <td>
                                        <div class="d-flex flex-wrap gap-1">
                                            <button type="button" class="btn btn-outline-dark btn-sm mb-0" data-bs-toggle="modal" data-bs-target="#appointmentControlModal{{ (int) $appointment->id }}">Detalle</button>
                                            <a href="{{ route('appointments.customerControl.appointment.pdf', ['appointment' => (int) $appointment->id]) }}" target="_blank" class="btn btn-dark btn-sm mb-0">PDF</a>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-4">Este cliente aún no tiene citas registradas.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card">
            <div class="card-header pb-0">
                <h6 class="mb-0">Compras y pagos del cliente</h6>
            </div>
            <div class="card-body px-0 pt-2 pb-2">
                <div class="table-responsive px-3">
                    <table class="table align-items-center mb-0">
                        <thead>
                            <tr>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Pedido</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Fecha</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Items comprados</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Total pedido</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Pagos</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if($salesOrders->isEmpty())
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">Este cliente no tiene compras registradas.</td>
                                </tr>
                            @else
                                @foreach($salesOrders as $order)
                                    @php($orderItemsTotal = round((float) $order->details->sum('amount'), 2))
                                    @php($orderDelivery = round((float) ($order->delivery_fee ?? 0), 2))
                                    @php($orderTotal = round($orderItemsTotal + $orderDelivery, 2))
                                    @php($orderPaymentsTotal = round((float) $order->payments->sum('amount'), 2))
                                    <tr>
                                        <td>
                                            <a href="{{ url('/publicOrder/' . (int) $order->id) }}" target="_blank" class="text-sm fw-semibold">#{{ (int) $order->id }}</a>
                                        </td>
                                        <td><span class="text-xs">{{ !empty($order->date) ? \Carbon\Carbon::parse($order->date)->format('d/m/Y') : 'N/A' }}</span></td>
                                        <td>
                                            @if($order->details->isEmpty())
                                                <span class="text-xs text-muted">Sin detalles</span>
                                            @else
                                                @foreach($order->details as $detail)
                                                    <div class="text-xs">
                                                        {{ $detail->variant->product->display_name ?? 'Producto' }}{{ !empty($detail->variant->size) ? ' · ' . $detail->variant->size : '' }}
                                                        · x{{ number_format((float) ($detail->quantity ?? 0), 2) }}
                                                    </div>
                                                @endforeach
                                            @endif
                                        </td>
                                        <td>
                                            <div class="text-xs">{{ number_format($orderTotal, 2) }} USD</div>
                                            <div class="text-xs text-muted">Bs: {{ $bsRate > 0 ? number_format($orderTotal * (float) $bsRate, 2) : 'N/A' }}</div>
                                        </td>
                                        <td>
                                            @if($order->payments->isEmpty())
                                                <span class="text-xs text-muted">Sin pagos</span>
                                            @else
                                                @foreach($order->payments as $payment)
                                                    <div class="text-xs">
                                                        {{ $payment->payment->name ?? 'Método' }} · {{ number_format((float) ($payment->amount ?? 0), 2) }} {{ $payment->currency ?: 'USD' }}
                                                    </div>
                                                @endforeach
                                                <div class="text-xs text-muted mt-1">Total: {{ number_format($orderPaymentsTotal, 2) }} USD</div>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        @foreach($appointments as $appointment)
            @php
                $modalServices = $appointment->serviceItems->pluck('service')->filter()->values();
                if ($modalServices->isEmpty() && $appointment->service) {
                    $modalServices = collect([$appointment->service]);
                }
                $modalServicesTotal = round((float) $modalServices->sum(fn($s) => (float) ($s->price ?? 0)), 2);
                $modalItemsTotal = round((float) $appointment->consumptions->sum('amount'), 2);
                $modalTotal = $modalServicesTotal + $modalItemsTotal;
            @endphp
            <div class="modal fade" id="appointmentControlModal{{ (int) $appointment->id }}" tabindex="-1" aria-hidden="true">
                <div class="modal-dialog modal-xl modal-dialog-scrollable">
                    <div class="modal-content">
                        <div class="modal-header">
                            <h5 class="modal-title">Control de cita #{{ (int) $appointment->id }}</h5>
                            <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                        </div>
                        <div class="modal-body">
                            <div class="row g-3 mb-3">
                                <div class="col-12 col-md-4">
                                    <div class="border rounded p-2 h-100">
                                        <div class="text-xs text-muted">Fecha y hora</div>
                                        <div class="fw-semibold">{{ optional($appointment->starts_at)->format('d/m/Y H:i') }}</div>
                                        <div class="text-xs text-muted">Hasta {{ optional($appointment->ends_at)->format('H:i') }}</div>
                                    </div>
                                </div>
                                <div class="col-12 col-md-4">
                                    <div class="border rounded p-2 h-100">
                                        <div class="text-xs text-muted">Profesional / Estado</div>
                                        <div class="fw-semibold">{{ $appointment->assignedUser->name ?? 'Profesional' }}</div>
                                        <div class="text-xs">{{ $appointment->status_label }} · {{ $appointment->payment_status_label }}</div>
                                    </div>
                                </div>
                                <div class="col-12 col-md-4">
                                    <div class="border rounded p-2 h-100">
                                        <div class="text-xs text-muted">Total cita</div>
                                        <div class="fw-semibold">{{ number_format($modalTotal, 2) }} USD</div>
                                        <div class="text-xs text-muted">Bs {{ $bsRate > 0 ? number_format($modalTotal * (float) $bsRate, 2) : 'N/A' }}</div>
                                    </div>
                                </div>
                            </div>

                            <div class="row g-3">
                                <div class="col-12 col-lg-6">
                                    <div class="border rounded p-3 h-100">
                                        <h6 class="mb-2">Qué se hizo (servicios e items)</h6>
                                        <div class="mb-2">
                                            <div class="text-xs text-muted">Servicios</div>
                                            @forelse($modalServices as $service)
                                                <div class="text-sm">• {{ $service->display_name ?? $service->name ?? 'Servicio' }}</div>
                                            @empty
                                                <div class="text-sm text-muted">Sin servicios</div>
                                            @endforelse
                                        </div>
                                        <div class="mb-2">
                                            <div class="text-xs text-muted">Consumos / productos usados</div>
                                            @forelse($appointment->consumptions as $consumption)
                                                <div class="text-sm">• {{ $consumption->variant->product->display_name ?? 'Item' }}{{ !empty($consumption->variant->size) ? ' · ' . $consumption->variant->size : '' }} · x{{ number_format((float) ($consumption->quantity ?? 0), 2) }}</div>
                                            @empty
                                                <div class="text-sm text-muted">Sin consumos registrados</div>
                                            @endforelse
                                        </div>
                                        <div class="text-xs text-muted mt-2">Servicio: {{ number_format($modalServicesTotal, 2) }} USD | Items: {{ number_format($modalItemsTotal, 2) }} USD</div>
                                    </div>
                                </div>

                                <div class="col-12 col-lg-6">
                                    <form method="POST" action="{{ route('appointments.customerControl.evidence.store', ['appointment' => (int) $appointment->id]) }}" enctype="multipart/form-data" class="border rounded p-3 h-100">
                                        @csrf
                                        <input type="hidden" name="customer_id" value="{{ (int) $selectedCustomer->id }}">
                                        <h6 class="mb-2">Anotaciones e imágenes de control</h6>
                                        <div class="mb-2">
                                            <label class="form-label mb-1">Anotaciones de la cita</label>
                                            @php($cleanNotes = trim((string) preg_replace('/^\[APPOINTMENT_PAYMENT_META\].*$/m', '', (string) ($appointment->notes ?? ''))))
                                            <textarea name="notes" rows="4" class="form-control border border-1 p-2" placeholder="Describe qué se hizo en esta cita, observaciones, recomendaciones, etc.">{{ $cleanNotes }}</textarea>
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label mb-1">Etiqueta para imágenes</label>
                                            <input type="text" name="caption" class="form-control border border-1 p-2" placeholder="Ej. antes/después, avance sesión 1, etc.">
                                        </div>
                                        <div class="mb-2">
                                            <label class="form-label mb-1">Subir imágenes</label>
                                            <input type="file" name="evidence_images[]" class="form-control border border-1 p-2" accept="image/*" multiple>
                                            <small class="text-muted">Puedes subir varias imágenes (máx 6 por envío).</small>
                                        </div>
                                        <button type="submit" class="btn btn-dark btn-sm mb-0">Guardar control</button>
                                    </form>
                                </div>
                            </div>

                            <div class="mt-3 border rounded p-3">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <h6 class="mb-0">Galería de evidencias</h6>
                                    <a href="{{ route('appointments.customerControl.appointment.pdf', ['appointment' => (int) $appointment->id]) }}" target="_blank" class="btn btn-outline-dark btn-sm mb-0">Ver cita en PDF</a>
                                </div>
                                @if($appointment->images->isEmpty())
                                    <div class="text-muted text-sm">Esta cita todavía no tiene imágenes de evidencia.</div>
                                @else
                                    <div class="row g-2">
                                        @foreach($appointment->images as $image)
                                            <div class="col-6 col-md-4 col-lg-3">
                                                <a href="{{ \App\Support\ImageStorage::url($image->image_path) ?? '#' }}" target="_blank" class="d-block border rounded p-1 text-decoration-none">
                                                    <img src="{{ \App\Support\ImageStorage::url($image->image_path) ?? asset('assets/img/shopix5.png') }}" alt="Evidencia cita" class="img-fluid rounded" style="height: 130px; width: 100%; object-fit: cover;">
                                                    <div class="text-xs mt-1">{{ $image->caption ?: 'Sin etiqueta' }}</div>
                                                    <div class="text-xs text-muted">{{ $image->uploadedBy->name ?? 'Usuario' }} · {{ optional($image->created_at)->format('d/m/Y H:i') }}</div>
                                                </a>
                                            </div>
                                        @endforeach
                                    </div>
                                @endif
                            </div>
                        </div>
                        <div class="modal-footer">
                            <button type="button" class="btn btn-secondary mb-0" data-bs-dismiss="modal">Cerrar</button>
                            <a href="{{ route('appointments.customerControl.appointment.pdf', ['appointment' => (int) $appointment->id]) }}" target="_blank" class="btn btn-dark mb-0">PDF de esta cita</a>
                        </div>
                    </div>
                </div>
            </div>
        @endforeach
    @else
        <div class="card">
            <div class="card-body text-center text-muted py-5">
                Selecciona un cliente para ver su historial integral de citas, compras y pagos.
            </div>
        </div>
    @endif
</div>
@endsection
