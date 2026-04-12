<?php

declare(strict_types=1);

namespace Tests\Feature\SuperAdmin;

use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

final class TenantControllerTest extends TestCase
{
    private User $superAdmin;

    private SubscriptionPlan $plan;

    protected function setUp(): void
    {
        parent::setUp();

        $this->superAdmin = User::factory()->create(['is_super_admin' => true]);
        $this->superAdmin->syncRoles(['super_admin']);

        $this->plan = SubscriptionPlan::factory()->create(['is_active' => true]);
    }

    // ──────────────────────────────────────────────────────────────
    // INDEX
    // ──────────────────────────────────────────────────────────────

    public function test_super_admin_can_view_tenants_list(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('super-admin.tenants.index'));

        $response->assertStatus(200);
        $response->assertViewIs('super-admin.tenants.index');
    }

    public function test_non_super_admin_cannot_access_tenants(): void
    {
        $admin = User::factory()->create(['is_super_admin' => false]);
        $admin->syncRoles(['admin']);

        $response = $this->actingAs($admin)->get(route('super-admin.tenants.index'));

        $response->assertStatus(403);
    }

    public function test_can_search_tenants_by_name(): void
    {
        Tenant::factory()->create(['name' => 'Empresa ABC', 'slug' => 'empresa-abc']);
        Tenant::factory()->create(['name' => 'Compañía XYZ', 'slug' => 'compania-xyz']);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('super-admin.tenants.index', ['q' => 'ABC']));

        $response->assertSee('Empresa ABC');
        $response->assertDontSee('Compañía XYZ');
    }

    public function test_search_escapes_sql_like_metacharacters(): void
    {
        // No debe explotar con % o _
        $response = $this->actingAs($this->superAdmin)
            ->get(route('super-admin.tenants.index', ['q' => '% _ \\']));

        $response->assertStatus(200);
    }

    public function test_can_filter_tenants_by_status_active(): void
    {
        $activeTenant = Tenant::factory()->create(['slug' => 'active-tenant']);
        Subscription::factory()->create([
            'tenant_id'   => $activeTenant->id,
            'plan_id'     => $this->plan->id,
            'status'      => 'active',
            'billing_cycle' => 'monthly',
        ]);

        $trialTenant = Tenant::factory()->create(['slug' => 'trial-tenant']);
        Subscription::factory()->create([
            'tenant_id'   => $trialTenant->id,
            'plan_id'     => $this->plan->id,
            'status'      => 'trial',
            'billing_cycle' => 'monthly',
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('super-admin.tenants.index', ['status' => 'active']));

        $response->assertSee($activeTenant->name);
        $response->assertDontSee($trialTenant->name);
    }

    public function test_can_filter_tenants_by_status_suspended(): void
    {
        $suspendedTenant = Tenant::factory()->inactive()->create(['slug' => 'suspended-one']);
        $activeTenant    = Tenant::factory()->create(['slug' => 'active-one']);

        $response = $this->actingAs($this->superAdmin)
            ->get(route('super-admin.tenants.index', ['status' => 'suspended']));

        $response->assertSee($suspendedTenant->name);
        $response->assertDontSee($activeTenant->name);
    }

    // ──────────────────────────────────────────────────────────────
    // CREATE / STORE
    // ──────────────────────────────────────────────────────────────

    public function test_super_admin_can_access_create_form(): void
    {
        $response = $this->actingAs($this->superAdmin)->get(route('super-admin.tenants.create'));

        $response->assertStatus(200);
        $response->assertViewIs('super-admin.tenants.create');
    }

    public function test_super_admin_can_create_tenant_with_monthly_active_plan(): void
    {
        $response = $this->actingAs($this->superAdmin)->post(route('super-admin.tenants.store'), [
            'name'          => 'Cliente Nuevo',
            'slug'          => 'cliente-nuevo',
            'nit'           => '900.123.456-7',
            'email'         => 'cliente@nuevo.com',
            'phone'         => '3001234567',
            'plan_id'       => $this->plan->id,
            'billing_cycle' => 'monthly',
            'status'        => 'active',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('tenants', ['slug' => 'cliente-nuevo']);
        $this->assertDatabaseHas('subscriptions', [
            'status'        => 'active',
            'billing_cycle' => 'monthly',
        ]);
    }

    public function test_tenant_created_with_yearly_plan_sets_period_end_one_year_ahead(): void
    {
        $this->actingAs($this->superAdmin)->post(route('super-admin.tenants.store'), [
            'name'          => 'Anual Corp',
            'slug'          => 'anual-corp',
            'email'         => 'anual@corp.com',
            'plan_id'       => $this->plan->id,
            'billing_cycle' => 'yearly',
            'status'        => 'active',
        ])->assertRedirect();

        $tenantId = DB::table('tenants')->where('slug', 'anual-corp')->value('id');
        $this->assertNotNull($tenantId);

        $periodEnd = DB::table('subscriptions')->where('tenant_id', $tenantId)->value('current_period_end');
        $this->assertNotNull($periodEnd);
        $this->assertTrue(Carbon::parse($periodEnd)->greaterThan(now()->addMonths(11)));
    }

    public function test_tenant_created_with_trial_status_sets_trial_ends_at(): void
    {
        $this->actingAs($this->superAdmin)->post(route('super-admin.tenants.store'), [
            'name'          => 'Trial Company',
            'slug'          => 'trial-company',
            'email'         => 'trial@company.com',
            'plan_id'       => $this->plan->id,
            'billing_cycle' => 'monthly',
            'status'        => 'trial',
        ])->assertRedirect();

        $tenantId = DB::table('tenants')->where('slug', 'trial-company')->value('id');
        $this->assertNotNull($tenantId);

        $trialEnd = DB::table('subscriptions')->where('tenant_id', $tenantId)->value('trial_ends_at');
        $this->assertNotNull($trialEnd);
        $this->assertTrue(Carbon::parse($trialEnd)->greaterThan(now()->addDays(13)));
    }

    public function test_tenant_created_without_trial_has_null_trial_ends_at(): void
    {
        $this->actingAs($this->superAdmin)->post(route('super-admin.tenants.store'), [
            'name'          => 'Active No Trial',
            'slug'          => 'active-no-trial',
            'email'         => 'active@notrial.com',
            'plan_id'       => $this->plan->id,
            'billing_cycle' => 'monthly',
            'status'        => 'active',
        ]);

        $tenant = Tenant::withoutGlobalScopes()->where('slug', 'active-no-trial')->first();
        $this->assertNull($tenant?->subscription?->trial_ends_at);
    }

    public function test_duplicate_slug_is_rejected(): void
    {
        Tenant::factory()->create(['slug' => 'slug-existente']);

        $response = $this->actingAs($this->superAdmin)->post(route('super-admin.tenants.store'), [
            'name'          => 'Otra Empresa',
            'slug'          => 'slug-existente',
            'email'         => 'otra@empresa.com',
            'plan_id'       => $this->plan->id,
            'billing_cycle' => 'monthly',
            'status'        => 'active',
        ]);

        $response->assertSessionHasErrors('slug');
    }

    public function test_slug_with_invalid_characters_is_rejected(): void
    {
        $response = $this->actingAs($this->superAdmin)->post(route('super-admin.tenants.store'), [
            'name'          => 'Empresa Mala',
            'slug'          => 'Empresa Con Espacios!',
            'email'         => 'empresa@mala.com',
            'plan_id'       => $this->plan->id,
            'billing_cycle' => 'monthly',
            'status'        => 'active',
        ]);

        $response->assertSessionHasErrors('slug');
    }

    public function test_invalid_billing_cycle_is_rejected(): void
    {
        $response = $this->actingAs($this->superAdmin)->post(route('super-admin.tenants.store'), [
            'name'          => 'Test',
            'slug'          => 'test-billing',
            'email'         => 'test@billing.com',
            'plan_id'       => $this->plan->id,
            'billing_cycle' => 'weekly', // inválido
            'status'        => 'active',
        ]);

        $response->assertSessionHasErrors('billing_cycle');
    }

    // ──────────────────────────────────────────────────────────────
    // UPDATE
    // ──────────────────────────────────────────────────────────────

    public function test_super_admin_can_update_tenant(): void
    {
        $tenant = Tenant::factory()->create(['name' => 'Nombre Viejo', 'slug' => 'nombre-viejo']);

        $response = $this->actingAs($this->superAdmin)->put(route('super-admin.tenants.update', $tenant), [
            'name'  => 'Nombre Nuevo',
            'slug'  => 'nombre-viejo', // mismo slug (unique ignore self)
            'email' => $tenant->email,
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('tenants', ['id' => $tenant->id, 'name' => 'Nombre Nuevo']);
    }

    public function test_update_rejects_slug_already_taken_by_another_tenant(): void
    {
        $existing = Tenant::factory()->create(['slug' => 'slug-tomado']);
        $tenant   = Tenant::factory()->create(['slug' => 'mi-tenant']);

        $response = $this->actingAs($this->superAdmin)->put(route('super-admin.tenants.update', $tenant), [
            'name'  => $tenant->name,
            'slug'  => 'slug-tomado',
            'email' => $tenant->email,
        ]);

        $response->assertSessionHasErrors('slug');
    }

    // ──────────────────────────────────────────────────────────────
    // SUSPEND / ACTIVATE
    // ──────────────────────────────────────────────────────────────

    public function test_super_admin_can_suspend_tenant(): void
    {
        $tenant = Tenant::factory()->create(['is_active' => true]);
        Subscription::factory()->create([
            'tenant_id'     => $tenant->id,
            'plan_id'       => $this->plan->id,
            'status'        => 'active',
            'billing_cycle' => 'monthly',
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->post(route('super-admin.tenants.suspend', $tenant));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertFalse($tenant->fresh()->is_active);
        $this->assertSame('suspended', $tenant->fresh()->subscription?->status);
    }

    public function test_super_admin_can_activate_suspended_tenant(): void
    {
        $tenant = Tenant::factory()->inactive()->create();
        Subscription::factory()->create([
            'tenant_id'     => $tenant->id,
            'plan_id'       => $this->plan->id,
            'status'        => 'suspended',
            'billing_cycle' => 'monthly',
        ]);

        $response = $this->actingAs($this->superAdmin)
            ->post(route('super-admin.tenants.activate', $tenant));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertTrue($tenant->fresh()->is_active);
        $this->assertSame('active', $tenant->fresh()->subscription?->status);
    }

    public function test_activating_tenant_recalculates_period_end(): void
    {
        $tenant = Tenant::factory()->inactive()->create();
        $sub = Subscription::factory()->create([
            'tenant_id'          => $tenant->id,
            'plan_id'            => $this->plan->id,
            'status'             => 'suspended',
            'billing_cycle'      => 'monthly',
            'current_period_end' => now()->subMonths(2), // vencida
        ]);

        $this->actingAs($this->superAdmin)
            ->post(route('super-admin.tenants.activate', $tenant));

        $newEnd = $sub->fresh()->current_period_end;
        $this->assertTrue($newEnd->greaterThan(now()));
    }

    public function test_activating_tenant_without_subscription_does_not_fail(): void
    {
        $tenant = Tenant::factory()->inactive()->create();
        // Sin subscription

        $response = $this->actingAs($this->superAdmin)
            ->post(route('super-admin.tenants.activate', $tenant));

        $response->assertRedirect();
        $this->assertTrue($tenant->fresh()->is_active);
    }

    public function test_suspending_tenant_without_subscription_does_not_fail(): void
    {
        $tenant = Tenant::factory()->create(['is_active' => true]);
        // Sin subscription

        $response = $this->actingAs($this->superAdmin)
            ->post(route('super-admin.tenants.suspend', $tenant));

        $response->assertRedirect();
        $this->assertFalse($tenant->fresh()->is_active);
    }
}
