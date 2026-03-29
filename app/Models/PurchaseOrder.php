<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'provider_id',
        'provider_name',
        'warehouse_id',
        'date',
        'tenant_id',
        'entry_mode',
        'production_cost_total',
        'production_notes',
    ];

    public function detalles()
    {
        return $this->hasMany(PurchaseOrderDetail::class);
    }

    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function consumptions()
    {
        return $this->hasMany(PurchaseOrderConsumption::class);
    }

    public function getProviderDisplayNameAttribute(): string
    {
        return (string) ($this->provider->name ?? $this->provider_name ?? 'No asignado');
    }
}
