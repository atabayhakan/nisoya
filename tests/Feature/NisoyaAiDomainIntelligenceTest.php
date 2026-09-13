<?php

namespace Tests\Feature;

use App\Contracts\AiProvider;
use App\Enums\AccountType;
use App\Enums\EmploymentType;
use App\Enums\JobStatus;
use App\Enums\ListingStatus;
use App\Models\Company;
use App\Models\Listing;
use App\Models\User;
use App\Services\NisoyaAiYonlendirici;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\SahteAiSaglayici;
use Tests\TestCase;

class NisoyaAiDomainIntelligenceTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CurrencySeeder::class, CountrySeeder::class]);
        config(['ai.features.nisoya_ai_arama' => true]);

        $sahte = new SahteAiSaglayici([
            'niyet' => 'belirsiz',
            'ulke_kodu' => null,
            'sehir' => null,
            'islem_turu_slug' => null,
            'yasam_kategori_slug' => null,
            'anahtar_kelimeler' => [],
        ]);
        $this->app->instance(AiProvider::class, $sahte);
    }

    public function test_is_aramasi_kirgizistanda_bosken_akilli_cevap_ve_eylem_butonlari_doner(): void
    {
        $yonlendirici = app(NisoyaAiYonlendirici::class);

        $this->assertTrue($yonlendirici->aranmaliMi('iş'));

        $sonuc = $yonlendirici->ara('iş', 'KG');

        $this->assertSame('is', $sonuc['niyet']);
        $this->assertStringContainsString('Kırgızistan', $sonuc['baslik'] ?? '');
        $this->assertStringContainsString('Kırgızistan', $sonuc['mesaj'] ?? '');
        $this->assertStringContainsString('bulunmuyor', $sonuc['mesaj'] ?? '');
        $this->assertSame('KG', $sonuc['ulke']['kod']);
        $this->assertSame('🇰🇬', $sonuc['ulke']['emoji']);

        // Eylemler: İlk ilanı yayınla ve tüm ülkelerdeki işleri gör
        $eylemler = $sonuc['eylemler'] ?? [];
        $this->assertNotEmpty($eylemler);

        $urls = array_column($eylemler, 'url');
        $this->assertTrue(collect($urls)->contains(fn ($u) => str_contains($u, '/panel/is-ilani/yeni')));
        $this->assertTrue(collect($urls)->contains(fn ($u) => str_contains($u, '/isler')));
    }

    public function test_is_aramasi_aktif_ilanlar_varsa_gercek_kayitlari_listeler(): void
    {
        $isveren = User::factory()->create(['country_code' => 'DE']);
        $isveren->update(['account_type' => AccountType::Kurumsal]);
        $sirket = Company::create(['user_id' => $isveren->id, 'name' => 'Berlin Tech GmbH', 'slug' => 'berlin-tech']);

        $sirket->jobListings()->create([
            'title' => 'Kıdemli PHP Yazılımcı',
            'slug' => 'kidemli-php-yazilimci',
            'description' => 'Laravel ve modern mimarilere hakim yazılımcı aranıyor.',
            'employment_type' => EmploymentType::TamZamanli,
            'status' => JobStatus::Aktif,
            'country_code' => 'DE',
            'city' => 'Berlin',
            'salary_min' => '5000',
            'salary_currency' => 'EUR',
            'positions' => 1,
        ]);

        $yonlendirici = app(NisoyaAiYonlendirici::class);
        $sonuc = $yonlendirici->ara('iş', 'DE');

        $this->assertSame('is', $sonuc['niyet']);
        $this->assertNotEmpty($sonuc['sonuclar']);

        $basliklar = $sonuc['sonuclar']->pluck('baslik')->all();
        $this->assertTrue(collect($basliklar)->contains(fn ($b) => str_contains($b, 'Kıdemli PHP Yazılımcı')));

        $altbasliklar = $sonuc['sonuclar']->pluck('altbaslik')->all();
        $this->assertTrue(collect($altbasliklar)->contains(fn ($a) => str_contains($a, 'Berlin Tech GmbH')));
    }

    public function test_ilan_aramasi_kirgizistanda_bosken_akilli_cevap_ve_eylem_butonlari_doner(): void
    {
        $yonlendirici = app(NisoyaAiYonlendirici::class);

        $this->assertTrue($yonlendirici->aranmaliMi('avukat'));

        $sonuc = $yonlendirici->ara('avukat', 'KG');

        $this->assertSame('ilan', $sonuc['niyet']);
        $this->assertStringContainsString('Kırgızistan', $sonuc['baslik'] ?? '');
        $this->assertStringContainsString('Kırgızistan', $sonuc['mesaj'] ?? '');
        $this->assertStringContainsString('bulunamadı', $sonuc['mesaj'] ?? '');

        // Eylemler: İlan ver ve pazar yerinde ara
        $eylemler = $sonuc['eylemler'] ?? [];
        $this->assertNotEmpty($eylemler);

        $urls = array_column($eylemler, 'url');
        $this->assertTrue(collect($urls)->contains(fn ($u) => str_contains($u, '/ilan-ver')));
        $this->assertTrue(collect($urls)->contains(fn ($u) => str_contains($u, '/ilanlar')));
    }

    public function test_ilan_aramasi_aktif_kayitlar_varsa_gercek_ilanlari_listeler(): void
    {
        $user = User::factory()->create(['country_code' => 'DE']);

        Listing::factory()->create([
            'user_id' => $user->id,
            'title' => 'Frankfurt Türkçe Bilen Avukatlık Danışmanlığı',
            'slug' => 'frankfurt-turkce-avukat',
            'description' => 'Göçmenlik ve şirketler hukuku konusunda Türkçe destek.',
            'status' => ListingStatus::Aktif,
            'country_code' => 'DE',
            'city' => 'Frankfurt',
            'price' => 150,
            'currency' => 'EUR',
        ]);

        $yonlendirici = app(NisoyaAiYonlendirici::class);
        $sonuc = $yonlendirici->ara('avukat', 'DE');

        $this->assertSame('ilan', $sonuc['niyet']);
        $this->assertNotEmpty($sonuc['sonuclar']);

        $basliklar = $sonuc['sonuclar']->pluck('baslik')->all();
        $this->assertTrue(collect($basliklar)->contains(fn ($b) => str_contains($b, 'Frankfurt Türkçe Bilen Avukatlık')));
    }

    public function test_pasaport_aramasi_rehberde_yokken_konsolosluk_akilli_cevap_ve_cagri_merkezi_doner(): void
    {
        $yonlendirici = app(NisoyaAiYonlendirici::class);

        $this->assertTrue($yonlendirici->aranmaliMi('pasaport'));

        // Kırgızistan'da henüz pasaport rehberi seeded değilken
        $sonuc = $yonlendirici->ara('pasaport', 'KG');

        $this->assertSame('belirsiz', $sonuc['niyet']);
        $this->assertStringContainsString('Konsolosluk Rehberi', $sonuc['baslik'] ?? '');
        $this->assertStringContainsString('Kırgızistan', $sonuc['baslik'] ?? '');

        $eylemler = $sonuc['eylemler'] ?? [];
        $this->assertNotEmpty($eylemler);

        $urls = array_column($eylemler, 'url');
        $this->assertTrue(collect($urls)->contains(fn ($u) => str_contains($u, 'konsolosluk.gov.tr')));
        $this->assertTrue(collect($urls)->contains(fn ($u) => str_contains($u, 'tel:+903122922929')));
        $this->assertTrue(collect($urls)->contains(fn ($u) => str_contains($u, '/rehber')));
    }

    public function test_arama_ai_endpointi_is_ve_ilan_icin_akilli_yanit_verir(): void
    {
        // 1. İş araması JSON uç testi
        $resIs = $this->getJson('/arama/ai?q=iş&ulke=KG');
        $resIs->assertOk();
        $resIs->assertJson([
            'niyet' => 'is',
            'aktif' => true,
        ]);
        $this->assertStringContainsString('Kırgızistan', $resIs->json('baslik'));
        $this->assertNotEmpty($resIs->json('eylemler'));

        // 2. İlan araması JSON uç testi
        $resIlan = $this->getJson('/arama/ai?q=avukat&ulke=KG');
        $resIlan->assertOk();
        $resIlan->assertJson([
            'niyet' => 'ilan',
            'aktif' => true,
        ]);
        $this->assertStringContainsString('Kırgızistan', $resIlan->json('baslik'));
        $this->assertNotEmpty($resIlan->json('eylemler'));
    }
}
