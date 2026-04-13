<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\Tenant;
use App\Models\TenantBillingProfile;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

/**
 * Comando para sincronizar la fase técnica (billing_profile.current_phase)
 * con la fase del plan comercial (subscription.plan.phase).
 *
 * Uso: php artisan tenant:sync-phases [--dry-run]
 */
final class SyncTenantPhasesCommand extends Command
{
    protected $signature = 'tenant:sync-phases 
                            {--dry-run : Solo mostrar cambios sin aplicar}';

    protected $description = 'Sincroniza billing_profile.current_phase con subscription.plan.phase';

    public function handle(): int
    {
        $dryRun = $this->option('dry-run');
        $tenants = Tenant::with(['subscription.plan', 'billingProfile'])->get();

        if ($tenants->isEmpty()) {
            $this->info('No se encontraron tenants.');

            return self::SUCCESS;
        }

        $this->info("Procesando {$tenants->count()} tenants...");
        $this->newLine();

        $updated = 0;
        $skipped = 0;
        $errors = 0;

        $headers = ['Tenant', 'Plan Fase', 'Billing Fase', 'Acción'];
        $rows = [];

        foreach ($tenants as $tenant) {
            $subscription = $tenant->subscription;
            $billingProfile = $tenant->billingProfile;

            if (! $subscription || ! $subscription->plan) {
                $rows[] = [
                    $tenant->name,
                    'Sin plan',
                    $billingProfile?->current_phase ?? 'N/A',
                    '⚠️ Sin suscripción activa',
                ];
                $skipped++;

                continue;
            }

            $planPhase = $subscription->plan->phase ?? 1;
            $billingPhase = $billingProfile?->current_phase ?? 1;

            if ($billingPhase < $planPhase) {
                // Billing menor que plan → sincronizar
                $action = $dryRun
                    ? "🔄 sync {$billingPhase} → {$planPhase}"
                    : "✅ Sincronizado {$billingPhase} → {$planPhase}";

                if (! $dryRun) {
                    if ($billingProfile) {
                        $billingProfile->update(['current_phase' => $planPhase]);
                    } else {
                        TenantBillingProfile::create([
                            'tenant_id' => $tenant->id,
                            ...TenantBillingProfile::defaultPayload($planPhase),
                        ]);
                    }

                    Log::info("SyncTenantPhases: Tenant {$tenant->id} actualizado de fase {$billingPhase} a {$planPhase}");
                }

                $rows[] = [
                    $tenant->name,
                    $planPhase,
                    $billingPhase,
                    $action,
                ];
                $updated++;
            } elseif ($billingPhase > $planPhase) {
                // Billing MAYOR que plan → caso especial permitido
                $rows[] = [
                    $tenant->name,
                    $planPhase,
                    $billingPhase,
                    '🔒 Custom (mayor que plan)',
                ];
                $skipped++;
            } else {
                // Ya están sincronizados
                $rows[] = [
                    $tenant->name,
                    $planPhase,
                    $billingPhase,
                    '✓ Sincronizado',
                ];
            }
        }

        $this->table($headers, $rows);
        $this->newLine();

        if ($dryRun) {
            $this->warn('⚠️  Modo dry-run: ningún cambio fue aplicado.');
        }

        $this->info("Resumen: {$updated} actualizados, {$skipped} omitidos, {$errors} errores.");

        return $errors > 0 ? self::FAILURE : self::SUCCESS;
    }
}
