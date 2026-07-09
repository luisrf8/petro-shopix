<?php

namespace App\Http\Controllers;

use App\Models\ProductImage;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Support\AuditLogger;
use App\Support\ImageStorage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use SimpleSoftwareIO\QrCode\Facades\QrCode as QrCodeGenerator;

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

        $variants = $request->input('variants', []);
        if (is_string($variants)) {
            $variants = json_decode($variants, true);
        }
        if (!is_array($variants)) {
            $variants = [];
        }

        $request->merge(['variants' => $variants]);

        $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'variants' => 'required|array',
            'variants.*.size' => 'required|string|max:255',
            'variants.*.price' => 'required|numeric|gt:0',
            'variants.*.discount_percentage' => 'nullable|numeric|min:0|max:100',
            'variants.*.stock' => 'required|numeric|min:0',
            'variants.*.barcode' => 'nullable|string|max:100',
            'variants.*.unit_type' => 'nullable|string|max:50',
            'variants.*.quantity_input_mode' => 'nullable|string|in:integer,decimal',
            'variants.*.min_sale_quantity' => 'nullable|numeric|gt:0',
            'variant_images.*' => 'nullable|file|mimes:jpeg,png,jpg,gif,svg,webp|max:5120',
        ]);

        $variantImages = $request->file('variant_images', []);

        foreach ($request->variants as $index => $variant) {
            $barcode = $this->sanitizeVariantCode($variant['barcode'] ?? null);
            $this->assertVariantCodeAvailable($barcode);
            $quantityInputMode = ProductVariant::normalizeQuantityInputMode($variant['quantity_input_mode'] ?? null);

            $createdVariant = ProductVariant::create([
                'product_id' => $request->product_id,
                'size' => trim((string) $variant['size']),
                'price' => $variant['price'],
                'discount_percentage' => (float) ($variant['discount_percentage'] ?? 0),
                'stock' => round((float) $variant['stock'], 2),
                'barcode' => $barcode,
                'unit_type' => ProductVariant::normalizeUnitType($variant['unit_type'] ?? null),
                'quantity_input_mode' => $quantityInputMode,
                'min_sale_quantity' => ProductVariant::normalizeMinSaleQuantity($variant['min_sale_quantity'] ?? null, $quantityInputMode),
            ]);

            if (empty($createdVariant->qr_code)) {
                $createdVariant->qr_code = $this->generateUniqueVariantCode('QRV');
            }

            if (empty($createdVariant->barcode)) {
                $createdVariant->barcode = $this->generateUniqueVariantCode('BCV');
            }

            $createdVariant->save();

            if (isset($variantImages[$index]) && $variantImages[$index]) {
                $path = ImageStorage::storeUploadedImageAsWebp($variantImages[$index], 'products');
                ProductImage::create([
                    'product_id' => $request->product_id,
                    'product_variant_id' => $createdVariant->id,
                    'path' => $path,
                ]);
            }
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

        $oldValues = [
            'variant_id' => (int) $productVariant->id,
            'product_id' => (int) $productVariant->product_id,
            'size' => (string) $productVariant->size,
            'price' => (float) $productVariant->price,
            'discount_percentage' => (float) ($productVariant->discount_percentage ?? 0),
            'stock' => (int) $productVariant->stock,
            'barcode' => (string) ($productVariant->barcode ?? ''),
            'unit_type' => (string) ($productVariant->unit_type ?? 'unidad'),
            'quantity_input_mode' => (string) ($productVariant->quantity_input_mode ?? 'integer'),
            'min_sale_quantity' => (float) ($productVariant->min_sale_quantity ?? 1),
        ];

        $request->validate([
            'size' => 'required|string|max:255',
            'price' => 'required|numeric|gt:0',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'stock' => 'required|numeric|min:0',
            'barcode' => 'nullable|string|max:100',
            'unit_type' => 'nullable|string|max:50',
            'quantity_input_mode' => 'nullable|string|in:integer,decimal',
            'min_sale_quantity' => 'nullable|numeric|gt:0',
            'image' => 'nullable|file|mimes:jpeg,png,jpg,gif,svg,webp|max:5120',
        ]);

        $barcode = $this->sanitizeVariantCode($request->input('barcode'));
        $this->assertVariantCodeAvailable($barcode, (int) $productVariant->id);
        $quantityInputMode = ProductVariant::normalizeQuantityInputMode($request->input('quantity_input_mode'));
    
        // Actualizar la variante con los datos proporcionados
        $productVariant->update([
            'size' => trim((string) $request->input('size')),
            'price' => $request->input('price'),
            'discount_percentage' => $request->input('discount_percentage', 0),
            'stock' => round((float) $request->input('stock'), 2),
            'barcode' => $barcode,
            'unit_type' => ProductVariant::normalizeUnitType($request->input('unit_type')),
            'quantity_input_mode' => $quantityInputMode,
            'min_sale_quantity' => ProductVariant::normalizeMinSaleQuantity($request->input('min_sale_quantity'), $quantityInputMode),
        ]);

        $productVariant->refresh();

        AuditLogger::logEvent(
            'product_variants',
            'VARIANT_UPDATED',
            'Variante actualizada',
            (int) (auth()->id() ?? 0),
            [
                'route_name' => (string) ($request->route()?->getName() ?? ''),
                'path' => '/' . trim((string) $request->path(), '/'),
                'method' => strtoupper((string) $request->method()),
                'old' => $oldValues,
                'new' => [
                    'variant_id' => (int) $productVariant->id,
                    'product_id' => (int) $productVariant->product_id,
                    'size' => (string) $productVariant->size,
                    'price' => (float) $productVariant->price,
                    'discount_percentage' => (float) ($productVariant->discount_percentage ?? 0),
                    'stock' => (int) $productVariant->stock,
                    'barcode' => (string) ($productVariant->barcode ?? ''),
                    'unit_type' => (string) ($productVariant->unit_type ?? 'unidad'),
                    'quantity_input_mode' => (string) ($productVariant->quantity_input_mode ?? 'integer'),
                    'min_sale_quantity' => (float) ($productVariant->min_sale_quantity ?? 1),
                ],
            ]
        );

        if (empty($productVariant->barcode)) {
            $productVariant->barcode = $this->generateUniqueVariantCode('BCV');
            $productVariant->save();
        }

        if ($request->hasFile('image')) {
            $existingImage = $productVariant->images()->first();
            if ($existingImage) {
                ImageStorage::delete($existingImage->path);
                $existingImage->delete();
            }

            $path = ImageStorage::storeUploadedImageAsWebp($request->file('image'), 'products');
            ProductImage::create([
                'product_id' => $productVariant->product_id,
                'product_variant_id' => $productVariant->id,
                'path' => $path,
            ]);
        }
    
        // Responder con JSON para las solicitudes AJAX
        return response()->json([
            'success' => true,
            'message' => 'Variante actualizada exitosamente.',
            'variant' => $productVariant
        ]);
    }

    public function updateBarcode(Request $request, ProductVariant $productVariant)
    {
        DB::raw("SET @user_id = " . auth()->id());

        $request->validate([
            'barcode' => 'nullable|string|max:100',
        ]);

        $barcode = $this->sanitizeVariantCode($request->input('barcode'));
        $this->assertVariantCodeAvailable($barcode, (int) $productVariant->id);

        $productVariant->barcode = $barcode;
        if (empty($productVariant->barcode)) {
            $productVariant->barcode = $this->generateUniqueVariantCode('BCV');
        }

        $productVariant->save();

        return response()->json([
            'success' => true,
            'message' => 'Código de barras actualizado.',
            'barcode' => $productVariant->barcode,
        ]);
    }

    public function destroy(ProductVariant $productVariant)
    {
        DB::raw("SET @user_id = " . auth()->id());

        $productVariant->loadMissing('product');

        if ((int) ($productVariant->product->tenant_id ?? 0) !== (int) (auth()->user()->tenant_id ?? 0)) {
            return response()->json(['success' => false, 'message' => 'No autorizado.'], 403);
        }

        $protectionReasons = $this->variantProtectionReasons([(int) $productVariant->id]);
        $hasUsageDependencies = !empty($protectionReasons);
        $hasStock = (float) ($productVariant->stock ?? 0) > 0;

        if ($hasUsageDependencies) {
            if ($productVariant->is_active !== false) {
                $productVariant->is_active = false;
                $productVariant->save();
            }

            $message = 'La variante tiene uso previo y se ha inhabilitado en lugar de eliminarse.';

            AuditLogger::logEvent(
                'product_variants',
                'VARIANT_INACTIVATED',
                'Variante inhabilitada por dependencias',
                (int) (auth()->id() ?? 0),
                [
                    'route_name' => (string) (request()->route()?->getName() ?? ''),
                    'path' => '/' . trim((string) request()->path(), '/'),
                    'method' => strtoupper((string) request()->method()),
                    'old' => [
                        'variant_id' => (int) $productVariant->id,
                        'product_id' => (int) $productVariant->product_id,
                        'is_active' => true,
                    ],
                    'new' => [
                        'variant_id' => (int) $productVariant->id,
                        'product_id' => (int) $productVariant->product_id,
                        'is_active' => false,
                        'stock' => (float) ($productVariant->stock ?? 0),
                        'reasons' => $protectionReasons,
                    ],
                ]
            );

            if (request()->expectsJson()) {
                return response()->json([
                    'success' => true,
                    'action' => 'inactivated',
                    'message' => $message,
                    'reasons' => $protectionReasons,
                    'variant' => [
                        'id' => (int) $productVariant->id,
                        'is_active' => false,
                    ],
                ]);
            }

            return redirect()->route('productItem', $productVariant->product_id)
                ->with('success', $message);
        }

        $productVariant->delete();

        AuditLogger::logEvent(
            'product_variants',
            'VARIANT_DELETED',
            'Variante eliminada',
            (int) (auth()->id() ?? 0),
            [
                'route_name' => (string) (request()->route()?->getName() ?? ''),
                'path' => '/' . trim((string) request()->path(), '/'),
                'method' => strtoupper((string) request()->method()),
                'old' => [
                    'variant_id' => (int) $productVariant->id,
                    'product_id' => (int) $productVariant->product_id,
                    'is_active' => (bool) ($productVariant->is_active ?? true),
                    'stock' => (float) ($productVariant->stock ?? 0),
                ],
                'new' => null,
            ]
        );

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'action' => 'deleted',
                'message' => 'Variante eliminada exitosamente.',
            ]);
        }

        return redirect()->route('productItem', $productVariant->product_id)
            ->with('success', 'Variante eliminada exitosamente.');
    }

    public function toggleStatus(ProductVariant $productVariant)
    {
        DB::raw("SET @user_id = " . auth()->id());

        $productVariant->loadMissing('product');

        if ((int) ($productVariant->product->tenant_id ?? 0) !== (int) (auth()->user()->tenant_id ?? 0)) {
            return response()->json(['success' => false, 'message' => 'No autorizado.'], 403);
        }

        $previousStatus = (bool) $productVariant->is_active;
        $productVariant->is_active = !$previousStatus;
        $productVariant->save();

        $message = $productVariant->is_active
            ? 'Variante habilitada correctamente.'
            : 'Variante inhabilitada correctamente.';

        AuditLogger::logEvent(
            'product_variants',
            $productVariant->is_active ? 'VARIANT_ENABLED' : 'VARIANT_DISABLED',
            $productVariant->is_active ? 'Variante habilitada' : 'Variante inhabilitada',
            (int) (auth()->id() ?? 0),
            [
                'route_name' => (string) (request()->route()?->getName() ?? ''),
                'path' => '/' . trim((string) request()->path(), '/'),
                'method' => strtoupper((string) request()->method()),
                'old' => [
                    'variant_id' => (int) $productVariant->id,
                    'product_id' => (int) $productVariant->product_id,
                    'is_active' => $previousStatus,
                ],
                'new' => [
                    'variant_id' => (int) $productVariant->id,
                    'product_id' => (int) $productVariant->product_id,
                    'is_active' => (bool) $productVariant->is_active,
                ],
            ]
        );

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'action' => $productVariant->is_active ? 'enabled' : 'disabled',
                'message' => $message,
                'variant' => [
                    'id' => (int) $productVariant->id,
                    'is_active' => (bool) $productVariant->is_active,
                ],
            ]);
        }

        return redirect()->route('productItem', $productVariant->product_id)
            ->with('success', $message);
    }

    public function reassign(Request $request, ProductVariant $productVariant)
    {
        DB::raw("SET @user_id = " . auth()->id());

        $productVariant->loadMissing('product', 'images');

        if ((int) ($productVariant->product->tenant_id ?? 0) !== (int) (auth()->user()->tenant_id ?? 0)) {
            return response()->json(['success' => false, 'message' => 'No autorizado.'], 403);
        }

        $validated = $request->validate([
            'target_product_id' => 'required|integer|exists:products,id',
        ]);

        $targetProduct = Product::query()
            ->where('tenant_id', (int) (auth()->user()->tenant_id ?? 0))
            ->where('is_active', true)
            ->findOrFail((int) $validated['target_product_id']);

        if ((int) $targetProduct->id === (int) $productVariant->product_id) {
            return response()->json(['success' => false, 'message' => 'La variante ya pertenece a ese producto.'], 422);
        }

        $oldProductId = (int) $productVariant->product_id;

        DB::transaction(function () use ($productVariant, $targetProduct) {
            $productVariant->product_id = (int) $targetProduct->id;
            $productVariant->save();

            foreach ($productVariant->images as $image) {
                $image->product_id = (int) $targetProduct->id;
                $image->save();
            }
        });

        AuditLogger::logEvent(
            'product_variants',
            'VARIANT_REASSIGNED',
            'Variante reasignada a otro producto',
            (int) (auth()->id() ?? 0),
            [
                'route_name' => (string) (request()->route()?->getName() ?? ''),
                'path' => '/' . trim((string) request()->path(), '/'),
                'method' => strtoupper((string) request()->method()),
                'old' => [
                    'variant_id' => (int) $productVariant->id,
                    'product_id' => $oldProductId,
                ],
                'new' => [
                    'variant_id' => (int) $productVariant->id,
                    'product_id' => (int) $targetProduct->id,
                ],
            ]
        );

        return response()->json([
            'success' => true,
            'message' => 'Variante reasignada exitosamente.',
            'variant' => [
                'id' => (int) $productVariant->id,
                'product_id' => (int) $targetProduct->id,
            ],
        ]);
    }

    public function generateCodes(ProductVariant $productVariant)
    {
        $productVariant->loadMissing('product');

        if ((int) ($productVariant->product->tenant_id ?? 0) !== (int) (auth()->user()->tenant_id ?? 0)) {
            return response()->json(['success' => false, 'message' => 'No autorizado.'], 403);
        }

        if (empty($productVariant->qr_code)) {
            $productVariant->qr_code = $this->generateUniqueVariantCode('QRV');
        }

        if (empty($productVariant->barcode)) {
            $productVariant->barcode = $this->generateUniqueVariantCode('BCV');
        }

        $productVariant->save();

        return response()->json([
            'success' => true,
            'qr_code' => $productVariant->qr_code,
            'barcode' => $productVariant->barcode,
        ]);
    }

    public function qrImage(ProductVariant $productVariant)
    {
        $productVariant->loadMissing('product');

        if ((int) ($productVariant->product->tenant_id ?? 0) !== (int) (auth()->user()->tenant_id ?? 0)) {
            return response()->json(['success' => false, 'message' => 'No autorizado.'], 403);
        }

        if (empty($productVariant->qr_code)) {
            return response()->json(['success' => false, 'message' => 'La variante no tiene código QR generado.'], 404);
        }

        try {
            $png = QrCodeGenerator::format('png')->size(420)->margin(1)->generate($productVariant->qr_code);

            return response($png, 200, [
                'Content-Type' => 'image/png',
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            ]);
        } catch (\Throwable $exception) {
            $svg = QrCodeGenerator::format('svg')->size(420)->margin(1)->generate($productVariant->qr_code);

            return response($svg, 200, [
                'Content-Type' => 'image/svg+xml; charset=UTF-8',
                'Cache-Control' => 'no-store, no-cache, must-revalidate, max-age=0',
            ]);
        }
    }

    private function generateUniqueVariantCode(string $prefix): string
    {
        do {
            $value = $prefix . '-' . strtoupper(Str::random(10));
        } while (ProductVariant::where('qr_code', $value)->orWhere('barcode', $value)->exists());

        return $value;
    }

    private function sanitizeVariantCode(?string $value): ?string
    {
        $clean = trim((string) $value);

        return $clean === '' ? null : $clean;
    }

    private function assertVariantCodeAvailable(?string $barcode, ?int $ignoreVariantId = null): void
    {
        if (empty($barcode)) {
            return;
        }

        $query = ProductVariant::query()
            ->where(function ($innerQuery) use ($barcode) {
                $innerQuery->where('barcode', $barcode)
                    ->orWhere('qr_code', $barcode);
            });

        if ($ignoreVariantId) {
            $query->where('id', '!=', $ignoreVariantId);
        }

        if ($query->exists()) {
            throw new \Illuminate\Validation\ValidationException(
                validator([], []),
                response()->json([
                    'success' => false,
                    'message' => 'El código de barras ya está en uso por otra variante.',
                    'errors' => [
                        'barcode' => ['El código de barras ya está en uso por otra variante.'],
                    ],
                ], 422)
            );
        }
    }

    private function variantProtectionReasons(array $variantIds): array
    {
        if (empty($variantIds)) {
            return [];
        }

        $relationRules = [
            [
                'table' => 'sales_order_details',
                'column' => 'product_variant_id',
                'label' => 'ventas',
            ],
            [
                'table' => 'sales_return_items',
                'column' => 'product_variant_id',
                'label' => 'devoluciones de ventas',
            ],
            [
                'table' => 'purchase_order_detail',
                'column' => 'product_variant_id',
                'label' => 'compras',
            ],
            [
                'table' => 'purchase_order_details',
                'column' => 'product_variant_id',
                'label' => 'compras',
            ],
            [
                'table' => 'purchase_order_consumptions',
                'column' => 'consumed_variant_id',
                'label' => 'consumos de producción',
            ],
            [
                'table' => 'purchase_order_consumptions',
                'column' => 'produced_variant_id',
                'label' => 'producción interna',
            ],
            [
                'table' => 'warehouse_movements',
                'column' => 'product_variant_id',
                'label' => 'movimientos de almacén',
            ],
            [
                'table' => 'product_variant_warehouse_stocks',
                'column' => 'product_variant_id',
                'label' => 'stock por almacén',
            ],
            [
                'table' => 'material_package_items',
                'column' => 'product_variant_id',
                'label' => 'listas de materiales',
            ],
            [
                'table' => 'appointment_services',
                'column' => 'product_variant_id',
                'label' => 'servicios de citas',
            ],
            [
                'table' => 'appointment_consumptions',
                'column' => 'product_variant_id',
                'label' => 'consumos de citas',
            ],
            [
                'table' => 'project_quotation_items',
                'column' => 'product_variant_id',
                'label' => 'cotizaciones de proyectos',
            ],
        ];

        $reasons = [];

        foreach ($relationRules as $rule) {
            $table = (string) $rule['table'];
            $column = (string) $rule['column'];
            $label = (string) $rule['label'];

            if (!Schema::hasTable($table) || !Schema::hasColumn($table, $column)) {
                continue;
            }

            if (DB::table($table)->whereIn($column, $variantIds)->exists()) {
                $reasons[] = $label;
            }
        }

        return array_values(array_unique($reasons));
    }
}

