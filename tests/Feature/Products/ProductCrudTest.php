<?php

declare(strict_types=1);

namespace Tests\Feature\Products;

use App\Models\Product;
use App\Models\Stock;
use App\Models\User;
use App\Models\Warehouse;
use Tests\TestCase;

final class ProductCrudTest extends TestCase
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
    }

    // ============================================================
    // TESTS: Listado de Productos
    // ============================================================

    public function test_authenticated_user_can_view_products(): void
    {
        Product::factory()->count(5)->create();

        $response = $this->actingAs($this->adminUser)
            ->get(route('products.index'));

        $response->assertStatus(200);
        $response->assertViewIs('products.index');
    }

    public function test_products_are_paginated(): void
    {
        Product::factory()->count(30)->create();

        $response = $this->actingAs($this->adminUser)
            ->get(route('products.index', ['per_page' => 10]));

        $response->assertStatus(200);
        $response->assertSee('page=2');
    }

    public function test_products_can_be_searched(): void
    {
        Product::factory()->create(['name' => 'Coca Cola']);
        Product::factory()->create(['name' => 'Pepsi']);

        $response = $this->actingAs($this->adminUser)
            ->get(route('products.index', ['search' => 'Coca']));

        $response->assertStatus(200);
        $response->assertSee('Coca Cola');
    }

    public function test_products_can_be_filtered_by_category(): void
    {
        Product::factory()->create(['category' => 'bebida_fria', 'name' => 'Refresco']);
        Product::factory()->create(['category' => 'snack', 'name' => 'Papas']);

        $response = $this->actingAs($this->adminUser)
            ->get(route('products.index', ['category' => 'bebida_fria']));

        $response->assertStatus(200);
        $response->assertSee('Refresco');
        $response->assertDontSee('Papas');
    }

    public function test_inactive_products_are_hidden_by_default(): void
    {
        $activeProduct = Product::factory()->create(['is_active' => true, 'name' => 'Producto Activo Test']);
        $inactiveProduct = Product::factory()->create(['is_active' => false, 'name' => 'Producto Inactivo Test']);

        $response = $this->actingAs($this->adminUser)
            ->get(route('products.index'));

        $response->assertStatus(200);
        // Only active product should appear in the list
        $response->assertSee($activeProduct->name);
        $response->assertDontSee($inactiveProduct->name);
    }

    public function test_inactive_tab_shows_inactive_products(): void
    {
        $activeProduct = Product::factory()->create(['is_active' => true, 'name' => 'Producto Activo Test']);
        $inactiveProduct = Product::factory()->create(['is_active' => false, 'name' => 'Producto Inactivo Test']);

        $response = $this->actingAs($this->adminUser)
            ->get(route('products.index', ['status' => 'inactive']));

        $response->assertStatus(200);
        $response->assertSee($inactiveProduct->name);
    }

    // ============================================================
    // TESTS: Creación de Productos
    // ============================================================

    public function test_user_with_permission_can_see_create_form(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->get(route('products.create'));

        $response->assertStatus(200);
        $response->assertViewIs('products.create');
    }

    public function test_user_without_permission_cannot_see_create_form(): void
    {
        $user = User::factory()->create();
        $user->givePermissionTo('products.view');

        $response = $this->actingAs($user)
            ->get(route('products.create'));

        $response->assertStatus(403);
    }

    public function test_product_can_be_created(): void
    {
        $productData = [
            'code' => 'TEST001',
            'name' => 'Producto de Prueba',
            'category' => 'snack',
            'unit_of_measure' => 'Und.',
            'unit_price' => 1500.00,
            'min_stock_alert' => 10,
        ];

        $response = $this->actingAs($this->adminUser)
            ->post(route('products.store'), $productData);

        $response->assertRedirect(route('products.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('products', [
            'code' => 'TEST001',
            'name' => 'Producto de Prueba',
        ]);
    }

    public function test_product_code_must_be_unique(): void
    {
        Product::factory()->create(['code' => 'UNIQUE001']);

        $response = $this->actingAs($this->adminUser)
            ->post(route('products.store'), [
                'code' => 'UNIQUE001',
                'name' => 'Producto Duplicado',
                'category' => 'snack',
                'unit_of_measure' => 'Und.',
                'unit_price' => 1000,
            ]);

        $response->assertSessionHasErrors('code');
    }

    public function test_product_requires_name(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('products.store'), [
                'code' => 'NOTITLE',
                'category' => 'snack',
            ]);

        $response->assertSessionHasErrors('name');
    }

    // ============================================================
    // TESTS: Actualización de Productos
    // ============================================================

    public function test_product_can_be_updated(): void
    {
        $product = Product::factory()->create([
            'name' => 'Nombre Original',
        ]);

        $response = $this->actingAs($this->adminUser)
            ->put(route('products.update', $product), [
                'code' => $product->code,
                'name' => 'Nombre Actualizado',
                'category' => $product->category,
                'unit_of_measure' => $product->unit_of_measure,
                'unit_price' => $product->unit_price,
                'min_stock_alert' => $product->min_stock_alert,
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => 'Nombre Actualizado',
        ]);
    }

    // ============================================================
    // TESTS: Desactivación de Productos
    // ============================================================

    public function test_product_can_be_deactivated(): void
    {
        $product = Product::factory()->create(['is_active' => true]);

        $response = $this->actingAs($this->adminUser)
            ->delete(route('products.destroy', $product));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'is_active' => false,
        ]);
    }

    public function test_product_with_stock_cannot_be_deactivated(): void
    {
        $product = Product::factory()->create();
        $warehouse = Warehouse::factory()->create();
        Stock::factory()->create([
            'product_id' => $product->id,
            'warehouse_id' => $warehouse->id,
            'quantity' => 100,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->delete(route('products.destroy', $product));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $response->assertSessionHas('error', 'No se puede desactivar un producto con stock activo en alguna bodega.');
    }

    public function test_product_can_be_toggled(): void
    {
        $product = Product::factory()->create(['is_active' => true]);

        $response = $this->actingAs($this->adminUser)
            ->patch(route('products.toggle', $product));

        $response->assertRedirect();
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'is_active' => false,
        ]);
    }
}
