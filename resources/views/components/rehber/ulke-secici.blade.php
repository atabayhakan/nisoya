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
@props(['aktif' => null, 'baslik' => 'Başka bir ülke:'])

@php
    $hazirUlkeler = app(\App\Services\RehberYuzeyi::class)->hazirUlkeler();

    if ($aktif) {
        $hazirUlkeler = $hazirUlkeler->reject(fn ($c) => $c->code === $aktif->code);
    }
@endphp

@if ($hazirUlkeler->isNotEmpty())
    <div {{ $attributes->class(['inline-flex flex-wrap items-center gap-2.5 rounded-2xl border border-stone-200/90 bg-stone-50/80 px-3.5 py-1.5 dark:border-stone-800 dark:bg-stone-900/60 shadow-2xs']) }}>
        <div class="flex items-center gap-1.5 text-xs font-bold text-stone-700 dark:text-stone-300">
            <x-heroicon-o-globe-alt class="h-4 w-4 text-emerald-700 dark:text-emerald-400 shrink-0" />
            <span>{{ $baslik }}</span>
        </div>

        <div class="relative">
            <select
                onchange="if (this.value) window.location.href = this.value"
                aria-label="Başka bir ülke rehberine geç"
                class="h-8 rounded-xl border border-stone-200 bg-white pl-2.5 pr-8 text-xs font-semibold text-stone-800 shadow-2xs transition hover:border-emerald-300 focus:border-emerald-600 focus:outline-none focus:ring-1 focus:ring-emerald-600 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100 dark:hover:border-emerald-600">
                <option value="">Ülke seçin ({{ $hazirUlkeler->count() }} hazır ülke)...</option>
                @foreach ($hazirUlkeler as $c)
                    <option value="{{ route('rehber.ulke', strtolower($c->code)) }}">
                        {{ $c->emoji }} {{ $c->name_tr }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>
@endif

