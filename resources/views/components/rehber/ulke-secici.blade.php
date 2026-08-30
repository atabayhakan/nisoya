{{--
    Rehber ülke değiştirici (F3) — 2026-08-01 planının K1 kararındaki
    "her rehber yüzeyinde elle ülke değiştirici" taahhüdünün eksik kalan
    yarısı. `RehberYuzeyi::hazirUlkeler()`'i kendi çözer; hangi sayfadan
    çağrıldığına bakmaksızın hep gerçek (yayında içeriği olan) ülkeleri
    listeler. Aktif ülke listeden düşürülür — "buradasın zaten" göstermenin
    anlamı yok. Hiç alternatif kalmazsa (tek ülke ya da rehber tamamen boş)
    kendini hiç render etmez; boş bir "başka ülke" satırı yalancı seçenek
    sunmuş olurdu.
--}}
@props(['aktif' => null, 'baslik' => 'Farklı Ülke Rehberi:'])

@php
    $hazirUlkeler = app(\App\Services\RehberYuzeyi::class)->hazirUlkeler();

    if ($aktif) {
        $hazirUlkeler = $hazirUlkeler->reject(fn ($c) => $c->code === $aktif->code);
    }

    // Öncelikli diaspora ülkeleri
    $oncelikliKodlar = ['DE', 'NL', 'GB', 'FR', 'AT', 'BE', 'CH', 'US', 'AZ', 'KZ', 'UZ', 'TM'];
    $oncelikli = $hazirUlkeler->filter(fn ($c) => in_array($c->code, $oncelikliKodlar, true))->take(7);
    $diger = $hazirUlkeler->reject(fn ($c) => $oncelikli->contains('code', $c->code));
@endphp

@if ($hazirUlkeler->isNotEmpty())
    <div {{ $attributes->class(['rounded-2xl border border-stone-200/90 bg-stone-50/80 p-4 dark:border-stone-800 dark:bg-stone-900/60 shadow-xs']) }}
         x-data="{ showAll: false, search: '' }">
        <div class="flex flex-wrap items-center justify-between gap-3">
            <div class="flex flex-wrap items-center gap-2">
                @if ($baslik)
                    <span class="text-xs font-bold uppercase tracking-wider text-stone-500 dark:text-stone-400 mr-1">{{ $baslik }}</span>
                @endif
                @foreach ($oncelikli as $c)
                    <a href="{{ route('rehber.ulke', strtolower($c->code)) }}"
                        class="inline-flex items-center gap-1.5 rounded-xl border border-stone-200 bg-white px-3 py-1.5 text-xs font-semibold text-stone-700 shadow-xs transition hover:-translate-y-0.5 hover:border-emerald-300 hover:text-emerald-700 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-200 dark:hover:border-emerald-700 dark:hover:text-emerald-400">
                        <span aria-hidden="true">{{ $c->emoji }}</span>
                        {{ $c->name_tr }}
                    </a>
                @endforeach
            </div>

            @if ($diger->isNotEmpty())
                <button type="button"
                    @click="showAll = !showAll"
                    class="inline-flex items-center gap-1.5 rounded-xl border border-dashed border-stone-300 bg-white px-3 py-1.5 text-xs font-bold text-emerald-700 transition hover:border-emerald-400 hover:bg-emerald-50 dark:border-stone-700 dark:bg-stone-800 dark:text-emerald-400 dark:hover:bg-emerald-950/40">
                    <span x-text="showAll ? 'Daralt ↑' : 'Tüm Ülkeler ({{ $hazirUlkeler->count() }}) ↓'">Tüm Ülkeler ({{ $hazirUlkeler->count() }}) ↓</span>
                </button>
            @endif
        </div>

        @if ($diger->isNotEmpty())
            <div x-show="showAll"
                 x-collapse
                 style="display: none"
                 class="mt-4 border-t border-stone-200/80 pt-4 dark:border-stone-800">
                <div class="mb-3 max-w-xs">
                    <input type="text"
                           x-model="search"
                           placeholder="Ülke ara..."
                           class="w-full rounded-xl border border-stone-200 bg-white px-3 py-1.5 text-xs text-stone-800 placeholder-stone-400 focus:border-emerald-500 focus:outline-none dark:border-stone-700 dark:bg-stone-800 dark:text-stone-200" />
                </div>
                <div class="flex flex-wrap gap-2 max-h-60 overflow-y-auto pr-1">
                    @foreach ($diger as $c)
                        <a href="{{ route('rehber.ulke', strtolower($c->code)) }}"
                            x-show="!search || '{{ mb_strtolower($c->name_tr) }}'.includes(search.toLowerCase())"
                            class="inline-flex items-center gap-1.5 rounded-xl border border-stone-200/70 bg-white px-2.5 py-1 text-xs font-medium text-stone-700 transition hover:border-emerald-300 hover:text-emerald-700 dark:border-stone-700/80 dark:bg-stone-800 dark:text-stone-300 dark:hover:border-emerald-700 dark:hover:text-emerald-400">
                            <span aria-hidden="true">{{ $c->emoji }}</span>
                            {{ $c->name_tr }}
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
@endif
