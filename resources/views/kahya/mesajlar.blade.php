{{--
    Kâhya sohbetinin mesaj akışı — sayfa (kahya-sohbet) ve balon (kahya-balonu)
    aynı listeyi kullanır. wire:click'ler dahil eden Livewire bileşenine bağlanır;
    davranışın tek kopyası için bkz. App\Livewire\Concerns\KahyaSohbetiYurutur.

    Bekler: $mesajlar (Collection<KahyaMesaji>)
--}}
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
