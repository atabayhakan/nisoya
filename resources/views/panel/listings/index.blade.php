<x-layouts.app title="İlanlarım — Nisoya">
    @php
        $badge = ['success' => 'bg-emerald-100 text-emerald-700', 'warning' => 'bg-amber-100 text-amber-700', 'danger' => 'bg-red-100 text-red-700', 'gray' => 'bg-stone-100 text-stone-600'];
    @endphp
    <div class="mx-auto max-w-5xl px-4 py-10">
        <div class="flex items-center justify-between gap-4">
            <h1 class="text-2xl font-bold text-stone-900">İlanlarım</h1>
            <a href="{{ route('panel.listings.create') }}" class="rounded-lg bg-emerald-600 px-4 py-2.5 font-semibold text-white transition hover:bg-emerald-700">+ Yeni İlan</a>
        </div>

        @if (session('status'))
            <div class="mt-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
        @endif

        @forelse ($listings as $listing)
            @if ($loop->first)<div class="mt-6 space-y-3">@endif
            <div class="flex items-center gap-4 rounded-2xl border border-stone-200 bg-white p-3 shadow-sm">
                <div class="h-16 w-16 shrink-0 overflow-hidden rounded-lg bg-stone-100">
                    @if ($listing->coverImage)
                        <img src="{{ Storage::url($listing->coverImage->path) }}" alt="" class="h-full w-full object-cover">
                    @else
                        <div class="flex h-full w-full items-center justify-center text-2xl text-stone-300">🧰</div>
                    @endif
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2">
                        <span class="rounded-full px-2 py-0.5 text-xs font-medium {{ $badge[$listing->status->getColor()] ?? $badge['gray'] }}">{{ $listing->status->getLabel() }}</span>
                        <span class="text-xs text-stone-400">👁 {{ $listing->views_count }}</span>
                    </div>
                    <a href="{{ route('listings.show', [$listing, $listing->slug]) }}" class="mt-1 block truncate font-semibold text-stone-800 hover:text-emerald-700">{{ $listing->title }}</a>
                    <p class="text-sm text-stone-500">
                        @if ($listing->price !== null){{ number_format((float) $listing->price, 2) }} {{ $listing->currency }} <span class="text-stone-400">{{ $listing->price_unit->suffix() }}</span>@else Görüşülür @endif
                    </p>
                </div>
                <div class="flex shrink-0 flex-wrap items-center justify-end gap-2">
                    @if ($listing->isCurrentlyFeatured())
                        <span class="rounded-lg bg-amber-100 px-2 py-1 text-xs font-semibold text-amber-700">⭐ Öne çıkan</span>
                    @elseif ($listing->has_pending_feature)
                        <span class="rounded-lg bg-stone-100 px-2 py-1 text-xs text-stone-500">⏳ İncelemede</span>
                    @elseif ($listing->status->value === 'aktif')
                        <form method="POST" action="{{ route('panel.listings.feature', $listing) }}" class="flex items-center gap-1">
                            @csrf
                            <select name="days" class="rounded-lg border-stone-300 py-1 pl-2 pr-7 text-xs focus:border-amber-500 focus:ring-amber-500">
                                <option value="7">7 gün</option>
                                <option value="30">30 gün</option>
                            </select>
                            <button type="submit" class="rounded-lg border border-amber-300 px-2 py-1 text-xs font-medium text-amber-700 hover:bg-amber-50">⭐ Öne çıkar</button>
                        </form>
                    @endif
                    <a href="{{ route('panel.listings.edit', $listing) }}" class="rounded-lg border border-stone-300 px-3 py-1.5 text-sm font-medium text-stone-600 hover:bg-stone-50">Düzenle</a>
                </div>
            </div>
            @if ($loop->last)</div>{{ $listings->links() }}@endif
        @empty
            <div class="mt-10 rounded-2xl border border-dashed border-stone-300 bg-white p-10 text-center">
                <div class="text-4xl">🧰</div>
                <h2 class="mt-3 text-lg font-semibold text-stone-800">Henüz ilanın yok</h2>
                <p class="mt-1 text-sm text-stone-500">İlk ilanını ver, yeteneğini paraya dönüştür.</p>
                <a href="{{ route('panel.listings.create') }}" class="mt-5 inline-block rounded-lg bg-emerald-600 px-5 py-2.5 font-semibold text-white transition hover:bg-emerald-700">+ İlk İlanını Ver</a>
            </div>
        @endforelse
    </div>
</x-layouts.app>
