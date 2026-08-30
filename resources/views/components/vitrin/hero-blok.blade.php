@props(['anahtar', 'countries', 'stats', 'latestListings', 'koyu' => false, 'heroCips' => null, 'ziyaretciUlke' => null])

@php
    $aiAktif = app(\App\Services\NisoyaAiYonlendirici::class)->isEnabled();
@endphp

@if ($anahtar === 'arama')
    <div class="mt-6 sm:mt-7" x-data="{ searchTab: 'ilan' }">
        <div class="rounded-3xl border border-stone-200/90 bg-white/95 p-2 sm:p-2.5 shadow-2xl backdrop-blur-xl transition dark:border-stone-700/80 dark:bg-stone-900/95">
            
            @if ($aiAktif)
                {{-- Sekme Başlıkları (İlan Arama & AI Asistan) --}}
                <div class="mb-2 flex items-center justify-between border-b border-stone-100 px-2 pb-2 pt-1 text-xs dark:border-stone-800">
                    <div class="flex items-center gap-1">
                        <button type="button"
                                @click="searchTab = 'ilan'"
                                :class="searchTab === 'ilan' ? 'bg-emerald-50 text-emerald-800 font-bold dark:bg-emerald-950/60 dark:text-emerald-300' : 'text-stone-600 hover:text-stone-900 dark:text-stone-400 dark:hover:text-stone-200 font-medium'"
                                class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 transition">
                            <x-heroicon-o-magnifying-glass class="h-3.5 w-3.5" />
                            <span>İlan &amp; Hizmet Ara</span>
                        </button>
                        <button type="button"
                                @click="searchTab = 'ai'"
                                :class="searchTab === 'ai' ? 'bg-emerald-50 text-emerald-800 font-bold dark:bg-emerald-950/60 dark:text-emerald-300' : 'text-stone-600 hover:text-stone-900 dark:text-stone-400 dark:hover:text-stone-200 font-medium'"
                                class="inline-flex items-center gap-1.5 rounded-lg px-3 py-1.5 transition">
                            <x-heroicon-s-sparkles class="h-3.5 w-3.5 text-emerald-700 dark:text-emerald-400" />
                            <span>Nisoya AI Asistanı</span>
                            <span class="rounded bg-emerald-100 px-1 py-0.2 text-[9px] font-extrabold text-emerald-800 dark:bg-emerald-900/80 dark:text-emerald-300">Yeni</span>
                        </button>
                    </div>
                </div>
            @endif

            {{-- 1. Normal İlan Arama Formu --}}
            <div x-show="searchTab === 'ilan'">
                <form action="{{ url('/ilanlar') }}" method="GET" class="flex flex-col gap-2 sm:flex-row sm:items-center">
                    <div class="flex min-w-0 flex-1 items-center gap-2.5 px-3">
                        <x-heroicon-o-magnifying-glass class="h-5 w-5 shrink-0 text-stone-500 dark:text-stone-400" />
                        <input type="text" name="q" placeholder="{{ setting('home.arama_placeholder') ?: 'Kim lazım? (örn. Nakliyeci, Usta, Ev, Avukat...)' }}"
                               class="h-12 w-full min-w-0 border-0 bg-transparent p-0 text-sm sm:text-base font-medium text-stone-900 placeholder-stone-500 focus:outline-none focus:ring-0 dark:text-stone-100 dark:placeholder-stone-500">
                    </div>
                    <div class="h-7 w-px bg-stone-200 dark:bg-stone-700 hidden sm:block"></div>
                    <select name="ulke" aria-label="Ülke seç" class="h-12 w-full shrink-0 truncate rounded-xl border border-stone-200 bg-stone-50 px-3 text-sm font-semibold text-stone-700 focus:outline-none focus:ring-2 focus:ring-emerald-500 sm:w-40 sm:border-0 sm:bg-transparent dark:border-stone-700 dark:bg-stone-800 dark:text-stone-200 sm:dark:bg-transparent">
                        <option value="">🌍 Tüm ülkeler</option>
                        @foreach ($countries as $country)
                            <option value="{{ $country->code }}" @selected($ziyaretciUlke?->code === $country->code)>{{ $country->emoji }} {{ $country->name_tr }}</option>
                        @endforeach
                    </select>
                    <button type="submit" class="inline-flex h-12 w-full items-center justify-center gap-2 rounded-xl bg-emerald-700 px-6 text-sm font-bold text-white shadow-brand transition hover:bg-emerald-800 active:scale-[0.98] sm:w-auto dark:bg-emerald-500 dark:text-stone-900 dark:hover:bg-emerald-400">
                        <span>Ara</span>
                        <x-heroicon-o-arrow-right class="h-4 w-4 stroke-2" />
                    </button>
                </form>
            </div>

            {{-- 2. Nisoya AI Asistanı Arama Formu --}}
            @if ($aiAktif)
                <div x-show="searchTab === 'ai'" x-cloak>
                    <x-nisoya-ai-arama :ziyaretci-ulke="$ziyaretciUlke" />
                </div>
            @endif
        </div>

        {{-- Yapısal Güven Bildirimi --}}
        <div class="mt-3 flex items-center justify-center sm:justify-start gap-2 text-xs {{ $koyu ? 'text-white/80' : 'text-stone-500 dark:text-stone-400' }}">
            <span class="inline-flex items-center gap-1.5 font-semibold {{ $koyu ? 'text-white' : 'text-stone-700 dark:text-stone-200' }}">
                <x-heroicon-s-shield-check class="h-4 w-4 text-emerald-700 dark:text-emerald-400 inline" />
                Nisoya'dan para geçmez.
            </span>
            <span class="hidden sm:inline">Komisyon yok, aracı yok.</span>
            <span class="h-3 w-px bg-stone-300/60 dark:bg-stone-700 hidden sm:inline" aria-hidden="true"></span>
            <a href="{{ url('/guvenli-alisveris') }}" class="font-medium underline decoration-dotted underline-offset-2 hover:text-emerald-700 dark:hover:text-emerald-400">
                Dolandırılmamak için →
            </a>
        </div>
    </div>
