<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectAssignment extends Model
{
    use HasFactory;

    protected $table = 'pm_project_assignments';

    protected $fillable = [
        'tenant_id',
        'project_id',
        'team_member_id',
        'commission_type',
        'commission_value',
        'pay_amount',
        'pay_currency_code',
        'project_share_percent',
        'member_status',
        'is_active',
        'notes',
    ];

    protected $casts = [
        'commission_value' => 'float',
        'pay_amount' => 'float',
        'project_share_percent' => 'float',
        'is_active' => 'boolean',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function teamMember()
    {
        return $this->belongsTo(ProjectTeamMember::class, 'team_member_id');
    }
}
