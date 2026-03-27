<?php

namespace App\Http\Controllers;

use App\Models\Provider;
use App\Models\StoreExpense;
use Illuminate\Http\Request;

class StoreExpenseController extends Controller
{
    public function index(Request $request)
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        abort_if($tenantId <= 0, 403);

        $search = trim((string) $request->query('search', ''));
        $category = trim((string) $request->query('category', ''));
        $dateFrom = trim((string) $request->query('date_from', ''));
        $dateTo = trim((string) $request->query('date_to', ''));

        $baseQuery = StoreExpense::query()
            ->with('creator')
            ->where('tenant_id', $tenantId)
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($innerQuery) use ($search) {
                    $innerQuery
                        ->where('title', 'like', '%' . $search . '%')
                        ->orWhere('description', 'like', '%' . $search . '%')
                        ->orWhere('provider_name', 'like', '%' . $search . '%')
                        ->orWhere('payment_method', 'like', '%' . $search . '%');
                });
            })
            ->when($category !== '', function ($query) use ($category) {
                $query->where('category', $category);
            })
            ->when($dateFrom !== '', function ($query) use ($dateFrom) {
                $query->whereDate('spent_at', '>=', $dateFrom);
            })
            ->when($dateTo !== '', function ($query) use ($dateTo) {
                $query->whereDate('spent_at', '<=', $dateTo);
            });

        $expenses = (clone $baseQuery)
            ->orderByDesc('spent_at')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        $totalExpenses = (float) (clone $baseQuery)->sum('amount');
        $monthExpenses = (float) StoreExpense::query()
            ->where('tenant_id', $tenantId)
            ->whereBetween('spent_at', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
            ->sum('amount');
        $categories = StoreExpense::query()
            ->where('tenant_id', $tenantId)
            ->whereNotNull('category')
            ->where('category', '!=', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');
        $providers = Provider::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('storeExpenses.index', compact('expenses', 'search', 'category', 'dateFrom', 'dateTo', 'totalExpenses', 'monthExpenses', 'categories', 'providers'));
    }

    public function store(Request $request)
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        abort_if($tenantId <= 0, 403);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:2000',
            'amount' => 'required|numeric|min:0.01',
            'spent_at' => 'required|date',
            'payment_method' => 'nullable|string|max:100',
            'provider_name' => 'nullable|string|max:255',
            'status' => 'required|string|max:50',
        ]);

        StoreExpense::create([
            'tenant_id' => $tenantId,
            'title' => trim((string) $validated['title']),
            'category' => $validated['category'] ?? null,
            'description' => $validated['description'] ?? null,
            'amount' => $validated['amount'],
            'spent_at' => $validated['spent_at'],
            'payment_method' => $validated['payment_method'] ?? null,
            'provider_name' => $validated['provider_name'] ?? null,
            'status' => $validated['status'],
            'created_by' => auth()->id(),
        ]);

        return back()->with('success', 'Gasto registrado correctamente.');
    }

    public function update(Request $request, StoreExpense $expense)
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        abort_if((int) $expense->tenant_id !== $tenantId, 404);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:2000',
            'amount' => 'required|numeric|min:0.01',
            'spent_at' => 'required|date',
            'payment_method' => 'nullable|string|max:100',
            'provider_name' => 'nullable|string|max:255',
            'status' => 'required|string|max:50',
        ]);

        $expense->update($validated);

        return back()->with('success', 'Gasto actualizado correctamente.');
    }
}