<div class="flex flex-col items-center justify-center p-2 text-stone-900 dark:text-stone-100">
    <div class="w-full max-w-sm overflow-hidden rounded-2xl bg-black shadow-2xl border border-stone-800">
        @if ($reel->embed_url)
            <iframe
                src="{{ $reel->embed_url }}"
                class="w-full h-[520px] border-0 bg-stone-950"
                allowtransparency="true"
                allowfullscreen="true"
                frameborder="0"
                scrolling="no"
            ></iframe>
        @elseif ($reel->video_url)
            <video
                src="{{ $reel->video_url }}"
                class="w-full max-h-[520px] object-contain bg-black"
                controls
                autoplay
                playsinline
            ></video>
        @elseif ($reel->thumbnail_url)
            <img
                src="{{ $reel->thumbnail_url }}"
                alt="{{ $reel->title }}"
                class="w-full aspect-[9/16] object-cover"
            >
        @else
            <div class="w-full aspect-[9/16] bg-gradient-to-br from-stone-900 via-stone-800 to-rose-950 flex flex-col items-center justify-center text-center p-6 text-white">
                <span class="text-5xl mb-3 drop-shadow">{{ $reel->country?->emoji ?: '🌍' }}</span>
                <span class="text-base font-bold leading-snug">{{ $reel->title }}</span>
                <span class="mt-1 text-xs text-stone-300">{{ $reel->displayLocation() }}</span>
                <span class="mt-4 rounded-full bg-white/10 px-3 py-1 text-2xs text-stone-300 font-mono">Instagram Reels</span>
            </div>
        @endif
    </div>

    <div class="mt-4 w-full max-w-sm flex flex-col gap-2">
        <div class="flex items-center justify-between text-xs">
            <span class="font-bold flex items-center gap-1.5">
                <span>{{ $reel->country?->emoji }}</span>
                <span>{{ $reel->displayLocation() }}</span>
            </span>
            @if ($reel->instagram_username)
                <span class="font-semibold text-rose-600 dark:text-rose-400">{{ $reel->instagram_username }}</span>
            @endif
        </div>

        @if ($reel->caption)
            <p class="text-xs text-stone-600 dark:text-stone-400 line-clamp-3 leading-relaxed">
                {{ $reel->caption }}
            </p>
        @endif

        <div class="mt-2 pt-2 border-t border-stone-200 dark:border-stone-800 flex flex-wrap items-center justify-between gap-2">
            <div class="flex flex-wrap items-center gap-1.5 text-2xs">
                @if ($reel->category)
                    <span class="inline-flex items-center rounded-md bg-sky-50 px-2 py-0.5 font-semibold text-sky-700 ring-1 ring-sky-200 dark:bg-sky-950/60 dark:text-sky-400">
                        {{ \App\Models\DiasporaReel::getCategories()[$reel->category] ?? $reel->category }}
                    </span>
                @endif
                @if ($reel->safety_score !== null)
                    <span class="inline-flex items-center rounded-md {{ $reel->safety_score >= 85 ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-400' : 'bg-amber-50 text-amber-700 ring-1 ring-amber-200' }} px-1.5 py-0.5 font-medium">
                        🛡️ %{{ $reel->safety_score }} Güvenli
                    </span>
                @endif
                @if ($reel->engagement_score > 0)
                    <span class="inline-flex items-center rounded-md bg-amber-50 px-1.5 py-0.5 font-bold text-amber-700 ring-1 ring-amber-200 dark:bg-amber-950/60 dark:text-amber-400">
                        ★ {{ $reel->engagement_score }} Puan
                    </span>
                @endif
                @if ($reel->is_featured)
                    <span class="inline-flex items-center rounded-md bg-purple-50 px-2 py-0.5 font-bold text-purple-700 ring-1 ring-purple-200 dark:bg-purple-950/60 dark:text-purple-400">Öne Çıkan</span>
                @endif
                <span class="inline-flex items-center rounded-md {{ $reel->status === 'published' ? 'bg-emerald-50 text-emerald-700 ring-1 ring-emerald-200 dark:bg-emerald-950/60 dark:text-emerald-400' : 'bg-amber-50 text-amber-700 ring-1 ring-amber-200' }} px-2 py-0.5 font-semibold">
                    {{ $reel->status === 'published' ? 'Yayında' : 'Onay Bekliyor' }}
                </span>
            </div>

            <a
                href="{{ $reel->instagram_url }}"
                target="_blank"
                rel="noopener noreferrer"
                class="inline-flex items-center gap-1 text-xs font-bold text-rose-600 hover:text-rose-700 hover:underline dark:text-rose-400"
            >
                <span>Instagram'da Aç</span>
                <span>↗</span>
            </a>
        </div>
    </div>
</div>
