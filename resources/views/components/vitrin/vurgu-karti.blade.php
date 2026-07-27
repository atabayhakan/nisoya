@props(['highlights', 'buyuk' => false, 'yedekIkon' => 'sparkles', 'yedekBaslik' => '', 'yedekMetin' => ''])

{{-- VİTRİN VURGU KARTI (P3-b) — home_highlights kayıtlarını Vitrin diliyle
     gösterir. Backend'e dokunulmaz: aynı model, aynı forSlot() verisi, aynı
     medya render'ı (partials/highlight-media TEK kopya olarak yeniden
     kullanılır — vitrin ağacına kopyalanmaz).

     Klasikten devralınan sözleşmeler:
     - activityTicker: kayıtlar arasında geçiş (count<2 veya reduced-motion → sabit)
     - İÇ İÇE ikinci ticker: bir kaydın birden çok medyası varsa onları döndürür
     - min-h: tüm slaytlar absolute olduğu için ebeveyn yüksekliği çökmesin
     - FOUC: ilk slayt dışındakiler inline style="display:none" ile başlar
       (klasik desen; x-cloak değil — iki desen de geçerli, burada klasikle
       birebir aynısı korunur ki davranış farkı doğmasın) --}}
<div
    @if ($highlights->count()) x-data="activityTicker({{ $highlights->count() }})" @endif
    @class([
        'relative overflow-hidden rounded-[22px] text-white shadow-brand-lg',
        'min-h-[224px] bg-gradient-to-br from-emerald-600 to-emerald-700 lg:col-span-2 lg:row-span-2 dark:from-emerald-700 dark:to-emerald-800' => $buyuk,
        'min-h-[152px] bg-gradient-to-br from-stone-800 to-stone-900' => ! $buyuk,
    ])
>
    @forelse ($highlights as $i => $highlight)
        <div
            class="absolute inset-0"
            x-show="index === {{ $i }}"
            x-transition:enter="transition ease-out duration-500"
            x-transition:enter-start="opacity-0 translate-y-1"
            x-transition:enter-end="opacity-100 translate-y-0"
            x-transition:leave="transition ease-in duration-300"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @if ($i > 0) style="display: none" @endif
        >
            @if ($highlight->hasMedia())
                @php $media = $highlight->media; @endphp
                <div
                    class="absolute inset-0 bg-black/10"
                    @if (count($media) > 1) x-data="activityTicker({{ count($media) }})" @endif
                >
                    @foreach ($media as $mi => $item)
                        <div
                            class="absolute inset-0"
                            @if (count($media) > 1)
                                x-show="index === {{ $mi }}"
                                x-transition:enter="transition ease-out duration-500"
                                x-transition:enter-start="opacity-0"
                                x-transition:enter-end="opacity-100"
                                x-transition:leave="transition ease-in duration-300"
                                x-transition:leave-start="opacity-100"
                                x-transition:leave-end="opacity-0"
                                @if ($mi > 0) style="display: none" @endif
                            @endif
                        >
                            @include('partials.highlight-media', ['item' => $item])
                        </div>
                    @endforeach
                </div>
                @if ($highlight->title || $highlight->text)
                    <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-black/75 via-black/25 to-transparent"></div>
                    <div class="absolute inset-x-0 bottom-0 {{ $buyuk ? 'p-6 lg:p-7' : 'p-5' }}">
                        @if ($highlight->title)
                            <h3 class="{{ $buyuk ? 'text-2xl' : 'text-base' }} font-extrabold tracking-tight text-white drop-shadow-md">{{ $highlight->title }}</h3>
                        @endif
                        @if ($highlight->text)
                            <p class="mt-1 max-w-sm {{ $buyuk ? 'text-sm' : 'text-xs' }} font-medium text-white/90 drop-shadow">{{ $highlight->text }}</p>
                        @endif
                    </div>
                @endif
            @else
                <div class="{{ $buyuk ? 'p-6 lg:p-7' : 'p-5' }}">
                    @if ($highlight->icon)
                        <span class="grid {{ $buyuk ? 'h-11 w-11' : 'h-9 w-9' }} place-items-center rounded-xl bg-white/15">
                            <x-dynamic-component :component="'heroicon-o-'.$highlight->heroicon()" class="{{ $buyuk ? 'h-6 w-6' : 'h-5 w-5' }}" />
                        </span>
                    @endif
                    @if ($highlight->title)
                        <h3 class="{{ $buyuk ? 'mt-5 text-2xl' : 'mt-3 text-base' }} font-extrabold tracking-tight">{{ $highlight->title }}</h3>
                    @endif
                    @if ($highlight->text)
                        <p class="mt-1.5 max-w-xs {{ $buyuk ? 'text-sm' : 'text-xs' }} font-medium text-white/80">{{ $highlight->text }}</p>
                    @endif
                </div>
            @endif
        </div>
    @empty
        {{-- Kayıt yoksa panel varsayılanlarıyla anlamlı bir kart kalır --}}
        <div class="{{ $buyuk ? 'p-6 lg:p-7' : 'p-5' }}">
            <span class="grid {{ $buyuk ? 'h-11 w-11' : 'h-9 w-9' }} place-items-center rounded-xl bg-white/15">
                <x-dynamic-component :component="'heroicon-o-'.$yedekIkon" class="{{ $buyuk ? 'h-6 w-6' : 'h-5 w-5' }}" />
            </span>
            <h3 class="{{ $buyuk ? 'mt-5 text-2xl' : 'mt-3 text-base' }} font-extrabold tracking-tight">{{ $yedekBaslik }}</h3>
            <p class="mt-1.5 max-w-xs {{ $buyuk ? 'text-sm' : 'text-xs' }} font-medium text-white/80">{{ $yedekMetin }}</p>
        </div>
    @endforelse
</div>
