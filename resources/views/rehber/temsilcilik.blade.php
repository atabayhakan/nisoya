{{--
    Rehber — temsilcilik sayfası (/de/koeln). Temsilcilik künyesi +
    yayındaki işlem rehberlerinin listesi.
--}}
<x-layouts.app
    :title="$temsilcilik->ad.' İşlem Rehberi — '.setting('genel.site_adi')"
    :description="$temsilcilik->ad.' için vekaletname, pasaport, askerlik gibi işlemlerin evrak listeleri, süreleri ve resmî kaynakları.'"
>
    <div class="mx-auto max-w-6xl px-4 py-8 sm:py-10">
        <nav class="text-sm text-stone-500 dark:text-stone-400" aria-label="breadcrumb">
            <a href="{{ route('home') }}" class="hover:underline">Ana sayfa</a>
            <span aria-hidden="true"> / </span>
            <a href="{{ route('rehber.ulke', strtolower($country->code)) }}" class="hover:underline">{{ $country->name_tr }} Rehberi</a>
            <span aria-hidden="true"> / </span>
            <span class="text-stone-700 dark:text-stone-200">{{ $temsilcilik->sehir }}</span>
        </nav>

        <x-rehber.ulke-secici :aktif="$country" class="mt-3" />

        <p class="mt-4 text-xs font-medium uppercase tracking-wide text-emerald-700 dark:text-emerald-400">{{ $temsilcilik->turEtiketi() }}</p>
        <h1 class="mt-1 text-3xl font-bold text-stone-900 dark:text-stone-50">{{ $temsilcilik->ad }}</h1>

        <div class="mt-4 space-y-1 text-sm text-stone-600 dark:text-stone-300">
            @if ($temsilcilik->adres)
                <p>{{ $temsilcilik->adres }}</p>
            @endif
            @if ($temsilcilik->resmi_url)
                <p>
                    <a href="{{ $temsilcilik->resmi_url }}" target="_blank" rel="noopener nofollow"
                        class="font-medium text-emerald-700 hover:underline dark:text-emerald-400">
                        Resmî site ↗
                    </a>
                </p>
            @endif
        </div>

        {{-- HARİTA DÜĞMELERİ — yalnız koordinat varsa basılır (bkz.
             Temsilcilik::haritaBaglantilari() docblock'u). Boş bir "haritada
             aç" sözü yerine düğme hiç görünmez. --}}
        @php($haritalar = $temsilcilik->haritaBaglantilari())
        @if ($haritalar->isNotEmpty())
            <div class="mt-4 flex flex-wrap gap-2">
                @foreach ($haritalar as $harita)
                    <a href="{{ $harita['url'] }}" target="_blank" rel="noopener nofollow"
                        class="inline-flex items-center gap-1.5 rounded-full border border-stone-300 bg-white px-3.5 py-1.5 text-sm font-medium text-stone-700 transition hover:border-emerald-300 hover:text-emerald-700 dark:border-stone-600 dark:bg-stone-900 dark:text-stone-200 dark:hover:border-emerald-700 dark:hover:text-emerald-400">
                        <x-heroicon-o-map-pin class="h-4 w-4 shrink-0" />
                        {{ $harita['ad'] }}'ta aç
                    </a>
                @endforeach
            </div>
        @endif

        {{-- ZİYARET ÖNCESİ — ülkeden/temsilcilikten BAĞIMSIZ, genel ve
             doğrulanmış tavsiye (docs/plans/2026-08-25-…). Belirli bir
             noter/fotoğrafçı/print noktası İSİMLENDİRİLMİYOR — bu depoda
             hiçbir temsilciliğin çevresi tek tek doğrulanmadı, isim vermek
             uydurma olurdu. Genel pratikler ise her yerde gerçek. --}}
        <div class="mt-6 rounded-2xl border border-stone-200 bg-stone-50 p-5 dark:border-stone-700 dark:bg-stone-800/50">
            <h2 class="text-sm font-semibold text-stone-800 dark:text-stone-100">Ziyaret öncesi</h2>
            <ul class="mt-2.5 space-y-1.5 text-sm text-stone-600 dark:text-stone-300">
                <li>• Yabancı dilde düzenlenmiş evrakların çoğu için <strong>yeminli tercüme</strong> istenir — bulunduğun şehirdeki bir yeminli tercümanı önceden araştır.</li>
                <li>• Vekaletname/pasaport gibi işlemler <strong>vesikalık/biyometrik fotoğraf</strong> ister; standartlar ülkeye göre değişir, gitmeden önce evrak listesindeki ölçüleri kontrol et.</li>
                <li>• Bazı işlemler evrakların <strong>fotokopisini veya ıslak imzalı çıktısını</strong> ister — yanına yedek bulundurmak randevunun aynı gün bitmesini sağlar.</li>
                <li>• Vekaletname gibi işlemler yerel bir <strong>noterden</strong> onay gerektirebilir — temsilciliğin evrak listesi "noter onaylı" diyorsa bunu önceden hallet.</li>
            </ul>
        </div>

        <h2 class="mt-10 text-xl font-semibold text-stone-900 dark:text-stone-50">İşlem rehberleri</h2>

        @if ($islemler->isEmpty() && $temsilcilik->yonlendirme_notu)
            {{-- YÖNLENDİRME — "hazırlanıyor" DEĞİL.

                 Bu temsilcilik işlem bilgisini kendi sitesinde yayınlamıyor
                 (Bişkek'te ölçüldü: bilgi notu indeksi tamamen boş). Ziyaretçiye
                 "hazırlanıyor" demek onu beklemeye iter; oysa yapması gereken
                 merkezî kaynağa gitmek. Bekleme ile yönlendirme farklı şeyler. --}}
            <div class="mt-4 rounded-2xl border border-amber-200 bg-amber-50 p-6 dark:border-amber-800 dark:bg-amber-950/30">
                <p class="leading-relaxed text-stone-700 dark:text-stone-200">{{ $temsilcilik->yonlendirme_notu }}</p>
                <a href="https://www.konsolosluk.gov.tr" target="_blank" rel="noopener nofollow"
                    class="mt-4 inline-flex items-center gap-2 rounded-xl bg-emerald-700 px-5 py-2.5 font-medium text-white transition hover:bg-emerald-800">
                    Konsolosluk işlemleri portalını aç ↗
                </a>
                @if ($temsilcilik->resmi_url)
                    <p class="mt-3 text-sm">
                        <a href="{{ $temsilcilik->resmi_url }}" target="_blank" rel="noopener nofollow"
                            class="font-medium text-emerald-700 hover:underline dark:text-emerald-400">
                            {{ $temsilcilik->ad }} resmî sitesi ↗
                        </a>
                        <span class="text-stone-500 dark:text-stone-400">— iletişim ve duyurular</span>
                    </p>
                @endif
            </div>
        @elseif ($islemler->isEmpty())
            <div class="mt-4 rounded-2xl border border-stone-200 bg-stone-50 p-6 dark:border-stone-700 dark:bg-stone-800/50">
                <p class="text-stone-600 dark:text-stone-300">Bu temsilcilik için işlem rehberleri hazırlanıyor.</p>
            </div>
        @else
            <ul class="mt-4 divide-y divide-stone-200 overflow-hidden rounded-2xl border border-stone-200 bg-white dark:divide-stone-700 dark:border-stone-700 dark:bg-stone-800/50">
                @foreach ($islemler as $islem)
                    <li>
                        <a href="{{ route('rehber.islem', [strtolower($country->code), $temsilcilik->slug, $islem->islemTuru->slug]) }}"
                            class="group flex items-center justify-between gap-4 px-5 py-4 transition hover:bg-stone-50 dark:hover:bg-stone-800">
                            <span>
                                <span class="font-medium text-stone-900 group-hover:underline dark:text-stone-50">{{ $islem->islemTuru->ad }}</span>
                                @if ($islem->islemTuru->aciklama)
                                    <span class="mt-0.5 block text-sm text-stone-500 dark:text-stone-400">{{ $islem->islemTuru->aciklama }}</span>
                                @endif
                            </span>
                            <span class="shrink-0 text-stone-600 dark:text-stone-400" aria-hidden="true">→</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        @endif

        <p class="mt-10 rounded-xl bg-stone-100 p-4 text-xs leading-relaxed text-stone-500 dark:bg-stone-800 dark:text-stone-400">
            Bu sayfalar bilgilendirme amaçlıdır; evrak listeleri, ücretler ve süreler değişebilir.
            İşleme gitmeden önce lütfen temsilciliğin resmî sayfasından teyit edin.
        </p>
    </div>

    <x-json-ld type="BreadcrumbList" :data="[
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Ana sayfa', 'item' => route('home')],
            ['@type' => 'ListItem', 'position' => 2, 'name' => $country->name_tr.' Rehberi', 'item' => route('rehber.ulke', strtolower($country->code))],
            ['@type' => 'ListItem', 'position' => 3, 'name' => $temsilcilik->ad, 'item' => route('rehber.temsilcilik', [strtolower($country->code), $temsilcilik->slug])],
        ],
    ]" />
</x-layouts.app>
