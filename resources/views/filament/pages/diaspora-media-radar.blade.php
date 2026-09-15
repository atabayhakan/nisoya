<x-filament-panels::page class="gcc-surface">
    <p class="text-sm text-gray-600 dark:text-gray-300">{{ $geoLabel }} · Kaynak metrikleri bağımsız doğrulama değildir. AI değerlendirmesi henüz yapılmamış içerikler açıkça işaretlenir.</p>
    <nav aria-label="Medya durum filtresi" class="flex min-w-0 flex-wrap gap-2">
        @foreach(['all' => 'Tümü', 'draft' => 'Taslak', 'published' => 'Yayında', 'archived' => 'Arşiv', 'needs_review' => 'İnceleme gerekli'] as $value => $label)
            <x-filament::button :color="$filter === $value ? 'primary' : 'gray'" wire:click="setFilter('{{ $value }}')" :aria-pressed="$filter === $value ? 'true' : 'false'">{{ $label }}</x-filament::button>
        @endforeach
    </nav>
    @if($preview && $preview['url'])
        <section aria-label="Video önizleme" class="min-w-0 rounded-xl border border-gray-200 p-4 dark:border-gray-700">
            <div class="mb-3 flex flex-wrap items-center justify-between gap-2"><h2 class="min-w-0 break-words font-semibold">{{ $preview['title'] }}</h2><x-filament::button color="gray" wire:click="closePreview">Önizlemeyi kapat</x-filament::button></div>
            <iframe wire:key="preview-{{ $preview['id'] }}" src="{{ $preview['url'] }}" title="{{ $preview['title'] }} — Instagram video önizlemesi" class="mx-auto h-[32rem] w-full max-w-md rounded-xl" loading="lazy" referrerpolicy="no-referrer" allow="fullscreen" allowfullscreen></iframe>
            <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">Video Instagram üzerinden yüklenir. Kaynak silinmiş veya erişime kapalı olabilir.</p>
        </section>
    @endif
    <div class="grid min-w-0 gap-4 md:grid-cols-2 xl:grid-cols-3">
        @forelse($cards as $card)
            <article wire:key="reel-{{ $card['id'] }}" class="min-w-0 rounded-xl border border-gray-200 bg-white p-4 dark:border-gray-700 dark:bg-gray-900">
                @if($card['thumbnail'])<img src="{{ $card['thumbnail'] }}" alt="" loading="lazy" referrerpolicy="no-referrer" class="mb-3 aspect-video w-full rounded-lg object-cover" />@endif
                <h2 class="break-words font-semibold">{{ $card['title'] }}</h2>
                <p class="my-2 break-words text-sm text-gray-600 dark:text-gray-300">{{ $card['username'] }} · {{ $card['location'] }} · {{ $card['status'] }}</p>
                <x-intelligence-score-badge :score="$card['score']" />
                <p class="my-3 break-words text-sm">{{ $card['caption'] }}</p>
                <dl class="grid grid-cols-3 gap-2 text-sm">
                    @foreach(['views' => 'İzlenme', 'likes' => 'Beğeni', 'engagement' => 'Etkileşim skoru'] as $key => $label)
                        <div class="min-w-0"><dt class="break-words text-gray-500 dark:text-gray-400">{{ $label }}</dt><dd class="break-words">{{ $card[$key] }}</dd></div>
                    @endforeach
                </dl>
                <div class="mt-4 flex min-w-0 flex-wrap gap-2">
                    @if($card['canPreview'])<x-filament::button size="sm" wire:click="openPreview({{ $card['id'] }})">Videoyu önizle</x-filament::button>@endif
                    <x-filament::button tag="a" :href="$card['editUrl']" size="sm" color="gray">İncele</x-filament::button>
                    @if($card['sourceUrl'])<a href="{{ $card['sourceUrl'] }}" target="_blank" rel="noopener noreferrer" class="inline-flex min-h-11 items-center text-sm underline">Kaynakta aç</a>@endif
                    @if($card['accountId'] && $card['activeAccount'])
                        @if(!$card['verified'])<x-filament::button size="sm" color="gray" wire:click="verifyAccount({{ $card['accountId'] }})" wire:confirm="Hesabın bu topluluğa ait olduğunu dış kaynaktan kontrol ettiniz mi?" wire:loading.attr="disabled">Hesabı doğrula</x-filament::button>@endif
                        @if($syncEnabled && $card['verified'])<x-filament::button size="sm" color="gray" wire:click="syncAccount({{ $card['accountId'] }})" wire:loading.attr="disabled">Şimdi tara</x-filament::button>@endif
                    @endif
                </div>
            </article>
        @empty
            <p role="status" class="text-sm">Bu görünümde içerik yok. Hesap ekleyerek yerel medya havuzunu başlatabilirsiniz.</p>
        @endforelse
    </div>
    <nav aria-label="Medya sayfaları" class="flex flex-wrap items-center gap-3">
        <x-filament::button color="gray" wire:click="previousRadarPage" :disabled="$radarPage <= 1">Önceki</x-filament::button>
        <span class="text-sm" aria-live="polite">Sayfa {{ $radarPage }}</span>
        <x-filament::button color="gray" wire:click="nextRadarPage" :disabled="!$hasMorePages">Sonraki</x-filament::button>
    </nav>
</x-filament-panels::page>
