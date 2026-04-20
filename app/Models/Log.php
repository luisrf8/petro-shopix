<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    protected $table = 'audit_log';

    protected $fillable = [
        'table_name',
        'action',
        'user_id',
        'tenant_id',
        'event_type',
        'record_id',
        'description',
        'old_values',
        'new_values',
        'ip_address',
        'occurred_at',
        'created_at',
        'updated_at',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
        'occurred_at' => 'datetime',
    ];


}
	

