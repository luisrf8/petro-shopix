@extends('layouts.app')

@section('title', 'Reportes PDF')

@section('content')
<div class="container-fluid py-2">
    <div class="row mt-4">
        <div class="col-12">
            <div class="card">
                <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                    <div class="bg-gradient-dark shadow-dark border-radius-lg pt-4 pb-3">
                        <h6 class="text-white text-capitalize ps-3">Centro de Reportes PDF</h6>
                    </div>
                </div>
                <div class="card-body">
                    <div class="alert alert-light border text-sm" role="alert">
                        Selecciona un reporte y formato. Los filtros se solicitan en un modal segun el reporte que vayas a generar.
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
                                        <a class="btn btn-dark btn-sm mb-0 js-report-launch" href="{{ route('reports.products.topSelling.pdf') }}" data-report-name="Productos mas vendidos" data-format="PDF" data-endpoint="{{ route('reports.products.topSelling.pdf') }}" data-filters="date,currency">PDF</a>
                                        <a class="btn btn-outline-success btn-sm mb-0 js-report-launch" href="{{ route('reports.products.topSelling.excel') }}" data-report-name="Productos mas vendidos" data-format="Excel" data-endpoint="{{ route('reports.products.topSelling.excel') }}" data-filters="date,currency">Excel</a>
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
                                        <a class="btn btn-dark btn-sm mb-0 js-report-launch" href="{{ route('reports.inventory.entries.pdf') }}" data-report-name="Productos de entrada" data-format="PDF" data-endpoint="{{ route('reports.inventory.entries.pdf') }}" data-filters="date,currency">PDF</a>
                                        <a class="btn btn-outline-success btn-sm mb-0 js-report-launch" href="{{ route('reports.inventory.entries.excel') }}" data-report-name="Productos de entrada" data-format="Excel" data-endpoint="{{ route('reports.inventory.entries.excel') }}" data-filters="date,currency">Excel</a>
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
                                        <a class="btn btn-dark btn-sm mb-0 js-report-launch" href="{{ route('reports.sales.management.pdf') }}" data-report-name="Gestion de ventas" data-format="PDF" data-endpoint="{{ route('reports.sales.management.pdf') }}" data-filters="date,currency">PDF</a>
                                        <a class="btn btn-outline-success btn-sm mb-0 js-report-launch" href="{{ route('reports.sales.management.excel') }}" data-report-name="Gestion de ventas" data-format="Excel" data-endpoint="{{ route('reports.sales.management.excel') }}" data-filters="date,currency">Excel</a>
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
                                        <a class="btn btn-dark btn-sm mb-0 js-report-launch" href="{{ route('reports.inventory.total.pdf') }}" data-report-name="Total del inventario" data-format="PDF" data-endpoint="{{ route('reports.inventory.total.pdf') }}" data-filters="currency">PDF</a>
                                        <a class="btn btn-outline-success btn-sm mb-0 js-report-launch" href="{{ route('reports.inventory.total.excel') }}" data-report-name="Total del inventario" data-format="Excel" data-endpoint="{{ route('reports.inventory.total.excel') }}" data-filters="currency">Excel</a>
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
                                        <a class="btn btn-dark btn-sm mb-0 js-report-launch" href="{{ route('reports.system.modules.pdf') }}" data-report-name="Reporte general por modulos" data-format="PDF" data-endpoint="{{ route('reports.system.modules.pdf') }}" data-filters="date,currency">PDF</a>
                                        <a class="btn btn-outline-success btn-sm mb-0 js-report-launch" href="{{ route('reports.system.modules.excel') }}" data-report-name="Reporte general por modulos" data-format="Excel" data-endpoint="{{ route('reports.system.modules.excel') }}" data-filters="date,currency">Excel</a>
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
                                        <a class="btn btn-dark btn-sm mb-0 js-report-launch" href="{{ route('reports.customers.pdf') }}" data-report-name="Clientes" data-format="PDF" data-endpoint="{{ route('reports.customers.pdf') }}" data-filters="date,currency,customer_status">PDF</a>
                                        <a class="btn btn-outline-success btn-sm mb-0 js-report-launch" href="{{ route('reports.customers.excel') }}" data-report-name="Clientes" data-format="Excel" data-endpoint="{{ route('reports.customers.excel') }}" data-filters="date,currency,customer_status">Excel</a>
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
                                        <a class="btn btn-dark btn-sm mb-0 js-report-launch" href="{{ route('reports.accountsReceivable.pdf') }}" data-report-name="Cuentas por cobrar" data-format="PDF" data-endpoint="{{ route('reports.accountsReceivable.pdf') }}" data-filters="date,currency,min_pending_balance">PDF</a>
                                        <a class="btn btn-outline-success btn-sm mb-0 js-report-launch" href="{{ route('reports.accountsReceivable.excel') }}" data-report-name="Cuentas por cobrar" data-format="Excel" data-endpoint="{{ route('reports.accountsReceivable.excel') }}" data-filters="date,currency,min_pending_balance">Excel</a>
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
                                        <a class="btn btn-dark btn-sm mb-0 js-report-launch" href="{{ route('reports.appointments.workflow.pdf') }}" data-report-name="Gestion de citas y flujo" data-format="PDF" data-endpoint="{{ route('reports.appointments.workflow.pdf') }}" data-filters="date,currency,appointment_status,appointment_payment_status,appointment_service_id">PDF</a>
                                        <a class="btn btn-outline-success btn-sm mb-0 js-report-launch" href="{{ route('reports.appointments.workflow.excel') }}" data-report-name="Gestion de citas y flujo" data-format="Excel" data-endpoint="{{ route('reports.appointments.workflow.excel') }}" data-filters="date,currency,appointment_status,appointment_payment_status,appointment_service_id">Excel</a>
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
                                        <a class="btn btn-dark btn-sm mb-0 js-report-launch" href="{{ route('reports.sales.book.pdf') }}" data-report-name="Libro de ventas" data-format="PDF" data-endpoint="{{ route('reports.sales.book.pdf') }}" data-filters="date,currency,sales_book_source">PDF</a>
                                        <a class="btn btn-outline-success btn-sm mb-0 js-report-launch" href="{{ route('reports.sales.book.excel') }}" data-report-name="Libro de ventas" data-format="Excel" data-endpoint="{{ route('reports.sales.book.excel') }}" data-filters="date,currency,sales_book_source">Excel</a>
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
                                        <a class="btn btn-dark btn-sm mb-0 js-report-launch" href="{{ route('reports.storeExpenses.pdf') }}" data-report-name="Gastos de tienda" data-format="PDF" data-endpoint="{{ route('reports.storeExpenses.pdf') }}" data-filters="date,currency,expense_category">PDF</a>
                                        <a class="btn btn-outline-success btn-sm mb-0 js-report-launch" href="{{ route('reports.storeExpenses.excel') }}" data-report-name="Gastos de tienda" data-format="Excel" data-endpoint="{{ route('reports.storeExpenses.excel') }}" data-filters="date,currency,expense_category">Excel</a>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-md-6 col-xl-4">
                            <div class="card h-100 border">
                                <div class="card-body">
                                    <h6 class="mb-2">Ingresos por usuario</h6>
                                    <p class="text-sm text-muted mb-3">Ingresos vendidos/cobrados por usuario, con filtro por paciente o cliente.</p>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <a class="btn btn-dark btn-sm mb-0 js-report-launch" href="{{ route('reports.income.byUser.pdf') }}" data-report-name="Ingresos por usuario" data-format="PDF" data-endpoint="{{ route('reports.income.byUser.pdf') }}" data-filters="date,currency,income_user_id,income_customer_id">PDF</a>
                                        <a class="btn btn-outline-success btn-sm mb-0 js-report-launch" href="{{ route('reports.income.byUser.excel') }}" data-report-name="Ingresos por usuario" data-format="Excel" data-endpoint="{{ route('reports.income.byUser.excel') }}" data-filters="date,currency,income_user_id,income_customer_id">Excel</a>
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

                                            <div class="col-md-6" data-filter-group="currency">
                                                <label for="modal_currency_code" class="form-label">Moneda de salida</label>
                                                <select id="modal_currency_code" name="currency_code" class="form-control border border-1 p-2">
                                                    <option value="USD" {{ request('currency_code', $selectedCurrencyCode ?? $baseCurrencyCode ?? 'USD') === 'USD' ? 'selected' : '' }}>USD</option>
                                                    <option value="EUR" {{ request('currency_code', $selectedCurrencyCode ?? $baseCurrencyCode ?? 'USD') === 'EUR' ? 'selected' : '' }}>EUR</option>
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

    if (!modalElement || !form || !launchers.length) {
        return;
    }

    if (typeof bootstrap === 'undefined' || !bootstrap.Modal) {
        return;
    }

    var modal = bootstrap.Modal.getOrCreateInstance(modalElement);

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
            var filtersRaw = launcher.getAttribute('data-filters') || '';
            var filters = filtersRaw
                .split(',')
                .map(function (value) {
                    return value.trim();
                })
                .filter(function (value) {
                    return value !== '';
                });

            form.setAttribute('action', endpoint);
            modalLabel.textContent = 'Filtros: ' + reportName + ' (' + format + ')';
            toggleGroups(filters);
            modal.show();
        });
    });
});
</script>
@endsection
