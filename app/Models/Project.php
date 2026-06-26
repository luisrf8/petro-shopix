<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    use HasFactory;

    protected $table = 'pm_projects';

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'phase',
        'starts_at',
        'development_at',
        'ends_at',
        'budget_amount',
        'currency_code',
        'quotation_id',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'starts_at' => 'date',
        'development_at' => 'date',
        'ends_at' => 'date',
        'budget_amount' => 'float',
    ];

    public function quotation()
    {
        return $this->belongsTo(ProjectQuotation::class, 'quotation_id');
    }

    public function payrollEntries()
    {
        return $this->hasMany(ProjectPayroll::class, 'project_id');
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(ProjectTask::class, 'project_id');
    }

    public function assignments(): HasMany
    {
        return $this->hasMany(ProjectAssignment::class, 'project_id');
    }

    public function assets(): HasMany
    {
        return $this->hasMany(ProjectAsset::class, 'project_id');
    }
}
