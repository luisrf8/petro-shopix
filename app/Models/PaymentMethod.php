<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PaymentMethod extends Model
{
    use HasFactory;
    
    protected $fillable = ['name', 'currency_id', 'admin_name', 'dni', 'description', 'bank', 'status', 'tenant_id', 'qr_image'];

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
    
    public function currency()
        {
            return $this->belongsTo(Currency::class);
        }
}
