<?php

declare(strict_types=1);

namespace Tests\Unit\Application;

use App\Application\Command\Driver\RegisterSale;
use App\Models\Machine;
use App\Models\MachineSale;
use App\Models\MachineSaleItem;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\User;
use App\Models\Warehouse;
use Tests\TestCase;

final class RegisterSaleTest extends TestCase
{
    private RegisterSale $command;

    private User $driver;

    private Machine $machine;

    private Warehouse $machineWarehouse;

    protected function setUp(): void
    {
        parent::setUp();

        $this->command          = new RegisterSale();
        $this->driver           = User::factory()->create(['is_super_admin' => true]);
        $this->machine          = Machine::factory()->create();
        $this->machineWarehouse = Warehouse::factory()->maquina()->create();
    }

    // ──────────────────────────────────────────────────────────────
    // HAPPY PATH
    // ──────────────────────────────────────────────────────────────

    public function test_creates_sale_with_correct_code_format(): void
    {
        $product = Product::factory()->create();
        $this->seedMachineStock($product, 10);

        $items = collect([$product->id => ['quantity' => 2, 'unit_price' => 3500.0]]);

        $sale = $this->command->handle($this->driver, $this->machine, $this->machineWarehouse, $items, null);

        $this->assertStringStartsWith('VENTA-', $sale->code);
        $this->assertSame('completado', $sale->status);
        $this->assertFalse($sale->was_offline);
    }

    public function test_decrements_machine_stock_after_sale(): void
    {
        $product = Product::factory()->create();
        $this->seedMachineStock($product, 8);

        $items = collect([$product->id => ['quantity' => 3, 'unit_price' => 2000.0]]);

        $this->command->handle($this->driver, $this->machine, $this->machineWarehouse, $items, null);

        $remaining = Stock::where('warehouse_id', $this->machineWarehouse->id)
            ->where('product_id', $product->id)
            ->value('quantity');

        $this->assertSame(5, $remaining);
    }

    public function test_creates_sale_items_with_quantity_and_price(): void
    {
        $product = Product::factory()->create();
        $this->seedMachineStock($product, 10);

        $items = collect([$product->id => ['quantity' => 2, 'unit_price' => 4500.0]]);

        $sale = $this->command->handle($this->driver, $this->machine, $this->machineWarehouse, $items, null);

        $saleItem = MachineSaleItem::where('machine_sale_id', $sale->id)->first();

        $this->assertNotNull($saleItem);
        $this->assertSame(2, $saleItem->quantity_sold);
        $this->assertSame('4500.00', (string) $saleItem->unit_price);
    }

    public function test_creates_stock_movement_of_type_venta_maquina(): void
    {
        $product = Product::factory()->create();
        $this->seedMachineStock($product, 10);

        $items = collect([$product->id => ['quantity' => 4, 'unit_price' => 1500.0]]);

        $sale = $this->command->handle($this->driver, $this->machine, $this->machineWarehouse, $items, null);

        $movement = StockMovement::where('reference_code', $sale->code)->first();

        $this->assertNotNull($movement);
        $this->assertSame('venta_maquina', $movement->movement_type);
        $this->assertSame($this->machineWarehouse->id, $movement->origin_warehouse_id);
        $this->assertNull($movement->destination_warehouse_id);
        $this->assertSame(4, $movement->quantity);
        $this->assertSame('1500.00', (string) $movement->unit_cost);
    }

    public function test_sale_stores_notes(): void
    {
        $product = Product::factory()->create();
        $this->seedMachineStock($product, 5);

        $items = collect([$product->id => ['quantity' => 1, 'unit_price' => 0.0]]);

        $sale = $this->command->handle(
            $this->driver, $this->machine, $this->machineWarehouse,
            $items, 'Venta especial fin de jornada',
        );

        $this->assertSame('Venta especial fin de jornada', $sale->notes);
    }

    public function test_sale_date_is_today(): void
    {
        $product = Product::factory()->create();
        $this->seedMachineStock($product, 5);

        $items = collect([$product->id => ['quantity' => 1, 'unit_price' => 1000.0]]);

        $sale = $this->command->handle($this->driver, $this->machine, $this->machineWarehouse, $items, null);

        $this->assertTrue($sale->sale_date->isToday());
    }

