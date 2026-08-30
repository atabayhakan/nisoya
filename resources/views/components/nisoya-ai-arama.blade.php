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

        {{-- Sonuç paneli: Arama kartının içinde entegre açılır, taşma/üst üste binme yapmaz --}}
        <div x-show="submitted" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="mt-3 overflow-hidden rounded-2xl border border-stone-200/90 bg-stone-50/80 p-2 shadow-inner dark:border-stone-700/80 dark:bg-stone-800/50">
            <template x-if="error">
                <div class="p-3 text-center">
                    <p class="text-xs text-stone-600 dark:text-stone-300">Şu an doğrudan yanıt veremedim — normal aramayı deneyebilirsin.</p>
                </div>
            </template>
            {{-- Kapsam Dışı Uyarısı --}}
            <template x-if="!error && result && result.niyet === 'kapsam_disi'">
                <div class="p-3.5 bg-amber-50/80 rounded-xl border border-amber-200/80 text-left dark:bg-amber-950/40 dark:border-amber-800/60">
                    <div class="text-xs font-bold text-amber-900 dark:text-amber-200">ℹ️ T.C. Konsolosluk Kapsamı Dışında</div>
                    <p class="mt-1 text-xs text-amber-800 dark:text-amber-300">Bu işlem, T.C. konsolosluklarının yetki alanında değildir. Üçüncü bir ülkeye vize veya ikamet başvurusu için ilgili ülkenin kendi göç idaresi / konsolosluğuna başvurmanız gerekir.</p>
                </div>
            </template>
            {{-- Bulunan Sonuçlar Listesi --}}
            <template x-if="!error && result && result.sonuclar && result.sonuclar.length">
                <ul class="max-h-72 space-y-1.5 overflow-y-auto pr-1">
                    <template x-for="item in (result ? result.sonuclar : [])" :key="item.url">
                        <li>
                            <a :href="item.url" class="flex items-center justify-between gap-3 rounded-xl border border-stone-200/70 bg-white p-3 text-left shadow-2xs transition hover:border-emerald-300 hover:bg-emerald-50/50 dark:border-stone-700/80 dark:bg-stone-900 dark:hover:border-emerald-600 dark:hover:bg-stone-800/80">
                                <div class="min-w-0 flex-1">
                                    <div class="text-xs sm:text-sm font-bold text-stone-900 dark:text-stone-100" x-text="item.baslik"></div>
                                    <div class="mt-0.5 text-[11px] text-stone-500 dark:text-stone-400" x-text="item.altbaslik"></div>
                                </div>
                                <span class="shrink-0 text-xs font-bold text-emerald-700 dark:text-emerald-400">Rehbere Git →</span>
                            </a>
                        </li>
                    </template>
                </ul>
            </template>
            {{-- Hazır Rehber Bulunamadığında Güvenli Alternatif --}}
            <template x-if="!error && (!result || result.niyet !== 'kapsam_disi') && (!result || !result.sonuclar || !result.sonuclar.length)">
                <div class="rounded-xl border border-stone-200/70 bg-white p-3.5 text-center shadow-2xs dark:border-stone-700/80 dark:bg-stone-900">
                    <p class="text-xs font-medium text-stone-600 dark:text-stone-300" x-text="result && result.niyet === 'is' ? 'Bu konuda hazır bir iş ilanı kaydı bulunamadı.' : 'Bu konuda henüz hazır bir rehber içeriği bulunamadı.'"></p>
                    <a :href="result && result.ilanBaglantisi ? result.ilanBaglantisi : '/ilanlar'" class="mt-2 inline-flex items-center gap-1 text-xs font-bold text-emerald-700 hover:underline dark:text-emerald-400" x-text="(result && result.niyet === 'is' ? 'İş ilanlarında ara' : 'Platform ilanlarında ara') + ' →'">
                    </a>
                </div>
            </template>
            <button type="button" @click="reset()" class="mt-2 w-full rounded-lg py-1.5 text-center text-xs font-semibold text-stone-600 transition hover:bg-stone-200/60 dark:text-stone-400 dark:hover:bg-stone-700/50">Kapat ✕</button>
        </div>
    </div>
@endif
