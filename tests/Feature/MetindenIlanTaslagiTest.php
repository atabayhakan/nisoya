<?php

namespace Tests\Feature;

use App\Contracts\AiProvider;
use App\Models\Category;
use App\Models\User;
use App\Services\ListingTextService;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

/**
 * Serbest metinden ilan taslağı.
 *
 * ---------------------------------------------------------------------------
 * NE KORUYOR
 *
 * Bu özellik arz darboğazına en yakın duran şey: diaspora ticareti bugün
 * WhatsApp gruplarında dönüyor ve buradaki amaç o metni siteye taşımak.
 * Ama kolaylık uğruna üç şeyden ödün verilemez:
 *
 *   1. UYDURMA YOK — metinde geçmeyen özellik yazılmaz, fiyat tahmin
 *      edilmez. Sahibin duruşu: sitedeki her bilgi gerçek.
 *   2. İLETİŞİM BİLGİSİ SIZMAZ — yapıştırılan metindeki telefon/e-posta
 *      ilana taşınmaz (gizlilik + platform dışına çekmenin önü).
 *   3. AI KAPALIYSA AKIŞ ÖLMEZ — normal form çalışmaya devam eder.
 *
 * AI çağrısı sahte sağlayıcıyla yapılır; bu testler modelin ne dediğini
 * değil, BİZİM sözleşmemizi sınar (şema, eşleme, kapalı hâl, sınırlar).
 */
class MetindenIlanTaslagiTest extends TestCase
{
    use RefreshDatabase;

    private function uye(): User
    {
        $this->seed([CountrySeeder::class, CurrencySeeder::class, CategorySeeder::class]);

        return User::factory()->create(['country_code' => 'DE']);
    }

    /** Sağlayıcıyı sahteleyip verilen JSON'ı döndürtür; prompt'u da yakalar. */
    private function sahteAi(?array $donen, ?string &$yakalananPrompt = null): void
    {
        $sahte = new class($donen, $yakalananPrompt) implements AiProvider
        {
            public function __construct(private ?array $donen, private ?string &$prompt) {}

            public function isConfigured(): bool
            {
                return true;
            }

            public function name(): string
            {
                return 'sahte';
            }

            public function lastError(): ?string
            {
                return null;
            }

            public function analyzeImage(string $b, string $m, string $p, ?array $s = null, ?int $t = null): ?array
            {
                return null;
            }

            public function analyzeText(string $prompt, ?array $jsonSchema = null, ?int $timeoutSeconds = null): ?array
            {
                $this->prompt = $prompt;

                return $this->donen;
            }
        };

        $this->app->instance(AiProvider::class, $sahte);
    }

    public function test_ozellik_kapaliyken_null_doner(): void
    {
        // AI kapalıyken akış ölmemeli; çağıran taraf normal forma düşer.
        config(['ai.features.text_listing' => false]);
        $this->sahteAi(['baslik' => 'X']);
        $this->seed(CategorySeeder::class);

        $this->assertNull(app(ListingTextService::class)->analyze('iphone 15'));
    }

    public function test_cok_kisa_metin_ai_cagirmadan_reddedilir(): void
    {
        /*
         * Boş/çok kısa girdi için para harcamak anlamsız. Sağlayıcıya HİÇ
         * gidilmediğini prompt'un boş kalmasından anlıyoruz.
         */
        config(['ai.features.text_listing' => true]);
        $prompt = null;
        $this->sahteAi(['baslik' => 'X'], $prompt);
        $this->seed(CategorySeeder::class);

        $this->assertNull(app(ListingTextService::class)->analyze('ab'));
        $this->assertNull($prompt, 'Çok kısa metin için AI çağrılmış — boşuna para harcanıyor.');
    }

    public function test_taslak_form_alanlarina_esleniyor(): void
    {
        config(['ai.features.text_listing' => true]);
        $this->seed(CategorySeeder::class);
        $kategori = Category::where('slug', 'doktorlar')->firstOrFail();

        $this->sahteAi([
            'baslik' => 'Nöbetçi doktor hizmeti',
            'kategori_slug' => $kategori->slug,
            'aciklama' => 'Hafta sonu dahil evde muayene hizmeti veriyorum.',
            'durum' => null,
            'fiyat' => 80,
        ]);

        $sonuc = app(ListingTextService::class)->analyze('doktorum, evde muayene yapıyorum');

        $this->assertSame('Nöbetçi doktor hizmeti', $sonuc['title']);
        $this->assertSame($kategori->id, $sonuc['category_id']);
        $this->assertSame(80.0, $sonuc['price']);
        $this->assertNull($sonuc['condition']);
    }

