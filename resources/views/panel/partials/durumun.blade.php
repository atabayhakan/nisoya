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
        ['deger' => $s->aktifIlan, 'etiket' => 'Aktif ilan', 'url' => '/panel/ilanlarim'],
        ['deger' => $s->toplamGoruntulenme, 'etiket' => 'Görüntülenme', 'url' => '/panel/ilanlarim'],
        ['deger' => $s->favori, 'etiket' => 'Favori', 'url' => '/panel/favorilerim'],
        ['deger' => $s->kayitliArama, 'etiket' => 'Kayıtlı arama', 'url' => '/panel/aramalarim'],
        ['deger' => $s->isModulu ? $s->basvuru : 0, 'etiket' => 'Başvuru', 'url' => '/panel/basvurularim'],
        ['deger' => $s->davetiyeModulu ? $s->yaklasanDavetiye : 0, 'etiket' => 'Yaklaşan davetiye', 'url' => '/panel/etkinlikler'],
        ['deger' => $s->davet, 'etiket' => 'Davet ettiğin', 'url' => '/panel/davet'],
    ], fn ($k) => $k['deger'] > 0));

    // İlan durum kompozisyonu: genişlikler GERÇEK sayılardan türer.
    $ilanToplam = max(1, $s->toplamIlan);
@endphp

@if ($kutular !== [])
    <h2 class="mt-8 text-sm font-bold uppercase tracking-wide text-stone-500 dark:text-stone-400">Durumun</h2>

    <div class="mt-3 grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
        @foreach ($kutular as $kutu)
            <a href="{{ url($kutu['url']) }}"
               class="flex min-h-11 flex-col justify-center rounded-2xl border border-stone-200 bg-white px-4 py-3 transition hover:border-emerald-300 dark:border-stone-800 dark:bg-stone-900 dark:hover:border-emerald-700">
                <span class="text-3xl font-bold tabular-nums text-stone-900 dark:text-stone-50">{{ number_format($kutu['deger'], 0, ',', '.') }}</span>
                <span class="mt-0.5 text-xs text-stone-500 dark:text-stone-400">{{ $kutu['etiket'] }}</span>
            </a>
        @endforeach
    </div>

    {{-- İlan durum şeridi. Şerit dekoratif (aria-hidden); ALTINDAKİ lejant
         gerçek metin + gerçek sayı taşır, yani ekran okuyucu şeride bağımlı
         değil. Animasyon yok. --}}
    @if ($s->toplamIlan > 0 && ($s->bekleyenIlan > 0 || $s->pasifIlan > 0))
        <div class="mt-3 rounded-2xl border border-stone-200 bg-white px-4 py-3 dark:border-stone-800 dark:bg-stone-900">
            <div class="flex h-1.5 overflow-hidden rounded-full bg-stone-100 dark:bg-stone-800" aria-hidden="true">
                <span class="bg-emerald-500" style="width: {{ round($s->aktifIlan / $ilanToplam * 100) }}%"></span>
                <span class="bg-amber-400" style="width: {{ round($s->bekleyenIlan / $ilanToplam * 100) }}%"></span>
                <span class="bg-stone-300 dark:bg-stone-600" style="width: {{ round($s->pasifIlan / $ilanToplam * 100) }}%"></span>
            </div>
            <p class="mt-2 flex flex-wrap gap-x-4 gap-y-1 text-xs text-stone-500 dark:text-stone-400">
                <span>{{ $s->aktifIlan }} aktif</span>
                @if ($s->bekleyenIlan > 0)
                    <span>{{ $s->bekleyenIlan }} yönetici onayında</span>
                @endif
                @if ($s->pasifIlan > 0)
                    <span>{{ $s->pasifIlan }} pasif</span>
                @endif
            </p>
        </div>
    @endif

    @if ($s->isModulu && $s->gorusme > 0)
        <p class="mt-2 text-xs text-emerald-700 dark:text-emerald-400">{{ $s->gorusme }} başvurun görüşme aşamasında.</p>
    @endif
@endif
