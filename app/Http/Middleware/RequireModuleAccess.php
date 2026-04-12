<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Tenant\Services\TenantContext;
use App\Models\AppModule;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class RequireModuleAccess
{
    public function __construct(private readonly TenantContext $tenantContext) {}

    public function handle(Request $request, Closure $next, string $moduleKey): Response
    {
        if (! $this->tenantContext->canAccessModule($moduleKey)) {
            $module = AppModule::query()->where('key', $moduleKey)->first();

            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'Este módulo no está disponible en la fase activa de tu cliente.',
                    'module' => $moduleKey,
                    'required_phase' => $module?->phase_required,
                    'current_phase' => $this->tenantContext->currentPhase(),
                ], 403);
            }

            return redirect()->route('subscription.upgrade')
                ->with([
                    'module_required' => $moduleKey,
                    'module_required_phase' => $module?->phase_required,
                    'module_current_phase' => $this->tenantContext->currentPhase(),
                ]);
        }

        return $next($request);
    }
}
