<?php

declare(strict_types=1);

namespace Tests\Feature\Operations;

use App\Domain\Tenant\Services\TenantContext;
use App\Models\AppModule;
use App\Models\Route as ClientRoute;
use App\Models\RouteScheduleAssignment;
use App\Models\Tenant;
use App\Models\TenantBillingProfile;
use App\Models\User;
use Illuminate\Support\Carbon;
use Tests\TestCase;

final class RouteAssignmentBoardTest extends TestCase
{
    public function test_manager_can_view_route_assignment_board(): void
    {
        [$manager, $driverA, $driverB, $routeA] = $this->seedBoardContext();

        $response = $this->actingAs($manager)->get(route('operations.routes.board'));

        $response->assertOk();
        $response->assertSee('Rutas y conductores');
        $response->assertSee($driverA->name);
        $response->assertSee($driverB->name);
        $response->assertSee($routeA->code);
    }

    public function test_manager_can_swap_routes_between_conductors(): void
    {
        [$manager, $driverA, $driverB, $routeA, $routeB] = $this->seedBoardContext();

        $response = $this->actingAs($manager)->post(route('operations.routes.reassign'), [
            'route_id' => $routeA->id,
            'target_driver_id' => $driverB->id,
        ]);

        $response->assertRedirect(route('operations.routes.board'));
        $response->assertSessionHas('success');

        $this->assertSame($driverB->id, $routeA->fresh()->driver_user_id);
        $this->assertSame($driverA->id, $routeB->fresh()->driver_user_id);
        $this->assertSame($routeB->id, $driverA->fresh()->route_id);
        $this->assertSame($routeA->id, $driverB->fresh()->route_id);
    }

    public function test_contador_cannot_access_route_assignment_board(): void
    {
        [, , , , , $contador] = $this->seedBoardContext();

        $this->actingAs($contador)
            ->get(route('operations.routes.board'))
            ->assertRedirect(route('dashboard'));
    }

    public function test_manager_can_view_route_calendar(): void
    {
        [$manager, $driverA, $driverB, $routeA] = $this->seedBoardContext();

        $response = $this->actingAs($manager)->get(route('operations.routes.calendar'));

        $response->assertOk();
        $response->assertSee('Calendario operativo de rutas');
        $response->assertSee($driverA->name);
        $response->assertSee($driverB->name);
        $response->assertSee($routeA->code);
    }

    public function test_manager_can_schedule_second_route_for_same_driver_on_specific_date(): void
    {
        Carbon::setTestNow('2026-04-14 09:00:00');
        [$manager, $driverA, $driverB, $routeA, $routeB] = $this->seedBoardContext();

        $response = $this->actingAs($manager)->post(route('operations.routes.calendar.store'), [
            'route_id' => $routeB->id,
            'target_driver_id' => $driverA->id,
            'assignment_date' => today()->toDateString(),
            'week_start' => today()->startOfWeek(Carbon::MONDAY)->toDateString(),
        ]);

        $response->assertRedirect(route('operations.routes.calendar', [
            'week_start' => today()->startOfWeek(Carbon::MONDAY)->toDateString(),
        ]));
        $response->assertSessionHas('success');

        $assignment = RouteScheduleAssignment::query()
            ->where('route_id', $routeB->id)
            ->whereDate('assignment_date', today())
            ->first();

        $this->assertNotNull($assignment);
        $this->assertSame($driverA->id, $assignment->driver_user_id);
        $this->assertSame($driverB->id, $routeB->fresh()->driver_user_id);
        $this->assertSame($routeA->id, $driverA->fresh()->route_id);

        Carbon::setTestNow();
    }

    /**
     * @return array{0: User, 1: User, 2: User, 3: ClientRoute, 4: ClientRoute, 5: User}
     */
    private function seedBoardContext(): array
    {
        AppModule::query()->create([
            'key' => 'dashboard',
            'name' => 'Dashboard',
            'phase_required' => 1,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        AppModule::query()->create([
            'key' => 'drivers',
            'name' => 'Conductores',
            'phase_required' => 1,
            'sort_order' => 2,
            'is_active' => true,
        ]);

        $tenant = Tenant::factory()->create();
        app(TenantContext::class)->setTenant($tenant);

        TenantBillingProfile::create([
            'tenant_id' => $tenant->id,
            ...TenantBillingProfile::defaultPayload(1),
        ]);

        $manager = User::factory()->create([
            'tenant_id' => $tenant->id,
            'email' => 'manager-routes@example.com',
            'must_change_password' => false,
        ]);
        $manager->syncRoles(['manager']);

        $contador = User::factory()->create([
            'tenant_id' => $tenant->id,
            'email' => 'contador-routes@example.com',
            'must_change_password' => false,
        ]);
        $contador->syncRoles(['contador']);

        $driverA = User::factory()->create([
            'tenant_id' => $tenant->id,
            'email' => 'driver-a@example.com',
            'must_change_password' => false,
        ]);
        $driverA->syncRoles(['conductor']);

        $driverB = User::factory()->create([
            'tenant_id' => $tenant->id,
            'email' => 'driver-b@example.com',
            'must_change_password' => false,
        ]);
        $driverB->syncRoles(['conductor']);

        $routeA = ClientRoute::factory()->create([
            'driver_user_id' => $driverA->id,
            'is_active' => true,
        ]);

        $routeB = ClientRoute::factory()->create([
            'driver_user_id' => $driverB->id,
            'is_active' => true,
        ]);

        $driverA->update(['route_id' => $routeA->id]);
        $driverB->update(['route_id' => $routeB->id]);

        return [$manager, $driverA, $driverB, $routeA, $routeB, $contador];
    }
}
