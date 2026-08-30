{{--
    K4 · BAŞLANGIÇ — yalnız hiç sinyali olmayan kullanıcıya.

    Burada tek bir "0" görünmez: sıfırlarla dolu bir pano, yeni kullanıcıya
    "burada hiçbir şey yok" der. Onun yerine tek bir soru sorulur.

    İKİ EŞİT KAPI, çünkü platform kayıt sırasında niyeti HİÇ SORMUYOR — niyet
    varsayılmaz. (Varsaymak için yeni bir kolon eklemek de bu işin kapsamı
    değil; veri olmadan tahmin yürütmek yanlış kapıyı öne çıkarır.)

    Profil tamamlama KESİR olarak gösterilir, yüzde olarak DEĞİL: yüzde,
    arkasında olmayan bir kesinlik ima eder.
--}}
<div class="mt-8 rounded-3xl border border-stone-200/90 bg-white p-6 sm:p-8 shadow-sm dark:border-stone-800 dark:bg-stone-900">
    <div class="max-w-xl">
        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-600/20 dark:bg-emerald-950/60 dark:text-emerald-300">
            👋 Başlangıç Rehberi
        </span>
        <h2 class="mt-3 text-lg sm:text-xl font-bold text-stone-900 dark:text-stone-50">Nereden başlamak istersin?</h2>
        <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">İki yönde de çalışır: birini arayabilir ya da kendini duyurabilirsin.</p>
    </div>

    <div class="mt-5 grid gap-3 sm:grid-cols-2">
        <a href="{{ url('/ilanlar') }}"
           class="group flex min-h-12 items-center justify-between rounded-2xl border border-stone-200 bg-stone-50/60 px-5 py-3 text-sm font-semibold text-stone-800 transition hover:border-emerald-300 hover:bg-white hover:text-emerald-700 dark:border-stone-800 dark:bg-stone-800/40 dark:text-stone-200 dark:hover:border-emerald-600 dark:hover:text-emerald-300">
            <span class="flex items-center gap-2.5">
                <x-heroicon-o-magnifying-glass class="h-5 w-5 text-stone-500 group-hover:text-emerald-700 dark:text-stone-400 dark:group-hover:text-emerald-300" />
                <span>Bir şey arıyorum</span>
            </span>
            <x-heroicon-o-arrow-right class="h-4 w-4 text-stone-500 transition group-hover:translate-x-0.5 group-hover:text-emerald-700 dark:text-stone-400 dark:group-hover:text-emerald-300" />
        </a>
        <a href="{{ url('/panel/ilan/yeni') }}"
           class="group flex min-h-12 items-center justify-between rounded-2xl bg-emerald-700 px-5 py-3 text-sm font-bold text-white shadow-xs transition hover:bg-emerald-800 dark:bg-emerald-500 dark:text-stone-900 dark:hover:bg-emerald-400">
            <span class="flex items-center gap-2.5">
                <x-heroicon-o-plus-circle class="h-5 w-5" />
                <span>Bir şey sunuyorum</span>
            </span>
            <x-heroicon-o-arrow-right class="h-4 w-4 transition group-hover:translate-x-0.5" />
        </a>
    </div>

    @if ($s->profilEksikleri !== [])
        @php($tamam = 2 - count($s->profilEksikleri))
        <div class="mt-6 border-t border-stone-100 pt-5 dark:border-stone-800">
            <div class="flex items-center justify-between">
                <p class="text-xs font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">Profil Durumu</p>
                <span class="text-xs font-semibold text-emerald-700 dark:text-emerald-400">2 adımdan {{ $tamam }}'i tamam</span>
            </div>
            <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-stone-100 dark:bg-stone-800">
                <div class="h-full bg-emerald-500" style="width: {{ $tamam * 50 }}%"></div>
            </div>
            <ul class="mt-3 flex flex-wrap gap-3">
                @foreach ($s->profilEksikleri as $eksik)
                    <li>
                        <a href="{{ url('/panel/profil') }}" class="inline-flex min-h-9 items-center gap-1.5 rounded-xl border border-stone-200 bg-stone-50 px-3 py-1.5 text-xs font-bold text-emerald-700 transition hover:bg-emerald-50 dark:border-stone-700 dark:bg-stone-800 dark:text-emerald-400 dark:hover:bg-emerald-950/40">
                            <span>{{ $eksik }}</span>
                            <span>→</span>
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</div>

{{-- Kendi ülkesinden GERÇEK ilanlar --}}
@if ($s->ulkeIlanlari !== null && $s->ulkeIlanlari->isNotEmpty())
    <div class="mt-8">
        <h2 class="text-xs font-bold uppercase tracking-wider text-stone-500 dark:text-stone-400">Ülkende son eklenenler</h2>
        <ul class="mt-3 space-y-2.5">
            @foreach ($s->ulkeIlanlari as $ilan)
                <li>
                    <a href="{{ route('listings.show', [$ilan, $ilan->slug]) }}"
                       class="flex min-h-14 items-center gap-3.5 rounded-2xl border border-stone-200/80 bg-white px-4 py-3 shadow-2xs transition hover:border-emerald-300 hover:shadow-xs dark:border-stone-800 dark:bg-stone-900 dark:hover:border-emerald-700">
                        <span class="min-w-0 flex-1">
                            <span class="block truncate text-sm font-semibold text-stone-800 dark:text-stone-100">{{ $ilan->title }}</span>
                            @if ($ilan->city)
                                <span class="block truncate text-xs text-stone-500 dark:text-stone-400">{{ $ilan->city }}</span>
                            @endif
                        </span>
                        @if ($ilan->price !== null)
                            <span class="shrink-0 text-sm font-bold text-emerald-700 dark:text-emerald-400">{{ number_format((float) $ilan->price, 0, ',', '.') }} {{ $ilan->currency }}</span>
                        @endif
                    </a>
                </li>
            @endforeach
        </ul>
    </div>
@endif
