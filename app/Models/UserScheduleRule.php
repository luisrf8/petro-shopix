<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UserScheduleRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'user_id',
        'day_of_week',
        'start_time',
        'end_time',
        'slot_interval_minutes',
        'is_active',
    ];

    protected $casts = [
        'day_of_week' => 'integer',
        'slot_interval_minutes' => 'integer',
        'is_active' => 'boolean',
    ];

    public const WEEK_DAYS = [
        0 => 'Domingo',
        1 => 'Lunes',
        2 => 'Martes',
        3 => 'Miércoles',
        4 => 'Jueves',
        5 => 'Viernes',
        6 => 'Sábado',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function getDayLabelAttribute(): string
    {
        return self::WEEK_DAYS[(int) $this->day_of_week] ?? 'Día';
    }
}