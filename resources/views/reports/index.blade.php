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
                    </form>

                    @php
                        $params = [
                            'start_date' => request('start_date', now()->subDays(30)->toDateString()),
                            'end_date' => request('end_date', now()->toDateString()),
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
                                        <a class="btn btn-dark btn-sm mb-0" href="{{ route('reports.inventory.total.pdf') }}">PDF</a>
                                        <a class="btn btn-outline-success btn-sm mb-0" href="{{ route('reports.inventory.total.excel') }}">Excel</a>
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
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
