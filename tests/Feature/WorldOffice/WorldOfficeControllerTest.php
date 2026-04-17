<?php

declare(strict_types=1);

namespace Tests\Feature\WorldOffice;

use App\Models\AppModule;
use App\Models\Route as ClientRoute;
use App\Models\Tenant;
use App\Models\TenantBillingProfile;
use App\Models\User;
use App\Models\Warehouse;
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
