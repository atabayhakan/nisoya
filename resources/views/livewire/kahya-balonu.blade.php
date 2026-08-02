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
            {{-- "Aktif" noktası: Kâhya'nın çevrimiçi/hazır olduğunu, klasik
                 sohbet widget'larındaki gibi tek bakışta söyler. --}}
            <span class="absolute -bottom-0.5 -right-0.5 h-3.5 w-3.5 rounded-full bg-green-500 ring-2 ring-white dark:ring-gray-900"></span>
            <span class="sr-only">Kâhya ile konuş</span>
        </button>
    @else
        <div class="fixed bottom-5 right-5 z-30 flex max-h-[75vh] w-[24rem] max-w-[calc(100vw-2.5rem)] flex-col overflow-hidden rounded-2xl bg-white shadow-2xl ring-1 ring-gray-950/10 dark:bg-gray-900 dark:ring-white/10">
            {{-- Başlık çubuğu --}}
            <div class="flex items-center gap-3 border-b border-gray-200 bg-gray-50/80 px-4 py-3 dark:border-white/10 dark:bg-white/[0.03]">
                <x-kahya.avatar durum />
                <div class="min-w-0 flex-1">
                    <p class="text-sm font-semibold text-gray-900 dark:text-white">{{ config('kahya.isim', 'Kâhya') }}</p>
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
                <div class="flex items-start gap-2.5">
                    <x-kahya.avatar boyut="h-7 w-7 text-sm" class="mt-0.5" />
                    <div class="min-w-0 rounded-2xl rounded-tl-md bg-gray-100 px-4 py-3 shadow-sm ring-1 ring-black/5 dark:bg-gray-800 dark:ring-white/5">
                        <p class="whitespace-pre-line text-sm leading-relaxed text-gray-800 dark:text-gray-100">{{ $this->getKarsilama() }}</p>
                    </div>
                </div>

                @include('kahya.mesajlar', ['mesajlar' => $this->getMesajlar()])

                @include('kahya.dusunuyor')
            </div>

            {{-- Yazma alanı — tek kapsül "composer": ataç + yazı + gönder aynı
                 yumuşak kutuda; odaklanınca kapsül marka rengiyle çerçevelenir. --}}
            <form wire:submit="gonder" class="flex flex-col gap-2 border-t border-gray-200 px-3 py-3 dark:border-white/10">
                @include('kahya.ek-onizleme')

                <div class="flex items-end gap-1 rounded-2xl bg-gray-100 p-1.5 ring-1 ring-transparent transition focus-within:bg-white focus-within:ring-primary-500 dark:bg-white/5 dark:focus-within:bg-gray-900 dark:focus-within:ring-primary-500">
                    <label class="grid h-9 w-9 shrink-0 cursor-pointer place-items-center rounded-xl text-gray-400 transition hover:bg-gray-200/70 hover:text-gray-600 dark:hover:bg-white/10 dark:hover:text-gray-300" title="Dosya/resim ekle">
                        <input type="file" wire:model="ekDosya" class="hidden" />
                        <x-filament::icon icon="heroicon-m-paper-clip" class="h-5 w-5" />
                    </label>

                    <textarea
                        wire:model="mesaj"
                        rows="1"
                        placeholder="Kâhya'ya yaz…"
                        class="block max-h-40 min-w-0 flex-1 resize-none overflow-y-auto border-0 bg-transparent px-1 py-2 text-sm text-gray-900 placeholder:text-gray-400 focus:ring-0 dark:text-gray-100 dark:placeholder:text-gray-500"
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
                        class="grid h-9 w-9 shrink-0 place-items-center rounded-xl bg-primary-600 text-white transition hover:bg-primary-500 disabled:opacity-50"
                        wire:loading.attr="disabled" wire:target="gonder">
                        <x-filament::icon icon="heroicon-m-paper-airplane" class="h-4 w-4" />
                        <span class="sr-only">Gönder</span>
                    </button>
                </div>
            </form>
        </div>
    @endif
</div>
