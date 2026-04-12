<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

final class SuperAdminTenantBillingTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_store_billing_profile_and_payment_and_view_report(): void
    {
        $superAdmin = User::factory()->create([
            'is_super_admin' => true,
            'tenant_id' => null,
        ]);

        $plan = SubscriptionPlan::create([
            'name' => 'Starter',
            'slug' => 'starter',
            'phase' => 1,
            'monthly_price' => 290000,
            'yearly_price' => 2958000,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $tenant = Tenant::create([
            'name' => 'Cliente Demo',
            'slug' => 'cliente-demo',
            'email' => 'demo@example.com',
            'is_active' => true,
        ]);

        Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'current_period_start' => now()->subDay(),
            'current_period_end' => now()->addMonth(),
        ]);

        $this->actingAs($superAdmin)
            ->put(route('super-admin.tenants.billing-profile.update', $tenant), [
                'current_phase' => 1,
                'current_phase_value' => 3800000,
                'total_project_value' => 18144000,
                'minimum_commitment_months' => 3,
                'phase_started_at' => '2026-04-13',
                'proposal_reference' => 'PROP-GACOV-F1',
                'notes' => 'Inicio aprobado por el cliente.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('tenant_billing_profiles', [
            'tenant_id' => $tenant->id,
            'current_phase' => 1,
            'current_phase_value' => '3800000.00',
            'total_project_value' => '18144000.00',
            'phase_commitment_ends_at' => '2026-07-13 00:00:00',
            'review_notice_at' => '2026-06-28 00:00:00',
        ]);

        $this->actingAs($superAdmin)
            ->post(route('super-admin.tenants.payments.store', $tenant), [
                'type' => 'development',
                'phase' => 1,
                'description' => 'Pago inicial 50% Fase 1',
                'invoice_number' => 'FAC-2026-001',
                'amount' => 1900000,
                'paid_at' => '2026-04-13',
                'notes' => 'Transferencia confirmada.',
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('tenant_payments', [
            'tenant_id' => $tenant->id,
            'type' => 'development',
            'phase' => 1,
            'invoice_number' => 'FAC-2026-001',
            'amount' => '1900000.00',
            'counts_toward_project_total' => 1,
        ]);

        $this->actingAs($superAdmin)
            ->get(route('super-admin.tenants.billing-report', $tenant))
            ->assertOk()
            ->assertSee('Control financiero por fases')
            ->assertSee('FAC-2026-001')
            ->assertSee('1.900.000');
    }

    public function test_subscription_update_keeps_history_without_double_counting_active_records(): void
    {
        $superAdmin = User::factory()->create([
            'is_super_admin' => true,
            'tenant_id' => null,
        ]);

        $starter = SubscriptionPlan::create([
            'name' => 'Starter',
            'slug' => 'starter',
            'phase' => 1,
            'monthly_price' => 290000,
            'yearly_price' => 2958000,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $basic = SubscriptionPlan::create([
            'name' => 'Básico',
            'slug' => 'basic',
            'phase' => 2,
            'monthly_price' => 690000,
            'yearly_price' => 7038000,
            'is_active' => true,
            'sort_order' => 2,
        ]);

        $tenant = Tenant::create([
            'name' => 'Cliente Historial',
            'slug' => 'cliente-historial',
            'email' => 'historial@example.com',
            'is_active' => true,
        ]);

        $previousSubscription = Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $starter->id,
            'status' => 'active',
            'billing_cycle' => 'monthly',
            'current_period_start' => now()->subMonth(),
            'current_period_end' => now()->addMonth(),
        ]);

        $this->actingAs($superAdmin)
            ->put(route('super-admin.tenants.subscription.update', $tenant), [
                'plan_id' => $basic->id,
                'billing_cycle' => 'yearly',
                'status' => 'active',
            ])
            ->assertRedirect();

        $tenant->refresh();
        $latestSubscription = $tenant->subscription()->first();

        $this->assertSame(2, Subscription::count());
        $this->assertNotNull($previousSubscription->fresh()->cancelled_at);
        $this->assertSame($basic->id, $latestSubscription?->plan_id);
        $this->assertSame(1, Subscription::active()->count());
    }

    public function test_suspended_tenant_can_access_subscription_expired_page(): void
    {
        $plan = SubscriptionPlan::create([
            'name' => 'Starter',
            'slug' => 'starter',
            'phase' => 1,
            'monthly_price' => 290000,
            'yearly_price' => 2958000,
            'is_active' => true,
            'sort_order' => 1,
        ]);

        $tenant = Tenant::create([
            'name' => 'Cliente Suspendido',
            'slug' => 'cliente-suspendido',
            'email' => 'suspendido@example.com',
            'is_active' => false,
        ]);

        Subscription::create([
            'tenant_id' => $tenant->id,
            'plan_id' => $plan->id,
            'status' => 'suspended',
            'billing_cycle' => 'monthly',
            'current_period_start' => now()->subMonth(),
            'current_period_end' => now()->subDay(),
        ]);

        $user = User::factory()->create([
            'tenant_id' => $tenant->id,
            'is_super_admin' => false,
        ]);

        $this->actingAs($user)
            ->get(route('subscription.expired'))
            ->assertOk()
            ->assertSee('Suscripción vencida');
    }
}
