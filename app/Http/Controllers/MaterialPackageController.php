<?php

namespace App\Http\Controllers;

use App\Models\MaterialPackage;
use App\Models\MaterialPackageItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MaterialPackageController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        $productItems = Product::with(['images', 'variants'])
            ->where('tenant_id', $user->tenant_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $packages = MaterialPackage::with(['items', 'items.variant', 'items.variant.product', 'items.variant.product.images'])
            ->where('tenant_id', $user->tenant_id)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('materials', compact('productItems', 'packages'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'package_price' => 'nullable|numeric|min:0.01',
            'items' => 'required|array|min:1',
            'items.*.variant_id' => 'required|integer|exists:product_variants,id',
            'items.*.quantity' => 'required|numeric|min:0.01',
        ]);

        $itemRows = collect($validated['items'])
            ->map(function ($row) {
                return [
                    'variant_id' => (int) $row['variant_id'],
                    'quantity' => (float) $row['quantity'],
                ];
            })
            ->groupBy('variant_id')
            ->map(function ($rows, $variantId) {
                return [
                    'variant_id' => (int) $variantId,
                    'quantity' => $rows->sum('quantity'),
                ];
            })
            ->values();

        $variantIds = $itemRows->pluck('variant_id')->all();

        $validVariantIds = ProductVariant::query()
            ->whereIn('id', $variantIds)
            ->whereHas('product', function ($query) use ($user) {
                $query->where('tenant_id', $user->tenant_id);
            })
            ->pluck('id')
            ->all();

        if (count($validVariantIds) !== count($variantIds)) {
            return back()->withErrors(['items' => 'Una o más variantes no pertenecen a esta tienda.'])->withInput();
        }

        DB::beginTransaction();

        try {
            $package = MaterialPackage::create([
                'tenant_id' => $user->tenant_id,
                'name' => $validated['name'],
                'description' => $validated['description'] ?? null,
                'discount_percentage' => (float) ($validated['discount_percentage'] ?? 0),
                'package_price' => isset($validated['package_price']) ? (float) $validated['package_price'] : null,
                'is_active' => true,
            ]);

            foreach ($itemRows as $item) {
                MaterialPackageItem::create([
                    'material_package_id' => $package->id,
                    'product_variant_id' => $item['variant_id'],
                    'quantity' => $item['quantity'],
                    'discount_percentage' => 0,
                ]);
            }

            DB::commit();
            return redirect()->route('materials.index')->with('success', 'Paquete creado correctamente.');
        } catch (\Throwable $exception) {
            DB::rollBack();
            return back()->withErrors(['items' => 'No se pudo crear el paquete.'])->withInput();
        }
    }

    public function toggleStatus($id)
    {
        $user = auth()->user();
        $package = MaterialPackage::where('tenant_id', $user->tenant_id)->findOrFail($id);

        $package->is_active = !$package->is_active;
        $package->save();

        return redirect()->route('materials.index')->with('success', 'Estado del paquete actualizado.');
    }

    public function generateCodes($id)
    {
        $user = auth()->user();
        $package = MaterialPackage::where('tenant_id', $user->tenant_id)->findOrFail($id);

        if (empty($package->qr_code)) {
            $package->qr_code = $this->generateUniquePackageCode('QRP');
        }

        if (empty($package->barcode)) {
            $package->barcode = $this->generateUniquePackageCode('BCP');
        }

        $package->save();

        return response()->json([
            'success' => true,
            'qr_code' => $package->qr_code,
            'barcode' => $package->barcode,
        ]);
    }

    private function generateUniquePackageCode(string $prefix): string
    {
        do {
            $value = $prefix . '-' . strtoupper(Str::random(10));
        } while (MaterialPackage::where('qr_code', $value)->orWhere('barcode', $value)->exists());

        return $value;
    }
}
