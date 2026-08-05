@props(['anahtar', 'countries', 'stats', 'latestListings', 'koyu' => false, 'heroCips' => null, 'ziyaretciUlke' => null])

{{-- VİTRİN HERO BLOĞU (P3) — Hero Yöneticisi'ndeki blok listesinin tek tek
     karşılıkları. Kapalı bloklar buraya HİÇ gelmez (çağıran taraf
     Hero::aktifBloklar() ile döner) — CSS ile gizleme yok.
     $koyu: "sahne" düzeninde metinler koyu görsel üzerinde beyaz olur. --}}
@if ($anahtar === 'arama')
    {{-- Arama paneli — klasikle aynı form sözleşmesi (GET /ilanlar, q + ulke) --}}
    {{-- ARAMA KARTI — mobilde "uygulama" düzeni, masaüstünde tek satır.
         Mobilde üç yığılmış hücre (ara · ülke · büyük eylem) telefonda tek elle
         kullanılır; masaüstünde aynı işaretleme sm:'den itibaren tek satıra
         döner ve mevcut görünüm korunur. --}}
    <form action="{{ url('/ilanlar') }}" method="GET" class="mt-7 flex flex-col gap-1.5 rounded-3xl border border-stone-200/70 bg-white p-2 shadow-brand sm:flex-row sm:items-center sm:rounded-2xl dark:border-stone-800 dark:bg-stone-900 dark:shadow-none">
        <div class="flex min-w-0 flex-1 items-center gap-2 px-3">
            <x-heroicon-o-magnifying-glass class="h-4 w-4 shrink-0 text-stone-600" />
            <input type="text" name="q" placeholder="{{ setting('home.arama_placeholder') }}"
                   class="h-12 w-full min-w-0 border-0 bg-transparent p-0 text-base font-medium text-stone-800 placeholder-stone-400 focus:outline-none focus:ring-0 dark:text-stone-100 dark:placeholder-stone-500">
        </div>
        {{-- w-full sm:w-40 + shrink-0 ŞART: select'in doğal genişliği en uzun
             seçenekten gelir ("🇺🇸 Amerika Birleşik Devletleri") ve sol sütun
             sabit genişlikte olduğu için arama input'unu yiyordu — placeholder
             "Kim lazım? (ör." diye kırpılıyordu. En kötü hâli tam 1024px'te:
             orada hem lg: düzeni hem de dokunmatik 16px kuralı (app.css
             pointer:coarse) aynı anda devreye giriyor. --}}
        {{-- Ziyaretçinin bulunduğu ülke önceden seçili gelir (tespit
             edilebildiyse). Mobilde aramanın en sık yapılan elle düzeltmesi
             buydu: kutuyu aç, listeyi kaydır, ülkeni bul. Tespit yoksa
             "Tüm ülkeler"de kalır — uydurulmaz. --}}
        {{-- Mobilde kart hücresi görünümü: beyaz zemin + kenarlık + daha uzun
             dokunma alanı. Yerli <select> BİLİNÇLE korunuyor — özel bir açılır
             liste erişilebilirlik ve klavye davranışını sıfırdan yazmayı
             gerektirirdi; kazanç yalnız görseldi. Bayrak zaten seçenek
             metninde olduğu için kapalı hâlde de görünüyor. --}}
        <select name="ulke" aria-label="Ülke seç" class="h-14 w-full shrink-0 truncate rounded-2xl border border-stone-200 bg-white px-4 text-base font-semibold text-stone-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 sm:h-12 sm:w-36 sm:rounded-xl sm:border-0 sm:bg-stone-100 sm:px-3 sm:text-sm dark:border-stone-700 dark:bg-stone-900 dark:text-stone-200 sm:dark:bg-stone-800">
            <option value="">Tüm ülkeler</option>
            @foreach ($countries as $country)
                <option value="{{ $country->code }}" @selected($ziyaretciUlke?->code === $country->code)>{{ $country->emoji }} {{ $country->name_tr }}</option>
            @endforeach
        </select>
        {{-- Mobilde tam genişlik ve daha uzun (h-14): başparmakla ulaşılan
             birincil eylem. Masaüstünde eski ölçüsüne döner. --}}
        {{-- Degrade yalnız mobilde: tam genişlikte düz bir renk yassı duruyor,
             hafif bir geçiş düğmeye derinlik veriyor. Masaüstünde düğme küçük;
             orada degrade fark edilmeden karmaşa ekler. --}}
        <button type="submit" class="inline-flex h-14 w-full items-center justify-center gap-2 rounded-2xl bg-gradient-to-r from-emerald-800 to-emerald-600 px-6 text-base font-bold text-white shadow-[0_12px_22px_-12px_rgba(62,99,240,1)] transition hover:brightness-95 sm:h-12 sm:w-auto sm:rounded-xl sm:bg-emerald-700 sm:bg-none sm:text-sm sm:hover:bg-emerald-800 dark:from-emerald-500 dark:to-emerald-400 dark:text-stone-900 dark:shadow-none sm:dark:bg-emerald-500">
            <span class="sm:hidden">Hemen bul</span>
            <span class="hidden sm:inline">Ara</span>
            <x-heroicon-o-arrow-right class="h-4 w-4" />
        </button>
    </form>

    {{-- YAPISAL GÜVEN — tek satır, aramanın hemen altında.

         Nisoya'nın tek gerçek farklılaştırıcısı bu ve BEDAVA: kaçılan kanallar
         (Facebook Marketplace, WhatsApp vitrinleri) bunu asla söyleyemez.

         "Ücretsiz" bunun YERİNİ TUTMAZ — ücretsiz FİYAT der, "para geçmez"
         RİSK der. Göçmen hedefli dolandırıcılığın merkezinde para transferi
         var; ziyaretçinin taşıdığı korku fiyat değil, kandırılma korkusu.

         Ölçülmüş "zero-price effect": ücretsiz sunulan hizmet daha DÜŞÜK
         kaliteli algılanabiliyor. Sebebini söylemezsek ziyaretçi boşluğu
         "gizli bir tuzak var" diye doldurur.

         Ardından gelen bağlantı bilinçli olarak DÜRÜST OLUMSUZ taşıyor:
         para geçmediği için işlemi garanti de etmiyoruz. Bu bir zayıflık
         itirafı gibi görünür ama blemishing effect (JCR 2012) tersini
         söylüyor — olumlu tablonun ardına küçük ve dürüst bir olumsuz
         eklemek, aceleci okuyucuda olumlu izlenimi ARTIRIYOR. --}}
    <p class="mt-3 flex flex-wrap items-center gap-x-2 gap-y-1 text-xs {{ $koyu ? 'text-white/75' : 'text-stone-500 dark:text-stone-400' }}">
        <span class="font-semibold {{ $koyu ? 'text-white' : 'text-stone-700 dark:text-stone-200' }}">Nisoya'dan para geçmez.</span>
        <span>Komisyon yok, aracı yok.</span>
        <a href="{{ url('/guvenli-alisveris') }}" class="font-medium underline decoration-dotted underline-offset-2 hover:text-emerald-700 dark:hover:text-emerald-400">
            Dolandırılmamak için →
        </a>
    </p>
