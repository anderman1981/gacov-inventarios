<?php

declare(strict_types=1);

namespace Tests\Feature\WorldOffice;

use App\Models\Machine;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockMovement;
use App\Models\AppModule;
use App\Models\Route as ClientRoute;
use App\Models\Tenant;
use App\Models\TenantBillingProfile;
use App\Models\User;
use App\Models\Warehouse;
use App\Support\WorldOffice\WorldOfficeExportService;
use Tests\TestCase;

final class WorldOfficeControllerTest extends TestCase
{
    public function test_allowed_roles_can_open_worldoffice_index(): void
    {
        [$tenant, $route] = $this->seedTenantContext();

        foreach (['admin', 'manager', 'contador'] as $role) {
            $user = $this->makeTenantUser($tenant, $role, "{$role}-worldoffice@example.com", $route);

            $this->actingAs($user)->get(route('worldoffice.index'))->assertOk();
        }
    }

    public function test_conductor_cannot_access_worldoffice(): void
    {
        [$tenant, $route] = $this->seedTenantContext();

        $conductor = $this->makeTenantUser($tenant, 'conductor', 'conductor-worldoffice@example.com', $route);

        $this->actingAs($conductor)->get(route('worldoffice.index'))->assertForbidden();
        $this->actingAs($conductor)->get(route('worldoffice.download', ['category' => 'bodega', 'direction' => 'load']))->assertForbidden();
    }

    public function test_worldoffice_downloads_generate_all_six_formats(): void
    {
        [$tenant, $route] = $this->seedTenantContext();

        $manager = $this->makeTenantUser($tenant, 'manager', 'manager-worldoffice@example.com', $route);

        $cases = [
            ['category' => 'bodega', 'direction' => 'load', 'filename' => 'worldoffice-bodega-carga.xlsx'],
            ['category' => 'bodega', 'direction' => 'unload', 'filename' => 'worldoffice-bodega-descarga.xlsx'],
            ['category' => 'routes', 'direction' => 'load', 'filename' => 'worldoffice-vehiculos-carga.xlsx'],
            ['category' => 'routes', 'direction' => 'unload', 'filename' => 'worldoffice-vehiculos-descarga.xlsx'],
            ['category' => 'machines', 'direction' => 'load', 'filename' => 'worldoffice-maquinas-carga.xlsx'],
            ['category' => 'machines', 'direction' => 'unload', 'filename' => 'worldoffice-maquinas-descarga.xlsx'],
        ];

        foreach ($cases as $case) {
            $response = $this->actingAs($manager)->get(route('worldoffice.download', [
                'category' => $case['category'],
                'direction' => $case['direction'],
            ]));

            $response->assertOk();
            $response->assertHeader('content-disposition');
            $this->assertStringContainsString($case['filename'], (string) $response->headers->get('content-disposition'));
        }
    }

