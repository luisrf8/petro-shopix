<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'description', 'image', 'is_active', 'tenant_id'];

    public static function boot()
    {
        parent::boot();

        static::updated(function ($category) {
            if ($category->isDirty('is_active') && !$category->is_active) {
                // Inactivar todos los productos relacionados
                $category->products()->update(['is_active' => false]);
            }
        });
    }
    // Relación con productos
    public function products()
    {
        return $this->hasMany(Product::class);
    }
}
