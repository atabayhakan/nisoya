<?php

namespace Tests\Feature;

use App\Models\BekleyenHamle;
use App\Services\Kahya\Dis\EngelListesi;
use App\Services\Kahya\Dis\HamleGonderici;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Symfony\Component\Mime\RawMessage;
use Tests\TestCase;

/**
 * Erişim postasında "listeden çık" (2026-08-07).
 *
 * ---------------------------------------------------------------------------
 * NEDEN VAR
 *
 * F4'te gönderim kuruldu ama çıkış yolu HİÇ yoktu: giden posta düz metindi,
 * ne `List-Unsubscribe` başlığı ne de gövdede bir bağlantı vardı. Çıkış yolu
 * bulamayan alıcının elindeki tek düğme "spam"dır ve şikâyet, gönderim
 * alanının itibarını çıkıştan kat kat pahalıya mal eder.
 *
 * AWS üretim erişimi talebinde de sorulan şey buydu.
 */
class KahyaGonderimCikisTest extends TestCase
{
    use RefreshDatabase;

    private function gonderimiYapilandir(): void
    {
        Settings::setMany([
            'kahya.gonderim_host' => 'smtp.ornek-gonderim.com',
            'kahya.gonderim_kullanici' => 'kahya',
            'kahya.gonderim_parola' => 'gizli',
            'kahya.gonderim_adresi' => 'merhaba@mail.nisoya.test',
            'kahya.gunluk_gonderim_limiti' => '10',
        ]);
        config(['mail.mailers.'.HamleGonderici::MAILER => ['transport' => 'array']]);
    }

    private function gonderilmisHamle(string $alici = 'info@dernek.test'): BekleyenHamle
    {
        $hamle = BekleyenHamle::create([
            'baslik' => 'Tanışma mesajı',
            'gerekce' => 'Dönem başı erişimi.',
            'icerik' => 'Merhaba, Nisoya yurtdışındaki Türkler için ücretsiz bir pazaryeri.',
            'tur' => 'eposta',
            'alici_eposta' => $alici,
        ])->refresh();
        $hamle->kararVer(BekleyenHamle::DURUM_ONAYLANDI);

        app(HamleGonderici::class)->gonder($hamle->refresh());

        return $hamle->refresh();
    }

    /** @return RawMessage */
    private function sonMesaj()
    {
        $mesajlar = iterator_to_array(Mail::mailer(HamleGonderici::MAILER)->getSymfonyTransport()->messages());

        return end($mesajlar)->getOriginalMessage();
    }

    // === Giden postanın taşıdıkları =================================

    public function test_giden_posta_tek_tik_cikis_basliklarini_tasir(): void
    {
        /*
         * İKİSİ BİRDEN olmalı. `List-Unsubscribe` tek başına varsa Gmail ve
         * Outlook mesajın tepesindeki "Abonelikten çık" düğmesini GÖSTERMEZ;
         * tek-tık akışını açan şey `List-Unsubscribe-Post` (RFC 8058).
         */
        $this->gonderimiYapilandir();
        $hamle = $this->gonderilmisHamle();

        $basliklar = $this->sonMesaj()->getHeaders();

        $this->assertSame(
            '<'.route('kahya.cikis', $hamle->cikis_jetonu).'>',
            $basliklar->get('List-Unsubscribe')->getBodyAsString(),
        );
        $this->assertSame(
            'List-Unsubscribe=One-Click',
            $basliklar->get('List-Unsubscribe-Post')->getBodyAsString(),
        );
    }

    public function test_govdede_de_gorunur_cikis_baglantisi_var(): void
    {
        // Başlık yetmez: onu yalnız bazı istemciler gösterir. Metindeki satır
        // çıkışın HER alıcı için ulaşılabilir olmasını garanti eder.
        $this->gonderimiYapilandir();
        $hamle = $this->gonderilmisHamle();

        $this->assertStringContainsString(
            route('kahya.cikis', $hamle->cikis_jetonu),
            $this->sonMesaj()->getTextBody(),
        );
    }

    public function test_jeton_gonderimde_uretilir_ve_tahmin_edilemez(): void
    {
        $this->gonderimiYapilandir();

        $a = $this->gonderilmisHamle('a@dernek.test');
        $b = $this->gonderilmisHamle('b@dernek.test');

        $this->assertNotNull($a->cikis_jetonu);
        $this->assertNotSame($a->cikis_jetonu, $b->cikis_jetonu);
        $this->assertGreaterThanOrEqual(32, strlen((string) $a->cikis_jetonu));
    }

    // === Çıkış akışı ================================================

    public function test_get_sayfayi_gosterir_ama_cikis_uygulamaz(): void
    {
        /*
         * EN ÖNEMLİ TEST. Kurumsal posta tarayıcıları ve önizleme botları
         * mesajdaki bağlantıları kendiliğinden AÇAR. GET çıkışı uygulasaydı
         * alıcı postayı okumadan listeden düşerdi — üstelik kimse nedenini
         * bilemezdi. Değiştiren eylem POST'ta durur.
         */
        $this->gonderimiYapilandir();
        $hamle = $this->gonderilmisHamle();

        $this->get(route('kahya.cikis', $hamle->cikis_jetonu))
            ->assertOk()
            ->assertSee('info@dernek.test');

        $this->assertFalse(app(EngelListesi::class)->engelliMi('info@dernek.test'));
    }

