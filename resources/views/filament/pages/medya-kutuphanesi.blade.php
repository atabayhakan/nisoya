<x-filament-panels::page>
    @php
        $usage = $this->getUsage();
        $files = $this->getFiles();
        $human = \App\Services\BackupService::humanSize(...);
    @endphp

    {{-- Uyarı --}}
    <x-filament::section>
        <div class="flex items-start gap-3 text-sm">
            <x-filament::icon icon="heroicon-o-exclamation-triangle" class="h-5 w-5 shrink-0 text-warning-500" />
            <span class="text-gray-700 dark:text-gray-300">
                Bir dosyayı silmeden önce emin ol: bir <strong>ilana, sayfaya veya profile ait</strong> bir görseli
                silersen orada görsel kırılır. Yalnızca artık kullanılmadığından emin olduğun dosyaları sil.
            </span>
        </div>
    </x-filament::section>

    {{-- Kullanım özeti --}}
    <div class="grid grid-cols-2 gap-4 lg:grid-cols-3">
        <x-filament::section class="text-center">
            <div class="text-2xl font-bold text-gray-950 dark:text-white">{{ number_format($usage['total']['count']) }}</div>
            <div class="text-sm text-gray-500 dark:text-gray-400">Toplam dosya</div>
        </x-filament::section>
        <x-filament::section class="text-center">
            <div class="text-2xl font-bold text-gray-950 dark:text-white">{{ $human($usage['total']['size']) }}</div>
            <div class="text-sm text-gray-500 dark:text-gray-400">Medya boyutu</div>
        </x-filament::section>
        <x-filament::section class="text-center">
            <div class="text-2xl font-bold text-gray-950 dark:text-white">{{ $human($this->freeSpace()) }}</div>
            <div class="text-sm text-gray-500 dark:text-gray-400">Boş disk</div>
        </x-filament::section>
    </div>

    {{-- Dizinler --}}
    <x-filament::section>
        <x-slot name="heading">Klasörler</x-slot>
        <x-slot name="description">En çok yer kaplayan klasör en üstte. Dosyaları görmek için birine tıkla.</x-slot>

        @if (count($usage['dirs']) === 0)
            <div class="py-6 text-center text-sm text-gray-500 dark:text-gray-400">Henüz yüklenmiş medya yok.</div>
        @else
            <div class="flex flex-wrap gap-2">
                @foreach ($usage['dirs'] as $name => $stat)
                    <button
                        type="button"
                        wire:click="selectDir(@js($name))"
                        @class([
                            'rounded-lg border px-3 py-2 text-left text-sm transition',
                            'border-primary-500 bg-primary-50 dark:bg-primary-500/10' => $dir === $name,
                            'border-gray-200 hover:border-gray-300 dark:border-gray-700 dark:hover:border-gray-600' => $dir !== $name,
                        ])
                    >
                        <span class="block font-medium text-gray-900 dark:text-gray-100">{{ $name }}</span>
                        <span class="text-xs text-gray-500 dark:text-gray-400">{{ number_format($stat['count']) }} dosya · {{ $human($stat['size']) }}</span>
                    </button>
                @endforeach
            </div>
        @endif
    </x-filament::section>

    {{-- Seçili klasörün dosyaları --}}
    @if ($dir !== '')
        <x-filament::section>
            <x-slot name="heading">{{ $dir }}</x-slot>
            <x-slot name="description">{{ number_format($files['total']) }} dosya</x-slot>

            @if (count($files['items']) === 0)
                <div class="py-6 text-center text-sm text-gray-500 dark:text-gray-400">Bu klasörde dosya yok.</div>
            @else
                <div class="grid grid-cols-2 gap-3 sm:grid-cols-3 lg:grid-cols-4">
                    @foreach ($files['items'] as $item)
                        <div class="overflow-hidden rounded-lg border border-gray-200 dark:border-gray-700">
                            <a href="{{ \App\Support\MediaLibrary::url($item['path']) }}" target="_blank" rel="noopener"
                               class="flex aspect-square items-center justify-center bg-gray-50 dark:bg-gray-800">
                                @if ($item['is_image'])
                                    <img src="{{ \App\Support\MediaLibrary::url($item['path']) }}" alt="" loading="lazy" class="h-full w-full object-cover">
                                @else
                                    <x-filament::icon icon="heroicon-o-document" class="h-10 w-10 text-gray-400" />
                                @endif
                            </a>
                            <div class="flex items-center justify-between gap-2 p-2">
                                <div class="min-w-0">
                                    <div class="truncate text-xs font-medium text-gray-700 dark:text-gray-300" title="{{ $item['path'] }}">{{ basename($item['path']) }}</div>
                                    <div class="text-xs text-gray-400">{{ $human($item['size']) }}</div>
                                </div>
                                <button
                                    type="button"
                                    wire:click="deleteFile(@js($item['path']))"
                                    wire:confirm="Bu dosyayı kalıcı olarak sil? Bir ilana/sayfaya aitse orada görsel kırılır."
                                    class="shrink-0 rounded p-1 text-danger-500 hover:bg-danger-50 dark:hover:bg-danger-500/10"
                                    aria-label="Sil"
                                >
                                    <x-filament::icon icon="heroicon-o-trash" class="h-4 w-4" />
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>

                @if ($files['pages'] > 1)
                    <div class="mt-4 flex items-center justify-between">
                        <x-filament::button size="sm" color="gray" wire:click="prevPage" :disabled="$files['page'] <= 1">
                            ← Önceki
                        </x-filament::button>
                        <span class="text-sm text-gray-500 dark:text-gray-400">Sayfa {{ $files['page'] }} / {{ $files['pages'] }}</span>
                        <x-filament::button size="sm" color="gray" wire:click="nextPage" :disabled="$files['page'] >= $files['pages']">
                            Sonraki →
                        </x-filament::button>
                    </div>
                @endif
            @endif
        </x-filament::section>
    @endif
</x-filament-panels::page>
