<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Tax extends Model
{
    protected $fillable = [
        'code',
        'name',
        'rate',
        'description',
        'is_bill',
        'is_active'
    ];
    public function products()
    {
        return $this->belongsToMany(Product::class, 'product_tax');
    }
}
