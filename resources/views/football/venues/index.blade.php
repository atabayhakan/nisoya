<x-layouts.app>
    <div class="mx-auto max-w-6xl px-4 py-8">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <a href="{{ route('football.city', \Illuminate\Support\Str::slug($currentCity)) }}" class="text-xs font-semibold text-emerald-700 hover:underline dark:text-emerald-400">
                    ← {{ $currentCity }} Futbol Ana Sayfası
                </a>
                <h1 class="mt-1 text-2xl font-black tracking-tight text-stone-900 sm:text-3xl dark:text-stone-100">
                    {{ $currentCity }} Halı Sahaları & Tesisler
                </h1>
                <p class="text-sm text-stone-600 dark:text-stone-400">
                    Şehrindeki en iyi halı sahaları, zemin ve tesis özelliklerini, fiyatları ve oyuncu yorumlarını incele.
                </p>
            </div>
            <a href="{{ route('football.venues.create') }}"
               class="inline-flex items-center gap-2 rounded-2xl bg-emerald-600 px-4 py-2.5 text-sm font-bold text-white shadow-md transition hover:bg-emerald-500">
                + Halı Saha Ekle
            </a>
        </div>

        {{-- Arama & Filtreleme --}}
        <form method="GET" class="mt-6 flex flex-wrap items-center gap-3 rounded-2xl border border-stone-200 bg-white p-4 shadow-sm dark:border-stone-800 dark:bg-stone-900">
            <div class="min-w-[200px] flex-1">
                <input type="text" name="q" value="{{ request('q') }}" placeholder="Saha adı veya semt/adres ara..."
                       class="w-full rounded-xl border border-stone-200 bg-stone-50 px-3.5 py-2 text-sm text-stone-900 focus:border-emerald-500 focus:bg-white focus:outline-none dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
            </div>
            <div>
                <select name="pitch_type" class="rounded-xl border border-stone-200 bg-stone-50 px-3 py-2 text-sm text-stone-900 focus:border-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                    <option value="">Tüm Saha Tipleri</option>
                    <option value="kapali" @selected(request('pitch_type') === 'kapali')>Kapalı Saha</option>
                    <option value="acik" @selected(request('pitch_type') === 'acik')>Açık Saha</option>
                    <option value="yari_acik" @selected(request('pitch_type') === 'yari_acik')>Yarı Açık</option>
                </select>
            </div>
            <div>
                <select name="surface_type" class="rounded-xl border border-stone-200 bg-stone-50 px-3 py-2 text-sm text-stone-900 focus:border-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                    <option value="">Tüm Zeminler</option>
                    <option value="suni_cim" @selected(request('surface_type') === 'suni_cim')>Suni Çim</option>
                    <option value="dogal_cim" @selected(request('surface_type') === 'dogal_cim')>Doğal Çim</option>
                    <option value="parke" @selected(request('surface_type') === 'parke')>Parke / Salon</option>
                </select>
            </div>
            <button type="submit" class="rounded-xl bg-stone-900 px-4 py-2 text-sm font-semibold text-white transition hover:bg-stone-800 dark:bg-stone-700 dark:hover:bg-stone-600">
                Filtrele
            </button>
        </form>

        @if ($venues->isEmpty())
            <div class="mt-8 rounded-3xl border border-dashed border-stone-200 p-12 text-center text-stone-500 dark:border-stone-800 dark:text-stone-400">
                <p class="text-base font-semibold">Bu şehirde aradığınız kriterlere uygun halı saha bulunamadı.</p>
                <a href="{{ route('football.venues.create') }}" class="mt-3 inline-block font-semibold text-emerald-700 hover:underline dark:text-emerald-400">
                    Bildiğin halı sahayı hemen ekle!
                </a>
            </div>
        @else
            <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                @foreach ($venues as $venue)
                    <div class="flex flex-col justify-between rounded-3xl border border-stone-200 bg-white p-5 shadow-sm transition hover:border-emerald-300 dark:border-stone-800 dark:bg-stone-900">
                        <div>
                            @if ($venue->cover_image_path)
                                <div class="h-40 w-full overflow-hidden rounded-2xl bg-stone-100">
                                    <img src="{{ Storage::url($venue->cover_image_path) }}" alt="{{ $venue->name }}" class="h-full w-full object-cover">
                                </div>
                            @endif

                            <div class="mt-3 flex items-start justify-between gap-2">
                                <div>
                                    <h2 class="text-lg font-bold text-stone-900 dark:text-stone-100">
                                        <a href="{{ route('football.venues.show', ['city' => \Illuminate\Support\Str::slug($venue->city), 'venue' => $venue->slug]) }}" class="hover:text-emerald-700 dark:hover:text-emerald-400">
                                            {{ $venue->name }}
                                        </a>
                                    </h2>
                                    <p class="mt-1 text-xs text-stone-500 line-clamp-1 dark:text-stone-400">
                                        📍 {{ $venue->address }}
                                    </p>
                                </div>
                                <span class="inline-flex items-center gap-1 rounded-xl bg-amber-50 px-2 py-1 text-xs font-black text-amber-700 dark:bg-amber-950/40 dark:text-amber-300">
                                    ⭐ {{ number_format($venue->rating, 1) }}
                                </span>
                            </div>

                            {{-- Özellik Etiketleri --}}
                            <div class="mt-3 flex flex-wrap gap-1.5">
                                <span class="rounded-lg bg-stone-100 px-2 py-0.5 text-3xs font-semibold text-stone-700 dark:bg-stone-800 dark:text-stone-300">
                                    {{ \App\Models\FootballVenue::PITCH_TYPES[$venue->pitch_type] ?? $venue->pitch_type }}
                                </span>
                                <span class="rounded-lg bg-stone-100 px-2 py-0.5 text-3xs font-semibold text-stone-700 dark:bg-stone-800 dark:text-stone-300">
                                    {{ \App\Models\FootballVenue::SURFACE_TYPES[$venue->surface_type] ?? $venue->surface_type }}
                                </span>
                                @if (! empty($venue->features) && is_array($venue->features))
                                    @foreach (array_slice($venue->features, 0, 3) as $feat)
                                        <span class="rounded-lg bg-emerald-50 px-2 py-0.5 text-3xs font-semibold text-emerald-800 dark:bg-emerald-950/40 dark:text-emerald-300">
                                            {{ \App\Models\FootballVenue::FEATURE_OPTIONS[$feat] ?? $feat }}
                                        </span>
                                    @endforeach
                                @endif
                            </div>

                            @if ($venue->price_info)
                                <p class="mt-3 text-xs font-bold text-emerald-700 dark:text-emerald-400">
                                    💰 {{ $venue->price_info }}
                                </p>
                            @endif
                        </div>

                        <div class="mt-4 border-t border-stone-100 pt-3 dark:border-stone-800">
                            <a href="{{ route('football.venues.show', ['city' => \Illuminate\Support\Str::slug($venue->city), 'venue' => $venue->slug]) }}"
                               class="flex w-full items-center justify-center rounded-xl bg-stone-50 py-2 text-xs font-bold text-stone-800 transition hover:bg-emerald-50 hover:text-emerald-800 dark:bg-stone-800 dark:text-stone-200 dark:hover:bg-emerald-950/40 dark:hover:text-emerald-300">
                                Saha Detayı & Yorumlar ({{ $venue->reviews_count }}) →
                            </a>
                        </div>
                    </div>
                @endforeach
            </div>

            <div class="mt-8">
                {{ $venues->links() }}
            </div>
        @endif
    </div>
</x-layouts.app>
