<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SellerCommission extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'seller_user_id',
        'sales_order_id',
        'commission_base_amount',
        'commission_rate',
        'commission_amount',
        'currency_code',
        'status',
        'calculated_at',
        'paid_at',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'commission_base_amount' => 'decimal:4',
        'commission_rate' => 'decimal:2',
        'commission_amount' => 'decimal:4',
        'calculated_at' => 'datetime',
        'paid_at' => 'datetime',
    ];

    public function seller()
    {
        return $this->belongsTo(User::class, 'seller_user_id');
    }

    public function order()
    {
        return $this->belongsTo(SalesOrder::class, 'sales_order_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
