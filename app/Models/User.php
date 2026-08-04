<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use NotificationChannels\WebPush\HasPushSubscriptions;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, HasPushSubscriptions, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'email_verified_at',
        'password',
        'tenant_id',
        'country_id',
        'state_id',
        'city_id',
        'address',
        'latitude',
        'longitude',
        'role_id',
        'phone_number',
        'dni',
        'is_active',
        'is_retention_agent',
        'commission_percentage',
        'google_id',
        'facebook_id',
        'apple_id',
        'avatar',
        'help_disable_global',
        'help_disabled_routes',
    ];


    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'latitude' => 'float',
        'longitude' => 'float',
        'commission_percentage' => 'float',
        'help_disable_global' => 'boolean',
        'help_disabled_routes' => 'array',
    ];

    /**
     * Relación con el modelo Role.
     */
    public function role()
    {
        return $this->belongsTo(Role::class);
    }

    public function tenant()
    {
        return $this->belongsTo(Tenant::class);
    }

    public function salesOrders()
    {
        return $this->hasMany(SalesOrder::class);
    }

    public function payments()
    {
        return $this->hasManyThrough(Payment::class, SalesOrder::class, 'user_id', 'sales_order_id');
    }

    public function appointmentServices()
    {
        return $this->hasMany(AppointmentService::class);
    }

    public function scheduleRules()
    {
        return $this->hasMany(UserScheduleRule::class);
    }

    public function assignedAppointments()
    {
        return $this->hasMany(Appointment::class, 'user_id');
    }

    public function customerAppointments()
    {
        return $this->hasMany(Appointment::class, 'customer_id');
    }

    public function employmentProfile()
    {
        return $this->hasOne(UserEmploymentProfile::class);
    }

    public static function storeRoleDefinitions(): array
    {
        return [
            'superowner' => [
                'name' => 'Superowner',
                'aliases' => ['superowner', 'super_user', 'super user', 'super-admin'],
                'description' => 'Gestiona todas las sedes corporativas y controla los procesos globales del negocio.',
                'permissions' => [
                    'Administrar todas las sedes del grupo corporativo.',
                    'Asignar administradores de sede y definir permisos locales.',
                    'Supervisar operaciones globales sin depender de una sede específica.',
                ],
            ],
            'owner' => [
                'name' => 'Owner',
                'aliases' => ['owner'],
                'description' => 'Dirige la sede y controla la configuracion general del negocio.',
                'permissions' => [
                    'Configurar la sede, marca, colores y datos generales.',
                    'Crear usuarios y asignar roles de admin, vendedor y almacenista.',
                    'Supervisar ventas, inventario, almacenes, productos y metodos de pago.',
                ],
            ],
            'sede_admin' => [
                'name' => 'Admin de Sede',
                'aliases' => ['sede_admin', 'admin de sede', 'sede-admin'],
                'description' => 'Administra la operacion diaria de una sede específica.',
                'permissions' => [
                    'Gestionar catalogo, pagos, ventas e inventario en la sede asignada.',
                    'Crear usuarios operativos con rol de vendedor y almacenista.',
                    'No puede administrar las sedes de otros puntos de negocio.',
                ],
            ],
            'admin' => [
                'name' => 'Admin',
                'aliases' => ['admin', 'administrador'],
                'description' => 'Administra la operacion diaria de la sede sin reemplazar al owner.',
                'permissions' => [
                    'Gestionar catalogo, pagos, ventas e inventario.',
                    'Crear usuarios operativos con rol de vendedor y almacenista.',
                    'No puede asignar ni cambiar el rol owner.',
                ],
            ],
            'seller' => [
                'name' => 'Vendedor',
                'aliases' => ['vendor', 'vendedor'],
                'description' => 'Se enfoca en registrar ventas y hacer seguimiento comercial.',
                'permissions' => [
                    'Registrar ventas y revisar pedidos generados.',
                    'Consultar informacion operativa necesaria para vender.',
                    'No administra configuracion general de la sede.',
                ],
            ],
            'warehouse' => [
                'name' => 'Almacenista',
                'aliases' => ['almacen', 'almacenista'],
                'description' => 'Se encarga de entradas de inventario y entrega de pedidos.',
                'permissions' => [
                    'Registrar entradas de inventario y consultar historiales.',
                    'Preparar y despachar pedidos asignados a almacen.',
                    'No modifica configuracion comercial de la sede.',
                ],
            ],
            'delivery' => [
                'name' => 'Delivery',
                'aliases' => ['delivery', 'repartidor'],
                'description' => 'Gestiona despachos, entregas y seguimiento operativo del reparto.',
                'permissions' => [
                    'Consultar pedidos pendientes de entrega y su detalle.',
                    'Actualizar el estado de despacho y entrega.',
                    'No administra configuracion comercial ni pagos.',
                ],
            ],
        ];
    }

    public static function canonicalRoleName(?string $roleName): ?string
    {
        if (!$roleName) {
            return null;
        }

        $normalizedRoleName = strtolower(trim((string) $roleName));

        foreach (self::storeRoleDefinitions() as $key => $definition) {
            if (in_array($normalizedRoleName, $definition['aliases'], true)) {
                return $key;
            }
        }

        return $normalizedRoleName;
    }

    public static function displayRoleName(?string $roleName): string
    {
        $canonicalRoleName = self::canonicalRoleName($roleName);
        $definitions = self::storeRoleDefinitions();

        return $definitions[$canonicalRoleName]['name'] ?? ucfirst((string) $roleName);
    }

    public static function rawRoleNamesForKeys(array $roleKeys): array
    {
        $definitions = self::storeRoleDefinitions();
        $rawRoleNames = [];

        foreach ($roleKeys as $roleKey) {
            foreach (($definitions[$roleKey]['aliases'] ?? []) as $alias) {
                $rawRoleNames[] = strtolower((string) $alias);
            }
        }

        return array_values(array_unique($rawRoleNames));
    }

    public function canonicalRole(): ?string
    {
        return self::canonicalRoleName(optional($this->role)->name);
    }

    public function hasStoreRole(string ...$roleKeys): bool
    {
        $canonicalRole = $this->canonicalRole();
        $normalizedKeys = array_values(array_unique(array_map(static fn ($value) => strtolower(trim((string) $value)), $roleKeys)));

        if ($canonicalRole === 'superowner') {
            return in_array('superowner', $normalizedKeys, true)
                || in_array('owner', $normalizedKeys, true)
                || in_array('admin', $normalizedKeys, true);
        }

        if ($canonicalRole === 'sede_admin') {
            return in_array('sede_admin', $normalizedKeys, true)
                || in_array('admin', $normalizedKeys, true);
        }

        return in_array($canonicalRole, $normalizedKeys, true);
    }

    public function isOwner(): bool
    {
        return $this->hasStoreRole('owner');
    }

    public function isAdmin(): bool
    {
        return $this->hasStoreRole('admin');
    }

    public function isSuperowner(): bool
    {
        return $this->hasStoreRole('superowner');
    }

    public function canManageTenantStore(): bool
    {
        return $this->hasStoreRole('owner', 'admin');
    }

    public function canManageSedes(): bool
    {
        return $this->isSuperowner() || $this->canManageTenantStore();
    }

    public function canAssignStoreRoles(): bool
    {
        return $this->canManageTenantStore();
    }

    public function assignableStoreRoleKeys(): array
    {
        if ($this->isSuperowner()) {
            return ['sede_admin', 'admin', 'seller', 'warehouse', 'delivery'];
        }

        if ($this->isOwner()) {
            return ['admin', 'seller', 'warehouse', 'delivery'];
        }

        if ($this->isAdmin()) {
            return ['seller', 'warehouse', 'delivery'];
        }

        return [];
    }

    /**
     * Obtén el identificador único del usuario para JWT.
     *
     * @return mixed
     */
    public function getJWTIdentifier()
    {
        return $this->getKey(); // Retorna el ID del usuario
    }

    /**
     * Obtén las reclamaciones personalizadas que incluirás en el JWT.
     *
     * @return array
     */
    public function getJWTCustomClaims()
    {
        return [
            'role' => $this->role->name, // Accede correctamente al rol del usuario
            'name' => $this->name, // Agrega el nombre del usuario si es necesario
        ];
    }
}
