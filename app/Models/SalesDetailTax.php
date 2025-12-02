<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalesDetailTax extends Model
{
    use HasFactory;

    protected $table = 'sales_detail_tax';   // 👈 IMPORTANTE

    protected $fillable = [
        'sales_order_detail_id',
        'tax_name',
        'tax_rate',
        'tax_amount'
    ];

    public function detail()
    {
        return $this->belongsTo(SalesOrderDetail::class, 'sales_order_detail_id');
    }

    public function tax()
    {
        return $this->belongsTo(Tax::class);
    }
}
