<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Product extends Model
{
    use HasFactory;

    private static array $tenantCategorySuffixCache = [];

    protected $fillable = [
        'name',
        'slug',
        'description',
        'discount_percentage',
        'is_consumable',
        'category_id',
        'is_active',
        'tenant_id'
    ];

    protected $casts = [
        'is_consumable' => 'boolean',
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

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function taxes()
    {
        return $this->belongsToMany(Tax::class, 'product_tax');
    }

    public function getDisplayNameAttribute(): string
    {
        $baseName = trim((string) ($this->name ?? ''));
        if ($baseName === '') {
            return 'Producto';
        }

        if (!$this->shouldAppendCategorySuffix()) {
            return $baseName;
        }

        $categoryName = trim((string) ($this->category?->name ?? ''));
        if ($categoryName === '') {
            return $baseName;
        }

        if (Str::contains(Str::lower($baseName), Str::lower($categoryName))) {
            return $baseName;
        }

        return $baseName . ' - ' . $categoryName;
    }

    protected function shouldAppendCategorySuffix(): bool
    {
        $tenantId = (int) ($this->tenant_id ?? 0);
        if ($tenantId <= 0) {
            return false;
        }

        if (array_key_exists($tenantId, self::$tenantCategorySuffixCache)) {
            return self::$tenantCategorySuffixCache[$tenantId];
        }

        if ($this->relationLoaded('tenant') && $this->tenant) {
            $enabled = (bool) ($this->tenant->show_product_category_suffix ?? false);
            self::$tenantCategorySuffixCache[$tenantId] = $enabled;
            return $enabled;
        }

        $enabled = (bool) Tenant::query()->whereKey($tenantId)->value('show_product_category_suffix');
        self::$tenantCategorySuffixCache[$tenantId] = $enabled;

        return $enabled;
    }
}
