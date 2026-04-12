<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Product;
use App\Models\Stock;
use App\Models\User;
use App\Models\Warehouse;
use Tests\TestCase;

final class ProductApiTest extends TestCase
{
    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create(['is_super_admin' => true]);
        $this->adminUser->givePermissionTo('products.view');
        $this->adminUser->givePermissionTo('products.create');
        $this->adminUser->givePermissionTo('products.edit');
        $this->adminUser->givePermissionTo('products.delete');
        $this->adminUser->givePermissionTo('inventory.view');
    }

    public function test_unauthenticated_user_cannot_access_api(): void
    {
        $response = $this->getJson('/api/v1/products');

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_list_products(): void
    {
        Product::factory()->count(3)->create();

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/products');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data',
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ]);
    }

    public function test_products_can_be_filtered_by_category(): void
    {
        Product::factory()->create(['category' => 'bebida_fria']);
        Product::factory()->create(['category' => 'snack']);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/products?category=bebida_fria');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }

    public function test_products_can_be_searched(): void
    {
        Product::factory()->create(['name' => 'Coca Cola']);
        Product::factory()->create(['name' => 'Pepsi']);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/products?search=Coca');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }

    public function test_only_active_products_shown_by_default(): void
    {
        Product::factory()->create(['is_active' => true, 'name' => 'Active Product']);
        Product::factory()->create(['is_active' => false, 'name' => 'Inactive Product']);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/products');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }

    public function test_can_get_single_product(): void
    {
        $product = Product::factory()->create(['name' => 'Test Product']);

        $response = $this->actingAs($this->adminUser)
            ->getJson("/api/v1/products/{$product->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('data.name', 'Test Product');
    }

    public function test_can_create_product(): void
    {
        $data = [
            'code' => 'TEST001',
            'name' => 'New Product',
            'category' => 'bebida_fria',
            'unit_of_measure' => 'Und.',
            'unit_price' => 1500,
            'min_stock_alert' => 10,
        ];

        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/v1/products', $data);

        $response->assertStatus(201);
        $response->assertJsonPath('message', 'Producto creado correctamente.');

        $this->assertDatabaseHas('products', ['code' => 'TEST001']);
    }

    public function test_can_update_product(): void
    {
        $product = Product::factory()->create(['name' => 'Old Name']);

        $data = [
            'code' => $product->code,
            'name' => 'New Name',
            'category' => $product->category,
            'unit_of_measure' => $product->unit_of_measure,
            'unit_price' => $product->unit_price,
        ];

        $response = $this->actingAs($this->adminUser)
            ->putJson("/api/v1/products/{$product->id}", $data);

        $response->assertStatus(200);
        $response->assertJsonPath('message', 'Producto actualizado correctamente.');

        $this->assertDatabaseHas('products', ['id' => $product->id, 'name' => 'New Name']);
    }

    public function test_can_delete_product(): void
    {
        $product = Product::factory()->create();

        $response = $this->actingAs($this->adminUser)
            ->deleteJson("/api/v1/products/{$product->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('message', 'Producto desactivado correctamente.');

        $this->assertDatabaseHas('products', ['id' => $product->id, 'is_active' => false]);
    }

    public function test_cannot_delete_product_with_stock(): void
    {
        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();
        Stock::factory()->create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 10,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->deleteJson("/api/v1/products/{$product->id}");

        $response->assertStatus(422);
        $response->assertJsonPath('error', 'No se puede eliminar un producto con stock activo.');
    }

    public function test_can_list_stock(): void
    {
        $warehouse = Warehouse::factory()->create(['type' => 'bodega']);
        $product = Product::factory()->create();
        Stock::factory()->create([
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'quantity' => 50,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/stock');

        $response->assertStatus(200);
        $response->assertJsonStructure(['data', 'meta']);
    }

    public function test_can_filter_stock_by_warehouse(): void
    {
        $warehouse1 = Warehouse::factory()->create(['type' => 'bodega']);
        $warehouse2 = Warehouse::factory()->create(['type' => 'bodega']);
        $product1 = Product::factory()->create();
        $product2 = Product::factory()->create();

        Stock::factory()->create([
            'warehouse_id' => $warehouse1->id,
            'product_id' => $product1->id,
            'quantity' => 50,
        ]);
        Stock::factory()->create([
            'warehouse_id' => $warehouse2->id,
            'product_id' => $product2->id,
            'quantity' => 30,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->getJson("/api/v1/stock/{$warehouse1->id}");

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }

    public function test_user_without_permission_cannot_list_products(): void
    {
        $user = User::factory()->create(['is_super_admin' => true]);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/products');

        $response->assertStatus(403);
    }
}
