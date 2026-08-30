    {{-- Canlı Akış: son ilanlar arasında geçiş yapan şerit (Konteyner İçinde) --}}
    @if (\App\Support\HomeSections::visible('canli_akis') && $activityFeed->isNotEmpty())
        <div class="mx-auto max-w-6xl px-4 pt-3 sm:pt-4">
            <div
                x-data="activityTicker({{ $activityFeed->count() }})"
                class="flex items-center gap-3 rounded-2xl border border-stone-200/80 bg-white/90 px-4 py-2.5 shadow-xs backdrop-blur dark:border-stone-800/80 dark:bg-stone-900/90"
            >
                <span class="flex shrink-0 items-center gap-1.5 text-xs font-bold text-emerald-700 dark:text-emerald-400">
                    <span class="relative flex h-2 w-2">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-75"></span>
                        <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
                    </span>
                    Canlı
                </span>
                <div class="relative h-5 flex-1 overflow-hidden">
                    @foreach ($activityFeed as $i => $item)
                        <a
                            href="{{ $item['href'] }}"
                            x-show="index === {{ $i }}"
                            x-transition:enter="transition ease-out duration-500"
                            x-transition:enter-start="opacity-0 translate-y-1"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            x-transition:leave="transition ease-in duration-300"
                            x-transition:leave-start="opacity-100"
                            x-transition:leave-end="opacity-0"
                            class="absolute inset-0 flex items-center gap-2 truncate text-xs sm:text-sm text-stone-600 hover:text-emerald-700 dark:text-stone-300 dark:hover:text-emerald-400"
                            @if ($i > 0) style="display: none" @endif
                        >
                            <x-dynamic-component :component="'heroicon-o-'.$item['categoryIcon']" class="h-4 w-4 shrink-0 text-emerald-500" />
                            <span class="truncate">
                                <strong class="font-semibold text-stone-800 dark:text-stone-100">{{ $item['firstName'] }}</strong>
                                yeni bir ilan paylaştı ·
                                <span class="text-stone-500 dark:text-stone-400">{{ $item['categoryName'] }}</span>
                                @if ($item['place'])
                                    · {{ $item['flag'] }} {{ $item['place'] }}
                                @endif
                                · {{ $item['timeAgo'] }}
                            </span>
                        </a>
                    @endforeach
                </div>
            </div>
        </div>
    @endif
