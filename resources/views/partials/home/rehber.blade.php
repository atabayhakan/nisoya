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
    <section class="mx-auto max-w-6xl px-4 py-8 sm:py-12" x-reveal>
        <div class="relative overflow-hidden rounded-3xl border border-emerald-200/80 bg-gradient-to-b from-emerald-50/70 via-white to-stone-50/70 p-6 shadow-lg sm:p-10 dark:border-emerald-900/50 dark:from-stone-900 dark:via-stone-900/90 dark:to-emerald-950/30">
            {{-- Arka plan ışıltı efekti --}}
            <div class="pointer-events-none absolute -right-20 -top-20 h-72 w-72 rounded-full bg-emerald-300/20 blur-3xl dark:bg-emerald-700/10" aria-hidden="true"></div>

            <div class="relative z-10">
                @if ($rehber['ulkeler']->isNotEmpty())
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider text-emerald-800 dark:text-emerald-400">Ülke Rehberi</p>

                            @if ($rehber['secili'] !== null)
                                <h2 class="mt-2 text-2xl font-bold tracking-tight text-stone-900 sm:text-3xl lg:text-4xl dark:text-stone-50">
                                    {{ $rehber['secili']->emoji }} {{ $rehber['secili']->name_tr }} için konsolosluk rehberi
                                </h2>
                                <p class="mt-2 max-w-2xl text-sm sm:text-base text-stone-600 dark:text-stone-300">
                                    Hangi evrak lazım, ücret ne, ne kadar sürer — {{ $rehber['ozet']['temsilcilikSayisi'] }} temsilcilik
                                    için resmî kaynaktan kendi ifademizle özetledik.
                                </p>

                                @if ($rehber['ozet']['islemTurleri']->isNotEmpty())
                                    <div class="mt-4 flex flex-wrap gap-2">
                                        @foreach ($rehber['ozet']['islemTurleri'] as $tur)
                                            <a href="{{ route('rehber.ulke', strtolower($rehber['secili']->code)) }}"
                                               class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200/90 bg-white px-3.5 py-1.5 text-sm font-medium text-emerald-800 shadow-2xs transition hover:border-emerald-400 hover:bg-emerald-50/60 hover:text-emerald-900 dark:border-emerald-800/80 dark:bg-stone-900 dark:text-emerald-300 dark:hover:border-emerald-600">
                                                <span>{{ $tur->ad }}</span>
                                            </a>
                                        @endforeach
                                    </div>
                                @endif

                                <a href="{{ route('rehber.ulke', strtolower($rehber['secili']->code)) }}"
                                   class="mt-5 inline-flex items-center gap-1.5 rounded-xl bg-emerald-700 px-5 py-2.5 text-sm font-bold text-white shadow-brand transition hover:bg-emerald-800 dark:bg-emerald-500 dark:text-stone-900 dark:hover:bg-emerald-400">
                                    Rehberi aç
                                    <span aria-hidden="true">→</span>
                                </a>
                            @else
                                <h2 class="mt-2 text-2xl font-bold tracking-tight text-stone-900 sm:text-3xl lg:text-4xl dark:text-stone-50">
                                    Konsolosluk işlemleri rehberi
                                </h2>
                                <p class="mt-2 max-w-2xl text-sm sm:text-base text-stone-600 dark:text-stone-300">
                                    Vekaletname, pasaport, askerlik, Mavi Kart… Hangi evrak lazım, ücret ne, ne kadar sürer —
                                    resmî kaynaktan kendi ifademizle özetledik.
                                    @if ($rehber['cozulenKod'] !== null)
                                        Yaşadığın ülkenin rehberi hazırlanıyor; şimdilik hazır olanlar:
                                    @endif
                                </p>
                            @endif
                        </div>

                        {{-- Güven Rozetleri --}}
                        <div class="flex shrink-0 flex-wrap gap-2 text-2xs font-semibold">
                            <span class="inline-flex items-center gap-1 rounded-xl border border-emerald-200/90 bg-white/90 px-3 py-1.5 text-emerald-800 shadow-2xs backdrop-blur dark:border-emerald-800/80 dark:bg-stone-800/90 dark:text-emerald-300">
                                <span>✓</span> Resmî Kaynak Teyitli
                            </span>
                            <span class="inline-flex items-center gap-1 rounded-xl border border-stone-200/90 bg-white/90 px-3 py-1.5 text-stone-700 shadow-2xs backdrop-blur dark:border-stone-700/80 dark:bg-stone-800/90 dark:text-stone-300">
                                <span>🕒</span> 90 Günlük Tazelik
                            </span>
                        </div>
                    </div>

                    {{-- Elle ülke değiştirici (K1): hazır ülkeler, seçili olan vurgulu --}}
                    <div class="mt-6 flex flex-wrap items-center gap-2 border-t border-emerald-100 pt-4 dark:border-emerald-900/40">
                        @foreach ($rehber['ulkeler'] as $ulke)
                            <a href="{{ route('rehber.ulke', strtolower($ulke->code)) }}"
                               @class([
                                   'inline-flex items-center gap-1.5 rounded-full px-3.5 py-1.5 text-sm font-medium transition shadow-2xs',
                                   'bg-emerald-700 text-white font-bold dark:bg-emerald-500 dark:text-stone-900' => $rehber['secili']?->code === $ulke->code,
                                   'border border-stone-200/90 bg-white text-stone-700 hover:border-emerald-400 hover:text-emerald-800 dark:border-stone-700 dark:bg-stone-900 dark:text-stone-200 dark:hover:border-emerald-700 dark:hover:text-emerald-400' => $rehber['secili']?->code !== $ulke->code,
                               ])>
                                <span>{{ $ulke->emoji }}</span>
                                <span>{{ $ulke->name_tr }}</span>
                            </a>
                        @endforeach
                    </div>

                    {{-- Yaşam Rehberi — İKİNCİL blok, Ülke Rehberi zaten birincilken --}}
                    @if ($rehber['yasamOzeti'] !== null && $rehber['yasamOzeti']->isNotEmpty())
                        <div class="mt-8 border-t border-emerald-100 pt-6 dark:border-emerald-900/40">
                            <div class="flex flex-col gap-1 sm:flex-row sm:items-center sm:justify-between">
                                <div>
                                    <p class="text-xs font-bold uppercase tracking-wider text-emerald-800 dark:text-emerald-400">Yaşam Rehberi</p>
                                    <p class="mt-1 text-sm text-stone-600 dark:text-stone-300">
                                        Bankacılıktan barınmaya, {{ $rehber['yasamSecili']->name_tr }} için gündelik hayat bilgileri.
                                    </p>
                                </div>
                                <a href="{{ route('yasam-rehberi.kategoriler', strtolower($rehber['yasamSecili']->code)) }}" class="text-xs font-bold text-emerald-700 hover:underline dark:text-emerald-400">
                                    Tüm Yaşam Kategorileri →
                                </a>
                            </div>

                            <div class="mt-4 flex flex-wrap gap-2">
                                @foreach ($rehber['yasamOzeti'] as $satir)
                                    <a href="{{ route('yasam-rehberi.konular', [strtolower($rehber['yasamSecili']->code), $satir['kategori']->slug]) }}"
                                       class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200/90 bg-white px-3.5 py-1.5 text-sm font-medium text-emerald-800 shadow-2xs transition hover:border-emerald-400 hover:bg-emerald-50/60 hover:text-emerald-900 dark:border-emerald-800/80 dark:bg-stone-900 dark:text-emerald-300 dark:hover:border-emerald-600">
                                        @if ($satir['kategori']->ikon)<span aria-hidden="true">{{ $satir['kategori']->ikon }}</span>@endif
                                        <span>{{ $satir['kategori']->ad }}</span>
                                    </a>
                                @endforeach
                            </div>
                        </div>
                    @endif

                @elseif ($rehber['yasamOzeti'] !== null || $rehber['yasamUlkeler']->isNotEmpty())
                    {{-- Ülke Rehberi bu ülkede/hiçbir ülkede hazır değil ama Yaşam
                         Rehberi hazır — Yaşam Rehberi birincil olur. --}}
                    <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                        <div>
                            <p class="text-xs font-bold uppercase tracking-wider text-emerald-800 dark:text-emerald-400">Yaşam Rehberi</p>

                            @if ($rehber['yasamSecili'] !== null)
                                <h2 class="mt-2 text-2xl font-bold tracking-tight text-stone-900 sm:text-3xl lg:text-4xl dark:text-stone-50">
                                    {{ $rehber['yasamSecili']->emoji }} {{ $rehber['yasamSecili']->name_tr }} için yaşam rehberi
                                </h2>
                                <p class="mt-2 max-w-2xl text-sm sm:text-base text-stone-600 dark:text-stone-300">
                                    Bankacılıktan barınmaya, gündelik hayatı kolaylaştıran pratik bilgiler, Türkçe.
                                </p>

                                @if ($rehber['yasamOzeti']->isNotEmpty())
                                    <div class="mt-4 flex flex-wrap gap-2">
                                        @foreach ($rehber['yasamOzeti'] as $satir)
                                            <a href="{{ route('yasam-rehberi.konular', [strtolower($rehber['yasamSecili']->code), $satir['kategori']->slug]) }}"
                                               class="inline-flex items-center gap-1.5 rounded-full border border-emerald-200/90 bg-white px-3.5 py-1.5 text-sm font-medium text-emerald-800 shadow-2xs transition hover:border-emerald-400 hover:bg-emerald-50/60 hover:text-emerald-900 dark:border-emerald-800/80 dark:bg-stone-900 dark:text-emerald-300 dark:hover:border-emerald-600">
                                                @if ($satir['kategori']->ikon)<span aria-hidden="true">{{ $satir['kategori']->ikon }}</span>@endif
                                                <span>{{ $satir['kategori']->ad }}</span>
                                            </a>
                                        @endforeach
                                    </div>
                                @endif

                                <a href="{{ route('yasam-rehberi.kategoriler', strtolower($rehber['yasamSecili']->code)) }}"
                                   class="mt-5 inline-flex items-center gap-1.5 rounded-xl bg-emerald-700 px-5 py-2.5 text-sm font-bold text-white shadow-brand transition hover:bg-emerald-800 dark:bg-emerald-500 dark:text-stone-900 dark:hover:bg-emerald-400">
                                    Rehberi aç
                                    <span aria-hidden="true">→</span>
                                </a>
                            @else
                                <h2 class="mt-2 text-2xl font-bold tracking-tight text-stone-900 sm:text-3xl lg:text-4xl dark:text-stone-50">
                                    Gündelik hayat rehberi
                                </h2>
                                <p class="mt-2 max-w-2xl text-sm sm:text-base text-stone-600 dark:text-stone-300">
                                    Bankacılıktan barınmaya, gündelik hayatı kolaylaştıran pratik bilgiler, Türkçe.
                                    @if ($rehber['cozulenKod'] !== null)
                                        Yaşadığın ülkenin rehberi hazırlanıyor; şimdilik hazır olanlar:
                                    @endif
                                </p>
                            @endif
                        </div>

                        {{-- Güven Rozetleri --}}
                        <div class="flex shrink-0 flex-wrap gap-2 text-2xs font-semibold">
                            <span class="inline-flex items-center gap-1 rounded-xl border border-emerald-200/90 bg-white/90 px-3 py-1.5 text-emerald-800 shadow-2xs backdrop-blur dark:border-emerald-800/80 dark:bg-stone-800/90 dark:text-emerald-300">
                                <span>✓</span> Resmî Kaynak Teyitli
                            </span>
                            <span class="inline-flex items-center gap-1 rounded-xl border border-stone-200/90 bg-white/90 px-3 py-1.5 text-stone-700 shadow-2xs backdrop-blur dark:border-stone-700/80 dark:bg-stone-800/90 dark:text-stone-300">
                                <span>🕒</span> 90 Günlük Tazelik
                            </span>
                        </div>
                    </div>

                    <div class="mt-6 flex flex-wrap items-center gap-2 border-t border-emerald-100 pt-4 dark:border-emerald-900/40">
                        @foreach ($rehber['yasamUlkeler'] as $ulke)
                            <a href="{{ route('yasam-rehberi.kategoriler', strtolower($ulke->code)) }}"
                               @class([
                                   'inline-flex items-center gap-1.5 rounded-full px-3.5 py-1.5 text-sm font-medium transition shadow-2xs',
                                   'bg-emerald-700 text-white font-bold dark:bg-emerald-500 dark:text-stone-900' => $rehber['yasamSecili']?->code === $ulke->code,
                                   'border border-stone-200/90 bg-white text-stone-700 hover:border-emerald-400 hover:text-emerald-800 dark:border-stone-700 dark:bg-stone-900 dark:text-stone-200 dark:hover:border-emerald-700 dark:hover:text-emerald-400' => $rehber['yasamSecili']?->code !== $ulke->code,
                               ])>
                                <span>{{ $ulke->emoji }}</span>
                                <span>{{ $ulke->name_tr }}</span>
                            </a>
                        @endforeach
                    </div>
                @endif
            </div>
        </div>
    </section>
@endif
