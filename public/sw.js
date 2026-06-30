// Nisoya service worker — offline fallback + statik önbellek
const CACHE = 'nisoya-v2';
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

    const url = new URL(req.url);

    // Yalnızca kendi origin'imiz (chrome-extension, harici CDN vb. dokunma)
    if (url.origin !== self.location.origin) return;

    // Dinamik/admin yollarına dokunma (Livewire, yönetim, üye paneli)
    if (/^\/(yonetim|livewire|panel)/.test(url.pathname) || url.pathname.startsWith('/livewire-')) {
        return;
    }

    // Sayfa gezinmesi: ağ önce, başarısızsa offline sayfası
    if (req.mode === 'navigate') {
        event.respondWith(fetch(req).catch(() => caches.match(OFFLINE_URL)));
        return;
    }

    // Statik varlıklar: cache-first; yalnızca başarılı yanıtları önbelleğe al
    if (/\.(?:css|js|woff2?|png|jpe?g|webp|svg|ico)$/.test(url.pathname)) {
        event.respondWith(
            caches.match(req).then((cached) =>
                cached ||
                fetch(req).then((res) => {
                    if (res.ok) {
                        const copy = res.clone();
                        caches.open(CACHE).then((c) => c.put(req, copy));
                    }
                    return res;
                }).catch(() => cached)
            )
        );
    }
});
