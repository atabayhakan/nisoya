import Alpine from 'alpinejs';
import { Passkeys, UserCancelledError, InvalidDomainError, PasskeyExistsError, NotSupportedError } from '@laravel/passkeys';

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

/**
 * Mobil alt sayfa (bottom sheet) — Keşfet, ülke seçici, hesap menüsü.
 *
 * -------------------------------------------------------------------------
 * NEDEN ORTAK
 *
 * Üç alt sayfa aynı deseni elle tekrarlıyordu ve AYNI İKİ KUSURU taşıyordu
 * (sahibin 2026-08-05 bildirimi: "Keşfet'i açtığımda tekrar kapatamıyorum,
 * arka plan hareket ediyor"):
 *
 *   1. KAPATMA DÜĞMESİ YOKTU. Tek yol arka plana dokunmaktı; Keşfet paneli
 *      ekranın %80'ini kapladığı için geriye ince bir şerit kalıyordu.
 *      Escape ise telefonda yok. Kapanmayan bir örtü, sayfayı kilitli
 *      hissettirir.
 *
 *   2. GÖVDE KAYDIRMA KİLİDİ YOKTU. Sayfa 5556px; örtü açıkken arkadaki
 *      içerik kayıyordu. iOS'ta bu yalnız çirkin değil, sabit konumlu örtüyü
 *      görünür alanın dışına taşıyıp gerçekten kapatılamaz hâle getirebilir.
 *
 * Kaydırma kilidi `position: fixed` + `top: -scrollY` ile yapılıyor:
 * `overflow: hidden` iOS Safari'de tek başına YETMEZ, sayfa yine kayar.
 * Kapanışta kaydırma konumu birebir geri verilir — aksi hâlde kullanıcı
 * sayfanın başına fırlar ve nerede kaldığını kaybeder.
 *
 * Aynı anda birden fazla örtü açılabilir (hesap sayfasından ülke seçiciye);
 * kilit SAYAÇLA yönetilir, ilki kapanınca kilit erken kalkmasın.
 */
let acikOrtuSayisi = 0;
let kilitliScrollY = 0;

function govdeyiKilitle() {
    acikOrtuSayisi += 1;
    if (acikOrtuSayisi > 1) return;

    kilitliScrollY = window.scrollY;

    // Kaydırma çubuğu genişliği telafi edilir: gövde sabitlenince çubuk
    // kaybolur ve sayfa masaüstünde birkaç piksel YANA SIÇRAR. Telefonda
    // çubuk katman üstü olduğu için fark 0'dır, yani kod tek yolda kalır.
    const cubukGenisligi = window.innerWidth - document.documentElement.clientWidth;

    document.body.style.position = 'fixed';
    document.body.style.top = `-${kilitliScrollY}px`;
    document.body.style.left = '0';
    document.body.style.right = '0';
    document.body.style.width = '100%';
    if (cubukGenisligi > 0) {
        document.body.style.paddingRight = `${cubukGenisligi}px`;
    }
}

function govdeKilidiniAc() {
    acikOrtuSayisi = Math.max(0, acikOrtuSayisi - 1);
    if (acikOrtuSayisi > 0) return;

    document.body.style.position = '';
    document.body.style.top = '';
    document.body.style.left = '';
    document.body.style.right = '';
    document.body.style.width = '';
    document.body.style.paddingRight = '';

    // Konum GERİ VERİLİR. Verilmezse kullanıcı sayfanın en başına fırlar ve
    // nerede kaldığını kaybeder — uzun ana sayfada bu, örtüyü açmanın
    // cezası hâline gelir.
    window.scrollTo(0, kilitliScrollY);
}

/**
 * Çift gönderim kilidi.
 *
 * -------------------------------------------------------------------------
 * NEDEN
 *
 * İlan verme formu 8'e kadar fotoğraf yüklüyor (multipart). Zayıf ya da
 * uluslararası bir bağlantıda gönderim uzun sürüyor ve ekranda hiçbir şey
 * değişmiyor — kullanıcı "tıkladım, bir şey olmadı" diye tekrar tıklıyor.
 * Sonuç: MÜKERRER İLAN. Nisoya'nın kitlesi tanımı gereği yurt dışında,
 * yani yavaş bağlantı istisna değil kural.
 *
 * Aynı sorun kayıt, iletişim ve ilk mesaj formlarında da var; oralarda
 * bedeli mükerrer kayıt/mesaj.
 *
 * Kullanımı: <form x-data="gonderimKilidi" @submit="kilitle">
 * Düğme metni "Gönderiliyor..." olur ve düğme devre dışı kalır.
 *
 * NOT: `disabled` doğrudan submit düğmesine uygulanır, forma değil — form
 * disabled edilirse tarayıcı alan değerlerini GÖNDERMEZ.
 */
