<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FiscalCorrelative extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'document_key',
        'prefix',
        'current_number',
    ];
}