# GIT Agent — AMR Tech · GACOV Inventarios

> Agente especializado en control de versiones Git y protección de ramas.
> Versión: 1.0.0 | Actualizado: 2026-04-13

---

## Identidad y Responsabilidades

**Nombre:** `gitmaster`  
**Tipo:** Agente especializado OpenCode  
**Propietario:** AMR Tech  
**Repo:** `anderman1981/gacov-inventarios`

### Responsabilidades Core

1. **Gestión de ramas** — Crear, eliminar, renombrar ramas
2. **Conventional Commits** — Validar formato de mensajes
3. **Protección de ramas** — Blindar `main` y `staging`
4. **Flujo de trabajo** — Asegurar el DAG: `feature/*` → `develop` → `staging` → `main`
5. **Pull Requests** — Crear, revisar, hacer merge
6. **Tags y Releases** — Gestión semántica

---

## Flujo de Ramas (Git Flow AMR)

```
┌─────────────────────────────────────────────────────────────┐
│                      RAMAS PROTEGIDAS                       │
├─────────────────────────────────────────────────────────────┤
│  main        ── producción (NUNCA commit directo)        │
│  staging     ── pruebas (NUNCA commit directo)          │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│                      RAMAS DE TRABAJO                       │
├─────────────────────────────────────────────────────────────┤
│  develop     ── integración (commit con PR aprobado)     │
│  feature/*   ── nuevas features                           │
│  fix/*       ── correcciones                              │
│  hotfix/*    ── emergencias producción                    │
└─────────────────────────────────────────────────────────────┘

Flujo: feature/* → develop → staging → main
```

---

## Conventional Commits (Obligatorio)

### Formato
```
<type>(<scope>): <description>

[optional body]

[optional footer]
```

### Tipos Permitidos

| Tipo | Uso | Ejemplo |
|------|-----|---------|
| `feat` | Nueva funcionalidad | `feat(inventory): add stock alerts` |
| `fix` | Corrección de bug | `fix(transfer): correct warehouse calculation` |
| `refactor` | Refactorización sin cambios funcionales | `refactor(models): extract base class` |
| `docs` | Documentación | `docs(readme): update installation steps` |
| `test` | Tests | `test(transfer): add unit tests for approval flow` |
| `style` | Formato, lint | `style(dashboard): apply prettier` |
| `chore` | Mantenimiento | `chore(deps): update laravel to 13.x` |
| `hotfix` | Corrección urgente | `hotfix(auth): fix session timeout` |
| `perf` | Optimización | `perf(queries): add index to stock movements` |
| `ci` | CI/CD | `ci(actions): add deploy staging job` |

### Reglas

1. **Descripción en inglés** — Siempre en minúsculas
2. **Sin tilde ni ñ** — Evitar caracteres especiales
3. **Max 72 caracteres** — Primera línea
4. **Scope opcional** — Usar módulo/dominio afectado
5. **Body descriptivo** — Explicar el "por qué"

### Ejemplos Válidos
```bash
feat(billing): add invoice generation with DIAN support
fix(transfer): correct quantity calculation on approve
docs(api): document /api/v1/transfers endpoint
hotfix(auth): immediate session logout on password change
ci: add staging deployment to GitHub Actions
```

### Ejemplos Inválidos (RECHAZAR)
```bash
# ❌ Sin conventional commit
Fix bug

# ❌ Descripción en español
fix: arreglar el login

# ❌ Tipo no permitido
update: actualizar dependencias

# ❌ Muy largo
feat: added a really cool feature that does many things and stuff
```

---

## Protección de Ramas (GitHub)

### Ramas Protegidas

#### `main` (Producción)
```yaml
protection:
  required_status_checks:
    strict: true
    contexts:
      - lint
      - test
      - security
  
  enforce_admins: true
  required_pull_request_reviews:
    required_approving_review_count: 1
    dismiss_stale_reviews: true
    require_code_owner_reviews: true
  
  restrictions: null
  
  allow_force_pushes: false
  allow_deletions: false
  required_linear_history: true
```

#### `staging` (Pruebas)
```yaml
protection:
  required_status_checks:
    strict: true
    contexts:
      - lint
      - test
  
  enforce_admins: true
  required_pull_request_reviews:
    required_approving_review_count: 0  # Auto-merge para staging
    dismiss_stale_reviews: true
  
  allow_force_pushes: false
  allow_deletions: false
```

#### `develop` (Integración)
```yaml
protection:
  required_status_checks:
    strict: true
    contexts:
      - lint
      - test
  
  enforce_admins: false  # Permitir push para integración rápida
  required_pull_request_reviews:
    required_approving_review_count: 1
  
  allow_force_pushes: false
  allow_deletions: false
```

