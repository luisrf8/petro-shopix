<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppointmentImage extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'appointment_id',
        'uploaded_by_user_id',
        'image_path',
        'caption',
    ];

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function uploadedBy()
    {
        return $this->belongsTo(User::class, 'uploaded_by_user_id');
    }
}
