<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountPayable extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'provider_id',
        'purchase_order_id',
        'document_number',
        'issued_at',
        'due_at',
        'amount_total',
        'amount_paid',
        'amount_pending',
        'currency_code',
        'status',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'issued_at' => 'date',
        'due_at' => 'date',
        'amount_total' => 'decimal:4',
        'amount_paid' => 'decimal:4',
        'amount_pending' => 'decimal:4',
    ];

    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }

    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    public function payments()
    {
        return $this->hasMany(AccountPayablePayment::class, 'account_payable_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
