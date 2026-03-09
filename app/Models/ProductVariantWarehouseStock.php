<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductVariantWarehouseStock extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'warehouse_id',
        'product_variant_id',
        'quantity',
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
