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
        ['ad' => 'İlanlarım', 'ikon' => 'clipboard-document-list', 'url' => '/panel/ilanlarim', 'renk' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300'],
        ['ad' => 'Mesajlar', 'ikon' => 'chat-bubble-left-right', 'url' => '/panel/mesajlar', 'renk' => 'bg-sky-50 text-sky-700 dark:bg-sky-950/60 dark:text-sky-300'],
        ['ad' => 'Bildirimler', 'ikon' => 'bell', 'url' => '/panel/bildirimler', 'renk' => 'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300'],
        ['ad' => 'Favorilerim', 'ikon' => 'heart', 'url' => '/panel/favorilerim', 'renk' => 'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300'],
        ['ad' => 'Aramalarım', 'ikon' => 'bookmark', 'url' => '/panel/aramalarim', 'renk' => 'bg-violet-50 text-violet-700 dark:bg-violet-950/60 dark:text-violet-300'],
        ['ad' => 'Profilim', 'ikon' => 'user-circle', 'url' => '/panel/profil', 'renk' => 'bg-teal-50 text-teal-700 dark:bg-teal-950/60 dark:text-teal-300'],
        ['ad' => 'Arkadaşını Davet Et', 'ikon' => 'gift', 'url' => '/panel/davet', 'renk' => 'bg-orange-50 text-orange-700 dark:bg-orange-950/60 dark:text-orange-300'],
    ];

    if ($s->davetiyeModulu) {
        $genel[] = ['ad' => 'Davetiyelerim', 'ikon' => 'envelope-open', 'url' => '/panel/etkinlikler', 'renk' => 'bg-pink-50 text-pink-700 dark:bg-pink-950/60 dark:text-pink-300'];
    }

    $is = [];
    if ($s->isModulu) {
        $is = [
            ['ad' => 'İş İlanlarım', 'ikon' => 'briefcase', 'url' => '/panel/is-ilanlarim', 'renk' => 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300'],
            ['ad' => 'Başvurularım', 'ikon' => 'document-text', 'url' => '/panel/basvurularim', 'renk' => 'bg-blue-50 text-blue-700 dark:bg-blue-950/60 dark:text-blue-300'],
            ['ad' => 'İş Yer İmlerim', 'ikon' => 'bookmark', 'url' => '/panel/is-yer-imlerim', 'renk' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300'],
            ['ad' => 'İş Aramalarım', 'ikon' => 'bell', 'url' => '/panel/is-aramalarim', 'renk' => 'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300'],
        ];
    }
@endphp

<div class="mt-8">
    <div class="flex items-center justify-between">
        <h2 class="text-xs font-bold uppercase tracking-wider text-stone-500 dark:text-stone-400">Bölümler</h2>
        <span class="text-[11px] font-semibold text-stone-500 dark:text-stone-400">Kişisel & Pazar Yeri</span>
    </div>

    <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-2 md:grid-cols-4">
        @foreach ($genel as $b)
            <a href="{{ url($b['url']) }}"
               class="group flex min-h-14 items-center gap-3 rounded-2xl border border-stone-200/80 bg-white p-3.5 shadow-2xs transition-all hover:-translate-y-0.5 hover:border-emerald-300 hover:shadow-xs dark:border-stone-800 dark:bg-stone-900 dark:hover:border-emerald-700">
                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl {{ $b['renk'] }} transition group-hover:scale-105">
                    <x-dynamic-component :component="'heroicon-o-'.$b['ikon']" class="h-5 w-5" />
                </span>
                <span class="min-w-0 flex-1 truncate text-xs sm:text-sm font-semibold text-stone-800 group-hover:text-emerald-700 dark:text-stone-100 dark:group-hover:text-emerald-300">{{ $b['ad'] }}</span>
                <x-heroicon-o-chevron-right class="h-3.5 w-3.5 shrink-0 text-stone-500 transition group-hover:translate-x-0.5 group-hover:text-emerald-700 dark:text-stone-400 dark:group-hover:text-emerald-400" />
            </a>
        @endforeach
    </div>
</div>

@if ($s->isModulu)
    <div class="mt-8">
        <div class="flex items-center justify-between">
            <h3 class="text-xs font-bold uppercase tracking-wider text-stone-500 dark:text-stone-400">İş & Kariyer</h3>
            <span class="text-[11px] font-semibold text-stone-500 dark:text-stone-400">İstihdam Merkezi</span>
        </div>
        <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-2 md:grid-cols-4">
            @foreach ($is as $b)
                <a href="{{ url($b['url']) }}"
                   class="group flex min-h-14 items-center gap-3 rounded-2xl border border-stone-200/80 bg-white p-3.5 shadow-2xs transition-all hover:-translate-y-0.5 hover:border-emerald-300 hover:shadow-xs dark:border-stone-800 dark:bg-stone-900 dark:hover:border-emerald-700">
                    <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl {{ $b['renk'] }} transition group-hover:scale-105">
                        <x-dynamic-component :component="'heroicon-o-'.$b['ikon']" class="h-5 w-5" />
                    </span>
                    <span class="min-w-0 flex-1 truncate text-xs sm:text-sm font-semibold text-stone-800 group-hover:text-emerald-700 dark:text-stone-100 dark:group-hover:text-emerald-300">{{ $b['ad'] }}</span>
                    <x-heroicon-o-chevron-right class="h-3.5 w-3.5 shrink-0 text-stone-500 transition group-hover:translate-x-0.5 group-hover:text-emerald-700 dark:text-stone-400 dark:group-hover:text-emerald-400" />
                </a>
            @endforeach

            {{-- Şirket Kartı --}}
            <a href="{{ url('/panel/sirket') }}"
               class="group col-span-2 sm:col-span-2 md:col-span-4 flex min-h-14 flex-col justify-center rounded-2xl border border-stone-200/80 bg-white p-4 shadow-2xs transition-all hover:-translate-y-0.5 hover:border-emerald-300 hover:shadow-xs dark:border-stone-800 dark:bg-stone-900 dark:hover:border-emerald-700">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-stone-100 text-stone-700 dark:bg-stone-800 dark:text-stone-300 transition group-hover:scale-105">
                            <x-heroicon-o-building-office-2 class="h-5 w-5" />
                        </span>
                        <div>
                            <span class="text-sm font-bold text-stone-900 group-hover:text-emerald-700 dark:text-stone-100 dark:group-hover:text-emerald-300">{{ $s->sirketVar ? 'Şirket Profili' : 'Şirket profili oluştur' }}</span>
                            @unless ($s->sirketVar)
                                <p class="text-xs text-stone-500 dark:text-stone-400">hesabın kurumsala geçer, Yetenek Havuzu'nda görünmezsin</p>
                            @endunless
                        </div>
                    </div>
                    <x-heroicon-o-chevron-right class="h-4 w-4 shrink-0 text-stone-500 transition group-hover:translate-x-0.5 group-hover:text-emerald-700 dark:text-stone-400 dark:group-hover:text-emerald-400" />
                </div>
            </a>
        </div>
    </div>
@endif

@if ($s->yetenekHavuzuKapali)
    <a href="{{ url('/panel/profil') }}"
       class="mt-4 flex min-h-12 items-center gap-3 rounded-2xl border border-stone-200/80 bg-stone-50/80 px-4 py-3 text-xs font-semibold text-stone-700 transition hover:border-emerald-300 hover:bg-white dark:border-stone-800 dark:bg-stone-900/60 dark:text-stone-300 dark:hover:bg-stone-800">
        <x-heroicon-o-information-circle class="h-4 w-4 shrink-0 text-amber-600 dark:text-amber-400" />
        <span class="min-w-0 flex-1">Yetenek Havuzu'nda görünmüyorsun — profilinden açabilirsin</span>
        <x-heroicon-o-chevron-right class="h-4 w-4 shrink-0 text-stone-500 dark:text-stone-400" />
    </a>
@endif
