# GACOV Inventarios - Estado del Proyecto

## 📅 Última Actualización
**Fecha:** 13 de abril de 2026  
**Versión:** 1.1  
**Auditoría:** AMR Tech v2.0

---

## 🚀 Estado General

| Módulo | Estado | Descripción |
|--------|--------|-------------|
| Dashboard | ✅ Operativo | Widgets por rol (admin, manager, contador, conductor) + mapa de máquinas |
| Inventario | ✅ Operativo | Bodega principal, vehículos, máquinas |
| Productos | ✅ Operativo | Catálogo con stock mínimo |
| Traslados | ✅ Operativo | Flujo completo con aprobación |
| Máquinas | ✅ Operativo | Inventario independiente por máquina |
| Conductores | ✅ Operativo | Surtido, ventas, inventario vehicular + GPS |
| Reportes | ✅ Operativo | Movimientos con trazabilidad |
| Facturas | ✅ Operativo | CRUD completo con PDF |
| Módulos SaaS | ✅ Operativo | Sistema de fases y upgrades |
| PWA | ✅ Operativo | Instalable en dispositivos |

---

## 🚛 Perfil Conductor - Control de Acceso

### Permisos por Defecto del Conductor ✅

| Permiso | Acceso | Descripción |
|---------|--------|-------------|
| `products.view` | ✅ | Ver catálogo de productos |
| `stockings.view` | ✅ | Ver historial de surtidos |
| `stockings.create` | ✅ | Registrar nuevos surtidos |
| `stockings.own` | ✅ | Solo sus propios surtidos |
| `sales.view` | ✅ | Ver ventas |
| `sales.create` | ✅ | Registrar ventas |
| `sales.own` | ✅ | Solo sus propias ventas |
| `dashboard.own` | ✅ | Dashboard personalizado |
| `vehicle.view` | ✅ | Ver vehículo asignado |
| `vehicle.inventory.view` | ✅ | Inventario del vehículo |

### Permisos RESTRINGIDOS para Conductores ❌

| Permiso | Motivo |
|---------|--------|
| `machines.view` | No debe ver TODAS las máquinas |
| `inventory.view` | No debe ver bodega principal |
| `transfers.view` | No debe ver traslados |

### Filtro por Ruta Asignada ✅

El conductor solo ve:
- Máquinas de SU ruta (`route_id` igual a su `user.route_id`)
- Vehículo de SU ruta
- Inventario de SU bodega vehicular

---

## 📍 Geolocalización y Mapa

### Características Implementadas ✅

| Feature | Estado | Descripción |
|---------|--------|-------------|
| Captura GPS en surtido | ✅ | Lat, Lng, Accuracy al registrar |
| Mapa de máquinas | ✅ | Leaflet + OpenStreetMap |
| Marcadores por stock | ✅ | Verde/amarillo/rojo según nivel |
| Popup con detalles | ✅ | Código, ubicación, stock |

### Campos de Geolocalización

**En `machines`:**
- `latitude` (DECIMAL 10,8)
- `longitude` (DECIMAL 11,8)

**En `machine_stocking_records`:**
- `latitude` (DECIMAL 10,8)
- `longitude` (DECIMAL 11,8)
- `geolocation_accuracy` (VARCHAR 20)

---

## 🔐 Sistema SaaS Multi-Tenant

### Arquitectura de Fases

| Fase | Plan | Módulos Incluidos | Precio Mensual |
|------|------|-------------------|----------------|
| 1 | Starter | dashboard, auth, drivers, inventory, products, machines, transfers, users, ocr | $290,000 |
| 2 | Basic | + routes, machine_sales, reports | $690,000 |
| 3 | Professional | + analytics, stock_alerts | $890,000 |
| 4 | Business | + world_office, geolocation, api_rest | $1,200,000 |
| 5 | Enterprise | + white_label | $1,500,000 |

### Sincronización de Fases ✅

**Problema:** La fase comercial (`subscription.plan.phase`) y la fase técnica (`billingProfile.current_phase`) estaban desincronizadas.

**Solución implementada:**
- `SubscriptionObserver`: Sincroniza automáticamente cuando cambia el plan
- `SyncTenantPhasesCommand`: `php artisan tenant:sync-phases [--dry-run]`
- Verificación: Billing fase >= Plan fase siempre

### Control de Acceso por Fase ✅

Todas las rutas están protegidas con middleware `module:NOMBRE`:

| Módulo | Middleware | Rutas Protegidas |
|--------|-----------|------------------|
| dashboard | `module:dashboard` | ✅ |
| inventory | `module:inventory` | ✅ |
| machines | `module:machines` | ✅ |
| drivers | `module:drivers` | ✅ |
| products | `module:products` | ✅ |
| transfers | `module:transfers` | ✅ |
| reports | `module:reports` | ✅ |
| users | `module:users` | ✅ |
| sales | `module:sales` | ✅ |

### Flujo de Upgrades Escalonados ✅

```
┌─────────────────┐
│ Cliente solicita │ (nueva fase)
│     upgrade      │
└────────┬────────┘
         │
         ▼
┌─────────────────┐
│  Super Admin    │ (recibe notificación)
│    revisa       │
└────────┬────────┘
         │
    ┌────┴────┐
    ▼         ▼
┌───────┐ ┌────────┐
│Aprueba│ │Rechaza  │
└───┬───┘ └───┬────┘
    │         │
    ▼         ▼
┌───────────┐ ┌──────────┐
│ Se ejecuta│ │Se marca  │
│  upgrade  │ │rechazada │
└───────────┘ └──────────┘
```

