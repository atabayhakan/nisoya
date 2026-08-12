@props(['yorum' => null, 'cumle' => ''])

@php
    /*
     * ARAMA YORUMU ŞERİDİ — tek kaynak (klasik + vitrin).
     *
     * Doğal dille arama, kullanıcının yazdığı cümleyi süzgeçlere ayırıyor.
     * Bunu SÖYLEMEDEN yapmak, kişiye yazdığından başka bir sonuç kümesi
     * gösterip sebebini gizlemek olurdu — aramaya olan güveni bitiren şey
     * tam olarak budur.
     *
     * "Aynen ara" bağlantısı çıkış yolu: yorum yanlışsa kullanıcı tek tıkla
     * ham aramaya döner. Çıkışı olmayan bir otomatik davranış, özellik değil
     * tuzaktır.
     */
    $parcalar = [];

    if ($yorum) {
        $etiketler = [
            'q' => 'Aranan',
            'kategori' => 'Kategori',
            'tip' => 'Tür',
            'sehir' => 'Şehir',
            'ulke' => 'Ülke',
            'max' => 'En fazla',
        ];

        foreach ($etiketler as $alan => $etiket) {
            if (filled($yorum[$alan] ?? null)) {
                $parcalar[] = $etiket.': '.$yorum[$alan];
            }
        }
    }
@endphp

@if ($parcalar !== [])
    <div {{ $attributes->merge(['class' => 'rounded-xl border border-stone-200 bg-stone-50 px-4 py-3 dark:border-stone-700 dark:bg-stone-900/40']) }}>
        <div class="flex flex-wrap items-center gap-x-2 gap-y-1.5 text-sm">
            <span class="font-semibold text-stone-700 dark:text-stone-300">Şunu anladım:</span>
            @foreach ($parcalar as $parca)
                <span class="rounded-full bg-white px-2.5 py-1 text-xs font-medium text-stone-700 ring-1 ring-stone-200 dark:bg-stone-800 dark:text-stone-200 dark:ring-stone-700">{{ $parca }}</span>
            @endforeach
            <a href="{{ request()->fullUrlWithQuery(['ham' => 1]) }}"
               class="text-xs font-semibold text-emerald-700 underline underline-offset-2 hover:no-underline dark:text-emerald-400">
                Aynen “{{ \Illuminate\Support\Str::limit($cumle, 40) }}” diye ara
            </a>
        </div>
    </div>
@endif
