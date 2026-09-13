<?php

namespace Tests\Feature;

use App\Contracts\AiProvider;
use App\Enums\FootballLevel;
use App\Models\FootballTeam;
use App\Models\User;
use App\Services\NisoyaAiYonlendirici;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\SahteAiSaglayici;
use Tests\TestCase;

class NisoyaAiSportsAndActionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CurrencySeeder::class, CountrySeeder::class]);
        config(['ai.features.nisoya_ai_arama' => true]);

        $sahte = new SahteAiSaglayici([
            'niyet' => 'spor',
            'ulke_kodu' => null,
            'sehir' => null,
            'islem_turu_slug' => null,
            'yasam_kategori_slug' => null,
            'anahtar_kelimeler' => ['maç'],
        ]);
        $this->app->instance(AiProvider::class, $sahte);
    }

    public function test_mac_aramasi_kirgizistanda_bosken_akilli_cevap_ve_eylem_butonlari_doner(): void
    {
        $yonlendirici = app(NisoyaAiYonlendirici::class);

        $this->assertTrue($yonlendirici->aranmaliMi('maç'));

        $sonuc = $yonlendirici->ara('maç', 'KG');

        $this->assertSame('spor', $sonuc['niyet']);
        $this->assertStringContainsString('Kırgızistan', $sonuc['baslik'] ?? '');
        $this->assertStringContainsString('Kırgızistan', $sonuc['mesaj'] ?? '');
        $this->assertStringContainsString('oluşturulmamış', $sonuc['mesaj'] ?? '');

        // Eylemler içinde takım kurma ve maç ilanı butonları olmalı
        $eylemler = $sonuc['eylemler'] ?? [];
        $this->assertNotEmpty($eylemler);

        $urls = array_column($eylemler, 'url');
        $this->assertTrue(collect($urls)->contains(fn ($u) => str_contains($u, '/spor/takim/yeni')));
        $this->assertTrue(collect($urls)->contains(fn ($u) => str_contains($u, '/spor/ilan/yeni')));
    }

    public function test_mac_aramasi_takim_ve_mac_varsa_gercek_sonuclari_listeler(): void
    {
        $user = User::factory()->create(['country_code' => 'DE']);

        FootballTeam::query()->create([
            'user_id' => $user->id,
            'name' => 'Berlin Kartalları FC',
            'slug' => 'berlin-kartallari-fc',
            'city' => 'Berlin',
            'country_code' => 'DE',
            'level' => FootballLevel::Orta,
            'is_active' => true,
            'is_verified' => true,
        ]);

        $yonlendirici = app(NisoyaAiYonlendirici::class);
        $sonuc = $yonlendirici->ara('maç', 'DE');

        $this->assertSame('spor', $sonuc['niyet']);
        $this->assertNotEmpty($sonuc['sonuclar']);

        $basliklar = $sonuc['sonuclar']->pluck('baslik')->all();
        $this->assertTrue(collect($basliklar)->contains(fn ($b) => str_contains($b, 'Berlin Kartalları FC')));
    }

    public function test_ilan_ver_veya_takim_kur_eylem_niyetini_tetikler(): void
    {
        $yonlendirici = app(NisoyaAiYonlendirici::class);

        // 1. İlan ver eylemi
        $sonucIlan = $yonlendirici->ara('ilan vermek istiyorum', 'KG');
        $this->assertSame('eylem', $sonucIlan['niyet']);
        $this->assertStringContainsString('İlan Ver', $sonucIlan['baslik'] ?? '');
        $eylemUrls = array_column($sonucIlan['eylemler'] ?? [], 'url');
        $this->assertTrue(collect($eylemUrls)->contains(fn ($u) => str_contains($u, '/ilan-ver')));

        // 2. Takım kur eylemi
        $sonucTakim = $yonlendirici->ara('takım kurmak istiyorum', 'KG');
        $this->assertSame('eylem', $sonucTakim['niyet']);
        $this->assertStringContainsString('Futbol Takımı Kur', $sonucTakim['baslik'] ?? '');
        $takimUrls = array_column($sonucTakim['eylemler'] ?? [], 'url');
        $this->assertTrue(collect($takimUrls)->contains(fn ($u) => str_contains($u, '/spor/takim/yeni')));
    }

    public function test_arama_ai_endpointi_spor_ve_akilli_cevap_doner(): void
    {
        $response = $this->getJson('/arama/ai?q=maç&ulke=KG');

        $response->assertOk();
        $response->assertJsonStructure([
            'niyet',
            'baslik',
            'mesaj',
            'oneri',
            'eylemler',
            'sonuclar',
            'ilanBaglantisi',
            'aktif',
        ]);

        $response->assertJson([
            'niyet' => 'spor',
            'aktif' => true,
        ]);

        $this->assertStringContainsString('Kırgızistan', $response->json('baslik'));
        $this->assertStringContainsString('Kırgızistan', $response->json('mesaj'));
    }
}
