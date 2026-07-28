{{--
    K3 · BÖLÜMLER — her zaman görünen dizin.

    ROL KAPISI YOK. Jürinin en keskin kararı buydu: kişiselleştirme yanlış
    tahmin etse bile hiçbir panel sayfası erişilemez olmamalı. Tüm ilanlarını
    silen satıcı "İlanlarım"ı bulamazsa, hiç davetiyesi olmayan kullanıcı
    modülün varlığından habersiz kalırsa kişiselleştirme zarar vermiş olur.
    Kişiselleştirme yalnız SIRALAR, asla GİZLEMEZ. Tek kapı Modules::enabled.

    Kartlar dizin satırlarına indi: 14 kartın 14 açıklaması ("İlanlarını
    yönet", "Gelen mesajların"...) saf gürültüydü — başlık zaten aynı şeyi
    söylüyor.
--}}
@php
    $genel = [
        ['ad' => 'İlanlarım', 'ikon' => 'clipboard-document-list', 'url' => '/panel/ilanlarim'],
        ['ad' => 'Mesajlar', 'ikon' => 'chat-bubble-left-right', 'url' => '/panel/mesajlar'],
        ['ad' => 'Bildirimler', 'ikon' => 'bell', 'url' => '/panel/bildirimler'],
        ['ad' => 'Favorilerim', 'ikon' => 'heart', 'url' => '/panel/favorilerim'],
        ['ad' => 'Aramalarım', 'ikon' => 'bookmark', 'url' => '/panel/aramalarim'],
        ['ad' => 'Profilim', 'ikon' => 'user-circle', 'url' => '/panel/profil'],
        ['ad' => 'Arkadaşını Davet Et', 'ikon' => 'gift', 'url' => '/panel/davet'],
    ];

    if ($s->davetiyeModulu) {
        $genel[] = ['ad' => 'Davetiyelerim', 'ikon' => 'envelope-open', 'url' => '/panel/etkinlikler'];
    }

    $is = [];
    if ($s->isModulu) {
        $is = [
            ['ad' => 'İş İlanlarım', 'ikon' => 'briefcase', 'url' => '/panel/is-ilanlarim'],
            ['ad' => 'Başvurularım', 'ikon' => 'document-text', 'url' => '/panel/basvurularim'],
            ['ad' => 'İş Yer İmlerim', 'ikon' => 'bookmark', 'url' => '/panel/is-yer-imlerim'],
            ['ad' => 'İş Aramalarım', 'ikon' => 'bell', 'url' => '/panel/is-aramalarim'],
        ];
    }
@endphp

<h2 class="mt-10 text-sm font-bold uppercase tracking-wide text-stone-500 dark:text-stone-400">Bölümler</h2>

<div class="mt-3 grid grid-cols-2 gap-2 md:grid-cols-4">
    @foreach ($genel as $b)
        <a href="{{ url($b['url']) }}"
           class="flex min-h-11 items-center gap-2.5 rounded-xl border border-stone-200 bg-white px-3 text-sm font-medium text-stone-700 transition hover:border-emerald-300 hover:text-emerald-700 dark:border-stone-800 dark:bg-stone-900 dark:text-stone-200 dark:hover:border-emerald-700 dark:hover:text-emerald-400">
            <x-dynamic-component :component="'heroicon-o-'.$b['ikon']" class="h-4 w-4 shrink-0 text-stone-600" />
            <span class="min-w-0 truncate">{{ $b['ad'] }}</span>
        </a>
    @endforeach
</div>

@if ($s->isModulu)
    <h3 class="mt-6 text-xs font-bold uppercase tracking-wide text-stone-600 dark:text-stone-400">İş</h3>
    <div class="mt-2 grid grid-cols-2 gap-2 md:grid-cols-4">
        @foreach ($is as $b)
            <a href="{{ url($b['url']) }}"
               class="flex min-h-11 items-center gap-2.5 rounded-xl border border-stone-200 bg-white px-3 text-sm font-medium text-stone-700 transition hover:border-emerald-300 hover:text-emerald-700 dark:border-stone-800 dark:bg-stone-900 dark:text-stone-200 dark:hover:border-emerald-700 dark:hover:text-emerald-400">
                <x-dynamic-component :component="'heroicon-o-'.$b['ikon']" class="h-4 w-4 shrink-0 text-stone-600" />
                <span class="min-w-0 truncate">{{ $b['ad'] }}</span>
            </a>
        @endforeach

        {{-- ŞİRKET SATIRI TEK YERDE, İKİ YÜZLÜ.
             Şirketi olmayana "İşveren profilin" demek yanlış etiket; satırı
             hiç göstermemek ise işveren giriş yolunu kapatır. Üstelik şirket
             oluşturmanın sessiz bir bedeli var: hesap kurumsala geçiyor ve
             kullanıcı Yetenek Havuzu'nda görünmez oluyor. O bedel ilk kez
             burada yazılı hale geliyor. --}}
        <a href="{{ url('/panel/sirket') }}"
           class="flex min-h-11 flex-col justify-center gap-0.5 rounded-xl border border-stone-200 bg-white px-3 py-2 transition hover:border-emerald-300 dark:border-stone-800 dark:bg-stone-900 dark:hover:border-emerald-700">
            <span class="flex items-center gap-2.5 text-sm font-medium text-stone-700 dark:text-stone-200">
                <x-heroicon-o-building-office-2 class="h-4 w-4 shrink-0 text-stone-600" />
                <span class="min-w-0 truncate">{{ $s->sirketVar ? 'Şirket Profili' : 'Şirket profili oluştur' }}</span>
            </span>
            @unless ($s->sirketVar)
                <span class="pl-6.5 text-2xs leading-tight text-stone-600 dark:text-stone-400">hesabın kurumsala geçer, Yetenek Havuzu'nda görünmezsin</span>
            @endunless
        </a>
    </div>
@endif

@if ($s->yetenekHavuzuKapali)
    {{-- Tam satır bağlantı, düz metin içinde gömülü link DEĞİL: bu bir eylem
         ve mobilde 16px'lik bir kelimeye dokundurulamaz. --}}
    <a href="{{ url('/panel/profil') }}"
       class="mt-4 flex min-h-11 items-center gap-2 rounded-xl bg-stone-50 px-3 text-xs text-stone-600 transition hover:bg-stone-100 dark:bg-stone-900/60 dark:text-stone-300 dark:hover:bg-stone-800">
        <span class="min-w-0 flex-1">Yetenek Havuzu'nda görünmüyorsun — profilinden açabilirsin</span>
        <x-heroicon-o-chevron-right class="h-4 w-4 shrink-0 text-stone-600" />
    </a>
@endif

{{-- Kart ızgarasının 14. elemanı olmaktan çıktı: bu bir panel bölümü değil,
     halka açık siteye giden bir gezinme linki. --}}
<a href="{{ url('/ilanlar') }}" class="mt-5 inline-flex min-h-11 items-center text-sm font-medium text-emerald-700 hover:underline dark:text-emerald-400">
    İlanlara göz at →
</a>
