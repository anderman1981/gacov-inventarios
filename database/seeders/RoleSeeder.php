<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

final class RoleSeeder extends Seeder
{
    public function run(): void
    {
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // ── Permisos granulares ───────────────────────────────────────────
        $permissions = [
            // Sistema
            'system.modules',       // instalar/configurar módulos (solo super_admin)
            'system.develop',       // acceso a herramientas de desarrollo (solo super_admin)

            // Usuarios
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',
            'users.assign_roles',

            // Roles y permisos
            'roles.view',
            'roles.manage',

            // Productos y categorías
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

            // Inventario bodega
            'inventory.view',
            'inventory.load_excel',
            'inventory.adjust',

            // Movimientos (historial)
            'movements.view',

            // Traslados
            'transfers.view',
            'transfers.create',
            'transfers.approve',
            'transfers.complete',

            // Surtido (conductor)
            'stockings.view',
            'stockings.create',
            'stockings.own',         // solo ve sus propios surtidos

            // Ventas máquinas (conductor)
            'sales.view',
            'sales.create',
            'sales.own',             // solo registra en sus máquinas

            // Reportes
            'reports.view',
            'reports.export_excel',
            'reports.worldoffice',

            // Dashboard
            'dashboard.full',       // KPIs completos
            'dashboard.own',        // solo vista de su ruta

            // Vehículo (conductor)
            'vehicle.view',
            'vehicle.inventory.view',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        // ── Definición de roles y sus permisos ────────────────────────────
        $rolePermissions = [

            'super_admin' => $permissions, // TODO sin excepción

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

        foreach ($rolePermissions as $roleName => $perms) {
            $role = Role::firstOrCreate(['name' => $roleName, 'guard_name' => 'web']);
            $role->syncPermissions($perms);
        }

        // ── Usuario Super Admin ───────────────────────────────────────────
        $superAdmin = User::updateOrCreate(
            ['email' => 'superadmin@gacov.com.co'],
            [
                'name' => 'Super Administrador',
                'password' => Hash::make('SuperGacov2026!$'),
                'is_active' => true,
                'must_change_password' => false,
                'email_verified_at' => now(),
            ]
        );
        $superAdmin->syncRoles(['super_admin']);

        // ── Usuario Admin Principal ───────────────────────────────────────
        $admin = User::updateOrCreate(
            ['email' => 'admin@gacov.com.co'],
            [
                'name' => 'Administrador GACOV',
                'password' => Hash::make('AdminGacov2026!'),
                'is_active' => true,
                'must_change_password' => true,
                'email_verified_at' => now(),
            ]
        );
        $admin->syncRoles(['admin']);

        $this->command->info('');
        $this->command->info('╔══════════════════════════════════════════════════╗');
        $this->command->info('║          CREDENCIALES DEL SISTEMA GACOV          ║');
        $this->command->info('╠══════════════════════════════════════════════════╣');
        $this->command->info('║  SUPER ADMIN                                     ║');
        $this->command->info('║  Email:  superadmin@gacov.com.co                 ║');
        $this->command->info('║  Pass:   SuperGacov2026!$                        ║');
        $this->command->info('╠══════════════════════════════════════════════════╣');
        $this->command->info('║  ADMIN                                           ║');
        $this->command->info('║  Email:  admin@gacov.com.co                      ║');
        $this->command->info('║  Pass:   AdminGacov2026!  (cambiar al 1er login) ║');
        $this->command->info('╚══════════════════════════════════════════════════╝');
        $this->command->info('');
        $this->command->info('5 roles creados: super_admin, admin, manager, contador, conductor');
        $this->command->info(count($permissions).' permisos registrados.');
    }
}
