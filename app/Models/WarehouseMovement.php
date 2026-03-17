<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WarehouseMovement extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'product_variant_id',
        'movement_type',
        'source_warehouse_id',
        'destination_warehouse_id',
        'quantity',
        'notes',
        'moved_at',
        'user_id',
    ];

    protected $casts = [
        'quantity' => 'float',
        'moved_at' => 'datetime',
    ];

    public const TYPE_ENTRY = 'entry';
    public const TYPE_EXIT = 'exit';
    public const TYPE_TRANSFER = 'transfer';

    public static function typeOptions(): array
    {
        return [
            self::TYPE_ENTRY => 'Entrada',
            self::TYPE_EXIT => 'Salida',
            self::TYPE_TRANSFER => 'Transferencia',
        ];
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function variant()
    {
        return $this->belongsTo(ProductVariant::class, 'product_variant_id');
    }

    public function sourceWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'source_warehouse_id');
    }

    public function destinationWarehouse()
    {
        return $this->belongsTo(Warehouse::class, 'destination_warehouse_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}