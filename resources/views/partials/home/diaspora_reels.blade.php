@php
    $reels = $diasporaReels ?? collect();
    $countries = $reels->pluck('country')->filter()->unique('code')->values();
@endphp

@if ($reels->isNotEmpty())
    {{-- Küratörlü Diaspora Reels & Hikayeleri Vitrini (Model A) --}}
    <section
        class="mx-auto max-w-6xl px-4 py-8 sm:py-14"
        x-data="{
            activeCountry: 'all',
            modalOpen: false,
            selectedReel: null,
            openReel(reel) {
                this.selectedReel = reel;
                this.modalOpen = true;
                document.body.classList.add('overflow-hidden');
            },
            closeReel() {
                this.modalOpen = false;
                this.selectedReel = null;
                document.body.classList.remove('overflow-hidden');
            },
            scrollLeft() {
                this.$refs.reelsTrack.scrollBy({ left: -280, behavior: 'smooth' });
            },
            scrollRight() {
                this.$refs.reelsTrack.scrollBy({ left: 280, behavior: 'smooth' });
            }
        }"
        @keydown.escape.window="closeReel()"
        x-reveal
    >
        {{-- Bölüm Başlığı & Ülke Filtre Şeridi --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <div class="inline-flex items-center gap-2 rounded-full bg-rose-50 px-3 py-1 text-xs font-bold text-rose-600 ring-1 ring-rose-200/70 dark:bg-rose-950/50 dark:text-rose-400 dark:ring-rose-800/60">
                    <span class="relative flex h-2 w-2">
                        <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-rose-400 opacity-75"></span>
                        <span class="relative inline-flex h-2 w-2 rounded-full bg-rose-500"></span>
                    </span>
                    <span>DİASPORADAN CANLI</span>
                </div>
                <h2 class="mt-2 text-2xl sm:text-3xl font-extrabold tracking-tight text-stone-900 dark:text-stone-100">
                    Sınırların Ötesinde, Kendi İnsanımızla
                </h2>
                <p class="mt-1 text-sm text-stone-600 dark:text-stone-400 max-w-2xl">
                    Almanya'dan Kırgızistan'a, Hollanda'dan İngiltere'ye Türk diasporasının en sıcak anları, etkinlikleri ve hikayeleri.
                </p>
            </div>

            {{-- Sağ taraf: Sayaç & Mobil Navigasyon Butonları --}}
            <div class="flex items-center justify-between sm:justify-end gap-2">
                <span class="inline-flex items-center gap-1 rounded-full bg-stone-100 px-2.5 py-1 text-xs font-semibold text-stone-700 dark:bg-stone-800 dark:text-stone-300">
                    <x-heroicon-m-sparkles class="h-3.5 w-3.5 text-amber-500" />
                    {{ $reels->count() }} Paylaşım
                </span>
                <div class="flex items-center gap-1 sm:hidden">
                    <button
                        type="button"
                        @click="scrollLeft()"
                        aria-label="Önceki reels"
                        class="grid h-8 w-8 place-items-center rounded-full bg-stone-100 text-stone-700 active:bg-stone-200 dark:bg-stone-800 dark:text-stone-300 cursor-pointer"
                    >
                        <x-heroicon-m-chevron-left class="h-4 w-4" />
                    </button>
                    <button
                        type="button"
                        @click="scrollRight()"
                        aria-label="Sonraki reels"
                        class="grid h-8 w-8 place-items-center rounded-full bg-stone-100 text-stone-700 active:bg-stone-200 dark:bg-stone-800 dark:text-stone-300 cursor-pointer"
                    >
                        <x-heroicon-m-chevron-right class="h-4 w-4" />
                    </button>
                </div>
            </div>
        </div>

        {{-- Ülke Filtre Butonları --}}
        @if ($countries->count() > 1)
            <div class="mt-6 flex items-center gap-2 overflow-x-auto pb-2 scrollbar-none">
                <button
                    type="button"
                    @click="activeCountry = 'all'"
                    :class="activeCountry === 'all'
                        ? 'bg-stone-900 text-white shadow-sm dark:bg-emerald-600'
                        : 'bg-stone-100 text-stone-700 hover:bg-stone-200 dark:bg-stone-800 dark:text-stone-300 dark:hover:bg-stone-700'"
                    class="shrink-0 rounded-full px-4 py-1.5 text-xs font-semibold transition cursor-pointer"
                >
                    🌍 Tümü ({{ $reels->count() }})
                </button>
                @foreach ($countries as $country)
                    @php
                        $count = $reels->where('country_code', $country->code)->count();
                    @endphp
                    <button
                        type="button"
                        @click="activeCountry = '{{ $country->code }}'"
                        :class="activeCountry === '{{ $country->code }}'
                            ? 'bg-stone-900 text-white shadow-sm dark:bg-emerald-600'
                            : 'bg-stone-100 text-stone-700 hover:bg-stone-200 dark:bg-stone-800 dark:text-stone-300 dark:hover:bg-stone-700'"
                        class="shrink-0 inline-flex items-center gap-1.5 rounded-full px-4 py-1.5 text-xs font-semibold transition cursor-pointer"
                    >
                        <span>{{ $country->emoji }}</span>
                        <span>{{ $country->name_tr }}</span>
                        <span class="opacity-60 text-2xs">({{ $count }})</span>
                    </button>
                @endforeach
            </div>
        @endif

        {{-- Reels Kartları: Mobilde Kaydırmalı Akış (Snap Carousel), Masaüstünde Grid --}}
        <div
            x-ref="reelsTrack"
            class="mt-6 flex overflow-x-auto snap-x snap-mandatory scrollbar-none gap-3.5 pb-3 pt-1 -mx-4 px-4 sm:mx-0 sm:px-0 sm:grid sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 sm:gap-6 sm:overflow-visible"
        >
            @foreach ($reels as $reel)
                <div
                    x-show="activeCountry === 'all' || activeCountry === '{{ $reel->country_code }}'"
                    x-transition:enter="transition ease-out duration-300"
                    x-transition:enter-start="opacity-0 scale-95"
                    x-transition:enter-end="opacity-100 scale-100"
                    class="group relative flex flex-col justify-between shrink-0 w-[74vw] max-w-[270px] sm:w-auto snap-center sm:snap-align-none overflow-hidden rounded-3xl bg-stone-900 aspect-[9/16] shadow-brand-lg border border-stone-800/80 transition-all duration-300 hover:-translate-y-1.5 hover:shadow-2xl hover:border-rose-500/50 cursor-pointer"
                    @click="openReel({{ Js::from([
                        'id' => $reel->id,
                        'title' => $reel->title,
                        'caption' => $reel->caption,
                        'instagram_url' => $reel->instagram_url,
                        'embed_url' => $reel->embed_url,
                        'username' => $reel->instagram_username,
                        'location' => $reel->displayLocation(),
                        'country_emoji' => $reel->country?->emoji,
                        'video_url' => $reel->video_url,
                    ]) }})"
                >
                    {{-- Medya / Arka Plan --}}
                    @if ($reel->thumbnail_url)
                        <img
                            src="{{ $reel->thumbnail_url }}"
                            alt="{{ $reel->title }}"
                            class="absolute inset-0 h-full w-full object-cover transition-transform duration-700 group-hover:scale-105"
                            loading="lazy"
                        >
                    @elseif ($reel->video_url)
                        <video
                            src="{{ $reel->video_url }}"
                            class="absolute inset-0 h-full w-full object-cover"
                            muted
                            loop
                            playsinline
                            preload="metadata"
                            onmouseover="this.play()"
                            onmouseout="this.pause()"
                        ></video>
                    @else
                        {{-- Şık Diaspora Gradyan Fonu --}}
                        <div class="absolute inset-0 bg-gradient-to-br from-stone-900 via-stone-800 to-rose-950 flex items-center justify-center p-6 text-center">
                            <div class="absolute inset-0 opacity-10 bg-[radial-gradient(#e5e7eb_1px,transparent_1px)] [background-size:16px_16px]"></div>
                            <div class="relative flex flex-col items-center">
                                <span class="text-4xl drop-shadow-md">{{ $reel->country?->emoji ?: '🌍' }}</span>
                                <span class="mt-2 text-xs font-semibold tracking-wider uppercase text-stone-400">{{ $reel->displayLocation() }}</span>
                            </div>
                        </div>
                    @endif

                    {{-- Gradyan Karartma Katmanı --}}
                    <div class="pointer-events-none absolute inset-0 bg-gradient-to-t from-stone-950 via-stone-950/40 to-black/30 group-hover:from-stone-950/95 transition-colors"></div>

                    {{-- Üst Çubuk: Ülke Rozeti & Instagram Hesabı --}}
                    <div class="relative z-10 p-4 flex items-center justify-between gap-2">
                        <span class="inline-flex items-center gap-1 rounded-full bg-black/50 backdrop-blur-md px-2.5 py-1 text-xs font-medium text-white ring-1 ring-white/20">
                            <span>{{ $reel->country?->emoji }}</span>
                            <span class="truncate max-w-[90px]">{{ $reel->city ?: $reel->country?->name_tr }}</span>
                        </span>

                        @if ($reel->instagram_username)
                            <span class="inline-flex items-center gap-1 rounded-full bg-gradient-to-r from-purple-600 via-pink-600 to-rose-500 px-2.5 py-1 text-2xs font-bold text-white shadow-sm ring-1 ring-white/20">
                                <svg class="h-3 w-3" fill="currentColor" viewBox="0 0 24 24">
                                    <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                                </svg>
                                <span class="truncate max-w-[85px]">{{ $reel->instagram_username }}</span>
                            </span>
                        @endif
                    </div>

                    {{-- Orta: Oynatma Butonu İkonu --}}
                    <div class="relative z-10 flex flex-1 items-center justify-center">
                        <div class="grid h-14 w-14 place-items-center rounded-full bg-white/25 backdrop-blur-md text-white ring-2 ring-white/50 transition-all duration-300 group-hover:scale-110 group-hover:bg-rose-600 group-hover:ring-rose-400 shadow-xl">
                            <svg class="h-6 w-6 translate-x-0.5 fill-current" viewBox="0 0 24 24">
                                <path d="M8 5v14l11-7z"/>
                            </svg>
                        </div>
                    </div>

                    {{-- Alt Bilgi: Başlık & Altyazı --}}
                    <div class="relative z-10 p-4 pt-0">
                        <h3 class="text-sm sm:text-base font-bold text-white drop-shadow-md line-clamp-2 leading-snug group-hover:text-rose-200 transition-colors">
                            {{ $reel->title }}
                        </h3>
                        @if ($reel->caption)
                            <p class="mt-1 text-xs text-stone-300/90 line-clamp-2 leading-relaxed">
                                {{ $reel->caption }}
                            </p>
                        @endif

                        <div class="mt-3 flex items-center justify-between border-t border-white/10 pt-2 text-2xs text-stone-300">
                            <span class="font-medium inline-flex items-center gap-1 text-white">
                                <span>İzlemek için tıkla</span>
                                <span>→</span>
                            </span>
                            <a
                                href="{{ $reel->instagram_url }}"
                                target="_blank"
                                rel="noopener noreferrer"
                                class="text-rose-300 hover:text-rose-100 hover:underline inline-flex items-center gap-0.5"
                                @click.stop
                            >
                                <span>Instagram</span>
                                <x-heroicon-m-arrow-top-right-on-square class="h-3 w-3" />
                            </a>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        {{-- Mobilde Yana Kaydırma İpucu --}}
        <div class="mt-2 flex items-center justify-between px-1 text-2xs text-stone-500 dark:text-stone-400 sm:hidden">
            <span class="inline-flex items-center gap-1.5">
                <svg class="h-3.5 w-3.5 animate-pulse text-rose-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3" />
                </svg>
                <span>Yana kaydırarak izleyin</span>
            </span>
            <span class="font-medium text-stone-400">9:16 Video</span>
        </div>

        {{-- Alpine.js Instagram Reels Oynatıcı Modalı --}}
        <div
            x-show="modalOpen"
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-black/80 backdrop-blur-md"
            style="display: none;"
        >
            <div
                @click.outside="closeReel()"
                x-show="modalOpen"
                x-transition:enter="transition ease-out duration-300"
                x-transition:enter-start="opacity-0 scale-95 translate-y-4"
                x-transition:enter-end="opacity-100 scale-100 translate-y-0"
                x-transition:leave="transition ease-in duration-200"
                x-transition:leave-start="opacity-100 scale-100 translate-y-0"
                x-transition:leave-end="opacity-0 scale-95 translate-y-4"
                class="relative flex flex-col w-full max-w-md max-h-[90vh] rounded-3xl bg-stone-900 border border-stone-800 shadow-2xl overflow-hidden text-white"
            >
                {{-- Modal Üst Başlık --}}
                <div class="flex items-center justify-between border-b border-stone-800 px-5 py-3.5 bg-stone-950/80">
                    <div class="flex items-center gap-2 truncate pr-2">
                        <span class="text-lg" x-text="selectedReel?.country_emoji"></span>
                        <div class="truncate">
                            <div class="text-xs font-bold text-white truncate" x-text="selectedReel?.username || 'Diaspora Hikayesi'"></div>
                            <div class="text-2xs text-stone-400 truncate" x-text="selectedReel?.location"></div>
                        </div>
                    </div>
                    <button
                        type="button"
                        @click="closeReel()"
                        class="grid h-8 w-8 place-items-center rounded-full bg-stone-800 text-stone-300 hover:bg-stone-700 hover:text-white transition cursor-pointer"
                    >
                        <x-heroicon-m-x-mark class="h-5 w-5" />
                    </button>
                </div>

                {{-- Modal İçeriği / Oynatıcı --}}
                <div class="relative flex-1 overflow-y-auto bg-black p-2 flex flex-col items-center justify-center min-h-[420px]">
                    <template x-if="selectedReel?.embed_url">
                        <iframe
                            :src="selectedReel?.embed_url"
                            class="w-full h-[480px] rounded-xl border-0 bg-stone-950"
                            allowtransparency="true"
                            allowfullscreen="true"
                            frameborder="0"
                            scrolling="no"
                        ></iframe>
                    </template>
                    <template x-if="!selectedReel?.embed_url && selectedReel?.video_url">
                        <video
                            :src="selectedReel?.video_url"
                            class="w-full max-h-[480px] rounded-xl object-contain bg-black"
                            controls
                            autoplay
                            playsinline
                        ></video>
                    </template>
                </div>

                {{-- Modal Alt Bilgi & Doğrudan Instagram Butonu --}}
                <div class="p-4 border-t border-stone-800 bg-stone-950/90 flex flex-col gap-2.5">
                    <h4 class="text-sm font-bold text-white leading-snug" x-text="selectedReel?.title"></h4>
                    <p class="text-xs text-stone-300 line-clamp-2" x-text="selectedReel?.caption" x-show="selectedReel?.caption"></p>
                    <a
                        :href="selectedReel?.instagram_url"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="mt-1 inline-flex w-full items-center justify-center gap-2 rounded-xl bg-gradient-to-r from-purple-600 via-pink-600 to-rose-500 px-4 py-2.5 text-xs font-bold text-white shadow-md hover:opacity-95 transition"
                    >
                        <svg class="h-4 w-4 fill-current" viewBox="0 0 24 24">
                            <path d="M12 2.163c3.204 0 3.584.012 4.85.07 3.252.148 4.771 1.691 4.919 4.919.058 1.265.069 1.645.069 4.849 0 3.205-.012 3.584-.069 4.849-.149 3.225-1.664 4.771-4.919 4.919-1.266.058-1.644.07-4.85.07-3.204 0-3.584-.012-4.849-.07-3.26-.149-4.771-1.699-4.919-4.92-.058-1.265-.07-1.644-.07-4.849 0-3.204.013-3.583.07-4.849.149-3.227 1.664-4.771 4.919-4.919 1.266-.057 1.645-.069 4.849-.069zm0-2.163c-3.259 0-3.667.014-4.947.072-4.358.2-6.78 2.618-6.98 6.98-.059 1.281-.073 1.689-.073 4.948 0 3.259.014 3.668.072 4.948.2 4.358 2.618 6.78 6.98 6.98 1.281.058 1.689.072 4.948.072 3.259 0 3.668-.014 4.948-.072 4.354-.2 6.782-2.618 6.979-6.98.059-1.28.073-1.689.073-4.948 0-3.259-.014-3.667-.072-4.947-.196-4.354-2.617-6.78-6.979-6.98-1.281-.059-1.69-.073-4.949-.073zm0 5.838c-3.403 0-6.162 2.759-6.162 6.162s2.759 6.163 6.162 6.163 6.162-2.759 6.162-6.163c0-3.403-2.759-6.162-6.162-6.162zm0 10.162c-2.209 0-4-1.79-4-4 0-2.209 1.791-4 4-4s4 1.791 4 4c0 2.21-1.791 4-4 4zm6.406-11.845c-.796 0-1.441.645-1.441 1.44s.645 1.44 1.441 1.44c.795 0 1.439-.645 1.439-1.44s-.644-1.44-1.439-1.44z"/>
                        </svg>
                        <span>Instagram'da Aç ve Keşfet</span>
                    </a>
                </div>
            </div>
        </div>
    </section>
@endif
