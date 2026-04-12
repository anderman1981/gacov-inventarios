<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use App\Application\Command\Driver\RegisterStocking;
use App\Models\Machine;
use App\Models\MachineStockingItem;
use App\Models\MachineStockingRecord;
use App\Models\Product;
use App\Models\Route;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use Database\Factories\MachineFactory;
use Database\Factories\RouteFactory;
use Illuminate\Support\Collection;
use Tests\TestCase;

final class RegisterStockingTest extends TestCase
{
    private RegisterStocking $command;

    private User $driver;

    private Route $route;

    private Machine $machine;

    private Warehouse $vehicleWarehouse;

    private Warehouse $machineWarehouse;

    protected function setUp(): void
    {
        parent::setUp();

        $this->command = new RegisterStocking();
        $this->driver  = User::factory()->create(['is_super_admin' => true]);
        $this->route   = Route::factory()->create();
        $this->machine = Machine::factory()->forRoute($this->route->id)->create();

        $this->vehicleWarehouse = Warehouse::factory()->vehiculo()->create([
            'route_id' => $this->route->id,
        ]);
        $this->machineWarehouse = Warehouse::factory()->maquina()->create([
            'machine_id' => $this->machine->id,
        ]);
        // Relacionar bodega a la máquina
        $this->machine->update(['route_id' => $this->route->id]);
    }

    // ──────────────────────────────────────────────────────────────
    // HAPPY PATH
    // ──────────────────────────────────────────────────────────────

    public function test_creates_stocking_record_with_correct_code_format(): void
    {
        $product = Product::factory()->create();
        $this->seedVehicleStock($product, 10);

        $items = collect([$product->id => ['quantity' => 5]]);

        $record = $this->command->handle(
            $this->driver, $this->route, $this->machine,
            $this->vehicleWarehouse, $this->machineWarehouse,
            $items, null,
        );

        $this->assertStringStartsWith('SURT-', $record->code);
        $this->assertSame('completado', $record->status);
        $this->assertFalse($record->was_offline);
    }

    public function test_decrements_vehicle_stock_after_stocking(): void
    {
        $product = Product::factory()->create();
        $this->seedVehicleStock($product, 10);

        $items = collect([$product->id => ['quantity' => 4]]);

        $this->command->handle(
            $this->driver, $this->route, $this->machine,
            $this->vehicleWarehouse, $this->machineWarehouse,
            $items, null,
        );

        $remaining = Stock::where('warehouse_id', $this->vehicleWarehouse->id)
            ->where('product_id', $product->id)
            ->value('quantity');

        $this->assertSame(6, $remaining);
    }

    public function test_increments_machine_stock_after_stocking(): void
    {
        $product = Product::factory()->create();
        $this->seedVehicleStock($product, 10);
        // Bodega máquina comienza en 2
        Stock::factory()->forWarehouse($this->machineWarehouse)->forProduct($product)->withQuantity(2)->create();

        $items = collect([$product->id => ['quantity' => 3]]);

        $this->command->handle(
            $this->driver, $this->route, $this->machine,
            $this->vehicleWarehouse, $this->machineWarehouse,
            $items, null,
        );

        $machineQty = Stock::where('warehouse_id', $this->machineWarehouse->id)
            ->where('product_id', $product->id)
            ->value('quantity');

        $this->assertSame(5, $machineQty);
    }

    public function test_creates_stock_movement_of_type_surtido_maquina(): void
    {
        $product = Product::factory()->create();
        $this->seedVehicleStock($product, 10);

        $items = collect([$product->id => ['quantity' => 6]]);

        $record = $this->command->handle(
            $this->driver, $this->route, $this->machine,
            $this->vehicleWarehouse, $this->machineWarehouse,
            $items, null,
        );

        $movement = StockMovement::where('reference_code', $record->code)->first();

        $this->assertNotNull($movement);
        $this->assertSame('surtido_maquina', $movement->movement_type);
        $this->assertSame($this->vehicleWarehouse->id, $movement->origin_warehouse_id);
        $this->assertSame($this->machineWarehouse->id, $movement->destination_warehouse_id);
        $this->assertSame(6, $movement->quantity);
        $this->assertSame($this->driver->id, $movement->performed_by);
    }

    public function test_creates_stocking_items_for_each_product(): void
    {
        $p1 = Product::factory()->create();
        $p2 = Product::factory()->create();
        $this->seedVehicleStock($p1, 10);
        $this->seedVehicleStock($p2, 10);

        $items = collect([
            $p1->id => ['quantity' => 2],
            $p2->id => ['quantity' => 3],
        ]);

        $record = $this->command->handle(
            $this->driver, $this->route, $this->machine,
            $this->vehicleWarehouse, $this->machineWarehouse,
            $items, 'Notas de prueba',
        );

        $this->assertSame(2, MachineStockingItem::where('stocking_record_id', $record->id)->count());
        $this->assertSame('Notas de prueba', $record->notes);
    }

