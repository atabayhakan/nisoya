<x-layouts.app title="İlanlarım — Nisoya">
    @php
        $badge = ['success' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300', 'warning' => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300', 'danger' => 'bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-300', 'gray' => 'bg-stone-100 text-stone-600 dark:bg-stone-800 dark:text-stone-300'];
    @endphp
    <div class="mx-auto max-w-5xl px-4 py-10">
        <x-panel.back-link />
        <div class="flex items-center justify-between gap-4">
            <h1 class="text-2xl font-bold text-stone-900 dark:text-stone-50">İlanlarım</h1>
            <a href="{{ route('panel.listings.create') }}" class="rounded-lg bg-emerald-600 px-4 py-2.5 font-semibold text-white transition hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-400 dark:text-stone-900">+ Yeni İlan</a>
        </div>

        @if (session('status'))
            <div class="mt-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">{{ session('status') }}</div>
        @endif

        @forelse ($listings as $listing)
            @if ($loop->first)<div class="mt-6 space-y-3">@endif
            <div class="flex flex-col gap-3 rounded-2xl border border-stone-200 bg-white p-3 shadow-sm sm:flex-row sm:items-center sm:gap-4 dark:border-stone-800 dark:bg-stone-900 dark:shadow-none">
                <div class="flex items-center gap-4">
                    <div class="h-16 w-16 shrink-0 overflow-hidden rounded-lg bg-stone-100 dark:bg-stone-800">
                        @if ($listing->coverImage)
                            <img src="{{ $listing->coverImage->url('thumb') }}" alt=""
                                 style="object-position: {{ $listing->coverImage->objectPosition() }}"
                                 class="h-full w-full object-cover">
                        @else
                            @php($fallbackIcon = \App\Support\CategoryIcon::heroicon($listing->category?->parent?->icon ?? $listing->category?->icon))
                            <div class="flex h-full w-full items-center justify-center text-stone-300 dark:text-stone-600">
                                <x-dynamic-component :component="'heroicon-o-'.$fallbackIcon" class="h-7 w-7" />
                            </div>
                        @endif
                    </div>
                    <div class="min-w-0 flex-1">
                        <div class="flex items-center gap-2">
                            <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $badge[$listing->status->getColor()] ?? $badge['gray'] }}">{{ $listing->status->getLabel() }}</span>
                            <span class="inline-flex items-center gap-0.5 text-xs text-stone-400 dark:text-stone-500"><x-heroicon-o-eye class="h-3.5 w-3.5" /> {{ $listing->views_count }}</span>
                        </div>
                        <a href="{{ route('listings.show', [$listing, $listing->slug]) }}" class="mt-1 block truncate font-semibold text-stone-800 hover:text-emerald-700 dark:text-stone-100 dark:hover:text-emerald-400">{{ $listing->title }}</a>
                        <p class="whitespace-nowrap text-sm text-stone-500 dark:text-stone-400">
                            @if ($listing->price !== null){{ number_format((float) $listing->price, 2) }} {{ $listing->currency }} <span class="text-stone-400 dark:text-stone-500">{{ $listing->price_unit->suffix() }}</span>@else Görüşülür @endif
                        </p>
                    </div>
                </div>
                <div class="flex shrink-0 flex-wrap items-center gap-2 sm:justify-end">
                    @if ($listing->isCurrentlyFeatured())
                        <span class="inline-flex items-center gap-1 rounded-lg bg-amber-100 px-2 py-1 text-xs font-semibold text-amber-700 dark:bg-amber-900/40 dark:text-amber-300"><x-heroicon-s-star class="h-3.5 w-3.5" /> Öne çıkan</span>
                    @elseif ($listing->has_pending_feature)
                        <span class="inline-flex items-center gap-1 rounded-lg bg-stone-100 px-2 py-1 text-xs text-stone-500 dark:bg-stone-800 dark:text-stone-400"><x-heroicon-o-clock class="h-3.5 w-3.5" /> İncelemede</span>
                    @elseif ($listing->status->value === 'aktif')
                        <form method="POST" action="{{ route('panel.listings.feature', $listing) }}" class="flex items-center gap-1">
                            @csrf
                            <select name="days" class="rounded-lg border-stone-300 py-1 pl-2 pr-7 text-xs focus:border-amber-500 focus:ring-amber-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                                <option value="7">7 gün</option>
                                <option value="30">30 gün</option>
                            </select>
                            <button type="submit" class="inline-flex items-center gap-1 rounded-lg border border-amber-300 px-2 py-1 text-xs font-medium text-amber-700 transition hover:bg-amber-50 dark:border-amber-700 dark:text-amber-300 dark:hover:bg-amber-900/30"><x-heroicon-o-star class="h-3.5 w-3.5" /> Öne çıkar</button>
                        </form>
                    @endif
                    <a href="{{ route('panel.listings.edit', $listing) }}" class="rounded-lg border border-stone-300 px-3 py-1.5 text-sm font-medium text-stone-600 hover:bg-stone-50 dark:border-stone-700 dark:text-stone-300 dark:hover:bg-stone-800">Düzenle</a>
                </div>
            </div>
            @if ($loop->last)</div>{{ $listings->links() }}@endif
        @empty
            <x-empty-state
                illustration="listing"
                title="Henüz ilanın yok"
                description="İlk ilanını ver, yeteneğini paraya dönüştür."
                cta-text="+ İlk İlanını Ver"
                :cta-href="route('panel.listings.create')"
            />
        @endforelse
    </div>
</x-layouts.app>
