@props(['countries', 'stats', 'latestListings', 'activityFeed', 'pulseCountries' => [], 'heroCips' => null, 'ziyaretciUlke' => null])

{{-- VİTRİN HERO (P1 → P3) — artık Hero Yöneticisi'nden yönetilir
     (App\Support\Hero / Filament: İçerik & Tasarım → Hero Yöneticisi):
     düzen varyantı (bento|sahne), metinler, butonlar, arka plan
     (görsel/video + karartma + odak) ve blokların sırası/açıklığı.
     Boş bırakılan metinler klasik `home.hero_*` değerlerine düşer, yani
     panel hiç açılmadan da doğru çalışır.

     Tüm sayılar GERÇEK veridir (stats/aktivite/ilan) — prototipteki temsili
     rakamlar bilinçli olarak KULLANILMAZ. Kapalı bloklar DOM'a hiç basılmaz. --}}
@php
    $hero = \App\Support\Hero::class;
    $duzen = $hero::duzen();
    $bloklar = $hero::aktifBloklar();
    $arkaplanTipi = $hero::arkaplanTipi();
    $gorsel = $arkaplanTipi === 'gorsel' ? $hero::arkaplanGorseli() : null;
    $gorselMobil = $arkaplanTipi === 'gorsel' ? $hero::arkaplanGorseli(true) : null;
    $video = $arkaplanTipi === 'video' ? $hero::videoUrl() : null;
    $medyaVar = $gorsel !== null || $video !== null;
    // "Sahne" düzeni metni medyanın üstüne koyar → yalnız medya varken koyu zemin.
    $sahne = $duzen === 'sahne';
    $koyu = $sahne && $medyaVar;
    $overlay = $hero::overlay();
    $cta1 = $hero::cta(1);
    $cta2 = $hero::cta(2);
    $oneCikanIlan = $latestListings->first();

    // "Nerede ne var" — sahte haftalık çubukların yerine GERÇEK ülke hareketi.
    // Veri zaten HomeController'da hesaplanıp view'a geliyor (NabizService::
    // countryActivity, 5 dk cache); burada yeni sorgu YOK.
    // countryActivity() ilanı olmayan ülkeleri de count=0 ile döndürür —
    // önce sıfırlar elenir, sonra azalan sıralanır. Aksi hâlde ekranda
    // "0 ilan" yazan satırlar çıkardı ki bu sahte veriden beter olurdu.
    $ulkeHareketi = collect($pulseCountries)
        ->filter(fn ($u) => ($u['count'] ?? 0) > 0)
        ->sortByDesc('count')
        ->take(4)
        ->values();
    $enYuksek = (int) ($ulkeHareketi->max('count') ?: 0);

    // "Canlı akış" başlığı ancak gerçekten taze içerik varken dürüsttür.
    $akisTaze = collect($activityFeed)->contains(fn ($i) => $i['taze'] ?? false);
@endphp

<section class="relative overflow-hidden {{ $koyu ? 'bg-stone-900' : '' }}">
    @if ($medyaVar)
        {{-- Arka plan medyası (Hero Yöneticisi) --}}
        <div class="absolute inset-0" aria-hidden="true">
            @if ($gorsel)
                <picture>
                    @if ($gorselMobil && $gorselMobil !== $gorsel)
                        <source media="(max-width: 639px)" srcset="{{ $gorselMobil }}">
                    @endif
                    <img src="{{ $gorsel }}" alt="" width="2400" height="1200" fetchpriority="high"
                         class="h-full w-full object-cover" style="object-position: {{ $hero::odakCss() }}">
                </picture>
            @else
                <video class="h-full w-full object-cover" autoplay muted loop playsinline preload="metadata">
                    <source src="{{ $video }}" type="video/mp4">
                </video>
            @endif
            @if ($overlay > 0)
                <div class="absolute inset-0 bg-stone-950" style="opacity: {{ $overlay / 100 }}"></div>
            @endif
        </div>
    @else
        {{-- Dekoratif radial (saf CSS, görsel dosyası yok) --}}
        <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
            <div class="absolute -left-32 -top-40 h-[420px] w-[420px] rounded-full bg-[radial-gradient(circle,rgba(62,99,240,.13),transparent_68%)] dark:bg-[radial-gradient(circle,rgba(107,138,253,.12),transparent_68%)]"></div>
            <div class="absolute -right-24 top-1/3 h-80 w-80 rounded-full bg-[radial-gradient(circle,rgba(22,169,127,.08),transparent_70%)]"></div>
        </div>
    @endif

    {{-- Sol sütun 505px → 560px: arama kutusu ilk 3 saniyenin son adımı, ama
         505px'te ülke seçici ve "Ara" butonundan sonra girdiye ~170px kalıyordu
         (yaklaşık 11 karakter). Sağ bento zaten esnek kolonda, 55px onu
         bozmuyor. --}}
    <div class="relative z-10 mx-auto max-w-6xl px-4 py-12 lg:py-14 {{ $sahne ? '' : 'grid items-center gap-10 lg:grid-cols-[minmax(0,560px)_minmax(0,1fr)]' }}">
        <div class="{{ $sahne ? 'mx-auto max-w-2xl text-center' : '' }}">
            @if ($hero::rozet())
                <span class="inline-flex items-center gap-2 rounded-full border px-3.5 py-2 text-xs font-semibold shadow-sm {{ $koyu
                    ? 'border-white/25 bg-white/10 text-white'
                    : 'border-stone-200 bg-white text-emerald-700 dark:border-stone-800 dark:bg-stone-900 dark:text-emerald-400' }}">
                    <span class="relative inline-flex h-2 w-2" aria-hidden="true">
                        <span class="vitrin-pulse absolute inset-0 rounded-full bg-[#16a97f]"></span>
                        <span class="absolute inset-0 rounded-full bg-[#16a97f]"></span>
                    </span>
                    {{ $hero::rozet() }}
                </span>
            @endif

            <h1 class="mt-5 text-4xl font-extrabold tracking-[-0.032em] sm:text-5xl {{ $koyu ? 'text-white' : 'text-stone-800 dark:text-stone-50' }}" style="text-wrap: pretty">
                {{ $hero::baslik() }}
                @if ($hero::vurgu())
                    <br><span class="{{ $koyu ? 'text-emerald-300' : 'text-emerald-700 dark:text-emerald-400' }}">{{ $hero::vurgu() }}</span>
                @endif
            </h1>

            {{-- SLOGAN — artık H1 DEĞİL, bir basamak aşağıda.

                 H1 eskiden sloganı taşıyordu ("Tarif etmeye çalışma.") ve
                 ürünün NE OLDUĞU 250px'teki paragrafta bekliyordu. Ölçüm:
                 ziyaretçilerin %79'u tarıyor, %16'sı okuyor — yani sitenin ne
                 olduğunu anlatan tek cümle, okunmama olasılığı en yüksek
                 biçimde yazılmıştı. H1 artık tanımlıyor; slogan burada
                 duygusal çapa olarak kalıyor. Silinmedi, yeri değişti. --}}
            @if ($hero::altBaslik())
                <p class="mt-3 text-base font-medium leading-relaxed {{ $koyu ? 'mx-auto max-w-xl text-white/70' : 'max-w-md text-stone-500 dark:text-stone-400' }}" style="text-wrap: pretty">
                    {{ $hero::altBaslik() }}
                </p>
            @endif

            {{-- KAPSAM ÇİPLERİ — "bu ne?" sorusunun cevabı, ilk taramada.

                 Aşağıdaki "popüler" çiplerle KARIŞTIRILMAMALI: onlar
                 "bu kategoride GERÇEK ilan var" vaadidir ve demo süzgecinden
                 geçer (bugün yalnız 2 tane çıkıyor). Bunlar ise sitenin
                 KAPSAMINI anlatır — envanter vaadi değil.

                 Bu yüzden TIKLANAMAZLAR. Tıklanabilir olsalardı boş bir
                 kategori sayfasına götürür ve tutulmamış bir vaat olurlardı;
                 envanter büyüyünce bağlanabilirler. --}}
            <ul class="mt-4 flex flex-wrap gap-1.5 {{ $sahne ? 'justify-center' : '' }}" aria-label="Nisoya'da neler var">
                @foreach (['Nakliyeci', 'Hoca', 'Tamirci', 'Kuaför', 'Tercüman', 'İkinci el', 'İş ilanı', 'Ülke rehberi'] as $kapsam)
                    <li class="rounded-full px-2.5 py-1 text-xs font-medium {{ $koyu ? 'bg-white/15 text-white/90' : 'bg-stone-100 text-stone-600 dark:bg-stone-800 dark:text-stone-300' }}">{{ $kapsam }}</li>
                @endforeach
            </ul>

            @foreach ($bloklar as $blokAnahtari)
                <x-vitrin.hero-blok
                    :anahtar="$blokAnahtari"
                    :countries="$countries"
                    :stats="$stats"
                    :latest-listings="$latestListings"
                    :hero-cips="$heroCips"
                    :ziyaretci-ulke="$ziyaretciUlke"
                    :koyu="$koyu"
                />
            @endforeach

            @if ($cta1 || $cta2)
                <div class="mt-6 flex flex-wrap gap-3 {{ $sahne ? 'justify-center' : '' }}">
                    @if ($cta1)
                        <a href="{{ $cta1['url'] }}" class="inline-flex items-center gap-2 rounded-xl bg-emerald-700 px-5 py-3 text-sm font-bold text-white shadow-[0_12px_22px_-12px_rgba(62,99,240,1)] transition hover:brightness-95 dark:bg-emerald-500 dark:text-stone-900 dark:shadow-none">
                            {{ $cta1['etiket'] }}
                        </a>
                    @endif
                    @if ($cta2)
                        <a href="{{ $cta2['url'] }}" class="inline-flex items-center gap-2 rounded-xl border px-5 py-3 text-sm font-bold transition {{ $koyu
                            ? 'border-white/30 text-white hover:bg-white/10'
                            : 'border-stone-300 text-stone-800 hover:bg-stone-100 dark:border-stone-700 dark:text-stone-200 dark:hover:bg-stone-800' }}">
                            {{ $cta2['etiket'] }}
                        </a>
                    @endif
                </div>
            @endif
        </div>

        {{-- MOBİL YERELLİK ŞERİDİ — sayı DEĞİL, yer.

             Eskiden burada "🇩🇪 Almanya — 12 aktif ilan" yazıyordu ve iki ayrı
             sorunu vardı:

             1. Sayı DEMO DAHİLDİ (bkz. NabizService::countryActivity) —
                hemen üstündeki kanıt şeridi demo süzüyordu. İki öge 100 piksel
                arayla birbirinin tersini söylüyordu.
             2. Süzgeç eklenince sayı "3" olacaktı. Düşük sayı POZİTİF değil
                NEGATİF sosyal kanıttır: ziyaretçiye "burası boş" der.
                Araştırma bulgusu: sayı düşükken sayı gösterme, YER göster.

             Yerellik sinyalinin kendisi değerli (diaspora güveninde en güçlü
             ikinci sinyal, ana dilden sonra) — kaybolan yalnız rakam. --}}
        @if (! $sahne && $ulkeHareketi->isNotEmpty())
            <a href="{{ url('/ilanlar') }}?ulke={{ $ulkeHareketi->first()['code'] }}"
               class="mt-6 flex min-h-11 items-center gap-2 rounded-2xl border border-stone-200/70 bg-white px-3 py-2 text-sm shadow-brand lg:hidden dark:border-stone-800 dark:bg-stone-900">
                <span aria-hidden="true">{{ $ulkeHareketi->first()['emoji'] }}</span>
                <span class="min-w-0 flex-1 truncate text-stone-600 dark:text-stone-400">
                    <span class="font-semibold text-stone-700 dark:text-stone-200">{{ $ulkeHareketi->first()['name'] }}</span>'dasın
                </span>
                <span class="shrink-0 font-semibold text-emerald-700 dark:text-emerald-400">İlanlara bak →</span>
            </a>
        @endif

        {{-- Bento düzeninin sağ sütunu: yüzen canlı veri kartları (yalnız lg+;
             mobilde hero + arama ilk ekranda kalmalı). "Sahne" düzeninde
             sayfa tek kolon olduğu için hiç basılmaz. --}}
        @unless ($sahne)
            <div class="relative hidden min-h-[460px] lg:block" x-data x-reveal>
                <div class="absolute inset-0 rounded-[26px] border border-stone-200/70 bg-gradient-to-br from-emerald-100/60 via-stone-100 to-emerald-50/40 dark:border-stone-800 dark:from-emerald-950/40 dark:via-stone-900 dark:to-stone-950" aria-hidden="true"></div>

                <div class="relative grid grid-cols-2 gap-3.5 p-5">
                    {{-- (a) "Şu an nerede ilan var" — GERÇEK ülke hareketi.

                         Buradaki eski kart, başlığı "Topluluk her gün büyüyor",
                         alt etiketi "Son 7 gün · tüm kategoriler" ve "canlı"
                         rozetiyle bir VERİ İDDİASINDA bulunuyordu; çubuklar ise
                         elle yazılmış sabit sayılardı ([46,63,39,72,88,55,67]).
                         Güven üzerine kurulu bir pazaryerinin ilk ekranında
                         uydurma grafik olamaz — kart gerçek veriye bağlandı.
                         Veri yoksa kart DOM'a hiç basılmaz. --}}
                    @if ($ulkeHareketi->isNotEmpty())
                        <div class="col-span-2 rounded-[18px] border border-stone-200/60 bg-white p-4 shadow-brand dark:border-stone-800 dark:bg-stone-900">
                            <div class="flex items-start justify-between gap-3">
                                <div>
                                    <div class="text-sm font-bold text-stone-800 dark:text-stone-100">Şu an nerede ilan var</div>
                                    <div class="mt-0.5 text-xs font-medium text-stone-500 dark:text-stone-400">Aktif ilanlar · ülkeye göre</div>
                                </div>
                            </div>
                            <ul class="mt-3 space-y-1">
                                @foreach ($ulkeHareketi as $ulke)
                                    <li>
                                        <a href="{{ url('/ilanlar') }}?ulke={{ $ulke['code'] }}"
                                           class="flex min-h-11 items-center gap-3 rounded-xl px-2 transition hover:bg-stone-50 dark:hover:bg-stone-800">
                                            <span class="text-base" aria-hidden="true">{{ $ulke['emoji'] }}</span>
                                            <span class="min-w-0 flex-1 truncate text-sm font-semibold text-stone-700 dark:text-stone-200">{{ $ulke['name'] }}</span>
                                            <span class="h-1.5 w-20 shrink-0 overflow-hidden rounded-full bg-stone-100 dark:bg-stone-800" aria-hidden="true">
                                                <span class="block h-full rounded-full bg-gradient-to-r from-emerald-400 to-emerald-600"
                                                      style="width: {{ $enYuksek > 0 ? max(8, (int) round($ulke['count'] / $enYuksek * 100)) : 0 }}%"></span>
                                            </span>
                                            <span class="w-10 shrink-0 text-right text-sm font-extrabold text-stone-800 dark:text-stone-100">{{ $ulke['count'] }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- (b) Son eklenenler — 3 satır, STATİK.

                         Eskiden tek öğe dönüyordu (activityTicker): aynı 132px'te
                         tek bilgi, tek tıklanabilir hedef ve sürekli hareket.
                         Statik liste aynı yerde 3 kat bilgi ve 3 hedef verir,
                         JS gerektirmez, reduced-motion derdi yoktur.

                         Satır sırası da değişti: kalın satır artık İŞ (kategori),
                         gri satır kim/nerede/ne zaman. "Hakan yeni bir ilan
                         paylaştı" kim'i söylüyordu ama ne iş olduğunu söylemiyordu;
                         iş öne alınınca başlığın vaadi tekrarlanmış oluyor.

                         Başlık taze içerik yoksa "canlı" demez — nabız noktası da
                         yalnız o zaman yanar. --}}
                    @if (\App\Support\HomeSections::visible('canli_akis') && $activityFeed->isNotEmpty())
                        <div class="col-span-2 rounded-[18px] border border-stone-200/60 bg-white p-4 shadow-brand dark:border-stone-800 dark:bg-stone-900">
                            <div class="flex items-center gap-1.5 text-2xs font-bold uppercase tracking-wide text-emerald-700 dark:text-emerald-400">
                                @if ($akisTaze)
                                    <span class="relative inline-flex h-1.5 w-1.5" aria-hidden="true"><span class="vitrin-pulse absolute inset-0 rounded-full bg-emerald-500"></span><span class="absolute inset-0 rounded-full bg-emerald-500"></span></span>
                                    Canlı akış
                                @else
                                    Son eklenenler
                                @endif
                            </div>
                            <ul class="mt-2 space-y-0.5">
                                @foreach ($activityFeed->take(3) as $item)
                                    <li>
                                        <a href="{{ $item['href'] }}" class="flex min-h-11 flex-col justify-center rounded-xl px-2 transition hover:bg-stone-50 dark:hover:bg-stone-800">
                                            <span class="line-clamp-1 text-sm font-bold text-stone-800 dark:text-stone-100">{{ $item['categoryName'] }}</span>
                                            <span class="line-clamp-1 text-xs font-medium text-stone-500 dark:text-stone-400">
                                                {{ $item['firstName'] }} @if ($item['place'])· {{ $item['flag'] }} {{ $item['place'] }}@endif · {{ $item['timeAgo'] }}
                                            </span>
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endif

                    {{-- (c) Yüzen mini ilan kartı — gerçek son ilan --}}
                    @if ($oneCikanIlan)
                        {{-- Vitrindeki ilan hizmete DARALTILMADI: platform hizmet,
                             ürün, emlak, vasıta hepsini taşıyor ve vitrini tek tipe
                             indirmek envanteri gizlemek olurdu. Kapsam mesajını
                             artık gerçek kategorilerden kurulan çip satırı veriyor. --}}
                        <a href="{{ route('listings.show', [$oneCikanIlan, $oneCikanIlan->slug]) }}" class="vitrin-float col-span-2 block rounded-[18px] border border-stone-200/60 bg-white p-3 shadow-brand-lg transition hover:border-emerald-300 dark:border-stone-800 dark:bg-stone-900 dark:hover:border-emerald-700">
                            <div class="relative h-24 overflow-hidden rounded-xl bg-stone-100 dark:bg-stone-800">
                                @if ($oneCikanIlan->coverImage)
                                    <img src="{{ $oneCikanIlan->coverImage->enIyiUrl('thumb') }}"
                                         alt="{{ $oneCikanIlan->title }}" loading="lazy" decoding="async"
                                         class="h-full w-full object-cover" style="object-position: {{ $oneCikanIlan->coverImage->objectPosition() }}">
                                @else
                                    <div class="flex h-full items-center justify-center text-stone-300 dark:text-stone-400">
                                        <x-dynamic-component :component="'heroicon-o-'.\App\Support\CategoryIcon::heroicon($oneCikanIlan->category?->parent?->icon ?? $oneCikanIlan->category?->icon)" class="h-8 w-8" />
                                    </div>
                                @endif
                                @if ($oneCikanIlan->category)
                                    <span class="absolute left-2 top-2 rounded-full bg-white/95 px-2 py-1 text-2xs font-bold text-emerald-700 dark:bg-stone-900/95 dark:text-emerald-400">{{ $oneCikanIlan->category->name }}</span>
                                @endif
                            </div>
                            <div class="mt-2.5 line-clamp-1 text-sm font-bold text-stone-800 dark:text-stone-100">{{ $oneCikanIlan->title }}</div>
                            <div class="mt-0.5 text-xs font-medium text-stone-500 dark:text-stone-400">
                                @if ($oneCikanIlan->country){{ $oneCikanIlan->country->emoji }} {{ $oneCikanIlan->city ?: $oneCikanIlan->country->name_tr }}@endif
                            </div>
                            <div class="mt-1.5 text-base font-extrabold text-emerald-700 dark:text-emerald-400">
                                @if ($oneCikanIlan->price !== null)
                                    {{ number_format((float) $oneCikanIlan->price, 0) }} {{ $oneCikanIlan->currency }}<span class="text-2xs font-semibold text-stone-600">{{ $oneCikanIlan->price_unit->suffix() }}</span>
                                @else
                                    Görüşülür
                                @endif
                            </div>
                        </a>
                    @endif
                </div>
            </div>
        @endunless
    </div>
</section>
