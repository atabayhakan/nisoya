{{--
    Anasayfa "Nisoya AI ile ara" çubuğu (docs/plans/2026-08-19-…, madde C).
    Kendini kapatır (logo-ikon/hareketli-logo ile aynı desen): sağlayıcı
    yapılandırılmamışsa ya da admin panelden kapatılmışsa hiç basılmaz —
    çağıran taraf bir koşul yazmak zorunda kalmaz.
--}}
@php($aktif = app(\App\Services\NisoyaAiYonlendirici::class)->isEnabled())

@if ($aktif)
    <div x-data="nisoyaAiArama()" class="relative">
        <form @submit.prevent="search()">
            <div class="flex items-center gap-2 px-3 py-1">
                <x-heroicon-s-sparkles class="h-5 w-5 shrink-0 text-emerald-700 dark:text-emerald-400" aria-hidden="true" />
                <input
                    type="text"
                    x-model="query"
                    placeholder="Nisoya AI'ya sor: pasaportum kayboldu, SSN'siz banka hesabı..."
                    class="h-12 w-full border-0 bg-transparent p-0 text-sm sm:text-base font-medium text-stone-900 placeholder:text-stone-500 focus:outline-none focus:ring-0 dark:text-stone-100 dark:placeholder:text-stone-500"
                    aria-label="Nisoya AI ile ara"
                    maxlength="200"
                >
                <button
                    type="submit"
                    :disabled="loading || query.trim().length < 3"
                    class="inline-flex h-10 shrink-0 items-center justify-center gap-1.5 rounded-xl bg-emerald-700 px-5 text-xs font-bold text-white shadow-brand transition hover:bg-emerald-800 active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-40 dark:bg-emerald-500 dark:text-stone-900 dark:hover:bg-emerald-400"
                >
                    <span x-show="!loading" x-cloak>Sor</span>
                    <span x-show="loading" x-cloak aria-hidden="true">
                        <svg class="h-4 w-4 animate-spin motion-reduce:animate-none" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
                    </span>
                </button>
            </div>
        </form>

        {{-- Sonuç paneli — sessiz başarısızlık YOK: her durumda (hata, boş,
             dolu) bir sonraki adımı gösterir, asla boş bırakmaz. --}}
        <div x-show="submitted" x-cloak x-transition.opacity.duration.150ms class="absolute left-0 right-0 top-full z-50 mt-3 overflow-hidden rounded-2xl border border-stone-200 bg-white shadow-2xl dark:border-stone-700 dark:bg-stone-900">
            <template x-if="error">
                <p class="p-4 text-sm text-stone-500 dark:text-stone-400">Şu an yanıt veremedim — normal aramayı deneyebilirsin.</p>
            </template>
            {{--
                'kapsam_disi': üçüncü ülke vizesi gibi T.C. konsolosluğunun
                SUNMADIĞI bir konu. Gerçek olay (2026-08-25): bu ayrım yokken
                "Tayland vize işlemleri" sessizce /ilanlar'a düşüyordu —
                ziyaretçi neden sonuç alamadığını hiç öğrenmiyordu. Burada da
                LİNK YOK — yanlış bir sonraki adım önermek, hiç önermemekten
                kötüdür (ilanBaglantisi zaten backend'te null).
            --}}
            <template x-if="!error && result && result.niyet === 'kapsam_disi'">
                <div class="p-4">
                    <p class="text-sm text-stone-600 dark:text-stone-300">Bu, T.C. konsolosluklarının kapsamında değil. Üçüncü bir ülkeye (vize, ikamet vb.) başvuru için o ülkenin kendi konsolosluğuna ya da e-vize sistemine bakman gerekir.</p>
                </div>
            </template>
            <template x-if="!error && result && result.sonuclar && result.sonuclar.length">
                <ul class="max-h-80 divide-y divide-stone-100 overflow-y-auto dark:divide-stone-800">
                    <template x-for="item in (result ? result.sonuclar : [])" :key="item.url">
                        <li>
                            <a :href="item.url" class="block px-4 py-3 text-left transition hover:bg-stone-50 dark:hover:bg-stone-800">
                                <div class="text-sm font-semibold text-stone-800 dark:text-stone-100" x-text="item.baslik"></div>
                                <div class="text-xs text-stone-500 dark:text-stone-400" x-text="item.altbaslik"></div>
                            </a>
                        </li>
                    </template>
                </ul>
            </template>
            <template x-if="!error && (!result || result.niyet !== 'kapsam_disi') && (!result || !result.sonuclar || !result.sonuclar.length)">
                <div class="p-4">
                    <p class="text-sm text-stone-600 dark:text-stone-300" x-text="result && result.niyet === 'is' ? 'Bu konuda hazır bir iş ilanı bulamadım.' : 'Bu konuda hazır bir rehberimiz yok.'"></p>
                    <a :href="result && result.ilanBaglantisi ? result.ilanBaglantisi : '/ilanlar'" class="mt-2 inline-flex items-center gap-1 text-sm font-semibold text-emerald-700 hover:underline dark:text-emerald-400" x-text="(result && result.niyet === 'is' ? 'İş ilanlarında ara' : 'İlanlarda ara') + ' →'">
                    </a>
                </div>
            </template>
            <button type="button" @click="reset()" class="w-full border-t border-stone-100 px-4 py-2 text-center text-xs font-medium text-stone-500 transition hover:bg-stone-50 dark:border-stone-800 dark:text-stone-400 dark:hover:bg-stone-800">Kapat</button>
        </div>
    </div>
@endif
