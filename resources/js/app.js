import Alpine from 'alpinejs';
import Webpass from '@laragear/webpass';

// Hafif scroll-reveal: eleman görünüme girdiğinde hafifçe belirir/yükselir.
// JS çalışmazsa hiçbir static class eklenmediği için içerik normal
// görünür kalır (progressive enhancement) — yeni bir kütüphane gerekmiyor,
// native IntersectionObserver kullanılıyor.
Alpine.directive('reveal', (el) => {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
        return;
    }
    el.classList.add('opacity-0', 'translate-y-4');
    const observer = new IntersectionObserver((entries) => {
        entries.forEach((entry) => {
            if (entry.isIntersecting) {
                el.classList.add('transition', 'duration-700', 'ease-out');
                el.classList.remove('opacity-0', 'translate-y-4');
                observer.unobserve(el);
            }
        });
    }, { threshold: 0.15 });
    observer.observe(el);
});

// Ana sayfadaki "Canlı Akış" şeridi: son ilanlar arasında birkaç
// saniyede bir geçiş yaparak siteye canlılık katar (prefers-reduced-motion
// saygılı — hareketi azalt tercihinde ilk öğede sabit kalır).
Alpine.data('activityTicker', (count) => ({
    index: 0,
    interval: null,
    init() {
        if (count < 2 || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            return;
        }
        this.interval = setInterval(() => {
            this.index = (this.index + 1) % count;
        }, 4500);
    },
    destroy() {
        clearInterval(this.interval);
    },
}));

// Header komut paleti (Cmd+K, Faz H2). Statik girdiler (nav linkleri, sık
// kullanılan sayfalar) sunucudan gelen küçük bir dizi üzerinde anında
// alt-dize eşleşir — ağ isteği yok. Canlı sonuçlar 300ms debounce ile
// /arama/hizli'ye gider (bkz. App\Http\Controllers\QuickSearchController).
Alpine.data('commandPalette', (staticEntries) => ({
    open: false,
    query: '',
    liveResults: [],
    loading: false,
    activeIndex: 0,
    debounceTimer: null,

    get staticMatches() {
        const q = this.query.trim().toLocaleLowerCase('tr');
        if (!q) return [];

        return staticEntries
            .filter((entry) => entry.title.toLocaleLowerCase('tr').includes(q))
            .slice(0, 6);
    },

    get results() {
        return [...this.staticMatches, ...this.liveResults];
    },

    openPalette() {
        this.open = true;
        this.activeIndex = 0;
        this.$nextTick(() => this.$refs.input?.focus());
    },

    closePalette() {
        this.open = false;
        this.query = '';
        this.liveResults = [];
        this.activeIndex = 0;
        clearTimeout(this.debounceTimer);
    },

    onInput() {
        this.activeIndex = 0;
        clearTimeout(this.debounceTimer);
        const q = this.query.trim();
        if (q.length < 2) {
            this.liveResults = [];
            return;
        }
        this.debounceTimer = setTimeout(() => this.fetchResults(q), 300);
    },

    async fetchResults(q) {
        this.loading = true;
        try {
            const response = await fetch(`/arama/hizli?q=${encodeURIComponent(q)}`, {
                headers: { Accept: 'application/json' },
            });
            const data = await response.json();
            if (this.query.trim() === q) {
                this.liveResults = data.results ?? [];
            }
        } catch (e) {
            this.liveResults = [];
        } finally {
            this.loading = false;
        }
    },

    move(delta) {
        if (!this.results.length) return;
        this.activeIndex = (this.activeIndex + delta + this.results.length) % this.results.length;
    },

    choose(index = this.activeIndex) {
        const item = this.results[index];
        if (!item) return;
        this.closePalette();
        if (item.action === 'toggleTheme') {
            window.toggleTheme && window.toggleTheme();
            return;
        }
        window.location.href = item.url;
    },

    // Minimal odak tuzağı: yeni bir bağımlılık eklemeden Tab'ı panel
    // içindeki ilk/son odaklanabilir elemanlar arasında döndürür.
    trapFocus(event) {
        const focusables = this.$refs.panel.querySelectorAll('input, a[href], button:not([disabled])');
        if (!focusables.length) return;

        const first = focusables[0];
        const last = focusables[focusables.length - 1];

        if (event.shiftKey && document.activeElement === first) {
            event.preventDefault();
            last.focus();
        } else if (!event.shiftKey && document.activeElement === last) {
            event.preventDefault();
            first.focus();
        }
    },
}));

// Header Faz H4: aşağı kaydırınca hafifçe küçülür (gölge + daha az padding),
// belli bir eşiği (160px) geçip aşağı kaydırırken tamamen gizlenir, yukarı
// kaydırınca hemen geri açılır — native-app hissi. prefers-reduced-motion'da
// gizleme devre dışı kalır (yalnızca gölge/padding geçişi kalır, ani hareket yok).
Alpine.data('headerScroll', () => ({
    scrolled: false,
    hidden: false,
    lastY: 0,

    onScroll() {
        const y = window.scrollY;
        this.scrolled = y > 10;

        const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        if (!reduceMotion && y > 160 && y > this.lastY) {
            this.hidden = true;
        } else if (y < this.lastY || y <= 10) {
            this.hidden = false;
        }
        this.lastY = y;
    },
}));

