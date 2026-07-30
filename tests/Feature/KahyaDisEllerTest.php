<?php

namespace Tests\Feature;

use App\Models\BekleyenHamle;
use App\Models\KahyaEylemKaydi;
use App\Services\Kahya\Dis\HamleGonderici;
use App\Services\Kahya\Eylem\EylemCalistirici;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

/**
 * Dış eller (F4): onaylanan e-posta hamlesinin GERÇEK gönderimi.
 *
 * Sınanan sözleşme: gönderim yalnız AYRI kimlikle yapılır (yapılandırma
 * eksikse gönderim yok, ana mailer'a fallback yok), günlük ısıtma tavanı,
 * engel listesi, çift-gönderim koruması ve her sonucun dürüst cümlesi.
 */
class KahyaDisEllerTest extends TestCase
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
        // AppServiceProvider boot'ta koştu; ayarlar testte sonradan yazıldığı
        // için mailer config'ini burada elle eşliyoruz (aynı eşleme).
        config(['mail.mailers.'.HamleGonderici::MAILER => [
            'transport' => 'array',
        ]]);
    }

    /** Array transport'a düşen gerçek mesajlar. @return list<mixed> */
    private function gidenler(): array
    {
        return iterator_to_array(Mail::mailer(HamleGonderici::MAILER)->getSymfonyTransport()->messages());
    }

    private function onayliHamle(?string $alici = 'info@dernek.test'): BekleyenHamle
    {
        $hamle = BekleyenHamle::create([
            'baslik' => 'TUSU tanıtım mesajı',
            'gerekce' => 'Dönem başı erişimi.',
            'icerik' => 'Merhaba, Nisoya yurtdışındaki Türkler için ücretsiz bir pazaryeri...',
            'tur' => 'eposta',
            'alici_eposta' => $alici,
        ])->refresh(); // durum'un DB varsayılanı (beklemede) belleğe insin
        $hamle->kararVer(BekleyenHamle::DURUM_ONAYLANDI);

        return $hamle->refresh();
    }

    public function test_yapilandirilmamis_gonderim_durustce_soyler_ve_gondermez(): void
    {
        Mail::fake();

        $sonuc = app(HamleGonderici::class)->gonder($this->onayliHamle());

        $this->assertStringContainsString('yapılandırılmamış', $sonuc);
        $this->assertStringContainsString('elle uygula', $sonuc);
        Mail::assertNothingSent();
    }

    public function test_yapilandirilmis_gonderim_calisir_ve_izi_yazar(): void
    {
        $this->gonderimiYapilandir();

        $hamle = $this->onayliHamle();
        $sonuc = app(HamleGonderici::class)->gonder($hamle);

        $this->assertStringContainsString('Gönderildi: info@dernek.test', $sonuc);
        $this->assertNotNull($hamle->refresh()->gonderildi_at);
        $this->assertCount(1, $this->gidenler());
    }

    public function test_ayni_hamle_ikinci_kez_gonderilmez(): void
    {
        $this->gonderimiYapilandir();

        $hamle = $this->onayliHamle();
        app(HamleGonderici::class)->gonder($hamle);
        $sonuc = app(HamleGonderici::class)->gonder($hamle->refresh());

        $this->assertStringContainsString('ikinci kez gönderilmez', $sonuc);
        $this->assertCount(1, $this->gidenler());
    }

    public function test_gunluk_isitma_tavani_asilamaz(): void
    {
        $this->gonderimiYapilandir();
        Settings::setMany(['kahya.gunluk_gonderim_limiti' => '1']);

        app(HamleGonderici::class)->gonder($this->onayliHamle('a@dernek.test'));
        $sonuc = app(HamleGonderici::class)->gonder($this->onayliHamle('b@dernek.test'));

        $this->assertStringContainsString('ısıtma tavanı dolu (1/1)', $sonuc);
        $this->assertCount(1, $this->gidenler());
        // Tavan hatası kalıcı iz bırakmaz — yarın yeniden denenebilir.
        $this->assertNull(BekleyenHamle::query()->where('alici_eposta', 'b@dernek.test')->firstOrFail()->gonderim_hata);
    }

    public function test_engelli_adrese_gonderilmez(): void
    {
        $this->gonderimiYapilandir();
        DB::table('kahya_gonderim_engelleri')->insert([
            'eposta' => 'istemiyor@dernek.test',
            'neden' => 'Ret yanıtı geldi.',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        Mail::fake();

        $sonuc = app(HamleGonderici::class)->gonder($this->onayliHamle('istemiyor@dernek.test'));

        $this->assertStringContainsString('engel listesinde', $sonuc);
        Mail::assertNothingSent();
    }

    public function test_alicisiz_eposta_hamlesi_gonderilmez(): void
    {
        $this->gonderimiYapilandir();
        Mail::fake();

        $sonuc = app(HamleGonderici::class)->gonder($this->onayliHamle(null));

        $this->assertStringContainsString('geçerli bir alıcı adresi yok', $sonuc);
        Mail::assertNothingSent();
    }

    public function test_onaysiz_hamle_gonderilmez(): void
    {
        $this->gonderimiYapilandir();
        Mail::fake();

        $hamle = BekleyenHamle::create([
            'baslik' => 'Onaysız hamle',
            'gerekce' => 'Onay kapısı sözleşmesi.',
            'icerik' => 'Bu içerik sahibin onayı olmadan asla gönderilmemeli.',
            'tur' => 'eposta',
            'alici_eposta' => 'info@dernek.test',
        ]);

        $sonuc = app(HamleGonderici::class)->gonder($hamle);

        $this->assertStringContainsString('onaylı değil', $sonuc);
        Mail::assertNothingSent();
    }

    public function test_hamle_oner_eposta_turunde_alici_ister(): void
    {
        $kayit = app(EylemCalistirici::class)->calistir('hamle-oner', [
            'baslik' => 'Alıcısız e-posta önerisi',
            'gerekce' => 'Doğrulama sözleşmesi testi.',
            'icerik' => 'Bu kart alıcı olmadan e-posta türünde açılamamalı.',
            'tur' => 'eposta',
        ]);

        $this->assertSame(KahyaEylemKaydi::DURUM_HATA, $kayit->durum);
    }
}
