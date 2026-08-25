<?php

namespace Tests\Feature;

use App\Models\Country;
use App\Models\Temsilcilik;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Temsilcilik::haritaBaglantilari() — Google Haritalar + doğrulanmış yerel
 * alternatif (2GIS). docs/plans/2026-08-25-…
 */
class TemsilcilikHaritaTest extends TestCase
{
    use RefreshDatabase;

    private function temsilcilik(array $overrides = []): Temsilcilik
    {
        Country::firstOrCreate(['code' => 'DE'], ['name_tr' => 'Almanya', 'emoji' => '🇩🇪', 'is_active' => true]);

        return Temsilcilik::create(array_merge([
            'country_code' => 'DE',
            'ad' => 'Berlin Büyükelçiliği',
            'slug' => 'berlin-buyukelciligi',
            'tur' => Temsilcilik::TUR_BUYUKELCILIK,
            'sehir' => 'Berlin',
            'is_active' => true,
        ], $overrides));
    }

    public function test_koordinat_yoksa_bos_doner(): void
    {
        $t = $this->temsilcilik();

        $this->assertTrue($t->haritaBaglantilari()->isEmpty());
    }

    public function test_koordinat_varsa_google_haritalar_doner(): void
    {
        $t = $this->temsilcilik(['latitude' => 52.5097998, 'longitude' => 13.3560419]);

        $sonuc = $t->haritaBaglantilari();

        $this->assertCount(1, $sonuc);
        $this->assertSame('Google Haritalar', $sonuc->first()['ad']);
        $this->assertStringContainsString('52.5097998,13.3560419', $sonuc->first()['url']);
    }

    public function test_yerel_uygulamasi_olan_ulkede_ikinci_dugme_eklenir(): void
    {
        Country::firstOrCreate(['code' => 'KG'], ['name_tr' => 'Kırgızistan', 'emoji' => '🇰🇬', 'is_active' => true]);
        $t = $this->temsilcilik([
            'country_code' => 'KG', 'slug' => 'biskek', 'sehir' => 'Bişkek',
            'latitude' => 42.8699660, 'longitude' => 74.6083652,
        ]);

        $sonuc = $t->haritaBaglantilari();

        $this->assertCount(2, $sonuc);
        $this->assertSame('2GIS', $sonuc->last()['ad']);
        // 2GIS BOYLAM,ENLEM sırası bekliyor — Google'ın tersi.
        $this->assertStringContainsString('2gis.kg/geo/74.6083652,42.869966', $sonuc->last()['url']);
    }

    public function test_yerel_uygulamasi_olmayan_ulkede_yalniz_google(): void
    {
        $t = $this->temsilcilik(['latitude' => 52.5097998, 'longitude' => 13.3560419]);

        $this->assertCount(1, $t->haritaBaglantilari());
    }

    /** Türkmenistan'da 2GIS kapsamı yok (2026-08-25 doğrulandı) — eklenmedi. */
    public function test_turkmenistan_yerel_uygulama_listesinde_yok(): void
    {
        Country::firstOrCreate(['code' => 'TM'], ['name_tr' => 'Türkmenistan', 'emoji' => '🇹🇲', 'is_active' => true]);
        $t = $this->temsilcilik([
            'country_code' => 'TM', 'slug' => 'askabat', 'sehir' => 'Aşkabat',
            'latitude' => 38.0419474, 'longitude' => 58.1884890,
        ]);

        $this->assertCount(1, $t->haritaBaglantilari());
    }
}