@endif

{{-- Çipler artık GERÇEK KATEGORİLERDEN kurulur ve `?kategori=` ile gider.

     Eskiden serbest metin etiketleri `?q=` ile gidiyordu; BrowseController:42
     bunu title/description LIKE'ına çeviriyor, kategoriye BAKMIYOR. Yani
     "taşınma" çipi "Nakliyat & Taşınma" kategorisindeki dolu ilanları
     getirmiyordu — ilk tıklamada boş sayfa dönüyordu ve ilk tıklamada boş
     sayfa güveni bitirir. Liste yalnız ilanı OLAN kategorilerden kurulduğu
     için artık boş sonuç yapısal olarak imkânsız.
     $heroCips yoksa (klasik tema / eski çağrı) blok hiç basılmaz. --}}
@if ($anahtar === 'populer_etiketler' && $heroCips !== null && $heroCips->isNotEmpty())
    <div class="mt-4 flex flex-wrap items-center gap-2 {{ $koyu ? 'justify-center' : '' }}">
        <span class="text-xs font-semibold text-stone-600 dark:text-stone-400">Popüler:</span>
        @foreach ($heroCips as $kategori)
            <a href="{{ url('/ilanlar') }}?kategori={{ $kategori->slug }}"
               class="inline-flex min-h-11 items-center gap-1.5 rounded-full border px-3 text-xs font-semibold transition sm:min-h-0 sm:py-1.5 {{ $koyu
                   ? 'border-white/25 bg-white/10 text-white hover:bg-white/20'
                   : 'border-stone-200 bg-white hover:border-emerald-300 hover:text-emerald-700 dark:border-stone-800 dark:bg-stone-900 dark:hover:border-emerald-700 dark:hover:text-emerald-400 '.($loop->first ? 'text-emerald-700 dark:text-emerald-400' : 'text-stone-600 dark:text-stone-300') }}">
                @if ($loop->first && ! $koyu)
                    <x-heroicon-o-arrow-trending-up class="h-3.5 w-3.5 text-[#16a97f]" />
                @endif
                {{ $kategori->name }}
            </a>
        @endforeach
    </div>
