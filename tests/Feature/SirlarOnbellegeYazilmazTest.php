<?php

namespace Tests\Feature;

use App\Providers\AppServiceProvider;
use App\Support\Settings;
use App\Support\YapilandirmaOnbellegi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionMethod;
use Tests\TestCase;

/**
 * `config:cache` sırları `bootstrap/cache/config.php`'ye DÜZ METİN yazmamalı.
 *
 * Gerçek olay (2026-08-10, canlı): SES SMTP parolası veritabanında 256 hane
 * şifreliyken önbellek dosyasında 44 hane açık metindi; dahası ayar DB'den
 * silindiği hâlde gömülü kopya yüzünden mailer "silahlı" kalmıştı.
 *
 * Bu testler İKİ yönü birden mühürler — yalnız "önbellekte yok" demek yetmez,
 * "çalışma anında hâlâ var" da kanıtlanmalı, yoksa düzeltme e-postayı susturur.
 */
class SirlarOnbellegeYazilmazTest extends TestCase
{
    use RefreshDatabase;

    /** @var array<int, string> */
    private array $orijinalArgv;

    protected function setUp(): void
    {
        parent::setUp();
        $this->orijinalArgv = $_SERVER['argv'] ?? [];
    }

    protected function tearDown(): void
    {
        $_SERVER['argv'] = $this->orijinalArgv;
        parent::tearDown();
    }

    /** @param  array<int, string>  $argv */
    private function argvKur(array $argv): void
    {
        $_SERVER['argv'] = $argv;
    }

    private function cagir(string $metot): void
    {
        $m = new ReflectionMethod(AppServiceProvider::class, $metot);
        $m->setAccessible(true);
        $m->invoke(new AppServiceProvider($this->app));
    }

    public function test_kapi_config_cache_ve_optimize_komutlarini_taniyor(): void
    {
        $this->argvKur(['artisan', 'config:cache']);
        $this->assertTrue(YapilandirmaOnbellegi::aliniyorMu());

        // `optimize` config:cache'i AYNI süreçte çağırır; argv[1] 'optimize' olur.
        $this->argvKur(['artisan', 'optimize']);
        $this->assertTrue(YapilandirmaOnbellegi::aliniyorMu(), 'optimize yolu açık kalırsa sızıntı geri gelir');

        $this->argvKur(['artisan', 'migrate']);
        $this->assertFalse(YapilandirmaOnbellegi::aliniyorMu());

        $this->argvKur(['artisan', 'config:clear']);
        $this->assertFalse(YapilandirmaOnbellegi::aliniyorMu(), 'config:clear dosyaya yazmaz, engellenmemeli');
    }

    public function test_onbellek_kurulurken_smtp_parolasi_yazilmaz(): void
    {
        Settings::setMany([
            'mail.host' => 'smtp.ornek.com',
            'mail.username' => 'kullanici@ornek.com',
            'mail.password' => 'GIZLI-PAROLA-123',
        ]);

        // env'den gelmiş gibi bir değer koy: null'lanmalı, korunmamalı.
        config(['mail.mailers.smtp.password' => 'ENVDEN-GELEN-PAROLA']);

        $this->argvKur(['artisan', 'config:cache']);
        $this->cagir('mergeMailConfig');

        $this->assertNull(config('mail.mailers.smtp.password'));
        // Sır olmayan alanlar yazılmaya devam etmeli — aksi hâlde önbellek eksik kalır.
        $this->assertSame('smtp.ornek.com', config('mail.mailers.smtp.host'));
        $this->assertSame('kullanici@ornek.com', config('mail.mailers.smtp.username'));
    }

    public function test_calisma_aninda_smtp_parolasi_normal_yazilir(): void
    {
        Settings::setMany([
            'mail.host' => 'smtp.ornek.com',
            'mail.password' => 'GIZLI-PAROLA-123',
        ]);

        $this->argvKur(['artisan', 'serve']);
        $this->cagir('mergeMailConfig');

        $this->assertSame('GIZLI-PAROLA-123', config('mail.mailers.smtp.password'));
    }

    public function test_db_ayari_yokken_env_parolasi_bozulmaz(): void
    {
        // mail.host YOK → mergeMailConfig erken döner, env değerine dokunulmamalı.
        config(['mail.mailers.smtp.password' => 'ENVDEN-GELEN-PAROLA']);

        $this->argvKur(['artisan', 'config:cache']);
        $this->cagir('mergeMailConfig');

        $this->assertSame('ENVDEN-GELEN-PAROLA', config('mail.mailers.smtp.password'));
    }

