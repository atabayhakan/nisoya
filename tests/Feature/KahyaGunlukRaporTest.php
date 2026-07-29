<?php

namespace Tests\Feature;

use App\Models\KahyaCalismasi;
use App\Notifications\GunlukKahyaRaporu;
use App\Support\Settings;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Günlük Kâhya raporu komutu (Faz C/2).
 *
 * En kritik davranış: SESSİZ ÖLÜM KORUMASI. Zamanlanmış komutlar sessizce
 * ölür — cron kapanır, kuyruk durur, SMTP susar. Hepsinde sahibin gördüğü
 * şey aynıdır: gelen kutusunda rapor yok. "Rapor gelmedi" ile "rapor geldi,
 * her şey yolundaydı" ayırt edilemezse sistem işe yaramaz.
 *
 * Bu yüzden her koşu deftere yazılır — GÖNDERİM BAŞARISIZ OLSA BİLE.
 */
class KahyaGunlukRaporTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CurrencySeeder::class, CountrySeeder::class, CategorySeeder::class]);
    }

    public function test_rapor_uretilir_ve_gonderilir(): void
    {
        Notification::fake();
        Settings::setMany(['kahya.alici' => 'yonetici@ornek.com']);

        $this->artisan('kahya:gunluk-rapor')->assertSuccessful();

        Notification::assertSentOnDemand(GunlukKahyaRaporu::class);

        $kayit = KahyaCalismasi::query()->gunlukRapor()->firstOrFail();
        $this->assertTrue($kayit->gonderildi);
        $this->assertSame('yonetici@ornek.com', $kayit->alici);
        $this->assertNull($kayit->hata);
    }

    public function test_gonderme_bayragi_eposta_atmaz_ama_deftere_yazar(): void
    {
        Notification::fake();
        Settings::setMany(['kahya.alici' => 'yonetici@ornek.com']);

        $this->artisan('kahya:gunluk-rapor', ['--gonderme' => true])->assertSuccessful();

        Notification::assertNothingSent();
        $this->assertSame(1, KahyaCalismasi::query()->gunlukRapor()->count(), 'Deneme koşusu da deftere yazılmalı.');
    }

    /**
     * ASIL BEKÇİ: alıcı tanımlı değilse komut ÇÖKMEZ, deftere hatayı yazar.
     *
     * Çökerse defter kaydı da yazılmaz ve "en son ne zaman koştu" bilgisi
     * kaybolur — ki bu sistemin tek erken uyarı işaretidir.
     */
    public function test_alici_yoksa_cokmez_hatayi_deftere_yazar(): void
    {
        Notification::fake();

        // Settings::get boş değerde config/site_defaults'a DÜŞER — yani
        // `iletisim.eposta` boşaltılsa bile oradaki varsayılan (destek@…)
        // döner ve alıcı hiç boş kalmaz. Bu, uygulamanın doğru davranışı;
        // "alıcı yok" dalına ulaşmak için varsayılanı da kaldırmak gerekir.
        config(['site_defaults.fields' => []]);
        Settings::setMany(['kahya.alici' => '', 'iletisim.eposta' => '']);

        $this->artisan('kahya:gunluk-rapor')->assertSuccessful();

        Notification::assertNothingSent();

        $kayit = KahyaCalismasi::query()->gunlukRapor()->firstOrFail();
        $this->assertFalse($kayit->gonderildi);
        $this->assertNotNull($kayit->hata);
        $this->assertStringContainsString('Alıcı', $kayit->hata);
    }

    /**
     * Rapor üretilip gönderilemese bile İÇERİK saklanır — e-posta tek
     * dağıtım kanalı değildir, panelden okunabilmeli.
     */
    public function test_gonderilemese_de_icerik_saklanir(): void
    {
        Notification::fake();
        config(['site_defaults.fields' => []]);
        Settings::setMany(['kahya.alici' => '', 'iletisim.eposta' => '']);

        $this->artisan('kahya:gunluk-rapor')->assertSuccessful();

        $ozet = KahyaCalismasi::query()->gunlukRapor()->firstOrFail()->ozet;

        $this->assertArrayHasKey('teshis', $ozet);
        $this->assertArrayHasKey('envanter', $ozet['teshis']);
        $this->assertArrayHasKey('bekleyen', $ozet['teshis']);
    }

    public function test_gecersiz_eposta_adresi_kullanilmaz(): void
    {
        Notification::fake();
        Settings::setMany(['kahya.alici' => 'bu-bir-eposta-degil', 'iletisim.eposta' => 'gecerli@ornek.com']);

        $this->artisan('kahya:gunluk-rapor')->assertSuccessful();

        $this->assertSame(
            'gecerli@ornek.com',
            KahyaCalismasi::query()->gunlukRapor()->firstOrFail()->alici,
            'Geçersiz adres atlanıp yedeğe düşülmeli.'
        );
    }

    /**
     * Boş günde de gönderilir: sessizlik "her şey yolunda" ile "komut çöktü"yü
     * ayırt edilemez kılar.
     */
    public function test_bos_gunde_de_rapor_gonderilir(): void
    {
        Notification::fake();
        Settings::setMany(['kahya.alici' => 'yonetici@ornek.com']);

        $this->artisan('kahya:gunluk-rapor')->assertSuccessful();

        Notification::assertSentOnDemand(GunlukKahyaRaporu::class);
    }

    public function test_son_kosu_yasi_hesaplanir(): void
    {
        $this->assertNull(KahyaCalismasi::sonKosuYasiSaat(), 'Hiç koşmamışken null dönmeli — 0 ile karıştırılmamalı.');

        Notification::fake();
        Settings::setMany(['kahya.alici' => 'yonetici@ornek.com']);
        $this->artisan('kahya:gunluk-rapor')->assertSuccessful();

        $this->assertSame(0, KahyaCalismasi::sonKosuYasiSaat());
    }

    public function test_zamanlayiciya_kayitli(): void
    {
        $this->artisan('schedule:list')
            ->expectsOutputToContain('kahya:gunluk-rapor')
            ->assertSuccessful();
    }
}
