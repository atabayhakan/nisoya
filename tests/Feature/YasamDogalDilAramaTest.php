<?php

namespace Tests\Feature;

use App\Models\YasamKategorisi;
use App\Models\YasamKonuIcerigi;
use App\Models\YasamKonusu;
use App\Services\YasamDogalDilArama;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Nisoya AI arama — Yaşam Rehberi tarafı sorgu/doğrulama katmanı (AI
 * çağırmaz, bkz. YasamDogalDilArama docblock'u). RehberDogalDilArama'nın
 * aynadaşı, aynı test deseni. docs/plans/2026-08-25-…
 */
class YasamDogalDilAramaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CurrencySeeder::class, CountrySeeder::class]);
    }

    private function kategori(array $overrides = []): YasamKategorisi
    {
        return YasamKategorisi::query()->create(array_merge([
            'ad' => 'Bankacılık & Finans',
            'slug' => 'bankacilik-finans',
            'ikon' => '🏦',
            'is_active' => true,
            'sort_order' => 1,
        ], $overrides));
    }

    private function konu(YasamKategorisi $kategori, array $overrides = []): YasamKonusu
    {
        return YasamKonusu::query()->create(array_merge([
            'kategori_id' => $kategori->id,
            'baslik' => "SSN'siz banka hesabı açma",
            'slug' => 'ssnsiz-hesap-acma',
            'kisa_aciklama' => 'Sosyal güvenlik numarası olmadan hesap açmak mümkün mü?',
            'is_active' => true,
            'sort_order' => 1,
        ], $overrides));
    }

    private function icerik(YasamKonusu $konu, string $ulke, string $status = YasamKonuIcerigi::STATUS_YAYIN): YasamKonuIcerigi
    {
        return YasamKonuIcerigi::query()->create([
            'yasam_konusu_id' => $konu->id,
            'country_code' => $ulke,
            'icerik' => [['tip' => 'paragraf', 'metin' => 'Test içerik paragrafı.']],
            'kaynak_url' => 'https://example.com/kaynak',
            'kaynak_aciklama' => 'Resmî kaynak',
            'dogrulanma_tarihi' => now()->subDays(5),
            'status' => $status,
            'yazan_tur' => YasamKonuIcerigi::YAZAN_AI,
        ]);
    }

    public function test_gecerli_ulke_ve_kategoriyle_dogrudan_bulur(): void
    {
        $this->icerik($this->konu($this->kategori()), 'DE');

        $sonuc = app(YasamDogalDilArama::class)->ara('DE', 'bankacilik-finans', []);

        $this->assertCount(1, $sonuc);
        $this->assertSame("SSN'siz banka hesabı açma", $sonuc->first()['baslik']);
        $this->assertStringContainsString('/de/yasam/bankacilik-finans/ssnsiz-hesap-acma', $sonuc->first()['url']);
    }

    public function test_uydurulmus_ulke_kodu_sessizce_atilir(): void
    {
        $this->icerik($this->konu($this->kategori()), 'DE');

        // AI'nin listede olmayan bir kod uydurduğu senaryo — çökmemeli,
        // kategori tek başına aramayı sürdürmeli.
        $sonuc = app(YasamDogalDilArama::class)->ara('ZZ', 'bankacilik-finans', []);

        $this->assertTrue($sonuc->isNotEmpty(), 'ülke kodu geçersizken kategori tek başına aramayı sürdürmeli');
    }

    public function test_uydurulmus_kategori_slug_sessizce_atilir_anahtar_kelimeye_duser(): void
    {
        $this->icerik($this->konu($this->kategori()), 'DE');

        $sonuc = app(YasamDogalDilArama::class)->ara('DE', 'uydurma-slug', ['SSN']);

        $this->assertCount(1, $sonuc, 'geçersiz kategori slug\'ı atılıp anahtar kelimeye düşmeli');
    }

    public function test_ulke_belirtilmezse_varsayilan_ulke_kullanilir(): void
    {
        $kategori = $this->kategori();
        $this->icerik($this->konu($kategori), 'DE');
        $this->icerik($this->konu($kategori, ['slug' => 'ssnsiz-hesap-acma-nl']), 'NL');

        // Soru ülke içermiyor (ulke_kodu=null) ama ziyaretçinin kendi
        // ülkesi NL — Rehber'deki aynı kişiselleştirme ilkesi burada da geçerli.
        $sonuc = app(YasamDogalDilArama::class)->ara(null, 'bankacilik-finans', [], 'NL');

        $this->assertCount(1, $sonuc);
        $this->assertStringContainsString('/nl/yasam/', $sonuc->first()['url']);
    }

    public function test_taslak_icerik_sonuca_sizmaz(): void
    {
        $this->icerik($this->konu($this->kategori()), 'DE', YasamKonuIcerigi::STATUS_TASLAK);

        $sonuc = app(YasamDogalDilArama::class)->ara('DE', 'bankacilik-finans', []);

        // K7: taslak içerik sonuç olarak dönmez — ama ülke+kategori bilindiği
        // için kategori düzeyi yedek de burada boş döner (hiç yayında
        // kategori yok), tamamen boş sonuç doğru davranış.
        $this->assertTrue($sonuc->isEmpty());
    }

    public function test_hicbir_ipucu_ve_anahtar_kelime_yoksa_bos_doner(): void
    {
        $this->icerik($this->konu($this->kategori()), 'DE');

        $sonuc = app(YasamDogalDilArama::class)->ara(null, null, []);

        $this->assertTrue($sonuc->isEmpty());
    }

    public function test_anahtar_kelime_konu_basliginda_da_arar(): void
    {
        $this->icerik($this->konu($this->kategori()), 'DE');

        $sonuc = app(YasamDogalDilArama::class)->ara(null, null, ['SSN']);

        $this->assertCount(1, $sonuc);
    }

    public function test_anahtar_kelime_kisa_aciklamada_da_arar(): void
    {
        $this->icerik($this->konu($this->kategori()), 'DE');

        $sonuc = app(YasamDogalDilArama::class)->ara(null, null, ['sosyal güvenlik']);

        $this->assertCount(1, $sonuc);
    }

    /**
     * Rehber'deki "temsilciliğin kendisini öner" karşılığı: konu düzeyinde
     * hiçbir eşleşme yoksa ama ülke biliniyorsa, o ülkenin yayında içeriği
     * olan TÜM kategorileri önerilir — sessiz kalınmaz.
     */
    public function test_konu_eslesmezse_ulkenin_kategorileri_onerilir(): void
    {
        $this->icerik($this->konu($this->kategori()), 'DE');

        $sonuc = app(YasamDogalDilArama::class)->ara('DE', null, ['alakasız', 'kelimeler']);

        $this->assertCount(1, $sonuc);
        $this->assertSame('Bankacılık & Finans', $sonuc->first()['baslik']);
        $this->assertStringContainsString('/de/yasam/bankacilik-finans', $sonuc->first()['url']);
        $this->assertStringNotContainsString('/ssnsiz-hesap-acma', $sonuc->first()['url']);
    }

    public function test_hicbir_icerik_de_yoksa_bos_doner(): void
    {
        // NL için hiç Yaşam Rehberi içeriği seed edilmedi.
        $sonuc = app(YasamDogalDilArama::class)->ara('NL', null, ['banka']);

        $this->assertTrue($sonuc->isEmpty());
    }

    public function test_pasif_kategori_yedek_olarak_da_onerilmez(): void
    {
        $kategori = $this->kategori(['is_active' => false]);
        $this->icerik($this->konu($kategori), 'DE');

        $sonuc = app(YasamDogalDilArama::class)->ara('DE', null, []);

        $this->assertTrue($sonuc->isEmpty());
    }

    public function test_pasif_konu_sonuca_sizmaz(): void
    {
        $this->icerik($this->konu($this->kategori(), ['is_active' => false]), 'DE');

        $sonuc = app(YasamDogalDilArama::class)->ara('DE', 'bankacilik-finans', []);

        $this->assertTrue($sonuc->isEmpty());
    }
}
