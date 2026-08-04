<?php

namespace App\Http\Controllers;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Http\Request;
use App\Models\Provider;
use App\Models\ProductVariant;
use App\Models\ProductVariantWarehouseStock;
use App\Models\PurchaseOrder;
use App\Models\PurchaseOrderDetail;
use App\Models\PurchaseOrderConsumption;
use App\Models\PurchaseVatRetention;
use App\Models\AccountPayable;
use App\Models\Tax;
use App\Models\Warehouse;
use App\Models\Tenant;
use App\Services\FiscalCorrelativeService;
use App\Support\ImageStorage;
use App\Support\TenantCurrency;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;



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
            ->orderBy('name')
            ->get();

        $warehouses = Warehouse::where('tenant_id', $user->tenant_id)
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();

        $providers = Schema::hasTable('providers')
            ? Provider::where('tenant_id', $user->tenant_id)
                ->where('is_active', true)
                ->orderBy('name')
                ->get()
            : collect();

        $tenant = auth()->user()?->tenant;
        $baseCurrencyCode = TenantCurrency::resolveBaseCurrencyCode($tenant);
        $baseCurrencySymbol = TenantCurrency::resolveCurrencySymbol($baseCurrencyCode);
        $baseRateToBs = TenantCurrency::resolveRateToBs((int) $user->tenant_id, $baseCurrencyCode);
        $dollarRateToBs = TenantCurrency::resolveRateToBs((int) $user->tenant_id, 'USD');
        $euroRateToBs = TenantCurrency::resolveRateToBs((int) $user->tenant_id, 'EUR');

        return view('purchase', compact('categories', 'productItems', 'warehouses', 'providers', 'baseCurrencyCode', 'baseCurrencySymbol', 'baseRateToBs', 'dollarRateToBs', 'euroRateToBs')); // Asegúrate de tener una vista para mostrar las categorías.
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
        $entryMode = in_array((string) $request->input('entry_mode'), ['purchase', 'production'], true)
            ? (string) $request->input('entry_mode')
            : 'purchase';
        $purchaseDate = $request->input('purchase_date');
        $warehouseId = (int) $request->input('warehouse_id');
        $tenant = Tenant::query()->find((int) $user->tenant_id);
        $baseCurrencyCode = TenantCurrency::resolveBaseCurrencyCode($tenant);
        $baseRateToBs = TenantCurrency::resolveRateToBs((int) $user->tenant_id, $baseCurrencyCode);

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

        if ($entryMode === 'production') {
            return $this->storeProductionEntry($itemsSelected, $user, $warehouse, $purchaseDate, $baseCurrencyCode);
        }

        $providerName = trim((string) $request->input('provider_name', ''));
        $providerRif = trim((string) $request->input('provider_rif', ''));
        $supplierInvoiceNumber = trim((string) $request->input('supplier_invoice_number', ''));
        $supplierInvoiceControlNumber = trim((string) $request->input('supplier_invoice_control_number', ''));
        $supplierInvoiceDate = trim((string) $request->input('supplier_invoice_date', ''));
        $supplierInvoiceFilePath = null;

        if ($providerName === '' || $providerRif === '' || $supplierInvoiceNumber === '' || $supplierInvoiceControlNumber === '' || $supplierInvoiceDate === '') {
            return response()->json(['error' => 'Debes indicar proveedor, RIF, número de factura, número de control y fecha de la factura para registrar la retención.'], 422);
        }

        if ($request->hasFile('supplier_invoice_file')) {
            $supplierInvoiceFilePath = $request->file('supplier_invoice_file')->store(
                'purchase_invoices/' . $user->tenant_id,
                'public'
            );
        }

        $provider = Schema::hasTable('providers')
            ? Provider::updateOrCreate(
                [
                    'tenant_id' => $user->tenant_id,
                    'name' => $providerName,
                ],
                [
                    'rif' => $providerRif,
                    'is_active' => true,
                ]
            )
            : null;

        $groupedData = [[
            'provider_id' => $provider?->id,
            'provider_name' => $providerName,
            'provider_rif' => $providerRif,
            'details' => [],
        ]];

        foreach ($itemsSelected as $item) {
            $variantId = (int) data_get($item, 'variant.id', 0);
            $quantity = (int) data_get($item, 'quantity', 0);
            $price = (float) data_get($item, 'price', 0);
            $inputCurrencyCode = TenantCurrency::normalizeCurrencyCode((string) data_get($item, 'currency', $baseCurrencyCode));

            $normalizedPriceInBase = (float) TenantCurrency::convertAmount($price, $inputCurrencyCode, $baseCurrencyCode, (int) $user->tenant_id);
            $inputRateToBs = TenantCurrency::resolveRateToBs((int) $user->tenant_id, $inputCurrencyCode);

            // Preserve the conversion rate used at registration time for historical reconstruction.
            $conversionRateUsed = $inputCurrencyCode === 'BS'
                ? ($baseRateToBs > 0 ? $baseRateToBs : null)
                : ($inputRateToBs > 0 ? $inputRateToBs : null);

            if ($variantId <= 0 || $quantity <= 0 || $normalizedPriceInBase <= 0) {
                return response()->json(['error' => 'Hay productos con datos incompletos (variante, cantidad o precio).'], 422);
            }

            $groupedData[0]['details'][] = [
                'product_variant_id' => $variantId,
                'quantity' => $quantity,
                'price' => $normalizedPriceInBase,
                'input_currency_code' => $inputCurrencyCode,
                'input_exchange_rate' => $conversionRateUsed,
            ];
        }

        if (empty($groupedData[0]['details'])) {
            return response()->json(['error' => 'Debes indicar al menos una variante válida.'], 422);
        }

        DB::beginTransaction();

        try {
            $orderPayload = [
                'provider_id' => $groupedData[0]['provider_id'],
                'provider_name' => $groupedData[0]['provider_name'],
                'provider_rif' => $groupedData[0]['provider_rif'],
                'warehouse_id' => $warehouse->id,
                'date' => $purchaseDate,
                'entry_mode' => 'purchase',
                'supplier_invoice_number' => $supplierInvoiceNumber,
                'supplier_invoice_control_number' => $supplierInvoiceControlNumber,
                'supplier_invoice_date' => $supplierInvoiceDate,
                'supplier_invoice_file_path' => $supplierInvoiceFilePath,
            ];

            if (Schema::hasColumn('purchase_orders', 'tenant_id')) {
                $orderPayload['tenant_id'] = $user->tenant_id;
            }

            $purchaseOrder = PurchaseOrder::create($orderPayload);

            foreach ($groupedData[0]['details'] as $detail) {
                $productVariant = ProductVariant::with('product')->find($detail['product_variant_id']);

                if (!$productVariant || !$productVariant->product || (int) $productVariant->product->tenant_id !== (int) $user->tenant_id) {
                    throw new \RuntimeException('Se intentó registrar una variante inválida para esta sede.');
                }

                $detailPayload = [
                    'purchase_order_id' => $purchaseOrder->id,
                    'product_variant_id' => $detail['product_variant_id'],
                    'quantity' => $detail['quantity'],
                    'price' => $detail['price'],
                    'amount' => $detail['price'] * $detail['quantity'],
                    'input_currency_code' => $detail['input_currency_code'] ?? null,
                    'input_exchange_rate' => $detail['input_exchange_rate'] ?? null,
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

            $this->syncPurchaseAccountPayable(
                $purchaseOrder,
                (int) $user->tenant_id,
                (int) ($user->id ?? 0),
                (string) $purchaseDate,
                (string) $baseCurrencyCode
            );

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

    private function syncPurchaseAccountPayable(PurchaseOrder $purchaseOrder, int $tenantId, int $actorUserId, string $issuedAt, string $baseCurrencyCode): void
    {
        if ((string) ($purchaseOrder->entry_mode ?? 'purchase') !== 'purchase') {
            return;
        }

        $purchaseOrder->loadMissing(['detalles', 'provider']);

        $amountTotal = round((float) $purchaseOrder->detalles->sum('amount'), 4);
        if ($amountTotal <= 0) {
            return;
        }

        $currencyCode = strtoupper(trim((string) ($purchaseOrder->provider?->payment_currency_code ?: $baseCurrencyCode)));
        if (!in_array($currencyCode, ['USD', 'EUR', 'VES'], true)) {
            $currencyCode = strtoupper(trim($baseCurrencyCode)) ?: 'USD';
        }

        $dueAt = Carbon::parse($issuedAt)->addDays(30)->toDateString();

        $vatRate = $this->resolveVatRate();
        $taxableBase = $vatRate > 0
            ? round($amountTotal / (1 + ($vatRate / 100)), 4)
            : round($amountTotal, 4);
        $taxAmount = round(max(0, $amountTotal - $taxableBase), 4);

        $accountPayable = AccountPayable::updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'purchase_order_id' => (int) $purchaseOrder->id,
            ],
            array_filter([
                'provider_id' => $purchaseOrder->provider_id,
                'document_number' => (string) ($purchaseOrder->supplier_invoice_number ?? $purchaseOrder->id),
                'invoice_number' => Schema::hasColumn('accounts_payables', 'invoice_number')
                    ? (string) ($purchaseOrder->supplier_invoice_number ?? $purchaseOrder->id)
                    : null,
                'control_number' => Schema::hasColumn('accounts_payables', 'control_number')
                    ? (string) ($purchaseOrder->supplier_invoice_control_number ?? '')
                    : null,
                'invoice_date' => Schema::hasColumn('accounts_payables', 'invoice_date')
                    ? ($purchaseOrder->supplier_invoice_date ?? $issuedAt)
                    : null,
                'issued_at' => $issuedAt,
                'due_at' => $dueAt,
                'amount_total' => $amountTotal,
                'amount_paid' => 0,
                'amount_pending' => $amountTotal,
                'currency_code' => $currencyCode,
                'is_service' => true,
                'taxable_base' => $taxableBase,
                'tax_rate' => $vatRate,
                'tax_amount' => $taxAmount,
                'status' => Carbon::parse($dueAt)->isPast() ? 'overdue' : 'pending',
                'notes' => 'Cuenta por pagar generada automáticamente desde la orden de compra #' . $purchaseOrder->id . '.',
                'created_by' => $actorUserId > 0 ? $actorUserId : null,
            ], static fn ($value) => !is_null($value))
        );

        $this->generatePurchaseVatRetentionIfApplies($purchaseOrder, $accountPayable, $issuedAt, $actorUserId, $currencyCode);
    }

    private function resolveVatRate(): float
    {
        return (float) (Tax::query()
            ->where(function ($query) {
                $query->whereRaw('LOWER(name) = ?', ['iva'])
                    ->orWhereRaw('LOWER(name) like ?', ['%iva%']);
            })
            ->where(function ($query) {
                $query->whereNull('is_active')->orWhere('is_active', 1);
            })
            ->orderByDesc('rate')
            ->value('rate') ?? 0);
    }

    private function generatePurchaseVatRetentionIfApplies(
        PurchaseOrder $purchaseOrder,
        AccountPayable $accountPayable,
        string $issuedAt,
        int $actorUserId,
        string $currencyCode
    ): void {
        $tenant = Tenant::query()->find((int) $purchaseOrder->tenant_id);
        if (!$tenant || !((bool) ($tenant->special_taxpayer ?? false))) {
            return;
        }

        $provider = $purchaseOrder->provider;
        if ($provider && (bool) ($provider->is_special_taxpayer ?? false)) {
            return;
        }

        $exists = PurchaseVatRetention::query()
            ->where('tenant_id', (int) $purchaseOrder->tenant_id)
            ->where('purchase_order_id', (int) $purchaseOrder->id)
            ->exists();

        if ($exists) {
            return;
        }

        $taxAmount = round((float) ($accountPayable->tax_amount ?? 0), 4);
        $taxableBase = round((float) ($accountPayable->taxable_base ?? 0), 4);
        if ($taxAmount <= 0 || $taxableBase <= 0) {
            return;
        }

        $retentionRate = 75.0;
        $retainedAmount = round($taxAmount * ($retentionRate / 100), 4);
        $retentionDate = Carbon::parse($issuedAt)->toDateString();
        $deadline = Carbon::parse($retentionDate)->addDays(2);
        $correlative = app(FiscalCorrelativeService::class)->next(
            (int) $purchaseOrder->tenant_id,
            'purchase_vat_retention',
            'RET-IVA'
        );

        PurchaseVatRetention::create([
            'tenant_id' => (int) $purchaseOrder->tenant_id,
            'purchase_order_id' => (int) $purchaseOrder->id,
            'account_payable_id' => (int) $accountPayable->id,
            'provider_id' => (int) ($purchaseOrder->provider_id ?? 0) ?: null,
            'created_by' => $actorUserId > 0 ? $actorUserId : null,
            'retention_date' => $retentionDate,
            'legal_deadline_at' => $deadline->toDateString(),
            'issued_within_deadline' => now()->lte($deadline->endOfDay()),
            'certificate_number' => $correlative,
            'retention_rate' => $retentionRate,
            'taxable_base' => $taxableBase,
            'tax_amount' => $taxAmount,
            'retained_amount' => $retainedAmount,
            'currency_code' => $currencyCode,
            'invoice_number' => (string) ($purchaseOrder->supplier_invoice_number ?? $accountPayable->document_number ?? ''),
            'control_number' => (string) ($purchaseOrder->supplier_invoice_control_number ?? ''),
            'status' => 'issued',
            'notes' => 'Comprobante generado automáticamente por compra a proveedor ordinario.',
        ]);
    }

    public function viewOrders()
    {
        $user = auth()->user();
        $tenant = Tenant::query()->find((int) $user->tenant_id);
        $baseCurrencyCode = TenantCurrency::resolveBaseCurrencyCode($tenant);
        $purchaseOrders = PurchaseOrder::with(['warehouse', 'provider', 'detalles', 'detalles.productVariant.product.images', 'consumptions'])
        ->where('tenant_id', $user->tenant_id)
        ->orderBy('date', 'desc')
        ->get();

        foreach ($purchaseOrders as $order) {
            $order->total_items = $order->detalles->sum('quantity');
            $order->total_amount = $order->detalles->sum('amount');
            $order->total_variants = $order->detalles->count();
            $order->entry_mode = $order->entry_mode ?: 'purchase';
            $order->entry_mode_label = $order->entry_mode === 'production' ? 'Producción interna' : 'Compra';
            $order->consumption_total = (float) $order->consumptions->sum('amount');
            $firstDetail = $order->detalles->first();
            $order->preview_image = $firstDetail
                && $firstDetail->productVariant
                && $firstDetail->productVariant->product
                && $firstDetail->productVariant->product->images->first()
                    ? (ImageStorage::url($firstDetail->productVariant->product->images->first()->path) ?? asset('assets/img/shopix5.png'))
                    : asset('assets/img/shopix5.png');
        }

        return view('purchaseOrders', compact('purchaseOrders', 'baseCurrencyCode'));
    }
    
    public function showByOrder($id)
    {
        $user = auth()->user();
        $tenant = Tenant::query()->find((int) $user->tenant_id);
        $baseCurrencyCode = TenantCurrency::resolveBaseCurrencyCode($tenant);
        $order = PurchaseOrder::with([
            'warehouse',
            'provider',
            'detalles',
            'detalles.productVariant',
            'detalles.productVariant.product.images',
            'consumptions',
            'consumptions.consumedVariant.product',
            'consumptions.producedVariant.product',
        ])
            ->where('tenant_id', $user->tenant_id)
            ->findOrFail($id);

        $order->total_items = $order->detalles->sum('quantity');
        $order->total_amount = $order->detalles->sum('amount');
        $order->total_variants = $order->detalles->count();
        $order->entry_mode = $order->entry_mode ?: 'purchase';
        $order->entry_mode_label = $order->entry_mode === 'production' ? 'Producción interna' : 'Compra';
        $order->consumption_total = (float) $order->consumptions->sum('amount');

        return view('orderDetail', compact('order', 'baseCurrencyCode'));
    }

    private function storeProductionEntry(array $itemsSelected, $user, Warehouse $warehouse, string $purchaseDate, string $baseCurrencyCode)
    {
        $productionLines = [];
        $consumptionByVariant = [];

        foreach ($itemsSelected as $line) {
            $outputVariantId = (int) data_get($line, 'variant.id', 0);
            $outputQuantity = (float) data_get($line, 'quantity', 0);
            $consumptions = data_get($line, 'production_consumptions', []);

            if ($outputVariantId <= 0 || $outputQuantity <= 0 || !is_array($consumptions) || empty($consumptions)) {
                return response()->json(['error' => 'Cada línea de producción debe tener variante, cantidad y consumibles.'], 422);
            }

            $outputVariant = ProductVariant::with('product')->find($outputVariantId);
            if (!$outputVariant || !$outputVariant->product || (int) $outputVariant->product->tenant_id !== (int) $user->tenant_id) {
                return response()->json(['error' => 'Hay variantes de producto terminado no válidas para esta sede.'], 422);
            }

            $lineConsumptions = [];
            $lineTotalCost = 0.0;

            foreach ($consumptions as $consumption) {
                $consumedVariantId = (int) data_get($consumption, 'consumed_variant_id', 0);
                $consumedQty = (float) data_get($consumption, 'quantity', 0);
                $unitCost = (float) data_get($consumption, 'unit_cost', 0);

                if ($consumedVariantId === $outputVariantId) {
                    return response()->json(['error' => 'Una producción interna no puede consumir la misma variante que está produciendo.'], 422);
                }

                if ($consumedVariantId <= 0 || $consumedQty <= 0 || $unitCost <= 0) {
                    return response()->json(['error' => 'Cada consumible debe tener variante, cantidad y costo unitario válidos.'], 422);
                }

                $consumedVariant = ProductVariant::with('product')->find($consumedVariantId);
                if (!$consumedVariant || !$consumedVariant->product || (int) $consumedVariant->product->tenant_id !== (int) $user->tenant_id) {
                    return response()->json(['error' => 'Hay consumibles no válidos para esta sede.'], 422);
                }

                $consumedAmount = round($consumedQty * $unitCost, 4);
                $lineTotalCost += $consumedAmount;

                $lineConsumptions[] = [
                    'produced_variant_id' => $outputVariantId,
                    'consumed_variant_id' => $consumedVariantId,
                    'quantity' => $consumedQty,
                    'unit_cost' => $unitCost,
                    'amount' => $consumedAmount,
                ];

                if (!isset($consumptionByVariant[$consumedVariantId])) {
                    $consumptionByVariant[$consumedVariantId] = 0;
                }
                $consumptionByVariant[$consumedVariantId] += $consumedQty;
            }

            $unitCostOutput = $outputQuantity > 0 ? round($lineTotalCost / $outputQuantity, 4) : 0;
            if ($unitCostOutput <= 0) {
                return response()->json(['error' => 'No se pudo calcular el costo unitario del producto terminado.'], 422);
            }

            $productionLines[] = [
                'output_variant_id' => $outputVariantId,
                'output_quantity' => $outputQuantity,
                'line_total_cost' => round($lineTotalCost, 4),
                'line_unit_cost' => $unitCostOutput,
                'consumptions' => $lineConsumptions,
            ];
        }

        foreach ($consumptionByVariant as $consumedVariantId => $requiredQty) {
            $variant = ProductVariant::find($consumedVariantId);
            if (!$variant || (float) $variant->stock < (float) $requiredQty) {
                return response()->json([
                    'error' => 'No hay stock suficiente para los consumibles seleccionados.',
                    'detail' => 'Variante #' . $consumedVariantId . ' requiere ' . $requiredQty . ' y tiene ' . (float) ($variant->stock ?? 0),
                ], 422);
            }
        }

        DB::beginTransaction();

        try {
            $productionTotal = round(collect($productionLines)->sum('line_total_cost'), 4);

            $orderPayload = [
                'provider_id' => null,
                'provider_name' => 'PRODUCCIÓN INTERNA',
                'warehouse_id' => $warehouse->id,
                'date' => $purchaseDate,
                'entry_mode' => 'production',
                'production_cost_total' => $productionTotal,
                'production_notes' => 'Entrada generada por producción interna usando consumibles.',
            ];

            if (Schema::hasColumn('purchase_orders', 'tenant_id')) {
                $orderPayload['tenant_id'] = $user->tenant_id;
            }

            $purchaseOrder = PurchaseOrder::create($orderPayload);

            foreach ($productionLines as $line) {
                $detailPayload = [
                    'purchase_order_id' => $purchaseOrder->id,
                    'product_variant_id' => $line['output_variant_id'],
                    'quantity' => $line['output_quantity'],
                    'price' => $line['line_unit_cost'],
                    'amount' => $line['line_total_cost'],
                ];

                if (Schema::hasColumn('purchase_order_detail', 'input_currency_code')) {
                    $detailPayload['input_currency_code'] = $baseCurrencyCode;
                }

                if (Schema::hasColumn('purchase_order_detail', 'input_exchange_rate')) {
                    $detailPayload['input_exchange_rate'] = TenantCurrency::resolveRateToBs((int) $user->tenant_id, $baseCurrencyCode);
                }

                if (Schema::hasColumn('purchase_order_detail', 'tenant_id')) {
                    $detailPayload['tenant_id'] = $user->tenant_id;
                }

                PurchaseOrderDetail::create($detailPayload);

                $outputVariant = ProductVariant::findOrFail($line['output_variant_id']);
                $outputVariant->stock = (float) $outputVariant->stock + (float) $line['output_quantity'];
                $outputVariant->save();

                $outputWarehouseStock = ProductVariantWarehouseStock::firstOrNew([
                    'tenant_id' => $user->tenant_id,
                    'warehouse_id' => $warehouse->id,
                    'product_variant_id' => $line['output_variant_id'],
                ]);
                $outputWarehouseStock->quantity = (float) ($outputWarehouseStock->quantity ?? 0) + (float) $line['output_quantity'];
                $outputWarehouseStock->save();

                foreach ($line['consumptions'] as $consumption) {
                    PurchaseOrderConsumption::create([
                        'purchase_order_id' => $purchaseOrder->id,
                        'produced_variant_id' => $consumption['produced_variant_id'],
                        'consumed_variant_id' => $consumption['consumed_variant_id'],
                        'quantity' => $consumption['quantity'],
                        'unit_cost' => $consumption['unit_cost'],
                        'amount' => $consumption['amount'],
                        'tenant_id' => $user->tenant_id,
                    ]);

                    $consumedVariant = ProductVariant::findOrFail($consumption['consumed_variant_id']);
                    $consumedVariant->stock = (float) $consumedVariant->stock - (float) $consumption['quantity'];
                    if ((float) $consumedVariant->stock < 0) {
                        throw new \RuntimeException('El stock del consumible no puede quedar negativo.');
                    }
                    $consumedVariant->save();

                    $consumedWarehouseStock = ProductVariantWarehouseStock::firstOrNew([
                        'tenant_id' => $user->tenant_id,
                        'warehouse_id' => $warehouse->id,
                        'product_variant_id' => $consumption['consumed_variant_id'],
                    ]);
                    $currentWarehouseQty = (float) ($consumedWarehouseStock->quantity ?? 0);
                    $nextWarehouseQty = $currentWarehouseQty - (float) $consumption['quantity'];
                    if ($nextWarehouseQty < 0) {
                        throw new \RuntimeException('No hay stock suficiente en el almacén seleccionado para los consumibles.');
                    }
                    $consumedWarehouseStock->quantity = $nextWarehouseQty;
                    $consumedWarehouseStock->save();
                }
            }

            DB::commit();
            return response()->json(['message' => 'Entrada por producción registrada correctamente.'], 200);
        } catch (\Throwable $exception) {
            DB::rollBack();

            return response()->json([
                'error' => 'No se pudo registrar la entrada por producción.',
                'detail' => $exception->getMessage(),
            ], 500);
        }
    }
}
