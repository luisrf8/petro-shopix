<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppointmentServiceItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'appointment_id',
        'appointment_service_id',
        'sequence',
        'duration_minutes',
        'buffer_minutes',
        'price',
        'starts_at',
        'ends_at',
    ];

    protected $casts = [
        'sequence' => 'integer',
        'duration_minutes' => 'integer',
        'buffer_minutes' => 'integer',
        'price' => 'float',
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
    ];

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function service()
    {
        return $this->belongsTo(AppointmentService::class, 'appointment_service_id');
    }
}
