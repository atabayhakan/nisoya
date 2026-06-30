<x-layouts.app :title="$listing->title.' — Nisoya'" :description="\Illuminate\Support\Str::limit(strip_tags($listing->description), 150)" :ogImage="$listing->coverImage ? \Illuminate\Support\Facades\Storage::url($listing->coverImage->path) : null">
    <div class="mx-auto max-w-5xl px-4 py-8">
        @if (session('status'))
            <div class="mb-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
        @endif

        {{-- Breadcrumb --}}
        <nav class="text-sm text-stone-500">
            <a href="{{ url('/ilanlar') }}" class="hover:text-emerald-700">İlanlar</a>
            @if ($listing->category)
                <span class="mx-1">/</span>
                <a href="{{ url('/ilanlar/kategori/'.$listing->category->slug) }}" class="hover:text-emerald-700">{{ $listing->category->name }}</a>
            @endif
        </nav>

        @if ($isOwner && $listing->status->value !== 'aktif')
            <div class="mt-4 rounded-lg bg-amber-50 px-4 py-3 text-sm text-amber-700">
                Bu ilan şu an <strong>{{ $listing->status->getLabel() }}</strong> durumunda — yalnızca sen görüyorsun.
            </div>
        @endif

        <div class="mt-4 grid gap-8 lg:grid-cols-3">
            {{-- Sol: görseller + açıklama --}}
            <div class="lg:col-span-2">
                @if ($listing->images->isNotEmpty())
                    <div class="overflow-hidden rounded-2xl bg-stone-100">
                        <img src="{{ Storage::url(($listing->coverImage ?? $listing->images->first())->path) }}" alt="{{ $listing->title }}" class="max-h-[420px] w-full object-cover">
                    </div>
                    @if ($listing->images->count() > 1)
                        <div class="mt-3 grid grid-cols-4 gap-3 sm:grid-cols-5">
                            @foreach ($listing->images as $image)
                                <img src="{{ Storage::url($image->path) }}" alt="" class="aspect-square w-full rounded-lg object-cover">
                            @endforeach
                        </div>
                    @endif
                @else
                    <div class="flex h-56 items-center justify-center rounded-2xl bg-stone-100 text-6xl text-stone-300">🧰</div>
                @endif

                <h1 class="mt-6 text-2xl font-bold text-stone-900">{{ $listing->title }}</h1>

                <div class="mt-2 flex flex-wrap items-center gap-2 text-sm">
                    @if ($listing->isCurrentlyFeatured())
                        <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-3 py-1 font-medium text-amber-700">⭐ Öne çıkan</span>
                    @endif
                    @if ($listing->country)
                        <span class="inline-flex items-center gap-1 rounded-full bg-stone-100 px-3 py-1 text-stone-600">
                            {{ $listing->country->emoji }} @if ($listing->city){{ $listing->city }}, @endif{{ $listing->country->name_tr }}
                        </span>
                    @endif
                    @if ($listing->is_remote)
                        <span class="inline-flex items-center gap-1 rounded-full bg-emerald-100 px-3 py-1 font-medium text-emerald-700">🌐 Uzaktan / Online</span>
                    @endif
                    @if ($listing->type->value === 'urun' && $listing->stock !== null)
                        <span class="inline-flex items-center gap-1 rounded-full bg-amber-100 px-3 py-1 font-medium text-amber-700">📦 Stokta {{ $listing->stock }} adet</span>
                    @endif
                </div>

                <div class="prose prose-stone mt-6 max-w-none text-stone-700">
                    {!! nl2br(e($listing->description)) !!}
                </div>

                @auth
                    @unless ($isOwner)
                        <details class="mt-8 text-sm">
                            <summary class="cursor-pointer text-stone-400 hover:text-stone-600">⚐ Bu ilanı şikayet et</summary>
                            <form method="POST" action="{{ route('reports.store', $listing) }}" class="mt-3 max-w-md space-y-2 rounded-lg border border-stone-200 bg-white p-4">
                                @csrf
                                <select name="reason" required class="w-full rounded-lg border-stone-300 px-3 py-2 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                                    <option value="">Sebep seç...</option>
                                    <option value="Yanıltıcı / sahte ilan">Yanıltıcı / sahte ilan</option>
                                    <option value="Uygunsuz içerik">Uygunsuz içerik</option>
                                    <option value="Dolandırıcılık şüphesi">Dolandırıcılık şüphesi</option>
                                    <option value="Yanlış kategori">Yanlış kategori</option>
                                    <option value="Diğer">Diğer</option>
                                </select>
                                <textarea name="note" rows="2" placeholder="Eklemek istediğin not (ops.)" class="w-full rounded-lg border-stone-300 px-3 py-2 text-sm focus:border-emerald-500 focus:ring-emerald-500"></textarea>
                                <button type="submit" class="rounded-lg bg-stone-800 px-4 py-2 text-sm font-medium text-white hover:bg-stone-900">Şikayeti gönder</button>
                            </form>
                        </details>
                    @endunless
                @endauth
            </div>

            {{-- Sağ: fiyat + satıcı + iletişim --}}
            <div class="lg:col-span-1">
                <div class="sticky top-20 space-y-4">
                    <div class="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm">
                        <div class="text-2xl font-bold text-stone-900">
                            @if ($listing->price !== null)
                                {{ number_format((float) $listing->price, 2) }} {{ $listing->currency }}
                                <span class="text-base font-normal text-stone-400">{{ $listing->price_unit->suffix() }}</span>
                            @else
                                <span class="text-emerald-700">Görüşülür</span>
                            @endif
                        </div>

                        <div class="mt-4 space-y-3">
                            @if ($isOwner)
                                <a href="{{ route('panel.listings.edit', $listing) }}" class="block w-full rounded-lg border border-stone-300 px-4 py-2.5 text-center font-semibold text-stone-700 hover:bg-stone-50">İlanı Düzenle</a>
                            @elseif (auth()->check())
                                <form method="POST" action="{{ route('messages.start', $listing) }}" class="space-y-2">
                                    @csrf
                                    <textarea name="body" rows="3" required placeholder="Satıcıya bir mesaj yaz..."
                                              class="w-full rounded-lg border-stone-300 px-3 py-2 text-sm shadow-sm focus:border-emerald-500 focus:ring-emerald-500">{{ old('body') }}</textarea>
                                    @error('body') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                                    <button type="submit" class="w-full rounded-lg bg-emerald-600 px-4 py-2.5 font-semibold text-white transition hover:bg-emerald-700">Mesaj Gönder</button>
                                </form>
                            @else
                                <a href="{{ route('login') }}" class="block w-full rounded-lg bg-emerald-600 px-4 py-2.5 text-center font-semibold text-white hover:bg-emerald-700">Mesaj göndermek için giriş yap</a>
                            @endif

                            @auth
                                @unless ($isOwner)
                                    <form method="POST" action="{{ route('favorites.toggle', $listing) }}">
                                        @csrf
                                        <button type="submit" class="flex w-full items-center justify-center gap-2 rounded-lg border px-4 py-2.5 font-medium transition {{ $isFavorited ? 'border-red-200 bg-red-50 text-red-600' : 'border-stone-300 text-stone-700 hover:bg-stone-50' }}">
                                            {{ $isFavorited ? '❤️ Favorilerde' : '🤍 Favorilere ekle' }}
                                        </button>
                                    </form>
                                @endunless
                            @endauth
                        </div>

                        <div class="mt-4 border-t border-stone-100 pt-3">
                            <p class="mb-2 text-xs font-medium text-stone-500">Bu ilanı paylaş</p>
                            @include('partials.share-buttons', ['shareUrl' => route('listings.show', [$listing, $listing->slug]), 'shareText' => $listing->title])
                        </div>
                    </div>

                    <div class="rounded-2xl border border-stone-200 bg-white p-5 shadow-sm">
                        <div class="flex items-center gap-3">
                            <div class="grid h-12 w-12 place-items-center overflow-hidden rounded-full bg-emerald-100 text-lg font-bold text-emerald-700">
                                @if ($listing->user->avatar_path)
                                    <img src="{{ Storage::url($listing->user->avatar_path) }}" alt="" class="h-full w-full object-cover">
                                @else
                                    {{ mb_strtoupper(mb_substr($listing->user->name, 0, 1)) }}
                                @endif
                            </div>
                            <div class="min-w-0">
                                <div class="flex items-center gap-1 font-semibold text-stone-800">
                                    {{ $listing->user->name }}
                                    @if ($listing->user->is_verified)<span title="Doğrulanmış üye" class="text-emerald-600">✓</span>@endif
                                </div>
                                <div class="text-xs text-stone-500">Üyelik: {{ $listing->user->created_at->translatedFormat('MMMM Y') }}</div>
                                @if ($sellerRating['count'] > 0)
                                    <div class="text-xs font-medium text-amber-500">★ {{ $sellerRating['avg'] }} <span class="text-stone-400">({{ $sellerRating['count'] }} değerlendirme)</span></div>
                                @endif
                            </div>
                        </div>
                        <a href="{{ route('profiles.show', $listing->user->username) }}" class="mt-3 block text-sm font-medium text-emerald-700 hover:underline">Profili ve değerlendirmeleri gör →</a>
                        <p class="mt-3 text-xs text-stone-400">
                            Nisoya bir ilan ve iletişim platformudur; ödeme ve anlaşma taraflar arasındadır.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
