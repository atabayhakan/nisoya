    @if (\App\Support\HomeSections::visible('cta'))
    {{-- CTA (Konteyner İçinde / Modern Degrade Kart) --}}
    <section class="mx-auto max-w-6xl px-4 py-8 sm:py-12" x-data x-reveal>
        <div class="relative overflow-hidden rounded-3xl border border-stone-200/80 bg-gradient-to-br from-stone-900 via-stone-900 to-emerald-950 px-6 py-10 text-white shadow-xl sm:px-12 sm:py-14 dark:border-stone-800">
            <div class="pointer-events-none absolute -right-20 -top-20 h-64 w-64 rounded-full bg-emerald-500/20 blur-3xl" aria-hidden="true"></div>
            <div class="relative z-10 grid gap-8 lg:grid-cols-12 lg:items-center">
                <div class="lg:col-span-7">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-white/10 px-3 py-1 text-xs font-semibold text-emerald-300 backdrop-blur">
                        <span>✨</span> {{ setting('genel.site_adi', 'Nisoya') }} Topluluğu
                    </span>
                    <h2 class="mt-3 text-2xl font-bold tracking-tight text-white sm:text-4xl">
                        {{ setting('home.cta_baslik', 'İlk ilanın 3 dakikada yayında, kuruş ödemeden.') }}
                    </h2>
                    <p class="mt-3 max-w-xl text-sm sm:text-base text-stone-300">
                        {{ setting('home.cta_metin', 'İlan ver, mesajlaş, anlaş — hepsi tamamen ücretsiz ve aracısız.') }}
                    </p>
                    <div class="mt-6 flex flex-wrap items-center gap-3">
                        <a href="{{ url('/kayit') }}"
                           class="inline-flex items-center gap-2 rounded-2xl bg-white px-6 py-3.5 text-sm font-bold text-stone-900 shadow-lg transition hover:-translate-y-0.5 hover:bg-stone-100 hover:shadow-xl dark:bg-emerald-400 dark:text-stone-950 dark:hover:bg-emerald-300">
                            <span>+</span>
                            <span>{{ setting('home.cta_buton', 'Ücretsiz İlan Ver') }}</span>
                        </a>
                        @if (\App\Support\Modules::enabled('hali_saha'))
                            <a href="{{ route('football.index') }}"
                               class="inline-flex items-center gap-2 rounded-2xl border border-white/20 bg-white/10 px-5 py-3.5 text-sm font-semibold text-white backdrop-blur transition hover:bg-white/20">
                                <span>⚽</span>
                                <span>Takımını Kur</span>
                            </a>
                        @endif
                    </div>
                </div>
                <div class="grid grid-cols-2 gap-3 lg:col-span-5">
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4 backdrop-blur text-center">
                        <p class="text-2xl font-black text-emerald-400">%0</p>
                        <p class="mt-1 text-xs text-stone-300">Komisyon Yok</p>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4 backdrop-blur text-center">
                        <p class="text-2xl font-black text-white">Ücretsiz</p>
                        <p class="mt-1 text-xs text-stone-300">İlan & Üyelik</p>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4 backdrop-blur text-center">
                        <p class="text-2xl font-black text-white">Doğrudan</p>
                        <p class="mt-1 text-xs text-stone-300">Kullanıcı İletişimi</p>
                    </div>
                    <div class="rounded-2xl border border-white/10 bg-white/5 p-4 backdrop-blur text-center">
                        <p class="text-2xl font-black text-emerald-400">Türkçe</p>
                        <p class="mt-1 text-xs text-stone-300">Yerel Topluluk</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
    @endif
