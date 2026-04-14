<?php

declare(strict_types=1);

namespace Tests\Feature\SuperAdmin;

use App\Models\AppModule;
use App\Models\Route as ClientRoute;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\TenantBillingProfile;
use App\Models\User;
use Tests\TestCase;

final class TenantPhaseActivationTest extends TestCase
{
    public function test_super_admin_can_escalate_a_client_phase_and_unlock_modules_for_their_users(): void
    {
        $this->seedModuleCatalog();

        $superAdmin = User::factory()->create([
            'is_super_admin' => true,
            'tenant_id' => null,
        ]);
        $superAdmin->syncRoles(['super_admin']);

        $tenant = Tenant::factory()->create([
            'name' => 'Cliente Escalonado',
            'slug' => 'cliente-escalonado',
            'email' => 'cliente-escalonado@example.com',
        ]);

        $enterprisePlan = SubscriptionPlan::create([
            'name' => 'Enterprise',
            'slug' => 'enterprise-phase-test',
            'phase' => 5,
            'monthly_price' => 1500000,
            'yearly_price' => 15300000,
            'is_active' => true,
            'sort_order' => 5,
            'modules' => ['drivers', 'sales', 'reports'],
            'features' => ['Acceso completo'],
        ]);

        Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $enterprisePlan->id,
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'current_period_start' => now()->subDay(),
            'current_period_end' => now()->addMonth(),
        ]);

        TenantBillingProfile::updateOrCreate(
            ['tenant_id' => $tenant->id],
            TenantBillingProfile::defaultPayload(1)
        );

        $route = ClientRoute::factory()->create([
            'tenant_id' => $tenant->id,
            'code' => 'RT-PHASE',
            'name' => 'Ruta Fases',
            'is_active' => true,
        ]);

        $driver = User::factory()->create([
            'name' => 'Conductor Fases',
            'email' => 'conductor-fases@example.com',
            'tenant_id' => $tenant->id,
            'route_id' => $route->id,
            'is_super_admin' => false,
        ]);
        $driver->syncRoles(['conductor']);

        $route->update(['driver_user_id' => $driver->id]);

        $this->actingAs($driver)
            ->get(route('driver.stocking.create'))
            ->assertOk();

        $this->actingAs($driver)
            ->get(route('driver.sales.create'))
            ->assertRedirect(route('subscription.upgrade'))
            ->assertSessionHas('module_required', 'sales')
            ->assertSessionHas('module_required_phase', 2)
            ->assertSessionHas('module_current_phase', 1);

        $this->actingAs($superAdmin)
            ->put(route('super-admin.tenants.billing-profile.update', $tenant), [
                'current_phase' => 2,
                'current_phase_value' => TenantBillingProfile::DEFAULT_PHASE_VALUE,
                'total_project_value' => TenantBillingProfile::DEFAULT_TOTAL_PROJECT_VALUE,
                'minimum_commitment_months' => 3,
                'phase_started_at' => '2026-04-12',
                'proposal_reference' => 'PROP-PHASE-2',
                'notes' => 'Cliente escalado a fase 2 desde super admin.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('tenant_billing_profiles', [
            'tenant_id' => $tenant->id,
            'current_phase' => 2,
        ]);

        $this->actingAs($driver)
            ->get(route('driver.sales.create'))
            ->assertOk();
    }

    public function test_new_tenant_created_from_super_admin_starts_with_phase_one_even_if_the_plan_is_higher(): void
    {
        $superAdmin = User::factory()->create([
            'is_super_admin' => true,
            'tenant_id' => null,
        ]);
        $superAdmin->syncRoles(['super_admin']);

        $enterprisePlan = SubscriptionPlan::create([
            'name' => 'Enterprise Comercial',
            'slug' => 'enterprise-comercial',
            'phase' => 5,
            'monthly_price' => 1500000,
            'yearly_price' => 15300000,
            'is_active' => true,
            'sort_order' => 5,
            'modules' => ['drivers', 'sales', 'reports', 'white_label'],
            'features' => ['Plan comercial superior'],
        ]);

        $this->actingAs($superAdmin)
            ->post(route('super-admin.tenants.store'), [
                'name' => 'Nuevo Cliente Fases',
                'slug' => 'nuevo-cliente-fases',
                'nit' => '900111222',
                'email' => 'nuevo-cliente-fases@example.com',
                'phone' => '3001234567',
                'plan_id' => $enterprisePlan->id,
                'billing_cycle' => 'monthly',
                'status' => 'active',
            ])
            ->assertRedirect();

        $tenant = Tenant::query()->where('slug', 'nuevo-cliente-fases')->firstOrFail();

        $this->assertDatabaseHas('tenant_billing_profiles', [
            'tenant_id' => $tenant->id,
            'current_phase' => 1,
        ]);

        $this->assertSame(1, $tenant->fresh('billingProfile')->phase());
    }

    private function seedModuleCatalog(): void
    {
        AppModule::query()->create([
            'key' => 'drivers',
            'name' => 'Conductores',
            'phase_required' => 1,
            'sort_order' => 1,
            'is_active' => true,
        ]);

        AppModule::query()->create([
            'key' => 'sales',
            'name' => 'Ventas',
            'phase_required' => 2,
            'sort_order' => 2,
            'is_active' => true,
        ]);
    }
}
