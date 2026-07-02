<x-layouts.app :title="'Çerez Tercihleri — Nisoya'">
    <article class="mx-auto max-w-2xl px-4 py-10">
        <header class="mb-8 border-b border-stone-200 pb-6">
            <p class="text-xs font-semibold uppercase tracking-wider text-emerald-700">Gizlilik</p>
            <h1 class="mt-2 text-3xl font-bold text-stone-900">Çerez Tercihleri</h1>
            <p class="mt-2 text-sm text-stone-500">
                Sitemizde kullanılan çerez kategorilerini buradan yönetebilirsin.
                Tercihlerin tarayıcında saklanır.
            </p>
        </header>

        <div
            x-data="cookiePreferences()"
            x-init="init()"
            class="space-y-5"
        >
            {{-- Zorunlu --}}
            <div class="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex-1">
                        <h2 class="text-base font-semibold text-stone-900">🔒 Zorunlu çerezler</h2>
                        <p class="mt-1 text-sm text-stone-600">
                            Oturum açma, dil tercihi, tema, güvenlik için gereklidir. Bu çerezler kapatılamaz.
                        </p>
                    </div>
                    <div class="flex items-center">
                        <span class="inline-flex items-center rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700">
                            Her zaman açık
                        </span>
                    </div>
                </div>
            </div>

            {{-- Analytics --}}
            <div class="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex-1">
                        <h2 class="text-base font-semibold text-stone-900">📊 Analitik çerezleri (Google Analytics 4)</h2>
                        <p class="mt-1 text-sm text-stone-600">
                            Sayfa görüntülemeleri, oturum süresi gibi anonim metrikler. IP anonimleştirmesi aktiftir.
                        </p>
                    </div>
                    <label class="relative inline-flex cursor-pointer items-center">
                        <input type="checkbox" x-model="prefs.analytics" class="peer sr-only">
                        <div class="h-6 w-11 rounded-full bg-stone-200 transition peer-checked:bg-emerald-600 peer-focus:ring-2 peer-focus:ring-emerald-300"></div>
                        <div class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow transition peer-checked:translate-x-5"></div>
                    </label>
                </div>
            </div>

            {{-- Reklam --}}
            <div class="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex-1">
                        <h2 class="text-base font-semibold text-stone-900">📢 Reklam çerezleri (Google AdSense)</h2>
                        <p class="mt-1 text-sm text-stone-600">
                            Siteye gelir sağlayan reklamların gösterimi ve ölçümü. Kişiselleştirilmiş reklamlar için kullanılır.
                            Kapatırsan sitemiz yine ücretsiz kalır ancak daha az gelir elde ederiz.
                        </p>
                    </div>
                    <label class="relative inline-flex cursor-pointer items-center">
                        <input type="checkbox" x-model="prefs.ads" class="peer sr-only">
                        <div class="h-6 w-11 rounded-full bg-stone-200 transition peer-checked:bg-emerald-600 peer-focus:ring-2 peer-focus:ring-emerald-300"></div>
                        <div class="absolute left-0.5 top-0.5 h-5 w-5 rounded-full bg-white shadow transition peer-checked:translate-x-5"></div>
                    </label>
                </div>
            </div>

            <div class="flex flex-col gap-3 pt-4 sm:flex-row">
                <button
                    type="button"
                    @click="saveAll()"
                    class="flex-1 rounded-xl bg-emerald-600 px-5 py-3 text-sm font-semibold text-white shadow-sm transition hover:bg-emerald-700"
                >
                    Seçtiklerimi kaydet
                </button>
                <button
                    type="button"
                    @click="acceptAll()"
                    class="rounded-xl border border-emerald-300 bg-white px-5 py-3 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-50"
                >
                    Tümünü kabul et
                </button>
                <button
                    type="button"
                    @click="rejectAll()"
                    class="rounded-xl border border-stone-300 bg-white px-5 py-3 text-sm font-semibold text-stone-700 transition hover:bg-stone-50"
                >
                    Sadece zorunlu
                </button>
            </div>

            <div
                x-show="savedAt"
                x-transition.opacity
                class="rounded-xl border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800"
                role="status"
                aria-live="polite"
            >
                ✅ Tercihlerin kaydedildi.
            </div>

            <p class="pt-2 text-center text-xs text-stone-400">
                Detaylı bilgi için <a href="/gizlilik" class="underline hover:text-stone-600">Gizlilik Politikası</a>'nı inceleyebilirsin.
            </p>
        </div>
    </article>

    <script>
        function cookiePreferences() {
            return {
                KEY: 'nisoya_consent_v1',
                savedAt: false,
                prefs: { analytics: false, ads: false },
                init() {
                    try {
                        const stored = JSON.parse(localStorage.getItem(this.KEY) || 'null');
                        if (stored) {
                            this.prefs.analytics = !!stored.analytics;
                            this.prefs.ads = !!stored.ads;
                        }
                    } catch (e) {}
                },
                save() {
                    try {
                        localStorage.setItem(this.KEY, JSON.stringify(this.prefs));
                    } catch (e) {}

                    document.documentElement.dataset.consentAds = this.prefs.ads ? 'granted' : 'denied';
                    document.documentElement.dataset.consentAnalytics = this.prefs.analytics ? 'granted' : 'denied';

                    if (typeof gtag === 'function') {
                        gtag('consent', 'update', {
                            ad_storage: this.prefs.ads ? 'granted' : 'denied',
                            ad_user_data: this.prefs.ads ? 'granted' : 'denied',
                            ad_personalization: this.prefs.ads ? 'granted' : 'denied',
                            analytics_storage: this.prefs.analytics ? 'granted' : 'denied',
                        });
                    }

                    // AdSense'i yeniden tetikle (reklam onay verildiyse)
                    if (this.prefs.ads) {
                        try {
                            (window.adsbygoogle = window.adsbygoogle || []).push({});
                        } catch (e) {}
                    } else {
                        // Reddedildiyse slot'ları temizle
                        document.querySelectorAll('.adsense-slot').forEach((el) => {
                            el.innerHTML = '';
                        });
                    }
                },
                saveAll() {
                    this.save();
                    this.savedAt = true;
                    setTimeout(() => this.savedAt = false, 2500);
                },
                acceptAll() {
                    this.prefs.analytics = true;
                    this.prefs.ads = true;
                    this.saveAll();
                },
                rejectAll() {
                    this.prefs.analytics = false;
                    this.prefs.ads = false;
                    this.saveAll();
                },
            };
        }
    </script>
</x-layouts.app>