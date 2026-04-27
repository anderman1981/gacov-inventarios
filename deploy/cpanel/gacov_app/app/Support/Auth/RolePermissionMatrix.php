<?php

declare(strict_types=1);

namespace App\Support\Auth;

final class RolePermissionMatrix
{
    /**
     * @return list<string>
     */
    public static function permissions(): array
    {
        return [
            'system.modules',
            'system.develop',
            'users.view',
            'users.create',
            'users.edit',
            'users.delete',
            'users.assign_roles',
            'roles.view',
            'roles.manage',
            'products.view',
            'products.create',
            'products.edit',
            'products.delete',
            'products.import',
            'machines.view',
            'machines.create',
            'machines.edit',
            'machines.delete',
            'inventory.view',
            'inventory.load_excel',
            'inventory.load_vehicle_excel',
            'inventory.load_machine_excel',
            'inventory.adjust',
            'movements.view',
            'transfers.view',
            'transfers.create',
            'transfers.approve',
            'transfers.complete',
            'drivers.view',
            'drivers.assign_routes',
            'stockings.view',
            'stockings.create',
            'stockings.own',
            'sales.view',
            'sales.create',
            'sales.own',
            'reports.view',
            'reports.export_excel',
            'reports.worldoffice',
            'invoices.view',
            'invoices.create',
            'invoices.edit',
            'invoices.issue',
            'invoices.cancel',
            'invoices.payments',
            'dashboard.full',
            'dashboard.own',
            'vehicle.view',
            'vehicle.inventory.view',
            'cash.manage',   // Registrar entregas de efectivo a conductores
            'cash.view',     // Ver historial de entregas recibidas
            'sales.review',  // Revisión detallada de ventas por ruta/máquina (manager)
        ];
    }

    /**
     * @return array<string, list<string>>
     */
    public static function rolePermissions(): array
    {
        $permissions = self::permissions();

        return [
            'super_admin' => $permissions,
            'admin' => [
                'users.view',
                'users.create',
                'users.edit',
                'users.delete',
                'users.assign_roles',
                'roles.view',
                'roles.manage',
                'products.view',
                'products.create',
                'products.edit',
                'products.delete',
                'products.import',
                'inventory.view',
                'inventory.load_excel',
                'inventory.load_vehicle_excel',
                'inventory.load_machine_excel',
                'inventory.adjust',
                'transfers.view',
                'transfers.create',
                'transfers.approve',
                'transfers.complete',
                'drivers.view',
                'drivers.assign_routes',
                'movements.view',
                'reports.view',
                'invoices.view',
                'cash.manage',
                'cash.view',
                'reports.worldoffice',
                'vehicle.view',
                'vehicle.inventory.view',
                'dashboard.full',
            ],
            'manager' => [
                'inventory.view',
                'inventory.load_vehicle_excel',
                'inventory.load_machine_excel',
                'inventory.adjust',
                'transfers.view',
                'transfers.create',
                'drivers.view',
                'drivers.assign_routes',
                'machines.view',
                'machines.create',
                'machines.edit',
                'movements.view',
                'reports.view',
                'reports.export_excel',
                'reports.worldoffice',
                'sales.view',
                'sales.create',
                'sales.review',
                'cash.manage',
                'dashboard.full',
            ],
            'contador' => [
                'movements.view',
                'sales.view',
                'reports.view',
                'reports.worldoffice',
                'invoices.view',
                'dashboard.full',
            ],
            'conductor' => [
                'drivers.view',
                'stockings.view',
                'stockings.create',
                'stockings.own',
                'sales.view',
                'sales.create',
                'sales.own',
                'cash.view',
                'dashboard.own',
                'vehicle.view',
            ],
        ];
    }
}
