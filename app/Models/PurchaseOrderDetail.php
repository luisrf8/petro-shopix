<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PurchaseOrderDetail extends Model
{
    use HasFactory;

    // Definir la tabla si no sigue el nombre plural por defecto
    protected $table = 'purchase_order_detail';

    // Definir la clave primaria si no es 'id'
    // protected $primaryKey = 'purchase_order_detail_id';

    // Si no usas las marcas de tiempo automáticamente, puedes desactivar
    // public $timestamps = false;

    // Campos asignables
    protected $fillable = [
        'purchase_order_id',
        'product_variant_id',
        'quantity',
        'amount',
        'price',
        'tenant_id'
    ];

    // Relaciones

    /**
     * Obtener la orden de compra asociada al detalle de la orden.
     */
    public function purchaseOrder()
    {
        return $this->belongsTo(PurchaseOrder::class);
    }

    /**
     * Obtener el producto variante asociado al detalle de la orden.
     */
    public function productVariant()
    {
        return $this->belongsTo(ProductVariant::class);
    }
}
