{{--
    Yaşam Rehberi — içerik detayı (/de/yasam/bankacilik-finans/ssn-siz-hesap-acma).

    `icerik` markdown DEĞİL, yapılandırılmış blok listesi — bkz. migration
    yorumu. Render ederken kullanıcıdan gelen HTML'e güvenmiyoruz, Blade
    `{{ }}` her blok için otomatik kaçışlı basıyor.

    Topluluk düzeltme önerisi formu BURADA YOK — F3'te eklenecek (bkz.
    docs/plans/2026-08-21-yasam-rehberi-tasarimi.md §7). F0 yalnız okuma
    yüzeyi.
--}}
<x-layouts.app
    :title="$konu->baslik.' — '.$country->name_tr.' — '.setting('genel.site_adi')"
    :description="$konu->kisa_aciklama ?? ($country->name_tr.'\'da '.$konu->baslik.' hakkında pratik, Türkçe rehber.')"
>
    <div class="mx-auto max-w-3xl px-4 py-12">
        <nav class="text-sm text-stone-500 dark:text-stone-400" aria-label="breadcrumb">
            <a href="{{ route('home') }}" class="hover:underline">Ana sayfa</a>
            <span aria-hidden="true"> / </span>
            <a href="{{ route('yasam-rehberi.kategoriler', strtolower($country->code)) }}" class="hover:underline">{{ $country->name_tr }} Yaşam Rehberi</a>
            <span aria-hidden="true"> / </span>
            <a href="{{ route('yasam-rehberi.konular', [strtolower($country->code), $kategori->slug]) }}" class="hover:underline">{{ $kategori->ad }}</a>
            <span aria-hidden="true"> / </span>
            <span class="text-stone-700 dark:text-stone-200">{{ $konu->baslik }}</span>
        </nav>

        <h1 class="mt-4 text-3xl font-bold text-stone-900 dark:text-stone-50">{{ $konu->baslik }}</h1>
        <p class="mt-2 text-stone-600 dark:text-stone-300">{{ $country->name_tr }} için</p>

        <div class="mt-6 flex flex-wrap gap-3 text-sm">
            <span class="rounded-full bg-stone-100 px-3 py-1.5 text-stone-500 dark:bg-stone-800 dark:text-stone-400">
                Son doğrulama: {{ $icerik->dogrulanma_tarihi?->translatedFormat('d F Y') ?? 'henüz doğrulanmadı' }}
            </span>
        </div>

        @if (! empty($icerik->icerik))
            {{-- Bloklar DÜZ bir liste (iç içe repeater yok — hem panelden
                 düzenlemesi hem AI ajanlarının üretmesi daha kolay). Ardışık
                 "madde" blokları TEK <ul>'a toplanır, bunun dışında sıradan
                 basılır. --}}
            <div class="mt-8 space-y-4">
                @php $maddeAcik = false; @endphp
                @foreach ($icerik->icerik as $blok)
                    @php $tip = $blok['tip'] ?? 'paragraf'; @endphp

                    @if ($tip === 'madde' && ! $maddeAcik)
                        <ul class="list-disc space-y-1.5 pl-5 text-stone-700 dark:text-stone-200">
                        @php $maddeAcik = true; @endphp
                    @elseif ($tip !== 'madde' && $maddeAcik)
                        </ul>
                        @php $maddeAcik = false; @endphp
                    @endif

                    @if ($tip === 'madde')
                        <li>{{ $blok['metin'] ?? '' }}</li>
                    @elseif ($tip === 'baslik')
                        <h2 class="pt-2 text-xl font-semibold text-stone-900 dark:text-stone-50">{{ $blok['metin'] ?? '' }}</h2>
                    @else
                        <p class="leading-relaxed text-stone-700 dark:text-stone-200">{{ $blok['metin'] ?? '' }}</p>
                    @endif
                @endforeach
                @if ($maddeAcik)
                    </ul>
                @endif
            </div>
        @endif

        {{-- Kaynak — biz özetiz, doğru bilgi oradan. --}}
        @if ($icerik->kaynak_url)
            <div class="mt-10 rounded-2xl border border-emerald-200 bg-emerald-50 p-6 dark:border-emerald-800 dark:bg-emerald-950/40">
                <p class="font-semibold text-stone-900 dark:text-stone-50">Kaynağı incele</p>
                @if ($icerik->kaynak_aciklama)
                    <p class="mt-1 text-sm text-stone-600 dark:text-stone-300">{{ $icerik->kaynak_aciklama }}</p>
                @endif
                <a href="{{ $icerik->kaynak_url }}" target="_blank" rel="noopener nofollow"
                    class="mt-4 inline-flex items-center gap-2 rounded-xl bg-emerald-700 px-5 py-2.5 font-medium text-white transition hover:bg-emerald-800">
                    Kaynağı aç ↗
                </a>
            </div>
        @endif

        <p class="mt-10 rounded-xl bg-stone-100 p-4 text-xs leading-relaxed text-stone-500 dark:bg-stone-800 dark:text-stone-400">
            Bu sayfa bilgilendirme amaçlıdır ve zamanla değişebilir. Önemli kararlardan önce
            lütfen resmî/güncel kaynaktan teyit edin.
        </p>
    </div>

    <x-json-ld type="BreadcrumbList" :data="[
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Ana sayfa', 'item' => route('home')],
            ['@type' => 'ListItem', 'position' => 2, 'name' => $country->name_tr.' Yaşam Rehberi', 'item' => route('yasam-rehberi.kategoriler', strtolower($country->code))],
            ['@type' => 'ListItem', 'position' => 3, 'name' => $kategori->ad, 'item' => route('yasam-rehberi.konular', [strtolower($country->code), $kategori->slug])],
            ['@type' => 'ListItem', 'position' => 4, 'name' => $konu->baslik, 'item' => route('yasam-rehberi.icerik', [strtolower($country->code), $kategori->slug, $konu->slug])],
        ],
    ]" />
</x-layouts.app>
