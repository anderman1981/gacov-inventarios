# Architecture Baseline

Fecha: 2026-04-08

## Agentes usados

- `superorchestrator` equivalente: coordinación principal de la tarea.
- `php-architect` equivalente: revisión de estructura Laravel y capa de aplicación.
- `dba` equivalente: validación estática de migraciones, seeders y setup.
- `uiux` equivalente: validación de layouts, vistas faltantes y design tokens.
- `security` equivalente: revisión de `.env`, webroot y headers.

## Decisiones aplicadas

- Se creó `src/` como base AMR mínima sin romper la app Laravel existente.
- Se movió el dashboard desde una closure de ruta a `DashboardController` + `GetDashboardOverview`.
- Se registraron bindings de repositorios y configuración base en el contenedor.
- Se agregó `config/amr.php` para centralizar datos de empresa, hosting y tokens visuales.
- Se creó `.amr/docs/` como espacio oficial de documentación de agentes.
- Se corrigieron vistas faltantes referenciadas por backend: `layouts.guest`, `profile.edit` e `inventory.vehicle-stocks`.
- Se endureció el baseline de despliegue con headers en `public/.htaccess` y exclusiones de runtime en `.gitignore`.

## Estructura resultante

```text
src/
├── Application/Query/Dashboard/GetDashboardOverview.php
├── Contract/Repository/
├── Domain/Shared/CompanyProfile.php
├── Infrastructure/Persistence/Eloquent/
└── Support/Config/AmrConfig.php
```

## Pendientes conscientes

- Alinear completamente esquema DB vs modelos/controladores.
- Migrar lógica de controladores grandes hacia casos de uso por módulo.
- Normalizar naming frontend `gacov-*` -> `amr-*` en todas las vistas.
- Separar seeders de bootstrap y seeders demo.
