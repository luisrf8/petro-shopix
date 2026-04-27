<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppointmentPackage extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'description',
        'appointment_service_id',
        'sessions_count',
        'repeat_every_weeks',
        'preferred_day_of_week',
        'preferred_time',
        'price',
        'is_active',
    ];

    protected $casts = [
        'price' => 'float',
        'is_active' => 'boolean',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function service()
    {
        return $this->belongsTo(AppointmentService::class, 'appointment_service_id');
    }

    public function sessions()
    {
        return $this->hasMany(AppointmentPackageSession::class);
    }
}
