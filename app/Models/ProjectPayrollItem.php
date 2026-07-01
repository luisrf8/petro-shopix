<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectPayrollItem extends Model
{
    use HasFactory;

    protected $table = 'pm_payroll_entry_items';

    protected $fillable = [
        'tenant_id',
        'payroll_entry_id',
        'item_type',
        'amount',
        'description',
        'sort_order',
    ];

    protected $casts = [
        'amount' => 'float',
        'sort_order' => 'integer',
    ];

    public function payrollEntry()
    {
        return $this->belongsTo(ProjectPayroll::class, 'payroll_entry_id');
    }
}
