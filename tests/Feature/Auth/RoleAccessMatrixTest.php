<?php

declare(strict_types=1);

namespace Tests\Feature\Auth;

use App\Models\AppModule;
use App\Models\Route as ClientRoute;
use App\Models\Tenant;
use App\Models\TenantBillingProfile;
use App\Models\User;
use App\Models\Warehouse;
use Tests\TestCase;

final class RoleAccessMatrixTest extends TestCase
{
    public function test_admin_only_gets_inventory_and_user_management_access(): void
    {
        [$tenant, $route] = $this->seedTenantContext();

        $admin = $this->makeTenantUser($tenant, 'admin', 'admin-role-test@example.com', $route);

        $this->actingAs($admin)->get(route('admin.users.index'))->assertOk();
        $this->actingAs($admin)->get(route('inventory.adjust'))->assertOk();
        $this->actingAs($admin)->get(route('operations.routes.board'))->assertOk();
        $this->actingAs($admin)->get(route('operations.routes.calendar'))->assertOk();
        $this->actingAs($admin)->get(route('machines.index'))->assertRedirect(route('dashboard'));
        $this->actingAs($admin)->get(route('driver.stocking.create'))->assertForbidden();
    }

    public function test_manager_only_gets_inventory_machines_and_reports_access(): void
    {
        [$tenant, $route] = $this->seedTenantContext();

        $manager = $this->makeTenantUser($tenant, 'manager', 'manager-role-test@example.com', $route);

        $this->actingAs($manager)->get(route('machines.index'))->assertOk();
        $this->actingAs($manager)->get(route('inventory.movements'))->assertOk();
        $this->actingAs($manager)->get(route('inventory.vehicles'))->assertOk();
        $this->actingAs($manager)->get(route('inventory.vehicles.import.form'))->assertOk();
        $this->actingAs($manager)->get(route('inventory.machines'))->assertOk();
        $this->actingAs($manager)->get(route('inventory.machines.import.form'))->assertOk();
        $this->actingAs($manager)->get(route('operations.routes.board'))->assertOk();
        $this->actingAs($manager)->get(route('operations.routes.calendar'))->assertOk();
        $this->actingAs($manager)->get(route('admin.users.index'))->assertRedirect(route('dashboard'));
        $this->actingAs($manager)->get(route('driver.sales.create'))->assertRedirect(route('dashboard'));
    }

    public function test_contador_is_limited_to_read_only_financial_views(): void
    {
        [$tenant, $route] = $this->seedTenantContext();

        $contador = $this->makeTenantUser($tenant, 'contador', 'contador-role-test@example.com', $route);

        $this->actingAs($contador)->get(route('dashboard'))->assertOk();
        $this->actingAs($contador)->get(route('inventory.movements'))->assertOk();
        $this->actingAs($contador)->get(route('inventory.warehouse'))->assertRedirect(route('dashboard'));
        $this->actingAs($contador)->get(route('machines.index'))->assertRedirect(route('dashboard'));
        $this->actingAs($contador)->get(route('driver.sales.create'))->assertForbidden();
    }

    public function test_conductor_only_gets_route_operation_access(): void
    {
        [$tenant, $route] = $this->seedTenantContext();

        $conductor = $this->makeTenantUser($tenant, 'conductor', 'conductor-role-test@example.com', $route);

        $this->actingAs($conductor)->get(route('driver.dashboard'))->assertOk();
        $this->actingAs($conductor)->get(route('driver.stocking.create'))->assertOk();
        $this->actingAs($conductor)->get(route('driver.sales.create'))->assertOk();
        $this->actingAs($conductor)->get(route('driver.inventory'))->assertOk();
        $this->actingAs($conductor)->get(route('inventory.warehouse'))->assertRedirect(route('dashboard'));
        $this->actingAs($conductor)->get(route('admin.users.index'))->assertRedirect(route('dashboard'));
    }

    /**
     * @return array{0: Tenant, 1: ClientRoute}
     */
    private function seedTenantContext(): array
    {
        AppModule::query()->create([
            'key' => 'dashboard',
            'name' => 'Dashboard',
            'phase_required' => 1,
            'sort_order' => 1,
            'is_active' => true,
        ]);
        AppModule::query()->create([
            'key' => 'users',
            'name' => 'Usuarios',
            'phase_required' => 1,
            'sort_order' => 2,
            'is_active' => true,
        ]);
        AppModule::query()->create([
            'key' => 'inventory',
            'name' => 'Inventario',
            'phase_required' => 1,
            'sort_order' => 3,
            'is_active' => true,
        ]);
        AppModule::query()->create([
            'key' => 'products',
            'name' => 'Productos',
            'phase_required' => 1,
            'sort_order' => 4,
            'is_active' => true,
        ]);
        AppModule::query()->create([
            'key' => 'machines',
            'name' => 'Máquinas',
            'phase_required' => 1,
            'sort_order' => 5,
            'is_active' => true,
        ]);
        AppModule::query()->create([
            'key' => 'transfers',
            'name' => 'Traslados',
            'phase_required' => 1,
            'sort_order' => 6,
            'is_active' => true,
        ]);
        AppModule::query()->create([
            'key' => 'drivers',
            'name' => 'Conductores',
            'phase_required' => 1,
            'sort_order' => 7,
            'is_active' => true,
        ]);
        AppModule::query()->create([
            'key' => 'sales',
            'name' => 'Ventas',
            'phase_required' => 2,
            'sort_order' => 8,
            'is_active' => true,
        ]);
        AppModule::query()->create([
            'key' => 'reports',
            'name' => 'Reportes',
            'phase_required' => 2,
            'sort_order' => 9,
            'is_active' => true,
        ]);

        $tenant = Tenant::factory()->create();
        TenantBillingProfile::create([
            'tenant_id' => $tenant->id,
            ...TenantBillingProfile::defaultPayload(2),
        ]);

        $route = ClientRoute::factory()->create([
            'tenant_id' => $tenant->id,
            'is_active' => true,
        ]);

        Warehouse::factory()->create([
            'tenant_id' => $tenant->id,
            'route_id' => null,
            'type' => 'bodega',
            'is_active' => true,
        ]);

        return [$tenant, $route];
    }

    private function makeTenantUser(Tenant $tenant, string $role, string $email, ClientRoute $route): User
    {
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'email' => $email,
            'is_super_admin' => false,
            'must_change_password' => false,
            'route_id' => $role === 'conductor' ? $route->id : null,
        ]);
        $user->syncRoles([$role]);

        if ($role === 'conductor') {
            $route->update(['driver_user_id' => $user->id]);
        }

        return $user;
    }
}