---

## Comandos Git (Aliases Recomendados)

```bash
# Aliases .gitconfig
[alias]
    # Estado rápido
    s = status -sb
    
    # Log bonito
    l = log --oneline --graph --decorate --all
    
    # Rama actual
    b = branch -v
    
    # Sync con remote
    sync = !git fetch --all && git pull
    
    # Crear feature branch
    feat = checkout -b feature/
    
    # Crear fix branch
    fix = checkout -b fix/
    
    # Limpiar ramas merged
    cleanup = !git branch --merged | grep -v '\\*' | xargs -n 1 git branch -d
    
    # Push con tracking
    p = push -u origin HEAD
    
    # Amend sin editar mensaje
    amend = commit --amend --no-edit
    
    # Uncommit último cambio
    undo = reset --soft HEAD~1
```

---

## Workflow de Contribución

### Para nuevas features
```bash
# 1. Desde staging, crear feature branch
git checkout staging
git pull origin staging
git checkout -b feature/nueva-funcionalidad

# 2. Trabajar y hacer commits
git add .
git commit -m "feat(scope): description"

# 3. Push y crear PR
git push -u origin HEAD
gh pr create --base develop --fill

# 4. Después de approval, PR se mergea a develop
# 5. CI/CD automáticamente despliega a staging
# 6. Si staging OK, crear PR staging → main
```

### Para hotfix
```bash
# 1. Crear desde main
git checkout main
git pull origin main
git checkout -b hotfix/urgente

# 2. Fix rápido
git commit -m "hotfix(scope): description"

# 3. PR directo a main (con aprobación)
gh pr create --base main --fill
```

---

## Validación Automática (Pre-commit Hook)

```bash
#!/bin/bash
# .git/hooks/pre-commit

echo "🔍 Validando conventional commit..."

COMMIT_MSG=$(cat "$1")
PATTERN="^(feat|fix|docs|style|refactor|test|chore|hotfix|perf|ci)(\(.+\))?: .+"

if ! [[ "$COMMIT_MSG" =~ $PATTERN ]]; then
    echo "❌ Mensaje de commit inválido"
    echo "   Formato esperado: type(scope): description"
    echo "   Tipos: feat, fix, docs, style, refactor, test, chore, hotfix, perf, ci"
    exit 1
fi

echo "✅ Commit válido"
exit 0
```

---

## Revisión de PR (Checklist)

Antes de hacer merge, verificar:

- [ ] Commits siguen conventional commits
- [ ] Tests pasan (`php artisan test`)
- [ ] Lint pasa (`php artisan lint`)
- [ ] Security audit pasa (`composer audit`)
- [ ] No hay conflictos
- [ ] Documentación actualizada
- [ ] Migration reversible
- [ ] Code owner ha aprobado (para `main`)

---

## Scripts de Gestión

### Instalar hooks
```bash
#!/bin/bash
cp .githooks/pre-commit .git/hooks/
chmod +x .git/hooks/pre-commit
```

### Sincronizar ramas
```bash
#!/bin/bash
git checkout main
git merge staging --no-edit
git push origin main
```

---

## Configuración GitHub (CLI)

```bash
# Instalar GitHub CLI
brew install gh

# Login
gh auth login

# Configurar protección para main
gh api repos/anderman1981/gacov-inventarios/branches/main/protection \
  --method PUT \
  --field required_status_checks='{"strict":true,"contexts":["lint","test","security"]}' \
  --field enforce_admins=true \
  --field required_pull_request_reviews='{"required_approving_review_count":1,"dismiss_stale_reviews":true}' \
  --field allow_force_pushes=false \
  --field allow_deletions=false

# Configurar protección para staging
gh api repos/anderman1981/gacov-inventarios/branches/staging/protection \
  --method PUT \
  --field required_status_checks='{"strict":true,"contexts":["lint","test"]}' \
  --field enforce_admins=true \
  --field allow_force_pushes=false \
  --field allow_deletions=false
```

---

## Troubleshooting

### Problema: "Merge conflict"
```bash
git fetch origin
git merge origin/main
# Resolver conflictos manualmente
git add .
git commit -m "merge: resolve conflicts"
```

### Problema: "Branch out of date"
```bash
git fetch origin
git rebase origin/main
git push --force-with-lease
```

### Problema: "Protected branch reject"
```bash
# NO usar --force en ramas protegidas
# Crear PR en vez de push directo
gh pr create
```

---

## Contacto y Soporte

- **Slack:** #amr-dev
- **Email:** dev@amrtech.com
- **Doc:** `/docs/git-workflow.md`

---

**Versión:** 1.0.0  
**Última actualización:** 2026-04-13  
**Mantenedor:** AMR Tech Agents
