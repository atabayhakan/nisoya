{{--
    Kâhya balonu — sağ altta yüzen sohbet. Görünümün iki hâli var:
    kapalıyken tek düğme (ucuz), açıkken mini sohbet penceresi.

    z-30: Filament'in modal/bildirim katmanlarının (z-40+) ALTINDA kalır —
    balon hiçbir onay penceresinin üstünü örtmemeli.
--}}
<div>
    @if (! $acik)
        <button
            type="button"
            wire:click="ac"
            title="Kâhya ile konuş"
            class="fixed bottom-5 right-5 z-30 flex h-14 w-14 items-center justify-center rounded-full bg-primary-600 text-2xl shadow-lg ring-1 ring-black/10 transition hover:scale-105 hover:bg-primary-500 focus:outline-none focus:ring-2 focus:ring-primary-400"
        >
            <span aria-hidden="true">🤵</span>
            <span class="sr-only">Kâhya ile konuş</span>
        </button>
    @else
        <div class="fixed bottom-5 right-5 z-30 flex max-h-[75vh] w-[24rem] max-w-[calc(100vw-2.5rem)] flex-col overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-gray-950/10 dark:bg-gray-900 dark:ring-white/10">
            {{-- Başlık çubuğu --}}
            <div class="flex items-center gap-2 border-b border-gray-200 px-4 py-3 dark:border-white/10">
                <span class="text-lg">🤵</span>
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">Kâhya</p>
                    <p class="truncate text-xs text-gray-400 dark:text-gray-500">Sor ya da iş iste — her şey geri alınabilir</p>
                </div>
                <a
                    href="{{ \App\Filament\Pages\KahyaSohbet::getUrl() }}"
                    title="Tam ekran aç"
                    class="rounded-lg p-1.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-white/5 dark:hover:text-gray-300"
                >
                    <x-filament::icon icon="heroicon-m-arrows-pointing-out" class="h-4 w-4" />
                </a>
                <button
                    type="button"
                    wire:click="kapat"
                    title="Kapat"
                    class="rounded-lg p-1.5 text-gray-400 transition hover:bg-gray-100 hover:text-gray-600 dark:hover:bg-white/5 dark:hover:text-gray-300"
                >
                    <x-filament::icon icon="heroicon-m-x-mark" class="h-4 w-4" />
                </button>
            </div>

            {{-- Mesaj akışı --}}
            <div
                class="flex-1 space-y-4 overflow-y-auto px-4 py-4"
                x-data
                x-init="$el.scrollTop = $el.scrollHeight"
                x-on:kahya-kaydir.window="$nextTick(() => $el.scrollTop = $el.scrollHeight)"
            >
                {{-- Karşılama her açılışta en üstte: sahip nereden devam
                     edeceğini sormadan görsün. Kaydedilmez — mesaj değil,
                     o anki durumun özeti. --}}
                <div class="flex gap-3">
                    <span class="mt-1 shrink-0 text-lg">🤵</span>
                    <div class="min-w-0 rounded-xl rounded-tl-none bg-gray-100 px-4 py-3 dark:bg-gray-800">
                        <p class="whitespace-pre-line text-sm leading-relaxed text-gray-800 dark:text-gray-100">{{ $this->getKarsilama() }}</p>
                    </div>
                </div>

                @include('kahya.mesajlar', ['mesajlar' => $this->getMesajlar()])

                <div wire:loading wire:target="gonder" class="flex gap-3">
                    <span class="mt-1 shrink-0 text-lg">🤵</span>
                    <div class="rounded-xl rounded-tl-none bg-gray-100 px-4 py-3 dark:bg-gray-800">
                        <p class="text-sm text-gray-500 dark:text-gray-400">Düşünüyorum…</p>
                    </div>
                </div>
            </div>

            {{-- Yazma alanı --}}
            <form wire:submit="gonder" class="flex items-end gap-2 border-t border-gray-200 px-3 py-3 dark:border-white/10">
                <div class="min-w-0 flex-1">
                    <textarea
                        wire:model="mesaj"
                        rows="1"
                        placeholder="Örn: etiketler nerede? · SEO'yu doldur"
                        class="block w-full resize-none rounded-xl border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                        {{-- Enter gönderir, Shift+Enter satır atlar — sohbet kutusu beklentisi. --}}
                        x-on:keydown.enter.prevent="if (!$event.shiftKey) { $wire.gonder(); } else { $el.value += '\n'; $wire.set('mesaj', $el.value, false); }"
                        wire:loading.attr="disabled"
                        wire:target="gonder"
                    ></textarea>
                </div>

                <x-filament::icon-button type="submit" icon="heroicon-m-paper-airplane"
                    label="Gönder" size="lg" wire:loading.attr="disabled" wire:target="gonder" />
            </form>
        </div>
    @endif
</div>