@endif

{{-- Popüler Kategori Etiketleri --}}
@if ($anahtar === 'populer_etiketler' && $heroCips !== null && $heroCips->isNotEmpty())
    <div class="mt-4 flex flex-wrap items-center gap-2 {{ $koyu ? 'justify-center' : '' }}">
        <span class="text-xs font-semibold {{ $koyu ? 'text-white/80' : 'text-stone-600 dark:text-stone-400' }}">Popüler:</span>
        @foreach ($heroCips as $kategori)
            <a href="{{ url('/ilanlar') }}?kategori={{ $kategori->slug }}"
               class="inline-flex items-center gap-1.5 rounded-full border px-3 py-1 text-xs font-semibold backdrop-blur-md transition hover:scale-105 {{ $koyu
                   ? 'border-white/20 bg-white/10 text-white hover:bg-white/20 shadow-2xs'
                   : 'border-stone-200/90 bg-white text-stone-700 shadow-2xs hover:border-emerald-300 hover:text-emerald-700 dark:border-stone-800 dark:bg-stone-900 dark:text-stone-300 dark:hover:border-emerald-700 dark:hover:text-emerald-400 '.($loop->first ? '!border-emerald-300/80 !text-emerald-700 dark:!text-emerald-400' : '') }}">
                @if ($loop->first)
                    <x-heroicon-o-arrow-trending-up class="h-3.5 w-3.5 text-emerald-700 dark:text-emerald-400" />
                @endif
                {{ $kategori->name }}
            </a>
        @endforeach
    </div>
@endif

