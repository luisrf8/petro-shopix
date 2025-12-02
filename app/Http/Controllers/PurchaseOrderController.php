<?php

namespace App\Http\Controllers;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderDetail;



class PurchaseOrderController extends Controller
{
    public function index()
    {
        $categories = Category::all();
        $productItems = Product::all();
        return view('purchase', compact('categories', 'productItems')); // Asegúrate de tener una vista para mostrar las categorías.
    }

    public function getVariants(Request $request)
    {
        $itemIds = $request->input('item_ids');
    
        // Validar que se reciban IDs válidos
        if (empty($itemIds) || !is_array($itemIds)) {
            return response()->json(['error' => 'No se enviaron productos válidos.'], 400);
        }
    
        // Obtener variantes y agruparlas por producto
        $variants = ProductVariant::whereIn('product_id', $itemIds)->get();
        $groupedVariants = $variants->groupBy('product_id')->map(function ($group, $productId) {
            return [
                'product_id' => $productId,
                'variants' => $group->map(function ($variant) {
                    return [
                        'id' => $variant->id,
                        'type' => $variant->type,
                        'size' => $variant->size,
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
        // Paso 1: Validar los datos
        DB::raw("SET @user_id = " . auth()->id());

        $itemsSelected = $request->itemsSelected;
        if (empty($itemsSelected) || !is_array($itemsSelected)) {
            return response()->json(['error' => 'No se enviaron productos válidos.'], 400);
        }
    
        // Paso 2: Agrupar y sumar cantidades por provider_id
        $groupedData = [];
        foreach ($itemsSelected as $item) {
            if (!isset($item['providers']) || !is_array($item['providers'])) {
                return response()->json(['error' => 'Los proveedores no están definidos correctamente.'], 400);
            }
        
            foreach ($item['providers'] as $providerId) {
                if (!isset($groupedData[$providerId])) {
                    $groupedData[$providerId] = [
                        'provider_id' => $providerId,
                        'total_quantity' => 0,
                        'details' => [],
                    ];
                }
        
                // Sumar las cantidades y agregar detalles
                $groupedData[$providerId]['total_quantity'] += $item['quantity'];
                $groupedData[$providerId]['details'][] = [
                    'product_variant_id' => $item['variant']['id'],
                    'quantity' => $item['quantity'],
                    'price' => $item['price'], // Asegúrate de incluir el 'price' aquí
                ];
            }
        }
        // Paso 3: Crear las órdenes de compra y sus detalles
        foreach ($groupedData as $providerId => $orderData) {
            // Crear la orden principal
            $purchaseOrder = PurchaseOrder::create([
                'provider_id' => $orderData['provider_id'],
                'date' => now()->toDateString(),
                'total' => $orderData['total_quantity'],
                'created_at' => now(),
                'updated_at' => now(),
            ]);
    
            // Crear los detalles de la orden
            foreach ($orderData['details'] as $detail) {
                PurchaseOrderDetail::create([
                    'purchase_order_id' => $purchaseOrder->id,
                    'product_variant_id' => $detail['product_variant_id'],
                    'quantity' => $detail['quantity'],
                    'amount' => $detail['price'],
                    'price' => $detail['price'],
                    // 'price' => isset($detail['price']) ? $detail['price'] : 0,
                ]);

                // Actualizar el stock de la variante del producto
                $productVariant = ProductVariant::find($detail['product_variant_id']);
                if ($productVariant) {
                    $productVariant->stock += $detail['quantity']; // Sumar al stock existente
                    $productVariant->save();
                }
            }
        }
    
        // Retornar respuesta exitosa
        return response()->json(['message' => 'Orden de compra creada exitosamente y stock actualizado.'], 200);
    }

    public function viewOrders()
    {
        // Obtener las órdenes de compra ordenadas por fecha
        $purchaseOrders = PurchaseOrder::with('detalles')
        ->orderBy('date', 'desc')
        ->get();
        foreach ($purchaseOrders as $order) {
            $order->total_items = $order->detalles->sum('quantity'); // Sumar la cantidad de productos en los detalles
        }
        // Formatear los datos para enviarlos a la vista si es necesario
        return view('purchaseOrders', compact('purchaseOrders'));
    }
    
    public function showByOrder($id)
    {
        // Busca la orden con sus relaciones
        $order = PurchaseOrder::with(['detalles', 'detalles.productVariant', 'detalles.productVariant.product'])->find($id);
        // Devuelve la vista con los datos de la orden
        return view('orderDetail', compact('order'));
    }
}
