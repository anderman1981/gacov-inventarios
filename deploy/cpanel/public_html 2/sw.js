/**
 * GACOV Inventarios - Service Worker
 * Versión: 1.0.0
 */

const CACHE_NAME = 'gacov-v3';
const STATIC_ASSETS = [
  '/manifest.json',
  '/icons/icon-192.png',
  '/icons/icon-512.png',
];

const CACHEABLE_DESTINATIONS = new Set([
  'script',
  'style',
  'image',
  'font',
  'manifest',
]);

// Install event - cache static assets
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      console.log('[SW] Caching static assets');
      return cache.addAll(STATIC_ASSETS);
    })
  );
  self.skipWaiting();
});

// Activate event - clean old caches
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames
          .filter((name) => name !== CACHE_NAME)
          .map((name) => {
            console.log('[SW] Deleting old cache:', name);
            return caches.delete(name);
          })
      );
    })
  );
  self.clients.claim();
});

// Fetch event - serve from cache, fallback to network
self.addEventListener('fetch', (event) => {
  // Skip non-GET requests
  if (event.request.method !== 'GET') return;

  // Skip cross-origin requests
  if (!event.request.url.startsWith(self.location.origin)) return;

  // Let the browser handle HTML navigations and redirects natively.
  if (event.request.mode === 'navigate') return;

  // Some browser internal requests use redirect modes the SW should not override.
  if (event.request.redirect && event.request.redirect !== 'follow') return;

  const requestUrl = new URL(event.request.url);
  const acceptHeader = event.request.headers.get('accept') || '';
  const isHtmlRequest = acceptHeader.includes('text/html') || event.request.destination === 'document';

  if (isHtmlRequest) return;

  // Skip API, Livewire, and authentication routes
  if (requestUrl.pathname === '/' ||
      event.request.url.includes('/api/') || 
      event.request.url.includes('/livewire/') ||
      event.request.url.includes('/logout') ||
      event.request.url.includes('/login') ||
      event.request.url.includes('/register') ||
      event.request.url.includes('/password/')) {
    return;
  }

  event.respondWith(
    caches.match(event.request).then((cachedResponse) => {
      if (cachedResponse) {
        return cachedResponse;
      }

      return fetch(event.request).then((response) => {
        // Never cache redirects or followed redirects from authenticated routes.
        if (response.redirected || response.type === 'opaqueredirect') {
          return response;
        }

        // Don't cache non-successful responses
        if (!response || response.status !== 200) {
          return response;
        }

        // Only cache static-like assets. Dynamic HTML/app responses stay out of the SW cache.
        if (!CACHEABLE_DESTINATIONS.has(event.request.destination) && !requestUrl.pathname.startsWith('/build/')) {
          return response;
        }

        // Clone the response
        const responseToCache = response.clone();

        caches.open(CACHE_NAME).then((cache) => {
          cache.put(event.request, responseToCache);
        });

        return response;
      }).catch(() => {
        return Response.error();
      });
    })
  );
});

// Handle push notifications (for future use)
self.addEventListener('push', (event) => {
  if (!event.data) return;

  const data = event.data.json();
  const options = {
    body: data.body || 'Nueva notificación',
    icon: '/icons/icon-192.png',
    badge: '/icons/icon-72.png',
    vibrate: [100, 50, 100],
    data: {
      url: data.url || '/',
    },
  };

  event.waitUntil(
    self.registration.showNotification(data.title || 'GACOV', options)
  );
});

// Handle notification click
self.addEventListener('notificationclick', (event) => {
  event.notification.close();
  event.waitUntil(
    clients.openWindow(event.notification.data.url)
  );
});
