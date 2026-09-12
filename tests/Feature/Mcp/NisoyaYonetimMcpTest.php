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
use App\Models\IslemTuru;
use App\Models\Listing;
use App\Models\OutreachTarget;
use App\Models\Temsilcilik;
use App\Models\TemsilcilikIslemi;
use App\Models\User;
use App\Models\YasamKategorisi;
use App\Models\YasamKonuIcerigi;
use App\Models\YasamKonusu;
use App\Models\Zone;
use App\Support\Modules;
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
        $this->assertCount(25, $araclar, 'Nisoya Yönetim Sunucusu tam olarak 25 araç barındırmalı.');

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
        $this->assertCount(25, $response->json('result.tools'));
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

    public function test_temsilcilik_yonet_araci_listeler_ve_gunceller(): void
    {
        $t = Temsilcilik::create([
            'country_code' => 'DE',
            'sehir' => 'Berlin',
            'ad' => 'Berlin Başkonsolosluğu Test',
            'slug' => 'berlin-baskonsoloslugu-test',
            'adres' => 'Heerstr. 21, 14052 Berlin',
            'resmi_url' => 'http://berlin.bk.mfa.gov.tr',
            'is_active' => true,
        ]);

        // 1. Listele
        $resListele = $this->withToken(self::TEST_API_KEY)->postJson('/api/mcp', [
            'jsonrpc' => '2.0',
            'id' => 21,
            'method' => 'tools/call',
            'params' => [
                'name' => 'nisoya_temsilcilik_yonet',
                'arguments' => ['islem' => 'listele', 'country_code' => 'DE', 'sehir' => 'Berlin'],
            ],
        ]);

        $resListele->assertStatus(200);
        $this->assertGreaterThanOrEqual(1, $resListele->json('result.structuredContent.toplam_bulunan'));

        // 2. Güncelle (adres ve GPS koordinatları)
        $resGuncelle = $this->withToken(self::TEST_API_KEY)->postJson('/api/mcp', [
            'jsonrpc' => '2.0',
            'id' => 22,
            'method' => 'tools/call',
            'params' => [
                'name' => 'nisoya_temsilcilik_yonet',
                'arguments' => [
                    'islem' => 'guncelle',
                    'temsilcilik_id' => $t->id,
                    'adres' => 'Yeni Adres 123, Berlin',
                    'latitude' => 52.5097998,
                    'longitude' => 13.3560419,
                ],
            ],
        ]);

        $resGuncelle->assertStatus(200);
        $this->assertTrue($resGuncelle->json('result.structuredContent.basarili'));

        $t->refresh();
        $this->assertEquals('Yeni Adres 123, Berlin', $t->adres);
        $this->assertEquals('52.5097998', (string) $t->latitude);
        $this->assertEquals('13.3560419', (string) $t->longitude);

        // 3. Denetle
        $resDenetle = $this->withToken(self::TEST_API_KEY)->postJson('/api/mcp', [
            'jsonrpc' => '2.0',
            'id' => 23,
            'method' => 'tools/call',
            'params' => [
                'name' => 'nisoya_temsilcilik_yonet',
                'arguments' => [
                    'islem' => 'denetle',
                    'temsilcilik_id' => $t->id,
                ],
            ],
        ]);

        $resDenetle->assertStatus(200);
        $this->assertTrue($resDenetle->json('result.structuredContent.basarili'));
        $this->assertArrayHasKey('puan', $resDenetle->json('result.structuredContent.denetim_sonucu'));
    }

    public function test_konsolosluk_rehber_yonet_araci_listeler_ve_durum_gunceller(): void
    {
        $t = Temsilcilik::create([
            'country_code' => 'DE',
            'sehir' => 'Köln',
            'ad' => 'Köln Başkonsolosluğu Test',
            'slug' => 'koln-baskonsoloslugu-test',
            'is_active' => true,
        ]);

        $islemTuru = IslemTuru::create([
            'ad' => 'Pasaport Yenileme Test',
            'slug' => 'pasaport-yenileme-test',
            'kategori' => 'Pasaport',
            'is_active' => true,
        ]);

        $icerik = TemsilcilikIslemi::create([
            'temsilcilik_id' => $t->id,
            'islem_turu_id' => $islemTuru->id,
            'evraklar' => [['ad' => 'Kimlik kartı', 'not' => 'Aslı']],
            'sure_metni' => '1 hafta',
            'ucret_metni' => '45 €',
            'resmi_kaynak_url' => 'https://www.konsolosluk.gov.tr',
            'status' => TemsilcilikIslemi::STATUS_TASLAK,
        ]);

        // 1. Listele
        $resListele = $this->withToken(self::TEST_API_KEY)->postJson('/api/mcp', [
            'jsonrpc' => '2.0',
            'id' => 23,
            'method' => 'tools/call',
            'params' => [
                'name' => 'nisoya_konsolosluk_rehber_yonet',
                'arguments' => ['islem' => 'listele', 'durum' => 'taslak'],
            ],
        ]);

        $resListele->assertStatus(200);
        $this->assertGreaterThanOrEqual(1, $resListele->json('result.structuredContent.listelenen'));

        // 2. Durum Güncelle
        $resGuncelle = $this->withToken(self::TEST_API_KEY)->postJson('/api/mcp', [
            'jsonrpc' => '2.0',
            'id' => 24,
            'method' => 'tools/call',
            'params' => [
                'name' => 'nisoya_konsolosluk_rehber_yonet',
                'arguments' => [
                    'islem' => 'durum_guncelle',
                    'icerik_id' => $icerik->id,
                    'yeni_durum' => 'yayin',
                ],
            ],
        ]);

        $resGuncelle->assertStatus(200);
        $this->assertTrue($resGuncelle->json('result.structuredContent.basarili'));

        $icerik->refresh();
        $this->assertEquals(TemsilcilikIslemi::STATUS_YAYIN, $icerik->status);
    }

    public function test_yasam_rehberi_yonet_araci_konulari_ve_icerikleri_yonetir(): void
    {
        $kat = YasamKategorisi::create([
            'ad' => 'Barınma Test',
            'slug' => 'barinma-test',
            'ikon' => '🏠',
            'is_active' => true,
        ]);

        $konu = YasamKonusu::create([
            'kategori_id' => $kat->id,
            'baslik' => 'Kiralık Ev Arama Test',
            'slug' => 'kiralik-ev-arama-test',
            'is_active' => true,
        ]);

        $icerik = YasamKonuIcerigi::create([
            'yasam_konusu_id' => $konu->id,
            'country_code' => 'DE',
            'icerik' => [['tip' => 'paragraf', 'metin' => 'Almanya kiralık ev rehberi test.']],
            'kaynak_url' => 'https://example.com/guide',
            'status' => YasamKonuIcerigi::STATUS_TASLAK,
            'yazan_tur' => YasamKonuIcerigi::YAZAN_AI,
        ]);

        // 1. Konular
        $resKonular = $this->withToken(self::TEST_API_KEY)->postJson('/api/mcp', [
            'jsonrpc' => '2.0',
            'id' => 25,
            'method' => 'tools/call',
            'params' => [
                'name' => 'nisoya_yasam_rehberi_yonet',
                'arguments' => ['islem' => 'konular'],
            ],
        ]);

        $resKonular->assertStatus(200);
        $this->assertGreaterThanOrEqual(1, $resKonular->json('result.structuredContent.toplam_kategori'));

        // 2. Durum Güncelle
        $resGuncelle = $this->withToken(self::TEST_API_KEY)->postJson('/api/mcp', [
            'jsonrpc' => '2.0',
            'id' => 26,
            'method' => 'tools/call',
            'params' => [
                'name' => 'nisoya_yasam_rehberi_yonet',
                'arguments' => [
                    'islem' => 'durum_guncelle',
                    'icerik_id' => $icerik->id,
                    'yeni_durum' => 'yayinda',
                ],
            ],
        ]);

        $resGuncelle->assertStatus(200);
        $this->assertTrue($resGuncelle->json('result.structuredContent.basarili'));

        $icerik->refresh();
        $this->assertEquals(YasamKonuIcerigi::STATUS_YAYIN, $icerik->status);
    }

    public function test_sistem_saglik_ve_hatalar_araci_calisir(): void
    {
        // 1. Sağlık özeti
        $resOzet = $this->withToken(self::TEST_API_KEY)->postJson('/api/mcp', [
            'jsonrpc' => '2.0',
            'id' => 30,
            'method' => 'tools/call',
            'params' => [
                'name' => 'nisoya_sistem_saglik_ve_hatalar',
                'arguments' => ['islem' => 'saglik_ozeti'],
            ],
        ]);

        $resOzet->assertStatus(200);
        $data = $resOzet->json('result.structuredContent');
        $this->assertEquals('basarili', $data['durum']);
        $this->assertArrayHasKey('sistem_sagligi', $data);
        $this->assertArrayHasKey('yonetici_ve_guvenlik', $data);
        $this->assertArrayHasKey('yedekleme', $data);

        // 2. Hataları listele
        $resHatalar = $this->withToken(self::TEST_API_KEY)->postJson('/api/mcp', [
            'jsonrpc' => '2.0',
            'id' => 31,
            'method' => 'tools/call',
            'params' => [
                'name' => 'nisoya_sistem_saglik_ve_hatalar',
                'arguments' => [
                    'islem' => 'hatalari_listele',
                    'limit' => 5,
                ],
            ],
        ]);

        $resHatalar->assertStatus(200);
        $this->assertEquals('basarili', $resHatalar->json('result.structuredContent.durum'));

        // 3. AI Hata Teşhisi
        $resTeshis = $this->withToken(self::TEST_API_KEY)->postJson('/api/mcp', [
            'jsonrpc' => '2.0',
            'id' => 32,
            'method' => 'tools/call',
            'params' => [
                'name' => 'nisoya_sistem_saglik_ve_hatalar',
                'arguments' => [
                    'islem' => 'ai_hata_teshis',
                    'hata_mesaji' => 'Connection refused on port 3306',
                    'hata_sinifi' => 'PDOException',
                ],
            ],
        ]);

        $resTeshis->assertStatus(200);
        $teshisData = $resTeshis->json('result.structuredContent.ai_teshisi');
        $this->assertEquals('kritik', $teshisData['severity']);
    }

    public function test_eposta_ve_sablon_yonet_araci_calisir(): void
    {
        // 1. Şablonları listele
        $resList = $this->withToken(self::TEST_API_KEY)->postJson('/api/mcp', [
            'jsonrpc' => '2.0',
            'id' => 33,
            'method' => 'tools/call',
            'params' => [
                'name' => 'nisoya_eposta_ve_sablon_yonet',
                'arguments' => ['islem' => 'sablonlari_listele'],
            ],
        ]);

        $resList->assertStatus(200);
        $this->assertEquals(4, $resList->json('result.structuredContent.toplam_sablon'));

        // 2. AI Şablon İyileştir
        $resAi = $this->withToken(self::TEST_API_KEY)->postJson('/api/mcp', [
            'jsonrpc' => '2.0',
            'id' => 34,
            'method' => 'tools/call',
            'params' => [
                'name' => 'nisoya_eposta_ve_sablon_yonet',
                'arguments' => [
                    'islem' => 'ai_sablon_iyilestir',
                    'sablon_anahtari' => 'yeni_mesaj',
                    'parca' => 'greeting',
                    'ton' => 'profesyonel',
                ],
            ],
        ]);

        $resAi->assertStatus(200);
        $this->assertTrue($resAi->json('result.structuredContent.yer_tutucular_korundu'));

        // 3. SMTP durumu
        $resSmtp = $this->withToken(self::TEST_API_KEY)->postJson('/api/mcp', [
            'jsonrpc' => '2.0',
            'id' => 35,
            'method' => 'tools/call',
            'params' => [
                'name' => 'nisoya_eposta_ve_sablon_yonet',
                'arguments' => ['islem' => 'smtp_durumu'],
            ],
        ]);

        $resSmtp->assertStatus(200);
        $this->assertEquals('basarili', $resSmtp->json('result.structuredContent.durum'));
    }

    public function test_sistem_yapilandirma_yonet_araci_calisir(): void
    {
        // 1. Modülleri listele
        $resModul = $this->withToken(self::TEST_API_KEY)->postJson('/api/mcp', [
            'jsonrpc' => '2.0',
            'id' => 36,
            'method' => 'tools/call',
            'params' => [
                'name' => 'nisoya_sistem_yapilandirma_yonet',
                'arguments' => ['islem' => 'moduller_listele'],
            ],
        ]);

        $resModul->assertStatus(200);
        $this->assertEquals(count(Modules::KEYS), $resModul->json('result.structuredContent.toplam_modul'));

        // 2. Ülkeleri listele
        $resUlke = $this->withToken(self::TEST_API_KEY)->postJson('/api/mcp', [
            'jsonrpc' => '2.0',
            'id' => 37,
            'method' => 'tools/call',
            'params' => [
                'name' => 'nisoya_sistem_yapilandirma_yonet',
                'arguments' => ['islem' => 'ulkeleri_listele'],
            ],
        ]);

        $resUlke->assertStatus(200);
        $this->assertEquals('basarili', $resUlke->json('result.structuredContent.durum'));

        // 3. Demo kapısı durumu
        $resDemo = $this->withToken(self::TEST_API_KEY)->postJson('/api/mcp', [
            'jsonrpc' => '2.0',
            'id' => 38,
            'method' => 'tools/call',
            'params' => [
                'name' => 'nisoya_sistem_yapilandirma_yonet',
                'arguments' => ['islem' => 'demo_kapisi_durumu'],
            ],
        ]);

        $resDemo->assertStatus(200);
        $this->assertEquals('basarili', $resDemo->json('result.structuredContent.durum'));
    }

    public function test_seo_ve_geo_yonet_araci_denetler_ve_ayarlari_gunceller(): void
    {
        // 1. Denetle
        $resDenetle = $this->withToken(self::TEST_API_KEY)->postJson('/api/mcp', [
            'jsonrpc' => '2.0',
            'id' => 39,
            'method' => 'tools/call',
            'params' => [
                'name' => 'nisoya_seo_ve_geo_yonet',
                'arguments' => ['islem' => 'denetle'],
            ],
        ]);

        $resDenetle->assertStatus(200);
        $data = $resDenetle->json('result.structuredContent');
        $this->assertEquals('basarili', $data['durum']);
        $this->assertArrayHasKey('skor', $data);
        $this->assertArrayHasKey('geo_hazirligi', $data);

        // 2. llms_txt_uret
        $resLlms = $this->withToken(self::TEST_API_KEY)->postJson('/api/mcp', [
            'jsonrpc' => '2.0',
            'id' => 40,
            'method' => 'tools/call',
            'params' => [
                'name' => 'nisoya_seo_ve_geo_yonet',
                'arguments' => ['islem' => 'llms_txt_uret'],
            ],
        ]);

        $resLlms->assertStatus(200);
        $this->assertEquals('basarili', $resLlms->json('result.structuredContent.durum'));
        $this->assertGreaterThan(0, $resLlms->json('result.structuredContent.karakter_sayisi'));

        // 3. Ayar güncelle
        $resUpdate = $this->withToken(self::TEST_API_KEY)->postJson('/api/mcp', [
            'jsonrpc' => '2.0',
            'id' => 41,
            'method' => 'tools/call',
            'params' => [
                'name' => 'nisoya_seo_ve_geo_yonet',
                'arguments' => [
                    'islem' => 'ayar_guncelle',
                    'varsayilan_baslik' => 'Nisoya 2026 Avrupa Türk Pazaryeri',
                    'robots_index' => true,
                ],
            ],
        ]);

        $resUpdate->assertStatus(200);
        $this->assertEquals('basarili', $resUpdate->json('result.structuredContent.durum'));
        $this->assertEquals('Nisoya 2026 Avrupa Türk Pazaryeri', Settings::get('seo.default_title'));
    }

    public function test_kesif_havuzu_yonet_araci_listeler_analiz_eder_ve_vitrin_acar(): void
    {
        $target = OutreachTarget::create([
            'source' => 'overpass',
            'external_id' => 'node/123456789',
            'name' => 'Gaziantep Sofrası & Baklava',
            'city' => 'Köln',
            'country' => 'DE',
            'sector' => 'Lokanta & Kebap',
            'detection_band' => 'ambiguous',
            'detection_confidence' => 55,
            'needs_review' => true,
            'status' => 'beklemede',
        ]);

        // 1. Listele
        $resList = $this->withToken(self::TEST_API_KEY)->postJson('/api/mcp', [
            'jsonrpc' => '2.0',
            'id' => 42,
            'method' => 'tools/call',
            'params' => [
                'name' => 'nisoya_kesif_havuzu_yonet',
                'arguments' => ['islem' => 'adaylari_listele', 'sadece_inceleme_bekleyen' => true],
            ],
        ]);

        $resList->assertStatus(200);
        $this->assertGreaterThanOrEqual(1, $resList->json('result.structuredContent.listelenen_adet'));

        // 2. Kültürel Analiz
        $resAnaliz = $this->withToken(self::TEST_API_KEY)->postJson('/api/mcp', [
            'jsonrpc' => '2.0',
            'id' => 43,
            'method' => 'tools/call',
            'params' => [
                'name' => 'nisoya_kesif_havuzu_yonet',
                'arguments' => [
                    'islem' => 'kulturel_analiz',
                    'aday_id' => $target->id,
                ],
            ],
        ]);

        $resAnaliz->assertStatus(200);
        $this->assertEquals('basarili', $resAnaliz->json('result.structuredContent.durum'));
        $this->assertTrue($resAnaliz->json('result.structuredContent.turk_mu'));

        // 3. Karar Ver
        $resKarar = $this->withToken(self::TEST_API_KEY)->postJson('/api/mcp', [
            'jsonrpc' => '2.0',
            'id' => 44,
            'method' => 'tools/call',
            'params' => [
                'name' => 'nisoya_kesif_havuzu_yonet',
                'arguments' => [
                    'islem' => 'karar_ver',
                    'aday_id' => $target->id,
                    'karar' => 'onayla',
                ],
            ],
        ]);

        $resKarar->assertStatus(200);
        $this->assertEquals('onayli', $resKarar->json('result.structuredContent.yeni_durum'));

        // 4. Vitrin Üret
        $resVitrin = $this->withToken(self::TEST_API_KEY)->postJson('/api/mcp', [
            'jsonrpc' => '2.0',
            'id' => 45,
            'method' => 'tools/call',
            'params' => [
                'name' => 'nisoya_kesif_havuzu_yonet',
                'arguments' => [
                    'islem' => 'vitrin_uret',
                    'aday_id' => $target->id,
                ],
            ],
        ]);

        $resVitrin->assertStatus(200);
        $this->assertEquals('basarili', $resVitrin->json('result.structuredContent.durum'));
        $this->assertNotEmpty($resVitrin->json('result.structuredContent.claim_url'));
    }

    public function test_reklam_ve_alan_yonet_araci_listeler_ve_reklam_ekler(): void
    {
        $zone = Zone::create([
            'key' => 'mcp_test_alani',
            'name' => 'MCP Test Reklam Alanı',
            'location_note' => 'Anasayfa ortasında banner',
            'is_active' => true,
            'blocks' => [],
        ]);

        // 1. Listele
        $resList = $this->withToken(self::TEST_API_KEY)->postJson('/api/mcp', [
            'jsonrpc' => '2.0',
            'id' => 46,
            'method' => 'tools/call',
            'params' => [
                'name' => 'nisoya_reklam_ve_alan_yonet',
                'arguments' => ['islem' => 'alanlari_listele'],
            ],
        ]);

        $resList->assertStatus(200);
        $this->assertGreaterThanOrEqual(1, $resList->json('result.structuredContent.toplam_alan'));

        // 2. AI Reklam Üret
        $resAi = $this->withToken(self::TEST_API_KEY)->postJson('/api/mcp', [
            'jsonrpc' => '2.0',
            'id' => 47,
            'method' => 'tools/call',
            'params' => [
                'name' => 'nisoya_reklam_ve_alan_yonet',
                'arguments' => [
                    'islem' => 'ai_reklam_uret',
                    'alan_anahtari' => 'mcp_test_alani',
                    'kampanya_hedefi' => 'Esnaf Vitrin Sahiplendirme',
                ],
            ],
        ]);

        $resAi->assertStatus(200);
        $this->assertEquals('basarili', $resAi->json('result.structuredContent.durum'));
        $this->assertNotEmpty($resAi->json('result.structuredContent.baslik'));

        // 3. Blok Ekle
        $resBlok = $this->withToken(self::TEST_API_KEY)->postJson('/api/mcp', [
            'jsonrpc' => '2.0',
            'id' => 48,
            'method' => 'tools/call',
            'params' => [
                'name' => 'nisoya_reklam_ve_alan_yonet',
                'arguments' => [
                    'islem' => 'blok_ekle',
                    'alan_anahtari' => 'mcp_test_alani',
                    'baslik' => 'Avrupa Türk Esnafı Buluşuyor',
                    'buton_metni' => 'Hemen Katıl',
                    'buton_url' => '/sahiplen',
                ],
            ],
        ]);

        $resBlok->assertStatus(200);
        $this->assertEquals('basarili', $resBlok->json('result.structuredContent.durum'));
        $this->assertEquals(1, $resBlok->json('result.structuredContent.yeni_blok_sayisi'));

        $zone->refresh();
        $this->assertCount(1, $zone->blocks);
    }
}
