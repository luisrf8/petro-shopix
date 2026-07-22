<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

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

    public function assignedUsers()
    {
        return $this->belongsToMany(User::class, 'appointment_service_user', 'appointment_service_id', 'user_id')
            ->withTimestamps();
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
        $categoryName = trim((string) ($this->productVariant?->product?->category?->name ?? ''));

        if ($this->name) {
            $baseName = trim((string) $this->name);

            if ($categoryName !== '' && !Str::contains(Str::lower($baseName), Str::lower($categoryName))) {
                return $baseName . ' - ' . $categoryName;
            }

            return $baseName;
        }

        if ($this->productVariant && $this->productVariant->product) {
            $baseName = trim(($this->productVariant->product->name ?? 'Servicio') . ' ' . ($this->productVariant->size ?? ''));

            if ($categoryName !== '' && !Str::contains(Str::lower($baseName), Str::lower($categoryName))) {
                return $baseName . ' - ' . $categoryName;
            }

            return $baseName;
        }

        return 'Servicio';
    }
}