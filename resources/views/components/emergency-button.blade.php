@props(['categories', 'countries', 'defaultCountry' => '', 'city' => null])

{{-- KAPI YOK — BİLEREK.

     Burada eskiden `@if ($categories->isNotEmpty())` vardı ve TÜM bileşeni
     sarıyordu: acil düğmesi, 112, konsolosluk çağrı merkezi, konum kopyalama,
     hepsi "acil-yardim" kategorisinin aktif alt kategorisi olmasına bağlıydı.

     Yani biri panelden o kategorileri pasife çekseydi, sitenin acil numarası
     da sessizce kaybolacaktı. Hayat kurtaran katman (1 ve 2), pazaryeri
     envanterine bağlanamaz. Kapı kaldırıldı; artık YALNIZ 3. katman
     (ilan bağlantıları) koşullu. --}}

<div
    x-data="{
        open: false,
        ulke: @js($defaultCountry),
        numaralar: @js(\App\Support\AcilNumaralar::harita()),
        rehberliUlkeler: @js(collect($countries)->filter(fn ($u) => !empty($u->rehber_var))->pluck('code')->values()),
        konsolosluklar: @js(\App\Support\AcilNumaralar::konsoloslukYerelHaritasi()),
        sehir: @js($city ?? ''),
        konumDurumu: '',
        konumKoordinat: '',

        ac() {
            this.open = true;
            this.$nextTick(() => this.$refs.kapat?.focus());
        },

        /* Kapanışta odak tetikleyiciye DÖNER: klavye/ekran okuyucu kullanıcısı
           modal kapanınca sayfanın başına düşmez, kaldığı yerden devam eder. */
        kapat() {
            this.open = false;
            this.$nextTick(() => this.$refs.tetik?.focus());
        },

        /* Seçili ülkenin acil numarası; ülke seçili değilse null. */
        acilNumara() {
            return (this.ulke && this.numaralar[this.ulke]) ? this.numaralar[this.ulke] : null;
        },

        /* Yardım kategorisi bağlantısı — ülke VE şehirle daraltılmış.
           `URLSearchParams` kaçışı kendisi yapar; şehir adında boşluk ya da
           Türkçe harf olduğunda elle birleştirme bozulurdu. */
        kategoriBaglantisi(temel) {
            const p = new URLSearchParams();
            if (this.ulke) p.set('ulke', this.ulke);
            if (this.sehir) p.set('sehir', this.sehir);
            const q = p.toString();

            return q ? temel + '?' + q : temel;
        },

        /* Seçili ülkenin yerel konsolosluk erişim numarası; yoksa null.
           Yalnız bir avuç ülkede var, o yüzden her yerde null kontrolü şart. */
        yerelKonsolosluk() {
            return (this.ulke && this.konsolosluklar[this.ulke]) ? this.konsolosluklar[this.ulke] : null;
        },

        /* Genel numaradan FARKLI olan adlandırılmış hatlar. Aynı olanı
           tekrar göstermek ekranı şişirir ve seçim yaptırır — acil durumda
           ikisi de zarar. */
        ekHatlar() {
            const n = this.acilNumara();
            if (! n) return [];
            return [
                { ad: 'Polis', no: n.polis },
                { ad: 'Ambulans', no: n.ambulans },
                { ad: 'İtfaiye', no: n.itfaiye },
            ].filter((h) => h.no && h.no !== n.genel);
        },

        /* Yabancı ülkede adres tarif edememe sorununun ucuz çözümü: konumu
           EKRANDA okunacak biçimde gösterir, ayrıca panoya yazmayı dener.

           ÜÇ ÖLÇÜLEN HATA BURADA DÜZELTİLDİ:

           1. Sonuç 8 saniye sonra kendini siliyordu. Telefonda operatörle
              konuşurken 8 saniye hiçbir şeydir; koordinat artık SİLİNMİYOR.
           2. Pano birincil yoldu, ama `clipboard.writeText` burada kullanıcı
              dokunuşundan kopmuş bir bağlamda (geolocation geri çağrısı)
              çalışıyor ve iOS Safari bunu reddediyor. Yani iPhone'da her
              seferinde yedek dala düşüyordu. Artık EKRAN birincil, pano ikramiye.
           3. Her hata, izin verilmedi diyordu. GPS zaman aşımı da aynı mesajı
              veriyor ve kullanıcı zaten verdiği izni aramaya ayar ekranına
              gidiyordu. `code` artık ayrıştırılıyor.

           NOT — BURADA ÇİFT TIRNAK KULLANMA. Burası bir HTML özniteliğinin
           içi; tek bir çift tırnak özniteliği erkenden kapatır ve Alpine
           ifadesi sözdizimi hatasıyla ölür. Panelin TAMAMI çalışmaz hâle
           gelir: ülke seçilemez, numara gösterilemez, modal açılamaz.

           Bu hata arka arkaya İKİ KEZ yapıldı — ikincisi tam da birincisini
           anlatan uyarı yazılırken. Ve 2000+ testin hepsi ikisinde de yeşil
           kaldı, çünkü testler basılan metne bakar, JS'in koştuğuna değil.
           Bekçi: AcilMenusuTest::test_alpine_ifadesi_html_ozniteligini_erken_kapatmiyor

           4 ondalık ~11 metre; acil çağrı için fazlasıyla yeterli ve 6 hane
           telefonda okunamayacak kadar uzundu.

           `maximumAge`: 30 sn içinde alınmış konum varsa ANINDA döner. */
        konumuKopyala() {
            if (! navigator.geolocation) { this.konumDurumu = 'Bu cihaz konum vermiyor'; return; }
            this.konumDurumu = 'Konum alınıyor…';
            this.konumKoordinat = '';
            navigator.geolocation.getCurrentPosition(
                async (p) => {
                    const e = p.coords.latitude.toFixed(4), b = p.coords.longitude.toFixed(4);
                    this.konumKoordinat = e + ', ' + b;
                    try {
                        await navigator.clipboard.writeText(this.konumKoordinat + ' — https://maps.google.com/?q=' + e + ',' + b);
                        this.konumDurumu = 'Panoya da kopyalandı';
                    } catch (_) {
                        this.konumDurumu = 'Aşağıdaki koordinatı okuyabilirsin';
                    }
                },
                (hata) => {
                    this.konumDurumu = hata.code === 1 ? 'Konum izni verilmedi'
                        : hata.code === 3 ? 'Zaman aşımı — tekrar dene'
                        : 'Konum şu an alınamıyor';
                },
                { enableHighAccuracy: true, timeout: 8000, maximumAge: 30000 }
            );
        },

        /* Minimal odak tuzağı — yeni bağımlılık eklemeden Tab'ı panel içinde
           döndürür (app.js'teki Cmd+K paneliyle aynı desen). */
        odagiTut(event) {
            const odaklanabilir = this.$refs.panel?.querySelectorAll('a[href], button:not([disabled]), select');
            if (! odaklanabilir || ! odaklanabilir.length) return;
            const ilk = odaklanabilir[0], son = odaklanabilir[odaklanabilir.length - 1];
            if (event.shiftKey && document.activeElement === ilk) { event.preventDefault(); son.focus(); }
            else if (! event.shiftKey && document.activeElement === son) { event.preventDefault(); ilk.focus(); }
        },
    }"
    @keydown.escape.window="open && kapat()"
    {{-- Mobil misafirde başlıktaki tetikleyici gizli (bkz.
         layouts/app.blade.php) — o durumda x-mobile-tab-bar'daki yelpazenin
         "Acil" öğesi bu olayı fırlatarak AYNI modalı açar. Numara/konsolosluk
         mantığı tek yerde kalsın diye modal ÇOĞALTILMADI. --}}
    @acil-yardim-ac.window="ac()"
    {{-- Modal açıkken arkadaki sayfa kaymasın: telefonda parmak panelin
         dışına taştığında sayfa kayıyor, panel yerinde kalıyordu. --}}
    x-effect="document.body.style.overflow = open ? 'hidden' : ''"
