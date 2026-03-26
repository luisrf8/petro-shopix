<?php

namespace App\Http\Controllers;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use App\Models\ProductVariant;
use App\Models\ProductVariantWarehouseStock;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderDetail;
use App\Models\Warehouse;
use App\Support\ImageStorage;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;



class PurchaseOrderController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $categories = Category::where('tenant_id', $user->tenant_id)->get();

        if ($categories->isEmpty()) {
            return redirect()->route('categories.index')
                ->with('warning', 'Debes crear al menos una categoría antes de registrar entradas de inventario.');
        }

        $productItems = Product::with(['images', 'variants'])
            ->where('tenant_id', $user->tenant_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $warehouses = Warehouse::where('tenant_id', $user->tenant_id)
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        return view('purchase', compact('categories', 'productItems', 'warehouses')); // Asegúrate de tener una vista para mostrar las categorías.
    }

    public function getVariants(Request $request)
    {
        $user = auth()->user();
        $itemIds = $request->input('item_ids');
    
        // Validar que se reciban IDs válidos
        if (empty($itemIds) || !is_array($itemIds)) {
            return response()->json(['error' => 'No se enviaron productos válidos.'], 400);
        }
    
        // Obtener variantes y agruparlas por producto
        $variants = ProductVariant::with(['product.images'])
            ->whereHas('product', function ($query) use ($itemIds, $user) {
                $query->whereIn('id', $itemIds)
                    ->where('tenant_id', $user->tenant_id);
            })
            ->get();

        $groupedVariants = $variants->groupBy('product_id')->map(function ($group, $productId) {
            $product = $group->first()->product;

            return [
                'product_id' => $productId,
                'product_name' => $product?->name,
                'product_image' => $product && $product->images->first()
                    ? (ImageStorage::url($product->images->first()->path) ?? asset('assets/img/shopix5.png'))
                    : asset('assets/img/shopix5.png'),
                'variants' => $group->map(function ($variant) {
                    return [
                        'id' => $variant->id,
                        'type' => $variant->type,
                        'size' => $variant->size,
                        'price' => $variant->price,
                        'stock' => $variant->stock,
                        'storage_description' => $variant->storage_description,
                        'shelf_life_description' => $variant->shelf_life_description,
                    ];
                }),
            ];
        })->values();
    
        // Devolver solo los datos esperados
        return response()->json($groupedVariants, 200);
    }

    public function getSuppliers(Request $request)
    {
        $itemId = $request->input('item_id');
        $variantId = $request->input('variant_id');

        $suppliers = Supplier::whereHas('items', function ($query) use ($itemId, $variantId) {
            $query->where('item_id', $itemId);

            if ($variantId) {
                $query->where('variant_id', $variantId);
            }
        })->get();

        return response()->json($suppliers);
    }

    public function store(Request $request)
    {
        $user = auth()->user();
        $itemsSelected = $request->input('itemsSelected');
        $purchaseDate = $request->input('purchase_date');
        $warehouseId = (int) $request->input('warehouse_id');

        $warehouse = Warehouse::where('tenant_id', $user->tenant_id)
            ->where('is_active', true)
            ->where('id', $warehouseId)
            ->first();

        if (!$warehouse) {
            return response()->json(['error' => 'Debes seleccionar un almacén válido.'], 422);
        }

        if (!empty($purchaseDate)) {
            try {
                $purchaseDate = \Carbon\Carbon::parse($purchaseDate)->toDateString();
            } catch (\Throwable $exception) {
                return response()->json(['error' => 'La fecha de compra no es válida.'], 422);
            }
        } else {
            $purchaseDate = now()->toDateString();
        }

        if (empty($itemsSelected) || !is_array($itemsSelected)) {
            return response()->json(['error' => 'No se enviaron productos válidos.'], 400);
        }

        $groupedData = [];

        foreach ($itemsSelected as $item) {
            $variantId = (int) data_get($item, 'variant.id', 0);
            $quantity = (int) data_get($item, 'quantity', 0);
            $price = (float) data_get($item, 'price', 0);
            $providers = data_get($item, 'providers', []);

            if ($variantId <= 0 || $quantity <= 0 || $price <= 0 || !is_array($providers) || empty($providers)) {
                return response()->json(['error' => 'Hay productos con datos incompletos (variante, cantidad, precio o proveedor).'], 422);
            }

            foreach ($providers as $providerNameRaw) {
                $providerName = trim((string) $providerNameRaw);
                if ($providerName === '') {
                    continue;
                }

                if (!isset($groupedData[$providerName])) {
                    $groupedData[$providerName] = [
                        'provider_id' => $providerName,
                        'details' => [],
                    ];
                }

                $groupedData[$providerName]['details'][] = [
                    'product_variant_id' => $variantId,
                    'quantity' => $quantity,
                    'price' => $price,
                ];
            }
        }

        if (empty($groupedData)) {
            return response()->json(['error' => 'Debes indicar al menos un proveedor válido.'], 422);
        }

        DB::beginTransaction();

        try {
            foreach ($groupedData as $orderData) {
                $orderPayload = [
                    'provider_id' => $orderData['provider_id'],
                    'warehouse_id' => $warehouse->id,
                    'date' => $purchaseDate,
                ];

                if (Schema::hasColumn('purchase_orders', 'tenant_id')) {
                    $orderPayload['tenant_id'] = $user->tenant_id;
                }

                $purchaseOrder = PurchaseOrder::create($orderPayload);

                foreach ($orderData['details'] as $detail) {
                    $productVariant = ProductVariant::with('product')->find($detail['product_variant_id']);

                    if (!$productVariant || !$productVariant->product || (int) $productVariant->product->tenant_id !== (int) $user->tenant_id) {
                        throw new \RuntimeException('Se intentó registrar una variante inválida para esta tienda.');
                    }

                    $detailPayload = [
                        'purchase_order_id' => $purchaseOrder->id,
                        'product_variant_id' => $detail['product_variant_id'],
                        'quantity' => $detail['quantity'],
                        'price' => $detail['price'],
                        'amount' => $detail['price'] * $detail['quantity'],
                    ];

                    if (Schema::hasColumn('purchase_order_detail', 'tenant_id')) {
                        $detailPayload['tenant_id'] = $user->tenant_id;
                    }

                    PurchaseOrderDetail::create($detailPayload);

                    $productVariant->stock += $detail['quantity'];
                    $productVariant->save();

                    $warehouseStock = ProductVariantWarehouseStock::firstOrNew([
                        'tenant_id' => $user->tenant_id,
                        'warehouse_id' => $warehouse->id,
                        'product_variant_id' => $detail['product_variant_id'],
                    ]);

                    $warehouseStock->quantity = (float) ($warehouseStock->quantity ?? 0) + (float) $detail['quantity'];
                    $warehouseStock->save();
                }
            }

            DB::commit();
            return response()->json(['message' => 'Entrada de inventario registrada y stock actualizado correctamente.'], 200);
        } catch (\Throwable $exception) {
            DB::rollBack();
            return response()->json([
                'error' => 'No se pudo registrar la entrada de inventario.',
                'detail' => $exception->getMessage(),
            ], 500);
        }
    }

    public function viewOrders()
    {
        $user = auth()->user();
        $purchaseOrders = PurchaseOrder::with(['warehouse', 'detalles', 'detalles.productVariant.product.images'])
        ->where('tenant_id', $user->tenant_id)
        ->orderBy('date', 'desc')
        ->get();

        foreach ($purchaseOrders as $order) {
            $order->total_items = $order->detalles->sum('quantity');
            $order->total_amount = $order->detalles->sum('amount');
            $order->total_variants = $order->detalles->count();
            $firstDetail = $order->detalles->first();
            $order->preview_image = $firstDetail
                && $firstDetail->productVariant
                && $firstDetail->productVariant->product
                && $firstDetail->productVariant->product->images->first()
                    ? (ImageStorage::url($firstDetail->productVariant->product->images->first()->path) ?? asset('assets/img/shopix5.png'))
                    : asset('assets/img/shopix5.png');
        }

        return view('purchaseOrders', compact('purchaseOrders'));
    }
    
    public function showByOrder($id)
    {
        $user = auth()->user();
        $order = PurchaseOrder::with(['warehouse', 'detalles', 'detalles.productVariant', 'detalles.productVariant.product.images'])
            ->where('tenant_id', $user->tenant_id)
            ->findOrFail($id);

        $order->total_items = $order->detalles->sum('quantity');
        $order->total_amount = $order->detalles->sum('amount');
        $order->total_variants = $order->detalles->count();

        return view('orderDetail', compact('order'));
    }
}
