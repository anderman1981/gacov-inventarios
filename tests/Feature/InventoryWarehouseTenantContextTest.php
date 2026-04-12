<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Tenant;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

final class InventoryWarehouseTenantContextTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_render_main_warehouse_screen_with_tenant_scoped_data(): void
    {
        Permission::findOrCreate('inventory.view', 'web');

        $tenant = Tenant::create([
            'name' => 'GACOV Tenant',
            'slug' => 'gacov-tenant',
            'email' => 'tenant@example.com',
            'is_active' => true,
        ]);

        $warehouse = new Warehouse([
            'tenant_id' => $tenant->id,
            'name' => 'Bodega Principal',
            'code' => 'BODEGA',
            'type' => 'bodega',
            'is_active' => true,
        ]);
        $warehouse->tenant_id = $tenant->id;
        $warehouse->save();

        $user = User::factory()->create([
            'is_super_admin' => true,
            'tenant_id' => null,
        ]);
        $user->givePermissionTo('inventory.view');

        $response = $this->actingAs($user)->get(route('inventory.warehouse'));

        $response->assertOk();
        $response->assertSee('Inventario central');
        $response->assertDontSee('No se encontró bodega principal');
    }
}
