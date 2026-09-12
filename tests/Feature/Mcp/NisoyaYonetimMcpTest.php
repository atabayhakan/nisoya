<?php

declare(strict_types=1);

namespace Tests\Feature\Mcp;

use App\Enums\ListingStatus;
use App\Mcp\Araclar\Yonetim\YonetimAraci;
use App\Mcp\Sunucular\NisoyaYonetimSunucusu;
use App\Models\Listing;
use App\Models\User;
use App\Support\Settings;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionProperty;
use Tests\TestCase;

class NisoyaYonetimMcpTest extends TestCase
{
    use RefreshDatabase;

    private const TEST_API_KEY = 'test_secret_nisoya_mcp_key_12345';

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CurrencySeeder::class, CountrySeeder::class, CategorySeeder::class]);
        Settings::setMany(['mcp.api_key' => self::TEST_API_KEY]);
    }

    /** @return array<int, class-string> */
    private function sunucuAraclari(): array
    {
        return (new ReflectionProperty(NisoyaYonetimSunucusu::class, 'tools'))->getDefaultValue();
    }

    public function test_tum_araclar_yonetim_araci_tabanindan_turer(): void
    {
        $araclar = $this->sunucuAraclari();
        $this->assertCount(8, $araclar, 'Nisoya Yönetim Sunucusu tam olarak 8 araç barındırmalı.');

        foreach ($araclar as $sinif) {
            $this->assertTrue(
                is_subclass_of($sinif, YonetimAraci::class),
                "{$sinif} aracı YonetimAraci tabanından türemelidir."
            );
        }
    }

    public function test_api_mcp_rotasi_yetkisiz_istekleri_401_ile_reddeder(): void
    {
        // 1. Hiç token gönderilmediğinde
        $resNoToken = $this->postJson('/api/mcp', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/list',
        ]);
        $resNoToken->assertStatus(401);
        $resNoToken->assertJsonPath('error.code', -32000);

        // 2. Yanlış token gönderildiğinde
        $resBadToken = $this->withToken('yanlis_anahtar')->postJson('/api/mcp', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/list',
        ]);
        $resBadToken->assertStatus(401);
        $resBadToken->assertJsonPath('error.code', -32000);
    }

    public function test_api_mcp_rotasi_gecerli_token_ile_araclari_listeler(): void
    {
        $response = $this->withToken(self::TEST_API_KEY)->postJson('/api/mcp', [
            'jsonrpc' => '2.0',
            'id' => 1,
            'method' => 'tools/list',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('result.tools.0.name', 'nisoya_ilan_ara');
        $this->assertCount(8, $response->json('result.tools'));
    }

    public function test_ozet_metrikler_araci_dogru_verileri_doner(): void
    {
        $user = User::factory()->create();
        Listing::factory()->create([
            'user_id' => $user->id,
            'status' => ListingStatus::Aktif,
        ]);
        Listing::factory()->create([
            'user_id' => $user->id,
            'status' => ListingStatus::Beklemede,
        ]);

        $response = $this->withToken(self::TEST_API_KEY)->postJson('/api/mcp', [
            'jsonrpc' => '2.0',
            'id' => 2,
            'method' => 'tools/call',
            'params' => [
                'name' => 'nisoya_ozet_metrikler',
                'arguments' => [],
            ],
        ]);

        $response->assertStatus(200);
        $content = $response->json('result.structuredContent');
        $this->assertEquals(1, $content['ilan_istatistikleri']['aktif_ilan_sayisi']);
        $this->assertEquals(1, $content['ilan_istatistikleri']['onay_bekleyen_ilanlar']);
        $this->assertEquals(2, $content['ilan_istatistikleri']['toplam_ilan']);
    }

    public function test_ilan_arama_ve_bekleyen_ilanlar_filtreleri_calisir(): void
    {
        $user = User::factory()->create();
        $aktif = Listing::factory()->create([
            'user_id' => $user->id,
            'title' => 'Berlin Satılık Daire',
            'city' => 'Berlin',
            'country_code' => 'DE',
            'status' => ListingStatus::Aktif,
        ]);
        $bekleyen = Listing::factory()->create([
            'user_id' => $user->id,
            'title' => 'Köln Oto Lastik',
            'city' => 'Köln',
            'country_code' => 'DE',
            'status' => ListingStatus::Beklemede,
        ]);

        // Arama testi
        $resAra = $this->withToken(self::TEST_API_KEY)->postJson('/api/mcp', [
            'jsonrpc' => '2.0',
            'id' => 3,
            'method' => 'tools/call',
            'params' => [
                'name' => 'nisoya_ilan_ara',
                'arguments' => ['sehir' => 'Berlin'],
            ],
        ]);
        $resAra->assertStatus(200);
        $resAraData = $resAra->json('result.structuredContent');
        $this->assertEquals(1, $resAraData['toplam_bulunan']);
        $this->assertEquals($aktif->id, $resAraData['ilanlar'][0]['id']);

        // Bekleyen ilanlar testi
        $resBekleyen = $this->withToken(self::TEST_API_KEY)->postJson('/api/mcp', [
            'jsonrpc' => '2.0',
            'id' => 4,
            'method' => 'tools/call',
            'params' => [
                'name' => 'nisoya_bekleyen_ilanlar',
                'arguments' => [],
            ],
        ]);
        $resBekleyen->assertStatus(200);
        $resBekleyenData = $resBekleyen->json('result.structuredContent');
        $this->assertEquals(1, $resBekleyenData['bekleyen_toplam_adet']);
        $this->assertEquals($bekleyen->id, $resBekleyenData['ilanlar'][0]['id']);
    }

    public function test_ilan_durum_guncelleme_araci_onay_verir(): void
    {
        $user = User::factory()->create();
        $listing = Listing::factory()->create([
            'user_id' => $user->id,
            'status' => ListingStatus::Beklemede,
            'fraud_reason' => 'Şüpheli telefon numarası',
        ]);

        $response = $this->withToken(self::TEST_API_KEY)->postJson('/api/mcp', [
            'jsonrpc' => '2.0',
            'id' => 5,
            'method' => 'tools/call',
            'params' => [
                'name' => 'nisoya_ilan_durum_guncelle',
                'arguments' => [
                    'ilan_id' => $listing->id,
                    'yeni_durum' => 'aktif',
                    'gerekce' => 'Doğrulandı ve onaylandı',
                ],
            ],
        ]);

        $response->assertStatus(200);
        $this->assertTrue($response->json('result.structuredContent.basarili'));

        $listing->refresh();
        $this->assertEquals(ListingStatus::Aktif, $listing->status);
        $this->assertNull($listing->fraud_reason, 'Onaylanan ilanın fraud şüphesi temizlenmeli.');
    }

    public function test_whatsapp_davet_araci_baglanti_ve_mesaj_uretir(): void
    {
        $user = User::factory()->create();
        $listing = Listing::factory()->create([
            'user_id' => $user->id,
            'title' => 'Antep Sofrası',
            'city' => 'Frankfurt',
            'country_code' => 'DE',
        ]);

        $response = $this->withToken(self::TEST_API_KEY)->postJson('/api/mcp', [
            'jsonrpc' => '2.0',
            'id' => 6,
            'method' => 'tools/call',
            'params' => [
                'name' => 'nisoya_whatsapp_davet_onizle',
                'arguments' => [
                    'ilan_id' => $listing->id,
                ],
            ],
        ]);

        $response->assertStatus(200);
        $data = $response->json('result.structuredContent');
        $this->assertStringContainsString('Antep Sofrası', $data['davet_mesaj_metni']);
        $this->assertStringContainsString('https://wa.me/?text=', $data['whatsapp_linki']);
    }
}
