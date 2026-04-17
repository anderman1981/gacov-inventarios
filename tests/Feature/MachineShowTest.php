<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AppModule;
use App\Models\Machine;
use App\Models\Product;
use App\Models\Route;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Warehouse;
use App\Models\Stock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Pagination\LengthAwarePaginator;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

final class MachineShowTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_can_open_machine_detail_page(): void
    {
        $tenant = $this->createTenant();
        $manager = $this->createManager($tenant);
        $machine = $this->createMachine($tenant, 'M006', 'Máquina 006');

        $response = $this->actingAs($manager)->get(route('machines.show', $machine));

        $response->assertOk();
        $response->assertSee('Máquina 006');
        $response->assertSee('M006');
    }

    public function test_manager_can_navigate_from_machine_index_to_detail_page(): void
    {
        $tenant = $this->createTenant();
        $manager = $this->createManager($tenant);
        $machine = $this->createMachine($tenant, 'M007', 'Máquina 007');

        $response = $this->actingAs($manager)->get(route('machines.index'));

        $response->assertOk();
        $response->assertSee('Máquina 007');
        $response->assertSee(route('machines.show', $machine), false);
    }

    public function test_manager_can_use_machine_index_pagination(): void
    {
        $tenant = $this->createTenant();
        $manager = $this->createManager($tenant);

        Machine::factory()->count(11)->create([
            'tenant_id' => $tenant->id,
            'is_active' => true,
        ]);

        $response = $this->actingAs($manager)->get(route('machines.index', [
            'per_page' => 10,
        ]));

        $response->assertOk();
        $response->assertSee('Máquinas');
        $response->assertSee('Por página');

        $machines = $response->viewData('machines');
        $this->assertInstanceOf(LengthAwarePaginator::class, $machines);
        $this->assertSame(10, $machines->perPage());
        $this->assertSame(10, $machines->count());
    }

    public function test_manager_can_filter_and_paginate_machine_stock(): void
    {
        $tenant = $this->createTenant();
        $manager = $this->createManager($tenant);
        $machine = $this->createMachine($tenant, 'M008', 'Máquina 008');

        $withStock = Product::factory()->create([
            'tenant_id' => $tenant->id,
            'code' => 'PRD-OK',
            'name' => 'Producto con stock',
            'supplier_sku' => 'SKU-OK',
        ]);
        $withoutStock = Product::factory()->create([
            'tenant_id' => $tenant->id,
            'code' => 'PRD-ZERO',
            'name' => 'Producto sin stock',
            'supplier_sku' => 'SKU-ZERO',
        ]);

        $machineWarehouse = Warehouse::where('machine_id', $machine->id)
            ->where('type', 'maquina')
            ->firstOrFail();

        Stock::factory()->forWarehouse($machineWarehouse)->forProduct($withStock)->withQuantity(12)->create();
        Stock::factory()->forWarehouse($machineWarehouse)->forProduct($withoutStock)->withQuantity(0)->create();

        $response = $this->actingAs($manager)->get(
            route('machines.show', $machine) . '?' . http_build_query([
                'stock_search' => 'PRD-OK',
                'stock_filter' => 'with_stock',
                'per_page' => 10,
            ])
        );

        $response->assertOk();
        $response->assertSee('Stock actual');
        $response->assertSee('Filtra por producto, estado y cantidad por página.');
        $response->assertSee('PRD-OK');
        $response->assertDontSee('PRD-ZERO');

        $stockItems = $response->viewData('stockItems');
        $this->assertInstanceOf(LengthAwarePaginator::class, $stockItems);
        $this->assertSame(10, $stockItems->perPage());
    }

    private function createTenant(): Tenant
    {
        $this->ensureMachineModule();

        return Tenant::create([
            'name' => 'Tenant de prueba',
            'slug' => 'tenant-maquinas',
            'email' => 'tenant@example.com',
            'is_active' => true,
        ]);
    }

    private function createManager(Tenant $tenant): User
    {
        Permission::findOrCreate('machines.view', 'web');

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'is_active' => true,
            'is_super_admin' => false,
        ]);
        $user->syncRoles(['manager']);
        $user->givePermissionTo('machines.view');

        return $user;
    }

    private function createMachine(Tenant $tenant, string $code, string $name): Machine
    {
        $route = Route::factory()->create([
            'tenant_id' => $tenant->id,
            'code' => 'RT-'.$code,
            'name' => 'Ruta '.$code,
            'is_active' => true,
        ]);

        $machine = Machine::factory()->create([
            'tenant_id' => $tenant->id,
            'route_id' => $route->id,
            'code' => $code,
            'name' => $name,
            'is_active' => true,
        ]);

        Warehouse::factory()->maquina()->create([
            'tenant_id' => $tenant->id,
            'machine_id' => $machine->id,
            'route_id' => $route->id,
            'code' => $code,
            'name' => 'Bodega '.$name,
            'is_active' => true,
        ]);

        return $machine;
    }

    private function ensureMachineModule(): void
    {
        AppModule::query()->firstOrCreate(
            ['key' => 'machines'],
            [
                'name' => 'Máquinas',
                'phase_required' => 1,
                'sort_order' => 8,
                'is_active' => true,
            ]
        );
    }
}
