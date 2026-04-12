# Middleware: EnsureTenantContext

## metadata
```yaml
name: EnsureTenantContext
path: app/Http/Middleware/EnsureTenantContext.php
type: middleware
date: 2026-04-08
```

## Descripción

Este middleware establece el contexto de tenant para cada solicitud HTTP. Es crítico para el sistema multi-tenant de GACOV.

## Código Fuente

```php
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

        // Guest: continuar sin contexto
        if ($user === null) {
            return $next($request);
        }

        // SUPER ADMIN: sin contexto de tenant, ve todos los datos
        if ($user->is_super_admin) {
            $this->tenantContext->setTenant(null);
            return $next($request);
        }

        // Usuario normal sin tenant
        if ($user->tenant_id === null) {
            abort(403, 'Tu cuenta no está asociada a ninguna empresa. Contacta al administrador.');
        }

        // Obtener tenant
        $tenant = Tenant::query()
            ->with(['billingProfile', 'moduleOverrides.module', 'subscription.plan'])
            ->find($user->tenant_id);

        // Tenant no existe
        if ($tenant === null) {
            abort(403, 'La empresa no está activa. Contacta al administrador de AMR Tech.');
        }

        $this->tenantContext->setTenant($tenant);

        // Verificar suspensión (solo redirigir, no bloquear)
        if (! $tenant->is_active && ! $request->routeIs('subscription.*')) {
            abort(403, 'La empresa no está activa. Contacta al administrador de AMR Tech.');
        }

        // Verificar suscripción
        $subscription = $tenant->subscription;
        if ($subscription && ! $subscription->isActive() && $subscription->status === 'suspended') {
            if (! $request->routeIs('subscription.*')) {
                return redirect()->route('subscription.expired');
            }
        }

        return $next($request);
    }
}
```

## Flujo de Ejecución

```
Solicitud HTTP
      │
      ▼
┌─────────────────┐
│ auth()->user() │
└────────┬────────┘
         │
         ▼
    ┌────────┐     No
    │ user   │──────────┐
    │ ===    │          │
    │ null?  │          ▼
    └────┬───┘    ┌──────────────┐
         │ Sí     │ $next()     │ ← Guest pasa libre
         │        └──────────────┘
         ▼
    ┌────────────┐     No
    │ is_super   │──────────┐
    │ _admin?    │          │
    └────┬───────┘          │
         │ Sí               ▼
         │           ┌────────────────────┐
         ▼           │ $tenantContext     │
    ┌────────────┐   │ ->setTenant(null) │
    │ tenant_id  │   │ $next()           │
    │ === null?  │   └────────────────────┘
    └────┬───────┘
         │ No
         ▼
    ┌─────────────────────────────────┐
    │ Buscar Tenant con relaciones   │
    │ - billingProfile               │
    │ - moduleOverrides.module        │
    │ - subscription.plan             │
    └───────────────┬─────────────────┘
                    │
                    ▼
            ┌───────────────┐     No
            │ tenant ===   │───────────┐
            │ null?        │           │
            └───────┬───────┘           │
                    │ Sí                ▼
                    │            ┌──────────────────────┐
                    ▼            │ abort(403)           │
            ┌───────────┐        │ "Empresa no activa"  │
            │ tenant    │        └──────────────────────┘
            │ ->is_active?
            └───────┬───────┘
                    │
        ┌───────────┴───────────┐
        │                       │
        ▼                       ▼
   ┌─────────┐            ┌─────────┐
   │ Active  │            │ Inactive│
   │ $next() │            │ abort() │
   └─────────┘            └─────────┘
```

## Casos de Uso

### 1. Super Admin Accediendo al Dashboard
```
1. GET /dashboard
2. Middleware: user = Super Admin (is_super_admin = true)
3. setTenant(null) → $next()
4. Dashboard muestra todos los datos
```

### 2. Usuario Normal Accediendo a Inventario
```
1. GET /inventory/warehouse
2. Middleware: user = Admin (tenant_id = 1)
3. Buscar Tenant::find(1)
4. Verificar is_active = true
5. setTenant($tenant) → $next()
6. Inventario filtra por tenant_id = 1
```

### 3. Usuario Sin Tenant
```
1. GET /inventory/warehouse
2. Middleware: user = User (tenant_id = null, is_super_admin = false)
3. abort(403, 'Tu cuenta no está asociada a ninguna empresa...')
```

## Errores Comunes

| Error | Causa | Solución |
|-------|-------|----------|
| 403 "Tu cuenta no está asociada..." | Usuario sin tenant_id | Asignar tenant_id en BD |
| 403 "Empresa no está activa" | tenant.is_active = false | Activar tenant |
| 403 "Empresa no está activa" | subscription.status = 'suspended' | Renovar suscripción |
| Datos duplicados | Sin contexto de tenant | Verificar setTenant() |

## Tests

```php
// tests/Unit/Middleware/EnsureTenantContextTest.php

public function test_guest_passes_through(): void
{
    $response = $this->get('/public-route');
    $response->assertOk();
}

public function test_super_admin_accesses_without_tenant(): void
{
    $superAdmin = User::factory()->create(['is_super_admin' => true, 'tenant_id' => null]);
    
    $this->actingAs($superAdmin);
    $response = $this->get('/dashboard');
    
    $response->assertOk();
}

public function test_user_without_tenant_gets_403(): void
{
    $user = User::factory()->create(['is_super_admin' => false, 'tenant_id' => null]);
    
    $this->actingAs($user);
    $response = $this->get('/dashboard');
    
    $response->assertStatus(403);
}
```

## Integración con TenantContext Service

```php
// En cualquier parte de la aplicación
$tenant = app(TenantContext::class)->getTenant();

// En modelos con Global Scope
$products = Product::all(); // Auto-filtrado por tenant
```
