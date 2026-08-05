<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use App\Support\HataKayitlari;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * "Son Hatalar" ekranı (2026-08-05).
 *
 * ---------------------------------------------------------------------------
 * NEDEN VAR
 *
 * El Kitabı canlıda 500 verdi ve sebebi bulmak için sahibe ÜÇ KEZ sunucuda
 * komut çalıştırttım (Claude'un SSH erişimi yok). Hata tek satırlıktı; onu
 * GÖRMEK yarım saat aldı. Bu ekran o bağımlılığı kaldırır.
 */
class SonHatalarTest extends TestCase
{
    use RefreshDatabase;

    private string $klasor;

    protected function setUp(): void
    {
        parent::setUp();

        /*
         * İZOLE KLASÖR ŞART: ilk yazışta gerçek `storage/logs` okunuyordu ve
         * testler geliştirme makinesindeki 25 kaydı bulup kırıldı. Kendi
         * verisini kuramayan bir test, ne olduğunu değil ne olduğunu sandığını
         * ölçer.
         */
        $this->klasor = storage_path('framework/testing/loglar-'.uniqid());
        mkdir($this->klasor, 0755, true);

        $this->app->bind(HataKayitlari::class, fn () => new HataKayitlari($this->klasor));
    }

    protected function tearDown(): void
    {
        foreach (glob($this->klasor.'/*') ?: [] as $dosya) {
            @unlink($dosya);
        }
        @rmdir($this->klasor);

        parent::tearDown();
    }

    private function logYaz(string $icerik): void
    {
        file_put_contents($this->klasor.'/laravel-2026-08-05.log', $icerik);
    }

    private function admin(): User
    {
        return User::factory()->create(['role' => UserRole::Admin]);
    }

    public function test_gercek_bir_laravel_hata_satiri_ayristirilir(): void
    {
        // CANLIDAKİ HATANIN BİREBİR BİÇİMİ — teşhis edebildiğimizi kanıtlar.
        $this->logYaz(<<<'LOG'
        [2026-08-05 19:02:09] production.ERROR: App\Services\Rehber\ElKitabiRehberi::tumSayfalar(): Return value must be of type Illuminate\Support\Collection, __PHP_Incomplete_Class returned {"exception":"[object] (TypeError(code: 0): App\\Services\\Rehber\\ElKitabiRehberi::tumSayfalar() at /var/www/nisoya/app/Services/Rehber/ElKitabiRehberi.php:55)
        [stacktrace]
        #0 {main}
        "}
        LOG);

        $hatalar = app(HataKayitlari::class)->sonHatalar();

        $this->assertCount(1, $hatalar);
        $this->assertSame('ERROR', $hatalar[0]['seviye']);
        $this->assertSame('2026-08-05 19:02:09', $hatalar[0]['zaman']);
        $this->assertSame('TypeError', $hatalar[0]['sinif']);
        $this->assertSame('ElKitabiRehberi.php:55', $hatalar[0]['yer']);
        $this->assertStringContainsString('__PHP_Incomplete_Class', $hatalar[0]['mesaj']);
    }

    public function test_baglam_yuku_mesaja_sizmaz(): void
    {
        /*
         * Log satırları e-posta, mesaj metni ve form girdisi içerebilir. Bir
         * teşhis ekranının bunları sayfaya dökmesi gereksiz bir sızıntı
         * yüzeyi olurdu — teşhis için dosya:satır zaten yeterli.
         */
        $this->logYaz('[2026-08-05 10:00:00] production.ERROR: Bir hata {"exception":"[object] (Exception(code: 0): gizli at /app/X.php:9)","email":"kullanici@ornek.com"}');

        $hatalar = app(HataKayitlari::class)->sonHatalar();

        $this->assertStringNotContainsString('kullanici@ornek.com', $hatalar[0]['mesaj']);
        $this->assertSame('Bir hata', $hatalar[0]['mesaj']);
    }

    public function test_uyari_ve_bilgi_satirlari_gosterilmez(): void
    {
        // Ekranın işi HATA göstermek; gürültü onu okunmaz yapar.
        $this->logYaz(implode("\n", [
            '[2026-08-05 10:00:00] production.INFO: sadece bilgi',
            '[2026-08-05 10:00:01] production.WARNING: sadece uyari',
            '[2026-08-05 10:00:02] production.ERROR: gercek hata',
        ]));

        $hatalar = app(HataKayitlari::class)->sonHatalar();

        $this->assertCount(1, $hatalar);
        $this->assertSame('gercek hata', $hatalar[0]['mesaj']);
    }

    public function test_log_dosyasi_yoksa_hata_yok_denmez(): void
    {
        /*
         * "Hata yok" ile "kayıt tutulmuyor" APAYRI şeylerdir. İkincisini
         * birincisi gibi göstermek, olmayan bir güvence vermek olurdu —
         * üstelik hata sayfamız ziyaretçiye "kaydedildi" diyor.
         */
        // Klasör boş (setUp yeni oluşturdu) — "hata yok" değil "kayıt yok".
        $kayitlar = app(HataKayitlari::class);

        $this->assertFalse($kayitlar->kayitTutuluyorMu());
        $this->assertSame([], $kayitlar->sonHatalar());

        $this->actingAs($this->admin())
            ->get('/yonetim/son-hatalar')
            ->assertOk()
            ->assertSee('Hiç log dosyası yok')
            ->assertSee('Bu "hata yok" demek değil', false);
    }

    public function test_ekran_yalniz_admine_acik(): void
    {
        $uye = User::factory()->create(['role' => UserRole::Uye]);
        $this->actingAs($uye)->get('/yonetim/son-hatalar')->assertRedirect(route('dashboard'));

        $moderator = User::factory()->create(['role' => UserRole::Moderator]);
        $this->actingAs($moderator)->get('/yonetim/son-hatalar')->assertForbidden();
    }

    public function test_admin_ekrani_acabilir_ve_hatayi_gorur(): void
    {
        $this->logYaz('[2026-08-05 19:02:09] production.ERROR: Gorunur olmali {"exception":"[object] (TypeError(code: 0): x at /app/Y.php:12)"}');

        $this->actingAs($this->admin())
            ->get('/yonetim/son-hatalar')
            ->assertOk()
            ->assertSee('Gorunur olmali')
            ->assertSee('TypeError')
            ->assertSee('Y.php:12');
    }
}