@endif

@if ($anahtar === 'canli_sayaclar')
    {{-- Gerçek platform istatistikleri (uydurma rakam yok) --}}
    <div class="mt-7 flex flex-wrap items-center gap-x-5 gap-y-3 {{ $koyu ? 'justify-center' : '' }}">
        @if (! $koyu && $latestListings->isNotEmpty())
            <div class="flex" aria-hidden="true">
                @foreach ($latestListings->take(4) as $ilan)
                    <span class="{{ $loop->first ? '' : '-ml-2.5' }} inline-block rounded-full border-2 border-stone-50 dark:border-stone-950">
                        <x-avatar :user="$ilan->user" size="h-8 w-8" text="text-2xs" />
                    </span>
                @endforeach
            </div>
        @endif
        @php $serit = $stats['serit'] ?? ['tip' => 'sayilar']; @endphp

        {{-- Şerit üç hâlden birini basar (karar HomeController::heroSerit'te,
             gerekçesi orada yazılı). Kısaca: gerçek hareket bir ilan listesi
             sayfasını (12) bile doldurmuyorsa sayı göstermek siteyi savunmaz,
             aleyhine tanıklık eder — o durumda ziyaretçi için gerçekten dolu
             olan şey (ülke rehberi) ya da dürüst bir arz çağrısı gösterilir. --}}
        @if ($serit['tip'] === 'rehber')
            <a href="{{ url('/'.strtolower($serit['ulke']->code)) }}"
               class="flex items-center gap-2 {{ $koyu ? 'text-white' : 'text-stone-700 dark:text-stone-200' }}">
                <span aria-hidden="true">{{ $serit['ulke']->emoji }}</span>
                <span class="text-sm font-semibold">{{ $serit['ulke']->name_tr }} rehberi</span>
                <span class="text-xs font-medium {{ $koyu ? 'text-white/70' : 'text-stone-500 dark:text-stone-400' }}">{{ $serit['temsilcilik'] }} temsilcilik · {{ $serit['islem'] }} işlem</span>
            </a>
        @elseif ($serit['tip'] === 'cagri')
            <a href="{{ url('/panel/ilan/yeni') }}"
               class="flex items-center gap-2 {{ $koyu ? 'text-white' : 'text-stone-700 dark:text-stone-200' }}">
                <span class="text-sm font-semibold">Şehrinde ilk ilanı sen ver</span>
                <span class="text-xs font-medium {{ $koyu ? 'text-white/70' : 'text-stone-500 dark:text-stone-400' }}">ücretsiz, komisyonsuz</span>
            </a>
        @else
        <div class="flex items-baseline gap-1.5">
            <span class="text-lg font-extrabold {{ $koyu ? 'text-white' : 'text-stone-800 dark:text-stone-50' }}">{{ $stats['countries'] }}</span>
            <span class="text-xs font-medium {{ $koyu ? 'text-white/70' : 'text-stone-500 dark:text-stone-400' }}">ülke</span>
        </div>
        <div class="h-5 w-px {{ $koyu ? 'bg-white/25' : 'bg-stone-300/60 dark:bg-stone-700' }}" aria-hidden="true"></div>
        {{-- Katalog büyüklüğü değil GERÇEK HAREKET gösterilir.
             Eski satır "97 kategori · 44 şehir" diyordu: ikisi de sahte değildi
             ama ziyaretçiye hiçbir şey söylemiyordu — 'kategori' katalog
             boyutu, 'şehir' ise CitySeeder'ın ülke başına 2 tohumladığı sayı,
             yani ilanı olan şehir DEĞİL. Artık ikisi de aktif ilandan türüyor. --}}
        <div class="flex items-baseline gap-1.5">
            <span class="text-lg font-extrabold {{ $koyu ? 'text-white' : 'text-stone-800 dark:text-stone-50' }}">{{ $stats['activeCities'] ?? $stats['cities'] }}</span>
            <span class="text-xs font-medium {{ $koyu ? 'text-white/70' : 'text-stone-500 dark:text-stone-400' }}">şehir</span>
        </div>
        <div class="h-5 w-px {{ $koyu ? 'bg-white/25' : 'bg-stone-300/60 dark:bg-stone-700' }}" aria-hidden="true"></div>
        <div class="flex items-baseline gap-1.5">
            <span class="text-lg font-extrabold {{ $koyu ? 'text-white' : 'text-[#16a97f]' }}">{{ $stats['activeListings'] ?? 0 }}</span>
            <span class="text-xs font-medium {{ $koyu ? 'text-white/70' : 'text-stone-500 dark:text-stone-400' }}">aktif ilan</span>
        </div>
        @endif
    </div>
@endif