>
    {{-- ACİL DÜĞMESİ — ÖNCE ANLAŞILSIN, SONRA TÜRK OLSUN.

         Önceki tasarım ay-yıldızlıydı ve sahip haklı olarak "kimse bunun
         acil durum düğmesi olduğunu anlamaz" dedi. İki sebebi vardı:

         1. ETİKET MOBİLDE GİZLİYDİ (`hidden sm:inline`). Telefonda geriye
            yalnız ikon kalıyordu — ve ay-yıldız "Türk" der, "acil" demez.
            Bir düğmenin ne yaptığını anlatan en güçlü şey kelimenin
            kendisidir; artık her ekranda "Acil" yazıyor.
         2. Bayrak motifi kimlik anlatır, ACİLİYET anlatmaz. Ünlem üçgeni
            kültürden bağımsız okunur; tehlike/dikkat için evrensel işaret.

         Kırmızı kaldı ve şanslı bir örtüşme: hem acil rengi hem bayrak
         kırmızısı (#E30A17). Türklüğü renk taşıyor, anlaşılırlığı ikon ve
         kelime taşıyor. Ay-yıldız modal başlığında duruyor — orada bağlam
         zaten kurulmuş oluyor ("Acil Yardım" başlığının yanında). --}}
    <button
        type="button"
        x-ref="tetik"
        @click="ac()"
        class="inline-flex h-9 shrink-0 items-center gap-1.5 whitespace-nowrap rounded-full border border-red-200/90 bg-red-50/90 px-3 text-xs font-bold text-red-700 shadow-2xs transition hover:-translate-y-0.5 hover:border-red-500 hover:bg-red-600 hover:text-white hover:shadow-xs focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-700 focus-visible:ring-offset-2 dark:border-red-900/60 dark:bg-red-950/40 dark:text-red-300 dark:hover:bg-red-600 dark:hover:text-white"
        aria-label="Acil yardım — hızlı erişim"
        title="Acil yardım — hızlı erişim"
    >
        <span class="relative flex h-2 w-2 shrink-0">
            <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-red-400 opacity-75"></span>
            <span class="relative inline-flex h-2 w-2 rounded-full bg-red-500"></span>
        </span>
        <x-heroicon-s-exclamation-triangle class="h-3.5 w-3.5 shrink-0" />
        <span>Acil</span>
    </button>

    {{-- x-teleport: modal, header'ın backdrop-blur'unun (position:fixed
    için yeni bir containing block oluşturur) İÇİNDEN kaçıp <body>
    sonuna taşınır — aksi halde "fixed inset-0" header'ın küçük
    yüksekliğine sıkışıp modal neredeyse tamamen ekran dışına taşar. --}}
    <template x-teleport="body">
        <div
            x-show="open"
            x-transition.opacity.duration.200ms
            class="fixed inset-0 z-50 flex items-end justify-center bg-stone-900/60 sm:items-center sm:p-6"
            @click.self="kapat()"
            role="dialog"
            aria-modal="true"
            aria-labelledby="emergency-title"
            x-cloak
        >
            {{-- YÜKSEKLİK SINIRI + İÇ KAYDIRMA — bu panelin en kritik iki satırı.

                 ÖLÇÜLEN HATA (sahibin telefonu, 2026-08-12): panelin yükseklik
                 sınırı yoktu. Kapsayıcı `items-end` olduğu için içerik ekrandan
                 uzun olunca panel AŞAĞIYA değil YUKARIYA taşıyor; başlık ve
                 "Kapat" düğmesi ekranın üstünden dışarı çıkıyordu. Kapsayıcı
                 `fixed inset-0` olduğu için oraya kaydırmak da mümkün değildi
                 ve panel tüm ekranı kapladığından arkaplana dokunup kapatacak
                 boşluk da kalmıyordu. Yani panel açıldığında ÇIKIŞ YOKTU.

                 Çözüm üç kapıyı birden geri açar:
                   · max-height  → üstte her zaman arkaplan görünür (dokun-kapat)
                   · flex-col    → başlık ve alt not sabit, YALNIZ orta kısım kayar
                   · shrink-0    → "Kapat" düğmesi hiçbir içerik boyunda kaybolmaz

                 `dvh` mobil tarayıcıda adres çubuğu gizlenince değişen gerçek
                 yüksekliği verir; `vh` desteklemeyen tarayıcı için yedek olarak
                 önce yazıldı (CSS kaskadı: sonraki geçerli kural kazanır). --}}
            <div
                x-show="open"
                x-transition.duration.200ms
                x-ref="panel"
                @keydown.tab="odagiTut($event)"
                style="max-height: 95vh; max-height: 95dvh;"
                class="flex w-full max-w-md flex-col overflow-hidden rounded-t-2xl bg-white shadow-2xl ring-1 ring-stone-200 sm:rounded-2xl dark:bg-stone-900 dark:ring-stone-800"
            >
                {{-- BAŞLIK — shrink-0: asla kaydırma alanına girmez --}}
                <div class="shrink-0 border-b border-rose-100 bg-rose-50 dark:border-rose-900/40 dark:bg-rose-950/30">
                    {{-- TUTAMAK ÇUBUĞU KALDIRILDI (12px).

                         "Aşağıdan açılan panel" işareti olarak duruyordu, ama
                         panelin sağ üstünde zaten görünür bir X var ve kapatma
                         üç yoldan mümkün (X, arkaplana dokunma, Escape).
                         Süslü bir işaret için 12px, panelin tek ekrana
                         sığmasından daha değerli değil. --}}
                    <div class="flex items-start justify-between gap-4 px-5 py-2.5">
                        <div class="flex items-center gap-3">
                            <span class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-[#E30A17] text-white">
                                <x-ay-yildiz class="h-5 w-5" />
                            </span>
                            {{-- Alt satır ("Bulunduğun ülkedeki acil numaralar")
                                 KALDIRILDI: panelin tek ekrana sığması için 20px
                                 gerekiyordu ve o satır zaten gereksizdi — hemen
                                 altında "ACİL SERVİS" etiketi ve kırmızı çağrı
                                 düğmesi duruyor, panelin ne olduğu tartışmasız. --}}
                            <h2 id="emergency-title" class="text-base font-bold text-rose-900 dark:text-rose-200">Acil Yardım</h2>
                        </div>
                        <button
                            type="button"
                            x-ref="kapat"
                            @click="kapat()"
                            {{-- p-3 → 44×44 dokunma hedefi. Panel açılınca odak
                                 BURAYA geliyor ve sağ üst köşe tek elle en zor
                                 erişilen bölge; 32px'lik hedef panik hâlinde,
                                 titreyen parmakla ıskalanıyordu. --}}
                            class="shrink-0 rounded-full p-3 text-rose-700 transition hover:bg-rose-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-700 dark:text-rose-300 dark:hover:bg-rose-900/40"
                            aria-label="Kapat"
                        >
                            <x-heroicon-o-x-mark class="h-5 w-5" />
                        </button>
                    </div>
                </div>

                {{-- GÖVDE — tek kayan bölge --}}
                <div class="flex-1 overflow-y-auto overscroll-contain">
                    {{-- ÜÇ KATMAN, ACİLİYET SIRASINA GÖRE.

                         Eskiden modal yalnız ilan kategorisi linkleri
                         gösteriyordu. Gerçek bir acil durumda kimse ilan
                         kategorisine tıklamaz; üstelik envanter boşken o
                         linkler boş sayfaya götürüyordu.

                         Sıra hayatî olandan başlar:
                           1. Bulunduğun ülkenin acil numaraları (tek dokunuş)
                           2. Konsolosluk — Türk'e özgü olan katman
                           3. Türkçe konuşan yardım — YALNIZ gerçekten varsa
                    --}}

                    {{-- KATMAN 1 — ÜLKENİN ACİL NUMARALARI --}}
                    <div class="border-b border-stone-100 px-5 py-2.5 dark:border-stone-800">
                        {{-- Ekran okuyucuya haber ver: ülke seçilince aşağıda
                             yeni bir çağrı düğmesi BELİRİYOR. Görsel kullanıcı
                             bunu görüyor, kör kullanıcı tek tek gezinmeden
                             bilemiyordu. --}}
                        <p class="sr-only" role="status" aria-live="polite"
                           x-text="acilNumara() ? 'Acil numara hazır: ' + acilNumara().genel_etiket + ' ' + acilNumara().genel : ''"></p>

                        {{-- ETİKET VE ÜLKE SEÇİCİ AYNI SATIRDA.

                             Seçici eskiden kendi bölümündeydi ve tek başına
                             69px yer kaplıyordu; panelin tamamı tek ekrana
                             sığmıyordu. Etiket satırı zaten vardı, seçici
                             oraya taşındı — iki blok bire indi. Seçicinin
                             dokunma hedefi `min-h-11` ile 44px'de sabit,
                             görünür etiket yerini ekran okuyucu etiketi aldı
                             (seçilen ülke adı zaten seçicinin üstünde yazıyor).
                        --}}
                        <div class="flex items-center gap-2">
                            <p class="shrink-0 text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">Acil servis</p>
                            @if ($countries->isNotEmpty())
                                <label for="acil-ulke" class="sr-only">Hangi ülkedesin?</label>
                                <select
                                    id="acil-ulke"
                                    x-model="ulke"
                                    class="ml-auto min-h-9 max-w-[62%] rounded-lg border-stone-400 bg-stone-50 py-1 text-xs text-stone-700 focus:border-rose-500 focus:ring-rose-500 dark:border-stone-500 dark:bg-stone-800 dark:text-stone-200"
                                >
                                    {{-- Eskiden "Tüm ülkeler" yazıyordu. Bir acil
                                         panelinde anlamsız: insan tek bir ülkede
                                         olur, hepsinde değil. --}}
                                    <option value="">Ülkeni seç</option>
                                    @foreach ($countries as $country)
                                        <option value="{{ $country->code }}">{{ $country->emoji }} {{ $country->name_tr }}</option>
                                    @endforeach
                                </select>
                            @endif
                        </div>

                        <template x-if="acilNumara()">
                            <div>

                                {{-- BİRİNCİL HAT — tek dokunuş, en büyük hedef.

                                     ÜSTTEKİ ETİKET HAYATÎ. Eskiden düğmede yalnız
                                     rakam vardı; Almanya'da panelde görünen tek
                                     adlandırılmış hat "Polis 110" oluyor,
                                     "Ambulans" kelimesi ekranda HİÇ geçmiyordu.
                                     Panik hâlindeki insan kelime tarar, rakam
                                     taramaz — aradığı kelimeyi bulamayınca ya
                                     donar ya yanlış hattı arar. --}}
                                <a :href="'tel:' + acilNumara().genel"
                                   :aria-label="acilNumara().genel + ' ara — ' + acilNumara().genel_etiket"
                                   class="mt-1.5 flex items-center justify-center gap-2.5 rounded-xl bg-[#E30A17] px-4 py-3 text-white shadow-sm transition hover:bg-[#C10914] focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-700 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-stone-900">
                                    <x-heroicon-s-phone class="h-6 w-6 shrink-0" />
                                    <span class="text-center leading-tight">
                                        <span class="block text-xs font-semibold uppercase tracking-wide text-white/90" x-text="acilNumara().genel_etiket"></span>
                                        <span class="block text-2xl font-bold" x-text="acilNumara().genel"></span>
                                    </span>
                                </a>

                                {{-- ADLANDIRILMIŞ HATLAR — okunacak metin değil,
                                     ARANAN düğme. Yalnız genel numaradan farklı
                                     olanlar; aynısını iki kez göstermek acil
                                     durumda gereksiz seçim yaptırır. --}}
                                <template x-if="ekHatlar().length">
                                    <div class="mt-1.5 grid grid-cols-3 gap-1.5">
                                        <template x-for="hat in ekHatlar()" :key="hat.ad">
                                            <a :href="'tel:' + hat.no"
                                               :aria-label="hat.ad + ' ' + hat.no + ' ara'"
                                               class="flex min-h-11 min-w-0 flex-col items-center justify-center rounded-xl border border-stone-400 px-2 py-1.5 transition hover:bg-stone-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-600 dark:border-stone-500 dark:hover:bg-stone-800">
                                                {{-- rem tabanlı: tarayıcı yazı boyutunu
                                                     büyüten kullanıcı da fayda görsün.
                                                     `break-words`: "AMBULANS" 320px'de
                                                     kırpılmak yerine sarılsın. --}}
                                                <span class="break-words text-center text-[0.6875rem] font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400" x-text="hat.ad"></span>
                                                <span class="text-base font-bold text-stone-800 dark:text-stone-100" x-text="hat.no"></span>
                                            </a>
                                        </template>
                                    </div>
                                </template>

                                {{-- Not YALNIZ artık bilgi taşıyorsa basılır;
                                     düğmelerin söylediğini tekrar etmez. --}}
                                <template x-if="acilNumara().not">
                                    <p class="mt-2 text-xs text-stone-600 dark:text-stone-400" x-text="acilNumara().not"></p>
                                </template>
                            </div>
                        </template>

                        {{-- ÜLKE BİLİNMİYORSA DA DÜĞME BASILIR — paragraf değil.

                             Eskiden burada küçük punto bir cümle ve cümlenin
                             İÇİNE gömülü bir 112 bağlantısı vardı. Bu durum
                             sanıldığından sık: misafir + çözümlenemeyen IP, VPN,
                             ya da `config/acil.php`'de karşılığı olmayan bir ülke.
                             Hayatının en kötü anındaki insana, çağrı düğmesinin
                             olması gereken yerde okuması gereken bir metin
                             çıkıyordu. Artık aynı yerde aynı düğme var. --}}
                        <template x-if="! acilNumara()">
                            <div>
                                <a href="tel:112"
                                   aria-label="112 ara — Avrupa geneli acil numara"
                                   class="mt-1.5 flex items-center justify-center gap-2.5 rounded-xl bg-[#E30A17] px-4 py-3 text-white shadow-sm transition hover:bg-[#C10914] focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-700 focus-visible:ring-offset-2 dark:focus-visible:ring-offset-stone-900">
                                    <x-heroicon-s-phone class="h-6 w-6 shrink-0" />
                                    <span class="text-center leading-tight">
                                        <span class="block text-xs font-semibold uppercase tracking-wide text-white/90">Avrupa geneli</span>
                                        <span class="block text-2xl font-bold">112</span>
                                    </span>
                                </a>
                                <p class="mt-2 text-xs text-stone-600 dark:text-stone-400">
                                    Ülkeni seçersen o ülkenin kendi numaralarını gösteririz. 112 Avrupa'nın
                                    tamamında ve pek çok ülkede yönlendirir — ama her ülkede çalışmaz.
                                </p>
                            </div>
                        </template>
                    </div>

                    {{-- KATMAN 2 — KONSOLOSLUK (Türk'e özgü olan) --}}
                    @php $ccm = \App\Support\AcilNumaralar::konsoloslukCagriMerkezi(); @endphp
                    <div class="border-b border-stone-100 px-5 py-2.5 dark:border-stone-800">
                        <p class="text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">Konsolosluk</p>

                        {{-- YEREL ERİŞİM NUMARASI — varsa birincil.

                             +90 312 dünyanın her yerinden çalışır ama yurt
                             dışından ULUSLARARASI tarifeden ücretlendirilir; bu
                             panelin kitlesi ise tam olarak yurt dışındakiler.
                             Dışişleri aynı çağrı merkezi için bazı ülkelerde
                             yerel numara yayımlıyor.

                             Etiket `tarife`den geliyor ve muhafazakâr: kaynak
                             açıkça "ücretsiz" demiyorsa "yerel tarife" yazar.
                             Ücretsiz olmayan bir hatta ücretsiz demek, acil
                             durumdaki birine verilmiş yanlış bir sözdür.

                             Ülke adına EK GETİRİLMİYOR ("Almanya'dan" gibi):
                             Türkçe çekim her ülke adında doğru çalışmıyor
                             (bu depoda daha önce ölçülmüş bir tuzak). --}}
                        <template x-if="yerelKonsolosluk()">
                            <a :href="'tel:' + yerelKonsolosluk().numara"
                               :aria-label="'Konsolosluk çağrı merkezini ara — ' + yerelKonsolosluk().gosterim"
                               class="mt-2 flex items-center gap-3 rounded-xl border border-emerald-300 bg-emerald-50 px-3.5 py-2.5 transition hover:bg-emerald-100 focus:outline-none focus-visible:ring-2 focus-visible:ring-emerald-700 dark:border-emerald-700 dark:bg-emerald-950/30 dark:hover:bg-emerald-950/50">
                                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-white text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">
                                    <x-ay-yildiz class="h-5 w-5" />
                                </span>
                                <span class="min-w-0">
                                    <span class="block font-bold text-stone-800 dark:text-stone-100" x-text="yerelKonsolosluk().gosterim"></span>
                                    <span class="block text-xs text-stone-600 dark:text-stone-400"
                                          x-text="yerelKonsolosluk().tarife === 'ucretsiz'
                                              ? 'Bulunduğun ülkeden ücretsiz — 7/24'
                                              : 'Bulunduğun ülkeden yerel tarife — 7/24'"></span>
                                </span>
                            </a>
                        </template>

                        {{-- ULUSLARARASI HAT — yerel numara varken de duruyor,
                             ama ikincil. Yerel hat çalışmazsa çıkış yolu bu;
                             kaldırmak tek noktalı bağımlılık yaratırdı. --}}
                        <a href="tel:{{ $ccm['numara'] }}"
                           :class="yerelKonsolosluk()
                               ? 'mt-2 flex items-center gap-3 rounded-xl px-3.5 py-2 text-sm transition hover:bg-stone-50 dark:hover:bg-stone-800'
                               : 'mt-2 flex items-center gap-3 rounded-xl border border-stone-400 px-3.5 py-2.5 transition hover:border-emerald-300 hover:bg-emerald-50 dark:border-stone-500 dark:hover:border-emerald-700 dark:hover:bg-emerald-950/20'">
                            <template x-if="! yerelKonsolosluk()">
                                <span class="grid h-9 w-9 shrink-0 place-items-center rounded-lg bg-emerald-50 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-300">
                                    <x-ay-yildiz class="h-5 w-5" />
                                </span>
                            </template>
                            <span class="min-w-0">
                                <span class="block font-bold text-stone-800 dark:text-stone-100">{{ $ccm['gosterim'] }}</span>
                                <span class="block text-xs text-stone-600 dark:text-stone-400"
                                      x-text="yerelKonsolosluk()
                                          ? 'Aynı merkez, dünyanın her yerinden (uluslararası tarife)'
                                          : @js($ccm['aciklama'])"></span>
                            </span>
                        </a>
                        {{-- Rehber bağlantısı YALNIZ rehberi olan ülkelerde:
                             olmayan bir ülkede bağlantı vermek, acil durumdaki
                             kişiyi boş sayfaya götürmek olurdu. --}}
                        <template x-if="rehberliUlkeler.includes(ulke)">
                            <a :href="'/' + ulke.toLowerCase()"
                               class="mt-1.5 flex min-h-9 items-center justify-between gap-3 rounded-xl px-3.5 py-1.5 text-sm font-semibold text-emerald-700 transition hover:bg-emerald-50 dark:text-emerald-400 dark:hover:bg-emerald-950/20">
                                Ülkendeki temsilcilikler ve işlemler
                                <x-heroicon-o-arrow-right class="h-4 w-4 shrink-0" />
                            </a>
                        </template>
                    </div>

                    {{-- KONUM — yabancı ülkede adres tarif edememek gerçek bir
                         sorun; koordinatı kopyalayıp okumak ya da yapıştırmak
                         en hızlı çözüm. İzin istenerek yapılır. --}}
                    <div class="{{ $categories->isNotEmpty() ? 'border-b border-stone-100 dark:border-stone-800' : '' }} px-5 py-2.5">
                        {{-- Düğmenin ADI SABİT. Eskiden durum metni etiketin
                             yerine geçiyordu ("Konum alınıyor…"), yani düğmeye
                             Tab'layan kör kullanıcı düğmenin NE YAPTIĞINI
                             öğrenemiyordu. Durum artık ayrı canlı bölgede. --}}
                        <button type="button" @click="konumuKopyala()"
                                class="flex min-h-11 w-full items-center gap-2 rounded-xl border border-stone-400 px-3 py-2 text-sm font-semibold text-stone-700 transition hover:bg-stone-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-stone-600 dark:border-stone-500 dark:text-stone-300 dark:hover:bg-stone-800">
                            <x-heroicon-o-map-pin class="h-5 w-5 shrink-0" />
                            Konumumu kopyala
                        </button>

                        <p role="status" aria-live="polite" :class="konumDurumu ? 'mt-1.5' : ''" class="text-xs text-stone-600 dark:text-stone-400" x-text="konumDurumu"></p>

                        {{-- Koordinat DÜĞMENİN DIŞINDA ve SİLİNMİYOR: pano
                             yazımı başarısız olduğunda (iOS'ta kural) yedek
                             bir `<button>` metninin içine düşüyordu — mobilde
                             düğme metni basılı tutularak seçilemez, yani
                             yedeğin var olma sebebi ortadan kalkıyordu. --}}
                        <template x-if="konumKoordinat">
                            <p class="mt-1 select-all text-lg font-bold tracking-tight text-stone-800 dark:text-stone-100" x-text="konumKoordinat"></p>
                        </template>
                    </div>

                    {{-- KATMAN 3 — TÜRKÇE KONUŞAN YARDIM.
                         Bölümün tamamı koşullu: ilanı olmayan kategori hiç
                         basılmaz, hiçbiri yoksa başlık da açılmaz. --}}
                    @if ($categories->isNotEmpty())
                        <div class="px-5 py-2.5">
                            <div class="flex items-baseline justify-between gap-2">
                                <p class="text-xs font-semibold uppercase tracking-wide text-stone-500 dark:text-stone-400">Türkçe konuşan yardım ara</p>
                                {{-- Şehir adı ROZET değil sade metin ve yalnız
                                     biliniyorsa basılır: aramanın nereyi
                                     kapsadığını söylemek, sonuç boş çıktığında
                                     kullanıcının sebebi anlamasını sağlar.
                                     Ada Türkçe ek GETİRİLMİYOR ("Berlin'de"
                                     gibi) — çekim her şehir adında doğru
                                     çalışmıyor, bu depoda ölçülmüş bir tuzak. --}}
                                @if (filled($city))
                                    <span class="inline-flex min-w-0 items-center gap-1 text-xs text-stone-600 dark:text-stone-400">
                                        <x-heroicon-o-map-pin class="h-3.5 w-3.5 shrink-0" />
                                        <span class="truncate">{{ $city }}</span>
                                    </span>
                                @endif
                            </div>

                            {{-- KÜÇÜK ÇİPLER, BÜYÜK SATIR DEĞİL — bilinçli.

                                 Bu bölüm bir kez BÜYÜK dokunulabilir satırlar
                                 hâlindeydi ve konsolosluk kartıyla aynı görsel
                                 ağırlıktaydı; acil durumdaki kişi hayatî katmanla
                                 dizin aramasını ayırt edemiyordu. Şimdi ikincil:
                                 hayat kurtaran numaralar üstte ve büyük kalıyor,
                                 bunlar altta ve küçük.

                                 İki sütun, dört madde: "Cenaze Hizmetleri" dört
                                 sütuna sığmıyor, ikili ızgarada rahat duruyor. --}}
                            {{-- `auto-rows-fr`: "Cenaze Hizmetleri" iki satıra
                                 sarıyor ve o satırı uzatıyordu; eşit yükseklik
                                 olmadan ızgara tırtıklı görünüyor. --}}
                            <div class="mt-1.5 grid auto-rows-fr grid-cols-2 gap-1.5">
                                @foreach ($categories as $cat)
                                    <a
                                        :href="kategoriBaglantisi('{{ route('listings.category', $cat->slug) }}')"
                                        class="flex min-h-11 min-w-0 items-center gap-2 rounded-xl border border-stone-300 px-3 py-1.5 transition hover:border-rose-300 hover:bg-rose-50 focus:outline-none focus-visible:ring-2 focus-visible:ring-rose-600 dark:border-stone-600 dark:hover:border-rose-700 dark:hover:bg-rose-950/20"
                                    >
                                        <span class="shrink-0 text-lg leading-none" aria-hidden="true">{{ $cat->icon }}</span>
                                        <span class="min-w-0 break-words text-xs font-semibold leading-tight text-stone-800 dark:text-stone-100">{{ $cat->name }}</span>
                                    </a>
                                @endforeach
                            </div>

                            {{-- DÜRÜST BEKLENTİ ARTIK BAŞLIKTA, AYRI SATIRDA DEĞİL.

                                 Burada "henüz kayıtlı kimse olmayabilir" diye bir
                                 satır vardı. Sahibin telefonunda ÖLÇÜLDÜ: uygulama
                                 içi tarayıcının kendi başlığı görünür alanı
                                 kısalttığı için o satır alt bandın altında YARIM
                                 kalıyordu — kesik bir cümle, bilgi vermek yerine
                                 arıza gibi görünüyor.

                                 Kaldırıldı ama dürüstlük kaybolmadı: bölüm başlığı
                                 artık "ara" diyor. "Yardım" bir vaat, "yardım ara"
                                 bir eylem — sonuç garantisi vermiyor. Sonucun
                                 kendisi zaten kategori sayfasında dürüst
                                 ("0 ilan bulundu" + boş hâl). --}}
                        </div>
                    @endif
                </div>

                {{-- ALT NOT — shrink-0: kaydırmadan bağımsız, hep görünür --}}
                <div class="shrink-0 border-t border-stone-100 bg-stone-50 px-5 py-1.5 text-center text-xs text-stone-500 dark:border-stone-800 dark:bg-stone-800 dark:text-stone-400">
                    Nisoya acil servis değildir — önce resmî numarayı ara.
                </div>
            </div>
        </div>
    </template>
</div>
