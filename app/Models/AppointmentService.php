<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppointmentService extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'product_variant_id',
        'name',
        'description',
        'duration_minutes',
        'buffer_minutes',
        'price',
        'color_hex',
        'is_active',
    ];

    protected $casts = [
        'duration_minutes' => 'integer',
        'buffer_minutes' => 'integer',
        'price' => 'float',
        'is_active' => 'boolean',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'appointment_service_id');
    }

    public function getDisplayNameAttribute(): string
    {
        if ($this->name) {
            return (string) $this->name;
        }

        if ($this->productVariant && $this->productVariant->product) {
            return trim(($this->productVariant->product->name ?? 'Servicio') . ' ' . ($this->productVariant->size ?? ''));
        }

        return 'Servicio';
    }
}