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
    class="fixed inset-x-3 bottom-3 z-50 mx-auto max-w-2xl rounded-2xl border border-stone-200 bg-white p-4 shadow-2xl ring-1 ring-stone-200 sm:p-5"
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
            KEY: 'nisoya_consent',
            init() {
                try {
                    const stored = localStorage.getItem(this.KEY);
                    if (stored === 'accepted') {
                        this.accepted = true;
                        this.decided = 'accepted';
                        this.applyConsent(true);
                    } else if (stored === 'rejected') {
                        this.decided = 'rejected';
                        this.applyConsent(false);
                    }
                } catch (e) {}
            },
            accept() {
                this.accepted = true;
                this.decided = 'accepted';
                try { localStorage.setItem(this.KEY, 'accepted'); } catch (e) {}
                this.applyConsent(true);
                this.reloadAdsIfNeeded();
            },
            reject() {
                this.accepted = false;
                this.decided = 'rejected';
                try { localStorage.setItem(this.KEY, 'rejected'); } catch (e) {}
                this.applyConsent(false);
            },
            applyConsent(granted) {
                document.documentElement.dataset.consentAds = granted ? 'granted' : 'denied';
                document.documentElement.dataset.consentAnalytics = granted ? 'granted' : 'denied';

                if (typeof gtag === 'function') {
                    gtag('consent', 'update', {
                        ad_storage: granted ? 'granted' : 'denied',
                        ad_user_data: granted ? 'granted' : 'denied',
                        ad_personalization: granted ? 'granted' : 'denied',
                        analytics_storage: granted ? 'granted' : 'denied',
                    });
                }
            },
            reloadAdsIfNeeded() {
                // AdSense auto ads zaten yüklüyse ek bir şey yapma; aksi durumda yeniden tetikle
                try {
                    if (window.adsbygoogle && Array.isArray(window.adsbygoogle)) {
                        window.adsbygoogle.push({});
                    }
                } catch (e) {}
            },
        };
    }
</script>