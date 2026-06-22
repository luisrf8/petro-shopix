<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IslrWithholdingConcept extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'code',
        'name',
        'rate_percent',
        'sustraendo_ut',
        'applicable_person_type',
        'applicable_residency_type',
        'is_active',
    ];

    protected $casts = [
        'rate_percent' => 'float',
        'sustraendo_ut' => 'float',
        'is_active' => 'boolean',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
