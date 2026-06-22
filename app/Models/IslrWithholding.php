<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IslrWithholding extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'account_payable_id',
        'account_payable_payment_id',
        'provider_id',
        'concept_id',
        'created_by',
        'retention_date',
        'payment_date',
        'certificate_number',
        'invoice_number',
        'control_number',
        'base_amount',
        'rate_percent',
        'sustraendo_ut',
        'sustraendo_amount',
        'retained_amount',
        'currency_code',
        'status',
        'notes',
        'request_payload',
        'response_payload',
    ];

    protected $casts = [
        'retention_date' => 'date',
        'payment_date' => 'date',
        'base_amount' => 'float',
        'rate_percent' => 'float',
        'sustraendo_ut' => 'float',
        'sustraendo_amount' => 'float',
        'retained_amount' => 'float',
        'request_payload' => 'array',
        'response_payload' => 'array',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function accountPayable()
    {
        return $this->belongsTo(AccountPayable::class);
    }

    public function accountPayablePayment()
    {
        return $this->belongsTo(AccountPayablePayment::class);
    }

    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }

    public function concept()
    {
        return $this->belongsTo(IslrWithholdingConcept::class, 'concept_id');
    }
}
