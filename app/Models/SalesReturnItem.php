<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesReturnItem extends Model
{
    use HasFactory;
    protected $fillable = [
        'sales_return_id',
        'product_variant_id',
        'quantity',
        'price',
        'total',
        'reason',
        'disposition',
        'returned_subtotal',
        'returned_tax_amount',
        'returned_igtf_amount',
    ];

    protected $casts = [
        'quantity' => 'float',
        'price' => 'float',
        'returned_subtotal' => 'float',
        'returned_tax_amount' => 'float',
        'returned_igtf_amount' => 'float',
    ];

    public function salesReturn()
    {
        return $this->belongsTo(SalesReturn::class);
    }

}
