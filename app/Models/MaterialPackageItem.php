<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaterialPackageItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'material_package_id',
        'product_variant_id',
        'quantity',
        'selection_mode',
        'discount_percentage',
    ];

    public function materialPackage()
    {
        return $this->belongsTo(MaterialPackage::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
