<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesOrderDetail extends Model
{
    use HasFactory;

    protected $fillable = ['sales_order_id', 'product_variant_id', 'quantity', 'price', 'amount', 'line_subtotal_before_discount', 'line_discount_amount',
        'tax_name',
        'tax_rate', 'tax_amount'
    ];

    protected $casts = [
        'quantity' => 'float',
        'price' => 'float',
        'amount' => 'float',
        'line_subtotal_before_discount' => 'float',
        'line_discount_amount' => 'float',
        'tax_rate' => 'float',
        'tax_amount' => 'float',
    ];

    // En el modelo SalesOrderDetail
    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
    
    public function tax()
    {
        return $this->hasOneThrough(
            Tax::class,
            ProductVariant::class,
            'id',              // Variant.id
            'id',              // Tax.id
            'product_variant_id', // SalesOrderDetail.product_variant_id
            'tax_id'           // Product.tax_id
        );
    }
    public function taxes()
    {
        return $this->hasMany(SalesDetailTax::class);
    }
}
