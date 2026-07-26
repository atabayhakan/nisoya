@props(['size' => 'sm'])

@php
    $yeni = \App\Support\Tema::tasarimModu() === 'yeni';
    $px = match ($size) {
        'xs' => 14,
        'base' => 20,
        default => 16,
    };
@endphp

{{-- Faz İ3 ("2. Tasarım" pilotu): jenerik ✓ yerine soyut bir "mühür" —
     Mühür Kızılı → Bakır geçişli küçük bir daire. "1. Tasarım"da eski
     düz ✓ aynen kalıyor, hiçbir şey değişmiyor. --}}
@if ($yeni)
    <span
        title="Doğrulanmış üye"
        class="inline-flex shrink-0 items-center justify-center rounded-full align-middle"
        style="height: {{ $px }}px; width: {{ $px }}px; background: linear-gradient(135deg, var(--nisoya-seal, #c1440e), #a9713c);"
    >
        <x-heroicon-s-check class="text-white" style="height: {{ round($px * 0.6) }}px; width: {{ round($px * 0.6) }}px;" />
    </span>
@else
    <span title="Doğrulanmış üye" class="text-emerald-600 dark:text-emerald-400">✓</span>
@endif
