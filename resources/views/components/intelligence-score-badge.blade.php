@props(['score'])

<div class="flex min-w-0 flex-wrap items-center gap-2" title="{{ $score['explanation'] }}">
    <x-filament::badge :color="$score['color']">{{ $score['label'] }}</x-filament::badge>
    <x-filament::badge color="gray">{{ $score['ai'] }}</x-filament::badge>
</div>
