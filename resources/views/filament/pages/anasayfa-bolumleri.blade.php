<x-filament-panels::page>
    {{-- Hızlı Yönetim ve İlgili CMS Modülleri Köprüsü --}}
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 p-3 bg-stone-50 border border-stone-200 rounded-2xl dark:bg-stone-900/60 dark:border-stone-800">
        <a href="{{ url('/yonetim/hero-yoneticisi') }}" class="flex items-center gap-2.5 p-2.5 rounded-xl bg-white border border-stone-200/80 shadow-xs hover:border-emerald-500 hover:shadow-sm transition dark:bg-stone-800 dark:border-stone-700">
            <span class="p-1.5 rounded-lg bg-amber-50 text-amber-600 dark:bg-amber-950/60 dark:text-amber-400">
                <x-heroicon-o-sparkles class="w-4 h-4" />
            </span>
            <div class="truncate">
                <div class="text-xs font-bold text-stone-800 dark:text-stone-200">Hero Yöneticisi</div>
                <div class="text-2xs text-stone-400">Üst manşet & CTA</div>
            </div>
        </a>
        <a href="{{ url('/yonetim/diaspora-reels') }}" class="flex items-center gap-2.5 p-2.5 rounded-xl bg-white border border-stone-200/80 shadow-xs hover:border-emerald-500 hover:shadow-sm transition dark:bg-stone-800 dark:border-stone-700">
            <span class="p-1.5 rounded-lg bg-rose-50 text-rose-600 dark:bg-rose-950/60 dark:text-rose-400">
                <x-heroicon-o-play-circle class="w-4 h-4" />
            </span>
            <div class="truncate">
                <div class="text-xs font-bold text-stone-800 dark:text-stone-200">Diaspora Reels</div>
                <div class="text-2xs text-stone-400">Topluluk videoları</div>
            </div>
        </a>
        <a href="{{ url('/yonetim/big-highlights') }}" class="flex items-center gap-2.5 p-2.5 rounded-xl bg-white border border-stone-200/80 shadow-xs hover:border-emerald-500 hover:shadow-sm transition dark:bg-stone-800 dark:border-stone-700">
            <span class="p-1.5 rounded-lg bg-emerald-50 text-emerald-600 dark:bg-emerald-950/60 dark:text-emerald-400">
                <x-heroicon-o-rectangle-group class="w-4 h-4" />
            </span>
            <div class="truncate">
                <div class="text-xs font-bold text-stone-800 dark:text-stone-200">Büyük Kartlar</div>
                <div class="text-2xs text-stone-400">Vurgu mesajları</div>
            </div>
        </a>
        <a href="{{ url('/yonetim/tasarim-ayarlari') }}" class="flex items-center gap-2.5 p-2.5 rounded-xl bg-white border border-stone-200/80 shadow-xs hover:border-emerald-500 hover:shadow-sm transition dark:bg-stone-800 dark:border-stone-700">
            <span class="p-1.5 rounded-lg bg-indigo-50 text-indigo-600 dark:bg-indigo-950/60 dark:text-indigo-400">
                <x-heroicon-o-paint-brush class="w-4 h-4" />
            </span>
            <div class="truncate">
                <div class="text-xs font-bold text-stone-800 dark:text-stone-200">Görünüm & Tema</div>
                <div class="text-2xs text-stone-400">Renkler & fontlar</div>
            </div>
        </a>
    </div>

    <form wire:submit="save" class="space-y-6">
        {{ $this->form }}

        <div class="flex items-center justify-end border-t border-gray-200 pt-4 dark:border-gray-700">
            <x-filament::button type="submit" size="lg">
                Kaydet
            </x-filament::button>
        </div>
    </form>

    {{-- ---------------------------------------------------------------
         BÖLÜM SIRASI (Faz 2 · G5)

         Sıra TEMA BAŞINA ayrıdır: iki temanın bugünkü sırası zaten farklı,
         ortak tek ayar hangisine yazılırsa diğerini bozardı.

         Yukarı/aşağı düğmeleri ASIL yoldur — klavye, ekran okuyucu ve
         dokunmatikte çalışan tek yol odur. Sürükleme yalnız fare için bir
         hızlandırıcıdır (Kanban panosuyla aynı ilke).
    --------------------------------------------------------------- --}}
    <x-filament::section>
        <x-slot name="heading">Bölüm sırası</x-slot>
        <x-slot name="description">
            Anasayfada bölümlerin hangi sırayla görüneceği. Kapalı bölümler listede kalır ama sayfada görünmez.
            Hero her zaman en üsttedir; Nisoya Nabzı ve reklam alanları sayfadaki yerlerini korur.
        </x-slot>

        <div class="mb-4 flex flex-wrap gap-2">
            @foreach (\App\Support\HomeSections::temalar() as $tema)
                <x-filament::button
                    wire:click="temaSec('{{ $tema }}')"
                    :color="$siraTema === $tema ? 'primary' : 'gray'"
                    size="sm"
                >
                    {{ $tema === 'vitrin' ? 'Vitrin' : 'Klasik' }}
                    @if ($tema === $aktifTema) · aktif @endif
                </x-filament::button>
            @endforeach
        </div>

        <ul
            x-data="{
                suruklenen: null,
                tut(e, i) {
                    if (!window.matchMedia('(pointer: fine)').matches) return;
                    if (e.target.closest('button')) return;
                    this.suruklenen = i;
                },
                uzerinde(i) {
                    if (this.suruklenen === null || this.suruklenen === i) return;
                    $wire.tasi(this.suruklenen, i);
                    this.suruklenen = i;
                },
                birak() { this.suruklenen = null; },
            }"
            @pointerup.window="birak()"
            @pointercancel.window="birak()"
            class="space-y-2"
        >
            @foreach ($sira as $i => $anahtar)
                <li
                    @pointerdown="tut($event, {{ $i }})"
                    @pointerenter="uzerinde({{ $i }})"
                    :class="suruklenen === {{ $i }} ? 'opacity-50' : ''"
                    class="flex items-center gap-3 rounded-lg border border-gray-200 bg-white px-3 py-2 dark:border-gray-700 dark:bg-gray-900"
                >
                    <span class="w-6 shrink-0 text-center text-sm font-semibold text-gray-400">{{ $i + 1 }}</span>

                    <span class="min-w-0 flex-1 truncate text-sm text-gray-800 dark:text-gray-100">
                        {{ \App\Support\HomeSections::SECTIONS[$anahtar] ?? $anahtar }}
                        @unless (\App\Support\HomeSections::visible($anahtar))
                            <span class="ml-1 rounded bg-gray-100 px-1.5 py-0.5 text-xs text-gray-500 dark:bg-gray-800 dark:text-gray-400">kapalı</span>
                        @endunless
                    </span>

                    <div class="flex shrink-0 gap-1">
                        <x-filament::icon-button
                            icon="heroicon-o-arrow-up"
                            wire:click="yukari({{ $i }})"
                            :disabled="$i === 0"
                            label="Yukarı taşı: {{ \App\Support\HomeSections::SECTIONS[$anahtar] ?? $anahtar }}"
                            size="sm"
                        />
                        <x-filament::icon-button
                            icon="heroicon-o-arrow-down"
                            wire:click="asagi({{ $i }})"
                            :disabled="$i === count($sira) - 1"
                            label="Aşağı taşı: {{ \App\Support\HomeSections::SECTIONS[$anahtar] ?? $anahtar }}"
                            size="sm"
                        />
                    </div>
                </li>
            @endforeach
        </ul>

        <div class="mt-4 flex flex-wrap items-center justify-between gap-2 border-t border-gray-200 pt-4 dark:border-gray-700">
            <x-filament::button wire:click="siraVarsayilana" color="gray" size="sm">
                Varsayılan sıraya dön
            </x-filament::button>
            <x-filament::button wire:click="siraKaydet" size="lg">
                Sırayı kaydet
            </x-filament::button>
        </div>
    </x-filament::section>
</x-filament-panels::page>
