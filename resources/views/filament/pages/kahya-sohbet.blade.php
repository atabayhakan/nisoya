@php
    $mesajlar = $this->getMesajlar();
@endphp

<x-filament-panels::page>
    <div class="mx-auto w-full max-w-3xl">
        {{-- Konuşma akışı --}}
        <div class="space-y-4" id="kahya-akis">
            {{-- Karşılama her açılışta en üstte: sahip nereden devam
                 edeceğini sormadan görsün. Kaydedilmez — bir mesaj değil,
                 o anki durumun özeti. --}}
            <div class="flex items-start gap-2.5">
                <x-kahya.avatar boyut="h-7 w-7 text-sm" class="mt-0.5" />
                <div class="min-w-0 rounded-2xl rounded-tl-md bg-gray-100 px-4 py-3 shadow-sm ring-1 ring-black/5 dark:bg-gray-800 dark:ring-white/5">
                    <p class="whitespace-pre-line text-sm leading-relaxed text-gray-800 dark:text-gray-100">{{ $this->getKarsilama() }}</p>
                </div>
            </div>

            @include('kahya.mesajlar', ['mesajlar' => $mesajlar])

            <div wire:loading wire:target="gonder" class="flex items-center gap-2.5">
                <x-kahya.avatar boyut="h-7 w-7 text-sm" />
                <div class="flex items-center gap-1 rounded-2xl rounded-tl-md bg-gray-100 px-4 py-3.5 shadow-sm ring-1 ring-black/5 dark:bg-gray-800 dark:ring-white/5">
                    <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-gray-400 [animation-delay:-0.3s] dark:bg-gray-500"></span>
                    <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-gray-400 [animation-delay:-0.15s] dark:bg-gray-500"></span>
                    <span class="h-1.5 w-1.5 animate-bounce rounded-full bg-gray-400 dark:bg-gray-500"></span>
                </div>
            </div>
        </div>

        {{-- Yazma alanı --}}
        <form wire:submit="gonder" class="mt-6 flex flex-col gap-2">
            @include('kahya.ek-onizleme')

            <div class="flex items-end gap-2">
            <label class="cursor-pointer rounded-lg p-2 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-white/5 dark:hover:text-gray-300" title="Dosya/resim ekle">
                <input type="file" wire:model="ekDosya" class="hidden" />
                <x-filament::icon icon="heroicon-m-paper-clip" class="h-5 w-5" />
            </label>
            <div class="min-w-0 flex-1">
                <textarea
                    wire:model="mesaj"
                    rows="2"
                    placeholder="Örn: ülkeler kısmına Japonya ekle · duyuru bandına şunu yaz · kaç ilan var?"
                    class="block max-h-60 w-full resize-none overflow-y-auto rounded-xl border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                    x-data
                    x-init="$el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px'"
                    {{-- Yazarken yükseklik içeriğe göre büyür — sabit tek satır
                         yüzünden yazının üst kısmı görünmez kalıyordu. --}}
                    x-on:input="$el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px'"
                    x-on:kahya-kaydir.window="$nextTick(() => { $el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px' })"
                    {{-- Enter gönderir, Shift+Enter satır atlar — sohbet kutusu beklentisi. --}}
                    x-on:keydown.enter.prevent="if (!$event.shiftKey) { $wire.gonder(); } else { $el.value += '\n'; $wire.set('mesaj', $el.value, false); $el.style.height = 'auto'; $el.style.height = $el.scrollHeight + 'px'; }"
                    wire:loading.attr="disabled"
                    wire:target="gonder"
                ></textarea>
            </div>

            <x-filament::button type="submit" icon="heroicon-m-paper-airplane"
                wire:loading.attr="disabled" wire:target="gonder">
                Gönder
            </x-filament::button>
            </div>
        </form>

        <p class="mt-3 text-xs text-gray-400 dark:text-gray-500">
            Kâhya yalnızca tanımlı işleri yapabilir; yüksek riskli işler önce onayına sunulur ve
            her uygulanan iş geri alınabilir. Konuşmalar saklanır — bir dahaki gelişinde kaldığın
            yeri hatırlatır.
        </p>
    </div>
</x-filament-panels::page>
