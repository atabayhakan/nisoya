import Alpine from 'alpinejs';

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

window.Alpine = Alpine;
Alpine.start();