    public function test_processes_multiple_products_in_single_stocking(): void
    {
        $products = Product::factory()->count(3)->create();
        foreach ($products as $product) {
            $this->seedVehicleStock($product, 20);
        }

        $items = collect($products->mapWithKeys(fn ($p) => [$p->id => ['quantity' => 5]])->all());

        $record = $this->command->handle(
            $this->driver, $this->route, $this->machine,
            $this->vehicleWarehouse, $this->machineWarehouse,
            $items, null,
        );

        $this->assertSame(3, MachineStockingItem::where('stocking_record_id', $record->id)->count());
        $this->assertSame(3, StockMovement::where('reference_code', $record->code)->count());
    }

    // ──────────────────────────────────────────────────────────────
    // STOCK INSUFICIENTE — excepciones
    // ──────────────────────────────────────────────────────────────

    public function test_throws_exception_when_vehicle_stock_insufficient(): void
    {
        $product = Product::factory()->create(['name' => 'Café Solo']);
        $this->seedVehicleStock($product, 3);

        $items = collect([$product->id => ['quantity' => 10]]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Stock insuficiente/');
        $this->expectExceptionMessageMatches('/Café Solo/');

        $this->command->handle(
            $this->driver, $this->route, $this->machine,
            $this->vehicleWarehouse, $this->machineWarehouse,
            $items, null,
        );
    }

    public function test_exception_message_includes_available_and_requested_quantities(): void
    {
        $product = Product::factory()->create(['name' => 'Producto X']);
        $this->seedVehicleStock($product, 2);

        $items = collect([$product->id => ['quantity' => 8]]);

        try {
            $this->command->handle(
                $this->driver, $this->route, $this->machine,
                $this->vehicleWarehouse, $this->machineWarehouse,
                $items, null,
            );
            $this->fail('RuntimeException expected');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('2', $e->getMessage());
            $this->assertStringContainsString('8', $e->getMessage());
        }
    }

    public function test_throws_when_zero_vehicle_stock_and_positive_request(): void
    {
        $product = Product::factory()->create();
        // Sin stock en vehículo

        $items = collect([$product->id => ['quantity' => 1]]);

        $this->expectException(\RuntimeException::class);

        $this->command->handle(
            $this->driver, $this->route, $this->machine,
            $this->vehicleWarehouse, $this->machineWarehouse,
            $items, null,
        );
    }

    public function test_insufficient_stock_on_second_product_prevents_whole_transaction(): void
    {
        $p1 = Product::factory()->create();
        $p2 = Product::factory()->create();
        $this->seedVehicleStock($p1, 10);
        // p2 sin stock

        $items = collect([
            $p1->id => ['quantity' => 3],
            $p2->id => ['quantity' => 5],
        ]);

        try {
            $this->command->handle(
                $this->driver, $this->route, $this->machine,
                $this->vehicleWarehouse, $this->machineWarehouse,
                $items, null,
            );
        } catch (\RuntimeException) {
            // Sin registros creados (la validación es previa a la transacción)
            $this->assertSame(0, MachineStockingRecord::count());
            $this->assertSame(0, StockMovement::count());
        }
    }

    // ──────────────────────────────────────────────────────────────
    // EDGE CASES
    // ──────────────────────────────────────────────────────────────

    public function test_product_not_in_vehicle_warehouse_counts_as_zero_stock(): void
    {
        $product = Product::factory()->create(['name' => 'Sin Stock']);
        // No se crea Stock para vehicleWarehouse

        $items = collect([$product->id => ['quantity' => 1]]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Sin Stock/');

        $this->command->handle(
            $this->driver, $this->route, $this->machine,
            $this->vehicleWarehouse, $this->machineWarehouse,
            $items, null,
        );
    }

    public function test_exact_stock_match_is_allowed(): void
    {
        $product = Product::factory()->create();
        $this->seedVehicleStock($product, 5);

        $items = collect([$product->id => ['quantity' => 5]]); // exacto

        $record = $this->command->handle(
            $this->driver, $this->route, $this->machine,
            $this->vehicleWarehouse, $this->machineWarehouse,
            $items, null,
        );

        $this->assertNotNull($record->id);

        $remaining = Stock::where('warehouse_id', $this->vehicleWarehouse->id)
            ->where('product_id', $product->id)
            ->value('quantity');
        $this->assertSame(0, $remaining);
    }

    // ──────────────────────────────────────────────────────────────
    // HELPERS
    // ──────────────────────────────────────────────────────────────

    private function seedVehicleStock(Product $product, int $qty): void
    {
        Stock::factory()
            ->forWarehouse($this->vehicleWarehouse)
            ->forProduct($product)
            ->withQuantity($qty)
            ->create();
    }
}
