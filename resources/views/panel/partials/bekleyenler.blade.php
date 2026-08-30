{{--
    K1 · BEKLEYENLER — yalnız EYLEM gerektirenler.

    SABİT KANONİK SIRA. Bloklar veri değiştikçe yer değiştirmez: haftada bir
    telefondan giren teknik olmayan kullanıcının kas hafızasını bozmak, sıfır
    fayda karşılığında gerçek bir maliyettir.

    SATIR / SAYI AYRIMI (kritik): anlaşma sinyalleri sorguda TAVANLI çekildiği
    için ASLA sayıya çevrilmez — her biri kendi satırı olarak basılır, tavanı
    aşarsa sonda SAYISIZ "…ve daha fazlası" satırı çıkar. Mesaj / bildirim /
    başvuru / öne-çıkarma ise kırpılmamış gerçek COUNT olduğu için rakam
    basılabilir. Tavanlı bir sorgudan sayı üretmek, kullanıcıya yanlış bir
    büyüklük söylemek olurdu.

    "N ilanın onay bekliyor" BURADA YOKTUR: orada tıkanan yönetici, kullanıcı
    değil. O bilgi K2'nin altına not olarak iner.
--}}
@php
    $bekleyenVar = $s->bekleyenVarMi();
@endphp

@if ($bekleyenVar)
    <div class="mt-8">
        <div class="flex items-center gap-2">
            <span class="grid h-2 w-2 rounded-full bg-amber-500 animate-pulse"></span>
            <h2 class="text-xs font-bold uppercase tracking-wider text-stone-500 dark:text-stone-400">Bekleyenler</h2>
        </div>

        <ul class="mt-3 space-y-2.5">
            {{-- 1 · Sorun bildirilen anlaşma --}}
            @foreach ($s->bekleyenSatirlar as $satir)
                @php
                    $stil = match ($satir['tur']) {
                        'sorunlu' => ['zemin' => 'bg-red-50/80 border-red-200 dark:bg-red-950/30 dark:border-red-900', 'ikon' => 'text-red-700 dark:text-red-300', 'ikonZemin' => 'bg-red-100 dark:bg-red-900/50'],
                        'teklif' => ['zemin' => 'bg-amber-50/80 border-amber-200 dark:bg-amber-950/30 dark:border-amber-900', 'ikon' => 'text-amber-700 dark:text-amber-300', 'ikonZemin' => 'bg-amber-100 dark:bg-amber-900/50'],
                        default => ['zemin' => 'bg-stone-50 border-stone-200 dark:bg-stone-800/40 dark:border-stone-800', 'ikon' => 'text-stone-700 dark:text-stone-300', 'ikonZemin' => 'bg-stone-200 dark:bg-stone-800'],
                    };
                    $metin = match ($satir['tur']) {
                        'sorunlu' => $satir['karsiTarafAd'].' bir sorun bildirdi',
                        'teklif' => $satir['karsiTarafAd'].' bir teklif gönderdi'.($satir['tutar'] ? ' — '.$satir['tutar'].' '.$satir['currency'] : ''),
                        default => $satir['karsiTarafAd'].' ile işin bitti — değerlendirmen bekleniyor',
                    };
                    $hedef = $satir['tur'] === 'degerlendirme' && $satir['karsiTarafKullaniciAdi']
                        ? route('profiles.show', $satir['karsiTarafKullaniciAdi'])
                        : ($satir['konusmaId'] ? route('panel.messages.show', $satir['konusmaId']) : route('panel.messages.index'));
                @endphp
                <li>
                    <a href="{{ $hedef }}" class="flex min-h-14 items-center gap-3.5 rounded-2xl border {{ $stil['zemin'] }} px-4 py-3 transition hover:shadow-sm">
                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl {{ $stil['ikonZemin'] }}">
                            <x-heroicon-o-exclamation-circle class="h-5 w-5 {{ $stil['ikon'] }}" />
                        </span>
                        <span class="min-w-0 flex-1 text-sm font-semibold text-stone-900 dark:text-stone-100">{{ $metin }}</span>
                        <x-heroicon-o-chevron-right class="h-4 w-4 shrink-0 text-stone-500 dark:text-stone-400" />
                    </a>
                </li>
            @endforeach

            @if ($s->dahaFazlasiVar)
                <li class="px-4 text-xs font-medium text-stone-500 dark:text-stone-400">…ve daha fazlası</li>
            @endif

            {{-- 3 · Okunmamış mesaj --}}
            @if ($s->okunmamisMesaj > 0)
                <li>
                    <a href="{{ route('panel.messages.index') }}" class="flex min-h-14 items-center gap-3.5 rounded-2xl border border-amber-200 bg-amber-50/80 px-4 py-3 transition hover:shadow-sm dark:border-amber-900 dark:bg-amber-950/30">
                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-amber-100 dark:bg-amber-900/50">
                            <x-heroicon-o-chat-bubble-left-right class="h-5 w-5 text-amber-700 dark:text-amber-300" />
                        </span>
                        <span class="min-w-0 flex-1 text-sm font-semibold text-stone-900 dark:text-stone-100">
                            {{ $s->okunmamisMesaj }} okunmamış mesaj
                        </span>
                        <x-heroicon-o-chevron-right class="h-4 w-4 shrink-0 text-stone-500 dark:text-stone-400" />
                    </a>
                </li>
            @endif

            {{-- 4 · İşverene gelen yeni başvuru --}}
            @if ($s->gelenYeniBasvuru > 0)
                <li>
                    <a href="{{ url('/panel/is-ilanlarim') }}" class="flex min-h-14 items-center gap-3.5 rounded-2xl border border-emerald-200 bg-emerald-50/80 px-4 py-3 transition hover:shadow-sm dark:border-emerald-900 dark:bg-emerald-950/30">
                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-emerald-100 dark:bg-emerald-900/50">
                            <x-heroicon-o-document-text class="h-5 w-5 text-emerald-700 dark:text-emerald-300" />
                        </span>
                        <span class="min-w-0 flex-1 text-sm font-semibold text-stone-900 dark:text-stone-100">
                            {{ $s->gelenYeniBasvuru }} yeni başvuru geldi
                        </span>
                        <x-heroicon-o-chevron-right class="h-4 w-4 shrink-0 text-stone-500 dark:text-stone-400" />
                    </a>
                </li>
            @endif

            {{-- 6 · Okunmamış bildirim --}}
            @if ($s->okunmamisBildirim > 0)
                <li>
                    <a href="{{ url('/panel/bildirimler') }}" class="flex min-h-14 items-center gap-3.5 rounded-2xl border border-stone-200 bg-stone-50 px-4 py-3 transition hover:shadow-sm dark:border-stone-800 dark:bg-stone-800/40">
                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-stone-200 dark:bg-stone-800">
                            <x-heroicon-o-bell class="h-5 w-5 text-stone-700 dark:text-stone-300" />
                        </span>
                        <span class="min-w-0 flex-1 text-sm font-semibold text-stone-900 dark:text-stone-100">
                            {{ $s->okunmamisBildirim }} okunmamış bildirim
                        </span>
                        <x-heroicon-o-chevron-right class="h-4 w-4 shrink-0 text-stone-500 dark:text-stone-400" />
                    </a>
                </li>
            @endif

            {{-- 7 · Öne çıkarması bitmek üzere olan ilan --}}
            @if ($s->oneCikarmaBitiyor > 0)
                <li>
                    <a href="{{ url('/panel/ilanlarim') }}" class="flex min-h-14 items-center gap-3.5 rounded-2xl border border-amber-200 bg-amber-50/80 px-4 py-3 transition hover:shadow-sm dark:border-amber-900 dark:bg-amber-950/30">
                        <span class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-amber-100 dark:bg-amber-900/50">
                            <x-heroicon-o-star class="h-5 w-5 text-amber-700 dark:text-amber-300" />
                        </span>
                        <span class="min-w-0 flex-1 text-sm font-semibold text-stone-900 dark:text-stone-100">
                            {{ $s->oneCikarmaBitiyor }} ilanının öne çıkarması bitmek üzere — yenileme talebi gönderebilirsin
                        </span>
                        <x-heroicon-o-chevron-right class="h-4 w-4 shrink-0 text-stone-500 dark:text-stone-400" />
                    </a>
                </li>
            @endif
        </ul>
    </div>
@elseif (! $s->bosDurum())
    <div class="mt-6 flex items-center gap-2 rounded-2xl border border-emerald-100 bg-emerald-50/50 px-4 py-3 text-xs font-semibold text-emerald-800 dark:border-emerald-900/30 dark:bg-emerald-950/20 dark:text-emerald-300">
        <x-heroicon-s-check-circle class="h-4 w-4 text-emerald-700 shrink-0 dark:text-emerald-400" />
        <span>Bekleyen bir şey yok — tüm işlemlerin ve bildirimlerin güncel.</span>
    </div>
@endif
