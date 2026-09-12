<?php

declare(strict_types=1);

namespace Tests\Feature\Mcp;

use App\Enums\DealStatus;
use App\Enums\FeatureRequestStatus;
use App\Enums\ListingStatus;
use App\Mcp\Araclar\Yonetim\YonetimAraci;
use App\Mcp\Sunucular\NisoyaYonetimSunucusu;
use App\Models\Conversation;
use App\Models\Deal;
use App\Models\FeatureRequest;
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
        $this->assertCount(16, $araclar, 'Nisoya Yönetim Sunucusu tam olarak 16 araç barındırmalı.');

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
        $this->assertCount(16, $response->json('result.tools'));
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

    public function test_cms_ozet_araci_tasarim_ve_icerik_durumunu_verir(): void
    {
        $response = $this->withToken(self::TEST_API_KEY)->postJson('/api/mcp', [
            'jsonrpc' => '2.0',
            'id' => 7,
            'method' => 'tools/call',
            'params' => [
                'name' => 'nisoya_cms_ozet',
                'arguments' => ['detayli' => false],
            ],
        ]);

        $response->assertStatus(200);
        $data = $response->json('result.structuredContent');
        $this->assertArrayHasKey('aktif_tema', $data);
        $this->assertArrayHasKey('hero', $data);
        $this->assertArrayHasKey('duyuru_bandi', $data);
        $this->assertArrayHasKey('sayfalar_istatistik', $data);
        $this->assertArrayHasKey('sss_istatistik', $data);
    }

    public function test_duyuru_yonet_araci_okuma_ve_guncelleme_yapar(): void
    {
        // 1. Güncelleme
        $resUpdate = $this->withToken(self::TEST_API_KEY)->postJson('/api/mcp', [
            'jsonrpc' => '2.0',
            'id' => 8,
            'method' => 'tools/call',
            'params' => [
                'name' => 'nisoya_duyuru_yonet',
                'arguments' => [
                    'islem' => 'guncelle',
                    'aktif' => true,
                    'metin' => 'Almanya geneli ücretsiz kargo haftası!',
                    'link' => 'https://nisoya.com/kampanya',
                    'link_metni' => 'İncele',
                    'renk' => 'marka',
                ],
            ],
        ]);

        $resUpdate->assertStatus(200);
        $this->assertTrue($resUpdate->json('result.structuredContent.basarili'));
        $this->assertEquals('1', Settings::get('duyuru.aktif'));
        $this->assertEquals('Almanya geneli ücretsiz kargo haftası!', Settings::get('duyuru.metin'));

        // 2. Okuma
        $resRead = $this->withToken(self::TEST_API_KEY)->postJson('/api/mcp', [
            'jsonrpc' => '2.0',
            'id' => 9,
            'method' => 'tools/call',
            'params' => [
                'name' => 'nisoya_duyuru_yonet',
                'arguments' => ['islem' => 'oku'],
            ],
        ]);

        $resRead->assertStatus(200);
        $this->assertTrue($resRead->json('result.structuredContent.aktif'));
        $this->assertEquals('Almanya geneli ücretsiz kargo haftası!', $resRead->json('result.structuredContent.metin'));
    }

    public function test_hero_yonet_araci_okuma_ve_guncelleme_yapar(): void
    {
        $resUpdate = $this->withToken(self::TEST_API_KEY)->postJson('/api/mcp', [
            'jsonrpc' => '2.0',
            'id' => 10,
            'method' => 'tools/call',
            'params' => [
                'name' => 'nisoya_hero_yonet',
                'arguments' => [
                    'islem' => 'guncelle',
                    'baslik' => 'Avrupa Türk Topluluğu',
                    'vurgu' => 'Tek Pazaryerinde Buluşuyor',
                    'rozet' => '🌍 Nisoya 2026',
                    'cta1_etiket' => 'Ücretsiz İlan Ver',
                ],
            ],
        ]);

        $resUpdate->assertStatus(200);
        $this->assertTrue($resUpdate->json('result.structuredContent.basarili'));
        $this->assertEquals('Avrupa Türk Topluluğu', Settings::get('hero.baslik'));
        $this->assertEquals('Tek Pazaryerinde Buluşuyor', Settings::get('hero.vurgu'));
    }

    public function test_sss_yonet_araci_listeler_ve_ekler(): void
    {
        $resEkle = $this->withToken(self::TEST_API_KEY)->postJson('/api/mcp', [
            'jsonrpc' => '2.0',
            'id' => 11,
            'method' => 'tools/call',
            'params' => [
                'name' => 'nisoya_sss_yonet',
                'arguments' => [
                    'islem' => 'ekle',
                    'soru' => 'Nisoya üzerinden satış yapmak ücretli mi?',
                    'cevap' => 'Hayır, bireysel ilan vermek ve alışveriş yapmak tamamen ücretsizdir.',
                    'is_active' => true,
                    'sort_order' => 1,
                ],
            ],
        ]);

        $resEkle->assertStatus(200);
        $this->assertTrue($resEkle->json('result.structuredContent.basarili'));
        $id = $resEkle->json('result.structuredContent.id');

        // Listeleme
        $resListele = $this->withToken(self::TEST_API_KEY)->postJson('/api/mcp', [
            'jsonrpc' => '2.0',
            'id' => 12,
            'method' => 'tools/call',
            'params' => [
                'name' => 'nisoya_sss_yonet',
                'arguments' => ['islem' => 'listele'],
            ],
        ]);

        $resListele->assertStatus(200);
        $this->assertGreaterThanOrEqual(1, $resListele->json('result.structuredContent.toplam_adet'));
    }

    public function test_sayfa_yonet_araci_listeler_ve_olusturur(): void
    {
        $resOlustur = $this->withToken(self::TEST_API_KEY)->postJson('/api/mcp', [
            'jsonrpc' => '2.0',
            'id' => 13,
            'method' => 'tools/call',
            'params' => [
                'name' => 'nisoya_sayfa_yonet',
                'arguments' => [
                    'islem' => 'olustur',
                    'title' => 'Topluluk Kuralları',
                    'slug' => 'topluluk-kurallari-test',
                    'status' => 'yayinda',
                    'meta_description' => 'Nisoya topluluk etik ve kuralları rehberi.',
                ],
            ],
        ]);

        $resOlustur->assertStatus(200);
        $this->assertTrue($resOlustur->json('result.structuredContent.basarili'));

        // Detay
        $resDetay = $this->withToken(self::TEST_API_KEY)->postJson('/api/mcp', [
            'jsonrpc' => '2.0',
            'id' => 14,
            'method' => 'tools/call',
            'params' => [
                'name' => 'nisoya_sayfa_yonet',
                'arguments' => [
                    'islem' => 'detay',
                    'slug' => 'topluluk-kurallari-test',
                ],
            ],
        ]);

        $resDetay->assertStatus(200);
        $this->assertTrue($resDetay->json('result.structuredContent.basarili'));
        $this->assertEquals('Topluluk Kuralları', $resDetay->json('result.structuredContent.sayfa.title'));
    }

    public function test_kategori_yonet_araci_listeler_ve_ekler(): void
    {
        // 1. Ekle
        $resEkle = $this->withToken(self::TEST_API_KEY)->postJson('/api/mcp', [
            'jsonrpc' => '2.0',
            'id' => 15,
            'method' => 'tools/call',
            'params' => [
                'name' => 'nisoya_kategori_yonet',
                'arguments' => [
                    'islem' => 'ekle',
                    'name' => 'Güzellik & Kuaför',
                    'icon' => '✂️',
                    'sort_order' => 5,
                    'is_active' => true,
                ],
            ],
        ]);

        $resEkle->assertStatus(200);
        $this->assertTrue($resEkle->json('result.structuredContent.basarili'));

        // 2. Listele
        $resListele = $this->withToken(self::TEST_API_KEY)->postJson('/api/mcp', [
            'jsonrpc' => '2.0',
            'id' => 16,
            'method' => 'tools/call',
            'params' => [
                'name' => 'nisoya_kategori_yonet',
                'arguments' => ['islem' => 'listele'],
            ],
        ]);

        $resListele->assertStatus(200);
        $this->assertGreaterThanOrEqual(1, $resListele->json('result.structuredContent.toplam_kategori'));
    }

    public function test_anlasma_yonet_araci_listeler_ve_cozer(): void
    {
        $seller = User::factory()->create();
        $buyer = User::factory()->create();
        $listing = Listing::factory()->create(['user_id' => $seller->id]);
        $conversation = Conversation::create([
            'listing_id' => $listing->id,
            'user_one_id' => $seller->id,
            'user_two_id' => $buyer->id,
        ]);

        $deal = Deal::create([
            'conversation_id' => $conversation->id,
            'listing_id' => $listing->id,
            'seller_id' => $seller->id,
            'buyer_id' => $buyer->id,
            'proposed_by' => $buyer->id,
            'amount' => 150.00,
            'currency' => 'EUR',
            'status' => DealStatus::Sorunlu,
            'dispute_note' => 'Kargo elime ulaşmadı.',
            'disputed_at' => now(),
        ]);

        // 1. Listele
        $resListele = $this->withToken(self::TEST_API_KEY)->postJson('/api/mcp', [
            'jsonrpc' => '2.0',
            'id' => 17,
            'method' => 'tools/call',
            'params' => [
                'name' => 'nisoya_anlasma_yonet',
                'arguments' => ['islem' => 'listele', 'sadece_sorunlular' => true],
            ],
        ]);

        $resListele->assertStatus(200);
        $this->assertEquals(1, $resListele->json('result.structuredContent.listelenen_adet'));

        // 2. Çöz / Durum Güncelle
        $resCoz = $this->withToken(self::TEST_API_KEY)->postJson('/api/mcp', [
            'jsonrpc' => '2.0',
            'id' => 18,
            'method' => 'tools/call',
            'params' => [
                'name' => 'nisoya_anlasma_yonet',
                'arguments' => [
                    'islem' => 'durum_guncelle',
                    'anlasma_id' => $deal->id,
                    'yeni_durum' => 'tamamlandi',
                ],
            ],
        ]);

        $resCoz->assertStatus(200);
        $this->assertTrue($resCoz->json('result.structuredContent.basarili'));

        $deal->refresh();
        $this->assertEquals(DealStatus::Tamamlandi, $deal->status);
    }

    public function test_one_cikarma_yonet_araci_listeler_ve_onaylar(): void
    {
        $user = User::factory()->create();
        $listing = Listing::factory()->create(['user_id' => $user->id, 'is_featured' => false]);
        $request = FeatureRequest::create([
            'listing_id' => $listing->id,
            'user_id' => $user->id,
            'days' => 14,
            'status' => FeatureRequestStatus::Beklemede,
        ]);

        // 1. Listele
        $resListele = $this->withToken(self::TEST_API_KEY)->postJson('/api/mcp', [
            'jsonrpc' => '2.0',
            'id' => 19,
            'method' => 'tools/call',
            'params' => [
                'name' => 'nisoya_one_cikarma_yonet',
                'arguments' => ['islem' => 'listele', 'sadece_bekleyenler' => true],
            ],
        ]);

        $resListele->assertStatus(200);
        $this->assertEquals(1, $resListele->json('result.structuredContent.toplam_bekleyen'));

        // 2. Onayla (karar_ver)
        $resOnayla = $this->withToken(self::TEST_API_KEY)->postJson('/api/mcp', [
            'jsonrpc' => '2.0',
            'id' => 20,
            'method' => 'tools/call',
            'params' => [
                'name' => 'nisoya_one_cikarma_yonet',
                'arguments' => [
                    'islem' => 'karar_ver',
                    'talep_id' => $request->id,
                    'karar' => 'onayla',
                ],
            ],
        ]);

        $resOnayla->assertStatus(200);
        $this->assertTrue($resOnayla->json('result.structuredContent.basarili'));

        $request->refresh();
        $this->assertEquals(FeatureRequestStatus::Onaylandi, $request->status);
        $listing->refresh();
        $this->assertTrue($listing->is_featured);
    }
}
