<?php

namespace Tests\Feature;

use App\Support\Settings;
use App\Support\Tema;
use Database\Seeders\GuvenliAlisverisSayfasiSeeder;
use Database\Seeders\StaticPagesSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * "Biri gelip üye olmak isteseydi güvenir miydi?" — Tur 1 (2026-08-05).
 *
 * ---------------------------------------------------------------------------
 * NEREDEN ÇIKTI
 *
 * Sahibin eleştirisi: aylardır özellik ekleniyor ama ürün bir kullanıcı gibi
 * baştan sona YÜRÜNMEMİŞ. Beş ajanlık bir denetim kuruldu; bu dosya o
 * denetimin "güven" başlığındaki bulgularını mühürlüyor.
 *
 * Bulguların ortak yanı: hiçbiri bağlantı denetleyicisiyle bulunamazdı.
 * Hiçbiri 404 vermiyordu — ya İngilizceydi, ya hiç yoktu, ya yanlış yere
 * götürüyordu.
 */
class GuvenIlkIzlenimTest extends TestCase
{
    use RefreshDatabase;

    /** @return array<string, array{string}> */
    public static function temalar(): array
    {
        return ['klasik' => ['klasik'], 'vitrin' => ['vitrin']];
    }

    private function temayiSec(string $tema): void
    {
        Settings::setMany(['gorunum.tema' => $tema]);
        Cache::flush();
        $this->assertSame($tema === 'vitrin', Tema::vitrinMi());
    }

    // ------------------------------------------------- Hata sayfaları

    public function test_404_turkce_markali_ve_cikisli(): void
    {
        /*
         * Canlıda doğrulanmıştı: lang="en", "Not Found", Nisoya yok, ana
         * sayfaya dönüş bağlantısı yok. Türkçe bir siteye gelen ziyaretçi
         * bozuk bir bağlantıda "bu site terk edilmiş" hissi alıyordu.
         */
        $yanit = $this->get('/boyle-bir-sayfa-yok-12345')->assertNotFound();

        $icerik = $yanit->getContent();

        $this->assertStringContainsString('lang="tr"', $icerik);
        $this->assertStringContainsString('Bu sayfa yok', $icerik);
        $this->assertStringContainsString('Nisoya', $icerik);
        $this->assertStringContainsString('href="/"', $icerik, 'Ana sayfaya dönüş yolu yok.');
    }

    public function test_hata_sayfasi_veritabanina_ve_derlemeye_bagimli_degil(): void
    {
        /*
         * EN ÖNEMLİ TASARIM KARARI. Bir 500 hatasının en olası sebepleri
         * veritabanı erişimi ve derleme sorunlarıdır. Hata sayfası bunlara
         * bağımlı olsaydı, hatayı gösterirken KENDİSİ çöker ve kullanıcı yine
         * çıplak sunucu hatası görürdü.
         */
        $icerik = $this->get('/boyle-bir-sayfa-yok-12345')->getContent();

        $this->assertStringNotContainsString('build/assets', $icerik, 'Hata sayfası Vite varlığı yüklüyor — derleme bozuksa kendisi de çöker.');
        $this->assertStringNotContainsString('@vite', $icerik);
        // Stil gömülü olmalı ki harici bir dosyaya ihtiyaç duymasın.
        $this->assertStringContainsString('<style>', $icerik);
    }

    public function test_tum_hata_sayfalari_var_ve_turkce(): void
    {
        foreach (['403', '404', '419', '429', '500', '503'] as $kod) {
            $this->assertTrue(
                view()->exists("errors.{$kod}"),
                "errors/{$kod}.blade.php yok — o durumda çıplak İngilizce sayfaya düşülür."
            );
        }
    }

    // ------------------------------------------- Misafir güven sinyalleri

    #[DataProvider('temalar')]
    public function test_kayit_sayfasinda_kurumsal_baglantilar_var(string $tema): void
    {
        /*
         * /kayit ve /giris `layouts.guest` kullanıyor ve içinde site footer'ı
         * YOKTU. Bir reklamdan doğrudan kayıt sayfasına gelen kişi, e-posta ve
         * parola vermeden önce sitenin kim olduğuna dair hiçbir bağımsız
         * işaret göremiyordu — bağlantılar yalnız onay kutusunun metnindeydi.
         */
        $this->temayiSec($tema);

        $icerik = $this->get('/kayit')->assertOk()->getContent();

        foreach (['/hakkimizda', '/gizlilik', '/kosullar', '/iletisim', '/guvenli-alisveris'] as $yol) {
            $this->assertStringContainsString($yol, $icerik, "[$tema] Kayıt sayfasında {$yol} bağlantısı yok.");
        }
    }

    #[DataProvider('temalar')]
    public function test_kurumsal_sayfalarin_hepsi_gercekten_aciliyor(string $tema): void
    {
        // Bağlantı koymak yetmez; hedefi olmayan bağlantı tutulmamış vaattir.
        $this->temayiSec($tema);
        $this->seed(StaticPagesSeeder::class);
        $this->seed(GuvenliAlisverisSayfasiSeeder::class);

        foreach (['/hakkimizda', '/gizlilik', '/kosullar', '/iletisim', '/guvenli-alisveris'] as $yol) {
            $this->get($yol)->assertOk();
        }
    }

    // ------------------------------------------- Mobil üyelik yolu

    #[DataProvider('temalar')]
    public function test_misafir_mobilde_uye_ol_dugmesini_gorur(string $tema): void
    {
        /*
         * "Kayıt" yalnız `hidden md:inline-block` ile masaüstündeydi; telefonda
         * üye olmanın başlıkta HİÇBİR yolu yoktu. Ziyaretçi ya footer'a kadar
         * kaydıracak ya "Giriş"e basıp oradaki bağlantıyı bulacaktı. Nisoya'nın
         * kitlesi ağırlıkla telefonda.
         */
        $this->temayiSec($tema);

        $icerik = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('Üye ol', $icerik, "[$tema] Mobil başlıkta kayıt düğmesi yok.");
        $this->assertMatchesRegularExpression('/md:hidden(?:(?!<\/div>).)*Üye ol/su', $icerik, "[$tema] Kayıt düğmesi mobile özel blokta değil.");
    }

    #[DataProvider('temalar')]
    public function test_misafirin_ilan_ver_dugmesi_kayda_goturur(string $tema): void
    {
        /*
         * Rota `auth` altında olduğu için misafir /giris'e düşüyordu: hesabı
         * OLMAYAN kişi "hemen ilan vereyim" diye dokunup GİRİŞ formu görüyordu.
         */
        $this->temayiSec($tema);

        $icerik = $this->get('/')->assertOk()->getContent();

        $this->assertMatchesRegularExpression(
            '/href="[^"]*\/kayit"(?:(?!<\/a>).)*İlan Ver/su',
            $icerik,
            "[$tema] Misafirin \"İlan Ver\" düğmesi hâlâ kayıt sayfasına gitmiyor."
        );
    }

    #[DataProvider('temalar')]
    public function test_misafir_temayi_degistirebilir(string $tema): void
    {
        /*
         * GERİLEME DÜZELTMESİ: 2026-08-05'te başlıktaki tema düğmesi mobilde
         * gizlenip "hesap sayfasında var" denmişti; ama oradaki düğme @auth
         * bloğunun içindeydi. Giriş yapmamış mobil ziyaretçi temayı HİÇ
         * değiştiremiyordu. Artık Keşfet alt sayfasında, herkese açık.
         */
        $this->temayiSec($tema);

        $icerik = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('Karanlık / aydınlık', $icerik, "[$tema] Misafir için tema seçeneği yok.");
    }
}
