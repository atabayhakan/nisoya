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

            @include('kahya.dusunuyor')
        </div>

        {{-- Yazma alanı — balonla aynı "composer" kapsülü (bkz. kahya-balonu). --}}
        <form wire:submit="gonder" class="mt-6 flex flex-col gap-2">
            @include('kahya.ek-onizleme')

            <div class="flex items-end gap-1 rounded-2xl bg-gray-100 p-2 ring-1 ring-transparent transition focus-within:bg-white focus-within:ring-primary-500 dark:bg-white/5 dark:focus-within:bg-gray-900 dark:focus-within:ring-primary-500">
                <label class="grid h-10 w-10 shrink-0 cursor-pointer place-items-center rounded-xl text-gray-400 transition hover:bg-gray-200/70 hover:text-gray-600 dark:hover:bg-white/10 dark:hover:text-gray-300" title="Dosya/resim ekle">
                    <input type="file" wire:model="ekDosya" class="hidden" />
                    <x-filament::icon icon="heroicon-m-paper-clip" class="h-5 w-5" />
                </label>

                <textarea
                    wire:model="mesaj"
                    rows="2"
                    placeholder="Kâhya'ya yaz — soru sor ya da iş ver…"
                    class="block max-h-60 min-w-0 flex-1 resize-none overflow-y-auto border-0 bg-transparent px-1 py-2 text-sm text-gray-900 placeholder:text-gray-400 focus:ring-0 dark:text-gray-100 dark:placeholder:text-gray-500"
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

                <button type="submit" title="Gönder"
                    class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-primary-600 text-white transition hover:bg-primary-500 disabled:opacity-50"
                    wire:loading.attr="disabled" wire:target="gonder">
                    <x-filament::icon icon="heroicon-m-paper-airplane" class="h-5 w-5" />
                    <span class="sr-only">Gönder</span>
                </button>
            </div>
        </form>

        <p class="mt-3 text-xs text-gray-400 dark:text-gray-500">
            Kâhya yalnızca tanımlı işleri yapabilir; yüksek riskli işler önce onayına sunulur ve
            her uygulanan iş geri alınabilir. Konuşmalar saklanır — bir dahaki gelişinde kaldığın
            yeri hatırlatır.
        </p>
    </div>
</x-filament-panels::page>
