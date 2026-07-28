    @if (\App\Support\HomeSections::visible('nasil_calisir'))
    {{-- Nasıl çalışır --}}
    <section class="bg-white py-14 dark:bg-stone-900" x-data x-reveal>
        <div class="mx-auto max-w-6xl px-4">
            <h2 class="text-center text-2xl font-bold text-stone-900 dark:text-stone-50">{{ setting('home.nasil_baslik') }}</h2>
            <div class="mt-10 grid gap-8 md:grid-cols-3">
                @php
                    $adimlar = [
                        ['no' => '1', 'ikon' => 'user-plus', 'baslik' => setting('home.adim1_baslik'), 'metin' => setting('home.adim1_metin')],
                        ['no' => '2', 'ikon' => 'megaphone', 'baslik' => setting('home.adim2_baslik'), 'metin' => setting('home.adim2_metin')],
                        ['no' => '3', 'ikon' => 'chat-bubble-left-right', 'baslik' => setting('home.adim3_baslik'), 'metin' => setting('home.adim3_metin')],
                    ];
                @endphp
                @foreach ($adimlar as $a)
                    <div class="text-center">
                        <div class="relative mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-emerald-700 text-white dark:bg-emerald-500 dark:text-stone-900">
                            <x-dynamic-component :component="'heroicon-o-'.$a['ikon']" class="h-6 w-6" />
                            <span class="absolute -right-2 -top-2 grid h-6 w-6 place-items-center rounded-full bg-white text-xs font-bold text-emerald-700 ring-2 ring-emerald-600 dark:bg-stone-900 dark:text-emerald-400 dark:ring-emerald-500">{{ $a['no'] }}</span>
                        </div>
                        <h3 class="mt-4 text-lg font-semibold text-stone-900 dark:text-stone-100">{{ $a['baslik'] }}</h3>
                        <p class="mt-2 text-sm text-stone-600 dark:text-stone-300">{{ $a['metin'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    </section>
    @endif