{{-- Canlı Sayaçlar ve Ülke Rehberi Şeridi --}}
@if ($anahtar === 'canli_sayaclar')
    <div class="mt-6 flex flex-wrap items-center gap-x-5 gap-y-3 {{ $koyu ? 'justify-center' : '' }}">
        @if (! $koyu && $latestListings->isNotEmpty())
            <div class="flex" aria-hidden="true">
                @foreach ($latestListings->take(4) as $ilan)
                    <span class="{{ $loop->first ? '' : '-ml-2.5' }} inline-block rounded-full border-2 border-white dark:border-stone-900 shadow-2xs">
                        <x-avatar :user="$ilan->user" size="h-8 w-8" text="text-2xs" />
                    </span>
                @endforeach
            </div>
        @endif

        @php $serit = $stats['serit'] ?? ['tip' => 'sayilar']; @endphp

        @if ($serit['tip'] === 'rehber')
            <a href="{{ url('/'.strtolower($serit['ulke']->code)) }}"
               class="inline-flex items-center gap-2 rounded-2xl border px-3.5 py-1.5 text-xs font-semibold backdrop-blur-md transition hover:-translate-y-0.5 {{ $koyu ? 'border-white/20 bg-white/10 text-white shadow-2xs hover:bg-white/15' : 'border-stone-200/90 bg-white text-stone-700 shadow-2xs hover:border-emerald-300 dark:border-stone-800 dark:bg-stone-900 dark:text-stone-200' }}">
                <span class="text-sm" aria-hidden="true">{{ $serit['ulke']->emoji }}</span>
                <span class="font-bold text-stone-900 dark:text-stone-100 {{ $koyu ? '!text-white' : '' }}">{{ $serit['ulke']->name_tr }} rehberi</span>
                <span class="h-3 w-px bg-stone-300/60 dark:bg-stone-700 {{ $koyu ? '!bg-white/30' : '' }}" aria-hidden="true"></span>
                <span class="{{ $koyu ? 'text-white/80' : 'text-stone-500 dark:text-stone-400' }}">{{ $serit['temsilcilik'] }} temsilcilik · {{ $serit['islem'] }} işlem</span>
                <span class="font-bold text-emerald-700 dark:text-emerald-400 ml-0.5">→</span>
            </a>
        @elseif ($serit['tip'] === 'cagri')
            <a href="{{ url('/panel/ilan/yeni') }}"
               class="inline-flex items-center gap-2 rounded-2xl border px-3.5 py-1.5 text-xs font-semibold backdrop-blur-md transition hover:-translate-y-0.5 {{ $koyu ? 'border-white/20 bg-white/10 text-white shadow-2xs hover:bg-white/15' : 'border-stone-200/90 bg-white text-stone-700 shadow-2xs hover:border-emerald-300 dark:border-stone-800 dark:bg-stone-900 dark:text-stone-200' }}">
                <span class="font-bold text-stone-900 dark:text-stone-100 {{ $koyu ? '!text-white' : '' }}">Şehrinde ilk ilanı sen ver</span>
                <span class="h-3 w-px bg-stone-300/60 dark:bg-stone-700 {{ $koyu ? '!bg-white/30' : '' }}" aria-hidden="true"></span>
                <span class="{{ $koyu ? 'text-white/80' : 'text-stone-500 dark:text-stone-400' }}">ücretsiz, komisyonsuz</span>
                <span class="font-bold text-emerald-700 dark:text-emerald-400 ml-0.5">→</span>
            </a>
        @else
            <div class="flex items-center gap-4 rounded-2xl border border-stone-200/80 bg-white/90 px-4 py-2 shadow-2xs backdrop-blur-md dark:border-stone-800 dark:bg-stone-900/90">
                <div class="flex items-baseline gap-1.5">
                    <span class="text-base font-extrabold text-stone-900 dark:text-stone-50">{{ $stats['countries'] }}</span>
                    <span class="text-xs font-medium text-stone-500 dark:text-stone-400">ülke</span>
                </div>
                <div class="h-4 w-px bg-stone-200 dark:bg-stone-700" aria-hidden="true"></div>
                <div class="flex items-baseline gap-1.5">
                    <span class="text-base font-extrabold text-stone-900 dark:text-stone-50">{{ $stats['activeCities'] ?? $stats['cities'] }}</span>
                    <span class="text-xs font-medium text-stone-500 dark:text-stone-400">şehir</span>
                </div>
                <div class="h-4 w-px bg-stone-200 dark:bg-stone-700" aria-hidden="true"></div>
                <div class="flex items-baseline gap-1.5">
                    <span class="text-base font-extrabold text-emerald-700 dark:text-emerald-400">{{ $stats['activeListings'] ?? 0 }}</span>
                    <span class="text-xs font-medium text-stone-500 dark:text-stone-400">aktif ilan</span>
                </div>
            </div>
        @endif
    </div>
@endif
