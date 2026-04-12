# Activación de fases por cliente

Fecha: 2026-04-12

## Fuente de verdad

- La fase operativa activa del cliente ahora vive en `tenant_billing_profiles.current_phase`.
- Esa fase gobierna el acceso real a módulos para todos los usuarios del tenant.
- El plan comercial sigue existiendo como referencia de negocio, pero ya no abre módulos por sí solo.

## Reglas activas

- `F1`: `auth`, `dashboard`, `drivers`, `inventory`, `products`, `machines`, `transfers`, `users`, `ocr`
- `F2`: suma `routes`, `sales`, `reports`
- `F3`: suma `analytics`, `alerts`
- `F4`: suma `world_office`, `geolocation`, `api`
- `F5`: suma `white_label`

## Super admin

- Puede escalar clientes entre fases desde la ficha del tenant.
- También puede aplicar la fase desde `Super Admin -> Módulos`, y ese atajo limpia overrides manuales para que vuelva a mandar la fase operativa.
- Los widgets de `Sistema` y `Estado del proyecto` quedaron visibles solo para `super_admin`.

## Cliente actual GACOV

- Tenant base: `gacov`
- Perfil operativo inicial: `F1`
- Usuarios base sincronizados:
  - `admin@gacov.com.co`
  - `manager@gacov.com.co`
  - `contador@gacov.com.co`
  - `osvaldo@gacov.com.co`
  - `andres@gacov.com.co`

## Validación

- Se validó que un conductor en `F1` puede entrar a surtido pero no a ventas.
- Al subir el cliente a `F2` desde super admin, el mismo conductor queda habilitado para ventas.
