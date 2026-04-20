<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesRetention extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'sales_order_id',
        'electronic_document_id',
        'created_by',
        'retention_type',
        'status',
        'retention_date',
        'internal_number',
        'certificate_number',
        'retention_rate',
        'taxable_base',
        'retained_amount',
        'currency_code',
        'notes',
        'request_payload',
        'response_payload',
        'applied_at',
    ];

    protected $casts = [
        'retention_date' => 'date',
        'retention_rate' => 'float',
        'taxable_base' => 'float',
        'retained_amount' => 'float',
        'request_payload' => 'array',
        'response_payload' => 'array',
        'applied_at' => 'datetime',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function electronicDocument()
    {
        return $this->belongsTo(ElectronicDocument::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}