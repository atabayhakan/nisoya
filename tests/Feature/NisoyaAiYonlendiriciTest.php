<?php

namespace Tests\Feature;

use App\Contracts\AiProvider;
use App\Models\IslemTuru;
use App\Models\Temsilcilik;
use App\Models\TemsilcilikIslemi;
use App\Services\NisoyaAiYonlendirici;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\Support\SahteAiSaglayici;
use Tests\TestCase;

/**
 * Anasayfa "Nisoya AI ile ara" çubuğu — yönlendirme katmanı.
 * docs/plans/2026-08-19-…, madde C · docs/03-buyume-fikirleri.md KARAR: YAP.
 */
class NisoyaAiYonlendiriciTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([CurrencySeeder::class, CountrySeeder::class]);
        config(['ai.features.nisoya_ai_arama' => true]);
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

    private function yayindaAlmanya(): void
    {
        $tur = $this->islemTuru();
        TemsilcilikIslemi::query()->create([
            'temsilcilik_id' => $this->temsilcilik()->id,
            'islem_turu_id' => $tur->id,
            'evraklar' => [['ad' => 'T.C. kimlik kartı']],
            'resmi_kaynak_url' => 'https://www.konsolosluk.gov.tr',
            'status' => TemsilcilikIslemi::STATUS_YAYIN,
        ]);
    }

    private function sahteBagla(?array $yanit): SahteAiSaglayici
    {
        $sahte = new SahteAiSaglayici($yanit);
        $this->app->instance(AiProvider::class, $sahte);

        return $sahte;
    }

    public function test_ayar_kapaliyken_pasif(): void
    {
        config(['ai.features.nisoya_ai_arama' => false]);
        $this->sahteBagla(['niyet' => 'rehber', 'ulke_kodu' => null, 'islem_turu_slug' => null, 'anahtar_kelimeler' => []]);

        $this->assertFalse(app(NisoyaAiYonlendirici::class)->isEnabled());
    }

    public function test_iki_kelimeden_az_sorgu_tetiklenmez(): void
    {
        $this->sahteBagla(['niyet' => 'rehber', 'ulke_kodu' => null, 'islem_turu_slug' => null, 'anahtar_kelimeler' => []]);

        $this->assertFalse(app(NisoyaAiYonlendirici::class)->aranmaliMi('pasaport'));
        $this->assertTrue(app(NisoyaAiYonlendirici::class)->aranmaliMi('pasaportum kayboldu'));
    }

    public function test_rehber_niyeti_gercek_sonuca_yonlendirir(): void
    {
        $this->yayindaAlmanya();
        $this->sahteBagla([
            'niyet' => 'rehber', 'ulke_kodu' => 'DE', 'islem_turu_slug' => 'vekaletname', 'anahtar_kelimeler' => ['vekaletname'],
        ]);

        $sonuc = app(NisoyaAiYonlendirici::class)->ara('Almanyada vekaletname nasıl çıkarılır', null);

        $this->assertSame('rehber', $sonuc['niyet']);
        $this->assertCount(1, $sonuc['sonuclar']);
        $this->assertNull($sonuc['ilanBaglantisi']);
    }

    public function test_ilan_niyeti_yeniden_uretmez_sadece_baglanti_verir(): void
    {
        $this->sahteBagla([
            'niyet' => 'ilan', 'ulke_kodu' => null, 'islem_turu_slug' => null, 'anahtar_kelimeler' => ['temizlikçi', 'Berlin'],
        ]);

        $sonuc = app(NisoyaAiYonlendirici::class)->ara('Berlinde temizlikçi arıyorum', null);

        $this->assertSame('ilan', $sonuc['niyet']);
        $this->assertTrue($sonuc['sonuclar']->isEmpty());
        $this->assertStringContainsString('/ilanlar?q=', $sonuc['ilanBaglantisi']);
    }

    public function test_rehber_sonucu_bossa_ilan_baglantisina_duser(): void
    {
        // Hiç Rehber içeriği yok — sessiz başarısızlık YOK, her zaman bir sonraki adım.
        $this->sahteBagla([
            'niyet' => 'rehber', 'ulke_kodu' => null, 'islem_turu_slug' => null, 'anahtar_kelimeler' => ['bilinmeyen', 'konu'],
        ]);

        $sonuc = app(NisoyaAiYonlendirici::class)->ara('bilinmeyen bir konu sorusu', null);

        $this->assertSame('belirsiz', $sonuc['niyet']);
        $this->assertTrue($sonuc['sonuclar']->isEmpty());
        $this->assertNotNull($sonuc['ilanBaglantisi']);
    }

    /**
     * Gerçek olay (2026-08-20, canlıda ölçüldü): "merhaba naber nasılsın"
     * gibi alakasız bir selamlama, ziyaretçinin kendi ülkesindeki
     * temsilciliği "rehber" sonucu olarak döndürüyordu — AI doğru şekilde
     * "belirsiz" demişti ama varsayılan ülkeye düşme YALNIZ "rehber"
     * niyetinde uygulanmalıydı.
     */
    public function test_belirsiz_niyette_ipucu_yokken_varsayilan_ulkeye_dusulmez(): void
    {
        $this->yayindaAlmanya(); // ziyaretçinin "kendi ülkesi" (DE) hazır ve gerçek içerik taşıyor

        $this->sahteBagla([
            'niyet' => 'belirsiz', 'ulke_kodu' => null, 'islem_turu_slug' => null, 'anahtar_kelimeler' => ['selamlama', 'genel sohbet'],
        ]);

        // varsayilanUlkeKodu DOLU (DE) — ama soru resmî bir konu değil, bu yüzden kullanılmamalı.
        $sonuc = app(NisoyaAiYonlendirici::class)->ara('merhaba naber nasılsın', 'DE');

        $this->assertSame('belirsiz', $sonuc['niyet']);
        $this->assertTrue($sonuc['sonuclar']->isEmpty(), 'Alakasız bir selamlama, ziyaretçinin ülkesindeki temsilciliği döndürmemeli');
        $this->assertNotNull($sonuc['ilanBaglantisi']);
    }

    public function test_ai_basarisiz_olursa_kaba_kelimelere_duser_cokme(): void
    {
        $this->yayindaAlmanya();
        $this->sahteBagla(null); // sağlayıcı çöktü benzetimi

        $sonuc = app(NisoyaAiYonlendirici::class)->ara('vekaletname nasıl alınır Almanya', null);

        // Çökmemeli; kaba kelime ayıklamasıyla (>=4 harf) yine de bir sonuç dönebilir.
        $this->assertContains($sonuc['niyet'], ['rehber', 'belirsiz']);
    }

    public function test_gecersiz_niyet_degeri_belirsize_coker(): void
    {
        $this->sahteBagla(['niyet' => 'uydurma-niyet', 'ulke_kodu' => null, 'islem_turu_slug' => null, 'anahtar_kelimeler' => []]);

        $sonuc = app(NisoyaAiYonlendirici::class)->ara('bu ne anlama geliyor acaba', null);

        $this->assertContains($sonuc['niyet'], ['belirsiz', 'rehber']);
        $this->assertNotNull($sonuc['ilanBaglantisi']);
    }

    public function test_ulke_belirtilmeyince_varsayilan_ulkeye_duser(): void
    {
        $tur = $this->islemTuru();
        TemsilcilikIslemi::query()->create([
            'temsilcilik_id' => $this->temsilcilik([
                'country_code' => 'KG', 'ad' => 'Bişkek Büyükelçiliği', 'slug' => 'biskek', 'sehir' => 'Bişkek',
            ])->id,
            'islem_turu_id' => $tur->id,
            'evraklar' => [['ad' => 'T.C. kimlik kartı']],
            'resmi_kaynak_url' => 'https://www.konsolosluk.gov.tr',
            'status' => TemsilcilikIslemi::STATUS_YAYIN,
        ]);

        $this->sahteBagla([
            'niyet' => 'rehber', 'ulke_kodu' => null, 'islem_turu_slug' => 'vekaletname', 'anahtar_kelimeler' => ['vekaletname'],
        ]);

        $sonuc = app(NisoyaAiYonlendirici::class)->ara('vekaletname nasıl çıkarılır', 'KG');

        $this->assertSame('rehber', $sonuc['niyet']);
        $this->assertStringContainsString('Bişkek', $sonuc['sonuclar']->first()['altbaslik']);
    }

    /**
     * Gerçek olay (2026-08-20, canlıda ölçüldü): AI'nin ülke listesi
     * hazirUlkeler()'den (yalnız işlem içeriği olanlar) geliyordu — yeni
     * eklenen 55 iskelet ülke o listede hiç yoktu, bu yüzden model
     * "Avustralya'da büyükelçilik nerede" sorusundan ülke kodu
     * çıkaramıyordu. Artık kapsananUlkeler() kullanılıyor (bkz.
     * RehberYuzeyi), işlem içeriği olmasa bile temsilciliği olan her ülke
     * modele bildiriliyor.
     */
    public function test_istem_islem_icerigi_olmayan_ulkeyi_de_listeler(): void
    {
        $this->temsilcilik([
            'country_code' => 'AU', 'ad' => 'Canberra Büyükelçiliği', 'slug' => 'canberra', 'sehir' => 'Canberra',
        ]); // hiç işlem yok — tam da yeni iskelet ülkelerin durumu

        $sahte = $this->sahteBagla(['niyet' => 'rehber', 'ulke_kodu' => 'AU', 'islem_turu_slug' => null, 'anahtar_kelimeler' => []]);

        app(NisoyaAiYonlendirici::class)->ara('Avustralya\'da büyükelçilik nerede', null);

        $this->assertNotNull($sahte->sonYonerge);
        $this->assertStringContainsString('AU', $sahte->sonYonerge);
    }
}
