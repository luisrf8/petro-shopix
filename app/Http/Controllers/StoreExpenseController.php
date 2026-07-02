<?php

namespace App\Http\Controllers;

use App\Models\Provider;
use App\Models\StoreExpense;
use App\Support\TenantCurrency;
use App\Models\DollarRate;
use App\Models\EuroRate;
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

        $totalExpenses = (float) (clone $baseQuery)->sum('amount_bs');
        $monthExpenses = (float) StoreExpense::query()
            ->where('tenant_id', $tenantId)
            ->whereBetween('spent_at', [now()->startOfMonth()->toDateString(), now()->endOfMonth()->toDateString()])
            ->sum('amount_bs');
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

        $usdRateToBs = (float) (DollarRate::query()->where('tenant_id', $tenantId)->latest('created_at')->value('rate') ?: 0);
        $euroRateToBs = (float) (EuroRate::query()->where('tenant_id', $tenantId)->latest('created_at')->value('rate') ?: 0);

        return view('storeExpenses.index', compact('expenses', 'search', 'category', 'dateFrom', 'dateTo', 'totalExpenses', 'monthExpenses', 'categories', 'providers', 'usdRateToBs', 'euroRateToBs'));
    }

    public function store(Request $request)
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        abort_if($tenantId <= 0, 403);

        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'category' => 'nullable|string|max:100',
            'description' => 'nullable|string|max:2000',
            'currency_code' => 'required|string|in:USD,EUR,BS,VES',
            'amount_original' => 'required|numeric|min:0.01',
            'exchange_rate_to_bs' => 'nullable|numeric|min:0.0001',
            'spent_at' => 'required|date',
            'payment_method' => 'nullable|string|max:100',
            'provider_name' => 'nullable|string|max:255',
            'status' => 'required|string|max:50',
        ]);

        $currencyCode = TenantCurrency::normalizeCurrencyCode((string) $validated['currency_code']);
        $amountOriginal = round((float) $validated['amount_original'], 4);
        $exchangeRateToBs = $currencyCode === 'BS'
            ? 1.0
            : round((float) ($validated['exchange_rate_to_bs'] ?? 0), 4);

        if ($currencyCode !== 'BS' && $exchangeRateToBs <= 0) {
            $exchangeRateToBs = TenantCurrency::resolveRateToBs($tenantId, $currencyCode);
        }

        if ($currencyCode !== 'BS' && $exchangeRateToBs <= 0) {
            return back()->withErrors([
                'exchange_rate_to_bs' => 'Debes indicar una tasa válida o usar la tasa interna para convertir el gasto a bolívares.',
            ])->withInput();
        }

        $amountBs = round($amountOriginal * $exchangeRateToBs, 2);

        StoreExpense::create([
            'tenant_id' => $tenantId,
            'title' => trim((string) $validated['title']),
            'category' => $validated['category'] ?? null,
            'description' => $validated['description'] ?? null,
            'amount' => $amountBs,
            'currency_code' => $currencyCode,
            'amount_original' => $amountOriginal,
            'exchange_rate_to_bs' => $exchangeRateToBs,
            'amount_bs' => $amountBs,
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
            'currency_code' => 'required|string|in:USD,EUR,BS,VES',
            'amount_original' => 'required|numeric|min:0.01',
            'exchange_rate_to_bs' => 'nullable|numeric|min:0.0001',
            'spent_at' => 'required|date',
            'payment_method' => 'nullable|string|max:100',
            'provider_name' => 'nullable|string|max:255',
            'status' => 'required|string|max:50',
        ]);

        $currencyCode = TenantCurrency::normalizeCurrencyCode((string) $validated['currency_code']);
        $amountOriginal = round((float) $validated['amount_original'], 4);
        $exchangeRateToBs = $currencyCode === 'BS'
            ? 1.0
            : round((float) ($validated['exchange_rate_to_bs'] ?? 0), 4);

        if ($currencyCode !== 'BS' && $exchangeRateToBs <= 0) {
            $exchangeRateToBs = TenantCurrency::resolveRateToBs($tenantId, $currencyCode);
        }

        if ($currencyCode !== 'BS' && $exchangeRateToBs <= 0) {
            return back()->withErrors([
                'exchange_rate_to_bs' => 'Debes indicar una tasa válida o usar la tasa interna para convertir el gasto a bolívares.',
            ])->withInput();
        }

        $expense->update([
            'title' => trim((string) $validated['title']),
            'category' => $validated['category'] ?? null,
            'description' => $validated['description'] ?? null,
            'amount' => round($amountOriginal * $exchangeRateToBs, 2),
            'currency_code' => $currencyCode,
            'amount_original' => $amountOriginal,
            'exchange_rate_to_bs' => $exchangeRateToBs,
            'amount_bs' => round($amountOriginal * $exchangeRateToBs, 2),
            'spent_at' => $validated['spent_at'],
            'payment_method' => $validated['payment_method'] ?? null,
            'provider_name' => $validated['provider_name'] ?? null,
            'status' => $validated['status'],
        ]);

        return back()->with('success', 'Gasto actualizado correctamente.');
    }
}