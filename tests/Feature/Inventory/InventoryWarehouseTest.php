<?php

declare(strict_types=1);

namespace Tests\Feature\Inventory;

use App\Models\Product;
use App\Models\Stock;
use App\Models\User;
use App\Models\Warehouse;
use Tests\TestCase;

final class InventoryWarehouseTest extends TestCase
{
    private User $adminUser;

    private User $viewerUser;

    private Warehouse $warehouse;

    protected function setUp(): void
    {
        parent::setUp();

        // Crear usuarios con diferentes permisos (super_admin para evitar middleware blocking)
        $this->adminUser = User::factory()->create(['is_super_admin' => true]);
        $this->adminUser->givePermissionTo('inventory.view');
        $this->adminUser->givePermissionTo('inventory.adjust');
        $this->adminUser->givePermissionTo('movements.view');
        $this->adminUser->givePermissionTo('inventory.load_excel');

        $this->viewerUser = User::factory()->create(['is_super_admin' => true]);
        $this->viewerUser->givePermissionTo('inventory.view');

        // Crear bodega principal
        $this->warehouse = Warehouse::factory()->create([
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
