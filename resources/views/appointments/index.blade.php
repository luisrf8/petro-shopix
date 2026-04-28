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
        transition: transform 0.22s ease, box-shadow 0.22s ease, filter 0.22s ease;
    }

    .appointments-calendar-event.is-realtime-new {
        animation: appointmentRealtimeNew 1.15s ease;
    }

    .appointments-calendar-event.is-realtime-updated {
        animation: appointmentRealtimeUpdated 1.35s ease;
    }

    .appointments-calendar-day-column.is-realtime-removed {
        animation: appointmentRealtimeColumnPulse 1.2s ease;
    }

    @keyframes appointmentRealtimeNew {
        0% {
            opacity: 0;
            transform: translateY(10px) scale(0.97);
            filter: brightness(1.12);
        }
        45% {
            opacity: 1;
            transform: translateY(-1px) scale(1.01);
        }
        100% {
            opacity: 1;
            transform: translateY(0) scale(1);
            filter: brightness(1);
        }
    }

    @keyframes appointmentRealtimeUpdated {
        0% {
            box-shadow: 0 0 0 0 rgba(56, 189, 248, 0);
            filter: brightness(1);
        }
        35% {
            box-shadow: 0 0 0 3px rgba(56, 189, 248, 0.45);
            filter: brightness(1.14);
        }
        100% {
            box-shadow: 0 14px 28px rgba(15, 23, 42, 0.16);
            filter: brightness(1);
        }
    }

    @keyframes appointmentRealtimeColumnPulse {
        0% {
            box-shadow: inset 0 0 0 0 rgba(239, 68, 68, 0);
        }
        30% {
            box-shadow: inset 0 0 0 2px rgba(239, 68, 68, 0.42);
        }
        100% {
            box-shadow: inset 0 0 0 0 rgba(239, 68, 68, 0);
        }
    }

    .appointments-calendar-event-ribbon {
        position: absolute;
        top: 0;
        right: 0;
        width: 26px;
        height: 26px;
        clip-path: polygon(100% 0, 0 0, 100% 100%);
        opacity: 0.95;
    }

    .appointments-calendar-event-indicators {
        margin-top: 0.25rem;
        display: flex;
        flex-wrap: wrap;
        gap: 0.25rem;
    }

    .appointment-state-chip {
        display: inline-flex;
        align-items: center;
        gap: 0.32rem;
        padding: 0.18rem 0.46rem;
        border-radius: 999px;
        background: rgba(255, 255, 255, 0.14);
        font-size: 0.66rem;
        line-height: 1;
        font-weight: 600;
    }

    .appointment-state-dot {
        width: 8px;
        height: 8px;
        border-radius: 999px;
        display: inline-block;
        box-shadow: 0 0 0 2px rgba(15, 23, 42, 0.28);
        flex-shrink: 0;
    }

    .appointment-payment-summary {
        border: 1px solid rgba(15, 23, 42, 0.08);
        border-radius: 0.9rem;
        background: #f8fafc;
        padding: 0.75rem;
    }

    .appointment-payment-summary-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 0.5rem;
    }

    .appointment-payment-summary-item {
        border-radius: 0.7rem;
        border: 1px solid rgba(148, 163, 184, 0.24);
        background: #ffffff;
        padding: 0.45rem 0.55rem;
    }

    .appointment-payment-summary-label {
        display: block;
        color: #64748b;
        font-size: 0.72rem;
    }

    .appointment-payment-summary-value {
        display: block;
        color: #0f172a;
        font-size: 0.95rem;
        font-weight: 700;
        line-height: 1.15;
    }

    .appointment-toggle-chip {
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 0.35rem;
        border-radius: 999px;
        border: 1px solid rgba(15, 23, 42, 0.14);
        background: #fff;
        font-size: 0.75rem;
        font-weight: 700;
        padding: 0.38rem 0.7rem;
    }

    .appointment-toggle-chip.is-active {
        background: #0f172a;
        color: #ffffff;
        border-color: #0f172a;
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

    .appointments-mobile-fab {
        position: fixed !important;
        right: 1rem;
        bottom: 1rem;
        z-index: 1200;
        width: 54px;
        height: 54px;
        border-radius: 999px;
        padding: 0;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 1.15rem;
        box-shadow: 0 14px 26px rgba(15, 23, 42, 0.24);
    }

    .appointments-mobile-fab-icon {
        width: 22px;
        height: 22px;
        color: #ffffff;
        display: block;
    }

    .appointments-mobile-actions-panel .offcanvas-body {
        background: #f8fafc;
    }

    .appointments-mobile-actions-panel .appointments-stat-card {
        box-shadow: none;
    }

    #appointmentsMobileActionsPanel .btn-close,
    #appointmentBookingModal .btn-close,
    #appointmentServicesModal .btn-close,
    #appointmentSchedulesModal .btn-close,
    #appointmentUpcomingModal .btn-close {
        position: relative;
        width: 2rem;
        height: 2rem;
        border-radius: 999px;
        border: 1px solid rgba(15, 23, 42, 0.18);
        background: #ffffff !important;
        background-image: none !important;
        opacity: 1 !important;
        filter: none !important;
    }

    #appointmentsMobileActionsPanel .btn-close::before,
    #appointmentBookingModal .btn-close::before,
    #appointmentServicesModal .btn-close::before,
    #appointmentSchedulesModal .btn-close::before,
    #appointmentUpcomingModal .btn-close::before {
        content: '×';
        position: absolute;
        top: 50%;
        left: 50%;
        transform: translate(-50%, -56%);
        color: #0f172a;
        font-size: 1.25rem;
        font-weight: 700;
        line-height: 1;
    }

    #appointmentsMobileActionsPanel .btn-close:hover,
    #appointmentBookingModal .btn-close:hover,
    #appointmentServicesModal .btn-close:hover,
    #appointmentSchedulesModal .btn-close:hover,
    #appointmentUpcomingModal .btn-close:hover {
        background: #f8fafc !important;
    }

    @media (max-width: 1199.98px) {
        .appointments-calendar-grid {
            min-width: 900px;
        }
    }

    @media (max-width: 991.98px) {
        .appointments-top-cards-row {
            display: none;
        }

        .appointments-mobile-fab {
            display: inline-flex !important;
        }

        .appointments-calendar-grid {
            min-width: 0;
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

    @if(session('success'))
        <div class="alert alert-success border text-white">{{ session('success') }}</div>
    @endif

    @if($errors->any())
        <div class="alert alert-danger border text-white">{{ $errors->first() }}</div>
    @endif

    @if(!$serviceBusinessType)
        <div class="alert alert-warning text-dark border">La tienda no está marcada como tipo servicio. El módulo funciona, pero conviene configurar el negocio como servicio para mantener la segmentación correcta.</div>
    @endif

    <div class="row g-3 mb-4 appointments-top-cards-row d-none d-lg-flex">
        <div class="col-12 col-md-4">
            <div class="appointments-stat-card">
                <div class="appointments-stat-label">Profesionales activos</div>
                <div class="appointments-stat-value">{{ $professionals->count() }}</div>
                <div class="d-grid mt-2">
                    <button type="button" class="btn btn-dark btn-sm mb-0" data-bs-toggle="modal" data-bs-target="#appointmentSchedulesModal">Turnos y horarios</button>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="appointments-stat-card">
                <div class="appointments-stat-label">Servicios configurados</div>
                <div class="appointments-stat-value">{{ $services->count() }}</div>
                <div class="d-grid mt-2">
                    <button type="button" class="btn btn-outline-dark btn-sm mb-0" data-bs-toggle="modal" data-bs-target="#appointmentServicesModal">Servicios</button>
                </div>
            </div>
        </div>
        <div class="col-12 col-md-4">
            <div class="appointments-stat-card">
                <div class="appointments-stat-label">Citas en la semana</div>
                <div class="appointments-stat-value" id="appointmentsWeekCountValue">{{ count($calendarEvents ?? []) }}</div>
                <div class="appointment-inline-note" id="appointmentsWeekRangeNote">Semana de {{ $calendarWeekStart->format('d/m') }} a {{ $calendarWeekEnd->format('d/m') }}.</div>
                <div class="d-flex flex-wrap gap-2 mt-2">
                    <button type="button" class="btn btn-success btn-sm mb-0" data-bs-toggle="modal" data-bs-target="#appointmentBookingModal">Registrar cita</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm mb-0" data-bs-toggle="modal" data-bs-target="#appointmentUpcomingModal">Próximas citas</button>
                </div>
            </div>
        </div>
    </div>

    <div class="row g-3">
        <div class="col-12">
            <div class="appointments-calendar-card h-100" id="appointmentsCalendarCard">
                <div class="card-body">
                    <div class="d-flex justify-content-end mb-2">
                        <button id="appointmentsFiltersToggleButton" class="btn btn-outline-dark btn-sm mb-0" type="button" aria-expanded="true" aria-controls="appointmentsFiltersCollapse">
                            <span id="appointmentsFiltersToggleLabel">Ocultar filtros</span>
                        </button>
                    </div>

                    <div id="appointmentsFiltersCollapse">
                        <form method="GET" action="{{ route('appointments.index') }}" class="d-flex flex-wrap gap-2 align-items-end mb-3">
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

                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                        <div class="btn-group" role="group" aria-label="Vista calendario">
                            <button type="button" class="btn btn-outline-secondary btn-sm" data-calendar-view="day">Día</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm active" data-calendar-view="week">Semana</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm" data-calendar-view="month">Mes</button>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                        <div>
                            <h4 class="mb-1" id="appointmentsWeekRangeTitle">{{ \Illuminate\Support\Str::ucfirst($calendarWeekStart->translatedFormat('d M')) }} - {{ \Illuminate\Support\Str::ucfirst($calendarWeekEnd->translatedFormat('d M Y')) }}</h4>
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

                    <div class="appointments-calendar-scroll" id="appointmentsCalendarScroll">
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

                    <div id="appointmentsMonthView" class="d-none">
                        <div class="appointments-form-section-title mb-2">Agenda del mes (vista rápida)</div>
                        <div id="appointmentsMonthList" class="d-flex flex-column gap-2"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<button type="button" class="btn btn-dark appointments-mobile-fab d-lg-none" data-bs-toggle="offcanvas" data-bs-target="#appointmentsMobileActionsPanel" aria-controls="appointmentsMobileActionsPanel" aria-label="Abrir acciones rápidas">
    <svg class="appointments-mobile-fab-icon" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true" focusable="false">
        <path d="M4 7H20" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        <path d="M4 12H20" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
        <path d="M4 17H20" stroke="currentColor" stroke-width="2" stroke-linecap="round"/>
    </svg>
</button>

<div class="offcanvas offcanvas-end appointments-mobile-actions-panel" tabindex="-1" id="appointmentsMobileActionsPanel" aria-labelledby="appointmentsMobileActionsPanelLabel">
    <div class="offcanvas-header">
        <h5 class="offcanvas-title" id="appointmentsMobileActionsPanelLabel">Acciones y resumen</h5>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Cerrar"></button>
    </div>
    <div class="offcanvas-body">
        <div class="d-flex flex-column gap-3">
            <div class="appointments-stat-card">
                <div class="appointments-stat-label">Profesionales activos</div>
                <div class="appointments-stat-value">{{ $professionals->count() }}</div>
                <div class="d-grid mt-2">
                    <button type="button" class="btn btn-dark btn-sm mb-0" data-bs-dismiss="offcanvas" data-bs-toggle="modal" data-bs-target="#appointmentSchedulesModal">Turnos y horarios</button>
                </div>
            </div>
            <div class="appointments-stat-card">
                <div class="appointments-stat-label">Servicios configurados</div>
                <div class="appointments-stat-value">{{ $services->count() }}</div>
                <div class="d-grid mt-2">
                    <button type="button" class="btn btn-outline-dark btn-sm mb-0" data-bs-dismiss="offcanvas" data-bs-toggle="modal" data-bs-target="#appointmentServicesModal">Servicios</button>
                </div>
            </div>
            <div class="appointments-stat-card">
                <div class="appointments-stat-label">Citas en la semana</div>
                <div class="appointments-stat-value">{{ count($calendarEvents ?? []) }}</div>
                <div class="appointment-inline-note">Semana de {{ $calendarWeekStart->format('d/m') }} a {{ $calendarWeekEnd->format('d/m') }}.</div>
                <div class="d-flex flex-wrap gap-2 mt-2">
                    <button type="button" class="btn btn-success btn-sm mb-0" data-bs-dismiss="offcanvas" data-bs-toggle="modal" data-bs-target="#appointmentBookingModal">Registrar cita</button>
                    <button type="button" class="btn btn-outline-secondary btn-sm mb-0" data-bs-dismiss="offcanvas" data-bs-toggle="modal" data-bs-target="#appointmentUpcomingModal">Próximas citas</button>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="appointmentBookingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Registrar cita</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <form method="POST" action="{{ route('appointments.store') }}" class="row g-2" id="appointmentBookingForm">
                    @csrf
                    <input type="hidden" name="appointment_id" id="appointmentIdInput" value="">
                    <input type="hidden" name="create_customer" id="appointmentCreateCustomerInput" value="0">
                    <div class="col-12 col-lg-6">
                        <label class="form-label">Servicio</label>
                        <select name="appointment_service_id" id="appointmentServiceSelect" class="form-control border border-1 p-2" required>
                            <option value="">Seleccione</option>
                            @foreach($services as $service)
                                <option value="{{ $service->id }}" {{ (string) old('appointment_service_id') === (string) $service->id ? 'selected' : '' }}>{{ $service->display_name }} · {{ $service->duration_minutes }} min</option>
                            @endforeach
                        </select>
                        <small id="appointmentServiceMeta" class="appointment-inline-note d-block mt-1">Selecciona el servicio-producto a reservar.</small>
                    </div>
                    <div class="col-12 col-lg-6">
                        <label class="form-label">Profesional</label>
                        <select name="user_id" id="appointmentUserSelect" class="form-control border border-1 p-2" required>
                            <option value="">Seleccione</option>
                            @foreach($professionals as $professional)
                                <option value="{{ $professional->id }}" {{ (string) old('user_id', $selectedUserId > 0 ? $selectedUserId : '') === (string) $professional->id ? 'selected' : '' }}>{{ $professional->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <label class="form-label">Fecha</label>
                        <input type="date" name="scheduled_date" id="appointmentDateInput" class="form-control border border-1 p-2" value="{{ old('scheduled_date', $selectedDate->toDateString()) }}" required>
                    </div>
                    <div class="col-md-6 col-lg-4">
                        <label class="form-label">Hora disponible</label>
                        <select name="start_time" id="appointmentSlotSelect" class="form-control border border-1 p-2" required>
                            <option value="">Seleccione un servicio, profesional y fecha</option>
                        </select>
                    </div>
                    <div class="col-12 col-lg-8">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="form-label mb-0">Cliente existente</label>
                            <button type="button" class="appointment-toggle-chip mb-0" id="appointmentToggleNewCustomerBtn">Cliente nuevo</button>
                        </div>
                        <select name="customer_id" id="appointmentCustomerSelect" class="form-control border border-1 p-2">
                            <option value="">Sin cliente registrado</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}" {{ (string) old('customer_id') === (string) $customer->id ? 'selected' : '' }}>{{ $customer->name }}{{ $customer->phone_number ? ' · ' . $customer->phone_number : '' }}</option>
                            @endforeach
                        </select>
                        <small class="appointment-inline-note d-block mt-1" id="appointmentCustomerModeHint">Selecciona cliente existente o activa “Cliente nuevo”.</small>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label">Nombre de contacto</label>
                        <input type="text" name="contact_name" class="form-control border border-1 p-2" value="{{ old('contact_name') }}" placeholder="Si no hay cliente registrado">
                    </div>
                    <div class="col-4 col-md-2">
                        <label class="form-label">Código</label>
                        <select name="contact_phone_code" id="appointmentContactPhoneCodeInput" class="form-control border border-1 p-2">
                            <option value="+58" {{ old('contact_phone_code', '+58') === '+58' ? 'selected' : '' }}>+58</option>
                            <option value="+1" {{ old('contact_phone_code') === '+1' ? 'selected' : '' }}>+1</option>
                            <option value="+52" {{ old('contact_phone_code') === '+52' ? 'selected' : '' }}>+52</option>
                            <option value="+57" {{ old('contact_phone_code') === '+57' ? 'selected' : '' }}>+57</option>
                            <option value="+51" {{ old('contact_phone_code') === '+51' ? 'selected' : '' }}>+51</option>
                            <option value="+54" {{ old('contact_phone_code') === '+54' ? 'selected' : '' }}>+54</option>
                            <option value="+34" {{ old('contact_phone_code') === '+34' ? 'selected' : '' }}>+34</option>
                        </select>
                    </div>
                    <div class="col-8 col-md-4">
                        <label class="form-label">Teléfono</label>
                        <input type="text" name="contact_phone" id="appointmentContactPhoneInput" class="form-control border border-1 p-2" value="{{ old('contact_phone') }}" placeholder="4120000000">
                    </div>
                    <div class="col-md-6 d-none" id="appointmentNewCustomerExtra">
                        <label class="form-label">Email cliente (opcional)</label>
                        <input type="email" name="customer_email" id="appointmentCustomerEmailInput" class="form-control border border-1 p-2" value="{{ old('customer_email') }}" placeholder="cliente@correo.com">
                    </div>
                    <div class="col-md-6 d-none" id="appointmentNewCustomerExtraDni">
                        <label class="form-label">DNI cliente (opcional)</label>
                        <input type="text" name="customer_dni" id="appointmentCustomerDniInput" class="form-control border border-1 p-2" value="{{ old('customer_dni') }}">
                    </div>
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <label class="form-label mb-0">Consumibles utilizados</label>
                            <button type="button" class="btn btn-outline-dark btn-sm mb-0" id="addAppointmentConsumptionBtn">Agregar consumible</button>
                        </div>
                        <div id="appointmentConsumptionsWrapper" class="d-flex flex-column gap-2"></div>
                        <small class="appointment-inline-note d-block mt-1">Registra solo los consumibles realmente usados en la atención.</small>
                    </div>
                    <div class="col-12">
                        <div class="appointment-payment-summary">
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="appointments-form-section-title mb-0">Pago de la cita</span>
                                <button type="button" class="btn btn-outline-dark btn-sm mb-0" data-bs-toggle="collapse" data-bs-target="#appointmentPaymentCollapse" aria-expanded="false" aria-controls="appointmentPaymentCollapse">Detalles de pago</button>
                            </div>
                            <div class="appointment-payment-summary-grid">
                                <div class="appointment-payment-summary-item">
                                    <span class="appointment-payment-summary-label">Precio servicio USD</span>
                                    <span class="appointment-payment-summary-value" id="appointmentServicePriceUsd">0.00</span>
                                </div>
                                <div class="appointment-payment-summary-item">
                                    <span class="appointment-payment-summary-label">Precio servicio Bs</span>
                                    <span class="appointment-payment-summary-value" id="appointmentServicePriceBs">0.00</span>
                                </div>
                                <div class="appointment-payment-summary-item">
                                    <span class="appointment-payment-summary-label">Monto pagado</span>
                                    <span class="appointment-payment-summary-value" id="appointmentPaidAmountLabel">0.00</span>
                                </div>
                                <div class="appointment-payment-summary-item">
                                    <span class="appointment-payment-summary-label">Saldo pendiente</span>
                                    <span class="appointment-payment-summary-value" id="appointmentPendingAmountLabel">0.00</span>
                                </div>
                            </div>
                            <small class="appointment-inline-note d-block mt-2" id="appointmentFractionedBadge">Pago único o sin pago registrado.</small>
                        </div>
                    </div>
                    <div class="col-12 collapse" id="appointmentPaymentCollapse">
                        <div class="row g-2">
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
                                <select name="payment_status" id="appointmentPaymentStatusSelect" class="form-control border border-1 p-2">
                                    @foreach(\App\Models\Appointment::PAYMENT_STATUSES as $statusKey => $statusLabel)
                                        <option value="{{ $statusKey }}" {{ old('payment_status', 'pending') === $statusKey ? 'selected' : '' }}>{{ $statusLabel }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
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
                    <div class="col-12 d-none" id="appointmentWorkflowActionsWrap">
                        <div class="d-flex flex-wrap gap-2">
                            <button type="button" class="btn btn-outline-dark btn-sm mb-0" data-appointment-workflow-action="call_customer">Llamar cliente</button>
                            <a href="#" target="_blank" rel="noopener" class="btn btn-outline-success btn-sm mb-0 d-none" id="appointmentAdminWhatsappButton">WhatsApp cliente</a>
                            <button type="button" class="btn btn-outline-success btn-sm mb-0" data-appointment-workflow-action="confirm_attendance">Confirmar asistencia</button>
                            <button type="button" class="btn btn-outline-primary btn-sm mb-0" data-appointment-workflow-action="reschedule">Reprogramar</button>
                            <button type="button" class="btn btn-outline-danger btn-sm mb-0" data-appointment-workflow-action="cancel">Cancelar</button>
                            <button type="button" class="btn btn-outline-warning btn-sm mb-0" data-appointment-workflow-action="no_show">No asistió</button>
                            <button type="button" class="btn btn-success btn-sm mb-0" data-appointment-workflow-action="confirm_payment">Confirmar pago y crear venta</button>
                        </div>
                        <small class="appointment-inline-note d-block mt-1">Estas acciones aplican a citas ya existentes y disparan notificaciones del flujo.</small>
                    </div>
                    <div class="col-12">
                        <button class="btn btn-success w-100 mb-0" id="appointmentSubmitButton" type="submit">Agendar cita</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="appointmentServicesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Servicios</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
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
    </div>
</div>

<div class="modal fade" id="appointmentSchedulesModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Turnos y horarios</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="d-flex justify-content-end mb-2">
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

                <hr>
                <h6 class="mb-2">Paquetes de citas</h6>
                <form method="POST" action="{{ route('appointments.packages.store') }}" class="row g-2 mb-3">
                    @csrf
                    <div class="col-12 col-md-6">
                        <label class="form-label">Nombre del paquete</label>
                        <input type="text" name="name" class="form-control border border-1 p-2" placeholder="Ej: 10 sesiones de corte" required>
                    </div>
                    <div class="col-12 col-md-6">
                        <label class="form-label">Servicio base</label>
                        <select name="appointment_service_id" class="form-control border border-1 p-2" required>
                            @foreach($services as $service)
                                <option value="{{ $service->id }}">{{ $service->display_name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label">N° de sesiones</label>
                        <input type="number" name="sessions_count" min="1" max="60" value="10" class="form-control border border-1 p-2" required>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label">Cada (semanas)</label>
                        <input type="number" name="repeat_every_weeks" min="1" max="12" value="1" class="form-control border border-1 p-2" required>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label">Día semanal</label>
                        <select name="preferred_day_of_week" class="form-control border border-1 p-2">
                            @foreach(
                                \App\Models\UserScheduleRule::WEEK_DAYS as $dayIndex => $dayLabel
                            )
                                <option value="{{ $dayIndex }}">{{ $dayLabel }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label">Hora</label>
                        <input type="time" name="preferred_time" class="form-control border border-1 p-2" value="09:00" required>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label">Profesional</label>
                        <select name="user_id" class="form-control border border-1 p-2" required>
                            @foreach($professionals as $professional)
                                <option value="{{ $professional->id }}">{{ $professional->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12 col-md-4">
                        <label class="form-label">Cliente (opcional)</label>
                        <select name="customer_id" class="form-control border border-1 p-2">
                            <option value="">Sin asignar</option>
                            @foreach($customers as $customer)
                                <option value="{{ $customer->id }}">{{ $customer->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label">Inicio</label>
                        <input type="date" name="start_date" class="form-control border border-1 p-2" value="{{ now()->toDateString() }}" required>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label">Precio</label>
                        <input type="number" name="price" min="0" step="0.01" value="0" class="form-control border border-1 p-2">
                    </div>
                    <div class="col-12">
                        <button class="btn btn-outline-dark w-100 mb-0" type="submit">Crear paquete y agendar sesiones</button>
                    </div>
                </form>

                <div class="d-flex flex-column gap-2" style="max-height: 320px; overflow:auto;">
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
    </div>
</div>

<div class="modal fade" id="appointmentUpcomingModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Próximas citas</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
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
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    let appointmentEvents = @json($calendarEvents ?? []);
    const calendarDays = @json($calendarDays ?? []);
    const calendarStartHour = Number(@json($calendarBounds['startHour'] ?? 7));
    const calendarHourHeight = 72;
    const appointmentsRealtimeFeedUrl = @json(route('appointments.index'));
    const servicesPayload = @json($servicesPayload ?? []);
    const consumableVariants = @json($consumableVariantsPayload ?? []);
    const appointmentBsRate = Number(@json($bsRate ?? 0));

    const serviceSelect = document.getElementById('appointmentServiceSelect');
    const userSelect = document.getElementById('appointmentUserSelect');
    const dateInput = document.getElementById('appointmentDateInput');
    const slotSelect = document.getElementById('appointmentSlotSelect');
    const bookingForm = document.getElementById('appointmentBookingForm');
    const appointmentIdInput = document.getElementById('appointmentIdInput');
    const createCustomerInput = document.getElementById('appointmentCreateCustomerInput');
    const customerSelect = document.getElementById('appointmentCustomerSelect');
    const toggleNewCustomerBtn = document.getElementById('appointmentToggleNewCustomerBtn');
    const customerModeHint = document.getElementById('appointmentCustomerModeHint');
    const customerEmailInput = document.getElementById('appointmentCustomerEmailInput');
    const customerDniInput = document.getElementById('appointmentCustomerDniInput');
    const newCustomerExtra = document.getElementById('appointmentNewCustomerExtra');
    const newCustomerExtraDni = document.getElementById('appointmentNewCustomerExtraDni');
    const submitButton = document.getElementById('appointmentSubmitButton');
    const workflowActionsWrap = document.getElementById('appointmentWorkflowActionsWrap');
    const workflowActionButtons = Array.from(document.querySelectorAll('[data-appointment-workflow-action]'));
    const serviceMeta = document.getElementById('appointmentServiceMeta');
    const paymentMethodSelect = document.getElementById('appointmentPaymentMethodSelect');
    const paymentStatusSelect = document.getElementById('appointmentPaymentStatusSelect');
    const paidAmountInput = document.getElementById('appointmentPaidAmountInput');
    const paymentReferenceGroup = document.getElementById('appointmentPaymentReferenceGroup');
    const paymentReferenceInput = document.getElementById('appointmentPaymentReferenceInput');
    const servicePriceUsdLabel = document.getElementById('appointmentServicePriceUsd');
    const servicePriceBsLabel = document.getElementById('appointmentServicePriceBs');
    const paidAmountLabel = document.getElementById('appointmentPaidAmountLabel');
    const pendingAmountLabel = document.getElementById('appointmentPendingAmountLabel');
    const fractionedBadge = document.getElementById('appointmentFractionedBadge');
    const consumptionsWrapper = document.getElementById('appointmentConsumptionsWrapper');
    const addConsumptionBtn = document.getElementById('addAppointmentConsumptionBtn');
    const serviceProductSelect = document.getElementById('appointmentServiceProductSelect');
    const serviceNameInput = document.getElementById('appointmentServiceNameInput');
    const bookingModalElement = document.getElementById('appointmentBookingModal');
    const adminWhatsappButton = document.getElementById('appointmentAdminWhatsappButton');
    const contactNameInput = bookingForm?.querySelector('[name="contact_name"]') || null;
    const contactPhoneInput = bookingForm?.querySelector('[name="contact_phone"]') || null;
    const contactPhoneCodeInput = document.getElementById('appointmentContactPhoneCodeInput');
    const mobileFab = document.querySelector('.appointments-mobile-fab');
    const mobileActionsPanel = document.getElementById('appointmentsMobileActionsPanel');
    const calendarCard = document.getElementById('appointmentsCalendarCard');
    const calendarScroll = document.getElementById('appointmentsCalendarScroll');
    const calendarGrid = document.querySelector('.appointments-calendar-grid');
    const monthView = document.getElementById('appointmentsMonthView');
    const monthList = document.getElementById('appointmentsMonthList');
    const appointmentsWeekCountValue = document.getElementById('appointmentsWeekCountValue');
    const appointmentsWeekRangeNote = document.getElementById('appointmentsWeekRangeNote');
    const appointmentsWeekRangeTitle = document.getElementById('appointmentsWeekRangeTitle');
    const filtersCollapseElement = document.getElementById('appointmentsFiltersCollapse');
    const filtersToggleButton = document.getElementById('appointmentsFiltersToggleButton');
    const filtersToggleLabel = document.getElementById('appointmentsFiltersToggleLabel');
    const calendarViewButtons = Array.from(document.querySelectorAll('[data-calendar-view]'));
    const isMobileQuery = window.matchMedia('(max-width: 767.98px)');
    const bookingModal = bookingModalElement && typeof bootstrap !== 'undefined'
        ? bootstrap.Modal.getOrCreateInstance(bookingModalElement)
        : null;
    let calendarView = isMobileQuery.matches ? 'day' : 'week';
    let activeCalendarDate = (calendarDays.find((day) => day.is_today)?.date)
        || (dateInput?.value || '')
        || (calendarDays[0]?.date || '');

    function resetBookingFormMode() {
        if (appointmentIdInput) {
            appointmentIdInput.value = '';
        }

        workflowActionsWrap?.classList.add('d-none');

        if (submitButton) {
            submitButton.textContent = 'Agendar cita';
            submitButton.classList.remove('btn-warning');
            submitButton.classList.add('btn-success');
        }

        if (adminWhatsappButton) {
            adminWhatsappButton.classList.add('d-none');
            adminWhatsappButton.setAttribute('href', '#');
        }

        setCreateCustomerMode(false);
    }

    function setBookingFormEditMode() {
        if (submitButton) {
            submitButton.textContent = 'Actualizar cita';
            submitButton.classList.remove('btn-success');
            submitButton.classList.add('btn-warning');
        }

        workflowActionsWrap?.classList.remove('d-none');
    }

    function normalizeWhatsappPhone(phoneValue) {
        return String(phoneValue || '').replace(/\D/g, '');
    }

    function splitPhoneWithCode(value, fallbackCode = '+58') {
        const raw = String(value || '').trim();
        if (!raw) {
            return { code: fallbackCode, local: '' };
        }

        if (raw.startsWith('+')) {
            const match = raw.match(/^\+(\d{1,4})(\d+)$/);
            if (match) {
                return {
                    code: `+${match[1]}`,
                    local: match[2],
                };
            }

            return { code: fallbackCode, local: raw.replace(/\D/g, '') };
        }

        return { code: fallbackCode, local: raw.replace(/\D/g, '') };
    }

    function normalizePhoneForSubmit() {
        if (!contactPhoneInput) {
            return;
        }

        const rawDigits = String(contactPhoneInput.value || '').replace(/\D/g, '');
        const codeDigits = String(contactPhoneCodeInput?.value || '+58').replace(/\D/g, '');

        if (!rawDigits) {
            contactPhoneInput.value = '';
            return;
        }

        const normalizedCode = codeDigits ? `+${codeDigits}` : '+58';
        contactPhoneInput.value = `${normalizedCode}${rawDigits}`;
    }

    function setCreateCustomerMode(enabled) {
        const isEnabled = !!enabled;

        if (createCustomerInput) {
            createCustomerInput.value = isEnabled ? '1' : '0';
        }

        if (customerSelect) {
            customerSelect.disabled = isEnabled;
            if (isEnabled) {
                customerSelect.value = '';
            }
        }

        newCustomerExtra?.classList.toggle('d-none', !isEnabled);
        newCustomerExtraDni?.classList.toggle('d-none', !isEnabled);

        if (toggleNewCustomerBtn) {
            toggleNewCustomerBtn.classList.toggle('is-active', isEnabled);
            toggleNewCustomerBtn.textContent = isEnabled ? 'Usar cliente existente' : 'Cliente nuevo';
        }

        if (customerModeHint) {
            customerModeHint.textContent = isEnabled
                ? 'Se creará cliente nuevo con nombre/teléfono y opcionales.'
                : 'Selecciona cliente existente o activa “Cliente nuevo”.';
        }

        if (!isEnabled) {
            if (customerEmailInput) customerEmailInput.value = '';
            if (customerDniInput) customerDniInput.value = '';
        }
    }

    function syncPaymentSummary() {
        const selectedService = getSelectedService();
        const servicePriceUsd = Number(selectedService?.price || 0);
        const paidAmount = Number(paidAmountInput?.value || 0);
        const pendingAmount = Math.max(0, servicePriceUsd - paidAmount);
        const servicePriceBs = appointmentBsRate > 0 ? (servicePriceUsd * appointmentBsRate) : 0;
        const isFractioned = paidAmount > 0 && pendingAmount > 0;

        if (servicePriceUsdLabel) {
            servicePriceUsdLabel.textContent = `${servicePriceUsd.toFixed(2)} $`;
        }

        if (servicePriceBsLabel) {
            servicePriceBsLabel.textContent = appointmentBsRate > 0
                ? `${servicePriceBs.toFixed(2)} Bs (tasa ${appointmentBsRate.toFixed(2)})`
                : 'Sin tasa Bs';
        }

        if (paidAmountLabel) {
            paidAmountLabel.textContent = `${paidAmount.toFixed(2)} $`;
        }

        if (pendingAmountLabel) {
            pendingAmountLabel.textContent = `${pendingAmount.toFixed(2)} $`;
        }

        if (fractionedBadge) {
            fractionedBadge.textContent = isFractioned
                ? 'Pago fraccionado activo: hay abono y saldo pendiente.'
                : 'Pago único o sin pago registrado.';
        }
    }

    function resolveAppointmentStatusColor(statusKey) {
        const normalized = String(statusKey || '').toLowerCase();
        if (normalized === 'confirmed') return '#22c55e';
        if (normalized === 'completed') return '#14b8a6';
        if (normalized === 'cancelled') return '#ef4444';
        if (normalized === 'no_show') return '#f59e0b';
        return '#38bdf8';
    }

    function resolveAppointmentPaymentColor(paymentStatusKey) {
        const normalized = String(paymentStatusKey || '').toLowerCase();
        if (normalized === 'paid') return '#22c55e';
        if (normalized === 'partial') return '#f59e0b';
        if (normalized === 'waived') return '#60a5fa';
        return '#94a3b8';
    }

    function syncAdminWhatsappLink(eventData = null) {
        if (!adminWhatsappButton) {
            return;
        }

        const inputPhone = String(contactPhoneInput?.value || '').trim();
        const inputCode = String(contactPhoneCodeInput?.value || '').trim();
        const composedPhone = inputPhone !== '' ? `${inputCode}${inputPhone}` : '';
        const rawPhone = normalizeWhatsappPhone(eventData?.contact_phone || composedPhone);
        if (!rawPhone) {
            adminWhatsappButton.classList.add('d-none');
            adminWhatsappButton.setAttribute('href', '#');
            return;
        }

        const customerName = String(eventData?.customer || contactNameInput?.value || 'cliente').trim();
        const serviceLabel = String(eventData?.title || serviceSelect?.selectedOptions?.[0]?.textContent || 'tu cita').trim();
        const dateLabel = String(eventData?.date || dateInput?.value || '').trim();
        const timeLabel = String(eventData?.start_time || slotSelect?.value || '').trim();
        const message = encodeURIComponent(`Hola ${customerName}, te escribimos para coordinar tu cita de ${serviceLabel}${dateLabel ? ` el ${dateLabel}` : ''}${timeLabel ? ` a las ${timeLabel}` : ''}.`);

        adminWhatsappButton.setAttribute('href', `https://wa.me/${rawPhone}?text=${message}`);
        adminWhatsappButton.classList.remove('d-none');
    }

    async function runWorkflowAction(actionKey) {
        const appointmentId = Number(appointmentIdInput?.value || 0);
        if (appointmentId <= 0) {
            alert('Debes abrir una cita existente para usar esta acción.');
            return;
        }

        const payload = {
            action: actionKey,
            note: bookingForm?.querySelector('[name="notes"]')?.value || '',
            create_sale: true,
        };

        if (actionKey === 'reschedule') {
            payload.scheduled_date = dateInput?.value || '';
            payload.start_time = slotSelect?.value || '';
        }

        if (actionKey === 'confirm_payment') {
            payload.payment_method_id = paymentMethodSelect?.value || '';
            payload.paid_amount = bookingForm?.querySelector('[name="paid_amount"]')?.value || '';
            payload.payment_reference = paymentReferenceInput?.value || '';
        }

        const endpoint = `{{ route('appointments.workflow', ['appointment' => '__APPOINTMENT__']) }}`.replace('__APPOINTMENT__', String(appointmentId));

        try {
            workflowActionButtons.forEach((button) => {
                button.disabled = true;
            });

            const response = await fetch(endpoint, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '',
                },
                body: JSON.stringify(payload),
            });

            const data = await response.json().catch(() => ({}));
            if (!response.ok || data?.success === false) {
                alert(data?.message || 'No se pudo ejecutar la acción de la cita.');
                return;
            }

            alert(data?.message || 'Acción aplicada correctamente.');
            await refreshCalendarRealtime(true);
            await loadSlots();
        } catch (error) {
            console.error(error);
            alert('No se pudo ejecutar la acción de la cita.');
        } finally {
            workflowActionButtons.forEach((button) => {
                button.disabled = false;
            });
        }
    }

    function setActiveCalendarDate(dateValue) {
        if (!dateValue) {
            return;
        }

        activeCalendarDate = dateValue;

        document.querySelectorAll('[data-calendar-column]').forEach((column) => {
            column.dataset.active = column.dataset.calendarColumn === activeCalendarDate ? '1' : '0';
        });

        document.querySelectorAll('[data-calendar-date]').forEach((button) => {
            const isActive = button.dataset.calendarDate === activeCalendarDate;
            button.classList.toggle('is-today', isActive);
        });
    }

    function getCalendarEventKey(event) {
        if (!event) {
            return '';
        }

        const id = Number(event.id || 0);
        if (id > 0) {
            return `id:${id}`;
        }

        return [
            String(event.date || ''),
            String(event.start_time || ''),
            String(event.end_time || ''),
            String(event.title || ''),
            String(event.professional || ''),
            String(event.customer || ''),
        ].join('|');
    }

    function getCalendarEventSignature(event) {
        if (!event) {
            return '';
        }

        return [
            String(event.date || ''),
            String(event.start_time || ''),
            String(event.end_time || ''),
            String(event.status_key || ''),
            String(event.payment_status_key || ''),
            String(event.title || ''),
            String(event.professional || ''),
            String(event.customer || ''),
        ].join('|');
    }

    function renderMonthView() {
        if (!monthList) {
            return;
        }

        const sortedEvents = [...appointmentEvents].sort((a, b) => {
            const aDate = `${a.date} ${a.start_time}`;
            const bDate = `${b.date} ${b.start_time}`;
            return aDate.localeCompare(bDate);
        });

        if (!sortedEvents.length) {
            monthList.innerHTML = '<div class="text-muted">No hay citas para mostrar en esta vista.</div>';
            return;
        }

        const items = sortedEvents.map((event) => {
            const statusColor = resolveAppointmentStatusColor(event.status_key);
            const paymentColor = resolveAppointmentPaymentColor(event.payment_status_key);
            return `
                <div class="appointment-upcoming-item">
                    <div class="fw-semibold">${event.title}</div>
                    <div class="appointment-inline-note">${event.date} · ${event.start_time} - ${event.end_time} · ${event.professional}</div>
                    <div>${event.customer}</div>
                    <div class="appointments-calendar-event-indicators mt-1">
                        <span class="appointment-state-chip"><span class="appointment-state-dot" style="background:${statusColor};"></span>${event.status}</span>
                        <span class="appointment-state-chip"><span class="appointment-state-dot" style="background:${paymentColor};"></span>${event.payment_status}</span>
                    </div>
                </div>
            `;
        });

        monthList.innerHTML = items.join('');
    }

    function applyCalendarView() {
        if (!calendarGrid || !calendarScroll || !monthView) {
            return;
        }

        const headers = Array.from(document.querySelectorAll('[data-calendar-date]'));
        const columns = Array.from(document.querySelectorAll('[data-calendar-column]'));
        const timeHeader = document.querySelector('.appointments-calendar-time-header');

        calendarViewButtons.forEach((button) => {
            const isActiveView = button.dataset.calendarView === calendarView;
            button.classList.toggle('active', isActiveView);
            button.classList.toggle('btn-dark', isActiveView);
            button.classList.toggle('btn-outline-secondary', !isActiveView);
        });

        if (calendarView === 'month') {
            calendarScroll.classList.add('d-none');
            monthView.classList.remove('d-none');
            renderMonthView();
            return;
        }

        monthView.classList.add('d-none');
        calendarScroll.classList.remove('d-none');

        if (calendarView === 'day') {
            const existingDay = calendarDays.some((day) => day.date === activeCalendarDate);
            if (!existingDay) {
                activeCalendarDate = calendarDays[0]?.date || activeCalendarDate;
            }

            headers.forEach((header) => {
                const shouldShow = header.dataset.calendarDate === activeCalendarDate;
                header.style.display = shouldShow ? '' : 'none';
            });

            columns.forEach((column) => {
                const shouldShow = column.dataset.calendarColumn === activeCalendarDate;
                column.style.display = shouldShow ? '' : 'none';
            });

            if (timeHeader) {
                timeHeader.style.display = '';
            }

            calendarGrid.style.gridTemplateColumns = '88px minmax(220px, 1fr)';
            calendarGrid.style.minWidth = '0';
            setActiveCalendarDate(activeCalendarDate);
            return;
        }

        headers.forEach((header) => {
            header.style.display = '';
        });

        columns.forEach((column) => {
            column.style.display = '';
        });

        if (timeHeader) {
            timeHeader.style.display = '';
        }

        calendarGrid.style.gridTemplateColumns = '';
        calendarGrid.style.minWidth = '';
        setActiveCalendarDate(activeCalendarDate);
    }

    function renderCalendar(options = {}) {
        const previousEvents = Array.isArray(options.previousEvents) ? options.previousEvents : [];
        const realtimeMode = !!options.realtime;
        const previousMap = new Map(previousEvents.map((event) => [getCalendarEventKey(event), event]));
        const currentMap = new Map(appointmentEvents.map((event) => [getCalendarEventKey(event), event]));
        const removedDates = new Set();

        previousMap.forEach((prevEvent, key) => {
            if (key && !currentMap.has(key)) {
                const prevDate = String(prevEvent?.date || '').trim();
                if (prevDate) {
                    removedDates.add(prevDate);
                }
            }
        });

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
                const statusColor = resolveAppointmentStatusColor(event.status_key);
                const paymentColor = resolveAppointmentPaymentColor(event.payment_status_key);
                const eventKey = getCalendarEventKey(event);
                const previousEvent = previousMap.get(eventKey);
                const previousSignature = getCalendarEventSignature(previousEvent);
                const currentSignature = getCalendarEventSignature(event);
                card.type = 'button';
                card.className = 'appointments-calendar-event';
                card.style.top = `${(event.minutes_from_start / 60) * calendarHourHeight + 4}px`;
                card.style.height = `${Math.max(54, (event.duration_minutes / 60) * calendarHourHeight - 8)}px`;
                card.style.background = event.color_hex || '#0f172a';
                card.innerHTML = `
                    <span class="appointments-calendar-event-ribbon" style="background:${statusColor};"></span>
                    <div class="appointments-calendar-event-title">${event.title}</div>
                    <div class="appointments-calendar-event-meta">${event.start_time} - ${event.end_time}</div>
                    <div class="appointments-calendar-event-meta">${event.professional}</div>
                    <div class="appointments-calendar-event-meta">${event.customer}</div>
                    <div class="appointments-calendar-event-indicators">
                        <span class="appointment-state-chip"><span class="appointment-state-dot" style="background:${statusColor};"></span>${event.status}</span>
                        <span class="appointment-state-chip"><span class="appointment-state-dot" style="background:${paymentColor};"></span>${event.payment_status}</span>
                    </div>
                `;
                card.addEventListener('click', () => {
                    openBookingModalWithPrefill({
                        date: event.date,
                        startTime: event.start_time,
                        eventData: event,
                    });
                });

                if (realtimeMode) {
                    if (!previousEvent) {
                        card.classList.add('is-realtime-new');
                    } else if (previousSignature !== currentSignature) {
                        card.classList.add('is-realtime-updated');
                    }
                }

                column.appendChild(card);
            });

            if (realtimeMode && removedDates.has(day.date)) {
                column.classList.add('is-realtime-removed');
                setTimeout(() => {
                    column.classList.remove('is-realtime-removed');
                }, 1300);
            }
        });

        setActiveCalendarDate(activeCalendarDate);
        applyCalendarView();
    }

    function isAppointmentRealtimeNotification(notification) {
        const action = String(notification?.action || '').toLowerCase();
        const type = String(notification?.type || '').toLowerCase();
        const appointmentId = Number(notification?.appointment_id || notification?.meta?.appointment_id || 0);

        return action.startsWith('appointment_') || type.includes('appointment') || appointmentId > 0;
    }

    function buildRealtimeFeedParams() {
        const params = new URLSearchParams();
        const filtersForm = document.querySelector('form[action="{{ route('appointments.index') }}"]');
        const dateValue = String(filtersForm?.querySelector('input[name="date"]')?.value || '').trim();
        const userIdValue = String(filtersForm?.querySelector('select[name="user_id"]')?.value || '').trim();

        params.set('realtime', '1');
        if (dateValue) {
            params.set('date', dateValue);
        }
        if (userIdValue) {
            params.set('user_id', userIdValue);
        }

        return params;
    }

    let realtimeRefreshInProgress = false;
    let realtimeRefreshQueued = false;

    async function refreshCalendarRealtime(force = false) {
        if (realtimeRefreshInProgress && !force) {
            realtimeRefreshQueued = true;
            return;
        }

        realtimeRefreshInProgress = true;
        try {
            const params = buildRealtimeFeedParams();
            const response = await fetch(`${appointmentsRealtimeFeedUrl}?${params.toString()}`, {
                headers: {
                    'Accept': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                },
            });

            const payload = await response.json().catch(() => ({}));
            if (!response.ok || payload?.success === false) {
                return;
            }

            const previousEvents = Array.isArray(appointmentEvents) ? [...appointmentEvents] : [];
            appointmentEvents = Array.isArray(payload?.calendar_events) ? payload.calendar_events : [];
            if (appointmentsWeekCountValue) {
                appointmentsWeekCountValue.textContent = String(Number(payload?.calendar_week_events_count || appointmentEvents.length || 0));
            }
            if (appointmentsWeekRangeTitle && payload?.calendar_week_title) {
                appointmentsWeekRangeTitle.textContent = String(payload.calendar_week_title);
            }
            if (appointmentsWeekRangeNote && payload?.calendar_week_note) {
                appointmentsWeekRangeNote.textContent = String(payload.calendar_week_note);
            }

            renderCalendar({ previousEvents, realtime: true });
        } catch (error) {
            console.error(error);
        } finally {
            realtimeRefreshInProgress = false;
            if (realtimeRefreshQueued) {
                realtimeRefreshQueued = false;
                refreshCalendarRealtime().catch(() => {});
            }
        }
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
            syncPaymentSummary();
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

        syncPaymentSummary();
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

    function roundToHourLabelFromOffset(offsetY) {
        const totalMinutes = Math.max(0, Math.floor(offsetY / calendarHourHeight) * 60) + (calendarStartHour * 60);
        const hours = String(Math.floor(totalMinutes / 60)).padStart(2, '0');
        return `${hours}:00`;
    }

    function pickClosestSlot(slots, targetStartTime) {
        if (!Array.isArray(slots) || slots.length === 0) {
            return '';
        }

        const target = String(targetStartTime || '').trim();
        const exact = slots.find((slot) => String(slot.start || '') === target);
        if (exact) {
            return String(exact.start || '');
        }

        return String(slots[0]?.start || '');
    }

    async function openBookingModalWithPrefill(config = {}) {
        const {
            date = '',
            startTime = '',
            eventData = null,
        } = config;

        if (eventData?.id && appointmentIdInput) {
            appointmentIdInput.value = String(eventData.id);
            setBookingFormEditMode();
        } else {
            resetBookingFormMode();
        }

        if (eventData?.service_id && serviceSelect) {
            serviceSelect.value = String(eventData.service_id);
        }

        if (eventData?.user_id && userSelect) {
            userSelect.value = String(eventData.user_id);
        }

        if (eventData?.customer_id) {
            const customerSelect = bookingForm?.querySelector('[name="customer_id"]');
            if (customerSelect) {
                customerSelect.value = String(eventData.customer_id);
            }
        }

        if (dateInput && date) {
            dateInput.value = String(date);
        }

        if (eventData) {
            const paidAmountInput = bookingForm?.querySelector('[name="paid_amount"]');
            const paymentStatusSelect = bookingForm?.querySelector('[name="payment_status"]');
            const statusSelect = bookingForm?.querySelector('[name="status"]');
            const notesInput = bookingForm?.querySelector('[name="notes"]');

            if (contactNameInput) contactNameInput.value = eventData.contact_name || '';
            if (contactPhoneInput) {
                const splitPhone = splitPhoneWithCode(eventData.contact_phone || '');
                if (contactPhoneCodeInput) {
                    contactPhoneCodeInput.value = splitPhone.code || '+58';
                }
                contactPhoneInput.value = splitPhone.local || '';
            }
            if (paymentMethodSelect) paymentMethodSelect.value = eventData.payment_method_id ? String(eventData.payment_method_id) : '';
            if (paidAmountInput) paidAmountInput.value = Number(eventData.paid_amount || 0) > 0 ? String(eventData.paid_amount) : '';
            if (paymentReferenceInput) paymentReferenceInput.value = eventData.payment_reference || '';
            if (paymentStatusSelect) paymentStatusSelect.value = eventData.payment_status_key || 'pending';
            if (statusSelect) statusSelect.value = eventData.status_key || 'scheduled';
            if (notesInput) notesInput.value = eventData.notes || '';
            setCreateCustomerMode(false);

            syncAdminWhatsappLink(eventData);
        } else {
            syncAdminWhatsappLink();
        }

        syncServiceMetadata();
        syncPaymentReferenceRequirement();
        const slots = await loadSlots();
        const selectedStart = pickClosestSlot(slots, eventData?.start_time || startTime);
        if (slotSelect && selectedStart) {
            slotSelect.value = selectedStart;
        }

        bookingModal?.show();
    }

    async function loadSlots() {
        if (!serviceSelect?.value || !userSelect?.value || !dateInput?.value) {
            slotSelect.innerHTML = '<option value="">Seleccione un servicio, profesional y fecha</option>';
            return [];
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
                return [];
            }

            slotSelect.innerHTML = '<option value="">Seleccione una hora</option>';
            slots.forEach((slot) => {
                const option = document.createElement('option');
                option.value = slot.start;
                option.textContent = slot.label;
                slotSelect.appendChild(option);
            });

            return slots;
        } catch (error) {
            console.error(error);
            slotSelect.innerHTML = '<option value="">No se pudieron cargar los horarios</option>';
            return [];
        }
    }

    document.querySelectorAll('[data-calendar-date]').forEach((button) => {
        button.addEventListener('click', () => {
            if (dateInput) {
                dateInput.value = button.dataset.calendarDate;
                loadSlots();
            }
            setActiveCalendarDate(button.dataset.calendarDate || '');
            applyCalendarView();
        });
    });

    document.querySelectorAll('[data-calendar-column]').forEach((column) => {
        column.addEventListener('click', (event) => {
            if (event.target.closest('.appointments-calendar-event')) {
                return;
            }

            const dateValue = String(column.dataset.calendarColumn || '').trim();
            if (!dateValue) {
                return;
            }

            const rect = column.getBoundingClientRect();
            const clickOffsetY = Math.max(0, Math.min(rect.height - 1, event.clientY - rect.top));
            const roundedTime = roundToHourLabelFromOffset(clickOffsetY);

            openBookingModalWithPrefill({
                date: dateValue,
                startTime: roundedTime,
            });
        });
    });

    calendarViewButtons.forEach((button) => {
        button.addEventListener('click', () => {
            calendarView = button.dataset.calendarView || 'week';
            applyCalendarView();
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
        syncAdminWhatsappLink();
        syncPaymentSummary();
    });
    userSelect?.addEventListener('change', () => {
        loadSlots();
        syncAdminWhatsappLink();
    });
    dateInput?.addEventListener('change', () => {
        loadSlots();
        setActiveCalendarDate(dateInput.value || '');
        if (calendarView === 'day') {
            applyCalendarView();
        }
        syncAdminWhatsappLink();
    });
    paymentMethodSelect?.addEventListener('change', syncPaymentReferenceRequirement);
    paidAmountInput?.addEventListener('input', syncPaymentSummary);
    paymentStatusSelect?.addEventListener('change', syncPaymentSummary);
    slotSelect?.addEventListener('change', () => syncAdminWhatsappLink());
    contactPhoneInput?.addEventListener('input', () => syncAdminWhatsappLink());
    contactNameInput?.addEventListener('input', () => syncAdminWhatsappLink());
    contactPhoneCodeInput?.addEventListener('change', () => syncAdminWhatsappLink());

    customerSelect?.addEventListener('change', () => {
        if (String(customerSelect.value || '').trim() !== '') {
            setCreateCustomerMode(false);
        }
    });

    toggleNewCustomerBtn?.addEventListener('click', () => {
        const currentlyNew = String(createCustomerInput?.value || '0') === '1';
        setCreateCustomerMode(!currentlyNew);
    });

    bookingForm?.addEventListener('submit', () => {
        normalizePhoneForSubmit();
    });
    serviceProductSelect?.addEventListener('change', () => {
        const selectedOption = serviceProductSelect.selectedOptions?.[0];
        if (selectedOption && serviceNameInput && !serviceNameInput.value.trim()) {
            serviceNameInput.value = selectedOption.textContent.trim();
        }
    });

    bookingModalElement?.addEventListener('hidden.bs.modal', () => {
        resetBookingFormMode();
    });

    workflowActionButtons.forEach((button) => {
        button.addEventListener('click', () => {
            const actionKey = button.dataset.appointmentWorkflowAction || '';
            if (!actionKey) {
                return;
            }

            runWorkflowAction(actionKey);
        });
    });

    if (mobileFab && mobileFab.parentElement !== document.body) {
        document.body.appendChild(mobileFab);
    }

    if (mobileActionsPanel && mobileActionsPanel.parentElement !== document.body) {
        document.body.appendChild(mobileActionsPanel);
    }

    if (filtersCollapseElement) {
        const setFiltersVisible = (isVisible) => {
            filtersCollapseElement.classList.toggle('d-none', !isVisible);

            if (filtersToggleButton) {
                filtersToggleButton.setAttribute('aria-expanded', isVisible ? 'true' : 'false');
            }

            if (filtersToggleLabel) {
                filtersToggleLabel.textContent = isVisible ? 'Ocultar filtros' : 'Mostrar filtros';
            }
        };

        setFiltersVisible(!isMobileQuery.matches);

        filtersToggleButton?.addEventListener('click', () => {
            const isHidden = filtersCollapseElement.classList.contains('d-none');
            setFiltersVisible(isHidden);
        });
    }

    syncServiceMetadata();
    syncPaymentReferenceRequirement();
    syncPaymentSummary();
    resetBookingFormMode();
    if (calendarCard && isMobileQuery.matches) {
        calendarView = 'day';
    }
    renderCalendar();
    loadSlots();

    window.addEventListener('shopix:backoffice-notification', (event) => {
        const notification = event?.detail || {};
        if (!isAppointmentRealtimeNotification(notification)) {
            return;
        }

        refreshCalendarRealtime().catch(() => {});
    });
});
</script>
@endpush
