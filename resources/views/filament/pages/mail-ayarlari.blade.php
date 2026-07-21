<x-filament-panels::page>
    <form wire:submit="save" class="space-y-6">
        {{ $this->form }}

        <div class="flex flex-wrap items-center justify-end gap-3 border-t border-gray-200 pt-4 dark:border-gray-700">
            <x-filament::button type="button" color="gray" wire:click="testEt" wire:target="testEt" wire:loading.attr="disabled">
                <span wire:loading.remove wire:target="testEt">Test e-postası gönder</span>
                <span wire:loading wire:target="testEt">Gönderiliyor…</span>
            </x-filament::button>
            <x-filament::button type="submit" size="lg">
                Kaydet
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
