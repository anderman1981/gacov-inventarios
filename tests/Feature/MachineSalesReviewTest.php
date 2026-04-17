<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Machine;
use App\Models\MachineSale;
use App\Models\MachineSaleItem;
use App\Models\Product;
use App\Models\Route;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

final class MachineSalesReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_manager_sees_guided_machine_review_and_pagination_controls(): void
    {
        [$tenant, $manager] = $this->createTenantAndManager();
        $machineA = $this->createMachine($tenant, 'M001', 'Máquina 001', 'Ruta Centro');
        $machineB = $this->createMachine($tenant, 'M002', 'Máquina 002', 'Ruta Norte');
        $product = Product::factory()->create([
            'tenant_id' => $tenant->id,
            'code' => 'AGU600',
            'name' => 'Agua 600ML',
        ]);

        $this->createSales($tenant, $manager, $machineA, $product, 7, '2026-04-15');
        $this->createSales($tenant, $manager, $machineB, $product, 6, '2026-04-15');

        $response = $this->actingAs($manager)->get(route('machines.sales.review', [
            'per_page' => 6,
        ]));

        $response->assertOk();
        $response->assertSee('Revisión por máquina');
        $response->assertSee('Por página');
        $response->assertSee('Agua 600ML');
        $response->assertViewHas('sales', function ($sales): bool {
            return $sales->total() === 13
                && $sales->perPage() === 6
                && $sales->currentPage() === 1;
        });
    }

    public function test_manager_can_drill_down_to_single_machine_sales(): void
    {
        [$tenant, $manager] = $this->createTenantAndManager();
        $machineA = $this->createMachine($tenant, 'M010', 'Máquina 010', 'Ruta Centro');
        $machineB = $this->createMachine($tenant, 'M020', 'Máquina 020', 'Ruta Norte');
        $product = Product::factory()->create([
            'tenant_id' => $tenant->id,
            'code' => 'SNK001',
            'name' => 'Papas',
        ]);

        $this->createSales($tenant, $manager, $machineA, $product, 3, '2026-04-15');
        $this->createSales($tenant, $manager, $machineB, $product, 2, '2026-04-15');

        $response = $this->actingAs($manager)->get(route('machines.sales.review', [
            'machine_id' => $machineA->id,
            'per_page' => 12,
        ]));

        $response->assertOk();
        $response->assertSee('M010');
        $response->assertSee('Máquina 010');
        $response->assertViewHas('sales', function ($sales) use ($machineA): bool {
            return $sales->total() === 3
                && $sales->count() === 3
                && $sales->first()?->machine_id === $machineA->id;
        });
    }

    /**
     * @return array{0: Tenant, 1: User}
     */
    private function createTenantAndManager(): array
    {
        $tenant = Tenant::factory()->create([
            'is_active' => true,
        ]);

        $manager = User::factory()->create([
            'tenant_id' => $tenant->id,
            'is_super_admin' => false,
            'is_active' => true,
        ]);
        $manager->syncRoles(['manager']);

        Permission::findOrCreate('sales.review', 'web');
        $manager->givePermissionTo('sales.review');

        return [$tenant, $manager];
    }

    private function createMachine(Tenant $tenant, string $code, string $name, string $routeName): Machine
    {
        $route = Route::factory()->create([
            'tenant_id' => $tenant->id,
            'code' => $code.'-RT',
            'name' => $routeName,
            'is_active' => true,
        ]);

        return Machine::factory()->create([
            'tenant_id' => $tenant->id,
            'route_id' => $route->id,
            'code' => $code,
            'name' => $name,
            'location' => 'Centro Comercial',
            'is_active' => true,
        ]);
    }

    private function createSales(Tenant $tenant, User $manager, Machine $machine, Product $product, int $count, string $date): void
    {
        for ($i = 1; $i <= $count; $i++) {
            $sale = MachineSale::create([
                'tenant_id' => $tenant->id,
                'code' => sprintf('VTA-%s-%02d', $machine->code, $i),
                'machine_id' => $machine->id,
                'registered_by' => $manager->id,
                'status' => 'confirmado',
                'offline_local_id' => null,
                'was_offline' => false,
                'notes' => 'Venta de prueba',
                'sale_date' => $date,
                'cash_bills' => 10000,
                'cash_coins' => 0,
                'cash_total' => 10000,
            ]);

            MachineSaleItem::create([
                'tenant_id' => $tenant->id,
                'machine_sale_id' => $sale->id,
                'product_id' => $product->id,
                'quantity_sold' => 1,
                'unit_price' => 10000,
                'notes' => null,
            ]);
        }
    }
}
