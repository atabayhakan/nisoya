<x-filament-panels::page>
    {{-- Yerel değişken KULLANILMIYOR: `@php` bloğunda tanımlanan değişken
         Filament'in bileşen yuvalarında kapsam dışında kalıyor ("Undefined
         variable" — ölçüldü). Metot doğrudan çağrılıyor; altındaki koleksiyon
         üretimde önbellekli, maliyeti yok. --}}
    <div class="grid gap-6 lg:grid-cols-4">
        {{-- Kenar çubuğu: arama + sayfa listesi + hızlı erişim.
             min-w-0 ŞART: grid öğesinin varsayılan min-width:auto kilidi
             olmadan uzun başlıklar sütunu şişirip yatay taşma yapar. --}}
        <aside class="min-w-0 lg:col-span-1">
            <div class="space-y-4 lg:sticky lg:top-24">
                <x-filament::input.wrapper prefix-icon="heroicon-o-magnifying-glass">
                    <x-filament::input
                        type="search"
                        wire:model.live.debounce.300ms="arama"
                        placeholder="Rehberde ara…"
                    />
                </x-filament::input.wrapper>

                <nav class="space-y-1" aria-label="Rehber sayfaları">
                    @forelse ($this->sayfalar() as $sayfa)
                        <button
                            type="button"
                            wire:click="ac('{{ $sayfa->slug }}')"
                            @class([
                                'block w-full rounded-lg px-3 py-2 text-left text-sm transition',
                                'bg-primary-50 font-semibold text-primary-700 dark:bg-primary-500/10 dark:text-primary-400' => $this->acikSayfa()?->slug === $sayfa->slug,
                                'text-gray-600 hover:bg-gray-50 dark:text-gray-300 dark:hover:bg-white/5' => $this->acikSayfa()?->slug !== $sayfa->slug,
                            ])
                        >
                            {{ $sayfa->baslik }}
                        </button>
                    @empty
                        <p class="px-3 py-2 text-sm text-gray-500 dark:text-gray-400">
                            "{{ $this->arama }}" için sonuç yok.
                        </p>
                    @endforelse
                </nav>

                {{-- Hızlı erişim: metin değil CANLI URL'ler, bayatlayamazlar. --}}
                <x-filament::section collapsible collapsed>
                    <x-slot name="heading">Hızlı erişim</x-slot>

                    <div class="space-y-1">
                        @foreach ($this->hizliErisim() as $kart)
                            <a href="{{ $kart['url'] }}"
                               class="flex items-center gap-2 rounded-lg px-2 py-1.5 text-sm text-gray-600 transition hover:bg-gray-50 hover:text-primary-600 dark:text-gray-300 dark:hover:bg-white/5 dark:hover:text-primary-400">
                                <x-filament::icon :icon="$kart['ikon']" class="h-4 w-4 shrink-0" />
                                {{ $kart['baslik'] }}
                            </a>
                        @endforeach
                    </div>
                </x-filament::section>
            </div>
        </aside>

        {{-- İçerik --}}
        <div class="min-w-0 lg:col-span-3">
            @if ($this->acikSayfa() === null)
                <x-filament::section>
                    <p class="text-sm text-gray-500 dark:text-gray-400">
                        Rehber sayfası bulunamadı. Sayfalar <code>docs/rehber/</code> altında markdown olarak durur.
                    </p>
                </x-filament::section>
            @else
                <x-filament::section>
                    <x-slot name="heading">{{ $this->acikSayfa()->baslik }}</x-slot>
                    @if ($this->acikSayfa()->ozet)
                        <x-slot name="description">{{ $this->acikSayfa()->ozet }}</x-slot>
                    @endif

                    <div class="prose prose-sm dark:prose-invert max-w-none">
                        {!! $this->acikSayfa()->html() !!}
                    </div>

                    @if ($this->acikSayfaUrl())
                        <div class="mt-6 border-t border-gray-200 pt-4 dark:border-white/10">
                            <x-filament::button tag="a" :href="$this->acikSayfaUrl()" icon="heroicon-o-arrow-top-right-on-square">
                                İlgili ekranı aç
                            </x-filament::button>
                        </div>
                    @endif
                </x-filament::section>
            @endif
        </div>
    </div>
</x-filament-panels::page>
