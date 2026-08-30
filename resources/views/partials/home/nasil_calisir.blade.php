    @if (\App\Support\HomeSections::visible('nasil_calisir'))
    {{-- Nasıl çalışır (Konteyner İçinde / Modern 3 Adım Kartları) --}}
    <section class="mx-auto max-w-6xl px-4 py-10 sm:py-14" x-data x-reveal>
        <div class="text-center">
            <span class="text-xs font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-400">Kolay & Güvenli</span>
            <h2 class="mt-1 text-2xl font-bold text-stone-900 md:text-3xl dark:text-stone-50">{{ setting('home.nasil_baslik', 'Nasıl Çalışır?') }}</h2>
        </div>
        <div class="mt-8 grid gap-4 sm:gap-6 md:grid-cols-3">
            @php
                $adimlar = [
                    ['no' => '01', 'ikon' => 'user-plus', 'baslik' => setting('home.adim1_baslik', 'Ücretsiz Kayıt Ol'), 'metin' => setting('home.adim1_metin', 'Birkaç dakikada hesabını oluştur, bulunduğun ülke ve şehri seç.')],
                    ['no' => '02', 'ikon' => 'megaphone', 'baslik' => setting('home.adim2_baslik', 'İlanını Ver veya Ara'), 'metin' => setting('home.adim2_metin', 'Yeteneğini/hizmetini ilan et ya da ihtiyacın olan hizmeti ara.')],
                    ['no' => '03', 'ikon' => 'chat-bubble-left-right', 'baslik' => setting('home.adim3_baslik', 'Mesajlaş, Anlaş'), 'metin' => setting('home.adim3_metin', 'Karşı tarafla doğrudan mesajlaş, güvenle anlaş. Ödeme aranızda.')],
                ];
            @endphp
            @foreach ($adimlar as $a)
                <div class="group relative rounded-3xl border border-stone-200/90 bg-white p-6 sm:p-8 shadow-sm transition hover:-translate-y-1 hover:border-emerald-300 hover:shadow-md dark:border-stone-800 dark:bg-stone-900 dark:hover:border-emerald-700">
                    <div class="flex items-center justify-between">
                        <div class="grid h-12 w-12 place-items-center rounded-2xl bg-emerald-50 text-emerald-700 transition group-hover:bg-emerald-700 group-hover:text-white dark:bg-emerald-950/60 dark:text-emerald-400 dark:group-hover:bg-emerald-500 dark:group-hover:text-stone-950">
                            <x-dynamic-component :component="'heroicon-o-'.$a['ikon']" class="h-6 w-6" />
                        </div>
                        <span class="text-2xl font-bold text-stone-200 transition group-hover:text-emerald-500/40 dark:text-stone-800 dark:group-hover:text-emerald-400/40">{{ $a['no'] }}</span>
                    </div>
                    <h3 class="mt-5 text-lg font-bold text-stone-900 dark:text-stone-100">{{ $a['baslik'] }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-stone-600 dark:text-stone-300">{{ $a['metin'] }}</p>
                </div>
            @endforeach
        </div>
    </section>
    @endif
