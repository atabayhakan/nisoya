{{--
    Kâhya sohbetinin mesaj akışı — sayfa (kahya-sohbet) ve balon (kahya-balonu)
    aynı listeyi kullanır. wire:click'ler dahil eden Livewire bileşenine bağlanır;
    davranışın tek kopyası için bkz. App\Livewire\Concerns\KahyaSohbetiYurutur.

    Bekler: $mesajlar (Collection<KahyaMesaji>)
--}}
@foreach ($mesajlar as $m)
    @if ($m->rol === \App\Models\KahyaMesaji::ROL_SAHIP)
        <div class="flex flex-col items-end gap-1">
            <div class="min-w-0 max-w-[85%] rounded-2xl rounded-tr-md bg-primary-600 px-4 py-3 shadow-sm">
                @if ($m->ekVarMi())
                    @if ($m->ekResimMi())
                        <a href="{{ $m->ekUrl() }}" target="_blank" rel="noopener" class="mb-2 block">
                            <img src="{{ $m->ekUrl() }}" alt="{{ $m->ek_ad }}" class="max-h-48 w-full rounded-lg object-cover" />
                        </a>
                    @else
                        <a href="{{ $m->ekUrl() }}" target="_blank" rel="noopener"
                            class="mb-2 flex items-center gap-2 rounded-lg bg-white/10 px-2.5 py-2 text-xs text-white hover:bg-white/20">
                            <x-filament::icon icon="heroicon-m-paper-clip" class="h-4 w-4 shrink-0" />
                            <span class="truncate">{{ $m->ek_ad }}</span>
                        </a>
                    @endif
                @endif

                @if (trim((string) $m->metin) !== '')
                    <p class="whitespace-pre-line text-sm leading-relaxed text-white">{{ $m->metin }}</p>
                @endif
            </div>
            <span class="pr-1 text-[11px] text-gray-400 dark:text-gray-500">{{ $m->created_at->format('H:i') }}</span>
        </div>
    @else
        <div class="flex items-start gap-2.5">
            <x-kahya.avatar boyut="h-7 w-7 text-sm" class="mt-0.5" />

            <div class="flex min-w-0 max-w-[85%] flex-col gap-1">
                <div class="min-w-0 rounded-2xl rounded-tl-md bg-gray-100 px-4 py-3 shadow-sm ring-1 ring-black/5 dark:bg-gray-800 dark:ring-white/5">
                    <p class="whitespace-pre-line text-sm leading-relaxed text-gray-800 dark:text-gray-100">{{ $m->metin }}</p>

                    {{-- Eylem düğmeleri TIKLANAN mesajın eylemine bağlı —
                         sayfadaki "son eyleme" değil. Arka arkaya iki iş
                         istendiğinde yanlışını onaylamak mümkün olmamalı. --}}
                    @if ($m->eylem !== null)
                        @php
                            $durumRenk = match ($m->eylem->durum) {
                                \App\Models\KahyaEylemKaydi::DURUM_UYGULANDI => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-500/10 dark:text-emerald-400',
                                \App\Models\KahyaEylemKaydi::DURUM_HATA => 'bg-rose-100 text-rose-700 dark:bg-rose-500/10 dark:text-rose-400',
                                \App\Models\KahyaEylemKaydi::DURUM_BEKLEMEDE => 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400',
                                default => 'bg-gray-200 text-gray-600 dark:bg-white/10 dark:text-gray-300',
                            };
                            $riskRenk = $m->eylem->risk->onayGerekir()
                                ? 'bg-amber-100 text-amber-700 dark:bg-amber-500/10 dark:text-amber-400'
                                : 'bg-gray-200 text-gray-600 dark:bg-white/10 dark:text-gray-300';
                        @endphp
                        <div class="mt-3 flex flex-wrap items-center gap-2 border-t border-gray-200 pt-3 dark:border-white/10">
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

                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium {{ $durumRenk }}">
                                {{ $m->eylem->durumEtiketi() }}
                            </span>
                            <span class="inline-flex items-center rounded-full px-2 py-0.5 text-[11px] font-medium {{ $riskRenk }}">
                                {{ $m->eylem->risk->etiket() }}
                            </span>
                        </div>
                    @endif
                </div>
                <span class="pl-1 text-[11px] text-gray-400 dark:text-gray-500">{{ $m->created_at->format('H:i') }}</span>
            </div>
        </div>
    @endif
@endforeach
