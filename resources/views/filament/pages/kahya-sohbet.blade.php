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
            <div class="flex gap-3">
                <span class="mt-1 shrink-0 text-lg">🤵</span>
                <div class="min-w-0 rounded-xl rounded-tl-none bg-gray-100 px-4 py-3 dark:bg-gray-800">
                    <p class="whitespace-pre-line text-sm leading-relaxed text-gray-800 dark:text-gray-100">{{ $this->getKarsilama() }}</p>
                </div>
            </div>

            @foreach ($mesajlar as $m)
                @if ($m->rol === \App\Models\KahyaMesaji::ROL_SAHIP)
                    <div class="flex justify-end gap-3">
                        <div class="min-w-0 max-w-[85%] rounded-xl rounded-tr-none bg-primary-600 px-4 py-3">
                            <p class="whitespace-pre-line text-sm leading-relaxed text-white">{{ $m->metin }}</p>
                        </div>
                    </div>
                @else
                    <div class="flex gap-3">
                        <span class="mt-1 shrink-0 text-lg">🤵</span>
                        <div class="min-w-0 max-w-[85%]">
                            <div class="rounded-xl rounded-tl-none bg-gray-100 px-4 py-3 dark:bg-gray-800">
                                <p class="whitespace-pre-line text-sm leading-relaxed text-gray-800 dark:text-gray-100">{{ $m->metin }}</p>
                            </div>

                            {{-- Eylem düğmeleri TIKLANAN mesajın eylemine bağlı —
                                 sayfadaki "son eyleme" değil. Arka arkaya iki iş
                                 istendiğinde yanlışını onaylamak mümkün olmamalı. --}}
                            @if ($m->eylem !== null)
                                <div class="mt-2 flex flex-wrap items-center gap-2">
                                    @if ($m->eylem->durum === \App\Models\KahyaEylemKaydi::DURUM_BEKLEMEDE)
                                        <x-filament::button size="sm" color="success"
                                            wire:click="eylemOnayla({{ $m->eylem->id }})"
                                            wire:loading.attr="disabled">
                                            Onayla ve uygula
                                        </x-filament::button>
                                        <x-filament::button size="sm" color="gray" outlined
                                            wire:click="eylemReddet({{ $m->eylem->id }})"
                                            wire:loading.attr="disabled">
                                            Vazgeç
                                        </x-filament::button>
                                    @elseif ($m->eylem->geriAlinabilirMi())
                                        <x-filament::button size="sm" color="gray" outlined
                                            wire:click="eylemGeriAl({{ $m->eylem->id }})"
                                            wire:loading.attr="disabled"
                                            wire:confirm="Bu eylem geri alınacak: {{ $m->eylem->onizleme }}">
                                            Geri al
                                        </x-filament::button>
                                    @endif

                                    <span class="text-xs text-gray-400 dark:text-gray-500">
                                        {{ $m->eylem->durumEtiketi() }} · {{ $m->eylem->risk->etiket() }}
                                    </span>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            @endforeach

            <div wire:loading wire:target="gonder" class="flex gap-3">
                <span class="mt-1 shrink-0 text-lg">🤵</span>
                <div class="rounded-xl rounded-tl-none bg-gray-100 px-4 py-3 dark:bg-gray-800">
                    <p class="text-sm text-gray-500 dark:text-gray-400">Düşünüyorum…</p>
                </div>
            </div>
        </div>

        {{-- Yazma alanı --}}
        <form wire:submit="gonder" class="mt-6 flex items-end gap-2">
            <div class="min-w-0 flex-1">
                <textarea
                    wire:model="mesaj"
                    rows="2"
                    placeholder="Örn: ülkeler kısmına Japonya ekle · duyuru bandına şunu yaz · kaç ilan var?"
                    class="block w-full resize-none rounded-xl border-gray-300 text-sm shadow-sm focus:border-primary-500 focus:ring-primary-500 dark:border-gray-600 dark:bg-gray-900 dark:text-gray-100"
                    {{-- Enter gönderir, Shift+Enter satır atlar — sohbet kutusu beklentisi. --}}
                    x-on:keydown.enter.prevent="if (!$event.shiftKey) { $wire.gonder(); } else { $el.value += '\n'; $wire.set('mesaj', $el.value, false); }"
                    wire:loading.attr="disabled"
                    wire:target="gonder"
                ></textarea>
            </div>

            <x-filament::button type="submit" icon="heroicon-m-paper-airplane"
                wire:loading.attr="disabled" wire:target="gonder">
                Gönder
            </x-filament::button>
        </form>

        <p class="mt-3 text-xs text-gray-400 dark:text-gray-500">
            Kâhya yalnızca tanımlı işleri yapabilir; yüksek riskli işler önce onayına sunulur ve
            her uygulanan iş geri alınabilir. Konuşmalar saklanır — bir dahaki gelişinde kaldığın
            yeri hatırlatır.
        </p>
    </div>
</x-filament-panels::page>
