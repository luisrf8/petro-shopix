<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppointmentConsumption extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'appointment_id',
        'product_variant_id',
        'quantity',
        'unit_cost',
        'amount',
    ];

    protected $casts = [
        'quantity' => 'float',
        'unit_cost' => 'float',
        'amount' => 'float',
    ];

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }
}