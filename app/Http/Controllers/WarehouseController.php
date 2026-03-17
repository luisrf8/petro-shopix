<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductVariantWarehouseStock;
use App\Models\Warehouse;
use App\Models\WarehouseMovement;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

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

        $variants = ProductVariant::with('product')
            ->whereHas('product', function ($query) use ($user) {
                $query->where('tenant_id', $user->tenant_id)
                    ->where('is_active', true);
            })
            ->orderBy('size')
            ->get();

        $movements = WarehouseMovement::with(['variant.product', 'sourceWarehouse', 'destinationWarehouse', 'user'])
            ->where('tenant_id', $user->tenant_id)
            ->orderByDesc('moved_at')
            ->orderByDesc('id')
            ->limit(50)
            ->get();

        $movementTypes = WarehouseMovement::typeOptions();

        return view('warehouses', compact('warehouses', 'products', 'stocks', 'variants', 'movements', 'movementTypes'));
    }

    public function store(Request $request): RedirectResponse
    {
        $user = auth()->user();

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('warehouses', 'name')->where(fn($query) => $query->where('tenant_id', $user->tenant_id)),
            ],
            'is_default' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        DB::transaction(function () use ($user, $validated) {
            $shouldBeDefault = (bool) ($validated['is_default'] ?? false);

            if ($shouldBeDefault) {
                Warehouse::where('tenant_id', $user->tenant_id)->update(['is_default' => false]);
            }

            Warehouse::create([
                'tenant_id' => $user->tenant_id,
                'name' => trim($validated['name']),
                'is_default' => $shouldBeDefault,
                'is_active' => array_key_exists('is_active', $validated) ? (bool) $validated['is_active'] : true,
            ]);
        });

        return redirect()->route('warehouses.index')->with('success', 'Almacén creado correctamente.');
    }

    public function update(Request $request, Warehouse $warehouse): RedirectResponse
    {
        $user = auth()->user();
        $warehouse = $this->warehouseForTenant($warehouse->id, (int) $user->tenant_id);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('warehouses', 'name')
                    ->ignore($warehouse->id)
                    ->where(fn($query) => $query->where('tenant_id', $user->tenant_id)),
            ],
            'is_default' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $isDefault = (bool) ($validated['is_default'] ?? false);
        $isActive = (bool) ($validated['is_active'] ?? false);

        if ($isDefault && !$isActive) {
            return redirect()->route('warehouses.index')->withErrors('El almacén principal debe permanecer activo.');
        }

        if (!$isDefault) {
            $hasAnotherDefault = Warehouse::where('tenant_id', $warehouse->tenant_id)
                ->where('id', '!=', $warehouse->id)
                ->where('is_default', true)
                ->exists();

            if (!$hasAnotherDefault) {
                return redirect()->route('warehouses.index')->withErrors('Debes mantener al menos un almacén principal.');
            }
        }

        DB::transaction(function () use ($warehouse, $validated, $isDefault, $isActive) {
            if ($isDefault) {
                Warehouse::where('tenant_id', $warehouse->tenant_id)->update(['is_default' => false]);
            }

            $warehouse->update([
                'name' => trim($validated['name']),
                'is_default' => $isDefault,
                'is_active' => $isActive,
            ]);
        });

        return redirect()->route('warehouses.index')->with('success', 'Almacén actualizado correctamente.');
    }

    public function storeMovement(Request $request): RedirectResponse
    {
        $user = auth()->user();

        $validated = $this->validateMovement($request);

        DB::transaction(function () use ($validated, $user) {
            $this->applyMovementData($validated, (int) $user->tenant_id);

            WarehouseMovement::create([
                'tenant_id' => $user->tenant_id,
                'product_variant_id' => $validated['product_variant_id'],
                'movement_type' => $validated['movement_type'],
                'source_warehouse_id' => $validated['source_warehouse_id'],
                'destination_warehouse_id' => $validated['destination_warehouse_id'],
                'quantity' => $validated['quantity'],
                'notes' => $validated['notes'],
                'moved_at' => $validated['moved_at'],
                'user_id' => $user->id,
            ]);
        });

        return redirect()->route('warehouses.index')->with('success', 'Movimiento registrado correctamente.');
    }

    public function updateMovement(Request $request, WarehouseMovement $movement): RedirectResponse
    {
        $user = auth()->user();
        $movement = $this->movementForTenant($movement->id, (int) $user->tenant_id);
        $validated = $this->validateMovement($request);

        DB::transaction(function () use ($movement, $validated, $user) {
            $this->revertMovement($movement, (int) $user->tenant_id);
            $this->applyMovementData($validated, (int) $user->tenant_id);

            $movement->update([
                'product_variant_id' => $validated['product_variant_id'],
                'movement_type' => $validated['movement_type'],
                'source_warehouse_id' => $validated['source_warehouse_id'],
                'destination_warehouse_id' => $validated['destination_warehouse_id'],
                'quantity' => $validated['quantity'],
                'notes' => $validated['notes'],
                'moved_at' => $validated['moved_at'],
                'user_id' => $user->id,
            ]);
        });

        return redirect()->route('warehouses.index')->with('success', 'Movimiento actualizado correctamente.');
    }

    private function validateMovement(Request $request): array
    {
        $user = auth()->user();

        $validated = $request->validate([
            'product_variant_id' => ['required', 'integer'],
            'movement_type' => ['required', Rule::in(array_keys(WarehouseMovement::typeOptions()))],
            'source_warehouse_id' => ['nullable', 'integer'],
            'destination_warehouse_id' => ['nullable', 'integer'],
            'quantity' => ['required', 'numeric', 'gt:0'],
            'moved_at' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $variant = ProductVariant::where('id', $validated['product_variant_id'])
            ->whereHas('product', function ($query) use ($user) {
                $query->where('tenant_id', $user->tenant_id);
            })
            ->first();

        if (!$variant) {
            throw ValidationException::withMessages([
                'product_variant_id' => 'La variante seleccionada no pertenece a tu tienda.',
            ]);
        }

        $sourceId = $validated['source_warehouse_id'] ? (int) $validated['source_warehouse_id'] : null;
        $destinationId = $validated['destination_warehouse_id'] ? (int) $validated['destination_warehouse_id'] : null;

        if ($sourceId) {
            $this->warehouseForTenant($sourceId, (int) $user->tenant_id);
        }

        if ($destinationId) {
            $this->warehouseForTenant($destinationId, (int) $user->tenant_id);
        }

        if ($validated['movement_type'] === WarehouseMovement::TYPE_ENTRY && !$destinationId) {
            throw ValidationException::withMessages([
                'destination_warehouse_id' => 'La entrada requiere un almacén destino.',
            ]);
        }

        if ($validated['movement_type'] === WarehouseMovement::TYPE_EXIT && !$sourceId) {
            throw ValidationException::withMessages([
                'source_warehouse_id' => 'La salida requiere un almacén origen.',
            ]);
        }

        if ($validated['movement_type'] === WarehouseMovement::TYPE_TRANSFER) {
            if (!$sourceId || !$destinationId) {
                throw ValidationException::withMessages([
                    'source_warehouse_id' => 'La transferencia requiere origen y destino.',
                ]);
            }

            if ($sourceId === $destinationId) {
                throw ValidationException::withMessages([
                    'destination_warehouse_id' => 'El origen y el destino deben ser diferentes.',
                ]);
            }
        }

        if ($validated['movement_type'] === WarehouseMovement::TYPE_ENTRY) {
            $sourceId = null;
        }

        if ($validated['movement_type'] === WarehouseMovement::TYPE_EXIT) {
            $destinationId = null;
        }

        $validated['quantity'] = round((float) $validated['quantity'], 2);
        $validated['product_variant_id'] = (int) $validated['product_variant_id'];
        $validated['source_warehouse_id'] = $sourceId;
        $validated['destination_warehouse_id'] = $destinationId;
        $validated['notes'] = trim((string) ($validated['notes'] ?? '')) ?: null;

        return $validated;
    }

    private function applyMovementData(array $movement, int $tenantId): void
    {
        $quantity = (float) $movement['quantity'];

        if ($movement['movement_type'] === WarehouseMovement::TYPE_ENTRY) {
            $this->adjustWarehouseStock($tenantId, (int) $movement['destination_warehouse_id'], (int) $movement['product_variant_id'], $quantity);
            $this->adjustVariantStock((int) $movement['product_variant_id'], $quantity);
            return;
        }

        if ($movement['movement_type'] === WarehouseMovement::TYPE_EXIT) {
            $this->adjustWarehouseStock($tenantId, (int) $movement['source_warehouse_id'], (int) $movement['product_variant_id'], -$quantity);
            $this->adjustVariantStock((int) $movement['product_variant_id'], -$quantity);
            return;
        }

        $this->adjustWarehouseStock($tenantId, (int) $movement['source_warehouse_id'], (int) $movement['product_variant_id'], -$quantity);
        $this->adjustWarehouseStock($tenantId, (int) $movement['destination_warehouse_id'], (int) $movement['product_variant_id'], $quantity);
    }

    private function revertMovement(WarehouseMovement $movement, int $tenantId): void
    {
        $payload = [
            'product_variant_id' => (int) $movement->product_variant_id,
            'movement_type' => $movement->movement_type,
            'source_warehouse_id' => $movement->source_warehouse_id,
            'destination_warehouse_id' => $movement->destination_warehouse_id,
            'quantity' => (float) $movement->quantity,
        ];

        if ($payload['movement_type'] === WarehouseMovement::TYPE_ENTRY) {
            $this->adjustWarehouseStock($tenantId, (int) $payload['destination_warehouse_id'], (int) $payload['product_variant_id'], -$payload['quantity']);
            $this->adjustVariantStock((int) $payload['product_variant_id'], -$payload['quantity']);
            return;
        }

        if ($payload['movement_type'] === WarehouseMovement::TYPE_EXIT) {
            $this->adjustWarehouseStock($tenantId, (int) $payload['source_warehouse_id'], (int) $payload['product_variant_id'], $payload['quantity']);
            $this->adjustVariantStock((int) $payload['product_variant_id'], $payload['quantity']);
            return;
        }

        $this->adjustWarehouseStock($tenantId, (int) $payload['source_warehouse_id'], (int) $payload['product_variant_id'], $payload['quantity']);
        $this->adjustWarehouseStock($tenantId, (int) $payload['destination_warehouse_id'], (int) $payload['product_variant_id'], -$payload['quantity']);
    }

    private function adjustWarehouseStock(int $tenantId, int $warehouseId, int $variantId, float $delta): void
    {
        $warehouseStock = ProductVariantWarehouseStock::where('tenant_id', $tenantId)
            ->where('warehouse_id', $warehouseId)
            ->where('product_variant_id', $variantId)
            ->lockForUpdate()
            ->first();

        if (!$warehouseStock) {
            $warehouseStock = new ProductVariantWarehouseStock([
                'tenant_id' => $tenantId,
                'warehouse_id' => $warehouseId,
                'product_variant_id' => $variantId,
                'quantity' => 0,
            ]);
        }

        $newQuantity = round((float) $warehouseStock->quantity + $delta, 2);
        if ($newQuantity < 0) {
            throw ValidationException::withMessages([
                'quantity' => 'La operación deja stock negativo en uno de los almacenes.',
            ]);
        }

        $warehouseStock->quantity = $newQuantity;
        $warehouseStock->save();
    }

    private function adjustVariantStock(int $variantId, float $delta): void
    {
        $variant = ProductVariant::where('id', $variantId)->lockForUpdate()->firstOrFail();
        $newStock = round((float) $variant->stock + $delta, 2);

        if ($newStock < 0) {
            throw ValidationException::withMessages([
                'quantity' => 'La operación deja stock general negativo para la variante seleccionada.',
            ]);
        }

        $variant->stock = $newStock;
        $variant->save();
    }

    private function warehouseForTenant(int $warehouseId, int $tenantId): Warehouse
    {
        $warehouse = Warehouse::where('tenant_id', $tenantId)->where('id', $warehouseId)->first();

        if (!$warehouse) {
            throw (new ModelNotFoundException())->setModel(Warehouse::class, [$warehouseId]);
        }

        return $warehouse;
    }

    private function movementForTenant(int $movementId, int $tenantId): WarehouseMovement
    {
        $movement = WarehouseMovement::where('tenant_id', $tenantId)->where('id', $movementId)->first();

        if (!$movement) {
            throw (new ModelNotFoundException())->setModel(WarehouseMovement::class, [$movementId]);
        }

        return $movement;
    }
}
