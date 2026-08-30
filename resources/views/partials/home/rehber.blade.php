{{-- Ülke rehberi + Yaşam Rehberi (F2) — ana sayfanın ortak rehber yüzü.

     Veri sözleşmesi HomeController::rehberVerisi: modül kapalıysa ya da
     HİÇBİR rehberin (Ülke ya da Yaşam) YAYINDA içeriği yoksa $rehber null
     gelir ve bölüm hiç basılmaz. Ülke önceliği K1 (üye ikameti > GeoIP);
     çözülen ülke hazır değilse bölüm hazır ülkeleri önerir — varsayılan
     dayatmaz. Ülke pilleri aynı zamanda K1'in "elle ülke değiştirici" şartı.

     BİRİNCİL/İKİNCİL SIRASI BİLİNÇLİ: Ülke Rehberi varsa (bugün itibarıyla
     her zaman) birincil kalır, Yaşam Rehberi altına EK blok olarak eklenir
     (tasarım §5: "mevcut bölüm genişletilir", ayrı bölüm açılmaz). Ülke
     Rehberi'nin kapsamadığı bir ülkede yalnız Yaşam Rehberi hazırsa (örn.
     Hollanda/Fransa/Belçika/Avusturya — F1 Bankacılık partisi burada),
     Yaşam Rehberi birincil olur; boş bir "Ülke Rehberi" başlığı asla
     içeriksiz basılmaz. --}}
