<?php

namespace App\Services;

use App\Models\AppointmentService;
use App\Models\Category;
use App\Models\City;
use App\Models\Country;
use App\Models\Currency;
use App\Models\PaymentMethod;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProductVariantWarehouseStock;
use App\Models\Role;
use App\Models\State;
use App\Models\Tenant;
use App\Models\User;
use App\Models\UserScheduleRule;
use App\Models\Warehouse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ShopixSetupImportService
{
    public function applyToTenant(Tenant $tenant, array $payload): array
    {
        return DB::transaction(function () use ($tenant, $payload) {
            $tenantUpdated = $this->applyTenantData($tenant, $payload['tenant'] ?? []);
            $usersSynced = $this->syncUsers($tenant, $payload['users'] ?? []);
            $paymentMethodsSynced = $this->syncPaymentMethods($tenant, $payload['payment_methods'] ?? []);
            $storeItemsSynced = $this->syncStoreCatalog($tenant, $payload['store_catalog'] ?? []);
            $serviceItemsSynced = $this->syncServiceCatalog($tenant, $payload['service_catalog'] ?? []);
            $scheduleRulesSynced = $this->syncScheduleRules($tenant, $payload['schedule_rules'] ?? []);

            return [
                'tenant_updated' => $tenantUpdated ? 1 : 0,
                'users_synced' => $usersSynced,
                'payment_methods_synced' => $paymentMethodsSynced,
                'store_items_synced' => $storeItemsSynced,
                'service_items_synced' => $serviceItemsSynced,
                'schedule_rules_synced' => $scheduleRulesSynced,
            ];
        });
    }

    private function applyTenantData(Tenant $tenant, array $data): bool
    {
        if (empty($data)) {
            return false;
        }

        $countryId = $this->resolveCountryId($data['country_name'] ?? null);
        $stateId = $this->resolveStateId($data['state_name'] ?? null, $countryId);
        $cityId = $this->resolveCityId($data['city_name'] ?? null, $stateId);

        $updates = array_filter([
            'name' => $this->cleanString($data['name'] ?? null),
            'slug' => $this->resolveTenantSlug($tenant, $data['slug'] ?? null, $data['name'] ?? null),
            'email' => $this->cleanString($data['email'] ?? null),
            'business_type' => $this->normalizeBusinessType($data['business_type'] ?? null),
            'economic_activity' => $this->cleanString($data['economic_activity'] ?? null),
            'slogan' => $this->cleanString($data['slogan'] ?? null),
            'description' => $this->cleanString($data['description'] ?? null),
            'phone_code' => $this->cleanString($data['phone_code'] ?? null),
            'phone_number' => $this->cleanString($data['phone_number'] ?? null),
            'country' => $countryId ?: $this->cleanString($data['country_name'] ?? null),
            'state' => $stateId ?: $this->cleanString($data['state_name'] ?? null),
            'city' => $cityId ?: $this->cleanString($data['city_name'] ?? null),
            'address' => $this->cleanString($data['address'] ?? null),
            'working_days' => $this->normalizeWorkingDays($data['working_days'] ?? null),
            'opening_time' => $this->normalizeTime($data['opening_time'] ?? null),
            'closing_time' => $this->normalizeTime($data['closing_time'] ?? null),
            'appointments_first_come_enabled' => $this->normalizeBooleanOrNull($data['appointments_first_come_enabled'] ?? null),
            'special_taxpayer' => $this->normalizeBooleanOrNull($data['special_taxpayer'] ?? null),
            'delivery_enabled' => $this->normalizeBooleanOrNull($data['delivery_enabled'] ?? null),
            'delivery_fee_mode' => $this->normalizeDeliveryFeeMode($data['delivery_fee_mode'] ?? null),
            'delivery_fixed_fee' => $this->normalizeDecimal($data['delivery_fixed_fee'] ?? null),
            'delivery_fee_per_km' => $this->normalizeDecimal($data['delivery_fee_per_km'] ?? null),
            'restrict_delivery_city_to_tenant' => $this->normalizeBooleanOrNull($data['restrict_delivery_city_to_tenant'] ?? null),
            'delivery_notifications_enabled' => $this->normalizeBooleanOrNull($data['delivery_notifications_enabled'] ?? null),
            'tiktok' => $this->cleanString($data['tiktok'] ?? null),
            'instagram' => $this->cleanString($data['instagram'] ?? null),
            'facebook' => $this->cleanString($data['facebook'] ?? null),
            'color_primary' => $this->normalizeColor($data['color_primary'] ?? null),
            'color_secondary' => $this->normalizeColor($data['color_secondary'] ?? null),
            'color_accent' => $this->normalizeColor($data['color_accent'] ?? null),
        ], fn ($value) => !is_null($value));

        if (empty($updates)) {
            return false;
        }

        $tenant->fill($updates);
        if (!$tenant->isDirty()) {
            return false;
        }

        $tenant->save();

        return true;
    }

    private function syncUsers(Tenant $tenant, array $users): int
    {
        if (empty($users)) {
            return 0;
        }

        $rolesByCanonicalName = Role::query()
            ->get()
            ->mapWithKeys(fn (Role $role) => [User::canonicalRoleName((string) $role->name) => $role]);

        $synced = 0;
        foreach ($users as $row) {
            $email = $this->cleanString($row['email'] ?? null);
            $name = $this->cleanString($row['name'] ?? null);

            if (!$email && !$name) {
                continue;
            }

            $roleKey = User::canonicalRoleName($this->cleanString($row['role'] ?? null) ?: 'seller');
            $role = $rolesByCanonicalName->get($roleKey);

            $user = null;
            if ($email) {
                $user = User::query()
                    ->where('tenant_id', $tenant->id)
                    ->whereRaw('LOWER(email) = ?', [mb_strtolower($email)])
                    ->first();
            }

            if (!$user && $name) {
                $user = User::query()
                    ->where('tenant_id', $tenant->id)
                    ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
                    ->when($role, fn ($query) => $query->where('role_id', $role->id))
                    ->first();
            }

            if (!$user) {
                $user = new User();
                $user->tenant_id = $tenant->id;
                $user->is_active = 1;
            }

            if ($name) {
                $user->name = $name;
            }
            if ($email) {
                $user->email = $email;
            }
            if ($role) {
                $user->role_id = $role->id;
            }

            $user->phone_number = $this->cleanString($row['phone_number'] ?? null) ?? $user->phone_number;
            $user->dni = $this->cleanString($row['dni'] ?? null) ?? $user->dni;

            $password = $this->cleanString($row['password'] ?? null);
            if ($password) {
                $user->password = Hash::make($password);
            } elseif (!$user->exists) {
                $user->password = Hash::make('password123');
            }

            $user->save();
            $synced++;

            if ($roleKey === 'owner' && Schema::hasColumn('tenants', 'owner_id') && (int) ($tenant->owner_id ?? 0) !== (int) $user->id) {
                $tenant->owner_id = $user->id;
                $tenant->save();
            }
        }

        return $synced;
    }

    private function syncPaymentMethods(Tenant $tenant, array $methods): int
    {
        if (empty($methods)) {
            return 0;
        }

        $currencyId = $this->resolveCurrencyId($tenant);
        $synced = 0;

        foreach ($methods as $row) {
            $name = $this->cleanString($row['name'] ?? null);
            if (!$name) {
                continue;
            }

            $bank = $this->cleanString($row['bank'] ?? null);
            $method = PaymentMethod::query()
                ->where('tenant_id', $tenant->id)
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
                ->when($bank, fn ($query) => $query->whereRaw("LOWER(COALESCE(bank, '')) = ?", [mb_strtolower($bank)]))
                ->first();

            if (!$method) {
                $method = new PaymentMethod();
                $method->tenant_id = $tenant->id;
            }

            $method->name = $name;
            $method->currency_id = $currencyId;
            $method->admin_name = $this->cleanString($row['admin_name'] ?? null);
            $method->dni = $this->cleanString($row['dni'] ?? null);
            $method->description = $this->cleanString($row['description'] ?? null);
            $method->bank = $bank;
            $method->has_reference = $this->normalizeBooleanOrNull($row['has_reference'] ?? null) ?? true;
            $method->status = true;
            $method->save();
            $synced++;
        }

        return $synced;
    }

    private function syncStoreCatalog(Tenant $tenant, array $rows): int
    {
        if (empty($rows)) {
            return 0;
        }

        $synced = 0;
        foreach ($rows as $row) {
            $productName = $this->cleanString($row['product_name'] ?? null);
            if (!$productName) {
                continue;
            }

            $category = $this->firstOrCreateCategory($tenant, $row['category'] ?? 'General');
            $product = $this->firstOrCreateProduct($tenant, $category, $productName, $row['description'] ?? null, $row['is_consumable'] ?? null, $row['is_active'] ?? null);
            $this->firstOrCreateVariant($tenant, $product, $row['variant_name'] ?? 'Unica', $row['price'] ?? null, $row['stock'] ?? null);
            $synced++;
        }

        return $synced;
    }

    private function syncServiceCatalog(Tenant $tenant, array $rows): int
    {
        if (empty($rows)) {
            return 0;
        }

        $synced = 0;
        foreach ($rows as $row) {
            $serviceName = $this->cleanString($row['name'] ?? null);
            if (!$serviceName) {
                continue;
            }

            $category = $this->firstOrCreateCategory($tenant, $row['category'] ?? 'Servicios');
            $product = $this->firstOrCreateProduct($tenant, $category, $serviceName, $row['description'] ?? null, false, $row['is_active'] ?? null);
            $variant = $this->firstOrCreateVariant($tenant, $product, 'Servicio', $row['price'] ?? null, 0);
            $professionalId = $this->resolveProfessionalUserId($tenant, $row['professional'] ?? null);
            $service = AppointmentService::query()
                ->where('tenant_id', $tenant->id)
                ->whereRaw('LOWER(name) = ?', [mb_strtolower($serviceName)])
                ->first();

            if (!$service) {
                $service = new AppointmentService();
                $service->tenant_id = $tenant->id;
            }

            $service->user_id = $professionalId;
            $service->product_variant_id = $variant->id;
            $service->name = $serviceName;
            $service->description = $this->cleanString($row['description'] ?? null);
            $service->duration_minutes = max(15, (int) ($row['duration_minutes'] ?? 60));
            $service->buffer_minutes = max(0, (int) ($row['buffer_minutes'] ?? 0));
            $service->price = (float) ($row['price'] ?? $variant->price ?? 0);
            $service->color_hex = $this->normalizeColor($row['color_hex'] ?? null) ?? '#0f172a';
            $service->is_active = $this->normalizeBooleanOrNull($row['is_active'] ?? null) ?? true;
            $service->save();
            $synced++;
        }

        return $synced;
    }

    private function syncScheduleRules(Tenant $tenant, array $rows): int
    {
        if (empty($rows)) {
            return 0;
        }

        $synced = 0;
        foreach ($rows as $row) {
            $professionalId = $this->resolveProfessionalUserId($tenant, $row['professional'] ?? null);
            $dayOfWeek = $this->resolveDayOfWeek($row['day'] ?? null);
            $startTime = $this->normalizeTime($row['start_time'] ?? null);
            $endTime = $this->normalizeTime($row['end_time'] ?? null);

            if (!$professionalId || is_null($dayOfWeek) || !$startTime || !$endTime) {
                continue;
            }

            $rule = UserScheduleRule::query()->firstOrNew([
                'tenant_id' => $tenant->id,
                'user_id' => $professionalId,
                'day_of_week' => $dayOfWeek,
                'start_time' => $startTime,
                'end_time' => $endTime,
            ]);

            $rule->slot_interval_minutes = max(15, (int) ($row['slot_interval_minutes'] ?? 30));
            $rule->is_active = $this->normalizeBooleanOrNull($row['is_active'] ?? null) ?? true;
            $rule->save();
            $synced++;
        }

        return $synced;
    }

    private function firstOrCreateCategory(Tenant $tenant, mixed $name): Category
    {
        $categoryName = $this->cleanString($name) ?: 'General';

        $category = Category::query()
            ->where('tenant_id', $tenant->id)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($categoryName)])
            ->first();

        if ($category) {
            return $category;
        }

        return Category::create([
            'tenant_id' => $tenant->id,
            'name' => $categoryName,
            'description' => null,
            'is_active' => true,
        ]);
    }

    private function firstOrCreateProduct(Tenant $tenant, Category $category, string $name, mixed $description, mixed $isConsumable, mixed $isActive): Product
    {
        $product = Product::query()
            ->where('tenant_id', $tenant->id)
            ->where('category_id', $category->id)
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->first();

        if (!$product) {
            $product = new Product();
            $product->tenant_id = $tenant->id;
            $product->category_id = $category->id;
            $product->slug = $this->generateUniqueProductSlug($tenant, $name);
        }

        $product->name = $name;
        $product->description = $this->cleanString($description) ?? $product->description;
        $product->is_consumable = $this->normalizeBooleanOrNull($isConsumable) ?? (bool) ($product->is_consumable ?? false);
        $product->is_active = $this->normalizeBooleanOrNull($isActive) ?? true;
        $product->save();

        return $product;
    }

    private function firstOrCreateVariant(Tenant $tenant, Product $product, mixed $size, mixed $price, mixed $stock): ProductVariant
    {
        $variantName = $this->cleanString($size) ?: 'Unica';

        $variant = ProductVariant::query()
            ->where('product_id', $product->id)
            ->whereRaw('LOWER(size) = ?', [mb_strtolower($variantName)])
            ->first();

        if (!$variant) {
            $variant = new ProductVariant();
            $variant->product_id = $product->id;
            $variant->size = $variantName;
        }

        $variant->price = (float) ($price ?? $variant->price ?? 0);
        $variant->stock = (int) ($stock ?? $variant->stock ?? 0);
        $variant->save();

        $this->syncDefaultWarehouseStock($tenant, $variant, (int) ($stock ?? 0));

        return $variant;
    }

    private function syncDefaultWarehouseStock(Tenant $tenant, ProductVariant $variant, int $quantity): void
    {
        $warehouse = Warehouse::query()
            ->where('tenant_id', $tenant->id)
            ->orderByDesc('is_default')
            ->orderBy('id')
            ->first();

        if (!$warehouse) {
            return;
        }

        ProductVariantWarehouseStock::query()->updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'warehouse_id' => $warehouse->id,
                'product_variant_id' => $variant->id,
            ],
            [
                'quantity' => $quantity,
            ]
        );
    }

    private function resolveProfessionalUserId(Tenant $tenant, mixed $value): ?int
    {
        $needle = $this->cleanString($value);
        if (!$needle) {
            return null;
        }

        $query = User::query()->where('tenant_id', $tenant->id);

        if (filter_var($needle, FILTER_VALIDATE_EMAIL)) {
            return (int) ($query->whereRaw('LOWER(email) = ?', [mb_strtolower($needle)])->value('id') ?? 0) ?: null;
        }

        return (int) ($query->whereRaw('LOWER(name) = ?', [mb_strtolower($needle)])->value('id') ?? 0) ?: null;
    }

    private function resolveDayOfWeek(mixed $value): ?int
    {
        $normalized = Str::lower(trim((string) $value));
        if ($normalized === '') {
            return null;
        }

        $map = [
            'domingo' => 0,
            'domingo.' => 0,
            'lunes' => 1,
            'martes' => 2,
            'miercoles' => 3,
            'miércoles' => 3,
            'jueves' => 4,
            'viernes' => 5,
            'sabado' => 6,
            'sábado' => 6,
            'sunday' => 0,
            'monday' => 1,
            'tuesday' => 2,
            'wednesday' => 3,
            'thursday' => 4,
            'friday' => 5,
            'saturday' => 6,
        ];

        return $map[$normalized] ?? null;
    }

    private function resolveCountryId(?string $name): ?int
    {
        $clean = $this->cleanString($name);
        if (!$clean) {
            return null;
        }

        return Country::query()->whereRaw('LOWER(name) = ?', [mb_strtolower($clean)])->value('id');
    }

    private function resolveStateId(?string $name, ?int $countryId): ?int
    {
        $clean = $this->cleanString($name);
        if (!$clean) {
            return null;
        }

        return State::query()
            ->when($countryId, fn ($query) => $query->where('country_id', $countryId))
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($clean)])
            ->value('id');
    }

    private function resolveCityId(?string $name, ?int $stateId): ?int
    {
        $clean = $this->cleanString($name);
        if (!$clean) {
            return null;
        }

        return City::query()
            ->when($stateId, fn ($query) => $query->where('state_id', $stateId))
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($clean)])
            ->value('id');
    }

    private function resolveCurrencyId(Tenant $tenant): int
    {
        $code = strtoupper(trim((string) ($tenant->base_currency ?? 'USD')));
        $name = match ($code) {
            'EUR' => 'Euro',
            default => 'Dólar',
        };

        return (int) Currency::query()->firstOrCreate(['code' => $code], ['name' => $name])->id;
    }

    private function generateUniqueProductSlug(Tenant $tenant, string $name): string
    {
        $base = Str::slug($name);
        $base = $base !== '' ? $base : 'item';
        $candidate = $base;
        $suffix = 2;

        while (Product::query()->where('tenant_id', $tenant->id)->where('slug', $candidate)->exists()) {
            $candidate = $base . '-' . $suffix;
            $suffix++;
        }

        return $candidate;
    }

    private function resolveTenantSlug(Tenant $tenant, mixed $requestedSlug, mixed $fallbackName): ?string
    {
        $baseSource = $this->cleanString($requestedSlug) ?: $this->cleanString($fallbackName);
        if (!$baseSource) {
            return null;
        }

        $candidate = Str::slug($baseSource);
        if ($candidate === '') {
            return null;
        }

        $original = $candidate;
        $suffix = 2;
        while (Tenant::query()->where('id', '!=', $tenant->id)->where('slug', $candidate)->exists()) {
            $candidate = $original . '-' . $suffix;
            $suffix++;
        }

        return $candidate;
    }

    private function normalizeBusinessType(mixed $value): ?string
    {
        $normalized = Str::lower(trim((string) $value));
        if ($normalized === '') {
            return null;
        }

        return $normalized === 'servicio' ? 'Servicio' : 'Tienda';
    }

    private function normalizeWorkingDays(mixed $value): ?array
    {
        if (!is_array($value)) {
            return null;
        }

        $allowed = ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'];
        $days = collect($value)
            ->map(fn ($day) => Str::lower(trim((string) $day)))
            ->filter(fn ($day) => in_array($day, $allowed, true))
            ->unique()
            ->values()
            ->all();

        return empty($days) ? null : $days;
    }

    private function normalizeTime(mixed $value): ?string
    {
        $clean = trim((string) $value);
        if ($clean === '') {
            return null;
        }

        if (preg_match('/^\d{2}:\d{2}$/', $clean) === 1) {
            return $clean;
        }

        if (preg_match('/^\d{2}:\d{2}:\d{2}$/', $clean) === 1) {
            return substr($clean, 0, 5);
        }

        return null;
    }

    private function normalizeBooleanOrNull(mixed $value): ?bool
    {
        if (is_bool($value)) {
            return $value;
        }

        $normalized = Str::lower(trim((string) $value));
        if ($normalized === '') {
            return null;
        }

        if (in_array($normalized, ['si', 'sí', 'yes', '1', 'true', 'activo', 'activa'], true)) {
            return true;
        }

        if (in_array($normalized, ['no', '0', 'false', 'inactivo', 'inactiva'], true)) {
            return false;
        }

        return null;
    }

    private function normalizeDecimal(mixed $value): ?float
    {
        $clean = trim((string) $value);
        if ($clean === '') {
            return null;
        }

        $clean = str_replace(['$', 'Bs', 'USD', ' '], '', $clean);
        $clean = str_replace(',', '.', $clean);

        return is_numeric($clean) ? (float) $clean : null;
    }

    private function normalizeDeliveryFeeMode(mixed $value): ?string
    {
        $normalized = Str::lower(trim((string) $value));

        return in_array($normalized, ['free', 'fixed', 'distance'], true) ? $normalized : null;
    }

    private function normalizeColor(mixed $value): ?string
    {
        $clean = trim((string) $value);
        if ($clean === '') {
            return null;
        }

        return preg_match('/^#[0-9A-Fa-f]{6}$/', $clean) === 1 ? strtoupper($clean) : null;
    }

    private function cleanString(mixed $value): ?string
    {
        $clean = trim((string) $value);

        return $clean === '' ? null : $clean;
    }
}