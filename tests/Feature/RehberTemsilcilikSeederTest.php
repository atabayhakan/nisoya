<?php

namespace Tests\Feature;

use App\Models\Country;
use App\Models\Temsilcilik;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Database\Seeders\RehberTemsilcilikleriSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Temsilcilik seeder'ı DEPLOY ZİNCİRİNDE çalışıyor (ReferenceDataSeeder).
 *
 * Bu yüzden en kritik bekçi `panelden_yapilan_duzeltme_ezilmez`: `updateOrCreate`
 * kullanılsaydı her deploy panelden yapılan düzeltmeleri geri alırdı — sessiz ve
 * bulması zor bir hata. Seeder yalnız ÖLÇÜLMÜŞ kırık adres desenini onarır.
 */
class RehberTemsilcilikSeederTest extends TestCase
{
    use RefreshDatabase;

    private function tohumla(): void
    {
        $this->seed(CurrencySeeder::class);
        $this->seed(CountrySeeder::class);
        $this->seed(RehberTemsilcilikleriSeeder::class);
    }

    public function test_abd_ve_kirgizistan_temsilcilikleri_eklenir(): void
    {
        $this->tohumla();

        // ABD: 1 büyükelçilik + 6 KARİYER başkonsolosluğu. Fahri olanlar
        // bilerek yok — konsolosluk işlemi yapmıyorlar.
        $this->assertSame(7, Temsilcilik::where('country_code', 'US')->count());
        $this->assertSame(1, Temsilcilik::where('country_code', 'KG')->count());
    }

    public function test_biskek_yonlendirme_notu_tasir(): void
    {
        $this->tohumla();

        $biskek = Temsilcilik::where('slug', 'biskek-buyukelciligi')->first();

        $this->assertNotNull($biskek);
        $this->assertNotEmpty($biskek->yonlendirme_notu);
    }

    public function test_kirik_noktali_adres_onarilir(): void
    {
        Country::firstOrCreate(['code' => 'DE'], ['name_tr' => 'Almanya', 'emoji' => '🇩🇪', 'is_active' => true]);

        // RehberAlmanyaSeeder'ın ürettiği biçim. Ölçüldü: DNS'te hiç çözülmüyor.
        $koln = Temsilcilik::create([
            'country_code' => 'DE', 'ad' => 'Köln Başkonsolosluğu', 'slug' => 'koeln',
            'tur' => Temsilcilik::TUR_BASKONSOLOSLUK, 'sehir' => 'Köln',
            'resmi_url' => 'https://koln.bk.mfa.gov.tr', 'is_active' => true, 'sort_order' => 8,
        ]);

        $this->seed(RehberTemsilcilikleriSeeder::class);

        $this->assertSame('https://koln-bk.mfa.gov.tr', $koln->fresh()->resmi_url);
    }

    public function test_panelden_yapilan_duzeltme_ezilmez(): void
    {
        Country::firstOrCreate(['code' => 'DE'], ['name_tr' => 'Almanya', 'emoji' => '🇩🇪', 'is_active' => true]);

        // Sahip panelden adresi ve adı düzeltmiş olsun. Seeder deploy'da
        // koştuğunda bunları GERİ ALMAMALI.
        $koln = Temsilcilik::create([
            'country_code' => 'DE', 'ad' => 'Köln Başkonsolosluğu (yeni bina)', 'slug' => 'koeln',
            'tur' => Temsilcilik::TUR_BASKONSOLOSLUK, 'sehir' => 'Köln',
            'resmi_url' => 'https://koln-bk.mfa.gov.tr/ozel-sayfa',
            'adres' => 'Sahibin girdiği adres', 'is_active' => true, 'sort_order' => 8,
        ]);

        $this->seed(RehberTemsilcilikleriSeeder::class);

        $taze = $koln->fresh();
        $this->assertSame('https://koln-bk.mfa.gov.tr/ozel-sayfa', $taze->resmi_url);
        $this->assertSame('Köln Başkonsolosluğu (yeni bina)', $taze->ad);
        $this->assertSame('Sahibin girdiği adres', $taze->adres);
    }

    public function test_iki_kez_kosmak_yeni_kayit_uretmez(): void
    {
        $this->tohumla();
        $ilk = Temsilcilik::count();

        $this->seed(RehberTemsilcilikleriSeeder::class);

        $this->assertSame($ilk, Temsilcilik::count());
    }

    public function test_adres_ve_koordinat_dolduruluyor(): void
    {
        $this->tohumla();

        $berlin = Temsilcilik::where('slug', 'berlin-buyukelciligi')->first();

        $this->assertNotEmpty($berlin->adres);
        $this->assertNotNull($berlin->latitude);
        $this->assertNotNull($berlin->longitude);
    }

    public function test_panelden_girilen_adres_ezilmez(): void
    {
        Country::firstOrCreate(['code' => 'DE'], ['name_tr' => 'Almanya', 'emoji' => '🇩🇪', 'is_active' => true]);

        // Sahip panelden adresi düzeltmiş olsun (ör. bina taşındı).
        $koln = Temsilcilik::create([
            'country_code' => 'DE', 'ad' => 'Köln Başkonsolosluğu', 'slug' => 'koeln',
            'tur' => Temsilcilik::TUR_BASKONSOLOSLUK, 'sehir' => 'Köln',
            'resmi_url' => 'https://koln-bk.mfa.gov.tr',
            'adres' => 'Sahibin girdiği yeni bina adresi',
            'latitude' => 1.111111, 'longitude' => 2.222222,
            'is_active' => true, 'sort_order' => 8,
        ]);

        $this->seed(RehberTemsilcilikleriSeeder::class);

        $taze = $koln->fresh();
        $this->assertSame('Sahibin girdiği yeni bina adresi', $taze->adres);
        $this->assertEquals(1.111111, $taze->latitude);
    }
}
