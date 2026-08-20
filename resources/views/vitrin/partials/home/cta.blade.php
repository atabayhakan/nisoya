    {{-- Satıcı bandı (CTA) --}}
    @if (\App\Support\HomeSections::visible('cta'))
        <section class="mx-auto max-w-6xl px-4 pt-14" x-data x-reveal>
            <div class="relative overflow-hidden rounded-[24px] bg-stone-800 p-8 text-white sm:p-10 dark:bg-stone-900 dark:ring-1 dark:ring-stone-800">
                <div class="pointer-events-none absolute -right-24 -top-24 h-72 w-72 rounded-full bg-[radial-gradient(circle,color-mix(in_srgb,var(--color-emerald-600)_35%,transparent),transparent_70%)]" aria-hidden="true"></div>
                <div class="relative grid items-center gap-8 md:grid-cols-[minmax(0,1fr)_minmax(0,320px)]">
                    <div>
                        <span class="inline-flex rounded-full bg-white/10 px-3 py-1.5 text-xs font-bold uppercase tracking-wider text-emerald-300">Satıcı ol</span>
                        <h2 class="mt-3 text-2xl font-extrabold sm:text-3xl" style="text-wrap: pretty">{{ setting('home.cta_baslik') }}</h2>
                        <p class="mt-2 max-w-lg text-sm font-medium leading-relaxed text-white/70">{{ setting('home.cta_metin') }}</p>
                        <div class="mt-6 flex flex-wrap gap-3">
                            <x-button :href="route('panel.listings.create')" variant="inverse" size="lg">
                                <x-heroicon-o-plus class="h-4 w-4" />{{ setting('home.cta_buton') }}
                            </x-button>
                            <x-button :href="route('pages.how')" variant="outline-dark" size="lg">Nasıl çalışır?</x-button>
                        </div>
                    </div>
                    {{-- SAYIM DEĞİL, SÖZ (2026-08-05).

                         Buradaki dört kutu eskiden katalog büyüklüğü sayıyordu:
                         "25 ülke · 50 şehir · 97 kategori". Üçü de sahte
                         değildi ama hiçbiri gerçek hareketi anlatmıyordu —
                         'şehir' CitySeeder'ın ülke başına tohumladığı sayı,
                         'kategori' katalog boyutu. Aynı yanıltıcı üçlü hero'nun
                         kanıt satırından zaten kaldırılmıştı; burada unutulmuş.

                         Yerlerine ÜRÜNÜN DOĞRU OLAN ÖZELLİKLERİ kondu. Bunlar
                         envanter büyüdükçe değişmez, küçükken de utandırmaz —
                         ve satıcıya asıl sorduğu soruyu yanıtlar: "bana neye
                         mal olacak?" --}}
                    <div class="grid grid-cols-2 gap-3">
                        <div class="rounded-2xl bg-white/[.07] p-4"><div class="text-2xl font-extrabold text-emerald-300">%0</div><div class="mt-0.5 text-xs font-semibold text-white/60">komisyon</div></div>
                        <div class="rounded-2xl bg-white/[.07] p-4"><div class="text-2xl font-extrabold">Ücretsiz</div><div class="mt-0.5 text-xs font-semibold text-white/60">ilan ve üyelik</div></div>
                        <div class="rounded-2xl bg-white/[.07] p-4"><div class="text-2xl font-extrabold">Doğrudan</div><div class="mt-0.5 text-xs font-semibold text-white/60">ödeme sana gelir</div></div>
                        <div class="rounded-2xl bg-white/[.07] p-4"><div class="text-2xl font-extrabold">Türkçe</div><div class="mt-0.5 text-xs font-semibold text-white/60">baştan sona</div></div>
                    </div>
                </div>
            </div>
        </section>
    @endif
