<?php

namespace Tests\Feature;

use App\Contracts\AiProvider;
use App\Enums\ListingStatus;
use App\Models\Category;
use App\Models\Listing;
use App\Models\ListingTranslation;
use App\Models\User;
use App\Services\IlanCevirmeni;
use App\Support\Settings;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * İlanın yerel dile çevrilmesi (SEO + Türkçe bilmeyen müşteri).
 *
 * ---------------------------------------------------------------------------
 * NE KORUYOR
 *
 * 1. BAYAT ÇEVİRİ BASILMAZ. Satıcı metni değiştirince çeviri sessizce yanlışa
 *    döner: eski fiyatı, kaldırılmış bir detayı anlatmaya devam eder. Kaynak
 *    özeti tutmuyorsa çeviri GİZLENİR. Bu testin en önemli maddesi bu —
 *    yanlış bilgi, bilgi olmamasından kötüdür.
 * 2. OTOMATİK OLDUĞU YAZAR. Etiketsiz çeviri, hatayı satıcının üstüne yıkar.
 * 3. HER İKİ TEMADA. Vitrin dosyaları klasik görünümleri geçersiz kılıyor;
 *    yalnız birini güncellemek bir temada bloğu görünmez bırakır.
 * 4. UYDURMA YOK. İstem "yalnız çevir" diyor — modelin ilanı "daha çekici"
 *    yazması, satıcının vermediği sözü onun ağzından vermek olurdu.
 */
class IlanCevirisiTest extends TestCase
{
    use RefreshDatabase;

    private function sahteAi(?array $donen, ?string &$prompt = null): void
    {
        $sahte = new class($donen, $prompt) implements AiProvider
        {
            public function __construct(private ?array $donen, private ?string &$p) {}

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

            public function analyzeImage(string $b, string $m, string $pr, ?array $s = null, ?int $t = null): ?array
            {
                return null;
            }

            public function analyzeText(string $prompt, ?array $jsonSchema = null, ?int $timeoutSeconds = null): ?array
            {
                $this->p = $prompt;

                return $this->donen;
            }
        };

        $this->app->instance(AiProvider::class, $sahte);
    }

