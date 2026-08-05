<div class="relative">
<a href="{{ route('listings.show', [$listing, $listing->slug]) }}"
   class="group block overflow-hidden rounded-2xl border bg-white shadow-sm transition hover:-translate-y-0.5 hover:shadow-md dark:bg-stone-900 dark:shadow-none {{ $listing->isCurrentlyFeatured() ? 'border-amber-300 ring-1 ring-amber-200 dark:border-amber-700 dark:ring-amber-900/40' : 'border-stone-200 dark:border-stone-800' }}">
    <div class="aspect-[4/3] w-full overflow-hidden bg-stone-100 dark:bg-stone-800">
        @if ($listing->coverImage)
            @php
                $thumbUrl = $listing->coverImage->enIyiUrl('thumb');
                $mediumUrl = $listing->coverImage->enIyiUrl('medium');
                $largeUrl = $listing->coverImage->enIyiUrl('large');
            @endphp
            <img src="{{ $thumbUrl }}"
                 srcset="{{ $thumbUrl }} 300w, {{ $mediumUrl }} 800w, {{ $largeUrl }} 1600w"
                 sizes="(min-width: 1024px) 25vw, (min-width: 640px) 50vw, 100vw"
                 alt="{{ $listing->title }}"
                 width="400"
                 height="300"
                 loading="lazy"
                 decoding="async"
                 style="--listing-transition-name: listing-image-{{ $listing->id }}; object-position: {{ $listing->coverImage->objectPosition() }}"
                 class="listing-cover-transition h-full w-full object-cover transition duration-300 group-hover:scale-105">
        @else
            @php($fallbackIcon = \App\Support\CategoryIcon::heroicon($listing->category?->parent?->icon ?? $listing->category?->icon))
            <div style="--listing-transition-name: listing-image-{{ $listing->id }}" class="listing-cover-transition flex h-full w-full items-center justify-center text-stone-300 dark:text-stone-400">
                <x-dynamic-component :component="'heroicon-o-'.$fallbackIcon" class="h-12 w-12" />
            </div>
        @endif
    </div>
    <div class="p-4">
        <div class="flex flex-wrap items-center gap-1.5 text-xs">
            @if ($listing->isCurrentlyFeatured())
                <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-2 py-0.5 font-semibold text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">
                    <x-heroicon-s-star class="h-3 w-3" /> Öne çıkan
                </span>
            @endif
            @if ($listing->category)
                <span class="rounded-full bg-stone-100 px-2 py-0.5 text-stone-600 dark:bg-stone-800 dark:text-stone-400">{{ $listing->category->name }}</span>
            @endif
            @if ($listing->is_remote)
                <span class="rounded-full bg-emerald-100 px-2 py-0.5 font-medium text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">Online</span>
            @endif
            @if ($listing->relationLoaded('propertyDetail') && $listing->propertyDetail && ($listing->propertyDetail->rooms || $listing->propertyDetail->area_m2))
                <span class="rounded-full bg-stone-100 px-2 py-0.5 text-stone-600 dark:bg-stone-800 dark:text-stone-400">
                    {{ collect([$listing->propertyDetail->rooms, $listing->propertyDetail->area_m2 ? $listing->propertyDetail->area_m2.' m²' : null])->filter()->implode(' · ') }}
                </span>
            @endif
            @if ($listing->relationLoaded('vehicleDetail') && $listing->vehicleDetail && ($listing->vehicleDetail->year || $listing->vehicleDetail->mileage_km !== null))
                <span class="rounded-full bg-stone-100 px-2 py-0.5 text-stone-600 dark:bg-stone-800 dark:text-stone-400">
                    {{ collect([$listing->vehicleDetail->year, $listing->vehicleDetail->mileage_km !== null ? number_format($listing->vehicleDetail->mileage_km, 0, ',', '.').' km' : null])->filter()->implode(' · ') }}
                </span>
            @endif
        </div>

        <x-ornek-isareti :listing="$listing" class="mt-2" />
        <h3 class="mt-2 line-clamp-2 min-h-[2.5rem] font-semibold text-stone-800 group-hover:text-emerald-700 dark:text-stone-100 dark:group-hover:text-emerald-400">{{ $listing->title }}</h3>

        <div class="mt-2 font-bold text-stone-900 dark:text-stone-50">
            @if ($listing->price !== null)
                {{ number_format((float) $listing->price, 0) }} {{ $listing->currency }}<span class="text-xs font-normal text-stone-600 dark:text-stone-400">{{ $listing->price_unit->suffix() }}</span>
            @else
                <span class="text-emerald-700 dark:text-emerald-400">Görüşülür</span>
            @endif
        </div>

        <div class="mt-2 flex items-center gap-1 truncate text-xs text-stone-600 dark:text-stone-400">
            @if ($listing->country){{ $listing->country->emoji }} @if ($listing->city){{ $listing->city }}@else{{ $listing->country->name_tr }}@endif · @endif
            {{ $listing->user->name }}
        </div>
    </div>
</a>
    {{-- FAVORİ KALBİ — kartın KARDEŞİ, çocuğu DEĞİL.

         Kart tek bir <a> ile sarılı; içine <form>/<button> koymak iç içe
         etkileşimli öge demek olurdu (geçersiz HTML, tarayıcılar arasında
         öngörülemez davranış). Bu yüzden kart `relative` bir kapsayıcıya
         alındı ve kalp mutlak konumla üstüne bindirildi.

         JS YOK: `favorites.toggle` `back()` döndüğü için düz form yeterli.
         Bir Alpine bileşeni daha hızlı hissettirirdi ama giriş gerektiren bir
         akışı burada tarayıcıda test edemiyorum — çalıştığını kanıtlayamadığım
         bir optimizasyon yerine kanıtlayabildiğim sade çözüm.

         Yalnız giriş yapmışa görünür: favori zaten hesap gerektiriyor,
         misafire kalp göstermek tutulamayacak bir vaat olurdu. --}}
    @auth
        @php($favorili = auth()->user()->favorilerindeMi($listing->id))
        <form method="POST" action="{{ route('favorites.toggle', $listing) }}" class="absolute right-2 top-2 z-10">
            @csrf
            <button type="submit"
                    class="grid h-9 w-9 place-items-center rounded-full bg-white/90 shadow-sm backdrop-blur transition hover:bg-white dark:bg-stone-900/90 dark:hover:bg-stone-900 {{ $favorili ? 'text-red-600 dark:text-red-400' : 'text-stone-600 dark:text-stone-300' }}"
                    title="{{ $favorili ? 'Favorilerden çıkar' : 'Favorilere ekle' }}"
                    aria-label="{{ $favorili ? 'Favorilerden çıkar' : 'Favorilere ekle' }}">
                @if ($favorili)
                    <x-heroicon-s-heart class="h-5 w-5" />
                @else
                    <x-heroicon-o-heart class="h-5 w-5" />
                @endif
            </button>
        </form>
    @endauth
</div>