@if (\App\Support\HomeSections::visible('rehber') && $rehber !== null)
    <section class="mx-auto max-w-6xl px-4 py-8" x-data x-reveal>
        <div class="rounded-2xl border border-emerald-100 bg-emerald-50/60 p-6 sm:p-8 dark:border-emerald-900/40 dark:bg-emerald-950/20">
            @if ($rehber['ulkeler']->isNotEmpty())
                <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700 dark:text-emerald-400">Ülke Rehberi</p>

                @if ($rehber['secili'] !== null)
                    {{-- "için" kalıbı bilinçli: "{ülke}'da" eki her ülke adında doğru
                         çekimlenmez (Almanya'da ✓ ama İngiltere'de/ABD'nde ✗). --}}
                    <h2 class="mt-2 text-3xl font-serif font-normal text-stone-900 md:text-4xl dark:text-stone-50">
                        {{ $rehber['secili']->emoji }} {{ $rehber['secili']->name_tr }} için konsolosluk rehberi
                    </h2>
                    <p class="mt-2 max-w-2xl text-sm text-stone-600 dark:text-stone-300">
                        Hangi evrak lazım, ücret ne, ne kadar sürer — {{ $rehber['ozet']['temsilcilikSayisi'] }} temsilcilik
                        için resmî kaynaktan kendi ifademizle özetledik.
                    </p>

                    @if ($rehber['ozet']['islemTurleri']->isNotEmpty())
                        <div class="mt-4 flex flex-wrap gap-2">
                            @foreach ($rehber['ozet']['islemTurleri'] as $tur)
                                <a href="{{ route('rehber.ulke', strtolower($rehber['secili']->code)) }}"
                                   class="inline-flex items-center rounded-full border border-emerald-200 bg-white px-3 py-1.5 text-sm font-medium text-emerald-800 transition hover:border-emerald-400 hover:text-emerald-700 dark:border-emerald-800 dark:bg-stone-900 dark:text-emerald-300 dark:hover:border-emerald-600">
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
                    <h2 class="mt-2 text-3xl font-serif font-normal text-stone-900 md:text-4xl dark:text-stone-50">Konsolosluk işlemleri rehberi</h2>
                    <p class="mt-2 max-w-2xl text-sm text-stone-600 dark:text-stone-300">
                        Vekaletname, pasaport, askerlik, Mavi Kart… Hangi evrak lazım, ücret ne, ne kadar sürer —
                        resmî kaynaktan kendi ifademizle özetledik.
                        @if ($rehber['cozulenKod'] !== null)
                            Yaşadığın ülkenin rehberi hazırlanıyor; şimdilik hazır olanlar:
                        @endif
                    </p>
                @endif

                {{-- Elle ülke değiştirici (K1): hazır ülkeler, seçili olan vurgulu. --}}
                <div class="mt-5 flex flex-wrap items-center gap-2 border-t border-emerald-100 pt-4 dark:border-emerald-900/40">
                    @foreach ($rehber['ulkeler'] as $ulke)
                        <a href="{{ route('rehber.ulke', strtolower($ulke->code)) }}"
                           @class([
                               'inline-flex items-center gap-1.5 rounded-full px-3.5 py-1.5 text-sm font-medium transition',
                               'bg-emerald-700 text-white dark:bg-emerald-500 dark:text-stone-900' => $rehber['secili']?->code === $ulke->code,
                               'border border-stone-200 bg-white text-stone-700 hover:border-emerald-300 hover:text-emerald-700 dark:border-stone-700 dark:bg-stone-900 dark:text-stone-200 dark:hover:border-emerald-700 dark:hover:text-emerald-400' => $rehber['secili']?->code !== $ulke->code,
                           ])>
                            <span>{{ $ulke->emoji }}</span>
                            <span>{{ $ulke->name_tr }}</span>
                        </a>
                    @endforeach
                </div>

                {{-- Yaşam Rehberi — İKİNCİL blok, Ülke Rehberi zaten birincilken. --}}
                @if ($rehber['yasamOzeti'] !== null && $rehber['yasamOzeti']->isNotEmpty())
                    <div class="mt-6 border-t border-emerald-100 pt-6 dark:border-emerald-900/40">
                        <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700 dark:text-emerald-400">Yaşam Rehberi</p>
                        <p class="mt-1 text-sm text-stone-600 dark:text-stone-300">
                            Bankacılıktan barınmaya, {{ $rehber['yasamSecili']->name_tr }} için gündelik hayat bilgileri.
                        </p>
                        <div class="mt-3 flex flex-wrap gap-2">
                            @foreach ($rehber['yasamOzeti'] as $satir)
                                <a href="{{ route('yasam-rehberi.konular', [strtolower($rehber['yasamSecili']->code), $satir['kategori']->slug]) }}"
                                   class="inline-flex items-center gap-1 rounded-full border border-emerald-200 bg-white px-3 py-1.5 text-sm font-medium text-emerald-800 transition hover:border-emerald-400 hover:text-emerald-700 dark:border-emerald-800 dark:bg-stone-900 dark:text-emerald-300 dark:hover:border-emerald-600">
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
                <p class="text-xs font-semibold uppercase tracking-wide text-emerald-700 dark:text-emerald-400">Yaşam Rehberi</p>

                @if ($rehber['yasamSecili'] !== null)
                    <h2 class="mt-2 text-3xl font-serif font-normal text-stone-900 md:text-4xl dark:text-stone-50">
                        {{ $rehber['yasamSecili']->emoji }} {{ $rehber['yasamSecili']->name_tr }} için yaşam rehberi
                    </h2>
                    <p class="mt-2 max-w-2xl text-sm text-stone-600 dark:text-stone-300">
                        Bankacılıktan barınmaya, gündelik hayatı kolaylaştıran pratik bilgiler, Türkçe.
                    </p>

                    @if ($rehber['yasamOzeti']->isNotEmpty())
                        <div class="mt-4 flex flex-wrap gap-2">
                            @foreach ($rehber['yasamOzeti'] as $satir)
                                <a href="{{ route('yasam-rehberi.konular', [strtolower($rehber['yasamSecili']->code), $satir['kategori']->slug]) }}"
                                   class="inline-flex items-center gap-1 rounded-full border border-emerald-200 bg-white px-3 py-1.5 text-sm font-medium text-emerald-800 transition hover:border-emerald-400 hover:text-emerald-700 dark:border-emerald-800 dark:bg-stone-900 dark:text-emerald-300 dark:hover:border-emerald-600">
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
                    <h2 class="mt-2 text-3xl font-serif font-normal text-stone-900 md:text-4xl dark:text-stone-50">Gündelik hayat rehberi</h2>
                    <p class="mt-2 max-w-2xl text-sm text-stone-600 dark:text-stone-300">
                        Bankacılıktan barınmaya, gündelik hayatı kolaylaştıran pratik bilgiler, Türkçe.
                        @if ($rehber['cozulenKod'] !== null)
                            Yaşadığın ülkenin rehberi hazırlanıyor; şimdilik hazır olanlar:
                        @endif
                    </p>
                @endif

                <div class="mt-5 flex flex-wrap items-center gap-2 border-t border-emerald-100 pt-4 dark:border-emerald-900/40">
                    @foreach ($rehber['yasamUlkeler'] as $ulke)
                        <a href="{{ route('yasam-rehberi.kategoriler', strtolower($ulke->code)) }}"
                           @class([
                               'inline-flex items-center gap-1.5 rounded-full px-3.5 py-1.5 text-sm font-medium transition',
                               'bg-emerald-700 text-white dark:bg-emerald-500 dark:text-stone-900' => $rehber['yasamSecili']?->code === $ulke->code,
                               'border border-stone-200 bg-white text-stone-700 hover:border-emerald-300 hover:text-emerald-700 dark:border-stone-700 dark:bg-stone-900 dark:text-stone-200 dark:hover:border-emerald-700 dark:hover:text-emerald-400' => $rehber['yasamSecili']?->code !== $ulke->code,
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
