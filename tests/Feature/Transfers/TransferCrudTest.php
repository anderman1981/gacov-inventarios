<?php

declare(strict_types=1);

namespace Tests\Feature\Transfers;

use App\Models\Product;
use App\Models\Stock;
use App\Models\TransferOrder;
use App\Models\User;
use App\Models\Warehouse;
use Tests\TestCase;

final class TransferCrudTest extends TestCase
{
    private User $adminUser;

    private Warehouse $originWarehouse;

    private Warehouse $destinationWarehouse;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create(['is_super_admin' => true]);
        $this->adminUser->givePermissionTo('transfers.view');
        $this->adminUser->givePermissionTo('transfers.create');
        $this->adminUser->givePermissionTo('transfers.approve');
        $this->adminUser->givePermissionTo('transfers.complete');

        $this->originWarehouse = Warehouse::factory()->create(['type' => 'bodega']);
        $this->destinationWarehouse = Warehouse::factory()->create(['type' => 'bodega']);
    }

    // ============================================================
    // TESTS: Listado de Traslados
    // ============================================================

    public function test_authenticated_user_can_view_transfers(): void
    {
        TransferOrder::factory()->count(3)->create();

        $response = $this->actingAs($this->adminUser)
            ->get(route('transfers.index'));

        $response->assertStatus(200);
        $response->assertViewIs('transfers.index');
    }

    public function test_transfers_can_be_filtered_by_status(): void
    {
        TransferOrder::factory()->create(['status' => 'pendiente']);
        TransferOrder::factory()->create(['status' => 'completado']);

        $response = $this->actingAs($this->adminUser)
            ->get(route('transfers.index', ['status' => 'pendiente']));

        $response->assertStatus(200);
    }

    public function test_transfers_can_be_searched(): void
    {
        TransferOrder::factory()->create(['code' => 'TRAS-20260411-0001']);

        $response = $this->actingAs($this->adminUser)
            ->get(route('transfers.index', ['search' => 'TRAS-2026']));

        $response->assertStatus(200);
    }

    // ============================================================
    // TESTS: Creación de Traslados
    // ============================================================

    public function test_user_can_see_create_form(): void
    {
        Product::factory()->create();
        Warehouse::factory()->create();

        $response = $this->actingAs($this->adminUser)
            ->get(route('transfers.create'));

        $response->assertStatus(200);
        $response->assertViewIs('transfers.create');
    }

    public function test_transfer_can_be_created(): void
    {
        $product = Product::factory()->create();

        // Crear stock en bodega origen
        Stock::factory()->create([
            'product_id' => $product->id,
            'warehouse_id' => $this->originWarehouse->id,
            'quantity' => 100,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('transfers.store'), [
                'origin_warehouse_id' => $this->originWarehouse->id,
                'destination_warehouse_id' => $this->destinationWarehouse->id,
                'items' => [
                    [
                        'product_id' => $product->id,
                        'quantity_requested' => 50,
                    ],
                ],
            ]);

        $response->assertRedirect(route('transfers.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('transfer_orders', [
            'origin_warehouse_id' => $this->originWarehouse->id,
            'destination_warehouse_id' => $this->destinationWarehouse->id,
            'status' => 'pendiente',
        ]);
    }

    public function test_transfer_requires_at_least_one_item(): void
    {
        $response = $this->actingAs($this->adminUser)
            ->post(route('transfers.store'), [
                'origin_warehouse_id' => $this->originWarehouse->id,
                'destination_warehouse_id' => $this->destinationWarehouse->id,
                'items' => [],
            ]);

        $response->assertSessionHasErrors('items');
    }

    public function test_transfer_code_is_generated(): void
    {
        $product = Product::factory()->create();
        Stock::factory()->create([
            'product_id' => $product->id,
            'warehouse_id' => $this->originWarehouse->id,
            'quantity' => 100,
        ]);

        $this->actingAs($this->adminUser)
            ->post(route('transfers.store'), [
                'origin_warehouse_id' => $this->originWarehouse->id,
                'destination_warehouse_id' => $this->destinationWarehouse->id,
                'items' => [
                    ['product_id' => $product->id, 'quantity_requested' => 10],
                ],
            ]);

        $this->assertDatabaseHas('transfer_orders', [
            'status' => 'pendiente',
        ]);

        // Verificar que el código tiene el formato correcto
        $transfer = TransferOrder::where('status', 'pendiente')->first();
        $this->assertMatchesRegularExpression('/^TRAS-\d{8}-\d{4}$/', $transfer->code);
    }

    // ============================================================
    // TESTS: Aprobación de Traslados
    // ============================================================

    public function test_transfer_can_be_approved(): void
    {
        $transfer = TransferOrder::factory()->create(['status' => 'pendiente']);

        $response = $this->actingAs($this->adminUser)
            ->post(route('transfers.approve', $transfer));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('transfer_orders', [
            'id' => $transfer->id,
            'status' => 'aprobado',
        ]);
    }

    public function test_only_pending_transfer_can_be_approved(): void
    {
        $transfer = TransferOrder::factory()->create(['status' => 'completado']);

        $response = $this->actingAs($this->adminUser)
            ->post(route('transfers.approve', $transfer));

        $response->assertRedirect();
        $response->assertSessionHasErrors();
    }

    // ============================================================
    // TESTS: Completación de Traslados
    // ============================================================

    public function test_transfer_can_be_completed(): void
    {
        $product = Product::factory()->create();

        // Stock en origen
        Stock::factory()->create([
            'product_id' => $product->id,
            'warehouse_id' => $this->originWarehouse->id,
            'quantity' => 100,
        ]);

        $transfer = TransferOrder::factory()->create([
            'status' => 'aprobado',
            'origin_warehouse_id' => $this->originWarehouse->id,
            'destination_warehouse_id' => $this->destinationWarehouse->id,
        ]);

        // Crear items de transferencia
        $transfer->items()->create([
            'product_id' => $product->id,
            'quantity_requested' => 50,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->post(route('transfers.complete', $transfer), [
                'items' => [
                    $transfer->items->first()->id => ['quantity_received' => 50],
                ],
            ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('transfer_orders', [
            'id' => $transfer->id,
            'status' => 'completado',
        ]);
    }

    public function test_only_approved_transfer_can_be_completed(): void
    {
        $transfer = TransferOrder::factory()->create(['status' => 'pendiente']);

        $response = $this->actingAs($this->adminUser)
            ->post(route('transfers.complete', $transfer));

        $response->assertRedirect();
        $response->assertSessionHasErrors();
    }

    public function test_completed_transfer_updates_stock(): void
    {
        $product = Product::factory()->create();

        // Stock inicial en origen
        $originStock = Stock::factory()->create([
            'product_id' => $product->id,
            'warehouse_id' => $this->originWarehouse->id,
            'quantity' => 100,
        ]);

        $transfer = TransferOrder::factory()->create([
            'status' => 'aprobado',
            'origin_warehouse_id' => $this->originWarehouse->id,
            'destination_warehouse_id' => $this->destinationWarehouse->id,
        ]);

        $item = $transfer->items()->create([
            'product_id' => $product->id,
            'quantity_requested' => 50,
        ]);

        $this->actingAs($this->adminUser)
            ->post(route('transfers.complete', $transfer), [
                'items' => [
                    $item->id => ['quantity_received' => 50],
                ],
            ]);

        // Verificar stock en origen decrementado
        $originStock->refresh();
        $this->assertEquals(50, $originStock->quantity);

        // Verificar stock en destino incrementado
        $destStock = Stock::where('warehouse_id', $this->destinationWarehouse->id)
            ->where('product_id', $product->id)
            ->first();
        $this->assertNotNull($destStock);
        $this->assertEquals(50, $destStock->quantity);
    }

    public function test_completed_transfer_creates_movements(): void
    {
        $product = Product::factory()->create();

        Stock::factory()->create([
            'product_id' => $product->id,
            'warehouse_id' => $this->originWarehouse->id,
            'quantity' => 100,
        ]);

        $transfer = TransferOrder::factory()->create([
            'status' => 'aprobado',
            'origin_warehouse_id' => $this->originWarehouse->id,
            'destination_warehouse_id' => $this->destinationWarehouse->id,
        ]);

        $item = $transfer->items()->create([
            'product_id' => $product->id,
            'quantity_requested' => 30,
        ]);

        $this->actingAs($this->adminUser)
            ->post(route('transfers.complete', $transfer), [
                'items' => [
                    $item->id => ['quantity_received' => 30],
                ],
            ]);

        // Verificar movimientos de salida y entrada
        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'movement_type' => 'traslado_salida',
            'quantity' => 30,
            'reference_code' => $transfer->code,
        ]);

        $this->assertDatabaseHas('stock_movements', [
            'product_id' => $product->id,
            'movement_type' => 'traslado_entrada',
            'quantity' => 30,
            'reference_code' => $transfer->code,
        ]);
    }

    // ============================================================
    // TESTS: Cancelación de Traslados
    // ============================================================

    public function test_transfer_can_be_cancelled(): void
    {
        $transfer = TransferOrder::factory()->create(['status' => 'pendiente']);

        $response = $this->actingAs($this->adminUser)
            ->post(route('transfers.cancel', $transfer));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('transfer_orders', [
            'id' => $transfer->id,
            'status' => 'cancelado',
        ]);
    }

    public function test_only_pending_transfer_can_be_cancelled(): void
    {
        $transfer = TransferOrder::factory()->create(['status' => 'completado']);

        $response = $this->actingAs($this->adminUser)
            ->post(route('transfers.cancel', $transfer));

        $response->assertRedirect();
        $response->assertSessionHasErrors();
    }
}
