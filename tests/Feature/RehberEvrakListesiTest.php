<?php

namespace Tests\Feature;

use App\Models\IslemTuru;
use App\Models\Temsilcilik;
use App\Models\TemsilcilikIslemi;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Rehberdeki "Gerekli evraklar" listesi.
 *
 * ---------------------------------------------------------------------------
 * CANLIDA BULUNDU (2026-08-13)
 *
 * /de/berlin/pasaport sayfasında "Gerekli evraklar" başlığının altında SEKİZ
 * BOŞ MADDE vardı: yalnız ✔ işaretleri, hiç metin yok. Rehberin en değerli
 * kısmı buydu ve sayfa 200 dönüyordu — yani hiçbir izleme bunu göremezdi.
 *
 * Sebep: aynı sütunda iki farklı şekil dolaşıyordu.
 *   - Yönetim paneli + seeder taslakları → ['ad' => …, 'not' => …]
 *   - JSON içe aktarımıyla gelen DOĞRULANMIŞ içerik → düz metin dizisi
 *
 * Görünüm yalnız birincisini biliyordu; `?? ''` ikincisini sessizce boşa
 * çeviriyordu. Kırık olan, tam da yayına alınmış gerçek içerikti.
 */
class RehberEvrakListesiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CurrencySeeder::class, CountrySeeder::class]);
    }

    private function islem(array $evraklar): TemsilcilikIslemi
    {
        $temsilcilik = Temsilcilik::query()->create([
            'country_code' => 'DE',
            'ad' => 'Berlin Başkonsolosluğu',
            'slug' => 'berlin',
            'tur' => Temsilcilik::TUR_BASKONSOLOSLUK,
            'sehir' => 'Berlin',
            'is_active' => true,
        ]);

        $tur = IslemTuru::query()->create([
            'ad' => 'Pasaport',
            'slug' => 'pasaport',
            'is_active' => true,
        ]);

        return TemsilcilikIslemi::query()->create([
            'temsilcilik_id' => $temsilcilik->id,
            'islem_turu_id' => $tur->id,
            'evraklar' => $evraklar,
            'resmi_kaynak_url' => 'https://www.konsolosluk.gov.tr',
            'status' => TemsilcilikIslemi::STATUS_YAYIN,
            'dogrulanma_tarihi' => now(),
        ]);
    }

    public function test_duz_metin_evrak_listesi_sayfada_gorunuyor(): void
    {
        // ASIL BULGU. Yayındaki içerik bu şekilde geliyor.
        $islem = $this->islem([
            'T.C. Kimlik Kartı veya geçerli pasaport',
            'Konsolosluk randevusu',
        ]);

        $this->get('/de/berlin/pasaport')
            ->assertOk()
            ->assertSee('T.C. Kimlik Kartı veya geçerli pasaport')
            ->assertSee('Konsolosluk randevusu');
    }

    public function test_dizi_seklindeki_evrak_listesi_de_gorunuyor(): void
    {
        // Yönetim panelinin ve seeder taslaklarının şekli — bozulmamalı.
        $islem = $this->islem([
            ['ad' => 'Vekaletname konusu bilgiler', 'not' => 'Tapu/araç/dava gibi bilgiler'],
        ]);

        $this->get('/de/berlin/pasaport')
            ->assertOk()
            ->assertSee('Vekaletname konusu bilgiler')
            ->assertSee('Tapu/araç/dava gibi bilgiler');
    }

    public function test_adi_bos_madde_bos_satir_birakmiyor(): void
    {
        /*
         * Boş bir ✔ satırı, satır olmamasından kötüdür: kullanıcı bir evrak
         * olduğunu sanır ama ne olduğunu göremez.
         */
        $islem = $this->islem([
            ['ad' => '', 'not' => 'sadece not'],
            '   ',
            ['ad' => 'Geçerli madde'],
        ]);

        $this->assertSame(
            [['ad' => 'Geçerli madde', 'not' => null]],
            $islem->fresh()->evraklar,
        );
    }

    public function test_karisik_liste_tek_sekle_donuyor(): void
    {
        $islem = $this->islem([
            'Düz metin madde',
            ['ad' => 'Dizi madde', 'not' => 'notu var'],
        ]);

        $this->assertSame([
            ['ad' => 'Düz metin madde', 'not' => null],
            ['ad' => 'Dizi madde', 'not' => 'notu var'],
        ], $islem->fresh()->evraklar);
    }
}
