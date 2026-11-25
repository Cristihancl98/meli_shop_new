/*
 * Service worker de Marketplace Manager.
 * - Las páginas del panel NUNCA se guardan en caché: tienen datos de cada tienda y sesión.
 *   Si no hay red, se muestra offline.html.
 * - Íconos y archivos de /icons, /images y /build: caché primero.
 * - Bootstrap, íconos y fuentes de CDN: se sirven desde caché y se actualizan en segundo plano.
 * Al cambiar este archivo, subir CACHE_VERSION para invalidar la caché anterior.
 */
const CACHE_VERSION = 'v1';
const STATIC_CACHE  = `mm-static-${CACHE_VERSION}`;
const CDN_CACHE     = `mm-cdn-${CACHE_VERSION}`;
const SCOPE         = self.registration.scope;
const OFFLINE_URL   = new URL('offline.html', SCOPE).href;

const PRECACHE = [
    OFFLINE_URL,
    new URL('icons/icon-192.png', SCOPE).href,
    new URL('icons/icon-512.png', SCOPE).href,
    new URL('manifest.webmanifest', SCOPE).href,
];

const CDN_HOSTS = ['cdn.jsdelivr.net', 'fonts.googleapis.com', 'fonts.gstatic.com'];
const STATIC_PATHS = ['icons/', 'images/', 'build/', 'favicon.ico'];

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(STATIC_CACHE).then((cache) => cache.addAll(PRECACHE)).then(() => self.skipWaiting()));
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(keys.filter((k) => k.startsWith('mm-') && ![STATIC_CACHE, CDN_CACHE].includes(k)).map((k) => caches.delete(k))))
            .then(() => self.clients.claim())
    );
});

self.addEventListener('fetch', (event) => {
    const request = event.request;
    if (request.method !== 'GET') return;

    const url = new URL(request.url);

    if (request.mode === 'navigate') {
        event.respondWith(fetch(request).catch(() => caches.match(OFFLINE_URL)));
        return;
    }

    if (CDN_HOSTS.includes(url.hostname)) {
        event.respondWith(staleWhileRevalidate(request));
        return;
    }

    if (url.href.startsWith(SCOPE) && STATIC_PATHS.some((path) => url.href.startsWith(SCOPE + path))) {
        event.respondWith(cacheFirst(request));
    }
});

async function cacheFirst(request) {
    const cached = await caches.match(request);
    if (cached) return cached;

    const response = await fetch(request);
    if (response.ok) {
        const cache = await caches.open(STATIC_CACHE);
        cache.put(request, response.clone());
    }
    return response;
}

async function staleWhileRevalidate(request) {
    const cache   = await caches.open(CDN_CACHE);
    const cached  = await cache.match(request);
    const network = fetch(request)
        .then((response) => {
            if (response.ok || response.type === 'opaque') cache.put(request, response.clone());
            return response;
        })
        .catch(() => cached);

    return cached || network;
}
