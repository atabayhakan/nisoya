<?php

namespace Tests\Feature;

use App\Ai\Kahya\Araclar\BuyumeRaporu;
use App\Enums\ListingStatus;
use App\Enums\ListingType;
use App\Enums\UserRole;
use App\Models\BekleyenHamle;
use App\Models\Category;
use App\Models\Country;
use App\Models\Listing;
use App\Models\OutreachTarget;
use App\Models\User;
use App\Services\Growth\BuyumeMetrikleriServisi;
use App\Services\Growth\DetectionResult;
use App\Services\Kahya\BekleyenIsler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Ai\Tools\Request;
use Tests\TestCase;

class BuyumeMetrikleriTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        Country::firstOrCreate(
            ['code' => 'NL'],
            ['name_tr' => 'Hollanda', 'name_en' => 'Netherlands', 'is_active' => true, 'currency_code' => 'EUR', 'sort_order' => 1]
        );

        Country::firstOrCreate(
            ['code' => 'TR'],
            ['name_tr' => 'Türkiye', 'name_en' => 'Turkey', 'is_active' => true, 'currency_code' => 'TRY', 'sort_order' => 2]
        );

        Category::firstOrCreate(
            ['slug' => 'hizmet'],
            ['name' => 'Hizmetler', 'type' => 'hizmet', 'is_active' => true, 'sort_order' => 1]
        );

        $this->admin = User::factory()->create(['role' => UserRole::Admin]);
    }

    public function test_buyume_metrikleri_ozet_calculates_correct_ratios(): void
    {
        $servis = app(BuyumeMetrikleriServisi::class);

        // 1. Keşif adayları
        OutreachTarget::create([
            'name' => 'Hedef 1',
            'external_id' => 'p1',
            'detection_band' => DetectionResult::BAND_TURKISH,
            'source' => 'test',
        ]);
        OutreachTarget::create([
            'name' => 'Hedef 2',
            'external_id' => 'p2',
            'detection_band' => DetectionResult::BAND_NOT,
            'source' => 'test',
        ]);

        // 2. Vitrinler
        Listing::create([
            'user_id' => $this->admin->id,
            'category_id' => 1,
            'type' => ListingType::Hizmet,
            'title' => 'Vitrin 1',
            'slug' => 'vitrin-1',
            'description' => 'Açıklama',
            'country_code' => 'NL',
            'city' => 'Rotterdam',
            'status' => ListingStatus::Aktif,
            'is_claimed' => false,
            'claim_token' => 'token_1',
        ]);

        Listing::create([
            'user_id' => $this->admin->id,
            'category_id' => 1,
            'type' => ListingType::Hizmet,
            'title' => 'Vitrin 2',
            'slug' => 'vitrin-2',
            'description' => 'Açıklama',
            'country_code' => 'NL',
            'city' => 'Rotterdam',
            'status' => ListingStatus::Aktif,
            'is_claimed' => true,
            'claimed_at' => now(),
            'claim_token' => 'token_2',
        ]);

        // 3. Hamle
        BekleyenHamle::create([
            'baslik' => 'Test Hamle',
            'gerekce' => 'Gerekçe',
            'icerik' => 'İçerik',
            'tur' => 'eposta',
            'durum' => BekleyenHamle::DURUM_BEKLEMEDE,
        ]);

        $ozet = $servis->ozet();

        $this->assertSame(2, $ozet['toplam_kesif']);
        $this->assertSame(1, $ozet['turk_isletmeler']);
        $this->assertSame(2, $ozet['hazirlanan_vitrinler']);
        $this->assertSame(1, $ozet['bekleyen_vitrinler']);
        $this->assertSame(1, $ozet['sahiplenilen_vitrinler']);
        $this->assertSame(50.0, $ozet['donusum_orani']); // 1 / 2 = %50
        $this->assertSame(1, $ozet['onay_bekleyen_hamleler']);
    }

    public function test_buyume_metrikleri_sehir_bazli_ranks_cities(): void
    {
        $servis = app(BuyumeMetrikleriServisi::class);

        Listing::create([
            'user_id' => $this->admin->id,
            'category_id' => 1,
            'type' => ListingType::Hizmet,
            'title' => 'Rotterdam Dükkan 1',
            'slug' => 'rotterdam-1',
            'description' => 'Açıklama',
            'country_code' => 'NL',
            'city' => 'Rotterdam',
            'status' => ListingStatus::Aktif,
            'is_claimed' => true,
            'claimed_at' => now(),
            'claim_token' => 'tok_r1',
        ]);

        Listing::create([
            'user_id' => $this->admin->id,
            'category_id' => 1,
            'type' => ListingType::Hizmet,
            'title' => 'Rotterdam Dükkan 2',
            'slug' => 'rotterdam-2',
            'description' => 'Açıklama',
            'country_code' => 'NL',
            'city' => 'Rotterdam',
            'status' => ListingStatus::Aktif,
            'is_claimed' => false,
            'claim_token' => 'tok_r2',
        ]);

        Listing::create([
            'user_id' => $this->admin->id,
            'category_id' => 1,
            'type' => ListingType::Hizmet,
            'title' => 'Amsterdam Dükkan 1',
            'slug' => 'amsterdam-1',
            'description' => 'Açıklama',
            'country_code' => 'NL',
            'city' => 'Amsterdam',
            'status' => ListingStatus::Aktif,
            'is_claimed' => false,
            'claim_token' => 'tok_a1',
        ]);

        $sehirler = $servis->sehirBazli(5);

        $this->assertNotEmpty($sehirler);
        $this->assertSame('Rotterdam', $sehirler[0]['sehir']);
        $this->assertSame(2, $sehirler[0]['toplam_vitrin']);
        $this->assertSame(1, $sehirler[0]['sahiplenilen']);
        $this->assertSame(50.0, $sehirler[0]['oran']);

        $this->assertSame('Amsterdam', $sehirler[1]['sehir']);
        $this->assertSame(1, $sehirler[1]['toplam_vitrin']);
    }

    public function test_buyume_raporu_tool_returns_formatted_stats(): void
    {
        Listing::create([
            'user_id' => $this->admin->id,
            'category_id' => 1,
            'type' => ListingType::Hizmet,
            'title' => 'Dönerci Ali',
            'slug' => 'donerci-ali',
            'description' => 'Açıklama',
            'country_code' => 'NL',
            'city' => 'Rotterdam',
            'status' => ListingStatus::Aktif,
            'is_claimed' => true,
            'claimed_at' => now(),
            'claim_token' => 'token_ali',
        ]);

        $tool = app(BuyumeRaporu::class);
        $cikti = (string) $tool->handle(new Request([]));

        $this->assertStringContainsString('NİSOYA BÜYÜME & TERSİNE KATILIM RAPORU', $cikti);
        $this->assertStringContainsString('Hazırlanan Ön Vitrin: 1 adet', $cikti);
        $this->assertStringContainsString('Sahiplenilen Vitrin (Yeni Üye): 1 adet', $cikti);
        $this->assertStringContainsString('Genel Sahiplenme Dönüşüm Oranı: %100', $cikti);
        $this->assertStringContainsString('Rotterdam (NL)', $cikti);
    }

    public function test_bekleyen_isler_includes_pending_hamleler(): void
    {
        BekleyenHamle::create([
            'baslik' => 'E-posta Daveti',
            'gerekce' => 'Gerekçe',
            'icerik' => 'İçerik',
            'tur' => 'eposta',
            'durum' => BekleyenHamle::DURUM_BEKLEMEDE,
        ]);

        $bekleyen = app(BekleyenIsler::class)->topla();

        $hamleKuyrugu = collect($bekleyen)->firstWhere('anahtar', 'hamle_beklemede');
        $this->assertNotNull($hamleKuyrugu);
        $this->assertSame('Onay bekleyen davet mektubu', $hamleKuyrugu['etiket']);
        $this->assertSame(1, $hamleKuyrugu['adet']);
    }
}
