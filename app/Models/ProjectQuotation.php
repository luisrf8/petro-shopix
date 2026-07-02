<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectQuotation extends Model
{
    use HasFactory;

    protected $table = 'pm_quotations';

    protected $fillable = [
        'tenant_id',
        'type',
        'quotation_kind',
        'status',
        'title',
        'customer_id',
        'customer_name',
        'customer_email',
        'provider_id',
        'provider_name',
        'discount_percent',
        'subtotal',
        'discount_amount',
        'total_amount',
        'currency_code',
        'exchange_rate_to_bs',
        'base_rate_to_bs',
        'usd_rate_to_bs',
        'valid_until',
        'notes',
        'conversion_target',
        'converted_sale_reference',
        'converted_project_id',
        'converted_purchase_order_id',
        'converted_to_sale_at',
        'converted_to_project_at',
        'converted_to_inventory_at',
        'created_by',
    ];

    protected $casts = [
        'discount_percent' => 'float',
        'subtotal' => 'float',
        'discount_amount' => 'float',
        'total_amount' => 'float',
        'exchange_rate_to_bs' => 'float',
        'base_rate_to_bs' => 'float',
        'usd_rate_to_bs' => 'float',
        'valid_until' => 'date',
        'converted_to_sale_at' => 'datetime',
        'converted_to_project_at' => 'datetime',
        'converted_to_inventory_at' => 'datetime',
    ];

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function items()
    {
        return $this->hasMany(ProjectQuotationItem::class, 'quotation_id');
    }

    public function provider()
    {
        return $this->belongsTo(Provider::class, 'provider_id');
    }

    public function projects()
    {
        return $this->hasMany(Project::class, 'quotation_id');
    }

    public function convertedProject()
    {
        return $this->belongsTo(Project::class, 'converted_project_id');
    }

    public function convertedPurchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class, 'converted_purchase_order_id');
    }
}
