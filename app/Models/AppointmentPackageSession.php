<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppointmentPackageSession extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'appointment_package_id',
        'appointment_id',
        'session_number',
        'scheduled_for',
        'status',
    ];

    protected $casts = [
        'scheduled_for' => 'datetime',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function package()
    {
        return $this->belongsTo(AppointmentPackage::class, 'appointment_package_id');
    }

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }
}
