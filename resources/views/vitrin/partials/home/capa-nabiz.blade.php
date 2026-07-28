    {{-- Nisoya Nabzı — klasikle aynı veri, Vitrin yüzeyi --}}
    @if ($nabizGoal)
        <section class="mx-auto max-w-6xl px-4 pt-14" x-data x-reveal>
            <a href="{{ route('nabiz') }}" class="block rounded-[22px] border border-emerald-200 bg-emerald-50/60 p-6 transition hover:border-emerald-300 hover:shadow-brand sm:p-8 dark:border-emerald-900/40 dark:bg-emerald-950/20 dark:hover:border-emerald-700">
                <div class="flex flex-wrap items-start justify-between gap-4">
                    <div>
                        <span class="inline-flex items-center gap-1.5 text-xs font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-400">
                            <span class="relative flex h-2 w-2" aria-hidden="true">
                                <span class="vitrin-pulse absolute inline-flex h-full w-full rounded-full bg-emerald-400"></span>
                                <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
                            </span>
                            Nisoya Nabzı
                        </span>
                        <h2 class="mt-1 text-xl font-extrabold text-stone-800 dark:text-stone-50">{{ $nabizGoal['baslik'] }}: {{ $nabizGoal['mevcut'] }} / {{ $nabizGoal['hedef'] }}</h2>
                        @if ($nabizGoal['odul'])
                            <p class="mt-1 text-sm font-medium text-stone-600 dark:text-stone-300">{{ $nabizGoal['odul'] }}</p>
                        @endif
                    </div>
                    <span class="text-sm font-bold text-emerald-700 hover:underline dark:text-emerald-400">Tümünü gör →</span>
                </div>
                <div class="mt-4 h-3 w-full overflow-hidden rounded-full bg-emerald-100 dark:bg-emerald-900/40">
                    <div class="h-full rounded-full bg-emerald-600 transition-all duration-700 dark:bg-emerald-500" style="width: {{ $nabizGoal['yuzde'] }}%"></div>
                </div>
                @if ($nabizAmbassadors->isNotEmpty())
                    <div class="mt-5 flex flex-wrap items-center gap-2">
                        <span class="text-xs font-semibold text-stone-500 dark:text-stone-400">Bu ayın şehir elçileri:</span>
                        @foreach ($nabizAmbassadors as $ambassador)
                            <span class="inline-flex items-center gap-1 rounded-full bg-white px-2.5 py-1 text-xs font-semibold text-stone-700 shadow-sm dark:bg-stone-800 dark:text-stone-200">
                                🏅 {{ $ambassador->name }} · {{ $ambassador->city }}
                            </span>
                        @endforeach
                    </div>
                @endif
            </a>
        </section>
    @endif
