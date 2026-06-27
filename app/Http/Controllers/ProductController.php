<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Product;
use App\Models\Category;
use App\Models\ProductVariantWarehouseStock;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Tax;
use App\Models\Tenant;
use App\Models\Warehouse;
use App\Support\ImageStorage;
use App\Support\TenantCurrency;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class ProductController extends Controller
{
    // private $client;

    // public function __construct()
    // {
    //     $this->client = new Client();
    //     // $this->client->setAuthConfig(storage_path('app/credentials.json'));
    //     $this->client->addScope(Drive::DRIVE_FILE);
    //     $this->client->setAccessType('offline');
    //     $this->client->setPrompt('select_account consent');
    // }
    public function index()
    {
        $user = auth()->user();
        $tenant = Tenant::find($user->tenant_id);
        $baseCurrencyCode = TenantCurrency::resolveBaseCurrencyCode($tenant);
        $baseCurrencySymbol = TenantCurrency::resolveCurrencySymbol($baseCurrencyCode);

        $categories = Category::with(['products' => function ($query) use ($user) {
                $query->where('is_active', true)
                    ->where('tenant_id', $user->tenant_id)
                    ->with(['variants']);
            }])
            ->where('is_active', true)
            ->where('tenant_id', $user->tenant_id) // 👈 aquí filtras las categorías
            ->get();
        $taxes = $this->allowedProductTaxes();

        $productItems = Product::with(['category', 'images', 'variants.images'])
            ->where('tenant_id', $user->tenant_id)
            ->orderBy('created_at', 'desc')
            ->get();
        return view('products', compact('categories', 'productItems', 'taxes', 'baseCurrencyCode', 'baseCurrencySymbol'));
    }

    public function indexCreateProduct()
    {
        $user = auth()->user();

        $categories = Category::where('tenant_id', $user->tenant_id)->get();
        if ($categories->isEmpty()) {
            return redirect()->route('categories.index')
                ->with('warning', 'Primero debes crear al menos una categoría para registrar productos.');
        }

        $taxes = $this->allowedProductTaxes();
        return view('createProductItem', compact('categories', 'taxes'));
    }

    public function getProducts()
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        $productItems = Product::with(['category', 'images', 'variants.images'])
            ->when($tenantId > 0, fn ($query) => $query->where('tenant_id', $tenantId))
            ->orderBy('created_at', 'desc')
            ->get();
        return response()->json($productItems);
    }
    public function categoriesIndex()
    {
        $user = auth()->user();

        $categories = Category::where('tenant_id', $user->tenant_id)
            ->with(['products' => function ($query) use ($user) {
                $query->where('is_active', true)
                    ->where('tenant_id', $user->tenant_id)
                    ->with('variants');
            }])
            ->get();

        // Calcular total de stock por categoría
        foreach ($categories as $category) {
            $totalStock = 0;

            foreach ($category->products as $product) {
                foreach ($product->variants as $variant) {
                    $totalStock += $variant->stock;
                }
            }

            // Agregar el total como propiedad adicional para usarlo en la vista
            $category->total_available_items = $totalStock;
        }

        return view('categories', compact('categories'));
    }

    public function showByCategory($categoryId)
    {
        $user = auth()->user();
        $tenant = Tenant::find($user->tenant_id);
        $baseCurrencyCode = TenantCurrency::resolveBaseCurrencyCode($tenant);
        $baseCurrencySymbol = TenantCurrency::resolveCurrencySymbol($baseCurrencyCode);

        $category = Category::where('tenant_id', $user->tenant_id)->findOrFail($categoryId);
        $categories = Category::where('tenant_id', $user->tenant_id)
        ->where('is_active', true)
        ->get();
        $productItems = Product::where('category_id', $category->id)
        ->orderBy('created_at', 'desc')
        ->get();
        $taxes = $this->allowedProductTaxes();
    
        return view('products', compact('productItems', 'category', 'categories', 'taxes', 'baseCurrencyCode', 'baseCurrencySymbol'));
    }
    public function showByCategoryEcomm($categoryId)
    {
        $category = Category::findOrFail($categoryId);
        $categories = Category::all();
        $productItems = Product::where('category_id', $category->id)
        ->with(['images', 'variants'])
        ->orderBy('created_at', 'desc')
        ->get();
        return response()->json($productItems);
    }
    public function showByCategoryEcommAll()
    {
        try {
            // Obtener todos los productos con sus imágenes y variantes
            $productItems = Product::with(['images', 'variants'])->get();
    
            // Verificar si hay productos, de lo contrario devolver un mensaje adecuado
            if ($productItems->isEmpty()) {
                return response()->json(['message' => 'No products found'], 404);
            }
    
            return response()->json($productItems);
    
        } catch (\Exception $e) {
            return response()->json(['error' => 'Something went wrong', 'message' => $e->getMessage()], 500);
        }
    }
    
    public function showByProduct($id)
    {
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);
        $product = Product::with(['variants.images', 'images', 'category', 'taxes'])->findOrFail($id);
        $tenant = Tenant::find($tenantId);

        if ($tenantId > 0 && (int) $product->tenant_id !== $tenantId) {
            abort(404);
        }

        $categories = Category::query()
            ->when($tenantId > 0, fn ($query) => $query->where('tenant_id', $tenantId))
            ->get();
        $warehouses = Warehouse::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->orderByDesc('is_default')
            ->orderBy('name')
            ->get();
        $warehouseStocks = ProductVariantWarehouseStock::query()
            ->where('tenant_id', $tenantId)
            ->whereIn('product_variant_id', $product->variants->pluck('id'))
            ->get()
            ->keyBy(function ($item) {
                return $item->warehouse_id . '_' . $item->product_variant_id;
            });
        $taxes = $this->allowedProductTaxes();
        $canEditProductTaxes = (bool) ($tenant->printer_tax_change_enabled ?? false);
        $productTaxChangeReference = (string) ($tenant->printer_tax_change_reference ?? '');

        return view('productItem', compact('product', 'categories', 'taxes', 'canEditProductTaxes', 'productTaxChangeReference', 'warehouses', 'warehouseStocks'));
    }
    
    public function store(Request $request)
    {
        DB::raw("SET @user_id = " . auth()->id());

        $tenantId = (int) (auth()->user()->tenant_id ?? $request->tenant_id ?? 0);

        $validatedData = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category_id' => 'required',
            'is_consumable' => 'nullable|boolean',
            'tenant_id' => 'required'
        ]);

        $categoryExistsForTenant = Category::query()
            ->where('id', $validatedData['category_id'])
            ->where('tenant_id', $tenantId)
            ->exists();

        if (!$categoryExistsForTenant) {
            throw ValidationException::withMessages([
                'category_id' => ['La categoría seleccionada no pertenece a tu tenant.'],
            ]);
        }

        $this->assertValidProductTaxSelection($validated['tax_ids'] ?? []);

        $validatedData['slug'] = $this->generateUniqueProductSlug((string) $validatedData['name'], $tenantId);
        $validatedData['is_consumable'] = (bool) ($validatedData['is_consumable'] ?? false);

        Product::create($validatedData);
        return response()->json(['success' => true, 'message' => 'Product created successfully'], 200);

    }
    public function create(Request $request)
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

        // Compatibilidad entre formularios antiguos (name/description) y nuevos (productName/productDescription).
        $request->merge([
            'productName' => $request->input('productName', $request->input('name')),
            'productDescription' => $request->input('productDescription', $request->input('description')),
            'productDiscount' => $request->input('productDiscount', $request->input('discount', 0)),
            'is_consumable' => $request->boolean('is_consumable'),
        ]);

        $validated = $request->validate([
            'category_id' => 'required|exists:categories,id',
            'productName' => 'required|string|max:255',
            'productDescription' => 'nullable|string',
            'productDiscount' => 'nullable|numeric|min:0|max:100',
            'is_consumable' => 'nullable|boolean',
            'tax_ids' => 'nullable|array|max:1',
            'tax_ids.*' => 'exists:taxes,id',
            'images.*' => 'nullable|file|mimes:jpeg,png,jpg,gif,svg,webp|max:5120',
            'variant_images.*' => 'nullable|file|mimes:jpeg,png,jpg,gif,svg,webp|max:5120',
            'variants' => 'required|array|min:1',
            'variants.*.name' => 'required|string|max:255',
            'variants.*.price' => 'required|numeric|gt:0',
            'variants.*.discount_percentage' => 'nullable|numeric|min:0|max:100',
            'variants.*.stock' => 'required|integer|min:0',
            'variants.*.barcode' => 'nullable|string|max:100',
        ]);

        $normalizedBarcodes = [];
        foreach ($variants as $index => $variant) {
            $barcode = $this->sanitizeVariantCode($variant['barcode'] ?? null);

            if (!empty($barcode)) {
                if (in_array($barcode, $normalizedBarcodes, true)) {
                    throw ValidationException::withMessages([
                        "variants.$index.barcode" => ['El codigo de barras esta repetido dentro del mismo producto.'],
                    ]);
                }

                $normalizedBarcodes[] = $barcode;
                $this->assertVariantCodeAvailable($barcode);
            }
        }

        $tenantId = (int) (auth()->user()->tenant_id ?? $request->tenant_id ?? 0);
        if ($tenantId <= 0) {
            throw ValidationException::withMessages([
                'tenant_id' => ['No se pudo identificar el tenant del producto.'],
            ]);
        }

        $categoryExistsForTenant = Category::query()
            ->where('id', $validated['category_id'])
            ->where('tenant_id', $tenantId)
            ->exists();

        if (!$categoryExistsForTenant) {
            throw ValidationException::withMessages([
                'category_id' => ['La categoría seleccionada no pertenece a tu tenant.'],
            ]);
        }

        $storedImagePaths = [];

        if (!ImageStorage::usesGoogleDrive()) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo crear el producto porque Google Drive no esta configurado para imagenes.',
            ], 500);
        }

        try {
            DB::transaction(function () use ($request, $validated, $variants, $tenantId, &$storedImagePaths) {
                $variantSizeMaxLength = $this->resolveVarcharColumnMaxLength('product_variants', 'size', 255);

                $product = Product::create([
                    'category_id' => $validated['category_id'],
                    'name' => $validated['productName'],
                    'slug' => $this->generateUniqueProductSlug((string) $validated['productName'], $tenantId),
                    'description' => (string) ($validated['productDescription'] ?? ''),
                    'discount_percentage' => max(0, min(100, (float) ($validated['productDiscount'] ?? 0))),
                    'is_consumable' => (bool) ($validated['is_consumable'] ?? false),
                    'tenant_id' => $tenantId,
                ]);

                if (!empty($validated['tax_ids']) && is_array($validated['tax_ids'])) {
                    $product->taxes()->sync($validated['tax_ids']);
                }

                if ($request->hasFile('images')) {
                    foreach ($request->file('images') as $image) {
                        $path = ImageStorage::storeUploadedImageAsWebp($image, 'products');
                        $storedImagePaths[] = $path;

                        ProductImage::create([
                            'product_id' => $product->id,
                            'path' => $path,
                        ]);
                    }
                }

                $variantImageUploads = $request->file('variant_images', []);

                foreach ($variants as $index => $variant) {
                    $variantName = $this->clampStringToLength(
                        trim((string) ($variant['name'] ?? '')),
                        $variantSizeMaxLength
                    );

                    if ($variantName === '') {
                        $variantName = 'Unica';
                    }

                    $productVariant = ProductVariant::create([
                        'product_id' => $product->id,
                        'size' => $variantName,
                        'price' => $variant['price'],
                        'discount_percentage' => max(0, min(100, (float) ($variant['discount_percentage'] ?? 0))),
                        'stock' => $variant['stock'],
                        'barcode' => $this->sanitizeVariantCode($variant['barcode'] ?? null),
                    ]);

                    $this->ensureVariantCodes($productVariant);

                    if (isset($variantImageUploads[$index]) && $variantImageUploads[$index]) {
                        $variantImagePath = ImageStorage::storeUploadedImageAsWebp($variantImageUploads[$index], 'products');
                        $storedImagePaths[] = $variantImagePath;

                        ProductImage::create([
                            'product_id' => $product->id,
                            'product_variant_id' => $productVariant->id,
                            'path' => $variantImagePath,
                        ]);
                    }
                }
            });
        } catch (\Throwable $exception) {
            foreach ($storedImagePaths as $storedImagePath) {
                ImageStorage::delete($storedImagePath);
            }

            Log::error('Error al crear producto', [
                'tenant_id' => $tenantId,
                'category_id' => $validated['category_id'] ?? null,
                'product_name' => $validated['productName'] ?? null,
                'error' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'No se pudo crear el producto. Si adjuntaste imágenes, verifica el formato o intenta nuevamente.',
            ], 500);
        }

        return response()->json(['success' => true, 'message' => 'Product created successfully']);
    }

    public function updateTaxes(Request $request, $id)
    {
        DB::raw("SET @user_id = " . auth()->id());

        $product = Product::findOrFail($id);
        $tenantId = (int) (auth()->user()->tenant_id ?? 0);

        if ($tenantId > 0 && (int) $product->tenant_id !== $tenantId) {
            return response()->json(['success' => false, 'message' => 'No autorizado.'], 403);
        }

        return response()->json([
            'success' => false,
            'message' => 'La edición de impuestos generales fue deshabilitada para productos ya creados. El producto conserva el impuesto asignado inicialmente.',
        ], 403);
    }

    public function generateCodes(Product $product)
    {
        if ((int) ($product->tenant_id ?? 0) !== (int) (auth()->user()->tenant_id ?? 0)) {
            return response()->json(['success' => false, 'message' => 'No autorizado.'], 403);
        }

        $product->loadMissing('variants');

        $generated = 0;
        $variants = [];

        foreach ($product->variants as $variant) {
            $beforeQr = (string) ($variant->qr_code ?? '');
            $beforeBarcode = (string) ($variant->barcode ?? '');

            $this->ensureVariantCodes($variant);

            $afterQr = (string) ($variant->qr_code ?? '');
            $afterBarcode = (string) ($variant->barcode ?? '');

            if ($beforeQr !== $afterQr || $beforeBarcode !== $afterBarcode) {
                $generated++;
            }

            $variants[] = [
                'id' => $variant->id,
                'size' => $variant->size,
                'qr_code' => $afterQr,
                'barcode' => $afterBarcode,
            ];
        }

        return response()->json([
            'success' => true,
            'generated' => $generated,
            'total_variants' => count($variants),
            'variants' => $variants,
        ]);
    }

    private function generateUniqueVariantCode(string $prefix): string
    {
        do {
            $value = $prefix . '-' . strtoupper(Str::random(10));
        } while (ProductVariant::where('qr_code', $value)->orWhere('barcode', $value)->exists());

        return $value;
    }

    private function ensureVariantCodes(ProductVariant $variant): void
    {
        $dirty = false;

        if (empty($variant->qr_code)) {
            $variant->qr_code = $this->generateUniqueVariantCode('QRV');
            $dirty = true;
        }

        if (empty($variant->barcode)) {
            $variant->barcode = $this->generateUniqueVariantCode('BCV');
            $dirty = true;
        }

        if ($dirty) {
            $variant->save();
        }
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

    // Función para agregar una imagen
    public function addImage(Request $request, $productId)
    {
        DB::raw("SET @user_id = " . auth()->id());

        if (!ImageStorage::usesGoogleDrive()) {
            return redirect()->back()->with('error', 'Google Drive no esta configurado para imagenes.');
        }

        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
            'variant_id' => 'nullable|exists:product_variants,id',
        ]);

            $product = Product::findOrFail($productId);
            $variantId = $request->input('variant_id');

            if (!empty($variantId)) {
                $variant = ProductVariant::where('id', $variantId)
                    ->where('product_id', $product->id)
                    ->firstOrFail();
                $variantId = $variant->id;
            }
    
            // Guardar la imagen en el almacenamiento
            $path = ImageStorage::storeUploadedImageAsWebp($request->file('image'), 'products');
    
            // Asociar la imagen al producto
            ProductImage::create([
                'product_id' => $productId,
                'product_variant_id' => $variantId,
                'path' => $path,
            ]);
    
            return redirect()->back()->with('success', 'Imagen agregada correctamente.');
        }
    
        // Función para eliminar una imagen
        public function removeImage($imageId)
        {
            $image = ProductImage::findOrFail($imageId);
    
            // Eliminar la imagen del almacenamiento
            ImageStorage::delete($image->path);
    
            // Eliminar el registro de la base de datos
            $image->delete();
    
            return response()->json(['success' => true, 'message' => 'Imagen eliminada correctamente.']);
        }

    public function importCatalog(Request $request)
    {
        $request->validate([
            'file' => 'nullable|file|max:20480',
            'google_sheet_url' => 'nullable|url',
            'use_gemini_mapping' => 'nullable|boolean',
        ]);

        $user = auth()->user();
        if (!$user || empty($user->tenant_id)) {
            return response()->json([
                'success' => false,
                'message' => 'No se pudo identificar el tenant del usuario.',
            ], 422);
        }

        if (!$request->hasFile('file') && !$request->filled('google_sheet_url')) {
            return response()->json([
                'success' => false,
                'message' => 'Debes subir un archivo o indicar la URL de Google Sheets.',
            ], 422);
        }

        try {
            $rows = [];
            $useGemini = filter_var($request->input('use_gemini_mapping', true), FILTER_VALIDATE_BOOL);

            if ($request->hasFile('file')) {
                $file = $request->file('file');
                $extension = strtolower($file->getClientOriginalExtension());

                if (in_array($extension, ['xlsx', 'xls'], true)) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Este sistema no permite importar archivos XLSX/XLS. Usa CSV, JSON o SQL.',
                    ], 422);
                }

                $rows = $this->extractRowsFromUploadedFile($file->getRealPath(), $extension);

                if (empty($rows) && $useGemini && $extension === 'sql') {
                    $rows = $this->extractRowsFromSqlWithGemini(file_get_contents($file->getRealPath()) ?: '');
                }
            } else {
                $rows = $this->extractRowsFromGoogleSheets($request->string('google_sheet_url')->toString());
            }

            if (empty($rows)) {
                return response()->json([
                    'success' => false,
                    'message' => 'No se encontraron registros válidos para importar.',
                ], 422);
            }

            $mapping = $this->resolveImportMapping(array_keys($rows[0] ?? []), $useGemini, $rows);
            $normalizedRows = $this->normalizeImportRows($rows, $mapping);

            DB::beginTransaction();

            $hasVariantUnitTypeColumn = Schema::hasColumn('product_variants', 'unit_type');
            $variantSizeMaxLength = $this->resolveVarcharColumnMaxLength('product_variants', 'size', 255);
            $variantUnitTypeMaxLength = $hasVariantUnitTypeColumn
                ? $this->resolveVarcharColumnMaxLength('product_variants', 'unit_type', 50)
                : 50;

            $createdCategories = 0;
            $createdProducts = 0;
            $createdVariants = 0;
            $skippedRows = 0;
            $lastCategoryName = '';
            $lastProductName = '';

            foreach ($normalizedRows as $row) {
                $rawCategoryName = trim((string) ($row['category_name'] ?? ''));
                $rawProductName = trim((string) ($row['product_name'] ?? ''));

                if ($rawCategoryName !== '') {
                    if ($lastCategoryName !== '' && $rawCategoryName !== $lastCategoryName) {
                        $lastProductName = '';
                    }
                    $lastCategoryName = $rawCategoryName;
                }

                if ($rawProductName !== '') {
                    $lastProductName = $rawProductName;
                }

                $categoryName = $rawCategoryName !== '' ? $rawCategoryName : $lastCategoryName;
                $productName = $rawProductName !== '' ? $rawProductName : $lastProductName;

                if ($categoryName === '' || $productName === '') {
                    $skippedRows++;
                    continue;
                }

                $categoryDefaults = [
                    'description' => $row['category_description'] ?? null,
                    'tenant_id' => $user->tenant_id,
                    'is_active' => isset($row['category_is_active']) ? (bool) $row['category_is_active'] : true,
                ];

                $category = Category::firstOrCreate(
                    ['name' => $categoryName],
                    $categoryDefaults
                );

                if ($category->wasRecentlyCreated) {
                    $createdCategories++;
                }

                if ((int) $category->tenant_id !== (int) $user->tenant_id) {
                    $category->tenant_id = $user->tenant_id;
                    $category->save();
                }

                $product = Product::firstOrCreate(
                    [
                        'tenant_id' => $user->tenant_id,
                        'category_id' => $category->id,
                        'name' => $productName,
                    ],
                    [
                        'slug' => $this->generateUniqueProductSlug($productName, (int) $user->tenant_id),
                        'description' => $row['product_description'] ?? 'Sin descripción',
                        'is_active' => isset($row['product_is_active']) ? (bool) $row['product_is_active'] : true,
                    ]
                );

                if ($product->wasRecentlyCreated) {
                    $createdProducts++;
                }

                $variants = $row['variants'] ?? [];
                if (!is_array($variants) || empty($variants)) {
                    if (!empty($row['variant_size']) || isset($row['variant_price']) || isset($row['variant_stock'])) {
                        $variants = [[
                            'size' => $row['variant_size'] ?? 'Única',
                            'price' => $row['variant_price'] ?? null,
                            'stock' => $row['variant_stock'] ?? 0,
                            'barcode' => $row['variant_barcode'] ?? null,
                            'unit_type' => $row['variant_unit_type'] ?? 'unidad',
                        ]];
                    }
                }

                foreach ($variants as $variant) {
                    $size = trim((string) ($variant['size'] ?? ''));
                    if ($size === '') {
                        $size = 'Única';
                    }
                    $size = $this->clampStringToLength($size, $variantSizeMaxLength);

                    $unitType = trim((string) ($variant['unit_type'] ?? 'unidad'));
                    if ($unitType === '') {
                        $unitType = 'unidad';
                    }
                    $unitType = $this->clampStringToLength($unitType, $variantUnitTypeMaxLength);

                    $price = $this->parseLocalizedNumber($variant['price'] ?? null, 0.0);
                    if ($price <= 0) {
                        $skippedRows++;
                        continue;
                    }

                    $stock = (int) round($this->parseLocalizedNumber($variant['stock'] ?? null, 0.0));

                    $variantLookup = [
                        'product_id' => $product->id,
                        'size' => $size,
                    ];

                    if ($hasVariantUnitTypeColumn) {
                        $variantLookup['unit_type'] = $unitType;
                    }

                    $variantValues = [
                        'tenant_id' => $user->tenant_id,
                        'price' => $price,
                        'stock' => $stock,
                    ];

                    if ($hasVariantUnitTypeColumn) {
                        $variantValues['unit_type'] = $unitType;
                    }

                    $productVariant = ProductVariant::updateOrCreate($variantLookup, $variantValues);

                    $barcode = $this->sanitizeVariantCode($variant['barcode'] ?? null);
                    if (!empty($barcode)) {
                        try {
                            $this->assertVariantCodeAvailable($barcode, (int) $productVariant->id);
                            if ((string) ($productVariant->barcode ?? '') !== $barcode) {
                                $productVariant->barcode = $barcode;
                                $productVariant->save();
                            }
                        } catch (\Illuminate\Validation\ValidationException $exception) {
                            // Continue import when barcode is duplicated in DB or CSV.
                        }
                    }

                    $this->ensureVariantCodes($productVariant);

                    $createdVariants++;
                }
            }

            DB::commit();

            return response()->json([
                'success' => true,
                'message' => 'Importación completada correctamente.',
                'summary' => [
                    'created_categories' => $createdCategories,
                    'created_products' => $createdProducts,
                    'processed_variants' => $createdVariants,
                    'skipped_rows' => $skippedRows,
                ],
                'mapping' => $mapping,
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();

            return response()->json([
                'success' => false,
                'message' => 'No se pudo completar la importación.',
                'error' => $e->getMessage(),
            ], 422);
        }
    }

    private function extractRowsFromUploadedFile(string $path, string $extension): array
    {
        if (in_array($extension, ['csv', 'txt'])) {
            return $this->extractRowsFromCsvContent(file_get_contents($path) ?: '');
        }

        if (in_array($extension, ['json'])) {
            $decoded = json_decode(file_get_contents($path) ?: '[]', true);
            return is_array($decoded) ? $this->normalizeRawRows($decoded) : [];
        }

        if (in_array($extension, ['sql'])) {
            return $this->extractRowsFromSqlContent(file_get_contents($path) ?: '');
        }

        if (in_array($extension, ['xlsx', 'xls'], true)) {
            throw new \RuntimeException('Este sistema no permite importar XLSX/XLS. Usa CSV, JSON o SQL.');
        }

        throw new \RuntimeException('Formato no soportado. Usa CSV, JSON o SQL.');
    }

    private function extractRowsFromGoogleSheets(string $url): array
    {
        preg_match('/\/spreadsheets\/d\/([a-zA-Z0-9-_]+)/', $url, $docMatches);
        if (empty($docMatches[1])) {
            throw new \RuntimeException('URL de Google Sheets inválida.');
        }

        preg_match('/[?&]gid=(\d+)/', $url, $gidMatches);
        $gid = $gidMatches[1] ?? '0';

        $csvUrl = "https://docs.google.com/spreadsheets/d/{$docMatches[1]}/export?format=csv&gid={$gid}";
        $response = Http::timeout(30)->get($csvUrl);

        if (!$response->successful()) {
            throw new \RuntimeException('No se pudo descargar la hoja de Google Sheets. Verifica permisos de acceso.');
        }

        return $this->extractRowsFromCsvContent($response->body());
    }

    private function extractRowsFromCsvContent(string $content): array
    {
        // Normaliza BOM UTF-8 para evitar que el primer header quede contaminado.
        $content = preg_replace('/^\xEF\xBB\xBF/', '', $content) ?? $content;
        $lines = preg_split('/\r\n|\r|\n/', $content);
        $parsed = [];

        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }

            $columns = str_getcsv($line);

            // Algunos CSV vienen con TODA la fila encerrada en comillas.
            // Ej: "col1,col2,col3" -> str_getcsv() retorna una sola columna.
            if (count($columns) === 1 && str_contains((string) ($columns[0] ?? ''), ',')) {
                $collapsed = trim((string) $columns[0]);
                $columns = str_getcsv($collapsed);
            }

            $parsed[] = $columns;
        }

        if (empty($parsed)) {
            return [];
        }

        $headers = array_map(fn ($h) => trim((string) $h), array_shift($parsed));
        $rows = [];

        foreach ($parsed as $row) {
            if (count($row) === 1 && str_contains((string) ($row[0] ?? ''), ',')) {
                $row = str_getcsv((string) $row[0]);
            }

            $assoc = [];
            foreach ($headers as $index => $header) {
                if ($header === '') {
                    continue;
                }
                $assoc[$header] = $row[$index] ?? null;
            }
            $rows[] = $assoc;
        }

        return $rows;
    }

    private function extractRowsFromSqlContent(string $sql): array
    {
        preg_match_all('/INSERT\s+INTO\s+`?([a-zA-Z0-9_]+)`?\s*\(([^\)]+)\)\s*VALUES\s*(.+?);/is', $sql, $matches, PREG_SET_ORDER);

        $categoriesById = [];
        $productsById = [];
        $variantsByProductId = [];
        $directRows = [];
        $categoryAutoId = 1;
        $productAutoId = 1;

        foreach ($matches as $match) {
            $table = strtolower(trim($match[1]));
            $columns = array_map(function ($item) {
                return trim(str_replace('`', '', $item));
            }, explode(',', $match[2]));

            $isCategoryTable = in_array($table, ['categories', 'category', 'categorias', 'categoria'], true);
            $isProductTable = in_array($table, ['products', 'product', 'productos', 'producto'], true);
            $isVariantTable = in_array($table, ['product_variants', 'variants', 'variantes', 'product_variant'], true);

            preg_match_all('/\((.*?)\)(?=,\s*\(|$)/s', $match[3], $valueGroups);
            foreach ($valueGroups[1] as $group) {
                $values = str_getcsv($group, ',', "'", '\\');
                $row = [];
                foreach ($columns as $index => $column) {
                    $value = $values[$index] ?? null;
                    $row[$column] = $this->decodeSqlValue($value);
                }

                $lowerRow = [];
                foreach ($row as $key => $value) {
                    $normalizedKey = Str::lower(Str::ascii((string) $key));
                    $normalizedKey = preg_replace('/[^a-z0-9_]+/', '_', $normalizedKey);
                    $lowerRow[(string) $normalizedKey] = $value;
                }

                $hasDirectCatalogShape = array_key_exists('category_name', $lowerRow)
                    && array_key_exists('product_name', $lowerRow)
                    && (
                        array_key_exists('variant_size', $lowerRow)
                        || array_key_exists('variant_price', $lowerRow)
                        || array_key_exists('variant_stock', $lowerRow)
                    );

                if ($hasDirectCatalogShape) {
                    $directRows[] = [
                        'category_name' => $lowerRow['category_name'] ?? null,
                        'category_description' => $lowerRow['category_description'] ?? null,
                        'product_name' => $lowerRow['product_name'] ?? null,
                        'product_description' => $lowerRow['product_description'] ?? null,
                        'variant_size' => $lowerRow['variant_size'] ?? 'Única',
                        'variant_price' => $lowerRow['variant_price'] ?? 0,
                        'variant_stock' => $lowerRow['variant_stock'] ?? 0,
                        'variant_unit_type' => $lowerRow['variant_unit_type'] ?? 'unidad',
                    ];
                    continue;
                }

                if ($isCategoryTable) {
                    $categoryId = (string) ($this->extractFirstSqlValue($row, ['id', 'id_categoria', 'category_id']) ?? $categoryAutoId++);
                    $categoryName = $this->extractFirstSqlValue($row, ['name', 'nombre', 'nombre_categoria', 'category_name']);
                    if (!is_null($categoryName) && trim((string) $categoryName) !== '') {
                        $categoriesById[$categoryId] = [
                            'name' => $categoryName,
                            'description' => $this->extractFirstSqlValue($row, ['description', 'descripcion', 'descripcion_categoria', 'category_description']),
                        ];
                    }
                }
                if ($isProductTable) {
                    $productId = (string) ($this->extractFirstSqlValue($row, ['id', 'id_producto', 'product_id']) ?? $productAutoId++);
                    $productsById[$productId] = [
                        'id' => $productId,
                        'category_id' => $this->extractFirstSqlValue($row, ['category_id', 'id_categoria', 'categoria_id']),
                        'name' => $this->extractFirstSqlValue($row, ['name', 'nombre', 'product_name', 'nombre_producto']),
                        'description' => $this->extractFirstSqlValue($row, ['description', 'descripcion', 'product_description', 'descripcion_producto']),
                        'price' => $this->extractFirstSqlValue($row, ['price', 'precio', 'variant_price']),
                        'stock' => $this->extractFirstSqlValue($row, ['stock', 'existencia', 'inventario', 'variant_stock']),
                        'unit_type' => $this->extractFirstSqlValue($row, ['unit_type', 'tipo_unidad', 'unidad', 'variant_unit_type']),
                    ];
                }
                if ($isVariantTable) {
                    $productId = (string) ($this->extractFirstSqlValue($row, ['product_id', 'id_producto']) ?? '');
                    if (!isset($variantsByProductId[$productId])) {
                        $variantsByProductId[$productId] = [];
                    }
                    $variantsByProductId[$productId][] = [
                        'size' => $this->extractFirstSqlValue($row, ['size', 'talla', 'variant_size']),
                        'price' => $this->extractFirstSqlValue($row, ['price', 'precio', 'variant_price']),
                        'stock' => $this->extractFirstSqlValue($row, ['stock', 'existencia', 'inventario', 'variant_stock']),
                        'unit_type' => $this->extractFirstSqlValue($row, ['unit_type', 'tipo_unidad', 'unidad', 'variant_unit_type']),
                    ];
                }
            }
        }

        if (!empty($directRows)) {
            return $directRows;
        }

        $rows = [];
        foreach ($productsById as $productId => $product) {
            $categoryId = (string) ($product['category_id'] ?? '');
            $category = $categoriesById[$categoryId] ?? [];
            $variants = $variantsByProductId[$productId] ?? [];

            if (empty($variants)) {
                $rows[] = [
                    'category_name' => $category['name'] ?? null,
                    'category_description' => $category['description'] ?? null,
                    'product_name' => $product['name'] ?? null,
                    'product_description' => $product['description'] ?? null,
                    'variant_size' => 'Única',
                    'variant_price' => $product['price'] ?? 0,
                    'variant_stock' => $product['stock'] ?? 0,
                    'variant_unit_type' => $product['unit_type'] ?? 'unidad',
                ];
                continue;
            }

            foreach ($variants as $variant) {
                $rows[] = [
                    'category_name' => $category['name'] ?? null,
                    'category_description' => $category['description'] ?? null,
                    'product_name' => $product['name'] ?? null,
                    'product_description' => $product['description'] ?? null,
                    'variant_size' => $variant['size'] ?? null,
                    'variant_price' => $variant['price'] ?? null,
                    'variant_stock' => $variant['stock'] ?? null,
                    'variant_unit_type' => $variant['unit_type'] ?? null,
                ];
            }
        }

        return $rows;
    }

    private function extractRowsFromSqlWithGemini(string $sql): array
    {
        $apiKey = config('services.gemini.api_key');
        $textModel = config('services.gemini.text_model', 'gemini-2.5-flash');
        if (empty($apiKey)) {
            return [];
        }

        $trimmedSql = trim($sql);
        if ($trimmedSql === '') {
            return [];
        }

        $prompt = "Convierte este SQL en un JSON array para importar catálogo de ecommerce. "
            . "Cada objeto debe tener: category_name, category_description, product_name, product_description, variant_size, variant_price, variant_stock, variant_unit_type. "
            . "Si no hay variantes, crea una variante única por producto. Responde SOLO JSON válido, sin markdown ni texto extra. SQL: "
            . mb_substr($trimmedSql, 0, 14000);

        $response = Http::timeout(40)->post(
            "https://generativelanguage.googleapis.com/v1beta/models/{$textModel}:generateContent?key={$apiKey}",
            [
                'contents' => [
                    ['parts' => [['text' => $prompt]]],
                ],
            ]
        );

        if (!$response->successful()) {
            return [];
        }

        $text = data_get($response->json(), 'candidates.0.content.parts.0.text', '');
        if (!is_string($text) || trim($text) === '') {
            return [];
        }

        $clean = trim($text);
        $clean = preg_replace('/^```json\s*/', '', $clean);
        $clean = preg_replace('/^```\s*/', '', (string) $clean);
        $clean = preg_replace('/\s*```$/', '', (string) $clean);

        $decoded = json_decode((string) $clean, true);
        if (!is_array($decoded)) {
            return [];
        }

        $rows = [];
        foreach ($decoded as $row) {
            if (!is_array($row)) {
                continue;
            }

            $categoryName = trim((string) ($row['category_name'] ?? ''));
            $productName = trim((string) ($row['product_name'] ?? ''));
            if ($categoryName === '' || $productName === '') {
                continue;
            }

            $rows[] = [
                'category_name' => $categoryName,
                'category_description' => $row['category_description'] ?? null,
                'product_name' => $productName,
                'product_description' => $row['product_description'] ?? null,
                'variant_size' => $row['variant_size'] ?? 'Única',
                'variant_price' => $row['variant_price'] ?? 0,
                'variant_stock' => $row['variant_stock'] ?? 0,
                'variant_unit_type' => $row['variant_unit_type'] ?? 'unidad',
            ];
        }

        return $rows;
    }

    private function extractFirstSqlValue(array $row, array $candidates)
    {
        foreach ($candidates as $candidate) {
            if (!array_key_exists($candidate, $row)) {
                continue;
            }

            $value = $row[$candidate];
            if (!is_null($value) && trim((string) $value) !== '') {
                return $value;
            }
        }

        return null;
    }

    private function decodeSqlValue($value)
    {
        if ($value === null) {
            return null;
        }

        $trimmed = trim((string) $value);
        if (strtoupper($trimmed) === 'NULL') {
            return null;
        }

        if (Str::startsWith($trimmed, "'") && Str::endsWith($trimmed, "'")) {
            return stripcslashes(substr($trimmed, 1, -1));
        }

        return $trimmed;
    }

    private function normalizeRawRows(array $rows): array
    {
        if (empty($rows)) {
            return [];
        }

        if (array_is_list($rows) && is_array($rows[0] ?? null)) {
            return $rows;
        }

        return [];
    }

    private function resolveImportMapping(array $headers, bool $useGemini, array $rows = []): array
    {
        $mapping = $this->guessMappingByHeuristics($headers);

        if (!$useGemini) {
            return $this->sanitizeImportMapping($mapping);
        }

        $geminiMapping = $this->guessMappingWithGemini($headers, $rows);
        return $this->sanitizeImportMapping(array_merge($mapping, array_filter($geminiMapping)));
    }

    private function guessMappingByHeuristics(array $headers): array
    {
        $synonyms = [
            'category_name' => ['category_name', 'nombre_categoria', 'categoria_nombre', 'category', 'categoria'],
            'category_description' => ['category_description', 'descripcion_categoria', 'desc_categoria'],
            'product_name' => ['product_name', 'nombre_producto', 'producto_nombre', 'producto_base', 'producto', 'name', 'nombre', 'item', 'articulo'],
            'product_description' => ['product_description', 'descripcion_producto', 'description', 'descripcion'],
            'variant_size' => ['size', 'talla', 'variant_size', 'variante', 'variantes', 'variantes_especificaciones', 'especificaciones', 'presentacion'],
            'variant_price' => ['price', 'precio', 'variant_price', 'costo'],
            'variant_stock' => ['stock', 'existencia', 'inventario', 'cantidad', 'cant', 'qty', 'variant_stock'],
            'variant_barcode' => ['variant_barcode', 'barcode', 'codigo', 'codigo_barras', 'cod_barra'],
            'variant_unit_type' => ['unit_type', 'unidad', 'tipo_unidad'],
            'variants' => ['variants', 'variantes'],
            'product_is_active' => ['product_is_active', 'activo_producto', 'estado_producto'],
            'category_is_active' => ['category_is_active', 'activo_categoria', 'estado_categoria'],
        ];

        $mapping = [];
        foreach ($headers as $header) {
            $normalizedHeader = Str::lower(Str::ascii(trim((string) $header)));
            $normalizedHeader = preg_replace('/[^a-z0-9_]+/', '_', $normalizedHeader);

            if ($this->isLikelyIdHeader((string) $normalizedHeader)) {
                continue;
            }

            foreach ($synonyms as $target => $words) {
                foreach ($words as $word) {
                    if (
                        $normalizedHeader === $word
                        || Str::startsWith($normalizedHeader, $word . '_')
                        || Str::endsWith($normalizedHeader, '_' . $word)
                    ) {
                        if (!isset($mapping[$target])) {
                            $mapping[$target] = $header;
                        }
                        break 2;
                    }
                }
            }
        }

        return $mapping;
    }

    private function guessMappingWithGemini(array $headers, array $rows = []): array
    {
        $apiKey = config('services.gemini.api_key');
        $textModel = config('services.gemini.text_model', 'gemini-2.5-flash');
        if (empty($apiKey) || empty($headers)) {
            return [];
        }

        $targetFields = [
            'category_name',
            'category_description',
            'product_name',
            'product_description',
            'variant_size',
            'variant_price',
            'variant_stock',
            'variant_barcode',
            'variant_unit_type',
            'variants',
            'product_is_active',
            'category_is_active',
        ];

        $sampleRows = array_values(array_filter(array_map(function ($row) {
            return is_array($row) ? $row : null;
        }, array_slice($rows, 0, 3))));

        $prompt = "Mapea estos headers a campos del sistema. Responde SOLO JSON objeto {campoSistema: headerOriginal}. Headers: "
            . json_encode(array_values($headers), JSON_UNESCAPED_UNICODE)
            . ". Muestra de filas: " . json_encode($sampleRows, JSON_UNESCAPED_UNICODE)
            . ". Campos permitidos: " . json_encode($targetFields, JSON_UNESCAPED_UNICODE);

        $response = Http::timeout(25)->post(
            "https://generativelanguage.googleapis.com/v1beta/models/{$textModel}:generateContent?key={$apiKey}",
            [
                'contents' => [
                    ['parts' => [['text' => $prompt]]],
                ],
            ]
        );

        if (!$response->successful()) {
            return [];
        }

        $text = data_get($response->json(), 'candidates.0.content.parts.0.text', '');
        if (!is_string($text) || trim($text) === '') {
            return [];
        }

        $clean = trim($text);
        $clean = preg_replace('/^```json\s*/', '', $clean);
        $clean = preg_replace('/\s*```$/', '', (string) $clean);

        $decoded = json_decode($clean, true);
        if (!is_array($decoded)) {
            return [];
        }

        $allowed = array_flip($targetFields);
        $result = [];
        foreach ($decoded as $key => $value) {
            if (isset($allowed[$key]) && is_string($value)) {
                $result[$key] = $value;
            }
        }

        return $result;
    }

    private function sanitizeImportMapping(array $mapping): array
    {
        $result = [];
        foreach ($mapping as $target => $sourceHeader) {
            if (!is_string($sourceHeader)) {
                continue;
            }

            $normalizedHeader = Str::lower(Str::ascii(trim($sourceHeader)));
            $normalizedHeader = preg_replace('/[^a-z0-9_]+/', '_', $normalizedHeader);

            if (
                in_array($target, ['product_name', 'category_name'], true)
                && $this->isLikelyIdHeader((string) $normalizedHeader)
            ) {
                continue;
            }

            $result[$target] = $sourceHeader;
        }

        return $result;
    }

    private function isLikelyIdHeader(string $normalizedHeader): bool
    {
        $header = trim($normalizedHeader);
        if ($header === '' || $header === 'id') {
            return true;
        }

        return Str::startsWith($header, 'id_')
            || Str::endsWith($header, '_id')
            || in_array($header, ['idproducto', 'idcategoria', 'id_variant', 'id_variante', 'product_id', 'category_id', 'id_producto', 'id_categoria'], true);
    }

    private function normalizeImportRows(array $rows, array $mapping): array
    {
        $output = [];

        foreach ($rows as $row) {
            if (!is_array($row)) {
                continue;
            }

            $normalized = [];
            foreach ($mapping as $target => $sourceHeader) {
                $normalized[$target] = $row[$sourceHeader] ?? null;
            }

            if (isset($normalized['variants']) && is_string($normalized['variants'])) {
                $trim = trim($normalized['variants']);
                if (Str::startsWith($trim, '[') || Str::startsWith($trim, '{')) {
                    $decoded = json_decode($trim, true);
                    if (is_array($decoded)) {
                        $normalized['variants'] = array_is_list($decoded) ? $decoded : [$decoded];
                    }
                }
            }

            $output[] = $normalized;
        }

        return $output;
    }

    private function parseLocalizedNumber($value, float $default = 0.0): float
    {
        if (is_int($value) || is_float($value)) {
            return (float) $value;
        }

        $text = trim((string) $value);
        if ($text === '') {
            return $default;
        }

        $text = str_replace(["\xc2\xa0", ' '], '', $text);
        $text = preg_replace('/[^\d,\.\-]/u', '', $text) ?? '';

        if ($text === '' || $text === '-' || $text === ',' || $text === '.') {
            return $default;
        }

        $commaPos = strrpos($text, ',');
        $dotPos = strrpos($text, '.');

        if ($commaPos !== false && $dotPos !== false) {
            if ($commaPos > $dotPos) {
                $text = str_replace('.', '', $text);
                $text = str_replace(',', '.', $text);
            } else {
                $text = str_replace(',', '', $text);
            }
        } elseif ($commaPos !== false) {
            $text = str_replace('.', '', $text);
            $text = str_replace(',', '.', $text);
        }

        return is_numeric($text) ? (float) $text : $default;
    }

    private function clampStringToLength(string $value, int $maxLength): string
    {
        $trimmed = trim($value);
        if ($maxLength <= 0) {
            return $trimmed;
        }

        if (mb_strlen($trimmed) <= $maxLength) {
            return $trimmed;
        }

        return rtrim(mb_substr($trimmed, 0, $maxLength));
    }

    private function resolveVarcharColumnMaxLength(string $table, string $column, int $fallback): int
    {
        try {
            $row = DB::selectOne(
                'SELECT CHARACTER_MAXIMUM_LENGTH AS max_length FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ? LIMIT 1',
                [$table, $column]
            );

            $maxLength = (int) ($row->max_length ?? 0);
            return $maxLength > 0 ? $maxLength : $fallback;
        } catch (\Throwable $exception) {
            return $fallback;
        }
    }
    
    public function storeGoogle(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|gt:0',
            'images.*' => 'nullable|image|mimes:jpeg,png,jpg,gif,webp|max:2048',
        ]);

        $product = Product::create([
            'name' => $request->name,
            'slug' => $this->generateUniqueProductSlug((string) $request->name, (int) ($request->tenant_id ?? auth()->user()?->tenant_id ?? 0)),
            'description' => $request->description,
            'price' => $request->price,
        ]);

        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $filePath = $image->getPathName();
                $fileName = $image->getClientOriginalName();
                $fileMetadata = new Drive\DriveFile([
                    'name' => $fileName,
                    'parents' => ['your-folder-id']
                ]);

                $content = file_get_contents($filePath);
                $file = $service->files->create($fileMetadata, [
                    'data' => $content,
                    'mimeType' => $image->getMimeType(),
                    'uploadType' => 'multipart',
                    'fields' => 'id'
                ]);

                $fileId = $file->id;
                $fileUrl = "https://drive.google.com/uc?export=view&id={$fileId}";

                ProductImage::create([
                    'product_id' => $product->id,
                    'path' => $fileUrl,
                ]);
            }
        }

        return response()->json(['success' => true, 'message' => 'Product created successfully', 'product' => $product], 201);
    }

    public function show($id) {
        $product = Product::with(['images', 'variants', 'category'])->findOrFail($id);
        return response()->json($product);
    }
    
    public function update(Request $request, $id)
    {
        $authUser = auth()->user();
        if (!$authUser) {
            return response()->json([
                'success' => false,
                'message' => 'Sesión expirada. Inicia sesión nuevamente.',
            ], 401);
        }

        DB::raw("SET @user_id = " . $authUser->id);

        $tenantId = (int) ($authUser->tenant_id ?? 0);

        $product = Product::findOrFail($id);

        if ($tenantId > 0 && (int) $product->tenant_id !== $tenantId) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado.',
            ], 403);
        }

        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'sometimes|nullable|exists:categories,id',
            'category_id' => 'sometimes|nullable|exists:categories,id',
            'is_active' => 'sometimes|required|boolean',
            'is_consumable' => 'nullable|boolean',
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
        ]);

        $categoryId = (int) ($validated['category'] ?? $validated['category_id'] ?? $product->category_id ?? 0);
        if ($categoryId <= 0) {
            return response()->json([
                'success' => false,
                'message' => 'Debes seleccionar una categoría válida.',
            ], 422);
        }

        $categoryBelongsToTenant = Category::query()
            ->where('id', $categoryId)
            ->where('tenant_id', (int) $product->tenant_id)
            ->exists();

        if (!$categoryBelongsToTenant) {
            return response()->json([
                'success' => false,
                'message' => 'La categoría seleccionada no pertenece al tenant del producto.',
            ], 422);
        }

        $product->update([
            'name' => $validated['name'] ?? $product->name,
            'slug' => $this->generateUniqueProductSlug((string) ($validated['name'] ?? $product->name), (int) $product->tenant_id, (int) $product->id),
            'description' => array_key_exists('description', $validated) ? ($validated['description'] ?? null) : $product->description,
            'category_id' => $categoryId,
            'is_active' => array_key_exists('is_active', $validated) ? (bool) $validated['is_active'] : (bool) $product->is_active,
            'is_consumable' => array_key_exists('is_consumable', $validated) ? (bool) $validated['is_consumable'] : (bool) $product->is_consumable,
            'discount_percentage' => array_key_exists('discount_percentage', $validated) ? (float) $validated['discount_percentage'] : (float) ($product->discount_percentage ?? 0),
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Producto actualizado correctamente',
            'product' => $product,
        ]);
    }

    public function destroy($id)
    {
        DB::raw("SET @user_id = " . auth()->id());

        $product = Product::with(['images', 'variants'])->findOrFail($id);

        if ((int) ($product->tenant_id ?? 0) !== (int) (auth()->user()->tenant_id ?? 0)) {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado.',
            ], 403);
        }

        $variantIds = $product->variants->pluck('id')->filter()->all();

        $protectionReasons = $this->productProtectionReasons($variantIds);

        if (!empty($protectionReasons)) {
            return response()->json([
                'success' => false,
                'message' => 'No se puede eliminar el producto porque ya tiene registros asociados en: ' . implode(', ', $protectionReasons) . '. Puedes desactivarlo en su lugar.',
            ], 409);
        }

        DB::transaction(function () use ($product) {
            foreach ($product->images as $image) {
                ImageStorage::delete($image->path);
            }

            $product->delete();
        });

        return response()->json([
            'success' => true,
            'message' => 'Producto eliminado correctamente.',
        ]);
    }

    private function generateUniqueProductSlug(string $name, int $tenantId, ?int $ignoreProductId = null): string
    {
        $baseSlug = Str::slug($name);
        if ($baseSlug === '') {
            $baseSlug = 'producto';
        }

        $slug = $baseSlug;
        $suffix = 2;

        while ($this->productSlugExists($slug, $tenantId, $ignoreProductId)) {
            $slug = $baseSlug . '-' . $suffix;
            $suffix++;
        }

        return $slug;
    }

    private function productSlugExists(string $slug, int $tenantId, ?int $ignoreProductId = null): bool
    {
        return Product::query()
            ->where('tenant_id', $tenantId)
            ->where('slug', $slug)
            ->when($ignoreProductId, fn ($query) => $query->where('id', '!=', $ignoreProductId))
            ->exists();
    }

    private function productProtectionReasons(array $variantIds): array
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

    private function allowedProductTaxes()
    {
        return Tax::query()
            ->orderBy('rate')
            ->orderBy('name')
            ->get()
            ->filter(fn (Tax $tax) => $this->isAllowedIntrinsicProductTax($tax))
            ->values();
    }

    private function assertValidProductTaxSelection(array $taxIds): void
    {
        if (empty($taxIds)) {
            return;
        }

        $selectedTaxes = Tax::query()
            ->whereIn('id', collect($taxIds)->map(fn ($id) => (int) $id)->unique()->values()->all())
            ->get();

        $invalid = $selectedTaxes
            ->reject(fn (Tax $tax) => $this->isAllowedIntrinsicProductTax($tax))
            ->pluck('name')
            ->all();

        if (!empty($invalid)) {
            throw ValidationException::withMessages([
                'tax_ids' => ['Solo puedes asignar IVA intrínseco de producto (0%, 8%, 16% o 31%). Impuestos no válidos: ' . implode(', ', $invalid)],
            ]);
        }
    }

    private function isAllowedIntrinsicProductTax(Tax $tax): bool
    {
        $haystack = Str::lower(Str::ascii(trim((string) ($tax->name ?? '') . ' ' . (string) ($tax->code ?? ''))));

        if (str_contains($haystack, 'igtf') || str_contains($haystack, 'islr') || str_contains($haystack, 'retencion')) {
            return false;
        }

        return true;
    }

    public function generateReport(Request $request)
    {
        $products = Product::with('variants')
        ->where('tenant_id', $request->tenant_id)
        ->get();

        $csvData = "Nombre,Descripción,Precio,Stock Total\n";

        foreach ($products as $product) {
            $totalStock = $product->variants->sum('stock');
            $csvData .= "{$product->name},{$product->description},{$product->price},{$totalStock}\n";
        }

        $fileName = "reporte_productos_" . now()->format('Y-m-d') . ".csv";

        return Response::make($csvData, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename={$fileName}",
        ]);
    }

    public function downloadImportTemplate()
    {
        $csvData = "category_name,category_description,product_name,product_description,variant_size,variant_price,variant_stock,variant_unit_type,category_is_active,product_is_active\n";
        $csvData .= "Calzado,Calzado deportivo,Tenis Runner,Tenis para correr con amortiguacion,40,59.99,15,par,1,1\n";
        $csvData .= "Calzado,Calzado deportivo,Tenis Runner,Tenis para correr con amortiguacion,41,59.99,12,par,1,1\n";
        $csvData .= "Accesorios,Accesorios fitness,Botella Termica,Botella acero inoxidable 750ml,Unica,14.50,30,unidad,1,1\n";

        $fileName = 'plantilla_importacion_catalogo_shopix.csv';

        return Response::make($csvData, 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => "attachment; filename={$fileName}",
        ]);
    }
    
}
