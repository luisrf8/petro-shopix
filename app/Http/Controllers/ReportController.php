<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\MaterialPackage;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use App\Models\StoreExpense;
use App\Models\User;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        $expenseCategories = StoreExpense::query()
            ->where('tenant_id', $user->tenant_id)
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->orderBy('category')
            ->distinct()
            ->pluck('category')
            ->values();

        return view('reports.index', [
            'expenseCategories' => $expenseCategories,
        ]);
    }

    public function topSellingProductsPdf(Request $request)
    {
        $user = auth()->user();
        [$startDate, $endDate] = $this->resolveDateRange($request);

        $rows = ProductVariant::query()
            ->join('products', 'products.id', '=', 'product_variants.product_id')
            ->join('sales_order_details', 'sales_order_details.product_variant_id', '=', 'product_variants.id')
            ->join('sales_orders', 'sales_orders.id', '=', 'sales_order_details.sales_order_id')
            ->where('sales_orders.tenant_id', $user->tenant_id)
            ->whereBetween('sales_orders.date', [$startDate->toDateString(), $endDate->toDateString()])
            ->groupBy('product_variants.id', 'products.name', 'product_variants.size')
            ->selectRaw('products.name as product_name, product_variants.size as variant_name, SUM(sales_order_details.quantity) as total_quantity, SUM(sales_order_details.amount) as total_amount')
            ->orderByDesc('total_quantity')
            ->limit(50)
            ->get();

        $summary = [
            'total_units' => (int) $rows->sum('total_quantity'),
            'total_amount' => (float) $rows->sum('total_amount'),
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];

        return $this->renderPdf(
            'reports.pdf.top-selling-products',
            compact('rows', 'summary'),
            'reporte_productos_mas_vendidos'
        );
    }

    public function topSellingProductsExcel(Request $request)
    {
        $user = auth()->user();
        [$startDate, $endDate] = $this->resolveDateRange($request);

        $rows = ProductVariant::query()
            ->join('products', 'products.id', '=', 'product_variants.product_id')
            ->join('sales_order_details', 'sales_order_details.product_variant_id', '=', 'product_variants.id')
            ->join('sales_orders', 'sales_orders.id', '=', 'sales_order_details.sales_order_id')
            ->where('sales_orders.tenant_id', $user->tenant_id)
            ->whereBetween('sales_orders.date', [$startDate->toDateString(), $endDate->toDateString()])
            ->groupBy('product_variants.id', 'products.name', 'product_variants.size')
            ->selectRaw('products.name as product_name, product_variants.size as variant_name, SUM(sales_order_details.quantity) as total_quantity, SUM(sales_order_details.amount) as total_amount')
            ->orderByDesc('total_quantity')
            ->limit(50)
            ->get();

        $csvRows = $rows->map(function ($row) {
            return [
                $row->product_name,
                $row->variant_name ?: 'N/A',
                (string) $row->total_quantity,
                number_format((float) $row->total_amount, 2, '.', ''),
            ];
        })->all();

        return $this->downloadCsv(
            'reporte_productos_mas_vendidos',
            ['Producto', 'Variante', 'Unidades', 'Monto_USD'],
            $csvRows
        );
    }

    public function inventoryEntriesPdf(Request $request)
    {
        $user = auth()->user();
        [$startDate, $endDate] = $this->resolveDateRange($request);

        $entries = PurchaseOrder::with(['warehouse', 'provider', 'detalles.productVariant.product'])
            ->where('tenant_id', $user->tenant_id)
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();

        $summary = [
            'orders' => (int) $entries->count(),
            'total_items' => (int) $entries->sum(fn ($order) => $order->detalles->sum('quantity')),
            'total_amount' => (float) $entries->sum(fn ($order) => $order->detalles->sum('amount')),
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];

        return $this->renderPdf(
            'reports.pdf.inventory-entries',
            compact('entries', 'summary'),
            'reporte_entradas_inventario'
        );
    }

    public function inventoryEntriesExcel(Request $request)
    {
        $user = auth()->user();
        [$startDate, $endDate] = $this->resolveDateRange($request);

        $entries = PurchaseOrder::with(['warehouse', 'provider', 'detalles'])
            ->where('tenant_id', $user->tenant_id)
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();

        $csvRows = $entries->map(function ($order) {
            return [
                (string) $order->id,
                (string) $order->date,
                (string) ($order->warehouse->name ?? 'N/A'),
                (string) ($order->provider->name ?? $order->provider_name ?? 'N/A'),
                (string) $order->detalles->sum('quantity'),
                number_format((float) $order->detalles->sum('amount'), 2, '.', ''),
            ];
        })->all();

        return $this->downloadCsv(
            'reporte_entradas_inventario',
            ['Orden_ID', 'Fecha', 'Almacen', 'Proveedor', 'Items', 'Monto_USD'],
            $csvRows
        );
    }

    public function salesManagementPdf(Request $request)
    {
        $user = auth()->user();
        [$startDate, $endDate] = $this->resolveDateRange($request);

        $orders = SalesOrder::with(['user', 'details', 'payments'])
            ->where('tenant_id', $user->tenant_id)
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();

        $summary = [
            'orders' => (int) $orders->count(),
            'total_items' => (int) $orders->sum(fn ($order) => $order->details->sum('quantity')),
            'total_amount' => (float) $orders->sum(fn ($order) => $order->details->sum('amount')),
            'total_paid' => (float) $orders->sum(fn ($order) => $order->payments->where('status', 1)->sum('amount')),
            'start_date' => $startDate,
            'end_date' => $endDate,
        ];

        return $this->renderPdf(
            'reports.pdf.sales-management',
            compact('orders', 'summary'),
            'reporte_gestion_ventas'
        );
    }

    public function salesManagementExcel(Request $request)
    {
        $user = auth()->user();
        [$startDate, $endDate] = $this->resolveDateRange($request);

        $orders = SalesOrder::with(['user', 'details', 'payments'])
            ->where('tenant_id', $user->tenant_id)
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();

        $csvRows = $orders->map(function ($order) {
            $status = $order->status == 0
                ? 'En Proceso'
                : ($order->status == 1 ? 'Aprobado' : ($order->status == 2 ? 'Negado' : 'N/A'));

            return [
                (string) $order->id,
                (string) $order->date,
                (string) ($order->user->name ?? 'N/A'),
                $status,
                (string) $order->details->sum('quantity'),
                number_format((float) $order->details->sum('amount'), 2, '.', ''),
                number_format((float) $order->payments->where('status', 1)->sum('amount'), 2, '.', ''),
            ];
        })->all();

        return $this->downloadCsv(
            'reporte_gestion_ventas',
            ['Orden_ID', 'Fecha', 'Cliente', 'Estado', 'Items', 'Total_USD', 'Cobrado_USD'],
            $csvRows
        );
    }

    public function inventoryTotalPdf()
    {
        $user = auth()->user();

        $rows = ProductVariant::with(['product.category'])
            ->whereHas('product', function ($query) use ($user) {
                $query->where('tenant_id', $user->tenant_id);
            })
            ->orderByDesc('stock')
            ->get();

        $summary = [
            'variants' => (int) $rows->count(),
            'total_stock' => (int) $rows->sum('stock'),
            'inventory_value' => (float) $rows->sum(function ($variant) {
                return (float) $variant->stock * (float) $variant->price;
            }),
            'generated_at' => now(),
        ];

        return $this->renderPdf(
            'reports.pdf.inventory-total',
            compact('rows', 'summary'),
            'reporte_inventario_total'
        );
    }

    public function inventoryTotalExcel()
    {
        $user = auth()->user();

        $rows = ProductVariant::with(['product.category'])
            ->whereHas('product', function ($query) use ($user) {
                $query->where('tenant_id', $user->tenant_id);
            })
            ->orderByDesc('stock')
            ->get();

        $csvRows = $rows->map(function ($row) {
            return [
                (string) ($row->product->name ?? 'N/A'),
                (string) ($row->product->category->name ?? 'N/A'),
                (string) ($row->size ?: 'N/A'),
                (string) $row->stock,
                number_format((float) $row->price, 2, '.', ''),
                number_format((float) $row->stock * (float) $row->price, 2, '.', ''),
            ];
        })->all();

        return $this->downloadCsv(
            'reporte_inventario_total',
            ['Producto', 'Categoria', 'Variante', 'Stock', 'Precio_USD', 'Valor_USD'],
            $csvRows
        );
    }

    public function systemModulesPdf(Request $request)
    {
        $user = auth()->user();
        [$startDate, $endDate] = $this->resolveDateRange($request);

        $modules = [
            [
                'name' => 'Catalogo',
                'metrics' => [
                    'Categorias activas' => Category::where('tenant_id', $user->tenant_id)->where('is_active', 1)->count(),
                    'Productos activos' => Product::where('tenant_id', $user->tenant_id)->where('is_active', 1)->count(),
                    'Variantes totales' => ProductVariant::whereHas('product', function ($query) use ($user) {
                        $query->where('tenant_id', $user->tenant_id);
                    })->count(),
                ],
            ],
            [
                'name' => 'Ventas',
                'metrics' => [
                    'Ordenes en rango' => SalesOrder::where('tenant_id', $user->tenant_id)->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])->count(),
                    'Monto vendido' => SalesOrder::where('tenant_id', $user->tenant_id)->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])->with('details')->get()->sum(fn ($order) => $order->details->sum('amount')),
                    'Pagos aprobados' => SalesOrder::where('tenant_id', $user->tenant_id)->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])->with('payments')->get()->sum(fn ($order) => $order->payments->where('status', 1)->sum('amount')),
                ],
            ],
            [
                'name' => 'Inventario y Compras',
                'metrics' => [
                    'Entradas en rango' => PurchaseOrder::where('tenant_id', $user->tenant_id)->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])->count(),
                    'Stock total' => ProductVariant::whereHas('product', function ($query) use ($user) {
                        $query->where('tenant_id', $user->tenant_id);
                    })->sum('stock'),
                    'Valor inventario' => ProductVariant::whereHas('product', function ($query) use ($user) {
                        $query->where('tenant_id', $user->tenant_id);
                    })->get()->sum(fn ($variant) => (float) $variant->stock * (float) $variant->price),
                ],
            ],
            [
                'name' => 'Operacion',
                'metrics' => [
                    'Metodos de pago activos' => PaymentMethod::where('tenant_id', $user->tenant_id)->where('is_active', 1)->count(),
                    'Paquetes de materiales activos' => MaterialPackage::where('tenant_id', $user->tenant_id)->where('is_active', 1)->count(),
                    'Usuarios tienda' => User::where('tenant_id', $user->tenant_id)->count(),
                ],
            ],
        ];

        $summary = [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'generated_at' => now(),
        ];

        return $this->renderPdf(
            'reports.pdf.system-modules',
            compact('modules', 'summary'),
            'reporte_general_modulos'
        );
    }

    public function systemModulesExcel(Request $request)
    {
        $user = auth()->user();
        [$startDate, $endDate] = $this->resolveDateRange($request);

        $modules = [
            [
                'name' => 'Catalogo',
                'metrics' => [
                    'Categorias activas' => Category::where('tenant_id', $user->tenant_id)->where('is_active', 1)->count(),
                    'Productos activos' => Product::where('tenant_id', $user->tenant_id)->where('is_active', 1)->count(),
                    'Variantes totales' => ProductVariant::whereHas('product', function ($query) use ($user) {
                        $query->where('tenant_id', $user->tenant_id);
                    })->count(),
                ],
            ],
            [
                'name' => 'Ventas',
                'metrics' => [
                    'Ordenes en rango' => SalesOrder::where('tenant_id', $user->tenant_id)->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])->count(),
                    'Monto vendido' => SalesOrder::where('tenant_id', $user->tenant_id)->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])->with('details')->get()->sum(fn ($order) => $order->details->sum('amount')),
                    'Pagos aprobados' => SalesOrder::where('tenant_id', $user->tenant_id)->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])->with('payments')->get()->sum(fn ($order) => $order->payments->where('status', 1)->sum('amount')),
                ],
            ],
            [
                'name' => 'Inventario y Compras',
                'metrics' => [
                    'Entradas en rango' => PurchaseOrder::where('tenant_id', $user->tenant_id)->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])->count(),
                    'Stock total' => ProductVariant::whereHas('product', function ($query) use ($user) {
                        $query->where('tenant_id', $user->tenant_id);
                    })->sum('stock'),
                    'Valor inventario' => ProductVariant::whereHas('product', function ($query) use ($user) {
                        $query->where('tenant_id', $user->tenant_id);
                    })->get()->sum(fn ($variant) => (float) $variant->stock * (float) $variant->price),
                ],
            ],
            [
                'name' => 'Operacion',
                'metrics' => [
                    'Metodos de pago activos' => PaymentMethod::where('tenant_id', $user->tenant_id)->where('is_active', 1)->count(),
                    'Paquetes de materiales activos' => MaterialPackage::where('tenant_id', $user->tenant_id)->where('is_active', 1)->count(),
                    'Usuarios tienda' => User::where('tenant_id', $user->tenant_id)->count(),
                ],
            ],
        ];

        $csvRows = [];
        foreach ($modules as $module) {
            foreach ($module['metrics'] as $metricName => $metricValue) {
                $csvRows[] = [
                    (string) $module['name'],
                    (string) $metricName,
                    is_numeric($metricValue) ? number_format((float) $metricValue, 2, '.', '') : (string) $metricValue,
                ];
            }
        }

        return $this->downloadCsv(
            'reporte_general_modulos',
            ['Modulo', 'Metrica', 'Valor'],
            $csvRows
        );
    }

    public function customersPdf(Request $request)
    {
        [$rows, $summary] = $this->buildCustomersReportData($request);

        return $this->renderPdf(
            'reports.pdf.customers',
            compact('rows', 'summary'),
            'reporte_clientes'
        );
    }

    public function customersExcel(Request $request)
    {
        [$rows] = $this->buildCustomersReportData($request);

        $csvRows = $rows->map(function ($customer) {
            return [
                (string) $customer->name,
                (string) $customer->email,
                (string) ($customer->phone_number ?? ''),
                (string) ($customer->dni ?? ''),
                (string) $customer->orders_count,
                number_format((float) ($customer->total_paid_amount ?? 0), 2, '.', ''),
                (string) ($customer->last_purchase_at ? Carbon::parse($customer->last_purchase_at)->format('d/m/Y') : 'N/A'),
                $customer->is_active ? 'Activo' : 'Inactivo',
            ];
        })->all();

        return $this->downloadCsv(
            'reporte_clientes',
            ['Cliente', 'Correo', 'Telefono', 'DNI', 'Compras', 'Cobrado_USD', 'Ultima_Compra', 'Estado'],
            $csvRows
        );
    }

    public function receivablesPdf(Request $request)
    {
        [$rows, $summary] = $this->buildReceivablesReportData($request);

        return $this->renderPdf(
            'reports.pdf.receivables',
            compact('rows', 'summary'),
            'reporte_cuentas_por_cobrar'
        );
    }

    public function receivablesExcel(Request $request)
    {
        [$rows] = $this->buildReceivablesReportData($request);

        $csvRows = $rows->map(function ($order) {
            return [
                (string) $order->id,
                (string) Carbon::parse($order->date)->format('d/m/Y'),
                (string) ($order->user->name ?? 'N/A'),
                (string) $order->details->sum('quantity'),
                number_format((float) $order->order_total_amount, 2, '.', ''),
                number_format((float) $order->approved_paid_amount, 2, '.', ''),
                number_format((float) $order->pending_amount, 2, '.', ''),
                (string) ($order->preference ?? 'N/A'),
            ];
        })->all();

        return $this->downloadCsv(
            'reporte_cuentas_por_cobrar',
            ['Orden_ID', 'Fecha', 'Cliente', 'Items', 'Total_USD', 'Cobrado_USD', 'Saldo_USD', 'Entrega'],
            $csvRows
        );
    }

    public function storeExpensesPdf(Request $request)
    {
        [$rows, $summary] = $this->buildStoreExpensesReportData($request);

        return $this->renderPdf(
            'reports.pdf.store-expenses',
            compact('rows', 'summary'),
            'reporte_gastos_tienda'
        );
    }

    public function storeExpensesExcel(Request $request)
    {
        [$rows] = $this->buildStoreExpensesReportData($request);

        $csvRows = $rows->map(function ($expense) {
            return [
                (string) Carbon::parse($expense->spent_at)->format('d/m/Y'),
                (string) $expense->title,
                (string) ($expense->category ?? ''),
                (string) ($expense->provider_name ?? ''),
                (string) ($expense->payment_method ?? ''),
                number_format((float) $expense->amount, 2, '.', ''),
                (string) ($expense->status ?? 'paid'),
            ];
        })->all();

        return $this->downloadCsv(
            'reporte_gastos_tienda',
            ['Fecha', 'Concepto', 'Categoria', 'Proveedor', 'Metodo_Pago', 'Monto_USD', 'Estado'],
            $csvRows
        );
    }

    private function resolveDateRange(Request $request): array
    {
        $startInput = $request->query('start_date');
        $endInput = $request->query('end_date');

        $startDate = $startInput ? Carbon::parse($startInput)->startOfDay() : Carbon::now()->subDays(30)->startOfDay();
        $endDate = $endInput ? Carbon::parse($endInput)->endOfDay() : Carbon::now()->endOfDay();

        if ($startDate->gt($endDate)) {
            [$startDate, $endDate] = [$endDate->copy()->startOfDay(), $startDate->copy()->endOfDay()];
        }

        return [$startDate, $endDate];
    }

    private function renderPdf(string $view, array $data, string $prefix)
    {
        $html = view($view, $data)->render();

        $options = new Options();
        $options->set('isHtml5ParserEnabled', true);
        $options->set('isPhpEnabled', true);

        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $fileName = $prefix . '_' . now()->format('Ymd_His') . '.pdf';

        return response($dompdf->output(), 200)
            ->header('Content-Type', 'application/pdf')
            ->header('Content-Disposition', 'attachment; filename="' . $fileName . '"');
    }

    private function downloadCsv(string $prefix, array $header, array $rows)
    {
        $fileName = $prefix . '_' . now()->format('Ymd_His') . '.csv';

        return response()->streamDownload(function () use ($header, $rows) {
            $output = fopen('php://output', 'w');
            fprintf($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
            fputcsv($output, $header);

            foreach ($rows as $row) {
                fputcsv($output, $row);
            }

            fclose($output);
        }, $fileName, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    private function buildCustomersReportData(Request $request): array
    {
        $user = auth()->user();
        [$startDate, $endDate] = $this->resolveDateRange($request);
        $customerStatus = strtolower(trim((string) $request->query('customer_status', 'all')));

        $rows = User::query()
            ->where('tenant_id', $user->tenant_id)
            ->when($customerStatus === 'active', function ($query) {
                $query->where('is_active', 1);
            })
            ->when($customerStatus === 'inactive', function ($query) {
                $query->where('is_active', 0);
            })
            ->whereHas('salesOrders', function ($query) use ($user, $startDate, $endDate) {
                $query->where('tenant_id', $user->tenant_id)
                    ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()]);
            })
            ->withCount(['salesOrders as orders_count' => function ($query) use ($user, $startDate, $endDate) {
                $query->where('tenant_id', $user->tenant_id)
                    ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()]);
            }])
            ->withMax(['salesOrders as last_purchase_at' => function ($query) use ($user, $startDate, $endDate) {
                $query->where('tenant_id', $user->tenant_id)
                    ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()]);
            }], 'date')
            ->withSum(['payments as total_paid_amount' => function ($query) use ($user, $startDate, $endDate) {
                $query->where('payments.status', 1)
                    ->whereHas('salesOrder', function ($salesOrderQuery) use ($user, $startDate, $endDate) {
                        $salesOrderQuery->where('tenant_id', $user->tenant_id)
                            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()]);
                    });
            }], 'amount')
            ->orderByDesc('orders_count')
            ->orderBy('name')
            ->get();

        $summary = [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'customer_status' => $customerStatus,
            'customers' => (int) $rows->count(),
            'orders' => (int) $rows->sum('orders_count'),
            'total_paid' => (float) $rows->sum(fn (User $customer) => (float) ($customer->total_paid_amount ?? 0)),
        ];

        return [$rows, $summary];
    }

    private function buildReceivablesReportData(Request $request): array
    {
        $user = auth()->user();
        [$startDate, $endDate] = $this->resolveDateRange($request);
        $minPendingBalance = max(0, (float) $request->query('min_pending_balance', 0));

        $rows = SalesOrder::with(['user', 'details', 'payments'])
            ->where('tenant_id', $user->tenant_id)
            ->whereBetween('date', [$startDate->toDateString(), $endDate->toDateString()])
            ->where('status', '!=', 2)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get()
            ->map(function (SalesOrder $order) {
                $order->order_total_amount = (float) $order->details->sum('amount');
                $order->approved_paid_amount = (float) $order->payments->where('status', 1)->sum('amount');
                $order->pending_amount = max(0, round($order->order_total_amount - $order->approved_paid_amount, 2));

                return $order;
            })
            ->filter(fn (SalesOrder $order) => $order->pending_amount >= $minPendingBalance && $order->pending_amount > 0)
            ->values();

        $summary = [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'min_pending_balance' => $minPendingBalance,
            'orders' => (int) $rows->count(),
            'total_amount' => (float) $rows->sum('order_total_amount'),
            'total_paid' => (float) $rows->sum('approved_paid_amount'),
            'total_pending' => (float) $rows->sum('pending_amount'),
        ];

        return [$rows, $summary];
    }

    private function buildStoreExpensesReportData(Request $request): array
    {
        $user = auth()->user();
        [$startDate, $endDate] = $this->resolveDateRange($request);
        $expenseCategory = trim((string) $request->query('expense_category', ''));

        $rows = StoreExpense::query()
            ->where('tenant_id', $user->tenant_id)
            ->whereBetween('spent_at', [$startDate->toDateString(), $endDate->toDateString()])
            ->when($expenseCategory !== '', function ($query) use ($expenseCategory) {
                $query->where('category', $expenseCategory);
            })
            ->orderByDesc('spent_at')
            ->orderByDesc('id')
            ->get();

        $summary = [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'expense_category' => $expenseCategory,
            'expenses' => (int) $rows->count(),
            'total_amount' => (float) $rows->sum('amount'),
        ];

        return [$rows, $summary];
    }
}
