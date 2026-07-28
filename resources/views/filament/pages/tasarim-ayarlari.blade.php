<x-filament-panels::page>
    {{-- Üst Bilgi Şeridi --}}
    <x-filament::section class="bg-gradient-to-r from-emerald-900 to-stone-900 text-white dark:from-emerald-950 dark:to-stone-950">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <div class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/20 px-3 py-1 text-xs font-semibold text-emerald-300 backdrop-blur">
                    ✨ 2027 UI/UX Vizyonu · Ultra Komuta Merkezi
                </div>
                <h2 class="mt-2 text-xl font-bold text-white">Nisoya Marka & Tasarım Mimarisi</h2>
                <p class="text-xs text-stone-300">
                    Sitenin tüm renk, tipografi, köşe yumuşatma ve cam efektlerini canlı olarak simüle edin ve tek tıkla yayınlayın.
                </p>
            </div>
            <div class="flex items-center gap-2">
                <x-filament::button color="gray" icon="heroicon-o-arrow-path" wire:click="sifirla">
                    Varsayılana Sıfırla
                </x-filament::button>
                <x-filament::button color="success" icon="heroicon-o-check" wire:click="kaydetCustom">
                    Değişiklikleri Kaydet
                </x-filament::button>
            </div>
        </div>
    </x-filament::section>

    {{-- 🚪 Site Teması: Klasik ↔ Vitrin (view ağacını değiştirir) --}}
    <div class="mt-6">
        <h3 class="text-base font-bold text-gray-900 dark:text-white">Site Teması</h3>
        <p class="text-xs text-gray-500 dark:text-gray-400">
            Sitenin tamamının hangi tasarımla sunulacağını seçer. Geçiş anında uygulanır, geri dönüş tek tıkla — klasik dosyalara dokunulmaz.
        </p>

        <div class="mt-4 grid gap-4 sm:grid-cols-2">
            {{-- Klasik --}}
            <div
                @class([
                    'relative flex flex-col justify-between overflow-hidden rounded-2xl border bg-white p-5 transition shadow-sm dark:bg-gray-800',
                    'border-emerald-500 ring-2 ring-emerald-500/30' => $aktifTema === 'klasik',
                    'border-gray-200 hover:border-gray-300 dark:border-gray-700' => $aktifTema !== 'klasik',
                ])
            >
                @if ($aktifTema === 'klasik')
                    <span class="absolute right-3 top-3 rounded-full bg-emerald-100 px-2 py-0.5 text-2xs font-bold text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">
                        ✓ Aktif
                    </span>
                @endif
                <div>
                    <div class="flex items-center gap-2">
                        <span class="h-4 w-4 rounded-full bg-emerald-600"></span>
                        <h4 class="font-bold text-sm text-gray-900 dark:text-white">Klasik</h4>
                    </div>
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                        Bugünkü tasarım. Aşağıdaki Tasarım Modu preset'leri ve tüm ince ayarlar bu temada geçerlidir.
                    </p>
                </div>
                <x-filament::button
                    size="sm"
                    color="gray"
                    class="mt-4 w-full"
                    :disabled="$aktifTema === 'klasik'"
                    wire:click="secTema('klasik')"
                >
                    Etkinleştir
                </x-filament::button>
            </div>

            {{-- Vitrin --}}
            <div
                @class([
                    'relative flex flex-col justify-between overflow-hidden rounded-2xl border bg-white p-5 transition shadow-sm dark:bg-gray-800',
                    'border-emerald-500 ring-2 ring-emerald-500/30' => $aktifTema === 'vitrin',
                    'border-gray-200 hover:border-gray-300 dark:border-gray-700' => $aktifTema !== 'vitrin',
                ])
            >
                @if ($aktifTema === 'vitrin')
                    <span class="absolute right-3 top-3 rounded-full bg-emerald-100 px-2 py-0.5 text-2xs font-bold text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">
                        ✓ Aktif
                    </span>
                @endif
                <div>
                    <div class="flex items-center gap-2">
                        <span class="h-4 w-4 rounded-full" style="background:#3E63F0"></span>
                        <h4 class="font-bold text-sm text-gray-900 dark:text-white">Vitrin</h4>
                        <span class="rounded-full bg-blue-100 px-2 py-0.5 text-2xs font-bold text-blue-700 dark:bg-blue-950 dark:text-blue-300">Hazırlanıyor</span>
                    </div>
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                        Yeni nesil tasarım: Deniz mavisi palet, Plus Jakarta Sans, bento hero. Karşılığı hazır olmayan sayfalar klasik görünümle sunulur.
                    </p>
                </div>
                <x-filament::button
                    size="sm"
                    color="gray"
                    class="mt-4 w-full"
                    :disabled="$aktifTema === 'vitrin'"
                    wire:click="secTema('vitrin')"
                >
                    Etkinleştir
                </x-filament::button>
            </div>
        </div>
    </div>

    {{-- 🎨 4 İmza Tema Preset'i --}}
    <div @class(['mt-6', 'opacity-50' => $aktifTema === 'vitrin'])>
        <h3 class="text-base font-bold text-gray-900 dark:text-white">İmza Tema Preset'leri</h3>
        <p class="text-xs text-gray-500 dark:text-gray-400">2027 trendlerine uygun hazırlanmış hazıl tasarım stilleri</p>
        @if ($aktifTema === 'vitrin')
            <p class="mt-1 text-xs font-semibold text-amber-600 dark:text-amber-400">
                ⚠ Bu preset'ler yalnız Klasik temada geçerlidir — Vitrin aktifken görünüme etki etmezler, seçiminiz Klasik'e dönünce uygulanır.
            </p>
        @endif

        <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            {{-- 1. Zümrüt Klasik --}}
            <div
                @class([
                    'relative flex flex-col justify-between overflow-hidden rounded-2xl border bg-white p-5 transition shadow-sm dark:bg-gray-800',
                    'border-emerald-500 ring-2 ring-emerald-500/30' => $aktifMod === 'eski',
                    'border-gray-200 hover:border-gray-300 dark:border-gray-700' => $aktifMod !== 'eski',
                ])
            >
                @if ($aktifMod === 'eski')
                    <span class="absolute right-3 top-3 rounded-full bg-emerald-100 px-2 py-0.5 text-2xs font-bold text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">
                        ✓ Aktif
                    </span>
                @endif
                <div>
                    <div class="flex items-center gap-2">
                        <span class="h-4 w-4 rounded-full bg-emerald-600"></span>
                        <h4 class="font-bold text-sm text-gray-900 dark:text-white">1. Zümrüt Klasik</h4>
                    </div>
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                        Zümrüt yeşili marka rengi, taş grisi zemin, Instrument Sans başlıklar — Orijinal klasik görünüm.
                    </p>

                    <div class="mt-4 rounded-xl bg-stone-50 p-3 dark:bg-stone-900/50">
                        <span class="font-sans text-sm font-bold text-gray-800 dark:text-gray-200">Ne İş Olursa Yaparım</span>
                    </div>
                </div>

                <x-filament::button
                    size="sm"
                    color="gray"
                    class="mt-4 w-full"
                    :disabled="$aktifMod === 'eski'"
                    wire:click="secPreset('eski')"
                >
                    Etkinleştir
                </x-filament::button>
            </div>

            {{-- 2. 2027 Vitrin & Neo-Craft --}}
            <div
                @class([
                    'relative flex flex-col justify-between overflow-hidden rounded-2xl border bg-white p-5 transition shadow-sm dark:bg-gray-800',
                    'border-emerald-500 ring-2 ring-emerald-500/30' => $aktifMod === 'yeni',
                    'border-gray-200 hover:border-gray-300 dark:border-gray-700' => $aktifMod !== 'yeni',
                ])
            >
                @if ($aktifMod === 'yeni')
                    <span class="absolute right-3 top-3 rounded-full bg-emerald-100 px-2 py-0.5 text-2xs font-bold text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">
                        ✓ Aktif
                    </span>
                @endif
                <div>
                    <div class="flex items-center gap-2">
                        <span class="h-4 w-4 rounded-full" style="background:#0f5c42"></span>
                        <h4 class="font-bold text-sm text-gray-900 dark:text-white">2. Neo-Craft 2027</h4>
                    </div>
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                        Vitrin Yeşili, sıcak Tezgah Kremi zemin ve Instrument Serif italik — Esnaf/vitrin ruhlu yeni stil.
                    </p>

                    <div class="mt-4 rounded-xl p-3" style="background:#f3eee4">
                        <span class="font-serif text-sm italic text-gray-900">Ne İş Olursa Yaparım</span>
                    </div>
                </div>

                <x-filament::button
                    size="sm"
                    color="success"
                    class="mt-4 w-full"
                    :disabled="$aktifMod === 'yeni'"
                    wire:click="secPreset('yeni')"
                >
                    Etkinleştir
                </x-filament::button>
            </div>

            {{-- 3. Midnight Obsidian --}}
            <div
                @class([
                    'relative flex flex-col justify-between overflow-hidden rounded-2xl border bg-white p-5 transition shadow-sm dark:bg-gray-800',
                    'border-emerald-500 ring-2 ring-emerald-500/30' => $aktifMod === 'obsidian',
                    'border-gray-200 hover:border-gray-300 dark:border-gray-700' => $aktifMod !== 'obsidian',
                ])
            >
                @if ($aktifMod === 'obsidian')
                    <span class="absolute right-3 top-3 rounded-full bg-emerald-100 px-2 py-0.5 text-2xs font-bold text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">
                        ✓ Aktif
                    </span>
                @endif
                <div>
                    <div class="flex items-center gap-2">
                        <span class="h-4 w-4 rounded-full bg-emerald-400"></span>
                        <h4 class="font-bold text-sm text-gray-900 dark:text-white">3. Midnight Obsidian</h4>
                    </div>
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                        Mat gece siyahı zemin, neon zümrüt ışımaları ve cam efektli ultra lüks lüks tema.
                    </p>

                    <div class="mt-4 rounded-xl bg-slate-950 p-3">
                        <span class="font-mono text-sm font-bold text-emerald-400">Ne İş Olursa Yaparım</span>
                    </div>
                </div>

                <x-filament::button
                    size="sm"
                    color="gray"
                    class="mt-4 w-full"
                    :disabled="$aktifMod === 'obsidian'"
                    wire:click="secPreset('obsidian')"
                >
                    Etkinleştir
                </x-filament::button>
            </div>

            {{-- 4. Nordik Minimal --}}
            <div
                @class([
                    'relative flex flex-col justify-between overflow-hidden rounded-2xl border bg-white p-5 transition shadow-sm dark:bg-gray-800',
                    'border-emerald-500 ring-2 ring-emerald-500/30' => $aktifMod === 'nordic',
                    'border-gray-200 hover:border-gray-300 dark:border-gray-700' => $aktifMod !== 'nordic',
                ])
            >
                @if ($aktifMod === 'nordic')
                    <span class="absolute right-3 top-3 rounded-full bg-emerald-100 px-2 py-0.5 text-2xs font-bold text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">
                        ✓ Aktif
                    </span>
                @endif
                <div>
                    <div class="flex items-center gap-2">
                        <span class="h-4 w-4 rounded-full bg-slate-900 dark:bg-slate-100"></span>
                        <h4 class="font-bold text-sm text-gray-900 dark:text-white">4. Nordik Minimal</h4>
                    </div>
                    <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">
                        Yüksek beyaz alanlar, 0.5px zarif hatlar ve İskandinav sadeliğinde tipografi.
                    </p>

                    <div class="mt-4 rounded-xl bg-slate-100 p-3 dark:bg-slate-800">
                        <span class="font-sans text-sm font-medium tracking-wide text-slate-800 dark:text-slate-200">Ne İş Olursa Yaparım</span>
                    </div>
                </div>

                <x-filament::button
                    size="sm"
                    color="gray"
                    class="mt-4 w-full"
                    :disabled="$aktifMod === 'nordic'"
                    wire:click="secPreset('nordic')"
                >
                    Etkinleştir
                </x-filament::button>
            </div>
        </div>
    </div>

    {{-- ÖLÜ KONTROL UYARISI (2026-07-28)

         Aşağıdaki ince ayarların tamamı yalnız KLASİK temada çalışır: klasik
         iskelet <x-brand-theme /> + <x-tasarim-theme /> basar, Vitrin iskeleti
         yalnız <x-vitrin-theme /> basar. Vitrin açıkken bu kontroller
         kaydediliyor ama siteye hiç yansımıyordu ve sayfada tek bir uyarı
         yoktu — sahip düğmeleri çeviriyor, hiçbir şey değişmiyordu.
         Hangi ayarın hangi temada yaşadığı App\Support\TemaJetonlari'nda
         beyan edilir ve TemaJetonlariTest bunu makineyle doğrular. --}}
    @if (\App\Support\Tema::vitrinMi())
        <div class="mt-8 rounded-xl border border-amber-300 bg-amber-50 p-4 dark:border-amber-700 dark:bg-amber-900/30">
            <p class="text-sm font-semibold text-amber-900 dark:text-amber-200">
                Şu an <strong>Vitrin</strong> teması açık — aşağıdaki ince ayarlar bu temada bir şey yapmaz.
            </p>
            <p class="mt-1 text-xs text-amber-800 dark:text-amber-300">
                Vitrin kendi yazı tipini, köşe ölçeğini ve gölgelerini taşır. Vitrin'in vurgu rengini
                sitenin üzerindeki <strong>Görünüm</strong> panelinden değiştirebilirsin; buradaki
                ayarlar Klasik temaya geçtiğinde geçerli olur.
            </p>
        </div>
    @endif

    {{-- 🎛️ İnce Ayar Kontrol Paneli & Canlı Simülatör --}}
    <div @class([
        'mt-8 grid gap-8 lg:grid-cols-3',
        'opacity-60' => \App\Support\Tema::vitrinMi(),
    ])>
        {{-- İnce Ayar Kontrolleri --}}
        <div class="space-y-6 lg:col-span-2">
            <x-filament::section>
                <x-slot name="heading">Marka Rengi Paleti (Primary Accent)</x-slot>
                <x-slot name="description">Ana butonlar, vurgular ve rozetlerde kullanılacak ana marka rengi</x-slot>

                <div class="flex flex-wrap items-center gap-3">
                    <input
                        type="color"
                        wire:model.live="primaryColor"
                        class="h-10 w-16 cursor-pointer rounded-lg border-0 bg-transparent p-0 shadow"
                    />
                    <input
                        type="text"
                        wire:model.live="primaryColor"
                        class="w-32 rounded-lg border-gray-300 text-sm focus:border-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                    />

                    {{-- Hazır Palet Butonları --}}
                    <div class="flex items-center gap-1.5 border-l border-gray-200 pl-3 dark:border-gray-700">
                        @foreach (['#059669', '#0f5c42', '#10b981', '#0f172a', '#2563eb', '#7c3aed', '#c1440e'] as $hex)
                            <button
                                type="button"
                                wire:click="$set('primaryColor', '{{ $hex }}')"
                                style="background: {{ $hex }}"
                                @class([
                                    'h-7 w-7 rounded-full transition transform hover:scale-110 shadow-sm',
                                    'ring-2 ring-offset-2 ring-primary-500 dark:ring-offset-gray-900' => $primaryColor === $hex,
                                ])
                            ></button>
                        @endforeach
                    </div>
                </div>
            </x-filament::section>

            <x-filament::section>
                <x-slot name="heading">Tipografi ve Şekil Mimarisi</x-slot>
                <x-slot name="description">Yazı tipleri, köşe yuvarlatma ve cam efektlerini kişiselleştirin</x-slot>

                <div class="grid gap-6 sm:grid-cols-2">
                    {{-- Tipografi --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300">Başlık Yazı Tipi</label>
                        <select
                            wire:model.live="fontFamily"
                            class="mt-1.5 w-full rounded-xl border-gray-300 text-xs focus:border-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                        >
                            {{-- Seçenekler tek kaynaktan (TemaJetonlari::FONTLAR) gelir:
                                 orada YALNIZ self-host edilen aileler bulunur. Buraya elle
                                 seçenek eklemek, sahibin seçip hiçbir şeyin değişmediği ölü
                                 bir kontrol üretir — 'Inter'/'Outfit' tam olarak öyleydi. --}}
                            @foreach (\App\Support\TemaJetonlari::fontSecenekleri() as $fontAnahtar => $fontEtiket)
                                <option value="{{ $fontAnahtar }}">{{ $fontEtiket }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Köşe Yuvarlatma --}}
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 dark:text-gray-300">Köşe Yuvarlatma (Border Radius)</label>
                        <select
                            wire:model.live="borderRadius"
                            class="mt-1.5 w-full rounded-xl border-gray-300 text-xs focus:border-primary-500 dark:border-gray-700 dark:bg-gray-900 dark:text-white"
                        >
                            <option value="sharp">Keskin (2px)</option>
                            <option value="soft">Yumuşak (8px)</option>
                            <option value="modern">Modern (14px)</option>
                            <option value="pill">Pill / Kapsül (24px)</option>
                        </select>
                    </div>
                </div>

                <div class="mt-6 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between border-t border-gray-100 pt-4 dark:border-gray-700/50">
                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" wire:model.live="glassmorphism" class="rounded text-primary-600 focus:ring-primary-500" />
                        <div>
                            <span class="text-xs font-semibold text-gray-800 dark:text-gray-200">Glassmorphism (Cam Efekti & Blur)</span>
                            <span class="block text-2xs text-gray-400">Kartlar ve menülerde şeffaf cam efekti uygular</span>
                        </div>
                    </label>

                    <label class="flex items-center gap-3 cursor-pointer">
                        <input type="checkbox" wire:model.live="smoothAnimations" class="rounded text-primary-600 focus:ring-primary-500" />
                        <div>
                            <span class="text-xs font-semibold text-gray-800 dark:text-gray-200">Akıcı Geçiş Animasyonları</span>
                            <span class="block text-2xs text-gray-400">Hover ve tıklamalarda yumuşak yay efekti</span>
                        </div>
                    </label>
                </div>
            </x-filament::section>
        </div>

        {{-- 👁️ CANLI SİMÜLATÖR (Real-time Live Simulator) --}}
        <div class="space-y-4">
            <h3 class="text-sm font-bold text-gray-900 dark:text-white flex items-center gap-2">
                <span>👁️ Canlı Simülatör</span>
                <span class="rounded-full bg-emerald-100 px-2 py-0.5 text-2xs text-emerald-700 dark:bg-emerald-950 dark:text-emerald-300">Anlık Önizleme</span>
            </h3>

            @php
                $simRadius = match($borderRadius) {
                    'sharp' => '2px',
                    'soft' => '8px',
                    'pill' => '24px',
                    default => '14px',
                };
                // Önizleme, sitenin gerçekte uygulayacağı CSS'in AYNISINI kullanır;
                // ayrı bir eşleme tutmak ikisinin sessizce ayrışmasına yol açardı.
                $simFont = \App\Support\TemaJetonlari::fontCss($fontFamily);
            @endphp

            <div
                style="border-radius: {{ $simRadius }};"
                class="overflow-hidden border border-gray-200 bg-stone-50 p-5 shadow-lg transition-all duration-300 dark:border-gray-700 dark:bg-stone-900"
            >
                {{-- Simülatör Rozeti --}}
                <span
                    style="background: {{ $primaryColor }}20; color: {{ $primaryColor }}; border-radius: {{ $simRadius }};"
                    class="inline-block px-2.5 py-1 text-2xs font-bold"
                >
                    ✨ 2027 Gurbetçi Vitrini
                </span>

                {{-- Simülatör Başlığı --}}
                <h4
                    style="font-family: {{ $simFont }};"
                    class="mt-3 text-2xl font-bold text-gray-900 dark:text-white"
                >
                    Ne İş Olursa Yaparım
                </h4>

                <p class="mt-1 text-xs text-gray-500 dark:text-gray-400">
                    Yurtdışındaki Türklerin yetenek ve hizmet pazaryeri.
                </p>

                {{-- Simülatör Butonu --}}
                <button
                    type="button"
                    style="background: {{ $primaryColor }}; border-radius: {{ $simRadius }};"
                    class="mt-4 w-full py-2.5 text-xs font-bold text-white shadow-md transition hover:opacity-90"
                >
                    İlan Ver veya Keşfet →
                </button>

                {{-- Simülatör Kart Örneği --}}
                <div
                    style="border-radius: {{ $simRadius }};"
                    class="mt-4 border border-gray-200 bg-white p-3 shadow-sm dark:border-gray-800 dark:bg-gray-800"
                >
                    <div class="flex items-center justify-between text-xs">
                        <span class="font-bold text-gray-800 dark:text-gray-200">Berlin İçi Nakliye</span>
                        <span style="color: {{ $primaryColor }};" class="font-bold">75 EUR</span>
                    </div>
                    <p class="mt-1 text-2xs text-gray-400">Almanya · Hizmet İlanı</p>
                </div>
            </div>
        </div>
    </div>
</x-filament-panels::page>
