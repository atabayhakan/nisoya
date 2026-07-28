<x-filament-panels::page>
    @unless ($this->vitrinAktifMi())
        <div class="rounded-xl border border-amber-300 bg-amber-50 p-4 text-sm dark:border-amber-800 dark:bg-amber-950/30">
            <p class="font-semibold text-amber-800 dark:text-amber-300">Bu sayfa Vitrin temasını yönetir.</p>
            <p class="mt-1 text-amber-700 dark:text-amber-400">
                Şu an <strong>Klasik</strong> tema aktif — buradaki ayarlar kaydedilir ama sitede görünmez.
                Vitrin'i açmak için <a href="{{ \App\Filament\Pages\TasarimAyarlari::getUrl() }}" class="font-semibold underline">Tasarım Modu</a> sayfasındaki
                Klasik/Vitrin seçicisini kullan.
            </p>
        </div>
    @endunless

    <form wire:submit="save" class="space-y-6">
        {{-- Düzen seçici: görsel kartlar, state şemanın içinde tutulur --}}
        <div>
            <h3 class="text-base font-bold text-gray-900 dark:text-white">Hero düzeni</h3>
            <p class="text-xs text-gray-500 dark:text-gray-400">Düzen değişir, içerik aynı kalır.</p>

            <div class="mt-4 grid gap-4 sm:grid-cols-2">
                @php
                    $seciliDuzen = $this->data['duzen'] ?? 'bento';
                    $duzenler = [
                        'bento' => ['Bento Vitrin', 'Sol tarafta metin + arama, sağda canlı veri kartları. Varsayılan.'],
                        'sahne' => ['Sahne', 'Tam genişlik görsel/video üzerinde ortalanmış metin ve arama.'],
                    ];
                @endphp
                @foreach ($duzenler as $anahtar => [$ad, $aciklama])
                    <button
                        type="button"
                        wire:click="secDuzen('{{ $anahtar }}')"
                        @class([
                            'relative rounded-2xl border bg-white p-5 text-left transition shadow-sm dark:bg-gray-800',
                            'border-primary-500 ring-2 ring-primary-500/30' => $seciliDuzen === $anahtar,
                            'border-gray-200 hover:border-gray-300 dark:border-gray-700' => $seciliDuzen !== $anahtar,
                        ])
                    >
                        @if ($seciliDuzen === $anahtar)
                            <span class="absolute right-3 top-3 rounded-full bg-primary-100 px-2 py-0.5 text-2xs font-bold text-primary-700 dark:bg-primary-950 dark:text-primary-300">✓ Seçili</span>
                        @endif

                        {{-- Mini wireframe --}}
                        <div class="flex h-16 gap-1.5 rounded-lg bg-gray-100 p-2 dark:bg-gray-900/60">
                            @if ($anahtar === 'bento')
                                <div class="flex flex-1 flex-col justify-center gap-1">
                                    <span class="h-1.5 w-3/4 rounded bg-gray-400 dark:bg-gray-600"></span>
                                    <span class="h-1.5 w-1/2 rounded bg-primary-500"></span>
                                    <span class="mt-1 h-2 w-full rounded bg-white shadow-sm dark:bg-gray-700"></span>
                                </div>
                                <div class="grid flex-1 grid-cols-2 gap-1">
                                    <span class="col-span-2 rounded bg-white shadow-sm dark:bg-gray-700"></span>
                                    <span class="rounded bg-white shadow-sm dark:bg-gray-700"></span>
                                    <span class="rounded bg-white shadow-sm dark:bg-gray-700"></span>
                                </div>
                            @else
                                <div class="relative flex-1 rounded bg-gray-300 dark:bg-gray-700">
                                    <div class="absolute inset-0 flex flex-col items-center justify-center gap-1">
                                        <span class="h-1.5 w-1/2 rounded bg-white/90"></span>
                                        <span class="h-1.5 w-1/3 rounded bg-primary-400"></span>
                                        <span class="mt-1 h-2 w-2/3 rounded bg-white/90"></span>
                                    </div>
                                </div>
                            @endif
                        </div>

                        <div class="mt-3 text-sm font-bold text-gray-900 dark:text-white">{{ $ad }}</div>
                        <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">{{ $aciklama }}</p>
                    </button>
                @endforeach
            </div>
        </div>

        {{ $this->form }}

        <div class="flex flex-wrap items-center justify-end gap-3 border-t border-gray-200 pt-4 dark:border-gray-700">
            <a href="{{ url('/?tema_onizleme=vitrin') }}" target="_blank" rel="noopener"
               class="text-sm font-semibold text-gray-600 underline hover:text-gray-900 dark:text-gray-300 dark:hover:text-white">
                Taslağı yeni sekmede önizle →
            </a>
            <x-filament::button type="submit" size="lg">
                Kaydet ve yayınla
            </x-filament::button>
        </div>
    </form>
</x-filament-panels::page>
