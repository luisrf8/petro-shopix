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
        'payment_reason',
        'deduction_reason',
        'total_to_pay',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'float',
        'total_to_pay' => 'float',
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

    public function items()
    {
        return $this->hasMany(ProjectPayrollItem::class, 'payroll_entry_id')->orderBy('sort_order');
    }
}
