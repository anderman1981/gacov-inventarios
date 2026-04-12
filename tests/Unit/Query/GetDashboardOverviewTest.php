<?php

declare(strict_types=1);

namespace Tests\Unit\Query;

use App\Application\Query\Dashboard\GetDashboardOverview;
use App\Models\Machine;
use App\Models\Product;
use App\Models\Route;
use App\Models\StockMovement;
use App\Models\TransferOrder;
use App\Models\User;
use App\Models\Warehouse;
use Tests\TestCase;

final class GetDashboardOverviewTest extends TestCase
{
    private GetDashboardOverview $query;

    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->query     = $this->app->make(GetDashboardOverview::class);
        $this->adminUser = User::factory()->create(['is_super_admin' => true]);
        $this->actingAs($this->adminUser);
    }

    // ──────────────────────────────────────────────────────────────
    // STATS CORRECTNESS
    // ──────────────────────────────────────────────────────────────

    public function test_returns_correct_total_products_count(): void
    {
        Product::factory()->count(5)->create();
        Product::factory()->count(2)->inactive()->create();

        $result = $this->query->handle();

        $this->assertSame(7, $result['stats']['total_products']);
    }

    public function test_returns_correct_total_machines_count(): void
    {
        Machine::factory()->count(3)->create();

        $result = $this->query->handle();

        $this->assertSame(3, $result['stats']['total_machines']);
    }

    public function test_counts_pending_transfers_with_status_pendiente(): void
    {
        $wh1 = Warehouse::factory()->create();
        $wh2 = Warehouse::factory()->create();
        $user = $this->adminUser;

        TransferOrder::factory()->create([
            'origin_warehouse_id'      => $wh1->id,
            'destination_warehouse_id' => $wh2->id,
            'status'                   => 'pendiente',
            'requested_by'             => $user->id,
        ]);
        TransferOrder::factory()->create([
            'origin_warehouse_id'      => $wh1->id,
            'destination_warehouse_id' => $wh2->id,
            'status'                   => 'completado',
            'requested_by'             => $user->id,
        ]);

        $result = $this->query->handle();

        // Bug corregido: debe contar 'pendiente', no 'pending'
        $this->assertSame(1, $result['stats']['pending_transfers']);
    }

    public function test_pending_transfers_does_not_count_approved_or_completed(): void
    {
        $wh1 = Warehouse::factory()->create();
        $wh2 = Warehouse::factory()->create();

        foreach (['aprobado', 'completado', 'cancelado'] as $status) {
            TransferOrder::factory()->create([
                'origin_warehouse_id'      => $wh1->id,
                'destination_warehouse_id' => $wh2->id,
                'status'                   => $status,
                'requested_by'             => $this->adminUser->id,
            ]);
        }

        $result = $this->query->handle();

        $this->assertSame(0, $result['stats']['pending_transfers']);
    }

    public function test_counts_active_routes(): void
    {
        Route::factory()->count(3)->create(['is_active' => true]);
        Route::factory()->count(2)->inactive()->create();

        $result = $this->query->handle();

        $this->assertSame(3, $result['stats']['active_routes']);
    }

    public function test_movements_today_counts_only_todays_movements(): void
    {
        $product = Product::factory()->create();
        $wh      = Warehouse::factory()->create();

        // Hoy
        StockMovement::factory()->count(3)->create([
            'product_id'           => $product->id,
            'origin_warehouse_id'  => $wh->id,
            'movement_type'        => 'ajuste_manual',
            'performed_by'         => $this->adminUser->id,
        ]);
        // Ayer (no debe contar)
        $oldMovement = StockMovement::factory()->create([
            'product_id'           => $product->id,
            'origin_warehouse_id'  => $wh->id,
            'movement_type'        => 'ajuste_manual',
            'performed_by'         => $this->adminUser->id,
        ]);
        $oldMovement->forceFill(['created_at' => now()->subDay()])->save();

        $result = $this->query->handle();

        $this->assertSame(3, $result['stats']['movements_today']);
    }

    // ──────────────────────────────────────────────────────────────
    // COLLECTIONS RETURNED
    // ──────────────────────────────────────────────────────────────

    public function test_returns_recent_movements_from_today(): void
    {
        $product = Product::factory()->create();
        $wh      = Warehouse::factory()->create();

        StockMovement::factory()->count(5)->create([
            'product_id'          => $product->id,
            'origin_warehouse_id' => $wh->id,
            'movement_type'       => 'ajuste_manual',
            'performed_by'        => $this->adminUser->id,
        ]);

        $result = $this->query->handle();

        $this->assertCount(5, $result['recentMovements']);
    }

    public function test_recent_movements_capped_at_eight(): void
    {
        $product = Product::factory()->create();
        $wh      = Warehouse::factory()->create();

        StockMovement::factory()->count(12)->create([
            'product_id'          => $product->id,
            'origin_warehouse_id' => $wh->id,
            'movement_type'       => 'ajuste_manual',
            'performed_by'        => $this->adminUser->id,
        ]);

        $result = $this->query->handle();

        $this->assertLessThanOrEqual(8, $result['recentMovements']->count());
    }

    public function test_returns_pending_transfers_collection(): void
    {
        $wh1 = Warehouse::factory()->create();
        $wh2 = Warehouse::factory()->create();

        TransferOrder::factory()->count(3)->create([
            'origin_warehouse_id'      => $wh1->id,
            'destination_warehouse_id' => $wh2->id,
            'status'                   => 'pendiente',
            'requested_by'             => $this->adminUser->id,
        ]);

        $result = $this->query->handle();

        $this->assertCount(3, $result['pendingTransfers']);
    }

    public function test_pending_transfers_collection_capped_at_five(): void
    {
        $wh1 = Warehouse::factory()->create();
        $wh2 = Warehouse::factory()->create();

        TransferOrder::factory()->count(8)->create([
            'origin_warehouse_id'      => $wh1->id,
            'destination_warehouse_id' => $wh2->id,
            'status'                   => 'pendiente',
            'requested_by'             => $this->adminUser->id,
        ]);

        $result = $this->query->handle();

        $this->assertLessThanOrEqual(5, $result['pendingTransfers']->count());
    }

    public function test_returns_active_routes_collection_capped_at_ten(): void
    {
        Route::factory()->count(15)->create(['is_active' => true]);

        $result = $this->query->handle();

        $this->assertLessThanOrEqual(10, $result['routes']->count());
    }

    // ──────────────────────────────────────────────────────────────
    // RETURN STRUCTURE
    // ──────────────────────────────────────────────────────────────

    public function test_returns_all_required_keys(): void
    {
        $result = $this->query->handle();

        $this->assertArrayHasKey('stats', $result);
        $this->assertArrayHasKey('recentMovements', $result);
        $this->assertArrayHasKey('routes', $result);
        $this->assertArrayHasKey('pendingTransfers', $result);
        $this->assertArrayHasKey('monthMovements', $result);
        $this->assertArrayHasKey('role', $result);
    }

    public function test_stats_contains_all_keys(): void
    {
        $result = $this->query->handle();

        $this->assertArrayHasKey('total_products', $result['stats']);
        $this->assertArrayHasKey('total_machines', $result['stats']);
        $this->assertArrayHasKey('movements_today', $result['stats']);
        $this->assertArrayHasKey('movements_month', $result['stats']);
        $this->assertArrayHasKey('low_stock_machines', $result['stats']);
        $this->assertArrayHasKey('pending_transfers', $result['stats']);
        $this->assertArrayHasKey('active_routes', $result['stats']);
    }

    public function test_returns_user_role(): void
    {
        $result = $this->query->handle();

        $this->assertNotEmpty($result['role']);
    }

    public function test_empty_database_returns_zeros_in_stats(): void
    {
        $result = $this->query->handle();

        $this->assertSame(0, $result['stats']['total_products']);
        $this->assertSame(0, $result['stats']['total_machines']);
        $this->assertSame(0, $result['stats']['pending_transfers']);
        $this->assertSame(0, $result['stats']['active_routes']);
    }
}
