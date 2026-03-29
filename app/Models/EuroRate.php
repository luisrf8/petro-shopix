<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EuroRate extends Model
{
    use HasFactory;

    protected $table = 'euro_rates';

    protected $fillable = ['date', 'rate', 'tenant_id'];

    public $timestamps = true;

    public function setRateAttribute($value): void
    {
        $this->attributes['rate'] = round((float) $value, 4);
    }
}
