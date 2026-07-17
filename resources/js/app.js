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

// Profil/ürün fotoğrafı odak noktası: parmak (touch) ya da fare (mouse) ile
// sürükleyerek hizalama (bkz. panel/profile/edit.blade.php + panel/listings/edit.blade.php).
// Pointer Events tek bir API ile hem dokunmatik hem fareyi kapsar — ayrı
// touchstart/mousedown dinleyicisi gerekmez. Sürükleme, DELTA tabanlı: fotoğrafı
// gerçekten "kaydırıyormuş" hissi versin diye, parmak sağa gittikçe object-position
// yüzdesi AZALIR (görselin sol kısmı ortaya çıkar) — bu yüzden işaret ters (-).
// move/up dinleyicileri `window`'a bağlanır (sadece frame'e değil): kullanıcı
// küçük daireyi sürüklerken parmak/imleç doğal olarak dairenin dışına taşıyor,
// window'a bağlamak imlecin nerede olduğundan bağımsız çalışmasını garantiler.
Alpine.data('focalDrag', (initialX, initialY, saveUrl) => ({
    x: initialX,
    y: initialY,
    dragging: false,
    saved: false,
    startClientX: 0,
    startClientY: 0,
    startX: 0,
    startY: 0,
    frameWidth: 0,
    frameHeight: 0,
    _boundMove: null,
    _boundEnd: null,

    get objectPosition() {
        return this.x + '% ' + this.y + '%';
    },

    startDrag(e) {
        // Gerçek (trusted) pointerdown'da tarayıcının kendi resim sürükleme
        // hazırlığı (native dragstart) ya da iOS callout'u bu gesture'ı
        // devralıp pointermove'un hiç gelmemesine yol açabiliyor —
        // preventDefault() bunu tetiklenmeden önce keser. Synthetic
        // dispatchEvent() testleri native gesture recognizer'ları hiç
        // tetiklemediği için bu sorun testlerde görünmez kalıyordu
        // (bkz. app.css .focal-drag-frame). setPointerCapture, dokunmatikte
        // parmak dairenin dışına taşsa bile bu pointer'ın olaylarının bu
        // elemente yönlendirilmesini garanti eder; desteklenmiyorsa/
        // başarısız olursa sessizce yok sayılır — window'daki move/up
        // dinleyicileri zaten çalışmaya devam eder.
        e.preventDefault();
        try {
            e.currentTarget.setPointerCapture(e.pointerId);
        } catch (_) {
            // Pointer capture opsiyonel bir sağlamlaştırma — desteklenmiyorsa
            // sürükleme window dinleyicileriyle yine de çalışır.
        }

        const rect = this.$refs.frame.getBoundingClientRect();
        this.frameWidth = rect.width;
        this.frameHeight = rect.height;
        this.startClientX = e.clientX;
        this.startClientY = e.clientY;
        this.startX = this.x;
        this.startY = this.y;
        this.dragging = true;
        this.saved = false;

        this._boundMove = (ev) => this.onDrag(ev);
        this._boundEnd = () => this.endDrag();
        window.addEventListener('pointermove', this._boundMove);
        window.addEventListener('pointerup', this._boundEnd);
        window.addEventListener('pointercancel', this._boundEnd);
    },

    onDrag(e) {
        if (!this.dragging) return;
        const dx = e.clientX - this.startClientX;
        const dy = e.clientY - this.startClientY;
        this.x = clampPercent(this.startX - (dx / this.frameWidth) * 100);
        this.y = clampPercent(this.startY - (dy / this.frameHeight) * 100);
    },

    async endDrag() {
        if (!this.dragging) return;
        this.dragging = false;
        window.removeEventListener('pointermove', this._boundMove);
        window.removeEventListener('pointerup', this._boundEnd);
        window.removeEventListener('pointercancel', this._boundEnd);
        await postJson(saveUrl, 'PATCH', { focal_x: Math.round(this.x), focal_y: Math.round(this.y) });
        this.saved = true;
        setTimeout(() => { this.saved = false; }, 1500);
    },
}));

