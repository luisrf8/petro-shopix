<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderConsumption extends Model
{
    use HasFactory;

    protected $fillable = [
        'purchase_order_id',
        'produced_variant_id',
        'consumed_variant_id',
        'quantity',
        'unit_cost',
        'amount',
        'tenant_id',
    ];

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function consumedVariant()
    {
        return $this->belongsTo(ProductVariant::class, 'consumed_variant_id');
    }

    public function producedVariant()
    {
        return $this->belongsTo(ProductVariant::class, 'produced_variant_id');
    }
}
