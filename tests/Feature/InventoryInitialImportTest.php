<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

final class InventoryInitialImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_import_screen_can_be_rendered_for_authorized_user(): void
    {
        $user = $this->authorizedUser();
        Warehouse::create([
            'name' => 'Bodega Principal',
            'code' => 'BODEGA',
            'type' => 'bodega',
            'is_active' => true,
        ]);

        $response = $this->actingAs($user)->get(route('inventory.import.form'));

        $response->assertOk();
        $response->assertSee('Importar carga inicial');
    }

    public function test_template_can_be_downloaded(): void
    {
        $user = $this->authorizedUser();

        $response = $this->actingAs($user)->get(route('inventory.import.template'));

        $response->assertOk();
        $response->assertHeader('content-disposition');
    }

    public function test_initial_inventory_can_be_imported_from_csv(): void
    {
        $user = $this->authorizedUser();
        $warehouse = Warehouse::create([
            'name' => 'Bodega Principal',
            'code' => 'BODEGA',
            'type' => 'bodega',
            'is_active' => true,
        ]);

        $product = Product::create([
            'code' => '124',
            'worldoffice_code' => '124',
            'name' => 'COCOSETTE WAFER',
            'category' => 'snack',
            'unit_of_measure' => 'Und.',
            'unit_price' => 1500,
            'min_stock_alert' => 5,
            'is_active' => true,
        ]);

        $file = UploadedFile::fake()->createWithContent(
            'inventario-inicial.csv',
            "codigo_producto,cantidad_inicial,observaciones\n124,36,Carga inicial abril\n"
        );

        $response = $this->actingAs($user)->post(route('inventory.import.store'), [
            'inventory_file' => $file,
        ]);

        $response->assertRedirect(route('inventory.import.form'));

        $this->assertDatabaseHas('stock', [
            'warehouse_id' => $warehouse->id,
            'product_id' => $product->id,
            'quantity' => 36.000,
        ]);

        $this->assertDatabaseHas('excel_imports', [
            'import_type' => 'inventario_inicial',
            'status' => 'completado',
            'processed_rows' => 1,
        ]);
    }

    private function authorizedUser(): User
    {
        Permission::findOrCreate('inventory.load_excel', 'web');

        $user = User::factory()->create();
        $user->givePermissionTo('inventory.load_excel');

        return $user;
    }
}
