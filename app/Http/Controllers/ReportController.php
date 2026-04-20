<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\MaterialPackage;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\PurchaseOrder;
use App\Models\SalesAdjustmentNote;
use App\Models\SalesOrder;
use App\Models\SalesRetention;
use App\Models\StoreExpense;
use App\Models\Tenant;
use App\Models\User;
use App\Services\TheFactoryHkaService;
use App\Support\TenantCurrency;
use Carbon\Carbon;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();
        $tenant = Tenant::find($user->tenant_id);
        $baseCurrencyCode = TenantCurrency::resolveBaseCurrencyCode($tenant);
        $selectedCurrencyCode = TenantCurrency::normalizeCurrencyCode((string) $request->query('currency_code', $baseCurrencyCode));
        $selectedSalesBookSource = (string) $request->query('sales_book_source', 'shopix');

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
            'baseCurrencyCode' => $baseCurrencyCode,
            'selectedCurrencyCode' => $selectedCurrencyCode,
            'selectedSalesBookSource' => in_array($selectedSalesBookSource, ['shopix', 'hka'], true) ? $selectedSalesBookSource : 'shopix',
        ]);
    }

    public function topSellingProductsPdf(Request $request)
    {
        $user = auth()->user();
        [$startDate, $endDate] = $this->resolveDateRange($request);
        $currency = $this->resolveReportCurrencyContext($request, (int) $user->tenant_id);

        $rows = ProductVariant::query()
            ->join('products', 'products.id', '=', 'product_variants.product_id')
            ->join('sales_order_details', 'sales_order_details.product_variant_id', '=', 'product_variants.id')
            ->join('sales_orders', 'sales_orders.id', '=', 'sales_order_details.sales_order_id')
            ->where('sales_orders.tenant_id', $user->tenant_id)
            ->whereDate('sales_orders.date', '>=', $startDate->toDateString())
            ->whereDate('sales_orders.date', '<=', $endDate->toDateString())
            ->groupBy('product_variants.id', 'products.name', 'product_variants.size')
            ->selectRaw('products.name as product_name, product_variants.size as variant_name, SUM(sales_order_details.quantity) as total_quantity, SUM(sales_order_details.amount) as total_amount')
            ->orderByDesc('total_quantity')
            ->limit(50)
            ->get();

        $rows->transform(function ($row) use ($currency, $user) {
            $row->total_amount = TenantCurrency::convertAmount((float) $row->total_amount, $currency['base_code'], $currency['code'], (int) $user->tenant_id);
            return $row;
        });

        $summary = [
            'total_units' => (int) $rows->sum('total_quantity'),
            'total_amount' => (float) $rows->sum('total_amount'),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'currency_code' => $currency['code'],
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
        $currency = $this->resolveReportCurrencyContext($request, (int) $user->tenant_id);

        $rows = ProductVariant::query()
            ->join('products', 'products.id', '=', 'product_variants.product_id')
            ->join('sales_order_details', 'sales_order_details.product_variant_id', '=', 'product_variants.id')
            ->join('sales_orders', 'sales_orders.id', '=', 'sales_order_details.sales_order_id')
            ->where('sales_orders.tenant_id', $user->tenant_id)
            ->whereDate('sales_orders.date', '>=', $startDate->toDateString())
            ->whereDate('sales_orders.date', '<=', $endDate->toDateString())
            ->groupBy('product_variants.id', 'products.name', 'product_variants.size')
            ->selectRaw('products.name as product_name, product_variants.size as variant_name, SUM(sales_order_details.quantity) as total_quantity, SUM(sales_order_details.amount) as total_amount')
            ->orderByDesc('total_quantity')
            ->limit(50)
            ->get();

        $rows->transform(function ($row) use ($currency, $user) {
            $row->total_amount = TenantCurrency::convertAmount((float) $row->total_amount, $currency['base_code'], $currency['code'], (int) $user->tenant_id);
            return $row;
        });

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
            ['Producto', 'Variante', 'Unidades', 'Monto_' . $currency['code']],
            $csvRows
        );
    }

    public function inventoryEntriesPdf(Request $request)
    {
        $user = auth()->user();
        [$startDate, $endDate] = $this->resolveDateRange($request);
        $currency = $this->resolveReportCurrencyContext($request, (int) $user->tenant_id);

        $entries = PurchaseOrder::with(['warehouse', 'provider', 'detalles.productVariant.product'])
            ->where('tenant_id', $user->tenant_id)
            ->whereDate('date', '>=', $startDate->toDateString())
            ->whereDate('date', '<=', $endDate->toDateString())
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();

        $entries->transform(function ($order) use ($currency, $user) {
            $reportAmount = TenantCurrency::convertAmount((float) $order->detalles->sum('amount'), $currency['base_code'], $currency['code'], (int) $user->tenant_id);
            $order->report_total_amount = $reportAmount;
            return $order;
        });

        $summary = [
            'orders' => (int) $entries->count(),
            'total_items' => (int) $entries->sum(fn ($order) => $order->detalles->sum('quantity')),
            'total_amount' => (float) $entries->sum('report_total_amount'),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'currency_code' => $currency['code'],
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
        $currency = $this->resolveReportCurrencyContext($request, (int) $user->tenant_id);

        $entries = PurchaseOrder::with(['warehouse', 'provider', 'detalles'])
            ->where('tenant_id', $user->tenant_id)
            ->whereDate('date', '>=', $startDate->toDateString())
            ->whereDate('date', '<=', $endDate->toDateString())
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();

        $csvRows = $entries->map(function ($order) use ($currency, $user) {
            return [
                (string) $order->id,
                (string) $order->date,
                (string) ($order->warehouse->name ?? 'N/A'),
                (string) ($order->provider->name ?? $order->provider_name ?? 'N/A'),
                (string) $order->detalles->sum('quantity'),
                number_format(TenantCurrency::convertAmount((float) $order->detalles->sum('amount'), $currency['base_code'], $currency['code'], (int) $user->tenant_id), 2, '.', ''),
            ];
        })->all();

        return $this->downloadCsv(
            'reporte_entradas_inventario',
            ['Orden_ID', 'Fecha', 'Almacen', 'Proveedor', 'Items', 'Monto_' . $currency['code']],
            $csvRows
        );
    }

    public function salesManagementPdf(Request $request)
    {
        $user = auth()->user();
        [$startDate, $endDate] = $this->resolveDateRange($request);
        $currency = $this->resolveReportCurrencyContext($request, (int) $user->tenant_id);

        $orders = SalesOrder::with(['user', 'details', 'payments'])
            ->where('tenant_id', $user->tenant_id)
            ->whereDate('date', '>=', $startDate->toDateString())
            ->whereDate('date', '<=', $endDate->toDateString())
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();

        $orders->transform(function ($order) use ($currency, $user) {
            $order->report_total_amount = TenantCurrency::convertAmount((float) $order->details->sum('amount'), $currency['base_code'], $currency['code'], (int) $user->tenant_id);
            $order->report_total_paid = TenantCurrency::convertAmount((float) $order->payments->where('status', 1)->sum('amount'), $currency['base_code'], $currency['code'], (int) $user->tenant_id);
            return $order;
        });

        $summary = [
            'orders' => (int) $orders->count(),
            'total_items' => (int) $orders->sum(fn ($order) => $order->details->sum('quantity')),
            'total_amount' => (float) $orders->sum('report_total_amount'),
            'total_paid' => (float) $orders->sum('report_total_paid'),
            'start_date' => $startDate,
            'end_date' => $endDate,
            'currency_code' => $currency['code'],
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
        $currency = $this->resolveReportCurrencyContext($request, (int) $user->tenant_id);

        $orders = SalesOrder::with(['user', 'details', 'payments'])
            ->where('tenant_id', $user->tenant_id)
            ->whereDate('date', '>=', $startDate->toDateString())
            ->whereDate('date', '<=', $endDate->toDateString())
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();

        $csvRows = $orders->map(function ($order) use ($currency, $user) {
            $status = $order->status == 0
                ? 'En Proceso'
                : ($order->status == 1 ? 'Aprobado' : ($order->status == 2 ? 'Negado' : 'N/A'));

            return [
                (string) $order->id,
                (string) $order->date,
                (string) ($order->user->name ?? 'N/A'),
                $status,
                (string) $order->details->sum('quantity'),
                number_format(TenantCurrency::convertAmount((float) $order->details->sum('amount'), $currency['base_code'], $currency['code'], (int) $user->tenant_id), 2, '.', ''),
                number_format(TenantCurrency::convertAmount((float) $order->payments->where('status', 1)->sum('amount'), $currency['base_code'], $currency['code'], (int) $user->tenant_id), 2, '.', ''),
            ];
        })->all();

        return $this->downloadCsv(
            'reporte_gestion_ventas',
            ['Orden_ID', 'Fecha', 'Cliente', 'Estado', 'Items', 'Total_' . $currency['code'], 'Cobrado_' . $currency['code']],
            $csvRows
        );
    }

    public function inventoryTotalPdf()
    {
        $user = auth()->user();
        $currency = $this->resolveReportCurrencyContext(request(), (int) $user->tenant_id);

        $rows = ProductVariant::with(['product.category'])
            ->whereHas('product', function ($query) use ($user) {
                $query->where('tenant_id', $user->tenant_id);
            })
            ->orderByDesc('stock')
            ->get();

        $rows->transform(function ($variant) use ($currency, $user) {
            $variant->report_price = TenantCurrency::convertAmount((float) $variant->price, $currency['base_code'], $currency['code'], (int) $user->tenant_id);
            $variant->report_value = TenantCurrency::convertAmount((float) $variant->stock * (float) $variant->price, $currency['base_code'], $currency['code'], (int) $user->tenant_id);
            return $variant;
        });

        $summary = [
            'variants' => (int) $rows->count(),
            'total_stock' => (int) $rows->sum('stock'),
            'inventory_value' => (float) $rows->sum('report_value'),
            'generated_at' => now(),
            'currency_code' => $currency['code'],
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
        $currency = $this->resolveReportCurrencyContext(request(), (int) $user->tenant_id);

        $rows = ProductVariant::with(['product.category'])
            ->whereHas('product', function ($query) use ($user) {
                $query->where('tenant_id', $user->tenant_id);
            })
            ->orderByDesc('stock')
            ->get();

        $csvRows = $rows->map(function ($row) use ($currency, $user) {
            return [
                (string) ($row->product->name ?? 'N/A'),
                (string) ($row->product->category->name ?? 'N/A'),
                (string) ($row->size ?: 'N/A'),
                (string) $row->stock,
                number_format(TenantCurrency::convertAmount((float) $row->price, $currency['base_code'], $currency['code'], (int) $user->tenant_id), 2, '.', ''),
                number_format(TenantCurrency::convertAmount((float) $row->stock * (float) $row->price, $currency['base_code'], $currency['code'], (int) $user->tenant_id), 2, '.', ''),
            ];
        })->all();

        return $this->downloadCsv(
            'reporte_inventario_total',
            ['Producto', 'Categoria', 'Variante', 'Stock', 'Precio_' . $currency['code'], 'Valor_' . $currency['code']],
            $csvRows
        );
    }

    public function systemModulesPdf(Request $request)
    {
        $user = auth()->user();
        [$startDate, $endDate] = $this->resolveDateRange($request);
        $currency = $this->resolveReportCurrencyContext($request, (int) $user->tenant_id);

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
                    'Ordenes en rango' => SalesOrder::where('tenant_id', $user->tenant_id)->whereDate('date', '>=', $startDate->toDateString())->whereDate('date', '<=', $endDate->toDateString())->count(),
                    'Monto vendido' => SalesOrder::where('tenant_id', $user->tenant_id)->whereDate('date', '>=', $startDate->toDateString())->whereDate('date', '<=', $endDate->toDateString())->with('details')->get()->sum(fn ($order) => $order->details->sum('amount')),
                    'Pagos aprobados' => SalesOrder::where('tenant_id', $user->tenant_id)->whereDate('date', '>=', $startDate->toDateString())->whereDate('date', '<=', $endDate->toDateString())->with('payments')->get()->sum(fn ($order) => $order->payments->where('status', 1)->sum('amount')),
                ],
            ],
            [
                'name' => 'Inventario y Compras',
                'metrics' => [
                    'Entradas en rango' => PurchaseOrder::where('tenant_id', $user->tenant_id)->whereDate('date', '>=', $startDate->toDateString())->whereDate('date', '<=', $endDate->toDateString())->count(),
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
            'currency_code' => $currency['code'],
        ];

        $modules = $this->convertModulesMoneyMetrics($modules, $currency, (int) $user->tenant_id);

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
        $currency = $this->resolveReportCurrencyContext($request, (int) $user->tenant_id);

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
                    'Ordenes en rango' => SalesOrder::where('tenant_id', $user->tenant_id)->whereDate('date', '>=', $startDate->toDateString())->whereDate('date', '<=', $endDate->toDateString())->count(),
                    'Monto vendido' => SalesOrder::where('tenant_id', $user->tenant_id)->whereDate('date', '>=', $startDate->toDateString())->whereDate('date', '<=', $endDate->toDateString())->with('details')->get()->sum(fn ($order) => $order->details->sum('amount')),
                    'Pagos aprobados' => SalesOrder::where('tenant_id', $user->tenant_id)->whereDate('date', '>=', $startDate->toDateString())->whereDate('date', '<=', $endDate->toDateString())->with('payments')->get()->sum(fn ($order) => $order->payments->where('status', 1)->sum('amount')),
                ],
            ],
            [
                'name' => 'Inventario y Compras',
                'metrics' => [
                    'Entradas en rango' => PurchaseOrder::where('tenant_id', $user->tenant_id)->whereDate('date', '>=', $startDate->toDateString())->whereDate('date', '<=', $endDate->toDateString())->count(),
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

        $modules = $this->convertModulesMoneyMetrics($modules, $currency, (int) $user->tenant_id);

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
        [$rows, $summary] = $this->buildCustomersReportData($request);

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
            ['Cliente', 'Correo', 'Telefono', 'DNI', 'Compras', 'Cobrado_' . ($summary['currency_code'] ?? 'USD'), 'Ultima_Compra', 'Estado'],
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
        [$rows, $summary] = $this->buildReceivablesReportData($request);

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
            ['Orden_ID', 'Fecha', 'Cliente', 'Items', 'Total_' . ($summary['currency_code'] ?? 'USD'), 'Cobrado_' . ($summary['currency_code'] ?? 'USD'), 'Saldo_' . ($summary['currency_code'] ?? 'USD'), 'Entrega'],
            $csvRows
        );
    }

    public function salesBookPdf(Request $request)
    {
        [$rows, $summary] = $this->buildSalesBookData($request);

        return $this->renderPdf(
            'reports.pdf.sales-book',
            compact('rows', 'summary'),
            'libro_ventas'
        );
    }

    public function salesBookExcel(Request $request)
    {
        [$rows, $summary] = $this->buildSalesBookData($request);

        $csvRows = $rows->map(function ($row) {
            return [
                (string) $row['sale_date'],
                (string) $row['order_id'],
                (string) $row['customer_name'],
                (string) $row['document_label'],
                (string) $row['document_number'],
                (string) $row['control_number'],
                number_format((float) $row['taxable_base'], 2, '.', ''),
                number_format((float) $row['tax_total'], 2, '.', ''),
                number_format((float) $row['total_amount'], 2, '.', ''),
                number_format((float) $row['credit_notes_total'], 2, '.', ''),
                number_format((float) $row['debit_notes_total'], 2, '.', ''),
                number_format((float) $row['retentions_total'], 2, '.', ''),
                number_format((float) $row['net_total'], 2, '.', ''),
            ];
        })->all();

        return $this->downloadCsv(
            'libro_ventas',
            ['Fecha', 'Orden_ID', 'Cliente', 'Documento', 'Numero', 'Control', 'Base_' . ($summary['currency_code'] ?? 'USD'), 'IVA_' . ($summary['currency_code'] ?? 'USD'), 'Total_' . ($summary['currency_code'] ?? 'USD'), 'Notas_Credito', 'Notas_Debito', 'Retenciones', 'Neto'],
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
        [$rows, $summary] = $this->buildStoreExpensesReportData($request);

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
            ['Fecha', 'Concepto', 'Categoria', 'Proveedor', 'Metodo_Pago', 'Monto_' . ($summary['currency_code'] ?? 'USD'), 'Estado'],
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
        $currency = $this->resolveReportCurrencyContext($request, (int) $user->tenant_id);
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
                    ->whereDate('date', '>=', $startDate->toDateString())
                    ->whereDate('date', '<=', $endDate->toDateString());
            })
            ->withCount(['salesOrders as orders_count' => function ($query) use ($user, $startDate, $endDate) {
                $query->where('tenant_id', $user->tenant_id)
                    ->whereDate('date', '>=', $startDate->toDateString())
                    ->whereDate('date', '<=', $endDate->toDateString());
            }])
            ->withMax(['salesOrders as last_purchase_at' => function ($query) use ($user, $startDate, $endDate) {
                $query->where('tenant_id', $user->tenant_id)
                    ->whereDate('date', '>=', $startDate->toDateString())
                    ->whereDate('date', '<=', $endDate->toDateString());
            }], 'date')
            ->withSum(['payments as total_paid_amount' => function ($query) use ($user, $startDate, $endDate) {
                $query->where('payments.status', 1)
                    ->whereHas('salesOrder', function ($salesOrderQuery) use ($user, $startDate, $endDate) {
                        $salesOrderQuery->where('tenant_id', $user->tenant_id)
                            ->whereDate('date', '>=', $startDate->toDateString())
                            ->whereDate('date', '<=', $endDate->toDateString());
                    });
            }], 'amount')
            ->orderByDesc('orders_count')
            ->orderBy('name')
            ->get();

        $rows->transform(function ($customer) use ($currency, $user) {
            $customer->total_paid_amount = TenantCurrency::convertAmount((float) ($customer->total_paid_amount ?? 0), $currency['base_code'], $currency['code'], (int) $user->tenant_id);
            return $customer;
        });

        $summary = [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'customer_status' => $customerStatus,
            'customers' => (int) $rows->count(),
            'orders' => (int) $rows->sum('orders_count'),
            'total_paid' => (float) $rows->sum(fn (User $customer) => (float) ($customer->total_paid_amount ?? 0)),
            'currency_code' => $currency['code'],
        ];

        return [$rows, $summary];
    }

    private function buildReceivablesReportData(Request $request): array
    {
        $user = auth()->user();
        $currency = $this->resolveReportCurrencyContext($request, (int) $user->tenant_id);
        [$startDate, $endDate] = $this->resolveDateRange($request);
        $minPendingBalance = max(0, (float) $request->query('min_pending_balance', 0));
        $minPendingBalanceBase = TenantCurrency::convertAmount($minPendingBalance, $currency['code'], $currency['base_code'], (int) $user->tenant_id);

        $rows = SalesOrder::with(['user', 'details', 'payments'])
            ->where('tenant_id', $user->tenant_id)
            ->whereDate('date', '>=', $startDate->toDateString())
            ->whereDate('date', '<=', $endDate->toDateString())
            ->where('status', '!=', 2)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get()
            ->map(function (SalesOrder $order) use ($currency, $user) {
                $order->order_total_amount = (float) $order->details->sum('amount');
                $order->approved_paid_amount = (float) $order->payments->where('status', 1)->sum('amount');
                $order->pending_amount = max(0, round($order->order_total_amount - $order->approved_paid_amount, 2));

                $order->order_total_amount = TenantCurrency::convertAmount($order->order_total_amount, $currency['base_code'], $currency['code'], (int) $user->tenant_id);
                $order->approved_paid_amount = TenantCurrency::convertAmount($order->approved_paid_amount, $currency['base_code'], $currency['code'], (int) $user->tenant_id);
                $order->pending_amount = TenantCurrency::convertAmount($order->pending_amount, $currency['base_code'], $currency['code'], (int) $user->tenant_id);

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
            'currency_code' => $currency['code'],
        ];

        return [$rows, $summary];
    }

    private function buildStoreExpensesReportData(Request $request): array
    {
        $user = auth()->user();
        $currency = $this->resolveReportCurrencyContext($request, (int) $user->tenant_id);
        [$startDate, $endDate] = $this->resolveDateRange($request);
        $expenseCategory = trim((string) $request->query('expense_category', ''));

        $rows = StoreExpense::query()
            ->where('tenant_id', $user->tenant_id)
            ->whereDate('spent_at', '>=', $startDate->toDateString())
            ->whereDate('spent_at', '<=', $endDate->toDateString())
            ->when($expenseCategory !== '', function ($query) use ($expenseCategory) {
                $query->where('category', $expenseCategory);
            })
            ->orderByDesc('spent_at')
            ->orderByDesc('id')
            ->get();

        $rows->transform(function ($expense) use ($currency, $user) {
            $expense->amount = TenantCurrency::convertAmount((float) $expense->amount, $currency['base_code'], $currency['code'], (int) $user->tenant_id);
            return $expense;
        });

        $summary = [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'expense_category' => $expenseCategory,
            'expenses' => (int) $rows->count(),
            'total_amount' => (float) $rows->sum('amount'),
            'currency_code' => $currency['code'],
        ];

        return [$rows, $summary];
    }

    private function buildSalesBookData(Request $request): array
    {
        $user = auth()->user();
        $currency = $this->resolveReportCurrencyContext($request, (int) $user->tenant_id);
        [$startDate, $endDate] = $this->resolveDateRange($request);
        $salesBookSource = in_array((string) $request->query('sales_book_source', 'shopix'), ['shopix', 'hka'], true)
            ? (string) $request->query('sales_book_source', 'shopix')
            : 'shopix';

        $orders = SalesOrder::with(['user', 'details.taxes', 'electronicDocuments', 'adjustmentNotes', 'retentions'])
            ->where('tenant_id', $user->tenant_id)
            ->whereDate('date', '>=', $startDate->toDateString())
            ->whereDate('date', '<=', $endDate->toDateString())
            ->where('status', '!=', 2)
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        $rows = $orders->map(function (SalesOrder $order) use ($currency, $user) {
            $latestElectronicDocument = $order->electronicDocuments->sortByDesc('id')->first();
            $taxableBase = (float) $order->details->sum('amount');
            $taxTotal = (float) $order->details->flatMap->taxes->sum('tax_amount');
            $totalAmount = $taxableBase + $taxTotal;
            $creditNotesTotal = (float) $order->adjustmentNotes
                ->where('note_type', 'credit')
                ->sum('amount');
            $debitNotesTotal = (float) $order->adjustmentNotes
                ->where('note_type', 'debit')
                ->sum('amount');
            $retentionsTotal = (float) $order->retentions->sum('retained_amount');

            $taxableBase = TenantCurrency::convertAmount($taxableBase, $currency['base_code'], $currency['code'], (int) $user->tenant_id);
            $taxTotal = TenantCurrency::convertAmount($taxTotal, $currency['base_code'], $currency['code'], (int) $user->tenant_id);
            $totalAmount = TenantCurrency::convertAmount($totalAmount, $currency['base_code'], $currency['code'], (int) $user->tenant_id);
            $creditNotesTotal = TenantCurrency::convertAmount($creditNotesTotal, $currency['base_code'], $currency['code'], (int) $user->tenant_id);
            $debitNotesTotal = TenantCurrency::convertAmount($debitNotesTotal, $currency['base_code'], $currency['code'], (int) $user->tenant_id);
            $retentionsTotal = TenantCurrency::convertAmount($retentionsTotal, $currency['base_code'], $currency['code'], (int) $user->tenant_id);

            return [
                'sale_date' => Carbon::parse($order->date)->format('d/m/Y'),
                'order_id' => (int) $order->id,
                'customer_name' => (string) ($order->user->name ?? 'N/A'),
                'document_label' => $latestElectronicDocument ? 'Factura digital' : 'Orden de entrega',
                'document_type' => (string) ($latestElectronicDocument->tipo_documento ?? '04'),
                'document_series' => (string) ($latestElectronicDocument->serie ?? ''),
                'document_number' => (string) ($latestElectronicDocument->numero_documento ?? $order->id),
                'control_number' => (string) ($latestElectronicDocument->numero_control ?? '-'),
                'status_label' => (bool) ($latestElectronicDocument?->is_annulled ?? false) ? 'Anulado' : 'Activo',
                'status_origin' => 'shopix',
                'taxable_base' => $taxableBase,
                'tax_total' => $taxTotal,
                'total_amount' => $totalAmount,
                'credit_notes_total' => $creditNotesTotal,
                'debit_notes_total' => $debitNotesTotal,
                'retentions_total' => $retentionsTotal,
                'net_total' => $totalAmount - $creditNotesTotal + $debitNotesTotal - $retentionsTotal,
            ];
        })->values();

        if ($salesBookSource === 'hka') {
            $rows = $this->syncSalesBookRowsWithHka($rows, $startDate, $endDate);
        }

        $summary = [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'rows_count' => (int) $rows->count(),
            'taxable_base' => (float) $rows->sum('taxable_base'),
            'tax_total' => (float) $rows->sum('tax_total'),
            'total_amount' => (float) $rows->sum('total_amount'),
            'credit_notes_total' => (float) $rows->sum('credit_notes_total'),
            'debit_notes_total' => (float) $rows->sum('debit_notes_total'),
            'retentions_total' => (float) $rows->sum('retentions_total'),
            'net_total' => (float) $rows->sum('net_total'),
            'currency_code' => $currency['code'],
            'source' => $salesBookSource,
        ];

        return [$rows, $summary];
    }

    private function syncSalesBookRowsWithHka($rows, Carbon $startDate, Carbon $endDate)
    {
        $service = app(TheFactoryHkaService::class);
        if (!$service->isConfigured()) {
            return $rows;
        }

        $statusMap = [];
        foreach (['01', '02', '03'] as $documentType) {
            foreach ($this->fetchHkaDocumentsByType($service, $documentType, $startDate, $endDate) as $document) {
                $key = $this->buildFiscalRowKey(
                    (string) Arr::get($document, 'tipoDocumento', $documentType),
                    (string) Arr::get($document, 'serie', ''),
                    (string) Arr::get($document, 'numeroDocumento', '')
                );

                if ($key === '') {
                    continue;
                }

                $statusMap[$key] = [
                    'status_label' => (string) (Arr::get($document, 'estadoDocumento') ?: (Arr::get($document, 'fechaAnulacion') ? 'Anulado' : 'Registrado en HKA')),
                    'control_number' => (string) (Arr::get($document, 'numeroControl') ?: '-'),
                    'is_annulled' => trim((string) Arr::get($document, 'fechaAnulacion', '')) !== '',
                    'status_origin' => 'hka',
                ];
            }
        }

        return $rows->map(function (array $row) use ($statusMap) {
            $key = $this->buildFiscalRowKey(
                (string) ($row['document_type'] ?? ''),
                (string) ($row['document_series'] ?? ''),
                (string) ($row['document_number'] ?? '')
            );

            if ($key !== '' && isset($statusMap[$key])) {
                $row['status_label'] = $statusMap[$key]['status_label'];
                $row['status_origin'] = $statusMap[$key]['status_origin'];
                if (($row['control_number'] ?? '-') === '-' && trim((string) $statusMap[$key]['control_number']) !== '') {
                    $row['control_number'] = $statusMap[$key]['control_number'];
                }
            }

            return $row;
        })->values();
    }

    private function fetchHkaDocumentsByType(TheFactoryHkaService $service, string $documentType, Carbon $startDate, Carbon $endDate): array
    {
        $documents = [];

        for ($page = 1; $page <= 20; $page++) {
            $response = $service->listDocuments([
                'TipoDocumento' => $documentType,
                'FechaInicio' => $startDate->format('d/m/Y'),
                'FechaFin' => $endDate->format('d/m/Y'),
                'NumPagina' => $page,
            ]);

            if (!($response['ok'] ?? false)) {
                break;
            }

            $pageDocuments = Arr::get($response, 'data.documentos', []);
            if (!is_array($pageDocuments) || empty($pageDocuments)) {
                break;
            }

            $documents = array_merge($documents, $pageDocuments);

            $totalPages = (int) Arr::get($response, 'data.totalPaginas', 1);
            if ($page >= max($totalPages, 1)) {
                break;
            }
        }

        return $documents;
    }

    private function buildFiscalRowKey(string $documentType, string $series, string $documentNumber): string
    {
        $normalizedNumber = preg_replace('/\D+/', '', $documentNumber) ?: trim($documentNumber);
        if ($normalizedNumber === '') {
            return '';
        }

        return implode('|', [trim($documentType), trim($series), $normalizedNumber]);
    }

    private function resolveReportCurrencyContext(Request $request, int $tenantId): array
    {
        $tenant = Tenant::find($tenantId);
        $baseCurrencyCode = TenantCurrency::resolveBaseCurrencyCode($tenant);
        $reportCurrencyCode = TenantCurrency::normalizeCurrencyCode((string) $request->query('currency_code', $baseCurrencyCode));

        if (!in_array($reportCurrencyCode, ['USD', 'EUR'], true)) {
            $reportCurrencyCode = $baseCurrencyCode;
        }

        return [
            'base_code' => $baseCurrencyCode,
            'code' => $reportCurrencyCode,
        ];
    }

    private function convertModulesMoneyMetrics(array $modules, array $currency, int $tenantId): array
    {
        return collect($modules)->map(function ($module) use ($currency, $tenantId) {
            foreach ($module['metrics'] as $metricName => $metricValue) {
                $normalized = mb_strtolower((string) $metricName);
                $isMoneyMetric = str_contains($normalized, 'monto') || str_contains($normalized, 'pagos') || str_contains($normalized, 'valor');

                if ($isMoneyMetric && is_numeric($metricValue)) {
                    $module['metrics'][$metricName] = TenantCurrency::convertAmount((float) $metricValue, $currency['base_code'], $currency['code'], $tenantId);
                }
            }

            return $module;
        })->values()->all();
    }
}
