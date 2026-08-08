<x-layouts.app>
    {{-- HERO ARKA PLAN MEDYASI — KABLO BAĞLANDI (2026-08-08).

         2026-08-06'da Hero Yöneticisi'nin METİN anahtarları buraya bağlanmıştı
         (aşağıdaki not). Ama MEDYA anahtarları bağlanmadan kaldı: sahip panelden
         arka plan görseli yükleyip kırpabiliyor, karartma ayarlayabiliyor,
         9 noktalı odak seçebiliyordu — ve canlıda hiçbir şey değişmiyordu.
         `App\Support\Hero` 16 metot sunuyor, bu dosya 4'ünü kullanıyordu.

         Yani PR #119'un düzelttiği hatanın yarım kalmış hâliydi: ekranın bir
         yarısı bağlıydı, öbür yarısı hâlâ sessizce hiçbir şey yapmıyordu.

         SÖZLEŞME VİTRİN İLE BİREBİR AYNI (components/vitrin/hero.blade.php):
         aynı `Hero` metotları, aynı `<picture>` + `<video>` işaretlemesi, aynı
         karartma katmanı. İki temanın medya davranışı ayrışmasın — bu dosyalar
         zaten bağımsız yeniden yazım, bir de medya sözleşmesi ayrışırsa
         panelin ne yapacağı temaya göre değişir.

         TEK BİLİNÇLİ FARK — `duzen()` OKUNMUYOR: Vitrin metni medyanın üstüne
         yalnız "sahne" düzeninde koyar, o yüzden orada `koyu = sahne && medya`.
         Klasik hero'nun tek bir düzeni var ve metin her zaman ortada, yani
         medya varsa metin HER ZAMAN onun üstünde. Burada `koyu = medya`.

         GERİYE DÖNÜK UYUM: medya seçilmemişken (`arkaplan_tipi = yok`, varsayılan)
         çıktı eskisiyle BİREBİR aynı — aynı degrade, aynı üç bulanık daire. --}}
    @php
        $hero = \App\Support\Hero::class;
        $heroTip = $hero::arkaplanTipi();
        $heroGorsel = $heroTip === 'gorsel' ? $hero::arkaplanGorseli() : null;
        $heroGorselMobil = $heroTip === 'gorsel' ? $hero::arkaplanGorseli(true) : null;
        $heroVideo = $heroTip === 'video' ? $hero::videoUrl() : null;
        $heroMedya = $heroGorsel !== null || $heroVideo !== null;
        $heroOverlay = $hero::overlay();
    @endphp

    {{-- Hero --}}
    <section class="relative overflow-hidden {{ $heroMedya ? 'bg-stone-900' : 'bg-gradient-to-b from-emerald-50 to-stone-50 dark:from-emerald-950/30 dark:to-stone-950' }}">
        @if ($heroMedya)
            {{-- Arka plan medyası (Hero Yöneticisi). Dekoratif: alt="" +
                 aria-hidden — ekran okuyucuya anlatacak bir şeyi yok. --}}
            <div class="absolute inset-0" aria-hidden="true">
                @if ($heroGorsel)
                    <picture>
                        @if ($heroGorselMobil && $heroGorselMobil !== $heroGorsel)
                            <source media="(max-width: 639px)" srcset="{{ $heroGorselMobil }}">
                        @endif
                        {{-- fetchpriority=high: bu görsel sayfanın LCP adayı. --}}
                        <img src="{{ $heroGorsel }}" alt="" width="2400" height="1200" fetchpriority="high"
                             class="h-full w-full object-cover" style="object-position: {{ $hero::odakCss() }}">
                    </picture>
                @else
                    <video class="h-full w-full object-cover" autoplay muted loop playsinline preload="metadata">
                        <source src="{{ $heroVideo }}" type="video/mp4">
                    </video>
                @endif
                @if ($heroOverlay > 0)
                    {{-- Karartma metnin okunabilirliği için. Yüzdesi artık ELLE
                         GİRİLMİYOR: HeroKontrast görselin metin altında kalan
                         piksellerini örnekleyip WCAG eşiğini geçen EN DÜŞÜK
                         değeri yazıyor (masaüstü ve mobil ayrı ölçülür). --}}
                    <div class="absolute inset-0 bg-stone-950" style="opacity: {{ $heroOverlay / 100 }}"></div>
                @endif

                @if ($hero::metinPaneli())
                    {{-- OKUNABİLİRLİK PANELİ — karartma üst sınıra dayandığı
                         hâlde küçük metin eşiği geçemediğinde açılır. Görselin
                         tamamını daha da karartmak yerine yalnız metnin arkasını
                         koyultur; seçilen kare görünür kalır. --}}
                    {{-- YÖN CİHAZA GÖRE DEĞİŞİR — ölçümle belirlendi (2026-08-09).

                         Tek bir yatay gradyan ikisine birden konmuştu ve mobilde
                         İŞE YARAMIYORDU: masaüstünde metin genişliğin %77'sinde
                         bitiyor, mobilde %96'sına uzanıyor. `to-r` gradyanı
                         %100'de tamamen söndüğü için mobilde metnin sağ ucu
                         çıplak zeminde kalıyordu (ölçüldü: alt metin 3.62,
                         rozet 4.41 — eşik 4.5).

                         Mobilde metin DİKEYDE üst yarıda duruyor (rozet 0.10,
                         alt metnin dibi 0.51 — ölçüldü), o yüzden yukarıdan
                         aşağı sönen gradyan tam oturuyor. Masaüstünde metin
                         solda toplandığı için yatay gradyan doğru kalıyor. --}}
                    <div class="absolute inset-0 bg-gradient-to-b from-stone-950/85 via-stone-950/60 to-transparent sm:bg-gradient-to-r sm:from-stone-950/80 sm:via-stone-950/45 sm:to-transparent"></div>
                @endif
            </div>
        @else
            {{-- Soyut arka plan dekorasyonu (saf CSS, görsel dosyası yok) --}}
            <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
                <div class="absolute -left-24 -top-24 h-72 w-72 rounded-full bg-emerald-300/30 blur-3xl dark:bg-emerald-700/20"></div>
                <div class="absolute -right-16 top-1/4 h-80 w-80 rounded-full bg-amber-200/30 blur-3xl dark:bg-amber-900/10"></div>
                <div class="absolute -bottom-16 left-1/3 h-64 w-64 rounded-full bg-emerald-200/40 blur-3xl dark:bg-emerald-800/10"></div>
            </div>
        @endif

        <div class="relative z-10 mx-auto max-w-6xl px-4 py-16 sm:py-24">
            <div class="mx-auto max-w-3xl text-center">
                {{-- HERO METİNLERİ App\Support\Hero ÜZERİNDEN OKUNUR (2026-08-06).

                     Önceden burada `setting('home.hero_badge')` yazıyordu, yani
                     klasik ana sayfa ayarları DOĞRUDAN okuyordu. Hero Yöneticisi
                     ise `hero.*` anahtarlarına yazıyor ve onları yalnızca
                     App\Support\Hero (dolayısıyla yalnızca VİTRİN hero'su)
                     okuyordu.

                     Sonuç: canlı site klasik temada olduğu için Hero
                     Yöneticisi'ne yazılan hiçbir şey ana sayfaya çıkmıyordu.
                     Sahip "burada değişiklik yapıyorum ana sayfada değişmiyor"
                     dedi ve bire bir haklıydı — ekran vardı, bağlantısı yoktu.

                     Hero::rozet() zaten `hero.rozet ?: home.hero_badge` diye
                     okuyor. Yani `hero.*` boşken çıktı ESKİSİYLE BİREBİR AYNI;
                     doluyken yönetici nihayet işe yarıyor. --}}
                {{-- METİN RENKLERİ MEDYAYA GÖRE DÖNER. Karartılmış bir görselin
                     üstünde `text-stone-900` okunmaz; medya varken açık renge
                     geçilir. Medya yokken sınıflar eskisiyle birebir aynı. --}}
                <span class="inline-flex items-center gap-2 rounded-full px-3 py-1 text-xs font-semibold {{ $heroMedya ? 'bg-white/15 text-white' : 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300' }}">
                    {{ \App\Support\Hero::rozet() }}
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
                <h1 class="mt-5 text-4xl sm:text-5xl md:text-6xl {{ $heroMedya ? 'text-white' : 'text-stone-900 dark:text-stone-50' }} {{ \App\Support\Tema::tasarimModu() === 'yeni' ? 'font-serif italic font-normal' : 'font-bold' }}">
                    {{ \App\Support\Hero::baslik() }}<br>
                    <span class="{{ $heroMedya ? 'text-emerald-300' : 'text-emerald-700 dark:text-emerald-400' }}">{{ \App\Support\Hero::vurgu() }}</span> {{ setting('home.hero_satir2') }}
                </h1>
                <p class="mx-auto mt-5 max-w-2xl text-lg {{ $heroMedya ? 'text-white/80' : 'text-stone-600 dark:text-stone-300' }}">
                    {{ \App\Support\Hero::altBaslik() }}
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
                    <button type="submit" class="rounded-xl bg-emerald-700 px-6 py-3 font-semibold text-white transition hover:bg-emerald-800 dark:bg-emerald-500 dark:hover:bg-emerald-400 dark:text-stone-900">
                        Ara
                    </button>
                </form>
                <p class="mt-3 text-sm {{ $heroMedya ? 'text-white/70' : 'text-stone-600 dark:text-stone-400' }}">{{ setting('home.populer_metin') }}</p>
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
