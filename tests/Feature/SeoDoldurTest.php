<?php

namespace Tests\Feature;

use App\Models\KahyaEylemKaydi;
use App\Services\Kahya\Eylem\EylemCalistirici;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * seo-doldur — "SEO'yu Kâhya otomatik yapsın" eyleminin sözleşmesi.
 *
 * Metni sohbet modeli yazar (parametre olarak gelir); burada sınanan şey:
 * onay kapısı (okunmamış makine metni yayına girmez), önizlemenin yeni metni
 * göstermesi ve geri almanın siteyi birebir eski hâline döndürmesi.
 */
class SeoDoldurTest extends TestCase
{
    use RefreshDatabase;

    private const BASLIK = 'Nisoya — Gurbetçinin Türkçe Pazaryeri';

    private const ACIKLAMA = 'Yurt dışındaki Türkler için yetenek, hizmet ve ev ürünleri pazaryeri. Kendi insanından güvenle al, yeteneğini gelire dönüştür.';

    private function calistirici(): EylemCalistirici
    {
        return app(EylemCalistirici::class);
    }

    public function test_onay_bekler_ve_onizleme_yeni_metni_gosterir(): void
    {
        $kayit = $this->calistirici()->calistir('seo-doldur', [
            'baslik' => self::BASLIK,
            'aciklama' => self::ACIKLAMA,
        ]);

        // Model metni yazdı ama sahip okumadan yayına girmedi.
        $this->assertSame(KahyaEylemKaydi::DURUM_BEKLEMEDE, $kayit->durum);
        $this->assertStringContainsString(self::BASLIK, $kayit->onizleme);
        $this->assertStringContainsString(self::ACIKLAMA, $kayit->onizleme);
        $this->assertDatabaseMissing('site_settings', ['key' => 'seo.default_title']);
    }

    public function test_onaylaninca_yazilir_geri_alinca_eski_hal_doner(): void
    {
        $varsayilanBaslik = Settings::get('seo.default_title');

        $kayit = $this->calistirici()->calistir('seo-doldur', [
            'baslik' => self::BASLIK,
            'aciklama' => self::ACIKLAMA,
        ]);

        $kayit = $this->calistirici()->onayla($kayit);

        $this->assertSame(KahyaEylemKaydi::DURUM_UYGULANDI, $kayit->durum);
        $this->assertSame(self::BASLIK, Settings::get('seo.default_title'));
        $this->assertSame(self::ACIKLAMA, Settings::get('seo.default_description'));

        $kayit = $this->calistirici()->geriAl($kayit);

        $this->assertSame(KahyaEylemKaydi::DURUM_GERI_ALINDI, $kayit->durum);
        // Tablo hiç doldurulmamıştı: geri alma siteyi config varsayılanına
        // döndürmeli — modelin metnini tabloda bırakmamalı.
        $this->assertSame($varsayilanBaslik, Settings::get('seo.default_title'));
    }

    public function test_cok_kisa_metin_reddedilir(): void
    {
        $kayit = $this->calistirici()->calistir('seo-doldur', [
            'baslik' => 'Nisoya',
            'aciklama' => 'Kısa.',
        ]);

        $this->assertSame(KahyaEylemKaydi::DURUM_HATA, $kayit->durum);
        $this->assertDatabaseMissing('site_settings', ['key' => 'seo.default_title']);
    }
}
