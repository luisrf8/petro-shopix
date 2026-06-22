<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Provider extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'rif',
        'fiscal_person_type',
        'fiscal_residency_type',
        'is_special_taxpayer',
        'contact_name',
        'email',
        'phone_number',
        'payment_currency_code',
        'notes',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'is_special_taxpayer' => 'boolean',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}