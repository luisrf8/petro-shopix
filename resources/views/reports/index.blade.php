@extends('layouts.app')

@section('title', 'Reportes')

@section('content')
<div class="container-fluid py-2">
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                    <div class="bg-gradient-dark shadow-dark border-radius-lg pt-4 pb-3">
                        <h6 class="text-white text-capitalize ps-3">Centro de Reportes</h6>
                    </div>
                </div>
                <div class="card-body">
                    <div class="alert alert-light border text-sm" role="alert">
                        Selecciona un reporte y formato. Los PDF se generan directamente con filtros por defecto y los CSV se visualizan online con filtros desde modal.
                    </div>

                    <div class="card border mb-3">
                        <div class="card-body">
                            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                                <div>
                                    <h6 class="mb-1">Vista previa de citas (multi-servicio)</h6>
                                    <p class="text-sm text-muted mb-0">Este resumen respeta los filtros actuales y considera todos los servicios asociados a cada cita.</p>
                                </div>
                                <div class="text-sm text-muted">
                                    Servicio filtrado:
                                    <strong>{{ $selectedAppointmentServiceLabel ?: 'Todos los servicios' }}</strong>
                                </div>
                            </div>

                            <div class="row g-2 mt-2">
                                <div class="col-6 col-md-3">
                                    <div class="border rounded p-2 h-100">
                                        <div class="text-xs text-muted">Citas</div>
                                        <div class="fw-bold">{{ number_format((int) ($appointmentsPreviewSummary['appointments'] ?? 0)) }}</div>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="border rounded p-2 h-100">
                                        <div class="text-xs text-muted">Pago pendiente/parcial</div>
                                        <div class="fw-bold">{{ number_format((int) ($appointmentsPreviewSummary['pending_payment'] ?? 0)) }}</div>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="border rounded p-2 h-100">
                                        <div class="text-xs text-muted">Precio total</div>
                                        <div class="fw-bold">{{ number_format((float) ($appointmentsPreviewSummary['total_service_price'] ?? 0), 2) }} {{ $appointmentsPreviewSummary['currency_code'] ?? 'USD' }}</div>
                                    </div>
                                </div>
                                <div class="col-6 col-md-3">
                                    <div class="border rounded p-2 h-100">
                                        <div class="text-xs text-muted">Pendiente</div>
                                        <div class="fw-bold">{{ number_format((float) ($appointmentsPreviewSummary['total_pending'] ?? 0), 2) }} {{ $appointmentsPreviewSummary['currency_code'] ?? 'USD' }}</div>
                                    </div>
                                </div>
                            </div>

                            <div class="table-responsive mt-3">
                                <table class="table table-sm align-items-center mb-0">
                                    <thead>
                                        <tr>
                                            <th>Cita</th>
                                            <th>Servicio(s)</th>
                                            <th>Profesional</th>
                                            <th>Fecha</th>
                                            <th>Estado</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @forelse(($appointmentsPreviewRows ?? collect()) as $previewAppointment)
                                            <tr>
                                                <td>#{{ $previewAppointment->id }}</td>
                                                <td>{{ $previewAppointment->service_label_report ?? ($previewAppointment->service->display_name ?? $previewAppointment->service->name ?? 'Servicio') }}</td>
                                                <td>{{ $previewAppointment->assignedUser->name ?? 'Profesional' }}</td>
                                                <td>{{ optional($previewAppointment->starts_at)?->format('d/m/Y H:i') }}</td>
                                                <td>{{ $previewAppointment->status_label ?? ucfirst((string) ($previewAppointment->status ?? 'scheduled')) }}</td>
                                            </tr>
                                        @empty
                                            <tr>
                                                <td colspan="5" class="text-muted text-center py-3">No hay citas para el filtro actual.</td>
                                            </tr>
                                        @endforelse
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <div class="row g-3">
                        <div class="col-md-6 col-xl-4">
                            <div class="card h-100 border">
                                <div class="card-body">
                                    <h6 class="mb-2">Productos mas vendidos</h6>
                                    <p class="text-sm text-muted mb-3">Ranking por unidades y monto vendido.</p>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <a class="btn btn-dark btn-sm mb-0" href="{{ route('reports.products.topSelling.pdf') }}">PDF</a>
                                        <a class="btn btn-outline-success btn-sm mb-0 js-report-launch" href="{{ route('reports.csv.viewer') }}" data-report-name="Productos mas vendidos" data-format="CSV" data-endpoint="{{ route('reports.csv.viewer') }}" data-csv-endpoint="{{ route('reports.products.topSelling.excel') }}" data-filters="date,currency">CSV</a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 col-xl-4">
                            <div class="card h-100 border">
                                <div class="card-body">
                                    <h6 class="mb-2">Productos de entrada</h6>
                                    <p class="text-sm text-muted mb-3">Entradas de inventario por orden de compra.</p>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <a class="btn btn-dark btn-sm mb-0" href="{{ route('reports.inventory.entries.pdf') }}">PDF</a>
                                        <a class="btn btn-outline-success btn-sm mb-0 js-report-launch" href="{{ route('reports.csv.viewer') }}" data-report-name="Productos de entrada" data-format="CSV" data-endpoint="{{ route('reports.csv.viewer') }}" data-csv-endpoint="{{ route('reports.inventory.entries.excel') }}" data-filters="date,currency">CSV</a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 col-xl-4">
                            <div class="card h-100 border">
                                <div class="card-body">
                                    <h6 class="mb-2">Gestion de ventas</h6>
                                    <p class="text-sm text-muted mb-3">Estado, cliente, montos y pagos por orden.</p>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <a class="btn btn-dark btn-sm mb-0" href="{{ route('reports.sales.management.pdf') }}">PDF</a>
                                        <a class="btn btn-outline-success btn-sm mb-0 js-report-launch" href="{{ route('reports.csv.viewer') }}" data-report-name="Gestion de ventas" data-format="CSV" data-endpoint="{{ route('reports.csv.viewer') }}" data-csv-endpoint="{{ route('reports.sales.management.excel') }}" data-filters="date,currency">CSV</a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 col-xl-4">
                            <div class="card h-100 border">
                                <div class="card-body">
                                    <h6 class="mb-2">Total del inventario</h6>
                                    <p class="text-sm text-muted mb-3">Stock y valor total por variante de producto.</p>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <a class="btn btn-dark btn-sm mb-0" href="{{ route('reports.inventory.total.pdf') }}">PDF</a>
                                        <a class="btn btn-outline-success btn-sm mb-0 js-report-launch" href="{{ route('reports.csv.viewer') }}" data-report-name="Total del inventario" data-format="CSV" data-endpoint="{{ route('reports.csv.viewer') }}" data-csv-endpoint="{{ route('reports.inventory.total.excel') }}" data-filters="date,currency">CSV</a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 col-xl-4">
                            <div class="card h-100 border">
                                <div class="card-body">
                                    <h6 class="mb-2">Reporte general por modulos</h6>
                                    <p class="text-sm text-muted mb-3">Resumen de metricas clave de cada modulo del sistema.</p>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <a class="btn btn-dark btn-sm mb-0" href="{{ route('reports.system.modules.pdf') }}">PDF</a>
                                        <a class="btn btn-outline-success btn-sm mb-0 js-report-launch" href="{{ route('reports.csv.viewer') }}" data-report-name="Reporte general por modulos" data-format="CSV" data-endpoint="{{ route('reports.csv.viewer') }}" data-csv-endpoint="{{ route('reports.system.modules.excel') }}" data-filters="date,currency">CSV</a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 col-xl-4">
                            <div class="card h-100 border">
                                <div class="card-body">
                                    <h6 class="mb-2">Clientes</h6>
                                    <p class="text-sm text-muted mb-3">Clientes con compras, actividad y montos cobrados.</p>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <a class="btn btn-dark btn-sm mb-0" href="{{ route('reports.customers.pdf') }}">PDF</a>
                                        <a class="btn btn-outline-success btn-sm mb-0 js-report-launch" href="{{ route('reports.csv.viewer') }}" data-report-name="Clientes" data-format="CSV" data-endpoint="{{ route('reports.csv.viewer') }}" data-csv-endpoint="{{ route('reports.customers.excel') }}" data-filters="date,currency,customer_status">CSV</a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 col-xl-4">
                            <div class="card h-100 border">
                                <div class="card-body">
                                    <h6 class="mb-2">Cuentas por cobrar</h6>
                                    <p class="text-sm text-muted mb-3">Ordenes con saldo pendiente de cobro.</p>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <a class="btn btn-dark btn-sm mb-0" href="{{ route('reports.accountsReceivable.pdf') }}">PDF</a>
                                        <a class="btn btn-outline-success btn-sm mb-0 js-report-launch" href="{{ route('reports.csv.viewer') }}" data-report-name="Cuentas por cobrar" data-format="CSV" data-endpoint="{{ route('reports.csv.viewer') }}" data-csv-endpoint="{{ route('reports.accountsReceivable.excel') }}" data-filters="date,currency,min_pending_balance">CSV</a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 col-xl-4">
                            <div class="card h-100 border">
                                <div class="card-body">
                                    <h6 class="mb-2">Gestión de citas y flujo</h6>
                                    <p class="text-sm text-muted mb-3">Agenda, confirmación, cancelaciones, pagos y saldo pendiente por cita.</p>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <a class="btn btn-dark btn-sm mb-0" href="{{ route('reports.appointments.workflow.pdf') }}">PDF</a>
                                        <a class="btn btn-outline-success btn-sm mb-0 js-report-launch" href="{{ route('reports.csv.viewer') }}" data-report-name="Gestion de citas y flujo" data-format="CSV" data-endpoint="{{ route('reports.csv.viewer') }}" data-csv-endpoint="{{ route('reports.appointments.workflow.excel') }}" data-filters="date,currency,appointment_status,appointment_payment_status,appointment_service_id">CSV</a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 col-xl-4">
                            <div class="card h-100 border">
                                <div class="card-body">
                                    <h6 class="mb-2">Libro de ventas</h6>
                                    <p class="text-sm text-muted mb-3">Ventas con IVA, notas de crédito/débito y retenciones.</p>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <a class="btn btn-dark btn-sm mb-0" href="{{ route('reports.sales.book.pdf') }}">PDF</a>
                                        <a class="btn btn-outline-success btn-sm mb-0 js-report-launch" href="{{ route('reports.csv.viewer') }}" data-report-name="Libro de ventas" data-format="CSV" data-endpoint="{{ route('reports.csv.viewer') }}" data-csv-endpoint="{{ route('reports.sales.book.excel') }}" data-filters="date,currency,sales_book_source">CSV</a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 col-xl-4">
                            <div class="card h-100 border">
                                <div class="card-body">
                                    <h6 class="mb-2">Gastos de tienda</h6>
                                    <p class="text-sm text-muted mb-3">Egresos por categoria, proveedor y metodo de pago.</p>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <a class="btn btn-dark btn-sm mb-0" href="{{ route('reports.storeExpenses.pdf') }}">PDF</a>
                                        <a class="btn btn-outline-success btn-sm mb-0 js-report-launch" href="{{ route('reports.csv.viewer') }}" data-report-name="Gastos de tienda" data-format="CSV" data-endpoint="{{ route('reports.csv.viewer') }}" data-csv-endpoint="{{ route('reports.storeExpenses.excel') }}" data-filters="date,currency,expense_category">CSV</a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 col-xl-4">
                            <div class="card h-100 border">
                                <div class="card-body">
                                    <h6 class="mb-2">Ventas por vendedor</h6>
                                    <p class="text-sm text-muted mb-3">Ventas y cobros por vendedor, con periodo semanal, mensual, trimestral o anual.</p>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <a class="btn btn-dark btn-sm mb-0" href="{{ route('reports.income.byUser.pdf') }}">PDF</a>
                                        <a class="btn btn-outline-success btn-sm mb-0 js-report-launch" href="{{ route('reports.csv.viewer') }}" data-report-name="Ventas por vendedor" data-format="CSV" data-endpoint="{{ route('reports.csv.viewer') }}" data-csv-endpoint="{{ route('reports.income.byUser.excel') }}" data-filters="date,currency,report_period,income_user_id,income_customer_id">CSV</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="modal fade" id="reportFiltersModal" tabindex="-1" aria-labelledby="reportFiltersModalLabel" aria-hidden="true">
                        <div class="modal-dialog modal-lg modal-dialog-scrollable">
                            <div class="modal-content">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="reportFiltersModalLabel">Configurar filtros del reporte</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <form id="reportFiltersForm" method="GET" action="{{ route('reports.index') }}">
                                    <input type="hidden" id="modal_csv_url" name="csv_url" value="" disabled>
                                    <input type="hidden" id="modal_report_name" name="report_name" value="" disabled>
                                    <div class="modal-body">
                                        <div class="row g-3">
                                            <div class="col-md-6" data-filter-group="date">
                                                <label for="modal_start_date" class="form-label">Fecha inicio</label>
                                                <input type="date" id="modal_start_date" name="start_date" value="{{ request('start_date', now()->subDays(30)->toDateString()) }}" class="form-control border border-1 p-2">
                                            </div>
                                            <div class="col-md-6" data-filter-group="date">
                                                <label for="modal_end_date" class="form-label">Fecha fin</label>
                                                <input type="date" id="modal_end_date" name="end_date" value="{{ request('end_date', now()->toDateString()) }}" class="form-control border border-1 p-2">
                                            </div>
                                            <div class="col-12" data-filter-group="date">
                                                <small class="text-muted d-block">El rango aplica a reportes que trabajan por periodo.</small>
                                            </div>

                                            <div class="col-md-6" data-filter-group="user_id">
                                                <label for="modal_user_id" class="form-label">Usuario</label>
                                                <select id="modal_user_id" name="user_id" class="form-control border border-1 p-2">
                                                    <option value="0">Todos</option>
                                                    @foreach(($reportUsers ?? []) as $reportUser)
                                                        <option value="{{ $reportUser->id }}" {{ (int) request('user_id', 0) === (int) $reportUser->id ? 'selected' : '' }}>{{ $reportUser->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="col-md-6" data-filter-group="payment_method_id">
                                                <label for="modal_payment_method_id" class="form-label">Metodo de pago</label>
                                                <select id="modal_payment_method_id" name="payment_method_id" class="form-control border border-1 p-2">
                                                    <option value="0">Todos</option>
                                                    @foreach(($reportPaymentMethods ?? []) as $reportPaymentMethod)
                                                        <option value="{{ $reportPaymentMethod->id }}" {{ (int) request('payment_method_id', 0) === (int) $reportPaymentMethod->id ? 'selected' : '' }}>{{ $reportPaymentMethod->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                            <div class="col-12" data-filter-group="payment_method_id">
                                                <small class="text-muted d-block">Si no seleccionas usuario o método de pago, el reporte incluirá todos.</small>
                                            </div>

                                            <div class="col-md-6" data-filter-group="currency">
                                                <label for="modal_currency_code" class="form-label">Moneda de salida</label>
                                                <select id="modal_currency_code" name="currency_code" class="form-control border border-1 p-2">
                                                    <option value="USD" {{ request('currency_code', $selectedCurrencyCode ?? $baseCurrencyCode ?? 'USD') === 'USD' ? 'selected' : '' }}>USD</option>
                                                    <option value="EUR" {{ request('currency_code', $selectedCurrencyCode ?? $baseCurrencyCode ?? 'USD') === 'EUR' ? 'selected' : '' }}>EUR</option>
                                                </select>
                                            </div>

                                            <div class="col-md-6" data-filter-group="report_period">
                                                <label for="modal_report_period" class="form-label">Periodo rápido</label>
                                                <select id="modal_report_period" name="report_period" class="form-control border border-1 p-2">
                                                    <option value="custom" {{ request('report_period', 'custom') === 'custom' ? 'selected' : '' }}>Personalizado</option>
                                                    <option value="weekly" {{ request('report_period') === 'weekly' ? 'selected' : '' }}>Semanal</option>
                                                    <option value="monthly" {{ request('report_period') === 'monthly' ? 'selected' : '' }}>Mensual</option>
                                                    <option value="quarterly" {{ request('report_period') === 'quarterly' ? 'selected' : '' }}>Trimestral</option>
                                                    <option value="yearly" {{ request('report_period') === 'yearly' ? 'selected' : '' }}>Anual</option>
                                                </select>
                                            </div>

                                            <div class="col-md-6" data-filter-group="customer_status">
                                                <label for="modal_customer_status" class="form-label">Estado de cliente</label>
                                                <select id="modal_customer_status" name="customer_status" class="form-control border border-1 p-2">
                                                    <option value="all" {{ request('customer_status', 'all') === 'all' ? 'selected' : '' }}>Todos</option>
                                                    <option value="active" {{ request('customer_status') === 'active' ? 'selected' : '' }}>Activos</option>
                                                    <option value="inactive" {{ request('customer_status') === 'inactive' ? 'selected' : '' }}>Inactivos</option>
                                                </select>
                                            </div>

                                            <div class="col-md-6" data-filter-group="expense_category">
                                                <label for="modal_expense_category" class="form-label">Categoría de gasto</label>
                                                <select id="modal_expense_category" name="expense_category" class="form-control border border-1 p-2">
                                                    <option value="">Todas</option>
                                                    @foreach(($expenseCategories ?? []) as $expenseCategoryOption)
                                                        <option value="{{ $expenseCategoryOption }}" {{ request('expense_category') === $expenseCategoryOption ? 'selected' : '' }}>{{ $expenseCategoryOption }}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="col-md-6" data-filter-group="min_pending_balance">
                                                <label for="modal_min_pending_balance" class="form-label">Saldo pendiente mínimo</label>
                                                <input type="number" id="modal_min_pending_balance" name="min_pending_balance" min="0" step="0.01" value="{{ request('min_pending_balance', '0') }}" class="form-control border border-1 p-2" placeholder="0.00" data-decimal-friendly="true">
                                            </div>

                                            <div class="col-md-6" data-filter-group="sales_book_source">
                                                <label for="modal_sales_book_source" class="form-label">Fuente del libro de ventas</label>
                                                <select id="modal_sales_book_source" name="sales_book_source" class="form-control border border-1 p-2">
                                                    <option value="shopix" {{ request('sales_book_source', $selectedSalesBookSource ?? 'shopix') === 'shopix' ? 'selected' : '' }}>Shopix</option>
                                                    <option value="hka" {{ request('sales_book_source', $selectedSalesBookSource ?? 'shopix') === 'hka' ? 'selected' : '' }}>Sesion HKA</option>
                                                </select>
                                            </div>
                                            <div class="col-12" data-filter-group="sales_book_source">
                                                <small class="text-muted d-block">HKA se usa para reconciliar estatus y controles con la sesión autenticada.</small>
                                            </div>

                                            <div class="col-md-4" data-filter-group="appointment_status">
                                                <label for="modal_appointment_status" class="form-label">Estado de cita</label>
                                                <select id="modal_appointment_status" name="appointment_status" class="form-control border border-1 p-2">
                                                    <option value="all" {{ request('appointment_status', $selectedAppointmentStatus ?? 'all') === 'all' ? 'selected' : '' }}>Todos</option>
                                                    <option value="scheduled" {{ request('appointment_status', $selectedAppointmentStatus ?? 'all') === 'scheduled' ? 'selected' : '' }}>Programada</option>
                                                    <option value="confirmed" {{ request('appointment_status', $selectedAppointmentStatus ?? 'all') === 'confirmed' ? 'selected' : '' }}>Confirmada</option>
                                                    <option value="completed" {{ request('appointment_status', $selectedAppointmentStatus ?? 'all') === 'completed' ? 'selected' : '' }}>Completada</option>
                                                    <option value="cancelled" {{ request('appointment_status', $selectedAppointmentStatus ?? 'all') === 'cancelled' ? 'selected' : '' }}>Cancelada</option>
                                                    <option value="no_show" {{ request('appointment_status', $selectedAppointmentStatus ?? 'all') === 'no_show' ? 'selected' : '' }}>No asistió</option>
                                                </select>
                                            </div>

                                            <div class="col-md-4" data-filter-group="appointment_payment_status">
                                                <label for="modal_appointment_payment_status" class="form-label">Estado de pago</label>
                                                <select id="modal_appointment_payment_status" name="appointment_payment_status" class="form-control border border-1 p-2">
                                                    <option value="all" {{ request('appointment_payment_status', $selectedAppointmentPaymentStatus ?? 'all') === 'all' ? 'selected' : '' }}>Todos</option>
                                                    <option value="pending" {{ request('appointment_payment_status', $selectedAppointmentPaymentStatus ?? 'all') === 'pending' ? 'selected' : '' }}>Pendiente</option>
                                                    <option value="partial" {{ request('appointment_payment_status', $selectedAppointmentPaymentStatus ?? 'all') === 'partial' ? 'selected' : '' }}>Parcial</option>
                                                    <option value="paid" {{ request('appointment_payment_status', $selectedAppointmentPaymentStatus ?? 'all') === 'paid' ? 'selected' : '' }}>Pagado</option>
                                                    <option value="waived" {{ request('appointment_payment_status', $selectedAppointmentPaymentStatus ?? 'all') === 'waived' ? 'selected' : '' }}>Sin cobro</option>
                                                </select>
                                            </div>

                                            <div class="col-md-4" data-filter-group="appointment_service_id">
                                                <label for="modal_appointment_service_id" class="form-label">Servicio</label>
                                                <select id="modal_appointment_service_id" name="appointment_service_id" class="form-control border border-1 p-2">
                                                    <option value="0">Todos los servicios</option>
                                                    @foreach(($appointmentServices ?? []) as $serviceOption)
                                                        <option value="{{ $serviceOption->id }}" {{ (int) request('appointment_service_id', $selectedAppointmentServiceId ?? 0) === (int) $serviceOption->id ? 'selected' : '' }}>{{ $serviceOption->display_name ?? $serviceOption->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="col-md-6" data-filter-group="income_user_id">
                                                <label for="modal_income_user_id" class="form-label">Usuario</label>
                                                <select id="modal_income_user_id" name="income_user_id" class="form-control border border-1 p-2">
                                                    <option value="0">Todos</option>
                                                    @foreach(($incomeUsers ?? []) as $incomeUser)
                                                        <option value="{{ $incomeUser->id }}" {{ (int) request('income_user_id', $selectedIncomeUserId ?? 0) === (int) $incomeUser->id ? 'selected' : '' }}>{{ $incomeUser->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>

                                            <div class="col-md-6" data-filter-group="income_customer_id">
                                                <label for="modal_income_customer_id" class="form-label">Paciente/Cliente</label>
                                                <select id="modal_income_customer_id" name="income_customer_id" class="form-control border border-1 p-2">
                                                    <option value="0">Todos</option>
                                                    @foreach(($incomeCustomers ?? []) as $incomeCustomer)
                                                        <option value="{{ $incomeCustomer->id }}" {{ (int) request('income_customer_id', $selectedIncomeCustomerId ?? 0) === (int) $incomeCustomer->id ? 'selected' : '' }}>{{ $incomeCustomer->name }}</option>
                                                    @endforeach
                                                </select>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-outline-secondary mb-0" data-bs-dismiss="modal">Cancelar</button>
                                        <button type="submit" class="btn btn-dark mb-0">Generar reporte</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var modalElement = document.getElementById('reportFiltersModal');
    var modalLabel = document.getElementById('reportFiltersModalLabel');
    var form = document.getElementById('reportFiltersForm');
    var launchers = document.querySelectorAll('.js-report-launch');
    var groups = modalElement ? modalElement.querySelectorAll('[data-filter-group]') : [];
    var periodSelect = document.getElementById('modal_report_period');
    var startDateInput = document.getElementById('modal_start_date');
    var endDateInput = document.getElementById('modal_end_date');
    var csvUrlInput = document.getElementById('modal_csv_url');
    var reportNameInput = document.getElementById('modal_report_name');
    var submitButton = form.querySelector('button[type="submit"]');

    if (!modalElement || !form || !launchers.length) {
        return;
    }

    if (typeof bootstrap === 'undefined' || !bootstrap.Modal) {
        return;
    }

    var modal = bootstrap.Modal.getOrCreateInstance(modalElement);

    var pad = function (value) {
        return String(value).padStart(2, '0');
    };

    var formatDate = function (date) {
        return [date.getFullYear(), pad(date.getMonth() + 1), pad(date.getDate())].join('-');
    };

    var applyReportPeriod = function () {
        if (!periodSelect || !startDateInput || !endDateInput) {
            return;
        }

        var today = new Date();
        var period = periodSelect.value;

        if (period === 'weekly') {
            var weekStart = new Date(today);
            var day = weekStart.getDay();
            var offset = day === 0 ? -6 : 1 - day;
            weekStart.setDate(weekStart.getDate() + offset);
            startDateInput.value = formatDate(weekStart);
            endDateInput.value = formatDate(today);
            return;
        }

        if (period === 'monthly') {
            startDateInput.value = formatDate(new Date(today.getFullYear(), today.getMonth(), 1));
            endDateInput.value = formatDate(today);
            return;
        }

        if (period === 'quarterly') {
            var quarterMonth = Math.floor(today.getMonth() / 3) * 3;
            startDateInput.value = formatDate(new Date(today.getFullYear(), quarterMonth, 1));
            endDateInput.value = formatDate(today);
            return;
        }

        if (period === 'yearly') {
            startDateInput.value = formatDate(new Date(today.getFullYear(), 0, 1));
            endDateInput.value = formatDate(today);
        }
    };

    periodSelect?.addEventListener('change', applyReportPeriod);

    var toggleGroups = function (filters) {
        groups.forEach(function (group) {
            var key = group.getAttribute('data-filter-group');
            var visible = filters.indexOf(key) !== -1;
            group.classList.toggle('d-none', !visible);

            var controls = group.querySelectorAll('input, select, textarea');
            controls.forEach(function (control) {
                control.disabled = !visible;
            });
        });
    };

    launchers.forEach(function (launcher) {
        launcher.addEventListener('click', function (event) {
            event.preventDefault();

            var endpoint = launcher.getAttribute('data-endpoint') || launcher.getAttribute('href');
            var reportName = launcher.getAttribute('data-report-name') || 'Reporte';
            var format = launcher.getAttribute('data-format') || 'PDF';
            var csvEndpoint = launcher.getAttribute('data-csv-endpoint') || '';
            var filtersRaw = launcher.getAttribute('data-filters') || '';
            var filters = filtersRaw
                .split(',')
                .map(function (value) {
                    return value.trim();
                })
                .filter(function (value) {
                    return value !== '';
                });

            ['date', 'user_id', 'payment_method_id'].forEach(function (defaultFilter) {
                if (filters.indexOf(defaultFilter) === -1) {
                    filters.push(defaultFilter);
                }
            });

            form.setAttribute('action', endpoint);
            form.setAttribute('target', csvEndpoint ? '_blank' : '_self');

            if (csvUrlInput) {
                csvUrlInput.value = csvEndpoint;
                csvUrlInput.disabled = csvEndpoint === '';
            }

            if (reportNameInput) {
                reportNameInput.value = reportName;
                reportNameInput.disabled = csvEndpoint === '';
            }

            if (submitButton) {
                submitButton.textContent = csvEndpoint ? 'Abrir CSV online' : 'Generar reporte';
            }

            modalLabel.textContent = 'Filtros: ' + reportName + ' (' + format + ')';
            toggleGroups(filters);

            if (periodSelect) {
                if (filters.indexOf('report_period') === -1) {
                    periodSelect.value = 'custom';
                } else {
                    applyReportPeriod();
                }
            }

            modal.show();
        });
    });
});
</script>
@endsection
