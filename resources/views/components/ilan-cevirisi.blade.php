@props(['listing'])

@php
    /*
     * YEREL DİL BLOĞU — tek kaynak.
     *
     * Klasik ve vitrin ilan detayları ayrı dosyalar; metni ikisine ayrı ayrı
     * yazsaydım biri güncellenip diğeri unutulurdu ve bir temada çeviri
     * ETİKETSİZ ya da hiç görünmez olurdu.
     *
     * `guncelCeviri()` bayat çeviriyi SİLMEZ, GİZLER: satıcı metni
     * değiştirdiyse çeviri artık başka bir ilanı anlatıyor (eski fiyat,
     * kaldırılmış detay). Bayat çeviri, çeviri olmamasından kötüdür.
     */
    $cevirmen = app(\App\Services\IlanCevirmeni::class);
    $ceviri = $cevirmen->guncelCeviri($listing);
@endphp

@if ($ceviri)
    {{-- <details> BİLEREK: sayfanın Türkçe akışını bölmüyor ama içeriği
         HTML'de duruyor, yani arama motoru okuyor. Asıl amaç zaten bu. --}}
    <details class="mt-6 rounded-2xl border border-stone-200 bg-stone-50 p-4 dark:border-stone-700 dark:bg-stone-900/40">
        <summary class="flex cursor-pointer items-center gap-2 text-sm font-semibold text-stone-800 dark:text-stone-200">
            <x-heroicon-o-language class="h-4 w-4 shrink-0" />
            {{ $cevirmen->dilAdi($ceviri->locale) }}
            <span class="rounded-full bg-stone-200 px-2 py-0.5 text-2xs font-semibold text-stone-700 dark:bg-stone-700 dark:text-stone-200">
                otomatik çeviri
            </span>
        </summary>

        <div lang="{{ $ceviri->locale }}" class="mt-3">
            <h2 class="text-base font-bold text-stone-900 dark:text-stone-100">{{ $ceviri->title }}</h2>
            <div class="prose prose-stone mt-2 max-w-none text-sm text-stone-700 dark:prose-invert dark:text-stone-300">
                {!! nl2br(e($ceviri->description)) !!}
            </div>
        </div>

        {{-- Sorumluluk sınırı: hatalı bir kelime satıcının değil, makinenin.
             Okuyan kişi yanlışı satıcıya yüklememeli. --}}
        <p class="mt-3 text-xs text-stone-500 dark:text-stone-400">
            Bu metin yapay zekâ ile çevrildi. Bağlayıcı olan Türkçe aslıdır.
        </p>
    </details>
@endif
