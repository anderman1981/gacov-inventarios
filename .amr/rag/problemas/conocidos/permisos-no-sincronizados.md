# Permisos No Sincronizados con Roles

## metadata
```yaml
id: permisos-no-sincronizados
severity: critical
date: 2026-04-12
affected_roles:
  - super_admin
  - admin
  - manager
  - contador
  - conductor
resolved: true
```

## Síntomas

- Todas las rutas protegidas dan **HTTP 403 Forbidden**
- El usuario puede hacer login correctamente
- Dashboard funciona (200) pero otras secciones no
- El middleware `auth` y `tenant` pasan, pero `can()` retorna false

## Diagnóstico

```bash
# Verificar permisos de un usuario
php artisan tinker --execute="
\$user = App\Models\User::withoutGlobalScopes()
  ->where('email', 'superadmin@gacov.com.co')->first();
echo 'Permissions count: ' . \$user->getAllPermissions()->count() . PHP_EOL;
echo 'transfers.view: ' . (\$user->can('transfers.view') ? 'YES' : 'NO') . PHP_EOL;
"
```

**Resultado antes de corregir:**
```
Permissions count: 0
transfers.view: NO
```

**Verificar roles:**
```bash
php artisan tinker --execute="
\$role = Spatie\Permission\Models\Role::where('name', 'super_admin')->first();
echo 'Permissions in role: ' . \$role->permissions->count() . PHP_EOL;
"
```

**Resultado:**
```
Permissions in role: 0
```

## Causa Raíz

Los permisos existían en la tabla `permissions` (37 registros) pero **no estaban vinculados a los roles** en la tabla `role_has_permissions`.

Esto puede ocurrir por:
1. Seeder corrupto o incompleto
2. Migración que borró las relaciones
3. Cache de permisos de Spatie no limpiada

## Solución

### Opción 1: Ejecutar en Tinker (una vez)

```bash
php artisan tinker --execute="
app()[Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

\$permissions = [
    'system.modules', 'system.develop',
    'users.view', 'users.create', 'users.edit', 'users.delete', 'users.assign_roles',
    'roles.view', 'roles.manage',
    'products.view', 'products.create', 'products.edit', 'products.delete', 'products.import',
    'machines.view', 'machines.create', 'machines.edit', 'machines.delete',
    'inventory.view', 'inventory.load_excel', 'inventory.adjust',
    'movements.view',
    'transfers.view', 'transfers.create', 'transfers.approve', 'transfers.complete',
    'stockings.view', 'stockings.create', 'stockings.own',
    'sales.view', 'sales.create', 'sales.own',
    'reports.view', 'reports.export_excel', 'reports.worldoffice',
    'dashboard.full', 'dashboard.own',
];

\$rolePermissions = [
    'super_admin' => \$permissions,
    'admin' => [
        'users.view', 'users.create', 'users.edit', 'users.delete', 'users.assign_roles',
        'roles.view', 'roles.manage',
        'products.view', 'products.create', 'products.edit', 'products.delete', 'products.import',
        'machines.view', 'machines.create', 'machines.edit', 'machines.delete',
        'inventory.view', 'inventory.load_excel', 'inventory.adjust',
        'transfers.view', 'transfers.create', 'transfers.approve', 'transfers.complete',
        'stockings.view',
        'sales.view',
        'reports.view', 'reports.export_excel', 'reports.worldoffice',
        'dashboard.full',
    ],
    // ... otros roles
];

foreach (\$rolePermissions as \$roleName => \$perms) {
    \$role = Spatie\Permission\Models\Role::firstOrCreate(['name' => \$roleName, 'guard_name' => 'web']);
    \$role->syncPermissions(\$perms);
    echo \"Sincronizado: {\$roleName} - \" . count(\$perms) . \" permisos\" . PHP_EOL;
}
"
```

### Opción 2: Crear Comando Artisan (Recomendado)

```php
// app/Console/Commands/SyncPermissions.php
<?php
namespace App\Console\Commands;

use Illuminate\Console\Command;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class SyncPermissions extends Command
{
    protected \$signature = 'permissions:sync';
    protected \$description = 'Sincroniza permisos con roles';

    public function handle(): int
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        \$rolePermissions = [
            'super_admin' => Role::findByName('super_admin')->getAllPermissions()->pluck('name')->toArray(),
            // ...
        ];

        foreach (\$rolePermissions as \$roleName => \$perms) {
            \$role = Role::findByName(\$roleName);
            \$role->syncPermissions(\$perms);
            \$this->info(\"Sincronizado: {\$roleName}\");
        }

        return Command::SUCCESS;
    }
}
```

### Opción 3: Re-ejecutar Seeder

```bash
php artisan db:seed --class=RoleSeeder
php artisan cache:forget spatie.permission.cache
```

## Verificación

```bash
# Verificar que ahora hay permisos
php artisan tinker --execute="
\$user = App\Models\User::withoutGlobalScopes()
  ->where('email', 'superadmin@gacov.com.co')->first();
echo 'Permissions: ' . \$user->getAllPermissions()->count() . PHP_EOL;
echo 'transfers.view: ' . (\$user->can('transfers.view') ? 'YES' : 'NO') . PHP_EOL;
"
```

**Resultado esperado:**
```
Permissions: 37
transfers.view: YES
```

## Prevención

1. **Limpiar cache de permisos** después de modificar seeders:
   ```bash
   php artisan cache:forget spatie.permission.cache
   php artisan permission:cache-reset  # si tienes el package
   ```

2. **Verificar en tests** que los permisos están sincronizados:
   ```php
   public function test_super_admin_has_all_permissions(): void
   {
       \$user = User::factory()->superAdmin()->create();
       \$this->assertTrue(\$user->can('transfers.create'));
       \$this->assertTrue(\$user->can('inventory.view'));
   }
   ```

## Referencias

- Modelo: `app/Models/User.php` (usa HasRoles de Spatie)
- Seeder: `database/seeders/RoleSeeder.php`
- Documentación: [Spatie Permission](https://spatie.be/docs/laravel-permission)
