{{-- İKİ KAPI, TEK HEDEF.

     Bu ekran eskiden yalnız fotoğraf kapısıydı. Metin kapısı eklendi çünkü
     diaspora ticareti bugün WhatsApp gruplarında dönüyor: insanlar oradaki
     hazır ilan metnini yapıştırabilsin diye. İkisi de aynı yere gider —
     normal ilan formu, önceden doldurulmuş hâlde.

     Kapılar AYRI AYRI kapatılabilir (config/ai.php features). Biri kapalıysa
     diğeri görünmeye devam eder; ikisi de kapalıysa kontrolcü bu ekrana hiç
     gelmez, doğrudan normal forma yönlendirir. --}}
<x-layouts.app title="Hızlı İlan — Nisoya">
    <div class="mx-auto max-w-lg px-4 py-10">
        <x-panel.back-link :href="route('panel.listings.create', ['tip' => 'urun'])" label="Normal form" />
        <h1 class="mt-2 text-2xl font-bold text-stone-900 dark:text-stone-50">⚡ Hızlı İlan</h1>
        <p class="mt-2 text-sm text-stone-500 dark:text-stone-400">
            Fotoğraf çek ya da birkaç kelime yaz — başlık, kategori ve açıklamayı senin için hazırlayalım.
            Sen onaylamadan hiçbir şey yayınlanmaz.
        </p>

        @if ($metinAcik)
            {{-- METİN KAPISI ÖNCE: yazmak fotoğraf çekmekten hızlı ve
                 yapıştırma tek hamle. Kamera izni/ışık/çerçeveleme gerektiren
                 yol ikinci sırada. --}}
            <div class="mt-6 rounded-2xl border border-stone-200 bg-white p-5 dark:border-stone-800 dark:bg-stone-900"
                 x-data="{ gonderiliyor: false, metin: '' }">
                <h2 class="flex items-center gap-2 text-sm font-semibold text-stone-800 dark:text-stone-100">
                    <x-heroicon-o-pencil-square class="h-5 w-5 shrink-0 text-emerald-700 dark:text-emerald-400" />
                    Yaz ya da yapıştır
                </h2>

                <form method="POST" action="{{ route('panel.listings.analyze-text') }}" class="mt-3" @submit="gonderiliyor = true">
                    @csrf
                    <label for="metin" class="sr-only">İlan metni</label>
                    <textarea id="metin" name="metin" rows="4" x-model="metin" maxlength="4000"
                              placeholder="Örnek: iphone 15 pro max 256gb gri, az kullanılmış&#10;&#10;Ya da WhatsApp'taki ilan metnini buraya yapıştır."
                              class="w-full rounded-xl border-stone-300 bg-stone-50 text-sm text-stone-800 placeholder-stone-500 focus:border-emerald-500 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-100 dark:placeholder-stone-400">{{ old('metin') }}</textarea>
                    @error('metin') <p class="mt-1 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror

                    {{-- Kullanıcıya ÖNCEDEN söylenen söz: yapıştırdığı metindeki
                         telefon/e-posta ilana taşınmaz. Hem gizlilik hem de
                         platform dışına çekmenin önü. Servis prompt'unda da
                         aynı kural yazılı. --}}
                    <p class="mt-2 text-xs text-stone-600 dark:text-stone-400">
                        Metindeki telefon, e-posta ve adres bilgileri ilana taşınmaz.
                    </p>

                    <button type="submit" :disabled="gonderiliyor || metin.trim().length < 3"
                            class="mt-3 flex min-h-11 w-full items-center justify-center gap-2 rounded-lg bg-emerald-700 px-5 py-2.5 font-semibold text-white transition hover:bg-emerald-800 disabled:opacity-60 dark:bg-emerald-500 dark:text-stone-900 dark:hover:bg-emerald-400">
                        <template x-if="gonderiliyor">
                            <svg class="h-5 w-5 motion-safe:animate-spin" viewBox="0 0 24 24" fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                            </svg>
                        </template>
                        <span x-text="gonderiliyor ? 'Okunuyor…' : 'Taslak hazırla'"></span>
                    </button>
                </form>
            </div>
        @endif

        @if ($fotografAcik)
            <div class="mt-4 rounded-2xl border border-stone-200 bg-white p-5 dark:border-stone-800 dark:bg-stone-900"
                 x-data="{ analyzing: false, preview: null, hasFile: false,
                    pick(e) {
                        const file = e.target.files[0];
                        this.hasFile = !!file;
                        if (file) { this.preview = URL.createObjectURL(file); }
                    }
                 }">
                <h2 class="flex items-center gap-2 text-sm font-semibold text-stone-800 dark:text-stone-100">
                    <x-heroicon-o-camera class="h-5 w-5 shrink-0 text-emerald-700 dark:text-emerald-400" />
                    Fotoğrafla
                </h2>

                <form method="POST" action="{{ route('panel.listings.analyze') }}" enctype="multipart/form-data"
                      class="mt-3" @submit="analyzing = true">
                    @csrf

                    <label for="photo"
                           class="flex cursor-pointer flex-col items-center justify-center gap-3 rounded-2xl border-2 border-dashed border-stone-300 p-6 text-center transition hover:border-emerald-400 hover:bg-emerald-50/40 dark:border-stone-700 dark:hover:border-emerald-600 dark:hover:bg-emerald-950/20">
                        <template x-if="!preview">
                            <span class="flex flex-col items-center gap-2">
                                <span class="grid h-14 w-14 place-items-center rounded-full bg-emerald-700 text-white dark:bg-emerald-500">
                                    <x-heroicon-o-camera class="h-7 w-7" />
                                </span>
                                <span class="text-sm font-semibold text-stone-700 dark:text-stone-200">Fotoğraf çek veya seç</span>
                                <span class="text-xs text-stone-600 dark:text-stone-400">Kameran açılır — ürünü ortala ve çek</span>
                            </span>
                        </template>
                        <template x-if="preview">
                            <img :src="preview" alt="Önizleme" class="max-h-64 w-auto rounded-xl object-contain">
                        </template>
                    </label>
                    <input id="photo" name="photo" type="file" accept="image/*" capture="environment" class="sr-only" @change="pick($event)">
                    @error('photo') <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror

                    <button type="submit" x-show="hasFile" :disabled="analyzing" x-cloak
                            class="mt-3 flex min-h-11 w-full items-center justify-center gap-2 rounded-lg bg-emerald-700 px-5 py-2.5 font-semibold text-white transition hover:bg-emerald-800 disabled:opacity-60 dark:bg-emerald-500 dark:text-stone-900 dark:hover:bg-emerald-400">
                        <template x-if="analyzing">
                            <svg class="h-5 w-5 motion-safe:animate-spin" viewBox="0 0 24 24" fill="none">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                            </svg>
                        </template>
                        <span x-text="analyzing ? 'Fotoğraf okunuyor…' : 'Analiz et ve devam et'"></span>
                    </button>

                    <label for="photo" x-show="hasFile" x-cloak class="mt-3 flex min-h-11 cursor-pointer items-center justify-center gap-1 text-sm text-stone-500 hover:text-stone-700 dark:text-stone-400 dark:hover:text-stone-200">
                        <x-heroicon-o-arrow-path class="h-4 w-4" />
                        Başka fotoğraf seç
                    </label>
                </form>
            </div>
        @endif

        <p class="mt-6 text-center text-xs text-stone-600 dark:text-stone-400">
            Okuyamazsak sorun değil — bilgileri elle doldurabileceğin normal forma yönlendiririz.
        </p>
    </div>
</x-layouts.app>
