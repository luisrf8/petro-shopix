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
                    <form method="GET" class="row g-3 align-items-end mb-4">
                        <div class="col-md-4">
                            <label for="start_date" class="form-label">Fecha inicio</label>
                            <input type="date" id="start_date" name="start_date" value="{{ request('start_date', now()->subDays(30)->toDateString()) }}" class="form-control border border-1 p-2">
                        </div>
                        <div class="col-md-4">
                            <label for="end_date" class="form-label">Fecha fin</label>
                            <input type="date" id="end_date" name="end_date" value="{{ request('end_date', now()->toDateString()) }}" class="form-control border border-1 p-2">
                        </div>
                        <div class="col-md-4">
                            <small class="text-muted d-block">El rango aplica para ventas, entradas y reporte general.</small>
                        </div>
                        <div class="col-md-4">
                            <label for="currency_code" class="form-label">Moneda de salida</label>
                            <select id="currency_code" name="currency_code" class="form-control border border-1 p-2">
                                <option value="USD" {{ request('currency_code', $selectedCurrencyCode ?? $baseCurrencyCode ?? 'USD') === 'USD' ? 'selected' : '' }}>USD</option>
                                <option value="EUR" {{ request('currency_code', $selectedCurrencyCode ?? $baseCurrencyCode ?? 'USD') === 'EUR' ? 'selected' : '' }}>EUR</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="customer_status" class="form-label">Estado de cliente (reporte clientes)</label>
                            <select id="customer_status" name="customer_status" class="form-control border border-1 p-2">
                                <option value="all" {{ request('customer_status', 'all') === 'all' ? 'selected' : '' }}>Todos</option>
                                <option value="active" {{ request('customer_status') === 'active' ? 'selected' : '' }}>Activos</option>
                                <option value="inactive" {{ request('customer_status') === 'inactive' ? 'selected' : '' }}>Inactivos</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="expense_category" class="form-label">Categoría de gasto (reporte gastos)</label>
                            <select id="expense_category" name="expense_category" class="form-control border border-1 p-2">
                                <option value="">Todas</option>
                                @foreach(($expenseCategories ?? []) as $expenseCategoryOption)
                                    <option value="{{ $expenseCategoryOption }}" {{ request('expense_category') === $expenseCategoryOption ? 'selected' : '' }}>{{ $expenseCategoryOption }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label for="min_pending_balance" class="form-label">Saldo pendiente mínimo (cuentas por cobrar)</label>
                            <input type="number" id="min_pending_balance" name="min_pending_balance" min="0" step="0.01" value="{{ request('min_pending_balance', '0') }}" class="form-control border border-1 p-2" placeholder="0.00" data-decimal-friendly="true">
                        </div>
                        <div class="col-md-4">
                            <label for="sales_book_source" class="form-label">Fuente del libro de ventas</label>
                            <select id="sales_book_source" name="sales_book_source" class="form-control border border-1 p-2">
                                <option value="shopix" {{ request('sales_book_source', $selectedSalesBookSource ?? 'shopix') === 'shopix' ? 'selected' : '' }}>Shopix</option>
                                <option value="hka" {{ request('sales_book_source', $selectedSalesBookSource ?? 'shopix') === 'hka' ? 'selected' : '' }}>Sesion HKA</option>
                            </select>
                            <small class="text-muted d-block mt-1">Para el libro de ventas, HKA se usa para reconciliar estatus y controles con la sesión autenticada.</small>
                        </div>
                        <div class="col-12 d-flex gap-2">
                            <button type="submit" class="btn btn-dark mb-0">Aplicar filtros</button>
                            <a href="{{ route('reports.index') }}" class="btn btn-outline-secondary mb-0">Limpiar</a>
                        </div>
                    </form>

                    @php
                        $params = [
                            'start_date' => request('start_date', now()->subDays(30)->toDateString()),
                            'end_date' => request('end_date', now()->toDateString()),
                            'customer_status' => request('customer_status', 'all'),
                            'expense_category' => request('expense_category', ''),
                            'min_pending_balance' => request('min_pending_balance', '0'),
                            'currency_code' => request('currency_code', $selectedCurrencyCode ?? $baseCurrencyCode ?? 'USD'),
                            'sales_book_source' => request('sales_book_source', $selectedSalesBookSource ?? 'shopix'),
                        ];
                    @endphp

                    <div class="row g-3">
                        <div class="col-md-6 col-xl-4">
                            <div class="card h-100 border">
                                <div class="card-body">
                                    <h6 class="mb-2">Productos mas vendidos</h6>
                                    <p class="text-sm text-muted mb-3">Ranking por unidades y monto vendido.</p>
                                    <div class="d-flex gap-2 flex-wrap">
                                        <a class="btn btn-dark btn-sm mb-0" href="{{ route('reports.products.topSelling.pdf', $params) }}">PDF</a>
                                        <a class="btn btn-outline-success btn-sm mb-0" href="{{ route('reports.products.topSelling.excel', $params) }}">Excel</a>
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
                                        <a class="btn btn-dark btn-sm mb-0" href="{{ route('reports.inventory.entries.pdf', $params) }}">PDF</a>
                                        <a class="btn btn-outline-success btn-sm mb-0" href="{{ route('reports.inventory.entries.excel', $params) }}">Excel</a>
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
                                        <a class="btn btn-dark btn-sm mb-0" href="{{ route('reports.sales.management.pdf', $params) }}">PDF</a>
                                        <a class="btn btn-outline-success btn-sm mb-0" href="{{ route('reports.sales.management.excel', $params) }}">Excel</a>
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
                                        <a class="btn btn-dark btn-sm mb-0" href="{{ route('reports.inventory.total.pdf', $params) }}">PDF</a>
                                        <a class="btn btn-outline-success btn-sm mb-0" href="{{ route('reports.inventory.total.excel', $params) }}">Excel</a>
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
                                        <a class="btn btn-dark btn-sm mb-0" href="{{ route('reports.system.modules.pdf', $params) }}">PDF</a>
                                        <a class="btn btn-outline-success btn-sm mb-0" href="{{ route('reports.system.modules.excel', $params) }}">Excel</a>
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
                                        <a class="btn btn-dark btn-sm mb-0" href="{{ route('reports.customers.pdf', $params) }}">PDF</a>
                                        <a class="btn btn-outline-success btn-sm mb-0" href="{{ route('reports.customers.excel', $params) }}">Excel</a>
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
                                        <a class="btn btn-dark btn-sm mb-0" href="{{ route('reports.accountsReceivable.pdf', $params) }}">PDF</a>
                                        <a class="btn btn-outline-success btn-sm mb-0" href="{{ route('reports.accountsReceivable.excel', $params) }}">Excel</a>
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
                                        <a class="btn btn-dark btn-sm mb-0" href="{{ route('reports.sales.book.pdf', $params) }}">PDF</a>
                                        <a class="btn btn-outline-success btn-sm mb-0" href="{{ route('reports.sales.book.excel', $params) }}">Excel</a>
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
                                        <a class="btn btn-dark btn-sm mb-0" href="{{ route('reports.storeExpenses.pdf', $params) }}">PDF</a>
                                        <a class="btn btn-outline-success btn-sm mb-0" href="{{ route('reports.storeExpenses.excel', $params) }}">Excel</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
