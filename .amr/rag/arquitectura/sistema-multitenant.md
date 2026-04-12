# Arquitectura Multi-Tenant GACOV Inventarios

## metadata
```yaml
version: 1.0.0
date: 2026-04-12
system: Laravel 11
database: MySQL
```

## Visión General

El sistema GACOV Inventarios implementa una arquitectura **multi-tenant** con:

1. **Super Admin**: Usuario global sin tenant específico
2. **Tenants**: Empresas/clientes con sus propios datos
3. **Usuarios**: Pertenecen a un tenant específico

## Estructura de Tenants

```
┌─────────────────────────────────────────────────────────────┐
│                      SUPER ADMIN                             │
│  - is_super_admin = true                                    │
│  - tenant_id = null                                         │
│  - Accede a TODOS los tenants                               │
│  - Panel SaaS completo                                      │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
┌─────────────────────────────────────────────────────────────┐
│                       TENANT GACOV                          │
│  - id = 1                                                   │
│  - slug = gacov                                             │
│  - subscription: Enterprise (Fase 5)                        │
│  - Todos los módulos habilitados                            │
└─────────────────────────────────────────────────────────────┘
                              │
          ┌───────────────────┼───────────────────┐
          ▼                   ▼                   ▼
    ┌──────────┐       ┌──────────┐       ┌──────────┐
    │  ADMIN   │       │ MANAGER  │       │CONDUCTOR │
    │ is_super │       │ is_super │       │ is_super │
    │ _admin=0 │       │ _admin=0 │       │ _admin=0  │
    │ tenant=1 │       │ tenant=1 │       │ tenant=1  │
    └──────────┘       └──────────┘       └──────────┘
```

## Middlewares de Autorización

### 1. `auth` (Laravel default)
Verifica que el usuario esté autenticado.

### 2. `tenant` (EnsureTenantContext)
```php
// Flujo de ejecución:
1. Si user === null → $next (continuar, guest puede pasar)
2. Si user.is_super_admin === true → setTenant(null) → $next
3. Si user.tenant_id === null → abort(403, 'Tu cuenta no está asociada...')
4. Si tenant no existe → abort(403, 'La empresa no está activa...')
5. Verificar suscripción activa
6. setTenant($tenant) → $next
```

### 3. `super_admin` (RequireSuperAdmin)
```php
if (!auth()->check() || !auth()->user()->is_super_admin) {
    abort(403, 'Acceso restringido al panel de AMR Tech.');
}
return $next($request);
```

## Roles y Permisos

### Roles Disponibles
| Rol | Descripción | Permisos |
|-----|-------------|----------|
| super_admin | Administrador global | Todos |
| admin | Administrador del tenant | Gestión completa |
| manager | Gerente de operaciones | Inventario, traslados |
| contador | Contador | Reportes, ventas |
| conductor | Conductor de ruta | Solo su ruta |

### Permisos Granulares
```php
// Sistema
'system.modules', 'system.develop'

// Usuarios
'users.view', 'users.create', 'users.edit', 'users.delete', 'users.assign_roles'

// Productos
'products.view', 'products.create', 'products.edit', 'products.delete', 'products.import'

// Inventario
'inventory.view', 'inventory.load_excel', 'inventory.adjust'

// Movimientos
'movements.view'

// Traslados
'transfers.view', 'transfers.create', 'transfers.approve', 'transfers.complete'
```

## Rutas Protegidas

### Middleware Stack por Archivo
| Archivo | Middlewares |
|---------|-------------|
| routes/auth.php | `guest` (login), `auth` (logout) |
| routes/web.php | `['auth', 'tenant']` |
| routes/admin.php | `['auth', 'tenant']` |
| routes/transfers.php | `['auth', 'tenant']` |
| routes/inventory.php | `['auth', 'tenant']` |
| routes/machines.php | `['auth', 'tenant']` |
| routes/products.php | `['auth', 'tenant']` |
| routes/super-admin.php | `['auth', 'tenant', 'super_admin']` |

## Base de Datos

### Tablas Principales
- `users` - id, name, email, password, is_super_admin, tenant_id, ...
- `tenants` - id, name, slug, is_active, ...
- `subscriptions` - tenant_id, plan_id, status, ...
- `subscription_plans` - slug, modules (JSON), features (JSON), ...

### Índices Importantes
```sql
-- users
INDEX idx_users_email (email)
INDEX idx_users_tenant (tenant_id)
INDEX idx_users_super_admin (is_super_admin)

-- tenants
UNIQUE INDEX idx_tenants_slug (slug)
```

## Credenciales del Sistema

```
╔══════════════════════════════════════════════════╗
║  SUPER ADMIN                                     ║
║  Email:  superadmin@gacov.com.co                 ║
║  Pass:   SuperGacov2026!$                        ║
╠══════════════════════════════════════════════════╣
║  ADMIN                                           ║
║  Email:  admin@gacov.com.co                      ║
║  Pass:   AdminGacov2026!  (cambiar al 1er login)║
╚══════════════════════════════════════════════════╝
```

## Solución de Problemas

### Error 403 después de login exitoso
1. Verificar `tenant_id` del usuario en BD
2. Verificar que el tenant esté activo
3. Limpiar caché de configuración: `php artisan config:clear`

### Error 419 en login
1. Limpiar cookies del navegador
2. Verificar que JS/AJAX envía token CSRF
3. Verificar `SESSION_COOKIE` en config

### Super admin no puede acceder
1. Verificar `is_super_admin = true` en BD
2. Verificar `tenant_id = null` en BD
3. Verificar rol `super_admin` asignado
