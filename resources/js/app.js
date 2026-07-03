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

window.Alpine = Alpine;
Alpine.start();
