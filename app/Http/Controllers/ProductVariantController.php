<?php

namespace App\Http\Controllers;

use App\Models\ProductVariant;
use Illuminate\Http\Request;

class ProductVariantController extends Controller
{
    public function index($productId)
    {
        $variants = ProductVariant::where('product_id', $productId)->get();
        return view('product.variants.index', compact('variants'));
    }

    public function create($productId)
    {
        return view('product.variants.create', compact('productId'));
    }

    public function store(Request $request)
    {
        DB::raw("SET @user_id = " . auth()->id());

        $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'variants' => 'required|array',
            'variants.*.size' => 'required|string|max:10',
            'variants.*.price' => 'required|numeric',
            'variants.*.stock' => 'required|integer',
        ]);
    
        foreach ($request->variants as $variant) {
            ProductVariant::create([
                'product_id' => $request->product_id,
                'size' => $variant['size'],
                'price' => $variant['price'],
                'stock' => $variant['stock'],
            ]);
        }
    
        return response()->json(['success' => true, 'message' => 'Variantes guardadas exitosamente.']);
    }

    public function edit(ProductVariant $productVariant)
    {
        return view('product.variants.edit', compact('productVariant'));
    }

    public function update(Request $request, ProductVariant $productVariant)
    {
        DB::raw("SET @user_id = " . auth()->id());

        $request->validate([
            'size' => 'required|string|max:10',
            'price' => 'required|numeric',
            'stock' => 'required|integer',
        ]);
    
        // Actualizar la variante con los datos proporcionados
        $productVariant->update($request->only(['size', 'price', 'stock']));
    
        // Responder con JSON para las solicitudes AJAX
        return response()->json([
            'success' => true,
            'message' => 'Variante actualizada exitosamente.',
            'variant' => $productVariant
        ]);
    }

    public function destroy(ProductVariant $productVariant)
    {
        DB::raw("SET @user_id = " . auth()->id());

        $productVariant->delete();

        return redirect()->route('products.show', $productVariant->product_id)
                         ->with('success', 'Variante eliminada exitosamente.');
    }
}

