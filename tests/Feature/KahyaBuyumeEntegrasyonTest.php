<?php

namespace Tests\Feature;

use App\Ai\Kahya\Araclar\BuyumeRaporu;
use App\Ai\Kahya\Araclar\VitrinHazirla;
use App\Contracts\AiProvider;
use App\Enums\ListingStatus;
use App\Enums\ListingType;
use App\Enums\UserRole;
use App\Models\BekleyenHamle;
use App\Models\Category;
use App\Models\Country;
use App\Models\Listing;
use App\Models\OutreachTarget;
use App\Models\User;
use App\Services\Growth\BusinessSignal;
use App\Services\Growth\DetectionResult;
use App\Services\Growth\TurkishBusinessDetector;
use App\Services\Kahya\Eylem\Eylemler\SeoDoldur;
use App\Services\Kahya\PanelHaritasi;
use App\Support\Settings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Tools\Request;
use Tests\TestCase;

class KahyaBuyumeEntegrasyonTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Country::firstOrCreate(
            ['code' => 'DE'],
            ['name_tr' => 'Almanya', 'name_en' => 'Germany', 'is_active' => true, 'currency_code' => 'EUR', 'sort_order' => 1]
        );

        Category::firstOrCreate(
            ['slug' => 'hizmet'],
            ['name' => 'Hizmetler', 'type' => 'hizmet', 'is_active' => true, 'sort_order' => 1]
        );

        $this->admin = User::factory()->create(['role' => UserRole::Admin]);
    }

    /**
     * 1. Test: Kâhya "BuyumeRaporu" aracıyla Keşif Havuzu ve Büyüme hunisini canlı okur.
     */
    public function test_kahya_can_read_growth_report_metrics(): void
    {
        // Keşif havuzuna örnek adaylar ekle
        OutreachTarget::create([
            'name' => 'Berlin Dönerci Mehmet',
            'external_id' => 'de-1',
            'country_code' => 'DE',
            'city' => 'Berlin',
            'detection_band' => DetectionResult::BAND_TURKISH,
            'source' => 'test',
        ]);

        OutreachTarget::create([
            'name' => 'Almanya Pizza Express',
            'external_id' => 'de-2',
            'country_code' => 'DE',
            'city' => 'Berlin',
            'detection_band' => DetectionResult::BAND_NOT,
            'source' => 'test',
        ]);

        // Vitrin ilanı ekle
        Listing::create([
            'user_id' => $this->admin->id,
            'category_id' => 1,
            'type' => ListingType::Hizmet,
            'title' => 'Antepli Baklava Berlin',
            'slug' => 'antepli-baklava-berlin',
            'description' => 'Açıklama',
            'country_code' => 'DE',
            'city' => 'Berlin',
            'status' => ListingStatus::Aktif,
            'is_claimed' => true,
            'claimed_at' => now(),
            'claim_token' => 'token_de_1',
        ]);

        $tool = app(BuyumeRaporu::class);
        $result = (string) $tool->handle(new Request([]));

        $this->assertStringContainsString('NİSOYA BÜYÜME & TERSİNE KATILIM RAPORU', $result);
        $this->assertStringContainsString('Keşfedilen Türk İşletmesi: 1', $result);
        $this->assertStringContainsString('Toplam havuz: 2', $result);
        $this->assertStringContainsString('Sahiplenilen Vitrin (Yeni Üye): 1', $result);
    }

    /**
     * 2. Test: Kâhya "VitrinHazirla" aracıyla yeni bir işletme için anında tersine katılım vitrini üretir.
     */
    public function test_kahya_can_prepare_showcase_listing_via_growth_engine(): void
    {
        $tool = app(VitrinHazirla::class);

        $result = (string) $tool->handle(new Request([
            'isletme_adi' => 'Köln Usta Berber Hasan',
            'ulke_kodu' => 'DE',
            'sehir' => 'Köln',
            'kategori' => 'Hizmetler',
            'telefon' => '+49 221 1234567',
        ]));

        $this->assertStringContainsString('BAŞARILI: Sahiplenilebilir vitrin oluşturuldu', $result);
        $this->assertStringContainsString('/sahiplen/', $result);
        $this->assertStringContainsString('wa.me/492211234567', $result);

        // Veritabanında ilanın oluştuğunu ve is_claimed = false olduğunu doğrula
        $listing = Listing::where('title', 'Köln Usta Berber Hasan')->first();
        $this->assertNotNull($listing);
        $this->assertFalse($listing->is_claimed);
        $this->assertNotEmpty($listing->claim_token);
    }

    /**
     * 3. Test: Büyüme Ajanı sınırda adaylar için merkezi Yapay Zekâ motorunu (AiProvider) kullanır.
     */
    public function test_growth_detector_communicates_with_central_ai_provider(): void
    {
        $mockAi = $this->createMock(AiProvider::class);
        $mockAi->method('isConfigured')->willReturn(true);
        $mockAi->method('analyzeText')->willReturn([
            'turk_mu' => true,
            'guven' => 0.88,
            'sinyaller' => ['anadolu kökenli isim', 'türk mutfağı spesiyalleri'],
            'gerekce' => 'İşletme açıkça Türk lezzetleri sunuyor.',
        ]);

        $detector = new TurkishBusinessDetector($mockAi);

        // Sınırda kalan bir işletme sinyali (ne bariz Türk ne bariz yabancı)
        $signal = new BusinessSignal(
            name: 'Anatolia Delights Bistro',
            category: 'cafe',
            country: 'DE',
        );

        $decision = $detector->detect($signal);

        $this->assertTrue($decision->isTurkish);
        $this->assertSame(0.88, $decision->confidence);
        $this->assertSame(DetectionResult::BAND_TURKISH, $decision->band);
        $this->assertSame('llm', $decision->method);
    }

    /**
     * 4. Test: Kâhya'nın Panel Haritası "Pazarlama & Büyüme" sayfalarını tanır ve yol tarif edebilir.
     */
    public function test_kahya_panel_haritasi_knows_marketing_and_growth_pages(): void
    {
        $harita = app(PanelHaritasi::class);
        $metin = $harita->metin();

        // Pazarlama & Büyüme grubundaki sayfaların haritada olduğunu doğrula
        $this->assertStringContainsString('Pazarlama & Büyüme', $metin);
        $this->assertStringContainsString('Büyüme Ajanı', $metin);
        $this->assertStringContainsString('Keşif Havuzu', $metin);
        $this->assertStringContainsString('SEO', $metin);
    }

    /**
     * 5. Test: Kâhya SEO ayarlarını otonom olarak doldurabilir (SeoDoldur eylemi).
     */
    public function test_kahya_can_populate_seo_settings(): void
    {
        $eylem = app(SeoDoldur::class);

        $sonuc = $eylem->uygula([
            'baslik' => 'Nisoya - Yurtdışındaki Türkler İçin Pazaryeri',
            'aciklama' => 'Avrupa ve dünyadaki Türk esnafının buluşma noktası ve ilan platformu.',
        ]);

        $this->assertNotEmpty($sonuc['sonuc']);
        $this->assertSame('Nisoya - Yurtdışındaki Türkler İçin Pazaryeri', Settings::get('seo.default_title'));
        $this->assertSame('Avrupa ve dünyadaki Türk esnafının buluşma noktası ve ilan platformu.', Settings::get('seo.default_description'));
    }

    /**
     * 6. Test: Bekleyen Hamleler onay kuyruğu büyüme hamlelerini güvenle tutar.
     */
    public function test_bekleyen_hamleler_queues_growth_outreach_safely(): void
    {
        $hamle = BekleyenHamle::create([
            'baslik' => 'Berlin Esnafına WhatsApp Davet Mektubu',
            'tur' => 'eposta',
            'alici_eposta' => 'info@berlin-usta.de',
            'icerik' => 'Sayın işletme sahibi, vitrininiz nisoya.com/sahiplen/abc adresinde hazır...',
            'gerekce' => 'Keşfedilen esnafa vitrin sahiplenme daveti iletilmesi.',
            'durum' => 'beklemede',
        ]);

        $this->assertNotNull($hamle);
        $this->assertSame('beklemede', $hamle->durum);
        $this->assertSame(1, BekleyenHamle::beklemede()->count());
    }
}
