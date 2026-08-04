<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectTeamMember extends Model
{
    use HasFactory;

    protected $table = 'pm_team_members';

    protected $fillable = [
        'tenant_id',
        'team_group_id',
        'user_id',
        'full_name',
        'email',
        'phone',
        'role',
        'payment_frequency',
        'is_active',
        'terminated_at',
        'termination_reason',
        'notes',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'terminated_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function group()
    {
        return $this->belongsTo(ProjectTeamGroup::class, 'team_group_id');
    }

    public function payrollEntries()
    {
        return $this->hasMany(ProjectPayroll::class, 'team_member_id');
    }

    public function assignments()
    {
        return $this->hasMany(ProjectAssignment::class, 'team_member_id');
    }
}
