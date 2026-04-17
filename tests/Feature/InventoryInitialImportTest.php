<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\AppModule;
use App\Models\Product;
use App\Models\Tenant;
use App\Models\TenantBillingProfile;
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
        $tenant = $this->createTenant();
        $user = $this->authorizedUser($tenant);
        $warehouse = new Warehouse([
            'name' => 'Bodega Principal',
            'code' => 'BODEGA',
            'type' => 'bodega',
            'is_active' => true,
        ]);
        $warehouse->tenant_id = $tenant->id;
        $warehouse->save();

        $response = $this->actingAs($user)->get(route('inventory.import.form'));

        // Debug: show redirect location if 302
        if ($response->status() === 302) {
            $this->fail('Redirected to: '.$response->headers->get('Location'));
        }

        $response->assertOk();
        $response->assertSee('Importar carga inicial');
    }

    public function test_template_can_be_downloaded(): void
    {
        $tenant = $this->createTenant();
        $user = $this->authorizedUser($tenant);

        $response = $this->actingAs($user)->get(route('inventory.import.template'));

        $response->assertOk();
        $response->assertHeader('content-disposition');
    }

    public function test_initial_inventory_can_be_imported_from_csv(): void
    {
        $tenant = $this->createTenant();
        $user = $this->authorizedUser($tenant);
        $warehouse = new Warehouse([
            'name' => 'Bodega Principal',
            'code' => 'BODEGA',
            'type' => 'bodega',
            'is_active' => true,
        ]);
        $warehouse->tenant_id = $tenant->id;
        $warehouse->save();

        $product = new Product([
            'code' => '124',
            'worldoffice_code' => '124',
            'name' => 'COCOSETTE WAFER',
            'category' => 'snack',
            'unit_of_measure' => 'Und.',
            'unit_price' => 1500,
            'min_stock_alert' => 5,
            'is_active' => true,
        ]);
        $product->tenant_id = $tenant->id;
        $product->save();

        $filePath = tempnam(sys_get_temp_dir(), 'inventory-import-');
        file_put_contents(
            $filePath,
            "codigo_producto,cantidad_inicial,observaciones\n124,36,Carga inicial abril\n"
        );

        $file = new UploadedFile(
            $filePath,
            'inventario-inicial.csv',
            'text/csv',
            null,
            true
        );

        $response = $this->actingAs($user)
            ->from(route('inventory.import.form'))
            ->post(route('inventory.import.store'), [
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

        @unlink($filePath);
    }

    private function createTenant(): Tenant
    {
        // Create inventory module if not exists
        AppModule::firstOrCreate(
            ['key' => 'inventory'],
            [
                'name' => 'Inventario',
                'phase_required' => 1,
                'sort_order' => 2,
                'is_active' => true,
            ]
        );

        $tenant = Tenant::create([
            'name' => 'Tenant de prueba',
            'slug' => 'tenant-prueba-'.uniqid(),
            'email' => 'tenant@example.com',
            'is_active' => true,
        ]);

        // Create billing profile with phase 1 access for module middleware
        TenantBillingProfile::create([
            'tenant_id' => $tenant->id,
            ...TenantBillingProfile::defaultPayload(1),
        ]);

        return $tenant;
    }

    private function authorizedUser(Tenant $tenant): User
    {
        // Create required permissions
        Permission::findOrCreate('inventory.view', 'web');
        Permission::findOrCreate('inventory.load_excel', 'web');

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
        ]);
        $user->givePermissionTo('inventory.view');
        $user->givePermissionTo('inventory.load_excel');

        return $user;
    }
}
