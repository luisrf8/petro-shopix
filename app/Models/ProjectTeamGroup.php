<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectTeamGroup extends Model
{
    use HasFactory;

    protected $table = 'pm_team_groups';

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'payment_type',
        'default_payment_frequency',
        'start_date',
        'end_date',
        'is_active',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'is_active' => 'boolean',
    ];

    public function members()
    {
        return $this->hasMany(ProjectTeamMember::class, 'team_group_id');
    }
}
