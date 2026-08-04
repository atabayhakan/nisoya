@props(['anahtar', 'countries', 'stats', 'latestListings', 'koyu' => false, 'heroCips' => null])

{{-- VİTRİN HERO BLOĞU (P3) — Hero Yöneticisi'ndeki blok listesinin tek tek
     karşılıkları. Kapalı bloklar buraya HİÇ gelmez (çağıran taraf
     Hero::aktifBloklar() ile döner) — CSS ile gizleme yok.
     $koyu: "sahne" düzeninde metinler koyu görsel üzerinde beyaz olur. --}}
@if ($anahtar === 'arama')
    {{-- Arama paneli — klasikle aynı form sözleşmesi (GET /ilanlar, q + ulke) --}}
    <form action="{{ url('/ilanlar') }}" method="GET" class="mt-7 flex flex-col gap-1.5 rounded-2xl border border-stone-200/70 bg-white p-2 shadow-brand sm:flex-row sm:items-center dark:border-stone-800 dark:bg-stone-900 dark:shadow-none">
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
        <select name="ulke" aria-label="Ülke seç" class="h-12 w-full shrink-0 truncate rounded-xl border-0 bg-stone-100 px-3 text-sm font-semibold text-stone-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 sm:w-36 dark:bg-stone-800 dark:text-stone-200">
            <option value="">Tüm ülkeler</option>
            @foreach ($countries as $country)
                <option value="{{ $country->code }}">{{ $country->emoji }} {{ $country->name_tr }}</option>
            @endforeach
        </select>
        <button type="submit" class="inline-flex h-12 items-center justify-center gap-2 rounded-xl bg-emerald-700 px-6 text-sm font-bold text-white shadow-[0_12px_22px_-12px_rgba(62,99,240,1)] transition hover:bg-emerald-800 dark:bg-emerald-500 dark:text-stone-900 dark:shadow-none dark:hover:bg-emerald-400">
            Ara
            <x-heroicon-o-arrow-right class="h-4 w-4" />
        </button>
    </form>
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
