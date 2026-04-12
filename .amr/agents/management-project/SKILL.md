# Management Project Agent - GACOV Inventarios

## Descripción
Este agente es responsable de gestionar TODO el ciclo de vida del proyecto en GitHub:
- Issues (bugs, features, fixes, documentation)
- GitHub Projects (roadmap, status, progreso)
- Discussions (preguntas, ideas, anuncios)
- Actions (CI/CD, workflows)
- Documentación del proyecto
- Ramas y flujo de trabajo Git

## Configuración

### Repositorio
- **Owner:** anderman1981
- **Repo:** gacov-inventarios
- **URL:** https://github.com/anderman1981/gacov-inventarios
- **Token:** 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

### GitHub Project
- **Project URL:** https://github.com/users/anderman1981/projects/4
- **Project ID:** PVT_kwHOBXPLac4BUagA
- **Status Field ID:** PVTSSF_lAHOBXPLac4BUagAzhBixJ0
- **Status Options:**
  - Todo (f75ad846) - Verde
  - In Progress (47fc9ee4) - Amarillo
  - Done (98236657) - Morado

## Workflow Git

```
feature/* → develop → staging → main
fix/* → develop → staging → main
hotfix/* → main (directo, luego merge a develop)
```

### Ramas
- **main:** Rama principal protegida, código en producción
- **staging:** Rama de pre-producción
- **develop:** Rama de desarrollo
- **feature/*:** Ramas de features nuevas
- **fix/*:** Ramas de correcciones
- **hotfix/*:** Correcciones urgentes

## Etiquetas de Issues

| Label | Color | Uso |
|-------|-------|-----|
| bug | d73a4a | Bugs y errores |
| enhancement | a2eeef | Nuevas features |
| documentation | 0366d6 | Documentación |
| deploy | f9d71c | Despliegues |
| staging | 5ebeff | Issues de staging |
| production | ff7f7f | Issues de producción |
| high-priority | ededed | Prioridad alta |
| question | d4e5f7 | Preguntas |
| help-wanted | 008672 | Ayuda necesaria |

## Tareas del Agente

### 1. Crear Issues Automáticamente
Cada vez que se detecte un problema, feature o tarea:
```bash
# Crear issue vía API
curl -X POST "https://api.github.com/repos/anderman1981/gacov-inventarios/issues" \
  -H "Authorization: Bearer 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" \
  -d '{"title":"...", "body":"...", "labels":["..."]}'
```

### 2. Agregar Issues al Project
```bash
# Obtener node_id del issue
# Agregar al project via GraphQL
curl -X POST "https://api.github.com/graphql" \
  -H "Authorization: Bearer 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" \
  -d '{"query":"mutation { addProjectV2ItemById(input: {projectId: \"PVT_kwHOBXPLac4BUagA\", contentId: \"ISSUE_NODE_ID\"}) { item { id } } }"}'
```

### 3. Actualizar Status del Issue
```bash
# Setear status del item en el project
curl -X POST "https://api.github.com/graphql" \
  -H "Authorization: Bearer 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" \
  -d '{"query":"mutation { updateProjectV2ItemFieldValue(input: {projectId: \"PVT_kwHOBXPLac4BUagA\", itemId: \"ITEM_ID\", fieldId: \"PVTSSF_lAHOBXPLac4BUagAzhBixJ0\", value: {singleSelectOptionId: \"STATUS_OPTION_ID\"}}) { projectV2Item { id } } }"}'
```

### 4. Crear Discussions
```bash
curl -X POST "https://api.github.com/graphql" \
  -H "Authorization: Bearer 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" \
  -d '{"query":"mutation { createDiscussion(input: {repositoryId: \"REPO_ID\", title: \"...\", body: \"...\", categoryId: \"CATEGORY_ID\"}) { discussion { id } } }"}'
```

### 5. Gestionar Workflows (Actions)
- Verificar estado de workflows
- Consultar logs de CI/CD
- Documentar resultados

## Conventional Commits

```
feat(scope): descripción en inglés
fix(scope): descripción en inglés
docs(scope): descripción en inglés
test(scope): descripción en inglés
chore(scope): descripción en inglés
refactor(scope): descripción en inglés
hotfix(scope): descripción en inglés
style(scope): descripción en inglés
```

## Comandos Útiles

### Listar Issues
```bash
curl -s "https://api.github.com/repos/anderman1981/gacov-inventarios/issues?state=all" \
  -H "Authorization: Bearer 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"
```

### Listar PRs
```bash
curl -s "https://api.github.com/repos/anderman1981/gacov-inventarios/pulls?state=all" \
  -H "Authorization: Bearer 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"
```

### Ver Project
```bash
curl -s -X POST "https://api.github.com/graphql" \
  -H "Authorization: Bearer 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" \
  -d '{"query":"query { node(id: \"PVT_kwHOBXPLac4BUagA\") { ... on ProjectV2 { title items(first: 20) { nodes { id fieldValues(first: 10) { nodes { ... on ProjectV2ItemFieldValue { field { ... on ProjectV2Field { name } } value } } } content { ... on Issue { title number state } } } } } } }"}'
```

## Reglas

1. **Siempre crear issue** para cualquier bug, feature o tarea nueva
2. **Siempre agregar al project** con el status correcto
3. **Usar etiquetas** apropiadas
4. **Documentar decisiones** de arquitectura en issues
5. **Mantener project actualizado** con el progreso real
6. **Crear discusión** para temas que requieren debate
7. **Verificar CI/CD** después de cada merge

## Ejemplo de Issue Completo

```markdown
## Problema/Feature
Descripción clara del issue

## Causa/Contexto
Información adicional

## Solución/Propuesta
Cómo se planea resolver

## Tareas
- [ ] Subtarea 1
- [ ] Subtarea 2

## Criterios de Aceptación
- Criterio 1
- Criterio 2

## Evidencia
Capturas de pantalla, logs, etc.
```

## Contacto
- **Owner:** Anderson Martínez (anderman1981)
- **Proyecto:** GACOV Inventarios - SaaS multi-tenant
