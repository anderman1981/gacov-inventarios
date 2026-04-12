# Auditoría Super Admin

## Estado actual

La sección `super-admin` ya cubre cinco frentes reales:

- panel general con KPIs de tenants, suscripciones y cartera interna
- gestión de clientes (`tenants`)
- catálogo comercial de planes y módulos
- control financiero por fases con registro de pagos
- centro de proyecto para documentación y seguimiento

En otras palabras: la base existe y no está en cero. El problema no es ausencia de módulo, sino que la capa comercial y de gobierno todavía no está cerrada.

## Qué ya está funcionando

### Operación SaaS interna

- crear, editar, activar y suspender clientes
- asignar y cambiar suscripciones
- ver planes y módulos cargados
- ver clientes recientes con fase técnica y fase comercial

### Control financiero interno

- definir fase comercial actual
- definir valor aprobado por fase
- definir valor total del proyecto
- registrar pagos manuales
- marcar si un pago abona o no al valor total
- generar un reporte interno consolidado

## Hallazgos de auditoría

### 1. El acceso escalonado por fase existe en discurso, pero no está cerrado en operación

Hoy hay dos conceptos de fase:

- fase técnica del plan (`subscription_plans.phase`)
- fase comercial del control financiero (`tenant_billing_profiles.current_phase`)

Eso permite desalineaciones: un cliente puede quedar comercialmente en F1 pero técnicamente en un plan superior, o viceversa.

### 2. Los módulos todavía no representan una ruta segura de progresión comercial

El catálogo de módulos existe, pero la estrategia necesita cerrarse con una matriz explícita:

- qué se libera en F1
- qué se libera en F2
- qué solo se abre por override manual
- qué queda fuera del core comercial

### 3. El sistema registra pagos, pero aún no factura formalmente

Lo actual sirve para control interno AMR:

- fecha
- tipo
- valor
- referencia/factura
- notas

Lo que falta para cliente es una entidad de factura con:

- consecutivo
- emisor
- cliente
- conceptos facturados
- subtotal
- impuestos o aclaración de no aplicación
- total
- saldo
- estado
- PDF o formato imprimible

### 4. El reporte existente no debe confundirse con factura

`billing-report` hoy es un consolidado interno. Está bien conservarlo, pero no debe usarse como documento final al cliente.

## Semáforo

### Verde

- tenants
- suscripciones
- pagos internos
- documentación del proyecto

### Amarillo

- fases comerciales
- gating por módulos
- gobierno del roadmap comercial por cliente

### Rojo

- factura formal para entregar
- política consistente de upgrade por fase

## Plan de avance recomendado

### Etapa 1. Cierre de control

Objetivo:

dejar `super-admin` como centro de verdad para el estado comercial del cliente actual.

Entregables:

- una sola regla para definir fase activa
- criterio formal para upgrade
- checklist visible de estado

### Etapa 2. Acceso escalonado del cliente

Objetivo:

abrir el sistema por etapas al cliente actual sin entregar de una todo el alcance.

Entregables:

- matriz fase → módulos permitidos
- overrides manuales por tenant
- decisión de qué módulos son demo, qué módulos son productivos y qué módulos son premium

### Etapa 3. Cartera y pagos

Objetivo:

dejar trazabilidad limpia de lo que el cliente ha pagado y lo que aún debe.

Entregables:

- tipos de cobro normalizados
- saldo por fase
- saldo total
- histórico cronológico de pagos

### Etapa 4. Factura al cliente

Objetivo:

emitir un documento comercial consistente y repetible.

Entregables:

- modelo de factura
- plantilla imprimible/PDF
- relación factura ↔ pagos
- estado de cobro

## Recomendación puntual para el cliente actual

No abrir todo por plan técnico todavía.

Abrir por fases comerciales controladas:

1. Fase 1: inventario, productos, traslados y operación base
2. Fase 2: reportes, ventas y mejoras operativas
3. Fase 3+: analytics, integraciones, geolocalización y add-ons

Mientras esa política se implementa, cualquier acceso extra debería darse por override explícito y documentado.