Alpine.data('gonderimKilidi', (meslugu = 'Gönderiliyor...') => ({
    kilitli: false,

    kilitle(olay) {
        if (this.kilitli) {
            olay.preventDefault();

            return;
        }

        this.kilitli = true;

        const dugme = this.$el.querySelector('button[type="submit"], input[type="submit"]');
        if (!dugme) return;

        // Metni sakla ki doğrulama hatasıyla geri dönüşte eski hâli kalsın.
        dugme.dataset.eskiMetin = dugme.innerHTML;
        dugme.innerHTML = meslugu;
        dugme.disabled = true;
    },
}));

/**
 * GÖRSEL KÜÇÜLTÜCÜ — yüklemeden ÖNCE, tarayıcıda.
 *
 * ---------------------------------------------------------------------------
 * NEDEN VAR
 *
 * Sunucu tarafında görsel başına sınır 4 MB (ListingRequest: images.* max:4096)
 * ve PHP upload_max_filesize 5M. Telefonla çekilen bir fotoğraf bunu rahatlıkla
 * aşıyor: 12 MP bir JPEG tipik olarak 4-8 MB. Sahip 2026-08-12'de tam bunu
 * yaşadı. Ölçüldü: 5 MB'lık dosya doğrulamada reddediliyor, hata ekranda
 * görünüyor ve İLAN HİÇ OLUŞMUYOR.
 *
 * Sunucu ayarını (php.ini) büyütmek de bir yol ama tek başına yetmez: 1 vCPU'luk
 * sunucuda 12 MP bir görseli işlemek pahalı, mobil veriyle 8 MB yüklemek yavaş.
 * Kaynağında küçültmek üç sorunu birden çözer.
 *
 * ---------------------------------------------------------------------------
 * SADECE GEREKTİĞİNDE — bu bilinçli bir kısıtlama
 *
 * Canvas'a çizip yeniden kodlamak EXIF'i SİLER. Bu gizlilik açısından iyi (GPS
 * cihazdan hiç çıkmaz) ama yönetimdeki "EXIF Haritası" ekranı GPS kümelemesiyle
 * kopya/dolandırıcılık tespiti yapıyor ve o veriyi kaybeder.
 *
 * Bu yüzden küçültme KOŞULLU: dosya zaten sınırın altındaysa ve boyutları
 * makulse dokunulmaz, EXIF'i korunur. Yalnız reddedilecek olan dosyalar
 * yeniden kodlanır — yani müdahale, sorunu çözen en küçük müdahale.
 *
 * `imageOrientation: 'from-image'`: EXIF yönelimi canvas'a çizerken uygulanır.
 * Olmadan, telefonun dikey çektiği fotoğraf yan yatar — çünkü yönelim bilgisi
 * EXIF'te tutulur ve biz EXIF'i silmiş oluruz.
 *
 * HER HATADA ORİJİNALE DÖNER (fail-safe): küçültme başarısızsa dosya olduğu
 * gibi gönderilir ve sunucu sınırı yine son söz olur.
 */
const GORSEL_AZAMI_KENAR = 2048;   // sunucunun en büyük varyantı 1600px; pay bırakıldı
const GORSEL_ESIK_BAYT = 3 * 1024 * 1024; // 3 MB — 4 MB sınırının altında güvenli marj
const GORSEL_KALITE = 0.85;

