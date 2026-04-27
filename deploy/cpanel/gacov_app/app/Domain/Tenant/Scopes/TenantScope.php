<?php

declare(strict_types=1);

namespace App\Domain\Tenant\Scopes;

use App\Domain\Tenant\Services\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

final class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $context = app(TenantContext::class);

        // Fail-safe: si el middleware EnsureTenantContext no corrió, bloquear todo.
        // Evita que rutas sin middleware expongan datos de todos los tenants.
        if (! $context->isInitialized()) {
            $builder->whereRaw('0 = 1');

            return;
        }

        $tenantId = $context->getTenantId();

        if ($tenantId !== null) {
            $builder->where($model->getTable().'.tenant_id', $tenantId);
        }

        // Super admin (tenantId = null, contexto inicializado) ve todos los datos.
    }
}
