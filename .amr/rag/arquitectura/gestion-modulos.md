# Gestión de Módulos - GACOV Inventarios

## metadata
```yaml
version: 1.0.0
date: 2026-04-12
system: GACOV Inventarios SaaS
category: modules
```

## Visión General

El sistema de módulos permite activar/desactivar funcionalidades según la fase de suscripción del cliente.

## Estructura de Módulos

### Módulos Disponibles (18)

| Módulo | Clave | Fase | Descripción |
|--------|-------|------|-------------|
| Autenticación | auth | 1 | Login, roles y permisos |
| Dashboard | dashboard | 1 | Panel principal |
| Conductores | drivers | 1 | Surtido de máquinas y planillas |
| Inventario | inventory | 1 | Bodega, vehículo, movimientos |
| Productos | products | 1 | Catálogo de productos |
| Máquinas | machines | 1 | Gestión de máquinas expendedoras |
| Traslados | transfers | 1 | Órdenes de traslado |
| Rutas | routes | 1 | Gestión de rutas de distribución |
| Usuarios | users | 1 | Administración de usuarios |
| OCR | ocr | 1 | Lectura de planillas con IA |
| Ventas | sales | 1 | Ventas de máquinas |
| Reportes | reports | 2 | Exportación PDF y Excel |
| Analytics | analytics | 2 | Gráficas y estadísticas |
| Alertas | alerts | 2 | Notificaciones de stock mínimo |
| WorldOffice | world_office | 4 | Exportación contable |
| Geolocalización | geolocation | 4 | Rastreo de conductores |
| API REST | api | 4 | Acceso programático externo |
| White-label | white_label | 5 | Marca propia por tenant |

## Fases del Sistema

### Fase 1: Inventario Base
- Autenticación, Dashboard, Inventario, Productos, Máquinas, Traslados, Rutas, Usuarios, OCR, Ventas, Conductores

### Fase 2: Operaciones Avanzadas
- +Reportes, Analytics, Alertas de stock

### Fase 3: Reportes y OCR
- Módulos de reportes mejorados

### Fase 4: Empresarial
- +WorldOffice, Geolocalización, API REST

### Fase 5: Enterprise
- +White-label, Backup automatizado, Soporte prioritario

## Panel de Gestión (Super Admin)

### URL
`/super-admin/modules`

### Funcionalidades

#### 1. Ver Estado Global de Módulos
- Tabla con todos los módulos
- Indicadores de estado activo/inactivo
- Contador de overrides por módulo

#### 2. Activar/Desactivar Módulo Globalmente
```html
<form action="/super-admin/modules/{module}/toggle" method="POST">
    <input type="hidden" name="is_active" value="0">
    <button type="submit">Desactivar</button>
</form>
```

#### 3. Cambiar Fase de un Módulo
```html
<form action="/super-admin/modules/{module}/phase" method="POST">
    <select name="phase_required">
        <option value="1">Fase 1</option>
        <option value="2">Fase 2</option>
        ...
    </select>
</form>
```

#### 4. Overrides por Cliente
Para habilitar/bloquear un módulo específico a un cliente:
```
POST /super-admin/modules/{module}/enable/{tenant}
POST /super-admin/modules/{module}/disable/{tenant}
```

#### 5. Configurar Múltiples Módulos por Fase
```html
<form action="/super-admin/tenants/{tenant}/phase" method="POST">
    <select name="phase">
        <option value="1">Fase 1</option>
        <option value="2">Fase 2</option>
        ...
    </select>
    <button type="submit">Aplicar</button>
</form>
```

## Modelo de Datos

### Tabla: modules
```sql
CREATE TABLE modules (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    key VARCHAR(50) UNIQUE NOT NULL,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    phase_required INT DEFAULT 1,
    icon VARCHAR(10),
    color VARCHAR(20),
    route_prefix VARCHAR(100),
    permission_prefix VARCHAR(100),
    sort_order INT DEFAULT 0,
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP,
    updated_at TIMESTAMP
);
```

### Tabla: tenant_module_overrides
```sql
CREATE TABLE tenant_module_overrides (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    tenant_id BIGINT UNSIGNED NOT NULL,
    module_id BIGINT UNSIGNED NOT NULL,
    is_enabled BOOLEAN NOT NULL,
    created_at TIMESTAMP,
    updated_at TIMESTAMP,
    UNIQUE KEY unique_tenant_module (tenant_id, module_id)
);
```

## Control de Acceso

### Permisos
- `system.modules` - Requiere este permiso para ver la sección de módulos en el menú admin

### Roles con Acceso
- `super_admin` - Acceso total al panel de módulos
- `admin` - Puede ver si tiene permiso `system.modules`

## API Routes

| Método | Ruta | Descripción |
|--------|------|-------------|
| GET | /super-admin/modules | Listar módulos |
| POST | /super-admin/modules/{module}/toggle | Toggle activo/inactivo |
| POST | /super-admin/modules/{module}/phase | Cambiar fase |
| POST | /super-admin/modules/{module}/enable/{tenant} | Habilitar para tenant |
| POST | /super-admin/modules/{module}/disable/{tenant} | Bloquear para tenant |
| DELETE | /super-admin/modules/{module}/override/{tenant} | Eliminar override |
| POST | /super-admin/tenants/{tenant}/phase | Configurar fase completa |

## Comandos Artisan

### Resetear módulos por fase
```bash
# Aplicar Fase 5 a GACOV (tenant_id=1)
php artisan modules:reset-phase 1 5

# Dry run (ver qué cambiaría)
php artisan modules:reset-phase 1 5 --dry-run
```

## Uso en Views

### Directiva Blade @moduleEnabled
```blade
@moduleEnabled('transfers')
    <a href="{{ route('transfers.index') }}">Traslados</a>
@endmoduleEnabled
```

### Directiva @can
```blade
@can('transfers.view')
    <a href="{{ route('transfers.index') }}">Ver Traslados</a>
@endcan
```

## Configuración de TenantContext

El servicio `TenantContext` verifica el acceso a módulos:

```php
public function canAccessModule(string $moduleKey): bool
{
    // Super admin sin tenant = acceso total
    if ($this->tenant === null) {
        return true;
    }

    return $this->tenant->hasModuleAccess($moduleKey);
}
```

## Referencias

- Controlador: `app/Http/Controllers/SuperAdmin/ModuleController.php`
- Modelo: `app/Models/AppModule.php`
- Vista: `resources/views/super-admin/modules/index.blade.php`
- Layout: `resources/views/super-admin/layout.blade.php`
- Rutas: `routes/super-admin.php`
- Comando: `app/Console/Commands/ResetModulesByPhaseCommand.php`
