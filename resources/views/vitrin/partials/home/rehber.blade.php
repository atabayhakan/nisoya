{{-- Ülke rehberi + Yaşam Rehberi (F2) — Vitrin teması. --}}
@if (\App\Support\HomeSections::visible('rehber') && $rehber !== null)
    <section class="mx-auto max-w-6xl px-4 pt-14" x-data="{ showAllCountries: false, searchCountry: '' }" x-reveal>
        <div class="relative overflow-hidden rounded-3xl border border-stone-200/90 bg-white p-6 sm:p-10 shadow-sm dark:border-stone-800 dark:bg-stone-900">
            {{-- İnce arka plan ışıltısı --}}
            <div class="pointer-events-none absolute -right-20 -top-20 h-60 w-60 rounded-full bg-[radial-gradient(circle,color-mix(in_srgb,var(--color-emerald-500)_10%,transparent),transparent_70%)]" aria-hidden="true"></div>

            @if ($rehber['ulkeler']->isNotEmpty())
                <div class="relative z-10">
                    <div class="flex flex-wrap items-center gap-2">
                        <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-600/20 dark:bg-emerald-950/60 dark:text-emerald-300">
                            🏛️ Ülke Rehberi · Konsolosluk
                        </span>
                        <span class="text-xs font-medium text-stone-500 dark:text-stone-400">Güncel Harçlar, Belgeler ve Randevu Süreçleri</span>
                    </div>

                    @if ($rehber['secili'] !== null)
                        <div class="mt-4 flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                            <div class="max-w-2xl">
                                <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-stone-900 dark:text-stone-100">
                                    {{ $rehber['secili']->emoji }} {{ $rehber['secili']->name_tr }} için konsolosluk rehberi
                                </h2>
                                <p class="mt-2 text-sm leading-relaxed text-stone-500 dark:text-stone-400">
                                    Hangi evrak lazım, ücret ne, ne kadar sürer — {{ $rehber['ozet']['temsilcilikSayisi'] }} temsilcilik
                                    için resmî kaynaktan kendi ifademizle özetledik.
                                </p>
                            </div>

                            <a href="{{ route('rehber.ulke', strtolower($rehber['secili']->code)) }}"
                               class="inline-flex items-center gap-2 rounded-xl bg-emerald-700 px-5 py-2.5 text-xs sm:text-sm font-bold text-white shadow-sm transition hover:bg-emerald-800 shrink-0">
                                <span>Rehberi aç</span>
                                <span aria-hidden="true">→</span>
                            </a>
                        </div>

                        {{-- Hızlı İşlem Kartları --}}
                        @if ($rehber['ozet']['islemTurleri']->isNotEmpty())
                            <div class="mt-6 grid grid-cols-2 gap-2.5 sm:grid-cols-3 lg:grid-cols-6">
                                @foreach ($rehber['ozet']['islemTurleri'] as $tur)
                                    <a href="{{ route('rehber.ulke', strtolower($rehber['secili']->code)) }}"
                                       class="group flex flex-col justify-between rounded-2xl border border-stone-200/80 bg-stone-50/70 p-3 shadow-2xs transition hover:-translate-y-0.5 hover:border-emerald-300 hover:bg-white hover:shadow-xs dark:border-stone-800 dark:bg-stone-800/40 dark:hover:border-emerald-700 dark:hover:bg-stone-800">
                                        <span class="text-xs font-bold text-stone-800 group-hover:text-emerald-700 dark:text-stone-200 dark:group-hover:text-emerald-300">
                                            {{ $tur->ad }}
                                        </span>
                                        <span class="mt-2 text-[10px] font-medium text-stone-500 group-hover:text-emerald-700 dark:text-stone-400 dark:group-hover:text-emerald-300">
                                            Evrakları Gör →
                                        </span>
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    @else
                        <div class="mt-4">
                            <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-stone-900 dark:text-stone-100">
                                Konsolosluk işlemleri rehberi
                            </h2>
                            <p class="mt-2 max-w-2xl text-sm leading-relaxed text-stone-500 dark:text-stone-400">
                                Vekaletname, pasaport, askerlik, Mavi Kart… Hangi evrak lazım, ücret ne, ne kadar sürer —
                                resmî kaynaktan kendi ifademizle özetledik.
                                @if ($rehber['cozulenKod'] !== null)
                                    Yaşadığın ülkenin rehberi hazırlanıyor; şimdilik hazır olanlar:
                                @endif
                            </p>
                        </div>
                    @endif

                    {{-- Kompakt ve Akıllı Ülke Değiştirici --}}
                    @php
                        $topUlkeListesi = $rehber['ulkeler']->take(8);
                        $digerUlkeler = $rehber['ulkeler']->skip(8);
                    @endphp

                    <div class="mt-8 border-t border-stone-100 pt-5 dark:border-stone-800">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <span class="text-xs font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">
                                Ülke Seçimi:
                            </span>

                            @if ($digerUlkeler->isNotEmpty())
                                <button type="button" @click="showAllCountries = !showAllCountries"
                                        class="text-xs font-semibold text-emerald-700 transition hover:text-emerald-800 dark:text-emerald-400 dark:hover:text-emerald-300">
                                    <span x-text="showAllCountries ? 'Daha Az Ülke Göster ↑' : 'Tüm Hazır Ülkeler (' + {{ $rehber['ulkeler']->count() }} + ') ↓'"></span>
                                </button>
                            @endif
                        </div>

                        <div class="mt-3 flex flex-wrap items-center gap-2">
                            @foreach ($topUlkeListesi as $ulke)
                                <a href="{{ route('rehber.ulke', strtolower($ulke->code)) }}"
                                   @class([
                                       'inline-flex items-center gap-1.5 rounded-xl px-3.5 py-1.5 text-xs font-bold transition shadow-2xs',
                                       'bg-emerald-700 text-white shadow-xs dark:bg-emerald-500 dark:text-stone-950' => $rehber['secili']?->code === $ulke->code,
                                       'border border-stone-200/90 bg-stone-50 text-stone-700 hover:border-emerald-300 hover:bg-emerald-50/50 hover:text-emerald-700 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-200 dark:hover:border-emerald-700 dark:hover:text-emerald-400' => $rehber['secili']?->code !== $ulke->code,
                                   ])>
                                    <span>{{ $ulke->emoji }}</span>
                                    <span>{{ $ulke->name_tr }}</span>
                                </a>
                            @endforeach
                        </div>

                        {{-- Genişletilebilir Kalan Ülkeler Çekmecesi --}}
                        @if ($digerUlkeler->isNotEmpty())
                            <div x-show="showAllCountries" x-collapse x-cloak class="mt-4 rounded-2xl border border-stone-200/80 bg-stone-50/60 p-4 dark:border-stone-800 dark:bg-stone-800/40">
                                <div class="mb-3 max-w-xs">
                                    <input type="text" x-model="searchCountry" placeholder="Ülke filtrele..."
                                           class="w-full rounded-xl border border-stone-200 bg-white px-3 py-1.5 text-xs text-stone-800 placeholder-stone-400 focus:border-emerald-500 focus:outline-none focus:ring-1 focus:ring-emerald-500 dark:border-stone-700 dark:bg-stone-900 dark:text-stone-100 dark:placeholder-stone-500" />
                                </div>
                                <div class="flex flex-wrap gap-1.5">
                                    @foreach ($digerUlkeler as $ulke)
                                        <a href="{{ route('rehber.ulke', strtolower($ulke->code)) }}"
                                           x-show="searchCountry === '' || '{{ mb_strtolower($ulke->name_tr) }}'.includes(searchCountry.toLowerCase())"
                                           @class([
                                               'inline-flex items-center gap-1.5 rounded-lg px-2.5 py-1 text-xs font-semibold transition',
                                               'bg-emerald-700 text-white dark:bg-emerald-500 dark:text-stone-950' => $rehber['secili']?->code === $ulke->code,
                                               'border border-stone-200 bg-white text-stone-700 hover:border-emerald-300 hover:text-emerald-700 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-300 dark:hover:border-emerald-600 dark:hover:text-emerald-400' => $rehber['secili']?->code !== $ulke->code,
                                           ])>
                                            <span>{{ $ulke->emoji }}</span>
                                            <span>{{ $ulke->name_tr }}</span>
                                        </a>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>

                    {{-- Yaşam Rehberi — İKİNCİL blok --}}
                    @if ($rehber['yasamOzeti'] !== null && $rehber['yasamOzeti']->isNotEmpty())
                        <div class="mt-8 border-t border-stone-100 pt-6 dark:border-stone-800">
                            <div class="flex items-center gap-2">
                                <span class="inline-flex items-center gap-1.5 rounded-full bg-stone-100 px-2.5 py-0.5 text-xs font-semibold text-stone-700 dark:bg-stone-800 dark:text-stone-300">
                                    💡 Yaşam Rehberi
                                </span>
                                <span class="text-xs text-stone-500 dark:text-stone-400">
                                    Bankacılıktan barınmaya, {{ $rehber['yasamSecili']->name_tr }} için gündelik hayat bilgileri.
                                </span>
                            </div>
                            <div class="mt-3 flex flex-wrap gap-2">
                                @foreach ($rehber['yasamOzeti'] as $satir)
                                    <a href="{{ route('yasam-rehberi.konular', [strtolower($rehber['yasamSecili']->code), $satir['kategori']->slug]) }}"
                                       class="inline-flex items-center gap-1.5 rounded-xl border border-stone-200/90 bg-stone-50 px-3 py-1.5 text-xs font-semibold text-stone-700 transition hover:border-emerald-300 hover:bg-emerald-50/50 hover:text-emerald-700 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-300 dark:hover:border-emerald-600 dark:hover:text-emerald-400">
                                        @if ($satir['kategori']->ikon)<span aria-hidden="true">{{ $satir['kategori']->ikon }}</span>@endif
                                        <span>{{ $satir['kategori']->ad }}</span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif
                </div>
            @elseif ($rehber['yasamOzeti'] !== null || $rehber['yasamUlkeler']->isNotEmpty())
                <div class="relative z-10">
                    <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-50 px-2.5 py-0.5 text-xs font-semibold text-emerald-700 ring-1 ring-emerald-600/20 dark:bg-emerald-950/60 dark:text-emerald-300">
                        💡 Yaşam Rehberi
                    </span>

                    @if ($rehber['yasamSecili'] !== null)
                        <div class="mt-4 flex flex-col gap-5 lg:flex-row lg:items-start lg:justify-between">
                            <div class="max-w-2xl">
                                <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-stone-900 dark:text-stone-100">
                                    {{ $rehber['yasamSecili']->emoji }} {{ $rehber['yasamSecili']->name_tr }} için yaşam rehberi
                                </h2>
                                <p class="mt-2 text-sm leading-relaxed text-stone-500 dark:text-stone-400">
                                    Bankacılıktan barınmaya, gündelik hayatı kolaylaştıran pratik bilgiler, Türkçe.
                                </p>
                            </div>

                            <a href="{{ route('yasam-rehberi.kategoriler', strtolower($rehber['yasamSecili']->code)) }}"
                               class="inline-flex items-center gap-2 rounded-xl bg-emerald-700 px-5 py-2.5 text-xs sm:text-sm font-bold text-white shadow-sm transition hover:bg-emerald-800 shrink-0">
                                <span>Rehberi aç</span>
                                <span aria-hidden="true">→</span>
                            </a>
                        </div>

                        @if ($rehber['yasamOzeti']->isNotEmpty())
                            <div class="mt-6 flex flex-wrap gap-2">
                                @foreach ($rehber['yasamOzeti'] as $satir)
                                    <a href="{{ route('yasam-rehberi.konular', [strtolower($rehber['yasamSecili']->code), $satir['kategori']->slug]) }}"
                                       class="inline-flex items-center gap-1.5 rounded-xl border border-stone-200/90 bg-stone-50 px-3.5 py-2 text-xs font-semibold text-stone-700 transition hover:border-emerald-300 hover:bg-emerald-50/50 hover:text-emerald-700 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-300 dark:hover:border-emerald-600 dark:hover:text-emerald-400">
                                        @if ($satir['kategori']->ikon)<span aria-hidden="true">{{ $satir['kategori']->ikon }}</span>@endif
                                        <span>{{ $satir['kategori']->ad }}</span>
                                    </a>
                                @endforeach
                            </div>
                        @endif
                    @else
                        <div class="mt-4">
                            <h2 class="text-xl sm:text-2xl font-bold tracking-tight text-stone-900 dark:text-stone-100">
                                Gündelik hayat rehberi
                            </h2>
                            <p class="mt-2 max-w-2xl text-sm leading-relaxed text-stone-500 dark:text-stone-400">
                                Bankacılıktan barınmaya, gündelik hayatı kolaylaştıran pratik bilgiler, Türkçe.
                                @if ($rehber['cozulenKod'] !== null)
                                    Yaşadığın ülkenin rehberi hazırlanıyor; şimdilik hazır olanlar:
                                @endif
                            </p>
                        </div>
                    @endif

                    <div class="mt-8 border-t border-stone-100 pt-5 dark:border-stone-800">
                        <span class="text-xs font-bold uppercase tracking-wider text-stone-700 dark:text-stone-300">
                            Ülke Seçimi:
                        </span>
                        <div class="mt-3 flex flex-wrap items-center gap-2">
                            @foreach ($rehber['yasamUlkeler'] as $ulke)
                                <a href="{{ route('yasam-rehberi.kategoriler', strtolower($ulke->code)) }}"
                                   @class([
                                       'inline-flex items-center gap-1.5 rounded-xl px-3.5 py-1.5 text-xs font-bold transition shadow-2xs',
                                       'bg-emerald-700 text-white dark:bg-emerald-500 dark:text-stone-950' => $rehber['yasamSecili']?->code === $ulke->code,
                                       'border border-stone-200/90 bg-stone-50 text-stone-700 hover:border-emerald-300 hover:bg-emerald-50/50 hover:text-emerald-700 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-200 dark:hover:border-emerald-700 dark:hover:text-emerald-400' => $rehber['yasamSecili']?->code !== $ulke->code,
                                   ])>
                                    <span>{{ $ulke->emoji }}</span>
                                    <span>{{ $ulke->name_tr }}</span>
                                </a>
                            @endforeach
                        </div>
                    </div>
                </div>
            @endif
        </div>
    </section>
@endif

