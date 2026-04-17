# Plan de trabajo - UI/UX e inventario operativo

## Objetivo

Mejorar la experiencia de uso y la lógica operativa de inventarios, ventas, máquinas, surtido y traslados, priorizando pantallas responsive, filtros útiles, mejor distribución del contenido y reglas claras por rol.

## Estado del plan

- Fecha de inicio: 17 de abril de 2026
- Estado general: pendiente
- Enfoque: primero UX responsive, luego filtros y permisos, después reglas de negocio

## Orden de trabajo

### 1. Efectivo a conductores

- [x] Revisar por qué el filtro no se activa automáticamente al entrar al módulo.
- [x] Confirmar cuál debe ser el criterio por defecto: ruta activa, conductor actual o último registro.
- [x] Aplicar el filtro automático al cargar la vista.
- [x] Validar que el resultado inicial coincida con el contexto del conductor autenticado.
- [x] Probar en móvil y escritorio que no se pierda el estado del filtro.

**Resultado:** el índice de efectivo abre por defecto filtrado a la ruta del usuario autenticado cuando existe, y el formulario de creación también precarga ruta y conductor asociados cuando aplica.

### 2. Registrar venta en máquina

- [ ] Rediseñar el bloque principal de registro de venta para que quepa completo en responsive sin scroll horizontal.
- [ ] Agregar un filtro visible de stock disponible para separar productos con stock, stock bajo y sin stock.
- [ ] Ajustar la tabla para que el contenido se adapte al ancho de pantalla.
- [ ] Mantener editable el precio unitario por línea.
- [ ] Confirmar que la observación por producto siga visible y cómoda de editar.
- [ ] Revisar el comportamiento en pantallas pequeñas y evitar columnas innecesarias.

### 3. Vista interna de máquinas

- [ ] Corregir colores de texto con baja legibilidad.
- [ ] Agregar paginación configurable con opciones `10`, `20`, `50`, `100`.
- [ ] Incorporar filtros en el stock actual.
- [ ] Alinear la altura visual del bloque de stock con la información general.
- [ ] Habilitar scroll interno solo donde sea necesario, sin romper la vista completa.
- [ ] Validar que la tabla conserve una lectura limpia en responsive.

### 4. Surtir máquina

- [ ] Reorganizar la vista para mejorar distribución y evitar espacios desperdiciados.
- [ ] Agregar filtros en la tabla de productos.
- [ ] Mostrar más columnas para `admin`, `manager`, `contador` y `super_admin` sin edición: costo, precio de venta y stock.
- [ ] Para `conductor`, reemplazar esa información sensible por una acción clara para registrar novedad.
- [ ] Registrar ubicación automáticamente cuando el conductor ingrese información.
- [ ] Validar que el flujo siga siendo usable en móvil y sin scroll horizontal excesivo.

### 5. Traslados

- [ ] Reordenar los selectores para que el flujo sea claro: Bodega Principal → Vehículos → Máquinas.
- [ ] Permitir el camino inverso: Máquina → Vehículo → Bodega.
- [ ] Agregar observaciones por producto para soportar devoluciones y perecederos vencidos.
- [ ] Asegurar que la observación pueda marcar el motivo de retorno.
- [ ] Revisar validaciones para no romper el flujo cuando el traslado se invierte.
- [ ] Probar los dos sentidos con casos reales del inventario.

## Criterio de cierre

- [ ] No quedan secciones partidas o solapadas en móvil.
- [ ] Los filtros aparecen por defecto donde deben aparecer.
- [ ] Las tablas críticas tienen paginación y lectura clara.
- [ ] Las acciones por rol respetan permisos y contexto.
- [ ] Los flujos de venta, surtido y traslado quedan coherentes con stock real y ubicación.

## Notas de implementación

- Prioridad alta: ventas, surtido y traslados.
- Prioridad media: vista interna de máquinas.
- Prioridad base: filtros automáticos y orden de información.
- Si aparece una decisión de negocio ambigua, se documenta antes de tocar código.
