<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StoreExpense extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'title',
        'category',
        'description',
        'amount',
        'currency_code',
        'amount_original',
        'exchange_rate_to_bs',
        'amount_bs',
        'spent_at',
        'payment_method',
        'provider_name',
        'status',
        'created_by',
    ];

    protected $casts = [
        'spent_at' => 'date',
        'amount' => 'decimal:2',
        'amount_original' => 'decimal:4',
        'exchange_rate_to_bs' => 'decimal:4',
        'amount_bs' => 'decimal:2',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}