    public function test_multiple_products_create_separate_movements(): void
    {
        $p1 = Product::factory()->create();
        $p2 = Product::factory()->create();
        $this->seedMachineStock($p1, 10);
        $this->seedMachineStock($p2, 10);

        $items = collect([
            $p1->id => ['quantity' => 2, 'unit_price' => 3000.0],
            $p2->id => ['quantity' => 3, 'unit_price' => 2000.0],
        ]);

        $sale = $this->command->handle($this->driver, $this->machine, $this->machineWarehouse, $items, null);

        $this->assertSame(2, MachineSaleItem::where('machine_sale_id', $sale->id)->count());
        $this->assertSame(2, StockMovement::where('reference_code', $sale->code)->count());
    }

    public function test_unit_price_zero_is_accepted(): void
    {
        $product = Product::factory()->create();
        $this->seedMachineStock($product, 5);

        $items = collect([$product->id => ['quantity' => 1, 'unit_price' => 0.0]]);

        $sale = $this->command->handle($this->driver, $this->machine, $this->machineWarehouse, $items, null);

        $this->assertNotNull($sale->id);
    }

    // ──────────────────────────────────────────────────────────────
    // STOCK INSUFICIENTE
    // ──────────────────────────────────────────────────────────────

    public function test_throws_exception_when_machine_stock_insufficient(): void
    {
        $product = Product::factory()->create(['name' => 'Refresco Cola']);
        $this->seedMachineStock($product, 2);

        $items = collect([$product->id => ['quantity' => 10, 'unit_price' => 2500.0]]);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessageMatches('/Stock insuficiente en máquina/');
        $this->expectExceptionMessageMatches('/Refresco Cola/');

        $this->command->handle($this->driver, $this->machine, $this->machineWarehouse, $items, null);
    }

    public function test_exception_shows_available_and_requested_quantities(): void
    {
        $product = Product::factory()->create(['name' => 'Snack X']);
        $this->seedMachineStock($product, 1);

        $items = collect([$product->id => ['quantity' => 5, 'unit_price' => 1000.0]]);

        try {
            $this->command->handle($this->driver, $this->machine, $this->machineWarehouse, $items, null);
            $this->fail('RuntimeException expected');
        } catch (\RuntimeException $e) {
            $this->assertStringContainsString('1', $e->getMessage());
            $this->assertStringContainsString('5', $e->getMessage());
        }
    }

    public function test_throws_when_product_has_no_stock_in_machine(): void
    {
        $product = Product::factory()->create();
        // Sin stock en bodega máquina

        $items = collect([$product->id => ['quantity' => 1, 'unit_price' => 1000.0]]);

        $this->expectException(\RuntimeException::class);

        $this->command->handle($this->driver, $this->machine, $this->machineWarehouse, $items, null);
    }

    public function test_transaction_rolls_back_on_insufficient_stock_midway(): void
    {
        $p1 = Product::factory()->create();
        $p2 = Product::factory()->create();
        $this->seedMachineStock($p1, 10);
        // p2 sin stock

        $items = collect([
            $p1->id => ['quantity' => 3, 'unit_price' => 1000.0],
            $p2->id => ['quantity' => 5, 'unit_price' => 2000.0],
        ]);

        try {
            $this->command->handle($this->driver, $this->machine, $this->machineWarehouse, $items, null);
        } catch (\RuntimeException) {
            $this->assertSame(0, MachineSale::count());
            $this->assertSame(0, StockMovement::count());
        }
    }

    public function test_exact_stock_match_is_allowed(): void
    {
        $product = Product::factory()->create();
        $this->seedMachineStock($product, 4);

        $items = collect([$product->id => ['quantity' => 4, 'unit_price' => 5000.0]]);

        $sale = $this->command->handle($this->driver, $this->machine, $this->machineWarehouse, $items, null);

        $this->assertNotNull($sale->id);
        $remaining = Stock::where('warehouse_id', $this->machineWarehouse->id)
            ->where('product_id', $product->id)
            ->value('quantity');
        $this->assertSame(0, $remaining);
    }

    // ──────────────────────────────────────────────────────────────
    // HELPERS
    // ──────────────────────────────────────────────────────────────

    private function seedMachineStock(Product $product, int $qty): void
    {
        Stock::factory()
            ->forWarehouse($this->machineWarehouse)
            ->forProduct($product)
            ->withQuantity($qty)
            ->create();
    }
}
