    @if (\App\Support\HomeSections::visible('deger_onerileri'))
    {{-- Değer önerileri + istatistik şeridi (bento grid) --}}
    <section class="border-y border-stone-200 bg-stone-50 dark:border-stone-800 dark:bg-stone-950">
        <div class="mx-auto max-w-6xl px-4 py-14">
            <div class="grid gap-4 lg:grid-cols-4 lg:grid-rows-2">
                {{-- Öne çıkan büyük kutu — otomatik dönen vurgu mesajları (bkz.
                     App\Models\HomeHighlight, admin: Site Yönetimi → Ana Sayfa —
                     Büyük Kart). activityTicker "Canlı Akış" şeridiyle aynı Alpine
                     bileşeni + geçiş deseni yeniden kullanılıyor; tek mesaj varken
                     (count<2) otomatik olarak sabit kalır. min-h: içerik mutlak
                     konumlandığı için (kartlar üst üste biner) ebeveynin doğal
                     yüksekliğe düşmesini engeller. --}}
                <div
                    @if ($bigHighlights->count()) x-data="activityTicker({{ $bigHighlights->count() }})" @endif
                    class="relative min-h-56 overflow-hidden rounded-3xl bg-gradient-to-br from-emerald-600 to-emerald-700 text-white shadow-brand-lg lg:col-span-2 lg:row-span-2 dark:from-emerald-700 dark:to-emerald-800"
                >
                    @forelse ($bigHighlights as $i => $highlight)
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
                                {{-- Görsel-öncelikli: medya tüm kartı kaplar; başlık/metin altta okunaklı degrade üzerinde --}}
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
                                    <div class="absolute inset-x-0 bottom-0 p-6 lg:p-8">
                                        @if ($highlight->title)
                                            <h3 class="text-2xl font-bold text-white drop-shadow-md">{{ $highlight->title }}</h3>
                                        @endif
                                        @if ($highlight->text)
                                            <p class="mt-1 max-w-sm text-sm text-white/90 drop-shadow">{{ $highlight->text }}</p>
                                        @endif
                                    </div>
                                @endif
                            @else
                                <div class="p-6 lg:p-8">
                                    @if ($highlight->icon)
                                        <span class="grid h-12 w-12 place-items-center rounded-2xl bg-white/15">
                                            <x-dynamic-component :component="'heroicon-o-'.$highlight->heroicon()" class="h-6 w-6" />
                                        </span>
                                    @endif
                                    @if ($highlight->title)
                                        <h3 class="mt-6 text-2xl font-bold">{{ $highlight->title }}</h3>
                                    @endif
                                    @if ($highlight->text)
                                        <p class="mt-2 max-w-xs text-emerald-50">{{ $highlight->text }}</p>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="p-6 lg:p-8">
                            <span class="grid h-12 w-12 place-items-center rounded-2xl bg-white/15">
                                <x-heroicon-o-language class="h-6 w-6" />
                            </span>
                            <h3 class="mt-6 text-2xl font-bold">Tamamen Türkçe</h3>
                            <p class="mt-2 max-w-xs text-emerald-50">Kendi dilinde, kendi insanınla.</p>
                        </div>
                    @endforelse
                </div>

                <div class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm lg:col-span-2 dark:border-stone-800 dark:bg-stone-900 dark:shadow-none">
                    <span class="grid h-10 w-10 place-items-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-900/40 dark:text-emerald-300">
                        <x-heroicon-o-shield-check class="h-5 w-5" />
                    </span>
                    <h3 class="mt-4 font-semibold text-stone-900 dark:text-stone-100">{{ setting('home.deger2_baslik') }}</h3>
                    <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">{{ setting('home.deger2_metin') }}</p>
                </div>

                {{-- Küçük kart — aynı desen, bkz. App\Models\HomeHighlight
                     (admin: Site Yönetimi → Ana Sayfa — Küçük Kart). --}}
                <div
                    @if ($smallHighlights->count()) x-data="activityTicker({{ $smallHighlights->count() }})" @endif
                    class="relative min-h-36 overflow-hidden rounded-3xl border border-stone-200 bg-white shadow-sm dark:border-stone-800 dark:bg-stone-900 dark:shadow-none"
                >
                    @forelse ($smallHighlights as $i => $highlight)
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
                                {{-- Görsel-öncelikli: medya tüm kartı kaplar --}}
                                <div
                                    class="absolute inset-0 bg-stone-100 dark:bg-stone-800"
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
                                    <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-black/70 via-black/15 to-transparent"></div>
                                    <div class="absolute inset-x-0 bottom-0 p-4">
                                        @if ($highlight->title)
                                            <h3 class="font-semibold text-white drop-shadow">{{ $highlight->title }}</h3>
                                        @endif
                                        @if ($highlight->text)
                                            <p class="mt-0.5 text-xs text-white/90 drop-shadow">{{ $highlight->text }}</p>
                                        @endif
                                    </div>
                                @endif
                            @else
                                <div class="p-6">
                                    @if ($highlight->icon)
                                        <span class="grid h-10 w-10 place-items-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-900/40 dark:text-emerald-300">
                                            <x-dynamic-component :component="'heroicon-o-'.$highlight->heroicon()" class="h-5 w-5" />
                                        </span>
                                    @endif
                                    @if ($highlight->title)
                                        <h3 class="mt-4 font-semibold text-stone-900 dark:text-stone-100">{{ $highlight->title }}</h3>
                                    @endif
                                    @if ($highlight->text)
                                        <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">{{ $highlight->text }}</p>
                                    @endif
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="p-6">
                            <span class="grid h-10 w-10 place-items-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-900/40 dark:text-emerald-300">
                                <x-heroicon-o-sparkles class="h-5 w-5" />
                            </span>
                            <h3 class="mt-4 font-semibold text-stone-900 dark:text-stone-100">Ücretsiz ilan</h3>
                            <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">İlan vermek tamamen ücretsiz.</p>
                        </div>
                    @endforelse
                </div>

                <div class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm dark:border-stone-800 dark:bg-stone-900 dark:shadow-none">
                    <span class="grid h-10 w-10 place-items-center rounded-xl bg-emerald-50 text-emerald-600 dark:bg-emerald-900/40 dark:text-emerald-300">
                        <x-heroicon-o-globe-alt class="h-5 w-5" />
                    </span>
                    <h3 class="mt-4 font-semibold text-stone-900 dark:text-stone-100">{{ $stats['countries'] }} ülke · {{ $stats['cities'] }} şehir</h3>
                    <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">{{ $stats['categories'] }} kategoride hizmet ve ürün.</p>
                </div>
            </div>
        </div>
    </section>
    @endif

    {{-- Faz İ2 ("2. Tasarım" pilotu): istatistik kartındaki "22 ülke" metnini
         gerçek veriyle gösteren Nabız Haritası. Sadece yeni tasarım modunda —
         bkz. /yonetim Tasarım Modu. --}}
    @if (\App\Support\Tema::tasarimModu() === 'yeni')
        <section class="border-b border-stone-200 bg-white dark:border-stone-800 dark:bg-stone-950">
            <div class="mx-auto max-w-6xl px-4 py-14">
                <x-pulse-map :countries="$pulseCountries" />
            </div>
        </section>
    @endif
