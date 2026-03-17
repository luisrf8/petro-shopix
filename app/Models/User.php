<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Tymon\JWTAuth\Contracts\JWTSubject;

class User extends Authenticatable implements JWTSubject
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'name',
        'email',
        'password',
        'tenant_id',
        'role_id',
        'phone_number',
        'dni',
        'is_active',
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

    public static function storeRoleDefinitions(): array
    {
        return [
            'owner' => [
                'name' => 'Owner',
                'aliases' => ['owner'],
                'description' => 'Dirige la tienda y controla la configuracion general del negocio.',
                'permissions' => [
                    'Configurar la tienda, marca, colores y datos generales.',
                    'Crear usuarios y asignar roles de admin, vendedor y almacenista.',
                    'Supervisar ventas, inventario, almacenes, productos y metodos de pago.',
                ],
            ],
            'admin' => [
                'name' => 'Admin',
                'aliases' => ['admin', 'administrador'],
                'description' => 'Administra la operacion diaria de la tienda sin reemplazar al owner.',
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
                    'No administra configuracion general de la tienda.',
                ],
            ],
            'warehouse' => [
                'name' => 'Almacenista',
                'aliases' => ['almacen', 'almacenista'],
                'description' => 'Se encarga de entradas de inventario y entrega de pedidos.',
                'permissions' => [
                    'Registrar entradas de inventario y consultar historiales.',
                    'Preparar y despachar pedidos asignados a almacen.',
                    'No modifica configuracion comercial de la tienda.',
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
        return in_array($this->canonicalRole(), $roleKeys, true);
    }

    public function isOwner(): bool
    {
        return $this->hasStoreRole('owner');
    }

    public function isAdmin(): bool
    {
        return $this->hasStoreRole('admin');
    }

    public function canManageTenantStore(): bool
    {
        return $this->hasStoreRole('owner', 'admin');
    }

    public function canAssignStoreRoles(): bool
    {
        return $this->canManageTenantStore();
    }

    public function assignableStoreRoleKeys(): array
    {
        if ($this->isOwner()) {
            return ['admin', 'seller', 'warehouse'];
        }

        if ($this->isAdmin()) {
            return ['seller', 'warehouse'];
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
