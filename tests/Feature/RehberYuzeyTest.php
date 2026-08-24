<?php

namespace Tests\Feature;

use App\Models\IslemTuru;
use App\Models\Temsilcilik;
use App\Models\TemsilcilikIslemi;
use App\Models\User;
use App\Models\YasamKategorisi;
use App\Models\YasamKonuIcerigi;
use App\Models\YasamKonusu;
use App\Services\RehberYuzeyi;
use App\Support\Settings;
use Database\Seeders\CategorySeeder;
use Database\Seeders\CountrySeeder;
use Database\Seeders\CurrencySeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Rehber F2 — ana sayfa bölümü + footer bağlantısı + Cmd+K araması.
 *
 * Sınanan sözleşmeler: bölüm ancak YAYINDA içerik varken var olur (boş rehber
 * vaadi ana sayfaya çıkmaz), ülke önceliği K1'dir (üye ikameti önce; hazır
 * olmayan ülkeye içerik dayatılmaz), modül kapısı her yüzeyde geçerlidir ve
 * taslak içerik aramaya sızmaz (K7'nin arama yüzüne izdüşümü).
 */
class RehberYuzeyTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Settings::forget();
        $this->seed([CurrencySeeder::class, CountrySeeder::class, CategorySeeder::class]);
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

    private function yayindaAlmanya(): void
    {
        $this->islem($this->temsilcilik(), $this->islemTuru());
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

    private function yasamYayindaIcerik(YasamKonusu $konu, string $ulke): YasamKonuIcerigi
    {
        return YasamKonuIcerigi::query()->create([
            'yasam_konusu_id' => $konu->id,
            'country_code' => $ulke,
            'icerik' => [['tip' => 'paragraf', 'metin' => 'Test içerik paragrafı.']],
            'kaynak_url' => 'https://example.com/kaynak',
            'kaynak_aciklama' => 'Resmî kaynak',
            'dogrulanma_tarihi' => now()->subDays(5),
            'status' => YasamKonuIcerigi::STATUS_YAYIN,
            'yazan_tur' => YasamKonuIcerigi::YAZAN_AI,
        ]);
    }

    // ----------------------------------------------------- Ana sayfa bölümü

    public function test_yayinda_icerik_yokken_bolum_gorunmez(): void
    {
        // Taslak içerik var ama yayında değil — bölüm yokmuş gibi davranır.
        $this->islem($this->temsilcilik(), $this->islemTuru(), TemsilcilikIslemi::STATUS_TASLAK);

        $this->get('/')->assertOk()->assertDontSee('Ülke Rehberi');
    }

    public function test_yayinda_icerik_varken_bolum_gorunur(): void
    {
        $this->yayindaAlmanya();

        // Misafir + testte GeoIP kapalı → ülke çözülmez, jenerik başlık +
        // hazır ülke pili görünür.
        $this->get('/')
            ->assertOk()
            ->assertSee('Ülke Rehberi')
            ->assertSee('Konsolosluk işlemleri rehberi')
            ->assertSee('Almanya');
    }

    public function test_modul_kapaliyken_bolum_ve_footer_yok(): void
    {
        $this->yayindaAlmanya();
        Settings::setMany(['modul.rehber' => '0']);

        $this->get('/')->assertOk()->assertDontSee('Ülke Rehberi');
    }

    public function test_bolum_panelden_gizlenebilir(): void
    {
        $this->yayindaAlmanya();
        Settings::setMany(['home.section.rehber' => '0']);

        $this->get('/')->assertOk()->assertDontSee('Konsolosluk işlemleri rehberi');
    }

    public function test_uye_ikamet_ulkesini_secili_gorur(): void
    {
        $this->yayindaAlmanya();

        $uye = User::factory()->create(['country_code' => 'DE', 'email_verified_at' => now()]);

        // K1: üye ikameti önce — Almanya seçili gelir, işlem çipi görünür.
        $this->actingAs($uye)->get('/')
            ->assertOk()
            ->assertSee('Almanya için konsolosluk rehberi')
            ->assertSee('Vekaletname');
    }

    public function test_ikameti_hazir_olmayan_uye_dayatma_gormez(): void
    {
        $this->yayindaAlmanya();

        $uye = User::factory()->create(['country_code' => 'US', 'email_verified_at' => now()]);

        // K1: hazır olmayan ülkeye Almanya içeriği DAYATILMAZ — bölüm
        // "hazırlanıyor" der ve hazır ülkeleri önerir.
        $this->actingAs($uye)->get('/')
            ->assertOk()
            ->assertSee('Yaşadığın ülkenin rehberi hazırlanıyor')
            ->assertSee('Almanya')
            ->assertDontSee('Almanya için konsolosluk rehberi');
    }

    public function test_vitrin_temasinda_da_bolum_calisir(): void
    {
        $this->yayindaAlmanya();
        Settings::setMany(['gorunum.tema' => 'vitrin']);

        $this->get('/')
            ->assertOk()
            ->assertSee('Ülke Rehberi')
            ->assertSee('Almanya');
    }

    // ------------------------------------------- Yaşam Rehberi köprüsü (F2)

    /** Ülke Rehberi zaten hazırsa Yaşam Rehberi İKİNCİL blok olarak eklenir. */
    public function test_yasam_rehberi_ulke_rehberi_varken_ikincil_blok_olarak_gorunur(): void
    {
        $this->yayindaAlmanya();
        $this->yasamYayindaIcerik($this->yasamKonu($this->yasamKategori()), 'DE');

        $uye = User::factory()->create(['country_code' => 'DE', 'email_verified_at' => now()]);

        $this->actingAs($uye)->get('/')
            ->assertOk()
            ->assertSeeInOrder(['Ülke Rehberi', 'Almanya için konsolosluk rehberi', 'Yaşam Rehberi', 'Bankacılık & Finans']);
    }

    /** Ülke Rehberi'nin kapsamadığı ülkede Yaşam Rehberi BİRİNCİL olur. */
    public function test_yasam_rehberi_ulke_rehberinin_kapsamadigi_ulkede_birincil_olur(): void
    {
        $this->yasamYayindaIcerik($this->yasamKonu($this->yasamKategori()), 'NL');

        $uye = User::factory()->create(['country_code' => 'NL', 'email_verified_at' => now()]);

        $this->actingAs($uye)->get('/')
            ->assertOk()
            ->assertDontSee('Ülke Rehberi')
            ->assertSee('Hollanda için yaşam rehberi')
            ->assertSee('Bankacılık & Finans');
    }

    /** Misafir + çözülemeyen ülke → jenerik başlık, dayatma yok (K1 ile aynı desen). */
    public function test_yasam_rehberi_misafir_icin_jenerik_baslikla_birincil_gorunur(): void
    {
        $this->yasamYayindaIcerik($this->yasamKonu($this->yasamKategori()), 'NL');

        $this->get('/')
            ->assertOk()
            ->assertDontSee('Ülke Rehberi')
            ->assertSee('Gündelik hayat rehberi')
            ->assertSee('Hollanda');
    }

    public function test_vitrin_temasinda_yasam_rehberi_ikincil_blok_gorunur(): void
    {
        $this->yayindaAlmanya();
        $this->yasamYayindaIcerik($this->yasamKonu($this->yasamKategori()), 'DE');
        Settings::setMany(['gorunum.tema' => 'vitrin']);

        $uye = User::factory()->create(['country_code' => 'DE', 'email_verified_at' => now()]);

        $this->actingAs($uye)->get('/')
            ->assertOk()
            ->assertSeeInOrder(['Ülke Rehberi', 'Almanya için konsolosluk rehberi', 'Yaşam Rehberi', 'Bankacılık & Finans']);
    }

    // ---------------------------------------------------------------- Footer

    public function test_footer_baglantisi_yayinda_icerikle_belirir(): void
    {
        // İçerik yokken link yok…
        $this->get('/nasil-calisir')->assertOk()->assertDontSee('Ülke Rehberi');

        // …içerik yayınlanınca HEMEN belirir ve ilk hazır ülkeye gider.
        // Bu sıra bilinçli: boş sonuç önbelleğe yapışsaydı ilk istek "yok"
        // cevabını 10 dakika kilitler, yayınlama düğmesi bozuk görünürdü.
        $this->yayindaAlmanya();
        $this->get('/nasil-calisir')->assertOk()->assertSee('Ülke Rehberi')->assertSee('/de');
    }

    public function test_footer_baglantisi_uyenin_kendi_ulkesine_gider_ilk_hazir_ulkeye_degil(): void
    {
        // F3: DE ilk hazır ülke (varsayilanUlkeKodu) — ama üye KG'de yaşıyor
        // ve KG de hazır. Footer artık sabit "ilk hazır ülke"ye değil,
        // üyenin KENDİ ülkesine gitmeli.
        $tur = $this->islemTuru();
        $this->islem($this->temsilcilik(), $tur);
        $this->islem($this->temsilcilik([
            'country_code' => 'KG', 'ad' => 'Bişkek Büyükelçiliği', 'slug' => 'biskek', 'sehir' => 'Bişkek',
        ]), $tur);

        $uye = User::factory()->create(['country_code' => 'KG', 'email_verified_at' => now()]);

        // Tam href karşılaştırması bilinçli: bare "/de" alt dizesi sayfadaki
        // </details> etiketleriyle (FAQ akordeonu) yanlış eşleşiyordu.
        $this->actingAs($uye)->get('/nasil-calisir')
            ->assertOk()
            ->assertSee(route('rehber.ulke', 'kg'))
            ->assertDontSee(route('rehber.ulke', 'de'));
    }

    public function test_footer_baglantisi_hazir_olmayan_uye_ulkesine_dayatilmaz(): void
    {
        // Üyenin ülkesi (US) rehberde hiç yok — DE'ye (ilk hazır) düşer.
        // Kırık/boş link yerine hâlâ ÇALIŞAN bir hedef.
        $this->yayindaAlmanya();

        $uye = User::factory()->create(['country_code' => 'US', 'email_verified_at' => now()]);

        $this->actingAs($uye)->get('/nasil-calisir')->assertOk()->assertSee(route('rehber.ulke', 'de'));
    }

    // ------------------------------------------------- kapsananUlkeler (Nisoya AI)

    /**
     * hazirUlkeler()'in TERSİNE: işlem içeriği olmayan ama temsilciliği olan
     * bir ülke de burada görünmeli — Nisoya AI arama çubuğunun ülke listesi
     * buradan geliyor (bkz. RehberYuzeyi::kapsananUlkeler() docblock'u).
     */
    public function test_kapsanan_ulkeler_islem_icerigi_olmayan_ulkeyi_de_dondurur(): void
    {
        $this->temsilcilik([
            'country_code' => 'KG', 'ad' => 'Bişkek Büyükelçiliği', 'slug' => 'biskek', 'sehir' => 'Bişkek',
        ]); // hiç işlem yok — bilerek

        $kodlar = app(RehberYuzeyi::class)->kapsananUlkeler()->pluck('code');

        $this->assertContains('KG', $kodlar);
        // hazirUlkeler() ise AYNI durumda KG'yi listelemez — iki metot kasıtlı farklı.
        $this->assertNotContains('KG', app(RehberYuzeyi::class)->hazirUlkeler()->pluck('code'));
    }

    public function test_kapsanan_ulkeler_pasif_temsilcilikli_ulkeyi_saymaz(): void
    {
        $this->temsilcilik(['country_code' => 'KG', 'slug' => 'biskek', 'is_active' => false]);

        $kodlar = app(RehberYuzeyi::class)->kapsananUlkeler()->pluck('code');

        $this->assertNotContains('KG', $kodlar);
    }

    // ----------------------------------------------------------- Cmd+K arama

    public function test_arama_yayindaki_islemi_bulur(): void
    {
        $this->yayindaAlmanya();

        $yanit = $this->getJson('/arama/hizli?q=vekalet')->assertOk()->json('results');

        $rehber = collect($yanit)->firstWhere('category', 'Rehber');
        $this->assertNotNull($rehber);
        $this->assertSame('Vekaletname', $rehber['title']);
        $this->assertSame('Köln Başkonsolosluğu', $rehber['subtitle']);
        $this->assertStringContainsString('/de/koeln/vekaletname', $rehber['url']);
    }

    public function test_arama_temsilcilik_adiyla_da_bulur(): void
    {
        $this->yayindaAlmanya();

        $yanit = $this->getJson('/arama/hizli?q=Köln')->assertOk()->json('results');

        $this->assertNotNull(collect($yanit)->firstWhere('category', 'Rehber'));
    }

    public function test_arama_taslagi_ve_kapali_modulu_gormez(): void
    {
        // Taslak: aramaya sızmaz (K7).
        $this->islem($this->temsilcilik(), $this->islemTuru(), TemsilcilikIslemi::STATUS_TASLAK);

        $yanit = $this->getJson('/arama/hizli?q=vekalet')->assertOk()->json('results');
        $this->assertNull(collect($yanit)->firstWhere('category', 'Rehber'));

        // Modül kapalı: yayında içerik bile dönmez.
        TemsilcilikIslemi::query()->update(['status' => TemsilcilikIslemi::STATUS_YAYIN]);
        Settings::setMany(['modul.rehber' => '0']);

        $yanit = $this->getJson('/arama/hizli?q=vekalet')->assertOk()->json('results');
        $this->assertNull(collect($yanit)->firstWhere('category', 'Rehber'));
    }
}