// Nabız Haritası (Faz İ2, "2. Tasarım" pilotu). Süs değil veri: her nokta
// gerçek bir ülkenin enlem/boylamından gelen konumda, boyutu o ülkedeki
// aktif ilan sayısıyla orantılı (bkz. App\Services\NabizService::countryActivity).
// prefers-reduced-motion'da nabız animasyonu durur, noktalar sabit kalır.
Alpine.data('pulseMap', (countries) => ({
    init() {
        const canvas = this.$refs.canvas;
        const ctx = canvas.getContext('2d');
        const reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;
        const maxCount = Math.max(1, ...countries.map((c) => c.count));
        const seeds = countries.map(() => Math.random() * 3000);
        const start = performance.now();

        const resize = () => {
            const rect = canvas.parentElement.getBoundingClientRect();
            const dpr = Math.min(window.devicePixelRatio || 1, 2);
            canvas.width = rect.width * dpr;
            canvas.height = rect.height * dpr;
            ctx.setTransform(dpr, 0, 0, dpr, 0, 0);
        };
        resize();
        window.addEventListener('resize', resize);

        const colors = () => {
            const s = getComputedStyle(document.documentElement);
            return {
                dot: s.getPropertyValue('--color-emerald-600').trim() || '#0f5c42',
                pulse: s.getPropertyValue('--nisoya-seal').trim() || '#c1440e',
                ink: s.getPropertyValue('--color-stone-500').trim() || '#78716c',
            };
        };

        const frame = (t) => {
            const rect = canvas.parentElement.getBoundingClientRect();
            const w = rect.width;
            const h = rect.height;
            const c = colors();
            ctx.clearRect(0, 0, w, h);

            countries.forEach((country, i) => {
                const px = country.x * w;
                const py = country.y * h;
                const weight = country.count / maxCount;
                const radius = 2.5 + weight * 4.5;
                const phase = reduceMotion ? 0 : ((t - start + seeds[i]) % 2800) / 2800;
                const pulse = reduceMotion ? 0.4 : Math.sin(phase * Math.PI * 2) * 0.5 + 0.5;

                if (!reduceMotion && country.count > 0) {
                    ctx.beginPath();
                    ctx.arc(px, py, radius + pulse * 12, 0, Math.PI * 2);
                    ctx.strokeStyle = c.pulse;
                    ctx.globalAlpha = (1 - pulse) * 0.4;
                    ctx.lineWidth = 1.5;
                    ctx.stroke();
                    ctx.globalAlpha = 1;
                }

                ctx.beginPath();
                ctx.arc(px, py, radius, 0, Math.PI * 2);
                ctx.fillStyle = country.count > 0 ? c.dot : c.ink;
                ctx.globalAlpha = country.count > 0 ? 0.9 : 0.35;
                ctx.fill();
                ctx.globalAlpha = 1;
            });

            if (!reduceMotion) {
                this.frameId = requestAnimationFrame(frame);
            }
        };
        this.frameId = requestAnimationFrame(frame);

        this.$el.addEventListener('alpine:destroy', () => {
            cancelAnimationFrame(this.frameId);
            window.removeEventListener('resize', resize);
        });
    },
}));

// Web push aboneliği (Faz M1.3). Bildirim izni SADECE kullanıcı düğmeye
// basınca istenir (sayfa açılışında asla — tarayıcılar bunu cezalandırıyor,
// marka tonuna da aykırı). vapidKey Blade'den config('webpush.vapid.public_key')
// ile gelir; uçlar: routes/web.php → push.subscribe / push.unsubscribe.
Alpine.data('pushToggle', (vapidKey, subscribeUrl, unsubscribeUrl) => ({
    supported: false,
    subscribed: false,
    busy: false,
    denied: false,

    async init() {
        this.supported = !!(vapidKey && 'serviceWorker' in navigator && 'PushManager' in window && 'Notification' in window);
        if (!this.supported) return;
        this.denied = Notification.permission === 'denied';
        const reg = await navigator.serviceWorker.ready;
        this.subscribed = !!(await reg.pushManager.getSubscription());
    },

    async toggle() {
        if (this.busy || this.denied) return;
        this.busy = true;
        try {
            this.subscribed ? await this.unsubscribe() : await this.subscribe();
        } catch (e) {
            this.denied = Notification.permission === 'denied';
        } finally {
            this.busy = false;
        }
    },

    async subscribe() {
        const permission = await Notification.requestPermission();
        if (permission !== 'granted') {
            this.denied = permission === 'denied';
            return;
        }
        const reg = await navigator.serviceWorker.ready;
        const sub = await reg.pushManager.subscribe({
            userVisibleOnly: true,
            applicationServerKey: urlBase64ToUint8Array(vapidKey),
        });
        const json = sub.toJSON();
        await postJson(subscribeUrl, 'POST', {
            endpoint: json.endpoint,
            keys: json.keys,
            content_encoding: (PushManager.supportedContentEncodings || ['aesgcm'])[0],
        });
        this.subscribed = true;
    },

    async unsubscribe() {
        const reg = await navigator.serviceWorker.ready;
        const sub = await reg.pushManager.getSubscription();
        if (sub) {
            await postJson(unsubscribeUrl, 'DELETE', { endpoint: sub.endpoint });
            await sub.unsubscribe();
        }
        this.subscribed = false;
    },
}));

