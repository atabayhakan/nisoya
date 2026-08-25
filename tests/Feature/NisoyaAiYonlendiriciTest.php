<?php

namespace Tests\Feature;

use App\Contracts\AiProvider;
use App\Models\IslemTuru;
use App\Models\SssSorusu;
use App\Models\Temsilcilik;
use App\Models\TemsilcilikIslemi;
use App\Models\YasamKategorisi;
use App\Models\YasamKonuIcerigi;
use App\Models\YasamKonusu;
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

    private function yasamKategori(array $overrides = []): YasamKategorisi
    {
        return YasamKategorisi::query()->create(array_merge([
            'ad' => 'Bankacılık & Finans',
            'slug' => 'bankacilik-finans',
            'ikon' => '🏦',
            'is_active' => true,
            'sort_order' => 1,
        ], $overrides));
    }

    private function yasamKonu(YasamKategorisi $kategori, array $overrides = []): YasamKonusu
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

    private function yasamYayindaAlmanya(): void
    {
        YasamKonuIcerigi::query()->create([
            'yasam_konusu_id' => $this->yasamKonu($this->yasamKategori())->id,
            'country_code' => 'DE',
            'icerik' => [['tip' => 'paragraf', 'metin' => 'Test içerik paragrafı.']],
            'kaynak_url' => 'https://example.com/kaynak',
            'kaynak_aciklama' => 'Resmî kaynak',
            'dogrulanma_tarihi' => now()->subDays(5),
            'status' => YasamKonuIcerigi::STATUS_YAYIN,
            'yazan_tur' => YasamKonuIcerigi::YAZAN_AI,
        ]);
    }

    private function sssSorusu(array $overrides = []): SssSorusu
    {
        return SssSorusu::query()->create(array_merge([
            'soru' => 'Nisoya ücretli mi?',
            'cevap' => 'Hayır, kayıt olmak ve ilan vermek tamamen ücretsiz.',
            'is_active' => true,
            'sort_order' => 1,
        ], $overrides));
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

    public function test_yasam_niyeti_gercek_sonuca_yonlendirir(): void
    {
        $this->yasamYayindaAlmanya();
        $this->sahteBagla([
            'niyet' => 'yasam', 'ulke_kodu' => 'DE', 'yasam_kategori_slug' => 'bankacilik-finans',
            'anahtar_kelimeler' => ['banka', 'hesap'],
        ]);

        $sonuc = app(NisoyaAiYonlendirici::class)->ara('Almanyada SSNsiz banka hesabı nasıl açılır', null);

        $this->assertSame('yasam', $sonuc['niyet']);
        $this->assertCount(1, $sonuc['sonuclar']);
        $this->assertNull($sonuc['ilanBaglantisi']);
    }

    public function test_is_niyeti_yeniden_uretmez_sadece_isler_baglantisi_verir(): void
    {
        $this->sahteBagla([
            'niyet' => 'is', 'ulke_kodu' => null, 'anahtar_kelimeler' => ['satış', 'temsilcisi'],
        ]);

        $sonuc = app(NisoyaAiYonlendirici::class)->ara('İstanbulda satış temsilcisi iş arıyorum', null);

        $this->assertSame('is', $sonuc['niyet']);
        $this->assertTrue($sonuc['sonuclar']->isEmpty());
        $this->assertStringContainsString('/isler?', $sonuc['ilanBaglantisi']);
        $this->assertStringContainsString('q=', $sonuc['ilanBaglantisi']);
    }

    public function test_is_baglantisina_gecerli_ulke_kodu_eklenir(): void
    {
        $this->sahteBagla([
            'niyet' => 'is', 'ulke_kodu' => 'DE', 'anahtar_kelimeler' => ['yazılımcı'],
        ]);

        $sonuc = app(NisoyaAiYonlendirici::class)->ara('Almanyada uzaktan yazılımcı iş ilanı', null);

        $this->assertStringContainsString('ulke=DE', $sonuc['ilanBaglantisi']);
    }

    public function test_is_baglantisina_uydurulmus_ulke_kodu_eklenmez(): void
    {
        $this->sahteBagla([
            'niyet' => 'is', 'ulke_kodu' => 'ZZ', 'anahtar_kelimeler' => ['yazılımcı'],
        ]);

        $sonuc = app(NisoyaAiYonlendirici::class)->ara('ZZ ülkesinde yazılımcı iş ilanı', null);

        $this->assertStringNotContainsString('ulke=', $sonuc['ilanBaglantisi']);
    }

    /** AI 'rehber' dese bile, o motor boşsa Yaşam Rehberi güvenlik ağı devreye girer. */
    public function test_rehber_bossa_yasam_guvenlik_agina_duser(): void
    {
        $this->yasamYayindaAlmanya(); // Rehber içeriği YOK, yalnız Yaşam Rehberi var

        $this->sahteBagla([
            'niyet' => 'rehber', 'ulke_kodu' => 'DE', 'islem_turu_slug' => null,
            'yasam_kategori_slug' => 'bankacilik-finans', 'anahtar_kelimeler' => ['banka'],
        ]);

        $sonuc = app(NisoyaAiYonlendirici::class)->ara('Almanyada banka hesabı nasıl açılır', null);

        $this->assertSame('yasam', $sonuc['niyet']);
        $this->assertCount(1, $sonuc['sonuclar']);
    }

    /** Simetrik: AI 'yasam' dese bile, o motor boşsa Rehber güvenlik ağı devreye girer. */
    public function test_yasam_bossa_rehber_guvenlik_agina_duser(): void
    {
        $this->yayindaAlmanya(); // Yaşam Rehberi içeriği YOK, yalnız Rehber var

        $this->sahteBagla([
            'niyet' => 'yasam', 'ulke_kodu' => 'DE', 'islem_turu_slug' => 'vekaletname',
            'yasam_kategori_slug' => null, 'anahtar_kelimeler' => ['vekaletname'],
        ]);

        $sonuc = app(NisoyaAiYonlendirici::class)->ara('Almanyada vekaletname', null);

        $this->assertSame('rehber', $sonuc['niyet']);
        $this->assertCount(1, $sonuc['sonuclar']);
    }

    public function test_sss_niyeti_gercek_sonuca_yonlendirir(): void
    {
        $this->sssSorusu();
        $this->sahteBagla([
            'niyet' => 'sss', 'ulke_kodu' => null, 'anahtar_kelimeler' => ['ücretli'],
        ]);

        $sonuc = app(NisoyaAiYonlendirici::class)->ara('Nisoya ücretli mi acaba', null);

        $this->assertSame('sss', $sonuc['niyet']);
        $this->assertCount(1, $sonuc['sonuclar']);
        $this->assertNull($sonuc['ilanBaglantisi']);
    }

    /**
     * 'sss' kendi başına bir dal — boşsa rehber/yaşam'a SIÇRAMAZ (örtüşme
     * yok), doğrudan belirsize düşer. Bunu kanıtlamak için rehber içeriği
     * BİLEREK de hazır — sıçrasaydı 'rehber' dönerdi.
     */
    public function test_sss_bossa_rehber_yasama_sicramaz_belirsize_duser(): void
    {
        $this->yayindaAlmanya();

        $this->sahteBagla([
            'niyet' => 'sss', 'ulke_kodu' => null, 'anahtar_kelimeler' => ['alakasız', 'kelimeler'],
        ]);

        $sonuc = app(NisoyaAiYonlendirici::class)->ara('bu ne anlama geliyor', null);

        $this->assertSame('belirsiz', $sonuc['niyet']);
        $this->assertTrue($sonuc['sonuclar']->isEmpty());
        $this->assertNotNull($sonuc['ilanBaglantisi']);
    }

    /** Rehber VE Yaşam Rehberi ikisi de boşsa SSS son güvenlik ağı olarak denenir. */
    public function test_rehber_ve_yasam_bossa_sss_son_guvenlik_agina_duser(): void
    {
        $this->sssSorusu();

        $this->sahteBagla([
            'niyet' => 'rehber', 'ulke_kodu' => null, 'islem_turu_slug' => null,
            'yasam_kategori_slug' => null, 'anahtar_kelimeler' => ['ücretli'],
        ]);

        $sonuc = app(NisoyaAiYonlendirici::class)->ara('Nisoya ücretli mi', null);

        $this->assertSame('sss', $sonuc['niyet']);
        $this->assertCount(1, $sonuc['sonuclar']);
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
