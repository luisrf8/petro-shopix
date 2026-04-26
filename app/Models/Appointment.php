<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'appointment_service_id',
        'user_id',
        'customer_id',
        'contact_name',
        'contact_phone',
        'starts_at',
        'ends_at',
        'status',
        'payment_method_id',
        'paid_amount',
        'payment_currency',
        'payment_reference',
        'payment_status',
        'source',
        'notes',
    ];

    protected $casts = [
        'starts_at' => 'datetime',
        'ends_at' => 'datetime',
        'paid_amount' => 'float',
    ];

    public const STATUSES = [
        'scheduled' => 'Programada',
        'confirmed' => 'Confirmada',
        'completed' => 'Completada',
        'cancelled' => 'Cancelada',
        'no_show' => 'No asistió',
    ];

    public const PAYMENT_STATUSES = [
        'pending' => 'Pendiente',
        'partial' => 'Abono parcial',
        'paid' => 'Pagada',
        'waived' => 'Sin cobro',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function service()
    {
        return $this->belongsTo(AppointmentService::class, 'appointment_service_id');
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function customer()
    {
        return $this->belongsTo(User::class, 'customer_id');
    }

    public function paymentMethod()
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method_id');
    }

    public function consumptions()
    {
        return $this->hasMany(AppointmentConsumption::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[(string) $this->status] ?? ucfirst((string) $this->status);
    }

    public function getPaymentStatusLabelAttribute(): string
    {
        return self::PAYMENT_STATUSES[(string) ($this->payment_status ?? 'pending')] ?? ucfirst((string) ($this->payment_status ?? 'pending'));
    }
}