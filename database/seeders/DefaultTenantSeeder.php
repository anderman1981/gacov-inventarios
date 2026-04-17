<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\AppModule;
use App\Models\Route;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\TenantBillingProfile;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

/**
 * Seeder del tenant inicial para Inversiones GACOV S.A.S.
 *
 * Crea:
 *   1. Los 5 planes de suscripción con sus módulos
 *   2. Los módulos del sistema
 *   3. El tenant GACOV con suscripción Fase 5 (Empresarial)
 *   4. Asigna tenant_id=1 a todos los registros existentes sin tenant
 *
 * Ejecutar: php artisan db:seed --class=DefaultTenantSeeder
 */
final class DefaultTenantSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. MÓDULOS DEL SISTEMA ─────────────────────────────────────────
        $modules = [
            ['key' => 'auth',          'name' => 'Autenticación',           'description' => 'Login, roles y permisos', 'phase_required' => 1, 'sort_order' => 1],
            ['key' => 'dashboard',     'name' => 'Dashboard',               'description' => 'Panel principal', 'phase_required' => 1, 'sort_order' => 2],
            ['key' => 'drivers',       'name' => 'Conductores',             'description' => 'Surtido de máquinas y planillas', 'phase_required' => 1, 'sort_order' => 3],
            ['key' => 'inventory',     'name' => 'Inventario',              'description' => 'Bodega, vehículo, movimientos', 'phase_required' => 1, 'sort_order' => 4],
            ['key' => 'products',      'name' => 'Productos',               'description' => 'Catálogo de productos', 'phase_required' => 1, 'sort_order' => 5],
            ['key' => 'machines',      'name' => 'Máquinas',                'description' => 'Gestión de máquinas expendedoras', 'phase_required' => 1, 'sort_order' => 6],
            ['key' => 'transfers',     'name' => 'Traslados',               'description' => 'Órdenes de traslado', 'phase_required' => 1, 'sort_order' => 7],
            ['key' => 'users',         'name' => 'Usuarios',                'description' => 'Administración de usuarios', 'phase_required' => 1, 'sort_order' => 8],
            ['key' => 'ocr',           'name' => 'OCR / Importación IA',    'description' => 'Lectura de planillas con IA', 'phase_required' => 1, 'sort_order' => 9],
            ['key' => 'invoices',      'name' => 'Facturas',                'description' => 'Facturación formal y pagos del cliente', 'phase_required' => 1, 'sort_order' => 10],
            ['key' => 'routes',        'name' => 'Rutas',                   'description' => 'Gestión de rutas de distribución', 'phase_required' => 2, 'sort_order' => 11],
            ['key' => 'sales',         'name' => 'Ventas de máquinas',      'description' => 'Registro de ventas por máquina', 'phase_required' => 2, 'sort_order' => 12],
            ['key' => 'reports',       'name' => 'Reportes',                'description' => 'Exportación PDF y Excel', 'phase_required' => 2, 'sort_order' => 13],
            ['key' => 'analytics',     'name' => 'Analytics',               'description' => 'Gráficas y estadísticas', 'phase_required' => 3, 'sort_order' => 14],
            ['key' => 'alerts',        'name' => 'Alertas de stock',        'description' => 'Notificaciones de stock mínimo', 'phase_required' => 3, 'sort_order' => 15],
            ['key' => 'world_office',  'name' => 'Integración WorldOffice', 'description' => 'Exportación contable', 'phase_required' => 4, 'sort_order' => 16],
            ['key' => 'geolocation',   'name' => 'Geolocalización',         'description' => 'Rastreo de conductores', 'phase_required' => 4, 'sort_order' => 17],
            ['key' => 'api',           'name' => 'API REST',                'description' => 'Acceso programático externo', 'phase_required' => 4, 'sort_order' => 18],
            ['key' => 'white_label',   'name' => 'White-label',             'description' => 'Marca propia por tenant', 'phase_required' => 5, 'sort_order' => 19],
        ];

        foreach ($modules as $moduleData) {
            AppModule::updateOrCreate(['key' => $moduleData['key']], $moduleData);
        }

        $this->command->info('✓ '.count($modules).' módulos creados/verificados.');

        // ── 2. PLANES DE SUSCRIPCIÓN ───────────────────────────────────────
        // La tabla usa: slug, monthly_price, yearly_price, phase, modules (json), features (json)
        $plans = [
            [
                'slug' => 'starter',
                'name' => 'Starter',
                'phase' => 1,
                'monthly_price' => 290000,
                'yearly_price' => 2958000,
                'sort_order' => 1,
                'is_active' => true,
                'modules' => json_encode(['auth', 'dashboard', 'drivers', 'inventory', 'products', 'machines', 'transfers', 'users', 'ocr']),
                'features' => json_encode(['OCR planillas', 'Inventario bodega y vehículo', 'Conductores y surtido', 'Traslados básicos']),
                'max_users' => 5,
                'max_machines' => 20,
                'max_routes' => 2,
                'max_warehouses' => 1,
            ],
            [
                'slug' => 'basic',
                'name' => 'Básico',
                'phase' => 2,
                'monthly_price' => 690000,
                'yearly_price' => 7038000,
                'sort_order' => 2,
                'is_active' => true,
                'modules' => json_encode(['auth', 'dashboard', 'drivers', 'inventory', 'products', 'machines', 'transfers', 'routes', 'users', 'ocr', 'sales', 'reports']),
                'features' => json_encode(['Todo Starter', 'Ventas de máquinas', 'Reportes PDF/Excel', 'Gestión multi-ruta']),
                'max_users' => 10,
                'max_machines' => 50,
                'max_routes' => 5,
                'max_warehouses' => 2,
            ],
            [
                'slug' => 'professional',
                'name' => 'Profesional',
                'phase' => 3,
                'monthly_price' => 890000,
                'yearly_price' => 9078000,
                'sort_order' => 3,
                'is_active' => true,
                'modules' => json_encode(['auth', 'dashboard', 'drivers', 'inventory', 'products', 'machines', 'transfers', 'routes', 'users', 'ocr', 'sales', 'reports', 'analytics', 'alerts']),
                'features' => json_encode(['Todo Básico', 'Analytics en tiempo real', 'Alertas de stock mínimo', 'Reportes multi-filtro']),
                'max_users' => 20,
                'max_machines' => 100,
                'max_routes' => 10,
                'max_warehouses' => 3,
            ],
            [
                'slug' => 'business',
                'name' => 'Empresarial',
                'phase' => 4,
                'monthly_price' => 1200000,
                'yearly_price' => 12240000,
                'sort_order' => 4,
                'is_active' => true,
                'modules' => json_encode(['auth', 'dashboard', 'drivers', 'inventory', 'products', 'machines', 'transfers', 'routes', 'users', 'ocr', 'sales', 'reports', 'analytics', 'alerts', 'world_office', 'geolocation', 'api']),
                'features' => json_encode(['Todo Profesional', 'WorldOffice integrado', 'Geolocalización conductores', 'API REST']),
                'max_users' => 50,
                'max_machines' => 500,
                'max_routes' => 20,
                'max_warehouses' => 5,
            ],
            [
                'slug' => 'enterprise',
                'name' => 'Enterprise',
                'phase' => 5,
                'monthly_price' => 1500000,
                'yearly_price' => 15300000,
                'sort_order' => 5,
                'is_active' => true,
                'modules' => json_encode(['auth', 'dashboard', 'drivers', 'inventory', 'products', 'machines', 'transfers', 'routes', 'users', 'ocr', 'sales', 'reports', 'analytics', 'alerts', 'world_office', 'geolocation', 'api', 'white_label']),
                'features' => json_encode(['Todo Empresarial', 'White-label', 'Backup automatizado', 'Soporte prioritario', 'SLA 99.9%']),
                'max_users' => 9999,
                'max_machines' => 9999,
                'max_routes' => 9999,
                'max_warehouses' => 9999,
            ],
        ];

        foreach ($plans as $planData) {
            SubscriptionPlan::firstOrCreate(
                ['slug' => $planData['slug']],
                $planData
            );
        }

        $this->command->info('✓ 5 planes de suscripción creados/verificados.');

        // ── 3. TENANT GACOV ────────────────────────────────────────────────
        $tenant = Tenant::firstOrCreate(
            ['slug' => 'gacov'],
            [
                'name' => 'Inversiones GACOV S.A.S.',
                'nit' => '900983146',
                'email' => 'sistema@gacov.com.co',
                'phone' => null,
                'is_active' => true,
            ]
        );

        $this->command->info("✓ Tenant GACOV (ID: {$tenant->id}) creado/verificado.");

        // Suscripción Fase 5 Enterprise para GACOV
        $enterprisePlan = SubscriptionPlan::where('slug', 'enterprise')->first();

        if ($enterprisePlan && ! $tenant->subscription) {
            Subscription::create([
                'tenant_id' => $tenant->id,
                'plan_id' => $enterprisePlan->id,
                'status' => 'active',
                'billing_cycle' => 'monthly',
                'current_period_start' => now(),
                'current_period_end' => now()->addYear(), // 1 año de gracia
                'trial_ends_at' => null,
            ]);
            $this->command->info('✓ Suscripción Enterprise creada para GACOV.');
        } else {
            $this->command->info('✓ Suscripción GACOV ya existente, omitida.');
        }

        TenantBillingProfile::updateOrCreate(
            ['tenant_id' => $tenant->id],
            TenantBillingProfile::defaultPayload(1)
        );

        $this->command->info('✓ Perfil operativo de GACOV sincronizado en Fase 1 para activación escalonada.');

        $routes = [
            'RT1' => Route::withoutGlobalScopes()->updateOrCreate(
                ['code' => 'RT1'],
                [
                    'tenant_id' => $tenant->id,
                    'name' => 'Ruta 1',
                    'vehicle_plate' => null,
                    'is_active' => true,
                ]
            ),
            'RT2' => Route::withoutGlobalScopes()->updateOrCreate(
                ['code' => 'RT2'],
                [
                    'tenant_id' => $tenant->id,
                    'name' => 'Ruta 2',
                    'vehicle_plate' => null,
                    'is_active' => true,
                ]
            ),
        ];

        $gacovUsers = [
            [
                'name' => 'Administrador GACOV',
                'email' => 'admin@gacov.com.co',
                'password' => 'AdminGacov2026!',
                'role' => 'admin',
                'route_code' => null,
            ],
            [
                'name' => 'Manager GACOV',
                'email' => 'manager@gacov.com.co',
                'password' => 'ManagerGacov2026!',
                'role' => 'manager',
                'route_code' => null,
            ],
            [
                'name' => 'Contador GACOV',
                'email' => 'contador@gacov.com.co',
                'password' => 'ContadorGacov2026!',
                'role' => 'contador',
                'route_code' => null,
            ],
            [
                'name' => 'Osvaldo',
                'email' => 'osvaldo@gacov.com.co',
                'password' => 'Gacov2026!',
                'role' => 'conductor',
                'route_code' => 'RT1',
            ],
            [
                'name' => 'Andres',
                'email' => 'andres@gacov.com.co',
                'password' => 'Gacov2026!',
                'role' => 'conductor',
                'route_code' => 'RT2',
            ],
        ];

        foreach ($gacovUsers as $userData) {
            $user = User::withoutGlobalScopes()->firstOrNew(['email' => $userData['email']]);

            if (! $user->exists) {
                $user->password = Hash::make($userData['password']);
            }

            $user->fill([
                'name' => $userData['name'],
                'tenant_id' => $tenant->id,
                'route_id' => $userData['route_code'] !== null ? $routes[$userData['route_code']]->id : null,
                'is_active' => true,
                'must_change_password' => false,
                'is_super_admin' => false,
                'email_verified_at' => $user->email_verified_at ?? now(),
            ]);
            $user->save();

            if (Role::query()->where('name', $userData['role'])->exists()) {
                $user->syncRoles([$userData['role']]);
            }

            if ($userData['route_code'] !== null) {
                $routes[$userData['route_code']]->forceFill([
                    'tenant_id' => $tenant->id,
                    'driver_user_id' => $user->id,
                ])->save();
            }
        }

        $this->command->info('✓ Usuarios base de GACOV sincronizados (admin, manager, contador y conductores).');

        // ── 4. ASIGNAR TENANT_ID=1 A REGISTROS EXISTENTES ─────────────────
        $tables = [
            'products', 'machines', 'warehouses', 'routes', 'stock',
            'stock_movements', 'transfer_orders', 'transfer_order_items',
            'machine_stocking_records', 'machine_sales',
        ];

        $updated = 0;
        foreach ($tables as $table) {
            if (DB::getSchemaBuilder()->hasTable($table) && DB::getSchemaBuilder()->hasColumn($table, 'tenant_id')) {
                $count = DB::table($table)->whereNull('tenant_id')->update(['tenant_id' => $tenant->id]);
                if ($count > 0) {
                    $this->command->info("  → {$table}: {$count} registros asignados al tenant GACOV.");
                    $updated += $count;
                }
            }
        }

        $this->command->info("✓ {$updated} registros existentes asignados al tenant GACOV.");

        // ── 5. ASIGNAR USUARIO SUPER ADMIN ────────────────────────────────
        $superAdmin = User::withoutGlobalScopes()->where('is_super_admin', true)->first();
        if ($superAdmin) {
            $superAdmin->update(['tenant_id' => null]); // super admin sin tenant
            $this->command->info("✓ Super admin '{$superAdmin->email}' sin tenant (acceso global).");
        }

        // Asignar tenant a usuarios sin tenant que no son super admin
        $usersUpdated = User::withoutGlobalScopes()
            ->whereNull('tenant_id')
            ->where('is_super_admin', false)
            ->update(['tenant_id' => $tenant->id]);

        if ($usersUpdated > 0) {
            $this->command->info("✓ {$usersUpdated} usuario(s) asignados al tenant GACOV.");
        }

        $this->command->newLine();
        $this->command->info('══════════════════════════════════════════════');
        $this->command->info('  Seeder DefaultTenantSeeder completado ✅');
        $this->command->info("  Tenant: {$tenant->name} (ID: {$tenant->id})");
        $this->command->info('  Plan: Enterprise (Fase 5)');
        $this->command->info('  Vigencia: 1 año desde hoy');
        $this->command->info('══════════════════════════════════════════════');
    }
}
