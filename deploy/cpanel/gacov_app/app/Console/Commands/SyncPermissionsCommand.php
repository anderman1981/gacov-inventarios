<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\Auth\RolePermissionMatrix;
use Illuminate\Console\Command;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final class SyncPermissionsCommand extends Command
{
    protected $signature = 'permissions:sync 
                            {--fresh : Limpiar cache antes de sincronizar}
                            {--check : Solo verificar, no sincronizar}';

    protected $description = 'Sincroniza permisos con roles del sistema GACOV';

    public function handle(): int
    {
        if ($this->option('fresh')) {
            app()[PermissionRegistrar::class]->forgetCachedPermissions();
            $this->info('Cache de permisos limpiada.');
        }

        $permissions = RolePermissionMatrix::permissions();
        $rolePermissions = RolePermissionMatrix::rolePermissions();

        // Asegurar que todos los permisos del sistema existan antes de sincronizar roles.
        $existingPermissions = Permission::pluck('name')->toArray();
        $missingPermissions = array_diff($permissions, $existingPermissions);

        if (! empty($missingPermissions)) {
            foreach ($missingPermissions as $permissionName) {
                Permission::firstOrCreate([
                    'name' => $permissionName,
                    'guard_name' => 'web',
                ]);
            }

            $this->info('Permisos creados: '.implode(', ', $missingPermissions));
        }

        if ($this->option('check')) {
            $this->info('Modo verificación - no se sincronizó nada.');
            $this->table(
                ['Rol', 'Permisos Asignados'],
                collect($rolePermissions)->map(fn ($perms, $role) => [$role, count($perms)])->toArray()
            );

            return Command::SUCCESS;
        }

        // Sincronizar
        $this->info('Sincronizando permisos con roles...');

        foreach ($rolePermissions as $roleName => $perms) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($perms);
            $this->line("  ✅ {$roleName}: ".count($perms).' permisos');
        }

        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        $this->newLine();
        $this->info('✅ Sincronización completada.');

        return Command::SUCCESS;
    }
}
