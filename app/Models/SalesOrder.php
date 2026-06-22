<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesOrder extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'sales_rep_user_id', 'date', 'address', 'status', 'preference', 'deliver_status', 'tenant_id', 'document_issue_mode', 'sale_currency_code', 'delivery_fee', 'delivery_fee_mode', 'delivery_distance_km', 'delivery_latitude', 'delivery_longitude', 'delivery_assigned_user_id', 'subtotal_before_discount', 'total_discount', 'total_paid_base', 'igtf_base_amount', 'igtf_amount', 'change_due_base', 'change_paid_in_bs', 'change_rate_to_bs', 'change_due_bs'];

    protected $casts = [
        'delivery_fee' => 'float',
        'delivery_distance_km' => 'float',
        'delivery_latitude' => 'float',
        'delivery_longitude' => 'float',
        'subtotal_before_discount' => 'float',
        'total_discount' => 'float',
        'total_paid_base' => 'float',
        'igtf_base_amount' => 'float',
        'igtf_amount' => 'float',
        'change_due_base' => 'float',
        'change_paid_in_bs' => 'boolean',
        'change_rate_to_bs' => 'float',
        'change_due_bs' => 'float',
    ];

    public function details()
    {
        return $this->hasMany(SalesOrderDetail::class, 'sales_order_id');
    }
    public function user()
    {
        return $this->belongsTo(User::class);
    }
    public function payments()
    {
        return $this->hasMany(Payment::class, 'sales_order_id'); // Asegúrate de que la clave foránea sea la correcta
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function assignedDeliveryUser()
    {
        return $this->belongsTo(User::class, 'delivery_assigned_user_id');
    }

    public function salesRepresentative()
    {
        return $this->belongsTo(User::class, 'sales_rep_user_id');
    }

    public function sellerCommission()
    {
        return $this->hasOne(SellerCommission::class, 'sales_order_id');
    }

    public function returns()
    {
        return $this->hasMany(SalesReturn::class);
    }

    public function electronicDocuments()
    {
        return $this->hasMany(ElectronicDocument::class);
    }

    public function adjustmentNotes()
    {
        return $this->hasMany(SalesAdjustmentNote::class)->orderByDesc('note_date')->orderByDesc('id');
    }

    public function retentions()
    {
        return $this->hasMany(SalesRetention::class)->orderByDesc('retention_date')->orderByDesc('id');
    }

    public function getItemsSubtotalAttribute(): float
    {
        $details = $this->relationLoaded('details') ? $this->details : $this->details()->get();

        return round((float) $details->sum('amount'), 2);
    }

    public function getDeliveryFeeAmountAttribute(): float
    {
        return round((float) ($this->delivery_fee ?? 0), 2);
    }

    public function getGrossTotalAttribute(): float
    {
        return round($this->items_subtotal + $this->delivery_fee_amount, 2);
    }

    public function totalAfterReturns(float $returnsTotal = 0): float
    {
        return round(max(0, $this->gross_total - max(0, $returnsTotal)), 2);
    }
}
