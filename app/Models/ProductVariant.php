<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    use HasFactory;

    public const UNIT_TYPE_OPTIONS = [
        'unidad' => 'Unidad',
        'kg' => 'Kilogramo (kg)',
        'g' => 'Gramo (g)',
        'lb' => 'Libra (lb)',
        'm' => 'Metro (m)',
        'cm' => 'Centimetro (cm)',
        'mm' => 'Milimetro (mm)',
        'm2' => 'Metro cuadrado (m2)',
        'm3' => 'Metro cubico (m3)',
        'l' => 'Litro (l)',
        'ml' => 'Mililitro (ml)',
        'caja' => 'Caja',
        'paquete' => 'Paquete',
        'rollo' => 'Rollo',
        'pieza' => 'Pieza',
    ];

    public const QUANTITY_INPUT_MODE_OPTIONS = [
        'integer' => 'Entero',
        'decimal' => 'Decimal',
    ];

    protected $fillable = [
        'product_id',
        'size',
        'price',
        'discount_percentage',
        'qr_code',
        'barcode',
        'stock',
        'unit_type',
        'quantity_input_mode',
        'min_sale_quantity',
    ];

    protected $casts = [
        'price' => 'float',
        'discount_percentage' => 'float',
        'stock' => 'float',
        'min_sale_quantity' => 'float',
    ];

    public static function normalizeUnitType(?string $value): string
    {
        $normalized = strtolower(trim((string) $value));
        return array_key_exists($normalized, self::UNIT_TYPE_OPTIONS) ? $normalized : 'unidad';
    }

    public static function normalizeQuantityInputMode(?string $value): string
    {
        $normalized = strtolower(trim((string) $value));
        return array_key_exists($normalized, self::QUANTITY_INPUT_MODE_OPTIONS) ? $normalized : 'integer';
    }

    public static function normalizeMinSaleQuantity($value, ?string $quantityInputMode = null): float
    {
        $mode = self::normalizeQuantityInputMode($quantityInputMode);
        $numericValue = is_numeric($value) ? (float) $value : 1.0;

        if ($mode === 'decimal') {
            $normalized = round($numericValue, 2);
            return $normalized > 0 ? $normalized : 1.0;
        }

        $normalized = (float) round($numericValue);
        return $normalized > 0 ? $normalized : 1.0;
    }


    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class, 'product_variant_id');
    }

    public function warehouseStocks()
    {
        return $this->hasMany(ProductVariantWarehouseStock::class, 'product_variant_id');
    }

    public function getDiscountPercentageAttribute($value)
    {
        return (float) ($value ?? 0);
    }

    public function getEffectivePriceAttribute(): float
    {
        $basePrice = (float) ($this->price ?? 0);
        $productMultiplier = $this->product?->discount_multiplier ?? 1;
        $variantDiscount = max(0, min(100, (float) $this->discount_percentage));
        $variantMultiplier = (100 - $variantDiscount) / 100;

        return round($basePrice * $productMultiplier * $variantMultiplier, 2);
    }
}

