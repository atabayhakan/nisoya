<?php

namespace Tests\Feature;

use App\Mcp\Araclar\EksikAlanlar;
use App\Mcp\Araclar\HataKayitlari;
use App\Mcp\Araclar\KahyaAraci;
use App\Mcp\Araclar\MedyaDogrula;
use App\Mcp\Araclar\Nabiz;
use App\Mcp\Araclar\SistemSagligi;
use App\Mcp\Araclar\SonRapor;
use App\Mcp\Araclar\TamTeshis;
use App\Mcp\Sunucular\KahyaSunucusu;
use App\Models\KahyaCalismasi;
use App\Support\Settings;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use ReflectionProperty;
use Tests\Support\DenemeKahyaSunucusu;
use Tests\Support\YazmayaCalisanArac;
use Tests\TestCase;

/**
 * Kâhya MCP sunucusu (Faz E).
 *
 * BU TESTLER MCP KATMANINI SINAR, servisleri değil — teşhis servislerinin
 * kendi testleri `KahyaTeshisServisleriTest` içinde. Burada sorulan sorular:
 * araçlar salt-okunur mu, sır sızdırıyor mu, parametreler gerçekten
 * kırpılıyor mu, ve yeni bir araç tabanı atlayabilir mi.
 */
class KahyaMcpTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CurrencySeeder::class, CountrySeeder::class, CategorySeeder::class]);
    }

    /** @return array<int, class-string> */
    private function kayitliAraclar(): array
    {
        $ozellik = new ReflectionProperty(KahyaSunucusu::class, 'tools');

        return $ozellik->getDefaultValue();
    }

    // ------------------------------------------------------------- Bekçiler

    /**
     * ASIL BEKÇİ: her araç `KahyaAraci` tabanından türemeli.
     *
     * Taban atlanırsa üç garanti birden düşer — salt-okunurluk, istisna
     * mesajının sızmaması ve yapılandırılmış çıktı — ve hiçbiri gürültü
     * çıkarmadan düşer. Bu test, yeni bir araç ekleyen kişiyi tabanı
     * kullanmaya zorlar.
     */
    public function test_her_arac_kahya_araci_tabanindan_turer(): void
    {
        $araclar = $this->kayitliAraclar();

        $this->assertNotEmpty($araclar);

        foreach ($araclar as $sinif) {
            $this->assertTrue(
                is_subclass_of($sinif, KahyaAraci::class),
                "{$sinif} KahyaAraci tabanından türemiyor — salt-okunurluk garantisi düşer."
            );
        }
    }

    /**
     * Her araç protokolde `readOnlyHint: true` İLAN ETMELİ.
     *
     * Bu ilan istemcinin ve modelin gördüğü tek "bu araç zararsız" sinyalidir.
     * İlk yazımda `#[IsReadOnly]` ortak tabana konmuştu ve tele HİÇ ÇIKMIYORDU:
     * PHP sınıf attribute'larını miras aldırmaz, paket de yalnız somut sınıfa
     * bakar. Beyan koddaydı, protokolde `annotations: []` görünüyordu.
     */
    public function test_her_arac_salt_okunur_ilan_eder(): void
    {
        foreach ($this->kayitliAraclar() as $sinif) {
            $arac = app($sinif);

            $this->assertSame(
                ['readOnlyHint' => true],
                $arac->annotations(),
                "{$sinif} protokolde salt-okunur olduğunu ilan etmiyor — #[IsReadOnly] eksik."
            );
        }
    }

    /**
     * SALT-OKUNURLUK, MCP YOLUNUN TAMAMINDA. Bekçinin kendi testleri
     * servisi doğrudan çağırıyor; burada asıl soru şu: araç MCP üzerinden
     * çağrıldığında da kip gerçekten açık mı?
     *
     * HATANIN METNİ DE DOĞRULANIYOR: yalnız `assertHasErrors()` yazılsaydı
     * test yanlış sebepten yeşil yanardı — kayıtlı olmayan bir araç da hata
     * döndürür ("Tool not found") ve bekçi hiç devreye girmemiş olurdu.
     * (Bu ilk yazımda gerçekten oldu.)
     */
    public function test_yazmaya_calisan_arac_engellenir_ve_veri_degismez(): void
    {
        $once = KahyaCalismasi::query()->count();

        DenemeKahyaSunucusu::tool(YazmayaCalisanArac::class)
            ->assertHasErrors()
            ->assertSee('salt-okunur kipte')
            ->assertSee('INSERT');

        $this->assertSame($once, KahyaCalismasi::query()->count(), 'Araç veri yazmış olmamalı.');
    }

    /**
     * SIR SIZINTISI. `site_settings` tablosunda SMTP parolası ve API
     * anahtarları duruyor; bir aracın ayar DEĞERİ döndürmesi sırrı yapay
     * zekâya taşımak demektir. Bütün araçlar tek tek süzülüyor.
     */
    public function test_hicbir_arac_sir_degeri_dondurmez(): void
    {
        Settings::setMany([
            'mail.password' => 'SIR-SMTP-PAROLASI-9x1',
            'mail.host' => 'SIR-SMTP-SUNUCUSU-9x2',
            'ai.api_anahtari' => 'SIR-AI-ANAHTARI-9x3',
            'growth.google_places_api_key' => 'SIR-PLACES-ANAHTARI-9x4',
        ]);

        $sirlar = ['SIR-SMTP-PAROLASI-9x1', 'SIR-SMTP-SUNUCUSU-9x2', 'SIR-AI-ANAHTARI-9x3', 'SIR-PLACES-ANAHTARI-9x4'];

        foreach ($this->kayitliAraclar() as $sinif) {
            KahyaSunucusu::tool($sinif)
                ->assertOk()
                ->assertDontSee($sirlar);
        }
    }

    // ------------------------------------------------------------- Nabız

    public function test_nabiz_calisir_ve_bos_pazaryerini_uyarir(): void
    {
        KahyaSunucusu::tool(Nabiz::class)
            ->assertOk()
            ->assertName('kahya-nabiz')
            ->assertSee('Pazaryeri boş')
            ->assertSee('envanter');
    }

    /**
     * `null` (hiç koşmadı) ile `0` (az önce koştu) farkı MCP yüzeyinde de
     * korunmalı — ikincisi sağlık, birincisi arıza.
     */
    public function test_nabiz_kahya_hic_kosmadiysa_bunu_soyler(): void
    {
        KahyaSunucusu::tool(Nabiz::class)
            ->assertOk()
            ->assertSee('HİÇ ÇALIŞMADI');
    }

    public function test_nabiz_taze_kosuyu_duzenli_sayar(): void
    {
        KahyaCalismasi::create(['tur' => 'gunluk_rapor', 'gonderildi' => true]);

        KahyaSunucusu::tool(Nabiz::class)
            ->assertOk()
            ->assertSee('düzenli')
            ->assertDontSee('HİÇ ÇALIŞMADI');
    }

    // ------------------------------------------------------------- Son rapor

    public function test_son_rapor_hic_kayit_yokken_sebebini_acikliyor(): void
    {
        KahyaSunucusu::tool(SonRapor::class)
            ->assertOk()
            ->assertSee('HİÇ çalışmamış')
            ->assertSee('sessizce ölür');
    }

    public function test_son_rapor_deftereki_ozeti_yeniden_hesaplamadan_dondurur(): void
    {
        KahyaCalismasi::create([
            'tur' => 'gunluk_rapor',
            'gonderildi' => true,
            'alici' => 'yonetici@ornek.com',
            'sure_ms' => 1234,
            'ozet' => ['teshis' => ['envanter' => ['ilan' => 42, 'satici' => 7, 'uyari' => null]]],
        ]);

        KahyaSunucusu::tool(SonRapor::class)
            ->assertOk()
            // Defterdeki sayı olduğu gibi dönmeli: sahibin gelen kutusunda
            // gördüğü rakamla burada söylenen rakam aynı olmalı.
            ->assertSee('42')
            ->assertSee('yonetici@ornek.com');
    }

    public function test_son_rapor_geriye_bakabilir(): void
    {
        $eski = KahyaCalismasi::create(['tur' => 'gunluk_rapor', 'gonderildi' => true, 'alici' => 'eski@ornek.com']);
        $eski->forceFill(['created_at' => now()->subDays(2)])->saveQuietly();

        KahyaCalismasi::create(['tur' => 'gunluk_rapor', 'gonderildi' => true, 'alici' => 'yeni@ornek.com']);

        KahyaSunucusu::tool(SonRapor::class, ['sira' => 1])
            ->assertOk()
            ->assertSee('eski@ornek.com')
            ->assertDontSee('yeni@ornek.com');
    }

    // ------------------------------------------------- Parametre kırpma

    /**
     * ŞEMAYA GÜVENİLMEZ. JSON Schema istemci tarafında bir öneridir; sunucuya
     * ne gelirse gelsin sınırı kod uygular. Aksi hâlde tek bir çağrı
     * `medya_limit: 999999` ile üretimde on binlerce dosya kontrolü başlatır.
     */
    public function test_medya_limiti_kirpilir(): void
    {
        KahyaSunucusu::tool(MedyaDogrula::class, ['limit' => 999999])
            ->assertOk()
            ->assertSee('"limit":500');

        KahyaSunucusu::tool(MedyaDogrula::class, ['limit' => -5])
            ->assertOk()
            ->assertSee('"limit":1');
    }

    public function test_log_penceresi_kirpilir(): void
    {
        KahyaSunucusu::tool(HataKayitlari::class, ['saat' => 100000])
            ->assertOk()
            ->assertSee('"pencere_saat":168');
    }

    public function test_tam_teshis_parametreleri_kirpar(): void
    {
        KahyaSunucusu::tool(TamTeshis::class, ['medya_limit' => 999999, 'log_saat' => 999999])
            ->assertOk()
            ->assertSee('"medya_limit":500')
            ->assertSee('"log_saat":168');
    }

    // ------------------------------------------------------------- Gizlilik

    /**
     * Log aracı yalnız İMZA taşır. Bu testin varlığı, ileride biri
     * "hata mesajını da ekleyelim, faydalı olur" dediğinde kırmızı yanmasını
     * sağlar — nedeni `LogOzeti` docblock'unda yazılı.
     */
    public function test_hata_kayitlari_imza_alanlarini_dondurur(): void
    {
        KahyaSunucusu::tool(HataKayitlari::class)
            ->assertOk()
            ->assertSee(['taranan_dosya', 'imzalar', 'toplam']);
    }

    public function test_eksik_alanlar_yalniz_anahtar_ve_neden_dondurur(): void
    {
        KahyaSunucusu::tool(EksikAlanlar::class)
            ->assertOk()
            ->assertSee(['kritik', 'istege_bagli_sayi', 'ilansiz_kategori']);
    }

    // ------------------------------------------------------- Sistem sağlığı

    public function test_sistem_sagligi_yedeksiz_durumu_uyarir(): void
    {
        KahyaSunucusu::tool(SistemSagligi::class)
            ->assertOk()
            ->assertSee('HİÇ YEDEK YOK')
            ->assertSee('kuyruk');
    }

    /**
     * Üretimde açık kalmış `APP_DEBUG` gerçek bir güvenlik açığıdır: hata
     * sayfası yığın izini, dosya yollarını ve ortam değişkenlerini ziyaretçiye
     * gösterir. Hiçbir ekranda uyarı çıkmaz.
     */
    public function test_sistem_sagligi_uretimde_acik_hata_ayiklamayi_yakalar(): void
    {
        $this->app->detectEnvironment(fn (): string => 'production');
        config(['app.debug' => true]);

        KahyaSunucusu::tool(SistemSagligi::class)
            ->assertOk()
            ->assertSee('ÜRETİMDE APP_DEBUG AÇIK');
    }

    public function test_sistem_sagligi_yerelde_hata_ayiklamayi_uyari_saymaz(): void
    {
        config(['app.debug' => true]);

        KahyaSunucusu::tool(SistemSagligi::class)
            ->assertOk()
            ->assertDontSee('ÜRETİMDE APP_DEBUG AÇIK');
    }
}