    public function test_worldoffice_separates_loads_and_unloads_by_warehouse_type(): void
    {
        [$tenant, $route] = $this->seedTenantContext();
        $manager = $this->makeTenantUser($tenant, 'manager', 'manager-worldoffice-movements@example.com', $route);

        $bodega = Warehouse::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Bodega Principal',
            'code' => 'BODEGA',
            'type' => 'bodega',
            'route_id' => null,
            'machine_id' => null,
            'is_active' => true,
        ]);

        $vehicleWarehouse = Warehouse::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Vehículo Ruta 1',
            'code' => 'VH-RT1',
            'type' => 'vehiculo',
            'route_id' => $route->id,
            'machine_id' => null,
            'is_active' => true,
        ]);

        $machine = Machine::query()->create([
            'tenant_id' => $tenant->id,
            'code' => 'MAQ-001',
            'worldoffice_code' => 'WO-MAQ-001',
            'name' => 'Máquina Lobby',
            'location' => 'Recepción',
            'route_id' => $route->id,
            'type' => 'mixta',
            'is_active' => true,
        ]);

        $machineWarehouse = Warehouse::query()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Bodega Máquina Lobby',
            'code' => 'WH-MAQ-001',
            'type' => 'maquina',
            'route_id' => $route->id,
            'machine_id' => $machine->id,
            'is_active' => true,
        ]);

        $product = Product::query()->create([
            'tenant_id' => $tenant->id,
            'code' => 'PROD-001',
            'worldoffice_code' => 'WO-PROD-001',
            'name' => 'Agua 600ML',
            'category' => 'bebida_fria',
            'unit_of_measure' => 'Und.',
            'cost' => 1000,
            'min_sale_price' => 1500,
            'unit_price' => 1800,
            'min_stock_alert' => 5,
            'is_active' => true,
        ]);

        Stock::query()->create([
            'tenant_id' => $tenant->id,
            'warehouse_id' => $bodega->id,
            'product_id' => $product->id,
            'quantity' => 30,
            'min_quantity' => 5,
        ]);

        Stock::query()->create([
            'tenant_id' => $tenant->id,
            'warehouse_id' => $vehicleWarehouse->id,
            'product_id' => $product->id,
            'quantity' => 12,
            'min_quantity' => 5,
        ]);

        Stock::query()->create([
            'tenant_id' => $tenant->id,
            'warehouse_id' => $machineWarehouse->id,
            'product_id' => $product->id,
            'quantity' => 6,
            'min_quantity' => 5,
        ]);

        StockMovement::query()->create([
            'tenant_id' => $tenant->id,
            'movement_type' => 'carga_inicial',
            'origin_warehouse_id' => null,
            'destination_warehouse_id' => $bodega->id,
            'product_id' => $product->id,
            'quantity' => 30,
            'reference_code' => 'WO-LOAD-001',
            'notes' => 'Carga inicial WorldOffice',
            'performed_by' => $manager->id,
        ]);

        StockMovement::query()->create([
            'tenant_id' => $tenant->id,
            'movement_type' => 'traslado_salida',
            'origin_warehouse_id' => $bodega->id,
            'destination_warehouse_id' => $vehicleWarehouse->id,
            'product_id' => $product->id,
            'quantity' => 12,
            'reference_code' => 'TR-001',
            'notes' => 'Salida desde bodega',
            'performed_by' => $manager->id,
        ]);

        StockMovement::query()->create([
            'tenant_id' => $tenant->id,
            'movement_type' => 'traslado_entrada',
            'origin_warehouse_id' => $bodega->id,
            'destination_warehouse_id' => $vehicleWarehouse->id,
            'product_id' => $product->id,
            'quantity' => 12,
            'reference_code' => 'TR-001',
            'notes' => 'Entrada a vehículo',
            'performed_by' => $manager->id,
        ]);

        StockMovement::query()->create([
            'tenant_id' => $tenant->id,
            'movement_type' => 'surtido_maquina',
            'origin_warehouse_id' => $vehicleWarehouse->id,
            'destination_warehouse_id' => $machineWarehouse->id,
            'product_id' => $product->id,
            'quantity' => 6,
            'reference_code' => 'SURT-001',
            'notes' => 'Surtido máquina',
            'performed_by' => $manager->id,
        ]);

        StockMovement::query()->create([
            'tenant_id' => $tenant->id,
            'movement_type' => 'venta_maquina',
            'origin_warehouse_id' => $machineWarehouse->id,
            'destination_warehouse_id' => null,
            'product_id' => $product->id,
            'quantity' => 2,
            'reference_code' => 'VEN-001',
            'notes' => 'Venta máquina',
            'performed_by' => $manager->id,
        ]);

        /** @var WorldOfficeExportService $service */
        $service = app(WorldOfficeExportService::class);

        $bodegaLoad = $service->definition('bodega', 'load');
        $bodegaUnload = $service->definition('bodega', 'unload');
        $routeLoad = $service->definition('routes', 'load');
        $routeUnload = $service->definition('routes', 'unload');
        $machineLoad = $service->definition('machines', 'load');
        $machineUnload = $service->definition('machines', 'unload');

        $this->assertSame('worldoffice-bodega-carga.xlsx', $bodegaLoad['filename']);
        $this->assertSame('WO-PROD-001', $bodegaLoad['rows'][0][2]);
        $this->assertSame(30, $bodegaLoad['rows'][0][6]);

        $this->assertSame('worldoffice-bodega-descarga.xlsx', $bodegaUnload['filename']);
        $this->assertSame(12, $bodegaUnload['rows'][0][6]);

        $this->assertSame('worldoffice-vehiculos-carga.xlsx', $routeLoad['filename']);
        $this->assertSame(12, $routeLoad['rows'][0][7]);

        $this->assertSame('worldoffice-vehiculos-descarga.xlsx', $routeUnload['filename']);
        $this->assertSame(6, $routeUnload['rows'][0][7]);

        $this->assertSame('worldoffice-maquinas-carga.xlsx', $machineLoad['filename']);
        $this->assertSame('WO-MAQ-001', $machineLoad['rows'][0][0]);
        $this->assertSame(6, $machineLoad['rows'][0][8]);

        $this->assertSame('worldoffice-maquinas-descarga.xlsx', $machineUnload['filename']);
        $this->assertSame(2, $machineUnload['rows'][0][8]);
    }

    /**
     * @return array{0: Tenant, 1: ClientRoute}
     */
    private function seedTenantContext(): array
    {
        foreach ([
            ['key' => 'dashboard', 'name' => 'Dashboard', 'phase_required' => 1, 'sort_order' => 1],
            ['key' => 'users', 'name' => 'Usuarios', 'phase_required' => 1, 'sort_order' => 2],
            ['key' => 'inventory', 'name' => 'Inventario', 'phase_required' => 1, 'sort_order' => 3],
            ['key' => 'products', 'name' => 'Productos', 'phase_required' => 1, 'sort_order' => 4],
            ['key' => 'machines', 'name' => 'Máquinas', 'phase_required' => 1, 'sort_order' => 5],
            ['key' => 'transfers', 'name' => 'Traslados', 'phase_required' => 1, 'sort_order' => 6],
            ['key' => 'drivers', 'name' => 'Conductores', 'phase_required' => 1, 'sort_order' => 7],
            ['key' => 'sales', 'name' => 'Ventas', 'phase_required' => 2, 'sort_order' => 8],
            ['key' => 'reports', 'name' => 'Reportes', 'phase_required' => 2, 'sort_order' => 9],
            ['key' => 'invoices', 'name' => 'Facturas', 'phase_required' => 1, 'sort_order' => 10],
        ] as $module) {
            AppModule::query()->updateOrCreate(
                ['key' => $module['key']],
                array_merge($module, ['is_active' => true])
            );
        }

        $tenant = Tenant::factory()->create();
        TenantBillingProfile::create([
            'tenant_id' => $tenant->id,
            ...TenantBillingProfile::defaultPayload(2),
        ]);

        $route = ClientRoute::factory()->create([
            'tenant_id' => $tenant->id,
            'is_active' => true,
        ]);

        Warehouse::factory()->create([
            'tenant_id' => $tenant->id,
            'route_id' => null,
            'type' => 'bodega',
            'is_active' => true,
        ]);

        return [$tenant, $route];
    }

    private function makeTenantUser(Tenant $tenant, string $role, string $email, ClientRoute $route): User
    {
        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'email' => $email,
            'is_super_admin' => false,
            'must_change_password' => false,
            'route_id' => $role === 'conductor' ? $route->id : null,
        ]);
        $user->syncRoles([$role]);

        if ($role === 'conductor') {
            $route->update(['driver_user_id' => $user->id]);
        }

        return $user;
    }
}
