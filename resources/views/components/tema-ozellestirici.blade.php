{{--
    CANLI TEMA ÖZELLEŞTİRİCİ (2026-07-28)

    Yalnız admin için, yalnız HALKA AÇIK sayfalarda basılır. Panel ve yönetim
    sayfalarında hiç render edilmez: bağış FAB'ı tam olarak orada mobilde form
    ve aksiyon butonlarının üzerine bindiği için kaldırılmıştı
    (layouts/app.blade.php'deki @unless notu). Özelleştirici zaten sitenin
    kendisini görmek içindir.

    ÖLÜ KONTROL YOK: hangi ayarın hangi temada yaşadığı App\Support\TemaJetonlari
    kayıt defterinden okunur ve o temada çalışmayan kontroller görünür biçimde
    kilitlenir (aria-disabled + gerekçe + çalışan alternatife yönlendirme).
    Denetimde bulunan hata tam olarak buydu: panelde çevrilen düğmeler Vitrin
    aktifken hiçbir şey yapmıyordu ve hiçbir uyarı yoktu.
--}}
@php
    $kullanici = auth()->user();
    $gorunur = $kullanici?->isAdmin()
        && ! request()->is('panel*', 'yonetim*');
@endphp

@if ($gorunur)
    @php
        $aktifTema = \App\Support\Tema::aktif();
        $aksanlar = config('vitrin_accents', []);
        $aileler = config('brand_colors', []);
        $ozelRenk = setting('gorunum.primary_color', '#059669');
        $baslangic = [
            'vitrin_aksan' => setting('gorunum.vitrin_aksan', 'deniz'),
            'marka_rengi' => setting('gorunum.marka_rengi', 'emerald'),
            'primary_color' => $ozelRenk,
            'renk_kaynagi' => strtolower((string) $ozelRenk) !== '#059669' ? 'ozel' : 'aile',
            'font_family' => in_array(setting('gorunum.font_family', 'sans'), \App\Http\Controllers\TemaOzellestiriciController::FONTLAR, true)
                ? setting('gorunum.font_family', 'sans')
                : 'sans',
            'border_radius' => setting('gorunum.border_radius', 'modern'),
            'glassmorphism' => setting('gorunum.glassmorphism', '1') === '1',
            'smooth_animations' => setting('gorunum.smooth_animations', '1') === '1',
        ];
    @endphp

    <div
        x-data="temaOzellestirici({
            aktifTema: @js($aktifTema),
            aksanlar: @js($aksanlar),
            aileler: @js(array_map(fn ($a) => $a['hex'], $aileler)),
            kayitUrl: @js(route('panel.gorunum.kaydet')),
            sifirlaUrl: @js(route('panel.gorunum.sifirla')),
            baslangic: @js($baslangic),
        })"
        x-cloak
    >
        <input type="hidden" x-ref="jeton" value="{{ csrf_token() }}">

        {{-- Başlatıcı: bağış FAB'ı sağ altta (bottom-20 right-4 z-40) olduğu için
             SOL alta konur — aynı katman, karşı köşe, çakışma yok. Mobil sekme
             çubuğunun (h-16 + güvenli alan) üstünde durur. --}}
        <button
            type="button"
            x-show="!acik"
            @click="acik = true; kucuk = false"
            class="fixed bottom-[calc(4.5rem+env(safe-area-inset-bottom))] left-4 z-40 flex items-center gap-2 rounded-full bg-stone-900 px-4 py-3 text-sm font-semibold text-white shadow-lg motion-safe:transition hover:bg-stone-800 md:bottom-6 md:left-6 dark:bg-stone-100 dark:text-stone-900 dark:hover:bg-white"
            aria-label="Tema özelleştiriciyi aç"
        >
            <span aria-hidden="true">🎨</span> Görünüm
        </button>

        {{-- Arka perde --}}
        <div x-show="acik && !kucuk" @click="acik = false" x-transition.opacity
             class="fixed inset-0 z-40 bg-stone-900/40" aria-hidden="true"></div>

        {{-- Küçültülmüş şerit: bir tasarım aracında sayfanın tamamını görebilmek şart. --}}
        <button type="button" x-show="acik && kucuk" @click="kucuk = false"
                class="fixed bottom-[calc(4.5rem+env(safe-area-inset-bottom))] left-4 z-50 rounded-full bg-emerald-600 px-4 py-3 text-sm font-semibold text-white shadow-lg md:bottom-6 md:left-6 dark:bg-emerald-500 dark:text-stone-900">
            🎨 Görünüm panelini aç <span x-show="kirli" class="ml-1">•</span>
        </button>

        <section
            x-show="acik && !kucuk"
            x-transition
            @keydown.escape.window="acik = false"
            role="dialog" aria-modal="true" aria-label="Tema özelleştirici"
            class="fixed inset-x-0 bottom-0 z-50 max-h-[80dvh] overflow-y-auto rounded-t-3xl bg-white p-5 pb-[calc(1.25rem+env(safe-area-inset-bottom))] shadow-2xl md:inset-y-0 md:right-0 md:left-auto md:w-80 md:max-h-none md:rounded-none dark:bg-stone-900"
        >
            <div class="flex items-start justify-between gap-2">
                <div>
                    <h2 class="text-base font-bold text-stone-900 dark:text-stone-50">Görünüm</h2>
                    <p class="text-xs text-stone-500 dark:text-stone-400">
                        Değişiklikler anında bu sayfada denenir; <strong>Kaydet</strong> demeden kimse görmez.
                    </p>
                </div>
                {{-- min-w/h-11: mobilde bunlar parmakla basılan gerçek kontroller;
                     masaüstünde fareyle kompakt kalırlar. --}}
                <div class="flex shrink-0 gap-1">
                    <button type="button" @click="kucuk = true" class="min-h-11 min-w-11 rounded-lg px-2 text-xs font-medium text-stone-500 hover:bg-stone-100 md:min-h-0 md:min-w-0 md:py-1 dark:hover:bg-stone-800" title="Küçült (sayfayı tam gör)">▁</button>
                    <button type="button" @click="acik = false" class="min-h-11 min-w-11 rounded-lg px-2 text-xs font-medium text-stone-500 hover:bg-stone-100 md:min-h-0 md:min-w-0 md:py-1 dark:hover:bg-stone-800" aria-label="Kapat">✕</button>
                </div>
            </div>

            <p class="mt-3 rounded-lg bg-stone-100 px-3 py-2 text-xs text-stone-600 dark:bg-stone-800 dark:text-stone-300">
                Aktif tema: <strong>{{ $aktifTema === 'vitrin' ? 'Vitrin' : 'Klasik' }}</strong>.
                Temayı değiştirmek için Yönetim → Tasarım Ayarları.
            </p>

            {{-- ---------------- Renk ---------------- --}}
            <div class="mt-4">
                <h3 class="text-sm font-semibold text-stone-800 dark:text-stone-100">Vurgu rengi</h3>

                @if ($aktifTema === 'vitrin')
                    <p class="mt-1 text-xs text-stone-500 dark:text-stone-400">Vitrin'in nötr renkleri, yazı tipi ve gölgeleri tasarımın parçasıdır; değişmez.</p>
                    <div class="mt-2 grid grid-cols-5 gap-2">
                        @foreach ($aksanlar as $ad => $aksan)
                            <button type="button"
                                    @click="vitrin_aksan = '{{ $ad }}'; degisti()"
                                    :class="vitrin_aksan === '{{ $ad }}' ? 'ring-2 ring-offset-2 ring-stone-900 dark:ring-stone-100 dark:ring-offset-stone-900' : ''"
                                    class="h-11 w-full rounded-lg border border-stone-200 md:h-10 dark:border-stone-700"
                                    style="background: {{ $aksan['hex'] }}"
                                    title="{{ $aksan['label'] }}"
                                    aria-label="{{ $aksan['label'] }}"></button>
                        @endforeach
                    </div>
                @else
                    <div class="mt-2 grid grid-cols-4 gap-2">
                        @foreach ($aileler as $ad => $aile)
                            <button type="button"
                                    @click="renk_kaynagi = 'aile'; marka_rengi = '{{ $ad }}'; degisti()"
                                    :class="renk_kaynagi === 'aile' && marka_rengi === '{{ $ad }}' ? 'ring-2 ring-offset-2 ring-stone-900 dark:ring-stone-100 dark:ring-offset-stone-900' : ''"
                                    class="h-11 w-full rounded-lg border border-stone-200 md:h-10 dark:border-stone-700"
                                    style="background: {{ $aile['hex'] }}"
                                    title="{{ $aile['label'] }}"
                                    aria-label="{{ $aile['label'] }}"></button>
                        @endforeach
                    </div>
                    <label class="mt-3 flex items-center gap-2 text-xs text-stone-600 dark:text-stone-300">
                        <input type="color" x-model="primary_color"
                               @input="renk_kaynagi = 'ozel'; degisti()"
                               class="h-9 w-12 rounded border border-stone-300 dark:border-stone-700">
                        Özel renk
                    </label>
                @endif
            </div>

            {{-- ---------------- Klasik'e özgü ince ayarlar ---------------- --}}
            <div class="mt-5" :class="klasikKilitli ? 'opacity-60' : ''">
                <h3 class="text-sm font-semibold text-stone-800 dark:text-stone-100">
                    İnce ayarlar
                    <template x-if="klasikKilitli">
                        <span class="ml-1 rounded-full bg-amber-100 px-2 py-0.5 text-[11px] font-medium text-amber-800 dark:bg-amber-900/40 dark:text-amber-200">Klasik temada çalışır</span>
                    </template>
                </h3>
                <template x-if="klasikKilitli">
                    <p class="mt-1 text-xs text-stone-500 dark:text-stone-400">
                        Vitrin kendi yazı tipini, köşe ölçeğini ve hareketini taşır — bu ayarlar burada bir şey yapmaz.
                    </p>
                </template>

                <label class="mt-3 block text-xs font-medium text-stone-600 dark:text-stone-300">Yazı tipi</label>
                <select x-model="font_family" @change="degisti()" :disabled="klasikKilitli" :aria-disabled="klasikKilitli"
                        class="mt-1 w-full rounded-lg border-stone-300 py-2 text-sm disabled:cursor-not-allowed dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                    <option value="sans">Instrument Sans (varsayılan)</option>
                    <option value="serif">Instrument Serif</option>
                </select>

                <label class="mt-3 block text-xs font-medium text-stone-600 dark:text-stone-300">Köşe yuvarlatma</label>
                <select x-model="border_radius" @change="degisti()" :disabled="klasikKilitli" :aria-disabled="klasikKilitli"
                        class="mt-1 w-full rounded-lg border-stone-300 py-2 text-sm disabled:cursor-not-allowed dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100">
                    <option value="sharp">Keskin</option>
                    <option value="soft">Yumuşak</option>
                    <option value="modern">Modern (varsayılan)</option>
                    <option value="pill">Kapsül</option>
                </select>

                <label class="mt-3 flex items-center gap-2 text-sm text-stone-700 dark:text-stone-200">
                    <input type="checkbox" x-model="glassmorphism" @change="degisti()" :disabled="klasikKilitli"
                           class="rounded border-stone-300 text-emerald-600 focus:ring-emerald-500 dark:border-stone-600">
                    Cam efekti
                </label>
                <label class="mt-2 flex items-center gap-2 text-sm text-stone-700 dark:text-stone-200">
                    <input type="checkbox" x-model="smooth_animations" @change="degisti()" :disabled="klasikKilitli"
                           class="rounded border-stone-300 text-emerald-600 focus:ring-emerald-500 dark:border-stone-600">
                    Akıcı animasyonlar
                </label>
            </div>

            <p x-show="mesaj" x-cloak role="status" aria-live="polite"
               :class="mesajTipi === 'hata' ? 'bg-red-50 text-red-700 dark:bg-red-900/30 dark:text-red-300' : 'bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300'"
               class="mt-4 rounded-lg px-3 py-2 text-xs" x-text="mesaj"></p>

            <div class="mt-4 flex flex-wrap gap-2 border-t border-stone-200 pt-4 dark:border-stone-800">
                <button type="button" @click="kaydet()" :disabled="calisiyor || !kirli"
                        class="min-h-11 flex-1 rounded-lg bg-emerald-600 px-4 text-sm font-semibold text-white hover:bg-emerald-700 disabled:opacity-50 md:min-h-0 md:py-2 dark:bg-emerald-500 dark:text-stone-900">
                    <span x-text="kirli ? 'Kaydet' : 'Kaydedildi'"></span>
                </button>
                <button type="button" @click="vazgec()" :disabled="calisiyor"
                        class="min-h-11 rounded-lg border border-stone-300 px-4 text-sm font-medium text-stone-700 hover:bg-stone-100 md:min-h-0 md:py-2 dark:border-stone-700 dark:text-stone-200 dark:hover:bg-stone-800">
                    Vazgeç
                </button>
                <button type="button" @click="if (confirm('Görünüm fabrika ayarlarına dönecek. Emin misin?')) sifirla()" :disabled="calisiyor"
                        class="min-h-11 rounded-lg px-3 text-xs font-medium text-stone-500 hover:bg-stone-100 md:min-h-0 md:py-2 dark:text-stone-400 dark:hover:bg-stone-800">
                    Varsayılana dön
                </button>
            </div>
        </section>
    </div>
@endif
