<?php

declare(strict_types=1);

namespace Tests\Feature\Driver;

use App\Models\AppModule;
use App\Models\Machine;
use App\Models\Product;
use App\Models\Route;
use App\Models\Stock;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Warehouse;
use Tests\TestCase;

final class DriverControllerTest extends TestCase
{
    private User $driver;

    private Route $route;

    private Machine $machine;

    private Warehouse $vehicleWarehouse;

    private Warehouse $machineWarehouse;

    private Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        // Flush static module catalog cache between tests
        Tenant::flushActiveModuleCatalog();

        // Create AppModule records so module:* middleware can find them
        $this->seedModules();

        // Create tenant with active subscription (phase 2 covers drivers+sales+inventory)
        $plan = SubscriptionPlan::factory()->create(['phase' => 2, 'is_active' => true]);
        $this->tenant = Tenant::factory()->create(['is_active' => true]);
        Subscription::factory()->create([
            'tenant_id'           => $this->tenant->id,
            'plan_id'             => $plan->id,
            'status'              => 'active',
            'billing_cycle'       => 'monthly',
            'current_period_end'  => now()->addMonth(),
        ]);

        $this->driver = User::factory()->create([
            'is_super_admin' => false,
            'tenant_id'      => $this->tenant->id,
        ]);
        $this->driver->syncRoles(['conductor']);

        $this->route   = Route::factory()->withDriver($this->driver->id)->create();
        $this->driver->update(['route_id' => $this->route->id]);

        $this->machine = Machine::factory()->forRoute($this->route->id)->create();

