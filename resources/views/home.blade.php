<x-layouts.app>
    {{-- Hero --}}
    <section class="relative overflow-hidden bg-gradient-to-b from-emerald-50 to-stone-50 dark:from-emerald-950/30 dark:to-stone-950">
        {{-- Soyut arka plan dekorasyonu (saf CSS, görsel dosyası yok) --}}
        <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
            <div class="absolute -left-24 -top-24 h-72 w-72 rounded-full bg-emerald-300/30 blur-3xl dark:bg-emerald-700/20"></div>
            <div class="absolute -right-16 top-1/4 h-80 w-80 rounded-full bg-amber-200/30 blur-3xl dark:bg-amber-900/10"></div>
            <div class="absolute -bottom-16 left-1/3 h-64 w-64 rounded-full bg-emerald-200/40 blur-3xl dark:bg-emerald-800/10"></div>
        </div>

        <div class="relative z-10 mx-auto max-w-6xl px-4 py-16 sm:py-24">
            <div class="mx-auto max-w-3xl text-center">
                <span class="inline-flex items-center gap-2 rounded-full bg-emerald-100 px-3 py-1 text-xs font-semibold text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">
                    {{ setting('home.hero_badge') }}
                </span>
                {{-- "2. Tasarım" modunda (bkz. /yonetim Tasarım Modu) başlık Instrument
                     Serif italik ile — 2027 vizyon pilotunun tek somut tipografi izi. --}}
                {{-- font-bold (700), font-extrabold (800) DEĞİL: Instrument Sans'ın
                     800'ü yüklenmiyor, tarayıcı onu sahte kalın olarak çiziyordu —
                     sitenin en büyük yazısında en görünür yerde. Gerçek 700, sahte
                     800'den daima daha iyi görünür. Tek bir başlık için ayrı bir
                     font dosyası indirmek de ziyaret başına ödenecek bir bedeldi.
                     Ayrıca elle yazılan dar harf aralığı kaldırıldı: 4xl–6xl basamaklarının
                     harf aralığı artık ölçeğin kendisinde tanımlı (tipografi.css),
                     elle üstüne yazmak o reçeteyi geçersiz kılıyordu. --}}
                <h1 class="mt-5 text-4xl text-stone-900 sm:text-5xl md:text-6xl dark:text-stone-50 {{ \App\Support\Tema::tasarimModu() === 'yeni' ? 'font-serif italic font-normal' : 'font-bold' }}">
                    {{ setting('home.hero_satir1') }}<br>
                    <span class="text-emerald-600 dark:text-emerald-400">{{ setting('home.hero_vurgu') }}</span> {{ setting('home.hero_satir2') }}
                </h1>
                <p class="mx-auto mt-5 max-w-2xl text-lg text-stone-600 dark:text-stone-300">
                    {{ setting('home.hero_aciklama') }}
                </p>

                {{-- Arama kutusu --}}
                <form action="{{ url('/ilanlar') }}" method="GET" class="mx-auto mt-8 flex max-w-2xl flex-col gap-2 rounded-2xl bg-white p-2 shadow-lg ring-1 ring-stone-200 sm:flex-row dark:bg-stone-900 dark:ring-stone-800">
                    <input type="text" name="q" placeholder="{{ setting('home.arama_placeholder') }}"
                           class="flex-1 rounded-xl border-0 bg-transparent px-4 py-3 text-stone-800 placeholder-stone-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:text-stone-100 dark:placeholder-stone-500">
                    <select name="ulke" class="rounded-xl border-0 bg-stone-50 px-4 py-3 text-stone-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:bg-stone-800 dark:text-stone-200">
                        <option value="">Tüm ülkeler</option>
                        @foreach ($countries as $country)
                            <option value="{{ $country->code }}">{{ $country->emoji }} {{ $country->name_tr }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="rounded-xl bg-emerald-600 px-6 py-3 font-semibold text-white transition hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-400 dark:text-stone-900">
                        Ara
                    </button>
                </form>
                <p class="mt-3 text-sm text-stone-400 dark:text-stone-500">{{ setting('home.populer_metin') }}</p>
            </div>
        </div>
    </section>


    {{-- Bölümler artık SIRALANABİLİR (Faz 2 · G5). Sıra panelden yönetilir
         (Anasayfa Bölümleri sayfası); her bölüm kendi @if görünürlük kapısını
         partial'ının içinde taşır, yani mantık yer değiştirmedi.
         Sıralanamayan bloklar bir SIRA İNDEKSİNE çapalanır (bkz. HomeSections
         ::CAPALAR) — varsayılan sırada sayfa bugünküyle birebir aynıdır. --}}
    @foreach (\App\Support\HomeSections::sirali('klasik') as $sira => $bolum)
        @include('partials.home.'.$bolum)

        @if (\App\Support\HomeSections::capa('klasik', 'nabiz') === $sira)
            @include('partials.home.capa-nabiz')
        @endif
        @if (\App\Support\HomeSections::capa('klasik', 'zone_orta') === $sira)
            @include('partials.home.capa-zone-orta')
        @endif
    @endforeach

    {{-- Alan: sayfa sonu (reklam/duyuru) --}}
    <div class="mx-auto max-w-6xl px-4 pb-6">
        <x-zone zone-key="anasayfa_alt" />
    </div>
</x-layouts.app>
