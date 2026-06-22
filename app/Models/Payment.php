<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'sales_order_id', 'payment_method', 'amount', 'amount_original', 'amount_base', 'exchange_rate_to_base', 'applies_igtf', 'currency', 'reference', 'status'
    ];

    protected $casts = [
        'amount' => 'float',
        'amount_original' => 'float',
        'amount_base' => 'float',
        'exchange_rate_to_base' => 'float',
        'applies_igtf' => 'boolean',
    ];

    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class);
    }
    public function images()
    {
        return $this->hasMany(PaymentImage::class);
    }
    public function payment()
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method', 'id');
    }
}
