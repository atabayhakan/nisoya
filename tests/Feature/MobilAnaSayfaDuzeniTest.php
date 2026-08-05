<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Models\Category;
use App\Models\Country;
use App\Models\Listing;
use App\Models\User;
use App\Services\VisitorLocationService;
use App\Support\Settings;
use App\Support\Tema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Mobil ana sayfa düzeni — "uygulama gibi" ilk ekran (2026-08-05).
 *
 * ---------------------------------------------------------------------------
 * TASARIM REFERANSINDAN NE ALINDI, NE ALINMADI
 *
 * Sahip bir mockup paylaştı. Yerleşim fikirleri alındı (tam genişlik arama
 * eylemi, yatay kategori şeridi, ülkenin hazır gelmesi); mockup'taki SAYILAR
 * alınmadı — "126 kişi hizmet buldu", "4.9 / 27.000+ değerlendirme",
 * "18.400+ mutlu kullanıcı", "25.000+ aktif ilan" ve isimli/fotoğraflı
 * "az önce hizmet bulanlar" akışı uydurmadır. Sitede 3 gerçek ilan var.
 *
 * Proje doktrini bu konuda net: hiçbir sayı demoyla ya da hayalle şişirilmez.
 * Bu tur, aynı doktrini iki gün önce ihlal eden bir hatayı düzeltmişti
 * (bkz. MobilIlkIzlenimTest) — referans uğruna geri almak tutarsızlık olurdu.
 */
class MobilAnaSayfaDuzeniTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Settings::setMany(['gorunum.tema' => 'vitrin']);
        Cache::flush();
        $this->assertTrue(Tema::vitrinMi());
    }

    public function test_arama_eylemi_mobilde_tam_genislik_masaustunde_degil(): void
    {
        // Mobilde başparmakla ulaşılan birincil eylem; masaüstünde satır içi
        // kalmalı. Aynı düğme, iki farklı etiket.
        $icerik = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('Hemen bul', $icerik);
        $this->assertStringContainsString('>Ara', $icerik);
    }

    public function test_ziyaretcinin_ulkesi_arama_kutusunda_hazir_gelir(): void
    {
        Country::firstOrCreate(['code' => 'DE'], [
            'name_tr' => 'Almanya', 'emoji' => '🇩🇪', 'is_active' => true, 'sort_order' => 0,
        ]);

        // Servis testlerde bilinçle null döner; ülke tespitini taklit etmek
        // için konteynerde değiştiriliyor.
        $this->swap(VisitorLocationService::class, new class extends VisitorLocationService
        {
            public function resolve($request): ?object
            {
                return (object) ['code' => 'DE', 'name' => 'Almanya', 'emoji' => '🇩🇪'];
            }
        });

        $icerik = $this->get('/')->assertOk()->getContent();

        $this->assertMatchesRegularExpression(
            '/<option value="DE"[^>]*\bselected\b/i',
            $icerik,
            'Ziyaretçinin ülkesi arama kutusunda seçili gelmiyor.'
        );
    }

    public function test_ulke_tespit_edilemezse_uydurulmaz(): void
    {
        // Tespit yoksa "Tüm ülkeler" kalır. Rastgele bir ülke seçmek,
        // ziyaretçiye yanlış yerde arama yaptırırdı.
        Country::firstOrCreate(['code' => 'DE'], [
            'name_tr' => 'Almanya', 'emoji' => '🇩🇪', 'is_active' => true, 'sort_order' => 0,
        ]);

        $icerik = $this->get('/')->assertOk()->getContent();

        $this->assertDoesNotMatchRegularExpression('/<option value="[A-Z]{2}"[^>]*\bselected\b/i', $icerik);
    }

    public function test_kategoriler_mobilde_serit_masaustunde_izgara(): void
    {
        foreach (['Ev & Tamir', 'Eğitim & Ders', 'Nakliyat'] as $sira => $ad) {
            Category::create([
                'name' => $ad, 'slug' => 'k-'.$sira, 'icon' => '🔧',
                'is_active' => true, 'parent_id' => null, 'sort_order' => $sira,
            ]);
        }

        $icerik = $this->get('/')->assertOk()->getContent();

        // Şerit yalnız mobilde (sm:hidden), ızgara yalnız sm+ (hidden sm:grid).
        $this->assertStringContainsString('snap-x', $icerik, 'Mobil kategori şeridi basılmıyor.');
        $this->assertStringContainsString('sm:hidden', $icerik);
        $this->assertStringContainsString('hidden grid-cols-2', $icerik, 'Masaüstü ızgarası mobilde gizlenmemiş.');
    }

    public function test_uydurma_sosyal_kanit_sayilari_eklenmedi(): void
    {
        // Referans mockup'taki rakamlar. Biri bile sayfaya girerse bu test
        // kırılır — doktrinin bekçisi.
        $icerik = $this->get('/')->assertOk()->getContent();

        foreach (['18.400', '27.000', '25.000', 'mutlu kullanıcı', 'kişi hizmet buldu'] as $uydurma) {
            $this->assertStringNotContainsString($uydurma, $icerik, "Uydurma sosyal kanıt sayfaya girmiş: {$uydurma}");
        }
    }

    public function test_gercek_ilan_sayisi_hala_dogru(): void
    {
        Listing::factory()->for(User::factory())->create([
            'status' => ListingStatus::Aktif, 'is_demo' => false,
        ]);
        Listing::factory()->for(User::factory())->create([
            'status' => ListingStatus::Aktif, 'is_demo' => true,
        ]);

        $this->assertSame(1, Listing::query()->active()->where('is_demo', false)->count());
    }
}
