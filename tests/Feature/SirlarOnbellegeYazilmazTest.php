<?php

namespace Tests\Feature;

use App\Providers\AppServiceProvider;
use App\Support\Settings;
use App\Support\YapilandirmaOnbellegi;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
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

        // `optimize` config:cache'i AYNI süreçte çağırır; komut adı 'optimize' olur.
        $this->argvKur(['artisan', 'optimize']);
        $this->assertTrue(YapilandirmaOnbellegi::aliniyorMu(), 'optimize yolu açık kalırsa sızıntı geri gelir');

        $this->argvKur(['artisan', 'migrate']);
        $this->assertFalse(YapilandirmaOnbellegi::aliniyorMu());

        $this->argvKur(['artisan', 'config:clear']);
        $this->assertFalse(YapilandirmaOnbellegi::aliniyorMu(), 'config:clear dosyaya yazmaz, engellenmemeli');

        $this->argvKur(['artisan', 'optimize:clear']);
        $this->assertFalse(YapilandirmaOnbellegi::aliniyorMu(), 'optimize:clear de yazmaz');
    }

    /**
     * ÖLÇÜLMÜŞ YANLIŞ POZİTİF: ikinci ağ (argv'nin tamamına bakış) artisan
     * dışındaki çağrılarda tetiklenmemeli. `phpunit --filter optimize` argv'sinde
     * 'optimize' jetonunu taşır; kapı orada kapansaydı o testler boyunca
     * veritabanı katmanı sessizce atlanır ve sebebi görünmeyen kırıklar çıkardı.
     */
    public function test_artisan_disindaki_cagrilar_kapiyi_tetiklemez(): void
    {
        $this->argvKur(['vendor/bin/phpunit', '--filter', 'optimize']);
        $this->assertFalse(YapilandirmaOnbellegi::aliniyorMu());

        $this->argvKur(['vendor/bin/phpunit', '--filter', 'config:cache']);
        $this->assertFalse(YapilandirmaOnbellegi::aliniyorMu());
    }

    /**
     * REGRESYON BEKÇİSİ — düşmanca inceleme bu deliği ölçtü (2026-08-10).
     *
     * İlk sürüm `$_SERVER['argv'][1]`'e bakıyordu. Symfony seçenekleri komut
     * adından ÖNCE kabul ettiği için tek bir bayrak kapıyı sessizce devre dışı
     * bırakıyor ve dört sır da düz metin dosyaya düşüyordu.
     *
     * @param  array<int, string>  $argv
     */
    #[DataProvider('seceneklerKomuttanOnce')]
    public function test_secenek_komuttan_once_gelse_de_kapi_kapanir(array $argv): void
    {
        $this->argvKur($argv);

        $this->assertTrue(
            YapilandirmaOnbellegi::aliniyorMu(),
            'Komut adı argv[1] değil: "'.implode(' ', $argv).'" biçiminde kapı açık kalırsa sızıntı geri gelir.'
        );
    }

    /** @return array<string, array{array<int, string>}> */
    public static function seceneklerKomuttanOnce(): array
    {
        return [
            '--env=production önce' => [['artisan', '--env=production', 'config:cache']],
            '--env boşluklu önce' => [['artisan', '--env', 'production', 'config:cache']],
            '-n önce' => [['artisan', '-n', 'config:cache']],
            '-q önce' => [['artisan', '-q', 'optimize']],
            '-v önce' => [['artisan', '-v', 'config:cache']],
            '--no-interaction önce' => [['artisan', '--no-interaction', 'config:cache']],
            'birden çok seçenek' => [['artisan', '-n', '-q', '--env=production', 'optimize']],
        ];
    }

    /**
     * Kapının bilinen sınırı belgeli kalsın: süreç içinden `Artisan::call('config:cache')`
     * çağrılırsa argv dış komutu gösterir ve kapı kapanmaz. Bugün depoda böyle bir
     * çağrı YOK; biri eklerse bu test kırılır ve YapilandirmaOnbellegi'ndeki notu okur.
     */
    public function test_kod_icinden_config_cache_cagrilmiyor(): void
    {
        $kokler = [base_path('app'), base_path('routes'), base_path('deploy')];
        $bulunan = [];

        foreach ($kokler as $kok) {
            if (! is_dir($kok)) {
                continue;
            }

            $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($kok));
            foreach ($it as $dosya) {
                if (! $dosya->isFile() || ! in_array($dosya->getExtension(), ['php', 'sh'], true)) {
                    continue;
                }

                $icerik = (string) file_get_contents($dosya->getPathname());

                /*
                 * YORUMLARI AYIKLA. İlk sürüm ham metinde arıyordu ve kendi
                 * kendini yakaladı: YapilandirmaOnbellegi'nin docblock'u bu
                 * sınırı ANLATIRKEN `Artisan::call('config:cache')` yazıyor.
                 * Dosyayı listeden çıkarmak yerine yorumları atıyoruz —
                 * o dosyaya ileride GERÇEK bir çağrı girerse yine yakalansın.
                 */
                if ($dosya->getExtension() === 'php') {
                    $icerik = implode('', array_map(
                        fn ($t) => is_array($t)
                            ? (in_array($t[0], [T_COMMENT, T_DOC_COMMENT], true) ? ' ' : $t[1])
                            : $t,
                        token_get_all($icerik)
                    ));
                }

                if (preg_match('/(Artisan::call|callSilent(?:ly)?)\(\s*[\'"](config:cache|optimize)[\'"]/', $icerik)) {
                    $bulunan[] = str_replace(base_path().DIRECTORY_SEPARATOR, '', $dosya->getPathname());
                }
            }
        }

        $this->assertSame([], $bulunan,
            'Süreç içinden config:cache/optimize çağrısı eklenmiş: '.implode(', ', $bulunan).
            "\nYapilandirmaOnbellegi argv'ye baktığı için bu yolda kapı KAPANMAZ ve sırlar önbelleğe düz metin yazılır."
        );
    }

    public function test_onbellek_kurulurken_smtp_parolasi_yazilmaz(): void
    {
        Settings::setMany([
            'mail.host' => 'smtp.ornek.com',
            'mail.username' => 'kullanici@ornek.com',
            'mail.password' => 'GIZLI-PAROLA-123',
        ]);

        // env'den gelmiş gibi TUTARLI bir mail yapılandırması koy.
        config([
            'mail.mailers.smtp.host' => 'env.ornek.com',
            'mail.mailers.smtp.username' => 'env@ornek.com',
            'mail.mailers.smtp.password' => 'ENVDEN-GELEN-PAROLA',
        ]);

        $this->argvKur(['artisan', 'config:cache']);
        $this->cagir('mergeRuntimeConfig');

        $this->assertNotSame('GIZLI-PAROLA-123', config('mail.mailers.smtp.password'));

        /*
         * ÜÇÜ BİRDEN dokunulmamış olmalı — "yalnız sırrı atla" YETMEZ.
         *
         * Düşmanca inceleme bunu ölçtü: parola atlanıp host/kullanıcı önbellekte
         * kalırsa, sahip panelden SMTP sunucusunu boşalttığı anda mergeMailConfig
         * erken döner ve geriye host+kullanıcı dolu, parolası boş bir YARIM
         * SİLAHLI mailer kalır — SMTP AUTH boş parolayla denenir, işlemsel
         * e-postanın tamamı sessizce düşer, sağlık panosu ise yeşil görünür.
         * Toptan atlayınca önbellek "yalnız env" anlamına gelen tutarlı bir
         * anlık görüntü olur ve bu durum hiç oluşamaz.
         */
        $this->assertSame('ENVDEN-GELEN-PAROLA', config('mail.mailers.smtp.password'));
        $this->assertSame('env.ornek.com', config('mail.mailers.smtp.host'),
            'Önbellek kurulurken DB host da yazılmamalı; yarım silahlı mailer bu yüzden oluşuyordu.');
        $this->assertSame('env@ornek.com', config('mail.mailers.smtp.username'));
    }

    public function test_calisma_aninda_smtp_parolasi_normal_yazilir(): void
    {
        Settings::setMany([
            'mail.host' => 'smtp.ornek.com',
            'mail.password' => 'GIZLI-PAROLA-123',
        ]);

        $this->argvKur(['artisan', 'serve']);
        $this->cagir('mergeRuntimeConfig');

        $this->assertSame('GIZLI-PAROLA-123', config('mail.mailers.smtp.password'));
    }

    public function test_db_ayari_yokken_env_parolasi_bozulmaz(): void
    {
        // mail.host YOK → mergeMailConfig erken döner, env değerine dokunulmamalı.
        config(['mail.mailers.smtp.password' => 'ENVDEN-GELEN-PAROLA']);

        $this->argvKur(['artisan', 'config:cache']);
        $this->cagir('mergeRuntimeConfig');

        $this->assertSame('ENVDEN-GELEN-PAROLA', config('mail.mailers.smtp.password'));
    }

    public function test_onbellek_kurulurken_ai_anahtari_yazilmaz(): void
    {
        $oncekiSaglayici = config('ai.default');
        Settings::setMany(['ai.saglayici' => 'openrouter', 'ai.api_anahtari' => 'sk-GIZLI-ANAHTAR']);

        $this->argvKur(['artisan', 'config:cache']);
        $this->cagir('mergeRuntimeConfig');

        $this->assertNotSame('sk-GIZLI-ANAHTAR', config('ai.providers.openrouter.api_key'));
        $this->assertNotSame('sk-GIZLI-ANAHTAR', config('ai.providers.openrouter.key'));
        // Sağlayıcı seçimi de yazılmamalı: anahtarsız bir sağlayıcı önbelleğe
        // gömülürse, DB katmanı bir sebeple uygulanmadığında YZ sessizce ölür.
        // (Ölçü "değişmedi" — 'openrouter' zaten env varsayılanı olabilir.)
        $this->assertSame($oncekiSaglayici, config('ai.default'));
    }

    public function test_calisma_aninda_ai_anahtari_normal_yazilir(): void
    {
        Settings::setMany(['ai.saglayici' => 'openrouter', 'ai.api_anahtari' => 'sk-GIZLI-ANAHTAR']);

        $this->argvKur(['artisan', 'queue:work']);
        $this->cagir('mergeRuntimeConfig');

        $this->assertSame('sk-GIZLI-ANAHTAR', config('ai.providers.openrouter.api_key'));
        $this->assertSame('sk-GIZLI-ANAHTAR', config('ai.providers.openrouter.key'));
    }

    public function test_onbellek_kurulurken_places_anahtari_yazilmaz(): void
    {
        Settings::setMany(['growth.google_places_api_key' => 'AIza-GIZLI', 'growth.source' => 'google']);

        $this->argvKur(['artisan', 'config:cache']);
        $this->cagir('mergeRuntimeConfig');

        $this->assertNotSame('AIza-GIZLI', config('growth.google_places.api_key'));
        // Kaynak seçimi de yazılmamalı — anahtarsız 'google' önbellekte kalırsa
        // keşif, anahtarı olmayan bir kaynağa düşerdi.
        $this->assertNotSame('google', config('growth.source'));
    }

    public function test_calisma_aninda_places_anahtari_normal_yazilir(): void
    {
        Settings::setMany(['growth.google_places_api_key' => 'AIza-GIZLI']);

        $this->argvKur(['artisan', 'growth:discover']);
        $this->cagir('mergeRuntimeConfig');

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
        $this->cagir('mergeRuntimeConfig');

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
        $this->cagir('mergeRuntimeConfig');

        $mailer = config('mail.mailers.kahya-gonderim');
        $this->assertIsArray($mailer);
        $this->assertSame('SES-GIZLI-PAROLA', $mailer['password']);
    }

    /**
     * BÜTÜNCÜL BEKÇİ — tek tek anahtar kontrolü yetmez.
     *
     * Yukarıdaki testler "şu anahtarda sır yok" der; bu test "config ağacının
     * HİÇBİR yerinde sır yok" der. Sır beklenmedik bir anahtara sızarsa (yeni bir
     * merge metodu, bir paketin config'i, kopyalanmış bir alan) ötekiler yeşil
     * kalır, bu kırılır. Düşmanca inceleme "testler üretilen config'in tamamına
     * hiç bakmıyor" dedi — bu ona cevap.
     */
    public function test_onbellek_kurulurken_config_agacinin_hicbir_yerinde_sir_kalmaz(): void
    {
        $sirlar = [
            'SENTINEL-MAIL-PAROLA', 'SENTINEL-AI-ANAHTAR',
            'SENTINEL-PLACES-ANAHTAR', 'SENTINEL-SES-PAROLA',
        ];

        Settings::setMany([
            'mail.host' => 'smtp.ornek.com',
            'mail.password' => 'SENTINEL-MAIL-PAROLA',
            'ai.saglayici' => 'openrouter',
            'ai.api_anahtari' => 'SENTINEL-AI-ANAHTAR',
            'growth.google_places_api_key' => 'SENTINEL-PLACES-ANAHTAR',
            'kahya.gonderim_host' => 'email-smtp.eu-central-1.amazonaws.com',
            'kahya.gonderim_parola' => 'SENTINEL-SES-PAROLA',
        ]);

        $this->argvKur(['artisan', 'config:cache']);
        $this->cagir('mergeRuntimeConfig');

        // config:cache'in diske yazdığı şey tam olarak budur.
        $yazilacak = var_export(config()->all(), true);

        foreach ($sirlar as $sir) {
            $this->assertStringNotContainsString($sir, $yazilacak,
                "Sır config ağacında kalmış: {$sir} — config:cache bunu diske düz metin yazardı.");
        }
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