    public function test_post_adresi_kalici_engelle(): void
    {
        $this->gonderimiYapilandir();
        $hamle = $this->gonderilmisHamle();

        $this->post(route('kahya.cikis.uygula', $hamle->cikis_jetonu))
            ->assertOk()
            ->assertSee('Çıkışın alındı');

        $this->assertTrue(app(EngelListesi::class)->engelliMi('info@dernek.test'));
    }

    public function test_tek_tik_cikis_csrf_jetonu_olmadan_calisir(): void
    {
        /*
         * POST'u atan taraf bizim sayfamız değil, alıcının posta istemcisi —
         * CSRF jetonu taşıyamaz. Muafiyet kalkarsa tek-tık çıkış 419 döner ve
         * Gmail bunu "çıkış çalışmıyor" diye işaretler; SESSİZ bir bozulma.
         */
        $this->gonderimiYapilandir();
        $hamle = $this->gonderilmisHamle();

        $this->withMiddleware()
            ->post(route('kahya.cikis.uygula', $hamle->cikis_jetonu), ['List-Unsubscribe' => 'One-Click'])
            ->assertOk();

        $this->assertTrue(app(EngelListesi::class)->engelliMi('info@dernek.test'));
    }

    public function test_cikan_adrese_bir_daha_gonderilmez(): void
    {
        // Zincirin kapanması: çıkış gerçekten gönderimi durduruyor mu?
        $this->gonderimiYapilandir();
        $hamle = $this->gonderilmisHamle('istemiyor@dernek.test');
        $this->post(route('kahya.cikis.uygula', $hamle->cikis_jetonu));

        $yeni = BekleyenHamle::create([
            'baslik' => 'İkinci deneme', 'gerekce' => '.', 'icerik' => '.',
            'tur' => 'eposta', 'alici_eposta' => 'istemiyor@dernek.test',
        ])->refresh();
        $yeni->kararVer(BekleyenHamle::DURUM_ONAYLANDI);

        $sonuc = app(HamleGonderici::class)->gonder($yeni->refresh());

        $this->assertStringContainsString('engel listesinde', $sonuc);
        $this->assertNull($yeni->refresh()->gonderildi_at);
    }

    public function test_ikinci_kez_cikmak_hata_vermez(): void
    {
        $this->gonderimiYapilandir();
        $hamle = $this->gonderilmisHamle();

        $this->post(route('kahya.cikis.uygula', $hamle->cikis_jetonu))->assertOk();
        $this->post(route('kahya.cikis.uygula', $hamle->cikis_jetonu))->assertOk();

        $this->assertSame(1, DB::table(EngelListesi::TABLO)->where('eposta', 'info@dernek.test')->count());
    }

    public function test_gecersiz_jeton_404(): void
    {
        $this->get('/e-posta/cikis/'.str_repeat('x', 48))->assertNotFound();
        $this->post('/e-posta/cikis/'.str_repeat('x', 48))->assertNotFound();
    }

    public function test_gonderilmemis_hamlenin_jetonu_calismaz(): void
    {
        // Jeton gönderim anında yazılıyor; yine de koşul açıkça sınanıyor —
        // taslak bir kartın jetonu sızsa bile çıkış bağlantısına dönüşmesin.
        $hamle = BekleyenHamle::create([
            'baslik' => 'Taslak', 'gerekce' => '.', 'icerik' => '.',
            'tur' => 'eposta', 'alici_eposta' => 'kimse@dernek.test',
            'cikis_jetonu' => str_repeat('a', 48),
        ]);

        $this->get(route('kahya.cikis', $hamle->cikis_jetonu))->assertNotFound();
    }

    // === Engel listesi normalizasyonu ================================

    public function test_engel_listesi_buyuk_kucuk_harf_ayirmaz(): void
    {
        /*
         * Normalizasyon tek yerde olmazsa "Info@X" ile "info@x" iki ayrı kayıt
         * olur ve engel SESSİZCE delinir: liste dolu görünür, posta yine gider.
         */
        $engeller = app(EngelListesi::class);
        $engeller->engelle('  Info@Dernek.TEST  ', 'test');

        $this->assertTrue($engeller->engelliMi('info@dernek.test'));
        $this->assertTrue($engeller->engelliMi('INFO@DERNEK.TEST'));
    }

    public function test_gecersiz_adres_engel_listesine_girmez(): void
    {
        $engeller = app(EngelListesi::class);

        $this->assertFalse($engeller->engelle('adres-degil', 'test'));
        $this->assertFalse($engeller->engelle('', 'test'));
        $this->assertSame(0, DB::table(EngelListesi::TABLO)->count());
    }
}
