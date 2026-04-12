<?php

declare(strict_types=1);

namespace Tests\Feature\Api;

use App\Models\Product;
use App\Models\Stock;
use App\Models\TransferOrder;
use App\Models\TransferOrderItem;
use App\Models\User;
use App\Models\Warehouse;
use Tests\TestCase;

final class TransferApiTest extends TestCase
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

    public function test_unauthenticated_user_cannot_access_transfers_api(): void
    {
        $response = $this->getJson('/api/v1/transfers');

        $response->assertStatus(401);
    }

    public function test_authenticated_user_can_list_transfers(): void
    {
        TransferOrder::factory()->count(3)->create();

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/transfers');

        $response->assertStatus(200);
        $response->assertJsonStructure([
            'data',
            'meta' => ['current_page', 'last_page', 'per_page', 'total'],
        ]);
    }

    public function test_transfers_can_be_filtered_by_status(): void
    {
        TransferOrder::factory()->create(['status' => 'pendiente']);
        TransferOrder::factory()->create(['status' => 'completado']);

        $response = $this->actingAs($this->adminUser)
            ->getJson('/api/v1/transfers?status=pendiente');

        $response->assertStatus(200);
        $response->assertJsonCount(1, 'data');
    }

    public function test_can_get_single_transfer(): void
    {
        $transfer = TransferOrder::factory()->create(['code' => 'TRF-TEST-001']);

        $response = $this->actingAs($this->adminUser)
            ->getJson("/api/v1/transfers/{$transfer->id}");

        $response->assertStatus(200);
        $response->assertJsonPath('data.code', 'TRF-TEST-001');
    }

    public function test_can_create_transfer(): void
    {
        $product = Product::factory()->create();

        $data = [
            'origin_warehouse_id' => $this->originWarehouse->id,
            'destination_warehouse_id' => $this->destinationWarehouse->id,
            'notes' => 'Test transfer',
            'items' => [
                ['product_id' => $product->id, 'quantity_requested' => 10],
            ],
        ];

        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/v1/transfers', $data);

        $response->assertStatus(201);
        $response->assertJsonPath('message', 'Traslado creado correctamente.');

        $this->assertDatabaseHas('transfer_orders', [
            'origin_warehouse_id' => $this->originWarehouse->id,
            'destination_warehouse_id' => $this->destinationWarehouse->id,
            'status' => 'pendiente',
        ]);
    }

    public function test_transfer_requires_items(): void
    {
        $data = [
            'origin_warehouse_id' => $this->originWarehouse->id,
            'destination_warehouse_id' => $this->destinationWarehouse->id,
            'items' => [],
        ];

        $response = $this->actingAs($this->adminUser)
            ->postJson('/api/v1/transfers', $data);

        $response->assertStatus(422);
    }

    public function test_can_approve_transfer(): void
    {
        $transfer = TransferOrder::factory()->create(['status' => 'pendiente']);

        $response = $this->actingAs($this->adminUser)
            ->postJson("/api/v1/transfers/{$transfer->id}/approve");

        $response->assertStatus(200);
        $response->assertJsonPath('message', "Traslado {$transfer->code} aprobado.");

        $this->assertDatabaseHas('transfer_orders', [
            'id' => $transfer->id,
            'status' => 'aprobado',
        ]);
    }

    public function test_only_pending_transfer_can_be_approved(): void
    {
        $transfer = TransferOrder::factory()->create(['status' => 'completado']);

        $response = $this->actingAs($this->adminUser)
            ->postJson("/api/v1/transfers/{$transfer->id}/approve");

        $response->assertStatus(422);
        $response->assertJsonPath('error', 'Solo se pueden aprobar traslados en estado pendiente.');
    }

    public function test_can_complete_transfer(): void
    {
        $product = Product::factory()->create();
        Stock::factory()->create([
            'warehouse_id' => $this->originWarehouse->id,
            'product_id' => $product->id,
            'quantity' => 100,
        ]);

        $transfer = TransferOrder::factory()->create([
            'origin_warehouse_id' => $this->originWarehouse->id,
            'destination_warehouse_id' => $this->destinationWarehouse->id,
            'status' => 'aprobado',
        ]);
        TransferOrderItem::factory()->create([
            'transfer_order_id' => $transfer->id,
            'product_id' => $product->id,
            'quantity_requested' => 10,
        ]);

        $response = $this->actingAs($this->adminUser)
            ->postJson("/api/v1/transfers/{$transfer->id}/complete");

        $response->assertStatus(200);
        $response->assertJsonPath('message', "Traslado {$transfer->code} completado.");

        $this->assertDatabaseHas('transfer_orders', [
            'id' => $transfer->id,
            'status' => 'completado',
        ]);
    }

    public function test_only_approved_transfer_can_be_completed(): void
    {
        $transfer = TransferOrder::factory()->create(['status' => 'pendiente']);

        $response = $this->actingAs($this->adminUser)
            ->postJson("/api/v1/transfers/{$transfer->id}/complete", [
                'items' => [],
            ]);

        $response->assertStatus(422);
        $response->assertJsonPath('error', 'Solo se pueden completar traslados en estado aprobado.');
    }

    public function test_can_cancel_transfer(): void
    {
        $transfer = TransferOrder::factory()->create(['status' => 'pendiente']);

        $response = $this->actingAs($this->adminUser)
            ->postJson("/api/v1/transfers/{$transfer->id}/cancel");

        $response->assertStatus(200);
        $response->assertJsonPath('message', "Traslado {$transfer->code} cancelado.");

        $this->assertDatabaseHas('transfer_orders', [
            'id' => $transfer->id,
            'status' => 'cancelado',
        ]);
    }

    public function test_only_pending_transfer_can_be_cancelled(): void
    {
        $transfer = TransferOrder::factory()->create(['status' => 'completado']);

        $response = $this->actingAs($this->adminUser)
            ->postJson("/api/v1/transfers/{$transfer->id}/cancel");

        $response->assertStatus(422);
        $response->assertJsonPath('error', 'Solo se pueden cancelar traslados en estado pendiente.');
    }

    public function test_user_without_permission_cannot_list_transfers(): void
    {
        $user = User::factory()->create(['is_super_admin' => true]);

        $response = $this->actingAs($user)
            ->getJson('/api/v1/transfers');

        $response->assertStatus(403);
    }
}
