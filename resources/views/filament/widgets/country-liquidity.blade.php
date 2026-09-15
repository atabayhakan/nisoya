<x-filament-widgets::widget class="gcc-surface">
    <x-filament::section>
        <x-slot name="heading">Pazar ve başlangıç radarı · {{ $context->label() }}</x-slot>
        <x-slot name="description">Aktif ülke kataloğu · son ölçüm {{ $measuredAt }} · en fazla 60 saniye önbellek</x-slot>
        <div class="grid min-w-0 gap-3 sm:grid-cols-2 xl:grid-cols-3">
            @foreach(['countries' => 'Ülke', 'listings' => 'Aktif gerçek ilan', 'jobs' => 'Açık iş ilanı', 'users' => 'Aktif gerçek kullanıcı', 'reels' => 'Yayımlanan reel', 'cold' => 'Başlangıç pazarı'] as $key => $label)
                <div class="min-w-0 rounded-xl bg-gray-50 p-4 dark:bg-gray-800"><p class="text-sm text-gray-600 dark:text-gray-300">{{ $label }}</p><p class="break-words text-2xl font-semibold">{{ number_format($totals[$key], 0, ',', '.') }}</p></div>
            @endforeach
        </div>
        <p class="my-4 text-sm text-gray-600 dark:text-gray-300">Puan arz göstergesidir; işlem hacmi veya AI tahmini değildir. Ülkesi bilinmeyen kayıtlar ve kapalı pazarlar bu toplamlara dahil değildir. Şehir görünümü kayıtlı ad eşleşmelerini kullanır; belirsiz adlar dahil edilmez.</p>
        <label class="grid min-w-0 gap-1 text-sm">Pazar ara
            <x-filament::input.wrapper><x-filament::input wire:model.live.debounce.300ms="search" maxlength="80" placeholder="Ülke veya ISO kodu" /></x-filament::input.wrapper>
        </label>
        <div class="mt-4 grid min-w-0 gap-4 md:grid-cols-2 xl:grid-cols-3">
            @forelse($rows as $row)
                <article wire:key="market-{{ $row['code'] }}" class="min-w-0 rounded-xl border border-gray-200 p-4 dark:border-gray-700">
                    <h3 class="break-words font-semibold">{{ $row['name'] }} · {{ $row['code'] }}</h3>
                    <p class="mt-1 break-words text-sm">{{ $row['currency'] ?: 'Para birimi eksik' }} · {{ implode(', ', $row['languages']) ?: 'Dil verisi eksik' }}</p>
                    <p class="my-3 break-words font-medium">{{ $row['label'] }} · {{ $row['score'] }}/100</p>
                    <dl class="grid grid-cols-2 gap-2 text-sm">
                        @foreach(['listings' => 'İlan', 'jobs' => 'İş', 'users' => 'Kullanıcı', 'accounts' => 'Doğrulanmış hesap'] as $key => $label)
                            <div class="min-w-0"><dt class="break-words text-gray-500 dark:text-gray-400">{{ $label }}</dt><dd>{{ $row[$key] }}</dd></div>
                        @endforeach
                    </dl>
                    <p class="mt-3 break-words text-sm text-gray-600 dark:text-gray-300">Öneri: {{ $row['next_action'] }}</p>
                </article>
            @empty
                <p role="status" class="text-sm">Bu görünümde eşleşen aktif ülke yok. Bölge üyeliği ve ülke kataloğunu kontrol edin.</p>
            @endforelse
        </div>
        <div class="mt-4 min-w-0">{{ $rows->links() }}</div>
    </x-filament::section>
</x-filament-widgets::widget>
