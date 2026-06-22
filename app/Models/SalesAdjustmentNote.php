<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesAdjustmentNote extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'sales_order_id',
        'electronic_document_id',
        'created_by',
        'note_type',
        'adjustment_mode',
        'status',
        'note_date',
        'internal_number',
        'document_code',
        'reference_document_number',
        'reference_control_number',
        'amount',
        'taxable_base',
        'tax_rate',
        'tax_amount',
        'affected_igtf_amount',
        'currency_code',
        'reason',
        'notes',
        'request_payload',
        'response_payload',
        'issued_at',
        'related_at',
    ];

    protected $casts = [
        'note_date' => 'date',
        'amount' => 'float',
        'taxable_base' => 'float',
        'tax_rate' => 'float',
        'tax_amount' => 'float',
        'affected_igtf_amount' => 'float',
        'request_payload' => 'array',
        'response_payload' => 'array',
        'issued_at' => 'datetime',
        'related_at' => 'datetime',
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