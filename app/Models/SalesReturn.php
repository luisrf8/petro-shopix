<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesReturn extends Model
{
    use HasFactory;

    protected $fillable = [
        'sales_order_id',
        'reason',
        'subtotal_returned',
        'tax_returned',
        'igtf_returned',
        'total_returned',
    ];

    protected $casts = [
        'subtotal_returned' => 'float',
        'tax_returned' => 'float',
        'igtf_returned' => 'float',
        'total_returned' => 'float',
    ];

    public function items()
    {
        return $this->hasMany(SalesReturnItem::class);
    }
}
