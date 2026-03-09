<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariantWarehouseStock;
use App\Models\Warehouse;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $warehouses = Warehouse::where('tenant_id', $user->tenant_id)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        $products = Product::with(['variants'])
            ->where('tenant_id', $user->tenant_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $stocks = ProductVariantWarehouseStock::where('tenant_id', $user->tenant_id)
            ->get()
            ->keyBy(function ($item) {
                return $item->warehouse_id . '_' . $item->product_variant_id;
            });

        return view('warehouses', compact('warehouses', 'products', 'stocks'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
        ]);

        Warehouse::create([
            'tenant_id' => $user->tenant_id,
            'name' => trim($validated['name']),
            'is_default' => false,
            'is_active' => true,
        ]);

        return redirect()->route('warehouses.index')->with('success', 'Almacén creado correctamente.');
    }
}
