<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Models\AppModule;
use App\Models\Tenant;
use App\Models\TenantModuleOverride;
use Illuminate\Console\Command;

final class ResetModulesByPhaseCommand extends Command
{
    protected $signature = 'modules:reset-phase 
                            {tenant_id : ID del tenant}
                            {phase : Número de fase (1-5)}
                            {--dry-run : Solo mostrar qué cambios se harían}';

    protected $description = 'Resetea los módulos de un tenant según la fase seleccionada';

    public function handle(): int
    {
        $tenantId = (int) $this->argument('tenant_id');
        $phase = (int) $this->argument('phase');

        if ($phase < 1 || $phase > 5) {
            $this->error('La fase debe estar entre 1 y 5.');

            return self::FAILURE;
        }

        $tenant = Tenant::find($tenantId);

        if (! $tenant) {
            $this->error("Tenant con ID {$tenantId} no encontrado.");

            return self::FAILURE;
        }

        $this->info("Configurando {$tenant->name} para Fase {$phase}...");

        // Obtener módulos activos globalmente que deben estar habilitados
        $modulesEnabled = AppModule::where('phase_required', '<=', $phase)
            ->where('is_active', true)
            ->get();

        // Módulos que deben estar bloqueados
        $modulesDisabled = AppModule::where('phase_required', '>', $phase)
            ->get();

        $this->table(
            ['Tipo', 'Módulos'],
            [
                ['Habilitar', $modulesEnabled->count()],
                ['Bloquear', $modulesDisabled->count()],
            ]
        );

        if ($this->option('dry-run')) {
            $this->warn('DRY RUN - No se realizarán cambios.');

            $this->newLine();
            $this->info('Módulos a habilitar:');
            foreach ($modulesEnabled as $m) {
                $this->line("  ✅ {$m->name} (Fase {$m->phase_required})");
            }

            $this->newLine();
            $this->info('Módulos a bloquear:');
            foreach ($modulesDisabled as $m) {
                $this->line("  ❌ {$m->name} (Fase {$m->phase_required})");
            }

            return self::SUCCESS;
        }

        // Limpiar overrides existentes
        $deleted = TenantModuleOverride::where('tenant_id', $tenantId)->delete();
        $this->info("Overrides anteriores eliminados: {$deleted}");

        // Crear overrides de habilitación
        $created = 0;
        foreach ($modulesEnabled as $module) {
            TenantModuleOverride::create([
                'tenant_id' => $tenantId,
                'module_id' => $module->id,
                'is_enabled' => true,
            ]);
            $created++;
        }

        // Crear overrides de bloqueo
        foreach ($modulesDisabled as $module) {
            TenantModuleOverride::create([
                'tenant_id' => $tenantId,
                'module_id' => $module->id,
                'is_enabled' => false,
            ]);
            $created++;
        }

        $this->info("Overrides creados: {$created}");

        // Actualizar fase de suscripción
        if ($tenant->subscription) {
            $tenant->subscription->update(['phase' => $phase]);
            $this->info("Suscripción actualizada a Fase {$phase}.");
        }

        $this->newLine();
        $this->info("✅ {$tenant->name} configurado para Fase {$phase}.");
        $this->info("  - {$modulesEnabled->count()} módulos habilitados");
        $this->info("  - {$modulesDisabled->count()} módulos bloqueados");

        return self::SUCCESS;
    }
}
