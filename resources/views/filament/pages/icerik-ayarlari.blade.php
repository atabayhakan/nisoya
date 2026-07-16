<x-filament-panels::page>
    <form wire:submit="save" class="space-y-6 pb-20">
        {{ $this->form }}

        {{-- Yapışkan alt çubuk: birçok bölümü olan bu form için Kaydet
             butonu kaydırma boyunca ekranın altında sabit kalır — sayfanın
             sonuna kadar inip tekrar yukarı çıkmaya gerek kalmaz. Filament'in
             kendi sticky form-actions görsel diliyle (bg/blur/border) aynı. --}}
        <div class="sticky bottom-0 z-10 -mx-4 flex justify-end border-t border-gray-200 bg-white/95 px-4 py-3 backdrop-blur sm:-mx-6 sm:px-6 dark:border-gray-700 dark:bg-gray-900/95">
            <x-filament::button type="submit" size="lg">
                Kaydet
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
