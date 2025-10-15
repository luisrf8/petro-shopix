<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    use HasFactory;
    
    protected $fillable = ['name', 'currency_id', 'admin_name', 'dni', 'bank', 'status', 'tenant_id'];
    
    public function currency()
        {
            return $this->belongsTo(Currency::class);
        }
}
