<a href="{{ route('listings.show', [$listing, $listing->slug]) }}"
   class="group block overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:border-stone-800 dark:bg-stone-900 dark:shadow-none">
    <div class="aspect-[4/3] w-full overflow-hidden bg-stone-100 dark:bg-stone-800">
        @if ($listing->coverImage)
            @php
                $srcset = $listing->coverImage->srcset();
                $thumbUrl = $srcset['thumb'] ?? Storage::url($listing->coverImage->path);
                $mediumUrl = $srcset['medium'] ?? Storage::url($listing->coverImage->path);
                $largeUrl = $srcset['large'] ?? Storage::url($listing->coverImage->path);
            @endphp
            <img src="{{ $thumbUrl }}"
                 srcset="{{ $thumbUrl }} 300w, {{ $mediumUrl }} 800w, {{ $largeUrl }} 1600w"
                 sizes="(min-width: 1024px) 25vw, (min-width: 640px) 50vw, 100vw"
                 alt="{{ $listing->title }}"
                 width="400"
                 height="300"
                 loading="lazy"
                 decoding="async"
                 class="h-full w-full object-cover transition duration-300 group-hover:scale-105">
        @else
            <div class="flex h-full w-full items-center justify-center text-5xl text-stone-300 dark:text-stone-600">
                {{ $listing->category?->parent?->icon ?? $listing->category?->icon ?? '🧰' }}
            </div>
        @endif
    </div>
    <div class="p-4">
        <div class="flex flex-wrap items-center gap-1.5 text-xs">
            @if ($listing->isCurrentlyFeatured())
                <span class="rounded-full bg-amber-100 px-2 py-0.5 font-semibold text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">⭐ Öne çıkan</span>
            @endif
            @if ($listing->category)
                <span class="rounded-full bg-stone-100 px-2 py-0.5 text-stone-500 dark:bg-stone-800 dark:text-stone-400">{{ $listing->category->name }}</span>
            @endif
            @if ($listing->is_remote)
                <span class="rounded-full bg-emerald-100 px-2 py-0.5 font-medium text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">Online</span>
            @endif
        </div>

        <h3 class="mt-2 line-clamp-2 min-h-[2.5rem] font-semibold text-stone-800 group-hover:text-emerald-700 dark:text-stone-100 dark:group-hover:text-emerald-400">{{ $listing->title }}</h3>

        <div class="mt-2 font-bold text-stone-900 dark:text-stone-50">
            @if ($listing->price !== null)
                {{ number_format((float) $listing->price, 0) }} {{ $listing->currency }}<span class="text-xs font-normal text-stone-400 dark:text-stone-500">{{ $listing->price_unit->suffix() }}</span>
            @else
                <span class="text-emerald-700 dark:text-emerald-400">Görüşülür</span>
            @endif
        </div>

        <div class="mt-2 flex items-center gap-1 truncate text-xs text-stone-400 dark:text-stone-500">
            @if ($listing->country){{ $listing->country->emoji }} @if ($listing->city){{ $listing->city }}@else{{ $listing->country->name_tr }}@endif · @endif
            {{ $listing->user->name }}
        </div>
    </div>
</a>
