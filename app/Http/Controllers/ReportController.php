<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\MaterialPackage;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\PurchaseOrder;
use App\Models\SalesOrder;
use App\Models\User;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index()
    {
        return view('reports.index');
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

        $entries = PurchaseOrder::with(['warehouse', 'detalles.productVariant.product'])
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

        $entries = PurchaseOrder::with(['warehouse', 'detalles'])
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
                (string) $order->provider_id,
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
}