Alpine.data('gorselKucultucu', () => ({
    durum: '',
    calisiyor: false,

    async secildi(olay) {
        const girdi = olay.target;
        const dosyalar = Array.from(girdi.files || []);
        if (!dosyalar.length) { this.durum = ''; return; }

        if (typeof createImageBitmap !== 'function' || typeof DataTransfer !== 'function') {
            return; // Eski tarayıcı: dokunma, sunucu sınırı geçerli kalsın.
        }

        this.calisiyor = true;
        this.durum = 'Görseller hazırlanıyor…';
        this.gonderimiKilitle(true);

        let kazanc = 0;
        const sonuc = [];

        for (const dosya of dosyalar) {
            try {
                const yeni = await this.kucult(dosya);
                if (yeni && yeni.size < dosya.size) {
                    kazanc += dosya.size - yeni.size;
                    sonuc.push(yeni);
                } else {
                    sonuc.push(dosya);
                }
            } catch (_) {
                sonuc.push(dosya); // Bu dosya küçültülemedi; orijinali gönder.
            }
        }

        try {
            const dt = new DataTransfer();
            sonuc.forEach((d) => dt.items.add(d));
            girdi.files = dt.files;
        } catch (_) {
            // Dosya listesi değiştirilemedi — orijinaller gönderilir.
        }

        this.calisiyor = false;
        this.gonderimiKilitle(false);
        this.durum = kazanc > 0
            ? `${sonuc.length} görsel hazır · ${this.mb(kazanc)} MB tasarruf edildi`
            : `${sonuc.length} görsel hazır`;
    },

    /*
     * YARIŞ DURUMU: küçültme ~0.5 sn/görsel sürüyor. Kullanıcı 8 fotoğraf
     * seçip hemen "Yayınla"ya basarsa form HENÜZ KÜÇÜLTÜLMEMİŞ orijinalleri
     * gönderir ve sunucu 4 MB sınırından reddeder — yani düzeltmenin kendisi
     * atlanmış olur. İşlem bitene kadar gönderim kapalı.
     */
    gonderimiKilitle(kilit) {
        const form = this.$el.closest('form');
        if (!form) return;

        form.querySelectorAll('button[type="submit"]').forEach((d) => {
            d.disabled = kilit;
            d.classList.toggle('opacity-50', kilit);
            d.classList.toggle('cursor-not-allowed', kilit);
        });
    },

    /** Gerekmiyorsa null döner (dosyaya dokunulmaz, EXIF korunur). */
    async kucult(dosya) {
        if (!dosya.type.startsWith('image/')) return null;

        const bitmap = await createImageBitmap(dosya, { imageOrientation: 'from-image' });
        const enBuyukKenar = Math.max(bitmap.width, bitmap.height);

        // Zaten küçük VE sınırın altındaysa dokunma — EXIF'i korunsun.
        if (dosya.size <= GORSEL_ESIK_BAYT && enBuyukKenar <= GORSEL_AZAMI_KENAR) {
            bitmap.close?.();

            return null;
        }

        const olcek = Math.min(1, GORSEL_AZAMI_KENAR / enBuyukKenar);
        const g = Math.round(bitmap.width * olcek);
        const y = Math.round(bitmap.height * olcek);

        const tuval = document.createElement('canvas');
        tuval.width = g;
        tuval.height = y;
        tuval.getContext('2d').drawImage(bitmap, 0, 0, g, y);
        bitmap.close?.();

        const blob = await new Promise((c) => tuval.toBlob(c, 'image/jpeg', GORSEL_KALITE));
        if (!blob) return null;

        // Uzantı da .jpg olmalı: sunucu `mimes:jpg,jpeg,png,webp` istiyor ve
        // biz her hâlükârda JPEG üretiyoruz.
        const ad = dosya.name.replace(/\.[^.]+$/, '') + '.jpg';

        return new File([blob], ad, { type: 'image/jpeg', lastModified: Date.now() });
    },

    mb(bayt) {
        return (bayt / (1024 * 1024)).toFixed(1);
    },
}));

