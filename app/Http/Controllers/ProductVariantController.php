<?php

namespace App\Http\Controllers;

use App\Models\ProductVariant;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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

        $request->validate([
            'product_id' => 'required|integer|exists:products,id',
            'variants' => 'required|array',
            'variants.*.size' => 'required|string|max:10',
            'variants.*.price' => 'required|numeric',
            'variants.*.discount_percentage' => 'nullable|numeric|min:0|max:100',
            'variants.*.stock' => 'required|integer',
            'variants.*.barcode' => 'nullable|string|max:100',
        ]);
    
        foreach ($request->variants as $variant) {
            $barcode = $this->sanitizeVariantCode($variant['barcode'] ?? null);
            $this->assertVariantCodeAvailable($barcode);

            $createdVariant = ProductVariant::create([
                'product_id' => $request->product_id,
                'size' => $variant['size'],
                'price' => $variant['price'],
                'discount_percentage' => (float) ($variant['discount_percentage'] ?? 0),
                'stock' => $variant['stock'],
                'barcode' => $barcode,
            ]);

            if (empty($createdVariant->qr_code)) {
                $createdVariant->qr_code = $this->generateUniqueVariantCode('QRV');
            }

            if (empty($createdVariant->barcode)) {
                $createdVariant->barcode = $this->generateUniqueVariantCode('BCV');
            }

            $createdVariant->save();
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
            'discount_percentage' => 'nullable|numeric|min:0|max:100',
            'stock' => 'required|integer',
            'barcode' => 'nullable|string|max:100',
        ]);

        $barcode = $this->sanitizeVariantCode($request->input('barcode'));
        $this->assertVariantCodeAvailable($barcode, (int) $productVariant->id);
    
        // Actualizar la variante con los datos proporcionados
        $productVariant->update([
            'size' => $request->input('size'),
            'price' => $request->input('price'),
            'discount_percentage' => $request->input('discount_percentage', 0),
            'stock' => $request->input('stock'),
            'barcode' => $barcode,
        ]);

        if (empty($productVariant->barcode)) {
            $productVariant->barcode = $this->generateUniqueVariantCode('BCV');
            $productVariant->save();
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

        $productVariant->delete();

        return redirect()->route('products.show', $productVariant->product_id)
                         ->with('success', 'Variante eliminada exitosamente.');
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
}

