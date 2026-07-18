{{-- Vurgu kartı medya öğesi (bkz. App\Filament\Support\HighlightMediaBlocks,
     resources/views/home.blade.php). $item = ['type' => ..., 'data' => [...]]. --}}
@php
    $data = $item['data'] ?? [];
@endphp

@switch($item['type'] ?? '')
    @case('resim')
        <img
            src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($data['path'] ?? '') }}"
            alt=""
            class="h-full w-full object-cover"
            loading="lazy"
        >
        @break

    @case('youtube')
        @php($youtubeId = \App\Support\HighlightMedia::youtubeId($data['url'] ?? null))
        @if ($youtubeId)
            <iframe
                class="h-full w-full"
                src="https://www.youtube-nocookie.com/embed/{{ $youtubeId }}"
                title="YouTube video"
                loading="lazy"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                allowfullscreen
            ></iframe>
        @endif
        @break

    @case('video')
        <video class="h-full w-full object-cover" controls preload="metadata">
            <source src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($data['path'] ?? '') }}" type="video/mp4">
        </video>
        @break
@endswitch
