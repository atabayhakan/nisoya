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

// Web push (Faz M1.3). Sunucu tarafı: App\Notifications\*::toWebPush().
self.addEventListener('push', (event) => {
    if (!event.data) return;
    let payload;
    try {
        payload = event.data.json();
    } catch (e) {
        return;
    }
    event.waitUntil(
        self.registration.showNotification(payload.title || 'Nisoya', {
            body: payload.body || '',
            icon: payload.icon || '/icons/icon-192.png',
            badge: payload.badge || '/icons/icon-192.png',
            tag: payload.tag || undefined,
            data: payload.data || {},
        })
    );
});

self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const url = (event.notification.data && event.notification.data.url) || '/';
    event.waitUntil(
        clients.matchAll({ type: 'window', includeUncontrolled: true }).then((windows) => {
            // Site zaten açıksa o pencereyi öne getirip yönlendir; yoksa yeni aç.
            for (const win of windows) {
                if (new URL(win.url).origin === self.location.origin) {
                    win.navigate(url);
                    return win.focus();
                }
            }
            return clients.openWindow(url);
        })
    );
});