    public function test_tip_kategoriden_turetiliyor(): void
    {
        /*
         * Tip modele AYRICA sorulmuyor; kategoriden türetiliyor. İki ayrı alan
         * sorulsaydı birbiriyle çelişebilirlerdi.
         *
         * BU TEST BİR HATAYI YAKALADI: `Category::type` enum'a cast ediliyor,
         * dizeyle karşılaştırma (`=== 'hizmet'`) her zaman false dönüyordu ve
         * her ilan sessizce ÜRÜN oluyordu. `->value` şart.
         */
        config(['ai.features.text_listing' => true]);
        $this->seed(CategorySeeder::class);
        $hizmet = Category::where('slug', 'doktorlar')->firstOrFail();

        $this->assertSame('hizmet', $hizmet->type->value, 'Ön koşul: doktorlar hizmet kategorisi olmalı');

        $this->sahteAi([
            'baslik' => 'Doktor hizmeti',
            'kategori_slug' => $hizmet->slug,
            'aciklama' => 'Evde muayene hizmeti veriyorum, hafta sonu dahil.',
            'durum' => null,
            'fiyat' => null,
        ]);

        $sonuc = app(ListingTextService::class)->analyze('doktorum');

        $this->assertSame('hizmet', $sonuc['type'],
            'Hizmet kategorisi seçilmesine rağmen tip ürün çıktı — enum karşılaştırması bozuk.');
    }

    public function test_prompt_uydurmayi_ve_iletisim_bilgisini_yasakliyor(): void
    {
        /*
         * Kuralların prompt'ta GERÇEKTEN yazılı olduğunu zorlar. Kural yalnız
         * yorumda kalırsa bir sonraki düzenlemede sessizce düşer.
         */
        config(['ai.features.text_listing' => true]);
        $this->seed(CategorySeeder::class);
        $prompt = null;
        $this->sahteAi(null, $prompt);

        app(ListingTextService::class)->analyze('iphone 15 pro max, 0555 111 22 33');

        $this->assertStringContainsString('UYDURMA', $prompt);
        $this->assertStringContainsString('FİYAT TAHMİN ETME', $prompt);
        $this->assertStringContainsString('İLETİŞİM BİLGİSİNİ ÇIKAR', $prompt);
        $this->assertStringContainsString('iphone 15 pro max', $prompt, 'Kullanıcının metni prompt\'a girmemiş.');
    }

    public function test_uzun_metin_kesiliyor(): void
    {
        // Maliyet ve kötüye kullanım sınırı: prompt'a giren metin sınırlı.
        config(['ai.features.text_listing' => true]);
        $this->seed(CategorySeeder::class);
        $prompt = null;
        $this->sahteAi(null, $prompt);

        app(ListingTextService::class)->analyze(str_repeat('a', 9000));

        $this->assertLessThan(9000, mb_substr_count($prompt, 'a'),
            'Metin kesilmemiş — uzun girdi maliyeti sınırsız büyütür.');
    }

    public function test_ekran_iki_kapiyi_da_gosteriyor(): void
    {
        config(['ai.features.text_listing' => true, 'ai.features.quick_listing' => true]);
        $this->sahteAi(null);

        $this->actingAs($this->uye())
            ->get(route('panel.listings.quick'))
            ->assertOk()
            ->assertSee('Yaz, yapıştır ya da konuş')
            ->assertSee('Fotoğrafla')
            ->assertSee('ilana taşınmaz');
    }

    public function test_metin_kapisi_kayitli_bilesene_bagli(): void
    {
        /*
         * `x-data` NESNE LİTERALİ DEĞİL, KAYITLI BİLEŞEN ADI olmalı.
         *
         * Uzun bir ifadeyi öznitelik içine yazmak bu depoda bir kez canlıya
         * hata gönderdi: yorumdaki çift tırnak özniteliği erken kapattı,
         * Alpine öldü ve 2000+ testin hepsi yeşil kaldı. Mantık app.js'te
         * yaşayınca o tuzak YAPISAL olarak imkânsız.
         *
         * Bu testin sınırı: kablonun yerinde olduğunu kanıtlar, JS'in
         * koştuğunu KANITLAMAZ.
         */
        config(['ai.features.text_listing' => true, 'ai.features.quick_listing' => false]);
        $this->sahteAi(null);

        $html = $this->actingAs($this->uye())->get(route('panel.listings.quick'))->getContent();

        $this->assertStringContainsString('x-data="metinKapisi"', $html,
            'Metin kapısı kayıtlı bileşene bağlı değil — öznitelik içine ifade yazılmış olabilir.');

        $js = File::get(resource_path('js/app.js'));
        $this->assertStringContainsString("Alpine.data('metinKapisi'", $js,
            'Bileşen app.js\'ten kaldırılmış — formdaki x-data ölü kalır.');
    }

