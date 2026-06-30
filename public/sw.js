// Nisoya service worker — minimal & güvenli.
// Sadece sayfa gezinmesinde offline yedeği sunar; HİÇBİR varlığı (js/css) önbelleğe ALMAZ.
// Böylece bayat önbellek / dinamik dosya sorunları olmaz.
const CACHE = 'nisoya-v3';
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
    // Yalnızca sayfa gezinmesi; varlıklara ve diğer isteklere dokunma.
    if (req.method !== 'GET' || req.mode !== 'navigate') return;
    event.respondWith(fetch(req).catch(() => caches.match(OFFLINE_URL)));
});
