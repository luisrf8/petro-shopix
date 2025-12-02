<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    protected $table = 'audit_log';

    protected $fillable = [
        'table_name', 'action', 'user_id', 'description', 'created_at'
    ];


}
	

