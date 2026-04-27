# Deploy en cPanel sin terminal

## Qué incluye este paquete

- `deploy/cpanel/gacov_app.zip`
  Contiene la app Laravel lista, con `vendor/` incluido y sin archivos de desarrollo.
- `deploy/cpanel/public_html.zip`
  Contiene el webroot listo para `public_html`, con `index.php` adaptado para cargar la app desde `../gacov_app`.
- `public/install.php`
  Instalador web para generar `.env`, correr migraciones y seeders, y dejar el sistema operativo.

## Análisis corto de Fase 1

La Fase 1 ya está implementada y lista para salida operativa base. Según la evidencia del proyecto:

- Seguridad HTTP en `public/.htaccess`.
- Índices críticos en base de datos con `database/migrations/2026_04_11_000001_add_critical_indexes.php`.
- Rate limiting en auth y API.
- Pruebas de inventario y flujo crítico documentadas como aprobadas.
- Assets frontend compilados en `public/build/`.
- Dependencias PHP incluidas en `vendor/`, clave para cPanel sin Composer por terminal.

El bloqueo real para publicar sin SSH no era el código de Fase 1, sino la instalación operativa:

- generar `APP_KEY`
- crear `.env`
- ejecutar migraciones
- ejecutar seeders
- acomodar `public_html`

Este paquete resuelve esos cuatro puntos.

## Estructura objetivo en cPanel

Debes dejar esto:

```text
/home/USUARIO/
├── gacov_app/
└── public_html/

### Si staging va en subcarpeta

Para una URL como `https://ingacov.com/staging-inv/` la estructura objetivo cambia a:

```text
/home/USUARIO/
├── gacov_app/
└── public_html/
    └── staging-inv/
```

En ese caso no debes publicar el paquete raíz `public_html/` en la carpeta principal, sino el paquete preparado para subcarpeta `staging-inv/`.
```

`public_html/index.php` ya viene preparado para apuntar a `../gacov_app`.

## Paso a paso exacto

1. En cPanel crea una base de datos MySQL.
2. Crea un usuario MySQL y asígnale todos los permisos sobre esa base.
3. En el File Manager sube `gacov_app.zip` a tu carpeta home y descomprímelo.
4. Si publicarás en la raíz del dominio, sube `public_html.zip` dentro de `public_html/` y descomprímelo.
5. Si publicarás staging en `https://ingacov.com/staging-inv/`, sube el contenido del paquete `staging-inv/` dentro de `public_html/staging-inv/`.
6. Verifica que la app quede en `/home/USUARIO/gacov_app`.
7. Verifica que el sitio público quede en `/home/USUARIO/public_html` o en `/home/USUARIO/public_html/staging-inv`.
8. Abre en el navegador `https://tu-dominio.com/install.php` o `https://tu-dominio.com/staging-inv/install.php`.
9. Completa el formulario con:
   - URL del sistema
   - host MySQL, normalmente `localhost`
   - puerto `3306`
   - base de datos
   - usuario
   - contraseña
10. Ejecuta la instalación.
11. Entra al login y cambia contraseñas iniciales.
12. Elimina `public_html/install.php` o `public_html/staging-inv/install.php`.

## Credenciales iniciales que crea el seeder

- Super admin
  `superadmin@gacov.com.co` / `SuperGacov2026!$`
- Admin
  `admin@gacov.com.co` / `AdminGacov2026!`

## Qué hace el instalador web

- Genera `.env` en producción
- Crea `APP_KEY`
- Ejecuta `php artisan migrate --force`
- Ejecuta `php artisan db:seed --force`
- Intenta `storage:link`
- Cachea config, rutas y vistas si el entorno lo permite
- Escribe `storage/app/install.lock` para bloquear reinstalaciones accidentales

## Validaciones rápidas después de subir

- `https://tu-dominio.com/` o `https://tu-dominio.com/staging-inv/` carga login
- `https://tu-dominio.com/up` responde correctamente
- puedes iniciar sesión con super admin
- `storage/logs/laravel.log` no muestra errores críticos

## Riesgos o notas

- Si el hosting no permite symlinks, `storage:link` puede fallar; el instalador lo tolera.
- Si `route:cache` o `view:cache` fallan por restricciones del hosting, la instalación igualmente puede quedar operativa.
- El OCR local no funcionará en cPanel compartido salvo que exista un servicio externo para ello.
- Después del primer acceso conviene desactivar o borrar el instalador.
