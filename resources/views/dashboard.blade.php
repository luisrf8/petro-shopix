@extends('layouts.app')

@section('title', 'Shopix - Dashboard')

@section('content')
        <style>
            .dashboard-toast-stack {
                position: fixed;
                top: 1rem;
                right: 1rem;
                z-index: 2060;
                display: flex;
                flex-direction: column;
                gap: 0.5rem;
            }

            .dashboard-toast {
                min-width: 280px;
                max-width: 420px;
                border-radius: 10px;
                color: #fff;
                padding: 0.75rem 1rem;
                box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
                opacity: 0;
                transform: translateY(-6px);
                transition: opacity 0.2s ease, transform 0.2s ease;
            }

            .dashboard-toast.show {
                opacity: 1;
                transform: translateY(0);
            }

            .dashboard-toast.warning {
                background: #7a4e00;
            }

            .dashboard-toast.error {
                background: #842029;
            }

            .chart-neo-surface {
                background: linear-gradient(135deg, #ffffff 0%, #f3f6ff 45%, #eef2ff 100%);
                border: 1px solid rgba(148, 163, 184, 0.24);
                box-shadow: 0 14px 30px rgba(15, 23, 42, 0.12);
                border-radius: 16px;
                backdrop-filter: blur(4px);
            }

            .chart-canvas {
                border-radius: 12px;
            }

            .dashboard-headline {
                display: flex;
                align-items: center;
                justify-content: space-between;
                gap: 1rem;
                margin-bottom: 1.25rem;
            }

            .dashboard-title-wrap {
                min-width: 0;
            }

            .dashboard-store-url-inline {
                width: 100%;
                max-width: 460px;
            }

            .dashboard-tour-btn {
                display: inline-flex;
                align-items: center;
                gap: 0.35rem;
                margin-top: 0.65rem;
            }

            .dashboard-url-shell {
                display: flex;
                align-items: center;
                gap: 0.15rem;
                background: var(--bs-tertiary-bg);
                border-radius: 0.75rem;
                padding: 0.35rem 0.5rem;
            }

            .dashboard-url-input {
                min-width: 0;
                border: 0 !important;
                background: transparent !important;
                box-shadow: none !important;
                padding: 0 !important;
                font-size: 0.9rem;
                line-height: 1.3;
            }

            .dashboard-url-input:focus {
                border: 0 !important;
                box-shadow: none !important;
            }

            .dashboard-url-icon-btn {
                display: inline-flex;
                align-items: center;
                justify-content: center;
                width: 2.15rem;
                height: 2.15rem;
                flex: 0 0 2.15rem;
                border: 0 !important;
                background: transparent !important;
                box-shadow: none !important;
                padding: 0;
                color: inherit;
                line-height: 1;
            }

            .dashboard-url-icon-btn .material-symbols-rounded {
                display: block;
                line-height: 1;
            }

            .dashboard-url-icon-btn:focus,
            .dashboard-url-icon-btn:hover,
            .dashboard-url-icon-btn:active {
                border: 0 !important;
                background: transparent !important;
                box-shadow: none !important;
                color: inherit;
            }

            @media (max-width: 992px) {
                .dashboard-headline {
                    flex-direction: column;
                    align-items: stretch;
                    gap: 0.75rem;
                }

                .dashboard-store-url-inline {
                    max-width: 100%;
                }
            }

            @media (max-width: 576px) {
                .dashboard-url-input {
                    font-size: 0.82rem;
                }

                .dashboard-url-shell {
                    padding: 0.32rem 0.42rem;
                    gap: 0.1rem;
                }

                .dashboard-url-icon-btn {
                    width: 1.9rem;
                    height: 1.9rem;
                    flex: 0 0 1.9rem;
                }

                .dashboard-tour-btn {
                    width: 100%;
                    justify-content: center;
                }
            }
        </style>
        <div id="dashboardToastContainer" class="dashboard-toast-stack"></div>
    <div class="container-fluid py-2">
            @if($isDeliveryUser ?? false)
            <div class="row">
                <div class="col-md-6 col-xl-3 mb-4">
                    <div class="card">
                        <div class="card-body p-3">
                            <p class="text-sm mb-1 text-uppercase font-weight-bold">Delivery tienda pendientes</p>
                            <h4 class="mb-0">{{ number_format($deliveryDashboardOrders->count()) }}</h4>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-xl-3 mb-4">
                    <div class="card">
                        <div class="card-body p-3">
                            <p class="text-sm mb-1 text-uppercase font-weight-bold">Monto cobrado</p>
                            <h4 class="mb-0">${{ number_format((float) $deliveryDashboardAmount, 2) }}</h4>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card my-4">
                <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                    <div class="bg-gradient-dark shadow-dark border-radius-lg pt-3 pb-3 d-flex justify-content-between align-items-center">
                        <h6 class="text-white text-capitalize ps-3 mb-0">Órdenes Delivery tienda por entregar</h6>
                        <a href="{{ url('/paid-pending-deliveries') }}" class="btn btn-sm btn-light mb-0 me-3">Ver todas</a>
                    </div>
                </div>
                <div class="card-body px-0 pb-2">
                    <div class="table-responsive">
                        <table class="table align-items-center mb-0">
                            <thead>
                                <tr>
                                    <th>Orden</th>
                                    <th>Fecha</th>
                                    <th>Cliente</th>
                                    <th>Items</th>
                                    <th>Total</th>
                                    <th>Pagado</th>
                                    <th>Entrega</th>
                                    <th>Acción</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($deliveryDashboardOrders as $order)
                                    <tr>
                                        <td>#{{ $order->id }}</td>
                                        <td>{{ \Carbon\Carbon::parse($order->date)->format('d/m/Y') }}</td>
                                        <td>
                                            <div class="d-flex flex-column">
                                                <span class="font-weight-bold text-sm">{{ $order->user->name ?? 'Cliente no asignado' }}</span>
                                                <span class="text-xs text-secondary">{{ $order->user->phone_number ?? '-' }}</span>
                                            </div>
                                        </td>
                                        <td>{{ $order->total_items }}</td>
                                        <td>${{ number_format($order->order_total_amount, 2) }}</td>
                                        <td><span class="badge bg-success">${{ number_format($order->approved_paid_amount, 2) }}</span></td>
                                        <td>{{ $order->preference }}</td>
                                        <td>
                                            <a href="{{ route('sales.showByOrder', $order->id) }}" class="btn btn-outline-dark btn-sm mb-0">Gestionar delivery</a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-4">No hay órdenes de Delivery tienda pendientes por entregar.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @else
      <div class="row">
        <div class="col-12">
            <div class="dashboard-headline ms-1 ms-md-3" id="tour-dashboard-headline">
                <div class="dashboard-title-wrap">
                    <h3 class="mb-0 h4 font-weight-bolder">{{ $user->name }}</h3>
                    <p class="mb-0">Datos y Análisis.</p>
                    <button type="button" class="btn btn-outline-dark btn-sm mb-0 dashboard-tour-btn" id="startDashboardTourBtn">
                        <i class="material-symbols-rounded" style="font-size:18px;">school</i>
                        Recorrido rapido
                    </button>
                </div>

                @if(!empty($tenantPublicUrl))
                <div class="dashboard-store-url-inline" id="tour-dashboard-store-url">
                    <div class="dashboard-url-shell">
                        <input type="text" class="form-control dashboard-url-input" id="dashboardStoreUrlInput" value="{{ $tenantPublicUrl }}" readonly>
                        <a href="{{ $tenantPublicUrl }}" target="_blank" rel="noopener" class="btn dashboard-url-icon-btn" aria-label="Abrir tienda" title="Abrir tienda">
                            <i class="material-symbols-rounded">open_in_new</i>
                        </a>
                        <button type="button" class="btn dashboard-url-icon-btn" id="dashboardCopyStoreUrlBtn" aria-label="Copiar enlace" title="Copiar enlace" data-icon-default="content_copy">
                            <i class="material-symbols-rounded">content_copy</i>
                        </button>
                    </div>
                </div>
                @endif
            </div>
        </div>

                                @if(isset($currentPlanPayment) && $currentPlanPayment)
                                <div class="col-12 mb-3">
                                        @if(!is_null($currentPlanDaysRemaining) && $currentPlanDaysRemaining < 0)
                                                <div class="alert alert-danger border mb-0" role="alert">
                                                        Tu plan <strong>{{ $currentPlanPayment->plan->name ?? 'actual' }}</strong> está vencido hace {{ abs((int) $currentPlanDaysRemaining) }} días.
                                                        Registra tu pago desde <a href="{{ route('tenant.store') }}" class="alert-link">Gestión de Tienda</a>.
                                                </div>
                                        @elseif(!is_null($currentPlanDaysRemaining) && $currentPlanDaysRemaining <= 7)
                                                <div class="alert alert-warning border mb-0" role="alert">
                                                        Tu plan <strong>{{ $currentPlanPayment->plan->name ?? 'actual' }}</strong> vence en {{ (int) $currentPlanDaysRemaining }} días.
                                                        Puedes anticipar el pago desde <a href="{{ route('tenant.store') }}" class="alert-link">Gestión de Tienda</a>.
                                                </div>
                                        @endif
                                </div>
                                @endif
        @foreach($stats as $stat)
        <a href="{{ $stat['link'] }}" class="text-decoration-none col-xl-3 col-sm-6">
            <div class="card">
              <div class="card-header p-2 ps-3">
                <div class="d-flex justify-content-between">
                  <div>
                    <p class="text-sm mb-0 text-capitalize">{{$stat['name']}}</p>
                    <h4 class="mb-0">{{$stat['count']}}</h4>
                  </div>
                  <div class="icon icon-md icon-shape bg-gray-900 shadow-dark shadow text-center border-radius-lg">
                    <i class="material-symbols-rounded opacity-10">leaderboard</i>
                  </div>
                </div>
              </div>
              <hr class="dark horizontal my-0">
              <div class="card-footer p-2 ps-3">
              </div>
            </div>
        </a>
        @endforeach
      </div>
            <div class="row mt-5 mb-4">
                <div class="col-12">
                    <div class="card z-index-2" id="tour-dashboard-financial-summary">
                        <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2 bg-transparent">
                            <div class="chart-neo-surface py-3 pe-1 ps-1">
                                <div class="chart">
                                    <canvas id="financial-chart" class="chart-canvas" height="110"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <h6 class="mb-0">Resumen financiero avanzado</h6>
                            <p class="text-sm mb-0">Comparativa mensual de cobros, gastos y utilidad estimada.</p>
                            <div class="d-flex justify-content-between mt-2 flex-wrap gap-3">
                                <div>
                                    <p class="text-sm mb-1 text-uppercase font-weight-bold">Utilidad estimada</p>
                                    <h4 class="mb-0">${{ number_format((float) ($financialSummary['estimated_profit'] ?? 0), 2) }}</h4>
                                    <small class="text-muted">Margen: {{ number_format((float) ($financialSummary['estimated_margin'] ?? 0), 2) }}%</small>
                                </div>
                                <div>
                                    <p class="text-sm mb-1 text-uppercase font-weight-bold">Tendencia mensual</p>
                                    <h4 class="mb-0 {{ (float) ($monthlyTrend['delta'] ?? 0) >= 0 ? 'text-success' : 'text-danger' }}">
                                        {{ (float) ($monthlyTrend['delta'] ?? 0) >= 0 ? '+' : '' }}${{ number_format((float) ($monthlyTrend['delta'] ?? 0), 2) }}
                                    </h4>
                                    <small class="text-muted">
                                        @if(!is_null($monthlyTrend['delta_percent'] ?? null))
                                            {{ (float) ($monthlyTrend['delta_percent'] ?? 0) >= 0 ? '+' : '' }}{{ number_format((float) ($monthlyTrend['delta_percent'] ?? 0), 2) }}% vs mes anterior
                                        @else
                                            Sin base comparativa
                                        @endif
                                    </small>
                                </div>
                                <div>
                                    <p class="text-sm mb-1 text-uppercase font-weight-bold">Cobrado del mes</p>
                                    <h4 class="mb-0">${{ number_format((float) ($financialSummary['collected'] ?? 0), 2) }}</h4>
                                </div>
                                <div>
                                    <p class="text-sm mb-1 text-uppercase font-weight-bold">Cuentas por cobrar</p>
                                    <h4 class="mb-0">${{ number_format((float) ($financialSummary['receivables'] ?? 0), 2) }}</h4>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
      <div class="row mt-4">
                <div class="col-lg-4 col-md-6 mt-4 mb-4">
                    <div class="card z-index-2" style="min-height: 18.3rem;">
                    <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                        <div class="bg-gradient-dark shadow-dark border-radius-lg pt-4 pb-3 d-flex justify-content-between align-items-center">
                            <h6 class="text-white ps-3">Productos con menor stock</h6>
                        </div>
                    </div> 
                    <div class="card-body pt-0">
                        <div class="table-responsive">
                        <table class="table align-items-center mb-0">
                            <thead class="">
                            <tr>
                                <th>Nombre</th>
                                <th>Cantidad</th>
                                <th>Acciones</th>
                            </tr>
                            </thead>
                            <tbody class="">
                            @foreach($lowStockProducts as $product)
                                <tr>
                                <td>{{ $product->name }}</td>
                                <td class="text-center">{{ $product->total_stock }}</td>
                                <td>
                                    <a href="/products" class="text-secondary font-weight-bold text-xs toggle-status-btn">Ver Detalles</a>
                                </td>
                                </tr>
                            @endforeach
                            </tbody>
                        </table>
                        </div>
                    </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6 mt-4 mb-4">
                    <div class="card z-index-2  ">
                        <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2 bg-transparent">
                            <div class="chart-neo-surface py-3 pe-1 ps-1">
                                <div class="chart">
                                    <canvas id="chart-line" class="chart-canvas" height="170"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <h6 class="mb-0 ">Ventas Mensuales</h6>
                            <p class="text-sm ">Ventas de los ultimos meses.</p>
                            <hr class="dark horizontal">
                            <p class="text-sm mb-1 text-uppercase font-weight-bold">Ventas del mes</p>
                            <h4 class="mb-0">${{ number_format((float) ($financialSummary['sales'] ?? 0), 2) }}</h4>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 mt-4 mb-3">
                    <div class="card z-index-2">
                        <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2 bg-transparent">
                            <div class="chart-neo-surface py-3 pe-1 ps-1">
                                <div class="chart">
                                    <canvas id="chart-line-tasks" class="chart-canvas" height="170"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <h6 class="mb-0">Top gastos por categoría</h6>
                            <p class="text-sm">Categorías con mayor egreso acumulado.</p>
                            <hr class="horizontal">
                            <p class="text-sm mb-1 text-uppercase font-weight-bold">Gastos del mes</p>
                            <h4 class="mb-0">${{ number_format((float) ($financialSummary['expenses'] ?? 0), 2) }}</h4>
                        </div>
                    </div>
                </div>
            </div>
            <div class="row mb-4">
                <div class="col-lg-8 col-md-6 mb-md-0 mb-4">
                    <div class="card mt-4" id="tour-dashboard-sales-table">
                        <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2">
                            <div class="bg-gradient-dark shadow-dark border-radius-lg pt-4 pb-3 d-flex justify-content-between align-items-center">
                                <h6 class="text-white text-capitalize ps-3">VENTAS REALIZADAS</h6>
                            <div class="py-1 px-3 text-end ">
                                <a href="/sales-orders" class="mx-4 text-white">Ver Más ></a>
                                <a class="text-white" href="/sales">
                                    <i class="material-symbols-rounded text-sm">add</i>&nbsp;&nbsp;Realizar Venta
                                </a>
                            </div>
                            </div>
                        </div> 
                        <div class="card-body px-0 pb-2">
                            <div class="table-responsive">
                            <table class="table align-items-center mb-0">
                        <thead>
                            <tr>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Orden</th>
                                <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Usuario</th>
                                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Fecha</th>
                                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Total</th>
                                <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Estatus</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($salesOrders as $order)
                            <tr>
                                <td>
                                    <div class="d-flex px-2 py-1">
                                        <div class="avatar avatar-sm me-3">
                                        </div>
                                        <div class="d-flex flex-column justify-content-center">
                                            <h6 class="mb-0 text-sm">Orden #{{ $order->id }}</h6>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="text-xs font-weight-bold">
                                        @if ($order->user)
                                            {{ $order->user->name }}
                                        @else
                                            Usuario no asignado
                                        @endif
                                    </span>
                                </td>
                                <td class="align-middle text-center text-sm">
                                    <span class="text-xs font-weight-bold">{{ $order->date }}</span>
                                </td>
                                <td class="align-middle text-center text-sm">
                                    <span class="text-xs font-weight-bold">${{ number_format($order->details->sum('price'), 2) }}</span>
                                </td>
                                <td class="align-middle text-center text-sm">
                                <span class="badge badge-sm
                                    {{ $order->status == '0' ? 'bg-gradient-warning' :
                                        ($order->status == '1' ? 'bg-gradient-success' :
                                        ($order->status == '2' ? 'bg-gradient-danger' : '') ) }}">
                                    {{ $order->status == 0 ? 'En Proceso' :
                                        ($order->status == 1 ? 'Aprobado' :
                                        ($order->status == 2 ? 'Negado' : '')) }}
                                </span>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-4 col-md-6">
                    <div class="card h-100" id="tour-dashboard-purchase-timeline">
                        <div class="card-header pb-0">
                          <div class="pb-0 px-3 d-flex justify-content-between align-items-center">
                            <div class="d-flex align-items-center">
                              <h6 class="mb-0">Compras Realizadas</h6>
                              <a href="/purchase-orders" class="mx-4 btn-outline-black">Ver Más ></a>
                            </div>
                            <a class="" href="/purchase">
                              <i class="material-symbols-rounded text-sm">add</i>&nbsp;&nbsp;Realizar Compra
                            </a>
                          </div>
                        </div>
                        <div class="card-body p-3">
                            <div class="timeline timeline-one-side" id="timeline">
                                @foreach($purchaseOrders as $order)
                                <div class="timeline-block mb-1">
                                    <span class="timeline-step">
                                      <i class="material-symbols-rounded text-success opacity-10" style="font-size: 30px">add_shopping_cart</i>
                                    </span>
                                    <div class="timeline-content">
                                        <h6 class="text-dark text-sm font-weight-bold mb-0">
                                            Orden de Compra #{{ $order->id }}
                                        </h6>
                                        <p class="text-secondary font-weight-bold text-xs mt-1 mb-0">
                                            Proveedor: {{ $order->provider_display_name }} 
                                        </p>
                                        <p class="text-secondary font-weight-bold text-xs mt-1 mb-0">
                                            Fecha: {{ $order->date }}
                                        </p>
                                </div>
                                @endforeach
                            </div>
                        </div>
                    </div>
                </div>
            </div>
    </div>
    @endif
    
</div>

    </div>
@endsection
@push('scripts')

  <!--   Core JS Files   -->
  <script src="../assets/js/core/popper.min.js"></script>
  <script src="../assets/js/core/bootstrap.min.js"></script>
  <script src="../assets/js/plugins/perfect-scrollbar.min.js"></script>
  <script src="../assets/js/plugins/smooth-scrollbar.min.js"></script>
  <script src="../assets/js/plugins/chartjs.min.js"></script>
  <script>
        // Asegúrate de que las variables estén bien formateadas para JS
        const monthlySalesFormatted = @json($monthlySalesFormatted); // Podría ser un número o string
        const monthlyExpensesFormatted = @json($monthlyExpensesFormatted);
        const monthlySalesAmountFormatted = @json($monthlySalesAmountFormatted);
        const monthlyCollectedFormatted = @json($monthlyCollectedFormatted);
        const monthlyProfitTrendFormatted = @json($monthlyProfitTrendFormatted);
        const months = @json($months); // Por ejemplo: [50, 20, 10, 22, 50, 10, 40]
        const topProductNames = @json($topProductNames); // ["Producto A", "Producto B", ...]
        const topProductSales = @json($topProductSales); // [120, 90, 70, 50, 30]
        const topExpenseCategoryLabels = @json($topExpenseCategoryLabels);
        const topExpenseCategoryTotals = @json($topExpenseCategoryTotals);
        const dashboardStoreUrlInput = document.getElementById('dashboardStoreUrlInput');
        const dashboardCopyStoreUrlBtn = document.getElementById('dashboardCopyStoreUrlBtn');
        const dashboardTourButton = document.getElementById('startDashboardTourBtn');
        const dashboardPlanDaysRemaining = {{ isset($currentPlanDaysRemaining) && !is_null($currentPlanDaysRemaining) ? (int) $currentPlanDaysRemaining : 'null' }};

        const chartTheme = {
            text: '#334155',
            mutedText: '#64748b',
            grid: 'rgba(148, 163, 184, 0.24)',
            tooltipBg: '#0f172a',
            tooltipText: '#e2e8f0',
            linePrimary: '#6366f1',
            lineSecondary: '#06b6d4',
            lineSuccess: '#22c55e',
            lineWarning: '#f59e0b',
            barA: '#8b5cf6',
            barB: '#6366f1',
            barC: '#06b6d4',
            barD: '#22c55e',
            barE: '#f59e0b'
        };

        const baseChartPlugins = {
            legend: {
                labels: {
                    color: chartTheme.text,
                    usePointStyle: true,
                    pointStyle: 'circle',
                    boxWidth: 8,
                    boxHeight: 8,
                    padding: 16,
                    font: {
                        size: 12,
                        family: 'Inter, Roboto, sans-serif',
                        weight: '600'
                    }
                }
            },
            tooltip: {
                backgroundColor: chartTheme.tooltipBg,
                titleColor: '#ffffff',
                bodyColor: chartTheme.tooltipText,
                borderColor: 'rgba(255,255,255,0.14)',
                borderWidth: 1,
                cornerRadius: 10,
                padding: 12,
                displayColors: true,
                boxPadding: 5,
            }
        };

        const makeAreaGradient = (ctx, from, to, alpha = 0.24) => {
            const gradient = ctx.createLinearGradient(0, 0, 0, 240);
            gradient.addColorStop(0, from.replace(')', `, ${alpha})`).replace('rgb', 'rgba'));
            gradient.addColorStop(1, to.replace(')', ', 0)').replace('rgb', 'rgba'));
            return gradient;
        };

        const showDashboardToast = (message, type = 'warning') => {
            const container = document.getElementById('dashboardToastContainer');
            if (!container || !message) return;

            const toast = document.createElement('div');
            toast.className = `dashboard-toast ${type}`;
            toast.textContent = message;
            container.appendChild(toast);
            requestAnimationFrame(() => toast.classList.add('show'));

            setTimeout(() => {
                toast.classList.remove('show');
                setTimeout(() => toast.remove(), 220);
            }, 3600);
        };

        const copyText = async (text) => {
            if (navigator.clipboard?.writeText) {
                await navigator.clipboard.writeText(text);
                return true;
            }

            const textArea = document.createElement('textarea');
            textArea.value = text;
            textArea.style.position = 'fixed';
            textArea.style.opacity = '0';
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            const copied = document.execCommand('copy');
            document.body.removeChild(textArea);

            return copied;
        };

        if (dashboardCopyStoreUrlBtn && dashboardStoreUrlInput) {
            dashboardCopyStoreUrlBtn.addEventListener('click', async () => {
                const icon = dashboardCopyStoreUrlBtn.querySelector('.material-symbols-rounded');
                const defaultIcon = dashboardCopyStoreUrlBtn.dataset.iconDefault || 'content_copy';
                const copied = await copyText(dashboardStoreUrlInput.value || '');
                if (icon) {
                    icon.textContent = copied ? 'check' : 'error';
                }
                setTimeout(() => {
                    if (icon) {
                        icon.textContent = defaultIcon;
                    }
                }, 1400);
            });
        }

        if (dashboardPlanDaysRemaining !== null) {
            setTimeout(() => {
                if (dashboardPlanDaysRemaining < 0) {
                    showDashboardToast(`Tu plan está vencido hace ${Math.abs(dashboardPlanDaysRemaining)} días. Registra el pago en Gestión de Tienda.`, 'error');
                    return;
                }

                if (dashboardPlanDaysRemaining <= 7) {
                    showDashboardToast(`Tu plan vence en ${dashboardPlanDaysRemaining} días. Registra tu pago con anticipación en Gestión de Tienda.`, 'warning');
                }
            }, 250);
        }

        const startDashboardTour = () => {
            const driverFactory = window.driver?.js?.driver || window.driver;
            if (typeof driverFactory !== 'function') {
                showDashboardToast('No se pudo cargar el asistente guiado. Recarga la pagina e intenta otra vez.', 'error');
                return;
            }

            const dashboardSteps = [
                {
                    element: '#tour-dashboard-headline',
                    popover: {
                        title: 'Bienvenido a tu Dashboard',
                        description: 'Aqui ves un resumen rapido del estado de tu tienda y accesos clave.',
                        side: 'bottom',
                        align: 'start'
                    }
                },
                {
                    element: '#tour-dashboard-store-url',
                    popover: {
                        title: 'Enlace publico de tu tienda',
                        description: 'Desde aqui puedes abrir tu tienda online o copiar su URL para compartirla.',
                        side: 'bottom',
                        align: 'start'
                    }
                },
                {
                    element: '#tour-dashboard-financial-summary',
                    popover: {
                        title: 'Resumen financiero',
                        description: 'Compara cobros, gastos y utilidad para entender el rendimiento mensual.',
                        side: 'top',
                        align: 'start'
                    }
                },
                {
                    element: '#tour-dashboard-sales-table',
                    popover: {
                        title: 'Ventas realizadas',
                        description: 'Consulta ordenes recientes y entra rapido a crear una nueva venta.',
                        side: 'top',
                        align: 'start'
                    }
                },
                {
                    element: '#tour-dashboard-purchase-timeline',
                    popover: {
                        title: 'Compras realizadas',
                        description: 'Revisa actividad de compras y entra directo al modulo de abastecimiento.',
                        side: 'left',
                        align: 'start'
                    }
                }
            ].filter((step) => document.querySelector(step.element));

            if (!dashboardSteps.length) {
                showDashboardToast('No hay elementos disponibles para el recorrido en esta vista.', 'warning');
                return;
            }

            const tour = driverFactory({
                showProgress: true,
                allowClose: true,
                overlayColor: 'rgba(15, 23, 42, 0.72)',
                nextBtnText: 'Siguiente',
                prevBtnText: 'Anterior',
                doneBtnText: 'Listo',
                showButtons: ['next', 'previous', 'close'],
                steps: dashboardSteps,
            });

            tour.drive();
        };

        if (dashboardTourButton) {
            dashboardTourButton.addEventListener('click', startDashboardTour);

            const dashboardTourKey = 'shopix_dashboard_tour_seen_v1';
            if (localStorage.getItem(dashboardTourKey) !== '1') {
                setTimeout(() => {
                    startDashboardTour();
                    localStorage.setItem(dashboardTourKey, '1');
                }, 700);
            }
        }

        var financialCtx = document.getElementById("financial-chart")?.getContext("2d");

        if (financialCtx) {
            const collectedFill = makeAreaGradient(financialCtx, 'rgb(99, 102, 241)', 'rgb(99, 102, 241)', 0.16);
            const expensesFill = makeAreaGradient(financialCtx, 'rgb(6, 182, 212)', 'rgb(6, 182, 212)', 0.14);
            const profitFill = makeAreaGradient(financialCtx, 'rgb(34, 197, 94)', 'rgb(34, 197, 94)', 0.12);

            new Chart(financialCtx, {
                type: "line",
                data: {
                    labels: months,
                    datasets: [
                        {
                            label: "Cobrado",
                            data: monthlyCollectedFormatted,
                            borderColor: chartTheme.linePrimary,
                            backgroundColor: collectedFill,
                            borderWidth: 2.6,
                            pointRadius: 0,
                            pointHoverRadius: 4,
                            tension: 0.36,
                            fill: true,
                        },
                        {
                            label: "Gastos",
                            data: monthlyExpensesFormatted,
                            borderColor: chartTheme.lineSecondary,
                            backgroundColor: expensesFill,
                            borderWidth: 2.4,
                            pointRadius: 0,
                            pointHoverRadius: 4,
                            tension: 0.34,
                            fill: true,
                        },
                        {
                            label: "Utilidad estimada",
                            data: monthlyProfitTrendFormatted,
                            borderColor: chartTheme.lineSuccess,
                            backgroundColor: profitFill,
                            borderWidth: 2.4,
                            pointRadius: 0,
                            pointHoverRadius: 4,
                            tension: 0.35,
                            fill: true,
                        }
                    ],
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        ...baseChartPlugins
                    },
                    scales: {
                        y: {
                            grid: {
                                color: chartTheme.grid,
                                drawBorder: false
                            },
                            ticks: {
                                color: chartTheme.mutedText,
                                padding: 8
                            }
                        },
                        x: {
                            grid: {
                                display: false
                            },
                            ticks: {
                                color: chartTheme.mutedText,
                                padding: 8
                            }
                        }
                    }
                }
            });
        }

        var ctx2 = document.getElementById("chart-line")?.getContext("2d");

        if (ctx2) {
        const salesArea = makeAreaGradient(ctx2, 'rgb(99, 102, 241)', 'rgb(99, 102, 241)', 0.22);
        new Chart(ctx2, {
            type: "line",
            data: {
                labels: months,
                datasets: [{
                    label: "Ventas mensuales (USD)",
                    tension: 0.35,
                    pointRadius: 0,
                    pointHoverRadius: 4,
                    pointBackgroundColor: chartTheme.linePrimary,
                    pointBorderColor: "transparent",
                    borderColor: chartTheme.linePrimary,
                    borderWidth: 2.8,
                    backgroundColor: salesArea,
                    fill: true,
                    data: monthlySalesAmountFormatted,
                    maxBarThickness: 6

                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    ...baseChartPlugins,
                    legend: {
                        display: false,
                    },
                },
                interaction: {
                    intersect: false,
                    mode: 'index',
                },
                scales: {
                    y: {
                        grid: {
                            drawBorder: false,
                            display: true,
                            drawOnChartArea: true,
                            drawTicks: false,
                            borderDash: [4, 4],
                            color: chartTheme.grid
                        },
                        ticks: {
                            display: true,
                            color: chartTheme.mutedText,
                            padding: 10,
                            font: {
                                size: 12,
                                weight: 500,
                                family: "Inter, Roboto, sans-serif",
                                style: 'normal',
                                lineHeight: 1.5
                            },
                        }
                    },
                    x: {
                        grid: {
                            drawBorder: false,
                            display: false,
                            drawOnChartArea: false,
                            drawTicks: false,
                            borderDash: [5, 5]
                        },
                        ticks: {
                            display: true,
                            color: chartTheme.mutedText,
                            padding: 10,
                            font: {
                                size: 12,
                                weight: 500,
                                family: "Inter, Roboto, sans-serif",
                                style: 'normal',
                                lineHeight: 1.5
                            },
                        }
                    },
                },
            },
        });
        }

        var ctx3 = document.getElementById("chart-line-tasks")?.getContext("2d");

        var maxLabelLength = 14;

        // Guardamos etiquetas truncadas para mostrar en el eje X
        var truncatedLabels = topExpenseCategoryLabels.map(name => 
            name.length > maxLabelLength ? name.substring(0, maxLabelLength) + "…" : name
        );

        // Usamos el original para el tooltip
        var originalLabels = topExpenseCategoryLabels;
        var expensesByCategory = topExpenseCategoryTotals;

        if (ctx3) {
        const expensePalette = [chartTheme.barA, chartTheme.barB, chartTheme.barC, chartTheme.barD, chartTheme.barE];
        new Chart(ctx3, {
            type: "bar",
            data: {
                labels: truncatedLabels,
                datasets: [{
                    label: "Gastos",
                    data: expensesByCategory,
                    backgroundColor: expensePalette,
                    borderColor: expensePalette,
                    borderWidth: 1,
                    borderRadius: 10,
                    maxBarThickness: 16
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    ...baseChartPlugins,
                    legend: {
                        display: false
                    },
                    tooltip: {
                        ...baseChartPlugins.tooltip,
                        enabled: true,
                        callbacks: {
                            label: function (context) {
                                const index = context.dataIndex;
                                return `${originalLabels[index]}: $${Number(context.raw || 0).toFixed(2)}`;
                            }
                        }
                    }
                },
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: {
                            drawBorder: false,
                            display: true,
                            drawOnChartArea: true,
                            drawTicks: false,
                            borderDash: [4, 4],
                            color: chartTheme.grid
                        },
                        ticks: {
                            display: true,
                            color: chartTheme.mutedText,
                            padding: 10,
                            font: {
                                size: 12,
                                weight: 500,
                                family: "Inter, Roboto, sans-serif",
                                style: 'normal',
                                lineHeight: 1.5
                            }
                        }
                    },
                    x: {
                        grid: {
                            drawBorder: false,
                            display: false,
                            drawOnChartArea: false,
                            drawTicks: false,
                            borderDash: [5, 5]
                        },
                        ticks: {
                            display: true,
                            color: chartTheme.mutedText,
                            padding: 10,
                            font: {
                                size: 12,
                                weight: 500,
                                family: "Inter, Roboto, sans-serif",
                                style: 'normal',
                                lineHeight: 1.5
                            }
                        }
                    }
                }
            }
        });
        }

    </script>
@endpush
