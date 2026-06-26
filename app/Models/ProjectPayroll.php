<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectPayroll extends Model
{
    use HasFactory;

    protected $table = 'pm_payroll_entries';

    protected $fillable = [
        'tenant_id',
        'team_member_id',
        'project_id',
        'payment_type',
        'amount',
        'currency_code',
        'paid_at',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'float',
        'paid_at' => 'date',
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
