    {{-- Kategori şeridi --}}
    @if (\App\Support\HomeSections::visible('kategoriler') && $categories->isNotEmpty())
        @php
            // Handoff kategori renk döngüsü: mavi/mint/amber/coral/mor.
            $kategoriRenkleri = [
                ['bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400'],
                ['bg-[#e7f7f1] text-[#0f9d76] dark:bg-teal-950/60 dark:text-teal-300'],
                ['bg-[#fff6e8] text-[#b9741a] dark:bg-amber-950/60 dark:text-amber-300'],
                ['bg-[#fdeeeb] text-[#c2452f] dark:bg-rose-950/60 dark:text-rose-300'],
                ['bg-[#f1ecfe] text-[#8a6bf2] dark:bg-violet-950/60 dark:text-violet-300'],
            ];
        @endphp
        <section class="mx-auto max-w-6xl px-4 pt-14" x-data x-reveal>
            {{-- MOBİL: yatay kaydırılan ikon şeridi ("uygulama" düzeni).
                 Telefonda 2 sütunlu kart ızgarası ekranın büyük kısmını yiyor
                 ve ziyaretçi bir bakışta yalnız 4 kategori görüyordu. Şerit
                 aynı yükseklikte 5-6 kategori gösterir; bir sonraki öğenin
                 yarısı görünerek kaydırmayı kendiliğinden haber eder.

                 Aynı kategoriler, aynı bağlantılar — yalnız yerleşim değişti.
                 sm:'den itibaren mevcut kart ızgarası aynen korunur. --}}
            {{-- Şeridin başlığı — mobilde bölüm etiketsiz duruyordu; ne
                 olduğunu söylemeyen bir ikon sırası ziyaretçiye "bu ne?"
                 dedirtir.

                 "En çok aranan hizmetler" DEMİYORUZ: arama istatistiğine göre
                 sıralanmıyorlar, panelden verilen sıraya göre diziliyorlar.
                 Doğru olmayan bir üstünlük iddiası yerine dürüst bir soru. --}}
            <div class="mb-3 flex items-baseline justify-between sm:hidden">
                <h2 class="text-lg font-extrabold text-stone-900 dark:text-stone-50">Ne arıyorsun?</h2>
                <a href="{{ url('/ilanlar') }}" class="text-xs font-bold text-emerald-700 dark:text-emerald-400">Tümünü gör →</a>
            </div>

            <div class="-mx-4 flex snap-x snap-mandatory gap-3 overflow-x-auto px-4 pb-1 sm:hidden [scrollbar-width:none] [&::-webkit-scrollbar]:hidden">
                @foreach ($categories->take(10) as $category)
                    <a href="{{ route('listings.category', $category) }}"
                       class="flex w-[4.75rem] shrink-0 snap-start flex-col items-center gap-1.5 text-center">
                        <span class="grid h-[4.25rem] w-[4.25rem] place-items-center rounded-2xl {{ $kategoriRenkleri[$loop->index % 5][0] }}">
                            <x-dynamic-component :component="'heroicon-o-'.\App\Support\CategoryIcon::heroicon($category->icon)" class="h-7 w-7" />
                        </span>
                        <span class="text-2xs font-semibold leading-tight text-stone-600 dark:text-stone-300">{{ $category->name }}</span>
                    </a>
                @endforeach
                <a href="{{ url('/ilanlar') }}" class="flex w-[4.75rem] shrink-0 snap-start flex-col items-center gap-1.5 text-center">
                    <span class="grid h-[4.25rem] w-[4.25rem] place-items-center rounded-2xl bg-stone-100 text-stone-500 dark:bg-stone-800 dark:text-stone-400">
                        <x-heroicon-o-ellipsis-horizontal class="h-7 w-7" />
                    </span>
                    <span class="text-2xs font-semibold leading-tight text-stone-600 dark:text-stone-300">Tümü</span>
                </a>
            </div>

            <div class="hidden grid-cols-2 gap-3.5 sm:grid sm:grid-cols-3 lg:grid-cols-5">
                @if (\App\Support\Modules::enabled('hali_saha'))
                    <a href="{{ route('football.index') }}"
                       class="group rounded-2xl border border-emerald-300 bg-emerald-50/70 p-4 shadow-sm transition hover:-translate-y-0.5 hover:border-emerald-400 hover:shadow-md dark:border-emerald-800 dark:bg-emerald-950/40">
                        <div class="flex items-center justify-between">
                            <span class="grid h-10 w-10 place-items-center rounded-xl bg-emerald-700 text-lg text-white shadow-xs">
                                ⚽
                            </span>
                            <span class="rounded-md bg-emerald-700 px-1.5 py-0.5 text-[10px] font-bold text-white uppercase tracking-wider dark:bg-emerald-500 dark:text-stone-950">Yeni</span>
                        </div>
                        <div class="mt-3 text-base font-bold text-emerald-950 dark:text-emerald-200">Halı Saha & Spor</div>
                    </a>
                @endif
                @foreach ($categories->take(9) as $category)
                    <a href="{{ route('listings.category', $category) }}"
                       class="group rounded-2xl border border-stone-200/60 bg-white p-4 shadow-brand transition hover:-translate-y-0.5 hover:border-emerald-300 dark:border-stone-800 dark:bg-stone-900 dark:hover:border-emerald-700">
                        <div class="flex items-center justify-between">
                            <span class="grid h-10 w-10 place-items-center rounded-xl {{ $kategoriRenkleri[$loop->index % 5][0] }}">
                                <x-dynamic-component :component="'heroicon-o-'.\App\Support\CategoryIcon::heroicon($category->icon)" class="h-5 w-5" />
                            </span>
                            <x-heroicon-o-arrow-right class="h-4 w-4 text-stone-300 transition group-hover:translate-x-0.5 group-hover:text-emerald-700 dark:text-stone-400 dark:group-hover:text-emerald-400" />
                        </div>
                        <div class="mt-3 text-base font-bold text-stone-800 dark:text-stone-100">{{ $category->name }}</div>
                    </a>
                @endforeach
            </div>
            {{-- Şeritte zaten bir "Tümü" karosu var; mobilde ikinci bir
                 "tüm kategoriler" bağlantısı yalnız yer kaplar. --}}
            <div class="mt-4 hidden text-right sm:block">
                <a href="{{ url('/ilanlar') }}" class="inline-flex items-center gap-1.5 text-sm font-bold text-emerald-700 hover:text-emerald-700 dark:text-emerald-400">Tüm kategoriler <x-heroicon-o-arrow-right class="h-4 w-4" /></a>
            </div>
        </section>
    @endif
