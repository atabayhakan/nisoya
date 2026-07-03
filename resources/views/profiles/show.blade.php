<x-layouts.app :title="$user->name.' — Nisoya'">
    <div class="mx-auto max-w-6xl px-4 py-8">
        {{-- Satıcı başlığı --}}
        <div class="rounded-2xl border border-stone-200 bg-white p-6 shadow-sm">
            <div class="flex flex-wrap items-center gap-4">
                <div class="grid h-20 w-20 place-items-center overflow-hidden rounded-full bg-emerald-100 text-3xl font-bold text-emerald-700">
                    @if ($user->avatar_path)
                        <img src="{{ Storage::url($user->avatar_path) }}" alt="" class="h-full w-full object-cover">
                    @else
                        {{ mb_strtoupper(mb_substr($user->name, 0, 1)) }}
                    @endif
                </div>
                <div class="min-w-0 flex-1">
                    <h1 class="flex items-center gap-2 text-2xl font-bold text-stone-900">
                        {{ $user->name }}
                        @if ($user->is_verified)<span title="Doğrulanmış üye" class="text-base text-emerald-600">✓ Doğrulanmış</span>@endif
                    </h1>
                    <p class="mt-1 text-sm text-stone-500">
                        @if ($user->city){{ $user->city }} · @endif{{ $user->country_code }} · Üyelik: {{ $user->created_at->translatedFormat('F Y') }}
                    </p>
                    @if ($rating['count'] > 0)
                        <p class="mt-1 text-sm font-medium text-amber-500">★ {{ $rating['avg'] }} <span class="text-stone-400">({{ $rating['count'] }} değerlendirme)</span></p>
                    @endif
                    @if ($user->bio)
                        <p class="mt-2 text-sm text-stone-600">{{ $user->bio }}</p>
                    @endif
                    @if ($user->payment_methods && $user->payment_methods->isNotEmpty())
                        <div class="mt-2 flex flex-wrap items-center gap-1.5">
                            <span class="text-xs text-stone-400">Kabul ettiği ödeme yöntemleri:</span>
                            @foreach ($user->payment_methods as $method)
                                <span class="inline-flex items-center gap-1 rounded-full bg-stone-100 px-2 py-0.5 text-xs text-stone-600" title="{{ $method->getLabel() }}">
                                    <span aria-hidden="true">{{ $method->icon() }}</span>{{ $method->getLabel() }}
                                </span>
                            @endforeach
                        </div>
                    @endif
                </div>
                <div class="text-center">
                    <div class="text-2xl font-bold text-stone-900">{{ $listings->total() }}</div>
                    <div class="text-xs text-stone-500">aktif ilan</div>
                </div>
            </div>

            <div class="mt-4 border-t border-stone-100 pt-3">
                @include('partials.share-buttons', ['shareUrl' => route('profiles.show', $user->username), 'shareText' => $user->name.' — Nisoya'])
            </div>
        </div>

        {{-- İlanları --}}
        <h2 class="mt-8 text-lg font-bold text-stone-900">{{ $user->name }} kullanıcısının ilanları</h2>

        @if ($listings->isNotEmpty())
            <div class="mt-4 grid gap-5 sm:grid-cols-2 xl:grid-cols-3">
                @foreach ($listings as $listing)
                    @include('partials.listing-card', ['listing' => $listing])
                @endforeach
            </div>
            <div class="mt-8">{{ $listings->links() }}</div>
        @else
            <div class="mt-4 rounded-2xl border border-dashed border-stone-300 bg-white p-12 text-center text-stone-500">
                Bu üyenin şu an aktif ilanı yok.
            </div>
        @endif

        {{-- Değerlendirmeler --}}
        <div class="mt-12">
            <h2 class="text-lg font-bold text-stone-900">
                Değerlendirmeler
                @if ($rating['count'] > 0)<span class="text-amber-500">★ {{ $rating['avg'] }}</span> <span class="text-sm font-normal text-stone-400">({{ $rating['count'] }})</span>@endif
            </h2>

            @if (session('status'))
                <div class="mt-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700">{{ session('status') }}</div>
            @endif

            @auth
                @if ($canReview)
                    <form method="POST" action="{{ route('reviews.store', $user->username) }}" class="mt-4 rounded-2xl border border-stone-200 bg-white p-5 shadow-sm">
                        @csrf
                        <p class="text-sm font-medium text-stone-700">{{ $myReview ? 'Değerlendirmeni güncelle' : 'Bu üyeyi değerlendir' }}</p>
                        <div class="mt-2 flex flex-wrap items-end gap-3">
                            <div>
                                <label for="rating" class="block text-sm text-stone-600">Puan</label>
                                <select id="rating" name="rating" class="mt-1 rounded-lg border-stone-300 px-3 py-2 text-sm focus:border-emerald-500 focus:ring-emerald-500">
                                    @for ($i = 5; $i >= 1; $i--)
                                        <option value="{{ $i }}" @selected(($myReview?->rating ?? 5) === $i)>{{ $i }} yıldız</option>
                                    @endfor
                                </select>
                            </div>
                        </div>
                        <textarea name="comment" rows="3" placeholder="Deneyimini paylaş (ops.)" class="mt-2 w-full rounded-lg border-stone-300 px-3 py-2 text-sm focus:border-emerald-500 focus:ring-emerald-500">{{ $myReview?->comment }}</textarea>
                        @error('rating') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
                        <button type="submit" class="mt-2 rounded-lg bg-emerald-600 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-700">{{ $myReview ? 'Güncelle' : 'Gönder' }}</button>
                    </form>
                @elseif (auth()->id() !== $user->id)
                    <div class="mt-4 rounded-2xl border border-stone-200 bg-stone-50 p-5 text-sm text-stone-600">
                        Bu üyeyi değerlendirebilmek için önce kendisiyle iletişime geçmiş olman gerekir.
                        @if ($listings->isNotEmpty())
                            @php($firstListing = $listings->first())
                            <a href="{{ route('listings.show', [$firstListing, $firstListing->slug]) }}" class="font-medium text-emerald-700 hover:underline">Bir ilanına mesaj gönder</a>
                        @endif
                    </div>
                @endif
            @endauth

            <div class="mt-4 space-y-3">
                @forelse ($reviews as $review)
                    <div class="rounded-2xl border border-stone-200 bg-white p-4">
                        <div class="flex items-center justify-between">
                            <span class="font-medium text-stone-800">{{ $review->reviewer->name }}</span>
                            <span class="text-amber-500">{{ str_repeat('★', $review->rating) }}<span class="text-stone-300">{{ str_repeat('★', 5 - $review->rating) }}</span></span>
                        </div>
                        @if ($review->comment)<p class="mt-1 text-sm text-stone-600">{{ $review->comment }}</p>@endif
                        <p class="mt-1 text-xs text-stone-400">{{ $review->created_at->translatedFormat('j F Y') }}</p>
                    </div>
                @empty
                    <p class="text-sm text-stone-500">Henüz değerlendirme yok. İlk değerlendiren sen ol!</p>
                @endforelse
            </div>
        </div>
    </div>
</x-layouts.app>
