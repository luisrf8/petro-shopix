<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectTask extends Model
{
    use HasFactory;

    protected $table = 'pm_project_tasks';

    protected $fillable = [
        'tenant_id',
        'project_id',
        'responsible_team_member_id',
        'title',
        'description',
        'status',
        'due_date',
        'completed_at',
        'created_by',
    ];

    protected $casts = [
        'due_date' => 'date',
        'completed_at' => 'datetime',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function responsibleMember()
    {
        return $this->belongsTo(ProjectTeamMember::class, 'responsible_team_member_id');
    }
}
