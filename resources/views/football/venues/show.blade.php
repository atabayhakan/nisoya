<x-layouts.app>
    <div class="mx-auto max-w-5xl px-4 py-8">
        {{-- Bildirimler --}}
        @if (session('status'))
            <div class="mb-6 rounded-2xl bg-emerald-50 p-4 text-sm font-semibold text-emerald-800 ring-1 ring-emerald-200 dark:bg-emerald-950/40 dark:text-emerald-300 dark:ring-emerald-800">
                {{ session('status') }}
            </div>
        @endif

        {{-- Saha Başlık & Bilgiler --}}
        <div class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm sm:p-8 dark:border-stone-800 dark:bg-stone-900">
            <div class="flex flex-col gap-6 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <a href="{{ route('football.venues.index', \Illuminate\Support\Str::slug($venue->city)) }}" class="text-xs font-semibold text-emerald-700 hover:underline dark:text-emerald-400">
                        ← {{ $venue->city }} Halı Sahalarına Dön
                    </a>
                    <h1 class="mt-1 text-2xl font-black text-stone-900 sm:text-3xl dark:text-stone-100">
                        {{ $venue->name }}
                    </h1>
                    <p class="mt-1 text-sm text-stone-600 dark:text-stone-400">
                        📍 {{ $venue->address }}, {{ $venue->city }}
                    </p>
                </div>

                <div class="flex items-center gap-3">
                    <div class="rounded-2xl bg-amber-50 px-4 py-3 text-center ring-1 ring-amber-200 dark:bg-amber-950/40 dark:ring-amber-800">
                        <span class="text-2xl font-black text-amber-700 dark:text-amber-300">⭐ {{ number_format($venue->rating, 1) }}</span>
                        <p class="text-3xs font-semibold text-amber-800 dark:text-amber-400">{{ $venue->reviews_count }} Değerlendirme</p>
                    </div>
                </div>
            </div>

            @if ($venue->cover_image_path)
                <div class="mt-6 h-64 sm:h-80 w-full overflow-hidden rounded-3xl bg-stone-100">
                    <img src="{{ Storage::url($venue->cover_image_path) }}" alt="{{ $venue->name }}" class="h-full w-full object-cover">
                </div>
            @endif

            {{-- Saha Özellikleri Grid --}}
            <div class="mt-6 grid grid-cols-2 gap-3 sm:grid-cols-4 border-t border-stone-100 pt-6 dark:border-stone-800">
                <div class="rounded-2xl bg-stone-50 p-3.5 dark:bg-stone-800/60">
                    <p class="text-3xs uppercase font-bold text-stone-400">Saha Tipi</p>
                    <p class="mt-1 text-sm font-bold text-stone-900 dark:text-stone-100">
                        {{ \App\Models\FootballVenue::PITCH_TYPES[$venue->pitch_type] ?? $venue->pitch_type }}
                    </p>
                </div>
                <div class="rounded-2xl bg-stone-50 p-3.5 dark:bg-stone-800/60">
                    <p class="text-3xs uppercase font-bold text-stone-400">Zemin Türü</p>
                    <p class="mt-1 text-sm font-bold text-stone-900 dark:text-stone-100">
                        {{ \App\Models\FootballVenue::SURFACE_TYPES[$venue->surface_type] ?? $venue->surface_type }}
                    </p>
                </div>
                <div class="rounded-2xl bg-stone-50 p-3.5 dark:bg-stone-800/60">
                    <p class="text-3xs uppercase font-bold text-stone-400">Fiyat</p>
                    <p class="mt-1 text-sm font-bold text-emerald-700 dark:text-emerald-400">
                        {{ $venue->price_info ?: 'Belirtilmedi' }}
                    </p>
                </div>
                <div class="rounded-2xl bg-stone-50 p-3.5 dark:bg-stone-800/60">
                    <p class="text-3xs uppercase font-bold text-stone-400">İletişim / Tel</p>
                    <p class="mt-1 text-sm font-bold text-stone-900 dark:text-stone-100 truncate">
                        {{ $venue->phone ?: 'Belirtilmedi' }}
                    </p>
                </div>
            </div>

            {{-- Tesis Donanımları --}}
            @if (! empty($venue->features) && is_array($venue->features))
                <div class="mt-6 border-t border-stone-100 pt-6 dark:border-stone-800">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-stone-500 dark:text-stone-400">Tesis Özellikleri</h3>
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach ($venue->features as $feat)
                            <span class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-50 px-3 py-1.5 text-xs font-semibold text-emerald-800 dark:bg-emerald-950/50 dark:text-emerald-300">
                                <span>✓</span> {{ \App\Models\FootballVenue::FEATURE_OPTIONS[$feat] ?? $feat }}
                            </span>
                        @endforeach
                    </div>
                </div>
            @endif
        </div>

        {{-- 2 Kolon: Yorumlar & Değerlendirme Formu --}}
        <div class="mt-8 grid gap-8 lg:grid-cols-3">
            {{-- Yorumlar Listesi (2 Kolon) --}}
            <div class="space-y-6 lg:col-span-2">
                <div class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm dark:border-stone-800 dark:bg-stone-900">
                    <h2 class="text-base font-bold text-stone-900 dark:text-stone-100 border-b border-stone-100 pb-3 dark:border-stone-800">
                        Oyuncu Değerlendirmeleri ({{ $venue->reviews_count }})
                    </h2>

                    @if ($venue->publishedReviews->isEmpty())
                        <p class="mt-4 text-center text-xs text-stone-500 dark:text-stone-400">
                            Bu saha için henüz yorum yapılmadı. İlk yorumu sen yap!
                        </p>
                    @else
                        <div class="mt-4 space-y-4">
                            @foreach ($venue->publishedReviews as $review)
                                <div class="rounded-2xl border border-stone-100 bg-stone-50 p-4 dark:border-stone-800 dark:bg-stone-800/50">
                                    <div class="flex items-center justify-between">
                                        <div class="flex items-center gap-2">
                                            <strong class="text-sm font-bold text-stone-900 dark:text-stone-100">{{ $review->user?->name }}</strong>
                                            <span class="text-2xs text-stone-400">{{ $review->created_at->translatedFormat('d M Y') }}</span>
                                        </div>
                                        <span class="text-xs font-bold text-amber-600 dark:text-amber-400">
                                            ⭐ {{ $review->rating }} / 5
                                        </span>
                                    </div>
                                    @if ($review->comment)
                                        <p class="mt-2 text-xs text-stone-700 dark:text-stone-300">
                                            {{ $review->comment }}
                                        </p>
                                    @endif
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>
            </div>

            {{-- Değerlendirme Yap Formu --}}
            <div>
                <div class="rounded-3xl border border-stone-200 bg-white p-6 shadow-sm dark:border-stone-800 dark:bg-stone-900">
                    <h3 class="text-sm font-bold text-stone-900 dark:text-stone-100">
                        Sahayı Değerlendir
                    </h3>
                    @auth
                        <form method="POST" action="{{ route('football.venues.review', $venue) }}" class="mt-4 space-y-4">
                            @csrf
                            <x-honeypot />

                            <div>
                                <label class="block text-xs font-bold uppercase text-stone-700 dark:text-stone-300">Genel Puan *</label>
                                <select name="rating" required class="mt-1 w-full rounded-xl border border-stone-200 bg-stone-50 px-3 py-2 text-sm focus:border-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                                    <option value="5">⭐⭐⭐⭐⭐ (5 - Mükemmel)</option>
                                    <option value="4">⭐⭐⭐⭐ (4 - Çok İyi)</option>
                                    <option value="3">⭐⭐⭐ (3 - Ortalama)</option>
                                    <option value="2">⭐⭐ (2 - Geliştirilmeli)</option>
                                    <option value="1">⭐ (1 - Yetersiz)</option>
                                </select>
                            </div>

                            <div>
                                <label class="block text-xs font-bold uppercase text-stone-700 dark:text-stone-300">Yorumunuz</label>
                                <textarea name="comment" rows="3" placeholder="Zemin, aydınlatma, duşlar ve personel hakkında deneyimleriniz..."
                                          class="mt-1 w-full rounded-xl border border-stone-200 bg-stone-50 px-3 py-2 text-xs focus:border-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100"></textarea>
                            </div>

                            <button type="submit" class="w-full rounded-xl bg-emerald-600 py-2.5 text-xs font-bold text-white shadow-sm transition hover:bg-emerald-500">
                                Değerlendirmeyi Gönder
                            </button>
                        </form>
                    @else
                        <p class="mt-3 text-xs text-stone-500">
                            Değerlendirme yapmak için <a href="{{ route('login') }}" class="font-bold text-emerald-700 hover:underline">giriş yapmalısınız</a>.
                        </p>
                    @endauth
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
