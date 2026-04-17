<?php

declare(strict_types=1);

namespace Tests\Feature\Cash;

use App\Models\AppModule;
use App\Models\DriverCashDelivery;
use App\Models\Route as ClientRoute;
use App\Models\Tenant;
use App\Models\TenantBillingProfile;
use App\Models\User;
use App\Models\Warehouse;
use Tests\TestCase;

final class DriverCashControllerTest extends TestCase
{
    public function test_manager_can_open_cash_delivery_detail_by_id(): void
    {
        [$tenant, $route, $manager, $driver] = $this->seedTenantContext();

        $delivery = DriverCashDelivery::create([
            'tenant_id' => $tenant->id,
            'route_id' => $route->id,
            'driver_user_id' => $driver->id,
            'delivered_by_user_id' => $manager->id,
            'delivery_date' => now()->toDateString(),
            'bill_100000' => 1,
            'bill_50000' => 0,
            'bill_20000' => 0,
            'bill_10000' => 0,
            'bill_5000' => 0,
            'bill_2000' => 0,
            'bill_1000' => 0,
            'coin_1000' => 0,
            'coin_500' => 0,
            'coin_200' => 0,
            'coin_100' => 0,
            'coin_50' => 0,
            'notes' => 'Entrega base de prueba',
        ]);

        $this->actingAs($manager)->get(route('cash.show', $delivery->id))
            ->assertOk()
            ->assertSee('Entrega de Efectivo')
            ->assertSee($driver->name)
            ->assertSee('Entrega base de prueba');
    }

    public function test_cash_index_defaults_to_authenticated_users_route(): void
    {
        [$tenant, $route, $manager, $driver] = $this->seedTenantContext();

        $otherRoute = ClientRoute::factory()->create([
            'tenant_id' => $tenant->id,
            'is_active' => true,
            'name' => 'Ruta Alterna',
            'code' => 'RT-ALT',
        ]);

        $manager->update(['route_id' => $route->id]);

        DriverCashDelivery::create([
            'tenant_id' => $tenant->id,
            'route_id' => $route->id,
            'driver_user_id' => $driver->id,
            'delivered_by_user_id' => $manager->id,
            'delivery_date' => now()->toDateString(),
            'bill_100000' => 1,
            'bill_50000' => 0,
            'bill_20000' => 0,
            'bill_10000' => 0,
            'bill_5000' => 0,
            'bill_2000' => 0,
            'bill_1000' => 0,
            'coin_1000' => 0,
            'coin_500' => 0,
            'coin_200' => 0,
            'coin_100' => 0,
            'coin_50' => 0,
        ]);

        DriverCashDelivery::create([
            'tenant_id' => $tenant->id,
            'route_id' => $otherRoute->id,
            'driver_user_id' => $driver->id,
            'delivered_by_user_id' => $manager->id,
            'delivery_date' => now()->toDateString(),
            'bill_100000' => 2,
            'bill_50000' => 0,
            'bill_20000' => 0,
            'bill_10000' => 0,
            'bill_5000' => 0,
            'bill_2000' => 0,
            'bill_1000' => 0,
            'coin_1000' => 0,
            'coin_500' => 0,
            'coin_200' => 0,
            'coin_100' => 0,
            'coin_50' => 0,
        ]);

        $response = $this->actingAs($manager->fresh())->get(route('cash.index'));

        $response->assertRedirect(route('cash.index', ['route_id' => $route->id]));

        $followed = $this->actingAs($manager->fresh())->followingRedirects()->get(route('cash.index'));
        $followed->assertOk();
        $followed->assertSee('Filtro activo');
        $followed->assertSee($route->name);
    }

    /**
     * @return array{0: Tenant, 1: ClientRoute, 2: User, 3: User}
     */
    private function seedTenantContext(): array
    {
        foreach ([
            ['key' => 'dashboard', 'name' => 'Dashboard', 'phase_required' => 1, 'sort_order' => 1],
            ['key' => 'users', 'name' => 'Usuarios', 'phase_required' => 1, 'sort_order' => 2],
            ['key' => 'inventory', 'name' => 'Inventario', 'phase_required' => 1, 'sort_order' => 3],
            ['key' => 'products', 'name' => 'Productos', 'phase_required' => 1, 'sort_order' => 4],
            ['key' => 'machines', 'name' => 'Máquinas', 'phase_required' => 1, 'sort_order' => 5],
            ['key' => 'transfers', 'name' => 'Traslados', 'phase_required' => 1, 'sort_order' => 6],
            ['key' => 'drivers', 'name' => 'Conductores', 'phase_required' => 1, 'sort_order' => 7],
            ['key' => 'sales', 'name' => 'Ventas', 'phase_required' => 2, 'sort_order' => 8],
            ['key' => 'reports', 'name' => 'Reportes', 'phase_required' => 2, 'sort_order' => 9],
            ['key' => 'cash', 'name' => 'Efectivo', 'phase_required' => 1, 'sort_order' => 10],
        ] as $module) {
            AppModule::query()->updateOrCreate(
                ['key' => $module['key']],
                array_merge($module, ['is_active' => true])
            );
        }

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

        $manager = User::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Manager GACOV',
            'email' => 'manager-cash-test@example.com',
            'is_active' => true,
            'must_change_password' => false,
        ]);
        $manager->syncRoles(['manager']);

        $driver = User::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Conductor Prueba',
            'email' => 'driver-cash-test@example.com',
            'is_active' => true,
            'must_change_password' => false,
            'route_id' => $route->id,
        ]);
        $driver->syncRoles(['conductor']);

        $route->update(['driver_user_id' => $driver->id]);

        return [$tenant, $route, $manager, $driver];
    }
}