function postJson(url, method, body) {
    return fetch(url, {
        method,
        headers: {
            'Content-Type': 'application/json',
            Accept: 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content ?? '',
        },
        body: JSON.stringify(body),
    });
}

// PushManager.subscribe VAPID anahtarını Uint8Array ister; anahtar
// URL-safe base64 string olarak gelir.
function urlBase64ToUint8Array(base64String) {
    const padding = '='.repeat((4 - (base64String.length % 4)) % 4);
    const base64 = (base64String + padding).replace(/-/g, '+').replace(/_/g, '/');
    const raw = window.atob(base64);
    return Uint8Array.from([...raw].map((c) => c.charCodeAt(0)));
}

// Passkey ile giriş (Faz M2). Giriş sayfasındaki düğme — e-posta alanı
// doluysa o hesabın passkey'leri, boşsa cihazın hatırladığı (discoverable)
// passkey kullanılır. Sunucu: App\Http\Controllers\WebAuthn\WebAuthnLoginController.
Alpine.data('passkeyLogin', (optionsUrl, loginUrl) => ({
    supported: Webpass.isSupported(),
    busy: false,
    error: null,

    async login() {
        if (this.busy) return;
        this.busy = true;
        this.error = null;

        const email = document.getElementById('email')?.value.trim();
        const { data, success } = await Webpass.assert(
            { path: optionsUrl, body: email ? { email } : {} },
            loginUrl,
        );

        this.busy = false;
        if (success && data?.redirect) {
            window.location.href = data.redirect;
            return;
        }
        this.error = 'Passkey ile giriş yapılamadı. Şifrenle giriş yapabilir veya tekrar deneyebilirsin.';
    },
}));

// Passkey ekleme (Faz M2, 2FA/güvenlik sayfası). Alias'ı query string ile
// taşıyoruz — sunucu tarafındaki gerekçe için bkz. WebAuthnRegisterController.
Alpine.data('passkeyManage', (optionsUrl, registerUrl) => ({
    supported: Webpass.isSupported(),
    busy: false,
    error: null,
    alias: '',

    async add() {
        if (this.busy) return;
        this.busy = true;
        this.error = null;

        const url = this.alias.trim()
            ? `${registerUrl}?alias=${encodeURIComponent(this.alias.trim())}`
            : registerUrl;
        const { success } = await Webpass.attest(optionsUrl, url);

        this.busy = false;
        if (success) {
            window.location.reload();
            return;
        }
        this.error = 'Passkey eklenemedi. Cihazın desteklemiyor olabilir veya işlem iptal edildi.';
    },
}));

// PWA yükleme ipucu (Faz M1.4). Chrome/Android'de beforeinstallprompt
// yakalanır; iOS'ta bu event yok, Safari tespitiyle "Ana Ekrana Ekle"
// talimatı gösterilir (bkz. mobile-tab-bar.blade.php Keşfet sayfası).
const DISMISS_KEY = 'nisoya-install-dismissed';
Alpine.store('pwa', {
    installEvent: null,
    isIos: /iphone|ipad|ipod/i.test(navigator.userAgent) && !window.MSStream,
    isStandalone: window.matchMedia('(display-mode: standalone)').matches || window.navigator.standalone === true,
    dismissedAt: Number(localStorage.getItem(DISMISS_KEY) || 0),

    get visible() {
        if (this.isStandalone) return false;
        if (Date.now() - this.dismissedAt < 30 * 24 * 60 * 60 * 1000) return false;
        return !!this.installEvent || this.isIos;
    },

    async install() {
        if (!this.installEvent) return;
        this.installEvent.prompt();
        const { outcome } = await this.installEvent.userChoice;
        if (outcome !== 'accepted') this.dismiss();
        this.installEvent = null;
    },

    dismiss() {
        this.dismissedAt = Date.now();
        localStorage.setItem(DISMISS_KEY, String(this.dismissedAt));
    },
});

window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    Alpine.store('pwa').installEvent = e;
});
window.addEventListener('appinstalled', () => {
    Alpine.store('pwa').installEvent = null;
    Alpine.store('pwa').isStandalone = true;
});

window.Alpine = Alpine;
Alpine.start();
