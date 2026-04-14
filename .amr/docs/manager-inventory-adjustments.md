# Ajustes de inventario para manager

Fecha: 2026-04-14

## Regla operativa

- `manager` puede abrir ajustes sobre bodegas de `vehiculo` y `maquina`.
- La primera carga en una bodega sin historial se registra como `carga_inicial`.
- Después de la carga inicial, cualquier cambio pasa a `ajuste_manual`.
- Todo `ajuste_manual` exige observación obligatoria.
- Cuando el ajuste lo realiza un usuario que no es `admin` ni `super_admin`, el sistema notifica al `admin` para revisión.

## Puntos visibles en UI

- En `Inventario > Vehículos` aparece la acción `Registrar carga inicial` o `Agregar mercancía`.
- En `Inventario > Vehículos` también aparece `Carga masiva por Excel` para managers y admins.
- En `Inventario > Máquinas` aparece la acción `Carga inicial` o `Corregir inventario`.
- En el formulario de ajuste se explica si el movimiento es inicial o si ya requiere observación.
- El dashboard de `admin` y `super_admin` muestra la bandeja `Ajustes pendientes de revisión`.

## Cobertura de prueba

- Acceso del `manager` al ajuste de vehículo.
- Acceso del `manager` a la carga masiva de vehículos por Excel.
- Importación inicial de vehículos sin observación obligatoria.
- Ajuste posterior de vehículos por Excel con observación obligatoria y notificación al admin.
- Registro de carga inicial sin observación obligatoria.
- Bloqueo de ajuste posterior sin observación.
- Notificación al `admin` cuando el `manager` corrige inventario después de la carga inicial.
