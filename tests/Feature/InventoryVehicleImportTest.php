<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AppModule;
use App\Models\Product;
use App\Models\Route;
use App\Models\Stock;
use App\Models\Tenant;
use App\Models\User;
use App\Models\Warehouse;
use App\Notifications\InventoryAdjustmentNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

final class InventoryVehicleImportTest extends TestCase
{
    use RefreshDatabase;

    public function test_vehicle_import_screen_can_be_rendered_for_manager_with_permission(): void
    {
        $tenant = $this->createTenant();
        $manager = $this->authorizedManager($tenant);

        $response = $this->actingAs($manager)->get(route('inventory.vehicles.import.form'));

        $response->assertOk();
        $response->assertSee('Carga masiva de vehículos');
    }

    public function test_vehicle_template_can_be_downloaded(): void
    {
        $tenant = $this->createTenant();
        $manager = $this->authorizedManager($tenant);

        $response = $this->actingAs($manager)->get(route('inventory.vehicles.import.template'));

        $response->assertOk();
        $response->assertHeader('content-disposition');
    }

    public function test_vehicle_inventory_can_be_imported_from_csv(): void
    {
        Notification::fake();

        $tenant = $this->createTenant();
        $manager = $this->authorizedManager($tenant);
        $route = $this->createRouteWithVehicleWarehouse($tenant, 'RT1');
        $product = $this->createProduct($tenant, '124', 'COCOSETTE WAFER');

        $filePath = tempnam(sys_get_temp_dir(), 'vehicle-import-');
        file_put_contents(
            $filePath,
            "codigo_ruta,codigo_producto,cantidad_total,observaciones\nRT1,124,36,Carga inicial ruta 1\n"
        );

        $file = new UploadedFile(
            $filePath,
            'inventario-vehiculos.csv',
            'text/csv',
            null,
            true
        );

        $response = $this->actingAs($manager)
            ->from(route('inventory.vehicles.import.form'))
            ->post(route('inventory.vehicles.import.store'), [
                'inventory_file' => $file,
            ]);

        $response->assertRedirect(route('inventory.vehicles.import.form'));
        $this->assertDatabaseHas('stock', [
            'warehouse_id' => $route['warehouse']->id,
            'product_id' => $product->id,
            'quantity' => 36.000,
        ]);
        $this->assertDatabaseHas('excel_imports', [
            'import_type' => 'inventario_vehiculos',
            'status' => 'completado',
            'processed_rows' => 1,
        ]);
        Notification::assertNothingSent();

        @unlink($filePath);
    }

    public function test_vehicle_import_requires_observation_after_initial_load_and_notifies_admin(): void
    {
        Notification::fake();

        $tenant = $this->createTenant();
        $manager = $this->authorizedManager($tenant);
        $admin = $this->adminRecipient($tenant);
        $route = $this->createRouteWithVehicleWarehouse($tenant, 'RT2');
        $product = $this->createProduct($tenant, '220', 'AGUA 600ML');

        $existingStock = new Stock([
            'warehouse_id' => $route['warehouse']->id,
            'product_id' => $product->id,
            'quantity' => 10,
            'min_quantity' => 5,
        ]);
        $existingStock->tenant_id = $tenant->id;
        $existingStock->save();

        $missingReasonFilePath = tempnam(sys_get_temp_dir(), 'vehicle-import-missing-');
        file_put_contents(
            $missingReasonFilePath,
            "codigo_ruta,codigo_producto,cantidad_total,observaciones\nRT2,220,14,\n"
        );

        $missingReasonFile = new UploadedFile(
            $missingReasonFilePath,
            'inventario-vehiculos-sin-observacion.csv',
            'text/csv',
            null,
            true
        );

        $this->actingAs($manager)
            ->from(route('inventory.vehicles.import.form'))
            ->post(route('inventory.vehicles.import.store'), [
                'inventory_file' => $missingReasonFile,
            ])
            ->assertRedirect(route('inventory.vehicles.import.form'))
            ->assertSessionHas('error');

        $this->assertDatabaseHas('stock', [
            'warehouse_id' => $route['warehouse']->id,
            'product_id' => $product->id,
            'quantity' => 10.000,
        ]);

        $validFilePath = tempnam(sys_get_temp_dir(), 'vehicle-import-valid-');
        file_put_contents(
            $validFilePath,
            "codigo_ruta,codigo_producto,cantidad_total,observaciones\nRT2,220,14,Ajuste por conteo de cierre de ruta.\n"
        );

        $validFile = new UploadedFile(
            $validFilePath,
            'inventario-vehiculos-ajuste.csv',
            'text/csv',
            null,
            true
        );

        $response = $this->actingAs($manager)
            ->from(route('inventory.vehicles.import.form'))
            ->post(route('inventory.vehicles.import.store'), [
                'inventory_file' => $validFile,
            ]);

        $response->assertRedirect(route('inventory.vehicles.import.form'));
        $this->assertDatabaseHas('stock', [
            'warehouse_id' => $route['warehouse']->id,
            'product_id' => $product->id,
            'quantity' => 14.000,
        ]);
        $this->assertDatabaseHas('stock_movements', [
            'destination_warehouse_id' => $route['warehouse']->id,
            'product_id' => $product->id,
            'movement_type' => 'ajuste_manual',
            'quantity' => 4,
        ]);
        Notification::assertSentTo(
            $admin,
            InventoryAdjustmentNotification::class
        );

        @unlink($missingReasonFilePath);
        @unlink($validFilePath);
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
        Permission::findOrCreate('inventory.load_vehicle_excel', 'web');

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'is_active' => true,
            'is_super_admin' => false,
        ]);
        $user->syncRoles(['manager']);
        $user->givePermissionTo('inventory.view');
        $user->givePermissionTo('inventory.load_vehicle_excel');

        return $user;
    }

    private function adminRecipient(Tenant $tenant): User
    {
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'is_active' => true,
            'is_super_admin' => false,
        ]);
        $user->syncRoles(['admin']);

        return $user;
    }

    /**
     * @return array{route: Route, warehouse: Warehouse}
     */
    private function createRouteWithVehicleWarehouse(Tenant $tenant, string $routeCode): array
    {
        $route = Route::factory()->create([
            'tenant_id' => $tenant->id,
            'code' => $routeCode,
            'name' => 'Ruta '.$routeCode,
            'is_active' => true,
        ]);

        $warehouse = Warehouse::factory()->vehiculo()->create([
            'tenant_id' => $tenant->id,
            'route_id' => $route->id,
            'code' => $routeCode,
            'name' => 'Vehículo '.$routeCode,
            'is_active' => true,
        ]);

        return [
            'route' => $route,
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
