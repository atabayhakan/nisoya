<?php

namespace Tests\Feature;

use App\Enums\ListingStatus;
use App\Models\Country;
use App\Models\Listing;
use App\Models\Page;
use App\Models\User;
use App\Services\NabizService;
use App\Support\Settings;
use App\Support\Tema;
use Database\Seeders\GuvenliAlisverisSayfasiSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

/**
 * Mobil ilk izlenim turu (A + B + C).
 *
 * EN KRİTİK BEKÇİ `demo_ilan_ulke_sayisina_giremez`: canlıda ana sayfada 100
 * piksel arayla iki öge birbirinin tersini söylüyordu — kanıt şeridi "ilk
 * ilanı sen ver" (demo süzülü) derken ülke satırı "12 aktif ilan" (demo dahil)
 * diyordu. Kök neden tek eksik `where('is_demo', false)` koşuluydu ve üç
 * yüzeye birden sızıyordu.
 *
 * Kural zaten vardı ve her yerde uygulanıyordu; burada unutulmuştu. Bu test o
 * unutmanın tekrarını imkânsız kılıyor.
 */
class MobilIlkIzlenimTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Settings::setMany(['gorunum.tema' => 'vitrin']);
        Cache::flush();

        $this->assertTrue(Tema::vitrinMi());
    }

    /**
     * countryActivity() yalnız AKTİF ve KOORDİNATLI ülkeleri döndürür
     * (harita için). Kayıt olmadan sonuç boş gelir ve test yanlış sebeple
     * kırılır — o yüzden ülke burada kuruluyor.
     */
    private function almanya(): void
    {
        Country::firstOrCreate(['code' => 'DE'], [
            'name_tr' => 'Almanya', 'emoji' => '🇩🇪', 'is_active' => true,
            'latitude' => 51.1657, 'longitude' => 10.4515, 'sort_order' => 0,
        ]);
    }

    private function ilan(array $nitelikler = []): Listing
    {
        return Listing::factory()->for(User::factory())->create(array_merge([
            'status' => ListingStatus::Aktif,
            'country_code' => 'DE',
        ], $nitelikler));
    }

    // ------------------------------------------------------------------ A

    public function test_demo_ilan_ulke_sayisina_giremez(): void
    {
        $this->almanya();
        $this->ilan(['is_demo' => false]);
        $this->ilan(['is_demo' => true]);
        $this->ilan(['is_demo' => true]);

        $almanya = collect(app(NabizService::class)->countryActivity())
            ->firstWhere('code', 'DE');

        $this->assertNotNull($almanya);
        $this->assertSame(1, $almanya['count'], 'Demo ilanlar ülke sayısına sızdı — kural ihlali geri geldi.');
    }

    public function test_ana_sayfa_ulke_seridinde_sayi_gostermez(): void
    {
        // Süzgeç eklendikten sonra sayı "3" olacaktı; DÜŞÜK SAYI negatif
        // sosyal kanıttır. Şerit artık yer gösteriyor, rakam değil.
        $this->almanya();
        $this->ilan(['is_demo' => false]);

        $this->get('/')
            ->assertOk()
            ->assertDontSee('aktif ilan');
    }

    // ------------------------------------------------------------------ B

    public function test_h1_urunu_tanimlar(): void
    {
        // H1 artık duygu cümlesi değil, ürünün ne olduğunu söylüyor.
        $this->get('/')
            ->assertOk()
            ->assertSee('Şehrindeki Türkçe konuşan')
            ->assertSee('nakliyeciyi bul');
    }

    public function test_slogan_silinmedi_bir_basamak_indi(): void
    {
        $this->get('/')->assertSee('Türkçe anlat, iş bitsin.');
    }

    public function test_kapsam_cipleri_ilk_ekranda(): void
    {
        // Kapsam, okunmayan bir paragraf yerine taranabilir çiplerde.
        $yanit = $this->get('/')->assertOk();

        foreach (['Nakliyeci', 'Tercüman', 'İş ilanı', 'Ülke rehberi'] as $kapsam) {
            $yanit->assertSee($kapsam);
        }
    }

    public function test_kapsam_cipleri_tiklanabilir_degil(): void
    {
        // Tıklanabilir olsalardı boş kategori sayfasına götürür ve tutulmamış
        // bir vaat olurlardı. Envanter büyüyünce bağlanabilirler.
        $icerik = $this->get('/')->getContent();

        $this->assertMatchesRegularExpression(
            '/aria-label="Nisoya\'da neler var"[^>]*>(?:(?!<\/ul>).)*?<li/su',
            $icerik,
            'Kapsam çipleri liste öğesi olmalı; <a> olurlarsa envanter vaadi olurlar.'
        );
    }

    // ------------------------------------------------------------------ C

    public function test_yapisal_guven_satiri_ilk_ekranda(): void
    {
        // "Ücretsiz" bunun yerini tutmaz: ücretsiz FİYAT der, bu RİSK der.
        $this->get('/')
            ->assertOk()
            ->assertSee('para geçmez.')
            ->assertSee('Komisyon yok, aracı yok.');
    }

    public function test_guven_baglantisinin_hedefi_gercekten_var(): void
    {
        // Hedefsiz bağlantı, bu turda kaçınılması gerektiğini yazdığımız
        // şeyin ta kendisi olurdu: tutulmayan vaat.
        $this->seed(GuvenliAlisverisSayfasiSeeder::class);

        $this->assertNotNull(Page::where('slug', 'guvenli-alisveris')->first());

        $this->get('/guvenli-alisveris')
            ->assertOk()
            ->assertSee('Mal ve Hizmetler');
    }
}
