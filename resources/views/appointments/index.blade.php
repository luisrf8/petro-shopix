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
        padding: 0.6rem;
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
        padding: 0.35rem 0.45rem;
    }

    .appointment-payment-summary-label {
        display: block;
        color: #64748b;
        font-size: 0.67rem;
    }

    .appointment-payment-summary-value {
        display: block;
        color: #0f172a;
        font-size: 0.88rem;
        font-weight: 700;
        line-height: 1.15;
    }

    .appointment-payment-summary-subvalue {
        display: block;
        margin-top: 0.08rem;
        color: #64748b;
        font-size: 0.66rem;
        line-height: 1.2;
    }

    .appointment-payment-row {
        background: #fbfdff;
    }

    .appointment-payment-row .form-label {
        font-size: 0.73rem;
        margin-bottom: 0.2rem;
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
        display: -webkit-box;
        -webkit-line-clamp: 2;
        -webkit-box-orient: vertical;
        overflow: hidden;
    }

    .appointments-calendar-event-title-row {
        display: flex;
        align-items: flex-start;
        justify-content: space-between;
        gap: 0.35rem;
    }

    .appointments-calendar-event-services-badge {
        flex-shrink: 0;
        font-size: 0.64rem;
        font-weight: 700;
        line-height: 1;
        border-radius: 999px;
        padding: 0.2rem 0.38rem;
        background: rgba(255, 255, 255, 0.2);
        border: 1px solid rgba(255, 255, 255, 0.28);
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

    .btn.is-loading,
    button.is-loading {
        opacity: 0.9;
        pointer-events: none;
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
    $requestedCalendarView = strtolower((string) request('view', ''));
    $selectedCalendarView = in_array($requestedCalendarView, ['day', 'week', 'month'], true) ? $requestedCalendarView : null;
    $previousDayDate = $selectedDate->copy()->subDay()->toDateString();
    $nextDayDate = $selectedDate->copy()->addDay()->toDateString();
    $previousWeekDate = $calendarWeekStart->copy()->subWeek()->toDateString();
    $nextWeekDate = $calendarWeekStart->copy()->addWeek()->toDateString();
    $previousMonthDate = $selectedDate->copy()->subMonthNoOverflow()->toDateString();
    $nextMonthDate = $selectedDate->copy()->addMonthNoOverflow()->toDateString();
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
                <div class="d-grid gap-2 mt-2">
                    <a href="{{ route('appointments.services.index') }}" class="btn btn-outline-dark btn-sm mb-0">Servicios</a>
                    <a href="{{ route('appointments.customerControl.index') }}" class="btn btn-outline-secondary btn-sm mb-0">Control clientes</a>
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
                            <input type="hidden" name="view" id="appointmentsCalendarViewInput" value="{{ $selectedCalendarView ?? '' }}">
                            <button class="btn btn-dark mb-0" type="submit">Actualizar vista</button>
                        </form>
                    </div>

                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                        <div class="btn-group" role="group" aria-label="Vista calendario">
                            <button type="button" class="btn btn-outline-secondary btn-sm {{ $selectedCalendarView === 'day' ? 'active btn-dark' : '' }}" data-calendar-view="day">Día</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm {{ ($selectedCalendarView === 'week' || !$selectedCalendarView) ? 'active btn-dark' : '' }}" data-calendar-view="week">Semana</button>
                            <button type="button" class="btn btn-outline-secondary btn-sm {{ $selectedCalendarView === 'month' ? 'active btn-dark' : '' }}" data-calendar-view="month">Mes</button>
                        </div>
                    </div>

                    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
                        <div>
                            <h4
                                class="mb-1"
                                id="appointmentsWeekRangeTitle"
                                data-title-day="{{ \Illuminate\Support\Str::ucfirst($selectedDate->translatedFormat('d M Y')) }}"
                                data-title-week="{{ \Illuminate\Support\Str::ucfirst($calendarWeekStart->translatedFormat('d M')) }} - {{ \Illuminate\Support\Str::ucfirst($calendarWeekEnd->translatedFormat('d M Y')) }}"
                                data-title-month="{{ \Illuminate\Support\Str::ucfirst($selectedDate->translatedFormat('F Y')) }}"
                            >{{ \Illuminate\Support\Str::ucfirst($calendarWeekStart->translatedFormat('d M')) }} - {{ \Illuminate\Support\Str::ucfirst($calendarWeekEnd->translatedFormat('d M Y')) }}</h4>
                        </div>
                        <div class="d-flex flex-wrap gap-2" data-calendar-nav-view="day">
                            <a href="{{ route('appointments.index', ['date' => $previousDayDate, 'user_id' => $selectedUserId, 'view' => 'day']) }}" class="btn btn-outline-dark mb-0">Día anterior</a>
                            <a href="{{ route('appointments.index', ['date' => now()->toDateString(), 'user_id' => $selectedUserId, 'view' => 'day']) }}" class="btn btn-outline-secondary mb-0">Hoy</a>
                            <a href="{{ route('appointments.index', ['date' => $nextDayDate, 'user_id' => $selectedUserId, 'view' => 'day']) }}" class="btn btn-outline-dark mb-0">Día siguiente</a>
                        </div>
                        <div class="d-flex flex-wrap gap-2" data-calendar-nav-view="week">
                            <a href="{{ route('appointments.index', ['date' => $previousWeekDate, 'user_id' => $selectedUserId, 'view' => 'week']) }}" class="btn btn-outline-dark mb-0">Semana anterior</a>
                            <a href="{{ route('appointments.index', ['date' => now()->toDateString(), 'user_id' => $selectedUserId, 'view' => 'week']) }}" class="btn btn-outline-secondary mb-0">Hoy</a>
                            <a href="{{ route('appointments.index', ['date' => $nextWeekDate, 'user_id' => $selectedUserId, 'view' => 'week']) }}" class="btn btn-outline-dark mb-0">Semana siguiente</a>
                        </div>
                        <div class="d-flex flex-wrap gap-2" data-calendar-nav-view="month">
                            <a href="{{ route('appointments.index', ['date' => $previousMonthDate, 'user_id' => $selectedUserId, 'view' => 'month']) }}" class="btn btn-outline-dark mb-0">Mes anterior</a>
                            <a href="{{ route('appointments.index', ['date' => now()->toDateString(), 'user_id' => $selectedUserId, 'view' => 'month']) }}" class="btn btn-outline-secondary mb-0">Hoy</a>
                            <a href="{{ route('appointments.index', ['date' => $nextMonthDate, 'user_id' => $selectedUserId, 'view' => 'month']) }}" class="btn btn-outline-dark mb-0">Mes siguiente</a>
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
                <div class="d-grid gap-2 mt-2">
                    <a href="{{ route('appointments.services.index') }}" class="btn btn-outline-dark btn-sm mb-0" data-bs-dismiss="offcanvas">Servicios</a>
                    <a href="{{ route('appointments.customerControl.index') }}" class="btn btn-outline-secondary btn-sm mb-0" data-bs-dismiss="offcanvas">Control clientes</a>
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
                    <div class="col-12">
                        <ul class="nav nav-tabs" id="appointmentBookingTabs" role="tablist">
                            <li class="nav-item" role="presentation">
                                <button class="nav-link active" id="appointment-tab-data" data-bs-toggle="tab" data-bs-target="#appointment-pane-data" type="button" role="tab" aria-controls="appointment-pane-data" aria-selected="true">Datos de la cita</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="appointment-tab-consumptions" data-bs-toggle="tab" data-bs-target="#appointment-pane-consumptions" type="button" role="tab" aria-controls="appointment-pane-consumptions" aria-selected="false">Consumibles y venta</button>
                            </li>
                            <li class="nav-item" role="presentation">
                                <button class="nav-link" id="appointment-tab-payments" data-bs-toggle="tab" data-bs-target="#appointment-pane-payments" type="button" role="tab" aria-controls="appointment-pane-payments" aria-selected="false">Pagos</button>
                            </li>
                        </ul>
                    </div>

                    <div class="col-12 tab-content pt-3" id="appointmentBookingTabContent">
                        <div class="tab-pane fade show active" id="appointment-pane-data" role="tabpanel" aria-labelledby="appointment-tab-data" tabindex="0">
                            <div class="row g-2">
                                <div class="col-12 col-lg-6">
                                    <label class="form-label">Servicios de la cita</label>
                                    <input type="hidden" name="appointment_service_id" id="appointmentPrimaryServiceInput" value="{{ old('appointment_service_id') }}">
                                    <select name="appointment_service_ids[]" id="appointmentServiceSelect" class="d-none" required multiple size="5" aria-hidden="true" tabindex="-1">
                                        @php
                                            $oldServiceIds = collect(old('appointment_service_ids', []))
                                                ->map(fn ($value) => (string) $value)
                                                ->values()
                                                ->all();
                                        @endphp
                                        @foreach($activeServices as $service)
                                            <option value="{{ $service->id }}" {{ in_array((string) $service->id, $oldServiceIds, true) || (string) old('appointment_service_id') === (string) $service->id ? 'selected' : '' }}>{{ $service->display_name }} · {{ $service->duration_minutes }} min</option>
                                        @endforeach
                                    </select>
                                    <div id="appointmentServiceChecklist" class="border border-1 rounded p-2" style="max-height: 210px; overflow: auto;">
                                        <div class="d-flex flex-column gap-2">
                                            @foreach($activeServices as $service)
                                                @php
                                                    $isChecked = in_array((string) $service->id, $oldServiceIds, true) || (string) old('appointment_service_id') === (string) $service->id;
                                                @endphp
                                                <label class="d-flex align-items-start gap-2 border rounded p-2 mb-0" data-service-card="{{ $service->id }}">
                                                    <input
                                                        type="checkbox"
                                                        class="form-check-input mt-1 appointment-service-check"
                                                        data-service-id="{{ $service->id }}"
                                                        {{ $isChecked ? 'checked' : '' }}
                                                    >
                                                    <span>
                                                        <span class="fw-semibold d-block">{{ $service->display_name }}</span>
                                                        <small class="text-muted d-block">{{ $service->duration_minutes }} min{{ !is_null($service->price) ? ' · ' . number_format((float) $service->price, 2) . ' $' : '' }}</small>
                                                    </span>
                                                </label>
                                            @endforeach
                                        </div>
                                    </div>
                                    <small id="appointmentServiceMeta" class="appointment-inline-note d-block mt-1">Marca uno o varios servicios para la misma cita.</small>
                                    <div class="d-none mt-2" id="appointmentServiceEditControlsWrap">
                                        <div class="form-check mb-1">
                                            <input class="form-check-input" type="checkbox" value="1" id="appointmentAllowServiceChangeCheck" name="allow_service_change">
                                            <label class="form-check-label" for="appointmentAllowServiceChangeCheck">Cambiar servicio principal por otro</label>
                                        </div>
                                        <div class="form-check mb-1">
                                            <input class="form-check-input" type="checkbox" value="1" id="appointmentAllowAdditionalServicesCheck" name="allow_additional_services">
                                            <label class="form-check-label" for="appointmentAllowAdditionalServicesCheck">Agregar otro servicio a esta cita (suma tiempo)</label>
                                        </div>
                                        <div class="form-check d-none" id="appointmentRollNextAppointmentsWrap">
                                            <input type="hidden" name="roll_next_appointments" value="0">
                                            <input class="form-check-input" type="checkbox" value="1" id="appointmentRollNextAppointmentsCheck" name="roll_next_appointments">
                                            <label class="form-check-label" for="appointmentRollNextAppointmentsCheck">Si hay solape, rodar citas siguientes y notificar clientes afectados</label>
                                        </div>
                                        <div class="alert alert-warning py-2 px-3 mt-2 mb-0 d-none" id="appointmentRollPreviewBox" role="status" aria-live="polite">
                                            <div class="fw-semibold" id="appointmentRollPreviewTitle"></div>
                                            <small class="d-block mt-1" id="appointmentRollPreviewDetail"></small>
                                        </div>
                                        <div class="form-check mt-2">
                                            <input class="form-check-input" type="checkbox" value="1" id="appointmentKeepCurrentDateCheck">
                                            <label class="form-check-label" for="appointmentKeepCurrentDateCheck">Mantener fecha actual de la cita</label>
                                        </div>
                                        <div class="form-check mt-1">
                                            <input class="form-check-input" type="checkbox" value="1" id="appointmentKeepCurrentTimeCheck">
                                            <label class="form-check-label" for="appointmentKeepCurrentTimeCheck">Mantener hora actual de la cita</label>
                                        </div>
                                        <small class="appointment-inline-note d-block mt-1">Para selección múltiple usa Ctrl/Cmd al hacer clic en los servicios.</small>
                                    </div>
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
                                    <input type="hidden" id="appointmentDateInputLocked" value="">
                                </div>
                                <div class="col-md-6 col-lg-4">
                                    <label class="form-label">Hora disponible</label>
                                    <select name="start_time" id="appointmentSlotSelect" class="form-control border border-1 p-2" required>
                                        <option value="">Seleccione un servicio, profesional y fecha</option>
                                    </select>
                                    <input type="hidden" id="appointmentSlotInputLocked" value="">
                                    @if((bool) ($tenant->appointments_first_come_enabled ?? false))
                                        <small class="appointment-inline-note d-block mt-1">Modo por orden de llegada activo: el sistema toma el primer horario libre.</small>
                                    @endif
                                </div>
                                <div class="col-md-6 col-lg-4">
                                    <label class="form-label">Estado de la cita</label>
                                    <select name="status" id="appointmentStatusSelect" class="form-control border border-1 p-2">
                                        @foreach(\App\Models\Appointment::STATUSES as $statusKey => $statusLabel)
                                            <option value="{{ $statusKey }}" {{ old('status', 'scheduled') === $statusKey ? 'selected' : '' }}>{{ $statusLabel }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-12 d-flex justify-content-end">
                                    <button type="button" class="appointment-toggle-chip mb-0" id="appointmentToggleNewCustomerBtn">Cliente nuevo</button>
                                </div>
                                <div class="col-12 col-lg-8" id="appointmentExistingCustomerWrap">
                                    <label class="form-label mb-1">Cliente existente</label>
                                    <select name="customer_id" id="appointmentCustomerSelect" class="form-control border border-1 p-2">
                                        <option value="">Sin cliente registrado</option>
                                        @foreach($customers as $customer)
                                            <option value="{{ $customer->id }}" {{ (string) old('customer_id') === (string) $customer->id ? 'selected' : '' }}>{{ $customer->name }}{{ $customer->phone_number ? ' · ' . $customer->phone_number : '' }}</option>
                                        @endforeach
                                    </select>
                                    <small class="appointment-inline-note d-block mt-1" id="appointmentCustomerModeHint">Selecciona cliente existente o activa “Cliente nuevo”.</small>
                                </div>
                                <div class="col-12 d-none" id="appointmentNewCustomerFormWrap">
                                    <div class="row g-2">
                                        <div class="col-md-6">
                                            <label class="form-label">Nombre de contacto</label>
                                            <input type="text" name="contact_name" id="appointmentContactNameInput" class="form-control border border-1 p-2" value="{{ old('contact_name') }}" placeholder="Nombre del cliente nuevo">
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
                                            <label class="form-label">Email cliente</label>
                                            <input type="email" name="customer_email" id="appointmentCustomerEmailInput" class="form-control border border-1 p-2" value="{{ old('customer_email') }}" placeholder="cliente@correo.com">
                                        </div>
                                        <div class="col-md-6 d-none" id="appointmentNewCustomerExtraDni">
                                            <label class="form-label">DNI cliente</label>
                                            <input type="text" name="customer_dni" id="appointmentCustomerDniInput" class="form-control border border-1 p-2" value="{{ old('customer_dni') }}">
                                        </div>
                                        <div class="col-12 d-none" id="appointmentNewCustomerRetention">
                                            <div class="form-check form-switch">
                                                <input class="form-check-input" type="checkbox" name="is_retention_agent" id="appointmentRetentionAgentInput" value="1">
                                                <label class="form-check-label" for="appointmentRetentionAgentInput">Agente de retención</label>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="d-flex flex-wrap gap-2 align-items-center">
                                        <a href="#" target="_blank" rel="noopener" class="btn btn-outline-success btn-sm mb-0 d-none" id="appointmentAdminWhatsappButton">WhatsApp cliente</a>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <label class="form-label">Notas</label>
                                    <textarea name="notes" class="form-control border border-1 p-2" rows="2">{{ old('notes') }}</textarea>
                                </div>
                                <div class="col-12 d-none" id="appointmentWorkflowActionsWrap">
                                    <div class="d-flex flex-wrap gap-2">
                                        <button type="button" class="btn btn-outline-dark btn-sm mb-0" data-appointment-workflow-action="call_customer">Llamar cliente</button>
                                        <button type="button" class="btn btn-outline-success btn-sm mb-0" data-appointment-workflow-action="confirm_attendance">Confirmar asistencia</button>
                                        <button type="button" class="btn btn-outline-primary btn-sm mb-0" data-appointment-workflow-action="reschedule">Reprogramar</button>
                                        <button type="button" class="btn btn-outline-danger btn-sm mb-0" data-appointment-workflow-action="cancel">Cancelar</button>
                                        <button type="button" class="btn btn-outline-warning btn-sm mb-0" data-appointment-workflow-action="no_show">No asistió</button>
                                        <button type="button" class="btn btn-success btn-sm mb-0" data-appointment-workflow-action="confirm_payment">Confirmar pago y crear venta</button>
                                    </div>
                                    <small class="appointment-inline-note d-block mt-1">Estas acciones aplican a citas ya existentes y disparan notificaciones del flujo.</small>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="appointment-pane-consumptions" role="tabpanel" aria-labelledby="appointment-tab-consumptions" tabindex="0">
                            <div class="row g-2">
                                <div class="col-12">
                                    <div class="appointment-payment-summary">
                                        <div class="appointments-form-section-title mb-2">Venta asociada a la cita</div>
                                        <div class="appointment-inline-note" id="appointmentAssociatedServiceLabel">Servicio asociado: sin seleccionar.</div>
                                        <div class="appointment-inline-note mt-1" id="appointmentSaleStatusText">No hay venta asociada todavía.</div>
                                        <a href="#" target="_blank" rel="noopener" class="btn btn-outline-dark btn-sm mt-2 d-none" id="appointmentSaleOpenButton">Ver venta asociada</a>
                                    </div>
                                </div>
                                <div class="col-12">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <label class="form-label mb-0">Consumibles y productos de venta</label>
                                        <button type="button" class="btn btn-outline-dark btn-sm mb-0" id="addAppointmentConsumptionBtn">Agregar item</button>
                                    </div>
                                    <div id="appointmentConsumptionsWrapper" class="d-flex flex-column gap-2"></div>
                                    <small class="appointment-inline-note d-block mt-1">Aquí puedes registrar consumibles usados y productos adicionales vendidos durante la atención.</small>
                                </div>
                            </div>
                        </div>

                        <div class="tab-pane fade" id="appointment-pane-payments" role="tabpanel" aria-labelledby="appointment-tab-payments" tabindex="0">
                            <div class="row g-2">
                                <div class="col-12">
                                    <div class="appointment-payment-summary">
                                        <div class="appointment-payment-summary-grid">
                                            <div class="appointment-payment-summary-item">
                                                <span class="appointment-payment-summary-label">Total cita USD</span>
                                                <span class="appointment-payment-summary-value" id="appointmentServicePriceUsd">0.00</span>
                                            </div>
                                            <div class="appointment-payment-summary-item">
                                                <span class="appointment-payment-summary-label">Total cita Bs</span>
                                                <span class="appointment-payment-summary-value" id="appointmentServicePriceBs">0.00</span>
                                            </div>
                                            <div class="appointment-payment-summary-item">
                                                <span class="appointment-payment-summary-label">Monto pagado</span>
                                                <span class="appointment-payment-summary-value" id="appointmentPaidAmountLabel">0.00</span>
                                                <span class="appointment-payment-summary-subvalue" id="appointmentPaidAmountSubLabel">0.00 Bs</span>
                                            </div>
                                            <div class="appointment-payment-summary-item">
                                                <span class="appointment-payment-summary-label">Saldo pendiente</span>
                                                <span class="appointment-payment-summary-value" id="appointmentPendingAmountLabel">0.00</span>
                                                <span class="appointment-payment-summary-subvalue" id="appointmentPendingAmountSubLabel">0.00 Bs</span>
                                            </div>
                                        </div>
                                        <small class="appointment-inline-note d-block mt-2" id="appointmentFractionedBadge">Pago único o sin pago registrado.</small>
                                    </div>
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
                                    <div class="d-flex gap-2 align-items-center">
                                        <input type="number" step="0.01" min="0" name="paid_amount" id="appointmentPaidAmountInput" class="form-control border border-1 p-2" value="{{ old('paid_amount') }}" placeholder="0.00">
                                        <button type="button" class="btn btn-outline-dark btn-sm mb-0" id="appointmentPayRemainingBtn">Pagar restante</button>
                                    </div>
                                    <small class="appointment-inline-note d-block mt-1" id="appointmentMainPaymentConversionHint">Abono en USD: 0.00 $</small>
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
                                <div class="col-12">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <label class="form-label mb-0">Pagos asociados a esta cita</label>
                                        <button type="button" class="btn btn-outline-dark btn-sm mb-0" id="addAppointmentPaymentRowBtn">Agregar pago</button>
                                    </div>
                                    <div id="appointmentPaymentsWrapper" class="d-flex flex-column gap-2"></div>
                                    <small class="appointment-inline-note d-block mt-1">Puedes registrar uno o varios pagos. El total pagado se calcula con todos los pagos de la lista.</small>
                                </div>
                                <div class="col-12 d-none" id="appointmentPaymentProofGroup">
                                    <label class="form-label">Comprobante de pago (imagen)</label>
                                    <input type="hidden" name="require_payment_proof" id="appointmentRequirePaymentProofInput" value="0">
                                    <input type="file" name="payment_proof_image" id="appointmentPaymentProofInput" class="form-control border border-1 p-2" accept="image/jpeg,image/png,image/jpg,image/webp">
                                    <small class="appointment-inline-note d-block mt-1" id="appointmentPaymentProofHint">Sube comprobante cuando el método lo requiera.</small>
                                </div>
                            </div>
                        </div>
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
                                <option value="{{ $variant->id }}">{{ $variant->product->display_name ?? 'Servicio' }} · {{ $variant->size ?? 'Variante' }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted d-block mt-1">Al asociar esta variante a un servicio activo, deja de venderse de forma directa en la landing y se procesa solo por cita.</small>
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
                        <label class="form-label">Profesionales asignados</label>
                        <select name="user_ids[]" class="form-control border border-1 p-2" multiple size="5">
                            @foreach($professionals as $professional)
                                <option value="{{ $professional->id }}">{{ $professional->name }}</option>
                            @endforeach
                        </select>
                        <small class="text-muted d-block mt-1">Puedes seleccionar varios profesionales. Si no seleccionas ninguno, el servicio queda disponible para cualquiera.</small>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label">Color</label>
                        <input type="color" name="color_hex" class="form-control form-control-color w-100" value="#0f172a">
                    </div>
                    <div class="col-12">
                        <button class="btn btn-outline-dark w-100 mb-0" type="submit">Guardar servicio</button>
                    </div>
                </form>

                <hr>
                <h6 class="mb-2">Servicios creados</h6>
                <div class="d-flex flex-column gap-2" style="max-height: 320px; overflow:auto;">
                    @forelse($services as $service)
                        @php
                            $serviceAssignedIds = $service->assignedUsers->pluck('id')->map(fn($id) => (int) $id)->all();
                            if (empty($serviceAssignedIds) && !empty($service->user_id)) {
                                $serviceAssignedIds = [(int) $service->user_id];
                            }
                            $serviceAssignedNames = $service->assignedUsers->pluck('name')->filter()->values();
                            if ($serviceAssignedNames->isEmpty() && $service->assignedUser) {
                                $serviceAssignedNames = collect([$service->assignedUser->name]);
                            }
                        @endphp
                        <details class="appointment-schedule-item">
                            <summary class="d-flex justify-content-between align-items-center" style="cursor:pointer; list-style:none;">
                                <div>
                                    <div class="fw-semibold">{{ $service->display_name }}</div>
                                    <div class="appointment-inline-note">
                                        {{ (int) ($service->duration_minutes ?? 60) }} min · Buffer {{ (int) ($service->buffer_minutes ?? 0) }} min · {{ number_format((float) ($service->price ?? 0), 2) }} $
                                    </div>
                                </div>
                                <span class="badge {{ (bool) ($service->is_active ?? true) ? 'bg-success' : 'bg-secondary' }}">
                                    {{ (bool) ($service->is_active ?? true) ? 'Activo' : 'Inactivo' }}
                                </span>
                            </summary>

                            <div class="mt-2">
                                <form method="POST" action="{{ route('appointments.services.update', ['service' => $service->id]) }}" class="row g-2">
                                    @csrf
                                    @method('PUT')
                                    <div class="col-12">
                                        <label class="form-label">Producto de servicio</label>
                                        <select name="product_variant_id" class="form-control border border-1 p-2" required>
                                            @foreach($serviceVariants as $variant)
                                                <option value="{{ $variant->id }}" {{ (int) $variant->id === (int) ($service->product_variant_id ?? 0) ? 'selected' : '' }}>{{ $variant->product->display_name ?? 'Servicio' }} · {{ $variant->size ?? 'Variante' }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Nombre comercial</label>
                                        <input type="text" name="name" class="form-control border border-1 p-2" value="{{ $service->name }}" required>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Descripción</label>
                                        <textarea name="description" class="form-control border border-1 p-2" rows="2">{{ $service->description }}</textarea>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Duración</label>
                                        <input type="number" name="duration_minutes" class="form-control border border-1 p-2" min="15" step="15" value="{{ (int) ($service->duration_minutes ?? 60) }}" required>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Buffer</label>
                                        <input type="number" name="buffer_minutes" class="form-control border border-1 p-2" min="0" step="5" value="{{ (int) ($service->buffer_minutes ?? 0) }}">
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Precio</label>
                                        <input type="number" name="price" class="form-control border border-1 p-2" min="0" step="0.01" value="{{ number_format((float) ($service->price ?? 0), 2, '.', '') }}">
                                    </div>
                                    <div class="col-md-8">
                                        <label class="form-label">Profesionales asignados</label>
                                        <select name="user_ids[]" class="form-control border border-1 p-2" multiple size="5">
                                            @foreach($professionals as $professional)
                                                <option value="{{ $professional->id }}" {{ in_array((int) $professional->id, $serviceAssignedIds, true) ? 'selected' : '' }}>{{ $professional->name }}</option>
                                            @endforeach
                                        </select>
                                        <small class="text-muted d-block mt-1">
                                            {{ $serviceAssignedNames->isNotEmpty() ? 'Asignados: ' . $serviceAssignedNames->join(', ') : 'Sin asignación específica (cualquiera).' }}
                                        </small>
                                    </div>
                                    <div class="col-md-4">
                                        <label class="form-label">Color</label>
                                        <input type="color" name="color_hex" class="form-control form-control-color w-100" value="{{ $service->color_hex ?: '#0f172a' }}">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label">Estado</label>
                                        <select name="is_active" class="form-control border border-1 p-2">
                                            <option value="1" {{ (bool) ($service->is_active ?? true) ? 'selected' : '' }}>Activo</option>
                                            <option value="0" {{ !(bool) ($service->is_active ?? true) ? 'selected' : '' }}>Inactivo</option>
                                        </select>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <button class="btn btn-dark w-100 mb-0" type="submit">Guardar cambios</button>
                                    </div>
                                </form>

                                <div class="row g-2 mt-1">
                                    <div class="col-12 col-md-6">
                                        <form method="POST" action="{{ route('appointments.services.toggleStatus', ['service' => $service->id]) }}">
                                            @csrf
                                            <button class="btn btn-outline-secondary w-100 mb-0" type="submit">
                                                {{ (bool) ($service->is_active ?? true) ? 'Inactivar servicio' : 'Activar servicio' }}
                                            </button>
                                        </form>
                                    </div>
                                    <div class="col-12 col-md-6">
                                        <form method="POST" action="{{ route('appointments.services.destroy', ['service' => $service->id]) }}" onsubmit="return confirm('¿Eliminar este servicio? Solo se puede si no tiene citas asociadas.');">
                                            @csrf
                                            @method('DELETE')
                                            <button class="btn btn-outline-danger w-100 mb-0" type="submit">Eliminar servicio</button>
                                        </form>
                                    </div>
                                </div>
                            </div>
                        </details>
                    @empty
                        <div class="text-muted">Todavía no hay servicios configurados.</div>
                    @endforelse
                </div>
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
                <div class="alert alert-light border mb-3">
                    <div class="fw-semibold">Paquetes de citas</div>
                    <div class="appointment-inline-note">La creación de paquetes y selección de días de asistencia ahora está en Servicios, pestaña "Paquetes de sesiones".</div>
                    <a href="{{ route('appointments.services.index', ['tab' => 'packages']) }}" class="btn btn-outline-dark btn-sm mt-2 mb-0">Ir a Paquetes de sesiones</a>
                </div>

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
    const appointmentFirstComeEnabled = @json((bool) ($tenant->appointments_first_come_enabled ?? false));
    const appointmentAllowUnpaidReservation = @json((bool) ($tenant->appointments_allow_unpaid_reservation ?? true));

    const serviceSelect = document.getElementById('appointmentServiceSelect');
    const serviceChecklist = document.getElementById('appointmentServiceChecklist');
    const primaryServiceInput = document.getElementById('appointmentPrimaryServiceInput');
    const userSelect = document.getElementById('appointmentUserSelect');
    const dateInput = document.getElementById('appointmentDateInput');
    const slotSelect = document.getElementById('appointmentSlotSelect');
    const bookingForm = document.getElementById('appointmentBookingForm');
    const appointmentIdInput = document.getElementById('appointmentIdInput');
    const createCustomerInput = document.getElementById('appointmentCreateCustomerInput');
    const customerSelect = document.getElementById('appointmentCustomerSelect');
    const existingCustomerWrap = document.getElementById('appointmentExistingCustomerWrap');
    const newCustomerFormWrap = document.getElementById('appointmentNewCustomerFormWrap');
    const toggleNewCustomerBtn = document.getElementById('appointmentToggleNewCustomerBtn');
    const customerModeHint = document.getElementById('appointmentCustomerModeHint');
    const customerEmailInput = document.getElementById('appointmentCustomerEmailInput');
    const customerDniInput = document.getElementById('appointmentCustomerDniInput');
    const newCustomerRetention = document.getElementById('appointmentNewCustomerRetention');
    const newCustomerExtra = document.getElementById('appointmentNewCustomerExtra');
    const newCustomerExtraDni = document.getElementById('appointmentNewCustomerExtraDni');
    const appointmentStatusSelect = document.getElementById('appointmentStatusSelect');
    const submitButton = document.getElementById('appointmentSubmitButton');
    const workflowActionsWrap = document.getElementById('appointmentWorkflowActionsWrap');
    const workflowActionButtons = Array.from(document.querySelectorAll('[data-appointment-workflow-action]'));
    const bookingTabDataButton = document.getElementById('appointment-tab-data');
    const bookingTabConsumptionsButton = document.getElementById('appointment-tab-consumptions');
    const bookingTabPaymentsButton = document.getElementById('appointment-tab-payments');
    const serviceMeta = document.getElementById('appointmentServiceMeta');
    const serviceEditControlsWrap = document.getElementById('appointmentServiceEditControlsWrap');
    const allowServiceChangeCheck = document.getElementById('appointmentAllowServiceChangeCheck');
    const allowAdditionalServicesCheck = document.getElementById('appointmentAllowAdditionalServicesCheck');
    const rollNextAppointmentsWrap = document.getElementById('appointmentRollNextAppointmentsWrap');
    const rollNextAppointmentsCheck = document.getElementById('appointmentRollNextAppointmentsCheck');
    const rollPreviewBox = document.getElementById('appointmentRollPreviewBox');
    const rollPreviewTitle = document.getElementById('appointmentRollPreviewTitle');
    const rollPreviewDetail = document.getElementById('appointmentRollPreviewDetail');
    const keepCurrentDateCheck = document.getElementById('appointmentKeepCurrentDateCheck');
    const keepCurrentTimeCheck = document.getElementById('appointmentKeepCurrentTimeCheck');
    const dateInputLocked = document.getElementById('appointmentDateInputLocked');
    const slotInputLocked = document.getElementById('appointmentSlotInputLocked');
    const paymentMethodSelect = document.getElementById('appointmentPaymentMethodSelect');
    const paymentStatusSelect = document.getElementById('appointmentPaymentStatusSelect');
    const paidAmountInput = document.getElementById('appointmentPaidAmountInput');
    const appointmentPayRemainingBtn = document.getElementById('appointmentPayRemainingBtn');
    const paymentReferenceGroup = document.getElementById('appointmentPaymentReferenceGroup');
    const paymentReferenceInput = document.getElementById('appointmentPaymentReferenceInput');
    const appointmentPaymentsWrapper = document.getElementById('appointmentPaymentsWrapper');
    const addAppointmentPaymentRowBtn = document.getElementById('addAppointmentPaymentRowBtn');
    const mainPaymentConversionHint = document.getElementById('appointmentMainPaymentConversionHint');
    const appointmentPaymentProofGroup = document.getElementById('appointmentPaymentProofGroup');
    const appointmentPaymentProofInput = document.getElementById('appointmentPaymentProofInput');
    const appointmentPaymentProofHint = document.getElementById('appointmentPaymentProofHint');
    const appointmentRequirePaymentProofInput = document.getElementById('appointmentRequirePaymentProofInput');
    const servicePriceUsdLabel = document.getElementById('appointmentServicePriceUsd');
    const servicePriceBsLabel = document.getElementById('appointmentServicePriceBs');
    const servicesSubtotalUsdLabel = document.getElementById('appointmentServicesSubtotalUsd');
    const itemsSubtotalUsdLabel = document.getElementById('appointmentItemsSubtotalUsd');
    const servicesSubtotalBsLabel = document.getElementById('appointmentServicesSubtotalBs');
    const itemsSubtotalBsLabel = document.getElementById('appointmentItemsSubtotalBs');
    const paidAmountLabel = document.getElementById('appointmentPaidAmountLabel');
    const paidAmountSubLabel = document.getElementById('appointmentPaidAmountSubLabel');
    const pendingAmountLabel = document.getElementById('appointmentPendingAmountLabel');
    const pendingAmountSubLabel = document.getElementById('appointmentPendingAmountSubLabel');
    const fractionedBadge = document.getElementById('appointmentFractionedBadge');
    const consumptionsWrapper = document.getElementById('appointmentConsumptionsWrapper');
    const addConsumptionBtn = document.getElementById('addAppointmentConsumptionBtn');
    const serviceProductSelect = document.getElementById('appointmentServiceProductSelect');
    const serviceNameInput = document.getElementById('appointmentServiceNameInput');
    const bookingModalElement = document.getElementById('appointmentBookingModal');
    const adminWhatsappButton = document.getElementById('appointmentAdminWhatsappButton');
    const appointmentAssociatedServiceLabel = document.getElementById('appointmentAssociatedServiceLabel');
    const appointmentSaleStatusText = document.getElementById('appointmentSaleStatusText');
    const appointmentSaleOpenButton = document.getElementById('appointmentSaleOpenButton');
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
    const appointmentsCalendarViewInput = document.getElementById('appointmentsCalendarViewInput');
    const calendarNavGroups = Array.from(document.querySelectorAll('[data-calendar-nav-view]'));
    const filtersCollapseElement = document.getElementById('appointmentsFiltersCollapse');
    const filtersToggleButton = document.getElementById('appointmentsFiltersToggleButton');
    const filtersToggleLabel = document.getElementById('appointmentsFiltersToggleLabel');
    const calendarViewButtons = Array.from(document.querySelectorAll('[data-calendar-view]'));
    const isMobileQuery = window.matchMedia('(max-width: 767.98px)');
    const bookingModal = bookingModalElement && typeof bootstrap !== 'undefined'
        ? bootstrap.Modal.getOrCreateInstance(bookingModalElement)
        : null;
    let calendarView = @json($selectedCalendarView) || (isMobileQuery.matches ? 'day' : 'week');
    let activeCalendarDate = (calendarDays.find((day) => day.is_today)?.date)
        || (dateInput?.value || '')
        || (calendarDays[0]?.date || '');
    let currentEditingEventData = null;
    let originalEditingDate = '';
    let originalEditingStartTime = '';
    let appointmentCommercialLocked = false;

    const buttonLoadingState = new WeakMap();

    function setButtonLoading(button, loadingText = 'Procesando...') {
        if (!button || button.dataset.loadingActive === '1') {
            return;
        }

        buttonLoadingState.set(button, {
            html: button.innerHTML,
            disabled: !!button.disabled,
        });

        const label = String(loadingText || button.dataset.loadingText || button.textContent || 'Procesando...').trim() || 'Procesando...';
        button.dataset.loadingActive = '1';
        button.disabled = true;
        button.classList.add('is-loading');
        button.innerHTML = `<span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true"></span><span>${label}</span>`;
    }

    function clearButtonLoading(button) {
        if (!button) {
            return;
        }

        const previous = buttonLoadingState.get(button);
        if (!previous) {
            button.dataset.loadingActive = '0';
            button.classList.remove('is-loading');
            return;
        }

        button.innerHTML = previous.html;
        button.disabled = previous.disabled;
        button.classList.remove('is-loading');
        button.dataset.loadingActive = '0';
        buttonLoadingState.delete(button);
    }

    function ensureSelectOption(selectElement, optionValue, optionLabel = '') {
        if (!selectElement) {
            return;
        }

        const value = String(optionValue || '').trim();
        if (!value) {
            return;
        }

        const exists = Array.from(selectElement.options || []).some((option) => String(option.value || '') === value);
        if (exists) {
            return;
        }

        const option = document.createElement('option');
        option.value = value;
        option.textContent = optionLabel || value;
        selectElement.appendChild(option);
    }

    function syncEditDateTimeLockState() {
        const isEditing = Number(appointmentIdInput?.value || 0) > 0;
        const lockDate = isEditing && !!keepCurrentDateCheck?.checked;
        const lockTime = isEditing && !!keepCurrentTimeCheck?.checked;

        if (keepCurrentDateCheck) {
            keepCurrentDateCheck.disabled = !isEditing;
        }

        if (keepCurrentTimeCheck) {
            keepCurrentTimeCheck.disabled = !isEditing;
        }

        if (dateInput && dateInputLocked) {
            if (lockDate) {
                const fixedDate = String(originalEditingDate || dateInput.value || '').trim();
                if (fixedDate) {
                    dateInput.value = fixedDate;
                }
                dateInput.name = '';
                dateInput.disabled = true;
                dateInput.classList.add('bg-light');
                dateInputLocked.name = 'scheduled_date';
                dateInputLocked.value = fixedDate;
            } else {
                dateInput.name = 'scheduled_date';
                dateInput.disabled = false;
                dateInput.classList.remove('bg-light');
                dateInputLocked.name = '';
            }
        }

        if (slotSelect && slotInputLocked) {
            if (lockTime) {
                const fixedTime = String(originalEditingStartTime || slotSelect.value || '').trim();
                if (fixedTime) {
                    ensureSelectOption(slotSelect, fixedTime, `${fixedTime} (hora actual de la cita)`);
                    slotSelect.value = fixedTime;
                }
                slotSelect.name = '';
                slotSelect.disabled = true;
                slotSelect.classList.add('bg-light');
                slotInputLocked.name = 'start_time';
                slotInputLocked.value = fixedTime;
            } else {
                slotSelect.name = 'start_time';
                slotSelect.disabled = false;
                slotSelect.classList.remove('bg-light');
                slotInputLocked.name = '';
            }
        }
    }

    function syncServiceSelectFromChecklist() {
        if (!serviceSelect) {
            return;
        }

        const checkedIds = Array.from(document.querySelectorAll('.appointment-service-check:checked'))
            .map((input) => String(input.dataset.serviceId || '').trim())
            .filter((value) => value !== '');

        Array.from(serviceSelect.options || []).forEach((option) => {
            option.selected = checkedIds.includes(String(option.value || ''));
        });
    }

    function isServiceSelectionEditable() {
        const isEditing = Number(appointmentIdInput?.value || 0) > 0;
        if (!isEditing) {
            return true;
        }

        if (appointmentCommercialLocked) {
            return false;
        }

        return !!allowServiceChangeCheck?.checked || !!allowAdditionalServicesCheck?.checked;
    }

    function isCommercialEditionLockedByEvent(eventData = null) {
        const statusKey = String(eventData?.status_key || '').toLowerCase();
        const salesOrderId = Number(eventData?.sales_order_id || 0);
        return statusKey === 'confirmed' || statusKey === 'completed' || salesOrderId > 0;
    }

    function syncCommercialEditState() {
        const isEditing = Number(appointmentIdInput?.value || 0) > 0;
        const lockEdition = isEditing && appointmentCommercialLocked;

        if (allowServiceChangeCheck) {
            if (lockEdition) {
                allowServiceChangeCheck.checked = false;
            }
            allowServiceChangeCheck.disabled = lockEdition;
        }

        if (allowAdditionalServicesCheck) {
            if (lockEdition) {
                allowAdditionalServicesCheck.checked = false;
            }
            allowAdditionalServicesCheck.disabled = lockEdition;
        }

        if (rollNextAppointmentsCheck) {
            if (lockEdition) {
                rollNextAppointmentsCheck.checked = false;
            }
            rollNextAppointmentsCheck.disabled = lockEdition;
        }

        if (addConsumptionBtn) {
            addConsumptionBtn.disabled = lockEdition;
        }

        Array.from(consumptionsWrapper?.querySelectorAll('select,input,button') || []).forEach((input) => {
            input.disabled = lockEdition;
        });
    }

    function syncServiceChecklistFromSelect() {
        if (!serviceChecklist || !serviceSelect) {
            return;
        }

        const selectedIds = Array.from(serviceSelect.selectedOptions || [])
            .map((option) => String(option.value || '').trim())
            .filter((value) => value !== '');

        Array.from(document.querySelectorAll('.appointment-service-check')).forEach((input) => {
            const serviceId = String(input.dataset.serviceId || '').trim();
            input.checked = selectedIds.includes(serviceId);
        });
    }

    function syncAppointmentServiceEditControls() {
        const isEditing = Number(appointmentIdInput?.value || 0) > 0;
        const allowChange = !!allowServiceChangeCheck?.checked;
        const allowAdditional = !!allowAdditionalServicesCheck?.checked;
        const canEditServices = !isEditing || allowChange || allowAdditional;

        serviceEditControlsWrap?.classList.toggle('d-none', !isEditing);

        if (serviceSelect) {
            serviceSelect.disabled = !canEditServices;
        }

        Array.from(document.querySelectorAll('.appointment-service-check')).forEach((input) => {
            input.disabled = !canEditServices;
        });

        if (!canEditServices) {
            // Keep visual checklist aligned with canonical selected options from event data.
            syncServiceChecklistFromSelect();
        }

        const showRollingToggle = isEditing && allowAdditional;
        rollNextAppointmentsWrap?.classList.toggle('d-none', !showRollingToggle);

        if (!showRollingToggle && rollNextAppointmentsCheck) {
            rollNextAppointmentsCheck.checked = false;
        }

        if (!showRollingToggle) {
            hideRollPreview();
        }

        if (!isEditing) {
            if (keepCurrentDateCheck) {
                keepCurrentDateCheck.checked = false;
            }
            if (keepCurrentTimeCheck) {
                keepCurrentTimeCheck.checked = false;
            }
        }

        syncEditDateTimeLockState();
        syncCommercialEditState();
    }

    function formatCalendarTitleDate(dateValue) {
        const normalized = String(dateValue || '').trim();
        if (!/^\d{4}-\d{2}-\d{2}$/.test(normalized)) {
            return normalized;
        }

        const [yearRaw, monthRaw, dayRaw] = normalized.split('-');
        const parsedDate = new Date(Number(yearRaw), Number(monthRaw) - 1, Number(dayRaw));
        if (Number.isNaN(parsedDate.getTime())) {
            return normalized;
        }

        return parsedDate.toLocaleDateString('es-ES', {
            day: '2-digit',
            month: 'short',
            year: 'numeric',
        });
    }

    function formatCalendarTitleMonth(dateValue) {
        const normalized = String(dateValue || '').trim();
        if (!/^\d{4}-\d{2}-\d{2}$/.test(normalized)) {
            return normalized;
        }

        const [yearRaw, monthRaw, dayRaw] = normalized.split('-');
        const parsedDate = new Date(Number(yearRaw), Number(monthRaw) - 1, Number(dayRaw));
        if (Number.isNaN(parsedDate.getTime())) {
            return normalized;
        }

        return parsedDate.toLocaleDateString('es-ES', {
            month: 'long',
            year: 'numeric',
        });
    }

    function syncCalendarHeaderByView() {
        if (!appointmentsWeekRangeTitle) {
            return;
        }

        calendarNavGroups.forEach((group) => {
            const groupView = String(group.dataset.calendarNavView || 'week');
            group.classList.toggle('d-none', groupView !== calendarView);
        });

        if (appointmentsCalendarViewInput) {
            appointmentsCalendarViewInput.value = calendarView;
        }

        if (calendarView === 'day') {
            const dayLabel = formatCalendarTitleDate(activeCalendarDate || dateInput?.value || '');
            appointmentsWeekRangeTitle.textContent = dayLabel || String(appointmentsWeekRangeTitle.dataset.titleDay || '');
            return;
        }

        if (calendarView === 'month') {
            const monthFromDate = String(activeCalendarDate || dateInput?.value || '').slice(0, 7);
            const fallbackDate = monthFromDate && /^\d{4}-\d{2}$/.test(monthFromDate) ? `${monthFromDate}-01` : '';
            const monthLabel = fallbackDate ? formatCalendarTitleMonth(fallbackDate) : '';
            appointmentsWeekRangeTitle.textContent = monthLabel || String(appointmentsWeekRangeTitle.dataset.titleMonth || '');
            return;
        }

        appointmentsWeekRangeTitle.textContent = String(appointmentsWeekRangeTitle.dataset.titleWeek || appointmentsWeekRangeTitle.textContent || '');
    }

    function resetBookingFormMode() {
        if (appointmentIdInput) {
            appointmentIdInput.value = '';
        }
        currentEditingEventData = null;
        appointmentCommercialLocked = false;

        if (allowServiceChangeCheck) {
            allowServiceChangeCheck.checked = false;
        }

        if (allowAdditionalServicesCheck) {
            allowAdditionalServicesCheck.checked = false;
        }

        if (rollNextAppointmentsCheck) {
            rollNextAppointmentsCheck.checked = false;
        }

        if (keepCurrentDateCheck) {
            keepCurrentDateCheck.checked = false;
        }

        if (keepCurrentTimeCheck) {
            keepCurrentTimeCheck.checked = false;
        }

        originalEditingDate = '';
        originalEditingStartTime = '';

        hideRollPreview();

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
        syncAppointmentServiceEditControls();
        syncAssociatedSaleState();
        showBookingTab(bookingTabDataButton);
    }

    function setBookingFormEditMode() {
        if (submitButton) {
            submitButton.textContent = 'Actualizar cita';
            submitButton.classList.remove('btn-success');
            submitButton.classList.add('btn-warning');
        }

        workflowActionsWrap?.classList.remove('d-none');
        if (keepCurrentDateCheck) {
            keepCurrentDateCheck.checked = true;
        }
        if (keepCurrentTimeCheck) {
            keepCurrentTimeCheck.checked = true;
        }
        syncAppointmentServiceEditControls();
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

    function showBookingTab(tabButton) {
        if (!tabButton || typeof bootstrap === 'undefined' || !bootstrap.Tab) {
            return;
        }

        bootstrap.Tab.getOrCreateInstance(tabButton).show();
    }

    function syncAssociatedSaleState(eventData = null) {
        if (appointmentAssociatedServiceLabel) {
            const selectedServiceLabel = String(serviceSelect?.selectedOptions?.[0]?.textContent || '').trim();
            appointmentAssociatedServiceLabel.textContent = selectedServiceLabel
                ? `Servicio asociado: ${selectedServiceLabel}`
                : 'Servicio asociado: sin seleccionar.';
        }

        if (!appointmentSaleStatusText || !appointmentSaleOpenButton) {
            return;
        }

        const salesOrderId = Number(eventData?.sales_order_id || 0);
        const publicOrderUrl = String(eventData?.public_order_url || '').trim();

        if (salesOrderId > 0) {
            appointmentSaleStatusText.textContent = `Venta asociada #${salesOrderId}.`;
            appointmentSaleOpenButton.classList.remove('d-none');
            appointmentSaleOpenButton.setAttribute('href', publicOrderUrl || `/publicOrder/${salesOrderId}`);
            return;
        }

        appointmentSaleStatusText.textContent = 'No hay venta asociada todavía.';
        appointmentSaleOpenButton.classList.add('d-none');
        appointmentSaleOpenButton.setAttribute('href', '#');
    }

    function setCreateCustomerMode(enabled) {
        const isEnabled = !!enabled;

        if (createCustomerInput) {
            createCustomerInput.value = isEnabled ? '1' : '0';
        }

        if (customerSelect) {
            customerSelect.disabled = isEnabled;
            customerSelect.required = !isEnabled;
            if (isEnabled) {
                customerSelect.value = '';
            }
        }

        existingCustomerWrap?.classList.toggle('d-none', isEnabled);
        newCustomerFormWrap?.classList.toggle('d-none', !isEnabled);

        if (contactNameInput) {
            contactNameInput.required = isEnabled;
        }

        if (contactPhoneInput) {
            contactPhoneInput.required = isEnabled;
        }

        if (customerEmailInput) {
            customerEmailInput.required = isEnabled;
        }

        if (customerDniInput) {
            customerDniInput.required = isEnabled;
        }

        newCustomerExtra?.classList.toggle('d-none', !isEnabled);
        newCustomerExtraDni?.classList.toggle('d-none', !isEnabled);
        newCustomerRetention?.classList.toggle('d-none', !isEnabled);

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

    function ensureCustomerOption(customerId, customerName = '', customerPhone = '') {
        if (!customerSelect) {
            return;
        }

        const normalizedId = Number(customerId || 0);
        if (normalizedId <= 0) {
            return;
        }

        const existingOption = Array.from(customerSelect.options || []).find((option) => Number(option.value || 0) === normalizedId);
        if (existingOption) {
            return;
        }

        const nameLabel = String(customerName || 'Cliente').trim() || 'Cliente';
        const phoneLabel = String(customerPhone || '').trim();
        const option = document.createElement('option');
        option.value = String(normalizedId);
        option.textContent = phoneLabel ? `${nameLabel} · ${phoneLabel}` : nameLabel;
        customerSelect.appendChild(option);
    }

    function toMinutes(timeValue) {
        const raw = String(timeValue || '').trim();
        const match = raw.match(/^(\d{1,2}):(\d{2})$/);
        if (!match) {
            return null;
        }

        const hours = Number(match[1]);
        const minutes = Number(match[2]);
        if (Number.isNaN(hours) || Number.isNaN(minutes)) {
            return null;
        }

        return (hours * 60) + minutes;
    }

    function formatMinutesToTime(totalMinutes) {
        const normalized = Math.max(0, Number(totalMinutes || 0));
        const hours = String(Math.floor(normalized / 60)).padStart(2, '0');
        const minutes = String(normalized % 60).padStart(2, '0');
        return `${hours}:${minutes}`;
    }

    function getSelectedServicesTotalMinutes() {
        const selectedServices = getSelectedServices();
        return Math.max(15, selectedServices.reduce((carry, service) => {
            return carry + Number(service?.duration_minutes || 0) + Number(service?.buffer_minutes || 0);
        }, 0));
    }

    function hideRollPreview() {
        rollPreviewBox?.classList.add('d-none');
        if (rollPreviewTitle) {
            rollPreviewTitle.textContent = '';
        }
        if (rollPreviewDetail) {
            rollPreviewDetail.textContent = '';
        }
    }

    function updateRollPreview() {
        const isEditing = Number(appointmentIdInput?.value || 0) > 0;
        if (!isEditing || !rollNextAppointmentsCheck?.checked) {
            hideRollPreview();
            return;
        }

        const selectedDate = String(dateInput?.value || currentEditingEventData?.date || '').trim();
        const selectedUserId = Number(userSelect?.value || currentEditingEventData?.user_id || 0);
        const selectedStartTime = String(slotSelect?.value || currentEditingEventData?.start_time || '').trim();
        const selectedServiceIds = getSelectedServiceIds();

        if (!selectedDate || selectedUserId <= 0 || !selectedStartTime || !selectedServiceIds.length) {
            hideRollPreview();
            return;
        }

        const anchorStart = toMinutes(selectedStartTime);
        if (anchorStart === null) {
            hideRollPreview();
            return;
        }

        let cursor = anchorStart + getSelectedServicesTotalMinutes();
        const targetAppointmentId = Number(appointmentIdInput?.value || 0);

        const sameLineAppointments = (Array.isArray(appointmentEvents) ? appointmentEvents : [])
            .filter((event) => {
                const eventId = Number(event?.id || 0);
                const eventUserId = Number(event?.user_id || 0);
                const eventDate = String(event?.date || '').trim();
                const statusKey = String(event?.status_key || '').toLowerCase();

                if (eventId <= 0 || eventId === targetAppointmentId) {
                    return false;
                }
                if (eventUserId !== selectedUserId || eventDate !== selectedDate) {
                    return false;
                }
                if (statusKey === 'cancelled' || statusKey === 'no_show') {
                    return false;
                }

                return true;
            })
            .map((event) => {
                const startMin = toMinutes(event?.start_time || '');
                const explicitDuration = Number(event?.duration_minutes || 0);
                const duration = explicitDuration > 0
                    ? explicitDuration
                    : (() => {
                        const endMin = toMinutes(event?.end_time || '');
                        if (startMin === null || endMin === null) {
                            return 60;
                        }
                        return Math.max(15, endMin - startMin);
                    })();

                return {
                    title: String(event?.title || 'Cita').trim(),
                    customer: String(event?.customer || 'Cliente').trim(),
                    startMin,
                    duration: Math.max(15, Number(duration || 0)),
                };
            })
            .filter((event) => event.startMin !== null && event.startMin >= anchorStart)
            .sort((a, b) => Number(a.startMin) - Number(b.startMin));

        const moved = [];
        sameLineAppointments.forEach((event) => {
            const originalStart = Number(event.startMin);
            const originalEnd = originalStart + Number(event.duration);

            if (originalStart < cursor) {
                const newStart = cursor;
                const newEnd = newStart + Number(event.duration);
                moved.push({
                    customer: event.customer,
                    from: `${formatMinutesToTime(originalStart)}-${formatMinutesToTime(originalEnd)}`,
                    to: `${formatMinutesToTime(newStart)}-${formatMinutesToTime(newEnd)}`,
                });
                cursor = newEnd;
                return;
            }

            if (originalEnd > cursor) {
                cursor = originalEnd;
            }
        });

        rollPreviewBox?.classList.remove('d-none');
        if (!moved.length) {
            if (rollPreviewTitle) {
                rollPreviewTitle.textContent = 'No se moverán citas con esta configuración.';
            }
            if (rollPreviewDetail) {
                rollPreviewDetail.textContent = 'No hay solapes detectados para el profesional y fecha seleccionados.';
            }
            return;
        }

        const sample = moved
            .slice(0, 3)
            .map((item) => `${item.customer}: ${item.from} -> ${item.to}`)
            .join(' | ');
        const remaining = moved.length - 3;

        if (rollPreviewTitle) {
            rollPreviewTitle.textContent = `Se moverán ${moved.length} cita(s) en cascada.`;
        }
        if (rollPreviewDetail) {
            rollPreviewDetail.textContent = remaining > 0
                ? `${sample} | y ${remaining} cita(s) adicional(es).`
                : sample;
        }
    }

    function syncPaymentSummary() {
        const totals = resolveAppointmentTotalsUsd();
        const servicesTotalUsd = totals.servicesTotalUsd;
        const consumptionsTotalUsd = totals.consumptionsTotalUsd;
        const servicePriceUsd = totals.totalUsd;
        const paidAmount = getTotalAppointmentPaidAmountFromInputs();
        const pendingAmount = Math.max(0, servicePriceUsd - paidAmount);
        const servicesTotalBs = appointmentBsRate > 0 ? (servicesTotalUsd * appointmentBsRate) : 0;
        const itemsTotalBs = appointmentBsRate > 0 ? (consumptionsTotalUsd * appointmentBsRate) : 0;
        const servicePriceBs = appointmentBsRate > 0 ? (servicePriceUsd * appointmentBsRate) : 0;
        const paidAmountBs = appointmentBsRate > 0 ? (paidAmount * appointmentBsRate) : 0;
        const pendingAmountBs = appointmentBsRate > 0 ? (pendingAmount * appointmentBsRate) : 0;
        const isFractioned = paidAmount > 0 && pendingAmount > 0;

        if (servicesSubtotalUsdLabel) {
            servicesSubtotalUsdLabel.textContent = `${servicesTotalUsd.toFixed(2)} $`;
        }

        if (itemsSubtotalUsdLabel) {
            itemsSubtotalUsdLabel.textContent = `${consumptionsTotalUsd.toFixed(2)} $`;
        }

        if (servicesSubtotalBsLabel) {
            servicesSubtotalBsLabel.textContent = appointmentBsRate > 0
                ? `${servicesTotalBs.toFixed(2)} Bs`
                : 'Sin tasa Bs';
        }

        if (itemsSubtotalBsLabel) {
            itemsSubtotalBsLabel.textContent = appointmentBsRate > 0
                ? `${itemsTotalBs.toFixed(2)} Bs`
                : 'Sin tasa Bs';
        }

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

        if (paidAmountSubLabel) {
            paidAmountSubLabel.textContent = appointmentBsRate > 0
                ? `${paidAmountBs.toFixed(2)} Bs`
                : 'Sin tasa Bs';
        }

        if (pendingAmountLabel) {
            pendingAmountLabel.textContent = `${pendingAmount.toFixed(2)} $`;
        }

        if (pendingAmountSubLabel) {
            pendingAmountSubLabel.textContent = appointmentBsRate > 0
                ? `${pendingAmountBs.toFixed(2)} Bs`
                : 'Sin tasa Bs';
        }

        if (mainPaymentConversionHint) {
            const mainAmount = parseMoneyValue(paidAmountInput?.value || 0);
            const mainCurrency = resolvePaymentCurrencyFromSelect(paymentMethodSelect);
            const mainAmountUsd = convertPaymentAmountToUsd(mainAmount, mainCurrency);
            mainPaymentConversionHint.textContent = `Abono en ${mainCurrency}: ${mainAmount.toFixed(2)} · Equivalente USD: ${mainAmountUsd.toFixed(2)} $`;
        }

        appointmentPaymentRows().forEach((row) => {
            syncPaymentRowReferenceRequirement(row);
        });

        syncPaymentProofRequirement();

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

    async function runWorkflowAction(actionKey, triggerButton = null) {
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
            setButtonLoading(triggerButton, 'Procesando...');

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
            clearButtonLoading(triggerButton);
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

    function escapeHtml(value) {
        return String(value || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function buildCompactServiceTitle(titleValue) {
        const rawTitle = String(titleValue || '').trim();
        if (rawTitle === '') {
            return { primary: 'Servicio', extraCount: 0, full: 'Servicio' };
        }

        const parts = rawTitle
            .split(' + ')
            .map((part) => String(part || '').trim())
            .filter((part) => part !== '');

        if (!parts.length) {
            return { primary: rawTitle, extraCount: 0, full: rawTitle };
        }

        return {
            primary: parts[0],
            extraCount: Math.max(0, parts.length - 1),
            full: parts.join(' + '),
        };
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
            const compactTitle = buildCompactServiceTitle(event.title);
            const extraBadge = compactTitle.extraCount > 0
                ? `<span class="appointments-calendar-event-services-badge">+${compactTitle.extraCount}</span>`
                : '';
            return `
                <div class="appointment-upcoming-item">
                    <div class="d-flex align-items-start justify-content-between gap-2">
                        <div class="fw-semibold">${escapeHtml(compactTitle.primary)}</div>
                        ${extraBadge}
                    </div>
                    ${compactTitle.extraCount > 0 ? `<div class="appointment-inline-note">${escapeHtml(compactTitle.full)}</div>` : ''}
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

        syncCalendarHeaderByView();

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
                const compactTitle = buildCompactServiceTitle(event.title);
                const extraBadge = compactTitle.extraCount > 0
                    ? `<span class="appointments-calendar-event-services-badge">+${compactTitle.extraCount}</span>`
                    : '';
                const eventKey = getCalendarEventKey(event);
                const previousEvent = previousMap.get(eventKey);
                const previousSignature = getCalendarEventSignature(previousEvent);
                const currentSignature = getCalendarEventSignature(event);
                card.type = 'button';
                card.className = 'appointments-calendar-event';
                card.style.top = `${(event.minutes_from_start / 60) * calendarHourHeight + 4}px`;
                card.style.height = `${Math.max(54, (event.duration_minutes / 60) * calendarHourHeight - 8)}px`;
                card.style.background = event.color_hex || '#0f172a';
                card.title = `${compactTitle.full}\n${event.start_time} - ${event.end_time}\n${event.customer}`;
                card.innerHTML = `
                    <span class="appointments-calendar-event-ribbon" style="background:${statusColor};"></span>
                    <div class="appointments-calendar-event-title-row">
                        <div class="appointments-calendar-event-title">${escapeHtml(compactTitle.primary)}</div>
                        ${extraBadge}
                    </div>
                    <div class="appointments-calendar-event-meta">${event.start_time} - ${event.end_time}</div>
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
                appointmentsWeekRangeTitle.dataset.titleWeek = String(payload.calendar_week_title);
                if (calendarView === 'week') {
                    appointmentsWeekRangeTitle.textContent = String(payload.calendar_week_title);
                }
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
        return getSelectedServices()[0] || null;
    }

    function getSelectedServiceIds() {
        if (!isServiceSelectionEditable()) {
            return Array.from(serviceSelect?.selectedOptions || [])
                .map((option) => Number(option.value || 0))
                .filter((id) => id > 0);
        }

        const checkedIds = Array.from(document.querySelectorAll('.appointment-service-check:checked'))
            .map((input) => Number(input.dataset.serviceId || 0))
            .filter((id) => id > 0);

        if (checkedIds.length > 0) {
            return checkedIds;
        }

        return Array.from(serviceSelect?.selectedOptions || [])
            .map((option) => Number(option.value || 0))
            .filter((id) => id > 0);
    }

    function getSelectedServices() {
        const selectedIds = getSelectedServiceIds();
        return selectedIds
            .map((selectedId) => servicesPayload.find((service) => Number(service.id) === selectedId) || null)
            .filter(Boolean);
    }

    function syncServiceMetadata() {
        const selectedServices = getSelectedServices();
        const selectedService = selectedServices[0] || null;

        if (primaryServiceInput) {
            primaryServiceInput.value = selectedService ? String(selectedService.id) : '';
        }

        if (!selectedService) {
            if (serviceMeta) {
                serviceMeta.textContent = 'Selecciona el servicio-producto a reservar.';
            }
            syncAssociatedSaleState();
            syncPaymentSummary();
            return;
        }

        if (serviceMeta) {
            const totalMinutes = Math.max(15, selectedServices.reduce((carry, service) => {
                return carry + Number(service.duration_minutes || 0) + Number(service.buffer_minutes || 0);
            }, 0));
            const totalPrice = selectedServices.reduce((carry, service) => carry + Number(service.price || 0), 0);
            const names = selectedServices.map((service) => service.name).join(' + ');
            serviceMeta.textContent = `${names} · ${totalMinutes} min · ${totalPrice.toFixed(2)} $`;
        }

        const assignedIds = Array.isArray(selectedService.assigned_user_ids)
            ? selectedService.assigned_user_ids.map((id) => Number(id || 0)).filter((id) => id > 0)
            : [];

        if (selectedServices.length === 1 && assignedIds.length === 1 && userSelect) {
            userSelect.value = String(assignedIds[0]);
        }

        syncAssociatedSaleState();
        syncPaymentSummary();
    }

    function buildConsumptionOptions(selectedId = '') {
        const options = ['<option value="">Selecciona un item</option>'];
        const consumableGroup = [];
        const saleGroup = [];

        consumableVariants.forEach((variant) => {
            const isSelected = String(selectedId) === String(variant.id) ? 'selected' : '';
            const optionHtml = `<option value="${variant.id}" ${isSelected}>${variant.label} · Stock ${Number(variant.stock || 0).toFixed(2)}</option>`;
            if (variant.is_consumable) {
                consumableGroup.push(optionHtml);
                return;
            }

            saleGroup.push(optionHtml);
        });

        if (consumableGroup.length) {
            options.push(`<optgroup label="Consumibles">${consumableGroup.join('')}</optgroup>`);
        }

        if (saleGroup.length) {
            options.push(`<optgroup label="Productos de venta">${saleGroup.join('')}</optgroup>`);
        }

        return options.join('');
    }

    function updateConsumptionMeta(row) {
        const select = row.querySelector('.appointment-consumption-variant');
        const meta = row.querySelector('.appointment-consumption-meta');
        const selectedVariant = consumableVariants.find((variant) => String(variant.id) === String(select?.value || ''));
        if (!meta) return;
        meta.textContent = selectedVariant
            ? `Costo ref. ${Number(selectedVariant.unit_cost || 0).toFixed(2)} · Stock ${Number(selectedVariant.stock || 0).toFixed(2)}`
            : 'Selecciona el item usado o vendido durante la cita.';
    }

    function addConsumptionRow(selectedId = '', quantity = '') {
        if (!consumptionsWrapper) return;
        const index = consumptionsWrapper.children.length;
        const row = document.createElement('div');
        row.className = 'appointment-consumption-row';
        row.innerHTML = `
            <div class="row g-2 align-items-end">
                <div class="col-12 col-md-7">
                    <label class="form-label">Item</label>
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
        syncCommercialEditState();
    }

    function clearConsumptionRows() {
        if (!consumptionsWrapper) {
            return;
        }

        consumptionsWrapper.innerHTML = '';
    }

    function appointmentPaymentRows() {
        if (!appointmentPaymentsWrapper) {
            return [];
        }

        return Array.from(appointmentPaymentsWrapper.querySelectorAll('.appointment-payment-row'));
    }

    function syncAppointmentPaymentRowsIndices() {
        appointmentPaymentRows().forEach((row, index) => {
            row.dataset.paymentIndex = String(index);
            const methodSelect = row.querySelector('.appointment-payment-method');
            const amountInput = row.querySelector('.appointment-payment-amount');
            const referenceInput = row.querySelector('.appointment-payment-reference');
            const statusSelect = row.querySelector('.appointment-payment-status');

            if (methodSelect) methodSelect.name = `payment_entries[${index}][payment_method_id]`;
            if (amountInput) amountInput.name = `payment_entries[${index}][paid_amount]`;
            if (referenceInput) referenceInput.name = `payment_entries[${index}][payment_reference]`;
            if (statusSelect) statusSelect.name = `payment_entries[${index}][payment_status]`;
        });
    }

    function addAppointmentPaymentRow(initial = {}) {
        if (!appointmentPaymentsWrapper || !paymentMethodSelect) {
            return;
        }

        const row = document.createElement('div');
        row.className = 'appointment-payment-row border rounded p-2';
        row.innerHTML = `
            <div class="row g-2 align-items-end">
                <div class="col-md-4">
                    <label class="form-label">Método</label>
                    <select class="form-control border border-1 p-2 appointment-payment-method">
                        ${paymentMethodSelect.innerHTML}
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Monto</label>
                    <div class="d-flex gap-1 align-items-center">
                        <input type="number" step="0.01" min="0" class="form-control border border-1 p-2 appointment-payment-amount" value="${Number(initial?.paid_amount || 0) > 0 ? Number(initial.paid_amount).toFixed(2) : ''}" placeholder="0.00">
                        <button type="button" class="btn btn-outline-dark btn-sm mb-0 appointment-payment-fill-remaining">Restante</button>
                    </div>
                    <div class="appointment-inline-note mt-1 appointment-payment-conversion">Equivalente USD: 0.00 $</div>
                </div>
                <div class="col-md-3">
                    <label class="form-label">Referencia</label>
                    <input type="text" class="form-control border border-1 p-2 appointment-payment-reference" value="${String(initial?.payment_reference || '').replace(/\"/g, '&quot;')}">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Estado</label>
                    <div class="d-flex gap-1 align-items-center">
                        <select class="form-control border border-1 p-2 appointment-payment-status">
                            ${paymentStatusSelect ? paymentStatusSelect.innerHTML : ''}
                        </select>
                        <button type="button" class="btn btn-outline-danger btn-sm mb-0 appointment-remove-payment-row">Quitar</button>
                    </div>
                </div>
            </div>
        `;

        const methodSelect = row.querySelector('.appointment-payment-method');
        const statusSelect = row.querySelector('.appointment-payment-status');

        if (methodSelect && initial?.payment_method_id) {
            methodSelect.value = String(initial.payment_method_id);
        }
        if (statusSelect && initial?.payment_status) {
            statusSelect.value = String(initial.payment_status);
        }

        appointmentPaymentsWrapper.appendChild(row);
        syncPaymentRowReferenceRequirement(row);
        syncAppointmentPaymentRowsIndices();
        syncCommercialEditState();
    }

    function clearAppointmentPaymentRows() {
        if (!appointmentPaymentsWrapper) {
            return;
        }

        appointmentPaymentsWrapper.innerHTML = '';
    }

    function parseMoneyValue(value) {
        const normalized = String(value ?? '').trim().replace(',', '.');
        const amount = Number(normalized);
        return Number.isFinite(amount) ? amount : 0;
    }

    function resolvePaymentCurrencyFromSelect(selectElement) {
        const selectedOption = selectElement?.selectedOptions?.[0] || null;
        const code = String(selectedOption?.dataset?.currency || 'USD').trim().toUpperCase();
        return code !== '' ? code : 'USD';
    }

    function convertPaymentAmountToUsd(amount, currencyCode) {
        const value = Math.max(0, Number(amount || 0));
        const currency = String(currencyCode || 'USD').trim().toUpperCase();
        if (currency === 'BS') {
            return appointmentBsRate > 0 ? value / appointmentBsRate : 0;
        }
        return value;
    }

    function convertUsdToCurrency(amountUsd, currencyCode) {
        const value = Math.max(0, Number(amountUsd || 0));
        const currency = String(currencyCode || 'USD').trim().toUpperCase();
        if (currency === 'BS') {
            return appointmentBsRate > 0 ? value * appointmentBsRate : 0;
        }
        return value;
    }

    function resolveAppointmentTotalsUsd() {
        const selectedServices = getSelectedServices();
        const servicesTotalUsd = selectedServices.reduce((carry, service) => carry + Number(service?.price || 0), 0);
        const consumptionsTotalUsd = Array.from(consumptionsWrapper?.querySelectorAll('.appointment-consumption-row') || []).reduce((carry, row) => {
            const select = row.querySelector('.appointment-consumption-variant');
            const quantityInput = row.querySelector('input[name*="[quantity]"]');
            const variantId = Number(select?.value || 0);
            const quantity = parseMoneyValue(quantityInput?.value || 0);
            if (variantId <= 0 || quantity <= 0) {
                return carry;
            }

            const variant = consumableVariants.find((item) => Number(item.id) === variantId);
            const unitCost = Number(variant?.unit_cost || 0);

            return carry + (unitCost * quantity);
        }, 0);

        return {
            servicesTotalUsd,
            consumptionsTotalUsd,
            totalUsd: servicesTotalUsd + consumptionsTotalUsd,
        };
    }

    function getMainPaymentAmountUsd() {
        const amount = parseMoneyValue(paidAmountInput?.value || 0);
        const currency = resolvePaymentCurrencyFromSelect(paymentMethodSelect);
        return convertPaymentAmountToUsd(amount, currency);
    }

    function getPaymentRowAmountUsd(row) {
        const methodSelect = row?.querySelector('.appointment-payment-method');
        const amountInput = row?.querySelector('.appointment-payment-amount');
        const amount = parseMoneyValue(amountInput?.value || 0);
        const currency = resolvePaymentCurrencyFromSelect(methodSelect);
        return convertPaymentAmountToUsd(amount, currency);
    }

    function getTotalAppointmentPaidAmountFromInputs() {
        const baseAmountUsd = getMainPaymentAmountUsd();
        const rowAmountsUsd = appointmentPaymentRows().reduce((sum, row) => sum + getPaymentRowAmountUsd(row), 0);
        return Math.max(0, baseAmountUsd + rowAmountsUsd);
    }

    function hasReferenceBasedPaymentWithAmount() {
        const baseOption = paymentMethodSelect?.selectedOptions?.[0] || null;
        const baseRequiresReference = baseOption?.dataset?.hasReference === '1';
        const baseAmount = parseMoneyValue(paidAmountInput?.value || 0);
        if (baseRequiresReference && baseAmount > 0) {
            return true;
        }

        return appointmentPaymentRows().some((row) => {
            const methodSelect = row.querySelector('.appointment-payment-method');
            const option = methodSelect?.selectedOptions?.[0] || null;
            const requiresReference = option?.dataset?.hasReference === '1';
            const amount = parseMoneyValue(row.querySelector('.appointment-payment-amount')?.value || 0);
            return requiresReference && amount > 0;
        });
    }

    function syncPaymentProofRequirement() {
        const requiresProof = hasReferenceBasedPaymentWithAmount();

        if (appointmentRequirePaymentProofInput) {
            appointmentRequirePaymentProofInput.value = requiresProof ? '1' : '0';
        }

        if (appointmentPaymentProofInput) {
            appointmentPaymentProofInput.required = requiresProof;
        }

        if (appointmentPaymentProofGroup) {
            appointmentPaymentProofGroup.classList.toggle('d-none', !requiresProof);
        }

        if (appointmentPaymentProofHint) {
            appointmentPaymentProofHint.textContent = requiresProof
                ? 'Este pago requiere comprobante. Sube una imagen (jpg, png o webp).'
                : 'Sube comprobante solo cuando el método de pago lo requiera.';
        }
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

    function syncPaymentRowReferenceRequirement(row) {
        if (!row) {
            return;
        }

        const methodSelect = row.querySelector('.appointment-payment-method');
        const referenceInput = row.querySelector('.appointment-payment-reference');
        const selectedOption = methodSelect?.selectedOptions?.[0] || null;
        const requiresReference = selectedOption?.dataset?.hasReference === '1';

        if (referenceInput) {
            referenceInput.required = !!requiresReference;
        }

        const amountInput = row.querySelector('.appointment-payment-amount');
        const conversionHint = row.querySelector('.appointment-payment-conversion');
        const rowCurrency = resolvePaymentCurrencyFromSelect(methodSelect);
        const rowAmount = parseMoneyValue(amountInput?.value || 0);
        const rowAmountUsd = convertPaymentAmountToUsd(rowAmount, rowCurrency);
        if (conversionHint) {
            conversionHint.textContent = `Equivalente USD: ${rowAmountUsd.toFixed(2)} $`;
        }
    }

    function fillMainPaymentWithRemaining() {
        if (!paidAmountInput) {
            return;
        }

        const totals = resolveAppointmentTotalsUsd();
        const extraRowsPaidUsd = appointmentPaymentRows().reduce((sum, row) => sum + getPaymentRowAmountUsd(row), 0);
        const pendingUsd = Math.max(0, totals.totalUsd - extraRowsPaidUsd);
        const mainCurrency = resolvePaymentCurrencyFromSelect(paymentMethodSelect);
        const pendingInCurrency = convertUsdToCurrency(pendingUsd, mainCurrency);
        paidAmountInput.value = pendingInCurrency > 0 ? pendingInCurrency.toFixed(2) : '';
        syncPaymentSummary();
    }

    function fillPaymentRowWithRemaining(row) {
        if (!row) {
            return;
        }

        const amountInput = row.querySelector('.appointment-payment-amount');
        if (!amountInput) {
            return;
        }

        const totals = resolveAppointmentTotalsUsd();
        const rowCurrency = resolvePaymentCurrencyFromSelect(row.querySelector('.appointment-payment-method'));

        const alreadyPaidUsd = getMainPaymentAmountUsd() + appointmentPaymentRows().reduce((sum, currentRow) => {
            if (currentRow === row) {
                return sum;
            }
            return sum + getPaymentRowAmountUsd(currentRow);
        }, 0);

        const pendingUsd = Math.max(0, totals.totalUsd - alreadyPaidUsd);
        const pendingInCurrency = convertUsdToCurrency(pendingUsd, rowCurrency);
        amountInput.value = pendingInCurrency > 0 ? pendingInCurrency.toFixed(2) : '';
        syncPaymentSummary();
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
            currentEditingEventData = eventData;
            originalEditingDate = String(eventData?.date || date || '').trim();
            originalEditingStartTime = String(eventData?.start_time || startTime || '').trim();
            appointmentCommercialLocked = isCommercialEditionLockedByEvent(eventData);
            setBookingFormEditMode();
        } else {
            resetBookingFormMode();
            appointmentCommercialLocked = false;
        }

        clearConsumptionRows();
        clearAppointmentPaymentRows();
        if (appointmentPaymentProofInput) {
            appointmentPaymentProofInput.value = '';
        }

        if (serviceSelect) {
            const preselectedServiceIds = Array.isArray(eventData?.service_ids) && eventData?.service_ids.length
                ? eventData.service_ids.map((value) => String(value))
                : (eventData?.service_id ? [String(eventData.service_id)] : []);

            Array.from(serviceSelect.options).forEach((option) => {
                option.selected = preselectedServiceIds.includes(String(option.value || ''));
            });
            syncServiceChecklistFromSelect();
        }

        if (eventData?.user_id && userSelect) {
            userSelect.value = String(eventData.user_id);
        }

        const eventCustomerId = Number(eventData?.customer_id || 0);
        if (eventCustomerId > 0) {
            ensureCustomerOption(eventCustomerId, eventData?.customer || eventData?.contact_name || 'Cliente', eventData?.contact_phone || '');
            if (customerSelect) {
                customerSelect.value = String(eventCustomerId);
            }
        }

        if (dateInput && date) {
            dateInput.value = String(date);
        }

        if (eventData) {
            const paidAmountInput = bookingForm?.querySelector('[name="paid_amount"]');
            const paymentStatusSelect = bookingForm?.querySelector('[name="payment_status"]');
            const notesInput = bookingForm?.querySelector('[name="notes"]');

            if (contactNameInput) contactNameInput.value = eventData.contact_name || '';
            if (contactPhoneInput) {
                const splitPhone = splitPhoneWithCode(eventData.contact_phone || '');
                if (contactPhoneCodeInput) {
                    contactPhoneCodeInput.value = splitPhone.code || '+58';
                }
                contactPhoneInput.value = splitPhone.local || '';
            }
            const existingPaymentEntries = Array.isArray(eventData.payment_entries) ? eventData.payment_entries : [];
            const firstPaymentEntry = existingPaymentEntries[0] || null;

            if (paymentMethodSelect) {
                paymentMethodSelect.value = firstPaymentEntry?.payment_method_id
                    ? String(firstPaymentEntry.payment_method_id)
                    : (eventData.payment_method_id ? String(eventData.payment_method_id) : '');
            }
            if (paidAmountInput) {
                const firstAmount = Number(firstPaymentEntry?.paid_amount || 0);
                paidAmountInput.value = firstAmount > 0
                    ? firstAmount.toFixed(2)
                    : (Number(eventData.paid_amount || 0) > 0 ? String(eventData.paid_amount) : '');
            }
            if (paymentReferenceInput) {
                paymentReferenceInput.value = String(firstPaymentEntry?.payment_reference || eventData.payment_reference || '');
            }
            if (paymentStatusSelect) {
                paymentStatusSelect.value = String(firstPaymentEntry?.payment_status || eventData.payment_status_key || 'pending');
            }

            existingPaymentEntries.slice(1).forEach((entry) => addAppointmentPaymentRow(entry));
            if (appointmentStatusSelect) appointmentStatusSelect.value = eventData.status_key || 'scheduled';
            if (notesInput) notesInput.value = eventData.notes || '';

            const existingConsumptions = Array.isArray(eventData.consumptions) ? eventData.consumptions : [];
            existingConsumptions.forEach((item) => {
                const variantId = Number(item?.variant_id || 0);
                const quantity = Number(item?.quantity || 0);
                if (variantId > 0 && quantity > 0) {
                    addConsumptionRow(String(variantId), String(quantity));
                }
            });

            if (eventCustomerId > 0) {
                setCreateCustomerMode(false);
                if (customerEmailInput) {
                    customerEmailInput.value = String(eventData.customer_email || '');
                }
                if (customerDniInput) {
                    customerDniInput.value = String(eventData.customer_dni || '');
                }
            } else {
                const landingSource = String(eventData.source || '').toLowerCase() === 'landing';
                setCreateCustomerMode(landingSource);
                if (landingSource) {
                    if (contactNameInput && !String(contactNameInput.value || '').trim()) {
                        contactNameInput.value = String(eventData.customer || '').trim();
                    }
                    if (customerEmailInput) {
                        customerEmailInput.value = String(eventData.customer_email || '');
                    }
                    if (customerDniInput) {
                        customerDniInput.value = String(eventData.customer_dni || '');
                    }
                }
            }

            syncAdminWhatsappLink(eventData);
            syncAssociatedSaleState(eventData);
        } else {
            if (paymentMethodSelect) paymentMethodSelect.value = '';
            if (paidAmountInput) paidAmountInput.value = '';
            if (paymentReferenceInput) paymentReferenceInput.value = '';
            if (paymentStatusSelect) paymentStatusSelect.value = 'pending';
            syncAdminWhatsappLink();
            syncAssociatedSaleState();
        }

        syncServiceMetadata();
        syncPaymentReferenceRequirement();
        syncPaymentSummary();
        const slots = await loadSlots();
        const eventStartTime = String(eventData?.start_time || startTime || '').trim();
        const selectedStart = pickClosestSlot(slots, eventStartTime);
        if (slotSelect) {
            if (eventData?.id && eventStartTime) {
                ensureSelectOption(slotSelect, eventStartTime, `${eventStartTime} (hora actual de la cita)`);
                slotSelect.value = eventStartTime;
            } else if (selectedStart) {
                slotSelect.value = selectedStart;
            }
        }

        syncEditDateTimeLockState();

        updateRollPreview();

        bookingModal?.show();
    }

    async function loadSlots() {
        const selectedServiceIds = getSelectedServiceIds();
        if (!selectedServiceIds.length || !userSelect?.value || !dateInput?.value) {
            slotSelect.innerHTML = '<option value="">Seleccione un servicio, profesional y fecha</option>';
            slotSelect.disabled = false;
            return [];
        }

        slotSelect.innerHTML = '<option value="">Cargando horarios...</option>';
        slotSelect.disabled = false;

        const params = new URLSearchParams({
            user_id: userSelect.value,
            date: dateInput.value,
        });
        const editingAppointmentId = Number(appointmentIdInput?.value || 0);
        if (editingAppointmentId > 0) {
            params.set('appointment_id', String(editingAppointmentId));
        }
        params.set('service_id', String(selectedServiceIds[0] || ''));
        selectedServiceIds.forEach((serviceId) => params.append('service_ids[]', String(serviceId)));

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

            const lockTime = Number(appointmentIdInput?.value || 0) > 0 && !!keepCurrentTimeCheck?.checked;
            if (lockTime) {
                const fixedTime = String(originalEditingStartTime || slotSelect.value || '').trim();
                if (fixedTime) {
                    ensureSelectOption(slotSelect, fixedTime, `${fixedTime} (hora actual de la cita)`);
                    slotSelect.value = fixedTime;
                }
            }

            if (appointmentFirstComeEnabled && slots[0]?.start && !lockTime) {
                slotSelect.value = String(slots[0].start);
            }

            slotSelect.disabled = appointmentFirstComeEnabled && !lockTime;

            syncEditDateTimeLockState();

            return slots;
        } catch (error) {
            console.error(error);
            slotSelect.innerHTML = '<option value="">No se pudieron cargar los horarios</option>';
            slotSelect.disabled = false;
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
            if (appointmentsCalendarViewInput) {
                appointmentsCalendarViewInput.value = calendarView;
            }
            applyCalendarView();
        });
    });

    consumptionsWrapper?.addEventListener('click', (event) => {
        const removeBtn = event.target.closest('.appointment-remove-consumption');
        if (!removeBtn) return;
        removeBtn.closest('.appointment-consumption-row')?.remove();
        syncPaymentSummary();
    });

    consumptionsWrapper?.addEventListener('change', (event) => {
        const row = event.target.closest('.appointment-consumption-row');
        if (row) {
            updateConsumptionMeta(row);
        }
        syncPaymentSummary();
    });

    consumptionsWrapper?.addEventListener('input', (event) => {
        if (event.target.matches('input[name*="[quantity]"]')) {
            syncPaymentSummary();
        }
    });

    addConsumptionBtn?.addEventListener('click', () => {
        addConsumptionRow();
        syncPaymentSummary();
    });

    serviceChecklist?.addEventListener('change', (event) => {
        if (!event.target.classList.contains('appointment-service-check')) {
            return;
        }

        syncServiceSelectFromChecklist();
        syncServiceMetadata();
        loadSlots();
        syncAdminWhatsappLink();
        syncPaymentSummary();
        updateRollPreview();
    });

    serviceSelect?.addEventListener('change', () => {
        syncServiceChecklistFromSelect();
        syncServiceMetadata();
        loadSlots();
        syncAdminWhatsappLink();
        syncPaymentSummary();
        updateRollPreview();
    });
    userSelect?.addEventListener('change', () => {
        loadSlots();
        syncAdminWhatsappLink();
        updateRollPreview();
    });
    dateInput?.addEventListener('change', () => {
        loadSlots();
        setActiveCalendarDate(dateInput.value || '');
        if (calendarView === 'day') {
            applyCalendarView();
        }
        syncAdminWhatsappLink();
        updateRollPreview();
    });
    paymentMethodSelect?.addEventListener('change', () => {
        syncPaymentReferenceRequirement();
        syncPaymentSummary();
    });
    paidAmountInput?.addEventListener('input', syncPaymentSummary);
    paymentStatusSelect?.addEventListener('change', syncPaymentSummary);
    appointmentPayRemainingBtn?.addEventListener('click', fillMainPaymentWithRemaining);
    addAppointmentPaymentRowBtn?.addEventListener('click', () => {
        addAppointmentPaymentRow();
        syncPaymentSummary();
    });
    appointmentPaymentsWrapper?.addEventListener('click', (event) => {
        const removeBtn = event.target.closest('.appointment-remove-payment-row');
        if (removeBtn) {
            removeBtn.closest('.appointment-payment-row')?.remove();
            syncAppointmentPaymentRowsIndices();
            syncPaymentSummary();
            return;
        }

        const fillBtn = event.target.closest('.appointment-payment-fill-remaining');
        if (fillBtn) {
            const row = fillBtn.closest('.appointment-payment-row');
            fillPaymentRowWithRemaining(row);
        }
    });
    appointmentPaymentsWrapper?.addEventListener('change', (event) => {
        const row = event.target.closest('.appointment-payment-row');
        if (!row) {
            return;
        }

        if (event.target.classList.contains('appointment-payment-method')) {
            syncPaymentRowReferenceRequirement(row);
        }

        syncPaymentSummary();
    });
    appointmentPaymentsWrapper?.addEventListener('input', (event) => {
        if (event.target.classList.contains('appointment-payment-amount')) {
            syncPaymentSummary();
        }
    });
    slotSelect?.addEventListener('change', () => {
        syncAdminWhatsappLink();
        updateRollPreview();
    });
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

    allowServiceChangeCheck?.addEventListener('change', () => {
        syncAppointmentServiceEditControls();
        updateRollPreview();
    });

    allowAdditionalServicesCheck?.addEventListener('change', () => {
        syncAppointmentServiceEditControls();
        if (allowAdditionalServicesCheck.checked) {
            alert('Ahora puedes seleccionar uno o más servicios adicionales. Si la duración solapa otras citas, activa el check para rodar citas siguientes y notificar clientes afectados.');
        }
        updateRollPreview();
    });

    keepCurrentDateCheck?.addEventListener('change', () => {
        syncEditDateTimeLockState();
        updateRollPreview();
    });

    keepCurrentTimeCheck?.addEventListener('change', () => {
        syncEditDateTimeLockState();
        updateRollPreview();
    });

    rollNextAppointmentsCheck?.addEventListener('change', () => {
        updateRollPreview();
    });

    bookingForm?.addEventListener('submit', (event) => {
        if (isServiceSelectionEditable()) {
            syncServiceSelectFromChecklist();
        }

        const selectedServiceIds = getSelectedServiceIds();
        const isEditing = Number(appointmentIdInput?.value || 0) > 0;
        const canTemporarilySaveWithoutServices = isEditing
            && !appointmentCommercialLocked
            && !!allowServiceChangeCheck?.checked;

        if (!selectedServiceIds.length && !canTemporarilySaveWithoutServices) {
            alert('Debes seleccionar al menos un servicio para agendar la cita.');
            showBookingTab(bookingTabDataButton);
            event.preventDefault();
            return;
        }

        if (primaryServiceInput) {
            primaryServiceInput.value = String(selectedServiceIds[0]);
        }

        if (!appointmentAllowUnpaidReservation && !isEditing) {
            const totals = resolveAppointmentTotalsUsd();
            const paidAmountUsd = getTotalAppointmentPaidAmountFromInputs();
            const pendingAmountUsd = Math.max(0, totals.totalUsd - paidAmountUsd);

            if (pendingAmountUsd > 0.009) {
                alert(`Para crear una cita nueva debes completar el pago total. Total: ${totals.totalUsd.toFixed(2)} USD · Pagado: ${paidAmountUsd.toFixed(2)} USD · Pendiente: ${pendingAmountUsd.toFixed(2)} USD.`);
                showBookingTab(bookingTabPaymentsButton);
                event.preventDefault();
                return;
            }
        }

        const createNewCustomer = String(createCustomerInput?.value || '0') === '1';
        if (createNewCustomer) {
            const contactName = String(contactNameInput?.value || '').trim();
            const contactPhone = String(contactPhoneInput?.value || '').trim();
            const contactEmail = String(customerEmailInput?.value || '').trim();
            const contactDni = String(customerDniInput?.value || '').trim();
            if (!contactName || !contactPhone || !contactEmail || !contactDni) {
                alert('Si seleccionas "Cliente nuevo", debes completar nombre, correo, teléfono y DNI.');
                showBookingTab(bookingTabDataButton);
                event.preventDefault();
                return;
            }
        } else {
            const selectedCustomerId = Number(customerSelect?.value || 0);
            if (selectedCustomerId <= 0) {
                alert('Si no es cliente nuevo, debes seleccionar un cliente existente.');
                showBookingTab(bookingTabDataButton);
                event.preventDefault();
                return;
            }
        }

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

            runWorkflowAction(actionKey, button);
        });
    });

    // Global form submit loading: improves UX and blocks double submissions.
    document.querySelectorAll('form').forEach((form) => {
        form.addEventListener('submit', (event) => {
            if (event.defaultPrevented) {
                return;
            }

            if (form.dataset.submitting === '1') {
                event.preventDefault();
                return;
            }

            form.dataset.submitting = '1';

            const submitter = event.submitter && form.contains(event.submitter)
                ? event.submitter
                : form.querySelector('button[type="submit"]');

            const submitButtons = Array.from(form.querySelectorAll('button[type="submit"]'));
            submitButtons.forEach((button) => {
                button.disabled = true;
            });

            if (submitter && submitter.tagName === 'BUTTON') {
                setButtonLoading(submitter, submitter.dataset.loadingText || 'Procesando...');
            }

            Array.from(form.querySelectorAll('button[type="button"],button:not([type])')).forEach((button) => {
                button.disabled = true;
            });
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
    syncServiceChecklistFromSelect();
    syncPaymentReferenceRequirement();
    syncPaymentSummary();
    resetBookingFormMode();
    syncAppointmentServiceEditControls();
    if (calendarCard && isMobileQuery.matches && !@json($selectedCalendarView)) {
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
