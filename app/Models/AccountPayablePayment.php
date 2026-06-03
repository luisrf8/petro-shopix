<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AccountPayablePayment extends Model
{
    use HasFactory;

    protected $table = 'accounts_payable_payments';

    protected $fillable = [
        'account_payable_id',
        'tenant_id',
        'paid_at',
        'amount',
        'currency_code',
        'payment_method',
        'reference',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'paid_at' => 'date',
        'amount' => 'decimal:4',
    ];

    public function accountPayable()
    {
        return $this->belongsTo(AccountPayable::class, 'account_payable_id');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
