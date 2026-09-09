<?php

namespace Tests\Feature;

use App\Contracts\AiProvider;
use App\Enums\ListingStatus;
use App\Models\Category;
use App\Models\Listing;
use App\Models\User;
use App\Services\Ai\FotografUretici;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * `listings:generate-representative-images` — 2+ gündür görselsiz kalan
 * aktif ilanlara otomatik temsilî görsel.
 *
 * ---------------------------------------------------------------------------
 * NE KORUYOR
 *
 * 1. 2 GÜNDEN GENÇ İLANA DOKUNMAZ. Sahibine kendi fotoğrafını ekleme fırsatı
 *    tanınır.
 * 2. BİR KEZ DENER. `temsili_gorsel_denendi_at` damgalandıktan sonra ilan
 *    bir daha taranmaz — başarılı/başarısız/ahlaki-durdurulmuş fark etmez.
 * 3. AHLAKİ KAPI ÖNCE. Uygun değilse görsel HİÇ üretilmez, ilan Beklemede'ye
 *    alınır. AI kırıksa (fail-open) görsel yine üretilir.
 * 4. --dry HİÇBİR ŞEY KALICI DEĞİŞTİRMEZ.
 */
class TemsiliGorselOtomatikUretTest extends TestCase
{
    use RefreshDatabase;

    private function sahteUretici(bool $basarili = true): void
    {
        $bayt = null;

        if ($basarili) {
            $sahteDosya = UploadedFile::fake()->image('temsili.png', 1280, 720);
            $bayt = (string) file_get_contents($sahteDosya->getRealPath());
        }

        $sahte = new class($bayt) extends FotografUretici
        {
            public function __construct(private ?string $bayt)
            {
                //
            }

            public function isConfigured(): bool
            {
                return true;
            }

            public function uret(string $istem, int $timeoutSeconds = 90): ?string
            {
                return $this->bayt;
            }
        };

        $this->app->instance(FotografUretici::class, $sahte);
    }

