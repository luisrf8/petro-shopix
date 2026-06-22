<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseVatRetention extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'purchase_order_id',
        'account_payable_id',
        'provider_id',
        'created_by',
        'retention_date',
        'legal_deadline_at',
        'issued_within_deadline',
        'certificate_number',
        'invoice_number',
        'control_number',
        'retention_rate',
        'taxable_base',
        'tax_amount',
        'retained_amount',
        'currency_code',
        'status',
        'notes',
        'request_payload',
        'response_payload',
    ];

    protected $casts = [
        'retention_date' => 'date',
        'legal_deadline_at' => 'date',
        'issued_within_deadline' => 'boolean',
        'retention_rate' => 'float',
        'taxable_base' => 'float',
        'tax_amount' => 'float',
        'retained_amount' => 'float',
        'request_payload' => 'array',
        'response_payload' => 'array',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function accountPayable()
    {
        return $this->belongsTo(AccountPayable::class);
    }

    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }
}
