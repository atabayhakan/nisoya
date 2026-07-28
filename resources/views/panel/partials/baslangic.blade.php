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
<div class="mt-8 rounded-2xl border border-stone-200 bg-white p-6 dark:border-stone-800 dark:bg-stone-900">
    <h2 class="text-lg font-bold text-stone-900 dark:text-stone-50">Nereden başlamak istersin?</h2>
    <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">İki yönde de çalışır: birini arayabilir ya da kendini duyurabilirsin.</p>

    <div class="mt-4 grid gap-3 sm:grid-cols-2">
        <a href="{{ url('/ilanlar') }}"
           class="flex min-h-11 items-center justify-center rounded-xl border border-stone-300 px-4 py-3 text-sm font-semibold text-stone-700 transition hover:border-emerald-400 hover:text-emerald-700 dark:border-stone-700 dark:text-stone-200 dark:hover:border-emerald-600 dark:hover:text-emerald-400">
            Bir şey arıyorum
        </a>
        <a href="{{ url('/panel/ilan/yeni') }}"
           class="flex min-h-11 items-center justify-center rounded-xl bg-emerald-600 px-4 py-3 text-sm font-semibold text-white transition hover:bg-emerald-700 dark:bg-emerald-500 dark:text-stone-900 dark:hover:bg-emerald-400">
            Bir şey sunuyorum
        </a>
    </div>

    @if ($s->profilEksikleri !== [])
        @php($tamam = 2 - count($s->profilEksikleri))
        <div class="mt-5 border-t border-stone-200 pt-4 dark:border-stone-800">
            <p class="text-sm font-semibold text-stone-700 dark:text-stone-200">Profilin: 2 adımdan {{ $tamam }}'i tamam</p>
            <ul class="mt-1.5 space-y-1">
                @foreach ($s->profilEksikleri as $eksik)
                    <li>
                        <a href="{{ url('/panel/profil') }}" class="inline-flex min-h-11 items-center text-sm text-emerald-700 hover:underline dark:text-emerald-400">
                            {{ $eksik }} →
                        </a>
                    </li>
                @endforeach
            </ul>
        </div>
    @endif
</div>

{{-- Kendi ülkesinden GERÇEK ilanlar. Ülkesinde ilan yoksa global/rastgele
     ilanla DOLDURULMAZ — boş kalır ve bu açıkça söylenir. --}}
@if ($s->ulkeIlanlari !== null && $s->ulkeIlanlari->isNotEmpty())
    <h2 class="mt-8 text-sm font-bold uppercase tracking-wide text-stone-500 dark:text-stone-400">Ülkende son eklenenler</h2>
    <ul class="mt-3 space-y-2">
        @foreach ($s->ulkeIlanlari as $ilan)
            <li>
                <a href="{{ route('listings.show', [$ilan, $ilan->slug]) }}"
                   class="flex min-h-14 items-center gap-3 rounded-xl border border-stone-200 bg-white px-4 transition hover:border-emerald-300 dark:border-stone-800 dark:bg-stone-900 dark:hover:border-emerald-700">
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
@endif
