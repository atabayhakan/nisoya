{{--
    Gönderilmeyi bekleyen ek — hem balon hem tam sayfa aynı önizlemeyi
    kullanır (kahya.mesajlar deseniyle aynı). $ekDosya, $uploading tam sayfa
    da balonun her ikisinde de bileşenin kendi public state'i.
--}}
<div wire:loading wire:target="ekDosya" class="flex items-center gap-2 self-start rounded-lg bg-gray-100 px-2.5 py-1.5 text-xs text-gray-500 dark:bg-gray-800 dark:text-gray-400">
    <svg class="h-3.5 w-3.5 animate-spin" viewBox="0 0 24 24" fill="none">
        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
    </svg>
    Yükleniyor…
</div>

@if ($ekDosya)
    <div wire:loading.remove wire:target="ekDosya" class="flex items-center gap-2 self-start rounded-lg bg-gray-100 px-2.5 py-1.5 text-xs text-gray-600 dark:bg-gray-800 dark:text-gray-300">
        <x-filament::icon icon="heroicon-m-paper-clip" class="h-3.5 w-3.5 shrink-0" />
        <span class="max-w-[12rem] truncate">{{ $ekDosya->getClientOriginalName() }}</span>
        <button type="button" wire:click="ekKaldir" title="Kaldır" class="text-gray-400 transition hover:text-gray-600 dark:hover:text-gray-200">
            <x-filament::icon icon="heroicon-m-x-mark" class="h-3.5 w-3.5" />
        </button>
    </div>
@endif
