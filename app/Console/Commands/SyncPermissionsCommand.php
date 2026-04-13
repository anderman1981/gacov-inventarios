<?php

declare(strict_types=1);

namespace App\Console\Commands;

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

        $permissions = [
            // Sistema
            'system.modules',
            'system.develop',
            // Usuarios
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',
            'users.assign_roles',
            // Roles
            'roles.view',
            'roles.manage',
            // Productos
            'products.view',
            'products.create',
            'products.edit',
            'products.delete',
            'products.import',
            // Máquinas
            'machines.view',
            'machines.create',
            'machines.edit',
            'machines.delete',
            // Inventario
            'inventory.view',
            'inventory.load_excel',
            'inventory.adjust',
            // Movimientos
            'movements.view',
            // Traslados
            'transfers.view',
            'transfers.create',
            'transfers.approve',
            'transfers.complete',
            // Surtido
            'stockings.view',
            'stockings.create',
            'stockings.own',
            // Ventas
            'sales.view',
            'sales.create',
            'sales.own',
            // Reportes
            'reports.view',
            'reports.export_excel',
            'reports.worldoffice',
            // Dashboard
            'dashboard.full',
            'dashboard.own',
            // Vehículo (conductor)
            'vehicle.view',
            'vehicle.inventory.view',
        ];

        $rolePermissions = [
            'super_admin' => $permissions,

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

            'manager' => [
                'products.view', 'products.create', 'products.edit',
                'machines.view', 'machines.create', 'machines.edit',
                'inventory.view', 'inventory.adjust',
                'transfers.view', 'transfers.create', 'transfers.approve',
                'stockings.view',
                'sales.view',
                'reports.view', 'reports.export_excel',
                'dashboard.full',
            ],

            'contador' => [
                'products.view',
                'machines.view',
                'inventory.view',
                'transfers.view',
                'stockings.view',
                'sales.view',
                'reports.view', 'reports.export_excel', 'reports.worldoffice',
                'dashboard.full',
            ],

            'conductor' => [
                // Solo productos (no ve bodega principal)
                'products.view',
                // Solo su propio surtido y ventas
                'stockings.view', 'stockings.create', 'stockings.own',
                'sales.view', 'sales.create', 'sales.own',
                // Dashboard personalizado
                'dashboard.own',
                // Vehículo (no ve todas las máquinas ni bodega principal)
                'vehicle.view',
                'vehicle.inventory.view',
            ],
        ];

        // Verificar permisos
        $existingPermissions = Permission::pluck('name')->toArray();
        $missingPermissions = array_diff($permissions, $existingPermissions);

        if (! empty($missingPermissions)) {
            $this->warn('Permisos faltantes: '.implode(', ', $missingPermissions));
            $this->info('Ejecuta: php artisan db:seed --class=RoleSeeder');
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
