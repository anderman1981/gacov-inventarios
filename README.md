# GACOV Inventarios

Sistema de inventarios para Inversiones GACOV S.A.S. construido sobre Laravel 13 y alineado con la base arquitectónica AMR Tech.

## Stack

- PHP 8.3+
- Laravel 13
- MySQL 8+
- Vite + Alpine.js
- Hostinger Shared Hosting apuntando a `public/`

## Estructura base

```text
gacov-inventarios/
├── app/                    # Adaptadores Laravel: HTTP, modelos, providers, components
├── config/                 # Configuración por arrays PHP
├── database/               # Migrations, factories y seeders
├── frontend/               # Código fuente del frontend compilado con Vite
│   └── resources/
│       ├── css/
│       ├── images/
│       └── js/
├── public/                 # Único webroot
├── resources/              # Blade views del backend Laravel
├── routes/                 # Rutas segmentadas por módulo
├── src/
│   ├── Application/        # Queries y casos de uso AMR
│   ├── Contract/           # Interfaces
│   ├── Domain/             # Objetos de dominio compartidos
│   ├── Infrastructure/     # Implementaciones Eloquent y adaptadores
│   └── Support/            # Configuración y utilidades base
├── storage/
├── tests/
│   ├── Feature/
│   ├── Integration/
│   └── Unit/
└── .amr/docs/              # Documentación de decisiones IA/arquitectura
```

## Levantar local

1. Copia `.env.example` a `.env`.
2. Configura credenciales MySQL locales.
3. Ejecuta `composer install`.
4. Ejecuta `php artisan key:generate`.
5. Ejecuta `php artisan migrate --seed`.
6. Ejecuta `npm install`.
7. Ejecuta `npm run build`.
8. Inicia con `php artisan serve`.

## Separación backend / frontend

- El backend queda en la raíz del proyecto con Laravel, `app/`, `src/`, `routes/`, `config/` y `database/`.
- El frontend fuente queda concentrado en `frontend/resources/` y se compila hacia `public/build` usando Vite.
- Las vistas Blade siguen en `resources/views/` porque forman parte de la capa HTTP del backend.

## Validaciones rápidas

- `php artisan route:list`
- `php artisan test`
- `npm run build`

## Notas de despliegue

- En Hostinger el dominio debe publicar exactamente `gacov-inventarios/public`.
- Nunca subas `.env`, `storage/` runtime ni `bootstrap/cache/*.php`.
- `public/.htaccess` ya incluye headers base de seguridad para Apache.

## Documentación IA

La base creada por agentes y decisiones de estructura está en `.amr/docs/architecture-baseline.md`.
