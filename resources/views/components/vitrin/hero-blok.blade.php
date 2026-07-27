@props(['anahtar', 'countries', 'stats', 'latestListings', 'koyu' => false])

{{-- VİTRİN HERO BLOĞU (P3) — Hero Yöneticisi'ndeki blok listesinin tek tek
     karşılıkları. Kapalı bloklar buraya HİÇ gelmez (çağıran taraf
     Hero::aktifBloklar() ile döner) — CSS ile gizleme yok.
     $koyu: "sahne" düzeninde metinler koyu görsel üzerinde beyaz olur. --}}
@php
    $populerEtiketler = \Illuminate\Support\Str::of(setting('home.populer_metin', ''))
        ->after(':')
        ->explode('·')
        ->map(fn ($s) => trim($s))
        ->filter()
        ->take(4);
@endphp

@if ($anahtar === 'arama')
    {{-- Arama paneli — klasikle aynı form sözleşmesi (GET /ilanlar, q + ulke) --}}
    <form action="{{ url('/ilanlar') }}" method="GET" class="mt-7 flex flex-col gap-1.5 rounded-2xl border border-stone-200/70 bg-white p-2 shadow-brand sm:flex-row sm:items-center dark:border-stone-800 dark:bg-stone-900 dark:shadow-none">
        <div class="flex min-w-0 flex-1 items-center gap-2 px-3">
            <x-heroicon-o-magnifying-glass class="h-4 w-4 shrink-0 text-stone-400" />
            <input type="text" name="q" placeholder="{{ setting('home.arama_placeholder') }}"
                   class="h-12 w-full min-w-0 border-0 bg-transparent p-0 text-[15px] font-medium text-stone-800 placeholder-stone-400 focus:outline-none focus:ring-0 dark:text-stone-100 dark:placeholder-stone-500">
        </div>
        <select name="ulke" aria-label="Ülke seç" class="h-12 rounded-xl border-0 bg-stone-100 px-3 text-sm font-semibold text-stone-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 dark:bg-stone-800 dark:text-stone-200">
            <option value="">Tüm ülkeler</option>
            @foreach ($countries as $country)
                <option value="{{ $country->code }}">{{ $country->emoji }} {{ $country->name_tr }}</option>
            @endforeach
        </select>
        <button type="submit" class="inline-flex h-12 items-center justify-center gap-2 rounded-xl bg-emerald-600 px-6 text-sm font-bold text-white shadow-[0_12px_22px_-12px_rgba(62,99,240,1)] transition hover:bg-emerald-700 dark:bg-emerald-500 dark:text-stone-900 dark:shadow-none dark:hover:bg-emerald-400">
            Ara
            <x-heroicon-o-arrow-right class="h-4 w-4" />
        </button>
    </form>
@endif

@if ($anahtar === 'populer_etiketler' && $populerEtiketler->isNotEmpty())
    <div class="mt-4 flex flex-wrap gap-2 {{ $koyu ? 'justify-center' : '' }}">
        @foreach ($populerEtiketler as $etiket)
            <a href="{{ url('/ilanlar') }}?q={{ urlencode($etiket) }}"
               class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1.5 text-xs font-semibold transition {{ $koyu
                   ? 'border-white/25 bg-white/10 text-white hover:bg-white/20'
                   : 'border-stone-200 bg-white hover:border-emerald-300 hover:text-emerald-700 dark:border-stone-800 dark:bg-stone-900 dark:hover:border-emerald-700 dark:hover:text-emerald-400 '.($loop->first ? 'text-emerald-600 dark:text-emerald-400' : 'text-stone-600 dark:text-stone-300') }}">
                @if ($loop->first && ! $koyu)
                    <x-heroicon-o-arrow-trending-up class="h-3.5 w-3.5 text-[#16a97f]" />
                @endif
                {{ $etiket }}
            </a>
        @endforeach
    </div>
@endif

@if ($anahtar === 'canli_sayaclar')
    {{-- Gerçek platform istatistikleri (uydurma rakam yok) --}}
    <div class="mt-7 flex flex-wrap items-center gap-x-5 gap-y-3 {{ $koyu ? 'justify-center' : '' }}">
        @if (! $koyu && $latestListings->isNotEmpty())
            <div class="flex" aria-hidden="true">
                @foreach ($latestListings->take(4) as $ilan)
                    <span class="{{ $loop->first ? '' : '-ml-2.5' }} inline-block rounded-full border-2 border-stone-50 dark:border-stone-950">
                        <x-avatar :user="$ilan->user" size="h-8 w-8" text="text-[10px]" />
                    </span>
                @endforeach
            </div>
        @endif
        <div class="flex items-baseline gap-1.5">
            <span class="text-lg font-extrabold {{ $koyu ? 'text-white' : 'text-stone-800 dark:text-stone-50' }}">{{ $stats['countries'] }}</span>
            <span class="text-xs font-medium {{ $koyu ? 'text-white/70' : 'text-stone-500 dark:text-stone-400' }}">ülke</span>
        </div>
        <div class="h-5 w-px {{ $koyu ? 'bg-white/25' : 'bg-stone-300/60 dark:bg-stone-700' }}" aria-hidden="true"></div>
        <div class="flex items-baseline gap-1.5">
            <span class="text-lg font-extrabold {{ $koyu ? 'text-white' : 'text-stone-800 dark:text-stone-50' }}">{{ $stats['categories'] }}</span>
            <span class="text-xs font-medium {{ $koyu ? 'text-white/70' : 'text-stone-500 dark:text-stone-400' }}">kategori</span>
        </div>
        <div class="h-5 w-px {{ $koyu ? 'bg-white/25' : 'bg-stone-300/60 dark:bg-stone-700' }}" aria-hidden="true"></div>
        <div class="flex items-baseline gap-1.5">
            <span class="text-lg font-extrabold {{ $koyu ? 'text-white' : 'text-[#16a97f]' }}">{{ $stats['cities'] }}</span>
            <span class="text-xs font-medium {{ $koyu ? 'text-white/70' : 'text-stone-500 dark:text-stone-400' }}">şehir</span>
        </div>
    </div>
@endif
