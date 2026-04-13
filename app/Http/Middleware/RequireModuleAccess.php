<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Tenant\Services\TenantContext;
use App\Models\AppModule;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Middleware que verifica acceso a módulos por:
 * 1. Fase del tenant (tenant.phase >= module.phase_required)
 * 2. Permisos del rol de usuario (module.view)
 */
final class RequireModuleAccess
{
    public function __construct(private readonly TenantContext $tenantContext) {}

    public function handle(Request $request, Closure $next, string $moduleKey): Response
    {
        // 1. Verificar que el módulo existe y está disponible para la fase del tenant
        if (! $this->tenantContext->canAccessModule($moduleKey)) {
            return $this->denyAccess($request, $moduleKey, 'Este módulo no está disponible en la fase activa de tu cliente.');
        }

        // 2. Verificar que el usuario tiene permiso de ver este módulo
        //    Si el usuario no tiene el permiso, denegar acceso
        if (! $this->userHasModulePermission($moduleKey)) {
            return $this->denyAccess($request, $moduleKey, 'No tienes permiso para acceder a este módulo.');
        }

        return $next($request);
    }

    /**
     * Verifica si el usuario tiene el permiso de ver el módulo.
     * El permiso sigue el formato: {module_key}.view
     */
    private function userHasModulePermission(string $moduleKey): bool
    {
        $user = auth()->user();

        if (! $user) {
            return false;
        }

        // Super admin tiene acceso total
        if ($user->isSuperAdmin()) {
            return true;
        }

        // Verificar permiso específico del módulo
        $permission = "{$moduleKey}.view";

        return $user->hasPermissionTo($permission);
    }

    /**
     * Maneja el acceso denegado.
     */
    private function denyAccess(Request $request, string $moduleKey, string $message): Response
    {
        $module = AppModule::query()->where('key', $moduleKey)->first();

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'module' => $moduleKey,
                'required_phase' => $module?->phase_required,
                'current_phase' => $this->tenantContext->currentPhase(),
            ], 403);
        }

        // Redirigir con mensaje de error
        return redirect()->route('dashboard')
            ->with('error', $message);
    }
}
