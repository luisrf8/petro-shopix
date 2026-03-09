<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaterialPackage extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'discount_percentage',
        'package_price',
        'qr_code',
        'barcode',
        'is_active',
    ];

    public function getDiscountPercentageAttribute($value)
    {
        return (float) ($value ?? 0);
    }

    public function items()
    {
        return $this->hasMany(MaterialPackageItem::class);
    }
}