// Profil fotoğrafı hizalama modalı (kaydır + yakınlaştır). Fotoğraf,
// çerçeveden BÜYÜK gösterilir (WhatsApp/Instagram avatar editörü deseni):
// durum = {panX, panY, zoom}; görsel `translate(panX, panY)` + piksel
// genişlikle çizilir, "İptal" son kaydedilen duruma döner. "Kaydet"
// pan/zum'u ORİJİNAL görselin piksel koordinatlarında kare bir kırpım
// dikdörtgenine çevirip sunucuya gönderir; sunucu kare dosyayı üretir
// (bkz. ProfileSettingsController::alignAvatar) ve gösterim her yerde o
// dosyayı kullanır — transform matematiği başka hiçbir yerde tekrarlanmaz.
// zoom=1 "cover" ölçeğidir (çerçeveyi tam doldurur), üst sınır 3x.
// Dokunmatikte iki parmakla sıkıştırarak, masaüstünde tekerlek ya da
// kaydırıcı ile yakınlaştırılır.
Alpine.data('avatarCropModal', (crop, saveUrl) => ({
    open: false,
    ready: false,
    saving: false,
    panX: 0,
    panY: 0,
    zoom: 1,
    natW: 0,
    natH: 0,
    frameW: 0,
    frameH: 0,
    previewUrl: null,
    // Aktif pointer'lar (pointerId -> {x, y}); 1 pointer = kaydırma,
    // 2 pointer = sıkıştırarak yakınlaştırma.
    _pointers: null,
    _pinchDist: 0,
    _boundMove: null,
    _boundEnd: null,
    // Çerçeve/fotoğraf elemanları x-init ile buraya kaydedilir: $refs,
    // teleport DIŞINDAN (openModal'ı tetikleyen buton) teleport İÇİNDEKİ
    // ref'lere erişemiyor (bilinen Alpine x-teleport sınırlaması).
    _frameEl: null,
    _photoEl: null,

    registerFrame(el) {
        this._frameEl = el;
    },

    registerPhoto(el) {
        this._photoEl = el;
    },

    // Çerçeveyi tam dolduran minimum ölçek ("cover").
    get coverScale() {
        if (!this.natW || !this.natH) return 1;
        return Math.max(this.frameW / this.natW, this.frameH / this.natH);
    },

    get scale() {
        return this.coverScale * this.zoom;
    },

    get imgStyle() {
        return {
            width: this.natW * this.scale + 'px',
            height: this.natH * this.scale + 'px',
            transform: 'translate(' + this.panX + 'px, ' + this.panY + 'px)',
            maxWidth: 'none',
        };
    },

    async openModal() {
        this.open = true;
        await this.$nextTick();

        const img = this._photoEl;
        if (!img || !this._frameEl) return;
        // complete=true resmin YÜKLENDİĞİ değil, yüklemenin BİTTİĞİ anlamına
        // gelir (başarısız olsa da) — bitmediyse bekle, bittiyse bekleme
        // (aksi halde hatalı resimde sonsuza dek beklenirdi).
        if (!img.complete) {
            await new Promise((resolve) => {
                img.addEventListener('load', resolve, { once: true });
                img.addEventListener('error', resolve, { once: true });
            });
        }
        this.natW = img.naturalWidth;
        this.natH = img.naturalHeight;

        const rect = this._frameEl.getBoundingClientRect();
        this.frameW = rect.width;
        this.frameH = rect.height;

        this.restoreState();
        this.ready = this.natW > 0 && this.frameW > 0;
    },

    // Kaydedilmiş kırpım karesinden pan/zum durumunu geri kur; yoksa
    // ortalanmış zoom=1 ile başla.
    restoreState() {
        if (crop.size && crop.size > 0 && this.natW > 0) {
            const s = this.frameW / crop.size;
            this.zoom = Math.max(1, Math.min(3, s / this.coverScale));
            this.panX = -crop.x * this.scale;
            this.panY = -crop.y * this.scale;
        } else {
            this.zoom = 1;
            this.panX = (this.frameW - this.natW * this.scale) / 2;
            this.panY = (this.frameH - this.natH * this.scale) / 2;
        }
        this.clampPan();
    },

    cancel() {
        if (this._pointers && this._pointers.size) return;
        this.open = false;
        this.ready = false;
    },

    // Görsel kenarları çerçevenin içine giremez.
    clampPan() {
        const imgW = this.natW * this.scale;
        const imgH = this.natH * this.scale;
        this.panX = Math.min(0, Math.max(this.frameW - imgW, this.panX));
        this.panY = Math.min(0, Math.max(this.frameH - imgH, this.panY));
    },

    // (px, py) çerçeve-noktası sabit kalacak şekilde yakınlaştır.
    zoomAt(px, py, newZoom) {
        const clamped = Math.max(1, Math.min(3, newZoom));
        const imgPointX = (px - this.panX) / this.scale;
        const imgPointY = (py - this.panY) / this.scale;
        this.zoom = clamped;
        this.panX = px - imgPointX * this.scale;
        this.panY = py - imgPointY * this.scale;
        this.clampPan();
    },

    onWheel(e) {
        if (!this.ready) return;
        const rect = this._frameEl.getBoundingClientRect();
        const factor = e.deltaY < 0 ? 1.08 : 1 / 1.08;
        this.zoomAt(e.clientX - rect.left, e.clientY - rect.top, this.zoom * factor);
    },

    onSlider(value) {
        if (!this.ready) return;
        this.zoomAt(this.frameW / 2, this.frameH / 2, Number(value));
    },

    startDrag(e) {
        if (!this.ready) return;
        // Native resim sürükleme/callout hijack'ini kes (bkz. focalDrag) —
        // synthetic testlerde görünmeyen, yalnızca gerçek girdide tetiklenen
        // tarayıcı davranışı.
        e.preventDefault();
        try {
            e.currentTarget.setPointerCapture(e.pointerId);
        } catch (_) {
            // Opsiyonel sağlamlaştırma — window dinleyicileri yine de çalışır.
        }

        if (!this._pointers) this._pointers = new Map();
        this._pointers.set(e.pointerId, { x: e.clientX, y: e.clientY });

        if (this._pointers.size === 2) {
            const [a, b] = [...this._pointers.values()];
            this._pinchDist = Math.hypot(a.x - b.x, a.y - b.y);
        }

        if (this._pointers.size === 1) {
            this._boundMove = (ev) => this.onMove(ev);
            this._boundEnd = (ev) => this.endDrag(ev);
            window.addEventListener('pointermove', this._boundMove);
            window.addEventListener('pointerup', this._boundEnd);
            window.addEventListener('pointercancel', this._boundEnd);
        }
    },

    onMove(e) {
        if (!this._pointers || !this._pointers.has(e.pointerId)) return;
        const prev = this._pointers.get(e.pointerId);
        const dx = e.clientX - prev.x;
        const dy = e.clientY - prev.y;
        this._pointers.set(e.pointerId, { x: e.clientX, y: e.clientY });

        if (this._pointers.size >= 2) {
            // İki parmak: sıkıştırarak yakınlaştır (orta nokta sabit kalır)
            // + orta nokta kayarsa görüntüyü de kaydır.
            const [a, b] = [...this._pointers.values()];
            const dist = Math.hypot(a.x - b.x, a.y - b.y);
            const rect = this._frameEl.getBoundingClientRect();
            const midX = (a.x + b.x) / 2 - rect.left;
            const midY = (a.y + b.y) / 2 - rect.top;
            if (this._pinchDist > 0 && dist > 0) {
                this.zoomAt(midX, midY, this.zoom * (dist / this._pinchDist));
            }
            this._pinchDist = dist;
            this.panX += dx / 2;
            this.panY += dy / 2;
            this.clampPan();
        } else {
            // Tek parmak/fare: fotoğrafı kaydır — parmak nereye, fotoğraf oraya.
            this.panX += dx;
            this.panY += dy;
            this.clampPan();
        }
    },

    endDrag(e) {
        if (!this._pointers) return;
        this._pointers.delete(e.pointerId);

        if (this._pointers.size === 1) {
            this._pinchDist = 0;
        }
        if (this._pointers.size === 0) {
            window.removeEventListener('pointermove', this._boundMove);
            window.removeEventListener('pointerup', this._boundEnd);
            window.removeEventListener('pointercancel', this._boundEnd);
        }
    },

    // Pan/zum durumunu kaynak görselin piksel koordinatlarında kare kırpıma çevir.
    cropRect() {
        const size = Math.round(this.frameW / this.scale);
        const x = Math.round(-this.panX / this.scale);
        const y = Math.round(-this.panY / this.scale);
        return {
            crop_size: Math.max(16, Math.min(size, Math.min(this.natW, this.natH))),
            crop_x: Math.max(0, Math.min(x, this.natW - size)),
            crop_y: Math.max(0, Math.min(y, this.natH - size)),
        };
    },

    async save() {
        if (this.saving || !this.ready) return;
        this.saving = true;
        try {
            const response = await postJson(saveUrl, 'PATCH', this.cropRect());
            if (response.ok) {
                const data = await response.json();
                if (data.cropped_url) {
                    this.previewUrl = data.cropped_url + '?t=' + Date.now();
                }
                const rect = this.cropRect();
                crop.x = rect.crop_x;
                crop.y = rect.crop_y;
                crop.size = rect.crop_size;
                this.open = false;
                this.ready = false;
            }
        } finally {
            this.saving = false;
        }
    },
}));

