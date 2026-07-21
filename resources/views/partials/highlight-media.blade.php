{{-- Vurgu kartı medya öğesi (bkz. App\Filament\Support\HighlightMediaBlocks,
     resources/views/home.blade.php). $item = ['type' => ..., 'data' => [...]]. --}}
@php
    $data = $item['data'] ?? [];
@endphp

@switch($item['type'] ?? '')
    @case('resim')
        @php
            $rawPath = trim($data['url'] ?? $data['path'] ?? '');
            $imageUrl = null;
            if ($rawPath !== '') {
                if (\Illuminate\Support\Str::startsWith($rawPath, ['http://', 'https://', '/'])) {
                    $imageUrl = $rawPath;
                } else {
                    $imageUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($rawPath);
                }
            }
        @endphp
        @if ($imageUrl)
            <img
                src="{{ $imageUrl }}"
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
            $rawUrl = trim($data['url'] ?? $data['path'] ?? '');
            $autoplay = $data['autoplay'] ?? true;
            $muted = $data['muted'] ?? true;
            $loop = $data['loop'] ?? true;

            $videoUrl = null;
            if ($rawUrl !== '') {
                if (\Illuminate\Support\Str::startsWith($rawUrl, ['http://', 'https://', '/'])) {
                    $videoUrl = $rawUrl;
                } else {
                    $videoUrl = \Illuminate\Support\Facades\Storage::disk('public')->url($rawUrl);
                }
            }
        @endphp
        @if ($videoUrl)
            <video
                class="h-full w-full object-cover"
                controls
                preload="metadata"
                @if ($autoplay) autoplay @endif
                @if ($muted) muted @endif
                @if ($loop) loop @endif
                playsinline
            >
                <source src="{{ $videoUrl }}" type="video/mp4">
            </video>
        @endif
        @break
@endswitch
