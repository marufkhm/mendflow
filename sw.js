const CACHE_NAME = 'pwe-static-v2';
const STATIC_ASSETS = [
  '/',
  '/index.html',
  '/style.css',
  '/script.js',
  '/manifest.json',
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => cache.addAll(STATIC_ASSETS))
  );
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) => Promise.all(
      keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key))
    ))
  );
  self.clients.claim();
});

self.addEventListener('fetch', (event) => {
  const { request } = event;
  if (request.method !== 'GET') {
    return;
  }

  const url = new URL(request.url);
  const isStaticAsset = url.origin === self.location.origin
    && (STATIC_ASSETS.includes(url.pathname) || url.pathname.endsWith('.css') || url.pathname.endsWith('.js'));

  if (!isStaticAsset) {
    return;
  }

  const isDocument = request.mode === 'navigate' || request.destination === 'document';

  event.respondWith(
    isDocument
      ? fetch(request)
        .then((response) => {
          const cloned = response.clone();
          caches.open(CACHE_NAME).then((cache) => cache.put(request, cloned));
          return response;
        })
        .catch(() => caches.match(request).then((cached) => cached || caches.match('/index.html')))
      : caches.match(request).then((cached) => {
        const networkFetch = fetch(request).then((response) => {
          const cloned = response.clone();
          caches.open(CACHE_NAME).then((cache) => cache.put(request, cloned));
          return response;
        });

        return cached || networkFetch;
      })
  );
});
