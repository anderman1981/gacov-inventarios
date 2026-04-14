<?php

declare(strict_types=1);

namespace App\Domain\Tenant\Observers;

use App\Models\Subscription;
use App\Models\TenantBillingProfile;
use Illuminate\Support\Facades\Log;

/**
 * Observador que sincroniza la fase técnica (billing_profile)
 * cuando cambia el plan comercial (subscription).
 */
final class SubscriptionObserver
{
    /**
     * Handle the Subscription "updated" event.
     * Cuando cambia el plan_id, sincroniza billing_profile.current_phase.
     */
    public function updated(Subscription $subscription): void
    {
        // Solo reaccionar si cambió el plan_id
        if (! $subscription->isDirty('plan_id')) {
            return;
        }

        $oldPlanId = $subscription->getOriginal('plan_id');
        $newPlanId = $subscription->plan_id;

        if ($oldPlanId === $newPlanId) {
            return;
        }

        $newPlan = $subscription->plan;
        if (! $newPlan) {
            Log::warning("SubscriptionObserver: Plan no encontrado para subscription {$subscription->id}");

            return;
        }

        $newPhase = $newPlan->phase ?? 1;

        // Obtener o crear billing profile
        $billingProfile = TenantBillingProfile::firstOrCreate(
            ['tenant_id' => $subscription->tenant_id],
            TenantBillingProfile::defaultPayload($newPhase)
        );

        // Solo actualizar si el nuevo plan tiene fase mayor
        // (permite billing_phase > plan.phase para casos especiales)
        if ($newPhase > $billingProfile->current_phase) {
            $oldPhase = $billingProfile->current_phase;
            $billingProfile->update(['current_phase' => $newPhase]);

            Log::info("SubscriptionObserver: Sincronizada fase de {$oldPhase} a {$newPhase} para tenant {$subscription->tenant_id}", [
                'subscription_id' => $subscription->id,
                'old_plan_id' => $oldPlanId,
                'new_plan_id' => $newPlanId,
                'old_phase' => $oldPhase,
                'new_phase' => $newPhase,
            ]);
        }
    }

    /**
     * Handle the Subscription "created" event.
     * Asegura que billing_profile tenga la fase correcta al crear.
     */
    public function created(Subscription $subscription): void
    {
        $plan = $subscription->plan;
        if (! $plan) {
            return;
        }

        $phase = $plan->phase ?? 1;

        TenantBillingProfile::firstOrCreate(
            ['tenant_id' => $subscription->tenant_id],
            array_merge(
                TenantBillingProfile::defaultPayload($phase),
                ['current_phase' => $phase]
            )
        );

        // Si ya existe pero tiene fase menor, sincronizar
        $billingProfile = TenantBillingProfile::where('tenant_id', $subscription->tenant_id)->first();
        if ($billingProfile && $billingProfile->current_phase < $phase) {
            $billingProfile->update(['current_phase' => $phase]);
        }
    }
}
