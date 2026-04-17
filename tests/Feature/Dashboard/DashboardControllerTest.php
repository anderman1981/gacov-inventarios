<?php

declare(strict_types=1);

namespace Tests\Feature\Dashboard;

use App\Models\Product;
use App\Models\TransferOrder;
use App\Models\User;
use App\Models\Warehouse;
use App\Notifications\InventoryAdjustmentNotification;
use Tests\TestCase;

final class DashboardControllerTest extends TestCase
{
    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminUser = User::factory()->create(['is_super_admin' => true]);
        $this->adminUser->syncRoles(['super_admin']);
    }

    // ──────────────────────────────────────────────────────────────
    // ACCESO Y REDIRECCIONES
    // ──────────────────────────────────────────────────────────────

    public function test_unauthenticated_user_is_redirected_to_login(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertRedirect(route('login'));
    }

    public function test_admin_user_sees_dashboard(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('dashboard'));

        $response->assertStatus(200);
        $response->assertViewIs('dashboard');
        $response->assertSee('Últimos movimientos');
        $response->assertSee('Productos más vendidos');
        $response->assertSee('Ventas por ubicación');
        $response->assertSee('Ventas por máquina');
    }

    public function test_conductor_is_redirected_to_driver_dashboard(): void
    {
        // is_super_admin=true so tenant middleware passes; conductor role still triggers redirect
        $conductor = User::factory()->create(['is_super_admin' => true]);
        $conductor->syncRoles(['conductor']);

        $response = $this->actingAs($conductor)->get(route('dashboard'));

        $response->assertRedirect(route('driver.dashboard'));
    }

    // ──────────────────────────────────────────────────────────────
    // DATOS PASADOS A LA VISTA
    // ──────────────────────────────────────────────────────────────

    public function test_dashboard_view_receives_stats_array(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('dashboard'));

        $response->assertViewHas('stats');
        $stats = $response->viewData('stats');

        $this->assertArrayHasKey('total_products', $stats);
        $this->assertArrayHasKey('pending_transfers', $stats);
    }

    public function test_dashboard_view_receives_user_role(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('dashboard'));

        $response->assertViewHas('userRole');
    }

    public function test_dashboard_shows_correct_pending_transfers_count(): void
    {
        $wh1 = Warehouse::factory()->create();
        $wh2 = Warehouse::factory()->create();

        TransferOrder::factory()->count(3)->create([
            'origin_warehouse_id'      => $wh1->id,
            'destination_warehouse_id' => $wh2->id,
            'status'                   => 'pendiente',
            'requested_by'             => $this->adminUser->id,
        ]);

        $response = $this->actingAs($this->adminUser)->get(route('dashboard'));

        $stats = $response->viewData('stats');
        $this->assertSame(3, $stats['pending_transfers']);
    }

    public function test_dashboard_view_receives_pending_transfers_collection(): void
    {
        $response = $this->actingAs($this->adminUser)->get(route('dashboard'));

        $response->assertViewHas('pendingTransfers');
        $response->assertViewHas('recentMovements');
        $response->assertViewHas('routes');
        $response->assertViewHas('inventoryNotifications');
    }

    public function test_admin_dashboard_shows_inventory_notifications_panel(): void
    {
        $actor = User::factory()->create(['is_super_admin' => true]);
        $actor->syncRoles(['manager']);
        $warehouse = Warehouse::factory()->create(['type' => 'maquina', 'name' => 'Máquina M104']);
        $product = Product::factory()->create(['name' => 'Agua 600ML']);

        $this->adminUser->notify(new InventoryAdjustmentNotification(
            actor: $actor,
            warehouse: $warehouse,
            product: $product,
            oldQuantity: 10,
            newQuantity: 14,
            reason: 'Diferencia encontrada en conteo físico.',
        ));

        $response = $this->actingAs($this->adminUser)->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee('Ajustes pendientes de revisión');
        $response->assertSee('Diferencia encontrada en conteo físico.');
    }
}