function clampPercent(value) {
    return Math.max(0, Math.min(100, Math.round(value)));
}

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
// webpass'in attest()/assert() döndürdüğü {error} nesnesini insan-okur bir
// mesaja çevirir. En sık görülen gerçek neden: sayfa bir e-posta/uygulama içi
// tarayıcıda (Outlook, Gmail, Instagram vb. WKWebView) açılmış — bu tür
// gömülü tarayıcılar Face ID/Touch ID ceremonisine izin vermiyor ve
// navigator.credentials.create()/get() sunucuya hiç ulaşmadan NotAllowedError
// ile reddediyor (bkz. webpass kaynağı: DOMException .cause üzerinde saklanır).
function passkeyErrorMessage(error) {
    const domName = error?.cause?.name || error?.name;
    const inAppHint = 'Bu bağlantıyı bir e-posta/uygulama içi tarayıcıda (ör. Outlook, Gmail) açtıysan Face ID/parmak izi çalışmaz — Safari veya Chrome\'da doğrudan aç, ya da Nisoya\'yı ana ekranına ekleyip oradan kullan.';

    if (domName === 'NotAllowedError') {
        return 'İşlem tamamlanamadı veya izin verilmedi. ' + inAppHint;
    }
    if (domName === 'SecurityError') {
        return 'Bu sayfa adresi passkey için güvenli kabul edilmedi. nisoya.com adresinden doğrudan eriştiğinden emin ol.';
    }
    if (domName === 'AbortError') {
        return 'İşlem zaman aşımına uğradı veya iptal edildi. Tekrar dene.';
    }
    return 'Passkey işlemi tamamlanamadı. Cihazın desteklemiyor olabilir. ' + inAppHint;
}

Alpine.data('passkeyLogin', (optionsUrl, loginUrl) => ({
    supported: Webpass.isSupported(),
    busy: false,
    error: null,

    async login() {
        if (this.busy) return;
        this.busy = true;
        this.error = null;

        const email = document.getElementById('email')?.value.trim();
        const { data, success, error } = await Webpass.assert(
            { path: optionsUrl, body: email ? { email } : {} },
            loginUrl,
        );

        this.busy = false;
        if (success && data?.redirect) {
            window.location.href = data.redirect;
            return;
        }
        this.error = passkeyErrorMessage(error);
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
        const { success, error } = await Webpass.attest(optionsUrl, url);

        this.busy = false;
        if (success) {
            window.location.reload();
            return;
        }
        this.error = passkeyErrorMessage(error);
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
