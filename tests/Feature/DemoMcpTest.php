<?php

namespace Tests\Feature;

use App\Mcp\Araclar\Demo\DemoDurum;
use App\Mcp\Araclar\Demo\DemoSil;
use App\Mcp\Araclar\Demo\DemoUret;
use App\Mcp\Araclar\KahyaAraci;
use App\Mcp\Sunucular\DemoSunucusu;
use App\Mcp\Sunucular\KahyaSunucusu;
use App\Models\DemoKaydi;
use App\Models\Listing;
use App\Models\User;
use App\Support\Settings;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Storage;
use ReflectionProperty;
use Tests\TestCase;

/**
 * Demo MCP sunucusu — Faz B.
 *
 * ASIL BEKÇİ `test_kahya_sunucusu_yazma_araci_barindirmaz`: bu iki sunucunun
 * ayrı kalması bütün tasarımın dayandığı şey. Kâhya'nın yazamadığı
 * `KahyaMcpTest` ile kanıtlı; yazma araçları oraya sızarsa o kanıt çöker ve
 * salt-okunurluk "çoğu zaman açık bir bayrak" hâline gelir.
 */
class DemoMcpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
        $this->seed([CurrencySeeder::class, CountrySeeder::class, CategorySeeder::class]);
    }

    private function kapiyiAc(): void
    {
        Settings::setMany(['demo.mcp_acik' => '1']);
    }

    // ------------------------------------------------------------- Ayrılık

    /** @return array<int, class-string> */
    private function sunucuAraclari(string $sunucu): array
    {
        return (new ReflectionProperty($sunucu, 'tools'))->getDefaultValue();
    }

    public function test_kahya_sunucusu_yazma_araci_barindirmaz(): void
    {
        foreach ($this->sunucuAraclari(KahyaSunucusu::class) as $sinif) {
            $this->assertTrue(
                is_subclass_of($sinif, KahyaAraci::class),
                "{$sinif} Kâhya sunucusunda ama salt-okunur tabandan türemiyor."
            );
        }

        $this->assertNotContains(DemoUret::class, $this->sunucuAraclari(KahyaSunucusu::class));
        $this->assertNotContains(DemoSil::class, $this->sunucuAraclari(KahyaSunucusu::class));
    }

    public function test_demo_sunucusu_uc_araci_kaydeder(): void
    {
        $this->assertSame(
            [DemoDurum::class, DemoUret::class, DemoSil::class],
            $this->sunucuAraclari(DemoSunucusu::class),
        );
    }

    // ---------------------------------------------------------------- Kapı

    /**
     * Kapı kapalıyken yazma araçları `tools/list` içinde HİÇ görünmez —
     * paket `shouldRegister()`'ı kayıt sırasında container üzerinden çağırır.
     */
    public function test_kapi_kapaliyken_yazma_araclari_kaydedilmez(): void
    {
        $this->assertFalse(app(DemoUret::class)->shouldRegister());
        $this->assertFalse(app(DemoSil::class)->shouldRegister());
    }

    public function test_kapi_acikken_yazma_araclari_kaydedilir(): void
    {
        $this->kapiyiAc();

        $this->assertTrue(app(DemoUret::class)->shouldRegister());
        $this->assertTrue(app(DemoSil::class)->shouldRegister());
    }

    /**
     * Durum aracı HER ZAMAN kayıtlı ve kapının kapalı olduğunu söylüyor.
     * Sessizce kaybolan bir yetenek, hata ayıklanamaz bir yetenektir.
     */
    public function test_durum_araci_kapali_kapiyi_acikca_soyler(): void
    {
        DemoSunucusu::tool(DemoDurum::class)
            ->assertOk()
            ->assertName('demo-durum')
            ->assertSee('KAPALI')
            ->assertSee('Örnek Veri');
    }

    public function test_durum_araci_acik_kapiyi_bildirir(): void
    {
        $this->kapiyiAc();

        DemoSunucusu::tool(DemoDurum::class)
            ->assertOk()
            ->assertSee('"acik":true');
    }

    // --------------------------------------------------------- Uçtan uca

    public function test_mcp_uzerinden_uret_ve_geri_al(): void
    {
        $this->kapiyiAc();

        $oncekiUye = User::query()->count();
        $oncekiIlan = Listing::query()->count();

        DemoSunucusu::tool(DemoUret::class, ['uye' => 2, 'ilan' => 1])
            ->assertOk()
            ->assertSee('geri_alma');

        $this->assertSame($oncekiUye + 2, User::query()->count());
        $this->assertSame($oncekiIlan + 2, Listing::query()->count());

        $parti = DemoKaydi::query()->value('parti');

        DemoSunucusu::tool(DemoSil::class, ['parti' => $parti])->assertOk();

        $this->assertSame($oncekiUye, User::query()->count());
        $this->assertSame($oncekiIlan, Listing::query()->count());
        $this->assertSame(0, DemoKaydi::query()->count());
    }

    /** Şemaya güvenilmez: sınırı kod uygular. */
    public function test_uret_araci_sinirlari_kirpar(): void
    {
        $this->kapiyiAc();

        DemoSunucusu::tool(DemoUret::class, ['uye' => 999999, 'ilan' => 0])
            ->assertOk()
            ->assertSee('"uye":50');
    }

    public function test_varsayilan_gizli_uretir(): void
    {
        $this->kapiyiAc();

        DemoSunucusu::tool(DemoUret::class, ['uye' => 2, 'ilan' => 1])
            ->assertOk()
            ->assertSee('taslak');

        $this->assertSame(0, Listing::query()->where('is_demo', true)->where('status', 'aktif')->count());
    }

    public function test_bilinmeyen_parti_silinmeye_calisilinca_liste_doner(): void
    {
        $this->kapiyiAc();

        DemoSunucusu::tool(DemoSil::class, ['parti' => 'yok-boyle-bir-parti'])
            ->assertOk()
            ->assertSee('diye bir parti yok');
    }
}
