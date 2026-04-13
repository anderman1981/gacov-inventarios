<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Tenant\Services\TenantContext;
use App\Models\AppModule;
use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\Exceptions\PermissionDoesNotExist;
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
     * El permiso sigue el formato: {module_key}.view o {module_key}.own
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

        // Dashboard permite tanto view como own (dashboard personalizado del conductor)
        if ($moduleKey === 'dashboard') {
            // Verificar si tiene dashboard.view O dashboard.own
            $hasView = $this->userHasPermission($user, 'dashboard.view');
            $hasOwn = $this->userHasPermission($user, 'dashboard.own');

            return $hasView || $hasOwn;
        }

        return $this->userHasPermission($user, "{$moduleKey}.view");
    }

    /**
     * Verifica si el usuario tiene un permiso específico (sin lanzar excepción).
     */
    private function userHasPermission($user, string $permission): bool
    {
        try {
            return $user->hasPermissionTo($permission);
        } catch (PermissionDoesNotExist) {
            return false;
        }
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

        // Si es dashboard y el usuario es conductor, redirigir a su dashboard
        $user = auth()->user();
        if ($moduleKey === 'dashboard' && $user?->hasRole('conductor')) {
            return redirect()->route('driver.dashboard')
                ->with('info', 'Redirigiendo a tu panel de conductor.');
        }

        // Redirigir con mensaje de error
        return redirect()->route('dashboard')
            ->with('error', $message);
    }
}
