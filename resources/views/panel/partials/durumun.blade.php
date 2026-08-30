{{--
    K2 · DURUMUN — gerçek rakamlar, büyük tipografi.

    DEĞERİ 0 OLAN KUTU BASILMAZ; hepsi 0 ise katman hiç basılmaz. Genç bir
    platformda "0" yazan kutu dizisi, kalabalık görünmeye çalışmaktan daha
    çok zarar verir.

    TREND / SPARKLINE / "geçen haftaya göre %X" YOK: views_count tek kümülatif
    tamsayı, tarih bazlı görüntülenme tablosu yok. Etiket bu yüzden yalnız
    "Görüntülenme" der — arkasında olmayan bir kesinlik ima etmez.
--}}
@php
    $kutular = array_values(array_filter([
        ['deger' => $s->aktifIlan, 'etiket' => 'Aktif ilan', 'url' => '/panel/ilanlarim', 'ikon' => 'clipboard-document-list', 'renk' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300'],
        ['deger' => $s->toplamGoruntulenme, 'etiket' => 'Görüntülenme', 'url' => '/panel/ilanlarim', 'ikon' => 'eye', 'renk' => 'bg-sky-50 text-sky-700 dark:bg-sky-950/60 dark:text-sky-300'],
        ['deger' => $s->favori, 'etiket' => 'Favori', 'url' => '/panel/favorilerim', 'ikon' => 'heart', 'renk' => 'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300'],
        ['deger' => $s->kayitliArama, 'etiket' => 'Kayıtlı arama', 'url' => '/panel/aramalarim', 'ikon' => 'bookmark', 'renk' => 'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300'],
        ['deger' => $s->isModulu ? $s->basvuru : 0, 'etiket' => 'Başvuru', 'url' => '/panel/basvurularim', 'ikon' => 'document-text', 'renk' => 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300'],
        ['deger' => $s->davetiyeModulu ? $s->yaklasanDavetiye : 0, 'etiket' => 'Yaklaşan davetiye', 'url' => '/panel/etkinlikler', 'ikon' => 'envelope-open', 'renk' => 'bg-purple-50 text-purple-700 dark:bg-purple-950/60 dark:text-purple-300'],
        ['deger' => $s->davet, 'etiket' => 'Davet ettiğin', 'url' => '/panel/davet', 'ikon' => 'gift', 'renk' => 'bg-teal-50 text-teal-700 dark:bg-teal-950/60 dark:text-teal-300'],
    ], fn ($k) => $k['deger'] > 0));

    // İlan durum kompozisyonu: genişlikler GERÇEK sayılardan türer.
    $ilanToplam = max(1, $s->toplamIlan);
@endphp

@if ($kutular !== [])
    <div class="mt-8">
        <div class="flex items-center justify-between">
            <h2 class="text-xs font-bold uppercase tracking-wider text-stone-500 dark:text-stone-400">Durumun</h2>
            <span class="text-[11px] font-semibold text-stone-500 dark:text-stone-400">Canlı Metrikler</span>
        </div>

        <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
            @foreach ($kutular as $kutu)
                <a href="{{ url($kutu['url']) }}"
                   class="group relative flex flex-col justify-between rounded-2xl border border-stone-200/80 bg-white p-4 sm:p-5 shadow-2xs transition-all hover:-translate-y-0.5 hover:border-emerald-300 hover:shadow-md dark:border-stone-800 dark:bg-stone-900 dark:hover:border-emerald-700">
                    <div class="flex items-center justify-between">
                        <span class="grid h-10 w-10 place-items-center rounded-xl {{ $kutu['renk'] }} transition group-hover:scale-105">
                            <x-dynamic-component :component="'heroicon-o-'.$kutu['ikon']" class="h-5 w-5" />
                        </span>
                        <x-heroicon-o-arrow-right class="h-4 w-4 text-stone-300 transition group-hover:translate-x-0.5 group-hover:text-emerald-700 dark:text-stone-400 dark:group-hover:text-emerald-400" />
                    </div>
                    <div class="mt-4">
                        <span class="text-2xl sm:text-3xl font-extrabold tabular-nums text-stone-900 dark:text-stone-50">{{ number_format($kutu['deger'], 0, ',', '.') }}</span>
                        <span class="mt-0.5 block text-xs font-medium text-stone-500 dark:text-stone-400">{{ $kutu['etiket'] }}</span>
                    </div>
                </a>
            @endforeach
        </div>

        {{-- İlan durum şeridi --}}
        @if ($s->toplamIlan > 0 && ($s->bekleyenIlan > 0 || $s->pasifIlan > 0))
            <div class="mt-4 rounded-2xl border border-stone-200/80 bg-white p-4 shadow-2xs dark:border-stone-800 dark:bg-stone-900">
                <div class="flex h-2 overflow-hidden rounded-full bg-stone-100 dark:bg-stone-800" aria-hidden="true">
                    <span class="bg-emerald-500" style="width: {{ round($s->aktifIlan / $ilanToplam * 100) }}%"></span>
                    <span class="bg-amber-400" style="width: {{ round($s->bekleyenIlan / $ilanToplam * 100) }}%"></span>
                    <span class="bg-stone-300 dark:bg-stone-600" style="width: {{ round($s->pasifIlan / $ilanToplam * 100) }}%"></span>
                </div>
                <p class="mt-2.5 flex flex-wrap gap-x-4 gap-y-1 text-xs font-medium text-stone-600 dark:text-stone-400">
                    <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-emerald-500"></span>{{ $s->aktifIlan }} aktif</span>
                    @if ($s->bekleyenIlan > 0)
                        <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-amber-400"></span>{{ $s->bekleyenIlan }} yönetici onayında</span>
                    @endif
                    @if ($s->pasifIlan > 0)
                        <span class="inline-flex items-center gap-1.5"><span class="h-2 w-2 rounded-full bg-stone-300 dark:bg-stone-600"></span>{{ $s->pasifIlan }} pasif</span>
                    @endif
                </p>
            </div>
        @endif

        @if ($s->isModulu && $s->gorusme > 0)
            <p class="mt-2 text-xs font-semibold text-emerald-700 dark:text-emerald-400">{{ $s->gorusme }} başvurun görüşme aşamasında.</p>
        @endif
    </div>
@endif
