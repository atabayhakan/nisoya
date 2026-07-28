<x-layouts.app
    :title="$company->name.' — Nisoya'"
    :description="$company->tagline ? \Illuminate\Support\Str::limit(strip_tags($company->tagline), 150) : ($company->about ? \Illuminate\Support\Str::limit(strip_tags($company->about), 150) : $company->name.' — Nisoya üzerinde iş ilanları veriyor.')"
    :ogImage="$company->logoUrl()"
>
    {{-- JSON-LD: Organization --}}
    <x-json-ld type="Organization" :data="array_filter([
        'name' => $company->name,
        'description' => $company->about ? \Illuminate\Support\Str::limit(strip_tags($company->about), 300) : $company->tagline,
        'url' => $company->website ?: route('companies.show', $company),
        'logo' => $company->logoUrl(),
        'foundingDate' => $company->founded_year ? (string) $company->founded_year : null,
        'address' => $company->city || $company->country_code ? array_filter([
            '@type' => 'PostalAddress',
            'streetAddress' => $company->address,
            'addressLocality' => $company->city,
            'addressCountry' => $company->country_code,
        ]) : null,
        'sameAs' => array_values(array_filter([
            $company->social_linkedin,
            $company->social_instagram,
            $company->social_twitter,
        ])),
    ])" />

    <div class="mx-auto max-w-3xl px-4 py-8">
        <x-panel.back-link :href="route('jobs.index')" label="İş ilanları" />

        {{-- Şirket başlığı --}}
        <div class="mt-3 rounded-2xl border border-stone-200 bg-white p-6 shadow-sm dark:border-stone-800 dark:bg-stone-900 dark:shadow-none">
            <div class="flex items-start gap-4">
                <div class="grid h-16 w-16 shrink-0 place-items-center overflow-hidden rounded-2xl bg-stone-100 text-xl font-bold text-stone-600 dark:bg-stone-800 dark:text-stone-400">
                    @if ($company->logoUrl())
                        <img src="{{ $company->logoUrl() }}" alt="{{ $company->name }}" class="h-full w-full object-cover">
                    @else
                        {{ mb_strtoupper(mb_substr($company->name, 0, 1)) }}
                    @endif
                </div>
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2">
                        <h1 class="text-xl font-bold text-stone-900 dark:text-stone-50">{{ $company->name }}</h1>
                        @if ($company->is_verified)
                            {{-- Faz İ3: "2. Tasarım"da mühür renkleriyle, "1. Tasarım"da eski turkuaz pil aynen kalıyor. --}}
                            @if (\App\Support\Tema::tasarimModu() === 'yeni')
                                <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-xs font-semibold text-white" style="background: linear-gradient(135deg, var(--nisoya-seal, #c1440e), #a9713c);"><x-heroicon-s-check-badge class="h-3.5 w-3.5" /> Doğrulandı</span>
                            @else
                                <span class="inline-flex items-center gap-1 rounded-full bg-sky-100 px-2 py-0.5 text-xs font-semibold text-sky-700 dark:bg-sky-900/40 dark:text-sky-300"><x-heroicon-s-check-badge class="h-3.5 w-3.5" /> Doğrulandı</span>
                            @endif
                        @endif
                    </div>
                    @if ($company->tagline)<p class="mt-0.5 text-sm text-stone-500 dark:text-stone-400">{{ $company->tagline }}</p>@endif
                    <div class="mt-2 flex flex-wrap gap-x-3 gap-y-1 text-xs text-stone-600 dark:text-stone-400">
                        @if ($company->sector)<span>🏢 {{ $company->sector }}</span>@endif
                        @if ($company->company_size)<span>👥 {{ $company->company_size }} çalışan</span>@endif
                        @if ($company->founded_year)<span>📅 {{ $company->founded_year }}</span>@endif
                        @if ($company->country)<span>{{ $company->country->emoji }} {{ $company->city ?: $company->country->name_tr }}</span>@endif
                    </div>
                    @if ($company->address)
                        <p class="mt-1 text-xs text-stone-600 dark:text-stone-400">📍 {{ $company->address }}</p>
                    @endif
                    <div class="mt-2 flex flex-wrap gap-3 text-xs">
                        @if ($company->website)<a href="{{ $company->website }}" target="_blank" rel="noopener nofollow" class="text-emerald-700 hover:underline dark:text-emerald-400">🌐 Web sitesi</a>@endif
                        @if ($company->video_url)<a href="{{ $company->video_url }}" target="_blank" rel="noopener nofollow" class="text-emerald-700 hover:underline dark:text-emerald-400">🎥 Tanıtım videosu</a>@endif
                        @if ($company->social_linkedin)<a href="{{ $company->social_linkedin }}" target="_blank" rel="noopener nofollow" class="text-emerald-700 hover:underline dark:text-emerald-400">LinkedIn</a>@endif
                        @if ($company->social_instagram)<a href="{{ $company->social_instagram }}" target="_blank" rel="noopener nofollow" class="text-emerald-700 hover:underline dark:text-emerald-400">Instagram</a>@endif
                        @if ($company->social_twitter)<a href="{{ $company->social_twitter }}" target="_blank" rel="noopener nofollow" class="text-emerald-700 hover:underline dark:text-emerald-400">Twitter/X</a>@endif
                        @if ($company->social_whatsapp)
                            @php($waHref = str_starts_with($company->social_whatsapp, 'http') ? $company->social_whatsapp : 'https://'.$company->social_whatsapp)
                            <a href="{{ $waHref }}" target="_blank" rel="noopener nofollow" class="text-emerald-700 hover:underline dark:text-emerald-400">💬 WhatsApp</a>
                        @endif
                    </div>
                </div>
            </div>

            <div class="mt-4 border-t border-stone-100 pt-3 dark:border-stone-800">
                @include('partials.share-buttons', ['shareUrl' => route('companies.show', $company), 'shareText' => $company->name.' — Nisoya'])
            </div>

            @if ($company->about)
                <div class="mt-5 whitespace-pre-line border-t border-stone-100 pt-4 text-sm text-stone-600 dark:border-stone-800 dark:text-stone-300">{{ $company->about }}</div>
            @endif

            @if ($company->galleryImages->isNotEmpty())
                <div class="mt-5 grid grid-cols-2 gap-3 border-t border-stone-100 pt-4 dark:border-stone-800 sm:grid-cols-4">
                    @foreach ($company->galleryImages as $item)
                        <a href="{{ $item->url('large') }}" target="_blank" rel="noopener" class="group relative overflow-hidden rounded-xl border border-stone-200 dark:border-stone-800">
                            <img src="{{ $item->url('medium') }}" alt="{{ $item->caption }}" loading="lazy" class="aspect-square w-full object-cover transition group-hover:scale-105">
                            @if ($item->caption)
                                <div class="absolute inset-x-0 bottom-0 truncate bg-stone-900/60 px-2 py-1 text-xs text-white">{{ $item->caption }}</div>
                            @endif
                        </a>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Açık pozisyonlar --}}
        <h2 class="mt-6 text-lg font-bold text-stone-900 dark:text-stone-50">Açık pozisyonlar ({{ $jobs->total() }})</h2>
        @if ($jobs->isNotEmpty())
            <div class="mt-3 space-y-4">
                @foreach ($jobs as $job)
                    @include('partials.job-card', ['job' => $job])
                @endforeach
            </div>
            <div class="mt-6">{{ $jobs->links() }}</div>
        @else
            <p class="mt-3 rounded-2xl border border-dashed border-stone-300 bg-white p-8 text-center text-sm text-stone-500 dark:border-stone-700 dark:bg-stone-900 dark:text-stone-400">Şu an açık pozisyon yok.</p>
        @endif

        {{-- Değerlendirmeler --}}
        <div class="mt-12">
            <h2 class="text-lg font-bold text-stone-900 dark:text-stone-50">
                Değerlendirmeler
                @if ($rating['count'] > 0)<span class="text-amber-500 dark:text-amber-400">★ {{ $rating['avg'] }}</span> <span class="text-sm font-normal text-stone-600 dark:text-stone-400">({{ $rating['count'] }})</span>@endif
            </h2>

            @if (session('status'))
                <div class="mt-4 rounded-lg bg-emerald-50 px-4 py-3 text-sm text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">{{ session('status') }}</div>
            @endif

            @auth
                @if ($canReview)
                    <form method="POST" action="{{ route('company-reviews.store', $company) }}" class="mt-4 rounded-2xl border border-stone-200 bg-white p-5 shadow-sm dark:border-stone-800 dark:bg-stone-900">
                        @csrf
                        <p class="text-sm font-medium text-stone-700 dark:text-stone-300">{{ $myReview ? 'Değerlendirmeni güncelle' : 'Bu şirketi değerlendir' }}</p>
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
                        <button type="submit" class="mt-2 rounded-lg bg-emerald-700 px-4 py-2 text-sm font-semibold text-white hover:bg-emerald-800 dark:bg-emerald-500 dark:hover:bg-emerald-400 dark:text-stone-900">{{ $myReview ? 'Güncelle' : 'Gönder' }}</button>
                    </form>
                @elseif (auth()->id() !== $company->user_id)
                    <div class="mt-4 rounded-2xl border border-stone-200 bg-stone-50 p-5 text-sm text-stone-600 dark:border-stone-800 dark:bg-stone-900 dark:text-stone-300">
                        Bu şirketi değerlendirebilmek için önce bir iş ilanına başvurmuş olman gerekir.
                        @if ($jobs->isNotEmpty())
                            <a href="{{ route('jobs.show', [$jobs->first(), $jobs->first()->slug]) }}" class="font-medium text-emerald-700 hover:underline dark:text-emerald-400">Bir ilanına başvur</a>
                        @endif
                    </div>
                @endif
            @endauth

            <div class="mt-4 space-y-3">
                @forelse ($reviews as $review)
                    <div class="rounded-2xl border border-stone-200 bg-white p-4 dark:border-stone-800 dark:bg-stone-900">
                        <div class="flex items-center justify-between">
                            <span class="font-medium text-stone-800 dark:text-stone-100">{{ $review->reviewer->name }}</span>
                            <span class="text-amber-500 dark:text-amber-400">{{ str_repeat('★', $review->rating) }}<span class="text-stone-300 dark:text-stone-400">{{ str_repeat('★', 5 - $review->rating) }}</span></span>
                        </div>
                        @if ($review->comment)<p class="mt-1 text-sm text-stone-600 dark:text-stone-300">{{ $review->comment }}</p>@endif
                        <p class="mt-1 text-xs text-stone-600 dark:text-stone-400">{{ $review->created_at->translatedFormat('j F Y') }}</p>
                    </div>
                @empty
                    <p class="text-sm text-stone-500 dark:text-stone-400">Henüz değerlendirme yok. İlk değerlendiren sen ol!</p>
                @endforelse
            </div>
        </div>
    </div>
</x-layouts.app>
