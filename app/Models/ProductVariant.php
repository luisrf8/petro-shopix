<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductVariant extends Model
{
    use HasFactory;

    protected $fillable = [
        'product_id',
        'size',
        'price',
        'discount_percentage',
        'qr_code',
        'barcode',
        'stock',
        'unit_type', // agregado
    ];


    public function product()
    {
        return $this->belongsTo(Product::class);
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