    public function test_onbellek_kurulurken_ai_anahtari_yazilmaz(): void
    {
        Settings::setMany(['ai.saglayici' => 'openrouter', 'ai.api_anahtari' => 'sk-GIZLI-ANAHTAR']);

        $this->argvKur(['artisan', 'config:cache']);
        $this->cagir('mergeAiConfig');

        $this->assertNotSame('sk-GIZLI-ANAHTAR', config('ai.providers.openrouter.api_key'));
        $this->assertNotSame('sk-GIZLI-ANAHTAR', config('ai.providers.openrouter.key'));
        // Sır olmayan seçim yazılmaya devam etmeli.
        $this->assertSame('openrouter', config('ai.default'));
    }

    public function test_calisma_aninda_ai_anahtari_normal_yazilir(): void
    {
        Settings::setMany(['ai.saglayici' => 'openrouter', 'ai.api_anahtari' => 'sk-GIZLI-ANAHTAR']);

        $this->argvKur(['artisan', 'queue:work']);
        $this->cagir('mergeAiConfig');

        $this->assertSame('sk-GIZLI-ANAHTAR', config('ai.providers.openrouter.api_key'));
        $this->assertSame('sk-GIZLI-ANAHTAR', config('ai.providers.openrouter.key'));
    }

    public function test_onbellek_kurulurken_places_anahtari_yazilmaz(): void
    {
        Settings::setMany(['growth.google_places_api_key' => 'AIza-GIZLI', 'growth.source' => 'google']);

        $this->argvKur(['artisan', 'config:cache']);
        $this->cagir('mergeGrowthConfig');

        $this->assertNotSame('AIza-GIZLI', config('growth.google_places.api_key'));
        // Sır olmayan kaynak seçimi yazılmalı (keşif kaynağı önbellekte kalabilir).
        $this->assertSame('google', config('growth.source'));
    }

    public function test_calisma_aninda_places_anahtari_normal_yazilir(): void
    {
        Settings::setMany(['growth.google_places_api_key' => 'AIza-GIZLI']);

        $this->argvKur(['artisan', 'growth:discover']);
        $this->cagir('mergeGrowthConfig');

        $this->assertSame('AIza-GIZLI', config('growth.google_places.api_key'));
    }

    public function test_onbellek_kurulurken_kahya_gondericisi_hic_tanimlanmaz(): void
    {
        Settings::setMany([
            'kahya.gonderim_host' => 'email-smtp.eu-central-1.amazonaws.com',
            'kahya.gonderim_kullanici' => 'AKIAGIZLI',
            'kahya.gonderim_parola' => 'SES-GIZLI-PAROLA',
            'kahya.gonderim_port' => '465',
        ]);

        $this->argvKur(['artisan', 'config:cache']);
        $this->cagir('mergeKahyaConfig');

        $this->assertArrayNotHasKey('kahya-gonderim', config('mail.mailers'));
    }

    public function test_calisma_aninda_kahya_gondericisi_tanimlanir(): void
    {
        Settings::setMany([
            'kahya.gonderim_host' => 'email-smtp.eu-central-1.amazonaws.com',
            'kahya.gonderim_kullanici' => 'AKIAGIZLI',
            'kahya.gonderim_parola' => 'SES-GIZLI-PAROLA',
            'kahya.gonderim_port' => '465',
        ]);

        $this->argvKur(['artisan', 'schedule:run']);
        $this->cagir('mergeKahyaConfig');

        $mailer = config('mail.mailers.kahya-gonderim');
        $this->assertIsArray($mailer);
        $this->assertSame('SES-GIZLI-PAROLA', $mailer['password']);
    }

    public function test_sirli_anahtarlarin_tamami_bu_testte_kapsanmis(): void
    {
        /*
         * BEKÇİ: Settings::SIRLI_ANAHTARLAR'a yeni bir sır eklenirse ve o sır
         * config'e yazılıyorsa, buraya da bir kapak testi eklenmeli. Liste
         * büyüdüğünde bu test kırmızıya döner ve unutulmayı engeller.
         */
        $this->assertSame(
            ['mail.password', 'ai.api_anahtari', 'growth.google_places_api_key', 'kahya.gonderim_parola'],
            Settings::SIRLI_ANAHTARLAR,
            'Sır listesi değişti — SirlarOnbellegeYazilmazTest\'e yeni sır için kapak testi ekle.'
        );
    }
}