    private function ilan(array $ustuneYaz = []): Listing
    {
        $kategori = Category::query()->whereNotNull('parent_id')->where('type', 'hizmet')->firstOrFail();

        return Listing::factory()->create(array_merge([
            'user_id' => User::factory()->create()->id,
            'category_id' => $kategori->id,
            'type' => 'hizmet',
            'status' => ListingStatus::Aktif,
            'country_code' => 'DE',
            'title' => 'Terzi ve tadilat hizmeti',
            'description' => 'Pantolon paça kısaltma, elbise daraltma ve fermuar değişimi yapıyorum. On yıllık tecrübe.',
        ], $ustuneYaz));
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CountrySeeder::class, CurrencySeeder::class, CategorySeeder::class]);
        config(['ai.features.listing_translation' => true]);
    }

    public function test_ulkeden_hedef_dil_bulunuyor(): void
    {
        $this->sahteAi(null);
        $cevirmen = app(IlanCevirmeni::class);

        $this->assertSame('de', $cevirmen->hedefDil($this->ilan(['country_code' => 'DE'])));
        $this->assertSame('nl', $cevirmen->hedefDil($this->ilan(['country_code' => 'NL'])));
        $this->assertSame('de', $cevirmen->hedefDil($this->ilan(['country_code' => 'AT'])));
    }

    public function test_haritada_olmayan_ulkede_ozellik_kapali(): void
    {
        /*
         * Kazakistan/Körfez gibi ülkelerde ticaretin hangi dilde arandığını
         * bilmiyoruz. Tahminle bir dile çevirmek, parayı kimsenin aramadığı
         * bir metne harcamaktır — düğme hiç görünmesin.
         */
        $this->sahteAi(['title' => 'X', 'description' => 'Y']);
        $ilan = $this->ilan(['country_code' => 'KZ']);

        $cevirmen = app(IlanCevirmeni::class);

        $this->assertNull($cevirmen->hedefDil($ilan));
        $this->assertFalse($cevirmen->uygunMu($ilan));
        $this->assertNull($cevirmen->cevir($ilan));
        $this->assertSame(0, $ilan->translations()->count());
    }

    public function test_cok_kisa_aciklama_cevrilmez(): void
    {
        $this->sahteAi(['title' => 'X', 'description' => 'Y']);
        $ilan = $this->ilan(['description' => 'Temizlik.']);

        $this->assertFalse(app(IlanCevirmeni::class)->uygunMu($ilan));
    }

    public function test_ceviri_kaydediliyor_ve_kaynak_ozeti_tutuluyor(): void
    {
        $this->sahteAi([
            'title' => 'Schneiderei und Änderungsservice',
            'description' => 'Hosen kürzen, Kleider enger machen und Reißverschluss wechseln.',
        ]);

        $ilan = $this->ilan();

        $this->actingAs($ilan->user)
            ->post(route('panel.listings.translate', $ilan))
            ->assertRedirect(route('panel.listings.edit', $ilan));

        $ceviri = $ilan->translations()->first();

        $this->assertNotNull($ceviri);
        $this->assertSame('de', $ceviri->locale);
        $this->assertSame('Schneiderei und Änderungsservice', $ceviri->title);
        $this->assertSame(ListingTranslation::kaynakOzeti($ilan), $ceviri->source_hash);
    }

    public function test_metin_degisince_ceviri_gizleniyor(): void
    {
        /*
         * ASIL BEKÇİ. Satıcı fiyatı ya da kapsamı değiştirdiğinde eski çeviri
         * hâlâ ESKİ ilanı anlatıyor. Yabancı müşteri o metne göre geliyor ve
         * karşısında başka bir şey buluyor.
         */
        $this->sahteAi([
            'title' => 'Schneiderei',
            'description' => 'Hosen kürzen und Reißverschluss wechseln.',
        ]);

        $ilan = $this->ilan();
        $cevirmen = app(IlanCevirmeni::class);
        $cevirmen->cevir($ilan);

        $this->assertNotNull($cevirmen->guncelCeviri($ilan->fresh()));

        // Satıcı açıklamayı değiştirdi.
        $ilan->update(['description' => 'Artık yalnız fermuar değişimi yapıyorum, tadilat almıyorum.']);

        $this->assertNull($cevirmen->guncelCeviri($ilan->fresh()),
            'Metin değişmiş ama eski çeviri hâlâ geçerli sayılıyor — yanlış bilgi basılır.');

        // Kayıt SİLİNMEDİ: satıcı metni geri alırsa çeviri kendiliğinden dönmeli.
        $this->assertSame(1, $ilan->translations()->count());
    }

    public function test_yeniden_ceviri_ikinci_satir_acmaz(): void
    {
        $this->sahteAi(['title' => 'A', 'description' => 'B']);

        $ilan = $this->ilan();
        $cevirmen = app(IlanCevirmeni::class);

        $cevirmen->cevir($ilan);
        $ilan->update(['description' => 'Yeni ve yeterince uzun bir açıklama metni buraya yazıldı.']);
        $cevirmen->cevir($ilan->fresh());

        $this->assertSame(1, $ilan->translations()->count(),
            'Aynı dilde iki satır oluşmuş — hangisinin geçerli olduğu belirsizleşir.');
    }

    public function test_baskasinin_ilani_cevrilemez(): void
    {
        $this->sahteAi(['title' => 'A', 'description' => 'B']);
        $ilan = $this->ilan();

        $this->actingAs(User::factory()->create())
            ->post(route('panel.listings.translate', $ilan))
            ->assertForbidden();

        $this->assertSame(0, $ilan->translations()->count());
    }

    public function test_bos_yanit_kaydedilmez(): void
    {
        $this->sahteAi(['title' => '', 'description' => '']);

        $ilan = $this->ilan();

        $this->assertNull(app(IlanCevirmeni::class)->cevir($ilan));
        $this->assertSame(0, $ilan->translations()->count());
    }

    public function test_istem_uydurmayi_ve_iletisim_bilgisini_yasakliyor(): void
    {
        $this->sahteAi(null, $prompt);
        $ilan = $this->ilan();

        app(IlanCevirmeni::class)->cevir($ilan);

        $this->assertStringContainsString('YALNIZ ÇEVİR', $prompt);
        $this->assertStringContainsString('VERMEDİĞİ hiçbir bilgiyi', $prompt);
        $this->assertStringContainsString('METNE ALMA', $prompt);
        $this->assertStringContainsString('Almanca', $prompt);
        $this->assertStringContainsString('Terzi ve tadilat', $prompt, 'İlanın kendi metni isteme girmemiş.');
    }

    /**
     * Blok İKİ TEMADA da basılmalı ve İKİSİNDE de "otomatik çeviri" demeli.
     *
     * Vitrin dosyaları klasik görünümleri geçersiz kılıyor; yalnız birini
     * güncellemek, bir temada bloğu görünmez ya da etiketsiz bırakır.
     */
    public function test_blok_her_iki_temada_etiketiyle_basiliyor(): void
    {
        $this->sahteAi([
            'title' => 'Schneiderei und Änderungsservice',
            'description' => 'Hosen kürzen und Reißverschluss wechseln.',
        ]);

        $ilan = $this->ilan();
        app(IlanCevirmeni::class)->cevir($ilan);

        foreach (['klasik', 'vitrin'] as $tema) {
            Settings::setMany(['gorunum.tema' => $tema]);

            $this->get(route('listings.show', [$ilan, $ilan->slug]))
                ->assertOk()
                ->assertSee('Schneiderei und Änderungsservice')
                ->assertSee('otomatik çeviri', escape: false)
                ->assertSee('Bağlayıcı olan Türkçe aslıdır', escape: false);
        }
    }

    public function test_bayat_ceviri_ilan_sayfasinda_basilmaz(): void
    {
        $this->sahteAi([
            'title' => 'Schneiderei und Änderungsservice',
            'description' => 'Hosen kürzen und Reißverschluss wechseln.',
        ]);

        $ilan = $this->ilan();
        app(IlanCevirmeni::class)->cevir($ilan);
        $ilan->update(['description' => 'Artık yalnız fermuar değişimi yapıyorum, başka iş almıyorum.']);

        foreach (['klasik', 'vitrin'] as $tema) {
            Settings::setMany(['gorunum.tema' => $tema]);

            $this->get(route('listings.show', [$ilan->fresh(), $ilan->slug]))
                ->assertOk()
                ->assertDontSee('Schneiderei und Änderungsservice');
        }
    }

    public function test_duzenleme_sayfasinda_dugme_yalniz_uygun_ilanda(): void
    {
        $this->sahteAi(null);

        $uygun = $this->ilan();
        $this->actingAs($uygun->user)
            ->get(route('panel.listings.edit', $uygun))
            ->assertOk()
            ->assertSee('Almanca çeviri ekle', escape: false);

        $uygunsuz = $this->ilan(['country_code' => 'KZ']);
        $this->actingAs($uygunsuz->user)
            ->get(route('panel.listings.edit', $uygunsuz))
            ->assertOk()
            ->assertDontSee('çeviri ekle', escape: false);
    }

    public function test_dili_tanimsiz_ulkede_ek_sorgu_acilmiyor(): void
    {
        /*
         * Controller'a "koşullu yükleme maliyeti sıfırlar" diye yorum yazdım;
         * yorum ölçüm değildir. Dili haritada OLMAYAN bir ilanda çeviri
         * tablosuna hiç gidilmediğini burada ölçüyoruz — gidilseydi
         * karşılığı olmayan bir sorgu olurdu, çünkü blok zaten basılmıyor.
         */
        $this->sahteAi(null);
        $ilan = $this->ilan(['country_code' => 'KZ']);

        $sorgular = [];
        DB::listen(function ($q) use (&$sorgular) {
            $sorgular[] = $q->sql;
        });

        $this->get(route('listings.show', [$ilan, $ilan->slug]))->assertOk();

        $cevirilenler = array_filter($sorgular, fn (string $sql) => str_contains($sql, 'listing_translations'));

        $this->assertCount(0, $cevirilenler,
            'Dili tanımsız ülkede bile çeviri tablosuna gidilmiş: '.implode(' | ', $cevirilenler));
    }

    public function test_dili_tanimli_ulkede_tek_sorgu_aciliyor(): void
    {
        // Diğer yarısı: dil varsa TEK sorgu açılıyor (N+1 değil).
        $this->sahteAi(null);
        $ilan = $this->ilan(['country_code' => 'DE']);

        $sorgular = [];
        DB::listen(function ($q) use (&$sorgular) {
            $sorgular[] = $q->sql;
        });

        $this->get(route('listings.show', [$ilan, $ilan->slug]))->assertOk();

        $cevirilenler = array_filter($sorgular, fn (string $sql) => str_contains($sql, 'listing_translations'));

        $this->assertCount(1, $cevirilenler);
    }

    public function test_ozellik_kapaliyken_ceviri_uretilmez(): void
    {
        config(['ai.features.listing_translation' => false]);
        $this->sahteAi(['title' => 'A', 'description' => 'B']);

        $ilan = $this->ilan();

        $this->assertFalse(app(IlanCevirmeni::class)->uygunMu($ilan));
        $this->assertNull(app(IlanCevirmeni::class)->cevir($ilan));
    }
}
