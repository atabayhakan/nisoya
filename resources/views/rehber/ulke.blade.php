<x-layouts.app
    :title="$country->name_tr.' Türk Konsolosluk Rehberi — '.setting('genel.site_adi')"
    :description="$country->name_tr.'\'daki Türk büyükelçiliği ve başkonsolosluklarında vekaletname, pasaport, askerlik gibi işlemler için evrak listeleri, süreler ve resmî kaynaklar.'"
>
    <div class="mx-auto max-w-6xl px-4 py-8 sm:py-10">
        {{-- Breadcrumbs --}}
        <nav class="flex items-center gap-2 text-xs font-semibold text-stone-500 dark:text-stone-400" aria-label="breadcrumb">
            <a href="{{ route('home') }}" class="hover:text-emerald-700 dark:hover:text-emerald-400">Ana Sayfa</a>
            <span class="text-stone-300 dark:text-stone-600">/</span>
            <span class="text-stone-800 dark:text-stone-200">{{ $country->name_tr }} Konsolosluk Rehberi</span>
        </nav>

        {{-- Hero Banner Card --}}
        <div class="relative mt-4 overflow-hidden rounded-3xl border border-stone-200/90 bg-gradient-to-br from-stone-900 via-stone-900 to-emerald-950 p-6 sm:p-10 text-white shadow-xl dark:border-stone-800">
            <div class="pointer-events-none absolute -right-20 -top-20 h-72 w-72 rounded-full bg-[radial-gradient(circle,color-mix(in_srgb,var(--color-emerald-500)_30%,transparent),transparent_70%)]" aria-hidden="true"></div>
            
            <div class="relative z-10 max-w-3xl">
                <div class="inline-flex items-center gap-2 rounded-full border border-white/20 bg-white/10 px-3.5 py-1.5 text-xs font-bold text-emerald-300 backdrop-blur">
                    <span class="text-base" aria-hidden="true">{{ $country->emoji }}</span>
                    <span>T.C. Dışişleri Bakanlığı Temsilcilik Rehberi</span>
                </div>

                <h1 class="mt-4 text-2xl font-bold tracking-tight sm:text-4xl text-white">
                    {{ $country->name_tr }} — Konsolosluk &amp; Resmî İşlem Rehberi
                </h1>

                <p class="mt-3 text-sm sm:text-base leading-relaxed text-stone-300">
                    {{ $country->name_tr }}'daki Türk temsilciliklerinde vekaletname, pasaport, askerlik, noter ve nüfus işlemleri için gerekli evrak listeleri, harç bilgileri, süreler ve resmî randevu rehberi — Türkçe, tek yerde.
                </p>

                {{-- Değer Rozetleri --}}
                <div class="mt-6 flex flex-wrap gap-2.5 sm:gap-3 text-xs font-semibold text-stone-300">
                    <span class="inline-flex items-center gap-1.5 rounded-xl bg-white/10 px-3 py-1.5 backdrop-blur">
                        🏛️ {{ $temsilcilikler->count() }} Temsilcilik
                    </span>
                    <span class="inline-flex items-center gap-1.5 rounded-xl bg-white/10 px-3 py-1.5 backdrop-blur">
                        📑 Güncel Evrak Listeleri
                    </span>
                    <span class="inline-flex items-center gap-1.5 rounded-xl bg-white/10 px-3 py-1.5 backdrop-blur">
                        ⚡ Resmî Randevu Yönlendirmesi
                    </span>
                    <span class="inline-flex items-center gap-1.5 rounded-xl bg-white/10 px-3 py-1.5 backdrop-blur">
                        🇹🇷 %100 Türkçe
                    </span>
                </div>
            </div>
        </div>

        {{-- Yaşam Rehberi Bloğu (Varsa) --}}
        @if ($yasamRehberiVar)
            <div class="mt-6">
                <a href="{{ route('yasam-rehberi.kategoriler', strtolower($country->code)) }}"
                    class="group flex flex-col sm:flex-row sm:items-center justify-between gap-4 rounded-3xl border border-emerald-200/90 bg-gradient-to-r from-emerald-50/80 to-teal-50/80 p-6 shadow-sm transition hover:-translate-y-0.5 hover:border-emerald-300 hover:shadow-md dark:border-emerald-900/40 dark:bg-emerald-950/20">
                    <div class="flex items-start gap-4">
                        <div class="grid h-12 w-12 shrink-0 place-items-center rounded-2xl bg-emerald-700 text-xl text-white shadow-xs">
                            📘
                        </div>
                        <div>
                            <div class="flex items-center gap-2">
                                <h2 class="text-base font-bold text-stone-900 dark:text-stone-50">
                                    {{ $country->name_tr }} Yaşam Rehberi
                                </h2>
                                <span class="rounded-md bg-emerald-700 px-1.5 py-0.5 text-[10px] font-bold text-white uppercase tracking-wider dark:bg-emerald-500 dark:text-stone-950">Gündelik Hayat</span>
                            </div>
                            <p class="mt-1 text-xs sm:text-sm text-stone-600 dark:text-stone-300">
                                Bankacılık, barınma, sağlık, oturum ve ehliyet — {{ $country->name_tr }}'da yaşayan Türkler için kapsamlı rehber.
                            </p>
                        </div>
                    </div>
                    <span class="inline-flex items-center gap-1.5 text-xs font-bold text-emerald-700 group-hover:underline dark:text-emerald-400 shrink-0">
                        Kategorilere Göz At →
                    </span>
                </a>
            </div>
        @endif

        @if ($temsilcilikler->isNotEmpty())
            {{-- Ülke Seçici --}}
            <div class="mt-6">
                <x-rehber.ulke-secici :aktif="$country" />
            </div>
        @endif

        {{-- Temsilcilikler Bölümü --}}
        <div class="mt-10">
            <div class="flex items-end justify-between">
                <div>
                    <span class="text-xs font-bold uppercase tracking-wider text-emerald-700 dark:text-emerald-400">Temsilcilikler</span>
                    <h2 class="mt-1 text-2xl font-bold text-stone-900 dark:text-stone-50">
                        {{ $country->name_tr }} Türk Dış Temsilcilikleri
                    </h2>
                </div>
                <span class="text-xs font-semibold text-stone-500 dark:text-stone-400">
                    {{ $temsilcilikler->count() }} Kayıtlı Kurum
                </span>
            </div>

            @if ($temsilcilikler->isEmpty())
                <div class="mt-6 rounded-3xl border border-dashed border-stone-300 bg-stone-50/50 p-10 text-center dark:border-stone-700 dark:bg-stone-800/30">
                    <span class="mx-auto grid h-14 w-14 place-items-center rounded-2xl bg-stone-200 text-2xl text-stone-600 dark:bg-stone-700 dark:text-stone-300">
                        🏛️
                    </span>
                    <h3 class="mt-4 text-lg font-bold text-stone-800 dark:text-stone-100">Bu ülkenin rehberi henüz hazırlanıyor.</h3>
                    <p class="mx-auto mt-2 max-w-md text-xs sm:text-sm text-stone-500 dark:text-stone-400">
                        Konsolosluk evrak listeleri doğrulanıp eklendiğinde burada listelenecektir.
                    </p>
                    <x-rehber.ulke-secici :aktif="$country" baslik="Şu an hazır olan ülkeler:" class="mt-6 justify-center" />
                </div>
            @else
                <div class="mt-6 grid gap-5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($temsilcilikler as $t)
                        <div class="flex flex-col justify-between rounded-3xl border border-stone-200/90 bg-white p-6 shadow-sm transition hover:-translate-y-1 hover:border-emerald-300 hover:shadow-md dark:border-stone-800 dark:bg-stone-900">
                            <div>
                                <div class="flex items-center justify-between">
                                    <span class="inline-flex items-center gap-1.5 rounded-xl bg-emerald-50 px-2.5 py-1 text-2xs font-bold uppercase tracking-wider text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300">
                                        <span class="h-1.5 w-1.5 rounded-full bg-emerald-600"></span>
                                        {{ $t->turEtiketi() }}
                                    </span>
                                    <span class="text-xl" aria-hidden="true">🏛️</span>
                                </div>

                                <h3 class="mt-4 text-lg font-bold text-stone-900 dark:text-stone-50">
                                    <a href="{{ route('rehber.temsilcilik', [strtolower($country->code), $t->slug]) }}" class="hover:text-emerald-700 dark:hover:text-emerald-400">
                                        {{ $t->ad }}
                                    </a>
                                </h3>

                                <p class="mt-1 flex items-center gap-1 text-xs font-semibold text-stone-500 dark:text-stone-400">
                                    <x-heroicon-o-map-pin class="h-3.5 w-3.5 text-stone-400" />
                                    {{ $t->sehir }}, {{ $country->name_tr }}
                                </p>

                                @if ($t->adres)
                                    <p class="mt-2 text-xs text-stone-600 dark:text-stone-300 line-clamp-2 leading-relaxed">
                                        {{ $t->adres }}
                                    </p>
                                @endif
                            </div>

                            <div class="mt-6 border-t border-stone-100 pt-4 dark:border-stone-800">
                                <div class="flex items-center justify-between">
                                    @if ($t->yayinda_islem_sayisi > 0)
                                        <span class="inline-flex items-center gap-1 text-xs font-bold text-emerald-700 dark:text-emerald-400">
                                            <span>✓</span> {{ $t->yayinda_islem_sayisi }} İşlem Rehberi
                                        </span>
                                    @else
                                        <span class="text-xs font-medium text-stone-500 dark:text-stone-400">
                                            İşlem Rehberi Hazırlanıyor
                                        </span>
                                    @endif

                                    <a href="{{ route('rehber.temsilcilik', [strtolower($country->code), $t->slug]) }}"
                                       class="inline-flex items-center gap-1 rounded-xl bg-emerald-700 px-3.5 py-1.5 text-xs font-bold text-white shadow-xs transition hover:bg-emerald-800 hover:shadow">
                                        Görüntüle →
                                    </a>
                                </div>
                            </div>
                        </div>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Sık Yapılan Konsolosluk İşlemleri Bilgi Kartları --}}
        <div class="mt-14 rounded-3xl border border-stone-200/90 bg-white p-6 sm:p-8 shadow-sm dark:border-stone-800 dark:bg-stone-900">
            <div class="flex items-center gap-2">
                <span class="grid h-8 w-8 place-items-center rounded-xl bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-400 text-sm">
                    📋
                </span>
                <h2 class="text-lg font-bold text-stone-900 dark:text-stone-50">
                    Sık Yapılan Resmî İşlemler
                </h2>
            </div>
            <p class="mt-1 text-xs sm:text-sm text-stone-500 dark:text-stone-400">
                Temsilciliğe gitmeden önce en çok ihtiyaç duyulan işlemler için genel ön hazırlık listesi:
            </p>

            <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                <div class="rounded-2xl border border-stone-100 bg-stone-50/70 p-4 dark:border-stone-800 dark:bg-stone-800/40">
                    <div class="flex items-center gap-2 font-bold text-sm text-stone-800 dark:text-stone-100">
                        <span>📜</span> Vekaletname
                    </div>
                    <p class="mt-1.5 text-xs leading-relaxed text-stone-600 dark:text-stone-300">
                        Gayrimenkul, araç, banka veya dava vekaletnamesi için vekilin T.C. kimlik numarası ve işlem detayları gereklidir.
                    </p>
                </div>

                <div class="rounded-2xl border border-stone-100 bg-stone-50/70 p-4 dark:border-stone-800 dark:bg-stone-800/40">
                    <div class="flex items-center gap-2 font-bold text-sm text-stone-800 dark:text-stone-100">
                        <span>🛂</span> Pasaport Yenileme
                    </div>
                    <p class="mt-1.5 text-xs leading-relaxed text-stone-600 dark:text-stone-300">
                        Mevcut pasaport, son 6 ayda çekilmiş biyometrik fotoğraf ve randevu ile başvuru yapılır.
                    </p>
                </div>

                <div class="rounded-2xl border border-stone-100 bg-stone-50/70 p-4 dark:border-stone-800 dark:bg-stone-800/40">
                    <div class="flex items-center gap-2 font-bold text-sm text-stone-800 dark:text-stone-100">
                        <span>🪖</span> Dövizle Askerlik
                    </div>
                    <p class="mt-1.5 text-xs leading-relaxed text-stone-600 dark:text-stone-300">
                        Yurt dışı çalışma ve oturum sürelerinin belgelenmesi, uzaktan eğitim tamamlama ve harç ödemesi gerektirir.
                    </p>
                </div>

                <div class="rounded-2xl border border-stone-100 bg-stone-50/70 p-4 dark:border-stone-800 dark:bg-stone-800/40">
                    <div class="flex items-center gap-2 font-bold text-sm text-stone-800 dark:text-stone-100">
                        <span>💍</span> Evlilik &amp; Nüfus
                    </div>
                    <p class="mt-1.5 text-xs leading-relaxed text-stone-600 dark:text-stone-300">
                        Yabancı yerel makamlardan alınan uluslararası evlenme belgesi veya doğum kayıtlarının Türk nüfus kütüğüne tescili.
                    </p>
                </div>

                <div class="rounded-2xl border border-stone-100 bg-stone-50/70 p-4 dark:border-stone-800 dark:bg-stone-800/40">
                    <div class="flex items-center gap-2 font-bold text-sm text-stone-800 dark:text-stone-100">
                        <span>📑</span> Apostil &amp; Belge Tasdiki
                    </div>
                    <p class="mt-1.5 text-xs leading-relaxed text-stone-600 dark:text-stone-300">
                        Yabancı resmî belgelerin Türkiye'de geçerli olması için apostil şerhi ve yeminli Türkçe tercüme tasdiki.
                    </p>
                </div>

                <div class="rounded-2xl border border-stone-100 bg-stone-50/70 p-4 dark:border-stone-800 dark:bg-stone-800/40">
                    <div class="flex items-center gap-2 font-bold text-sm text-stone-800 dark:text-stone-100">
                        <span>⚖️</span> Noter &amp; İmza Tasdiki
                    </div>
                    <p class="mt-1.5 text-xs leading-relaxed text-stone-600 dark:text-stone-300">
                        İmza sirküleri, muvafakatname, taahhütname ve Türkçe belge onayları konsolosluk noter servisinde düzenlenir.
                    </p>
                </div>
            </div>

            {{-- Resmî Portallar --}}
            <div class="mt-8 flex flex-col sm:flex-row items-center justify-between gap-4 rounded-2xl bg-stone-50 p-4 dark:bg-stone-800/60 border border-stone-200/80 dark:border-stone-700/80">
                <div class="flex items-center gap-3">
                    <span class="text-2xl">🌐</span>
                    <div>
                        <div class="text-xs font-bold text-stone-800 dark:text-stone-100">T.C. Dışişleri Bakanlığı e-Konsolosluk Randevu Portalı</div>
                        <div class="text-2xs text-stone-500 dark:text-stone-400">Resmî randevu almak ve harç yatırmak için konsolosluk.gov.tr adresini kullanın.</div>
                    </div>
                </div>
                <a href="https://www.konsolosluk.gov.tr" target="_blank" rel="noopener nofollow"
                   class="inline-flex items-center gap-1 rounded-xl bg-stone-900 px-4 py-2 text-xs font-bold text-white transition hover:bg-stone-800 dark:bg-stone-100 dark:text-stone-900 dark:hover:bg-white shrink-0">
                    konsolosluk.gov.tr ↗
                </a>
            </div>
        </div>

        {{-- Bilgilendirme ve Uyarı Notu --}}
        <div class="mt-8 rounded-2xl border border-stone-200/80 bg-stone-50/60 p-4 text-xs leading-relaxed text-stone-500 dark:border-stone-800 dark:bg-stone-900/40 dark:text-stone-400">
            <span class="font-bold text-stone-700 dark:text-stone-300">⚠️ Bilgilendirme Notu:</span> Bu sayfadaki evrak listeleri ve rehberler yurt dışındaki vatandaşlarımızın işlemlerini kolaylaştırmak amacıyla derlenmiştir. Harç tutarları, randevu saatleri ve evrak gereksinimleri mevzuat güncellemelerine göre değişebilir. İşleme gitmeden önce ilgili temsilciliğin resmî web sitesinden bilgileri teyit etmeniz önerilir.
        </div>
    </div>

    <x-json-ld type="BreadcrumbList" :data="[
        'itemListElement' => [
            ['@type' => 'ListItem', 'position' => 1, 'name' => 'Ana sayfa', 'item' => route('home')],
            ['@type' => 'ListItem', 'position' => 2, 'name' => $country->name_tr.' Rehberi', 'item' => route('rehber.ulke', strtolower($country->code))],
        ],
    ]" />
</x-layouts.app>
