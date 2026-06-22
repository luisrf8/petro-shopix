<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ElectronicDocument extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'sales_order_id',
        'created_by',
        'provider',
        'tipo_documento',
        'serie',
        'numero_documento',
        'internal_number',
        'numero_control',
        'transaccion_id',
        'estado_documento',
        'codigo',
        'mensaje',
        'url_consulta',
        'cufe',
        'qr_string',
        'request_payload',
        'response_payload',
        'issued_at',
        'emailed_at',
        'is_annulled',
        'annulled_at',
    ];

    protected $casts = [
        'request_payload' => 'array',
        'response_payload' => 'array',
        'issued_at' => 'datetime',
        'emailed_at' => 'datetime',
        'annulled_at' => 'datetime',
        'is_annulled' => 'boolean',
    ];

    public function salesOrder()
    {
        return $this->belongsTo(SalesOrder::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function adjustmentNotes()
    {
        return $this->hasMany(SalesAdjustmentNote::class);
    }

    public function retentions()
    {
        return $this->hasMany(SalesRetention::class);
    }
}
