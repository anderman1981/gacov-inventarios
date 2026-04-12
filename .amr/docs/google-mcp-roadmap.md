# Roadmap de Google MCP para GACOV

Fecha de evaluación: 2026-04-12

## Objetivo

Priorizar qué servidores y recursos MCP del ecosistema Google tienen mejor encaje futuro para GACOV Inventarios, considerando:

- stack actual Laravel + MySQL + Hostinger
- uso ya incorporado de Chrome DevTools MCP
- futura necesidad de mapas, rutas, visitas y geolocalización
- potencial crecimiento hacia analítica, observabilidad y operación asistida por IA

## Recomendación priorizada

### Prioridad 1

#### Google Maps Grounding Lite MCP

Encaje: muy alto.

Usos directos:

- búsqueda de lugares con `search_places`
- cálculo de rutas con `compute_routes`
- contexto climático con `lookup_weather`

Valor para GACOV:

- asignar rutas para conductores
- calcular trayectos entre bodega, máquinas y puntos de venta
- enriquecer decisiones operativas con clima y tiempo estimado
- construir futuros paneles de cobertura, cercanía y priorización territorial

#### Google Maps Platform Code Assist toolkit

Encaje: muy alto para desarrollo.

Valor para GACOV:

- aterriza al agente sobre documentación oficial y ejemplos frescos de Maps
- reduce errores cuando se integren Places, Routes, Geocoding, Weather o mapas embebidos
- sirve como complemento del runtime real: uno ayuda a construir, el otro a operar

### Prioridad 2

#### BigQuery MCP / MCP Toolbox for Databases

Encaje: alto si el proyecto pasa a una capa analítica seria.

Valor para GACOV:

- consolidar ventas, surtidos, rutas, tiempos y eventos en analítica histórica
- cruzar datos de operación con datos geográficos
- habilitar preguntas en lenguaje natural sobre productividad, quiebres y cobertura
- preparar dashboards IA sobre zonas, rendimiento por ruta y comportamiento por máquina

Nota:

No es el primer paso porque hoy el proyecto vive en MySQL + Hostinger, pero sí es el mejor candidato de fase analítica si se adopta GCP.

### Prioridad 3

#### Firebase MCP

Encaje: medio-alto.

Valor para GACOV:

- útil si aparece app móvil de conductor o supervisor
- puede ayudar con autenticación, Firestore, Remote Config, Crashlytics y mensajería
- sirve para notificaciones, telemetría de errores y experiencias offline/realtime

Nota:

No lo pondría como base principal del core web Laravel, pero sí como acelerador de una futura capa móvil o de campo.

### Prioridad 4

#### Google Analytics MCP

Encaje: medio.

Valor para GACOV:

- útil si el proyecto abre portal público, captación comercial o funnels SaaS
- permite conversar con métricas de adquisición y comportamiento
- es de solo lectura, así que el riesgo operativo es menor

Nota:

Sirve más para marketing y producto que para la operación interna de inventarios y rutas.

### Prioridad 5

#### Google Workspace MCP

Encaje: medio.

Valor para GACOV:

- automatizar reportes en Sheets
- consultar calendarios de visitas o mantenimientos
- buscar archivos operativos en Drive
- generar documentación o comunicaciones internas

Riesgo:

- alta exposición a prompt injection indirecta si se mezclan correos, documentos o archivos no confiables

## Prioridad baja por ahora

### gcloud MCP / Observability MCP / Storage MCP

Encaje actual: bajo-medio.

Solo suben de prioridad si GACOV migra parte relevante de infraestructura a Google Cloud.

En ese escenario servirían para:

- revisar logs, métricas y trazas
- mover artefactos o respaldos con GCS
- automatizar operación cloud

Con Hostinger Shared Hosting como base actual, no son el siguiente mejor movimiento.

## Orden sugerido de adopción

1. Mantener Chrome DevTools MCP para browser real y debugging.
2. Agregar Google Maps Grounding Lite MCP para capacidades operativas de rutas y lugares.
3. Agregar Google Maps Platform Code Assist toolkit para construir la integración de mapas con documentación viva.
4. Evaluar BigQuery MCP o MCP Toolbox cuando exista necesidad de analítica histórica y consultas en lenguaje natural.
5. Evaluar Firebase MCP si nace una app móvil o una capa realtime/offline.
6. Evaluar Workspace y Analytics como extensiones de operación comercial y administrativa.

## Recomendación ejecutiva

Si Anderson solo fuera a incorporar dos recursos Google MCP adicionales después de Chrome DevTools MCP, deberían ser:

1. Google Maps Grounding Lite MCP
2. Google Maps Platform Code Assist toolkit

Si luego se quisiera un tercero, sería:

3. BigQuery MCP o MCP Toolbox for Databases

## Criterio de arquitectura

Separar el uso en dos planos:

- MCP de desarrollo: Chrome DevTools MCP + Maps Code Assist
- MCP de operación: Maps Grounding Lite + luego BigQuery/Firebase según madurez

Ese enfoque evita mezclar documentación, navegador, geodatos y analítica en un solo bloque y permite crecer por etapas sin sobrecargar la base Laravel actual.