Alpine.data('altSayfa', () => ({
    acik: false,

    ac() {
        if (this.acik) return;
        this.acik = true;
        govdeyiKilitle();
    },

    kapat() {
        if (!this.acik) return;
        this.acik = false;
        govdeKilidiniAc();
    },

    // Bileşen DOM'dan kalkarken (sayfa geçişi) kilit asılı kalmasın.
    destroy() {
        if (this.acik) govdeKilidiniAc();
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
Alpine.data('avatarCropModal', (crop, focal, saveUrl) => ({
    open: false,
    ready: false,
    saving: false,
    error: null,
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
        this.error = null;
        // $nextTick, Alpine'ın giriş geçişini (x-transition) BEKLER ve geçiş
        // bitmeden hemen önceki karede devam eder — panel o anda hâlâ
        // scale(0.95)'te olabilir. Çerçeveyi transform'dan ETKİLENMEYECEK
        // şekilde ölçmek için: panel geçişi artık sadece opacity kullanıyor
        // (bkz. edit.blade.php `x-transition.opacity`), ama yine de bir
        // sonraki boyama karesini bekleyip GERÇEK layout'u ölçüyoruz.
        await this.$nextTick();
        await new Promise((resolve) => requestAnimationFrame(() => requestAnimationFrame(resolve)));

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

        if (this.natW === 0 || this.natH === 0) {
            this.error = 'Fotoğraf yüklenemedi. Sayfayı yenileyip tekrar dene.';
            return;
        }

        this.restoreState();
        this.ready = this.frameW > 0 && this.frameH > 0;
    },

    // Kaydedilmiş kırpım karesinden pan/zum durumunu geri kur; kayıtlı kırpım
    // yoksa (eski odak-noktası kullanıcıları) eski focal_x/y'yi YAKLAŞIK bir
    // başlangıç konumuna çevirir — aksi halde editör aniden ortalanmış açılır
    // ve kullanıcı hiç dokunmadan Kaydet'e basarsa avatar site genelinde
    // eski konumdan sıçrar.
    restoreState() {
        if (crop.size && crop.size > 0) {
            const s = this.frameW / crop.size;
            this.zoom = Math.max(1, Math.min(3, s / this.coverScale));
            this.panX = -crop.x * this.scale;
            this.panY = -crop.y * this.scale;
        } else {
            this.zoom = 1;
            const slackX = this.natW * this.scale - this.frameW;
            const slackY = this.natH * this.scale - this.frameH;
            this.panX = slackX > 0 ? -slackX * ((focal.x ?? 50) / 100) : (this.frameW - this.natW * this.scale) / 2;
            this.panY = slackY > 0 ? -slackY * ((focal.y ?? 50) / 100) : (this.frameH - this.natH * this.scale) / 2;
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
        if (!this.ready || this.saving) return;
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

        // >= 2 (== değil): 3. parmak inip 2'ye düşünce de (endDrag) taze
        // mesafe kaydedilir — aksi halde yeni parmak çiftinin gerçek mesafesi
        // yerine ESKİ çiftten kalma değer kullanılır ve zum aniden sıçrar.
        if (this._pointers.size >= 2) {
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

        if (this._pointers.size >= 2) {
            // 3 parmaktan 2'ye düştü: kalan çiftin GERÇEK anlık mesafesiyle
            // yeniden kalibre et (bkz. startDrag'teki aynı gerekçe).
            const [a, b] = [...this._pointers.values()];
            this._pinchDist = Math.hypot(a.x - b.x, a.y - b.y);
        } else if (this._pointers.size === 1) {
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
        this.error = null;
        // Tek seferde hesapla ve HEM sunucuya gönder HEM de başarı sonrası
        // yerel state'e yaz — istek uçuşurken kullanıcı sürüklemeye devam
        // ederse (aşağıda ayrıca engellendi) sunucunun kaydettiğiyle yerel
        // durumun AYNI ANA ait olduğundan emin olunur.
        const rect = this.cropRect();
        try {
            const response = await postJson(saveUrl, 'PATCH', rect);
            let data = null;
            try {
                data = await response.json();
            } catch (_) {
                // Gövde JSON değilse (ör. 500 HTML sayfası) sessizce yok say.
            }
            if (response.ok) {
                if (data && data.cropped_url) {
                    this.previewUrl = data.cropped_url + '?t=' + Date.now();
                }
                crop.x = rect.crop_x;
                crop.y = rect.crop_y;
                crop.size = rect.crop_size;
                this.open = false;
                this.ready = false;
            } else {
                this.error = (data && data.message) || 'Kaydedilemedi, lütfen tekrar dene.';
            }
        } catch (_) {
            this.error = 'Bağlantı hatası — internetini kontrol edip tekrar dene.';
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

// Passkey hataları (Faz M2; laravel/passkeys'e geçiş 2026-08-02) — istemci
// paketi DOMException'ları tipli hatalara çevirir (UserCancelledError vb.),
// biz de insan-okur Türkçeye. En sık görülen gerçek neden: sayfa bir
// e-posta/uygulama içi tarayıcıda (Outlook, Gmail, Instagram vb. WKWebView)
// açılmış — bu tür gömülü tarayıcılar Face ID/Touch ID ceremonisine izin
// vermiyor ve ceremony sunucuya hiç ulaşmadan iptalle düşüyor.
function passkeyErrorMessage(error) {
    const inAppHint = 'Bu bağlantıyı bir e-posta/uygulama içi tarayıcıda (ör. Outlook, Gmail) açtıysan Face ID/parmak izi çalışmaz — Safari veya Chrome\'da doğrudan aç, ya da Nisoya\'yı ana ekranına ekleyip oradan kullan.';

    if (error instanceof UserCancelledError) {
        return 'İşlem tamamlanamadı veya izin verilmedi. ' + inAppHint;
    }
    if (error instanceof InvalidDomainError) {
        // Alan adı sabit kodlanmaz; kullanıcının gerçekten üzerinde olduğu host.
        return 'Bu sayfa adresi passkey için güvenli kabul edilmedi. ' + window.location.hostname + ' adresinden doğrudan eriştiğinden emin ol.';
    }
    if (error instanceof PasskeyExistsError) {
        return 'Bu cihaz için zaten kayıtlı bir passkey var.';
    }
    if (error instanceof NotSupportedError) {
        return 'Bu tarayıcı passkey desteklemiyor — telefonundan veya güncel bir tarayıcıdan dene.';
    }
    return 'Passkey işlemi tamamlanamadı. Cihazın desteklemiyor olabilir. ' + inAppHint;
}

// Passkey ile giriş: kayıtlar keşfedilebilir (resident) olduğu için cihaz
// kendi hesap seçicisini gösterir. Uçlar paketin varsayılanları
// (/passkeys/login/options + /passkeys/login).
Alpine.data('passkeyLogin', () => ({
    supported: Passkeys.isSupported(),
    busy: false,
    error: null,

    async login() {
        if (this.busy) return;
        this.busy = true;
        this.error = null;

        try {
            const response = await Passkeys.verify();
            window.location.href = response?.redirect || '/panel';
        } catch (error) {
            this.busy = false;
            this.error = passkeyErrorMessage(error);
        }
    },
}));

// Passkey ekleme (2FA/güvenlik sayfası). Paket 'name' alanını zorunlu tutar;
// kullanıcı ad vermezse UI'daki varsayılan etiketle kaydedilir.
Alpine.data('passkeyManage', () => ({
    supported: Passkeys.isSupported(),
    busy: false,
    error: null,
    alias: '',

    async add() {
        if (this.busy) return;
        this.busy = true;
        this.error = null;

        try {
            await Passkeys.register({ name: this.alias.trim().slice(0, 50) || 'Passkey' });
            window.location.reload();
        } catch (error) {
            this.busy = false;
            this.error = passkeyErrorMessage(error);
        }
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

// iOS Safari autofill "geç boyama" hafifletmesi (bkz. app.css onAutoFillStart).
// WebKit, Face ID ile autofill'lenen kullanıcı adı/parolayı kutucuk odaklanana
// kadar görsel olarak boyamıyor — değer DOM'da vardır (form doğru gönderilir),
// yalnızca ekrana çizilmez. Autofill'i CSS animasyonuyla tespit edip alanı
// güvenle dürterek (DEĞERE dokunmadan) repaint'i tetikliyoruz.
// document seviyesinde tek dinleyici — sonradan eklenen inputlar için de çalışır.
document.addEventListener('animationstart', (event) => {
    if (event.animationName !== 'onAutoFillStart') return;

    const el = event.target;
    if (!(el instanceof HTMLInputElement)) return;

    requestAnimationFrame(() => {
        // Kısa süreli bir compositing katmanı + input olayı repaint'i zorlar;
        // değer değişmez, imleç/seçim bozulmaz.
        el.style.transform = 'translateZ(0)';
        el.dispatchEvent(new Event('input', { bubbles: true }));
        requestAnimationFrame(() => {
            el.style.transform = '';
        });
    });
}, true);

// Kanban panosu: aday kartlarını sütunlar arasında sürükleme (bkz.
// panel/jobs/applicants.blade.php).
//
// SÜRÜKLEME YALNIZCA BİR HIZLANDIRICIDIR. Her kartta durum <select>'i +
// "Uygula" butonu vardır; klavye, ekran okuyucu ve JS'siz tarayıcı o yoldan
// gider. Sürükleme sadece `pointer: fine` (fare) ortamda açılır: dokunmatikte
// sütunlar tek tek gösterildiği için sürüklenecek hedef yoktur, üstelik
// sürükleme yatay/dikey kaydırmayla çakışır.
//
// Pointer Events, focalDrag'deki ev deseninin aynısı: pointerdown +
// setPointerCapture + window'a bağlı move/up/cancel. pointercancel şart —
// sistem bir gesture'ı devralırsa tek kurtarıcı odur.
Alpine.data('kanbanPano', (ilkDurum) => ({
    fareVar: false,
    aktifSutun: ilkDurum, // mobil tek-sütun modu — bilinçli olarak KALICI DEĞİL
    surukleniyor: false,
    kart: null,
    hayalet: null,
    kaynakDurum: null,
    hedefDurum: null,
    aktifPointerId: null,
    tutmaX: 0,
    tutmaY: 0,
    baslamaX: 0,
    baslamaY: 0,
    mesaj: '',
    mesajTipi: 'bilgi',
    _move: null,
    _up: null,
    _key: null,

    init() {
        this.fareVar = window.matchMedia('(pointer: fine)').matches;
    },

    tut(e, durum) {
        if (!this.fareVar || this.aktifPointerId !== null) return;
        // Kart içindeki etkileşimli öğeler sürüklemeyi devralmamalı; aksi
        // halde CV linkine tıklamak ya da açılır menüyü kullanmak imkânsızlaşır.
        if (e.target.closest('a, button, select, textarea, input, summary, details, label')) return;
        if (e.button !== 0) return;

        this.kart = e.currentTarget;
        this.kaynakDurum = durum;
        this.baslamaX = e.clientX;
        this.baslamaY = e.clientY;
        this.aktifPointerId = e.pointerId;

        this._move = (ev) => this.hareket(ev);
        this._up = () => this.birak();
        this._key = (ev) => {
            if (ev.key === 'Escape') this.iptal();
        };
        window.addEventListener('pointermove', this._move);
        window.addEventListener('pointerup', this._up);
        window.addEventListener('pointercancel', this._up);
        window.addEventListener('keydown', this._key);
    },

    hareket(e) {
        if (this.aktifPointerId === null) return;

        if (!this.surukleniyor) {
            // Eşik: 6px'in altındaki hareket sürükleme değil tıklamadır.
            // Bu olmadan karttaki her tıklama hayalet üretirdi.
            if (Math.hypot(e.clientX - this.baslamaX, e.clientY - this.baslamaY) < 6) return;
            this.hayaletiOlustur();
        }

        this.hayalet.style.left = e.clientX - this.tutmaX + 'px';
        this.hayalet.style.top = e.clientY - this.tutmaY + 'px';

        // Hayaletin pointer-events'i none olduğu için elementFromPoint altındaki
        // gerçek sütunu görür.
        const sutun = document.elementFromPoint(e.clientX, e.clientY)?.closest('[data-durum]');
        this.hedefDurum = sutun ? sutun.dataset.durum : null;
    },

    hayaletiOlustur() {
        const kutu = this.kart.getBoundingClientRect();
        this.tutmaX = this.baslamaX - kutu.left;
        this.tutmaY = this.baslamaY - kutu.top;

        const h = this.kart.cloneNode(true);
        h.style.position = 'fixed';
        h.style.left = kutu.left + 'px';
        h.style.top = kutu.top + 'px';
        h.style.width = kutu.width + 'px';
        h.style.pointerEvents = 'none';
        // z-40: header ve mobil sekme çubuğunun (z-30) üstünde, modalların
        // (z-50) altında — panel sayfalarında z-40 boştur (bağış FAB'ı orada yok).
        h.style.zIndex = '40';
        h.style.margin = '0';
        if (!window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
            h.style.transform = 'rotate(1.5deg)';
            h.style.boxShadow = '0 20px 45px -12px rgba(0,0,0,0.35)';
        }
        document.body.appendChild(h);

        this.hayalet = h;
        this.surukleniyor = true;
        this.kart.style.opacity = '0.35';
    },

    async birak() {
        const kart = this.kart;
        const kaynak = this.kaynakDurum;
        const hedef = this.hedefDurum;
        const suruklendi = this.surukleniyor;
        this.temizle();

        if (!suruklendi || !hedef || hedef === kaynak || !kart) return;

        const hedefListe = document.querySelector('[data-durum="' + hedef + '"] [data-liste]');
        if (!hedefListe) return;
        const kaynakListe = kart.parentElement;

        // İyimser taşıma: sunucu yanıtını beklemeden kartı yerine koy, hata
        // olursa geri al. Sahibin 20 kartı hızlıca elemesi gereken bir ekran.
        hedefListe.prepend(kart);
        this.sayaciDegistir(kaynak, -1);
        this.sayaciDegistir(hedef, 1);

        try {
            const yanit = await fetch(kart.dataset.url, {
                method: 'PATCH',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': kart.querySelector('input[name="_token"]')?.value ?? '',
                },
                body: JSON.stringify({ status: hedef }),
            });
            if (!yanit.ok) throw new Error('durum kaydedilemedi');
            const veri = await yanit.json();

            // Erişilebilir yol (select) ile sürükleme aynı gerçeği göstermeli.
            const sec = kart.querySelector('select[name="status"]');
            if (sec) sec.value = veri.durum;

            this.mesajiGoster(veri.mesaj, 'basari');
        } catch (_) {
            kaynakListe.prepend(kart);
            this.sayaciDegistir(hedef, -1);
            this.sayaciDegistir(kaynak, 1);
            this.mesajiGoster('Durum kaydedilemedi — bağlantını kontrol edip tekrar dene.', 'hata');
        }
    },

    iptal() {
        this.temizle();
    },

    temizle() {
        window.removeEventListener('pointermove', this._move);
        window.removeEventListener('pointerup', this._up);
        window.removeEventListener('pointercancel', this._up);
        window.removeEventListener('keydown', this._key);
        if (this.hayalet) this.hayalet.remove();
        if (this.kart) this.kart.style.opacity = '';
        this.hayalet = null;
        this.surukleniyor = false;
        this.aktifPointerId = null;
        this.hedefDurum = null;
    },

    sayaciDegistir(durum, fark) {
        const el = document.querySelector('[data-sayac="' + durum + '"]');
        if (!el) return;
        el.textContent = Math.max(0, (parseInt(el.textContent, 10) || 0) + fark);
    },

    mesajiGoster(metin, tip) {
        this.mesaj = metin;
        this.mesajTipi = tip;
    },
}));

// Canlı tema özelleştirici (bkz. components/tema-ozellestirici.blade.php).
//
// ÖNİZLEME NEDEN SATIR İÇİ STİL DEĞİL, <style> BLOĞU:
// documentElement.style.setProperty() ile yazılan satır içi değerler hem açık
// hem koyu modu aynı anda ezer ve satır içi stille ".dark" koşulu İFADE
// EDİLEMEZ. Vitrin'in koyu setinde --color-emerald-600 bilerek açık moddakinden
// DAHA AÇIK bir tona çıkar (buton metni koyu olduğu için); satır içi tek bir
// değer bu sözleşmeyi sessizce kırar ve koyu modda okunmaz butonlar üretirdi.
// Bu yüzden önizleme, `html:root {}` ve `html:root.dark {}` kurallarını içeren
// tek bir <style> bloğu olarak yazılır — tema bileşenleriyle aynı dili konuşur.
// (CSP style-src'ı kısıtlamıyor, bkz. SecurityHeaders docblock.)
Alpine.data('temaOzellestirici', (yapilandirma) => ({
    ...yapilandirma.baslangic,
    aksanlar: yapilandirma.aksanlar,
    aileler: yapilandirma.aileler,
    fontlar: yapilandirma.fontlar,
    aktifTema: yapilandirma.aktifTema,
    kayitUrl: yapilandirma.kayitUrl,
    sifirlaUrl: yapilandirma.sifirlaUrl,

    acik: false,
    kucuk: false,
    kirli: false,
    calisiyor: false,
    mesaj: '',
    mesajTipi: 'bilgi',

    init() {
        // Kaydetmeden ayrılmak, denenen görünümün kaybolması demek. Sessizce
        // olmasın.
        window.addEventListener('beforeunload', (e) => {
            if (this.kirli) {
                e.preventDefault();
                e.returnValue = '';
            }
        });
    },

    get vitrinMi() {
        return this.aktifTema === 'vitrin';
    },

    /** Klasik-özel kontroller Vitrin aktifken gerçekten çalışmaz. */
    get klasikKilitli() {
        return this.vitrinMi;
    },

    degisti() {
        this.kirli = true;
        this.mesaj = '';
        this.onizle();
    },

    onizle() {
        let acikKurallar = '';
        let koyuKurallar = '';

        if (this.vitrinMi) {
            const rampa = this.aksanlar[this.vitrin_aksan];
            if (rampa) {
                for (const [basamak, renk] of Object.entries(rampa.acik)) {
                    acikKurallar += `--color-emerald-${basamak}:${renk};`;
                }
                for (const [basamak, renk] of Object.entries(rampa.koyu)) {
                    koyuKurallar += `--color-emerald-${basamak}:${renk};`;
                }
                acikKurallar += `--nisoya-primary:${rampa.hex};--nisoya-seal:${rampa.hex};`;
            }
        } else {
            if (this.renk_kaynagi === 'ozel') {
                // tasarim-theme.blade.php ile AYNI türetme; böylece önizleme ve
                // kaydedilmiş hâl birbirinden sapmaz.
                const c = this.primary_color;
                acikKurallar +=
                    `--color-emerald-50:color-mix(in srgb, ${c} 8%, white);` +
                    `--color-emerald-100:color-mix(in srgb, ${c} 15%, white);` +
                    `--color-emerald-200:color-mix(in srgb, ${c} 28%, white);` +
                    `--color-emerald-300:color-mix(in srgb, ${c} 45%, white);` +
                    `--color-emerald-400:color-mix(in srgb, ${c} 68%, white);` +
                    `--color-emerald-500:color-mix(in srgb, ${c} 85%, white);` +
                    `--color-emerald-600:${c};` +
                    `--color-emerald-700:color-mix(in srgb, ${c} 80%, black);` +
                    `--color-emerald-800:color-mix(in srgb, ${c} 62%, black);` +
                    `--color-emerald-900:color-mix(in srgb, ${c} 45%, black);` +
                    `--nisoya-primary:${c};`;
            } else if (this.marka_rengi !== 'emerald') {
                // brand-theme.blade.php ile aynı yönlendirme; bu değişkenler
                // app.css'teki @source inline(...) sayesinde derlenmiş durumda.
                for (const b of [50, 100, 200, 300, 400, 500, 600, 700, 800, 900, 950]) {
                    acikKurallar += `--color-emerald-${b}:var(--color-${this.marka_rengi}-${b});`;
                }
            }

            // Font CSS'i sunucudan gelir (TemaJetonlari::FONTLAR) — burada ikinci
            // bir kopya tutmak önizlemeyi kaydedilen sonuçtan ayırırdı.
            acikKurallar += `--font-sans:${this.fontlar[this.font_family] || this.fontlar.sans};`;

            const olcek = {
                sharp: ['2px', '3px', '4px', '6px'],
                soft: ['6px', '8px', '10px', '14px'],
                pill: ['14px', '18px', '24px', '32px'],
                modern: ['.5rem', '.75rem', '1rem', '1.5rem'],
            }[this.border_radius] || ['.5rem', '.75rem', '1rem', '1.5rem'];
            acikKurallar += `--radius-lg:${olcek[0]};--radius-xl:${olcek[1]};--radius-2xl:${olcek[2]};--radius-3xl:${olcek[3]};`;
        }

        let ekKurallar = '';
        if (!this.vitrinMi && !this.glassmorphism) {
            ekKurallar += '[class*="backdrop-blur"]{backdrop-filter:none !important;-webkit-backdrop-filter:none !important;}';
        }
        if (!this.vitrinMi && !this.smooth_animations) {
            ekKurallar += '*,*::before,*::after{animation-duration:.01ms !important;transition-duration:.01ms !important;}';
        }

        // html:root — tema bileşenlerinin :root kuralından daha yüksek
        // özgüllük, böylece kaynak sırasından bağımsız olarak kazanır.
        const css =
            (acikKurallar ? `html:root{${acikKurallar}}` : '') +
            (koyuKurallar ? `html:root.dark{${koyuKurallar}}` : '') +
            ekKurallar;

        let blok = document.getElementById('tema-onizleme');
        if (!blok) {
            blok = document.createElement('style');
            blok.id = 'tema-onizleme';
            document.head.appendChild(blok);
        }
        blok.textContent = css;
    },

    onizlemeyiKaldir() {
        document.getElementById('tema-onizleme')?.remove();
    },

    govde() {
        const g = {};
        if (this.vitrinMi) {
            g.vitrin_aksan = this.vitrin_aksan;
        } else {
            if (this.renk_kaynagi === 'ozel') {
                g.primary_color = this.primary_color;
            } else {
                g.marka_rengi = this.marka_rengi;
            }
            g.font_family = this.font_family;
            g.border_radius = this.border_radius;
            g.glassmorphism = this.glassmorphism;
            g.smooth_animations = this.smooth_animations;
        }

        return g;
    },

    async gonder(url, govde) {
        this.calisiyor = true;
        try {
            const yanit = await fetch(url, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    Accept: 'application/json',
                    'X-CSRF-TOKEN': this.$refs.jeton.value,
                },
                body: JSON.stringify(govde),
            });
            if (!yanit.ok) throw new Error('kaydedilemedi');
            const veri = await yanit.json();
            this.kirli = false;
            this.mesaj = veri.mesaj;
            this.mesajTipi = 'basari';
        } catch (_) {
            this.mesaj = 'Kaydedilemedi. Bağlantını kontrol edip tekrar dene.';
            this.mesajTipi = 'hata';
        } finally {
            this.calisiyor = false;
        }
    },

    kaydet() {
        return this.gonder(this.kayitUrl, this.govde());
    },

    async sifirla() {
        await this.gonder(this.sifirlaUrl, {});
        this.onizlemeyiKaldir();
        window.location.reload();
    },

    vazgec() {
        this.onizlemeyiKaldir();
        this.kirli = false;
        this.acik = false;
        window.location.reload();
    },
}));

window.Alpine = Alpine;
Alpine.start();
