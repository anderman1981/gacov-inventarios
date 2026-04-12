# Modelo de factura cliente

## Objetivo

Separar el control interno de pagos del documento formal que se entrega al cliente.

## Diferencia clave

### Pago interno

Registra que AMR recibió dinero:

- fecha
- valor
- tipo
- fase
- notas

### Factura

Formaliza qué se le está cobrando al cliente:

- número de factura
- fecha de emisión
- cliente
- conceptos
- subtotal
- impuestos
- total
- saldo
- vencimiento

## Modelo recomendado

### Factura

Campos mínimos:

- `id`
- `tenant_id`
- `invoice_number`
- `status`
- `issued_at`
- `due_at`
- `currency`
- `billing_name`
- `billing_nit`
- `billing_email`
- `billing_address`
- `phase`
- `concept_summary`
- `subtotal`
- `discount_total`
- `tax_total`
- `total`
- `paid_total`
- `balance_due`
- `notes`
- `pdf_path`
- `created_at`
- `updated_at`

Estados sugeridos:

- `draft`
- `issued`
- `partially_paid`
- `paid`
- `cancelled`
- `overdue`

### Ítems de factura

Campos mínimos:

- `id`
- `invoice_id`
- `type`
- `description`
- `quantity`
- `unit_price`
- `line_total`
- `phase`
- `created_at`
- `updated_at`

Tipos sugeridos:

- `phase_development`
- `operational_fee`
- `extra_hours`
- `extraordinary_charge`
- `discount`

### Aplicación de pagos

Campos mínimos:

- `id`
- `invoice_id`
- `tenant_payment_id`
- `applied_amount`
- `applied_at`
- `created_at`
- `updated_at`

Esto permite:

- un pago parcial sobre una factura
- un pago que cubre varias facturas
- trazabilidad entre cartera y caja recibida

## Plantilla sugerida para el cliente

### Encabezado

- logo AMR Tech
- razón social emisora
- NIT
- datos de contacto
- número de factura
- fecha de emisión
- fecha de vencimiento

### Datos del cliente

- empresa
- NIT o documento
- correo
- teléfono
- dirección

### Tabla de cobro

- concepto
- fase
- cantidad
- valor unitario
- total

### Totales

- subtotal
- descuentos
- impuestos
- total factura
- pagos aplicados
- saldo pendiente

### Pie

- observaciones comerciales
- referencia de propuesta
- datos de pago

## Primera versión recomendada para GACOV

No arrancar con facturación electrónica compleja.

Primero sacar una factura comercial simple en PDF con:

- consecutivo manual controlado
- datos del cliente
- conceptos
- total
- saldo
- referencia a la fase

Después, si el flujo se estabiliza, crecer a:

- relación factura ↔ pago
- envío por correo
- PDF descargable desde `super-admin`
- control de vencidas

## Regla práctica

No usar `tenant_payments.invoice_number` como si ya fuera la factura del sistema.

Ese campo solo debe quedar como referencia temporal hasta que exista el modelo formal de factura.
