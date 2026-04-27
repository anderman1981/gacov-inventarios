<?php

declare(strict_types=1);

namespace App\Domain\Tenant\Services;

use App\Models\Tenant;

final class TenantContext
{
    private ?Tenant $tenant = null;

    /** Bandera que confirma que el middleware inicializó el contexto */
    private bool $initialized = false;

    public function setTenant(?Tenant $tenant): void
    {
        $this->tenant = $tenant;
        $this->initialized = true;
    }

    /**
     * Indica si el middleware EnsureTenantContext ya procesó la request.
     * TenantScope usa esto como fail-safe: contexto no inicializado = bloquear acceso.
     */
    public function isInitialized(): bool
    {
        return $this->initialized;
    }

    public function getTenant(): ?Tenant
    {
        return $this->tenant;
    }

    public function getTenantId(): ?int
    {
        return $this->tenant?->id;
    }

    public function hasTenant(): bool
    {
        return $this->tenant !== null;
    }

    public function canAccessModule(string $moduleKey): bool
    {
        // Super admin sin tenant asignado tiene acceso a todo
        if ($this->tenant === null) {
            return true;
        }

        return $this->tenant->hasModuleAccess($moduleKey);
    }

    public function currentPhase(): int
    {
        // Super admin ve todas las fases
        if ($this->tenant === null) {
            return 5;
        }

        return $this->tenant->phase();
    }
}
