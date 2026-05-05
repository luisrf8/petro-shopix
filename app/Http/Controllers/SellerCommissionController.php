<?php

namespace App\Http\Controllers;

use App\Models\SellerCommission;
use App\Models\User;
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
            ->filter(fn (User $candidate) => $candidate->hasStoreRole('seller'))
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
        if ((int) $seller->tenant_id !== $tenantId || !$seller->hasStoreRole('seller')) {
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
}
