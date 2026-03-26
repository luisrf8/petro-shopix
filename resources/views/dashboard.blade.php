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
        </style>
        <div id="dashboardToastContainer" class="dashboard-toast-stack"></div>
    <div class="container-fluid py-2">
      <div class="row">
        <div class="ms-3">
          <h3 class="mb-0 h4 font-weight-bolder">{{ $user->name }}</h3>
          <p class="mb-4">
            Datos y Análisis.
          </p>
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

                @if(!empty($tenantPublicUrl))
                <div class="col-12 mb-3">
                    <div class="card">
                        <div class="card-body py-3">
                            <label class="form-label mb-2">URL pública de tu tienda</label>
                            <div class="input-group">
                                <input type="text" class="form-control p-2 border border-radius-lg" id="dashboardStoreUrlInput" value="{{ $tenantPublicUrl }}" readonly>
                                <a href="{{ $tenantPublicUrl }}" target="_blank" rel="noopener" class="btn btn-outline-dark url-icon-action-btn" aria-label="Abrir tienda" title="Abrir tienda">
                                    <i class="material-symbols-rounded">open_in_new</i>
                                </a>
                                <button type="button" class="btn btn-outline-secondary url-icon-action-btn" id="dashboardCopyStoreUrlBtn" aria-label="Copiar enlace" title="Copiar enlace" data-icon-default="content_copy">
                                    <i class="material-symbols-rounded">content_copy</i>
                                </button>
                            </div>
                        </div>
                    </div>
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
                            <div class="bg-gradient-dark shadow-dark border-radius-lg py-3 pe-1">
                                <div class="chart">
                                    <canvas id="chart-line" class="chart-canvas" height="170"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <h6 class="mb-0 ">Ventas Mensuales</h6>
                            <p class="text-sm ">Ventas de los ultimos meses.</p>
                            <hr class="dark horizontal">

                        </div>
                    </div>
                </div>
                <div class="col-lg-4 mt-4 mb-3">
                    <div class="card z-index-2">
                        <div class="card-header p-0 position-relative mt-n4 mx-3 z-index-2 bg-transparent">
                            <div class="bg-gradient-dark shadow-dark border-radius-lg py-3 pe-1">
                                <div class="chart">
                                    <canvas id="chart-line-tasks" class="chart-canvas" height="170"></canvas>
                                </div>
                            </div>
                        </div>
                        <div class="card-body">
                            <h6 class="mb-0">Productos mas vendidos</h6>
                            <p class="text-sm">Análisis general.</p>
                            <hr class="horizontal">
                        </div>
                    </div>
                </div>
            </div>
            <div class="row mb-4">
                <div class="col-lg-8 col-md-6 mb-md-0 mb-4">
                    <div class="card mt-4">
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
                    <div class="card h-100">
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
                                            Proveedor: {{ $order->provider_id ?? 'No asignado' }} 
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
        const months = @json($months); // Por ejemplo: [50, 20, 10, 22, 50, 10, 40]
        const topProductNames = @json($topProductNames); // ["Producto A", "Producto B", ...]
        const topProductSales = @json($topProductSales); // [120, 90, 70, 50, 30]
        const dashboardStoreUrlInput = document.getElementById('dashboardStoreUrlInput');
        const dashboardCopyStoreUrlBtn = document.getElementById('dashboardCopyStoreUrlBtn');
        const dashboardPlanDaysRemaining = {{ isset($currentPlanDaysRemaining) && !is_null($currentPlanDaysRemaining) ? (int) $currentPlanDaysRemaining : 'null' }};

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

        var ctx2 = document.getElementById("chart-line").getContext("2d");

        new Chart(ctx2, {
            type: "line",
            data: {
                labels: months,
                datasets: [{
                    label: "Ventas mensuales",
                    tension: 0,
                    borderWidth: 0,
                    pointRadius: 5,
                    pointBackgroundColor: "rgba(255, 255, 255, .8)",
                    pointBorderColor: "transparent",
                    borderColor: "rgba(255, 255, 255, .8)",
                    borderColor: "rgba(255, 255, 255, .8)",
                    borderWidth: 4,
                    backgroundColor: "transparent",
                    fill: true,
                    data: monthlySalesFormatted,
                    maxBarThickness: 6

                }],
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false,
                    }
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
                            borderDash: [5, 5],
                            color: 'rgba(255, 255, 255, .2)'
                        },
                        ticks: {
                            display: true,
                            color: '#f8f9fa',
                            padding: 10,
                            font: {
                                size: 14,
                                weight: 300,
                                family: "Roboto",
                                style: 'normal',
                                lineHeight: 2
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
                            color: '#f8f9fa',
                            padding: 10,
                            font: {
                                size: 14,
                                weight: 300,
                                family: "Roboto",
                                style: 'normal',
                                lineHeight: 2
                            },
                        }
                    },
                },
            },
        });

        var ctx3 = document.getElementById("chart-line-tasks").getContext("2d");

        var maxLabelLength = 10;

        // Guardamos etiquetas truncadas para mostrar en el eje X
        var truncatedLabels = topProductNames.map(name => 
            name.length > maxLabelLength ? name.substring(0, maxLabelLength) + "…" : name
        );

        // Usamos el original para el tooltip
        var originalLabels = topProductNames;
        var productSales = topProductSales;

        new Chart(ctx3, {
            type: "bar",
            data: {
                labels: truncatedLabels,
                datasets: [{
                    label: "Ventas",
                    data: productSales,
                    backgroundColor: [
                        "rgba(255, 255, 255, .8)",
                        "rgba(255, 255, 255, .8)",
                        "rgba(255, 255, 255, .8)",
                        "rgba(255, 255, 255, .8)",
                        "rgba(255, 255, 255, .8)"
                    ],
                    borderColor: [
                        "rgba(255, 255, 255, .8)",
                        "rgba(255, 255, 255, .8)",
                        "rgba(255, 255, 255, .8)",
                        "rgba(255, 255, 255, .8)",
                        "rgba(255, 255, 255, .8)"
                    ],
                    borderWidth: 1,
                    maxBarThickness: 6
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        display: false
                    },
                    tooltip: {
                        enabled: true,
                        callbacks: {
                            label: function (context) {
                                const index = context.dataIndex;
                                return `${originalLabels[index]}: ${context.raw} ventas`;
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
                            borderDash: [5, 5],
                            color: 'rgba(255, 255, 255, .2)'
                        },
                        ticks: {
                            display: true,
                            color: '#f8f9fa',
                            padding: 10,
                            font: {
                                size: 14,
                                weight: 300,
                                family: "Roboto",
                                style: 'normal',
                                lineHeight: 2
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
                            color: '#f8f9fa',
                            padding: 10,
                            font: {
                                size: 14,
                                weight: 300,
                                family: "Roboto",
                                style: 'normal',
                                lineHeight: 2
                            }
                        }
                    }
                }
            }
        });

    </script>
@endpush
