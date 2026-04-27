<?php

declare(strict_types=1);

namespace App\Domain\Tenant\Services;

use App\Domain\Tenant\Services\Workflow\PhaseUpgradeRequest;
use App\Models\Subscription;
use App\Models\SubscriptionPlan;
use App\Models\Tenant;
use App\Models\TenantBillingProfile;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Servicio que gestiona el flujo completo de upgrades de fase:
 *
 * 1. Cliente solicita upgrade (createUpgradeRequest)
 * 2. Admin revisa y aprueba/rechaza (approve, reject)
 * 3. Se ejecuta el upgrade técnicamente (execute)
 */
final class PhaseUpgradeService
{
    /**
     * Fase máxima permitida.
     */
    private const MAX_PHASE = 5;

    /**
     * Crea una solicitud formal de upgrade de fase.
     */
    public function createUpgradeRequest(
        Tenant $tenant,
        int $requestedPhase,
        int $requestedBy,
        ?string $notes = null
    ): PhaseUpgradeRequest {
        $currentPhase = $tenant->phase();

        // Validaciones
        if ($requestedPhase <= $currentPhase) {
            throw new \InvalidArgumentException(
                "No se puede solicitar una fase igual o menor a la actual ({$currentPhase})"
            );
        }

        if ($requestedPhase > self::MAX_PHASE) {
            throw new \InvalidArgumentException(
                'La fase máxima permitida es '.self::MAX_PHASE
            );
        }

        // Verificar que no haya una solicitud pendiente
        $pendingRequest = PhaseUpgradeRequest::where('tenant_id', $tenant->id)
            ->whereIn('status', [PhaseUpgradeRequest::STATUS_PENDING, PhaseUpgradeRequest::STATUS_APPROVED])
            ->first();

        if ($pendingRequest) {
            throw new \RuntimeException(
                'Ya existe una solicitud pendiente o aprobada para este tenant'
            );
        }

        return PhaseUpgradeRequest::create([
            'tenant_id' => $tenant->id,
            'requested_by' => $requestedBy,
            'current_phase' => $currentPhase,
            'requested_phase' => $requestedPhase,
            'notes' => $notes,
            'status' => PhaseUpgradeRequest::STATUS_PENDING,
        ]);
    }

    /**
     * Aprueba una solicitud de upgrade.
     * El admin puede aprobar una fase diferente a la solicitada.
     */
    public function approve(
        PhaseUpgradeRequest $request,
        int $approverId,
        ?int $approvedPhase = null,
        ?string $adminNotes = null
    ): PhaseUpgradeRequest {
        if (! $request->canBeApproved()) {
            throw new \RuntimeException('Esta solicitud no puede ser aprobada');
        }

        $approvedPhase = $approvedPhase ?? $request->requested_phase;

        // Verificar que la fase aprobada sea válida
        if ($approvedPhase <= $request->current_phase) {
            throw new \InvalidArgumentException(
                "La fase aprobada debe ser mayor a la actual ({$request->current_phase})"
            );
        }

        $request->approve($approverId, $approvedPhase, $adminNotes);

        Log::info("PhaseUpgradeRequest #{$request->id} aprobada para fase {$approvedPhase}");

        return $request->fresh();
    }

    /**
     * Rechaza una solicitud de upgrade.
     */
    public function reject(PhaseUpgradeRequest $request, int $approverId, string $reason): PhaseUpgradeRequest
    {
        if (! $request->canBeApproved()) {
            throw new \RuntimeException('Esta solicitud no puede ser rechazada');
        }

        $request->reject($approverId, $reason);

        Log::info("PhaseUpgradeRequest #{$request->id} rechazada: {$reason}");

        return $request->fresh();
    }

    /**
     * Ejecuta el upgrade técnicamente.
     * Actualiza billing_profile, subscription y crea los overrides de módulos.
     */
    public function execute(PhaseUpgradeRequest $request): Tenant
    {
        if (! $request->canBeExecuted()) {
            throw new \RuntimeException('Esta solicitud no puede ser ejecutada');
        }

        $targetPhase = $request->approved_phase;
        $tenant = $request->tenant;

        return DB::transaction(function () use ($request, $targetPhase, $tenant): Tenant {
            // 1. Actualizar billing_profile.current_phase
            $billingProfile = TenantBillingProfile::firstOrCreate(
                ['tenant_id' => $tenant->id],
                TenantBillingProfile::defaultPayload($targetPhase)
            );

            $billingProfile->update([
                'current_phase' => $targetPhase,
                'phase_started_at' => now(),
            ]);

            // 2. Encontrar el plan que corresponde a esa fase
            $plan = SubscriptionPlan::where('phase', $targetPhase)->first();

            if ($plan) {
                // 3. Actualizar suscripción si existe
                $subscription = Subscription::where('tenant_id', $tenant->id)->first();

                if ($subscription) {
                    $subscription->update(['plan_id' => $plan->id]);
                }
            }

            // 4. El observer SubscriptionObserver se encargará de sincronizar
            // la fase del subscription con el billing_profile

            // 5. Marcar como ejecutada
            $request->markExecuted();

            Log::info("Upgrade ejecutado: Tenant {$tenant->id} → Fase {$targetPhase}");

            return $tenant->fresh();
        });
    }

    /**
     * Obtiene el historial de solicitudes de upgrade para un tenant.
     */
    public function getHistory(Tenant $tenant): Collection
    {
        return PhaseUpgradeRequest::where('tenant_id', $tenant->id)
            ->with(['requester', 'approver'])
            ->orderByDesc('created_at')
            ->get();
    }

    /**
     * Obtiene las solicitudes pendientes de aprobación (para super admin).
     */
    public function getPendingApprovals(): Collection
    {
        return PhaseUpgradeRequest::pending()
            ->with(['tenant', 'requester'])
            ->orderBy('requested_at')
            ->get();
    }
}
