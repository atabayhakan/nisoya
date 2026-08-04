@props(['adimlar'])

{{-- İlan yaşam döngüsü şeridi — saf CSS animasyon, paket yok.

     wire:ignore ŞART: bu bileşen Livewire sayfasının içinde yaşıyor. Onsuz
     her Livewire güncellemesinde (arama kutusuna her harf) DOM yeniden
     kurulur ve animasyon baştan başlar — klasik tuzak.

     prefers-reduced-motion: animasyon kapanır ama diyagram SON KARESİNDE
     donmuş kalır. Hareketi kapatmak bilgiyi silmek değildir.

     Sayılar canlı sorgudan (bkz. SurecSeridi) — hiçbir rakam elle yazılmadı. --}}
<div wire:ignore x-data="{ tur: 0 }" class="not-prose">
    <style>
        @keyframes surecBelir {
            from { opacity: 0; transform: translateY(6px); }
            to   { opacity: 1; transform: translateY(0); }
        }
        @keyframes surecCizgi {
            from { transform: scaleX(0); }
            to   { transform: scaleX(1); }
        }
        .surec-adim { opacity: 0; animation: surecBelir .45s ease-out forwards; }
        .surec-cizgi { transform-origin: left; animation: surecCizgi .35s ease-out forwards; }

        @media (prefers-reduced-motion: reduce) {
            /* Hareket kapalı ama diyagram GÖRÜNÜR: son kare. */
            .surec-adim, .surec-cizgi { animation: none; opacity: 1; transform: none; }
        }
    </style>

    <div class="flex items-center justify-between gap-2">
        <p class="text-xs text-gray-500 dark:text-gray-400">
            Sayılar canlı: örnek (demo) ilanlar sayılmaz.
        </p>
        <button
            type="button"
            @click="tur++"
            class="inline-flex items-center gap-1 rounded-lg px-2 py-1 text-xs font-medium text-gray-500 transition hover:bg-gray-100 hover:text-gray-700 dark:text-gray-400 dark:hover:bg-white/5 dark:hover:text-gray-200"
        >
            ↻ Yeniden oynat
        </button>
    </div>

    {{-- :key ile tur değişince blok yeniden kurulur → animasyon baştan başlar. --}}
    <template x-for="_ in [tur]" :key="tur">
        <div class="mt-3 space-y-4">
            {{-- Ana hat --}}
            <div class="flex flex-wrap items-stretch gap-2">
                @foreach (collect($adimlar)->where('dal', 'ana')->values() as $i => $adim)
                    @if ($i > 0)
                        <div class="hidden self-center sm:block" aria-hidden="true">
                            <div class="surec-cizgi h-0.5 w-8 rounded bg-primary-300 dark:bg-primary-700"
                                 style="animation-delay: {{ $i * 0.45 - 0.1 }}s"></div>
                        </div>
                    @endif

                    <div class="surec-adim min-w-0 flex-1 rounded-xl border border-gray-200 bg-white p-3 dark:border-white/10 dark:bg-white/5"
                         style="animation-delay: {{ $i * 0.45 }}s">
                        <div class="flex items-baseline justify-between gap-2">
                            <span class="text-sm font-semibold text-gray-950 dark:text-white">{{ $adim['etiket'] }}</span>
                            <span class="shrink-0 text-lg font-bold text-primary-600 dark:text-primary-400">{{ $adim['adet'] }}</span>
                        </div>
                        <p class="mt-1 text-xs leading-relaxed text-gray-500 dark:text-gray-400">{{ $adim['aciklama'] }}</p>
                    </div>
                @endforeach
            </div>

            {{-- Yan dallar: çıkış durumları --}}
            <div class="flex flex-wrap gap-2">
                @foreach (collect($adimlar)->where('dal', 'yan')->values() as $i => $adim)
                    <div class="surec-adim min-w-0 flex-1 rounded-xl border border-dashed border-gray-300 p-3 dark:border-white/10"
                         style="animation-delay: {{ 1.4 + $i * 0.2 }}s">
                        <div class="flex items-baseline justify-between gap-2">
                            <span class="text-sm font-medium text-gray-600 dark:text-gray-300">{{ $adim['etiket'] }}</span>
                            <span class="shrink-0 text-base font-semibold text-gray-500 dark:text-gray-400">{{ $adim['adet'] }}</span>
                        </div>
                        <p class="mt-1 text-xs leading-relaxed text-gray-500 dark:text-gray-400">{{ $adim['aciklama'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </template>
</div>
