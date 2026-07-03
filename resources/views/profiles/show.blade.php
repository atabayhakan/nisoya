<x-layouts.app :title="$user->name.' — Nisoya'">
    <div class="mx-auto max-w-6xl px-4 py-8">
        {{-- Satıcı başlığı --}}
        <div class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm dark:border-stone-800 dark:bg-stone-900">
            <div class="flex flex-wrap items-center gap-4">
                <div class="grid h-20 w-20 place-items-center overflow-hidden rounded-full bg-emerald-100 text-3xl font-bold text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">
                    @if ($user->avatar_path)
                        <img src="{{ Storage::url($user->avatar_path) }}" alt="" class="h-full w-full object-cover">
                    @else
                        {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
                    @endif
                </div>
                <div class="min-w-0 flex-1">
                    <h1 class="flex items-center gap-2 text-2xl font-bold text-stone-900 dark:text-stone-50">
                        {{ $user->name }}
                        @if ($user->is_verified)<span title="Doğrulanmış üye" class="text-base text-emerald-600 dark:text-emerald-400">✓ Doğrulanmış</span>@endif
                    </h1>
                    <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">
                        @if ($user->city){{ $user->city }} · @endif{{ $user->country_code }} · Üyelik: {{ $user->created_at->translatedFormat('F Y') }}
                    </p>
                    @if ($rating['count'] > 0)
                        <p class="mt-1 text-sm font-medium text-amber-500 dark:text-amber-400">★ {{ $rating['avg'] }} <span class="text-stone-400 dark:text-stone-500">({{ $rating['count'] }} değerlendirme)</span></p>
                    @endif
                    @if ($user->bio)
                        <p class="mt-2 text-sm text-stone-600 dark:text-stone-300">{{ $user->bio }}</p>
                    @endif
                    @if ($user->skills)
                        <div class="mt-2 flex flex-wrap items-center gap-1.5">
                            @foreach ($user->skills as $skill)
                                <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">{{ $skill }}</span>
                            @endforeach
                        </div>
                    @endif
                    @if ($user->paymentLinks->isNotEmpty())
                        <div class="mt-2 flex flex-wrap items-center gap-1.5">
                            <span class="text-xs text-stone-400 dark:text-stone-500">Kabul ettiği ödeme yöntemleri:</span>
                            @foreach ($user->paymentLinks as $link)
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
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-stone-900 dark:text-stone-50">{{ $listings->total() }}</div>
                    <div class="text-xs text-stone-500 dark:text-stone-400">aktif ilan</div>
                </div>
            </div>

            <div class="mt-4 border-t border-stone-100 pt-3 dark:border-stone-800">
                @include('partials.share-buttons', ['shareUrl' => route('profiles.show', $user->username), 'shareText' => $user->name.' — Nisoya'])
            </div>
        </div>

        {{-- Portfolyo --}}
        @if ($user->portfolioItems->isNotEmpty())
            <h2 class="mt-8 text-lg font-bold text-stone-900 dark:text-stone-50">Portfolyo</h2>
            <div class="mt-4 grid grid-cols-2 gap-3 sm:grid-cols-4">
                @foreach ($user->portfolioItems as $item)
                    <a href="{{ $item->url('large') }}" target="_blank" rel="noopener" class="group relative overflow-hidden rounded-xl border border-stone-200 dark:border-stone-800">
                        <img src="{{ $item->url('medium') }}" alt="{{ $item->caption }}" loading="lazy" class="aspect-square w-full object-cover transition group-hover:scale-105">
                        @if ($item->caption)
                            <div class="absolute inset-x-0 bottom-0 truncate bg-stone-900/60 px-2 py-1 text-xs text-white">{{ $item->caption }}</div>
                        @endif
                    </a>
                @endforeach
            </div>
        @endif

        {{-- İlanları --}}
        <h2 class="mt-8 text-lg font-bold text-stone-900 dark:text-stone-50">{{ $user->name }} kullanıcısının ilanları</h2>

        @if ($listings->isNotEmpty())
            <div class="mt-4 grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($listings as $listing)
                    @include('partials.listing-card', ['listing' => $listing])
                @endforeach
            </div>
            <div class="mt-8">{{ $listings->links() }}</div>
        @else
            <div class="mt-4 rounded-2xl border border-dashed border-stone-300 bg-white p-12 text-center text-stone-500 dark:border-stone-700 dark:bg-stone-900 dark:text-stone-400">
                Bu üyenin şu an aktif ilanı yok.
            </div>
        @endif

        {{-- Değerlendirmeler --}}
        <div class="mt-12">
            <h2 class="text-lg font-bold text-stone-900 dark:text-stone-50">
                Değerlendirmeler
                @if ($rating['count'] > 0)<span class="text-amber-500 dark:text-amber-400">★ {{ $rating['avg'] }}</span> <span class="text-sm font-normal text-stone-400 dark:text-stone-500">({{ $rating['count'] }})</span>@endif
            </h2>

            @if (session('status'))
                <div class="mt-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">{{ session('status') }}</div>
            @endif

            @auth
                @if ($canReview)
                    <form method="POST" action="{{ route('reviews.store', $user->username) }}" class="mt-4 rounded-2xl border border-stone-200 bg-white p-5 shadow-sm dark:border-stone-800 dark:bg-stone-900">
                        @csrf
                        <p class="text-sm font-medium text-stone-700 dark:text-stone-300">{{ $myReview ? 'Değerlendirmeni güncelle' : 'Bu üyeyi değerlendir' }}</p>
                        <div class="mt-2 flex flex-wrap items-end gap-3">
                            <div>
                                <label for="rating" class="block text-sm text-stone-600 dark:text-stone-300">Puan</label>
                                <select id="rating" name="rating" class="mt-1 rounded-lg border-stone-300 px-3 py-2 text-sm text-stone-800 focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                                    @for ($i = 5; $i >= 1; $i--)
                                        <option value="{{ $i }}" @selected(($myReview?->rating ?? 5) === $i)>{{ $i }} yıldız</option>
                                    @endfor
                                </select>
                            </div>
                        </div>
                        <textarea name="comment" rows="3" placeholder="Deneyimini paylaş (ops.)" class="mt-2 w-full rounded-lg border-stone-300 px-3 py-2 text-sm text-stone-800 focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100 dark:placeholder-stone-500">{{ $myReview?->comment }}</textarea>
                        @error('rating') <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror
                        <button type="submit" class="mt-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700 dark:bg-emerald-500 dark:hover:bg-emerald-400 dark:text-stone-900">{{ $myReview ? 'Güncelle' : 'Gönder' }}</button>
                    </form>
                @elseif (auth()->id() !== $user->id)
                    <div class="mt-4 rounded-2xl border border-stone-200 bg-stone-50 p-5 text-sm text-stone-600 dark:border-stone-800 dark:bg-stone-900 dark:text-stone-300">
                        Bu üyeyi değerlendirebilmek için önce kendisiyle iletişime geçmiş olman gerekir.
                        @if ($listings->isNotEmpty())
                            @php($firstListing = $listings->first())
                            <a href="{{ route('listings.show', [$firstListing, $firstListing->slug]) }}" class="font-medium text-emerald-700 hover:underline dark:text-emerald-400">Bir ilanına mesaj gönder</a>
                        @endif
                    </div>
                @endif
            @endauth

            <div class="mt-4 space-y-3">
                @forelse ($reviews as $review)
                    <div class="rounded-2xl border border-stone-200 bg-white p-4 dark:border-stone-800 dark:bg-stone-900">
                        <div class="flex items-center justify-between">
                            <span class="font-medium text-stone-800 dark:text-stone-100">{{ $review->reviewer->name }}</span>
                            <span class="text-amber-500 dark:text-amber-400">{{ str_repeat('★', $review->rating) }}<span class="text-stone-300 dark:text-stone-600">{{ str_repeat('★', 5 - $review->rating) }}</span></span>
                        </div>
                        @if ($review->comment)<p class="mt-1 text-sm text-stone-600 dark:text-stone-300">{{ $review->comment }}</p>@endif
                        <p class="mt-1 text-xs text-stone-400 dark:text-stone-500">{{ $review->created_at->translatedFormat('j F Y') }}</p>
                    </div>
                @empty
                    <p class="text-sm text-stone-500 dark:text-stone-400">Henüz değerlendirme yok. İlk değerlendiren sen ol!</p>
                @endforelse
            </div>
        </div>
    </div>
</x-layouts.app>
