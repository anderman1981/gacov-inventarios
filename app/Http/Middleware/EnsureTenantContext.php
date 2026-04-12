<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Domain\Tenant\Services\TenantContext;
use App\Models\Tenant;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

final class EnsureTenantContext
{
    public function __construct(private readonly TenantContext $tenantContext) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if ($user === null) {
            return $next($request);
        }

        // Super admin: sin contexto de tenant, ve todos los datos
        if ($user->is_super_admin) {
            $this->tenantContext->setTenant(null);

            return $next($request);
        }

        if ($user->tenant_id === null) {
            abort(403, 'Tu cuenta no está asociada a ninguna empresa. Contacta al administrador.');
        }

        $tenant = Tenant::query()
            ->with(['billingProfile', 'moduleOverrides.module', 'subscription.plan'])
            ->find($user->tenant_id);

        if ($tenant === null) {
            abort(403, 'La empresa no está activa. Contacta al administrador de AMR Tech.');
        }

        $this->tenantContext->setTenant($tenant);

        if (! $tenant->is_active && ! $request->routeIs('subscription.*')) {
            abort(403, 'La empresa no está activa. Contacta al administrador de AMR Tech.');
        }

        // Verificar suscripción (solo advertir, no bloquear — super admin maneja suspensión)
        $subscription = $tenant->subscription;
        if ($subscription && ! $subscription->isActive() && $subscription->status === 'suspended') {
            if (! $request->routeIs('subscription.*')) {
                return redirect()->route('subscription.expired');
            }
        }

        return $next($request);
    }
}
