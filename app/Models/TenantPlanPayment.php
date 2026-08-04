<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\User;

class TenantPlanPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'plan_id',
        'amount',
        'status',
        'paid_at',
        'expires_at',
        'payment_reference',
        'payment_proof',
        'review_notes',
        'reviewed_at',
        'reviewed_by',
    ];

    protected $casts = [
        'paid_at' => 'datetime',
        'expires_at' => 'datetime',
        'reviewed_at' => 'datetime',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }
}
