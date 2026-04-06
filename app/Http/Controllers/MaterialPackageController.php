<?php

namespace App\Http\Controllers;

use App\Models\MaterialPackage;
use App\Models\MaterialPackageItem;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\TenantPlanPayment;

class MaterialPackageController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $isBasicPlanTenant = $this->isBasicPlanTenant((int) $user->tenant_id);

        $productItems = Product::with(['images', 'variants'])
            ->where('tenant_id', $user->tenant_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $packages = MaterialPackage::with(['items', 'items.variant', 'items.variant.product', 'items.variant.product.images'])
            ->where('tenant_id', $user->tenant_id)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('materials', compact('productItems', 'packages', 'isBasicPlanTenant'));
    }

    public function store(Request $request)
    {
        $user = auth()->user();

        if ($this->isBasicPlanTenant((int) $user->tenant_id)) {
            return redirect()->route('materials.index')
                ->withErrors(['items' => 'El plan Básico no permite crear listas de materiales.']);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'package_price' => 'nullable|numeric|min:0.01',
            'items' => 'required|array|min:1',
            'items.*.variant_id' => 'nullable|integer|exists:product_variants,id',
            'items.*.product_id' => 'nullable|integer|exists:products,id',
            'items.*.selection_mode' => ['required', Rule::in(['variant', 'product'])],
            'items.*.quantity' => 'required|numeric|min:0.01',
        ]);

        $itemRows = collect($validated['items'])
            ->map(function ($row, $index) use ($user) {
                $selectionMode = (string) ($row['selection_mode'] ?? 'variant');
                $variantId = isset($row['variant_id']) ? (int) $row['variant_id'] : 0;
                $productId = isset($row['product_id']) ? (int) $row['product_id'] : 0;

                if ($selectionMode === 'variant' && $variantId <= 0) {
                    throw ValidationException::withMessages([
                        "items.$index.variant_id" => 'Debes seleccionar una variante en modo por variante.',
                    ]);
                }

                if ($selectionMode === 'product' && $productId <= 0) {
                    throw ValidationException::withMessages([
                        "items.$index.product_id" => 'Debes seleccionar un producto en modo por producto.',
                    ]);
                }

                if ($selectionMode === 'product' && $variantId <= 0 && $productId > 0) {
                    $variantId = (int) ProductVariant::query()
                        ->where('product_id', $productId)
                        ->whereHas('product', function ($query) use ($user) {
                            $query->where('tenant_id', $user->tenant_id);
                        })
                        ->orderByDesc('stock')
                        ->orderBy('id')
                        ->value('id');

                    if ($variantId <= 0) {
                        throw ValidationException::withMessages([
                            "items.$index.product_id" => 'El producto seleccionado no tiene variantes disponibles para este tenant.',
                        ]);
                    }
                }

                if ($selectionMode === 'variant' && $variantId > 0) {
                    $validVariantForTenant = ProductVariant::query()
                        ->whereKey($variantId)
                        ->whereHas('product', function ($query) use ($user) {
                            $query->where('tenant_id', $user->tenant_id);
                        })
                        ->exists();

                    if (!$validVariantForTenant) {
                        throw ValidationException::withMessages([
                            "items.$index.variant_id" => 'La variante seleccionada no pertenece a esta tienda.',
                        ]);
                    }
                }

                return [
                    'variant_id' => $variantId,
                    'quantity' => (float) $row['quantity'],
                    'selection_mode' => $selectionMode,
                ];
            })
            ->groupBy(function ($row) {
                return $row['selection_mode'] . ':' . $row['variant_id'];
            })
            ->map(function ($rows) {
                $first = $rows->first();
                return [
                    'variant_id' => (int) ($first['variant_id'] ?? 0),
                    'quantity' => $rows->sum('quantity'),
                    'selection_mode' => (string) ($first['selection_mode'] ?? 'variant'),
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
                    'selection_mode' => $item['selection_mode'] ?? 'variant',
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

    private function isBasicPlanTenant(int $tenantId): bool
    {
        $latestPaid = TenantPlanPayment::with('plan')
            ->where('tenant_id', $tenantId)
            ->where('status', 'paid')
            ->orderByDesc('paid_at')
            ->orderByDesc('id')
            ->first();

        $planName = Str::lower(Str::ascii((string) ($latestPaid?->plan?->name ?? '')));

        return Str::contains($planName, ['basico', 'basic']);
    }
}
