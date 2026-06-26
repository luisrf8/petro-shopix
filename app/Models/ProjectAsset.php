<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProjectAsset extends Model
{
    use HasFactory;

    protected $table = 'pm_project_assets';

    protected $fillable = [
        'tenant_id',
        'project_id',
        'task_id',
        'asset_type',
        'title',
        'notes',
        'file_path',
        'amount',
        'currency_code',
        'happened_at',
        'created_by',
    ];

    protected $casts = [
        'amount' => 'float',
        'happened_at' => 'date',
    ];

    public function project()
    {
        return $this->belongsTo(Project::class, 'project_id');
    }

    public function task()
    {
        return $this->belongsTo(ProjectTask::class, 'task_id');
    }
}
