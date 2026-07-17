@once
@push('head')
<style>
    [x-cloak] { display: none !important; }
</style>
@endpush
@endonce

<div
    x-data="cookieConsent()"
    x-init="init()"
    x-cloak
    x-show="!accepted && decided === null"
    x-transition.opacity.duration.200ms
    class="fixed inset-x-3 bottom-20 z-50 mx-auto max-w-2xl rounded-2xl border border-stone-200 bg-white p-4 shadow-2xl ring-1 ring-stone-200 sm:p-5 md:bottom-3"
    role="dialog"
    aria-live="polite"
    aria-label="Çerez tercihleri"
>
    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:gap-4">
        <span aria-hidden="true" class="text-2xl">🍪</span>
        <div class="flex-1 text-sm text-stone-700">
            <p class="font-semibold text-stone-900">Çerez ve gizlilik tercihleri</p>
            <p class="mt-1 leading-relaxed">
                Sitemizde hizmeti geliştirmek ve reklam göstermek için çerezler kullanıyoruz.
                Google AdSense ve Analytics çerezleri, sitemizin ücretsiz kalmasını sağlayan
                reklam gelirlerinin ölçülmesine yardımcı olur. Detaylar için
                <a href="/gizlilik" class="font-medium text-emerald-700 underline-offset-2 hover:underline">Gizlilik Politikası</a>'nı
                inceleyebilir veya
                <a href="/cerez-tercihleri" class="font-medium text-emerald-700 underline-offset-2 hover:underline">tercihlerini yönetebilirsin</a>.
            </p>
        </div>
        <div class="flex flex-shrink-0 gap-2 sm:flex-col">
            <button
                type="button"
                @click="accept()"
                class="inline-flex items-center justify-center rounded-lg bg-emerald-600 px-3 py-2 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700"
            >
                Kabul Et
            </button>
            <button
                type="button"
                @click="reject()"
                class="inline-flex items-center justify-center rounded-lg border border-stone-300 bg-white px-3 py-2 text-sm font-medium text-stone-700 transition hover:bg-stone-50"
            >
                Reddet
            </button>
        </div>
    </div>
</div>

<script>
    function cookieConsent() {
        return {
            accepted: false,
            decided: null,
            // /cerez-tercihleri sayfasıyla AYNI anahtar (nisoya_consent_v1) ve
            // AYNI {analytics, ads} formatı kullanılır. Eskiden bu banner ayrı
            // bir "nisoya_consent" anahtarına düz 'accepted'/'rejected' string'i
            // yazıyordu — kullanıcı detaylı sayfada tercihini kaydetse bile bu
            // banner onu göremiyor, tekrar tekrar çıkıyordu (ve tam tersi).
            KEY: 'nisoya_consent_v1',
            init() {
                try {
                    const stored = JSON.parse(localStorage.getItem(this.KEY) || 'null');
                    if (stored) {
                        this.accepted = !!stored.analytics || !!stored.ads;
                        this.decided = 'set';
                        this.applyConsent(!!stored.analytics, !!stored.ads);
                    }
                } catch (e) {}
            },
            accept() {
                this.accepted = true;
                this.decided = 'accepted';
                this.store(true, true);
                this.applyConsent(true, true);
            },
            reject() {
                this.accepted = false;
                this.decided = 'rejected';
                this.store(false, false);
                this.applyConsent(false, false);
            },
            store(analytics, ads) {
                try { localStorage.setItem(this.KEY, JSON.stringify({ analytics, ads })); } catch (e) {}
            },
            applyConsent(analyticsGranted, adsGranted) {
                document.documentElement.dataset.consentAnalytics = analyticsGranted ? 'granted' : 'denied';
                document.documentElement.dataset.consentAds = adsGranted ? 'granted' : 'denied';

                // Onaylanan kategorinin script'lerini şimdi gerçekten yükle
                // (bkz. app.blade.php — onay öncesi bu script'ler <template>
                // içinde inert'tir, hiç çalışmaz/ağ isteği atmaz).
                if (analyticsGranted) window.nisoyaActivateConsent('analytics');
                if (adsGranted) window.nisoyaActivateConsent('ads');

                if (typeof gtag === 'function') {
                    gtag('consent', 'update', {
                        ad_storage: adsGranted ? 'granted' : 'denied',
                        ad_user_data: adsGranted ? 'granted' : 'denied',
                        ad_personalization: adsGranted ? 'granted' : 'denied',
                        analytics_storage: analyticsGranted ? 'granted' : 'denied',
                    });
                }
            },
        };
    }
</script>