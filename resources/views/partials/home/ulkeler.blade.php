    {{-- Popüler ülkeler (Sadeleştirilmiş & Akıllı Genişletilebilir Düzen) --}}
    @if (\App\Support\HomeSections::visible('ulkeler') && $countries->isNotEmpty())
        <section class="mx-auto max-w-6xl px-4 py-8" x-data="{ showAll: false }" x-reveal>
            <div class="flex items-end justify-between">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-400">Diaspora Ağı</span>
                    <h2 class="mt-1 text-2xl font-bold text-stone-900 md:text-3xl dark:text-stone-50">Nerede Yaşıyorsan Orada</h2>
                </div>
                <button type="button" @click="showAll = !showAll" class="text-sm font-semibold text-emerald-700 transition hover:text-emerald-800 dark:text-emerald-400 dark:hover:text-emerald-300">
                    <span x-text="showAll ? 'Daha az göster ↑' : 'Tüm ülkeler (' + {{ $countries->count() }} + ') ↓'"></span>
                </button>
            </div>

            @php
                $topCodes = ['DE', 'NL', 'GB', 'FR', 'AT', 'BE', 'CH', 'US', 'SE', 'NO', 'DK', 'AU', 'CA', 'AZ', 'KZ'];
                $featuredCountries = $countries->filter(fn($c) => in_array($c->code, $topCodes))->values();
                $otherCountries = $countries->filter(fn($c) => !in_array($c->code, $topCodes))->values();
                if ($featuredCountries->isEmpty()) {
                    $featuredCountries = $countries->take(12);
                    $otherCountries = $countries->skip(12);
                }
            @endphp

            <div class="mt-6 flex flex-wrap gap-2.5">
                @foreach ($featuredCountries as $country)
                    <a href="{{ url('/ilanlar') }}?ulke={{ $country->code }}"
                       class="inline-flex items-center gap-2 rounded-2xl border border-stone-200/90 bg-white px-4 py-2.5 text-sm font-medium text-stone-700 shadow-sm transition hover:-translate-y-0.5 hover:border-emerald-300 hover:bg-stone-50/50 hover:text-emerald-700 hover:shadow-md dark:border-stone-800 dark:bg-stone-900 dark:text-stone-200 dark:hover:border-emerald-700 dark:hover:bg-stone-800/60 dark:hover:text-emerald-400">
                        <span class="text-base">{{ $country->emoji }}</span>
                        <span>{{ $country->name_tr }}</span>
                    </a>
                @endforeach
            </div>

            @if ($otherCountries->isNotEmpty())
                <div x-show="showAll" x-collapse x-cloak class="mt-3 flex flex-wrap gap-2 border-t border-stone-200/60 pt-4 dark:border-stone-800/60">
                    @foreach ($otherCountries as $country)
                        <a href="{{ url('/ilanlar') }}?ulke={{ $country->code }}"
                           class="inline-flex items-center gap-1.5 rounded-xl border border-stone-200 bg-white/70 px-3 py-1.5 text-xs font-medium text-stone-600 shadow-xs transition hover:border-emerald-300 hover:text-emerald-700 dark:border-stone-800 dark:bg-stone-900/70 dark:text-stone-300 dark:hover:border-emerald-700 dark:hover:text-emerald-400">
                            <span>{{ $country->emoji }}</span>
                            <span>{{ $country->name_tr }}</span>
                        </a>
                    @endforeach
                </div>
            @endif
        </section>
    @endif