    public function test_sesle_yazdirma_bagli_ve_destek_yoksa_gizli(): void
    {
        /*
         * Ses SUNUCUYA GİTMİYOR: tarayıcının kendi konuşma tanıma motoru
         * metni doğrudan kutuya yazıyor, oradan sonrası var olan metin yolu.
         * Bu yüzden yeni bir uç nokta, yeni bir maliyet ve yeni bir yükleme
         * yüzeyi yok.
         *
         * İki şart: motoru desteklemeyen tarayıcıda düğme HİÇ görünmemeli
         * (çalışmayan düğme, hiç olmayandan kötü) ve sesin tarayıcı
         * servisine gittiği kullanıcıya SÖYLENMELİ.
         */
        config(['ai.features.text_listing' => true, 'ai.features.quick_listing' => false]);
        $this->sahteAi(null);

        $html = $this->actingAs($this->uye())->get(route('panel.listings.quick'))->getContent();

        $this->assertStringContainsString('dinlemeyiDegistir()', $html, 'Mikrofon düğmesi bağlı değil.');
        $this->assertStringContainsString('x-show="destekVar"', $html,
            'Mikrofon düğmesi destek kontrolüne bağlı değil — desteklemeyen tarayıcıda ölü düğme görünür.');
        $this->assertStringContainsString('konuşma tanıma servisine gönderilir', $html,
            'Sesin nereye gittiği kullanıcıya söylenmiyor.');

        $js = File::get(resource_path('js/app.js'));
        $this->assertStringContainsString('webkitSpeechRecognition', $js);
        $this->assertStringContainsString("lang = 'tr-TR'", $js, 'Tanıma dili Türkçe değil.');
        $this->assertStringContainsString('onend', $js,
            'onend işlenmiyor — sessizlikte otomatik kapanınca düğme sonsuza dek dinliyor görünür.');
    }

    public function test_metin_kapisi_kapaliyken_fotograf_kapisi_yasar(): void
    {
        /*
         * Kapılar AYRI kapatılabilmeli. Biri kapandığında ekranın tamamını
         * kaybetmek, çalışan yolu da öldürmek olurdu.
         */
        config(['ai.features.text_listing' => false, 'ai.features.quick_listing' => true]);
        $this->sahteAi(null);

        $this->actingAs($this->uye())
            ->get(route('panel.listings.quick'))
            ->assertOk()
            ->assertSee('Fotoğrafla')
            ->assertDontSee('Yaz, yapıştır ya da konuş');
    }

    public function test_ikisi_de_kapaliyken_normal_forma_dusulur(): void
    {
        config(['ai.features.text_listing' => false, 'ai.features.quick_listing' => false]);
        $this->sahteAi(null);

        $this->actingAs($this->uye())
            ->get(route('panel.listings.quick'))
            ->assertRedirect(route('panel.listings.create', ['tip' => 'urun']));
    }

    public function test_taslak_forma_onceden_dolduruluyor(): void
    {
        config(['ai.features.text_listing' => true]);
        $user = $this->uye();
        $kategori = Category::where('slug', 'doktorlar')->firstOrFail();

        $this->sahteAi([
            'baslik' => 'Evde doktor muayenesi',
            'kategori_slug' => $kategori->slug,
            'aciklama' => 'Hafta sonu dahil evde muayene hizmeti veriyorum.',
            'durum' => null,
            'fiyat' => 90,
        ]);

        $this->actingAs($user)
            ->post(route('panel.listings.analyze-text'), ['metin' => 'doktorum evde bakıyorum'])
            ->assertRedirect(route('panel.listings.create', ['tip' => 'hizmet']))
            ->assertSessionHas('quick_prefill', 'metin');

        // Form `old()` okuduğu için öneri oturumda taşınmalı.
        $this->assertSame('Evde doktor muayenesi', session('_old_input.title'));
        $this->assertSame($kategori->id, session('_old_input.category_id'));
    }

