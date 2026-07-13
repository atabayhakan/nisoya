@props(['zoneKey'])

@php
    $zone = \App\Models\Zone::byKey($zoneKey);
@endphp

@if ($zone && $zone->isCurrentlyActive() && ! empty($zone->blocks))
    @foreach ($zone->blocks as $block)
        @if (($block['type'] ?? '') === 'reklam')
            <x-ad-slot :slot-id="$block['data']['slot_id'] ?? null" :format="$block['data']['format'] ?? 'auto'" />
        @else
            @include('partials.page-block', ['block' => $block])
        @endif
    @endforeach
@endif
