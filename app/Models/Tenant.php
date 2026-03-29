<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class Tenant extends Model
{
    use HasFactory;

    private static array $locationNameCache = [];

    protected static function booted()
    {
        static::created(function (Tenant $tenant) {
            if (Schema::hasTable('warehouses') && class_exists(Warehouse::class)) {
                Warehouse::firstOrCreate(
                    ['tenant_id' => $tenant->id, 'name' => 'Almacén Principal'],
                    ['is_default' => true, 'is_active' => true]
                );
            }
        });
    }

    protected $fillable = [
        'name',
        'slug',
        'email',
        'logo',
        'color_primary',
        'color_secondary',
        'color_accent',
        'country',
        'state',
        'city',
        'phone_code',
        'phone_number',
        'base_currency',
        'slogan',
        'description',
        'business_type',
        'economic_activity',
        'address',
        'latitude',
        'longitude',
        'background_image',
        'tiktok',
        'instagram',
        'facebook',
        'electronic_invoicing_enabled',
    ];

    // Relaciones
    public function users()
    {
        return $this->hasMany(User::class);
    }

    public function categories()
    {
        return $this->hasMany(Category::class);
    }

    public function products()
    {
        return $this->hasMany(Product::class);
    }

    public function salesOrders()
    {
        return $this->hasMany(SalesOrder::class);
    }

    public function salesReturns()
    {
        return $this->hasMany(SalesReturn::class);
    }

    public function purchaseOrders()
    {
        return $this->hasMany(PurchaseOrder::class);
    }

    public function purchaseOrderDetails()
    {
        return $this->hasMany(PurchaseOrderDetail::class);
    }

    public function salesOrderDetails()
    {
        return $this->hasMany(SalesOrderDetail::class);
    }

    public function salesReturnItems()
    {
        return $this->hasMany(SalesReturnItem::class);
    }

    public function roles()
    {
        return $this->hasMany(Role::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function paymentMethods()
    {
        return $this->hasMany(PaymentMethod::class);
    }

    public function paymentImages()
    {
        return $this->hasMany(PaymentImage::class);
    }

    public function productVariants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function productImages()
    {
        return $this->hasMany(ProductImage::class);
    }

    public function currencies()
    {
        return $this->hasMany(Currency::class);
    }

    public function dollarRates()
    {
        return $this->hasMany(DollarRate::class);
    }

    public function euroRates()
    {
        return $this->hasMany(EuroRate::class);
    }

    public function orderStatuses()
    {
        return $this->hasMany(OrderStatus::class);
    }

    public function tenantPlanPayments()
    {
        return $this->hasMany(TenantPlanPayment::class);
    }

    public function warehouses()
    {
        return $this->hasMany(Warehouse::class);
    }

    // Si quieres solo el plan activo:
    public function activePlanPayment()
    {
        return $this->hasOne(TenantPlanPayment::class)->where('status', 'active');
    }

    public function getCountryNameAttribute(): ?string
    {
        return $this->resolveLocationName($this->country ?? null, Country::class);
    }

    public function getStateNameAttribute(): ?string
    {
        return $this->resolveLocationName($this->state ?? null, State::class);
    }

    public function getCityNameAttribute(): ?string
    {
        return $this->resolveLocationName($this->city ?? null, City::class);
    }

    private function resolveLocationName($value, string $modelClass): ?string
    {
        if (is_null($value) || trim((string) $value) === '') {
            return null;
        }

        $rawValue = trim((string) $value);

        if (!ctype_digit($rawValue)) {
            return $rawValue;
        }

        $cacheKey = $modelClass . ':' . $rawValue;
        if (array_key_exists($cacheKey, self::$locationNameCache)) {
            return self::$locationNameCache[$cacheKey];
        }

        $resolved = $modelClass::query()->whereKey((int) $rawValue)->value('name');
        self::$locationNameCache[$cacheKey] = $resolved ? (string) $resolved : $rawValue;

        return self::$locationNameCache[$cacheKey];
    }
}
