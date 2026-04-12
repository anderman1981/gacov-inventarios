# HTTP 403 y 419 - Errores de Autenticación y CSRF

## metadata
```yaml
id: http-auth-errors
severity: high
date: 2026-04-12
affected_routes:
  - /transfers/create
  - /inventory/movements
  - /inventory/warehouse
  - /machines/*
  - /products/*
  - /admin/*
resolved: true
```

## Diagnóstico

### HTTP 403 Forbidden
- **Descripción**: El servidor reconoce la solicitud pero la rechaza
- **Causas comunes**:
  1. Usuario no autenticado
  2. Token de sesión expirado
  3. Permisos insuficientes
  4. Middleware `EnsureTenantContext` abortando

### HTTP 419 Unknown Status (CSRF Token Mismatch)
- **Descripción**: El token CSRF no coincide con el del servidor
- **Causas comunes**:
  1. Cookie de sesión no configurada correctamente
  2. Navegador no enviando cookies con solicitudes
  3. Tiempo de espera de sesión agotado
  4. Dominio de cookie no coincide con dominio de solicitud

## Verificación Realizada

### 1. Base de datos
```bash
# Verificar usuarios
php artisan tinker --execute="
\$user = App\Models\User::withoutGlobalScopes()
  ->where('email', 'superadmin@gacov.com.co')->first();
echo 'is_super_admin: ' . (\$user->is_super_admin ? 'true' : 'false');
"
# Resultado: is_super_admin: true ✅
```

### 2. Credenciales
```bash
# Verificar password
php artisan tinker --execute="
\$user = App\Models\User::withoutGlobalScopes()
  ->where('email', 'superadmin@gacov.com.co')->first();
echo 'Password check: ' . (password_verify('SuperGacov2026!$', \$user->password) ? 'CORRECT' : 'WRONG');
"
# Resultado: Password check: CORRECT ✅
```

### 3. Middleware EnsureTenantContext
```php
// Located at: app/Http/Middleware/EnsureTenantContext.php
// Lógica:
// 1. Si usuario es guest (no logueado) → $next($request)
// 2. Si usuario es super_admin → $tenantContext->setTenant(null) → $next($request)
// 3. Si usuario NO es super_admin y tenant_id es null → abort(403)
// 4. Verificar que tenant esté activo
```

## Solución Implementada

### Para usuarios con navegador
1. **Limpiar cookies del sitio**
   - Chrome: DevTools → Application → Clear site data
   - Firefox: Configuración → Privacidad → Limpiar datos

2. **Verificar configuración de sesiones**
   ```bash
   php artisan config:show session
   # Session driver: file
   # Session cookie: inversiones-gacov-sas-session
   # Session lifetime: 480
   ```

3. **Reintentar login**
   - Cerrar todas las pestañas del sitio
   - Abrir nueva pestaña en modo incógnito
   - Acceder a http://localhost:9119
   - Iniciar sesión con credenciales correctas

### Para debugging con curl
```bash
# 1. Obtener página de login (para cookies)
curl -c /tmp/cookies.txt http://localhost:9119/login

# 2. Extraer token CSRF de la página
CSRF=$(grep '_token' /tmp/cookies.txt | awk '{print $7}')

# 3. Hacer login con cookies y CSRF
curl -b /tmp/cookies.txt -c /tmp/cookies.txt \
  -X POST http://localhost:9119/login \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d "_token=${CSRF}&email=superadmin@gacov.com.co&password=SuperGacov2026!%24"

# 4. Acceder a ruta protegida
curl -b /tmp/cookies.txt http://localhost:9119/transfers/create
```

## Prevención

### Checklist antes de reportar error
- [ ] ¿Las cookies están habilitadas en el navegador?
- [ ] ¿El dominio de la cookie coincide con la URL?
- [ ] ¿La sesión no ha expirado?
- [ ] ¿Las credenciales son correctas?
- [ ] ¿El usuario tiene los permisos necesarios?

### Configuración recomendada
```env
# .env
SESSION_DRIVER=file
SESSION_LIFETIME=480
SESSION_SECURE_COOKIE=false
SESSION_HTTP_ONLY=true
SESSION_SAME_SITE=lax
```

## Notas Adicionales

- El super admin NO requiere `tenant_id` (puede ser null)
- El middleware `EnsureTenantContext` maneja super_admin specially
- Los errores 403 en el navegador pueden ser por caché

## Referencias
- Middleware: `app/Http/Middleware/EnsureTenantContext.php`
- Login Controller: `app/Http/Controllers/Auth/AuthenticatedSessionController.php`
- Sesiones: `config/session.php`
