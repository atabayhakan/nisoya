{{-- Vurgu kartı medya öğesi (bkz. App\Filament\Support\HighlightMediaBlocks,
     resources/views/home.blade.php). $item = ['type' => ..., 'data' => [...]]. --}}
@php
    $data = $item['data'] ?? [];
@endphp

@switch($item['type'] ?? '')
    @case('resim')
        @if (! empty($data['path']))
            <img
                src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($data['path']) }}"
                alt=""
                class="h-full w-full object-cover"
                loading="lazy"
            >
        @endif
        @break

    @case('youtube')
        @php
            $youtubeId = \App\Support\HighlightMedia::youtubeId($data['url'] ?? null);
            $autoplay = ($data['autoplay'] ?? true) ? 1 : 0;
            $muted = ($data['muted'] ?? true) ? 1 : 0;
        @endphp
        @if ($youtubeId)
            <iframe
                class="h-full w-full"
                src="https://www.youtube-nocookie.com/embed/{{ $youtubeId }}?autoplay={{ $autoplay }}&mute={{ $muted }}&controls=1"
                title="YouTube video"
                loading="lazy"
                allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture"
                allowfullscreen
            ></iframe>
        @endif
        @break

    @case('video')
        @php
            $path = $data['path'] ?? null;
            $autoplay = $data['autoplay'] ?? true;
            $muted = $data['muted'] ?? true;
            $loop = $data['loop'] ?? true;
        @endphp
        @if ($path)
            <video
                class="h-full w-full object-cover"
                controls
                preload="metadata"
                @if ($autoplay) autoplay @endif
                @if ($muted) muted @endif
                @if ($loop) loop @endif
                playsinline
            >
                <source src="{{ \Illuminate\Support\Facades\Storage::disk('public')->url($path) }}" type="video/mp4">
            </video>
        @endif
        @break
@endswitch