    /** @param array{uygun: bool, sebep: ?string}|null $ahlakSonucu null = AI kapalı gibi davran (fail-open) */
    private function sahteAhlak(?array $ahlakSonucu): void
    {
        $sahte = new class($ahlakSonucu) implements AiProvider
        {
            public function __construct(private ?array $donen) {}

            public function isConfigured(): bool
            {
                return $this->donen !== null;
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
            'description' => 'Haftalık ev temizliği yapıyorum.',
            'created_at' => now()->subDays(3),
        ], $ustuneYaz));
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CountrySeeder::class, CurrencySeeder::class, CategorySeeder::class]);
        Storage::fake('public');
        Storage::fake('local');
        config(['ai.features.service_image' => true]);
        config(['ai.features.auto_representative_image' => true]);
        config(['ai.features.content_ethics_check' => true]);
    }

    public function test_2_gunden_genc_ilana_dokunmuyor(): void
    {
        $this->sahteUretici();
        $this->sahteAhlak(['uygun' => true, 'sebep' => null]);

        $ilan = $this->ilan(['created_at' => now()->subDays(1)]);

        $this->artisan('listings:generate-representative-images')->assertSuccessful();

        $this->assertSame(0, $ilan->images()->count());
        $this->assertNull($ilan->fresh()->temsili_gorsel_denendi_at);
    }

    public function test_2_gunden_eski_gorselsiz_ilana_uretir_ve_damgalar(): void
    {
        $this->sahteUretici();
        $this->sahteAhlak(['uygun' => true, 'sebep' => null]);

        $ilan = $this->ilan();

        $this->artisan('listings:generate-representative-images')->assertSuccessful();

        $taze = $ilan->fresh();
        $this->assertSame(1, $taze->images()->count());
        $this->assertTrue($taze->images()->first()->is_representative);
        $this->assertNotNull($taze->temsili_gorsel_denendi_at);
    }

    public function test_zaten_denenmis_ilan_tekrar_islenmiyor(): void
    {
        $this->sahteUretici();
        $this->sahteAhlak(['uygun' => true, 'sebep' => null]);

        $ilan = $this->ilan(['temsili_gorsel_denendi_at' => now()->subDay()]);

        $this->artisan('listings:generate-representative-images')->assertSuccessful();

        $this->assertSame(0, $ilan->images()->count(), 'Daha önce denenmiş ilan yeniden işlenmiş.');
    }

    public function test_ahlaki_uygun_olmayan_ilan_gorsel_almadan_beklemeye_aliniyor(): void
    {
        $this->sahteUretici();
        $this->sahteAhlak(['uygun' => false, 'sebep' => 'Yetişkin içerikli hizmet']);

        $ilan = $this->ilan();

        $this->artisan('listings:generate-representative-images')->assertSuccessful();

        $taze = $ilan->fresh();
        $this->assertSame(0, $taze->images()->count(), 'Ahlaki kapıda durdurulan ilana görsel üretilmiş.');
        $this->assertSame(ListingStatus::Beklemede, $taze->status);
        $this->assertNotNull($taze->temsili_gorsel_denendi_at, 'Beklemeye alınan ilan da damgalanmalı — bir daha taranmasın.');
    }

    public function test_ahlak_ai_kirikken_fail_open_gorsel_uretiliyor(): void
    {
        // Fail-open: AhlakDenetimi null döner (isConfigured=false), üretim engellenmez.
        $this->sahteUretici();
        $this->sahteAhlak(null);

        $ilan = $this->ilan();

        $this->artisan('listings:generate-representative-images')->assertSuccessful();

        $taze = $ilan->fresh();
        $this->assertSame(1, $taze->images()->count());
        $this->assertSame(ListingStatus::Aktif, $taze->status);
    }

    public function test_uretim_basarisiz_olsa_da_damgalaniyor_bir_daha_denenmiyor(): void
    {
        $this->sahteUretici(basarili: false);
        $this->sahteAhlak(['uygun' => true, 'sebep' => null]);

        $ilan = $this->ilan();

        $this->artisan('listings:generate-representative-images')->assertSuccessful();

        $taze = $ilan->fresh();
        $this->assertSame(0, $taze->images()->count());
        $this->assertNotNull($taze->temsili_gorsel_denendi_at, 'Başarısız üretim damgalanmamış — komut her gün yeniden deneyecek.');
    }

    public function test_dry_hicbir_sey_degistirmiyor(): void
    {
        $this->sahteUretici();
        $this->sahteAhlak(['uygun' => true, 'sebep' => null]);

        $ilan = $this->ilan();

        $this->artisan('listings:generate-representative-images --dry')->assertSuccessful();

        $taze = $ilan->fresh();
        $this->assertSame(0, $taze->images()->count());
        $this->assertNull($taze->temsili_gorsel_denendi_at);
        $this->assertSame(ListingStatus::Aktif, $taze->status);
    }

    public function test_demo_ilan_islenmiyor(): void
    {
        $this->sahteUretici();
        $this->sahteAhlak(['uygun' => true, 'sebep' => null]);

        $ilan = $this->ilan(['is_demo' => true]);

        $this->artisan('listings:generate-representative-images')->assertSuccessful();

        $this->assertSame(0, $ilan->images()->count());
        $this->assertNull($ilan->fresh()->temsili_gorsel_denendi_at);
    }

    public function test_aktif_olmayan_ilan_islenmiyor(): void
    {
        $this->sahteUretici();
        $this->sahteAhlak(['uygun' => true, 'sebep' => null]);

        $taslak = $this->ilan(['status' => ListingStatus::Taslak]);
        $pasif = $this->ilan(['status' => ListingStatus::Pasif]);

        $this->artisan('listings:generate-representative-images')->assertSuccessful();

        $this->assertSame(0, $taslak->images()->count());
        $this->assertSame(0, $pasif->images()->count());
    }

    public function test_gorseli_olan_ilan_atlaniyor(): void
    {
        $this->sahteUretici();
        $this->sahteAhlak(['uygun' => true, 'sebep' => null]);

        $ilan = $this->ilan();
        $ilan->images()->create(['path_thumb' => 'a.webp', 'path_medium' => 'b.webp', 'path_large' => 'c.webp']);

        $this->artisan('listings:generate-representative-images')->assertSuccessful();

        $this->assertSame(1, $ilan->images()->count(), 'Gerçek görseli olan ilana ikinci bir görsel eklenmiş.');
        $this->assertNull($ilan->fresh()->temsili_gorsel_denendi_at);
    }

    public function test_ozellik_kapaliyken_hicbir_sey_islenmiyor(): void
    {
        config(['ai.features.auto_representative_image' => false]);
        $this->sahteUretici();
        $this->sahteAhlak(['uygun' => true, 'sebep' => null]);

        $ilan = $this->ilan();

        $this->artisan('listings:generate-representative-images')->assertSuccessful();

        $this->assertSame(0, $ilan->images()->count());
        $this->assertNull($ilan->fresh()->temsili_gorsel_denendi_at);
    }

    public function test_limit_secenegi_uygulaniyor(): void
    {
        $this->sahteUretici();
        $this->sahteAhlak(['uygun' => true, 'sebep' => null]);

        $this->ilan();
        $this->ilan();
        $this->ilan();

        $this->artisan('listings:generate-representative-images --limit=1')->assertSuccessful();

        $islenen = Listing::query()->whereNotNull('temsili_gorsel_denendi_at')->count();
        $this->assertSame(1, $islenen);
    }
}
