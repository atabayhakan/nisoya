<x-filament-panels::page>
    @php
        $state = $this->data ?? [];
        $aktif = !empty($state['aktif']);
        $metin = trim((string)($state['metin'] ?? ''));
        $link = trim((string)($state['link'] ?? ''));
        $linkMetni = trim((string)($state['link_metni'] ?? ''));
        $renk = $state['renk'] ?? 'marka';
        $renkSinifi = match ($renk) {
            'uyari' => 'bg-amber-500 text-stone-950',
            'onemli' => 'bg-rose-600 text-white',
            default => 'bg-emerald-700 text-white',
        };
    @endphp

    <div class="rounded-xl border border-gray-200 bg-white p-4 shadow-sm dark:border-gray-800 dark:bg-gray-900">
        <div class="flex items-center justify-between pb-3 text-xs font-semibold text-gray-500 dark:text-gray-400">
            <span class="flex items-center gap-1.5">
                <x-heroicon-o-eye class="h-4 w-4 text-primary-500" />
                <span>Canlı Önizleme (Sitede Nasıl Görünecek?)</span>
            </span>
            <span>
                @if ($aktif && $metin !== '')
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2 py-0.5 text-xs font-medium text-emerald-700 dark:bg-emerald-950/40 dark:text-emerald-400">
                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-500"></span> Yayında Görünür
                    </span>
                @else
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-amber-50 px-2 py-0.5 text-xs font-medium text-amber-700 dark:bg-amber-950/40 dark:text-amber-400">
                        <span class="h-1.5 w-1.5 rounded-full bg-amber-500"></span> Gizli / Kapalı
                    </span>
                @endif
            </span>
        </div>

        @if ($metin !== '')
            <div class="{{ $renkSinifi }} rounded-lg px-4 py-2.5 text-center text-sm font-medium shadow-inner transition-colors">
                <span>{{ $metin }}</span>
                @if ($link !== '')
                    <a href="{{ $link }}" target="_blank" class="ml-2 font-bold underline underline-offset-2 hover:opacity-85">
                        {{ $linkMetni !== '' ? $linkMetni : 'Detay →' }}
                    </a>
                @endif
            </div>
        @else
            <div class="rounded-lg border border-dashed border-gray-200 py-3 text-center text-xs text-gray-400 dark:border-gray-800 dark:text-gray-500">
                Duyuru metni girildiğinde sitedeki görünüm burada canlı olarak belirecektir.
            </div>
        @endif
    </div>

    <form wire:submit="save" class="space-y-6">
        {{ $this->form }}

        <div class="flex items-center justify-end border-t border-gray-200 pt-4 dark:border-gray-700">
            <x-filament::button type="submit" size="lg">
                Kaydet
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
