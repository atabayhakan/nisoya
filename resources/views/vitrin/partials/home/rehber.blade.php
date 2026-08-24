{{-- Ülke rehberi + Yaşam Rehberi (F2) — Vitrin teması. Klasikle AYNI veri
     sözleşmesi ve AYNI birincil/ikincil dallanma mantığı (bkz. klasik
     partial'ın yorumu); yalnız kabuk Vitrin kart dili. --}}
@if (\App\Support\HomeSections::visible('rehber') && $rehber !== null)
    <section class="mx-auto max-w-6xl px-4 pt-14" x-data x-reveal>
        <div class="rounded-[22px] border border-stone-200/60 bg-white p-6 shadow-brand sm:p-8 dark:border-stone-800 dark:bg-stone-900">
            @if ($rehber['ulkeler']->isNotEmpty())
                <p class="text-xs font-bold uppercase tracking-wide text-emerald-700 dark:text-emerald-400">Ülke Rehberi</p>

                @if ($rehber['secili'] !== null)
                    {{-- "için" kalıbı bilinçli: "{ülke}'da" eki her ülke adında doğru
                         çekimlenmez (Almanya'da ✓ ama İngiltere'de/ABD'nde ✗). --}}
                    <h2 class="mt-2 text-xl font-extrabold text-stone-800 dark:text-stone-50">
                        {{ $rehber['secili']->emoji }} {{ $rehber['secili']->name_tr }} için konsolosluk rehberi
                    </h2>
                    <p class="mt-1 max-w-2xl text-sm font-medium text-stone-500 dark:text-stone-400">
                        Hangi evrak lazım, ücret ne, ne kadar sürer — {{ $rehber['ozet']['temsilcilikSayisi'] }} temsilcilik
                        için resmî kaynaktan kendi ifademizle özetledik.
                    </p>

                    @if ($rehber['ozet']['islemTurleri']->isNotEmpty())
                        <div class="mt-4 flex flex-wrap gap-2">
                            @foreach ($rehber['ozet']['islemTurleri'] as $tur)
                                <a href="{{ route('rehber.ulke', strtolower($rehber['secili']->code)) }}"
                                   class="inline-flex items-center rounded-full border border-stone-200 bg-stone-50 px-3 py-1.5 text-sm font-semibold text-stone-700 transition hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-700 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-200 dark:hover:border-emerald-700 dark:hover:text-emerald-400">
                                    {{ $tur->ad }}
                                </a>
                            @endforeach
                        </div>
                    @endif

                    <a href="{{ route('rehber.ulke', strtolower($rehber['secili']->code)) }}"
                       class="mt-5 inline-flex items-center gap-1.5 rounded-xl bg-emerald-700 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-800 dark:bg-emerald-500 dark:text-stone-900 dark:hover:bg-emerald-400">
                        Rehberi aç
                        <span aria-hidden="true">→</span>
                    </a>
                @else
                    <h2 class="mt-2 text-xl font-extrabold text-stone-800 dark:text-stone-50">Konsolosluk işlemleri rehberi</h2>
                    <p class="mt-1 max-w-2xl text-sm font-medium text-stone-500 dark:text-stone-400">
                        Vekaletname, pasaport, askerlik, Mavi Kart… Hangi evrak lazım, ücret ne, ne kadar sürer —
                        resmî kaynaktan kendi ifademizle özetledik.
                        @if ($rehber['cozulenKod'] !== null)
                            Yaşadığın ülkenin rehberi hazırlanıyor; şimdilik hazır olanlar:
                        @endif
                    </p>
                @endif

                {{-- Elle ülke değiştirici (K1): hazır ülkeler, seçili olan vurgulu. --}}
                <div class="mt-5 flex flex-wrap items-center gap-2 border-t border-stone-100 pt-4 dark:border-stone-800">
                    @foreach ($rehber['ulkeler'] as $ulke)
                        <a href="{{ route('rehber.ulke', strtolower($ulke->code)) }}"
                           @class([
                               'inline-flex items-center gap-1.5 rounded-full px-3.5 py-1.5 text-sm font-semibold transition',
                               'bg-emerald-700 text-white dark:bg-emerald-500 dark:text-stone-900' => $rehber['secili']?->code === $ulke->code,
                               'border border-stone-200 bg-stone-50 text-stone-700 hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-700 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-200 dark:hover:border-emerald-700 dark:hover:text-emerald-400' => $rehber['secili']?->code !== $ulke->code,
                           ])>
                            <span>{{ $ulke->emoji }}</span>
                            <span>{{ $ulke->name_tr }}</span>
                        </a>
                    @endforeach
                </div>

                {{-- Yaşam Rehberi — İKİNCİL blok, Ülke Rehberi zaten birincilken. --}}
                @if ($rehber['yasamOzeti'] !== null && $rehber['yasamOzeti']->isNotEmpty())
                    <div class="mt-6 border-t border-stone-100 pt-6 dark:border-stone-800">
                        <p class="text-xs font-bold uppercase tracking-wide text-emerald-700 dark:text-emerald-400">Yaşam Rehberi</p>
                        <p class="mt-1 text-sm font-medium text-stone-500 dark:text-stone-400">
                            Bankacılıktan barınmaya, {{ $rehber['yasamSecili']->name_tr }} için gündelik hayat bilgileri.
                        </p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach ($rehber['yasamOzeti'] as $satir)
                                <a href="{{ route('yasam-rehberi.konular', [strtolower($rehber['yasamSecili']->code), $satir['kategori']->slug]) }}"
                                   class="inline-flex items-center gap-1 rounded-full border border-stone-200 bg-stone-50 px-3 py-1.5 text-sm font-semibold text-stone-700 transition hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-700 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-200 dark:hover:border-emerald-700 dark:hover:text-emerald-400">
                                    @if ($satir['kategori']->ikon)<span aria-hidden="true">{{ $satir['kategori']->ikon }}</span>@endif
                                    {{ $satir['kategori']->ad }}
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endif
            @elseif ($rehber['yasamOzeti'] !== null || $rehber['yasamUlkeler']->isNotEmpty())
                {{-- Ülke Rehberi bu ülkede/hiçbir ülkede hazır değil ama Yaşam
                     Rehberi hazır — Yaşam Rehberi birincil olur. --}}
                <p class="text-xs font-bold uppercase tracking-wide text-emerald-700 dark:text-emerald-400">Yaşam Rehberi</p>

                @if ($rehber['yasamSecili'] !== null)
                    <h2 class="mt-2 text-xl font-extrabold text-stone-800 dark:text-stone-50">
                        {{ $rehber['yasamSecili']->emoji }} {{ $rehber['yasamSecili']->name_tr }} için yaşam rehberi
                    </h2>
                    <p class="mt-1 max-w-2xl text-sm font-medium text-stone-500 dark:text-stone-400">
                        Bankacılıktan barınmaya, gündelik hayatı kolaylaştıran pratik bilgiler, Türkçe.
                    </p>

                    @if ($rehber['yasamOzeti']->isNotEmpty())
                        <div class="mt-4 flex flex-wrap gap-2">
                            @foreach ($rehber['yasamOzeti'] as $satir)
                                <a href="{{ route('yasam-rehberi.konular', [strtolower($rehber['yasamSecili']->code), $satir['kategori']->slug]) }}"
                                   class="inline-flex items-center rounded-full border border-stone-200 bg-stone-50 px-3 py-1.5 text-sm font-semibold text-stone-700 transition hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-700 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-200 dark:hover:border-emerald-700 dark:hover:text-emerald-400">
                                    @if ($satir['kategori']->ikon)<span aria-hidden="true">{{ $satir['kategori']->ikon }}</span>@endif
                                    {{ $satir['kategori']->ad }}
                                </a>
                            @endforeach
                        </div>
                    @endif

                    <a href="{{ route('yasam-rehberi.kategoriler', strtolower($rehber['yasamSecili']->code)) }}"
                       class="mt-5 inline-flex items-center gap-1.5 rounded-xl bg-emerald-700 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-emerald-800 dark:bg-emerald-500 dark:text-stone-900 dark:hover:bg-emerald-400">
                        Rehberi aç
                        <span aria-hidden="true">→</span>
                    </a>
                @else
                    <h2 class="mt-2 text-xl font-extrabold text-stone-800 dark:text-stone-50">Gündelik hayat rehberi</h2>
                    <p class="mt-1 max-w-2xl text-sm font-medium text-stone-500 dark:text-stone-400">
                        Bankacılıktan barınmaya, gündelik hayatı kolaylaştıran pratik bilgiler, Türkçe.
                        @if ($rehber['cozulenKod'] !== null)
                            Yaşadığın ülkenin rehberi hazırlanıyor; şimdilik hazır olanlar:
                        @endif
                    </p>
                @endif

                <div class="mt-5 flex flex-wrap items-center gap-2 border-t border-stone-100 pt-4 dark:border-stone-800">
                    @foreach ($rehber['yasamUlkeler'] as $ulke)
                        <a href="{{ route('yasam-rehberi.kategoriler', strtolower($ulke->code)) }}"
                           @class([
                               'inline-flex items-center gap-1.5 rounded-full px-3.5 py-1.5 text-sm font-semibold transition',
                               'bg-emerald-700 text-white dark:bg-emerald-500 dark:text-stone-900' => $rehber['yasamSecili']?->code === $ulke->code,
                               'border border-stone-200 bg-stone-50 text-stone-700 hover:border-emerald-300 hover:bg-emerald-50 hover:text-emerald-700 dark:border-stone-700 dark:bg-stone-800 dark:text-stone-200 dark:hover:border-emerald-700 dark:hover:text-emerald-400' => $rehber['yasamSecili']?->code !== $ulke->code,
                           ])>
                            <span>{{ $ulke->emoji }}</span>
                            <span>{{ $ulke->name_tr }}</span>
                        </a>
                    @endforeach
                </div>
            @endif
        </div>
    </section>
@endif
