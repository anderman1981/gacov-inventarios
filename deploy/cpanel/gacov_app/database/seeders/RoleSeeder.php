<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Support\Auth\RolePermissionMatrix;
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
        $permissions = RolePermissionMatrix::permissions();

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
        }

        // ── Definición de roles y sus permisos ────────────────────────────
        $rolePermissions = RolePermissionMatrix::rolePermissions();

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
