<x-layouts.app :title="$listing->title.' — Nisoya'" :description="\Illuminate\Support\Str::limit(strip_tags($listing->description), 150)" :ogImage="$listing->coverImage ? \Illuminate\Support\Facades\Storage::url($listing->coverImage->path) : null">
    {{-- JSON-LD: BreadcrumbList --}}
    <x-json-ld type="BreadcrumbList" :data="[
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'İlanlar', 'item' => url('/ilanlar')],
            ['@type' => 'ListItem', 'position' => 2, 'name' => $listing->category?->name ?? 'Genel', 'item' => $listing->category ? url('/ilanlar/kategori/'.$listing->category->slug) : url('/ilanlar')],
            ['@type' => 'ListItem', 'position' => 3, 'name' => $listing->title, 'item' => url()->current()],
        ],
    ]" />

    {{-- JSON-LD: Service veya Product --}}
    @if ($listing->type->value === 'urun')
        <x-json-ld type="Product" :data="[
            'name' => $listing->title,
            'description' => \Illuminate\Support\Str::limit(strip_tags($listing->description), 300),
            'image' => $listing->coverImage ? \Illuminate\Support\Facades\Storage::url($listing->coverImage->path) : null,
            'offers' => [
                '@type' => 'Offer',
                'price' => $listing->price ?? 0,
                'priceCurrency' => $listing->currency,
                'availability' => ($listing->stock && $listing->stock > 0) ? 'https://schema.org/InStock' : 'https://schema.org/OutOfStock',
            ],
        ]" />
    @else
        <x-json-ld type="Service" :data="[
            'name' => $listing->title,
            'description' => \Illuminate\Support\Str::limit(strip_tags($listing->description), 300),
            'image' => $listing->coverImage ? \Illuminate\Support\Facades\Storage::url($listing->coverImage->path) : null,
            'provider' => [
                '@type' => 'Person',
                'name' => $listing->user->name,
            ],
            'areaServed' => $listing->country ? [
                '@type' => 'Country',
                'name' => $listing->country->name_tr,
            ] : null,
            'offers' => $listing->price ? [
                '@type' => 'Offer',
                'price' => $listing->price,
                'priceCurrency' => $listing->currency,
            ] : null,
        ]" />
    @endif

    <div class="mx-auto max-w-5xl px-4 py-8">
        @if (session('status'))
            <div class="mb-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">{{ session('status') }}</div>
        @endif

        {{-- Breadcrumb --}}
        <nav class="text-sm text-stone-500 dark:text-stone-400">
            <a href="{{ url('/ilanlar') }}" class="hover:text-emerald-700 dark:hover:text-emerald-400">İlanlar</a>
            @if ($listing->category)
                <span class="mx-1">/</span>
                <a href="{{ url('/ilanlar/kategori/'.$listing->category->slug) }}" class="hover:text-emerald-700 dark:hover:text-emerald-400">{{ $listing->category->name }}</a>
            @endif
        </nav>

        @if ($isOwner && $listing->status->value !== 'aktif')
            <div class="mt-4 rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-700 dark:bg-amber-900/30 dark:text-amber-300">
                Bu ilan şu an <strong>{{ $listing->status->getLabel() }}</strong> durumunda — yalnızca sen görüyorsun.
            </div>
        @endif

        <div class="mt-4 grid gap-8 lg:grid-cols-3">
            {{-- Sol: görseller + açıklama --}}
            <div class="lg:col-span-2">
                @if ($listing->images->isNotEmpty())
                    <div class="overflow-hidden rounded-2xl bg-stone-100 dark:bg-stone-800">
                        @php
                            $hero = $listing->coverImage ?? $listing->images->first();
                            $heroSrcset = $hero->srcset();
                            $heroLarge = $heroSrcset['large'] ?? Storage::url($hero->path);
                            $heroMedium = $heroSrcset['medium'] ?? Storage::url($hero->path);
                        @endphp
                        <img src="{{ $heroLarge }}"
                             srcset="{{ $heroMedium }} 800w, {{ $heroLarge }} 1600w"
                             sizes="(min-width: 1024px) 800px, 100vw"
                             alt="{{ $listing->title }}"
                             width="800"
                             height="420"
                             fetchpriority="high"
                             class="max-h-[420px] w-full object-cover">
                    </div>
                    @if ($listing->images->count() > 1)
                        <div class="mt-3 grid grid-cols-4 gap-3 sm:grid-cols-5">
                            @foreach ($listing->images as $image)
                                @php $thumb = $image->url('thumb') ?? Storage::url($image->path); @endphp
                                <img src="{{ $thumb }}"
                                     srcset="{{ ($image->url('thumb') ?? Storage::url($image->path)) }} 300w, {{ ($image->url('medium') ?? Storage::url($image->path)) }} 800w"
                                     sizes="120px"
                                     alt=""
                                     width="120"
                                     height="120"
                                     loading="lazy"
                                     decoding="async"
                                     class="aspect-square w-full rounded-lg object-cover">
                            @endforeach
                        </div>
                    @endif
                @else
                    @php($fallbackIcon = \App\Support\CategoryIcon::heroicon($listing->category?->parent?->icon ?? $listing->category?->icon))
                    <div class="flex h-56 items-center justify-center rounded-2xl bg-stone-100 text-stone-300 dark:bg-stone-800 dark:text-stone-600">
                        <x-dynamic-component :component="'heroicon-o-'.$fallbackIcon" class="h-16 w-16" />
                    </div>
                @endif

                <h1 class="mt-6 text-2xl font-bold text-stone-900 dark:text-stone-50">{{ $listing->title }}</h1>

                <div class="mt-2 flex flex-wrap items-center gap-2 text-sm">
                    @if ($listing->isCurrentlyFeatured())
                        <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-3 py-1 font-medium text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">
                            <x-heroicon-s-star class="h-3.5 w-3.5" /> Öne çıkan
                        </span>
                    @endif
                    @if ($listing->country)
                        <span class="inline-flex items-center gap-1 rounded-full bg-stone-100 px-3 py-1 text-stone-600 dark:bg-stone-800 dark:text-stone-300">
                            {{ $listing->country->emoji }} @if ($listing->city){{ $listing->city }}, @endif{{ $listing->country->name_tr }}
                        </span>
                    @endif
                    @if ($listing->is_remote)
                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-3 py-1 font-medium text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">
                            <x-heroicon-o-globe-alt class="h-3.5 w-3.5" /> Uzaktan / Online
                        </span>
                    @endif
                    @if ($listing->type->value === 'urun' && $listing->stock !== null)
                        <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-3 py-1 font-medium text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">
                            <x-heroicon-o-archive-box class="h-3.5 w-3.5" /> Stokta {{ $listing->stock }} adet
                        </span>
                    @endif
                </div>

                <div class="prose prose-stone mt-6 max-w-none text-stone-700 dark:prose-invert dark:text-stone-300">
                    {!! nl2br(e($listing->description)) !!}
                </div>

                @auth
                    @unless ($isOwner)
                        <details class="mt-8 text-sm">
                            <summary class="inline-flex cursor-pointer items-center gap-1 text-stone-400 hover:text-stone-600 dark:text-stone-500 dark:hover:text-stone-300">
                                <x-heroicon-o-flag class="h-4 w-4" /> Bu ilanı şikayet et
                            </summary>
                            <form method="POST" action="{{ route('reports.store', $listing) }}" class="mt-3 max-w-md space-y-2 rounded-lg border border-stone-200 bg-white p-4 dark:border-stone-800 dark:bg-stone-900">
                                @csrf
                                <select name="reason" required class="w-full rounded-lg border-stone-300 px-3 py-2 text-sm text-stone-800 focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                                    <option value="">Sebep seç...</option>
                                    <option value="Yanıltıcı / sahte ilan">Yanıltıcı / sahte ilan</option>
                                    <option value="Uygunsuz içerik">Uygunsuz içerik</option>
                                    <option value="Dolandırıcılık şüphesi">Dolandırıcılık şüphesi</option>
                                    <option value="Yanlış kategori">Yanlış kategori</option>
                                    <option value="Diğer">Diğer</option>
                                </select>
                                <textarea name="note" rows="2" placeholder="Eklemek istediğin not (ops.)" class="w-full rounded-lg border-stone-300 px-3 py-2 text-sm text-stone-800 focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100 dark:placeholder-stone-500"></textarea>
                                <button type="submit" class="rounded-lg bg-stone-800 px-4 py-2 text-sm font-medium text-white hover:bg-stone-900 dark:bg-stone-700 dark:hover:bg-stone-600">Şikayeti gönder</button>
                            </form>
                        </details>
                    @endunless
                @endauth
            </div>

            {{-- Sağ: fiyat + satıcı + iletişim --}}
            <div class="lg:col-span-1">
                <div class="sticky top-20 space-y-4">
                    <div class="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm dark:border-stone-800 dark:bg-stone-900">
                        <div class="text-2xl font-bold text-stone-900 dark:text-stone-50">
                            @if ($listing->price !== null)
                                {{ number_format((float) $listing->price, 2) }} {{ $listing->currency }}
                                <span class="text-base font-normal text-stone-400 dark:text-stone-500">{{ $listing->price_unit->suffix() }}</span>
                            @else
                                <span class="text-emerald-700 dark:text-emerald-400">Görüşülür</span>
                            @endif
                        </div>

                        <div class="mt-4 space-y-3">
                            @if ($isOwner)
                                <a href="{{ route('panel.listings.edit', $listing) }}" class="block w-full rounded-lg border border-stone-300 px-4 py-2.5 text-center font-semibold text-stone-700 hover:bg-stone-50 dark:border-stone-700 dark:text-stone-300 dark:hover:bg-stone-800">İlanı Düzenle</a>
                            @elseif (auth()->check())
                                <form method="POST" action="{{ route('messages.start', $listing) }}" class="space-y-2">
                                    @csrf
                                    <textarea name="body" rows="3" required placeholder="Satıcıya bir mesaj yaz..."
                                              class="w-full rounded-lg border-stone-300 px-3 py-2 text-sm text-stone-800 shadow-sm focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100 dark:placeholder-stone-500">{{ old('body') }}</textarea>
                                    @error('body') <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                                    <button type="submit" class="w-full rounded-lg bg-emerald-600 px-4 py-2.5 font-semibold text-white transition hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-400 dark:text-stone-900">Mesaj Gönder</button>
                                </form>
                            @else
                                <a href="{{ route('login') }}" class="block w-full rounded-lg bg-emerald-600 px-4 py-2.5 text-center font-semibold text-white hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-400 dark:text-stone-900">Mesaj göndermek için giriş yap</a>
                            @endif

                            @auth
                                @unless ($isOwner)
                                    <form method="POST" action="{{ route('favorites.toggle', $listing) }}">
                                        @csrf
                                        <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-lg border px-4 py-2.5 font-medium transition {{ $isFavorited ? 'border-red-200 bg-red-50 text-red-600 dark:border-red-800 dark:bg-red-950/30 dark:text-red-400' : 'border-stone-300 text-stone-700 hover:bg-stone-50 dark:border-stone-700 dark:text-stone-300 dark:hover:bg-stone-800' }}">
                                            @if ($isFavorited)
                                                <x-heroicon-s-heart class="h-5 w-5" /> Favorilerde
                                            @else
                                                <x-heroicon-o-heart class="h-5 w-5" /> Favorilere ekle
                                            @endif
                                        </button>
                                    </form>
                                @endunless
                            @endauth
                        </div>

                        <div class="mt-4 border-t border-stone-100 pt-3 dark:border-stone-800">
                            <p class="mb-2 text-xs font-medium text-stone-500 dark:text-stone-400">Bu ilanı paylaş</p>
                            @include('partials.share-buttons', ['shareUrl' => route('listings.show', [$listing, $listing->slug]), 'shareText' => $listing->title])
                        </div>
                    </div>

                    <div class="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm dark:border-stone-800 dark:bg-stone-900">
                        <div class="flex items-center gap-3">
                            <div class="grid h-12 w-12 place-items-center overflow-hidden rounded-full bg-emerald-100 text-lg font-bold text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">
                                @if ($listing->user->avatar_path)
                                    <img src="{{ Storage::url($listing->user->avatar_path) }}" alt="" class="h-full w-full object-cover">
                                @else
                                    {{ mb_strtoupper(mb_substr($listing->user->name, 0, 1)) }}
                                @endif
                            </div>
                            <div class="min-w-0">
                                <div class="flex items-center gap-1 font-semibold text-stone-800 dark:text-stone-100">
                                    {{ $listing->user->name }}
                                    @if ($listing->user->is_verified)<span title="Doğrulanmış üye" class="text-emerald-600 dark:text-emerald-400">✓</span>@endif
                                </div>
                                <div class="text-xs text-stone-500 dark:text-stone-400">Üyelik: {{ $listing->user->created_at->translatedFormat('F Y') }}</div>
                                @if ($sellerRating['count'] > 0)
                                    <div class="text-xs font-medium text-amber-500 dark:text-amber-400">★ {{ $sellerRating['avg'] }} <span class="text-stone-400 dark:text-stone-500">({{ $sellerRating['count'] }} değerlendirme)</span></div>
                                @endif
                            </div>
                        </div>
                        @if ($listing->user->paymentLinks->isNotEmpty())
                            <div class="mt-3 flex flex-wrap items-center gap-1.5">
                                @foreach ($listing->user->paymentLinks as $link)
                                    @if ($link->detailIsLink())
                                        <a href="{{ $link->detail }}" target="_blank" rel="noopener nofollow" class="inline-flex items-center gap-1 rounded-full bg-stone-100 px-2 py-0.5 text-xs text-stone-600 hover:bg-emerald-100 hover:text-emerald-700 dark:bg-stone-800 dark:text-stone-300 dark:hover:bg-emerald-900/40 dark:hover:text-emerald-300" title="{{ $link->method->getLabel() }} — kendi ödeme sayfasına git">
                                            <span aria-hidden="true">{{ $link->method->icon() }}</span>{{ $link->method->getLabel() }} ↗
                                        </a>
                                    @elseif ($link->qr_path)
                                        <a href="{{ Storage::url($link->qr_path) }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 rounded-full bg-stone-100 px-2 py-0.5 text-xs text-stone-600 hover:bg-emerald-100 hover:text-emerald-700 dark:bg-stone-800 dark:text-stone-300 dark:hover:bg-emerald-900/40 dark:hover:text-emerald-300" title="{{ $link->method->getLabel() }} — QR kodu gör">
                                            <span aria-hidden="true">{{ $link->method->icon() }}</span>{{ $link->method->getLabel() }} 🔳
                                        </a>
                                    @else
                                        <span class="inline-flex items-center gap-1 rounded-full bg-stone-100 px-2 py-0.5 text-xs text-stone-600 dark:bg-stone-800 dark:text-stone-300">
                                            <span aria-hidden="true">{{ $link->method->icon() }}</span>{{ $link->method->getLabel() }}@if ($link->detail) — {{ $link->detail }}@endif
                                        </span>
                                    @endif
                                @endforeach
                            </div>
                        @endif
                        <a href="{{ route('profiles.show', $listing->user->username) }}" class="mt-3 block text-sm font-medium text-emerald-700 hover:underline dark:text-emerald-400">Profili ve değerlendirmeleri gör →</a>
                        <p class="mt-3 text-xs text-stone-400 dark:text-stone-500">
                            Nisoya bir ilan ve iletişim platformudur; ödeme ve anlaşma taraflar arasındadır.
                        </p>
                    </div>

                    {{-- Reklam: sidebar alt --}}
                    <x-ad-slot slot="3333333333" format="rectangle" />
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
