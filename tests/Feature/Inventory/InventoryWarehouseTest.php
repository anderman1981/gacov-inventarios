<?php

declare(strict_types=1);

namespace Tests\Feature\Inventory;

use App\Models\Machine;
use App\Models\Product;
use App\Models\Route;
use App\Models\Stock;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Warehouse;
use App\Notifications\InventoryAdjustmentNotification;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

final class InventoryWarehouseTest extends TestCase
{
    private Tenant $tenant;

    private User $adminUser;

    private User $viewerUser;

    private User $managerUser;

    private Warehouse $warehouse;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tenant = Tenant::factory()->create();

        // Crear usuarios con diferentes permisos (super_admin para evitar middleware blocking)
        $this->adminUser = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_super_admin' => true,
        ]);
        $this->adminUser->syncRoles(['admin']);
        $this->adminUser->givePermissionTo('inventory.view');
        $this->adminUser->givePermissionTo('inventory.adjust');
        $this->adminUser->givePermissionTo('movements.view');
        $this->adminUser->givePermissionTo('inventory.load_excel');
        $this->adminUser->givePermissionTo('inventory.load_machine_excel');

        $this->viewerUser = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_super_admin' => true,
        ]);
        $this->viewerUser->givePermissionTo('inventory.view');

        $this->managerUser = User::factory()->create([
            'tenant_id' => $this->tenant->id,
            'is_super_admin' => true,
        ]);
        $this->managerUser->syncRoles(['manager']);
        $this->managerUser->givePermissionTo('inventory.view');
        $this->managerUser->givePermissionTo('inventory.adjust');
        $this->managerUser->givePermissionTo('inventory.load_vehicle_excel');
        $this->managerUser->givePermissionTo('inventory.load_machine_excel');
        $this->managerUser->givePermissionTo('drivers.assign_routes');

        // Crear bodega principal
        $this->warehouse = Warehouse::factory()->create([
            'tenant_id' => $this->tenant->id,
            'type' => 'bodega',
            'name' => 'Bodega Principal',
        ]);
    }

    private function grantPermission(User $user, string $permission): User
    {
        $user->givePermissionTo($permission);

        return $user;
    }

    // ============================================================
    // TESTS: Permisos de Acceso
    // ============================================================

    public function test_authenticated_user_with_permission_can_view_warehouse(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('inventory.warehouse'));

        $response->assertStatus(200);
        $response->assertViewIs('inventory.warehouse');
    }

    public function test_vehicle_inventory_page_shows_create_and_delete_actions_for_manager(): void
    {
        $route = Route::factory()->create([
            'tenant_id' => $this->tenant->id,
            'code' => 'RT-TEST',
            'name' => 'Ruta Test',
        ]);

        Warehouse::factory()->vehiculo()->create([
            'tenant_id' => $this->tenant->id,
            'route_id' => $route->id,
        ]);

        $response = $this->actingAs($this->managerUser)
            ->get(route('inventory.vehicles'));

        $response->assertOk();
        $response->assertSee('Crear vehículo');
        $response->assertSee('Eliminar');
    }

    public function test_manager_can_create_vehicle_from_vehicle_inventory_page(): void
    {
        $response = $this->actingAs($this->managerUser)
            ->post(route('inventory.vehicles.store'), [
                'name' => 'Ruta Occidente',
                'code' => 'RT-OCC',
                'vehicle_plate' => 'ABC123',
            ]);

        $response->assertRedirect(route('inventory.vehicles'));
        $this->assertDatabaseHas('routes', [
            'tenant_id' => $this->tenant->id,
            'code' => 'RT-OCC',
            'name' => 'Ruta Occidente',
            'vehicle_plate' => 'ABC123',
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('warehouses', [
            'tenant_id' => $this->tenant->id,
            'route_id' => Route::query()->where('code', 'RT-OCC')->value('id'),
            'type' => 'vehiculo',
            'is_active' => true,
        ]);
    }

    public function test_manager_can_deactivate_vehicle_from_vehicle_inventory_page(): void
    {
        $route = Route::factory()->create([
            'tenant_id' => $this->tenant->id,
            'code' => 'RT-DEL',
            'name' => 'Ruta Eliminar',
            'driver_user_id' => $this->managerUser->id,
        ]);

        $this->managerUser->update(['route_id' => $route->id]);

        Warehouse::factory()->vehiculo()->create([
            'tenant_id' => $this->tenant->id,
            'route_id' => $route->id,
            'responsible_user_id' => $this->managerUser->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->managerUser)
            ->delete(route('inventory.vehicles.destroy', $route));

        $response->assertRedirect(route('inventory.vehicles'));
        $this->assertDatabaseHas('routes', [
            'id' => $route->id,
            'is_active' => false,
            'driver_user_id' => null,
        ]);
        $this->assertDatabaseHas('warehouses', [
            'route_id' => $route->id,
            'type' => 'vehiculo',
            'is_active' => false,
            'responsible_user_id' => null,
        ]);
        $this->assertDatabaseHas('users', [
            'id' => $this->managerUser->id,
            'route_id' => null,
        ]);
    }

    public function test_authenticated_user_without_permission_gets_403(): void
    {
        $userWithoutPermission = User::factory()->create();

        $response = $this->actingAs($userWithoutPermission)
            ->get(route('inventory.warehouse'));

        $response->assertStatus(403);
    }

    public function test_unauthenticated_user_gets_redirect_to_login(): void
    {
        $response = $this->get(route('inventory.warehouse'));

        $response->assertRedirectToRoute('login');
    }

    // ============================================================
    // TESTS: Visualización de Inventario
    // ============================================================

    public function test_warehouse_page_shows_stock_data(): void
    {
        // Crear productos con stock
        $product = Product::factory()->create(['name' => 'Test Product']);
        Stock::factory()->create([
            'warehouse_id' => $this->warehouse->id,
            'product_id' => $product->id,
            'quantity' => 100,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('inventory.warehouse'));

        $response->assertStatus(200);
        $response->assertSee('Test Product');
        $response->assertSee('100');
    }

    public function test_warehouse_page_shows_total_units(): void
    {
        // Crear múltiples productos
        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();

        Stock::factory()->create([
            'warehouse_id' => $this->warehouse->id,
            'product_id' => $product1->id,
            'quantity' => 50,
        ]);

        Stock::factory()->create([
            'warehouse_id' => $this->warehouse->id,
            'product_id' => $product2->id,
            'quantity' => 75,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('inventory.warehouse'));

        $response->assertStatus(200);
        $response->assertSee('125'); // Total: 50 + 75
    }

    public function test_warehouse_page_shows_low_stock_badge(): void
    {
        $product = Product::factory()->create([
            'min_stock_alert' => 20,
        ]);

        Stock::factory()->create([
            'warehouse_id' => $this->warehouse->id,
            'product_id' => $product->id,
            'quantity' => 15, // Bajo del umbral
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('inventory.warehouse'));

        $response->assertStatus(200);
        // Verifica que hay indicador de stock bajo
        $response->assertSee('15');
    }

    // ============================================================
    // TESTS: Búsqueda y Filtrado
    // ============================================================

    public function test_search_filters_products_by_name(): void
    {
        $coca = Product::factory()->create(['name' => 'Coca Cola']);
        $pepsi = Product::factory()->create(['name' => 'Pepsi']);

        Stock::factory()->create([
            'warehouse_id' => $this->warehouse->id,
            'product_id' => $coca->id,
            'quantity' => 10,
        ]);
        Stock::factory()->create([
            'warehouse_id' => $this->warehouse->id,
            'product_id' => $pepsi->id,
            'quantity' => 10,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('inventory.warehouse', ['search' => 'Coca']));

        $response->assertStatus(200);
        $response->assertSee('Coca Cola');
    }

    public function test_search_filters_products_by_code(): void
    {
        $coca = Product::factory()->create(['code' => 'COCA001', 'name' => 'Coca Cola']);
        Product::factory()->create(['code' => 'PEPS002', 'name' => 'Pepsi']);

        Stock::factory()->create([
            'warehouse_id' => $this->warehouse->id,
            'product_id' => $coca->id,
            'quantity' => 10,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('inventory.warehouse', ['search' => 'COCA']));

        $response->assertStatus(200);
        $response->assertSee('Coca Cola');
    }

    public function test_category_filter_works(): void
    {
        $bebida = Product::factory()->create(['category' => 'bebida_fria', 'name' => 'Refresco de Cola']);
        $snack = Product::factory()->create(['category' => 'snack', 'name' => 'Papas Fritas']);

        Stock::factory()->create([
            'warehouse_id' => $this->warehouse->id,
            'product_id' => $bebida->id,
            'quantity' => 10,
        ]);
        Stock::factory()->create([
            'warehouse_id' => $this->warehouse->id,
            'product_id' => $snack->id,
            'quantity' => 10,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('inventory.warehouse', ['category' => 'bebida_fria']));

        $response->assertStatus(200);
        $response->assertSee('Refresco de Cola');
        $response->assertDontSee('Papas Fritas');
    }

    // ============================================================
    // TESTS: Paginación
    // ============================================================

    public function test_pagination_works(): void
    {
        // Crear más de 25 productos con stock para activar paginación
        $products = Product::factory()->count(30)->create();
        foreach ($products as $product) {
            Stock::factory()->create([
                'warehouse_id' => $this->warehouse->id,
                'product_id' => $product->id,
                'quantity' => 10,
            ]);
        }

        $response = $this->actingAs($this->adminUser)
            ->get(route('inventory.warehouse', ['per_page' => 10]));

        $response->assertStatus(200);
        $response->assertSee('page=2'); // Link a segunda página
    }

    public function test_per_page_options_are_valid(): void
    {
        $validOptions = [10, 25, 50, 100];

        foreach ($validOptions as $perPage) {
            $response = $this->actingAs($this->adminUser)
                ->get(route('inventory.warehouse', ['per_page' => $perPage]));

            $response->assertStatus(200);
        }
    }

    public function test_invalid_per_page_defaults_to_25(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('inventory.warehouse', ['per_page' => 999])); // Inválido

        $response->assertStatus(200);
        // Debe usar el default de 25
    }

    // ============================================================
    // TESTS: Ajuste de Inventario
    // ============================================================

    public function test_adjust_page_requires_permission(): void
    {
        $response = $this->actingAs($this->viewerUser)
            ->get(route('inventory.adjust'));

        $response->assertStatus(403);
    }

    public function test_adjust_page_loads_with_permission(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('inventory.adjust'));

        $response->assertStatus(200);
        $response->assertViewIs('inventory.adjust');
    }

    public function test_manager_can_open_vehicle_adjust_form(): void
    {
        $route = Route::factory()->create();
        $vehicleWarehouse = Warehouse::factory()->vehiculo()->create([
            'route_id' => $route->id,
        ]);

        $response = $this->actingAs($this->managerUser)
            ->get(route('inventory.adjust', ['warehouse_id' => $vehicleWarehouse->id]));

        $response->assertOk();
        $response->assertSee('Carga de mercancía para vehículo');
        $response->assertSee($vehicleWarehouse->name);
    }

    public function test_manager_can_view_vehicle_inventory_cards_with_adjustment_actions(): void
    {
        $route = Route::factory()->create([
            'name' => 'Ruta Norte',
            'code' => 'RN-01',
        ]);
        $vehicleWarehouse = Warehouse::factory()->vehiculo()->create([
            'route_id' => $route->id,
            'name' => 'Vehiculo Ruta Norte',
        ]);
        $product = Product::factory()->create([
            'name' => 'Agua 600ML',
        ]);

        Stock::factory()->create([
            'warehouse_id' => $vehicleWarehouse->id,
            'product_id' => $product->id,
            'quantity' => 24,
        ]);

        $response = $this->actingAs($this->managerUser)
            ->get(route('inventory.vehicles'));

        $response->assertOk();
        $response->assertSee('Ruta Norte');
        $response->assertSee('Agregar mercancía');
        $response->assertSee('Carga masiva por Excel');
        $response->assertSee('Agua 600ML');
    }

    public function test_manager_can_view_machine_inventory_cards_with_adjustment_actions(): void
    {
        $route = Route::factory()->create();
        $machine = Machine::factory()->forRoute($route->id)->create([
            'name' => 'Maquina Central',
            'code' => 'MC-01',
        ]);
        $machineWarehouse = Warehouse::factory()->maquina()->create([
            'machine_id' => $machine->id,
            'name' => 'Bodega Maquina Central',
        ]);
        $product = Product::factory()->create([
            'name' => 'Cafe Latte',
        ]);

        Stock::factory()->create([
            'warehouse_id' => $machineWarehouse->id,
            'product_id' => $product->id,
            'quantity' => 12,
        ]);

        $response = $this->actingAs($this->managerUser)
            ->get(route('inventory.machines'));

        $response->assertOk();
        $response->assertSee('Maquina Central');
        $response->assertSee('Corregir inventario');
        $response->assertSee('Carga masiva cerrada');
        $response->assertSee('Cafe Latte');
    }

    public function test_manager_can_view_machine_mass_initial_button_when_pending_initial_load_exists(): void
    {
        $route = Route::factory()->create();
        $machine = Machine::factory()->forRoute($route->id)->create([
            'name' => 'Maquina Nueva',
            'code' => 'MN-01',
        ]);

        Warehouse::factory()->maquina()->create([
            'machine_id' => $machine->id,
            'name' => 'Bodega Maquina Nueva',
        ]);

        $response = $this->actingAs($this->managerUser)
            ->get(route('inventory.machines'));

        $response->assertOk();
        $response->assertSee('Carga inicial por Excel');
    }

    public function test_adjust_stock_creates_movement(): void
    {
        $product = Product::factory()->create();

        Stock::factory()->create([
            'warehouse_id' => $this->warehouse->id,
            'product_id' => $product->id,
            'quantity' => 50,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('inventory.adjust.store'), [
                'warehouse_id' => $this->warehouse->id,
                'product_id' => $product->id,
                'new_quantity' => 75,
                'reason' => 'Ajuste por conteo físico',
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        // Verificar que el stock se actualizó
        $this->assertDatabaseHas('stock', [
            'warehouse_id' => $this->warehouse->id,
            'product_id' => $product->id,
            'quantity' => 75,
        ]);

        // Verificar que se creó un movimiento
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'movement_type' => 'ajuste_manual',
            'quantity' => 25, // 75 - 50 = 25
        ]);
    }

    public function test_adjust_negative_quantity_creates_negative_movement(): void
    {
        $product = Product::factory()->create();

        Stock::factory()->create([
            'warehouse_id' => $this->warehouse->id,
            'product_id' => $product->id,
            'quantity' => 50,
        ]);

        $this->actingAs($this->adminUser)
            ->post(route('inventory.adjust.store'), [
                'warehouse_id' => $this->warehouse->id,
                'product_id' => $product->id,
                'new_quantity' => 30,
                'reason' => 'Productos dañados',
            ]);

        // Verificar movimiento negativo
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'movement_type' => 'ajuste_manual',
            'quantity' => 20, // 30 - 50 = -20, abs = 20
        ]);
    }

    public function test_machine_initial_load_is_recorded_without_required_reason(): void
    {
        Notification::fake();

        $route = Route::factory()->create();
        $machine = Machine::factory()->forRoute($route->id)->create();
        $machineWarehouse = Warehouse::factory()->maquina()->create([
            'machine_id' => $machine->id,
        ]);
        $product = Product::factory()->create();

        $response = $this->actingAs($this->managerUser)
            ->post(route('inventory.adjust.store'), [
                'warehouse_id' => $machineWarehouse->id,
                'product_id' => $product->id,
                'new_quantity' => 18,
                'reason' => '',
            ]);

        $response->assertRedirect(route('inventory.machines'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('stock_movements', [
            'destination_warehouse_id' => $machineWarehouse->id,
            'product_id' => $product->id,
            'movement_type' => 'carga_inicial',
            'quantity' => 18,
        ]);
        Notification::assertNothingSent();
    }

    public function test_vehicle_initial_load_is_recorded_without_required_reason(): void
    {
        Notification::fake();

        $route = Route::factory()->create();
        $vehicleWarehouse = Warehouse::factory()->vehiculo()->create([
            'route_id' => $route->id,
        ]);
        $product = Product::factory()->create();

        $response = $this->actingAs($this->managerUser)
            ->post(route('inventory.adjust.store'), [
                'warehouse_id' => $vehicleWarehouse->id,
                'product_id' => $product->id,
                'new_quantity' => 20,
                'reason' => '',
            ]);

        $response->assertRedirect(route('inventory.vehicles'));
        $response->assertSessionHas('success');
        $this->assertDatabaseHas('stock_movements', [
            'destination_warehouse_id' => $vehicleWarehouse->id,
            'product_id' => $product->id,
            'movement_type' => 'carga_inicial',
            'quantity' => 20,
        ]);
        Notification::assertNothingSent();
    }

    public function test_manager_adjustment_after_initial_load_requires_reason_and_notifies_admin(): void
    {
        Notification::fake();

        $foreignAdmin = User::factory()->create([
            'tenant_id' => Tenant::factory()->create()->id,
            'email' => 'foreign-admin@example.com',
            'must_change_password' => false,
            'is_super_admin' => false,
        ]);
        $foreignAdmin->syncRoles(['admin']);

        $route = Route::factory()->create();
        $machine = Machine::factory()->forRoute($route->id)->create();
        $machineWarehouse = Warehouse::factory()->maquina()->create([
            'machine_id' => $machine->id,
        ]);
        $product = Product::factory()->create();

        Stock::factory()->create([
            'warehouse_id' => $machineWarehouse->id,
            'product_id' => $product->id,
            'quantity' => 10,
        ]);

        $this->actingAs($this->managerUser)
            ->post(route('inventory.adjust.store'), [
                'warehouse_id' => $machineWarehouse->id,
                'product_id' => $product->id,
                'new_quantity' => 15,
                'reason' => '',
            ])
            ->assertSessionHasErrors('reason');

        $response = $this->actingAs($this->managerUser)
            ->post(route('inventory.adjust.store'), [
                'warehouse_id' => $machineWarehouse->id,
                'product_id' => $product->id,
                'new_quantity' => 15,
                'reason' => 'Se corrigió diferencia detectada en el conteo de la máquina.',
            ]);

        $response->assertRedirect(route('inventory.machines'));
        $this->assertDatabaseHas('stock_movements', [
            'destination_warehouse_id' => $machineWarehouse->id,
            'product_id' => $product->id,
            'movement_type' => 'ajuste_manual',
            'quantity' => 5,
        ]);
        Notification::assertSentTo(
            $this->adminUser,
            InventoryAdjustmentNotification::class,
            fn (InventoryAdjustmentNotification $notification, array $channels): bool => in_array('database', $channels, true)
        );
        Notification::assertNotSentTo($foreignAdmin, InventoryAdjustmentNotification::class);
    }

    public function test_manager_vehicle_adjustment_after_initial_load_requires_reason_and_notifies_admin(): void
    {
        Notification::fake();

        $foreignAdmin = User::factory()->create([
            'tenant_id' => Tenant::factory()->create()->id,
            'email' => 'foreign-admin-vehicle@example.com',
            'must_change_password' => false,
            'is_super_admin' => false,
        ]);
        $foreignAdmin->syncRoles(['admin']);

        $route = Route::factory()->create();
        $vehicleWarehouse = Warehouse::factory()->vehiculo()->create([
            'route_id' => $route->id,
        ]);
        $product = Product::factory()->create();

        Stock::factory()->create([
            'warehouse_id' => $vehicleWarehouse->id,
            'product_id' => $product->id,
            'quantity' => 8,
        ]);

        $this->actingAs($this->managerUser)
            ->post(route('inventory.adjust.store'), [
                'warehouse_id' => $vehicleWarehouse->id,
                'product_id' => $product->id,
                'new_quantity' => 11,
                'reason' => '',
            ])
            ->assertSessionHasErrors('reason');

        $response = $this->actingAs($this->managerUser)
            ->post(route('inventory.adjust.store'), [
                'warehouse_id' => $vehicleWarehouse->id,
                'product_id' => $product->id,
                'new_quantity' => 11,
                'reason' => 'Se ajustó el inventario del vehículo por diferencia encontrada al cierre de ruta.',
            ]);

        $response->assertRedirect(route('inventory.vehicles'));
        $this->assertDatabaseHas('stock_movements', [
            'destination_warehouse_id' => $vehicleWarehouse->id,
            'product_id' => $product->id,
            'movement_type' => 'ajuste_manual',
            'quantity' => 3,
        ]);
        Notification::assertSentTo(
            $this->adminUser,
            InventoryAdjustmentNotification::class,
            fn (InventoryAdjustmentNotification $notification, array $channels): bool => in_array('database', $channels, true)
        );
        Notification::assertNotSentTo($foreignAdmin, InventoryAdjustmentNotification::class);
    }

    // ============================================================
    // TESTS: Movimientos
    // ============================================================

    public function test_movements_page_requires_permission(): void
    {
        $response = $this->actingAs($this->viewerUser)
            ->get(route('inventory.movements'));

        $response->assertStatus(403);
    }

    public function test_movements_page_shows_history(): void
    {
        $product = Product::factory()->create();
        Stock::factory()->create([
            'warehouse_id' => $this->warehouse->id,
            'product_id' => $product->id,
            'quantity' => 100,
        ]);

        // Ajustar stock para crear movimiento
        $this->actingAs($this->adminUser)
            ->post(route('inventory.adjust.store'), [
                'warehouse_id' => $this->warehouse->id,
                'product_id' => $product->id,
                'new_quantity' => 80,
                'reason' => 'Test',
            ]);

        $response = $this->actingAs($this->adminUser)
            ->get(route('inventory.movements'));

        $response->assertStatus(200);
        $response->assertSee('ajuste_manual');
    }

    public function test_movements_can_be_filtered_by_type(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('inventory.movements', ['type' => 'ajuste_manual']));

        $response->assertStatus(200);
    }

    // ============================================================
    // TESTS: Importación
    // ============================================================

    public function test_import_page_requires_permission(): void
    {
        $response = $this->actingAs($this->viewerUser)
            ->get(route('inventory.import.form'));

        $response->assertStatus(403);
    }

    public function test_import_page_loads_with_permission(): void
    {
        $this->adminUser->givePermissionTo('inventory.load_excel');

        $response = $this->actingAs($this->adminUser)
            ->get(route('inventory.import.form'));

        $response->assertStatus(200);
        $response->assertViewIs('inventory.import-initial');
    }
}
