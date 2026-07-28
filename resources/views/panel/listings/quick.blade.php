<x-layouts.app title="Fotoğrafla Hızlı İlan — Nisoya">
    <div class="mx-auto max-w-lg px-4 py-10" x-data="{ analyzing: false, preview: null, hasFile: false,
        pick(e) {
            const file = e.target.files[0];
            this.hasFile = !!file;
            if (file) { this.preview = URL.createObjectURL(file); }
        }
    }">
        <x-panel.back-link :href="route('panel.listings.create', ['tip' => 'urun'])" label="Normal form" />
        <h1 class="mt-2 text-2xl font-bold text-stone-900 dark:text-stone-50">📷 Fotoğrafla Hızlı İlan</h1>
        <p class="mt-2 text-sm text-stone-500 dark:text-stone-400">
            Satmak istediğin ürünün fotoğrafını çek — başlık, kategori ve tahmini fiyatı senin için önerelim.
            Sen onaylamadan hiçbir şey yayınlanmaz.
        </p>

        <form method="POST" action="{{ route('panel.listings.analyze') }}" enctype="multipart/form-data"
              class="mt-6" @submit="analyzing = true">
            @csrf

            <label for="photo"
                   class="flex cursor-pointer flex-col items-center justify-center gap-3 rounded-2xl border-2 border-dashed border-stone-300 bg-white p-8 text-center transition hover:border-emerald-400 hover:bg-emerald-50/40 dark:border-stone-700 dark:bg-stone-900 dark:hover:border-emerald-600 dark:hover:bg-emerald-950/20">
                <template x-if="!preview">
                    <span class="flex flex-col items-center gap-3">
                        <span class="grid h-16 w-16 place-items-center rounded-full bg-emerald-700 text-white dark:bg-emerald-500">
                            <x-heroicon-o-camera class="h-8 w-8" />
                        </span>
                        <span class="text-sm font-semibold text-stone-700 dark:text-stone-200">Fotoğraf çek veya seç</span>
                        <span class="text-xs text-stone-600 dark:text-stone-400">Kameran açılır — ürünü ortala ve çek</span>
                    </span>
                </template>
                <template x-if="preview">
                    <img :src="preview" alt="Önizleme" class="max-h-72 w-auto rounded-xl object-contain">
                </template>
            </label>
            <input id="photo" name="photo" type="file" accept="image/*" capture="environment" class="sr-only" @change="pick($event)">
            @error('photo') <p class="mt-2 text-sm text-red-600 dark:text-red-400">{{ $message }}</p> @enderror

            <button type="submit" x-show="hasFile" :disabled="analyzing" x-cloak
                    class="mt-4 flex w-full items-center justify-center gap-2 rounded-lg bg-emerald-700 px-5 py-3 font-semibold text-white transition hover:bg-emerald-800 disabled:opacity-60 dark:bg-emerald-500 dark:hover:bg-emerald-400 dark:text-stone-900">
                <template x-if="analyzing">
                    <svg class="h-5 w-5 animate-spin" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"></path>
                    </svg>
                </template>
                <span x-text="analyzing ? 'Fotoğraf okunuyor...' : 'Analiz et ve devam et'"></span>
            </button>

            <label for="photo" x-show="hasFile" x-cloak class="mt-3 flex cursor-pointer items-center justify-center gap-1 text-sm text-stone-500 hover:text-stone-700 dark:text-stone-400 dark:hover:text-stone-200">
                <x-heroicon-o-arrow-path class="h-4 w-4" />
                Başka fotoğraf seç
            </label>
        </form>

        <p class="mt-6 text-center text-xs text-stone-600 dark:text-stone-400">
            Fotoğrafı okuyamazsak sorun değil — bilgileri elle doldurabileceğin normal forma yönlendiririz.
        </p>
    </div>
</x-layouts.app>
