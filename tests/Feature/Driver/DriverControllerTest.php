<?php

declare(strict_types=1);

namespace Tests\Feature\Driver;

use App\Domain\Tenant\Services\TenantContext;
use App\Models\AppModule;
use App\Models\Machine;
use App\Models\MachineStockingRecord;
use App\Models\MachineSaleItem;
use App\Models\Product;
use App\Models\Route;
use App\Models\RouteScheduleAssignment;
use App\Models\Stock;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Support\Carbon;
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
        app(TenantContext::class)->setTenant($this->tenant);
        Subscription::factory()->create([
            'tenant_id' => $this->tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'current_period_end' => now()->addMonth(),
        ]);

        $this->driver = User::factory()->create([
            'is_super_admin' => false,
            'tenant_id' => $this->tenant->id,
        ]);
        $this->driver->syncRoles(['conductor']);

        $this->route = Route::factory()->withDriver($this->driver->id)->create();
        $this->driver->update(['route_id' => $this->route->id]);
        $this->driver->load('route'); // Ensure route relation is loaded

        $this->machine = Machine::factory()->forRoute($this->route->id)->create([
            'location' => 'Universidad - Bloque A',
        ]);

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

    public function test_driver_can_select_second_route_scheduled_for_today(): void
    {
        Carbon::setTestNow('2026-04-14 08:30:00');

        $backupDriver = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_super_admin' => false,
        ]);
        $backupDriver->syncRoles(['conductor']);

        $otherRoute = Route::factory()->withDriver($backupDriver->id)->create();
        $backupDriver->update(['route_id' => $otherRoute->id]);

        Warehouse::factory()->vehiculo()->create([
            'route_id' => $otherRoute->id,
        ]);

        Machine::factory()->forRoute($otherRoute->id)->create([
            'location' => 'Clínica - Torre Norte',
        ]);

        RouteScheduleAssignment::query()->create([
            'tenant_id' => $this->tenant->id,
            'route_id' => $otherRoute->id,
            'assignment_date' => today()->toDateString(),
            'driver_user_id' => $this->driver->id,
            'assigned_by_user_id' => $this->driver->id,
        ]);

        $response = $this->actingAs($this->driver)
            ->get(route('driver.dashboard', ['route_id' => $otherRoute->id]));

        $response->assertStatus(200);
        $viewRoute = $response->viewData('route');
        $availableRoutes = $response->viewData('availableRoutes');

        $this->assertSame($otherRoute->id, $viewRoute?->id);
        $this->assertNotNull($availableRoutes);
        $this->assertCount(2, $availableRoutes);
        $this->assertTrue($availableRoutes->pluck('id')->contains($this->route->id));
        $this->assertTrue($availableRoutes->pluck('id')->contains($otherRoute->id));

        Carbon::setTestNow();
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
        $response->assertDontSee('Carga por foto para surtido');
        $response->assertSee('Fase 1 — Inspección de máquina.');
        $response->assertSee('Ubicación de la máquina');
        $response->assertSee('Productos a surtir');
    }

    public function test_stocking_form_groups_machine_selection_by_location(): void
    {
        Machine::factory()->forRoute($this->route->id)->create([
            'location' => 'Clínica - Torre Norte',
        ]);

        $response = $this->actingAs($this->driver)->get(route('driver.stocking.create'));

        $response->assertStatus(200);
        $response->assertSee('Seleccionar ubicación...');
        $response->assertSee('Universidad - Bloque A');
        $response->assertSee('Clínica - Torre Norte');
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
            'route_id' => $this->route->id,
            'machine_id' => $this->machine->id,
            'items' => [$product->id => ['quantity' => 3]],
        ]);

        $record = MachineStockingRecord::query()->latest('id')->firstOrFail();

        $response->assertRedirect(route('driver.stocking.loading', $record));
        $response->assertSessionHas('success');
    }

    public function test_driver_can_open_stocking_loading_view(): void
    {
        $record = MachineStockingRecord::query()->create([
            'tenant_id' => $this->tenant->id,
            'code' => 'STK-001',
            'machine_id' => $this->machine->id,
            'route_id' => $this->route->id,
            'vehicle_warehouse_id' => $this->vehicleWarehouse->id,
            'performed_by' => $this->driver->id,
            'status' => MachineStockingRecord::STATUS_PENDIENTE_CARGA,
            'notes' => null,
            'started_at' => now(),
            'was_offline' => false,
        ]);

        $response = $this->actingAs($this->driver)->get(route('driver.stocking.loading', $record));

        $response->assertStatus(200);
        $response->assertViewIs('driver.stocking.loading');
        $response->assertSee('Cargar el vehículo');
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
            'route_id' => 99999, // no existe
            'machine_id' => $this->machine->id,
            'items' => [$product->id => ['quantity' => 3]],
        ]);

        $response->assertSessionHasErrors('route_id');
    }

    public function test_stocking_fails_when_all_items_are_zero(): void
    {
        $this->driver->givePermissionTo('stockings.create');

        $product = Product::factory()->create();
        Stock::factory()->forWarehouse($this->vehicleWarehouse)->forProduct($product)->withQuantity(10)->create();

        $response = $this->actingAs($this->driver)->post(route('driver.stocking.store'), [
            'route_id' => $this->route->id,
            'machine_id' => $this->machine->id,
            'items' => [$product->id => ['quantity' => 0]],
        ]);

        $response->assertSessionHas('error');
    }

    public function test_stocking_fails_when_vehicle_stock_insufficient(): void
    {
        $this->driver->givePermissionTo('stockings.create');

        $product = Product::factory()->create();
        Stock::factory()->forWarehouse($this->vehicleWarehouse)->forProduct($product)->withQuantity(2)->create();

        $response = $this->actingAs($this->driver)->post(route('driver.stocking.store'), [
            'route_id' => $this->route->id,
            'machine_id' => $this->machine->id,
            'items' => [$product->id => ['quantity' => 10]],
        ]);

        $response->assertSessionHas('error');
    }

    public function test_stocking_fails_when_machine_not_in_route(): void
    {
        $this->driver->givePermissionTo('stockings.create');

        $otherMachine = Machine::factory()->create(); // sin route_id

        $response = $this->actingAs($this->driver)->post(route('driver.stocking.store'), [
            'route_id' => $this->route->id,
            'machine_id' => $otherMachine->id,
            'items' => [1 => ['quantity' => 1]],
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

    public function test_sales_form_shows_machine_inventory_and_editable_pricing(): void
    {
        $product = Product::factory()->create([
            'code' => 'AGUA600',
            'name' => 'Agua 600ML',
            'category' => 'bebida_fria',
            'cost' => 1200,
            'unit_price' => 3500,
        ]);

        Stock::factory()->forWarehouse($this->machineWarehouse)->forProduct($product)->withQuantity(7)->create();

        $response = $this->actingAs($this->driver)->get(route('driver.sales.create', [
            'route_id' => $this->route->id,
            'machine_id' => $this->machine->id,
        ]));

        $response->assertStatus(200);
        $response->assertSee('Máquina activa');
        $response->assertSee('Stock visible');
        $response->assertSee('Agua 600ML');
        $response->assertSee('Observación');
        $response->assertSee('Precio unit. (COP)');
        $response->assertSee('7');
        $response->assertSee('$1.200');
        $response->assertSee('value="3500"', false);
    }

    public function test_sales_form_can_filter_products_with_stock_only(): void
    {
        $inStock = Product::factory()->create([
            'code' => 'STOCK01',
            'name' => 'Producto con stock',
        ]);
        $emptyStock = Product::factory()->create([
            'code' => 'STOCK00',
            'name' => 'Producto sin stock',
        ]);

        Stock::factory()->forWarehouse($this->machineWarehouse)->forProduct($inStock)->withQuantity(4)->create();
        Stock::factory()->forWarehouse($this->machineWarehouse)->forProduct($emptyStock)->withQuantity(0)->create();

        $response = $this->actingAs($this->driver)->get(route('driver.sales.create', [
            'route_id' => $this->route->id,
            'machine_id' => $this->machine->id,
            'stock_filter' => 'with_stock',
        ]));

        $response->assertOk();
        $response->assertSee('Filtro de stock');
        $response->assertSee('Con stock');
        $response->assertSee('Producto con stock');
        $response->assertDontSee('Producto sin stock');
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
            'route_id' => $this->route->id,
            'machine_id' => $this->machine->id,
            'items' => [$product->id => [
                'quantity' => 2,
                'unit_price' => 3500,
                'notes' => 'Precio ajustado por ubicación premium',
            ]],
        ]);

        $response->assertRedirect(route('driver.dashboard', ['route_id' => $this->route->id]));
        $response->assertSessionHas('success');

        $saleItem = MachineSaleItem::query()->latest('id')->firstOrFail();
        $this->assertSame(3500.0, (float) $saleItem->unit_price);
        $this->assertSame('Precio ajustado por ubicación premium', $saleItem->notes);
    }

    // ──────────────────────────────────────────────────────────────
    // STORE SALE — ERRORES
    // ──────────────────────────────────────────────────────────────

    public function test_sale_fails_with_invalid_route(): void
    {
        $this->driver->givePermissionTo('sales.create');

        $response = $this->actingAs($this->driver)->post(route('driver.sales.store'), [
            'route_id' => 99999,
            'machine_id' => $this->machine->id,
            'items' => [1 => ['quantity' => 1, 'unit_price' => 1000]],
        ]);

        $response->assertSessionHasErrors('route_id');
    }

    public function test_sale_fails_when_all_items_zero(): void
    {
        $this->driver->givePermissionTo('sales.create');

        $product = Product::factory()->create();

        $response = $this->actingAs($this->driver)->post(route('driver.sales.store'), [
            'route_id' => $this->route->id,
            'machine_id' => $this->machine->id,
            'items' => [$product->id => ['quantity' => 0, 'unit_price' => 1000, 'notes' => '']],
        ]);

        $response->assertSessionHas('error');
    }

    public function test_sale_fails_when_machine_stock_insufficient(): void
    {
        $this->driver->givePermissionTo('sales.create');

        $product = Product::factory()->create();
        Stock::factory()->forWarehouse($this->machineWarehouse)->forProduct($product)->withQuantity(1)->create();

        $response = $this->actingAs($this->driver)->post(route('driver.sales.store'), [
            'route_id' => $this->route->id,
            'machine_id' => $this->machine->id,
            'items' => [$product->id => ['quantity' => 5, 'unit_price' => 1000, 'notes' => 'Sin stock suficiente']],
        ]);

        $response->assertSessionHas('error');
    }

    // ──────────────────────────────────────────────────────────────
    // VEHICLE INVENTORY
    // ──────────────────────────────────────────────────────────────

    public function test_driver_cannot_access_vehicle_inventory(): void
    {
        $response = $this->actingAs($this->driver)->get(route('driver.inventory'));

        $response->assertForbidden();
    }

    public function test_vehicle_inventory_shows_only_positive_stock(): void
    {
        $productA = Product::factory()->create(['name' => 'Con Stock']);
        $productB = Product::factory()->create(['name' => 'Sin Stock']);

        Stock::factory()->forWarehouse($this->vehicleWarehouse)->forProduct($productA)->withQuantity(5)->create();
        Stock::factory()->forWarehouse($this->vehicleWarehouse)->forProduct($productB)->withQuantity(0)->create();

        // Act & Assert
        $response = $this->actingAs($this->driver)->get(route('driver.inventory'));
        $response->assertForbidden();
    }

    public function test_vehicle_inventory_empty_when_no_warehouse(): void
    {
        // Driver with tenant but no route → no vehicle warehouse → empty inventory
        $driverNoRoute = User::factory()->create([
            'is_super_admin' => false,
            'tenant_id' => $this->tenant->id,
        ]);
        $driverNoRoute->syncRoles(['conductor']);

        $response = $this->actingAs($driverNoRoute)->get(route('driver.inventory'));

        $response->assertForbidden();
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
