    {{-- Satıcı bandı (CTA) --}}
    @if (\App\Support\HomeSections::visible('cta'))
        <section class="mx-auto max-w-6xl px-4 pt-14" x-data x-reveal>
            <div class="relative overflow-hidden rounded-[24px] bg-stone-800 p-8 text-white sm:p-10 dark:bg-stone-900 dark:ring-1 dark:ring-stone-800">
                <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-[radial-gradient(circle,rgba(62,99,240,.35),transparent_70%)]" aria-hidden="true"></div>
                <div class="relative grid items-center gap-8 md:grid-cols-[minmax(0,1fr)_minmax(0,320px)]">
                    <div>
                        <span class="inline-flex rounded-full bg-white/10 px-3 py-1.5 text-xs font-bold uppercase tracking-wider text-emerald-300">Satıcı ol</span>
                        <h2 class="mt-3 text-2xl font-extrabold tracking-tight sm:text-3xl" style="text-wrap: pretty">{{ setting('home.cta_baslik') }}</h2>
                        <p class="mt-2 max-w-lg text-sm font-medium leading-relaxed text-white/70">{{ setting('home.cta_metin') }}</p>
                        <div class="mt-6 flex flex-wrap gap-3">
                            <a href="{{ url('/panel/ilan/yeni') }}" class="inline-flex items-center gap-2 rounded-xl bg-white px-5 py-3 text-sm font-bold text-stone-800 transition hover:bg-stone-100">
                                <x-heroicon-o-plus class="h-4 w-4" />{{ setting('home.cta_buton') }}
                            </a>
                            <a href="{{ url('/nasil-calisir') }}" class="inline-flex items-center gap-2 rounded-xl border border-white/25 px-5 py-3 text-sm font-bold text-white transition hover:bg-white/10">Nasıl çalışır?</a>
                        </div>
                    </div>
                    <div class="grid grid-cols-2 gap-3" aria-hidden="false">
                        <div class="rounded-2xl bg-white/[.07] p-4"><div class="text-2xl font-extrabold">{{ $stats['countries'] }}</div><div class="mt-0.5 text-xs font-semibold text-white/60">ülke</div></div>
                        <div class="rounded-2xl bg-white/[.07] p-4"><div class="text-2xl font-extrabold">{{ $stats['cities'] }}</div><div class="mt-0.5 text-xs font-semibold text-white/60">şehir</div></div>
                        <div class="rounded-2xl bg-white/[.07] p-4"><div class="text-2xl font-extrabold">{{ $stats['categories'] }}</div><div class="mt-0.5 text-xs font-semibold text-white/60">kategori</div></div>
                        <div class="rounded-2xl bg-white/[.07] p-4"><div class="text-2xl font-extrabold text-emerald-300">%0</div><div class="mt-0.5 text-xs font-semibold text-white/60">komisyon</div></div>
                    </div>
                </div>
            </div>
        </section>
    @endif
