# Calendario operativo de rutas

Fecha: 2026-04-14

## Regla base

- `routes.driver_user_id` + `users.route_id` siguen siendo la asignación fija del conductor.
- El manager o admin puede programar excepciones por fecha sin alterar esa base.

## Nueva capa diaria

- Tabla: `route_schedule_assignments`
- Llave funcional: `tenant_id + route_id + assignment_date`
- Permite:
  - mover una ruta a otro conductor por un día,
  - dejar una ruta sin asignar por un día,
  - darle a un conductor una segunda ruta en el mismo día.

## Resolución efectiva para el conductor

1. Si la ruta tiene programación diaria para hoy, manda esa programación.
2. Si no la tiene, manda la asignación base.
3. El selector de ruta del conductor ahora muestra todas sus rutas efectivas del día.

## UI operativa

- `Operaciones -> Rutas y conductores`: tablero base, una ruta por conductor.
- `Calendario operativo`: vista semanal con drag and drop por día.

## Compatibilidad

- Si existe data antigua donde solo `users.route_id` quedó poblado, el resolver toma ese valor como fallback para no romper operación mientras la base queda sincronizada.
