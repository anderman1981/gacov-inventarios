<?php

declare(strict_types=1);

namespace Tests\Feature\Inventory;

use App\Models\Product;
use App\Models\PurchaseImportBatch;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

final class PurchaseImportTest extends TestCase
{
    public function test_purchase_csv_is_staged_without_touching_stock_until_confirmation(): void
    {
        Storage::fake('local');

        [$user, $warehouse, $product] = $this->seedPurchaseContext();
        $file = UploadedFile::fake()->createWithContent(
            'compras.csv',
            "codigo_producto;cantidad;costo_unitario;proveedor;factura;fecha_compra;observaciones\n"
            ."P001;12;1500;Proveedor Uno;FC-001;2026-04-30;Compra de reposición\n"
        );

        $response = $this->actingAs($user)->post(route('inventory.purchases.store'), [
            'purchase_file' => $file,
        ]);

        $batch = PurchaseImportBatch::query()->firstOrFail();

        $response->assertRedirect(route('inventory.purchases.show', $batch));
        $this->assertSame('borrador', $batch->status);
        $this->assertSame(1, $batch->valid_rows);
        $this->assertSame(0, $batch->error_rows);
        $this->assertDatabaseMissing('stock', [
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
        ]);
        $this->assertDatabaseMissing('stock_movements', [
            'movement_type' => 'compra',
            'product_id' => $product->id,
        ]);

        $this->actingAs($user)
            ->post(route('inventory.purchases.confirm', $batch))
            ->assertRedirect(route('inventory.purchases.show', $batch));

        $this->assertDatabaseHas('purchase_import_batches', [
            'id' => $batch->id,
            'status' => 'procesado',
        ]);
        $this->assertDatabaseHas('stock', [
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'quantity' => 12,
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'movement_type' => 'compra',
            'destination_warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'quantity' => 12,
            'reference_code' => 'COMPRA-FC-001-2',
        ]);
        $this->assertSame('Proveedor Uno', $product->fresh()->supplier);
        $this->assertSame('1500.00', (string) $product->fresh()->cost);
    }

    public function test_purchase_csv_with_errors_cannot_be_confirmed(): void
    {
        Storage::fake('local');

        [$user] = $this->seedPurchaseContext();
        $file = UploadedFile::fake()->createWithContent(
            'compras-error.csv',
            "codigo_producto;cantidad;costo_unitario\n"
            ."NO-EXISTE;8;1200\n"
        );

        $this->actingAs($user)->post(route('inventory.purchases.store'), [
            'purchase_file' => $file,
        ]);

        $batch = PurchaseImportBatch::query()->firstOrFail();

        $this->assertSame('borrador', $batch->status);
        $this->assertSame(0, $batch->valid_rows);
        $this->assertSame(1, $batch->error_rows);

        $this->actingAs($user)
            ->post(route('inventory.purchases.confirm', $batch))
            ->assertSessionHas('error');

        $this->assertSame('borrador', $batch->fresh()->status);
        $this->assertSame(0, StockMovement::query()->where('movement_type', 'compra')->count());
    }

    /**
     * @return array{0:User,1:Warehouse,2:Product}
     */
    private function seedPurchaseContext(): array
    {
        $tenant = Tenant::factory()->create();
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'is_super_admin' => true,
        ]);
        $user->syncRoles(['admin']);
        $user->givePermissionTo('inventory.load_excel');
        $user->givePermissionTo('inventory.view');

        $warehouse = Warehouse::factory()->create([
            'tenant_id' => $tenant->id,
            'type' => 'bodega',
            'name' => 'Bodega Principal',
        ]);

        $product = Product::factory()->create([
            'tenant_id' => $tenant->id,
            'code' => 'P001',
            'name' => 'Producto Compra',
            'cost' => 0,
            'supplier' => null,
        ]);

        return [$user, $warehouse, $product];
    }
}
