<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class PaymentMethod extends Model
{
    use HasFactory;
    
    protected $fillable = ['name', 'currency_id', 'admin_name', 'dni', 'description', 'bank', 'status', 'tenant_id', 'qr_image', 'has_reference', 'applies_igtf_base'];

    protected $casts = [
        'status' => 'boolean',
        'has_reference' => 'boolean',
        'applies_igtf_base' => 'boolean',
    ];

    public function scopeActive($query)
    {
        return $query->where('status', 1);
    }
    
    public function currency()
        {
            return $this->belongsTo(Currency::class);
        }

    public function usesReference(): bool
    {
        if (array_key_exists('has_reference', $this->attributes) && $this->attributes['has_reference'] !== null) {
            return (bool) $this->attributes['has_reference'];
        }

        return !in_array(Str::lower(trim((string) $this->name)), ['efectivo', 'punto de venta', 'pago movil'], true);
    }
}
