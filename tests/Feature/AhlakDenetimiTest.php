<?php

namespace Tests\Feature;

use App\Contracts\AiProvider;
use App\Enums\ListingStatus;
use App\Models\Category;
use App\Models\Listing;
use App\Models\User;
use App\Services\AhlakDenetimi;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Otomatik temsilî görsel üretiminden önceki ahlak/uygunluk ön-elemesi.
 *
 * ---------------------------------------------------------------------------
 * NE KORUYOR
 *
 * 1. DAR TUTULMUŞ KAPSAM. Yalnız yetişkin içerik/yasa dışı mal-hizmet/nefret
 *    söylemi/istismar — sıradan bir ilanın pazarlık/iletişim ifadeleri
 *    ("kapora", "WhatsApp'tan yazın") burada işaretlenmemeli; o zaten
 *    DolandiricilikTespiti'nin işi, bu sınıfın işi değil.
 * 2. FAIL-OPEN. AI kapalı/kırıksa görsel üretimi ENGELLENMEZ — bu bir
 *    güvenlik ağı, üretimin önünde bir kapı değil.
 * 3. İKİ SINIF AYRI SORU SORAR. AhlakDenetimi ile DolandiricilikTespiti aynı
 *    ilanda aynı anda farklı sonuç verebilir; biri diğerinin yerine geçmez.
 */
class AhlakDenetimiTest extends TestCase
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
            'title' => 'Ev temizliği hizmeti',
            'description' => 'Haftalık ev temizliği yapıyorum, referanslarım var.',
        ], $ustuneYaz));
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CountrySeeder::class, CurrencySeeder::class, CategorySeeder::class]);
        config(['ai.features.content_ethics_check' => true]);
    }

    public function test_uygun_ilan_engellenmiyor(): void
    {
        $this->sahteAi(['uygun' => true, 'sebep' => null]);

        $sonuc = app(AhlakDenetimi::class)->kontrolEt($this->ilan());

        $this->assertTrue($sonuc['uygun']);
        $this->assertNull($sonuc['sebep']);
    }

    public function test_uygun_olmayan_ilan_sebebiyle_isaretleniyor(): void
    {
        $this->sahteAi(['uygun' => false, 'sebep' => 'Yetişkin içerikli hizmet']);

        $sonuc = app(AhlakDenetimi::class)->kontrolEt($this->ilan());

        $this->assertFalse($sonuc['uygun']);
        $this->assertSame('Yetişkin içerikli hizmet', $sonuc['sebep']);
    }

    public function test_ai_kirikken_fail_open_null_doner(): void
    {
        // Fail-open: null döner, çağıran taraf bunu "engelleme" diye okur.
        $this->sahteAi(null);

        $sonuc = app(AhlakDenetimi::class)->kontrolEt($this->ilan());

        $this->assertNull($sonuc);
    }

    public function test_ozellik_kapaliyken_ai_hic_cagrilmiyor(): void
    {
        config(['ai.features.content_ethics_check' => false]);
        $this->sahteAi(['uygun' => false, 'sebep' => 'çağrılmamalı']);

        $sonuc = app(AhlakDenetimi::class)->kontrolEt($this->ilan());

        $this->assertNull($sonuc);
    }

    public function test_istem_siradan_pazarlik_ifadelerini_ornek_verip_disliyor(): void
    {
        /*
         * ASIL BEKÇİ. Bu sınıf DolandiricilikTespiti'nin işini tekrar
         * etmemeli — "kapora", "WhatsApp'tan yazın" gibi sıradan ifadeler
         * burada işaretlenmemeli, istem bunu açıkça söylemeli.
         */
        $this->sahteAi(null, $prompt);

        app(AhlakDenetimi::class)->kontrolEt($this->ilan());

        $this->assertStringContainsString('Yetişkin', $prompt);
        $this->assertStringContainsString('Yasa dışı', $prompt);
        $this->assertStringContainsString('TEK BAŞINA uygun=false gerekçesi DEĞİLDİR', $prompt);
        $this->assertStringContainsString('Ev temizliği hizmeti', $prompt, 'İlanın kendi metni isteme girmemiş.');
    }

    public function test_sebepsiz_uygun_degil_yaniti_da_sebep_null_donduruyor(): void
    {
        // Sağlayıcı sebep vermezse null'a düşürülür — panelde boş gerekçe göstermeyelim.
        $this->sahteAi(['uygun' => false, 'sebep' => null]);

        $sonuc = app(AhlakDenetimi::class)->kontrolEt($this->ilan());

        $this->assertFalse($sonuc['uygun']);
        $this->assertNull($sonuc['sebep']);
    }
}
