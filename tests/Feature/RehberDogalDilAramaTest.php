<?php

namespace Tests\Feature;

use App\Models\IslemTuru;
use App\Models\Temsilcilik;
use App\Models\TemsilcilikIslemi;
use App\Services\RehberDogalDilArama;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Nisoya AI arama — Rehber tarafı sorgu/doğrulama katmanı (AI çağırmaz,
 * bkz. RehberDogalDilArama docblock'u). docs/plans/2026-08-19-…, madde C.
 */
class RehberDogalDilAramaTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CurrencySeeder::class, CountrySeeder::class]);
    }

    private function temsilcilik(array $overrides = []): Temsilcilik
    {
        return Temsilcilik::query()->create(array_merge([
            'country_code' => 'DE',
            'ad' => 'Köln Başkonsolosluğu',
            'slug' => 'koeln',
            'tur' => Temsilcilik::TUR_BASKONSOLOSLUK,
            'sehir' => 'Köln',
            'is_active' => true,
        ], $overrides));
    }

    private function islemTuru(array $overrides = []): IslemTuru
    {
        return IslemTuru::query()->create(array_merge([
            'ad' => 'Vekaletname',
            'slug' => 'vekaletname',
            'is_active' => true,
        ], $overrides));
    }

    private function islem(Temsilcilik $temsilcilik, IslemTuru $tur, string $status = TemsilcilikIslemi::STATUS_YAYIN): TemsilcilikIslemi
    {
        return TemsilcilikIslemi::query()->create([
            'temsilcilik_id' => $temsilcilik->id,
            'islem_turu_id' => $tur->id,
            'evraklar' => [['ad' => 'T.C. kimlik kartı']],
            'resmi_kaynak_url' => 'https://www.konsolosluk.gov.tr',
            'status' => $status,
        ]);
    }

    public function test_gecerli_ulke_ve_islem_turuyle_dogrudan_bulur(): void
    {
        $this->islem($this->temsilcilik(), $this->islemTuru());

        $sonuc = app(RehberDogalDilArama::class)->ara('DE', 'vekaletname', []);

        $this->assertCount(1, $sonuc);
        $this->assertSame('Vekaletname', $sonuc->first()['baslik']);
        $this->assertStringContainsString('/de/koeln/vekaletname', $sonuc->first()['url']);
    }

    public function test_uydurulmus_ulke_kodu_sessizce_atilir(): void
    {
        $this->islem($this->temsilcilik(), $this->islemTuru());

        // AI'nin listede olmayan bir kod uydurduğu senaryo — çökmemeli,
        // sonuç aramasına düşmeli (anahtar kelime yoksa boş döner).
        $sonuc = app(RehberDogalDilArama::class)->ara('ZZ', 'vekaletname', []);

        $this->assertTrue($sonuc->isNotEmpty(), 'ülke kodu geçersizken işlem türü tek başına aramayı sürdürmeli');
    }

    public function test_uydurulmus_islem_turu_sessizce_atilir_anahtar_kelimeye_duser(): void
    {
        $this->islem($this->temsilcilik(), $this->islemTuru());

        $sonuc = app(RehberDogalDilArama::class)->ara('DE', 'uydurma-slug', ['vekaletname']);

        $this->assertCount(1, $sonuc, 'geçersiz işlem türü slug\'ı atılıp anahtar kelimeye düşmeli');
    }

    public function test_ulke_belirtilmezse_varsayilan_ulke_kullanilir(): void
    {
        $tur = $this->islemTuru();
        $this->islem($this->temsilcilik(), $tur); // DE
        $this->islem($this->temsilcilik([
            'country_code' => 'KG', 'ad' => 'Bişkek Büyükelçiliği', 'slug' => 'biskek', 'sehir' => 'Bişkek',
        ]), $tur);

        // Soru ülke içermiyor (ulke_kodu=null) ama ziyaretçinin kendi
        // ülkesi KG — F3'ün aynı kişiselleştirme ilkesi burada da geçerli.
        $sonuc = app(RehberDogalDilArama::class)->ara(null, 'vekaletname', [], 'KG');

        $this->assertCount(1, $sonuc);
        $this->assertStringContainsString('Bişkek', $sonuc->first()['altbaslik']);
    }

    public function test_taslak_islem_sonuca_sizmaz(): void
    {
        $this->islem($this->temsilcilik(), $this->islemTuru(), TemsilcilikIslemi::STATUS_TASLAK);

        $sonuc = app(RehberDogalDilArama::class)->ara('DE', 'vekaletname', []);

        // K7: taslak İŞLEM sonuç olarak dönmez — ama ülke bilindiği için
        // temsilcilik-seviyesi yedek devreye girer (bkz.
        // test_yayinda_islemi_olmayan_temsilcilik_yine_de_onerilir). Sızan
        // şey taslağın içeriği değil, zaten aktif/genel temsilcilik kaydı.
        $this->assertCount(1, $sonuc);
        $this->assertSame('Köln Başkonsolosluğu', $sonuc->first()['baslik']);
        $this->assertStringNotContainsString('/vekaletname', $sonuc->first()['url']);
    }

    public function test_hicbir_ipucu_ve_anahtar_kelime_yoksa_bos_doner(): void
    {
        $this->islem($this->temsilcilik(), $this->islemTuru());

        $sonuc = app(RehberDogalDilArama::class)->ara(null, null, []);

        $this->assertTrue($sonuc->isEmpty());
    }

    public function test_anahtar_kelime_temsilcilik_adinda_da_arar(): void
    {
        $this->islem($this->temsilcilik(), $this->islemTuru());

        $sonuc = app(RehberDogalDilArama::class)->ara(null, null, ['Köln']);

        $this->assertCount(1, $sonuc);
    }

    /**
     * Gerçek olay (2026-08-19, canlıda ölçüldü): "Kırgızistan elçilik nerede"
     * cevapsız kaldı. Kök neden — Bişkek'in hiç yayında İşlem kaydı yok,
     * yalnız yönlendirme notu var; üstteki üç arama da orada hep boş döner.
     * Bu test o boşluğu kilitliyor: hiç işlem kaydı olmasa bile ülke
     * biliniyorsa temsilciliğin kendisi önerilmeli.
     */
    public function test_yayinda_islemi_olmayan_temsilcilik_yine_de_onerilir(): void
    {
        $this->temsilcilik([
            'country_code' => 'KG', 'ad' => 'Bişkek Büyükelçiliği', 'slug' => 'biskek', 'sehir' => 'Bişkek',
            'yonlendirme_notu' => 'Bu temsilcilik işlem bilgisini kendi sitesinde yayınlamıyor.',
        ]);
        // KG'de hiç yayında TemsilcilikIslemi YOK — bilerek.

        $sonuc = app(RehberDogalDilArama::class)->ara('KG', null, ['elçilik']);

        $this->assertCount(1, $sonuc);
        $this->assertSame('Bişkek Büyükelçiliği', $sonuc->first()['baslik']);
        $this->assertStringContainsString('/kg/biskek', $sonuc->first()['url']);
    }

    public function test_hicbir_temsilcilik_de_yoksa_bos_doner(): void
    {
        // KG hiç seed edilmedi — ülke geçerli ama temsilcilik hiç yok.
        $sonuc = app(RehberDogalDilArama::class)->ara('KG', null, ['elçilik']);

        $this->assertTrue($sonuc->isEmpty());
    }

    public function test_pasif_temsilcilik_yedek_olarak_da_onerilmez(): void
    {
        $this->temsilcilik([
            'country_code' => 'KG', 'ad' => 'Bişkek Büyükelçiliği', 'slug' => 'biskek', 'sehir' => 'Bişkek',
            'is_active' => false,
        ]);

        $sonuc = app(RehberDogalDilArama::class)->ara('KG', null, []);

        $this->assertTrue($sonuc->isEmpty());
    }
}
