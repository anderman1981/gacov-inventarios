# PWA — Progressive Web App — GACOV Inventarios

## metadata
```yaml
version: 1.0.0
date: 2026-04-12
type: feature
category: pwa
```

## Visión General

GACOV Inventarios ahora puede instalarse como una aplicación nativa en dispositivos móviles (iOS/Android) y como app de escritorio en Windows/Mac/Linux.

## Características Implementadas

### 1. Manifest.json
**Archivo:** `public/manifest.json`

Define la configuración de la PWA:
- Nombre: "GACOV Inventarios"
- Modo de visualización: standalone
- Tema: Cyan (#00D4FF) / Violeta (#7C3AED)
- Shortcuts para acceso rápido

### 2. Service Worker
**Archivo:** `public/sw.js`

Funcionalidades:
- Cache de assets estáticos
- Estrategia cache-first
- Fallback offline
- Limpieza de caches antiguas
- Soporte para notificaciones push (preparado)

### 3. Banner de Instalación
**Ubicación:** `resources/views/layouts/app.blade.php`

- Aparece automáticamente cuando el navegador lo permite
- Botón "Instalar" con acción de instalación
- Botón "X" para cerrar/dismiss
- Animación slide-up

### 4. Meta Tags PWA
```html
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="GACOV">
<link rel="apple-touch-icon" href="/icons/icon-192.png">
```

## Cómo Instalar la App

### En Móvil (Android)
1. Abrir la app en Chrome
2. Tocar el banner de instalación "Instalar"
   O
1. Abrir el menú de Chrome (⋮)
2. Tocar "Instalar app" o "Agregar a pantalla de inicio"

### En iPhone/iPad
1. Abrir la app en Safari (NO en Chrome)
2. Tocar el botón de compartir (□↑)
3. Desplazarse y tocar "Agregar a pantalla de inicio"
4. Tocar "Agregar"

### En Desktop (Windows/Mac)
1. Abrir en Chrome/Edge
2. Aparecerá el banner de instalación
   O
3. Click en el icono de instalación en la barra de direcciones (⊕)

## Estructura de Archivos

```
public/
├── manifest.json          # Configuración PWA
├── sw.js                  # Service Worker
└── icons/
    ├── icon.svg           # Icono SVG original
    ├── icon-192.png       # Para móviles
    └── icon-512.png       # Para tablets/desktop
```

## Shortcuts Configurados

| Shortcut | Ruta | Descripción |
|----------|------|-------------|
| Dashboard | /dashboard | Panel principal |
| Inventario | /inventory/warehouse | Gestión de inventario |
| Traslados | /transfers | Órdenes de traslado |
| Surtido | /stockings/create | Registrar surtido |

## Iconos PNG (Generación)

Los iconos actuales son SVGs convertidos. Para producción, genera PNGs reales:

```bash
# Usando ImageMagick (requiere instalación)
convert -size 192x192 icon.svg icon-192.png
convert -size 512x512 icon.svg icon-512.png
convert -size 72x72 icon.svg icon-72.png
convert -size 96x96 icon.svg icon-96.png
convert -size 144x144 icon.svg icon-144.png
convert -size 152x152 icon.svg icon-152.png
convert -size 384x384 icon.svg icon-384.png
```

### Herramientas Online Alternativas
- [Favicon.io](https://favicon.io/)
- [RealFaviconGenerator](https://realfavicongenerator.net/)
- [App Icon Generator](https://www.appicon.co/)

## Características Offline

El Service Worker permite:
- ✅ Navegar sin conexión (páginas visitadas)
- ✅ Assets cacheados para rápido cargado
- ❌ Nuevas solicitudes requieren conexión

Para una app completamente offline, se necesitaría:
1. Cachear todas las rutas
2. Implementar sync background
3. IndexedDB para datos offline

## Detección de Instalación

```javascript
window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredPrompt = e;
    // Mostrar banner de instalación
});

window.addEventListener('appinstalled', () => {
    // Ocultar banner
});
```

## Verificación

1. Abrir DevTools (F12)
2. Ir a Application > Manifest
3. Verificar que no haya errores
4. Ir a Service Workers y verificar registro

## Browser Support

| Browser | Móvil | Desktop |
|---------|-------|---------|
| Chrome | ✅ | ✅ |
| Edge | ✅ | ✅ |
| Safari | ⚠️ Limitado | ❌ |
| Firefox | ⚠️ Limitado | ⚠️ Limitado |

**Nota:** iOS Safari tiene soporte limitado para PWA. La experiencia de instalación funciona pero algunas features pueden no estar disponibles.

## Troubleshooting

### Banner no aparece
1. Verificar que no esté ya instalada
2. Verificar que Manifest sea válido
3. Verificar HTTPS (o localhost para desarrollo)
4. Chrome requiere interacción del usuario

### Service Worker no registra
```javascript
// Ver errores en consola
navigator.serviceWorker.register('/sw.js')
    .then(reg => console.log('OK'))
    .catch(err => console.error('Error:', err));
```

### Iconos no se ven
1. Verificar que los PNGs existan
2. Verificar tamaños en manifest.json
3. Limpiar cache del navegador

## Comandos Útiles

```bash
# Limpiar todos los Service Workers cacheados
# En Chrome DevTools: Application > Clear storage > Clear site data

# Verificar manifest
# En Chrome DevTools: Application > Manifest

# Forzar update del Service Worker
navigator.serviceWorker.getRegistration().then(reg => reg.update());
```

## Referencias

- [Google PWA Guide](https://web.dev/progressive-web-apps/)
- [MDN PWA](https://developer.mozilla.org/en-US/docs/Web/Progressive_web_apps)
- [Web.dev Installability](https://web.dev/installable-offline/)
