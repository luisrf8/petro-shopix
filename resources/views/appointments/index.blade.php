@extends('layouts.app')

@section('title', 'Citas')

@push('styles')
<style>
    .appointments-shell {
        --calendar-hour-height: 72px;
    }

    .appointments-hero {
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 1.5rem;
        background: linear-gradient(135deg, #ffffff 0%, #f5f7fb 100%);
        box-shadow: 0 20px 45px rgba(15, 23, 42, 0.08);
    }

    .appointments-stat-card {
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 1rem;
        background: #fff;
        padding: 1rem;
        height: 100%;
    }

    .appointments-stat-label {
        font-size: 0.76rem;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #64748b;
    }

    .appointments-stat-value {
        font-size: 1.6rem;
        font-weight: 700;
        color: #0f172a;
        line-height: 1.1;
    }

    .appointments-calendar-card,
    .appointments-sidebar-card {
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 1.25rem;
        background: #fff;
        box-shadow: 0 18px 40px rgba(15, 23, 42, 0.06);
    }

    .appointments-sidebar-card .card-body,
    .appointments-calendar-card .card-body {
        padding: 1.15rem;
    }

    .appointments-calendar-scroll {
        overflow-x: auto;
        padding-bottom: 0.5rem;
    }

    .appointments-calendar-grid {
        min-width: 980px;
        display: grid;
        grid-template-columns: 88px repeat(7, minmax(140px, 1fr));
        gap: 0.75rem;
        align-items: start;
    }

    .appointments-calendar-time-header {
        font-size: 0.75rem;
        color: #64748b;
        padding-top: 0.9rem;
        text-transform: uppercase;
        letter-spacing: 0.06em;
    }

    .appointments-calendar-day-header {
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 1rem;
        background: #f8fafc;
        padding: 0.85rem;
        cursor: pointer;
        transition: transform 0.18s ease, box-shadow 0.18s ease, border-color 0.18s ease;
    }

    .appointments-calendar-day-header:hover {
        transform: translateY(-1px);
        box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
        border-color: rgba(15, 23, 42, 0.18);
    }

    .appointments-calendar-day-header.is-today {
        background: #0f172a;
        color: #fff;
    }

    .appointments-calendar-day-label {
        font-size: 0.76rem;
        text-transform: uppercase;
        letter-spacing: 0.06em;
        opacity: 0.85;
    }

    .appointments-calendar-day-date {
        font-size: 1.1rem;
        font-weight: 700;
    }

    .appointments-calendar-times {
        position: relative;
        height: calc(var(--calendar-hour-height) * var(--calendar-hours));
        padding-top: 0.35rem;
    }

    .appointments-calendar-time-slot {
        height: var(--calendar-hour-height);
        font-size: 0.76rem;
        color: #64748b;
        border-top: 1px solid rgba(148, 163, 184, 0.18);
        padding-top: 0.2rem;
    }

    .appointments-calendar-day-column {
        position: relative;
        height: calc(var(--calendar-hour-height) * var(--calendar-hours));
        border: 1px solid rgba(148, 163, 184, 0.18);
        border-radius: 1rem;
        background: linear-gradient(180deg, #ffffff 0%, #f8fafc 100%);
        overflow: hidden;
    }

    .appointments-calendar-hour-line {
        position: absolute;
        inset-inline: 0;
        height: 1px;
        background: rgba(148, 163, 184, 0.16);
    }

    .appointments-calendar-day-column[data-active="1"] {
        box-shadow: inset 0 0 0 2px rgba(15, 23, 42, 0.08);
    }

    .appointments-calendar-empty {
        position: absolute;
        inset: 0;
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 1rem;
        text-align: center;
        color: #94a3b8;
        font-size: 0.85rem;
        pointer-events: none;
    }

    .appointments-calendar-event {
        position: absolute;
        left: 0.45rem;
        right: 0.45rem;
        border-radius: 0.9rem;
        padding: 0.6rem 0.65rem;
        color: #fff;
        box-shadow: 0 14px 28px rgba(15, 23, 42, 0.16);
        overflow: hidden;
    }

    .appointments-calendar-event-title {
        font-size: 0.84rem;
        font-weight: 700;
        line-height: 1.2;
        margin-bottom: 0.18rem;
    }

    .appointments-calendar-event-meta {
        font-size: 0.72rem;
        line-height: 1.25;
        opacity: 0.95;
    }

    .appointments-form-section-title {
        font-size: 0.82rem;
        text-transform: uppercase;
        letter-spacing: 0.08em;
        color: #64748b;
        margin-bottom: 0.75rem;
    }

    .appointment-consumption-row {
        border: 1px dashed rgba(15, 23, 42, 0.14);
        border-radius: 0.9rem;
        padding: 0.75rem;
        background: #f8fafc;
    }

    .appointment-inline-note {
        font-size: 0.8rem;
        color: #64748b;
    }

    .appointment-badge-soft {
        display: inline-flex;
        align-items: center;
        gap: 0.35rem;
        padding: 0.35rem 0.65rem;
        border-radius: 999px;
        background: #eef2ff;
        color: #312e81;
        font-size: 0.75rem;
        font-weight: 600;
    }

    .appointment-upcoming-item,
    .appointment-schedule-item {
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 1rem;
        background: #fff;
        padding: 0.85rem;
    }

    @media (max-width: 1199.98px) {
        .appointments-calendar-grid {
            min-width: 900px;
        }
    }
</style>
@endpush

@section('content')
@php
    $calendarHours = range($calendarBounds['startHour'], max($calendarBounds['startHour'], $calendarBounds['endHour'] - 1));
    $calendarHoursCount = count($calendarHours);
    $previousWeekDate = $calendarWeekStart->copy()->subWeek()->toDateString();
    $nextWeekDate = $calendarWeekStart->copy()->addWeek()->toDateString();
@endphp
<div class="container-fluid py-3 appointments-shell">
    <div class="appointments-hero p-4 mb-4">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div>
                <span class="badge bg-dark mb-2">Plan Pro</span>
                <h2 class="mb-1">Servicios y citas</h2>
                <p class="text-muted mb-0">Agenda semanal amplia para disponibilidad, turnos por profesional, consumibles usados y registro de pago.</p>
            </div>
            <form method="GET" action="{{ route('appointments.index') }}" class="d-flex flex-wrap gap-2 align-items-end">
                <div>
                    <label class="form-label mb-1">Fecha eje</label>
                    <input type="date" class="form-control border border-1 p-2" name="date" value="{{ $selectedDate->toDateString() }}">
                </div>
                <div>
                    <label class="form-label mb-1">Profesional</label>
                    <select class="form-control border border-1 p-2" name="user_id">
                        <option value="0">Todos</option>
                        @foreach($professionals as $professional)
                            <option value="{{ $professional->id }}" {{ (int) $selectedUserId === (int) $professional->id ? 'selected' : '' }}>{{ $professional->name }}</option>
                        @endforeach
                    </select>
                </div>
                <button class="btn btn-dark mb-0" type="submit">Actualizar vista</button>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border text-white">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger border text-white">{{ $errors->first() }}</div>
    @endif

    @if(!$serviceBusinessType)
        <div class="alert alert-warning text-dark border">La tienda no está marcada como tipo servicio. El módulo funciona, pero conviene configurar el negocio como servicio para mantener la segmentación correcta.</div>
    @endif

    <div class="row g-3 mb-4">
        <div class="col-12 col-md-4">
            <div class="appointments-stat-card">
                <div class="appointments-stat-label">Profesionales activos</div>
                <div class="appointments-stat-value">{{ $professionals->count() }}</div>
                <div class="appointment-inline-note">Usuarios habilitados para atender y tomar citas.</div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="appointments-stat-card">
                <div class="appointments-stat-label">Servicios configurados</div>
                <div class="appointments-stat-value">{{ $services->count() }}</div>
                <div class="appointment-inline-note">Cada servicio puede quedar ligado a un producto y a un profesional.</div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="appointments-stat-card">
                <div class="appointments-stat-label">Citas en la semana</div>
                <div class="appointments-stat-value">{{ count($calendarEvents ?? []) }}</div>
                <div class="appointment-inline-note">Semana de {{ $calendarWeekStart->format('d/m') }} a {{ $calendarWeekEnd->format('d/m') }}.</div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12 col-xxl-8">
            <div class="appointments-calendar-card h-100">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                        <div>
                            <div class="appointments-form-section-title mb-1">Calendario semanal</div>
                            <h4 class="mb-1">{{ \Illuminate\Support\Str::ucfirst($calendarWeekStart->translatedFormat('d M')) }} - {{ \Illuminate\Support\Str::ucfirst($calendarWeekEnd->translatedFormat('d M Y')) }}</h4>
                            <div class="appointment-inline-note">Haz clic en un día para precargar la fecha del formulario.</div>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="{{ route('appointments.index', ['date' => $previousWeekDate, 'user_id' => $selectedUserId]) }}" class="btn btn-outline-dark mb-0">Semana anterior</a>
                            <a href="{{ route('appointments.index', ['date' => now()->toDateString(), 'user_id' => $selectedUserId]) }}" class="btn btn-outline-secondary mb-0">Hoy</a>
                            <a href="{{ route('appointments.index', ['date' => $nextWeekDate, 'user_id' => $selectedUserId]) }}" class="btn btn-dark mb-0">Semana siguiente</a>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap gap-2 mb-3">
                        @foreach($calendarProfessionals as $professional)
                            <span class="appointment-badge-soft">{{ $professional->name }}</span>
                        @endforeach
                        @if($calendarProfessionals->isEmpty())
                            <span class="text-muted small">No hay profesionales para mostrar en el calendario.</span>
                        @endif
                    </div>

                    <div class="appointments-calendar-scroll">
                        <div class="appointments-calendar-grid" style="--calendar-hours: {{ max(1, $calendarHoursCount) }};">
                            <div class="appointments-calendar-time-header">Horas</div>
                            @foreach($calendarDays as $day)
                                <button type="button" class="appointments-calendar-day-header {{ $day['is_today'] ? 'is-today' : '' }}" data-calendar-date="{{ $day['date'] }}">
                                    <div class="appointments-calendar-day-label">{{ $day['day_name'] }}</div>
                                    <div class="appointments-calendar-day-date">{{ $day['label'] }}</div>
                                </button>
                            @endforeach

                            <div class="appointments-calendar-times">
                                @foreach($calendarHours as $hour)
                                    <div class="appointments-calendar-time-slot">{{ str_pad((string) $hour, 2, '0', STR_PAD_LEFT) }}:00</div>
                                @endforeach
                            </div>

                            @foreach($calendarDays as $day)
                                <div class="appointments-calendar-day-column" data-calendar-column="{{ $day['date'] }}" data-active="{{ $day['is_today'] ? '1' : '0' }}">
                                    @foreach($calendarHours as $hour)
                                        <div class="appointments-calendar-hour-line" style="top: calc(({{ $loop->index }} * var(--calendar-hour-height)) + 0px);"></div>
                                    @endforeach
                                    <div class="appointments-calendar-empty" data-calendar-empty="{{ $day['date'] }}">Sin citas registradas</div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-12 col-xxl-4">
            <div class="d-flex flex-column gap-3">
                <div class="appointments-sidebar-card">
                    <div class="card-body">
                        <div class="appointments-form-section-title">Registrar cita</div>
                        <form method="POST" action="{{ route('appointments.store') }}" class="row g-2" id="appointmentBookingForm">
                            @csrf
                            <div class="col-12">
                                <label class="form-label">Servicio</label>
                                <select name="appointment_service_id" id="appointmentServiceSelect" class="form-control border border-1 p-2" required>
                                    <option value="">Seleccione</option>
                                    @foreach($services as $service)
                                        <option value="{{ $service->id }}" {{ (string) old('appointment_service_id') === (string) $service->id ? 'selected' : '' }}>{{ $service->display_name }} · {{ $service->duration_minutes }} min</option>
                                    @endforeach
                                </select>
                                <small id="appointmentServiceMeta" class="appointment-inline-note d-block mt-1">Selecciona el servicio-producto a reservar.</small>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Profesional</label>
                                <select name="user_id" id="appointmentUserSelect" class="form-control border border-1 p-2" required>
                                    <option value="">Seleccione</option>
                                    @foreach($professionals as $professional)
                                        <option value="{{ $professional->id }}" {{ (string) old('user_id', $selectedUserId > 0 ? $selectedUserId : '') === (string) $professional->id ? 'selected' : '' }}>{{ $professional->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Fecha</label>
                                <input type="date" name="scheduled_date" id="appointmentDateInput" class="form-control border border-1 p-2" value="{{ old('scheduled_date', $selectedDate->toDateString()) }}" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Hora disponible</label>
                                <select name="start_time" id="appointmentSlotSelect" class="form-control border border-1 p-2" required>
                                    <option value="">Seleccione un servicio, profesional y fecha</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Cliente existente</label>
                                <select name="customer_id" class="form-control border border-1 p-2">
                                    <option value="">Sin cliente registrado</option>
                                    @foreach($customers as $customer)
                                        <option value="{{ $customer->id }}" {{ (string) old('customer_id') === (string) $customer->id ? 'selected' : '' }}>{{ $customer->name }}{{ $customer->phone_number ? ' · ' . $customer->phone_number : '' }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Nombre de contacto</label>
                                <input type="text" name="contact_name" class="form-control border border-1 p-2" value="{{ old('contact_name') }}" placeholder="Si no hay cliente registrado">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Teléfono</label>
                                <input type="text" name="contact_phone" class="form-control border border-1 p-2" value="{{ old('contact_phone') }}">
                            </div>
                            <div class="col-12">
                                <div class="d-flex justify-content-between align-items-center mb-2">
                                    <label class="form-label mb-0">Consumibles utilizados</label>
                                    <button type="button" class="btn btn-outline-dark btn-sm mb-0" id="addAppointmentConsumptionBtn">Agregar consumible</button>
                                </div>
                                <div id="appointmentConsumptionsWrapper" class="d-flex flex-column gap-2"></div>
                                <small class="appointment-inline-note d-block mt-1">Registra solo los consumibles realmente usados en la atención.</small>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Método de pago</label>
                                <select name="payment_method_id" id="appointmentPaymentMethodSelect" class="form-control border border-1 p-2">
                                    <option value="">Sin pago registrado</option>
                                    @foreach($paymentMethods as $paymentMethod)
                                        <option value="{{ $paymentMethod->id }}" data-has-reference="{{ $paymentMethod->usesReference() ? '1' : '0' }}" data-currency="{{ $paymentMethod->currency->code ?? '' }}" {{ (string) old('payment_method_id') === (string) $paymentMethod->id ? 'selected' : '' }}>{{ $paymentMethod->name }}{{ !empty($paymentMethod->currency?->code) ? ' · ' . $paymentMethod->currency->code : '' }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Monto pagado</label>
                                <input type="number" step="0.01" min="0" name="paid_amount" id="appointmentPaidAmountInput" class="form-control border border-1 p-2" value="{{ old('paid_amount') }}" placeholder="0.00">
                            </div>
                            <div class="col-md-6" id="appointmentPaymentReferenceGroup">
                                <label class="form-label">Referencia de pago</label>
                                <input type="text" name="payment_reference" id="appointmentPaymentReferenceInput" class="form-control border border-1 p-2" value="{{ old('payment_reference') }}">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Estado del pago</label>
                                <select name="payment_status" class="form-control border border-1 p-2">
                                    @foreach(\App\Models\Appointment::PAYMENT_STATUSES as $statusKey => $statusLabel)
                                        <option value="{{ $statusKey }}" {{ old('payment_status', 'pending') === $statusKey ? 'selected' : '' }}>{{ $statusLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Estado de la cita</label>
                                <select name="status" class="form-control border border-1 p-2">
                                    @foreach(\App\Models\Appointment::STATUSES as $statusKey => $statusLabel)
                                        <option value="{{ $statusKey }}" {{ old('status', 'scheduled') === $statusKey ? 'selected' : '' }}>{{ $statusLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Notas</label>
                                <textarea name="notes" class="form-control border border-1 p-2" rows="2">{{ old('notes') }}</textarea>
                            </div>
                            <div class="col-12">
                                <button class="btn btn-success w-100 mb-0" type="submit">Agendar cita</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="appointments-sidebar-card">
                    <div class="card-body">
                        <div class="appointments-form-section-title">Servicios</div>
                        <form method="POST" action="{{ route('appointments.services.store') }}" class="row g-2" id="appointmentServiceForm">
                            @csrf
                            <div class="col-12">
                                <label class="form-label">Producto de servicio</label>
                                <select name="product_variant_id" id="appointmentServiceProductSelect" class="form-control border border-1 p-2" required>
                                    <option value="">Selecciona un producto/variante</option>
                                    @foreach($serviceVariants as $variant)
                                        <option value="{{ $variant->id }}">{{ $variant->product->name ?? 'Servicio' }} · {{ $variant->size ?? 'Variante' }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Nombre comercial</label>
                                <input type="text" name="name" id="appointmentServiceNameInput" class="form-control border border-1 p-2" required>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Descripción</label>
                                <textarea name="description" class="form-control border border-1 p-2" rows="2"></textarea>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Duración</label>
                                <input type="number" name="duration_minutes" class="form-control border border-1 p-2" min="15" step="15" value="60" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Buffer</label>
                                <input type="number" name="buffer_minutes" class="form-control border border-1 p-2" min="0" step="5" value="0">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Precio</label>
                                <input type="number" name="price" class="form-control border border-1 p-2" min="0" step="0.01" value="0">
                            </div>
                            <div class="col-md-8">
                                <label class="form-label">Profesional asignado</label>
                                <select name="user_id" class="form-control border border-1 p-2">
                                    <option value="">Cualquiera</option>
                                    @foreach($professionals as $professional)
                                        <option value="{{ $professional->id }}">{{ $professional->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label">Color</label>
                                <input type="color" name="color_hex" class="form-control form-control-color w-100" value="#0f172a">
                            </div>
                            <div class="col-12">
                                <button class="btn btn-outline-dark w-100 mb-0" type="submit">Guardar servicio</button>
                            </div>
                        </form>
                    </div>
                </div>

                <div class="appointments-sidebar-card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center gap-2 mb-2">
                            <div class="appointments-form-section-title mb-0">Turnos y horarios</div>
                            <a href="{{ route('tenant.store') }}" class="btn btn-outline-secondary btn-sm mb-0">Gestión de Tienda</a>
                        </div>
                        <form method="POST" action="{{ route('appointments.schedules.store') }}" class="row g-2 mb-3">
                            @csrf
                            <div class="col-12">
                                <label class="form-label">Profesional</label>
                                <select name="user_id" class="form-control border border-1 p-2" required>
                                    @foreach($professionals as $professional)
                                        <option value="{{ $professional->id }}">{{ $professional->name }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Día</label>
                                <select name="day_of_week" class="form-control border border-1 p-2" required>
                                    @foreach(\App\Models\UserScheduleRule::WEEK_DAYS as $dayIndex => $dayLabel)
                                        <option value="{{ $dayIndex }}">{{ $dayLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Intervalo</label>
                                <input type="number" name="slot_interval_minutes" class="form-control border border-1 p-2" min="15" step="15" value="60">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Inicio</label>
                                <input type="time" name="start_time" class="form-control border border-1 p-2" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Fin</label>
                                <input type="time" name="end_time" class="form-control border border-1 p-2" required>
                            </div>
                            <div class="col-12">
                                <button class="btn btn-dark w-100 mb-0" type="submit">Guardar turno</button>
                            </div>
                        </form>
                        <div class="d-flex flex-column gap-2" style="max-height: 240px; overflow:auto;">
                            @forelse($scheduleRules as $rule)
                                <div class="appointment-schedule-item">
                                    <div class="fw-semibold">{{ $rule->user->name ?? 'Profesional' }}</div>
                                    <div class="appointment-inline-note">{{ $rule->day_label }} · {{ \Carbon\Carbon::parse($rule->start_time)->format('H:i') }} - {{ \Carbon\Carbon::parse($rule->end_time)->format('H:i') }} · cada {{ $rule->slot_interval_minutes }} min</div>
                                </div>
                            @empty
                                <div class="text-muted">Todavía no hay turnos configurados.</div>
                            @endforelse
                        </div>
                    </div>
                </div>

                <div class="appointments-sidebar-card">
                    <div class="card-body">
                        <div class="appointments-form-section-title">Próximas citas</div>
                        <div class="d-flex flex-column gap-2">
                            @forelse($upcomingAppointments as $appointment)
                                <div class="appointment-upcoming-item">
                                    <div class="fw-semibold">{{ $appointment->service->display_name ?? $appointment->service->name ?? 'Servicio' }}</div>
                                    <div class="appointment-inline-note">{{ $appointment->starts_at->format('d/m/Y H:i') }} · {{ $appointment->assignedUser->name ?? 'Profesional' }}</div>
                                    <div>{{ $appointment->customer->name ?? $appointment->contact_name ?? 'Cliente sin registro' }}</div>
                                    <div class="appointment-inline-note mt-1">Pago: {{ $appointment->payment_status_label }}{{ !is_null($appointment->paid_amount) ? ' · ' . number_format((float) $appointment->paid_amount, 2) . ' ' . ($appointment->payment_currency ?: '') : '' }}</div>
                                </div>
                            @empty
                                <div class="text-muted">No hay próximas citas registradas.</div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const appointmentEvents = @json($calendarEvents ?? []);
    const calendarDays = @json($calendarDays ?? []);
    const calendarStartHour = Number(@json($calendarBounds['startHour'] ?? 7));
    const calendarHourHeight = 72;
    const servicesPayload = @json($servicesPayload ?? []);
    const consumableVariants = @json($consumableVariantsPayload ?? []);

    const serviceSelect = document.getElementById('appointmentServiceSelect');
    const userSelect = document.getElementById('appointmentUserSelect');
    const dateInput = document.getElementById('appointmentDateInput');
    const slotSelect = document.getElementById('appointmentSlotSelect');
    const serviceMeta = document.getElementById('appointmentServiceMeta');
    const paymentMethodSelect = document.getElementById('appointmentPaymentMethodSelect');
    const paymentReferenceGroup = document.getElementById('appointmentPaymentReferenceGroup');
    const paymentReferenceInput = document.getElementById('appointmentPaymentReferenceInput');
    const consumptionsWrapper = document.getElementById('appointmentConsumptionsWrapper');
    const addConsumptionBtn = document.getElementById('addAppointmentConsumptionBtn');
    const serviceProductSelect = document.getElementById('appointmentServiceProductSelect');
    const serviceNameInput = document.getElementById('appointmentServiceNameInput');

    function renderCalendar() {
        const grouped = appointmentEvents.reduce((accumulator, item) => {
            accumulator[item.date] = accumulator[item.date] || [];
            accumulator[item.date].push(item);
            return accumulator;
        }, {});

        calendarDays.forEach((day) => {
            const column = document.querySelector(`[data-calendar-column="${day.date}"]`);
            const emptyState = document.querySelector(`[data-calendar-empty="${day.date}"]`);
            if (!column) return;

            column.querySelectorAll('.appointments-calendar-event').forEach((element) => element.remove());
            const events = grouped[day.date] || [];
            if (emptyState) {
                emptyState.style.display = events.length ? 'none' : 'flex';
            }

            events.forEach((event) => {
                const card = document.createElement('button');
                card.type = 'button';
                card.className = 'appointments-calendar-event';
                card.style.top = `${(event.minutes_from_start / 60) * calendarHourHeight + 4}px`;
                card.style.height = `${Math.max(54, (event.duration_minutes / 60) * calendarHourHeight - 8)}px`;
                card.style.background = event.color_hex || '#0f172a';
                card.innerHTML = `
                    <div class="appointments-calendar-event-title">${event.title}</div>
                    <div class="appointments-calendar-event-meta">${event.start_time} - ${event.end_time}</div>
                    <div class="appointments-calendar-event-meta">${event.professional}</div>
                    <div class="appointments-calendar-event-meta">${event.customer}</div>
                    <div class="appointments-calendar-event-meta">${event.status} · ${event.payment_status}</div>
                `;
                card.addEventListener('click', () => {
                    dateInput.value = event.date;
                    if (serviceMeta) {
                        serviceMeta.textContent = `${event.title} · ${event.professional} · ${event.start_time}`;
                    }
                    window.scrollTo({ top: 0, behavior: 'smooth' });
                });
                column.appendChild(card);
            });
        });
    }

    function getSelectedService() {
        const selectedId = Number(serviceSelect?.value || 0);
        return servicesPayload.find((service) => Number(service.id) === selectedId) || null;
    }

    function syncServiceMetadata() {
        const selectedService = getSelectedService();
        if (!selectedService) {
            if (serviceMeta) {
                serviceMeta.textContent = 'Selecciona el servicio-producto a reservar.';
            }
            return;
        }

        if (serviceMeta) {
            const priceLabel = Number(selectedService.price || 0) > 0 ? ` · ${Number(selectedService.price).toFixed(2)}` : '';
            const productLabel = selectedService.product_label ? ` · ${selectedService.product_label}` : '';
            serviceMeta.textContent = `${selectedService.name}${productLabel} · ${selectedService.duration_minutes} min${priceLabel}`;
        }

        if (selectedService.assigned_user_id && userSelect) {
            userSelect.value = String(selectedService.assigned_user_id);
        }
    }

    function buildConsumptionOptions(selectedId = '') {
        const options = ['<option value="">Selecciona un consumible</option>'];
        consumableVariants.forEach((variant) => {
            const isSelected = String(selectedId) === String(variant.id) ? 'selected' : '';
            options.push(`<option value="${variant.id}" ${isSelected}>${variant.label} · Stock ${Number(variant.stock || 0).toFixed(2)}</option>`);
        });
        return options.join('');
    }

    function updateConsumptionMeta(row) {
        const select = row.querySelector('.appointment-consumption-variant');
        const meta = row.querySelector('.appointment-consumption-meta');
        const selectedVariant = consumableVariants.find((variant) => String(variant.id) === String(select?.value || ''));
        if (!meta) return;
        meta.textContent = selectedVariant
            ? `Costo ref. ${Number(selectedVariant.unit_cost || 0).toFixed(2)} · Stock ${Number(selectedVariant.stock || 0).toFixed(2)}`
            : 'Selecciona el consumible usado en la cita.';
    }

    function addConsumptionRow(selectedId = '', quantity = '') {
        if (!consumptionsWrapper) return;
        const index = consumptionsWrapper.children.length;
        const row = document.createElement('div');
        row.className = 'appointment-consumption-row';
        row.innerHTML = `
            <div class="row g-2 align-items-end">
                <div class="col-12 col-md-7">
                    <label class="form-label">Consumible</label>
                    <select name="consumptions[${index}][variant_id]" class="form-control border border-1 p-2 appointment-consumption-variant">
                        ${buildConsumptionOptions(selectedId)}
                    </select>
                </div>
                <div class="col-8 col-md-3">
                    <label class="form-label">Cantidad</label>
                    <input type="number" step="0.01" min="0.01" name="consumptions[${index}][quantity]" class="form-control border border-1 p-2" value="${quantity}">
                </div>
                <div class="col-4 col-md-2">
                    <button type="button" class="btn btn-outline-danger w-100 mb-0 appointment-remove-consumption">Quitar</button>
                </div>
                <div class="col-12 appointment-consumption-meta appointment-inline-note">Selecciona el consumible usado en la cita.</div>
            </div>
        `;
        consumptionsWrapper.appendChild(row);
        updateConsumptionMeta(row);
    }

    function syncPaymentReferenceRequirement() {
        const selectedOption = paymentMethodSelect?.selectedOptions?.[0] || null;
        const requiresReference = selectedOption?.dataset?.hasReference === '1';
        if (paymentReferenceGroup) {
            paymentReferenceGroup.style.display = selectedOption && paymentMethodSelect.value ? 'block' : 'none';
        }
        if (paymentReferenceInput) {
            paymentReferenceInput.required = !!requiresReference;
            if (!requiresReference) {
                paymentReferenceInput.value = paymentReferenceInput.value || '';
            }
        }
    }

    async function loadSlots() {
        if (!serviceSelect?.value || !userSelect?.value || !dateInput?.value) {
            slotSelect.innerHTML = '<option value="">Seleccione un servicio, profesional y fecha</option>';
            return;
        }

        slotSelect.innerHTML = '<option value="">Cargando horarios...</option>';

        const params = new URLSearchParams({
            service_id: serviceSelect.value,
            user_id: userSelect.value,
            date: dateInput.value,
        });

        try {
            const response = await fetch(`{{ route('appointments.availableSlots') }}?${params.toString()}`, {
                headers: { Accept: 'application/json' }
            });
            const payload = await response.json();
            const slots = Array.isArray(payload.slots) ? payload.slots : [];

            if (!slots.length) {
                slotSelect.innerHTML = '<option value="">No hay horarios disponibles</option>';
                return;
            }

            slotSelect.innerHTML = '<option value="">Seleccione una hora</option>';
            slots.forEach((slot) => {
                const option = document.createElement('option');
                option.value = slot.start;
                option.textContent = slot.label;
                slotSelect.appendChild(option);
            });
        } catch (error) {
            console.error(error);
            slotSelect.innerHTML = '<option value="">No se pudieron cargar los horarios</option>';
        }
    }

    document.querySelectorAll('[data-calendar-date]').forEach((button) => {
        button.addEventListener('click', () => {
            if (dateInput) {
                dateInput.value = button.dataset.calendarDate;
                loadSlots();
            }
        });
    });

    consumptionsWrapper?.addEventListener('click', (event) => {
        const removeBtn = event.target.closest('.appointment-remove-consumption');
        if (!removeBtn) return;
        removeBtn.closest('.appointment-consumption-row')?.remove();
    });

    consumptionsWrapper?.addEventListener('change', (event) => {
        const row = event.target.closest('.appointment-consumption-row');
        if (row) {
            updateConsumptionMeta(row);
        }
    });

    addConsumptionBtn?.addEventListener('click', () => addConsumptionRow());

    serviceSelect?.addEventListener('change', () => {
        syncServiceMetadata();
        loadSlots();
    });
    userSelect?.addEventListener('change', loadSlots);
    dateInput?.addEventListener('change', loadSlots);
    paymentMethodSelect?.addEventListener('change', syncPaymentReferenceRequirement);
    serviceProductSelect?.addEventListener('change', () => {
        const selectedOption = serviceProductSelect.selectedOptions?.[0];
        if (selectedOption && serviceNameInput && !serviceNameInput.value.trim()) {
            serviceNameInput.value = selectedOption.textContent.trim();
        }
    });

    syncServiceMetadata();
    syncPaymentReferenceRequirement();
    renderCalendar();
    loadSlots();
});
</script>
@endpush