    public function test_bant_kaynagi_dogru_soyluyor(): void
    {
        /*
         * CANLIDA BULUNDU (2026-08-13). Bant metni "Fotoğrafından bir taslak
         * hazırladık" diye SABİTTİ; fotoğraf tek yolken doğruydu. Metin ve ses
         * kapıları sonradan eklendi ve YAZARAK taslak hazırlayan kullanıcıya
         * fotoğraf çektiği söyleniyordu. Kullanıcıya yapmadığı şeyi söylemek,
         * ekranın geri kalanına olan güveni de zayıflatır.
         */
        config(['ai.features.text_listing' => true]);
        $uye = $this->uye();
        $kategori = Category::where('slug', 'doktorlar')->firstOrFail();

        $this->sahteAi([
            'baslik' => 'Evde doktor muayenesi',
            'kategori_slug' => $kategori->slug,
            'aciklama' => 'Hafta sonu dahil evde muayene hizmeti veriyorum.',
            'durum' => null,
            'fiyat' => null,
        ]);

        $this->actingAs($uye)
            ->post(route('panel.listings.analyze-text'), ['metin' => 'doktorum evde bakıyorum'])
            ->assertSessionHas('quick_prefill', 'metin');

        // Bandın METNİ de doğru olmalı — oturum değeri doğru olup ekranda
        // yine fotoğraf yazsaydı hata sürerdi.
        $this->actingAs($uye)
            ->get(route('panel.listings.create', ['tip' => 'hizmet']))
            ->assertOk()
            ->assertSee('Yazdığın metinden bir taslak hazırladık', escape: false)
            ->assertDontSee('Fotoğrafından bir taslak hazırladık', escape: false);
    }

    public function test_fiyat_birimi_metinden_cikariliyor(): void
    {
        /*
         * CANLIDA BULUNDU. "saat ücreti 20 euro" yazan kullanıcıda fiyat 20
         * doluyor ama birim varsayılan "Görüşülür"de kalıyordu: hem çelişkili
         * (somut fiyat + "görüşülür"), hem de kullanıcının AÇIKÇA verdiği
         * bilgi kayboluyordu.
         */
        config(['ai.features.text_listing' => true]);
        $uye = $this->uye();
        $kategori = Category::where('slug', 'doktorlar')->firstOrFail();

        $this->sahteAi([
            'baslik' => 'Evde muayene',
            'kategori_slug' => $kategori->slug,
            'aciklama' => 'Evde muayene hizmeti veriyorum.',
            'durum' => null,
            'fiyat' => 20,
            'fiyat_birimi' => 'saatlik',
        ]);

        $this->actingAs($uye)
            ->post(route('panel.listings.analyze-text'), ['metin' => 'saat ücreti 20 euro evde muayene']);

        $this->assertSame('saatlik', session('_old_input.price_unit'));
    }

    public function test_birim_cikarilamazsa_formun_varsayilani_bozulmuyor(): void
    {
        /*
         * `withInput` içine null KOYULMAZ. Form `old('price_unit', $varsayilan)`
         * okuyor ve Laravel'in `old()` çağrısı `Arr::get` üzerinden çalışıyor:
         * anahtar VARSA ve değeri null'sa varsayılana düşmez, null döner.
         * Yani null flash'lamak birim seçimini boşaltırdı — üstelik alan
         * `required`.
         */
        config(['ai.features.text_listing' => true]);
        $uye = $this->uye();
        $kategori = Category::where('slug', 'doktorlar')->firstOrFail();

        $this->sahteAi([
            'baslik' => 'Evde muayene',
            'kategori_slug' => $kategori->slug,
            'aciklama' => 'Evde muayene hizmeti veriyorum.',
            'durum' => null,
            'fiyat' => null,
            'fiyat_birimi' => null,
        ]);

        $this->actingAs($uye)
            ->post(route('panel.listings.analyze-text'), ['metin' => 'doktorum evde bakıyorum']);

        $this->assertNull(session('_old_input.price_unit'),
            'Birim anahtarı flash edilmiş — formun varsayılanı ezilir.');
        $this->assertFalse(array_key_exists('price_unit', (array) session('_old_input')),
            'Anahtar hiç bulunmamalı; null değer varsayılanı bozar.');
    }

    public function test_uydurulan_fiyat_birimi_atiliyor(): void
    {
        config(['ai.features.text_listing' => true]);
        $uye = $this->uye();
        $kategori = Category::where('slug', 'doktorlar')->firstOrFail();

        $this->sahteAi([
            'baslik' => 'Evde muayene',
            'kategori_slug' => $kategori->slug,
            'aciklama' => 'Evde muayene hizmeti veriyorum.',
            'durum' => null,
            'fiyat' => 20,
            'fiyat_birimi' => 'boyle-bir-birim-yok',
        ]);

        $this->actingAs($uye)
            ->post(route('panel.listings.analyze-text'), ['metin' => 'saat ücreti 20 euro']);

        $this->assertFalse(array_key_exists('price_unit', (array) session('_old_input')));
    }

    public function test_ai_cevap_veremezse_kullaniciya_soyleniyor(): void
    {
        // Sessizce boş forma düşmek "özellik bozuk" hissi verir; sebep söylenir.
        config(['ai.features.text_listing' => true]);
        $this->sahteAi(null);

        $this->actingAs($this->uye())
            ->post(route('panel.listings.analyze-text'), ['metin' => 'iphone 15'])
            ->assertRedirect()
            ->assertSessionHas('status');
    }
}
