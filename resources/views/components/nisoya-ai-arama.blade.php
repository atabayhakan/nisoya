{{--
    Anasayfa "Nisoya AI ile ara" çubuğu (docs/plans/2026-08-19-…, madde C).
    Kendini kapatır (logo-ikon/hareketli-logo ile aynı desen): sağlayıcı
    yapılandırılmamışsa ya da admin panelden kapatılmışsa hiç basılmaz —
    çağıran taraf bir koşul yazmak zorunda kalmaz.
--}}
@props(['ziyaretciUlke' => null, 'showPrompts' => true])

@php
    $aktif = app(\App\Services\NisoyaAiYonlendirici::class)->isEnabled();
@endphp

@if ($aktif)
    <div x-data="nisoyaAiArama({ ulke: '{{ $ziyaretciUlke?->code ?? '' }}' })" class="relative w-full">
        <form @submit.prevent="search()" class="relative">
            <div class="flex items-center gap-2.5 rounded-2xl bg-white/95 p-2 shadow-xl ring-1 ring-stone-200/90 backdrop-blur transition focus-within:ring-2 focus-within:ring-emerald-600 dark:bg-stone-900/95 dark:ring-stone-700/80">
                <div class="grid h-10 w-10 shrink-0 place-items-center rounded-xl bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400">
                    <x-heroicon-s-sparkles class="h-5 w-5" aria-hidden="true" />
                </div>
                <input
                    type="text"
                    x-model="query"
                    placeholder="Nisoya AI'ya sor: maç, pasaport yenileme, Berlin'de usta, takım kur..."
                    class="h-11 w-full border-0 bg-transparent p-0 text-sm sm:text-base font-medium text-stone-900 placeholder-stone-500 focus:outline-none focus:ring-0 dark:text-stone-100 dark:placeholder-stone-500"
                    aria-label="Nisoya AI ile diaspora asistanına sor"
                    maxlength="200"
                >
                <button
                    type="submit"
                    :disabled="loading || query.trim().length < 2"
                    class="inline-flex h-11 shrink-0 items-center justify-center gap-2 rounded-xl bg-emerald-700 px-5 text-sm font-bold text-white shadow-brand transition hover:bg-emerald-800 active:scale-[0.98] disabled:cursor-not-allowed disabled:opacity-50 dark:bg-emerald-500 dark:text-stone-900 dark:hover:bg-emerald-400"
                >
                    <span x-show="!loading" x-cloak class="flex items-center gap-1.5">
                        <span>Sor</span>
                        <span aria-hidden="true">→</span>
                    </span>
                    <span x-show="loading" x-cloak aria-hidden="true" class="flex items-center gap-1.5">
                        <svg class="h-4 w-4 animate-spin motion-reduce:animate-none" viewBox="0 0 24 24" fill="none"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4" /><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z" /></svg>
                        <span class="text-xs">Düşünüyor...</span>
                    </span>
                </button>
            </div>
        </form>

        {{-- Hazır Soru Çipleri (Prompt Önerileri) --}}
        @if ($showPrompts)
            <div class="mt-3 flex flex-wrap items-center justify-center gap-1.5 text-xs" x-show="!submitted">
                <span class="text-xs font-medium text-stone-500 dark:text-stone-400">✨ Hızlı Sor:</span>
                @php
                    $ornekSorular = [
                        ['etiket' => '⚽ Halı Saha Maçı & Takım', 'soru' => 'Şehrimde halı saha maçı ve Türk futbol takımları'],
                        ['etiket' => '🛂 Pasaport Yenileme', 'soru' => 'Pasaport yenileme randevusu ve gerekli evraklar'],
                        ['etiket' => '🏦 Hesap Açma', 'soru' => 'Yurt dışında ikametgah ve vergi numarası olmadan banka hesabı'],
                        ['etiket' => '💼 İş & Kariyer', 'soru' => 'Yurt dışında Türkçe bilenler için iş ilanları'],
                        ['etiket' => '⚖️ Türk Avukat', 'soru' => 'Türkçe bilen göçmenlik ve iş hukuku avukatı'],
                    ];
                @endphp
                @foreach ($ornekSorular as $os)
                    <button
                        type="button"
                        @click="query = '{{ addslashes($os['soru']) }}'; search()"
                        class="inline-flex items-center gap-1 rounded-full border border-stone-200/80 bg-white/80 px-2.5 py-1 text-2xs font-medium text-stone-700 shadow-2xs backdrop-blur transition hover:border-emerald-400 hover:bg-emerald-50/80 hover:text-emerald-800 active:scale-95 dark:border-stone-700 dark:bg-stone-800/80 dark:text-stone-300 dark:hover:border-emerald-600 dark:hover:bg-stone-700"
                    >
                        <span>{{ $os['etiket'] }}</span>
                    </button>
                @endforeach
            </div>
        @endif

        {{-- Sonuç paneli: Arama kartının içinde entegre açılır, taşma/üst üste binme yapmaz --}}
        <div x-show="submitted" x-cloak x-transition:enter="transition ease-out duration-200" x-transition:enter-start="opacity-0 -translate-y-2" x-transition:enter-end="opacity-100 translate-y-0" class="mt-3 overflow-hidden rounded-2xl border border-stone-200/90 bg-stone-50/95 p-3.5 shadow-xl backdrop-blur dark:border-stone-700/80 dark:bg-stone-800/95 text-left">
            <template x-if="error">
                <div class="p-3 text-center">
                    <p class="text-xs text-stone-600 dark:text-stone-300">Şu an doğrudan yanıt veremedim — normal aramayı deneyebilirsin.</p>
                </div>
            </template>

            {{-- 1. Akıllı AI Mesajı ve Eylemler (Spor, İş, Eylem, Kapsam Dışı veya Belirsiz) --}}
            <template x-if="!error && result && (result.mesaj || result.baslik)">
                <div class="rounded-2xl border p-4 shadow-2xs transition"
                     :class="{
                         'bg-emerald-50/90 border-emerald-200/90 dark:bg-emerald-950/40 dark:border-emerald-800/70': result.niyet === 'spor' || result.niyet === 'rehber' || result.niyet === 'yasam',
                         'bg-teal-50/90 border-teal-200/90 dark:bg-teal-950/40 dark:border-teal-800/70': result.niyet === 'is' || result.niyet === 'eylem',
                         'bg-amber-50/90 border-amber-200/90 dark:bg-amber-950/40 dark:border-amber-800/70': result.niyet === 'kapsam_disi',
                         'bg-stone-100/90 border-stone-200/90 dark:bg-stone-900/60 dark:border-stone-700/70': result.niyet === 'belirsiz' || result.niyet === 'ilan' || result.niyet === 'sss'
                     }">
                    <div>
                        <h4 class="text-xs sm:text-sm font-bold text-stone-900 dark:text-stone-100 flex items-center gap-1.5" x-text="result.baslik || '✨ Nisoya AI Asistanı'"></h4>
                        <p class="mt-1 text-xs leading-relaxed text-stone-700 dark:text-stone-300" x-text="result.mesaj"></p>
                        <p x-show="result.oneri" class="mt-1.5 text-2xs italic font-medium text-stone-600 dark:text-stone-400" x-text="'💡 ' + result.oneri"></p>
                    </div>

                    {{-- Akıllı Aksiyon Butonları --}}
                    <template x-if="result.eylemler && result.eylemler.length">
                        <div class="mt-3 flex flex-wrap items-center gap-2 pt-2.5 border-t border-black/5 dark:border-white/10">
                            <template x-for="act in result.eylemler" :key="act.url">
                                <a :href="act.url"
                                   :class="act.stil === 'primary'
                                       ? 'bg-emerald-700 text-white hover:bg-emerald-800 shadow-2xs dark:bg-emerald-500 dark:text-stone-950 dark:hover:bg-emerald-400'
                                       : (act.stil === 'secondary'
                                           ? 'bg-teal-700 text-white hover:bg-teal-800 shadow-2xs dark:bg-teal-600'
                                           : 'bg-white text-stone-800 border border-stone-200/90 hover:bg-stone-100 shadow-2xs dark:bg-stone-800 dark:text-stone-200 dark:border-stone-700')"
                                   class="inline-flex items-center gap-1.5 rounded-xl px-3 py-1.5 text-xs font-bold transition active:scale-95">
                                    <span x-text="act.baslik"></span>
                                    <span aria-hidden="true">→</span>
                                </a>
                            </template>
                        </div>
                    </template>
                </div>
            </template>

            {{-- 2. Bulunan Doğrulanmış Kayıtlar (Rehberler, Maçlar, Takımlar, Tesisler) --}}
            <template x-if="!error && result && result.sonuclar && result.sonuclar.length">
                <div class="mt-3">
                    <div class="mb-2 flex items-center justify-between px-1">
                        <span class="text-2xs font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-400">
                            ✓ Doğrulanmış Sonuçlar
                        </span>
                        <span class="text-2xs text-stone-500 dark:text-stone-400" x-text="result.sonuclar.length + ' Kayıt Bulundu'"></span>
                    </div>
                    <ul class="max-h-72 space-y-1.5 overflow-y-auto pr-1">
                        <template x-for="item in result.sonuclar" :key="item.url">
                            <li>
                                <a :href="item.url" class="flex items-center justify-between gap-3 rounded-xl border border-stone-200/80 bg-white p-3 text-left shadow-2xs transition hover:border-emerald-400 hover:bg-emerald-50/50 dark:border-stone-700 dark:bg-stone-900 dark:hover:border-emerald-600 dark:hover:bg-stone-800/90">
                                    <div class="min-w-0 flex-1">
                                        <div class="text-xs sm:text-sm font-bold text-stone-900 dark:text-stone-100" x-text="item.baslik"></div>
                                        <div class="mt-0.5 text-[11px] text-stone-500 dark:text-stone-400" x-text="item.altbaslik"></div>
                                    </div>
                                    <span class="shrink-0 text-xs font-bold text-emerald-700 dark:text-emerald-400 flex items-center gap-1">
                                        <span>Detay</span>
                                        <span>→</span>
                                    </span>
                                </a>
                            </li>
                        </template>
                    </ul>
                </div>
            </template>

            {{-- 3. Klasik İlan Linki Fallback (Mesaj veya Eylem yoksa) --}}
            <template x-if="!error && result && (!result.mesaj && !result.baslik) && (!result.sonuclar || !result.sonuclar.length)">
                <div class="rounded-xl border border-stone-200/70 bg-white p-3.5 text-center shadow-2xs dark:border-stone-700/80 dark:bg-stone-900">
                    <p class="text-xs font-medium text-stone-600 dark:text-stone-300">Bu konuda doğrudan bir sonuç bulunamadı.</p>
                    <a :href="result.ilanBaglantisi ? result.ilanBaglantisi : '/ilanlar'" class="mt-2 inline-flex items-center gap-1 text-xs font-bold text-emerald-700 hover:underline dark:text-emerald-400">
                        <span>Platform ilanlarında ara →</span>
                    </a>
                </div>
            </template>

            <div class="mt-3 flex items-center justify-between border-t border-stone-200/60 pt-2 dark:border-stone-700/60">
                <span class="text-3xs text-stone-500 dark:text-stone-400">Nisoya AI • Akıllı Diaspora Ağı</span>
                <button type="button" @click="reset()" class="rounded-lg px-2.5 py-1 text-xs font-semibold text-stone-600 transition hover:bg-stone-200/70 dark:text-stone-300 dark:hover:bg-stone-700/60">Kapat ✕</button>
            </div>
        </div>
    </div>
@endif
