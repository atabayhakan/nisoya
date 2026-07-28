@props(['illustration', 'title', 'description', 'ctaText' => null, 'ctaHref' => null])

{{-- Faz İ4: 13 farklı "boş durum" kartı elle tekrarlanan emoji/heroicon
     karışımı yerine tek, tutarlı bir bileşen — 6 özel çizilmiş SVG,
     hepsi aynı "boş raf" (alttaki kesikli çizgi) motifini paylaşıyor.
     currentColor kullanır: hem "1. Tasarım" hem "2. Tasarım"da doğru
     renklenir, tasarım moduna göre ayrı kod gerekmez. Tasarım moduna
     bakılmaksızın her yerde aynı (kullanıcı tercihi). --}}
<div
    x-reveal
    class="mt-10 rounded-2xl border border-dashed border-stone-300 bg-white p-10 text-center dark:border-stone-700 dark:bg-stone-900"
>
    <div class="mx-auto flex h-16 w-28 items-center justify-center text-emerald-700 dark:text-emerald-400">
        <svg viewBox="0 0 120 90" class="h-16 w-28" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            @switch($illustration)
                @case('search')
                    <rect x="30" y="18" width="34" height="26" rx="4" stroke-dasharray="4 4" opacity="0.45" />
                    <circle cx="72" cy="42" r="14" />
                    <line x1="82" y1="52" x2="94" y2="64" />
                    @break

                @case('chat')
                    <path d="M28 20h54a9 9 0 0 1 9 9v19a9 9 0 0 1-9 9H57l-15 13V57H28a9 9 0 0 1-9-9V29a9 9 0 0 1 9-9Z" />
                    <circle cx="45" cy="38" r="2" fill="currentColor" opacity="0.6" />
                    <circle cx="60" cy="38" r="2" fill="currentColor" opacity="0.6" />
                    @break

                @case('listing')
                    <path d="M28 40 36 18h48l8 22" />
                    <rect x="28" y="40" width="64" height="32" rx="3" />
                    <line x1="28" y1="52" x2="92" y2="52" opacity="0.45" />
                    @break

                @case('heart')
                    <path d="M60 68 26 38a15 15 0 0 1 24-19l10 10 10-10a15 15 0 0 1 24 19Z" />
                    @break

                @case('bell')
                    <path d="M60 16a17 17 0 0 0-17 17v9c0 9-4 15-9 19h52c-5-4-9-10-9-19v-9a17 17 0 0 0-17-17Z" />
                    <path d="M52 64a8 8 0 0 0 16 0" />
                    @break

                @case('inbox')
                    <path d="M24 32h19l8 10h18l8-10h19v27a7 7 0 0 1-7 7H31a7 7 0 0 1-7-7Z" />
                    <line x1="24" y1="32" x2="24" y2="59" opacity="0.45" />
                    <line x1="96" y1="32" x2="96" y2="59" opacity="0.45" />
                    @break
            @endswitch

            {{-- Ortak "boş raf" motifi — tüm illüstrasyonların üstünde durduğu kesikli çizgi. --}}
            <line x1="8" y1="80" x2="112" y2="80" stroke-dasharray="3 6" opacity="0.3" />
        </svg>
    </div>

    <h2 class="mt-3 text-lg font-semibold text-stone-800 dark:text-stone-100">{{ $title }}</h2>
    <p class="mt-1 text-sm text-stone-500 dark:text-stone-400">{{ $description }}</p>

    @if ($ctaText && $ctaHref)
        <a href="{{ $ctaHref }}" class="mt-5 inline-block rounded-lg bg-emerald-700 px-5 py-2.5 text-sm font-semibold text-white transition hover:-translate-y-0.5 hover:bg-emerald-800 hover:shadow-brand dark:bg-emerald-500 dark:hover:bg-emerald-400 dark:text-stone-900">{{ $ctaText }}</a>
    @endif
</div>