        $this->vehicleWarehouse = Warehouse::factory()->vehiculo()->create([
            'route_id' => $this->route->id,
        ]);
        $this->machineWarehouse = Warehouse::factory()->maquina()->create([
            'machine_id' => $this->machine->id,
        ]);
    }

    // ──────────────────────────────────────────────────────────────
    // DASHBOARD
    // ──────────────────────────────────────────────────────────────

    public function test_driver_can_access_dashboard(): void
    {
        $response = $this->actingAs($this->driver)->get(route('driver.dashboard'));

        $response->assertStatus(200);
        $response->assertViewIs('driver.dashboard');
    }

    public function test_unauthenticated_user_is_redirected(): void
    {
        $response = $this->get(route('driver.dashboard'));

        $response->assertRedirect(route('login'));
    }

    public function test_driver_dashboard_passes_route_and_machines(): void
    {
        $response = $this->actingAs($this->driver)->get(route('driver.dashboard'));

        $response->assertViewHas('route');
        $response->assertViewHas('machines');
        $response->assertViewHas('todayStockings');
        $response->assertViewHas('todaySales');
        $response->assertViewHas('todaySalesAmount');
    }

    public function test_super_admin_can_select_any_route_in_dashboard(): void
    {
        $superAdmin = User::factory()->create(['is_super_admin' => true]);
        $otherRoute = Route::factory()->create();

        $response = $this->actingAs($superAdmin)
            ->get(route('driver.dashboard', ['route_id' => $otherRoute->id]));

        $response->assertStatus(200);
    }

    public function test_driver_cannot_select_different_route(): void
    {
        $otherRoute = Route::factory()->create();

        // Conductor passes another route_id — must resolve to their own route
        $response = $this->actingAs($this->driver)
            ->get(route('driver.dashboard', ['route_id' => $otherRoute->id]));

        $response->assertStatus(200);
        $viewRoute = $response->viewData('route');
        $this->assertSame($this->route->id, $viewRoute?->id);
    }

    public function test_dashboard_shows_today_stockings_count(): void
    {
        $response = $this->actingAs($this->driver)->get(route('driver.dashboard'));

        $response->assertViewHas('todayStockings', 0);
    }

    // ──────────────────────────────────────────────────────────────
    // STOCKING VIEW
    // ──────────────────────────────────────────────────────────────

    public function test_driver_can_access_stocking_form(): void
    {
        $response = $this->actingAs($this->driver)->get(route('driver.stocking.create'));

        $response->assertStatus(200);
        $response->assertViewIs('driver.stocking.create');
    }

    public function test_stocking_form_shows_vehicle_stocks(): void
    {
        $product = Product::factory()->create();
        Stock::factory()->forWarehouse($this->vehicleWarehouse)->forProduct($product)->withQuantity(5)->create();

        $response = $this->actingAs($this->driver)->get(route('driver.stocking.create'));

        $response->assertViewHas('products');
        $response->assertViewHas('vehicleWarehouse');
    }

    // ──────────────────────────────────────────────────────────────
    // STORE STOCKING — HAPPY PATH
    // ──────────────────────────────────────────────────────────────

    public function test_driver_can_register_stocking(): void
    {
        $this->driver->givePermissionTo('stockings.create');

        $product = Product::factory()->create();
        Stock::factory()->forWarehouse($this->vehicleWarehouse)->forProduct($product)->withQuantity(10)->create();

        $response = $this->actingAs($this->driver)->post(route('driver.stocking.store'), [
            'route_id'   => $this->route->id,
            'machine_id' => $this->machine->id,
            'items'      => [$product->id => ['quantity' => 3]],
        ]);

        $response->assertRedirect(route('driver.dashboard', ['route_id' => $this->route->id]));
        $response->assertSessionHas('success');
    }

    // ──────────────────────────────────────────────────────────────
    // STORE STOCKING — ERRORES
    // ──────────────────────────────────────────────────────────────

    public function test_stocking_fails_with_invalid_route(): void
    {
        $this->driver->givePermissionTo('stockings.create');

        $product = Product::factory()->create();
        Stock::factory()->forWarehouse($this->vehicleWarehouse)->forProduct($product)->withQuantity(10)->create();

        $response = $this->actingAs($this->driver)->post(route('driver.stocking.store'), [
            'route_id'   => 99999, // no existe
            'machine_id' => $this->machine->id,
            'items'      => [$product->id => ['quantity' => 3]],
        ]);

        $response->assertSessionHas('error');
    }

    public function test_stocking_fails_when_all_items_are_zero(): void
    {
        $this->driver->givePermissionTo('stockings.create');

        $product = Product::factory()->create();
        Stock::factory()->forWarehouse($this->vehicleWarehouse)->forProduct($product)->withQuantity(10)->create();

        $response = $this->actingAs($this->driver)->post(route('driver.stocking.store'), [
            'route_id'   => $this->route->id,
            'machine_id' => $this->machine->id,
            'items'      => [$product->id => ['quantity' => 0]],
        ]);

        $response->assertSessionHas('error');
    }

    public function test_stocking_fails_when_vehicle_stock_insufficient(): void
    {
        $this->driver->givePermissionTo('stockings.create');

        $product = Product::factory()->create();
        Stock::factory()->forWarehouse($this->vehicleWarehouse)->forProduct($product)->withQuantity(2)->create();

        $response = $this->actingAs($this->driver)->post(route('driver.stocking.store'), [
            'route_id'   => $this->route->id,
            'machine_id' => $this->machine->id,
            'items'      => [$product->id => ['quantity' => 10]],
        ]);

        $response->assertSessionHas('error');
    }

    public function test_stocking_fails_when_machine_not_in_route(): void
    {
        $this->driver->givePermissionTo('stockings.create');

        $otherMachine = Machine::factory()->create(); // sin route_id

        $response = $this->actingAs($this->driver)->post(route('driver.stocking.store'), [
            'route_id'   => $this->route->id,
            'machine_id' => $otherMachine->id,
            'items'      => [1 => ['quantity' => 1]],
        ]);

        $response->assertStatus(404); // firstOrFail lanza 404
    }

    // ──────────────────────────────────────────────────────────────
    // SALES VIEW
    // ──────────────────────────────────────────────────────────────

    public function test_driver_can_access_sales_form(): void
    {
        $response = $this->actingAs($this->driver)->get(route('driver.sales.create'));

        $response->assertStatus(200);
        $response->assertViewIs('driver.sales.create');
    }

    // ──────────────────────────────────────────────────────────────
    // STORE SALE — HAPPY PATH
    // ──────────────────────────────────────────────────────────────

    public function test_driver_can_register_sale(): void
    {
        $this->driver->givePermissionTo('sales.create');

        $product = Product::factory()->create();
        Stock::factory()->forWarehouse($this->machineWarehouse)->forProduct($product)->withQuantity(10)->create();

        $response = $this->actingAs($this->driver)->post(route('driver.sales.store'), [
            'route_id'   => $this->route->id,
            'machine_id' => $this->machine->id,
            'items'      => [$product->id => ['quantity' => 2, 'unit_price' => 3500]],
        ]);

        $response->assertRedirect(route('driver.dashboard', ['route_id' => $this->route->id]));
        $response->assertSessionHas('success');
    }

    // ──────────────────────────────────────────────────────────────
    // STORE SALE — ERRORES
    // ──────────────────────────────────────────────────────────────

    public function test_sale_fails_with_invalid_route(): void
    {
        $this->driver->givePermissionTo('sales.create');

        $response = $this->actingAs($this->driver)->post(route('driver.sales.store'), [
            'route_id'   => 99999,
            'machine_id' => $this->machine->id,
            'items'      => [1 => ['quantity' => 1, 'unit_price' => 1000]],
        ]);

        $response->assertSessionHas('error');
    }

    public function test_sale_fails_when_all_items_zero(): void
    {
        $this->driver->givePermissionTo('sales.create');

        $product = Product::factory()->create();

        $response = $this->actingAs($this->driver)->post(route('driver.sales.store'), [
            'route_id'   => $this->route->id,
            'machine_id' => $this->machine->id,
            'items'      => [$product->id => ['quantity' => 0, 'unit_price' => 1000]],
        ]);

        $response->assertSessionHas('error');
    }

    public function test_sale_fails_when_machine_stock_insufficient(): void
    {
        $this->driver->givePermissionTo('sales.create');

        $product = Product::factory()->create();
        Stock::factory()->forWarehouse($this->machineWarehouse)->forProduct($product)->withQuantity(1)->create();

        $response = $this->actingAs($this->driver)->post(route('driver.sales.store'), [
            'route_id'   => $this->route->id,
            'machine_id' => $this->machine->id,
            'items'      => [$product->id => ['quantity' => 5, 'unit_price' => 1000]],
        ]);

        $response->assertSessionHas('error');
    }

    // ──────────────────────────────────────────────────────────────
    // VEHICLE INVENTORY
    // ──────────────────────────────────────────────────────────────

    public function test_driver_can_access_vehicle_inventory(): void
    {
        $response = $this->actingAs($this->driver)->get(route('driver.inventory'));

        $response->assertStatus(200);
        $response->assertViewIs('driver.vehicle-inventory');
    }

    public function test_vehicle_inventory_shows_only_positive_stock(): void
    {
        $productA = Product::factory()->create(['name' => 'Con Stock']);
        $productB = Product::factory()->create(['name' => 'Sin Stock']);

        Stock::factory()->forWarehouse($this->vehicleWarehouse)->forProduct($productA)->withQuantity(5)->create();
        Stock::factory()->forWarehouse($this->vehicleWarehouse)->forProduct($productB)->withQuantity(0)->create();

        $response = $this->actingAs($this->driver)->get(route('driver.inventory'));

        $stocks = $response->viewData('stocks');
        $this->assertCount(1, $stocks);
        $this->assertSame('Con Stock', $stocks->first()->product->name);
    }

    public function test_vehicle_inventory_empty_when_no_warehouse(): void
    {
        // Driver with tenant but no route → no vehicle warehouse → empty inventory
        $driverNoRoute = User::factory()->create([
            'is_super_admin' => false,
            'tenant_id'      => $this->tenant->id,
        ]);
        $driverNoRoute->syncRoles(['conductor']);

        $response = $this->actingAs($driverNoRoute)->get(route('driver.inventory'));

        $response->assertStatus(200);
        $response->assertViewHas('stocks');
        $stocks = $response->viewData('stocks');
        $this->assertCount(0, $stocks);
    }

    // ──────────────────────────────────────────────────────────────
    // PRIVATE HELPERS
    // ──────────────────────────────────────────────────────────────

    private function seedModules(): void
    {
        $modules = [
            ['key' => 'dashboard', 'name' => 'Dashboard',    'phase_required' => 1],
            ['key' => 'drivers',   'name' => 'Conductores',  'phase_required' => 1],
            ['key' => 'inventory', 'name' => 'Inventario',   'phase_required' => 1],
            ['key' => 'sales',     'name' => 'Ventas',       'phase_required' => 2],
        ];

        foreach ($modules as $data) {
            AppModule::firstOrCreate(
                ['key' => $data['key']],
                ['name' => $data['name'], 'phase_required' => $data['phase_required'], 'is_active' => true]
            );
        }
    }
}
