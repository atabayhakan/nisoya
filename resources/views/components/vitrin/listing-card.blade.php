@props(['listing'])

{{-- VİTRİN İLAN KARTI (P2) — klasik partials/listing-card'ın Vitrin karşılığı.
     Korunan sözleşmeler (klasik kartla birebir): responsive srcset/sizes,
     --listing-transition-name + .listing-cover-transition morph adı,
     isCurrentlyFeatured() ÜCRETLİ öne-çıkarma rozeti ve çerçevesi,
     emlak (oda/m²) ve vasıta (yıl/km) koşullu metası (relationLoaded korumalı),
     "Görüşülür" fiyat metni. Yalnız görsel dil değişir. --}}
<a href="{{ route('listings.show', [$listing, $listing->slug]) }}"
   class="group block rounded-2xl border bg-white p-3 shadow-brand transition duration-150 hover:-translate-y-0.5 hover:border-emerald-300 hover:shadow-brand-lg dark:bg-stone-900 dark:hover:border-emerald-700 {{ $listing->isCurrentlyFeatured() ? 'border-amber-300 ring-1 ring-amber-200 dark:border-amber-700 dark:ring-amber-900/40' : 'border-stone-200/60 dark:border-stone-800' }}">
    <div class="relative aspect-[4/3] w-full overflow-hidden rounded-[14px] bg-stone-100 dark:bg-stone-800">
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
                 style="--listing-transition-name: listing-image-{{ $listing->id }}; object-position: {{ $listing->coverImage->objectPosition() }}"
                 class="listing-cover-transition h-full w-full object-cover transition duration-300 group-hover:scale-105">
        @else
            @php $fallbackIcon = \App\Support\CategoryIcon::heroicon($listing->category?->parent?->icon ?? $listing->category?->icon); @endphp
            <div style="--listing-transition-name: listing-image-{{ $listing->id }}" class="listing-cover-transition flex h-full w-full items-center justify-center text-stone-300 dark:text-stone-600">
                <x-dynamic-component :component="'heroicon-o-'.$fallbackIcon" class="h-12 w-12" />
            </div>
        @endif

        {{-- Zaman çipi (gerçek created_at) --}}
        <span class="absolute left-2.5 top-2.5 rounded-full bg-white/95 px-2.5 py-1.5 text-2xs font-bold text-stone-700 dark:bg-stone-900/95 dark:text-stone-200">
            {{ $listing->created_at->diffForHumans(null, true) }} önce
        </span>

        @if ($listing->isCurrentlyFeatured())
            <span class="absolute right-2.5 top-2.5 inline-flex items-center gap-1 rounded-full bg-amber-100 px-2.5 py-1.5 text-2xs font-bold text-amber-700 dark:bg-amber-900/70 dark:text-amber-300">
                <x-heroicon-s-star class="h-3 w-3" /> Öne çıkan
            </span>
        @endif
    </div>

    <div class="px-1.5 pb-1 pt-3">
        {{-- Meta satırı: şehir · kategori (+ emlak/vasıta koşullu bilgisi) --}}
        <div class="flex flex-wrap items-center gap-x-2 gap-y-1 text-xs font-semibold text-stone-500 dark:text-stone-400">
            @if ($listing->country)
                <span>{{ $listing->country->emoji }} {{ $listing->city ?: $listing->country->name_tr }}</span>
                <span class="h-1 w-1 rounded-full bg-stone-300 dark:bg-stone-600" aria-hidden="true"></span>
            @endif
            @if ($listing->category)
                <span>{{ $listing->category->name }}</span>
            @endif
            @if ($listing->relationLoaded('propertyDetail') && $listing->propertyDetail && ($listing->propertyDetail->rooms || $listing->propertyDetail->area_m2))
                <span class="h-1 w-1 rounded-full bg-stone-300 dark:bg-stone-600" aria-hidden="true"></span>
                <span>{{ collect([$listing->propertyDetail->rooms, $listing->propertyDetail->area_m2 ? $listing->propertyDetail->area_m2.' m²' : null])->filter()->implode(' · ') }}</span>
            @endif
            @if ($listing->relationLoaded('vehicleDetail') && $listing->vehicleDetail && ($listing->vehicleDetail->year || $listing->vehicleDetail->mileage_km !== null))
                <span class="h-1 w-1 rounded-full bg-stone-300 dark:bg-stone-600" aria-hidden="true"></span>
                <span>{{ collect([$listing->vehicleDetail->year, $listing->vehicleDetail->mileage_km !== null ? number_format($listing->vehicleDetail->mileage_km, 0, ',', '.').' km' : null])->filter()->implode(' · ') }}</span>
            @endif
        </div>

        {{-- min-h ARİTMETİKTİR, keyfi değil: iki satırlık başlığın tam
             yüksekliğini rezerve eder ki ızgaradaki tüm kartlar eşit boyda
             kalsın. 16px × 1.35 satır × 2 = 43.2px = 2.7rem.
             (Eski hâli 15px'e göre 2.6rem'di; punto ölçeğe katlanınca bu
             sayı da güncellenmek zorundaydı, yoksa iki satırlık başlıklar
             tabanı aşıp kartları farklı boylara ayırırdı.) --}}
        <h3 class="mt-2 line-clamp-2 min-h-[2.7rem] text-base font-bold leading-[1.35] tracking-[-0.008em] text-stone-800 group-hover:text-emerald-700 dark:text-stone-100 dark:group-hover:text-emerald-400" style="text-wrap: pretty">{{ $listing->title }}</h3>

        <div class="mt-3 flex items-center justify-between gap-2.5">
            <span class="text-lg font-extrabold text-emerald-600 dark:text-emerald-400">
                @if ($listing->price !== null)
                    {{ number_format((float) $listing->price, 0) }} {{ $listing->currency }}<span class="text-2xs font-semibold text-stone-400">{{ $listing->price_unit->suffix() }}</span>
                @else
                    Görüşülür
                @endif
            </span>
            <span class="flex shrink-0 items-center gap-1.5">
                @if ($listing->is_remote)
                    <span class="rounded-full bg-[#e7f7f1] px-2.5 py-1.5 text-2xs font-bold text-[#0f9d76] dark:bg-teal-950/60 dark:text-teal-300">Online</span>
                @endif
                @if ($listing->user->is_verified)
                    <span class="inline-flex items-center gap-1 rounded-full bg-[#e7f7f1] px-2.5 py-1.5 text-2xs font-bold text-[#0f9d76] dark:bg-teal-950/60 dark:text-teal-300" title="Doğrulanmış üye">
                        <x-heroicon-s-check class="h-2.5 w-2.5" /> Doğrulandı
                    </span>
                @endif
            </span>
        </div>
    </div>
</a>
