<?php

namespace Tests\Feature\Growth;

use App\Services\GeocodingService;
use App\Services\Growth\Discovery\KesifKaynagiHatasi;
use App\Services\Growth\Discovery\OverpassDiscoverySource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OverpassDiscoverySourceTest extends TestCase
{
    use RefreshDatabase;

    private function source(): OverpassDiscoverySource
    {
        return new OverpassDiscoverySource(app(GeocodingService::class));
    }

    public function test_maps_overpass_elements_to_businesses(): void
    {
        $elements = [
            ['type' => 'node', 'id' => 1, 'tags' => ['name' => 'Anadolu Kebap', 'website' => 'https://anadolu.example', 'addr:city' => 'Almaty']],
            ['type' => 'way', 'id' => 2, 'tags' => ['name' => 'Ali Berber', 'contact:website' => 'https://ali.example']],
            ['type' => 'node', 'id' => 3, 'tags' => ['amenity' => 'restaurant']], // adsız → atlanır
        ];
        $trade = ['key' => 'lokanta', 'tr' => 'lokanta', 'en' => 'restaurant', 'osm' => 'amenity=restaurant'];

        $result = $this->source()->mapElements($elements, 'KZ', 'Almaty', $trade);

        $this->assertCount(2, $result);
        $this->assertSame('Anadolu Kebap', $result[0]->name);
        $this->assertSame('https://anadolu.example', $result[0]->website);
        $this->assertSame('Almaty', $result[0]->city);
        $this->assertSame('osm-node-1', $result[0]->externalId);
        $this->assertSame('lokanta', $result[0]->sector);
        // İkinci kayıt: website yoksa contact:website'e düşer.
        $this->assertSame('https://ali.example', $result[1]->website);
    }

    public function test_nitelenmis_sehir_dizesi_kayda_ham_gecmez(): void
    {
        /*
         * Katalogdaki ABD şehirleri adaşı eyalet karışmasın diye nitelenir
         * ("Clifton, New Jersey" — ölçüldü: sade "Union City" Kaliforniya'yı
         * getiriyordu). O dize bir ARAMA dizesi; aday kaydının `city` alanına
         * ham geçerse havuz "Clifton, New Jersey" gibi değerlerle kirlenir.
         */
        $elements = [
            ['type' => 'node', 'id' => 9, 'tags' => ['name' => 'Istanbul Kebab House']], // addr:city YOK
        ];
        $trade = ['key' => 'lokanta', 'tr' => 'lokanta', 'en' => 'restaurant', 'osm' => 'amenity=restaurant'];

        $result = $this->source()->mapElements($elements, 'US', 'Clifton, New Jersey', $trade);

        $this->assertSame('Clifton', $result[0]->city);
    }

    public function test_discover_returns_empty_without_osm_tag(): void
    {
        $result = $this->source()->discover('Almaty', 'KZ', ['key' => 'x', 'tr' => 'x', 'en' => 'x']);

        $this->assertSame([], $result);
    }

    public function test_discover_returns_empty_when_city_cannot_be_geocoded(): void
    {
        // Testte GeocodingService ağ kullanmaz; seed'siz ülke → null koordinat → boş.
        $result = $this->source()->discover('Nowhereville', 'ZZ', [
            'key' => 'lokanta', 'tr' => 'lokanta', 'en' => 'restaurant', 'osm' => 'amenity=restaurant',
        ]);

        $this->assertSame([], $result);
    }

    public function test_source_is_always_configured_no_key_needed(): void
    {
        $this->assertTrue($this->source()->isConfigured());
        $this->assertSame('openstreetmap', $this->source()->name());
    }

    // -----------------------------------------------------------------
    // ARIZAYI SONUÇSUZLUKTAN AYIRMAK (2026-08-06'da ölçümle bulundu)
    // -----------------------------------------------------------------

    public function test_zaman_asimi_remarki_sessizce_bos_sonuca_donusmez(): void
    {
        /*
         * GERÇEK YANIT. Overpass zaman aşımını HTTP 200 + `remark` ile
         * bildirir; `elements` boş gelir, yani "hiçbir şey bulunamadı" ile
         * BİREBİR AYNI görünür. Eski kod `json('elements') ?? []` diyerek
         * bunu sonuçsuzluk sanıyordu ve komut "Bulunan işletme: 0" yazıyordu —
         * sahip bundan "orada Türk işletmesi yok" sonucunu çıkarırdı.
         */
        $govde = '{"version":0.6,"elements":[],"remark":"runtime error: Query timed out in \"query\" at line 1 after 26 seconds."}';

        $this->expectException(KesifKaynagiHatasi::class);
        $this->expectExceptionMessageMatches('/timed out/');

        $this->source()->cozumleVeyaPatlat(200, $govde);
    }

    public function test_sunucu_mesgul_html_sayfasi_sessizce_bos_sonuca_donusmez(): void
    {
        // İkinci gerçek arıza biçimi: Overpass yüklüyken JSON yerine HTML
        // hata sayfası döner — yine HTTP 200 ile.
        $govde = '<?xml version="1.0"?><html><body><p><strong>Error</strong>: runtime error: '
            .'Dispatcher_Client::request_read_and_idx::timeout. The server is probably too busy.</p></body></html>';

        $this->expectException(KesifKaynagiHatasi::class);

        $this->source()->cozumleVeyaPatlat(200, $govde);
    }

    public function test_http_hatasi_da_patlatir(): void
    {
        $this->expectException(KesifKaynagiHatasi::class);

        $this->source()->cozumleVeyaPatlat(504, 'Gateway Timeout');
    }

    public function test_gecerli_yanit_elemanlari_dondurur(): void
    {
        // Testin ters yönü: sağlıklı yanıt PATLAMAMALI, yoksa yukarıdaki üç
        // test her koşulda geçer ve hiçbir şey ölçmez.
        $govde = '{"elements":[{"type":"node","id":1,"tags":{"name":"Istanbul Kebab"}}]}';

        $elemanlar = $this->source()->cozumleVeyaPatlat(200, $govde);

        $this->assertCount(1, $elemanlar);
        $this->assertSame('Istanbul Kebab', $elemanlar[0]['tags']['name']);
    }
}
