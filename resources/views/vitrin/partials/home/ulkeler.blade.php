    {{-- Ülkeler (Vitrin Teması — Profesyonel Bento Düzeni & Akıllı Çekmece) --}}
    @if (\App\Support\HomeSections::visible('ulkeler') && $countries->isNotEmpty())
        @php
            $topCodes = ['DE', 'NL', 'GB', 'FR', 'AT', 'BE', 'CH', 'US', 'SE', 'NO', 'DK', 'AU', 'CA', 'AZ', 'KZ', 'KG'];
            $featuredCountries = $countries->filter(fn($c) => in_array($c->code, $topCodes))->values();
            $otherCountries = $countries->filter(fn($c) => !in_array($c->code, $topCodes))->values();
            if ($featuredCountries->isEmpty()) {
                $featuredCountries = $countries->take(12);
                $otherCountries = $countries->skip(12);
            }
        @endphp

        <section class="mx-auto max-w-6xl px-4 pt-14" x-data="{ showAll: false, search: '' }" x-reveal>
            <div class="rounded-3xl border border-stone-200/90 bg-white p-6 sm:p-10 shadow-sm dark:border-stone-800 dark:bg-stone-900">
                <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-600/20 dark:bg-emerald-950/60 dark:text-emerald-300">
                            🌍 Global Diaspora Ağı
                        </span>
                        <h2 class="mt-2 text-xl sm:text-2xl font-bold tracking-tight text-stone-900 dark:text-stone-100">
                            Nerede yaşıyorsan orada
                        </h2>
                        <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">
                            Ülkeni seç, şehrindeki Türkçe konuşan satıcıları, ustaları ve ilanları gör.
                        </p>
                    </div>
                    @if ($otherCountries->isNotEmpty())
                        <button type="button" @click="showAll = !showAll"
                                class="inline-flex items-center gap-1.5 rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-2 text-xs font-semibold text-stone-700 transition hover:border-emerald-300 hover:bg-stone-100 hover:text-emerald-700 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-200 dark:hover:border-emerald-600 dark:hover:text-emerald-300">
                            <span x-text="showAll ? 'Daha Az Göster ↑' : 'Tüm Ülkeler (' + {{ $countries->count() }} + ') ↓'"></span>
                        </button>
                    @endif
                </div>

                {{-- Öne Çıkan Ülke Kartları (Grid) --}}
                <div class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6">
                    @foreach ($featuredCountries as $country)
                        <a href="{{ url('/ilanlar') }}?ulke={{ $country->code }}"
                           class="group flex items-center gap-3 rounded-2xl border border-stone-200/80 bg-stone-50/70 p-3 shadow-2xs transition hover:-translate-y-0.5 hover:border-emerald-300 hover:bg-white hover:shadow-sm dark:border-stone-800 dark:bg-stone-800/40 dark:hover:border-emerald-700 dark:hover:bg-stone-800">
                            <span class="grid h-8 w-8 shrink-0 place-items-center rounded-xl bg-white text-base shadow-xs dark:bg-stone-900">
                                {{ $country->emoji }}
                            </span>
                            <div class="min-w-0 flex-1">
                                <span class="block truncate text-xs font-bold text-stone-800 group-hover:text-emerald-700 dark:text-stone-200 dark:group-hover:text-emerald-300">
                                    {{ $country->name_tr }}
                                </span>
                                <span class="text-[10px] font-medium text-stone-500 dark:text-stone-400">İlanları Gör →</span>
                            </div>
                        </a>
                    @endforeach
                </div>

                {{-- Genişletilebilir Diğer Ülkeler Bölümü (Alpine Collapse + Canlı Arama) --}}
                @if ($otherCountries->isNotEmpty())
                    <div x-show="showAll" x-collapse x-cloak class="mt-6 border-t border-stone-100 pt-6 dark:border-stone-800">
                        <div class="mb-4 max-w-xs">
                            <input type="text" x-model="search" placeholder="Ülke adı yazarak filtrele..."
                                   class="w-full rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-2 text-xs text-stone-800 placeholder-stone-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100 dark:placeholder-stone-500" />
                        </div>

                        <div class="flex flex-wrap gap-2">
                            @foreach ($otherCountries as $country)
                                <a href="{{ url('/ilanlar') }}?ulke={{ $country->code }}"
                                   x-show="search === '' || '{{ mb_strtolower($country->name_tr) }}'.includes(search.toLowerCase()) || '{{ strtolower($country->code) }}'.includes(search.toLowerCase())"
                                   class="inline-flex items-center gap-1.5 rounded-xl border border-stone-200/80 bg-stone-50 px-3 py-1.5 text-xs font-medium text-stone-700 transition hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-700 dark:border-stone-700 dark:bg-stone-800/80 dark:text-stone-300 dark:hover:border-emerald-600 dark:hover:text-emerald-300">
                                    <span>{{ $country->emoji }}</span>
                                    <span>{{ $country->name_tr }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            </div>
        </section>
    @endif
