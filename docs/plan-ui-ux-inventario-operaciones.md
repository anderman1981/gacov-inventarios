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

- [x] Rediseñar el bloque principal de registro de venta para que quepa completo en responsive sin scroll horizontal.
- [x] Agregar un filtro visible de stock disponible para separar productos con stock, stock bajo y sin stock.
- [x] Ajustar la tabla para que el contenido se adapte al ancho de pantalla.
- [x] Mantener editable el precio unitario por línea.
- [x] Confirmar que la observación por producto siga visible y cómoda de editar.
- [x] Revisar el comportamiento en pantallas pequeñas y evitar columnas innecesarias.
- [x] Validar paginación amigable con opciones `10`, `20`, `50`, `100` para recorrer el catálogo sin cargar demasiadas filas.
- [x] Registrar el hallazgo de CI/CD: `php artisan migrate --force` falló en GitHub Actions por acceso denegado a MySQL con `gacov_user`; dejar el fix documentado para revisar variables, credenciales y disponibilidad del servicio antes del deploy.

**Resultado:** la vista de venta quedó en formato tabla, con paginación y filtro de stock, y el incidente de CI/CD quedó anotado para validar el pipeline de GitHub antes de promover cambios de base de datos.

### 3. Vista interna de máquinas

- [x] Corregir colores de texto con baja legibilidad.
- [x] Agregar paginación configurable con opciones `10`, `20`, `50`, `100`.
- [x] Incorporar filtros en el stock actual.
- [x] Alinear la altura visual del bloque de stock con la información general.
- [x] Habilitar scroll interno solo donde sea necesario, sin romper la vista completa.
- [x] Validar que la tabla conserve una lectura limpia en responsive.
- [x] Revisar si el panel de stock actual puede compartir el mismo patrón visual de paginación usado en ventas.

**Resultado:** la vista interna de máquina quedó con filtros automáticos, paginación y panel de stock balanceado frente a la información general.

### 4. Surtir máquina

- [x] Reorganizar la vista para mejorar distribución y evitar espacios desperdiciados.
- [x] Agregar filtros en la tabla de productos.
- [x] Mostrar más columnas para `admin`, `manager`, `contador` y `super_admin` sin edición: costo, precio de venta y stock.
- [x] Para `conductor`, reemplazar esa información sensible por una acción clara para registrar novedad.
- [x] Registrar ubicación automáticamente cuando el conductor ingrese información.
- [x] Validar que el flujo siga siendo usable en móvil y sin scroll horizontal excesivo.
- [x] Agregar paginación real para evitar una tabla interminable.
- [x] Encapsular la tabla en un scroll interno con encabezado fijo.

**Resultado:** `Surtir máquina` ahora usa una distribución más amplia, filtros de búsqueda/estado por producto, paginación real, scroll interno controlado, columnas comerciales para roles con lectura extendida y una acción de novedad persistida por ítem para conductor, con geolocalización automática al guardar.

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
