<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AppModule;
use App\Models\Machine;
use App\Models\Product;
use App\Models\Route;
use App\Models\Stock;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

final class InventoryMachineInitialImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_machine_import_screen_can_be_rendered_for_manager_with_permission(): void
    {
        $tenant = $this->createTenant();
        $manager = $this->authorizedManager($tenant);

        $response = $this->actingAs($manager)->get(route('inventory.machines.import.form'));

        $response->assertOk();
        $response->assertSee('Carga inicial masiva de maquinas');
    }

    public function test_machine_initial_template_can_be_downloaded(): void
    {
        $tenant = $this->createTenant();
        $manager = $this->authorizedManager($tenant);

        $response = $this->actingAs($manager)->get(route('inventory.machines.import.template'));

        $response->assertOk();
        $response->assertHeader('content-disposition');
    }

    public function test_machine_initial_inventory_can_be_imported_from_csv(): void
    {
        $tenant = $this->createTenant();
        $manager = $this->authorizedManager($tenant);
        $machine = $this->createMachineWithWarehouse($tenant, 'M104');
        $product = $this->createProduct($tenant, '124', 'COCOSETTE WAFER');

        $filePath = tempnam(sys_get_temp_dir(), 'machine-import-');
        file_put_contents(
            $filePath,
            "codigo_maquina,codigo_producto,cantidad_inicial,observaciones\nM104,124,18,Carga inicial de la maquina M104\n"
        );

        $file = new UploadedFile(
            $filePath,
            'inventario-maquinas.csv',
            'text/csv',
            null,
            true
        );

        $response = $this->actingAs($manager)
            ->from(route('inventory.machines.import.form'))
            ->post(route('inventory.machines.import.store'), [
                'inventory_file' => $file,
            ]);

        $response->assertRedirect(route('inventory.machines.import.form'));
        $this->assertDatabaseHas('stock', [
            'warehouse_id' => $machine['warehouse']->id,
            'product_id' => $product->id,
            'quantity' => 18.000,
        ]);
        $this->assertDatabaseHas('excel_imports', [
            'import_type' => 'inventario_maquinas_inicial',
            'status' => 'completado',
            'processed_rows' => 1,
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'destination_warehouse_id' => $machine['warehouse']->id,
            'product_id' => $product->id,
            'movement_type' => 'carga_inicial',
            'quantity' => 18,
        ]);

        @unlink($filePath);
    }

    public function test_machine_initial_import_rejects_already_initialized_machine(): void
    {
        $tenant = $this->createTenant();
        $manager = $this->authorizedManager($tenant);
        $machine = $this->createMachineWithWarehouse($tenant, 'M205');
        $product = $this->createProduct($tenant, '220', 'AGUA 600ML');

        Stock::create([
            'tenant_id' => $tenant->id,
            'warehouse_id' => $machine['warehouse']->id,
            'product_id' => $product->id,
            'quantity' => 10,
            'min_quantity' => 5,
        ]);

        $filePath = tempnam(sys_get_temp_dir(), 'machine-import-blocked-');
        file_put_contents(
            $filePath,
            "codigo_maquina,codigo_producto,cantidad_inicial,observaciones\nM205,220,14,Intento de reimportacion\n"
        );

        $file = new UploadedFile(
            $filePath,
            'inventario-maquinas-bloqueado.csv',
            'text/csv',
            null,
            true
        );

        $response = $this->actingAs($manager)
            ->from(route('inventory.machines.import.form'))
            ->post(route('inventory.machines.import.store'), [
                'inventory_file' => $file,
            ]);

        $response->assertRedirect(route('inventory.machines.import.form'));
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('stock', [
            'warehouse_id' => $machine['warehouse']->id,
            'product_id' => $product->id,
            'quantity' => 10.000,
        ]);

        @unlink($filePath);
    }

    private function createTenant(): Tenant
    {
        $this->ensureInventoryModule();

        return Tenant::create([
            'name' => 'Tenant de prueba',
            'slug' => 'tenant-prueba',
            'email' => 'tenant@example.com',
            'is_active' => true,
        ]);
    }

    private function authorizedManager(Tenant $tenant): User
    {
        Permission::findOrCreate('inventory.view', 'web');
        Permission::findOrCreate('inventory.load_machine_excel', 'web');

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'is_active' => true,
            'is_super_admin' => false,
        ]);
        $user->syncRoles(['manager']);
        $user->givePermissionTo('inventory.view');
        $user->givePermissionTo('inventory.load_machine_excel');

        return $user;
    }

    /**
     * @return array{machine: Machine, warehouse: Warehouse}
     */
    private function createMachineWithWarehouse(Tenant $tenant, string $machineCode): array
    {
        $route = Route::factory()->create([
            'tenant_id' => $tenant->id,
            'code' => 'RT-'.$machineCode,
            'name' => 'Ruta '.$machineCode,
            'is_active' => true,
        ]);

        $machine = Machine::factory()->create([
            'tenant_id' => $tenant->id,
            'route_id' => $route->id,
            'code' => $machineCode,
            'name' => 'Maquina '.$machineCode,
            'is_active' => true,
        ]);

        $warehouse = Warehouse::factory()->maquina()->create([
            'tenant_id' => $tenant->id,
            'machine_id' => $machine->id,
            'code' => $machineCode,
            'name' => 'Bodega '.$machineCode,
            'is_active' => true,
        ]);

        return [
            'machine' => $machine,
            'warehouse' => $warehouse,
        ];
    }

    private function createProduct(Tenant $tenant, string $code, string $name): Product
    {
        return Product::factory()->create([
            'tenant_id' => $tenant->id,
            'code' => $code,
            'worldoffice_code' => $code,
            'name' => $name,
            'is_active' => true,
        ]);
    }

    private function ensureInventoryModule(): void
    {
        AppModule::query()->firstOrCreate(
            ['key' => 'inventory'],
            [
                'name' => 'Inventario',
                'phase_required' => 1,
                'sort_order' => 1,
                'is_active' => true,
            ]
        );
    }
}
