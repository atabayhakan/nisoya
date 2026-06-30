// Nisoya service worker — offline fallback + statik önbellek
const CACHE = 'nisoya-v1';
const OFFLINE_URL = '/offline';

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(CACHE).then((c) => c.add(OFFLINE_URL)));
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) =>
            Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k)))
        )
    );
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    const req = event.request;
    if (req.method !== 'GET') return;

    // Sayfa gezinmesi: ağ önce, başarısızsa offline sayfası
    if (req.mode === 'navigate') {
        event.respondWith(fetch(req).catch(() => caches.match(OFFLINE_URL)));
        return;
    }

    // Statik varlıklar: cache-first
    const path = new URL(req.url).pathname;
    if (/\.(?:css|js|woff2?|png|jpe?g|webp|svg|ico)$/.test(path)) {
        event.respondWith(
            caches.match(req).then((cached) =>
                cached ||
                fetch(req).then((res) => {
                    const copy = res.clone();
                    caches.open(CACHE).then((c) => c.put(req, copy));
                    return res;
                }).catch(() => cached)
            )
        );
    }
});
