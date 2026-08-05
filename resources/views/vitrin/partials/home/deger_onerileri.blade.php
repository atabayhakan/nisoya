    {{-- Değer önerileri — bento: admin panelden yönetilen vurgu kartları
         (home_highlights) + sabit güven kartı + gerçek istatistik kartı.
         Klasik ana sayfayla AYNI veriyi (bigHighlights/smallHighlights) ve
         aynı HomeSections kapısını kullanır; backend'e dokunulmaz. --}}
    @if (\App\Support\HomeSections::visible('deger_onerileri'))
        <section class="mx-auto max-w-6xl px-4 pt-14" x-data x-reveal>
            <div class="grid gap-3.5 lg:grid-cols-4 lg:grid-rows-2">
                <x-vitrin.vurgu-karti
                    :highlights="$bigHighlights"
                    :buyuk="true"
                    yedek-ikon="language"
                    :yedek-baslik="setting('home.deger1_baslik')"
                    :yedek-metin="setting('home.deger1_metin')"
                />

                <div class="rounded-[22px] border border-stone-200/60 bg-white p-5 shadow-brand lg:col-span-2 dark:border-stone-800 dark:bg-stone-900">
                    <span class="grid h-9 w-9 place-items-center rounded-xl bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400">
                        <x-heroicon-o-shield-check class="h-5 w-5" />
                    </span>
                    <div class="mt-3 text-base font-extrabold text-stone-800 dark:text-stone-100">{{ setting('home.deger2_baslik') }}</div>
                    <p class="mt-1 text-sm font-medium text-stone-500 dark:text-stone-400">{{ setting('home.deger2_metin') }}</p>
                </div>

                <x-vitrin.vurgu-karti
                    :highlights="$smallHighlights"
                    yedek-ikon="gift"
                    :yedek-baslik="setting('home.deger3_baslik')"
                    :yedek-metin="setting('home.deger3_metin')"
                />

                <div class="rounded-[22px] border border-stone-200/60 bg-white p-5 shadow-brand dark:border-stone-800 dark:bg-stone-900">
                    <span class="grid h-9 w-9 place-items-center rounded-xl bg-[#e7f7f1] text-[#0f9d76] dark:bg-teal-950/60 dark:text-teal-300">
                        <x-heroicon-o-globe-alt class="h-5 w-5" />
                    </span>
                    {{-- KATALOG SAYISI DEĞİL, SÖZ (2026-08-05).

                         Burada "25 ülke · 50 şehir · 97 kategoride hizmet ve
                         ürün" yazıyordu. Üçü de sahte değildi ama üçü de
                         yanıltıyordu: 'şehir' CitySeeder'ın ülke başına
                         tohumladığı sayı (ilanı olan şehir DEĞİL), 'kategori'
                         katalog boyutu, 'ülke' ise ilan olan ülke değil AÇIK
                         olan ülke. Aynı yanıltıcı üçlü hero'nun kanıt
                         satırından kaldırılmıştı; bu kartta unutulmuştu.

                         Kartın işi ("nerede olursan ol") sayıya değil vaade
                         dayanıyor — envanter büyüdükçe de küçükken de doğru. --}}
                    <div class="mt-3 text-base font-extrabold text-stone-800 dark:text-stone-100">Nerede olursan ol, Türkçe</div>
                    <p class="mt-1 text-sm font-medium text-stone-500 dark:text-stone-400">Ülkeni seç, şehrindeki Türkçe konuşan kişiye ulaş.</p>
                </div>
            </div>
        </section>
    @endif
