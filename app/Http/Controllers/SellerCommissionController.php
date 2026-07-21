<?php

namespace App\Http\Controllers;

use App\Models\SellerCommission;
use App\Models\SalesOrder;
use App\Models\User;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Http\Request;

class SellerCommissionController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        abort_if($tenantId <= 0, 403);

        $authUser = auth()->user();
        $isAdminView = (bool) ($authUser?->hasStoreRole('owner', 'admin') ?? false);
        $isSellerOnlyView = (bool) ($authUser?->hasStoreRole('seller') ?? false) && !$isAdminView;

        $sellerId = (int) $request->query('seller_id', 0);
        $status = trim((string) $request->query('status', ''));
        $dateFrom = trim((string) $request->query('date_from', ''));
        $dateTo = trim((string) $request->query('date_to', ''));

        if ($isSellerOnlyView) {
            $sellerId = (int) ($authUser->id ?? 0);
        }

        $sellers = User::query()
            ->with('role')
            ->where('tenant_id', $tenantId)
            ->where('is_active', 1)
            ->orderBy('name')
            ->get()
            ->filter(fn (User $candidate) => $candidate->hasStoreRole('seller', 'admin'))
            ->values();

        $baseQuery = SellerCommission::query()
            ->with(['seller', 'order'])
            ->where('tenant_id', $tenantId)
            ->when($sellerId > 0, function ($query) use ($sellerId) {
                $query->where('seller_user_id', $sellerId);
            })
            ->when($status !== '', function ($query) use ($status) {
                $query->where('status', $status);
            })
            ->when($dateFrom !== '', function ($query) use ($dateFrom) {
                $query->whereDate('created_at', '>=', $dateFrom);
            })
            ->when($dateTo !== '', function ($query) use ($dateTo) {
                $query->whereDate('created_at', '<=', $dateTo);
            });

        $commissions = (clone $baseQuery)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        $totalPending = (float) (clone $baseQuery)
            ->where('status', 'pending')
            ->sum('commission_amount');

        $totalPaid = (float) (clone $baseQuery)
            ->where('status', 'paid')
            ->sum('commission_amount');

        $monthGenerated = (float) SellerCommission::query()
            ->where('tenant_id', $tenantId)
            ->when($sellerId > 0, function ($query) use ($sellerId) {
                $query->where('seller_user_id', $sellerId);
            })
            ->whereBetween('created_at', [now()->startOfMonth(), now()->endOfMonth()])
            ->sum('commission_amount');

        return view('sellerCommissions.index', compact(
            'commissions',
            'sellers',
            'sellerId',
            'status',
            'dateFrom',
            'dateTo',
            'totalPending',
            'totalPaid',
            'monthGenerated',
            'isAdminView',
            'isSellerOnlyView'
        ));
    }

    public function updateSellerRate(Request $request, User $seller)
    {
        $authUser = auth()->user();
        if (!$authUser?->hasStoreRole('owner', 'admin')) {
            abort(403);
        }

        $tenantId = (int) ($authUser->tenant_id ?? 0);
        if ((int) $seller->tenant_id !== $tenantId || !$seller->hasStoreRole('seller', 'admin')) {
            abort(404);
        }

        $validated = $request->validate([
            'commission_percentage' => 'required|numeric|min:0|max:100',
        ]);

        $seller->commission_percentage = round((float) $validated['commission_percentage'], 2);
        $seller->save();

        return back()->with('success', 'Porcentaje de comisión actualizado.');
    }

    public function markAsPaid(SellerCommission $commission)
    {
        $authUser = auth()->user();
        if (!$authUser?->hasStoreRole('owner', 'admin')) {
            abort(403);
        }

        $tenantId = (int) ($authUser->tenant_id ?? 0);
        if ((int) $commission->tenant_id !== $tenantId) {
            abort(404);
        }

        if ((string) $commission->status !== 'paid') {
            $commission->status = 'paid';
            $commission->paid_at = now();
            $commission->save();
        }

        return back()->with('success', 'Comisión marcada como pagada.');
    }

    public function sellerProgress()
    {
        [$summary, $commissions, $frequentCustomers, $debtCustomers, $receivableOrders, $pendingDeliveryOrders] = $this->buildSellerProgressData();

        return view('sellerCommissions.progress', compact(
            'summary',
            'commissions',
            'frequentCustomers',
            'debtCustomers',
            'receivableOrders',
            'pendingDeliveryOrders'
        ));
    }

    public function sellerProgressPdf()
    {
        @ini_set('max_execution_time', '180');
        @set_time_limit(180);
        @ini_set('memory_limit', '512M');

        [$summary, $commissions] = $this->buildSellerProgressData();

        $html = view('sellerCommissions.pdf.progress', compact('summary', 'commissions'))->render();

        $monthLabel = $summary['month_start']->format('Y-m');
        $fileName = 'avance_comisiones_' . $monthLabel . '.pdf';

        try {
            $options = new Options();
            $options->set('isRemoteEnabled', true);
            $options->set('defaultFont', 'DejaVu Sans');

            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();

            $binary = $dompdf->output();
            if ($binary === '' || strlen($binary) < 128) {
                throw new \RuntimeException('Salida PDF vacia o invalida.');
            }

            return response($binary, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            ]);
        } catch (\Throwable $exception) {
            throw new \RuntimeException('[PDF] No se pudo generar el avance de comisiones en PDF. Intenta nuevamente.', 0, $exception);
        }
    }

    private function buildSellerProgressData(): array
    {
        $authUser = auth()->user();
        abort_unless($authUser?->hasStoreRole('seller') ?? false, 403);

        $tenantId = (int) ($authUser->tenant_id ?? 0);
        abort_if($tenantId <= 0, 403);

        $sellerId = (int) ($authUser->id ?? 0);
        $monthStart = now()->startOfMonth();
        $monthEnd = now()->endOfMonth();

        $baseQuery = SellerCommission::query()
            ->with(['order'])
            ->where('tenant_id', $tenantId)
            ->where('seller_user_id', $sellerId)
            ->whereBetween('created_at', [$monthStart, $monthEnd]);

        $commissions = (clone $baseQuery)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get();

        $currencyCode = (string) ($commissions->first()->currency_code ?? 'USD');
        $totalGenerated = (float) $commissions->sum('commission_amount');
        $totalPending = (float) $commissions->where('status', 'pending')->sum('commission_amount');
        $totalPaid = (float) $commissions->where('status', 'paid')->sum('commission_amount');

        $summary = [
            'seller_name' => (string) ($authUser->name ?? 'Vendedor'),
            'month_start' => $monthStart,
            'month_end' => $monthEnd,
            'currency_code' => $currencyCode,
            'total_generated' => $totalGenerated,
            'total_pending' => $totalPending,
            'total_paid' => $totalPaid,
            'orders_count' => (int) $commissions->pluck('sales_order_id')->filter()->unique()->count(),
        ];

        $sellerOrders = SalesOrder::query()
            ->with(['user', 'details', 'payments', 'retentions'])
            ->where('tenant_id', $tenantId)
            ->where('sales_rep_user_id', $sellerId)
            ->where('status', '!=', 2)
            ->orderByDesc('id')
            ->get()
            ->map(function (SalesOrder $order) {
                $order->total_items = (int) $order->details->sum('quantity');
                $order->order_total_amount = (float) $order->gross_total;
                $order->approved_paid_amount = (float) $order->payments->where('status', 1)->sum('amount');
                $order->registered_paid_amount = (float) $order->payments->where('status', '!=', 3)->sum('amount');
                $order->effective_paid_amount = max($order->approved_paid_amount, $order->registered_paid_amount);
                $order->retentions_amount = (float) $order->retentions->sum('retained_amount');
                $order->pending_amount = max(0, round($order->order_total_amount - $order->approved_paid_amount - $order->retentions_amount, 2));
                $order->pending_amount_effective = max(0, round($order->order_total_amount - $order->effective_paid_amount - $order->retentions_amount, 2));

                return $order;
            })
            ->values();

        $frequentCustomers = $sellerOrders
            ->filter(fn (SalesOrder $order) => (int) ($order->user_id ?? 0) > 0)
            ->groupBy('user_id')
            ->map(function ($orders) {
                /** @var \Illuminate\Support\Collection<int, SalesOrder> $orders */
                $first = $orders->first();

                return [
                    'customer_id' => (int) ($first->user_id ?? 0),
                    'customer_name' => (string) ($first->user->name ?? 'Cliente'),
                    'orders_count' => (int) $orders->count(),
                    'total_amount' => (float) $orders->sum('order_total_amount'),
                    'pending_amount' => (float) $orders->sum('pending_amount'),
                    'last_order_date' => optional($orders->max('created_at')),
                ];
            })
            ->sortByDesc(function (array $row) {
                return [$row['orders_count'], $row['total_amount']];
            })
            ->take(8)
            ->values();

        $debtCustomers = $sellerOrders
            ->filter(fn (SalesOrder $order) => (int) ($order->user_id ?? 0) > 0 && (float) ($order->pending_amount ?? 0) > 0)
            ->groupBy('user_id')
            ->map(function ($orders) {
                /** @var \Illuminate\Support\Collection<int, SalesOrder> $orders */
                $first = $orders->first();

                return [
                    'customer_id' => (int) ($first->user_id ?? 0),
                    'customer_name' => (string) ($first->user->name ?? 'Cliente'),
                    'orders_count' => (int) $orders->count(),
                    'pending_amount' => (float) $orders->sum('pending_amount'),
                ];
            })
            ->sortByDesc('pending_amount')
            ->take(8)
            ->values();

        $receivableOrders = $sellerOrders
            ->filter(fn (SalesOrder $order) => (float) ($order->pending_amount ?? 0) > 0)
            ->sortByDesc('id')
            ->take(10)
            ->values();

        $pendingDeliveryOrders = $sellerOrders
            ->filter(fn (SalesOrder $order) => (int) ($order->deliver_status ?? 0) === 0 && (float) ($order->pending_amount_effective ?? 0) <= 0.0001)
            ->sortByDesc('id')
            ->take(10)
            ->values();

        $summary['frequent_customers_count'] = (int) $frequentCustomers->count();
        $summary['debt_customers_count'] = (int) $debtCustomers->count();
        $summary['receivable_orders_count'] = (int) $receivableOrders->count();
        $summary['receivable_total'] = (float) $sellerOrders->sum('pending_amount');
        $summary['pending_delivery_orders_count'] = (int) $pendingDeliveryOrders->count();
        $summary['pending_delivery_total'] = (float) $pendingDeliveryOrders->sum('order_total_amount');

        return [$summary, $commissions, $frequentCustomers, $debtCustomers, $receivableOrders, $pendingDeliveryOrders];
    }
}
