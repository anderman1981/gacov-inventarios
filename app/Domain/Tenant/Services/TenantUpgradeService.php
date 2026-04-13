<?php

declare(strict_types=1);

namespace App\Domain\Tenant\Services;

use App\Models\AppModule;
use App\Models\Tenant;
use App\Models\TenantBillingProfile;
use App\Models\TenantModuleOverride;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Servicio para gestionar upgrades escalonados de tenants.
 *
 * Este servicio asegura que la fase comercial (billing_profile.current_phase)
 * esté siempre sincronizada con la fase técnica real del sistema.
 */
final class TenantUpgradeService
{
    /**
     * fases válidas del sistema
     */
    private const VALID_PHASES = [1, 2, 3, 4, 5];

    /**
     * Módulos que se habilitan en cada fase
     * phase_required => [module_keys]
     */
    private const PHASE_MODULES = [
        1 => ['dashboard', 'authentication', 'drivers', 'inventory', 'products', 'machines', 'transfers', 'users', 'ocr'],
        2 => ['routes', 'machine_sales', 'reports'],
        3 => ['analytics', 'stock_alerts'],
        4 => ['worldoffice_integration', 'geolocation', 'api_rest'],
        5 => ['white_label'],
    ];

    /**
     * Ejecuta upgrade de fase para un tenant.
     */
    public function upgradePhase(Tenant $tenant, int $newPhase): array
    {
        if (! $this->isValidPhase($newPhase)) {
            return [
                'success' => false,
                'message' => "Fase {$newPhase} no es válida. Fases válidas: ".implode(', ', self::VALID_PHASES),
            ];
        }

        $currentPhase = $tenant->phase();
        if ($newPhase <= $currentPhase) {
            return [
                'success' => false,
                'message' => "No se puede hacer downgrade. Fase actual: {$currentPhase}, solicitada: {$newPhase}",
            ];
        }

        return DB::transaction(function () use ($tenant, $newPhase, $currentPhase) {
            // 1. Actualizar fase en billing profile
            $this->updateBillingPhase($tenant, $newPhase);

            // 2. Habilitar módulos de la nueva fase
            $newModules = $this->enablePhaseModules($tenant, $newPhase);

            // 3. Log de la acción
            $this->logUpgrade($tenant, $currentPhase, $newPhase, $newModules);

            // 4. Notificar (email/webhook) - placeholder
            $this->notifyUpgrade($tenant, $newPhase);

            return [
                'success' => true,
                'previous_phase' => $currentPhase,
                'new_phase' => $newPhase,
                'modules_enabled' => $newModules,
                'message' => "Upgrade completado: Fase {$currentPhase} → Fase {$newPhase}",
            ];
        });
    }

    /**
     * Synchronizes commercial phase with technical phase.
     * Called automatically or manually to ensure alignment.
     */
    public function syncPhaseWithPlan(Tenant $tenant): array
    {
        $planPhase = $tenant->currentPlan()?->phase ?? 1;
        $billingPhase = $tenant->billingProfile?->current_phase ?? $planPhase;

        // Si billing_phase está por debajo del plan, actualizar
        if ($billingPhase < $planPhase) {
            return $this->upgradePhase($tenant, $planPhase);
        }

        // Si billing_phase está por encima del plan, es un upgrade personalizado
        // Permitir pero no sincronizar hacia abajo
        if ($billingPhase > $planPhase) {
            Log::info("Tenant {$tenant->id} tiene fase {$billingPhase} > plan fase {$planPhase}");
        }

        return [
            'success' => true,
            'phase_synced' => $billingPhase === $planPhase,
            'billing_phase' => $billingPhase,
            'plan_phase' => $planPhase,
        ];
    }

    /**
     * Obtiene el roadmap de módulos para una fase específica.
     */
    public function getPhaseRoadmap(int $fromPhase = 1): array
    {
        $roadmap = [];

        foreach (self::PHASE_MODULES as $phase => $modules) {
            if ($phase > $fromPhase) {
                $roadmap[$phase] = [
                    'modules' => $modules,
                    'module_count' => count($modules),
                    'upgrade_needed' => true,
                ];
            }
        }

        return $roadmap;
    }

    /**
     * Verifica si una fase es válida.
     */
    public function isValidPhase(int $phase): bool
    {
        return in_array($phase, self::VALID_PHASES, true);
    }

    /**
     * Obtiene todos los módulos de una fase.
     */
    public function getPhaseModules(int $phase): array
    {
        return self::PHASE_MODULES[$phase] ?? [];
    }

    /**
     * Verifica qué módulos estarán disponibles después del upgrade.
     */
    public function getModulesAfterUpgrade(Tenant $tenant, int $targetPhase): array
    {
        $enabledModules = [];

        // Módulos de fases actuales
        foreach (range(1, $targetPhase) as $phase) {
            $modules = $this->getPhaseModules($phase);
            foreach ($modules as $moduleKey) {
                $enabledModules[$moduleKey] = $phase;
            }
        }

        // Agregar overrides específicos del tenant
        $overrides = $tenant->moduleOverrides()
            ->where('is_enabled', true)
            ->with('module')
            ->get();

        foreach ($overrides as $override) {
            if ($override->module) {
                $enabledModules[$override->module->key] = $override->module->phase_required;
            }
        }

        return $enabledModules;
    }

    /**
     * Obtiene historial de upgrades de un tenant.
     */
    public function getUpgradeHistory(Tenant $tenant): array
    {
        // Implementar con tabla de audit si existe
        return [];
    }

    // ==================== MÉTODOS PRIVADOS ====================

    private function updateBillingPhase(Tenant $tenant, int $newPhase): void
    {
        $billing = $tenant->billingProfile;

        if (! $billing instanceof TenantBillingProfile) {
            $billing = new TenantBillingProfile(['tenant_id' => $tenant->id]);
        }

        $billing->current_phase = $newPhase;
        $billing->save();
    }

    private function enablePhaseModules(Tenant $tenant, int $phase): array
    {
        $moduleKeys = $this->getPhaseModules($phase);
        $enabled = [];

        foreach ($moduleKeys as $moduleKey) {
            $module = AppModule::query()
                ->where('key', $moduleKey)
                ->first();

            if ($module) {
                TenantModuleOverride::updateOrCreate(
                    [
                        'tenant_id' => $tenant->id,
                        'module_id' => $module->id,
                    ],
                    [
                        'is_enabled' => true,
                    ]
                );
                $enabled[] = $moduleKey;
            }
        }

        return $enabled;
    }

    private function logUpgrade(Tenant $tenant, int $fromPhase, int $toPhase, array $modules): void
    {
        Log::channel('tenant')->info('Tenant upgrade', [
            'tenant_id' => $tenant->id,
            'tenant_name' => $tenant->name,
            'from_phase' => $fromPhase,
            'to_phase' => $toPhase,
            'modules_enabled' => $modules,
            'timestamp' => now()->toIso8601String(),
        ]);
    }

    private function notifyUpgrade(Tenant $tenant, int $newPhase): void
    {
        // Placeholder para email/webhook de notificación
        // TODO: Implementar notificaciones
    }
}
