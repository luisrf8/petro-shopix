<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'name',
        'is_default',
        'is_active',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function stocks()
    {
        return $this->hasMany(ProductVariantWarehouseStock::class);
    }

    public function outgoingMovements()
    {
        return $this->hasMany(WarehouseMovement::class, 'source_warehouse_id');
    }

    public function incomingMovements()
    {
        return $this->hasMany(WarehouseMovement::class, 'destination_warehouse_id');
    }
}
