<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectQuotationItem extends Model
{
    use HasFactory;

    protected $table = 'pm_quotation_items';

    protected $fillable = [
        'quotation_id',
        'tenant_id',
        'product_id',
        'product_variant_id',
        'item_type',
        'service_name',
        'description',
        'quantity',
        'unit_price',
        'discount_percent',
        'total',
    ];

    protected $casts = [
        'quantity' => 'float',
        'unit_price' => 'float',
        'discount_percent' => 'float',
        'total' => 'float',
    ];

    public function quotation()
    {
        return $this->belongsTo(ProjectQuotation::class, 'quotation_id');
    }

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}
