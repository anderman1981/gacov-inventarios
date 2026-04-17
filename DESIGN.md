# DESIGN.md — Sistema visual GACOV

Este documento es la fuente de verdad visual del aplicativo. Toda nueva pantalla, ajuste de UI, componente, modal, gráfico, helper o estado visual debe respetar estas reglas sin excepción.

## 1) Marca

Logo oficial del sistema:

- `/Users/andersonmartinezrestrepo/DEV-PROJECTS/INVERSIONES GACOV/gacov-inventarios/resources/images/logo.jpg`

El logo muestra una marca con:

- Rojo dominante en el símbolo principal.
- Negro en la tipografía.
- Blanco como fondo de contraste.
- Grises claros para superficies, bordes y separación visual.

## 2) Paleta corporativa obligatoria

La interfaz del sistema debe construirse solo con esta familia cromática:

- Negro
- Gris claro
- Rojo corporativo
- Blanco

### Tokens recomendados

Usar estas variables como base en CSS:

```css
--amr-primary: #D71920
--amr-primary-dark: #A31217
--amr-primary-light: #F04B4F
--amr-secondary: #111111
--amr-bg-base: #0B0B0B
--amr-bg-surface: #161616
--amr-text-primary: #FFFFFF
```

## 3) Regla de marca

- Nunca cambiar los colores de marca por pantalla, módulo, rol o estado puntual.
- Si un componente necesita destacar, usar rojo corporativo, gris, blanco o variaciones de opacidad.
- No introducir colores ajenos a la marca como azul, violeta, verde o amarillo para decoración general.
- Si un estado necesita codificación, resolverlo con peso visual, bordes, fondos suaves, iconografía o variaciones del mismo rojo/gris.

## 4) Fondo y superficies

- Fondo principal: negro o gris muy oscuro.
- Superficies: gris oscuro con bordes suaves.
- Bloques principales: blanco o gris muy claro cuando la legibilidad lo requiera.
- No saturar la interfaz con gradientes llamativos.

## 5) Componentes

- Priorizar iconos y ayudas visuales sobre texto repetido.
- Usar tooltips elegantes y cortos para explicar acciones.
- Mantener botones principales en rojo corporativo.
- Acciones secundarias deben verse neutras, limpias y discretas.
- Las tarjetas KPI, gráficos y paneles deben aprovechar el espacio sin dejar vacíos innecesarios.

## 6) Tipografía

- Títulos: `Space Grotesk` o equivalente limpia y corporativa.
- Texto: `Inter`.
- Código o valores técnicos: `JetBrains Mono`.

## 7) Layout

- Evitar cards excesivamente altas con mucho aire interno.
- Favorecer rejillas compactas y responsivas.
- Menú lateral compacto en pantallas medias y móvil.
- El logo debe conservar presencia, pero no competir con el contenido principal.

## 8) Login y autenticación

- El login debe mantener la misma identidad visual que el resto del sistema.
- Para el login, el fondo exterior debe permanecer en gris oscuro y el card interno debe ser blanco con tipografía negra para conservar la lectura y resaltar el logo.
- No usar colores ajenos a la marca en estados del formulario.
- Mantener alta legibilidad y contraste.

## 9) Cambios futuros

Cuando se pida un movimiento, una corrección o una nueva función:

- No alterar la identidad cromática de la marca.
- No introducir otra paleta por comodidad.
- Usar siempre estos tokens o sus variaciones derivadas.
- Si un diseño requiere un nuevo estado o matiz, debe derivarse del negro, gris, rojo y blanco.
