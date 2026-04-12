# Estado del proyecto

## Resumen ejecutivo

El sistema ya tiene base operativa en inventario y una base SaaS funcional en `super-admin`, pero la parte comercial todavía está en consolidación: hoy puedes crear clientes, manejar planes y suscripciones, registrar pagos internos y ver un reporte financiero, aunque todavía no existe una capa completa de acceso escalonado por fase ni una factura formal para entregar al cliente.

## Completado

- [x] CRUD base de clientes/tenants en `super-admin`
- [x] Catálogo de planes y módulos visible para administración interna
- [x] Cambio de suscripción con historial de registros en `subscriptions`
- [x] Perfil financiero por cliente con fase comercial, valor de fase y valor total
- [x] Registro manual de pagos recibidos por cliente
- [x] Reporte interno consolidado de pagos y saldo por fase
- [x] Centro de proyecto con biblioteca documental visible desde `super-admin`

## En curso

- [ ] Alinear la fase comercial con la fase técnica que realmente habilita módulos
- [ ] Convertir el acceso por fase en un control real y no solo informativo
- [ ] Definir el flujo oficial para upgrades escalonados del cliente actual
- [ ] Diseñar factura formal con numeración, emisor, cliente, conceptos y saldo

## Pendientes críticos

- [ ] Corregir la estrategia de fases de módulos para que no queden todos en fase 1 por defecto
- [ ] Aplicar validación real de módulos/fase en rutas o pantallas del sistema
- [ ] Separar “reporte interno” de “factura para cliente” con modelos distintos
- [ ] Definir política de pagos operativos vs pagos que abonan al proyecto

## Siguiente foco recomendado

1. Cerrar la auditoría del `super-admin`.
2. Decidir la matriz de acceso escalonado por fase para el cliente actual.
3. Implementar el registro comercial completo: fases, pagos, factura y evidencia.
4. Convertir ese flujo en entregable repetible para futuros clientes.
