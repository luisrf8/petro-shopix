<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'discount_percentage',
        'category_id',
        'is_active',
        'tenant_id'
    ];

    public function getDiscountPercentageAttribute($value)
    {
        return (float) ($value ?? 0);
    }

    public function getDiscountMultiplierAttribute(): float
    {
        $discount = max(0, min(100, (float) $this->discount_percentage));
        return (100 - $discount) / 100;
    }

    public function images()
    {
        return $this->hasMany(ProductImage::class)->whereNull('product_variant_id');
    }

    public function allImages()
    {
        return $this->hasMany(ProductImage::class);
    }
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }
    public function taxes()
    {
        return $this->belongsToMany(Tax::class, 'product_tax');
    }
}