**Archivos:**
- `app/Domain/Tenant/Services/PhaseUpgradeService.php`
- `app/Domain/Tenant/Services/Workflow/PhaseUpgradeRequest.php`
- `database/migrations/2026_04_13_000002_create_phase_upgrade_requests_table.php`

---

## 📄 Sistema de Facturas

### Características Implementadas ✅

| Feature | Estado | Descripción |
|---------|--------|-------------|
| CRUD completo | ✅ | index, create, store, show, edit, update |
| Numeración automática | ✅ | INV-YYYY-NNNNNN |
| Items dinámicos | ✅ | Agregar/quitar líneas en frontend |
| Cálculo automático | ✅ | subtotal, descuento, IVA, total |
| Estados | ✅ | draft, issued, paid, cancelled, expired |
| Pagos parciales | ✅ | Registro de abonos |
| PDF generation | ✅ | DomPDF con plantilla profesional |
| DIAN | ✅ | sequential_code, resolution_number, dates |
| API REST | ✅ | InvoiceResource, InvoiceItemResource |

### Campos de Factura

**Cabecera:**
- `prefix`, `number`, `full_number`
- `issue_date`, `due_date`, `paid_at`
- `status`, `payment_status`

**Totales:**
- `subtotal`, `tax_rate`, `tax_amount`
- `discount_amount`, `total`, `paid_amount`, `balance_due`

**Emisor:**
- `issuer_name`, `issuer_nit`, `issuer_address`
- `issuer_phone`, `issuer_email`

**Cliente:**
- `client_name`, `client_nit`, `client_address`
- `client_email`, `client_phone`

**DIAN:**
- `dian_sequential_code`
- `dian_resolution_number`
- `dian_from_date`, `dian_to_date`

### Rutas de Facturas

```
GET  /invoices                 → Lista
GET  /invoices/create          → Crear
POST /invoices                 → Guardar
GET  /invoices/{id}            → Ver
GET  /invoices/{id}/edit       → Editar
PUT  /invoices/{id}            → Actualizar
POST /invoices/{id}/issue      → Emitir
POST /invoices/{id}/cancel     → Cancelar
POST /invoices/{id}/payments   → Registrar pago
GET  /invoices/{id}/pdf        → Descargar PDF
```

---

## 🌐 URLs en Producción

| Entorno | URL | Rama Git |
|---------|-----|----------|
| Staging | https://staging-gacov.webtechnology.com.co | develop |
| Producción | https://gacov.webtechnology.com.co | main |

---

## 🗄️ Base de Datos

**Tablas principales:**
- `users` - Usuarios con tenant_id
- `tenants` - Empresas/clientes
- `subscriptions` - Suscripciones activas
- `subscription_plans` - 5 planes SaaS
- `billing_profiles` - Perfil de facturación con fase
- `app_modules` - 18 módulos configurables
- `tenant_module_overrides` -Overrides por tenant
- `phase_upgrade_requests` - Solicitudes de upgrade
- `invoices` - Facturas formales
- `invoice_items` - Líneas de factura
- `invoice_payments` - Pagos registrados

---

## ⚠️ Pendientes

| Item | Prioridad | Descripción |
|------|-----------|-------------|
| Gemini API Key | 🔴 Alta | API key baneada, necesita renovación |
| WorldOffice Integration | 🔵 Media | Exportación a WorldOffice |
| OCR Planillas | 🔵 Media | Lectura automática de planillas |

---

## 🔧 Comandos Artisan Útiles

```bash
# Sincronizar fases
php artisan tenant:sync-phases [--dry-run]

# Resetear módulos por fase
php artisan tenant:reset-modules {tenant_id}

# Ver rutas con middleware
php artisan route:list --columns=method,uri,name,middleware
```

---

## 📁 Estructura de Archivos Clave

```
app/
├── Domain/Tenant/
│   ├── Observers/SubscriptionObserver.php
│   └── Services/
│       ├── PhaseUpgradeService.php
│       ├── TenantUpgradeService.php
│       └── Workflow/PhaseUpgradeRequest.php
├── Http/Controllers/InvoiceController.php
├── Http/Requests/InvoiceStoreRequest.php
└── Http/Resources/
    ├── InvoiceResource.php
    ├── InvoiceItemResource.php
    └── InvoicePaymentResource.php

resources/views/
├── invoices/
│   ├── index.blade.php
│   ├── create.blade.php
│   ├── edit.blade.php
│   ├── show.blade.php
│   └── pdf.blade.php
└── layouts/app.blade.php (sidebar con facturas)

database/migrations/
├── 2026_04_13_000001_create_invoices_table.php
└── 2026_04_13_000002_create_phase_upgrade_requests_table.php
```

---

## 👥 Credenciales

### Super Admin
- **Email:** superadmin@gacov.com.co
- **Contraseña:** SuperGacov2026!$

### Base de Datos (Producción)
- **Host:** localhost
- **Database:** u886736901_gacovm
- **User:** u886736901_mgacov
- **Pass:** nCx9I4Ri.t=S3j4yVP

---

## 📊 Métricas

| Métrica | Valor |
|---------|-------|
| Total tablas | 37+ |
| Total permisos | 37 |
| Total módulos | 18 |
| Total rutas protegidas | 15 |
| Líneas de código (aprox) | ~2800+ |

---

**Desarrollado por AMR Tech**  
**© Inversiones GACOV S.A.S.**
