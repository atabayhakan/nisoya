{{-- Satıcının ilanlarına giden iki düğme.

     TEK KAYNAK: hem mobildeki satıcı şeridi hem masaüstündeki satıcı kartı
     bunu içerir. İki yere ayrı ayrı yazılsaydı biri güncellenip diğeri
     unutulurdu — bu depoda bugün panoda tam olarak bu olmuştu (iki ayrı
     "merhaba" kartı).

     SIFIR OLAN DÜĞME BASILMAZ. "Geçmiş ilanları (0)" tıklandığında boş bir
     sayfa açar; olmayan bir şeyin sözünü veren düğme, olmayan düğmeden
     kötüdür.

     Beklenen değişkenler: $seller (User), $counts ['guncel','gecmis'].
--}}
@if ($counts['guncel'] > 0 || $counts['gecmis'] > 0)
    <div class="mt-3 grid grid-cols-2 gap-2">
        @if ($counts['guncel'] > 0)
            <a href="{{ route('profiles.show', $seller->username) }}#ilanlar"
               class="flex items-center justify-center gap-1.5 rounded-[11px] border border-stone-300 px-3 py-2.5 text-xs font-bold text-stone-700 transition hover:border-emerald-500 hover:text-emerald-700 dark:border-stone-700 dark:text-stone-200 dark:hover:border-emerald-500 dark:hover:text-emerald-400">
                <x-heroicon-o-squares-2x2 class="h-4 w-4 shrink-0" />
                Güncel ilanları ({{ $counts['guncel'] }})
            </a>
        @endif

        @if ($counts['gecmis'] > 0)
            <a href="{{ route('profiles.show', ['user' => $seller->username, 'durum' => 'gecmis']) }}#ilanlar"
               class="flex items-center justify-center gap-1.5 rounded-[11px] border border-stone-300 px-3 py-2.5 text-xs font-bold text-stone-700 transition hover:border-emerald-500 hover:text-emerald-700 dark:border-stone-700 dark:text-stone-200 dark:hover:border-emerald-500 dark:hover:text-emerald-400">
                <x-heroicon-o-archive-box class="h-4 w-4 shrink-0" />
                Geçmiş ilanları ({{ $counts['gecmis'] }})
            </a>
        @endif
    </div>
@endif
