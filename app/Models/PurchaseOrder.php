<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrder extends Model
{
    use HasFactory;

    protected $fillable = ['provider_id', 'warehouse_id', 'date', 'tenant_id'];

    public function detalles()
    {
        return $this->hasMany(PurchaseOrderDetail::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }
}
