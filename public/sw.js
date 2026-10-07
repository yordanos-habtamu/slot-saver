// SlotSaver Progressive Web App Service Worker
const CACHE_NAME = 'slotsaver-v1';
const PRECACHE_URLS = [
  '/',
  '/dashboard',
  '/manifest.json',
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(PRECACHE_URLS).catch(() => {});
    })
  );
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames
          .filter((name) => name !== CACHE_NAME)
          .map((name) => caches.delete(name))
      );
    })
  );
  self.clients.claim();
});

self.addEventListener('fetch', (event) => {
  const { request } = event;
  const url = new URL(request.url);

  // Network-first strategy for dynamic API calls & navigations
  if (request.method === 'GET') {
    event.respondWith(
      fetch(request)
        .then((response) => {
          if (response.status === 200) {
            const copy = response.clone();
            caches.open(CACHE_NAME).then((cache) => cache.put(request, copy));
          }
          return response;
        })
        .catch(() => {
          return caches.match(request).then((cachedResponse) => {
            if (cachedResponse) {
              return cachedResponse;
            }
            // Offline fallback for HTML navigations
            if (request.headers.get('accept')?.includes('text/html')) {
              return caches.match('/dashboard');
            }
            return new Response(JSON.stringify({ offline: true, error: 'Offline mode' }), {
              headers: { 'Content-Type': 'application/json' },
            });
          });
        })
    );
  }
});
