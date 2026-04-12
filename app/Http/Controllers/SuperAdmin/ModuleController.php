<?php

declare(strict_types=1);

namespace App\Http\Controllers\SuperAdmin;

use App\Http\Controllers\Controller;
use App\Models\AppModule;
use App\Models\Tenant;
use App\Models\TenantBillingProfile;
use App\Models\TenantModuleOverride;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

final class ModuleController extends Controller
{
    /**
     * Listar todos los módulos del sistema.
     */
    public function index(): View
    {
        $modules = AppModule::query()
            ->withCount([
                'overrides as enabled_overrides_count' => static fn ($query) => $query->where('is_enabled', true),
                'overrides as disabled_overrides_count' => static fn ($query) => $query->where('is_enabled', false),
            ])
            ->orderBy('sort_order')
            ->orderBy('phase_required')
            ->get();

        $stats = [
            'total_modules' => $modules->count(),
            'active_modules' => $modules->where('is_active', true)->count(),
            'phase_count' => $modules->pluck('phase_required')->unique()->count(),
            'tenant_overrides' => $modules->sum(
                static fn (AppModule $module): int => $module->enabled_overrides_count + $module->disabled_overrides_count
            ),
        ];

        // Obtener tenants para overrides
        $tenants = Tenant::query()
            ->with(['subscription.plan', 'billingProfile'])
            ->orderBy('name')
            ->get();

        return view('super-admin.modules.index', compact('modules', 'stats', 'tenants'));
    }

    /**
     * Alternar estado activo/inactivo de un módulo.
     */
    public function toggle(Request $request, AppModule $module): RedirectResponse
    {
        $request->validate([
            'is_active' => 'required|boolean',
        ]);

        $module->update(['is_active' => $request->boolean('is_active')]);

        $status = $request->boolean('is_active') ? 'activado' : 'desactivado';

        return redirect()
            ->back()
            ->with('success', "Módulo {$module->name} {$status} exitosamente.");
    }

    /**
     * Actualizar fase requerida de un módulo.
     */
    public function updatePhase(Request $request, AppModule $module): RedirectResponse
    {
        $request->validate([
            'phase_required' => 'required|integer|min:1|max:5',
        ]);

        $module->update(['phase_required' => $request->integer('phase_required')]);

        return redirect()
            ->back()
            ->with('success', "Módulo {$module->name} movido a Fase {$module->phase_required}.");
    }

    /**
     * Habilitar módulo para un tenant específico (override).
     */
    public function enableForTenant(Request $request, AppModule $module, Tenant $tenant): RedirectResponse
    {
        TenantModuleOverride::updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'module_id' => $module->id,
            ],
            ['is_enabled' => true]
        );

        return redirect()
            ->back()
            ->with('success', "Módulo {$module->name} habilitado para {$tenant->name}.");
    }

    /**
     * Deshabilitar módulo para un tenant específico (override).
     */
    public function disableForTenant(Request $request, AppModule $module, Tenant $tenant): RedirectResponse
    {
        TenantModuleOverride::updateOrCreate(
            [
                'tenant_id' => $tenant->id,
                'module_id' => $module->id,
            ],
            ['is_enabled' => false]
        );

        return redirect()
            ->back()
            ->with('success', "Módulo {$module->name} deshabilitado para {$tenant->name}.");
    }

    /**
     * Eliminar override de un tenant.
     */
    public function removeTenantOverride(AppModule $module, Tenant $tenant): RedirectResponse
    {
        TenantModuleOverride::where('tenant_id', $tenant->id)
            ->where('module_id', $module->id)
            ->delete();

        return redirect()
            ->back()
            ->with('success', "Override eliminado para {$tenant->name}.");
    }

    /**
     * Configurar módulos por defecto según la fase.
     */
    public function setPhaseModules(Request $request, Tenant $tenant): RedirectResponse
    {
        $request->validate([
            'phase' => 'required|integer|min:1|max:5',
        ]);

        $phase = $request->integer('phase');

        $billingProfile = $tenant->billingProfile()->firstOrNew();
        $billingProfile->tenant_id = $tenant->id;

        if (! $billingProfile->exists) {
            $billingProfile->fill(TenantBillingProfile::defaultPayload($phase));
        }

        $billingProfile->current_phase = $phase;
        $billingProfile->save();

        // La fase operativa es ahora la fuente de verdad; limpiamos overrides manuales.
        TenantModuleOverride::where('tenant_id', $tenant->id)->delete();

        return redirect()
            ->back()
            ->with('success', "{$tenant->name} configurado en Fase {$phase}. Los overrides manuales fueron limpiados para que gobierne la fase operativa.");
    }
}
