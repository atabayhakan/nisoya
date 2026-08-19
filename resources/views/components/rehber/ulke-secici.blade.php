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
@props(['aktif' => null, 'baslik' => 'Başka ülke:'])

@php
    $hazirUlkeler = app(\App\Services\RehberYuzeyi::class)->hazirUlkeler();

    if ($aktif) {
        $hazirUlkeler = $hazirUlkeler->reject(fn ($c) => $c->code === $aktif->code);
    }
@endphp

@if ($hazirUlkeler->isNotEmpty())
    <div {{ $attributes->class(['flex flex-wrap items-center gap-2']) }}>
        @if ($baslik)
            <span class="text-sm font-medium text-stone-500 dark:text-stone-400">{{ $baslik }}</span>
        @endif
        @foreach ($hazirUlkeler as $c)
            <a href="{{ route('rehber.ulke', strtolower($c->code)) }}"
                class="inline-flex items-center gap-1.5 rounded-full border border-stone-200 bg-white px-3 py-1.5 text-sm font-medium text-stone-700 transition hover:border-emerald-300 hover:text-emerald-700 dark:border-stone-700 dark:bg-stone-800/50 dark:text-stone-200 dark:hover:border-emerald-700 dark:hover:text-emerald-400">
                <span aria-hidden="true">{{ $c->emoji }}</span>
                {{ $c->name_tr }}
            </a>
        @endforeach
    </div>
@endif
