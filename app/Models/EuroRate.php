<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EuroRate extends Model
{
    use HasFactory;

    protected static function booted(): void
    {
        static::updating(function () {
            throw new \LogicException('Las tasas historicas del BCV no se pueden modificar.');
        });

        static::deleting(function () {
            throw new \LogicException('Las tasas historicas del BCV no se pueden eliminar.');
        });
    }

    protected $table = 'euro_rates';

    protected $fillable = ['date', 'rate', 'tenant_id'];

    protected $casts = [
        'date' => 'date',
        'rate' => 'float',
    ];

    public $timestamps = true;

    public function setRateAttribute($value): void
    {
        $this->attributes['rate'] = round((float) $value, 4);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }
}
