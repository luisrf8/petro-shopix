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
        'spent_at',
        'payment_method',
        'provider_name',
        'status',
        'created_by',
    ];

    protected $casts = [
        'spent_at' => 'date',
        'amount' => 'decimal:2',
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