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
            ->assertSee('Yaz ya da yapıştır')
            ->assertSee('Fotoğrafla')
            ->assertSee('ilana taşınmaz');
    }

    public function test_alpine_ifadesi_html_ozniteligini_erken_kapatmiyor(): void
    {
        /*
         * BU DEPODA AYNI HATA CANLIYA GİTTİ (acil paneli, 2026-08-12).
         *
         * `x-data="{ … }"` bir HTML özniteliğidir; içine çift tırnak girerse
         * öznitelik erken kapanır ve Alpine ifadesi sözdizimi hatasıyla ölür.
         * Burada ölürse gönder düğmesi `:disabled` bağı çözülemediği için
         * KALICI OLARAK PASİF kalır — özellik sessizce çalışmaz.
         *
         * Sunucu tarafı test JS'in koştuğunu kanıtlamaz; bu test yalnız
         * özniteliğin bütün kaldığını kanıtlar. Gerçek koşma ancak AI açıkken
         * tarayıcıda görülür.
         */
        config(['ai.features.text_listing' => true, 'ai.features.quick_listing' => false]);
        $this->sahteAi(null);

        $html = $this->actingAs($this->uye())->get(route('panel.listings.quick'))->getContent();

        preg_match_all('/x-data="([^"]*)"/', $html, $eslesmeler);
        $parca = collect($eslesmeler[1])->first(fn ($p) => str_contains($p, 'gonderiliyor'));

        $this->assertNotNull($parca, 'Metin kapısının x-data ifadesi bulunamadı.');
        $this->assertSame(substr_count($parca, '{'), substr_count($parca, '}'),
            'x-data özniteliği erken kapanmış — içinde çift tırnak var, Alpine ölür.');
        $this->assertStringContainsString('metin:', $parca);
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
            ->assertDontSee('Yaz ya da yapıştır');
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
            ->assertSessionHas('quick_prefill', true);

        // Form `old()` okuduğu için öneri oturumda taşınmalı.
        $this->assertSame('Evde doktor muayenesi', session('_old_input.title'));
        $this->assertSame($kategori->id, session('_old_input.category_id'));
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
